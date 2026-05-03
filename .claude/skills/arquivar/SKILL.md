---
name: arquivar
description: Move uma história aceita de ativas/ pra feitas/, atualiza AGORA.md e ROADMAP.md. Use depois do aceite + deploy, quando o trabalho está 100% concluído. Roldão diz "arquiva essa", "pode fechar a história X", "tá no servidor, fecha".
disable-model-invocation: true
---

# /arquivar

Quando uma história está aceita E foi pro servidor (ou foi decidido que não precisa subir), esta skill arruma a casa.

## Passos

### 1. Identificar a história

Se argumento veio (slug), pegar. Senão listar `docs/backlog/historias/ativas/*.md` que tenham `- [x] aceita` e perguntar qual.

### 2. Validar que está aceita

Ler o arquivo. Se não tem `- [x] aceita`, **bloquear** e dizer:

> "Essa história ainda não foi aceita por você. Antes de arquivar, rode `/aceitar` (depois de ver o roteiro de aceite com imagens)."

### 3. Mover pra feitas/ com data

Renomear pra `docs/backlog/historias/feitas/AAAA-MM-DD-<slug>.md`:

`mv docs/backlog/historias/ativas/<slug>.md docs/backlog/historias/feitas/$(date +%Y-%m-%d)-<slug>.md`

### 4. Atualizar AGORA.md

Tirar a história da seção "Em andamento". Se houver próxima na fila (em `aguardando/`), perguntar ao Roldão se quer aprovar ela agora.

### 5. Atualizar ROADMAP.md (se aplicável)

Se a história estava marcada num épico (`docs/backlog/epicos/`), marcar como concluída lá também.

### 6. Resumo final pro Roldão

```
✓ História "<título>" arquivada.
  - Foi pra feitas/ com data <AAAA-MM-DD>
  - AGORA.md atualizado
  - <X> dias entre aprovação e aceite

Próximo passo:
  - Tem <N> história(s) aguardando aprovação. Quer ver?
  - ou: nada na fila, posso seguir com captura de novas ideias.
```

## Princípios

-   **Não arquiva o que não foi aceito.** Bloqueio firme.
-   **Sempre data no nome do arquivo arquivado.** Facilita auditoria histórica e relatórios semanais.
-   **Manter ROADMAP/AGORA em dia automaticamente.** Roldão não precisa mexer em arquivo nenhum manualmente.
