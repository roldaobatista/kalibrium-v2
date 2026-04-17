# 08 — Metricas do Processo

> Documento normativo. versao 1.2.4 — 2026-04-16.
> Changelog 1.2.2 (PATCH — meta-audit, L4-ready): M-C01/M-C02/M-C03 agora usam campo canonico `tokens_used` declarado em 03 §10.1 (antes divergiam em `tokens`, `input_tokens+output_tokens` e `tokens_verify/tokens_code_review/...` ad-hoc).
> Changelog 1.2.1 (PATCH — meta-audit): M-H04 definicao agora enumera `E1-E10` (antes `E1-E9`, inconsistente com introducao de E10 em 1.2.0).
> Changelog 1.2.0: M-V03 recalibrada — thresholds por trilha (L2/L3/L4) substituem threshold global irrealista que divergia da matematica de M-Q03 × M-V04.
> Fonte de verdade para definicao, coleta e analise de metricas da fabrica de software Kalibrium V2.

---

## 1. Principio geral

Toda metrica deve ter formula de calculo, fonte de dados, frequencia de coleta, threshold e acao quando o threshold for violado. Metricas sem acao definida nao devem ser coletadas.

---

## 2. Categoria 1 — Velocidade

### M-V01: Lead time por slice

| Campo | Valor |
|---|---|
| **Definicao** | Tempo decorrido entre `/start-story` (criacao do slice) e `/merge-slice` (merge aprovado). |
| **Formula** | `merge_timestamp - start_timestamp` em horas. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `slice_started` e `slice_merged`). |
| **Frequencia de coleta** | Por slice. |
| **Threshold** | Verde: < 4h (standard lane). Amarelo: 4-8h. Vermelho: > 8h. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Orchestrator deve investigar causa (gate loops, espera PM, complexidade subestimada) e registrar no retrospective. |

### M-V02: Lead time por fase

| Campo | Valor |
|---|---|
| **Definicao** | Tempo total gasto em cada fase (A: Descoberta, B: Estrategia, C: Planejamento, D: Execucao, E: Gates, F: Encerramento). |
| **Formula** | `fase_end_timestamp - fase_start_timestamp` em horas. |
| **Fonte de dados** | `project-state.json` (campos `phase_timestamps`). |
| **Frequencia de coleta** | Por fase completada. |
| **Threshold** | Definido por lane em 02-trilhas-complexidade.md. Sem threshold fixo global. |
| **Quem revisa** | Orchestrator (operacional); PM (produto, via dashboard). |
| **Acao quando excedido** | Identificar fase gargalo e propor melhoria no retrospective. |

### M-V03: Throughput de gates

| Campo | Valor |
|---|---|
| **Definicao** | Tempo entre primeira submissao a gates e aprovacao completa de todos os gates. |
| **Formula** | `last_gate_approved_timestamp - first_gate_submitted_timestamp` em horas. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos de gate). |
| **Frequencia de coleta** | Por slice. |
| **Threshold (calibrado com M-Q03 e M-V04)** | L2: verde < 30min, amarelo 30-60min, vermelho > 60min. L3: verde < 4h, amarelo 4-8h, vermelho > 8h. L4: verde < 6h, amarelo 6-12h, vermelho > 12h. Derivacao: 6 gates × 3 ciclos × 15min ≈ 4.5h para L3 verde nominal; L4 acrescenta 3 gates condicionais. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Analisar qual gate consumiu mais tempo; verificar se fix cycles estao acima de M-Q03 verde. |

### M-V04: Tempo de ciclo fix

| Campo | Valor |
|---|---|
| **Definicao** | Tempo entre emissao de um finding e re-aprovacao do gate apos correcao. |
| **Formula** | `gate_reapproved_timestamp - finding_emitted_timestamp` em minutos. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `finding_emitted` e `gate_approved`). |
| **Frequencia de coleta** | Por finding. Agregado por slice e por epico. |
| **Threshold** | Verde: < 15min por finding. Amarelo: 15-30min. Vermelho: > 30min. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Verificar se findings sao claros o suficiente para o builder (fixer); verificar complexidade do fix. |

### M-V05: Tempo ocioso de pipeline

| Campo | Valor |
|---|---|
| **Definicao** | Tempo em que o pipeline esta parado aguardando decisao do PM ou dependencia externa. |
| **Formula** | Soma de intervalos com `status: "waiting_pm"` ou `status: "waiting_external"` em `project-state.json`. |
| **Fonte de dados** | `project-state.json` (campo `active_exceptions[]` tipos E2 e E6). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: < 10% do lead time total. Amarelo: 10-25%. Vermelho: > 25%. |
| **Quem revisa** | PM (produto, via dashboard). |
| **Acao quando vermelho** | PM deve avaliar se decisoes podem ser antecipadas; orchestrator deve melhorar paralelismo de slices. |

