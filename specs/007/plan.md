# Plano técnico do slice 007 — SEG-001 Login seguro do laboratório

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/007/spec.md`

---

## Decisões arquiteturais

### D1: Usar Fortify + Sanctum como base de autenticação, com telas Web em Livewire e prefixo `/auth`

**Opções consideradas:**
- **Opção A: instalar `laravel/fortify` e `laravel/sanctum`, registrar um provider próprio e expor as telas como páginas Livewire sob `/auth/*`** — prós: segue a ADR-0004, entrega login, 2FA e reset de senha na estrutura nativa do Laravel, mantém o UX em Livewire e deixa a URL do slice exatamente como o spec pede; contras: exige bootstrap inicial mais explícito.
- **Opção B: escrever toda a autenticação em controllers e forms customizados, sem Fortify** — prós: controle total do fluxo; contras: duplica trabalho que o framework já cobre, aumenta a superfície de erro e enfraquece a aderência à ADR-0004.

**Escolhida:** Opção A.

**Razão:** o slice precisa do primeiro fluxo seguro de acesso, não de um subsistema de identidade inventado do zero. Fortify cobre autenticação, recuperação de senha e 2FA com menos código, Sanctum fecha a base de identidade do MVP e o prefixo `/auth` mantém o contrato do spec e da API E02.

**Reversibilidade:** média.

**ADR:** `docs/adr/0004-estrategia-de-identidade-e-autenticacao.md` e, por consistência de stack, `docs/adr/0001-stack-choice.md`.

### D2: Centralizar a avaliação de acesso em um resolvedor único de tenant, vínculo e fator adicional

**Opções consideradas:**
- **Opção A: criar um `TenantAccessResolver`/serviço único usado pelo provider de auth e pelo middleware da rota `/app`** — prós: uma única regra decide se o usuário entra, se entra em modo somente leitura, se precisa de 2FA e se o vínculo é bloqueado; isso simplifica os testes dos ACs 001, 002, 011, 012, 013, 019 e 020; contras: exige uma classe central com responsabilidade maior.
- **Opção B: espalhar as verificações entre páginas Livewire, controllers e middleware separados** — prós: menos estrutura inicial; contras: alto risco de divergência entre login, challenge, redirect e proteção da rota protegida.

**Escolhida:** Opção A.

**Razão:** o spec tem muitos estados de entrada e saída para o mesmo usuário. Sem um resolvedor central, a probabilidade de uma regra ficar diferente entre login, challenge e `/app` sobe rápido. Aqui a prioridade é consistência mecânica, não elegância abstrata.

**Reversibilidade:** média.

**ADR:** não requer ADR novo; é uma decisão local de implementação alinhada à ADR-0004.

### D3: Registrar auditoria por eventos/listeners dedicados, com sanitização explícita de payload

**Opções consideradas:**
- **Opção A: capturar login, falha, lockout e uso de recovery code em listeners dedicados que gravam `login_audit_logs` após passar por um sanitizador** — prós: separa fluxo de UI da persistência, facilita provar o AC-007 e protege o AC-021 contra vazamento de segredo; contras: adiciona uma camada a mais.
- **Opção B: gravar auditoria direto nas actions das páginas Livewire** — prós: menos arquivos; contras: mistura validação, autenticação e persistência, e torna mais fácil vazar senha, token ou código de 2FA em logs.

**Escolhida:** Opção A.

**Razão:** o slice tem requisito explícito de não persistir segredo sensível. Auditoria precisa nascer como preocupação de domínio, não como `logger()` jogado dentro da tela.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

### D4: Representar o modo somente leitura por estado de sessão e middleware na rota protegida, sem criar um guard separado

**Opções consideradas:**
- **Opção A: gravar um estado de sessão do tenant e bloquear escrita por middleware/policy na rota `/app`** — prós: atende o AC-011 sem criar uma arquitetura paralela de autenticação; prós adicionais: a mudança é fácil de testar e não interfere com o restante do app; contras: o enforcement completo de escrita fina fica para slices seguintes.
- **Opção B: criar um guard ou provider de autenticação separado para tenant suspenso** — prós: isolamento conceitual; contras: excesso para um slice que só precisa manter a sessão viva em modo somente leitura.

**Escolhida:** Opção A.

**Razão:** o objetivo do slice é permitir login seguro e um primeiro ponto protegido em `/app`. A sessão já existe e é suficiente para carregar o modo somente leitura; um guard novo seria custo sem benefício para este corte.

**Reversibilidade:** média.

**ADR:** não requer ADR novo.

### D5: Manter o escopo de RBAC mínimo neste slice, usando a relação de papel necessária para 2FA e deixando permissões completas para o próximo corte

**Opções consideradas:**
- **Opção A: modelar só o necessário para este slice — tenants, vínculos, papéis canônicos e auditoria de login — sem tentar fechar todo o RBAC do E02** — prós: respeita o escopo do spec, evita espalhar o slice em gestão de usuários e permissões, e mantém o foco na autenticação; contras: a formalização completa de permissões continua para o próximo slice do E02.
- **Opção B: implementar agora todo o RBAC de E02 com permissões, telas administrativas e gerenciamento de usuários** — prós: “fecha” a épica; contras: quebra o corte do slice 007 e mistura autenticação com administração de usuários, indo além do spec.

**Escolhida:** Opção A.

**Razão:** o slice precisa saber se o usuário é gerente ou administrativo para exigir 2FA e precisa saber se o vínculo está ativo, suspenso, removido ou convidado. Isso já resolve os ACs do slice sem antecipar a suíte inteira de gestão de acesso.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

---

## Sequência de implementação

### Task 1: Fechar a fundação de identidade e as rotas públicas

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `bootstrap/providers.php`
- Create: `config/fortify.php`
- Create: `config/sanctum.php`
- Modify: `routes/web.php`
- Modify: `resources/views/welcome.blade.php`
- Create: `app/Providers/FortifyServiceProvider.php`

- [ ] **Step 1: instalar a base de auth do MVP**

Adicionar `laravel/fortify` e `laravel/sanctum` ao projeto e publicar a configuração mínima que permita `/auth/*`, recuperação de senha e 2FA.

- [ ] **Step 2: registrar o provider e o prefixo de rota**

Registrar o provider de Fortify em `bootstrap/providers.php` e configurar o prefixo `/auth` em `config/fortify.php`.

- [ ] **Step 3: expor a entrada pública e a rota protegida**

Manter a entrada `/` como página pública de entrada do produto, com redirecionamento para `/app` quando já houver sessão, e criar a rota protegida `/app` como ponto de pós-login.

- [ ] **Step 4: validar o bootstrap com testes de rota**

Executar os testes do slice que confirmam que `/`, `/auth/login`, `/auth/forgot-password`, `/auth/reset-password/{token}`, `/auth/two-factor-challenge` e `/app` existem e respeitam os redirects esperados.

### Task 2: Criar o modelo mínimo de tenant, vínculo e auditoria

**Files:**
- Create: `database/migrations/2026_04_13_000100_create_tenants_table.php`
- Create: `database/migrations/2026_04_13_000110_create_roles_table.php`
- Create: `database/migrations/2026_04_13_000120_create_tenant_users_table.php`
- Create: `database/migrations/2026_04_13_000130_extend_users_for_two_factor_auth.php`
- Create: `database/migrations/2026_04_13_000140_create_login_audit_logs_table.php`
- Create: `app/Models/Tenant.php`
- Create: `app/Models/Role.php`
- Create: `app/Models/TenantUser.php`
- Create: `app/Models/LoginAuditLog.php`
- Modify: `app/Models/User.php`
- Create: `database/factories/TenantFactory.php`
- Create: `database/factories/RoleFactory.php`
- Create: `database/factories/TenantUserFactory.php`
- Create: `database/factories/LoginAuditLogFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: criar as migrations do corte E02 necessárias para o login**

Criar `tenants`, `roles`, `tenant_users`, as colunas de 2FA em `users` e `login_audit_logs`, com os estados que o spec usa: `trial`, `active`, `suspended`, `cancelled` para tenant e `invited`, `active`, `suspended`, `removed` para vínculo.

- [ ] **Step 2: modelar as relações mínimas**

Adicionar relações entre `User`, `Tenant`, `TenantUser` e `Role` para que o resolvedor de acesso consiga decidir 2FA, status de vínculo e modo somente leitura sem lógica duplicada.

- [ ] **Step 3: preparar fixtures de teste e seed demo**

Criar factories e um seed demo mínimo para apoiar os testes do slice sem depender de dados manuais.

- [ ] **Step 4: validar a migration em banco limpo**

Executar a suíte do slice de auth contra banco limpo e confirmar que as tabelas novas suportam os casos de login, bloqueio e auditoria.

### Task 3: Implementar login, 2FA e bloqueios de acesso

**Files:**
- Create: `app/Support/Auth/TenantAccessResolver.php`
- Create: `app/Support/Auth/LoginAuditRecorder.php`
- Create: `app/Http/Middleware/EnsureTwoFactorChallengeCompleted.php`
- Create: `app/Http/Responses/Auth/LoginResponse.php`
- Create: `app/Http/Responses/Auth/TwoFactorLoginResponse.php`
- Create: `app/Livewire/Pages/Auth/LoginPage.php`
- Create: `app/Livewire/Pages/Auth/TwoFactorChallengePage.php`
- Create: `app/Livewire/Pages/App/HomePage.php`
- Create: `resources/views/livewire/pages/auth/login-page.blade.php`
- Create: `resources/views/livewire/pages/auth/two-factor-challenge-page.blade.php`
- Create: `resources/views/livewire/pages/app/home-page.blade.php`

- [ ] **Step 1: centralizar a decisão de acesso**

Implementar o resolvedor único que decide se a combinação usuário+tenant permite entrar, se o tenant deve ficar em modo somente leitura e se o papel exige 2FA.

- [ ] **Step 2: implementar a tela de login e o challenge de 2FA**

Criar as páginas Livewire para login e challenge, com redirects para `/app` quando o acesso for válido e retorno para `/auth/two-factor-challenge` quando o fator adicional for exigido.

- [ ] **Step 3: proteger a rota `/app`**

Aplicar `auth` e o middleware de 2FA concluído em `/app`, redirecionando usuário não autenticado para login e usuário com challenge pendente para o fluxo correto.

- [ ] **Step 4: cobrir o modo somente leitura do tenant suspenso**

Ao autenticar um tenant `suspended`, manter a sessão ativa, mas marcar o estado da sessão como somente leitura para que a app não trate esse acesso como sessão normal.

- [ ] **Step 5: validar redirecionamentos e bloqueios**

Executar os testes de login e rota protegida até confirmar que os ACs de sucesso, 2FA e negação fecham como esperado.

### Task 4: Recuperação e redefinição de senha sem enumeração de conta

**Files:**
- Create: `app/Livewire/Pages/Auth/ForgotPasswordPage.php`
- Create: `app/Livewire/Pages/Auth/ResetPasswordPage.php`
- Create: `resources/views/livewire/pages/auth/forgot-password-page.blade.php`
- Create: `resources/views/livewire/pages/auth/reset-password-page.blade.php`
- Create: `app/Http/Responses/Auth/PasswordResetLinkSentResponse.php`
- Create: `app/Http/Responses/Auth/PasswordResetResponse.php`

- [ ] **Step 1: criar a página de recuperação de senha**

Expor o formulário de e-mail com resposta neutra, sem revelar se a conta existe, e garantir que e-mail inválido devolva `422`.

- [ ] **Step 2: criar a página de redefinição de senha**

Permitir redefinição somente com token válido, senha nova com no mínimo 12 caracteres e confirmação igual.

- [ ] **Step 3: garantir o reset seguro**

Fazer o fluxo invalidar token usado, redirecionar de volta ao login e manter a mesma mensagem neutra de sucesso.

- [ ] **Step 4: validar os casos de erro**

Cobrir token inválido ou expirado, senha fraca e confirmação divergente com `422`, sem tocar a senha atual.

### Task 5: Fechar auditoria e proteção de segredo

**Files:**
- Create: `app/Http/Middleware/EnsureReadOnlyTenantMode.php`
- Create: `app/Support/Auth/AuthPayloadSanitizer.php`
- Create: `app/Listeners/RecordLoginAudit.php`
- Create: `app/Listeners/RecordTwoFactorAudit.php`
- Create: `app/Providers/EventServiceProvider.php`
- Modify: `app/Providers/FortifyServiceProvider.php`

- [ ] **Step 1: registrar as tentativas de acesso**

Gravar auditoria em sucesso, falha, lockout e uso de recovery code, sempre com `user_id`, `tenant_id` quando disponível, IP e hash de user agent.

- [ ] **Step 2: sanitizar qualquer payload sensível**

Garantir que senha, token de recuperação, segredo TOTP e recovery code nunca sejam persistidos em texto puro nem reflitam em resposta.

- [ ] **Step 3: ligar a limitação de tentativas**

Configurar limitador por e-mail+IP para login e um limitador separado para 2FA, usando respostas `429` quando a janela for excedida.

- [ ] **Step 4: validar o corte de segurança**

Executar os testes que verificam que não há enumeração de conta, que o lockout existe e que nenhum segredo aparece em audit log ou resposta.

### Task 6: Preparar o harness de testes do slice

**Files:**
- Modify: `tests/Pest.php`
- Create: `tests/slice-007/TestHelpers.php`
- Create: `tests/slice-007/AuthLoginTest.php`
- Create: `tests/slice-007/AuthPasswordResetTest.php`
- Create: `tests/slice-007/AuthTwoFactorTest.php`
- Create: `tests/slice-007/AuthAuditTest.php`

- [ ] **Step 1: registrar o novo diretório de slice**

Adicionar `slice-007` ao bootstrap do Pest.

- [ ] **Step 2: quebrar os ACs por preocupação**

Separar login/acesso, recuperação/reset, 2FA e auditoria em arquivos diferentes para manter o slice legível e rastreável.

- [ ] **Step 3: executar os testes por grupo**

Rodar primeiro os testes de login, depois recuperação/reset, depois 2FA e por fim auditoria, fechando o slice com cobertura de todos os ACs.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `composer.json`, `composer.lock`, `bootstrap/providers.php`, `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `routes/web.php`, `app/Livewire/Pages/Auth/LoginPage.php`, `resources/views/livewire/pages/auth/login-page.blade.php`, `app/Livewire/Pages/App/HomePage.php`, `resources/views/livewire/pages/app/home-page.blade.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-002 | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Support/Auth/TenantAccessResolver.php`, `app/Http/Responses/Auth/LoginResponse.php`, `app/Livewire/Pages/Auth/LoginPage.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-003 | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Livewire/Pages/Auth/TwoFactorChallengePage.php`, `resources/views/livewire/pages/auth/two-factor-challenge-page.blade.php`, `app/Http/Responses/Auth/TwoFactorLoginResponse.php` | `tests/slice-007/AuthTwoFactorTest.php` |
| AC-004 | `app/Support/Auth/LoginAuditRecorder.php`, `app/Listeners/RecordTwoFactorAudit.php`, `app/Models/LoginAuditLog.php`, `database/migrations/2026_04_13_000140_create_login_audit_logs_table.php` | `tests/slice-007/AuthAuditTest.php` |
| AC-005 | `app/Livewire/Pages/Auth/ForgotPasswordPage.php`, `resources/views/livewire/pages/auth/forgot-password-page.blade.php`, `app/Http/Responses/Auth/PasswordResetLinkSentResponse.php` | `tests/slice-007/AuthPasswordResetTest.php` |
| AC-006 | `app/Livewire/Pages/Auth/ResetPasswordPage.php`, `resources/views/livewire/pages/auth/reset-password-page.blade.php`, `app/Http/Responses/Auth/PasswordResetResponse.php`, `routes/web.php` | `tests/slice-007/AuthPasswordResetTest.php` |
| AC-007 | `database/migrations/2026_04_13_000140_create_login_audit_logs_table.php`, `app/Models/LoginAuditLog.php`, `app/Support/Auth/LoginAuditRecorder.php`, `app/Listeners/RecordLoginAudit.php`, `app/Listeners/RecordTwoFactorAudit.php` | `tests/slice-007/AuthAuditTest.php` |
| AC-008 | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Livewire/Pages/Auth/LoginPage.php`, `app/Support/Auth/TenantAccessResolver.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-009 | `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Support/Auth/TenantAccessResolver.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-010 | `app/Livewire/Pages/Auth/ForgotPasswordPage.php`, `app/Providers/FortifyServiceProvider.php`, `resources/views/livewire/pages/auth/forgot-password-page.blade.php` | `tests/slice-007/AuthPasswordResetTest.php` |
| AC-011 | `database/migrations/2026_04_13_000100_create_tenants_table.php`, `database/migrations/2026_04_13_000120_create_tenant_users_table.php`, `app/Support/Auth/TenantAccessResolver.php`, `app/Http/Middleware/EnsureReadOnlyTenantMode.php`, `app/Livewire/Pages/App/HomePage.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-012 | `database/migrations/2026_04_13_000100_create_tenants_table.php`, `app/Support/Auth/TenantAccessResolver.php`, `app/Http/Responses/Auth/LoginResponse.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-013 | `database/migrations/2026_04_13_000120_create_tenant_users_table.php`, `app/Support/Auth/TenantAccessResolver.php`, `app/Http/Responses/Auth/LoginResponse.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-014 | `app/Livewire/Pages/Auth/TwoFactorChallengePage.php`, `app/Support/Auth/LoginAuditRecorder.php`, `app/Providers/FortifyServiceProvider.php` | `tests/slice-007/AuthTwoFactorTest.php` |
| AC-015 | `app/Livewire/Pages/Auth/TwoFactorChallengePage.php`, `app/Support/Auth/LoginAuditRecorder.php`, `app/Providers/FortifyServiceProvider.php` | `tests/slice-007/AuthTwoFactorTest.php` |
| AC-016 | `app/Livewire/Pages/Auth/ResetPasswordPage.php`, `resources/views/livewire/pages/auth/reset-password-page.blade.php`, `app/Http/Responses/Auth/PasswordResetResponse.php` | `tests/slice-007/AuthPasswordResetTest.php` |
| AC-017 | `app/Livewire/Pages/Auth/ResetPasswordPage.php`, `app/Http/Responses/Auth/PasswordResetResponse.php`, `routes/web.php` | `tests/slice-007/AuthPasswordResetTest.php` |
| AC-018 | `app/Providers/FortifyServiceProvider.php`, `app/Livewire/Pages/Auth/LoginPage.php`, `app/Livewire/Pages/Auth/ForgotPasswordPage.php`, `app/Livewire/Pages/Auth/ResetPasswordPage.php`, `app/Livewire/Pages/Auth/TwoFactorChallengePage.php`, `app/Support/Auth/AuthPayloadSanitizer.php` | `tests/slice-007/AuthAuditTest.php` |
| AC-019 | `routes/web.php`, `app/Livewire/Pages/App/HomePage.php`, `app/Http/Middleware/EnsureTwoFactorChallengeCompleted.php` | `tests/slice-007/AuthLoginTest.php` |
| AC-020 | `routes/web.php`, `app/Http/Middleware/EnsureTwoFactorChallengeCompleted.php`, `app/Livewire/Pages/App/HomePage.php` | `tests/slice-007/AuthTwoFactorTest.php` |
| AC-021 | `app/Support/Auth/AuthPayloadSanitizer.php`, `app/Support/Auth/LoginAuditRecorder.php`, `app/Listeners/RecordLoginAudit.php`, `app/Listeners/RecordTwoFactorAudit.php`, `app/Http/Responses/Auth/LoginResponse.php`, `app/Http/Responses/Auth/TwoFactorLoginResponse.php`, `app/Http/Responses/Auth/PasswordResetLinkSentResponse.php`, `app/Http/Responses/Auth/PasswordResetResponse.php` | `tests/slice-007/AuthAuditTest.php` |

---

## Novos arquivos

- `app/Providers/FortifyServiceProvider.php` — bootstrap de Fortify, prefixo `/auth`, limitadores e respostas customizadas.
- `app/Providers/EventServiceProvider.php` — registro dos listeners de auditoria.
- `app/Support/Auth/TenantAccessResolver.php` — decisão única de acesso, 2FA e modo somente leitura.
- `app/Support/Auth/LoginAuditRecorder.php` — gravação de auditoria com campos seguros.
- `app/Support/Auth/AuthPayloadSanitizer.php` — remoção explícita de senha, token, TOTP e recovery code.
- `app/Http/Middleware/EnsureTwoFactorChallengeCompleted.php` — bloqueio da rota `/app` quando o desafio não terminou.
- `app/Http/Middleware/EnsureReadOnlyTenantMode.php` — marcação e enforcement do estado somente leitura.
- `app/Http/Responses/Auth/LoginResponse.php` — redirect pós-login.
- `app/Http/Responses/Auth/TwoFactorLoginResponse.php` — redirect pós-2FA.
- `app/Http/Responses/Auth/PasswordResetLinkSentResponse.php` — resposta neutra do forgot password.
- `app/Http/Responses/Auth/PasswordResetResponse.php` — resposta do reset concluído.
- `app/Models/Tenant.php` — tenant com status do ciclo de vida.
- `app/Models/Role.php` — papel canônico do laboratório.
- `app/Models/TenantUser.php` — vínculo usuário+tenant com status e 2FA.
- `app/Models/LoginAuditLog.php` — trilha append-only do login.
- `app/Livewire/Pages/Auth/LoginPage.php` — formulário de login.
- `app/Livewire/Pages/Auth/ForgotPasswordPage.php` — formulário de recuperação.
- `app/Livewire/Pages/Auth/ResetPasswordPage.php` — formulário de reset.
- `app/Livewire/Pages/Auth/TwoFactorChallengePage.php` — challenge TOTP/recovery.
- `app/Livewire/Pages/App/HomePage.php` — landing protegida pós-login.
- `resources/views/livewire/pages/auth/login-page.blade.php` — view do login.
- `resources/views/livewire/pages/auth/forgot-password-page.blade.php` — view da recuperação.
- `resources/views/livewire/pages/auth/reset-password-page.blade.php` — view do reset.
- `resources/views/livewire/pages/auth/two-factor-challenge-page.blade.php` — view do challenge.
- `resources/views/livewire/pages/app/home-page.blade.php` — view da área protegida.
- `tests/slice-007/TestHelpers.php` — utilidades do slice.
- `tests/slice-007/AuthLoginTest.php` — ACs de login, bloqueio e acesso.
- `tests/slice-007/AuthPasswordResetTest.php` — ACs de recuperação e reset.
- `tests/slice-007/AuthTwoFactorTest.php` — ACs de challenge e recovery codes.
- `tests/slice-007/AuthAuditTest.php` — ACs de auditoria e não vazamento.

## Arquivos modificados

- `composer.json` — adicionar `laravel/fortify` e `laravel/sanctum`.
- `composer.lock` — travar as dependências resolvidas.
- `bootstrap/providers.php` — registrar o provider de Fortify e o provider de eventos.
- `routes/web.php` — raiz pública, redirect para `/app` quando autenticado e rota protegida `/app`.
- `resources/views/welcome.blade.php` — simplificar a entrada pública do produto.
- `app/Models/User.php` — esconder e castar os campos de 2FA e adicionar relações com tenant/vínculo.
- `database/seeders/DatabaseSeeder.php` — seed demo mínimo para login, tenant e papéis canônicos.
- `tests/Pest.php` — registrar `slice-007`.

## Schema / migrations

- `database/migrations/2026_04_13_000100_create_tenants_table.php` — `status` com `trial`, `active`, `suspended`, `cancelled`; nome; razão social; documento.
- `database/migrations/2026_04_13_000110_create_roles_table.php` — papéis canônicos do laboratório para distinguir gerente, técnico, administrativo e visualizador.
- `database/migrations/2026_04_13_000120_create_tenant_users_table.php` — vínculo do usuário ao tenant com `status`, `role`, `requires_2fa`, `tenant_id` e `user_id`.
- `database/migrations/2026_04_13_000130_extend_users_for_two_factor_auth.php` — `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`.
- `database/migrations/2026_04_13_000140_create_login_audit_logs_table.php` — `event`, `user_id`, `tenant_id`, `ip_address`, `user_agent_hash`, timestamps e payload seguro.

## APIs / contratos

### GET `/`
- Exibe a entrada pública do produto.
- Se houver sessão ativa, redireciona para `/app`.
- Não expõe dado de tenant para visitante anônimo.

### GET `/auth/login`
- Exibe o formulário de login.
- Resposta esperada: `200 OK`.

### POST `/auth/login`
- Autentica por e-mail e senha.
- Resposta esperada: `302` para `/app` quando 2FA não é exigido.
- Resposta esperada: `302` para `/auth/two-factor-challenge` quando 2FA é exigido.
- Resposta esperada: `422` para credencial inválida com mensagem neutra.
- Resposta esperada: `403` para tenant `cancelled` ou vínculo não ativo.
- Resposta esperada: `429` quando o limite de tentativas estoura.

### GET `/auth/forgot-password`
- Exibe o formulário de recuperação.

### POST `/auth/forgot-password`
- Aceita e-mail válido.
- Responde com mensagem neutra sem revelar se a conta existe.
- Resposta esperada: `422` para formato inválido.

### GET `/auth/reset-password/{token}`
- Exibe a tela de redefinição com token válido ou inválido.

### POST `/auth/reset-password`
- Troca a senha quando o token é válido e a senha nova tem pelo menos 12 caracteres.
- Resposta esperada: `302` para `/auth/login` quando concluído.
- Resposta esperada: `422` para token inválido, senha fraca ou confirmação divergente.

### GET `/auth/two-factor-challenge`
- Exibe o desafio TOTP ou recovery code.

### POST `/auth/two-factor-challenge`
- Conclui a autenticação quando o código é válido.
- Resposta esperada: `302` para `/app`.
- Resposta esperada: `422` para código inválido.
- Resposta esperada: `429` quando o limite de tentativas estoura.

### GET `/app`
- Requer sessão autenticada.
- Sem sessão, redireciona para login.
- Com 2FA pendente, mantém o fluxo no challenge.
- Com tenant `suspended`, entra em modo somente leitura.

## Riscos e mitigações

- **Fortify pode subir com prefixo errado e deixar `/login` exposto em vez de `/auth/login`** → mitigação: configurar o prefixo no `config/fortify.php` e cobrir com teste de rota.
- **O resolvedor de acesso pode divergir entre login, challenge e `/app`** → mitigação: uma única classe de decisão para acesso, usada por provider, response e middleware.
- **A auditoria pode vazar segredo se o payload for montado na tela** → mitigação: sanitizador dedicado e testes que inspecionam o conteúdo salvo em `login_audit_logs`.
- **Tenant suspenso pode escrever se o modo somente leitura ficar só no front-end** → mitigação: enforcement por middleware na rota protegida, não só por texto de UI.
- **O rate limit pode ser fraco demais ou bloquear demais** → mitigação: chave combinada por e-mail+IP para login e chave separada para 2FA.
- **Usuário com múltiplos vínculos pode gerar ambiguidade de tenant no login** → mitigação: resolver somente o vínculo elegível deste slice e falhar fechado se não houver um vínculo ativo e claro.

## Dependências de outros slices

- `slice-006` — base de frontend com Livewire, Vite, Tailwind e layout já existente.
- `docs/design/wireframes/wireframes-e02-auth.md` — wireframes e inventário das telas E02.
- `docs/architecture/api-contracts/api-e02-auth.md` — contrato de rotas e estados esperados.
- `docs/architecture/data-models/erd-e02-auth.md` — referência do modelo E02.
- `docs/architecture/data-models/migrations-e02-auth.md` — ordem e intenção das migrations do E02.
- `docs/product/flows/flows-e02-auth.md` — jornada do usuário e resultados esperados.

## Fora de escopo deste plano (confirmando spec)

- Registro público de novo laboratório.
- Tela de configuração do tenant em `/settings/tenant`.
- Gestão de usuários e papéis em `/settings/users`.
- Planos, limites e upgrade.
- Base legal LGPD, consentimentos e opt-out.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Portal do cliente final.
- Gestão de suporte/admin fora do fluxo de autenticação.
- Qualquer módulo de negócio além de login, 2FA, recuperação de senha, reset e auditoria.
