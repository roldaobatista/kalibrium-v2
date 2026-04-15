# Plano de ação — Upgrade do harness para ambiente local perfeito

**Data:** 2026-04-14
**Autor:** Claude Opus 4.6 (auditoria a pedido do PM)
**Revisão externa:** 2 rodadas de LLM independente em 2026-04-14 — 3 riscos técnicos + 4 gaps de processo (rodada 1) + 6 ressalvas bloqueantes + 3 ajustes (rodada 2), todos incorporados (§13, §14).
**Status:** draft-v4 — incorpora ADR-0012 (autonomia do agente + dual-LLM + retrospectiva + harness-learner). Aguardando PM clicar `relock.bat` + assinar decisão.
**Mudança estrutural v3→v4:** PM não opera terminal. `plan-reviewer` em contexto isolado foi substituído por `master-auditor` (dual-LLM Claude + GPT-5 via Codex). Fluxo automatizado até bloqueio real ou fim de épico. Ver §15.
**Motivação:** Reduzir atrito local do harness sem afrouxar enforcement, fechar gaps identificados na auditoria de 2026-04-14.
**Cross-ref:** `docs/constitution.md §5`, `docs/governance/harness-evolution.md`, `docs/harness-limitations.md`, `.claude/settings.json`, `CLAUDE.md §9`.

---

## 1. Contexto

O harness do Kalibrium V2 já está **muito acima da média** em governança (P1-P9, R1-R14, 22 sub-agents, 38 skills, hooks selados, pipeline CI, dual-gate spec/plan-reviewer). A auditoria de 2026-04-14 identificou que os pontos de fragilidade **não** estão no harness de agente — estão no **ambiente de desenvolvimento local**, onde devs humanos (e o agente em CI) ainda enfrentam:

- Setup manual não-reproduzível (sem devcontainer)
- Pre-commit gates só rodam em CI (brecha para commit local fora do Claude Code)
- Caminhos absolutos Windows-específicos em `.claude/settings.json` (Stop hook referencia `/c/PROJETOS/saas/kalibrium-v2/...`)
- Coverage não é gated no CI
- Atalhos de comando espalhados em `composer scripts` — dev precisa memorizar

Este plano **não cria regra nova**, **não revoga regra existente**, **não altera sub-agent**. Ele propõe:

- **1 correção em arquivo selado** (paths absolutos → `${CLAUDE_PROJECT_DIR}`) → exige relock manual pelo PM
- **1 endurecimento de deny list** em `.claude/settings.json` → exige relock manual pelo PM
- **N adições em arquivos não-selados** (devcontainer, lefthook, Taskfile, phpunit.xml) → podem ser executadas pelo agente via PR normal

Toda mudança segue `docs/governance/harness-evolution.md §5` (novo hook = relock manual) e respeita `docs/harness-limitations.md §Edição externa de hooks`.

---

## 2. Diagnóstico consolidado

| Gap | Arquivo afetado | Selado? | Severidade |
|---|---|---|---|
| Paths absolutos `/c/PROJETOS/saas/kalibrium-v2/...` em Stop hook | `.claude/settings.json` | ✅ sim | **CRÍTICA** |
| `git checkout .` / `git restore .` não bloqueados na deny list | `.claude/settings.json` | ✅ sim | ALTA |
| Sem devcontainer/docker-compose (setup manual, env heterogêneo) | novos arquivos | ❌ não | ALTA |
| Pre-commit hook local inexistente (só hooks CI) | `lefthook.yml` novo | ❌ não | MÉDIA |
| Coverage threshold não gated | `phpunit.xml`, CI workflow | ❌ não | MÉDIA |
| Atalhos dispersos em composer scripts | `Taskfile.yml` novo | ❌ não | BAIXA |
| Sem `pest --watch` para TDD local | `composer.json` | ❌ não | BAIXA |

Referência da auditoria completa: seção 6 do relatório interno do agente (2026-04-14).

---

## 3. Decisões arquiteturais

### D1: Corrigir paths absolutos via relock manual (não contornar)
**Opções consideradas:**
- **A.** Pedir relock ao PM editando `.claude/settings.json` em terminal externo.
- **B.** Criar wrapper script que resolve path dinamicamente e chamar o wrapper via path relativo.
- **C.** Deixar como está (cada dev em Windows com mesmo path).

**Escolhida:** A.

**Razão:** Opção A é o caminho documentado em `docs/harness-limitations.md §Edição externa de hooks` e `CLAUDE.md §9`. B adiciona indireção sem ganho — `${CLAUDE_PROJECT_DIR}` já é interpolado pelo Claude Code. C fere P3 (enforcement por arquitetura, não convenção de path de máquina).

**Reversibilidade:** fácil (git revert + relock).

**ADR:** não necessário (correção de bug, não alteração de regra).

### D2: Adicionar deny rules sem ADR (fortalecimento, não afrouxamento)
**Escolhida:** adicionar `Bash(git checkout -- *)`, `Bash(git checkout .)`, `Bash(git restore *)` em `.claude/settings.json` deny list.

**Razão:** `docs/governance/harness-evolution.md §5` permite fortalecimento de enforcement com justificativa leve em arquivo de incidente. Não é regra nova (R-NN), é extensão da lista de operações destrutivas já denied (rm -rf, reset --hard, push --force).

**Reversibilidade:** fácil.

**ADR:** não necessário. Justificativa leve em arquivo de incidente de relock.

### D3: Devcontainer como fonte única de verdade do ambiente
**Opções consideradas:**
- **A.** `.devcontainer/devcontainer.json` + `docker-compose.yml` com PHP 8.4 + PostgreSQL 18 + Redis.
- **B.** Apenas `docker-compose.yml` (sem devcontainer).
- **C.** Documentar setup manual em `docs/operations/local-setup.md`.

**Escolhida:** A.

**Razão:** Devcontainer roda tanto em VS Code quanto Codespaces quanto CLI (`devcontainer up`). Claude Code dentro do devcontainer garante que humanos e agente rodam no mesmo ambiente — elimina "funciona no meu Windows" e mitiga RNF de reprodutibilidade.

**Reversibilidade:** fácil (remover diretório).

**ADR:** não necessário (infra aditiva, não muda P/R).

### D4: Lefthook ao invés de Husky
**Opções consideradas:**
- **A.** Lefthook (YAML, Go binary).
- **B.** Husky (Node-based, integrado com npm).
- **C.** Script bash `.git/hooks/pre-commit` manual.

**Escolhida:** A.

