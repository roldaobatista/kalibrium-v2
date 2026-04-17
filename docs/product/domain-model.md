# Modelo de Domínio — Kalibrium

> **Status:** ativo — ampliado em 2026-04-16 (v1 + v2 + v3). Produzido originalmente pelo sub-agent `domain-analyst` em 2026-04-12. Ampliação v1 cobre operação de campo, UMC, estoque multinível, despesa, offline-first e segurança móvel. Ampliação v2 cobre LGPD, SPC/drift, backup, push, ciclo de rejeição NFS-e, revalidação. **Ampliação v3** (pós re-auditoria independente) adiciona: competência técnica como gate, dual sign-off de certificado, suspensão retroativa quando padrão falha, agendamento automático de preventiva (recalibração de padrão + manutenção de veículo), regra de despacho round-robin, re-despacho por indisponibilidade, OS de garantia com custo zero, configuração tributária RTC 2026 (IBS/CBS/cIndOp), formalização técnica de isolamento multi-tenant (ADR-0016). Backup do estado anterior em `domain-model-backup-2026-04-16.md`.

---

## 1. Entidades Principais

### 1.1 Core Platform

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Tenant** | Organização cliente do SaaS. Contexto de isolamento máximo de dados. Pode conter N empresas. | id, plano, status (trial/ativo/suspenso/cancelado), data de ativação, owner admin |
| **Empresa** | CNPJ operacional dentro de um tenant. Tem filiais. Emite NF-e/NFS-e pelo seu CNPJ. | id, tenant_id, CNPJ, razão social, regime tributário, série fiscal |
| **Filial** | Estabelecimento de uma empresa. Pode ter laboratório, agenda e estoque próprios. | id, empresa_id, CNPJ de filial, endereço, séries fiscais, almoxarifado |
| **Usuário** | Pessoa com acesso ao sistema. Pode ter papéis diferentes em empresas diferentes. | id, tenant_id, e-mail, status, papéis por empresa |
| **Papel (Role)** | Conjunto de permissões atribuído a um usuário em uma empresa. Ex: gerente, técnico, motorista-UMC, vendedor, gestor-campo, administrativo, visualizador. | id, empresa_id, permissões, escopo |
| **Assinatura** | Contrato comercial do tenant com o Kalibrium. Define plano, entitlements, limites e ciclo de cobrança. | id, tenant_id, plano, entitlements, limites, data de início, data de renovação |
| **Dispositivo registrado** (novo) | Smartphone, tablet, notebook ou desktop autorizado a operar em nome de um usuário. Primeira login exige aprovação do administrador do tenant. | id, usuário_id, plataforma (iOS/Android/web/desktop), identificador único, data_registro, status (ativo/suspenso/wipe-solicitado/wipe-completo), última_sincronização |

### 1.2 Revenue Engine

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Lead** | Contato ou organização em fase de prospecção. Estado inicial do funil comercial. | id, origem, segmento, responsável, status (lead/qualificado/oportunidade) |
| **Cliente** | Organização ou pessoa com relacionamento ativo com o tenant. Pode ter N contatos, N instrumentos. | id, tenant_id, CNPJ/CPF, razão social, contatos, limite de crédito, carteira_vendedor_id (nullable) |
| **Contato** | Pessoa física vinculada a um cliente. Pode ter e-mail, WhatsApp e papel (comprador, responsável técnico). | id, cliente_id, nome, e-mail, WhatsApp, consentimentos LGPD |
| **Proposta** | Documento comercial versionado enviado ao cliente com escopo, preços e condições. Pode ser gerada offline em campo pelo vendedor externo. | id, cliente_id, versão, status, itens, validade, aprovações, origem (escritório/campo-offline), hash_offline (se gerada offline) |
| **Contrato** | Acordo formal derivado de proposta aceita. Define SLA, frequência de cobrança e escopo de serviços. | id, cliente_id, proposta_id, tipo (avulso/recorrente/projeto/marco), vigência, modelo de faturamento |
| **Fatura** | Documento financeiro interno que consolida OS concluídas aguardando emissão fiscal. | id, contrato_id, OS vinculadas, status (rascunho/aprovada/emitida/cancelada), valor |
| **Título financeiro** | Cobrança a receber ou a pagar com vencimento, valor e status. | id, empresa_id, tipo (AR/AP), valor, vencimento, status (aberto/parcial/quitado/vencido) |
| **NFS-e / NF-e** | Documento fiscal eletrônico transmitido à prefeitura (NFS-e) ou SEFAZ (NF-e). Pode ficar em estado "preparada offline, aguardando transmissão" quando o gestor em campo está sem sinal. | id, empresa_id, número, série, XML, status SEFAZ/prefeitura, chave de acesso, estado_transmissao (pendente/transmitindo/autorizada/rejeitada) |
| **Pré-fatura** | Rascunho de fatura gerado automaticamente a partir de OS concluídas. Aguarda revisão e aprovação. | id, OS vinculadas, valor calculado, status (pendente/aprovado/bloqueado) |
| **Visita comercial** (novo) | Registro de visita do vendedor externo ao cliente. Contém nota de voz/transcrição, foto, timestamp, GPS opcional. | id, cliente_id, vendedor_id, data, duração, nota_de_voz_url, transcrição, foto, geolocalização, follow_up_agendado |

