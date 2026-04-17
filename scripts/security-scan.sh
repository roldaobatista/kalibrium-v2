#!/usr/bin/env bash
# security-scan.sh — Scans mecanicos de seguranca.
# Roda ANTES do security-expert (modo security-gate, agente LLM).
# Se qualquer scan falhar, o agente NAO e spawnado.
#
# Uso: bash scripts/security-scan.sh [NNN]
#
# Exit codes:
#   0 = limpo
#   1 = vulnerabilidade encontrada

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"

say()  { echo "[security-scan] $*"; }
fail() { echo "[security-scan FAIL] $*" >&2; }
pass() { echo "[security-scan PASS] $*"; }

# shellcheck source=scripts/bootstrap-bash-php.sh
source "$SCRIPT_DIR/bootstrap-bash-php.sh" || true

FAILURES=0

# ============================================================
# Scan 1: composer audit (CVEs em dependencias)
# ============================================================
say "Scan 1/4: composer audit..."

AUDIT_OUTPUT=$(composer audit 2>&1)
AUDIT_EXIT=$?

if [ $AUDIT_EXIT -ne 0 ]; then
  fail "Scan 1 — vulnerabilidades em dependencias"
  echo "$AUDIT_OUTPUT" >&2
  FAILURES=$((FAILURES + 1))
else
  pass "Scan 1 — 0 CVEs em dependencias"
fi

# ============================================================
# Scan 2: Secrets hardcoded (grep patterns)
# ============================================================
say "Scan 2/4: secrets scan..."

SECRET_PATTERNS=(
  'password\s*=\s*["\x27][^"\x27]{3,}'
  'api_key\s*=\s*["\x27][^"\x27]{3,}'
  'secret\s*=\s*["\x27][^"\x27]{3,}'
  'token\s*=\s*["\x27][a-zA-Z0-9]{20,}'
  'PRIVATE.KEY'
  'BEGIN RSA PRIVATE KEY'
  'BEGIN EC PRIVATE KEY'
  'BEGIN OPENSSH PRIVATE KEY'
)

SECRETS_FOUND=0
for pattern in "${SECRET_PATTERNS[@]}"; do
  HITS=$(grep -rnE "$pattern" src/ tests/ 2>/dev/null | grep -v '\.example' | grep -v 'test' | grep -v 'mock' | grep -v 'fake' || true)
  if [ -n "$HITS" ]; then
    fail "Scan 2 — possivel secret encontrado:"
    echo "$HITS" >&2
    SECRETS_FOUND=$((SECRETS_FOUND + 1))
  fi
done

if [ $SECRETS_FOUND -gt 0 ]; then
  FAILURES=$((FAILURES + 1))
else
  pass "Scan 2 — 0 secrets hardcoded detectados"
fi

# ============================================================
# Scan 3: .env ou credentials no staged/committed
# ============================================================
say "Scan 3/4: arquivos sensiveis..."

SENSITIVE_FILES=$(git ls-files | grep -iE '\.env$|credentials|\.key$|\.pem$|\.p12$|\.pfx$' 2>/dev/null || true)

if [ -n "$SENSITIVE_FILES" ]; then
  fail "Scan 3 — arquivos sensiveis no repositorio:"
  echo "$SENSITIVE_FILES" >&2
  FAILURES=$((FAILURES + 1))
else
  pass "Scan 3 — 0 arquivos sensiveis no repositorio"
fi

# ============================================================
# Scan 4: PHPStan com foco em seguranca (se disponivel)
# ============================================================
say "Scan 4/4: PHPStan security analysis..."

if [ -n "${PHP_BIN:-}" ] && [ -f "vendor/bin/phpstan" ]; then
  # PHPStan level 8 ja pega type errors que podem ser vulnerabilidades
  PHPSTAN_OUTPUT=$("$PHP_BIN" vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1)
  PHPSTAN_EXIT=$?

  if [ $PHPSTAN_EXIT -ne 0 ]; then
    fail "Scan 4 — PHPStan encontrou erros (podem ser vulnerabilidades)"
    echo "$PHPSTAN_OUTPUT" >&2
    FAILURES=$((FAILURES + 1))
  else
    pass "Scan 4 — PHPStan level 8 OK"
  fi
elif [ -z "${PHP_BIN:-}" ]; then
  fail "Scan 4 — binario PHP nao encontrado"
  FAILURES=$((FAILURES + 1))
else
  say "Scan 4 — SKIP (phpstan nao instalado)"
fi

# ============================================================
# Resultado
# ============================================================
echo ""
if [ $FAILURES -gt 0 ]; then
  say "RESULTADO: $FAILURES scan(s) falharam"
  say "Security-reviewer NAO sera spawnado ate correcao."
  exit 1
else
  say "RESULTADO: todos os scans passaram"
  say "Security-reviewer pode ser spawnado."
  exit 0
fi
