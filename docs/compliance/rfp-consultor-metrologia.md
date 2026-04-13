# RFP — Consultor(a) Especialista em Metrologia (GUM / ISO/IEC 17025)

**Emitido por:** Kalibrium (SaaS B2B para gestão de laboratórios de calibração/metrologia)
**Contratante:** roldaobatista
**Data:** 2026-04-10
**Status:** rascunho (aguarda revisão final do PM antes de publicação)

---

## 1. Resumo em 3 parágrafos

Estamos construindo o **Kalibrium**, um SaaS B2B voltado para laboratórios de calibração e metrologia no Brasil. O produto inclui um motor de cálculo de incerteza de medição que será usado para emitir **certificados de calibração sob ISO/IEC 17025**. Erro nesse motor = perda de acreditação RBC na próxima auditoria do INMETRO.

Um diferencial do projeto é que **100% do desenvolvimento é feito por agentes de IA** (Claude Code + harness disciplinado), com humano atuando apenas como Product Manager. Isso cria uma lacuna crítica: **nenhuma IA hoje tem confiabilidade suficiente para validar sozinha cálculos de propagação de incerteza com variáveis correlacionadas, graus de liberdade efetivos e distribuições não-normais.**

Por isso, **antes de liberar o primeiro slice do motor de cálculo**, precisamos de um pacote de casos golden (gabarito) validados manualmente por um(a) metrologista acreditado(a), que servirá como teste de conformidade obrigatório no pipeline de build. Todo commit que toque o módulo de metrologia será bloqueado se não passar nesses casos com 100% de acerto.

---

## 2. O problema que estamos tentando resolver

### 2.1. O risco técnico
LLMs modernos (incluindo o modelo que está construindo o Kalibrium) erram silenciosamente em:
- Sinais de covariância em propagação de incertezas com variáveis correlacionadas
- Fórmula de coeficientes de sensibilidade (derivadas parciais) em funções não-triviais
- Graus de liberdade efetivos via Welch-Satterthwaite quando há ≥ 3 fontes
- Arredondamento de incerteza conforme regra do GUM §7.2.6
- Distribuições não-normais (retangular, triangular, arco-seno) e seus fatores de cobertura

Validação por LLM ≠ validação por especialista. Um motor que parece correto no teste unitário pode emitir certificado com incerteza subestimada em casos de borda — e a IA que validou o teste não tem referência normativa independente.

### 2.2. A mitigação
Um conjunto de **~50 casos** cobrindo:
- Calibração de instrumentos comuns (paquímetro, micrômetro, balança analítica, termômetro digital, multímetro, manômetro, cronômetro)
- Cenários com variáveis correlacionadas (ex.: temperatura e dilatação térmica)
- Cenários com mix de distribuições
- Casos de borda documentados (ex.: cov = 0, cov = σ₁σ₂, graus de liberdade muito baixos)
- Casos extraídos de exemplos canônicos do próprio GUM / JCGM 100:2008 quando aplicável

Para cada caso, queremos **entradas + resultado esperado + justificativa normativa**, em formato CSV, para que o pipeline de testes consiga comparar numericamente.

---

## 3. Escopo do trabalho

### 3.1. Entregáveis (concretos)

**E1 — Arquivo `gum-golden-cases.csv`** com no mínimo **50 casos**, estrutura:

| Coluna | Tipo | Descrição |
|---|---|---|
| `case_id` | string | Identificador único (ex.: `GUM-PAQ-001`) |
| `category` | enum | Uma de: `length`, `mass`, `temperature`, `electric`, `pressure`, `time`, `volume` |
| `instrument_type` | string | Tipo de instrumento (ex.: `paquímetro 150mm resolução 0,02mm`) |
| `scenario` | string | Descrição curta em português (ex.: `3 medições repetidas + resolução + padrão de referência`) |
| `input_variables_json` | JSON | Array de objetos `{name, value, unit, distribution, std_uncertainty, degrees_of_freedom, sensitivity_coeff}` |
| `correlation_matrix_json` | JSON | Matriz de correlação entre variáveis (ou `null` se independentes) |
| `formula_description` | string | Modelo matemático Y = f(X₁, X₂, ...) em notação textual |
| `expected_combined_uncertainty` | number | u_c(y) esperado |
| `expected_effective_dof` | number | ν_eff via Welch-Satterthwaite |
| `expected_coverage_factor` | number | k para 95% de confiança |
| `expected_expanded_uncertainty` | number | U = k · u_c |
| `expected_result_with_uncertainty` | string | Formatado conforme GUM §7.2 (ex.: `(25,003 ± 0,015) mm, k=2`) |
| `normative_reference` | string | Cita seção específica (ex.: `JCGM 100:2008 §5.2.2`) |
| `edge_case_notes` | string | Vazio para casos normais; explica se é caso de borda |

