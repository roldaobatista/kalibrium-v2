# Re-auditoria comparativa — Kalibrium V2 vs KALIBRIUM SAAS + sistema (ISOLADA)

**Data:** 2026-04-16
**Executado por:** auditor independente em contexto isolado (R3/R11)
**Baseline:** estado atual do repositório Kalibrium V2 pós-ampliação v1+v2 (NÃO foi lido o relatório da primeira auditoria `comparativa-externa-2026-04-16.md` nem o `PRD-ampliacao-2026-04-16-v2.md` nem os `*-backup-2026-04-16.md`, conforme regra de isolamento definida no brief).
**Fontes externas (read-only):** `C:\PROJETOS\KALIBRIUM SAAS`, `C:\PROJETOS\sistema`
**Metodologia:** conforme `docs/audits/BRIEF-auditoria-comparativa-externa.md`. Inventário estrutural via sandbox isolado, extração de candidatos em 4 dimensões (funções, funcionalidades, fluxos, personas), dedup contra baseline atual, classificação de impacto (alto/médio/baixo), recomendação MVP/PÓS/DESC/?.

---

## 1. Sumário executivo

### 1.1. Inventário bruto das fontes externas

**C:\PROJETOS\sistema** (mais rico do ponto de vista funcional):

- **38 módulos** funcionais documentados em `docs/.archive/modules/*.md` (Agenda, Alerts, Analytics_BI, Contracts, Core, CRM, ESocial, Email, Finance, Fiscal, FixedAssets, Fleet, HR, Helpdesk, Inmetro, Innovation, Integrations, Inventory, IoT_Telemetry, Lab, Logistics, Mobile, Omnichannel, Operational, Portal, Pricing, Procurement, Projects, Quality, Quotes, Recruitment, RepairSeals, Service-Calls, SupplierPortal, TvDashboard, WeightTool, WorkOrders + INTEGRACOES-CROSS-MODULE).
- **33 fluxos** documentados em `docs/.archive/fluxos/*.md` (ADMISSAO-FUNCIONARIO, AVALIACAO-DESEMPENHO, CERTIFICADO-CALIBRACAO, CHAMADO-EMERGENCIA, CICLO-COMERCIAL, CICLO-TICKET-SUPORTE, COBRANCA-RENEGOCIACAO, COMPETENCIA-PESSOAL-METROLOGIA, CONTESTACAO-FATURA, CONTROLE-PADROES-REFERENCIA, COTACAO-FORNECEDORES, DESLIGAMENTO-FUNCIONARIO, DESPACHO-ATRIBUICAO, DEVOLUCAO-EQUIPAMENTO, ESTOQUE-MOVEL, FALHA-CALIBRACAO, FATURAMENTO-POS-SERVICO, FECHAMENTO-MENSAL, GARANTIA, GESTAO-FROTA, INTEGRACOES-EXTERNAS, MANUTENCAO-PREVENTIVA, ONBOARDING-CLIENTE, OPERACAO-DIARIA, PORTAL-CLIENTE, PWA-OFFLINE-SYNC, RECRUTAMENTO-SELECAO, RELATORIOS-GERENCIAIS, REQUISICAO-COMPRA, RESCISAO-CONTRATUAL, SLA-ESCALONAMENTO, TECNICO-EM-CAMPO, TECNICO-INDISPONIVEL).
- **Backend Laravel** com ~75 subdiretórios `Http/Requests/` (indicador direto de áreas funcionais implementadas), ~90+ diretórios em `Models/` na amostra inicial, pastas Actions/Services/Events específicas (Calibration, Crm, CrmFieldManagement, Quote, Report, ServiceCall).
- **Frontend React** com ~49 pastas de páginas (admin, agenda, alertas, analytics, automacao, avancado, cadastros, calibracao, catalogo, ceo, chamados, configuracoes, contratos, crm, emails, equipamentos, estoque, financeiro, fiscal, fleet, ia, iam, importacao, inmetro, integracao, laboratorio, notificacoes, operacional, operational, orcamentos, os, portal, projects, qualidade, relatorios, rh, seguranca, tech, tecnicos, tv, vendas, ...).
- Pastas E2E em `frontend/e2e/`: auth, core, crm, cross-module, customers, financial, modules, pages, quotes, security, settings, stock, tech-pwa, work-orders.
- ERP completo e volumoso; auditoria interna do próprio sistema (`auditoria-sistema-2026-04-10.md`) confessa "projeto cresceu mais rápido do que a documentação".

**C:\PROJETOS\KALIBRIUM SAAS** (mais "PRD-completo" e menos profundo em fluxos):

- `docs/IDEIA.md` monumental (~5.8k linhas, indicado como PRD original).
- `docs/planning-artifacts/product-brief-KALIBRIUM-SAAS.md` (Product Brief consolidado) + `product-brief-KALIBRIUM-SAAS-distillate.md` (Detail Pack para PRD).
- 6 macrodomínios: Revenue Engine, Service Operations, Technical Quality & Metrology, People & Governance, Digital Experience & Relationship Channels, Intelligence/Automation.
- Auditoria detalhada de 4 épicos implementados (1 a 4 — Fundação, Ordens de Serviço/PWA, Laboratório ISO 17025, CRM Comercial).
- `kalibrium-app/app/Domains/` — organização DDD com Analytics, Commercial, Core, Finance, HumanResources, Integrations (parcial).
- `kalibrium-pwa/` — PWA separado com pages de service-tickets, sla-management, work-orders.
- Modelo de negócio: 8 planos R$300–R$3.000/mês, implantação assistida, 14-day trial sem cartão, Parceiros Silver/Gold/Platinum.

### 1.2. Números consolidados

| Dimensão | Candidatos extraídos | Já cobertos no baseline | Parciais | Gaps identificados |
|---|---:|---:|---:|---:|
| **Funções / capabilities** | 96 | 48 | 18 | 30 |
| **Funcionalidades / módulos** | 38 módulos do sistema + 6 macrodomínios do KSAAS | 14 domínios MVP + 9 E-pós | 5 | 9 |
| **Fluxos** | 33 do sistema + 4 épicos detalhados KSAAS | 13 jornadas documentadas | 6 parciais | 14 gaps/ausentes |
| **Personas** | 13 personas KSAAS + 9 papéis internos sistema | 9 personas (P1–P8 + P2B) | 3 | 5 |
| **TOTAL GAPS** | — | — | — | **58** |

### 1.3. Gaps por impacto

| Impacto | Total | Nota rápida |
|---|---:|---|
| **Alto** | 13 | Afetam persona primária, jornada central, área regulada, ou offline-first |
| **Médio** | 21 | Qualidade de vida, relatórios extras, integrações não-críticas |
| **Baixo** | 24 | Admin secundário, cosméticos, nichos |

### 1.4. Recomendação consolidada (síntese)

