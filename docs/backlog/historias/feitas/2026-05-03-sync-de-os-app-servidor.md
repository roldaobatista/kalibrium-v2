# História: Ordem de serviço sincroniza entre app e servidor

> **Origem:** segunda fatia do épico E16 (sync engine). A primeira fatia provou o mecanismo com anotações livres. Esta fatia transporta a entidade real do dia-a-dia do técnico — Ordem de Serviço — pela mesma infra `sync_changes` já entregue.
>
> **Escopo de domínio enxuto:** esta história NÃO entrega o modelo completo de OS do PRD (com FKs para cliente, instrumento, equipamento, equipe, veículo, evidências). Entrega o **suficiente pra provar que o sync transporta OS de verdade**. Os relacionamentos com outras entidades virão em épicos de domínio futuros, conforme cada entidade tiver seu próprio sync.

## O que o cliente vai ver

Carlos (técnico) abre o app. Na tela inicial, além do card "Anotações", aparece um card novo:

**"Ordens de Serviço"** — lista das OS dele em ordem cronológica reversa. Cada item mostra: nome do cliente, descrição curta do instrumento, status colorido (recebido / em calibração / aguardando aprovação / concluído / cancelado), e indicador "⏳ Aguardando sincronizar" quando aplicável.

Botão "+ Nova OS" abre formulário com campos:

-   **Cliente** (texto livre — ex: "Acme Indústria Ltda")
-   **Instrumento** (texto livre — ex: "Paquímetro digital Mitutoyo 200mm")
-   **Status** (seletor: recebido / em calibração / aguardando aprovação / concluído / cancelado — começa em "recebido")
-   **Observações** (textarea livre)

Carlos pode tocar em qualquer OS pra abrir e editar os mesmos campos. Cada edição re-sincroniza.

**Online:** salva localmente E sobe pro servidor de imediato.
**Offline:** salva no SQLite local, indicador "⏳ Aguardando sincronizar" aparece. Quando voltar conexão, indicador some sozinho.

Marcelo (gerente), no painel web, na tela de cada técnico, ganha aba "Ordens de Serviço" mostrando o que Carlos andou registrando em campo. Marcelo só lê — não edita OS criada pelo técnico (no MVP). Lista mostra cliente, instrumento, status colorido, data da última atualização.

Se Carlos editar uma OS que ele mesmo criou, e Marcelo já tinha visto a versão antiga, na próxima sync do Marcelo a versão atualizada chega.

## Por que isso importa

1. **A primeira fatia do E16 provou o mecanismo com anotação descartável. Esta fatia prova com a entidade real do produto.** Se OS sincroniza bem, todas as outras entidades vão usar o mesmo padrão sem surpresa.

2. **Técnico em campo precisa registrar OS sem depender de internet.** Cliente em zona rural, prédio com sinal ruim, deslocamento entre cidades — sinal cai a toda hora. Sem fila local, ele perde mudanças.

3. **Sem OS no app, o produto móvel não tem razão de existir.** Anotação livre era prova de conceito. OS é o motivo do produto.

## Como saberemos que ficou pronto

1. **Tabela `service_orders`** existe no servidor com: `id` (UUID), `tenant_id`, `user_id` (técnico responsável), `client_name` (string, nullable false), `instrument_description` (string, nullable false), `status` (enum: `received`, `in_calibration`, `awaiting_approval`, `completed`, `cancelled`), `notes` (text, nullable), `created_at`, `updated_at`, `version` (incrementa a cada mudança), `last_modified_by_device`. Multi-tenant scoped via `ScopesToCurrentTenant`. Soft delete.

2. **Migration segura:** novas colunas, sem alterar tabelas existentes. Index em `(tenant_id, user_id)` e `(tenant_id, status)`.

3. **Endpoints `POST /api/mobile/sync/push` e `GET /api/mobile/sync/pull`** já existentes aceitam `entity_type = 'service_order'` e aplicam last-write-wins por campo, exatamente como já fazem com `note`. Nenhum endpoint novo. Reuso total da infra.

4. **Tabela local `service_orders` no SQLite criptografado** do app, mesma estrutura da remota + colunas `pending_sync` e `local_id` (ULID gerado offline antes de ter ID do servidor).

5. **`syncEngine.ts` registra `service_order` como entidade conhecida.** `recordChange('service_order', 'create' | 'update', payload)` funciona simétrico ao que já existe pra `note`.

6. **UI mobile:**

    - Card "Ordens de Serviço" na home, com contador de pendentes de sync.
    - Lista cronológica reversa, badge de status colorido (cores compatíveis com paleta atual).
    - Botão flutuante "+ Nova OS" abre form modal/sheet.
    - Tap em item abre tela de edição com mesmos campos.
    - Indicador "⏳ Aguardando sincronizar" por item quando `pending_sync = true`.
    - Form valida: cliente e instrumento não podem estar vazios. Status sempre tem valor default ("recebido").

7. **UI web do gerente:** rota `/technicians/{id}/service-orders` mostra tabela read-only das OS daquele técnico. Reaproveita layout master existente (mesmo padrão da aba "Anotações"). Colunas: cliente, instrumento, status (badge colorido), última atualização. Sem ação de editar/excluir nesta história.

8. **Multi-tenant rígido:** OS do tenant A jamais aparece em pull do tenant B. Todas as queries escopadas. Policy `ServiceOrderPolicy` autoriza `view`/`update` apenas para o dono ou gerente do mesmo tenant.

9. **Conflito real (raro):** dois dispositivos do mesmo técnico editam a mesma OS offline. O que tiver `updated_at` maior vence por campo. Audit log em `sync_changes` registra `payload_before` descartado.

10. **Testes Pest cobrem:**

    - Push de OS nova cria registro corretamente, incrementa `version`.
    - Push de update com `updated_at` mais antigo é descartado.
    - Pull com cursor retorna apenas mudanças posteriores, escopado pelo tenant.
    - Multi-tenant: push de OS do tenant A não aparece em pull do tenant B.
    - Multi-tenant: técnico do tenant A não consegue puxar OS criada por técnico do tenant B (mesmo via hijack de payload).
    - Form valida campos obrigatórios.
    - UI Livewire do gerente filtra corretamente por técnico.

11. **Robô Playwright cobre o caminho feliz:** técnico cria OS no app, fica online, sobe pro servidor. Gerente abre painel, vê a OS na aba do técnico. Técnico edita status. Gerente atualiza, vê novo status.

## Fora do escopo desta história

-   **Vincular OS a cliente real (FK), instrumento real, equipamento real, equipe real, veículo real.** Tudo texto livre nesta história. FKs entram quando essas entidades também tiverem sync.
-   **Foto/anexo na OS.** Próxima história do E16.
-   **Gerente edita OS pelo painel.** No MVP só lê.
-   **Workflow de aprovação multi-etapa.** Status simples sequencial nesta fatia.
-   **Cálculo de SLA, prazos, alertas.** Vira história futura de produto.
-   **Geração de certificado.** Outro épico.
-   **Exclusão de OS.** Soft delete existe na tabela mas UI não expõe ação de excluir nesta história.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita
