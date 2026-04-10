#!/usr/bin/env bash
# PostToolUse Bash(git commit*) hook — grava telemetria por commit.
# Escreve linha JSONL em .claude/telemetry/<slice-atual>.jsonl
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

mkdir -p .claude/telemetry

SLICE="$(cat specs/.current 2>/dev/null || echo 'meta')"
TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# Proteção contra repo sem commits (exit 128 do git)
HASH="unknown"
AUTHOR="unknown"
SUBJECT=""
FILES="0"
if git rev-parse --verify HEAD >/dev/null 2>&1; then
  HASH="$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')"
  AUTHOR="$(git log -1 --format='%an' 2>/dev/null || echo 'unknown')"
  SUBJECT="$(git log -1 --format='%s' 2>/dev/null || echo '')"
  FILES="$(git show --stat --format='' HEAD 2>/dev/null | wc -l | tr -d ' ')"
fi

# JSON line (escaping básico — sem aspas duplas em subject vai quebrar, aceitável)
printf '{"event":"commit","timestamp":"%s","slice":"%s","hash":"%s","author":"%s","files_touched":%s,"subject":"%s"}\n' \
  "$TS" "$SLICE" "$HASH" "$AUTHOR" "$FILES" "${SUBJECT//\"/\\\"}" \
  >> ".claude/telemetry/${SLICE}.jsonl"

exit 0