---

## 3. Categoria 2 — Qualidade

### M-Q01: Taxa de aprovacao first-pass por gate

| Campo | Valor |
|---|---|
| **Definicao** | Percentual de slices que passam em cada gate na primeira tentativa. |
| **Formula** | `slices_approved_first_pass / total_slices_submitted * 100` por gate. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos de gate com `attempt: 1`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: > 70%. Amarelo: 50-70%. Vermelho: < 50%. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Analisar padroes de rejeicao; propor checklists pre-gate ou melhoria de specs. |

### M-Q02: Taxa de rejeicao por gate

| Campo | Valor |
|---|---|
| **Definicao** | Numero total de rejeicoes por gate, normalizado por numero de slices. |
| **Formula** | `total_rejections_gate_X / total_slices` por gate. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl`. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Sem threshold fixo. Analise comparativa entre gates para identificar o mais restritivo. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Gate com taxa desproporcionalmente alta indica: criterios desalinhados, ou area sistematica de fraqueza. Investigar e propor melhoria. |

### M-Q03: Media de ciclos fix por slice

| Campo | Valor |
|---|---|
| **Definicao** | Numero medio de loops fix→re-gate por slice. |
| **Formula** | `sum(fix_cycles_per_slice) / total_slices`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `fix_applied` e `gate_rerun`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: < 3 ciclos. Amarelo: 3-5 ciclos. Vermelho: > 5 ciclos. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Verificar se specs sao claras; verificar se testes red cobrem ACs adequadamente; verificar se builder tem contexto suficiente. |

### M-Q04: Defeitos escapados

| Campo | Valor |
|---|---|
| **Definicao** | Findings encontrados em fases posteriores que deveriam ter sido capturados em fases anteriores. |
| **Formula** | Contagem de findings com `escaped_from: "<fase_anterior>"` anotado pelo gate que os encontrou. |
| **Fonte de dados** | JSONs de gate (`verification.json`, `review.json`, etc.). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: 0 escapados. Amarelo: 1-2. Vermelho: > 2. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Revisar criterios do gate anterior; adicionar criterio especifico para prevenir recorrencia. |

### M-Q05: Distribuicao de severidade

| Campo | Valor |
|---|---|
| **Definicao** | Proporcao de findings por nivel de severidade (S1:S2:S3:S4:S5). |
| **Formula** | `count_per_severity / total_findings * 100`. |
| **Fonte de dados** | JSONs de gate. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | S1 deve ser < 1% do total. S2 deve ser < 5%. Se exceder, indica problema sistemico grave. |
| **Quem revisa** | Orchestrator (operacional); PM (via dashboard, em linguagem de produto). |
| **Acao quando vermelho** | S1 > 1%: auditoria completa de seguranca e isolamento. S2 > 5%: revisao de padroes arquiteturais. |

### M-Q06: Efetividade de cross-review

| Campo | Valor |
|---|---|
| **Definicao** | Findings capturados pelo architecture-expert (code-review) que o qa-expert (verify) nao detectou (e vice-versa). |
| **Formula** | `findings_unique_to_reviewer / total_findings_reviewer * 100`. |
| **Fonte de dados** | Comparacao entre `verification.json` e `review.json` do mesmo slice. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Informativo. Se cross-review captura < 10% de findings unicos, questionar se dual-review agrega valor. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando threshold atingido** | Revisar escopo de cada gate para garantir complementaridade; ajustar criterios se necessario. |

---

## 4. Categoria 3 — Custo

### M-C01: Consumo de tokens por slice

| Campo | Valor |
|---|---|
| **Definicao** | Total de tokens consumidos em todas as invocacoes de agents para um slice. |
| **Formula** | `sum(tokens_used)` por slice, agregando todos os eventos que contem o campo. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (campo canonico `tokens_used` — ver 03 §10.1). |
| **Frequencia de coleta** | Por slice. |
| **Threshold** | Verde: < 500k tokens. Amarelo: 500k-1M. Vermelho: > 1M. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Identificar agent mais custoso; verificar se ha loops desnecessarios; otimizar prompts. |

### M-C02: Consumo de tokens por agent

| Campo | Valor |
|---|---|
| **Definicao** | Total de tokens consumidos por cada agent, agregado por epico. |
| **Formula** | `sum(tokens_used)` agrupado por `agent` nos eventos `gate_result`, `fix_applied`, `task_completed`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl`. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Sem threshold fixo. Analise comparativa para identificar agents desproporcionalmente custosos. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando desproporcionado** | Verificar budget do agent (definido em CLAUDE.md secao 8); ajustar prompts; considerar split de responsabilidades. |

### M-C03: Consumo de tokens por gate

