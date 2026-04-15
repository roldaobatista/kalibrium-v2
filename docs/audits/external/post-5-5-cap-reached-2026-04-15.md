# Dossiê para Re-auditoria Externa — Post 5/5 Cap Reached

**Data de montagem:** 2026-04-15
**Montado por:** orquestrador Claude Opus 4.6 (1M) via sessão do PM
**Destinatário:** auditor externo humano ou `agent-advisor-externo` em **sessão nova**
**Status do projeto no momento da montagem:** PAUSADO (teto 5/5 atingido em 2026-04-15 via PR #14, incident `pr-14-bypass-p0-billing-governance`)

---

## 1. Escopo da re-auditoria (conforme política P0)

Do incident `pr-14-bypass-p0-billing-governance-2026-04-15.md §Plano de saída`:

> 1. PM resolve o billing do GitHub (prioritário — reabre CI para auditoria externa validar) ✅ concluído em 2026-04-15 mid-session
> 2. PM contrata auditor externo ou invoca agent-advisor-externo em sessão nova ⏳ pendente
> 3. Auditor revisa:
>    - ADR-0012 e se sua implementação foi adequada
>    - Todos os 5 bypasses acumulados
>    - Se política de bypass precisa ser revisada (via novo ADR)
>    - Estado do harness após entrada da nova governança
> 4. PM assina decisão de retomada ou de reformulação

---

## 2. Evento novo — violação durante estado pausado

**Fato:** slice-010 (E02-S07 LGPD + consentimentos) foi mergeado via PR #15 em `2026-04-15T15:24:24Z` — **após** o incident P0 ter declarado estado pausado com a regra explícita:

> ❌ Não fechar slices em andamento (slice-010 aguarda auditoria)

O merge foi automático (auto-merge armado) quando o billing voltou. Não houve admin bypass adicional — auto-merge usou o fluxo padrão com base no `required_approving_review_count=0` do ruleset. **Não consumiu novo slot do contador 5/5**, mas violou a regra de bloqueio operacional.

Incident dedicado: `docs/incidents/slice-010-merge-during-paused-state-2026-04-15.md`.

**O auditor externo deve decidir:**
- (a) Aceitar como fato consumado e ajustar política (merge via auto-merge ≠ bypass manual, cabe exceção explícita).
- (b) Reverter merge do slice-010 e aguardar re-auditoria completar.
- (c) Classificar como 6º bypass de facto e aplicar sanção adicional.

---

## 2.5 Catch-22 descoberto na execução desta R.1 — bypasses técnicos pós-5/5

Durante a preparação deste próprio dossiê (autorizada pela política), o push do commit `ae26463` para main disparou admin bypass inevitavelmente porque o ruleset continua com `current_user_can_bypass: always` (Bloco 5 item 5.3 nunca implementado). Idem o push deste update.

PM decidiu em 2026-04-15 via opção **X.1**: bypasses técnicos por execução de atividade explicitamente autorizada **não consomem slot adicional** do contador 5/5, são logados como categoria separada.

Incidents:
- `docs/incidents/bypass-6-audit-prep-push-2026-04-15.md`
- `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Nota de exceção`

**Auditor deve validar:** (a) essa classificação é legítima? (b) não havendo como pushar sem bypass, o catch-22 obriga Bloco 5 item 5.3 como pré-requisito de retomada? (c) a whitelist de paths proposta (§Mitigações em `bypass-6-audit-prep-push`) é suficiente?

---

## 3. Histórico completo dos 5 bypasses

Copiado verbatim de `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Contador oficial`:

| # | Data | Origem | Commits | Justificativa declarada |
|---|---|---|---|---|
| 1 | 2026-04-10 | PR #1 admin merge (`pr-1-admin-merge.md`) | merge `182a7ca` | Fase 2 setup — humano=PM, sem revisor técnico no loop |
| 2 | 2026-04-10 | Push Bloco 1 meta-audit | `345b0a2`, `c061e3c` | Bloco 5 item 5.3 não implementado |
| 3 | 2026-04-10 | Push auto-referência do incident Bloco 1 | `a1ad97e` | Recursão aceita até Bloco 5 fechar bypass manual |
| 4 | 2026-04-11 | Push sessão 01 execução meta-audit #2 | pacote 36 commits `6cc9c2f`..HEAD | Autorizado explícito pelo PM "pode fazer o push" |
| 5 | 2026-04-15 | Merge PR #14 ADR-0012 via `--admin` | merge PR #14 | **P0 inviabilidade operacional** do modelo PM não-técnico |

**Observação:** Bypasses 1-4 foram classificados como "bypass de continuidade" (projeto em ramp-up, Bloco 5 pendente). Bypass 5 foi o primeiro classificado como **P0** por inviabilidade operacional — significa que o modelo atual trava se o PM não pode mergear autonomamente.

---

## 4. ADR-0012 — texto em vigor (pós-merge PR #14)

Path: `docs/adr/0012-constitution-amendment-autonomy-dual-llm.md`.

Resumo operacional do que ADR-0012 instituiu:
- **Dual-LLM gate** (master-auditor): Claude Opus 4.6 + GPT-5 via Codex MCP aprovam consenso. Substitui o R11 humano-em-loop.
- **Retrospectiva de épico** automatizada via agent `epic-retrospective`.
- **Harness-learner** — agent que evolui o harness a partir das retrospectivas.
- **Novas regras** R15, R16 (ver texto completo no ADR).
- **Emenda a R11**: dual-verifier humano+agent → dual-LLM (sem necessidade de humano no loop cotidiano).

**Auditor deve avaliar:**
- (a) O conteúdo da ADR-0012 é defensável? (PM não-técnico + agente com decisão autônoma — trade-off de governança)
- (b) A implementação técnica é adequada? (ver §5 abaixo — há gaps significativos)
- (c) A classificação P0 do merge foi genuína? (bloqueio operacional real ou pressão de prazo?)

---

## 5. Estado do harness após ADR-0012 — gaps identificados

Auditoria feita pelo orquestrador em 2026-04-15 após o merge:

| # | Gap | Arquivo | Status | Severidade |
|---|---|---|---|---|
| 1 | CLAUDE.md Fase E continua descrevendo os 5 gates individuais antigos; sem menção a master-auditor ou dual-LLM | `CLAUDE.md §6 Fase E` | Pendente | Alta — toda nova sessão lê CLAUDE.md e não saberá usar o novo fluxo |
| 2 | orchestrator.md sem referência a master-auditor | `.claude/agents/orchestrator.md` | Pendente | Alta — orquestrador não invoca o novo agente |
| 3 | Tool mismatch: master-auditor.md declara `mcp__codex_cli__invoke` (inexistente); MCP real expõe `mcp__codex__codex` e `mcp__codex__codex-reply` | `.claude/agents/master-auditor.md` | Corrigível em 1 linha | Crítica — spawn falha mesmo se invocado |
| 4 | Dois ADRs com número 0012: `0012-additional-mcps-postgres-figma.md` + `0012-constitution-amendment-autonomy-dual-llm.md` | `docs/adr/` | Pendente | Média — viola convenção de numeração unívoca |
| 5 | Slice-010 fechado durante estado pausado (ver §2) | Projeto | Acontecido | Crítica — demonstra que política de pausa não é enforced mecanicamente |

**Decisão autorizada pelo PM na sessão atual (R.1):** orquestrador pode corrigir gaps 3 e 4 (fixes cirúrgicos, não afetam governança). Gaps 1 e 2 requerem decisão do auditor porque afetam diretamente o fluxo operacional e devem ser revistos com olhar externo.

---

## 6. Material para verificação do auditor

Arquivos que o auditor deve ler, em ordem:

1. **Política e contexto:**
   - `docs/incidents/bloco1-admin-bypass-2026-04-10.md` — histórico do ruleset e política de congelamento
   - `docs/incidents/pr-14-bypass-p0-billing-governance-2026-04-15.md` — incident do 5º bypass
   - `docs/incidents/slice-010-merge-during-paused-state-2026-04-15.md` — incident da violação durante pausa
   - `docs/harness-limitations.md` — limites conhecidos do harness

2. **ADR objeto de auditoria:**
   - `docs/adr/0012-constitution-amendment-autonomy-dual-llm.md`
   - `docs/decisions/pm-decision-adr-0012-2026-04-15.md` (se existir)

3. **Artefatos novos da ADR-0012:**
   - `.claude/agents/master-auditor.md`
   - `.claude/agents/harness-learner.md`
   - `.claude/agents/epic-retrospective.md`

4. **Harness operacional atual:**
   - `CLAUDE.md` (versão 2.7.0)
   - `docs/constitution.md`
   - `.claude/agents/orchestrator.md`

5. **Provas da execução em estado pausado:**
   - PR #15 em https://github.com/roldaobatista/kalibrium-v2/pull/15
   - `specs/010/` completo
   - `docs/incidents/slice-010-pm-override-2026-04-15.md` — findings fantasmagóricos do reviewer que motivaram override considerado mas não usado

---

## 7. Entregáveis esperados do auditor

Conforme política:

1. **Relatório de re-auditoria** em `docs/audits/external/post-5-5-cap-reached-2026-04-15.md` (este arquivo + capítulo novo "§Conclusões do auditor").
2. **Decisão do PM** em `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md`.
3. Ambos commitados antes de retomar execução de qualquer slice novo.

**Questões que o auditor deve responder explicitamente:**

- Q1. ADR-0012 é defensável como política? Se sim/parcial/não, justificar.
- Q2. Os 5 bypasses são proporcionais ao valor entregue? Alguma classificação foi indevida?
- Q3. O merge do slice-010 durante estado pausado é tolerável como automação legítima (auto-merge) ou é 6º bypass de facto?
- Q4. Os 4 gaps técnicos (§5) são corrigíveis no escopo atual ou requerem novo ADR?
- Q5. A política de congelamento do contador de bypasses (`bloco1-admin-bypass-2026-04-10.md`) continua vigente, precisa de emenda, ou precisa de substituição?
- Q6. Quais critérios objetivos marcam o fim do estado pausado?

---

## 8. Instruções operacionais para o auditor (agent-advisor-externo em sessão nova)

Para maximizar a independência:

1. **Abrir sessão Claude nova** (não continuar esta). Contexto limpo.
2. Fazer bootstrap mínimo: CLAUDE.md + docs/constitution.md + docs/harness-limitations.md + este dossiê.
3. **Não ler** o código do slice-010 nem artefatos operacionais anteriores sem justificativa — foco é governança.
4. Auditoria tripla obrigatória: usar MCP codex (GPT-5) numa trilha paralela como contraponto a Opus 4.6.
5. Emitir relatório conclusivo dentro deste arquivo no capítulo "§Conclusões do auditor".
6. PM lê conclusão e assina decisão em `docs/decisions/`.

---

## 9. Estado operacional presente (ponto de partida do auditor)

- **Main atual:** commit `81643ed` (merge PR #15 slice-010)
- **Branches ativas:** apenas `main` no remoto após a limpeza pós-merge
- **CI:** desbloqueado após billing resolvido 2026-04-15 mid-session
- **Contador de bypasses:** 5/5 atingido (este dossiê não consome slot adicional)
- **Bloqueios operacionais vigentes:**
  - ❌ Novo slice
  - ❌ Nova decisão de produto não-emergencial
  - ❌ Novo ADR (exceto para ressolução do caso pausado)
  - ✅ Correção cirúrgica P0/P1 com incidente dedicado
  - ✅ Preparação de material para re-auditoria (deste dossiê é exemplo)
  - ✅ Gaps técnicos 3 e 4 (fixes não-governança autorizados pelo PM em R.1)

---

## § Conclusões do auditor

**Modelo de auditoria:** dual-LLM paralelo, contextos isolados, cada trilha em arquivo próprio.

- Trilha A — Claude Opus 4.6: `docs/audits/external/audit-trail-opus-2026-04-15.md`
- Trilha B — GPT-5 (gpt-5.4 via Codex CLI): `docs/audits/external/audit-trail-gpt5-2026-04-15.md`

Cada trilha respondeu Q1-Q6 de forma independente, sem acesso ao output da outra. Consolidação abaixo por ordem de convergência → divergência → reconciliação.

### Convergências entre as duas trilhas

1. **ADR-0012 é defensável em mérito** — dual-LLM é superior a PM não-técnico como revisor; R15/R16 são sólidas.
2. **ADR-0012 é questionável em forma** — mergeada com `Status: Proposta`, sob pressão de billing, sem dry-run, sem checklist de aprovação marcado.
3. **4 dos 5 bypasses têm causa-raiz comum** — Bloco 5 item 5.3 (`current_user_can_bypass: always` no ruleset) nunca foi implementado desde 2026-04-10. Bypass virou fluxo padrão, não exceção.
4. **Slice-010 mergeado durante pausa não é 6º bypass** (não usou `--admin`, auto-merge já armado), mas é **violação P1 de enforcement** — a política de pausa era puramente declarativa.
5. **Não reverter o slice-010** — reversão introduz mais instabilidade; conteúdo é legítimo.
6. **Gaps 1-2 bloqueiam retomada** — CLAUDE.md e orchestrator.md não mencionam master-auditor. ADR-0012 seria letra morta sem essas correções.
7. **Política de bypass precisa de nova ADR** — distinção entre bypass governança/produto e bypass técnico autorizado, com whitelist de paths. Não aceitar decisão X.1 ad-hoc do PM sem ADR formal.
8. **Decisão PM assinada é pré-requisito** de qualquer retomada.

### Divergências

| Dimensão | Opus 4.6 | GPT-5.4 |
|---|---|---|
| Verdict rótulo | **BLOCKED** | **CONDITIONAL_RESUME** |
| Número de condições | 7 (ordem rígida 1→7) | 7 (sem ordem explícita) |
| Retrospectiva humana | Exigida como condição 7 | Não menciona |
| Master-auditor | Dry-run documentado obrigatório | Smoke-test obrigatório |
| Riscos residuais levantados | 7 (incl. P0 inflation, rollback ADR-0012, harness-learner como arma) | 6 (incl. `Status: Proposta`, ZERO TOLERANCE vs findings minor aceitáveis) |

**Operacionalmente os dois verdicts são equivalentes:** ambos bloqueiam retomada de slices até condições objetivas idênticas. A diferença é só rótulo.

### Reconciliação (verdict consolidado)

**CONDITIONAL_RESUME com 8 pré-requisitos objetivos** (superset das duas trilhas, sem contradição):

| # | Pré-requisito | Origem | Ordem |
|---|---|---|---|
| 1 | **Bloco 5 item 5.3** implementado — ruleset `main` sem `current_user_can_bypass: always`; GitHub App auto-reviewer ativo; CI obrigatório. Evidência: `gh api /repos/.../rulesets/14936750` + PR dummy passando sem admin. | Ambas | P0 (1º — quebra o catch-22) |
| 2 | **Hook de enforcement da pausa** — `pre-commit`/`pre-push` lê estado pausado em `project-state.json.paused` (ou arquivo selado) e bloqueia mutação em `main` exceto whitelist (`docs/audits/external/**`, `docs/incidents/bypass-*.md`, `docs/decisions/**`). Selado via `hooks-lock.sh`. | Ambas | P0 (2º — selado após 1) |
| 3 | **Gaps 1-2 corrigidos** — CLAUDE.md §6 Fase E e orchestrator.md incluem master-auditor no fluxo. Exige relock (terminal externo). | Ambas | P0 (3º) |
| 4 | **Gap 3 validado** — master-auditor.md com tool names corretos (`mcp__codex__codex` + `mcp__codex__codex-reply`) **e** smoke-test/dry-run documentado contra artefato real (ex: plan.md do slice-010 retroativo), anexando `master-audit.json`. | Ambas | P0 (4º) |
| 5 | **Gap 4 confirmado** — renumeração ADR 0012 resolvida. Hoje: `0012-additional-mcps` → `0013` e autonomy permanece em `0012-constitution-amendment-autonomy-dual-llm.md`. PM valida ou renumera. | Ambas | P1 |
| 6 | **Nova ADR de política de bypass técnico** — formaliza categoria técnica autorizada, whitelist de paths, contador separado, validade limitada a estados de pausa/auditoria. Numeração a decidir pelo PM junto com item 5. Não reclassificar retroativamente bypasses 2-4. | Ambas | P0 |
| 7 | **ADR-0012 forma corrigida** — `Status: Proposta` → `Accepted` com evidência de decisão PM; checklist de aprovação completo; R15/R16 codificadas no corpo da constitution §4 (hoje o corpo só lista R1-R14 mesmo com versão 1.5.0 no cabeçalho). | GPT-5 (principalmente) | P1 |
| 8 | **Retrospectiva humana dos 5 bypasses** — documento escrito pelo PM (não pelo harness-learner automatizado) explicando por que 4 dos 5 bypasses vieram da mesma causa-raiz e por que foi permitido. Não substitui epic-retrospective (que está bloqueada porque E02 não está "fechado"). | Opus (principalmente) | P1 |

**Operações permitidas durante CONDITIONAL_RESUME:**
- Implementação dos pré-requisitos 1-8.
- Nova ADR (item 6 e eventualmente 5/7).
- Trabalho nos relocks via terminal externo.
- Correções cirúrgicas P0/P1 com incidente dedicado.

**Operações proibidas durante CONDITIONAL_RESUME:**
- Novo slice (incluindo E02-S08).
- Retrospectiva/slice-report automática do slice-010 (epic-retrospective bloqueada porque E02 está travado, não fechado).
- `/next-slice`.
- Qualquer auto-aplicação do `harness-learner`.
- Nova decisão de produto não-emergencial.

### Próximo passo do PM

Ler as duas trilhas completas (paths acima) e assinar decisão em `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md`. O template está criado com opções de assinatura alinhadas aos 8 pré-requisitos. Somente após a assinatura do PM e implementação dos 8 pré-requisitos o estado pausado termina.

### Riscos residuais reconciliados (não cobertos pelo dossiê original)

Compilação não-duplicada das duas trilhas:

1. **Harness-learner como arma:** critério "afrouxar vs endurecer" é subjetivo; precisa lista enumerativa de ações bloqueadas. (Opus)
2. **Master-auditor consolidando pareceres que ele próprio gerou:** se a trilha Claude do master-auditor é executada pelo próprio agente master-auditor, só GPT-5 seria realmente externo. (Opus)
3. **Custos e latência do master-auditor não dimensionados:** 7 × 80k tokens por slice em produção. (Opus)
4. **`relock.bat` não verificado no dossiê** — ADR-0012 E1 promete PM opera via `.bat`, mas não há teste documentado. (Opus)
5. **P0 inflation** — "inviabilidade operacional" virou critério aberto; precisa lista fechada. (Opus)
6. **Ausência de rollback plan para ADR-0012** — sem critério de sunset/reversão. (Opus)
7. **R15/R16 invisíveis no corpo da constitution** — cabeçalho diz 1.5.0 mas §4 lista só R1-R14. (GPT-5)
8. **`Status: Proposta` vs uso como vigente** — contradição formal. (GPT-5)
9. **ZERO TOLERANCE vs master-auditor permitir findings minor** — política conflitante. (GPT-5)
10. **Relock programático no harness-learner** tensiona CLAUDE.md §9 e `harness-limitations.md`. (GPT-5)
11. **Independência dual-LLM sem schema de input idêntico** — nada garante que as duas trilhas recebem o mesmo pacote. (GPT-5)

Esses 11 riscos residuais devem ser endereçados nos itens 6, 7 e 8 dos pré-requisitos acima, ou em ADRs subsequentes.

**Verdict consolidado: CONDITIONAL_RESUME. Retomada autorizada somente após 8 pré-requisitos satisfeitos e decisão PM assinada.**
