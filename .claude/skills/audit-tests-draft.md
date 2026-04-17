---
description: Audita testes red antes da implementacao (ADR-0017 Mudanca 1). Garante que cada AC tem teste, cada teste referencia AC-ID, e testes estao realmente red. Uso: /audit-tests-draft NNN.
protocol_version: "1.2.2"
changelog: "2026-04-16 — skill nova criada em ADR-0017 Mudanca 1 (fecha gap #0 da auditoria de fluxo 2026-04-16)"
---

# /audit-tests-draft

## Uso
```
/audit-tests-draft NNN
```

## Por que existe

Ate ADR-0017, havia um gap critico no fluxo: apos `/draft-tests` (builder test-writer), o `builder implementer` era invocado diretamente. Se o test-writer escrevesse um teste cobrindo AC errado, o implementer fazia o teste "ficar verde" e o problema so era detectado no gate `audit-tests` tardio (apos toda a implementacao pronta). Resultado: ciclos inteiros de implementacao perdidos.

Esta skill fecha o gap rodando auditoria independente dos testes **antes** da implementacao. O `qa-expert (audit-tests-draft)` valida que testes e ACs estao mecanicamente vinculados (AC-ID rastreavel) e que os testes estao realmente red.

## Quando invocar

Apos `/draft-tests NNN` concluir com commit `test(slice-NNN): AC tests red`. **Antes** de qualquer invocacao do `builder implementer`.

O orquestrador invoca automaticamente no fluxo Fase D: `/draft-tests NNN` -> `/audit-tests-draft NNN` -> implementer (so se approved).

## Pre-condicoes (validadas)

1. `specs/NNN/spec.md` existe com ACs numerados (formato AC-001, AC-002, ...)
2. `specs/NNN/ac-list.json` existe (gerado por `/draft-spec` ou `/audit-spec`)
3. Arquivos de teste em `tests/Feature/SliceNNN/` ou `tests/Unit/SliceNNN/` existem
4. `specs/NNN/plan-review.json` existe com `verdict: approved` e `findings: []`
5. Ultimo commit e `test(slice-NNN): AC tests red`
6. `specs/NNN/tests-draft-audit.json` NAO existe ainda (ou existe de tentativa anterior rejeitada)

Se alguma pre-condicao falhar, a skill aborta com mensagem clara em PT-BR.

## O que faz

### 1. Montar `tests-draft-audit-input/` (sandbox)

Diretorio tempo/untracked com APENAS os inputs permitidos ao modo `audit-tests-draft`:

- `spec.md` — copia de `specs/NNN/spec.md`
- `ac-list.json` — copia de `specs/NNN/ac-list.json`
- `test-files/` — copia dos arquivos de teste do slice
- `test-run-output.txt` — output da execucao dos testes (deve mostrar falhas)
- `constitution-snapshot.md` — copia de `docs/constitution.md`

### 2. Executar os testes e capturar output

```
vendor/bin/pest --filter=SliceNNN --no-coverage 2>&1 > tests-draft-audit-input/test-run-output.txt
```

Comportamento esperado: exit code != 0 (testes red). Se exit == 0, a skill aborta e reporta ao orquestrador — testes nao podem estar green nesta fase.

### 3. Spawn qa-expert (modo: audit-tests-draft)

```
Agent(subagent_type="qa-expert")
```

Com prompt explicitando:
- modo: `audit-tests-draft`
- slice: NNN
- isolation_context: `slice-NNN-audit-tests-draft-instance-01`
- input dir: `tests-draft-audit-input/`
- output target: `specs/NNN/tests-draft-audit.json`
- schema de validacao: `docs/protocol/schemas/gate-output.schema.json`

**Isolamento:** NAO usar `isolation: "worktree"` (input e untracked). Isolamento garantido por:
- Hook `verifier-sandbox.sh` (read-only mount do input)
- R11: audit-tests-draft NAO pode ser invocado na mesma instancia que o test-writer que produziu os testes

### 4. Validar output contra schema

Output `tests-draft-audit.json` deve ter 14 campos canonicos. Validar com:

```
bash scripts/validate-gate-output.sh specs/NNN/tests-draft-audit.json
```

Se nao valida, re-spawn qa-expert ate 5x. Na 6a falha -> R6 escalacao.

### 5. Apresentar ao PM (R12)

