# Plano tecnico do slice 009 — TEN-002 Usuarios, papeis e plano do laboratorio

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/009/spec.md`

---

## Decisões arquiteturais

### D1: Guardar o convite no proprio `tenant_users`, com token hash e expiracao, em vez de criar uma tabela separada de convites

**Opcoes consideradas:**
- **Opcao A:** estender `tenant_users` com `invitation_token_hash`, `invitation_expires_at` e limpeza do token apos aceite, mantendo `status=invited` ate a confirmacao final - prós: usa o modelo que o E02 ja definiu, deixa o fluxo de aceite preso ao mesmo vinculo, reduz joins e evita duplicar regras de tenant; contras: a linha de convite fica mais rica.
- **Opcao B:** criar uma tabela separada `user_invitations` - prós: separa convite de vinculacao final; contras: adiciona outra entidade, outra migration e outra fonte de verdade para um fluxo que o slice precisa fechar agora.

**Escolhida:** Opcao A.

**Razao:** o E02 ja trouxe `tenant_users` como o lugar natural do vinculo do usuario no tenant. Como o slice precisa convidar, aceitar e bloquear convites expirados/usados, manter tudo no mesmo registro reduz risco de inconsistencias sem mudar a estrategia de identidade ou tenancy.

**Reversibilidade:** media.

**ADR:** nao requer ADR novo.

### D2: Tratar `/settings/plans` como leitura permissiva por permissao e restringir somente a acao de upgrade ao gerente

**Opcoes consideradas:**
- **Opcao A:** permitir `GET /settings/plans` para qualquer usuario autenticado do tenant com permissao de leitura, e restringir `requestUpgrade()` ao gerente - prós: atende o AC-018, deixa o plano visivel para perfis nao gerente e separa leitura de acao; contras: precisa de dois niveis de autorizacao na mesma tela.
- **Opcao B:** bloquear toda a tela para nao gerente - prós: mais simples; contras: conflita com o AC-018, que pede visualizacao basica do plano para usuario nao gerente autorizado.

**Escolhida:** Opcao A.

**Razao:** o contrato do E02 e o spec deste slice pedem que o plano seja visivel para mais de um perfil, mas que o upgrade continue exclusivo do gerente. Separar leitura de acao evita esconder informacao util sem abrir permissao de mudanca.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

### D3: Montar a visao de plano com um service de resumo e um atualizador minimo de uso do tenant

**Opcoes consideradas:**
- **Opcao A:** criar `PlanSummaryService`, `TenantPlanMetricsUpdater` e um read model `tenant_plan_metrics` para usuarios, OS/mensal e armazenamento - prós: entrega valores deterministicos para os alertas de 80% e 95%, evita calculo espalhado na view, define um caminho explicito de refresh e deixa o plan page preparado para os proximos slices; contras: adiciona uma tabela pequena de suporte e um service de atualizacao.
- **Opcao B:** calcular tudo direto na Blade ou em queries soltas dentro da pagina - prós: menos arquivos; contras: mistura apresentacao com regra, dificulta teste e aumenta risco de divergencia entre metricas.

**Escolhida:** Opcao A.

**Razao:** a tela de planos precisa ser previsivel e testavel. Um resumo unico consegue combinar `subscriptions`, `plan_entitlements`, `tenant_entitlements`, `tenant_users` ativos e um read model de uso sem inventar billing nem dependencia externa. O atualizador roda antes de montar o resumo, recalcula `users_used` a partir dos vinculos ativos do tenant, cria a linha quando ausente e preserva `monthly_os_used`/`storage_used_bytes` como contadores explicitamente atualizados por este ou por slices futuros.

**Reversibilidade:** media.

**ADR:** nao requer ADR novo.

### D4: Registrar pedidos de upgrade em tabela propria, nao apenas em auditoria

**Opcoes consideradas:**
- **Opcao A:** criar `plan_upgrade_requests` com tenant, usuario, feature e justificativa sanitizada - prós: o pedido fica rastreavel para acompanhamento, permite status futuro e nao mistura pedido funcional com log de auditoria; contras: mais uma tabela pequena.
- **Opcao B:** gravar o pedido somente em `tenant_audit_logs` ou log de aplicacao - prós: menos schema; contras: dificulta acompanhamento operacional e reduz a qualidade do sinal para as proximas etapas do produto.

**Escolhida:** Opcao A.

**Razao:** o spec pede registrar a solicitacao para acompanhamento. Auditoria continua existindo, mas o pedido de upgrade precisa ter vida propria para ser consultado depois sem depender de log bruto.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

## Sequencia de implementacao

### Task 1: Fechar o modelo de dados e os servicos de dominio do slice

**Files:**
- Create: `database/migrations/2026_04_14_000300_extend_tenant_users_for_invitations.php`
- Create: `database/migrations/2026_04_14_000310_create_tenant_plan_metrics_table.php`
- Create: `database/migrations/2026_04_14_000320_create_plan_upgrade_requests_table.php`
- Create: `app/Models/PlanUpgradeRequest.php`
- Create: `app/Models/TenantPlanMetric.php`
- Modify: `app/Models/TenantUser.php`
- Modify: `app/Models/Tenant.php`
- Create: `app/Support/Settings/UserInvitationService.php`
- Create: `app/Support/Settings/UserRoleService.php`
- Create: `app/Support/Settings/UserDeactivationService.php`
- Create: `app/Support/Settings/UsersDirectoryQuery.php`
- Create: `app/Support/Settings/PlanSummaryService.php`
- Create: `app/Support/Settings/TenantPlanMetricsUpdater.php`
- Create: `app/Support/Settings/PlanUpgradeRequestService.php`
- Create: `database/factories/PlanUpgradeRequestFactory.php`
- Create: `database/factories/TenantPlanMetricFactory.php`
- Modify: `tests/Pest.php`

- [ ] **Step 1: escrever os testes RED do dominio**

Criar testes focados para convite, aceite, troca de papel, desativacao, visao de plano, alertas de uso e pedido de upgrade, todos ainda vermelhos antes da implementacao. Cobrir tambem os casos de tenant diferente, token expirado e payload sanitizado.

- [ ] **Step 2: estender o vinculo do usuario**

Adicionar ao `tenant_users` os campos de convite que faltam para o aceite seguro: hash do token e expiracao do convite. Manter `status`, `invited_at`, `accepted_at` e `requires_2fa` como a fonte de verdade do ciclo de vida do vinculo.

- [ ] **Step 3: criar o read model de uso do plano**

Adicionar `tenant_plan_metrics` com `tenant_id`, usuarios usados, OS do mes, armazenamento usado e data do ultimo calculo. Esse read model nao substitui `subscriptions` nem `tenant_entitlements`; ele so alimenta a tela de planos e os alertas de 80%/95%.

- [ ] **Step 4: criar o atualizador de metricas do plano**

Criar `TenantPlanMetricsUpdater` com `refreshForTenant(Tenant $tenant)`: cria a linha quando ausente, recalcula `users_used` contando vinculos ativos do tenant, mantem `monthly_os_used` e `storage_used_bytes` em zero quando ainda nao houver origem operacional, e preserva valores existentes quando testes ou slices futuros ja os tiverem atualizado. `PlanSummaryService` deve chamar esse refresh antes de calcular percentuais e alertas.

- [ ] **Step 5: criar o registro de pedidos de upgrade**

Adicionar `plan_upgrade_requests` com tenant, usuario, feature, justificativa, status e timestamps, com persistencia em tenant correto e sem salvar HTML/JS/SQL cru.

- [ ] **Step 6: implementar os servicos de dominio**

Centralizar a criacao de convite, a ativacao de papel, a desativacao do vinculo, o resumo do plano e o pedido de upgrade em services dedicados. Esses services fazem a validacao de tenant, aplicam a regra do ultimo gerente, sanitizam entrada livre e mantem as mensagens de erro neutras.

### Task 2: Construir a tela `/settings/users`

**Files:**
- Create: `app/Livewire/Pages/Settings/UsersPage.php`
- Create: `resources/views/livewire/pages/settings/users-page.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Support/Auth/TenantAccessResolver.php`
- Modify: `app/Support/Settings/UsersDirectoryQuery.php`
- Modify: `app/Support/Settings/UserInvitationService.php`
- Modify: `app/Support/Settings/UserRoleService.php`
- Modify: `app/Support/Settings/UserDeactivationService.php`

- [ ] **Step 1: escrever os testes RED de listagem e autorizacao**

Cobrir `GET /settings/users` para gerente com 2FA concluido, gerente sem 2FA, usuario nao gerente, tenant `suspended` em leitura e usuario sem vinculo ativo. Cobrir filtro por nome, e-mail e papel, e validar que o resultado permanece no tenant atual.

- [ ] **Step 2: entregar a listagem com filtros**

Exibir nome, e-mail, papel, status e obrigatoriedade de 2FA. Implementar busca textual e filtro por papel no estado da pagina, com paginacao e sem expor usuarios de outro tenant.

- [ ] **Step 3: entregar convite de usuario**

Permitir criar vinculo pendente para usuario novo ou usuario ja existente, sempre no tenant atual e na empresa/filial permitidas. O service normaliza `requires_2fa` para `gerente` e `administrativo`, bloqueia duplicidade de e-mail no mesmo tenant e registra auditoria sem expor senha ou token.

- [ ] **Step 4: entregar troca de papel e desativacao**

Permitir mudar papel entre `gerente`, `tecnico`, `administrativo` e `visualizador`, e desativar vinculo sem remover o ultimo gerente ativo. O service precisa revalidar o vinculo atual no servidor antes de salvar.

- [ ] **Step 5: respeitar modo somente leitura**

Em tenant `suspended`, a tela continua visivel para leitura permitida, mas as acoes mutaveis retornam erro seguro e nao alteram dados.

### Task 3: Construir o aceite publico do convite

**Files:**
- Create: `app/Livewire/Pages/Auth/AcceptInvitationPage.php`
- Create: `resources/views/livewire/pages/auth/accept-invitation-page.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Support/Settings/UserInvitationService.php`
- Modify: `app/Models/TenantUser.php`

- [ ] **Step 1: escrever os testes RED do aceite**

Cobrir convite valido, convite expirado, convite ja usado, convite de outro tenant, senha curta, confirmacao divergente e retorno apos aceite. O teste precisa provar que o vinculo continua pendente quando a validacao falha.

- [ ] **Step 2: validar o token sem vazar contexto**

Aceitar o token por hash, conferir expiracao e status do vinculo, e bloquear qualquer convite que nao bata com o tenant esperado. Em erro, a mensagem deve orientar um novo convite sem revelar detalhes do outro tenant.

- [ ] **Step 3: concluir o aceite**

Gravar `accepted_at`, mudar `status` para ativo, limpar o token armazenado e permitir login somente dentro do tenant, empresa e filial vinculados.

- [ ] **Step 4: manter a senha segura**

Exigir senha com no minimo 12 caracteres e confirmacao igual. Nunca salvar senha, token ou segredo em auditoria ou resposta.

### Task 4: Construir a tela `/settings/plans`

**Files:**
- Create: `app/Livewire/Pages/Settings/PlansPage.php`
- Create: `resources/views/livewire/pages/settings/plans-page.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Support/Settings/PlanSummaryService.php`
- Modify: `app/Support/Settings/TenantPlanMetricsUpdater.php`
- Modify: `app/Support/Settings/PlanUpgradeRequestService.php`
- Modify: `app/Models/PlanUpgradeRequest.php`
- Modify: `app/Models/TenantPlanMetric.php`

- [ ] **Step 1: escrever os testes RED de resumo e limites**

Cobrir `GET /settings/plans` para gerente com 2FA concluido, gerente sem 2FA, usuario nao gerente autorizado, tenant `suspended` e usuario sem permissao de leitura. Validar plano atual, status, limites, uso de usuarios, uso de OS no mes, armazenamento, percentual usado e status dos modulos.

- [ ] **Step 2: entregar autorizacao de leitura da tela**

Garantir que gerente sem 2FA concluido seja bloqueado na tela de planos, enquanto usuario nao gerente com permissao de visualizacao pode ver informacoes basicas sem botao de upgrade. A regra precisa ficar no servidor e nao apenas na view.

- [ ] **Step 3: entregar o resumo do plano**

Montar o resumo a partir de `subscriptions`, `plan_entitlements`, `tenant_entitlements`, `tenant_users` ativos e `tenant_plan_metrics`. Antes de ler o resumo, chamar `TenantPlanMetricsUpdater::refreshForTenant()` para recalcular `users_used`, criar metricas ausentes e manter OS/armazenamento com valor controlado. O service calcula o percentual por limite e expõe os alertas leve e forte sem concentrar regra na view.

- [ ] **Step 4: entregar o pedido de upgrade**

Permitir que apenas o gerente peça upgrade para um modulo fora do plano, salvando a solicitacao com feature, justificativa e tenant corretos. Usuarios nao gerente veem o resumo basico, mas nao veem o botao de upgrade.

- [ ] **Step 5: manter a tela consistente com tenant suspenso**

Em tenant `suspended`, a leitura continua disponivel quando autorizada, mas o pedido de upgrade fica bloqueado no servidor.

### Task 5: Fechar regressao, seguranca e validacao do slice

**Files:**
- Create: `tests/slice-009/TestHelpers.php`
- Create: `tests/slice-009/UsersPageTest.php`
- Create: `tests/slice-009/UsersInviteTest.php`
- Create: `tests/slice-009/UsersInviteAcceptanceTest.php`
- Create: `tests/slice-009/UsersRoleTest.php`
- Create: `tests/slice-009/UsersDeactivateTest.php`
- Create: `tests/slice-009/PlansPageTest.php`
- Create: `tests/slice-009/PlanUpgradeRequestTest.php`
- Create: `tests/slice-009/UsersPlansSecurityTest.php`
- Modify: `tests/Pest.php`

- [ ] **Step 1: registrar o diretorio do slice no Pest**

Adicionar `slice-009` ao bootstrap de testes para manter o slice autocontido.

- [ ] **Step 2: cobrir todos os ACs numericos**

Garantir pelo menos um teste por AC-001 a AC-018, incluindo os caminhos felizes, erros, estados somente leitura e bloqueio do ultimo gerente.

- [ ] **Step 3: cobrir os ACs de seguranca**

Verificar que HTML, JavaScript e payload SQL sao tratados como dado, que a auditoria nao grava senha/token/segredo e que cada tenant ve apenas seus proprios usuarios e planos.

- [ ] **Step 4: validar o slice com comandos focados**

Rodar os testes do slice e depois a validacao mecanica dos arquivos tocados, sem suite full nesta etapa.

Comandos previstos:
```bash
php artisan test tests/slice-009
vendor/bin/pint --test app/Models app/Support/Settings app/Livewire/Pages/Settings app/Livewire/Pages/Auth database/migrations database/factories resources/views/livewire/pages/settings resources/views/livewire/pages/auth routes/web.php tests/slice-009 tests/Pest.php
vendor/bin/phpstan analyse app/Models app/Support/Settings app/Livewire/Pages/Settings app/Livewire/Pages/Auth database/migrations database/factories --level=8 --no-progress
```

---

## Mapeamento AC -> arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `routes/web.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `resources/views/livewire/pages/settings/users-page.blade.php`, `app/Support/Settings/UsersDirectoryQuery.php`, `tests/Pest.php`, `tests/slice-009/TestHelpers.php` | `tests/slice-009/UsersPageTest.php` |
| AC-002 | `app/Support/Settings/UserInvitationService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Models/TenantUser.php`, `database/migrations/2026_04_14_000300_extend_tenant_users_for_invitations.php` | `tests/slice-009/UsersInviteTest.php` |
| AC-003 | `app/Livewire/Pages/Auth/AcceptInvitationPage.php`, `resources/views/livewire/pages/auth/accept-invitation-page.blade.php`, `routes/web.php`, `app/Support/Settings/UserInvitationService.php` | `tests/slice-009/UsersInviteAcceptanceTest.php` |
| AC-004 | `app/Support/Settings/UserRoleService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersRoleTest.php` |
| AC-005 | `app/Support/Settings/UserDeactivationService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersDeactivateTest.php` |
| AC-006 | `routes/web.php`, `app/Livewire/Pages/Settings/PlansPage.php`, `resources/views/livewire/pages/settings/plans-page.blade.php`, `app/Support/Settings/PlanSummaryService.php`, `app/Models/Tenant.php` | `tests/slice-009/PlansPageTest.php` |
| AC-007 | `app/Support/Settings/PlanUpgradeRequestService.php`, `app/Models/PlanUpgradeRequest.php`, `app/Livewire/Pages/Settings/PlansPage.php`, `database/factories/PlanUpgradeRequestFactory.php` | `tests/slice-009/PlanUpgradeRequestTest.php` |
| AC-008 | `routes/web.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Support/Settings/UserInvitationService.php`, `app/Support/Settings/UserRoleService.php`, `app/Support/Settings/UserDeactivationService.php` | `tests/slice-009/UsersPageTest.php` |
| AC-009 | `routes/web.php`, `app/Support/Auth/TenantAccessResolver.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Support/Settings/UserInvitationService.php`, `app/Support/Settings/UserRoleService.php`, `app/Support/Settings/UserDeactivationService.php` | `tests/slice-009/UsersPageTest.php` |
| AC-010 | `app/Support/Settings/UserInvitationService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersInviteTest.php` |
| AC-011 | `app/Support/Settings/UserInvitationService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersInviteTest.php` |
| AC-012 | `app/Support/Settings/UserRoleService.php`, `app/Support/Settings/UserDeactivationService.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersRoleTest.php` + `tests/slice-009/UsersDeactivateTest.php` |
| AC-013 | `app/Support/Settings/UsersDirectoryQuery.php`, `app/Support/Settings/UserInvitationService.php`, `app/Support/Settings/PlanSummaryService.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Livewire/Pages/Settings/PlansPage.php` | `tests/slice-009/UsersPlansSecurityTest.php` |
| AC-014 | `routes/web.php`, `app/Livewire/Pages/Settings/UsersPage.php`, `app/Livewire/Pages/Settings/PlansPage.php`, `app/Support/Auth/TenantAccessResolver.php` | `tests/slice-009/UsersPageTest.php` + `tests/slice-009/PlansPageTest.php` |
| AC-015 | `app/Livewire/Pages/Auth/AcceptInvitationPage.php`, `app/Support/Settings/UserInvitationService.php`, `app/Models/TenantUser.php` | `tests/slice-009/UsersInviteAcceptanceTest.php` |
| AC-016 | `app/Livewire/Pages/Auth/AcceptInvitationPage.php`, `resources/views/livewire/pages/auth/accept-invitation-page.blade.php`, `app/Support/Settings/UserInvitationService.php` | `tests/slice-009/UsersInviteAcceptanceTest.php` |
| AC-017 | `app/Support/Settings/PlanSummaryService.php`, `app/Support/Settings/TenantPlanMetricsUpdater.php`, `app/Models/TenantPlanMetric.php`, `database/migrations/2026_04_14_000310_create_tenant_plan_metrics_table.php`, `database/factories/TenantPlanMetricFactory.php`, `app/Livewire/Pages/Settings/PlansPage.php` | `tests/slice-009/PlansPageTest.php` |
| AC-018 | `routes/web.php`, `app/Livewire/Pages/Settings/PlansPage.php`, `app/Support/Settings/PlanSummaryService.php` | `tests/slice-009/PlansPageTest.php` |
| AC-SEC-001 | `app/Support/Settings/UsersDirectoryQuery.php`, `app/Support/Settings/UserInvitationService.php`, `app/Support/Settings/PlanUpgradeRequestService.php`, `resources/views/livewire/pages/settings/users-page.blade.php`, `resources/views/livewire/pages/settings/plans-page.blade.php` | `tests/slice-009/UsersPlansSecurityTest.php` |
| AC-SEC-002 | `app/Support/Settings/UserInvitationService.php`, `app/Support/Settings/UserRoleService.php`, `app/Support/Settings/UserDeactivationService.php`, `app/Support/Settings/PlanUpgradeRequestService.php`, `app/Models/PlanUpgradeRequest.php` | `tests/slice-009/UsersPlansSecurityTest.php` |
| AC-SEC-003 | `app/Support/Settings/UsersDirectoryQuery.php`, `app/Support/Settings/PlanSummaryService.php`, `app/Models/TenantPlanMetric.php`, `database/migrations/2026_04_14_000310_create_tenant_plan_metrics_table.php`, `database/migrations/2026_04_14_000320_create_plan_upgrade_requests_table.php` | `tests/slice-009/UsersPlansSecurityTest.php` |

