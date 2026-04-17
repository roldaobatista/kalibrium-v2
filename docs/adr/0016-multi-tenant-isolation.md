# ADR-0016 — Isolamento multi-tenant (row-level com `tenant_id` + enforcement em query scope)

**Data:** 2026-04-16
**Status:** aceita
**Decisor:** PM (via aceitação de pacote v3 — "preparado pra tudo")
**Origem:** re-auditoria comparativa externa independente identificou `F-GOV-02` como gap alto impacto (`docs/audits/comparativa-externa-reaudit-2026-04-16.md`). `REQ-TEN-005` do MVP estabelece isolamento forte mas não prescreve mecanismo.

---

## Contexto

O Kalibrium é um SaaS multi-tenant. Cada laboratório pagante é um tenant isolado. Vazamento cruzado de dados entre tenants é classificado como incidente S1 (crítico) em `mvp-scope.md §3.1 REQ-TEN-005`. Até esta ADR, a estratégia técnica de isolamento não estava formalizada.

O auditor independente apontou:
- Sem formalização, cada desenvolvedor pode implementar isolamento de forma diferente em query nova (esqueceu `WHERE tenant_id = ?`).
- Risco cresce linearmente com novos endpoints.
- Entidades novas da ampliação (Despesa, Padrão SPC, Requisição LGPD, Push Subscription etc) precisam de política clara.

## Opções consideradas

### Opção A — Schema-per-tenant (PostgreSQL schemas separados)

- **Prós:** isolamento físico no banco; zero risco de query esquecer filtro; backup/restore por tenant é trivial; downgrade de tenant não afeta outros.
- **Contras:** complexidade operacional alta (N schemas, N migrations); onboarding/offboarding mais lento; frameworks (Laravel Eloquent) exigem mais configuração por request; difícil executar query analítica cross-tenant (para dashboards de ops SaaS).

### Opção B — Database-per-tenant (N instâncias Postgres)

- **Prós:** isolamento máximo; conformidade mais forte; recursos dedicados por tenant.
- **Contras:** custo de infra alto (N instâncias); migrations explodem em tempo de CI/CD; operacionalmente inviável para o MVP (alvo: até 50 tenants no primeiro ano).

### Opção C — Row-level com `tenant_id` + enforcement automático em query scope (Eloquent Global Scope)

- **Prós:** simplicidade operacional (1 banco, 1 schema); migrations unificadas; cross-tenant queries simples para ops SaaS; framework-native com Laravel (global scope + middleware).
- **Contras:** risco de esquecer scope em query custom; exige teste automatizado forte contra vazamento.

### Opção D — Row-level com PostgreSQL RLS (Row-Level Security nativa)

- **Prós:** enforcement no banco, impossível esquecer; funciona para SQL direto sem Eloquent.
- **Contras:** configuração por conexão (`SET app.current_tenant = X` em cada request); complexidade de debug; menos ferramenta com Laravel Eloquent nativamente.

## Decisão

**Opção C + reforço da D em nível de segurança defesa em profundidade:**

**Primária (Opção C):** row-level com coluna `tenant_id` em **toda** tabela de domínio, enforçada via Eloquent Global Scope aplicado automaticamente no boot dos models + middleware de request que injeta `tenant_id` no contexto (autenticação do tenant pelo usuário logado).

**Defesa em profundidade (Opção D):** ativar PostgreSQL RLS nas **10 tabelas mais sensíveis** (Calibração, Certificado, Cliente, Instrumento, Padrão, OS, NFS-e, Requisição LGPD, Dispositivo Registrado, Evidência) como segunda linha de defesa — se o Global Scope falhar (bug, query custom), o banco recusa.

### Justificativa

- MVP precisa sair com simplicidade operacional (Opção C resolve).
- Risco de vazamento é crítico (`REQ-TEN-005` S1) — defesa em profundidade justifica overhead de RLS nas 10 tabelas-crítico.
- Cross-tenant queries para ops SaaS ficam viáveis (Opção C permite).
- Custo de infra contido (não explode em N instâncias como Opção B).

## Consequências

### Positivas
- Isolamento garantido por 2 mecanismos independentes nas 10 tabelas críticas.
- Baixo atrito operacional: 1 banco, 1 schema, migrations unificadas.
- `REQ-TEN-005` + novo `REQ-TEN-007` (v3) formalizados.
- Testes automatizados de isolamento (negative tests: "usuário do tenant X tenta ler dado do tenant Y → 403 ou vazio") possíveis e obrigatórios.

### Negativas
- Global Scope precisa ser aplicado a **todos** os models novos (pattern a ser reforçado em review via regra de lint ou template de model).
- RLS configurada nas 10 tabelas exige `SET app.current_tenant = X` em cada request — implementado em middleware inicial.
- Queries de ops cross-tenant (ex: dashboard Kalibrium-interno de todos os tenants) precisam de escape explícito (`withoutGlobalScope(TenantScope::class)`) + permissão de super-admin.
- Teste automatizado de isolamento torna-se gate obrigatório (não pode merge sem passar).

### Mitigações
- Template de model novo com Global Scope pré-aplicado.
- Rule de CI que falha merge se model novo não tem `tenant_id` column + scope.
- Suíte de teste de isolamento em `tests/Isolation/` rodada em todo PR.
- Auditoria periódica (`/guide-check`) inclui varredura por models sem scope.

## REQ relacionado

- `REQ-TEN-005` (v1) Isolamento forte entre tenants — vazamento é S1.
- `REQ-TEN-007` (v3, novo) Enforcement técnico documentado: Eloquent Global Scope + PostgreSQL RLS nas 10 tabelas críticas + teste de isolamento obrigatório em CI.

## Referências

- `docs/audits/comparativa-externa-reaudit-2026-04-16.md` §3 F-GOV-02
- `docs/product/PRD-ampliacao-2026-04-16-v3.md` §1.4
- Laravel docs — Eloquent Global Scopes.
- PostgreSQL docs — Row Security Policies.
- OWASP Testing Guide — Multi-tenancy isolation tests.
