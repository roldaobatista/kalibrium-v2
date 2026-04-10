#!/usr/bin/env bash
# validate-review.sh — valida review.json contra review.schema.json (R11).
# Equivalente a validate-verification.sh mas para o reviewer output.
#
# Sem dependências externas (bash puro + grep). Usa python3+jsonschema se
# disponível para validação mais rigorosa.

set -uo pipefail

FILE="${1:-}"
if [ -z "$FILE" ]; then
  echo "Uso: validate-review.sh <path/to/review.json>" >&2
  exit 1
fi
if [ ! -f "$FILE" ]; then
  echo "[validate-review BLOCK] arquivo não encontrado: $FILE" >&2
  exit 1
fi

say()  { echo "[validate-review] $*" >&2; }
fail() { echo "[validate-review FAIL] $*" >&2; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SCHEMA="$REPO_ROOT/docs/schemas/review.schema.json"

# ---------- python3+jsonschema (preferencial) ----------
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
    print("[validate-review OK] jsonschema passou", file=sys.stderr)
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

# ---------- Fallback: validação em bash puro ----------
say "python3+jsonschema indisponível — validando em bash"

CONTENT="$(cat "$FILE")"

case "$CONTENT" in
  "{"*"}"*) : ;;
  *) fail "não parece objeto JSON" ;;
esac

REQUIRED=(slice_id verdict timestamp quality_checks findings next_action)
for f in "${REQUIRED[@]}"; do
  if ! echo "$CONTENT" | grep -q "\"$f\""; then
    fail "campo obrigatório ausente: $f"
  fi
done

VERDICT="$(echo "$CONTENT" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
case "$VERDICT" in
  approved|rejected) : ;;
  *) fail "verdict inválido: '$VERDICT'" ;;
esac

NEXT="$(echo "$CONTENT" | grep -o '"next_action"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
case "$NEXT" in
  approve_pr|return_to_implementer|escalate_human) : ;;
  *) fail "next_action inválido: '$NEXT'" ;;
esac

SLICE_ID="$(echo "$CONTENT" | grep -o '"slice_id"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
if ! echo "$SLICE_ID" | grep -qE '^slice-[0-9]{3}$'; then
  fail "slice_id inválido: '$SLICE_ID'"
fi

# Coerência
if [ "$VERDICT" = "approved" ] && [ "$NEXT" != "approve_pr" ]; then
  fail "approved exige next_action=approve_pr"
fi
if [ "$VERDICT" = "rejected" ] && [ "$NEXT" = "approve_pr" ]; then
  fail "rejected não pode ter next_action=approve_pr"
fi

# approved não pode ter severity=blocker
if [ "$VERDICT" = "approved" ]; then
  if echo "$CONTENT" | grep -qE '"severity"[[:space:]]*:[[:space:]]*"blocker"'; then
    fail "approved tem finding severity=blocker"
  fi
fi

# Categorias válidas em quality_checks
BAD_CATEGORIES="$(echo "$CONTENT" | grep -o '"category"[[:space:]]*:[[:space:]]*"[^"]*"' | sed -E 's/.*"([^"]*)".*/\1/' | grep -vE '^(duplication|complexity|naming|security|simplicity|glossary|adr_compliance|dead_code)$' || true)"
if [ -n "$BAD_CATEGORIES" ]; then
  fail "categorias inválidas: $BAD_CATEGORIES"
fi

# Severidades válidas
BAD_SEVERITIES="$(echo "$CONTENT" | grep -o '"severity"[[:space:]]*:[[:space:]]*"[^"]*"' | sed -E 's/.*"([^"]*)".*/\1/' | grep -vE '^(blocker|major|minor)$' || true)"
if [ -n "$BAD_SEVERITIES" ]; then
  fail "severidades inválidas: $BAD_SEVERITIES"
fi

say "validado OK (fallback bash): $FILE"
exit 0
