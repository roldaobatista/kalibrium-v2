# API Contract — Clientes (E03)

> **Stories:** E03-S01a, E03-S01b
> **Recurso:** Cliente
> **Model:** `App\Models\Cliente`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |
| `X-Tenant-ID` | UUID do tenant (injetado via middleware) | automatico |

---

## GET /clientes

**Descricao:** Lista clientes ativos do tenant com paginacao e filtros.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 60 req/min

### Request — Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `search` | string | nao | max:100; busca em `razao_social`, `nome_fantasia`, `cnpj_cpf` (case-insensitive, LIKE) | `"Calibra"` |
| `tipo_pessoa` | enum | nao | `in:PJ,PF` | `"PJ"` |
| `ativo` | boolean | nao | `in:true,false`; padrao: `true` | `"true"` |
| `page` | integer | nao | min:1; padrao: 1 | `2` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |
| `sort` | string | nao | `in:razao_social,-razao_social,created_at,-created_at`; padrao: `razao_social` | `"razao_social"` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": 1,
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
      "cep": "01310-100",
      "regime_tributario": "Lucro Presumido",
      "limite_credito": "5000.00",
      "ativo": true,
      "created_at": "2026-04-15T10:00:00-03:00",
      "updated_at": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/clientes?page=1",
    "last": "/clientes?page=3",
    "prev": null,
    "next": "/clientes?page=2"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao de leitura |

---

## POST /clientes

**Descricao:** Criar novo cliente. CNPJ/CPF validado algoritmicamente (sem chamada externa).
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `tipo_pessoa` | enum | sim | `in:PJ,PF` | `"PJ"` |
| `cnpj_cpf` | string | sim | algoritmo de validacao de CNPJ (se PJ) ou CPF (se PF); unico por tenant (exclui `ativo=false`) | `"11.222.333/0001-81"` |
| `razao_social` | string | sim | max:255 | `"Calibra Laboratorios Ltda"` |
| `nome_fantasia` | string | nao | max:255 | `"CalibLab"` |
| `logradouro` | string | sim | max:255 | `"Rua das Industrias"` |
| `numero` | string | sim | max:20 | `"100"` |
| `complemento` | string | nao | max:100 | `"Galpao 3"` |
| `bairro` | string | sim | max:100 | `"Distrito Industrial"` |
| `cidade` | string | sim | max:100 | `"Sao Paulo"` |
| `uf` | string | sim | `size:2`, estados brasileiros validos | `"SP"` |
| `cep` | string | sim | formato `NNNNN-NNN` ou `NNNNNNNN`, 8 digitos | `"01310-100"` |
| `regime_tributario` | enum | sim | aceita labels (`Simples`, `Lucro Presumido`, `Lucro Real`, `MEI`, `Isento`) ou slugs (`simples`, `presumido`, `real`, `mei`, `isento`); banco armazena slugs — API expoe labels via `ClienteResource` | `"Lucro Presumido"` |
| `limite_credito` | numeric | nao | min:0, max:9999999.99; padrao: 0 | `5000.00` |

### Response 201 Created

```json
{
  "data": {
    "id": 1,
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
    "cep": "01310-100",
    "regime_tributario": "Lucro Presumido",
    "limite_credito": "5000.00",
    "ativo": true,
    "created_at": "2026-04-15T10:00:00-03:00",
    "updated_at": "2026-04-15T10:00:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `tecnico` tentando criar |
| 422 | `validation_error` | Campos invalidos — `errors` detalha por campo |
| 422 | `cnpj_invalido` | CNPJ falhou na validacao algoritmica |
| 422 | `cpf_invalido` | CPF falhou na validacao algoritmica |
| 422 | `cnpj_cpf_duplicado` | CNPJ/CPF ja existe no tenant (cliente ativo) |

### Exemplo de erro 422

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cnpj_cpf": ["CNPJ invalido. Verifique os digitos verificadores."],
    "uf": ["O campo uf deve ser um estado brasileiro valido."]
  }
}
```

---

## GET /clientes/{id}

**Descricao:** Detalhe completo do cliente. Retorna 404 para clientes de outro tenant.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | integer | ID do cliente |

### Response 200 OK

