# ADR-0017 — Sync Engine offline-first

**Status:** accepted
**Data:** 2026-05-03
**Autor:** roldao-tecnico (PM) + Co-Authored-By Claude

> **Nota de numeração:** ADR-0015 reserva o número 0016 pra "Sync Engine", mas o número 0016 já foi usado por "Multi-tenant Isolation". Esta ADR ocupa a posição 0017 mantendo o conteúdo originalmente previsto pra 0016.

**Complementa:** ADR-0015 (escolha do stack PWA + Capacitor) — esta ADR define como o cliente offline-first sincroniza com o backend Laravel + PostgreSQL.

---

## Contexto

ADR-0015 escolheu PWA + Capacitor como stack cliente offline-first, com SQLite + SQLCipher como banco local. Faltou definir **como** os dados locais sincronizam com o backend Laravel + PostgreSQL.

Cenário-alvo do MVP:

-   **Janela offline esperada:** até 4 dias (técnico em zona rural).
-   **Volume por dispositivo:** 50-200 OS ativas, 500-2000 clientes do laboratório, ~5000 instrumentos cadastrados, fotos de evidência (média 1-3 MB cada).
-   **Padrão de mudança offline:** técnico edita campos de OS, marca status, tira fotos, registra despesa, anota leitura de calibração.
-   **Conflitos previstos:** raros (técnico A não trabalha na mesma OS do técnico B simultaneamente). Quando ocorrerem (ex: gerente edita OS no painel enquanto técnico edita no campo), resolver por **last-write-wins por campo** com log de auditoria.
-   **Volume estimado de sync por sessão:** dezenas de mudanças, raramente centenas.

A escolha do sync engine afeta:

-   Custo financeiro (alguns SaaS de sync cobram por dispositivo conectado).
-   Velocidade de implementação (do simples ao complexo).
-   Manutenibilidade no longo prazo.
-   Capacidade de evoluir pra requisitos avançados (CRDT, real-time, presence).

## Opções consideradas

### Opção A — PowerSync (SaaS pago, sync engine maduro)

