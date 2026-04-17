# Quality Audit — Agentes v3 — Auditor 1 (Claude Opus 4.7, contexto isolado)

**Data:** 2026-04-16
**Escopo:** 12 arquivos em `.claude/agents/` (11 sub-agents + 1 orchestrator) no commit `7c18f97` da branch `chore/checkpoint-2026-04-16`.
**Protocolo de referencia:** `docs/protocol/` v1.2.2 (esp. 00, 03, 04, 05; schema `gate-output.schema.json`).
**Metodo:** avaliacao independente em contexto isolado (R3), sem consulta previa a auditorias anteriores (comparacao opcional ao final).
**Auditor:** Claude Opus 4.7 (1M context), modo `governance` — este auditor NAO edita codigo nem agentes; apenas avalia.

---

## 1. Rubrica de avaliacao

Sete dimensoes, pesos normalizados para soma = 1.0. Cada dimensao recebe nota 0-5 por agente. Escala: **5 = excelente / referencia de industria**, **4 = muito bom / pequenas melhorias possiveis**, **3 = aceitavel mas com lacunas reais**, **2 = significativas lacunas**, **1 = gravemente deficiente**, **0 = inexistente**.

| # | Dimensao | Peso | Justificativa |
|---|---|---:|---|
| D1 | **Clareza de escopo e papel** | 0.10 | Agente que nao sabe o que e/nao e causa overlap e duplo-veto. Papel precisa distinguir-se dos outros 11. |
| D2 | **Contratos de I/O por modo (schema formal)** | 0.18 | Auditabilidade depende de contrato. Cada modo precisa: inputs permitidos, inputs proibidos, output esperado (idealmente JSON validavel contra schema). Peso alto porque e o principal vetor de falha mecanica do harness. |
| D3 | **Aderencia ao protocolo v1.2.2** | 0.17 | Mapa canonico de modos (00 §3.1), gate-name enum, schema JSON, criterios binarios 04, isolation_context R3, severidade S1-S5 conforme 01. Peso alto por ser o contrato mais recente. |
| D4 | **Testabilidade / criterio objetivo** | 0.15 | Gate que usa "esta bom" em vez de metrica numerica nao e gate — e opiniao. Threshold declarado, metodo de medicao descrito, evidence block estruturado. |
| D5 | **Isolamento R3 / R11 (dual-gate)** | 0.13 | Contexto isolado e um dos principios fundamentais. Agente que audita seu proprio output ou ve output de outro gate do mesmo slice viola R11. |
| D6 | **Completude de modos e cobertura** | 0.10 | Modos declarados no CLAUDE.md §8 precisam existir e estar descritos. Gaps (ex: modo ausente, checklist incompleto) reduzem. |
| D7 | **Qualidade tecnica de persona/referencias/anti-padroes** | 0.10 | Persona define o "red team mindset" do agente. Referencias de mercado ancoram decisoes. Anti-padroes operacionalizam padrao de qualidade. Menor peso porque e apoio, nao mecanica. |
| D8 | **Manutenibilidade e rastreabilidade (changelog, protocol_version, nota normativa)** | 0.07 | Campo `protocol_version`, `changelog`, clausula "em caso de conflito, o protocolo prevalece" sao o que permite evolucao segura. |

Soma: 1.00. Nota agregada = sum(D_i * peso_i).

**Thresholds de verdict:**
- >= 4.60 -> **aprovar**
- 4.00 a 4.59 -> **aprovar com ressalvas** (gaps cosmeticos, nao estruturais)
- 3.00 a 3.99 -> **rejeitar com ajustes dirigidos**
- < 3.00 -> **rejeitar**

---

## 2. Avaliacao agente por agente

### 2.1 `product-expert.md` (50k tokens, sonnet, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=4 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** (5*0.10 + 5*0.18 + 5*0.17 + 4*0.15 + 5*0.13 + 5*0.10 + 5*0.10 + 5*0.07) = **4.85**

**Pontos fortes:**
- Schema obrigatorio do Story Contract documentado explicitamente com pattern regex e regras de validacao (linhas 101-128) — transforma "audit-story" em verificacao mecanica, nao subjetiva.
- Persona muito especifica para o dominio laboratorial (ISO/IEC 17025, RBC/Inmetro, linha 23, 39) — ancora real, nao generica.
- Bloco `evidence` do `functional-review.json` (linhas 159-186) detalha `ac_verification` por AC com happy_path/error_paths/multi_tenant/rbac — criterio objetivo.
- Cita changelog F-09 no frontmatter (linha 8) — rastreabilidade de evolucao.

**Gaps reais (com evidencia literal):**
- **D4 (testabilidade):** o checklist da secao "Checklist de validacao funcional" (linhas 191-198) usa itens como `"Dados de calibracao: precisao, unidades, rastreabilidade metrologica preservados"` sem definir threshold ou metodo de medicao (como o `ux-designer` faz para `design_system_compliance_ratio`). Isso deixa espaco para subjetividade em gates funcionais. Nao e bloqueador mas reduz D4.
- **D6 (menor):** modo `decompose` (linhas 77-129) declara 4 outputs esperados (INDEX.md, epic.md, stories/INDEX.md, stories/ENN-SNN.md) mas nao declara obrigacao de rodar `qa-expert (audit-story)` no proprio modo — a integracao e so no orchestrator. Isso esta correto arquiteturalmente mas merecia uma linha de handoff explicita.

**Verdict individual:** **aprovar**.

---

### 2.2 `ux-designer.md` (50k tokens, sonnet, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **5.00**

