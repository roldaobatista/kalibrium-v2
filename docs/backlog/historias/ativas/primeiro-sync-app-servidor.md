# História: Primeiro sync entre app e servidor

> **Origem:** abertura do épico E16 (sync engine). Decisão arquitetural registrada em **ADR-0017** — sync custom REST com last-write-wins por campo. Esta história entrega a infra de sync (servidor + cliente) sem ainda ter entidades reais sincronizando — funciona com uma entidade-piloto simples (ex: "anotação rápida do técnico") pra provar o caminho.

## O que o cliente vai ver

Carlos (técnico) abre o app. Na tela inicial, há agora um card novo:

**"Anotações"** — espaço pra ele jogar lembretes rápidos do dia ("ligar João da Acme", "buscar peça no almoxarifado"). Botão "+ Nova anotação" abre form simples (título + texto livre). Cada anotação aparece numa lista cronológica.

Se Carlos está **online**: anotação salva localmente E sobe pro servidor de imediato.

Se Carlos está **offline**: anotação salva no SQLite local. Aparece com indicador discreto "⏳ Aguardando sincronizar". Quando voltar conexão, indicador some sozinho.

Marcelo (gerente), no painel web, na tela de cada técnico, tem uma aba "Anotações" mostrando o que Carlos andou anotando. Marcelo só lê — não edita anotações de outros.

Se Carlos editar uma anotação que ele mesmo criou, e Marcelo já tinha visto a versão antiga, na próxima sync do Marcelo a versão atualizada chega.

Se a conexão quebrar no meio do upload, Carlos não perde nada — fila local guarda as mudanças e o app retenta sozinho.

## Por que isso importa

1. **Sem sync funcionando, o app móvel é só telinha.** Login, biometria, criptografia local — tudo isso está pronto. Mas se nada vai pro servidor, não tem produto.

2. **Anotação é entidade simples e descartável pra provar o caminho.** Usar OS de verdade pra estrear o sync seria arriscado — dados reais, regras complexas, integrações fiscais. Anotação livre é seguro: campo de texto, sem regras de negócio, fácil de validar.

3. **Construir essa infra agora destrava todas as próximas histórias.** Lista de OS, foto, despesa, certificado — todas vão usar exatamente o mesmo mecanismo de pull/push. Investir bem aqui acelera o resto.

4. **Fila local + retry resolvem o cenário real.** Técnico em zona rural perde sinal a cada 10 minutos. Sem fila, ele perde mudanças. Com fila, ele só vê "aguardando sincronizar" e o app cuida sozinho.

## Como saberemos que ficou pronto

1. **Tabela `notes`** existe no servidor com `id`, `tenant_id`, `user_id`, `title`, `body`, `created_at`, `updated_at`, `version` (incrementa a cada mudança), `last_modified_by_device`. Multi-tenant scoped via `ScopesToCurrentTenant`.

2. **Tabela `sync_changes`** existe no servidor — outbox global. Cada mudança de qualquer entidade (anotação ou futuras) registra: `ulid`, `tenant_id`, `entity_type`, `entity_id`, `action` (create/update/delete), `payload_before`, `payload_after`, `source_device_id`, `applied_at`.

3. **Endpoint `POST /api/mobile/sync/push`** aceita lote de até 100 mudanças. Aplica cada uma com last-write-wins por campo (compara `updated_at` da mudança vs `updated_at` no banco). Retorna lista de mudanças aplicadas (com novos ULIDs) + lista de mudanças rejeitadas (com motivo).

4. **Endpoint `GET /api/mobile/sync/pull?cursor={ulid}`** retorna mudanças desde o cursor, escopadas pelo tenant + user (técnico só vê o que ele criou; futuro: também o que foi atribuído a ele). Paginado, máximo 200 por response. Inclui `next_cursor` na resposta.

5. **Auth + middleware:** ambas as rotas usam `auth:sanctum` + `mobile.device.status` (mesmo padrão das outras rotas mobile).

6. **Tabela local `notes` no SQLite criptografado** do app, com mesma estrutura da remota + colunas `pending_sync` (bool) e `local_id` (ULID gerado offline antes de ter ID do servidor).

7. **Tabela local `sync_outbox`** no SQLite com mudanças pendentes pra subir.

8. **Service `syncEngine.ts` no app:**

    - `recordChange(entity, action, payload)` — adiciona no outbox local.
    - `flushOutbox()` — envia outbox pendente pro `/sync/push`. Backoff exponencial em falha.
    - `pull()` — chama `/sync/pull` com cursor salvo, aplica mudanças no SQLite local.
    - `start()` — inicia loop: a cada 30s online, chama flush + pull. Reage a `online`/`offline` events.

9. **UI da anotação:** botão "+ Nova anotação" abre modal/sheet com campo título + textarea. Salvar chama `syncEngine.recordChange('note', 'create', {...})` que escreve no SQLite local + outbox + dispara flush.

10. **Indicador "⏳ Aguardando sincronizar"** aparece em cada anotação cuja `pending_sync = true`. Some quando confirmado.

11. **Painel web do gerente:** rota `/technicians/{id}/notes` mostra lista de anotações daquele técnico (read-only). Reaproveita layout master existente.

12. **Multi-tenant:** anotação criada no tenant A é invisível pro tenant B. Todas as queries escopadas.

13. **Conflito real (raro):** se técnico edita anotação enquanto gerente edita do painel, o que tiver `updated_at` maior vence. Audit log registra valor descartado.

14. **Testes Pest cobrem:**
    - Push de mudança nova cria registro.
    - Push de mudança com `updated_at` mais antigo é descartado (last-write-wins).
    - Pull com cursor retorna apenas mudanças posteriores.
    - Multi-tenant: push de mudança de tenant A não aparece em pull do tenant B.
    - Outbox local tem retry em falha de rede.

## Fora do escopo desta história

-   **Sync de fotos/anexos** — vira história separada (anexos exigem upload pré-assinado, fluxo diferente).
-   **Sync de OS de verdade** — OS exige modelo + regras de negócio + UI complexa. Vira história separada de domínio depois que o sync estiver provado com anotação.
-   **Real-time bidirecional** — ADR-0017 deixa claro que isso só vem se justificar no futuro.
-   **Resolução manual de conflito pelo usuário** — automático por last-write-wins. UI de conflito vira história futura se aparecer demanda.
-   **Push notification quando sincroniza** — vira história futura.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita
