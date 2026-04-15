# Slice 011 — E02-S08: Testes estruturais de isolamento entre tenants

**Status:** draft
**Data de criação:** 2026-04-15
**Autor:** PM (via Story Contract E02-S08)
**Depende de:** slice-001 (scaffold Laravel + tenancy) ... slice-010 (consentimentos LGPD — E02-S07)

---

## Contexto

E02 encerrou 7 stories entregando multi-tenancy, autenticação, RBAC, 2FA, planos e consentimentos LGPD. Cada slice cobriu isolamento por amostra — testes específicos por feature. Falta uma rede de proteção **sistemática** que prove, de forma mecânica, que nenhum dado de um tenant A aparece em qualquer request/query/job/export executado no contexto do tenant B.

Este slice é o gate de saída do E02 e a pré-condição estrutural para E03 (Diagramas/Instrumentos/Processos) — qualquer feature adicionada em E03+ que toque models com `tenant_id` precisa passar por esta suite antes de mergear. Sem ela, o risco de vazamento cross-tenant cresce linearmente com cada novo model.

Stakeholder primário: PM (confiança de produto) + compliance LGPD (evidência estrutural de isolamento). Usuário final é beneficiado indiretamente via garantia de privacidade.

## Jornada alvo

Desenvolvedor abre PR que toca `app/Models/**`, `app/Http/**`, `app/Livewire/**` ou `app/Jobs/**`. CI dispara job `tenant-isolation` que: (1) cria 2 tenants fixture com dados homônimos, (2) executa suite de isolamento cobrindo models sensíveis, rotas autenticadas, jobs de fundo e exports, (3) falha o PR se qualquer assert detectar vazamento. Merge só avança com suite verde em <60s.

Quando PM solicita adicionar novo model sensível (ex: `InstrumentoCalibrado` no E03), dev lê `tests/tenant-isolation/README.md`, adiciona 1 entrada em `config/tenancy.php` + 1 linha na data provider, suite passa a cobrir o novo model automaticamente.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). Edge cases obrigatórios.

### Happy path
- **AC-001:** Dado 2 tenants (A e B) com 1 usuário cada e dados homônimos em **todos** os models listados em `config/tenancy.php[sensitive_models]`, quando `TenantIsolationModelTest` roda, então para cada model o escopo global filtra corretamente em `all()`, `find()`, `where()`, `whereHas()`, `with()`, `count()`, `sum()`, `avg()` e relacionamentos — retornando **apenas** registros do tenant corrente.

- **AC-002:** Dado usuário autenticado no tenant A, quando tenta acessar qualquer rota em `routes/web.php` ou `routes/api.php` com middleware `auth` informando ID de recurso pertencente ao tenant B, então resposta é **404 ou 403**, nunca 200 com dados do tenant B.

- **AC-003:** Dado job listado em `config/tenancy-jobs.php` disparado no contexto do tenant A, quando executa e persiste resultado, então os registros criados têm `tenant_id = A` e nenhum efeito colateral aparece no banco do tenant B.

- **AC-004:** Dado relatório/export gerado (ex: `PlansPage summary`, futuros `/reports/*`) autenticado no tenant A, quando inspecionado o payload final (PDF/CSV/JSON), então contém **apenas** dados do tenant A e nenhuma referência a IDs ou valores do tenant B.

- **AC-005:** Dado PR que toca `app/Models/**`, `app/Http/**`, `app/Livewire/**` ou `app/Jobs/**`, quando o workflow `.github/workflows/ci.yml` roda, então job `tenant-isolation` executa obrigatoriamente e qualquer falha **bloqueia merge** (comportamento alvo definitivo; ver Riscos para dívida técnica de CI).

- **AC-006:** Dado CI padrão rodando a suite completa `tests/tenant-isolation/`, quando cronometrado, então tempo total < **60s** em runner padrão GitHub Actions (ubuntu-latest).

- **AC-007:** Dado desenvolvedor novo, quando lê `tests/tenant-isolation/README.md`, então em ≤1 parágrafo + 1 exemplo de código aprende a adicionar novo model sensível à suite.

### Edge cases e erros (obrigatórios)

- **AC-008:** (edge de AC-001) Dado model sensível **sem** `BelongsToTenant` trait (regressão), quando `TenantIsolationModelTest` descobre o model via introspecção de `config/tenancy.php`, então teste **falha explicitamente** com mensagem apontando o model faltante.

- **AC-009:** (edge de AC-001) Dado agregação `DB::raw('SUM(valor)')` executada em model sensível, quando roda no tenant A com dados em A e B, então soma retornada corresponde apenas aos registros de A (não pular escopo global via raw SQL sem `tenant_id`).

