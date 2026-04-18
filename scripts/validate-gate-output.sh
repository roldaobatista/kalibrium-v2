#!/usr/bin/env bash
# Slice 018 — AC-005, AC-006-A (B-038)
#
# Validator mecanico de JSON de gate output.
#
# Contrato (AC-005):
#   - "$schema" DEVE ser literal "gate-output-v1"
#   - "slice" DEVE casar ^[0-9]{3}$
#   - "gate" DEVE pertencer ao enum canonico de
#     docs/protocol/schemas/gate-output.schema.json (lido em runtime)
#
# Uso:
#   scripts/validate-gate-output.sh <gate-output.json>
#
# Exit 0 = valido
# Exit 1 = invalido (stderr reporta campo violador)
# Exit 2 = erro de uso / dependencia ausente

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SCHEMA="$REPO_ROOT/docs/protocol/schemas/gate-output.schema.json"

FILE="${1:-}"

if [ -z "$FILE" ]; then
  echo "usage: $0 <gate-output.json>" >&2
  exit 2
fi

if [ ! -f "$FILE" ]; then
  echo "error: arquivo '$FILE' nao existe" >&2
  exit 2
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "error: dependencia jq nao instalada" >&2
  exit 2
fi

if [ ! -f "$SCHEMA" ]; then
  echo "error: schema canonico nao encontrado em $SCHEMA" >&2
  exit 2
fi

# -------- 1. JSON parseavel --------
if ! jq empty "$FILE" >/dev/null 2>&1; then
  echo "[validate-gate-output] REJECTED $FILE: JSON invalido (parse error)" >&2
  exit 1
fi

VIOLATIONS=0

# -------- 2. $schema literal "gate-output-v1" --------
schema_value="$(jq -r '."$schema" // empty' "$FILE")"
if [ "$schema_value" != "gate-output-v1" ]; then
  line="$(grep -nE '"\$schema"' "$FILE" | head -1 | cut -d: -f1 || echo '?')"
  echo "[validate-gate-output] REJECTED $FILE:${line} — \$schema='${schema_value}' esperado literal 'gate-output-v1'" >&2
  VIOLATIONS=$((VIOLATIONS + 1))
fi

# -------- 3. slice presente e ^[0-9]{3}$ --------
has_slice="$(jq 'has("slice")' "$FILE")"
if [ "$has_slice" != "true" ]; then
  echo "[validate-gate-output] REJECTED $FILE — campo 'slice' ausente" >&2
  VIOLATIONS=$((VIOLATIONS + 1))
else
  slice_value="$(jq -r '.slice' "$FILE")"
  if ! echo "$slice_value" | grep -qE '^[0-9]{3}$'; then
    line="$(grep -nE '"slice"' "$FILE" | head -1 | cut -d: -f1 || echo '?')"
    echo "[validate-gate-output] REJECTED $FILE:${line} — slice='${slice_value}' fora do padrao ^[0-9]{3}$" >&2
    VIOLATIONS=$((VIOLATIONS + 1))
  fi
fi

# -------- 4. gate pertence ao enum canonico lido do schema --------
# Extrai enum de gate do schema (caminho conhecido: properties.gate.enum)
gate_enum_json="$(jq -r '.properties.gate.enum | @json' "$SCHEMA")"
if [ -z "$gate_enum_json" ] || [ "$gate_enum_json" = "null" ]; then
  echo "error: schema nao declara properties.gate.enum em $SCHEMA" >&2
  exit 2
fi

gate_value="$(jq -r '.gate // empty' "$FILE")"
if [ -z "$gate_value" ]; then
  echo "[validate-gate-output] REJECTED $FILE — campo 'gate' ausente" >&2
  VIOLATIONS=$((VIOLATIONS + 1))
else
  # Verifica se gate_value esta no enum
  is_valid="$(jq --arg g "$gate_value" --argjson enum "$gate_enum_json" -n '$enum | index($g) != null')"
  if [ "$is_valid" != "true" ]; then
    line="$(grep -nE '"gate"' "$FILE" | head -1 | cut -d: -f1 || echo '?')"
    echo "[validate-gate-output] REJECTED $FILE:${line} — gate='${gate_value}' fora do enum canonico ${gate_enum_json}" >&2
    VIOLATIONS=$((VIOLATIONS + 1))
  fi
fi

if [ $VIOLATIONS -gt 0 ]; then
  echo "[validate-gate-output] total de violacoes: $VIOLATIONS" >&2
  exit 1
fi

echo "[validate-gate-output] OK $FILE"
exit 0
