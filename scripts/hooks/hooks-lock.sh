#!/usr/bin/env bash
# hooks-lock.sh — selo do diretório scripts/hooks/.
#
# Origem: meta-audit 2026-04-10, item 1.2.
# Vetor coberto: §B.2 (sub-agent isolation), §H ameaça #3 (drift de hooks),
# Gemini ameaça #2 do action plan: "implementer edita post-edit-gate.sh
# para retornar sempre exit 0".
#
# Dois modos:
#   pre (default)  → PreToolUse Edit|Write: bloqueia tool-based mutation
#                    em qualquer arquivo dentro de scripts/hooks/.
#   --check        → SessionStart + invocado pelo verifier no spawn (item 1.8):
#                    valida MANIFEST.sha256 contra hashes correntes.
#                    Drift = exit 1.
#
# Updates ao manifesto exigem scripts/relock-harness.sh (TTY interativa +
# variável manual KALIB_RELOCK_AUTHORIZED + incidente automático).
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_TOOL_ARG_FILE — caminho do arquivo que o tool tentou acessar

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

MODE="${1:-pre}"
HOOKS_DIR="scripts/hooks"
MANIFEST="$HOOKS_DIR/MANIFEST.sha256"

normalize_path() {
  local p="${1//\\//}"
  p="${p#./}"
  echo "$p"
}

# ----------------------------------------------------------------------
# MODE: --check (drift detection)
# ----------------------------------------------------------------------
if [ "$MODE" = "--check" ]; then
  if [ ! -f "$MANIFEST" ]; then
    echo "[hooks-lock CHECK FAIL] $MANIFEST ausente" >&2
    echo "  Rode 'KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh' externamente" >&2
    exit 1
  fi

  # sha256sum --check espera o working dir onde os arquivos foram hashados.
  # MANIFEST guarda paths como "block-project-init.sh" (relativos a scripts/hooks).
  if ! ( cd "$HOOKS_DIR" && sha256sum --check --strict --quiet MANIFEST.sha256 ) >/tmp/hooks-lock-check.out 2>&1; then
    echo "[hooks-lock CHECK FAIL] DRIFT em $HOOKS_DIR/" >&2
    sed 's/^/  /' /tmp/hooks-lock-check.out >&2
    echo "  Origem: meta-audit 2026-04-10 item 1.2" >&2
    exit 1
  fi

  # Valida que TODOS os hooks .sh do diretório estão no manifesto.
  # (Detecta hook novo NÃO catalogado — caminho de bypass.)
  # Aceita ambos os formatos sha256sum: text mode ("  name") e binary mode (" *name").
  while IFS= read -r hookfile; do
    base="$(basename "$hookfile")"
    if ! grep -qE "[[:space:]\*]${base}\$" "$MANIFEST"; then
      echo "[hooks-lock CHECK FAIL] hook não catalogado no MANIFEST: $hookfile" >&2
      echo "  Adicione via 'KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh'" >&2
      exit 1
    fi
  done < <(find "$HOOKS_DIR" -maxdepth 1 -type f -name '*.sh' 2>/dev/null)

  exit 0
fi

# ----------------------------------------------------------------------
# MODE: pre (default — PreToolUse Edit|Write)
# ----------------------------------------------------------------------
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$TARGET" ] && exit 0
case "$TARGET" in
  --check) exit 0 ;;
esac

TARGET_NORM="$(normalize_path "$TARGET")"

# Bloqueia qualquer Edit/Write em scripts/hooks/*
case "$TARGET_NORM" in
  scripts/hooks/*|*/scripts/hooks/*)
    echo "[hooks-lock BLOCK] scripts/hooks/ é selado contra edição via tool: $TARGET" >&2
    echo "  Origem: meta-audit 2026-04-10 item 1.2" >&2
    echo "" >&2
    echo "  Para alterar um hook legitimamente:" >&2
    echo "    1. Saia do agente Claude Code." >&2
    echo "    2. Edite o hook manualmente em terminal externo." >&2
    echo "    3. Rode: KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh" >&2
    echo "    4. Volte à sessão." >&2
    exit 1
    ;;
esac

exit 0
