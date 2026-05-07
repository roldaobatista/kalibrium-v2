# Agora

> O que está sendo feito **agora**. No máximo 1 ou 2 histórias ao mesmo tempo. Atualizado automaticamente pela maestra.

## Em andamento

-   **Sync de fotos anexadas à OS** — terceira fatia do épico E16. **Ajustes amarelos resolvidos**, testes verdes:
    1. ✅ `SyncPhotoDownloadController` agora usa `withoutGlobalScope('current_tenant')` + `whereNull('deleted_at')` — foto soft-deletada não pode mais ser baixada.
    2. ✅ Testes adicionados: download de foto soft-deletada retorna 404; signed URL adulterada retorna 403.
-   E2e-aceite gerou roteiro em texto (sem prints — MCP Playwright não carregou nesta rodada). Próxima sessão pode regenerar com prints.

## Próxima da fila

_(vazio — após fechar os amarelos da história acima e Roldão aceitar, E16 está completo. Próximas frentes dependem de decisão dele.)_

---

**Como funciona:**

-   Quando uma história sai de `historias/aguardando/` e vai pra `historias/ativas/`, ela aparece aqui.
-   Quando o Roldão aceita, ela some daqui e vai pra `historias/feitas/`.
-   Se nada estiver aqui, é porque está esperando o Roldão decidir o que fazer.
