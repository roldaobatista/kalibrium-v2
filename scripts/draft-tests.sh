#!/usr/bin/env bash
# draft-tests.sh — validador de pré-condições para o sub-agent ac-to-test.
# Garante que plan.md existe e está aprovado antes de disparar geração de testes.
# Resolve G-07 da auditoria PM 2026-04-12.
#
# Uso:
#   bash scripts/draft-tests.sh NNN --check    (valida pré-condições)
#   bash scripts/draft-tests.sh NNN --validate (valida testes gerados)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:---check}"

if [ -z "$NNN" ]; then
  echo "Uso: draft-tests.sh NNN [--check|--validate]" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi

SPEC="specs/$NNN/spec.md"
PLAN="specs/$NNN/plan.md"
SLICE_TEST_DIR="tests/slice-$NNN"

ERR=0
fail() { echo "  ✗ $*" >&2; ERR=1; }
ok()   { echo "  ✓ $*"; }

# =========================================================================
# --check: valida pré-condições para disparar ac-to-test
# =========================================================================
if [ "$MODE" = "--check" ]; then
  echo "[draft-tests] verificando pré-condições para slice $NNN..."

  # spec.md deve existir
  if [ ! -f "$SPEC" ]; then
    fail "$SPEC ausente — rode /new-slice $NNN e /draft-spec $NNN primeiro"
    echo ""; echo "[draft-tests FAIL] pré-condições não atendidas" >&2; exit 1
  fi
  ok "$SPEC existe"

  # plan.md deve existir
  if [ ! -f "$PLAN" ]; then
    fail "$PLAN ausente — rode /draft-plan $NNN primeiro"
    echo ""; echo "[draft-tests FAIL] plan.md não existe" >&2; exit 1
  fi
  ok "$PLAN existe"

  # plan.md deve estar aprovado (status: approved)
  if grep -qiE '^.*Status:.*approved' "$PLAN"; then
    ok "plan.md está aprovado"
  else
    PLAN_STATUS=$(grep -oiE 'Status:.*$' "$PLAN" | head -1 || echo "não encontrado")
    echo "  ⚠ plan.md status: $PLAN_STATUS" >&2
    echo "  ⚠ PM precisa aprovar o plano antes de gerar testes (mude Status para approved)" >&2
    fail "plan.md não está aprovado"
  fi

  # plan-reviewer deve ter aprovado o plan em contexto isolado, sem findings.
  if bash "$SCRIPT_DIR/plan-review.sh" "$NNN" --approved >/dev/null; then
    ok "plan-review.json aprovado com findings []"
  else
    echo "  ⚠ plan-review.json ausente, reprovado ou com findings" >&2
    echo "  ⚠ rode /review-plan $NNN antes de gerar testes" >&2
    fail "plan-review obrigatório não aprovado"
  fi

  # spec.md deve ter ACs
  AC_COUNT=$(grep -cE '^\s*-\s*\*?\*?AC-[0-9]+' "$SPEC" || echo 0)
  if [ "$AC_COUNT" -ge 1 ]; then
    ok "$AC_COUNT AC(s) no spec"
  else
    fail "spec.md sem ACs — rode /draft-spec $NNN"
  fi

  # Testes AC do slice não devem existir ainda (ou o agente vai sobrescrever)
  if [ -d "$SLICE_TEST_DIR" ]; then
    EXISTING_TESTS=$(find "$SLICE_TEST_DIR" -type f -name "*Test.php" 2>/dev/null | wc -l | tr -d ' ')
  else
    EXISTING_TESTS=$(find tests/ -name "ac-${NNN}-*" -type f 2>/dev/null | wc -l | tr -d ' ')
  fi
  if [ "$EXISTING_TESTS" -gt 0 ]; then
    echo "  ⚠ $EXISTING_TESTS teste(s) AC já existem — serão sobrescritos pelo ac-to-test" >&2
  else
    ok "nenhum teste AC pré-existente para slice $NNN"
  fi

  echo ""
  if [ "$ERR" -eq 0 ]; then
    echo "[draft-tests] OK — pré-condições atendidas, pronto para disparar ac-to-test"
    exit 0
  else
    echo "[draft-tests FAIL] corrija os itens acima" >&2
    exit 1
  fi

