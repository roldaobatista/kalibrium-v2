#!/usr/bin/env bash
# audit-spec.sh — pre-condicoes e validacao do output do spec-auditor.
#
# Uso:
#   bash scripts/audit-spec.sh NNN --check
#   bash scripts/audit-spec.sh NNN --validate
#   bash scripts/audit-spec.sh NNN --approved

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:---check}"

if [ -z "$NNN" ]; then
  echo "Uso: audit-spec.sh NNN [--check|--validate|--approved]" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 digitos (ex.: 007)" >&2
  exit 1
fi

SPEC="specs/$NNN/spec.md"
AUDIT="specs/$NNN/spec-audit.json"
SCHEMA="docs/schemas/spec-audit.schema.json"

ERR=0
fail() { echo "  ✗ $*" >&2; ERR=1; }
ok()   { echo "  ✓ $*"; }

check_preconditions() {
  [ -f "$SPEC" ] && ok "$SPEC existe" || fail "$SPEC ausente"

  if [ -f "$SPEC" ] && bash "$REPO_ROOT/scripts/draft-spec.sh" "$NNN" --check > /dev/null 2>&1; then
    ok "spec.md passa validação mecânica (draft-spec --check)"
  else
    fail "spec.md não passa validação mecânica — rode /draft-spec $NNN"
  fi

  [ -f ".claude/agents/spec-auditor.md" ] && ok "spec-auditor definido" || fail ".claude/agents/spec-auditor.md ausente"
  [ -f ".claude/skills/audit-spec.md" ] && ok "skill audit-spec definida" || fail ".claude/skills/audit-spec.md ausente"
  [ -f "$SCHEMA" ] && ok "schema spec-audit disponível" || fail "$SCHEMA ausente"
  [ -f "docs/constitution.md" ] && ok "constitution.md acessível" || fail "docs/constitution.md ausente"
  [ -f "docs/TECHNICAL-DECISIONS.md" ] && ok "TECHNICAL-DECISIONS.md acessível" || fail "docs/TECHNICAL-DECISIONS.md ausente"
  [ -f "docs/product/roadmap.md" ] && ok "roadmap de slices acessível" || fail "docs/product/roadmap.md ausente"
}

if [ "$MODE" = "--check" ]; then
  echo "[audit-spec] verificando pre-condicoes para slice $NNN..."
  check_preconditions
  echo ""
  if [ "$ERR" -eq 0 ]; then
    echo "[audit-spec] OK — pronto para disparar spec-auditor"
    exit 0
  fi
  echo "[audit-spec FAIL] corrija os itens acima" >&2
  exit 1
fi

validate_audit_json() {
  echo "[audit-spec] validando $AUDIT..."
  check_preconditions
  [ -f "$AUDIT" ] && ok "$AUDIT existe" || fail "$AUDIT ausente — rode /audit-spec $NNN"

  if [ "$ERR" -ne 0 ]; then
    echo ""; echo "[audit-spec FAIL] pre-condicoes/output ausentes" >&2
    exit 1
  fi

  if command -v python3 >/dev/null 2>&1 && python3 -c "import jsonschema" 2>/dev/null; then
    if python3 - <<PY
import json, sys
import jsonschema
try:
    with open("$SCHEMA", encoding="utf-8") as f:
        schema = json.load(f)
    with open("$AUDIT", encoding="utf-8") as f:
        doc = json.load(f)
    jsonschema.validate(doc, schema)
    if doc["verdict"] == "approved" and doc["findings"]:
        raise ValueError("approved exige findings vazio")
    if doc["verdict"] == "rejected" and not doc["findings"]:
        raise ValueError("rejected exige pelo menos um finding")
    critical = sum(1 for item in doc["findings"] if item["severity"] == "critical")
    major = sum(1 for item in doc["findings"] if item["severity"] == "major")
    minor = sum(1 for item in doc["findings"] if item["severity"] == "minor")
    if doc["stats"]["findings_critical"] != critical:
        raise ValueError("stats.findings_critical inconsistente")
    if doc["stats"]["findings_major"] != major:
        raise ValueError("stats.findings_major inconsistente")
    if doc["stats"]["findings_minor"] != minor:
        raise ValueError("stats.findings_minor inconsistente")
    print("[audit-spec] jsonschema OK")
except Exception as e:
    print(f"[audit-spec FAIL] {e}", file=sys.stderr)
    sys.exit(1)
PY
    then
      return 0
    else
      return 1
    fi
  fi

  CONTENT="$(cat "$AUDIT")"
  for field in schema_version slice_id audit_date verdict summary checks findings stats; do
    echo "$CONTENT" | grep -q "\"$field\"" || fail "campo obrigatório ausente: $field"
  done
  VERDICT="$(echo "$CONTENT" | grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"verdict"[[:space:]]*:[[:space:]]*"([^"]*)".*/\1/')"
  case "$VERDICT" in
    approved|rejected) ok "verdict=$VERDICT" ;;
    *) fail "verdict inválido: $VERDICT" ;;
  esac
  if [ "$VERDICT" = "approved" ] && ! echo "$CONTENT" | grep -qE '"findings"[[:space:]]*:[[:space:]]*\[[[:space:]]*\]'; then
    fail "approved exige findings vazio"
  fi

  if [ "$ERR" -eq 0 ]; then
    echo "[audit-spec] OK — spec-audit.json válido (fallback bash)"
    return 0
  fi
  echo "[audit-spec FAIL] corrija os itens acima" >&2
  return 1
}

if [ "$MODE" = "--validate" ]; then
  if validate_audit_json; then
    echo "[audit-spec] OK — spec-audit.json válido"
    exit 0
  fi
  exit 1
fi

if [ "$MODE" = "--approved" ]; then
  if ! validate_audit_json; then
    exit 1
  fi

  if python3 - <<PY
import json, sys
with open("$AUDIT", encoding="utf-8") as f:
    doc = json.load(f)
if doc.get("verdict") != "approved":
    print("[audit-spec FAIL] verdict não aprovado", file=sys.stderr)
    sys.exit(1)
if doc.get("findings") != []:
    print("[audit-spec FAIL] approved exige findings vazio", file=sys.stderr)
    sys.exit(1)
PY
  then
    ok "spec-audit.json aprovado com findings vazio"
    echo "[audit-spec] OK — spec liberado para /draft-plan"
    exit 0
  fi
  exit 1
fi

echo "modo não suportado: $MODE (use --check, --validate ou --approved)" >&2
exit 1
