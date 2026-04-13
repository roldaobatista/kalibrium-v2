# Caminho do Kalibrium — leitura única do PM

**Este é o único arquivo que o PM precisa ler.** Tudo mais é trabalho interno do agente.

**Atualização:** 2026-04-11

---

## Onde estamos

- **Descoberta de produto:** ✅ **feita** (8 documentos em `docs/product/` + PRD consolidado em `docs/product/PRD.md`)
- **Tecnologia escolhida:** ❌ não decidida ainda
- **Primeira feature visível:** ❌ ainda não começou
- **Oficina de segurança (harness):** 🟡 parcialmente pronta (o suficiente para o próximo passo)

---

## O caminho em 5 etapas (linguagem de produto)

### Etapa 1 — Descoberta do produto ✅ FEITO
Os 8 documentos em `docs/product/` foram escritos e validados. Consolidei tudo num PRD único em `docs/product/PRD.md`. **Este é o documento-mestre do produto.** Se quiser ler o Kalibrium de cabo a rabo em um único arquivo, é ele.

### Etapa 2 — Escolher a tecnologia — PRÓXIMA
Próxima sessão Claude Code vai:
1. Ler o PRD
2. Comparar 2 ou 3 alternativas de tecnologia (Node/TypeScript vs Python vs outra)
3. Traduzir pra você em português: *"recomendo alternativa A porque X, Y, Z. Alternativa B é viável também, mas Z. Qual você aceita?"*
4. Você responde "aceito A" ou "aceito B"
5. Vira `docs/adr/0001-stack.md` com status `accepted`.

**Duração:** 1 sessão. **O que você faz:** ler 1 ou 2 parágrafos em português e escolher.

### Etapa 3 — Primeira feature visível
Depois da tecnologia escolhida, a próxima sessão vai construir **uma feature pequena mas visível**. Minha recomendação é começar pelo **TEN — cadastro de tenant** (criar um laboratório no sistema). É a porta de entrada de qualquer outra coisa e permite ver tela funcionando.

**Duração:** 2 a 4 sessões. **O que você faz:** abrir no navegador e testar. Se funcionar, aprovar. Se não, me dizer o que está errado em português.

### Etapa 4 — Crescer feature por feature, em ordem de valor
Depois da primeira feature, o sistema cresce em slices pequenos. Ordem provisória:

1. Cadastro de tenant (TEN) — porta de entrada
2. Cadastro de padrões de referência (MET) — base para toda calibração
3. Cadastro de procedimentos técnicos (MET) — os "manuais" das calibrações
4. Jornada 1 passos 1 a 5 (FLX) — registrar pedido novo até pôr na fila
5. Jornada 1 passos 6 a 8 (MET + FLX) — executar, aprovar, gerar certificado
6. NFS-e (FIS) — emissão fiscal no primeiro município
7. Dashboard operacional (OPL) — visão do gerente
8. Trilha de auditoria (CMP) — registro imutável + exportação RBC

Cada uma dessas **é uma feature visível.** Você vê, testa, aprova.

### Etapa 5 — Primeiro cliente pagante
Quando as 8 features acima estão no ar e testadas, o MVP está pronto para o primeiro laboratório pagante (critério de sucesso do PRD §10).

---

## O que NÃO é preocupação sua

Daqui pra frente, **não precisa entender** nada do que está abaixo. O agente cuida, e só te pergunta quando precisar de decisão em linguagem de produto:

- "Harness", "Bloco 1", "Bloco 2", ..., "Bloco 9"
- Arquivos em `docs/audits/`, `docs/decisions/`, `docs/reference/`
- `sealed files`, `relock`, `MANIFEST.sha256`
- `sub-agents`, `verify-slice`, `review-pr`
- Eval suite, mechanical diff check, observabilidade estruturada
- Qualquer coisa que termine em `.sh` ou `.json`

**Se o agente mencionar qualquer um desses termos em resposta pra você, ele errou a tradução.** Pode me parar e pedir em linguagem de produto.

---

## O que SIM é preocupação sua

- **Ler e validar o PRD** (`docs/product/PRD.md`) ao menos uma vez. Se tiver algo errado, me diz em português.
- **Responder decisões de produto em linguagem simples** (sim/não, aceito A ou B, faltou X, precisa Y).
- **Testar feature quando eu entregar tela funcionando.**
- **As 4 ações manuais fora do Claude Code** que já estavam na sua lista:
  1. Contratação do consultor de metrologia (golden tests GUM/ISO 17025)
  2. Contratação do consultor fiscal (golden tests NF-e/NFS-e/ICMS)
  3. Contratação do DPO fracionário (LGPD)
  4. NDA + proposta do advisor técnico externo

Estas 4 **não dependem do agente**. Dependem de você administrar. São independentes do caminho do produto acima.

---

## Próxima ação única

**Abrir uma sessão nova do Claude Code e pedir:** *"rodar decide-stack com base no PRD consolidado"*.

