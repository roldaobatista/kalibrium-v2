#!/usr/bin/env bash
# PreToolUse Bash(git commit*) hook — enforcement P1/P4/P6/P9 + R5/R9.
#
# 1. Detecta bypass (--no-verify, SKIP=, etc.)  -> R9
# 2. Valida autor do commit                      -> R5
# 3. Valida mensagem (sem auto-gen, sem rodada N APROVADO)
# 4. Lint + type-check + testes afetados nos arquivos staged -> P1/P4
# 5. Scan por secrets staged                     -> defesa em profundidade

set -euo pipefail

CMD="${CLAUDE_TOOL_ARG_COMMAND:-${1:-}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

say() { echo "[pre-commit-gate] $*" >&2; }
die() { echo "[pre-commit-gate BLOCK] $*" >&2; exit 1; }

# ---------- 1. R9 — bypass detection ----------
if echo "$CMD" | grep -qE -- "--no-verify|--no-gpg-sign"; then
  die "R9/P9: bypass de gate proibido ('$CMD')"
fi
if [ -n "${SKIP:-}" ] || [ -n "${HUSKY:-}" ] || [ -n "${PRE_COMMIT:-}" ]; then
  die "R9/P9: variáveis de bypass detectadas (SKIP/HUSKY/PRE_COMMIT)"
fi

# ---------- 2. R5 — autor ----------
AUTHOR_NAME="$(git config user.name 2>/dev/null || true)"
AUTHOR_EMAIL="$(git config user.email 2>/dev/null || true)"

if [ -z "$AUTHOR_NAME" ] || [ -z "$AUTHOR_EMAIL" ]; then
  die "R5: git user.name/email não configurados"
fi

case "$AUTHOR_NAME" in
  auto-*|Auto-*|AUTO-*)
    die "R5: autor '$AUTHOR_NAME' matches 'auto-*'"
    ;;
esac

case "$AUTHOR_EMAIL" in
  noreply@*|no-reply@*)
    # Permitido APENAS se a mensagem contiver Co-Authored-By com email válido
    if ! echo "$CMD" | grep -qi "Co-Authored-By:"; then
      die "R5: email noreply sem Co-Authored-By"
    fi
    ;;
esac

# ---------- 3. Mensagem ----------
# Extrai a mensagem do comando (aceita -m "..." ou heredoc)
MSG="$(echo "$CMD" | grep -oE -- "-m [\"'][^\"']+[\"']" | head -1 | sed -E 's/-m ["'"'"']//;s/["'"'"']$//' || true)"

if [ -n "$MSG" ]; then
  case "$MSG" in
    Auto-generated*|auto-commit*|"Auto commit"*)
      die "R5: mensagem proibida: '$MSG'"
      ;;
  esac
  if echo "$MSG" | grep -qiE "rodada [0-9]+.*aprovad[oa]"; then
    die "R5: padrão 'rodada N aprovado' é anti-pattern do V1"
  fi
fi

# ---------- 4. Secrets scan em staged files ----------
STAGED="$(git diff --cached --name-only 2>/dev/null || true)"
if [ -n "$STAGED" ]; then
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    base="$(basename "$f")"
    case "$base" in
      .env|.env.local|.env.production|credentials|credentials.json|*.key|*.pem|*.p12)
        die "secret detectado em staged: $f"
        ;;
    esac
    # Grep por patterns comuns (simples, não-exaustivo)
    if [ -f "$f" ]; then
      if grep -qiE "(api[_-]?key|secret|password|token)[[:space:]]*=[[:space:]]*['\"][a-z0-9]{20,}" "$f" 2>/dev/null; then
        say "WARN: possível segredo hardcoded em $f — revise antes de commitar"
      fi
    fi
  done <<< "$STAGED"
fi

# ---------- 5. Lint + type-check nos arquivos staged (P1/P4) ----------
if [ -n "$STAGED" ]; then
  # JS/TS
  TS_STAGED="$(echo "$STAGED" | grep -E '\.(ts|tsx|js|jsx)$' || true)"
  if [ -n "$TS_STAGED" ] && command -v npx >/dev/null 2>&1 && [ -f package.json ]; then
    say "rodando eslint nos staged..."
    # shellcheck disable=SC2086
    npx eslint $TS_STAGED --max-warnings 0 2>&1 || die "ESLint falhou nos arquivos staged"

    if [ -f tsconfig.json ]; then
      say "rodando tsc --noEmit..."
      npx tsc --noEmit 2>&1 || die "tsc falhou"
    fi
  fi

  # PHP
  PHP_STAGED="$(echo "$STAGED" | grep '\.php$' || true)"
  if [ -n "$PHP_STAGED" ] && [ -x vendor/bin/pint ]; then
    say "rodando pint --test nos staged..."
    # shellcheck disable=SC2086
    vendor/bin/pint --test $PHP_STAGED 2>&1 || die "Pint falhou"
  fi

  # Python
  PY_STAGED="$(echo "$STAGED" | grep '\.py$' || true)"
  if [ -n "$PY_STAGED" ] && command -v ruff >/dev/null 2>&1; then
    say "rodando ruff check..."
    # shellcheck disable=SC2086
    ruff check $PY_STAGED 2>&1 || die "Ruff falhou"
  fi
fi

# ---------- 6. Testes afetados (grupo do módulo — P8) ----------
# Ainda não roda suite full. Roda grupo do módulo dos arquivos staged.
# Implementação concreta depende da convenção de módulos pós ADR-0001.
# Por ora, faz o mínimo: valida que existe ao menos 1 arquivo de teste no diff.
if [ -n "$STAGED" ]; then
  HAS_TEST="$(echo "$STAGED" | grep -E '(test|spec|Test|_test)' || true)"
  HAS_CODE="$(echo "$STAGED" | grep -vE '(test|spec|Test|_test|\.md$|\.json$|\.yml$|\.yaml$)' || true)"
  if [ -n "$HAS_CODE" ] && [ -z "$HAS_TEST" ]; then
    say "WARN: commit com código mas sem teste no diff — P2 exige AC mapeado a teste"
  fi
fi

say "todos os gates passaram"
exit 0