## Novos arquivos

- `database/migrations/2026_04_14_000300_extend_tenant_users_for_invitations.php` — adiciona hash e expiracao do convite ao vinculo do usuario.
- `database/migrations/2026_04_14_000310_create_tenant_plan_metrics_table.php` — read model de uso do plano por tenant.
- `database/migrations/2026_04_14_000320_create_plan_upgrade_requests_table.php` — pedidos de upgrade com rastreabilidade.
- `app/Models/PlanUpgradeRequest.php` — registro persistido do pedido de upgrade.
- `app/Models/TenantPlanMetric.php` — snapshot de uso do plano por tenant.
- `app/Support/Settings/UserInvitationService.php` — cria convite, valida aceite e finaliza vinculo.
- `app/Support/Settings/UserRoleService.php` — troca papel e recalcula `requires_2fa`.
- `app/Support/Settings/UserDeactivationService.php` — desativa vinculo e protege o ultimo gerente.
- `app/Support/Settings/UsersDirectoryQuery.php` — consulta filtrada da lista de usuarios.
- `app/Support/Settings/PlanSummaryService.php` — resumo do plano, limites, uso e alertas.
- `app/Support/Settings/TenantPlanMetricsUpdater.php` — cria e atualiza metricas de uso antes do resumo.
- `app/Support/Settings/PlanUpgradeRequestService.php` — cria e sanitiza pedido de upgrade.
- `app/Livewire/Pages/Settings/UsersPage.php` — pagina de lista e gestao de usuarios.
- `app/Livewire/Pages/Settings/PlansPage.php` — pagina de visao do plano e upgrade.
- `app/Livewire/Pages/Auth/AcceptInvitationPage.php` — pagina publica de aceite do convite.
- `resources/views/livewire/pages/settings/users-page.blade.php` — UI da lista e acoes de usuarios.
- `resources/views/livewire/pages/settings/plans-page.blade.php` — UI do plano, limites e CTA.
- `resources/views/livewire/pages/auth/accept-invitation-page.blade.php` — UI do aceite do convite.
- `database/factories/PlanUpgradeRequestFactory.php` — fixtures de teste para pedidos de upgrade.
- `database/factories/TenantPlanMetricFactory.php` — fixtures de teste para metricas do plano.
- `tests/slice-009/TestHelpers.php` — utilitarios do slice.
- `tests/slice-009/UsersPageTest.php` — cobertura de listagem, filtro e autorizacao.
- `tests/slice-009/UsersInviteTest.php` — cobertura de convite e validacoes.
- `tests/slice-009/UsersInviteAcceptanceTest.php` — cobertura de aceite de convite.
- `tests/slice-009/UsersRoleTest.php` — cobertura de troca de papel.
- `tests/slice-009/UsersDeactivateTest.php` — cobertura de desativacao.
- `tests/slice-009/PlansPageTest.php` — cobertura do resumo do plano e alertas.
- `tests/slice-009/PlanUpgradeRequestTest.php` — cobertura do pedido de upgrade.
- `tests/slice-009/UsersPlansSecurityTest.php` — cobertura dos ACs de seguranca.

