# Escopo do MVP — Kalibrium

> 🔄 **STATUS 2026-04-16: AMPLIADO (v1 + v2 + v3).** A versão original (MVP focado em laboratório de bancada, 29 requisitos) está correta e preservada integralmente. **Ampliação v1** (mesma data): operação de campo + UMC + CRM offline + despesa por OS + estoque multinível + offline-first + segurança mobile + sync. **Ampliação v2** (mesma data): retransmissão de NFS-e, retenção fiscal, push, jornada LGPD, backup por tenant, SPC + drift, revalidação proativa. **Ampliação v3** (mesma data, pós re-auditoria independente): competência ISO 17025, dual sign-off, suspensão retroativa, despacho round-robin, re-despacho automático, OS de garantia, agendamento preventivo (padrões + veículos), Reforma Tributária 2026 (IBS/CBS), isolamento multi-tenant formalizado (ADR-0016). Backup do estado anterior em `mvp-scope-backup-2026-04-16.md`. Ver [`docs/incidents/discovery-gap-offline-2026-04-16.md`](../incidents/discovery-gap-offline-2026-04-16.md), [`docs/product/PRD-ampliacao-2026-04-16-v2.md`](PRD-ampliacao-2026-04-16-v2.md) e [`docs/product/PRD-ampliacao-2026-04-16-v3.md`](PRD-ampliacao-2026-04-16-v3.md).

> **Status:** ativo. Item 1.5.2 do plano da meta-auditoria #2 (Bloco 1.5). Destrava `scripts/decide-stack.sh` (sem este arquivo, `/decide-stack` falha). Depende da `docs/product/ideia-v1.md` para continuidade conceitual. Este arquivo responde à pergunta "o que entra no primeiro produto que vai pro cliente" — nada mais, nada menos.

> **Princípio operacional reafirmado:** PRD e escopo são **aditivos** — sempre melhoram, reorganizam, corrigem e ampliam. Nada do que já estava dentro é removido. Ver `feedback_prd_only_grows.md` na memória do agente.

## 1. Problema-alvo

Empresas brasileiras de serviço técnico em campo e laboratórios de calibração de pequeno e médio porte (ver `docs/product/laboratorio-tipo.md` para a definição formal do laboratório-modelo) operam hoje um fluxo fragmentado composto por planilha, software legado sem suporte, portal fiscal externo, caderno de campo em papel, WhatsApp com gerente, e decisão humana feita por experiência. A operação é **híbrida** (bancada no laboratório + campo no cliente, frequentemente numa única empresa que faz os dois modos) e a conectividade é **frequentemente intermitente** (até 4 dias offline em visitas a mina, usina, obra, zona rural). Isso cria problemas recorrentes que o MVP precisa atacar diretamente:

1. **Retrabalho administrativo** — o mesmo dado do cliente, do instrumento e do padrão é reinserido em três lugares distintos ao longo do ciclo de vida da calibração.
2. **Rastreabilidade metrológica frágil** — quando a RBC audita, encontrar a trilha completa (instrumento → procedimento → padrão → calibração do padrão → incerteza calculada) exige arqueologia entre pastas compartilhadas e memória humana.
3. **Tempo excessivo entre calibração concluída e cobrança recebida** — certificado, NF-e/NFS-e e lançamento no contas a receber são etapas desconectadas, com atraso médio de 7 a 14 dias corridos por pedido.
4. **Invisibilidade para o cliente final** — o cliente que manda o instrumento calibrar depende de e-mail e telefone para descobrir status do pedido e baixar certificado antigo.
5. **Operação de campo invisível ao sistema** — técnico em campo leva caderno de papel, tira foto no WhatsApp, perde cupom de combustível, digita tudo 2-3 horas no escritório depois que volta. Gestor em escritório não tem visibilidade em tempo real da UMC, das despesas em andamento, do que o técnico está fazendo no cliente.
6. **Sem rastreabilidade de custo real por OS** — a empresa não sabe o custo efetivo de cada ordem de serviço (combustível da UMC + almoço da equipe + pedágio + hora-homem + depreciação da massa-padrão). Decisão de preço é feita no instinto.
7. **CRM do vendedor externo é ausência pura** — vendedor em campo trabalha com WhatsApp + planilha + memória. Cliente novo liga, fica sem retorno. Orçamento demora 2 dias quando o concorrente fecha na hora.
8. **Conectividade intermitente destrói produtividade** — software online-only não funciona em cliente sem 4G, em mina, em galpão de estrutura metálica, em zona rural. Técnico perde o dia ou opera fora do sistema.

O MVP do Kalibrium resolve os oito, sem mais, em escopo intencionalmente estreito mas suficientemente largo para cobrir a operação híbrida bancada+campo.

## 2. Cliente-alvo do MVP

