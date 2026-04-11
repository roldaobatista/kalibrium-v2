# Guide Backlog

Backlog de melhorias ao próprio harness (constitution, hooks, sub-agents, skills). Cada item deve referenciar a evidência que motivou (slice, retrospectiva, incidente, audit).

Itens resolvidos movem para o histórico no final.

---

## Aberto

### [B-001] Operacionalizar post-edit-gate pós ADR-0001

- **Origem:** `post-edit-gate.sh` atual é stack-agnóstico e faz format/lint/testes apenas se as ferramentas existem.
- **Ação:** após ADR-0001 (stack escolhida), atualizar `post-edit-gate.sh` com comandos concretos e convenção de mapeamento arquivo→teste validada.
- **Status:** **bloqueado por ADR-0001** (stack ainda não decidida). Item ativo no Dia 1.
- **Bloqueia:** slice 1.

### [B-007] Integração com CI externo

- **Origem:** P8 (suite full em CI).
- **Ação:** quando stack estiver definida, configurar GitHub Actions (ou equivalente) rodando lint + types + suite full + security scan.
- **Status:** **bloqueado por ADR-0001**. Item ativo no Dia 1.

### [B-009] GitHub Action para auto-aprovar PR quando verifier + reviewer passam

- **Origem:** Fase 2 (R11). Hoje o merge de PR requer admin bypass do owner, o que é aceitável mas não ideal.
- **Ação:** criar `.github/workflows/auto-approve.yml` que:
  1. Roda quando um PR é aberto em `main`
  2. Lê `specs/NNN/verification.json` e `specs/NNN/review.json` da branch do PR
  3. Valida ambos contra os schemas (R4 e R11)
  4. Se ambos têm `verdict: approved` → adiciona approval automática via bot e mergeia
  5. Se algum falha → deixa PR aberto com comentário explicando
- **Pré-requisito:** GitHub App ou PAT com permissão de approve. Preferir App.
- **Status:** aberto. Pode ser feito após o primeiro slice real.

### [B-010] Tradução automática técnica → linguagem de produto

- **Origem:** R12. `explain-slice.sh` hoje cria apenas template com placeholders que o agente principal preenche manualmente.
- **Ação:** criar helper que lê `verification.json` + `review.json` + `spec.md` e **automaticamente** traduz findings técnicos para linguagem de produto usando um prompt estruturado de tradução (pode ser um mini sub-agent `translator-pm`).
- **Status:** aberto. Depende de ter o primeiro slice real para calibrar o tradutor.

### [B-011] Drift semântico entre skill e script: `guide-check`

- **Origem:** validação Sessão 3 da meta-audit #2 (2026-04-11).
- **Evidência:** `.claude/skills/guide-check.md` descreve *"Spawn do sub-agent guide-auditor"* mas `scripts/guide-check.sh` (linha 2 comentário explícito) roda em **modo standalone**, sem spawnar sub-agent. A documentação mente sobre o comportamento real.
- **Ação:** decidir uma de duas direções:
  - **(a)** alinhar doc com impl — reescrever `guide-check.md` pra refletir modo standalone, e justificar por que não vira sub-agent
  - **(b)** alinhar impl com doc — refatorar `guide-check.sh` pra virar um invocador que dispara `guide-auditor` como Agent tool
- **Recomendação:** (a) é mais barato e a implementação standalone funciona. Mas (b) é mais fiel ao modelo de sub-agents com budget declarado (R8). Decisão precisa ADR ou discussão explícita.
- **Risco se não resolvido:** gap de confiança — qualquer futura audit vai ler a skill e assumir comportamento que não existe. É exatamente o mesmo modo de falha que o check #10 que eu adicionei (skill referenciada mas inexistente) tenta prevenir, só que na outra direção.

### [B-012] CHECK-4 (bypass history) false-positive recorrente

- **Origem:** validação Sessão 3 da meta-audit #2 (2026-04-11) + memory `feedback_audit_regex_strictness.md` (pattern já reconhecido).
- **Evidência:** Sessão 3 rodou `bash scripts/guide-check.sh` e CHECK-4 reportou FAIL listando commits **legítimos** do próprio harness: `relock pos-meta-audit`, `registra admin bypass`, `contador bypass 3/5 -> 4/5`. A regex casa qualquer menção a "bypass" sem considerar contexto.
- **Ação:** refinar CHECK-4 em `scripts/guide-check.sh` (ou `.claude/agents/guide-auditor.md`) para excluir commits que **apenas** tocam paths do harness:
  - `.claude/**`
  - `scripts/hooks/**`
  - `docs/incidents/**`
  - `docs/audits/**`
  - Autor em allowlist git-identity
