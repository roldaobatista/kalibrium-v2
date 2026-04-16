---
slice: "013"
story: E03-S01b
title: "Listagem + filtro + paginação + RBAC de cliente"
status: draft
dependencies:
  - slice-012
---

# Slice 013 — Listagem + filtro + paginação + RBAC de cliente

## Contexto

O Model `Cliente` e o CRUD de criação/exclusão foram implementados no slice 012 (E03-S01a). Esta slice completa o CRUD expondo listagem paginada com filtros, edição, desativação e controle RBAC por role. Com esta story o atendente consegue localizar um cliente existente, editar seus dados e desativá-lo; o técnico tem acesso somente-leitura.

## Jornada do usuário

1. Atendente acessa `/clientes` → vê listagem paginada (20 por página) dos clientes do seu tenant
2. Atendente digita busca por razão social ou CNPJ → lista filtra em tempo real
3. Atendente clica em um cliente → vê detalhes (show)
4. Atendente clica em "Editar" → altera limite de crédito e regime tributário → salva
5. Atendente clica em "Desativar" → cliente sai da listagem padrão mas permanece acessível via URL direta
6. Técnico acessa `/clientes` → vê listagem e detalhes, mas botões de criar/editar/desativar não aparecem (403 no backend)

## Critérios de aceite

### AC-007 — Listagem paginada com isolamento de tenant
**Dado** que tenant A tem 25 clientes e tenant B tem 10 clientes
**Quando** um usuário do tenant A lista clientes (página 1, 20 por página)
**Então** retorna exatamente 20 registros, todos com `tenant_id` do tenant A, sem nenhum registro do tenant B

### AC-008 — Filtro por razão social, nome fantasia e CNPJ/CPF
**Dado** que tenant A tem clientes com razões sociais, nomes fantasia e documentos variados
**Quando** filtro por substring (ex: "Calibra" ou "11.222")
**Então** retorna apenas clientes cujo `razao_social`, `nome_fantasia` ou `cnpj_cpf` contém a substring (case-insensitive)

### AC-009 — Edição de cliente (todos os campos editáveis)
**Dado** que existe um cliente no tenant A
**Quando** o atendente edita campos editáveis (`razao_social`, `nome_fantasia`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `regime_tributario`, `limite_credito`)
**Então** os novos valores são persistidos e a exibição reflete a alteração; campos `cnpj_cpf` e `tipo_pessoa` permanecem imutáveis

### AC-009b — Edição com payload vazio retorna 422
**Dado** que existe um cliente no tenant A
**Quando** o atendente envia PUT /clientes/{id} com payload vazio `{}`
**Então** recebe HTTP 422 com erro indicando que ao menos um campo deve ser informado

### AC-010 — Desativação de cliente (soft-delete)
**Dado** que existe um cliente ativo no tenant A
**Quando** o atendente desativa o cliente
**Então** `ativo = false` é persistido, o cliente não aparece na listagem padrão (`WHERE ativo = true`), mas continua acessível via rota de exibição direta com ID

### AC-010a — Listagem de clientes inativos via filtro
**Dado** que tenant A tem clientes ativos e inativos
**Quando** faço GET /clientes?ativo=false
**Então** retorna apenas clientes com `ativo = false` do tenant A, paginados

### AC-010b — Desativação de cliente já inativo retorna 409
**Dado** que existe um cliente com `ativo = false` no tenant A
**Quando** o atendente tenta desativá-lo novamente
**Então** recebe HTTP 409 Conflict e nenhuma alteração é realizada

### AC-011 — RBAC: técnico não pode criar cliente
**Dado** que sou um usuário com role `tecnico`
**Quando** faço POST para a rota de criação de cliente
**Então** recebo HTTP 403 e nenhum registro é persistido

### AC-011b — RBAC: técnico não pode editar cliente
**Dado** que sou um usuário com role `tecnico`
**Quando** faço PUT /clientes/{id}
**Então** recebo HTTP 403 e nenhuma alteração é persistida

### AC-011c — RBAC: técnico não pode desativar cliente
**Dado** que sou um usuário com role `tecnico`
**Quando** faço DELETE /clientes/{id}
**Então** recebo HTTP 403 e nenhuma alteração de estado é realizada

### AC-012a — RBAC: técnico pode listar clientes
**Dado** que sou um usuário com role `tecnico`
**Quando** faço GET /clientes
**Então** recebo HTTP 200 com a listagem paginada dos clientes do meu tenant

### AC-012b — RBAC: técnico pode ver detalhe de cliente
**Dado** que sou um usuário com role `tecnico`
**Quando** faço GET /clientes/{id} de um cliente do meu tenant
**Então** recebo HTTP 200 com os dados completos do cliente, incluindo `contatos_count` e `instrumentos_count` (valor 0 quando não há relacionamentos)

### AC-012c — Ordenação com valor inválido retorna 422
**Dado** que sou um usuário autenticado
**Quando** faço GET /clientes?sort=campo_invalido
**Então** recebo HTTP 422 com erro de validação indicando os valores aceitos de sort

### AC-013 — Cross-tenant: cliente de outro tenant retorna 404
**Dado** que sou um usuário do tenant A
**Quando** faço GET na rota `/clientes/{id}` onde o ID pertence a um cliente do tenant B
**Então** recebo HTTP 404 (o scope global filtra e o registro não é encontrado)

## Fora de escopo

- Validação CNPJ/CPF (slice 012)
- Contatos do cliente (E03-S02a)
- Importação em massa via CSV (pós-MVP)
- Histórico de alterações / audit log (E03-S07a)
- UI/frontend — esta slice é backend-only (API + rotas web resource)

## Arquivos/módulos impactados

- `app/Http/Controllers/ClienteController.php` (index, show, update, destroy — expandir métodos existentes)
- `app/Http/Requests/UpdateClienteRequest.php` (novo)
- `app/Policies/ClientePolicy.php` (viewAny, view, update — expandir policy existente)
- `routes/web.php` (rotas adicionais: GET index, GET show, PUT update)

## Riscos

- Paginação com filtro simultâneo deve preservar parâmetros de query na URL — mitigação: usar `->withQueryString()` no Paginator do Laravel

## Dependências técnicas

- slice-012 (Model `Cliente`, migration, ClienteController parcial, Policy parcial)
