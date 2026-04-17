# Personas do Kalibrium

> **Status:** ativo — ampliado em 2026-04-16 (v1 + v2 + v3). Revisão pós-incidente `docs/incidents/discovery-gap-offline-2026-04-16.md`: versão anterior (3 personas) estava correta mas **incompleta**. Ampliação v1 adicionou técnico de campo, motorista UMC, vendedor externo, gestor em campo, atendente de escritório. Ampliação v2 acrescentou Persona 8 — Responsável de Qualidade / ISO 17025 owner. **Ampliação v3** (pós re-auditoria independente): **refinamento da Persona 1** (Marcelo) incluindo dimensão financeira/CFO/Diretoria — em laboratório pequeno/médio-alvo, o sócio-gerente acumula decisão executiva e decisão financeira; o KPI financeiro passa a ser explicitamente parte da persona. Backup do estado anterior em `personas-backup-2026-04-16.md`. Ver `docs/product/PRD-ampliacao-2026-04-16-v2.md` e `docs/product/PRD-ampliacao-2026-04-16-v3.md`.
>
> **Princípio operacional:** o Kalibrium atende empresas de serviço técnico em campo **e** laboratórios de calibração de bancada — muitos operam os dois modelos ao mesmo tempo. Uma pessoa pode aparecer em mais de uma persona (ex: técnico que faz bancada de manhã e campo à tarde é a mesma pessoa vivendo as personas 2 e 2B conforme o dia).
>
> **Papel de técnico é único no sistema.** Não existe distinção de sistema entre "técnico de bancada" e "técnico de campo" — ambos são o papel `tecnico-calibrador`. O que varia é a escala operacional de cada empresa-cliente: algumas especializam, outras alternam, outras misturam. As personas 2 (Juliana) e 2B (Carlos) abaixo são dois exemplares do **mesmo papel** com rotinas diferentes, apresentados separados porque as dores e os fluxos diários divergem.
>
> Total atual: 9 personas primárias (8 de v1 + 1 de v2). Se aparecer uma décima em pesquisa de campo, precisa passar por discovery antes de entrar aqui.

---

## Persona 1 — Sócio-gerente do laboratório (Marcelo, 48 anos)

**Contexto profissional.** Marcelo é formado em Engenharia Mecânica, tem especialização em metrologia pelo próprio Inmetro, trabalhou 12 anos como técnico em um laboratório maior antes de abrir o próprio. Hoje é sócio-gerente de um laboratório acreditado com 5 técnicos, 2 administrativos e cerca de 1.400 calibrações/mês no pico. Ele é o responsável técnico perante a Cgcre, é quem assina os certificados acreditados e é quem recebe a auditoria anual. Também é quem fecha a venda dos contratos com clientes grandes, negocia prazo e responde por inadimplência. Conhece Excel profundamente, conhece o portal da prefeitura de memória, e tem uma relação de amor-ódio com o software legado que o laboratório usa há 8 anos.

**Responsabilidade ampliada (campo + frota).** Marcelo também decide a agenda da UMC (Unidade Móvel de Calibração) da empresa, aprova quem vai em qual visita, revisa a caixa de despesa de cada OS antes de reembolsar o técnico, assina a decisão de adiantar dinheiro para uma viagem longa e responde pela manutenção/seguro dos veículos operacionais. Se uma balança rodoviária de cliente grande precisa de aferição urgente, é ele que libera a UMC sair do cronograma, negocia valor e aprova hora-extra do motorista.