### 1.3 Service Operations

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Ordem de Serviço (OS)** | Entidade central de execução. Registra demanda, execução, evidências e resultado. Suporta 3 modos (bancada / campo-veículo / campo-UMC). Pode ter equipe multi-pessoa. Gera downstream fiscal, financeiro e documental. | id, cliente_id, instrumento_id, responsável_id, tipo, modo (bancada/campo-veiculo/campo-umc), status (recebido/em calibração/aprovação/concluído/faturado + estados de campo), SLA, evidências, equipe_ids[], veiculo_id (nullable), umc_id (nullable) |
| **Equipe da OS** (novo) | Conjunto de usuários designados para executar uma OS. Até 5 pessoas. Inclui o responsável (dono da OS, pode fechar), técnicos adicionais, motorista UMC, auxiliares. | OS_id, usuário_id, papel_na_OS (responsável/técnico/motorista/auxiliar), atribuições, data_inclusao |
| **Chamado (Ticket)** | Demanda de atendimento ou suporte que pode originar uma OS. | id, cliente_id, canal de origem, status, SLA, OS vinculada |
| **Agendamento** | Registro de data/hora de coleta ou execução vinculado a uma OS e a um técnico. | id, OS_id, técnico_id, data, janela, status, confirmação do cliente |
| **Checklist** | Lista de verificação vinculada a uma OS ou procedimento. Itens com resposta obrigatória. | id, OS_id, itens, respostas, evidências por item |
| **Evidência** | Arquivo multimídia (foto, vídeo, PDF, assinatura digital, geolocalização) vinculado a uma OS ou NC. Fotos do campo (selo do instrumento, fachada, equipamento, cupom de despesa) são obrigatórias em certos fluxos. | id, OS_id, tipo (foto/vídeo/pdf/assinatura/geoloc), URL, timestamp, autor, geolocalização quando aplicável, obrigatoriedade (obrigatória/opcional) |
| **Deslocamento** (novo) | Registro de movimento do técnico ou da UMC durante uma OS de campo. Contém KM inicial, KM final, horário, GPS opcional. | id, OS_id, veiculo_id, usuário_id, km_inicial, km_final, horario_inicio, horario_fim, geoloc_inicio, geoloc_fim |
| **Assinatura do cliente** (novo) | Assinatura capturada no touch do dispositivo móvel ao final da visita. Contém nome do signatário, RG/cargo, timestamp, imagem SVG/PNG da assinatura, OS vinculada. | id, OS_id, nome, rg_ou_cargo, timestamp, imagem_url, hash |

### 1.4 Technical & Metrology

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Instrumento (do cliente)** | Equipamento de medição de propriedade do cliente entregue para calibração (bancada) ou calibrado in loco (campo). | id, cliente_id, modelo, número de série, faixa, resolução, domínio metrológico, localização_fisica (quando campo: "instalado no cliente") |
| **Padrão de referência** | Instrumento metrológico do laboratório com certificado vigente e cadeia de rastreabilidade. Existe em múltiplos locais de estoque (laboratório, UMC, veículo operacional, carro pessoal). | id, empresa_id, modelo, número de série, certificado, validade, padrão anterior na cadeia, local_estoque_atual |
| **Calibração** | Registro técnico de uma execução de calibração: leituras, condições ambientais, padrões usados, local (bancada ou campo). | id, OS_id, instrumento_id, padrões_usados, leituras brutas, temperatura, umidade, técnico, data, local (bancada/campo-veiculo/campo-umc), registrada_offline (bool) |
| **Procedimento de calibração** | Documento técnico aprovado que define método, equipamentos e cálculo de incerteza para um domínio. Versionado. Disponível offline no dispositivo do técnico. | id, empresa_id, nome, versão, domínio metrológico, orçamento de incerteza, validade |
| **Orçamento de incerteza** | Planilha estruturada com componentes de incerteza, calculada conforme GUM/JCGM 100:2008. Versionada. Cálculo roda localmente no dispositivo (funciona offline). | id, procedimento_id, versão, componentes, U (expandida), k (fator de cobertura) |
| **Certificado de calibração** | Documento resultante da calibração aprovada. Formato compatível com RBC quando acreditado. Pode ser gerado offline em campo (PDF local) e transmitido na sync. | id, calibração_id, número, PDF, QR de autenticidade, status (rascunho/assinado/emitido), validade, gerado_offline (bool) |
| **Lacre / Selo** | Dispositivo físico aplicado ao instrumento após calibração. Rastreado por número de série e tipo (aprovação/reparo). | id, instrumento_id, OS_id, tipo, número, data de aplicação, vencimento PSEI |
| **NC (Não Conformidade)** | Desvio registrado no sistema de qualidade, com causa raiz, CAPA e evidência de eficácia. | id, empresa_id, origem (auditoria/campo/laboratório), causa raiz, CAPA, status, prazo |
| **Item de estoque** | Material ou peça consumível no processo técnico, controlado por lote, serial e almoxarifado. Suporta 4 localizações de estoque (ver §1.7 abaixo). | id, empresa_id, código, descrição, saldo, reservas por OS, custo médio, localização_atual |
| **Ativo patrimonial** | Imobilizado da empresa com depreciação, responsável e apólice de seguro. Inclui veículos operacionais, UMC, equipamentos de laboratório. | id, empresa_id, código patrimonial, valor, depreciação, responsável, apólice |

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