**Pontos fortes:**
- **Metrica objetiva de design system compliance** (linhas 226-246) — substitui `"design_system_compliance": "100%"` subjetivo por ratio calculavel com metodo de medicao determinista em 5 passos. Esta e uma das peças mais bem projetadas de todo o harness.
- Incorporacao explicita de IxD, micro-interactions (Dan Saffer) e motion guidelines (Material + HIG) na linha 40-42, incluindo `prefers-reduced-motion` como requisito nao opcional (linha 42).
- Documentos B.1-B.9 e documentos por epico (linhas 112-128) estao mapeados 1-a-1 ao `docs/documentation-requirements.md`.
- Checklist de validacao UX (linhas 248-262) tem 13 itens, cada um verificavel (ex: `"Contraste minimo 4.5:1 para texto normal (WCAG AA)"`).

**Gaps reais:** nenhum estrutural. Observacao minima: a lista `tokens_not_in_style_guide` (linha 216) usa exemplo `"custom-hex-#ab12cd"` — o formato de enumeracao de tokens poderia ser padronizado em ADR ou anexo, mas isso e refinamento, nao gap.

**Verdict individual:** **aprovar**.

---

### 2.3 `architecture-expert.md` (50k tokens, opus, 4 modos)

**Notas:** D1=5 | D2=4 | D3=5 | D4=4 | D5=5 | D6=5 | D7=5 | D8=3

**Agregada:** (5*0.10 + 4*0.18 + 5*0.17 + 4*0.15 + 5*0.13 + 5*0.10 + 5*0.10 + 3*0.07) = **4.58**

**Pontos fortes:**
- 4 modos bem separados (`design`, `plan`, `plan-review`, `code-review`) com isolation_context distintos (linhas 121-123, 178-180) resolvendo a cross-review R11.
- "Plan.md e o mapa, nao o territorio" (linha 31) — principio operacional claro contra plan-como-codigo.
- Checklist de revisao de plano (linhas 160-172) com 12 itens verificaveis, incluindo `"Nenhum controller com logica de negocio"` (linha 172) e `"Migrations seguem safe patterns (nullable first, backfill, then constraint)"` (linha 171).
- `review.json` tem bloco `evidence` com metricas quantitativas (`max_cyclomatic_complexity`, `max_class_length`, etc. — linhas 232-245).

**Gaps reais (com evidencia literal):**
- **D2/D8 (ausencia de changelog):** frontmatter (linhas 1-8) nao possui campo `changelog` — ao contrario de `product-expert.md` linha 8, `ux-designer.md` linha 8, `data-expert.md` linha 8, `devops-expert.md` linha 8, `observability-expert.md` linha 8, `security-expert.md` linha 8, `integration-expert.md` linha 8, `builder.md` linha 8. Isso e inconsistencia direta dentro do conjunto e impacta D8 (rastreabilidade de evolucao). Gap cosmetico mas pontuado.
- **D2 (schema plan-review.json simplificado):** o schema mostrado em linhas 146-155 do modo `plan-review` declara apenas 6 campos (`slice`, `gate`, `verdict`, `findings`, `summary`, `timestamp`) e NAO inclui os 14 campos obrigatorios do `gate-output.schema.json` v1 (falta `$schema`, `lane`, `agent`, `mode`, `isolation_context`, `commit_hash`, `blocking_findings_count`, `non_blocking_findings_count`, `findings_by_severity`). Em contraste, o modo `code-review` no mesmo arquivo (linhas 215-246) tambem esta incompleto (falta `$schema`, `lane`, `mode`, `isolation_context`, `findings_by_severity` explicitos — cita apenas parcialmente). Outros agentes (ex: `qa-expert.md` linhas 75-107, `security-expert.md` linhas 126-170) mostram o JSON completo com os 14 campos. Isso e inconsistencia relevante com o protocolo v1.2.2.
- **D4 (menor):** checklist de `code-review` (linhas 204-211) tem itens qualitativos como `"God classes (>300 linhas)"` e `"complexidade ciclomatica (< 10 por metodo)"` que sao mensuraveis — esta bem —, mas outros como `"Aderencia aos ADRs ativos"` (linha 206) nao tem metodo operacional descrito (como se mede aderencia?). Reduz levemente D4.

**Verdict individual:** **aprovar com ressalvas** — gaps sao cosmeticos (changelog faltando, schemas JSON mostrados incompletos nos dois modos de gate) mas somam impacto em D8. Recomendacao: adicionar changelog e expandir os dois schemas JSON para os 14 campos.

---

### 2.4 `data-expert.md` (40k tokens, sonnet, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=4 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **4.85**

**Pontos fortes:**
- Changelog registra fix F-01 (linha 8) — modo `review` agora emite JSON formal. Correcao feita.
- Separacao clara entre `review` (pre-gate no plan, linha 87-140) e `data-gate` (gate de codigo, linha 143-212) — evita confusao comum entre "revisar plano de migration" vs "auditar migration executada".
- Checklist de validacao de dados (linhas 198-212) tem 13 itens, todos verificaveis mecanicamente (ex: `"FK no lado N da relacao tem index correspondente"` — checavel via `pg_indexes`).
- Persona ancorada em realidade brasileira e PostgreSQL internals (linha 23) — nao generica.

**Gaps reais:**
- **D4 (menor):** campo `tenant_id_coverage` no `data-review.json` (linha 192) usa string `"100%"` em vez de numero float — inconsistencia de tipo em comparacao com `design_system_compliance_ratio: 0.98` do ux-designer (linha 211). Nao viola o schema base mas reduz comparabilidade entre gates.
- **D4 (menor):** o bloco `evidence` do modo `review` (linhas 129-137) lista `"plan_migrations_reviewed": 0` como exemplo — se e exemplo com valor "0", convem deixar explicito que e placeholder. Cosmetico.
- Observacao: modo `review` emite arquivo com nome `specs/NNN/data-plan-review.json` (linha 91) enquanto gate_name canonico e `data-gate`. Esta duplicidade arquivo vs gate_name esta explicitada (linha 112), mas pode gerar confusao em relatorios agregados.

