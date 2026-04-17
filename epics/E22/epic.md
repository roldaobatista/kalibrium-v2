# E22 — SPC e Drift de Qualidade Metrológica ISO 17025

## Objetivo

Implementar o monitoramento estatístico de controle de processo (SPC) dos padrões de referência com gráficos de controle, detecção automática de drift (desvio do valor nominal) com alerta e bloqueio condicional — entregando a capacidade de monitoria metrológica exigida por laboratório acreditado Cgcre/Inmetro conforme ISO 17025:2017 §6.4.

## Valor entregue

Aline (Responsável de Qualidade, Persona 8) monitora a estabilidade dos padrões de referência semanalmente em um único painel, recebe alerta automático quando um padrão começa a deriva antes do vencimento formal, e tem a evidência estruturada para responder auditoria do RBC/Inmetro sem planilha manual. Gestor ganha garantia de que calibrações não são emitidas com padrão fora de controle — risco reputacional e legal eliminado.

## Escopo

### Gráficos de controle SPC (REQ-MET-009)
- Gráfico de controle por padrão de referência: eixo X = data de leitura/calibração, eixo Y = valor medido
- Limites de controle: UCL (Upper Control Limit), LCL (Lower Control Limit), CL (Center Line) — calculados a partir de histórico de pelo menos 10 leituras
- Limites de aviso (Warning Limits) em ±2σ; limites de controle em ±3σ
- Regras de Nelson/Western Electric configuráveis por padrão (mínimo: regra 1 — ponto fora de ±3σ)
- Dados de entrada: leituras de calibração periódica do padrão (vindas do módulo E05)
- Entidades: `ControlChartConfig`, `ControlChartReading`

### Drift automático de padrão (REQ-MET-010)
- Drift detectado quando leitura do padrão se afasta progressivamente do valor nominal (tendência estatística, não só ponto fora de limite)
- Algoritmo de detecção de tendência: mínimo 5 leituras consecutivas no mesmo sentido (regra de Nelson #2)
- Alerta ao responsável de qualidade e ao gestor: tipo `DRIFT_DETECTADO`, padrão afetado, magnitude do desvio, data da primeira leitura da tendência
- Bloqueio condicional: quando drift ultrapassa limiar configurável (ex.: >50% do limite de controle), sistema sugere suspensão do padrão para recalibração
- Bloqueio hard: quando padrão entra em estado `suspenso_drift`, não pode ser associado a OS nova (validação server-side)
- Entidades: `DriftAlert`, estado `suspenso_drift` em padrão de referência

### Integração com calibrações (E05)
- SPC lê automaticamente os resultados de cada calibração periódica do padrão (evento `CalibracaoRegistrada`)
- Nenhuma entrada manual de dados de SPC — alimentado automaticamente pelo fluxo normal de calibração
- Histórico mínimo para cálculo de limites: 10 leituras (abaixo disso, gráfico exibe "dados insuficientes")

### Painel de qualidade (persona Aline)
- Visão consolidada de todos os padrões do laboratório: nome, última leitura, status SPC (em controle / alerta / fora de controle / suspenso)
- Filtros: local de estoque, tipo de padrão, status SPC
- Exportação do histórico de leituras e gráfico em PDF para evidência de auditoria
- Entidade: nenhuma nova — consolidação de `ControlChartConfig` e `DriftAlert` existentes

## Fora de escopo
- SPC para grandezas de calibração (o valor calibrado no instrumento do cliente) — SPC aqui é só para padrões do laboratório
- Análise de capacidade de processo (Cp, Cpk) — pós-MVP
- Integração com equipamentos via protocolo RS-232/GPIB — pós-MVP
- Método de análise de incerteza expandida via Monte Carlo — pós-MVP

## Acceptance Criteria do épico

- **AC-E22-01:** Gráfico de controle de padrão exibe UCL, LCL e CL calculados a partir de 10+ leituras históricas; ponto fora de ±3σ fica destacado em vermelho.
- **AC-E22-02:** Drift detectado automaticamente após 5 leituras consecutivas no mesmo sentido; alerta enviado ao responsável de qualidade sem ação manual.
- **AC-E22-03:** Padrão em estado `suspenso_drift` não pode ser associado a OS nova; tentativa retorna erro com código explícito.
- **AC-E22-04:** Dados do SPC são alimentados automaticamente pelas calibrações do E05, sem digitação manual.
- **AC-E22-05:** Aline exporta histórico de leituras e gráfico de controle em PDF para evidência de auditoria Inmetro.
- **AC-E22-06:** Isolamento multi-tenant: padrões e gráficos de tenant A não visíveis para tenant B.

## Dependências

### Diretas (bloqueiam início)
- E05 merged (calibrações e padrões de referência — fonte de dados do SPC)
- ADR-0016 aceita (tenant_id em ControlChartConfig e DriftAlert)

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- ISO 17025:2017 §6.4 (Equipamentos) + §6.6 (Rastreabilidade de medição) + ABNT NBR ISO 8258 (cartas de controle Shewhart)

## Definition of Done
- Gráficos de controle calculados e exibidos corretamente para padrões com 10+ leituras
- Detecção de drift funcional com regras de Nelson configuráveis
- Bloqueio de padrão suspenso em OS funcional (server-side)
- Exportação de PDF para auditoria funcional
- Testes: unit (Pest) para cálculo de limites e detecção de drift, E2E (Playwright) para painel — verdes no CI
- Validação pelo product-expert com Aline (simulação de auditoria RBC)

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E22-S01 | Configuração de gráfico de controle por padrão (UCL/LCL/CL + regras de Nelson) | alta |
| E22-S02 | Alimentação automática do SPC pelas calibrações do E05 (event listener) | média |
| E22-S03 | Detecção de drift automática + alerta (5 leituras consecutivas, regra #2) | alta |
| E22-S04 | Bloqueio de padrão suspenso em OS + estado `suspenso_drift` | média |
| E22-S05 | Painel de qualidade consolidado (todos os padrões + filtros + exportação PDF) | alta |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Histórico de leituras < 10 impede cálculo de limites para padrão novo | médio | Exibir aviso "dados insuficientes — 10 leituras mínimas" sem bloquear o padrão; calcular limites provisórios após 5 leituras com aviso |
| Regras de Nelson geram falsos positivos em laboratórios com poucos dados | médio | Configuração por padrão: habilitar apenas Regra 1 (ponto fora) por padrão; Regra 2 (drift) é opt-in |
| PDF de auditoria precisa atender formato específico do RBC/Inmetro | alto | Consultar modelo de gráfico de controle do NIT-Dicla-035 antes de implementar S05 <!-- TBD: validar template com PM/Aline --> |

## Estimativa
- Stories: 5
- Complexidade relativa: alta (algoritmos metrológicos + domínio ISO 17025)
- Duração estimada: 2-3 semanas

## Referências
- PRD-ampliacao-2026-04-16-v2.md §1.2 (Pacote B — SPC e Drift, REQ-MET-009/010)
- ISO 17025:2017 §6.4 e §6.6
- ABNT NBR ISO 8258 — Cartas de controle de Shewhart
- NIT-Dicla-035 (Inmetro) — Rastreabilidade de medição
- docs/product/personas.md Persona 8 (Aline, responsável de qualidade)
- docs/product/journeys.md Jornada 14 (monitoria de qualidade)
