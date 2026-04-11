# Trilha #2 — Compliance do Produto (tracker)

**Origem:** `docs/audits/meta-audit-completeness-2026-04-10-action-plan.md §3`
**Status geral:** Estado 1 (13/16) ✅ — 2026-04-10
**Estado 2:** pendente do Bloco 2 fechar (faltam T2.6, T2.7, T2.8)
**Estado DPO:** 5 itens (T2.1-T2.5) em `draft-awaiting-dpo`, conteúdo produzido e revisado internamente, aguardando assinatura externa.
**Relatório de execução:** `docs/reports/execution-meta-audit-2-2026-04-10-session01.md`

## Legenda

- `[x]` = completo (data + commit curto + status)
- `[⏸]` = pending-block-2

## Itens com revisão externa pendente (draft-awaiting-dpo)

Conteúdo entregue e revisado por sub-agente reviewer interno. Assinatura do DPO fracionário (a ser contratado, ver `docs/compliance/procurement-tracker.md`) promove cada item para `ativo — aprovado pelo DPO`.

- [x] T2.1 `docs/security/threat-model.md` — 2026-04-10 (`f3636bf`, draft-awaiting-dpo) — 15 ameaças STRIDE
- [x] T2.2 `docs/security/lgpd-base-legal.md` — 2026-04-10 (`89c0828`, draft-awaiting-dpo) — 9 finalidades mapeadas
- [x] T2.3 `docs/security/dpia.md` — 2026-04-10 (`1a4e3a0`, draft-awaiting-dpo) — Art. 38 LGPD
- [x] T2.4 `docs/security/rot.md` — 2026-04-10 (`6e9efa0`, draft-awaiting-dpo) — 9 entradas (Art. 37 LGPD)
- [x] T2.5 `docs/security/incident-response-playbook.md` — 2026-04-10 (`8bb4173`, draft-awaiting-dpo) — 3 cenários (Art. 48 LGPD)

## Itens independentes (ativos, sem bloqueio externo)

- [x] T2.9 `docs/security/contrato-operador-template.md` — 2026-04-10 (`fdc02c3`, draft-awaiting-advogado-LGPD)
- [x] T2.10 Policies por domínio (5 arquivos em `docs/compliance/`) — 2026-04-10 (`328bd06`) — metrology, fiscal, repp, icp-brasil, lgpd
- [x] T2.11 `docs/compliance/vendor-matrix.md` — 2026-04-10 (`e1d6497`)
- [x] T2.12 `docs/compliance/law-watch.md` — 2026-04-10 (`0a8b92d`)
- [x] T2.13 `docs/compliance/traceability-template.md` — 2026-04-10 (`d049f6e`)
- [x] T2.14 `docs/compliance/procurement-tracker.md` — 2026-04-10 (`1fab9a1`)
- [x] T2.15 `docs/compliance/ia-no-go.md` — 2026-04-10 (`21ead26`)
- [x] T2.16 `docs/compliance/revalidation-calendar.md` — 2026-04-10 (`9506ecf`)

## Itens pending-block-2

- [⏸] T2.6 `docs/security/backup-dr-policy.md` — depende do Bloco 2 (como backup é gerado na stack).
- [⏸] T2.7 `docs/security/secrets-policy.md` — depende do Bloco 2 (qual cofre/gestor).
- [⏸] T2.8 `docs/security/dependency-policy.md` — depende da linguagem escolhida (SBOM/CVE).

## Dependência cruzada

A promoção de T2.1-T2.5 de `draft-awaiting-dpo` para `ativo` é **independente** do Bloco 2 — só depende da contratação do DPO (item do `procurement-tracker.md`). Portanto, a Trilha #2 pode sair de 13/16 para 16/16 em **duas etapas paralelas**: (a) Bloco 2 fecha e libera T2.6/T2.7/T2.8; (b) DPO assina e promove T2.1-T2.5.
