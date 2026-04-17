# Auditoria comparativa — Kalibrium V2 vs KALIBRIUM SAAS + sistema

**Data:** 2026-04-16
**Executado por:** Claude Code (sessão nova, contexto limpo)
**Baseline:** `C:\PROJETOS\saas\kalibrium-v2` — PRD ampliado 2026-04-16 (62 REQs, 13 domínios, 8 personas, 11 jornadas, 20 épicos, ADR-0015 offline-first).
**Fontes externas (read-only):**
- `C:\PROJETOS\KALIBRIUM SAAS` — PRD 471KB, Laravel 11 DDD (11 domínios), 105 FRs, 425 migrations, 411 models, 300+ controllers, PWA offline-first.
- `C:\PROJETOS\sistema` — Laravel + React/TS, 386 models, 303 controllers, 13 papéis, 200+ permissões, auditoria interna `auditoria-sistema-2026-04-10.md`.
**Regras observadas:** R1 (arquivos proibidos apenas anotados), read-only, PRD aditivo (nada removido do baseline).

---

## Sumário executivo

| Dimensão | Candidatos extraídos | Cobertos | Parcial | Gap |
|---|---|---|---|---|
| Funções | 38 | 18 | 6 | 14 |
| Funcionalidades | 34 | 14 | 7 | 13 |
| Fluxos | 22 | 11 | 5 | 6 |
| Personas | 13 | 8 | 1 | 4 |
| **Total** | **107** | **51** | **19** | **37** |

**Gaps por impacto:**
- **Alto (MVP candidato):** 7 itens
- **Médio (pós-MVP provável):** 14 itens
- **Baixo (descartar ou diferir muito):** 16 itens

**Arquivos proibidos R1 encontrados nas fontes externas (apenas anotados, nenhum copiado):**
- `KALIBRIUM SAAS/.cursorrules`, `AGENTS.md`, `GEMINI.md`, `.github/copilot-instructions.md`, `.agents/skills/` (cache bmad)
- `sistema/AGENTS.md`, `GEMINI.md`, `.superpowers/GEMINI.md`, `.superpowers/` (diretório)

---

## 1. Funções — gaps

Funções = operações de sistema executáveis (endpoints, comandos, jobs, automações).

### 1.1. Alto impacto

| Função | Fonte | Observação |
|---|---|---|
| **Retransmissão automática de NFS-e rejeitada** | KALIBRIUM SAAS (`TransmitPseiCertificateJob` análogo para fiscal; rejeição + reprocessamento) | Baseline `REQ-FIS-001/002/006` cobre emissão e preparação offline, mas não formaliza o ciclo "prefeitura rejeitou → diagnosticar → corrigir → retransmitir" que é rotina real de NFS-e. Alto impacto fiscal. |
| **Push notifications ao dispositivo** | KALIBRIUM SAAS (`/push-subscription`) | Baseline notifica por e-mail e WhatsApp (`REQ-FLX-003/004`), não tem push nativo. Técnico em campo e gestor precisam saber na hora de mudança de status / SLA / atribuição nova. |
| **Despacho automático de OS (location + skills + ETA)** | KALIBRIUM SAAS (`/dispatch`) | Baseline tem agenda manual (`REQ-OPL-002`) e mapa das OS do dia (`REQ-FLD-006`); não otimiza distribuição. Alto impacto para operação com 3+ técnicos em campo. |
| **Requisição LGPD pelo titular (acesso/exclusão/retificação)** | KALIBRIUM SAAS (`/lgpd`), sistema (LGPD+biometria) | Baseline `REQ-CMP-004` registra base legal mas não implementa jornada pública do titular. Obrigação legal — titular pode exercer direito a qualquer momento. |
| **Escalação automática de SLA** | KALIBRIUM SAAS (`MonitorSlaDeadlinesJob`, `ProcessSlaViolationJob`, `EscalationRule`) | Baseline tem aderência ao prazo como indicador (`REQ-OPL-003`) mas não escalona. OS com prazo estourando deve avisar gestor, reatribuir, priorizar. |
| **Drift automático de padrão de referência + bloqueio** | KALIBRIUM SAAS (`block-expired-standards`, `ControlChartAlert`, SPC) | Baseline `REQ-MET-008` bloqueia por validade apenas. Drift (padrão se afastando do valor nominal) é monitoria metrológica exigida por ISO 17025 mesmo antes da expiração. |
| **Preparação/transmissão de NFS-e com retenções (ISS/IR/INSS)** | KALIBRIUM SAAS (Lucro Presumido/Simples com half-even rounding, splits) | Baseline cita Simples e Lucro Presumido (§2) mas não formaliza cálculo de retenções por município + rounding preciso. Rejeição por cálculo incorreto é causa #1 de NFS-e rejeitada. |

