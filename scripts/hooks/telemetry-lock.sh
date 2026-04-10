#!/usr/bin/env bash
# telemetry-lock.sh — selo append-only de .claude/telemetry/.
#
# Origem: meta-audit 2026-04-10, item 1.3.
# Vetor coberto: §H ameaça #4 (telemetria mutável reseta R6).
#
# PreToolUse Edit|Write: bloqueia qualquer mutação tool-based em
# .claude/telemetry/. O único caminho legítimo de escrita é
# scripts/record-telemetry.sh, que escreve via shell append (não tool).
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_TOOL_ARG_FILE — caminho do arquivo que o tool tentou acessar

set -uo pipefail

normalize_path() {
  local p="${1//\\//}"
  p="${p#./}"
  echo "$p"
}

TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$TARGET" ] && exit 0

TARGET_NORM="$(normalize_path "$TARGET")"

case "$TARGET_NORM" in
  .claude/telemetry/*|*/.claude/telemetry/*)
    echo "[telemetry-lock BLOCK] .claude/telemetry/ é append-only via scripts/record-telemetry.sh" >&2
    echo "  Caminho passado pelo tool: $TARGET" >&2
    echo "  Origem: meta-audit 2026-04-10 item 1.3" >&2
    echo "" >&2
    echo "  Para registrar telemetria:" >&2
    echo "    bash scripts/record-telemetry.sh --event=... --slice=... --verdict=... --next-action=... --reject-count=N" >&2
    echo "" >&2
    echo "  Tampering com a cadeia (apagar/zerar) é detectado por:" >&2
    echo "    bash scripts/record-telemetry.sh --verify-chain <arquivo>" >&2
    exit 1
    ;;
esac

exit 0
