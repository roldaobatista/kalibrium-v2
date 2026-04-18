---
slice: "019"
title: "Harness — Hook git nativo + paths filter tenant atualizado"
lane: L2
story: B-042+B-043
spec: specs/019/spec.md
spec_audit: specs/019/spec-audit.json (approved, zero findings)
author: architecture-expert (plan)
created: 2026-04-18
depends_on: [slice-018 merged]
---

# Plan — Slice 019

## 1. Contexto técnico

O slice-019 endereça dois pontos frágeis reais do harness identificados na análise 2026-04-18 pós-merge do slice-018:

1. **B-042 / AC-001..AC-004** — Pre-push protection atualmente só cobre quem orquestra via Claude Code (hook `PreToolUse Bash(git push*)`). Qualquer pusher externo (PM via `.bat`, `workflow_dispatch`, outra sessão) escapa. Precisa hook git **nativo** (`.git/hooks/pre-push`) para cobertura universal, sem remover o `PreToolUse` (defesa em profundidade — R3/R11).
2. **B-043 / AC-005..AC-007** — O paths filter `dorny/paths-filter` em `.github/workflows/ci.yml` (linhas 449-460, step `Check changed paths` do job `tenant-isolation`) está **defasado** (lista `app/Livewire/**` que foi demolido no slice-016/ADR-0015) e **estático** (qualquer nova camada sensível a tenant escapa silenciosamente).

### ADRs aplicáveis

- **ADR-0015** — Demolição frontend Livewire. Justifica remoção de `app/Livewire/**` do paths filter.
- **ADR-0016** — Isolamento multi-tenant. Define o conjunto de áreas sensíveis a `tenant_id` (Models, Http, Services, Domain, Jobs, migrations).
- **ADR-0017** — Rastreabilidade AC→teste. Obriga `@covers AC-NNN` em todos os testes do slice (`tests/slice-019/`).

### Princípios invioláveis aplicáveis

- **P2** — AC é teste executável, escrito antes do código. Todos os 7 ACs ganham teste red em `tests/slice-019/` antes da implementação.
- **P3** — Contexto isolado: `plan-review`, `audit-tests-draft`, `verify`, `code-review`, `security-review`, `test-audit`, `functional-review` rodam em instâncias separadas.
- **P4** — Hooks executam. Hook nativo `pre-push` deve bloquear de verdade, não só logar.
- **P7** — Verificação antes de afirmação. O `install-git-hooks.sh` imprime evidência (`installed:` / `already-current:`) e os testes validam SHA-256 do hook instalado.
- **R9** — Zero bypass. Hook nativo também rejeita `--no-verify` (alinhado ao PreToolUse existente).

### Estado confirmado do repositório (verificação P7 durante redação do plan)

- `.github/workflows/ci.yml` linhas 415-500: job `tenant-isolation` existe, step `Check changed paths` usa `dorny/paths-filter@v3` com filtro `run > paths` listando as 5 entradas que a spec reclama.
- `scripts/hooks/pre-push-gate.sh` (referência): 52 linhas, bloqueia (a) `--no-verify` (R9), (b) push em `main|master` (com exceção bootstrap `-u`), (c) `--force` em `main|master`. Emite `say`/`die` em stderr e usa `exit 0/1`.
- `scripts/hooks/session-start.sh`: tem bloco 4.5 com drift checks via `settings-lock.sh --check` e `hooks-lock.sh --check`. Ainda **não invoca** instalador de hooks git nativos — o gancho será adicionado como bloco 4.7 (após drift checks, antes do resumo de sessão).
- `.git/hooks/pre-push`: ausente (confirmado por spec-audit §`git_hooks_pre_push_absent_confirmed`).

---

## 2. Decisões de design

### D-01 — Estratégia do hook git nativo: wrapper fino que delega ao PreToolUse existente

**Decisão:** `.git/hooks/pre-push` é um wrapper bash de ~20 linhas que, em tempo de execução, invoca `scripts/hooks/pre-push-native.sh`. O `pre-push-native.sh` por sua vez **reusa a lógica de bloqueio** do `pre-push-gate.sh` sem importar o arquivo diretamente — cópia de regras simples (push direto em main/master + `--force` em main/master + rejeição de `--no-verify`) em função bash isolada. Não é `source` para evitar acoplamento com a convenção `CLAUDE_TOOL_ARG_COMMAND` do PreToolUse.