**Responsabilidade financeira / papel CFO acumulado (refinado em v3).** No laboratório-alvo do MVP (pequeno a médio), Marcelo **acumula papel executivo e financeiro** — não há CFO dedicado. É ele quem: (a) acompanha fluxo de caixa mensal (contas a receber, inadimplência, despesas operacionais consolidadas de todas as OS), (b) negocia prazo com cliente inadimplente e decide quando mandar para cobrança formal, (c) aprova investimento em novo padrão de referência, novo veículo ou recalibração externa (custo alto), (d) acompanha margem por tipo de serviço (bancada vs campo vs UMC) e decide política de preço, (e) fecha o mês fiscal junto com a Cláudia (atendente) e valida relatório do contador externo. Precisa de visão executiva consolidada — dashboard com faturamento, margem, aderência a prazo, custo real por OS, ocupação da UMC — sem precisar abrir 5 módulos. O Kalibrium entrega isso como **visão Diretoria** do dashboard operacional (`REQ-OPL-001/003`), com indicadores financeiros de primeira linha visíveis no login.

**Dor principal.** O tempo que Marcelo gasta "apagando incêndio administrativo" — caçando o número de série de um padrão no livro antigo, redigitando cadastro porque o software legado perdeu, cruzando planilha com portal fiscal, explicando ao cliente por que o certificado atrasou, tentando entender no WhatsApp onde a UMC está e quanto tempo falta para voltar, reconciliando o cupom de combustível que o técnico tirou foto mas não anexou no lugar certo. Ele sente que trabalha como administrativo quando deveria trabalhar como técnico e como vendedor. Cada hora de "apagar incêndio" é uma hora que ele não está calibrando, não está atendendo cliente novo e não está revisando procedimento. Em um mês ruim, essa conta chega a 40% do tempo dele.

**Como o Kalibrium resolve.** Transforma a operação do laboratório em um fluxo único onde o dado é digitado uma vez, a trilha de rastreabilidade é automática e o certificado sai direto no portal do cliente. Marcelo deixa de ser "coordenador de planilha" e volta a ser engenheiro metrólogo. O Kalibrium também entrega, como efeito colateral, os indicadores que ele não tinha tempo de montar manualmente — tempo médio por calibração, pedidos atrasados, aderência ao prazo, custo real de cada OS (incluindo deslocamento da UMC e despesa do técnico), ocupação da UMC — permitindo conversas mais concretas com técnicos, clientes e contador.

**Objeção mais comum.** "Eu já paguei por um software desses e ele quebrou." Marcelo tem cicatriz de implantação que deu errado. A venda precisa responder por que o Kalibrium não é só mais um — a resposta do produto é dar um caminho reversível (exportação CSV em qualquer momento, dados dele, sempre dele) e começar pelo módulo de metrologia, não pelo fiscal, que é onde o legado normalmente falhou.

---

## Persona 2 — Técnica calibradora com rotina de bancada (Juliana, 32 anos)

**Contexto profissional.** Juliana é técnica em mecânica com formação complementar em metrologia. Está há 6 anos no laboratório. Calibra instrumentos dimensionais e de pressão. É a pessoa que mais domina o orçamento de incerteza entre os técnicos, e virou referência informal quando alguém tem dúvida no cálculo. Usa a planilha de incerteza herdada do antigo responsável técnico, versão que ela mesma modificou três vezes para cobrir casos que não estavam previstos. Trabalha na bancada, não no escritório. Odeia interromper uma calibração para fazer lançamento em sistema.

**Escala operacional típica.** 90% bancada, 10% campo (só acompanha quando o cliente é grande e o gerente pede). Usa tablet na bancada e smartphone em campo. Quando sai para campo, normalmente é dia único e volta no mesmo dia — raramente enfrenta cenário offline por mais de algumas horas.

**Dor principal.** "Interrupção de contexto." Quando Juliana está na bancada medindo um bloco padrão, pedir que ela saia, vá ao computador do escritório, faça login no software legado, procure o pedido, digite os valores, salve, volte — destrói o ritmo da calibração. Ela prefere anotar em papel, terminar o lote inteiro e depois digitar tudo de uma vez. O problema é que o papel às vezes some, o lote demora a ser digitado, e cada atraso de lançamento é um atraso no certificado, que é um atraso na cobrança. Marcelo (gerente) sabe e reclama.

