# Persona Scenarios — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** G.19
> **Dependencias:** `docs/product/personas.md`, `docs/product/journeys.md`, `docs/product/sitemap.md`, `docs/design/screen-inventory.md`

---

## 1. Objetivo

Este documento detalha cenarios de uso por persona para guiar wireframes, testes funcionais e revisao PM. Ele e mais granular que `docs/product/journeys.md` e menos tecnico que os acceptance tests por story.

Formato:
- **Pre-condicao:** estado necessario antes do cenario;
- **Passos:** sequencia observavel na UI;
- **Resultado esperado:** estado final do produto;
- **Risco coberto:** falha operacional que o cenario protege.

---

## 2. Marcelo — Gerente do Laboratorio

### M01 — Criar pedido novo sem duplicar cliente

**Pre-condicao:** Marcelo esta autenticado como `gerente` ou `administrativo`; o cliente pode ou nao existir.

**Passos:**
1. Abrir `/clientes`.
2. Buscar CNPJ/CPF recebido por e-mail ou telefone.
3. Se o cliente existir, abrir `/clientes/{cliente}`.
4. Se o cliente nao existir, abrir `/clientes/novo`, preencher cliente e contato, salvar.
5. Clicar em criar OS e abrir `/ordens-servico/nova`.
6. Selecionar instrumento, procedimento e prazo acordado.
7. Salvar OS e confirmar entrada na fila.

**Resultado esperado:** OS criada com cliente, instrumento, procedimento, prazo e status inicial auditado.

**Risco coberto:** duplicidade de cadastro e retrabalho administrativo.

### M02 — Redistribuir fila sem alocar tecnico sem habilitacao

**Pre-condicao:** existem OS em fila, tecnicos cadastrados e habilitacoes com validade.

**Passos:**
1. Abrir `/agenda` ou `/app/dashboard`.
2. Identificar OS atrasada ou em risco.
3. Mover OS para outro tecnico disponivel.
4. Ler alerta se o tecnico nao possuir habilitacao vigente.
5. Escolher tecnico valido ou registrar excecao justificada quando permitido.

**Resultado esperado:** OS redistribuida apenas quando a habilitacao for valida ou quando excecao autorizada for registrada.

**Risco coberto:** calibracao atribuida a tecnico sem aptidao rastreavel.

### M03 — Aprovar certificado com trilha completa

**Pre-condicao:** Juliana concluiu a calibracao e o certificado esta em revisao.

**Passos:**
1. Abrir notificacao de aprovacao em `/notificacoes`.
2. Abrir `/certificados/{certificado}/revisao`.
3. Conferir instrumento, procedimento, padroes, validade dos padroes, ambiente e calculo de incerteza.
4. Abrir preview do PDF.
5. Aprovar emissao ou devolver para retrabalho com comentario.

**Resultado esperado:** certificado aprovado gera numero definitivo, PDF, hash e evento de entrega.

**Risco coberto:** certificado emitido sem revisao tecnica suficiente.

### M04 — Tratar rejeicao de NFS-e sem travar certificado

**Pre-condicao:** certificado foi emitido e a prefeitura retornou rejeicao fiscal.

**Passos:**
1. Abrir alerta em `/fiscal/notas`.
2. Abrir `/fiscal/notas/{nota}`.
3. Ler codigo de rejeicao e campos envolvidos.
4. Abrir `/fiscal/notas/{nota}/reprocessar`.
5. Corrigir dados permitidos e reenviar.
6. Confirmar que o certificado segue disponivel ao cliente.

**Resultado esperado:** nota fiscal reprocessada; certificado continua consultavel; erro fiscal fica auditado.

**Risco coberto:** falha fiscal bloquear entrega operacional ao cliente.

### M05 — Consultar indicadores sem planilha externa

**Pre-condicao:** existem OS em diferentes status no mes atual.

**Passos:**
1. Abrir `/app/dashboard`.
2. Ver pedidos atrasados, aguardando aprovacao e fila por tecnico.
3. Filtrar por periodo e dominio metrologico em `/relatorios`.
4. Exportar relatorio ou CSV quando necessario.