- **Proposta de regex refinada:** `grep -E "--no-verify|SKIP=|HUSKY=0|hook\s+(removido|desabilitado|renomeado)" --fixed-strings` focando em verbos de bypass efetivo, não na palavra "bypass" isolada.
- **Status:** aberto. Prioridade média — enquanto não resolvido, toda execução de `/guide-check` gera ruído que esconde findings reais.

### [B-013] Cadeia E2E verifier → reviewer → merge-slice NUNCA exercida

- **Origem:** validação Sessão 3 da meta-audit #2 (2026-04-11).
- **Evidência:** `specs/` está vazio, `.claude/telemetry/` só tem `meta.jsonl` (22 linhas de eventos do harness, zero eventos de slice real). Os componentes estão selados + smoke-testados unitariamente, mas nenhum slice real produziu `verification.json` + `review.json` + executou o merge-slice completo.
- **Ação:** criar `slice-000-smoke` (descartável, fora do numbering de produção) antes do primeiro slice real do Kalibrium. Objetivo: exercitar toda a cadeia ponta-a-ponta sem lógica de domínio:
  1. `/new-slice 000 "smoke ponta a ponta do harness"`
  2. spec.md trivial (ex.: "endpoint GET /health retorna 200")
  3. Executar: architect → ac-to-test → implementer → `/verify-slice` → `/review-pr` → `/merge-slice`
  4. Produzir `verification.json` e `review.json` reais, rodar merge-slice com push real, gerar `slice-report` e `retrospective`
  5. Quando OK: deletar branch + registrar aprendizados em retrospective + marcar este item resolvido
- **Pré-requisito:** stack decidida (ADR-0001 ✅ já aceito 2026-04-11) + `post-edit-gate.sh` operacionalizado ([B-001]).
- **Status:** **mandatório antes do primeiro slice de produção**. Bloqueia slice 1.

### [B-014] Sessão de validação ("Sessão 3") não é read-only na prática

- **Origem:** Sessão 3 rodada em 2026-04-11 para validar a meta-audit #2.
- **Evidência:** o agente executor da Sessão 3 era esperado como READ-ONLY (rodar `/guide-check` + smoke-tests + ler agents/skills), mas **modificou o working tree**:
  - Deletou `docs/adr/0001-stack-choice.md` no working tree (HEAD intacto — `d4b23b8`)
  - "Editou" 18 scripts em `scripts/` (strip trailing newline — cosmético, mas mutação não autorizada)
  - PM confirmou que **não abriu a pasta nem mexeu manualmente** — a modificação veio exclusivamente do agente
- **Modo de falha:** o prompt da Sessão 3 dizia "agente fresh, sem contexto, valida ponta-a-ponta", mas não restringia permissões. O agente aplicou Edit/Write/Delete como parte do processo de "validação" (provavelmente auto-formatação ao salvar arquivos lidos, ou reestruturação acidental de ADR-0001).
- **Ação:** próxima Sessão 3 (ou qualquer agente de validação independente no futuro) deve rodar com **uma de duas proteções**:
  - **(a) Worktree isolada descartável** — `git worktree add /tmp/kalibrium-smoke main`, agente roda lá, nada do working tree principal é afetado
  - **(b) Permissões restritas** — `settings.json` com `permissions.deny` incluindo `Edit`, `Write`, `Bash(git restore*)`, `Bash(rm*)` durante a sessão de validação
- **Recomendação:** (a) é mais simples e mais aderente ao modelo R3 (verifier isolado em worktree) do próprio harness. Vira template pra Sessão 3 futura.
- **Status:** aberto. Não bloqueia, mas toda Sessão 3 a partir de agora deve seguir essa regra.

### [B-015] Nota descarte — "snapshot post-relock não criado" (Sessão 3 finding #7)

- **Origem:** Sessão 3 da meta-audit #2 reportou: *"CHECK-2: settings.json divergiu de settings-2026-04-10.json — warning normal pós-relock, mas indica que `.claude/snapshots/settings-2026-04-11.json` não foi criado pelo fluxo atual"*.
- **Verificação:** FALSO POSITIVO. O arquivo `.claude/snapshots/settings-2026-04-11.json` **existe** (criado 2026-04-11 13:07, pós-relock `f01fb46` que ocorreu 12:46). A Sessão 3 olhou estado defasado ou não refresh-ou o filesystem.
- **Aprendizado:** validações de drift documentais devem sempre refazer `ls -la` do diretório relevante **imediatamente antes** de reportar o finding. Não cachear estado filesystem ao longo da sessão.
- **Status:** descartado, registrado para histórico.