**Alternativas consideradas:**

- **Alt-A:** Duplicar 100% o `pre-push-gate.sh` como `.git/hooks/pre-push` direto. Rejeitado: dois arquivos divergem com o tempo, violando "fonte única de verdade" de regras P9.
- **Alt-B:** `source scripts/hooks/pre-push-gate.sh` no hook nativo. Rejeitado: `pre-push-gate.sh` lê `CLAUDE_TOOL_ARG_COMMAND` (vazia em contexto git nativo) e foi desenhado para receber a linha de comando como arg do Claude Code; semântica incompatível com o contrato git nativo (`pre-push` recebe refs via stdin, não a linha `git push` completa).
- **Alt-C (escolhida):** Script separado `pre-push-native.sh` que lê o contrato git nativo (stdin com linhas `<local_ref> <local_sha> <remote_ref> <remote_sha>` + arg `$1 = remote_name`, `$2 = remote_url`). Aplica as 3 regras críticas: bloqueio em main/master, bloqueio `--force` (detectado via `FORCE=1` no env quando disponível ou via presença de `+` no update), bloqueio de `--no-verify` (implícito — se o hook está executando, `--no-verify` não foi usado; o check é simbólico/redundante por consistência de mensagens).

**Trade-off:** pequena duplicação de regras (3 condicionais) em troca de independência de contrato (git nativo vs PreToolUse). Aceitável porque as 3 regras são estáveis (não mudaram desde criação do gate).

**Reversibilidade:** fácil. `git config --unset core.hooksPath` + `rm .git/hooks/pre-push` e volta ao estado pre-slice.

---

### D-02 — Detecção de "push em main" no contrato git nativo

**Decisão:** o hook lê as linhas do stdin no formato `<local_ref> <local_sha> <remote_ref> <remote_sha>`; se qualquer `<remote_ref>` for `refs/heads/main` ou `refs/heads/master`, bloqueia com exit 1. Detecção de `--force` usa a heurística de `<local_sha>` não ser ancestral de `<remote_sha>` (via `git merge-base --is-ancestor`); se não for ancestral e remote_sha não for zero, é force-update.

**Alternativas consideradas:**

- **Alt-A:** Detectar via `git rev-parse --abbrev-ref HEAD` (como o PreToolUse faz). Rejeitado: `git push origin main:main` de uma branch feature não aparece em HEAD — quebra detecção.
- **Alt-B (escolhida):** Ler stdin (contrato oficial do git hook) e casar com padrões. Funciona para `git push`, `git push origin main`, `git push origin HEAD:main`, `git push --all` etc.
- **Alt-C:** Usar `GIT_PUSH_OPTION_*` para detectar `--force`. Rejeitado: só existe se o usuário passar `--push-option`, não em `--force` puro.

**Trade-off:** heurística de merge-base tem 1 caso borda (remote ref novo, `<remote_sha>` é `0000...`) — tratado explicitamente como "não é force, é criação".

**Reversibilidade:** fácil.

---

### D-03 — Parser YAML de `ci.yml` em shell puro

**Decisão:** `check-tenant-filter-coverage.sh` usa `grep` + `sed` + `awk` para extrair a lista `paths:` do bloco `filters: run: paths:` do `.github/workflows/ci.yml`. Sem dependência externa (nem `yq`, nem `python-yaml`).

**Algoritmo:**

1. Localiza a linha `tenant-isolation:` (âncora de início).
2. A partir dela, localiza a primeira linha `paths:` indentada (dentro de `filters > run`).
3. Lê as linhas seguintes que começam com indentação `- '`, extrai o conteúdo entre aspas.
4. Para cada entrada, normaliza removendo `/**`, `/*`, aspas e espaços.
5. `ls app/` e compara com a lista normalizada — emite `uncovered:` para cada subdir ausente.
6. Para `uncovered:`, roda `grep -r -l -i 'tenant' app/<dir>/ --include='*.php'` — se encontra, marca `[SUSPECT]`.

