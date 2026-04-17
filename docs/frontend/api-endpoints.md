# API Endpoints — Backend E01/E02/E03 (Inventário para E15)

> **Spike INF-007 (Slice 015)** — inventário canônico dos endpoints do backend Laravel 13 já mergeados em E01/E02/E03, com finalidade exclusiva de **consumo pelo novo cliente PWA+Capacitor** (E15+).
>
> **Validade:** snapshot do `HEAD` no momento do spike (2026-04-16, branch `work/offline-discovery-2026-04-16`). E15-S02 deve re-validar antes do scaffold.
>
> **Fonte primária:** `routes/web.php` + controllers em `app/Http/Controllers/` + FormRequests em `app/Http/Requests/`. Não há `routes/api.php` — o backend atual serve via web+sessão; o cliente PWA reaproveitará os endpoints **como API** via `Authorization: Bearer <sanctum-token>` após adaptação prevista em E15-S02/S03 (ver débito `FE-API-01`).
>
> **ADRs relacionadas:** ADR-0015 (stack offline-first), ADR-0016 (multi-tenancy isolation via `tenant_id`).

---

## Endpoints

Todos os endpoints abaixo vivem em `routes/web.php` (não há `routes/api.php` no HEAD atual). O cabeçalho de auth indicado reflete o estado vigente — E15-S02 avaliará expor os mesmos controllers sob `routes/api.php` com `auth:sanctum`.

### Auth (E02)

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/auth/login` | GET | nenhum (`guest`) | — | HTML Livewire (`LoginPage`) | `LoginPage` (Livewire class) |
| `/auth/login` | POST | nenhum (`guest`) + CSRF | `email`, `password`, `remember?` | Redirect 302 para `/app` ou challenge 2FA | closure inline (`routes/web.php:67`) |
| `/auth/forgot-password` | GET | nenhum (`guest`) | — | HTML Livewire (`ForgotPasswordPage`) | `ForgotPasswordPage` |
| `/auth/forgot-password` | POST | nenhum (`guest`) + CSRF | `email` | JSON/redirect com flash de sucesso | closure inline (`routes/web.php:168`) |
| `/auth/reset-password/{token}` | GET | nenhum (`guest`) | — | HTML Livewire (`ResetPasswordPage`) | closure inline |
| `/auth/reset-password` | POST | nenhum (`guest`) + CSRF | `token`, `email`, `password`, `password_confirmation` | Redirect 302 para `/auth/login` | closure inline |
| `/auth/invitations/{token}` | GET | nenhum (`guest`) | — | HTML Livewire (`AcceptInvitationPage`) | `AcceptInvitationPage` |
| `/auth/invitations/{token}` | POST | nenhum (`guest`) + CSRF | `password`, `password_confirmation`, `name?` | Redirect 302 para `/auth/login` | closure inline |
| `/auth/two-factor-challenge` | GET | `auth` (sem 2FA completo) | — | HTML Livewire (`TwoFactorChallengePage`) | `TwoFactorChallengePage` |
| `/auth/two-factor-challenge` | POST | `auth` | `code` (TOTP 6 dígitos) OU `recovery_code` | Redirect 302 para `/app` | closure inline |
| `/logout` | POST | `auth` + CSRF | — | Redirect 302 para `/auth/login` | (registrado em linha ≥ 400) |

**Nota de adaptação E15-S02:** o cliente PWA não renderiza Livewire. E15-S02 deve (a) expor `POST /api/auth/login` que retorne `{token, user, tenant}` JSON via Sanctum, (b) preservar fluxo 2FA como segundo passo JSON. Débito registrado em `stack-versions.md` seção "Pré-condições".

### Tenants / Settings (E01)

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/app` | GET | `auth` + `2fa_completed` + tenant-resolved | — | HTML Livewire (`HomePage`) | `HomePage` |
| `/api/tenant-context` | GET | `auth` + `2fa_completed` | — | JSON `{tenant_id, tenant_name, user_role, plan}` | closure inline (`routes/web.php:418`) |
| `/settings/tenant` | GET | `auth` + `2fa_completed` + role ≥ admin | — | HTML Livewire (`TenantPage`) | `TenantPage` |
| `/settings/tenant` | POST | `auth` + `2fa_completed` + CSRF + role ≥ admin | `name`, `document`, `timezone`, `companies[]`, `branches[]` | Redirect 302 + flash | `TenantSettingsController` (invokable) |
| `/settings/users` | GET | `auth` + `2fa_completed` + role ≥ admin | — | HTML Livewire (`UsersPage`) | `UsersPage` |
| `/settings/plans` | GET | `auth` + `2fa_completed` + role ≥ admin | — | HTML Livewire (`PlansPage`) | `PlansPage` |

