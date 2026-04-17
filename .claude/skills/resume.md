---
description: Restaura contexto da sessao anterior e continua do ponto exato onde parou. Le project-state.json + ultimo handoff + telemetria para reconstruir estado. Uso: /resume.
protocol_version: "1.2.4"
changelog:
  - "2026-04-16 — quality audit Cat C polishing"
  - "2026-04-16 — ADR-0017 Mudanca 3: reconcile-project-state.sh chamado apos carregar state (detecta drift entre project-state.json e git antes de o PM continuar)"
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

### 1b. Reconciliar project-state contra git (ADR-0017 Mudanca 3)

```bash
bash scripts/reconcile-project-state.sh --verbose
```

- **exit 0:** estado consistente, prosseguir
- **exit 1:** drift bloqueante detectado — apresentar ao PM via R12:
  > "Detectei que o estado do projeto diverge do que git mostra. Antes de continuar, preciso que voce confirme: [lista de divergencias em PT-BR]. Detalhe tecnico em `docs/audits/project-state-reconcile-*.json`."
- **exit 2:** pre-requisito faltando (json invalido, schema ausente) — abortar /resume, reportar incidente
- Drift informativo (avisos sem bloqueio): registrar em logs mas prosseguir

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

## Próximo passo

- Estado restaurado → seguir `next_action` do handoff (exibido ao PM em R12)
- Nenhum estado → `/start` para onboarding ou `/intake` se projeto novo
- Inconsistência → decisão PM antes de qualquer outro comando

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador lê artefatos persistidos).
- **Gates produzidos:** não é gate; é restauração de contexto de sessão.
- **Output:** mensagem R12 no chat com resumo, pendências e próxima ação.
- **Schema formal:** consome `project-state.json` (schema em `docs/schemas/`) e handoffs.
- **Isolamento R3:** não aplicável.
- **Ordem no pipeline:** primeiro comando de toda sessão que continua trabalho anterior.