**Alternativas consideradas:**

- **Alt-A:** Adicionar `yq` como dependência. Rejeitado: aumenta superfície de instalação; não existe no Git Bash de Windows nativo; PM opera em Windows.
- **Alt-B:** Python + PyYAML. Rejeitado: mesma razão (PM não tem Python garantido).
- **Alt-C (escolhida):** Shell puro. Frágil a reformatação do YAML, mas **o teste AC-005 congela o formato** (grep assertivo em paths específicos). Se o YAML for reformatado, o teste quebra primeiro.

**Trade-off:** fragilidade ao formato do YAML aceita porque a superfície é pequena (1 bloco, ~5 linhas) e o teste age como canário.

**Reversibilidade:** fácil (deletar script).

---

### D-04 — Reinstalação automática no session-start

**Decisão:** `scripts/hooks/session-start.sh` ganha novo bloco "4.7. Git native hooks" (depois do 4.5 drift checks, antes do bloco final de resumo). Bloco executa:

```
if [ ! -f .git/hooks/pre-push ] || ! grep -q "pre-push-native.sh" .git/hooks/pre-push; then
  bash scripts/install-git-hooks.sh --silent
  echo "[session-start] reinstalled git hook: .git/hooks/pre-push" >&2
fi
```

A checagem compara conteúdo (grep de `pre-push-native.sh`) para detectar hook legado substituído por outro tool (ex: husky) — força reinstalação se o wrapper canônico foi sobrescrito.

**Alternativas consideradas:**

- **Alt-A:** Comparar SHA-256 do hook contra um SHA esperado em `.claude/`. Rejeitado: cria mais arquivo selado e exige relock do PM (ritual pesado) a cada ajuste do wrapper.
- **Alt-B:** Não reinstalar automaticamente; só avisar "hook ausente — rode install-git-hooks.sh". Rejeitado: PM não usa terminal (feedback CLAUDE.md §3.1) e sessão Claude não pode rodar relock — se o hook é deletado, ninguém restaura.
- **Alt-C (escolhida):** Reinstalação automática silenciosa. É idempotente (D-01 garante), não bloqueia sessão, é auditável via linha `[session-start] reinstalled`.

**⚠️ Interação com harness selado:** `session-start.sh` está no MANIFEST.sha256 (selado). Alteração nele **exige relock** via `relock-harness.sh` em terminal externo (§9 CLAUDE.md). O PM será informado (tradução R12 via `/explain-slice 019`) de que o merge do slice-019 exige relock procedimental pós-merge. Este plano declara explicitamente que o relock é parte do fluxo de merge e não é contornável pelo agente.

**Trade-off:** aceita-se o ritual do relock em troca de auto-healing do hook git.

**Reversibilidade:** média. Remover o bloco do session-start exige novo relock.

---

### D-05 — Formato da saída do `check-tenant-filter-coverage.sh`

**Decisão:** saída em stdout, uma linha por subdir, formato fixo:

- `uncovered: app/<dir>/` — subdir de `app/` não presente no filter.
- `[SUSPECT] uncovered: app/<dir>/` — idem, mas contém ao menos 1 `.php` com string case-insensitive `tenant`.
- `covered: app/<dir>/` — (opcional) — emitido somente com flag `--verbose`; modo default emite só uncovered.

Exit code sempre 0 nesta versão (warning-only, AC-006.d). Flag `--strict` reservada para futuro (não implementada neste slice; reservar slot no parser via `case` para failing-closed).

**Alternativas consideradas:**

- **Alt-A:** JSON estruturado. Rejeitado para primeira versão (consumidor é humano em review PR; texto linear é mais legível).
- **Alt-B (escolhida):** Texto com prefixos fixos `uncovered:` / `[SUSPECT]`. Grepavel, diffavel, trivial de assert em teste.

**Trade-off:** não é machine-readable por default; troca por simplicidade e grep-friendliness.

**Reversibilidade:** fácil.

---

### D-06 — Política de limpeza do filter (remoção de `app/Livewire/**`)

