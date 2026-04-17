# 00 — Protocolo Operacional da Fabrica de Software — Kalibrium V2

> Documento normativo mestre. Versao 1.2.2 — 2026-04-16.
> Status: aprovado pelo PM.
> Changelog 1.2.2 (PATCH — meta-audit L4-ready): fechados os 5 findings S3 do meta-audit (F-004 gate name `review` vs modo `code-review`; F-005 `07 §5.3` alinhado com cascata diferida; F-006 `tokens_used` canonico em 08; F-007 `phase_timestamps` em schema de project-state em 03; F-008 criterios objetivos dos 6 gates faltantes adicionados em 04 §§10-15). Protocolo agora apto para trilha L4 (high-risk).
> Changelog 1.2.1 (PATCH — meta-audit governance Opus 4.7): fechados os 3 findings S2 do meta-audit (F-001 enum E1-E10 em 03/07/08; F-002 exemplos JSON de 04 alinhados ao schema formal; F-003 campo `agent` sem modo + campo `mode` separado conforme schema). Relatorio completo em `docs/audits/protocol-meta-audit-2026-04-16.md`.
> Changelog 1.2.0: correcoes de auditoria externa — schema JSON unificado (03 §8 + arquivo formal), RACI alinhada com mapa canonico, nomes de gate unificados (verify/review/security-gate/etc.), protocolo dual-LLM reconciliacao formalizado (04 §9.4 + E10), cascata S4→S3 corrigida (promocao diferida ao fim de epico), M-V03 recalibrada, contratos de scripts documentados, glossario de commit local/push remoto/sessao/instancia isolada, harness-learner path unificado em docs/governance/.

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
| **Commit local** | Commit registrado na branch do slice no repositorio local (ainda nao enviado ao remoto). Gates da Fase E operam sobre o HEAD local da branch do slice. Ciclos de fix geram novos commits locais no topo da branch — nao usar `--amend` em commits ja auditados. |
| **Push remoto** | Envio da branch do slice para o remoto (`git push`). Somente autorizado apos `governance (master-audit)` emitir verdict approved e o script `merge-slice.sh` validar todos os JSONs. O push e parte do merge e nao ocorre antes. |
| **Sessao** | Intervalo delimitado entre `/resume` (ou abertura do projeto) e `/checkpoint` (ou encerramento). SLAs "na mesma sessao" referem-se a esse intervalo. O PM define o limite maximo de sessao (default: jornada de trabalho). |
| **Instancia isolada (R3)** | Invocacao de um agente em processo/sessao separada, sem acesso ao output de outras invocacoes do mesmo slice. Dois modos diferentes do mesmo agente (ex: plan e plan-review) contam como instancias isoladas quando executados em contextos separados. Obrigatorio em gates de cross-review. |

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
| qa-expert | audit-tests-draft | tests-draft-auditor | sonnet |
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

Define os criterios de aprovacao e rejeicao de cada gate (qa-expert verify, architecture-expert code-review, security-expert security-gate, qa-expert audit-tests-draft, qa-expert audit-tests, product-expert functional-gate, governance master-audit). Cada criterio deve ter input, output e evidencia. Governa a objetividade do pipeline de qualidade.

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
| **Anexo E** | Contratos de scripts auxiliares (merge-slice, record-telemetry, telemetry-lock, relock-harness). | Ver secao 13.1 abaixo. |

---

## 13.1. Anexo E — Contratos de scripts auxiliares

Scripts operacionais referenciados pelo protocolo. Todo script deve ter input, output, exit codes e side effects declarados.

### 13.1.1. scripts/merge-slice.sh