[https://www.powersync.com](https://www.powersync.com) — escolha mais sofisticada do mercado pra Postgres + cliente offline-first.

**Como funciona:** PowerSync mantém uma conexão WebSocket entre cliente (SQLite local) e servidor PostgreSQL. Replica seletivamente, com regras configuráveis por dispositivo. Resolve conflitos via CRDTs.

**Prós:**

-   Sync engine maduro, usado em produção por terceiros.
-   Real-time bidirecional out-of-the-box.
-   Resolve conflitos com CRDT.
-   SDK Capacitor oficial.
-   Equipe especializada por trás (suporte profissional).

**Contras:**

-   **Custo:** plano gratuito limita 1.000 conexões concorrentes; planos pagos começam em ~US$ 200/mês.
-   **Vendor lock-in:** sair de PowerSync depois exige reescrever a camada de sync.
-   **Complexidade:** requer entender configuração de regras de replicação, CRDTs, etc.
-   **Setup inicial:** conta + integração com Postgres + permissões.

**Custo estimado pro MVP:** plano gratuito atende. Quando primeiro cliente passar de ~50 técnicos sincronizando ao mesmo tempo, migra pra plano pago.

**Custo de migração futura (de outra solução pra PowerSync):** médio (precisa adaptar protocolo de sync no app + ativar WebSocket no backend). Estimado em 1-2 sprints quando justificar.

### Opção B — ElectricSQL (open source, sync engine novo)

[https://electric-sql.com](https://electric-sql.com) — alternativa open source, em rápido desenvolvimento.

**Prós:**

-   Open source (sem custo de licença).
-   Postgres como source of truth.
-   CRDT por padrão.
-   Sem vendor lock-in.

**Contras:**

-   **Maturidade:** projeto jovem, ainda em alpha/beta em algumas features. API muda entre versões.
-   **Comunidade pequena:** suporte depende de issues no GitHub.
-   **Setup operacional:** precisa rodar processo extra (Electric Service) na infra.
-   **SDK Capacitor:** menos polido que o de PowerSync.

**Custo estimado pro MVP:** zero de licença. Custo de infra extra (rodar Electric Service em VPS modesta) ~US$ 10-20/mês.

### Opção C — Custom REST sync com last-write-wins por campo (escolhida)

Implementação caseira: endpoints `/api/mobile/sync/pull` e `/api/mobile/sync/push` que orquestram o sync via JSON simples.

**Como funciona:**

1. **Pull:** app envia `last_sync_cursor` (timestamp + lista de IDs já vistos). Servidor responde com mudanças desde o cursor, paginadas por entidade.
2. **Push:** app envia lote de mudanças locais (`{entity, id, version, fields_changed, updated_at}`). Servidor aplica last-write-wins por campo (compara `updated_at` da mudança vs `updated_at` do registro no banco).
3. **Conflitos:** detectados quando duas versões tocaram o mesmo campo. Resolução automática por timestamp; campo perdedor entra no audit log com nota "sobreescrito por sync de {device}".
4. **Auth:** token Sanctum atual + header `X-Device-Id` (mesmo padrão das outras rotas mobile).
5. **Multi-tenant:** todas as queries escopadas pelo tenant ativo no token.
6. **Outbox no servidor:** tabela `sync_changes` registra cada mudança com tenant_id, entity, action, before, after, source_device, applied_at — vira histórico auditável e fonte pro pull dos outros devices.
7. **Outbox local:** cliente tem tabela `sync_outbox` no SQLite local com mudanças pendentes. Reenvio automático com backoff quando offline.

**Prós:**

-   Zero custo de licença.
-   Zero vendor lock-in.
-   Código entendível por qualquer dev backend Laravel.
-   Cobre 100% dos requisitos do MVP (4 dias offline, last-write-wins, audit log).
-   Migração futura pra PowerSync/Electric continua possível (basta mudar a camada cliente do sync — backend continua sendo a fonte da verdade).
-   Permite começar a entregar features de OS, foto, despesa **agora**, sem bloquear em PoC enorme.

**Contras:**

-   Sem real-time bidirecional (técnico só vê mudanças do gerente quando puxar — aceitável pro MVP).
-   Last-write-wins por campo é solução simples — casos avançados de merge (texto longo, listas de itens) podem precisar de evolução futura.
-   Implementação inicial = 1 sprint de trabalho focado.

**Custo estimado pro MVP:** zero de licença. Custo de dev = ~5-7 dias.

## Decisão

**Opção C — Custom REST sync com last-write-wins por campo.**

### Justificativa

1. **MVP precisa entregar valor antes de otimizar.** Sem sync, qualquer história de OS/foto/despesa fica bloqueada esperando decisão arquitetural perfeita. Custom REST destrava o caminho hoje.
2. **Cenário de uso atual não exige real-time.** Técnico em campo trabalha sozinho na OS dele. Real-time entre múltiplos técnicos editando a mesma OS é cenário futuro, não atual.
3. **Custo zero.** PowerSync vira opção quando justificar dinheiro do cliente. Hoje, 100 técnicos no MVP cabem confortavelmente em sync custom.
4. **Reversibilidade.** Migrar pra PowerSync depois é caro mas possível. O backend Laravel continua sendo source of truth — só a camada cliente muda.
5. **Aprendizado.** Construir o sync próprio força a equipe a entender o problema. Quando migrar pra solução pronta, decisão será informada.

### Detalhes operacionais

-   **Cursor de sync:** ULID (combina timestamp + entropia). Cada mudança no servidor gera um ULID único; cliente guarda último ULID visto.
-   **Conflito real (mesmo campo, mesmo timestamp):** decidir por device ID (lexicográfico). Caso de borda raro mas determinístico.
-   **Tamanho do lote:** push até 100 mudanças por request, pull até 200 mudanças por response. Cliente pagina automaticamente.
-   **Prioridade:** mudanças de status > mudanças de campo > anexos.
-   **Anexos (fotos):** sync separado via upload pré-assinado (S3 ou storage Laravel). URL fica no payload; bytes vão no canal próprio.
-   **Retry:** exponential backoff client-side (1s, 2s, 4s, 8s, max 60s) com jitter.

## Consequências

### Positivas

-   Time pode começar a entregar histórias de OS/foto/despesa imediatamente.
-   Sem dependência de terceiro pago.
-   Código no repo, debugável, evoluível.
-   Multi-tenant isolation continua sob controle (regra existente do projeto se aplica direto).

### Negativas

-   Algumas features futuras (real-time, presence multi-user) vão exigir migração.
-   Manutenção do código de sync recai no time interno.
-   Casos avançados de merge (lista de items numa OS) podem exigir lógica custom.

### Riscos

-   **Técnico edita mesmo campo que gerente — perde mudança sem perceber.** Mitigação: audit log preserva valor descartado, gerente pode reverter manualmente. UI futura mostra alerta "esta OS foi alterada offline e por outro usuário" pra casos críticos.
-   **Volume de mudanças cresce além do esperado.** Mitigação: medição contínua. Quando primeiro cliente passar de N mudanças/minuto, reavaliar. Migração pra PowerSync vira projeto.
-   **Anexos perdidos por upload falho offline.** Mitigação: outbox local de anexos + retry com backoff + flag "sincronizar quando voltar conexão" visível pro técnico.

## Quando reavaliar

Reabrir esta ADR quando qualquer um dos gatilhos abaixo acontecer:

1. Primeiro cliente real passar de **50 técnicos sincronizando simultaneamente**.
2. Aparecer requisito de **real-time bidirecional** (ex: gerente vê em tempo real o que o técnico está editando).
3. Aparecer requisito de **multi-usuário editando o mesmo registro** com merge não-trivial (ex: dois técnicos editando a mesma OS).
4. Custo de manutenção do sync custom passar de **2 dias por mês** em ajustes recorrentes.

Em qualquer um desses gatilhos, fazer PoC sério de PowerSync (primeira opção) ou ElectricSQL (segunda) e migrar a camada cliente.

## Referências

-   ADR-0015 — Stack offline-first.
-   PowerSync docs: https://docs.powersync.com (verificado 2026-05-03)
-   ElectricSQL docs: https://electric-sql.com/docs (verificado 2026-05-03)
-   Last-write-wins per field — [pattern documentation]: comum em sistemas distribuídos com baixa contenção (ex: CouchDB, Realm).
