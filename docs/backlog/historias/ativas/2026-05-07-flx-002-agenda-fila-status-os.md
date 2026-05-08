---
status: em andamento
slice: FLX-002
dominio: Operações (Service Operations)
epico: E04 — Fluxo de Ordem de Serviço Ponta a Ponta
depende_de: FLX-001 — Nova ordem de serviço
---

# FLX-002 — Agenda, fila e status da OS

## Contexto

A FLX-001 entregou criação de OS com modo (bancada/campo-veículo/campo-UMC) e equipe. Agora o técnico precisa ver sua fila de trabalho no app, acompanhar o status de cada OS ao longo do dia e registrar transições de estado (especialmente em campo).

## Objetivo

Dar ao técnico no app mobile uma visão clara da sua agenda/fila de OSs e permitir que ele registre mudanças de status da OS conforme avança o trabalho — seja na bancada ou em campo.

## Requisitos

### R1 — Status expandidos da OS

O status da OS evolui de 5 estados para um fluxo que cobre bancada e campo:

| Status | Label | Quando usar |
|--------|-------|-------------|
| `received` | Recebido | OS criada, ainda não iniciada |
| `assigned` | Atribuído | Técnico/designado confirmou que vai trabalhar |
| `in_progress` | Em execução | Trabalho iniciado (bancada ou chegou no cliente) |
| `paused` | Pausado | Aguardando peça, cliente, ou interrupção |
| `in_calibration` | Em calibração | Fase metrológica específica (bancada) |
| `awaiting_approval` | Aguardando aprovação | Concluído, esperando aprovação do gestor |
| `completed` | Concluído | Aprovado e fechado |
| `cancelled` | Cancelado | Cancelado |

Estados de campo adicionais (específicos para modo campo):
- `dispatch_started` — Deslocamento iniciado
- `arrived_client` — Chegou no cliente
- `left_client` — Saiu do cliente

> Nota: estados de campo são um sub-fluxo dentro de `in_progress`. O técnico marca "iniciar deslocamento" → "cheguei" → "sair do cliente" antes de concluir.

### R2 — Fila/agenda do técnico no mobile

- Tela "Minha fila" acessível a partir do menu principal ou da home
- Lista as OSs atribuídas ao técnico logado (via `service_order_members` ou `user_id` da OS)
- Ordenação: primeiro as não iniciadas, depois em progresso, depois pausadas, depois concluídas
- Cada card mostra: cliente, instrumento, modo (badge), status (badge), data de criação
- Filtro rápido: "Hoje" / "Todas" / "Concluídas"
- Offline: a fila funciona 100% offline, lendo do SQLite local

### R3 — Tela de detalhes da OS com ações de status

- Ao tocar uma OS na fila, abre tela de detalhes com:
  - Dados da OS (cliente, instrumento, modo, equipe, notas)
  - Botões de ação contextuais conforme status atual:
    - `received` → "Iniciar" (vai para `in_progress`)
    - `in_progress` + modo campo → "Cheguei no cliente" (vai para `arrived_client`)
    - `arrived_client` → "Sair do cliente" (vai para `left_client`)
    - `left_client` ou `in_progress` (bancada) → "Pausar" ou "Concluir"
    - `paused` → "Retomar" (volta para `in_progress`)
  - Status atual em destaque
- Timeline da OS: lista cronológica de transições de status com timestamp

### R4 — Timeline de eventos da OS

- Nova tabela `service_order_events`:
  - `id` (uuid), `service_order_id` (fk), `user_id` (quem fez), `event_type` (enum: status_change, note_added, photo_added, team_changed), `old_value`, `new_value`, `metadata` (JSON), `created_at`
- Cada mudança de status gera um evento
- Timeline exibida na tela de detalhes da OS
- Sync: eventos são criados no backend e também armazenados localmente no mobile

### R5 — Sync de status

- Mudança de status no mobile é registrada no sync engine (mesmo mecanismo de FLX-001)
- Backend aceita push de `status` e cria `service_order_event` automaticamente
- No pull, o mobile recebe o status atualizado e a timeline

## Critérios de aceite

1. Técnico abre o app offline e vê sua fila de OSs
2. Técnico toca "Iniciar" numa OS de campo → status muda, evento registrado
3. Técnico toca "Cheguei no cliente" → status muda, evento registrado
4. Técnico sincroniza → status e eventos aparecem no painel web
5. Painel web mostra a timeline da OS com todos os eventos
6. Testes Pest cobrem: transição de status, criação de evento, fila do técnico API
7. PHPStan level 8 limpo no escopo
8. Build mobile passa sem erro TypeScript

## Fora de escopo (futuro)

- Roteirização e otimização de rota (FR-OPS-04)
- Despacho automático por competência (REQ-OPL-005)
- SLA e alertas de atraso
- Geolocalização nos eventos de campo
- Check-in/check-out de veículo UMC

## Notas técnicas

- Migration: expandir coluna `status` no backend (atualmente string livre, manter compatibilidade)
- Criar `ServiceOrderEvent` model e migration
- Atualizar `ServiceOrderStatus` enum/type no mobile
- `SyncPushController` precisa aceitar `status` no update e gerar evento
- O mobile já tem `status` no payload de create/update — precisa apenas expandir os valores válidos