| Campo | Valor |
|---|---|
| **Objetivo** | Validar que todos os JSONs de gate do slice existem, estao approved e nao stale; executar push + criar PR + armar auto-merge. |
| **Input** | `$1 = NNN` (numero do slice). Le `specs/NNN/*.json`, `project-state.json`. |
| **Output stdout** | Mensagens de progresso + URL do PR criado. |
| **Output colateral** | Commit + push da branch do slice; chamada `gh pr create` + `gh pr merge --auto`. |
| **Exit codes** | 0: merge armado; 1: input invalido; 2: JSON de gate faltando; 3: algum gate com `verdict: rejected` ou `stale: true`; 4: master-audit.json ausente ou divergente; 5: push falhou. |
| **Pre-condicoes** | Todos os gates aprovados com `blocking_findings_count == 0`; `master-audit.json` com `reconciliation_failed: false` OU `master-audit-pm-decision.json` presente; branch do slice existe. |
| **Seal status** | Selado (nao editavel pelo agente). |

### 13.1.2. scripts/record-telemetry.sh

| Campo | Valor |
|---|---|
| **Objetivo** | Append de eventos canonicos em `.claude/telemetry/slice-NNN.jsonl` via lock que garante atomicidade. |
| **Input** | `$1 = slice_id`, `$2 = event_name`, `$3 = JSON payload (string)`. |
| **Output stdout** | OK + hash do evento (idempotencia). |
| **Output colateral** | Linha JSON appendada ao arquivo `.jsonl`. |
| **Exit codes** | 0: sucesso; 1: input invalido; 2: event_name nao e evento canonico (ver 03-contrato-artefatos.md §10.1); 3: JSON payload malformado; 4: lock nao adquirido em 5s. |
| **Pre-condicoes** | Arquivo `.jsonl` existente ou criavel pelo agente atual. |
| **Seal status** | Selado. |

### 13.1.3. scripts/hooks/telemetry-lock.sh

| Campo | Valor |
|---|---|
| **Objetivo** | Bloquear edicao direta de arquivos em `.claude/telemetry/` por qualquer ferramenta que nao seja `record-telemetry.sh`. |
| **Invocado por** | Hook `PreToolUse` para Edit/Write/Bash quando path match `.claude/telemetry/`. |
| **Output** | Stderr com motivo + exit 2 (bloqueia ferramenta). |
| **Exit codes** | 0: caminho nao e telemetria (deixa passar); 2: caminho e telemetria + ferramenta nao e o script oficial (bloqueia). |
| **Seal status** | Selado. |

### 13.1.4. scripts/relock-harness.sh

| Campo | Valor |
|---|---|
| **Objetivo** | Regenerar selos criptograficos (SHA-256) de `.claude/settings.json` e `scripts/hooks/MANIFEST.sha256` apos edicao legitima de hooks/settings pelo PM em terminal externo. |
| **Input** | `KALIB_RELOCK_AUTHORIZED=1` no env + stdin TTY + confirmacao literal `RELOCK` + escrita automatica de incident report. |
| **Output** | Incident em `docs/incidents/harness-relock-<timestamp>.md` + selos atualizados. |
| **Exit codes** | 0: relock executado; 1: authorization env ausente; 2: stdin nao e TTY; 3: confirmacao nao digitada literal; 4: erro ao calcular hash. |
| **Pre-condicoes** | 4 camadas de salvaguarda conforme CLAUDE.md §9. |
| **Seal status** | Selado — executavel apenas pelo PM em terminal externo. |

### 13.1.5. scripts/sequencing-check.sh

| Campo | Valor |
|---|---|
| **Objetivo** | Validar ordem intra-epico (R13) e inter-epico (R14) antes de criar slice nova. |
| **Input** | `--story ENN-SNN` ou `--epic ENN`. |
| **Output** | Mensagem + exit code. |
| **Exit codes** | 0: OK; 1: story anterior do mesmo epico nao esta `merged`; 2: primeiro slice de epico N e epico N-1 nao esta 100% merged; 3: bypass autorizado via `KALIB_SKIP_SEQUENCE` (registra incidente). |
| **Seal status** | Selado. |

---

## 14. Vigencia

Este protocolo entra em vigor imediatamente e aplica-se a todas as operacoes da fabrica de software Kalibrium V2 a partir da data de publicacao. Todos os agents devem ser configurados para operar conforme este protocolo antes de iniciar qualquer trabalho novo.
