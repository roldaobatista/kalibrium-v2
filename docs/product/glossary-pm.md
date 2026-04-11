# Glossário PM — dicionário canônico positivo (R12)

> **Status:** ativo. Item 1.5.6 do plano da meta-auditoria #2. Fonte autoritativa para toda tradução técnica→produto que aparece em relatórios destinados ao Product Manager. Regra R12 da `docs/constitution.md §4`.

Este dicionário tem um propósito único: **mapear termos técnicos recorrentes para uma versão em linguagem de produto que o PM, que não é desenvolvedor, consiga entender sem abrir documentação externa.** Quando um agente do harness precisa comunicar um resultado ao PM (via `/explain-slice`, `/decide-stack`, relatório de slice, incident file), ele deve substituir o lado esquerdo pelo lado direito desta tabela.

**Como usar:**
- **Ordem alfabética** pelo termo técnico (coluna esquerda).
- **Tradução** na coluna direita é **uma frase curta**, não parágrafo. Deve caber em uma linha de relatório.
- **Se o termo técnico não está aqui, o agente deve ou pedir para adicionar, ou evitar o termo por completo.**
- Este arquivo é consultado pelo hook `check-r12-vocabulary.sh` (item 4.7 do plano) quando ele existir — então linha mal formatada pode quebrar o hook.

---

## Tabela (≥ 40 pares, ordem alfabética)