### 1.7 Frota e Operação de Campo (novo)

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **UMC (Unidade Móvel de Calibração)** | Caminhão especializado com guindaste e massas-padrão pesadas a bordo. Usado para calibração de balança rodoviária, balança industrial grande e outros serviços que exigem massa-padrão pesada. Um tenant pode ter 1 ou mais UMC. | id, empresa_id, placa, chassi, modelo, ano, capacidade_guindaste_kg, motorista_principal_id, status (disponível/em_OS/manutenção/inativa), próxima_manutenção_km, próxima_manutenção_data |
| **Veículo operacional** | Caminhonete ou carro pequeno usado pelo técnico em visitas de campo comuns (sem UMC). Pode ser assinado (fixo a um técnico) ou compartilhado (pool). | id, empresa_id, placa, modelo, ano, tipo (caminhonete/carro), modo_uso (assinado/compartilhado), técnico_responsável_id (nullable), status (disponível/em_uso/manutenção/inativo) |
| **Estoque por localização** (novo) | Estoque de itens (padrões, peças, materiais) em cada um dos 4 locais: laboratório central, UMC a bordo, veículo operacional a bordo, carro pessoal do técnico (mini-estoque portátil). | id, empresa_id, local (laboratorio/umc_id/veiculo_id/tecnico_id), item_id, saldo, última_conferencia |
| **Movimentação de estoque** (novo) | Transferência de item entre locais (ex: "Carlos retirou padrão X do laboratório e colocou no carro dele"). Rastreável com responsável e timestamp. | id, item_id, origem (local), destino (local), quantidade, responsável_usuário_id, timestamp, OS_id (se movimentação foi para uma OS específica) |
| **Diário de bordo** (novo) | Registros do dia de um veículo (UMC ou operacional): KM inicial, KM final, abastecimento (com foto), pedágio (com foto), manutenção incidental, rota percorrida. | id, veiculo_id (UMC ou operacional), data, motorista_ou_técnico_id, km_inicial, km_final, eventos[] |

### 1.8 Caixa de Despesa por OS (novo)

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Despesa** | Gasto de campo atrelado obrigatoriamente a uma OS. Foto do cupom/nota é obrigatória. Tem tipo, valor, origem do dinheiro. | id, OS_id (obrigatório), usuário_id, tipo (combustivel/pedagio/almoco/estacionamento/hotel/outros), valor, foto_cupom_url (obrigatório), origem_dinheiro (cartao_corporativo/adiantamento/proprio_bolso), data, status (registrada/em_triagem/aprovada/rejeitada/reclassificada) |
| **Cartão corporativo** | Cartão de crédito/débito da empresa entregue a técnico/motorista. Cada gasto no cartão vira despesa atrelada a OS. | id, empresa_id, número_mascarado, limite_mensal, portador_usuário_id, status |
| **Adiantamento em dinheiro** | Dinheiro entregue ao técnico/motorista antes de viagem longa. Acerto de contas quando volta. | id, empresa_id, usuário_id, valor, data_entrega, OS_id_vinculada (ou lote_ids), status (entregue/em_uso/acertado), saldo_residual |
| **Reembolso** | Pagamento ao técnico/motorista por despesa paga do próprio bolso, após aprovação. | id, usuário_id, despesas_ids[], valor_total, data_solicitação, data_pagamento, método (pix/transferencia), status |
| **Fatura do cartão corporativo** | Fatura mensal do cartão importada para conciliação com as despesas registradas. Discrepância vira alerta. | id, cartão_id, mês_referência, csv_importado, total_fatura, despesas_conciliadas[], discrepâncias[] |

### 1.9-bis Compliance e Qualidade Ampliada (novo v2)

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Requisição LGPD** | Pedido formal do titular (cliente, ex-cliente, contato) para exercer direito previsto na LGPD: acesso, retificação, exclusão, portabilidade ou revogação de consentimento. Entrada por portal ou canal de e-mail dedicado. Atendida pelo DPO. Cobertura de `REQ-CMP-006`. | id, tenant_id, protocolo, titular_identificacao (CPF/CNPJ + nome + email), tipo (acesso/retificacao/exclusao/portabilidade/revogacao), descricao, status (aberta/em_triagem/atendida/negada/vencida), data_abertura, prazo_legal, data_resposta, dpo_usuario_id, resposta_conteudo_url, log_imutavel_ids[] |
| **Configuração de gráfico de controle (SPC)** | Parâmetros estatísticos para monitorar estabilidade de um padrão de referência: média histórica, UCL, LCL, warning lines, regras de Nelson habilitadas. Cobertura de `REQ-MET-009`. | id, padrao_id, media_central, ucl, lcl, warning_upper_sigma1, warning_lower_sigma1, warning_upper_sigma2, warning_lower_sigma2, regras_nelson_habilitadas[], calculado_em, janela_historico (N últimas calibrações do padrão) |
| **Leitura de gráfico de controle (SPC reading)** | Ponto individual plotado no gráfico de controle: uma calibração do próprio padrão (recalibração externa ou medição de controle interna). Cobertura de `REQ-MET-009`. | id, padrao_id, chart_config_id, data_leitura, valor_medido, desvio_do_nominal, origem (recalibracao_externa/verificacao_interna), regra_nelson_violada (nullable), observacao_textual, autor_usuario_id |
| **Alerta de drift** | Detecção automática de desvio do padrão em relação ao comportamento histórico. Gerado quando leitura nova viola regra de Nelson. Cobertura de `REQ-MET-010`. | id, padrao_id, reading_id, regra_nelson_violada, severidade (warning/critical), status (aberto/aceito/em_verificacao/bloqueio_aplicado/recalibracao_solicitada/resolvido), responsavel_qualidade_id, acao_tomada, data_acao |
| **Job de backup** | Execução agendada de backup do tenant com verificação de integridade por restore isolado + checksum. Append-only. Cobertura de `REQ-CMP-007`. | id, tenant_id, tipo (diario/snapshot_mensal), data_inicio, data_fim, tamanho_bytes, checksum_sha256, destino (storage_uri), status (iniciado/copiando/verificando/concluido/falhou), erro_detalhe, retencao_ate, restore_test_resultado |
| **Assinatura de push** | Token de dispositivo para envio de push notification via APNs (iOS) ou FCM (Android). Cobertura de `REQ-FLX-007`. | id, dispositivo_id, usuario_id, plataforma (ios/android/web-push), token, categoria_consentimento (nova_os/sla/despesa/conflito_sync/revalidacao/lgpd/drift), ativa (bool), criada_em, renovada_em |
| **Cadência de revalidação** | Cadência automática disparada para contato do cliente quando certificado de instrumento se aproxima do vencimento. Cobertura de `REQ-CRM-008`. | id, certificado_origem_id, instrumento_id, contato_id, cliente_id, vendedor_responsavel_id, etapa (90d/60d/30d), canal (email/whatsapp/ambos), status (enviado/visto/clicado/agendado/convertido/recusado/desistiu), link_agendamento_assinado, os_convertida_id (nullable), data_envio, data_acao_cliente, motivo_recusa (nullable) |

