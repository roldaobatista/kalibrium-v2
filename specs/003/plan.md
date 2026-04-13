# Plano técnico do slice 003 — Pipeline CI (PHPStan + Pest + Pint + Rector + SBOM)

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/003/spec.md`

---

## Contexto de estado atual

O `ci.yml` já existe no repositório (criado no scaffold do slice 001). Ele contém 6 jobs funcionais: `harness`, `php-lint`, `php-static`, `php-test`, `js-lint` e `security`. Os arquivos `phpstan.neon` (nível 8, Larastan), `pint.json` (preset laravel) e `rector.php` (UP_TO_PHP_84) também existem e estão configurados. O `phpunit.xml` define as suites Unit e Feature com Pest 4.

**Consequência direta:** este slice não cria o workflow do zero — ele valida, ajusta e completa o que foi scaffoldado, garantindo que cada AC seja mecanicamente verificável via GitHub Actions real.

---

## Decisões arquiteturais

### D1: Estratégia do job Rector — `--dry-run` obrigatório no CI

**Opções consideradas:**
- **Opção A: Rector aplica mudanças em CI** (`rector process` sem flag) — prós: código sempre modernizado; contras: CI com efeito colateral de escrita no repositório é anti-padrão (requer commit de volta, complica histórico, não reversível sem revert manual).
- **Opção B: Rector em `--dry-run` no CI, com `exit 1` se houver sugestões** — prós: CI é read-only (detecta, não altera), desenvolvedor roda `rector process` localmente antes do push, comportamento previsível; contras: requer disciplina local (mitigado pelo pre-commit hook já existente em `scripts/hooks/`).
- **Opção C: Omitir Rector do CI** — prós: menos um job; contras: perde gate de refactoring automatizado, contradiz spec.

**Escolhida:** B

**Razão:** CI deve ser idempotente e sem efeitos colaterais de escrita. A opção A cria commits automáticos pelo runner (complexidade, histórico sujo). O pre-commit hook local (`pre-commit-gate.sh`) já bloqueia push com código que Rector modificaria, tornando o job CI uma segunda camada de proteção. Alinhado com P9 (zero bypass de gate) e com o padrão já adotado pelo Pint (`--test` em CI, nunca `--write`).

**Reversibilidade:** fácil — trocar a flag `--dry-run` para aplicar mudanças é uma linha no `ci.yml`.

---

### D2: Geração de SBOM — CycloneDX PHP Composer Plugin vs CycloneDX CLI binário

**Opções consideradas:**
- **Opção A: `cyclonedx/cyclonedx-php-composer` via `composer global require`** — instala na home do runner; prós: nativo PHP, acessa `composer.lock` diretamente; contras: instalação lenta (~30s), versão ^5 requer Composer 2.2+.
- **Opção B: CycloneDX CLI binário pré-compilado via download direto** — baixa o binário `cyclonedx-cli` da release do GitHub; prós: zero dependência de Composer global, rápido, versão pinada; contras: dependência de URL externa, binário Linux-only (adequado para ubuntu-latest).
- **Opção C: Manter comportamento atual do scaffold** — `composer global require ... || true` com warning em falha; contras: `|| true` mascara falha, AC-005 exige `sbom-php.xml` como artefato garantido, não opcional.

**Escolhida:** A (com ajuste: remover `|| true`, fixar versão, adicionar cache da home do Composer)

**Razão:** AC-005 exige que o artefato `sbom-php.xml` exista na aba Artifacts — é um critério objetivo, não opcional. O `|| true` atual no scaffold torna o AC não verificável mecanicamente (falha silenciosa). A opção A com cache da home do Composer resolve o problema de velocidade e é mais simples do que introduzir um binário externo (Opção B). A opção B permanece como fallback documentado no spec.

**Reversibilidade:** fácil — trocar o passo de instalação no `ci.yml` sem impacto em código de aplicação.

---

### D3: Escopo do `on: push` — branches cobertas

**Opções consideradas:**
- **Opção A: `push` apenas em `main`** — é o comportamento atual do scaffold; contras: AC-001/002/003 exigem que CI falhe em push para branch de feature, o que não ocorre sem cobertura de feature branches.
- **Opção B: `push` em `main` e em qualquer branch (`branches: ['**']`) + `pull_request` em `main`** — cobre todos os ACs; contras: gasta minutos de Actions em branches efêmeras.

**Escolhida:** B

**Razão:** Os ACs 001, 002 e 003 definem explicitamente "push para branch de feature" como gatilho de falha do CI. O comportamento atual do scaffold não cobre esses ACs. A mudança para `branches: ['**']` é necessária para verificabilidade mecânica dos ACs. O consumo extra de minutos é mitigado pelo `concurrency.cancel-in-progress: true` já presente (cancela run anterior da mesma branch ao novo push).

**Reversibilidade:** fácil — uma linha no `ci.yml`.

---

### D4: Job Rector — posição no grafo de dependências

**Opções consideradas:**
- **Opção A: Rector depende de `harness` (paralelo a lint e static-analysis)** — prós: falha rápida, sem esperar lint ou static; Rector e Pint analisam aspectos ortogonais.
- **Opção B: Rector depende de `php-lint` (sequencial)** — prós: garante código formatado antes de analisar refactoring; contras: aumenta tempo total do pipeline sem benefício real.

**Escolhida:** A

**Razão:** Rector e Pint analisam preocupações ortogonais (refactoring de modernização vs. formatação de estilo). Paralelizar reduz o tempo total do pipeline, crítico para repos com plano gratuito do GitHub (minutos limitados). Alinhado com o risco documentado no spec sobre consumo de minutos do Actions.

**Reversibilidade:** fácil — mudar `needs:` no `ci.yml`.

---

### D5: Cache de Composer — chave compartilhada entre jobs PHP

**Opções consideradas:**
- **Opção A: Chave idêntica `composer-${{ hashFiles('composer.lock') }}` em todos os jobs PHP** — o cache é compartilhado; prós: primeiro job popula, demais reutilizam sem re-download; contras: race condition teórica (GitHub Actions resolve internamente sem problema prático).
- **Opção B: Chave por job (`composer-lint-...`, `composer-static-...`)** — prós: sem race condition teórica; contras: cada job baixa vendor independentemente, multiplicando uso de armazenamento e tempo.

**Escolhida:** A

**Razão:** GitHub Actions resolve race conditions de cache automaticamente. A economia de tempo e armazenamento supera o risco teórico que não se materializa na prática. Padrão amplamente adotado pela comunidade Laravel em CI.

**Reversibilidade:** fácil.

---

## Mapeamento AC → arquivos

| AC | Descrição | Arquivos tocados | Como é verificado mecanicamente |
|---|---|---|---|
| AC-001 | Push com código mal formatado → job `lint` falha | `.github/workflows/ci.yml` (trigger ajustado), `pint.json` | `vendor/bin/pint --test` retorna exit 1 nos logs do Actions |
| AC-002 | Push com erro PHPStan nível 8 → job `static-analysis` falha | `.github/workflows/ci.yml`, `phpstan.neon` | `vendor/bin/phpstan analyse` retorna exit 1 |
| AC-003 | Push com teste falhando → job `tests` falha | `.github/workflows/ci.yml`, `phpunit.xml`, `tests/Feature/CiSmokeTest.php` | `vendor/bin/pest --ci` retorna exit 1 |
| AC-004 | PR para `main` com tudo verde → check verde no GitHub | `.github/workflows/ci.yml` (todos os jobs encadeados) | Status `success` via GitHub Checks API no PR |
| AC-005 | Artefato `sbom-php.xml` gerado na aba Artifacts | `.github/workflows/ci.yml` (job `security`, step CycloneDX corrigido) | `actions/upload-artifact@v4` com `sbom-php.xml` presente e download disponível |

---

## Novos arquivos

- `tests/Feature/CiSmokeTest.php` — baseline verde para AC-003; confirma que o ambiente de teste responde corretamente; o ac-to-test gerará testes red separados a partir deste baseline

## Arquivos modificados

- `.github/workflows/ci.yml` — 3 ajustes: (1) trigger `push: branches: ['**']`, (2) job `php-rector` adicionado, (3) step CycloneDX sem `|| true` + cache da home do Composer
- `rector.php` — adicionar `withSkip` para `storage/`, `bootstrap/cache/`, `database/migrations/`, `vendor/` (mitigação de falsos positivos em código gerado pelo Laravel)
- `README.md` — badge de CI (shield `github/workflow/status`)

## Schema / migrations

Nenhuma. O CI usa o banco configurado pelo slice 002; as variáveis de ambiente (`DB_CONNECTION: pgsql`, `DB_HOST: 127.0.0.1`, etc.) já estão corretas no `ci.yml`.

## APIs / contratos

Nenhum. O CI expõe apenas artefatos (SBOM) e status de check no GitHub, não endpoints de aplicação.

---

## Tasks numeradas

### TASK-001 — Corrigir trigger `push` para cobrir feature branches (AC-001, AC-002, AC-003)

**Arquivo:** `.github/workflows/ci.yml`

Alterar a seção `on:` de `branches: [main]` para `branches: ['**']` no bloco `push`. O bloco `pull_request` permanece em `branches: [main]`.

Sem esta mudança, AC-001/002/003 não são verificáveis mecanicamente (o CI não roda em push de feature branch).

---

### TASK-002 — Adicionar job `php-rector` ao workflow

**Arquivo:** `.github/workflows/ci.yml`

Adicionar job com `needs: harness` (paralelo a `php-lint` e `php-static`), com as seguintes características:
- `runs-on: ubuntu-latest`, `timeout-minutes: 10`
- Guard `if [ -f composer.json ]` idêntico aos outros jobs PHP
- Setup PHP 8.4 + Composer v2 + cache de vendor
- Step final: `vendor/bin/rector process --dry-run --no-progress`

O `--dry-run` garante que o job detecta sem alterar arquivos (D1).

---

### TASK-003 — Tornar geração de SBOM obrigatória (AC-005)

**Arquivo:** `.github/workflows/ci.yml` — job `security`

Substituir o step atual de geração CycloneDX (que usa `|| true`) por:
1. Step de cache da home do Composer (`~/.composer/vendor`, chave `composer-global-cyclonedx-v5`)
2. Step de instalação: `composer global require cyclonedx/cyclonedx-php-composer:^5 --no-interaction` (sem `|| true`)
3. Step de geração: `~/.composer/vendor/bin/cyclonedx-php-composer --output-format XML --output-file sbom-php.xml`

O step `upload-artifact` existente já cobre AC-005 desde que `sbom-php.xml` seja gerado sem falha silenciosa.

---

### TASK-004 — Adicionar exclusões ao `rector.php`

**Arquivo:** `rector.php`

Adicionar chamada `->withSkip([...])` no `RectorConfig::configure()` encadeado, excluindo:
- `__DIR__.'/storage'`
- `__DIR__.'/bootstrap/cache'`
- `__DIR__.'/vendor'`
- `__DIR__.'/database/migrations'`

Sem esta exclusão, Rector pode sugerir mudanças em arquivos de bootstrap gerados automaticamente pelo Laravel, causando falsos positivos no job `php-rector` (risco documentado no spec).

---

### TASK-005 — Badge de CI no `README.md`

**Arquivo:** `README.md`

Adicionar ao topo do arquivo o badge:
```
[![CI](https://github.com/roldaobatista/kalibrium-v2/actions/workflows/ci.yml/badge.svg)](https://github.com/roldaobatista/kalibrium-v2/actions/workflows/ci.yml)
```

Cobre AC-004 visualmente e confirma que o workflow está referenciado corretamente.

---

### TASK-006 — Teste smoke de CI para AC-003

**Arquivo:** `tests/Feature/CiSmokeTest.php`

Teste Pest mínimo que confirma ambiente configurado:

```php
<?php

declare(strict_types=1);

test('ambiente de teste está configurado', function (): void {
    expect(app()->environment())->toBe('testing');
});
```

Este é o baseline verde. O sub-agent `ac-to-test` gerará testes red separados (ex.: um teste que falha intencionalmente para validar que o job `tests` rejeita o push em AC-003).

---

## Riscos e mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| `postgres:18` indisponível como service no GitHub Actions | Baixa (imagem existe no Docker Hub) | Alto (AC-003 quebra) | Health check com retries já presente no scaffold; sem ação adicional |
| `cyclonedx/cyclonedx-php-composer:^5` incompatível com Composer 2.x do runner | Média | Médio (AC-005 quebra) | Cache da home do Composer; fallback para CycloneDX CLI binário (Opção B de D2) se falhar |
| Rector gera falsos positivos em código gerado do Laravel | Alta sem exclusão | Médio (pipeline bloqueado sem razão) | TASK-004 adiciona `withSkip` para `storage/`, `bootstrap/cache/`, `database/migrations/` |
| Minutos do GitHub Actions esgotados (plano gratuito) | Baixa no curto prazo | Médio | `concurrency.cancel-in-progress: true` já presente; jobs paralelos (D4); cache compartilhado (D5) |
| Push em `branches: ['**']` dispara CI em branches de dependabot/renovate | Baixa | Baixo | `concurrency` cancela runs anteriores da mesma branch; sem impacto em correctness |

---

## Dependências de outros slices

| Slice | Dependência | Natureza |
|---|---|---|
| slice-001 | Scaffold Laravel 13 com `composer.json`, `phpstan.neon`, `pint.json`, `rector.php`, `phpunit.xml`, `ci.yml` inicial | Obrigatória — mergeada |
| slice-002 | PostgreSQL 18 como service no job `php-test` (variáveis `DB_*` já configuradas no `ci.yml`) | Obrigatória — mergeada |

---

## Fora de escopo deste plano (confirmando spec)

- Deploy para staging/produção (E01-S04)
- Scan de vulnerabilidades CVE via Snyk ou Dependabot alerts
- Matrix multi-versão de PHP (ex.: 8.3 + 8.4)
- Notificações de falha de CI por Slack ou e-mail
- Self-hosted runners
- Testes E2E de navegador (Pest Browser / Playwright)
- Análise de cobertura com threshold mínimo obrigatório (será definido em ADR-0007)
