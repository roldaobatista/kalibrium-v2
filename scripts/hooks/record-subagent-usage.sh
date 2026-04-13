#!/usr/bin/env bash
# record-subagent-usage.sh — SubagentStop hook.
#
# Grava tokens consumidos por sub-agent na telemetria do slice ativo.
# Meta-audit #2 item P1-2 (2026-04-11) — fecha R8 enforcement gap:
# antes deste hook, /slice-report só tinha aproximação de tokens por sub-agent
# (capturava apenas no commit, não no término do sub-agent).
#
# Input esperado (via env vars do Claude Code harness):
#   CLAUDE_AGENT_NAME        — nome do sub-agent (verifier, reviewer, ...)
#   CLAUDE_SUBAGENT_TOKENS   — tokens totais consumidos na invocação (opcional)
#   CLAUDE_SUBAGENT_VERDICT  — verdict final (opcional)
#
# Slice ativo: lê specs/.current (ou "harness" se não houver).
# Se record-telemetry.sh não aceitar --extra, o evento ainda é gravado sem
# campos extras (fallback silencioso — não bloqueia o fluxo).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

SUBAGENT="${CLAUDE_AGENT_NAME:-unknown}"
TOKENS="${CLAUDE_SUBAGENT_TOKENS:-0}"
VERDICT="${CLAUDE_SUBAGENT_VERDICT:-n/a}"

SLICE="harness"
if [ -f specs/.current ]; then
  SLICE="slice-$(cat specs/.current | tr -d ' \n\r')"
fi

mkdir -p .claude/telemetry

bash "$REPO_ROOT/scripts/record-telemetry.sh" \
  --event="subagent-stop" \
  --slice="$SLICE" \
  --verdict="$VERDICT" \
  --next-action="monitor" \
  --reject-count="0" \
  --extra="subagent=${SUBAGENT};tokens=${TOKENS}" \
  >/dev/null 2>&1 || true

exit 0