- **Tipo de negócio:** empresa de serviço técnico em campo (com caminhão de calibração e frota operacional) e/ou laboratório de calibração de bancada — muitos operam os dois modelos simultaneamente. O MVP atende os dois.
- **Porte:** pequeno a médio (até 2.000 calibrações/mês, até 10 técnicos, até 1 UMC + até 5 veículos operacionais).
- **Domínios metrológicos cobertos no MVP:**
  - **Dimensional** (paquímetro, micrômetro, bloco padrão) — bancada.
  - **Pressão** (manômetro, transmissor de pressão) — bancada.
  - **Massa — bancada** (balança analítica, semi-analítica, comercial).
  - **Massa — industrial média/pequena** (balança industrial de piso, balança de linha, dosadora) — campo com veículo operacional.
  - **Massa — industrial grande e rodoviária** (balança rodoviária, balança de silo, balança industrial de grande porte) — campo com UMC.
  - **Temperatura** (termômetro, termopar, PT100) — bancada.
  
  Todos os seis são obrigatórios no MVP. Escolha dos quatro originais se deu pela cobertura do mix típico do laboratório modelo (ver `laboratorio-tipo.md`). Os dois adicionais (massa industrial média e massa industrial grande/rodoviária) foram incluídos em 2026-04-16 porque representam o core da operação de campo do cliente-alvo.
- **Geografia:** território nacional brasileiro, com atenção especial a São Paulo, Minas Gerais, Rio Grande do Sul, Paraná, Goiás e Mato Grosso no primeiro lote de clientes (os três últimos refletem operação de balança rodoviária comum nessas regiões).
- **Acreditação:** laboratórios acreditados pela Cgcre/Inmetro (selo RBC) são o público primário. Laboratórios não-acreditados mas em processo de acreditar são o público secundário.
- **Regime tributário:** Simples Nacional e Lucro Presumido no MVP. Lucro Real fica diferido para fase 2.

## 2-bis. Modos de operação do MVP

O MVP suporta **dois modos de serviço** e qualquer empresa-cliente pode operar nos dois simultaneamente:

### 2-bis.1. Modo Bancada
- Instrumento chega ao laboratório (pelo correio, entregue pelo cliente, coletado pela empresa).
- Calibração ocorre em bancada no laboratório, por técnico (Juliana típica).
- Certificado é emitido e o instrumento é devolvido ao cliente.
- Jornadas ancora: 1, 2, 3 em `journeys.md`.

### 2-bis.2. Modo Campo — veículo operacional (sem UMC)
- Técnico vai ao cliente com caminhonete/carro dele (estoque pessoal a bordo com padrões leves).
- Instrumento é calibrado no local (balança industrial média/pequena, manutenção, aferição simples).
- 100% offline-capable. Certificado pode ser emitido em campo (PDF gerado localmente) e transmitido quando sincronizar.
- Jornada ancora: 6 em `journeys.md`.

### 2-bis.3. Modo Campo — UMC (Unidade Móvel de Calibração)
- Caminhão especializado com guindaste + massas-padrão pesadas (500 kg, 1000 kg, etc) + motorista/operador.
- Usado para balança rodoviária, balança industrial grande, silo — tudo que não pode sair do local e exige massa-padrão pesada.
- OS envolve equipe (técnico + motorista + opcionalmente auxiliar). Colaboração multi-pessoa offline.
- Jornada ancora: 7 em `journeys.md`.

## 2-ter. Perfil de conectividade e offline-first (novo — 2026-04-16)

**Requisito sistêmico:** o MVP opera igual com ou sem conexão de internet. Todos os papéis que trabalham em campo (técnico, motorista UMC, vendedor externo, gestor em campo) têm um app que funciona 100% offline, aguenta até **4 dias de trabalho acumulado sem sincronizar** e sincroniza silenciosamente em background quando pegar sinal. Papéis de escritório (atendente, financeiro) operam online normalmente.

### Janelas offline esperadas (dados levantados em discovery 2026-04-16)

- Vendedor externo: minutos a horas (cidade com sinal ruim, galpão cliente, estrada).
- Técnico de campo: horas a 4 dias (mina, usina, obra rural, zona sem 4G).
- Motorista UMC: horas a 4 dias (mesma dinâmica do técnico).
- Gestor em campo: sempre online (escolhe lugares com sinal ou usa plano corporativo).
- Todos os papéis de escritório: sempre online.

### Volume offline dimensionado

- Vendedor: ~500 clientes no celular (ficha + histórico + contatos + último certificado).
- Técnico: ~2 OS por dia × 4 dias = 8 OS pendentes de sync no pior caso. Cada OS tem: ficha cliente, instrumentos, pontos medidos, ambiente, fotos (5-20 por OS), assinaturas, despesas (com fotos de cupons).
- Motorista UMC: registros de diário de bordo e despesas do dia × 4 dias.

### Dispositivos suportados

- **Smartphone Android** (primário em campo).
- **Smartphone iPhone** (secundário — executivo, gestor, sócio-gerente).
- **Tablet** (técnico de bancada — tela maior para formulários).
- **Notebook** (vendedor, gestor, escritório).
- **Desktop** (escritório, financeiro, administrativo).