**Razão:** Lefthook tem config declarativa, roda comandos em paralelo, binário Windows nativo. C não é versionado por default.

**Instalação — nota pós-revisão v2→v3:** v2 propunha `"lefthook/lefthook": "^1.12"` no Composer, mas **esse pacote não existe no Packagist** (verificado via `composer show`). Lefthook é distribuído via NPM, Go, Homebrew, Winget, Scoop, Chocolatey. Instalação correta:
- **Dev local (devcontainer):** `postCreate.sh` instala via `curl` do binário oficial ou via `apt`/`brew` conforme OS do container.
- **Dev local (fora do devcontainer):** README documenta 1 linha por OS (`brew install lefthook` / `scoop install lefthook` / `winget install evilmartians.Lefthook`).
- **CI:** ação `lefthook-action` ou `brew install lefthook` no runner.
- Opcionalmente, `package.json` adiciona `"lefthook": "^1.12"` em `devDependencies` com `postinstall` ativando os git hooks via `npx lefthook install` — aproveita que o projeto **já tem** Node para assets.

**Reversibilidade:** fácil.

**ADR:** não necessário.

### D5: Taskfile.yml ao invés de Makefile
**Opções consideradas:**
- **A.** Taskfile.yml (go-task, cross-platform).
- **B.** Makefile (nativo Unix, atrito em Windows).
- **C.** Justfile (similar ao Taskfile).

**Escolhida:** A.

**Razão:** projeto tem dev principal em Windows (PM). Taskfile tem binário Windows nativo, syntax declarativa, não depende de GNU Make. Makefile em Windows exige WSL/MSYS.

**Reversibilidade:** fácil.

**ADR:** não necessário.

---

## 4. Fases e entregáveis

### Fase 0 — Pré-requisitos (antes de qualquer relock ou PR)

**Responsável:** agente + PM.

#### 0.1 PoC de interpolação `${CLAUDE_PROJECT_DIR}` em Stop hook

**Problema:** documentação da Anthropic cobre `${CLAUDE_PROJECT_DIR}` para PreToolUse/PostToolUse; Stop hook tem menos exemplos. Se a variável não for interpolada no evento Stop, o relock da Fase 1.1 quebra o hook silenciosamente.

**Correção pós-revisão v2→v3:** a versão anterior propunha criar `scripts/hooks/poc-stop-test.sh`, mas `scripts/hooks/*` é path selado — criar arquivo ali exigiria um relock só para a PoC e outro para removê-la. Auto-derrota. Nova abordagem usa path não-selado.

**Execução (agente, ~15 min):**
1. Branch de teste `chore/poc-claude-project-dir-stop`.
2. Criar script de PoC em path **não-selado**: `scripts/poc/stop-interpolation-probe.sh`. Conteúdo minimal:
   ```bash
   #!/usr/bin/env bash
   echo "CLAUDE_PROJECT_DIR=$CLAUDE_PROJECT_DIR | PWD=$(pwd) | $(date -Iseconds)" \
     >> "${CLAUDE_PROJECT_DIR:-/tmp}/scripts/poc/stop-probe.log"
   exit 0
   ```
3. **Opção A — inline (preferida):** PM relocka adicionando ao Stop hook existente uma entrada inline:
   `bash -c 'echo "DIR=$CLAUDE_PROJECT_DIR" >> ${CLAUDE_PROJECT_DIR}/scripts/poc/stop-probe.log'`
   Zero criação de arquivo em path selado. Relock único.
4. **Opção B — fallback (script não-selado):** se inline não couber no schema do settings.json, apontar o Stop hook adicional para `bash ${CLAUDE_PROJECT_DIR}/scripts/poc/stop-interpolation-probe.sh`. Path fora de `scripts/hooks/` não é selado.
5. Rodar sessão nova do Claude Code, encerrar, verificar `scripts/poc/stop-probe.log`.
6. Se interpolado corretamente → registrar em `docs/incidents/poc-claude-project-dir-stop-hook.md` e **segundo relock** do PM remove a entrada de PoC e aplica o fix da Fase 1.1 no mesmo relock (economiza 1 ciclo).
7. Se **não** interpolado → abortar Fase 1.1; migrar para alternativa (wrapper script invocado por path absoluto relativo a `cwd` conhecido, documentado em arquivo de incidente).

**Sem PoC verde, Fase 1.1 não executa.**

#### 0.2 Baseline de coverage atual

**Problema:** gating em 70% sem medir o estado atual pode bloquear todo merge a partir do dia seguinte.

**Execução (agente, ~10 min):**
1. Rodar `vendor/bin/pest --coverage --min=0` localmente e em CI.
2. Registrar percentual atual em `docs/reports/coverage-baseline-2026-04-14.md`.
3. Threshold proposto para Fase 2.4:
   - Se atual ≥ 70% → gate em 70% imediato.
   - Se atual entre 50-69% → gate no valor atual menos 5 pontos, escalação trimestral para 70%.
   - Se atual < 50% → gate no valor atual, plano separado de recuperação de cobertura (fora deste plano).

#### 0.3 Confirmar versões contra ADR-0001

**Problema:** compose propõe PostgreSQL 18 + Node 22. Se ADR-0001 (`/decide-stack`) fixa outras versões, devcontainer precisa bater com o ADR.

**Execução (agente, ~5 min):**
1. Ler `docs/adr/0001-*.md` (se existir; caso contrário, ADR de stack equivalente).
2. Alinhar versões do `docker-compose.yml` e `.devcontainer/Dockerfile` com o ADR.
3. Se ADR diverge de `.env.example` (que sugere pgsql), abrir ticket para reconciliar — fora deste plano.

#### 0.4 Revisão por `master-auditor` dual-LLM (substitui plan-reviewer, conforme ADR-0012)

**Mudança v3→v4:** `plan-reviewer` em contexto isolado foi substituído por `master-auditor` (Claude Opus 4.6 + GPT-5 via Codex CLI em trilhas paralelas). Reduz viés single-model e elimina necessidade de PM invocar em sessão manual.

**Execução (automatizada pelo orquestrador):**
1. Orquestrador invoca `master-auditor` com `artifact_type: plan`, path deste plano.
2. Master-auditor roda 2 trilhas independentes:
   - Trilha Claude (Opus 4.6, sessão isolada)
   - Trilha GPT-5 (via `codex-cli` MCP, reasoning_effort: high)
3. Consolidação:
   - Ambos approved → prossegue para Fase 1 automaticamente
   - Ambos rejected → orquestrador invoca `fixer` com findings; ciclo até convergir
   - Divergência → reconciliação (até 3 rodadas); se não converge → **ESCALA AO PM**
