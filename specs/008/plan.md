# Plano técnico do slice 008 — TEN-001 Primeiro laboratorio isolado

**Gerado por:** Codex CLI (orquestrador)
**Status:** draft
**Spec de origem:** `specs/008/spec.md`

---

## Decisões arquiteturais

### D1: Entregar `/settings/tenant` como página Livewire autenticada, não como wizard público de onboarding

**Opções consideradas:**
- **Opção A: criar uma página Livewire autenticada em `/settings/tenant` para o gerente configurar o laboratório atual** — prós: segue o wireframe E02, reaproveita o login do slice 007 e mantém registro público de novo laboratório fora de escopo; contras: exige resolver o tenant atual a partir do vínculo do usuário.
- **Opção B: criar um wizard público de onboarding de laboratório** — prós: parece mais completo para cadastro inicial; contras: quebra o fora de escopo do slice, exige fluxo anônimo e mistura aquisição de cliente com configuração interna.

**Escolhida:** Opção A.

**Razão:** o slice 008 começa depois do login seguro. O usuário alvo é Marcelo já autenticado como gerente; portanto a configuração deve viver dentro da área protegida e não abrir um fluxo público novo.

**Reversibilidade:** média.

**ADR:** não requer ADR novo; segue ADR-0001 e ADR-0004.

### D2: Criar empresa raiz e filial raiz por serviço transacional, sem espalhar regra na tela

**Opções consideradas:**
- **Opção A: criar `TenantSettingsUpdater` para atualizar tenant, empresa raiz e filial raiz em uma única transação** — prós: atende AC-002, AC-003 e AC-012, evita duplicidade e centraliza rollback; contras: adiciona uma classe de aplicação.
- **Opção B: salvar tenant, empresa e filial diretamente no método Livewire `save()`** — prós: menos arquivos; contras: mistura UI com regra de consistência e aumenta risco de cadastro parcial.

**Escolhida:** Opção A.

**Razão:** o principal risco do slice é deixar tenant, empresa e filial em estados divergentes. Uma transação em serviço dedicado deixa a regra testável sem depender de comportamento visual.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

### D3: Criar auditoria própria de configurações do tenant, sem reaproveitar `login_audit_logs`

**Opções consideradas:**
- **Opção A: criar `tenant_audit_logs` para mudanças de dados do laboratório** — prós: separa auditoria de login da auditoria cadastral, atende AC-006 e mantém contexto de campos alterados; contras: adiciona uma tabela curta.
- **Opção B: reaproveitar `login_audit_logs` para registrar alterações do tenant** — prós: menos migration; contras: mistura eventos de autenticação com eventos cadastrais e enfraquece rastreabilidade.

**Escolhida:** Opção A.

**Razão:** o slice toca dados cadastrais do laboratório. Misturar isso com log de login criaria ambiguidade nos próximos slices de usuários, plano e LGPD.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

### D4: Validar CNPJ localmente no MVP, sem integração externa com Receita Federal

**Opções consideradas:**
- **Opção A: criar regra local `Cnpj` para validar formato, dígitos e unicidade no tenant raiz** — prós: suficiente para AC-007, testável e sem dependência externa; contras: não confirma situação cadastral em órgão externo.
- **Opção B: integrar consulta externa de CNPJ agora** — prós: validação mais rica; contras: adiciona fornecedor, falha de rede e decisão externa fora do slice.

**Escolhida:** Opção A.

**Razão:** o spec pede CNPJ ausente, inválido ou duplicado. Não pede consulta em órgão externo, e a dependência externa aumentaria o escopo sem necessidade.

**Reversibilidade:** média.

**ADR:** não requer ADR novo.

### D5: Aplicar permissão de gerente e modo somente leitura no servidor, não apenas na interface

