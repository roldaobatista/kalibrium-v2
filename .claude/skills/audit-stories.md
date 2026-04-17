---
name: audit-stories
description: Audita Story Contracts de um epico. Roda qa-expert (audit-story) em contexto limpo. Ciclo automatico — auditoria, correcao, re-auditoria ate aprovado (5 ciclos automáticos; 6ª rejeição escala PM). Uso /audit-stories ENN.
user_invocable: true
protocol_version: "1.2.4"
---

# /audit-stories

## Uso
```
/audit-stories ENN
```
Exemplo: `/audit-stories E01`

## Por que existe
Story Contracts podem ter ACs vagos, secoes faltantes, gaps de cobertura ou dependencias quebradas. Este skill roda um auditor independente em contexto limpo e, se encontrar problemas, corrige e re-audita automaticamente ate ficar limpo.

## Quando invocar
- **Automaticamente** apos cada `/decompose-stories ENN`
- Antes de `/start-story ENN-SNN` (gate de qualidade)
- Quando PM quiser validar contratos de stories

## Pre-condicoes (validadas)
1. `epics/ENN/epic.md` existe
2. `epics/ENN/stories/INDEX.md` existe
3. Pelo menos 1 arquivo `epics/ENN/stories/ENN-SNN.md` existe

## O que faz

### 1. Validar pre-condicoes
Se alguma falhar, listar o que falta e parar.

### 2. Spawn qa-expert (modo: audit-story) (contexto limpo)
Spawn sub-agent `qa-expert` (modo: audit-story) conforme mapa canonico 00 §3.1.
Produz: `docs/audits/planning/story-audit-ENN.json`

### 3. Avaliar resultado

#### Se approved (0 critical, 0 major)
Apresentar ao PM em linguagem R12:
```
Auditoria de stories do ENN: APROVADO

Stories auditadas: N
Total de ACs: M
Checks: X/X passaram
Findings minor: Y (nenhum bloqueante)

[lista de minor findings se houver]

Proximo passo: aprovar stories e iniciar implementacao com /start-story ENN-S01
```

#### Se rejected
Apresentar ao PM:
```
Auditoria de stories do ENN: PROBLEMAS ENCONTRADOS

Stories auditadas: N
Findings: X criticos, Y importantes, Z menores

Problemas encontrados:
1. [SA-001] (critico) — Descricao em linguagem de produto
2. [SA-002] (importante) — Descricao em linguagem de produto

Corrigindo automaticamente...
```

### 4. Ciclo de correcao (se rejected)

```
loop (5 ciclos automáticos; 6ª rejeição escala PM):
  1. Analisar findings do story-audit-ENN.json
  2. Para cada finding critical/major:
     - Se e secao faltante → completar no Story Contract
     - Se e AC vago/subjetivo → reescrever AC com comando + resultado esperado
     - Se e gap de cobertura → adicionar AC ou story
     - Se e dependencia quebrada → corrigir referencia
     - Se e scope leak → mover AC para story correta
  3. Re-spawnar `qa-expert` (modo: audit-story) (contexto limpo novo)
  4. Se approved → sair do loop
  5. Se rejected de novo → repetir
  6. Se 6 iteracoes sem aprovacao → escalar humano (R6)
```

### 5. Resultado final ao PM (R12)
Sempre em linguagem de produto. Nunca mostrar JSON cru.

## Integracao no fluxo

### Invocacao automatica
O orquestrador DEVE invocar `/audit-stories ENN` automaticamente apos cada `/decompose-stories ENN`, ANTES de apresentar stories ao PM para aprovacao.

### Fluxo completo
```
/decompose-stories ENN
  → product-expert (decompose) gera stories
  → /audit-stories ENN (automatico)
    → qa-expert (audit-story) valida
    → se rejected: corrige + re-audita (5 ciclos automaticos; 6a escala PM)
    → se approved: apresenta ao PM
  → PM aprova/ajusta stories
  → /start-story ENN-SNN
```

## Erros e Recuperacao

| Cenario | Recuperacao |
|---|---|
| `qa-expert` (modo: audit-story) excede budget (40k tokens) | Epico com muitas stories. Auditar em lotes de 3-4 stories. |
| Ciclo de correcao nao converge (6 iteracoes) | Escalar humano via R6. Apresentar findings restantes traduzidos com `/explain-slice`. |
| Pre-condicao falha | Listar o que falta e sugerir `/decompose-stories ENN`. |
| Story Contract com formato inesperado | Reportar como finding critical. Fixer regenera a partir do template. |

## Agentes
- `qa-expert` (modo: audit-story) (budget: 40k) — audita e emite JSON estruturado
- `builder` (modo: fixer) (budget: 60k) — corrige findings quando necessario (via orquestrador)

## Handoff
- Approved → prosseguir para aprovacao do PM e `/start-story ENN-SNN`
- Rejected e corrigido → re-auditar automaticamente
- Rejected 6x → escalar humano com `/explain-slice`

## Conformidade com protocolo v1.2.4

- **Agent invocado:** `qa-expert (audit-story)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `audit-story`
- **Output:** `docs/audits/planning/story-audit-ENN.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §11`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`
