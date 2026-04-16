# Plano tecnico do slice 011 — E02-S08: Testes estruturais de isolamento entre tenants

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/011/spec.md`

---

## Decisoes arquiteturais

### D1: TestCase base com fixture compartilhada de 2 tenants em vez de recriar tenants por teste

**Opcoes consideradas:**
- **Opcao A:** criar `TenantIsolationTestCase` que inicializa 2 tenants (A e B) com factories no `setUpBeforeClass` e usa transacoes por teste para reset incremental — pros: fixture pesada criada 1x por suite, cada teste e atomico via rollback, tempo total < 60s viavel; contras: estado compartilhado exige cuidado com dados mutantes entre testes.
- **Opcao B:** recriar os 2 tenants em cada teste via `RefreshDatabase` completo — pros: isolamento perfeito entre testes; contras: overhead de criacao de schema x N testes, facilmente ultrapassa 60s (AC-006), cresce combinatorialmente com novos models (viola AC-015).

**Escolhida:** Opcao A.

**Razao:** AC-006 (< 60s) e AC-015 (crescimento linear por model adicional) sao constraints mensuraveis que a Opcao B viola em projetos com mais de 5 models. A fixture compartilhada com rollback por transacao e o padrao recomendado pelo stancl/tenancy para suites de isolamento e consistente com o uso de `DatabaseTransactions` nos slices E02 anteriores.

**Reversibilidade:** media (mudar para Opcao B exige refatorar todos os testes da suite).

**ADR:** nao requer ADR novo.

---

### D2: Lista de models sensiveis via `config/tenancy.php[sensitive_models]` (config-driven) em vez de introspeccao automatica de classes

**Opcoes consideradas:**
- **Opcao A:** manter um array explícito `sensitive_models` em `config/tenancy.php` que serve como fonte de verdade; o teste itera sobre ele e falha se o model nao tiver `BelongsToTenant` — pros: determinístico, auditável, qualquer dev ve quais models estao no escopo sem ler codigo; contras: dev pode esquecer de adicionar novo model ao array.
- **Opcao B:** introspeccao automatica via `get_declared_classes()` ou scan de `app/Models/` para detectar models com `tenant_id` — pros: zero esquecimento de novo model; contras: requer full Laravel boot com autoload completo, indeterminístico em tempo de CI, pode incluir models de pacotes externos, viola AC-007 (README simples para dev junior).
- **Opcao C:** scan automatico gera warning, config explícita e o gate — pros: melhor dos dois mundos; contras: complexidade dupla desnecessaria no MVP.

**Escolhida:** Opcao A.

**Razao:** Determinismo e auditabilidade superam o risco de esquecimento, que e mitigado por AC-008 (teste falha explicitamente se model esta na config mas sem trait). O README (AC-007) fica simples: "adicione o model ao array, rode a suite". Config-driven ja e o padrao do stancl/tenancy para `tenancy.php`.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D3: Config de jobs em arquivo separado `config/tenancy-jobs.php` em vez de ampliar `config/tenancy.php`

**Opcoes consideradas:**
- **Opcao A:** criar `config/tenancy-jobs.php` com array `tenant_aware_jobs` listando jobs que propagam tenant context — pros: separacao de responsabilidade (models vs jobs), arquivo menor por dominio, alinha com o padrao do stancl/tenancy que ja usa arquivos de config separados por responsabilidade; contras: um arquivo a mais.
- **Opcao B:** adicionar chave `tenant_jobs` dentro de `config/tenancy.php` — pros: um arquivo so; contras: mistura dois dominios de configuracao em um arquivo ja denso, dificulta leitura e vai contra o padrao do pacote.

**Escolhida:** Opcao A.

**Razao:** Separacao de responsabilidade e padrao do stancl/tenancy. O arquivo e criado neste slice com a lista inicial de jobs existentes no E02 e cresce conforme novos jobs sao adicionados em E03+.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

### D4: Job CI separado `tenant-isolation` em `gates.yml` com paths filter

**Opcoes consideradas:**
- **Opcao A:** adicionar job `tenant-isolation` dedicado em `.github/workflows/gates.yml` com `paths` filter para `app/Models/**`, `app/Http/**`, `app/Livewire/**`, `app/Jobs/**` e `tests/tenant-isolation/**` — pros: atende AC-005 (bloqueia merge em toque em codigo sensivel) e AC-014 (pula em PRs so de docs); contras: job adicional consome cota de CI.
- **Opcao B:** adicionar a suite de isolamento como step dentro do job de testes existente — pros: sem job extra; contras: suite roda em todo PR independente do que foi tocado (viola AC-014), mais difícil de sinalizar especificamente qual gate falhou.
- **Opcao C:** rodar suite apenas localmente ate a cota de CI resolver — pros: zero custo CI; contras: viola AC-005 completamente como comportamento alvo.

**Escolhida:** Opcao A, com `continue-on-error: true` enquanto a cota CI esta esgotada (divida tecnica ativa documentada no spec §Riscos conhecidos). Quando a cota resolver, o flag e removido e o job passa a bloquear merge mecanicamente.

**Razao:** AC-005 define o comportamento alvo como blocking. A divida transitoria fica visível no workflow como comentario, nao altera o AC nem o plano estrutural. O paths filter atende AC-014 sem custo adicional.

**Reversibilidade:** facil (remover `continue-on-error` e 1 linha de diff).

**ADR:** nao requer ADR novo.

---

### D5: Assertivas de isolamento via data providers Pest em vez de loops inline

**Opcoes consideradas:**
- **Opcao A:** data providers Pest (`->with([...])`) para iterar sobre models, rotas e metodos de query — pros: cada combinacao vira um caso de teste nomeado no output, falha aponta exatamente qual model + metodo vazou, crescimento linear (AC-015 atendido automaticamente), idiomatico em Pest 4; contras: syntax de data provider pode ser menos familiar para dev junior mas e documentada no README.
- **Opcao B:** loop `foreach` dentro de um unico teste — pros: mais simples de ler; contras: uma falha encobre as demais, output opaco, impossibilita identificar qual model especifico vazou.

**Escolhida:** Opcao A.

**Razao:** P2 exige que cada AC vire teste executavel identificavel. Data providers garantem que cada (model, metodo) seja um caso individualizado com nome no output. O README (AC-007) documenta o pattern com exemplo copiavel.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

## Mapeamento AC → arquivos

| AC | Componente principal | Arquivo de teste |
|---|---|---|
| AC-001 | `TenantIsolationModelTest` — data provider: sensitive_models × metodos de query | `tests/tenant-isolation/TenantIsolationModelTest.php` |
| AC-002 | `TenantIsolationHttpTest` — rotas autenticadas com ID de recurso do tenant B | `tests/tenant-isolation/TenantIsolationHttpTest.php` |
| AC-003 | `TenantIsolationJobTest` — jobs do tenant A, verifica registros com tenant_id=A | `tests/tenant-isolation/TenantIsolationJobTest.php` |
| AC-004 | `TenantIsolationExportTest` — payload de exports/relatorios autenticados no tenant A | `tests/tenant-isolation/TenantIsolationExportTest.php` |
| AC-005 | Job `tenant-isolation` em `gates.yml` com paths filter | `.github/workflows/gates.yml` |
| AC-006 | Fixture compartilhada em `TenantIsolationTestCase` (setUp 1x, transacoes por teste) | `tests/tenant-isolation/TenantIsolationTestCase.php` |
| AC-007 | README da suite | `tests/tenant-isolation/README.md` |
| AC-008 | Edge case em `TenantIsolationModelTest` — model sem `BelongsToTenant` falha explicitamente | `tests/tenant-isolation/TenantIsolationModelTest.php` |
| AC-009 | Edge case em `TenantIsolationModelTest` — `DB::raw('SUM(valor)')` com escopo correto | `tests/tenant-isolation/TenantIsolationModelTest.php` |
| AC-010 | Edge case em `TenantIsolationHttpTest` — query string `?tenant=B` e header `X-Tenant: B` | `tests/tenant-isolation/TenantIsolationHttpTest.php` |
| AC-011 | Edge case em `TenantIsolationHttpTest` — batch de IDs cross-tenant rejeitado inteiro | `tests/tenant-isolation/TenantIsolationHttpTest.php` |
| AC-012 | Edge case em `TenantIsolationJobTest` — retry com tenant context restaurado | `tests/tenant-isolation/TenantIsolationJobTest.php` |
| AC-013 | Edge case em `TenantIsolationExportTest` — soft-deleted cross-tenant ausente no export | `tests/tenant-isolation/TenantIsolationExportTest.php` |
| AC-014 | Paths filter no job CI — pula em PRs que nao tocam codigo sensivel | `.github/workflows/gates.yml` |
| AC-015 | Crescimento linear: 1 entrada nova no data provider = 1 model coberto automaticamente | `tests/tenant-isolation/TenantIsolationModelTest.php` |
| AC-016 | SQL injection em parametro de rota — 404/403, sem dados do tenant B, log com tenant_context | `tests/tenant-isolation/TenantIsolationHttpTest.php` |

---

## Novos arquivos

- `tests/tenant-isolation/TenantIsolationTestCase.php` — TestCase base: cria 2 tenants (A e B) com usuarios e dados homonimos em `setUpBeforeClass`, usa `DatabaseTransactions` para reset incremental por teste, expoe helpers `tenantA()`, `tenantB()`, `userA()`, `userB()`
- `tests/tenant-isolation/TenantIsolationModelTest.php` — data provider itera `config/tenancy.php[sensitive_models]` × `[all, find, where, whereHas, with, count, sum, avg]`; edge cases de model sem trait (AC-008) e DB::raw aggregation (AC-009)
- `tests/tenant-isolation/TenantIsolationHttpTest.php` — data provider itera rotas autenticadas com IDs cross-tenant; cobre query string/header forjado (AC-010), batch de IDs (AC-011), SQL injection em rota (AC-016)
- `tests/tenant-isolation/TenantIsolationJobTest.php` — dispara jobs de `config/tenancy-jobs.php` no contexto do tenant A, verifica tenant_id nos registros criados, cobre retry com restauracao de contexto (AC-012)
- `tests/tenant-isolation/TenantIsolationExportTest.php` — inspeciona payload de exports do PlansPage e rotas /reports/* existentes; cobre soft-deleted cross-tenant (AC-013)
- `tests/tenant-isolation/README.md` — guia para dev junior: o que e a suite, como adicionar model sensivel (1 paragrafo + exemplo copiavel de data provider), como rodar localmente via `php artisan test --testsuite=tenant-isolation`
- `config/tenancy-jobs.php` — array `tenant_aware_jobs` com lista inicial de jobs do E02 que propagam tenant context (ex: `ProcessConsentJob`, `ExportReportJob`)

---

## Arquivos modificados

- `config/tenancy.php` — adicionar chave `sensitive_models` com array dos models sensiveis consolidados dos slices E02 (User, TenantUser, Plan, Subscription, ConsentRecord e derivados); se a chave ja existir, consolidar sem remover entradas anteriores
- `.github/workflows/gates.yml` — adicionar job `tenant-isolation` com paths filter em `app/Models/**`, `app/Http/**`, `app/Livewire/**`, `app/Jobs/**`, `tests/tenant-isolation/**`; incluir `continue-on-error: true` com comentario de remocao quando cota CI resolver
- `phpunit.xml` — registrar testsuite `tenant-isolation` apontando para `tests/tenant-isolation/`

---

## Schema / migrations

Nenhuma migration neste slice. O slice e exclusivamente de testes, configuracao e CI.

---

## APIs / contratos

Nenhuma API nova neste slice. Os testes de HTTP exercitam rotas ja existentes nos slices E02 e verificam que retornam 404/403 — nunca 200 com dados cross-tenant.

---

## Sequencia de implementacao

### Task 1: Infraestrutura da suite

**Files:**
- Create: `tests/tenant-isolation/TenantIsolationTestCase.php`
- Create: `config/tenancy-jobs.php`
- Modify: `config/tenancy.php` — adicionar `sensitive_models`
- Modify: `phpunit.xml` — registrar testsuite `tenant-isolation`

**Objetivo:** `php artisan test --testsuite=tenant-isolation` nao falha por erro de bootstrap; fixture de 2 tenants criada.

---

### Task 2: TenantIsolationModelTest (AC-001, AC-008, AC-009, AC-015)

**Files:**
- Create: `tests/tenant-isolation/TenantIsolationModelTest.php`

**Objetivo:** data provider itera `sensitive_models` × metodos de query; cada combinacao asserta zero resultados do outro tenant; edge cases de model sem trait e DB::raw falham explicitamente se vazamento detectado.

---

### Task 3: TenantIsolationHttpTest (AC-002, AC-010, AC-011, AC-016)

**Files:**
- Create: `tests/tenant-isolation/TenantIsolationHttpTest.php`

**Objetivo:** data provider itera rotas autenticadas; asserta 404/403 para ID cross-tenant; cobre query string/header forjado, batch, SQL injection com verificacao de log.

---

### Task 4: TenantIsolationJobTest (AC-003, AC-012)

**Files:**
- Create: `tests/tenant-isolation/TenantIsolationJobTest.php`

**Objetivo:** para cada job em `config/tenancy-jobs.php`, dispara no tenant A e asserta registros com `tenant_id=A`; cobre retry via `Queue::fake()` simulando requeue e verificando que o bootstrapper restaura o contexto.

---

### Task 5: TenantIsolationExportTest (AC-004, AC-013)

**Files:**
- Create: `tests/tenant-isolation/TenantIsolationExportTest.php`

**Objetivo:** inspeciona payload de exports existentes no E02; asserta ausencia de IDs/valores do tenant B; cobre `withTrashed()` cross-tenant em soft-deleted.

---

### Task 6: CI e documentacao (AC-005, AC-006, AC-007, AC-014)

**Files:**
- Modify: `.github/workflows/gates.yml` — job `tenant-isolation`
- Create: `tests/tenant-isolation/README.md`

**Objetivo:** job CI com paths filter configurado; README validado como legivel por dev junior (exemplo copiavel de adicao de model).

---

## Riscos e mitigacoes

- **Fixture pesada ultrapassar 60s (AC-006):** fixture criada 1x em `setUpBeforeClass` + rollback por transacao por teste. Se ainda lento, usar `RefreshDatabaseLazily` do Pest. Medir com `--profile` na Task 1 e ajustar antes de prosseguir.
- **Model sensivel sem `BelongsToTenant` nao detectado:** AC-008 torna isso falha explicita com nome do model. Nao e silencioso. Mitigacao e o proprio teste.
- **`DB::raw` bypassando global scope (AC-009):** o teste asserta a soma por raw SQL diretamente; se falhar, a mensagem aponta o model. Correcao no codigo e adicionar `WHERE tenant_id = ?` obrigatoriamente em toda query raw sensivel — documentado no README.
- **Introspeccao de rotas exigir boot completo:** usar `Route::getRoutes()` dentro do proprio TestCase (disponivel no contexto do teste Laravel), sem processo separado. Resultado cacheado como propriedade estatica do TestCase para nao repetir por teste.
- **Job retry rodando com tenant errado (AC-012):** stancl/tenancy ja inclui `JobTenancyBootstrapper`; o teste valida que o bootstrapper esta registrado no job. Se o job nao implementar o bootstrapper, o teste falha explicitamente.
- **CI cota esgotada:** `continue-on-error: true` no job CI enquanto divida ativa. Suite local sempre rodavel via `php artisan test --testsuite=tenant-isolation`. Comentario no workflow indica o condicional de remocao do flag.
- **SQL injection retornando 200 (AC-016):** o teste asserta HTTP 404/403 e ausencia de campos do tenant B no payload. A primeira linha de defesa e o global scope do Eloquent; parametros de rota nunca chegam como raw SQL se o controller usa binding do Eloquent.

---

## Dependencias de outros slices

- `slice-001` — scaffold Laravel 13 + stancl/tenancy instalado; `config/tenancy.php` base existente
- `slice-002` — PostgreSQL + migrations de `tenants`, `users`, `tenant_users`; factories de User e Tenant disponiveis
- `slice-003` — pipeline CI (`gates.yml`) existente onde o job `tenant-isolation` sera adicionado
- `slice-004` — RBAC, `spatie/laravel-permission` instalado; rotas autenticadas com middleware `auth` disponiveis para o data provider de HTTP
- `slice-005` — estrutura de rotas `web.php`/`api.php` com middleware confirmada
- `slice-006` a `slice-010` — models sensiveis (Plan, Subscription, ConsentRecord, AuditLog, TenantUser) e jobs (ProcessConsentJob, ExportReportJob) existentes e testáveis

---

## Fora de escopo deste plano (confirmando spec)

- Penetration test externo.
- Testes de escalacao de privilegio intra-tenant (cobertos pelas policies RBAC do E02-S04).
- Vazamento via logs, telemetria ou mensagens de erro.
- Isolamento de arquivos no storage (S3/disk) — slice dedicado em E04.
- Suite de performance/load testing.
