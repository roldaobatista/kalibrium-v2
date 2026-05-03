---
name: em-que-pe
description: Mostra ao Roldão em pt-BR o estado atual do trabalho — qual história está ativa, quantas estão aguardando, últimas feitas, alertas (testes vermelhos, mudanças não salvas). Resposta em formato de painel curto, sem código, sem terminal.
---

# /em-que-pe

Esta skill consolida em **um painel curto** o estado atual do trabalho pra o Roldão saber rapidamente onde estamos.

## Passos

### 1. Ler o que está ativo

- Ler `docs/backlog/AGORA.md`
- Listar arquivos em `docs/backlog/historias/ativas/`

### 2. Contar o que está esperando

- Listar arquivos em `docs/backlog/historias/aguardando/` (ordem alfabética)
- Listar arquivos em `docs/backlog/ideias/` que ainda não viraram história (status `[ ] virou história`)

### 3. Listar feitas recentes

- Últimas 5 histórias em `docs/backlog/historias/feitas/` (por data no nome do arquivo)

### 4. Checar saúde do código (rápido)

- `git status --short` (mudanças não salvas)
- Última saída de teste (se houver — não rodar de novo aqui, só ler `.phpunit.cache/` ou último log se existir)

### 5. Renderizar o painel em pt-BR

Formato esperado:

```
📍 Em que pé está

▸ Agora: <título da história ativa> (etapa: <etapa>)
  ou: nada em andamento

▸ Aguardando aprovação: <N> histórias prontas pra começar
  - <título 1>
  - <título 2>

▸ Ideias capturadas (não viraram história ainda): <N>
  - <ideia 1>

▸ Feitas recentemente:
  - <data> — <título>
  - <data> — <título>

▸ Mudanças não salvas: <N arquivos>
  ou: tudo salvo

▸ Alertas:
  - (nenhum)
  ou listar problemas em pt-BR (ex: "1 teste falhando na tela do financeiro")
```

## Princípios

- **Tudo em pt-BR.** Nunca "branch atual", "uncommitted", "test failure" — usar "ramo de trabalho", "mudanças não salvas", "1 teste falhando".
- **Painel curto.** Máximo 15-20 linhas no total. Se algo passar disso, resumir com contagem.
- **Sem terminal/log/stack trace.** Se um teste estiver vermelho, dizer **qual tela ou ação não funciona**, não o erro técnico.
- **Atualizar AGORA.md se estiver desatualizado** — se você notar que a história ativa já foi pra `feitas/` mas AGORA.md não foi atualizado, conserte.