**Como o Kalibrium resolve.** Interface de bancada com menos fricção possível — tablet ou celular, login rápido (biometria), lançamento dos valores campo-a-campo com teclado numérico grande, captura de condições ambientais (temperatura, umidade) com um toque, seleção do padrão usado em uma lista já filtrada pelos padrões vigentes. A calibração é lançada em tempo real, sem interromper a cadência. O orçamento de incerteza é pré-carregado do procedimento vigente e Juliana só confere — ela não precisa abrir outra planilha. Quando vai para campo, o mesmo app funciona offline sem que ela precise aprender nada novo.

**Objeção mais comum.** "Minha planilha de incerteza faz coisas que o sistema de vocês não vai fazer." Juliana já customizou demais o cálculo para acreditar que vai caber em qualquer produto. A resposta real é deixar o MVP aceitar versão específica por procedimento, versionar as planilhas como parte do procedimento e garantir que quando a planilha é atualizada, todas as calibrações futuras usam a nova versão sem perder as antigas. Se for preciso, o Kalibrium consome a planilha dela literalmente até o momento em que ela mesma decidir migrar o cálculo para dentro do sistema.

---

## Persona 2B — Técnico calibrador com rotina de campo (Carlos, 37 anos)

**Contexto profissional.** Carlos é técnico em eletromecânica com 10 anos de estrada em calibração industrial. Entrou no laboratório há 4 anos como o "técnico que aceita pegar a estrada". Calibra balança rodoviária, balança industrial de médio e grande porte, dosadora, silo, tanque. Dirige o veículo operacional dele próprio (uma caminhonete assinada a ele) e, quando a OS é de balança rodoviária grande, acompanha a UMC com o motorista/operador de guindaste da empresa. Conhece Minas, Goiás, Mato Grosso e o interior de São Paulo pelo mapa mental — sabe em qual posto parar, em qual ponte não passar com UMC, qual cliente oferece almoço, qual cobra portaria rigorosa.

**Escala operacional típica.** 95% campo, 5% bancada (só nos dias de chuva pesada ou quando tem um instrumento especial que o cliente mandou pro laboratório). Usa smartphone Android robusto da empresa + tablet pra relatórios longos. Frequentemente passa 2 a 4 dias fora do escritório, pernoitando em cidades do interior, com sinal intermitente (alguns clientes ficam em zona rural, mina, usina, galpão com paredes grossas).

**Dor principal.** Hoje Carlos opera com três ferramentas paralelas: caderno de campo em papel, WhatsApp com o gerente para tirar dúvida, e uma planilha do laboratório antigo pra planejar rota. Quando volta, **gasta 2-3 horas no escritório digitando tudo** que fez nos dias anteriores, porque o sistema do laboratório só funciona online e a conexão em campo é incerta. Se esquece um dado, liga pro cliente — o que é constrangedor. Se perde o caderno (já aconteceu), perde dia de trabalho. Na caixa de despesa é pior ainda: cupons de combustível amassados no bolso, recibo de almoço manchado, nota do hotel no WhatsApp do dono — organizar tudo e atrelar a OS certa é um trabalho de fim de semana.

**Como o Kalibrium resolve.** App mobile que funciona **100% offline**, aguentando até 4 dias de trabalho pendente de sincronização. Carlos abre a OS no celular, vê no mapa quais clientes vai visitar, calibra em campo registrando valores direto no app, tira foto do selo da balança, captura assinatura do representante do cliente no touch, anexa cupom de combustível dentro da OS (foto obrigatória), tudo isso sem sinal. Quando pega sinal — mesmo que seja por 2 minutos num posto de gasolina — o app sincroniza o que pode e continua. Quando a UMC é necessária, Carlos vê na OS quem é o motorista escalado, coordena horário, e quando estão juntos no cliente o app permite que ambos contribuam na mesma OS simultaneamente.

