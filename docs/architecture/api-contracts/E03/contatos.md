# API Contract — Contatos e Consentimentos LGPD (E03)

> **Stories:** E03-S02a, E03-S02b
> **Recursos:** Contato, ConsentimentoLGPD
> **Models:** `App\Models\Contato`, `App\Models\ConsentimentoLGPD`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |

---

## GET /clientes/{clienteId}/contatos

**Descricao:** Lista contatos do cliente. Sem paginacao se <= 10 registros; paginada (20/pagina) se > 10. Retorna apenas contatos ativos por padrao.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `clienteId` | UUID | ID do cliente pai |

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `ativo` | boolean | nao | `in:true,false`; padrao: `true` | `"true"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
      "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "nome": "Ana Paula Silva",
      "email": "ana.silva@calibralab.com.br",
      "whatsapp": "11987654321",
      "papel": "responsavel_tecnico",
      "ativo": true,
      "consentimentos": {
        "email_marketing": false,
        "whatsapp": true
      },
      "created_at": "2026-04-15T10:00:00-03:00",
      "updated_at": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 3,
    "last_page": 1
  }
}
```

> O campo `consentimentos` reflete o estado mais recente (ultimo registro por canal) — `true` = consentimento ativo e nao revogado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `cliente_not_found` | Cliente nao existe no tenant |

---

## POST /clientes/{clienteId}/contatos

**Descricao:** Cria novo contato vinculado ao cliente.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `clienteId` | UUID | ID do cliente pai |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `nome` | string | sim | max:255 | `"Ana Paula Silva"` |
| `email` | string | nao | formato email valido; max:255; nullable | `"ana.silva@calibralab.com.br"` |
| `whatsapp` | string | nao | minimo 10 digitos numericos (DDD + numero); nullable | `"11987654321"` |
| `papel` | enum | sim | `in:comprador,responsavel_tecnico,outro` | `"responsavel_tecnico"` |

> Pelo menos `email` ou `whatsapp` deve ser fornecido (validacao condicional).

### Response 201 Created

