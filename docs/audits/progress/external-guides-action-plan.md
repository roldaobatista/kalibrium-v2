# External Guides Action Plan — Blocos 8 e 9

**Status:** ✅ **ACEITO pelo PM em 2026-04-11** — ver `docs/decisions/pm-decision-external-guides-2026-04-11.md`
**Data de criação:** 2026-04-11
**Origem:** análise do documento externo `C:\PROJETOS\saas\Harness + Spec-Driven Development.md` (785 linhas, 8368 palavras, 6 perspectivas consolidadas de LLMs sobre harness engineering e Spec-Driven Development).
**Sessão analisadora:** 2026-04-11 — leitura integral, classificação de cada recomendação contra o estado atual do harness V2 (Blocos 0 a 7 do `meta-audit-tracker.md`).
**Relação com tracker principal:** este plano é uma extensão oficial. **Não substitui** o `meta-audit-tracker.md`. Todos os blocos aqui dependem em alguma medida dos Blocos 2-6 do tracker principal.
**Próxima ação:** iniciar em **sessão nova** os itens 9.1 e 9.3 (auditorias sem dependência). Regra de isolamento meta-audit impede execução na sessão de 2026-04-11.

---

## 1. Princípios operacionais obrigatórios

Qualquer item deste plano respeita, sem exceção:

1. **Arquivos selados não são tocados pelo agente.** Itens que exigem modificar `scripts/hooks/*`, `.claude/settings.json`, `.claude/allowed-git-identities.txt`, `.claude/git-identity-baseline`, `scripts/hooks/MANIFEST.sha256` ou `.claude/telemetry/*` precisam passar por `scripts/relock-harness.sh` em terminal externo, executado pelo PM, conforme `CLAUDE.md §9`. Esses itens são marcados como **SELADO** no plano.

2. **Meta-auditorias em sessão nova.** Auditorias do código existente do harness (Bloco 9) **nunca** rodam na mesma sessão que propõe o plano. Vieses confirmatórios são inevitáveis. Cada item de auditoria abre sua própria sessão fresh.

3. **Zero bypass de gate (R9).** Nenhum item justifica desabilitar hook, lint, type-check ou teste.

4. **Toda saída para o PM passa pelo tradutor R12.** Itens que gerem artefato de decisão precisam de versão PM-friendly via `/explain-slice` ou equivalente.

5. **Dependências ordinais:** nada do Bloco 8 começa antes do Bloco 2 (stack decidida) e do Bloco 3 (execução real de testes) estarem fechados no tracker principal. Auditorias do Bloco 9 podem rodar em paralelo.

---

## 2. Classificação resumida dos gaps

Detalhamento da análise completa está na conversa que originou este plano. Resumo:

### ✅ Já temos (confirmação)

Sub-agents especializados (R8), verifier/reviewer isolados e independentes (R3+R11), sanitização de input, hash-lock de sealed files (Bloco 1), telemetria append-only hash-chain, constitution + R1-R12, tradutor PM R12 (previsto Bloco 4), stack só via ADR (R10), autor identificável (R5), escalação R6, skills progressive disclosure, guide-auditor para entropia, cleanup ad-hoc via `/guide-check`, entrega via PR no GitHub, compliance versionado (LGPD/ICP-Brasil/metrologia/fiscal/REPP).

### ❌ Gaps reais endereçados aqui

| # | Gap | Bloco 8 | Sessão necessária | SELADO? |
|---|---|---|---|---|
| 1 | Etapa formal de clarificação de spec | 8.1 | nova (pós-Bloco 2) | não |
| 2 | Eval suite / benchmark de regressão | 8.2 | nova + relock | sim |
| 3 | Observabilidade estruturada de sub-agents | 8.3 | nova + relock | sim |
| 4 | Cleanup workflow recorrente | 8.4 | nova (pós-Bloco 5) | parcial |
| 5 | Feedback loop <30s como KPI | 8.5 | nova (pós-Bloco 3) | depende |
| 6 | `docs/environment-setup.md` consolidado | 8.6 | qualquer (pós-Bloco 2) | não |
| 7 | `observability/` versionado no repo | 8.7 | futura (pós-deploy) | não |
| 8 | Feature flags (decouple deploy/release) | 8.8 | futura (pós-produção) | não |

### 🚫 Conflita com nossa arquitetura (rejeitados explicitamente)