### 1.2. Médio impacto

| Função | Fonte | Observação |
|---|---|---|
| Templates de OS reaproveitáveis (`WorkOrderTemplate`) | KALIBRIUM SAAS | Produtividade — cada tipo de calibração tem procedimento + insumos + pontos padrão. |
| Sub-OS / decomposição em sub-tarefas (`SubWorkOrder`) | KALIBRIUM SAAS | Para OS grandes (UMC em cliente com 5 balanças, cada balança vira sub-OS). |
| MQTT listen (IoT de temperatura/umidade) | KALIBRIUM SAAS (`mqtt-listen`, `/environment-reading`) | Baseline captura condições manualmente (`REQ-MET-004`). Sensor automático acelera bancada e elimina erro de digitação. |
| Rotação automática de API keys + credenciais de integrações | KALIBRIUM SAAS (`rotate-api-keys`, `rotate-integration-credentials`) | Boa prática de segurança — baseline não explicita. |
| Circuit breaker em integrações externas com reset | KALIBRIUM SAAS (`reset-circuit-breaker`) | Resiliência para NFS-e, WhatsApp, PIX quando prefeitura/provider cai. |
| Backup por tenant + verify integrity | KALIBRIUM SAAS (`backup:database`, `verify-backup-integrity`) | Baseline não endereça backup operacional. Item crítico antes de produção. |
| Broadcasting real-time (Pusher/Laravel Echo) para edição multi-usuário | KALIBRIUM SAAS (channels) | Baseline Jornada 10.2 menciona "tempo real online" mas não materializa — Pusher/Echo é a implementação canônica. |
| Lead capture form + routing rules | KALIBRIUM SAAS (`LeadCaptureForm`, `LeadCaptureRoutingRule`) | Formulário público (landing) que captura lead e roteia para vendedor por região/porte/origem. |
| Webhook inbound para chamados (`/inbound-service-ticket-webhook`) | KALIBRIUM SAAS | Cliente reporta problema via e-mail/Whatsapp/formulário → vira ticket. |
| Conciliação automática com gateway de pagamento (Pix/Boleto) | KALIBRIUM SAAS (`RunPaymentGatewayReconciliationJob`, `/payment-gateway-webhook`) | Baseline `REQ-FIS-005` conciliação manual; automação é ganho claro pós-MVP. |
| Cleanup de tokens vencidos | KALIBRIUM SAAS (`cleanup-expired-tokens`) | Higiene de sessão — baseline menciona token longo (`REQ-SEC-005`) mas não cleanup. |
| Check de trial expiration | KALIBRIUM SAAS (`check-trial-expiration`) | Modelo SaaS — se vier plano trial depois do MVP. |
| Check de prazos de breach notification (LGPD 72h) | KALIBRIUM SAAS (`check-breach-notification-deadlines`) | Obrigação ANPD — incidente deve ser notificado em até 72h. |
| Check de requisições LGPD vencidas | KALIBRIUM SAAS (`check-overdue-lgpd-requests`) | Obrigação de prazo da ANPD para responder titular. |

### 1.3. Baixo impacto / descartar

| Função | Fonte | Recomendação |
|---|---|---|
| Comandos de eSocial (folha, afastamento, férias) | KALIBRIUM SAAS + sistema | Descartar — fora do produto Kalibrium (§4 MVP scope confirma). |
| Ponto eletrônico com geofence | sistema | Descartar — `mvp-scope.md §4` diz "automação REP-P nunca". |
| Anvisa RDC instrument validation | KALIBRIUM SAAS | Fora do MVP — só se cliente pagante exigir. Registrar como pós-MVP. |