**Decisão:** o diff do slice remove literalmente a linha `- 'app/Livewire/**'` do `.github/workflows/ci.yml` e adiciona `- 'app/Services/**'`, `- 'app/Domain/**'`, `- 'database/migrations/**'`. A lista final do filter:

```
- 'app/Models/**'
- 'app/Http/**'
- 'app/Services/**'
- 'app/Domain/**'
- 'app/Jobs/**'
- 'database/migrations/**'
- 'tests/slice-011/**'
- 'tests/tenant-isolation/**'
```

`app/Services/**` e `app/Domain/**` são adicionados proativamente ainda que hoje **não existam** subdirs nesses caminhos — o objetivo é garantir que quando surgirem (próximos slices de E03+), o filter já os cobre. ADR-0016 lista essas camadas como sensíveis.

**Alternativas consideradas:**

- **Alt-A:** Remover `Livewire` sem adicionar nada novo. Rejeitado: não endereça AC-006 do checker (que tornaria todo futuro subdir um "uncovered" ruidoso).
- **Alt-B:** Adicionar `app/**` (wildcard). Rejeitado: dispara o job `tenant-isolation` em alterações irrelevantes (Console commands, Providers) — CI pessimista.
- **Alt-C (escolhida):** Lista explícita das camadas sensíveis do ADR-0016 + migrations. Balanceia cobertura com seletividade.

**Trade-off:** se surgir camada nova **fora** dessas 6 áreas que lida com tenant (ex: `app/Integrations/`), o checker (D-03) avisa e um slice futuro atualiza o filter. Warning-now em vez de blocker-later.

**Reversibilidade:** fácil.

---

## 3. Mapa AC → arquivos/componentes

| AC | Arquivo(s) criados / editados | Função/abstração principal | Teste (tests/slice-019/) |
|---|---|---|---|
| AC-001 | **criar** `scripts/install-git-hooks.sh` | Instalador idempotente. Calcula SHA-256 esperado do wrapper, compara com `.git/hooks/pre-push` existente, copia se diferente, imprime `installed:` ou `already-current:`. Modo `--silent` suprime stdout mas mantém stderr. | `ac-001-install-idempotent.bats` ou `ac-001-install-idempotent.test.php` (Pest + shell) |
| AC-002 | **criar** `scripts/hooks/pre-push-native.sh` + `scripts/install-git-hooks.sh` (escrita do wrapper `.git/hooks/pre-push` que invoca native) | `pre-push-native.sh`: lê stdin (contrato git), detecta push em `refs/heads/main|master`, detecta `--force` via merge-base, emite `[pre-push-native BLOCK]` e exit 1 | `ac-002-equivalence-with-pretooluse.bats` |
| AC-003 | **editar** `scripts/hooks/session-start.sh` (bloco 4.7 novo) | Detecção de ausência + invocação `install-git-hooks.sh --silent` | `ac-003-session-start-reinstall.bats` |
| AC-004 | (cobertura runtime — sem arquivo novo, mas depende de `pre-push-native.sh` de AC-002) | Teste simula push em main via `git push` direto (bash -c) em repo temporário, verifica exit ≠ 0 e mensagem; faz rollback do commit | `ac-004-native-blocks-main-push.bats` |
| AC-005 | **editar** `.github/workflows/ci.yml` (bloco `filters > run > paths` do step `Check changed paths` do job `tenant-isolation`, linhas 453-460) | Lista de paths reescrita conforme D-06 | `ac-005-ci-paths-filter.test.php` (grep em ci.yml) |
| AC-006 | **criar** `scripts/check-tenant-filter-coverage.sh` | Parser shell do ci.yml + comparação com `ls app/` + heurística `[SUSPECT]` | `ac-006-checker-output.bats` |
| AC-007 | **editar** `docs/documentation-requirements.md` (adicionar seção `## Camadas sensíveis a tenant isolation`) | 3 elementos literais (declaração + apontamento ao checker + ref cruzada ADR-0016) | `ac-007-docs-requirements-section.test.php` (grep) |

### Arquivos tocados — resumo

