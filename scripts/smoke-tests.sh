#!/usr/bin/env bash
# Slice 018 — AC-002 (B-036)
#
# Runner de smoke suite local. Invocado pelo pre-push hook quando
# detect-shared-file-change.sh reporta shared_changed=true.
#
# Target: <30s (decisao D3 do plan.md). Falha => bloqueia push.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

say() { echo "[smoke] $*" >&2; }

say "Rodando smoke suite (@smoke) via playwright..."
say "Target: <30s total. Falha bloqueia push."

# --grep @smoke filtra apenas testes tageados (Decisao D3 do plan)
# Reporter list eh curto e mostra AC violado em caso de falha
exec npx playwright test --grep @smoke --reporter=list
