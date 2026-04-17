# E16 — Sync Engine + Merge por Campo + Conflito: Índice de Stories

**Épico:** E16
**Status geral:** backlog
**Complexidade relativa:** muito alta (protocolo de sync offline-first, fundacional para E17-E20)
**Estimativa total:** 8 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E16-S01 | Spike: Comparativo PowerSync vs ElectricSQL vs custom + ADR-0017 | média | 5 | — | backlog |
| E16-S02 | Fila de operações locais + UUID v7 offline + SyncQueue/SyncCheckpoint | alta | 8 | E16-S01 | backlog |
| E16-S03 | Protocolo delta-sync: upload de delta + reconciliação server-side | alta | 9 | E16-S02 | backlog |
| E16-S04 | Merge por campo (last-write-wins por campo + metadata edited_by/at/device) | alta | 8 | E16-S03 | backlog |
| E16-S05 | Detecção de conflito real + tela de resolução manual | alta | 10 | E16-S04 | backlog |
| E16-S06 | Sync em tempo real online via WebSocket (Laravel Echo + Reverb) | alta | 7 | E16-S03 | backlog |
| E16-S07 | Audit log de sync (append-only, 90 dias de retenção) | média | 6 | E16-S03 | backlog |
| E16-S08 | Modo avião forçado + indicador de status de conexão por membro da OS | baixa | 4 | E16-S02 | backlog |

---

## Notas de sequenciamento (R13)

- **E16-S01** é pré-requisito de todas (spike de tecnologia + ADR-0017). Sem ADR-0017 aceita, a implementação do protocolo é suposição.
- **E16-S02** depende de S01 (ADR define tecnologia de fila). Pré-requisito de S03, S04, S05, S08.
- **E16-S03** depende de S02. Pré-requisito de S04, S05, S06, S07.
- **E16-S04** depende de S03 (protocolo base estabelecido).
- **E16-S05** depende de S04 (precisa do merge para detectar desvio dele).
- **E16-S06** e **E16-S07** dependem de S03 e podem rodar em **paralelo** entre si.
- **E16-S08** depende apenas de S02 e pode rodar em **paralelo** com S03+.

---

## Dependências externas

- E15 merged (banco local SQLite operacional)
- ADR-0016 aceita (isolamento multi-tenant no sync)
- Spike INF-007 (E15-S01) concluído