4. Output: `docs/audits/master/harness-local-upgrade-master-audit.json`
5. **Sem approved, nenhuma fase subsequente executa.** Mas este é o único ponto onde PM é chamado se houver divergência.

---

### Fase 1 — Correções críticas em arquivos selados (ação do PM)

**Responsável:** PM (agente não executa).
**Pré-requisito:** Fase 0.1 e 0.4 completas + PM em terminal externo com `KALIB_RELOCK_AUTHORIZED=1`.
**Arquivo de incidente obrigatório:** `docs/incidents/harness-relock-<timestamp>.md` (criado automaticamente por `scripts/relock-harness.sh`).

#### 1.1 Trocar paths absolutos por `${CLAUDE_PROJECT_DIR}`

Auditar `.claude/settings.json` buscando `/c/PROJETOS/saas/kalibrium-v2/`:

```bash
grep -n "/c/PROJETOS" .claude/settings.json
```

Substituir cada ocorrência por `${CLAUDE_PROJECT_DIR}`, **preservando o prefixo `bash`** do comando original. Exemplo corrigido:

```diff
- "command": "bash /c/PROJETOS/saas/kalibrium-v2/scripts/hooks/stop-gate.sh"
+ "command": "bash \"${CLAUDE_PROJECT_DIR}/scripts/hooks/stop-gate.sh\""
```

**Nota pós-revisão v2→v3:** exemplo da v2 removia `bash` inadvertidamente (erro de copy-paste). Aspas duplas ao redor da variável interpolada protegem contra paths com espaços.

**Validação pós-mudança:**
- SessionStart limpo em sessão nova
- Hook Stop dispara corretamente (`scripts/smoke-test-hooks.sh` inclui caso?)
- Se smoke-test não cobre Stop hook em outro path, abrir caso novo antes do relock

**Justificativa do relock (arquivo de incidente):**
> Correção de paths hardcoded Windows-específicos em Stop hook. Portabilidade entre máquinas do PM (desktop/notebook) e futuros contribuidores. Fortalecimento de enforcement — sem mudança semântica.

#### 1.2 Adicionar deny rules Git

Em `.claude/settings.json` → `permissions.deny`, adicionar:

```json
"Bash(git checkout -- *)",
"Bash(git checkout .)",
"Bash(git restore .)",
"Bash(git restore -- *)",
"Bash(git stash drop*)",
"Bash(git stash clear)"
```

**Nota sobre escopo das deny rules de `git restore`:**
- **Incluído:** `git restore .` e `git restore -- <path>` — formas destrutivas explícitas que descartam working tree.
- **Intencionalmente NÃO incluído:** `git restore --staged <path>` — operação **não-destrutiva**, equivale a `git reset HEAD`, só move staged → unstaged, não toca working tree. Incluí-la geraria fricção sem ganho de segurança.
- Claude Code usa glob matching shell-like; `Bash(git restore *)` amplo bloquearia `--staged` também, por isso a lista usa formas específicas com `.` e `--`.
- Se na prática o agente tentar `git restore <file>` sem `--` nem `--staged` (forma ambígua mas destrutiva no Git moderno), adicionar regra específica em iteração futura.

**Validação pós-mudança:**
- Sessão nova — agente tenta `git checkout -- file.php` → negado
- Agente tenta `git restore --staged file.php` → **permitido** (não-destrutivo)
- Agente tenta `git checkout -f main` → negado
- Agente tenta `git stash drop` → negado
- Smoke test caso novo em `scripts/smoke-test-hooks.sh` (opcional)

**Nota de precedência deny vs allow (correção v2→v3):** settings.json atual tem `Bash(git checkout*)` e `Bash(git stash*)` em allow. A semântica do Claude Code é **deny vence allow** quando ambos matcham, mas vale confirmar empiricamente antes de declarar pass:
- Em PoC (pode piggy-backar na Fase 0.1), testar que uma regra deny específica (ex: `Bash(git checkout .)`) vence a regra allow ampla (`Bash(git checkout*)`) em uma sessão real.
- Se deny **não** vencer allow, alternativa é **restringir** o allow (remover `git checkout*` e listar apenas os verbos seguros: `git checkout -b *`, `git checkout main`, etc.).

**Justificativa do relock (arquivo de incidente):**
> Fortalecimento da deny list com operações que descartam changes não-commitadas. Coerente com regras já existentes (rm -rf, reset --hard, push --force). `docs/governance/harness-evolution.md §5` permite fortalecimento com justificativa leve.

---

### Fase 2 — Arquivos não-selados (ação do agente via PR)

**Responsável:** Claude Code (sub-agent fixer + verifier).
**Pré-requisito:** Fase 1 completa.
**Branch:** `chore/harness-local-upgrade`.

#### 2.1 Devcontainer + docker-compose

**Pré-requisito:** Fase 0.3 (versões confirmadas contra ADR-0001).

**Versões alinhadas com ADR-0001 + CI (correção v2→v3):**
- **PHP:** 8.4 (confirmado em composer.json + ADR)
- **PostgreSQL:** 18 (confirmado em .env.example)
- **Redis:** 8 — **correção v2→v3**: v2 propunha redis:7, mas ADR-0001 e `.github/workflows/ci.yml` especificam Redis 8
- **Node:** 20 (LTS atual do CI) com faixa `^20.19.0 || >=22.12.0` exigida pelo Vite em `package-lock.json` — **correção v2→v3**: v2 propunha Node 22, mas CI roda 20 e package-lock não exige 22

Se Fase 0.3 revelar divergência adicional, alinhar este item antes de executar.

**Novos arquivos:**
- `.devcontainer/devcontainer.json` — image custom com PHP 8.4, Composer, Node 20, extensões VS Code
- `.devcontainer/Dockerfile` — base oficial `mcr.microsoft.com/devcontainers/php:8.4`
- `docker-compose.yml` (dev-only, na raiz) — serviços: app, postgres:18, redis:8
- `.devcontainer/postCreate.sh` — roda `composer setup` + `npm ci` + `npx lefthook install`
- `.dockerignore` — excluir vendor, node_modules, storage/logs

**Validação:**
- `devcontainer up --workspace-folder .` sobe sem erro
- `composer test` roda dentro do container
- Testes slice-003 até 009 passam
- Versões em `docker-compose.yml` batem com `.github/workflows/ci.yml` (comparação textual)

**Arquivos modificados:**
- `.env.example` → adicionar comentário indicando hosts do compose (`DB_HOST=postgres`, `REDIS_HOST=redis`)
- `README.md` → seção "Setup via devcontainer" com 1 comando

