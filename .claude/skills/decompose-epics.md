---
description: Decompoe o PRD em epicos com dependencias e prioridades. Requer PRD frozen e arquitetura frozen. Spawn epic-decomposer que produz epics/ROADMAP.md e epics/ENN/epic.md para cada epico. Uso: /decompose-epics.
---

# /decompose-epics

## Uso
```
/decompose-epics
```

## Por que existe
Antes de implementar, o projeto precisa ser quebrado em blocos gerenciaveis. Epicos sao os blocos grandes — cada um entrega valor incremental e tem criterios claros de entrada/saida.

## Quando invocar
Apos `/freeze-architecture`. Antes de detalhar stories.

## Pre-condicoes (validadas)
1. `docs/product/prd.md` tem status `FROZEN`
2. Arquitetura congelada (ADRs com status `ACCEPTED-FROZEN`)
3. `docs/product/nfr.md` existe
4. `docs/product/domain-model.md` existe

## O que faz

### 1. Validar pre-condicoes
Se alguma falhar, listar o que falta e parar.

### 2. Spawn epic-decomposer
Spawn sub-agent `epic-decomposer` com acesso aos documentos aprovados.
O agent produz:
- `epics/ROADMAP.md` — indice com sequencia e dependencias
- `epics/ENN/epic.md` — detalhamento de cada epico

### 3. Apresentar roadmap ao PM

```
Decomposicao em epicos concluida. Aqui esta o roadmap:

📋 Epicos planejados: N

1. E01 — Setup e Infraestrutura (P0)
   → Base tecnica do projeto
   → Stories estimadas: 5

2. E02 — Autenticacao e Multi-tenancy (P0, depende de E01)
   → Login, permissoes, separacao de dados por empresa
   → Stories estimadas: 6

3. E03 — Cadastro de Instrumentos (P1, depende de E02)
   → CRUD de instrumentos com validacao
   → Stories estimadas: 4

[...]

Proximo passo: aprovar a sequencia e prioridades.
Quer ajustar algo ou posso prosseguir para detalhar stories do E01?
```

### 4. Apos aprovacao do PM
Atualizar `project-state.json`:
```json
{
  "phase": "planning",
  "planning": {
    "epics_total": N,
    "epics_decomposed": N,
    "stories_total": 0,
    "stories_with_contract": 0
  }
}
```

## Agentes
- `epic-decomposer` — decompoe PRD em epicos com dependencias, prioridades e estimativas de stories

## Erros e Recuperacao

| Erro | Recuperacao |
|---|---|
| `epic-decomposer` gera epicos sem dependencias claras | Re-spawnar com instrucao explicita para mapear dependencias. Se falhar 2x, escalar humano (R6). |
| Epicos gerados extrapolam o escopo do MVP | Comparar com `docs/product/mvp-scope.md`. Remover epicos fora do MVP e marcar como "pos-MVP" no roadmap. |
| Pre-condicao falha (PRD ou arquitetura nao frozen) | Listar o que falta e sugerir `/freeze-prd` ou `/freeze-architecture` conforme o caso. |

## Handoff
- PM aprova roadmap → `/decompose-stories E01` (primeiro epico)
- PM quer ajustar → reexecutar com feedback
- Pre-condicao falha → sugerir skill adequada