- `.cursorrules` ou `AGENTS.md` simultâneos ao `CLAUDE.md` — **viola R1**. Nossa fonte única é `CLAUDE.md` + `docs/constitution.md`. Rejeitado.
- "Humano = arquiteto técnico" assumido por várias seções do documento — nosso modelo é `humano = PM` (CLAUDE.md §3.1). Recomendações precisam passar pelo tradutor R12.
- Múltiplos harnesses em paralelo (Cursor + Claude Code) — **viola R2**. Rejeitado.

### ⚠️ Merecem auditoria focada (Bloco 9)

| # | Pergunta | Bloco 9 |
|---|---|---|
| A | `validate-review.sh` / `validate-verification.sh` fazem mechanical diff check (AST/grep valida promessas da story)? | 9.1 |
| B | ADR-0002 MCP Policy segue o padrão "code execution + MCP" ou é tool-spam disfarçado? | 9.2 |
| C | `CLAUDE.md` cresceu demais vs recomendação de "AGENTS.md curto + docs/ expansivas"? | 9.3 |

---

## 3. Bloco 8 — Gaps derivados de guias externos

### 8.1 — Skill `/clarify-slice` (etapa formal de clarificação)

**Gap endereçado:** #1
**Dependência:** Bloco 2 (template de spec precisa estar estabilizado pelo `/new-slice`).
**Risco arquitetural:** baixo. Não toca selados, não muda o fluxo principal (adiciona um passo opcional entre `/new-slice` e o `architect` sub-agent).
**SELADO?** Não. Skill nova + script novo fora de `scripts/hooks/`.

**Por que:** O documento externo cita explicitamente o fluxo do Spec Kit (GitHub) como referência para SDD maduro: `constitution → spec → clarify → plan → contracts → tasks → implement`. Nosso fluxo atual salta de `spec.md` direto para `architect` gerar `plan.md`. Ambiguidades que poderiam ser apanhadas em 1 round adicional viram retrabalho no verifier/reviewer.

**Tarefas:**

- [ ] 8.1.1 Criar `scripts/clarify-slice.sh` — lê `specs/NNN/spec.md`, spawna sub-agent em worktree isolada com prompt "liste ambiguidades bloqueantes, decisões pendentes, inconsistências e itens faltantes na spec", output estruturado em `specs/NNN/ambiguities.json`.
- [ ] 8.1.2 Criar `.claude/skills/clarify-slice.md` descrevendo quando usar, inputs, outputs, critério de sucesso.
- [ ] 8.1.3 Adicionar `/clarify-slice NNN` à tabela de comandos no `CLAUDE.md §7` (⚠️ não é selado, mas é parte do contrato — editar com cuidado).
- [ ] 8.1.4 Criar schema `docs/schemas/ambiguities.schema.json` (lista de objetos com `id`, `category`, `blocking: bool`, `question`, `suggested_resolution`).
- [ ] 8.1.5 Atualizar `scripts/smoke-test-scripts.sh` com cenário "clarify em slice com 3 ambiguidades semeadas".
- [ ] 8.1.6 Documentar em `docs/constitution.md §Flow` a posição opcional de `/clarify-slice` entre passos 3 e 4 do fluxo do CLAUDE.md §6.

**Gate de pronto:** 1 slice real (não sintético) executado com `/clarify-slice` gerando `ambiguities.json` não vazio → PM responde as perguntas via edição da `spec.md` → `architect` roda em cima da spec clarificada → `/verify-slice` retorna `approved`. Registro em `docs/reports/clarify-slice-first-run-YYYY-MM-DD.md`.

**Estimativa de esforço (em unidades de trabalho, não tempo):** 4 unidades. Comparável a criar uma skill nova como `/explain-slice`.

---

### 8.2 — Eval suite (benchmark de regressão)

**Gap endereçado:** #2 (e tangencialmente #14 — "evals antes de escalar autonomia")
**Dependência:** Bloco 3 (`/verify-slice` precisa executar testes de verdade para o eval poder medir pass@1).
**Risco arquitetural:** médio. Define métrica crítica que passa a governar se mudanças de prompt ou modelo podem ser aceitas. Mal calibrada, vira ruído; bem calibrada, é o único indicador confiável de regressão.
**SELADO?** Sim — item 8.2.4 modifica `pre-commit-gate.sh`.

