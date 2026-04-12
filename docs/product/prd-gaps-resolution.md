# Resolução de Gaps PRD — FRs × Módulos

> **Origem:** ASS-020 — inconsistência entre 122 FRs e 38+ módulos do PRD.
> **Decisão PM:** "Detalhar agora" (2026-04-12).
> **Status:** pendente aprovação PM.
> **Uso:** Este documento suplementa o PRD congelado. O epic-decomposer deve considerar estes FRs ao decompor épicos.

---

## 1. Módulos sem FRs — FRs criados

### 1.1 GED — Gestão Eletrônica de Documentos

> Módulo transversal referenciado em 8+ CAPs e fluxos, mas ausente do inventário formal de módulos.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-GED-01 | Repositório centralizado com upload de qualquer formato, vinculação contextual a entidades (OS, cliente, instrumento, contrato, fornecedor, colaborador, ativo, certificado, NC) e busca full-text com OCR | P0 | Starter |
| FR-GED-02 | Controle de versão documental com ciclo de vida: Rascunho → Análise Crítica → Aprovação → Publicado → Em Revisão → Obsoleto → Cancelado | P1 | Professional |
| FR-GED-03 | Controle de acesso por pasta/documento com marcação de confidencialidade e log de acesso auditável | P0 | Starter |
| FR-GED-04 | Alertas automáticos de vencimento de documentos (90/60/30/15 dias) com status "vencido" visível em todos os módulos consumidores | P1 | Basic |
| FR-GED-05 | Link temporário externo com token, expiração configurável, revogação e log de download (compartilhamento com auditores/clientes) | P1 | Professional |
| FR-GED-06 | Limites de armazenamento por plano SaaS (10 GB Starter → 1 TB+ Enterprise) com alertas em 80% e 95% e bloqueio suave em 100% | P0 | Starter |
| FR-GED-07 | Nenhum outro módulo armazena arquivos fora do GED — fonte de verdade única para documentos do tenant | P0 | Starter |

**Downstream:** Quality, Lab, HR, Finance, Fiscal, Portal, SupplierPortal, Security, WorkOrders.

---

### 1.2 LMS — Habilitações e Treinamento

> Módulo que conecta RH, técnico e OS. Referenciado em Employee Lifecycle e bloqueios de alocação, mas ausente do inventário.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-LMS-01 | Catálogo de cursos internos e externos com trilhas por função/cargo e certificações técnicas por colaborador | P1 | Professional |
| FR-LMS-02 | Matriz de competências auditável: cada tipo de serviço tem lista configurável de habilitações obrigatórias | P1 | Professional |
| FR-LMS-03 | Verificação automática de habilitação na alocação do técnico: bloqueio com mensagem indicando habilitação ausente ou vencida | P0 | Basic |
| FR-LMS-04 | Exceção justificada: gestor pode forçar alocação de técnico sem habilitação com justificativa auditável e aprovação registrada | P1 | Professional |
| FR-LMS-05 | NRs como habilitações de segurança (NR-10, NR-12, NR-35) com validade e bloqueio automático de alocação quando vencida | P0 | Basic |
| FR-LMS-06 | Avaliações com gabarito, nota mínima configurável e certificado de conclusão com QR público | P2 | Lab |
| FR-LMS-07 | Treinamento de clientes como receita: catálogo, inscrição, NF-e automática, certificado com QR, integração com LMS interno | P2 | Business |

**Downstream:** WorkOrders (bloqueio de alocação), HR (PDI, competências), Quality (SGQ), eSocial (S-2220), GED (certificados).

---

### 1.3 Pricing — Motor de Preços e Monetização SaaS

> Motor de regras de precificação transversal consumido por BillingSaaS, Quotes, Finance e CRM. Sem FRs próprios.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-PRI-01 | 6 planos canônicos (Starter → Enterprise) com limites operacionais configuráveis: usuários, CNPJs, filiais, OS/mês, certificados, armazenamento GED, chamadas API, retenção de logs | P0 | Starter |
| FR-PRI-02 | Feature gates por módulo: módulos fora do plano visíveis mas bloqueados com CTA de upgrade; alertas em 80% e 95% do limite de uso | P0 | Starter |
| FR-PRI-03 | Upgrade/downgrade de plano em tempo real com cálculo pro-rata automático e ajuste na fatura corrente | P0 | Starter |
| FR-PRI-04 | 5 categorias de monetização: entitlement de plano, add-on funcional, overage de uso, serviço profissional, exceção Enterprise | P1 | Professional |
| FR-PRI-05 | Motor de markup e descontos: regras por cliente, volume, contrato, campanha e vigência temporal | P1 | Professional |
| FR-PRI-06 | Tabelas de preço versionadas com vigência, aplicação automática por data e auditoria de alterações | P1 | Professional |

