# Jornadas do Kalibrium

> **Status:** ativo — ampliado em 2026-04-16. A versão anterior (5 jornadas, com Jornada 1 detalhada e 2-5 em esqueleto) está correta e preservada integralmente. Foram **adicionadas** as jornadas 6-11, que cobrem operação de campo, UMC, caixa de despesa por OS, CRM offline do vendedor, colaboração multi-pessoa e sincronização pós-offline. Backup do estado anterior em `journeys-backup-2026-04-16.md`. Depende de `mvp-scope.md` (1.5.2) e `personas.md` (1.5.3). A granularidade deste arquivo é "persona → intenção → passo → sistema" — NÃO é spec de tela.
>
> **Princípio offline-first transversal:** todas as jornadas que envolvem operação em campo (Jornadas 6, 7, 8, 9, 10) funcionam 100% offline — o usuário nunca precisa "decidir" se está conectado. O sistema opera igual com ou sem sinal; sincroniza silenciosamente quando conectar. Jornadas 1-5 (operação de laboratório/bancada) assumem conectividade normal mas também sobrevivem a queda de sinal sem perder dado.

---

## Jornada 1 — Pedido novo, da entrada ao pagamento (fluxo fim a fim detalhado)

Esta é a jornada-âncora do MVP no modo **bancada**. Se qualquer outra jornada conflitar com ela, esta manda no recorte de laboratório fixo. Todas as personas aparecem ao longo do fluxo.

### 1.1. Gatilho — cliente final pede calibração

- **Persona envolvida:** Rafael (cliente final) → atendente do laboratório.
- **Contexto:** Rafael liga ou manda e-mail dizendo "preciso calibrar 8 paquímetros + 2 micrômetros, preciso do certificado em até 7 dias úteis".
- **Estado inicial no sistema:** nada.

### 1.2. Passo 1 — Cadastro ou reaproveitamento do cliente

- **Persona:** Cláudia (atendente/administrativa), eventualmente Marcelo no início.
- **Intenção:** não duplicar cadastro do cliente.
- **Passo no sistema:** atendente abre a tela de novo pedido. Digita o CNPJ do cliente. Sistema busca: se existe, preenche os campos; se não, abre o formulário de cadastro.
- **Decisão de produto:** busca por CNPJ é campo obrigatório. Se o cliente for pessoa física, usa CPF no mesmo campo.

### 1.3. Passo 2 — Cadastro ou reaproveitamento de cada instrumento

- **Intenção:** vincular cada instrumento ao histórico do próprio equipamento, não criar um novo.
- **Passo:** para cada um dos 10 instrumentos, atendente informa número de série + modelo. Se o sistema já conhece, puxa a última calibração anterior. Se não, cadastra.
- **Regra:** o mesmo número de série não pode estar vinculado a dois CNPJs diferentes no mesmo tenant — é alerta, não bloqueio (instrumento pode ter trocado de dono).

### 1.4. Passo 3 — Escolha de procedimento e prazo

- **Intenção:** cada instrumento recebe o procedimento técnico vigente correto. Prazo do cliente é registrado.
- **Passo:** sistema sugere o procedimento pelo tipo do instrumento. Atendente confirma ou escolhe outro. Prazo acordado entra no pedido.
- **Saída:** ordem de serviço (pedido) criada, com status "recebido".

### 1.5. Passo 4 — Agendamento na fila

- **Intenção:** distribuir trabalho entre técnicos respeitando prazo.
- **Passo:** sistema sugere fila por técnico baseada em carga atual + especialidade (Juliana cobre dimensional + pressão). Gerente confirma ou ajusta.
- **Saída:** pedido avança para "em fila" e aparece na tela de Juliana.

### 1.5-bis. Passo 4-bis — Validação de competência do técnico (novo v3)

- **Gatilho:** OS é atribuída a técnico.
- **Regra (`REQ-MET-011`):** sistema verifica habilitação técnica vigente do técnico para o domínio metrológico do instrumento da OS. Se vigente, prossegue. Se vencida ou inexistente, bloqueia atribuição e sugere alternativas (outros técnicos habilitados + disponíveis). Esta validação também corre **offline** no app — datas de habilitação embarcadas no pacote do dispositivo.
- **Fallback:** se nenhum técnico habilitado estiver disponível, OS entra em "pendente competência" com alerta para gerente (Persona 1) e Responsável de Qualidade (Persona 8 Aline) decidirem (treinar, contratar externo, reagendar).

### 1.6. Passo 5 — Execução técnica

- **Persona:** Juliana (técnica calibradora).
- **Intenção:** calibrar cada instrumento sem interromper o ritmo de bancada.
- **Passo:** Juliana abre a tela de execução no tablet de bancada. Para cada instrumento: confere a identificação, seleciona os padrões vigentes da lista pré-filtrada, registra as condições ambientais (temperatura/umidade capturadas do termo-higrômetro ou digitadas), lança os pontos medidos. Sistema já calcula a incerteza na medida em que os valores são lançados, usando o orçamento de incerteza do procedimento vigente.
- **Regra dura:** padrão com certificado vencido no dia da execução bloqueia o lançamento. Tentativa é registrada em log.

### 1.7. Passo 6 — Revisão e aprovação (com dual sign-off — ampliado v3)

- **Personas:** técnico executor (Persona 2 Juliana ou 2B Carlos) + verificador técnico (Marcelo Persona 1 ou Aline Persona 8).
- **Intenção:** dual sign-off conforme `REQ-MET-012` — certificado só é emitido com assinatura digital do executor e de um verificador distinto (conformidade ISO 17025 + RBC).
- **Passo 1 — assinatura do executor:** ao concluir cálculo de incerteza, executor assina (biometria + confirmação). Estado do certificado avança para `aguardando_verificação`.
- **Passo 2 — verificação técnica:** Marcelo (ou Aline em laboratório pequeno) recebe notificação push (`REQ-FLX-007`). Abre, vê trilha completa (padrão usado + SPC vigente, condições ambientais, orçamento de incerteza, competência do executor verificada, assinatura do executor). Verifica e assina. Estado avança para `verificado → emitido`. Se o verificador e o executor forem a mesma pessoa, sistema bloqueia (regra dura).
- **Exceção:** se algo está estranho, o verificador manda para retrabalho com observação — estado volta para `rascunho` e executor vê o motivo.
- **Saída na aprovação:** status avança para "certificado emitido" e o passo 7 (geração do PDF + numeração) é disparado.

### 1.8. Passo 7 — Geração do certificado e numeração

