# 00 — Protocolo Operacional da Fabrica de Software — Kalibrium V2

> Documento normativo mestre. Versao 1.1.0 — 2026-04-16.
> Status: aprovado pelo PM.

---

## 1. Objetivo e escopo

Este protocolo governa TODAS as operacoes de agents na fabrica de software Kalibrium V2. O protocolo e normativo — todos os agents devem cumpri-lo. Violacoes sao findings.

**Escopo de aplicacao:**

1. Todo agent listado em CLAUDE.md secao 8 deve operar conforme este protocolo.
2. Todo slice, independente da trilha (hotfix, small, standard, high-risk), deve passar pelos gates definidos neste protocolo.
3. Todo artefato produzido deve seguir os contratos definidos neste protocolo.
4. Toda excecao ao fluxo normal deve seguir a politica de excecoes definida neste protocolo.

**Fora de escopo:**

1. Decisoes de produto — pertencem ao PM.
2. Escolhas de stack — pertencem aos ADRs (docs/adr/).
3. Regras de negocio — pertencem ao PRD e specs.

---

## 2. Principios normativos

### 2.1. Regras de redacao do protocolo

1. Toda regra deve usar "deve", "nao pode" ou "somente pode". Linguagem aspiracional ("deveria", "idealmente", "recomenda-se") nao e permitida.
2. Toda excecao deve ter owner e deadline. Excecoes sem responsavel ou prazo nao sao validas.
3. Todo gate deve ter input, output e evidencia definidos. Gate sem contrato nao pode ser executado.
4. Quem produz nao confere. O agent que gera um artefato nao pode ser o mesmo que o audita (principio de cross-review).
5. Evidencia precede afirmacao. Nenhum agent pode declarar "pronto", "aprovado" ou "corrigido" sem evidencia verificavel (comando executado, output capturado, exit code registrado).

### 2.2. Principios da constituicao (P1-P9)

Este protocolo opera sob os principios P1-P9 definidos em `docs/constitution.md` secao 2. Em caso de conflito entre este protocolo e a constituicao, a constituicao prevalece.

| Principio | Resumo |
|---|---|
| P1 | Gate objetivo precede opiniao de agent. |
| P2 | AC e teste executavel, escrito antes do codigo. |
| P3 | Verificacao em contexto isolado por pacote de input e sandbox. |
| P4 | Hooks executam, nao so formatam. |
| P5 | Uma fonte de verdade para instrucoes. |
| P6 | Commits atomicos com autor identificavel. |
| P7 | Verificacao de fato antes de afirmacao. |
| P8 | Piramide de escalacao de testes. |
| P9 | Nada de bypass de gates. |

---

## 3. Definicoes e glossario operacional

| Termo | Definicao |
|---|---|
| **Finding** | Problema identificado por um agent de gate durante auditoria. Deve ter ID, severidade, descricao, evidencia e agent emissor. |
| **Gate** | Ponto de verificacao obrigatorio no pipeline. Deve ter input definido, output estruturado (JSON) e criterios objetivos de aprovacao. |
| **Slice** | Unidade atomica de trabalho. Corresponde a uma story ou parte de story. Possui spec, plan, tests e codigo. |
| **Epic** | Agrupamento de stories que entrega uma capacidade de produto completa. |
| **Story** | Unidade de valor de produto decomposta a partir de um epic. Pode gerar um ou mais slices. |
| **Lane (trilha)** | Classificacao de complexidade de um slice que determina quais gates sao obrigatorios e qual o SLA. |
| **Severity (severidade)** | Nivel de impacto de um finding (S1-S5). Determina se o finding bloqueia o gate e qual o SLA de correcao. |
| **Evidence (evidencia)** | Prova verificavel de que uma acao foi executada ou um criterio foi atendido. Deve incluir comando, output e exit code. |
| **Cross-review** | Principio de que quem produz um artefato nao pode ser o mesmo que o audita. Garante independencia de verificacao. |
| **Handoff** | Documento de transferencia de contexto entre sessoes. Contem estado atual, pendencias e proxima acao. |
| **Checkpoint** | Ponto de salvamento do estado completo do projeto em `project-state.json` + handoff. |
| **Artifact (artefato)** | Qualquer arquivo produzido pelo pipeline: spec, plan, tests, codigo, JSON de gate, relatorio. |
| **Mode (modo)** | Estado operacional do pipeline: normal, degraded, paused, incident. |
| **Isolated context (contexto isolado)** | Ambiente de execucao de um agent de gate onde ele nao tem acesso ao output de outros agents do mesmo slice. |
| **Degraded approval (aprovacao degradada)** | Aprovacao emitida com menos agents do que o requerido (ex: single-LLM quando dual-LLM e obrigatorio). Nao permite merge em trilha high-risk. |
| **Technical debt (divida tecnica)** | Finding aceito como pendencia com deadline de resolucao. Somente S3 e S4 podem ser divida. S1 e S2 nao podem. |
| **Exception (excecao)** | Situacao que nao se enquadra no fluxo normal e requer tratamento especifico conforme politica de excecoes. |