**Objeção mais comum.** "Esse negócio vai travar no meio do nada, como todo aplicativo." Carlos já viu app de entrega gigante pedir "conexão instável" em lugar com 4G pleno. A resposta do produto é que tudo é offline-first por padrão: o app **funciona igual com ou sem sinal**, o usuário não precisa "decidir" se está offline, e se algo der errado, o dado permanece no celular até conseguir sincronizar. O botão de "modo avião forçado" existe como prova: o técnico pode testar antes de viajar longe e se convencer.

---

## Persona 3 — Cliente final do laboratório (Rafael, 40 anos, comprador industrial)

**Contexto profissional.** Rafael é comprador técnico numa fábrica de autopeças de médio porte no interior de São Paulo. Entre muitas outras responsabilidades, ele é quem organiza o envio semestral de instrumentos da linha de produção para calibração externa. Conhece vários laboratórios, compara preço, compara prazo. Não é metrólogo — para ele, o certificado é um documento que tem que chegar no prazo, ser aceito pela auditoria da montadora para quem ele fornece peça, e vir acompanhado de nota fiscal correta. Ele nunca acessa o laboratório pessoalmente. A relação é por e-mail e telefone.

**Dor principal.** Visibilidade zero entre o momento em que ele despacha os instrumentos e o momento em que os certificados chegam. Se a montadora pedir prova de calibração de um instrumento que foi calibrado há 9 meses, Rafael tem que ligar no laboratório, esperar a secretária achar, receber por e-mail, salvar numa pasta e mandar para o auditor. Em um ano ele faz isso 15 ou 20 vezes. Ele perde confiança no laboratório quando a resposta demora mais do que um dia útil.

**Como o Kalibrium resolve.** Portal do cliente final: Rafael tem usuário próprio, vê todos os certificados do histórico do CNPJ dele, baixa PDF em segundos, consulta validade, recebe notificação antes de vencer. Quando um novo lote é calibrado, ele recebe e-mail com link direto — não anexo perdido no meio da caixa de entrada. A relação com o laboratório fica mais profissional e Rafael vira fã, recomenda para outros compradores do setor. Mercado pequeno, boca a boca pesa.

**Objeção mais comum.** "Mais uma senha para gerenciar." Rafael administra talvez 40 sistemas diferentes. A resposta do produto é login simples (e-mail + senha, eventualmente OAuth via conta Google/Microsoft corporativa), acesso público aos certificados via link único assinado para quando for só uma consulta rápida, e nunca forçar o cadastro para ações-chave. A aderência ao portal tem que ser opcional e gradual, nunca barreira.

---

## Persona 4 — Motorista / operador de guindaste da UMC (Seu Lúcio, 52 anos)

**Contexto profissional.** Lúcio é motorista carreteiro veterano, CNH E, 28 anos de estrada. Entrou no laboratório há 3 anos como o motorista da UMC — a Unidade Móvel de Calibração, um caminhão equipado com guindaste hidráulico e um conjunto de massas-padrão rastreáveis (algumas pesam 500 kg, outras 1.000 kg ou mais). Ele dirige a UMC até o cliente, opera o guindaste para posicionar as massas na plataforma da balança rodoviária, acompanha o técnico durante a aferição, e dirige de volta. Entre uma calibração e outra, é ele que lembra de trocar óleo, calibrar pneu, renovar o licenciamento, pagar pedágio. Formação técnica formal: zero. Conhecimento metrológico necessário pra operar o guindaste de forma segura: total, adquirido na prática.

**Escala operacional típica.** A UMC sai 2-3 dias por semana quando há OS programada. Nos dias parados, Lúcio ajuda na garagem do laboratório, faz manutenção básica, separa padrões pra próxima viagem. Usa smartphone Android simples (não é confortável com tecnologia, mas aprende). Quando está em campo, frequentemente fica offline — obras, zonas rurais, minas, usinas.

