#!/usr/bin/env bash
# Smoke test dos scripts auxiliares: validate-verification, record-tokens, verify-slice --validate e plan-review.
# Separado do smoke-test-hooks.sh para manter categorias distintas.
#
# Uso: bash scripts/smoke-test-scripts.sh

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

PASS=0
FAIL=0
TESTS=0

run_test() {
  local desc="$1"
  local expected="$2"
  shift 2
  TESTS=$((TESTS+1))
  printf "  [%d] %-70s " "$TESTS" "$desc"
  local actual=0
  "$@" >/tmp/smoke-scripts-out.txt 2>&1 || actual=$?
  if [ "$actual" -eq "$expected" ]; then
    echo "OK (exit=$actual)"
    PASS=$((PASS+1))
  else
    echo "FAIL (exit=$actual, esperado=$expected)"
    echo "      output:"
    sed 's/^/        /' /tmp/smoke-scripts-out.txt
    FAIL=$((FAIL+1))
  fi
}

echo "=== smoke-test-scripts ==="
echo ""

# Salva config git original para restaurar no trap
ORIG_GIT_NAME="$(git config --local user.name 2>/dev/null || echo '')"
ORIG_GIT_EMAIL="$(git config --local user.email 2>/dev/null || echo '')"

# Override local APENAS durante os testes
git config --local user.name "smoke-test-user" 2>/dev/null || true
git config --local user.email "smoke@test.local" 2>/dev/null || true

restore_git_config() {
  if [ -n "${ORIG_GIT_NAME:-}" ]; then
    git config --local user.name "$ORIG_GIT_NAME" 2>/dev/null || true
  else
    git config --local --unset user.name 2>/dev/null || true
  fi
  if [ -n "${ORIG_GIT_EMAIL:-}" ]; then
    git config --local user.email "$ORIG_GIT_EMAIL" 2>/dev/null || true
  else
    git config --local --unset user.email 2>/dev/null || true
  fi
}

FIX="$(mktemp -d -t kalib-scripts.XXXXXX)"
trap 'rm -rf "$FIX"; rm -f /tmp/smoke-scripts-out.txt; restore_git_config' EXIT

# ======================================================================
# validate-verification.sh
# ======================================================================
echo "[1/5] validate-verification.sh"

# Fixture: approved válido
cat > "$FIX/approved.json" <<'EOF'
{
  "slice_id": "slice-001",
  "verdict": "approved",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [
    {"ac": "AC-001", "status": "pass", "evidence": "tests/foo.test.ts:42"},
    {"ac": "AC-002", "status": "pass", "evidence": "tests/bar.test.ts:10"}
  ],
  "violations": [],
  "next_action": "open_pr"
}
EOF
run_test "validate approved válido" 0 \
  bash scripts/validate-verification.sh "$FIX/approved.json"

# Fixture: rejected válido
cat > "$FIX/rejected.json" <<'EOF'
{
  "slice_id": "slice-002",
  "verdict": "rejected",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [
    {"ac": "AC-001", "status": "fail", "evidence": "tests/foo.test.ts:42 — asserção fraca"}
  ],
  "violations": [
    {"rule": "P2", "file": "src/foo.ts", "line": 10, "reason": "sem teste mapeado"}
  ],
  "next_action": "return_to_implementer"
}
EOF
run_test "validate rejected válido" 0 \
  bash scripts/validate-verification.sh "$FIX/rejected.json"

# Fixture: incoerente — approved com violations
cat > "$FIX/bad-approved-with-violations.json" <<'EOF'
{
  "slice_id": "slice-003",
  "verdict": "approved",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [{"ac": "AC-001", "status": "pass", "evidence": "x"}],
  "violations": [{"rule": "P2", "file": "x", "line": 1, "reason": "y"}],
  "next_action": "open_pr"
}
EOF
run_test "validate rejeita approved com violations" 1 \
  bash scripts/validate-verification.sh "$FIX/bad-approved-with-violations.json"

# Fixture: incoerente — rejected com open_pr
cat > "$FIX/bad-rejected-open-pr.json" <<'EOF'
{
  "slice_id": "slice-004",
  "verdict": "rejected",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [{"ac": "AC-001", "status": "fail", "evidence": "x"}],
  "violations": [],
  "next_action": "open_pr"
}
EOF
run_test "validate rejeita rejected + open_pr" 1 \
  bash scripts/validate-verification.sh "$FIX/bad-rejected-open-pr.json"

# Fixture: verdict inválido
cat > "$FIX/bad-verdict.json" <<'EOF'
{
  "slice_id": "slice-005",
  "verdict": "maybe",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [{"ac": "AC-001", "status": "pass", "evidence": "x"}],
  "violations": [],
  "next_action": "open_pr"
}
EOF
run_test "validate rejeita verdict inválido" 1 \
  bash scripts/validate-verification.sh "$FIX/bad-verdict.json"

