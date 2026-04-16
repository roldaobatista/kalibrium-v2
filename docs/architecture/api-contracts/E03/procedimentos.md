# API Contract — Procedimentos de Calibracao (E03)

> **Stories:** E03-S06a, E03-S06b
> **Recurso:** ProcedimentoCalibracao
> **Model:** `App\Models\ProcedimentoCalibracao`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |

---

## Status do procedimento

| Valor | Transicoes permitidas | Descricao |
|---|---|---|
| `rascunho` | → `vigente` (via publicar) | Editavel; nao pode ser usado em OS |
| `vigente` | → `obsoleto` (automatico ao publicar nova versao) | Imutavel; ativo para uso |
| `obsoleto` | nenhuma | Imutavel; versao anterior preservada |

---

## GET /procedimentos

**Descricao:** Lista procedimentos do tenant. Por padrao exibe apenas a versao mais recente de cada `codigo` (a com maior `versao` semantica). Use `?historico=true` para ver todas as versoes. Retorna apenas procedimentos de tenants do usuario.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 60 req/min

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `search` | string | nao | max:100; busca em `nome`, `codigo` | `"PG-DIM"` |
| `dominio_metrologico` | enum | nao | `in:dimensional,pressao,massa,temperatura` | `"pressao"` |
| `status` | enum | nao | `in:rascunho,vigente,obsoleto` | `"vigente"` |
| `historico` | boolean | nao | `in:true,false`; padrao: `false` | `"true"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |
| `sort` | string | nao | `in:nome,-nome,codigo,-codigo,publicado_em,-publicado_em`; padrao: `codigo` | `"codigo"` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "j0k1l2m3-n4o5-6789-jklm-890123456789",
      "codigo": "PG-DIM-001",
      "versao": "1.1",
      "nome": "Procedimento Dimensional 01",
      "dominio_metrologico": "dimensional",
      "descricao": "Procedimento para calibracao de instrumentos dimensionais",
      "validade": null,
      "status": "vigente",
      "publicado_em": "2026-04-15T09:00:00-03:00",
      "publicado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
      "created_at": "2026-04-15T08:00:00-03:00",
      "updated_at": "2026-04-15T09:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1,
    "historico": false
  }
}
```

> Quando `?historico=false` (padrao), o backend usa `DISTINCT ON (codigo) ORDER BY codigo, versao DESC` no PostgreSQL para retornar apenas a versao mais recente por codigo.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `atendente` → 403 em qualquer acesso |

---

## POST /procedimentos

**Descricao:** Cria novo procedimento em status `rascunho`. Somente `admin` pode criar.
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `codigo` | string | sim | max:50; regex:`/^[A-Za-z0-9\-]+$/` (alfanumerico com hifens, sem espacos); unico por tenant + versao | `"PG-DIM-001"` |
| `versao` | string | sim | max:20; formato semântico recomendado (ex: "1.0", "2.1"); par `(codigo, versao)` unico por tenant | `"1.0"` |
| `nome` | string | sim | max:255 | `"Procedimento Dimensional 01"` |
| `dominio_metrologico` | enum | sim | `in:dimensional,pressao,massa,temperatura` | `"dimensional"` |
| `descricao` | string | nao | max:5000; nullable | `"Procedimento para calibracao..."` |
| `validade` | date | nao | formato `YYYY-MM-DD`; nullable | `"2028-12-31"` |

> O status inicial e sempre `rascunho` — nao pode ser informado no request.

### Response 201 Created

