# E17 — UMC e Frota Operacional

## Objetivo

Implementar o cadastro e gestão operacional de UMC (Unidade Móvel de Calibração), veículos operacionais (assinados e pool) e diários de bordo, habilitando a empresa a despachar, rastrear e controlar toda a frota com massas-padrão a bordo e manutenção preventiva automática.

## Valor entregue

Gestor e administrativo conseguem cadastrar cada UMC e veículo com seus dados técnicos, massas-padrão e certificados vigentes, reservar veículos do pool, manter diário de bordo atualizado offline e receber alerta automático de manutenção preventiva — eliminando planilhas paralelas e o risco de enviar técnico ao campo com padrão vencido ou veículo em manutenção.

## Escopo

### UMC — Cadastro e configuração (REQ-UMC-001)
- Cadastro de UMC: placa, chassi, modelo, ano, capacidade do guindaste (tonelagem), motorista principal atribuído
- Status da UMC: disponível, em OS, em manutenção, inativo
- Vínculo com massas-padrão a bordo (ver estoque E19 — aqui apenas vínculo de referência)
- Entidades: `UMC`, `VinculoMassaUMC`

### Massas-padrão a bordo (REQ-UMC-002)
- Listagem de massas-padrão a bordo da UMC com número de série, capacidade, certificado vigente, validade da calibração
- Alerta automático quando certificado de massa-padrão vence em 30 dias
- Bloqueio de agendamento de OS de calibração de balança rodoviária quando massa-padrão necessária está vencida
- Entidade: `MassaPadraoUMC`

### Agenda e bloqueio (REQ-UMC-003)
- Agenda da UMC vinculada ao sistema de OS: UMC só pode estar em 1 OS ativa por vez
- Manutenção preventiva cria evento de bloqueio automático na agenda da UMC
- Conflito de agendamento: tentativa de alocar UMC já ocupada gera erro de validação com sugestão de alternativa

### Veículo operacional (REQ-VHL-001, REQ-VHL-002, REQ-VHL-003)
- Cadastro de veículo: placa, modelo, ano, `modo_uso` = `assinado` (fixo a técnico) ou `compartilhado` (pool)
- Veículo assinado: vínculo direto com técnico responsável; não disponível para reserva por outros
- Veículo compartilhado (pool): calendário de reserva por data/hora; conflito de reserva bloqueado
- Reserva: técnico seleciona veículo do pool no momento de aceitar OS de campo; liberação automática ao concluir OS
- Entidades: `VeiculoOperacional`, `ReservaVeiculo`

### Diário de bordo (REQ-UMC-004, REQ-VHL-003)
- Diário de bordo por viagem: KM inicial, KM final, abastecimento com foto obrigatória, pedágio com foto opcional, observações
- Funciona 100% offline (armazenado localmente, sync via E16)
- Relatório de custo de viagem por veículo/UMC por período
- Entidade: `DiarioBordo`, `RegistroCombustivel`

### Manutenção preventiva (REQ-UMC-003 + REQ-UMC-005 via E24)
<!-- TBD: refinar com PM antes de /start-story — REQ-UMC-005 (scheduler automático) foi atribuído ao E24; aqui cobre apenas o registro manual de manutenção preventiva e bloqueio de agenda. Agendamento automático fica para E24. -->
- Registro manual de manutenção: tipo, data, KM, responsável, próxima manutenção prevista (por KM ou por data)
- Bloqueio automático de agenda da UMC/veículo ao entrar em manutenção

## Fora de escopo
- Rastreamento GPS em tempo real da frota — pós-MVP
- Telemetria de veículo (OBD-II) — pós-MVP
- Agenda consolidada cross-UMC para 3+ UMC em rotação — pós-MVP
- Reserva avançada de pool com fila de espera — pós-MVP
- Agendamento automático de manutenção preventiva (scheduler por KM/tempo) — E24

## Acceptance Criteria do épico

- **AC-E17-01:** Gestor cadastra UMC com todas as massas-padrão a bordo e certificados; sistema bloqueia agendamento de OS quando massa necessária está vencida.
- **AC-E17-02:** Tentativa de alocar UMC já ocupada em outra OS gera erro imediato com nome da OS conflitante.
- **AC-E17-03:** Técnico reserva veículo do pool para OS de campo; outro técnico vê veículo como indisponível no mesmo período.
- **AC-E17-04:** Motorista registra diário de bordo offline (KM + abastecimento + foto); ao sincronizar, dados aparecem no relatório de custo de viagem.
- **AC-E17-05:** Alerta de validade de certificado de massa-padrão aparece 30 dias antes do vencimento para o gestor.
- **AC-E17-06:** Isolamento multi-tenant: UMC e veículos de tenant A não são visíveis para tenant B.

## Dependências

### Diretas (bloqueiam início)
- E16 merged (sync offline operacional para diário de bordo)
- ADR-0016 aceita (tenant_id em todas as entidades de frota)

### Transitivas
- E19 (Estoque Multinível) — vínculo de massas-padrão a bordo da UMC usa entidades de estoque do E19; E17-S02 pode referenciar stub antes do E19 estar completo

## ADRs relacionadas
- ADR-0015 — Stack offline-first mobile
- ADR-0016 — Isolamento multi-tenant
- ADR-0017 — Sync engine

## Definition of Done
- CRUD completo de UMC e veículo operacional com validações de negócio
- Reserva de pool com bloqueio de conflito funcional
- Diário de bordo offline sincronizando via E16 sync engine
- Alertas de validade de padrão/manutenção disparando no prazo
- Testes: unit (Pest) + E2E (Playwright) + teste de isolamento multi-tenant — todos verdes no CI

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E17-S01 | Cadastro de UMC (placa, chassi, motorista, status, massas-padrão a bordo) | média |
| E17-S02 | Agenda da UMC: bloqueio por OS + conflito de agendamento | alta |
| E17-S03 | Cadastro de veículo operacional (assinado vs pool) + reserva de pool | média |
| E17-S04 | Diário de bordo (KM + abastecimento + foto) — offline-first | alta |
| E17-S05 | Alertas de validade (massa-padrão + manutenção) + bloqueio de agenda | média |
| E17-S06 | Relatório de custo de viagem por veículo/período | baixa |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Fotos do diário de bordo sem sincronização de binários (E16 sincroniza só metadados) | alto | Definir protocolo de sync de arquivos binários (S3 presigned URL upload direto do dispositivo) em E17-S04 |
| Vínculo de massas-padrão com estoque (E19) cria dependência circular | médio | E17 usa referência por ID de item de estoque; E19 confirma a entidade real; sem bloqueio funcional |
| Gestor precisa de visão cross-UMC quando frota cresce | baixo | Aceito como pós-MVP; INDEX por tenant já cobre 1-3 UMC |

## Estimativa
- Stories: 6
- Complexidade relativa: alta
- Duração estimada: 2-3 semanas

## Referências
- PRD-ampliacao-2026-04-16.md §4 (UMC e Frota, REQ-UMC-001..004, REQ-VHL-001..003)
- docs/product/journeys.md Jornada 7 (campo UMC), Jornada 11 (admin UMC e frota)
- docs/product/personas.md Persona 4 (Lúcio, motorista UMC)
