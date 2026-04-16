# Slice 011 — E02-S08: Testes estruturais de isolamento entre tenants

**Status:** ⚠ precisa da sua decisão
**Data:** 2026-04-15
**Slice:** 011

---

## O que foi feito

Esta entrega cobre os seguintes critérios:

- **AC-001** — Dado 2 tenants (A e B) com 1 usuário cada e dados homônimos em **todos** os models listados em `config/tenancy.php[sensitive_models]`, quando `TenantIsolationModelTest` roda, então para cada model o escopo global filtra corretamente em `all()`, `find()`, `where()`, `whereHas()`, `with()`, `count()`, `sum()`, `avg()` e relacionamentos — retornando **apenas** registros do tenant corrente.
- **AC-002** — Dado usuário autenticado no tenant A, quando tenta acessar qualquer rota em `routes/web.php` ou `routes/api.php` com middleware `auth` informando ID de recurso pertencente ao tenant B, então resposta é **404 ou 403**, nunca 200 com dados do tenant B.
- **AC-003** — Dado job listado em `config/tenancy-jobs.php` disparado no contexto do tenant A, quando executa e persiste resultado, então os registros criados têm `tenant_id = A` e nenhum efeito colateral aparece no banco do tenant B.
- **AC-004** — Dado relatório/export gerado (ex: `PlansPage summary`, futuros `/reports/*`) autenticado no tenant A, quando inspecionado o payload final (PDF/CSV/JSON), então contém **apenas** dados do tenant A e nenhuma referência a IDs ou valores do tenant B.
- **AC-005** — Dado PR que toca `app/Models/**`, `app/Http/**`, `app/Livewire/**` ou `app/Jobs/**`, quando o workflow `.github/workflows/ci.yml` roda, então job `tenant-isolation` executa obrigatoriamente e qualquer falha **bloqueia merge** (comportamento alvo definitivo; ver Riscos para dívida técnica de CI).
- **AC-006** — Dado CI padrão rodando a suite completa `tests/tenant-isolation/`, quando cronometrado, então tempo total < **60s** em runner padrão GitHub Actions (ubuntu-latest).
- **AC-007** — Dado desenvolvedor novo, quando lê `tests/tenant-isolation/README.md`, então em ≤1 parágrafo + 1 exemplo de código aprende a adicionar novo model sensível à suite.
- **AC-008
AC-001** — (edge de AC-001) Dado model sensível **sem** `BelongsToTenant` trait (regressão), quando `TenantIsolationModelTest` descobre o model via introspecção de `config/tenancy.php`, então teste **falha explicitamente** com mensagem apontando o model faltante.
- **AC-009
AC-001** — (edge de AC-001) Dado agregação `DB::raw('SUM(valor)')` executada em model sensível, quando roda no tenant A com dados em A e B, então soma retornada corresponde apenas aos registros de A (não pular escopo global via raw SQL sem `tenant_id`).
- **AC-010
AC-002** — (edge de AC-002) Dado usuário do tenant A tentando acessar rota via **query string** (`?tenant=B`) ou **header** forjado (`X-Tenant: B`), quando middleware stancl/tenancy processa, então contexto permanece no tenant A e recursos de B continuam inacessíveis.
- **AC-011
AC-002** — (edge de AC-002) Dado rota autenticada que aceita **batch de IDs** (ex: `DELETE /calibrations?ids=1,2,3` onde qualquer ID pertence ao tenant B), quando executa no contexto do tenant A, então operação é **rejeitada inteira** com 403 — nenhum ID do batch é processado, nunca processa parcialmente.
- **AC-012
AC-003** — (edge de AC-003) Dado job que falha e vai para retry queue, quando reprocessado, então o tenant context original é restaurado via middleware — job nunca roda com tenant errado após retry.
- **AC-013
AC-004** — (edge de AC-004) Dado export CSV/PDF que inclui **soft-deleted** records (`withTrashed()`), quando gerado, então soft-deleted do tenant B **não** aparecem no export do tenant A.
- **AC-014
AC-005** — (edge de AC-005) Dado PR que **não** toca código sensível (ex: só muda `README.md`), quando workflow roda, então job `tenant-isolation` **é pulado** (paths filter) — não desperdiça minutos de CI.
- **AC-015
AC-006** — (edge de AC-006) Dado aumento de 1 model novo na suite, quando cronometrado, então overhead é **linear** (≤5s por model adicional) — zero crescimento combinatorial.
- **AC-016** — Dado atacante autenticado no tenant A enviando payload com **SQL injection** em parâmetro de rota (`/instrumentos/1 OR 1=1`), quando request processa, então resposta é **404 ou 403** (nunca 200 com dados), o payload de resposta não contém nenhum campo de registro do tenant B (ID, nome, valor), e o log de auditoria registra a tentativa com `tenant_context=A` — teste simula este vetor explicitamente.

## O que o usuário final vai ver