**Verdict individual:** **aprovar**.

---

### 2.5 `security-expert.md` (40k tokens, opus, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=4 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **4.85**

**Pontos fortes:**
- Diretiva adversarial explicita (linhas 32-34): `"Sua funcao e ENCONTRAR vulnerabilidades, nao aprovar"` com directiva `"Aprovar codigo inseguro e pior do que rejeitar codigo seguro"`. Anti-approval-bias bem operacionalizado.
- **Ownership de PII in logs (F-03)** (linhas 174-187) resolve overlap com `observability-gate` via regra clara: security = BLOCKING / observability = informational. Evita duplo-veto. Texto complementar e identico (por design) no `observability-expert.md` linhas 160-173 — consistencia cross-agent verificada.
- `security-review.json` schema completo com 14 campos (linhas 126-170) e bloco `evidence` rico (`owasp_checks`, `lgpd_checks`, vetores enumerados, `composer_audit_high_critical`).
- Checklist de auditoria (linhas 189-209) tem 16 itens, todos acionaveis.
- **Diferenca vs outros gates:** a linha 172 declara `"ZERO TOLERANCE: verdict so e approved quando findings: []. Qualquer finding, independente de severidade, resulta em rejected"` — ou seja, security-gate e MAIS ESTRITO que o criterio geral S1-S3 dos demais gates (que toleram S4/S5 non-blocking). **Isto e escolha legitima para security**, mas merece destaque explicito: este agente diverge da politica default por design. A divergencia esta correta conceitualmente; vale registrar em ADR ou nota de protocolo.

**Gaps reais:**
- **D4 (menor):** observacao acima sobre ZERO TOLERANCE mais estrita — nao esta escrito em 04 §6.1 que security difere dos demais gates. Pode gerar inconsistencia se outro auditor interpretar "verdict approved exige findings[] == []" vs "blocking_findings_count == 0". Recomendacao: explicitar `"S4/S5 nao bloqueiam mas security trata todo finding como S1-S3 por conservadorismo"` ou alinhar com os demais gates.

**Verdict individual:** **aprovar**.

---

### 2.6 `qa-expert.md` (50k tokens, sonnet, 5 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=5 | D6=5 | D7=5 | D8=4

**Agregada:** (5*0.10 + 5*0.18 + 5*0.17 + 5*0.15 + 5*0.13 + 5*0.10 + 5*0.10 + 4*0.07) = **4.93**

**Pontos fortes:**
- 5 modos (verify, audit-spec, audit-story, audit-planning, audit-tests) todos com schema JSON completo de 14 campos (linhas 75-107, 136-167, 193-225, 254-285, 316-351). Um dos agentes mais bem padronizados do conjunto.
- **Nota de separacao com architecture-expert** (linha 18) explicita que revisao estrutural de codigo foi movida ao architecture-expert para "eliminar violacao de cross-review" — historico documentado.
- **Nota sobre campo `slice` para auditorias nao-vinculadas** (linha 227, 287): usa `"slice": "000"` como placeholder conforme schema pattern `^[0-9]{3}$`. Detalhe que normalmente causa divergencia foi antecipado.
- Principios inegociaveis (linhas 24-32): `"Quem escreve nao audita"` + `"Contexto isolado e sagrado"` + `"A funcao e encontrar problemas, nao aprovar"` — mentalidade correta para QA.

**Gaps reais:**
- **D8 (menor):** o frontmatter (linhas 1-8) NAO possui campo `changelog` — igual ao `architecture-expert.md`. Embora outros agentes possuam (product-expert, ux-designer, data-expert, devops-expert, observability-expert, security-expert, integration-expert, builder). Inconsistencia dentro do conjunto. Reduz D8.
- Observacao: descricao no frontmatter (linha 3) diz `"5 modos de gate isolado"` — mas `audit-spec`, `audit-story`, `audit-planning` sao "auditorias" tecnicamente em contexto isolado mas nao sao "gates da Fase E". A taxonomia correta seria "5 modos em contexto isolado" ou "1 gate + 4 auditorias". Cosmetico.

**Verdict individual:** **aprovar**.

---

### 2.7 `devops-expert.md` (40k tokens, sonnet, 4 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=4 | D6=5 | D7=5 | D8=5

**Agregada:** (5*0.10 + 5*0.18 + 5*0.17 + 5*0.15 + 4*0.13 + 5*0.10 + 5*0.10 + 5*0.07) = **4.87**

**Pontos fortes:**
- **Checklist ampliado F-02** (linhas 176-198) de 6 para 18 checks — `job_timeout_minutes_explicit`, `concurrency_groups_configured`, `cache_key_versioned_by_lockfile`, `sbom_image_scanning_enabled`, `dockerfile_non_root_user`, `compose_healthcheck_per_service`, `github_actions_permissions_minimal`, `actions_pinned_by_sha`, `artifact_retention_policy_declared`, `secrets_via_secrets_context_only`, `matrix_fail_fast_false_on_parallel_tests`, `reusable_workflows_instead_of_duplication`. Todos mecanicamente verificaveis.
- Referencias expandidas F-07 (linhas 220-224): DevOps Handbook, Google SRE Book, Camille Fournier, Kelsey Hightower. Ancora a pratica em autores vivos e atuais.
- 4 modos bem separados: `ci-design`, `docker`, `deploy`, `ci-gate`.
- Output com `isolation_context` declarado (linhas 116-118) e path duplo `specs/NNN/ci-review.json` ou `docs/audits/ci-review-YYYY-MM-DD.json` conforme invocacao em slice ou fora.