O mesmo app/sistema serve todos — o que varia é o RBAC (quem vê o quê) e a adaptação de layout. Stack decidida em `ADR-0015` (PWA + Capacitor híbrido).

## 3. Módulos dentro do MVP (IN)

Cada módulo é um agrupamento funcional com um ou mais requisitos identificados no formato `REQ-DOM-NNN` onde `DOM` é um acrônimo de 3 letras.

### 3.1. Cadastro e tenant (DOM = `TEN`)
- `REQ-TEN-001` Cadastro inicial de um laboratório/empresa como tenant isolado.
- `REQ-TEN-002` Cadastro de usuários com papel (gerente, técnico, motorista-UMC, vendedor, gestor-campo, administrativo, visualizador).
- `REQ-TEN-003` Cadastro de clientes do laboratório/empresa (empresas que contratam serviço).
- `REQ-TEN-004` Cadastro de contatos dos clientes com e-mail e WhatsApp.
- `REQ-TEN-005` Isolamento forte de dados entre tenants — vazamento cruzado é incidente crítico.
- `REQ-TEN-006` Atribuição de usuário a carteira (vendedor → clientes específicos) e a equipe padrão (técnico ↔ veículo operacional assinado).
- `REQ-TEN-007` **(v3)** Isolamento técnico multi-tenant formalizado conforme ADR-0016: (i) coluna `tenant_id` em toda tabela de domínio, (ii) Eloquent Global Scope aplicado automaticamente, (iii) PostgreSQL Row-Level Security nas 10 tabelas mais críticas (Calibração, Certificado, Cliente, Instrumento, Padrão, OS, NFS-e, Requisição LGPD, Dispositivo, Evidência) como defesa em profundidade, (iv) suíte de teste de isolamento obrigatória em CI (negative tests por tenant).

### 3.2. Metrologia e calibração (DOM = `MET`)
- `REQ-MET-001` Cadastro de padrão de referência com número de série, certificado vigente, data de validade e rastreabilidade (padrão anterior na cadeia).
- `REQ-MET-002` Cadastro de instrumento do cliente (modelo, número de série, faixa, resolução).
- `REQ-MET-003` Criação de ordem de serviço (pedido) a partir de um cliente + um ou mais instrumentos + um procedimento + um modo de execução (bancada, campo-veiculo, campo-umc).
- `REQ-MET-004` Execução da calibração (bancada ou campo) com registro dos pontos medidos, condições ambientais (temperatura, umidade) e identificação dos padrões usados.
- `REQ-MET-005` Cálculo de incerteza (conforme GUM/JCGM 100:2008) com orçamento de incerteza versionado, rastreável ao procedimento. Cálculo funciona offline (rodando localmente no dispositivo).
- `REQ-MET-006` Emissão de certificado de calibração em PDF no formato compatível com a RBC. Funciona offline (PDF gerado localmente, transmitido na sync).
- `REQ-MET-007` Histórico do instrumento: todas as calibrações passadas do mesmo número de série, disponível offline para os últimos 12 meses.
- `REQ-MET-008` Validade de padrão bloqueia lançamento mesmo offline — app conhece a data de validade de cada padrão a bordo (veículo, UMC, estoque pessoal).
- `REQ-MET-009` **(v2)** Gráfico de controle (SPC) dos padrões de referência — cada padrão tem histórico das calibrações do próprio padrão plotado com limites de controle (UCL/LCL). Atualização automática a cada nova calibração do padrão. Visualização disponível para o Responsável de Qualidade (Persona 8) e gerente. Exigido por ISO 17025.
- `REQ-MET-010` **(v2)** Drift automático — o sistema compara a tendência do padrão com limites de aceitabilidade e dispara alerta quando o valor medido se aproxima do limite mesmo antes do vencimento do certificado. Alerta vai para o Responsável de Qualidade; se drift cruzar limite crítico, o padrão é bloqueado automaticamente para uso em novas calibrações (mesmo comportamento de `REQ-MET-008`).
- `REQ-MET-011` **(v3)** Bloqueio de técnico sem competência vigente — ISO 17025 §6.2 exige que pessoal só execute atividade para a qual está qualificado. O sistema conhece a habilitação técnica por domínio metrológico de cada técnico (entidade `Habilitação técnica`, com validade). No momento de iniciar calibração, se técnico não tem habilitação vigente para o domínio do instrumento, o app bloqueia antes mesmo de registrar leitura (inclusive offline — a data de validade vai embarcada). Gerente (Persona 1) e Responsável de Qualidade (Persona 8) recebem alerta.
- `REQ-MET-012` **(v3)** Dual sign-off de certificado de calibração — certificado não é emitido enquanto não for assinado digitalmente por duas pessoas distintas: o técnico executor (quem fez a calibração) e um verificador técnico (outro técnico qualificado ou o Responsável de Qualidade). Estados do certificado: `rascunho → aguardando_verificação → verificado → emitido`. Se executor e verificador forem a mesma pessoa, bloqueia. Em laboratório muito pequeno, o Responsável de Qualidade (Persona 8 Aline) acumula verificação. Conformidade RBC.
- `REQ-MET-013` **(v3)** Suspensão retroativa de certificados quando padrão falha — quando um padrão de referência falha na recalibração externa (valor retorna fora do limite aceitável da cadeia de rastreabilidade) ou dispara `DriftDetectado` crítico (`REQ-MET-010`), o sistema identifica automaticamente todos os certificados emitidos usando aquele padrão desde a última calibração válida. Cria **Suspensão retroativa** com lista de certificados afetados. Responsável de Qualidade revisa e decide: (a) suspender e emitir novos certificados corrigidos, (b) notificar clientes afetados para recalibração gratuita, (c) registrar não conformidade formal. Cadência de notificação automática aos clientes com template editável.
- `REQ-MET-014` **(v3)** Agendamento automático de recalibração de padrões — scheduler interno gera OS interna de recalibração de padrão 60 dias antes do vencimento (para permitir lead time do laboratório externo). Bloqueia agenda do padrão no período. Se padrão não retornar antes do vencimento, `REQ-MET-008` entra em efeito (bloqueio automático). Rastreamento do "padrão em trânsito" no estoque (novo estado `aguardando_retorno`).

