---
description: Dispara o sub-agent architect para gerar plan.md a partir de spec.md auditado e aprovado. Valida pré-condições, spawna architect, valida output, e apresenta resultado ao PM em linguagem de produto (R12). Uso: /draft-plan NNN.
---

# /draft-plan

## Uso
```
/draft-plan NNN
```

## Por que existe
Sem esta skill, o PM precisa saber que "agora é hora de chamar o architect" e o agente principal precisa lembrar de fazer isso. Se a sessão acabar entre o spec e o plan, o contexto se perde. `/draft-plan` é o handoff explícito que sobrevive ao fim de sessão.

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

### Fase 2 — Disparar architect

Spawna o sub-agent `architect` (`.claude/agents/architect.md`) com:
- `subagent_type: "architect"`
- Prompt contendo o NNN do slice
- O architect lê spec.md, constitution, ADRs, e gera `specs/NNN/plan.md`

### Fase 3 — Validar output

```bash
bash scripts/draft-plan.sh NNN --validate
```

Se falhar, reporta ao agente principal que re-instrui o architect.

### Fase 4 — Apresentar ao PM (R12)

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
- **PM aceita** → marcar status do plan.md como `approved` e sugerir `/draft-tests NNN`
- **PM pede ajuste** → re-disparar architect com instruções adicionais
- **PM quer pausar** → registrar estado e encerrar sem bloquear

## Agentes
- `architect` — gera `specs/NNN/plan.md` a partir de spec.md, constitution e ADRs

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `specs/NNN/spec.md` não passa validação (`draft-spec.sh --check`) | Abortar e sugerir `/draft-spec NNN` para corrigir o spec antes de gerar o plan. |
| `specs/NNN/spec-audit.json` ausente ou reprovado | Abortar e rodar `/audit-spec NNN`; se houver findings, corrigir spec e reauditar. |
| `architect` gera plan.md que falha na validação (`draft-plan.sh --validate`) | Re-instruir o architect com o motivo da falha. Máximo 2 tentativas; na 3ª, escalar humano (R6). |
| `architect` inventa requisitos que não estão no spec | Rejeitar o plan, re-spawnar architect com instrução explícita de manter escopo do spec. |
| PM não entende o resumo R12 do plan | Reformular com analogias mais simples. Oferecer "quer que eu explique de outro jeito?" antes de prosseguir. |

## Regras
- Não inventar requisitos além do spec
- Se o architect gerar ADR, mencionar ao PM: "surgiu uma decisão que afeta o projeto todo — rode /decide-stack ou peça mais detalhes"
- Máximo 2 tentativas de re-geração do plan. Na 3ª falha, escalar humano (R6)