### Privacidade / LGPD (E01.5)

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/settings/privacy` | GET | `auth` + `2fa_completed` + role ≥ admin | — | HTML Livewire | `LgpdCategoriesPage` |
| `/settings/privacy/lgpd-categories` | POST | `auth` + CSRF + role ≥ admin | `name`, `description`, `retention_days` | Redirect 302 | `LgpdCategoryStoreController` (invokable) |
| `/settings/privacy/consentimentos` | GET | `auth` + `2fa_completed` + role ≥ admin | — | HTML Livewire | `ConsentSubjectsPage` |
| `/privacy/revoke/{token}` | GET | público (token assinado) | — | HTML (`RevokeConsentPage`) | `RevokeConsentPage` |
| `/privacy/revoke/{token}` | POST | público (token assinado) + CSRF | `confirm=true` | Redirect 302 | `RevocationSubmitController` (invokable) |

### Clientes (E03)

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/clientes` | GET | `auth` + `2fa_completed` + tenant-scope | query `?q=&per_page=&page=` (`ListClientesRequest`) | JSON (Inertia ou Resource) paginado de `ClienteResource` | `ClienteController@index` |
| `/clientes` | POST | `auth` + CSRF + tenant-scope | `nome`, `documento`, `email?`, `telefone?`, `tipo`, `endereco?` (`StoreClienteRequest`) | 201 `ClienteResource` | `ClienteController@store` |
| `/clientes/{id}` | GET | `auth` + tenant-scope | — | 200 `ClienteResource` | `ClienteController@show` |
| `/clientes/{id}` | PUT | `auth` + CSRF + tenant-scope | subset de `StoreClienteRequest` (`UpdateClienteRequest`) | 200 `ClienteResource` | `ClienteController@update` |
| `/clientes/{id}` | DELETE | `auth` + CSRF + tenant-scope | — | 204 no content (soft delete) | `ClienteController@destroy` |