- **Promover para MVP-ampliação (7 itens):** retransmissão NFS-e com motivos canônicos (já parcial no baseline, mas merece ACs formais), pausa/retoma de SLA (cross-domain, já embutido em OS mas sem REQ), auto-dispatch básico ("round-robin mais simples" para atribuição de OS quando múltiplos técnicos livres), check de competência bloqueante antes de calibrar (ISO 17025 §6.2), OS de garantia com custo zero (evita discussão comercial), manutenção preventiva por recorrência contratual, handover de OS quando técnico fica indisponível em campo.
- **Promover para POST-MVP (21 itens):** despacho inteligente avançado (skill+geo+ETA), SLA multi-nível com penalidade %, portal do cliente ampliado (self-service), contestação formal de fatura, fechamento mensal orquestrado, cobrança com workflow automático por faixa de atraso, assistente conversacional/IA, omnichannel centralizado, analytics/BI avançado, WhatsApp Business Templates aprovados, gamificação, TvDashboard, eSocial, SPED/ECD/ECF, procurement/compras, projects/PPM, FixedAssets/CIAP, Logistics reversa, IoT telemetria, dispute management, commission campaigns, onboarding wizard do cliente.
- **Descartar (4 itens):** white-label, multi-país/LatAm, ERP de manufatura, marketplace de parceiros (explicitamente fora do escopo em ambas as fontes externas e já alinhado ao baseline).
- **Decisão PM (?)** (3 itens com trade-off estratégico): RTC 2026 / Reforma Tributária (IBS/CBS/cIndOp) — baseline não trata; Programa Confia Enterprise; monitoramento de padrões de referência com alerta de drift + SPC (baseline JÁ tem REQ-MET-009/010 — CONFIRMADO coberto).

### 1.5. Opinião independente sobre a ampliação já feita

O baseline atual (86 REQs em 14 domínios, 13 jornadas, 23 épicos, 9 personas) é **coerente, bem organizado e substancialmente mais maduro** do que seria de esperar para uma decisão tomada em uma única rodada de intake. A identificação dos domínios offline-first (FLD, UMC, VHL, DSP, INV, CRM, SEC, SYN) é de altíssima qualidade e se alinha com os sinais empíricos encontrados no `sistema/docs/.archive/fluxos/PWA-OFFLINE-SYNC.md` + `TECNICO-EM-CAMPO.md`.

**Entretanto, identifiquei alguns sinais de ampliação acima do necessário:**

- REQ-MET-009/010 (SPC + drift) — está correto conceitualmente (ISO 17025 §6.5 pede rastreabilidade) MAS a fonte externa `sistema` trata isso como fluxo maduro (`CONTROLE-PADROES-REFERENCIA.md` + `FALHA-CALIBRACAO.md` = 2 fluxos), então há cobertura conceitual justa. Baseline não está inflado aqui.
- REQ-CMP-007 (backup por tenant com verificação) — correto. Externas confirmam essa necessidade (IDEIA KSAAS, sistema em produção).
- REQ-CRM-008 (revalidação proativa) — presente em externas (fluxo CERTIFICADO-CALIBRACAO + jornada CRM do KSAAS distillate menciona RFM e lifecycle), parcialmente coberto no baseline.

**Sinais de ampliação sem evidência forte:**

- Jornada 13 (Revalidação proativa) está documentada como NOVA v2 mas o mesmo conceito aparece disperso em CRM.md (KSAAS) + cadências comerciais (sistema `CRM/crm_sequences`). Concordo com a inclusão mas o escopo pode ser menor no MVP (só o alerta, não o follow-up automatizado).
- Persona 8 (Responsável de Qualidade) — justificada pela ISO 17025. KSAAS brief confirma persona "Qualidade/Compliance". Boa ampliação.

**Onde o baseline NÃO ampliou e deveria considerar:** lista de 13 gaps de alto impacto abaixo na Seção 7.

---


## 2. Funções (capabilities) — gaps

Funções aqui = capabilities granulares extraídas das externas que podem ou não estar cobertas por REQs do baseline. Legenda: ✓ coberto, ~ parcial, ✗ gap.

### 2.1. Metrologia e laboratório

| # | Função extraída | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-MET-01 | Cálculo de incerteza GUM/JCGM 100:2008 | KSAAS Epic 3 / IDEIA / sistema Lab.md | ✓ REQ-MET-005 | — |
| F-MET-02 | Cálculo EA-4/02 como alternativa configurável | KSAAS distillate | ✗ | Médio |
| F-MET-03 | Terminologia VIM 3ª edição sugerida em campos livres | KSAAS distillate | ✗ | Baixo |
| F-MET-04 | Wizard poka-yoke 8 etapas para calibração | KSAAS Epic 3 (Story 3.4) | ~ (REQ-MET-004 implícito, sem wizard guiado) | Médio |
| F-MET-05 | Dual sign-off para aprovação de certificado | KSAAS Epic 3 (Story 3.7) | ✗ | Alto |
| F-MET-06 | Assinatura digital ICP-Brasil em certificado | KSAAS Epic 3 (Story 3.7) | ✗ | Médio |
| F-MET-07 | Verificação pública de certificado (QR/link) | KSAAS Epic 3 (Story 3.8) | ~ (REQ-FLX-005 portal cliente, sem verificação pública anônima) | Médio |
| F-MET-08 | Controle ambiental via IoT/telemetria | KSAAS Epic 3 (Story 3.9) + sistema IoT_Telemetry.md | ✗ | Baixo (POST-MVP) |
| F-MET-09 | Competência de pessoal documentada (ISO 17025 §6.2) + bloqueio de calibração por técnico não-qualificado | sistema/flx/COMPETENCIA-PESSOAL-METROLOGIA | ✗ | **Alto** |
| F-MET-10 | Lifecycle completo de padrão de referência (acquired→in_calibration→active→in_use→suspended→retired) | sistema/flx/CONTROLE-PADROES-REFERENCIA | ~ (REQ-MET-001 cobre cadastro, não todos os estados) | Médio |
| F-MET-11 | Falha de padrão com suspensão automática de certificados emitidos | sistema/flx/FALHA-CALIBRACAO + `StandardFailureRecord` + `CertificateSuspension` | ✗ | **Alto** (invalida certificados retroativamente) |
| F-MET-12 | Templates de certificado versionados por tenant | KSAAS Epic 3 (Story 3.6) | ~ (REQ-MET-006 cobre emissão, sem versionamento) | Médio |
| F-MET-13 | Pesos-padrão com histórico de verificação (`WeightAssignment`) | sistema WeightTool.md | ✓ REQ-MET-001 + UMC-002 + INV-001/004 | — |
| F-MET-14 | Selos de reparo e lacres numerados (RepairSeal + InmetroSeal) | sistema RepairSeals.md | ✗ | Médio (Portaria 671 + Inmetro — obrigatório em balanças) |
| F-MET-15 | Painel INMETRO com vencimentos e alertas | sistema Inmetro.md | ~ (REQ-INV-004, REQ-CRM-008) | Baixo |