Isso vai:
- Fazer o agente ler o PRD que acabei de escrever
- Comparar 2 ou 3 tecnologias contra os requisitos não-funcionais (RPS, custo, RAM, retenção, multi-tenant, NFS-e multi-município)
- Te trazer uma recomendação em linguagem de produto
- Esperar você aceitar

Você responde "aceito A" ou "aceito B" ou "prefiro outra, qual seria C?" e a etapa 2 fecha.

---

## Como me parar se eu errar

Se em qualquer sessão o agente começar a falar em "Bloco 8", "harness-sdd-knowledge-base", "sealed files" ou qualquer jargão técnico **pra você**, responda:

> "R12. Em linguagem de produto."

Isso força o tradutor e me obriga a repetir a resposta em português claro.

R12 é a regra do nosso próprio harness que diz "toda saída ao PM passa pelo tradutor de linguagem de produto". Se eu violar, você tem o direito de me parar.

---

## Segurança máxima + intervenção humana mínima (regra vinculante)

**Em 2026-04-11 você instruiu explicitamente:** *"todo o ambiente tem que ser desenhado para ter o mínimo possível de intervenção humana, só quando obrigado... eu não sei nada de código, mas o sistema tem que ser seguro, o ambiente tem que reduzir a zero o risco do código ser gerado errado."*

Essa instrução virou **regra vinculante do projeto**. Está registrada em:
- `docs/decisions/pm-decision-direction-reversal-2026-04-11.md §8`
- `docs/policies/human-intervention-policy.md`

### Você só intervém em 5 situações (lista fechada)

1. **Iniciar sessão nova do Claude Code** (não tem jeito — o agente não se auto-inicializa).
2. **Responder decisão de produto em linguagem simples** ("sim", "não", "aceito A", "aceito B", "faltou X", "testei, funcionou/não funcionou").
3. **Testar tela quando eu entregar feature visível** (navegar como usuário, dizer em português se está certo).
4. **Executar 4+2 ações administrativas** que não dependem do agente: selar políticas no harness, contratar consultores (metrologia + fiscal), contratar DPO, negociar advisor técnico. Lista exata em `docs/policies/human-intervention-policy.md §4`.
5. **Autorizar incidente P0 crítico** (só uma vez, só em emergência, com assinatura explícita).

**Qualquer coisa fora dessas 5 situações eu resolvo sozinho ou escalo internamente (verifier + reviewer independentes).** Nunca passo a bola pra você.

### O que eu prometo em troca

- **Nenhum código vai pra produção sem 3 verificações independentes aprovarem** (verifier + reviewer + CI externo). Um "rejeitado" = retrabalho imediato.
- **Nenhum slice em categoria crítica** (metrologia, fiscal, compliance, segurança, cálculo, ADR, simplicidade) **começa sem a oficina daquela categoria estar 100% pronta.** Se precisa tocar cálculo de calibração, os testes-ouro do consultor de metrologia precisam estar no ar **antes**. Se precisa tocar NFS-e, os testes-ouro do consultor fiscal precisam estar no ar **antes**.
- **Slices em categoria não-crítica** (cadastro de tenant, dashboard, etc.) podem começar com oficina parcial, mas continuam obrigados aos 3 verificadores.
- **Hash-lock permanente no harness.** O agente não pode modificar a si mesmo — só via procedimento manual (relock) executado por você em terminal externo.
- **Pausa dura** em categoria crítica = você **não pode** aprovar override. Se o harness rejeitar 2x, slice aborta e vira incidente.

### Consequência para o caminho acima

- **Etapa 2 (escolher tecnologia):** sem mudança. Próxima ação continua sendo `/decide-stack`.
- **Etapa 3 (primeira feature):** continua sendo **TEN — cadastro de tenant**, porque cadastro é **categoria não-crítica** e pode começar com oficina parcial.
- **Etapas 4.2 em diante (que tocam metrologia, fiscal, compliance):** vão esperar os consultores serem contratados e os testes-ouro deles estarem no ar. Isso pode reordenar a lista provisória de features — mas a reordenação é automática, não depende de você.
- **Trilha paralela de consultores:** vira **crítica** para destravar metade do MVP. Quanto antes as contratações M1-M2 (metrologia) e F1-F2 (fiscal) forem fechadas, antes o produto pode crescer para os módulos de cálculo e nota fiscal.

### Tradução curta para você guardar

- **Você = PM.** Diz o que o produto faz, valida tela, nada mais.
- **Agente = equipe inteira de engenharia.** Decide tudo o que é técnico, mas é fiscalizado por 3 verificações independentes antes de qualquer merge.
- **Oficina = trilha de gates.** Só destrava código na velocidade em que os gates da categoria estiverem prontos.
- **Segurança sempre vence velocidade.** Se houver trade-off, o harness escolhe segurança, sem te perguntar.
