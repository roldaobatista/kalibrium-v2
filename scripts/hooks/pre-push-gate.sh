#!/usr/bin/env bash
# PreToolUse Bash(git push*) hook — P8 push-time + safety.
#
# 1. Bloqueia push em main/master sem PR (defesa).
# 2. Bloqueia --force em main/master.
# 3. Bloqueia --no-verify (R9).
# 4. Roda testsuite do domínio (quando configurado pós ADR-0001).

set -euo pipefail

CMD="${CLAUDE_TOOL_ARG_COMMAND:-${1:-}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

say() { echo "[pre-push-gate] $*" >&2; }
die() { echo "[pre-push-gate BLOCK] $*" >&2; exit 1; }

# ---------- 1. R9 — bypass ----------
if echo "$CMD" | grep -qE -- "--no-verify"; then
  die "R9: --no-verify proibido"
fi

# ---------- 2. Branch atual ----------
BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '')"

case "$BRANCH" in
  main|master)
    # --- Exceção: bootstrap inicial ---
    # Primeiro push do repo (branch ainda sem upstream local) com -u/--set-upstream.
    # Acontece uma única vez no ciclo de vida do repo. Depois disso, qualquer push
    # direto em main/master é bloqueado como esperado.
    if ! git rev-parse --verify "${BRANCH}@{upstream}" >/dev/null 2>&1; then
      if echo "$CMD" | grep -qE -- "-u |--set-upstream"; then
        say "bootstrap: primeiro push de '$BRANCH' com -u — permitido uma única vez"
        exit 0
      fi
    fi
    # --- Caso normal: push em main/master bloqueado ---
    if echo "$CMD" | grep -qE -- "--force|-f\b"; then
      die "push --force em '$BRANCH' proibido"
    fi
    die "push direto em '$BRANCH' proibido — use feature branch + PR"
    ;;
esac

# ---------- 3. Domain testsuite (post-ADR-0001) ----------
# Placeholder: quando a stack estiver definida, rodar aqui testsuite do domínio
# afetado pelos commits entre @{upstream}..HEAD.
# Por ora, apenas sinaliza que o gate existe.
say "OK branch='$BRANCH' (domain testsuite ainda não configurada — pós ADR-0001)"
exit 0
