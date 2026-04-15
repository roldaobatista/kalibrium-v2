#!/usr/bin/env bash
# plan-review.sh — valida o gate obrigatório de plan-reviewer antes dos testes.
#
# Uso:
#   bash scripts/plan-review.sh NNN --check     (pré-condições para plan-reviewer)
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

  "$PYTHON_BIN" - "$REVIEW" "$require_approved" <<'PY'
import json
import sys
from pathlib import Path

path = Path(sys.argv[1])
require_approved = sys.argv[2] == "1"
errors = []

if not path.exists():
    print(f"  ✗ {path} ausente", file=sys.stderr)
    sys.exit(1)

try:
    data = json.loads(path.read_text(encoding="utf-8"))
except Exception as exc:
    print(f"  ✗ JSON inválido: {exc}", file=sys.stderr)
    sys.exit(1)

required = ["schema_version", "slice_id", "review_date", "provenance", "verdict", "summary", "checks", "findings", "stats"]
for key in required:
    if key not in data:
        errors.append(f"campo obrigatório ausente: {key}")

expected_slice = f"slice-{path.parent.name}"
if data.get("schema_version") != "1.0.0":
    errors.append("schema_version deve ser 1.0.0")
if data.get("slice_id") != expected_slice:
    errors.append(f"slice_id deve ser {expected_slice}")
if data.get("verdict") not in {"approved", "rejected"}:
    errors.append("verdict deve ser approved ou rejected")

provenance = data.get("provenance")
if not isinstance(provenance, dict):
    errors.append("provenance deve ser objeto")
else:
    if provenance.get("agent") != "plan-reviewer":
        errors.append("provenance.agent deve ser plan-reviewer")
    if provenance.get("context") != "isolated":
        errors.append("provenance.context deve ser isolated")

expected_checks = [
    "ac_coverage",
    "architectural_decisions",
    "technical_feasibility",
    "risks_mitigations",
    "security",
    "simplicity",
]
checks = data.get("checks", {})
if not isinstance(checks, dict):
    errors.append("checks deve ser objeto")
else:
    for check in expected_checks:
        value = checks.get(check)
        if not isinstance(value, dict):
            errors.append(f"check ausente ou inválido: {check}")
            continue
        if value.get("status") not in {"pass", "fail"}:
            errors.append(f"{check}.status deve ser pass ou fail")
        if not isinstance(value.get("details"), str) or value.get("details") == "":
            errors.append(f"{check}.details deve ser string não vazia")

findings = data.get("findings", [])
if not isinstance(findings, list):
    errors.append("findings deve ser array")
else:
    for index, finding in enumerate(findings, start=1):
        if not isinstance(finding, dict):
            errors.append(f"finding {index} deve ser objeto")
            continue
        for key in ["id", "severity", "category", "location", "description", "recommendation"]:
            if key not in finding:
                errors.append(f"finding {index} sem campo {key}")
        if finding.get("severity") not in {"critical", "major", "minor"}:
            errors.append(f"finding {index} com severity inválida")

stats = data.get("stats", {})
if not isinstance(stats, dict):
    errors.append("stats deve ser objeto")
else:
    for key in ["total_checks", "passed", "failed", "findings_critical", "findings_major", "findings_minor"]:
        if not isinstance(stats.get(key), int):
            errors.append(f"stats.{key} deve ser inteiro")

if require_approved:
    if data.get("verdict") != "approved":
        errors.append("verdict deve ser approved")
    if findings != []:
        errors.append("findings deve ser [] para liberar /draft-tests")
    if isinstance(checks, dict):
        failed_checks = [
            name for name in expected_checks
            if isinstance(checks.get(name), dict) and checks[name].get("status") != "pass"
        ]
        if failed_checks:
            errors.append("checks com status fail: " + ", ".join(failed_checks))
    if isinstance(stats, dict):
        if stats.get("failed") != 0:
            errors.append("stats.failed deve ser 0")
        for key in ["findings_critical", "findings_major", "findings_minor"]:
            if stats.get(key) != 0:
                errors.append(f"stats.{key} deve ser 0")

if errors:
    for error in errors:
        print(f"  ✗ {error}", file=sys.stderr)
    sys.exit(1)

print("  ✓ plan-review.json válido")
print("  ✓ provenance do plan-reviewer em contexto isolado")
if require_approved:
    print("  ✓ plan-review aprovado com findings []")
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
      echo "[plan-review] OK — pré-condições atendidas, pronto para disparar plan-reviewer"
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
