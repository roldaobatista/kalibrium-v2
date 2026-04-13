# Drafts de Hooks Melhorados

## Contexto

Os hooks em `scripts/hooks/` são selados — o agente não pode editá-los. Versões melhoradas ficam em `scripts/drafts/` aguardando ativação manual pelo PM via `relock-harness.sh`.

## Drafts Disponíveis

| Draft | Hook Original | Melhoria |
|-------|--------------|----------|
| `user-prompt-submit.sh` | 374B placeholder | Detecção de prompt injection, comandos destrutivos, reminder contextual com slice ativo |
| `sealed-files-bash-lock.sh` | v1 | Adiciona detecção de xargs, bash -c, node -e, ruby -e, php -r, install, cat > |
| `pre-commit-gate.sh` | v1 | Mapeamento código→teste (app/→tests/), validação de AC-NNN nos testes |
| `collect-telemetry.sh` | v1 (erros silenciados) | Erros logados em stderr com contexto (não-bloqueante) |
| `hook-cache.sh` | NOVO | Módulo de cache SHA256 para evitar re-runs de lint/typecheck em arquivos inalterados |
| `post-edit-gate.sh` | (já existia) | Pipeline format→lint→type→test para Laravel stack |

## Procedimento de Ativação (por hook)

```bash
# 1. Saia do Claude Code

# 2. Em terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2

# 3. Copie o draft desejado:
cp scripts/drafts/user-prompt-submit.sh scripts/hooks/user-prompt-submit.sh
# Repita para cada draft que quiser ativar

# 4. Para o hook-cache.sh (módulo, não hook direto):
# Não precisa copiar para hooks/ — é sourced pelo post-edit-gate.sh
# Basta que post-edit-gate.sh tenha: source "$SCRIPT_DIR/../drafts/hook-cache.sh"

# 5. Rode o relock:
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# Digite "RELOCK" quando solicitado

# 6. Commit:
git add scripts/hooks/ scripts/hooks/MANIFEST.sha256 \
      .claude/settings.json.sha256 \
      docs/incidents/harness-relock-*.md
git commit -m "chore(harness): ativa hook drafts v2 — G-11/G-12/G-13/G-14/G-15"

# 7. Volte ao Claude Code
```

## Ativação Recomendada por Fase

| Fase do Projeto | Hooks para Ativar | Motivo |
|----------------|-------------------|--------|
| Antes do primeiro código | `user-prompt-submit.sh`, `sealed-files-bash-lock.sh` | Segurança preventiva |
| Após `composer install` | `post-edit-gate.sh`, `hook-cache.sh` | Quality gates de código |
| Após primeiros testes | `pre-commit-gate.sh`, `collect-telemetry.sh` | Cobertura e auditoria |