- **Intenção:** gerar o PDF definitivo.
- **Passo:** sistema monta o PDF no formato compatível com a RBC, numera sequencialmente dentro do tenant, registra hash do conteúdo e armazena imutavelmente.
- **Regra:** a numeração é atômica — não pode ter número pulado nem duplicado. Teste P2 cobre esse caso.

### 1.9. Passo 8 — Emissão fiscal (NFS-e, com ciclo de rejeição — ampliado v2)

- **Intenção:** emitir a nota fiscal de serviço na prefeitura do laboratório com cálculo correto de retenções (`REQ-FIS-008`) e ciclo formal de rejeição (`REQ-FIS-007`).
- **Passo:** sistema calcula retenções de ISS/IR/INSS/PIS/COFINS conforme regime tributário do tenant (Simples ou Lucro Presumido) e regras do município, aplica arredondamento half-even (ABNT), monta XML, dispara à API da prefeitura. Aguarda confirmação.
- **Sucesso (autorização):** prefeitura retorna autorizada → sistema vincula número fiscal ao pedido, emite XML + PDF, baixa automaticamente no contas a receber, envia XML ao cliente.
- **Falha tratada (rejeição) — ampliação v2:** se a prefeitura rejeitar (motivo retornado no payload: erro de cálculo, dado do cliente incorreto, série fiscal duplicada, etc), a NFS-e entra em estado `rejeitada`. Sistema captura o motivo em linguagem clara (R12), notifica atendente (Cláudia) ou gestor em campo (Diego) via push (`REQ-FLX-007`). Operador abre a nota, corrige o ponto exato apontado pela prefeitura (mantendo rastro da correção), dispara retransmissão — mesmo XML com ajustes, nova tentativa à prefeitura. Estados: `preparada → transmitindo → autorizada | rejeitada → em_correção → retransmitida → autorizada`. Sem bloquear entrega do certificado ao cliente; cliente recebe a NFS-e quando o ciclo fechar. Trilha de auditoria completa preservada.
- **Fallback offline (mantido):** se gestor em campo está sem sinal no momento de emitir, a nota é preparada localmente com preview completo (`REQ-FIS-006`) e transmitida à prefeitura quando pegar sinal. Ciclo de rejeição se aplica depois.

### 1.10. Passo 9 — Entrega ao cliente final

- **Intenção:** Rafael receber o certificado rapidamente.
- **Passo:** sistema envia e-mail para Rafael com link único assinado apontando para o PDF do certificado + link para o portal onde ele pode baixar o histórico. Notificação opcional por WhatsApp, se Rafael consentiu.

### 1.11. Passo 10 — Consulta futura e revalidação

- **Intenção:** Rafael volta quando precisa provar calibração para a auditoria da montadora ou para revalidar.
- **Passo:** Rafael loga no portal, vê histórico do CNPJ, baixa PDF antigo. 30 dias antes do vencimento, sistema envia alerta de "vencendo em 30 dias".
- **Fechamento da jornada:** recorrência semestral ou anual.

### 1.12. Métricas de sucesso da jornada

- **Dias corridos entre 1.1 e 1.9:** alvo menor que 7 úteis no caso comum.
- **Taxa de retrabalho:** menor que 5% dos pedidos.
- **Aderência ao prazo acordado com o cliente:** maior que 90%.
- **Taxa de pedidos com falha fiscal não resolvida há mais de 24h:** menor que 2%.

---

## Jornada 2 — Execução técnica em lote (esqueleto)

Uma variação da Jornada 1 para o caso do cliente grande que manda 40 instrumentos de uma só vez. Pontos de diferença: cadastro em planilha, fila é um bloco único, aprovação em lote pelo gerente. Detalhar em rodada dedicada.

## Jornada 3 — Cliente consulta e revalida (esqueleto)

Rafael entra no portal para conferir certificados do histórico ou para agendar próxima revalidação. Sem criar pedido novo — só consulta e, opcionalmente, dispara um pedido novo pré-preenchido. Detalhar em rodada dedicada.

## Jornada 4 — Auditoria RBC (esqueleto)

Auditor da Cgcre sorteia um certificado emitido nos últimos 12 meses e pede trilha completa. Passos: login de auditor com perfil restrito, abertura do pedido sorteado, visualização da trilha (padrão + procedimento + condições ambientais + orçamento de incerteza + assinaturas), exportação em PDF consolidado. Detalhar em rodada dedicada, provavelmente próximo do Bloco 5.

## Jornada 5 — Administração do tenant (esqueleto)

Marcelo entra nas telas administrativas para cadastrar usuário, cadastrar novo padrão (com importação de certificado vigente), criar procedimento novo, ajustar procedimento existente, exportar CSV do mês, consultar dashboard operacional. Detalhar em rodada dedicada.

---

## Jornada 6 — Visita em campo com veículo operacional (técnico só, balança industrial média/pequena) — detalhada

Esta é a jornada-âncora do MVP no modo **campo simples**. Técnico único vai ao cliente com o carro/caminhonete assinado a ele. Sem UMC. Instrumento já está no cliente (não vai ser trazido para bancada). Jornada 100% offline do começo ao fim.

### 6.1. Gatilho — OS programada para campo

- **Persona envolvida:** Cláudia (atendente) ou Patrícia (vendedora) criou a OS com tipo `campo-veiculo-operacional`; Carlos (técnico de campo) é o responsável.
- **Pré-requisito:** OS com endereço, cliente, instrumentos a calibrar, procedimento sugerido, padrões necessários, horário combinado com o cliente.
- **Dia anterior à visita:** Carlos abre o app no celular (em casa, online), puxa as OS do dia seguinte — o sistema baixa no celular **tudo** que ele vai precisar (ficha do cliente, contatos, histórico dos instrumentos, procedimentos vigentes, padrões disponíveis no estoque pessoal dele).

### 6.2. Passo 1 — Saída do laboratório / escritório

- **Intenção:** registrar início do deslocamento.
- **Passo:** Carlos abre a OS no celular, aperta "iniciar deslocamento". App registra KM inicial (do odômetro do carro — ele digita) e horário. GPS captura localização se tiver sinal.
- **Offline:** nada disso precisa de sinal. Tudo fica na fila de sincronização local.

### 6.3. Passo 2 — Chegada ao cliente

- **Intenção:** marcar chegada e cumprir formalidades de portaria.
- **Passo:** Carlos registra "cheguei" no app. Tira foto da fachada ou do crachá que recebeu (opcional). Abre a checklist de segurança do cliente (se o cliente tiver NR-específica cadastrada — ex: fábrica de alimentos exige touca).
- **Regra:** chegada cria "janela da visita" no sistema — todas as ações subsequentes ficam atreladas a esta visita para fins de custo-por-OS.