### [B-016] Tradução contínua técnico → produto em TODOS os sub-agents

- **Origem:** análise 2026-04-11 da classe Hercules/Lovable/Bolt como UX pattern. PM hoje fica cego entre `/new-slice` e `/merge-slice` — só vê saída traduzida quando roda `/explain-slice` (escalação R6 ou a pedido). Entre os dois extremos, o harness opera em silêncio técnico.
- **Ação:** cada sub-agent passa a emitir **2 outputs**:
  1. Output técnico (para o próximo sub-agent consumir): `plan.md`, test files, `diff.patch`, `verification.json`, `review.json`
  2. Output em linguagem de produto (para o PM ver na hora): `plan-pm.md`, `tests-pm.md`, `diff-pm.md`, `verification-pm.md`, `review-pm.md`
- **Formato dos outputs-pm:** vocabulário permitido do R12 (definido em `/explain-slice`), seções fixas, sempre terminando com "próximo passo único: [ ] Sim [ ] Trocar X".
- **Implementação mínima:**
  - Mini sub-agent compartilhado `translator-pm` (budget ~10k) invocado no handoff de cada sub-agent
  - Atualizar `.claude/agents/*.md` com a regra "emit both outputs" no Handoff
  - `/explain-slice` vira ponto de **consolidação** (junta os \*-pm em um relatório único), não o único tradutor
- **Status:** **alta prioridade**. Antes do `slice-000-smoke` (B-013) — PM precisa conseguir acompanhar sem ler código.

### [B-017] Biblioteca de slice-kits — templates de spec+plan+ACs para padrões comuns

- **Origem:** análise Hercules/Lovable ("auth/DB/payments out of the box"). Cada slice nosso começa do zero: PM escreve spec livre, architect gera plan único, ac-to-test escreve testes únicos. Slices 1-5 do Kalibrium vão ser CRUD/auth/PDF variantes — reinventar roda em cada um.
- **Ação:** criar `.claude/slice-kits/` com templates para padrões recorrentes:
  - `crud-entidade.md` — CRUD com escopo por tenant (cliente, equipamento, calibração)
  - `integracao-pagamento.md` — Stripe/pagar.me com webhooks
  - `auth-multi-tenant.md` — login + escopo por tenant + RBAC básico
  - `relatorio-pdf.md` — gerador de PDF a partir de template (caso motivador: certificado de calibração)
  - `endpoint-rest-autenticado.md` — API REST com middleware auth + tenant scope
- **Integração com `/new-slice`:** flag opcional `--kit=crud-entidade`. `draft-spec.sh` pré-preenche ACs baseados no kit, PM só ajusta campos específicos de domínio.
- **Formato de kit:** frontmatter com `stack_required` (Laravel/Livewire/PostgreSQL do ADR-0001), `placeholders` (lista de campos que PM preenche), `acs_template` (ACs em dado-quando-então parametrizados), `hint_files` (arquivos típicos que serão tocados pelo implementer).
- **Status:** **alta prioridade**. Antes do primeiro slice de produção. Fecha a distância entre "PM escreve AC do zero" e "PM marca [x] no que muda".

### [B-018] Sub-agent `designer` + skill `/preview NNN` (mockup visual antes do código)

- **Origem:** análise Hercules/Lovable ("stunning designs"). PM descobre que a tela não era como imaginava só no final do slice. Nosso único output visual antes do código é `plan.md` (texto).
- **Ação:** criar sub-agent `designer` (budget ~20k tokens) que roda **entre** `architect` e `ac-to-test`:
  1. Lê `specs/NNN/plan.md` + `docs/glossary-domain.md`
  2. Gera `specs/NNN/preview/` com uma das duas opções:
     - **(a) HTML estático** (zero JS, só layout + textos + botões mockados, usa componentes Livewire como referência visual)
     - **(b) Descrição estruturada** (`preview/tela-01-cadastro.md` com wireframe ASCII + campos + estados) — mais barato em tokens, não exige preview deploy
