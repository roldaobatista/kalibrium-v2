#!/usr/bin/env bash
# SessionStart hook — valida o harness antes de qualquer ação do agente.
# Falha dura (exit 1) se qualquer regra fundamental for violada.
#
# Responsável por:
#   - Verificar arquivos obrigatórios (CLAUDE.md, constitution.md, settings.json)
#   - R1 — rejeitar arquivos de instrução proibidos
#   - Verificar que hooks referenciados em settings.json existem
#   - Drift checks (settings-lock, hooks-lock) — itens 1.1, 1.2 meta-audit
#   - [G-09, 2026-04-12] Estado do slice ativo (telemetria + próximo passo PM)
#   - Emitir mensagem estruturada para o Claude Code carregar na sessão

set -euo pipefail

# Resolve repo root (pasta que contém CLAUDE.md)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

ERRORS=0
err() { echo "[session-start ERR] $*" >&2; ERRORS=$((ERRORS+1)); }
ok()  { echo "[session-start OK ] $*" >&2; }
info(){ echo "[session-start INFO] $*" >&2; }

# ---------- 1. Arquivos obrigatórios ----------
REQUIRED=(
  "CLAUDE.md"
  "docs/constitution.md"
  ".claude/settings.json"
)
for f in "${REQUIRED[@]}"; do
  if [ ! -f "$f" ]; then
    err "arquivo obrigatório ausente: $f"
  else
    ok "encontrado $f"
  fi
done

# ---------- 2. R1 — arquivos/pastas proibidas ----------
FORBIDDEN_FILES=(
  ".cursorrules"
  "AGENTS.md"
  "GEMINI.md"
  "copilot-instructions.md"
  ".windsurfrules"
  ".aider.conf.yml"
)
FORBIDDEN_DIRS=(
  ".bmad-core"
  ".agents"
  ".cursor"
  ".continue"
)

R1_HITS=0
for f in "${FORBIDDEN_FILES[@]}"; do
  if [ -f "$f" ]; then
    err "R1 violada: arquivo proibido encontrado: $f"
    R1_HITS=$((R1_HITS+1))
  fi
done
for d in "${FORBIDDEN_DIRS[@]}"; do
  if [ -d "$d" ]; then
    err "R1 violada: diretório proibido encontrado: $d"
    R1_HITS=$((R1_HITS+1))
  fi
done
[ "$R1_HITS" -eq 0 ] && ok "R1 nenhum arquivo/pasta proibido"

# ---------- 3. Hooks referenciados existem ----------
if [ -f .claude/settings.json ]; then
  # grep simples sem jq (cross-platform)
  while IFS= read -r line; do
    script="$(echo "$line" | grep -oE 'scripts/hooks/[a-z-]+\.sh' || true)"
    if [ -n "$script" ] && [ ! -f "$script" ]; then
      err "hook referenciado mas ausente: $script"
    fi
  done < .claude/settings.json
  ok "hooks referenciados verificados"
fi

# ---------- 4. Pastas de suporte ----------
for d in ".claude/telemetry" ".claude/snapshots" "docs/audits" "docs/incidents" "docs/retrospectives" "docs/explanations"; do
  if [ ! -d "$d" ]; then
    mkdir -p "$d" 2>/dev/null && ok "criou $d" || err "não consegui criar $d"
  fi
done

# ---------- 4.5. Drift checks (meta-audit 2026-04-10 itens 1.1, 1.2) ----------
if [ -f scripts/hooks/settings-lock.sh ]; then
  if bash scripts/hooks/settings-lock.sh --check >/tmp/settings-lock-check.out 2>&1; then
    ok "settings-lock --check: settings.json íntegro"
  else
    err "settings-lock --check FAIL"
    sed 's/^/  /' /tmp/settings-lock-check.out >&2
  fi
fi

if [ -f scripts/hooks/hooks-lock.sh ]; then
  if bash scripts/hooks/hooks-lock.sh --check >/tmp/hooks-lock-check.out 2>&1; then
    ok "hooks-lock --check: scripts/hooks/ íntegro"
  else
    err "hooks-lock --check FAIL"
    sed 's/^/  /' /tmp/hooks-lock-check.out >&2
  fi
fi

# ============================================================================
# 4.6. G-09 — Estado do slice ativo
#
# Objetivo: PM abre Claude Code no dia seguinte e vê imediatamente "você
# parou em X, próximo passo é Y" — elimina o gap de perda de estado da
# auditoria de operabilidade PM (2026-04-12).
#
# Lógica:
#   1. Descobre todos os slices com spec.md em specs/*/
#   2. Filtra os "ativos" — sem evento "merge" na telemetria
#   3. Para cada ativo, extrai último evento de .claude/telemetry/slice-NNN.jsonl
#   4. Mapeia (last_event, last_verdict) → próximo passo sugerido
#   5. Imprime bloco resumido em stderr + adiciona ao systemMessage (seção 5)
#
# Tolerante a ausência: se specs/ não existe ou está vazio, gera mensagem
# "nenhum slice em andamento" sem falhar.
# ============================================================================

ACTIVE_SLICE_LINES=""
ACTIVE_COUNT=0

