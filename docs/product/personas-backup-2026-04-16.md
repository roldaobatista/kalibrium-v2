# Personas do Kalibrium

> **Status:** ativo. Item 1.5.3 do plano da meta-auditoria #2 (Bloco 1.5 Nível 2). Depende de `mvp-scope.md` (1.5.2) para o recorte de laboratório atendido. Três personas primárias — se aparecer uma quarta em pesquisa de campo, precisa passar por discovery antes de entrar aqui.

## Persona 1 — Gerente do laboratório (Marcelo, 48 anos)

**Contexto profissional.** Marcelo é formado em Engenharia Mecânica, tem especialização em metrologia pelo próprio Inmetro, trabalhou 12 anos como técnico em um laboratório maior antes de abrir o próprio. Hoje é sócio-gerente de um laboratório acreditado com 5 técnicos, 2 administrativos e cerca de 1.400 calibrações/mês no pico. Ele é o responsável técnico perante a Cgcre, é quem assina os certificados acreditados e é quem recebe a auditoria anual. Também é quem fecha a venda dos contratos com clientes grandes, negocia prazo e responde por inadimplência. Conhece Excel profundamente, conhece o portal da prefeitura de memória, e tem uma relação de amor-ódio com o software legado que o laboratório usa há 8 anos.

**Dor principal.** O tempo que Marcelo gasta "apagando incêndio administrativo" — caçando o número de série de um padrão no livro antigo, redigitando cadastro porque o software legado perdeu, cruzando planilha com portal fiscal, explicando ao cliente por que o certificado atrasou. Ele sente que trabalha como administrativo quando deveria trabalhar como técnico e como vendedor. Cada hora de "apagar incêndio" é uma hora que ele não está calibrando, não está atendendo cliente novo e não está revisando procedimento. Em um mês ruim, essa conta chega a 40% do tempo dele.

**Como o Kalibrium resolve.** Transforma a operação do laboratório em um fluxo único onde o dado é digitado uma vez, a trilha de rastreabilidade é automática e o certificado sai direto no portal do cliente. Marcelo deixa de ser "coordenador de planilha" e volta a ser engenheiro metrólogo. O Kalibrium também entrega, como efeito colateral, os indicadores que ele não tinha tempo de montar manualmente — tempo médio por calibração, pedidos atrasados, aderência ao prazo — permitindo conversas mais concretas com os técnicos e com os clientes.

**Objeção mais comum.** "Eu já paguei por um software desses e ele quebrou." Marcelo tem cicatriz de implantação que deu errado. A venda precisa responder por que o Kalibrium não é só mais um — a resposta do produto é dar um caminho reversível (exportação CSV em qualquer momento, dados dele, sempre dele) e começar pelo módulo de metrologia, não pelo fiscal, que é onde o legado normalmente falhou.

## Persona 2 — Técnico calibrador (Juliana, 32 anos)

**Contexto profissional.** Juliana é técnica em mecânica com formação complementar em metrologia. Está há 6 anos no laboratório. Calibra instrumentos dimensionais e de pressão. É a pessoa que mais domina o orçamento de incerteza entre os técnicos, e virou referência informal quando alguém tem dúvida no cálculo. Usa a planilha de incerteza herdada do antigo responsável técnico, versão que ela mesma modificou três vezes para cobrir casos que não estavam previstos. Trabalha na bancada, não no escritório. Odeia interromper uma calibração para fazer lançamento em sistema.

**Dor principal.** "Interrupção de contexto." Quando Juliana está na bancada medindo um bloco padrão, pedir que ela saia, vá ao computador do escritório, faça login no software legado, procure o pedido, digite os valores, salve, volte — destrói o ritmo da calibração. Ela prefere anotar em papel, terminar o lote inteiro e depois digitar tudo de uma vez. O problema é que o papel às vezes some, o lote demora a ser digitado, e cada atraso de lançamento é um atraso no certificado, que é um atraso na cobrança. Marcelo (gerente) sabe e reclama.

