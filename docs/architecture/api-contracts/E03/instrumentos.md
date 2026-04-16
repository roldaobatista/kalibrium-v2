# API Contract — Instrumentos (E03)

> **Stories:** E03-S03a, E03-S03b
> **Recurso:** Instrumento
> **Model:** `App\Models\Instrumento`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |

---

## Dominios metrologicos suportados (MVP)

| Valor | Descricao |
|---|---|
| `dimensional` | Medicao de comprimento, espessura, diametro |
| `pressao` | Medicao de pressao (bar, kPa, psi) |
| `massa` | Medicao de massa (kg, g) |
| `temperatura` | Medicao de temperatura (°C, °F, K) |

---

## GET /instrumentos

**Descricao:** Listagem global de instrumentos do tenant (todos os clientes). Util para recepcionista localizar instrumento pelo numero de serie. Retorna apenas instrumentos ativos por padrao.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 60 req/min

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `search` | string | nao | max:100; busca em `modelo`, `numero_serie`, `fabricante` (case-insensitive) | `"MT-0001"` |
| `dominio_metrologico` | enum | nao | `in:dimensional,pressao,massa,temperatura` | `"temperatura"` |
| `cliente_id` | UUID | nao | exists:clientes,id (do tenant) | `"a1b2c3d4-..."` |
| `ativo` | boolean | nao | `in:true,false`; padrao: `true` | `"true"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |
| `sort` | string | nao | `in:modelo,-modelo,created_at,-created_at`; padrao: `modelo` | `"modelo"` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "e5f6a7b8-c9d0-1234-efab-345678901234",
      "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "cliente_nome": "Calibra Laboratorios Ltda",
      "modelo": "Paquimetro Mitutoyo 150mm",
      "fabricante": "Mitutoyo",
      "numero_serie": "MT-0001",
      "dominio_metrologico": "dimensional",
      "faixa_medicao_min": "0.00",
      "faixa_medicao_max": "150.00",
      "unidade_faixa": "mm",
      "resolucao": "0.02",
      "resolucao_unidade": "mm",
      "ativo": true,
      "observacoes": null,
      "historico_calibracoes": [],
      "created_at": "2026-04-15T10:00:00-03:00",
      "updated_at": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 87,
    "last_page": 5,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/instrumentos?page=1",
    "last": "/instrumentos?page=5",
    "prev": null,
    "next": "/instrumentos?page=2"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao de leitura |

---

## GET /clientes/{clienteId}/instrumentos

**Descricao:** Listagem de instrumentos de um cliente especifico, com paginacao e filtros.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 60 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `clienteId` | UUID | ID do cliente |

### Query params

Mesmos parametros de `GET /instrumentos`, exceto `cliente_id` (ja definido pelo path).

### Response 200 OK

Mesmo schema de `GET /instrumentos`.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `cliente_not_found` | Cliente nao existe no tenant |

---

## POST /instrumentos

**Descricao:** Cria novo instrumento vinculado a um cliente.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `cliente_id` | UUID | sim | exists:clientes,id (do tenant) | `"a1b2c3d4-..."` |
| `modelo` | string | sim | max:255 | `"Paquimetro Mitutoyo 150mm"` |
| `fabricante` | string | nao | max:255; nullable | `"Mitutoyo"` |
| `numero_serie` | string | nao | max:100; nullable; unico por tenant quando nao-null | `"MT-0001"` |
| `dominio_metrologico` | enum | sim | `in:dimensional,pressao,massa,temperatura` | `"dimensional"` |
| `faixa_medicao_min` | numeric | sim | formato decimal | `0.00` |
| `faixa_medicao_max` | numeric | sim | formato decimal; maior que `faixa_medicao_min` | `150.00` |
| `unidade_faixa` | string | sim | max:20 | `"mm"` |
| `resolucao` | numeric | sim | formato decimal, positivo | `0.02` |
| `resolucao_unidade` | string | sim | max:20 | `"mm"` |
| `observacoes` | string | nao | max:2000; nullable | `"Verificar antes de usar"` |

### Response 201 Created

```json
{
  "data": {
    "id": "e5f6a7b8-c9d0-1234-efab-345678901234",
    "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "cliente_nome": "Calibra Laboratorios Ltda",
    "modelo": "Paquimetro Mitutoyo 150mm",
    "fabricante": "Mitutoyo",
    "numero_serie": "MT-0001",
    "dominio_metrologico": "dimensional",
    "faixa_medicao_min": "0.00",
    "faixa_medicao_max": "150.00",
    "unidade_faixa": "mm",
    "resolucao": "0.02",
    "resolucao_unidade": "mm",
    "ativo": true,
    "observacoes": null,
    "historico_calibracoes": [],
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
| 422 | `validation_error` | Campos invalidos |
| 422 | `dominio_invalido` | Dominio fora do enum MVP |
| 422 | `numero_serie_duplicado` | Numero de serie ja existe no tenant |
| 422 | `faixa_invalida` | `faixa_medicao_max` <= `faixa_medicao_min` |
| 404 | `cliente_not_found` | `cliente_id` nao existe no tenant |

---

## GET /instrumentos/{id}

**Descricao:** Detalhe do instrumento. Retorna 404 para instrumentos de outro tenant. Inclui `historico_calibracoes: []` (placeholder — escrita em E05).
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do instrumento |

### Response 200 OK

Mesmo schema do item em `POST /instrumentos` (response 201), com campo `historico_calibracoes` sempre presente:

```json
{
  "data": {
    "id": "e5f6a7b8-c9d0-1234-efab-345678901234",
    "cliente_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "cliente_nome": "Calibra Laboratorios Ltda",
    "modelo": "Paquimetro Mitutoyo 150mm",
    "fabricante": "Mitutoyo",
    "numero_serie": "MT-0001",
    "dominio_metrologico": "dimensional",
    "faixa_medicao_min": "0.00",
    "faixa_medicao_max": "150.00",
    "unidade_faixa": "mm",
    "resolucao": "0.02",
    "resolucao_unidade": "mm",
    "ativo": true,
    "observacoes": null,
    "historico_calibracoes": [],
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
| 404 | `not_found` | Instrumento nao existe ou pertence a outro tenant |

---

## PUT /instrumentos/{id}

**Descricao:** Atualiza dados do instrumento. `cliente_id` e `dominio_metrologico` nao sao alteraveis apos criacao.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do instrumento |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `modelo` | string | nao | max:255 | `"Paquimetro Digital 200mm"` |
| `fabricante` | string | nao | max:255; nullable | `"Mitutoyo"` |
| `numero_serie` | string | nao | max:100; nullable; unico por tenant | `"MT-0001-B"` |
| `faixa_medicao_min` | numeric | nao | formato decimal | `0.00` |
| `faixa_medicao_max` | numeric | nao | formato decimal; maior que min | `200.00` |
| `unidade_faixa` | string | nao | max:20 | `"mm"` |
| `resolucao` | numeric | nao | formato decimal, positivo | `0.01` |
| `resolucao_unidade` | string | nao | max:20 | `"mm"` |
| `observacoes` | string | nao | max:2000; nullable | `null` |

### Response 200 OK

Retorna o schema completo do instrumento atualizado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao de escrita |
| 404 | `not_found` | Instrumento nao existe no tenant |
| 422 | `validation_error` | Campos invalidos |
| 422 | `numero_serie_duplicado` | Numero de serie ja existe no tenant |

---

## DELETE /instrumentos/{id}

**Descricao:** Desativa o instrumento (soft-delete via `ativo = false`). O instrumento permanece acessivel via `GET /instrumentos/{id}`.
**Permissao:** `atendente`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do instrumento |

### Response 200 OK

```json
{
  "message": "Instrumento desativado com sucesso.",
  "data": {
    "id": "e5f6a7b8-c9d0-1234-efab-345678901234",
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
| 404 | `not_found` | Instrumento nao existe no tenant |
| 409 | `instrumento_ja_inativo` | Instrumento ja esta com `ativo = false` |

---

## Regras de negocio transversais

1. `cliente_id` e `dominio_metrologico` sao imutaveis apos criacao.
2. `numero_serie` e unico por tenant apenas quando nao-null (constraint parcial).
3. `historico_calibracoes` retorna sempre `[]` neste epico — preenchido pelo E05.
4. Dominio `eletrica` e qualquer outro fora do enum MVP retorna 422.
5. Instrumento herda isolamento de tenant via `cliente_id` (scope do cliente pai).

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 a AC-004 (S03a) | POST /instrumentos | Criacao nos 4 dominios |
| AC-005 (S03a) | POST /instrumentos | Dominio invalido → 422 |
| AC-006 (S03a) | POST /instrumentos | Numero de serie duplicado → 422 |
| AC-007 (S03a) | POST /instrumentos | Numero de serie do tenant B aceito |
| AC-008 (S03b) | GET /clientes/{id}/instrumentos | Listagem paginada por cliente |
| AC-009 (S03b) | GET /instrumentos?dominio_metrologico= | Filtro por dominio |
| AC-010 (S03b) | GET /instrumentos | Isolamento cross-tenant |
| AC-011 (S03b) | GET /instrumentos/{id} | Cross-tenant retorna 404 |
| AC-012 (S03b) | GET /instrumentos/{id} | historico_calibracoes: [] |
| AC-013 (S03b) | POST /instrumentos | Tecnico recebe 403 |
| AC-014 (S03b) | GET /instrumentos, GET /instrumentos/{id} | Tecnico recebe 200 |
| AC-015 (S03b) | DELETE /instrumentos/{id} | Soft-delete via ativo=false |
