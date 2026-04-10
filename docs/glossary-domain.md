# Glossário de Domínio — Kalibrium

**Status:** referência canônica do V2.
**Propósito:** vocabulário técnico do domínio (metrologia, calibração, compliance trabalhista, fiscal, LGPD). Agentes **devem** consultar antes de escrever código que usa estes termos.

**Diferente de `docs/reference/`:** este arquivo **não** é histórico. É a fonte canônica. Alterações via commit normal + link para spec que motivou.

---

## Metrologia e calibração

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **Calibração** | Aferição, Ajuste (são coisas diferentes — ver abaixo) | Processo técnico de medição e verificação com rastreabilidade metrológica. **Não** inclui ajuste do instrumento — apenas mede o erro. |
| **Ajuste** | Calibração | Operação de modificar o instrumento para reduzir erro. Distinto de calibração. |
| **GUM** | — | *Guide to the Expression of Uncertainty in Measurement* (ISO/IEC Guide 98-3). Método canônico de cálculo de incerteza de medição. Referência obrigatória em laudos ISO 17025. |
| **Incerteza de Medição** | Erro | Parâmetro associado ao resultado da medição que caracteriza a dispersão dos valores que podem ser atribuídos ao mensurando. Calculada via GUM. |
| **Padrão (de referência)** | Standard, Referência | Instrumento pertencente ao tenant usado como base comparativa para calibrar instrumentos do cliente. Distinto de **Instrumento** do cliente. |
| **Rastreabilidade metrológica** | — | Propriedade de um resultado de medição estar relacionado a referências (padrões nacionais ou internacionais) através de uma cadeia documentada de calibrações. |
| **Certificado de Calibração** | Certificate, Relatório, Laudo Metrológico | Documento formal emitido após calibração contendo leituras brutas, cálculo de incerteza, condições ambientais, dual sign-off e cadeia de rastreabilidade. Obrigatório em ISO 17025. |
| **Cadeia de rastreabilidade** | — | Sequência de calibrações que conecta o instrumento do cliente a um padrão nacional/internacional (via padrões intermediários do tenant). |

### Órgãos e normas

| Termo | Definição |
|---|---|
| **ISO 17025** | Norma internacional para competência de laboratórios de ensaio e calibração. Requisitos técnicos e de gestão. Compliance obrigatório para acreditação. |
| **ISO 9001** | Norma internacional para sistemas de gestão da qualidade. |
| **ISO/IEC Guide 98-3 (GUM)** | Guia para expressão da incerteza de medição. |
| **INMETRO** | Instituto Nacional de Metrologia, Qualidade e Tecnologia. Órgão brasileiro de metrologia legal. |
| **PSEI** | Sistema de Informações para Entidades do Setor de Metrologia Legal. Sistema do INMETRO. |
| **ORC** | Organização de Reparo Credenciada pelo INMETRO. |
| **RBC** | Rede Brasileira de Calibração. |
| **CGCRE** | Coordenação-Geral de Acreditação do INMETRO. |

---

## Operações técnicas

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **Ordem de Serviço (OS)** | WorkOrder, Chamado, Ticket, Ordem | Entidade central da execução técnica. Criada a partir de um Chamado. Contém ciclo completo: diagnóstico, execução, evidências, fechamento, faturamento. |
| **Chamado** | Service Call, Ticket, Demanda | Demanda inicial do cliente. Pode originar uma ou mais OSs. |
| **Proposta (Quote)** | Cotação, Orçamento | Documento comercial passível de aprovação e conversão em OS ou Contrato. |
| **Contrato** | Contract, Acordo, SLA Comercial | Instrumento formal de prestação recorrente com condições, prazo, SLA e faturamento. |
| **Técnico** | Executor, Technician, Operador de Campo, Field Agent | Colaborador responsável pela execução de OS em campo ou laboratório. |
| **Instrumento** | Equipamento do Cliente, Asset do Cliente, Ativo Calibrável | Equipamento **do cliente** que é atendido, calibrado ou reparado. |
| **Ativo** | — | Bem pertencente ao **tenant** (veículo, ferramenta, padrão de referência, imóvel). Distinto de Instrumento do cliente. |
| **CAPA** | Ação Corretiva, NC | Ação Corretiva e Preventiva. Processo de gestão de não-conformidades exigido por ISO 17025 e ISO 9001. |
| **NC** | Não-conformidade, Desvio | Afastamento de um requisito. Dispara CAPA. |

---

## Compliance trabalhista (CLT, ponto, jornada)

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **REP-P** | — | Registrador Eletrônico de Ponto por Programa. Conformidade com Portaria MTE 671/2021. |
| **REP-C** | — | REP Convencional (hardware dedicado). |
| **REP-A** | — | REP Alternativo. |
| **AFD** | — | Arquivo-Fonte de Dados. Gerado pelo REP-P com hash chain SHA-256 (cada registro referencia o hash do anterior). Destinado à fiscalização trabalhista. |
| **ACJEF** | — | Arquivo de Controle de Jornada para fins da Justiça do Trabalho. Formato aceito por TST/TRT para juntada em processos. |
| **NSR** | — | Número Sequencial do Registro. Único e irreversível por marcação de ponto. |
| **Espelho de ponto** | — | Relatório mensal de marcações, ajustes e saldo. Gerado em PDF com hash de integridade e assinatura digital (empregado + empregador). |
| **eSocial** | — | Sistema governamental brasileiro de escrituração fiscal digital trabalhista. Eventos S-1200, S-2210, S-2220, S-2240 relevantes. |
| **CLT** | — | Consolidação das Leis do Trabalho. |
| **DSR** | — | Descanso Semanal Remunerado. |
| **HE** | — | Horas Extras. Percentuais 50% e 100% conforme CCT/lei. |

