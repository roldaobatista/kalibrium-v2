#!/usr/bin/env bash
# relock-v3-flow.sh — fluxo interno chamado pelo .bat de relock v1.2.2
#
# Assume que ja estamos em /c/PROJETOS/saas/kalibrium-v2 (o .bat faz cd).
# Tarefas:
#   1. Backup do hook atual
#   2. Copiar staging v3 -> hook selado
#   3. Validar sintaxe bash
#   4. Rodar relock-harness.sh (pede RELOCK ao PM)
#   5. git add + git commit

set -e

echo ""
echo "=========================================================="
echo "  RELOCK HARNESS v1.2.2 — passo a passo"
echo "=========================================================="
echo ""

# 1. Backup
echo ">> [1/5] Fazendo backup do hook atual..."
BACKUP="scripts/hooks/verifier-sandbox.sh.bak-$(date +%Y%m%d-%H%M%S)"
cp scripts/hooks/verifier-sandbox.sh "$BACKUP"
echo "         Backup salvo em: $BACKUP"
echo ""

# 2. Aplicar patch v3
echo ">> [2/5] Aplicando patch v3 (nomes canonicos de agent + auto-detect sandbox)..."
cp scripts/staging/verifier-sandbox-v3.sh scripts/hooks/verifier-sandbox.sh
chmod +x scripts/hooks/verifier-sandbox.sh
echo "         Hook atualizado."
echo ""

# 3. Validar sintaxe
echo ">> [3/5] Validando sintaxe do novo hook..."
if ! bash -n scripts/hooks/verifier-sandbox.sh; then
  echo "ERRO: sintaxe invalida no novo hook. Restaurando backup."
  cp "$BACKUP" scripts/hooks/verifier-sandbox.sh
  exit 1
fi
echo "         Sintaxe OK."
echo ""

# 4. Rodar relock (interativo — pede RELOCK ao PM)
echo ">> [4/5] Rodando relock-harness.sh."
echo "         ATENCAO: o script vai pedir para voce digitar RELOCK (em maiusculas)."
echo "         Digite exatamente a palavra RELOCK e pressione ENTER."
echo ""
export KALIB_RELOCK_AUTHORIZED=1
bash scripts/relock-harness.sh
echo ""
echo "         Relock concluido."
echo ""

# 5. Git add + commit
echo ">> [5/5] Preparando commit..."
git add scripts/hooks/verifier-sandbox.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md 2>/dev/null || true

echo ""
echo "Files to commit:"
git status --short | head -20
echo ""

read -p "Confirma commit? (y/N): " confirm
if [ "$confirm" = "y" ] || [ "$confirm" = "Y" ]; then
  git commit -m "$(cat <<'EOF'
chore(harness): verifier-sandbox v3 + relock para protocolo v1.2.2

Hook agora aceita nomes canonicos v3 de agent (qa-expert, architecture-expert, etc.)
alem dos v2 legados. Sandbox dir e auto-detectado por presenca de *-input/ em cwd
(suporta os 8 sandboxes canonicos do protocolo).

Selos regenerados via relock-harness.sh (4 camadas de salvaguarda).
EOF
)"
  echo ""
  echo "Commit criado. Git log:"
  git log --oneline -3
else
  echo "Commit NAO executado. Alteracoes stageadas mas nao commitadas."
  echo "Para commitar depois: git commit -m 'sua mensagem'"
  echo "Para reverter: git reset HEAD scripts/hooks/verifier-sandbox.sh && cp $BACKUP scripts/hooks/verifier-sandbox.sh"
fi

echo ""
echo "=========================================================="
echo "  Terminado. Pode fechar esta janela e voltar ao Claude Code."
echo "=========================================================="