**Caso `approved` (findings: []):**
```
🧪 Auditoria dos testes: APROVADO

Cada criterio que voce definiu tem teste correspondente.
Todos os testes estao falhando como esperado (red).
Vinculacao AC <-> teste: 100%.

Proxima etapa: o agente vai comecar a implementacao para fazer os testes passarem.
```

**Caso `rejected`:**
```
🧪 Auditoria dos testes: PRECISA DE AJUSTE

Encontrei N pontos antes de partir para a implementacao:
- 2 criterios sem teste correspondente
- 1 teste que nao consigo vincular a criterio

Vou corrigir automaticamente os testes e reexecutar a auditoria.
```

Detalhes tecnicos ficam em `specs/NNN/tests-draft-audit.json` — nao mostrar ao PM.

### 6. Persistir resultado

- `specs/NNN/tests-draft-audit.json` gravado
- `project-state.json` atualizado: `slice_NNN.state: S7.1`, `slice_NNN.gates_status.audit_tests_draft: <verdict>`
- Evento telemetria append: `{"event": "gate_result", "gate": "audit-tests-draft", "verdict": "<v>", "attempt": N}`

## Loop fixer -> re-audit (zero tolerance)

Se `verdict == rejected` (qualquer finding de qualquer severidade):

1. Orquestrador invoca `builder (fixer em modo test-writer)` passando apenas `findings[]`
2. Fixer ajusta os testes (nao pode expandir escopo)
3. Fixer roda `vendor/bin/pest --filter=SliceNNN` e confirma que ainda estao red
4. Fixer commita: `fix(slice-NNN): audit-tests-draft correcoes`
5. Orquestrador re-invoca `/audit-tests-draft NNN` (mesmo gate, nao pula)
6. Repete ate `findings: []` ou R6 (6a rejeicao escala PM)

## Erros e Recuperacao

| Cenario | Recuperacao |
|---|---|
| `plan-review.json` nao existe ou nao tem `verdict: approved` | Abortar. Rodar `/review-plan NNN` primeiro — plan precisa estar aprovado antes de qualquer teste ser auditado. |
| Testes do slice passam (verdes) em vez de falhar | Abortar. Report ao orquestrador: "testes nao estao red — provavelmente ha implementacao parcial commitada. Verificar ultimo commit." |
| Arquivo de teste sem AC-ID e sem tag `@helper`/`@setup` | `audit-tests-draft` emite finding S1 ou S2. Fixer corrige o teste. |
| Teste nasce green (passa sem implementacao) | `audit-tests-draft` emite finding S1. Fixer corrige o teste para falhar por razao relevante. |
| `tests-draft-audit.json` nao valida contra schema | Re-spawn `qa-expert`. Se falhar 5 vezes consecutivas, escalar humano na 6a (R6). |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `qa-expert` (modo: audit-tests-draft) | sandbox via `scripts/hooks/verifier-sandbox.sh` (read-only mount) | 25k tokens |

## Handoff

- `approved` com `findings: []` -> builder invocado em modo implementer (Fase D prossegue)
- `rejected` -> builder (fixer em modo test-writer) -> re-run `/audit-tests-draft NNN`
- R6 (6a rejeicao) -> `/explain-slice NNN` -> PM decide (novo prompt de test-writer, descartar slice, etc.)

## Conformidade com protocolo v1.2.2 + ADR-0017

- **Agent invocado:** `qa-expert (audit-tests-draft)` — novo modo 5 conforme ADR-0017 Mudanca 1
- **Gate name (enum):** `audit-tests-draft` (novo gate #16 no protocolo 04)
- **Output:** `specs/NNN/tests-draft-audit.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §16.1`
- **Isolamento R3:** gate roda em instancia isolada; NAO pode ser a mesma instancia que test-writer (R11)
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0` e `findings: []`
- **Estado maquina:** S7 (testes red) -> S7.1 (testes red auditados) -> S8 (implementacao)

## Output no chat (para PM — R12)

Ao fim da execucao, apresentar ao PM em ate 3 linhas de linguagem de produto:

1. **Veredicto:** frase unica em PT-BR sem jargao — ex: "Os testes escritos batem com os criterios que voce definiu."
2. **Proxima etapa:** acao unica recomendada — ex: "Posso seguir para a implementacao."
3. **Se rejeitado:** "Encontrei N pontos nos testes antes de implementar. Vou corrigir automaticamente e reexecutar."

Nunca expor AC-IDs tecnicos, nomes de arquivos de teste, ou JSON cru ao PM. Detalhes ficam em `specs/NNN/tests-draft-audit.json` para o builder (fixer).
