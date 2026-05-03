---
name: relatorio-semana
description: Gera relatório em pt-BR do que andou na semana — histórias entregues, ideias capturadas, tempo médio entre aprovação e aceite. Use no fim de semana ou quando Roldão pergunta "o que andou?", "como foi a semana?", "me dá um resumo".
allowed-tools: Bash, Read, Glob, Grep
---

# /relatorio-semana

Resumo semanal pro Roldão saber o que avançou — só pt-BR de produto, sem código nem terminal.

## Passos

### 1. Definir janela

Por padrão últimos 7 dias. Se argumento veio (`/relatorio-semana 14d` ou `/relatorio-semana mes`), ajustar.

### 2. Histórias entregues na janela

Listar arquivos em `docs/backlog/historias/feitas/AAAA-MM-DD-*.md` cuja data esteja na janela.

Pra cada uma, extrair título e a linha "O que o cliente vai ver".

### 3. Ideias capturadas na janela

Listar `docs/backlog/ideias/*.md` cujo "Capturada em:" esteja na janela.

### 4. Histórias aprovadas mas não aceitas ainda

Contar `docs/backlog/historias/ativas/*.md`.

### 5. Histórias aguardando aprovação

Contar `docs/backlog/historias/aguardando/*.md`.

### 6. Tempo médio (se possível)

Pra histórias entregues, calcular dias entre aprovação (data no histórico) e aceite (linha "Aceita em:"). Mostrar média.

### 7. Renderizar relatório

```
📊 Relatório da semana — <data início> a <data fim>

✓ Entregues ao cliente: <N> história(s)
  - <título 1> (<o que cliente vê>)
  - <título 2> (<o que cliente vê>)

⏳ Em andamento agora: <N> história(s)
  - <título 1>

📋 Aguardando aprovação: <N> história(s)
  - <título 1> (esperando há <N> dia(s))

💡 Ideias novas capturadas: <N>
  - <título 1>
  - <título 2>

⏱ Tempo médio entre aprovar e aceitar: <X> dias

Resumo: <1 frase em pt-BR de produto, ex: "semana boa — 3 entregas pro cliente
e 2 ideias novas. Tem 1 história esperando você aprovar há 4 dias.">
```

## Princípios

-   **Pt-BR de produto.** "Entregue ao cliente", não "merged"; "ideias capturadas", não "tickets created".
-   **Sem código.** Nunca colar log, commit hash, branch.
-   **Foco no que o cliente recebe.** Mesmo que tenha tido refactor interno, o relatório fala do que mudou pro cliente.
-   **Pendências visíveis.** Se algo está esperando o Roldão (aprovar, aceitar), destacar pra não perder.
