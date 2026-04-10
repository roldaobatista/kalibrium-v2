#!/usr/bin/env bash
# slice-report.sh — agrega telemetria em métricas quantitativas.
# Parseia .claude/telemetry/slice-NNN.jsonl e gera docs/retrospectives/slice-NNN-report.md
#
# B-002 + B-006 do guide-backlog.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
if [ -z "$NNN" ] || ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "Uso: slice-report.sh NNN" >&2
  exit 1
fi

TEL=".claude/telemetry/slice-${NNN}.jsonl"
OUT="docs/retrospectives/slice-${NNN}-report.md"
mkdir -p docs/retrospectives

if [ ! -f "$TEL" ]; then
  echo "[slice-report] sem telemetria em $TEL — nada a reportar" >&2
  exit 1
fi

# --- Agregações via bash puro ---
# Usa grep | wc -l em vez de grep -c || echo 0 (evita corrupção "0\n0")
count_events() {
  local pattern="$1"
  grep "$pattern" "$TEL" 2>/dev/null | wc -l | tr -d ' \n\r'
}

COMMITS=$(count_events '"event":"commit"')
COMMITS="${COMMITS:-0}"

VERIFY_APPROVED=$(count_events '"event":"verify".*"verdict":"approved"')
VERIFY_APPROVED="${VERIFY_APPROVED:-0}"

VERIFY_REJECTED=$(count_events '"event":"verify".*"verdict":"rejected"')
VERIFY_REJECTED="${VERIFY_REJECTED:-0}"

# Tokens por agent (gravados por record-tokens.sh)
tokens_for_agent() {
  local agent="$1"
  local total=0
  while IFS= read -r line; do
    local t
    t=$(echo "$line" | sed -E 's/.*"tokens"[[:space:]]*:[[:space:]]*([0-9]+).*/\1/')
    if echo "$t" | grep -qE '^[0-9]+$'; then
      total=$((total + t))
    fi
  done < <(grep "\"event\":\"tokens\"" "$TEL" 2>/dev/null | grep "\"agent\":\"$agent\"" || true)
  echo "$total"
}

ARCH_TOKENS=$(tokens_for_agent "architect")
AC2T_TOKENS=$(tokens_for_agent "ac-to-test")
IMPL_TOKENS=$(tokens_for_agent "implementer")
VERF_TOKENS=$(tokens_for_agent "verifier")
AUD_TOKENS=$(tokens_for_agent "guide-auditor")
TOTAL_TOKENS=$((ARCH_TOKENS + AC2T_TOKENS + IMPL_TOKENS + VERF_TOKENS + AUD_TOKENS))

# Budget máximo declarado por agent (lê do frontmatter dos sub-agents)
budget_for_agent() {
  local agent="$1"
  local f=".claude/agents/${agent}.md"
  if [ -f "$f" ]; then
    grep '^max_tokens_per_invocation:' "$f" 2>/dev/null | head -1 | awk '{print $2}' || echo "?"
  else
    echo "?"
  fi
}

ARCH_BUDGET=$(budget_for_agent "architect")
AC2T_BUDGET=$(budget_for_agent "ac-to-test")
IMPL_BUDGET=$(budget_for_agent "implementer")
VERF_BUDGET=$(budget_for_agent "verifier")
AUD_BUDGET=$(budget_for_agent "guide-auditor")

# Primeiro e último timestamp
FIRST_TS=$(head -1 "$TEL" 2>/dev/null | sed -E 's/.*"timestamp":"([^"]+)".*/\1/' || echo '?')
LAST_TS=$(tail -1 "$TEL" 2>/dev/null | sed -E 's/.*"timestamp":"([^"]+)".*/\1/' || echo '?')

# Alertas de budget
ALERTS=""
check_budget() {
  local agent="$1" tokens="$2" budget="$3"
  if [ "$budget" != "?" ] && [ "$tokens" -gt "$budget" ] 2>/dev/null; then
    ALERTS="${ALERTS}- ⚠ $agent excedeu budget: $tokens > $budget tokens\n"
  fi
}
check_budget "architect" "$ARCH_TOKENS" "$ARCH_BUDGET"
check_budget "ac-to-test" "$AC2T_TOKENS" "$AC2T_BUDGET"
check_budget "implementer" "$IMPL_TOKENS" "$IMPL_BUDGET"
check_budget "verifier" "$VERF_TOKENS" "$VERF_BUDGET"
check_budget "guide-auditor" "$AUD_TOKENS" "$AUD_BUDGET"