- **Skill:** `/preview NNN` — abre `preview/index.html` no navegador do PM (ou exibe os .md formatados)
- **Gate:** PM marca "ok, pode implementar" antes do `ac-to-test` rodar. Se PM pedir mudança, volta pro `architect` com feedback específico.
- **Custo:** +1 sub-agent (~20k tokens/slice) + 1 gate PM (feature, não bug — força aprovação visual antes do investimento de implementação).
- **Benefício:** pega ~80% dos erros "não era bem assim que eu imaginava" antes do slice gastar budget do implementer/verifier/reviewer.
- **Status:** média prioridade. Depois do `slice-000-smoke` (B-013) confirmar que a cadeia base funciona, antes do slice 2 de produção.

### [B-019] Publish-to-staging automático em cada `/merge-slice` aprovado

- **Origem:** análise Hercules/Lovable ("1-click publish"). Nosso `/merge-slice` termina no merge+push mas PM não recebe URL clicável pra testar. A promessa R12 de "próximo passo único e claro" fica incompleta sem um link.
- **Ação:** ao fim de `merge-slice.sh` (pós push aprovado), dispara passo `publish-staging`:
  1. Deploy da branch na infra de homologação (depende de ADR-0005)
  2. Gera URL previsível: `https://slice-NNN.kalibrium-staging.<provider>.app`
  3. Aguarda healthcheck (máx 60s)
  4. Retorna no output do `/explain-slice` como **primeira linha**: *"✅ Pronto pra testar aqui: https://..."*
- **Candidatos a provider** (decisão em ADR-0005): Railway, Fly.io, Hostinger staging sub-domain, ou Cloudflare Workers+Pages se Laravel Octane permitir.
- **Pré-requisito:** **ADR-0005 aceito** (ambiente de homologação).
- **Status:** média prioridade. Bloqueado por ADR-0005.

### [B-020] Wrapper 1-click pro relock (.bat desktop + bash wrapper)

- **Origem:** atrito medido em 2026-04-11 durante meta-audit #2 — PM precisou abrir PowerShell, trocar pra Git Bash, copy-paste comando, digitar `RELOCK`, fazer commit separado. 5+ interações pra 1 ação.
- **Ação:**
  - `scripts/relock-and-commit.sh` — wrapper bash que roda `relock-harness.sh` + `git add -A` + `git commit` automático (mantém a digitação de `RELOCK` — camada 3 de confirmação preservada)
  - `tools/relock.bat` — atalho Windows que PM pode deixar no desktop; duplo-click abre console, roda o wrapper, aguarda `RELOCK`, fecha ao final
  - Opcional: atalho `.lnk` na barra de tarefas apontando pro `.bat`
- **O que NÃO muda:** camada 2 (TTY check) e camada 3 (digitar `RELOCK`) permanecem intactas — a defesa mecânica contra o agente continua enforced.
- **Status:** baixa prioridade. Ativar quando a próxima alteração de hook/settings aparecer — se o atrito incomodar de novo, priorizar.

### [B-021] Implementer paralelo — dividir UI e API em sub-agents concorrentes

- **Origem:** análise Hercules — AI builders entregam rápido porque fazem backend e frontend em paralelo. Nosso `implementer` é single-threaded.
- **Ação:** quando `plan.md` marcar `parallelizable: true`, o implementer vira **dois sub-agents em worktrees separadas**:
  - `implementer-api` — escopo: `app/Http/`, `app/Models/`, `database/migrations/`, `tests/Feature/`
  - `implementer-ui` — escopo: `resources/views/`, `app/Livewire/`, `resources/css/`, `tests/Browser/`
  - Main agent espera os 2 terminarem, merge de contexto, dispara `/verify-slice` único
- **Scope enforcement:** `edit-scope-check.sh` precisa de lógica nova — hoje usa env var global, viraria regra por agent. Cada implementer só pode tocar arquivos do próprio escopo.
- **Risco:** race condition em arquivos compartilhados (config, routes.php, composer.json). Decisão: se um arquivo aparece no diff dos dois, **falha o slice** e volta pro architect re-planejar.
- **Benefício estimado:** tempo wall-clock do slice cai ~40% em slices CRUD (que têm muita UI + muita API).
- **Status:** baixa prioridade. Otimização tardia — só depois de 3-4 slices de produção rodando single-thread, pra ter baseline real de tempo.

---

## Resolvido

### [B-003] Smoke-test dos hooks no Windows — RESOLVIDO 2026-04-10