### 3.3. Fluxo fim a fim (DOM = `FLX`)
- `REQ-FLX-001` Agendamento da coleta ou entrega do instrumento (modo bancada) ou agendamento da visita (modo campo).
- `REQ-FLX-002` Status do pedido: recebido → em calibração → aguardando aprovação → certificado emitido → enviado → pago. Para modo campo, adiciona: deslocamento iniciado → em visita → visita concluída.
- `REQ-FLX-003` Notificação ao cliente por e-mail a cada transição relevante.
- `REQ-FLX-004` Notificação opcional por WhatsApp (gate: só se o cliente consentir no cadastro).
- `REQ-FLX-005` Portal do cliente final para baixar certificado vigente e histórico.
- `REQ-FLX-006` Assinatura do representante do cliente no touch do celular ao final da visita de campo (prova de execução).
- `REQ-FLX-007` **(v2)** Push notification nativo — técnico, vendedor, motorista UMC e gestor em campo recebem push no app (iOS + Android) em eventos relevantes: nova OS atribuída, mudança de status crítica, prazo de SLA prestes a estourar, despesa aprovada/rejeitada, conflito de sync detectado. Funciona mesmo com app fechado. Consentimento obrigatório no primeiro login; o usuário pode desligar por categoria.

### 3.4. Fiscal (DOM = `FIS`)
- `REQ-FIS-001` Emissão da NFS-e para o município do laboratório/empresa quando o certificado é aprovado. Emitida pelo escritório (atendente) ou pelo gestor em campo com celular online. Técnico em campo **não** emite.
- `REQ-FIS-002` Numeração fiscal controlada pelo sistema (sem duplicidade, sem pulo).
- `REQ-FIS-003` Envio do XML da nota por e-mail ao cliente final.
- `REQ-FIS-004` Baixa automática no contas a receber quando a nota é emitida.
- `REQ-FIS-005` Conciliação manual do contas a receber (o MVP não integra com banco).
- `REQ-FIS-006` NFS-e "preparada offline, aguardando transmissão" — quando gestor em campo fica offline momentaneamente, a nota é preparada localmente com preview completo e transmitida à prefeitura quando pegar sinal.
- `REQ-FIS-007` **(v2)** Retransmissão de NFS-e rejeitada — quando a prefeitura rejeita a nota (erro de dado do cliente, de cálculo, de layout, de numeração), o sistema captura o motivo da rejeição, apresenta em linguagem clara ao operador (atendente ou gestor em campo), permite correção no ponto exato e retransmite sem perder o lote ou a trilha de auditoria. Estados formais da NFS-e: preparada → transmitida → aprovada | rejeitada → (se rejeitada) em correção → retransmitida → aprovada.
- `REQ-FIS-008` **(v2)** Retenção fiscal correta por regime — cálculo automático de ISS, IR, INSS, PIS, COFINS conforme regime tributário do laboratório (Simples Nacional, Lucro Presumido) e regras do município de prestação. Arredondamento half-even (ABNT) com precisão de centavos. Conformidade com NT da CGNFS-e vigente na data da emissão. Reduz rejeição por cálculo incorreto — principal causa de NFS-e rejeitada.
- `REQ-FIS-009` **(v3)** Reforma Tributária 2026 — suporte a IBS (Imposto sobre Bens e Serviços), CBS (Contribuição sobre Bens e Serviços) e cIndOp (Indicador de Operação) conforme cronograma oficial da RFB para NFS-e a partir de 2026-01-01. Produção confirmada para 2026; data-alvo: operacional antes da entrada em vigor conforme cronograma da RFB. Entregas: (a) cálculo correto dos novos tributos por regime (LC 214/2025), (b) preenchimento dos grupos do layout da NFS-e nacional padronizada, (c) versionamento das regras por município (onde houver regra local complementar), (d) matriz de compatibilidade com regimes Simples/Lucro Presumido preservando `REQ-FIS-008`, (e) ambiente de homologação contra sandbox da RFB antes de produção, (f) fallback seguro para regime atual se operação ocorrer antes da data oficial. Monitoramento de atualizações oficiais com alerta para responsável fiscal do tenant.