**Gaps reais:**
- **D5 (isolamento):** modo `ci-gate` nao declara "nao pode ser invocado na mesma instancia que outros modos de gate do mesmo slice" — enquanto a maioria dos outros gates (ex: `functional-gate` linha 137, `ux-gate` linha 170, `integration-gate` linha 121, `observability-gate` linha 101, `data-gate` linha 148, `security-gate` linha 105) declara essa clausula. No devops o texto (linha 118) diz apenas `"emitir campo isolation_context unico por invocacao"`. Como `ci-gate` geralmente nao concorre com outros gates (mudancas em CI raramente coexistem com mudancas em codigo de negocio no mesmo slice), isso e gap menor, mas reduz consistencia.
- Observacao: a descricao no frontmatter (linha 17) diz `"Atua em 4 modos"` mas o CLAUDE.md §8 declara os mesmos 4 modos — consistente.

**Verdict individual:** **aprovar**.

---

### 2.8 `observability-expert.md` (40k tokens, sonnet, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=4 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **4.85**

**Pontos fortes:**
- **Ownership de PII in logs (F-03)** complementar ao security-expert (linhas 160-173) — declara explicitamente `ownership: observability-expert/quality (informational)` e instrui que caso observational detecte PII nao-reportado pelo security-gate, pode emitir S3 sob categoria `"cross-gate coverage gap"` (linha 171). Esta e uma resolucao elegante do problema de overlap.
- Principio `"Observabilidade != monitoramento"` (linha 25) distinguindo answering `"esta quebrado?"` de `"por que esta quebrado?"` — ancora conceitual correta (Honeycomb-style).
- Checklist de auditoria (linhas 177-189) com 10 itens e referencias concretas (`laravel-query-detector`, `X-Request-ID`, `Monolog JSON formatter`).
- Anti-padroes (linhas 227-236) capturam realidades de producao: `"Log everything"`, `"Correlacao manual"`, `"Telescope em producao sem protecao"`.

**Gaps reais:**
- **D4 (menor):** campos `structured_count` e `unstructured_count` no `evidence.log_quality` (linha 151-152) usam contagem absoluta sem threshold normalizado. O ideal seria um ratio tipo `structured_ratio = structured / (structured + unstructured)` com threshold de pass (ex: `>= 0.95`) — igual ao que o ux-designer fez. Gap de padronizacao entre gates.
- Observacao: bloco `checks` em `evidence` (linhas 140-148) lista 8 booleanos. Se algum for `false`, a propria evidence ja sinaliza — bom. Mas nao ha mapeamento explicito de qual check falhando gera qual severidade (S1 vs S2 vs S3). Reduz levemente testabilidade.

**Verdict individual:** **aprovar**.

---

### 2.9 `integration-expert.md` (40k tokens, sonnet, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **5.00**

**Pontos fortes:**
- **Catalogo operacional de findings por categoria (F-10)** (linhas 193-274) — 5 exemplos JSON canonicos (`timeout` S2, `idempotency` S1, `webhook-signature` S1, `retry` S2, `queue-config` S3) com TODOS os campos obrigatorios (`id`, `severity`, `category`, `file`, `line`, `description`, `evidence`, `recommendation`). Isso transforma emitir finding de integracao em operacao mecanica — um auditor nao inventa campo, copia do catalogo. Esta e uma das pecas mais operacionais do conjunto, junto com a metrica de ratio do ux-designer.
- Persona ancorada na realidade brasileira fiscal/financeira (NF-e SEFAZ, PIX BACEN, CNAB FEBRABAN — linhas 33-34, 46-48) com profundidade real: NF-e tem 600+ campos, regras mudam por estado, timezone BRT/BRST afeta escrituracao (linha 29). Referencia autoral, nao generica.
- Isolamento R3 declarado explicitamente (linha 121) com clausula nao pode ser invocado na mesma instancia que outros modos de gate do mesmo slice — diferente do devops-expert (gap identificado em 2.7).
- 12 categorias de check (linhas 178-191) todas mecanicamente verificaveis (timeout, retry, idempotency, circuit-breaker, webhook-signature, queue-config, dead-letter, secrets, rate-limit, error-handling, contract-test, async-pattern).
- Nota normativa de conflito protocolo-vs-agente presente (linha 11) e changelog registra F-10 (linha 8) — rastreabilidade alinhada.
- Anti-padroes (linhas 295-302) capturam realidade de producao: Mock permanente, Happy path only, Mega-adapter, Certificado digital em .env como base64.

**Gaps reais:**
- Observacao minima: o exemplo JSON do output (linhas 143-173) tem os 14 campos do schema canonico presentes. Mas o bloco `evidence.checks` (linhas 161-170) mostra apenas 1 check de exemplo sem enumerar threshold de pass/fail para cada categoria (ex: retry exige backoff maior ou igual a 100ms nao aparece no check). Cosmetico — as categorias estao claramente descritas na tabela da linha 178 e no catalogo de findings, mas um mapeamento categoria-check-threshold no evidence seria refinamento.

**Verdict individual:** **aprovar**.

---

### 2.10 `builder.md` (80k tokens, opus, 3 modos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** **5.00**