```json
{
  "data": {
    "id": 1,
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
    "cep": "01310-100",
    "regime_tributario": "Lucro Presumido",
    "limite_credito": "5000.00",
    "ativo": true,
    "contatos_count": 3,
    "instrumentos_count": 7,
    "created_at": "2026-04-15T10:00:00-03:00",
    "updated_at": "2026-04-15T10:00:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao de leitura |
| 404 | `not_found` | Cliente nao existe no tenant (inclui cliente de outro tenant) |

---

## PUT /clientes/{id}

**Descricao:** Atualiza campos editaveis do cliente. Campos `cnpj_cpf` e `tipo_pessoa` nao podem ser alterados apos criacao.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | integer | ID do cliente |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `razao_social` | string | nao | max:255 | `"Calibra Laboratorios SA"` |
| `nome_fantasia` | string | nao | max:255; nullable | `"CalibLab Premium"` |
| `logradouro` | string | nao | max:255 | `"Av. Paulista"` |
| `numero` | string | nao | max:20 | `"1000"` |
| `complemento` | string | nao | max:100; nullable | `"Sala 50"` |
| `bairro` | string | nao | max:100 | `"Bela Vista"` |
| `cidade` | string | nao | max:100 | `"Sao Paulo"` |
| `uf` | string | nao | `size:2`, estados brasileiros validos | `"SP"` |
| `cep` | string | nao | formato `NNNNN-NNN` ou `NNNNNNNN` | `"01310-100"` |
| `regime_tributario` | enum | nao | aceita labels ou slugs (ver POST); banco armazena slugs | `"Simples"` |
| `limite_credito` | numeric | nao | min:0, max:9999999.99 | `10000.00` |

> Pelo menos um campo deve ser enviado (validacao de payload nao-vazio).

### Response 200 OK

Retorna o mesmo schema de `GET /clientes/{id}` com valores atualizados.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `tecnico` tentando editar |
| 404 | `not_found` | Cliente nao existe no tenant |
| 422 | `validation_error` | Campos invalidos |

---

## DELETE /clientes/{id}

**Descricao:** Desativa o cliente (soft-delete via `ativo = false`). O registro e preservado no banco e continua acessivel via `GET /clientes/{id}`.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | integer | ID do cliente |

### Response 200 OK

```json
{
  "message": "Cliente desativado com sucesso.",
  "data": {
    "id": 1,
    "ativo": false,
    "updated_at": "2026-04-15T15:30:00-03:00"
  }
}
```

> Nota: retorna 200 (nao 204) para permitir que o frontend exiba confirmacao com dados do recurso desativado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao de escrita |
| 404 | `not_found` | Cliente nao existe no tenant |
| 409 | `cliente_ja_inativo` | Cliente ja esta com `ativo = false` |

---

## Regras de negocio transversais

1. `cnpj_cpf` e `tipo_pessoa` sao imutaveis apos criacao.
2. Unicidade de `cnpj_cpf` e validada apenas contra clientes com `ativo = true` no mesmo tenant.
3. Clientes com `ativo = false` nao aparecem na listagem padrao (`GET /clientes` sem `?ativo=false`), mas sao acessiveis via `GET /clientes/{id}`.
4. Todas as queries filtram por `tenant_id` via scope global — nenhum registro de outro tenant e retornado.
5. Audit log (owen-it/laravel-auditing) registra automaticamente `created`, `updated`, `deleted` nos eventos de escrita.

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 | POST /clientes | Criacao PJ com CNPJ valido |
| AC-002 | POST /clientes | Criacao PF com CPF valido |
| AC-003 | POST /clientes | Rejeicao CNPJ invalido → 422 |
| AC-004 | POST /clientes | Rejeicao CPF invalido → 422 |
| AC-005 | POST /clientes | CNPJ duplicado no tenant → 422 |
| AC-006 | POST /clientes | CNPJ do tenant B aceito |
| AC-007 | GET /clientes | Listagem paginada, isolamento de tenant |
| AC-008 | GET /clientes?search= | Filtro por razao_social/nome_fantasia |
| AC-009 | PUT /clientes/{id} | Edicao de limite_credito e regime |
| AC-010 | DELETE /clientes/{id} | Soft-delete via ativo=false |
| AC-011 | POST /clientes | Tecnico recebe 403 |
| AC-012 | GET /clientes, GET /clientes/{id} | Tecnico recebe 200 |
| AC-013 | GET /clientes/{id} | Cross-tenant retorna 404 |
