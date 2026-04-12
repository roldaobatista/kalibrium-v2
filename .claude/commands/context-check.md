---
description: Verifica saude do contexto da sessao e sugere checkpoint proativo quando necessario. Monitora sinais de contexto grande (muitas mensagens, compressao, sub-agents pesados). Uso: /context-check.
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
| Cenario | Acao |
|---------|------|
| `project-state.json` nao existe | Criar com estado minimo e continuar |
| Checkpoint falha ao salvar | Reportar erro e sugerir salvamento manual |
| Sessao muito curta (< 5 mensagens) | Informar que nao ha necessidade de checkpoint |

## Handoff
- Contexto saudavel → continuar trabalho normal
- Contexto degradado → `/checkpoint` e sugerir nova sessao
- Contexto critico → `/checkpoint` automatico