### 1.9-ter Qualidade ISO 17025 + Operação Robusta (novo v3)

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Competência técnica vigente** | Visão materializada da habilitação técnica de um colaborador por domínio metrológico em uma data específica. Usada para decisões em tempo real (inclusive offline). Cobertura de `REQ-MET-011`. | id, colaborador_id, dominio_metrologico, habilitacao_tecnica_id, valida_desde, valida_ate, status (vigente/vencida/suspensa), fonte (certificado/treinamento/experiencia) |
| **Dual sign-off de certificado** | Registro da dupla assinatura de um certificado de calibração: executor (quem calibrou) + verificador (técnico distinto ou Responsável de Qualidade). Cobertura de `REQ-MET-012`. | id, certificado_id, executor_usuario_id, executor_assinatura_timestamp, executor_biometria_ok, verificador_usuario_id (obrigatoriamente != executor), verificador_assinatura_timestamp, verificador_biometria_ok, status (aguardando_verificacao/verificado/devolvido_retrabalho), motivo_devolucao (nullable) |
| **Suspensão retroativa** | Bloco formal disparado quando padrão falha. Identifica todos os certificados emitidos com aquele padrão desde a última calibração válida. Cobertura de `REQ-MET-013`. | id, padrao_id, data_evento_falha, data_ultima_calibracao_valida, severidade (leve/grave), certificados_afetados_ids[], status (triagem/decidida_leve/decidida_grave/resolvida), responsavel_qualidade_id, os_garantia_geradas_ids[], notificacoes_clientes_status (pendente/em_envio/concluida) |
| **Agendamento preventivo** | OS interna de manutenção preventiva ou recalibração automática gerada por scheduler conforme regra (tempo/KM/evento). Cobertura de `REQ-MET-014` e `REQ-UMC-005`. | id, tipo (recalibracao_padrao/manutencao_veiculo/manutencao_umc), alvo_id (padrao_id OU veiculo_id OU umc_id), regra_aplicada (tempo_6m/km_10k/apos_viagem_longa/etc), data_prevista, janela_bloqueio_inicio, janela_bloqueio_fim, os_interna_id (gerada), status (previsto/em_execucao/concluido/atrasado) |
| **Regra de despacho** | Configuração da estratégia de distribuição de OS para técnicos. MVP: round-robin. Pós-MVP: skill-match + geo. Cobertura de `REQ-OPL-005` e `REQ-OPL-006`. | id, tenant_id, estrategia (round_robin_mvp), filtros (dominio_metrologico/disponibilidade/habilitacao_vigente/carga_atual), fallback_pendente_reatribuicao (bool), habilitada (bool), ultima_atualizacao |
| **Reatribuição de OS** | Evento de re-despacho automático quando técnico/UMC fica indisponível. Cobertura de `REQ-OPL-006`. | id, os_id, tecnico_original_id, tecnico_novo_id (nullable se nenhum disponível), motivo (doenca/panne_umc/competencia_vencida/conflito_agenda), disparado_em, notificacoes_enviadas[] (tecnico/cliente/gerente), status (reatribuida_auto/pendente_reatribuicao_manual) |
| **Classificação de OS (garantia)** | Quando OS é do tipo `garantia`, campos adicionais: referência à OS original (se regarantia de calibração anterior), referência à não conformidade (se oriunda da Jornada 15), custo real vs faturado. Cobertura de `REQ-OPL-007`. | os_id, tipo (comercial/garantia/interna_recalibracao/interna_manutencao), os_original_id (nullable), nc_id (nullable), valor_faturado (zero se garantia), custo_real_acumulado (soma das despesas), motivo_garantia |
| **Configuração tributária RTC** | Regras versionadas de cálculo de IBS/CBS/cIndOp conforme LC 214/2025 e NT SE/CGNFS-e. Cobertura de `REQ-FIS-009`. | id, vigencia_inicio, vigencia_fim (nullable), regime (lc_214), aliquota_ibs, aliquota_cbs, regras_cind_op[], matriz_municipios[], fallback_regime_anterior (bool), homologada_sandbox_rfb (bool), observacoes |

### 1.9 Sincronização Offline-First (novo)