**Criados:**
- `scripts/install-git-hooks.sh`
- `scripts/hooks/pre-push-native.sh`
- `scripts/check-tenant-filter-coverage.sh`
- `tests/slice-019/ac-001-install-idempotent.bats` (ou equivalente)
- `tests/slice-019/ac-002-equivalence-with-pretooluse.bats`
- `tests/slice-019/ac-003-session-start-reinstall.bats`
- `tests/slice-019/ac-004-native-blocks-main-push.bats`
- `tests/slice-019/ac-005-ci-paths-filter.test.php`
- `tests/slice-019/ac-006-checker-output.bats`
- `tests/slice-019/ac-007-docs-requirements-section.test.php`

**Editados:**
- `.github/workflows/ci.yml` (linhas 453-460 do filter + comentários linhas 420-421)
- `scripts/hooks/session-start.sh` (novo bloco 4.7) — **exige relock**
- `docs/documentation-requirements.md` (nova seção no fim)

**Escolha de framework de teste:** mistura intencional. Testes de **shell** (AC-001..004, AC-006) usam `bats-core` (batteries-included Bash Automated Testing System) — mais natural para asserções sobre exit codes, stdout/stderr, arquivos de sistema, hooks git. Testes de **conteúdo de arquivo** (AC-005 e AC-007) usam Pest+PHP com `expect(file_get_contents)->toContain(...)` — consistente com `tests/slice-011/` e demais slices do repositório. Esta escolha **não adiciona dependência nova** se `bats-core` já existir no repo; se não existir, o builder deve confirmar no passo T-03 abaixo e, em última hipótese, converter os 5 testes bash para Pest com `shell_exec()` (plano B).

---

## 4. Tasks

Ordem rígida P8 (teste → código → verificação). Cada task é atômica (≤ 3 arquivos, 1 commit).

| Task | Descrição | Arquivos | Done quando |
|---|---|---|---|
| **T-01** | Verificar disponibilidade de `bats-core` no ambiente (PHPRC + Git Bash). Se ausente, documentar plano B (Pest com `shell_exec`) em comentário no `tests/slice-019/README.md`. | `tests/slice-019/README.md` | Escolha de framework shell documentada; plano B declarado se bats indisponível. |
| **T-02** | Escrever teste red AC-005 (grep em `ci.yml` — confirma presença dos 8 paths + ausência de Livewire). | `tests/slice-019/ac-005-ci-paths-filter.test.php` | Teste roda e falha (red): ci.yml ainda tem Livewire e falta Services/Domain/migrations. |
| **T-03** | Escrever teste red AC-007 (grep em `documentation-requirements.md`). | `tests/slice-019/ac-007-docs-requirements-section.test.php` | Teste roda e falha (red): seção ainda não existe. |
| **T-04** | Escrever testes red AC-001, AC-002, AC-003, AC-004, AC-006 em bats (ou Pest plano B). | 5 arquivos em `tests/slice-019/` | Todos rodam e falham por ausência dos scripts. |
| **T-05** | Commit inicial: `test(slice-019): AC tests red`. | — | `git log -1 --oneline` mostra o commit; `php artisan test tests/slice-019` + `bats tests/slice-019/` mostram N red. |
| **T-06** | `/audit-tests-draft 019` — qa-expert instância isolada — ADR-0017 | `specs/019/tests-draft-audit.json` | `verdict: approved`, findings: []. |
| **T-07** | Implementar `scripts/install-git-hooks.sh` (D-01 + D-05) e `scripts/hooks/pre-push-native.sh` (D-01 + D-02). | 2 arquivos | AC-001, AC-002 green; AC-004 green (native bloqueia push). |
| **T-08** | Implementar `scripts/check-tenant-filter-coverage.sh` (D-03 + D-05). | 1 arquivo | AC-006 green (parser emite `uncovered:` / `[SUSPECT]` corretamente). |
| **T-09** | Editar `.github/workflows/ci.yml` (D-06): remover Livewire, adicionar Services/Domain/migrations/tenant-isolation-tests. | 1 arquivo (diff ~5 linhas) | AC-005 green; job `tenant-isolation` continua verde em slice-019 (auto-trigger pelo diff do próprio ci.yml é esperado; roda e passa). |
| **T-10** | Editar `docs/documentation-requirements.md` (D-06): adicionar seção de camadas sensíveis. | 1 arquivo | AC-007 green. |
| **T-11** | Editar `scripts/hooks/session-start.sh` (D-04): adicionar bloco 4.7. **⚠️ não commitar ainda — arquivo selado.** | 1 arquivo | AC-003 green em ambiente de teste (sem selo); PM é informado via R12 que o relock é necessário no merge. |
| **T-12** | Commit final antes de verify: `feat(slice-019): git native hook + tenant filter refresh`. | — | Todos os 7 ACs green localmente. |
| **T-13** | Pipeline de gates: `/verify-slice 019` → `/review-pr 019` → `/security-review 019` → `/test-audit 019` → `/functional-review 019` → `/master-audit 019`. | — | Todos approved com blocking_findings_count == 0. |
| **T-14** | Relock procedimental do session-start (PM executa `relock-harness.sh` em terminal externo após abertura do PR, antes do merge). | `scripts/hooks/MANIFEST.sha256` | SHA de session-start.sh atualizado no MANIFEST; incidente de relock em `docs/incidents/`. |
| **T-15** | `/merge-slice 019`. | — | PR merged, telemetria `slice_merged` emitida. |