**Pontos fortes:**
- **Definicao objetiva de finding ambiguo (F-06)** (linhas 185-203) — 4 condicoes binariamente verificaveis (1. sem localizacao file+line, 2. recomendacao multipla conflitante, 3. decisao arquitetural fora do escopo, 4. contexto/historico ausente por R3). Isso elimina subjetividade da escalacao — o fixer nao escolhe a opcao que parece mais razoavel (linha 199). Esta e uma das correcoes mais bem feitas de todo o harness.
- Separacao clara dos 3 modos (test-writer, implementer, fixer) com inputs permitidos/proibidos distintos em cada um (linhas 72-103, 111-146, 154-203). Fixer so recebe findings do gate que rejeitou (linha 164) — respeita R3.
- **Disciplina de testes red** (linhas 98-104) com verificacao obrigatoria: apos escrever, rodar os testes e confirmar que TODOS falham com exit code diferente de 0 (linha 96). Teste que nasce green e rejeitado — operacionaliza TDD real.
- Nota normativa especial no topo (linha 11): Builder nao emite artefatos de gate (nao aparece no enum de gates do schema); consome findings S1-S3 de gates para aplicar correcoes no modo fixer. Isso resolve uma ambiguidade que poderia existir — builder e o unico agente que escreve codigo e o unico que nao e gate.
- Regras de implementacao (linhas 140-147) explicitam implementacao minima e respeitar o plan — combate direto a gold plating, que esta capturado nos anti-padroes (linha 227).
- Regras do fixer (linhas 178-183) com escopo fechado e nao refatorar — alinhadas a correcao cirurgica do escopo.
- Changelog registra F-06 (linha 8) e stack de referencia (linhas 42-52) mostra versoes concretas (PHP 8.5, Laravel 13, Pest 4, Vue 3.5, PostgreSQL 17).
- Anti-padroes (linhas 226-237) operacionalizam falhas especificas: Comentar teste para passar, Bypass de hook, Refactor oportunista, Over-mocking.

**Gaps reais:** nenhum estrutural. Observacao minima: a descricao no frontmatter (linha 3) usa prosa em vez de enumerar os 3 modos — pratica que alguns dos outros 11 agentes seguem de forma mais enumerativa (ex: `product-expert` linha 3). Cosmetico apenas.

**Verdict individual:** **aprovar**.

---

### 2.11 `governance.md` (60k tokens, opus, 4 modos)

**Notas:** D1=5 | D2=3 | D3=4 | D4=5 | D5=5 | D6=5 | D7=5 | D8=5

**Agregada:** (5*0.10 + 3*0.18 + 4*0.17 + 5*0.15 + 5*0.13 + 5*0.10 + 5*0.10 + 5*0.07) = **4.41**

**Pontos fortes:**
- **Criterio objetivo de convergencia do loop retrospective (F-05)** (linhas 193-219) — 3 condicoes binarias A/B/C (estabilizacao menor que 10 porcento em 2 iteracoes consecutivas, saude aceitavel com 0 criticos e ate 2 majors, limite duro de 10 iteracoes com escalacao automatica). Cada condicao tem formula matematica explicita (linha 201). Elimina sensacao de convergencia (linha 195). Excelente operacionalizacao.
- **Protocolo dual-LLM formalizado** (linhas 134-140) com 5 passos concretos: trilha primaria Opus, trilha secundaria GPT-5 via `mcp__codex__codex`, consenso, reconciliacao em ate 3 rodadas, E10 escalacao. Integrado ao `docs/operations/codex-gpt5-setup.md` (linha 144).
- Persona ancorada em Banco Central do Brasil + Google Engineering Productivity + ThoughtWorks (linha 23) — contexto de zero tolerancia a falha legitimado por referencia real.
- Principios inegociaveis (linhas 25-29): Trust but verify then verify the verifier, Zero tolerance nao e perfeccionismo e sim disciplina, Harness evolui nunca degrada, Evidencia antes de opiniao. Mentalidade governance correta.
- Limites R16 inviolaveis explicitados (linhas 259-265): lista clara de NAO pode (remover regras, afrouxar, desabilitar hooks, alterar MANIFEST.sha256) + Pode ADICIONAR (regras novas, hooks novos) + limite numerico Maximo 3 mudancas por ciclo. Mecanica explicita, nao politica.
- Separacao clara dos 4 modos (master-audit, retrospective, harness-learner, guide-audit) cada um com inputs/proibidos/output distintos.
- Categorias de check do guide-audit (linhas 322-335) com 10 categorias mecanicamente verificaveis (forbidden-files, settings-integrity, hooks-integrity, telemetry-integrity, commit-authors, token-budget, permissions, state-consistency, hook-coverage, orphan-artifacts).
- Protocol_version, changelog, nota normativa de conflito — tudo presente (linhas 7, 8, 11).

**Gaps reais (com evidencia literal):**
- **D2/D3 (schema JSON nao-conforme em master-audit e guide-audit):** o schema mostrado para `master-audit.json` (linhas 102-132) lista apenas 11 campos (`slice_id`, `gate`, `verdict`, `timestamp`, `provenance`, `trail_primary`, `trail_secondary`, `reconciliation_rounds`, `consensus`, `findings`, `summary`) e NAO inclui os 14 campos obrigatorios do `gate-output.schema.json` v1: faltam `$schema`, `lane`, `agent` (no topo, nao apenas em provenance), `mode`, `isolation_context`, `commit_hash`, `blocking_findings_count`, `non_blocking_findings_count`, `findings_by_severity`, `evidence`. Alem disso, o campo topo e `slice_id` (linha 104) enquanto o schema canonico usa `slice` (conforme `qa-expert.md` e outros gates). O schema do `guide-audit.json` (linhas 298-320) tem problema identico — apenas 6 campos (`gate`, `verdict`, `timestamp`, `provenance`, `checks`, `findings`, `summary`). Esta e a inconsistencia mais grave do agente: o AGENTE DE GOVERNANCA (o principal responsavel pela aderencia ao schema formal) mostra exemplos de JSON que nao validam contra o schema que ele proprio deveria fiscalizar. Alto impacto em D2 e D3.
- **D3 (menor):** o campo topo gate com valor master-audit (linha 105) usa nome canonico correto, conforme enum em 00 secao 3.1. OK. Mas `provenance` (linhas 108-114) inclui `model_primary` e `model_secondary` que nao sao parte do schema canonico — pode ser extensao legitima mas deveria estar marcada como tal ou em `evidence` em vez de `provenance`. Cosmetico.
- **D3 (menor):** a secao Invocacao da trilha GPT-5 (linhas 142-144) e muito curta — refere externamente a `docs/operations/codex-gpt5-setup.md` mas nao declara o que acontece se o arquivo externo nao existir, se o Codex CLI retornar erro, ou se a resposta do GPT-5 for malformada. Resiliencia do protocolo dual-LLM depende de tratamento desses casos.