**Por que:** Anthropic é explícita: sem eval suite, o engenheiro cai num ciclo reativo (descobre problema só em produção, corrige um ponto, quebra outro). LangChain saltou 52,8% → 66,5% no Terminal Bench 2.0 **sem trocar o modelo**, apenas mudando o harness — a única forma de saber que esse tipo de ganho aconteceu (ou de detectar uma regressão igual) é ter eval automatizada.

**Tarefas:**

- [ ] 8.2.1 Criar diretório `evals/` com 5 tarefas-golden representativas do que o harness precisa resolver:
  - `evals/001-fix-bug-simple/` — bug isolado com repro em teste, slice deve produzir fix que faz o teste passar.
  - `evals/002-feature-crud/` — feature CRUD pequena, 4 ACs, sem integração externa.
  - `evals/003-refactor-sem-quebrar-ac/` — refactor interno que não pode quebrar nenhum AC existente.
  - `evals/004-review-pr-adversarial/` — PR com bug sutil (off-by-one ou race), reviewer deve pegar.
  - `evals/005-responder-pergunta-do-pm/` — pergunta em linguagem de PM sobre o produto, resposta precisa passar por R12.
- [ ] 8.2.2 Criar `scripts/run-evals.sh` — executa cada eval em worktree isolada, mede:
  - `pass@1` (passou no primeiro try) e `pass@3` (passou em até 3 tries)
  - `cycle_time_ms` (ponta a ponta)
  - `tokens_consumed` (soma de todos os sub-agents envolvidos)
  - `r11_agreement` (verifier e reviewer concordaram?)
- [ ] 8.2.3 Baseline inicial em `docs/quality/eval-baseline.json` — contém métricas atuais + hash do commit em que foi gerada.
- [ ] 8.2.4 **[SELADO — requer relock]** Adicionar hook opcional ao `pre-commit-gate.sh`: quando commit toca `.claude/agents/*`, `scripts/*`, `docs/constitution.md` ou `CLAUDE.md`, bloqueia se `run-evals.sh` não rodou nos últimos 7 dias **e** se métricas caíram mais que 5% vs baseline. Gate pode ser desabilitado por env `KALIB_SKIP_EVAL_GATE=1` **apenas** em commits de docs puros (detectado por path allowlist).
- [ ] 8.2.5 Skill `/run-evals` — dispara a suite manualmente e reporta em linguagem de PM.
- [ ] 8.2.6 Documentar política em `docs/policies/eval-policy.md`: quando re-baselinar, quem aprova, como lidar com regressão legítima (ex.: trade-off aceito).

**Gate de pronto:** baseline registrada e commitada. Re-run imediato confirma variância <3% em todas as métricas (estabilidade). Um PR sintético que degrada deliberadamente uma skill falha no gate do 8.2.4.

**Estimativa:** 10 unidades. É o item mais pesado deste bloco.

---

### 8.3 — Observabilidade estruturada dos sub-agents

**Gap endereçado:** #3
**Dependência:** Bloco 5 (CI externo, para onde os traces eventualmente são enviados).
**Risco arquitetural:** médio. Traces acumulam inputs dos sub-agents — se não forem sanitizados e selados, viram vetor de injeção ou vazamento.
**SELADO?** Sim — itens 8.3.3 e 8.3.5 modificam scripts selados e adicionam hook novo.

**Por que:** Temos telemetria append-only do harness em si (Bloco 1, tokens + commits + hooks), mas não temos logs estruturados **dos sub-agents trabalhando**. Quando um slice falha estranho, não há forma de fazer post-mortem do que cada sub-agent viu. Anthropic/Langfuse/LangSmith resolvem esse problema com trace estruturado.

**Tarefas:**

- [ ] 8.3.1 Schema `docs/schemas/agent-trace.schema.json`:
  ```
  {
    "slice_id": "NNN",
    "agent_name": "verifier|reviewer|architect|...",
    "session_id": "uuid",
    "started_at": "ISO8601",
    "finished_at": "ISO8601",
    "input_digest": "sha256 do prompt sanitizado",
    "output_digest": "sha256 do JSON output",
    "tool_calls": [ { "tool": "...", "params_digest": "...", "result_digest": "..." } ],
    "tokens": { "input": N, "output": N },
    "verdict": "approved|rejected|error",
    "hash_chain_prev": "sha256 do registro anterior"
  }
  ```
  Sem **conteúdo cru** — apenas digests. Conteúdo cru vai para `.claude/traces/<slice_id>/<agent>-<session>.raw.json` em diretório separado, lido só para post-mortem explícito.
