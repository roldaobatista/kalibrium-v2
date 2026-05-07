# FLX-001 — Nova ordem de serviço

> **Épico:** FLX-001 (Nova ordem de serviço)
> **Status:** aceita e arquivada
> **Data de início:** 2026-05-07
> **Data de aceite:** 2026-05-07
> **Depende de:** E16 (sync engine) — merged

## O que entrega

Criação de ordem de serviço (OS) no app mobile com:

1. **Modo de execução:** bancada, campo-veículo, campo-UMC
2. **Atribuição de equipe:** até 5 pessoas do mesmo tenant
3. **Capacidade offline:** criação e edição funcionam sem internet; sync automática quando volta a conexão

## Checklist técnico

- [x] Migration `add_mode_to_service_orders_table` (campo `mode` enum: bench/field_vehicle/field_umc)
- [x] Migration `create_service_order_members_table` (relação N:M OS ↔ usuário, máx 5)
- [x] Model `ServiceOrder` atualizado com `mode` e relação `members()`
- [x] Model `ServiceOrderMember` criado
- [x] `SyncPushController` valida `mode` e `team_members` (máx 5, rejeita 6+)
- [x] `SyncPushController` substitui equipe no update (delete + recreate)
- [x] API `/api/mobile/team` lista técnicos ativos do tenant para seleção
- [x] Frontend mobile: seletor de modo no formulário de OS
- [x] Frontend mobile: lista de checkboxes para equipe (até 5, com contador)
- [x] `syncEngine.ts` e `db.ts` atualizados com novos campos (SQLite + IndexedDB)
- [x] Testes Pest: 13 testes, 74 assertions (9 originais + 4 novos)
- [x] PHPStan nível 8: sem erros nos arquivos alterados
- [x] Pint: formatação aplicada
- [x] Build mobile: TypeScript compila sem erros

## Testes novos

| Teste | O que verifica |
|-------|---------------|
| push create com modo campo-veiculo e equipe de 3 | Cria OS com `mode='field_vehicle'` e 3 membros |
| push create com equipe de 6 → rejeitado | Rejeita com `validation_error` quando excede 5 |
| push update substitui equipe | Remove membros antigos e insere novos no update |
| api mobile team retorna membros | `/api/mobile/team` lista técnicos ativos do tenant |

## Roteiro de aceite (e2e)

Imagens geradas em `docs/backlog/aceites/imagens/2026-05-07-flx-001-nova-ordem-de-servico/`:

| # | Imagem | Descrição |
|---|--------|-----------|
| 01 | `01-tela-login.png` | Tela de login do app mobile |
| 02 | `02-device-pendente.png` | IonAlert: dispositivo aguardando aprovação |
| 03 | `03-tela-inicial.png` | Tela inicial do técnico após login aprovado |
| 04 | `04-lista-os-vazia.png` | Lista de ordens de serviço vazia |
| 05 | `05-nova-os-bancada.png` | Formulário nova OS — modo Bancada selecionado |
| 06 | `06-lista-os-bancada.png` | Lista com OS recém-criada (modo Bancada) |
| 07 | `07-nova-os-campo-equipe.png` | Formulário nova OS — modo Campo-veículo com 2 membros |
| 08 | `08-lista-duas-os.png` | Lista com duas OS: Bancada e Campo-veículo |
| 09 | `09-editar-os-modo-umc.png` | Edição de OS — modo alterado para Campo-UMC com equipe aumentada |
| 10 | `10-lista-atualizada.png` | Lista atualizada após edição de modo e equipe |
| 11 | `11-painel-os-lista.png` | Painel do gerente — lista de OS com modo e informações |
