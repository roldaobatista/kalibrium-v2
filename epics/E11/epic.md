# E11 — Dashboard Operacional e Relatórios

## Objetivo
Implementar a visão gerencial do laboratório: painel com pedidos atrasados, fila e indicadores mínimos de desempenho. Permite que o gerente tome decisões operacionais com base em dados, sem depender de planilha.

## Valor entregue
Gerente abre o sistema e imediatamente vê: quantos pedidos estão atrasados, quais estão na fila de cada técnico, quais aguardam aprovação e os indicadores do mês (tempo médio por calibração, taxa de retrabalho, aderência ao prazo). Tudo em uma tela.

## Escopo

### Dashboard operacional (REQ-OPL-001)
- Painel em tempo real (atualização por polling ou Livewire push):
  - Pedidos atrasados: OS com prazo ultrapassado, por técnico
  - Pedidos na fila: OS em execução e agendadas, por técnico
  - Pedidos aguardando aprovação: OS concluídas aguardando dual sign-off do gerente
  - Pedidos aguardando emissão de NFS-e
- Filtros: por período, por técnico, por domínio metrológico
- Drill-down: clicar em um número vai para a listagem da OS correspondente

### Fila de trabalho consolidada (REQ-OPL-002)
- Visão da carga de trabalho de cada técnico: quantidade de OS, tempo estimado, OS atrasadas
- Redistribuição rápida: arrastar OS de um técnico para outro (interface Livewire)

### Indicadores mínimos (REQ-OPL-003)
- Tempo médio de calibração por domínio metrológico (do recebimento ao certificado emitido)
- Taxa de retrabalho: OS reabertas / total de OS concluídas no período
- Aderência ao prazo: OS entregues no prazo / total de OS concluídas no período
- Período configurável: semana, mês, trimestre
- Gráficos simples (barras e linha) — biblioteca Recharts via Alpine.js ou Chart.js

### Exportação (REQ-OPL-004)
- Exportação das calibrações do mês em CSV (também entregue em E08, consolidado aqui)
- Exportação dos indicadores do período em CSV

## Fora de escopo
- BI avançado / analytics preditivo (pós-MVP)
- Relatórios de lucratividade por OS (pós-MVP — requer dados de custo)
- Dashboard multi-tenant para a operação SaaS (pós-MVP — ProductOps)

## Critérios de entrada
- E04 completo (OS com fluxo operacional e dados de transição de estado)

## Critérios de saída
- Painel mostrando corretamente pedidos atrasados, na fila e aguardando aprovação
- Indicadores do mês calculados corretamente (verificados com dados de seed)
- Exportação CSV gerada com sucesso
- Painel atualiza sem reload de página ao mudar estado de uma OS (Livewire polling ou push)

## Stories previstas
- E11-S01 — Dashboard operacional: pedidos atrasados, na fila, aguardando aprovação
- E11-S02 — Fila consolidada por técnico com redistribuição
- E11-S03 — Indicadores mínimos (tempo médio, retrabalho, aderência)
- E11-S04 — Gráficos e exportação CSV
- E11-S05 — Atualização em tempo real (Livewire polling)

## Dependências
- E04 (OS e transições de estado)

## Riscos
- Queries de agregação para indicadores podem ser lentas com volume grande de OS — views materializadas ou cache por período
- Redistribuição de OS por drag-and-drop em Livewire requer atenção a estado de UI — simplificar para dropdown se drag-drop travar o slice

## Complexidade estimada
- Stories: 5
- Complexidade relativa: média
- Duração estimada: 1 semana
