# Auditoria do que sobra do sistema antigo

> **Data:** 2026-05-02
> **Por que existe este documento:** o roadmap antigo previa um trabalho chamado "INF-007" pra mapear o que do sistema atual ainda é útil depois do reset de abril/2026. O frontend (telas) antigo foi descartado e vai ser refeito como app no celular (que funciona offline). Antes de começar a refazer, eu precisava saber o que do **bastidor** (backend, banco, regras de validação) sobrou e dá pra reusar.

---

## Resumo em uma página (pra Roldão)

### O que está pronto e funciona

-   **Cadastro de clientes** — pessoa física e jurídica, com CPF/CNPJ validados, endereço, telefone, e-mail, regime tributário, limite de crédito. Funciona, tem teste, e respeita o isolamento entre laboratórios.
-   **Cadastro de contatos do cliente** — nome, papel (financeiro / técnico / etc.), e-mail OU WhatsApp (pelo menos um), marcador de contato principal. Funciona, tem teste.
-   **Multi-laboratório (cada laboratório vê só os dados dele)** — está implementado em duas camadas de proteção: (1) toda consulta filtra automaticamente por laboratório; (2) o próprio banco de dados tem uma trava de segurança extra. Tem suíte de testes específica que prova que dados de um laboratório nunca aparecem pra outro.
-   **Login e usuários** — entrar no sistema, autenticação em duas etapas (2FA), convite de novo usuário, papéis (gerente, técnico, administrativo, visualizador). Tem registro imutável de cada login (quem entrou, quando, de onde).
-   **Consentimento LGPD** — cada cliente tem registro de quais dados pessoais consentiu fornecer e por qual canal (e-mail, WhatsApp). Esse registro é **imutável** — uma vez salvo não pode ser apagado nem alterado, só revogado (que vira um novo registro).
-   **Planos e limites** — controle de qual plano cada laboratório está, métricas de uso, solicitação de upgrade.
-   **Verificação de saúde** — endereço técnico que responde "estou vivo" pra monitoramento.

### O que NÃO existe ainda (precisa ser feito)

-   **Nada do app móvel** — a primeira história a ser feita é justamente "técnico consegue entrar no app do celular".
-   **Cadastro de instrumentos do cliente** (paquímetro, balança, manômetro, etc.) — REQ-MET-002 do MVP, ainda não começou.
-   **Cadastro de padrões de referência** — REQ-MET-001, ainda não começou.
-   **Ordem de serviço** — REQ-MET-003, ainda não começou.
-   **Execução da calibração** (com pontos medidos, padrões usados, condições do ambiente) — REQ-MET-004, ainda não começou.
-   **Cálculo de incerteza** — REQ-MET-005, ainda não começou.
-   **Emissão de certificado** — REQ-MET-006, ainda não começou.
-   **Toda a parte fiscal** (NFS-e, retenções, conta a receber) — não começou.
-   **Toda a operação de campo** (UMC, veículos, despesas, fotos, assinaturas, GPS) — não começou.
-   **CRM do vendedor** — não começou.
-   **Sincronização e resolução de conflito offline** — não começou.

### Principais pontos de atenção

1. **Tela velha foi descartada de verdade.** As rotas antigas que apontavam pra Livewire/Blade foram removidas. Sobrou só a verificação de saúde técnica. Isso é coerente com o ADR-0015 (decisão de mudar pra app móvel).
2. **Atenção:** os controladores de Cliente, Contato e Configurações do laboratório **existem em código mas estão desconectados** — não tem nenhuma rota apontando pra eles agora. Eles vão precisar ser re-registrados como API (em `routes/api.php` com autenticação por token) quando o app móvel começar a consumir. Trabalho previsto pra E15-S02.
3. **O spike INF-007 antigo produziu dois documentos substantivos que já existem** (`docs/frontend/api-endpoints.md` com inventário dos endpoints e `docs/frontend/stack-versions.md` com versões dos pacotes do app). O `api-endpoints.md` foi escrito em abr/2026 e referencia rotas Livewire que já não existem mais — o **inventário de controllers continua válido**, mas as URLs precisam ser revalidadas em E15-S02.
4. **Tem uma área chamada "Domínio" planejada mas vazia.** Ela foi prevista pra organizar regras de negócio mais complexas no futuro. Por enquanto as regras estão dentro dos modelos e dos controladores. Isso não é um problema agora, é uma escolha que pode ser revisitada quando a complexidade aumentar.
5. **Uma tela específica precisa de pequeno ajuste pra virar API.** A tela de "configurações do laboratório" ainda devolve resposta no formato antigo (HTML). Quando o app móvel for consumir isso, vai precisar virar resposta no formato JSON. Trabalho pequeno, ~1h.
6. **Ambiente local do Roldão não tem o conector do banco PostgreSQL.** Por isso eu não consigo rodar a suíte de testes daqui agora. O servidor de validação automática (CI) tem tudo certo e funciona. Decisão pendente: instalar o conector aqui agora ou deixar pra resolver depois.
7. **Tinha 5 testes pendurados que apontavam pra estrutura de pastas que não existia mais** (eram do antigo "spike INF-007" que ficou no meio do caminho no reset). Já apaguei — não estavam protegendo nada.