# =========================================================================
# --validate: valida testes gerados pelo ac-to-test
# =========================================================================
elif [ "$MODE" = "--validate" ]; then
  echo "[draft-tests] validando testes gerados para slice $NNN..."

  # Contar ACs no spec
  SPEC_ACS=$(grep -oE 'AC-[0-9]+' "$SPEC" | sort -u)
  SPEC_AC_COUNT=$(echo "$SPEC_ACS" | grep -c . || echo 0)

  if [ "$SPEC_AC_COUNT" -eq 0 ]; then
    fail "spec.md sem ACs — nada para validar"
    echo ""; echo "[draft-tests FAIL]" >&2; exit 1
  fi

  # Procurar primeiro pela pasta padrão do slice; cair para o padrão legado ac-NNN-*.
  if [ -d "$SLICE_TEST_DIR" ]; then
    TEST_FILES=$(find "$SLICE_TEST_DIR" -type f -name "*Test.php" 2>/dev/null || true)
  else
    TEST_FILES=$(find tests/ -name "ac-${NNN}-*" -type f 2>/dev/null || true)
  fi
  TEST_COUNT=$(echo "$TEST_FILES" | grep -c . 2>/dev/null || echo 0)
  [ -z "$TEST_FILES" ] && TEST_COUNT=0

  if [ "$TEST_COUNT" -ge 1 ]; then
    ok "$TEST_COUNT arquivo(s) de teste encontrado(s)"
  else
    # Procurar também por padrão alternativo (test files que mencionam AC-NNN dentro)
    ALT_FILES=$(grep -rlE "AC-0*${NNN#0}" tests/ 2>/dev/null || true)
    ALT_COUNT=$(echo "$ALT_FILES" | grep -c . 2>/dev/null || echo 0)
    [ -z "$ALT_FILES" ] && ALT_COUNT=0
    if [ "$ALT_COUNT" -ge 1 ]; then
      ok "$ALT_COUNT arquivo(s) de teste mencionam ACs do slice $NNN"
      TEST_FILES="$ALT_FILES"
    else
      fail "nenhum arquivo de teste encontrado para slice $NNN"
    fi
  fi

  # Verificar cobertura: cada AC do spec tem pelo menos 1 menção em testes
  COVERED=0
  MISSING=""
  while IFS= read -r ac; do
    [ -z "$ac" ] && continue
    if echo "$TEST_FILES" | xargs grep -l "$ac" > /dev/null 2>&1; then
      COVERED=$((COVERED + 1))
    else
      MISSING="$MISSING $ac"
    fi
  done <<< "$SPEC_ACS"

  if [ "$COVERED" -eq "$SPEC_AC_COUNT" ]; then
    ok "todos os $SPEC_AC_COUNT ACs cobertos por testes"
  else
    fail "ACs sem teste:$MISSING ($COVERED/$SPEC_AC_COUNT cobertos)"
  fi

  # Verificar que nenhum teste tem TODO/TBD/FIXME/skip
  if [ -n "$TEST_FILES" ]; then
    SKIP_HITS=$(echo "$TEST_FILES" | xargs grep -lE '\b(TODO|TBD|FIXME)\b|\.skip\b|\bxtest\b|\bxit\b' 2>/dev/null || true)
    if [ -n "$SKIP_HITS" ]; then
      fail "testes com TODO/skip/FIXME:"
      echo "$SKIP_HITS" | sed 's/^/      /' >&2
    else
      ok "nenhum teste com TODO/skip/FIXME"
    fi
  fi

  echo ""
  if [ "$ERR" -eq 0 ]; then
    echo "[draft-tests] OK — testes válidos para slice $NNN"
    exit 0
  else
    echo "[draft-tests FAIL] corrija os itens acima" >&2
    exit 1
  fi
else
  echo "modo não suportado: $MODE (use --check ou --validate)" >&2
  exit 1
fi
