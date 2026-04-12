#!/usr/bin/env bash
# relock-and-commit.sh — wrapper 1-click em cima do relock-harness.sh.
#
# B-020 do guide-backlog. Elimina o atrito de 5+ passos que o PM paga
# cada vez que um hook selado ou settings.json precisa ser alterado.
#
# O que faz:
#   1. Valida que há mudança pendente em arquivos selados (senão aborta)
#   2. Pergunta ao PM uma linha descrevendo a mudança
#   3. Define KALIB_RELOCK_AUTHORIZED=1 (camada 1 — conveniência)
#   4. Invoca scripts/relock-harness.sh (camadas 2+3+4 permanecem intactas)
#   5. Se relock OK, stage cirúrgico + commit com mensagem "chore(harness): <linha>"
#
# O que NÃO faz (preserva enforcement):
#   - NÃO faz push (PM aprova manual depois)
#   - NÃO burla camada 2 (TTY): este wrapper precisa rodar em terminal real;
#     se for invocado pela ferramenta Bash do Claude Code, falha em [ -t 0 ]
#   - NÃO burla camada 3: relock-harness.sh continua pedindo "RELOCK" digitado
#   - NÃO bypassa pre-commit-gate: o commit passa pelos gates normais
#
# Uso (PM, em terminal externo — Git Bash ou equivalente):
#   bash scripts/relock-and-commit.sh
#
# Ou via duplo-clique em tools/relock.bat (Windows).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

say()  { echo "[relock-and-commit] $*"; }
fail() { echo "[relock-and-commit FAIL] $*" >&2; exit 1; }

# ----------------------------------------------------------------------
# Passo 0: precondições de ambiente
# ----------------------------------------------------------------------

# Guarda redundante — o relock-harness.sh já vai verificar TTY, mas é
# educado falhar cedo com mensagem clara em vez de deixar o relock abortar.
if [ ! -t 0 ]; then
  fail "Este script precisa rodar em terminal interativo (TTY). Abra Git Bash diretamente; não invoque via Claude Code."
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  fail "Não estou dentro de um repositório git."
fi

# ----------------------------------------------------------------------
# Passo 1: verifica se há mudança pendente em arquivos selados
# ----------------------------------------------------------------------
SEALED_PATHS=(
  ".claude/settings.json"
  ".claude/allowed-git-identities.txt"
  ".claude/git-identity-baseline"
  "scripts/hooks"
)

CHANGED=""
for p in "${SEALED_PATHS[@]}"; do
  if ! git diff --quiet -- "$p" 2>/dev/null || ! git diff --cached --quiet -- "$p" 2>/dev/null; then
    CHANGED="$CHANGED $p"
  fi
done

# Arquivos untracked em scripts/hooks/ também contam (novo hook)
UNTRACKED_HOOKS="$(git ls-files --others --exclude-standard scripts/hooks/ 2>/dev/null || true)"
if [ -n "$UNTRACKED_HOOKS" ]; then
  CHANGED="$CHANGED scripts/hooks(untracked)"
fi

if [ -z "$CHANGED" ]; then
  say "Nada pra relock. Working tree está limpo em todos os caminhos selados."
  say "Dica: edite o arquivo que você quer mudar (settings.json ou hook) ANTES de rodar este script."
  exit 0
fi

say "Mudança detectada em:"
for p in $CHANGED; do
  echo "    - $p"
done
echo

# ----------------------------------------------------------------------
# Passo 2: pede descrição curta ao PM
# ----------------------------------------------------------------------
echo "Descreva em UMA LINHA o que mudou (vira a mensagem do commit)."
echo "Exemplo: \"atualiza post-edit-gate com comandos Laravel\""
echo -n "> "
read -r DESC

if [ -z "$DESC" ]; then
  fail "Descrição vazia. Abortando (nada foi feito)."
fi

# Sanitiza aspas pra não quebrar a mensagem do commit
DESC_SAFE="$(echo "$DESC" | tr -d '\r' | sed 's/"/\\"/g')"

# ----------------------------------------------------------------------
# Passo 3: chama relock-harness.sh
# ----------------------------------------------------------------------
say "invocando relock-harness.sh (vai pedir 'RELOCK' — digite e confirme)..."
echo

# Exporta autorização (camada 1). Camadas 2 (TTY) e 3 (digitação RELOCK)
# continuam sendo aplicadas PELO relock-harness.sh, não por aqui.
export KALIB_RELOCK_AUTHORIZED=1

if ! bash "$SCRIPT_DIR/relock-harness.sh"; then
  fail "relock-harness.sh falhou ou foi abortado. Nenhum commit foi criado."
fi

echo
say "relock-harness.sh OK — selos regenerados."

# ----------------------------------------------------------------------
# Passo 4: stage cirúrgico + commit
# ----------------------------------------------------------------------
say "staging arquivos afetados..."

# Stage dos arquivos selados que mudaram + do incidente recém criado + dos selos regenerados
git add -- ".claude/settings.json" 2>/dev/null || true
git add -- ".claude/settings.json.sha256" 2>/dev/null || true
git add -- ".claude/allowed-git-identities.txt" 2>/dev/null || true
git add -- ".claude/git-identity-baseline" 2>/dev/null || true
git add -- "scripts/hooks/" 2>/dev/null || true
git add -- "docs/incidents/harness-relock-*.md" 2>/dev/null || true

# Confirma que há algo staged
if git diff --cached --quiet; then
  fail "Nada staged após relock. Algo deu errado — investigue git status manualmente."
fi

say "mostrando o que vai ser commitado:"
git diff --cached --stat
echo

# ----------------------------------------------------------------------
# Passo 5: commit (passa pelos gates normais — pre-commit-gate.sh)
# ----------------------------------------------------------------------
COMMIT_MSG="chore(harness): $DESC_SAFE"

say "commit: \"$COMMIT_MSG\""
if ! git commit -m "$COMMIT_MSG"; then
  fail "git commit falhou (provavelmente pre-commit-gate bloqueou). Corrija os erros reportados e rode 'git commit' manualmente."
fi

echo
say "✓ Tudo pronto. Commit criado localmente."
say "Próximo passo (você decide quando):"
say "  git push origin $(git rev-parse --abbrev-ref HEAD)"
