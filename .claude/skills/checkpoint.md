---
description: Salva estado completo do projeto em project-state.json e cria handoff em docs/handoffs/. Garante que qualquer nova sessao pode retomar via /resume. Usar antes de encerrar sessao ou em pontos importantes. Uso: /checkpoint.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit Cat C polishing"
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

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `docs/schemas/project-state.schema.json` não existe | Criar o schema primeiro ou salvar `project-state.json` sem validação, registrando o débito. |
| Diretório `docs/handoffs/` não existe | Criar o diretório automaticamente antes de escrever o handoff. |
| Estado do projeto inconsistente (artefatos referenciados não existem) | Registrar warnings no handoff. Marcar campos ausentes como `"unknown"` no JSON. Não falhar — checkpoint parcial é melhor que nenhum. |
| Escrita do `project-state.json` falha (disco cheio, permissão) | Informar PM imediatamente. Tentar salvar pelo menos o handoff `.md` como fallback. |

## Agentes

Nenhum — executada pelo orquestrador.

## Handoff
- PM quer encerrar → confirmar que checkpoint esta salvo
- PM quer continuar → checkpoint salvo, prosseguir normalmente

## Próximo passo

- Checkpoint salvo → PM pode encerrar com segurança ou continuar
- Encerrou → próxima sessão abre com `/resume`
- Falha ao salvar → investigar disco/permissões antes de qualquer outro comando

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador persiste estado local).
- **Gates produzidos:** não é gate; é ponto de salvamento para continuidade entre sessões.
- **Output:** `project-state.json` + `docs/handoffs/handoff-YYYY-MM-DD-HHMM.md` + `docs/handoffs/latest.md`.
- **Schema formal:** `docs/schemas/project-state.schema.json` + template `docs/templates/handoff.md`.
- **Isolamento R3:** não aplicável.
- **Ordem no pipeline:** invocado ad hoc — antes de encerrar sessão, após marco, ou quando contexto aproxima limite.
