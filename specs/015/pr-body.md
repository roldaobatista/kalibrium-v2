# Slice 015 — Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack

Slice **015** — pronto para aceitação do PM.

## Gates obrigatórios aprovados

- Verifier (mecânico): **approved** → `specs/015/verification.json`
- Reviewer (estrutural): **approved** → `specs/015/review.json`
- Security-reviewer (segurança/LGPD): **approved** → `specs/015/security-review.json`
- Test-auditor (cobertura/qualidade dos testes): **approved** → `specs/015/test-audit.json`
- Functional-reviewer (produto/UX/ACs): **approved** → `specs/015/functional-review.json`

Os gates obrigatórios foram concluídos com verdict approved e sem findings bloqueantes.

## Acceptance Criteria verificados

0
0 AC(s) no spec — todos passaram no verifier. Detalhes mecânicos em `specs/015/verification.json`.

## Para o PM (linguagem de produto, R12)

Este PR entrega o comportamento descrito em `specs/015/spec.md`. Antes de aceitar o merge:

1. Ler `specs/015/spec.md` (contexto + ACs em português).
2. Se houver UI: testar visualmente no ambiente de staging.
3. Aceitar (merge) ou comentar ajustes — o agente aplica na próxima iteração.

## Arquivos alterados

- .claude/agents/CHANGELOG.md
- .claude/agents/ac-to-test.md
- .claude/agents/api-designer.md
- .claude/agents/architect.md
- .claude/agents/architecture-expert.md
- .claude/agents/builder.md
- .claude/agents/data-expert.md
- .claude/agents/data-modeler.md
- .claude/agents/devops-expert.md
- .claude/agents/domain-analyst.md
- .claude/agents/epic-decomposer.md
- .claude/agents/epic-retrospective.md
- .claude/agents/fixer.md
- .claude/agents/functional-reviewer.md
- .claude/agents/governance.md
- .claude/agents/guide-auditor.md
- .claude/agents/harness-learner.md
- .claude/agents/implementer.md
- .claude/agents/integration-expert.md
- .claude/agents/master-auditor.md
- .claude/agents/nfr-analyst.md
- .claude/agents/observability-expert.md
- .claude/agents/orchestrator.md
- .claude/agents/plan-reviewer.md
- .claude/agents/planning-auditor.md
- .claude/agents/product-expert.md
- .claude/agents/qa-expert.md
- .claude/agents/reviewer.md
- .claude/agents/security-expert.md
- .claude/agents/security-reviewer.md
- .claude/agents/spec-auditor.md
- .claude/agents/story-auditor.md
- .claude/agents/story-decomposer.md
- .claude/agents/test-auditor.md
- .claude/agents/ux-designer.md
- .claude/agents/verifier.md
- .claude/skills/_TEMPLATE.md
- .claude/skills/adr.md
- .claude/skills/audit-planning.md
- .claude/skills/audit-spec.md
- .claude/skills/audit-stories.md
- .claude/skills/audit-tests-draft.md
- .claude/skills/checkpoint.md
- .claude/skills/codex-bootstrap.md
- .claude/skills/context-check.md
- .claude/skills/decide-stack.md
- .claude/skills/decompose-epics.md
- .claude/skills/decompose-stories.md
- .claude/skills/draft-plan.md
- .claude/skills/draft-spec.md
- .claude/skills/draft-tests.md
- .claude/skills/explain-harness-change.md
- .claude/skills/explain-slice.md
- .claude/skills/fix.md
- .claude/skills/forbidden-files-scan.md
- .claude/skills/freeze-architecture.md
- .claude/skills/freeze-prd.md
- .claude/skills/functional-review.md
- .claude/skills/guide-check.md
- .claude/skills/intake.md
- .claude/skills/master-audit.md
- .claude/skills/mcp-check.md
- .claude/skills/merge-slice.md
- .claude/skills/new-slice.md
- .claude/skills/next-slice.md
- .claude/skills/project-status.md
- .claude/skills/release-readiness.md
- .claude/skills/resume.md
- .claude/skills/retrospective.md
- .claude/skills/review-plan.md
- .claude/skills/review-pr.md
- .claude/skills/sealed-diff.md
- .claude/skills/security-review.md
- .claude/skills/slice-report.md
- .claude/skills/start-story.md
- .claude/skills/start.md
- .claude/skills/test-audit.md
- .claude/skills/verify-slice.md
- .claude/skills/where-am-i.md
- APLICAR-RELOCK-ADR-0017-0019.bat
- CLAUDE.md
- RELOCK-HARNESS-V1.2.2.bat
- docs/TECHNICAL-DECISIONS.md
- docs/adr/0015-stack-offline-first-mobile.md
- docs/adr/0016-multi-tenant-isolation.md
- docs/adr/0017-auditoria-early-stage.md
- docs/adr/0018-auditoria-fases-iniciais.md
- docs/adr/0019-robustez-loop-gates-harness-learner.md
- docs/agents-elite-profiles.md
- docs/agents-redesign/elite-profiles-batch-2.md
- docs/audits/BRIEF-auditoria-comparativa-externa.md
- docs/audits/comparativa-externa-2026-04-16.md
- docs/audits/comparativa-externa-reaudit-2026-04-16.md
- docs/audits/harness-meta-audit-2026-04-16.md
- docs/audits/project-state-reconcile-2026-04-17T03-37-53Z.json
- docs/audits/protocol-meta-audit-2026-04-16.md
- docs/audits/quality-audit-agents-2026-04-16-v2.md
- docs/audits/quality-audit-agents-2026-04-16-v3.md
- docs/audits/quality-audit-agents-2026-04-16-v4.md
- docs/audits/quality-audit-agents-2026-04-16.md
- docs/audits/quality-audit-skills-2026-04-16-v2.md
- docs/audits/quality-audit-skills-2026-04-16-v3.md
- docs/audits/quality-audit-skills-2026-04-16-v4.md
- docs/audits/quality-audit-skills-2026-04-16-v5.md
- docs/audits/quality-audit-skills-2026-04-16-v6.md
- docs/audits/quality-audit-skills-2026-04-16.md
- docs/audits/remediation-plan-2026-04-16.md
- docs/handoffs/handoff-2026-04-16-1700.md
- docs/handoffs/handoff-2026-04-16-2100.md
- docs/handoffs/handoff-2026-04-16-2200-ampliacao-v3.md
- docs/handoffs/handoff-2026-04-16-2330-harness-5-of-5.md
- docs/handoffs/handoff-2026-04-16-adr-017-018-019.md
- docs/handoffs/latest.md
- docs/incidents/aplicacao-adr-0017-0019-2026-04-17T03-55-01Z.md
- docs/incidents/discovery-gap-offline-2026-04-16.md
- docs/incidents/harness-relock-2026-04-16T21-11-02Z.md
- docs/incidents/harness-relock-2026-04-17T03-55-12Z.md
- docs/incidents/remediation-2026-04-16.md
- docs/operations/harness-relock-2026-04-16.md
- docs/product/PRD-ampliacao-2026-04-16-v2.md
- docs/product/PRD-ampliacao-2026-04-16-v3.md
- docs/product/PRD-ampliacao-2026-04-16.md
- docs/product/PRD.md
- docs/product/domain-model-backup-2026-04-16.md
- docs/product/domain-model.md
- docs/product/glossary-domain-backup-2026-04-16.md
- docs/product/glossary-domain.md
- docs/product/journeys-backup-2026-04-16.md
- docs/product/journeys.md
- docs/product/mvp-scope-backup-2026-04-16.md
- docs/product/mvp-scope.md
- docs/product/personas-backup-2026-04-16.md
- docs/product/personas.md
- docs/product/post-mvp-backlog.md
- docs/product/roadmap-backup-2026-04-16.md
- docs/product/roadmap.md
- docs/proposals/agent-flow-v3.md
- docs/proposals/agent-redesign-v3.md
- docs/proposals/fluxo-completo-visao-geral.md
- docs/protocol/00-protocolo-operacional.md
- docs/protocol/01-sistema-severidade.md
- docs/protocol/02-trilhas-complexidade.md
- docs/protocol/03-contrato-artefatos.md
- docs/protocol/04-criterios-gate.md
- docs/protocol/05-matriz-raci.md
- docs/protocol/06-estrategia-evidencias.md
- docs/protocol/07-politica-excecoes.md
- docs/protocol/08-metricas-processo.md
- docs/protocol/schemas/README.md
- docs/protocol/schemas/gate-output.schema.json
- docs/protocol/schemas/harness-audit-v1.schema.json
- docs/protocol/schemas/release-readiness.schema.json
- docs/retrospectives/remediation-2026-04-16.md
- docs/schemas/project-state.schema.json
- docs/templates/bug-brief.md
- docs/templates/fix-strategy.md
- docs/templates/spec-lite.md
- epics/ROADMAP-backup-2026-04-16.md
- epics/ROADMAP.md
- project-state.json
- scripts/aplicar-relock-adr-0017-0019.sh
- scripts/docs-gate-check.sh
- scripts/hooks/MANIFEST.sha256
- scripts/hooks/pre-commit-gate.sh
- scripts/hooks/session-start.sh
- scripts/hooks/verifier-sandbox.sh
- scripts/merge-slice.sh
- scripts/patches/adr-0017-session-start.patch.sh
- scripts/patches/adr-0019-merge-slice.patch.sh
- scripts/patches/adr-0019-pre-commit-gate.patch.sh
- scripts/reconcile-project-state.sh

---
Gerado por `/merge-slice 015`.
