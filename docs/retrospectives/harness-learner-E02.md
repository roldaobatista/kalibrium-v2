# Harness Learner — Epic E02

**Data:** 2026-04-15
**Ciclo:** ADR-0012 E4 / R16
**Mudancas auto-aplicadas:** 3 de 3 (limite R16 atingido)
**Mudancas escaladas ao PM:** 0

---

## Resumo executivo

O epic-retrospective E02 identificou 8 findings. O orquestrador resolveu manualmente a maioria (story contracts, markTestSkipped em producao, CI billing, technical_debt). Restaram 3 melhorias estruturais ao harness que o harness-learner aplicou dentro dos guardrails R16.

As 3 mudancas endurecem o harness para prevenir recorrencia dos findings mais impactantes de E02: slices em main sem feature branch, stubs incomplete aceitos como red, e scopes fail-open nao detectados pelo security-reviewer.

---

## Mudanca 1: B-023 — Guardrail branch != main

**Finding motivador:** ER-004 (MAJOR) — slices 006/008 executados em main sem feature branch.
**Diagnostico:** `/start-story` e `/new-slice` nao verificavam a branch atual. O `merge-slice.sh` bloqueia em main, mas tarde demais.
**Mudanca aplicada:**
- `.claude/skills/start-story.md` — novo item 3 nas pre-condicoes: verifica `git branch --show-current`, bloqueia se `main`.
- `.claude/skills/new-slice.md` — novo item 2 no fluxo: mesma verificacao.
- Bypass documentado via `KALIB_SKIP_BRANCH_CHECK` com registro de incidente.

**Guardrail R16:** auto-aplica (adiciona check em skill existente, fortalece sem relaxar).
**Prevencao esperada:** nenhum slice de produto inicia em main; drift detectado no inicio, nao no merge.

---

## Mudanca 2: B-026 — Red-check estrito (markTestIncomplete/markTestSkipped)

**Finding motivador:** ER-002 (CRITICO) — ac-to-test gerou stubs markTestIncomplete que passaram pelo red-check.
**Diagnostico:** ac-to-test.md nao proibia explicitamente markTestIncomplete/markTestSkipped. O hook red-check.sh e selado e nao detecta esses metodos como "nao-red".
**Mudanca aplicada:**
- `.claude/agents/ac-to-test.md` — nova subsecao "Stubs proibidos como red (B-026)" com:
  - Lista explicita de metodos proibidos (markTestIncomplete, markTestSkipped, variantes estaticas).
  - Definicao de "red valido": falha por assertion contra comportamento ausente ou exception por classe/metodo inexistente.
  - Exemplos concretos de red valido.

**Guardrail R16:** auto-aplica (adiciona check em agent existente, fortalece sem relaxar).
**Prevencao esperada:** ac-to-test nunca gera stubs incomplete; se gerar, red-check rejeita (melhoria futura no hook selado requer relock).
**Nota:** melhoria complementar em `scripts/red-check.sh` (deteccao mecanica) requer relock pelo PM — documentada como recomendacao pendente.

---

## Mudanca 3: B-027 — Fail-open em scopes globais (security-reviewer)

**Finding motivador:** ER-004 (MAJOR) — ScopesToCurrentTenant retornava query sem filtro quando TenantContext era null.
**Diagnostico:** security-reviewer.md nao tinha checklist item para fail-open em scopes. O reviewer generico pegou, mas deveria ter sido capturado pelo gate de seguranca especializado.
**Mudanca aplicada:**
- `.claude/agents/security-reviewer.md` — nova secao "Fail-Open em Scopes Globais / Builder Scopes (B-027)" no checklist com:
  - Instrucao para verificar todo GlobalScope, BuilderScope, local scope.
  - Pergunta-chave: "o que acontece quando contexto e null?"
  - Classificacao como finding critical se retorna sem clausula restritiva.
  - Cobertura especifica para multi-tenancy e jobs/workers sem contexto.

**Guardrail R16:** auto-aplica (adiciona check em agent existente, fortalece sem relaxar).
**Prevencao esperada:** fail-open em scopes capturado no gate de seguranca (nao na retrospectiva).

---

## Backlog atualizado

Os 3 itens foram marcados como resolvidos em `docs/guide-backlog.md`:
- [B-023] — RESOLVIDO 2026-04-15
- [B-026] — RESOLVIDO 2026-04-15
- [B-027] — RESOLVIDO 2026-04-15

---

## Recomendacoes pendentes (nao auto-aplicaveis)

### Red-check mecanico (complemento B-026)
- `scripts/red-check.sh` deveria fazer `grep -E 'markTestIncomplete|markTestSkipped'` nos testes gerados e rejeitar.
- Requer relock pelo PM (arquivo selado em `scripts/hooks/`).
- Prioridade: media (agent-level enforcement ja cobre; hook mecanico seria defense-in-depth).

### Sequencing-check em script (complemento B-023)
- `scripts/new-slice.sh` e `scripts/start-story.sh` poderiam verificar branch mecanicamente.
- Requer relock pelo PM (arquivos selados).
- Prioridade: media (skill-level enforcement ja cobre).

---

## Conformidade R16

| Criterio | Status |
|---|---|
| Maximo 3 mudancas por ciclo | 3/3 — conforme |
| Nenhuma regra revogada | Conforme |
| Nenhuma regra afrouxada | Conforme |
| P1-P9 / R1-R14 inalterados | Conforme |
| Constitution nao alterada | Conforme |
| Arquivos selados nao tocados | Conforme |
| Evidencia por finding | Conforme (ER-004, ER-002) |