Total: **15 tasks**.

---

## 5. Riscos técnicos

### R-01 — Hook recursion (hook git chamando git dentro de hook git)

**Descrição:** se `pre-push-native.sh` usar `git merge-base --is-ancestor` e esse comando disparar hook recursivamente, loop infinito.

**Mitigação:** `git merge-base --is-ancestor` é comando de leitura pura (não dispara hooks). Adicionalmente, `install-git-hooks.sh` **não invoca git commands dentro do hook** — só escreve arquivos. Teste AC-004 valida em ambiente controlado que um push real termina (timeout 30s).

**Probabilidade:** baixa. **Impacto:** alto.

---

### R-02 — Falso-positivo da heurística `[SUSPECT]` em `check-tenant-filter-coverage.sh`

**Descrição:** um subdir como `app/Console/` pode ter comentário `// filtra por tenant` sem ser área sensível → marca `[SUSPECT]` indevidamente → ruído.

**Mitigação:** warning-only nesta primeira versão (AC-006.d declara exit 0 sempre). A saída é lida por humano em review de PR (ou não é lida, sem custo). Slice-020+ pode refinar heurística (ex: buscar por `Tenant::` ou `tenant_id` em vez de só `tenant`). Teste AC-006 valida formato mas não verifica ausência de falso-positivo — é conscientemente warning-only.

**Probabilidade:** média. **Impacto:** baixo (só ruído de texto, não bloqueia).

---

### R-03 — Idempotência do instalador quebrada em Windows Git Bash (line endings CRLF vs LF)

**Descrição:** `install-git-hooks.sh` escreve o wrapper `.git/hooks/pre-push`; Git no Windows pode aplicar `core.autocrlf` e converter LF→CRLF na escrita, fazendo o SHA-256 diferir entre execuções 1 e 2 mesmo que o conteúdo lógico seja idêntico.

**Mitigação:** o script escreve via heredoc com `cat > .git/hooks/pre-push <<'EOF' ... EOF` **dentro** de `.git/hooks/` — por convenção do git, arquivos em `.git/` **não sofrem** `autocrlf` (gitdir é ignorado). Defensivamente, o install script força LF usando `printf '%s\n'` ou `tr -d '\r'` no output. Teste AC-001 valida SHA idêntico entre execuções — quebra imediatamente se CRLF se infiltrar.

**Probabilidade:** média (ambiente PM é Windows). **Impacto:** alto (AC-001 falha). **Prioridade de teste:** máxima.

---

### R-04 — `session-start.sh` selado bloqueia edição pelo agente

**Descrição:** `session-start.sh` está em `scripts/hooks/MANIFEST.sha256` (selado). Agente **não pode** editar via Edit/Write/Bash (bloqueado por `sealed-files-bash-lock.sh`). Slice não consegue ser implementado pelo agente.