### 6.4. Passo 3 — Execução da calibração em campo

- **Intenção:** calibrar os instrumentos que o cliente tem no chão de fábrica.
- **Passo:** para cada instrumento (ex: 3 balanças industriais de 150 kg cada, espalhadas pela linha), Carlos abre a OS, seleciona o instrumento, seleciona os padrões usados (do estoque pessoal dele que está no carro — padrões de massa de 10 kg, 50 kg, 100 kg), registra condições ambientais da fábrica (temperatura, umidade — digitadas manualmente ou capturadas de um termo-higrômetro Bluetooth se disponível), lança os pontos medidos. Tira foto do selo do instrumento calibrado (obrigatório) e foto geral do instrumento calibrado (opcional).
- **Offline:** mesmo fluxo da Jornada 1 Passo 5 (Juliana na bancada), só que num app mobile. Cálculo de incerteza roda localmente.
- **Regra dura:** padrão com certificado vencido **continua bloqueando** o lançamento, mesmo offline. O app sabe a validade dos padrões do estoque pessoal do Carlos.

### 6.5. Passo 4 — Assinatura do cliente

- **Intenção:** cliente atesta que a calibração foi feita no equipamento dele.
- **Passo:** Carlos apresenta resumo no celular para o representante do cliente (nome + RG + cargo), o cliente assina no touch do celular. Assinatura fica atrelada à OS com timestamp.
- **Opcional:** cliente pode também aceitar via QR Code enviado por e-mail para confirmar depois.

### 6.6. Passo 5 — Caixa de despesa do dia

- **Intenção:** registrar todos os gastos atrelados a esta OS.
- **Passo:** Carlos abre "despesas desta OS". Para cada gasto: escolhe tipo (combustível, pedágio, almoço, estacionamento, hotel), informa valor, **obrigatoriamente tira foto do cupom/nota**, escolhe origem do dinheiro (cartão corporativo, adiantamento, próprio bolso). Sistema calcula saldo otimista em tempo real.
- **Regra dura:** toda despesa precisa estar atrelada a **uma** OS — despesa "órfã" não é aceita. Se o gasto cobre 2 OS (ex: abastecimento pra dia inteiro com 2 visitas), Carlos divide manualmente entre as OS.

### 6.7. Passo 6 — Saída do cliente / retorno

- **Intenção:** fechar a visita.
- **Passo:** Carlos aperta "saindo". App registra KM/horário final da visita, horário de saída. Quando chega em casa ou no laboratório, aperta "chegada final" — fecha o deslocamento do dia.

### 6.8. Passo 7 — Sincronização silenciosa

- **Intenção:** tudo o que Carlos fez em campo vira dado no escritório.
- **Passo:** em algum momento entre 6.3 e 6.7, Carlos pega sinal. O app sincroniza silenciosamente em background: OS, calibrações, fotos, despesas, assinaturas, deslocamento. Se falha de sinal durante o upload, retoma no próximo. Usuário não precisa fazer nada.
- **Estado final da OS:** mesma do Passo 5 da Jornada 1 — "aguardando aprovação" para o Marcelo.

### 6.9. Passos 8-10 — Aprovação, certificado, NFS-e, entrega

- Idênticos à Jornada 1 passos 1.7 a 1.10. Marcelo aprova, certificado é gerado, NFS-e é emitida pelo escritório, Rafael (ou comprador equivalente do cliente) recebe.

### 6.10. Métricas de sucesso desta jornada

- **Dias corridos entre 6.1 e 6.9:** alvo menor que 3 úteis.
- **Tempo de digitação "pós-campo" por Carlos:** alvo zero (tudo foi digitado em campo no próprio app).
- **Taxa de OS de campo com despesa "órfã" (sem OS atrelada):** zero.
- **Taxa de sincronização completa em 24h após saída do cliente:** maior que 98%.

---

## Jornada 7 — Visita em campo com UMC (equipe com técnico + motorista) — detalhada

Esta é a jornada-âncora do MVP no modo **campo pesado**. UMC acompanha, motorista/operador de guindaste participa da OS, pode ter mais de um técnico. Balança rodoviária típica. Jornada 100% offline do começo ao fim, com colaboração multi-pessoa na mesma OS.

### 7.1. Gatilho — OS programada para UMC

- **Persona envolvida:** Marcelo (gerente) ou Cláudia (atendente) cria a OS com tipo `campo-umc`. Carlos (técnico principal), opcionalmente Juliana (técnica auxiliar), Lúcio (motorista UMC) são adicionados à equipe. OS bloqueia a agenda da UMC para aquele dia.
- **Pré-requisito:** UMC com padrões rastreáveis válidos, combustível, manutenção em dia, motorista escalado.
- **Dia anterior:** Carlos e Lúcio abrem o app. Cada um vê sua parte: Carlos vê instrumentos, procedimentos, padrões a bordo da UMC + padrões do estoque pessoal; Lúcio vê rota, endereço, telefone do contato no cliente, horário combinado. Ambos baixam tudo offline.

### 7.2. Passo 1 — Partida da UMC

- **Intenção:** registrar início do trabalho do dia.
- **Passo:** Lúcio aperta "UMC saindo" no app dele. KM inicial do odômetro do caminhão, horário. Carlos aperta "saída" no app dele (se estiver junto no caminhão) ou sai depois pelo carro dele, se for encontrar Lúcio no cliente.

### 7.3. Passo 2 — Abastecimento / pedágios (no caminho)

- **Persona:** Lúcio.
- **Passo:** cada abastecimento ou pedágio, Lúcio tira foto do cupom, registra valor, origem do dinheiro (cartão corporativo da UMC, adiantamento). Tudo atrelado à OS ativa da UMC naquele dia.
- **Regra:** abastecimento da UMC entra como despesa da OS. Se o tanque enche e atende 2 OS do dia, Lúcio divide proporcional (ou o escritório ajusta na triagem).

### 7.4. Passo 3 — Chegada ao cliente

- **Persona:** Lúcio (chega com a UMC) + Carlos (chega separado, se veio de outro carro).
- **Passo:** Lúcio aperta "UMC chegou". Carlos aperta "cheguei" quando chega. App mostra pro cliente no portão (se o cliente pediu app de controle de visita) ou pro gestor interno que a equipe está completa.
- **Foto obrigatória:** Lúcio tira foto da UMC posicionada no local (documento de chegada).

### 7.5. Passo 4 — Preparação da balança rodoviária

