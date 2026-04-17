# Meta-audit independente — Protocolo Kalibrium V2 v1.2.0

## Status pos-patch (2026-04-16 posterior)

PATCH 1.2.1 aplicado. Findings S2 (F-001, F-002, F-003) **fechados**:

- **F-001 CLOSED:** `03-contrato-artefatos.md:807`, `07-politica-excecoes.md:465`, `08-metricas-processo.md:261` agora enumeram `E1-E10`.
- **F-002 CLOSED:** 9 exemplos JSON em `04-criterios-gate.md` (§§1.3-9.3) reescritos com todos os 14 campos obrigatorios do schema formal + bloco `evidence`.
- **F-003 CLOSED:** campo `agent` agora contem apenas nome; campo `mode` separado conforme schema.

**PATCH 1.2.1 CLOSED:** Versao 1.2.1 em 00, 03, 04, 07, 08. Demais arquivos em 1.2.0.

## PATCH 1.2.2 aplicado (L4-ready)

Findings S3 fechados:

- **F-004 CLOSED:** `04 §2` agora explicita `gate_name: "review"` e `mode: "code-review"`; regra normativa de nao-confundir adicionada.
- **F-005 CLOSED:** `07 §5.3` tabela de alertas — "Excecao E3 com deadline ultrapassado" nao promove severidade automaticamente; avaliacao diferida conforme 01 §S4.
- **F-006 CLOSED:** M-C01, M-C02, M-C03 em 08 agora usam o campo canonico `tokens_used` de 03 §10.1. Removida invencao ad-hoc de `tokens_verify/tokens_code_review/...`.
- **F-007 CLOSED:** 03 §10.2 agora inclui `phase_timestamps` (discovery/strategy/planning/execution/closing × start/end) que M-V02 consulta.
- **F-008 CLOSED:** 04 agora tem criterios objetivos para os 6 gates faltantes — §10 audit-spec, §11 audit-story, §12 audit-planning, §13 plan-review, §14 spec-security (L4), §15 guide-audit. Todos com checklist binario + limiares + exemplo JSON conforme schema formal.

**Versao atual:** 1.2.2 em 00, 03, 04, 07, 08. Arquivos 01, 02, 05, 06 continuam 1.2.0.
**Status:** `approved` para trilha L4 (high-risk). Apenas findings S4 (F-009 a F-012) permanecem abertos — sao otimizacoes estruturais para proximo ciclo retrospectivo via harness-learner (R16 limita a 3 mudancas/ciclo).

---



Data: 2026-04-16
Auditor: governance (master-audit) — Opus 4.7, contexto isolado R3
Escopo: 9 documentos docs/protocol/ + gate-output.schema.json

## Verdict

```
verdict: approved_with_reservations
blocking_findings_count: 3    (S1+S2 — bloqueiam merge em zero-tolerance)
non_blocking_findings_count: 9
findings_by_severity: {S1: 0, S2: 3, S3: 5, S4: 4, S5: 0}
```

## Resumo executivo

O protocolo em v1.2.0 esta **executavel para trilha L3 (default)** apos uma rodada curta de patch. As 6 contradicoes S1 anteriores foram fechadas. Restam 3 findings S2 que afetam a validacao automatica do schema JSON e a consistencia de ownership nos exemplos, e 5 findings S3 que emergem antes do primeiro slice L4.

Nao ha nenhuma contradicao S1 remanescente que trave o pipeline no primeiro uso. O projeto pode prosseguir do estado PAUSED para RUNNING em trilha L3 apos aplicar `PATCH 1.2.1` (fix dos 3 S2). Trilha L4 exige ainda `PATCH 1.2.2` (fix dos S3 antes do primeiro slice de alto risco).

A reconciliacao dual-LLM formalizada em 04 §9.4 + E10 + Anexo E foi validada como coerente entre os documentos. A cascata S4 diferida (01 + 07 E3) tambem esta limpa, exceto por contradicao pontual em 07 §5.3.

## Findings

### F-001 — Enum E10 desalinhado
- **Severity:** S2
- **Dimensao:** consistencia
- **Files:** `03-contrato-artefatos.md:807`, `07-politica-excecoes.md:465`, `08-metricas-processo.md:261`
- **Evidencia:** 03 §10.2 `active_exceptions.type: "E1-E9"` contradiz a nova E10 em 07; M-H04 em 08 enumera `E1-E9`; ja 03 §10.1 usa `E1-E10`.
- **Recomendacao:** uniformizar 3 ocorrencias para `E1-E10`.

