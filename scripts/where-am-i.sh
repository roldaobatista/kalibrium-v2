#!/usr/bin/env bash
# where-am-i.sh — mostra ao PM o estado atual de todos os slices.
#
# G-10 do guide-backlog (auditoria PM 2026-04-12). Skill on-demand complementar
# ao G-09 (session-start imprime estado automático no boot). Enquanto o G-09
# mostra 1-3 linhas concisas no systemMessage, este script é o relatório full:
#
#   - Todos os slices (ativos e merged)
#   - Arquivos estruturais presentes por slice (spec.md, plan.md, verification,
#     review)
#   - Últimos 3 eventos da telemetria por slice
#   - Próximo passo sugerido em linguagem de produto (R12)
#
# Uso:
#   bash scripts/where-am-i.sh        # lista todos os slices
#   bash scripts/where-am-i.sh NNN    # foca em 1 slice específico

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

FOCUS_NNN="${1:-}"

# ============================================================================
# Helpers (mirror de session-start.sh — duplicação aceita; refactor via
# scripts/lib/ fica pra B-024 futuro se a lógica divergir)
# ============================================================================

extract_title() {
  local spec_file="$1"
  local raw_title
  raw_title="$(grep -m1 '^#[[:space:]]' "$spec_file" 2>/dev/null | sed -E 's/^#[[:space:]]+//')"
  if [ -n "$raw_title" ] && echo "$raw_title" | grep -qE '[—-]'; then
    echo "$raw_title" | sed -E 's/^[^—-]+[—-][[:space:]]*//'
  else
    echo "$raw_title"
  fi
}

json_verdict() {
  local file="$1"
  grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$file" 2>/dev/null | head -1 | sed -E 's/.*"([^"]*)".*/\1/'
}

gate_is_approved() {
  local file="$1"
  [ -f "$file" ] && [ "$(json_verdict "$file")" = "approved" ]
}

final_gates_next_step() {
  local nnn="$1" spec_dir="$2"
  if ! gate_is_approved "${spec_dir}security-review.json"; then
    echo "rodar revisão de segurança (/security-review ${nnn})"
  elif ! gate_is_approved "${spec_dir}test-audit.json"; then
    echo "rodar auditoria de testes (/test-audit ${nnn})"
  elif ! gate_is_approved "${spec_dir}functional-review.json"; then
    echo "rodar revisão funcional (/functional-review ${nnn})"
  else
    echo "pronto para merge (/merge-slice ${nnn})"
  fi
}

# Mapeia estado do slice → próximo passo em PT-BR
next_step_for_slice() {
  local nnn="$1" last_event="$2" last_verdict="$3" spec_dir="$4"
  case "${last_event}:${last_verdict}" in
    verify:approved)
      echo "rodar revisão estrutural (/review-pr ${nnn})"
      ;;
    verify:rejected)
      echo "aguardando correção pelo implementer (rejeição 1x — tentativa nova)"
      ;;
    review:approved)
      final_gates_next_step "$nnn" "$spec_dir"
      ;;
    review:rejected)
      echo "aguardando correção pelo implementer (rejeição 1x — revisor estrutural)"
      ;;
    merge:*)
      local report="docs/retrospectives/slice-${nnn}-report.md"
      local retro="docs/retrospectives/slice-${nnn}.md"
      if [ -f "$report" ] && [ -f "$retro" ]; then
        echo "✓ slice concluído e documentado"
      else
        echo "✓ slice concluído — documentação de encerramento pendente"
      fi
      ;;
    *)
      # Sem telemetria clara — infere por artefatos estruturais
      if [ -f "${spec_dir}review.json" ]; then
        final_gates_next_step "$nnn" "$spec_dir"
      elif [ -f "${spec_dir}verification.json" ]; then
        echo "verificação concluída — rodar /review-pr ${nnn}"
      elif [ -f "${spec_dir}plan.md" ] && grep -qE '^.*Status:.*approved' "${spec_dir}plan.md"; then
        if bash "$REPO_ROOT/scripts/plan-review.sh" "$nnn" --approved > /dev/null 2>&1; then
          echo "plano pronto — próximo passo: testes (/draft-tests ${nnn})"
        else
          echo "plano precisa de revisão independente — próximo passo: /review-plan ${nnn}"
        fi
      elif [ -f "${spec_dir}plan.md" ]; then
        echo "plano gerado — próximo passo: revisão independente (/review-plan ${nnn})"
      elif bash "$REPO_ROOT/scripts/audit-spec.sh" "$nnn" --approved > /dev/null 2>&1; then
        echo "spec auditado — próximo passo: plano (/draft-plan ${nnn})"
      else
        echo "spec preenchido — próximo passo: auditoria da spec (/audit-spec ${nnn})"
      fi
      ;;
  esac
}

# Formata 1 linha de evento da telemetria pra PM
format_event() {
  local line="$1"
  local ts event verdict
  ts="$(echo "$line" | grep -o '"timestamp"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
  event="$(echo "$line" | grep -o '"event"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
  verdict="$(echo "$line" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"

  # Data em formato curto (YYYY-MM-DD HH:MM)
  local ts_short="${ts:0:16}"
  ts_short="${ts_short//T/ }"

  # Traduz event+verdict pra PT-BR
  local descr=""
  case "${event}:${verdict}" in
    verify:approved) descr="verificação automática aprovou" ;;
    verify:rejected) descr="verificação automática rejeitou" ;;
    review:approved) descr="revisão estrutural aprovou" ;;
    review:rejected) descr="revisão estrutural rejeitou" ;;
    security-review:approved) descr="revisão de segurança aprovou" ;;
    security-review:rejected) descr="revisão de segurança rejeitou" ;;
    test-audit:approved) descr="auditoria de testes aprovou" ;;
    test-audit:rejected) descr="auditoria de testes rejeitou" ;;
    functional-review:approved) descr="revisão funcional aprovou" ;;
    functional-review:rejected) descr="revisão funcional rejeitou" ;;
    merge:*)         descr="slice mergeado" ;;
    *)               descr="${event}${verdict:+ ($verdict)}" ;;
  esac

  echo "${ts_short} — ${descr}"
}

