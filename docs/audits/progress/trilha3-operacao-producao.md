# Trilha #3 — Operação e Produção (tracker)

**Origem:** `docs/audits/meta-audit-completeness-2026-04-10-action-plan.md §4`
**Status geral:** Estado 1 (3/12) ✅ — 2026-04-10
**Estado 2:** pendente do Bloco 2 fechar (9 itens restantes)
**Relatório de execução:** `docs/reports/execution-meta-audit-2-2026-04-10-session01.md`

## Itens entregues nesta sessão (3 independentes)

- [x] T3.8 `docs/ops/oncall.md` — 2026-04-10 (`ff81ff4`) — política PM solo com cadência mínima diária, escalação, descanso pós-P0, limites honestos.
- [x] T3.11 `docs/ops/customer-support.md` — 2026-04-10 (`e64fa78`) — canal único e-mail alpha, SLA por categoria, 3 templates (recebido/em análise/resolvido), escalação para slice.
- [x] T3.12 `docs/templates/postmortem-prod.md` — 2026-04-10 (`b868d57`) — template com 12 seções obrigatórias, distinto do template de retrospectiva de slice.

## Itens pending-block-2 (9 restantes)

Todos dependem do Bloco 2 ter rodado a escolha da stack para poderem dimensionar operação real.

- [⏸] T3.1 capacity planning inicial
- [⏸] T3.2 deploy playbook (stack-specific)
- [⏸] T3.3 rollback playbook
- [⏸] T3.4 monitoring baseline
- [⏸] T3.5 alerting rules iniciais
- [⏸] T3.6 status page (interna primeiro)
- [⏸] T3.7 backup/restore runbook (depende também de T2.6)
- [⏸] T3.9 onboarding de primeiro tenant
- [⏸] T3.10 soak test antes do primeiro tenant pagante

(Os IDs e descrições exatos são extraídos do plano principal. Esta lista é um snapshot para referência.)