#### 2.2 Lefthook para pre-commit local

**Novo arquivo:** `lefthook.yml`

```yaml
pre-commit:
  parallel: true
  commands:
    pint:
      glob: "*.php"
      run: vendor/bin/pint --test {staged_files}
    phpstan:
      glob: "*.php"
      run: vendor/bin/phpstan analyse --no-progress {staged_files}
    pest-related:
      glob: "*.php"
      run: bash scripts/lefthook/pest-related.sh {staged_files}
pre-push:
  commands:
    full-fast:
      run: composer test -- --testsuite=Unit,Feature
```

**Novo arquivo auxiliar:** `scripts/lefthook/pest-related.sh`

Motivação: o comando inline original usava `grep -oP` (GNU-only, falha em macOS BSD) e `paste -sd` (não disponível em todos shells). Além disso, se nenhum `.php` fosse staged, `pest --filter=` ficaria vazio e quebraria.

Requisitos do script:
- **Cross-platform:** roda em Linux, macOS (BSD tools), e Windows/Git Bash.
- **Guard de vazio:** se não há arquivos PHP staged → exit 0 (skip limpo).
- **Extração de nome de classe/arquivo:** via `sed`/`awk` POSIX, sem `grep -P`.
- **Limite:** máximo 3 filtros (heurística de relacionados, não suite completa).
- **Fallback:** se extração falhar, rodar `pest --testsuite=Unit` curto em vez de filter vazio.

Esqueleto de validação obrigatório:
```bash
#!/usr/bin/env bash
set -euo pipefail
staged_php=("$@")
[ ${#staged_php[@]} -eq 0 ] && exit 0
# extrair basenames sem .php, até 3, unir com |
filter=$(printf '%s\n' "${staged_php[@]}" \
  | sed -n 's|.*/||; s|\.php$||; p' \
  | head -3 \
  | tr '\n' '|' \
  | sed 's/|$//')
[ -z "$filter" ] && exec vendor/bin/pest --testsuite=Unit
exec vendor/bin/pest --filter="$filter"
```

**Arquivos modificados (correção v2→v3 — Lefthook NÃO é pacote PHP):**
- `package.json` → adicionar `"lefthook": "^1.12"` em `devDependencies` (Lefthook distribui via npm e auto-instala hooks no postinstall)
- `package.json` → script `"prepare": "lefthook install"` para garantir hooks em `npm install` fresh
- `.devcontainer/postCreate.sh` → chamada `npx lefthook install` idempotente
- `README.md` → seção "Pre-commit hooks" documentando instalação fora do devcontainer (`brew install lefthook` / `scoop install lefthook`)

**Nota de enforcement (correção v2→v3):** Lefthook é **conveniência local**, não enforcement forte. `git commit -n` (ou `--no-verify`) bypassa todos os hooks. O enforcement estrutural continua sendo:
1. `scripts/hooks/pre-commit-gate.sh` (Claude Code, PreToolUse)
2. `.github/workflows/ci.yml` (servidor, impossível bypassar)

Lefthook reduz ciclo de feedback local em ~2-5 min — vale a pena mesmo sendo bypassável. O plano **não** afirma que Lefthook substitui CI gates.

**Validação:**
- `git commit` de arquivo com lint error → bloqueia
- `git commit -n` continua funcionando (documentado como aceito)
- `lefthook run pre-commit` manualmente roda OK
- **Teste explícito do `pest-related.sh` em Windows/Git Bash do PM antes do merge**, cobrindo: (a) commit só `.md`, (b) commit 1 `.php`, (c) commit 5+ `.php` (verifica limite 3), (d) commit `.php` em path com espaços.

#### 2.3 Taskfile.yml

**Princípio (correção v2→v3):** Taskfile **não duplica lógica**, só orquestra `composer scripts` e `scripts/test-scope.php` existentes. Source-of-truth continua sendo `composer.json` + `scripts/test-scope.php`. Isso evita drift entre os dois sistemas e respeita a arquitetura atual de escopos de teste.

**Novo arquivo:** `Taskfile.yml`

Tasks propostas (todas chamam comandos já existentes no repo):
- `task setup` → `composer setup`
- `task dev` → `composer dev`
- `task test` → `composer test` (equivalente a `php scripts/test-scope.php all`)
- `task test:fast` → `composer test:fast` (já existe em `composer.json`)
- `task test:slice -- SLICE=003` → `composer test:slice -- 003` (passa para `test-scope.php slice 003`, que resolve para suite real `Slice003`)
- `task lint` → `composer lint` ou `vendor/bin/pint --test` (conforme nome real do script)
- `task lint:fix` → `vendor/bin/pint`
- `task types` → `vendor/bin/phpstan analyse`
- `task ci` → `task lint && task types && task test` (simulação local do pipeline CI)
- `task harness:check` → `bash scripts/guide-check.sh`
- `task harness:smoke` → `bash scripts/smoke-test-hooks.sh`
- `task test:watch` → ver Fase 2.5 (sujeito ao resultado do spike)

**Nota de nomenclatura (correção v2→v3):** v2 usava `--testsuite=Slice-$SLICE` — errado. Suites reais em `phpunit.xml` são `Slice003`, `Slice004`, etc. (sem hífen). Taskfile delega para `test-scope.php` que já lida com a tradução corretamente.

**Validação:**
- Cada task roda isoladamente sem erro
- `task --list` mostra todas com descrição
- Composer scripts continuam funcionando (coexistência)
- `task test:slice -- 003` → resultado idêntico a `composer test:slice -- 003`
- Nenhum suite name hardcoded — tudo via test-scope.php

#### 2.4 Coverage gating em CI

**Pré-requisito:** Fase 0.2 (baseline de coverage atual medido e registrado).

**Arquivos modificados:**
- `phpunit.xml` → adicionar `<coverage>` com `includeUncoveredFiles` e path `coverage-report/`
- `.github/workflows/ci.yml` → job `php-test`:
  - Rodar `vendor/bin/pest --coverage --min=<threshold>` onde `<threshold>` vem da Fase 0.2
  - Upload artifact `coverage-html/`
- `composer.json` scripts:
  - `"test:coverage": "pest --coverage --min=<threshold>"`

**Decisão de threshold (definida por Fase 0.2, não hardcoded):**
- Atual ≥ 70% → gate em 70%.
- Atual 50-69% → gate em (atual − 5), escalação trimestral para 70%.
- Atual < 50% → gate no valor atual, plano separado de recuperação de cobertura.