| Entidade | Descrição | Atributos-chave |
|---|---|---|
| **Registro de sincronização** | Log de cada evento de sync: dispositivo, usuário, timestamp, delta enviado/recebido, resultado. Append-only. | id, dispositivo_id, usuário_id, timestamp, direção (upload/download/bidirecional), delta_resumo, registros_afetados[], resultado (sucesso/falha_parcial/falha_total), erro_detalhe |
| **Conflito de sincronização** | Detecção de duas edições no mesmo campo da mesma OS, feitas offline por membros diferentes da equipe, que chegaram ao servidor em tempos diferentes. Requer resolução manual pelo responsável da OS. | id, OS_id, entidade, campo, valor_a (autor_a, timestamp_a), valor_b (autor_b, timestamp_b), status (pendente/resolvido), resolvido_por_usuário_id, valor_final, data_resolução |
| **Fila de sincronização local** | Lista de operações pendentes no dispositivo (criadas offline, ainda não enviadas). Gerenciada pelo app mobile. Conceito de cliente, mas modelado para observabilidade (quantos dispositivos têm fila acumulada? qual o tamanho?). | dispositivo_id, quantidade_operações, tamanho_estimado, idade_da_mais_antiga |

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
Cliente N──1 Vendedor (carteira)             [novo]
Contrato 1──N OS
OS 1──1 Calibração (quando é OS de calibração)
OS 1──N Evidência
OS N──N Padrão de referência (padrões usados na calibração)
OS N──N Usuário (equipe)                     [novo — até 5 pessoas]
OS 1──N Deslocamento                         [novo]
OS 0..1──1 UMC (quando modo=campo-umc)       [novo]
OS 0..1──1 Veículo operacional (quando modo=campo-veiculo) [novo]
OS 1──N Despesa                              [novo — obrigatório atrelamento]
OS 1──N Assinatura do cliente                [novo — campo exige ao fim]
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

UMC 1──N Padrão de referência (a bordo)      [novo]
UMC 1──N Diário de bordo                     [novo]
Veículo operacional 1──N Diário de bordo     [novo]
Veículo operacional 0..1──1 Colaborador (responsável se assinado) [novo]
Item de estoque N──N Local (4 níveis via Estoque por localização) [novo]
Despesa 1──1 Cartão corporativo / Adiantamento / Reembolso (via origem_dinheiro) [novo]
Cartão corporativo 1──N Despesa              [novo]
Adiantamento 1──N Despesa                    [novo]

Usuário 1──N Dispositivo registrado          [novo]
Dispositivo registrado 1──N Registro de sincronização [novo]
OS 1──N Conflito de sincronização            [novo — raro]
Vendedor 1──N Visita comercial               [novo]

Titular (cliente/contato/ex-cliente) 1──N Requisição LGPD  [novo v2]
Requisição LGPD N──1 DPO (Usuário papel)      [novo v2]
Padrão de referência 1──1 Configuração de gráfico de controle [novo v2]
Configuração de gráfico de controle 1──N Leitura de gráfico de controle [novo v2]
Leitura de gráfico de controle 0..N──1 Alerta de drift (se violar regra Nelson) [novo v2]
Tenant 1──N Job de backup                    [novo v2]
Dispositivo registrado 1──N Assinatura de push [novo v2]
Certificado de calibração 0..N──1 Cadência de revalidação (gatilho) [novo v2]
Cadência de revalidação 0..1──1 OS (convertida quando cliente aceita) [novo v2]
```

---

## 3. Estados e Transições Críticas

### OS (Ordem de Serviço)
```
recebido → triado → agendado → atribuído (equipe designada)
       → [se modo=campo] deslocamento_iniciado → chegou_cliente
       → em execução
       → pausado (aguardando peça/cliente/terceiro)
       → concluído
       → [se modo=campo] sincronizando (dados ainda subindo do dispositivo)
       → aprovação pendente → aprovado
       → faturamento pendente → faturado → pago
       → reaberto | cancelado
```

### Certificado de calibração
```
rascunho → revisão técnica → dual sign-off → emitido → entregue ao cliente
          (pode ser gerado offline no dispositivo e transmitido na sync)
```

### Tenant (ciclo de vida SaaS)
```
trial → ativo → [dunning] → suspenso (somente leitura) → cancelado
                           → reativado
```

### NFS-e / NF-e (ampliado v2 — ciclo formal de rejeição)
```
rascunho → pré-fatura aprovada → transmitindo (prefeitura)
         → autorizada (sucesso) → entregue_cliente
         | rejeitada (motivo capturado: retenção errada, dado cliente, série duplicada, etc)
                → em_correcao (operador ajusta ponto exato)
                → retransmitida → autorizada (sucesso no segundo tentativa)
                | rejeitada de novo → volta para em_correcao (loop até autorizar ou desistir)
                | desistir (motivo registrado, escalação gerencial)
         ↳ preparada_offline (quando gestor em campo sem sinal) → transmitindo → ciclo acima
```

### Despesa (novo)
```
registrada (no dispositivo, foto + OS obrigatória)
        → em_triagem (escritório revisa)
        → aprovada | rejeitada | reclassificada (tipo ou OS trocada)
        → aprovada → paga (via cartão corporativo fechado, adiantamento acertado, ou reembolso pago)
```

### Dispositivo registrado (novo)
```
registrado (primeira login aprovada pelo admin)
        → ativo (operando normal)
        → wipe-solicitado (admin pediu limpeza remota)
        → wipe-completo (dispositivo limpou dados locais na próxima abertura/sync)
        → suspenso (bloqueado pelo admin, pode reativar) | revogado
```

### Conflito de sincronização (novo)
```
detectado (sync identificou edição dupla no mesmo campo)
        → notificado (responsável da OS foi avisado)
        → em_resolução (responsável abriu a tela de conflito)
        → resolvido (valor final escolhido, audit log registrado)
```

### Requisição LGPD (novo v2)
```
aberta (titular abriu pedido via portal/email)
        → em_triagem (DPO validou identidade)
        → em_atendimento (gerando relatório/aplicando retificação/anonimizando)
        → atendida (resposta enviada com protocolo + log imutável)
        | negada (base legal de retenção ou identidade não reconhecida)
        | vencida (prazo legal de 15 dias estourou sem resposta — incidente)
