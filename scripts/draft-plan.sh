#!/usr/bin/env bash
# draft-plan.sh — validador de pré-condições para o sub-agent architect.
# Garante que spec.md existe e está válido antes de disparar plan.md.
# Resolve G-05 da auditoria PM 2026-04-12.
#
# Uso:
#   bash scripts/draft-plan.sh NNN --check    (valida pré-condições)
#   bash scripts/draft-plan.sh NNN --validate (valida plan.md gerado)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:---check}"

if [ -z "$NNN" ]; then
  echo "Uso: draft-plan.sh NNN [--check|--validate]" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi

SPEC="specs/$NNN/spec.md"
PLAN="specs/$NNN/plan.md"

ERR=0
fail() { echo "  ✗ $*" >&2; ERR=1; }
ok()   { echo "  ✓ $*"; }

# =========================================================================
# --check: valida pré-condições para disparar architect
# =========================================================================
if [ "$MODE" = "--check" ]; then
  echo "[draft-plan] verificando pré-condições para slice $NNN..."

  # spec.md deve existir
  if [ ! -f "$SPEC" ]; then
    fail "$SPEC ausente — rode /new-slice $NNN primeiro, depois /draft-spec $NNN"
    echo ""; echo "[draft-plan FAIL] pré-condições não atendidas" >&2; exit 1
  fi
  ok "$SPEC existe"

  # spec.md deve passar validação do draft-spec
  if bash "$REPO_ROOT/scripts/draft-spec.sh" "$NNN" --check > /dev/null 2>&1; then
    ok "spec.md passa validação (draft-spec --check)"
  else
    fail "spec.md não passa validação — rode /draft-spec $NNN primeiro"
    echo ""; echo "[draft-plan FAIL] spec.md inválido" >&2; exit 1
  fi

  # spec.md deve ter auditoria independente aprovada antes do architect
  if [ -f "$REPO_ROOT/scripts/audit-spec.sh" ] && bash "$REPO_ROOT/scripts/audit-spec.sh" "$NNN" --approved > /dev/null 2>&1; then
    ok "spec-audit.json aprovado"
  else
    fail "spec-audit.json ausente ou não aprovado — rode /audit-spec $NNN antes de /draft-plan"
    echo ""; echo "[draft-plan FAIL] auditoria de spec pendente" >&2; exit 1
  fi

  # plan.md ainda não deve existir (ou deve estar em draft)
  if [ -f "$PLAN" ]; then
    if grep -qE '^.*Status:.*approved' "$PLAN"; then
      fail "plan.md já existe e está aprovado — não sobrescrever"
      echo ""; echo "[draft-plan FAIL] plan.md já aprovado" >&2; exit 1
    else
      echo "  ⚠ plan.md existe em draft — será sobrescrito pelo architect" >&2
    fi
  else
    ok "plan.md ainda não existe — pronto para gerar"
  fi

  # constitution e ADRs acessíveis
  [ -f "docs/constitution.md" ] && ok "constitution.md acessível" || fail "docs/constitution.md ausente"
  [ -f "docs/TECHNICAL-DECISIONS.md" ] && ok "TECHNICAL-DECISIONS.md acessível" || fail "docs/TECHNICAL-DECISIONS.md ausente"

  echo ""
  if [ "$ERR" -eq 0 ]; then
    echo "[draft-plan] OK — pré-condições atendidas, pronto para disparar architect"
    exit 0
  else
    echo "[draft-plan FAIL] corrija os itens acima" >&2
    exit 1
  fi

# =========================================================================
# --validate: valida plan.md gerado pelo architect
# =========================================================================
elif [ "$MODE" = "--validate" ]; then
  echo "[draft-plan] validando $PLAN..."

  [ ! -f "$PLAN" ] && { fail "$PLAN ausente — architect não gerou?"; echo ""; echo "[draft-plan FAIL]" >&2; exit 1; }
  ok "$PLAN existe"

  # Placeholders do template ainda presentes
  if grep -qE '<decisão>|<descrição>|<justificativa|<razão>|<path>|<risco' "$PLAN"; then
    fail "placeholders do template ainda presentes (<decisão>, <descrição>, etc.)"
  else
    ok "placeholders do template removidos"
  fi

  # Seções obrigatórias
  for section in "Decisões arquiteturais" "Mapeamento AC" "Riscos e mitigações"; do
    if awk -v s="$section" '
      BEGIN { found=0; nonempty=0 }
      $0 ~ "^##[[:space:]]+" s { found=1; next }
      found && /^##[[:space:]]/ { exit }
      found && NF>=3 { nonempty=1 }
      END { exit nonempty?0:1 }
    ' "$PLAN"; then
      ok "seção '$section' preenchida"
    else
      fail "seção '$section' vazia ou ausente"
    fi
  done

  # Pelo menos 1 decisão (D1, D2, ...)
  D_COUNT=$(grep -cE '^### D[0-9]+' "$PLAN" || echo 0)
  if [ "$D_COUNT" -ge 1 ]; then
    ok "$D_COUNT decisão(ões) arquitetural(is)"
  else
    fail "nenhuma decisão arquitetural (esperado ### D1: ...)"
  fi

  # Mapeamento cobre ACs do spec
  SPEC_ACS=$(grep -oE 'AC-[0-9]+' "$SPEC" | sort -u | wc -l | tr -d ' ')
  PLAN_ACS=$(grep -oE 'AC-[0-9]+' "$PLAN" | sort -u | wc -l | tr -d ' ')
  if [ "$PLAN_ACS" -ge "$SPEC_ACS" ] && [ "$SPEC_ACS" -gt 0 ]; then
    ok "plan cobre $PLAN_ACS/$SPEC_ACS ACs do spec"
  elif [ "$SPEC_ACS" -eq 0 ]; then
    fail "spec não tem ACs — rode /draft-spec $NNN"
  else
    fail "plan cobre $PLAN_ACS/$SPEC_ACS ACs — faltam ACs no mapeamento"
  fi

  echo ""
  if [ "$ERR" -eq 0 ]; then
    echo "[draft-plan] OK — plan.md válido"
    exit 0
  else
    echo "[draft-plan FAIL] corrija os itens acima" >&2
    exit 1
  fi
else
  echo "modo não suportado: $MODE (use --check ou --validate)" >&2
  exit 1
fi
