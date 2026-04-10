#!/usr/bin/env bash
# Smoke test para validar que todos os hooks funcionam no ambiente local
# (especialmente Windows 11 + Git Bash, onde pathseps, docker exec e TTY têm armadilhas).
#
# Uso: bash scripts/smoke-test-hooks.sh
#
# Exit 0 = todos os hooks executaram com exit code esperado.
# Exit 1 = pelo menos um hook falhou inesperadamente.

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
  printf "  [%d] %-60s " "$TESTS" "$desc"
  local actual=0
  "$@" >/tmp/smoke-test-out.txt 2>&1 || actual=$?
  if [ "$actual" -eq "$expected" ]; then
    echo "OK (exit=$actual)"
    PASS=$((PASS+1))
  else
    echo "FAIL (exit=$actual, esperado=$expected)"
    echo "      output:"
    sed 's/^/        /' /tmp/smoke-test-out.txt
    FAIL=$((FAIL+1))
  fi
}

echo "=== smoke-test-hooks ==="
echo "Repo: $REPO_ROOT"
echo "Shell: $BASH_VERSION"
echo "OS: $(uname -s 2>/dev/null || echo unknown)"
echo ""

# ---------- Preparar fixtures ----------
TMPDIR="$(mktemp -d -t kalib-smoke.XXXXXX)"
trap 'rm -rf "$TMPDIR"' EXIT
mkdir -p "$TMPDIR/verification-input"
touch "$TMPDIR/verification-input/spec.md"
echo "dummy" > "$TMPDIR/dummy.txt"

# ---------- 1. session-start.sh ----------
echo "[1/12] session-start.sh"
run_test "session-start limpa deve passar" 0 \
  bash scripts/hooks/session-start.sh

# Cria arquivo proibido temporariamente
touch .cursorrules
run_test "session-start detecta .cursorrules" 1 \
  bash scripts/hooks/session-start.sh
rm -f .cursorrules

# ---------- 2. forbidden-files-scan.sh ----------
echo "[2/12] forbidden-files-scan.sh"
run_test "forbidden-files-scan limpo" 0 \
  bash scripts/hooks/forbidden-files-scan.sh

touch AGENTS.md
run_test "forbidden-files-scan detecta AGENTS.md" 1 \
  bash scripts/hooks/forbidden-files-scan.sh
rm -f AGENTS.md

# ---------- 3. verifier-sandbox.sh ----------
echo "[3/12] verifier-sandbox.sh"
run_test "verifier-sandbox fora do verifier permite" 0 \
  env -u CLAUDE_AGENT_NAME bash scripts/hooks/verifier-sandbox.sh "specs/NNN/plan.md"

run_test "verifier-sandbox bloqueia plan.md quando verifier" 1 \
  env CLAUDE_AGENT_NAME=verifier CLAUDE_TOOL_ARG_FILE="specs/NNN/plan.md" \
  bash scripts/hooks/verifier-sandbox.sh

run_test "verifier-sandbox permite verification-input/" 0 \
  env CLAUDE_AGENT_NAME=verifier CLAUDE_TOOL_ARG_FILE="verification-input/spec.md" \
  bash scripts/hooks/verifier-sandbox.sh

# ---------- 4. read-secrets-block.sh ----------
echo "[4/12] read-secrets-block.sh"
run_test "read-secrets-block permite .md comum" 0 \
  env CLAUDE_TOOL_ARG_FILE="CLAUDE.md" bash scripts/hooks/read-secrets-block.sh

run_test "read-secrets-block bloqueia .env" 1 \
  env CLAUDE_TOOL_ARG_FILE=".env" bash scripts/hooks/read-secrets-block.sh

run_test "read-secrets-block bloqueia credentials.json" 1 \
  env CLAUDE_TOOL_ARG_FILE="credentials.json" bash scripts/hooks/read-secrets-block.sh

run_test "read-secrets-block permite .env.example" 0 \
  env CLAUDE_TOOL_ARG_FILE=".env.example" bash scripts/hooks/read-secrets-block.sh

# ---------- 5. edit-scope-check.sh ----------
echo "[5/12] edit-scope-check.sh"
run_test "edit-scope-check sem slice ativo permite" 0 \
  env CLAUDE_TOOL_ARG_FILE="src/foo.ts" bash scripts/hooks/edit-scope-check.sh

run_test "edit-scope-check bloqueia docs/reference" 1 \
  env CLAUDE_TOOL_ARG_FILE="docs/reference/v1-post-mortem.md" bash scripts/hooks/edit-scope-check.sh

run_test "edit-scope-check bloqueia constitution" 1 \
  env CLAUDE_TOOL_ARG_FILE="docs/constitution.md" bash scripts/hooks/edit-scope-check.sh

# ---------- 6. block-project-init.sh ----------
echo "[6/12] block-project-init.sh"
run_test "block-project-init comando inocente permite" 0 \
  env CLAUDE_TOOL_ARG_COMMAND="ls -la" bash scripts/hooks/block-project-init.sh

run_test "block-project-init bloqueia npm init" 1 \
  env CLAUDE_TOOL_ARG_COMMAND="npm init -y" bash scripts/hooks/block-project-init.sh

run_test "block-project-init bloqueia --no-verify" 1 \
  env CLAUDE_TOOL_ARG_COMMAND="git commit --no-verify -m test" bash scripts/hooks/block-project-init.sh