---

## Detalhe técnico (pra mim e desenvolvedores futuros)

### 1. Modelos Eloquent (`app/Models/`) — 16 modelos

| Modelo               | Multi-tenant                                | Soft-delete                                  | Observações                                                           |
| -------------------- | ------------------------------------------- | -------------------------------------------- | --------------------------------------------------------------------- |
| `Cliente`            | Sim (`tenant_id` + `ScopesToCurrentTenant`) | Sim                                          | created_by/updated_by → TenantUser                                    |
| `Contato`            | Sim                                         | Sim                                          | FK para Cliente; created_by/updated_by                                |
| `Tenant`             | N/A                                         | Não                                          | Raiz do isolamento                                                    |
| `User`               | Não (global)                                | Não                                          | E-mail único global; vinculado a 1+ tenants via TenantUser            |
| `TenantUser`         | Sim                                         | Não                                          | Vínculo + RBAC (campo `role`); suporte a 2FA + convites com expiração |
| `Role`               | Não                                         | Não                                          | Tabela de lookup                                                      |
| `Company`            | Implícito (via tenant_id)                   | Não                                          | Filial opcional                                                       |
| `Branch`             | Implícito                                   | Não                                          | Unidade operacional                                                   |
| `ConsentRecord`      | Sim                                         | Não — bloqueado por trigger PG (append-only) | Registro imutável de consentimento LGPD                               |
| `ConsentSubject`     | Sim                                         | Sim                                          | Pessoa titular de dado                                                |
| `LgpdCategory`       | Não (global)                                | Não                                          | Categorias canônicas (contato, financeiro, etc.)                      |
| `RevocationToken`    | Sim                                         | Não                                          | Token p/ revogar consentimento via link                               |
| `TenantAuditLog`     | Sim                                         | Não — append-only via trigger                | Eventos críticos do tenant                                            |
| `LoginAuditLog`      | Não (vínculo via user)                      | Não                                          | Cada tentativa de login                                               |
| `TenantPlanMetric`   | Sim                                         | Não                                          | Uso vs. limite do plano                                               |
| `PlanUpgradeRequest` | Sim                                         | Não                                          | Pedido de upgrade pendente                                            |

### 2. Controladores (`app/Http/Controllers/`) — 5 controllers

| Controller                              | Endpoints                 | Métodos                             | Policy                 | Form Request                 |
| --------------------------------------- | ------------------------- | ----------------------------------- | ---------------------- | ---------------------------- |
| `ClienteController`                     | `/clientes`               | index, show, store, update, destroy | `ClientePolicy`        | List/Store/Update            |
| `ContatoController`                     | `/clientes/{id}/contatos` | index, show, store, update, destroy | `ContatoPolicy`        | Store/Update                 |
| `TenantSettingsController`              | `/settings/tenant` (PUT)  | \_\_invoke                          | `TenantSettingsPolicy` | Validação inline (refatorar) |
| `HealthCheckController`                 | `/health`, `/`            | \_\_invoke                          | —                      | —                            |
| `Privacy/ConsentSubjectStoreController` | `POST /consent/subjects`  | \_\_invoke                          | —                      | —                            |

**Padrões observados:**

-   `Gate::authorize()` em todos os métodos sensíveis.
-   `ScopesToCurrentTenant` aplicado globalmente nos modelos.
-   `where('tenant_id', ...)` explícito em show/update/destroy do `ContatoController` (defesa em profundidade).
-   `ContatoController` mantém invariante "apenas 1 contato principal por cliente" via lógica de domínio.

### 3. Form Requests (`app/Http/Requests/`)

-   `ListClientesRequest` — search (ILIKE), tipo_pessoa, ativo, paginação, ordenação.
-   `StoreClienteRequest` — tipo_pessoa, cnpj_cpf (validado + único por tenant), razao_social, endereço completo, regime tributário, limite de crédito, contato. Mapeia `cnpj_cpf` (API) → `documento` (DB), só dígitos.
-   `UpdateClienteRequest` — espelho do Store.
-   `StoreContatoRequest` — nome, papel, email XOR whatsapp (validação custom em `withValidator()`), principal.
-   `UpdateContatoRequest` — espelho do Store.

### 4. Policies (`app/Policies/`)

-   `ClientePolicy` — viewAny, view, create, update, delete. Delega a `TenantRole::canRead/WriteClientes` + `isActiveWithRole`.
-   `ContatoPolicy` — mesma estrutura.
-   `TenantSettingsPolicy` — `assertManager()`.

### 5. Migrations (`database/migrations/`) — 24 migrations

**Grupo 1 — Infraestrutura base** (5)