# Fixture: rule inválida
cat > "$FIX/bad-rule.json" <<'EOF'
{
  "slice_id": "slice-006",
  "verdict": "rejected",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [{"ac": "AC-001", "status": "fail", "evidence": "x"}],
  "violations": [{"rule": "P99", "file": "x", "line": 1, "reason": "y"}],
  "next_action": "return_to_implementer"
}
EOF
run_test "validate rejeita rule inválida (P99)" 1 \
  bash scripts/validate-verification.sh "$FIX/bad-rule.json"

# Campo ausente
cat > "$FIX/missing-field.json" <<'EOF'
{
  "slice_id": "slice-007",
  "verdict": "approved"
}
EOF
run_test "validate rejeita campos obrigatórios ausentes" 1 \
  bash scripts/validate-verification.sh "$FIX/missing-field.json"

# ======================================================================
# record-tokens.sh
# ======================================================================
echo "[2/5] record-tokens.sh"

# Limpa telemetria de teste
rm -f .claude/telemetry/slice-999.jsonl

run_test "record-tokens válido (architect)" 0 \
  bash scripts/record-tokens.sh architect 999 12340

run_test "record-tokens válido (verifier)" 0 \
  bash scripts/record-tokens.sh verifier 999 8500

run_test "record-tokens rejeita agent desconhecido" 1 \
  bash scripts/record-tokens.sh frankenstein 999 1000

run_test "record-tokens rejeita tokens não-numérico" 1 \
  bash scripts/record-tokens.sh architect 999 abc

run_test "record-tokens rejeita slice inválido" 1 \
  bash scripts/record-tokens.sh architect XX 1000

# Verifica que o arquivo foi criado com eventos
TOKENS_LINES=$(grep -c '"event":"tokens"' .claude/telemetry/slice-999.jsonl 2>/dev/null || echo 0)
TESTS=$((TESTS+1))
printf "  [%d] %-70s " "$TESTS" "record-tokens gravou 2 eventos em slice-999"
if [ "$TOKENS_LINES" -eq 2 ]; then
  echo "OK"
  PASS=$((PASS+1))
else
  echo "FAIL (encontrou $TOKENS_LINES eventos, esperado 2)"
  FAIL=$((FAIL+1))
fi

# ======================================================================
# slice-report.sh
# ======================================================================
echo "[3/5] slice-report.sh"

run_test "slice-report rejeita NNN inválido" 1 \
  bash scripts/slice-report.sh XX

run_test "slice-report rejeita sem telemetria" 1 \
  bash scripts/slice-report.sh 888

# Com telemetria válida (criada por record-tokens acima)
run_test "slice-report gera report com telemetria" 0 \
  bash scripts/slice-report.sh 999

# Verifica que o arquivo foi criado
TESTS=$((TESTS+1))
printf "  [%d] %-70s " "$TESTS" "slice-report gerou docs/retrospectives/slice-999-report.md"
if [ -f docs/retrospectives/slice-999-report.md ]; then
  echo "OK"
  PASS=$((PASS+1))
else
  echo "FAIL"
  FAIL=$((FAIL+1))
fi

# ======================================================================
# verify-slice.sh --validate
# ======================================================================
echo "[4/5] verify-slice.sh --validate"

# Prepara estrutura mínima de slice
mkdir -p specs/995
cat > specs/995/spec.md <<'EOF'
# Slice 995 — teste smoke

## Acceptance Criteria

- **AC-001:** primeira coisa
- **AC-002:** segunda coisa
EOF
cat > specs/995/plan.md <<'EOF'
# Plan
EOF

# Copia fixture approved para verification-input
mkdir -p verification-input
cat > verification-input/verification.json <<'EOF'
{
  "slice_id": "slice-995",
  "verdict": "approved",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [
    {"ac": "AC-001", "status": "pass", "evidence": "tests/foo.test.ts:1"},
    {"ac": "AC-002", "status": "pass", "evidence": "tests/foo.test.ts:2"}
  ],
  "violations": [],
  "next_action": "open_pr"
}
EOF

# Limpa telemetria anterior
rm -f .claude/telemetry/slice-995.jsonl

run_test "verify-slice --validate approved" 0 \
  bash scripts/verify-slice.sh 995 --validate

