# E19 — Estoque Multinível: Índice de Stories

**Épico:** E19
**Status geral:** backlog
**Complexidade relativa:** alta
**Estimativa total:** 5 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E19-S01 | Cadastro de itens de estoque e quatro locais (CRUD + saldo por local) | média | 7 | — | backlog |
| E19-S02 | Movimentação entre locais (transferência + rastreabilidade + offline) | alta | 9 | E19-S01 | backlog |
| E19-S03 | Consulta offline do estoque local no dispositivo do técnico | alta | 8 | E19-S01, E19-S02 | backlog |
| E19-S04 | Alerta de padrão vencendo (30 dias) + bloqueio de uso em OS | alta | 7 | E19-S01 | backlog |
| E19-S05 | Relatório de posição de estoque por local e por item | baixa | 4 | E19-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E19-S01** é fundação — todas as demais dependem desta.
- **E19-S02** e **E19-S04** dependem de S01 e podem rodar em **paralelo** entre si.
- **E19-S03** depende de S01 e S02 (consulta offline precisa das movimentações para refletir saldo correto).
- **E19-S05** depende apenas de S01 (consolida dados básicos de saldo; pode rodar após S02 se quiser dados de movimentação).

---

## Dependências externas

- E16 merged (sync de movimentações offline)
- ADR-0016 aceita (tenant_id obrigatório)
- E17 referenciado (local `umc` — integração confirma entidades após E17 merged)
