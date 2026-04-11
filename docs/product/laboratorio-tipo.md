# Laboratório-tipo — modelo canônico de cliente do Kalibrium

> **Status:** ativo. Item 1.5.7 do plano da meta-auditoria #2 (Bloco 1.5). Serve como contexto canônico para o consultor de metrologia a ser contratado (itens M1-M6 da Trilha paralela) e para o consultor fiscal (F1-F6). **Gate operacional (item 1.5.7):** nenhum RFP de metrologia (M1) ou fiscal (F1) pode ser enviado a consultor antes deste arquivo e `ideia-v1.md` (1.5.1) existirem commitados. Com este commit + 1.5.1 commitado, o gate é satisfeito.

## 1. Por que este arquivo existe

"Laboratório de calibração" é um guarda-chuva grande. Dentro dele cabem realidades muito distintas: um laboratório acadêmico que faz pesquisa com 3 calibrações oficiais por mês, um laboratório interno de uma montadora fazendo 5.000/mês só para uso próprio, e um laboratório comercial acreditado atendendo 200 clientes externos. Se o Kalibrium não disser explicitamente para qual desses laboratórios ele é, o produto vira uma abstração que serve mal para todos. Este documento fixa o laboratório-modelo — o cliente canônico que o MVP atende — para que discussões de escopo, dimensionamento, compliance e pricing tenham referência comum.

## 2. Perfil do laboratório-modelo

### 2.1. Porte

- **Pequeno a médio porte.** Entre 600 e 2.000 calibrações por mês no pico de demanda.
- **Equipe do laboratório:** 1 gerente/responsável técnico, 2 a 6 técnicos calibradores, 1 a 2 administrativos.
- **Faturamento anual:** faixa de referência R$ 600 mil a R$ 4 milhões/ano, enquadramento Simples Nacional ou Lucro Presumido. Lucro Real fica fora do MVP (`mvp-scope.md §4`).
- **Espaço físico:** entre 80 e 250 m² de laboratório propriamente dito, com salas climatizadas para as bancadas críticas.

### 2.2. Volume típico por domínio

Baseado na distribuição observada em laboratórios comerciais brasileiros de porte pequeno-médio, a mistura canônica assumida pelo Kalibrium é:

- **Dimensional:** ~35% do volume (paquímetros, micrômetros, blocos padrão, relógios comparadores, traçadores).
- **Pressão:** ~25% (manômetros, transmissores de pressão, vacuômetros, pressostatos).
- **Massa:** ~20% (balanças analíticas, semi-analíticas, balanças comerciais).
- **Temperatura:** ~15% (termômetros, termopares, PT100, indicadores de temperatura, fornos/estufas de conforto).
- **Outros domínios (torque, vazão, elétricos):** ~5%, ficam fora do MVP.

### 2.3. Equipamentos típicos do próprio laboratório (padrões e instrumentos de referência)

- **Dimensional:** conjunto de blocos padrão grau 0 ou 1, micrômetros-padrão, projetor de perfil, altímetro digital, calibrador de paquímetros/micrômetros.
- **Pressão:** balança de pressão (pistão/cilindro), bomba geradora de pressão, transdutor-padrão de referência.
- **Massa:** conjunto de massas-padrão classe E2 ou F1, balança de referência classe I.
- **Temperatura:** calibrador de bloco seco, banho termostático, termômetro-padrão (PRT), sistema de ponto fixo (opcional, poucos laboratórios têm).
- **Ambiente:** termo-higrômetro calibrado para registrar condições da sala (um ou dois, dependendo do tamanho).

Esses padrões **têm calibração periódica** e **têm certificado vigente** com rastreabilidade a padrão de ordem superior (Inmetro ou laboratório internacional). O Kalibrium precisa cadastrar cada um, armazenar o certificado vigente, lembrar a data de revalidação e recusar o uso do padrão em nova calibração quando vencido.

### 2.4. Escopo de acreditação (RBC/Cgcre)

- **Estado de acreditação:** a maior parte dos laboratórios-alvo é **acreditada** pela Cgcre/Inmetro em pelo menos um dos quatro domínios do MVP, com selo RBC vigente.
- **Escopo típico acreditado:** subconjunto do que o laboratório faz — tipicamente entre 40% e 70% das calibrações emitidas carregam o selo RBC; o restante é calibração "não-acreditada" ou "rastreada", que também requer rastreabilidade mas não carrega o selo.
- **Auditoria anual:** a Cgcre audita presencialmente uma vez ao ano. A checagem usual envolve: sortear uma calibração realizada nos últimos 12 meses e pedir a trilha completa — padrão usado, certificado do padrão naquela data, orçamento de incerteza, procedimento vigente, assinatura do calibrador, assinatura do responsável técnico, registro das condições ambientais, cadeia de custódia do instrumento do cliente.
- **Consequência para o MVP:** o Kalibrium precisa emitir certificado com duas variantes visuais claramente distintas — "calibração acreditada (RBC)" e "calibração rastreada não-acreditada" — sem misturar as duas.

