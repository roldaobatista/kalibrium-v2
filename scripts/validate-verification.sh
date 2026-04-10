#!/usr/bin/env bash
# validate-verification.sh — valida um verification.json contra o schema de R4.
#
# Sem dependências externas (bash + grep puro). Cobre:
#  - Campos obrigatórios (slice_id, verdict, timestamp, ac_checks, violations, next_action)
#  - Enums (verdict, status, rule, next_action)
#  - Coerência:
#     verdict=approved  -> violations vazio, todos ac_checks pass, next_action=open_pr
#     verdict=rejected  -> next_action in {return_to_implementer, escalate_human}
#
# Se python3 + jsonschema estiverem disponíveis, usa-os (validação mais rigorosa).
# Fallback: checks em bash.
#
# Uso:
#   bash scripts/validate-verification.sh caminho/para/verification.json
#
# Exit 0 = válido, Exit 1 = inválido.

set -uo pipefail

FILE="${1:-}"
if [ -z "$FILE" ]; then
  echo "Uso: validate-verification.sh <path/to/verification.json>" >&2
  exit 1
fi
if [ ! -f "$FILE" ]; then
  echo "[validate BLOCK] arquivo não encontrado: $FILE" >&2
  exit 1
fi

say() { echo "[validate] $*" >&2; }
fail() { echo "[validate FAIL] $*" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SCHEMA="$REPO_ROOT/docs/schemas/verification.schema.json"

# ---------- Tenta jsonschema via python3 ----------
if command -v python3 >/dev/null 2>&1 && python3 -c "import jsonschema" 2>/dev/null; then
  say "usando python3 + jsonschema"
  if python3 - <<PY
import json, sys
try:
    import jsonschema
    with open("$SCHEMA") as s:
        schema = json.load(s)
    with open("$FILE") as f:
        doc = json.load(f)
    jsonschema.validate(doc, schema)
    print("[validate OK] jsonschema passou", file=sys.stderr)
    sys.exit(0)
except Exception as e:
    print(f"[validate FAIL] jsonschema: {e}", file=sys.stderr)
    sys.exit(1)
PY
  then
    exit 0
  else
    exit 1
  fi
fi

# ---------- Fallback: validação em bash puro ----------
say "python3+jsonschema indisponível — usando validação bash"

CONTENT="$(cat "$FILE")"

# 1. Parseabilidade básica — ao menos começa com { e termina com }
case "$CONTENT" in
  "{"*"}"*) : ;;
  *) fail "não parece um objeto JSON (primeiro/último char)" ;;
esac

# 2. Campos obrigatórios presentes (grep simples por "chave":)
REQUIRED_FIELDS=(slice_id verdict timestamp ac_checks violations next_action)
for f in "${REQUIRED_FIELDS[@]}"; do
  if ! echo "$CONTENT" | grep -q "\"$f\""; then
    fail "campo obrigatório ausente: $f"
  fi
done

# 3. verdict ∈ {approved, rejected}
VERDICT="$(echo "$CONTENT" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"verdict"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/')"
if [ "$VERDICT" != "approved" ] && [ "$VERDICT" != "rejected" ]; then
  fail "verdict inválido: '$VERDICT' (esperado: approved|rejected)"
fi
say "verdict=$VERDICT"

# 4. next_action ∈ {open_pr, return_to_implementer, escalate_human}
NEXT="$(echo "$CONTENT" | grep -o '"next_action"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"next_action"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/')"
case "$NEXT" in
  open_pr|return_to_implementer|escalate_human) : ;;
  *) fail "next_action inválido: '$NEXT'" ;;
esac
say "next_action=$NEXT"

# 5. slice_id formato slice-NNN
SLICE_ID="$(echo "$CONTENT" | grep -o '"slice_id"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"slice_id"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/')"
if ! echo "$SLICE_ID" | grep -qE '^slice-[0-9]{3}$'; then
  fail "slice_id inválido: '$SLICE_ID' (esperado: slice-NNN)"
fi

# 6. Coerência verdict/next_action/violations
VIOLATIONS_EMPTY=0
if echo "$CONTENT" | grep -qE '"violations"[[:space:]]*:[[:space:]]*\[[[:space:]]*\]'; then
  VIOLATIONS_EMPTY=1
fi

if [ "$VERDICT" = "approved" ]; then
  if [ "$NEXT" != "open_pr" ]; then
    fail "approved exige next_action=open_pr (recebido: $NEXT)"
  fi
  if [ "$VIOLATIONS_EMPTY" -ne 1 ]; then
    fail "approved exige violations vazio"
  fi
  # Nenhum ac com status fail
  if echo "$CONTENT" | grep -qE '"status"[[:space:]]*:[[:space:]]*"fail"'; then
    fail "approved tem ac_check com status=fail"
  fi
fi

if [ "$VERDICT" = "rejected" ]; then
  if [ "$NEXT" = "open_pr" ]; then
    fail "rejected não pode ter next_action=open_pr"
  fi
fi

# 7. Todas as regras em violations devem ser P1-P9 ou R1-R10
INVALID_RULES="$(echo "$CONTENT" | grep -o '"rule"[[:space:]]*:[[:space:]]*"[^"]*"' | sed -E 's/.*"rule"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/' | grep -vE '^(P[1-9]|R([1-9]|10))$' || true)"
if [ -n "$INVALID_RULES" ]; then
  fail "regras inválidas em violations: $INVALID_RULES"
fi

# 8. ac_checks status deve ser pass ou fail
INVALID_STATUS="$(echo "$CONTENT" | grep -o '"status"[[:space:]]*:[[:space:]]*"[^"]*"' | sed -E 's/.*"status"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/' | grep -vE '^(pass|fail)$' || true)"
if [ -n "$INVALID_STATUS" ]; then
  fail "ac_checks.status inválido(s): $INVALID_STATUS"
fi

# 9. timestamp formato ISO-8601 (heurística)
TS="$(echo "$CONTENT" | grep -o '"timestamp"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"timestamp"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/')"
if ! echo "$TS" | grep -qE '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}'; then
  fail "timestamp inválido: '$TS' (esperado ISO-8601)"
fi

say "validado OK (fallback bash): $FILE"
exit 0