**Mitigação:** esta é uma restrição **procedimental**, não um bug. O agente escreve tudo em branch feature **sem** tocar em `session-start.sh` durante T-01..T-10. Em T-11, o agente **gera o patch** e grava como `specs/019/session-start-patch.diff`. Após merge do PR (T-15), o PM executa em terminal externo: `git apply specs/019/session-start-patch.diff && KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh`. Incidente de relock fica em `docs/incidents/harness-relock-<timestamp>.md`. **AC-003 fica gated em "verificável após relock"**: o teste bats roda em sandbox (cópia temp de session-start.sh) para provar que a lógica funciona; verificação real pós-relock é manual pelo PM com screenshot/log.

**Alternativa avaliada:** adiar AC-003 para slice-020. Rejeitada pelo PM (feedback "zero technical debt" — MEMORY.md): AC-003 é parte do valor do slice (auto-healing), não pode ser cortada. Procedimento de relock é o custo aceito.

**Probabilidade:** certeza (arquivo É selado). **Impacto:** médio (adiciona passo manual ao merge, não bloqueia entrega).

---

### R-05 — Job `tenant-isolation` no CI roda sobre o próprio PR do slice-019 (meta-test)

**Descrição:** o PR do slice-019 edita `ci.yml` → dispara o job `tenant-isolation` → paths filter avalia o PR → decide se roda ou não → pode rodar com o **novo** filter contra código que **ainda não adaptou** ou pular quando deveria rodar.

**Mitigação:** edição do `ci.yml` é no próprio filter — o filter novo inclui `database/migrations/**` e o PR 019 não toca em migrations, nem em `app/Services/**`, nem em `app/Domain/**`. O único diretório da lista ampliada que o PR toca são `tests/slice-019/**` (não está no filter de trigger — slice-011 é o trigger histórico; slice-019 adiciona `tests/tenant-isolation/**` como path catch-all futuro). **Resultado esperado:** job `tenant-isolation` é **skipped** no PR-019 (paths filter `run: false`). Isso é correto comportamento — slice-019 não altera nada que tenant-isolation testes cobrem. Regressão de tenant-isolation nos slices já merged (010-018) não é afetada pois o job pula no PR novo.

**Probabilidade:** certeza (acontece). **Impacto:** baixo (é comportamento esperado, só precisa ser verificado manualmente no PR).

---

### R-06 — Conflito entre hook nativo e PreToolUse quando agente pusha

**Descrição:** quando o Claude Code pusha, passam **ambos**: PreToolUse intercepta `Bash(git push*)` e `.git/hooks/pre-push` intercepta o push real. Risco: mensagens duplicadas ou duplo bloqueio.

**Mitigação:** duplicação de bloqueio é **desejada** (defesa em profundidade). Se PreToolUse bloquear, o push nunca acontece e o hook nativo nem roda → ok. Se PreToolUse permitir (branch feature normal), hook nativo também permite → ok. Os dois gates são idempotentes em decisão: mesmas entradas → mesma saída. Teste AC-002 valida especificamente "equivalência funcional mínima" entre os dois.

**Probabilidade:** N/A (design feature). **Impacto:** zero (intencional).

---

## 6. Cobertura de não-regressão

### 6.1. Hooks existentes continuam funcionando

- **PreToolUse `pre-push-gate.sh`**: slice-019 **não toca** neste arquivo (spec §"Fora de escopo"). Mantém-se funcional. Regressão detectada se: alguma sessão Claude rodar `git push` e o gate não bloquear main. Hook `settings-lock.sh --check` em session-start valida SHA-256 do settings.json que referencia pre-push-gate.sh — drift seria detectado.
- **`session-start.sh`**: após adicionar bloco 4.7 (T-11) e relock (T-14), o novo MANIFEST.sha256 reflete o novo conteúdo. Seções 1, 2, 3, 4, 4.5 continuam rodando na mesma ordem — bloco 4.7 é estritamente aditivo. Teste manual pelo PM: abrir nova sessão Claude → conferir que o banner de SessionStart mostra as mesmas mensagens OK de antes + 1 linha nova de reinstall (se aplicável).
- **`pre-commit-gate.sh`** e demais hooks: **não são tocados** por este slice. MANIFEST.sha256 é alterado apenas para session-start.sh (linha única).

