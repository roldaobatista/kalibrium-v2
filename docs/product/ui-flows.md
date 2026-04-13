# UI Flows — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** G.18
> **Dependencias:** `docs/product/journeys.md`, `docs/product/sitemap.md`, `docs/design/screen-inventory.md`, `docs/design/interaction-patterns.md`

---

## 1. Objetivo

Este documento traduz as jornadas de produto para fluxos de tela. Ele define origem, acao, destino, dados carregados e estados de erro principais.

Convencoes:
- `OS` significa Ordem de Servico;
- erro bloqueante mantem usuario na tela atual com banner e erro inline;
- erro recuperavel permite retry ou fila assincrona;
- telas imutaveis entram em modo leitura quando a entidade ja foi emitida, aprovada ou autorizada.

---

## 2. F01 — Pedido Novo ate OS em Fila

**Jornada base:** Jornada 1, passos 1.1 a 1.5

**Persona primaria:** Marcelo no papel administrativo/gerente
**Objetivo:** cadastrar ou reaproveitar cliente e instrumento, criar OS e colocar na fila tecnica.

```mermaid
flowchart TD
    A[/clientes] --> B[/clientes/novo]
    A --> C[/clientes/{cliente}]
    C --> D[/ordens-servico/nova]
    D --> E[/agenda]
    E --> F[/ordens-servico/{os}]
    F --> G[/fila-tecnica]
```

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/clientes` | Buscar CNPJ/CPF | `/clientes/{cliente}` ou `/clientes/novo` | documento fiscal | Documento invalido, cliente duplicado |
| 2 | `/clientes/novo` | Salvar cliente e contato | `/clientes/{cliente}` | Cliente, Contato, Consentimento | CNPJ invalido, e-mail invalido |
| 3 | `/clientes/{cliente}` | Criar OS para cliente | `/ordens-servico/nova?cliente={id}` | `cliente_id` como prefill de query string | Cliente sem contato operacional |
| 4 | `/ordens-servico/nova` | Selecionar instrumento | mesma tela | instrumento_id | Serie ja existe em outro cliente, alerta nao bloqueante |
| 5 | `/ordens-servico/nova` | Selecionar procedimento e prazo | mesma tela | procedimento_id, due_date | Procedimento vencido, dominio incompativel |
| 6 | `/ordens-servico/nova` | Salvar OS | `/ordens-servico/{os}` | OS criada | Falha de validacao, feature gate do plano |
| 7 | `/ordens-servico/{os}` | Agendar tecnico | `/agenda?os={id}` | `os_id` como prefill de query string, tecnico_id | Tecnico sem habilitacao, agenda indisponivel |
| 8 | `/agenda` | Confirmar alocacao | `/fila-tecnica` | os_id atribuido | Habilitacao vencida, conflito de horario |

Regras:
- query strings como `?cliente={id}` e `?os={id}` servem apenas para pre-preencher formulario; a autorizacao e a consistencia do tenant sempre sao validadas no servidor.

---

## 3. F02 — Execucao Tecnica na Bancada

**Jornada base:** Jornada 1, passo 1.6; Jornada 2

**Persona primaria:** Juliana
**Objetivo:** executar calibracao com minimo de interrupcao e bloqueios metrologicos corretos.

```mermaid
flowchart TD
    A[/fila-tecnica] --> B[/bancada]
    B --> C[/ordens-servico/{os}/calibracao]
    C --> D[/calibracoes/{calibracao}]
    D --> E[/ordens-servico/{os}/checklist]
    E --> F[/ordens-servico/{os}]
