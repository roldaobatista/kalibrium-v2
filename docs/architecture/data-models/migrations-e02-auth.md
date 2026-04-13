# Migrations E02 — Multi-tenancy, Auth e Planos

> **Status:** draft — aguardando revisão do PM e do gate técnico.
> **Data:** 2026-04-13.
> **Epico:** E02 — Multi-tenancy, Auth e Planos.
> **Documento do gate:** D.5 — Migration Spec.
> **Base:** ADR-0004, `docs/architecture/data-models/erd-e02-auth.md`, `docs/architecture/api-contracts/api-e02-auth.md`.

---

## 1. Objetivo

Definir a ordem segura das migrations do E02 antes de iniciar `specs/007 — SEG-001 Login seguro do laboratório`.

O E02 precisa entregar a base de identidade, vínculo do usuário ao tenant, papéis, planos, consentimentos LGPD e auditoria. As migrations devem ser aplicáveis em PostgreSQL e preparadas para Row Level Security (RLS), conforme as decisões de arquitetura já aceitas.

---

## 2. Ordem proposta das migrations

1. `create_tenants_table`
2. `create_companies_table`
3. `create_branches_table`
4. `extend_users_for_two_factor_auth`
5. `create_tenant_users_table`
6. `create_roles_table`
7. `create_permissions_table`
8. `create_tenant_user_roles_table`
9. `create_role_permissions_table`
10. `create_plans_table`
11. `create_features_table`
12. `create_plan_entitlements_table`
13. `create_subscriptions_table`
14. `create_tenant_entitlements_table`
15. `create_lgpd_categories_table`
16. `create_consent_subjects_table`
17. `create_consent_records_table`
18. `create_login_audit_logs_table`
19. `create_support_audit_logs_table`
20. `enable_e02_rls_policies`

Essa ordem mantém entidades raiz antes de vínculos, autenticação antes de RBAC, planos antes de limites por tenant, e auditoria depois dos eventos que ela registra.

---

## 3. Tabelas principais

### tenants

Campos mínimos:
- `id` uuid primary key.
- `name` string obrigatório.
- `legal_name` string opcional no onboarding parcial.
- `document_number` string opcional até validação cadastral.
- `status` enum lógico: `trial`, `active`, `suspended`, `cancelled`.
- `operational_profile` string.
- `emits_metrological_certificate` boolean default `false`.
- `accreditation_settings` jsonb nullable.
- timestamps.

Índices:
- unique em `document_number` quando não nulo.
- index em `status`.

### companies

Campos mínimos:
- `id` uuid primary key.
- `tenant_id` uuid obrigatório.
- `legal_name` string obrigatório.
- `document_number` string obrigatório.
- `trade_name` string nullable.
- timestamps.

Regras:
- `tenant_id` referencia `tenants.id`.
- unique composto por `tenant_id` + `document_number`.

### branches

Campos mínimos:
- `id` uuid primary key.
- `tenant_id` uuid obrigatório.
- `company_id` uuid obrigatório.
- `name` string obrigatório.
- `document_number` string nullable.
- `city` string nullable.
- `state` string nullable.
- timestamps.

Regras:
- `company_id` deve pertencer ao mesmo `tenant_id`.
- unique composto por `tenant_id` + `company_id` + `name`.

### users e two factor

Campos adicionados em `users`:
- `two_factor_secret` text nullable.
- `two_factor_recovery_codes` text nullable.
- `two_factor_confirmed_at` timestamp nullable.

Regras:
- Não alterar a semântica existente de e-mail, senha e verificação.
- Segredos de 2FA não devem aparecer em logs nem responses.

### tenant_users

Campos mínimos:
- `id` uuid primary key.
- `tenant_id` uuid obrigatório.
- `user_id` uuid obrigatório.
- `company_id` uuid nullable.
- `branch_id` uuid nullable.
- `status` enum lógico: `invited`, `active`, `suspended`, `removed`.
- `requires_2fa` boolean default `false`.
- `invited_at` timestamp nullable.
- `accepted_at` timestamp nullable.
- timestamps.

Regras:
- unique composto por `tenant_id` + `user_id`.
- `company_id` e `branch_id`, quando presentes, devem pertencer ao mesmo tenant.
- O último usuário com papel `gerente` ativo não pode ser removido por regra de aplicação.

### RBAC

Tabelas:
- `roles`: `id`, `name`, `guard_name`, timestamps.
- `permissions`: `id`, `name`, `guard_name`, timestamps.
- `tenant_user_roles`: `tenant_user_id`, `role_id`.
- `role_permissions`: `role_id`, `permission_id`.

Papéis seed do MVP:
- `gerente`
- `tecnico`
- `administrativo`
- `visualizador`

