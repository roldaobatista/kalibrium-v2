# E18 — Caixa de Despesa por OS: Índice de Stories

**Épico:** E18
**Status geral:** backlog
**Complexidade relativa:** alta
**Estimativa total:** 7 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E18-S01 | Registro de despesa offline (foto + valor + tipo + OS + origem) | alta | 9 | — | backlog |
| E18-S02 | Saldo otimista por OS e por usuário em tempo real | média | 5 | E18-S01 | backlog |
| E18-S03 | Fila de triagem: aprovar / rejeitar / reclassificar | média | 7 | E18-S01 | backlog |
| E18-S04 | Aprovação em alçada (gerente para valores acima do limiar) | média | 6 | E18-S03 | backlog |
| E18-S05 | Reembolso por PIX em lote (seleção + confirmação + registro) | média | 6 | E18-S04 | backlog |
| E18-S06 | Conciliação com fatura do cartão corporativo (CSV + matching automático) | alta | 8 | E18-S03 | backlog |
| E18-S07 | Relatório de custo real por OS (por categoria, origem, usuário) | baixa | 4 | E18-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E18-S01** é fundação — todas as demais dependem desta.
- **E18-S02** pode rodar em **paralelo** com S03 após S01.
- **E18-S03** habilita S04 e S06. S04 e S06 podem rodar em **paralelo** entre si após S03.
- **E18-S05** depende de S04 (reembolso só processa despesas aprovadas).
- **E18-S07** depende apenas de S01 (consolida dados brutos de despesa).

---

## Dependências externas

- E16 merged (sync offline + upload de fotos via presigned URL)
- ADR-0016 aceita (tenant_id em despesas)
- E21 referenciado (push notification ao rejeitar despesa — não bloqueia, melhora UX)