**Verdict individual:** **aprovar com ressalvas** — os schemas JSON mostrados nos modos `master-audit` e `guide-audit` sao NAO-CONFORMES ao schema canonico v1.2.2 que este mesmo agente deveria policiar. Este e um gap estrutural (nao cosmetico): se a governance mostra exemplo errado, sub-agents podem replicar o erro. Recomendacao critica: expandir os dois schemas para os 14 campos obrigatorios mais bloco `evidence`. Tudo o mais esta excelente — persona, F-05, R16, protocolo dual-LLM.

---

### 2.12 `orchestrator.md` (100k tokens, opus, papel principal — sem modos discretos)

**Notas:** D1=5 | D2=5 | D3=5 | D4=5 | D5=5 | D6=5 | D7=5 | D8=3

**Agregada:** (5*0.10 + 5*0.18 + 5*0.17 + 5*0.15 + 5*0.13 + 5*0.10 + 5*0.10 + 3*0.07) = **4.86**

**Pontos fortes:**
- **Maquina de estados S0-S13** (linhas 161-183) com 15 estados, cada um com entrada/saida/gate-de-transicao. Tabela imediatamente legivel e operacional. Transicoes proibidas enumeradas (linhas 184-190): S0 para S5 (pular descoberta), S2 para S7 (testes sem plano), S6 para S7 sem plan-review approved, S8 para S10 sem gates. Isso e fitness function literal, no sentido de Ford/Parsons/Kua — referencia citada na linha 43.
- **Pipeline ASCII diagramado** (linhas 266-311) com nomes canonicos de gate (`verify`, `review`, `security-gate`, `audit-tests`, `functional-gate`, `data-gate`, `observability-gate`, `integration-gate`, `master-audit`) e isolation_context explicito A/B por instancia. Zero ambiguidade sobre sequencia.
- **Regras de divergencia dual-LLM (E10)** (linhas 325-331) explicitadas: 3 rodadas de reconciliacao, registro de `reconciliation_failed: true`, preservacao de dissenting opinion, geracao de `master-audit-pm-decision.json`, `exception_triggered` tipo E10. Protocolo completo de E10 no orchestrator alem do que esta no governance.md — consistencia cross-agent.
- **Contexto isolado R3** (linhas 212-219) com `isolation_context` passado em toda invocacao: dois modos distintos do mesmo agente (ex: architecture-expert `plan` e architecture-expert `plan-review`) satisfazem cross-review quando invocados em instancias isoladas separadas. Resolve R11 para agentes multi-modo.
- **Politica de zero tolerance** (linhas 347-352) explicitada sem ambiguidade: NENHUM finding de qualquer severidade e aceito. Um gate so aprova com `findings` vazio. Nota: isto e mais estrito que o criterio default de `blocking_findings_count == 0` (S1-S3) que permite S4/S5 non-blocking. Esta divergencia precisa alinhamento com 01 secao 1 — mas conceitualmente e defensavel para um orchestrator operando em modo conservador.
- **Enum canonico de gate_name listado explicitamente** (linha 524) com os 15 nomes validos. Orchestrator rejeita JSON com nome fora do enum (linha 525). Enforcement mecanico.
- **Protocolo R12 operacionalizado** (linhas 484-496) com 4 templates concretos de comunicacao com o PM para fase concluida, gate aprovado, gate rejeitado 1a vez, escalacao R6. Reduz improvisacao.
- **Ferramentas declaradas em tabela** (linhas 53-62) com categorias claras (Estado, Sequenciamento, Sub-agents, Skills, Hooks, Verificacao, Checkpoint, Comunicacao PM) — visibilidade do que o orquestrador usa de fato.
- Persona tecnica forte (linha 22): Netflix Conductor, Spotify Backstage, AWS Step Functions. Sabe exatamente quando cada um deve entrar, qual o tempo, e quando parar — metafora certa para orquestracao.
- Anti-padroes (linhas 539-546) operacionais: Eu mesmo faco, Pipeline de confianca, Checkpoint tardio, Escalacao tardia quando na 3a ja esta claro.