### 2.2. Ordens de serviço e operação de campo

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-OS-01 | Máquina de estados completa da OS (15+ estados) | KSAAS Epic 2 / sistema flx/OPERACAO-DIARIA | ~ (REQ-FLX-002 cobre macro) | Médio |
| F-OS-02 | Despacho automático de OS (round-robin + least-loaded + skill-match + geo) | sistema/flx/DESPACHO-ATRIBUICAO + `AutoAssignmentRule` | ✗ | **Alto** |
| F-OS-03 | Roteirização de visitas com GPS + ETA | KSAAS Epic 2 (Story 2.5) | ~ (REQ-FLD-006 mapa do dia, sem otimização) | Médio |
| F-OS-04 | SLA por contrato com timer automático, pausa/retoma, escalonamento multi-nível | sistema/flx/SLA-ESCALONAMENTO | ✗ | **Alto** |
| F-OS-05 | Penalidade financeira automática por SLA estourado (desconto %) | sistema SLA + Contracts | ✗ | Médio |
| F-OS-06 | Chat interno por OS | KSAAS Epic 2 (Story 2.8b) | ✗ | Baixo |
| F-OS-07 | Survey NPS pós-OS | KSAAS + sistema Operational.md | ✗ | Médio |
| F-OS-08 | Templates de OS reutilizáveis + Sub-OS | KSAAS Epic 2 (Story 2.8) | ✗ (já em POST-MVP §2.3) | Baixo |
| F-OS-09 | Histórico completo do equipamento | KSAAS Epic 2 (Story 2.8) | ✓ REQ-MET-007 | — |
| F-OS-10 | OS como documento técnico com PDF + QR | KSAAS Epic 2 (Story 2.7) | ~ (certificado PDF sim, OS PDF não explícito) | Baixo |
| F-OS-11 | Checklist de recebimento (ISO 17025 §7.4) | sistema/flx/CERTIFICADO-CALIBRACAO | ✗ | Médio |
| F-OS-12 | Re-despacho automático quando técnico fica indisponível + notificação de clientes | sistema/flx/TECNICO-INDISPONIVEL | ✗ | **Alto** |
| F-OS-13 | OS de garantia custo zero + cost allocation (empresa/fornecedor/seguro) | sistema/flx/GARANTIA | ✗ | Médio-**Alto** |
| F-OS-14 | Chamado de emergência com SLA diferenciado (P1 crítico) | sistema/flx/CHAMADO-EMERGENCIA | ✗ | Médio |
| F-OS-15 | Manutenção preventiva automática gerada por contrato (recorrência) | sistema/flx/MANUTENCAO-PREVENTIVA | ✗ | **Alto** |
| F-OS-16 | Geofence (entrada no raio do cliente = auto check-in) | KSAAS brief | ~ (REQ-FLD-002 manual) | Médio |

### 2.3. Comercial / CRM

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-CRM-01 | Pipeline de vendas com estágios e forecast | KSAAS Epic 4 + sistema CRM.md | ~ (REQ-CRM-007 pipeline) | Médio |
| F-CRM-02 | Visão 360 do cliente | KSAAS Epic 4 | ~ (REQ-TEN-003 + histórico) | Médio |
| F-CRM-03 | Scoring RFM + alerta de churn | KSAAS Epic 4 (Story 4.3) | ✗ (já em POST-MVP §2.1) | Médio |
| F-CRM-04 | Cadências multi-canal (e-mail, WhatsApp, ligação) | KSAAS Epic 4 (Story 4.4) | ~ (REQ-CRM-006 básico) | Médio |
| F-CRM-05 | Propostas interativas com catálogo de serviços | KSAAS Epic 4 (Story 4.5) | ~ (REQ-CRM-004 orçamento offline) | Médio |
| F-CRM-06 | Handoff CRM → Operação (deal ganho gera OS) | KSAAS Epic 4 + sistema/flx/ONBOARDING-CLIENTE | ~ (REQ-CRM-005) | Médio |
| F-CRM-07 | Modelos de receita: transacional, recorrente, por marco | KSAAS Epic 4 (Story 4.6) | ✗ | Médio |
| F-CRM-08 | Ciclo pós-venda: retenção + renovação + expansão | KSAAS Epic 4 (Story 4.7) | ~ (REQ-CRM-008 revalidação) | Médio |
| F-CRM-09 | Lead capture via web form com roteamento | KSAAS Epic 4 (Story 4.8) | ✗ | Baixo |
| F-CRM-10 | Pricing inteligente (histórico por cliente, simulador de margem) | KSAAS distillate + sistema Pricing.md | ✗ | Médio |
| F-CRM-11 | Comissionamento (campanhas, disputas, metas, eventos, settlement) | sistema CRM.md | ✗ | Médio |
| F-CRM-12 | Lead scoring rules | sistema `CrmLeadScore`, `CrmLeadScoringRule` | ✗ | Baixo |
| F-CRM-13 | Territórios de vendas | sistema `CrmTerritory` | ✗ | Baixo |
| F-CRM-14 | Onboarding estruturado do cliente (checklist 7 passos + SLA 7d) | sistema/flx/ONBOARDING-CLIENTE | ✗ | Médio |
| F-CRM-15 | Contratos recorrentes com renovação automática + medição | sistema Contracts.md | ✗ | Médio |

### 2.4. Fiscal e financeiro

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-FIS-01 | NF-e (produto) | KSAAS brief + sistema Fiscal.md | ✗ (só NFS-e em REQ-FIS-001) | Médio (se cliente vender equipamento) |
| F-FIS-02 | NFS-e com regras municipais versionadas | KSAAS brief | ~ (REQ-FIS-001 cobre emissão, não regras municipais) | Médio |
| F-FIS-03 | RTC 2026 — IBS/CBS/cIndOp, Ambiente Nacional NFS-e | KSAAS brief "Recorte Fiscal 2026" | ✗ | **Alto** |
| F-FIS-04 | eSocial (SPED, DCTF, EFD-Contribuições, ECD, ECF) | KSAAS brief + sistema ESocial.md | ✗ | Baixo (POST-MVP) |
| F-FIS-05 | DARF automático por tributo e competência | KSAAS distillate | ✗ | Baixo |
| F-FIS-06 | Alerta de proximidade do teto do Simples Nacional | KSAAS distillate | ✗ | Médio |
| F-FIS-07 | Bloqueio de duplo recolhimento (CPP no DAS) | KSAAS distillate | ✗ | Baixo |
| F-FIS-08 | Tipos de título AR variados (fatura/boleto/duplicata/cheque/promissória) | KSAAS distillate | ~ (REQ-FIS-005 conciliação manual) | Baixo |
| F-FIS-09 | Reconciliação bancária via CNAB + OFX | sistema Finance.md | ✗ (já em POST-MVP §2.5) | Baixo |
| F-FIS-10 | Retenção fiscal (ISS, IR, INSS, PIS, COFINS) por regime | sistema Fiscal.md | ✓ REQ-FIS-008 (v2) | — |
| F-FIS-11 | Fatura em lote pós-fechamento | sistema/flx/FECHAMENTO-MENSAL | ✗ | Médio |
| F-FIS-12 | Fechamento mensal orquestrado | sistema/flx/FECHAMENTO-MENSAL | ✗ | Médio |
| F-FIS-13 | Contestação formal de fatura com state machine | sistema/flx/CONTESTACAO-FATURA | ✗ | Médio |
| F-FIS-14 | Cobrança automática por faixa de atraso (D+1/7/15/30/60/90) | sistema/flx/COBRANCA-RENEGOCIACAO | ✗ | Médio-Alto |
| F-FIS-15 | Renegociação de dívida com parcelamento | sistema Finance.md | ✗ | Médio |
| F-FIS-16 | DRE por tenant | sistema Finance.md | ~ (REQ-OPL-004 CSV, DRE formal não) | Baixo |
| F-FIS-17 | Plano de contas + centros de custo | sistema Finance.md | ✗ | Baixo |

