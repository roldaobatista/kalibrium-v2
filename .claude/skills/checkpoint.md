---
description: Salva estado completo do projeto em project-state.json e cria handoff em docs/handoffs/. Garante que qualquer nova sessao pode retomar via /resume. Usar antes de encerrar sessao ou em pontos importantes. Uso: /checkpoint.
---

# /checkpoint

## Uso
```
/checkpoint
```

## Por que existe
A memoria da sessao e volatil. O checkpoint persiste o estado em arquivos que sobrevivem entre sessoes. Combinado com `/resume`, permite continuidade perfeita.

## Quando invocar
- Antes de encerrar uma sessao
- Apos concluir um marco importante (fase, epico, story, slice)
- Apos decisao do PM que muda o rumo
- Quando o contexto da sessao esta ficando grande

## Pre-condicoes
- Nenhuma (cria os arquivos se nao existirem)

## O que faz

### 1. Coletar estado atual

Inspecionar o projeto para determinar:
- Fase atual (discovery, strategy, planning, execution, release)
- Status de cada artefato de discovery (PRD, NFR, intake, domain model)
- Status da estrategia (ADRs, threat model, deploy profile)
- Status de planejamento (epicos, stories, contratos)
- Status de execucao (epic/story/slice ativos, gates, rejeicoes)
- Decisoes pendentes
- Bloqueios

### 2. Escrever `project-state.json`

Criar ou atualizar `project-state.json` na raiz do projeto seguindo `docs/schemas/project-state.schema.json`.

### 3. Escrever handoff

Criar `docs/handoffs/handoff-YYYY-MM-DD-HHMM.md` seguindo `docs/templates/handoff.md` com:
- Resumo da sessao
- Estado ao sair
- Pendencias
- Decisoes tomadas e pendentes
- Proxima acao recomendada

### 4. Atualizar latest
Copiar o conteudo do handoff mais recente para `docs/handoffs/latest.md`.

### 5. Confirmar ao PM

```
💾 Checkpoint salvo

Estado gravado em: project-state.json
Handoff gravado em: docs/handoffs/handoff-YYYY-MM-DD-HHMM.md

Para retomar na proxima sessao: /resume

Quer encerrar a sessao ou continuar trabalhando?
```

## Implementacao

```
1. Inspecionar filesystem e git para coletar estado
2. Validar contra project-state.schema.json
3. Write project-state.json
4. Write docs/handoffs/handoff-YYYY-MM-DD-HHMM.md
5. Write docs/handoffs/latest.md (copia)
```

## Handoff
- PM quer encerrar → confirmar que checkpoint esta salvo
- PM quer continuar → checkpoint salvo, prosseguir normalmente