**Dor principal.** Hoje Lúcio anota tudo em papel: KM de saída, KM de chegada, litros abastecidos, valor do pedágio, nome do posto, horário. Quando volta, entrega o monte de cupons à administrativa do laboratório, que digita. Cupom de papel térmico já apagou ("saiu tudo branco"), nota de posto já foi perdida no bolso da calça que virou máquina de lavar. Ele sente que é desorganização dele, mas na verdade é o processo que exige papel numa rotina de estrada. Na caixa de despesa o erro custa dinheiro — se o cupom some, ele perde o reembolso.

**Como o Kalibrium resolve.** App do Lúcio é ultra-simples: uma tela grande com 4 botões — "saída" (captura KM automático via GPS se tiver sinal, manual se não tiver, registra hora), "abastecimento" (foto do cupom + valor + litros + posto), "pedágio" (foto + valor), "chegada". Tudo fica atrelado automaticamente à OS atribuída à UMC naquele dia. Quando a OS tem equipe (técnico + Lúcio), o app mostra pro Lúcio a informação que ele precisa ver: cliente, endereço, horário combinado, quem é o técnico responsável, telefone do cliente. Nada mais. Ele não vê orçamento, não vê certificado, não precisa — só dados relevantes pro papel dele.

**Objeção mais comum.** "Eu não sou bom de celular." Resposta do produto: interface com poucos botões, letras grandes, sem termos técnicos, sem menu escondido. Se Lúcio errar, o gestor do escritório corrige pelo computador depois — ele não precisa saber deletar registro. Biometria pra abrir o app resolve senha.

---

## Persona 5 — Vendedor externo (Patrícia, 29 anos)

**Contexto profissional.** Patrícia é vendedora externa do laboratório há 2 anos. Trabalha com uma carteira de ~500 clientes ativos espalhados em 3 estados. Cobertura de campo: visita cliente em fábrica, conversa no portão, pega pedido, passa orçamento, faz follow-up pós-calibração. Usa carro próprio com reembolso de km. Formação: administração, com cursos internos de metrologia básica pra conversar com comprador técnico sem passar vergonha. Bateria do celular é o recurso mais crítico dela — chegou a dormir na portaria de cliente porque o celular apagou e ela perdeu o contato do comprador.

**Escala operacional típica.** 80% campo, 20% escritório (segunda-feira de manhã é o dia fixo de escritório pra alinhar com gerente e administrativa). Smartphone pessoal (BYOD — bring your own device) + notebook do escritório. Cobertura de sinal: boa em cidade, ruim em área industrial (galpões com estrutura metálica matam o sinal), intermitente na estrada.

**Dor principal.** Hoje Patrícia vive no WhatsApp + planilha + memória. Cliente novo liga pedindo orçamento, ela anota no caderno, promete retornar até o fim do dia, esquece, lembra só no fim de semana. Cliente antigo pede histórico da calibração anterior, ela não tem acesso no celular — liga pro laboratório, espera alguém olhar, repassa pro cliente horas depois, já esfriou. Quando está no cliente e ele pede orçamento na hora, ela rabisca no papel, promete enviar formal por e-mail, volta pro escritório, digita, manda — perde 2 dias de ciclo que o concorrente fecha antes.

**Como o Kalibrium resolve.** CRM mobile offline: Patrícia abre o app, vê os 500 clientes dela, histórico de cada um, certificados anteriores, contatos, última conversa, última visita. Faz orçamento no próprio celular no portão do cliente, manda na hora por link — mesmo sem sinal, o link é gerado localmente e sincroniza quando conectar. Registra a visita com nota de voz (transcrita depois) e foto da fachada/crachá. Follow-up automático: o app lembra ela de retornar em X dias. O gestor do escritório vê o pipeline dela em tempo real quando o sincronismo roda. Se ela perde o celular, wipe remoto protege os dados dos clientes.

**Objeção mais comum.** "Eu já tenho um jeito que funciona." Patrícia é veterana de CRM ruim (implantou 2 em empregos anteriores, nunca decolou). Resposta do produto: zero fricção de entrada — ela continua usando WhatsApp pra falar com cliente, o Kalibrium **captura** essa conversa (via integração opcional ou registro manual rápido), e ela vê o benefício depois sem ter mudado o comportamento dela. "Aderência gradual, nunca barreira", igual o Rafael.