### 2.5. RH, qualidade e governança

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-RH-01 | Ponto digital (Portaria 671/2021) | KSAAS brief + sistema HR.md | ✗ | Baixo (POST-MVP) |
| F-RH-02 | Espelho de ponto + violações CLT | sistema HR.md | ✗ | Baixo |
| F-RH-03 | eSocial eventos S-2200/S-2299/S-2230 | sistema ESocial.md | ✗ | Baixo |
| F-RH-04 | Admissão / desligamento / rescisão / avaliação | sistema 4 fluxos | ✗ | Baixo |
| F-RH-05 | Recrutamento e seleção | sistema Recruitment.md | ✗ | Baixo |
| F-RH-06 | Férias + ausências | sistema HR.md | ✗ | Baixo |
| F-Q-01 | CAPA (Corrective and Preventive Action) | sistema Quality.md | ✗ | Médio |
| F-Q-02 | Auditoria interna da qualidade | sistema Quality.md | ~ (REQ-CMP-001/002) | Médio |
| F-Q-03 | Não-conformidade com investigação (5-Whys) | sistema Quality.md | ✗ | Médio |
| F-Q-04 | Biometric consent | sistema `BiometricConsent` | ✓ REQ-SEC-001 + REQ-CMP-004 | — |
| F-GOV-01 | IAM/RBAC granular + policies por domínio | sistema Core + KSAAS Epic 1 | ~ (REQ-TEN-002 papéis) | Médio |
| F-GOV-02 | Multi-tenant schema-per-tenant | KSAAS TECHNICAL-DECISIONS | ~ (REQ-TEN-005 isolamento lógico, decisão técnica não declarada) | Alto (decisão de arquitetura) |
| F-GOV-03 | 2FA (SMS/TOTP) | KSAAS Epic 1 | ✗ | Médio |
| F-GOV-04 | Gestão de sessões ativas + revogação | KSAAS Epic 1 | ~ (REQ-SEC-005) | Médio |
| F-GOV-05 | Auditoria append-only de TODOS os dados | KSAAS brief + sistema AuditLog | ~ (REQ-CMP-001 só calibração) | Médio |

### 2.6. Integrações, comunicação e portal

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-INT-01 | WhatsApp Business API com templates Meta aprovados | sistema/flx/INTEGRACOES | ~ (REQ-FLX-004 consentimento; sem template/provider) | Médio |
| F-INT-02 | SMS provider | sistema Integrations.md | ✗ | Baixo |
| F-INT-03 | Push notifications iOS/Android | KSAAS brief | ✓ REQ-FLX-007 (v2) | — |
| F-INT-04 | E-mail transacional com templates | sistema Email.md | ~ (REQ-FIS-003) | Baixo |
| F-INT-05 | Google Calendar | sistema `GoogleCalendarService` | ✗ | Baixo |
| F-INT-06 | Omnichannel inbox centralizada | sistema Omnichannel.md | ✗ | Baixo |
| F-INT-07 | Webhooks outbound expostos | sistema Integrations.md | ✗ | Baixo |
| F-INT-08 | Integração Auvo (FSM brasileiro) | sistema `AuvoImport` | ✗ | Baixo |
| F-INT-09 | CNAB (boleto bancário) | sistema `CnabService` | ✗ | Baixo |
| F-INT-10 | Receita Federal CNPJ validation em tempo real | KSAAS distillate | ✗ | Médio |
| F-PORT-01 | Portal do cliente com convite + primeiro acesso seguro | sistema/flx/PORTAL-CLIENTE | ~ (REQ-FLX-005 sem convite formal) | Médio |
| F-PORT-02 | Portal B2B do fornecedor | sistema SupplierPortal.md | ✗ | Baixo |
| F-PORT-03 | Verificação pública anônima de certificado (QR público) | KSAAS Epic 3 (Story 3.8) | ✗ | Médio |
| F-PORT-04 | NPS survey via portal | sistema Operational.md | ✗ | Baixo |

### 2.7. Analytics, BI, alertas e automação

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-BI-01 | Dashboard executivo (receita, margem, churn, produtividade) | KSAAS brief + sistema flx/RELATORIOS | ~ (REQ-OPL-001 operacional) | Médio |
| F-BI-02 | KPIs por perfil (Dono/Coord/Fin/Lab/RH) | sistema flx/RELATORIOS | ~ | Médio |
| F-BI-03 | Data exports custom (`DataExportJob`, `AnalyticsDataset`) | sistema Analytics_BI.md | ~ (REQ-OPL-004 CSV básico) | Baixo |
| F-BI-04 | Agendamento de relatórios por email | sistema `ScheduledReportJob` | ✗ | Baixo |
| F-BI-05 | TV dashboard operacional | sistema TvDashboard.md | ✗ | Baixo |
| F-BI-06 | Alertas configuráveis (`SystemAlert`, `AlertConfiguration`) | sistema Alerts.md | ~ (REQ-INV-004, REQ-CRM-008) | Médio |
| F-AI-01 | Assistente conversacional / IA | KSAAS brief | ✗ | Baixo |
| F-AI-02 | Smart alerts CRM | sistema `CrmSmartAlert` | ✗ | Baixo |
| F-AUT-01 | Regras de automação (`AutomationRule`) | sistema Core.md | ✗ | Baixo |
| F-AUT-02 | Gamificação / badges | sistema Innovation.md | ✗ | Baixo (nicho) |

### 2.8. Estoque, frota, ativos, procurement

| # | Função | Fonte | Baseline | Impacto |
|---|---|---|---|---|
| F-INV-01 | Estoque móvel (técnico + armazém pessoal) | sistema/flx/ESTOQUE-MOVEL | ✓ REQ-INV-001 + INV-003 | — |
| F-INV-02 | Reposição de estoque (reabastecimento de veículo) | sistema/flx/ESTOQUE-MOVEL | ~ (REQ-INV-002 movimento, sem regra automática) | Médio |
| F-INV-03 | Batch / lotes / serial | sistema `Batch` | ~ (REQ-MET-001 serial) | Baixo |
| F-INV-04 | Asset tag scan (QR code ativo) | sistema `AssetTag`, `AssetTagScan` | ✗ | Baixo |
| F-FLEET-01 | Gestão de frota completa | sistema Fleet.md + flx/GESTAO-FROTA | ✓ REQ-VHL-001/002/003 + REQ-UMC-001/003 | — |
| F-FLEET-02 | Logística reversa / rastreamento | sistema Logistics.md | ✗ | Baixo |
| F-ASSET-01 | Ativo imobilizado com depreciação (CIAP) | sistema FixedAssets.md | ✗ | Baixo |
| F-PRC-01 | Requisição de compra com alçada | sistema/flx/REQUISICAO-COMPRA | ✗ | Baixo |
| F-PRC-02 | Cotação multi-fornecedor | sistema/flx/COTACAO-FORNECEDORES | ✗ | Baixo |
| F-PRC-03 | Devolução de equipamento | sistema/flx/DEVOLUCAO-EQUIPAMENTO | ✗ | Baixo |


## 3. Funcionalidades (módulos macro) — gaps

Enquanto a Seção 2 detalha capabilities granulares, aqui olho em nível de módulo/área e comparo com os 14 domínios MVP do baseline (TEN, MET, FLX, FIS, OPL, CMP, FLD, UMC, VHL, DSP, INV, CRM, SEC, SYN) + 23 épicos.

### 3.1. Mapeamento modulo-externo → cobertura baseline

