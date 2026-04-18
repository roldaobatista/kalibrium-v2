# Guide Backlog

Backlog de melhorias ao próprio harness (constitution, hooks, sub-agents, skills). Cada item deve referenciar a evidência que motivou (slice, retrospectiva, incidente, audit).

Itens resolvidos movem para o histórico no final.

---

## Aberto

### [B-046] Mutation testing (Infection PHP + Stryker JS)

- **Origem:** análise de fragilidade do harness 2026-04-18 (ponto #5). Teste pode existir e ser fraco (passar sem asserção útil). `audit-tests-draft` (ADR-0017) detecta rastreabilidade AC→teste mas não qualidade semântica das asserções.
- **Risco concreto:** um teste `expect(true).toBe(true)` passa pela rastreabilidade `@covers AC-NNN` mas não protege nada. Mutação do código fonte deveria quebrar o teste; se não quebrar, o teste é decorativo.
- **Ação:** ADR sobre budget de CI (mutation testing é caro — tipicamente 10-60min). Integrar Infection (PHP) + Stryker (JS/TS). Rodar em PR apenas em arquivos tocados, não baseline completo. Threshold mínimo de mutation score (60-70% como começo) como gate não-bloqueante (S4) escalando para bloqueante (S2) após 3 slices.
- **Status:** aberto. Prioridade **baixa** (nice-to-have). Alvo: slice-020.

### [B-045] Visual regression testing (Playwright screenshots)

- **Origem:** análise de fragilidade do harness 2026-04-18 (ponto #4). Playwright instalado mas zero uso de `toMatchSnapshot`/`toHaveScreenshot`. Mudança de CSS pode quebrar layout sem teste falhar.
- **Risco concreto:** qualquer refactor de Ionic/React component pode deslocar layout, mudar tipografia, quebrar responsividade — e todos os testes lógicos continuam verdes.
- **Ação:** habilitar `toHaveScreenshot` em Playwright. Storage de baselines em `tests/visual/__screenshots__/` com convenção por browser+viewport. ADR sobre: (a) update workflow (`--update-snapshots` bloqueado em main, permitido só em PR dedicado); (b) política de review de diffs visuais (ou auto-approve por similaridade ≥ 99.5%); (c) storage (git LFS ou artefato separado).
- **Status:** aberto. Prioridade **média** (vai doer quando frontend complexar). Alvo: slice-020.

### [B-044] CI gate "AC sem teste reprova" sobre histórico

- **Origem:** análise de fragilidade do harness 2026-04-18 (ponto #3). `audit-tests-draft` (ADR-0017) valida rastreabilidade AC→teste **antes da implementação**. Não há equivalente que rode no CI sobre o histórico do repo.
- **Risco concreto:** se alguém (agente ou humano) mergear commit de código de produção sem teste correspondente, nada pega. Rastreabilidade ADR-0017 vira documento de boas intenções, não gate executável.
- **Ação:** script `scripts/check-ac-coverage.sh` que: (a) lê specs/*/spec.md, extrai todos os `AC-NNN` declarados e mapeia para `story_id`; (b) grep `@covers AC-NNN` em `tests/**`; (c) reprova se qualquer AC declarado em spec mergeada não tem teste encontrado. Integrar em novo job de `test-regression.yml` ou novo workflow. Edge case: ACs abandonados/edge com `@skip` explícito no spec.
- **Status:** aberto. Prioridade **média**. Alvo: slice-020.

### [B-043] Paths filter do tenant-isolation dinâmico + limpo

- **Origem:** análise de fragilidade do harness 2026-04-18 (ponto #7). `.github/workflows/ci.yml` linhas 456-460 listam paths estáticos do filter do job `tenant-isolation`: `app/Models/**`, `app/Http/**`, `app/Livewire/**`, `app/Jobs/**`, `tests/slice-011/**`.
- **Duplo problema:**
  1. **Defasado:** `app/Livewire/**` foi demolido no slice 016 (ADR-0015, frontend Livewire removido). Filter lista área morta.
  2. **Estático:** se surgir `app/Services/Tenant/`, `app/Domain/Tenant/`, ou qualquer camada nova sensível ao isolamento, ninguém é lembrado de atualizar o filter. Área escapa do gate silenciosamente.
- **Ação:**
  - **Fix imediato (slice-019):** remover `app/Livewire/**`; adicionar `app/Services/**`, `app/Domain/**`, `database/migrations/**` (qualquer migration que adiciona coluna sem `tenant_id` é risco).
  - **Política (slice-019):** adicionar seção em `docs/documentation-requirements.md` ou em `docs/protocol/07-politica-excecoes.md`: toda nova camada de código de produção deve declarar no commit ou ADR se é sensível a tenant isolation e atualizar o filter.
  - **Enforcement mecânico (slice-019):** script `scripts/check-tenant-filter-coverage.sh` que compara `ls app/` vs. lista do filter e warna (não bloqueia inicialmente) quando há diretório fora do filter que parece sensível (heurística: contém `.php` + referencias a `tenant` ou Model). Pode virar bloqueante em slice futuro.
- **Status:** aberto. Prioridade **alta**. Alvo: slice-019 (fix barato, risco latente alto).

### [B-042] Hook git nativo pre-push + pre-commit + orquestrador

- **Origem:** análise de fragilidade do harness 2026-04-18 (ponto #6). `scripts/hooks/pre-push-gate.sh` é `PreToolUse Bash(git push*)` hook do Claude Code — só dispara quando o **agente** roda `git push`. Push fora do Claude Code (PM via terminal, workflow dispatch do GitHub, outro agente) escapa toda a rede.
- **Evidência:** `ls .git/hooks/pre-push` → "No such file or directory". Nenhum hook git nativo instalado.
- **Risco concreto:** atualmente funciona porque PM não pusha direto. Mas:
  - Qualquer `.bat` que o PM rode pode chamar `git push` fora do agente.
  - GitHub Actions workflow_dispatch pode fazer push fora do agente.
  - Outro Claude session sem esse harness específico escapa.
- **Ação:**
  - Script `scripts/install-git-hooks.sh` idempotente que instala `.git/hooks/pre-push` + `.git/hooks/pre-commit` apontando para os scripts de `scripts/hooks/` (PreToolUse + versão native bridge).
  - Invocação automática em `scripts/hooks/session-start.sh` (se hook não está instalado, instalar silenciosamente — não bloqueia sessão).
  - AC: deletar manualmente `.git/hooks/pre-push` e abrir nova sessão → hook é reinstalado.
  - AC: push fora do Claude Code (simulado: `cmd /c git push`) bloqueia se tentar pushar em main sem PR.
- **Status:** aberto. Prioridade **alta**. Alvo: slice-019 (fix barato, risco latente alto).

### [B-041] Contrato de paths explícito para sub-agents

- **Origem:** retrospectiva do slice-017 (2026-04-18). qa-expert falhou em 2 tentativas por tentar path `frontend/` que não existe neste repo.
- **Ação:** todos os agent files (`.claude/agents/*.md`) devem incluir seção "Paths do repositório" com guardrail explícito: "raiz do repo contém `src/`, `tests/`, `specs/`, etc. NÃO existe subpasta `frontend/`." Também adicionar Glob de descoberta obrigatório antes de Read quando em dúvida.
- **Status:** aberto. Prioridade **baixa-média**. Pode ir no slice-018 junto de B-036/B-037.

### [B-040] Limite estrutural para S4 ambientais com mesma justificativa

- **Origem:** retrospectiva do slice-017 + master-audit Trilha A (MA-A-017-001). 3 S4 ambientais com mesma raiz (Chromium headless) registrados no mesmo slice.
- **Ação:** política no harness-learner: "no máximo 2 findings S4 por slice com mesma categoria `environmental` e mesma justificativa-raiz". Ao atingir 3, forçar spike/ADR para endereçar a limitação estrutural em vez de aceitar como dívida recorrente.
- **Status:** aberto. Prioridade **média**. Pode ser slice-019 ou depois.

### [B-039] Telemetria de slice gravada automaticamente por hook

- **Origem:** retrospectiva do slice-017. `.claude/telemetry/slice-017.jsonl` ficou vazio — `/slice-report 017` gerou relatório sem dados.
- **Ação:** hook `scripts/hooks/slice-telemetry.sh` dispara em pre-commit quando commit message matcha `(slice-NNN|E??-S??)` e grava tokens + agentes + duração em jsonl append-only. Usar evento de `Agent` tool result como fonte.
- **Status:** aberto. Prioridade **média**.

### [B-038] Writers uniformes de gate output (schema gate-output-v1)

- **Origem:** retrospectiva do slice-017. Durante merge-slice.sh, 3 JSONs foram rejeitados por divergências: `$schema` como URL ao invés de literal "gate-output-v1", `slice` ausente, `gate` com valor errado (`security` vs `security-gate`, `functional` vs `functional-gate`).
- **Ação:** atualizar prompts padrão de cada modo de gate nos agent files (`qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`) para explicitar os valores LITERAIS exigidos por `docs/protocol/schemas/gate-output.schema.json` + `scripts/merge-slice.sh` linhas 65-78. Adicional: criar `scripts/validate-gate-output.sh` que cada agente pode rodar antes de reportar concluído.
- **Status:** aberto. Prioridade **média**. Bem fácil, pode entrar no slice-018.

### [B-037] Auditoria e re-auditoria sem bias — perímetro livre na 1ª vez, zero histórico na 2ª

- **Origem:** sessão slice-017 (2026-04-17). PM identificou que o princípio de auditoria isolada (R3/R11) está previsto no harness mas é **violado na prática** por prompts que induzem o auditor com contexto que ele não deveria ter.
- **Evidência concreta (nesta mesma sessão):**
  - No retry do `verify-slice 017` após sub-agent truncar, o prompt de recuperação incluía literalmente "88/88 scaffold + 4/4 dev e2e + 13/16 preview e2e verdes. As 3 falhas são S4 ambientais". O auditor só carimbou — não auditou.
  - Em uma tentativa seguinte, mandei o JSON pronto com os 3 findings S4 já descritos. Isso não é auditoria, é ditar o resultado.
  - Fixers recebiam "a causa é X, corrige via Z" em vez de "investigue a causa". Se o diagnóstico estivesse errado, o fixer não investigaria por conta própria.
- **Princípio a formalizar:**
  - **Auditoria 1ª vez:** auditor recebe o perímetro funcional completo (story/slice) sem restringir arquivos específicos. Ele decide onde olhar. Forma opinião livre.
  - **Re-auditoria:** auditor recebe **só o arquivo(s) do estado atual + checklist + agent file**. Zero menção a findings anteriores, verdict anterior, "verifique se X foi corrigido", IDs de findings prévios, commits de fix, diff do fix, nomes de arquivos tocados. **O auditor não deve nem saber que é re-auditoria** — audita como se fosse a 1ª vez.
  - **Set-difference fica no coordenador (orchestrator):**
    - `prévios ∩ atuais` → não resolvidos → fixer de novo
    - `prévios \ atuais` → resolvidos
    - `atuais \ prévios` → novos (regressões ou omissões anteriores)
  - **Retry por truncagem** respeita o princípio: novo sub-agent recebe o **mesmo prompt original** (talvez mais imperativo), nunca com a resposta pronta.
- **Ação (5 mudanças):**
  1. **`docs/protocol/06-estrategia-evidencias.md`** — adicionar seção formal "Auditoria e re-auditoria sem bias" com o princípio e as proibições.
  2. **Agent files** (`.claude/agents/qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`, `governance.md`) — adicionar modo "re-audit" (ou nota em cada modo de auditoria) explicitando: "quando invocado para re-auditoria, o prompt NÃO deve mencionar findings anteriores, veredito anterior, commits de fix, diff ou arquivos tocados. Recusar mecanicamente se detectar esse conteúdo no prompt".
  3. **Checklist de meta-review do orchestrator** — criar `docs/protocol/09-meta-review-checklist.md` que o orchestrator percorre antes de despachar qualquer prompt de re-auditoria: "meu prompt cita findings anteriores? cita verdict? cita arquivos tocados pelo fixer? se sim, reescreva".
  4. **Hook de enforcement mecânico** (opcional, prioridade média) — `scripts/hooks/auditor-input-lint.sh` que inspeciona prompts emitidos via Agent tool e bloqueia palavras-chave como "finding anterior", "previously found", "foi corrigido", "verifique se X", "o fixer tocou". Mais restritivo, mas mais seguro.
  5. **Política de auditoria 1ª vez ampla** — atualizar prompts padrão de gates inaugurais para passar "perímetro: slice-NNN inteiro com acesso ao repo, justifique onde procurou" em vez de "leia apenas arquivos X, Y, Z". Diferenciar clara 1ª vez (amplo) de re-audit (minimalista sem histórico).
- **Trade-offs:**
  - **Custo**: auditoria 1ª vez mais ampla = mais tokens. Aceitável — o viés custa mais caro (findings perdidos).
  - **Set-difference mecânico**: set-difference por `file:line + descrição` é frágil quando fix move código. Mitigação: comparar por **assinatura semântica** (hash categoria + descrição normalizada + path sem linha).
  - **Retry de truncagem**: pode levar a loop infinito se sub-agent trunca consistentemente. Fallback R6: 5 truncagens → escalar PM com prompt em texto puro.
- **Status:** aberto. Prioridade **alta**. Complementar a B-036 (ambos tratam qualidade de revisão/regressão, mas por ângulos diferentes: B-036 = testes mecânicos; B-037 = auditoria humana/LLM).

### [B-036] Regressão gate automática — CI full em PR + smoke suite no pre-push

- **Origem:** sessão slice-017 (2026-04-17). PM identificou lacuna sistêmica: harness atual só roda testes do slice ativo (`mechanical-gates.sh` chama `ac-tests.sh slice-NNN`), o que permite que slices novos quebrem silenciosamente slices anteriores.
- **Evidência concreta:** slice 017 modificou `src/main.tsx` + `src/sw-registration.ts` adicionando registro de Service Worker. O teste `tests/e2e/ac-001-dev-server.spec.ts` (slice 016) passou a falhar com `The script has an unsupported MIME type ('text/html')` porque o SW tentava registrar em Vite dev mode (onde `/sw.js` não existe). Regressão **não foi detectada** por nenhum gate existente — só apareceu quando o PM pediu validação manual com `KALIB_E2E_MODE=dev npx playwright test`. Corrigido em commit `0aed77f` (guard `import.meta.env.PROD`).
- **Ação (proposta D — 2 camadas):**
  1. **CI em PR (bloqueante):** `.github/workflows/test-regression.yml` roda `npm run test:scaffold` + `npx playwright test` (ambos projects dev/preview) em todo push para branch de PR. Ruleset de `main` já bloqueia merge se o workflow falhar. Repo já público desde 2026-04-15 (sem constrangimento de Actions quota).
  2. **Smoke suite no pre-push:** tag `@smoke` em testes críticos (aprox. 10-15 cobrindo jornadas: login, CRUD cliente, scaffold render, PWA offline, auth). `scripts/smoke-tests.sh` lista arquivos tagueados e roda no pre-push hook. Rápido (<30s). Adicional: `slice-report.sh` passa a mostrar seção "regressão checada: X/Y testes de slices anteriores passaram".
  3. **Política:** todo slice que tocar arquivo compartilhado (`src/main.tsx`, `vite.config.ts`, `package.json`, `capacitor.config.ts`, qualquer `src/auth/*`, qualquer `app/Http/Controllers/*`) obriga rodar smoke suite local antes do commit. Implementer valida em pré-commit-gate.sh via detecção de paths tocados.
- **Status:** aberto. Prioridade **alta**. Bloqueia próximo slice (E15-S04 ou onde for) até que pelo menos a camada 1 (CI em PR) esteja ativa.

### [B-034] `audit-spec` deve alertar para ACs que exigem destruição de feature sem substituto agendado

- **Origem:** retrospectiva do slice-016 (`docs/retrospectives/slice-016.md` §"Gates que deveriam ter disparado e não dispararam").
- **Evidência:** ACs AC-008 e AC-013 do slice 016 exigiam "zero Blade fora de /emails/" e "routes/web.php sem `view()`" — ou seja, destruição do frontend Livewire/Blade. O substituto funcional (API de auth consumida pelo SPA) só nasce em E15-S07. Os três auditores pré-impl (`audit-spec`, `plan-review`, `audit-tests-draft`) não pegaram essa dependência temporal porque avaliaram coerência interna do slice, não cross-slice. PM teve de escalar via "Opção A/B/C" durante a implementação.
- **Ação:** estender checklist do `qa-expert` modo `audit-spec` (ADR/skill + prompt) com verificação explícita: "quando um AC exige remoção de feature existente em produção (comando `rm`, assertiva de ausência, `remove`, `delete`, `não deve existir`), o auditor deve perguntar: (a) existe substituto funcional pronto?; (b) o slice aceita regressão temporária? se não há resposta explícita, finding S2 mínimo bloqueia o gate".
- **Status:** aberto. Prioridade média.

### [B-035] Template `ac-tests.sh` canônico para slices frontend-only (Node + Playwright)

- **Origem:** retrospectiva do slice-016 (`docs/retrospectives/slice-016.md` §"Gates que dispararam em falso" + §"Mudanças propostas").
- **Evidência:** `mechanical-gates.sh` Gate 1 exige `tests/slice-NNN/` formato Pest, mas slice 016 é 100% frontend (Node+Playwright, zero PHP). Solução ad-hoc: `tests/slice-016/ac-tests.sh` como bridge shell delegando para `npm run test:scaffold && npm run test:e2e`. Fix aplicado no commit `65d8cb9` (+ update em `scripts/test-scope.php` para aceitar o padrão). Sem template, próximo slice frontend vai refazer do zero.
- **Ação:** criar `templates/slice-ac-tests-frontend.sh` (template cru) e documentar em `docs/development-guide.md` quando usar (slice sem PHP tocado, stack frontend-only). Considerar auto-geração pelo `/new-slice NNN` quando o slice declarar `stack: frontend` no frontmatter da spec.
- **Status:** aberto. Prioridade baixa — só bloqueia o primeiro slice frontend futuro.

### [B-033] ADR formal para política dual-LLM 2× Opus (não GPT-5) — RESOLVIDO 2026-04-17

- **Origem:** decisão do PM em 2026-04-17 durante retomada do slice-015 (cancelou invocação Codex CLI no meio).
- **Evidência:** `feedback_dual_llm_two_opus.md` + incidentes recorrentes com Codex CLI em Windows (sandbox `CreateProcessAsUserW failed: 5`, ChatGPT Plus não aceita `--model`, timeout eterno).
- **Resolução:** PR #41 adiciona:
  - `docs/adr/0020-dual-llm-policy-2x-opus.md` — ADR aceito, supersede ADR-0012 §Trilha B.
  - `docs/operations/dual-llm-opus-setup.md` — guia canônico (prompt da Trilha B, consolidação, reconciliação, fallback).
  - `docs/operations/codex-gpt5-setup.md` — marcado DEPRECATED com link para guia canônico; preservado como fallback documentado.
- **Status:** resolvido.

### [B-025] Telemetria de tokens em todos os gates

- **Origem:** retrospectiva do slice-011.
- **Evidência:** `docs/retrospectives/slice-011-report.md` mostrou `Tokens totais = 0` apesar de ~35 sub-agents invocados (8 rodadas reviewer, 3 verifier, 2 security/test/functional, master-auditor dual-LLM). Eventos `verify`/`review`/`security-review`/`test-audit`/`functional-review` não emitem `tokens_used`.
- **Ação:** atualizar `scripts/record-telemetry.sh` e schemas (`docs/schemas/*.schema.json`) para incluir `tokens_used` em todos os eventos de gate. Ajustar `slice-report.sh` para somar.
- **Status:** aberto. Prioridade média; bloqueia análise de R8 (budget de tokens).

### [B-029] Migrar scripts/merge-slice.sh para gate-output-v1 (protocolo v1.2.4) — RESOLVIDO 2026-04-17

- **Origem:** retrospectiva do slice-015.
- **Evidência:** `scripts/merge-slice.sh` em main ainda exige schema legado (`slice_id`, `violations`, `findings`, `severity_summary`, `lgpd_checks`, `ac_coverage`, `ac_assessment`) enquanto `docs/protocol/schemas/gate-output.schema.json` v1.2.2 já é o canônico. Slice 015 precisou de patch bi-schema manual para merge acontecer.
- **Ação:** migrar o validador Python embutido no merge-slice.sh para ler `gate`, `findings_by_severity`, `blocking_findings_count` do gate-output-v1. Propagar para os outros validadores (audit-spec, plan-review, validate-review, validate-verification).
- **Resolução:** PR #40 (union merge de 2bbce17) trouxe a versão migrada do merge-slice.sh. Validador embutido em Python agora lê `gate`, `blocking_findings_count`, `findings_by_severity` do schema gate-output-v1. Confirmado: 9 refs ao schema novo, 0 ao legado. Protocolo bumpado para v1.2.4 (ADR-0017 `audit-tests-draft` incorporado).
- **Status:** resolvido.

### [B-030] `.gitattributes` forçando LF para arquivos hash-locked — RESOLVIDO 2026-04-17

- **Origem:** retrospectiva do slice-015.
- **Evidência:** `sha256sum -c scripts/hooks/MANIFEST.sha256` falhou em ambiente Windows porque `autocrlf=true` converteu `MANIFEST.sha256` + `.sh` para CRLF, quebrando a verificação do `hooks-lock`. Normalização manual com `sed -i 's/\r$//'` foi necessária para destravar merge.
- **Ação:** adicionar `.gitattributes` com `scripts/hooks/** text eol=lf`, `docs/protocol/schemas/** text eol=lf`, `*.sha256 text eol=lf binary`. Documentar em `docs/harness-limitations.md`.
- **Resolução:** PR #41 adiciona em `.gitattributes`: `scripts/hooks/*.sh text eol=lf`, `scripts/hooks/MANIFEST.sha256 text eol=lf`, `*.sha256 text eol=lf`, `docs/protocol/schemas/*.json text eol=lf`, `*.md text eol=lf`. Devs que clonaram antes precisam rodar `git rm --cached -r . && git reset --hard` para re-aplicar normalizações. Limitação L-02 em `docs/harness-limitations.md`.
- **Status:** resolvido.

### [B-031] Hook para detectar branch desatualizada vs origin/main — PROPOSTA PRONTA (aguarda relock PM)

- **Origem:** retrospectiva do slice-015.
- **Evidência:** Branch `work/offline-discovery-2026-04-16` ficou pausada durante ampliação + migração protocolo v1.2.2. Quando tentamos merge do slice 015, conflitos em ~15 arquivos (incluindo arquivo selado `MANIFEST.sha256`) obrigaram abandono do PR #35 e criação de branch nova + cherry-pick.
- **Ação:** adicionar check em `session-start.sh` (ou hook dedicado) que rode `git fetch origin main && git log --oneline origin/main..HEAD` e avise quando a branch atual está > N commits atrás de main (ou há commits em main que não estão na branch). Threshold sugerido: 10 commits.
- **Resolução parcial:** PR #41 entrega proposta completa em `scripts/staging/branch-sync-check.sh` + instruções PM em `docs/operations/relock-branch-sync-check.md`. Script verificado em working tree. Integração ao session-start.sh requer relock externo pelo PM (área selada).
- **Status:** aguardando relock PM para mover de `scripts/staging/` para `scripts/hooks/`.

### [B-032] Telemetria dos sub-agents isolados não chega ao .jsonl do slice — DOCUMENTADO 2026-04-17 (ainda aberto)

- **Origem:** retrospectiva do slice-015.
- **Evidência:** `docs/retrospectives/slice-015-report.md` saiu com `Commits: 0 | Approved: 0 | Rejected: 0 | Tokens totais: 0` apesar de 9 gates aprovados por sub-agents isolados (incluindo dual-LLM master-audit). O Agent tool em contexto isolado (worktree/subagent) provavelmente não tem acesso ao pipe de `.claude/telemetry/`.
- **Ação:** investigar se `scripts/record-telemetry.sh` precisa ser invocado explicitamente pelos sub-agents ou se há forma de propagar via env var. Se ambiente isolado for o bloqueio, documentar em `docs/harness-limitations.md` e revisar expectativa de slice-report para slices com muitos sub-agents isolados.
- **Documentação:** PR #41 adiciona seção L-03 em `docs/harness-limitations.md` descrevendo causa raiz (Agent tool subprocesso não herda env do projeto pai), impacto (slice-report.sh gera números zerados) e mitigações potenciais (passar SLICE_NNN+TELEMETRY_PATH no prompt do Agent OU consolidação pós-gate). Implementação fica para sessão dedicada.
- **Status:** aberto — investigação concluída, mitigação pendente. Prioridade média.

### [B-026] Red-check estrito: rejeitar `markTestIncomplete()` em ac-to-test — RESOLVIDO 2026-04-15

- **Origem:** retrospectiva do slice-011 — verifier rejeitou rodada 2 (`2026-04-15T22:50:06Z`) por 29 testes incomplete.
- **Evidência:** ac-to-test gerou stubs `markTestIncomplete()` que passaram pelo red-check (não falhavam por assertion). Fixer teve que converter em testes reais que falhavam.
- **Ação:** atualizar `.claude/agents/ac-to-test.md` com regra explícita.
- **Resolução (harness-learner E02):** adicionada seção "Stubs proibidos como red (B-026)" em `.claude/agents/ac-to-test.md` com lista explícita de métodos proibidos (`markTestIncomplete`, `markTestSkipped`) e definição de "red válido" (falha por assertion/exception). Hook `red-check.sh` é selado; melhoria futura no hook requer relock pelo PM.
- **Status:** resolvido. Agent-level enforcement ativo.

### [B-027] Detectar fail-open em scopes globais Eloquent — RESOLVIDO 2026-04-15

- **Origem:** retrospectiva do slice-011 — reviewer pegou em rodada inicial que `ScopesToCurrentTenant` original era fail-open quando sem tenant context.
- **Evidência:** scope global retornava query sem filtro quando `TenantContext::current()` era null, vazando dados cross-tenant em jobs sem context. Fixado com `whereRaw('1=0')` (fail-closed).
- **Resolução (harness-learner E02):** adicionada seção "Fail-Open em Scopes Globais / Builder Scopes (B-027)" no checklist do `security-reviewer` (`.claude/agents/security-reviewer.md`). Cobre: GlobalScope, BuilderScope, local scopes, multi-tenancy, jobs/workers sem contexto. Classifica fail-open como finding critical.
- **Status:** resolvido. Security-reviewer checklist endurecido.

### [B-028] Slice-report enriquecido: rodadas por gate

- **Origem:** retrospectiva do slice-011.
- **Evidência:** report atual conta apenas verifier (1 rejected). O sinal mais relevante do slice-011 (8 rodadas reviewer com 7 rejeições reais) ficou invisível.
- **Ação:** atualizar `scripts/slice-report.sh` para contar rodadas por gate (verifier/reviewer/security/test/functional/master-auditor), com taxa de finding-fix entre rodadas. Sobrepõe parcialmente com B-024 — pode ser mesma entrega.
- **Status:** aberto. Prioridade média; melhora leitura pós-slice.

### [B-023] Guardrail para impedir slice de produto iniciado diretamente em `main` — RESOLVIDO 2026-04-15

- **Origem:** retrospectiva do slice-008 e incidente `docs/incidents/slice-008-mainline-integration-2026-04-14.md`.
- **Evidência:** slices 006 e 008 chegaram ao encerramento com todos os gates aprovados, mas ja estavam em `main`. O `merge-slice.sh` bloqueia corretamente em `main`, porem tarde demais para evitar a excecao operacional.
- **Resolução (harness-learner E02):** adicionado check "Branch != main (B-023)" como pre-condicao em `.claude/skills/start-story.md` (item 3) e `.claude/skills/new-slice.md` (item 2). Bloqueia inicio de slice quando branch == `main`, orienta criar feature branch. Bypass via `KALIB_SKIP_BRANCH_CHECK` com registro de incidente.
- **Status:** resolvido. Guardrail preventivo ativo nas duas skills de criacao de slice.

### [B-024] `slice-report.sh` deve contabilizar todos os gates atuais

- **Origem:** retrospectiva do slice-008.
- **Evidência:** `docs/retrospectives/slice-008-report.md` mostra `Verificações (approved) = 1` porque o script conta apenas eventos `verify`, enquanto o fluxo atual inclui review, security-review, test-audit, functional-review e merge.
- **Ação:** atualizar `scripts/slice-report.sh` para listar contagem por evento/gate, incluindo rejeicoes de review e gates finais, e diferenciar commits/tokens indisponiveis de valor real zero.
- **Status:** aberto. Prioridade media; nao bloqueia produto, mas melhora leitura de encerramento.

### [B-022] Melhorar legibilidade de commits no slice-report com telemetria parcial

- **Origem:** retrospectiva do slice-005 gerada em 2026-04-13.
- **Evidência:** `docs/retrospectives/slice-005-report.md` registrou o evento de commit sem `hash`, `author` e `subject` normalizados; a seção de commits repetiu o JSON inteiro como hash, autor e assunto.
- **Ação:** ajustar `scripts/slice-report.sh` para detectar evento de commit sem campos normalizados e renderizar uma linha legível, por exemplo `commit registrado sem metadados normalizados — ver Raw JSONL`.
- **Status:** aberto. Prioridade baixa; não bloqueia execução de slices.

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

### [B-016] Tradução contínua técnico → produto em TODOS os sub-agents — PARCIAL 2026-04-12

- **Origem:** análise 2026-04-11 da classe Hercules/Lovable/Bolt como UX pattern. PM hoje fica cego entre `/new-slice` e `/merge-slice` — só vê saída traduzida quando roda `/explain-slice` (escalação R6 ou a pedido). Entre os dois extremos, o harness opera em silêncio técnico.
- **Proposta original:** cada sub-agent emite **2 outputs** (técnico + `-pm.md` irmão, ex.: `plan-pm.md`, `tests-pm.md`, `diff-pm.md`, `verification-pm.md`, `review-pm.md`).
- **Divergência arquitetural adotada (2026-04-12 / Fase A Bloco 1):** após implementar B-010 (`scripts/translate-pm.sh`) e G-11 (auto-dispare no `verify-slice.sh`), adotou-se modelo **centralizado** em vez de distribuído:
  - Sub-agents **não** geram tradução PM — emitem apenas saída técnica (JSON, plan.md, testes, código).
  - Tradução PM é centralizada em `scripts/translate-pm.sh`, invocada pelos **scripts orquestradores** (`verify-slice.sh --validate`, `review-slice.sh --validate`) ao final de cada handoff.
  - Resultado: único arquivo consolidado `docs/explanations/slice-NNN.md` que "cresce" naturalmente a cada handoff (verify → review → merge).
- **Razões da divergência:**
  - 1 fonte de tradução → upgrade de qualidade acontece em 1 lugar
  - Sub-agents não gastam tokens em R12 (economia ~3-5k por invocação × 6 agentes)
  - Já exercitado no G-11 (B-010 + verify-slice auto-dispare)
  - Consistência: mesma tradução independente de qual agent produziu
- **Escopo resolvido no Bloco 1 (2026-04-12):**
  - ✅ `scripts/translate-pm.sh` como único tradutor (B-010)
  - ✅ `scripts/verify-slice.sh --validate` dispara tradução (G-11)
  - ✅ `scripts/review-slice.sh --validate` dispara tradução (B-016 / G-11 estendido)
  - ✅ `.claude/agents/*.md` (5 sub-agents: architect, ac-to-test, implementer, verifier, reviewer) documentam que **não** geram tradução — seção "Output em linguagem de produto (B-016 / R12)" adicionada ao final de cada arquivo
  - ✅ `guide-auditor` não se aplica (audit report é standalone, fora do slice flow)
- **Escopo resolvido no Bloco 2 (2026-04-12):**
  - ✅ G-04 — `/draft-spec NNN` skill interativa NL→ACs + `scripts/draft-spec.sh` validador (já existia do meta-audit #2)
  - ✅ G-05 — `/draft-plan NNN` skill wrapper do architect + `scripts/draft-plan.sh` validador (--check + --validate)
  - ✅ G-07 — `/draft-tests NNN` skill wrapper do ac-to-test + `scripts/draft-tests.sh` validador (--check + --validate)
  - Skills apresentam resultado ao PM em linguagem R12 (sem código/paths/jargão)
- **Escopo pendente (B-016.1, abrir se necessário):**
  - Auto-dispare em `merge-slice.sh` (atualizar o relatório com status "merged").
- **Evidência:** commits do Bloco 1 Fase A (B-010, G-11, B-016 parcial).

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

### [B-025] `scripts/build-gate-inputs.sh` como ferramenta oficial

- **Origem:** retrospectiva do slice-009.
- **Evidência:** o fluxo dos 3 gates paralelos (security/test-audit/functional) exige montagem de 3 pacotes de input simultâneos, mas só existia `scripts/security-scan.sh` para a parte mecânica. Durante o slice-009 foi necessário improvisar `scripts/build-gate-inputs.sh` ad-hoc para cada rodada de correção.
- **Ação:** formalizar `scripts/build-gate-inputs.sh` (ou quebrar em 3 scripts: `build-security-input.sh`, `build-test-audit-input.sh`, `build-functional-input.sh`) com contrato estável:
  1. Ler `specs/NNN/spec.md` + `git diff main..HEAD`
  2. Copiar fontes alteradas em árvore preservada
  3. Rodar `php artisan test` para `test-results.txt`
  4. Anexar threat-model/lgpd/constitution/glossary/personas/journeys conforme gate
  5. Falhar se arquivo proibido (R11) aparecer no pacote
- **Status:** alta prioridade — os 3 gates paralelos já são parte do pipeline e rodam múltiplas vezes em loops de fix.

### [B-026] Espelhar gates locais em GitHub Actions como `required_status_checks`

- **Origem:** retrospectiva do slice-009 + decisão 2026-04-14 de ativar auto-merge.
- **Evidência:** auto-merge foi ligado com `required_approving_review_count=0` e sem required status checks. Hoje a única garantia de qualidade é o fluxo local (`/verify-slice` → `/review-pr` → 3 gates paralelos). Se um dev futuro empurrar direto sem rodar `/merge-slice`, nada no GitHub barra.
- **Ação:** criar `.github/workflows/gates.yml` que replique em CI:
  - Job 1: rodar testes (`php artisan test`)
  - Job 2: Pint + PHPStan nível 8
  - Job 3: `composer audit` + secrets scan
  - Job 4: validar que `specs/NNN/*.json` estão presentes com `verdict: approved` para cada slice tocado
  - Marcar como `required` na ruleset "Protect main"
- **Pré-requisito:** B-007 já entregou CI dormant; expandir para ser verdadeiramente exigente.
- **Status:** alta prioridade enquanto auto-merge está ativo sem double-check de CI.

### [B-027] Hook de consistência commit message ↔ estado dos gates

- **Origem:** retrospectiva do slice-009.
- **Evidência:** commit `b270cd2 fix(slice-009): corrige achados dos gates paralelos` foi criado em sessão anterior ANTES dos gates paralelos terem rodado. A sessão seguinte teve que diagnosticar inconsistência entre mensagem e estado (`specs/009/*.json` ausentes, `project-state.json` desatualizado). Mensagens enganosas poluem audit trail e quebram resume/handoff.
- **Ação:** adicionar `scripts/hooks/commit-msg-coherence.sh` (pre-commit-gate ou commit-msg hook) que valide:
  - Se mensagem contém "corrige achados do `<gate>`" ou "fecha findings do `<gate>`" → exigir que `specs/NNN/<gate>.json` exista e tenha verdict `rejected` com findings não-vazios
  - Se mensagem contém "aprova" → exigir verdict `approved`
- **Severidade proposta:** `warn` (não `fail`) para não bloquear commits legítimos em casos edge; escalar para `fail` após 2-3 slices validando a regra.
- **Status:** média prioridade.

### [B-028] SessionStart avisa drift local ↔ origin

- **Origem:** retrospectiva do slice-009.
- **Evidência:** local `main` tinha 14 commits não empurrados para origin (slice 008 integrado localmente sem push). Só descoberto quando `/merge-slice` tentou `git pull` pós PR #10 e caiu em conflito. Para salvar, foi necessário `git reset origin/main` após autorização do PM. Drift silencioso durou ~24h.
- **Ação:** estender `scripts/hooks/session-start.sh` com bloco opcional:
  ```
  LOCAL_AHEAD=$(git log --oneline origin/main..main 2>/dev/null | wc -l)
  LOCAL_BEHIND=$(git log --oneline main..origin/main 2>/dev/null | wc -l)
  [[ $LOCAL_AHEAD -gt 0 ]] && echo "[session-start WARN] main local esta $LOCAL_AHEAD commits a frente de origin"
  [[ $LOCAL_BEHIND -gt 0 ]] && echo "[session-start WARN] main local esta $LOCAL_BEHIND commits atras de origin"
  ```
- **Severidade:** `warn`, não `fail`. PM decide se push/pull é apropriado.
- **Status:** baixa prioridade, mas barato de implementar.

### [B-030] Automatizar atualizacao de `project-state.json[epics_status]` no `merge-slice`

- **Origem:** ADR-0011 (R13/R14 — ordem Story × Epic).
- **Evidencia:** `project-state.json[epics_status]` e a fonte canonica do gate R13/R14, mas hoje precisa ser editado manualmente apos cada merge. Risco de drift silencioso: gate pode liberar slice indevidamente se estado nao for atualizado.
- **Acao:** estender `scripts/merge-slice.sh` para apos `gh pr merge` autorizado:
  1. Extrair codigo da story do titulo do PR ou de `specs/NNN/spec.md` (padrao `ENN-SNN`)
  2. Atualizar `project-state.json[epics_status][ENN].stories[ENN-SNN] = "merged"`
  3. Se todas as stories do epico ficaram `merged`, marcar `epics_status[ENN].status = "merged"`
  4. Commit automatico com mensagem `chore(state): marca ENN-SNN como merged`
- **Prioridade:** alta. Sem isso, R13/R14 dependem de manutencao manual.

### [B-031] `story-decomposer` deve popular `epics_status` ao decompor

- **Origem:** ADR-0011.
- **Evidencia:** quando `/decompose-stories ENN` cria stories E02-SNN..ENN-SNN, deveria ja registrar em `project-state.json[epics_status][ENN].stories` com `status: "pending"` para que R13/R14 tenham baseline de pendencias.
- **Acao:** atualizar `.claude/agents/story-decomposer.md` com instrucao explicita + script auxiliar.
- **Prioridade:** media. Pode ser feito lazy (primeira execucao de `/decompose-stories` apos ADR-0011).

### [B-032] `epic-decomposer` deve popular `epics_status[ENN].status`

- **Origem:** ADR-0011.
- **Acao:** similar ao B-031: quando `/decompose-epics` cria `epics/ENN/epic.md`, gravar `epics_status[ENN] = { status: "planned", stories: {} }`. Permite R14 detectar epicos nao decompostos ainda.
- **Prioridade:** media.

### [B-033] `next-slice.sh` em modo CONSULTA deve cruzar roadmap × epic.md × epics_status

- **Origem:** ADR-0011 + retrospectiva slice-009.
- **Evidencia:** o script atual so lista `specs/*` para decidir "proximo slice" e delega ao agente a interpretacao do roadmap. O agente pode pular stories orfas (como aconteceu com E02-S07/S08).
- **Acao:** tornar `scripts/next-slice.sh` uma ferramenta que retorna JSON estruturado com:
  - `current_epic`
  - `stories_pending_in_current_epic`
  - `next_story_recommended`
  - `blocked_by: [E02-S07, E02-S08]` quando faltam dependencias
  - `violates_r13_or_r14: true/false`
- **Prioridade:** alta. Reduz chance de repetir o erro que motivou ADR-0011.

### [B-029] Skills de review devem instruir sub-agents a completar em 1 rodada

- **Origem:** retrospectiva do slice-009.
- **Evidência:** `functional-reviewer` pausou 2 vezes consecutivas no meio da execução, sem gravar `functional-review.json` final. Foi necessário respawnar com instrução explícita "NÃO pause, complete e grave o JSON final". Esse padrão se repete em outros agents que fazem verificações múltiplas.
- **Ação:** atualizar prompts nos arquivos `.claude/agents/functional-reviewer.md`, `security-reviewer.md`, `test-auditor.md` com cláusula "Complete TODA a verificação e grave o JSON final em UMA rodada. Não pause para confirmar com o orquestrador — se faltar contexto, registre como finding no próprio JSON e emita rejected."
- **Status:** média prioridade. Pausas consomem tokens extra e adicionam latência ao loop fix→re-gate.

---

## Resolvido

### [B-001] Operacionalizar post-edit-gate pós ADR-0001 — RESOLVIDO 2026-04-12

- **Origem:** `post-edit-gate.sh` anterior era stack-agnóstico e rodava format/lint/testes apenas se as ferramentas existiam.
- **Resolução (Bloco 0 / Fase A pós-auditoria PM):** novo `post-edit-gate.sh` com comandos concretos da stack ADR-0001:
  - Format: Pint (PHP) + Prettier (JS/TS/Vue/CSS/MD)
  - Lint: ESLint (JS/TS/Vue); PHP coberto por PHPStan no step 3
  - Type-check: PHPStan/Larastan nível 8 incremental + `tsc --noEmit`
  - Test mapping: `app/**/*.php` → `tests/Unit/**` E `tests/Feature/**` (roda ambos se existirem, cobre convenções Pest sem forçar uma)
  - Skips silenciosos para migrations/seeders/factories/routes/blade/config/bootstrap
- **Tolerância a ferramentas ausentes preservada:** cada passo só roda se o binário existir, permitindo edição de docs/config antes de `composer install`.
- **Evidência:** commit `75994ea` (pós-relock) + incidente `docs/incidents/harness-relock-2026-04-12T00-56-44Z.md`.
- **Impacto:** P4 + P8 agora enforced com comandos reais da stack.

### [B-007] Integração com CI externo — RESOLVIDO 2026-04-12

- **Origem:** P8 (suite full em CI).
- **Resolução (Bloco 0 / Fase A):** `.github/workflows/ci.yml` com 6 jobs:
  1. Harness integrity (smoke-test de hooks + scripts + scan de arquivos proibidos)
  2. PHP lint (Pint `--test`)
  3. PHP static analysis (Larastan nível 8)
  4. PHP tests (Pest 4 + PostgreSQL 18 como service)
  5. JS lint (ESLint + Prettier)
  6. Security scan (composer audit + npm audit + CycloneDX SBOM)
- **Design dormant:** jobs de PHP/JS usam `if: hashFiles('composer.json'|'package.json') != ''`. O workflow existe pré-`composer create-project` e "acorda" automaticamente quando Laravel inicializar. Jobs marcados como "skipped" até lá — válido, não falha.
- **Evidência:** commit `0d34a27`.

### [B-020] Wrapper 1-click pro relock — RESOLVIDO 2026-04-12

- **Origem:** atrito medido em 2026-04-11 durante meta-audit #2 — 5+ interações pra 1 relock.
- **Resolução (Bloco 0 / Fase A):**
  - `scripts/relock-and-commit.sh` — wrapper bash que detecta mudança em arquivos selados, pergunta descrição, chama `relock-harness.sh`, faz stage cirúrgico + commit com mensagem `chore(harness): <desc>`.
  - `tools/relock.bat` — atalho Windows, duplo-clique abre Git Bash em janela interativa e roda o wrapper.
  - `tools/apply-b001.bat` — one-click applier específico pro B-001 (copia draft por cima do hook selado + chama `relock.bat`). Serve como **template** pra futuros appliers one-shot quando um bloco tiver apenas 1 item selado.
- **Salvaguardas preservadas:** camadas 2 (TTY interativa) e 3 (digitação literal `RELOCK`) permanecem intactas. Só a camada 1 (`KALIB_RELOCK_AUTHORIZED`) foi internalizada no wrapper (conveniência).
- **Exercitado por caso real:** usado para ativar B-001 (commit `75994ea`). Primeiro uso end-to-end do wrapper.
- **Evidência:** commits `c532a43` (wrapper) + `7d1731a` (applier one-shot).
- **Nota de prioridade:** elevado de "baixa" para "alta" pela auditoria de operabilidade PM 2026-04-12 (`docs/audits/pm-operability-audit-2026-04-12.md`).
- **Follow-up proposto:** skill `/batch-harness-changes` (coletora de múltiplos drafts num único applier) vai nascer no primeiro bloco futuro que tiver 2+ itens selados simultâneos. Até lá, applier one-shot (template do `apply-b001.bat`) resolve.

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
- 2026-04-12 — Fase B: auditoria de operabilidade PM entrega 23 gaps novos (G-01..G-23) em `docs/audits/pm-operability-audit-2026-04-12.md` + revisão de prioridades do backlog existente
- 2026-04-12 — Fase A / Bloco 0: B-001, B-007, B-020 resolvidos (post-edit-gate Laravel + CI dormant + wrapper relock exercitado por caso real)
- 2026-04-16 — B-025, B-026, B-027, B-028 adicionados pós retrospectiva slice-011 (E02-S08 isolamento multi-tenant)
- 2026-04-15 — B-023, B-026, B-027 resolvidos por harness-learner E02 (ADR-0012 E4 / R16)
