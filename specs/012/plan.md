# Plano tecnico do slice 012 — E03-S01a: Model Cliente + validacao CNPJ/CPF + unicidade

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/012/spec.md`

---

## Resumo

Este slice cria o Model `Cliente` — entidade raiz do E03 Cadastro Core — com migration, validacao algoritmica de CNPJ e CPF, unicidade por tenant (partial unique index), soft-delete via coluna `ativo` + `deleted_at`, seeder de exemplo e os endpoints REST `POST /clientes` e `DELETE /clientes/{id}`. Cobre 9 ACs: criacao PJ/PF, rejeicao de documento invalido, unicidade intra-tenant, isolamento inter-tenant, soft-delete com 409 para cliente ja inativo, e migration+seeder.

---

## Decisoes arquiteturais

### D1: Criar Rule `Cpf` separada em vez de Rule unica `CnpjCpf`

**Opcoes consideradas:**
- **Opcao A: criar `App\Rules\Cpf` separada, analoga a `App\Rules\Cnpj` existente** — pros: cada rule e simples, testavel isoladamente, segue o padrao ja estabelecido no slice 008; contras: duas classes em vez de uma.
- **Opcao B: criar uma unica `App\Rules\CnpjCpf` que decide internamente pelo tamanho** — pros: uma unica rule; contras: viola SRP, mistura dois algoritmos distintos, diverge do padrao ja estabelecido.
- **Opcao C: usar pacote externo (`LaravelLegends/pt-br-validator`)** — pros: pronto; contras: dependencia externa para algo trivial, contradiz ADR-0001 que prefere solucoes nativas quando simples.

**Escolhida:** Opcao A.

**Razao:** ja existe `App\Rules\Cnpj` do slice 008. Criar `App\Rules\Cpf` com a mesma estrutura mantem consistencia. O FormRequest do cliente escolhe qual rule aplicar com base no `tipo_pessoa`.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

### D2: Mapeamento `cnpj_cpf` (API) para `documento` (banco) via FormRequest + Accessor/Mutator

**Opcoes consideradas:**
- **Opcao A: FormRequest mapeia o campo `cnpj_cpf` do input para `documento` antes de persistir; Model expoe accessor `cnpj_cpf` para API via Resource** — pros: camada de API usa nome do contrato, banco usa nome do ERD, mapeamento explicito e testavel; contras: requer atencao no mapeamento.
- **Opcao B: usar `documento` em todas as camadas (API inclusive)** — pros: nome unico; contras: viola o contrato de API aprovado que usa `cnpj_cpf`.
- **Opcao C: usar `cnpj_cpf` no banco** — pros: nome unico; contras: viola o ERD aprovado que usa `documento`.

**Escolhida:** Opcao A.

**Razao:** o spec explicita que API usa `cnpj_cpf` e banco usa `documento`. O mapeamento no FormRequest e a camada correta para traduzir entre contrato externo e modelo interno.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

### D3: Unicidade de documento via partial unique index no PostgreSQL, nao via aplicacao

**Opcoes consideradas:**
- **Opcao A: `UNIQUE (tenant_id, documento) WHERE deleted_at IS NULL` no PostgreSQL** — pros: atomico, resistente a race conditions, ja especificado no ERD e migration spec; contras: nenhum relevante.
- **Opcao B: validacao somente na aplicacao via `Rule::unique()`** — pros: simples; contras: race condition em concorrencia, nao protege dados se outra camada inserir.

**Escolhida:** Opcao A (ambas as camadas: index no banco + validacao na aplicacao).

**Razao:** defesa em profundidade. O index garante integridade; a validacao na aplicacao gera mensagem amigavel.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

### D4: Soft-delete via coluna `ativo` + SoftDeletes (`deleted_at`), nao apenas um deles

**Opcoes consideradas:**
- **Opcao A: usar `ativo = false` + `deleted_at` preenchido simultaneamente** — pros: atende AC-007 e AC-008 literalmente, `ativo` e filtravel sem precisar de `whereNull`, `deleted_at` integra com `SoftDeletes` do Eloquent; contras: dois campos para mesmo conceito.
- **Opcao B: usar apenas `SoftDeletes`** — pros: padrao Laravel; contras: spec pede explicitamente campo `ativo` e HTTP 409 para cliente ja inativo.

**Escolhida:** Opcao A.

**Razao:** o spec e o ERD definem ambos os campos. A action de DELETE seta `ativo = false` e `deleted_at`, garantindo consistencia. O partial unique index usa `WHERE deleted_at IS NULL`.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

### D6: Tipo do campo `id` — bigserial (ERD) prevalece sobre UUID (API contract)

**Contexto:** O API contract de clientes exibe `id` como UUID (ex: `"a1b2c3d4-e5f6-7890-abcd-ef1234567890"`). O ERD define `id` como `bigserial` (bigint), consistente com todas as migrations existentes no projeto (tenants, tenant_users, companies, etc.).

**Decisao:** ERD prevalece. A tabela `clientes` usara `bigserial` como PK, igual ao restante do projeto. O API contract sera corrigido para exibir `id` como integer. O `ClienteResource` expoe `id` como inteiro.

**Razao:** Consistencia com o padrao bigserial adotado em todos os slices anteriores (E01, E02). UUID exigiria mudanca de padrao em toda a base — escopo fora deste slice. O API contract continha erro tipografico.

**Acao:** API contract `docs/architecture/api-contracts/E03/clientes.md` corrigido para integer (ver PR-001).

**ADR:** nao requer ADR novo — confirma padrao ja estabelecido.

---

### D7: `regime_tributario` — banco armazena slugs, API expoe labels via mapeamento

**Contexto:** O ERD define `regime_tributario` com CHECK `in ('simples','presumido','real','mei','isento')` (slugs). O API contract define o campo com valores de label (`Simples`, `Lucro Presumido`, `Lucro Real`, `MEI`, `Isento`).

**Decisao:** Banco armazena slugs conforme ERD. API aceita labels conforme API contract. Mapeamento e feito no `StoreClienteRequest` (normaliza label → slug na entrada) e no `ClienteResource` (traduz slug → label na saida).

**Mapeamento:**
```
'Simples'        → 'simples'
'Lucro Presumido'→ 'presumido'
'Lucro Real'     → 'real'
'MEI'            → 'mei'
'Isento'         → 'isento'
```

**FormRequest:** aceita ambos os formatos (label ou slug) via validacao `in:simples,presumido,real,mei,isento,Simples,Lucro Presumido,Lucro Real,MEI,Isento`, normaliza para slug antes de persistir.

**Razao:** ERD define a constraint do banco — nao pode ser alterado sem migration. API contract define o contrato publico — labels sao mais legíveis para o frontend. O mapeamento na camada de aplicacao e o ponto correto de traducao.

**ADR:** nao requer ADR novo.

---

### D8: Campos de endereco — FormRequest exige, ERD permite null (dados legados)

**Contexto:** O API contract define campos de endereco (`logradouro`, `numero`, `bairro`, `cidade`, `uf`, `cep`) como obrigatorios no `POST /clientes`. O ERD define esses campos como `nullable`.

**Decisao:** API contract prevalece para validacao de input em novos cadastros (campos obrigatorios no `StoreClienteRequest`). ERD permite null como fallback para dados legados e migracao de dados historicos. O schema do banco nao e alterado (nullable preservado).

**Razao:** Novos clientes criados via API devem ter endereco completo (regra de negocio do produto). Dados legados importados diretamente no banco podem ter endereco null. O FormRequest e a camada de enforcement da regra de negocio para a API; o banco permanece flexivel para operacoes administrativas.

**ADR:** nao requer ADR novo.

---

### D5: Validacao de CNPJ/CPF do cliente reutiliza `App\Rules\Cnpj` existente (contexto diferente)

**Opcoes consideradas:**
- **Opcao A: reutilizar `App\Rules\Cnpj` do slice 008, adaptando para verificar unicidade na tabela `clientes` em vez de `tenants`** — pros: reutiliza algoritmo; contras: a rule atual verifica unicidade em `tenants`, nao em `clientes`.
- **Opcao B: criar `App\Rules\CnpjCliente` separada** — pros: separacao total; contras: duplica o algoritmo de digitos.

**Escolhida:** Opcao A com refatoracao minima: extrair o metodo estatico de validacao de digitos (`hasValidDigits` + `normalize`) e reutilizar. A unicidade de documento na tabela `clientes` fica no FormRequest via `Rule::unique('clientes', 'documento')->where('tenant_id', $tenantId)->whereNull('deleted_at')`.

**Razao:** o algoritmo de digitos do CNPJ e identico. A unicidade e contexto diferente (tenants vs clientes) e fica melhor no FormRequest. Para CPF, criar `App\Rules\Cpf` com o algoritmo de CPF.

**Reversibilidade:** facil.

**ADR:** nao requer ADR novo.

---

## Sequencia de implementacao

### Task 1: Migration da tabela `clientes` + RLS + partial unique index

**Descricao:** Criar a migration `create_clientes_table` conforme migration spec do E03, com todas as colunas do ERD, CHECK constraints, partial unique index `(tenant_id, documento) WHERE deleted_at IS NULL`, indices compostos e RLS policy.

**Arquivos:**
- Criar: `database/migrations/2026_04_16_000100_create_clientes_table.php`

**ACs cobertos:** AC-009 (parcial — migration executa sem erro)

**Criterio de done:** `php artisan migrate` executa sem erro; tabela `clientes` existe com RLS ativo e partial unique index confirmados via query `pg_tables`/`pg_indexes`.

---

### Task 2: Model Cliente com ScopesToCurrentTenant + SoftDeletes + Factory

**Descricao:** Criar o Model `Cliente` com trait `ScopesToCurrentTenant`, `SoftDeletes`, fillable conforme ERD, casts, relacao `belongsTo(Tenant)` e `belongsTo(TenantUser)` para `created_by`/`updated_by`. Criar `ClienteFactory` com dados brasileiros validos.

**Arquivos:**
- Criar: `app/Models/Cliente.php`
- Criar: `database/factories/ClienteFactory.php`

**ACs cobertos:** AC-001, AC-002 (modelo que permite persistir PJ e PF)

**Criterio de done:** Factory gera cliente valido PJ e PF; Model aplica global scope de tenant automaticamente.

---

### Task 3: Rule `Cpf` para validacao algoritmica de CPF

**Descricao:** Criar `App\Rules\Cpf` com validacao por digitos verificadores, rejeicao de sequencias repetidas, e metodo `normalize()`. Mesma estrutura de `App\Rules\Cnpj`.

**Arquivos:**
- Criar: `app/Rules/Cpf.php`

**ACs cobertos:** AC-002 (validacao CPF valido), AC-004 (rejeicao CPF invalido)

**Criterio de done:** Teste unitario passa para CPFs validos e rejeita invalidos (`111.111.111-11`, formato errado, etc.).

---

### Task 4: FormRequest `StoreClienteRequest` com mapeamento cnpj_cpf → documento

**Descricao:** Criar FormRequest que: (a) valida `tipo_pessoa` como `PJ` ou `PF`; (b) aplica `Cnpj` rule quando PJ, `Cpf` rule quando PF, no campo `cnpj_cpf`; (c) valida unicidade de documento por tenant via `Rule::unique`; (d) valida demais campos (razao_social, endereco, etc.); (e) metodo `validated()` ou `passedValidation()` mapeia `cnpj_cpf` para `documento` normalizando (so digitos).

**Arquivos:**
- Criar: `app/Http/Requests/StoreClienteRequest.php`

**ACs cobertos:** AC-001, AC-002, AC-003, AC-004, AC-005

**Criterio de done:** Request rejeita CNPJ invalido, CPF invalido, documento duplicado no tenant; aceita documentos validos; campo `documento` no output contem apenas digitos.

---

### Task 5: ClienteController com store() e destroy()

**Descricao:** Criar `ClienteController` com: (a) `store()` — usa `StoreClienteRequest`, persiste cliente com `tenant_id` do contexto atual e `created_by`/`updated_by` do usuario autenticado, retorna 201 com resource; (b) `destroy()` — verifica se `ativo == true`, se sim seta `ativo = false` + `deleted_at = now()` e retorna 200, se ja inativo retorna 409.

**Arquivos:**
- Criar: `app/Http/Controllers/ClienteController.php`
- Criar: `app/Http/Resources/ClienteResource.php`
- Modificar: `routes/api.php` (ou `routes/web.php` dependendo do setup)

**ACs cobertos:** AC-001, AC-002, AC-005, AC-006, AC-007, AC-008

**Criterio de done:** `POST /clientes` com dados validos retorna 201; `POST /clientes` com documento duplicado retorna 422; `DELETE /clientes/{id}` de ativo retorna 200; `DELETE /clientes/{id}` de inativo retorna 409.

---

### Task 6: Seeder `ClienteSeeder` para dados de exemplo

**Descricao:** Criar seeder que gera ao menos 1 cliente valido por tenant de exemplo (usando tenants criados por seeders anteriores de E02). Registrar no `DatabaseSeeder`.

**Arquivos:**
- Criar: `database/seeders/ClienteSeeder.php`
- Modificar: `database/seeders/DatabaseSeeder.php`

**ACs cobertos:** AC-009 (seeder cria ao menos um cliente por tenant)

**Criterio de done:** `php artisan migrate:fresh --seed` executa com exit 0; tabela `clientes` contem registros.

---

### Task 7: Testes de integracao — todos os 9 ACs

**Descricao:** Criar suite de testes Pest cobrindo todos os ACs em contexto isolado.

**Arquivos:**
- Criar: `tests/slice-012/ClienteCreationTest.php` — AC-001, AC-002, AC-003, AC-004
- Criar: `tests/slice-012/ClienteUniquenessTest.php` — AC-005, AC-006
- Criar: `tests/slice-012/ClienteSoftDeleteTest.php` — AC-007, AC-008
- Criar: `tests/slice-012/ClienteMigrationTest.php` — AC-009
- Modificar: `tests/Pest.php` — registrar `tests/slice-012`

**ACs cobertos:** AC-001 a AC-009 (todos)

**Criterio de done:** `php artisan test tests/slice-012` com exit 0, todos os testes passam.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `StoreClienteRequest`, `ClienteController`, `Cliente`, migration, `ClienteResource` | `tests/slice-012/ClienteCreationTest.php` |
| AC-002 | `StoreClienteRequest`, `ClienteController`, `Cliente`, `Cpf` rule | `tests/slice-012/ClienteCreationTest.php` |
| AC-003 | `StoreClienteRequest`, `Cnpj` rule | `tests/slice-012/ClienteCreationTest.php` |
| AC-004 | `StoreClienteRequest`, `Cpf` rule | `tests/slice-012/ClienteCreationTest.php` |
| AC-005 | `StoreClienteRequest`, migration (partial unique index) | `tests/slice-012/ClienteUniquenessTest.php` |
| AC-006 | `StoreClienteRequest`, `ScopesToCurrentTenant`, migration (RLS) | `tests/slice-012/ClienteUniquenessTest.php` |
| AC-007 | `ClienteController::destroy()`, `Cliente` (SoftDeletes) | `tests/slice-012/ClienteSoftDeleteTest.php` |
| AC-008 | `ClienteController::destroy()` | `tests/slice-012/ClienteSoftDeleteTest.php` |
| AC-009 | migration, `ClienteSeeder` | `tests/slice-012/ClienteMigrationTest.php` |

## Novos arquivos

- `database/migrations/2026_04_16_000100_create_clientes_table.php` — migration com todas as colunas do ERD, CHECK constraints, partial unique index, RLS.
- `app/Models/Cliente.php` — Model com `ScopesToCurrentTenant`, `SoftDeletes`, fillable, casts, relacoes.
- `database/factories/ClienteFactory.php` — factory com dados brasileiros validos (PJ e PF).
- `app/Rules/Cpf.php` — validacao algoritmica de CPF (digitos verificadores + rejeicao de sequencias).
- `app/Http/Requests/StoreClienteRequest.php` — validacao de input com mapeamento `cnpj_cpf` → `documento`.
- `app/Http/Controllers/ClienteController.php` — endpoints `store` e `destroy`.
- `app/Http/Resources/ClienteResource.php` — resource com mapeamento `documento` → `cnpj_cpf` na API.
- `database/seeders/ClienteSeeder.php` — seeder de exemplo por tenant.
- `tests/slice-012/ClienteCreationTest.php` — testes AC-001 a AC-004.
- `tests/slice-012/ClienteUniquenessTest.php` — testes AC-005, AC-006.
- `tests/slice-012/ClienteSoftDeleteTest.php` — testes AC-007, AC-008.
- `tests/slice-012/ClienteMigrationTest.php` — teste AC-009.

## Arquivos modificados

- `database/seeders/DatabaseSeeder.php` — registrar `ClienteSeeder`.
- `routes/api.php` (ou `routes/web.php`) — rotas `POST /clientes` e `DELETE /clientes/{id}`.
- `tests/Pest.php` — registrar diretorio `tests/slice-012`.

## Schema / migrations

- `2026_04_16_000100_create_clientes_table.php` — cria tabela `clientes` com 22 colunas conforme ERD, FKs para `tenants` e `tenant_users` com `RESTRICT`, CHECK constraints (`tipo_pessoa`, `regime_tributario`, `limite_credito`), partial unique index `(tenant_id, documento) WHERE deleted_at IS NULL`, indices compostos `(tenant_id, razao_social)`, `(tenant_id, ativo)`, `(deleted_at)`, RLS policy `clientes_tenant_isolation`. Conforme `docs/architecture/data-models/E03/migrations.md` Migration 1.

## APIs / contratos

### POST /clientes

**Request:**
```json
{
  "tipo_pessoa": "PJ",
  "cnpj_cpf": "11.222.333/0001-81",
  "razao_social": "Calibra Laboratorios Ltda",
  "nome_fantasia": "CalibLab",
  "logradouro": "Rua das Industrias",
  "numero": "100",
  "complemento": "Galpao 3",
  "bairro": "Distrito Industrial",
  "cidade": "Sao Paulo",
  "uf": "SP",
  "cep": "01310100",
  "regime_tributario": "presumido",
  "limite_credito": 5000.00
}
```

**Response 201:**
```json
{
  "data": {
    "id": 1,
    "tipo_pessoa": "PJ",
    "cnpj_cpf": "11.222.333/0001-81",
    "razao_social": "Calibra Laboratorios Ltda",
    "ativo": true,
    "created_at": "2026-04-16T10:00:00-03:00"
  }
}
```

### DELETE /clientes/{id}

**Response 200 (ativo → inativo):**
```json
{
  "message": "Cliente desativado com sucesso.",
  "data": {
    "id": 1,
    "ativo": false,
    "updated_at": "2026-04-16T15:30:00-03:00"
  }
}
```

**Response 409 (ja inativo):**
```json
{
  "message": "Cliente ja esta inativo.",
  "errors": {
    "id": ["Cliente ja esta com ativo = false."]
  }
}
```

## Riscos e mitigacoes

- **Race condition na unicidade de documento** → mitigacao: partial unique index no PostgreSQL garante atomicidade; validacao na aplicacao gera mensagem amigavel antes do insert.
- **Mapeamento `cnpj_cpf` ↔ `documento` inconsistente entre camadas** → mitigacao: mapeamento centralizado no FormRequest (entrada) e no Resource (saida); testes cobrem ambas as direcoes.
- **Rule `Cnpj` existente valida unicidade na tabela `tenants`, nao `clientes`** → mitigacao: reutilizar apenas o algoritmo de digitos; unicidade em `clientes` fica no FormRequest via `Rule::unique`.
- **Soft-delete com dois campos (`ativo` + `deleted_at`) pode divergir** → mitigacao: `ClienteController::destroy()` seta ambos atomicamente; teste AC-007 valida ambos os campos.
- **Seeder falha se tenants de E02 nao existem** → mitigacao: `ClienteSeeder` depende de `TenantSeeder`; `DatabaseSeeder` garante ordem; teste AC-009 roda `migrate:fresh --seed`.
- **CPF/CNPJ com mascara vs sem mascara** → mitigacao: `normalize()` remove tudo que nao e digito antes de persistir; Resource formata com mascara na saida; partial unique index opera sobre valor normalizado.

## Dependencias de outros slices

- `slice-008` (E02-S04) — `App\Rules\Cnpj` (algoritmo de digitos reutilizado), `ScopesToCurrentTenant` trait, `SetCurrentTenantContext` middleware.
- `slice-007` (E02-S03) — autenticacao, sessao, `TenantUser` model.
- Tabelas `tenants` e `tenant_users` de E02 — FKs de `clientes`.

## Fora de escopo deste plano (confirmando spec)

- Listagem paginada e filtros (E03-S01b)
- RBAC de escrita/leitura (E03-S01b)
- Contatos do cliente (E03-S02a)
- Importacao em massa via CSV (pos-MVP)
- Consulta a API da Receita Federal (pos-MVP)
- Historico de alteracoes / audit log (E03-S07a)
- Endpoints GET /clientes, GET /clientes/{id}, PUT /clientes/{id} (E03-S01b)