| Módulo / área externa | Baseline MVP | Baseline POST-MVP | Verdict |
|---|---|---|---|
| Core (Tenant, User, Auditoria base) | ✓ TEN | — | Coberto |
| Lab (EquipmentCalibration) | ✓ MET | — | Coberto |
| WeightTool (pesos-padrão) | ✓ MET + INV | — | Coberto |
| Inmetro (lifecycle balança) | ~ MET (parcial, sem painel RBC dedicado) | — | Parcial |
| RepairSeals (selos/lacres numerados) | ✗ | ✗ | **Gap médio** |
| WorkOrders (OS máquina de estados, PDF, QR) | ~ FLX (macro) | — | Parcial |
| Service-Calls (chamados técnicos com SLA) | ✗ | ✗ | **Gap alto** (se ICP tem suporte reativo) |
| Helpdesk (tickets internos) | ✗ | ✗ (já em POST-MVP 1.5) | Registrado |
| Operational (checklists, rotas, NPS, agendamentos) | ~ FLD + OPL | — | Parcial |
| Agenda (AgendaItem, subtasks, watchers) | ~ FLX + UMC | — | Parcial (sem colaboração de tarefa) |
| Mobile (PWA, user prefs) | ✓ SEC + SYN + FLD | — | Coberto |
| CRM (deals, pipelines, cadências, territórios) | ~ CRM | ✗ (POST §2.1, §2.4) | Parcial |
| Quotes (orçamentos com 11 estados) | ~ CRM-004 | — | Parcial (só offline MVP) |
| Contracts (contratos recorrentes, medição) | ✗ | ✗ | **Gap médio** |
| Pricing (catálogo + simulador de margem) | ✗ | ✗ | **Gap médio** |
| Finance (AR/AP, comissão, invoicing, DRE) | ~ FIS (parcial AR) | ✗ (POST §2.5, §2.9) | Parcial |
| Fiscal (NF-e/NFS-e, regras municipais, RTC 2026) | ~ FIS | ✗ | **Gap alto** (RTC 2026) |
| FixedAssets (CIAP, depreciação) | ✗ | ✗ | Gap baixo |
| HR (jornada, ponto, férias) | ✗ | ✗ | Gap baixo |
| ESocial (SPED, obrigações trabalhistas) | ✗ | ✗ | Gap baixo |
| Recruitment (candidatos, processo seletivo) | ✗ | ✗ | Gap baixo |
| Email (templates, accounts) | ~ FLX-003 email básico | — | Parcial |
| Integrations (WhatsApp, Google Calendar, Auvo, Webhooks) | ~ FLX-004 WhatsApp consent | — | Parcial |
| Portal cliente (convite, autoatendimento, tickets) | ~ FLX-005 | ✗ (POST §1.4) | Parcial |
| SupplierPortal (B2B fornecedor) | ✗ | ✗ | Gap baixo |
| Omnichannel (inbox centralizada) | ✗ | ✗ | Gap baixo |
| Procurement (compras, cotação, alçada) | ✗ | ✗ | Gap baixo |
| Logistics (WMS/TMS leve, reversa) | ✗ | ✗ | Gap baixo |
| Inventory (estoque multi-nível) | ✓ INV | — | Coberto |
| Fleet (frota completa) | ✓ VHL + UMC | — | Coberto |
| IoT_Telemetry (captura serial contínua) | ✗ | ✗ (POST §2.2) | Registrado |
| Quality (CAPA, auditoria, não-conformidade) | ~ CMP-001/002 | ✗ | Parcial |
| Projects (PPM, milestones) | ✗ | ✗ | Gap baixo |
| Alerts (`SystemAlert`, config) | ~ INV-004, CRM-008 | — | Parcial |
| Analytics_BI (data export custom, datasets) | ~ OPL-004 CSV | ✗ (POST §2.6) | Parcial |
| TvDashboard (cameras, painéis TV) | ✗ | ✗ | Gap baixo |
| Innovation (gamificação) | ✗ | ✗ | Descartar |
| KSAAS — Macrodomínio "Intelligence & Automation" (IA, assistente) | ✗ | ✗ | Gap baixo |
| KSAAS — Programa de Parceiros Silver/Gold/Platinum | ✗ | ✗ | Gap baixo (não é ICP) |
| KSAAS — Billing recorrente SaaS nativo + Self-service onboarding (trial 14d) | ✗ | ✗ | **Gap alto** (para o negócio SaaS do Kalibrium, não do tenant) |

### 3.2. Gaps reais por macrodomínio (com impacto)

- **Gap M1 — Service-Calls (chamados técnicos com SLA):** o baseline fundiu chamados na jornada da OS, mas no `sistema` é um módulo separado com estados próprios e eventos distintos. Se ICP inclui empresa que oferece "atendimento reativo" (não só calibração programada), isto é gap alto.
- **Gap M2 — Contracts recorrentes com medição:** baseline tem contrato implícito na OS, mas não contrato com clausulas de SLA, renovação automática, medição mensal. ISO 17025 não exige, mas cliente industrial médio exige.
- **Gap M3 — Pricing avançado + catálogo de serviços:** baseline trata preço como parte do orçamento, mas não tem tabela de preço versionada por cliente/contrato. Importante para laboratório com múltiplos clientes recorrentes.
- **Gap M4 — Fiscal RTC 2026:** baseline não menciona IBS/CBS/cIndOp nem versionamento por município/competência. Crítico se MVP vai operar em 2026+ e o cliente ICP emite NFS-e.
- **Gap M5 — Quality (CAPA + não-conformidade):** ISO 9001 / ISO 17025 pedem tratamento formal de não-conformidade. Baseline tem log de calibração mas não workflow de CAPA.
- **Gap M6 — Integrations (WhatsApp templates Meta-approved):** REQ-FLX-004 cobre consentimento, mas WhatsApp Business API exige templates aprovados pela Meta antes de enviar — se o provider não estiver cadastrado, nada funciona. Impacto médio-alto em jornada 1 passo 1.11 (entrega por WhatsApp).
- **Gap M7 — Billing SaaS (Stripe/Pagar.me) + onboarding self-service:** baseline não menciona como o Kalibrium SaaS cobra do tenant. KSAAS trata isso como core do modelo. Se o V2 é SaaS, precisa de trial, provisionamento automatizado, cobrança mensal do tenant.
- **Gap M8 — Programa de parceiros (revendedores) Silver/Gold/Platinum:** opcional, mas KSAAS dedica parágrafo. Se o modelo de GTM inclui revenda, precisa de multi-CNPJ e comissão por tenant.
- **Gap M9 — IAM granular com policies por domínio:** REQ-TEN-002 tem papéis, mas não Spatie Permission-style com policies por feature. Impacto: escalabilidade pro MVP avançar.

---

## 4. Fluxos — gaps

Os 33 fluxos do `sistema` são o material mais rico de comparação. Cruzando contra as 13 jornadas do baseline:

### 4.1. Fluxos do sistema vs jornadas baseline