```

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/fila-tecnica` | Abrir OS atribuida | `/bancada?os={id}` | `os_id` como prefill de contexto | OS nao atribuida ao tecnico |
| 2 | `/bancada` | Conferir instrumento | `/ordens-servico/{os}/calibracao` | instrumento_id, procedimento_id | Instrumento divergente |
| 3 | `/ordens-servico/{os}/calibracao` | Criar execucao | `/calibracoes/{calibracao}` | calibracao_id | Procedimento vencido |
| 4 | `/calibracoes/{calibracao}` | Selecionar padroes | mesma tela | padroes_usados | Padrao vencido bloqueia submissao |
| 5 | `/calibracoes/{calibracao}` | Registrar ambiente | mesma tela | temperatura, umidade | Valor fora da faixa do procedimento |
| 6 | `/calibracoes/{calibracao}` | Lancar pontos medidos | mesma tela | leituras brutas | Campo numerico invalido |
| 7 | `/calibracoes/{calibracao}` | Submeter calculo | `/ordens-servico/{os}/checklist` | incerteza calculada | Componente de incerteza ausente |
| 8 | `/ordens-servico/{os}/checklist` | Concluir checklist | `/ordens-servico/{os}` | checklist completo | Item obrigatorio pendente |

Estados especiais:
- autosave permitido para leituras e observacoes longas;
- padrao vencido e bloqueio duro, com log de tentativa;
- perda de conexao entra em estado visual `offline pending` apenas para avisar que o envio ainda nao foi confirmado; fila local, edicao offline completa e sincronizacao automatica ficam fora do MVP conforme E05.

---

## 4. F03 — Revisao, Aprovacao e Certificado

**Jornada base:** Jornada 1, passos 1.7 e 1.8

**Persona primaria:** Marcelo
**Objetivo:** revisar a trilha tecnica, registrar dual sign-off quando exigido, aprovar ou devolver para retrabalho, emitir certificado numerado.

```mermaid
flowchart TD
    A[/notificacoes] --> B[/certificados/{certificado}/revisao]
    B --> C{Decisao}
    C -->|aprovar| D[/certificados/{certificado}/preview]
    C -->|retrabalho| E[/ordens-servico/{os}]
    D --> F[/certificados/{certificado}]
```

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/notificacoes` | Abrir aprovacao pendente | `/certificados/{certificado}/revisao` | certificado_id | Certificado ja revisado |
| 2 | `/certificados/{certificado}/revisao` | Conferir rastreabilidade | mesma tela | padroes, procedimento, ambiente | Padrao vencido no dia da execucao |
| 3 | `/certificados/{certificado}/revisao` | Solicitar retrabalho | `/ordens-servico/{os}` | comentario de retrabalho | Comentario obrigatorio ausente |
| 4 | `/certificados/{certificado}/revisao` | Registrar revisao tecnica | mesma tela | primeiro sign-off, usuario, timestamp | Permissao insuficiente, usuario executor tentando aprovar sozinho |
| 5 | `/certificados/{certificado}/revisao` | Confirmar emissao com segundo sign-off quando a politica exigir | `/certificados/{certificado}/preview` | segundo sign-off, usuario, timestamp | Mesmo usuario do primeiro sign-off, 2FA ausente |
| 6 | `/certificados/{certificado}/preview` | Emitir numero definitivo | `/certificados/{certificado}` | numero, hash, PDF | Falha de geracao PDF, colisao de numeracao |
| 7 | `/certificados/{certificado}` | Enviar ao cliente | mesma tela | evento `Certificado.emitido` | Fila de e-mail indisponivel, retry assincrono |

Regras:
- certificado emitido fica imutavel;
- revogacao usa `/certificados/{certificado}/revogar`;
- preview nao substitui aprovacao;
- dual sign-off exige duas decisoes rastreaveis quando a politica normativa/documental do tenant exigir, e o mesmo usuario nao pode preencher os dois papeis.

---

## 5. F04 — Fiscal e Contas a Receber

**Jornada base:** Jornada 1, passos 1.9 e fiscal/financeiro do MVP

**Persona primaria:** Marcelo no papel administrativo
**Objetivo:** emitir NFS-e, tratar rejeicao e baixar titulo a receber.

```mermaid
flowchart TD
    A[/certificados/{certificado}] --> B[/fiscal/notas/{nota}]
    B --> C{Status prefeitura}
    C -->|autorizada| D[/financeiro/titulos/{titulo}]
    C -->|rejeitada| E[/fiscal/notas/{nota}/reprocessar]
    E --> B
    D --> F[/financeiro/titulos/{titulo}/baixa]
    F --> G[/financeiro/conciliacao]