**Correção v2→v3:** v2 deixou "70%" hardcoded em alguns pontos apesar de declarar threshold dinâmico. Na execução desta fase, o agente **lê** o percentual registrado em `docs/reports/coverage-baseline-2026-04-14.md` (output da Fase 0.2) e substitui `<threshold>` pelo valor resultante. Nenhum número aparece no plano-como-código — apenas a tabela de decisão acima.

Registrar decisão em retrospectiva trimestral (`docs/governance/harness-evolution.md §1`).

**Validação adicional — harness-integrity-job:**

Este item modifica `.github/workflows/ci.yml`, que contém `harness-integrity-job` (hash do MANIFEST + settings.sha256). Obrigatório:

1. Push da alteração em branch `chore/harness-local-upgrade-coverage` separada.
2. CI roda com job de integridade → verificar verde **antes** de merge.
3. Se integridade quebrar, investigar: a modificação no ci.yml não deve afetar hashes de `scripts/hooks/` nem `.claude/settings.json`. Se estiver afetando, há drift que precisa ser tratado via relock.

**Validação:**
- `composer test:coverage` local gera relatório
- CI falha se coverage cair abaixo do threshold
- Artifact de coverage aparece na aba Actions do GitHub
- `harness-integrity-job` continua verde

#### 2.5 Pest watch mode — **REBAIXADO PARA SPIKE** (correção v2→v3)

**Motivação da mudança:** v2 propunha `spatie/pest-plugin-watch`, mas `composer show` retornou inexistente. `pestphp/pest-plugin-watch` está **abandoned** no Packagist. `petecoop/pest-plugin-watch` existe mas é fork não-oficial de manutenção incerta. Tratar como spike, não entrega garantida.

**Escopo do spike:**
1. Avaliar `petecoop/pest-plugin-watch` — estado de manutenção, last release, issues abertos.
2. Avaliar alternativas não-Pest:
   - `nodemon --exec "composer test:fast"` (requer Node, projeto já tem)
   - `entr` (Unix-only) + `ls app/**/*.php | entr composer test:fast`
   - `chokidar-cli` via npm (cross-platform)
3. Testar a opção vencedora em dev local por 1 semana.
4. Se estável → promover a entrega (`composer test:watch` + `task test:watch` + docs).
5. Se instável → documentar decisão de não adotar em `docs/reports/test-watch-spike-2026-04-XX.md` e fechar.

**Saída do spike:** decisão documentada, não comprometimento prévio. Spike não bloqueia demais fases.

**Remove de acceptance criteria:** AC-7 (watch mode) passa de "obrigatório" para "opcional pendente de spike". Se spike fechar negativo, AC-7 sai do plano via retrospectiva.

---

### Fase 3 — Documentação e onboarding

**Responsável:** Claude Code.

#### 3.1 Atualizar README

Adicionar seções:
- "Setup 1-comando com devcontainer"
- "Quickstart para contribuidores" (5 linhas)
- "Quality gates locais" (referência ao lefthook)

#### 3.2 Estender `/guide-check` OU criar `/harness-check-local` — decisão pendente

**Correção pós-revisão:** antes de criar skill nova, avaliar overlap com `/guide-check` e `/context-check` existentes.

**Decisão a tomar durante Fase 3:**

| Opção | Critério |
|---|---|
| **A — Estender `/guide-check`** | Se `guide-check.sh` já roda CHECK-1..N e podemos adicionar CHECK-M (local env sanity) sem inflar escopo. Preferida se o script tem arquitetura modular. |
| **B — Criar `/harness-check-local`** | Se `/guide-check` é focado em governança/integridade e misturar verificação de devcontainer/lefthook polui o escopo. |

**Checklist antes da criação (se opção B):**
- Ler `scripts/guide-check.sh` e `.claude/skills/*check*.md` existentes.
- Documentar overlap evitado.
- Justificar escopo único no frontmatter da skill.

**Verificações da skill nova/estendida (comum às duas opções):**
- Devcontainer disponível (`docker` no PATH + `.devcontainer/devcontainer.json` presente)
- Lefthook instalado (`lefthook version` roda, `.git/hooks/pre-commit` existe)
- Taskfile disponível (`task --version` roda)
- Paths portáveis em `.claude/settings.json` (nenhuma ocorrência de `/c/PROJETOS`, `/home/`, `/Users/`)

**Status do enforcement:** skill é operacional (não altera P/R), então dispensa ADR por `docs/governance/harness-evolution.md §4`. Mas criação exige PM aprovando naming + escopo + justificativa de overlap.

#### 3.3 Atualizar `docs/harness-limitations.md`

Adicionar item novo: "Paths de máquina do desenvolvedor em arquivos selados" — com referência ao relock de 2026-04-14 como incidente resolvido.

---

## 5. Mapeamento AC → arquivos → teste

| AC (critério "feito") | Arquivos tocados | Como validar |
|---|---|---|
| AC-1: Paths absolutos eliminados | `.claude/settings.json` | `grep -r "/c/PROJETOS" .claude/` sem match |
| AC-2: Deny list ampliada | `.claude/settings.json` | Sessão nova: `git checkout -- file` negado, `git restore --staged file` permitido |
| AC-3: Devcontainer funcional | `.devcontainer/*`, `docker-compose.yml` | `devcontainer up && composer test` verde |
| AC-4: Pre-commit local ativo | `lefthook.yml`, `scripts/lefthook/pest-related.sh`, `composer.json` | `git commit` com lint error bloqueia; commits `.md` puro passam sem rodar pest |
| AC-5: Tasks funcionais | `Taskfile.yml` | `task test` e `task ci` rodam |
| AC-6: Coverage gated | `phpunit.xml`, `ci.yml` | CI falha com coverage < threshold dinâmico (Fase 0.2) |
| AC-7: Watch mode *(opcional, sujeito a spike Fase 2.5)* | `composer.json` ou `package.json` | `composer test:watch` ou `task test:watch` dispara rerun — aceitável ficar em `skip` se spike fechar negativo |
| AC-8: Docs atualizadas | `README.md`, `docs/harness-limitations.md` | `/guide-check` ou `/harness-check-local` sem warning |
| AC-9: Harness-integrity-job verde | `.github/workflows/ci.yml` | Job de integridade continua passando após merge |
| AC-10: PoC Stop hook documentada | `docs/incidents/poc-claude-project-dir-stop-hook.md` | Arquivo existe com resultado verde da interpolação |

### 5.1 Verification JSON obrigatório pós-execução