```json
{
  "data": {
    "id": "j0k1l2m3-n4o5-6789-jklm-890123456789",
    "codigo": "PG-DIM-001",
    "versao": "1.0",
    "nome": "Procedimento Dimensional 01",
    "dominio_metrologico": "dimensional",
    "descricao": "Procedimento para calibracao de instrumentos dimensionais",
    "validade": null,
    "status": "rascunho",
    "publicado_em": null,
    "publicado_por_user_id": null,
    "created_at": "2026-04-15T08:00:00-03:00",
    "updated_at": "2026-04-15T08:00:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `tecnico` ou `atendente` tentando criar |
| 422 | `validation_error` | Campos invalidos |
| 422 | `codigo_formato_invalido` | Codigo contem espacos ou caracteres invalidos |
| 422 | `versao_duplicada` | Par `(codigo, versao)` ja existe no tenant |

---

## GET /procedimentos/{id}

**Descricao:** Detalhe do procedimento. Retorna 404 para procedimentos de outro tenant.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do procedimento |

### Response 200 OK

Mesmo schema do item em `POST /procedimentos`.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `atendente` → 403 |
| 404 | `not_found` | Procedimento nao existe ou pertence a outro tenant |

---

## PUT /procedimentos/{id}

**Descricao:** Atualiza campos do procedimento. Somente procedimentos com `status = rascunho` podem ser editados. Procedimento `vigente` ou `obsoleto` retorna 422.
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do procedimento |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `nome` | string | nao | max:255 | `"Procedimento Dimensional 01 rev"` |
| `descricao` | string | nao | max:5000; nullable | `"Descricao atualizada"` |
| `validade` | date | nao | formato `YYYY-MM-DD`; nullable | `"2029-12-31"` |

> `codigo`, `versao` e `dominio_metrologico` sao imutaveis apos criacao.

### Response 200 OK

Retorna o schema completo do procedimento atualizado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Procedimento nao existe no tenant |
| 422 | `procedimento_nao_editavel` | Procedimento esta `vigente` ou `obsoleto` — crie nova versao |
| 422 | `validation_error` | Campos invalidos |

---

## POST /procedimentos/{id}/publicar

**Descricao:** Publica o procedimento (transicao `rascunho → vigente`). Atomicamente marca a versao anterior do mesmo `codigo` como `obsoleto`.
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do procedimento em rascunho |

### Request Body

Sem body.

### Response 200 OK

```json
{
  "message": "Procedimento publicado com sucesso.",
  "data": {
    "id": "j0k1l2m3-n4o5-6789-jklm-890123456789",
    "codigo": "PG-DIM-001",
    "versao": "1.1",
    "status": "vigente",
    "publicado_em": "2026-04-15T09:00:00-03:00",
    "publicado_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123",
    "versao_anterior_obsoleta": {
      "id": "k1l2m3n4-o5p6-7890-klmn-901234567890",
      "codigo": "PG-DIM-001",
      "versao": "1.0",
      "status": "obsoleto"
    }
  }
}
```

> A transicao e atomica: se falhar ao marcar a versao anterior como obsoleta, a publicacao e revertida (transaction de banco).

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Procedimento nao existe no tenant |
| 409 | `procedimento_nao_rascunho` | Procedimento ja esta `vigente` ou `obsoleto` |

---

## Regras de negocio transversais

1. `codigo`, `versao` e `dominio_metrologico` sao imutaveis apos criacao.
2. O par `(codigo, versao)` e unico por tenant.
3. Somente procedimentos com `status = rascunho` podem ser editados.
4. Publicar nova versao do mesmo `codigo` torna a versao `vigente` anterior em `obsoleto` — atomicamente via transaction.
5. Versoes obsoletas nao sao deletadas — permanecem no banco para rastreabilidade.
6. `atendente` nao tem acesso a nenhum endpoint de procedimentos (403 em GET e POST).
7. `tecnico` tem somente leitura (403 em POST e publicar).
8. Listagem padrao (`?historico=false`) usa `DISTINCT ON (codigo) ORDER BY codigo, versao DESC`.

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 (S06a) | POST /procedimentos | Criacao em rascunho |
| AC-002 (S06a) | POST /procedimentos/{id}/publicar | Publicacao: rascunho → vigente |
| AC-003 (S06a) | POST /procedimentos/{id}/publicar | Nova versao obsoleta anterior |
| AC-004 (S06a) | POST /procedimentos | Par (codigo, versao) duplicado → 422 |
| AC-008 (S06a) | PUT /procedimentos/{id} | Vigente nao editavel → 422 |
| AC-005 (S06b) | GET /procedimentos | Listagem sem historico: so versao mais recente |
| AC-006 (S06b) | GET /procedimentos?historico=true | Todas as versoes |
| AC-007 (S06b) | GET /procedimentos?dominio_metrologico= | Filtro por dominio |
| AC-009 (S06b) | GET /procedimentos/{id} | Cross-tenant → 404 |
| AC-010 (S06b) | GET /procedimentos | Atendente → 403 |
| AC-011 (S06b) | GET /procedimentos | Tecnico → 200 |
| AC-012 (S06b) | POST /procedimentos | Tecnico → 403 |
| AC-013 (S06b) | POST /procedimentos | Codigo com espacos → 422 |