- **Persona:** Carlos + Lúcio juntos.
- **Passo:** Lúcio opera o guindaste para posicionar as massas-padrão (500 kg, 1.000 kg, etc) na plataforma da balança do cliente, conforme o protocolo da calibração. Carlos supervisiona, registra no app a posição de cada massa, o momento, e confere identificação de cada padrão usado. Assinatura cruzada no app: Lúcio confirma posicionamento, Carlos confirma uso.
- **Regra:** padrão a bordo da UMC **também bloqueia se validade vencida** — mesmo offline, o app sabe as datas.

### 7.6. Passo 5 — Lançamento dos pontos medidos

- **Persona:** Carlos (ou o técnico designado como "responsável pelo lançamento").
- **Passo:** idêntico à Jornada 6 Passo 4, mas com os padrões da UMC. Lança pontos, registra ambiente (vento, temperatura externa, chuva — tudo relevante pra balança rodoviária), calcula incerteza.
- **Colaboração:** se Juliana está junto como auxiliar, ela pode estar preenchendo outra parte da ficha em paralelo no app dela — ambos os celulares contribuem pra mesma OS. Quando ambos estão online (na cidade, via 4G), veem em tempo real o que o outro digitou. Quando offline na obra/usina, cada um preenche isolado.

### 7.7. Passo 6 — Foto, vídeo, assinatura

- **Persona:** Carlos.
- **Passo:** tira foto do selo aplicado na balança calibrada, foto geral da balança + UMC + placa da cidade/empresa (prova visual da visita). Captura assinatura do representante do cliente no touch.
- **Opcional:** vídeo curto (10-30s) da massa sendo posicionada — útil como prova em caso de contestação futura.

### 7.8. Passo 7 — Fechamento da UMC no cliente

- **Persona:** Lúcio.
- **Passo:** Lúcio opera o guindaste removendo as massas da plataforma, arruma a UMC, aperta "UMC saindo do cliente". App registra horário.

### 7.9. Passo 8 — Caixa de despesa consolidada

- **Persona:** Carlos + Lúcio.
- **Passo:** cada um revisa suas despesas atreladas à OS no app. Carlos tem: almoço da equipe, eventual hotel, estacionamento. Lúcio tem: combustível, pedágios, lavagem da UMC. Todos fotos anexadas. Sistema consolida o custo total da OS em tempo real (otimista, antes da triagem).

### 7.10. Passo 9 — Retorno + sincronização

- **Passo:** UMC volta pra garagem (laboratório). Lúcio registra KM final, horário final, aperta "UMC de volta". App sincroniza tudo silenciosamente quando pegar sinal forte (geralmente na cidade ou ao chegar no laboratório via Wi-Fi).

### 7.11. Passo 10 — Aprovação, certificado, NFS-e, entrega

- Idênticos à Jornada 1 passos 1.7-1.10. Marcelo aprova, certificado gerado, NFS-e emitida (provavelmente por Diego se a visita foi grande e ele acompanhou, ou por Cláudia no escritório depois).

### 7.12. Métricas de sucesso desta jornada

- **Tempo total da visita (chegada a saída):** conforme protocolo da calibração da balança rodoviária (tipicamente 2-4 horas).
- **Taxa de OS com equipe completa na sincronização (técnico + motorista registraram):** maior que 98%.
- **Taxa de despesa fotografada corretamente atrelada à OS:** 100% (hard rule).
- **Taxa de conflitos de sync que exigiram intervenção manual:** menor que 2% das OS em equipe.

---

## Jornada 8 — Caixa de despesa por OS, do gasto à aprovação — detalhada

Esta jornada transpassa todas as jornadas de campo (6, 7, 9). Documenta o ciclo completo do dinheiro.

### 8.1. Três origens de dinheiro

1. **Cartão corporativo da empresa** — cartão entregue ao técnico/motorista, todo gasto já é da empresa. Técnico/motorista só registra o valor + foto do cupom + atrela à OS.
2. **Adiantamento em dinheiro** — técnico/motorista recebe R$ X em dinheiro antes da viagem. Usa e registra. Se sobra, devolve; se falta, pede reembolso.
3. **Próprio bolso (reembolso)** — técnico/motorista usa dinheiro próprio quando necessário, registra, recebe reembolso depois da aprovação.

### 8.2. Passo 1 — Registro da despesa (em campo)

- **Persona:** técnico ou motorista em campo.
- **Passo:** conforme Jornada 6 Passo 5 ou Jornada 7 Passo 8. Tipo + valor + foto obrigatória + origem do dinheiro + OS atrelada. Se o app não consegue puxar a OS ativa automaticamente, lista as OS em aberto do dia para seleção manual.
- **Saldo otimista:** se origem é cartão corporativo com R$ 2.000 de limite, saldo desce pra R$ 1.850 após um pedágio de R$ 150. Visão em tempo real **antes** da triagem.

### 8.3. Passo 2 — Triagem pelo escritório

- **Persona:** Cláudia (administrativa).
- **Passo:** ao final do dia (ou a qualquer momento), Cláudia abre a fila de triagem. Cada despesa tem: foto, valor, OS, técnico, origem. Ela valida: a foto é legível? Cupom bate com valor registrado? A OS existe e estava ativa? Categoria (combustível/almoço/etc) está correta?
- **Decisão:** aprovar, reprovar (com motivo — ex: "cupom ilegível, manda de novo"), ou reclassificar (ex: técnico marcou "almoço" mas era "café da manhã").

### 8.4. Passo 3 — Aprovação final e reembolso

- **Persona:** Cláudia para valores normais, Marcelo quando o valor ultrapassa limite X.
- **Passo:** aprovação final marca a despesa como "aprovada". Se origem é cartão corporativo, fecha o ciclo (gasto foi da empresa). Se origem é adiantamento, fecha contra o adiantamento (sobra vira reembolso; falta, reembolso complementar). Se origem é próprio bolso, dispara reembolso no próximo fechamento.
- **Pagamento de reembolso:** PIX ou transferência, em lote semanal/mensal conforme política da empresa.

### 8.5. Regras transversais

- **Foto obrigatória sempre**. Despesa sem foto é rejeitada automaticamente pelo app.
- **OS obrigatória sempre**. Despesa órfã não existe.
- **Conciliação com fatura do cartão corporativo**: ao fim do mês, Cláudia importa a fatura do cartão e o sistema concilia linha-a-linha contra as despesas registradas. Discrepância vira alerta (ex: lançamento no cartão sem despesa registrada = técnico esqueceu de lançar).
- **Custo real por OS**: soma de todas as despesas aprovadas da OS entra no relatório "lucratividade da OS" — Marcelo vê se o preço cobrado cobriu o custo real.

