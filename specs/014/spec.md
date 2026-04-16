---
slice: "014"
story: E03-S02a
title: "CRUD de contato + RBAC + isolamento"
status: draft
dependencies:
  - slice-013
---

# Slice 014 — CRUD de contato + RBAC + isolamento

## Contexto

O Model `Cliente` e seu CRUD completo (criação, listagem, filtro, edição, desativação, RBAC) foram implementados nos slices 012 e 013 (E03-S01a e E03-S01b). Este slice adiciona o model `Contato`, vinculado a `Cliente`, com CRUD completo, RBAC por role e isolamento de tenant. O contato herda o tenant do cliente ao qual pertence.

Esta slice é **backend-only** (API + rotas web resource). Sem views Blade — seguindo o pattern do slice 013.

## Jornada do usuário

1. Atendente acessa `/clientes/{id}/contatos` → vê listagem dos contatos ativos do cliente do seu tenant
2. Atendente clica em "Novo Contato" → preenche nome, e-mail, WhatsApp e papel → submete
3. Sistema valida formato do WhatsApp (DDD obrigatório, mínimo 10 dígitos numéricos) e persiste o contato com `ativo = true`
4. Atendente edita contato existente → altera campos editáveis → salva
5. Atendente desativa contato → `ativo = false` persiste; contato some da listagem padrão
6. Técnico acessa listagem → vê contatos (200), mas POST/PUT/DELETE retornam 403

## Critérios de aceite

### AC-001 — Criação de contato vinculado a cliente
**Dado** que existe o cliente `C001` no tenant A e sou atendente autenticado no tenant A
**Quando** faço POST para `/clientes/{id}/contatos` com payload `{ nome, email, papel: "comprador" }`
**Então** o contato é persistido com `cliente_id = C001`, `ativo = true` e a resposta retorna HTTP 201

### AC-002 — Múltiplos contatos por cliente
**Dado** que o cliente `C001` já tem 1 contato com papel `comprador`
**Quando** faço POST criando um segundo contato com papel `responsavel_tecnico`
**Então** ambos os contatos existem vinculados ao cliente, a listagem GET `/clientes/{id}/contatos` retorna os dois registros e HTTP 200

### AC-003 — Edição de contato (campos editáveis)
**Dado** que existe o contato `CT001` com papel `comprador` vinculado ao cliente `C001` no tenant A
**Quando** o atendente faz PUT `/contatos/{id}` com payload `{ nome: "Novo Nome", papel: "outro" }`
**Então** os novos valores são persistidos, HTTP 200 é retornado e a exibição reflete a alteração; `cliente_id` permanece imutável

### AC-004 — Payload vazio no PUT retorna 422
**Dado** que existe o contato `CT001` no tenant A
**Quando** o atendente faz PUT `/contatos/{id}` com payload vazio `{}`
**Então** recebe HTTP 422 com erro indicando que ao menos um campo deve ser informado

### AC-005 — RBAC: técnico não pode editar contato
**Dado** que sou usuário com role `tecnico`
**Quando** faço PUT `/contatos/{id}` para um contato do meu tenant
**Então** recebo HTTP 403 e nenhuma alteração é persistida

### AC-006 — RBAC: técnico não pode desativar contato
**Dado** que sou usuário com role `tecnico`
**Quando** faço DELETE `/contatos/{id}` para um contato do meu tenant
**Então** recebo HTTP 403 e nenhuma alteração de estado é realizada

### AC-007 — Desativação de contato já inativo retorna 409
**Dado** que existe o contato `CT001` com `ativo = false` no tenant A
**Quando** o atendente tenta desativá-lo novamente via DELETE `/contatos/{id}`
**Então** recebe HTTP 409 Conflict e nenhuma alteração é realizada

