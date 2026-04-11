# Compliance fora do MVP — decisão formal

> **Status:** ativo. Item 1.5.10 do plano da meta-auditoria #2 (Bloco 1.5 Nível 2). Responde às três áreas de compliance que as três auditorias externas sinalizaram como ambíguas: **REP-P (ponto eletrônico)**, **ICP-Brasil (assinatura digital qualificada)** e **LGPD (proteção de dados)**. Cada uma recebe uma classificação binária (dentro do MVP / fora do MVP / diferido com data explícita), justificativa de 1 parágrafo e gatilho concreto de reentrada no escopo.

## Classificação resumida

| Tema | Classificação | Data de reavaliação |
|---|---|---|
| REP-P (ponto eletrônico — Portaria MTP 671/2021) | **Fora do MVP** | Não há data — só volta por demanda externa. |
| ICP-Brasil (assinatura digital A1/A3 no certificado) | **Diferido** | Reavaliação formal em 2026-12-31. |
| LGPD (base legal + DPIA + ROT) | **Dentro do MVP** (mínimo viável) | Contínuo — reavaliação trimestral a partir de 2026-07-01. |

## 1. REP-P — Ponto eletrônico trabalhista

**Classificação: FORA DO MVP.**

O REP-P é um sistema de registro eletrônico de ponto regulamentado pela Portaria MTP 671/2021, que substitui o antigo REP convencional para empresas que optarem pela modalidade "programa". Algumas auditorias perguntaram se o Kalibrium vai registrar ponto dos técnicos calibradores. A resposta é um **não** formal pelos motivos abaixo:

- **Não é o problema do produto.** O Kalibrium resolve o fluxo da calibração — do pedido ao certificado e à cobrança. Ponto eletrônico é relação trabalhista entre o laboratório e o técnico, ortogonal ao produto.
- **Risco regulatório desproporcional.** REP-P exige homologação específica, laudo técnico, conformidade com os requisitos do e-Social e auditoria trabalhista. Entrar nesse domínio exige um especialista em direito trabalhista e um homologador, nenhum dos dois faz parte do escopo da equipe (humano = PM único, não-desenvolvedor).
- **Mercado já atendido.** Laboratórios que precisam de REP-P usam soluções especializadas que já passaram por homologação (Ahgora, Dimep, Kairos, entre outras). Não há valor em o Kalibrium competir nesse vertical.

**Gatilho de reentrada:** nenhum. Se algum dia um cliente exigir, a resposta contratual é integração com sistema especializado, nunca desenvolvimento interno.

## 2. ICP-Brasil — Assinatura digital qualificada no certificado de calibração

**Classificação: DIFERIDO. Reavaliação formal em 2026-12-31.**

A assinatura ICP-Brasil (certificado A1 ou A3 emitido por Autoridade Certificadora vinculada à Infraestrutura de Chaves Públicas Brasileira) confere valor jurídico equivalente a assinatura de próprio punho. Alguns laboratórios acreditados gostam de assinar o PDF do certificado de calibração usando certificado ICP-Brasil do responsável técnico. **No MVP, o Kalibrium não assina PDF com ICP-Brasil.** A assinatura do responsável técnico é registrada visualmente (hash + nome + data), não criptograficamente via ICP-Brasil. Justificativa:

- **Custo por unidade.** A3 exige hardware (token/cartão) e exige presença física do titular para assinar cada PDF, o que quebra o fluxo automatizado. A1 é menos invasivo mas precisa de módulo de assinatura integrado + armazenamento seguro da chave + rotação, o que traz complexidade e custo de auditoria desproporcionais ao MVP.
- **Não é requisito da RBC.** A RBC aceita certificado assinado visualmente com identificação do responsável técnico. A assinatura ICP-Brasil é conforto extra, não obrigação.
- **Risco fiscal baixo.** A NFS-e emitida pela prefeitura já tem validade jurídica independente. O certificado de calibração é documento técnico, não documento fiscal.

**Gatilho de reentrada explícito:**
1. Exigência formal documentada de um cliente pagante ativo, OU
2. Mudança regulatória publicada pela Cgcre ou Inmetro exigindo assinatura ICP-Brasil.

Reavaliação formal agendada: **2026-12-31** (incluída no cronograma de `docs/compliance/law-watch.md`, item T2.12 da Trilha #2).

## 3. LGPD — Proteção de dados pessoais

**Classificação: DENTRO DO MVP (patamar mínimo viável).**

A LGPD não é opcional. Os laboratórios tratam dados pessoais de contato (comprador industrial, responsável pela qualidade, e-mail, telefone, CPF em alguns casos). O cliente final da cadeia pode ser pessoa física. O Kalibrium, como operador de dados, precisa satisfazer os requisitos mínimos antes do primeiro tenant real. O **mínimo viável LGPD para o MVP** é composto por cinco itens:

1. **Base legal registrada por categoria de dado** (detalhe em `docs/compliance/lgpd-base-legal.md`, item T2.2 da Trilha #2). Cada campo que guarda dado pessoal precisa estar explicitamente amarrado a uma das bases legais da Lei 13.709/2018 (consentimento, execução de contrato, obrigação legal, legítimo interesse, etc.).
2. **Registro de Operações de Tratamento (ROT)** — documento vivo listando que dados são tratados, por que, por quanto tempo, com quem são compartilhados. Item T2.4 da Trilha #2.
3. **Fluxo de exercício de direitos do titular** — canal para o titular pedir confirmação, acesso, correção, exclusão ou portabilidade. Item coberto pelas policies por domínio (T2.10 da Trilha #2).
4. **Contrato de operador** entre Kalibrium (operador) e laboratório (controlador). Template em T2.9 da Trilha #2.
5. **Relatório de Impacto à Proteção de Dados (DPIA)** inicial. Item T2.3 da Trilha #2, depende de T2.1 (threat-model) e T2.2 (base legal).

O DPO formal (encarregado de tratamento de dados) **é contratado em segundo momento**, conforme decisão #2 do PM no plano da meta-auditoria #2. Os itens T2.1-T2.5 ficam em `status: draft-awaiting-dpo` até a contratação — eles são produzidos nesta sessão como rascunho e ganham revisão final do DPO antes do primeiro tenant real.

**Gatilho de reentrada para ampliar o escopo LGPD:**
- Primeiro incidente de segurança com dado pessoal.
- Novo tipo de dado sensível passando a ser tratado (atualmente não há dado de saúde, biométrico, de criança etc.).
- Reavaliação trimestral a partir de 2026-07-01.

## 4. Observações gerais

- Este arquivo **não trata** de certificações ISO/IEC 17025 (responsabilidade direta do laboratório, não do Kalibrium) nem de credenciamento junto à Cgcre (mesmo motivo).
- Este arquivo **não trata** de controles de segurança técnica (criptografia, TLS, armazenamento de segredo). Esses ficam em `docs/compliance/threat-model.md` (T2.1) e `docs/compliance/secrets-policy.md` (T2.7).
- A decisão de diferir ICP-Brasil e recusar REP-P é uma decisão de produto explícita — se qualquer uma for alterada, exige ADR novo seguindo `docs/constitution.md §5`.
