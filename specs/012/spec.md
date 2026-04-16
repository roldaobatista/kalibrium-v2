# Slice 012 — E03-S01a: Model cliente + validação CNPJ/CPF + unicidade

**Story:** E03-S01a
**Épico:** E03 — Cadastro Core
**Status:** spec_draft

## Contexto

Criar o model `Cliente` — entidade raiz que ancora contatos, instrumentos e ordens de serviço. Inclui validação algorítmica de CNPJ/CPF, unicidade por tenant e soft-delete.

## Jornada alvo

Atendente acessa o sistema, navega para "Clientes", clica "Novo Cliente", preenche CNPJ (ou CPF para PF), razão social, endereço e regime tributário. O sistema valida o documento algoritmicamente e verifica unicidade dentro do tenant. Se válido e único, persiste o cadastro.

## Acceptance Criteria

- **AC-001** — Criação de cliente PJ com CNPJ válido
  **Dado** que sou um usuário com role `atendente` autenticado no tenant A
  **Quando** submeto o formulário com CNPJ `11.222.333/0001-81` (válido algoritmicamente), razão social e endereço completo
  **Então** o cliente é persistido no banco com `tipo_pessoa = PJ`, `tenant_id` do tenant A, e a resposta HTTP retorna 201 ou redirect com flash de sucesso

- **AC-002** — Criação de cliente PF com CPF válido
  **Dado** que sou um usuário com role `atendente`
  **Quando** submeto formulário com CPF `529.982.247-25` (válido algoritmicamente) e nome completo
  **Então** o cliente é persistido com `tipo_pessoa = PF` e os dados ficam acessíveis via rota de exibição

- **AC-003** — Rejeição de CNPJ inválido
  **Dado** que sou um usuário com role `atendente`
  **Quando** submeto formulário com CNPJ `11.111.111/1111-11` (inválido)
  **Então** a request retorna erro de validação no campo `cnpj_cpf` e nenhum registro é persistido

- **AC-004** — Rejeição de CPF inválido
  **Dado** que sou um usuário com role `atendente`
  **Quando** submeto formulário com CPF `111.111.111-11` (inválido)
  **Então** a request retorna erro de validação no campo `cnpj_cpf` e nenhum registro é persistido

- **AC-005** — Unicidade de CNPJ/CPF dentro do tenant
  **Dado** que já existe um cliente com CNPJ `11.222.333/0001-81` no tenant A
  **Quando** tento cadastrar um segundo cliente com o mesmo CNPJ no tenant A
  **Então** a request retorna erro de validação indicando duplicata

- **AC-006** — Isolamento de CNPJ entre tenants
  **Dado** que existe um cliente com CNPJ `11.222.333/0001-81` no tenant A
  **Quando** um usuário do tenant B cadastra um cliente com o mesmo CNPJ
  **Então** o cadastro é aceito (CNPJ único apenas dentro do tenant, não globalmente)

- **AC-007** — Soft-delete de cliente ativo
  **Dado** que existe um cliente com `ativo = true` no tenant A
  **Quando** envio `DELETE /clientes/{id}` autenticado como atendente do tenant A
  **Então** a resposta retorna HTTP 200 e o campo `ativo` do registro fica `false` (soft-delete via coluna `ativo`; `deleted_at` também é preenchido)

- **AC-008** — Soft-delete de cliente já inativo retorna 409
  **Dado** que existe um cliente com `ativo = false` no tenant A
  **Quando** envio `DELETE /clientes/{id}` autenticado como atendente do tenant A
  **Então** a resposta retorna HTTP 409 e nenhuma alteração é realizada no registro

- **AC-009** — Migration e seeder executam sem erro
  **Dado** que o ambiente de banco está limpo
  **Quando** executo `php artisan migrate:fresh --seed`
  **Então** o comando conclui com exit 0, a tabela `clientes` existe com todas as colunas do ERD e o seeder cria ao menos um cliente válido por tenant de exemplo

## Nomenclatura: cnpj_cpf (API) vs documento (banco)

O campo que identifica o documento fiscal do cliente possui nomes diferentes em cada camada:

- **API / requests / validação / mensagens de erro:** `cnpj_cpf` — nome usado nos formulários, payloads JSON e nas mensagens de erro de validação.
- **Banco de dados / migration / ERD:** `documento` — nome da coluna na tabela `clientes`, conforme `docs/architecture/data-models/E03/erd.md`.

Ambos estão corretos nas suas respectivas camadas. O Model Laravel mapeia `documento` (coluna) para o input `cnpj_cpf` via FormRequest antes de persistir.

## Fora de escopo

- Listagem paginada e filtros (E03-S01b)
- RBAC de escrita/leitura (E03-S01b)
- Contatos do cliente (E03-S02a)
- Importação em massa via CSV (pós-MVP)
- Consulta à API da Receita Federal (pós-MVP)
- Histórico de alterações / audit log (E03-S07a)

## Referências

- Story Contract: `epics/E03/stories/E03-S01a.md`
- Data Model: `docs/architecture/data-models/E03/erd.md`
- API Contract: `docs/architecture/api-contracts/E03/clientes.md`
- Wireframe: `docs/design/wireframes/E03/clientes-form.md`
