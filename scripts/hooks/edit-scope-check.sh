#!/usr/bin/env bash
# PreToolUse Edit|Write hook — valida que o arquivo está no escopo do slice atual.
#
# Lógica:
#  - Se não há slice ativo (especs/.current missing), permite (setup phase).
#  - Se há slice ativo, lê specs/<slice>/plan.md §novos arquivos e §arquivos modificados.
#  - Arquivo fora da lista = warning (não bloqueante ainda, pois plan.md pode ser incompleto).
#  - Bloqueia SEMPRE: arquivos em docs/reference/ (read-only por R7) e docs/constitution.md (via §5).

set -euo pipefail

TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$TARGET" ] && exit 0

TARGET_NORM="${TARGET//\\//}"

# --- Read-only absoluto ---
case "$TARGET_NORM" in
  docs/reference/*|*/docs/reference/*)
    echo "[edit-scope-check BLOCK] R7: docs/reference/** é read-only (referência não-instrucional)" >&2
    exit 1
    ;;
  docs/constitution.md|*/docs/constitution.md)
    echo "[edit-scope-check BLOCK] constitution.md só muda via §5 (ADR + aprovação humana)" >&2
    exit 1
    ;;
esac

# --- Slice ativo? ---
SLICE_MARKER="specs/.current"
if [ ! -f "$SLICE_MARKER" ]; then
  # Fase de setup ou entre slices — permite
  exit 0
fi

CURRENT_SLICE="$(cat "$SLICE_MARKER" 2>/dev/null || true)"
[ -z "$CURRENT_SLICE" ] && exit 0

PLAN="specs/$CURRENT_SLICE/plan.md"
if [ ! -f "$PLAN" ]; then
  # Plan ainda não existe — provavelmente architect está gerando
  exit 0
fi

# Grep barato: o caminho aparece no plan.md?
if grep -qF "$TARGET_NORM" "$PLAN" 2>/dev/null; then
  exit 0
fi

# Aviso, não bloqueio — plan pode estar incompleto
echo "[edit-scope-check WARN] $TARGET não está listado em $PLAN — considere atualizar o plan antes de editar" >&2
exit 0