### 6.2. Job `tenant-isolation` continua verde para slices 010-018

- **Método:** após merge do slice-019, rodar manualmente em branch scratch `git checkout main && git log --oneline -20` e re-rodar o CI no último commit via `gh run rerun` sobre PR histórico (ex: PR do slice-015). Esperar job `tenant-isolation` ficar verde.
- **Alternativa (mais leve):** commit "no-op" em branch `test/tenant-isolation-regression` tocando 1 arquivo em `app/Models/` (ex: comentário adicional em `app/Models/Client.php`) para forçar o filter a disparar o job. Se verde, cobertura preservada.
- **Observação AC-005:** o teste AC-005 valida apenas **conteúdo** do ci.yml (grep). Ele **não** roda o job CI — isso é cobertura dinâmica, obtida pela execução do próprio CI ao abrir o PR-019 + verificação manual dos últimos 3 PRs pós-merge.

### 6.3. Checklist final pré-merge

- [ ] `php artisan test tests/slice-019` — 100% verde.
- [ ] `bats tests/slice-019/*.bats` (ou Pest plano B) — 100% verde.
- [ ] `git log -1 --stat` mostra apenas arquivos do escopo do plan.
- [ ] `ls .git/hooks/pre-push` existe, executável.
- [ ] Sessão Claude nova: banner mostra `settings-lock OK` + `hooks-lock OK` + (opcional) `[session-start] reinstalled git hook`.
- [ ] PR CI verde em **todos** os jobs (incluindo `tenant-isolation` se disparar; caso skipped, confirmar que foi por paths filter e não por erro).
- [ ] `docs/documentation-requirements.md` contém a seção nova (grep manual).

---

## 7. Referências cruzadas

- **spec.md:** `specs/019/spec.md` (approved via `specs/019/spec-audit.json`, zero findings).
- **ADR-0015:** demolição Livewire (justifica remoção do path).
- **ADR-0016:** isolamento multi-tenant (define camadas sensíveis do filter).
- **ADR-0017:** rastreabilidade AC→teste (obriga `@covers AC-NNN`).
- **`scripts/hooks/pre-push-gate.sh`:** referência funcional (contrato PreToolUse).
- **`scripts/hooks/session-start.sh`:** ponto de integração (bloco 4.7 novo).
- **`scripts/hooks/MANIFEST.sha256`:** selo a ser atualizado em T-14.
- **`.github/workflows/ci.yml` linhas 415-500:** job `tenant-isolation`.
- **`docs/harness-limitations.md`:** política de relock.
- **CLAUDE.md §9:** procedimento legítimo de relock.
- **docs/protocol/03-contrato-artefatos.md §4.5:** contrato do plan.md.
- **docs/protocol/04-criterios-gate.md §plan-review:** critérios antecipados.

---

## 8. Pipeline de gates deste slice (L2 — conforme spec §Gates aplicáveis)

1. ✅ `/audit-spec 019` — qa-expert — approved 2026-04-18.
2. ▶ `/draft-plan 019` — **este artefato**.
3. ⏭ `/review-plan 019` — architecture-expert (plan-review, instância isolada).
4. ⏭ `/draft-tests 019` — builder (test-writer).
5. ⏭ `/audit-tests-draft 019` — qa-expert (instância isolada, ADR-0017).
6. ⏭ builder (implementer).
7. ⏭ `/verify-slice 019`.
8. ⏭ `/review-pr 019`.
9. ⏭ `/security-review 019`.
10. ⏭ `/test-audit 019`.
11. ⏭ `/functional-review 019`.
12. ⏭ `/master-audit 019` (dual-LLM 2× Opus 4.7).
13. ⏭ Relock procedimental do session-start pelo PM (T-14).
14. ⏭ `/merge-slice 019`.

Gates **não aplicáveis**: data-gate (sem migrations), observability-gate (sem logging novo), integration-gate (sem APIs externas).