# ============================================================================
# Loop principal — varre specs/
# ============================================================================

print_slice() {
  local spec_dir="$1"
  local nnn="$2"
  local title="$3"

  local tfile=".claude/telemetry/slice-${nnn}.jsonl"

  # Determina estado: ativo (sem merge) ou fechado
  local state="em andamento"
  local has_merge=0
  if [ -f "$tfile" ] && grep -q '"event"[[:space:]]*:[[:space:]]*"merge"' "$tfile" 2>/dev/null; then
    state="✓ concluído"
    has_merge=1
  fi

  # Arquivos presentes
  local artifacts=""
  [ -f "${spec_dir}spec.md" ] && artifacts="${artifacts}spec "
  [ -f "${spec_dir}plan.md" ] && artifacts="${artifacts}plano "
  [ -f "${spec_dir}verification.json" ] && artifacts="${artifacts}verificação "
  [ -f "${spec_dir}review.json" ] && artifacts="${artifacts}revisão "
  [ -f "${spec_dir}security-review.json" ] && artifacts="${artifacts}segurança "
  [ -f "${spec_dir}test-audit.json" ] && artifacts="${artifacts}testes "
  [ -f "${spec_dir}functional-review.json" ] && artifacts="${artifacts}funcional "
  artifacts="${artifacts% }"

  # Últimos eventos (até 3)
  local last_event="-" last_verdict="-"
  if [ -f "$tfile" ] && [ -s "$tfile" ]; then
    local last_line
    last_line="$(grep -v '^$' "$tfile" | tail -1)"
    if [ -n "$last_line" ]; then
      last_event="$(echo "$last_line" | grep -o '"event"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
      last_verdict="$(echo "$last_line" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
    fi
  fi

  local next_event="$last_event" next_verdict="$last_verdict"
  if [ "$has_merge" -eq 1 ]; then
    next_event="merge"
    next_verdict="approved"
  fi

  local next
  next="$(next_step_for_slice "$nnn" "$next_event" "$next_verdict" "$spec_dir")"

  # Print bloco
  echo "─── slice-${nnn} ───"
  echo "Título:   ${title}"
  echo "Estado:   ${state}"
  echo "Entregas: ${artifacts:-(nenhuma ainda)}"

  if [ -f "$tfile" ] && [ -s "$tfile" ]; then
    echo "Últimos eventos:"
    grep -v '^$' "$tfile" | tail -3 | while IFS= read -r ev_line; do
      echo "  • $(format_event "$ev_line")"
    done
  else
    echo "Últimos eventos: (sem telemetria ainda)"
  fi

  echo "Próximo passo: ${next}"
  echo
}

# ============================================================================
# Entry point
# ============================================================================

echo "================================================================"
echo "  ONDE VOCÊ ESTÁ — estado dos slices do Kalibrium"
echo "================================================================"
echo

if [ ! -d specs ]; then
  echo "Nenhum slice foi criado ainda."
  echo
  echo "Próximo passo possível:"
  echo "  • Criar primeiro slice:          /new-slice 001 \"título em PT-BR\""
  echo "  • Pedir sugestão do próximo:     /next-slice"
  echo "  • Decidir uma tecnologia:        /decide-stack ou /adr NNNN"
  echo
  exit 0
fi

# Lista slices (com filtro opcional)
FOUND_ANY=0
ACTIVE_COUNT=0
CLOSED_COUNT=0

for s in specs/*/; do
  [ ! -d "$s" ] && continue
  nnn="$(basename "$s")"

  # NNN deve ser 3 dígitos
  if ! echo "$nnn" | grep -qE '^[0-9]{3}$'; then continue; fi

  # Filtro de foco
  if [ -n "$FOCUS_NNN" ] && [ "$nnn" != "$FOCUS_NNN" ]; then continue; fi

  [ ! -f "${s}spec.md" ] && continue

  title="$(extract_title "${s}spec.md")"
  [ -z "$title" ] && title="slice $nnn"

  print_slice "$s" "$nnn" "$title"

  FOUND_ANY=1

  # Conta estado (só quando não há filtro de foco)
  if [ -z "$FOCUS_NNN" ]; then
    tfile=".claude/telemetry/slice-${nnn}.jsonl"
    if [ -f "$tfile" ] && grep -q '"event"[[:space:]]*:[[:space:]]*"merge"' "$tfile" 2>/dev/null; then
      CLOSED_COUNT=$((CLOSED_COUNT+1))
    else
      ACTIVE_COUNT=$((ACTIVE_COUNT+1))
    fi
  fi
done

if [ "$FOUND_ANY" -eq 0 ]; then
  if [ -n "$FOCUS_NNN" ]; then
    echo "Slice $FOCUS_NNN não existe."
    echo "Use o comando sem argumento pra listar todos: bash scripts/where-am-i.sh"
  else
    echo "Nenhum slice foi criado ainda."
    echo
    echo "Próximo passo possível:"
    echo "  • Criar primeiro slice:          /new-slice 001 \"título em PT-BR\""
    echo "  • Pedir sugestão do próximo:     /next-slice"
  fi
  exit 0
fi

# Resumo final (só quando não há filtro)
if [ -z "$FOCUS_NNN" ]; then
  echo "================================================================"
  echo "Resumo: ${ACTIVE_COUNT} em andamento | ${CLOSED_COUNT} concluído(s)"
  echo "================================================================"
fi
