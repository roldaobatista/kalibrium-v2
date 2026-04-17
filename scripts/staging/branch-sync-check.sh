#!/usr/bin/env bash
# branch-sync-check.sh — detecta branch atrasada vs origin/main.
# Executado por session-start.sh (após selo) ou manualmente antes de abrir slice.
#
# Origem: débito B-031 em docs/guide-backlog.md (slice-015 retrospectiva).
# Motivação: branches que ficam > N commits atrás de main causam conflitos
# de merge grandes, incluindo em arquivos selados (MANIFEST.sha256).
#
# Uso: bash scripts/staging/branch-sync-check.sh
# Env:
#   KALIB_BRANCH_SYNC_THRESHOLD  (default: 10) — commits de tolerância
#   KALIB_BRANCH_SYNC_FAIL       (default: 0) — se 1, exit 1 em vez de warn
#
# Exit codes:
#   0 = branch ok, ou na própria main
#   1 = branch desatualizada e KALIB_BRANCH_SYNC_FAIL=1
#
# ESTE SCRIPT É UMA PROPOSTA. Para entrar em produção, deve:
#   1. Ser movido para scripts/hooks/branch-sync-check.sh
#   2. Ser referenciado em scripts/hooks/session-start.sh
#   3. Ter seu hash computado em scripts/hooks/MANIFEST.sha256
#   4. PM executa `KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh`
#      em terminal externo (agente não pode mexer em área selada).

set -uo pipefail

THRESHOLD="${KALIB_BRANCH_SYNC_THRESHOLD:-10}"
FAIL_MODE="${KALIB_BRANCH_SYNC_FAIL:-0}"

BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '')"
[ -z "$BRANCH" ] && exit 0  # não é repo git
[ "$BRANCH" = "main" ] && exit 0  # próprio main

# Fetch silencioso de origin/main
git fetch origin main --quiet 2>/dev/null || {
  echo "[branch-sync] aviso: git fetch falhou (offline?); pulando verificação"
  exit 0
}

# Quantos commits em origin/main não estão na branch atual?
BEHIND="$(git rev-list --count HEAD..origin/main 2>/dev/null || echo 0)"

if [ "$BEHIND" -gt "$THRESHOLD" ]; then
  echo ""
  echo "=============================================================="
  echo "  [branch-sync WARN] branch '$BRANCH' está $BEHIND commits"
  echo "  atrás de origin/main (threshold: $THRESHOLD)."
  echo ""
  echo "  Risco: merge grande pode causar conflitos em arquivos"
  echo "  selados (scripts/hooks/, .claude/settings.json*) e exigir"
  echo "  abandono da branch + cherry-pick manual."
  echo ""
  echo "  Ação recomendada:"
  echo "    git fetch origin main"
  echo "    git merge origin/main"
  echo "  (ou rebase, conforme política do time)"
  echo "=============================================================="
  echo ""
  [ "$FAIL_MODE" = "1" ] && exit 1
fi

exit 0