---

## 2. Funcionalidades — gaps

Funcionalidades = módulos/features de produto completos.

### 2.1. Alto impacto

| Funcionalidade | Fonte | Observação |
|---|---|---|
| **Contratos recorrentes com renovação automática** | KALIBRIUM SAAS (`Contract`, `ContractMilestone`, `ContractRecurrenceTerm`, `ContractAlertDispatch`) | Baseline tem orçamento único (`REQ-CRM-004`) mas não contrato anual recorrente. Cliente que calibra mensalmente quer contrato guarda-chuva, não orçamento por OS. |
| **SPC / Gráficos de controle + drift monitorado (ISO 17025)** | KALIBRIUM SAAS (`ControlChartConfig`, `ControlChartAlert`, AIAG MSA) | Baseline `REQ-OPL-003` tem indicadores básicos, mas não SPC. Laboratório acreditado RBC precisa de gráficos de controle dos padrões e drift. |
| **Portal do cliente com self-service completo** | KALIBRIUM SAAS | Baseline `REQ-FLX-005` tem "download de certificado vigente e histórico" — PARCIAL. Faltam: abrir chamado, acompanhar OS em andamento, baixar NF-e, consentimento LGPD, agendar nova calibração. |
| **Service tickets + helpdesk interno com SLA** | KALIBRIUM SAAS (`ServiceTicket`, `SlaAgreement`, `EscalationRule`) | Ausente no baseline. Cliente abre chamado ("certificado veio com erro de incerteza") → equipe triagem → escalação → resolução. |
| **Jornada LGPD ponta-a-ponta** | KALIBRIUM SAAS (`/lgpd`), sistema | Baseline registra consentimento WhatsApp (`REQ-FLX-004`) e base legal (`REQ-CMP-004`). Falta jornada completa: titular solicita acesso/retificação/exclusão, DPO aprova, sistema responde com log. |
| **Cadência comercial automática (email+SMS+WhatsApp step-wise)** | KALIBRIUM SAAS (`CommercialCadence`, `CadenceStep`, `CadenceStepRun`, `MessageTemplate`) | Baseline `REQ-CRM-006` tem follow-up manual. Cadência automatiza "renovação de contrato em 30 dias → proposta → 7 dias depois reminder → véspera SMS". |
| **Retenção fiscal correta por regime (Simples/LP) com half-even rounding** | KALIBRIUM SAAS (NT SE/CGNFS-e 006/2026, 007/2026) | Baseline cita regimes mas não formaliza regras de retenção. Gap fiscal crítico para não ter NFS-e rejeitada. |

### 2.2. Médio impacto

| Funcionalidade | Fonte | Observação |
|---|---|---|
| RFM + lifecycle opportunities (churn alert, expansion) | KALIBRIUM SAAS (`CustomerRfmSnapshot`, `CustomerLifecycleOpportunity`) | Analytics comercial — valioso mas não crítico no MVP. |
| Assistência remota (vídeo/screenshare) técnico-especialista | KALIBRIUM SAAS (`RemoteAssistSession`) | Útil quando técnico em campo precisa de ajuda; pós-MVP. |
| Analytics/BI consolidado (KPIs cross-módulo) | KALIBRIUM SAAS + sistema | Baseline tem dashboard operacional (`REQ-OPL-001`). BI comercial/financeiro/metrológico fica pós. |
| Observabilidade com OpenTelemetry (traces, metrics, logs estruturados) | sistema (`docker-compose.observability.yml`) | Baseline tem `observability-expert` como papel técnico mas não formaliza stack. Infra — pós-MVP se não houver incidente. |
| Segurança SAST/DAST (Semgrep, ZAP, Gitleaks) | sistema (`.semgrep.yml`, `.zap/`, `.gitleaks.toml`) | Baseline tem `security-expert` gate mas não ferramentas dedicadas configuradas. Pós-MVP. |
| Commissão/incentivo para vendedor (regras por deal fechado) | sistema | Extensão natural do CRM. Pós-MVP. |
| Marketing site / landing pública | KALIBRIUM SAAS (`/web`, `/external`) | Fora do produto core — GTM task separada. |