- **AC-010:** (edge de AC-002) Dado usuário do tenant A tentando acessar rota via **query string** (`?tenant=B`) ou **header** forjado (`X-Tenant: B`), quando middleware stancl/tenancy processa, então contexto permanece no tenant A e recursos de B continuam inacessíveis.

- **AC-011:** (edge de AC-002) Dado rota autenticada que aceita **batch de IDs** (ex: `DELETE /calibrations?ids=1,2,3` onde qualquer ID pertence ao tenant B), quando executa no contexto do tenant A, então operação é **rejeitada inteira** com 403 — nenhum ID do batch é processado, nunca processa parcialmente.

- **AC-012:** (edge de AC-003) Dado job que falha e vai para retry queue, quando reprocessado, então o tenant context original é restaurado via middleware — job nunca roda com tenant errado após retry.

- **AC-013:** (edge de AC-004) Dado export CSV/PDF que inclui **soft-deleted** records (`withTrashed()`), quando gerado, então soft-deleted do tenant B **não** aparecem no export do tenant A.

- **AC-014:** (edge de AC-005) Dado PR que **não** toca código sensível (ex: só muda `README.md`), quando workflow roda, então job `tenant-isolation` **é pulado** (paths filter) — não desperdiça minutos de CI.

- **AC-015:** (edge de AC-006) Dado aumento de 1 model novo na suite, quando cronometrado, então overhead é **linear** (≤5s por model adicional) — zero crescimento combinatorial.

### Segurança
- **AC-016:** Dado atacante autenticado no tenant A enviando payload com **SQL injection** em parâmetro de rota (`/instrumentos/1 OR 1=1`), quando request processa, então resposta é **404 ou 403** (nunca 200 com dados), o payload de resposta não contém nenhum campo de registro do tenant B (ID, nome, valor), e o log de auditoria registra a tentativa com `tenant_context=A` — teste simula este vetor explicitamente.

## Fora de escopo

- Penetration test externo — entrega de governança, fora do MVP.
- Testes de escalação de privilégio intra-tenant (já cobertos pelas policies RBAC do E02-S04).
- Vazamento via logs, telemetria ou mensagens de erro — backlog pós-MVP.
- Isolamento de arquivos no storage (S3/disk) — escopo de slice dedicado em E04.
- Suite de performance/load testing — fora do MVP, item de governança.

## Dependências externas

- **stancl/tenancy** (já instalado no slice-001) — scope global e middleware de jobs.
- **Laravel Testing** — `RefreshDatabase` + `actingAs` + factories.
- **Pest 4** — data providers para varrer models/rotas.
- **GitHub Actions** — workflow `ci.yml` já existente (adicionar job novo).
- **`config/tenancy-jobs.php`** — será **criado neste slice** (não existe em slice anterior); lista inicial de jobs que propagam tenant context (ex: `ProcessConsentJob`, `ExportReportJob`).
- ADR-0001 (stack Laravel 13 + stancl/tenancy single-database + Pest).
- ADR-0012 (dual-LLM gates — slice precisa passar todos).

## Riscos conhecidos

- **Suite lenta por fixture pesada** → mitigação: fixture compartilhada com 2 tenants base criados 1× no `setUp` do TestCase, reset incremental por teste via transactions.
- **Introspecção de rotas exige full Laravel boot** → mitigação: usar `Artisan::call('route:list')` em memória dentro do próprio teste, cacheado como data provider.
- **Models sem `BelongsToTenant` passarem despercebidos** → mitigação: AC-001a explícito, falha dura se `config/tenancy.php[sensitive_models]` lista model sem a trait.
- **Job retry com tenant errado** → mitigação: AC-003a cobre; stancl/tenancy middleware `InitializeTenancyBySessionTenant` restaura contexto.
- **CI cota esgotada (dívida técnica ativa)** → mitigação: suite local rodável via `php artisan test --testsuite=tenant-isolation`; CI obrigatório (block) ativado quando cota resolver (dívida P1 do handoff). Enquanto a cota não for resolvida, o job pode ser configurado como `continue-on-error: true` no workflow — mas o AC-005 define o comportamento alvo ("block") que deve ser restaurado assim que a cota voltar. Essa transição é registrada como comentário no workflow, não no AC.

## Notas do PM (humano)

- Este slice **fecha o E02** (8/8 stories). Depois entra no E03.
- CI obrigatório ("block") é o comportamento alvo de AC-005. A transição transitória enquanto a cota do GitHub Actions não resolver está documentada em Riscos conhecidos — o AC permanece determinístico.
- Se introspecção de rotas detectar rota autenticada sem cobertura, teste deve **falhar explicitamente** — não ignorar silenciosamente.
- README da suite deve ser escrito para dev júnior — linguagem acessível, exemplo copiável.
