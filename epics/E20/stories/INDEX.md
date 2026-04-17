# E20 — CRM Offline do Vendedor: Índice de Stories

**Épico:** E20
**Status geral:** backlog
**Complexidade relativa:** alta
**Estimativa total:** 7 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E20-S01 | Carteira de clientes por vendedor + transferência de carteira (RBAC + offline) | alta | 8 | — | backlog |
| E20-S02 | Ficha completa do cliente offline (500 registros + histórico OS + instrumentos) | alta | 9 | E20-S01 | backlog |
| E20-S03 | Registro de visita (nota de voz + foto + GPS + histórico na ficha) | alta | 8 | E20-S02 | backlog |
| E20-S04 | Criação de orçamento offline + geração de PDF local | alta | 9 | E20-S02 | backlog |
| E20-S05 | Conversão de orçamento aceito em OS (sync → OS rascunho) | média | 6 | E20-S04 | backlog |
| E20-S06 | Follow-up automático (lembrete configurável por dias sem resposta) | média | 5 | E20-S04 | backlog |
| E20-S07 | Pipeline do gerente (visão por vendedor, por fase, filtros) | média | 6 | E20-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E20-S01** é fundação do isolamento de carteira. Pré-requisito de S02 e S07.
- **E20-S02** depende de S01 (ficha usa entidades de carteira). Pré-requisito de S03 e S04.
- **E20-S03** e **E20-S04** dependem de S02 e podem rodar em **paralelo** entre si.
- **E20-S05** e **E20-S06** dependem de S04 e podem rodar em **paralelo** entre si.
- **E20-S07** depende apenas de S01 (pipeline usa dados de carteira + fase do orçamento; pode ser refinado com S04 merged).

---

## Dependências externas

- E16 merged (sync offline de fichas + orçamentos + visitas)
- ADR-0016 aceita (isolamento de carteira por vendedor_id + tenant_id)
- E03 merged (clientes cadastrados — fichas base)
- E04 referenciado (conversão de orçamento em OS — integração em E20-S05)