- [ ] 8.3.2 `scripts/record-agent-trace.sh` — append-only com hash-chain, mesmo padrão do `record-telemetry.sh`.
- [ ] 8.3.3 **[SELADO — requer relock]** Integrar chamada a `record-agent-trace.sh` em `verify-slice.sh` e `review-slice.sh` (ambos selados).
- [ ] 8.3.4 `scripts/query-traces.sh` — leitura read-only, filtra por slice_id/agent_name/verdict. Saída em JSON.
- [ ] 8.3.5 **[SELADO — requer relock]** Novo hook `scripts/hooks/trace-lock.sh` — selo append-only sobre `.claude/traces/`, análogo ao `telemetry-lock.sh`. Registrar no `MANIFEST.sha256`.
- [ ] 8.3.6 Adicionar `.claude/traces/` à lista de sealed dirs em `CLAUDE.md §9`.
- [ ] 8.3.7 Política em `docs/policies/trace-retention-policy.md` — retenção, rotação, quem pode ler o raw, integração com LGPD (se slice tocar dados pessoais, raw vai direto para shred).

**Gate de pronto:** 1 slice real rodado produz trace íntegro (hash-chain fecha). `query-traces.sh` retorna dados corretos. Tentativa do agente editar `.claude/traces/` via Write/Edit/Bash é bloqueada pelos 3 vetores (Edit, Write, Bash). Smoke-test cobre os 3.

**Estimativa:** 12 unidades.

---

### 8.4 — Cleanup workflow recorrente (garbage collection)

**Gap endereçado:** #4, #11
**Dependência:** Bloco 5 (precisa GitHub Action para cron).
**Risco arquitetural:** baixo. Automatiza skill que já existe (`/guide-check`).
**SELADO?** Parcial — item 8.4.3 é GitHub Action, não modifica selados locais; item 8.4.4 só escreve em `docs/incidents/`.

**Por que:** Temos `guide-auditor` sub-agent + skill `/guide-check`, mas uso é ad-hoc. OpenAI chama de "garbage collection da codebase agent-first" e é enfática: entropia é inevitável, cleanup recorrente é parte do harness, não detalhe operacional.

**Tarefas:**

- [ ] 8.4.1 Definir thresholds em `docs/quality/drift-thresholds.json`:
  - `guide_check_findings_max: 0` (nosso estado ideal)
  - `guide_check_findings_warning: 3`
  - `guide_check_findings_critical: 10`
  - `days_since_last_cleanup_max: 14`
- [ ] 8.4.2 `scripts/scheduled-cleanup.sh` — roda `/guide-check`, se findings > warning, abre PR de refactor via `gh pr create` com label `cleanup/auto`.
- [ ] 8.4.3 GitHub Action `.github/workflows/scheduled-cleanup.yml` — cron semanal (segunda-feira, madrugada), chama `scheduled-cleanup.sh`.
- [ ] 8.4.4 Incident log automático em `docs/incidents/cleanup-YYYY-MM-DD.md` quando cleanup aciona — contém findings, PR aberto, métricas.
- [ ] 8.4.5 Política em `docs/policies/cleanup-cadence-policy.md` — quem aprova PR de cleanup, o que fazer se cleanup quebra algo, como escalar.

**Gate de pronto:** cron roda uma vez em ambiente real (ou dispatch manual via `gh workflow run`), e:
- (a) se codebase está clean, registra "no-op, saudável" no log;
- (b) se codebase tem drift semeado artificialmente (para teste), abre PR correto.

**Estimativa:** 6 unidades.

---

### 8.5 — Feedback loop < 30s como KPI

**Gap endereçado:** #5
**Dependência:** Bloco 3 (`verify-slice.sh` precisa executar testes reais, senão a métrica é fictícia).
**Risco arquitetural:** baixo. Métrica derivada de dados já coletados pela telemetria.
**SELADO?** Depende — depende se `slice-report.sh` e `retrospective.sh` são selados. **Auditar antes de escrever as subtarefas definitivas** (é uma pergunta para o Bloco 9).

**Por que:** Anthropic e OpenAI convergem: ciclo curto (<30s do edit à falha-ou-verde) é o determinante #1 de produtividade do harness. Sem medir, não existe. Nosso fluxo de slices pode ou não estar nesse patamar — não sabemos.

**Tarefas:**

