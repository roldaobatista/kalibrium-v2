#!/usr/bin/env bash
# validate-review.sh — valida review.json contra o schema canonico gate-output-v1.
#
# Protocolo v1.2.2: toda saida de gate segue docs/protocol/schemas/gate-output.schema.json.
# Este validador substitui a logica v1 que usava docs/schemas/review.schema.json (deprecated).
#
# Politica zero-tolerance: approved exige blocking_findings_count == 0 (S1-S3).
# S4/S5 podem existir em approved (nao bloqueiam).
#
# Uso:
#   bash scripts/validate-review.sh <path/to/review.json>
#
# Exit 0 = valido, Exit 1 = invalido.

set -uo pipefail

FILE="${1:-}"
if [ -z "$FILE" ]; then
  echo "Uso: validate-review.sh <path/to/review.json>" >&2
  exit 1
fi
if [ ! -f "$FILE" ]; then
  echo "[validate-review BLOCK] arquivo nao encontrado: $FILE" >&2
  exit 1
fi

say()  { echo "[validate-review] $*" >&2; }
fail() { echo "[validate-review FAIL] $*" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SCHEMA="$REPO_ROOT/docs/protocol/schemas/gate-output.schema.json"
EXPECTED_GATE="review"

if [ ! -f "$SCHEMA" ]; then
  fail "schema canonico ausente: $SCHEMA"
fi

# ---------- python3+jsonschema (preferencial) ----------
if command -v python3 >/dev/null 2>&1 && python3 -c "import jsonschema" 2>/dev/null; then
  say "usando python3 + jsonschema (gate-output-v1)"
  if SCHEMA_PATH="$SCHEMA" AUDIT_PATH="$FILE" EXPECTED_GATE="$EXPECTED_GATE" python3 - <<'PY'
import json, os, sys
import jsonschema
try:
    with open(os.environ["SCHEMA_PATH"], encoding="utf-8") as s:
        schema = json.load(s)
    with open(os.environ["AUDIT_PATH"], encoding="utf-8") as f:
        doc = json.load(f)
    jsonschema.validate(doc, schema)
    if doc["gate"] != os.environ["EXPECTED_GATE"]:
        raise ValueError(f"gate deve ser '{os.environ['EXPECTED_GATE']}', achei '{doc['gate']}'")
    if doc["verdict"] == "approved" and doc["blocking_findings_count"] != 0:
        raise ValueError("approved exige blocking_findings_count == 0")
    if doc["verdict"] == "rejected" and not doc["findings"]:
        raise ValueError("rejected exige pelo menos um finding")
    sev = doc["findings_by_severity"]
    expected_blocking = sev["S1"] + sev["S2"] + sev["S3"]
    if doc["blocking_findings_count"] != expected_blocking:
        raise ValueError(
            f"blocking_findings_count ({doc['blocking_findings_count']}) "
            f"inconsistente com S1+S2+S3 ({expected_blocking})"
        )
    print("[validate-review OK] gate-output-v1 passou", file=sys.stderr)
    sys.exit(0)
except Exception as e:
    print(f"[validate-review FAIL] {e}", file=sys.stderr)
    sys.exit(1)
PY
  then
    exit 0
  else
    exit 1
  fi
fi

# ---------- Fallback: validacao em bash puro ----------
say "python3+jsonschema indisponivel — validando em bash (gate-output-v1)"

CONTENT="$(cat "$FILE")"

case "$CONTENT" in
  "{"*"}"*) : ;;
  *) fail "nao parece objeto JSON" ;;
esac

REQUIRED=('$schema' gate slice lane agent mode verdict timestamp commit_hash \
          isolation_context blocking_findings_count non_blocking_findings_count \
          findings_by_severity findings)
for f in "${REQUIRED[@]}"; do
  if ! echo "$CONTENT" | grep -q "\"$f\""; then
    fail "campo gate-output-v1 ausente: $f"
  fi
done

SCHEMA_VAL="$(echo "$CONTENT" | grep -oE '"\$schema"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
if [ "$SCHEMA_VAL" != "gate-output-v1" ]; then
  fail "\$schema deve ser 'gate-output-v1' (achei '$SCHEMA_VAL')"
fi

GATE_VAL="$(echo "$CONTENT" | grep -oE '"gate"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
if [ "$GATE_VAL" != "$EXPECTED_GATE" ]; then
  fail "gate deve ser '$EXPECTED_GATE' (achei '$GATE_VAL')"
fi

VERDICT="$(echo "$CONTENT" | grep -oE '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
case "$VERDICT" in
  approved|rejected) say "verdict=$VERDICT" ;;
  *) fail "verdict invalido: '$VERDICT'" ;;
esac

BLOCKING="$(echo "$CONTENT" | grep -oE '"blocking_findings_count"[[:space:]]*:[[:space:]]*[0-9]+' | head -1 | grep -oE '[0-9]+$')"
if [ "$VERDICT" = "approved" ] && [ "$BLOCKING" != "0" ]; then
  fail "approved exige blocking_findings_count == 0 (achei $BLOCKING)"
fi

TS="$(echo "$CONTENT" | grep -oE '"timestamp"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
if ! echo "$TS" | grep -qE '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}'; then
  fail "timestamp invalido: '$TS' (esperado ISO-8601)"
fi

say "validado OK (fallback bash, gate-output-v1): $FILE"
exit 0
