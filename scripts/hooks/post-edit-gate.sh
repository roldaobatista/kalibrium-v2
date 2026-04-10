#!/usr/bin/env bash
# PostToolUse Edit|Write hook — P4 + P8 enforcement.
#
# 1. Format (nunca bloqueia)
# 2. Lint (bloqueia)
# 3. Type-check incremental (bloqueia)
# 4. Rodar teste afetado (bloqueia se vermelho)
#
# Stack-agnóstico: detecta por extensão e roda apenas se a ferramenta existe.
# Antes de ADR-0001, funciona como esqueleto (format + lint se houver).

set -euo pipefail

FILE="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$FILE" ] && exit 0
[ ! -f "$FILE" ] && exit 0

say() { echo "[post-edit-gate] $*" >&2; }
die() { echo "[post-edit-gate BLOCK] $*" >&2; exit 1; }

FILE_NORM="${FILE//\\//}"

# ---------- 1. Format (grátis, não bloqueia) ----------
case "$FILE_NORM" in
  *.ts|*.tsx|*.js|*.jsx|*.json|*.md|*.css|*.scss|*.html|*.yml|*.yaml)
    if command -v npx >/dev/null 2>&1; then
      npx prettier --write "$FILE" >/dev/null 2>&1 || true
    fi
    ;;
  *.php)
    [ -x vendor/bin/pint ] && vendor/bin/pint "$FILE" >/dev/null 2>&1 || true
    ;;
  *.py)
    command -v ruff >/dev/null 2>&1 && ruff format "$FILE" >/dev/null 2>&1 || true
    ;;
  *.rs)
    command -v rustfmt >/dev/null 2>&1 && rustfmt "$FILE" >/dev/null 2>&1 || true
    ;;
  *.go)
    command -v gofmt >/dev/null 2>&1 && gofmt -w "$FILE" >/dev/null 2>&1 || true
    ;;
esac

# ---------- 2. Lint (bloqueia) ----------
case "$FILE_NORM" in
  *.ts|*.tsx|*.js|*.jsx)
    if command -v npx >/dev/null 2>&1 && [ -f package.json ]; then
      npx eslint "$FILE" --max-warnings 0 2>&1 || die "ESLint falhou em $FILE"
    fi
    ;;
  *.py)
    if command -v ruff >/dev/null 2>&1; then
      ruff check "$FILE" || die "Ruff falhou em $FILE"
    fi
    ;;
esac

# ---------- 3. Type-check incremental (bloqueia) ----------
case "$FILE_NORM" in
  *.ts|*.tsx)
    if command -v npx >/dev/null 2>&1 && [ -f tsconfig.json ]; then
      npx tsc --noEmit 2>&1 || die "tsc falhou"
    fi
    ;;
  *.php)
    if [ -x vendor/bin/phpstan ] && [ -f phpstan.neon ]; then
      vendor/bin/phpstan analyse "$FILE" --no-progress --error-format=raw || die "phpstan falhou em $FILE"
    fi
    ;;
esac

# ---------- 4. Mapear arquivo → teste (P8 pirâmide) ----------
TEST_FILE=""
case "$FILE_NORM" in
  # JS/TS convenção: src/foo/bar.ts → tests/foo/bar.test.ts
  src/*.ts|src/*.tsx|src/*.js|src/*.jsx)
    rel="${FILE_NORM#src/}"
    ext="${rel##*.}"
    base="${rel%.*}"
    candidate="tests/${base}.test.${ext}"
    [ -f "$candidate" ] && TEST_FILE="$candidate"
    ;;
  # PHP/Laravel: app/Domains/X/Y.php → tests/Unit/Domains/X/YTest.php
  app/*.php)
    candidate="$(echo "$FILE_NORM" | sed -e 's|^app/|tests/Unit/|' -e 's|\.php$|Test.php|')"
    [ -f "$candidate" ] && TEST_FILE="$candidate"
    ;;
  # Python: src/foo/bar.py → tests/foo/test_bar.py
  src/*.py)
    dir="$(dirname "$FILE_NORM")"
    name="$(basename "$FILE_NORM" .py)"
    candidate="tests/${dir#src/}/test_${name}.py"
    [ -f "$candidate" ] && TEST_FILE="$candidate"
    ;;
esac

# ---------- 5. Rodar SÓ o teste afetado (P8 — nunca suite full) ----------
if [ -n "$TEST_FILE" ]; then
  say "rodando teste afetado: $TEST_FILE"
  case "$TEST_FILE" in
    *.test.ts|*.test.tsx|*.test.js|*.test.jsx)
      if command -v npx >/dev/null 2>&1; then
        npx vitest run "$TEST_FILE" 2>&1 || die "teste falhou: $TEST_FILE"
      fi
      ;;
    *Test.php)
      if [ -x vendor/bin/pest ]; then
        vendor/bin/pest "$TEST_FILE" --compact 2>&1 || die "teste falhou: $TEST_FILE"
      fi
      ;;
    test_*.py)
      if command -v pytest >/dev/null 2>&1; then
        pytest "$TEST_FILE" -q 2>&1 || die "teste falhou: $TEST_FILE"
      fi
      ;;
  esac
else
  say "sem teste mapeado para $FILE (ok se for template/config/doc)"
fi

say "OK: $FILE"
exit 0
