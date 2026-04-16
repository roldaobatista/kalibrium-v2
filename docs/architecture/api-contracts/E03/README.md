# API Contracts — E03 Cadastro Core

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-15
> **Epico:** E03
> **Dependencia:** E02 completo (auth, RBAC, multi-tenancy)

---

## Visao geral

Este diretorio contém os contratos REST de todos os recursos do épico E03 (Cadastro Core). Os contratos devem ser lidos antes de implementar qualquer story do épico.

Convencoes globais em `docs/architecture/api-contracts/README.md`.

---

## Recursos deste epico

| Arquivo | Recurso | Stories |
|---|---|---|
| `clientes.md` | Cliente (CRUD + soft-delete) | E03-S01a, E03-S01b |
| `contatos.md` | Contato + Consentimentos LGPD | E03-S02a, E03-S02b |
| `instrumentos.md` | Instrumento do cliente | E03-S03a, E03-S03b |
| `padroes.md` | Padrao de referencia + cadeia + alertas | E03-S04a, E03-S04b, E03-S05a, E03-S05b |
| `procedimentos.md` | Procedimento de calibracao (versionado) | E03-S06a, E03-S06b |
| `auditoria.md` | Consulta de audit log | E03-S07a, E03-S07b |

---

## Indice de endpoints

### Clientes

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/clientes` | Listagem paginada com filtros |
| POST | `/clientes` | Criar cliente |
| GET | `/clientes/{id}` | Detalhe do cliente |
| PUT | `/clientes/{id}` | Atualizar cliente |
| DELETE | `/clientes/{id}` | Desativar cliente (soft-delete) |

### Contatos

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/clientes/{clienteId}/contatos` | Listar contatos do cliente |
| POST | `/clientes/{clienteId}/contatos` | Criar contato |
| GET | `/contatos/{id}` | Detalhe do contato |
| PUT | `/contatos/{id}` | Atualizar contato |
| DELETE | `/contatos/{id}` | Desativar contato (soft-delete) |
| POST | `/contatos/{id}/consentimentos` | Registrar consentimento LGPD |
| DELETE | `/contatos/{id}/consentimentos/{canal}` | Revogar consentimento LGPD |
| GET | `/contatos/{id}/consentimentos` | Historico de consentimentos |

### Instrumentos

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/instrumentos` | Listagem global do tenant com filtros |
| GET | `/clientes/{clienteId}/instrumentos` | Listagem por cliente |
| POST | `/instrumentos` | Criar instrumento |
| GET | `/instrumentos/{id}` | Detalhe do instrumento |
| PUT | `/instrumentos/{id}` | Atualizar instrumento |
| DELETE | `/instrumentos/{id}` | Desativar instrumento (soft-delete) |

### Padroes de referencia

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/padroes` | Listagem paginada com filtros e status de vigencia |
| POST | `/padroes` | Criar padrao |
| GET | `/padroes/{id}` | Detalhe do padrao |
| PUT | `/padroes/{id}` | Atualizar padrao |
| DELETE | `/padroes/{id}` | Desativar padrao (soft-delete) |
| GET | `/padroes/{id}/cadeia` | Cadeia de rastreabilidade |
| GET | `/padroes/vencidos` | Padroes com validade vencida |
| GET | `/padroes/proximos-vencimento` | Padroes vencendo em ate 30 dias |

### Alertas de padrao

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/alertas` | Listar alertas pendentes do tenant |
| PATCH | `/alertas/{id}/visto` | Marcar alerta como visto |

### Procedimentos de calibracao

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/procedimentos` | Listagem paginada (versao mais recente por codigo) |
| POST | `/procedimentos` | Criar procedimento em rascunho |
| GET | `/procedimentos/{id}` | Detalhe do procedimento |
| PUT | `/procedimentos/{id}` | Atualizar procedimento em rascunho |
| POST | `/procedimentos/{id}/publicar` | Publicar procedimento (rascunho → vigente) |

### Auditoria

| Metodo | URL | Descricao |
|---|---|---|
| GET | `/auditoria/clientes/{id}` | Historico de alteracoes do cliente |
| GET | `/auditoria/instrumentos/{id}` | Historico de alteracoes do instrumento |
| GET | `/auditoria/padroes/{id}` | Historico de alteracoes do padrao |
| GET | `/auditoria/procedimentos/{id}` | Historico de alteracoes do procedimento |

---

## RBAC resumido

| Role | Clientes | Contatos | Instrumentos | Padroes | Procedimentos | Auditoria |
|---|---|---|---|---|---|---|
| `gerente` / `admin` | CRUD | CRUD | CRUD | CRUD | CRUD + publicar | leitura |
| `administrativo` / `atendente` | CRUD | CRUD | CRUD | leitura | sem acesso | sem acesso |
| `tecnico` | leitura | leitura | leitura | leitura | leitura | sem acesso |
| `visualizador` | leitura | sem acesso | leitura | leitura | sem acesso | sem acesso |

> Nota: os Story Contracts usam `admin` e `atendente`. O sitemap usa `gerente` e `administrativo`. Os contratos abaixo adotam os termos dos Story Contracts (que sao os ACs executaveis).

---

## Convencoes deste epico

- Paginacao padrao: 20 registros por pagina
- Soft-delete: campo `ativo` (boolean); registros com `ativo = false` ficam preservados mas sao omitidos da listagem padrao
- Isolamento de tenant: todas as queries filtradas por `tenant_id` via scope global — cruzamento de tenant retorna 404
- Timestamps: ISO 8601 com timezone (`2026-04-15T06:00:00-03:00`)
- IDs: UUID v4
- Campos em snake_case
- URLs em kebab-case