| # | Fluxo sistema | Jornada baseline | Status |
|---|---|---|---|
| FL-01 | CERTIFICADO-CALIBRACAO | J1 (pedido fim a fim) + J6/J7 (campo) | ✓ Coberto (embora baseline menos detalhado em estados) |
| FL-02 | TECNICO-EM-CAMPO | J6 + J7 | ✓ Coberto |
| FL-03 | PWA-OFFLINE-SYNC | J6/J7 + J10 | ✓ Coberto |
| FL-04 | OPERACAO-DIARIA | J5 Admin tenant | ~ Parcial (baseline tem esqueleto) |
| FL-05 | ESTOQUE-MOVEL | J6/J7 + J11.4 | ✓ Coberto |
| FL-06 | GESTAO-FROTA | J11 | ✓ Coberto |
| FL-07 | CICLO-COMERCIAL | J9 Vendedor | ~ Parcial (J9 só offline; falta pipeline completo + contratos) |
| FL-08 | FATURAMENTO-POS-SERVICO | J1.9 NFS-e | ~ Parcial (falta batch invoicing + retenção + DARF) |
| FL-09 | RELATORIOS-GERENCIAIS | Nenhuma jornada específica | **Gap médio** (falta jornada "gestor consome relatórios") |
| FL-10 | PORTAL-CLIENTE | J3 (esqueleto) | ~ Parcial (convite formal + tickets ausentes) |
| FL-11 | ONBOARDING-CLIENTE | Implícito em J1 | **Gap médio** (falta onboarding estruturado pós-venda) |
| FL-12 | INTEGRACOES-EXTERNAS | Transversal | ~ Parcial (WhatsApp templates, Google Calendar, etc) |
| FL-13 | DESPACHO-ATRIBUICAO | Implícito em J6/J7 | **Gap alto** (auto-atribuição ausente) |
| FL-14 | SLA-ESCALONAMENTO | Nenhuma | **Gap alto** |
| FL-15 | MANUTENCAO-PREVENTIVA | Nenhuma | **Gap alto** (OS preventivas por contrato) |
| FL-16 | COMPETENCIA-PESSOAL-METROLOGIA | Nenhuma | **Gap alto** (bloqueio ISO 17025 §6.2) |
| FL-17 | CONTROLE-PADROES-REFERENCIA | ~ REQ-MET-001 + INV-004 | ~ Parcial (lifecycle completo não documentado) |
| FL-18 | FALHA-CALIBRACAO | Nenhuma | **Gap alto** (suspensão retroativa de certificados) |
| FL-19 | TECNICO-INDISPONIVEL | Nenhuma | **Gap alto** (re-despacho automático) |
| FL-20 | CHAMADO-EMERGENCIA | Nenhuma | Gap médio |
| FL-21 | CONTESTACAO-FATURA | Nenhuma | Gap médio |
| FL-22 | COBRANCA-RENEGOCIACAO | Nenhuma | Gap médio-alto |
| FL-23 | FECHAMENTO-MENSAL | Nenhuma | Gap médio |
| FL-24 | GARANTIA | Nenhuma | Gap médio-alto (OS custo zero) |
| FL-25 | DEVOLUCAO-EQUIPAMENTO | Implícito em J1.11 | Gap baixo |
| FL-26 | REQUISICAO-COMPRA | Nenhuma | Gap baixo |
| FL-27 | COTACAO-FORNECEDORES | Nenhuma | Gap baixo |
| FL-28 | ADMISSAO-FUNCIONARIO | Nenhuma | Gap baixo (POST-MVP HR) |
| FL-29 | DESLIGAMENTO-FUNCIONARIO | Nenhuma | Gap baixo |
| FL-30 | RESCISAO-CONTRATUAL | Nenhuma | Gap baixo |
| FL-31 | RECRUTAMENTO-SELECAO | Nenhuma | Gap baixo |
| FL-32 | AVALIACAO-DESEMPENHO | Nenhuma | Gap baixo |
| FL-33 | CICLO-TICKET-SUPORTE | Nenhuma | Gap baixo (POST-MVP Helpdesk) |

### 4.2. Sumário dos gaps de fluxo (alto impacto)

1. **FL-13 Despacho e atribuição automática** — `AutoAssignmentRule` com 3 estratégias (round-robin, least-loaded, skill-match). Se laboratório tem 3+ técnicos disponíveis, perder tempo atribuindo manualmente. **[Alto]**
2. **FL-14 SLA e escalonamento** — monitoramento em tempo real, pausa/retoma, notificação, penalidade. Contrato com SLA é diferencial comercial. **[Alto]**
3. **FL-15 Manutenção preventiva automática** — scheduler gera OS recorrente a partir de contrato. Sem isso, todas as OS preventivas têm que ser lembradas manualmente. **[Alto]**
4. **FL-16 Competência de pessoal** — ISO 17025 §6.2 exige. Sistema bloqueia técnico sem competência. **[Alto]**
5. **FL-18 Falha de padrão** — quando padrão falha, certificados emitidos ficam contestáveis. Pipeline formal é requisito de qualidade. **[Alto]**
6. **FL-19 Técnico indisponível** — re-despacho automático + notificação de clientes afetados. Sem isso, OS fica órfã. **[Alto]**
7. **FL-24 OS de garantia** — classificação + cost allocation. Evita bronca comercial. **[Médio-Alto]**
8. **FL-22 Cobrança por faixa de atraso** — automação de cobrança escala receita. **[Médio-Alto]**
9. **FL-17 Lifecycle completo padrão de referência** — estados formais + transições auditáveis. **[Médio]**
10. **FL-21 Contestação formal de fatura** — sem pipeline, vira ruído no financeiro. **[Médio]**
11. **FL-11 Onboarding estruturado do cliente** — checklist 7 passos + SLA 7d úteis. **[Médio]**
12. **FL-23 Fechamento mensal orquestrado** — evita OS órfã e rombo no fechamento. **[Médio]**
13. **FL-20 Chamado de emergência** — SLA diferenciado para P1 crítico. **[Médio]**
14. **FL-09 Relatórios gerenciais por perfil** — KPIs separados Dono/Coord/Fin/Lab/RH. **[Médio]**

---

## 5. Personas — gaps

### 5.1. Inventário comparativo

**Baseline (9 personas):** P1 Sócio-gerente, P2 Técnica bancada, P2B Técnico campo, P3 Cliente final, P4 Motorista UMC, P5 Vendedor externo, P6 Gestor campo, P7 Atendente, P8 Qualidade/ISO 17025.

**KSAAS (12 personas do distillate):** Diretoria/Gestão Executiva, Produto/Operações, Comercial/CRM, Atendimento/Helpdesk, Coordenação Técnica, Técnico de Campo, Laboratório/Metrologia, Financeiro/Fiscal, RH/DP, Qualidade/Compliance, Cliente Final, Fornecedor/Parceiro.

**Sistema (papéis/roles inferidos):** Dono/Diretor, Coordenador, Financeiro, Laboratório, RH, Técnico (campo), Gestor (agenda), Atendente (helpdesk), Administrador.

### 5.2. Dedup e gaps

| Persona candidata | Baseline | Status |
|---|---|---|
| Sócio-gerente / Dono | ✓ P1 | Coberto |
| Técnico bancada | ✓ P2 | Coberto |
| Técnico campo | ✓ P2B | Coberto |
| Cliente final comprador | ✓ P3 | Coberto |
| Motorista UMC | ✓ P4 | Coberto (único, só no Kalibrium) |
| Vendedor externo | ✓ P5 | Coberto |
| Gestor em campo | ✓ P6 | Coberto |
| Atendente escritório | ✓ P7 | Coberto |
| Qualidade / ISO 17025 | ✓ P8 | Coberto (ampliado v2 — bom) |
| **Diretoria / CFO** | ✗ | Gap alto (decisão executiva é coalizão com CFO) |
| **Coordenação Técnica / Despachador** | ✗ | Gap médio (quem prioriza fila em laboratório médio) |
| **Financeiro / Contas a Receber** | ~ P7 (mas P7 é administrativo genérico) | Parcial-gap médio |
| **Atendimento / Helpdesk (quem recebe chamado reativo)** | ~ P7 | Parcial-gap médio |
| **Fornecedor / Parceiro B2B** | ✗ (e não prioritária) | Registrado como não-prioritária |
| **Auditor externo (Cgcre)** | ~ listado como não-prioritária | OK |
| **Contador do laboratório** | ~ listado como não-prioritária | OK |
| **Comprador industrial corporativo** | ~ P3 | OK |
| **Responsável qualidade da indústria cliente** | ~ listado como não-prioritária | OK |
| **Técnico terceirizado/freelancer** | ~ listado como não-prioritária | OK |
| **Fornecedor de padrão** | ~ listado como dado, não usuário | OK |

