# Como ativar o post-edit-gate

## Contexto

O `post-edit-gate.sh` é o hook mais importante da fábrica — ele roda format → lint → typecheck → testes após cada edição de arquivo. Atualmente está em `scripts/drafts/post-edit-gate.sh` porque depende da stack estar instalada (ADR-0001: Laravel 11 + Livewire 3 + PostgreSQL 16).

## Quando ativar

Após o primeiro `composer install` e/ou `npm install` do projeto — quando as ferramentas de qualidade (PHPStan, Pint, Prettier, ESLint) estiverem disponíveis.

## Procedimento de ativação

```bash
# 1. Saia do Claude Code

# 2. Em terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2

# 3. Copie o draft para hooks/
cp scripts/drafts/post-edit-gate.sh scripts/hooks/post-edit-gate.sh

# 4. Rode o relock (4 camadas de segurança)
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# Digite "RELOCK" quando solicitado

# 5. Commit
git add scripts/hooks/post-edit-gate.sh \
      scripts/hooks/MANIFEST.sha256 \
      .claude/settings.json.sha256 \
      docs/incidents/harness-relock-*.md
git commit -m "chore(harness): ativa post-edit-gate.sh após stack install"

# 6. Volte ao Claude Code — SessionStart validará automaticamente
```

## O que o post-edit-gate faz

| Etapa | Ferramenta | Bloqueante? |
|-------|-----------|-------------|
| 1. Format | Pint (PHP), Prettier (JS/TS) | Não (auto-corrige) |
| 2. Lint | PHPStan (PHP), ESLint (JS/TS) | Sim |
| 3. Type-check | PHPStan level=max, tsc | Sim |
| 4. Test mapping | Arquivo → teste correspondente | — |
| 5. Test run | Apenas teste afetado (P8) | Sim |

## Comportamento antes da ativação

O hook referenciado em `settings.json` aponta para `scripts/hooks/post-edit-gate.sh`. Se o arquivo não existir, o hook é ignorado silenciosamente pelo Claude Code. Isso é intencional — permite que o harness funcione durante as fases de descoberta e planejamento (onde não há código para validar).

## Ferramentas necessárias

Antes de ativar, confirme que estão instaladas:
- `vendor/bin/pint` (Laravel Pint — format PHP)
- `vendor/bin/phpstan` (PHPStan — lint/types PHP)
- `node_modules/.bin/prettier` (format JS/TS/CSS)
- `node_modules/.bin/eslint` (lint JS/TS) — se houver frontend JS