### 3.5. Operação do laboratório e campo (DOM = `OPL`)
- `REQ-OPL-001` Dashboard operacional com pedidos atrasados, pedidos na fila, pedidos esperando aprovação, OS em campo em tempo real (localização + status).
- `REQ-OPL-002` Fila de trabalho por técnico (quem faz o quê hoje) + agenda da UMC + agenda dos veículos operacionais.
- `REQ-OPL-003` Indicadores mínimos: tempo médio por calibração (por modo), taxa de retrabalho, aderência ao prazo, custo real por OS, ocupação da UMC.
- `REQ-OPL-004` Exportação das calibrações do mês em formato CSV para reuso contábil.
- `REQ-OPL-005` **(v3)** Despacho automático de OS — estratégia MVP: **round-robin** entre técnicos disponíveis do domínio metrológico necessário, considerando carga atual (OS em aberto por técnico) e escala do dia. Gerente pode override manual sempre. Estratégias avançadas (skill-match fino, roteamento geográfico com ETA) ficam PÓS-MVP.
- `REQ-OPL-006` **(v3)** Re-despacho automático quando técnico fica indisponível — se o técnico atribuído fica indisponível (registra doença no app, UMC quebrou na estrada, bloqueio por competência vencida, ou status muda para "impedido"), sistema identifica as OS afetadas das próximas 48h, reatribui automaticamente por `REQ-OPL-005`, notifica: (a) técnico novo via push (`REQ-FLX-007`), (b) cliente afetado sobre mudança de técnico (e-mail + WhatsApp com consentimento), (c) gerente (Persona 1) e gestor em campo (Persona 6). Se nenhum técnico disponível, a OS vai para fila de "pendente reatribuição" com alerta crítico.
- `REQ-OPL-007` **(v3)** OS de garantia — classificação explícita da OS (tipo `garantia` vs `comercial` vs `interna_recalibração`). OS de garantia tem custo zero para o cliente (NFS-e com valor R$ 0,00 ou isenção conforme regra do município), entra no pipeline fiscal normalmente (rastreabilidade preservada) mas não gera cobrança. Cost allocation (atribuir custo real de uma OS de garantia ao fornecedor original do serviço, se aplicável) fica PÓS-MVP.