### AC-008 — Isolamento: contato de outro tenant inacessível
**Dado** que sou usuário do tenant A
**Quando** faço GET em `/contatos/{id}` onde o ID pertence a contato de cliente do tenant B
**Então** recebo HTTP 404 (isolamento via `cliente_id` → `tenant_id`)

### AC-009 — RBAC: técnico não pode criar contato
**Dado** que sou usuário com role `tecnico`
**Quando** faço POST para `/clientes/{id}/contatos`
**Então** recebo HTTP 403 e nenhum registro é persistido

### AC-010 — RBAC: técnico pode listar contatos do seu tenant
**Dado** que sou usuário com role `tecnico`
**Quando** faço GET em `/clientes/{id}/contatos` para um cliente do meu tenant
**Então** recebo HTTP 200 com a lista de contatos ativos

### AC-011 — Desativação de contato
**Dado** que existe o contato `CT001` com `ativo = true`
**Quando** o atendente faz DELETE `/contatos/{id}`
**Então** `ativo = false` é persistido, HTTP 200 é retornado e o contato não aparece na listagem padrão de contatos ativos

### AC-012 — Validação de formato de WhatsApp
**Dado** que submeto `whatsapp = "99999-9999"` (sem DDD, apenas 8 dígitos)
**Quando** faço POST para criação de contato
**Então** retorna HTTP 422 com erro de validação no campo `whatsapp` (deve ter DDD + número, mínimo 10 dígitos numéricos)

### AC-013 — Isolamento: PUT em contato de outro tenant retorna 404
**Dado** que sou atendente autenticado no tenant A
**Quando** faço PUT `/contatos/{id}` onde o contato pertence a cliente do tenant B
**Então** recebo HTTP 404 e nenhuma alteração é persistida

### AC-014 — Isolamento: DELETE em contato de outro tenant retorna 404
**Dado** que sou atendente autenticado no tenant A
**Quando** faço DELETE `/contatos/{id}` onde o contato pertence a cliente do tenant B
**Então** recebo HTTP 404 e nenhuma alteração de estado é realizada

### AC-015 — Validação condicional: POST sem email e sem whatsapp retorna 422
**Dado** que faço POST para `/clientes/{id}/contatos` com payload `{ nome: "Teste", papel: "comprador" }` sem `email` e sem `whatsapp`
**Quando** submeto a requisição
**Então** recebo HTTP 422 com erro indicando que pelo menos `email` ou `whatsapp` deve ser fornecido

### AC-016 — GET /contatos/{id} do próprio tenant retorna 200
**Dado** que existe o contato `CT001` vinculado ao cliente `C001` no tenant A e sou atendente autenticado no tenant A
**Quando** faço GET `/contatos/{CT001}`
**Então** recebo HTTP 200 com os campos `id`, `cliente_id`, `nome`, `email`, `whatsapp`, `papel`, `principal`, `ativo` do contato `CT001`

### AC-017 — PUT ignora tentativa de mutar cliente_id
**Dado** que o contato `CT001` pertence ao cliente `C001` no tenant A e sou atendente autenticado no tenant A
**Quando** faço PUT `/contatos/{CT001}` com payload `{ "cliente_id": C002 }`
**Então** o campo `cliente_id` permanece `C001` no banco (o campo é silenciosamente ignorado) e a resposta retorna HTTP 200 sem erro

## Fora de escopo

- Regra de unicidade do campo `principal` (apenas 1 contato principal por cliente) — será implementada em slice posterior. Neste slice o campo é apenas boolean sem constraint de unicidade.
- Consentimentos LGPD — E03-S02b. Campo `consentimentos` no response de GET /contatos/{id} — responsabilidade de E03-S02b. O contrato de API será satisfeito integralmente após E03-S02b.
- Envio efetivo de e-mail ou WhatsApp — E06 (notificações)
- Exportação de dados para portabilidade LGPD — pós-MVP
- Exclusão de dados por direito ao esquecimento — pós-MVP
- Audit log de alterações de contato — E03-S07a
- UI/frontend — esta slice é backend-only (API + rotas web resource)
- Paginação de contatos (listagem ≤ 20 sem paginação; > 20 paginada com 20 registros/página — `->paginate(20)` — alinhado com contrato de API)

