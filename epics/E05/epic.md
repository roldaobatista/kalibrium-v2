# E05 — Laboratório e Calibração

## Objetivo
Implementar o coração técnico do produto: o registro da execução da calibração com pontos medidos, condições ambientais, padrões utilizados, cálculo de incerteza conforme GUM/JCGM 100:2008 e orçamento de incerteza versionado e rastreável. Este é o épico de maior complexidade técnica e metrológica do MVP.

## Valor entregue
Técnico calibrador preenche a execução da calibração na bancada (desktop ou tablet/PWA), seleciona os padrões usados, registra as condições ambientais e os pontos medidos. O sistema calcula a incerteza expandida e gera o orçamento versionado. Gerente pode revisar e aprovar. Rastreabilidade completa: instrumento → procedimento → padrão → calibração do padrão → incerteza calculada.

## Escopo

### Execução da calibração (REQ-MET-004)
- Registro da calibração vinculado à OS: técnico, data, instrumento, padrões usados
- Leituras brutas organizadas por ponto de medição (N pontos configuráveis pelo procedimento)
- Condições ambientais obrigatórias: temperatura ambiente, umidade relativa, pressão (quando aplicável ao domínio)
- Seleção dos padrões de referência utilizados (com verificação de validade no momento da calibração)
- Interface otimizada para uso em tablet/PWA na bancada (Livewire com responsividade)
- Entidades: Calibração

### Cálculo de incerteza (REQ-MET-005)
- Motor de cálculo de incerteza conforme GUM/JCGM 100:2008
- Componentes configuráveis por procedimento: incerteza do padrão, resolução, repetibilidade, reprodutibilidade, temperatura, etc.
- Cálculo automático de: incerteza combinada (uc), fator de cobertura k (para 95% de confiança) e incerteza expandida U
- Orçamento de incerteza versionado: cada revisão gera nova versão imutável
- Vinculação do orçamento ao procedimento de calibração (versão vigente)
- Entidades: Orçamento de incerteza

### Domínios metrológicos do MVP
- **Dimensional:** paquímetro, micrômetro, bloco padrão — unidade mm, resolução configurável
- **Pressão:** manômetro, transmissor — unidade kPa/bar/psi, faixa configurável
- **Massa:** balança analítica, semi-analítica, comercial — unidade g/kg, OIML conforme configuração do procedimento
- **Temperatura:** termômetro, termopar, PT100 — unidade °C, método comparação com banho
- Cada domínio tem template de pontos de medição e componentes de incerteza pré-configurados

### Histórico do instrumento (REQ-MET-007)
- Todas as calibrações passadas do mesmo número de série consolidadas em linha do tempo
- Visualização de tendência de desvio ao longo das calibrações (gráfico simples)

### Lacres e selos (Entidade: Lacre/Selo)
- Registro do lacre aplicado ao instrumento após calibração aprovada
- Rastreamento: número de série do lacre, tipo (aprovação/reprovação), data de aplicação, vencimento PSEI
- Vinculação a OS e instrumento

### Compliance metrológico (REQ-CMP-001)
- Calibração é append-only: nenhum ponto medido pode ser alterado após submissão (somente nova calibração)
- Audit trail completo: quem submeteu, quando, IP
- Padrão vencido bloqueado automaticamente na seleção de padrões da calibração

## Fora de escopo
- Emissão do certificado em PDF (E06)
- Domínios metrológicos fora do MVP (elétrico, óptico, vazão, torque, vibração)
- Calibração offline sem internet (pós-MVP — PWA offline)
- Assinatura digital ICP-Brasil no certificado (explicitamente fora do MVP per mvp-scope.md)

## Critérios de entrada
- E04 completo (OS e fluxo operacional)
- Procedimentos de calibração cadastrados (E03)
- Padrões de referência vigentes cadastrados (E03)

## Critérios de saída
- Calibração completa registrada para cada um dos 4 domínios metrológicos do MVP
- Cálculo de incerteza expandida U correto para ao menos 2 domínios (verificado por caso de teste metrológico com valores de referência conhecidos)
- Orçamento de incerteza versionado e imutável após aprovação
- Padrão vencido bloqueado na seleção (verificado por teste)
- Histórico de calibrações do instrumento visível em linha do tempo
- Interface responsiva funcional em tablet (tela de 10 polegadas)

## Stories previstas
- E05-S01 — Entidade Calibração com migrations e model
- E05-S02 — Interface de execução de calibração — pontos medidos e condições ambientais
- E05-S03 — Motor de cálculo de incerteza GUM (componentes configuráveis)
- E05-S04 — Orçamento de incerteza versionado (imutável após aprovação)
- E05-S05 — Templates de domínio: dimensional e pressão
- E05-S06 — Templates de domínio: massa e temperatura
- E05-S07 — Histórico do instrumento + linha do tempo de calibrações
- E05-S08 — Lacres e selos (entidade e vinculação à OS)
- E05-S09 — Interface tablet/PWA responsiva para bancada

## Dependências
- E04 (OS e fluxo operacional)
- E03 (padrões de referência, procedimentos)

## Riscos
- **Risco alto:** cálculo de incerteza GUM é matematicamente complexo e metrológicos têm critério rigoroso de aceitação — exige caso de teste com valores de referência validados por especialista externo (consultor metrológico)
- Interfaces de bancada em tablet devem ser testadas em dispositivo real, não só em emulador
- Quatro domínios metrológicos distintos aumentam a superfície de teste — template por domínio minimiza retrabalho

## Complexidade estimada
- Stories: 9
- Complexidade relativa: muito alta
- Duração estimada: 2-3 semanas