### 3.6. Compliance mínimo (DOM = `CMP`)
- `REQ-CMP-001` Registro imutável de todas as calibrações (append-only, sem delete).
- `REQ-CMP-002` Log de acesso aos dados de calibração (quem leu qual certificado, quando).
- `REQ-CMP-003` Política de retenção: calibrações são mantidas por 10 anos (prazo RBC).
- `REQ-CMP-004` Base legal LGPD explicitamente registrada para cada categoria de dado pessoal (detalhe em `docs/compliance/lgpd-base-legal.md`, item T2.2 da Trilha #2).
- `REQ-CMP-005` Log de sincronização — cada sync registra o que foi enviado/recebido, timestamp e identidade do dispositivo.
- `REQ-CMP-006` **(v2)** Jornada LGPD do titular — canal formal (portal e/ou e-mail dedicado) para o titular (cliente final, ex-cliente, contato de cliente) solicitar: acesso aos seus dados, retificação, exclusão, portabilidade ou revogação de consentimento. O DPO do laboratório (papel do Responsável de Qualidade, Persona 8, ou do Sócio-gerente, Persona 1) recebe a requisição, tria, atende dentro do prazo legal (15 dias corridos conforme LGPD) e o sistema registra log imutável da resposta. Caso crítico (notificação de breach) tem alerta em 72h conforme ANPD.
- `REQ-CMP-007` **(v2)** Backup por tenant com verificação de integridade — job agendado diário executa backup do banco e storage do tenant, valida integridade (restore em ambiente isolado + checksum), registra resultado em log auditável. Em caso de falha, alerta o time de operação. Retenção do backup: 30 dias deslizantes + snapshot mensal retido por 12 meses.

### 3.7. Operação de campo (DOM = `FLD`) — novo
- `REQ-FLD-001` Registro de deslocamento (KM inicial, KM final, horário, GPS opcional) atrelado à OS.
- `REQ-FLD-002` Check-in / check-out no cliente (chegada e saída registradas).
- `REQ-FLD-003` Captura de foto (mínimo: selo do instrumento calibrado; opcional: fachada, crachá, equipamento geral, vídeo curto).
- `REQ-FLD-004` Assinatura do representante do cliente no touch (nome + RG/cargo + timestamp).
- `REQ-FLD-005` Operação 100% offline com sincronização silenciosa quando pegar sinal.
- `REQ-FLD-006` Visão mapa das OS do dia para o técnico (rota sugerida).

### 3.8. UMC e veículos (DOM = `UMC` + `VHL`) — novo
- `REQ-UMC-001` Cadastro da UMC (placa, chassi, ano, capacidade do guindaste, motorista principal).
- `REQ-UMC-002` Cadastro das massas-padrão a bordo da UMC (cada uma com número de série, certificado vigente, validade).
- `REQ-UMC-003` Agenda da UMC — OS bloqueia a agenda. Manutenção preventiva bloqueia automaticamente por KM ou por tempo.
- `REQ-UMC-004` Diário de bordo do motorista — KM, horário, abastecimento (com foto), pedágio (com foto), manutenção incidental.
- `REQ-VHL-001` Cadastro de veículo operacional (caminhonete, carro) com `modo_uso` = `assinado` (fixo a um técnico) OU `compartilhado` (pool).
- `REQ-VHL-002` Reserva de veículo do pool (quando `modo_uso = compartilhado`).
- `REQ-VHL-003` Diário de bordo do veículo operacional (KM, combustível, manutenção).
- `REQ-UMC-005` **(v3)** Agendamento automático de manutenção preventiva — scheduler gera OS interna de manutenção para cada UMC e cada veículo operacional baseado em regras configuráveis: (a) por tempo (ex: a cada 6 meses), (b) por KM (ex: a cada 10.000 km rodados — soma do diário de bordo `REQ-UMC-004` / `REQ-VHL-003`), (c) por evento (após viagem >500km, disparar checagem extra). A OS preventiva **bloqueia a agenda** do veículo no período previsto. Alerta vai para gerente (Persona 1) e motorista principal. Mesmo enforcement aplicado à recalibração de massas-padrão a bordo da UMC (via `REQ-MET-014`).

### 3.9. Caixa de despesa por OS (DOM = `DSP`) — novo
- `REQ-DSP-001` Registro de despesa com foto obrigatória do cupom/nota + valor + tipo (combustível, pedágio, almoço, estacionamento, hotel, outros) + OS atrelada obrigatória.
- `REQ-DSP-002` Três origens de dinheiro: cartão corporativo, adiantamento em dinheiro, próprio bolso (reembolso).
- `REQ-DSP-003` Saldo otimista em tempo real (antes da triagem).
- `REQ-DSP-004` Triagem pelo escritório (atendente revisa cada despesa, aprova/rejeita/reclassifica).
- `REQ-DSP-005` Aprovação final em alçada (escritório até X, gerente acima de X).
- `REQ-DSP-006` Reembolso por PIX/transferência em lote (semanal/mensal).
- `REQ-DSP-007` Conciliação com fatura do cartão corporativo (importação CSV + matching linha-a-linha).
- `REQ-DSP-008` Relatório de custo real por OS (soma das despesas aprovadas).

### 3.10. Estoque multinível (DOM = `INV`) — novo
- `REQ-INV-001` Quatro locais de estoque: laboratório (central), UMC (a bordo), veículo operacional (a bordo), carro pessoal do técnico (mini-estoque portátil).
- `REQ-INV-002` Movimentação entre locais com responsável registrado (ex: "Carlos tirou padrão X do laboratório e colocou no carro dele em 10/04 às 08:30").
- `REQ-INV-003` Consulta offline do estoque local do dispositivo (técnico em campo vê o que tem no próprio carro sem precisar de sinal).
- `REQ-INV-004` Alerta de padrão vencendo (30 dias antes) em cada local de estoque.

### 3.11. CRM do vendedor (DOM = `CRM`) — novo
- `REQ-CRM-001` Carteira de clientes por vendedor (vendedor vê só a dele; gerente vê tudo).
- `REQ-CRM-002` Ficha completa do cliente disponível offline no dispositivo do vendedor (até 500 clientes por vendedor).
- `REQ-CRM-003` Registro de visita (nota de voz → transcrição, foto da fachada/crachá, timestamp, GPS opcional).
- `REQ-CRM-004` Criação de orçamento em campo (offline) com geração de PDF local e envio imediato via WhatsApp/link.
- `REQ-CRM-005` Conversão de orçamento aceito em OS (sync quando conectar).
- `REQ-CRM-006` Follow-up automático (app lembra o vendedor do compromisso).
- `REQ-CRM-007` Visão de pipeline pro gerente (em tempo real conforme vendedor sincroniza).
- `REQ-CRM-008` **(v2)** Revalidação proativa de instrumento — o sistema conhece a data de validade de cada certificado emitido (`REQ-MET-007`). 90 dias antes do vencimento, dispara cadência automática para o contato do cliente (e-mail + WhatsApp conforme consentimento, `REQ-FLX-003/004`) oferecendo agendamento da próxima calibração. Se o cliente aceitar via link, a OS é criada automaticamente e o vendedor responsável pela carteira é notificado (via push, `REQ-FLX-007`). Engajamento pipeline: enviado → visto → clicado → agendado → convertido em OS. Relatório mensal para o gerente (Persona 1) com taxa de conversão por vendedor.

### 3.12. Segurança em dispositivo móvel (DOM = `SEC`) — novo
- `REQ-SEC-001` Biometria obrigatória para abrir o app em smartphone/tablet (digital ou face). PIN como fallback.
- `REQ-SEC-002` Criptografia local dos dados offline (AES-256, chave derivada do login).
- `REQ-SEC-003` Wipe remoto autorizado pela empresa — escritório aciona, próximo sync ou próxima abertura do app dispara limpeza total dos dados locais.
- `REQ-SEC-004` Device binding — dispositivo é registrado na primeira login. Login subsequente em dispositivo novo exige reaprovação da empresa.
- `REQ-SEC-005` Sessão longa (token válido por tempo suficiente para cobrir 4 dias offline) com refresh transparente quando conectar.

### 3.13. Sincronização e resolução de conflito (DOM = `SYN`) — novo
- `REQ-SYN-001` Sync silencioso em background quando o dispositivo pega sinal.
- `REQ-SYN-002` Merge por campo — cada campo mantém o último editor (last-write-wins por campo).
- `REQ-SYN-003` Detecção de conflito real — quando dois usuários editaram o mesmo campo offline, o sistema sinaliza "conflito detectado" e apresenta os dois valores + timestamps + autores para resolução manual pelo responsável da OS.
- `REQ-SYN-004` Tempo real online — quando todos os membros de uma OS estão conectados, as edições aparecem em tempo real nos outros dispositivos.
- `REQ-SYN-005` Audit log de sync — cada sync registra o delta enviado/recebido, timestamp, dispositivo, usuário.
- `REQ-SYN-006` Modo avião forçado (botão de teste) — técnico pode simular offline antes de viajar pra testar o app.

**Total de requisitos no MVP: 80** (29 originais + 33 v1 de operação de campo/UMC/despesa/estoque/CRM/segurança/sync + 8 v2 de fiscal/LGPD/push/backup/SPC/drift/revalidação + **10 v3** de competência ISO 17025 / dual sign-off / suspensão retroativa / despacho round-robin / re-despacho / garantia / agendamento preventivo / RTC 2026 / isolamento multi-tenant formalizado).

## 4. Módulos explicitamente FORA do MVP (OUT)

Estes módulos NÃO entram no primeiro produto e ficam documentados para evitar escopo-fantasma. Cada item tem gatilho de reentrada.

- **Pipeline comercial completo (CRM avançado).** Cotação com múltiplos anexos, proposta com negociação de cláusulas, funil de vendas com estágios complexos ficam fora. O que entra: carteira por vendedor, ficha do cliente, orçamento offline com envio direto (módulo `CRM` em §3.11). Gatilho para ampliar: quando >30% dos clientes pedirem funil complexo ou integração com CRM externo.
- **Integração bancária para conciliação automática.** Fica fora no MVP; conciliação é manual. Gatilho: quando >50% dos clientes pedirem.
- ~~**App mobile nativo.** MVP é web responsivo.~~ **STATUS 2026-04-16: MOVIDO PARA DENTRO DO MVP (§2-ter)** — após discovery, foi identificado que app mobile é requisito crítico para operação de campo (offline, biometria, foto, GPS, push). Solução: PWA + Capacitor híbrido cobre iOS e Android com um único código, sem exigir app nativo separado por plataforma. Ver `ADR-0015`.
- **Calibração de domínios não listados no §2** (elétricos, óticos, vazão, torque, umidade, vibração). Gatilho: primeiro cliente pagante do domínio solicitar formalmente.
- **Lucro Real como regime tributário.** Complexidade fiscal adicional. Gatilho: primeiro lead qualificado em Lucro Real.
- **Assinatura digital ICP-Brasil no certificado.** Fica fora por restrição de custo e complexidade. Gatilho: exigência documentada de um cliente pagante ou da Cgcre. Detalhado em `docs/compliance/out-of-scope.md` (item 1.5.10).
- **Automação de REP-P (ponto eletrônico).** Totalmente fora de escopo. Gatilho: nunca — não é o produto.
- **Treinamento/certificação de técnico.** Fora. Gatilho: nunca — parceria, não produto próprio.
- **Gestão de equipamento (manutenção preventiva do próprio laboratório).** Fora no MVP. Gatilho: feedback de >5 clientes pagantes.
- **Integração com ERP do cliente** (SAP, TOTVS). Fora. Gatilho: solicitação formal de cliente pagante.
- **Sincronização peer-to-peer via bluetooth/wi-fi local** entre dispositivos da mesma equipe quando todos offline. Solução atual: cada dispositivo sincroniza quando pegar sinal de internet. Gatilho: feedback consistente de que "a equipe trabalha num lugar sem internet nenhuma mas com sinal local de Wi-Fi da UMC" — aí entra em pós-MVP.
- **Múltiplas UMC.** O MVP suporta cadastro de 1 ou várias UMC, mas a agenda consolidada "quem vai em qual UMC hoje" entre várias unidades é simplificada no MVP (cada UMC tem sua agenda independente, sem otimização cross-UMC). Gatilho para ampliar: cliente com 3+ UMC em rotação.
- **Frota de carros pool com reserva avançada.** Reserva simples entra (§3.8). Reserva com conflito de horário, fila de espera, troca entre técnicos — fica fora. Gatilho: feedback consistente.

## 5. Jornadas críticas (alto nível)

O MVP precisa suportar estas jornadas fim a fim. O detalhamento campo-a-campo está em `docs/product/journeys.md`.

1. **Jornada 1 — Entrada de pedido novo (bancada).** Cliente liga/envia e-mail pedindo calibração. Atendente cadastra (ou reaproveita) cliente, cadastra instrumento, cria ordem de serviço. Sistema agenda.
2. **Jornada 2 — Execução técnica em bancada.** Técnico recebe fila, identifica instrumento, seleciona padrão, mede, preenche condições ambientais, submete para cálculo de incerteza. Orçamento é versionado.
3. **Jornada 3 — Aprovação e emissão.** Gerente revisa cálculo, aprova, sistema gera certificado em PDF e numera. Cliente recebe e-mail com link.
4. **Jornada 4 — Fiscal e cobrança.** NFS-e é emitida junto da aprovação. Baixa no contas a receber acontece no mesmo momento. Cliente recebe XML da nota.
5. **Jornada 5 — Cliente final consulta.** Cliente externo entra no portal, vê seu histórico, baixa certificado antigo, confere validade.
6. **Jornada 6 — Visita de campo com veículo operacional.** Técnico vai ao cliente sozinho, calibra no local, captura tudo offline, sincroniza depois. Despesa do dia atrelada à OS.
7. **Jornada 7 — Visita de campo com UMC.** Técnico + motorista UMC vão ao cliente, calibram balança rodoviária/grande com massas-padrão pesadas, colaboram multi-pessoa offline, fotografam tudo, sincronizam depois.
8. **Jornada 8 — Caixa de despesa por OS.** Técnico registra despesa em campo (foto obrigatória, OS obrigatória), saldo otimista cai em tempo real, escritório faz triagem + aprovação, reembolso em lote.
9. **Jornada 9 — Vendedor externo.** CRM offline, visita cliente, orçamento em campo, pipeline em tempo real pro gerente.
10. **Jornada 10 — Colaboração multi-pessoa offline.** Equipe (técnico + motorista + auxiliar) na mesma OS, offline, sincroniza sem perder dado, resolve conflito quando há sobreposição de edição.

## 6. O que este arquivo NÃO decide

Este arquivo não escolhe:
- Linguagem de programação, framework, banco de dados, ORM, runtime, servidor de aplicação — tudo isso é responsabilidade do Bloco 2 (ADR-0001 para backend + **ADR-0015 para stack mobile/offline-first**) e do `docs/architecture/foundation-constraints.md` (item 1.5.8).
- Modelo de multi-tenancy (RLS vs DB-per-tenant vs schema-per-tenant) — `foundation-constraints.md`.
- Modelo de cobrança e preço — `docs/product/pricing-assumptions.md` (item 1.5.15).
- RNFs numéricos de performance/capacidade — `docs/product/nfr.md` (item 1.5.5).

## 7. Critério de sucesso do MVP

O MVP está "no ar" quando um único cliente real (primeira empresa pagante) consegue, dentro do Kalibrium, executar **pelo menos uma jornada completa em cada um dos três modos de operação (bancada + campo-veículo + campo-UMC)**, indo do pedido à baixa do pagamento, sem usar planilha, software legado ou portal externo — e **com a operação de campo 100% offline-capable** (técnico consegue trabalhar 2 dias sem sinal e sincronizar quando voltar). Essa jornada completa, medida em dias úteis do pedido à baixa, deve ser menor que a linha de base atual do mesmo cliente. Se não for, o MVP falhou no critério e entra em ciclo de discovery, não em ciclo de crescimento.

## 8. Relação com E01-E03 já implementados (merged)

Os épicos E01 (setup Laravel + PG + Redis + CI), E02 (auth) e E03 (CRUD cliente + contato) foram implementados e mergidos antes da ampliação deste escopo. Auditoria de aproveitamento:

- **E01 (backend Laravel):** aproveitável. Backend segue como API, frontend antigo é descartado. Requer leve refactor para ficar "API-only" (sem Blade).
- **E02 (auth):** core aproveitável. Ajustes necessários: JWT long-lived + refresh para aguentar 4 dias offline, device binding (§3.12 `REQ-SEC-004`), biometria no mobile (§3.12 `REQ-SEC-001`), wipe remoto (§3.12 `REQ-SEC-003`).
- **E03 (CRUD cliente):** modelo de dados aproveitável. Frontend é 100% refeito em PWA + Capacitor. Regras de negócio (validação de CNPJ, unicidade, soft-delete) permanecem.

Os épicos novos de offline-first (E15+), quando criados, **não substituem** E01-E03 — eles adicionam o shell PWA, o sync engine, os módulos de campo, o CRM do vendedor, a caixa de despesa e o estoque multinível. Ver `epics/ROADMAP.md` (também ampliado).
