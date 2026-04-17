---
description: Skill de onboarding Dia 1 pro PM — mostra estado atual + decisões pendentes + menu de próximos passos em PT-BR. Use quando não souber o que fazer. Uso: /start.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit Cat C polishing + SK-006"
---

# /start

## Propósito

**Homepage conversacional** do PM. Resolve o gap "abri o Claude Code, olhei a tela em branco, e agora?".

**Resolve G-01 da auditoria de operabilidade PM 2026-04-12.**

## Uso

```
/start
```

Sem argumentos. É uma skill de orientação — não modifica nada.

## O que mostra

Em 4 seções:

1. **Boas-vindas** — 2-3 linhas em PT-BR
2. **Estado atual** — chama `scripts/where-am-i.sh` (reuso do G-10)
3. **Decisões de produto pendentes** — varre `docs/adr/*.md`, conta aceitos, lista pendentes (status `draft`/`proposed`)
4. **Menu de próximos passos** — lista de comandos agrupados por intenção:
   - 📋 Entender onde estou (`/where-am-i`, `/guide-check`)
   - 🚀 Começar algo novo (`/next-slice`, `/new-slice`)
   - 🏗️ Decidir tecnologia (`/decide-stack`, `/adr`)
   - 🔍 Continuar slice em andamento (`/verify-slice`, `/review-pr`, `/merge-slice`, `/explain-slice`)
   - 📊 Fechar ciclo (`/slice-report`, `/retrospective`)
5. **Dica** — adaptada ao estado:
   - Sem slice → recomenda `/next-slice` ou `/new-slice`
   - Com slice ativo → recomenda `/where-am-i` + próximo passo do slice

## Implementação

```bash
bash scripts/start.sh
```

Script 100% mecânico — só lê arquivos, nunca modifica. Agente principal lê o output e pode:
- Simplesmente apresentar ao PM (caso mais comum)
- Seguir automaticamente pro comando da dica se fizer sentido no contexto

## Quando usar

- **Dia 1** — primeira vez que o PM abre o projeto
- **Retomada após pausa longa** — PM voltou depois de dias/semanas
- **Confusão** — PM não sabe qual comando rodar
- **Onboarding de novo PM** (se um dia houver transição de dono)

## Complementa G-09 e G-10

- **G-09** (session-start automático) — 1-3 linhas no boot da sessão
- **G-10** (`/where-am-i`) — relatório full dos slices sob demanda
- **G-01** (`/start`) — orientação **conversacional** completa: estado + decisões + menu + dica

Os três resolvem diferentes momentos da jornada PM:
- Quero uma atualização rápida ao abrir → G-09 faz sozinho
- Quero o estado detalhado → `/where-am-i`
- Não sei nem o que perguntar → `/start`

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `scripts/where-am-i.sh` falha ou não existe | Degradar graciosamente: mostrar boas-vindas + menu sem a seção de estado detalhado. |
| Nenhum ADR encontrado em `docs/adr/` | Seção de decisões pendentes mostra "nenhuma decisão técnica registrada ainda". Sugerir `/decide-stack`. |
| Repositório em estado inconsistente (hooks falhando) | Alertar PM e sugerir `/guide-check` para diagnosticar o harness. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

Nenhuma — `/start` é o ponto de entrada universal. Funciona em qualquer estado do projeto.

## Handoff

Nenhum handoff automático — `/start` é puramente informativo. PM lê, decide, digita o próximo comando livremente.

Se o agente principal detectar que o PM está visivelmente perdido (ex.: respondeu "e agora?", "não sei"), pode disparar `/start` proativamente.

## Próximo passo

Sugestão dependente do estado:

- Projeto vazio → `/intake` ou `/decide-stack`
- Slice ativo → `/where-am-i NNN` para detalhe + próximo comando do slice
- ADR pendente → `/adr NNNN` ou aceitar rascunho existente
- Sem pista → menu completo fica visível

## Critério de saída (exit)

`/start` é idempotente e não muta estado. Considera-se "executado com sucesso" quando:
- Script `scripts/start.sh` retornou exit 0 (ou lógica equivalente inline)
- PM viu: boas-vindas + estado atual + decisões pendentes + menu + dica contextual

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador apresenta onboarding ao PM).
- **Gates produzidos:** não é gate; é ponto de entrada universal da skill-tree.
- **Output:** mensagem R12 no chat — 4 seções fixas + dica contextual.
- **Schema formal:** não aplicável (consome `/where-am-i` + varredura de ADRs).
- **Isolamento R3:** não aplicável.
- **Ordem no pipeline:** primeiro comando em Dia 1 ou após pausa longa; fallback universal para confusão.
