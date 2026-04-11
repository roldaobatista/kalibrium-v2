# Meta-auditoria #2 — Consolidação das 3 auditorias de completude do Kalibrium V2

## 1. Identificação do pedido

Este pedido vem do **Product Manager** (PM) do Kalibrium V2. O PM **não é desenvolvedor**. Ele precisa de uma leitura única, comparativa e acionável das três auditorias externas de completude que foram produzidas por modelos diferentes, porque não tem competência técnica para ler e reconciliar as três sozinho.

Você é o **meta-auditor**. Não é autor do projeto, não é autor do harness, não é autor do plano de blocos, e não é autor das 3 auditorias externas. Você está em sessão nova, deliberadamente isolada da sessão que planejou o trabalho — para reduzir viés confirmatório. A regra `feedback_meta_audit_isolation` aplicada recursivamente exige isto.

## 2. Contexto do projeto

- **Produto:** Kalibrium V2, SaaS B2B brasileiro para gestão de laboratórios de calibração e metrologia
- **Domínio regulado:** INMETRO, ISO/IEC 17025, NF-e/NFS-e multi-UF, REP-P, ICP-Brasil, LGPD, reforma tributária 2026-2033
- **Modelo operacional:** 1 humano (PM, não-técnico) + N sub-agentes de IA
- **Estado:** Bloco 1 do plano de endurecimento do harness já foi concluído e pushado (harness selado contra auto-modificação). Próximos blocos aguardam. Antes de iniciar Bloco 2 (decisão de stack), o PM teve dúvida sobre **completude do ambiente de desenvolvimento** e abriu uma segunda rodada de auditorias externas — a auditoria #1 cobriu enforcement técnico do harness, a #2 cobre governança de produto, fundações conceituais, processos operacionais e compliance de domínio
- **Contexto anterior:** há uma meta-auditoria #1 em `docs/audits/meta-audit-2026-04-10.md` que consolidou as 3 primeiras auditorias (focadas em enforcement técnico). Esta meta-auditoria #2 segue o mesmo padrão, mas cobre a rodada #2 (focada em completude)

## 3. Sua missão

Fazer **meta-auditoria comparativa** das 3 auditorias externas de completude, respondendo a pergunta central:

> **"Onde os 3 auditores concordam (alta confiança de problema real), onde divergem (subjetividade ou diferença de rigor), e onde cada um trouxe insight único que os outros não viram?"**

A partir disso, produzir **plano de ação** em linguagem de produto (R12) para o PM consumir.

## 4. Materiais obrigatórios de entrada

Antes de escrever qualquer parágrafo, leia **nesta ordem**:

### 4.1. Contexto do projeto (primeiros)
1. `CLAUDE.md` — instruções operacionais
2. `docs/constitution.md` — P1-P9 + R1-R12 (especialmente R12 — linguagem de produto)
3. `docs/incidents/pr-1-admin-merge.md` — por que o modelo operacional é humano=PM
4. `docs/audits/meta-audit-2026-04-10.md` — meta-auditoria #1 (enforcement), para entender o padrão
5. `docs/audits/meta-audit-2026-04-10-action-plan.md` — plano de 7 blocos vigente
6. `docs/audits/progress/meta-audit-tracker.md` — estado atual do progresso
7. `docs/decisions/pm-decision-meta-audit-2026-04-10.md` — decisões do PM sobre o plano
8. `docs/audits/completeness-audit-prompt-2026-04-10.md` — prompt que gerou as 3 auditorias #2

### 4.2. As 3 auditorias a consolidar (centrais)
9. `docs/audits/external/audit-claude-completeness-2026-04-10.md`
10. `docs/audits/external/audit-codex-completeness-2026-04-10.md`
11. `docs/audits/external/audit-gemini-completeness-2026-04-10.md`

Se qualquer um dos 3 não existir, pare e sinalize — é sinal de que o PM ainda não coletou todas as auditorias.

### 4.3. Evidência técnica (apoio opcional)
12. `docs/reference/ideia-v1.md` — visão de produto congelada
13. `docs/glossary-domain.md` — termos do domínio
14. `docs/adr/` — todos os ADRs existentes
15. Árvore do repositório — para verificar se afirmações dos auditores batem com a estrutura real

## 5. Tarefa específica

### 5.1. Tabela comparativa dos 3 auditores
Para cada uma das dimensões do prompt de auditoria #2 (seções A-K do prompt em `completeness-audit-prompt-2026-04-10.md`), produza uma tabela de 3 colunas (Claude / Codex / Gemini) mostrando o veredito de cada um (presente / parcial / ausente). Isto dá uma visão panorâmica imediata.

### 5.2. Consenso (prioridade máxima)
Identifique todos os itens onde **pelo menos 2 dos 3 auditores** apontam o mesmo gap. Para cada item de consenso:
- Cite literalmente o que cada auditor disse (com referência à seção da auditoria dele)
- Explique o que o plano de 7 blocos atual cobre e o que **não** cobre
- Classifique: **bloqueante** (não pode avançar sem resolver), **alta prioridade** (resolver nos próximos 2 blocos), **backlog** (importante mas não urgente)

### 5.3. Divergência
Identifique onde os auditores se contradisseram entre si. Para cada divergência:
- Quem disse o quê
- Qual é a interpretação mais fiel à realidade do projeto (você julga, com base na evidência do repositório)
- Se há risco de um deles ter sido superficial, registre

### 5.4. Insights únicos
Identifique achados que **apenas um auditor** trouxe, que os outros dois não viram. Para cada insight único:
- Qual auditor, em qual seção
- Por que é valioso mesmo sem corroboração
- Classifique: **aceitar**, **descartar**, **investigar mais**