---

## 3.1. Mapa canonico de agentes

Esta tabela e a **fonte unica de verdade** para nomes de agentes no protocolo. Todo documento normativo deve usar exclusivamente os nomes canonicos (coluna 1 + coluna 2). Nomes deprecated (coluna 3) nao podem aparecer em documentos normativos. Documentos historicos (retrospectivas, incidents) podem conter nomes deprecated com nota de equivalencia.

| Nome canonico (v3) | Modo | Substitui (v2, deprecated) | Modelo |
|---|---|---|---|
| product-expert | discovery | domain-analyst, nfr-analyst | sonnet |
| product-expert | decompose | epic-decomposer, story-decomposer | sonnet |
| product-expert | functional-gate | functional-reviewer | sonnet |
| ux-designer | research | (expanded) | sonnet |
| ux-designer | design | (expanded) | sonnet |
| architecture-expert | design | architect, api-designer | opus |
| architecture-expert | plan | architect | opus |
| architecture-expert | plan-review | plan-reviewer | opus |
| architecture-expert | code-review | reviewer | opus |
| data-expert | modeling | data-modeler | sonnet |
| data-expert | review | (new) | sonnet |
| data-expert | data-gate | (new) | sonnet |
| security-expert | threat-model | (expanded) | opus |
| security-expert | spec-security | (new) | opus |
| security-expert | security-gate | security-reviewer | opus |
| qa-expert | verify | verifier | sonnet |
| qa-expert | audit-spec | spec-auditor | sonnet |
| qa-expert | audit-story | story-auditor | sonnet |
| qa-expert | audit-planning | planning-auditor | sonnet |
| qa-expert | audit-tests | test-auditor | sonnet |
| devops-expert | ci-design | (new) | sonnet |
| devops-expert | docker | (new) | sonnet |
| devops-expert | deploy | (new) | sonnet |
| observability-expert | strategy | (new) | sonnet |
| observability-expert | observability-gate | (new) | sonnet |
| integration-expert | strategy | (new) | sonnet |
| integration-expert | integration-gate | (new) | sonnet |
| builder | test-writer | ac-to-test | opus |
| builder | implementer | implementer | opus |
| builder | fixer | fixer | opus |
| governance | master-audit | master-auditor | opus |
| governance | retrospective | epic-retrospective | opus |
| governance | harness-learner | harness-learner | opus |
| governance | guide-audit | guide-auditor | sonnet |
| orchestrator | (single mode) | orchestrator | opus |

**Regra normativa:** todo documento do protocolo deve usar exclusivamente os nomes canonicos (coluna 1 + coluna 2). Nomes deprecated (coluna 3) nao podem aparecer em documentos normativos. Documentos historicos (retrospectivas, incidents) podem conter nomes deprecated com nota de equivalencia.

---

## 4. Sistema de severidade

Define os 5 niveis de severidade de findings (S1-S5), seus efeitos no gate, SLAs de correcao, regras de excecao e processo de reclassificacao. Governa a politica "zero tolerance" (zero findings S1-S3 no merge; S4/S5 nao bloqueiam gates mas sao registrados).

**Documento completo:** [`docs/protocol/01-sistema-severidade.md`](01-sistema-severidade.md)

---

## 5. Trilhas por complexidade

Define as 4 trilhas de execucao (hotfix, small, standard, high-risk), criterios objetivos de classificacao, gates obrigatorios por trilha e SLAs de lead time. Governa qual pipeline cada slice deve seguir.

**Documento completo:** [`docs/protocol/02-trilhas-complexidade.md`](02-trilhas-complexidade.md)

---

## 6. Contrato de artefatos

Define o formato, campos obrigatorios e criterios de validade de cada artefato produzido pelo pipeline: spec.md, plan.md, tasks.md, JSONs de gate, relatorios, handoffs. Governa a consistencia e rastreabilidade dos artefatos.

**Documento completo:** [`docs/protocol/03-contrato-artefatos.md`](03-contrato-artefatos.md)

---

## 7. Criterios objetivos de gate

Define os criterios de aprovacao e rejeicao de cada gate (qa-expert verify, architecture-expert code-review, security-expert security-gate, qa-expert audit-tests, product-expert functional-gate, governance master-audit). Cada criterio deve ter input, output e evidencia. Governa a objetividade do pipeline de qualidade.

**Documento completo:** [`docs/protocol/04-criterios-gate.md`](04-criterios-gate.md)

