# LGPD — base legal por finalidade — Kalibrium

> **Status:** `draft-awaiting-dpo`. Item T2.2 da Trilha #2. Matriz finalidade × base legal × titular × papel conforme Art. 6, 7 e 11 da Lei 13.709/2018. Revisão formal pelo DPO obrigatória antes do primeiro tenant real.

## Papéis no tratamento (Art. 5, VI-IX da LGPD)

- **Controlador:** o laboratório cliente do Kalibrium (decide finalidade e meios do tratamento dos dados dos próprios clientes).
- **Operador:** Kalibrium Tecnologia (trata os dados em nome do controlador).
- **Titular:** a pessoa natural a quem os dados pessoais se referem. No contexto do Kalibrium os titulares típicos são: colaborador do laboratório, contato do cliente (pessoa do comprador industrial), eventualmente responsável legal de microempresa.
- **Encarregado (DPO):** pessoa nomeada pelo controlador para comunicação com a ANPD.

Em cenários específicos onde o Kalibrium decide a finalidade de tratamento próprio (por exemplo, analytics internos do próprio produto), o Kalibrium figura como controlador. Esses casos são minimizados a zero dentro do MVP.

## Matriz principal

Mínimo 6 finalidades mapeadas (requisito do critério T2.2). 9 entradas abaixo.

| # | Finalidade | Categorias de dado | Titular | Papel do Kalibrium | Base legal (Art. 7) | Retenção |
|---|---|---|---|---|---|---|
| 1 | Cadastro de cliente do laboratório | Razão social, CNPJ, endereço, nome contato, e-mail corporativo, telefone | Pessoa de contato do cliente | Operador | II — execução de contrato (laboratório ↔ cliente) + V — legítimo interesse para manter relacionamento comercial | 5 anos após último serviço |
| 2 | Cadastro de colaborador do laboratório | Nome, e-mail corporativo, papel operacional | Colaborador do laboratório | Operador | II — execução de contrato de trabalho + IX — legítimo interesse do laboratório | Enquanto ativo + 5 anos |
| 3 | Execução técnica da calibração | Dados do instrumento, condições ambientais, resultados, identificação do técnico | Colaborador (técnico) | Operador | II — execução de contrato + VI — exercício regular de direito (obrigação da RBC) | 10 anos (RNF-009) |
| 4 | Emissão de certificado de calibração | Dados do cliente, instrumento, padrões, resultado, incerteza | Contato do cliente + colaborador | Operador | II — execução de contrato + III — obrigação legal (ISO 17025, RBC) | 10 anos |
| 5 | Emissão fiscal (NFS-e) | CNPJ/CPF do cliente, valor, descrição do serviço | Contato do cliente (representante legal) | Operador | III — obrigação legal (tributária) | Prazo legal tributário (5-10 anos conforme tributo) |
| 6 | Cobrança e contas a receber | CNPJ/CPF, valor, prazo | Contato do cliente | Operador | II — execução de contrato + III — obrigação legal | Prazo legal contábil |
| 7 | Portal do cliente final (acesso a histórico de certificados) | E-mail, senha, histórico de consulta | Contato do cliente | Operador | II — execução de contrato + I — consentimento para notificações opcionais (WhatsApp) | Enquanto cliente ativo + 5 anos |
| 8 | Suporte ao usuário (laboratório e cliente final) | E-mail, histórico de atendimento | Usuário que abriu chamado | Operador | V — legítimo interesse para atendimento | 2 anos após fechamento do chamado |
| 9 | Auditoria e rastreabilidade (logs de acesso) | user_id, timestamp, ação, tenant_id | Colaborador ou contato do cliente | Operador | VI — exercício regular de direito (defesa em auditoria e processo) | 10 anos |

## Finalidades fora do MVP (não tratadas)

- Marketing ativo → não é tratado no MVP. Se entrar, base legal = consentimento (Art. 7, I).
- Perfilamento para personalização → não é tratado.
- Comercialização de dados agregados → proibido.
- Transferência internacional → não ocorre. Dados residem no Brasil (constraint de foundation §5).

## Direitos do titular (Art. 18)

O canal de exercício de direitos é operado pelo controlador (laboratório) via um e-mail monitorado. O Kalibrium fornece ferramentas ao laboratório para:

- Confirmação e acesso (exportação CSV do que há do titular).
- Correção (edição dos campos autorizada ao papel administrativo).
- Exclusão (marcação para apagamento, respeitada pelo prazo legal — alguns dados não podem ser apagados por obrigação legal da RBC).
- Portabilidade (exportação em formato estruturado, JSON ou CSV).
- Anonimização (campo livre que pode ser sobrescrito com hash).
- Revisão de decisão automatizada — não aplicável no MVP (o produto não faz decisão automatizada sobre titular).

## Minimização (Art. 6, III)

- Nenhum campo de dado pessoal é coletado sem finalidade correspondente na tabela.
- Nenhum campo de dado pessoal sensível é coletado.
- CPF do representante legal só é coletado quando a pessoa natural é a própria responsável pela NF-e (microempresa sem CNPJ, cenário raro).

## Pendências que dependem do DPO

- Validação jurídica da escolha de base legal para cada finalidade.
- Definição do prazo exato de retenção por categoria conforme norma municipal/federal vigente.
- Redação do texto visível ao titular no portal do cliente final (aviso de privacidade).
- Decisão sobre consentimento granular para notificações por WhatsApp.
