# Backlog pós-MVP — Kalibrium

> **Status:** ativo — criado 2026-04-16 após auditoria comparativa externa (`docs/audits/comparativa-externa-2026-04-16.md`).
> **Propósito:** preservar itens identificados como valiosos mas deliberadamente diferidos para depois do MVP. Cada item tem gatilho de reentrada claro — não é "nunca", é "depois de X evento".
> **Governança:** o PM revisa este backlog ao término de cada épico MVP e reavalia se algum gatilho já aconteceu. Itens podem ser promovidos a MVP-ampliação com justificativa explícita; nunca descartados sem registro.

---

## 1. Itens oriundos da auditoria comparativa externa (2026-04-16 v2)

### 1.1. Despacho automático de OS (location + skills + ETA)

- **Fonte:** KALIBRIUM SAAS (`/dispatch`, algoritmo de location + skills).
- **Valor:** otimização de rota para equipe de campo distribuída, reduz km rodado e aumenta OS/dia.
- **Por que não MVP:** MVP mira laboratório pequeno a médio (até 10 técnicos, geralmente 2-4 em campo simultâneo). Agenda manual + mapa das OS do dia (`REQ-FLD-006`) resolve. Otimização algorítmica só paga acima de 5 técnicos em rota simultânea.
- **Gatilho de reentrada:** primeiro cliente pagante com 5+ técnicos em campo simultâneos **ou** feedback de que rota manual está gerando conflito/ineficiência em 3+ clientes.
- **Escopo estimado quando entrar:** 1 épico (~8 stories) — motor de despacho, UI de sugestão, override manual, relatório de ganho.

### 1.2. Escalação automática de SLA

- **Fonte:** KALIBRIUM SAAS (`MonitorSlaDeadlinesJob`, `ProcessSlaViolationJob`, `EscalationRule`, `ServiceTicket`).
- **Valor:** monitora prazo contratual, reatribui automaticamente quando vence, notifica gerente em cascata.
- **Por que não MVP:** laboratório de calibração normalmente não vende SLA formal com cliente (vende prazo informal "5 dias úteis"). Indicador `REQ-OPL-003` (aderência ao prazo) cobre o essencial no MVP.
- **Gatilho de reentrada:** primeiro cliente pagante que exige cláusula formal de SLA em contrato **ou** 3+ clientes pedindo visibilidade contratual de prazo.
- **Escopo estimado:** 1 épico (~6 stories) — cadastro de SLA, monitor, regras de escalação, dashboard.

### 1.3. Contratos recorrentes com renovação automática

- **Fonte:** KALIBRIUM SAAS (`Contract`, `ContractMilestone`, `ContractRecurrenceTerm`, `ContractAlertDispatch`).
- **Valor:** cliente que calibra 20+ instrumentos/mês prefere contrato guarda-chuva anual; renovação automatizada reduz atrito comercial.
- **Por que não MVP:** pode ser simulado com orçamento-por-OS (`REQ-CRM-004/005`). Combinando com revalidação proativa (`REQ-CRM-008`, já no MVP v2), o valor comercial aparece sem o contrato formal.
- **Gatilho de reentrada:** primeiro cliente pagante que pede contrato anual formal **ou** mais de 30% dos clientes com 10+ calibrações/ano (evidência de que orçamento-por-OS vira atrito).
- **Escopo estimado:** 1 épico (~10 stories) — cadastro de contrato, vigências, milestones, cadência de renovação, ajuste de preço, integração com faturamento.

### 1.4. Portal do cliente self-service ampliado

- **Fonte:** KALIBRIUM SAAS (Portal ampliado).
- **Valor:** cliente final abre chamado, agenda online, baixa NF-e, exerce consentimento LGPD, vê histórico completo — sem precisar ligar no laboratório.
- **Cobertura atual no MVP (`REQ-FLX-005`):** baixar certificado + histórico de certificados do CNPJ + consulta de validade.
- **Por que não MVP:** ampliação completa é escopo grande; atrasaria E15-E20 (foundational) sem ganho proporcional. Canal LGPD do titular vai entrar no MVP via E21 por caminho minimalista (formulário público + e-mail ao DPO).
- **Gatilho de reentrada:** 3+ clientes pagantes pedirem ampliação do portal **ou** primeiro cliente que compra por preço e exige self-service completo como diferencial.
- **Escopo estimado:** 1 épico (~8 stories) — abrir chamado, agendar online, download NF-e, dashboard de consentimentos, notificações push ao cliente final.