if [ -d specs ]; then
  # Lista slices com spec.md (um por diretório)
  for s in specs/*/; do
    [ ! -d "$s" ] && continue
    nnn="$(basename "$s")"

    # NNN deve ser 3 dígitos (ignora specs/templates ou outros)
    if ! echo "$nnn" | grep -qE '^[0-9]{3}$'; then continue; fi

    [ ! -f "${s}spec.md" ] && continue

    t=".claude/telemetry/slice-${nnn}.jsonl"

    # "Ativo" = spec.md existe e NÃO há evento merge na telemetria
    if [ -f "$t" ] && grep -q '"event"[[:space:]]*:[[:space:]]*"merge"' "$t" 2>/dev/null; then
      continue  # já merged, pula
    fi

    # Extrai título do spec.
    # Estratégia robusta: pega primeira linha "# ...", remove "# ", e se tiver
    # um separador (— ou -) após prefixo tipo "slice 999", pega o que vem depois.
    # Funciona com "# slice 999 — Título", "# 999 — Título", "# Título livre".
    raw_title="$(grep -m1 '^#[[:space:]]' "${s}spec.md" | sed -E 's/^#[[:space:]]+//')"
    if echo "$raw_title" | grep -qE '[—-]'; then
      # Tem separador — pega depois do primeiro
      title="$(echo "$raw_title" | sed -E 's/^[^—-]+[—-][[:space:]]*//')"
    else
      title="$raw_title"
    fi
    [ -z "$title" ] && title="slice $nnn"

    # Último evento (se houver telemetria)
    last_event="-"
    last_verdict="-"
    if [ -f "$t" ] && [ -s "$t" ]; then
      last_line="$(grep -v '^$' "$t" | tail -1)"
      if [ -n "$last_line" ]; then
        last_event="$(echo "$last_line" | grep -o '"event"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
        last_verdict="$(echo "$last_line" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
      fi
    fi

    # Mapeia estado → próximo passo (linguagem de produto, R12)
    next=""
    case "${last_event}:${last_verdict}" in
      verify:approved)
        next="rodar revisão estrutural (/review-pr ${nnn})"
        ;;
      verify:rejected)
        next="aguardando correção pelo implementer"
        ;;
      review:approved)
        next="pronto para merge (/merge-slice ${nnn})"
        ;;
      review:rejected)
        next="aguardando correção pelo implementer"
        ;;
      *:*)
        # Sem telemetria clara — infere por artefatos estruturais
        if [ -f "${s}review.json" ]; then
          next="revisão concluída — rodar /merge-slice ${nnn}"
        elif [ -f "${s}verification.json" ]; then
          next="verificação concluída — rodar /review-pr ${nnn}"
        elif [ -f "${s}plan.md" ] && [ -s "${s}plan.md" ]; then
          next="plano pronto — próximo passo: testes (/draft-tests ${nnn})"
        else
          next="spec preenchido — próximo passo: plano (/draft-plan ${nnn})"
        fi
        ;;
    esac

    ACTIVE_COUNT=$((ACTIVE_COUNT+1))

    # Uma linha por slice, limitada pra não inflar o systemMessage
    line="slice-${nnn}: ${title} — próximo passo: ${next}"
    if [ -z "$ACTIVE_SLICE_LINES" ]; then
      ACTIVE_SLICE_LINES="$line"
    else
      ACTIVE_SLICE_LINES="${ACTIVE_SLICE_LINES}"$'\n'"$line"
    fi

    # Para de listar depois de 3 (evita spam se tiver muitos slices abandonados)
    [ "$ACTIVE_COUNT" -ge 3 ] && break
  done
fi

if [ "$ACTIVE_COUNT" -eq 0 ]; then
  info "nenhum slice em andamento (specs/ vazio ou todos merged)"
else
  info "slice(s) ativo(s) detectado(s): $ACTIVE_COUNT"
  while IFS= read -r l; do
    info "  $l"
  done <<< "$ACTIVE_SLICE_LINES"
fi

# ---------- 5. Resultado ----------
if [ "$ERRORS" -eq 0 ]; then
  # Constroi systemMessage dinamicamente incorporando estado do slice ativo.
  # Formato JSON single-line (Claude Code faz o parse e injeta como system msg).

  BASE_MSG="[SessionStart OK] Leia obrigatoriamente antes de qualquer acao: CLAUDE.md + docs/constitution.md + docs/TECHNICAL-DECISIONS.md. Regras P1-P9 e R1-R12 aplicam-se a TODA interacao. Verificacao de fato antes de afirmacao (P7)."

  if [ "$ACTIVE_COUNT" -gt 0 ]; then
    # Escapa quebras de linha pra JSON (troca por ' | ')
    SLICE_SUMMARY="$(echo "$ACTIVE_SLICE_LINES" | tr '\n' '|' | sed 's/|/ | /g' | sed 's/ | $//')"
    FULL_MSG="${BASE_MSG} ESTADO DO SLICE ATIVO (G-09): ${SLICE_SUMMARY}. Retome pelo proximo passo indicado ou pergunte ao PM se ele quer mudar de direcao."
  else
    FULL_MSG="${BASE_MSG} Nenhum slice em andamento no momento — aguardando PM iniciar novo slice ou decidir proximo passo."
  fi

  # Escapa aspas e backslashes pra JSON
  ESCAPED_MSG="$(printf '%s' "$FULL_MSG" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"

  printf '{"systemMessage":"%s"}\n' "$ESCAPED_MSG"
  exit 0
else
  echo "[session-start] $ERRORS erro(s) — abortando sessão" >&2
  echo "Corrija os erros acima antes de continuar." >&2
  exit 1
fi