| Termo técnico | Tradução para o PM |
|---|---|
| acceptance criterion (AC) | critério que o produto precisa cumprir para a tela estar pronta |
| ADR (architecture decision record) | registro de uma decisão importante sobre como o produto é feito |
| ambiente de dev | cópia do produto usada só para experimentar novas funcionalidades |
| ambiente de produção | cópia do produto que os clientes de verdade usam |
| ambiente de staging | cópia intermediária onde funcionalidades novas são testadas antes de virar produção |
| backend | parte do produto que fica no servidor e faz as contas |
| branch | uma versão paralela do produto onde uma nova funcionalidade está sendo construída |
| build | compilar o produto em um pacote pronto para instalar |
| cache | memória temporária que guarda respostas repetidas para responder mais rápido |
| callback | função que outra função chama depois de terminar um trabalho |
| CI (integração contínua) | robô que testa toda alteração do produto automaticamente antes dela virar parte dele |
| commit | registro único de uma alteração feita no produto |
| commit atômico | registro único com um propósito claro, nem grande demais nem misturando coisas |
| cron | robô que executa uma tarefa em horário fixo |
| dashboard | tela de painel com os indicadores mais importantes |
| dependency (dependência) | biblioteca externa que o produto usa para não precisar reinventar algo pronto |
| deploy | ato de colocar uma nova versão do produto no ar para os clientes |
| diff | comparação entre duas versões de um arquivo mostrando o que mudou |
| downtime | tempo em que o produto ficou fora do ar |
| endpoint | ponto de acesso que outra parte do sistema usa para pedir dados |
| failover | ato de trocar para um plano B quando o plano A quebra |
| feature flag | chave liga-desliga de funcionalidade que permite ativar para alguns clientes antes de todos |
| fixture (dados de teste) | dados falsos que os testes usam para simular situações |
| frontend | parte do produto que o cliente vê e usa na tela |
| gate | verificação automática que bloqueia uma alteração se algum critério não foi cumprido |
| hash (soma de verificação) | número fixo que identifica um conteúdo — se o conteúdo muda, o número muda |
| hook | script do harness que é disparado automaticamente por um evento |
| HTTP 4xx | erro de quem pediu (usuário enviou algo inválido) |
| HTTP 5xx | erro do próprio produto (servidor caiu ou tem bug) |
| index (banco de dados) | atalho que o banco usa para achar registros mais rápido |
| interface | o jeito que uma parte do produto conversa com outra parte |
| join (banco de dados) | operação que junta dois conjuntos de dados relacionados entre si |
| latência | quanto tempo o produto leva para responder uma ação do usuário |
| log | registro escrito do que o produto fez em ordem cronológica |
| merge | ato de juntar duas versões paralelas do produto em uma só |
| middleware | camada intermediária que intercepta pedidos antes de chegarem ao destino |
| migration | atualização da estrutura do banco de dados |
| mock (teste) | imitação falsa de uma parte do produto usada só em teste |
| monorepo | repositório único com várias partes do produto juntas |
| multi-tenant | mesmo produto servindo vários clientes com dados isolados entre eles |
| OAuth | protocolo padrão para fazer login usando uma conta externa (Google, Microsoft) |
| observability (observabilidade) | conjunto de sinais que mostra como o produto está se comportando |
| p95 (percentil 95) | tempo de resposta que 95 de cada 100 pedidos conseguem bater |
| PR (pull request) | proposta de alteração do produto que precisa ser revisada antes de entrar |
| prompt injection | tentativa de enganar o agente de IA fazendo ele acreditar que o texto do usuário é instrução |
| queue (fila) | lista de tarefas esperando para serem processadas uma por uma |
| rate limit | teto de quantos pedidos um cliente pode fazer por minuto |
| rebase | reorganizar a história de alterações sobre uma base mais nova |
| refactor | reescrever código sem mudar o que ele faz por fora |
| regression test | teste que garante que um bug antigo não voltou |
| replica (réplica) | cópia sincronizada do banco que serve leituras |
| repository (repositório) | pasta versionada onde o produto fica guardado |
| RLS (row-level security) | mecanismo do banco que impede um cliente de ler dados de outro cliente |
| rollback | ato de voltar para uma versão anterior porque a nova deu problema |
| schema | desenho da estrutura de uma tabela ou de um pedaço de dados |
| SDK | kit pronto que facilita usar um serviço externo |
| secret (segredo) | valor sensível como senha ou chave de API que nunca pode aparecer no código |
| sharding | dividir o banco em pedaços para cada pedaço caber em uma máquina |
| slice | unidade vertical de entrega do produto, uma tela ou funcionalidade fim a fim |
| SLA | promessa de tempo mínimo de disponibilidade feita ao cliente |
| SLO | alvo interno de qualidade que o produto tenta bater |
| snapshot (banco de dados) | foto do banco guardada em um momento específico para poder voltar nela |
| stack | conjunto de tecnologias escolhidas para fazer o produto |
| subquery | consulta menor dentro de outra consulta maior ao banco |
| SQL | linguagem para conversar com o banco de dados |
| TDD (test-driven development) | escrever o teste antes do código que faz o teste passar |
| tenant | cada cliente isolado dentro do produto multi-tenant |
| throttling | freio intencional para evitar sobrecarga |
| timeout | tempo máximo que o produto espera por uma resposta antes de desistir |
| token (agente de IA) | unidade básica de texto que o agente conta ao ler ou escrever |
| transaction (banco de dados) | conjunto de alterações que acontece tudo ou nada, nunca pela metade |
| type check | verificação automática de que cada dado tem o formato certo |
| unit test | teste isolado de um pedaço pequeno do produto |
| user agent | identificação do navegador ou app que está fazendo o pedido |
| validation (validação) | verificação de que um dado recebido é aceitável antes de salvar |
| webhook | aviso automático que o produto envia a outro sistema quando algo acontece |
| worktree descartável | pasta de trabalho temporária que o sub-agente usa e depois é apagada |

---

## Exceções explícitas

Termos que são deliberadamente mantidos em inglês mesmo em relatório de PM, porque são nome próprio ou viraram universalmente conhecidos:

- **API** — mantém, é acrônimo universal.
- **PDF** — mantém, todo mundo sabe.
- **URL / link** — mantém.
- **e-mail** — mantém.
- **WhatsApp** — nome próprio.
- **login / logout** — entraram no português coloquial.
- **backup** — mesmo motivo.
