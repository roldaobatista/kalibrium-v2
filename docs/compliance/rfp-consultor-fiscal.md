# RFP — Consultor(a) Especialista Fiscal (NF-e / NFS-e / ICMS multi-UF)

**Emitido por:** Kalibrium (SaaS B2B para gestão de laboratórios de calibração/metrologia)
**Contratante:** roldaobatista
**Data:** 2026-04-10
**Status:** rascunho (aguarda revisão final do PM antes de publicação)

---

## 1. Resumo em 3 parágrafos

Estamos construindo o **Kalibrium**, um SaaS B2B que será usado por laboratórios de calibração no Brasil para emitir **notas fiscais eletrônicas** (NF-e para produtos, NFS-e para serviços de calibração) como parte do fluxo de operação. Erro em campo fiscal = rejeição SEFAZ → cliente em malha fiscal → perda de credibilidade do produto + potencial passivo tributário.

Um diferencial do projeto é que **100% do desenvolvimento é feito por agentes de IA** (Claude Code + harness disciplinado), com humano atuando apenas como Product Manager. Isso cria uma lacuna crítica: **a legislação fiscal brasileira muda por convênio CONFAZ, portaria SEFAZ e diário oficial estadual, e modelos de IA têm cutoff de conhecimento desatualizado**. Um modelo treinado até maio/2025 não conhece convênios publicados depois disso. A reforma tributária em curso (LC 214/2025 + transição 2026-2032) só agrava.

Por isso, **antes de liberar o primeiro slice de emissão fiscal**, precisamos de um pacote de casos golden (gabarito) validados por um(a) contador(a)/analista fiscal atual, que servirá como teste de conformidade obrigatório no pipeline. Todo commit que toque o módulo fiscal será bloqueado se não passar nesses casos com 100% de acerto.

---

## 2. O problema que estamos tentando resolver

### 2.1. O risco técnico
LLMs erram silenciosamente em:
- **CST / CSOSN do ICMS** por UF e por tipo de operação (venda, devolução, remessa, industrialização por encomenda)
- **Alíquotas interestaduais** e diferencial de alíquota (DIFAL) — regras diferentes pré e pós EC 87/2015, mudanças em 2024-2025
- **CFOP** — escolher o código errado entre dezenas de opções próximas
- **Enquadramento em regime tributário** (Simples Nacional, Lucro Presumido, Lucro Real) e suas implicações
- **Carta de Correção Eletrônica (CC-e)** — o que pode ou não ser corrigido por CC-e (limitado)
- **Cancelamento de NF-e** dentro do prazo legal (24h para cancelamento "normal", depois só por extemporâneo)
- **NFS-e** (serviços) que segue regras municipais — cada município tem layout e aliquotas próprias do ISS
- **Reforma tributária** — transição IBS/CBS começando em 2026-2027, regras ainda sendo publicadas

### 2.2. A mitigação
Um conjunto de **~30 casos** cobrindo:
- Operações típicas de um laboratório de calibração: venda de serviço de calibração, revenda de instrumento calibrado (raro), devolução, remessa para assistência técnica
- Operações **interestaduais** cobrindo pelo menos 5 UFs principais (SP, MG, RJ, RS, PR)
- Operações **municipais** cobrindo NFS-e em pelo menos 3 municípios com regras distintas
- Casos de **cancelamento**, **CC-e**, **devolução total e parcial**
- Pelo menos **3 casos sob reforma tributária** (LC 214/2025) para cobertura da transição

Para cada caso, queremos **entradas operacionais + output esperado (XML-like) + justificativa legal citada**.

---

## 3. Escopo do trabalho

### 3.1. Entregáveis (concretos)

**E1 — Arquivo `fiscal-golden-cases.csv`** com no mínimo **30 casos**, estrutura:

| Coluna | Tipo | Descrição |
|---|---|---|
| `case_id` | string | Identificador único (ex.: `FIS-NFE-SP-001`) |
| `document_type` | enum | Uma de: `NFe`, `NFSe`, `NFCom`, `CCe`, `Cancelamento`, `DevolucaoTotal`, `DevolucaoParcial` |
| `uf_origin` | string | UF de origem (ex.: `SP`) |
| `uf_destination` | string | UF de destino (ex.: `MG`); ou `MUNICIPAL` para NFS-e |
| `regime_tributario` | enum | `simples_nacional`, `lucro_presumido`, `lucro_real` |
| `operation_description` | string | Descrição curta em português (ex.: `venda de serviço de calibração de paquímetro para cliente interestadual`) |
| `input_fields_json` | JSON | Valor operação, CFOP proposto, NCM, código do serviço (LC 116/03), destinatário (PF/PJ, IE), etc |
| `expected_cfop` | string | CFOP correto |
| `expected_cst_or_csosn` | string | CST ICMS (Lucro) ou CSOSN (Simples) |
| `expected_icms_aliquota` | number | Alíquota ICMS aplicável (0 se isento/suspenso) |
| `expected_icms_base_calculo_json` | JSON | Detalhamento da base de cálculo |
| `expected_ipi_aliquota` | number | Se aplicável |
| `expected_pis_cofins_json` | JSON | Regime + alíquotas quando aplicável |
| `expected_iss_aliquota` | number | Quando NFS-e |
| `expected_iss_municipio_incidente` | string | Quando NFS-e |
| `expected_total_notas_json` | JSON | Breakdown do total (base, impostos, líquido) |
| `expected_xml_excerpt` | string | Trecho do XML com os campos críticos (pode ser parcial) |
| `legal_reference` | string | Cita artigo de lei, convênio, portaria (ex.: `Convênio ICMS 142/2018 cláusula 9ª` / `RICMS-SP art. 52` / `LC 116/2003 anexo item 14.01`) |
| `reforma_tributaria_applies` | boolean | `true` se o caso envolve regras de transição IBS/CBS |
| `edge_case_notes` | string | Vazio para casos normais; explica se é caso sensível |

