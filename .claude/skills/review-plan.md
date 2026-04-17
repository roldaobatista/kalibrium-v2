---
description: Revisa plan.md com architecture-expert (plan-review) em contexto limpo. Quando approved, libera fluxo automatico para draft-tests sem aguardar PM (CLAUDE.md §6 Fase D step 17). Uso: /review-plan NNN.
protocol_version: "1.2.4"
---

# /review-plan

## Uso
```
/review-plan NNN
```

## Quando invocar
Depois de `/draft-plan NNN` gerar `specs/NNN/plan.md`. Quando o `architecture-expert` (modo: plan-review) retorna `approved` com findings vazios E o `qa-expert` (modo: audit-spec) ja aprovou (rodada anterior), o orquestrador segue automaticamente para `/draft-tests NNN` sem aguardar aprovacao manual do PM (CLAUDE.md 2.7.0 §6 Fase D step 17).

## Pré-condições
- `specs/NNN/spec.md` existe
- `specs/NNN/plan.md` existe e passa `bash scripts/draft-plan.sh NNN --validate`

## O que faz

### Fase 1 — Validação mecânica

```bash
bash scripts/plan-review.sh NNN --check
```

Se falhar, parar e corrigir o plan/spec antes de chamar o revisor.

### Fase 2 — Disparar architecture-expert (modo: plan-review)

Spawna o sub-agent `architecture-expert` (modo: plan-review) (`.claude/agents/architecture-expert.md`) em contexto limpo, sem conversa do architecture-expert e sem git history.

Inputs permitidos:
- `specs/NNN/spec.md`
- `specs/NNN/plan.md`
- `docs/constitution.md`
- `docs/TECHNICAL-DECISIONS.md`
- `docs/adr/*.md`
- `docs/product/glossary-domain.md`, se existir

Output:
- `specs/NNN/plan-review.json` com `provenance.agent: architecture-expert` e `provenance.context: isolated`

### Fase 3 — Validar aprovação

```bash
bash scripts/plan-review.sh NNN --approved
```

Gate só passa com `provenance` do `architecture-expert` em contexto `isolated`, `verdict: approved`, todos os checks em `pass`, `findings: []` e contadores de findings zerados.

## Handoff
- **Aprovado com findings []** → apresentar recomendação do plano ao PM em linguagem R12.
- **Reprovado ou com qualquer finding** → corrigir TODOS os findings no `plan.md` e re-rodar `/review-plan NNN`.

## Regras
- Não existe "aprovado com ressalva".
- Não pular para `/draft-tests NNN` sem `plan-review.json` aprovado, com proveniencia do `architecture-expert` (modo: plan-review) em contexto `isolated` e `findings: []`.
- Até 5 ciclos automáticos de correção; na 6ª falha consecutiva, escalar ao PM em linguagem de produto.

## Conformidade com protocolo v1.2.4

- **Agent invocado:** `architecture-expert (plan-review)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `plan-review`
- **Output:** `specs/NNN/plan-review.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §13`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico (separado da instancia que rodou architecture-expert (plan))
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`
