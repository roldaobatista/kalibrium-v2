# Micro-ajustes aos blocos 2-7 (tracker)

**Origem:** `docs/audits/meta-audit-completeness-2026-04-10-action-plan.md §6`
**Status geral:** 5/22 entregues nesta sessão (os itens que não dependiam do Bloco 2 e não exigiam relock).

## Entregues

### Bloco 6

- [x] 6.3 `docs/governance/raci.md` — 2026-04-10 (`19218a1`)
- [x] 6.4 `docs/governance/harness-evolution.md` — 2026-04-10 (`76735d1`)
- [x] 6.9 Templates faltantes em `docs/templates/` (prd, threat-model, runbook, rfp — postmortem-prod coberto por T3.12) — 2026-04-10 (`4bd32d5`)
- [x] 6.10 Consolidar prompts em `docs/audits/prompts/` — 2026-04-10 (`19218a1` rename absorvido + `c329253` 2 prompts + README)
- [x] 6.13 `docs/operations/anthropic-outage-playbook.md` — 2026-04-10 (`e30edad`)

## Pendentes — dependem do Bloco 2

- [ ] 2.1-2.7 itens internos do próprio Bloco 2 (ADR-0001, block-project-init hardening, gate ADR-0001 no session-start, stack.json, stress test, parecer advisor, ADRs 0003-0006)
- [ ] 3.1-3.5 gates reais de execução de teste
- [ ] 4.1-4.8 tradutor PM + pausa dura (alguns dependem de 1.5.6 pronto — glossário — que já está, mas hook exige relock)

## Pendentes — dependem de relock manual

- [ ] 3.3 `post-edit-gate.sh` obrigatório por arquivo — exige relock
- [ ] 3.4 `/spec-review` sub-agent — criação de sub-agent em `.claude/agents/` exige relock
- [ ] 4.2 `check-r12-vocabulary.sh` hook novo — exige relock
- [ ] 4.5 pausa dura categorias críticas — exige relock

## Pendentes — independentes mas fora do escopo desta sessão

- [ ] 4.8 `r6-r7-policy.md` (independente — pode rodar em próxima sessão sem relock)
- [ ] 6.5 `cooldown-policy.md` (independente)
- [ ] 6.6 `fixtures-policy.md` (depende Bloco 2 para linguagem)
- [ ] 6.7 `/project-status` skill (depende de 1.5.11, que é pending-block-2)
- [ ] 6.8 `harness-limitations.md` 2 novas seções (independente — foi adicionada só a de admin bypass)

## Pendentes — bloco 5 (só faz sentido após Bloco 3-4)

- [ ] 5.1-5.5 CI + GitHub App + ruleset endurecido + admin bypass só em hotfix

## Pendentes — bloco 7 (re-auditoria)

- [ ] 7.1-7.5 re-auditoria, smoke test, go/no-go, gate produto pronto, re-auditoria meta-audit #2
