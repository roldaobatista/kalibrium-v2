#!/usr/bin/env bash
# mechanical-gates.sh — Gates mecanicos binarios (passa/falha).
# Roda ANTES de qualquer gate de agente LLM.
# Se qualquer gate falhar, o agente NAO e spawnado.
#
# Uso:
#   bash scripts/mechanical-gates.sh NNN              # gates completos (pre-verify)
#   bash scripts/mechanical-gates.sh NNN --quick       # gates rapidos (pre-commit)
#
# Exit codes:
#   0 = todos os gates passaram
#   1 = pelo menos um gate falhou (detalhes no stderr)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:-full}"

say()  { echo "[mechanical-gates] $*"; }
fail() { echo "[mechanical-gates FAIL] $*" >&2; }
pass() { echo "[mechanical-gates PASS] $*"; }

FAILURES=0

# ============================================================
# Gate 1: Testes passam DE VERDADE (nao lido de arquivo)
# ============================================================
say "Gate 1/5: rodando testes..."

if [ -f "vendor/bin/pest" ]; then
  TEST_FILTER=""
  if [ -n "$NNN" ] && [ -d "tests" ]; then
    # Tenta rodar testes do slice se possivel, senao roda tudo
    TEST_FILTER="tests/"
  fi

  TEST_OUTPUT=$(vendor/bin/pest $TEST_FILTER 2>&1)
  TEST_EXIT=$?

  if [ $TEST_EXIT -ne 0 ]; then
    fail "Gate 1 FALHOU — testes nao passam (exit $TEST_EXIT)"
    echo "$TEST_OUTPUT" | tail -20 >&2
    FAILURES=$((FAILURES + 1))
  else
    PASS_COUNT=$(echo "$TEST_OUTPUT" | grep -oE '[0-9]+ passed' | head -1 || echo "? passed")
    pass "Gate 1 — testes OK ($PASS_COUNT)"
  fi
else
  fail "Gate 1 FALHOU — vendor/bin/pest nao encontrado (rode composer install)"
  FAILURES=$((FAILURES + 1))
fi

# ============================================================
# Gate 2: PHPStan level 8 (analise estatica)
# ============================================================
say "Gate 2/5: PHPStan analyse..."

if [ -f "vendor/bin/phpstan" ]; then
  PHPSTAN_OUTPUT=$(vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1)
  PHPSTAN_EXIT=$?

  if [ $PHPSTAN_EXIT -ne 0 ]; then
    fail "Gate 2 FALHOU — PHPStan encontrou erros"
    echo "$PHPSTAN_OUTPUT" >&2
    FAILURES=$((FAILURES + 1))
  else
    pass "Gate 2 — PHPStan level 8 OK (0 errors)"
  fi
else
  fail "Gate 2 FALHOU — vendor/bin/phpstan nao encontrado"
  FAILURES=$((FAILURES + 1))
fi

# ============================================================
# Gate 3: Pint (formatacao PSR-12)
# ============================================================
say "Gate 3/5: Pint format check..."

if [ -f "vendor/bin/pint" ]; then
  PINT_OUTPUT=$(vendor/bin/pint --test 2>&1)
  PINT_EXIT=$?

  if [ $PINT_EXIT -ne 0 ]; then
    fail "Gate 3 FALHOU — codigo fora do padrao de formatacao"
    echo "$PINT_OUTPUT" >&2
    FAILURES=$((FAILURES + 1))
  else
    pass "Gate 3 — Pint PSR-12 OK"
  fi
else
  fail "Gate 3 FALHOU — vendor/bin/pint nao encontrado"
  FAILURES=$((FAILURES + 1))
fi

# Quick mode para em 3 gates (pre-commit)
if [ "$MODE" = "--quick" ]; then
  if [ $FAILURES -gt 0 ]; then
    say "RESULTADO: $FAILURES gate(s) mecanico(s) falharam (modo quick)"
    exit 1
  else
    say "RESULTADO: todos os gates rapidos passaram"
    exit 0
  fi
fi

# ============================================================
# Gate 4: Composer audit (vulnerabilidades em dependencias)
# ============================================================
say "Gate 4/5: composer audit..."

AUDIT_OUTPUT=$(composer audit 2>&1)
AUDIT_EXIT=$?

if [ $AUDIT_EXIT -ne 0 ]; then
  fail "Gate 4 FALHOU — vulnerabilidades encontradas em dependencias"
  echo "$AUDIT_OUTPUT" >&2
  FAILURES=$((FAILURES + 1))
else
  pass "Gate 4 — 0 vulnerabilidades em dependencias"
fi

# ============================================================
# Gate 5: Coverage minima (se disponivel)
# ============================================================
say "Gate 5/5: coverage check..."

if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi xdebug; then
  COV_OUTPUT=$(vendor/bin/pest --coverage --min=80 2>&1)
  COV_EXIT=$?

  if [ $COV_EXIT -ne 0 ]; then
    fail "Gate 5 FALHOU — cobertura abaixo de 80%"
    echo "$COV_OUTPUT" | tail -10 >&2
    FAILURES=$((FAILURES + 1))
  else
    pass "Gate 5 — cobertura >= 80%"
  fi
else
  say "Gate 5 — SKIP (Xdebug/PCOV nao disponivel para coverage)"
fi

# ============================================================
# Resultado final
# ============================================================
echo ""
if [ $FAILURES -gt 0 ]; then
  say "RESULTADO: $FAILURES gate(s) mecanico(s) falharam"
  say "Agentes de review NAO serao spawnados ate correcao."
  exit 1
else
  say "RESULTADO: todos os gates mecanicos passaram"
  say "Agentes de review podem ser spawnados."
  exit 0
fi
