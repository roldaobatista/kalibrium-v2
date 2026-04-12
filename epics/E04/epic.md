# E04 — Ordens de Serviço e Fluxo Operacional

## Objetivo
Implementar a entidade central de execução do laboratório: a Ordem de Serviço (OS). Cobre a criação do pedido, agendamento, máquina de estados completa, fila de trabalho por técnico e status visível para toda a equipe. É a "espinha dorsal" do fluxo operacional.

## Valor entregue
Atendente cria uma OS a partir de cliente + instrumento + procedimento. Técnico visualiza sua fila diária. Gerente vê o painel com pedidos atrasados e pedidos aguardando aprovação. O fluxo recebido → em calibração → certificado emitido → pago está operacional.

## Escopo

### Ordem de Serviço (REQ-MET-003, REQ-FLX-002)
- Criação de OS: cliente + instrumento + procedimento + técnico responsável + prazo
- Máquina de estados completa:
  `recebido → triado → agendado → atribuído → em execução → pausado → concluído → aprovação pendente → aprovado → faturamento pendente → faturado → pago → reaberto | cancelado`
- Registro de data/hora em cada transição de estado (audit trail)
- Reabertura e cancelamento com justificativa obrigatória
- Entidades: Ordem de Serviço (OS)

### Agendamento (REQ-FLX-001)
- Registro de data/hora de coleta ou entrega do instrumento
- Vinculação do agendamento à OS e ao técnico
- Confirmação/cancelamento do agendamento
- Entidades: Agendamento

### Fila de trabalho por técnico (REQ-OPL-002)
- Visualização das OS atribuídas ao técnico logado, ordenadas por prazo e prioridade
- Atribuição de OS a técnico (pelo gerente ou pelo próprio técnico quando permitido pelo papel)
- Indicação de OS atrasadas (prazo ultrapassado) na fila

### Bloqueio de alocação por habilitação (FR-LMS-03, FR-LMS-05)
- Cadastro básico de habilitações por técnico (tipo, validade)
- NRs de segurança (NR-10, NR-12, NR-35) como habilitações com validade
- Verificação automática na atribuição do técnico à OS: bloqueia se habilitação obrigatória ausente ou vencida
- Exceção justificada: gestor pode forçar alocação com justificativa auditável
- Entidades: Habilitação, HabilitaçãoTécnico

### Checklist de OS
- Checklist vinculado à OS com itens de verificação obrigatória antes de concluir
- Itens configuráveis por procedimento de calibração
- Entidades: Checklist

### Notificações de fluxo (REQ-FLX-003, REQ-FLX-004)
- Notificação ao cliente por e-mail nas transições relevantes: OS criada, em execução, certificado emitido, enviado
- Notificação opcional por WhatsApp (somente se cliente consentiu — integração com E12)
- Eventos de domínio disparados em cada transição: `OS.concluida`, etc.

### Compliance de registros (REQ-CMP-001, REQ-CMP-002)
- Registro imutável de todas as OS (append-only: estado nunca deletado, apenas adicionado)
- Log de acesso: quem leu qual OS, quando (base para REQ-CMP-002)
- Política de retenção: OS retidas por 10 anos (REQ-CMP-003)

## Fora de escopo
- Execução técnica da calibração (E05)
- Emissão de certificado (E06)
- Fiscal e faturamento (E07, E08)
- Dashboard gerencial completo (E11 — mas eventos de OS alimentam E11)

## Critérios de entrada
- E03 completo (clientes, instrumentos, padrões, procedimentos)

## Critérios de saída
- OS criada com cliente, instrumento e procedimento
- Todas as transições de estado funcionando (verificadas por testes de máquina de estados)
- Fila do técnico mostrando suas OS por prazo
- Notificação por e-mail disparada na criação da OS (integração com fila de jobs)
- Registro imutável: nenhuma OS pode ser deletada (verificado por teste de segurança)
- Evento `OS.concluida` disparado corretamente ao concluir (verificado por teste de evento)

## Stories previstas
- E04-S01 — Entidade OS com migrations e model
- E04-S02 — Máquina de estados da OS (todas as transições)
- E04-S03 — Criação e edição de OS (interface Livewire)
- E04-S04 — Agendamento de coleta/entrega
- E04-S05 — Fila de trabalho por técnico (interface Livewire)
- E04-S06 — Checklist de OS configurável por procedimento
- E04-S07 — Notificações de transição de estado (via fila + E12)
- E04-S08 — Compliance: append-only, log de acesso, retenção 10 anos
- E04-S09 — Bloqueio de alocação por habilitação e NRs

## Dependências
- E03 (clientes, instrumentos, padrões, procedimentos)
- E12 (comunicação) — integração pontual; E04 pode ser desenvolvido com stub de notificação e E12 substituindo depois

## Riscos
- Máquina de estados com 12+ estados pode gerar regressões — cobertura de testes de estado deve ser completa (100% das transições)
- Notificação assíncrona por WhatsApp depende de credencial de provedor — usar stub em staging

## Complexidade estimada
- Stories: 9
- Complexidade relativa: alta
- Duração estimada: 2 semanas
