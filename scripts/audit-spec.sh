#!/usr/bin/env bash
# audit-spec.sh — pre-condicoes e validacao do output do qa-expert (modo audit-spec).
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
SCHEMA="docs/protocol/schemas/gate-output.schema.json"
LEGACY_SCHEMA="docs/schemas/spec-audit.schema.json"

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

  # Harness v3 (protocolo v1.2.2): audit-spec é modo do qa-expert; qa-expert (modo audit-spec) legado foi consolidado.
  [ -f ".claude/agents/qa-expert.md" ] && ok "qa-expert (modo audit-spec) definido" || fail ".claude/agents/qa-expert.md ausente"
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
    echo "[audit-spec] OK — pronto para disparar qa-expert (modo audit-spec)"
    exit 0
  fi
  echo "[audit-spec FAIL] corrija os itens acima" >&2
  exit 1
fi

validate_audit_json() {
  echo "[audit-spec] validando $AUDIT contra gate-output-v1..."
  check_preconditions
  [ -f "$AUDIT" ] && ok "$AUDIT existe" || fail "$AUDIT ausente — rode /audit-spec $NNN"

  if [ "$ERR" -ne 0 ]; then
    echo ""; echo "[audit-spec FAIL] pre-condicoes/output ausentes" >&2
    exit 1
  fi

  # Protocolo v1.2.2: valida contra schema canonico gate-output-v1 + politica zero-tolerance
  # (approved exige blocking_findings_count == 0; S4/S5 podem existir).
  if command -v python3 >/dev/null 2>&1 && python3 -c "import jsonschema" 2>/dev/null; then
    SCHEMA_PATH="$SCHEMA" AUDIT_PATH="$AUDIT" EXPECTED_SLICE="$NNN" python3 - <<'PY'
import json, os, sys
import jsonschema
schema_path = os.environ["SCHEMA_PATH"]
audit_path = os.environ["AUDIT_PATH"]
expected_slice = os.environ["EXPECTED_SLICE"]
try:
    with open(schema_path, encoding="utf-8") as f:
        schema = json.load(f)
    with open(audit_path, encoding="utf-8") as f:
        doc = json.load(f)
    jsonschema.validate(doc, schema)
    if doc["gate"] != "audit-spec":
        raise ValueError(f"gate deve ser 'audit-spec', achei '{doc['gate']}'")
    if doc["slice"] != expected_slice:
        raise ValueError(f"slice deve ser '{expected_slice}', achei '{doc['slice']}'")
    if doc["verdict"] == "rejected" and not doc["findings"]:
        raise ValueError("rejected exige pelo menos um finding")
    if doc["verdict"] == "approved" and doc["blocking_findings_count"] != 0:
        raise ValueError("approved exige blocking_findings_count == 0")
    # Cross-check: soma de severities bloqueantes (S1+S2+S3) == blocking_findings_count
    sev = doc["findings_by_severity"]
    expected_blocking = sev["S1"] + sev["S2"] + sev["S3"]
    if doc["blocking_findings_count"] != expected_blocking:
        raise ValueError(
            f"blocking_findings_count ({doc['blocking_findings_count']}) "
            f"inconsistente com S1+S2+S3 ({expected_blocking})"
        )
    print("[audit-spec] gate-output-v1 OK")
except Exception as e:
    print(f"[audit-spec FAIL] {e}", file=sys.stderr)
    sys.exit(1)
PY
    PY_EXIT=$?
    if [ "$PY_EXIT" -eq 0 ]; then
      return 0
    else
      return 1
    fi
  fi

  # Fallback bash — sem python3/jsonschema disponivel. Checa campos top-level obrigatorios
  # e politica blocking_findings_count == 0 para approved.
  CONTENT="$(cat "$AUDIT")"
  for field in '$schema' gate slice lane agent mode verdict timestamp commit_hash \
               isolation_context blocking_findings_count non_blocking_findings_count \
               findings_by_severity findings; do
    echo "$CONTENT" | grep -q "\"$field\"" || fail "campo gate-output-v1 obrigatório ausente: $field"
  done
  SCHEMA_VAL="$(echo "$CONTENT" | grep -oE '"\$schema"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
  [ "$SCHEMA_VAL" = "gate-output-v1" ] || fail "\$schema deve ser 'gate-output-v1' (achei '$SCHEMA_VAL')"

  VERDICT="$(echo "$CONTENT" | grep -oE '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
  case "$VERDICT" in
    approved|rejected) ok "verdict=$VERDICT" ;;
    *) fail "verdict inválido: $VERDICT" ;;
  esac

  BLOCKING="$(echo "$CONTENT" | grep -oE '"blocking_findings_count"[[:space:]]*:[[:space:]]*[0-9]+' | head -1 | grep -oE '[0-9]+$')"
  if [ "$VERDICT" = "approved" ] && [ "$BLOCKING" != "0" ]; then
    fail "approved exige blocking_findings_count == 0 (achei $BLOCKING)"
  fi

  if [ "$ERR" -eq 0 ]; then
    echo "[audit-spec] gate-output-v1 OK (fallback bash)"
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

  # Protocolo v1.2.2: approved exige blocking_findings_count == 0 (S1-S3).
  # S4/S5 podem existir em approved (nao bloqueiam).
  if AUDIT_PATH="$AUDIT" python3 - <<'PY'
import json, os, sys
audit_path = os.environ["AUDIT_PATH"]
with open(audit_path, encoding="utf-8") as f:
    doc = json.load(f)
if doc.get("verdict") != "approved":
    print("[audit-spec FAIL] verdict nao aprovado", file=sys.stderr)
    sys.exit(1)
if doc.get("blocking_findings_count", -1) != 0:
    print(
        f"[audit-spec FAIL] approved exige blocking_findings_count == 0 "
        f"(achei {doc.get('blocking_findings_count')})",
        file=sys.stderr,
    )
    sys.exit(1)
PY
  then
    ok "spec-audit.json aprovado com blocking_findings_count == 0"
    echo "[audit-spec] OK — spec liberado para /draft-plan"
    exit 0
  fi
  exit 1
fi

echo "modo não suportado: $MODE (use --check, --validate ou --approved)" >&2
exit 1