---

## Persona 6 — Gestor em campo (Diego, 35 anos)

**Contexto profissional.** Diego é o "braço direito" do Marcelo. Coordenador operacional — papel híbrido entre gerente-júnior e supervisor. Acompanha as OS grandes in loco (quando o cliente é importante, ou quando a UMC está envolvida, ou quando tem 3+ técnicos na mesma visita), resolve imprevisto ("o cliente mudou o horário", "falta combustível no cupom da UMC", "o cliente quer NFS-e ainda hoje"), e é a pessoa que o cliente vê como responsável durante a operação. Formação em Engenharia de Produção. Celular corporativo com plano ilimitado (sinal dele é sempre online, diferente dos técnicos em campo). Pode emitir NFS-e/boleto do celular quando o escritório está fechado — ele tem a credencial.

**Escala operacional típica.** 60% campo (acompanhando OS grandes), 40% escritório. Smartphone + notebook. Sempre online (plano bom + escolhe lugares com sinal).

**Dor principal.** Diego é o "tradutor" entre o técnico em campo (que não emite nota) e o escritório (que emite mas não está em campo). Hoje, quando o cliente pede NFS-e na hora, Diego liga pra administrativa, dita dados por telefone, ela emite, manda PDF pro WhatsApp dele, ele mostra pro cliente. Funciona, mas é frágil — já deu erro de CNPJ ditado ao telefone que precisou cancelar NFS-e e refazer. Quando ele mesmo tenta emitir pelo portal da prefeitura no celular, o portal é desktop-only e não abre direito.

**Como o Kalibrium resolve.** Diego tem perfil com permissão de emitir NFS-e/boleto direto do celular — o Kalibrium fala com a prefeitura pelo backend, retorna o PDF/XML, ele mostra pro cliente na tela, o cliente assina digital se quiser. Tudo atrelado à OS. Se estiver sem sinal no momento, o Kalibrium **prepara** a nota localmente, mostra o preview pro cliente, e transmite à prefeitura quando pegar sinal (a "nota fica pendente de transmissão" — status transparente, nenhum dado se perde). Diego também vê a caixa de despesa das OS que ele está acompanhando em tempo real — se o técnico precisa de dinheiro extra pro pedágio de volta, Diego aprova adiantamento na hora.

**Objeção mais comum.** "Prefeitura de [cidade] não deixa emitir via API." Diego já bateu de cabeça com portal municipal capenga. Resposta do produto: o Kalibrium integra cidade-a-cidade, começa pelas 2 ou 3 principais da operação do cliente, e expande conforme demanda. Onde não houver API, entrega preparação completa da nota pra colar no portal em 10 segundos — melhor que digitar do zero.

---

## Persona 7 — Atendente / administrativa do escritório (Cláudia, 45 anos)

**Contexto profissional.** Cláudia é a "dona do escritório" do laboratório — atende o telefone, recebe instrumento na recepção (quando chega pelo Correios ou pelo próprio cliente), abre OS inicial, emite NFS-e e boleto, concilia extrato bancário, faz triagem e aprovação de despesa dos técnicos, organiza a entrega dos certificados. 20 anos de administrativa em empresas diversas, 5 no laboratório atual. Formação: ensino médio técnico em administração. Dominou o sistema legado e o portal da prefeitura na base da persistência. Se sente insegura com tecnologia nova, mas não admite.

**Escala operacional típica.** 100% escritório. Desktop (tela grande é essencial — ela roda ERP + portal prefeitura + Excel + e-mail ao mesmo tempo). Sempre online. Horário de trabalho fixo (08:00-17:00).

