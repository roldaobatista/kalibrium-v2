---
description: Traduz proposta de mudanca no harness (harness-learner) para linguagem de produto (R12). Apresenta 3 secoes obrigatorias — "o que muda", "por que agora", "o que pode dar errado" — antes de PM decidir. ADR-0019 Mudanca 1.
protocol_version: "1.2.4"
changelog: "2026-04-16 — skill nova criada em ADR-0019 Mudanca 1 (fecha gap #1 da auditoria de fluxo 2026-04-16)"
---

# /explain-harness-change

## Uso
```
/explain-harness-change ENN
```

Onde `ENN` e o identificador do epico cuja retrospectiva originou a proposta.

## Por que existe

Fecha o gap #1 (S1) da auditoria de fluxo 2026-04-16: ate ADR-0019, o `governance (harness-learner)` podia aplicar mudancas no proprio harness com revisao apenas do `governance (guide-audit)` — mesmo agente em modo diferente, violando R11 (dual-verifier) no ponto mais sensivel do sistema.

Esta skill e a etapa final antes de qualquer mudanca no harness:

1. `governance (harness-learner)` propoe (gera `docs/governance/harness-learner-ENN.md`)
2. `architecture-expert (harness-review)` audita em instancia isolada (gera `harness-learner-review-ENN.json`)
3. **ESTA SKILL** traduz a mudanca aprovada tecnicamente para linguagem de produto e obtem confirmacao explicita do PM
4. So apos os 3 passos, o commit nos arquivos selados/harness pode ocorrer

## Quando invocar

Apos `architecture-expert (harness-review)` aprovar a proposta com `verdict: approved` e `findings: []`. Nunca antes.

## Pre-condicoes

1. `docs/governance/harness-learner-ENN.md` existe
2. `docs/governance/harness-learner-review-ENN.json` existe com `verdict: approved`, `findings: []`, `provenance.agent: architecture-expert`, `provenance.mode: harness-review`, `isolation_context` declarado
3. PM esta disponivel para confirmar explicitamente (nao e decisao automatizada)

## O que faz

### 1. Ler proposta e auditoria

- `docs/governance/harness-learner-ENN.md` (proposta original)
- `docs/governance/harness-learner-review-ENN.json` (auditoria tecnica)

### 2. Montar apresentacao em 3 secoes obrigatorias

```
🔧 Proposta de melhoria no funcionamento da fabrica (epico ENN)

## O que muda

<frase unica em PT-BR, sem jargao tecnico>

Exemplo:
  "Quando o agente terminar uma tarefa, ele vai passar por duas checagens
  independentes em vez de uma — como ter um segundo auditor conferindo
  antes de voce aceitar."

## Por que agora

<motivacao: qual incidente/retrospectiva originou; sem jargao>

Exemplo:
  "Na retrospectiva do epico 3, notamos que em dois slices diferentes
  o mesmo tipo de problema passou pela revisao. Com a checagem dupla,
  esses problemas serao pegos antes de voce ver."

## O que pode dar errado

<riscos: em que situacao essa mudanca pode atrapalhar; em PT-BR>

Exemplo:
  "Em slices muito simples (ex: ajuste de texto), a segunda checagem
  pode parecer redundante e adicionar 2-3 minutos ao tempo total.
  Se voce perceber isso incomodando, me avise — posso ajustar."

---

Proxima etapa: voce autoriza esta mudanca?

[ ] Sim, aplique
[ ] Nao, quero entender melhor
[ ] Nao, prefiro manter como esta
```

### 3. Aguardar decisao explicita do PM

- **Sim:** criar `docs/governance/harness-learner-pm-approval-ENN.md` com:
  ```markdown
  # PM Approval — harness-learner ENN

  **Data:** YYYY-MM-DD
  **Decisor:** PM (owner do projeto)
  **Proposta:** docs/governance/harness-learner-ENN.md
  **Revisao tecnica:** docs/governance/harness-learner-review-ENN.json (approved, findings [])
  **Decisao:** approved

  ## Resumo aceito
  <copia das 3 secoes apresentadas>

  ## Nota
  Este arquivo e requisito para commit em arquivos selados/harness (ADR-0019 Mudanca 1).
  ```

- **Nao, quero entender melhor:** pausar processo, aguardar perguntas do PM. Responder em PT-BR sem jargao.

- **Nao, prefiro manter:** registrar em `docs/governance/harness-learner-ENN-rejected-by-pm.md` com data e motivacao. Nao aplicar mudanca.

### 4. Handoff

- **Approved:** retornar ao orchestrator indicando que commit no harness esta liberado
- **Rejeicao explicita:** retornar ao orchestrator indicando que a proposta fica arquivada; se problema persistir, proxima retrospectiva pode reapresentar com ajustes

## Regras

- **Nunca** expor termos tecnicos ao PM. Se nao souber traduzir, perguntar ao orchestrator como traduzir (nao inventar).
- **Nunca** pular as 3 secoes. Omitir "o que pode dar errado" e inaceitavel — todo PM merece saber os riscos antes de aprovar.
- **Nunca** aplicar mudanca sem o arquivo `pm-approval-ENN.md`. Este arquivo e a evidencia auditavel de que o PM consentiu.
- PM tem direito de ouvir com mais detalhe tecnico se pedir. Oferecer isso, nao impor.

## Conformidade com protocolo v1.2.4 + ADR-0019

- **Agents invocados:** nenhum (skill de orquestracao/comunicacao, nao de gate)
- **Gate name:** n/a (esta skill e pos-gate, pre-commit)
- **Output:** `docs/governance/harness-learner-pm-approval-ENN.md` OU `docs/governance/harness-learner-ENN-rejected-by-pm.md`
- **R12:** obrigatorio — esta skill e o ponto de traducao para o PM
- **Nota sobre enforcement:** validacao mecanica no `pre-commit-gate.sh` que bloqueia commit sem este arquivo de approval exige relock do PM (CLAUDE.md §9). Ate o relock ocorrer, a regra e procedural (orchestrator garante via este skill). Documentado como pendencia em handoff.
