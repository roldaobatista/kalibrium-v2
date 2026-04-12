# E08 — Financeiro e Contas a Receber

## Objetivo
Implementar o controle financeiro básico: baixa automática no contas a receber quando a NFS-e é emitida e conciliação manual de recebimentos. Fecha o ciclo completo calibração → certificado → nota fiscal → cobrança, eliminando o atraso médio de 7-14 dias entre serviço prestado e cobrança registrada.

## Valor entregue
Quando a NFS-e é autorizada, um título a receber é criado automaticamente com valor, vencimento e dados do cliente. Administrativo gerencia os recebimentos por uma lista simples de títulos em aberto, parciais e quitados. O laboratório tem visibilidade de quanto tem a receber sem usar planilha.

## Escopo

### Título financeiro (REQ-FIS-004)
- Criação automática de título a receber disparada pelo evento `NFS-e.autorizada`
- Título vinculado à OS, ao certificado e à NFS-e correspondente
- Atributos: valor, vencimento (configurável por contrato/cliente ou padrão do tenant), cliente, status
- Estados do título: `aberto → parcial → quitado | vencido`
- Entidades: Título financeiro

### Contas a receber (REQ-FIS-004, REQ-FIS-005)
- Listagem de títulos com filtros: status, cliente, vencimento, período
- Visualização de totais: a receber, atrasados, recebidos no período
- Baixa manual de recebimento: parcial ou total, com data de pagamento e forma de pagamento (dinheiro, TED, PIX, cheque)
- Exportação da listagem em CSV (para reuso contábil — REQ-OPL-004)

### Conciliação manual (REQ-FIS-005)
- O MVP não integra com banco (conciliação é manual)
- Interface para marcar título como quitado com data e forma de pagamento
- Registro de quem realizou a baixa e quando (audit trail)

### Pré-fatura (agrupamento de OS)
- Pré-fatura automática agrupando OS concluídas do mesmo cliente no período (quando configurado para faturamento periódico)
- Aprovação da pré-fatura antes de emitir NFS-e (fluxo: OS concluídas → pré-fatura → aprovação → NFS-e → título a receber)
- Entidades: Pré-fatura, Fatura

### Exportação contábil (REQ-OPL-004)
- Exportação das calibrações e recebimentos do mês em formato CSV
- Campos: OS, cliente, valor, data, forma de pagamento, número NFS-e

## Fora de escopo
- Integração bancária para conciliação automática (explicitamente fora do MVP per mvp-scope.md)
- Contas a pagar (pós-MVP)
- DRE e relatórios gerenciais financeiros avançados (pós-MVP)
- Gateway de pagamento (boleto, cartão) — cobrança é feita externamente pelo laboratório no MVP

## Critérios de entrada
- E07 completo (NFS-e autorizada, evento `NFS-e.autorizada` disparando)

## Critérios de saída
- Título a receber criado automaticamente após autorização da NFS-e
- Baixa manual funcionando: título muda de `aberto` para `quitado` com data e forma registradas
- Listagem de contas a receber com filtros e totais corretos
- Exportação CSV com dados do mês gerada com sucesso
- Audit trail da baixa registrado (quem quitou, quando)

## Stories previstas
- E08-S01 — Entidades Título financeiro, Pré-fatura e Fatura com migrations
- E08-S02 — Criação automática de título a receber (evento `NFS-e.autorizada`)
- E08-S03 — Interface de contas a receber com filtros e totais (Livewire)
- E08-S04 — Baixa manual de recebimento (parcial e total)
- E08-S05 — Exportação CSV para reuso contábil

## Dependências
- E07 (NFS-e autorizada + evento)

## Riscos
- Agrupamento de OS em pré-fatura tem regras variáveis por cliente (avulso vs recorrente) — configuração simples por cliente no MVP, sem motor de regras complexo
- Exportação CSV precisa de validação com contador do cliente-âncora para garantir campos adequados — planejar reunião de validação

## Complexidade estimada
- Stories: 5
- Complexidade relativa: média
- Duração estimada: 1 semana