**Resultado esperado:** Marcelo consegue responder sobre prazo, retrabalho e fila sem montar planilha manual.

**Risco coberto:** decisao operacional baseada em memoria humana ou controles paralelos.

### M06 — Preparar trilha de auditoria RBC

**Pre-condicao:** auditor solicita um certificado emitido nos ultimos 12 meses.

**Passos:**
1. Buscar certificado em `/certificados`.
2. Abrir detalhe do certificado.
3. Navegar para OS, calibracao, padroes usados e documentos vinculados.
4. Baixar evidencias e relatorio consolidado em `/relatorios`.
5. Conferir log de acesso aos documentos sensiveis.

**Resultado esperado:** trilha instrumento -> procedimento -> padrao -> calibracao -> certificado fica acessivel em poucos cliques.

**Risco coberto:** auditoria depender de pasta compartilhada, memoria ou planilha.

---

## 3. Juliana — Tecnica Calibradora

### J01 — Executar OS atribuida no tablet de bancada

**Pre-condicao:** Juliana esta autenticada como `tecnico`; ha OS atribuida em fila.

**Passos:**
1. Abrir `/fila-tecnica`.
2. Selecionar OS por prioridade ou prazo.
3. Abrir `/bancada`.
4. Conferir identificacao do instrumento.
5. Iniciar calibracao.

**Resultado esperado:** Juliana entra na tela de execucao com campos grandes, unidade visivel e contexto da OS.

**Risco coberto:** interrupcao de contexto por interface densa de escritorio.

### J02 — Bloquear uso de padrao vencido

**Pre-condicao:** existe um padrao de referencia vencido ou vencendo, vinculado ao dominio metrologico.

**Passos:**
1. Abrir `/calibracoes/{calibracao}`.
2. Tentar selecionar padrao vencido.
3. Ler mensagem de bloqueio.
4. Selecionar padrao vigente ou pedir orientacao ao gerente.

**Resultado esperado:** padrao vencido nao pode ser usado; tentativa fica registrada em log.

**Risco coberto:** rastreabilidade metrologica invalida.

### J03 — Lancar pontos medidos com validacao numerica

**Pre-condicao:** calibracao criada e procedimento vigente carregado.

**Passos:**
1. Abrir tabela de pontos medidos.
2. Preencher temperatura e umidade.
3. Preencher leituras por ponto.
4. Receber validacao inline para valor fora da faixa ou formato invalido.
5. Corrigir valor e salvar.

**Resultado esperado:** dados tecnicos ficam salvos com validacao server-side e feedback local.

**Risco coberto:** erro de digitacao virar certificado incorreto.

### J04 — Continuar apos pausa operacional

**Pre-condicao:** Juliana iniciou uma calibracao longa e precisa interromper por motivo de bancada.

**Passos:**
1. Preencher parte dos pontos medidos.
2. Observar estado de autosave permitido.
3. Sair para `/fila-tecnica`.
4. Reabrir a mesma OS.
5. Confirmar que os dados salvos permanecem.

**Resultado esperado:** progresso parcial recuperavel sem transformar autosave em aprovacao final.

**Risco coberto:** perda de anotacoes parciais e retorno ao papel.

### J05 — Submeter calibracao para aprovacao

**Pre-condicao:** pontos medidos, ambiente, padroes e checklist estao completos.

**Passos:**
1. Abrir `/ordens-servico/{os}/checklist`.
2. Conferir itens obrigatorios.
3. Submeter para revisao.
4. Ler confirmacao de envio para Marcelo.

**Resultado esperado:** OS muda para aguardando aprovacao; Marcelo recebe notificacao.

**Risco coberto:** calibracao ficar concluida tecnicamente, mas parada sem handoff.

### J06 — Consultar procedimento antes de medir

**Pre-condicao:** OS aponta para procedimento tecnico versionado.

**Passos:**
1. Abrir OS na bancada.
2. Abrir detalhe do procedimento vinculado.
3. Conferir versao, validade e documento.
4. Voltar para a execucao.

**Resultado esperado:** Juliana consulta metodo vigente sem sair do fluxo de trabalho.

**Risco coberto:** uso de procedimento antigo ou planilha paralela.

