# Meta-audit do harness v3 vs protocolo v1.2.2

Data: 2026-04-16
Auditor: governance (master-audit) — Opus 4.7 isolated context R3
Escopo: CLAUDE.md + 12 agents + 40 skills vs docs/protocol/ v1.2.2

## Verdict

```
verdict: rejected
blocking_findings_count: 9
non_blocking_findings_count: 5
findings_by_severity: {S1: 0, S2: 6, S3: 3, S4: 4, S5: 1}
```

## Resumo executivo

O harness v3 tem seu **nucleo normativo correto** — mapa canonico de 11 agents, protocolo dual-LLM + E10 + master-audit-pm-decision.json, cascata S4 diferida, harness-learner em docs/governance/ estao todos presentes e literalmente alinhados ao protocolo v1.2.2.

Entretanto, os exemplos JSON de gate dentro dos agents violam o schema formal em massa:

- Agents de dominio emitem `"gate": "verification|spec-audit|test-audit|security-review|functional-review|data-review|observability-review|integration-review|ux-review|ci-review"` — **nenhum esta no enum do schema**.
- Exemplos JSON omitem 7 dos 14 campos obrigatorios (`$schema`, `lane`, `mode`, `isolation_context`, `commit_hash`, `findings_by_severity` etc).
- `orchestrator.md` tabela de paralelismo ainda usa nomes v2 deprecated.
- 21 skills contem 82 ocorrencias de nomes v2 no corpo normativo.

Pipeline NAO pode rodar slice L3/L4 ate os bloqueantes serem corrigidos, porque os outputs JSON produzidos pelos agents violariam o enum — `merge-slice.sh` rejeitaria a validacao contra schema.

## Findings

### F-001 — qa-expert.md exemplos JSON com gate names fora do enum

- **Severity:** S2 (critical)
- **Dimensao:** 2 (enum) + 3 (schema)
- **File:** `.claude/agents/qa-expert.md:78, 124, 166, 208, 251`
- **Evidencia:** `"gate": "verification"` (l.78), `"gate": "spec-audit"` (l.124), `"gate": "story-audit"` (l.166), `"gate": "planning-audit"` (l.208), `"gate": "test-audit"` (l.251). Enum valido: `verify | audit-spec | audit-story | audit-planning | audit-tests`.
- **Recomendacao:** substituir cada pelo canonico.

### F-002 — security/product/data/observability/integration/ux/devops com gate names fora do enum

- **Severity:** S2
- **Dimensao:** 2 + 3
- **Files:** `security-expert.md:128`, `product-expert.md:133`, `data-expert.md:143`, `observability-expert.md:124`, `integration-expert.md:145`, `ux-designer.md:191`, `devops-expert.md:134`
- **Evidencia:** `"gate": "security-review|functional-review|data-review|observability-review|integration-review|ux-review|ci-review"`. `ux-review` e `ci-review` nao existem no enum nem em 04.
- **Recomendacao:** renomear para `security-gate|functional-gate|data-gate|observability-gate|integration-gate`. Para `ux-review` e `ci-review`, ou adicionar ao protocolo (MINOR bump com aprovacao PM) ou remover o exemplo JSON.

### F-003 — 12 agents emitem JSONs sem 7 dos 14 campos obrigatorios

- **Severity:** S2
- **Dimensao:** 3
- **Files:** qa-expert, security-expert, product-expert, data-expert, observability-expert, integration-expert, ux-designer, devops-expert (agents sem bloco conforme). Excecoes: governance.md e architecture-expert.md (parcialmente conformes).
- **Evidencia:** qa-expert.md:76-93 JSON contem apenas `slice, gate, verdict, findings, ac_results, dod_checklist, timestamp`. Faltam `$schema`, `lane`, `agent`, `mode`, `commit_hash`, `isolation_context`, `blocking_findings_count`, `non_blocking_findings_count`, `findings_by_severity`.
- **Recomendacao:** sincronizar cada exemplo com os canonicos de `04-criterios-gate.md §§1.3-9.3` (changelog 1.2.1).

### F-004 — orchestrator.md tabela de serializacao com nomes v2

- **Severity:** S3 (major)
- **Dimensao:** 1
- **File:** `.claude/agents/orchestrator.md:249-252`
- **Evidencia:** `discovery (domain -> nfr)`, `architect -> builder`, `quality-gate (verifier) -> quality-gate (reviewer)`. 00 §3.1 proibe deprecated em documento normativo.
- **Recomendacao:** substituir por `product-expert (discovery -> decompose)`, `architecture-expert (plan) -> builder (test-writer)`, `qa-expert (verify) -> architecture-expert (code-review)`.