### 2.3. Baixo impacto / descartar

| Funcionalidade | Fonte | Recomendação |
|---|---|---|
| eSocial + folha + benefícios + departamentos | KALIBRIUM SAAS + sistema | Descartar — Kalibrium não é folha (§4 confirma). |
| Contas a pagar | sistema | Fora do MVP; Kalibrium mira contas a receber (§3.4). Gatilho: demanda pagante. |
| Gestão de ativos internos (`AssetRecord`) | sistema | Fora — Kalibrium não é ERP. §4 exclui "gestão de equipamento (manutenção preventiva do próprio laboratório)". |
| Renegociação de dívidas | sistema | Fora — não é função de software de calibração. |
| Integração Auvo (ERP legado) | KALIBRIUM SAAS + sistema | Customização por cliente; só se lead pagante exigir. |
| NF-e (mercadoria) além de NFS-e | KALIBRIUM SAAS | Fora — Kalibrium é serviço. |

---

## 3. Fluxos — gaps

Fluxos = jornadas de trabalho ou sequências de estado.

### 3.1. Alto impacto

| Fluxo | Fonte | Observação |
|---|---|---|
| **Ciclo de contrato recorrente: criar → vigência → renovação → ajuste de preço → término** | KALIBRIUM SAAS | Baseline não modela. Contrato guarda-chuva para cliente recorrente precisa de estados formais. |
| **NFS-e rejeitada → diagnóstico → correção → retransmissão** | KALIBRIUM SAAS | Baseline só modela "preparada offline → transmitida" (`REQ-FIS-006`). Rejeição pela prefeitura precisa de fluxo reverso. |
| **Abertura de chamado pelo cliente externo → triagem → atribuição → resolução → SLA** | KALIBRIUM SAAS (`ServiceTicket`) | Ausente. Portal só serve para baixar certificado (`REQ-FLX-005`). |
| **Escalação SLA: OS com prazo estourando → alerta gestor → reatribuição → priorização** | KALIBRIUM SAAS (`MonitorSlaDeadlinesJob`, `SendSlaNotificationJob`) | Ausente. |
| **Jornada LGPD do titular: requisição → triagem DPO → atendimento → log** | KALIBRIUM SAAS + sistema | Ausente. Base legal registrada não basta; titular precisa de canal. |
| **Revalidação proativa de instrumento (90 dias antes de vencer → notifica cliente + oferece agendamento)** | KALIBRIUM SAAS (cadência comercial) | Baseline registra histórico (`REQ-MET-007`) mas não proativa revalidação — perde receita recorrente. |

### 3.2. Médio impacto

| Fluxo | Fonte | Observação |
|---|---|---|
| Orçamento → aceito → convertido em contrato (não só em OS) | KALIBRIUM SAAS | Baseline `REQ-CRM-005` converte orçamento em OS. Contrato guarda-chuva fica para pós. |
| Aprovação de desconto em alçada (para orçamento, não só despesa) | KALIBRIUM SAAS | Baseline tem alçada em despesa (`REQ-DSP-005`) mas não em preço de orçamento. |
| Reabertura de OS cancelada / retomada | KALIBRIUM SAAS (estados `WorkOrder`) | Baseline estado `cancelado` é terminal implícito; não detalha retomada. |
| Cadência de cobrança (boleto vencido → reminder → escalação → negativação) | KALIBRIUM SAAS (`RunBillingCollectionCadenceJob`) | Baseline não formaliza. |
| Reconciliação de pagamento via webhook PSP com baixa automática | KALIBRIUM SAAS | Baseline `REQ-FIS-005` manual. |

### 3.3. Baixo impacto

| Fluxo | Fonte | Recomendação |
|---|---|---|
| Sub-OS aninhada (OS-pai → decomposição em sub-OS) | KALIBRIUM SAAS | Pós-MVP. |
| Ponto eletrônico com geofence (técnico bate ponto ao entrar na área do cliente) | sistema | Descartar. |

---

## 4. Personas — gaps

### 4.1. Alto impacto