```

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/certificados/{certificado}` | Evento de emissao | `/fiscal/notas/{nota}` | certificado_id, cliente, servico | Dados fiscais incompletos |
| 2 | `/fiscal/notas/{nota}` | Transmitir NFS-e | mesma tela | XML, serie, numero | Prefeitura indisponivel |
| 3 | `/fiscal/notas/{nota}` | Resolver rejeicao | `/fiscal/notas/{nota}/reprocessar` | codigo de erro | Campo fiscal obrigatorio ausente |
| 4 | `/fiscal/notas/{nota}/reprocessar` | Reenviar | `/fiscal/notas/{nota}` | dados corrigidos | Rejeicao repetida |
| 5 | `/fiscal/notas/{nota}` | Autorizacao recebida | `/financeiro/titulos/{titulo}` | titulo criado | Evento duplicado ignorado |
| 6 | `/financeiro/titulos/{titulo}` | Registrar pagamento | `/financeiro/titulos/{titulo}/baixa` | valor, data, forma | Valor maior que saldo |
| 7 | `/financeiro/titulos/{titulo}/baixa` | Confirmar baixa | `/financeiro/conciliacao` | baixa auditada | Permissao insuficiente |

Regras:
- rejeicao fiscal nao bloqueia download do certificado ja aprovado;
- baixa manual sempre registra usuario, data e forma de pagamento;
- exportacao contabil usa filtros ativos.

---

## 6. F05 — Portal do Cliente Final

**Jornada base:** Jornada 1, passos 1.10 e 1.11; Jornada 3

**Persona primaria:** Rafael
**Objetivo:** baixar certificado e consultar historico sem contato manual com o laboratorio.

```mermaid
flowchart TD
    A[Link por e-mail] --> B[/portal/certificados/{certificado}]
    C[/portal/login] --> D[/portal]
    D --> E[/portal/certificados]
    D --> F[/portal/instrumentos]
    E --> B
    F --> G[/portal/instrumentos/{instrumento}]
```

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | E-mail transacional | Abrir link assinado | `/portal/certificados/{certificado}` | token assinado | Token expirado |
| 2 | `/portal/login` | Autenticar usuario externo | `/portal` | usuario externo | Senha invalida |
| 3 | `/portal` | Ver certificados recentes | `/portal/certificados` | cliente_id | Sem certificados no historico |
| 4 | `/portal/certificados` | Abrir certificado | `/portal/certificados/{certificado}` | certificado_id | Certificado fora do escopo do cliente |
| 5 | `/portal/certificados/{certificado}` | Baixar PDF | mesma tela | arquivo PDF | Arquivo indisponivel, retry |
| 6 | `/portal` | Consultar instrumentos | `/portal/instrumentos` | cliente_id | Sem instrumentos cadastrados |
| 7 | `/portal/instrumentos` | Ver historico | `/portal/instrumentos/{instrumento}` | instrumento_id | Instrumento de outro cliente |

Regras:
- Rafael so enxerga dados do CNPJ/CPF vinculado ao seu contato;
- link assinado e para consulta pontual e deve ser auditado;
- portal usa linguagem menos tecnica que back-office.

---

## 7. F06 — Administracao do Tenant e RBAC

**Jornada base:** Jornada 5

