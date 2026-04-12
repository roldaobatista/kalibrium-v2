---
description: Congela a arquitetura para a fase de planejamento. Valida que PRD esta frozen, ADRs existem, threat model existe, deploy profile definido. Nenhum codigo antes deste gate. Uso: /freeze-architecture.
---

# /freeze-architecture

## Uso
```
/freeze-architecture
```

## Por que existe
Decisoes de arquitetura afetam todo o codigo. Mudar stack, modelo de dados ou deploy no meio da implementacao causa retrabalho massivo. Este gate garante que tudo esta decidido antes de comecar a codar.

## Quando invocar
Apos `/decide-stack` e revisao de ADRs pelo PM. Antes de criar epicos/stories.

## Pre-condicoes (validadas)
1. `docs/product/PRD.md` tem status `FROZEN`
2. `docs/adr/0001-*.md` existe (stack decision)
3. Pelo menos 1 ADR adicional existe (dados, auth, ou deploy)
4. `docs/security/threat-model.md` existe e nao esta vazio
5. `docs/architecture/foundation-constraints.md` existe
6. `docs/product/nfr.md` tem status `FROZEN` ou pelo menos revisado

## O que faz

### 1. Validacao de completude
Verifica cada pre-condicao. Lista gaps se existirem.

### 2. Validacao de consistencia
- ADRs nao contradizem entre si
- Stack escolhida atende NFRs de performance e custo
- Threat model cobre dados sensiveis listados no intake
- Deploy profile compativel com hospedagem declarada no intake

### 3. Matriz de decisao ao PM
Apresenta em linguagem R12:
```
Todas as decisoes tecnicas estao tomadas. Resumo:

📋 Stack: Laravel 11 + Livewire 3 + PostgreSQL (ADR-0001)
📋 Deploy: [modelo escolhido] (ADR-NNNN)
📋 Seguranca: [resumo do threat model]
📋 Dados: [modelo resumido]

Apos congelar a arquitetura:
- Mudancas de stack so via ADR formal + retrospectiva
- Comecamos a quebrar o projeto em epicos e stories

Confirma o congelamento? (sim/nao)
```

### 4. Congelamento
Se PM confirmar:
1. Adicionar aos ADRs relevantes: `Status: ACCEPTED-FROZEN — YYYY-MM-DD`
2. Atualizar `project-state.json`:
   ```json
   { "strategy": { "architecture_status": "frozen" } }
   ```
3. Criar snapshot: `docs/architecture/snapshots/arch-frozen-YYYY-MM-DD.md`
4. Registrar em telemetria

### 5. Proximo passo
```
Arquitetura congelada. Proximo passo: planejar a execucao.

Vou decompor o projeto em epicos (blocos grandes de trabalho)
e depois em stories (unidades implementaveis).

Quer iniciar? → /decompose-epics
```

## Agentes
Nenhum — executada pelo orquestrador.

## Erros e Recuperacao

| Erro | Recuperacao |
|---|---|
| PRD nao esta FROZEN | Abortar e sugerir `/freeze-prd` primeiro. Nao tentar congelar arquitetura sem PRD frozen. |
| ADR-0001 nao existe | Abortar e sugerir `/decide-stack`. Informar PM que a decisao de stack e pre-requisito. |
| Contradicao entre ADRs (ex: stack vs deploy) | Listar as contradicoes ao PM em linguagem R12. Sugerir revisao dos ADRs conflitantes antes de congelar. |
| Threat model nao cobre dados sensiveis do intake | Listar os dados sensiveis descobertos no intake que faltam no threat model. Nao congelar ate resolver. |

## Handoff
- PM confirma → congelar e iniciar planejamento
- PM recusa → listar o que quer mudar
- Pre-condicao falha → listar gaps e sugerir ADRs necessarios