| Campo | Valor |
|---|---|
| **Definicao** | Total de tokens consumidos pelo pipeline de gates (fase E). |
| **Formula** | `sum(tokens_used)` filtrado por eventos com `gate_name IN (verify, review, security-gate, audit-tests, functional-gate, master-audit, data-gate, observability-gate, integration-gate)`. Agregacoes por gate obtem-se via filtro adicional `WHERE gate_name = '<nome>'`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `gate_result` e `fix_applied`). |
| **Frequencia de coleta** | Por slice. |
| **Threshold** | Verde: < 200k tokens. Amarelo: 200k-400k. Vermelho: > 400k. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Verificar se gates estao sendo re-executados excessivamente (M-Q03 alto); otimizar criterios. |

### M-C04: Ratio de retrabalho

| Campo | Valor |
|---|---|
| **Definicao** | Proporcao de tokens gastos em correcoes (fix cycles) vs implementacao inicial. |
| **Formula** | `tokens_fix_cycles / tokens_initial_implementation * 100`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `fix_applied` vs `task_completed`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: < 30%. Amarelo: 30-60%. Vermelho: > 60%. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando vermelho** | Indica que implementacao inicial tem qualidade baixa. Revisar: specs, testes red, builder prompts. |

### M-C05: Distribuicao por trilha