### 8.6. Métricas de sucesso

- **Taxa de despesa registrada no dia do gasto:** maior que 95%.
- **Taxa de despesa aprovada sem rejeição:** maior que 90%.
- **Tempo médio entre gasto e aprovação:** menor que 3 dias úteis.
- **Tempo médio entre aprovação e reembolso:** menor que 7 dias úteis.

---

## Jornada 9 — Vendedor externo (CRM mobile offline + orçamento em campo) — detalhada

Jornada da Patrícia. Ela visita cliente em fábrica, lida com sinal irregular o dia todo, precisa responder rápido pra ganhar da concorrência.

### 9.1. Preparação da rota (noite anterior ou de manhã)

- **Persona:** Patrícia.
- **Passo:** abre o app, vê as 6-8 visitas programadas pro dia no mapa. O app baixa offline: ficha completa de cada cliente, histórico de calibrações, contratos vigentes, certificados recentes, contatos, última conversa, tabela de preços do laboratório.

### 9.2. Visita ao cliente

- **Passo:** chega no cliente, abre a ficha dele no app. Vê o histórico (última calibração foi há 4 meses, contrato vence em 60 dias, 3 instrumentos vencendo no próximo trimestre). Conversa com o comprador munida de dado real.
- **Registro da visita:** ao sair, grava nota de voz ("visita ok, João pediu proposta de renovação com 10% desconto, retornar em 5 dias"). App transcreve automaticamente quando sincronizar. Tira foto do crachá/fachada como prova de visita.

### 9.3. Orçamento em campo

- **Intenção:** fechar a venda no portão, não no dia seguinte.
- **Passo:** no portão do cliente, Patrícia abre "novo orçamento". Cliente + instrumentos (seleciona do histórico se já calibrou antes, cadastra novos se necessário) + desconto dentro da alçada dela + prazo. Sistema calcula total, aplica tabela vigente, gera orçamento em PDF **offline**.
- **Entrega:** Patrícia mostra no celular, manda pro WhatsApp do cliente via link único (link é gerado localmente, sincroniza quando conectar, mas o PDF pode ser enviado direto pelo WhatsApp imediatamente).
- **Confirmação:** se o cliente diz "fechado", Patrícia converte orçamento em pedido no mesmo app — vira OS no sistema quando sincronizar.

### 9.4. Follow-up automático

- **Passo:** app lembra Patrícia de retornar no prazo que ela prometeu. Lista de pendências do dia sempre visível na tela inicial.
- **Visão do gestor:** Marcelo vê o pipeline dela em tempo real quando ela sincroniza. Se 5 orçamentos estão há +10 dias sem retorno, ele conversa com Patrícia.

### 9.5. Conflito de carteira (multi-vendedor)

- **Regra:** cliente "Metalúrgica ABC" pertence à carteira da Patrícia. Vendedor José não pode ver/mexer na ficha a menos que o gerente transfira. Conflito de carteira não existe no dia-a-dia.
- **Exceção:** gerente Marcelo vê tudo.

---

## Jornada 10 — Colaboração multi-pessoa na mesma OS offline — detalhada

Esta jornada descreve o mecanismo transversal de colaboração que as Jornadas 6 e 7 usam quando há mais de uma pessoa.

### 10.1. OS com equipe

- **Pré-requisito:** OS tem `equipe` = [Carlos (técnico principal), Juliana (técnica auxiliar), Lúcio (motorista UMC)]. Pode ter até 5 pessoas.
- **Papéis na OS:** cada membro tem atribuições visíveis (Carlos lança pontos, Juliana preenche ficha ambiental, Lúcio registra diário de bordo da UMC).
- **Dono da OS:** existe o papel de "responsável" (geralmente o técnico principal). Só o responsável pode fechar a OS.

### 10.2. Edição em tempo real (online)

- **Cenário:** todos estão com sinal de 4G no cliente (cidade, indústria com Wi-Fi).
- **Comportamento:** cada celular mostra em tempo real o que os outros estão preenchendo. Se Juliana já está digitando "umidade: 55%", Carlos vê o campo sendo preenchido e não duplica. Se dois tentam editar o mesmo campo simultaneamente, o sistema mostra "Juliana está editando este campo" e bloqueia o de Carlos por 3 segundos.

### 10.3. Edição paralela (offline)

- **Cenário:** todos offline na mina/usina. Sem sinal.
- **Comportamento:** cada celular opera de forma isolada. Carlos preenche pontos medidos no celular dele. Juliana preenche foto + observação no dela. Lúcio registra despesa no dele. Ninguém vê o que o outro está fazendo.
- **Sinalização visual:** cada app mostra banner "modo offline — você não está vendo atualizações dos colegas, eles não veem as suas. Vai sincronizar quando conectar".

### 10.4. Sincronização pós-offline (convergência)

- **Cenário:** um deles pega sinal (saindo do cliente, ou quando chega no posto de gasolina). O app dele sincroniza tudo que ele preencheu offline.
- **Comportamento:** conforme outros membros da equipe pegarem sinal, seus aplicativos também sincronizam, e o conjunto converge para o estado consolidado.

### 10.5. Resolução de conflito

- **Cenário raro:** dois membros editaram **o mesmo campo da mesma OS** enquanto ambos estavam offline.
- **Comportamento:** regra "merge por campo" — cada campo mantém o último que foi editado quando o sync chega, mas **o sistema detecta que houve sobreposição real** (ambos escreveram valor no mesmo campo) e marca o registro como "conflito detectado — revisar".
- **Tela de conflito:** Carlos (ou o responsável) vê no app uma lista de conflitos pendentes. Para cada um, mostra os dois valores (ex: "temperatura: 23.5°C por Carlos às 14:05" vs "temperatura: 24.0°C por Juliana às 14:06"), e ele escolhe qual vale ou redige terceiro valor. Decisão registrada no audit log.

### 10.6. Tempo real por wi-fi local / bluetooth (fora do MVP v1)

- Nota: fora do MVP. Quando a equipe está offline mas os celulares podem se ver localmente (bluetooth ou wi-fi direto sem internet), poderia haver sync local entre eles. Isso é complexidade técnica significativa e fica para pós-v1. No MVP, offline = cada um isolado até um deles pegar sinal de internet real.

---

## Jornada 11 — Administração de UMC e frota — detalhada (esqueleto expansível)

Marcelo e Cláudia gerenciam o ciclo de vida da UMC e dos veículos operacionais.

### 11.1. Cadastro de UMC

- Placa, chassi, modelo, ano, capacidade do guindaste, massas-padrão a bordo (cada uma com seu certificado de rastreabilidade), motorista principal, agenda.

