# Incidente — APP_KEY Laravel exposta (GitGuardian)

**Data de detecção:** 2026-04-16
**Reportado por:** GitGuardian (alerta automático)
**Severidade:** média (ambiente de teste, sem dados reais)
**Repositório:** roldaobatista/kalibrium-v2 (público desde 2026-04-16)

## O que aconteceu

O arquivo `.env.testing` foi commitado no git no commit `bde36f4` (2026-04-14) contendo `APP_KEY=base64:0E5G/FqawQR0+QI9zFcgvbiIgd3xgBLZ0L1ABwwh19Q=`. O repo foi tornado público em 2026-04-16 (ER-003), expondo a chave no histórico.

## Impacto

- Chave de ambiente de **teste** (não produção)
- Projeto ainda não tem usuários reais
- Chave usada para encriptação de sessão/cookies em testes locais
- **Sem risco de dados reais comprometidos**

## Ações tomadas

1. `git rm --cached .env.testing` — arquivo removido do tracking
2. Nova APP_KEY gerada via `php artisan key:generate --show`
3. `.env.testing` local atualizado com nova chave (não commitado)
4. `.gitignore` já continha `.env.*` — arquivo não será re-commitado
5. Chave antiga (`0E5G/Fqa...`) considerada **comprometida e inutilizada**

## Ação pendente — limpeza de histórico

A chave antiga permanece no histórico do git (commit `bde36f4`). Para remover completamente, é necessário reescrever o histórico com `git filter-repo` ou BFG Repo-Cleaner. Isso invalida todos os SHAs de commits anteriores.

Opções:
- **(A)** Rodar BFG para remover `.env.testing` de todo o histórico — reescreve commits, quebra referências de PRs antigos
- **(B)** Aceitar que a chave antiga está no histórico — risco baixo pois é de ambiente de teste sem dados reais

**Decisão PM (2026-04-16):** opção B aceita. Chave de teste rotacionada, sem dados reais, risco baixo. Histórico não será reescrito.

## Prevenção

- `.env.testing` agora está no `.gitignore` (via regra `.env.*`)
- `.env.testing.example` (sem chave real) é o template versionado
