# E17 — UMC e Frota Operacional: Índice de Stories

**Épico:** E17
**Status geral:** backlog
**Complexidade relativa:** alta
**Estimativa total:** 6 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E17-S01 | Cadastro de UMC (placa, chassi, motorista, status, massas-padrão a bordo) | média | 7 | — | backlog |
| E17-S02 | Agenda da UMC: bloqueio por OS + conflito de agendamento | alta | 8 | E17-S01 | backlog |
| E17-S03 | Cadastro de veículo operacional (assinado vs pool) + reserva de pool | média | 7 | — | backlog |
| E17-S04 | Diário de bordo (KM + abastecimento + foto) — offline-first | alta | 9 | E17-S01, E17-S03 | backlog |
| E17-S05 | Alertas de validade (massa-padrão + manutenção) + bloqueio de agenda | média | 6 | E17-S01, E17-S02 | backlog |
| E17-S06 | Relatório de custo de viagem por veículo/período | baixa | 4 | E17-S04 | backlog |

---

## Notas de sequenciamento (R13)

- **E17-S01** e **E17-S03** não dependem entre si — podem rodar em **paralelo** (UMC e veículo operacional são entidades independentes).
- **E17-S02** depende de S01 (precisa da entidade UMC para criar agenda).
- **E17-S04** depende de S01 e S03 (diário é registrado para UMC ou veículo).
- **E17-S05** depende de S01 e S02 (alertas de validade afetam agenda).
- **E17-S06** depende de S04 (relatório consolida dados do diário).

---

## Dependências externas

- E16 merged (sync offline operacional — diário de bordo requer sync de fotos)
- ADR-0016 aceita (tenant_id em todas as entidades)
- E19 referenciado (estoque multinível para massas-padrão a bordo) — integração confirmada após E19 merged