- [ ] 8.5.1 Auditar `telemetry.schema.json` — já tem campo `cycle_time_ms` por slice? Senão, é um gap de schema que vira uma subtarefa selada (edit de schema).
- [ ] 8.5.2 Derivar KPI agregado em `scripts/slice-report.sh` — média, p50, p95 dos últimos 10 slices.
- [ ] 8.5.3 Documentar alvo em `docs/quality/harness-kpis.md`: feedback_loop_p95 < 30000ms.
- [ ] 8.5.4 `retrospective.sh` dispara alerta quando slice atual excede o alvo.
- [ ] 8.5.5 Se 3 slices consecutivos excedem, incident automático em `docs/incidents/harness-slowdown-YYYY-MM-DD.md`.

**Gate de pronto:** 3 slices reais rodados têm `cycle_time_ms` registrado no telemetry, aparecem no `slice-report`, e o alerta dispara corretamente quando o slice 4 (semeado com sleep) excede o alvo.

**Estimativa:** 4 unidades (ou 6 se schema precisar ser ampliado via relock).

---

### 8.6 — `docs/environment-setup.md`

**Gap endereçado:** #6
**Dependência:** Bloco 2 (ADR-0001 precisa ter decidido a stack para o setup ser específico).
**Risco arquitetural:** zero. Documentação pura.
**SELADO?** Não.

**Por que:** A primeira metade do documento externo (linhas 1-240) é uma receita concreta e boa para o ambiente Windows 11 + WSL2 + Ubuntu + Docker Desktop + VS Code + runtimes. Hoje essa receita existe dispersa em `GUIA-KALIBRIUM-V2-HARNESS-SETUP.md` (fora do repo). Consolidar dentro de `docs/` evita retrabalho quando o PM reconstruir a máquina ou quando um advisor técnico precisar replicar o ambiente.

**Tarefas:**

- [ ] 8.6.1 Esperar ADR-0001 decidir stack (Node ou Python ou ambos em dev containers separados).
- [ ] 8.6.2 Escrever `docs/environment-setup.md` — Windows host, WSL2+Ubuntu 24.04, Docker Desktop backend WSL2, VS Code + extensões, runtimes do ADR-0001, `.devcontainer/devcontainer.json` template, `.wslconfig` sugerido (12GB/6procs/8GB swap).
- [ ] 8.6.3 Cross-link em `CLAUDE.md §0` (leitura obrigatória — só como referência, não como instrução).
- [ ] 8.6.4 Checklist de validação: PM segue o doc do zero em máquina limpa, projeto roda, testes passam.

**Gate de pronto:** checklist do 8.6.4 executado e registrado em `docs/reports/environment-setup-first-validation-YYYY-MM-DD.md`.

**Estimativa:** 3 unidades.

---

### 8.7 — `observability/` versionado no repo

**Gap endereçado:** #7
**Dependência:** primeiro deploy em produção (staging conta).
**Risco arquitetural:** baixo.
**SELADO?** Não.

**Por que:** OpenAI (harness engineering post) recomenda dashboards, alerts e queries versionados no mesmo repo do produto — "para o agente, o que não está no repo não existe". Para nossa trilha de compliance (LGPD + metrologia + fiscal), ter dashboards de alertas versionados é pré-requisito de auditoria externa.

**Tarefas:**

- [ ] 8.7.1 Estrutura `observability/{dashboards,alerts,queries,runbooks}/` com READMEs stub.
- [ ] 8.7.2 ADR decidindo stack de observability (Grafana + Prometheus vs hosted vs nativo do cloud escolhido) — **depende de ADR-0001 stack + ADR de hosting**.
- [ ] 8.7.3 Primeiro dashboard: `observability/dashboards/slice-health.json` — derivado da telemetria do harness (não do produto ainda).
- [ ] 8.7.4 Primeiro alert: `observability/alerts/harness-drift.yml` — dispara quando `/guide-check` CI externo encontra findings.

**Gate de pronto:** dashboard renderiza com dados reais, alerta dispara em teste de fumaça.

**Estimativa:** 8 unidades. Pós-MVP.

---

### 8.8 — Feature flags (decouple deploy/release)

**Gap endereçado:** #8
**Dependência:** primeiro release real.
**Risco arquitetural:** médio. Feature flags mal governadas viram vetor de vazamento de PII (flag liga feature que expõe dado sem approval de DPO).
**SELADO?** Não.