**Como o Kalibrium resolve.** Interface de bancada com menos fricção possível — tablet ou celular, login rápido, lançamento dos valores campo-a-campo com teclado numérico grande, captura de condições ambientais (temperatura, umidade) com um toque, seleção do padrão usado em uma lista já filtrada pelos padrões vigentes. A calibração é lançada em tempo real, sem interromper a cadência. O orçamento de incerteza é pré-carregado do procedimento vigente e Juliana só confere — ela não precisa abrir outra planilha.

**Objeção mais comum.** "Minha planilha de incerteza faz coisas que o sistema de vocês não vai fazer." Juliana já customizou demais o cálculo para acreditar que vai caber em qualquer produto. A resposta real é deixar o MVP aceitar versão específica por procedimento, versionar as planilhas como parte do procedimento e garantir que quando a planilha é atualizada, todas as calibrações futuras usam a nova versão sem perder as antigas. Se for preciso, o Kalibrium consome a planilha dela literalmente até o momento em que ela mesma decidir migrar o cálculo para dentro do sistema.

## Persona 3 — Cliente final do laboratório (Rafael, 40 anos, comprador industrial)

**Contexto profissional.** Rafael é comprador técnico numa fábrica de autopeças de médio porte no interior de São Paulo. Entre muitas outras responsabilidades, ele é quem organiza o envio semestral de instrumentos da linha de produção para calibração externa. Conhece vários laboratórios, compara preço, compara prazo. Não é metrólogo — para ele, o certificado é um documento que tem que chegar no prazo, ser aceito pela auditoria da montadora para quem ele fornece peça, e vir acompanhado de nota fiscal correta. Ele nunca acessa o laboratório pessoalmente. A relação é por e-mail e telefone.

**Dor principal.** Visibilidade zero entre o momento em que ele despacha os instrumentos e o momento em que os certificados chegam. Se a montadora pedir prova de calibração de um instrumento que foi calibrado há 9 meses, Rafael tem que ligar no laboratório, esperar a secretária achar, receber por e-mail, salvar numa pasta e mandar para o auditor. Em um ano ele faz isso 15 ou 20 vezes. Ele perde confiança no laboratório quando a resposta demora mais do que um dia útil.

**Como o Kalibrium resolve.** Portal do cliente final: Rafael tem usuário próprio, vê todos os certificados do histórico do CNPJ dele, baixa PDF em segundos, consulta validade, recebe notificação antes de vencer. Quando um novo lote é calibrado, ele recebe e-mail com link direto — não anexo perdido no meio da caixa de entrada. A relação com o laboratório fica mais profissional e Rafael vira fã, recomenda para outros compradores do setor. Mercado pequeno, boca a boca pesa.

**Objeção mais comum.** "Mais uma senha para gerenciar." Rafael administra talvez 40 sistemas diferentes. A resposta do produto é login simples (e-mail + senha, eventualmente OAuth via conta Google/Microsoft corporativa), acesso público aos certificados via link único assinado para quando for só uma consulta rápida, e nunca forçar o cadastro para ações-chave. A aderência ao portal tem que ser opcional e gradual, nunca barreira.

## Personas NÃO prioritárias (não-alvo do MVP)

- **Responsável pela qualidade da indústria cliente** — consome certificado, mas quem operacionaliza a relação é o comprador (Persona 3).
- **Auditor da Cgcre** — usuário de leitura esporádica (uma vez ao ano). O MVP entrega a ele uma trilha de auditoria navegável, mas não é persona de produto contínuo.
- **Contador do laboratório** — consome exportação CSV do contas a receber. Não precisa logar.
- **Fornecedor de padrão** — entra como dado (cadastro do padrão + cadeia de rastreabilidade), não como usuário.

Se qualquer um desses virar persona ativa, precisa de uma rodada de entrevistas antes de alterar este arquivo.
