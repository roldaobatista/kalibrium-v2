---
description: Dispara o sub-agent architecture-expert (modo: plan) para gerar plan.md a partir de spec.md auditado e aprovado. Depois exige /review-plan antes de qualquer aprovação do PM ou testes. Uso: /draft-plan NNN.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005R"
---

# /draft-plan

## Uso
```
/draft-plan NNN
```

## Por que existe
Sem esta skill, o PM precisa saber que "agora é hora de chamar o architecture-expert" e o agente principal precisa lembrar de fazer isso. Se a sessão acabar entre o spec e o plan, o contexto se perde. `/draft-plan` é o handoff explícito que sobrevive ao fim de sessão.

**Resolve G-05 da auditoria de operabilidade PM 2026-04-12.**

## Quando invocar
Depois que `specs/NNN/spec.md` foi preenchido, auditado por `/audit-spec NNN`, aprovado pelo PM e **antes** de gerar testes.

## Pré-condições
- `specs/NNN/spec.md` existe e passa validação (`draft-spec.sh --check`)
- `specs/NNN/spec-audit.json` existe e está `approved` com `findings: []`
- `specs/NNN/plan.md` ainda não existe ou está em `draft`
- `docs/constitution.md` e `docs/TECHNICAL-DECISIONS.md` acessíveis

## O que faz

### Fase 1 — Validação mecânica

```bash
bash scripts/draft-plan.sh NNN --check
```

Se falhar, mostra ao PM o que falta em linguagem R12 e para.

### Fase 2 — Disparar architecture-expert

Spawna o sub-agent `architecture-expert` (modo: plan) (`.claude/agents/architecture-expert.md`) com:
- `subagent_type: "architecture-expert"`
- Prompt contendo o NNN do slice
- O architecture-expert lê spec.md, constitution, ADRs, e gera `specs/NNN/plan.md`

### Fase 3 — Validar output

```bash
bash scripts/draft-plan.sh NNN --validate
```

Se falhar, reporta ao agente principal que re-instrui o architecture-expert.

### Fase 4 — Revisar plan em contexto limpo

Antes de apresentar o plano ao PM, rodar:

```bash
bash scripts/plan-review.sh NNN --check
```

Spawna o sub-agent `architecture-expert` (modo: plan-review) em contexto limpo para gerar `specs/NNN/plan-review.json`.

Depois validar:

```bash
bash scripts/plan-review.sh NNN --approved
```

Se houver qualquer finding, corrigir TODOS no `plan.md` e re-rodar `/review-plan NNN`. Não existe "aprovado com ressalva".

### Fase 5 — Apresentar ao PM (R12)

Traduz o plan.md em linguagem de produto. Exemplo:

```
O plano técnico do slice NNN está pronto.

Resumo do que foi decidido:
- Vai usar [tecnologia X] para [funcionalidade Y] porque [razão simples]
- A tela vai ter [N campos/ações]
- Estimativa: [pequeno/médio/grande]

Riscos identificados:
- [risco em linguagem simples] → já tem solução planejada

Próximo passo:
[ ] Aceito o plano — gerar testes (/draft-tests NNN)
[ ] Quero ajustar — diga o que mudar
```

**NUNCA** mostrar o plan.md cru, nomes de arquivo, código, ou jargão técnico ao PM.

## Handoff
- **plan-review aprovado com proveniencia do `architecture-expert` (modo: plan-review) e `findings: []` + PM aceita** → marcar status do plan.md como `approved` e sugerir `/draft-tests NNN`
- **PM pede ajuste** → re-disparar architecture-expert com instruções adicionais
- **PM quer pausar** → registrar estado e encerrar sem bloquear

## Agentes
- `architecture-expert` — gera `specs/NNN/plan.md` a partir de spec.md, constitution e ADRs
- `architecture-expert` (modo: plan-review) — revisa `specs/NNN/plan.md` em contexto limpo antes do PM

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `specs/NNN/spec.md` não passa validação (`draft-spec.sh --check`) | Abortar e sugerir `/draft-spec NNN` para corrigir o spec antes de gerar o plan. |
| `specs/NNN/spec-audit.json` ausente ou reprovado | Abortar e rodar `/audit-spec NNN`; se houver findings, corrigir spec e reauditar. |
| `architecture-expert` gera plan.md que falha na validação (`draft-plan.sh --validate`) | Re-instruir o architecture-expert com o motivo da falha. Fazer até 5 ciclos automáticos; na 6ª falha consecutiva, escalar humano (R6). |
| `architecture-expert` (modo: plan-review) rejeita ou emite qualquer finding | Corrigir TODOS os findings no plan e re-rodar `/review-plan NNN`; não apresentar ao PM como aprovado. |
| `architecture-expert` inventa requisitos que não estão no spec | Rejeitar o plan, re-spawnar architecture-expert com instrução explícita de manter escopo do spec. |
| PM não entende o resumo R12 do plan | Reformular com analogias mais simples. Oferecer "quer que eu explique de outro jeito?" antes de prosseguir. |

## Regras
- Não inventar requisitos além do spec
- Não apresentar plan ao PM nem seguir para testes sem `plan-review.json` aprovado, com proveniencia do `architecture-expert` (modo: plan-review) em contexto `isolated` e `findings: []`
- Se o architecture-expert gerar ADR, mencionar ao PM: "surgiu uma decisão que afeta o projeto todo — rode /decide-stack ou peça mais detalhes"
- Até 5 ciclos automáticos de re-geração do plan. Na 6ª falha consecutiva, escalar humano (R6)

## Conformidade com protocolo v1.2.4

- **Agents invocados:** `architecture-expert (plan)` seguido por `architecture-expert (plan-review)` em contexto isolado — conforme mapa canonico 00 §3.1
- **Gates produzidos:** `plan-review` (via `/review-plan` interno) — nome canonico enum do gate: `plan-review`
- **Output:** `specs/NNN/plan.md` (markdown estruturado) + `specs/NNN/plan-review.json` (gate JSON)
- **Schema formal:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios) para o plan-review.json
- **Isolamento R3:** plan-review roda em contexto limpo, sem acesso ao contexto que gerou o plan (dual-verifier R11)
- **Zero-tolerance:** plan-review `approved` somente com `blocking_findings_count == 0`. Qualquer finding forca re-geracao do plan.
- **Ordem no pipeline:** pre-requisito: `/audit-spec NNN` approved; proximo: `/draft-tests NNN`
- **Referencia normativa:** `CLAUDE.md §6 Fase D`; `docs/constitution.md §2 P1, §4 R11`; `docs/protocol/04-criterios-gate.md` (criterios de plan-review)
