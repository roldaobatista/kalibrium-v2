# Retrospectiva slice-011

**Data:** 2026-04-16
**Story:** E02-S08 — Testes estruturais de isolamento entre tenants
**Resultado:** approved (PR #24 merged em 2026-04-16T00:36:43Z)
**Fonte numérica:** [slice-011-report.md](slice-011-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Verificações approved | 9 |
| Verificações rejected | 1 (verifier rodada 2) |
| Rodadas reviewer até approved | 8 (7 rejeições com findings reais) |
| Rodadas security-reviewer | 2 (6 findings: 1 HIGH, 2 MEDIUM, 2 LOW, 1 INFO) |
| Rodadas test-auditor | 2 |
| Rodadas functional-reviewer | 2 |
| Master-auditor (dual-LLM Opus + GPT-5) | approved |
| Suite final | 134 passed + 10 skipped (~14s) |
| Duração total | ~3h (21:30Z → 00:36Z) |

> Telemetria de tokens/commits ficou zerada porque o slice rodou em sub-agents que não emitem `tokens_used` no evento `verify` — débito conhecido (ver §Mudanças propostas).

## O que funcionou

- **Auto-approval do plano** (spec-auditor + plan-reviewer ambos approved → segue automático): PM não foi envolvido até decisões de produto reais. Política de ADR-0012 reduziu fricção sem perder R11.
- **Dual-LLM master-auditor** (Opus 4.6 nativo + GPT-5 via Codex CLI Bash): ambas as trilhas convergiram em `approved`, validando o consenso. Primeira execução completa do gate ADR-0012 E2 num slice MVP.
- **Decomposição em 5 tasks paralelas** (T1-T5) reduziu loops do implementer; cada task focada em 1-2 ACs.
- **Opus reviewer pegou issues reais que Sonnet poderia ter deixado passar**: fail-open no scope global, CI sem services postgres/redis, Log::listen trivial. Rodada 8 vs típico 3-4.
- **Política de modelo (PR #23, commit `7cf1b1a`)** entrou em vigor neste slice — primeiro teste real da reclassificação Opus/Sonnet.

## O que não funcionou

- **Reviewer rodou 8 rodadas (7 rejeições)** — todas com findings reais (ver handoff §2). Não é gate em falso, é sintoma de implementação inicial frouxa em isolamento multi-tenant. Implementer deveria ter antecipado fail-closed, sanitização de tokens em logs, e bypass de scope na resolução.
- **Verifier rejeitou rodada 2** (`2026-04-15T22:50:06Z`, reject_count=1) por 29 testes incomplete; fixer converteu em testes reais. Sintoma: ac-to-test gerou stubs `markTestIncomplete()` que passaram pelo red-check mas não validavam ACs.
- **Telemetria de tokens zerada** — `verify` events não emitem `tokens_used`. Slice-report mostra TOTAL=0 sem refletir o consumo real (~35 sub-agents invocados).
- **Codex CLI Windows** consumiu tempo extra na primeira execução do master-auditor por falta de docs operacionais (resolvido pelo `docs/operations/codex-gpt5-setup.md`).

## Gates que dispararam em falso

- Nenhum identificado. Todas as 7 rejeições do reviewer foram findings reais corrigidos.

## Gates que deveriam ter disparado e não dispararam

- **ac-to-test → red-check**: aceitou 29 testes `markTestIncomplete()` como red válido. Deveria distinguir "red por falha real" vs "red por incomplete". Hook `red-check-strict.sh` ou regra no `ac-to-test` agent.
- **post-edit-gate**: não detectou que `ScopesToCurrentTenant` original era fail-open quando sem tenant. Análise estática de scope global precisa cobrir esse padrão.

## Mudanças propostas

- [ ] **Telemetria de tokens** — eventos `verify`/`review`/`security-review`/`test-audit`/`functional-review` precisam emitir `tokens_used`; atualizar `scripts/record-telemetry.sh` e schemas. (`docs/guide-backlog.md`)
- [ ] **Red-check estrito** — rejeitar `markTestIncomplete()` como red válido em ac-to-test; exigir falha real (assertion/exception). (`docs/guide-backlog.md`)
- [ ] **Análise de fail-open em scope global** — adicionar check em `post-edit-gate` ou `security-reviewer` checklist para detectar `BuilderScope` sem `whereRaw('1=0')` quando contexto ausente. (`docs/guide-backlog.md`)
- [ ] **Slice-report enriquecido** — incluir contagem de rodadas por gate (não só verifier), tempo entre rodadas, taxa de finding-fix. (`docs/guide-backlog.md`)

## Lições para o guia

- **Opus reviewer é significativamente mais rigoroso que Sonnet em multi-tenancy/segurança** — esperar 6-8 rodadas em slices de isolamento/segurança quando reviewer = Opus. Não interpretar como falha do implementer; é sinal de gate funcionando.
- **Auto-approval do plano (spec+plan) está validado em produção** — manter como default; PM só entra em escalação R6 ou decisão de produto.
- **Codex CLI no Windows precisa de doc operacional permanente** — `docs/operations/codex-gpt5-setup.md` agora é leitura obrigatória antes do master-auditor; manter sincronizado.
- **Tasks paralelas (T1-T5) com 1-2 ACs cada** é padrão preferido para slices grandes — registrar em `.claude/skills/draft-plan.md`.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R16 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