-   `create_users_table`, `create_cache_table`, `create_jobs_table`, `create_sanity_check_table`
-   `enable_rls_setup` — **PostgreSQL Row-Level Security ativado** (defesa em profundidade do isolamento multi-tenant, REQ-TEN-007 do MVP)

**Grupo 2 — Multi-tenancy + Auth** (9)

-   `create_tenants_table`, `create_roles_table`, `create_tenant_users_table`
-   `extend_users_for_two_factor_auth`
-   `create_login_audit_logs_table`
-   `extend_tenants_for_settings`, `create_companies_table`, `create_branches_table`
-   `extend_tenant_users_for_invitations`
-   `create_tenant_audit_logs_table` — **append-only via trigger PG**

**Grupo 3 — Billing** (2)

-   `create_tenant_plan_metrics_table`, `create_plan_upgrade_requests_table`

**Grupo 4 — LGPD** (5)

-   `create_lgpd_categories_table`, `create_consent_subjects_table`
-   `create_consent_records_table` — **append-only via trigger PG**
-   `create_revocation_tokens_table`
-   `add_name_and_soft_deletes_to_consent_subjects`

**Grupo 5 — Domínio (Cliente/Contato)** (2)

-   `create_clientes_table`, `create_contatos_table`

**Recursos PG-específicos:** RLS + triggers de imutabilidade. **Não dá pra trocar por SQLite** — Postgres é obrigatório no ambiente de teste.

### 6. Outras pastas (`app/`)

-   **`Domain/`** — vazia (`.gitkeep`). Planejada, não implementada. Decidir se mantém ou remove quando E04+ começar.
-   **`Services/`** — `ConsentRecordService`, `RevocationTokenService` (ambos LGPD).
-   **`Support/`** — `TenantContext`, `TenantRole`, `TenantSettingsUpdater`.
-   **`Rules/`** — `Cpf`, `Cnpj`, `CnpjFormat` (validadores custom).
-   **`Infrastructure/`** — vazia.
-   **`Jobs/`** — existe, não auditado em profundidade.
-   **`Console/`, `Mail/`, `Exceptions/`, `Providers/`** — padrão Laravel.

### 7. Testes (`tests/`) — 54 arquivos em 16 slices

| Slice     | Escopo                                                                                              | Status                        |
| --------- | --------------------------------------------------------------------------------------------------- | ----------------------------- |
| Feature   | ConfigCache, DB connection, db:check, test:scope, example                                           | Utilitário                    |
| slice-003 | CI (jobs, sbom, runtime, trigger)                                                                   | Infra                         |
| slice-004 | Deploy staging                                                                                      | Infra                         |
| slice-005 | Health check                                                                                        | Infra                         |
| slice-006 | Build, Livewire cmd, ping, static analysis                                                          | **Legado Livewire — revisar** |
| slice-007 | Auth config                                                                                         | Auth                          |
| slice-008 | TestHelpers (utilitário)                                                                            | —                             |
| slice-009 | Plan upgrade, users, roles                                                                          | Domínio                       |
| slice-010 | Audit append-only, consent record                                                                   | LGPD                          |
| slice-011 | Isolamento multi-tenant (CI, jobs, models, perf, readme)                                            | **Core — manter**             |
| slice-012 | Cliente: criação, migration, soft-delete, uniqueness, CPF/CNPJ                                      | Domínio                       |
| slice-013 | Cliente: listing, RBAC, show, update                                                                | Domínio                       |
| slice-014 | Contato: criação, deativação, isolamento, RBAC, update, validação                                   | Domínio                       |
| slice-015 | **INF-007 spike — testes verificam existência de `specs/015/` e `epics/E15/` que não existem mais** | **Órfão — remover**           |

### 8. Pendências e ações imediatas

| Item                                                  | Ação                                           | Prioridade                 |
| ----------------------------------------------------- | ---------------------------------------------- | -------------------------- |
| `tests/slice-015/` aponta pra estrutura morta         | Remover diretório completo                     | Agora                      |
| `phpunit.xml` referencia `Slice015` testsuite         | Remover entrada                                | Agora                      |
| Ambiente local sem `pdo_pgsql`                        | Pendência operacional do Roldão                | Quando ele decidir         |
| `TenantSettingsController` retorna `RedirectResponse` | Refatorar pra JSON antes do app móvel consumir | Quando E15-S07+ começar    |
| Slice-006 testa "Livewire cmd"                        | Revisar relevância pós-descarte do Livewire    | Próxima passada de limpeza |
| `app/Domain/` e `app/Infrastructure/` vazias          | Decidir manter ou remover quando E04+ começar  | Diferido                   |

### 9. Próximo passo recomendado

Conforme `docs/product/roadmap.md`, o próximo slice planejado é **E15-S02** (login do app móvel com JWT longo + biometria + device binding). Esse é o primeiro trabalho que tem um efeito visível ao cliente (técnico consegue entrar no app do celular) e deve virar a primeira história formal em `docs/backlog/historias/aguardando/`.
