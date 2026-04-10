#!/usr/bin/env bash
# PreToolUse Read hook — bloqueia leitura de arquivos de segredo.
# Complementar ao deny de settings.json (defesa em profundidade).
set -euo pipefail

TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$TARGET" ] && exit 0

TARGET_NORM="${TARGET//\\//}"
BASENAME="$(basename "$TARGET_NORM")"

# Exceções explícitas checadas PRIMEIRO (matcher mais específico vence)
case "$BASENAME" in
  .env.example|.env.testing|.env.testing.example|.env.sample)
    exit 0
    ;;
esac

# Padrões proibidos
case "$BASENAME" in
  .env|.env.*|.envrc)
    echo "[read-secrets-block BLOCK] leitura de $TARGET proibida (.env*)" >&2
    exit 1
    ;;
  credentials.*|credentials|secrets.*|secret.*)
    echo "[read-secrets-block BLOCK] leitura de $TARGET proibida (credentials/secrets)" >&2
    exit 1
    ;;
  *.key|*.pem|*.p12|*.pfx|*.keystore)
    echo "[read-secrets-block BLOCK] leitura de $TARGET proibida (*.key/*.pem/etc.)" >&2
    exit 1
    ;;
esac

exit 0