Ao término da Fase 2 (antes do merge final da PR), agente roda `guide-auditor` em contexto isolado com os 10 ACs como input e gera:

**Arquivo:** `docs/audits/progress/harness-local-upgrade.verification.json`

Formato:
```json
{
  "plan": "harness-local-upgrade-action-plan",
  "date": "YYYY-MM-DD",
  "auditor_session_id": "...",
  "acceptance_criteria": [
    {"id": "AC-1", "status": "pass|fail|skip", "evidence": "...", "artifact": "..."},
    ...
  ],
  "overall": "pass|fail",
  "notes": "..."
}
```

**Regra:** merge da PR só acontece com `overall: "pass"`. Qualquer AC em `fail` ou `skip` sem justificativa em `notes` bloqueia merge.

---

## 6. Riscos e mitigações

| Risco | Mitigação |
|---|---|
| Relock da Fase 1 quebra SessionStart em outras máquinas | Smoke test completo antes do relock + rollback plan (git revert + relock novo) |
| Devcontainer em Windows sem Docker Desktop instalado | PR inclui fallback documentado: `composer dev` continua funcionando sem container |
| Lefthook falha em Windows sem Git Bash | Lefthook tem binário Windows nativo; testar em PR antes de merge |
| Coverage threshold 70% falha em slice novo sem testes suficientes | Baseline conservador + escalação gradual por trimestre |
| `pest --watch` consome muito CPU em dev local | Opt-in: só roda quando `task test:watch` é chamado explicitamente |
| Taskfile duplica composer scripts (drift) | Taskfile chama composer scripts internamente (source única de verdade = composer.json) |
| PR muito grande | Dividir em 3 PRs: (a) devcontainer, (b) lefthook+taskfile, (c) coverage+watch |

---

## 7. Dependências e ordem de execução

**Correção pós-revisão:** a versão anterior serializava toda Fase 2 após Fase 1. Incorreto — apenas Fase 2.4 (coverage em CI) depende de harness estável. Fase 2.1/2.2/2.3/2.5 podem rodar em paralelo com Fase 1.

```
Fase 0 (pré-requisitos)
   │
   ├─ 0.1 PoC ${CLAUDE_PROJECT_DIR} ───┐
   ├─ 0.2 baseline coverage ───────────┤
   ├─ 0.3 versões vs ADR ──────────────┤
   └─ 0.4 plan-reviewer isolado ───────┤
                                       ▼
                       aprovação do PM (decisão assinada)
                                       │
              ┌────────────────────────┼─────────────────────────┐
              ▼                        ▼                         ▼
     Fase 1 (PM relock)       Fase 2a (agente PR paralelo)   Fase 3 (docs)
        ├─ 1.1 paths            ├─ 2.1 devcontainer           ├─ 3.1 README
        └─ 1.2 deny list        ├─ 2.2 lefthook               ├─ 3.2 skill (ver nota)
              │                 ├─ 2.3 taskfile               └─ 3.3 harness-limitations
              ▼                 └─ 2.5 watch mode                     │
        smoke-test PASS                │                              │
              │                        ▼                              │
              └──────────► Fase 2b (serial, depende Fase 1) ◄─────────┘
                              └─ 2.4 coverage gating CI
                                       │
                                       ▼
                          verification.json + PR final
```

**Regras de dependência:**
- Fase 0.1, 0.2, 0.3, 0.4 rodam em paralelo — nenhuma depende das outras.
- **Nenhuma fase executa sem Fase 0 completa + decisão PM assinada.**
- Fase 1 e Fase 2a são independentes — podem abrir PRs separadas em paralelo.
- Fase 2b (coverage CI) depende de Fase 1 estável (settings.json pós-relock) + Fase 0.2 (threshold).
- Fase 3 pode rodar em paralelo com Fase 2a, mas `3.3` (update harness-limitations) só finaliza após Fase 1 (registra o relock ocorrido).

---

## 8. Estimativa de esforço

| Fase | Quem | Tempo estimado |
|---|---|---|
| Fase 0.1 — PoC `${CLAUDE_PROJECT_DIR}` | Agente + PM (relock curto) | 15 min agente + 15 min PM |
| Fase 0.2 — baseline coverage | Agente | 10 min |
| Fase 0.3 — versões vs ADR | Agente | 5 min |
| Fase 0.4 — plan-reviewer isolado | PM invoca, agente roda | 20 min |
| Fase 1.1 — paths | PM (relock + smoke-test + 2 máquinas) | 45 min |
| Fase 1.2 — deny list | PM (no mesmo relock de 1.1) | 15 min |
| Fase 2.1 — devcontainer | Agente | 2-3h |
| Fase 2.2 — lefthook + pest-related.sh | Agente + PM (teste Windows) | 1h agente + 15 min PM |
| Fase 2.3 — taskfile | Agente | 1h |
| Fase 2.4 — coverage | Agente | 1h |
| Fase 2.5 — watch | Agente | 30 min |
| Fase 3 — docs + skill | Agente | 1h |
| **Total** | | **~9h de trabalho agente + ~1h45 de PM** |

**Correção pós-revisão:** estimativa de "30 min de PM" na v1 subestimava. Realista é ~1h45, incluindo: leitura do plano (15 min), assinatura de decisão (10 min), Fase 0.1 relock (15 min), Fase 0.4 invocação (5 min), Fase 1 relock + smoke-test em 2 máquinas (45 min), teste de lefthook em Windows (15 min).

---

## 9. Fora de escopo deste plano

- **Alteração de sub-agent ou skill existentes** — nenhum é tocado.
- **Alteração de hooks** (`scripts/hooks/*.sh`) — nenhum é criado/alterado. Só `.claude/settings.json` é editado (via relock).
- **Nova regra R-NN** — não há. Fortalecimento da deny list é extensão de política existente.
- **Revogação de regra** — não há.
- **Mudança em CI que afete harness-integrity-job** — job continua rodando no pipeline intocado.
- **Migração para monorepo ou separação frontend/backend** — estrutura monolítica atual é mantida.
- **Mudança de ORM, framework ou banco** — Laravel 13 + Pest 4 + PostgreSQL 18 mantidos.
- **Upgrade Claude Opus/Sonnet** — decisão de modelo é do PM, não deste plano.

---

## 10. Critérios de aprovação (PM — versão v4 pós-ADR-0012)

Com adoção da ADR-0012 (autonomia + dual-LLM), a interação do PM é reduzida a **3 cliques**:

1. **Clicar em `relock.bat`** na raiz do projeto → digitar RELOCK → hashes atualizados.
   - Isso sela os novos arquivos (agents, ADR, configuração de MCP para Codex).