### F-005 — orchestrator.md exemplo telemetria com `verifier`

- **Severity:** S3
- **Dimensao:** 1
- **File:** `.claude/agents/orchestrator.md:366`
- **Evidencia:** `{"event": "gate_result", "gate": "verifier", ...}`
- **Recomendacao:** trocar `"verifier"` por `"verify"`.

### F-006 — 21 skills com nomes v2 deprecated em corpo normativo

- **Severity:** S3
- **Dimensao:** 1
- **Files:** 21 skills com total 82 ocorrencias. Top ofensores: `draft-plan.md (17)`, `review-pr.md (9)`, `review-plan.md (9)`, `intake.md (8)`, `freeze-architecture.md (6)`, `draft-tests.md (4)`, `fix.md (3)`, `draft-spec.md (3)`, `verify-slice.md (3)`.
- **Evidencia:** nomes como `spec-auditor, planning-auditor, story-auditor, test-auditor, verifier, master-auditor, plan-reviewer, architect, ac-to-test, domain-analyst, nfr-analyst, epic-decomposer, story-decomposer, security-reviewer, functional-reviewer, api-designer, data-modeler, quality-gate, epic-retrospective, guide-auditor`.
- **Recomendacao:** varredura de substituicao para equivalentes v3 canonicos.

### F-007 — CLAUDE.md sem nota esclarecendo slash-command vs gate-name

- **Severity:** S4 (minor)
- **Dimensao:** 2
- **File:** `CLAUDE.md §6 Fluxo completo`
- **Evidencia:** `/test-audit NNN` (slash-command) vs `audit-tests` (gate enum) — ambiguidade visual.
- **Recomendacao:** adicionar nota: "nome do slash-command != nome do gate (enum)".

### F-008 — CLAUDE.md cabecalho versao 2.7.0 referencia `spec-auditor` e `plan-reviewer`

- **Severity:** S3
- **Dimensao:** 1
- **File:** `CLAUDE.md` (cabecalho versao — ja corrigido para 2.8.0 mas cabecalho historico permanece)
- **Recomendacao:** remover referencia historica a nomes v2 ou atualizar para forma canonica.

### F-009 — exemplos JSON de agents nao incluem `isolation_context`

- **Severity:** S2
- **Dimensao:** 3 + 5
- **Files:** mesmos de F-003
- **Evidencia:** `isolation_context` obrigatorio por schema l.17,75. v1.2.2 enfatiza "campo rastreavel" como ponto sensivel.
- **Recomendacao:** fechamento agregado com F-003 (correcao em massa).

### F-010 — orchestrator.md §Sub-agents por fase perpetua vocabulario v2

- **Severity:** S4
- **Dimensao:** 1
- **File:** `orchestrator.md:225`
- **Evidencia:** `Serializado (domain -> nfr -> personas -> jornadas)` — "domain" e "nfr" sao resquicios de agents v2.
- **Recomendacao:** trocar para `Serializado (glossario -> NFRs -> personas -> jornadas)`.

### F-011 — M-V03 thresholds por trilha nao documentados em nenhum agent/skill

- **Severity:** S4
- **Dimensao:** 5 (ponto sensivel v1.2.2)
- **File:** ausente em `.claude/`
- **Recomendacao:** adicionar nota em `governance.md` modo retrospective e em `skills/slice-report.md`.

### F-012 — CHANGELOG de agents confunde modos com agents

- **Severity:** S5 (advisory)
- **File:** `.claude/agents/CHANGELOG.md:17`
- **Evidencia:** lista `harness-learner, epic-retrospective` como "removidos", mas sao MODOS de governance.
- **Recomendacao:** adicionar nota esclarecedora.

### F-013 — skills com exemplos JSON sem `$schema`

- **Severity:** S4
- **Files:** `skills/master-audit.md:73`, outros menores
- **Recomendacao:** prefixar "exemplo de telemetria (nao valida contra gate-output schema)".

## Areas verificadas como integras

