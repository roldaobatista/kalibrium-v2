# História: Foto anexada à Ordem de Serviço sobe pelo sync

> **Origem:** terceira e última fatia do épico E16. Fecha o épico de sync app↔servidor entregando o transporte de **anexo binário** (foto), que é caminho diferente de mudança JSON: arquivo grande não cabe em `sync_changes`, precisa upload separado com referência cruzada.

## O que o cliente vai ver

Carlos (técnico) abre uma Ordem de Serviço já criada. No final do form, há agora uma seção:

**"Fotos do serviço"** — grade de miniaturas das fotos já anexadas. Botão "+ Adicionar foto" abre câmera do celular ou galeria. Carlos tira uma foto do equipamento, do certificado físico, do lacre, da assinatura do cliente — qualquer evidência que ele queira registrar.

Cada foto recém-anexada aparece imediatamente na grade com indicador "⏳ Enviando" no canto. Quando termina de subir, indicador some. Se Carlos está offline, foto fica salva no celular e indicador permanece — sobe sozinha quando voltar conexão.

Cada foto pode ser tocada pra ver em tela cheia. Carlos pode remover foto que ele mesmo anexou, com confirmação ("Remover esta foto?").

Marcelo (gerente), na tela de OS no painel web, vê a mesma grade de fotos. Pode clicar pra ampliar. Não pode adicionar nem remover (no MVP só lê).

## Por que isso importa

1. **Calibração sem evidência fotográfica não vale nada na prática.** Cliente exige foto do equipamento antes/depois, lacre, certificado físico assinado. Sem isso, calibração é palavra contra palavra.

2. **Foto é caminho diferente do JSON do sync.** Resolver agora estabelece o padrão pra todos os anexos futuros (PDF de certificado, áudio de instrução, vídeo de procedimento). Investir bem aqui destrava muita coisa adiante.

3. **Técnico tira foto em zona sem sinal o tempo todo.** Sem fila local de upload, foto se perde quando ele esquece do celular no bolso e bateria acaba.

## Como saberemos que ficou pronto

1. **Tabela `service_order_photos`** existe no servidor com: `id` (UUID), `tenant_id`, `service_order_id` (FK), `user_id` (quem anexou), `disk` (string — qual filesystem), `path` (string — caminho dentro do disk), `original_filename`, `mime_type`, `size_bytes`, `uploaded_at`, `created_at`, `updated_at`, `version`, `last_modified_by_device`. Soft delete. Multi-tenant scoped.

2. **Migration segura:** nova tabela, sem mexer em `service_orders`. Index em `(tenant_id, service_order_id)`.

3. **Endpoint `POST /api/mobile/sync/upload-photo`** aceita multipart: `service_order_local_id` ou `service_order_id`, `photo` (arquivo), `client_uuid` (ULID gerado no app). Salva no disk privado configurado em `config/filesystems.php`. Cria registro `service_order_photos`. Retorna `{ id, server_id, url_signed_get }`. Limite: 8 MB por foto, mime aceito: `image/jpeg`, `image/png`, `image/webp`. Auth: `auth:sanctum` + `mobile.device.status`.

4. **Endpoint `GET /api/mobile/sync/photo/{id}/signed-url`** retorna URL temporária assinada pra baixar a foto (validade 30 min). Escopado pelo tenant + autorização do user (técnico só vê foto da própria OS; gerente vê foto de OS de técnicos do tenant dele).

5. **Mudança em `sync_changes`:** quando foto é anexada, registra `entity_type = 'service_order_photo'`, `action = 'create'`, `payload_after` com metadados (sem o binário). Pull entrega esses registros normalmente. Cliente baixa binário sob demanda via signed URL.

6. **Tabela local `service_order_photos` no SQLite** do app, com colunas: `local_id` (ULID), `server_id` (UUID quando subiu), `service_order_local_id`, `service_order_server_id`, `local_path` (caminho do arquivo no storage privado do app), `pending_upload` (bool), `mime_type`, `size_bytes`, `created_at`.

7. **`syncEngine.ts` ganha fila de upload separada (`uploadOutbox`)** porque arquivo binário não cabe no payload JSON. Loop online: primeiro flush de `sync_outbox` (mudanças JSON), depois flush de `uploadOutbox` (envia fotos pendentes uma a uma com retry exponencial).

8. **UI mobile (form de OS):**

    - Seção "Fotos do serviço" abaixo dos campos.
    - Grade de miniaturas (3 por linha).
    - Tap em "+ Adicionar foto" abre seletor: câmera ou galeria.
    - Indicador "⏳ Enviando" sobre miniatura quando `pending_upload = true`.
    - Tap em miniatura abre viewer fullscreen.
    - Long-press ou botão lixeira abre confirmação "Remover esta foto?".

9. **UI web do gerente:** na tela `/technicians/{id}/service-orders` (e/ou em rota de detalhe da OS), grade de miniaturas read-only, clicar amplia em modal. Foto carregada via signed URL.

10. **Limites e segurança:**

    - Tamanho máximo 8 MB por foto (validar no servidor; reapertar no app).
    - Tipo aceito: imagem comum (jpeg/png/webp).
    - Foto privada: nunca acessível por URL pública, sempre signed URL.
    - Multi-tenant rígido: foto do tenant A não acessível por user do tenant B mesmo com ID conhecido.

11. **Testes Pest cobrem:**

    - Upload feliz cria registro, salva arquivo no disk, retorna URL assinada.
    - Upload de foto >8 MB é rejeitado com 413.
    - Upload de mime não aceito rejeitado com 422.
    - Multi-tenant: user do tenant B não consegue baixar foto do tenant A nem com ID válido.
    - Signed URL expira em 30 min (mockar tempo).
    - Soft-delete de foto: registro fica, arquivo permanece (preserva auditoria) — só esconde da UI.

12. **Robô Playwright cobre caminho feliz:** técnico abre OS existente no app, anexa foto (mock de câmera no Playwright), foto sobe, gerente abre painel, vê a foto na grade da OS, clica e amplia.

## Fora do escopo desta história

-   **Anexo PDF, áudio, vídeo.** Só foto (imagem). PDF e outros tipos viram histórias futuras conforme demanda.
-   **Compressão/resize automático no app antes de subir.** Pode entrar como melhoria depois se 8 MB for apertado.
-   **Anotação livre sobre a foto** (legenda, marcações). Vira história futura.
-   **Foto vinculada a outras entidades** (despesa, certificado, equipamento). Esta história só vincula a OS.
-   **Backup/CDN externo.** Disco configurado em `filesystems.php` decide local. Migração pra S3/Spaces fica pra ops, fora do produto.
-   **Gerente apaga foto.** No MVP só técnico que anexou apaga.

## Status

-   [x] planejada
-   [ ] em andamento
-   [ ] revisada
-   [ ] pronta
-   [ ] aceita
