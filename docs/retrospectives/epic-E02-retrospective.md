# Retrospectiva do Épico E02 — Multi-tenancy, Auth e Planos

**Data:** 2026-04-16
**Épico:** E02
**Status declarado:** `merged` (8/8 stories: E02-S01..E02-S08; slices 004-011)
**Auditor:** sub-agent `epic-retrospective` (R15, ADR-0012 E3)
**Iteração:** 1
**Verdict:** **`escalated`** — findings estruturais exigem decisão do PM; não convergem em loop fixer automático.
**Contagem:** 8 findings (2 críticos, 3 majors, 3 minors)

---

## Findings

### ER-001 — CRÍTICO — constitution / P2 / R13
**Local:** `epics/E02/stories/E02-S01..S06.md` + `project-state.json[epics_status.E02.stories]`
**Problema:** Seis stories (S01-S06) foram criadas retroativamente em 2026-04-15, **depois** dos slices 007/008/009 já mergeados. O próprio texto declara "Contrato formal criado em 2026-04-15 para conformidade com R13/R14". Isso viola **P2** (AC executável escrito ANTES do código) e **R13** (sequenciamento) foi retrofitted, não exercido. Stories S01-S06 não têm seção "Acceptance Criteria" formal — apenas "Escopo coberto" em prosa.
**Recomendação:** Registrar lacuna em `docs/retrospectives/epic-E02-gaps.md`; abrir hook bloqueando stories criadas já-`merged` sem evidência de slice pré-existente.

### ER-002 — CRÍTICO — ac_coverage
**Local:** `tests/slice-011/TenantIsolationModelTest.php:129, 144, 166` vs `epics/E02/epic.md:56` ("100% dos ACs de isolamento") e `E02-S08 AC-001`
**Problema:** A suite de isolamento de tenant final tem **3 chamadas `markTestSkipped()` em runtime** que permitem saltar silenciosamente models sensíveis quando fixtures não existirem. AC-001 de E02-S08 declara "100% dos models sensíveis" — um teste que pula não prova isolamento. B-026 reconhece no red-check, mas a suite de PRODUÇÃO ainda usa `markTestSkipped`.
**Recomendação:** Converter `markTestSkipped()` em `fail()` explícito ou assertion de estrutura que quebre se novo model não tiver fixture.

### ER-003 — MAJOR — quality_gate
**Local:** `docs/incidents/ci-blocked-by-actions-quota-2026-04-15.md` + `project-state.json[technical_debt]`
**Problema:** Durante slices 007-011 o GitHub Actions ficou cego (cota de billing esgotada — `steps_count=0` em todos os jobs). CI obrigatório (pré-requisito 1 pós-re-auditoria) **não foi cumprido durante grande parte do E02**. Secret `CI_DB_PASSWORD` (slice-011 SEC-006) também permanece não cadastrado.
**Recomendação:** PM decide entre 4 opções do incidente (público / plano Team / pay-as-you-go / self-hosted); cadastrar `CI_DB_PASSWORD`; re-rodar CI verde em commit representativo de E02 antes de E03.

### ER-004 — MAJOR — constitution / drift sistêmico
**Local:** 10+ incidentes em `docs/incidents/` durante 2026-04-13 a 2026-04-15
**Problema:** E02 acumulou: 3 bypasses admin (bypass-6, bypass-7, pr-14), 2 merges em main sem feature branch (slices 006/008), 4 escalações R6 (slices 006/007/009/009), 1 merge durante estado pausado (slice-010), 1 PM override por findings alucinados (slice-010). **Sinal de drift sistêmico no harness.**
**Recomendação:** Elevar B-023 (guardrail branch != main) a prioridade crítica; acionar harness-learner para hook pre-start-story, hook pre-merge-slice-paused-check, e endurecimento de red-check (B-026).

### ER-005 — MAJOR — ac_coverage
**Local:** `epics/E02/epic.md:19, 33, 34` vs stories E02-S03, E02-S06
**Problema:** 3 ACs funcionais do epic.md sem AC testável correspondente: (a) "2FA **obrigatório** para gerente/administrativo" — enforcement por papel; S03 fala apenas de middleware genérico; (b) "Alertas em 80% e 95% dos limites de entitlement" — S06 menciona no escopo sem AC; (c) "Upgrade/downgrade com pro-rata" — mesmo caso. Não há evidência de teste que quebre se o pro-rata calcular errado ou se o alerta 80% não disparar.
**Recomendação:** Slice de preenchimento (`epic-gap-fill E02`) mapeando AC-a-AC do epic.md contra `tests/` e registrando lacunas.

### ER-006 — MINOR — debt
**Local:** `project-state.json[technical_debt]` vs `docs/guide-backlog.md`
**Problema:** `technical_debt` em project-state lista 3 itens; guide-backlog tem 7 itens **novos** abertos durante E02 (B-022..B-028) + 6 herdados abertos (B-009..B-014).
**Recomendação:** Sincronizar os dois; preferir ponteiro `technical_debt_count` + link.

### ER-007 — MINOR — debt / R8 não auditável
**Local:** `.claude/telemetry/slice-*.jsonl` + reports
**Problema:** Todos os reports mostram `Tokens totais = 0`. **R8 (budget de tokens) não é auditável retroativamente para E02.** Não sabemos se algum sub-agent estourou budget.
**Recomendação:** Priorizar B-025 antes de E03.

### ER-008 — MINOR — plan_divergence
**Local:** `epics/E02/epic.md:43-45` (billing fora de escopo) vs `docs/incidents/pr-14-bypass-p0-billing-governance-2026-04-15.md`
**Problema:** Incidente titula "P0 billing governance" mas E02 declara billing fora de escopo. Precisa confirmar se PR #14 introduziu código de billing real (violaria escopo) ou só governance de Actions (legítimo).
**Recomendação:** Ler PR #14 e esclarecer wording.

---

## Decisão de verdict e loop corretivo

Findings ER-001, ER-003, ER-004, ER-005 **não são corrigíveis por fixer mecânico** — exigem:
- Decisão de PM (opção CI billing);
- Novo slice dedicado (`epic-gap-fill`) para validar AC-a-AC;
- Trabalho de harness-learner com guardrails dentro de E4 (B-023, B-026, B-027).

Convergência em 10 iterações automáticas não é viável. **Verdict: `escalated`.**

## Recomendação ao PM (R12)

Antes de iniciar E03, autorizar 4 passos:

1. Rodar `epic-gap-fill E02` (slice dedicado) que valida AC-a-AC do `epic.md` contra testes reais, converte `markTestSkipped` em assertions explícitas, registra lacunas em `docs/retrospectives/epic-E02-gaps.md`.
2. PM decide opção de CI billing + cadastrar `CI_DB_PASSWORD`, re-rodar CI verde em commit representativo.
3. Invocar **harness-learner** (respeitando E4 — máx 3 mudanças/ciclo): guardrail branch != main (B-023), red-check strict contra `markTestSkipped` (B-026), detector de fail-open em Builder scopes (B-027).
4. Sincronizar `project-state.json[technical_debt]` com `docs/guide-backlog.md`.

Após esses 4 passos: re-rodar `epic-retrospective E02`. Se approved, liberar E03.

---

## Iteração 2 — Re-auditoria (2026-04-16)

**Verdict: `approved`** — todos os 8 findings endereçados (5 resolvidos, 3 aceitos como dívida MVP documentada).

### Verificação finding-a-finding

| Finding | Severidade | Correção | Evidência | Status |
|---|---|---|---|---|
| ER-001 | crítico | 6 Story Contracts reescritos com 46 ACs formais | `epics/E02/stories/E02-S0{1..6}.md` + `docs/retrospectives/epic-E02-story-contracts-retrofit.md` | ✅ resolvido |
| ER-002 | crítico | 3 `markTestSkipped` → assertions explícitas; 144 passed, 0 skipped; B-026 no harness | `tests/slice-011/TenantIsolationModelTest.php` (zero ocorrências de markTestSkipped) + `.claude/agents/ac-to-test.md` §Stubs proibidos | ✅ resolvido |
| ER-003 | major | Repo tornado público (CI ilimitado); secret CI_DB_PASSWORD cadastrada | `gh repo view → PUBLIC`; `gh secret list → CI_DB_PASSWORD 2026-04-16T01:22:17Z` | ✅ resolvido |
| ER-004 | major | B-023 guardrail em start-story + new-slice (bloqueia branch==main) | `.claude/skills/start-story.md:23-25` + `.claude/skills/new-slice.md:20` | ✅ resolvido (prevenção; incidentes históricos reconhecidos) |
| ER-005 | major | (a) 2FA por papel: falso positivo — teste existe em `AuthLoginTest.php:70`; (b) alertas 80/95%: falso positivo — testes em `PlansPageTest.php:258,284`; (c) pro-rata: gap real, documentado | `project-state.json[technical_debt]` → GAP-S06-001; `epic-E02-story-contracts-retrofit.md` | ✅ 2 falsos positivos refutados + 1 gap aceito como dívida MVP |
| ER-006 | minor | `project-state.json[technical_debt]` sincronizado (5 itens + referência ao guide-backlog) | `project-state.json:264-271` | ✅ resolvido |
| ER-007 | minor | B-025 aberto em guide-backlog (prioridade média, não-bloqueante) | `docs/guide-backlog.md` §B-025 | ✅ aceito como dívida (infraestrutura, não produto) |
| ER-008 | minor | Título do incidente clarificado para "harness governance, NÃO billing de produto" | `docs/incidents/pr-14-bypass-p0-billing-governance-2026-04-15.md:1` | ✅ resolvido |

### Harness-learner executado (R16)

3 mudanças aplicadas dentro do limite R16 (máx 3/ciclo):
- B-023: guardrail branch != main em `/start-story` e `/new-slice`
- B-026: regra anti-markTestIncomplete/markTestSkipped em `ac-to-test`
- B-027: checklist fail-open em GlobalScope/BuilderScope no `security-reviewer`

Relatório: `docs/retrospectives/harness-learner-E02.md`.

### Dívida aceita para MVP (não bloqueia E03)

| Item | Severidade | Justificativa |
|---|---|---|
| GAP-S05-001: automação trial→expired | média | MVP com tenants gerenciados manualmente; campo `trial_ends_at` existe, falta scheduler |
| GAP-S06-001: pro-rata upgrade/downgrade | alta | Billing real é pós-MVP (ADR-0004 / `epics/E02/epic.md:44`); pro-rata depende de gateway |
| B-025: telemetria de tokens zerada | média | Infraestrutura de observabilidade, não produto; resolver antes de E03 é desejável mas não bloqueante |

### Gate liberado

**E02 está aprovado.** O épico E03 pode ser iniciado respeitando R14 (sequenciamento inter-épico).

Próxima ação recomendada: `/decompose-stories E03` ou `/next-slice`.