### 5.3. Gaps de persona com impacto

- **PG-1 [Alto] Diretoria / CFO:** toda fonte externa trata CFO como comprador interno (coalizão com CEO/COO). No baseline, P1 (Sócio-gerente) absorveu esse papel. Funciona para laboratório pequeno, mas em laboratório médio há separação formal. Recomendação: reforçar P1 com detalhes de CFO ou extrair P1b.
- **PG-2 [Médio] Coordenação Técnica:** quem prioriza fila quando técnicos saem para campo. No baseline é P1 ou P7, mas na prática é papel distinto. Em laboratório maduro, existe "coordenador técnico" dedicado.
- **PG-3 [Médio] Financeiro / AR:** P7 é "atendente/administrativa" genérica. Faturamento + cobrança + conciliação exigem conhecimento específico. Recomendação: criar P7b (financeiro) ou documentar que P7 acumula.
- **PG-4 [Médio] Helpdesk / Atendimento a chamado reativo:** se ICP inclui serviço reativo (não só calibração programada), quem recebe chamado é persona distinta. No baseline é implícito em P7.
- **PG-5 [Baixo] Persona "Parceiro/Revendedor":** KSAAS dedica programa Silver/Gold/Platinum. Se Kalibrium SaaS vai ter revenda, precisa persona.

---

## 6. Itens parcialmente cobertos (delta)

Gaps onde o baseline tem REQ cobrindo parte do escopo mas falta precisão nos ACs. **Recomendo formalizar ACs faltantes sem necessariamente adicionar REQ novo.**

| Delta | REQ afetado | O que falta | Impacto |
|---|---|---|---|
| D-01 | REQ-FLX-002 (status do pedido) | Estados completos ISO 17025 da OS: recebido → amostragem → ambiente estabilizado → calibração → revisão → aprovação → emissão → despachado → pago | Médio |
| D-02 | REQ-FIS-001 (NFS-e) | Regras municipais versionadas por cidade + layout por prefeitura | Médio |
| D-03 | REQ-FIS-007 (retransmissão) | Motivos canônicos de rejeição + SLA de retentativa + alçada de intervenção manual | Médio |
| D-04 | REQ-MET-001 (padrão ref) | Estados lifecycle (acquired/in_calibration/active/in_use/suspended/retired) | Médio |
| D-05 | REQ-MET-006 (certificado) | Template por tenant + versionamento de template | Médio |
| D-06 | REQ-FLX-004 (WhatsApp) | Templates Meta-approved + handshake de provider | Médio |
| D-07 | REQ-FLX-005 (portal cliente) | Convite formal + primeiro acesso + tickets + verificação pública anônima | Médio |
| D-08 | REQ-TEN-002 (papéis) | Spatie Permission-style policies por feature (auth granular) | Médio |
| D-09 | REQ-OPL-001 (dashboard) | KPIs por perfil (Dono/Coord/Fin/Lab) | Médio |
| D-10 | REQ-SYN-003 (conflito) | UI de resolução com preview de ambos editores | Médio |
| D-11 | REQ-CRM-007 (pipeline) | Estágios formais com probability + forecast + loss_reason | Médio |
| D-12 | REQ-FLD-006 (mapa do dia) | Otimização de rota (TSP) + ETA por parada | Baixo |
| D-13 | REQ-INV-002 (movimento estoque) | Regra de reposição automática (min/max) | Baixo |
| D-14 | REQ-SEC-005 (sessão longa) | Gestão de sessões ativas + revogação seletiva | Baixo |
| D-15 | REQ-CMP-001 (registro imutável) | Estender append-only a TODOS os dados (OS, AR, etc), não só calibração | Médio |

---

## 7. Recomendação de ação por gap

Legenda: **MVP** = entrar no MVP-ampliação v3; **PÓS** = backlog pós-MVP; **DESC** = descartar; **?** = decisão do PM.

### 7.1. Gaps alto impacto (13) — recomendação

| # | Gap | Recomendação |
|---|---|---|
| G1 | F-MET-05 Dual sign-off certificado | **?** — ISO 17025 não exige explicitamente; RBC/Cgcre solicitam. PM decide se é v3 MVP ou v3.1. |
| G2 | F-MET-09 Competência bloqueante ISO 17025 §6.2 | **MVP** — requisito normativo direto |
| G3 | F-MET-11 Falha de padrão + suspensão automática | **MVP** — evita nulidade retroativa de certificados |
| G4 | F-OS-02 Despacho automático (round-robin básico) | **MVP** (versão simples) / **PÓS** (skill + geo) |
| G5 | F-OS-04 SLA com timer + pausa/retoma + escalonamento | **PÓS** — complexo, mas alto valor |
| G6 | F-OS-12 Re-despacho quando técnico indisponível | **MVP** — operação de campo quebra sem isso |
| G7 | F-OS-13 OS de garantia com custo zero + cost allocation | **MVP** (classificação) / **PÓS** (allocation) |
| G8 | F-OS-15 Manutenção preventiva automática | **MVP** — contratos recorrentes sem scheduler = planilhas paralelas |
| G9 | F-FIS-03 RTC 2026 (IBS/CBS/cIndOp) | **?** — depende do cronograma do MVP vs data-alvo de 2026. Se MVP vai pra produção antes de julho 2026, pode ficar PÓS. Se pós-julho, MVP. |
| G10 | F-GOV-02 Multi-tenant schema-per-tenant | **?** — decisão técnica de arquitetura. Recomendo abrir ADR dedicado (REQ-TEN-005 implica, mas não documenta). |
| G11 | M7 Billing SaaS nativo + self-service onboarding | **?** — se ICP vai pagar planos, precisa cobrança do tenant. PM decide se é parte do MVP ou vem via Stripe manual. |
| G12 | PG-1 Persona CFO / Diretoria | **MVP** — apenas refinar P1 (persona doc, não REQ) |
| G13 | FL-22 Cobrança automática por faixa de atraso | **PÓS** — MVP pode viver com cobrança manual |

### 7.2. Gaps médio impacto (21) — recomendação

Todos **PÓS** salvo os 5 a seguir, que recomendo **MVP** por terem baixo custo adicional:
- D-03 motivos canônicos de retransmissão NFS-e (reforça REQ-FIS-007)
- D-04 estados lifecycle padrão de referência (reforça REQ-MET-001)
- D-06 templates WhatsApp aprovados (viabiliza REQ-FLX-004 na prática)
- D-07 convite formal portal cliente (reforça REQ-FLX-005)
- PG-2 + PG-3 personas coordenação + financeiro (só doc, zero custo)

### 7.3. Gaps baixo impacto (24) — recomendação

Todos **PÓS** ou **DESC**:
- **DESC:** gamificação (Innovation.md); white-label; multi-país; marketplace de parceiros; manufatura.
- **PÓS:** todos os outros (procurement, fixed assets, logistics, projects, ioT, agenda interna, omnichannel inbox, etc).

### 7.4. Decisões PM explícitas (?)