**E2 — Documento `fiscal-methodology.md`** (em português) explicando:
- Fontes normativas usadas (RICMS de cada UF tocada, LC 87/96, LC 116/03, LC 214/2025, convênios CONFAZ relevantes)
- Data de corte da legislação usada (ex.: "legislação vigente em 2026-03-31; mudanças pós-cutoff não refletidas")
- Metodologia: cobertura por tipo de operação, por UF, por regime
- Limitações conhecidas (quais municípios foram cobertos, quais regras de exceção ficaram de fora)
- **Critério de revalidação obrigatória:** recomendar frequência mínima de revisão dos casos (trimestral? semestral?). Legislação fiscal envelhece rápido — o teste de hoje pode estar errado em 6 meses.

**E3 — Revisão técnica do glossário de domínio** `docs/glossary-domain.md`:
- Corrigir termos fiscais mal definidos
- Adicionar termos canônicos faltantes (IBS, CBS, CFOP, CSOSN, base reduzida, etc)
- Apontar qualquer confusão conceitual entre ICMS/ISS/IPI que possa induzir erro de implementação

**E4 — Mapa de risco por módulo** — documento curto (2-3 páginas) listando:
- Quais módulos fiscais do Kalibrium têm risco alto (precisam de revalidação frequente)
- Quais são estáveis (legislação consolidada)
- O que **não deve ser implementado por IA sem revisão humana recorrente** (potenciais bombas-relógio)

**E5 — Sessão de handoff** (1-2h, remota, gravada) onde o(a) consultor(a):
- Explica casos mais sensíveis e cenários de rejeição SEFAZ comuns
- Responde perguntas técnicas do PM
- Deixa claro o que pode ser automatizado e o que **sempre** precisa de revisão humana (ou terceirização para emissor fiscal especializado)

### 3.2. Fora de escopo (explicitamente)

- Implementação do emissor fiscal propriamente dito
- Integração direta com SEFAZ/Receita Federal
- Homologação do ambiente de produção (isso é responsabilidade do Kalibrium + possível parceria com emissor fiscal terceiro tipo Migrate, Focus NFe, eNotas)
- Definição de arquitetura de software
- Parecer tributário formal sobre a operação comercial do Kalibrium (isso é outra conversa, outro contrato)

---

## 4. Perfil desejado

### 4.1. Obrigatório
- Contador(a), analista fiscal ou advogado(a) tributarista **em atividade**
- **Experiência prática** com emissão de NF-e e NFS-e — não só teoria
- **Multi-UF**: ter lidado com operações em pelo menos 5 UFs diferentes, cada uma com suas regras
- Domínio fluente de CFOP, CST/CSOSN e ICMS interestadual
- Acompanhamento ativo da reforma tributária (LC 214/2025) — não precisa ser especialista, mas tem que estar lendo as atualizações
- Português fluente (leitura + escrita técnica)

### 4.2. Desejável
- Já ter consultado para empresa de tecnologia antes (entende que o consumidor do trabalho é software)
- Experiência específica com laboratórios, prestadores de serviço técnico ou setor de aferição
- Ter acompanhado implementação de sistema de emissão próprio (não só usuário final de sistema pronto)
- Ter noção básica do que é "teste automatizado" (consegue imaginar que alguém vai comparar o CSV dele com o output de um software rodando)

### 4.3. Não precisa
- Saber programar
- Conhecer o framework do Kalibrium (ainda não decidimos — ADR-0001)
- Conhecer IA ou LLMs

---

## 5. Critério de aceite

- ✅ `fiscal-golden-cases.csv` entregue com **≥ 30 casos válidos**
- ✅ Cada caso tem **fonte legal citada** (não pode ser "isso é padrão")
- ✅ Cobertura mínima: ≥ 5 UFs distintas, ≥ 3 municípios distintos, ≥ 3 tipos de documento diferentes
- ✅ Pelo menos **3 casos sob reforma tributária** (IBS/CBS)
- ✅ Pelo menos **5 casos de operações "negativas"** (cancelamento, CC-e, devolução)
- ✅ Pelo menos **3 casos de borda** explicitamente marcados (ex.: DIFAL interestadual para destinatário não-contribuinte, NFS-e com retenção de ISS na fonte)
- ✅ `fiscal-methodology.md` com data de corte explícita
- ✅ `risk-map.md` (E4) entregue
- ✅ Sessão de handoff realizada
- ✅ Declaração de responsabilidade técnica assinada