| Persona | Fonte | Observação |
|---|---|---|
| **Especialista remoto / backoffice técnico** (sênior que apoia campo por vídeo/chat) | KALIBRIUM SAAS (`RemoteAssistSession`) | Role `QUALITY`/`MANAGER` no externo absorve. Baseline não tem. Alto impacto se empresa tiver 1-2 seniors e muitos juniores em campo. |
| **Responsável de qualidade / ISO 17025 owner** (dono dos padrões, auditor interno) | KALIBRIUM SAAS (role `QUALITY`) | Baseline tem Juliana (técnica) e Marcelo (gerente) mas o papel específico de qualidade responsável pela acreditação não aparece. Alto impacto para laboratório acreditado. |

### 4.2. Médio impacto

| Persona | Fonte | Observação |
|---|---|---|
| Contador externo (consome relatórios CSV para contabilidade) | sistema (role `finance_external`) | Baseline `REQ-OPL-004` gera CSV mas persona não aparece. |
| Operador de helpdesk (recebe chamados, tria, escala) | KALIBRIUM SAAS (role `OPERATIONS`) | Nasce junto com Service Tickets; se gap 2.1 for aceito, persona vem junto. |

### 4.3. Parcialmente coberto

| Persona | Fonte | Observação |
|---|---|---|
| Cliente externo com múltiplos usuários (comprador + financeiro + qualidade do lado cliente) | KALIBRIUM SAAS (role `CUSTOMER_PORTAL` multi-user) | Baseline Persona 3 (Rafael, comprador industrial) é um papel só. Grandes clientes têm 3-5 usuários distintos. |

### 4.4. Baixo impacto / descartar

| Persona | Fonte | Recomendação |
|---|---|---|
| RH / DP (folha, eSocial) | KALIBRIUM SAAS + sistema | Descartar — fora do produto. |
| Supplier / fornecedor externo | sistema | Fora — Kalibrium não gerencia compras. |
| Inmetro Coordinator | sistema | Descartar — Kalibrium não é órgão fiscalizador. |
| Employment / Payroll / Employee | sistema | Descartar. |

---

## 5. Itens parcialmente cobertos (delta detalhado)

| Item baseline | Cobertura baseline | Ampliação sugerida pela fonte externa |
|---|---|---|
| `REQ-FLX-003/004` Notificação por e-mail/WhatsApp | e-mail + WhatsApp opt-in | Adicionar push notification ao app móvel (KALIBRIUM SAAS `/push-subscription`). |
| `REQ-FLX-005` Portal do cliente | baixar certificado + histórico | Ampliar: abrir chamado, agendar nova calibração, consentimento LGPD, baixar NF-e, ver contratos. |
| `REQ-FIS-005` Conciliação manual | manual | Adicionar webhook PSP (Pix/Boleto) com baixa automática (pós-MVP). |
| `REQ-MET-008` Validade bloqueia lançamento | validade = data + N dias | Adicionar drift automático (valor do padrão se afastando do nominal) e SPC (ISO 17025). |
| `REQ-OPL-003` Indicadores básicos | tempo médio, aderência, custo real | Ampliar BI: RFM, pipeline comercial, churn risk, recurring revenue. |
| `REQ-CMP-002` Log de acesso | quem leu qual certificado | Ampliar: log genérico cross-entidade (não só certificado) para compliance LGPD. |
| `REQ-CMP-004` Base legal LGPD | registro da base | Ampliar: jornada ponta-a-ponta do titular (acesso/retificação/exclusão). |
| `REQ-SEC-003` Wipe remoto | wipe autorizado pela empresa | Ampliar: device binding com re-aprovação (externo formaliza em table `registered_device`). |
| `REQ-CRM-006` Follow-up automático | lembra vendedor | Ampliar: cadência comercial step-wise multicanal (email+SMS+WhatsApp). |
| Jornada 10.2 Tempo real online | menciona texto | Materializar com Pusher/Laravel Echo (KALIBRIUM SAAS). |

---

## 6. Recomendação de ação por gap (tabela decisão PM)