### Contatos (E03)

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/clientes/{clienteId}/contatos` | GET | `auth` + tenant-scope | query `?per_page=` | JSON paginado de `ContatoResource` | `ContatoController@index` |
| `/clientes/{clienteId}/contatos` | POST | `auth` + CSRF + tenant-scope | `nome`, `email?`, `telefone?`, `cargo?`, `principal?` (`StoreContatoRequest`) | 201 `ContatoResource` | `ContatoController@store` |
| `/contatos/{id}` | GET | `auth` + tenant-scope | — | 200 `ContatoResource` | `ContatoController@show` |
| `/contatos/{id}` | PUT | `auth` + CSRF + tenant-scope | subset de `StoreContatoRequest` (`UpdateContatoRequest`) | 200 `ContatoResource` | `ContatoController@update` |
| `/contatos/{id}` | DELETE | `auth` + CSRF + tenant-scope | — | 204 no content | `ContatoController@destroy` |

### Healthcheck

| URL | Método | Auth header | Request payload (resumido) | Response (resumido) | Controller / Action |
|---|---|---|---|---|---|
| `/health` | GET | público (sem auth) | — | JSON `{status, db, cache, queue, version, timestamp}` | `HealthCheckController` (invokable) |
| `/ping` | GET | `auth` (dentro de grupo autenticado) | — | JSON `{pong: true}` | `Ping` (Livewire) |

### Headers de autenticação planejados para E15

O cliente PWA consumirá esses endpoints após adaptação E15-S02 usando o header padrão:

```
Authorization: Bearer <sanctum-token>
Accept: application/json
X-Tenant-Id: <tenant-uuid>   // apenas para fins de telemetria/log — o scope real é resolvido pelo token
```

Todos os endpoints autenticados aplicam middleware `auth` + `2fa_completed` + resolução de tenant. A migração para `auth:sanctum` (stateless) é trabalho de E15-S02.

---

## Schema local

Tabelas que o cliente offline (E15-S06) precisará espelhar em SQLite local. Cada linha declara a coluna `tenant_id` conforme ADR-0016 — se ausente, marcar explicitamente.

| Tabela | Finalidade | `tenant_id` presente? | Chave primária | Colunas PII / sensíveis | Notas |
|---|---|---|---|---|---|
| `tenants` | Metadados do tenant atual (read-only cache) | — (é a própria tabela de tenants) | `id` (UUID) | `document` (CNPJ) | Espelhar só o tenant do usuário logado. |
| `users` | Dados do usuário autenticado | não (link via `tenant_users`) | `id` (bigint) | `email`, `name`, `password` (NÃO espelhar hash) | Espelhar apenas `id, email, name` do usuário logado. |
| `tenant_users` | Associação user↔tenant + role | `tenant_id` (FK) | `(tenant_id, user_id)` | — | Espelhar só as linhas do usuário logado. |
| `roles` | Papéis (admin, user, etc.) | não (global) | `id` | — | Read-only cache global. |
| `companies` | Empresas do tenant | `tenant_id` (FK) | `id` (UUID) | `document`, `email` | Mirror completo por tenant. |
| `branches` | Filiais das empresas | `tenant_id` (FK) | `id` (UUID) | `address` | Mirror completo por tenant. |
| `clientes` | Clientes do CRM | `tenant_id` (FK) | `id` (UUID) | `documento` (CPF/CNPJ), `email`, `telefone`, `endereco` | Mirror principal E15-S06. Soft delete (`deleted_at`). |
| `contatos` | Contatos dos clientes | `tenant_id` (FK) | `id` (UUID) | `email`, `telefone` | Mirror linkado a `clientes`. Soft delete. |
| `lgpd_categories` | Categorias de consentimento | `tenant_id` (FK) | `id` (UUID) | — | Read-mostly; edição offline é rara. |
| `consent_subjects` | Titulares de dados | `tenant_id` (FK) | `id` (UUID) | `name`, `document`, `email` (altamente sensível) | **Avaliar criptografia por campo** independente do cipher global. |
| `consent_records` | Registros de consentimento | `tenant_id` (FK) | `id` (UUID) | vínculo com `consent_subjects` | Append-only. |

### Tabelas **não-espelhadas** (ficam só no servidor)

| Tabela | Por quê não espelhar |
|---|---|
| `cache`, `jobs`, `sessions`, `failed_jobs` | Infra Laravel — sem sentido offline. |
| `login_audit_logs`, `tenant_audit_logs` | Append-only do servidor; cliente envia eventos, não mantém replay. |
| `tenant_plan_metrics`, `plan_upgrade_requests` | Lógica de billing — servidor-only. |
| `revocation_tokens` | Tokens one-shot — nunca persistidos no cliente. |
| `sanity_check` | Tabela de smoke test do CI. |

### Notas críticas sobre `tenant_id` e ADR-0016

1. **Todas as tabelas espelhadas no cliente DEVEM manter `tenant_id` físico** mesmo sabendo que o cliente de um dispositivo serve um único tenant por sessão. Razão: se o usuário pertencer a 2+ tenants e trocar de tenant dentro do mesmo app, o isolamento lógico continua funcionando sem wipe forçado do SQLite.
2. **Toda query no cliente deve filtrar por `tenant_id`** via view/trigger ou helper compartilhado, replicando o comportamento das Global Scopes do Eloquent. E15-S06 definirá o pattern exato.
3. **RLS do PostgreSQL (`0001_01_02_000001_enable_rls_setup.php`) não se aplica offline** — a aplicação cliente assume responsabilidade de enforcement local.