2. **Clicar no commit do ADR-0012** e digitar `Aprovado` em `docs/decisions/pm-decision-adr-0002-2026-04-14.md` via interface web do GitHub (botão verde "Commit changes").
   - Agente prepara o arquivo, PM só confirma.
3. **Aprovar PR final de Fase 2** no GitHub UI quando o orquestrador abrir (botão "Merge").

Todas as etapas técnicas entre esses 3 cliques são automáticas:
- Agente cria arquivos não-selados
- Master-auditor aprova plan + PR
- CI valida
- Orquestrador invoca fixer se necessário, master-auditor re-audita

PM é chamado fora desses 3 cliques apenas em **bloqueio real** (E5 da ADR-0012).

O plano pode ser rejeitado total ou parcialmente. Se parcial, PM indica quais itens aprovar (ex: "aprovo tudo menos devcontainer — mantém setup manual por ora").

---

## 11. Plano de rollback

Se após execução o ambiente piorar:

- **Fase 1:** `git revert` + novo relock com settings.json anterior.
- **Fase 2.1 (devcontainer):** `rm -rf .devcontainer/ docker-compose.yml` — zero impacto no dev com setup manual.
- **Fase 2.2 (lefthook):** `lefthook uninstall && rm lefthook.yml` + remover do composer.json.
- **Fase 2.3 (taskfile):** `rm Taskfile.yml` — composer scripts continuam funcionando.
- **Fase 2.4 (coverage):** git revert do phpunit.xml + ci.yml.
- **Fase 2.5 (watch):** remover script do composer.json.

Cada rollback registrado em `docs/retrospectives/harness-local-upgrade-YYYY-MM-DD.md` com motivo.

---

## 12. Próximos passos (ação requerida do PM)

1. **Ler este documento.**
2. **Aprovar ou rejeitar** com commit em `docs/decisions/pm-decision-harness-local-upgrade-2026-04-14.md` (seguindo padrão de `docs/decisions/pm-decision-*`).
3. **Se aprovado:** agendar Fase 1 e notificar agente para iniciar Fase 2 em branch dedicada.

---

## 13. Changelog da revisão externa (2026-04-14)

Após redação inicial (draft-v1), plano foi submetido a LLM independente para revisão. Parecer: aprovação condicional a 3 correções técnicas + 4 melhorias de processo. Todos os pontos foram avaliados e incorporados nesta versão (draft-v2).

### Correções técnicas aceitas

| ID | Ponto | Ação tomada | Localização |
|---|---|---|---|
| R-T1 | `${CLAUDE_PROJECT_DIR}` em Stop hook não validado | Criada Fase 0.1 — PoC obrigatória antes do relock | §4 Fase 0.1 |
| R-T2 | `git restore --staged` é não-destrutivo, não deve estar na deny list | Removido da lista; rationale explícita adicionada | §4 Fase 1.2 |
| R-T3 | `pest-related` no lefthook usa `grep -oP` (GNU-only) e falha com 0 arquivos PHP | Extraído para `scripts/lefthook/pest-related.sh` com guards POSIX + teste explícito em Windows | §4 Fase 2.2 |

### Melhorias de processo aceitas

| ID | Ponto | Ação tomada | Localização |
|---|---|---|---|
| G-1 | Sem gate de verificação pós-execução | Adicionado `verification.json` obrigatório + ACs 9-10 | §5.1 |
| G-2 | R11 (dual-verifier) não aplicada ao próprio plano | Criada Fase 0.4 — plan-reviewer em contexto isolado | §4 Fase 0.4 |
| G-3 | Interação com harness-integrity-job do CI não tratada | Adicionada validação obrigatória em branch separada antes do merge (Fase 2.4) | §4 Fase 2.4 |
| G-4 | Fase 2 estava serializada após Fase 1 sem necessidade | Diagrama reescrito: 2a (paralelo) e 2b (serial, só coverage depende) | §7 |

### Pontos menores aceitos

- **Baseline de coverage antes de gating** — Fase 0.2 criada; threshold dinâmico. §4 Fase 0.2 + §4 Fase 2.4.
- **Confirmar versões contra ADR-0001** — Fase 0.3 criada. §4 Fase 0.3.
- **Estimativa PM subestimada** — corrigida de 30 min para 1h45. §8.
- **Overlap `/harness-check-local` vs `/guide-check`** — decisão estender vs criar adicionada. §4 Fase 3.2.

### Não aceitos / deferidos

- Nenhum ponto da revisão foi rejeitado. Naming `docker-compose.yml` vs `compose.yml` permanece `docker-compose.yml` na v2 por compatibilidade com base instalada de Docker Compose legacy; pode ser revisto em iteração futura sem impacto.

### Próxima revisão esperada (após v3)

`plan-reviewer` em contexto isolado (Fase 0.4), apontando para esta v3. Output em `docs/audits/internal/harness-local-upgrade-plan-review-2026-04-14.md`.

---

## 14. Changelog rodada 2 — v2 → v3 (2026-04-14, tarde)

LLM independente (segunda rodada) identificou 6 ressalvas bloqueantes + 3 ajustes + 1 observação de governança. Todos incorporados.

### Ressalvas bloqueantes aceitas

| ID | Ponto | Ação tomada | Localização |
|---|---|---|---|
| B-1 | PoC da Fase 0.1 criava `scripts/hooks/poc-stop-test.sh` em path selado → auto-derrota | Trocado para inline em settings.json (Opção A) ou `scripts/poc/` não-selado (Opção B) | §4 Fase 0.1 |
| B-2 | Exemplo de substituição do Stop hook removia `bash` prefixo | Restaurado `bash` + aspas em volta da variável interpolada | §4 Fase 1.1 |
| B-3 | `lefthook/lefthook` não existe no Packagist (contradiz a própria D4) | Movido para npm (`package.json` devDependencies) + OS-level no devcontainer | §3 D4, §4 Fase 2.2 |
| B-4 | Redis 7 no compose (ADR-0001 e CI dizem Redis 8); Node 22 (CI usa Node 20) | Alinhado: Redis 8, Node 20 | §4 Fase 2.1 |
| B-5 | Coverage 70% hardcoded em Fase 2.4 apesar de Fase 0.2 ser dinâmica | Substituído por `<threshold>` lido de `docs/reports/coverage-baseline-*.md` | §4 Fase 2.4 |
| B-6 | `spatie/pest-plugin-watch` não existe; `pestphp/pest-plugin-watch` abandoned | Fase 2.5 rebaixada a spike; AC-7 marcado opcional | §4 Fase 2.5, §5 |