```

### Alerta de drift (novo v2)
```
aberto (sistema detectou violação de regra de Nelson)
        → notificado (Responsável de Qualidade recebeu push + e-mail)
        → em_verificacao (Responsável de Qualidade abriu)
        → aceito (drift dentro de limite aceitável, anotação registrada)
        | bloqueio_aplicado (padrão bloqueado preventivamente)
        | recalibracao_solicitada (OS interna aberta, padrão em "aguardando retorno")
        → resolvido (padrão de volta em operação com evidência)
```

### Cadência de revalidação (novo v2)
```
enviado (90 ou 60 ou 30 dias antes do vencimento)
        → visto (abertura de e-mail ou entrega WhatsApp)
        → clicado (cliente acessou link de agendamento)
        → agendado (cliente escolheu data)
        → convertido (OS nova criada automaticamente)
        | recusado (cliente disse "não quero")
        | desistiu (3 tentativas sem resposta — para de enviar)
```

### Job de backup (novo v2)
```
iniciado → copiando → verificando (restore em sandbox + checksum) → concluido
                                                                   | falhou → alerta_operacao
```

### Certificado de calibração — dual sign-off (novo v3)
```
rascunho → executor_assinou (aguardando_verificacao)
        → verificador_assinou (verificado) → emitido → entregue_cliente
        | devolvido_retrabalho (verificador rejeitou com motivo) → volta para rascunho
```

### Suspensão retroativa (novo v3)
```
triagem (sistema pré-computou certificados afetados)
        → decidida_leve (Responsável marca cada cert como revisado) → resolvida
        | decidida_grave (Responsável suspende todos) → notificando_clientes → gerando_os_garantia → resolvida
```

### Agendamento preventivo (novo v3)
```
previsto (scheduler criou entrada N dias antes)
        → em_execucao (OS interna gerada + agenda bloqueada)
        → concluido (recalibração/manutenção executada, registro atualizado)
        | atrasado (data passou sem execução — alerta crítico)
```

### Reatribuição de OS (novo v3)
```
disparada (indisponibilidade detectada)
        → reatribuida_auto (novo técnico encontrado + notificações enviadas)
        | pendente_reatribuicao_manual (nenhum técnico disponível — alerta crítico ao gerente)
            → reatribuida_manual (gerente escolheu técnico ou contratou externo)
            | reagendada (gerente reagendou com cliente)
            | cancelada (cliente cancelou)
