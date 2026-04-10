#!/usr/bin/env bash
# PreToolUse Read|Grep|Glob hook — enforce R3 (verifier isolation).
#
# Quando CLAUDE_AGENT_NAME == "verifier", bloqueia qualquer leitura fora de
# verification-input/. Isto é enforcement por hook, não por prompt — um
# verifier que tente ler plan.md, git log ou mensagens do implementer é
# interrompido antes da leitura acontecer.
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_AGENT_NAME    — nome do sub-agent atual ("" no agente principal)
#   CLAUDE_TOOL_ARG_FILE — caminho do arquivo que o tool tentou acessar
#   (fallback: $1)

set -euo pipefail

AGENT="${CLAUDE_AGENT_NAME:-}"
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"

# Só aplica ao verifier
if [ "$AGENT" != "verifier" ]; then
  exit 0
fi

# Verifier sem alvo identificável = block (defesa em profundidade)
if [ -z "$TARGET" ]; then
  echo "[verifier-sandbox BLOCK] verifier tentou acesso sem alvo identificado" >&2
  exit 1
fi

# Normaliza path
TARGET_NORM="${TARGET//\\//}"  # Windows backslash → forward slash

# Whitelist rígida: apenas verification-input/
case "$TARGET_NORM" in
  verification-input/*|*/verification-input/*|./verification-input/*)
    exit 0
    ;;
  *)
    echo "[verifier-sandbox BLOCK] R3: verifier nao pode acessar '$TARGET' — apenas verification-input/ permitido" >&2
    exit 1
    ;;
esac
