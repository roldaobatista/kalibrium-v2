# Escopo do MVP — Kalibrium

> **Status:** ativo. Item 1.5.2 do plano da meta-auditoria #2 (Bloco 1.5). Destrava `scripts/decide-stack.sh` (sem este arquivo, `/decide-stack` falha). Depende da `docs/product/ideia-v1.md` para continuidade conceitual. Este arquivo responde à pergunta "o que entra no primeiro produto que vai pro cliente" — nada mais, nada menos.

## 1. Problema-alvo

Laboratórios brasileiros de calibração de pequeno e médio porte (ver `docs/product/laboratorio-tipo.md` para a definição formal do laboratório-modelo) operam hoje um fluxo fragmentado composto por planilha, software legado sem suporte, portal fiscal externo e decisão humana feita por experiência. Isso cria quatro problemas recorrentes que o MVP precisa atacar diretamente:

1. **Retrabalho administrativo** — o mesmo dado do cliente, do instrumento e do padrão é reinserido em três lugares distintos ao longo do ciclo de vida da calibração.
2. **Rastreabilidade metrológica frágil** — quando a RBC audita, encontrar a trilha completa (instrumento → procedimento → padrão → calibração do padrão → incerteza calculada) exige arqueologia entre pastas compartilhadas e memória humana.
3. **Tempo excessivo entre calibração concluída e cobrança recebida** — certificado, NF-e/NFS-e e lançamento no contas a receber são etapas desconectadas, com atraso médio de 7 a 14 dias corridos por pedido.
4. **Invisibilidade para o cliente final** — o cliente que manda o instrumento calibrar depende de e-mail e telefone para descobrir status do pedido e baixar certificado antigo.

O MVP do Kalibrium resolve os quatro, sem mais, em escopo intencionalmente estreito.

## 2. Laboratório-alvo do MVP

- **Porte:** pequeno a médio (até 2.000 calibrações/mês).
- **Domínios metrológicos cobertos no MVP:** dimensional (paquímetro, micrômetro, bloco padrão), pressão (manômetro, transmissor de pressão), massa (balança analítica, semi-analítica, comercial), temperatura (termômetro, termopar, PT100). Todos os quatro são obrigatórios no MVP — escolha dos quatro se deu pela cobertura do mix típico do laboratório modelo (ver `laboratorio-tipo.md`).
- **Geografia:** território nacional brasileiro, com atenção especial a São Paulo, Minas Gerais, Rio Grande do Sul e Paraná no primeiro lote de clientes.
- **Acreditação:** laboratórios acreditados pela Cgcre/Inmetro (selo RBC) são o público primário. Laboratórios não-acreditados mas em processo de acreditar são o público secundário.
- **Regime tributário:** Simples Nacional e Lucro Presumido no MVP. Lucro Real fica diferido para fase 2.

## 3. Módulos dentro do MVP (IN)

Cada módulo é um agrupamento funcional com um ou mais requisitos identificados no formato `REQ-DOM-NNN` onde `DOM` é um acrônimo de 3 letras.

### 3.1. Cadastro e tenant (DOM = `TEN`)
- `REQ-TEN-001` Cadastro inicial de um laboratório como tenant isolado.
- `REQ-TEN-002` Cadastro de usuários do laboratório com papel (gerente, técnico, administrativo, visualizador).
- `REQ-TEN-003` Cadastro de clientes do laboratório (empresas que mandam instrumento calibrar).
- `REQ-TEN-004` Cadastro de contatos dos clientes com e-mail e WhatsApp.
- `REQ-TEN-005` Isolamento forte de dados entre tenants — vazamento cruzado é incidente crítico.

### 3.2. Metrologia e calibração (DOM = `MET`)
- `REQ-MET-001` Cadastro de padrão de referência com número de série, certificado vigente, data de validade e rastreabilidade (padrão anterior na cadeia).
- `REQ-MET-002` Cadastro de instrumento do cliente (modelo, número de série, faixa, resolução).
- `REQ-MET-003` Criação de ordem de serviço (pedido) a partir de um cliente + um instrumento + um procedimento.
- `REQ-MET-004` Execução da calibração com registro dos pontos medidos, condições ambientais (temperatura, umidade) e identificação dos padrões usados.
- `REQ-MET-005` Cálculo de incerteza (conforme GUM/JCGM 100:2008) com orçamento de incerteza versionado, rastreável ao procedimento.
- `REQ-MET-006` Emissão de certificado de calibração em PDF no formato compatível com a RBC (cabeçalho, rastreabilidade, tabela de medidas, incerteza declarada, validade).
- `REQ-MET-007` Histórico do instrumento: todas as calibrações passadas do mesmo número de série.

### 3.3. Fluxo fim a fim (DOM = `FLX`)
- `REQ-FLX-001` Agendamento da coleta ou entrega do instrumento no laboratório.
- `REQ-FLX-002` Status do pedido: recebido → em calibração → aguardando aprovação → certificado emitido → enviado → pago.
- `REQ-FLX-003` Notificação ao cliente por e-mail a cada transição relevante.
- `REQ-FLX-004` Notificação opcional por WhatsApp (gate: só se o cliente consentir no cadastro).
- `REQ-FLX-005` Portal do cliente final para baixar certificado vigente e histórico.

