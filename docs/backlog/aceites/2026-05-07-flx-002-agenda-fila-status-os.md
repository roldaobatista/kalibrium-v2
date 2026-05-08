# Aceite — FLX-002: Agenda, fila e status da OS

**Data:** 2026-05-07  
**Executor:** Kimi Code CLI  
**Revisor:** Kimi Code CLI (revisor automático)  
**Status:** Aguardando aceite do Roldão

---

## Roteiro percorrido

| # | Passo | Resultado | Imagem |
|---|-------|-----------|--------|
| 1 | Tela de login do app mobile | ✅ Login visível | `01-tela-login.png` |
| 2 | Device pendente — alerta de aprovação | ✅ Alerta exibido | `02-device-pendente.png` |
| 3 | Tela inicial com card "Minha fila" | ✅ Card visível | `03-tela-inicial.png` |
| 4 | Criar OS modo Campo-veículo com equipe | ✅ Formulário preenchido | `04-nova-os-campo.png` |
| 5 | Tela "Minha fila" — lista de OSs | ✅ Lista com OS criada | `05-fila-todas.png` |
| 6 | Detalhes da OS — dados e timeline | ✅ Timeline visível | `08-detalhes-os.png` |
| 7 | Painel web — lista de OS atualizada | ✅ Status refletido | `17-painel-os-lista.png` |

---

## Prints disponíveis

Veja `docs/backlog/aceites/imagens/2026-05-07-flx-002-agenda-fila-status-os/`

---

## Notas

- Status expandidos: `received`, `assigned`, `in_progress`, `paused`, `in_calibration`, `awaiting_approval`, `completed`, `cancelled`, `dispatch_started`, `arrived_client`, `left_client`
- Timeline de eventos (`service_order_events`) sincroniza entre mobile e backend
- Correções pós-revisão:
  - `Queue.tsx` filtra por `user_id === me.id || isMember` (dono da OS também vê)
  - `SyncPushController` permite update por membros da equipe
  - Migration `service_order_events` com índice em `service_order_id` e `nullOnDelete` em `user_id`