### 11.2. Agenda da UMC

- Visão calendário: quais OS estão programadas, qual tem conflito de horário, quando a UMC precisa de manutenção preventiva (bloqueia agenda automaticamente).
- Regra: UMC só pode estar em **uma OS ativa por vez** (se tem 2 OS pequenas no mesmo dia na mesma cidade, registrar como 2 OS separadas sequenciais, não paralelas).

### 11.3. Cadastro de veículo operacional

- Placa, modelo, `modo_uso` (assinado-fixo / compartilhado-pool), técnico responsável (se assinado), estoque pessoal a bordo (padrões portáteis, ferramentas).

### 11.4. Estoque a bordo (4 níveis)

1. **Laboratório** (garagem): estoque central de padrões, peças, materiais.
2. **UMC**: massas-padrão pesadas, guindaste, ferramentas especiais.
3. **Veículo operacional**: padrões leves, kit de campo do técnico.
4. **Carro pessoal do técnico** (mini-estoque portátil): ferramentas básicas, padrões de referência.

- Cada movimento (tira do laboratório, põe no veículo, usa no cliente) é rastreado. Em campo, o técnico só pode "usar" padrão que está no estoque do veículo/carro dele.

### 11.5. Manutenção

- Veículo avisa quando precisa de revisão (por KM ou por tempo). UMC avisa quando as massas-padrão precisam ser recalibradas (rastreabilidade).
- Manutenção bloqueia agenda.

---

## Jornada 12 — Titular LGPD exerce direito (NOVA v2)

**Gatilho:** cliente final, ex-cliente ou contato cadastrado solicita acesso, retificação, exclusão, portabilidade ou revogação de consentimento (`REQ-CMP-006`).

### 12.1. Canal
- Titular acessa canal formal (portal público do cliente `REQ-FLX-005` ampliado ou e-mail dedicado, `lgpd@<tenant>.kalibrium`) e abre requisição com: tipo de pedido, identificação (CPF/CNPJ + nome + e-mail), descrição livre.
- Sistema registra entrada com timestamp, número de protocolo e envia confirmação automática ao titular.

### 12.2. Triagem pelo DPO
- DPO do laboratório (Persona 8 Aline ou, em laboratório pequeno, Persona 1 Marcelo acumulando) recebe notificação (e-mail + push `REQ-FLX-007`).
- Abre requisição, valida identidade do titular (conferência contra base cadastral), classifica tipo (acesso/retificação/exclusão/portabilidade/revogação).
- Se requisição for inválida (titular não reconhecido) ou inviável (ex: pedido de exclusão de dado retido por obrigação legal de 10 anos RBC), responde com justificativa legal.

### 12.3. Atendimento
- **Acesso:** sistema gera relatório automático de todos os dados do titular (cadastro, contatos, certificados vinculados se for representante de cliente, logs de acesso). Exportado em PDF + CSV.
- **Retificação:** DPO ajusta dado específico; audit log registra alteração com "base: requisição LGPD #N".
- **Exclusão:** sistema anonimiza dados pessoais (nome vira hash, e-mail/telefone apagados) **preservando** registros metrológicos imutáveis (`REQ-CMP-001`) — titular deixa de ser identificável mas a trilha RBC continua válida.
- **Portabilidade:** exporta dados em formato estruturado (JSON/CSV).
- **Revogação de consentimento:** atualiza flags de consentimento (WhatsApp, e-mail marketing, push) sem apagar dado transacional necessário.

### 12.4. Resposta ao titular
- Dentro do prazo legal da LGPD (15 dias corridos, prorrogável por mais 15 com justificativa).
- Resposta sai por e-mail com número de protocolo e anexo da resposta (PDF).
- Sistema registra log imutável da resposta (data, conteúdo, autor DPO).

### 12.5. Caso crítico — notificação de breach (72h)
- Se o sistema detectar incidente de segurança (acesso não autorizado, vazamento), alerta automático dispara para DPO em até 1h.
- DPO tem 72h para notificar ANPD + titulares afetados (conforme ANPD).
- Sistema oferece template de notificação pronto, com lista de afetados.

### 12.6. Métricas de sucesso
- Tempo médio de atendimento < 10 dias corridos (abaixo do limite legal de 15).
- Zero requisições vencidas no prazo.
- 100% das respostas registradas com log imutável.

---

## Jornada 13 — Revalidação proativa de instrumento (NOVA v2)

**Gatilho:** `REQ-CRM-008` — motor detecta que certificado de instrumento está a 90 dias do vencimento.

### 13.1. Detecção + disparo
- Job agendado diário varre certificados emitidos (`REQ-MET-007`) e seleciona os que vencem em 90, 60 e 30 dias.
- Sistema verifica consentimento de notificação do contato do cliente (e-mail + WhatsApp conforme `REQ-FLX-003/004`).
- Gera cadência automática com template editável pelo tenant: "Seu [instrumento] calibrado em [data] vence em [90/60/30] dias. Quer agendar a próxima calibração?"
- Link único assinado no corpo da mensagem leva a uma página pública de agendamento (1 clique + escolha de data preferida + confirmação).

### 13.2. Engajamento
- Estados: `enviado` → `visto` (quando e-mail é aberto ou WhatsApp entregue) → `clicado` (quando link é acessado) → `agendado` (cliente confirmou data) → `convertido` (OS nova criada).
- Se cliente não agir em 90 dias, dispara em 60 dias; se não agir, dispara em 30 dias. Depois disso, desiste (para não virar spam).
- Cliente pode recusar explicitamente ("não quero mais receber sobre este instrumento") → sistema registra motivo e para cadência para este instrumento.

### 13.3. Conversão
- Cliente aceita agendamento via link → sistema cria OS automaticamente no status `recebido`, atrelada ao cliente + instrumento.
- Vendedor responsável pela carteira (`REQ-CRM-001`) recebe push (`REQ-FLX-007`) com: "OS nova automática criada a partir de revalidação proativa — cliente X, instrumento Y, data preferida Z".
- Vendedor confirma ou reagenda com o cliente (por WhatsApp ou ligação), atualiza OS, segue fluxo normal (Jornada 1 ou 6/7 conforme modo).

### 13.4. Relatório mensal
- Gerente (Persona 1 Marcelo) recebe em primeiro dia útil do mês relatório consolidado:
  - Revalidações disparadas no mês anterior.
  - Taxa de conversão por vendedor.
  - Instrumentos vencidos sem revalidação disparada (deveria ter disparado, falha de processo) — alerta crítico.