# Com ADR-0001 presente, init passa
mkdir -p docs/adr
touch docs/adr/0001-stack-choice.md
run_test "block-project-init permite npm init com ADR-0001" 0 \
  env CLAUDE_TOOL_ARG_COMMAND="npm init -y" bash scripts/hooks/block-project-init.sh
rm -f docs/adr/0001-stack-choice.md

# ---------- 7. post-edit-gate.sh ----------
echo "[7/12] post-edit-gate.sh"
run_test "post-edit-gate arquivo markdown permite" 0 \
  env CLAUDE_TOOL_ARG_FILE="docs/guide-backlog.md" bash scripts/hooks/post-edit-gate.sh

run_test "post-edit-gate arquivo inexistente permite" 0 \
  env CLAUDE_TOOL_ARG_FILE="$TMPDIR/nao-existe.md" bash scripts/hooks/post-edit-gate.sh

# ---------- 8. pre-commit-gate.sh ----------
echo "[8/12] pre-commit-gate.sh"

# --- Salva config git original (será restaurada ao final da seção) ---
ORIG_GIT_NAME="$(git config --local user.name 2>/dev/null || echo '')"
ORIG_GIT_EMAIL="$(git config --local user.email 2>/dev/null || echo '')"

# Sobrescreve localmente APENAS durante os testes. Cleanup no final da seção.
git config --local user.name "smoke-test-user"
git config --local user.email "smoke@test.local"

run_test "pre-commit-gate comando inocente permite" 0 \
  env CLAUDE_TOOL_ARG_COMMAND='git commit -m "docs: foo"' bash scripts/hooks/pre-commit-gate.sh

run_test "pre-commit-gate bloqueia --no-verify" 1 \
  env CLAUDE_TOOL_ARG_COMMAND='git commit --no-verify -m "docs: foo"' bash scripts/hooks/pre-commit-gate.sh

# Autor auto-*
git config --local user.name "auto-bot"
run_test "pre-commit-gate bloqueia autor auto-*" 1 \
  env CLAUDE_TOOL_ARG_COMMAND='git commit -m "docs: foo"' bash scripts/hooks/pre-commit-gate.sh
git config --local user.name "smoke-test-user"

# Mensagem proibida
run_test "pre-commit-gate bloqueia 'rodada N aprovado'" 1 \
  env CLAUDE_TOOL_ARG_COMMAND='git commit -m "rodada 3 APROVADO"' bash scripts/hooks/pre-commit-gate.sh

# --- Restaura config git original ---
if [ -n "$ORIG_GIT_NAME" ]; then
  git config --local user.name "$ORIG_GIT_NAME"
else
  git config --local --unset user.name 2>/dev/null || true
fi
if [ -n "$ORIG_GIT_EMAIL" ]; then
  git config --local user.email "$ORIG_GIT_EMAIL"
else
  git config --local --unset user.email 2>/dev/null || true
fi

# ---------- 9. pre-push-gate.sh ----------
echo "[9/12] pre-push-gate.sh"
BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'no-branch')"
case "$BRANCH" in
  main|master)
    # Em main/master: push direto só é permitido em bootstrap (sem upstream).
    # Como o smoke test não queremos bagunçar o upstream real, testamos apenas --no-verify.
    :
    ;;
  *)
    run_test "pre-push-gate permite push em branch não-main" 0 \
      env CLAUDE_TOOL_ARG_COMMAND="git push origin $BRANCH" bash scripts/hooks/pre-push-gate.sh
    ;;
esac

run_test "pre-push-gate bloqueia --no-verify" 1 \
  env CLAUDE_TOOL_ARG_COMMAND="git push --no-verify origin HEAD" bash scripts/hooks/pre-push-gate.sh

# Bootstrap exception: em branch temporária sem upstream, push com -u deve passar
# (simulamos criando uma branch descartável)
ORIG_BRANCH="$BRANCH"
git checkout -q -b smoke-bootstrap-test 2>/dev/null || true
run_test "pre-push-gate permite feature branch com -u (sem upstream)" 0 \
  env CLAUDE_TOOL_ARG_COMMAND="git push -u origin smoke-bootstrap-test" bash scripts/hooks/pre-push-gate.sh
git checkout -q "$ORIG_BRANCH" 2>/dev/null || true
git branch -q -D smoke-bootstrap-test 2>/dev/null || true

# ---------- 10. user-prompt-submit.sh ----------
echo "[10/12] user-prompt-submit.sh"
run_test "user-prompt-submit sempre retorna 0" 0 \
  bash scripts/hooks/user-prompt-submit.sh

# ---------- 11. collect-telemetry.sh ----------
echo "[11/12] collect-telemetry.sh"
run_test "collect-telemetry não falha" 0 \
  bash scripts/hooks/collect-telemetry.sh

# ---------- 12. stop-gate.sh ----------
echo "[12/12] stop-gate.sh"
run_test "stop-gate nunca bloqueia (warnings ok)" 0 \
  bash scripts/hooks/stop-gate.sh

# ---------- Relatório final ----------
echo ""
echo "=== RESULTADO ==="
echo "Testes rodados: $TESTS"
echo "Passou:         $PASS"
echo "Falhou:         $FAIL"

if [ "$FAIL" -eq 0 ]; then
  echo ""
  echo "[smoke-test OK] todos os hooks funcionam neste ambiente"
  exit 0
else
  echo ""
  echo "[smoke-test FAIL] $FAIL hook(s) com comportamento inesperado"
  echo "Corrija antes de prosseguir para Dia 1."
  exit 1
fi