Legenda: **[MVP]** entrar no MVP ampliado antes de decompor E15 · **[PÓS]** registrar para pós-MVP · **[DESC]** descartar · **[?]** decisão de produto pendente.

| # | Gap | Dimensão | Impacto | Recomendação |
|---|---|---|---|---|
| 1 | Retransmissão de NFS-e rejeitada | Função | Alto | **[MVP]** — é rotina real, sem isso operação para |
| 2 | Push notifications ao dispositivo | Função | Alto | **[MVP]** — offline-first + push são par natural |
| 3 | Despacho automático (location+skills) | Função | Alto | **[?]** — PM decide se 3+ técnicos em campo é cenário real |
| 4 | Jornada LGPD do titular completa | Fluxo/Funcionalidade | Alto | **[MVP]** — obrigação legal, risco de multa ANPD |
| 5 | Escalação automática de SLA | Função/Fluxo | Alto | **[?]** — PM decide se cliente compra SLA formal |
| 6 | SPC / Drift de padrões | Função/Funcionalidade | Alto | **[MVP]** — ISO 17025 exige, laboratório acreditado não opera sem |
| 7 | Retenção fiscal correta (ISS/IR/INSS, half-even) | Função | Alto | **[MVP]** — NFS-e sem retenção correta é rejeitada |
| 8 | Contratos recorrentes + renovação | Funcionalidade/Fluxo | Alto | **[?]** — PM decide se modelo de receita inclui contrato anual |
| 9 | Portal do cliente self-service | Funcionalidade | Alto | **[?]** — ampliar `REQ-FLX-005` ou deixar pós-MVP |
| 10 | Service tickets + helpdesk com SLA | Funcionalidade | Alto | **[?]** — depende de #5 |
| 11 | Cadência comercial automática | Funcionalidade | Alto | **[PÓS]** — CRM §3.11 do baseline cobre o essencial para MVP |
| 12 | Revalidação proativa de instrumento | Fluxo | Alto | **[?]** — gera receita recorrente, forte candidato MVP |
| 13 | Persona: Especialista remoto / backoffice | Persona | Alto | **[?]** — vem junto com #14 |
| 14 | Persona: Responsável de qualidade ISO 17025 | Persona | Alto | **[MVP]** — laboratório RBC tem esse papel sempre |
| 15 | Templates de OS | Função | Médio | **[PÓS]** |
| 16 | Sub-OS | Função/Fluxo | Médio | **[PÓS]** |
| 17 | MQTT IoT (temp/umidade automática) | Função | Médio | **[PÓS]** — nice-to-have laboratório bancada |
| 18 | Rotação automática de credenciais | Função | Médio | **[PÓS]** |
| 19 | Circuit breaker em integrações | Função | Médio | **[PÓS]** |
| 20 | Backup por tenant + verify integrity | Função | Médio | **[MVP]** — obrigatório antes de produção |
| 21 | Broadcasting real-time (Pusher/Echo) | Função | Médio | **[MVP]** — materializa Jornada 10.2 |
| 22 | Lead capture form + routing | Função/Funcionalidade | Médio | **[PÓS]** |
| 23 | Webhook inbound de chamado | Função | Médio | **[PÓS]** — depende de #10 |
| 24 | Conciliação PSP automática | Função/Fluxo | Médio | **[PÓS]** — `REQ-FIS-005` manual cobre MVP |
| 25 | Cleanup de tokens / trial / breach / LGPD deadlines | Função | Médio | **[MVP]** — pack de compliance, custo baixo |
| 26 | RFM + lifecycle opportunities | Funcionalidade | Médio | **[PÓS]** |
| 27 | Assistência remota | Funcionalidade | Médio | **[PÓS]** |
| 28 | Analytics/BI avançado | Funcionalidade | Médio | **[PÓS]** |
| 29 | Observabilidade OpenTelemetry | Funcionalidade | Médio | **[PÓS]** — infra, não produto |
| 30 | SAST/DAST (Semgrep/ZAP/Gitleaks) | Funcionalidade | Médio | **[PÓS]** — pipeline CI, não produto |
| 31 | Comissão/incentivo vendedor | Funcionalidade | Médio | **[PÓS]** |
| 32 | Landing / marketing site | Funcionalidade | Médio | **[PÓS]** — GTM task |
| 33 | Aprovação de desconto em orçamento (alçada) | Fluxo | Médio | **[PÓS]** |
| 34 | Reabertura de OS cancelada | Fluxo | Médio | **[PÓS]** |
| 35 | Cadência de cobrança boleto vencido | Fluxo | Médio | **[PÓS]** |
| 36 | Persona: Contador externo | Persona | Médio | **[PÓS]** — consome CSV |
| 37 | Persona: Operador de helpdesk | Persona | Médio | Dependente de #10 |
| 38 | Cliente externo multi-usuário | Persona | Médio | **[?]** — ampliar Persona 3 |
| 39 | Comandos eSocial + folha | Função | Baixo | **[DESC]** |
| 40 | Ponto eletrônico + geofence | Função | Baixo | **[DESC]** (§4 "REP-P nunca") |
| 41 | Anvisa RDC validation | Função | Baixo | **[DESC]** (só sob demanda) |
| 42 | eSocial/folha/benefícios/departamentos | Funcionalidade | Baixo | **[DESC]** |
| 43 | Contas a pagar | Funcionalidade | Baixo | **[DESC]** |
| 44 | Gestão de ativos internos | Funcionalidade | Baixo | **[DESC]** (§4 exclui) |
| 45 | Renegociação de dívidas | Funcionalidade | Baixo | **[DESC]** |
| 46 | Integração Auvo ERP legado | Funcionalidade | Baixo | **[DESC]** (customização) |
| 47 | NF-e mercadoria | Funcionalidade | Baixo | **[DESC]** (Kalibrium é serviço) |
| 48 | Sub-OS aninhada (fluxo específico) | Fluxo | Baixo | **[PÓS]** |
| 49 | Persona RH/DP | Persona | Baixo | **[DESC]** |
| 50 | Persona Supplier | Persona | Baixo | **[DESC]** |
| 51 | Persona Inmetro Coordinator | Persona | Baixo | **[DESC]** |
| 52 | Persona Employment/Payroll | Persona | Baixo | **[DESC]** |