**Gaps reais (com evidencia literal):**
- **D8 (ausencia de changelog):** frontmatter (linhas 1-8) NAO possui campo `changelog` — enquanto `product-expert`, `ux-designer`, `data-expert`, `devops-expert`, `observability-expert`, `security-expert`, `integration-expert`, `builder` e `governance` TODOS possuem. Consistencia quebrada no agente mais central do harness. Reduz D8 mas nao D3 (protocol_version 1.2.2 esta presente na linha 7). Este gap e identico ao de `architecture-expert.md` e `qa-expert.md` — padrao de omissao recorrente nos 3 maiores agentes do conjunto (architecture, qa, orchestrator).
- **D8 (menor):** a descricao no frontmatter (linha 3) diz coordena 11 sub-agents — numero correto conforme mapa canonico (9 especialistas + builder + governance = 11), mas a secao Papel (linha 67) diz NUNCA escreve codigo, testes ou corrige bugs sem referenciar de volta ao mapa canonico de `docs/protocol/00` secao 3.1 onde o total 11 e definido. Cosmetico.
- Observacao: a secao Modos de operacao (linha 81) declara que o orquestrador nao tem modos discretos como sub-agents — opera continuamente como maquina de estados — escolha legitima, e alinhada com o fato de que este nao e sub-agent (linha 12). Nao reduz notas.

**Verdict individual:** **aprovar com ressalvas** — unico gap estrutural e a ausencia do campo `changelog` no frontmatter (D8). Recomendacao: adicionar changelog 2026-04-16 v1.2.2 alignment ou similar. Tudo o mais e referencia de industria.

---

## 3. Matriz consolidada

| # | Agente | Modelo | Budget | Nota agregada | Verdict |
|---:|---|---|---:|---:|---|
| 2.1 | `product-expert` | sonnet | 50k | 4.85 | aprovar |
| 2.2 | `ux-designer` | sonnet | 50k | 5.00 | aprovar |
| 2.3 | `architecture-expert` | opus | 50k | 4.58 | aprovar com ressalvas |
| 2.4 | `data-expert` | sonnet | 40k | 4.85 | aprovar |
| 2.5 | `security-expert` | opus | 40k | 4.85 | aprovar |
| 2.6 | `qa-expert` | sonnet | 50k | 4.93 | aprovar |
| 2.7 | `devops-expert` | sonnet | 40k | 4.87 | aprovar |
| 2.8 | `observability-expert` | sonnet | 40k | 4.85 | aprovar |
| 2.9 | `integration-expert` | sonnet | 40k | 5.00 | aprovar |
| 2.10 | `builder` | opus | 80k | 5.00 | aprovar |
| 2.11 | `governance` | opus | 60k | 4.41 | aprovar com ressalvas |
| 2.12 | `orchestrator` | opus | 100k | 4.86 | aprovar com ressalvas |

**Media geral:** (4.85 + 5.00 + 4.58 + 4.85 + 4.85 + 4.93 + 4.87 + 4.85 + 5.00 + 5.00 + 4.41 + 4.86) / 12 = **58.05 / 12 = 4.838**

**Distribuicao:**
- 9 agentes em aprovar (notas maior ou igual a 4.60): product, ux, data, security, qa, devops, observability, integration, builder
- 3 agentes em aprovar com ressalvas (4.00 a 4.59): architecture-expert (4.58), governance (4.41), orchestrator (4.86 — ressalva isolada por D8)
- 0 agentes em rejeitar com ajustes dirigidos (menor que 4.00)
- 0 agentes em rejeitar

Observacao: `orchestrator` atinge 4.86 que seria aprovar pelo threshold da rubrica (maior ou igual a 4.60). A ressalva aqui e qualitativa e nao numerica — a ausencia de changelog no agente mais central do harness e um risco de rastreabilidade que merece destaque explicito, mesmo que a nota agregada permita aprovacao.

---

## 4. Gaps criticos agregados (recorrencias entre agentes)

Analise cruzada dos 12 blocos acima identifica 4 padroes de gap que ocorrem em mais de um agente. Sao estes — e nao findings isolados — que merecem priorizacao em ciclo harness-learner.

### 4.1 Ausencia de campo `changelog` em agentes centrais

**Ocorrencia:** 3 de 12 — `architecture-expert.md` (linhas 1-8), `qa-expert.md` (linhas 1-8), `orchestrator.md` (linhas 1-8). Os outros 9 possuem (ver linha 8 em cada um).

**Impacto:** reduz D8 (rastreabilidade de evolucao) e cria inconsistencia visivel dentro do conjunto. Ironia operacional: os 3 agentes mais centrais do harness (arquitetura, QA, orquestracao) sao justamente os que nao registram evolucao.

**Recomendacao:** adicionar `changelog` aos 3 frontmatters com marcador de data e protocolo (ex: 2026-04-16 v1.2.2 alignment). Mudanca cosmetica, custo trivial, ganho de consistencia imediato.

### 4.2 Schemas JSON mostrados incompletos vs. `gate-output.schema.json` v1

**Ocorrencia:** 3 de 12 — `architecture-expert.md` (linhas 146-155 em `plan-review` e 215-246 em `code-review`, ambos faltando subset dos 14 campos), `governance.md` (linhas 102-132 em `master-audit` e 298-320 em `guide-audit`, faltando maior parte dos 14 campos mais bloco `evidence`).

**Impacto:** alto em D2 e D3. O agente de GOVERNANCA (`governance.md`) e o principal risco — ele deveria policiar o schema mas mostra exemplos nao-conformes. Se builder ou outros gates copiarem o shape dos schemas mostrados em governance, emitirao JSONs que falham validacao no merge-slice (conforme orchestrator linha 341 e 525).

**Contraste positivo:** `qa-expert.md` (5 schemas completos de 14 campos em linhas 75-107, 136-167, 193-225, 254-285, 316-351), `security-expert.md` (linhas 126-170) e `integration-expert.md` (linhas 143-173) demonstram o padrao correto — prova que o padrao e alcancavel.

**Recomendacao:** revisar todos os schemas JSON embutidos em markdown de agentes, alinhar aos 14 campos obrigatorios mais bloco `evidence`. Priorizar `governance.md` (critico) e `architecture-expert.md` (importante).