## Arquivos modificados

- `app/Models/TenantUser.php` — ciclo de vida do convite, status e relacoes do vinculo.
- `app/Models/Tenant.php` — relacoes com metricas e pedidos de upgrade.
- `routes/web.php` — rotas `/settings/users`, `/settings/plans` e `/auth/invitations/{token}`.
- `tests/Pest.php` — registrar `slice-009`.
- `app/Support/Auth/TenantAccessResolver.php` — reforcar leitura permissiva do plano e bloqueios do tenant.
- `app/Support/Settings/UsersDirectoryQuery.php` — busca, filtros e isolamento por tenant.
- `app/Support/Settings/UserInvitationService.php` — criacao e aceite seguros do convite.
- `app/Support/Settings/UserRoleService.php` — troca de papel com regra do ultimo gerente.
- `app/Support/Settings/UserDeactivationService.php` — desativacao segura do vinculo.
- `app/Support/Settings/PlanSummaryService.php` — resumo do plano e calculo de alertas.
- `app/Support/Settings/TenantPlanMetricsUpdater.php` — refresh dos contadores de uso do plano.
- `app/Support/Settings/PlanUpgradeRequestService.php` — pedido de upgrade sanitizado.

## Schema / migrations

- `2026_04_14_000300_extend_tenant_users_for_invitations.php` adiciona `invitation_token_hash` e `invitation_expires_at` em `tenant_users`, mantendo `status`, `invited_at`, `accepted_at` e `requires_2fa` como ciclo de vida do vinculo.
- `2026_04_14_000310_create_tenant_plan_metrics_table.php` cria `tenant_plan_metrics` com `tenant_id`, `users_used`, `monthly_os_used`, `storage_used_bytes` e `sampled_at`, com unique por tenant e RLS em PostgreSQL. `TenantPlanMetricsUpdater` popula a linha quando ausente, recalcula `users_used` a partir de `tenant_users` ativos e preserva OS/armazenamento como contadores controlados ate os slices operacionais passarem a atualiza-los.
- `2026_04_14_000320_create_plan_upgrade_requests_table.php` cria `plan_upgrade_requests` com `tenant_id`, `user_id`, `feature_code`, `justification`, `status`, `requested_at` e timestamps, com indice por tenant e feature e RLS em PostgreSQL.
- As migrations novas mantem `tenant_id` obrigatorio em tabelas escopadas e usam a politica de isolamento do E02 ja adotada no projeto.
- `subscriptions`, `plan_entitlements`, `tenant_entitlements` e `features` sao reutilizadas como base de plano e modulo; este slice nao cria outro sistema de cobranca.