### 13.5. Métricas de sucesso
- Taxa de conversão ≥ 25% nos primeiros 3 meses.
- Receita recorrente de revalidação ≥ 15% do faturamento total no 6º mês.
- Zero instrumento vencido sem cadência disparada.

---

## Jornada 14 — Monitoria de qualidade pela Responsável Técnica (NOVA v2)

**Gatilho:** rotina semanal da Persona 8 (Aline) + alertas automáticos de drift (`REQ-MET-010`).

### 14.1. Rotina semanal
- Aline abre tela "Monitoria de Padrões" no Kalibrium (desktop).
- Visão única mostra: lista de todos os padrões de referência do laboratório, com coluna de status (verde/amarelo/vermelho), última leitura, próximo vencimento, desvio atual vs nominal, gráfico SPC em miniatura clicável.
- Filtros: por domínio metrológico, por local de estoque (laboratório / UMC / veículo), por status.

### 14.2. Análise SPC de padrão individual (`REQ-MET-009`)
- Aline clica em um padrão específico → abre gráfico de controle em tela cheia.
- Eixo X: datas de calibração do próprio padrão. Eixo Y: desvio do valor medido em relação ao valor nominal.
- Linhas: UCL (limite superior de controle), LCL (limite inferior), centro (média histórica), warning lines (1 sigma, 2 sigma).
- Pontos coloridos por regra de Nelson (tendências estatísticas conhecidas: pontos fora de controle, 7+ pontos do mesmo lado da média, tendência crescente/decrescente, etc).
- Aline pode anotar observação textual no gráfico ("peguei padrão X e levei para conferência externa em 15/08").

### 14.3. Tratamento de alerta de drift (`REQ-MET-010`)
- Sistema detectou drift automático → push (`REQ-FLX-007`) + e-mail para Aline.
- Aline abre alerta → vê: qual padrão, qual regra de Nelson foi violada, último valor, histórico recente.
- Decisões possíveis:
  - **Aceitar e observar** — drift ainda dentro de limites, anota explicação, mantém padrão em uso.
  - **Bloquear preventivamente** — muda status do padrão para "em verificação", sistema bloqueia uso em novas calibrações (comportamento idêntico ao `REQ-MET-008`).
  - **Recalibrar** — Aline abre OS interna de recalibração externa do padrão (envio para laboratório de ordem superior ou Inmetro). Padrão fica bloqueado até retornar com novo certificado.

### 14.4. Auditoria RBC anual
- Auditor Cgcre visita presencialmente (ou remoto em casos específicos).
- Aline acompanha o auditor no Kalibrium — sequência típica: auditor escolhe um certificado emitido → clica → sistema mostra trilha: instrumento, cliente, OS, procedimento usado (com versão), padrões usados (com certificados e SPC visíveis), orçamento de incerteza aplicado, técnico executor (com habilitação vigente), condições ambientais do dia.
- Trilha navegável em 3-5 cliques. Auditor confere em minutos o que antes demandava dias.

### 14.5. Métricas de sucesso
- Zero incidente de padrão fora de controle não detectado.
- Tempo de preparação para auditoria RBC < 4 horas (antes: 2 semanas).
- 100% dos alertas de drift tratados dentro de 48h.

---

## Jornada 15 — Falha de padrão + suspensão retroativa de certificados (NOVA v3)

**Gatilho:** `REQ-MET-013` — padrão de referência falha na recalibração externa (laboratório de ordem superior retorna valor fora da incerteza aceitável) **OU** sistema dispara `DriftDetectado` de severidade crítica.

### 15.1. Detecção
- Aline (Persona 8) recebe push + e-mail imediato: "Padrão X falhou. 47 certificados podem estar afetados."
- Sistema já pré-computa a lista: todos os certificados emitidos usando o padrão X desde a data da **última calibração válida** do padrão até agora.

### 15.2. Triagem técnica
- Aline abre tela de "Falha de padrão". Visão: padrão afetado, janela de tempo, lista de 47 certificados com cliente/instrumento/data.
- Classifica severidade: (a) drift leve — certificados continuam aceitáveis com reanálise, ou (b) drift grave — todos os certificados são nulos.

### 15.3. Decisão e execução
- **Se drift leve:** Aline marca cada certificado como "revisado" com observação técnica. Trilha fica registrada (audit log imutável, `REQ-CMP-001`).
- **Se drift grave:** Aline cria Suspensão Retroativa formal. Sistema:
  1. Marca cada certificado afetado como `suspenso`. Emite não conformidade (`REQ-CMP-003` / NC).
  2. Dispara cadência automática aos clientes afetados (e-mail + WhatsApp com consentimento): "Identificamos que o certificado [N] pode estar comprometido. Oferecemos recalibração gratuita sem ônus." Template editável.
  3. Cria OS nova de garantia (`REQ-OPL-007`) para cada cliente que aceitar, com custo zero.
  4. Notifica Marcelo (Persona 1) com relatório consolidado: impacto comercial, custo previsto de recalibração, exposição financeira.

### 15.4. Recuperação do padrão
- Aline abre OS interna de recalibração externa do padrão (padrão já bloqueado via `REQ-MET-008`).
- Quando padrão volta com novo certificado válido, retorna ao operacional.

### 15.5. Métricas de sucesso
- Tempo entre detecção de falha e notificação do 1º cliente afetado < 24h.
- 100% dos clientes afetados recebem oferta de recalibração dentro de 72h.
- Zero certificado afetado esquecido (verificação cruzada automática).

---

## Jornada 16 — Re-despacho automático quando técnico fica indisponível (NOVA v3)

**Gatilho:** `REQ-OPL-006` — técnico atribuído fica indisponível em alguma das situações: (a) registra doença/afastamento no app, (b) UMC quebra na estrada, (c) bloqueio por competência vencida, (d) conflito de agenda detectado pelo sistema.

### 16.1. Detecção da indisponibilidade
- **Caso (a):** técnico abre app, vai em "Me reportar indisponível hoje", seleciona motivo e janela (hoje, esta semana).
- **Caso (b):** motorista UMC registra pane/acidente no diário de bordo (`REQ-UMC-004`) + muda status UMC para `em_manutenção_inesperada`.
- **Caso (c):** `REQ-MET-011` detectou que habilitação acabou de vencer e técnico tem OS futura.
- **Caso (d):** sistema detecta dois agendamentos conflitantes (raro — erro humano ao criar OS).

### 16.2. Identificação de OS afetadas
- Sistema lista OS atribuídas ao técnico/UMC indisponível nas próximas 48h (janela configurável).
- Para cada OS, calcula: urgência (prazo), cliente, domínio necessário, local (se campo).