### F-002 — Exemplos JSON em 04 nao validam contra schema formal
- **Severity:** S2
- **Dimensao:** schema
- **Files:** `04-criterios-gate.md` §§ 1.3, 2.3, 3.3, 4.3, 5.3, 6.3, 7.3, 8.3, 9.3
- **Evidencia:** schema `gate-output.schema.json` exige 14 campos obrigatorios (`$schema`, `gate`, `slice`, `lane`, `agent`, `mode`, `verdict`, `timestamp`, `commit_hash`, `isolation_context`, `blocking_findings_count`, `non_blocking_findings_count`, `findings_by_severity`, `findings`). Exemplos em 04 trazem apenas 9-10. Faltam especialmente `$schema`, `lane`, `mode`, `isolation_context`, `findings_by_severity`.
- **Recomendacao:** re-escrever os 9 exemplos em 04 com os 14 campos obrigatorios + bloco `evidence` especifico de cada gate. Alternativa: substituir exemplos por referencia ao schema + apenas bloco `evidence`.

### F-003 — Campo `agent` em 04 nao segue formato canonico `agent (mode)`
- **Severity:** S2
- **Dimensao:** consistencia
- **Files:** `04-criterios-gate.md:57,113,172,228,282,335,384,432,531`
- **Evidencia:** mapa canonico 00 §3.1 estabelece que todo agent e invocado com um modo explicito. Exemplos em 01-sistema-severidade.md linha 99 usam forma correta `security-expert (security-gate)`. Exemplos em 04 usam apenas `qa-expert`, `architecture-expert`, etc., sem modo.
- **Recomendacao:** adicionar `(modo)` em todo campo `agent` dos exemplos de 04, OU introduzir campo separado `mode` (preferido — alinha com schema formal que ja tem `mode`).

### F-004 — Gate name `review` vs modo `code-review`
- **Severity:** S3
- **Dimensao:** consistencia
- **Files:** `00:156` (mapa canonico: `architecture-expert (code-review)`), `02:132,152`, `06:24`, schema enum
- **Evidencia:** schema formal enum de `gate` lista `review`, mas os documentos frequentemente chamam de `code-review` (que e o nome do modo, nao do gate). Risco de merge-slice rejeitar JSON com `"gate": "code-review"`.
- **Recomendacao:** normativo: `gate_name: "review"`, `mode: "code-review"`. Documentar em 04 §2 explicitamente ("Gate: review / Agente: architecture-expert (code-review)").

### F-005 — Contradicao sobre promocao automatica em 07 §5.3
- **Severity:** S3
- **Dimensao:** contradicao
- **Files:** `07-politica-excecoes.md:475` vs `01-sistema-severidade.md:227-231` + `07-politica-excecoes.md:111`
- **Evidencia:** 07 §5.3 tabela "Alertas automaticos" linha "Excecao E3 com deadline ultrapassado | Promover severidade automaticamente." contradiz a regra estabelecida em 01 §S4 (promocao diferida via retrospective).
- **Recomendacao:** substituir "Promover severidade automaticamente" por "Avaliar no proximo governance (retrospective) conforme 01 §S4". Reforca regra de governanca centralizada.

### F-006 — Nomenclatura de tokens divergente entre 03 e 08
- **Severity:** S3
- **Dimensao:** consistencia
- **Files:** `03:718,722` vs `08:163,186,195`
- **Evidencia:** 03 §10.1 eventos `gate_result`, `fix_applied`, `task_completed` usam `tokens_used` (int). 08 M-C01 usa `input_tokens + output_tokens` (nao declarados em 03). M-C03 cria 6 nomes ad-hoc (`tokens_verify`, `tokens_code_review`...).
- **Recomendacao:** padronizar em `tokens_used` no evento. Derivar agregacoes por gate via filtro (`WHERE event.gate_name = 'verify'`), nao por invencao de campos.

### F-007 — `phase_timestamps` em 08 sem contraparte em schema
- **Severity:** S3
- **Dimensao:** lacuna
- **Files:** `08-metricas-processo.md:35` (M-V02)
- **Evidencia:** metrica M-V02 consulta campo `project-state.json[phase_timestamps]`. Schema em `03 §10.2` linhas 738-822 nao inclui essa chave.
- **Recomendacao:** adicionar `phase_timestamps: { discovery_start, discovery_end, strategy_start, ... }` ao schema de project-state em 03 §10.2. Atualizar regra "toda chave de primeiro nivel deve existir".

### F-008 — Schema lista 15 gates, 04 documenta apenas 9
- **Severity:** S3
- **Dimensao:** lacuna
- **Files:** `gate-output.schema.json` enum de `gate` vs `04-criterios-gate.md`
- **Evidencia:** schema enumera `verify, review, security-gate, audit-tests, functional-gate, data-gate, observability-gate, integration-gate, master-audit, audit-spec, audit-story, audit-planning, plan-review, spec-security, guide-audit` (15). 04 tem criterios binarios + limiares para apenas 9 (verify, review, security-gate, audit-tests, functional-gate, data-gate, observability-gate, integration-gate, master-audit).
- **Recomendacao:** adicionar §§ 10-15 em 04 cobrindo audit-spec, audit-story, audit-planning, plan-review, spec-security, guide-audit. Ate ser feito, o schema aceita 6 gates sem criterios objetivos definidos.

