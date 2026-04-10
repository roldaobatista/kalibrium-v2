#!/usr/bin/env bash
# record-tokens.sh — grava um evento de uso de tokens por sub-agent.
#
# Invocável manualmente ao fim de uma invocação de sub-agent, ou via hook
# futuro se o harness expor tokens no Stop event.
#
# B-006 do guide-backlog.
#
# Uso:
#   bash scripts/record-tokens.sh <agent> <slice> <tokens>
#
# Exemplo:
#   bash scripts/record-tokens.sh architect 001 12340
#   bash scripts/record-tokens.sh verifier 042 8921
#
# Efeitos:
#   - Grava linha JSONL em .claude/telemetry/slice-<slice>.jsonl
#   - Alerta se tokens > max_tokens_per_invocation declarado no frontmatter do agent

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

AGENT="${1:-}"
SLICE="${2:-}"
TOKENS="${3:-}"

if [ -z "$AGENT" ] || [ -z "$SLICE" ] || [ -z "$TOKENS" ]; then
  echo "Uso: record-tokens.sh <agent> <slice> <tokens>" >&2
  echo "Exemplo: record-tokens.sh architect 001 12340" >&2
  exit 1
fi

# Valida agent conhecido
AGENT_FILE=".claude/agents/${AGENT}.md"
if [ ! -f "$AGENT_FILE" ]; then
  echo "[record-tokens] agente desconhecido: $AGENT (esperado $AGENT_FILE)" >&2
  exit 1
fi

# Valida tokens numérico
if ! echo "$TOKENS" | grep -qE '^[0-9]+$'; then
  echo "[record-tokens] tokens deve ser inteiro não-negativo (recebido: $TOKENS)" >&2
  exit 1
fi

# Valida slice (3 dígitos ou 'meta')
if ! echo "$SLICE" | grep -qE '^([0-9]{3}|meta)$'; then
  echo "[record-tokens] slice deve ser NNN ou 'meta' (recebido: $SLICE)" >&2
  exit 1
fi

mkdir -p .claude/telemetry
TEL=".claude/telemetry/slice-${SLICE}.jsonl"
[ "$SLICE" = "meta" ] && TEL=".claude/telemetry/meta.jsonl"
touch "$TEL"

TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

printf '{"event":"tokens","timestamp":"%s","agent":"%s","slice":"%s","tokens":%s}\n' \
  "$TS" "$AGENT" "$SLICE" "$TOKENS" >> "$TEL"

# Verifica budget declarado
BUDGET="$(grep '^max_tokens_per_invocation:' "$AGENT_FILE" 2>/dev/null | head -1 | awk '{print $2}')"
if [ -n "$BUDGET" ] && echo "$BUDGET" | grep -qE '^[0-9]+$'; then
  if [ "$TOKENS" -gt "$BUDGET" ]; then
    echo "[record-tokens WARN] $AGENT excedeu budget: $TOKENS > $BUDGET" >&2
    echo "  Registrado em $TEL — abordar na retrospectiva do slice-${SLICE}"
    exit 0
  fi
fi

echo "[record-tokens] $AGENT / slice-$SLICE / $TOKENS tokens → $TEL"
exit 0