Permissões seed iniciais:
- `tenant.manage`
- `users.manage`
- `users.view`
- `plans.view`
- `plans.request_upgrade`
- `privacy.manage`
- `support.audit.view`

### Planos e limites

Tabelas:
- `plans`: plano comercial disponível.
- `features`: capacidade controlável do produto.
- `plan_entitlements`: limite padrão por plano.
- `subscriptions`: plano contratado pelo tenant.
- `tenant_entitlements`: exceção ou override por tenant.

Regras:
- Cobrança real fica fora do MVP.
- `subscriptions` precisa de `tenant_id`, `plan_id`, `status`, `trial_ends_on`, `current_period_ends_on`.
- `plan_entitlements` e `tenant_entitlements` usam `limit_value` nullable para representar ilimitado quando necessário.

### LGPD e consentimentos

Tabelas:
- `lgpd_categories`: categorias declaradas pelo tenant.
- `consent_subjects`: pessoa ou registro de referência do consentimento.
- `consent_records`: evento de consentimento concedido ou revogado.

Regras:
- `lgpd_categories` exige `tenant_id`, `code`, `name`, `legal_basis`, `retention_policy`.
- `consent_subjects` exige `tenant_id`, `subject_type`, `subject_id`, e ao menos um canal de contato quando aplicável.
- `consent_records` exige `tenant_id`, `consent_subject_id`, `lgpd_category_id`, `channel`, `status`, `granted_at` ou `revoked_at`.

### Auditoria

Tabelas:
- `login_audit_logs`: eventos de login e 2FA.
- `support_audit_logs`: acesso interno Kalibrium a dados operacionais de tenant.

Campos mínimos de `login_audit_logs`:
- `id`, `user_id`, `tenant_id`, `event`, `ip_address`, `user_agent_hash`, timestamps.

Campos mínimos de `support_audit_logs`:
- `id`, `tenant_id`, `support_user_id`, `reason`, `action`, `ip_address`, timestamps.

Regras:
- Não salvar senha, token, segredo TOTP ou código de recuperação.
- `reason` é obrigatório para suporte interno.

---

## 4. Row Level Security

Tabelas com RLS obrigatório:
- `companies`
- `branches`
- `tenant_users`
- `subscriptions`
- `tenant_entitlements`
- `lgpd_categories`
- `consent_subjects`
- `consent_records`
- `login_audit_logs`

Policy padrão para dados de tenant:

```sql
tenant_id = current_setting('app.current_tenant_id')::uuid
```

Exceções:
- `tenants` pode ser lida por fluxo de seleção de tenant quando o usuário já possui vínculo autorizado.
- `support_audit_logs` exige policy separada para usuário interno Kalibrium autorizado.
- Tabelas globais `roles`, `permissions`, `plans` e `features` não usam `tenant_id`, mas alterações exigem autorização administrativa.

---

## 5. Seeds mínimos

Seeds obrigatórios para ambiente local e teste:
- Papéis canônicos do MVP.
- Permissões canônicas do MVP.
- Mapeamento de permissões por papel.
- Planos iniciais: `starter`, `growth`, `enterprise`.
- Features iniciais usadas no bloqueio de plano.
- Categorias LGPD base para exemplos de teste.

Seed opcional:
- Tenant demo local com empresa raiz, filial raiz e usuário gerente.

Restrição:
- Dados demo não devem ser criados em produção por seed automático.

---

## 6. Testes mínimos por slice

Antes de aceitar o primeiro slice que tocar essas migrations:
- Migration roda em PostgreSQL limpo.
- `tenant_id` é obrigatório nas tabelas escopadas por tenant.
- Unique composto impede duplicidade dentro do mesmo tenant.
- RLS bloqueia leitura cruzada entre dois tenants de teste.
- Seeds criam papéis e permissões esperados.
- Regra de último gerente fica coberta no serviço/policy que alterar papéis.
- Auditoria de login não persiste credenciais nem tokens sensíveis.
- Auditoria de suporte exige justificativa.

---

## 7. Rollback

Ambiente local e teste:
- Remover tabelas em ordem inversa da criação.
- Remover colunas de 2FA de `users` apenas se nenhuma migration posterior depender delas.

Produção:
- Não usar rollback destrutivo automático para dados de tenant, auditoria, consentimentos ou usuários.
- Qualquer remoção em produção exige plano de migração reversível, backup e aprovação explícita do PM.

---

## 8. Checklist de aceite do documento

- [x] Ordem das migrations definida.
- [x] Tabelas principais cobertas.
- [x] RLS descrito.
- [x] Seeds mínimos listados.
- [x] Testes mínimos listados.
- [x] Rollback definido para local/teste e produção.
