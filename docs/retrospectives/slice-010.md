# Retrospectiva slice-010

**Data:** 2026-04-15
**Slice:** 010 — E02-S07 Base legal LGPD e consentimentos de contato
**PR:** [#15](https://github.com/roldaobatista/kalibrium-v2/pull/15) mergeado em 2026-04-15T15:24:24Z
**Commit final:** `81643ed`
**Resultado:** approved (com observação operacional sobre o momento do merge)
**Fonte numérica:** [slice-010-report.md](slice-010-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits | 4 |
| Verificações approved | 1 |
| Verificações rejected | 0 |
| Tokens totais | 0 (telemetria não registrou — ver §O que não funcionou) |

## O que funcionou

- **5 gates independentes aprovaram:** verifier, reviewer, security-reviewer, test-auditor e functional-reviewer todos com `verdict: approved` e `findings: []` após ciclo F.1 (fluxo especial de correção). Evidência: `specs/010/verification.json`, `review.json`, `security-review.json`, `test-audit.json`, `functional-review.json`.
- **Conformidade LGPD real entregue:** modelo de consentimento, endpoints de gestão, soft-delete com retenção, trilha de auditoria. Slice E02-S07 cumpre ACs da story contract.
- **Auto-merge funcionou após billing voltar:** PR #15 estava armado com `gh pr merge --auto` desde a fase de revisão; quando a cota do Actions foi restaurada mid-session, o merge aconteceu sem intervenção.

## O que não funcionou

- **Merge aconteceu durante estado pausado operacionalmente.** O incident `pr-14-bypass-p0-billing-governance-2026-04-15.md §Plano de saída` havia declarado "❌ Não fechar slices em andamento (slice-010 aguarda auditoria)". O auto-merge estava armado antes dessa declaração e disparou quando o CI destravou. Não usou admin bypass — usou fluxo padrão. Mas violou a intenção da pausa. Incident dedicado: `docs/incidents/slice-010-merge-during-paused-state-2026-04-15.md`.
- **Telemetria vazia (0 tokens registrados).** O script `slice-report.sh` leu `.claude/telemetry/slice-010.jsonl` mas não encontrou eventos de tokens (só 1 approved + 4 commits). Hipóteses: o arquivo foi gerado antes da instrumentação completa estar em vigor, ou o sub-agent `functional-reviewer` (F.1) não registra em jsonl. Perda de observabilidade retrospectiva — não conseguimos dimensionar custo real do slice.
- **Findings fantasmagóricos do reviewer durante o ciclo F.1.** Arquivo `specs/010/pm-override-findings.md` foi considerado porque o reviewer levantou questões que depois se provaram inválidas ao reler o código. Override foi considerado mas não usado. Expõe risco do reviewer em contexto isolado ser cético demais em slices de domínio jurídico (LGPD) onde vocabulário e padrões não batem com código genérico.

## Gates que dispararam em falso

- **Reviewer (no ciclo F.1) levantou findings fantasmagóricos** sobre tratamento de consentimento que, ao ser revisado pelo PM, resultaram inválidos. Evidência: `docs/incidents/slice-010-pm-override-2026-04-15.md`. **Ação sugerida:** adicionar ao contexto do reviewer uma nota de referência ao glossary de domínio LGPD quando o slice for de compliance. Entra no backlog.

## Gates que deveriam ter disparado e não dispararam

- **Não existia gate mecânico para "projeto pausado"** no momento do merge. Auto-merge pré-armado venceu a pausa declarativa em markdown. Este é o ponto que a re-auditoria dual-LLM pós-5/5 corrigiu criando:
  - `scripts/check-paused-state.sh` (PR #16)
  - `.github/workflows/pause-enforcement.yml` (PR #16)
  - `project-state.json.paused` legível por script (PR #16)

  A partir de agora, um auto-merge armado durante pausa seria sinalizado pelo workflow (status check informativo). Quando a cota do Actions permitir `required_status_checks`, passa a ser bloqueio real.

## Mudanças propostas

- [ ] **Glossary de domínio por épico carregado no contexto de reviewer/security-reviewer** quando o slice for de compliance (LGPD, segurança, auditoria). Evita findings fantasmagóricos vindos de ignorância de vocabulário regulatório. Ticket em `docs/guide-backlog.md`.
- [ ] **Instrumentação de tokens garantida no `functional-reviewer` e demais gates F.1** — registrar `event: "tokens_used"` em todos os sub-agents, incluindo ciclos especiais. Ticket em `docs/guide-backlog.md`.
- [x] **Enforcement mecânico da pausa** — já implementado no PR #16 após a re-auditoria. Fecha o gap que este slice expôs.
- [x] **Categoria "bypass técnico autorizado"** — já formalizada na ADR-0014 após a re-auditoria. Evita reabrir debates ad-hoc.

## Lições para o guia

- **Auto-merge precisa ser desativado quando projeto entra em pausa.** Hoje ainda não há cancelamento automático de auto-merges pendentes ao setar `project-state.json.paused = true`. Nova entrada em `docs/guide-backlog.md`: "quando `/pause` for implementado, cancelar todos os auto-merges armados".
- **Slices de compliance (LGPD, segurança, auditoria) merecem seus próprios templates de reviewer** com contexto regulatório injetado. Generic reviewer gera ruído.
- **Telemetria opcional é telemetria perdida.** Instrumentar tokens deve ser obrigatório em todos os sub-agents, não "quando der tempo".
- **Pausa declarativa é folclore; pausa mecânica é governança.** Esta lição custou um slice fechando durante pausa + 2 bypasses técnicos de preparação de auditoria. Já está corrigida pela ADR-0014 + PR #16.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R16 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