**Opções consideradas:**
- **Opção A: `TenantPage::save()` revalida vínculo ativo, papel `gerente` e sessão em modo escrita antes de chamar o updater** — prós: cobre AC-009, AC-010 e AC-011 mesmo que uma requisição pule a UI; contras: duplica uma pequena parte da proteção já visível na rota.
- **Opção B: depender apenas do texto e dos botões da página** — prós: menos código; contras: falha o requisito de segurança porque requests Livewire podem chamar ações diretamente.

**Escolhida:** Opção A.

**Razão:** este slice é sobre isolamento do primeiro laboratório. A proteção precisa ser verificável no servidor para impedir alteração fora do tenant atual ou em sessão somente leitura.

**Reversibilidade:** fácil.

**ADR:** não requer ADR novo.

### D6: Ativar isolamento por tenant nas novas tabelas escopadas, além do filtro de aplicação

**Opções consideradas:**
- **Opção A: criar policies PostgreSQL para `companies`, `branches` e `tenant_audit_logs`, usando o contexto `app.current_tenant_id` já iniciado no slice 007** — prós: segue o gate documental E02, protege leitura/escrita cruzada mesmo se uma consulta esquecer filtro e fortalece AC-005 e AC-SEC-001; contras: exige um middleware pequeno para setar contexto do tenant nas rotas autenticadas.
- **Opção B: confiar apenas em `where tenant_id = ...` nas consultas da aplicação** — prós: menos SQL nas migrations; contras: isolamento fica dependente de disciplina em cada consulta.

**Escolhida:** Opção A.

**Razão:** o slice existe para isolar o primeiro laboratório. Como o projeto já adotou contexto PostgreSQL para autenticação, as novas tabelas escopadas do E02 devem continuar esse padrão.

**Reversibilidade:** média.

**ADR:** não requer ADR novo; segue ADR-0001 e os documentos E02 de modelo de dados.

---

## Sequência de implementação

### Task 1: Criar modelo de empresa, filial e auditoria do tenant

**Files:**
- Create: `database/migrations/2026_04_14_000200_extend_tenants_for_settings.php`
- Create: `database/migrations/2026_04_14_000210_create_companies_table.php`
- Create: `database/migrations/2026_04_14_000220_create_branches_table.php`
- Create: `database/migrations/2026_04_14_000230_create_tenant_audit_logs_table.php`
- Modify: `app/Models/Tenant.php`
- Create: `app/Models/Company.php`
- Create: `app/Models/Branch.php`
- Create: `app/Models/TenantAuditLog.php`
- Create: `database/factories/CompanyFactory.php`
- Create: `database/factories/BranchFactory.php`
- Create: `database/factories/TenantAuditLogFactory.php`
- Modify: `database/factories/TenantFactory.php`

- [ ] **Step 1: escrever testes RED do modelo de dados**

Criar testes do slice 008 que esperam os campos cadastrais do tenant, a empresa raiz, a filial raiz e a tabela de auditoria.

- [ ] **Step 2: criar migrations mínimas do slice**

Adicionar no tenant: `legal_name`, `document_number`, `trade_name`, `main_email`, `phone`, `operational_profile`, `emits_metrological_certificate`. Para `operational_profile`, usar os três perfis do PRD: `basic`, `intermediate` e `accredited`. Criar `companies`, `branches` e `tenant_audit_logs` com `tenant_id` obrigatório, índices compostos por tenant e policies PostgreSQL de isolamento quando o driver for `pgsql`.

- [ ] **Step 3: criar modelos e factories**

Adicionar relações `Tenant::companies()`, `Tenant::branches()` e `Tenant::auditLogs()`, além das relações inversas em `Company`, `Branch` e `TenantAuditLog`.

- [ ] **Step 4: validar dados e migrations**

Rodar `php artisan test tests/slice-008 --filter=TenantSettingsData` até confirmar que a estrutura mínima atende AC-002, AC-003, AC-005, AC-006 e AC-012.

### Task 2: Criar regra de CNPJ e serviço transacional de atualização

