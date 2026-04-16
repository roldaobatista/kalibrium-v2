---
description: Verifica saude do contexto da sessao e sugere checkpoint proativo quando necessario. Monitora sinais de contexto grande (muitas mensagens, compressao, sub-agents pesados). Uso: /context-check.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-004 + Cat C polishing"
---

# /context-check

## Uso
```
/context-check
```

## Por que existe
Sessoes longas causam compressao de contexto, perda de detalhes e drift silencioso. Este skill detecta sinais de degradacao e sugere checkpoint antes que o problema aconteca.

## Quando invocar
- Automaticamente apos sub-agents pesados (implementer, fixer)
- Quando o PM perceber respostas menos precisas
- Antes de iniciar tarefas complexas (novo slice, pipeline de gates)
- Periodicamente durante sessoes longas

## Pre-condicoes
- Nenhuma (sempre disponivel)

## O que faz

### 1. Verificar sinais de contexto grande
Checar indicadores de sessao longa:
- Numero de mensagens na conversa (acima de 40 = alerta)
- Sub-agents invocados na sessao (cada um consome budget)
- Arquivos lidos/editados (muitos = contexto poluido)
- Se houve compressao automatica de mensagens anteriores

### 2. Verificar estado salvo
- `project-state.json` existe e esta atualizado?
- Ultimo checkpoint: quando foi?
- Telemetria do slice ativo: consistente?

### 3. Emitir recomendacao

**Se contexto saudavel:**
```
Contexto da sessao esta saudavel.
- Mensagens: ~N
- Sub-agents usados: N
- Ultimo checkpoint: HH:MM

Pode continuar normalmente.
```

**Se contexto degradado:**
```
A sessao esta ficando longa. Recomendo:

1. Salvar o estado atual → /checkpoint
2. Abrir nova sessao
3. Retomar com → /resume

Isso garante que nenhum detalhe se perca.
Quer que eu salve o checkpoint agora?
```

**Se critico (compressao detectada):**
```
⚠️ O contexto da sessao ja foi comprimido.
Detalhes de mensagens antigas podem ter sido perdidos.

Acao imediata:
1. Vou salvar checkpoint agora
2. Por favor abra nova sessao e use /resume

[executa /checkpoint automaticamente]
```

## Agentes
Nenhum — executada pelo orquestrador.

## Erros e Recuperacao

Tabela completa de cenários e ações (Cat C — severidade explícita):

| Cenário | Severidade | Ação |
|---|---|---|
| `project-state.json` nao existe | S4 | Criar com estado minimo e continuar. Alertar PM. |
| Checkpoint falha ao salvar (disco/permissão) | S2 | Reportar erro ao PM. Sugerir salvamento manual antes de continuar qualquer trabalho. Bloquear sub-agents novos. |
| Sessao muito curta (< 5 mensagens) | S5 | Informar que nao ha necessidade de checkpoint. Encerrar com OK. |
| **Contexto >80% do limite** | S3 | Recomendar `/checkpoint` imediato + nova sessão via `/resume`. Avisar que detalhes finos podem começar a se perder. |
| **Contexto >90% do limite** | S2 | Ação automática: rodar `/checkpoint` sem perguntar. Instruir PM a abrir nova sessão. Bloquear invocação de sub-agents pesados (implementer, master-audit). |
| **Compactação automática detectada** | S2 | Detalhes anteriores foram comprimidos. Executar `/checkpoint` imediato. Alertar PM que a sessão deve ser reiniciada para não perder contexto. |
| **Sub-agent falhou silenciosamente** (output vazio, timeout) | S3 | Registrar incidente em `docs/incidents/subagent-failure-YYYY-MM-DD.md`. Não reinvocar automaticamente sem decisão PM. Salvar checkpoint. |
| **MCP desconectou durante operação** | S3 | Retomar fallback local se existir. Alertar PM. Sugerir `/mcp-check` após reconexão. |
| **session-start.sh falhou no boot** | S1 | Bloquear qualquer trabalho. Escalar PM imediatamente. Possível tampering — consultar `docs/incidents/` e `/guide-check`. |

## Output esperado no chat

Toda invocação emite uma das três mensagens abaixo (nunca output técnico cru):

**Caso saudável:**
```
Contexto saudável. Mensagens: N. Sub-agents usados: N. Último checkpoint: HH:MM.
Pode continuar normalmente.
```

**Caso degradado (>80%):**
```
A sessão está ficando longa. Recomendo salvar o estado e abrir nova sessão.
Próximo passo sugerido: /checkpoint → fechar sessão → /resume.
```

**Caso crítico (>90% ou compactação):**
```
O contexto já foi comprimido. Detalhes antigos podem ter sido perdidos.
Ação automática: salvei checkpoint agora. Por favor abra nova sessão e use /resume.
```

## Próximo passo

- Saudável → continuar trabalho normal
- Degradado → `/checkpoint` + sugerir nova sessão
- Crítico → `/checkpoint` automático + pedir nova sessão ao PM
- Tampering suspeito (session-start falhou) → parar tudo e escalar PM

## Handoff
- Contexto saudavel → continuar trabalho normal
- Contexto degradado → `/checkpoint` e sugerir nova sessao
- Contexto critico → `/checkpoint` automatico

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador inspeciona contexto + telemetria local).
- **Gates produzidos:** não é gate; é utilitário de higiene de sessão.
- **Output:** mensagem R12 no chat; `/checkpoint` automático quando crítico.
- **Schema formal:** não aplicável (não emite JSON estruturado — usa `/checkpoint` que já emite).
- **Isolamento R3:** não aplicável.
- **Ordem no pipeline:** invocado ad hoc ou automático após sub-agents pesados (implementer, master-audit, fixer).