### 5.5. Plano de ação revisado
Com base no consenso + insights únicos aceitos, proponha **revisões ao plano de 7 blocos** vigente em `docs/audits/meta-audit-2026-04-10-action-plan.md`. Especificamente:

- **Novos blocos ou sub-blocos** a inserir (ex.: "Bloco 1.5 — Fundação de Produto" antes do Bloco 2)
- **Ajustes a blocos existentes** (itens novos, reordenação)
- **Itens removidos** (se algum auditor convencer que algo do plano é desnecessário)
- **Trilhas paralelas adicionais** (além das que já existem de metrologia e fiscal)

**Cada mudança proposta deve ter:**
- Justificativa (qual auditoria ou conjunto apontou)
- Impacto em blocos existentes (o que é afetado)
- Estimativa grosseira de esforço (em número de sub-itens, não em tempo)

### 5.6. Resposta direta ao PM

No fim do documento, uma seção **"Para o PM, em 1 página"** contendo:

1. **Veredito binário:** o plano vigente pode ser executado como está, precisa ajustes leves, ou precisa reformulação antes de avançar?
2. **Recomendação única:** qual a próxima ação imediata (em linguagem de produto)?
3. **3-5 decisões** que o PM precisa tomar (formato: pergunta + opções sim/não/ajustar, sem jargão)
4. **O que o PM não precisa decidir** — itens onde o consenso é tão forte que o meta-auditor já recomenda sem pedir sim/não

## 6. Formato de saída

Produza um único arquivo Markdown em:

`docs/audits/meta-audit-completeness-2026-04-10.md`

Estrutura obrigatória:

```
# Meta-auditoria #2 — Consolidação das 3 auditorias de completude

**Data:** [data]
**Meta-auditor:** Claude Opus 4.6 (1M context), sessão nova, isolada da sessão que planejou o trabalho
**Escopo:** consolidar audit-claude-completeness / audit-codex-completeness / audit-gemini-completeness
**Público:** Product Manager (linguagem de produto — R12)
**Contexto:** esta é a segunda rodada de meta-auditoria do projeto. A primeira (`meta-audit-2026-04-10.md`) cobriu enforcement técnico do harness. Esta cobre completude de ambiente de desenvolvimento

---

## 0. Resumo em uma página (para o PM)
[veredito + próxima ação única + 3-5 decisões]

## 1. Comparativo panorâmico dos 3 auditores
[tabelas por dimensão A-K]

## 2. Consenso (prioridade máxima)
[itens onde 2+ concordam]

## 3. Divergência
[onde se contradisseram, julgamento do meta-auditor]

## 4. Insights únicos
[achados que só um viu]

## 5. Plano de ação revisado
[novos blocos, ajustes, remoções]

## 6. Resposta direta ao PM
[versão detalhada da seção 0]

## 7. Apêndice — rastreabilidade
[arquivos fonte citados]
```

## 7. Restrições e instruções de conduta

1. **Linguagem de produto (R12)** para qualquer seção destinada ao PM. Vocabulário técnico é permitido nas seções 1-5 (para rastreabilidade interna), mas a seção 0 e a seção 6 devem ser compreensíveis por um Product Manager que não programa. Cite palavras proibidas do glossário R12 e substitua por equivalentes.

2. **Olhar fresco.** Você não escreveu o plano de 7 blocos. Se os auditores apontarem problemas no plano, registre honestamente. Não defenda o plano por deferência. O valor desta meta-auditoria é detectar onde o autor do plano errou.

3. **Evidência antes de afirmação (P7).** Qualquer afirmação sobre o que um auditor disse deve ter citação específica (arquivo + seção). Nada de parafrasear livremente.

4. **Nada de bajulação.** O PM explicitamente quer crítica honesta. Elogio só onde houver evidência.

5. **Neutralidade entre auditores.** Não favoreça um modelo específico a priori. Julgue cada afirmação pelo mérito, não pela reputação do modelo.

6. **Se encontrar erro nas próprias auditorias** (ex.: Claude Opus citou um arquivo que não existe, ou Codex contradisse evidência concreta), registre. Auditor pode errar também.

7. **Se o plano de 7 blocos já endereça algo** que um auditor apontou como gap, reconheça isso explicitamente em vez de registrar como consenso de gap falso.

8. **Não implemente nada.** Esta sessão é exclusivamente de leitura e análise. Nenhuma edição de código, nenhuma criação de novos hooks, nenhuma mudança em `settings.json`, nenhum commit. Apenas o arquivo de meta-auditoria.

## 8. Critérios de qualidade da meta-auditoria

Você será avaliado por:

1. **Honestidade** — se você engolir o plano por deferência ao autor, falhou. Se você detectar que o autor errou em etapa crítica, reporte com calma e evidência.
2. **Utilidade operacional** — o PM precisa saber o que fazer amanhã, não receber um ensaio acadêmico.
3. **Tradução fiel à R12** — se a seção 0 tiver jargão técnico que o PM não entende, falhou.
4. **Rastreabilidade** — cada afirmação tem que ser rastreável para o arquivo fonte.
5. **Coragem de divergir** — se o consenso dos 3 auditores é errado, tem coragem de dizer e justificar.

## 9. Entregável

Um único arquivo Markdown em `docs/audits/meta-audit-completeness-2026-04-10.md`, seguindo a estrutura da seção 6, em português, entregue em uma única mensagem ao fim da leitura. Se algum dos 3 arquivos de auditoria (`audit-*-completeness-*.md`) não existir, pare e sinalize antes de gerar a meta-auditoria — não invente conteúdo.

---

Vá com honestidade cirúrgica. O PM está nesta etapa justamente porque desconfiou de um plano que um Claude anterior produziu. Esta meta-auditoria é a chance de mostrar a ele o que está faltando antes que ele invista recursos em blocos que estão construídos em fundação incompleta.