**Downstream:** BillingSaaS, TenantOps, Finance, Quotes, CRM, Portal Admin.

---

### 1.4 Projects — Gestão de Projetos Técnicos

> Projetos que agrupam OS em fases e dependências. PRD reconhece como subespecificado (P1 de consolidação).

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-PRJ-01 | Cadastro de projeto com dados gerais, gerente responsável, cliente, contrato vinculado e tipo (implantação, adequação, manutenção programada) | P1 | Professional |
| FR-PRJ-02 | Fases do projeto com dependências, datas previstas/realizadas, avanço físico percentual e alertas de atraso | P1 | Professional |
| FR-PRJ-03 | Vinculação de OS a fases do projeto com propagação de status: fase só conclui quando todas as OS vinculadas estão concluídas | P1 | Professional |
| FR-PRJ-04 | Cronograma Gantt simplificado com visualização de fases, dependências e caminho crítico | P2 | Business |
| FR-PRJ-05 | Controle de horas e custos: alocadas vs realizadas por fase, custo real acumulado, margem atualizada em tempo real | P1 | Professional |
| FR-PRJ-06 | Faturamento por milestone: evento configurável por fase ou percentual de conclusão; geração automática de título a receber e NF-e | P1 | Professional |
| FR-PRJ-07 | Relatório de status do projeto exportável em PDF para envio ao cliente via Portal ou e-mail | P2 | Professional |

**Downstream:** WorkOrders, Contracts, Finance, Portal, Analytics, GED.

---

### 1.5 Procurement — Compras e Suprimentos

> Fluxo procurement-to-pay citado na matriz mas disperso entre compras, estoque e financeiro. PRD reconhece como subespecificado (P1).

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-PRO-01 | Requisição de compra com justificativa, centro de custo, urgência e vinculação a OS/projeto quando aplicável | P1 | Professional |
| FR-PRO-02 | Cotação com convite a fornecedores (mínimo 3), comparativo automático, critérios configuráveis (preço, prazo, qualidade) e seleção justificada | P1 | Professional |
| FR-PRO-03 | Pedido de compra com alçadas de aprovação por valor/categoria, bloqueio por fornecedor irregular e integração com Finance (título a pagar) | P1 | Professional |
| FR-PRO-04 | Recebimento com conferência: 3-way matching (pedido × NF × recebimento físico), registro de divergências e ação corretiva | P1 | Professional |
| FR-PRO-05 | Avaliação periódica de fornecedor com critérios configuráveis (prazo, qualidade, preço, conformidade documental) e score atualizado automaticamente | P2 | Business |
| FR-PRO-06 | Integração com SupplierPortal: fornecedor recebe cotação, envia proposta, confirma entrega e consulta pagamentos pelo portal | P2 | Business |

**Downstream:** Inventory, Finance, Fiscal, SupplierPortal, Quality, GED.

---

### 1.6 Recruitment — Recrutamento e Seleção

> Módulo embutido em §Vacância e Headcount Planning sem FRs próprios. Parte do Corporate Backbone.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-REC-01 | Cadastro de vagas vinculadas a departamento/nível com status: Planejada → Em Recrutamento → Ofertada → Preenchida → Cancelada | P2 | Professional |
| FR-REC-02 | Pipeline de candidatos por vaga com etapas configuráveis (triagem, entrevista, teste, proposta, admissão) | P2 | Professional |
| FR-REC-03 | Headcount planning 12 meses: comparativo atual vs planejado vs budget com alertas de desvio | P2 | Business |
| FR-REC-04 | Ao admitir, transferência automática do candidato para HR com pré-preenchimento de dados cadastrais e disparo de S-2200 (eSocial) | P2 | Professional |

**Downstream:** HR, eSocial, Organograma, Finance (budget de pessoal).

---

### 1.7 Email — Comunicação Interna e Mensageria

> Infraestrutura de comunicação transversal sem FRs próprios. Coberto parcialmente por FR-INT-03 e FR-POR-05.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-EML-01 | Templates de e-mail configuráveis por evento de negócio (OS criada, certificado emitido, cobrança, vencimento, convite) com variáveis dinâmicas e preview antes de envio | P0 | Starter |
| FR-EML-02 | Mural corporativo interno: comunicados com confirmação de leitura obrigatória para comunicados críticos, segmentação por filial/cargo/equipe | P2 | Professional |
| FR-EML-03 | Log de toda comunicação enviada: destinatário, canal, timestamp, status de entrega/abertura, vinculação a entidade de origem (OS, cliente, cobrança) | P0 | Starter |
| FR-EML-04 | Opt-out LGPD por canal: cliente pode desativar e-mail marketing sem afetar comunicações transacionais (cobrança, certificado, segurança) | P0 | Starter |