```

---

## 4. Bounded Contexts Sugeridos

| Bounded Context | Módulos incluídos | Entidade raiz |
|---|---|---|
| **Identity & Tenant** | Core, IAM, BillingSaaS, TenantOps, **Device Management (novo)** | Tenant |
| **Commercial** | CRM, Quotes, Contracts, Pricing, PartnerOps, **Field CRM (novo)** | Cliente / Contrato |
| **Service Execution** | WorkOrders, Helpdesk, Agenda, Mobile, Operational, **Field Operations (novo)** | OS |
| **Laboratory & Metrology** | Lab, Inmetro, RepairSeals, WeightTool, Quality | Calibração / Certificado |
| **Finance & Fiscal** | Finance, Fiscal, Billing, **Expense-per-OS (novo)** | Título financeiro / NFS-e / Despesa |
| **People & Compliance** | HR, ESocial, LMS | Colaborador |
| **Supply Chain** | Inventory (agora **Multi-location Inventory**), Procurement, Fleet (**com UMC + Veículo operacional**), Logistics, FixedAssets | Item de estoque / Ativo / UMC / Veículo operacional |
| **External Channels** | Portal, SupplierPortal, Omnichannel | Usuário externo |
| **Intelligence** | Analytics_BI, Alerts, Reports | — (consome outros contextos) |
| **SaaS Operations** | ProductOps, SupportOps | Tenant (visão interna) |
| **Sync & Offline (novo)** | Sync Engine, Conflict Resolution, Device Sync, Encrypted Local Storage | Registro de sincronização / Dispositivo registrado |
| **Compliance Ampliada (novo v2)** | LGPD Titular Journey, Backup & Integrity, Push Subscription | Requisição LGPD / Job de backup / Assinatura de push |
| **Quality & SPC (novo v2)** | Statistical Process Control, Drift Detection, ISO 17025 Audit Trail | Configuração de gráfico de controle / Alerta de drift |
| **Recurring Engagement (novo v2)** | Revalidação Proativa, Cadência Multi-canal | Cadência de revalidação |
| **ISO 17025 Enforcement (novo v3)** | Competência como Gate, Dual Sign-off, Suspensão Retroativa | Competência técnica vigente / Dual sign-off / Suspensão retroativa |
| **Dispatch & Scheduling (novo v3)** | Round-robin, Re-dispatch, Preventiva Automática | Regra de despacho / Reatribuição / Agendamento preventivo |
| **Tax Compliance 2026 (novo v3)** | RTC 2026 (IBS/CBS/cIndOp), Versionamento Tributário | Configuração tributária RTC |

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
| **Eventos de operação de campo (novo)** | | |
| `OS.deslocamento_iniciado` | Técnico/motorista apertou "saindo" no app | Atualiza painel, inicia rastreio de KM/horário |
| `OS.equipe_chegou_cliente` | Check-in feito no cliente | Atualiza painel, inicia contagem de tempo no local |
| `OS.equipe_saiu_cliente` | Check-out feito | Encerra janela da visita, consolida custo preliminar |
| `OS.sincronizando` | Dispositivo começou a subir dados de uma OS de campo | Flag visual no painel do escritório |
| `OS.sincronizada` | Sync completo, todos os dados no servidor | OS avança de "concluído" para "aprovação pendente" |
| `OS.conflito_detectado` | Sync identificou edição dupla no mesmo campo | Notifica responsável da OS, bloqueia aprovação até resolução |
| **Eventos de despesa (novo)** | | |
| `Despesa.registrada` | Técnico registrou despesa no app (com foto + OS) | Baixa saldo otimista do cartão corporativo / adiantamento, aparece na fila de triagem |
| `Despesa.em_triagem` | Administrativa abriu pra revisar | Status muda, audit log |
| `Despesa.aprovada` | Aprovação final (escritório ou gerente conforme alçada) | Se origem=próprio-bolso, gera registro pendente de reembolso; senão, fecha o ciclo |
| `Despesa.rejeitada` | Triagem rejeitou (foto ilegível, categoria errada, etc) | Notifica o técnico, pede correção |
| `Despesa.reclassificada` | Triagem mudou tipo/OS/categoria | Recalcula custo real da OS afetada |
| `Reembolso.pago` | Reembolso processado via PIX/transferência | Fecha despesa, atualiza conta corrente |
| `CartãoCorporativo.faturaImportada` | Fatura mensal importada (CSV) | Dispara conciliação automática, lista discrepâncias |
| **Eventos de frota (novo)** | | |
| `UMC.manutencao_proxima` | KM ou data de manutenção preventiva se aproximando | Bloqueia agenda da UMC nas datas previstas, alerta gerente |
| `UMC.padrão_vencendo` | Padrão a bordo da UMC com validade próxima | Alerta responsável técnico, bloqueia uso do padrão quando vencer |
| `Veículo.manutencao_proxima` | Veículo operacional com manutenção prevista | Alerta técnico responsável (se assinado) ou pool |
| `Estoque.movimentação_registrada` | Item movido entre locais (lab → UMC, UMC → veículo, etc) | Atualiza saldos por local, audit log |
| **Eventos de segurança e dispositivo (novo)** | | |
| `Dispositivo.registrado` | Primeira login de um dispositivo novo aprovada | Registra impressão digital do device, habilita operação |
| `Dispositivo.wipe_solicitado` | Admin acionou wipe remoto (celular perdido/roubado) | Aguarda próxima abertura/sync do device pra executar |
| `Dispositivo.wipe_completo` | Device limpou dados locais | Atualiza status, audit log, bloqueia dispositivo |
| `Biometria.falhou_multiplas_vezes` | 5+ falhas de biometria/PIN em curto período | Bloqueia app localmente, exige reautenticação via e-mail+senha |
| **Eventos de compliance ampliada (novo v2)** | | |
| `NfseRejeitada` | Prefeitura retornou rejeição da NFS-e | Notifica operador (push + e-mail), apresenta motivo em linguagem clara, abre fluxo de correção, mantém trilha |
| `NfseRetransmitida` | Operador corrigiu ponto apontado e disparou retransmissão | Dispara nova transmissão à prefeitura, audit log registra correção com before/after |
| `RequisicaoLgpdAberta` | Titular abriu requisição via portal/e-mail | Gera protocolo, notifica DPO (push + e-mail), inicia contagem de prazo legal 15 dias |
| `RequisicaoLgpdAtendida` | DPO concluiu atendimento e enviou resposta | Envia e-mail com anexo ao titular, registra log imutável, fecha protocolo |
| `RequisicaoLgpdVencendo` | 5 dias restantes para o prazo legal | Alerta crítico para DPO + gerente (Persona 1) |
| `BreachDetectado` | Sistema detectou acesso não autorizado ou vazamento | Alerta imediato ao DPO, dispara contagem de 72h para notificação ANPD, oferece template de notificação |
| `BackupConcluido` | Job de backup rodou com sucesso e checksum validado | Atualiza dashboard de ops, incrementa contador de backups válidos |
| `BackupFalhou` | Job falhou ou checksum não bateu | Alerta crítico para ops, notifica gerente (Persona 1), bloqueia operações de modificação até backup ser ressincronizado |
| **Eventos de qualidade e SPC (novo v2)** | | |
| `PadraoCalibrado` | Padrão de referência foi recalibrado (externamente ou internamente) | Adiciona leitura nova ao gráfico de controle, recalcula SPC, verifica regras de Nelson |
| `DriftDetectado` | Regra de Nelson violada em nova leitura do padrão | Abre alerta, notifica Responsável de Qualidade (push + e-mail), registra severidade |
| `PadraoBloqueadoPorDrift` | Responsável aceitou bloqueio preventivo | Comportamento idêntico a `PadrãoReferência.vencido` — bloqueia uso em novas calibrações |
| `OsInternaRecalibracaoAberta` | Responsável decidiu mandar padrão para recalibração externa | Cria OS interna, padrão fica em `aguardando_retorno`, agenda rastreio |
| **Eventos de revalidação proativa (novo v2)** | | |
| `RevalidacaoDisparada` | Job agendado detectou certificado 90/60/30d do vencimento | Envia e-mail/WhatsApp ao contato do cliente com link de agendamento |
| `RevalidacaoClicada` | Cliente acessou link de agendamento | Atualiza status da cadência, notifica vendedor responsável (push) |
| `RevalidacaoConvertida` | Cliente confirmou data de agendamento | Cria OS nova automaticamente, notifica vendedor (push), fecha cadência |
| `RevalidacaoRecusada` | Cliente clicou "não quero mais" | Para cadência para aquele instrumento, registra motivo |
| **Eventos de push (novo v2)** | | |
| `PushSubscriptionRegistrada` | Usuário aceitou receber push no primeiro login | Armazena token APNs/FCM, habilita envio por categoria |
| `PushEnviado` | Sistema disparou push ao dispositivo | Log append-only (categoria, timestamp, dispositivo, conteúdo resumido) |
| `PushCategoriaDesligada` | Usuário desligou uma categoria de notificação nas preferências | Atualiza consentimento, para envios daquela categoria |
| **Eventos de ISO 17025 + operação robusta (novo v3)** | | |
| `CompetenciaVerificada` | OS atribuída a técnico passou pela verificação de habilitação | Permite avançar para execução |
| `CompetenciaBloqueou` | OS atribuída a técnico sem habilitação vigente | Bloqueia atribuição, sugere alternativas, alerta gerente + Responsável de Qualidade |
| `ExecutorAssinouCertificado` | Técnico executor concluiu cálculo + assinatura | Certificado avança para `aguardando_verificação`, notifica verificador via push |
| `VerificadorAssinouCertificado` | Verificador distinto concluiu dual sign-off | Certificado emitido, dispara numeração + PDF + entrega |
| `VerificadorDevolveuParaRetrabalho` | Verificador rejeitou certificado | Volta para rascunho, executor vê motivo |
| `DualSignOffBloqueado` | Tentativa de mesma pessoa assinar dois papéis | Bloqueia com mensagem clara, registra tentativa em audit log |
| `PadraoFalhouRecalibracao` | Recalibração externa retornou padrão fora da incerteza aceitável | Dispara Suspensão Retroativa (cálculo dos afetados), alerta Responsável de Qualidade |
| `SuspensaoRetroativaCriada` | Sistema pré-computou lista de certificados afetados | Abre tela de triagem para Responsável de Qualidade |
| `SuspensaoRetroativaDecidida` | Responsável decidiu (leve/grave) | Se grave, dispara cadência de notificação aos clientes + geração de OS de garantia |
| `ClienteNotificadoFalhaPadrao` | Cliente afetado recebeu notificação de recalibração gratuita | Registra log de notificação, inicia janela de aceitação |
| `OsGarantiaGerada` | OS de garantia criada a partir de Suspensão Retroativa ou reclamação de cliente | OS normal mas `valor_faturado = 0`, rastreabilidade preservada |
| `AgendamentoPreventivoGerado` | Scheduler criou OS interna de recalibração/manutenção | Agenda bloqueada, alerta motorista/Responsável |
| `AgendamentoPreventivoAtrasado` | Data prevista passou sem execução | Alerta crítico, possível bloqueio de operação |
| **Eventos de despacho (novo v3)** | | |
| `OsDespachadaAutomaticamente` | `REQ-OPL-005` atribuiu OS via round-robin | Notifica técnico escolhido |
| `TecnicoIndisponivelRegistrado` | Técnico reportou indisponibilidade (doença, UMC quebrou, competência venceu) | Dispara identificação de OS afetadas |
| `ReatribuicaoDisparada` | Sistema começou re-despacho automático | Roda `REQ-OPL-005` para cada OS afetada |
| `ReatribuicaoConcluida` | Todas OS afetadas foram reatribuídas | Notifica gerente com resumo diário |
| `ReatribuicaoPendenteManual` | Nenhum técnico disponível para uma ou mais OS | Alerta crítico ao gerente |
| `ClienteNotificadoMudancaTecnico` | Cliente afetado recebeu aviso de mudança | Registra log |
| **Eventos tributários RTC 2026 (novo v3)** | | |
| `ConfiguracaoTributariaRtcAtualizada` | Regra tributária RTC foi atualizada (nova versão publicada pela RFB) | Alerta responsável fiscal, dispara recálculo de pré-faturas em aberto |
| `NfseEmitidaComIbsCbs` | NFS-e emitida após 2026-01-01 com IBS/CBS corretos | Log operacional, contabilizada no relatório fiscal |
| `NfseRejeitadaPorRegraRtc` | Prefeitura rejeitou NFS-e por regra RTC | Entra no fluxo `REQ-FIS-007` com motivo marcado como "rtc_2026" |

---

## 6. Domínios Metrológicos cobertos no MVP

| Domínio | Exemplos de instrumento | Modo de calibração |
|---|---|---|
| Dimensional | Paquímetro, micrômetro, bloco padrão | Bancada |
| Pressão | Manômetro, transmissor de pressão | Bancada |
| Massa — bancada | Balança analítica, semi-analítica, comercial | Bancada |
| Massa — industrial média/pequena | Balança industrial de piso, balança de linha, dosadora | Campo (veículo operacional) |
| **Massa — industrial grande e rodoviária (novo)** | Balança rodoviária, balança de silo, balança industrial de grande porte | Campo (UMC) |
| Temperatura | Termômetro, termopar, PT100 | Bancada |

Domínios fora do MVP (elétrico, óptico, vazão, torque, vibração) aguardam primeiro cliente pagante do domínio.

---

## 7. Regras transversais do modelo

- **Isolamento de tenant:** todas as entidades têm `tenant_id` direto ou indireto; nenhuma query pode atravessar tenants.
- **Offline-first:** toda entidade pode ser criada/atualizada offline no dispositivo móvel, com `origem` = `campo-offline` e `hash_offline` para rastreabilidade. Sync silenciosa converge estado no servidor.
- **Imutabilidade metrológica:** calibração, certificado, padrão, orçamento de incerteza — append-only. Correção vira nova versão, não sobrescreve.
- **Foto obrigatória em despesa:** regra dura de domínio. Despesa sem foto de cupom não é aceita pelo app (validação local + server-side).
- **OS obrigatória em despesa:** despesa órfã não existe. Toda despesa precisa atrelamento a OS ativa.
- **Validade de padrão bloqueia calibração mesmo offline:** dispositivo conhece datas de validade de todos os padrões acessíveis ao usuário (via estoque local). Tentativa de usar padrão vencido é rejeitada pelo app antes mesmo de chegar ao servidor.
- **Sincronização append-only:** sync nunca sobrescreve silenciosamente — conflito é detectado e elevado ao usuário.
