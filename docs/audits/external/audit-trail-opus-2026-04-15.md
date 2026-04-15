# Auditoria Externa — Trilha Claude Opus 4.6

**Data:** 2026-04-15
**Auditor:** Claude Opus 4.6 (sessão independente, contexto limpo)
**Verdict final:** BLOCKED

---

## Sumário executivo

O projeto acumulou 5/5 bypasses administrativos em apenas 5 dias, atingiu o teto declarado, e ainda assim registrou um 6º bypass técnico (`ae26463`) e uma violação operacional (merge do slice-010 durante pausa) — tudo após a auto-declaração de PAUSADO. A causa-raiz é estrutural e anterior à ADR-0012: o ruleset da `main` nunca teve `current_user_can_bypass` removido (Bloco 5 item 5.3 pendente desde 2026-04-10), e a política de pausa é puramente declarativa (zero enforcement mecânico). A ADR-0012 é defensável em mérito (substituir humano não-técnico por dual-LLM é razoável), mas sua implementação está inconsistente (tool mismatch crítico, número duplicado, CLAUDE.md e orchestrator não referenciam o novo fluxo) e foi adotada em pressão operacional, não após deliberação calma. **Retomada não autorizada** até (a) Bloco 5 item 5.3 implementado, (b) enforcement mecânico da pausa, (c) gaps 1-4 corrigidos, (d) fluxo dual-LLM testado em pelo menos um slice dry-run com auditoria humana concorrente.

---

## Respostas às questões Q1-Q6

### Q1. ADR-0012 é defensável como política?

**Parcialmente defensável — aprovado em mérito, reprovado em forma.**

Em mérito: substituir R11 humano-em-loop por dual-LLM (Opus 4.6 + GPT-5) quando o humano é PM não-técnico é razoável e até superior — dois modelos independentes de fornecedores distintos, com contextos isolados, fornecem mais sinal adversarial do que um PM que não lê código. A adição de R15 (retrospectiva pós-épico) e R16 (harness-learner com guardrails de só-endurecer) é sólida.

Em forma: a ADR tem **Status: Proposta** no próprio cabeçalho, foi mergeada como se fosse "Accepted" via `--admin`, sob pressão de billing expirado, classificada P0 num momento em que a alternativa (esperar 1-2 dias) era explicitamente conhecida. Isso é precedente ruim: qualquer decisão futura poderá ser classificada P0 por argumento de "inviabilidade operacional". A ADR também não contém o teste de suficiência — nenhum slice rodou sob o novo regime antes do merge. É uma emenda constitucional adotada sem provar que funciona.

### Q2. Os 5 bypasses são proporcionais ao valor entregue? Classificação indevida?

Análise por número:

