#!/usr/bin/env bash
# PreToolUse Read|Grep|Glob hook — enforce R3 (verifier isolation) + R11 (dual-verifier).
#
# Dois contextos isolados:
#   - verifier: só pode ler verification-input/   (R3)
#   - reviewer: só pode ler review-input/         (R11)
#              + BLOQUEADO de ler verification-input/ e specs/*/verification.json
#              (R11 — reviewer não vê output do verifier)
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_AGENT_NAME    — nome do sub-agent atual ("" no agente principal)
#   CLAUDE_TOOL_ARG_FILE — caminho do arquivo que o tool tentou acessar
#   (fallback: $1)

set -euo pipefail

AGENT="${CLAUDE_AGENT_NAME:-}"
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"

# Só aplica a verifier ou reviewer
case "$AGENT" in
  verifier|reviewer) : ;;
  *) exit 0 ;;
esac

# Sandbox sem alvo identificável = block (defesa em profundidade)
if [ -z "$TARGET" ]; then
  echo "[verifier-sandbox BLOCK] $AGENT tentou acesso sem alvo identificado" >&2
  exit 1
fi

# Normaliza path
TARGET_NORM="${TARGET//\\//}"  # Windows backslash → forward slash

# ---------- VERIFIER (R3) ----------
if [ "$AGENT" = "verifier" ]; then
  case "$TARGET_NORM" in
    verification-input/*|*/verification-input/*|./verification-input/*)
      exit 0
      ;;
    *review-input/*|*review.json)
      echo "[verifier-sandbox BLOCK] R11: verifier nao pode ver output do reviewer ('$TARGET')" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] R3: verifier so pode acessar verification-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

# ---------- REVIEWER (R11) ----------
if [ "$AGENT" = "reviewer" ]; then
  case "$TARGET_NORM" in
    review-input/*|*/review-input/*|./review-input/*)
      exit 0
      ;;
    *verification-input/*|*verification.json)
      echo "[verifier-sandbox BLOCK] R11: reviewer nao pode ver output do verifier ('$TARGET')" >&2
      exit 1
      ;;
    *plan.md|*tasks.md)
      echo "[verifier-sandbox BLOCK] R11: reviewer nao pode ler plan/tasks (narrativa do implementer)" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] R11: reviewer so pode acessar review-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

exit 0