---

## 6. Cronograma sugerido

| Fase | Duração | Marco |
|---|---|---|
| Kick-off + alinhamento de escopo | 1 semana | Reunião de abertura, ajuste fino desta RFP |
| Execução — geração dos casos | 3-5 semanas | Entrega parcial a cada ~10 casos |
| Revisão interna + feedback | 1 semana | PM confirma que o CSV passa no parser automático |
| Handoff + fechamento | 3 dias | Sessão gravada + aceite formal |

**Estimativa de esforço:** 15–30 horas técnicas. Consultor(a) propõe na resposta.

---

## 7. Modelo de contratação e pagamento

- **Formato:** freelance / PJ
- **Base de cálculo:** por hora técnica ou escopo fechado — consultor(a) escolhe e justifica
- **Faixa de referência inicial:** a definir na negociação
- **Pagamento:** 30% na assinatura, 40% na entrega dos primeiros 15 casos, 30% no aceite final
- **NDA:** contrato padrão de confidencialidade assinado antes do kick-off
- **Propriedade intelectual:** casos pertencem ao Kalibrium; consultor(a) mantém direito de citar em portfólio sem expor conteúdo
- **Revalidação anual:** opção (não obrigatória) de contrato recorrente de revalidação anual dos casos, a ser negociada separadamente — legislação fiscal envelhece

---

## 8. Confidencialidade

Consultor(a) terá acesso a:
- Este RFP
- Glossário de domínio (`docs/glossary-domain.md`)
- Visão de produto congelada (`docs/reference/ideia-v1.md`)
- Descrição operacional do laboratório-tipo (a ser fornecida)

**Não** terá acesso a:
- Código-fonte do emissor fiscal (não existe ainda)
- Informações de clientes (o Kalibrium está em pré-lançamento — não há clientes reais)
- Decisões de arquitetura interna

NDA padrão assinado antes de qualquer entrega de documento interno. Consultor(a) não comenta publicamente sobre o projeto até lançamento oficial.

---

## 9. Como responder a esta RFP

Envie por e-mail ou canal escolhido pelo contratante contendo:

1. **Mini-CV técnico** (1 página) focado em fiscal/tributário
2. **Registro profissional ativo** (CRC, OAB, ou registro equivalente)
3. **Exemplo concreto** de caso complexo resolvido por você — 1 página, anonimizado
4. **Proposta comercial**: modelo, valor, cronograma
5. **3 referências profissionais** com contato
6. **Disponibilidade** para kick-off nas próximas 2 semanas

**Prazo de envio:** a ser definido
**Canal:** a ser definido
**Previsão de resposta:** até 7 dias úteis

---

## 10. Perguntas frequentes antecipadas

**P: Posso usar software comercial (emissor fiscal) para gerar os casos?**
R: Pode usar como ferramenta de apoio. **Não pode entregar apenas o output** — queremos sua validação manual e a citação normativa. Software comercial pode estar desatualizado ou ter bug silencioso; o objetivo é justamente ter gabarito independente.

**P: E se a legislação mudar durante o trabalho?**
R: Documente no `fiscal-methodology.md` a data de corte. Mudanças posteriores ficam para revalidação futura.

**P: Posso propor cobertura diferente (outras UFs, outros tipos de operação)?**
R: Sim, justifique na proposta. A meta é "cobertura suficiente para pegar erros fiscais dos tipos mais comuns" e mitigação do cutoff do modelo de IA — não um número mágico.

**P: E os aspectos de compliance LGPD dos dados fiscais dos clientes?**
R: Fora do escopo deste contrato específico. Será tratado em contrato separado, possivelmente com DPO.

**P: Preciso tratar reforma tributária em profundidade?**
R: Preciso: ≥ 3 casos que exercitem regras da LC 214/2025 (transição IBS/CBS). Não preciso: cobertura exaustiva da reforma — ela ainda está em regulamentação e consolidar agora seria desperdício.

**P: E se eu encontrar erro no glossário?**
R: Registre e entregue junto (Entregável E3). Remunerado como parte do escopo.

---

## 11. Por que trabalhar neste projeto

- Você vai criar o **gabarito de verdade** para emissão fiscal de um produto brasileiro real
- O Kalibrium leva compliance a sério — não vai pedir pra você cortar canto
- Oportunidade de influenciar diretamente como IA trata legislação fiscal brasileira em domínio regulado
- Possibilidade de contrato recorrente de revalidação anual (legislação envelhece rápido, relacionamento de longo prazo é desejável)

---

**Fim da RFP.**

*Documento aberto a revisão. Envie comentários antes de aceitar o escopo. Toda ambiguidade no papel resolve-se antes do contrato, nunca depois.*
