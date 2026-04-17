#!/usr/bin/env bash
# plan-review.sh — valida o gate obrigatório de architecture-expert (modo plan-review) antes dos testes.
#
# Uso:
#   bash scripts/plan-review.sh NNN --check     (pré-condições para architecture-expert (modo plan-review))
#   bash scripts/plan-review.sh NNN --validate  (estrutura JSON mínima)
#   bash scripts/plan-review.sh NNN --approved  (aprovado com findings [])

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:---check}"

if [ -z "$NNN" ]; then
  echo "Uso: plan-review.sh NNN [--check|--validate|--approved]" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 007)" >&2
  exit 1
fi

SPEC="specs/$NNN/spec.md"
PLAN="specs/$NNN/plan.md"
REVIEW="specs/$NNN/plan-review.json"
PYTHON_BIN=""

if command -v python3 >/dev/null 2>&1; then
  PYTHON_BIN="python3"
elif command -v python >/dev/null 2>&1; then
  PYTHON_BIN="python"
elif command -v python.exe >/dev/null 2>&1; then
  PYTHON_BIN="python.exe"
else
  echo "python3/python não encontrado — necessário para validar plan-review.json" >&2
  exit 1
fi

ERR=0
fail() { echo "  ✗ $*" >&2; ERR=1; }
ok()   { echo "  ✓ $*"; }

validate_review_json() {
  local require_approved="$1"

  # Protocolo v1.2.2: valida contra gate-output-v1.
  # Politica zero-tolerance: approved exige blocking_findings_count == 0 (S1-S3).
  "$PYTHON_BIN" - "$REVIEW" "$require_approved" "$NNN" "$REPO_ROOT" <<'PY'
import json
import sys
from pathlib import Path

audit_path = Path(sys.argv[1])
require_approved = sys.argv[2] == "1"
expected_slice = sys.argv[3]
repo_root = Path(sys.argv[4])
schema_path = repo_root / "docs" / "protocol" / "schemas" / "gate-output.schema.json"

errors = []

if not audit_path.exists():
    print(f"  ✗ {audit_path} ausente", file=sys.stderr)
    sys.exit(1)

try:
    data = json.loads(audit_path.read_text(encoding="utf-8"))
except Exception as exc:
    print(f"  ✗ JSON invalido: {exc}", file=sys.stderr)
    sys.exit(1)

# jsonschema rigoroso se disponivel
try:
    import jsonschema
    with schema_path.open(encoding="utf-8") as f:
        schema = json.load(f)
    jsonschema.validate(data, schema)
except ImportError:
    pass
except Exception as exc:
    errors.append(f"schema gate-output-v1: {exc}")

# Checagem manual dos campos top-level (complementa schema)
required_top = [
    "$schema", "gate", "slice", "lane", "agent", "mode", "verdict",
    "timestamp", "commit_hash", "isolation_context",
    "blocking_findings_count", "non_blocking_findings_count",
    "findings_by_severity", "findings",
]
for key in required_top:
    if key not in data:
        errors.append(f"campo gate-output-v1 ausente: {key}")

if data.get("$schema") != "gate-output-v1":
    errors.append(f"$schema deve ser 'gate-output-v1' (achei '{data.get('$schema')}')")
if data.get("gate") != "plan-review":
    errors.append(f"gate deve ser 'plan-review' (achei '{data.get('gate')}')")
if data.get("slice") != expected_slice:
    errors.append(f"slice deve ser '{expected_slice}' (achei '{data.get('slice')}')")
if data.get("verdict") not in {"approved", "rejected"}:
    errors.append("verdict deve ser approved ou rejected")

# Coerencia blocking_findings_count vs findings_by_severity
sev = data.get("findings_by_severity", {})
if isinstance(sev, dict):
    for s in ("S1", "S2", "S3", "S4", "S5"):
        if not isinstance(sev.get(s), int):
            errors.append(f"findings_by_severity.{s} deve ser inteiro")
    if all(isinstance(sev.get(s), int) for s in ("S1", "S2", "S3")):
        expected_blocking = sev["S1"] + sev["S2"] + sev["S3"]
        if data.get("blocking_findings_count") != expected_blocking:
            errors.append(
                f"blocking_findings_count ({data.get('blocking_findings_count')}) "
                f"inconsistente com S1+S2+S3 ({expected_blocking})"
            )

# Politica zero-tolerance
findings = data.get("findings", [])
if data.get("verdict") == "rejected" and not findings:
    errors.append("rejected exige pelo menos um finding")
if data.get("verdict") == "approved" and data.get("blocking_findings_count") != 0:
    errors.append("approved exige blocking_findings_count == 0")

if require_approved:
    if data.get("verdict") != "approved":
        errors.append("verdict deve ser approved para liberar /draft-tests")
    if data.get("blocking_findings_count") != 0:
        errors.append("approved exige blocking_findings_count == 0 para liberar /draft-tests")

if errors:
    for error in errors:
        sys.stderr.write(f"  [FAIL] {error}\n")
    sys.exit(1)

sys.stdout.write("  [OK] plan-review.json valido (gate-output-v1)\n")
sys.stdout.write(f"  [OK] isolation_context: {data.get('isolation_context', 'N/A')}\n")
if require_approved:
    sys.stdout.write("  [OK] plan-review aprovado com blocking_findings_count == 0\n")
PY
}

case "$MODE" in
  --check)
    echo "[plan-review] verificando pré-condições para slice $NNN..."

    if [ -f "$SPEC" ]; then
      ok "$SPEC existe"
    else
      fail "$SPEC ausente"
    fi

    if [ -f "$PLAN" ]; then
      ok "$PLAN existe"
    else
      fail "$PLAN ausente"
    fi

    if [ -f "$PLAN" ] && bash "$SCRIPT_DIR/draft-plan.sh" "$NNN" --validate >/dev/null; then
      ok "plan.md passa em draft-plan --validate"
    elif [ -f "$PLAN" ]; then
      fail "plan.md não passa em draft-plan --validate"
    fi

    if [ -f "$REVIEW" ]; then
      echo "  ⚠ $REVIEW já existe — reauditoria deve sobrescrever com novo contexto limpo" >&2
    else
      ok "$REVIEW ainda não existe"
    fi

    echo ""
    if [ "$ERR" -eq 0 ]; then
      echo "[plan-review] OK — pré-condições atendidas, pronto para disparar architecture-expert (modo plan-review)"
      exit 0
    fi
    echo "[plan-review FAIL] corrija os itens acima" >&2
    exit 1
    ;;

  --validate)
    echo "[plan-review] validando specs/$NNN/plan-review.json..."
    if ! validate_review_json 0; then
      echo "" >&2
      echo "[plan-review FAIL] plan-review.json inválido" >&2
      exit 1
    fi
    echo ""
    echo "[plan-review] OK — plan-review.json válido"
    ;;

  --approved)
    echo "[plan-review] validando aprovação de specs/$NNN/plan-review.json..."
    if ! validate_review_json 1; then
      echo "" >&2
      echo "[plan-review FAIL] gate não aprovado para /draft-tests" >&2
      exit 1
    fi
    echo ""
    echo "[plan-review] OK — gate aprovado para /draft-tests"
    ;;

  *)
    echo "modo não suportado: $MODE (use --check, --validate ou --approved)" >&2
    exit 1
    ;;
esac
