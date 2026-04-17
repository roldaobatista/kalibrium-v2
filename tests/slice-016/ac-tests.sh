#!/usr/bin/env bash
# tests/slice-016/ac-tests.sh
#
# Slice 016 — E15-S02: Scaffold React+TypeScript+Ionic+Capacitor+Vite.
# 100% frontend — nao ha testes Pest/PHPUnit neste slice.
# Os 14 ACs sao validados por:
#   - 35 testes Node --test em tests/scaffold/*.test.cjs (ACs 002, 005, 007,
#     008, 010, 011, 012, 013, 014 + sub-tests de cada)
#   - 4 testes Playwright em tests/e2e/*.spec.ts (ACs 001, 006, 009)
#
# Este wrapper permite que scripts/test-scope.php slice 016 e
# mechanical-gates.sh Gate 1 chamem a suite correta apesar de slice
# nao ter testes PHP.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

echo "[slice-016/ac-tests] Node --test (scaffold)"
npm run test:scaffold

echo "[slice-016/ac-tests] Playwright (e2e)"
npm run test:e2e

echo "[slice-016/ac-tests] OK — 14 ACs do slice-016 verdes"