**Downstream:** Todos os módulos (transversal). Especialmente: CRM, Finance, Portal, WorkOrders, HR.

---

### 1.8 Innovation — Programas de Crescimento (reclassificado)

> Módulo classificado como "candidato modular" no PRD. Não tem FRs porque é um conjunto de add-ons opcionais que dependem de maturidade do tenant.

| ID | Descrição | Prioridade | Plano mínimo |
|---|---|---|---|
| FR-INN-01 | Programa de referral: cliente com NPS ≥ 9 recebe convite automático de indicação; indicação rastreada com recompensa configurável (desconto, crédito, brinde) | P2 | Business |
| FR-INN-02 | Gamificação de técnicos: pontos por OS concluída no prazo, calibração aprovada de primeira, avaliação positiva do cliente; ranking e premiação configurável | P2 | Business |
| FR-INN-03 | Programa de fidelização: acúmulo de pontos por OS, calibração, indicação e renovação de contrato; resgate configurável | P3 | Enterprise |
| FR-INN-04 | Garantia estendida como produto: regras de rentabilidade, provisão financeira, impacto em retenção e cross-sell automático na renovação | P3 | Enterprise |

**Downstream:** CRM (referral), Finance (comissão, provisão), HR/Mobile (gamificação), Portal (resgate).

**Nota:** Todos os FRs de Innovation são P2/P3 e add-ons opcionais. Nenhum é MVP.

---

## 2. FRs órfãos — módulos atribuídos

| FR | Descrição | Módulo atribuído | Justificativa |
|---|---|---|---|
| FR-SEG-07 | Importação assistida de dados legados | **Core** | Capacidade de plataforma, não módulo separado. Wizard de importação é feature do Core Platform. |
| FR-SEG-03 | Consentimento LGPD para mensageria | **Core** (Security) | Transversal, governado pelo módulo de segurança/privacidade do Core. Consumido por Email e Omnichannel. |
| FR-SEG-05 | Direito ao esquecimento LGPD | **Core** (Security) | Operação LGPD transversal. Execução orquestrada pelo Core que propaga para módulos afetados. |
| FR-SEG-06 | Portabilidade de dados LGPD | **Core** (Security) | Idem FR-SEG-05. Exportação estruturada coordenada pelo Core. |
| FR-BI-05 | OCR de documentos | **GED** | OCR é capacidade do repositório documental. Resultado armazenado como metadado do documento no GED. |
| FR-BI-05b | Análise de sentimento em feedbacks | **Analytics_BI** | Capacidade analítica avançada. P3/add-on. |
| FR-BI-05c | Resumo operacional diário | **Analytics_BI** | Idem. |
| FR-BI-05d | Recomendação de próxima ação comercial | **Analytics_BI** | Idem. |

---

## 3. Inventário atualizado de módulos

### Módulos adicionados ao inventário (2)

| Módulo | Macrodomínio | FRs | Status |
|---|---|---|---|
| **GED** | Technical & Metrology | FR-GED-01..07 | Transversal P0 — infraestrutura desde Starter |
| **LMS** | Corporate Backbone | FR-LMS-01..07 | P0 parcial (bloqueio de alocação) — módulo completo P1 |

### Total atualizado
- **45 módulos** (43 originais + GED + LMS)
- **152 FRs originais + 48 novos = 200 FRs canônicos**
- **0 módulos sem FRs**
- **0 FRs órfãos**

---

## 4. Resumo de impacto no MVP

| FRs novos críticos para MVP (P0) | Módulo |
|---|---|
| FR-GED-01, FR-GED-03, FR-GED-06, FR-GED-07 | GED |
| FR-LMS-03, FR-LMS-05 | LMS |
| FR-PRI-01, FR-PRI-02, FR-PRI-03 | Pricing |
| FR-EML-01, FR-EML-03, FR-EML-04 | Email |

**Total de FRs P0 adicionados ao escopo MVP:** 12 (de 48 novos).
Estes já estavam implícitos no PRD — agora estão formalizados como requisitos rastreáveis.

---

## Aprovação

- [x] PM aprova os 48 novos FRs — **aprovado 2026-04-12**
- [x] PM aprova adição de GED e LMS ao inventário — **aprovado 2026-04-12**
- [x] PM aprova atribuição dos 8 FRs órfãos — **aprovado 2026-04-12**
- [x] Epic-decomposer usa este documento como input complementar ao PRD