**Files:**
- Create: `app/Rules/Cnpj.php`
- Create: `app/Support/Tenancy/CurrentTenantResolver.php`
- Create: `app/Support/Tenancy/TenantSettingsUpdater.php`
- Create: `app/Support/Tenancy/TenantAuditRecorder.php`
- Create: `app/Http/Middleware/SetCurrentTenantContext.php`
- Modify: `app/Support/Auth/TenantAccessResolver.php`
- Modify: `app/Support/Auth/PostgresAuthContext.php`

- [ ] **Step 1: escrever testes RED de validação e consistência**

Cobrir CNPJ ausente, inválido, duplicado em outro tenant, razão social vazia, e-mail inválido e perfil operacional fora de `basic`, `intermediate` ou `accredited`.

- [ ] **Step 2: implementar regra local de CNPJ**

Validar CNPJ por dígitos e normalizar para apenas números antes de persistir.

- [ ] **Step 3: resolver tenant atual pelo usuário autenticado**

Usar o vínculo ativo do usuário para encontrar o tenant atual; falhar fechado quando o vínculo estiver suspenso, removido, convidado ou trocar antes do salvamento. Definir o contexto PostgreSQL do usuário e do tenant atual em requests autenticados para manter as policies de isolamento ativas.

- [ ] **Step 4: implementar atualização em transação**

`TenantSettingsUpdater` atualiza tenant, cria ou atualiza empresa raiz e cria ou atualiza filial raiz em uma única transação. Se qualquer parte falhar, nada fica salvo.

- [ ] **Step 5: registrar auditoria sem segredo**

`TenantAuditRecorder` grava usuário, tenant, ação, campos alterados, IP e hash de user agent, sem salvar senha, token de reset, segredo TOTP ou código de recuperação.

### Task 3: Criar a tela `/settings/tenant`