# Segunda invocação com rejected
cat > verification-input/verification.json <<'EOF'
{
  "slice_id": "slice-994",
  "verdict": "rejected",
  "timestamp": "2026-04-10T14:30:00Z",
  "ac_checks": [{"ac": "AC-001", "status": "fail", "evidence": "x"}],
  "violations": [{"rule": "P2", "file": "x", "line": 1, "reason": "y"}],
  "next_action": "return_to_implementer"
}
EOF
mkdir -p specs/994
cp specs/995/spec.md specs/994/spec.md
cp specs/995/plan.md specs/994/plan.md
rm -f .claude/telemetry/slice-994.jsonl

run_test "verify-slice --validate rejected (primeira de seis)" 1 \
  bash scripts/verify-slice.sh 994 --validate

# Rejeições 2 a 5 permanecem no loop automático
for attempt in 2 3 4 5; do
  run_test "verify-slice --validate rejected (${attempt}/6)" 1 \
    bash scripts/verify-slice.sh 994 --validate
done

# Sexta rejeição → R6 (exit 2)
run_test "verify-slice --validate rejected (sexta → R6 exit 2)" 2 \
  bash scripts/verify-slice.sh 994 --validate

# Verifica que incident foi criado
TESTS=$((TESTS+1))
printf "  [%d] %-70s " "$TESTS" "R6 criou incident file"
if ls docs/incidents/slice-994-escalation-*.md >/dev/null 2>&1; then
  echo "OK"
  PASS=$((PASS+1))
else
  echo "FAIL"
  FAIL=$((FAIL+1))
fi

# ======================================================================
# plan-review.sh --approved
# ======================================================================
echo "[5/5] plan-review.sh --approved"

mkdir -p specs/996
cat > specs/996/plan-review.json <<'EOF'
{
  "schema_version": "1.0.0",
  "slice_id": "slice-996",
  "review_date": "2026-04-13",
  "verdict": "approved",
  "summary": "Plan review aprovado para smoke test.",
  "checks": {
    "ac_coverage": {"status": "pass", "details": "ok"},
    "architectural_decisions": {"status": "pass", "details": "ok"},
    "technical_feasibility": {"status": "pass", "details": "ok"},
    "risks_mitigations": {"status": "pass", "details": "ok"},
    "security": {"status": "pass", "details": "ok"},
    "simplicity": {"status": "pass", "details": "ok"}
  },
  "findings": [],
  "stats": {
    "total_checks": 6,
    "passed": 6,
    "failed": 0,
    "findings_critical": 0,
    "findings_major": 0,
    "findings_minor": 0
  }
}
EOF

run_test "plan-review aprova findings vazio" 0 \
  bash scripts/plan-review.sh 996 --approved

cat > specs/996/plan-review.json <<'EOF'
{
  "schema_version": "1.0.0",
  "slice_id": "slice-996",
  "review_date": "2026-04-13",
  "verdict": "approved",
  "summary": "Plan review com finding deve bloquear o smoke.",
  "checks": {
    "ac_coverage": {"status": "fail", "details": "finding intencional"},
    "architectural_decisions": {"status": "pass", "details": "ok"},
    "technical_feasibility": {"status": "pass", "details": "ok"},
    "risks_mitigations": {"status": "pass", "details": "ok"},
    "security": {"status": "pass", "details": "ok"},
    "simplicity": {"status": "pass", "details": "ok"}
  },
  "findings": [
    {
      "id": "PR-001",
      "severity": "minor",
      "category": "ac_coverage",
      "location": "plan.md",
      "description": "finding intencional",
      "recommendation": "corrigir"
    }
  ],
  "stats": {
    "total_checks": 6,
    "passed": 5,
    "failed": 1,
    "findings_critical": 0,
    "findings_major": 0,
    "findings_minor": 1
  }
}
EOF

run_test "plan-review rejeita approved com finding minor" 1 \
  bash scripts/plan-review.sh 996 --approved

# ======================================================================
# Cleanup
# ======================================================================
rm -rf specs/995 specs/994 verification-input
rm -rf specs/996
rm -f .claude/telemetry/slice-999.jsonl
rm -f .claude/telemetry/slice-995.jsonl
rm -f .claude/telemetry/slice-994.jsonl
rm -f docs/retrospectives/slice-999-report.md
rm -f docs/incidents/slice-994-escalation-*.md
rm -f docs/explanations/slice-995.md
rm -f docs/explanations/slice-994.md

# ======================================================================
# Resultado
# ======================================================================
echo ""
echo "=== RESULTADO ==="
echo "Testes rodados: $TESTS"
echo "Passou:         $PASS"
echo "Falhou:         $FAIL"

if [ "$FAIL" -eq 0 ]; then
  echo ""
  echo "[smoke-test-scripts OK] todos os scripts funcionam"
  exit 0
else
  echo ""
  echo "[smoke-test-scripts FAIL] $FAIL script(s) com comportamento inesperado"
  exit 1
fi