**Dor principal.** Cláudia é a que "sofre por último" — quando o técnico volta do campo sem anexar despesa direito, é ela que liga perguntando. Quando a NFS-e tem CNPJ errado, é ela que cancela, refaz, pede desculpa por e-mail. Quando o cliente reclama de prazo, é ela que transfere ligação pro Marcelo. Quando o portal da prefeitura sai do ar em dia de fechamento de mês, é ela que liga no fornecedor de software fiscal às 16:50 pra entender se o problema é do portal ou do software dela. E tem o cadastro duplo — cada cliente novo entra no ERP, depois no portal da prefeitura, depois na planilha de controle de contrato. Três vezes a mesma coisa.

**Como o Kalibrium resolve.** Cláudia ganha **tela única**: cliente cadastrado uma vez, NFS-e emitida direto do sistema (Kalibrium fala com prefeitura), boleto registrado direto no banco integrado, caixa de despesa dos técnicos chega automaticamente na fila de aprovação dela (com foto anexada pelo próprio técnico, não pendura no WhatsApp), conciliação bancária por OCR do extrato. Ela vê o status da UMC em tempo real (saiu, voltou, combustível, manutenção próxima). Quando um técnico em campo pede adiantamento pro pedágio, a notificação chega pra Cláudia com 1 clique de aprovação. Vida mais tranquila, menos horas extras.

**Objeção mais comum.** "Eu sei onde clicar no sistema atual, não quero começar do zero." Resposta do produto: importação automática do legado (CSV/planilha), treinamento em 2 manhãs com acompanhamento próximo na primeira semana, e período de duplo sistema (legado + Kalibrium em paralelo) até ela ter confiança. Cláudia é quem mais precisa de onboarding humano — o laboratório para se ela não estiver confortável.

---

## Persona 8 — Responsável de Qualidade / ISO 17025 owner (Aline, 38 anos) — NOVA v2

**Contexto profissional.** Aline é engenheira química com pós em metrologia pelo Inmetro. É a Responsável Técnica formal perante a Cgcre no laboratório onde trabalha — o nome dela está no escopo de acreditação do laboratório. 10 anos na área, 4 no laboratório atual. Não é sócia nem gerente operacional — é o papel técnico de qualidade, responsável por: manter o sistema de gestão da qualidade ISO 17025, garantir que procedimentos de calibração estão válidos, gerenciar o ciclo de vida dos padrões de referência (incluindo envio para recalibração externa), monitorar a estabilidade dos padrões (drift), aprovar cálculos de incerteza, treinar técnicos novos em procedimentos. É quem recebe a auditoria anual Cgcre pessoalmente — perguntas técnicas do auditor passam por ela, não pelo Marcelo. Em laboratório maior, é um cargo separado do gerente; em laboratório pequeno (o caso do Kalibrium MVP), pode acumular com o sócio-gerente, mas a função existe sempre.

**Escala operacional típica.** 100% escritório + laboratório. Desktop (tela grande para cruzar procedimentos, planilhas de incerteza, gráficos de controle) + tablet na bancada quando precisa acompanhar calibração crítica. Sempre online. Horário próximo do comercial, com exceção das semanas de auditoria.

**Dor principal.** Aline hoje vive entre três sistemas que não conversam: um ERP do laboratório (cadastros e OS), uma pasta em rede com os procedimentos (controle de versão manual em nome de arquivo "procedimento-pressao-v12-FINAL-novo.docx"), e uma pilha de planilhas Excel, uma por padrão, onde ela anota manualmente cada recalibração do próprio padrão e plota um gráfico de controle que mostra se o padrão está "se comportando". Essas planilhas já causaram incidente: padrão deslizou para fora do limite de controle durante 4 meses sem ninguém notar, e ela teve que invalidar 47 certificados emitidos nesse período — conversa difícil com cliente. Quando vem auditoria da Cgcre, ela passa 2 semanas correndo, abrindo 40+ arquivos para montar a trilha de rastreabilidade que o auditor pede em minutos.

**Como o Kalibrium resolve.** Aline ganha três capacidades no MVP ampliado:

1. **SPC de padrões (`REQ-MET-009`).** Cada padrão de referência tem gráfico de controle atualizado automaticamente a cada recalibração. UCL/LCL visíveis. Visual imediato do comportamento histórico.
2. **Drift automático (`REQ-MET-010`).** O sistema avisa ela quando um padrão está tendendo para fora antes do problema virar incidente. Se cruzar limite crítico, bloqueia o padrão para uso em calibrações novas automaticamente — o erro de 4 meses não se repete.
3. **Trilha de auditoria ISO 17025 navegável.** Quando o auditor Cgcre pergunta "onde está a rastreabilidade do padrão X que calibrou o instrumento Y do cliente Z em data W?", Aline abre a tela, filtra e mostra — em vez de caçar em 40 arquivos.

Além disso, Aline é o **DPO** do laboratório no MVP (compartilhado com o Marcelo em lab pequeno). Recebe requisições LGPD do titular (`REQ-CMP-006`) e tria.

**Objeção mais comum.** "A planilha que eu uso hoje foi feita pra atender exatamente o que a Cgcre me pede na auditoria e vocês não vão conseguir reproduzir." Aline tem razão parcial — a auditoria Cgcre tem exigências específicas do escopo de cada laboratório. A resposta do produto é: o Kalibrium atende o essencial (SPC + drift + rastreabilidade navegável) no MVP e aceita exportação CSV dos dados brutos para que ela continue fazendo análise em Excel quando precisar. Na ampliação pós-MVP, entram cálculos específicos (MU reavaliação, ANOVA de repetitividade, etc).

---

## Personas NÃO prioritárias (não-alvo do MVP)

- **Responsável pela qualidade da indústria cliente** — consome certificado, mas quem operacionaliza a relação é o comprador (Persona 3).
- **Auditor da Cgcre** — usuário de leitura esporádica (uma vez ao ano). O MVP entrega a ele uma trilha de auditoria navegável, mas não é persona de produto contínuo.
- **Contador do laboratório** — consome exportação CSV do contas a receber. Não precisa logar.
- **Fornecedor de padrão** — entra como dado (cadastro do padrão + cadeia de rastreabilidade), não como usuário.
- **Técnico terceirizado / freelancer** — se for eventual, opera como convidado em uma OS específica com permissão limitada. Não é persona primária no MVP.
- **Motorista terceirizado da UMC (sem vínculo CLT)** — se aparecer, opera no perfil da Persona 4 (Lúcio) com restrição de acesso financeiro (não vê reembolso, só registra despesa da viagem). Não é persona primária separada.

Se qualquer um desses virar persona ativa, precisa de uma rodada de entrevistas antes de alterar este arquivo.

---

## Mapa de offline-first por persona

| Persona | Offline obrigatório? | Janela offline típica | Dispositivo primário |
|---|---|---|---|
| 1 — Marcelo (gerente) | Sim (acompanha operação em campo) | Horas (visita cliente grande) | Notebook + smartphone |
| 2 — Juliana (técnica bancada) | Sim, mas raro uso | Horas (dia de campo esporádico) | Tablet + smartphone |
| 2B — Carlos (técnico campo) | **Crítico** | Até 4 dias | Smartphone Android robusto + tablet |
| 3 — Rafael (cliente final) | Não | — | Desktop / smartphone |
| 4 — Lúcio (motorista UMC) | **Crítico** | Até 4 dias | Smartphone Android simples |
| 5 — Patrícia (vendedora) | **Crítico** | Horas a dia inteiro | Smartphone pessoal + notebook |
| 6 — Diego (gestor campo) | Parcial (emite NFS-e online) | Minutos | Smartphone + notebook |
| 7 — Cláudia (escritório) | Não | — | Desktop |
| 8 — Aline (Responsável de Qualidade) | Não | — | Desktop + tablet ocasional |

Todas as personas operam no **mesmo app/sistema**. O que muda é o conjunto de telas e permissões de cada papel (RBAC), não o produto.