---

## 4. Rafael — Cliente Final

### R01 — Baixar certificado por link assinado

**Pre-condicao:** certificado foi emitido e Rafael recebeu e-mail transacional.

**Passos:**
1. Clicar no link do e-mail.
2. Abrir `/portal/certificados/{certificado}`.
3. Conferir dados principais do certificado.
4. Baixar PDF.

**Resultado esperado:** Rafael baixa certificado sem depender de chamada telefonica ao laboratorio.

**Risco coberto:** atraso na entrega documental depois da calibracao.

### R02 — Consultar historico do CNPJ

**Pre-condicao:** Rafael tem usuario externo ativo vinculado ao cliente.

**Passos:**
1. Abrir `/portal/login`.
2. Entrar com e-mail e senha.
3. Abrir `/portal/certificados`.
4. Filtrar por instrumento, validade ou periodo.
5. Abrir certificado antigo.

**Resultado esperado:** Rafael consulta historico do proprio CNPJ com isolamento de dados.

**Risco coberto:** vazamento de certificado entre clientes ou dependencia de e-mail antigo.

### R03 — Ver instrumentos vencendo

**Pre-condicao:** existem instrumentos com validade proxima no historico do cliente.

**Passos:**
1. Abrir `/portal`.
2. Ler alerta de vencimento.
3. Abrir `/portal/instrumentos`.
4. Abrir detalhe do instrumento.

**Resultado esperado:** Rafael identifica itens que precisam de nova calibracao antes da auditoria do cliente dele.

**Risco coberto:** perda de recorrencia por falta de visibilidade.

### R04 — Recuperar acesso sem acionar o laboratorio

**Pre-condicao:** Rafael esqueceu a senha do portal.

**Passos:**
1. Abrir `/portal/login`.
2. Acionar recuperacao de senha.
3. Receber e-mail transacional.
4. Redefinir senha.
5. Acessar historico.

**Resultado esperado:** acesso recuperado sem trabalho manual do administrativo do laboratorio.

**Risco coberto:** portal virar mais um canal de suporte improdutivo.

### R05 — Link expirado com caminho de recuperacao

**Pre-condicao:** Rafael abriu um link assinado depois da expiracao.

**Passos:**
1. Abrir link expirado.
2. Ler mensagem objetiva.
3. Escolher entrar no portal ou solicitar novo link.
4. Acessar certificado pelo portal autenticado.

**Resultado esperado:** erro de link expirado nao bloqueia consulta quando Rafael tem acesso valido.

**Risco coberto:** falha de experiencia por link antigo encaminhado internamente.

---

## 5. Personas de Leitura Controlada

### A01 — Auditor externo consulta pacote sem editar

**Pre-condicao:** gerente concedeu acesso de leitura conforme politica do tenant.

**Passos:**
1. Abrir link ou sessao de leitura controlada.
2. Consultar certificado, OS, padroes e documentos vinculados.
3. Baixar pacote de auditoria permitido.

**Resultado esperado:** auditor acessa evidencias sem editar dados nem navegar fora do escopo.

**Risco coberto:** acesso excessivo para leitura esporadica.

### S01 — Suporte Kalibrium verifica status do tenant

**Pre-condicao:** usuario interno autorizado tem justificativa registrada.

**Passos:**
1. Abrir `/admin/tenants`.
2. Localizar tenant por identificador operacional.
3. Ver status, plano e eventos de suporte.
4. Registrar acao em `/admin/support-audit`.

**Resultado esperado:** suporte verifica saude do tenant sem consultar dados sensiveis de calibracao.

**Risco coberto:** suporte virar bypass de isolamento de tenant.

---

## 6. Checklist para Wireframes

| Pergunta | Regra |
|---|---|
| O wireframe cobre ao menos um cenario deste documento? | Obrigatorio para telas de jornada critica |
| O cenario tem pre-condicao e resultado esperado? | Obrigatorio |
| Erros e bloqueios principais aparecem na UI? | Obrigatorio |
| O fluxo respeita RBAC por tela? | Obrigatorio |
| O fluxo evita planilha, papel ou portal externo no caminho critico? | Obrigatorio no MVP |
