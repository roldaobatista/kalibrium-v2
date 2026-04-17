# Jornadas do Kalibrium

> **Status:** ativo. Item 1.5.4 do plano da meta-auditoria #2 (Bloco 1.5 Nível 3). Depende de `mvp-scope.md` (1.5.2) e `personas.md` (1.5.3). Define **um** fluxo fim a fim com nível alto de detalhe (Jornada 1) e mantém os outros quatro em esqueleto. A granularidade deste arquivo é "persona → intenção → passo → sistema" — NÃO é spec de tela.

---

## Jornada 1 — Pedido novo, da entrada ao pagamento (fluxo fim a fim detalhado)

Esta é a jornada-âncora do MVP. Se qualquer outra jornada conflitar com ela, esta manda. Todas as personas aparecem ao longo do fluxo.

### 1.1. Gatilho — cliente final pede calibração

- **Persona envolvida:** Rafael (cliente final) → atendente do laboratório.
- **Contexto:** Rafael liga ou manda e-mail dizendo "preciso calibrar 8 paquímetros + 2 micrômetros, preciso do certificado em até 7 dias úteis".
- **Estado inicial no sistema:** nada.

### 1.2. Passo 1 — Cadastro ou reaproveitamento do cliente

- **Persona:** atendente (papel "administrativo" do laboratório, subtipo do gerente Marcelo no início).
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

### 1.6. Passo 5 — Execução técnica

- **Persona:** Juliana (técnica calibradora).
- **Intenção:** calibrar cada instrumento sem interromper o ritmo de bancada.
- **Passo:** Juliana abre a tela de execução no tablet de bancada. Para cada instrumento: confere a identificação, seleciona os padrões vigentes da lista pré-filtrada, registra as condições ambientais (temperatura/umidade capturadas do termo-higrômetro ou digitadas), lança os pontos medidos. Sistema já calcula a incerteza na medida em que os valores são lançados, usando o orçamento de incerteza do procedimento vigente.
- **Regra dura:** padrão com certificado vencido no dia da execução bloqueia o lançamento. Tentativa é registrada em log.

### 1.7. Passo 6 — Revisão e aprovação

- **Persona:** Marcelo (gerente/responsável técnico).
- **Intenção:** ser o gate humano antes do certificado sair.
- **Passo:** Marcelo recebe notificação de "pedido pronto para aprovação". Abre, vê a trilha completa (padrão usado, condições ambientais, orçamento de incerteza, assinatura do técnico). Aprova — ou, se algo está estranho, manda para retrabalho com observação.
- **Saída na aprovação:** status avança para "certificado emitido".

### 1.8. Passo 7 — Geração do certificado e numeração

- **Intenção:** gerar o PDF definitivo.
- **Passo:** sistema monta o PDF no formato compatível com a RBC, numera sequencialmente dentro do tenant, registra hash do conteúdo e armazena imutavelmente.
- **Regra:** a numeração é atômica — não pode ter número pulado nem duplicado. Teste P2 cobre esse caso.

### 1.9. Passo 8 — Emissão fiscal (NFS-e)

- **Intenção:** emitir a nota fiscal de serviço na prefeitura do laboratório.
- **Passo:** sistema dispara o envio à API da prefeitura. Aguarda confirmação. Se confirmado, vincula o número fiscal ao pedido e baixa automaticamente no contas a receber.
- **Falha tratada:** se a prefeitura devolver erro, o pedido entra em estado "esperando resubmissão fiscal" sem bloquear a entrega do certificado ao cliente. Notificação ao administrativo do laboratório.

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

## Regras transversais a todas as jornadas

- **Estado imutável:** pedido em "certificado emitido" ou adiante não pode ser editado — só reaberto via incidente registrado.
- **Audit log:** toda transição de status é registrada com usuário + data/hora + diferença.
- **Isolamento de tenant:** nenhuma jornada pode cruzar dados entre tenants.
- **Linguagem de erro:** mensagens ao usuário final seguem o glossário R12 (`glossary-pm.md`, item 1.5.6) — vocabulário de produto, nunca técnico.
- **Acessibilidade mínima:** teclado completo, contraste AA, foco visível. Alvo para detalhamento formal no Bloco 4.