**E2 — Documento `gum-methodology.md`** (em português) explicando:
- Quais fontes normativas foram usadas (GUM, JCGM suppl. 1 se aplicável, ISO 17025:2017)
- Metodologia de geração dos casos (aleatórios? canônicos? cobertura por categoria?)
- Quais tipos de erro os casos foram desenhados para pegar
- Limitações conhecidas (ex.: não cobre Monte Carlo, não cobre distribuições multimodais)
- Critério de revisão recomendado (ex.: "revisar a cada mudança de norma ou a cada 12 meses")

**E3 — Revisão técnica do glossário de domínio** `docs/glossary-domain.md`:
- Corrigir termos mal definidos na seção metrologia
- Adicionar termos canônicos faltantes
- Apontar qualquer confusão conceitual que possa induzir erro de implementação

**E4 — Sessão de handoff** (1-2h, remota, gravada) onde o(a) consultor(a):
- Explica os casos mais sensíveis e por quê
- Responde perguntas técnicas do PM
- Deixa claro o que pode ser automatizado e o que **sempre** precisa de revisão humana

### 3.2. Fora de escopo (explicitamente)

- Implementação do motor de cálculo — quem implementa são os agentes de IA sob revisão
- Revisão de código — não queremos consultor fazendo code review, queremos gabarito independente
- Definição de arquitetura de software — isso é decisão interna do harness
- Emissão de certificado formal de calibração para o produto — consultor cria casos, não emite laudo
- Definição de funcionalidade de produto — escopo é compliance, não UX

---

## 4. Perfil desejado

### 4.1. Obrigatório
- Formação em metrologia, engenharia, física ou afim — com experiência prática em cálculo de incerteza
- **Certificação ou vínculo ativo com laboratório acreditado RBC/INMETRO** (ou equivalente internacional ILAC-MRA)
- Domínio fluente do JCGM 100:2008 (GUM) — não só leitura, mas ter aplicado em casos reais
- Capacidade de executar cálculos à mão / em planilha e documentar o raciocínio (não confia cegamente em software comercial)
- Português fluente (leitura + escrita técnica)

### 4.2. Desejável
- Experiência prévia com ISO/IEC 17025:2017 (gestão da qualidade do laboratório)
- Familiaridade com JCGM 101:2008 (Supplement 1 — Monte Carlo), mesmo que não seja usado neste escopo
- Noção básica de desenvolvimento de software (não precisa codar, só entender que "teste automatizado" compara número a número)
- Já ter atuado como auditor técnico ou avaliador Cgcre

### 4.3. Não precisa
- Saber programar
- Saber do framework usado no Kalibrium (ainda não decidimos — ADR-0001)
- Conhecer IA ou LLMs — apenas aceitar que o consumidor do trabalho dele é um pipeline automatizado

---

## 5. Critério de aceite (como saberemos que está pronto)

O trabalho será considerado aceito quando:

- ✅ `gum-golden-cases.csv` entregue com **≥ 50 casos válidos**
- ✅ Cada caso tem fonte normativa citada (não pode ser "eu acho que é assim")
- ✅ Cada caso tem unidade, distribuição e incerteza de entrada **consistentes** entre si (validado por script automático nosso)
- ✅ Distribuição equilibrada entre categorias (≥ 5 casos em pelo menos 5 categorias das 7)
- ✅ Pelo menos 10 casos envolvem **variáveis correlacionadas** (não só independentes)
- ✅ Pelo menos 5 casos marcados como **edge cases** documentados
- ✅ `gum-methodology.md` entregue e legível
- ✅ Sessão de handoff realizada
- ✅ Consultor(a) assina declaração de que os casos foram validados sob sua responsabilidade técnica (para fins de rastreabilidade interna)

---

## 6. Cronograma sugerido

| Fase | Duração | Marco |
|---|---|---|
| Kick-off + alinhamento de escopo | 1 semana | Reunião de abertura, ajuste fino desta RFP |
| Execução — geração dos casos | 3-5 semanas | Entrega parcial a cada ~20 casos |
| Revisão interna + feedback | 1 semana | PM confirma que o CSV passa no parser automático |
| Handoff + fechamento | 3 dias | Sessão gravada + aceite formal |

