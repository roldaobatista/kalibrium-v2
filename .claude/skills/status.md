---
description: Mostra o estado completo do projeto em linguagem de produto (R12). Le project-state.json e apresenta fase, epico/story/slice ativos, gates, pendencias e proxima acao. Substitui /where-am-i com dados estruturados. Uso: /status.
---

# /status

## Uso
```
/status
```

## Por que existe
O PM precisa saber "onde estamos" a qualquer momento, sem precisar entender arquivos tecnicos. Esta skill le o estado persistido e apresenta tudo em linguagem de produto.

## Quando invocar
A qualquer momento. Especialmente ao iniciar uma sessao.

## Pre-condicoes
- Nenhuma (funciona mesmo sem project-state.json, degradando gracefully)

## O que faz

### 1. Ler estado persistido
Tentar ler `project-state.json` na raiz do projeto. Se nao existir, construir estado a partir de:
- `docs/product/prd.md` (status)
- `docs/adr/*.md` (contagem)
- `epics/ROADMAP.md` (se existir)
- `specs/*/spec.md` (slices existentes)
- `git log --oneline -5`

### 2. Apresentar ao PM

Formato em linguagem R12:
```
📊 Estado do Projeto: Kalibrium V2

🔹 Fase atual: [Descoberta | Estrategia | Planejamento | Execucao | Release]

🔹 Progresso geral:
   - PRD: [rascunho | em revisao | congelado ✅]
   - Requisitos nao-funcionais: [rascunho | congelado ✅]
   - Arquitetura: [pendente | decidida | congelada ✅]
   - ADRs aceitos: N

🔹 Planejamento:
   - Epicos definidos: N (M decompostos em stories)
   - Stories com contrato: N de M

🔹 Execucao (se aplicavel):
   - Epico ativo: ENN — <titulo>
   - Story ativa: ENN-SNN — <titulo>
   - Slice ativo: slice-NNN — <status>
   - Ultimo commit verde: <hash curto>
   - Gates:
     ✅ Verificador mecanico: [aprovado]
     ✅ Revisor de codigo: [aprovado]
     ⏳ Revisor de seguranca: [pendente]
     ⏳ Auditor de testes: [pendente]
     ⏳ Revisor funcional: [pendente]

🔹 Bloqueios: [nenhum | <descricao>]

🔹 Decisoes pendentes:
   - <decisao 1>

🔹 Proxima acao recomendada:
   → <acao clara e unica>
```

### 3. Se nao houver state
```
📊 Estado do Projeto: Kalibrium V2

Ainda nao existe um estado persistido do projeto.
Isso e normal se estamos comecando.

Para iniciar o projeto do zero: /intake
Para retomar de onde paramos: /resume
Para ver o que o harness tem hoje: /where-am-i
```

## Implementacao

Ler `project-state.json` (se existir) e complementar com dados do git e filesystem. Nao criar o arquivo — isso e papel de `/checkpoint`.

## Handoff
- PM quer avancar → sugerir proxima skill baseada na fase
- PM quer detalhes → sugerir `/where-am-i` para detalhes tecnicos
- PM quer retomar → sugerir `/resume`
