#!/usr/bin/env bash
# settings-lock.sh — selo de arquivos críticos do harness.
#
# Origem: meta-audit 2026-04-10, item 1.1 (+ adição PM no item 1.2).
# Vetor coberto: §H ameaça #3 (drift silencioso de .claude/settings.json) +
# §D vetor 5 do audit-claude-opus-4-6-2026-04-10.md.
#
# Dois modos:
#   pre (default)  → PreToolUse Edit|Write: bloqueia tool-based mutation
#                    em arquivos sealed. Único caminho para alterar é
#                    bash scripts/relock-harness.sh fora do agente.
#   --check        → SessionStart: valida que cada sealed file tem hash
#                    correspondente em .claude/settings.json.sha256 ou
#                    no MANIFEST do hooks-lock. Drift = exit 1.
#
# Sealed files (NUNCA editáveis pelo agente):
#   .claude/settings.json
#   .claude/settings.json.sha256
#   scripts/hooks/MANIFEST.sha256        ← adição PM no item 1.2
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_TOOL_ARG_FILE — caminho do arquivo que o tool tentou acessar

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

MODE="${1:-pre}"

# Lista canônica de arquivos selados.
# Mantida aqui para que o hook seja auto-contido (sem ler config externa que
# também precisaria estar selada → infinite regress).
SEALED_FILES=(
  ".claude/settings.json"
  ".claude/settings.json.sha256"
  "scripts/hooks/MANIFEST.sha256"
  ".claude/allowed-git-identities.txt"
  ".claude/git-identity-baseline"
)

normalize_path() {
  # Converte backslash → forward slash e remove ./ inicial.
  local p="${1//\\//}"
  p="${p#./}"
  echo "$p"
}

# ----------------------------------------------------------------------
# MODE: --check (drift detection — chamado por session-start.sh)
# ----------------------------------------------------------------------
if [ "$MODE" = "--check" ]; then
  HASH_FILE=".claude/settings.json.sha256"

  if [ ! -f ".claude/settings.json" ]; then
    echo "[settings-lock CHECK FAIL] .claude/settings.json ausente" >&2
    exit 1
  fi

  if [ ! -f "$HASH_FILE" ]; then
    echo "[settings-lock CHECK FAIL] $HASH_FILE ausente — rode 'bash scripts/relock-harness.sh' externamente" >&2
    exit 1
  fi

  EXPECTED="$(awk '{print $1}' "$HASH_FILE" | head -1)"
  ACTUAL="$(sha256sum .claude/settings.json | awk '{print $1}')"

  if [ "$EXPECTED" != "$ACTUAL" ]; then
    echo "[settings-lock CHECK FAIL] DRIFT detectado em .claude/settings.json" >&2
    echo "  esperado: $EXPECTED" >&2
    echo "  atual:    $ACTUAL" >&2
    echo "  Ação:     edição não-autorizada — investigar antes de relock" >&2
    exit 1
  fi

  exit 0
fi

# ----------------------------------------------------------------------
# MODE: pre (default — PreToolUse Edit|Write)
# ----------------------------------------------------------------------
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"

# Sem alvo identificado: deixa passar (outros hooks tratam casos vazios).
[ -z "$TARGET" ] && exit 0
case "$TARGET" in
  --check) exit 0 ;;  # já tratado acima
esac

TARGET_NORM="$(normalize_path "$TARGET")"

for sealed in "${SEALED_FILES[@]}"; do
  # Match exato OR sufixo (cobre paths absolutos).
  if [ "$TARGET_NORM" = "$sealed" ] || [[ "$TARGET_NORM" == */"$sealed" ]]; then
    echo "[settings-lock BLOCK] arquivo selado contra edição via tool: $sealed" >&2
    echo "  Caminho passado pelo tool: $TARGET" >&2
    echo "  Origem: meta-audit 2026-04-10 item 1.1 (+ adição PM 1.2)" >&2
    echo "" >&2
    echo "  Para alterar legitimamente:" >&2
    echo "    1. Saia do agente Claude Code." >&2
    echo "    2. Edite o arquivo manualmente em terminal externo." >&2
    echo "    3. Rode: KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh" >&2
    echo "    4. Volte à sessão (SessionStart valida o novo hash)." >&2
    exit 1
  fi
done

exit 0