## APIs / contratos

### GET `/settings/users`
Uso: exibe a lista de usuarios do tenant atual.

Autorizacao:
- usuario autenticado;
- tenant atual resolvido;
- leitura permitida mesmo em tenant `suspended` quando autorizada;
- escrita restrita a gerente com 2FA concluido.

Resultados:
- `200` com lista filtrada por nome, e-mail e papel;
- `403` para usuario sem permissao de leitura administrativa;
- `403` ou erro seguro para tenant ou vinculo fora do escopo.

### Livewire action `inviteUser()`
Uso: cria convite e vinculo pendente.

Entrada:
- `name`;
- `email`;
- `role`;
- `company_id`;
- `branch_id`;
- `requires_2fa` normalizado pelo servidor quando o papel for `gerente` ou `administrativo`.

Resultados:
- vinculo pendente criado no tenant atual;
- convite enviado;
- auditoria registrada;
- `422` para validacao;
- `403` para leitura somente ou sem permissao;
- `409` para duplicidade de e-mail no mesmo tenant.

### Livewire action `updateRole(tenantUserId, role)`
Uso: altera o papel de um vinculo ativo.

Resultados:
- atualiza papel e `requires_2fa`;
- bloqueia alteracao do ultimo gerente ativo;
- `403` para usuario fora do tenant atual;
- `409` para estado invalido.