## Arquivos/módulos impactados

- `app/Models/Contato.php` (novo)
- `database/migrations/*_create_contatos_table.php` (novo)
- `database/factories/ContatoFactory.php` (novo)
- `database/seeders/ContatoSeeder.php` (novo)
- `app/Http/Controllers/ContatoController.php` (novo)
- `app/Http/Requests/StoreContatoRequest.php` (novo)
- `app/Http/Requests/UpdateContatoRequest.php` (novo)
- `app/Policies/ContatoPolicy.php` (novo)
- `routes/web.php` (adicionar rotas de contato)
- `tests/Feature/ac-014-*.php` (testes de AC)

> **Sem** `resources/views/contatos/` — backend-only seguindo pattern do slice 013.

## Modelo de dados

Tabela `contatos`:

| Coluna       | Tipo         | Constraints                                              |
|--------------|--------------|----------------------------------------------------------|
| `id`         | bigint PK    | auto-increment                                           |
| `tenant_id`  | bigint FK    | NOT NULL, FK → `tenants.id` RESTRICT                     |
| `cliente_id` | bigint FK    | NOT NULL, FK → `clientes.id`                             |
| `nome`       | varchar(255) | NOT NULL                                                 |
| `email`      | varchar(254) | nullable                                                 |
| `whatsapp`   | varchar(20)  | nullable, formato E.164 ou DDD+número (≥ 10 dígitos)     |
| `papel`      | enum         | `comprador`, `responsavel_tecnico`, `financeiro`, `outro` — NOT NULL |
| `principal`  | boolean      | NOT NULL, default `false`                                |
| `ativo`      | boolean      | NOT NULL, default `true`                                 |
| `created_at` | timestamp    |                                                          |
| `updated_at` | timestamp    |                                                          |
| `created_by` | bigint FK    | nullable, FK → `users.id` (quem criou o registro)        |
| `updated_by` | bigint FK    | nullable, FK → `users.id` (quem fez a última alteração)  |
| `deleted_at` | timestamp    | nullable — SoftDeletes do Laravel                        |

O `tenant_id` é armazenado diretamente na tabela `contatos` (FK → `tenants.id`), em alinhamento com o ERD E03. O isolamento é garantido tanto pela coluna `tenant_id` quanto pela relação `cliente_id → clientes.tenant_id`.

O Model `Contato` usa a trait `SoftDeletes` do Laravel (controla `deleted_at`) em coexistência com o campo `ativo` (controla exibição na listagem) — mesmo pattern do Model `Cliente` implementado no slice 012.

## Rotas

```
POST   /clientes/{cliente}/contatos          → ContatoController@store
GET    /clientes/{cliente}/contatos          → ContatoController@index
GET    /contatos/{contato}                   → ContatoController@show
PUT    /contatos/{contato}                   → ContatoController@update
DELETE /contatos/{contato}                   → ContatoController@destroy
```

## Riscos e dependências

- **Isolamento:** O contato tem `tenant_id` próprio (FK → `tenants.id`). A policy valida `contato.tenant_id` diretamente; a relação `cliente_id → clientes.tenant_id` serve como segunda camada de consistência.
- **Extensão futura:** enum `papel` pode precisar de novos valores — mitigação: definir como string com check constraint para facilitar ALTER TABLE posterior.
- **Dependência:** slice-013 (Model `Cliente` completo com RBAC deve existir para `cliente_id`)

## Rollback

- `dropTable('contatos')` no `down()` da migration
- Sem side effects externos

## Evidência necessária para aprovação

- Testes Pest cobrindo todos os ACs (AC-001 a AC-017) passando com exit 0
- `php artisan migrate:fresh --seed` sem erro
