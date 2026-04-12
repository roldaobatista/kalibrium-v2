# E14 — LMS e Habilitações Técnicas

## Objetivo
Implementar o controle de habilitações técnicas dos colaboradores e a verificação automática na alocação de técnicos em OS. Garante que nenhum técnico sem habilitação vigente (ou com NR vencida) seja alocado em calibrações que exigem aquela habilitação — requisito de auditoria RBC e de conformidade com NRs de segurança.

## Valor entregue
Sistema verifica automaticamente, ao alocar um técnico em uma OS, se ele tem as habilitações necessárias e se estão vigentes. Se não tiver, bloqueia com mensagem clara indicando qual habilitação está ausente ou vencida. Gerente tem visão da matriz de competências da equipe.

## Escopo

### Habilitações técnicas — extensões avançadas
> **Nota:** O bloqueio básico de alocação (FR-LMS-03, FR-LMS-05) e a exceção justificada (FR-LMS-04) foram movidos para **E04-S09** (MVP). Este épico cobre as extensões avançadas sobre a base já implementada em E04.

- Alertas de vencimento integrados ao GED (90/60/30/15 dias — via E10)
- Exibição das habilitações do técnico disponível para ajudar o gestor a escolher alternativas
- Relatório de exceções justificadas (histórico auditável de todas as exceções)

### Matriz de competências (FR-LMS-02)
- Configuração de habilitações obrigatórias por procedimento de calibração
- Visualização da matriz: técnicos × habilitações (verde = vigente, amarelo = vencendo, vermelho = ausente/vencida)
- Exportação da matriz em CSV para auditoria externa (RBC)

### Catálogo básico de cursos internos (FR-LMS-01)
- Registro de treinamentos realizados: curso, data, carga horária, arquivo de certificado (via GED)
- Vinculado ao histórico do colaborador
- Nota: catálogo completo com trilhas e avaliações é P2 (FR-LMS-06)

## Fora de escopo
- Avaliações com gabarito e certificado de conclusão (FR-LMS-06, P2)
- Treinamento de clientes como receita (FR-LMS-07, P2)
- Integração com LMS externo (pós-MVP)
- eSocial S-2220 para treinamentos (pós-MVP — depende de módulo eSocial)

## Critérios de entrada
- E05 completo (calibrações — procedimentos têm habilitações obrigatórias configuradas)
- E10 completo (GED — certificados de habilitação armazenados)

## Critérios de saída
- Alocação de técnico sem habilitação vigente bloqueada com mensagem descritiva
- Exceção justificada registrada com aprovação e quem aprovou
- Matriz de competências exportável em CSV
- Alerta de vencimento de habilitação disparado 30 dias antes (verificado por teste)
- Técnico com NR vencida bloqueado em OS que exige a NR (verificado por teste)

## Stories previstas
- E14-S01 — Alertas de vencimento de habilitação via GED
- E14-S02 — Configuração avançada de habilitações obrigatórias por procedimento
- E14-S03 — Matriz de competências + exportação CSV
- E14-S04 — Relatório de exceções justificadas (auditoria)
- E14-S05 — Registro de treinamentos (catálogo básico)

## Dependências
- E05 (procedimentos com habilitações obrigatórias configuradas)
- E10 (GED — certificados de habilitação)

## Riscos
- Configuração de habilitações por procedimento pode ser trabalhosa inicialmente — seed com habilitações padrão por domínio metrológico facilita a adoção
- Gestores podem resistir ao bloqueio automático — mecanismo de exceção justificada (FR-LMS-04) é o escape-hatch necessário

## Complexidade estimada
- Stories: 5
- Complexidade relativa: média
- Prioridade: P1 (pós-MVP)
- Duração estimada: 1-2 semanas