---

## 7. Próximos passos recomendados

1. **PM revisa a tabela §6** e marca decisão para os 13 itens **[?]** (decisão de produto pendente).
2. Após decisões, os itens marcados **[MVP]** consolidados geram um **PRD-ampliação-2026-04-NN-v2** (nome a definir), aditivo ao ampliação atual.
3. Itens **[PÓS]** entram em `docs/product/post-mvp-backlog.md` (criar se não existir) para não serem esquecidos.
4. Itens **[DESC]** entram em `mvp-scope.md §4` (OUT) com gatilho de reentrada (se houver).
5. Só depois disso, **decompor E15-E20** em stories (fluxo `/decompose-stories`).
6. Confirmar R1: nenhum arquivo proibido das fontes externas vazou para este repo (rodar `/forbidden-files-scan`).

---

## 8. Anexos — referência ao inventário das fontes

**KALIBRIUM SAAS:** Laravel 11 + DDD 11 domínios (Analytics, Commercial, Core, Finance, HumanResources, Integrations, Laboratory, Logistics, Operations, Portal, Quality). 105 FRs, 55 NFRs, 425 migrations, 411 models, 300+ controllers, 835 form requests, 39 enums, 45 events, 42 listeners, 35 jobs. PWA com offline-first, assinatura PSEI, PSP (Pix/Boleto), MQTT, WhatsApp, Auvo, Anvisa.

**sistema:** Laravel + React/TS. 386 models, 303 controllers (~4489 linhas de rotas), 13 papéis (admin, gestor, operacional, tecnico, vendedor, financeiro, rh, compliance, metrologia, support, portal_user, supplier, inmetro_coordinator), 200+ permissões. Features: metrologia core, sync offline/mobile, assinatura digital, WhatsApp, NFe, eSocial, ponto/geofence, LGPD+biometria, comissões, renegociação, OpenTelemetry, Semgrep/ZAP/Gitleaks, Auvo.

Inventários completos ficam na memória da sessão (não persistidos a disco por política do brief; este relatório sumariza).