### 16.3. Re-despacho automático
- Sistema roda `REQ-OPL-005` (round-robin) considerando técnicos disponíveis do domínio + habilitados (`REQ-MET-011`) + com agenda livre.
- Se encontra novo técnico, reatribui automaticamente.
- Notifica:
  - **Técnico novo** via push: "OS X atribuída a você em reatribuição. Detalhes: ...".
  - **Cliente afetado** por e-mail + WhatsApp: "Informamos que houve uma mudança na equipe que vai atender sua calibração em [data]. O novo técnico é [nome]. Tudo permanece conforme o combinado."
  - **Gerente (Persona 1)** e **gestor em campo (Persona 6)** com resumo diário: "Y OS reatribuídas hoje por indisponibilidade de Z técnicos."

### 16.4. Falha — nenhum técnico disponível
- Se nenhum técnico está disponível (domínio raro, todos ocupados, feriado), OS vai para fila `pendente_reatribuição`.
- Alerta crítico para Marcelo (Persona 1).
- Sistema oferece opções: (a) reagendar com cliente (template pronto para WhatsApp), (b) contratar externo (registrar fornecedor), (c) manter técnico afastado voltar antes do deadline.

### 16.5. Métricas de sucesso
- 90% das reatribuições acontecem sem intervenção humana.
- Tempo médio entre detecção e reatribuição < 5 minutos.
- Zero cliente sem notificação de mudança de técnico.

---

## Jornada 17 — OS de garantia (classificação + custo zero, NOVA v3)

**Gatilho:** `REQ-OPL-007` — cliente reclama que calibração anterior está com problema, ou sistema identifica falha retroativa (via Jornada 15), ou SLA interno foi violado e Marcelo decide cobrir como garantia.

### 17.1. Abertura
- Cláudia (Persona 7) ou Marcelo recebe o chamado do cliente.
- Cria OS normalmente, mas **marca tipo = `garantia`** com referência à OS original (se for regarantia de calibração anterior) ou à não conformidade que originou (se for Jornada 15).

### 17.2. Execução
- OS segue fluxo normal (bancada ou campo, conforme apropriado).
- Técnico vê no app que é OS de garantia (ícone visual + observação).
- Custos operacionais reais (combustível, pedágio, hora-homem) **são registrados normalmente** via `REQ-DSP-001`.

### 17.3. Faturamento
- Fatura é gerada com **valor R$ 0,00** para o cliente, com linha explícita "OS de garantia — referência [OS-N]".
- NFS-e é emitida com valor zero ou isenção conforme regra do município (`REQ-FIS-008` reconhece casos de isenção).
- Rastreabilidade preservada (a OS existe formalmente no sistema, aparece no histórico do cliente, pode ser auditada).

### 17.4. Relatório gerencial
- Mensalmente, Marcelo vê no dashboard (Persona 1 visão Diretoria) indicador **"Custo real de garantia"**: soma dos custos operacionais das OS de garantia do mês.
- Permite decisões: ajustar política de preço, investigar padrão de não conformidade, treinar equipe, etc.

### 17.5. PÓS-MVP (não escopo)
- **Cost allocation:** atribuir custo da OS de garantia ao fornecedor original (se houver), à não conformidade específica, ou a uma conta de provisão. Fica PÓS.
- **Reembolso de garantia por terceiros** (seguros, fornecedor de padrão defeituoso). Fica PÓS.

### 17.6. Métricas de sucesso
- Custo de garantia < 2% do faturamento (benchmark setor).
- Zero OS de garantia sem referência à causa (OS original ou NC).
- Tempo de atendimento de garantia ≤ tempo de OS comercial equivalente.

---

## Regras transversais a todas as jornadas

- **Estado imutável:** pedido em "certificado emitido" ou adiante não pode ser editado — só reaberto via incidente registrado.
- **Audit log:** toda transição de status é registrada com usuário + data/hora + diferença.
- **Isolamento de tenant:** nenhuma jornada pode cruzar dados entre tenants.
- **Linguagem de erro:** mensagens ao usuário final seguem o glossário R12 (`glossary-pm.md`, item 1.5.6) — vocabulário de produto, nunca técnico.
- **Acessibilidade mínima:** teclado completo, contraste AA, foco visível. Alvo para detalhamento formal no Bloco 4.
- **Offline-first transversal:** nenhuma jornada exige conectividade contínua. Todas sobrevivem a queda de sinal; sincronizam silenciosamente quando conectar. Único passo que exige online: transmissão da NFS-e à prefeitura (Jornada 1 Passo 1.9, Jornada 6.9, Jornada 7.11) — se offline, fica em "preparada, aguardando transmissão".
- **Biometria no mobile:** toda abertura do app em dispositivo móvel exige biometria ou PIN. Wipe remoto é autorizado pela empresa (PM respondeu "tudo isso" em 2026-04-16 para a pergunta de segurança).
- **Criptografia local:** dados no dispositivo móvel ficam criptografados em repouso (AES-256 via SQLCipher ou equivalente). Chave derivada do login — sem login, os dados offline ficam ilegíveis.

---

## Mapa de jornadas por persona

| Persona | Jornadas primárias | Jornadas secundárias |
|---|---|---|
| 1 — Marcelo (gerente) | 1 (aprovação), 5 (admin tenant), 7 (decisão UMC), 8 (aprovação alto valor), 11 (frota) | 2, 3, 4 |
| 2 — Juliana (bancada) | 1 (execução técnica) | 2, 6 (eventual), 7 (eventual auxiliar), 10 |
| 2B — Carlos (campo) | 6, 7, 8, 10 | 1 (eventual bancada) |
| 3 — Rafael (cliente final) | 3 | 1 (gatilho) |
| 4 — Lúcio (motorista UMC) | 7, 8, 10, 11 | — |
| 5 — Patrícia (vendedora) | 9 | — |
| 6 — Diego (gestor campo) | 7, 8 (aprovação em campo), 9 (NFS-e remota) | 1, 6 |
| 7 — Cláudia (escritório) | 1 (cadastro + NFS-e), 8 (triagem), 11 (cadastros), **12 (recebe pedido LGPD)** | 2, 3, 5 |
| **8 — Aline (Responsável de Qualidade)** | **12 (DPO), 14 (monitoria qualidade), 15 (suspensão retroativa)** | **1 (dual sign-off), 4 (auditoria), 13 (validação), 17 (garantia)** |
| — | **(jornadas transversais novas v3)** | 16 (re-despacho — afeta todas as personas de campo), 17 (garantia — atendimento + gerente + financeiro) |
