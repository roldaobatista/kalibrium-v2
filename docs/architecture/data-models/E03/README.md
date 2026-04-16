# Data Models — E03 Cadastro Core

> **Status:** draft
> **Data:** 2026-04-15
> **Épico:** E03 — Cadastro Core
> **Dependência:** E02 completo (tenants, companies, branches, tenant_users, roles, consent_subjects)

---

## Visão geral

O E03 introduz as entidades de domínio operacional que alimentam todos os fluxos de calibração. São cinco agregados principais:

| Tabela | Agregado | Escopo |
|---|---|---|
| `clientes` | Cliente | tenant-scoped |
| `contatos` | Contato (vinculado ao cliente) | tenant-scoped |
| `consentimentos_contato` | Consentimento LGPD por canal | tenant-scoped, append-only |
| `instrumentos` | Instrumento do cliente | tenant-scoped |
| `padroes_referencia` | Padrão de referência do laboratório | tenant-scoped |
| `procedimentos_calibracao` | Procedimento de calibração versionado | tenant-scoped |

Audit log de alterações é gerenciado pela biblioteca `owen-it/laravel-auditing` via tabela `audits` (criada pelo pacote, não por migration manual do E03).

---

## Relações cross-épico

- `clientes.tenant_id` → `tenants.id` (E02)
- `clientes.created_by` / `updated_by` → `tenant_users.id` (E02)
- `contatos.cliente_id` → `clientes.id` (E03)
- `consentimentos_contato.contato_id` → `contatos.id` (E03)
- `instrumentos.cliente_id` → `clientes.id` (E03)
- `padroes_referencia.padrao_anterior_id` → `padroes_referencia.id` (self-reference — cadeia de rastreabilidade)
- `procedimentos_calibracao.tenant_id` → `tenants.id` (E02)

---

## Arquivos deste diretório

| Arquivo | Conteúdo |
|---|---|
| `erd.md` | ERD Mermaid + tabela de colunas com tipos, constraints e índices |
| `migrations.md` | Especificação de migrations Laravel (ordem, colunas, RLS, rollback) |
| `seeds.md` | Seeds de desenvolvimento (2 tenants, dados isolados) |

---

## Notas de design

1. `clientes` usa `documento` (varchar 18) para suportar CPF (14 chars com máscara) e CNPJ (18 chars com máscara). Unicidade por `(tenant_id, documento)`.
2. `padroes_referencia.padrao_anterior_id` é nullable — null indica topo da cadeia (referência primária RBC).
3. Anti-ciclo na cadeia de rastreabilidade é responsabilidade da camada de aplicação (validação no Model antes de salvar).
4. `procedimentos_calibracao` segue máquina de estados: `rascunho → vigente → obsoleto`. Apenas um procedimento por `(tenant_id, nome, dominio_metrologico)` pode estar `vigente` simultaneamente — enforçado por partial unique index.
5. `consentimentos_contato` é append-only (trigger PostgreSQL bloqueia UPDATE/DELETE), seguindo o padrão já estabelecido em `consent_records` (E02).
6. Soft delete (`deleted_at`) em `clientes`, `contatos`, `instrumentos`, `padroes_referencia` e `procedimentos_calibracao` — dados regulatórios nunca são destruídos.
7. `audits` (owen-it) é tabela global sem `tenant_id` direto — isolamento via `auditable_type` + `auditable_id`. Consulta filtrada por `tenant_id` do registro auditável na camada de aplicação.