1. **RTC 2026:** entrar no MVP ou aguardar? (decisão depende do go-live)
2. **Programa Confia Enterprise:** manter monitorado ou promover? (KSAAS trata como capability opcional)
3. **Multi-tenant schema-per-tenant:** ADR formal sim/não?
4. **Billing SaaS + self-service trial 14d:** entra no MVP do Kalibrium V2 ou vem externo (Stripe manual)?
5. **Dual sign-off em certificado:** MVP ou v3.1?

---

## 8. Observações de qualidade do baseline (lidas do repo atual)

Durante a leitura dos artefatos do baseline, identifiquei inconsistências e pontos de atenção:

### 8.1. Consistência inter-arquivo

- **mvp-scope.md** cita 13 domínios numerados (3.1 a 3.13) mas eu contei 14 na tabela acima. Revisar numeração de domínios no mvp-scope — se alguns foram agrupados (ex: UMC + VHL em um §3.8), pode ser intencional.
- **ROADMAP.md** lista 23 épicos (E01–E23) mas o baseline só tem `epics/E01..E14/epic.md` como arquivo. Os E15-E23 existem só como entrada no roadmap, sem diretório próprio. **Risco:** épicos prometidos sem esqueleto podem drift. Recomendo rodar `/decompose-epics` para materializar os diretórios E15-E23 antes de iniciar qualquer slice desses épicos.
- **journeys.md** tem 13 jornadas, algumas são "esqueletos" explícitos (J2, J3, J4, J5, J11 parcial). Se o MVP depende dessas jornadas para cobertura, esqueletos não são suficientes para `/draft-spec`.
- **post-mvp-backlog.md** cita "auditoria comparativa externa 2026-04-16 v2" como fonte — esse arquivo é o `PRD-ampliacao-2026-04-16-v2.md` que eu não li (regra de isolamento). Observo que o baseline sabe que ele existe e tem 19 itens rastreáveis, o que sugere boa governança.

### 8.2. REQs com dependência circular ou referência cruzada

- REQ-CRM-008 (revalidação proativa) → depende de REQ-MET-007 (histórico instrumento offline) — cruzamento saudável.
- REQ-FIS-007 (retransmissão NFS-e) → depende de REQ-FIS-001. OK.
- REQ-MET-010 (drift automático) → depende de REQ-MET-009 (SPC). Recomendação: garantir que E22 decomponha MET-009 antes de MET-010 nas stories.

### 8.3. Áreas sub-especificadas (pode ser intencional, só sinalizando)

- **Billing / cobrança do Kalibrium SaaS pelo tenant** — nenhum REQ cobre. Se V2 é SaaS, precisa REQ-TEN-XXX ou épico dedicado.
- **Assinatura de e-mail transacional** — REQ-FLX-003 menciona "e-mail a cada transição" mas não especifica DKIM/SPF, templates, provider (SES/SendGrid/Mailgun).
- **Deploy profile** — nenhum REQ técnico direto, mas a constituição exige threat model + deploy profile antes de `/freeze-architecture`.

### 8.4. Pontos fortes do baseline

- Offline-first bem dimensionado (§ 2-ter do mvp-scope.md quantifica janelas e volume) — **raro** em PRDs.
- Personas com faixa etária, dialeto e objeção — nível de detalhe acima da média.
- Jornada J10 (colaboração multi-pessoa offline com convergência) — é tema muito avançado para MVP; impressiona ver documentado.
- Integração explícita com ISO 17025 (baseline cita §6.2, §6.4, §6.5, §7.4 em vários lugares) — sinaliza cliente ICP alvo com clareza.

---

## 9. Observação R1 — arquivos proibidos nas fontes externas

Durante inventário, constatei (não copiei) os seguintes arquivos/diretórios proibidos no Kalibrium V2 por R1:

### 9.1. `C:\PROJETOS\sistema`

- `CLAUDE.md` (presente — 16722 bytes)
- `AGENTS.md` (presente — 21656 bytes)
- `GEMINI.md` (presente — 6291 bytes)
- `.cursor/` (diretório)
- `.claude/` (diretório)
- `.superpowers/` (diretório — skills)
- `_bmad/` (diretório — skills BMM)
- `_bmad-output/` (diretório — artefatos bmad)

### 9.2. `C:\PROJETOS\KALIBRIUM SAAS`

- `CLAUDE.md` (presente — 13374 bytes)
- `AGENTS.md` (presente — 15348 bytes)
- `GEMINI.md` (presente — 2913 bytes)
- `.cursorrules` (presente — 1728 bytes)
- `.cursor/` (diretório)
- `.claude/` (diretório)
- `_bmad/` (diretório)
- `_bmad-output/` (diretório)

**Nenhum arquivo foi copiado para o baseline.** Apenas anotado aqui para evidência de auditoria. Ambas fontes externas operam sob harness distinto (Codex + Cursor + Superpowers + BMM) — NÃO portar sua filosofia de instrução para o V2.

---

## 10. Próximos passos recomendados

Em ordem de prioridade, recomendo ao PM:

### 10.1. Imediato (antes do próximo slice)

1. **Ler esta re-auditoria em paralelo à primeira** (`comparativa-externa-2026-04-16.md`, que NÃO li por isolamento). Comparar as duas opiniões e consolidar.
2. **Decidir os 5 itens da Seção 7.4** (RTC 2026, Confia, schema-per-tenant ADR, billing SaaS, dual sign-off).
3. **Formalizar via REQs/ACs** os gaps classificados MVP em §7.1 (G2, G3, G4-simples, G6, G7-classificação, G8).
4. **Refinar personas** (P1 detalhes CFO, criar P-Coordenação + P-Financeiro opcional, decidir se P-Helpdesk vira P9 ou fica em P7).
5. **Materializar diretórios E15–E23** via `/decompose-epics` ou aceitar que roadmap tem épicos fantasma.

### 10.2. Curto prazo (até próxima rodada de re-audit)

6. **Abrir ADR-0016 (schema-per-tenant)** se a resposta à decisão for positiva.
7. **Escrever épico novo E24 "Billing SaaS + onboarding self-service"** se a resposta à decisão for positiva.
8. **Revisar jornadas esqueleto (J2, J3, J4, J5, J11)** para terem passos suficientes antes de `/decompose-stories`.
9. **Adicionar jornada J14 "Gestor consome relatórios"** (para capturar FL-09 Relatórios Gerenciais por perfil).
10. **Adicionar jornada J15 "Onboarding pós-venda do cliente"** (capturar FL-11).

### 10.3. Médio prazo

11. **Re-auditoria pós-ampliação v3** após PM consolidar, para garantir que os 7 MVP + 21 PÓS + 4 DESC foram registrados sem drift.
12. **Harness-learner ciclo** com findings dessa auditoria (R16) para validar se regras atuais capturaram bem o conteúdo.

### 10.4. Restrição R1 reiterada

Nenhum dos arquivos proibidos das fontes externas foi lido com propósito de influenciar o V2. Se PM ainda assim quiser extrair algo de `sistema/_bmad-output/implementation-artifacts/*.md` (stories completas), **recomendo fazer isso via skill `/intake` de entrevista**, não via cópia — para preservar a autoria e isolamento do baseline.

---

**Fim do relatório.**

*Gerado por auditor independente em sessão isolada. Total aproximado: 58 gaps identificados (13 alto / 21 médio / 24 baixo), com recomendação MVP/PÓS/DESC/? por cada. Divergências esperadas vs. relatório anterior são úteis e saudáveis para o PM (R11 dual-verifier aplicado).*
