# Kalibrium V2 — Instruções do projeto

Plataforma SaaS para laboratórios de calibração. Multi-tenant (stancl/tenancy), Laravel 13, PHP 8.4, Livewire, Pest.

## Sobre o usuário

Roldão **não programa**. É dono/idealizador do produto. Comunicar em **português (Brasil)**, sem jargão técnico cru. Reportar erro pelo efeito visível ("a tela do financeiro não carrega"), não pelo stack trace.

## Quatro princípios

1. **Verificar antes de afirmar.** Não dizer "pronto" / "corrigido" sem rodar e mostrar a saída. Evidência antes da afirmação.
2. **Causa raiz, nunca sintoma.** Teste falhou = bug no código. Corrigir o código. Nunca mascarar com `skip`, `@ts-ignore`, `eslint-disable`, assertion relaxada, regra desligada, `|| true`, `--no-verify`.
3. **Validar antes de salvar.** Antes de `git commit`, rodar lint/types/testes proporcionais ao escopo (`composer pint`, `composer test`, `npm run lint`). Se falhar, corrigir primeiro.
4. **Confirmar antes de destruir.** Pedir confirmação antes de: `git reset --hard`, `git push --force`, `git branch -D`, `rm -rf`, `DROP TABLE`/`TRUNCATE`, deletar dados de produção, deletar arquivos não-versionados do working tree. Push fast-forward não entra aqui.

## Fluxo padrão

- Trabalhar direto em `main`. Sem PR/branch nova/code review interno, salvo pedido explícito.
- Commits atômicos: um propósito por commit. Stage seletivo por arquivo — nunca `git add .` cego com outras frentes dirty.
- Pró-ativo: identificou bug/gap → resolve. Não perguntar "quer que eu corrija?". Reportar "fiz X, resolvi Y".

## Stack e comandos

| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 13, PHP 8.4 |
| Multi-tenancy | stancl/tenancy v3 |
| UI server | Livewire |
| Frontend | Vite + PWA (Capacitor para mobile) |
| Auth | Fortify + Sanctum |
| Filas | Horizon + Redis (predis) |
| Testes | Pest 4 |
| Lint PHP | Pint |
| Análise estática | Larastan |

```
composer setup           # primeira vez
composer dev             # serve + queue + vite + pail (4 processos)
composer test            # config:clear + artisan test
composer pint            # formatação PHP
vendor/bin/phpstan       # análise estática
npm run dev / build      # frontend
```

## Estrutura

- `app/` Laravel (Console, Domain, Http, Models, Policies, Providers, Infrastructure)
- `tests/` Pest tests
- `database/` migrations + seeders + factories
- `routes/`, `config/`, `resources/`, `public/` Laravel padrão
- `docs/` documentação de produto (architecture, design, security, compliance, product, frontend, ops, finance)
- `scripts/` utilitários (`bootstrap-bash-php.sh`, `deploy.sh`, `pwa/`)
- `infra/` Docker/deploy
- `android/` build Capacitor

## Histórico

Este projeto teve uma fábrica de software (harness) extensa que foi removida em 2026-05-02 (commit "chore: reset harness — fresh start"). O conteúdo antigo está preservado:

- Tag git: `pre-harness-reset-2026-05-02`
- Branch git: `archive/harness-v3-completo`

Para reverter o reset: `git checkout pre-harness-reset-2026-05-02`.
