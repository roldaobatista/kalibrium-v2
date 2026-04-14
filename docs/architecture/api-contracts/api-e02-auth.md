# API Contracts E02 — Multi-tenancy, Auth e Planos

> **Status:** draft — aguardando revisão do PM e do gate técnico.
> **Data:** 2026-04-13.
> **Epico:** E02 — Multi-tenancy, Auth e Planos.
> **Documento do gate:** D.3 — API Contracts do epico.
> **Base:** ADR-0004, `docs/design/wireframes/wireframes-e02-auth.md`, `docs/architecture/api-contracts/README.md`.

---

## 1. Escopo

Este contrato cobre endpoints HTTP e actions Livewire que sustentam as telas do E02. O MVP usa páginas Livewire e rotas web; APIs públicas externas ficam fora deste épico.

Headers padrão:
- `Accept: text/html` para navegação web.
- `Accept: application/json` quando Livewire envia request assíncrono.
- Proteção CSRF obrigatória em ações mutáveis.
- Rotas autenticadas exigem sessão válida e contexto de tenant permitido, exceto `/admin/*`, que usa contexto interno Kalibrium.
- Tenant `trial` ou `active` permite leitura e escrita conforme permissões do usuário.
- Tenant `suspended` permite autenticação e leitura em modo somente leitura; ações mutáveis retornam erro seguro sem alterar dados.
- Tenant `cancelled` bloqueia rotas de aplicação e exige fluxo posterior de suporte, reativação ou exportação quando aplicável.

Erros padrão:
- `401` não autenticado.
- `403` sem permissão.
- `404` recurso ausente no tenant atual.
- `409` conflito de estado.
- `422` validação de formulário.
- `429` limite de tentativas.

---

## 2. Autenticação pública

### GET /auth/login

Uso: exibe formulário de login.

Autorização: anônimo.

Success: `200 OK`.

### POST /auth/login

Uso: autentica usuário interno.

Request:

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `email` | string | sim | e-mail válido |
| `password` | string | sim | não logar valor |
| `remember` | boolean | não | manter sessão quando permitido |

Success:
- `302` para `/app` quando 2FA não for exigido.
- `302` para `/auth/two-factor-challenge` quando 2FA for exigido.
- `302` para `/app` em modo somente leitura quando tenant estiver `suspended` e o usuário concluir os fatores exigidos.

Erros:
- `422` para credencial inválida com mensagem neutra.
- `403` para tenant `cancelled` ou vínculo de usuário sem status ativo.
- `429` para excesso de tentativas.

### POST /auth/forgot-password

Request:

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `email` | string | sim | e-mail válido |

Success: redirect com mensagem neutra: "Se o e-mail existir, enviaremos um link."

Erros:
- `422` para formato inválido.
- Nunca revelar se o usuário existe.

### POST /auth/reset-password

Request:

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `token` | string | sim | token recebido por e-mail |
| `email` | string | sim | e-mail válido |
| `password` | string | sim | mínimo 12 caracteres |
| `password_confirmation` | string | sim | igual a `password` |

Success: `302` para `/auth/login` com confirmação.

Erros: `422` para token inválido, senha fraca ou confirmação divergente.

### POST /auth/two-factor-challenge

Request:

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `code` | string | condicional | 6 dígitos TOTP |
| `recovery_code` | string | condicional | alternativa ao TOTP |

Success: `302` para `/app`.

Erros:
- `422` para código inválido.
- `429` para excesso de tentativas.

---

## 3. Configurações do tenant

Component: `App\Livewire\Settings\TenantPage`

Action: `saveTenant()`

Autorização: `gerente`.

Entrada:

| Propriedade | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `legal_name` | string | sim | razão social |
| `document_number` | string | sim | CNPJ válido |
| `trade_name` | string | não | nome fantasia |
| `main_email` | string | sim | e-mail válido |
| `phone` | string | não | telefone BR |
| `operational_profile` | string | sim | enum do tenant |
| `emits_metrological_certificate` | boolean | sim | controla módulo Lab |

Saída:
- Atualiza tenant, empresa raiz e filial raiz quando aplicável.
- Emite toast de sucesso.

Erros:
- `403` quando usuário não é gerente.
- `422` para CNPJ inválido ou perfil operacional incompatível.

---

## 4. Usuários e papéis

Component: `App\Livewire\Settings\UsersPage`

Estado de URL:
- `search`
- `role`
- `page`

Action: `inviteUser()`

Autorização: `gerente` com 2FA ativo.

Entrada:

| Propriedade | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `name` | string | sim | nome do usuário |
| `email` | string | sim | e-mail válido e único |
| `role` | string | sim | `gerente`, `tecnico`, `administrativo`, `visualizador` |
| `company_id` | uuid | não | empresa do tenant |
| `branch_id` | uuid | não | filial do tenant |
| `requires_2fa` | boolean | sim | obrigatório para gerente/administrativo |

Saída:
- Cria usuário ou vínculo pendente.
- Envia convite.
- Registra auditoria.

Action: `updateRole(tenantUserId, role)`

Autorização: `gerente` com 2FA ativo.

Regras:
- Não permitir remover o último gerente.
- Não permitir alterar usuário de outro tenant.
- Mudança gera log.

Action: `deactivateUser(tenantUserId)`

Autorização: `gerente` com 2FA ativo.

Erros:
- `409` quando tentativa remover/desativar último gerente.
- `403` quando usuário alvo está fora do tenant.

---

## 5. Planos, privacidade e suporte

### Component: `App\Livewire\Settings\PlansPage`

Action: `requestUpgrade(featureCode)`

Autorização: `gerente`.

Regras:
- Cobrança real fora do MVP.
- Limites exibidos com percentuais 80% e 95%.
- Módulo fora do plano gera CTA de upgrade apenas para gerente.

### Component: `App\Livewire\Settings\PrivacyPage`

Action: `saveLgpdCategory()`

Entrada:

| Propriedade | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `code` | string | sim | código único por tenant |
| `name` | string | sim | nome amigável |
| `legal_basis` | string | sim | base legal |
| `retention_policy` | string | sim | política de retenção |

Action: `recordConsent()`

Entrada:

| Propriedade | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `subject_type` | string | sim | `contact`, `user`, `external_user` |
| `subject_id` | uuid | sim | registro do tenant |
| `channel` | string | sim | `email`, `whatsapp`, `sms` |
| `status` | string | sim | `granted`, `revoked` |

Regra: consentimento de marketing não controla e-mail transacional obrigatório.

### GET /admin/tenants

Uso: lista tenants para suporte interno.

Autorização: `suporte-kalibrium`.

Filtros: `search`, `status`.

Regra: não exibir dados técnicos de calibração.

### POST /admin/tenants/{tenant}/support-access

Request:

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `justification` | string | sim | mínimo 20 caracteres |

Success: `302` para detalhe permitido.

Erros:
- `403` sem papel interno.
- `422` sem justificativa.

---

## 6. Relação com ACs iniciais

| Slice | Tela/contrato base | AC sugerido |
|---|---|---|
| `specs/007` | `/auth/login` | login válido entra; login inválido falha sem enumerar usuário |
| `specs/007` | `/auth/forgot-password` | recuperação mostra mensagem neutra |
| `specs/007` | `/auth/two-factor-challenge` | gerente exige 2FA |
| `specs/008` | `TenantPage` | tenant isolado tem empresa/filial raiz |
| `specs/009` | `UsersPage` | técnico não acessa `/settings/users` |
