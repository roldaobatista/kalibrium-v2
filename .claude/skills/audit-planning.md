---
name: audit-planning
description: Audita epicos e stories contra PRD, FRs e NFRs. Roda qa-expert (audit-planning) em contexto limpo. Ciclo automatico: auditoria → correcao → re-auditoria ate aprovado. Uso: /audit-planning [ENN | roadmap | all].
user_invocable: true
protocol_version: "1.2.2"
---

# /audit-planning

## Uso
```
/audit-planning ENN        # auditar stories do epico ENN
/audit-planning roadmap    # auditar cobertura dos epicos contra PRD
/audit-planning all        # auditar tudo (roadmap + todos os epicos com stories)
```

## Por que existe
Epicos e stories podem ter gaps de cobertura, ACs subjetivos, dependencias circulares ou inconsistencias com o PRD. Este skill roda um auditor independente em contexto limpo e, se encontrar problemas, corrige e re-audita automaticamente ate ficar limpo.

## Quando invocar
- Apos `/decompose-epics` (auditar roadmap)
- Apos `/decompose-stories ENN` (auditar stories do epico)
- Antes de `/start-story` (gate de qualidade)
- Sempre que o PM quiser validar o planejamento

## Pre-condicoes
- `epics/ROADMAP.md` existe (para roadmap/all)
- `epics/ENN/epic.md` existe (para auditoria de epico)
- `epics/ENN/stories/INDEX.md` existe (para auditoria de stories)

## O que faz

### 1. Validar pre-condicoes
Se alguma falhar, listar o que falta e parar.

### 2. Spawn qa-expert (modo: audit-planning) (contexto limpo)
Spawn sub-agent `qa-expert` (modo: audit-planning) com escopo definido.
Produz: `docs/audits/planning/planning-audit-{scope}.json`

### 3. Avaliar resultado

#### Se approved (0 critical, 0 major)
```
Auditoria de planejamento: APROVADO

Escopo: [roadmap | E01 stories | all]
Checks: N/N passaram
Findings minor: M (nenhum bloqueante)

Proximo passo: [sugerir acao baseada na fase]
```

#### Se rejected (findings criticos ou majors)
```
Auditoria de planejamento: PROBLEMAS ENCONTRADOS

Escopo: [roadmap | E01 stories | all]
Findings: X criticos, Y importantes, Z menores

Problemas encontrados:
1. [PA-001] (critico) — Descricao em linguagem de produto
2. [PA-002] (importante) — Descricao em linguagem de produto

Vou corrigir automaticamente e re-auditar. Aguarde...
```

### 4. Ciclo de correcao (se rejected)

```
loop:
  1. Spawnar `builder` (fixer) ou `product-expert` (decompose) para corrigir findings
  2. Re-spawnar `qa-expert` (modo: audit-planning) (contexto limpo novo)
  3. Se approved → sair do loop
  4. Se rejected de novo → repetir (5 ciclos automáticos)
  5. Se 6 iteracoes sem aprovacao → escalar humano (R6)
```

### 5. Apresentar resultado final ao PM (R12)
Sempre em linguagem de produto. Nunca mostrar JSON cru.

## Erros e Recuperacao

| Cenario | Recuperacao |
|---|---|
| `qa-expert` (modo: audit-planning) excede budget (40k tokens) | Reduzir escopo: auditar 1 epico por vez em vez de all |
| Ciclo de correcao nao converge (6 iteracoes) | Escalar humano via R6. Apresentar findings restantes traduzidos. |
| Pre-condicao falha | Listar o que falta e sugerir skill adequada |

## Agentes
- `qa-expert` (modo: audit-planning) (budget: 40k) — audita e emite JSON estruturado
- `builder` (modo: fixer) (budget: 60k) — corrige findings quando necessario

## Handoff
- Approved → prosseguir para implementacao (`/start-story ENN-SNN`)
- Rejected e corrigido → re-auditar automaticamente
- Rejected 6x → escalar humano com `/explain-slice`

## Conformidade com protocolo v1.2.2

- **Agent invocado:** `qa-expert (audit-planning)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `audit-planning`
- **Output:** `docs/audits/planning/planning-audit-{scope}.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §12`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`
