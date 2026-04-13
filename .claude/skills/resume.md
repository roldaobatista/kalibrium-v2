---
description: Restaura contexto da sessao anterior e continua do ponto exato onde parou. Le project-state.json + ultimo handoff + telemetria para reconstruir estado. Uso: /resume.
---

# /resume

## Uso
```
/resume
```

## Por que existe
Sessoes de Claude Code tem contexto limitado. Ao abrir uma nova sessao, todo o contexto anterior se perde. Esta skill reconstroi o estado minimo necessario para continuar exatamente de onde parou, sem o PM precisar explicar tudo de novo.

## Quando invocar
No inicio de qualquer sessao que continua trabalho anterior. Complementa o SessionStart hook.

## Pre-condicoes
- Pelo menos um de:
  - `project-state.json` existe
  - `docs/handoffs/` tem pelo menos um arquivo
  - `.claude/telemetry/` tem dados

## O que faz

### 1. Reconstruir contexto

Ler na seguinte ordem de prioridade:
1. `project-state.json` — estado canonico
2. `docs/handoffs/latest.md` — ultimo handoff
3. `.claude/telemetry/` — ultimo slice com atividade
4. `git log --oneline -10` — commits recentes
5. `git status` — working tree

### 2. Carregar arquivos relevantes

Com base no estado:
- Se em fase de **discovery**: carregar intake-responses, PRD, NFR
- Se em fase de **strategy**: carregar ADRs, threat model, architecture
- Se em fase de **planning**: carregar ROADMAP, epic e stories atuais
- Se em fase de **execution**: carregar spec, plan, tasks, ultimo verification/review
- Sempre: constitution.md, CLAUDE.md

### 3. Apresentar resumo ao PM

```
🔄 Sessao restaurada

Ultima sessao: <data do ultimo handoff>
Fase: <fase atual>

O que foi feito:
- <resumo 1>
- <resumo 2>

Onde paramos:
- <estado especifico>

Pendencias:
- <pendencia 1>
- <pendencia 2>

Proxima acao: <acao clara>

Quer continuar de onde paramos? (sim / quero fazer outra coisa)
```

### 4. Se nao houver estado
```
🔄 Nao encontrei estado anterior persistido.

Opcoes:
1. /intake — comecar o projeto do zero
2. /status — ver o que existe no repositorio
3. Me diga o que voce quer fazer e eu descubro o contexto
```

### 5. Validar consistencia
- Se `project-state.json` diz slice ativo mas `git status` mostra working tree limpo → alertar
- Se ultimo handoff menciona bloqueio que pode ter sido resolvido → verificar
- Se telemetria mostra rejeicao R6 → alertar PM sobre escalacao pendente

## Implementacao

Sequencia de leitura:
```
1. Read project-state.json
2. Read docs/handoffs/latest.md (ou mais recente em docs/handoffs/)
3. git log --oneline -10
4. git status
5. Glob specs/*/spec.md (slices existentes)
6. Carregar arquivos da fase atual
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `project-state.json` corrompido ou com JSON inválido | Reconstruir estado a partir de `docs/handoffs/`, telemetria e `git log`. Alertar PM. |
| Nenhum estado anterior encontrado (nenhuma das 3 fontes existe) | Apresentar opções: `/intake` (projeto novo), `/status` (explorar repo), ou pedir contexto ao PM. |
| Estado inconsistente (project-state diz slice ativo mas working tree limpo) | Alertar PM sobre a divergência e pedir decisão: retomar slice ou marcar como concluído. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- `project-state.json` existe (ou pelo menos `docs/handoffs/` ou `.claude/telemetry/` com dados).

## Handoff
- PM quer continuar → retomar exatamente da proxima acao
- PM quer fazer outra coisa → ajustar e sugerir skill adequada
- Inconsistencia detectada → alertar PM e pedir decisao