### 2.5. Procedimentos técnicos vigentes

O laboratório-tipo mantém entre 15 e 40 procedimentos técnicos escritos, um por combinação "tipo de instrumento × faixa × método". Cada procedimento tem versão, data de revisão, lista de padrões requeridos e orçamento de incerteza anexo. O Kalibrium precisa:

- Armazenar procedimentos versionados (não editar — nova versão, histórico preservado).
- Vincular cada calibração ao procedimento da versão vigente no momento da execução.
- Alertar quando uma calibração está sendo executada com procedimento em revisão.

### 2.6. Complexidade fiscal típica

- **Nota fiscal:** o laboratório emite **NFS-e** (nota fiscal de serviço) para a prefeitura do município onde está sediado. Cada município brasileiro tem seu próprio formato/API — São Paulo capital é diferente de Belo Horizonte, que é diferente de Porto Alegre. O MVP começa cobrindo **5 municípios-alvo iniciais** escolhidos pela concentração do mercado-alvo e pela distância entre os padrões das APIs municipais: São Paulo (SP), Campinas (SP), Belo Horizonte (MG), Curitiba (PR) e Porto Alegre (RS). Essa lista é a base do RFP F3 e pode ser reajustada após o parecer do consultor fiscal, desde que a reavaliação fique registrada como decisão rastreável em `docs/decisions/` antes de qualquer alteração de escopo.
- **ISS:** alíquota varia entre 2% e 5% dependendo do município. Calibração e metrologia normalmente se enquadram em código de serviço 14.01 ou 17.01 (dependendo da prefeitura).
- **ICMS:** calibração não tem ICMS (é serviço, não circulação de mercadoria). Mas quando o laboratório vende peças ou acessórios (raro, mas acontece), o ICMS entra no jogo — fora do escopo do MVP.
- **Simples Nacional vs Lucro Presumido:** o cálculo do ISS retido varia por regime. O Kalibrium precisa suportar os dois no MVP.
- **Reforma tributária:** o efeito concreto sobre o laboratório-tipo depende da implementação final do IBS/CBS e do cronograma de transição. Risco real, monitorado em `docs/compliance/law-watch.md` (item T2.12 da Trilha #2).

## 3. Clientes atendidos pelo laboratório-tipo

- **Perfil do cliente:** indústria de pequeno ou médio porte — metal-mecânica, alimentos, química, farmacêutica leve, autopeças, embalagem.
- **Volume por cliente:** entre 2 e 50 instrumentos por envio. Cliente típico manda lote trimestral ou semestral para calibrar.
- **Ticket médio do pedido:** faixa de R$ 200 a R$ 3.000 por pedido, dependendo do número e tipo de instrumentos.
- **Relação com prazo:** cliente industrial tem tolerância baixa a atraso — prazo usual acordado é de 5 a 10 dias úteis. Atraso maior que isso normalmente custa ao laboratório a próxima remessa.

## 4. O que o laboratório-tipo **não** é

- **Não é laboratório de ensaio.** Ensaios destrutivos (tração, flexão) ou químicos (pH, condutividade) não entram.
- **Não é LIMS acadêmico.** Pesquisa universitária tem fluxos completamente diferentes — fora.
- **Não é laboratório interno de indústria.** Laboratório que calibra só para si mesmo (uso interno) não é o alvo — ele pode usar o Kalibrium, mas o produto não é desenhado para ele. Eles não emitem NFS-e para cliente externo, por exemplo.
- **Não é laboratório internacional / multissede.** Operação em um único CNPJ, em um único município, é a premissa. Rede de laboratórios com 5 sedes fica para depois (Trilha de crescimento, não MVP).
- **Não é fabricante de instrumento.** Quem fabrica instrumento calibra no laboratório-tipo, não é o laboratório-tipo.

## 5. Referências que usam este arquivo

- `docs/product/mvp-scope.md` — usa o laboratório-tipo para justificar os quatro domínios cobertos no MVP.
- `docs/product/nfr.md` — usa o volume (600-2.000 calibrações/mês) como base do dimensionamento de RPS, p95 e capacidade.
- `docs/product/personas.md` — as três personas (gerente, técnico, cliente final) são extraídas do laboratório-tipo.
- `docs/compliance/rfp-consultor-metrologia.md` — já existia; consome este arquivo como contexto.
- `docs/compliance/rfp-consultor-fiscal.md` — já existia; consome este arquivo como contexto.
- `docs/audits/progress/meta-audit-tracker.md` — o gate operacional da Trilha #1 (RFP bloqueado sem 1.5.1 + 1.5.7) é registrado neste tracker.
