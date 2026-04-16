# API Contract — Auditoria (E03)

> **Stories:** E03-S07a, E03-S07b
> **Recurso:** Auditoria (owen-it/laravel-auditing)
> **Model:** tabela `audits` gerenciada pelo pacote
> **Controller:** `App\Http\Controllers\AuditoriaController`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |

---

## Visao geral

Os endpoints de auditoria expõem o historico de alteracoes dos cadastros core do E03, capturado automaticamente pelo `owen-it/laravel-auditing` via trait `Auditable` nos models.

### Models auditados

| Model | Endpoint de consulta |
|---|---|
| `Cliente` | `GET /auditoria/clientes/{id}` |
| `Instrumento` | `GET /auditoria/instrumentos/{id}` |
| `PadraoReferencia` | `GET /auditoria/padroes/{id}` |
| `ProcedimentoCalibracao` | `GET /auditoria/procedimentos/{id}` |
| `Contato` | Auditado pelo S07a, endpoint de consulta fora de escopo (pos-MVP) |

### Eventos capturados automaticamente

| Evento | Quando ocorre |
|---|---|
| `created` | Registro criado |
| `updated` | Registro atualizado |
| `deleted` | Registro desativado (soft-delete via `ativo = false`) |

### Campos excluidos do audit

- `updated_at`
- `remember_token`

---

## GET /auditoria/clientes/{id}

**Descricao:** Historico paginado de alteracoes do cliente, ordenado por `created_at DESC`. Retorna 404 para clientes de outro tenant.
**Permissao:** `admin` exclusivamente
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do cliente |

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `evento` | enum | nao | `in:created,updated,deleted` | `"updated"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "k1l2m3n4-o5p6-7890-klmn-901234567890",
      "evento": "updated",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Maria Atendente",
      "campos_alterados": [
        {
          "campo": "razao_social",
          "valor_anterior": "Empresa Antiga Ltda",
          "valor_novo": "Empresa Nova Ltda"
        },
        {
          "campo": "limite_credito",
          "valor_anterior": "5000.00",
          "valor_novo": "10000.00"
        }
      ],
      "criado_em": "2026-04-15T14:00:00-03:00"
    },
    {
      "id": "l2m3n4o5-p6q7-8901-lmno-012345678901",
      "evento": "created",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Maria Atendente",
      "campos_alterados": [],
      "criado_em": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2,
    "from": 1,
    "to": 20,
    "entidade": "Cliente",
    "entidade_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
  }
}
```

> `campos_alterados` e vazio para eventos `created` e `deleted` — a alteracao completa esta nos `new_values` do evento, disponivel internamente mas nao exposto no MVP.

> Quando `user_id` nao corresponde a usuario ativo, `user_nome` retorna `"(usuario removido)"` — sem erro 500.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role nao e `admin` |
| 404 | `not_found` | Cliente nao existe no tenant |

---

## GET /auditoria/instrumentos/{id}

**Descricao:** Historico paginado de alteracoes do instrumento.
**Permissao:** `admin` exclusivamente
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do instrumento |

### Query params

Mesmos de `GET /auditoria/clientes/{id}`.

### Response 200 OK

Mesmo schema de `GET /auditoria/clientes/{id}`, com `meta.entidade = "Instrumento"`.

Exemplo de entrada de audit de instrumento:

```json
{
  "data": [
    {
      "id": "m3n4o5p6-q7r8-9012-mnop-123456789012",
      "evento": "updated",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Maria Atendente",
      "campos_alterados": [
        {
          "campo": "numero_serie",
          "valor_anterior": "MT-0001",
          "valor_novo": "MT-0001-B"
        }
      ],
      "criado_em": "2026-04-15T11:00:00-03:00"
    },
    {
      "id": "n4o5p6q7-r8s9-0123-nopq-234567890123",
      "evento": "created",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Maria Atendente",
      "campos_alterados": [],
      "criado_em": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1,
    "entidade": "Instrumento",
    "entidade_id": "e5f6a7b8-c9d0-1234-efab-345678901234"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role nao e `admin` |
| 404 | `not_found` | Instrumento nao existe no tenant |

---

## GET /auditoria/padroes/{id}

**Descricao:** Historico paginado de alteracoes do padrao de referencia.
**Permissao:** `admin` exclusivamente
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do padrao de referencia |

### Query params

Mesmos de `GET /auditoria/clientes/{id}`.

### Response 200 OK

Mesmo schema de `GET /auditoria/clientes/{id}`, com `meta.entidade = "PadraoReferencia"`.

Exemplo de entrada de audit de padrao:

```json
{
  "data": [
    {
      "id": "o5p6q7r8-s9t0-1234-opqr-345678901234",
      "evento": "updated",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Joao Admin",
      "campos_alterados": [
        {
          "campo": "certificado_validade",
          "valor_anterior": "2026-12-31",
          "valor_novo": "2027-12-31"
        }
      ],
      "criado_em": "2026-04-15T12:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 3,
    "last_page": 1,
    "entidade": "PadraoReferencia",
    "entidade_id": "f6a7b8c9-d0e1-2345-fabc-456789012345"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role nao e `admin` |
| 404 | `not_found` | Padrao nao existe no tenant |

---

## GET /auditoria/procedimentos/{id}

**Descricao:** Historico paginado de alteracoes do procedimento de calibracao, incluindo transicoes de status (rascunho → vigente → obsoleto).
**Permissao:** `admin` exclusivamente
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do procedimento |

### Query params

Mesmos de `GET /auditoria/clientes/{id}`.

### Response 200 OK

Mesmo schema de `GET /auditoria/clientes/{id}`, com `meta.entidade = "ProcedimentoCalibracao"`.

Exemplo de entrada de audit de procedimento (publicacao):

```json
{
  "data": [
    {
      "id": "p6q7r8s9-t0u1-2345-pqrs-456789012345",
      "evento": "updated",
      "user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "user_nome": "Joao Admin",
      "campos_alterados": [
        {
          "campo": "status",
          "valor_anterior": "rascunho",
          "valor_novo": "vigente"
        },
        {
          "campo": "publicado_em",
          "valor_anterior": null,
          "valor_novo": "2026-04-15T09:00:00-03:00"
        }
      ],
      "criado_em": "2026-04-15T09:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1,
    "entidade": "ProcedimentoCalibracao",
    "entidade_id": "j0k1l2m3-n4o5-6789-jklm-890123456789"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role nao e `admin` |
| 404 | `not_found` | Procedimento nao existe no tenant |

---

## Fora de escopo neste epico

- `GET /auditoria/contatos/{id}` — auditoria de contatos e capturada pelo `Auditable` (S07a), mas o endpoint de consulta fica para pos-MVP por envolver dados pessoais que requerem analise LGPD separada.
- Exportacao em CSV do audit log — pos-MVP.
- Auditoria de OS e calibracoes — E05.
- Auditoria de usuarios e permissoes — E02.

---

## Regras de negocio transversais

1. Somente `admin` acessa qualquer endpoint `/auditoria/*` — `atendente`, `tecnico` e `visualizador` recebem 403.
2. Isolamento de tenant: o endpoint valida que a entidade consultada pertence ao tenant do usuario autenticado antes de buscar os audits; retorna 404 se pertencer a outro tenant.
3. Tratamento de usuario removido: quando `user_id` do registro de audit nao corresponde a usuario ativo no tenant, `user_nome` e preenchido com `"(usuario removido)"` — sem lancamento de excecao nem erro 500.
4. Campos `updated_at` e `remember_token` nao aparecem em `campos_alterados`.
5. Registros de audit sao imutaveis — nenhum endpoint de escrita e exposto neste recurso.
6. Paginacao padrao: 20 registros por pagina, ordenacao `created_at DESC`.

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 (S07a) | Interno (audit automatico) | Criacao de cliente gera audit created |
| AC-002 (S07a) | GET /auditoria/clientes/{id} | Atualizacao com old/new values |
| AC-003 (S07a) | GET /auditoria/clientes/{id} | Desativacao gera audit updated ativo |
| AC-004 (S07a) | Interno | Audit de contato (created + updated) |
| AC-010 (S07a) | GET /auditoria/clientes/{id} | updated_at ausente em campos_alterados |
| AC-005 (S07b) | GET /auditoria/padroes/{id} | Audit de padrao (created + updated validade) |
| AC-006 (S07b) | GET /auditoria/procedimentos/{id} | Publicacao: status rascunho→vigente |
| AC-007 (S07b) | GET /auditoria/clientes/{id} | 20 registros paginados, created_at DESC |
| AC-008 (S07b) | GET /auditoria/clientes/{id} | Cross-tenant → 404 |
| AC-009 (S07b) | Todos /auditoria/* | Atendente/tecnico → 403 |
| AC-011 (S07b) | GET /auditoria/instrumentos/{id} | Audit de instrumento created + updated |
| AC-012 (S07b) | GET /auditoria/clientes/{id} | Usuario removido → user_nome "(usuario removido)" |
