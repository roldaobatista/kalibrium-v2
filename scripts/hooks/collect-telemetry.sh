#!/usr/bin/env bash
# PostToolUse Bash(git commit*) hook — grava telemetria por commit.
#
# Atualizado em 2026-04-10 (item 1.3 meta-audit): roteia via
# scripts/record-telemetry.sh para preservar a hash-chain. Metadados
# extras do commit (hash, author, subject) ficam no git log — não
# duplicamos aqui. O evento de telemetria registra apenas que houve
# um commit, no slice corrente, para fins de auditoria R6/R11.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

# Determina slice corrente. Default 'meta' (operações no harness).
SLICE_RAW="$(cat specs/.current 2>/dev/null || echo 'meta')"

# Normaliza para o pattern do schema: 'meta' | 'harness' | 'slice-NNN'
case "$SLICE_RAW" in
  meta|harness) SLICE="$SLICE_RAW" ;;
  slice-[0-9][0-9][0-9]) SLICE="$SLICE_RAW" ;;
  [0-9][0-9][0-9]) SLICE="slice-$SLICE_RAW" ;;
  *) SLICE="meta" ;;
esac

# Roteia via record-telemetry.sh (única escrita autorizada de .claude/telemetry/).
# Erros aqui NÃO bloqueiam o commit (PostToolUse — commit já aconteceu).
bash "$REPO_ROOT/scripts/record-telemetry.sh" \
  --event=commit \
  --slice="$SLICE" \
  --verdict=n/a \
  --next-action=n/a \
  --reject-count=0 \
  --actor="commit-hook" >/dev/null 2>&1 || true

exit 0