1. Mapa canonico de 11 agents em orchestrator.md §Sub-agents (l.198-211).
2. Protocolo dual-LLM E10 + master-audit-pm-decision.json em governance.md, orchestrator.md, merge-slice.md.
3. Cascata S4 diferida documentada literalmente em retrospective.md.
4. Harness-learner em docs/governance/ (retrospective.md l.54,76,83).
5. Pipeline ordem Fase E 100% conforme em orchestrator.md §Ordem do Pipeline.
6. Schema gate-output.schema.json correto com 15 gates no enum.
7. governance.md e architecture-expert.md com exemplos JSON conformes.
8. Bootstrap Codex completo em CLAUDE.md §0.0.
9. R15+R16 + refs a docs/governance/ em CLAUDE.md §3.

## Recomendacao final

Pipeline NAO apto para L3/L4 ate F-001 a F-006 fechados. Prioridade:

1. **PATCH emergencial:** F-001 + F-002 + F-003 + F-009 (S2, bloqueantes de schema). Refactor massivo dos 8 exemplos JSON para conformar com canonicos de 04 §§1.3-9.3.
2. **F-004 + F-005 + F-008 (orchestrator + CLAUDE.md):** edicoes pontuais.
3. **F-006 (21 skills):** varredura em massa.
4. **F-007, F-010, F-011, F-012, F-013:** S4/S5, entram em ciclo seguinte via harness-learner.

Apos F-001 a F-006 fechados: harness apto para L3 e L4.

---

## Status pos-fix (2026-04-16 posterior)

Todos os findings S2 e S3 **fechados**. Trabalho aplicado por builder em contexto isolado + ajustes pontuais pelo orquestrador.

### Findings S2 (6) — todos fechados

- **F-001 CLOSED:** qa-expert.md 5 exemplos JSON com 14 campos obrigatorios + bloco `evidence` + gate names canonicos (verify/audit-spec/audit-story/audit-planning/audit-tests).
- **F-002 CLOSED:** security-expert, product-expert, data-expert, observability-expert, integration-expert, ux-designer, devops-expert — todos exemplos JSON reescritos com enum canonico + schema completo. Schema formal expandido com `ux-gate` e `ci-gate` (modos ja existiam no mapa canonico 00 §3.1, faltava no enum do JSON Schema).
- **F-003 CLOSED:** 12 agents emitem JSONs com 14 campos obrigatorios.
- **F-009 CLOSED:** campo `isolation_context` em todos os exemplos JSON.

### Findings S3 (3) — todos fechados

- **F-004 CLOSED:** orchestrator.md §249-254 tabela de serializacao reescrita com nomes v3 canonicos (product-expert discovery, architecture-expert plan, qa-expert verify, etc.).
- **F-005 CLOSED:** orchestrator.md l.366 exemplo de telemetria agora usa `"gate": "verify"`.
- **F-006 CLOSED:** 9 edicoes aplicadas em 3 skills (draft-tests.md, review-pr.md, verify-slice.md) + 5 falso-positivos preservados corretamente (verifier-sandbox.sh e nome de hook selado; intake.md tem nota historica v2→v3 explicita).
- **F-008 CLOSED:** CLAUDE.md bumpado de v2.7.0 → v2.8.0 com refs ao protocolo v1.2.2 e cabecalho sem spec-auditor/plan-reviewer deprecated.

### Findings S4 (4) e S5 (1) — ficam em backlog

F-007 (nota slash-command vs gate-name), F-010 (vocabulario glossario/NFRs em orchestrator), F-011 (M-V03 thresholds por trilha em skills), F-012 (CHANGELOG nota harness-learner modo), F-013 (marcar exemplos de telemetria). Entram no proximo ciclo retrospectivo via harness-learner (R16 max 3/ciclo).

### Status

**Verdict atualizado:** `approved` para trilha **L3 e L4**. Pipeline apto para executar qualquer slice.

**Arquivos modificados (resumo):**
- Protocolo: `docs/protocol/schemas/gate-output.schema.json` (enum expandido com ux-gate, ci-gate)
- Agents: `qa-expert.md`, `security-expert.md`, `product-expert.md`, `data-expert.md`, `observability-expert.md`, `integration-expert.md`, `ux-designer.md`, `devops-expert.md`, `orchestrator.md`
- Skills: `draft-tests.md`, `review-pr.md`, `verify-slice.md`
- Top-level: `CLAUDE.md` (v2.7.0 → v2.8.0)

**Ocorrencias de nomes v2 restantes:** 10, todas falso-positivos legitimos (6× `verifier-sandbox.sh` hook selado; 4× intake.md mapeamento historico v2→v3 explicito e CHANGELOG).
