---
description: Decompoe um epico aprovado em stories com Story Contract completo. Spawn product-expert (decompose) que produz epics/ENN/stories/INDEX.md e ENN-SNN.md por story. Uso: /decompose-stories ENN.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005R"
---

# /decompose-stories

## Uso
```
/decompose-stories ENN
```

Exemplo: `/decompose-stories E01`

## Por que existe
Stories sao a unidade de implementacao. Cada story precisa de um contrato completo (Story Contract) antes de qualquer codigo ser escrito. Isso garante que PM e agente concordam sobre escopo, ACs e evidencias.

## Quando invocar
Apos `/decompose-epics` e aprovacao do roadmap pelo PM. Executar por epico, na ordem do roadmap.

## Pre-condicoes (validadas)
1. `epics/ENN/epic.md` existe e foi aprovado pelo PM
2. `epics/ROADMAP.md` existe
3. Criterios de entrada do epico estao satisfeitos
4. Epicos dos quais ENN depende estao completos (ou em execucao)

## O que faz

### 1. Validar pre-condicoes
Se alguma falhar, listar o que falta e parar.

### 2. Spawn product-expert (modo: decompose)
Spawn sub-agent `product-expert` (modo: decompose) com acesso ao epic e docs aprovados.
O agent produz:
- `epics/ENN/stories/INDEX.md` — indice de stories
- `epics/ENN/stories/ENN-SNN.md` — Story Contract por story

### 3. Apresentar stories ao PM

```
Decomposicao do epico ENN concluida. Stories planejadas: N

📋 E01-S01 — Scaffold do projeto Laravel conforme ADR-0001
   ACs: 4 | Complexidade: baixa
   Depende de: nenhuma

📋 E01-S02 — Configurar PostgreSQL
   ACs: 3 | Complexidade: baixa
   Depende de: E01-S01

📋 E01-S03 — Pipeline CI basico
   ACs: 5 | Complexidade: media
   Depende de: E01-S01

[...]

Cada story tem um contrato completo com:
- Objetivo, escopo, fora de escopo
- Criterios de aceite testáveis
- Riscos e mitigacoes
- Evidencia necessaria para aprovacao

Proximo passo: aprovar cada Story Contract.
Quer ver o contrato detalhado de alguma story?
```

### 4. Aprovacao individual
PM revisa cada Story Contract. Para cada uma:
- "aceito" → marcar como aprovada
- "ajustar X" → editar e reapresentar
- "remover" → remover da lista

### 5. Apos aprovacao
Atualizar `project-state.json`:
```json
{
  "planning": {
    "stories_total": N,
    "stories_with_contract": M
  }
}
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Épico `ENN` não existe ou não foi aprovado pelo PM | Listar pré-condições faltantes. Orientar PM a aprovar o épico primeiro ou rodar `/decompose-epics`. |
| Sub-agent `product-expert` (modo: decompose) falha ou excede budget (30k tokens) | Revisar complexidade do épico. Se muito grande, sugerir ao PM dividir o épico antes de decompor. Re-invocar com contexto reduzido. |
| PM rejeita todas as stories propostas | Voltar ao épico (`epics/ENN/epic.md`), revisar escopo com PM, e re-decompor com novas diretrizes. |
| Dependência circular entre stories detectada | Apresentar o grafo de dependências ao PM (em linguagem de produto). Reorganizar a sequência até eliminar ciclos. |

## Agentes

- `product-expert` (modo: decompose) (budget: 30k tokens) — decompõe o épico em stories com Story Contract completo.

## Handoff
- Todos os contratos aprovados → `/start-story ENN-S01` para iniciar a primeira
- PM quer ajustar → editar contratos especificos
- Pre-condicao falha → listar dependencias nao satisfeitas

## Conformidade com protocolo v1.2.4

- **Agents invocados:** `product-expert (decompose)` — conforme mapa canonico 00 §3.1 (budget: 30k tokens)
- **Gates produzidos:** n/a — skill de decomposicao de planejamento, nao gate
- **Output:** `epics/ENN/stories/INDEX.md` + `epics/ENN/stories/ENN-SNN.md` (Story Contract por story, markdown R12 com frontmatter)
- **Schema formal:** nao aplicavel (Story Contracts sao markdown estruturado, nao gate JSON)
- **Isolamento R3:** nao aplicavel — decomposicao e atividade de produto, sem dual-verifier
- **Zero-tolerance:** nao aplicavel (sem verdict); PM aprova cada contrato individualmente
- **Ordem no pipeline:** pre-requisito: `/decompose-epics` approved + `epics/ENN/epic.md` aprovado pelo PM; proximo: `/start-story ENN-S01`
- **Referencia normativa:** `CLAUDE.md §6 Fase C`; `docs/constitution.md §2 P1` (contexto precede decisao), §4 R13 (Story Contract com `dependencies: []` habilita paralelismo intra-epico)