- **Origem:** R5 do `GUIA-KALIBRIUM-V2-HARNESS-SETUP.md`.
- **Resolução:** `scripts/smoke-test-hooks.sh` criado com 29 testes cobrindo os 12 hooks. Rodado no Windows 11 + Git Bash → `29/29 OK`.
- **Bugs corrigidos no processo:**
  - `read-secrets-block.sh` — ordem de cases invertida bloqueava `.env.example`.
  - `collect-telemetry.sh` — `git log -1` saía com 128 em repo sem commits.
- **Evidência:** output `[smoke-test OK] todos os hooks funcionam neste ambiente`.

### [B-002] Scripts auxiliares dos skills — RESOLVIDO 2026-04-10

- **Origem:** skills referenciavam `scripts/new-slice.sh`, `scripts/verify-slice.sh`, `scripts/adr-new.sh`, `scripts/slice-report.sh`, `scripts/retrospective.sh`, `scripts/guide-check.sh`.
- **Resolução:**
  - `new-slice.sh`, `adr-new.sh`, `guide-check.sh` já estavam funcionais.
  - `verify-slice.sh` implementado: valida pré-condições, monta `verification-input/`, parseia ACs, modo `--validate` lê `verification.json`, aplica schema (B-005) e R6 (escalação após 2 rejeições consecutivas).
  - `slice-report.sh` implementado: agrega eventos do JSONL (commits, gates, rejeições, tokens), gera markdown com métricas.
  - `retrospective.sh` polido: carrega números do slice-report e gera template com seções fixas.
- **Evidência:** script `verify-slice.sh --validate` aplica schema e atualiza telemetria; smoke test estendido cobre `validate-verification.sh`.

### [B-004] Política de MCPs — RESOLVIDO 2026-04-10

- **Origem:** `/mcp-check` existe, `.claude/allowed-mcps.txt` tem lista inicial mas sem justificativa formal.
- **Resolução:** `docs/adr/0002-mcp-policy.md` criado explicando critérios de autorização, MCPs aprovados inicialmente e processo de adição.

### [B-005] Schema do verification.json + validador — RESOLVIDO 2026-04-10

- **Origem:** R4 + skill `/verify-slice`.
- **Resolução:**
  - `docs/schemas/verification.schema.json` escrito em JSON Schema draft-07 com enums para `verdict`, `rule`, `next_action`.
  - `scripts/validate-verification.sh` em bash puro (zero dependência externa) valida estrutura, enums e coerência entre `verdict` e `next_action`.
  - Integrado com `verify-slice.sh --validate`.

### [B-006] Telemetria de tokens por sub-agent — RESOLVIDO 2026-04-10 (estrutura)

- **Origem:** R8.
- **Resolução:**
  - `scripts/record-tokens.sh AGENT SLICE TOKENS` — API simples para gravar eventos de token em `.claude/telemetry/<slice>.jsonl`.
  - Invocável manualmente ao fim de uma invocação de sub-agent, ou via hook custom se o harness futuro expor tokens.
  - `slice-report.sh` agrega por sub-agent e compara com `max_tokens_per_invocation` do frontmatter dos agents.
- **Observação:** parsing automático dos tokens diretamente do Claude Code depende da API do harness, que pode evoluir. Reabrir quando houver fonte confiável.

### [B-008] Glossário de domínio — RESOLVIDO 2026-04-10

- **Origem:** agentes precisam entender OS, GUM, ICP-Brasil, REP-P antes de escrever código de domínio.
- **Resolução:** `docs/glossary-domain.md` destilado do `ideia.md` como referência **canônica** do V2 (não confundir com `docs/reference/` que é read-only histórico). Agentes DEVEM consultar ao tocar código de domínio com terminologia técnica.

---

## Histórico de versões deste backlog

- 2026-04-10 — inicial (B-001..B-008)
- 2026-04-10 — B-003 resolvido pós smoke-test
- 2026-04-10 — B-002, B-004, B-005, B-006, B-008 resolvidos; B-001 e B-007 marcados como bloqueados por ADR-0001
- 2026-04-11 — B-009 e B-010 adicionados pós meta-audit #2
- 2026-04-11 — B-011, B-012, B-013, B-014, B-015 adicionados pós validação Sessão 3 da meta-audit #2
- 2026-04-11 — B-016..B-021 adicionados pós análise de classe Hercules/Lovable/Bolt (UX pattern import; import do que funciona, descarte do que mataria os gates)