**Estimativa de esforço:** 20–40 horas técnicas (varia conforme profundidade dos edge cases). Consultor(a) propõe na resposta.

---

## 7. Modelo de contratação e pagamento

- **Formato:** freelance / PJ (prestador de serviços)
- **Base de cálculo:** por hora técnica **ou** por escopo fechado — consultor escolhe e justifica na proposta
- **Faixa de referência inicial:** a ser negociada; abrir proposta na resposta
- **Pagamento:** 30% na assinatura do contrato, 40% na entrega parcial dos primeiros 25 casos, 30% no aceite final
- **NDA:** contrato padrão de confidencialidade assinado antes do kick-off
- **Propriedade intelectual:** os casos gerados pertencem ao Kalibrium; consultor(a) mantém direito de citar o trabalho em portfólio sem expor o conteúdo específico
- **Revisões pós-entrega:** até 2 rodadas de ajuste dentro de 30 dias após aceite incluídas no escopo

---

## 8. Confidencialidade

O Kalibrium está em fase de pré-lançamento. O consultor(a) terá acesso a:
- Este RFP
- Glossário de domínio (`docs/glossary-domain.md`)
- Visão de produto congelada (`docs/reference/ideia-v1.md`)
- Constituição técnica do projeto (somente para contexto — não precisa seguir)

**Não** terá acesso a:
- Código-fonte do motor de cálculo (não existe ainda)
- Especificações de slices futuros
- Decisões de arquitetura interna

NDA padrão será assinado antes de qualquer entrega de documento interno. Consultor(a) não deve comentar publicamente sobre o projeto até o lançamento oficial.

---

## 9. Como responder a esta RFP

Envie por e-mail ou canal escolhido pelo contratante contendo:

1. **Mini-CV técnico** (1 página) focado em metrologia e acreditação — pode ser link para LinkedIn se preferir
2. **Comprovante** de vínculo atual ou passado com laboratório RBC (ou equivalente) — nome do laboratório, período, papel
3. **Exemplo concreto** de cálculo de incerteza feito por você em caso real — 1 página, pode anonimizar cliente
4. **Proposta comercial**: modelo de contratação (hora ou escopo), valor, cronograma estimado
5. **3 referências profissionais** com contato
6. **Disponibilidade** para kick-off nas próximas 2 semanas (sim/não/quando)

**Prazo de envio:** a ser definido pelo contratante
**Canal:** a ser definido pelo contratante
**Previsão de resposta:** até 7 dias úteis após recebimento

---

## 10. Perguntas frequentes (antecipadas)

**P: Posso usar software comercial (GUM Workbench, LabDeviceSoft, etc) para gerar os casos?**
R: Pode usar como ferramenta de apoio, mas **não pode entregar apenas output de software**. Cada caso precisa da sua validação manual (mesmo que curta) e da fonte normativa. Queremos gabarito humano, não gabarito de caixa-preta.

**P: E se eu discordar de uma instrução desta RFP ou do glossário?**
R: Ótimo. Coloque a discordância na proposta. Ajustamos o escopo no kick-off.

**P: Posso propor escopo diferente (mais ou menos casos, outras categorias)?**
R: Sim, desde que justifique. A meta é "cobertura suficiente para bloquear erros metrológicos dos tipos mais comuns", não um número mágico.

**P: O cronograma é rígido?**
R: O cronograma sugerido é indicativo. Negociável no kick-off. O que não é negociável é o critério de aceite.

**P: E se eu encontrar um erro no glossário de domínio durante o trabalho?**
R: Registre e entregue junto. Item E3 da lista de entregáveis cobre isso explicitamente e é remunerado como parte do escopo.

---

## 11. Por que trabalhar neste projeto

- Você vai criar o **gabarito de verdade** para um motor de cálculo que vai ser usado por laboratórios reais no Brasil
- Seu trabalho vai estar documentado e citado em cada slice que tocar o módulo de metrologia
- É oportunidade de influenciar diretamente como IA faz software crítico em domínio regulado — e seu nome fica no paper trail
- O contratante é PM não-técnico que **leva compliance a sério** e não vai pedir pra você cortar canto

---

**Fim da RFP.**

*Documento aberto a revisão. Envie comentários antes de aceitar o escopo. Toda ambiguidade no papel resolve-se antes do contrato, nunca depois.*