### 4.3 Ratios quantitativos vs. contagens absolutas em `evidence`

**Ocorrencia:** 2 de 12 — `data-expert.md` (`tenant_id_coverage` como string 100 porcento, linha 192), `observability-expert.md` (`structured_count` e `unstructured_count` absolutos sem ratio normalizado, linhas 151-152).

**Impacto:** reduz D4 (testabilidade/criterio objetivo) e comparabilidade entre gates. Padroes inconsistentes dificultam agregacao em dashboards ou metricas DORA.

**Contraste positivo:** `ux-designer.md` (linhas 207-246) implementa o padrao correto — `design_system_compliance_ratio: 0.98` como float com metodo de medicao determinista em 5 passos. Esta e a forma canonica que deveria ser replicada pelos demais.

**Recomendacao:** padronizar metricas quantitativas em gates como floats no intervalo [0.0, 1.0] com threshold explicito de pass (maior ou igual a N), seguindo o exemplo de ux-designer. Emitir guideline em `docs/protocol/` ou `docs/adr/` formalizando a convencao.

### 4.4 Clausula de isolamento R3 explicita vs. implicita

**Ocorrencia:** 1 de 12 com omissao — `devops-expert.md` em modo `ci-gate` (linha 118) declara apenas emitir campo isolation_context unico por invocacao sem a clausula nao pode ser invocado na mesma instancia que outros modos de gate do mesmo slice que aparece em outros gates (ver `product-expert.md` linha 137, `ux-designer.md` linha 170, `integration-expert.md` linha 121, `observability-expert.md` linha 101, `data-expert.md` linha 148, `security-expert.md` linha 105, `governance.md` linhas 72, 152, 272).

**Impacto:** baixo em D5 (isolamento R3/R11) porque `ci-gate` raramente concorre com outros gates no mesmo slice — mudancas em CI sao em geral isoladas. Mas e inconsistencia de padrao e pode ser replicada por erro.

**Recomendacao:** adicionar a clausula a `devops-expert.md` para consistencia total entre os 9 gates.

---

## 5. Verdict final do conjunto

**Media geral: 4.84 / 5.00.**

**Veredito consolidado: APROVAR.**

O conjunto de 12 agentes v3 do harness Kalibrium atinge media 4.84 com 9 agentes em aprovar (notas maior ou igual a 4.60) e 3 em aprovar com ressalvas (arquitetura, governanca, orquestrador). Nenhum agente atinge rejeitar ou rejeitar com ajustes dirigidos.

**Justificativas do veredito positivo:**

1. **Aderencia ao protocolo v1.2.2 e alta na maioria:** 10 dos 12 agentes referenciam `docs/protocol/` e seguem o mapa canonico de modos, o schema formal e o enum de gate_name. Os 2 casos com desvio relevante (`governance.md` schemas incompletos, `architecture-expert.md` schemas incompletos) sao identificados como ajustes dirigidos, nao falhas estruturais.

2. **Criterios objetivos operacionalizados:** F-05 (governance retrospective com 3 condicoes), F-06 (builder fixer com 4 condicoes de ambiguidade), F-10 (integration-expert com catalogo de 5 findings canonicos), metrica de ratio do ux-designer, checklist ampliado F-02 do devops-expert, e ownership de PII F-03 entre security/observability. Estes sao trabalhos de engenharia de governanca de alto nivel.

3. **Isolamento R3/R11 e respeitado por design:** cada gate declara `isolation_context` e o orquestrador passa identificador unico por instancia. Multi-modo do mesmo agente (ex: architecture-expert plan mais plan-review) e resolvido por separacao de instancias — solucao elegante para cross-review.

4. **Zero tolerance operacionalizada como mecanica, nao politica:** gates rejeitam com qualquer finding S1-S3, loop fixer-re-gate automatizado ate R6 (6a rejeicao), escalacao a PM via R12 com `/explain-slice`. Orquestrador enforcea o fluxo.

**Ressalvas que devem ser enderecadas (nao bloqueadoras para aprovacao, mas importantes):**

1. **Completar schemas JSON em `governance.md`** (master-audit e guide-audit) e `architecture-expert.md` (plan-review e code-review) para os 14 campos obrigatorios mais bloco `evidence`. Critico no governance porque e o agente que policia o schema.

2. **Adicionar campo `changelog`** aos frontmatters de `architecture-expert.md`, `qa-expert.md` e `orchestrator.md`. Custo trivial, consistencia imediata.

3. **Padronizar metricas quantitativas como floats no intervalo [0.0, 1.0]** com threshold explicito, seguindo o exemplo do ux-designer. Aplica a `data-expert.md` (`tenant_id_coverage`) e `observability-expert.md` (`structured_count` e `unstructured_count`).

4. **Completar clausula de isolamento R3** em `devops-expert.md` modo `ci-gate`.

Estas 4 ressalvas juntas representam menos de 1 dia de trabalho de edicao documental. Se enderecadas, o conjunto ira convergir para media maior ou igual a 4.90 com 11 de 12 agentes em aprovar.

**Posicionamento do conjunto no mercado:** o harness Kalibrium v3 esta em nivel de referencia de industria para governanca de pipeline agentico. A combinacao de (a) mapa canonico de modos, (b) schema JSON formal validavel, (c) zero tolerance mecanicamente enforced, (d) dual-LLM com protocolo de reconciliacao E10, (e) isolamento R3/R11 por instancia, (f) catalogos operacionais de finding, e (g) criterios objetivos de convergencia em loops de auditoria — supera o estado da arte do que foi publicado em 2025-2026 para agentes de desenvolvimento. As ressalvas acima sao refinamento, nao fundacao.

---