```json
{
  "data": {
    "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "nome": "Ana Paula Silva",
    "email": "ana.silva@calibralab.com.br",
    "whatsapp": "11987654321",
    "papel": "responsavel_tecnico",
    "ativo": true,
    "consentimentos": {
      "email_marketing": false,
      "whatsapp": false
    },
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
| 404 | `cliente_not_found` | Cliente nao existe no tenant |
| 422 | `validation_error` | Campos invalidos |
| 422 | `whatsapp_invalido` | WhatsApp sem DDD ou com menos de 10 digitos |

---

## GET /contatos/{id}

**Descricao:** Detalhe do contato. Retorna 404 para contatos de clientes de outro tenant.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |

### Response 200 OK

Mesmo schema do item em `GET /clientes/{clienteId}/contatos`, com campo adicional:

```json
{
  "data": {
    "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "nome": "Ana Paula Silva",
    "email": "ana.silva@calibralab.com.br",
    "whatsapp": "11987654321",
    "papel": "responsavel_tecnico",
    "ativo": true,
    "consentimentos": {
      "email_marketing": false,
      "whatsapp": true
    },
    "created_at": "2026-04-15T10:00:00-03:00",
    "updated_at": "2026-04-15T10:00:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `not_found` | Contato nao existe ou pertence a cliente de outro tenant |

---

## PUT /contatos/{id}

**Descricao:** Atualiza dados do contato.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `nome` | string | nao | max:255 | `"Ana Paula Oliveira"` |
| `email` | string | nao | formato email valido; max:255; nullable | `"ana.oliveira@calibralab.com.br"` |
| `whatsapp` | string | nao | minimo 10 digitos numericos; nullable | `"11912345678"` |
| `papel` | enum | nao | `in:comprador,responsavel_tecnico,outro` | `"comprador"` |

### Response 200 OK

Retorna o schema completo do contato atualizado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao de escrita |
| 404 | `not_found` | Contato nao existe no tenant |
| 422 | `validation_error` | Campos invalidos |

---

## DELETE /contatos/{id}

**Descricao:** Desativa o contato (soft-delete via `ativo = false`). O registro e preservado.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |

### Response 200 OK

```json
{
  "message": "Contato desativado com sucesso.",
  "data": {
    "id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "ativo": false,
    "updated_at": "2026-04-15T15:30:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Contato nao existe no tenant |
| 409 | `contato_ja_inativo` | Contato ja esta com `ativo = false` |

---

## POST /contatos/{id}/consentimentos

**Descricao:** Registra consentimento LGPD para um canal. Sempre cria novo registro (imutabilidade de log) — nao atualiza o existente.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `canal` | enum | sim | `in:email_marketing,whatsapp` | `"email_marketing"` |
| `forma_aceite` | string | sim | max:500; descricao textual da forma de obtencao do consentimento | `"formulario web - pagina de cadastro"` |

### Response 201 Created

```json
{
  "data": {
    "id": "c3d4e5f6-a7b8-9012-cdef-123456789012",
    "contato_id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "canal": "email_marketing",
    "concedido": true,
    "concedido_em": "2026-04-15T10:00:00-03:00",
    "revogado_em": null,
    "forma_aceite": "formulario web - pagina de cadastro",
    "registrado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Contato nao existe no tenant |
| 422 | `validation_error` | Campo `canal` invalido |
| 422 | `whatsapp_nao_preenchido` | Canal `whatsapp` exige campo `whatsapp` preenchido no contato |

---

## DELETE /contatos/{id}/consentimentos/{canal}

**Descricao:** Revoga consentimento LGPD para o canal. Nao deleta o registro original — preenche `revogado_em = now()` e `concedido = false`. Cria novo registro de revogacao (imutabilidade de log).
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |
| `canal` | enum | `email_marketing` ou `whatsapp` |

### Response 200 OK

```json
{
  "message": "Consentimento revogado com sucesso.",
  "data": {
    "id": "c3d4e5f6-a7b8-9012-cdef-123456789012",
    "contato_id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
    "canal": "email_marketing",
    "concedido": false,
    "concedido_em": "2026-04-15T10:00:00-03:00",
    "revogado_em": "2026-04-15T15:30:00-03:00",
    "registrado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Contato ou canal nao encontrado |
| 409 | `consentimento_ja_revogado` | Nao existe consentimento ativo para o canal informado |

---

## GET /contatos/{id}/consentimentos

**Descricao:** Retorna o historico completo de consentimentos do contato (todos os registros, incluindo revogados), ordenados por `concedido_em DESC`.
**Permissao:** `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do contato |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "e5f6a7b8-c9d0-1234-efab-345678901234",
      "canal": "email_marketing",
      "concedido": true,
      "concedido_em": "2026-04-15T11:00:00-03:00",
      "revogado_em": null,
      "forma_aceite": "formulario web pos-revogacao",
      "registrado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123"
    },
    {
      "id": "c3d4e5f6-a7b8-9012-cdef-123456789012",
      "canal": "email_marketing",
      "concedido": false,
      "concedido_em": "2026-04-15T10:00:00-03:00",
      "revogado_em": "2026-04-15T10:30:00-03:00",
      "forma_aceite": "formulario web - pagina de cadastro",
      "registrado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123"
    }
  ]
}
```

> Sem paginacao — historico de consentimentos por contato e limitado na pratica.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Contato nao existe no tenant |

---

## Regras de negocio transversais

1. Contato herda isolamento de tenant via `cliente_id → tenant_id` (scope do cliente pai).
2. Consentimento LGPD e imutavel: nenhum registro e deletado ou sobrescrito — apenas novo registro e criado.
3. O estado ativo de consentimento por canal e determinado pelo registro mais recente (`concedido = true` e `revogado_em IS NULL`).
4. Canal `whatsapp` so pode receber consentimento se `contato.whatsapp` estiver preenchido.
5. `registrado_por_user_id` e preenchido automaticamente com o `id` do usuario autenticado.

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 (S02a) | POST /clientes/{id}/contatos | Criacao de contato com papel |
| AC-002 (S02a) | POST, GET /clientes/{id}/contatos | Multiplos contatos por cliente |
| AC-008 (S02a) | GET /contatos/{id} | Cross-tenant retorna 404 |
| AC-009 (S02a) | POST /clientes/{id}/contatos | Tecnico recebe 403 |
| AC-010 (S02a) | GET /clientes/{id}/contatos | Tecnico recebe 200 |
| AC-011 (S02a) | DELETE /contatos/{id} | Soft-delete via ativo=false |
| AC-012 (S02a) | POST /clientes/{id}/contatos | WhatsApp sem DDD → 422 |
| AC-003 (S02b) | POST /contatos/{id}/consentimentos | Consentimento email_marketing |
| AC-004 (S02b) | POST /contatos/{id}/consentimentos | Consentimento whatsapp |
| AC-005 (S02b) | DELETE /contatos/{id}/consentimentos/{canal} | Revogacao (opt-out) |
| AC-006 (S02b) | GET /contatos/{id}/consentimentos | Historico imutavel |
| AC-007 (S02b) | POST /contatos/{id}/consentimentos | Novo registro apos revogacao |