**Por que:** Para SaaS multi-tenant regulado (LGPD + metrologia), poder **desligar** uma feature sem rollback do deploy é crítico. Rollback de deploy num SaaS com 50+ clientes ativos = incidente. Rollback de feature flag = toggle.

**Tarefas:**

- [ ] 8.8.1 ADR decidindo ferramenta: open-source self-hosted (Unleash, Flagsmith, OpenFeature + PostHog), vs SaaS (LaunchDarkly, Harness.io, Statsig). Critério decisivo: LGPD + dado residente no Brasil + custo.
- [ ] 8.8.2 SDK integrado no stack escolhido no ADR-0001.
- [ ] 8.8.3 Política em `docs/policies/feature-flags-policy.md`: quando usar flag, quem aprova ligar, quanto tempo uma flag vive antes de virar default-on, regra especial para features que tocam PII.
- [ ] 8.8.4 Integração com o hook de compliance: flag que expõe campo novo em entidade pessoal exige assinatura do DPO antes de poder ligar.

**Gate de pronto:** primeira feature real do Kalibrium lançada atrás de flag, ligada em 1 tenant piloto, desligada sem rollback.

**Estimativa:** 8 unidades. Pós-produção.

---

## 4. Bloco 9 — Auditorias focadas (precedem implementação de partes do Bloco 8)

**Regra: cada item deste bloco roda em sessão nova (clean Claude Code, nenhum histórico desta conversa).** Viés confirmatório é inevitável se auditar o harness na mesma sessão que o propôs. Registrado em `memory/feedback_meta_audit_isolation.md`.

### 9.1 — Auditar `validate-review.sh` e `validate-verification.sh`

**Pergunta única:** os scripts validam que as **promessas da story** (endpoints, funções, arquivos prometidos em linguagem natural no slice) correspondem à **realidade do diff** (via AST ou grep estruturado), ou só validam schema JSON?

**Por que importa:** o documento externo propõe "mechanical diff check" como defesa contra agente que alucina "criei o endpoint X" quando X não existe. Temos P7 (verificação de fato antes de afirmação) como princípio, mas não sei se os scripts mecanizam isso semanticamente ou só sintaticamente.

**Entregável:** `docs/audits/internal/validate-scripts-audit-YYYY-MM-DD.md` com:
- Inventário do que cada script valida hoje (por categoria: schema, presença de arquivo, conteúdo do diff, match semântico).
- Lista de gaps vs o que o documento externo propõe.
- Recomendação: já está OK / pequeno ajuste / reescrita.

**Sessão:** nova.

---

### 9.2 — Auditar ADR-0002 MCP Policy vs padrão "code execution + MCP"

**Pergunta única:** nosso uso de MCP segue o padrão que Anthropic recomenda (agente escreve código pequeno que chama 2-3 APIs MCP e filtra dados antes de devolver ao contexto), ou expomos muitos tools crus que o agente chama diretamente (tool-spam)?

**Por que importa:** Anthropic mostra que tool-spam incha contexto, aumenta custo e erro. Se nosso `allowed-mcps.txt` está virando uma lista longa, é sinal do anti-padrão.

**Entregável:** `docs/audits/internal/mcp-policy-audit-YYYY-MM-DD.md` com:
- Inventário de MCPs permitidos hoje.
- Para cada um: número de tools que o MCP expõe, quais o agente usa de verdade nos últimos 10 slices (via telemetria/traces).
- Classificação: code-exec pattern / tool-spam / misto.
- Recomendação de consolidação se necessário.

**Sessão:** nova. Depende do Bloco 8.3 estar pelo menos em 8.3.4 (query-traces) para ter dado real.

---

### 9.3 — Auditar tamanho e escopo do `CLAUDE.md`

**Pergunta única:** há conteúdo no `CLAUDE.md` que deveria migrar para `docs/constitution.md`, e o arquivo hoje passa do "curto e operacional" que Anthropic/OpenAI recomendam, ou está no tamanho certo para o nosso modelo PM-only?

**Por que importa:** A recomendação externa é AGENTS.md curto como "mapa" + docs expansivas como "território". Mas nosso modelo é diferente: PM não lê `docs/constitution.md`, então CLAUDE.md precisa carregar mais contexto operacional. A pergunta é se há uma linha clara entre as duas responsabilidades.