- Dado 2 tenants (A e B) com 1 usuário cada e dados homônimos em **todos** os models listados em `config/tenancy.php[sensitive_models]`, quando `TenantIsolationModelTest` roda, então para cada model o escopo global filtra corretamente em `all()`, `find()`, `where()`, `whereHas()`, `with()`, `count()`, `sum()`, `avg()` e relacionamentos — retornando **apenas** registros do tenant corrente.
- Dado usuário autenticado no tenant A, quando tenta acessar qualquer rota em `routes/web.php` ou `routes/api.php` com middleware `auth` informando ID de recurso pertencente ao tenant B, então resposta é **404 ou 403**, nunca 200 com dados do tenant B.
- Dado job listado em `config/tenancy-jobs.php` disparado no contexto do tenant A, quando executa e persiste resultado, então os registros criados têm `tenant_id = A` e nenhum efeito colateral aparece no banco do tenant B.
- Dado relatório/export gerado (ex: `PlansPage summary`, futuros `/reports/*`) autenticado no tenant A, quando inspecionado o payload final (PDF/CSV/JSON), então contém **apenas** dados do tenant A e nenhuma referência a IDs ou valores do tenant B.
- Dado PR que toca `app/Models/**`, `app/Http/**`, `app/Livewire/**` ou `app/Jobs/**`, quando o workflow `.github/workflows/ci.yml` roda, então job `tenant-isolation` executa obrigatoriamente e qualquer falha **bloqueia merge** (comportamento alvo definitivo; ver Riscos para dívida técnica de CI).
- Dado CI padrão rodando a suite completa `tests/tenant-isolation/`, quando cronometrado, então tempo total < **60s** em runner padrão GitHub Actions (ubuntu-latest).
- Dado desenvolvedor novo, quando lê `tests/tenant-isolation/README.md`, então em ≤1 parágrafo + 1 exemplo de código aprende a adicionar novo model sensível à suite.
- (edge de AC-001) Dado model sensível **sem** `BelongsToTenant` trait (regressão), quando `TenantIsolationModelTest` descobre o model via introspecção de `config/tenancy.php`, então teste **falha explicitamente** com mensagem apontando o model faltante.
- (edge de AC-001) Dado agregação `DB::raw('SUM(valor)')` executada em model sensível, quando roda no tenant A com dados em A e B, então soma retornada corresponde apenas aos registros de A (não pular escopo global via raw SQL sem `tenant_id`).
- (edge de AC-002) Dado usuário do tenant A tentando acessar rota via **query string** (`?tenant=B`) ou **header** forjado (`X-Tenant: B`), quando middleware stancl/tenancy processa, então contexto permanece no tenant A e recursos de B continuam inacessíveis.
- (edge de AC-002) Dado rota autenticada que aceita **batch de IDs** (ex: `DELETE /calibrations?ids=1,2,3` onde qualquer ID pertence ao tenant B), quando executa no contexto do tenant A, então operação é **rejeitada inteira** com 403 — nenhum ID do batch é processado, nunca processa parcialmente.
- (edge de AC-003) Dado job que falha e vai para retry queue, quando reprocessado, então o tenant context original é restaurado via middleware — job nunca roda com tenant errado após retry.
- (edge de AC-004) Dado export CSV/PDF que inclui **soft-deleted** records (`withTrashed()`), quando gerado, então soft-deleted do tenant B **não** aparecem no export do tenant A.
- (edge de AC-005) Dado PR que **não** toca código sensível (ex: só muda `README.md`), quando workflow roda, então job `tenant-isolation` **é pulado** (paths filter) — não desperdiça minutos de CI.
- (edge de AC-006) Dado aumento de 1 model novo na suite, quando cronometrado, então overhead é **linear** (≤5s por model adicional) — zero crescimento combinatorial.
- Dado atacante autenticado no tenant A enviando payload com **SQL injection** em parâmetro de rota (`/instrumentos/1 OR 1=1`), quando request processa, então resposta é **404 ou 403** (nunca 200 com dados), o payload de resposta não contém nenhum campo de registro do tenant B (ID, nome, valor), e o log de auditoria registra a tentativa com `tenant_context=A` — teste simula este vetor explicitamente.

## O que funcionou

