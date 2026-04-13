#!/usr/bin/env bash
# PostToolUse Bash(git commit*) hook — grava telemetria por commit.
#
# v2: Melhoria sobre v1 — erros são logados em stderr ao invés de
# ignorados silenciosamente. O hook continua não-bloqueante (PostToolUse),
# mas falhas ficam visíveis para diagnóstico.
#
# Atualizado em 2026-04-10 (item 1.3 meta-audit): roteia via
# scripts/record-telemetry.sh para preservar a hash-chain.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

say() { echo "[collect-telemetry] $*" >&2; }

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
# Erros NÃO bloqueiam o commit (PostToolUse — commit já aconteceu),
# mas são logados para diagnóstico (v2: não mais silenciados).
if ! bash "$REPO_ROOT/scripts/record-telemetry.sh" \
  --event=commit \
  --slice="$SLICE" \
  --verdict=n/a \
  --next-action=n/a \
  --reject-count=0 \
  --actor="commit-hook" 2>&1; then
  say "WARN: falha ao gravar telemetria para slice=$SLICE"
  say "  Verifique scripts/record-telemetry.sh e .claude/telemetry/"
  say "  O commit NÃO foi afetado (hook PostToolUse), mas telemetria pode estar incompleta."
fi

exit 0
