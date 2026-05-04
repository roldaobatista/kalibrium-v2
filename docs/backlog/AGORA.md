# Agora

> O que está sendo feito **agora**. No máximo 1 ou 2 histórias ao mesmo tempo. Atualizado automaticamente pela maestra.

## Em andamento

-   **Sync de fotos anexadas à OS** — terceira fatia do épico E16. **Código entregue (commit `b8030f3`), testes verdes**, mas revisor apontou 2 ajustes amarelos pendentes antes do aceite:
    1. `app/Http/Controllers/Mobile/SyncPhotoDownloadController.php` linha 34: trocar `withoutGlobalScopes()` (plural) por `withoutGlobalScope('current_tenant')` ou adicionar `->whereNull('deleted_at')` — atualmente foto soft-deletada ainda pode ser baixada se signed URL estiver dentro dos 30 min.
    2. Adicionar teste em `tests/Feature/Mobile/SyncPhotoTest.php` que acessa a rota de download com foto soft-deletada e espera 404; e teste com signed URL adulterada esperando 403.
-   E2e-aceite gerou roteiro em texto (sem prints — MCP Playwright não carregou nesta rodada). Próxima sessão pode regenerar com prints.

## Próxima da fila

_(vazio — após fechar os amarelos da história acima e Roldão aceitar, E16 está completo. Próximas frentes dependem de decisão dele.)_

---

**Como funciona:**

-   Quando uma história sai de `historias/aguardando/` e vai pra `historias/ativas/`, ela aparece aqui.
-   Quando o Roldão aceita, ela some daqui e vai pra `historias/feitas/`.
-   Se nada estiver aqui, é porque está esperando o Roldão decidir o que fazer.
