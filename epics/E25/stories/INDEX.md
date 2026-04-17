# E25 — Reforma Tributária 2026 (IBS/CBS/cIndOp): Índice de Stories

**Épico:** E25
**Status geral:** backlog
**Complexidade relativa:** alta (fiscal + prazo fixo + regulamentação em evolução)
**Estimativa total:** 5 stories

**PRAZO FIXO: operacional antes de 2026-01-01. Este épico tem prioridade temporal sobre E24.**

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E25-S01 | Configuração tributária RTC 2026 por tenant (NBS, CNAE, alíquotas IBS/CBS) | média | 6 | — | backlog |
| E25-S02 | Cálculo de IBS e CBS com half-even rounding + memória de cálculo | alta | 8 | E25-S01 | backlog |
| E25-S03 | Campos RTC na NFS-e (ABRASF 3.0+) + validação local pré-transmissão | alta | 9 | E25-S02 | backlog |
| E25-S04 | Exibição no PDF + tela de emissão + relatório fiscal breakdown RTC | média | 6 | E25-S02 | backlog |
| E25-S05 | cIndOp: campo reservado + implementação condicionada à regulamentação | baixa | 3 | E25-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E25-S01** é fundação (configuração de alíquotas). Pré-requisito de S02 e S05.
- **E25-S02** depende de S01. Pré-requisito de S03 e S04.
- **E25-S03** e **E25-S04** dependem de S02 e podem rodar em **paralelo** entre si.
- **E25-S05** depende apenas de S01 e pode rodar em **paralelo** com S02/S03/S04.
- Prioridade temporal: S01 → S02 → S03 (caminho crítico para compliance 2026-01-01). S04 e S05 podem ser entregues em paralelo ou logo após.

---

## Dependências externas

- E07 merged (NFS-e base — RTC é extensão do ciclo existente)
- ADR-0016 aceita (ConfiguracaoTributariaRTC tenant-scoped)
- E21 referenciado (retransmissão de rejeição — novo vetor de rejeição com campos RTC)
- Regulamentação RFB IBS/CBS publicada (LC 214/2025 base + portarias finais esperadas para 2H/2025)