---

## 8. Matriz RACI

Define as responsabilidades de cada agent (Responsible, Accountable, Consulted, Informed) para cada atividade do pipeline. Governa a separacao de responsabilidades e o principio de cross-review.

**Documento completo:** [`docs/protocol/05-matriz-raci.md`](05-matriz-raci.md)

---

## 9. Estrategia de evidencias

Define como evidencias devem ser coletadas, armazenadas e referenciadas em cada fase do pipeline. Governa a rastreabilidade e auditabilidade de todas as decisoes e verificacoes.

**Documento completo:** [`docs/protocol/06-estrategia-evidencias.md`](06-estrategia-evidencias.md)

---

## 10. Politica de excecoes

Define as 9 categorias de excecao (E1-E9) para situacoes que nao se enquadram no fluxo normal: contexto ausente, dependencia bloqueada, divida aceita, TDD nao aplicavel, agent indisponivel, PM indisponivel, scope creep, incidente de seguranca e drift de harness. Cada excecao deve ter owner, deadline, registro e plano de resolucao.

**Documento completo:** [`docs/protocol/07-politica-excecoes.md`](07-politica-excecoes.md)

---

## 11. Metricas do processo

Define as metricas operacionais organizadas em 4 categorias: velocidade (lead time, throughput), qualidade (first-pass rate, defeitos escapados), custo (tokens, retrabalho) e saude do processo (divida tecnica, escalacoes). Inclui formulas de calculo, thresholds e template de dashboard para o PM.

**Documento completo:** [`docs/protocol/08-metricas-processo.md`](08-metricas-processo.md)

---

## 12. Regras de versionamento do protocolo

### 12.1. Esquema de versao

O protocolo segue versionamento semantico (semver): `MAJOR.MINOR.PATCH`.

| Tipo | Quando aplicar | Exemplos |
|---|---|---|
| **MAJOR** | Mudanca estrutural: nova secao, secao removida, alteracao de niveis de severidade, mudanca de numero de gates. | Adicionar S0, remover gate funcional, criar secao 12. |
| **MINOR** | Mudanca normativa dentro de secao existente: novo criterio de gate, nova categoria de excecao, novo tipo de metrica. | Adicionar E10, novo criterio em security-expert (security-gate), nova metrica M-V06. |
| **PATCH** | Clarificacao, correcao de erro de digitacao, exemplo adicionado, melhoria de redacao sem mudanca normativa. | Corrigir formula de metrica, adicionar exemplo em S3, ajustar redacao. |

### 12.2. Processo de alteracao

1. Toda alteracao deve ser commitada com prefixo: `docs(protocol): <descricao>`.
2. O campo de versao no cabecalho do documento alterado deve ser atualizado.
3. O campo de versao neste documento mestre (00-protocolo-operacional.md) deve ser atualizado para refletir a versao mais alta entre todas as secoes.

### 12.3. Aprovacao

| Tipo | Quem aprova |
|---|---|
| **MAJOR** | PM (obrigatorio). |
| **MINOR** | Orchestrator pode aplicar; PM deve ser informado. |
| **PATCH** | Orchestrator pode aplicar autonomamente. |

### 12.4. Harness-learner

O governance (harness-learner) (R16) somente pode propor alteracoes PATCH ou MINOR. Alteracoes MAJOR requerem decisao explicita do PM. O governance (harness-learner) nao pode revogar, afrouxar ou alterar P1-P9 ou R1-R14 por meio de alteracoes neste protocolo. Maximo de 3 mudancas por ciclo retrospectivo (R16).

---

## 13. Anexos

Os seguintes anexos complementam este protocolo. Devem ser criados conforme necessidade durante a execucao do projeto.

| Anexo | Conteudo | Status |
|---|---|---|
| **Anexo A** | Templates de artefatos — modelos para spec.md, plan.md, tasks.md, JSONs de gate, handoff, retrospective. | A ser criado por epico. |
| **Anexo B** | Checklists de gate — versao simplificada dos criterios de 04-criterios-gate.md para consulta rapida durante execucao. | A ser extraido de 04-criterios-gate.md. |
| **Anexo C** | Tabelas de decisao — fluxograma de classificacao de trilha (lane) para uso pelo orchestrator. | A ser extraido de 02-trilhas-complexidade.md. |
| **Anexo D** | Modelos de relatorio — templates para dashboard do PM, retrospective, incident report, slice report. | A ser criado com base em 08-metricas-processo.md. |

---

## 14. Vigencia

Este protocolo entra em vigor imediatamente e aplica-se a todas as operacoes da fabrica de software Kalibrium V2 a partir da data de publicacao. Todos os agents devem ser configurados para operar conforme este protocolo antes de iniciar qualquer trabalho novo.