**Persona primaria:** Marcelo
**Objetivo:** configurar usuarios, papeis, 2FA, plano e consentimentos sem suporte manual.

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/settings/tenant` | Atualizar dados do laboratorio | mesma tela | Tenant, Empresa, Filial | CNPJ invalido |
| 2 | `/settings/users` | Convidar usuario | mesma tela | e-mail, papel, empresa | E-mail ja existe no tenant |
| 3 | `/settings/users` | Alterar papel | mesma tela | usuario_id, role | Usuario tentando remover o proprio acesso gerente |
| 4 | `/settings/plans` | Ver limite de uso | mesma tela | assinatura, entitlements | Plano suspenso em modo somente leitura |
| 5 | `/settings/privacy` | Registrar base legal | mesma tela | categoria, base legal | Categoria obrigatoria sem base |
| 6 | `/settings/notificacoes` | Ajustar canal | mesma tela | template, canal | WhatsApp sem consentimento |

Regras:
- tecnico nao acessa rotas administrativas;
- gerente e administrativo exigem 2FA;
- suspensao de tenant preserva leitura e bloqueia criacao de OS/certificados.

---

## 8. F07 — GED e Auditoria RBC

**Jornada base:** Jornada 4

**Persona primaria:** Marcelo; visualizador/auditor como leitura controlada
**Objetivo:** recuperar trilha documental de uma calibracao.

| Passo | Origem | Acao | Destino | Dados transmitidos | Erros principais |
|---|---|---|---|---|---|
| 1 | `/certificados` | Buscar certificado sorteado | `/certificados/{certificado}` | numero, cliente, periodo | Certificado nao encontrado |
| 2 | `/certificados/{certificado}` | Abrir trilha tecnica | `/ordens-servico/{os}` | os_id | Acesso negado por role |
| 3 | `/ordens-servico/{os}` | Abrir documento vinculado | `/documentos/{documento}` | documento_id | Documento confidencial |
| 4 | `/documentos/{documento}` | Baixar evidencia | mesma tela | arquivo e log de acesso | Arquivo ausente no storage |
| 5 | `/documentos/{documento}` | Conferir log do download sensivel | mesma tela | evento de auditoria | Log indisponivel ou permissao insuficiente |
| 6 | `/relatorios` | Exportar pacote de auditoria | mesma tela | certificado, OS, padroes | Exportacao parcial com aviso |

Regras:
- todo download de documento sensivel registra log de acesso;
- auditoria nunca permite edicao de dados tecnicos;
- pacote de auditoria usa PDF/CSV conforme `docs/design/print-patterns.md`.

---

## 9. F08 — Notificacao e Comunicacao

**Jornada base:** Jornada 1, eventos transversais

**Persona primaria:** Marcelo, Juliana, Rafael
**Objetivo:** transformar eventos de dominio em notificacoes in-app, e-mail e WhatsApp consentido.

| Evento | Origem | Destino UI | Canal | Erro tratado |
|---|---|---|---|---|
| OS criada | `/ordens-servico/nova` | `/notificacoes` | in-app, e-mail | fila indisponivel com retry |
| OS atribuida ao tecnico | `/agenda` | `/fila-tecnica` | in-app | tecnico sem role ativa |
| Calibracao aguardando aprovacao | `/calibracoes/{calibracao}` | `/certificados/{certificado}/revisao` | in-app | gerente sem 2FA ativo |
| Certificado emitido | `/certificados/{certificado}` | `/portal/certificados/{certificado}` | portal + e-mail transacional; WhatsApp somente com consentimento | link expirado |
| NFS-e rejeitada | `/fiscal/notas/{nota}` | `/fiscal/notas/{nota}/reprocessar` | in-app | erro fiscal sem mapeamento |
| Titulo vencido | job agendado | `/financeiro/titulos/{titulo}` | in-app | cliente sem contato financeiro |
| Padrao vencendo | job agendado | `/padroes/{padrao}` | in-app | padrao ja inativo |

Regras:
- comunicacao transacional ignora opt-out de marketing;
- WhatsApp so dispara com consentimento por contato;
- log de comunicacao fica consultavel em `/settings/comunicacao/log`.