### Livewire action `deactivateUser(tenantUserId)`
Uso: desativa ou remove um vinculo do tenant.

Resultados:
- bloqueia o ultimo gerente ativo;
- impede novo acesso daquele vinculo;
- registra auditoria;
- `403` para usuario fora do tenant atual;
- `409` para conflito de estado.

### GET `/auth/invitations/{token}`
Uso: exibe a pagina publica de aceite do convite.

Resultados:
- `200` para convite valido;
- `403` ou `404` seguro para convite de outro tenant, expirado ou ja usado.

### POST `/auth/invitations/{token}`
Uso: define senha e conclui o aceite.

Entrada:
- `password`;
- `password_confirmation`.

Resultados:
- `302` para `/auth/login` com confirmacao;
- `422` para senha fraca ou confirmacao divergente;
- `403` ou `404` seguro para token invalido, expirado ou usado.

### GET `/settings/plans`
Uso: exibe o plano atual, limites, uso e status dos modulos.

Autorizacao:
- usuario autenticado do tenant;
- gerente precisa ter 2FA concluido para acessar como gerente;
- leitura liberada para quem tem permissao de visualizacao do plano;
- upgrade restrito ao gerente.

Resultados:
- `200` com resumo do plano e alertas de uso;
- `403` para usuario sem permissao de leitura.