**Entregável:** `docs/audits/internal/claude-md-sizing-audit-YYYY-MM-DD.md` com:
- Análise seção por seção: esta seção é "mapa" (curta, sempre carregada) ou "território" (detalhe, carregado sob demanda)?
- Recomendação de refactor (ou confirmação de que está certo).
- Se refactor: migração proposta com PR mapeado (precisa de relock se CLAUDE.md for selado — **auditar isso antes**).

**Sessão:** nova.

---

## 5. Ordem de execução recomendada

Respeitando dependências do tracker principal e as restrições operacionais:

### Fase A — Pode começar **sem esperar** o tracker principal
Todos rodam em sessão nova, são auditorias read-only:

1. **9.1** — auditoria `validate-*`.
2. **9.3** — auditoria tamanho `CLAUDE.md`.

(9.2 espera 8.3.4 para ter dado real.)

### Fase B — Após Bloco 2 (stack decidida via ADR-0001)

3. **8.6** — `docs/environment-setup.md` (só precisa da stack decidida).
4. **8.1** — `/clarify-slice` (precisa do template de spec estabilizado).

### Fase C — Após Bloco 3 (execução real de testes)

5. **8.5** — feedback loop KPI.

### Fase D — Após Bloco 5 (CI externo operacional)

6. **8.4** — cleanup workflow recorrente.
7. **8.2** — eval suite (mais pesada, encaixe natural depois de CI externo).
8. **8.3** — observability estruturada de sub-agents.
9. **9.2** — auditoria MCP policy (depende de 8.3.4).

### Fase E — Após primeiro deploy (não-MVP)

10. **8.7** — `observability/` versionado.
11. **8.8** — feature flags.

---

## 6. Esforço total e critério de sucesso

**Unidades de trabalho (não tempo):** 8.1 (4) + 8.2 (10) + 8.3 (12) + 8.4 (6) + 8.5 (4-6) + 8.6 (3) + 8.7 (8) + 8.8 (8) + 9.1 (2) + 9.2 (2) + 9.3 (2) = **61-63 unidades**.

Para contexto: o Bloco 1 inteiro do tracker principal (selar o harness contra auto-modificação) teve estimativa equivalente a ~20 unidades. Este plano é ~3x o Bloco 1.

**Critério de sucesso agregado:**

1. `meta-audit-tracker.md` tem link para este arquivo na seção apropriada — feito na mesma sessão que este plano é criado.
2. Cada item 8.x ou 9.x tem gate de pronto verificável e sessão designada.
3. Nenhum item é marcado `[x]` sem evidência de gate de pronto cumprida, registrada em arquivo versionado (`docs/reports/`, `docs/audits/internal/`, ou `docs/incidents/`).
4. Nenhum item modifica arquivo selado sem incident file de relock gerado pelo `scripts/relock-harness.sh`.
5. Meta-auditoria anual do harness (além do próximo go/no-go do Bloco 7 do tracker principal) reavalia se todos os 11 itens deste plano continuam relevantes ou se algum virou obsoleto.

---

## 7. O que este plano explicitamente **não** cobre

- **Mudanças arquiteturais ao modelo "humano = PM".** Várias recomendações do documento externo pressupõem humano técnico. Nós rejeitamos consciente (ver CLAUDE.md §3.1).
- **Adoção de `.cursorrules` ou multi-harness.** Rejeitado por R1+R2.
- **Substituir `guide-auditor` por LangSmith/Langfuse comerciais.** O Bloco 8.3 propõe observability **equivalente** mas self-hosted/versionada no repo, coerente com compliance brasileiro.
- **Migração de plataforma para Harness.io (o produto).** Parte G do documento externo detalha isso; não é nossa direção (usamos GitHub Actions + scripts próprios).
- **Skills da plataforma Harness.io (formato próprio).** Nossas skills seguem o formato `.claude/skills/` do Claude Code.

---

## 8. Rastreabilidade

- **Documento externo analisado:** `C:\PROJETOS\saas\Harness + Spec-Driven Development.md`.
- **Sessão de análise:** 2026-04-11.
- **Tracker principal:** `docs/audits/progress/meta-audit-tracker.md`.
- **Entrada de memória:** `memory/project_meta_audit_action_plan.md` (registrar este arquivo como "extensão Bloco 8+9").
- **Próximo passo único após este arquivo existir:** linkar no `meta-audit-tracker.md §Bloco 6 → próximos` para o PM decidir se aceita os Blocos 8 e 9 como extensão oficial ou se quer fatiar/priorizar diferente.