| Campo | Valor |
|---|---|
| **Definicao** | Percentual de slices por trilha (hotfix, small, standard, high-risk). |
| **Formula** | `count_per_lane / total_slices * 100`. |
| **Fonte de dados** | `project-state.json` (campo `slices[].lane`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Informativo. Se > 30% dos slices estao na trilha high-risk, decomposicao pode estar inadequada. |
| **Quem revisa** | PM (produto, via dashboard). |
| **Acao quando threshold atingido** | Revisar decomposicao de stories; considerar splits mais granulares. |

---

## 5. Categoria 4 — Saude do processo

### M-H01: Taxa de crescimento de divida tecnica

| Campo | Valor |
|---|---|
| **Definicao** | Novas entradas de divida tecnica por epico vs entradas resolvidas. |
| **Formula** | `new_debt_entries - resolved_debt_entries` por epico. Positivo = divida crescendo. |
| **Fonte de dados** | `project-state.json` (campo `technical_debt[]`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: <= 0 (divida estavel ou reduzindo). Amarelo: 1-3 novas. Vermelho: > 3 novas sem resolucao. |
| **Quem revisa** | PM (produto, via dashboard); orchestrator (operacional). |
| **Acao quando vermelho** | Orchestrator deve propor slice dedicado a reducao de divida no proximo epico. PM deve aprovar prioridade. |

### M-H02: Taxa de melhoria do harness

| Campo | Valor |
|---|---|
| **Definicao** | Mudancas aplicadas pelo governance (harness-learner) por retrospectiva. |
| **Formula** | Contagem de `harness_changes[]` no output do `governance (retrospective)`. |
| **Fonte de dados** | `docs/retrospectives/epic-ENN.md`. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: 1-3 mudancas por epico. Amarelo: 0 (sem aprendizado) ou > 3 (limite R16). Vermelho: violacao de R16. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando zero** | Investigar se retrospectiva esta gerando insights uteis; ajustar criterios de aprendizado. |

### M-H03: Frequencia de escalacao R6

| Campo | Valor |
|---|---|
| **Definicao** | Percentual de slices que atingem o limite de 6 rejeicoes consecutivas no mesmo gate. |
| **Formula** | `slices_with_R6 / total_slices * 100`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl` (eventos `r6_escalation`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Verde: < 5%. Amarelo: 5-15%. Vermelho: > 15%. |
| **Quem revisa** | PM (produto, via dashboard — traducao R12). |
| **Acao quando vermelho** | Indica problema sistemico. Orchestrator deve investigar: criterios de gate desalinhados? Builder incapaz? Spec ambigua? Propor correcao estrutural. |

### M-H04: Frequencia de excecoes

| Campo | Valor |
|---|---|
| **Definicao** | Contagem de excecoes por categoria (E1-E10) por epico. |
| **Formula** | `count(exceptions_type_EX)` por epico. |
| **Fonte de dados** | `project-state.json` (campo `active_exceptions[]`). |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | E7 (scope creep) > 3 por epico: vermelho. E8 (seguranca) > 0: vermelho. E9 (drift) > 0: vermelho. Demais: informativo. |
| **Quem revisa** | Orchestrator (operacional); PM (para E6, E7, E8). |
| **Acao quando vermelho** | E7 alto: revisar qualidade de specs e decomposicao. E8: auditoria de seguranca emergencial. E9: revisar processo de edicao de hooks. |

### M-H05: Utilizacao de agents

| Campo | Valor |
|---|---|
| **Definicao** | Frequencia de invocacao de cada agent, normalizada por numero de slices. |
| **Formula** | `invocations_agent_X / total_slices`. |
| **Fonte de dados** | `.claude/telemetry/slice-NNN.jsonl`. |
| **Frequencia de coleta** | Por epico. |
| **Threshold** | Informativo. Agents com 0 invocacoes indicam funcionalidade nao utilizada. Agents com invocacoes desproporcionalmente altas indicam gargalo. |
| **Quem revisa** | Orchestrator (operacional). |
| **Acao quando anomalo** | Agents nao utilizados: avaliar se devem ser removidos ou se o fluxo os bypassa indevidamente. Agents sobrecarregados: avaliar split. |

---

## 6. Coleta de dados

### 6.1. Fontes de dados

| Fonte | Tipo de dado | Formato |
|---|---|---|
| `.claude/telemetry/slice-NNN.jsonl` | Eventos de agents, timestamps, tokens | JSONL (append-only) |
| `project-state.json` | Estado do projeto, excecoes, divida | JSON |
| `specs/NNN/*.json` | Outputs de gates (verification, review, etc.) | JSON |
| `git log` | Timestamps de commits | Git |
| `docs/retrospectives/` | Analises qualitativas | Markdown |

### 6.2. Responsabilidade de coleta

O orchestrator deve garantir que:
1. Todo evento relevante e registrado em telemetria via `scripts/record-telemetry.sh`.
2. Os JSONs de gate contem todos os campos obrigatorios definidos em 01-sistema-severidade.md.
3. `project-state.json` e atualizado a cada checkpoint.

### 6.3. Integridade dos dados

1. Arquivos de telemetria sao append-only (protegidos por `telemetry-lock.sh`).
2. O orchestrator nao pode editar ou remover entradas de telemetria existentes.
3. O `governance (guide-audit)` deve verificar integridade dos dados de telemetria em cada auditoria periodica.

---

## 7. Dashboard do PM

O orchestrator deve gerar um dashboard periodico para o PM em linguagem de produto (R12). O dashboard nao deve conter termos tecnicos, JSONs, ou metricas brutas.

### 7.1. Frequencia

- **Por epico:** dashboard completo com todas as metricas.
- **Por sessao (sob demanda):** resumo via `/project-status`.

### 7.2. Template do dashboard

```markdown
# Relatorio do Epico ENN — [Nome do Epico]

Data: YYYY-MM-DD
Periodo: [data inicio] a [data fim]

## Resumo executivo
[1-2 frases sobre o estado geral do epico em linguagem de produto.]

## Velocidade
- Tempo medio por funcionalidade: X horas (meta: < 4h) [VERDE/AMARELO/VERMELHO]
- Tempo no pipeline de qualidade: X horas (meta: < 1h) [VERDE/AMARELO/VERMELHO]
- Tempo aguardando decisoes: X horas (X% do total) [VERDE/AMARELO/VERMELHO]

## Qualidade
- Funcionalidades aprovadas de primeira: X% (meta: > 70%) [VERDE/AMARELO/VERMELHO]
- Correcoes necessarias por funcionalidade: X em media (meta: < 3) [VERDE/AMARELO/VERMELHO]
- Problemas criticos encontrados: X (meta: 0) [VERDE/AMARELO/VERMELHO]

## Custo
- Recursos consumidos: X tokens (equivalente a ~R$ Y)
- Retrabalho: X% do total (meta: < 30%) [VERDE/AMARELO/VERMELHO]

## Saude do processo
- Pendencias tecnicas acumuladas: X novas, Y resolvidas [VERDE/AMARELO/VERMELHO]
- Escalacoes ao PM: X vezes [VERDE/AMARELO/VERMELHO]
- Excecoes ativas: X [lista resumida]

## Proximos passos
[1-3 itens claros e acionaveis para o PM.]

## Alertas
[Itens VERMELHOS que requerem atencao imediata do PM, se houver.]
```

### 7.3. Regras do dashboard

1. O orchestrator deve traduzir TODAS as metricas para linguagem de produto antes de incluir no dashboard.
2. "Tokens" deve ser traduzido para custo estimado em reais (usando taxa configurada em `project-state.json` campo `token_cost_brl`).
3. "Slices" deve ser traduzido para "funcionalidades".
4. "Gates" deve ser traduzido para "pipeline de qualidade".
5. "Findings" deve ser traduzido para "problemas encontrados" com qualificador de severidade ("critico", "importante", "menor", "sugestao").
6. Cores (VERDE/AMARELO/VERMELHO) devem ser usadas para indicar status visual.
7. A secao "Alertas" somente deve aparecer se houver itens VERMELHOS.

---

## 8. Vigencia

Este documento entra em vigor imediatamente. Metricas devem ser coletadas a partir do proximo slice iniciado. Dados historicos podem ser retroativamente calculados a partir da telemetria existente quando possivel.