- ✓ Dado 2 tenants (A e B) com 1 usuário cada e dados homônimos em **todos** os models listados em `config/tenancy.php[sensitive_models]`, quando `TenantIsolationModelTest` roda, então para cada model o escopo global filtra corretamente em `all()`, `find()`, `where()`, `whereHas()`, `with()`, `count()`, `sum()`, `avg()` e relacionamentos — retornando **apenas** registros do tenant corrente.
- ✓ Dado usuário autenticado no tenant A, quando tenta acessar qualquer rota em `routes/web.php` ou `routes/api.php` com middleware `auth` informando ID de recurso pertencente ao tenant B, então resposta é **404 ou 403**, nunca 200 com dados do tenant B.
- ✓ Dado job listado em `config/tenancy-jobs.php` disparado no contexto do tenant A, quando executa e persiste resultado, então os registros criados têm `tenant_id = A` e nenhum efeito colateral aparece no banco do tenant B.
- ✓ Dado relatório/export gerado (ex: `PlansPage summary`, futuros `/reports/*`) autenticado no tenant A, quando inspecionado o payload final (PDF/CSV/JSON), então contém **apenas** dados do tenant A e nenhuma referência a IDs ou valores do tenant B.
- ✓ Dado PR que toca `app/Models/**`, `app/Http/**`, `app/Livewire/**` ou `app/Jobs/**`, quando o workflow `.github/workflows/ci.yml` roda, então job `tenant-isolation` executa obrigatoriamente e qualquer falha **bloqueia merge** (comportamento alvo definitivo; ver Riscos para dívida técnica de CI).
- ✓ Dado CI padrão rodando a suite completa `tests/tenant-isolation/`, quando cronometrado, então tempo total < **60s** em runner padrão GitHub Actions (ubuntu-latest).
- ✓ Dado desenvolvedor novo, quando lê `tests/tenant-isolation/README.md`, então em ≤1 parágrafo + 1 exemplo de código aprende a adicionar novo model sensível à suite.
- ✓ (edge de AC-001) Dado model sensível **sem** `BelongsToTenant` trait (regressão), quando `TenantIsolationModelTest` descobre o model via introspecção de `config/tenancy.php`, então teste **falha explicitamente** com mensagem apontando o model faltante.
- ✓ (edge de AC-001) Dado agregação `DB::raw('SUM(valor)')` executada em model sensível, quando roda no tenant A com dados em A e B, então soma retornada corresponde apenas aos registros de A (não pular escopo global via raw SQL sem `tenant_id`).
- ✓ (edge de AC-002) Dado usuário do tenant A tentando acessar rota via **query string** (`?tenant=B`) ou **header** forjado (`X-Tenant: B`), quando middleware stancl/tenancy processa, então contexto permanece no tenant A e recursos de B continuam inacessíveis.
- ✓ (edge de AC-002) Dado rota autenticada que aceita **batch de IDs** (ex: `DELETE /calibrations?ids=1,2,3` onde qualquer ID pertence ao tenant B), quando executa no contexto do tenant A, então operação é **rejeitada inteira** com 403 — nenhum ID do batch é processado, nunca processa parcialmente.
- ✓ (edge de AC-003) Dado job que falha e vai para retry queue, quando reprocessado, então o tenant context original é restaurado via middleware — job nunca roda com tenant errado após retry.
- ✓ (edge de AC-004) Dado export CSV/PDF que inclui **soft-deleted** records (`withTrashed()`), quando gerado, então soft-deleted do tenant B **não** aparecem no export do tenant A.
- ✓ (edge de AC-005) Dado PR que **não** toca código sensível (ex: só muda `README.md`), quando workflow roda, então job `tenant-isolation` **é pulado** (paths filter) — não desperdiça minutos de CI.
- ✓ (edge de AC-006) Dado aumento de 1 model novo na suite, quando cronometrado, então overhead é **linear** (≤5s por model adicional) — zero crescimento combinatorial.
- ✓ Dado atacante autenticado no tenant A enviando payload com **SQL injection** em parâmetro de rota (`/instrumentos/1 OR 1=1`), quando request processa, então resposta é **404 ou 403** (nunca 200 com dados), o payload de resposta não contém nenhum campo de registro do tenant B (ID, nome, valor), e o log de auditoria registra a tentativa com `tenant_context=A` — teste simula este vetor explicitamente.

## O que NÃO está neste slice (fica pra depois)

- Penetration test externo — entrega de governança, fora do MVP.
- Testes de escalação de privilégio intra-tenant (já cobertos pelas policies RBAC do E02-S04).
- Vazamento via logs, telemetria ou mensagens de erro — backlog pós-MVP.
- Isolamento de arquivos no storage (S3/disk) — escopo de slice dedicado em E04.
- Suite de performance/load testing — fora do MVP, item de governança.

## Sua decisão é necessária

A entrega não ficou pronta nesta tentativa. Os problemas acima foram
encontrados por uma verificação automática — não é opinião minha,
é resultado mecânico.

**Opções:**

- [ ] **Pedir nova tentativa** — o agente implementador corrige os problemas e tenta de novo
- [ ] **Reescopar** — o slice é grande demais; dividir em pedaços menores
- [ ] **Pausar** — prefiro discutir antes de decidir

## Próximo passo

Marque uma opção acima e me avise. Não vou continuar sem sua decisão.

---

<details>
<summary>Detalhes técnicos (não precisa abrir)</summary>

- **Verifier verdict:** approved
- **Reviewer verdict:** approved
- **Security verdict:** approved
- **Test audit verdict:** rejected
- **Functional verdict:** rejected
- **ACs pass/fail:** 16 / 0
- **Artefatos:**
    - `specs/011/spec.md`
    - `specs/011/verification.json`
    - `specs/011/review.json`
    - `specs/011/security-review.json`
    - `specs/011/test-audit.json`
    - `specs/011/functional-review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