### F-009 — Campo `$schema` ambiguo (instance vs meta)
- **Severity:** S4
- **Dimensao:** schema
- **Files:** `03:664` + `gate-output.schema.json:14`
- **Evidencia:** JSON Schema reserva `$schema` para URI do meta-schema (draft-07). Usar o mesmo campo com valor `"gate-output-v1"` na instancia gera ambiguidade com ferramentas JSON Schema.
- **Recomendacao:** renomear campo da instancia para `schema_version: "gate-output-v1"`. Reservar `$schema` para meta-schema caso necessario.

### F-010 — Limite de 5 reclassificacoes sem campo rastreavel
- **Severity:** S4
- **Dimensao:** auditabilidade
- **Files:** `01-sistema-severidade.md:346`
- **Evidencia:** regra "numero maximo de reclassificacoes por slice e 5" nao tem campo correspondente em schema de gate nem evento de telemetria. Nao ha como detectar violacao automaticamente.
- **Recomendacao:** adicionar evento `reclassification_requested` em 03 §10.1 ou campo `reclassification_count` em `project-state.json[slice]`.

### F-011 — RACI "Correcao de findings" com C ambiguo
- **Severity:** S4
- **Dimensao:** consistencia
- **Files:** `05-matriz-raci.md:91`
- **Evidencia:** linha tem 4 agentes marcados como C (architecture-expert, data-expert, security-expert, qa-expert). Regra de cross-review diz que C sao "consultados" — mas aqui significa "todos podem ser consultados" ou "apenas o emitente do finding"?
- **Recomendacao:** alterar para "agente que emitiu o finding = C; demais = —".

### F-012 — Artefatos de excecao sem contrato formal
- **Severity:** S4
- **Dimensao:** lacuna
- **Files:** `07-politica-excecoes.md` §§ E1, E2, E4, E7
- **Evidencia:** arquivos `specs/NNN/missing-context.md`, `blocked-dependency.md`, `conditional-gate-skip.md`, `alternative-verification.md`, `scope-change.md` sao criados pelo builder/orchestrator mas nao tem contrato formal em 03 (secao de artefatos).
- **Recomendacao:** adicionar secao 12 em 03 "Artefatos de excecao" com contrato minimo (owner, input, output, criterio). Impede drift de formato.

## Areas avaliadas como integras

Os seguintes pontos foram verificados e estao coerentes entre os 9 documentos:

1. **Zero-tolerance S1-S3:** schema `allOf` + 01 §2 + 04 §10 ponto 1 + 06 §2.5 todos alinhados em `blocking_findings_count == 0 para approved`.
2. **Reconciliacao dual-LLM:** 04 §9.4 + 07 E10 + 00 §13.1.1 (merge-slice) coerentes. Prompt literal em §9.4. E10 registra resolucao via `master-audit-pm-decision.json`.
3. **Cascata S4 diferida:** 01 §S4 + 07 E3 alinhados (exceto F-005 em 07 §5.3).
4. **Harness-learner path:** 03 §7.2 + 06 §2.6 + 06 §5.2 unificados em `docs/governance/`.
5. **Mapa canonico 00 §3.1:** completo, sem agentes orfaos, todas entradas de outros docs rastreaveis.
6. **Schema JSON draft-07:** sintaxe executavel, `pattern` corretos para `slice` e `commit_hash`, enum `lane` coerente com 02.
7. **Imutabilidade pos-gate (stale:true):** 03 §9.2 + regra de merge-slice no Anexo E coerentes.
8. **M-V03 recalibrada:** derivacao matematica 6×3×15min ≈ 4.5h explicita, coerente com M-Q03 e M-V04.
9. **Isolamento por instancia R3:** 05 "Principio de isolamento" + campo `isolation_context` no schema + telemetria `gate_submitted` com mesmo campo.

## Recomendacao final

O protocolo em v1.2.0 **esta apto para despausar o projeto em trilha L3 (default) apos PATCH 1.2.1** fechando F-001, F-002 e F-003. Essas 3 correcoes sao pequenas edicoes documentais (ajustes de enum, re-escrita de exemplos JSON, adicao de modo em campo `agent`) — cabem em uma sessao de 30-60 minutos.

Para trilha L4 (high-risk), e obrigatorio fechar tambem F-004 a F-008 em `PATCH 1.2.2`. Sao findings S3 que emergem no primeiro slice que (a) use gate condicional, (b) use nome `review`/`code-review` em JSON real, (c) acione promocao de divida E3, (d) consulte telemetria de tokens, (e) consulte `phase_timestamps`, (f) acione gate de planejamento.

Os S4 (F-009 a F-012) sao otimizacoes estruturais — entrariam no proximo ciclo retrospectivo via harness-learner (R16 limita a 3 mudancas por ciclo, entao precisara escolher).

**Recomendacao forte ao PM:** aplicar PATCH 1.2.1 nesta sessao, despausar o projeto, e rodar ciclo normal. Nao tentar corrigir tudo de uma vez — os S3/S4 emergem como findings naturais no proximo slice e sao resolvidos pelo fluxo normal do harness.
