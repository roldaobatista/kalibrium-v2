---
description: Decompoe o PRD em epicos com dependencias e prioridades. Requer PRD frozen e arquitetura frozen. Spawn product-expert (decompose) que produz epics/ROADMAP.md e epics/ENN/epic.md para cada epico. Uso: /decompose-epics.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-003 + SK-005"
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
1. `docs/product/PRD.md` tem status `FROZEN`
2. Arquitetura congelada (ADRs com status `ACCEPTED-FROZEN`)
3. `docs/product/nfr.md` existe
4. `docs/product/domain-model.md` existe

## O que faz

### 1. Validar pre-condicoes
Se alguma falhar, listar o que falta e parar.

### 2. Spawn product-expert (modo: decompose)
Spawn sub-agent `product-expert` (modo: decompose) com acesso aos documentos aprovados.
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
- `product-expert` (modo: decompose) — decompoe PRD em epicos com dependencias, prioridades e estimativas de stories

## Erros e Recuperacao

| Erro | Recuperacao |
|---|---|
| `product-expert` (modo: decompose) gera epicos sem dependencias claras | Re-spawnar com instrucao explicita para mapear dependencias. Fazer até 5 ciclos automáticos; na 6ª falha consecutiva, escalar humano (R6). |
| Epicos gerados extrapolam o escopo do MVP | Comparar com `docs/product/mvp-scope.md`. Remover epicos fora do MVP e marcar como "pos-MVP" no roadmap. |
| Pre-condicao falha (PRD ou arquitetura nao frozen) | Listar o que falta e sugerir `/freeze-prd` ou `/freeze-architecture` conforme o caso. |

## Handoff
- PM aprova roadmap → `/decompose-stories E01` (primeiro epico)
- PM quer ajustar → reexecutar com feedback
- Pre-condicao falha → sugerir skill adequada

## Output para PM (R12)

Toda invocação termina com mensagem ao PM em linguagem de produto (nunca output técnico cru). R12 é obrigatório: o `product-expert` gera `ROADMAP.md` técnico, mas a skill **traduz** para o PM antes de apresentar.

### Template de apresentação ao PM

```
Quebrei o projeto em N blocos grandes de trabalho (épicos).
Cada bloco entrega uma parte do produto pronta para usar.

Ordem sugerida (com motivo):

1. E01 — <nome amigável, sem jargão técnico>
   O que o cliente vai ver quando este bloco estiver pronto:
     <frase em PT-BR, sem termos como "API", "endpoint", "schema">
   Por que começar por aqui: <razão de produto, não técnica>
   Tempo estimado: <N stories pequenas>

2. E02 — <nome>
   O que entrega: <...>
   Depende de: E01 estar pronto (<motivo de produto>)
   Tempo estimado: <...>

[...]

Sua decisão:
- Aceito a ordem proposta
- Quero trocar E0X por E0Y primeiro (motivo: ___)
- Quero conversar sobre algum épico antes de decidir
```

### Regras R12

1. **Nunca** apresentar `ROADMAP.md` cru ao PM. Traduzir.
2. Nomes dos épicos em **linguagem de produto**: "Cadastro de clientes" (não "CRUD Customer Resource").
3. Dependências traduzidas em termos de produto: "depende do login funcionar" (não "depende do middleware de auth").
4. Sempre ofertar **próximo passo único** (aceitar / trocar ordem / conversar).
5. Se PM responder "não entendi": reformular com analogia do cotidiano, não com mais jargão.

## Conformidade com protocolo v1.2.2

- **Agents invocados:** `product-expert (decompose)` — mapa canônico §3.1.
- **Gates produzidos:** não é gate; é scaffold de planejamento.
- **Output:** `epics/ROADMAP.md` + `epics/ENN/epic.md` + mensagem R12 ao PM.
- **Schema formal:** estrutura de épico declarada em template `docs/templates/epic.md`.
- **Isolamento R3:** `product-expert` roda em contexto isolado (worktree não aplicável — consome docs read-only).
- **Ordem no pipeline:** após `/freeze-architecture`; precede `/decompose-stories ENN`.