### 1.5. Service tickets + helpdesk interno com SLA

- **Fonte:** KALIBRIUM SAAS (`ServiceTicket`, `SlaAgreement`, `EscalationRule`).
- **Valor:** cliente abre chamado ("certificado com erro de incerteza", "preciso de 2ª via do XML", "equipamento retornou danificado") → equipe do laboratório tria → escala → resolve.
- **Por que não MVP:** clientes-alvo do MVP são laboratórios pequenos/médios que atendem por WhatsApp + telefone. Helpdesk formal é over-engineering.
- **Gatilho de reentrada:** conjunto com SLA (#1.2) e portal ampliado (#1.4). Se ambos forem promovidos, helpdesk vem junto.
- **Escopo estimado:** 1 épico (~8 stories) — ciclo de ticket, triagem, atribuição, escalação, integração com SLA.

### 1.6. Persona "Especialista remoto / backoffice técnico"

- **Fonte:** KALIBRIUM SAAS (`RemoteAssistSession` + role `QUALITY`).
- **Valor:** sênior dedicado apoia técnicos em campo por vídeo/chat/screenshare; reduz retrabalho e acelera OS complexa.
- **Por que não MVP:** laboratório pequeno/médio (alvo MVP) tem Marcelo (sócio-gerente, Persona 1) cobrindo esse papel por WhatsApp com Carlos (Persona 2B). Persona dedicada só faz sentido em empresa com 5+ técnicos e 1-2 seniores dedicados.
- **Gatilho de reentrada:** primeiro cliente pagante com 5+ técnicos e 1-2 seniores dedicados em backoffice.
- **Escopo estimado:** 1 persona nova + 1 funcionalidade (assistência remota com vídeo/chat) = meio épico (~5 stories).

### 1.7. Persona 3 multi-usuário (cliente final com múltiplos papéis)

- **Fonte:** KALIBRIUM SAAS (role `CUSTOMER_PORTAL` multi-user).
- **Valor:** grande cliente industrial tem comprador (Rafael), financeiro, qualidade — papéis distintos do lado cliente. Hoje todos compartilham 1 login.
- **Por que não MVP:** alvo MVP é laboratório pequeno/médio cujo cliente é indústria pequena/média com comprador único. Multi-usuário vem naturalmente quando cliente grande for prospectado.
- **Gatilho de reentrada:** primeiro cliente pagante com indústria grande (>500 funcionários) **ou** 3+ pedidos de separação de papéis do lado cliente.
- **Escopo estimado:** meio épico (~4 stories) — ampliação do RBAC externo, convite de usuário dentro do mesmo cliente, delegação.

---

## 1-bis. Itens oriundos da re-auditoria independente (2026-04-16 v3)

### 1-bis.1. SLA completo com timer + pausa/retoma + escalonamento multi-nível

- **Fonte:** re-auditoria independente (`comparativa-externa-reaudit-2026-04-16.md` G5 / F-OS-04).
- **Valor:** contrato formal com SLA temporal (tempo de resposta, tempo de conclusão) monitorado em tempo real, com pausa (cliente não respondeu, aguardando peça), retoma, escalonamento por nível (técnico → gestor → gerente → diretoria).
- **Por que não MVP:** complexo (múltiplos estados, integrações com calendário de feriado, pausas justificadas), e depende de service tickets + helpdesk (também pós, §1.5). Cobertura MVP: aderência ao prazo em `REQ-OPL-003` mais indicador genérico, sem timer refinado.
- **Gatilho de reentrada:** primeiro cliente pagante que exige cláusula contratual de SLA **ou** quando service tickets (§1.5) for promovido.
- **Escopo estimado:** 1 épico (~8 stories).

### 1-bis.2. Billing SaaS nativo + self-service onboarding do tenant

- **Fonte:** re-auditoria independente (G11 / M7).
- **Valor:** novo laboratório se cadastra no Kalibrium sozinho (sem contato humano), paga com cartão/Pix via provedor integrado (Stripe/Iugu/Asaas), escolhe plano, configura tenant, começa a usar em minutos.
- **Por que não MVP:** no modelo atual o Kalibrium é vendido por time comercial humano (CAC alto), com onboarding humano assistido — faz sentido enquanto o ICP ainda está sendo validado. Self-service economiza CAC quando o produto é maduro e o ICP está consolidado.
- **Gatilho de reentrada:** ICP validado com 10+ clientes pagantes + decisão de produto de ir para crescimento escalável **ou** PLG (product-led growth) vira estratégia.
- **Escopo estimado:** 1 épico (~10 stories) — integração provedor, fluxo onboarding, seleção de plano, trial, upgrade/downgrade, cancelamento self-service.

### 1-bis.3. Cobrança automática do cliente final por faixa de atraso

- **Fonte:** re-auditoria independente (G13 / FL-22).
- **Valor:** boleto/Pix vencido dispara cadência: D+1 reminder gentil, D+7 cobrança formal, D+15 pré-negativação, D+30 negativação + serviço de proteção ao crédito (SPC/Serasa via provedor).
- **Por que não MVP:** MVP opera com Cláudia (Persona 7) fazendo cobrança manual — primeiros clientes pagantes são pequenos/conhecidos, inadimplência é exceção. Automação escala quando a base cresce.
- **Gatilho de reentrada:** 30+ clientes pagantes **ou** inadimplência passando 5% de `REQ-OPL-003` financeiro.
- **Escopo estimado:** meio épico (~5 stories) — cadência multinível, integração Serasa/SPC (opcional), dashboard de inadimplência.

---

## 2. Itens complementares registrados (não oriundos da auditoria v2, mas relacionados)

### 2.1. RFM + Lifecycle opportunities (CRM avançado)

- **Fonte:** KALIBRIUM SAAS (`CustomerRfmSnapshot`, `CustomerLifecycleOpportunity`).
- **Valor:** segmentação automática (A-E tiers), churn risk, oportunidades de expansão.
- **Gatilho:** MVP operando 6+ meses com dados suficientes para análise estatística ter sentido.
- **Escopo estimado:** meio épico (~4 stories).

### 2.2. MQTT IoT (sensor automático de temperatura/umidade)

- **Fonte:** KALIBRIUM SAAS (`mqtt-listen`, `/environment-reading`).
- **Valor:** elimina digitação manual de condições ambientais na bancada (`REQ-MET-004`).
- **Gatilho:** cliente pagante já em operação pedir **ou** evidência de que digitação manual está causando erro relevante em >5% das calibrações.
- **Escopo estimado:** meio épico (~4 stories).

### 2.3. Templates de OS + Sub-OS

- **Fonte:** KALIBRIUM SAAS (`WorkOrderTemplate`, `SubWorkOrder`).
- **Valor:** produtividade em OS recorrentes; decomposição de OS grandes.
- **Gatilho:** feedback consistente de técnicos de que criação de OS está lenta.
- **Escopo estimado:** meio épico (~4 stories).

### 2.4. Cadência comercial automática (CRM avançado)

- **Fonte:** KALIBRIUM SAAS (`CommercialCadence`, `CadenceStep`, `CadenceStepRun`).
- **Valor:** automação step-wise multicanal (e-mail + SMS + WhatsApp) para vendas, não apenas revalidação.
- **Cobertura parcial no MVP:** `REQ-CRM-006` (follow-up manual) + `REQ-CRM-008` (revalidação proativa, v2).
- **Gatilho:** MVP em operação + feedback dos vendedores pedindo automação.
- **Escopo estimado:** 1 épico (~7 stories).

### 2.5. Conciliação automática com gateway de pagamento (PSP)

- **Fonte:** KALIBRIUM SAAS (`/payment-gateway-webhook`, `RunPaymentGatewayReconciliationJob`).
- **Valor:** baixa automática no contas a receber quando cliente paga Pix/Boleto (hoje é manual via `REQ-FIS-005`).
- **Gatilho:** >50% dos clientes pedirem.
- **Escopo estimado:** meio épico (~4 stories).

### 2.6. Analytics/BI avançado (cross-módulo)

- **Fonte:** KALIBRIUM SAAS + sistema.
- **Valor:** dashboards comerciais, metrológicos, financeiros integrados.
- **Cobertura parcial no MVP:** `REQ-OPL-001/003` (dashboard operacional básico).
- **Gatilho:** gerente (Persona 1) pedir visão consolidada **ou** 3+ clientes pedirem BI.
- **Escopo estimado:** 1 épico (~8 stories).

### 2.7. Observabilidade com OpenTelemetry

- **Fonte:** sistema (`docker-compose.observability.yml`).
- **Valor:** traces, metrics e logs estruturados para diagnóstico de incidente.
- **Gatilho:** primeiro incidente de produção onde diagnóstico demorou mais do que aceitável **ou** >100 usuários ativos simultâneos.
- **Escopo estimado:** infra — não um épico de produto.

### 2.8. SAST/DAST no CI (Semgrep, ZAP, Gitleaks)

- **Fonte:** sistema (`.semgrep.yml`, `.zap/`, `.gitleaks.toml`).
- **Valor:** pipeline de segurança automatizado.
- **Gatilho:** antes do primeiro release em produção (pode entrar ainda no MVP como ampliação de E01 CI, se o time considerar).
- **Escopo estimado:** meio épico (~3 stories).

### 2.9. Comissão/incentivo de vendedor

- **Fonte:** sistema.
- **Valor:** cálculo automático de comissão por OS fechada.
- **Gatilho:** primeiro laboratório pagante com modelo de comissionamento formal para vendedor externo.
- **Escopo estimado:** meio épico (~4 stories).

### 2.10. Lead capture form + routing rules

- **Fonte:** KALIBRIUM SAAS (`LeadCaptureForm`, `LeadCaptureFormField`, `LeadCaptureRoutingRule`).
- **Valor:** formulário público em landing captura lead, roteia para vendedor por região/porte/origem.
- **Gatilho:** marketing digital ativo (tarefa de GTM, não de produto core).
- **Escopo estimado:** meio épico (~3 stories).

### 2.11. Rotação automática de credenciais + circuit breaker

- **Fonte:** KALIBRIUM SAAS (`rotate-api-keys`, `rotate-integration-credentials`, `reset-circuit-breaker`).
- **Valor:** higiene de segurança e resiliência de integrações.
- **Gatilho:** antes de GA (General Availability), não MVP.
- **Escopo estimado:** meio épico (~3 stories).

### 2.12. Broadcasting real-time (Pusher/Laravel Echo)

- **Fonte:** KALIBRIUM SAAS (channels).
- **Valor:** materializa a Jornada 10.2 (edição multi-usuário em tempo real quando todos online).
- **Cobertura atual:** menção textual em `journeys.md`, sem implementação.
- **Gatilho:** quando E16 (Sync Engine) estiver maduro e feedback apontar que tempo real "quase ok" é frustrante.
- **Escopo estimado:** meio épico (~3 stories).

---

## 3. Critério de promoção para MVP-ampliação

Um item deste backlog pode ser promovido para MVP (ou MVP-ampliação) se **pelo menos um** dos critérios for satisfeito:

1. **Gatilho de reentrada explícito aconteceu** — evento registrado em memória ou incidente.
2. **Pedido formal do PM** com justificativa de valor.
3. **Cliente pagante exigindo** com evidência documentada.
4. **Bloqueio técnico descoberto** — item é pré-requisito de outro MVP.

Promoção nunca é automática. Sempre passa por:
1. Revisão do brief do item neste backlog (atualizado se precisar).
2. Decisão do PM registrada em incidente ou PRD-ampliação.
3. Ajuste em `mvp-scope.md` + `ROADMAP.md`.
4. Decomposição em stories só depois.

---

## 4. Itens descartados (confirmação — fora do produto Kalibrium)

Não entram neste backlog porque foram **descartados** por estarem fora do domínio Kalibrium (confirmados em `mvp-scope.md §4`):

- eSocial + folha + benefícios + departamentos
- Ponto eletrônico com geofence
- Anvisa RDC validação regulatória (só sob demanda explícita)
- Contas a pagar (Kalibrium mira contas a receber)
- Gestão de ativos internos / patrimônio (Kalibrium não é ERP)
- Renegociação de dívidas (não é função de software de calibração)
- NF-e de mercadoria (Kalibrium é serviço)
- Integração Auvo ERP legado (customização por cliente)
- Personas RH/DP, Supplier, Inmetro Coordinator, Employment/Payroll

Se algum destes for reavaliado no futuro, precisa passar por **ampliação de escopo formal** com novo PRD-ampliação aprovado pelo PM, não por este backlog.