---

## Compliance fiscal

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **NF-e** | — | Nota Fiscal Eletrônica (produto). Emitida via SEFAZ estadual. |
| **NFS-e** | — | Nota Fiscal de Serviço Eletrônica. Emitida via prefeituras (não uniforme nacionalmente). |
| **SEFAZ** | — | Secretaria da Fazenda (estadual). Autoridade que autoriza NF-e. |
| **ICMS** | — | Imposto sobre Circulação de Mercadorias e Serviços. Estadual. Regras variam por UF — complexidade alta. |
| **CST/CSOSN** | — | Código de Situação Tributária (ICMS)/para Simples Nacional. |
| **IBS/CBS** | — | Impostos introduzidos pela Reforma Tributária (Lei Complementar 214/2025). |
| **DCTFWeb** | — | Declaração de Débitos e Créditos Tributários Federais Web. |
| **DANFE** | — | Documento Auxiliar da NF-e (PDF impresso). |

---

## Identidade digital e segurança

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **ICP-Brasil** | — | Infraestrutura de Chaves Públicas Brasileira. Autoridade certificadora para assinatura digital com validade jurídica. |
| **Certificado A1** | — | Certificado ICP-Brasil em arquivo (software). Validade 1 ano. |
| **Certificado A3** | — | Certificado ICP-Brasil em hardware (token/cartão). Validade até 5 anos. |
| **Carimbo de tempo** | Timestamp confiável | Assinatura de tempo confiável via autoridade certificadora (fora do relógio local). |
| **PDF/A** | — | Formato PDF para arquivamento de longo prazo (ISO 19005). Obrigatório em documentos assinados juridicamente relevantes. |
| **2FA / TOTP** | — | Autenticação de dois fatores via one-time password (RFC 6238). |
| **Passkey / WebAuthn / FIDO2** | — | Autenticação sem senha via chave assimétrica. |
| **SSO** | — | Single Sign-On via SAML 2.0 ou OIDC. |

---

## Privacidade (LGPD)

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **LGPD** | — | Lei Geral de Proteção de Dados Pessoais (Lei 13.709/2018). |
| **DPO** | Encarregado | Encarregado de Proteção de Dados (Art. 41 LGPD). |
| **Titular** | Dono do dado | Pessoa natural a quem se referem os dados pessoais. |
| **Controlador** | — | Entidade que toma decisões sobre o tratamento (tenant no nosso contexto). |
| **Operador** | — | Entidade que trata dados em nome do controlador (Kalibrium no nosso contexto). |
| **Dado sensível** | — | Categoria especial do Art. 5º LGPD (saúde, biometria, origem racial, etc.). |
| **Direito ao esquecimento** | — | Art. 18 LGPD. Titular pode solicitar exclusão. Prazo de 15 dias. Dados com obrigação legal são retidos pelo prazo mínimo legal. |
| **Portabilidade** | — | Art. 18 LGPD. Exportação em JSON ou CSV em até 15 dias. |
| **Consentimento explícito** | — | Para WhatsApp, SMS e e-mail marketing: registro de canal, data e forma de aceite antes do primeiro envio automático. |
| **Log imutável** | — | Registro de acesso a dado sensível. Não editável. Acesso restrito a DPO + admin do tenant. |

---

## Arquitetura e tecnologia (vocabulário do harness)

| Termo canônico | Alias a evitar | Definição |
|---|---|---|
| **Tenant** | Cliente, Organização, Empresa no sentido técnico | Empresa ou unidade lógica isolada na plataforma multi-tenant. Isolamento é garantia, não configuração. |
| **Bounded Context** | — | Limite lógico de um domínio dentro da arquitetura modular (DDD). Define escopo de modelos, linguagem e regras. |
| **SLA** | — | Service Level Agreement. Tempo de atendimento definido contratualmente. |
| **RFM** | — | Recência, Frequência, Valor Monetário. Modelo de segmentação de clientes. |
| **PWA** | — | Progressive Web App. Web app com capacidades offline e instalável. |
| **Data logger** | — | Sensor que registra condições ambientais (temperatura, umidade, vibração) ao longo do tempo. Usado em transporte de instrumentos sensíveis. |
| **NTP stratum 1** | — | Servidor de tempo confiável sincronizado diretamente com fonte primária (atômica, GPS). Obrigatório para marcação de ponto. |

---

## Regras de uso

1. **Nunca usar alias** onde existir termo canônico.
2. **Nomes de classe/tabela/endpoint devem seguir o termo canônico** (ou sua tradução razoável).
3. **Commits que introduzem alias** proibido = rejeitados em review.
4. **Adição de termo novo** ao glossário:
   - Commit direto (não precisa ADR).
   - Mensagem: `docs(glossary): adiciona <termo> (ref: slice-NNN)`.
   - Preferir extrair de norma ISO/ABNT/legislação quando aplicável.
5. **Alteração de termo canônico** (ex.: renomear "Work Order" → "Ordem de Serviço"):
   - Exige ADR se o termo já apareceu em código.
   - Grep full-repo antes e depois do rename.
