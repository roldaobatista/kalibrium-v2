# E22 — SPC e Drift de Qualidade Metrológica ISO 17025: Índice de Stories

**Épico:** E22
**Status geral:** backlog
**Complexidade relativa:** alta (algoritmos metrológicos + domínio ISO 17025)
**Estimativa total:** 5 stories

---

## Stories

| ID | Título | Complexidade | ACs (est.) | Depende de | Status |
|---|---|---|---|---|---|
| E22-S01 | Configuração de gráfico de controle por padrão (UCL/LCL/CL + regras de Nelson) | alta | 8 | — | backlog |
| E22-S02 | Alimentação automática do SPC pelas calibrações do E05 (event listener) | média | 6 | E22-S01 | backlog |
| E22-S03 | Detecção de drift automática + alerta (5 leituras consecutivas, regra Nelson #2) | alta | 8 | E22-S02 | backlog |
| E22-S04 | Bloqueio de padrão suspenso em OS + estado `suspenso_drift` | média | 6 | E22-S03 | backlog |
| E22-S05 | Painel de qualidade consolidado (todos os padrões + filtros + exportação PDF) | alta | 7 | E22-S01 | backlog |

---

## Notas de sequenciamento (R13)

- **E22-S01** é fundação — configura a estrutura de dados do gráfico. Pré-requisito de S02 e S05.
- **E22-S02** depende de S01 (precisa de `ControlChartConfig` criada). Pré-requisito de S03.
- **E22-S03** depende de S02 (detecção usa dados alimentados automaticamente).
- **E22-S04** depende de S03 (bloqueio ativado apenas quando drift confirmado).
- **E22-S05** depende de S01 e pode rodar em **paralelo** com S02/S03/S04 após S01 (painel exibe o que existir, mesmo que detecção de drift ainda não esteja pronta).

---

## Dependências externas

- E05 merged (calibrações de padrões de referência — fonte de dados do SPC)
- ADR-0016 aceita (tenant_id em ControlChartConfig e DriftAlert)