**Files:**
- Create: `app/Livewire/Pages/Settings/TenantPage.php`
- Create: `resources/views/livewire/pages/settings/tenant-page.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: escrever testes RED de acesso e renderização**

Cobrir `GET /settings/tenant` para gerente ativo, usuário não gerente, tenant suspenso em modo somente leitura e usuário sem vínculo ativo.

- [ ] **Step 2: registrar rota protegida**

Adicionar `GET /settings/tenant` dentro do grupo autenticado com `EnsureTwoFactorChallengeCompleted` e `EnsureReadOnlyTenantMode`.

- [ ] **Step 3: implementar página Livewire**

Carregar dados atuais do tenant, empresa raiz e filial raiz no `mount()`. Exibir campos: razão social, CNPJ, nome fantasia, e-mail principal, telefone, perfil operacional e emissão de certificado metrológico.

- [ ] **Step 4: implementar `save()` com validação e revalidação de acesso**

Antes de salvar, confirmar papel `gerente`, vínculo ativo e sessão fora do modo somente leitura. Em seguida chamar `TenantSettingsUpdater`.

- [ ] **Step 5: renderizar estados funcionais**

Mostrar confirmação no sucesso, erros inline por campo inválido e estado de somente leitura sem botão funcional de salvamento.

### Task 4: Cobrir isolamento entre tenants e payloads maliciosos

**Files:**
- Create: `tests/slice-008/TestHelpers.php`
- Create: `tests/slice-008/TenantSettingsPageTest.php`
- Create: `tests/slice-008/TenantSettingsValidationTest.php`
- Create: `tests/slice-008/TenantSettingsIsolationTest.php`
- Create: `tests/slice-008/TenantSettingsAuditTest.php`
- Modify: `tests/Pest.php`

- [ ] **Step 1: registrar o diretório do slice no Pest**

Adicionar `uses(TestCase::class)->in('slice-008');` em `tests/Pest.php`.

- [ ] **Step 2: criar helpers do slice 008**

Criar helpers para gerente autenticado, usuário técnico, tenant `active`, `trial`, `suspended`, empresa raiz, filial raiz e payload válido do formulário.

- [ ] **Step 3: cobrir ACs de happy path**

Criar testes para AC-001 a AC-006, incluindo criação inicial, atualização sem duplicar registros, tenant `trial`, isolamento entre dois tenants e auditoria.

- [ ] **Step 4: cobrir ACs de erro**

Criar testes para AC-007 a AC-012, incluindo CNPJ inválido/duplicado, campos inválidos, não gerente, somente leitura, vínculo alterado e falha transacional.

- [ ] **Step 5: cobrir ACs de segurança**

Criar testes para AC-SEC-001 a AC-SEC-003, incluindo IDs externos, payload HTML/SQL e mensagens sem dados de outro tenant.

### Task 5: Fechar validação do slice

**Files:**
- Modify: `specs/008/tasks.md`

- [ ] **Step 1: rodar os testes focados do slice**

Run: `php artisan test tests/slice-008`

Expected: todos os testes do slice 008 passam.

- [ ] **Step 2: rodar formatação nos arquivos tocados**

Run: `vendor/bin/pint --test app/Models app/Rules app/Support/Tenancy app/Livewire/Pages/Settings resources/views/livewire/pages/settings routes/web.php tests/slice-008 tests/Pest.php`

Expected: `PASS` sem mudanças pendentes.

- [ ] **Step 3: rodar validação estática nos arquivos novos**

Run: `vendor/bin/phpstan analyse app/Models app/Rules app/Support/Tenancy app/Livewire/Pages/Settings --level=8 --no-progress`

Expected: exit 0.

- [ ] **Step 4: atualizar tasks do slice**

Registrar em `specs/008/tasks.md` quais tarefas ficaram concluídas e quais comandos foram usados.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `routes/web.php`, `app/Livewire/Pages/Settings/TenantPage.php`, `resources/views/livewire/pages/settings/tenant-page.blade.php` | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-002 | `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Models/Tenant.php`, `app/Models/Company.php`, `app/Models/Branch.php`, migrations E02 do slice 008 | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-003 | `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Models/Company.php`, `app/Models/Branch.php` | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-004 | `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Livewire/Pages/Settings/TenantPage.php` | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-005 | `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Livewire/Pages/Settings/TenantPage.php` | `tests/slice-008/TenantSettingsIsolationTest.php` |
| AC-006 | `app/Support/Tenancy/TenantAuditRecorder.php`, `app/Models/TenantAuditLog.php`, `database/migrations/2026_04_14_000230_create_tenant_audit_logs_table.php` | `tests/slice-008/TenantSettingsAuditTest.php` |
| AC-007 | `app/Rules/Cnpj.php`, `app/Livewire/Pages/Settings/TenantPage.php`, `app/Support/Tenancy/TenantSettingsUpdater.php` | `tests/slice-008/TenantSettingsValidationTest.php` |
| AC-008 | `app/Livewire/Pages/Settings/TenantPage.php`, `app/Support/Tenancy/TenantSettingsUpdater.php` | `tests/slice-008/TenantSettingsValidationTest.php` |
| AC-009 | `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Livewire/Pages/Settings/TenantPage.php`, `routes/web.php` | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-010 | `app/Livewire/Pages/Settings/TenantPage.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Http/Middleware/EnsureReadOnlyTenantMode.php` | `tests/slice-008/TenantSettingsPageTest.php` |
| AC-011 | `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Livewire/Pages/Settings/TenantPage.php` | `tests/slice-008/TenantSettingsIsolationTest.php` |
| AC-012 | `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Models/Tenant.php`, `app/Models/Company.php`, `app/Models/Branch.php` | `tests/slice-008/TenantSettingsValidationTest.php` |
| AC-SEC-001 | `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Http/Middleware/SetCurrentTenantContext.php`, `app/Livewire/Pages/Settings/TenantPage.php` | `tests/slice-008/TenantSettingsIsolationTest.php` |
| AC-SEC-002 | `app/Rules/Cnpj.php`, `app/Livewire/Pages/Settings/TenantPage.php`, `resources/views/livewire/pages/settings/tenant-page.blade.php` | `tests/slice-008/TenantSettingsValidationTest.php` |
| AC-SEC-003 | `app/Livewire/Pages/Settings/TenantPage.php`, `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php` | `tests/slice-008/TenantSettingsIsolationTest.php` |

## Novos arquivos

- `database/migrations/2026_04_14_000200_extend_tenants_for_settings.php` — campos cadastrais do laboratório no tenant.
- `database/migrations/2026_04_14_000210_create_companies_table.php` — empresa raiz e futuras empresas do tenant.
- `database/migrations/2026_04_14_000220_create_branches_table.php` — filial raiz e futuras filiais do tenant.
- `database/migrations/2026_04_14_000230_create_tenant_audit_logs_table.php` — auditoria de alteração de dados do laboratório.
- `app/Models/Company.php` — empresa ligada ao tenant.
- `app/Models/Branch.php` — filial ligada ao tenant e à empresa.
- `app/Models/TenantAuditLog.php` — auditoria cadastral do tenant.
- `database/factories/CompanyFactory.php` — dados de teste de empresa.
- `database/factories/BranchFactory.php` — dados de teste de filial.
- `database/factories/TenantAuditLogFactory.php` — dados de teste da auditoria de tenant.
- `app/Rules/Cnpj.php` — regra local de CNPJ.
- `app/Support/Tenancy/CurrentTenantResolver.php` — resolução do tenant atual e do vínculo ativo.
- `app/Support/Tenancy/TenantSettingsUpdater.php` — atualização transacional do tenant, empresa raiz e filial raiz.
- `app/Support/Tenancy/TenantAuditRecorder.php` — gravação sanitizada da auditoria cadastral.
- `app/Http/Middleware/SetCurrentTenantContext.php` — configuração do contexto PostgreSQL do usuário e tenant atual nas rotas autenticadas.
- `app/Livewire/Pages/Settings/TenantPage.php` — página Livewire de configuração do laboratório.
- `resources/views/livewire/pages/settings/tenant-page.blade.php` — formulário de configuração do laboratório.
- `tests/slice-008/TestHelpers.php` — helpers focados do slice.
- `tests/slice-008/TenantSettingsPageTest.php` — testes de acesso e happy path.
- `tests/slice-008/TenantSettingsValidationTest.php` — testes de validação e transação.
- `tests/slice-008/TenantSettingsIsolationTest.php` — testes de isolamento e payload externo.
- `tests/slice-008/TenantSettingsAuditTest.php` — testes de auditoria e não vazamento.

## Arquivos modificados

- `app/Models/Tenant.php` — fillable e relações com empresa, filial e auditoria.
- `database/factories/TenantFactory.php` — defaults cadastrais do tenant.
- `routes/web.php` — rota protegida `/settings/tenant`.
- `resources/views/layouts/app.blade.php` — link mínimo para configurações quando autenticado, se necessário para navegação do slice.
- `app/Support/Auth/TenantAccessResolver.php` — reaproveitar ou expor decisão de vínculo sem duplicar regra de status.
- `app/Support/Auth/PostgresAuthContext.php` — permitir limpar e reaplicar contexto de tenant por request autenticado.
- `tests/Pest.php` — registrar `tests/slice-008`.
- `specs/008/tasks.md` — checklist operacional do slice.

## Schema / migrations

- `2026_04_14_000200_extend_tenants_for_settings.php` adiciona `legal_name`, `document_number`, `trade_name`, `main_email`, `phone`, `operational_profile`, `emits_metrological_certificate` em `tenants`, com índice único para `document_number` quando não nulo e validação de aplicação para `basic`, `intermediate` e `accredited`.
- `2026_04_14_000210_create_companies_table.php` cria `companies` com `tenant_id`, `legal_name`, `document_number`, `trade_name`, `is_root` e unique composto `tenant_id + document_number`; em PostgreSQL, habilita RLS com `tenant_id = current_setting('app.current_tenant_id')`.
- `2026_04_14_000220_create_branches_table.php` cria `branches` com `tenant_id`, `company_id`, `name`, `document_number`, `city`, `state`, `is_root` e unique composto `tenant_id + company_id + name`; em PostgreSQL, habilita RLS com o mesmo contexto de tenant.
- `2026_04_14_000230_create_tenant_audit_logs_table.php` cria `tenant_audit_logs` com `tenant_id`, `user_id`, `action`, `changed_fields`, `ip_address`, `user_agent_hash` e timestamps; em PostgreSQL, habilita RLS para leitura/escrita no tenant atual.

## APIs / contratos

### GET `/settings/tenant`

Uso: exibe a tela de configuração do laboratório.

Autorização:
- usuário autenticado;
- 2FA concluído quando exigido;
- vínculo ativo com tenant;
- papel `gerente` para formulário editável.

Resultados:
- `200` com formulário editável para gerente em tenant `active` ou `trial`;
- `200` com visualização somente leitura para gerente em tenant `suspended`;
- `403` ou redirecionamento seguro para usuário sem papel permitido.

### Livewire action `TenantPage::save()`

Uso: salva dados do laboratório atual.

Entrada:
- `legal_name`;
- `document_number`;
- `trade_name`;
- `main_email`;
- `phone`;
- `operational_profile`;
- `emits_metrological_certificate`.

Resultados:
- sucesso com tenant, empresa raiz e filial raiz atualizados em uma transação;
- `422` para campos inválidos;
- `403` para não gerente, vínculo inválido ou tenant somente leitura;
- nenhum dado de outro tenant na mensagem.

## Riscos e mitigações

- **Cadastro parcial do laboratório** → mitigação: `TenantSettingsUpdater` usa transação e AC-012 cobre rollback total.
- **Usuário técnico alterar configurações por request direto** → mitigação: `TenantPage::save()` revalida papel `gerente`, não só o botão da tela.
- **Tenant suspenso salvar dados por chamada Livewire** → mitigação: ação `save()` verifica `tenant.access_mode` e bloqueia escrita no servidor.
- **CNPJ duplicado revelar nome de outro laboratório** → mitigação: validação retorna mensagem neutra sem citar tenant externo.
- **IDs externos no payload alterarem empresa ou filial de outro tenant** → mitigação: updater ignora ou rejeita IDs externos, sempre consulta registros pelo tenant atual e as novas tabelas escopadas usam RLS em PostgreSQL.
- **Auditoria salvar segredo herdado da sessão ou payload cru** → mitigação: `TenantAuditRecorder` grava somente campos de tenant permitidos, IP e hash de user agent.

## Dependências de outros slices

- `slice-007` — login seguro, sessão autenticada, vínculo de tenant e modo somente leitura.
- `docs/design/wireframes/wireframes-e02-auth.md` — define a tela `/settings/tenant`.
- `docs/architecture/api-contracts/api-e02-auth.md` — define ação `saveTenant()` e estados de tenant.
- `docs/architecture/data-models/erd-e02-auth.md` — define tenant, companies e branches.
- `docs/architecture/data-models/migrations-e02-auth.md` — define ordem e campos mínimos de E02.
- `docs/product/flows/flows-e02-auth.md` — define a criação inicial do laboratório.

## Fora de escopo deste plano (confirmando spec)

- Registro público de novo laboratório por visitante anônimo.
- Convite de usuários, alteração de papéis e remoção do último gerente.
- Tela de planos e limites.
- Tela de privacidade, base legal e consentimentos LGPD.
- Portal do cliente final.
- Gestão de clientes, instrumentos, padrões ou ordens de serviço.
- Cobrança real, troca de plano e emissão de nota fiscal.
- Suporte interno Kalibrium em `/admin/tenants`.
