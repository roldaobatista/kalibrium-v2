#!/usr/bin/env bash
set -euo pipefail
SLICE="${1:?slice number required}"
SPEC_DIR="specs/${SLICE}"

[[ -f "${SPEC_DIR}/spec.md" ]] || { echo "missing ${SPEC_DIR}/spec.md"; exit 1; }

rm -rf security-review-input test-audit-input functional-review-input
mkdir -p security-review-input/source test-audit-input/source-files test-audit-input/test-files functional-review-input/source

# files changed vs main, excluding gate artifacts and docs-only
git diff --name-only main..HEAD > /tmp/slice-${SLICE}-all-changed.txt

# source files (app/ + database/ + routes/ + resources/)
grep -E '^(app/|database/|routes/|resources/)' /tmp/slice-${SLICE}-all-changed.txt > /tmp/slice-${SLICE}-source.txt || true
# test files
grep -E '^tests/' /tmp/slice-${SLICE}-all-changed.txt > /tmp/slice-${SLICE}-tests.txt || true

# security-review-input
cp "${SPEC_DIR}/spec.md" security-review-input/spec.md
cp /tmp/slice-${SLICE}-all-changed.txt security-review-input/files-changed.txt
while IFS= read -r f; do
  [[ -f "$f" ]] || continue
  mkdir -p "security-review-input/source/$(dirname "$f")"
  cp "$f" "security-review-input/source/$f"
done < /tmp/slice-${SLICE}-source.txt
cp docs/security/threat-model.md security-review-input/threat-model.md 2>/dev/null || echo "# threat-model missing" > security-review-input/threat-model.md
cp docs/security/lgpd-base-legal.md security-review-input/lgpd-base-legal.md 2>/dev/null || echo "# lgpd missing" > security-review-input/lgpd-base-legal.md
cp docs/constitution.md security-review-input/constitution-snapshot.md

# test-audit-input
cp "${SPEC_DIR}/spec.md" test-audit-input/spec.md
while IFS= read -r f; do
  [[ -f "$f" ]] || continue
  mkdir -p "test-audit-input/source-files/$(dirname "$f")"
  cp "$f" "test-audit-input/source-files/$f"
done < /tmp/slice-${SLICE}-source.txt
while IFS= read -r f; do
  [[ -f "$f" ]] || continue
  mkdir -p "test-audit-input/test-files/$(dirname "$f")"
  cp "$f" "test-audit-input/test-files/$f"
done < /tmp/slice-${SLICE}-tests.txt
# ac-list from spec: naive parse ACs starting with "AC-"
grep -oE 'AC-[0-9]+' "${SPEC_DIR}/spec.md" | sort -u | python -c "
import sys,json
acs=[{'id':l.strip(),'source':'spec.md'} for l in sys.stdin if l.strip()]
print(json.dumps({'slice':'${SLICE}','acs':acs}, indent=2))" > test-audit-input/ac-list.json
php artisan test tests/slice-${SLICE} > test-audit-input/test-results.txt 2>&1 || true
echo '{"note":"coverage report not generated; tests ran in test-results.txt"}' > test-audit-input/coverage-report.json

# functional-review-input
cp "${SPEC_DIR}/spec.md" functional-review-input/spec.md
cp test-audit-input/ac-list.json functional-review-input/ac-list.json
cp test-audit-input/test-results.txt functional-review-input/test-results.txt
while IFS= read -r f; do
  [[ -f "$f" ]] || continue
  mkdir -p "functional-review-input/source/$(dirname "$f")"
  cp "$f" "functional-review-input/source/$f"
done < /tmp/slice-${SLICE}-source.txt
cp docs/product/glossary.md functional-review-input/glossary-pm.md 2>/dev/null || echo "# glossary missing" > functional-review-input/glossary-pm.md
cp docs/product/personas.md functional-review-input/personas.md 2>/dev/null || echo "# personas missing" > functional-review-input/personas.md
cp docs/product/flows/flows-e02-auth.md functional-review-input/journeys.md 2>/dev/null || echo "# journeys missing" > functional-review-input/journeys.md
cp docs/product/prd.md functional-review-input/prd-excerpt.md 2>/dev/null || echo "# prd missing" > functional-review-input/prd-excerpt.md

echo "[build-gate-inputs] OK"
ls security-review-input test-audit-input functional-review-input | head -40