- **Bypass 1 (PR #1, 2026-04-10):** defensável. Fase 2 setup, pré-existência do ruleset, não havia revisor técnico. Classificação correta.
- **Bypass 2 (`345b0a2`+`c061e3c`, 2026-04-10):** defensável como "bypass de continuidade" porque o próprio Bloco 1 estava criando o mecanismo que tornaria futuros bypasses desnecessários. Mas abriu a porta cultural para normalizar push direto.
- **Bypass 3 (`a1ad97e`, 2026-04-10):** questionável. Auto-referência honesta é boa, mas mesmo dia do bypass 2 — poderia ter sido squash no bypass 2. Consumiu slot por granularidade excessiva.
- **Bypass 4 (36 commits, 2026-04-11):** **indevido**. 36 commits num único push é o oposto de "bypass cirúrgico". "Pode fazer o push" do PM não equivale a justificativa P0. Deveria ter sido dividido em PRs mesmo que isso exigisse tocar o ruleset primeiro.
- **Bypass 5 (PR #14, 2026-04-15):** **mais grave**. Foi a emenda constitucional — o tipo de mudança que **mais** exige dual-review legítimo — mergeada sob pressão de billing. A justificativa "P0 por inviabilidade operacional" é falha: a inviabilidade foi criada pelo próprio fato de não existir auto-reviewer (bypass 5.3 nunca implementado). Self-inflicted P0.

**Padrão:** 4 dos 5 bypasses estão ligados à mesma causa-raiz (5.3 pendente). O projeto nunca deveria ter saído da Fase 2 sem fechar 5.3 primeiro. Bypass não foi exceção — virou fluxo padrão.

### Q3. Merge do slice-010 durante pausa — automação legítima ou 6º bypass de facto?

**Violação operacional, não 6º bypass — mas a distinção é semântica e expõe um gap maior.**

Tecnicamente não houve admin bypass: o merge usou fluxo padrão (auto-merge já armado antes da pausa, ruleset permite `required_approving_review_count=0`). Não consumiu slot.

Substantivamente, o merge ocorreu **contra** a regra explícita "❌ Não fechar slices em andamento (slice-010 aguarda auditoria)" de `pr-14-bypass-p0`. O fato de a regra ser puramente declarativa — sem `project-state.json.paused`, sem hook `pre-push`, sem check em `/merge-slice` — significa que a **pausa não é um estado do sistema, é um aviso em markdown**. Isso é arquitetonicamente pior do que um 6º bypass: mostra que o harness não tem conceito operacional de "projeto pausado", o que invalida qualquer promessa futura de pausa.

Classificação: opção (d) do incident — violação P1 com obrigação futura de enforcement mecânico. **Não reverter** o merge (opção b introduz mais instabilidade e o conteúdo do slice-010 é legítimo), mas **bloquear retomada** até o hook existir.

### Q4. Os 4 gaps técnicos (§5 do dossiê) — corrigíveis ou novo ADR?

- **Gap 1 (CLAUDE.md Fase E desatualizada):** corrigível no escopo atual. Edição de texto. Prioridade alta — toda sessão nova lê CLAUDE.md e não saberá do master-auditor.
- **Gap 2 (orchestrator.md sem master-auditor):** corrigível no escopo atual. Edição de texto + diagrama. Prioridade alta — se o orquestrador não o invoca, ADR-0012 é letra morta.
- **Gap 3 (tool mismatch `mcp__codex_cli__invoke`):** corrigível em 1 linha (já declarado corrigível pelo PM). Mas é **crítico**: significa que ninguém rodou o master-auditor sequer uma vez antes do merge. Prova empírica de que ADR-0012 foi mergeada sem teste. Requer além da correção um **dry-run documentado** antes de usar em produção.
- **Gap 4 (ADR 0012 duplicado):** corrigível no escopo atual. Renumerar o ADR antigo (stack additional-mcps) ou renumerar o novo. Prioridade média.

**Nenhum exige novo ADR.** Todos são dívida de implementação de ADR-0012. Mas o Gap 3 é evidência de que o PM aprovou um ADR cuja implementação não foi validada — falha de processo.

### Q5. Política de congelamento do contador — vigente / emenda / substituição?

**Precisa de emenda formal via novo ADR — não opção X.1 ad-hoc.**

A política original (5/5 cap em `bloco1-admin-bypass`) assumia que após o 5º bypass o projeto pararia até Bloco 5.3 ser resolvido. Essa assunção quebrou no exato instante em que o dossiê de re-auditoria precisou ser pushado (catch-22 §2.5). A decisão X.1 do PM ("bypasses técnicos autorizados não consomem slot") é **racional mas não-autorizada pela política vigente** — é o PM emendando política sem ADR, o que viola R9 (nada de bypass de gate) e o próprio §5 amendment procedure da constituição.

Recomendação: (a) nova ADR-0013 formalizando a distinção entre "bypass manual P0/P1" e "bypass técnico autorizado sob whitelist de paths"; (b) whitelist de paths selada; (c) contador separado para cada categoria; (d) retroativo: bypasses 2-4 ficariam reclassificados como "técnicos" se a ADR-0013 tivesse existido, mas a reclassificação retroativa NÃO deve ser feita — fica como registro histórico de 5 slots consumidos sob a regra vigente na época.

### Q6. Critérios objetivos para fim do estado pausado

Retomada autorizada **se e somente se todos os critérios abaixo forem satisfeitos**, nesta ordem:

1. **Bloco 5 item 5.3 implementado** — ruleset da `main` com `current_user_can_bypass` removido; GitHub App `kalibrium-auto-reviewer` ativo; CI obrigatório. Evidência: `gh api /repos/.../rulesets/14936750` mostrando sem bypass actors; PR dummy de teste passando sem admin.
2. **Hook de enforcement de pausa** — `pre-commit` e/ou `pre-push` que leiam `project-state.json.paused` (ou arquivo selado equivalente) e bloqueiem mutação em `main` exceto whitelist (`docs/audits/external/**`, `docs/incidents/bypass-*.md`, `docs/decisions/**`). Selado via `hooks-lock.sh`.
3. **Gaps 1-4 corrigidos** — CLAUDE.md Fase E menciona master-auditor; orchestrator.md invoca master-auditor no fluxo; `mcp__codex__codex` (nome correto) em master-auditor.md; ADR duplicado resolvido.
4. **Dry-run master-auditor** — executar master-auditor contra um artefato real (ex: plan.md do slice-010 retroativo) e anexar `master-audit.json` com ambas as trilhas (Claude e GPT-5) respondendo. Prova de que dual-LLM **funciona** antes de virar gate de produção.
5. **ADR-0013 (ou emenda a 0012)** formalizando categoria "bypass técnico autorizado" com whitelist de paths e contador separado. PM assina decisão em `docs/decisions/`.
6. **Dual-trail desta auditoria consolidada** — trilha GPT-5 emitida, divergências reconciliadas ou escaladas conforme §Passo 5 de master-auditor. PM assina `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md`.
7. **Retrospectiva de emergência** (não a de épico automatizada — uma humana) documentando por que 4 dos 5 bypasses vieram do mesmo 5.3 não-resolvido e por que foi permitido.

Sem **todos** os 7 satisfeitos, retomada é prematura.

---

## Análise dos 5 gaps (§5 do dossiê)

| Gap # | Classificação | Justificativa | Prioridade |
|---|---|---|---|
| 1 | Corrigível no escopo atual | Edição textual de CLAUDE.md §6 Fase E para incluir master-auditor e fluxo dual-LLM. Não afeta governança, apenas documenta o que ADR-0012 já decidiu. | Alta (pré-retomada) |
| 2 | Corrigível no escopo atual | orchestrator.md deve incluir master-auditor nos gates Fase E. Sem isso, ADR-0012 é letra morta mesmo com tools corretas. | Alta (pré-retomada) |
| 3 | Corrigível no escopo atual — mas exige dry-run | Fix de 1 linha (`mcp__codex_cli__invoke` → `mcp__codex__codex`). Crítico: revela que master-auditor nunca foi executado antes do merge. Exige dry-run documentado além do fix. | Crítica (pré-retomada) |
| 4 | Corrigível no escopo atual | Renumerar ADR. Preferir renumerar o NOVO (0012→0013) porque o antigo `0012-additional-mcps-postgres-figma` já estava estabelecido e o merge introduziu a colisão. Ou reservar 0013 para a ADR de política de bypass técnico (Q5) e mover o "autonomy" para 0014. PM decide. | Média |
| 5 | Requer novo ADR (Q5) + hook mecânico | Política de pausa puramente declarativa não é política — é folclore. Exige ADR formalizando categoria + hook mecânico selado. | Crítica (pré-retomada) |

---

## Verdict final e condições

**BLOCKED**

O projeto não pode retomar execução de slices novos até que os 7 critérios de Q6 sejam satisfeitos **nesta ordem**:

1. Bloco 5 item 5.3 (ruleset endurecido + auto-reviewer).
2. Hook de enforcement de pausa selado.
3. Gaps 1-4 corrigidos.
4. Dry-run master-auditor anexado.
5. ADR-0013 de política de bypass técnico.
6. Trilha GPT-5 desta auditoria + consolidação.
7. Retrospectiva humana dos 5 bypasses.

Ordem 1→2 é obrigatória porque (1) quebra o catch-22 que justificou bypass 6, e sem (1) o hook (2) pode ser contornado pelo próprio owner. Ordem 3→4 é obrigatória porque corrigir código sem provar que roda é o mesmo erro que mergeou ADR-0012 sem dry-run.

**Operações permitidas durante BLOCKED:**
- Correção dos gaps 1-4 (exceto edição de arquivos selados, que exige relock via terminal externo).
- Implementação do Bloco 5 item 5.3.
- Escrita do novo ADR-0013.
- Dry-run do master-auditor com artefato existente.
- Esta trilha de auditoria (Opus) + consolidação com GPT-5.

**Operações proibidas durante BLOCKED:**
- Novo slice (incluindo E02-S08 se existir).
- Novo merge em `main` fora da whitelist acima.
- Nova decisão de produto.
- Qualquer auto-aplicação do harness-learner.
- Invocação do epic-retrospective (E02 não está "fechado" — está travado).

---

## Riscos residuais não cobertos pelo dossiê

1. **Harness-learner pode ser arma contra o próprio harness.** ADR-0012 E4 limita auto-aplicação a "adicionar/endurecer, não afrouxar". Mas o critério "afrouxar" é subjetivo — um hook que "adiciona" um allowlist pode na prática afrouxar. Sem ADR explícito sobre o que conta como afrouxamento, o guardrail é ambíguo. Recomenda-se lista enumerativa de ações bloqueadas, não princípio textual.

2. **Master-auditor consolida pareceres que ele próprio gerou.** A trilha Claude é "Claude Opus 4.6 em sessão isolada", e o master-auditor é "model: opus". Pode haver colisão de identidade — a trilha Claude é executada **pelo próprio master-auditor** ou é sub-agent separado? A `.claude/agents/master-auditor.md` sugere o primeiro, o que degrada a independência das trilhas (só GPT-5 seria realmente externo).

3. **Custo e latência de master-auditor não dimensionados.** Budget 80k tokens por invocação, sem limite diário. Para um slice com 5 gates + plan-review + spec-audit, isso é 7 × 80k = 560k tokens só de master-auditor por slice. Sem ADR de orçamento, risco de explosão de custo quando o fluxo rodar em produção.

4. **Baseline de identidade git selado + `KALIB_RELOCK_AUTHORIZED` por TTY** não se sustenta para PM não-técnico. ADR-0012 E1 diz "PM opera via relock.bat" — mas o `.bat` precisa invocar o relock, que exige TTY e digitar "RELOCK". Se o `.bat` automatiza isso, a camada 3 de defesa do relock é neutralizada. Não vi o `.bat` no dossiê.

5. **P0 inflation.** Bypass 5 estabeleceu precedente de que "inviabilidade operacional" é P0. Qualquer dificuldade futura pode invocar a mesma cláusula. Recomenda-se ADR definindo critérios positivos de P0 (lista fechada) em vez de critério aberto.

6. **Ausência de rollback plan para ADR-0012.** Se o dual-LLM não convergir ou custar demais em produção, como voltar ao modelo anterior? A emenda substituiu R11; não há cláusula de sunset ou reversão. ADR-0012 deveria ter critérios de abandono.

7. **Sessão atual é a trilha única de Opus.** O dossiê menciona "auditoria tripla obrigatória" (§8.4) mas nada garante que GPT-5 efetivamente rode. Se esta auditoria for aprovada sozinha, o próprio mecanismo de R11-legacy (que o dossiê invoca para governar mudanças §1-§4 da constitution) é violado — retomar projeto é uma mudança dessa magnitude.