### Livewire action `requestUpgrade(featureCode, justification)`
Uso: registra pedido de upgrade de modulo.

Resultados:
- grava pedido no tenant atual;
- confirma o pedido sem cobrar nada;
- `403` para nao gerente ou tenant em modo somente leitura;
- `422` para feature inexistente ou justificativa invalida.

## Riscos e mitigações

- **Convite pode ser reutilizado ou aplicado em tenant errado** -> mitigacao: token hash no `tenant_users`, expiracao curta, limpeza do token no aceite e validacao de tenant antes de salvar.
- **Ultimo gerente pode ficar removido por troca de papel ou desativacao** -> mitigacao: regra de dominio centralizada em `UserRoleService` e `UserDeactivationService`, com transacao e bloqueio do registro alvo.
- **Tela de planos pode esconder ou mostrar botao errado para nao gerente** -> mitigacao: a tela faz leitura permissiva, mas `requestUpgrade()` e o CTA ficam protegidos por permissao separada.
- **Alertas de 80% e 95% podem variar se o calculo ficar espalhado na view** -> mitigacao: `PlanSummaryService` concentra percentuais e severidade em um unico retorno e chama `TenantPlanMetricsUpdater` antes de calcular.
- **Uso de OS e armazenamento nao existe ainda em outros slices** -> mitigacao: `tenant_plan_metrics` guarda o read model minimo deste slice sem criar billing real; `TenantPlanMetricsUpdater` garante linha existente, recalcula usuarios ativos e preserva OS/armazenamento controlados ate existirem fontes operacionais definitivas.
- **Payloads livres podem refletir HTML, JavaScript ou SQL** -> mitigacao: validacao, query parametrizada, escape nas views e sanitizacao no service de upgrade e no service de convite.
- **Dados de outro tenant podem aparecer por filtro ou parametro manual** -> mitigacao: toda consulta passa por tenant atual resolvido no servidor e por isolamento de tabela onde houver RLS.

