# Modelo de Domínio — Kalibrium

> **Status:** ativo. Produzido pelo sub-agent `domain-analyst` em 2026-04-12, a partir do PRD, mvp-scope.md, personas.md e compliance/*.md.
> Artefato de domínio para decisões de arquitetura e decomposição de épicos. Não prescreve tecnologia.

---

## 1. Entidades Principais

### 1.1 Core Platform

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Tenant** | Organização cliente do SaaS. Contexto de isolamento máximo de dados. Pode conter N empresas. | id, plano, status (trial/ativo/suspenso/cancelado), data de ativação, owner admin |
| **Empresa** | CNPJ operacional dentro de um tenant. Tem filiais. Emite NF-e/NFS-e pelo seu CNPJ. | id, tenant_id, CNPJ, razão social, regime tributário, série fiscal |
| **Filial** | Estabelecimento de uma empresa. Pode ter laboratório, agenda e estoque próprios. | id, empresa_id, CNPJ de filial, endereço, séries fiscais, almoxarifado |
| **Usuário** | Pessoa com acesso ao sistema. Pode ter papéis diferentes em empresas diferentes. | id, tenant_id, e-mail, status, papéis por empresa |
| **Papel (Role)** | Conjunto de permissões atribuído a um usuário em uma empresa. Ex: gerente, técnico, administrativo, visualizador. | id, empresa_id, permissões, escopo |
| **Assinatura** | Contrato comercial do tenant com o Kalibrium. Define plano, entitlements, limites e ciclo de cobrança. | id, tenant_id, plano, entitlements, limites, data de início, data de renovação |

### 1.2 Revenue Engine

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Lead** | Contato ou organização em fase de prospecção. Estado inicial do funil comercial. | id, origem, segmento, responsável, status (lead/qualificado/oportunidade) |
| **Cliente** | Organização ou pessoa com relacionamento ativo com o tenant. Pode ter N contatos, N instrumentos. | id, tenant_id, CNPJ/CPF, razão social, contatos, limite de crédito |
| **Contato** | Pessoa física vinculada a um cliente. Pode ter e-mail, WhatsApp e papel (comprador, responsável técnico). | id, cliente_id, nome, e-mail, WhatsApp, consentimentos LGPD |
| **Proposta** | Documento comercial versionado enviado ao cliente com escopo, preços e condições. | id, cliente_id, versão, status, itens, validade, aprovações |
| **Contrato** | Acordo formal derivado de proposta aceita. Define SLA, frequência de cobrança e escopo de serviços. | id, cliente_id, proposta_id, tipo (avulso/recorrente/projeto/marco), vigência, modelo de faturamento |
| **Fatura** | Documento financeiro interno que consolida OS concluídas aguardando emissão fiscal. | id, contrato_id, OS vinculadas, status (rascunho/aprovada/emitida/cancelada), valor |
| **Título financeiro** | Cobrança a receber ou a pagar com vencimento, valor e status. | id, empresa_id, tipo (AR/AP), valor, vencimento, status (aberto/parcial/quitado/vencido) |
| **NFS-e / NF-e** | Documento fiscal eletrônico transmitido à prefeitura (NFS-e) ou SEFAZ (NF-e). | id, empresa_id, número, série, XML, status SEFAZ/prefeitura, chave de acesso |
| **Pré-fatura** | Rascunho de fatura gerado automaticamente a partir de OS concluídas. Aguarda revisão e aprovação. | id, OS vinculadas, valor calculado, status (pendente/aprovado/bloqueado) |

### 1.3 Service Operations

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Ordem de Serviço (OS)** | Entidade central de execução. Registra demanda, execução, evidências e resultado. Gera downstream fiscal, financeiro e documental. | id, cliente_id, instrumento_id, técnico_id, tipo, status (recebido/em calibração/aprovação/concluído/faturado), SLA, evidências |
| **Chamado (Ticket)** | Demanda de atendimento ou suporte que pode originar uma OS. | id, cliente_id, canal de origem, status, SLA, OS vinculada |
| **Agendamento** | Registro de data/hora de coleta ou execução vinculado a uma OS e a um técnico. | id, OS_id, técnico_id, data, janela, status, confirmação do cliente |
| **Checklist** | Lista de verificação vinculada a uma OS ou procedimento. Itens com resposta obrigatória. | id, OS_id, itens, respostas, evidências por item |
| **Evidência** | Arquivo multimídia (foto, vídeo, PDF, assinatura digital, geolocalização) vinculado a uma OS ou NC. | id, OS_id, tipo, URL, timestamp, autor, geolocalização quando aplicável |

### 1.4 Technical & Metrology

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Instrumento (do cliente)** | Equipamento de medição de propriedade do cliente entregue para calibração. | id, cliente_id, modelo, número de série, faixa, resolução, domínio metrológico |
| **Padrão de referência** | Instrumento metrológico do laboratório com certificado vigente e cadeia de rastreabilidade. | id, empresa_id, modelo, número de série, certificado, validade, padrão anterior na cadeia |
| **Calibração** | Registro técnico de uma execução de calibração: leituras, condições ambientais, padrões usados. | id, OS_id, instrumento_id, padrões_usados, leituras brutas, temperatura, umidade, técnico, data |
| **Procedimento de calibração** | Documento técnico aprovado que define método, equipamentos e cálculo de incerteza para um domínio. Versionado. | id, empresa_id, nome, versão, domínio metrológico, orçamento de incerteza, validade |
| **Orçamento de incerteza** | Planilha estruturada com componentes de incerteza, calculada conforme GUM/JCGM 100:2008. Versionada. | id, procedimento_id, versão, componentes, U (expandida), k (fator de cobertura) |
| **Certificado de calibração** | Documento resultante da calibração aprovada. Formato compatível com RBC quando acreditado. | id, calibração_id, número, PDF, QR de autenticidade, status (rascunho/assinado/emitido), validade |
| **Lacre / Selo** | Dispositivo físico aplicado ao instrumento após calibração. Rastreado por número de série e tipo (aprovação/reparo). | id, instrumento_id, OS_id, tipo, número, data de aplicação, vencimento PSEI |
| **NC (Não Conformidade)** | Desvio registrado no sistema de qualidade, com causa raiz, CAPA e evidência de eficácia. | id, empresa_id, origem (auditoria/campo/laboratório), causa raiz, CAPA, status, prazo |
| **Item de estoque** | Material ou peça consumível no processo técnico, controlado por lote, serial e almoxarifado. | id, empresa_id, código, descrição, saldo, reservas por OS, custo médio |
| **Ativo patrimonial** | Imobilizado da empresa com depreciação, responsável e apólice de seguro. | id, empresa_id, código patrimonial, valor, depreciação, responsável, apólice |

### 1.5 Corporate Backbone

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Colaborador** | Funcionário ou prestador vinculado ao tenant. Tem jornada, ponto, habilitações e papel operacional. | id, empresa_id, CPF, nome, cargo, tipo (CLT/PJ), jornada, habilitações |
| **Registro de ponto** | Marcação de entrada/saída do colaborador, incluindo marcações offline com localização e timestamp. | id, colaborador_id, timestamp, localização, modo (presencial/remoto/campo), status |
| **Evento eSocial** | Registro XML de evento trabalhista transmitido ao ambiente do governo federal. | id, empresa_id, tipo (S-2200/S-2206/...), XML, status transmissão, data |
| **Habilitação técnica** | Certificação ou aptidão do colaborador para executar um tipo de serviço ou operar em laboratório acreditado. Pode vencer. | id, colaborador_id, tipo, validade, bloqueio_OS quando vencida |
| **Documento GED** | Arquivo no sistema de gestão documental com ciclo de vida controlado (rascunho/revisão/publicado/obsoleto). | id, empresa_id, título, versão, status, owner, retenção, link externo quando aplicável |

### 1.6 External Experience

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Usuário externo** | Contato do cliente (ex: Rafael) com acesso ao portal do cliente com escopo limitado. | id, contato_id, escopo de acesso, consentimentos, data de expiração |
| **Fornecedor** | Organização cadastrada como fornecedor de materiais ou serviços para o tenant. | id, empresa_id, CNPJ, status (em homologação/aprovado/suspenso), documentos, score |
| **Parceiro de canal** | Empresa parceira cadastrada no programa de parceiros com tier e comissões. | id, nome, tier (Indicador/Silver/Gold/Platinum), leads vinculados, comissão, MDF |
| **Requisição de compra** | Solicitação interna de material ou serviço que inicia o fluxo de procurement. | id, empresa_id, solicitante, categoria, status, pedido_vinculado |
| **Cotação** | Proposta de fornecedor recebida em resposta a uma requisição. | id, requisição_id, fornecedor_id, valor, prazo, status |

---

## 2. Relacionamentos Principais

```
Tenant 1──N Empresa
Empresa 1──N Filial
Empresa 1──N Usuário (via papéis)
Empresa 1──N Cliente
Cliente 1──N Contato
Cliente 1──N Instrumento
Cliente 1──N Contrato
Contrato 1──N OS
OS 1──1 Calibração (quando é OS de calibração)
OS 1──N Evidência
OS N──N Padrão de referência (padrões usados na calibração)
Calibração 1──1 Procedimento de calibração (versão vigente)
Calibração 1──1 Certificado de calibração
Certificado 1──N Lacre/Selo (aplicados ao instrumento)
OS 1──1 Pré-fatura (agrupada) → Fatura → NFS-e → Título financeiro
NC 1──1 CAPA
Colaborador 1──N Habilitação técnica
Colaborador 1──N Registro de ponto
Empresa 1──N Evento eSocial
Empresa 1──N Documento GED
Tenant 1──1 Assinatura (plano SaaS)
```

---

## 3. Estados e Transições Críticas

### OS (Ordem de Serviço)
```
recebido → triado → agendado → atribuído → em execução
       → pausado (aguardando peça/cliente/terceiro)
       → concluído → aprovação pendente → aprovado
       → faturamento pendente → faturado → pago
       → reaberto | cancelado
```

### Certificado de calibração
```
rascunho → revisão técnica → dual sign-off → emitido → entregue ao cliente
```

### Tenant (ciclo de vida SaaS)
```
trial → ativo → [dunning] → suspenso (somente leitura) → cancelado
                           → reativado
```

### NFS-e / NF-e
```
rascunho → pré-fatura aprovada → transmitida SEFAZ/prefeitura
         → autorizada | rejeitada → reprocessada → cancelada
```

---

## 4. Bounded Contexts Sugeridos

| Bounded Context | Módulos incluídos | Entidade raiz |
|---|---|---|
| **Identity & Tenant** | Core, IAM, BillingSaaS, TenantOps | Tenant |
| **Commercial** | CRM, Quotes, Contracts, Pricing, PartnerOps | Cliente / Contrato |
| **Service Execution** | WorkOrders, Helpdesk, Agenda, Mobile, Operational | OS |
| **Laboratory & Metrology** | Lab, Inmetro, RepairSeals, WeightTool, Quality | Calibração / Certificado |
| **Finance & Fiscal** | Finance, Fiscal, Billing | Título financeiro / NFS-e |
| **People & Compliance** | HR, ESocial, LMS | Colaborador |
| **Supply Chain** | Inventory, Procurement, Fleet, Logistics, FixedAssets | Item de estoque / Ativo |
| **External Channels** | Portal, SupplierPortal, Omnichannel | Usuário externo |
| **Intelligence** | Analytics_BI, Alerts, Reports | — (consome outros contextos) |
| **SaaS Operations** | ProductOps, SupportOps | Tenant (visão interna) |

---

## 5. Eventos de Domínio Principais

| Evento | Gatilho | Efeitos downstream |
|---|---|---|
| `OS.concluida` | Técnico finaliza execução e submete | Gera certificado (Lab), aciona pré-fatura (Finance), notifica cliente (Portal), atualiza painel (BI) |
| `Certificado.emitido` | Dual sign-off aprovado | Envia ao portal do cliente, envia e-mail ao contato, atualiza histórico do instrumento |
| `NFS-e.autorizada` | SEFAZ/prefeitura retorna autorização | Cria título a receber (Finance), envia XML ao cliente, baixa pré-fatura |
| `Título.pago` | Conciliação bancária ou baixa manual | Marca OS como paga, atualiza DRE, calcula comissão do vendedor |
| `PadrãoReferência.vencido` | Data de validade atingida | Bloqueia uso do padrão em novas calibrações, gera alerta para responsável técnico |
| `HabilitaçãoTécnica.vencida` | Data de validade atingida | Bloqueia alocação do técnico em OS que exigem a habilitação |
| `NC.aberta` | Desvio detectado em auditoria ou campo | Abre CAPA, notifica responsável de qualidade, inicia prazo de tratativa |
| `InstrumentoCliente.calibraçãoVencendo` | N dias antes da validade | Notifica cliente no portal, cria oportunidade de nova OS (CRM) |
| `Tenant.limiteAtingido` | Consumo ≥ 80% do entitlement | Alerta admin do tenant, registra no health score |
| `Tenant.suspenso` | Dunning D+14 sem pagamento | Modo somente leitura, bloqueia novas OS e certificados, preserva dados |

---

## 6. Domínios Metrológicos cobertos no MVP

| Domínio | Exemplos de instrumento |
|---|---|
| Dimensional | Paquímetro, micrômetro, bloco padrão |
| Pressão | Manômetro, transmissor de pressão |
| Massa | Balança analítica, semi-analítica, comercial |
| Temperatura | Termômetro, termopar, PT100 |

Domínios fora do MVP (elétrico, óptico, vazão, torque, vibração) aguardam primeiro cliente pagante do domínio.
