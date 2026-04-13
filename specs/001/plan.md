# Plano técnico do slice 001 — Scaffold Laravel 13 com dependências core

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/001/spec.md`

---

## Decisões arquiteturais

### D1: Inicialização do projeto via `composer create-project` vs `laravel new`

**Opções consideradas:**
- **Opção A: `composer create-project laravel/laravel . --prefer-dist`** — instala na raiz do repositório existente, sem wizard interativo, reprodutível em ambiente automatizado. Prós: não requer `laravel/installer` global, funciona em Git Bash/Windows sem TTY interativa, compatível com repositório já existente. Contras: versão depende do Packagist no momento da execução; requer atenção para não sobrescrever arquivos do harness.
- **Opção B: `laravel new kalibrium`** — usa o Laravel installer com wizard interativo. Prós: permite selecionar starter kit via prompt. Contras: requer installer global, prompts interativos quebram em execução automatizada por agente, cria subdiretório em vez de usar a raiz do repositório existente.

**Escolhida:** A

**Razão:** O repositório já existe com harness de qualidade na raiz (`scripts/hooks/`, `.claude/`, CI workflow). A opção A instala direto na raiz sem criar subdiretório e não exige TTY interativa — essencial dado o ambiente Windows/Git Bash e execução por agente. A versão do Laravel 13 é fixada via constraint `"laravel/framework": "^13.0"`.

**Reversibilidade:** fácil — é o scaffold inicial, nenhum código de domínio existe ainda.

---

### D2: Estrutura de diretórios customizada (`app/Domain/`, `app/Infrastructure/`, `app/Http/`)

**Opções consideradas:**
- **Opção A: Estrutura padrão Laravel** — `app/Models/`, `app/Http/Controllers/`, etc. Prós: sem configuração extra, compatível com todos os pacotes. Contras: não reflete separação de domínio/infraestrutura; dificulta crescimento com múltiplos bounded contexts exigidos pelo PRD.
- **Opção B: Estrutura customizada com `app/Domain/`, `app/Infrastructure/`, `app/Http/`** — alinhada com arquitetura hexagonal/DDD. Prós: bounded contexts isolados desde o início, facilita multi-tenancy e separação de responsabilidades à medida que o PRD cresce. Contras: requer registro de namespaces adicionais no `composer.json` (PSR-4 autoload).

**Escolhida:** B

**Razão:** O PRD define 5 tipos de cliente com regras distintas, multi-tenancy e múltiplos domínios (metrológico, fiscal, financeiro, RH). Começar com `app/Domain/` e `app/Infrastructure/` evita refatoração cara nos primeiros slices de negócio. O custo de configuração no scaffold é mínimo (2 entradas PSR-4 adicionais).

**Reversibilidade:** média — reverter após os primeiros slices de domínio exigiria mover classes e atualizar importações em múltiplos arquivos.

---

### D3: PHPStan configurado no nível 8 (máximo) via Larastan

**Opções consideradas:**
- **Opção A: Nível 8** — máxima cobertura de análise estática. Prós: detecta erros de tipo cedo; alinhado com ADR-0001 que especifica "nível 8 (máximo rigor)". Contras: scaffold padrão do Laravel 13 pode gerar alguns falsos-positivos que precisam de `ignoreErrors` pontual.
- **Opção B: Nível 5 (intermediário)** — menos ruído inicial. Prós: nenhum ajuste necessário no scaffold. Contras: contradiz ADR-0001 diretamente, violando R10.

**Escolhida:** A

**Razão:** ADR-0001 especifica PHPStan nível 8 com Larastan. O scaffold idiomático do Laravel 13 passa nível 8 sem baseline; qualquer `ignoreErrors` necessário será mínimo e documentado no `phpstan.neon`. Contornar ADR-0001 violaria R10.

**Reversibilidade:** fácil — mudança de número em `phpstan.neon`.

---

### D4: Estratégia de instalação do `stancl/tenancy`

**Opções consideradas:**
- **Opção A: Versão estável compatível com Laravel 13** — instalar `stancl/tenancy ^3.x` se houver release com suporte declarado. Prós: estável, sem risco de breaking changes. Contras: pode não existir release compatível no momento da execução.
- **Opção B: Branch `main` via VCS** — instalar a versão mais recente mesmo sem release formal. Prós: garante compatibilidade com Laravel 13 mesmo sem tag. Contras: instável; requer entrada `repositories` no `composer.json`; requer registro em ADR conforme spec §Riscos.
- **Opção C: Adiar para slice 002** — instalar apenas pacotes necessários para os ACs deste slice. Prós: elimina risco de incompatibilidade. Contras: o spec inclui `stancl/tenancy` explicitamente no escopo de `composer.json` deste slice.

**Escolhida:** A com fallback documentado para B

**Razão:** O spec exige `stancl/tenancy` no `composer.json` deste slice. A tentativa primária é a versão estável. Se não houver release compatível com Laravel 13 no momento da execução, o implementer usa branch `main` via VCS e abre ADR documentando a decisão — conforme indicado explicitamente no spec §Riscos. A decisão de qual branch usar é de runtime, não do architect.

**Reversibilidade:** média — trocar de branch para release estável é `composer update`, mas requer reteste dos ACs.

---

### D5: Configuração do `rector.php` — escopo mínimo para PHP 8.4

**Opções consideradas:**
- **Opção A: Apenas `LevelSetList::UP_TO_PHP_84`** — aplica somente upgrades de sintaxe PHP 8.4. Prós: mínimo, sem surpresas; não altera código do scaffold recém-gerado. Contras: não cobre dead code nem regras de qualidade.
- **Opção B: `LevelSetList::UP_TO_PHP_84` + `SetList::CODE_QUALITY` + `SetList::DEAD_CODE`** — conjunto mais amplo. Prós: qualidade mais alta desde o início. Contras: pode alterar código do scaffold e criar divergência com Pint antes do primeiro commit, arriscando AC-004.

**Escolhida:** A

**Razão:** Este slice valida que o Rector está instalado e configurado para PHP 8.4 — o spec não requer que o Rector transforme código aqui. O conjunto mínimo evita efeitos colaterais no scaffold que poderiam interferir com AC-003 (PHPStan) ou AC-004 (Pint). SetLists adicionais serão adicionados quando houver código de domínio a transformar.

**Reversibilidade:** fácil — adicionar/remover SetLists no `rector.php`.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `artisan`, `bootstrap/app.php`, `routes/web.php`, `public/index.php` | `tests/Feature/ScaffoldTest.php` — assert HTTP 200 na rota `/` |
| AC-002 | `composer.json` (script `test`), `phpunit.xml` ou `pest.php`, `tests/Pest.php` | `tests/Feature/ScaffoldTest.php` — `composer test` retorna exit 0 |
| AC-003 | `phpstan.neon`, `composer.json` (require-dev `nunomaduro/larastan`) | `tests/Feature/ScaffoldTest.php` — `phpstan analyse --level=8` retorna exit 0 |
| AC-004 | `pint.json`, `composer.json` (require-dev `laravel/pint`) | `tests/Feature/ScaffoldTest.php` — `pint --test` retorna exit 0 |
| AC-005 | `.env.example` | `tests/Feature/ScaffoldTest.php` — assert presença de todas as variáveis obrigatórias |

---

## Novos arquivos

- `composer.json` — projeto Laravel 13 com dependências PHP conforme spec; inclui namespaces PSR-4 para `App\Domain\` e `App\Infrastructure\`
- `composer.lock` — lockfile gerado após `composer install`
- `package.json` — dependências frontend base: vite, tailwindcss, alpinejs; livewire/livewire via Composer (não npm)
- `package-lock.json` — lockfile npm
- `phpstan.neon` — configuração PHPStan nível 8 com extensão Larastan
- `rector.php` — configuração Rector com `LevelSetList::UP_TO_PHP_84`
- `pint.json` — preset PSR-12 + opinionado Laravel; `exclude` para `scripts/` e `.claude/`
- `.env.example` — todas as variáveis obrigatórias: `APP_*`, `DB_*`, `REDIS_*`, `QUEUE_CONNECTION`, `MAIL_*`, `HORIZON_*`
- `.gitignore` — mescla do `.gitignore` Laravel com entradas existentes do harness
- `app/Domain/.gitkeep` — reserva o diretório de domínio (vazio neste slice)
- `app/Infrastructure/.gitkeep` — reserva o diretório de infraestrutura (vazio neste slice)
- `vite.config.js` — configuração base com entry point `resources/js/app.js`
- `tests/Feature/ScaffoldTest.php` — testes Pest cobrindo AC-001 a AC-005

## Arquivos modificados

- `composer.json` — adição de entradas PSR-4 `App\\Domain\\` → `app/Domain/` e `App\\Infrastructure\\` → `app/Infrastructure/` no bloco `autoload`
- `bootstrap/app.php` — verificar compatibilidade com estrutura customizada; Laravel 13 usa este arquivo como ponto de entrada único (sem `app/Http/Kernel.php` separado)
- `.gitignore` — mesclar entradas geradas pelo Laravel com as entradas pré-existentes do harness (`scripts/hooks/`, `.claude/`, `.claude/telemetry/`)

## Schema / migrations

Nenhuma migration neste slice. Configuração de banco de dados é escopo do slice 002 (E01-S02).

## APIs / contratos

Nenhuma API pública neste slice. A rota `/` é a rota de boas-vindas padrão do Laravel (AC-001), sem contrato a declarar.

---

## Tarefas de execução

| # | Tarefa | ACs atendidos |
|---|---|---|
| T01 | Executar `composer create-project laravel/laravel . --prefer-dist` na raiz, preservando arquivos existentes do harness | AC-001, AC-002 |
| T02 | Adicionar entradas PSR-4 `App\\Domain\\` e `App\\Infrastructure\\` ao `composer.json`; criar `app/Domain/.gitkeep` e `app/Infrastructure/.gitkeep`; rodar `composer dump-autoload` | — |
| T03 | Instalar require-dev: `composer require --dev nunomaduro/larastan pestphp/pest laravel/pint rector/rector` | AC-003, AC-004 |
| T04 | Instalar require: `composer require owen-it/laravel-auditing stancl/tenancy` (verificar compatibilidade Laravel 13; registrar resultado; usar branch `main` via VCS + abrir ADR se necessário) | — |
| T05 | Criar `phpstan.neon` com nível 8 e extensão Larastan; executar `./vendor/bin/phpstan analyse --level=8`; adicionar `ignoreErrors` pontuais se necessário; confirmar exit 0 | AC-003 |
| T06 | Criar `pint.json` com preset Laravel + PSR-12 + `exclude` para `scripts/` e `.claude/`; executar `./vendor/bin/pint` para formatar scaffold; confirmar `./vendor/bin/pint --test` exit 0 | AC-004 |
| T07 | Criar `rector.php` com `LevelSetList::UP_TO_PHP_84`; executar `./vendor/bin/rector --dry-run` para verificar sem alterar arquivos | — |
| T08 | Configurar script `"test": "pest"` no `composer.json`; confirmar `composer test` exit 0 com suite vazia | AC-002 |
| T09 | Instalar dependências frontend: `npm install vite tailwindcss alpinejs`; criar `vite.config.js` base | — |
| T10 | Ajustar `.env.example` com todas as variáveis obrigatórias (`APP_*`, `DB_*`, `REDIS_*`, `QUEUE_CONNECTION`, `MAIL_*`, `HORIZON_*`) | AC-005 |
| T11 | Mesclar `.gitignore` gerado pelo Laravel com entradas pré-existentes do harness | — |
| T12 | Escrever `tests/Feature/ScaffoldTest.php` com testes Pest para AC-001 a AC-005 | AC-001 a AC-005 |
| T13 | Executar todos os ACs em sequência e coletar evidências (outputs + exit codes) conforme spec §Evidência necessária | AC-001 a AC-005 |

---

## Riscos e mitigações

- **`stancl/tenancy` sem release estável para Laravel 13** → tentar versão estável primeiro; se incompatível, usar branch `main` via entrada `repositories` no `composer.json` e abrir ADR documentando a decisão (spec §Riscos prevê isso explicitamente).
- **Laravel 13 com novo padrão de bootstrapping** → Laravel 13 usa `bootstrap/app.php` como arquivo central (sem `app/Http/Kernel.php` separado); o implementer deve verificar o upgrade guide e adaptar a estrutura de diretórios conforme o novo padrão antes de T02.
- **Colisão de `.gitignore`** → o repositório já possui `.gitignore` do harness; o implementer deve mesclar manualmente em T11 em vez de sobrescrever, preservando entradas existentes (`scripts/hooks/`, `.claude/`, `.claude/telemetry/`).
- **Tailwind CSS 4 com API de configuração diferente** → Tailwind 4 usa `@import` CSS em vez de `tailwind.config.js` e requer `@tailwindcss/vite` como plugin; a instalação base em T09 não deve criar `tailwind.config.js` (configuração completa é escopo de E01-S06).
- **Pint reformatando arquivos do harness** → `pint.json` deve incluir `"exclude"` para `scripts/` e `.claude/` antes de qualquer execução do Pint (T06).

---

## Dependências de outros slices

- Nenhuma dependência de slices anteriores — este é o slice 001, o primeiro slice real do projeto.
- **Slice 002 (E01-S02)** depende deste slice: configuração de banco de dados e migrations pressupõem o projeto Laravel inicializado.

---

## Fora de escopo deste plano (confirmando spec)

- Configuração de banco de dados, migrations ou conexão PostgreSQL (slice 002 / E01-S02)
- Configuração completa de Tailwind CSS, Livewire e Alpine.js com componentes (E01-S06)
- Pipeline CI no GitHub Actions (E01-S03)
- Qualquer feature de negócio, autenticação ou modelo de domínio
- Configuração de Redis, Horizon ou filas
- Configuração de observabilidade (logs estruturados, OpenTelemetry)
- Migrations de domínio
