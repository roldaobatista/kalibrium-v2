# API Contract — Padroes de Referencia e Alertas (E03)

> **Stories:** E03-S04a, E03-S04b, E03-S05a, E03-S05b
> **Recursos:** PadraoReferencia, AlertaSistema
> **Models:** `App\Models\PadraoReferencia`, `App\Models\AlertaSistema`

---

## Cabecalho comum a todos os endpoints

| Header | Valor | Obrigatorio |
|---|---|---|
| `Accept` | `application/json` | sim |
| `Authorization` | `Bearer <token>` | sim |

---

## Status de vigencia calculado

| Valor | Condicao | Descricao |
|---|---|---|
| `vigente` | `certificado_validade > hoje + 30 dias` | Dentro da validade com folga |
| `proximo_vencimento` | `certificado_validade BETWEEN hoje AND hoje + 30 dias` | Requer atencao — alerta gerado |
| `vencido` | `certificado_validade < hoje` | Bloqueado para uso em OS (E05) |

---

## GET /padroes

**Descricao:** Lista padroes de referencia do tenant com status de vigencia calculado, paginacao e filtros. Retorna apenas padroes ativos por padrao.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 60 req/min

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `search` | string | nao | max:100; busca em `modelo`, `numero_serie`, `certificado_numero` | `"RBC-001"` |
| `dominio_metrologico` | enum | nao | `in:dimensional,pressao,massa,temperatura` | `"massa"` |
| `status_validade` | enum | nao | `in:vigente,proximo_vencimento,vencido` | `"proximo_vencimento"` |
| `ativo` | boolean | nao | `in:true,false`; padrao: `true` | `"true"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |
| `sort` | string | nao | `in:modelo,-modelo,certificado_validade,-certificado_validade`; padrao: `modelo` | `"certificado_validade"` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
      "modelo": "Bloco padrao INMETRO",
      "fabricante": "INMETRO",
      "numero_serie": "RBC-001",
      "certificado_numero": "RBC-2025-0001",
      "certificado_validade": "2027-03-15",
      "dominio_metrologico": "dimensional",
      "faixa_medicao_min": "0.00",
      "faixa_medicao_max": "100.00",
      "unidade_faixa": "mm",
      "resolucao": "0.001",
      "resolucao_unidade": "mm",
      "padrao_pai_id": null,
      "status_validade": "vigente",
      "ativo": true,
      "observacoes": null,
      "created_at": "2026-04-15T10:00:00-03:00",
      "updated_at": "2026-04-15T10:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 12,
    "last_page": 1,
    "from": 1,
    "to": 12
  },
  "links": {
    "first": "/padroes?page=1",
    "last": "/padroes?page=1",
    "prev": null,
    "next": null
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |

---

## POST /padroes

**Descricao:** Cria novo padrao de referencia. Somente `admin` pode criar.
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `modelo` | string | sim | max:255 | `"Bloco padrao INMETRO"` |
| `fabricante` | string | nao | max:255; nullable | `"INMETRO"` |
| `numero_serie` | string | nao | max:100; nullable; unico por tenant quando nao-null | `"RBC-001"` |
| `certificado_numero` | string | sim | max:100 | `"RBC-2025-0001"` |
| `certificado_validade` | date | sim | formato `YYYY-MM-DD`; data futura recomendada | `"2027-03-15"` |
| `dominio_metrologico` | enum | sim | `in:dimensional,pressao,massa,temperatura` | `"dimensional"` |
| `faixa_medicao_min` | numeric | sim | formato decimal | `0.00` |
| `faixa_medicao_max` | numeric | sim | formato decimal; maior que min | `100.00` |
| `unidade_faixa` | string | sim | max:20 | `"mm"` |
| `resolucao` | numeric | sim | formato decimal, positivo | `0.001` |
| `resolucao_unidade` | string | sim | max:20 | `"mm"` |
| `padrao_pai_id` | UUID | nao | nullable; exists:padroes_referencia,id (do tenant); validacao anti-ciclo transitivo | `"a1b2c3d4-..."` |
| `observacoes` | string | nao | max:2000; nullable | `null` |

### Response 201 Created

```json
{
  "data": {
    "id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
    "modelo": "Bloco padrao INMETRO",
    "fabricante": "INMETRO",
    "numero_serie": "RBC-001",
    "certificado_numero": "RBC-2025-0001",
    "certificado_validade": "2027-03-15",
    "dominio_metrologico": "dimensional",
    "faixa_medicao_min": "0.00",
    "faixa_medicao_max": "100.00",
    "unidade_faixa": "mm",
    "resolucao": "0.001",
    "resolucao_unidade": "mm",
    "padrao_pai_id": null,
    "status_validade": "vigente",
    "ativo": true,
    "observacoes": null,
    "created_at": "2026-04-15T10:00:00-03:00",
    "updated_at": "2026-04-15T10:00:00-03:00"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `atendente` ou `tecnico` tentando criar |
| 422 | `validation_error` | Campos invalidos |
| 422 | `numero_serie_duplicado` | Numero de serie ja existe no tenant |
| 422 | `ciclo_rastreabilidade` | `padrao_pai_id` criaria ciclo transitivo na cadeia |
| 404 | `padrao_pai_not_found` | `padrao_pai_id` nao existe no tenant |

---

## GET /padroes/{id}

**Descricao:** Detalhe do padrao. Retorna 404 para padroes de outro tenant.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do padrao |

### Response 200 OK

Mesmo schema do item em `POST /padroes`, com campo `esta_vigente` calculado:

```json
{
  "data": {
    "id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
    "modelo": "Bloco padrao INMETRO",
    "fabricante": "INMETRO",
    "numero_serie": "RBC-001",
    "certificado_numero": "RBC-2025-0001",
    "certificado_validade": "2027-03-15",
    "dominio_metrologico": "dimensional",
    "faixa_medicao_min": "0.00",
    "faixa_medicao_max": "100.00",
    "unidade_faixa": "mm",
    "resolucao": "0.001",
    "resolucao_unidade": "mm",
    "padrao_pai_id": null,
    "status_validade": "vigente",
    "esta_vigente": true,
    "ativo": true,
    "observacoes": null,
    "created_at": "2026-04-15T10:00:00-03:00",
    "updated_at": "2026-04-15T10:00:00-03:00"
  }
}
```

> `esta_vigente` = resultado do method `$padrao->estaVigente()` — `false` quando `certificado_validade < hoje`.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `not_found` | Padrao nao existe ou pertence a outro tenant |

---

## PUT /padroes/{id}

**Descricao:** Atualiza dados do padrao. `dominio_metrologico` nao e alteravel apos criacao.
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do padrao |

### Request Body

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `modelo` | string | nao | max:255 | `"Bloco padrao INMETRO v2"` |
| `fabricante` | string | nao | max:255; nullable | `"INMETRO"` |
| `numero_serie` | string | nao | max:100; nullable; unico por tenant | `"RBC-001-B"` |
| `certificado_numero` | string | nao | max:100 | `"RBC-2026-0001"` |
| `certificado_validade` | date | nao | formato `YYYY-MM-DD` | `"2028-03-15"` |
| `faixa_medicao_min` | numeric | nao | formato decimal | `0.00` |
| `faixa_medicao_max` | numeric | nao | formato decimal; maior que min | `150.00` |
| `unidade_faixa` | string | nao | max:20 | `"mm"` |
| `resolucao` | numeric | nao | formato decimal, positivo | `0.001` |
| `resolucao_unidade` | string | nao | max:20 | `"mm"` |
| `padrao_pai_id` | UUID | nao | nullable; exists:padroes_referencia,id; anti-ciclo | `null` |
| `observacoes` | string | nao | max:2000; nullable | `null` |

### Response 200 OK

Retorna o schema completo do padrao atualizado.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role sem permissao |
| 404 | `not_found` | Padrao nao existe no tenant |
| 422 | `validation_error` | Campos invalidos |
| 422 | `ciclo_rastreabilidade` | `padrao_pai_id` criaria ciclo transitivo |

---

## DELETE /padroes/{id}

**Descricao:** Desativa o padrao (soft-delete via `ativo = false`).
**Permissao:** `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do padrao |

### Response 200 OK

```json
{
  "message": "Padrao de referencia desativado com sucesso.",
  "data": {
    "id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
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
| 404 | `not_found` | Padrao nao existe no tenant |
| 409 | `padrao_ja_inativo` | Padrao ja esta com `ativo = false` |

---

## GET /padroes/{id}/cadeia

**Descricao:** Retorna a cadeia de rastreabilidade do padrao, do mais recente ao ancestral (raiz). Maximo 10 niveis.
**Permissao:** `tecnico`, `atendente`, `admin`
**Rate limit:** 120 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do padrao raiz da consulta |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "h8i9j0k1-l2m3-4567-hijk-678901234567",
      "modelo": "Paquimetro LAB-002",
      "numero_serie": "LAB-002",
      "certificado_validade": "2027-06-30",
      "status_validade": "vigente",
      "nivel": 0
    },
    {
      "id": "g7h8i9j0-k1l2-3456-ghij-567890123456",
      "modelo": "Paquimetro LAB-001",
      "numero_serie": "LAB-001",
      "certificado_validade": "2026-12-31",
      "status_validade": "vigente",
      "nivel": 1
    },
    {
      "id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
      "modelo": "Bloco padrao INMETRO",
      "numero_serie": "RBC-001",
      "certificado_validade": "2027-03-15",
      "status_validade": "vigente",
      "nivel": 2
    }
  ],
  "meta": {
    "profundidade": 3,
    "limite_maximo": 10
  }
}
```

> `nivel: 0` = o proprio padrao consultado; `nivel: N` = N ancestrais acima.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `not_found` | Padrao nao existe no tenant |

---

## GET /padroes/vencidos

**Descricao:** Lista padroes ativos com `certificado_validade < hoje` no tenant.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 60 req/min

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `dominio_metrologico` | enum | nao | `in:dimensional,pressao,massa,temperatura` | `"temperatura"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |

### Response 200 OK

Mesmo schema de `GET /padroes`, com todos os itens tendo `status_validade: "vencido"`.

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `atendente` tentando acessar |

---

## GET /padroes/proximos-vencimento

**Descricao:** Lista padroes ativos com `certificado_validade BETWEEN hoje AND hoje + 30 dias` no tenant.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 60 req/min

### Query params

Mesmos de `GET /padroes/vencidos`.

### Response 200 OK

Mesmo schema de `GET /padroes`, com todos os itens tendo `status_validade: "proximo_vencimento"`.

### Erros

Mesmos de `GET /padroes/vencidos`.

---

## GET /alertas

**Descricao:** Lista alertas de vencimento pendentes (`visto_em IS NULL`) do tenant, ordenados por `created_at DESC`.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 60 req/min

### Query params

| Campo | Tipo | Obrigatorio | Validacao | Exemplo |
|---|---|---|---|---|
| `tipo` | enum | nao | `in:proximo_vencimento,vencido` | `"vencido"` |
| `page` | integer | nao | min:1; padrao: 1 | `1` |
| `per_page` | integer | nao | min:1, max:100; padrao: 20 | `20` |

### Response 200 OK

```json
{
  "data": [
    {
      "id": "i9j0k1l2-m3n4-5678-ijkl-789012345678",
      "tipo": "vencido",
      "padrao_referencia_id": "f6a7b8c9-d0e1-2345-fabc-456789012345",
      "padrao_modelo": "Manometro LAB-003",
      "padrao_numero_serie": "LAB-003",
      "certificado_validade": "2026-03-01",
      "visto_em": null,
      "visto_por_user_id": null,
      "created_at": "2026-04-15T06:00:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1,
    "pendentes": 2
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Role `atendente` tentando acessar |

---

## PATCH /alertas/{id}/visto

**Descricao:** Marca o alerta como visto. Preenche `visto_em = now()` e `visto_por_user_id`.
**Permissao:** `tecnico`, `admin`
**Rate limit:** 30 req/min

### Path params

| Param | Tipo | Descricao |
|---|---|---|
| `id` | UUID | ID do alerta |

### Request Body

Sem body (acao idem-potente de marcacao).

### Response 200 OK

```json
{
  "message": "Alerta marcado como visto.",
  "data": {
    "id": "i9j0k1l2-m3n4-5678-ijkl-789012345678",
    "visto_em": "2026-04-15T10:30:00-03:00",
    "visto_por_user_id": "d4e5f6a7-b8c9-0123-defa-234567890123"
  }
}
```

### Erros

| Status | Code | Descricao |
|---|---|---|
| 401 | `unauthenticated` | Token ausente ou invalido |
| 403 | `forbidden` | Sem permissao |
| 404 | `not_found` | Alerta nao existe no tenant |
| 409 | `alerta_ja_visto` | Alerta ja foi marcado como visto |

---

## Regras de negocio transversais

1. `dominio_metrologico` e imutavel apos criacao.
2. `numero_serie` e unico por tenant quando nao-null (constraint parcial).
3. Anti-ciclo: antes de persistir `padrao_pai_id`, o sistema valida que nenhum ancestral e o proprio padrao sendo editado.
4. `status_validade` e calculado dinamicamente (`Carbon::now()`) — nao armazenado no banco.
5. `esta_vigente` = `certificado_validade >= hoje` (metodo `estaVigente()` do model).
6. Padrao com `esta_vigente = false` deve ser bloqueado para uso em novas OS (enforcement em E05).
7. Job `VerificarVencimentoPadroes` roda diariamente as 06:00 BRT (`America/Sao_Paulo`) e e idempotente.
8. Badge de alerta no menu exibe contagem de alertas com `visto_em IS NULL` para roles `admin` e `tecnico`.

---

## Relacao com ACs

| AC | Endpoint | Descricao |
|---|---|---|
| AC-001 (S04a) | POST /padroes | Criacao sem pai (raiz) |
| AC-002 (S04a) | POST /padroes | Criacao com pai (nivel 2) |
| AC-003 (S04a) | POST /padroes | Criacao nivel 3 |
| AC-004 (S04a) | GET /padroes/{id}/cadeia | Consulta cadeia 3 niveis |
| AC-005 (S04a) | PUT /padroes/{id} | Anti-ciclo → 422 |
| AC-006 (S04b) | POST /padroes | Numero de serie duplicado → 422 |
| AC-007 (S04b) | GET /padroes/{id} | Cross-tenant → 404 |
| AC-008 (S04b) | GET /padroes | status_validade calculado |
| AC-009 (S04b) | GET /padroes?dominio_metrologico= | Filtro por dominio |
| AC-010 (S04b) | POST /padroes | Atendente → 403 |
| AC-011 (S04b) | POST /padroes | Admin pode criar |
| AC-012 (S04b) | GET /padroes | Tecnico pode listar |
| AC-013 (S04b) | DELETE /padroes/{id} | Soft-delete via ativo=false |
| AC-001 (S05a) | Job interno | Alerta proximo_vencimento (janela 30 dias) |
| AC-002 (S05a) | Job interno | Alerta vencido |
| AC-009 (S05a) | GET /padroes/{id} | esta_vigente=false para vencido |
| AC-010 (S05a) | GET /padroes/{id} | esta_vigente=true para vigente |
| AC-007 (S05b) | PATCH /alertas/{id}/visto | Marcar visto |
| AC-008 (S05b) | GET /alertas | Isolamento de alertas |
| AC-011 (S05b) | GET /padroes/vencidos | Apenas vencidos do tenant |