# --- Monta relatório ---
cat > "$OUT" <<EOF
# slice-${NNN}-report

**Gerado em:** $(date -u +%Y-%m-%dT%H:%M:%SZ)
**Primeiro evento:** ${FIRST_TS}
**Último evento:** ${LAST_TS}
**Fonte:** \`$TEL\`

## Métricas

| Métrica | Valor |
|---|---|
| Commits no slice | $COMMITS |
| Verificações (approved) | $VERIFY_APPROVED |
| Verificações (rejected) | $VERIFY_REJECTED |

## Tokens por sub-agent (R8)

| Agent | Tokens gastos | Budget declarado | Status |
|---|---|---|---|
| architect | $ARCH_TOKENS | $ARCH_BUDGET | $([ "$ARCH_BUDGET" = "?" ] && echo "?" || { [ "$ARCH_TOKENS" -le "$ARCH_BUDGET" ] 2>/dev/null && echo "ok" || echo "⚠ excedeu"; }) |
| ac-to-test | $AC2T_TOKENS | $AC2T_BUDGET | $([ "$AC2T_BUDGET" = "?" ] && echo "?" || { [ "$AC2T_TOKENS" -le "$AC2T_BUDGET" ] 2>/dev/null && echo "ok" || echo "⚠ excedeu"; }) |
| implementer | $IMPL_TOKENS | $IMPL_BUDGET | $([ "$IMPL_BUDGET" = "?" ] && echo "?" || { [ "$IMPL_TOKENS" -le "$IMPL_BUDGET" ] 2>/dev/null && echo "ok" || echo "⚠ excedeu"; }) |
| verifier | $VERF_TOKENS | $VERF_BUDGET | $([ "$VERF_BUDGET" = "?" ] && echo "?" || { [ "$VERF_TOKENS" -le "$VERF_BUDGET" ] 2>/dev/null && echo "ok" || echo "⚠ excedeu"; }) |
| guide-auditor | $AUD_TOKENS | $AUD_BUDGET | $([ "$AUD_BUDGET" = "?" ] && echo "?" || { [ "$AUD_TOKENS" -le "$AUD_BUDGET" ] 2>/dev/null && echo "ok" || echo "⚠ excedeu"; }) |
| **TOTAL** | **$TOTAL_TOKENS** | — | — |

EOF

if [ -n "$ALERTS" ]; then
  echo "## Alertas de budget" >> "$OUT"
  echo "" >> "$OUT"
  echo -e "$ALERTS" >> "$OUT"
fi

cat >> "$OUT" <<EOF
## Commits

EOF

grep '"event":"commit"' "$TEL" 2>/dev/null | while IFS= read -r line; do
  HASH=$(echo "$line" | sed -E 's/.*"hash":"([^"]+)".*/\1/')
  AUTHOR=$(echo "$line" | sed -E 's/.*"author":"([^"]+)".*/\1/')
  SUBJECT=$(echo "$line" | sed -E 's/.*"subject":"([^"]+)".*/\1/')
  echo "- \`$HASH\` ($AUTHOR) — $SUBJECT" >> "$OUT"
done

cat >> "$OUT" <<EOF

## Eventos de verificação

EOF

grep '"event":"verify"' "$TEL" 2>/dev/null | while IFS= read -r line; do
  TS=$(echo "$line" | sed -E 's/.*"timestamp":"([^"]+)".*/\1/')
  VD=$(echo "$line" | sed -E 's/.*"verdict":"([^"]+)".*/\1/')
  NA=$(echo "$line" | sed -E 's/.*"next_action":"([^"]+)".*/\1/')
  RC=$(echo "$line" | sed -E 's/.*"reject_count":([0-9]+).*/\1/')
  echo "- \`$TS\` verdict=$VD next=$NA reject_count=$RC" >> "$OUT"
done

cat >> "$OUT" <<EOF

## Raw (JSONL completo)

\`\`\`jsonl
$(cat "$TEL")
\`\`\`
EOF

echo "[slice-report] gravado em $OUT"
echo "  Commits: $COMMITS  |  Approved: $VERIFY_APPROVED  |  Rejected: $VERIFY_REJECTED"
echo "  Tokens totais: $TOTAL_TOKENS"
if [ -n "$ALERTS" ]; then
  echo "  ⚠ há alertas de budget — ver relatório"
fi
exit 0