### Ajustes aceitos

| ID | Ponto | Ação tomada | Localização |
|---|---|---|---|
| A-1 | Taskfile duplicava lógica de `test-scope.php`; usava nomes errados (`Slice-003` vs `Slice003`) | Taskfile delega para composer scripts + test-scope.php, zero duplicação | §4 Fase 2.3 |
| A-2 | Deny vs allow precedence não validada (settings.json tem allow amplo em `git checkout*`) | Adicionada validação empírica piggy-backada na Fase 0.1 | §4 Fase 1.2 |
| A-3 | Lefthook apresentado como enforcement forte | Reclassificado como conveniência local; enforcement continua em Claude gate + CI | §4 Fase 2.2 |

### Observação de governança

| ID | Ponto | Ação tomada | Localização |
|---|---|---|---|
| O-1 | Arquivo do plano estava `untracked` no git | Adicionado como item 0 dos critérios de aprovação (precisa `git add` antes de decisão formal) | §10 |

### Não aceitos / deferidos

- Nenhum ponto da rodada 2 foi rejeitado. A revisão foi factualmente sólida e corrigiu múltiplos erros da v2.

### Próxima revisão esperada (após v3)

~~`plan-reviewer` em contexto isolado~~ → substituído por `master-auditor` dual-LLM conforme ADR-0012. Ver §15.

---

## 15. Changelog v3 → v4 (2026-04-14, noite)

PM declarou que **não opera terminal nem GitHub diretamente**. Plano-v3 assumia PM técnico. Reestruturação completa do modelo de governança via ADR-0012.

### Mudanças estruturais

| ID | Mudança | Motivação | Artefato |
|---|---|---|---|
| V4-1 | ADR-0012 emenda constitution §3.1 (PM não-técnico) + R11 (dual-verifier agora é dual-LLM) | PM não opera terminal | `docs/adr/0002-constitution-amendment-autonomy-dual-llm.md` |
| V4-2 | Criado agent `master-auditor` (dual-LLM Claude + GPT-5 via Codex CLI) | Substitui R11 humano em operação cotidiana | `.claude/agents/master-auditor.md` |
| V4-3 | Criado agent `epic-retrospective` com loop corretivo 10x | Automatiza auditoria de fim de épico | `.claude/agents/epic-retrospective.md` |
| V4-4 | Criado agent `harness-learner` com auto-aplicação limitada | Aprende com findings, melhora harness automaticamente | `.claude/agents/harness-learner.md` |
| V4-5 | Criado `relock.bat` clicável na raiz | Operação de relock sem terminal pelo PM | `relock.bat` |
| V4-6 | Fase 0.4 reescrita: master-auditor substitui plan-reviewer manual | Coerência com ADR-0012 | §4 Fase 0.4 |
| V4-7 | §10 reescrita: PM reduzido a 3 cliques | Alinhado com modelo não-técnico | §10 |

### Guardrails do harness-learner (E4 da ADR-0012)

Auto-aplicação proibida para operações que afetem constitution §1-§4, revoguem regra existente, afrouxem enforcement, ou removam hook. Limite de 3 mudanças auto por invocação. Todas registradas em `docs/incidents/auto-learn-*.md`.

### Novo fluxo operacional (diagrama)

```
[ PM clica relock.bat + aprova ADR-0012 + autoriza PR ]
                     │
                     ▼
          orquestrador (Claude Code)
                     │
       ┌─────────────┼─────────────┐
       ▼             ▼             ▼
  master-auditor  fixer      epic-retrospective
  (dual-LLM)                 (loop 10x)
       │             │             │
       │             │             ▼
       │             │        harness-learner
       │             │        (auto-aplica até 3)
       │             │             │
       └─────────────┴─────────────┘
                     │
                     ▼
         Bloqueio real OU fim de épico?
                     │
            ┌────────┴────────┐
            ▼                 ▼
         PM chamado       Ciclo continua
         (3 cliques)      autonomamente
```

### Pontos onde PM é chamado (E5 da ADR-0012)

| Situação | Como | Frequência esperada |
|---|---|---|
| Aprovação inicial deste plano + ADR-0012 | 3 cliques (relock.bat + GitHub UI 2x) | 1x (agora) |
| Dual-LLM divergente após 3 rodadas | Leitura de escalation report | Raro |
| Harness-learner propõe mudança proibida de auto | Aprovação via GitHub UI | ~1x por épico |
| Fim de épico | Leitura de relatório em linguagem de produto | 1x por épico |
| Incidente P0/P1 | Notificação urgente | Muito raro |
| LLM falha 3x consecutivas | Notificação | Raro |

**Estimativa:** 3 cliques agora + ~30min/semana de leitura de relatórios + intervenção pontual em bloqueios.

### Custo de tokens

Dual-LLM dobra custo de auditoria. PM declarou "sem teto". Monitoramento em `.claude/telemetry/master-auditor.jsonl` com breakdown por artefato auditado. Alerta visual se custo mensal > 10x média histórica.

### Não aceitos / diferidos na v4

- **Substituir Codex CLI por SDK direto OpenAI:** adiado; Codex CLI é suficiente e PM tem assinatura
- **Agente automatiza relock.bat:** proibido. Relock continua requerendo clique do PM para mudanças que exigem decisão humana (constitution §1-§4, revogações). Apenas harness-learner tem modo auto-relock programático via `KALIB_AUTO_LEARN_AUTHORIZED=1` (flag separada, audit-trail obrigatório).

### Pendências operacionais pós-aprovação

1. **Configurar MCP do Codex CLI** em `.mcp.json` ou equivalente para que `master-auditor` possa invocar GPT-5.
2. **Adicionar flag `KALIB_AUTO_LEARN_AUTHORIZED=1`** em `scripts/relock-harness.sh` para modo não-interativo do harness-learner (via PR do agente, não manual).
3. **Atualizar `scripts/guide-check.sh`** com check para verificar que mudanças auto-aplicadas respeitaram guardrails E4 (auditoria periódica).
4. **Criar `.claude/telemetry/master-auditor.jsonl`** e `harness-learner.jsonl` como arquivos monitorados.
5. **Atualizar `docs/constitution.md`** com referência à ADR-0012 e emendas E1-E4 (via relock).

### Próxima revisão

Após PM aprovar ADR-0012 + relockar, o próprio `master-auditor` roda sobre este plano-v4 como primeiro ato do novo regime. Se passar → execução autônoma começa.

---

**Fim do plano.**
