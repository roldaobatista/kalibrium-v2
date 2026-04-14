---
description: Revisa plan.md com plan-reviewer em contexto limpo antes de aprovacao do PM e antes de gerar testes. Uso: /review-plan NNN.
---

# /review-plan

## Uso
```
/review-plan NNN
```

## Quando invocar
Depois de `/draft-plan NNN` gerar `specs/NNN/plan.md` e antes de apresentar o plano para aprovacao do PM ou rodar `/draft-tests NNN`.

## Pré-condições
- `specs/NNN/spec.md` existe
- `specs/NNN/plan.md` existe e passa `bash scripts/draft-plan.sh NNN --validate`

## O que faz

### Fase 1 — Validação mecânica

```bash
bash scripts/plan-review.sh NNN --check
```

Se falhar, parar e corrigir o plan/spec antes de chamar o revisor.

### Fase 2 — Disparar plan-reviewer

Spawna o sub-agent `plan-reviewer` (`.claude/agents/plan-reviewer.md`) em contexto limpo, sem conversa do architect e sem git history.

Inputs permitidos:
- `specs/NNN/spec.md`
- `specs/NNN/plan.md`
- `docs/constitution.md`
- `docs/TECHNICAL-DECISIONS.md`
- `docs/adr/*.md`
- `docs/product/glossary-domain.md`, se existir

Output:
- `specs/NNN/plan-review.json`

### Fase 3 — Validar aprovação

```bash
bash scripts/plan-review.sh NNN --approved
```

Gate só passa com `verdict: approved`, todos os checks em `pass`, `findings: []` e contadores de findings zerados.

## Handoff
- **Aprovado com findings []** → apresentar recomendação do plano ao PM em linguagem R12.
- **Reprovado ou com qualquer finding** → corrigir TODOS os findings no `plan.md` e re-rodar `/review-plan NNN`.

## Regras
- Não existe "aprovado com ressalva".
- Não pular para `/draft-tests NNN` sem `plan-review.json` aprovado com `findings: []`.
- Até 5 ciclos automáticos de correção; na 6ª falha consecutiva, escalar ao PM em linguagem de produto.