### 3.4. Fiscal (DOM = `FIS`)
- `REQ-FIS-001` Emissão da NFS-e para o município do laboratório quando o certificado é aprovado.
- `REQ-FIS-002` Numeração fiscal controlada pelo sistema (sem duplicidade, sem pulo).
- `REQ-FIS-003` Envio do XML da nota por e-mail ao cliente final.
- `REQ-FIS-004` Baixa automática no contas a receber quando a nota é emitida.
- `REQ-FIS-005` Conciliação manual do contas a receber (o MVP não integra com banco).

### 3.5. Operação do laboratório (DOM = `OPL`)
- `REQ-OPL-001` Dashboard operacional com pedidos atrasados, pedidos na fila e pedidos esperando aprovação.
- `REQ-OPL-002` Fila de trabalho por técnico (quem faz o quê hoje).
- `REQ-OPL-003` Indicadores mínimos: tempo médio por calibração, taxa de retrabalho, aderência ao prazo.
- `REQ-OPL-004` Exportação das calibrações do mês em formato CSV para reuso contábil.

### 3.6. Compliance mínimo (DOM = `CMP`)
- `REQ-CMP-001` Registro imutável de todas as calibrações (append-only, sem delete).
- `REQ-CMP-002` Log de acesso aos dados de calibração (quem leu qual certificado, quando).
- `REQ-CMP-003` Política de retenção: calibrações são mantidas por 10 anos (prazo RBC).
- `REQ-CMP-004` Base legal LGPD explicitamente registrada para cada categoria de dado pessoal (detalhe em `docs/compliance/lgpd-base-legal.md`, item T2.2 da Trilha #2).

**Total de requisitos no MVP: 29.**

## 4. Módulos explicitamente FORA do MVP (OUT)

Estes módulos NÃO entram no primeiro produto e ficam documentados para evitar escopo-fantasma. Cada item tem gatilho de reentrada.

- **Pipeline comercial (CRM).** Cotação, proposta e funil de vendas ficam fora. Gatilho de reentrada: quando >30% dos clientes pedirem integração com CRM externo.
- **Integração bancária para conciliação automática.** Fica fora no MVP; conciliação é manual. Gatilho: quando >50% dos clientes pedirem.
- **App mobile nativo.** MVP é web responsivo. Gatilho: PWA insuficiente para uso de bancada (feedback real de técnico calibrador).
- **Calibração de domínios não listados no §2** (elétricos, óticos, vazão, torque, umidade, vibração). Gatilho: primeiro cliente pagante do domínio solicitar formalmente.
- **Lucro Real como regime tributário.** Complexidade fiscal adicional. Gatilho: primeiro lead qualificado em Lucro Real.
- **Assinatura digital ICP-Brasil no certificado.** Fica fora por restrição de custo e complexidade. Gatilho: exigência documentada de um cliente pagante ou da Cgcre. Detalhado em `docs/compliance/out-of-scope.md` (item 1.5.10).
- **Automação de REP-P (ponto eletrônico).** Totalmente fora de escopo. Gatilho: nunca — não é o produto.
- **Treinamento/certificação de técnico.** Fora. Gatilho: nunca — parceria, não produto próprio.
- **Gestão de equipamento (manutenção preventiva do próprio laboratório).** Fora no MVP. Gatilho: feedback de >5 clientes pagantes.
- **Integração com ERP do cliente** (SAP, TOTVS). Fora. Gatilho: solicitação formal de cliente pagante.

## 5. Jornadas críticas (alto nível)

O MVP precisa suportar estas jornadas fim a fim. O detalhamento campo-a-campo está em `docs/product/journeys.md`.

1. **Jornada 1 — Entrada de pedido novo.** Cliente liga/envia e-mail pedindo calibração. Atendente cadastra (ou reaproveita) cliente, cadastra instrumento, cria ordem de serviço. Sistema agenda.
2. **Jornada 2 — Execução técnica.** Técnico recebe fila, identifica instrumento, seleciona padrão, mede, preenche condições ambientais, submete para cálculo de incerteza. Orçamento é versionado.
3. **Jornada 3 — Aprovação e emissão.** Gerente revisa cálculo, aprova, sistema gera certificado em PDF e numera. Cliente recebe e-mail com link.
4. **Jornada 4 — Fiscal e cobrança.** NFS-e é emitida junto da aprovação. Baixa no contas a receber acontece no mesmo momento. Cliente recebe XML da nota.
5. **Jornada 5 — Cliente final consulta.** Cliente externo entra no portal, vê seu histórico, baixa certificado antigo, confere validade.

## 6. O que este arquivo NÃO decide

Este arquivo não escolhe:
- Linguagem de programação, framework, banco de dados, ORM, runtime, servidor de aplicação — tudo isso é responsabilidade do Bloco 2 (ADR-0001) e do `docs/architecture/foundation-constraints.md` (item 1.5.8).
- Modelo de multi-tenancy (RLS vs DB-per-tenant vs schema-per-tenant) — `foundation-constraints.md`.
- Modelo de cobrança e preço — `docs/product/pricing-assumptions.md` (item 1.5.15).
- RNFs numéricos de performance/capacidade — `docs/product/nfr.md` (item 1.5.5).

## 7. Critério de sucesso do MVP

O MVP está "no ar" quando um único laboratório real (primeiro cliente pagante) consegue, dentro do Kalibrium, registrar um pedido de calibração novo, executar a calibração até a emissão do certificado em PDF, emitir a NFS-e correspondente e receber o pagamento via contas a receber — tudo sem usar planilha, software legado ou portal externo. Essa jornada completa, medida em dias úteis do pedido à baixa, deve ser menor que a linha de base atual do mesmo laboratório. Se não for, o MVP falhou no critério e entra em ciclo de discovery, não em ciclo de crescimento.