## Dependencias de outros slices

- `slice-007` — login seguro, 2FA, sessao e base de identidade.
- `slice-008` — tenant, empresa raiz, filial raiz, modo somente leitura e contexto do laboratorio.
- `docs/adr/0001-stack-choice.md` — stack base do MVP.
- `docs/adr/0004-estrategia-de-identidade-e-autenticacao.md` — Fortify + Sanctum como identidade do MVP.
- `docs/architecture/api-contracts/api-e02-auth.md` — contratos de usuarios, planos e aceite de convite.
- `docs/architecture/data-models/erd-e02-auth.md` — relacoes de tenant, vinculo, planos e auditoria.
- `docs/architecture/data-models/migrations-e02-auth.md` — ordem e principios do schema E02.
- `docs/product/flows/flows-e02-auth.md` — jornada de login, convite, papel e plano.

## Fora de escopo deste plano (confirmando spec)

- Cobranca real, pagamento, nota fiscal, upgrade automatico, downgrade e pro-rata.
- Console interno de suporte Kalibrium para alterar plano de tenants.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Tela de privacidade, base legal LGPD e opt-out.
- Cadastro de clientes, instrumentos, padroes, ordens de servico ou certificados.
- Mudancas em outras telas do produto fora de `/settings/users`, `/settings/plans` e do aceite do convite.
- Qualquer permissao fina alem dos papeis canonicamente definidos e dos bloqueios basicos deste slice.
