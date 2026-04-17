# Handoff — 2026-04-17 — Slice 015 merged + débito de recuperação

## Resumo

Slice 015 (Spike INF-007, story E15-S01) **merged em main** via PR #36 commit `8addb11`. Primeira story completa sob ADR-0017 (AC-ID rastreável) e primeira execução da nova política **dual-LLM = 2× Opus 4.7 em contextos isolados** (GPT-5/Codex descontinuado 2026-04-17).

## O que entrou em main

- `tests/slice-015/SpikeInf007Test.php` — 8 testes Pest / 40 assertions / 0.24s
- `docs/frontend/api-endpoints.md` — 31 endpoints mapeados
- `docs/frontend/stack-versions.md` — 13 pacotes pinnados + riscos + checklist
- `spike-inf007/` — PoC descartável com package.json
- `specs/015/` — spec, plan, tasks, 9 JSONs de gate
- `epics/E15/` — 10 stories + INDEX
- `phpunit.xml` — adiciona testsuite Slice015

## O que NÃO entrou em main (débito de recuperação)

Os seguintes artefatos foram criados nas branches `work/offline-discovery-2026-04-16` e `feat/slice-015-spike-inf007` durante a ampliação v1+v2+v3, mas **não foram propagados para main** quando as branches foram abandonadas por conflito de schema/line-ending.

Commits ainda referenciados no reflog local (recuperáveis via `git show <sha>` ou `git cherry-pick <sha>`):

| Artefato | Último SHA conhecido | Origem |
|---|---|---|
| `work/offline-discovery-2026-04-16` tip | `2bbce17` | chore(state): slice 015 merged + dual-LLM 2x Opus |
| `feat/slice-015-spike-inf007` tip | `7abe9c8` | chore(slice-015): compat bi-schema |
| Migração harness v1→v1.2.2 (scripts) | `7c9b06a` | commit 1 da branch work |

Arquivos **ausentes em main** que existem nesses commits:
- `docs/product/PRD-ampliacao-2026-04-16.md` (v1 offline-first)
- `docs/product/PRD-ampliacao-2026-04-16-v2.md` (v2 auditoria comparativa)
- `docs/product/PRD-ampliacao-2026-04-16-v3.md` (v3 re-auditoria)
- `docs/product/post-mvp-backlog.md`
- `docs/incidents/discovery-gap-offline-2026-04-16.md`
- `docs/audits/comparativa-externa-2026-04-16.md`
- `docs/audits/comparativa-externa-reaudit-2026-04-16.md`
- `docs/adr/ADR-0016-*.md` (isolamento multi-tenant)
- `docs/handoffs/handoff-2026-04-16-ampliacao.md`
- Migrações de `scripts/*.sh` para gate-output-v1 (do commit `7c9b06a`)
- Atualizações em `project-state.json` sobre ampliação (25 épicos, 80 REQs, etc)

## Gates aprovados no slice 015

- **spec-audit · plan-review · tests-draft-audit** (planning gates)
- **verify · code-review · security-gate · audit-tests · functional-gate** (principais, 5/5)
- **master-audit dual-LLM 2× Opus**
  - Trilha A (governance, main instance): approved, 0 blocking, 9 S4, 1 S5
  - Trilha B (sub-agent Opus 4.7 isolado): approved, 0 blocking, 2 S4, 1 S5
  - Consenso pleno, zero divergência, zero reconciliação

## Débitos novos abertos (guide-backlog)

- **B-029** — Migrar `scripts/merge-slice.sh` para gate-output-v1 (alta prioridade)
- **B-030** — `.gitattributes` forçando LF para `scripts/hooks/**` + `*.sha256` (alta)
- **B-031** — Hook `session-start.sh` detectar branch atrasada vs `origin/main` (média)
- **B-032** — Telemetria dos sub-agents isolados não chega ao `.jsonl` do slice (média)
- **B-033 (proposto)** — Atualizar ADR-0012 + `docs/operations/codex-gpt5-setup.md` para refletir política 2× Opus (média)

## Próxima ação única

**PM decide:** recuperar os artefatos de ampliação (PRDs v1/v2/v3, ADR-0016, incidents, audits externos) via cherry-pick dos commits `2bbce17` e `7abe9c8`, ou recriá-los em sessão futura?

Após essa decisão, próximo slice natural: **`/start-story E15-S02`** (scaffold Capacitor/Ionic — depende do checklist em `docs/frontend/stack-versions.md`).

## Estado ao sair

- Branch: `main` (limpa, fast-forwarded, `8addb11`)
- Branches deletadas: `work/offline-discovery-2026-04-16`, `feat/slice-015-spike-inf007` (no remoto e localmente)
- PRs fechados: #35 (abandonado, superseded), #36 (merged)
- Working tree: alguns untracked que o PM pode limpar (`*.bak-*`, `INSTALAR-ATALHO.bat`, `scripts/install-desktop-shortcut.ps1`, `scripts/open-workspace.bat`, `scripts/pm-dashboard.ps1`, `scripts/staging/`)
- Arquivos selados com line endings CRLF no working tree — sem impacto pois não serão commitados
