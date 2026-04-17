# Handoff — 2026-04-17 — Sessão de recuperação + alinhamento protocolo v1.2.4

## Resumo executivo

Sessão longa de limpeza que resolveu 3 coisas principais:
1. **Slice 015 (E15-S01) merged em main** via PR #36 com política dual-LLM 2× Opus 4.7 estreada (ADR-0020).
2. **Recuperação completa** dos artefatos criados nas branches deletadas `work/offline-discovery-2026-04-16` (tip `2bbce17`) e `feat/slice-015-spike-inf007` (tip `7abe9c8`).
3. **Protocolo alinhado em v1.2.4** em 62 arquivos (antes era split v1.2.2 nos docs + v1.2.4 no schema) e todos os débitos B-029..B-033 fechados/documentados.

## 9 PRs mergeados

| PR | Título | Commit |
|---|---|---|
| #36 | feat(slice-015): Spike INF-007 | `8addb11` |
| #37 | chore(slice-015): retrospectiva + guide-backlog + handoff | `81e84d8` |
| #38 | chore(recovery): 37 artefatos órfãos das branches deletadas | `5ce2126` |
| #39 | feat(prd): ampliação v1+v2+v3 nos docs principais | `a13fe36` |
| #40 | chore(recovery): union merge `2bbce17` (44 arquivos) | `4d8c007` |
| #41 | chore(harness): protocol v1.2.4 + ADR-0020 + .gitattributes | `a900755` |
| #42 | chore(harness): .bat PM-friendly para B-031 | `fe12119` |
| #43 | fix(harness): .bat relock não propagava env var | `55423fb` |
| #44 | chore(harness): ativar branch-sync-check (fecha B-031) | `1e5e097` |

Main local + remoto em `1e5e097`.

## Débitos resolvidos

| ID | Status | Nota |
|---|---|---|
| B-029 | ✅ Resolvido (PR #40+#41) | `merge-slice.sh` já em v1.2.4 pelo union merge |
| B-030 | ✅ Resolvido (#41) | `.gitattributes` força LF em `scripts/hooks/**`, `*.sha256`, schemas, `*.md` |
| B-031 | ✅ Resolvido (#44) | Hook `branch-sync-check.sh` ativo no `session-start.sh`, relock aplicado pelo PM |
| B-032 | 📄 Documentado como L-03 (#41) | Telemetria sub-agents isolados — mitigação pendente |
| B-033 | ✅ Resolvido (#41) | ADR-0020 aceito, guia canônico em `docs/operations/dual-llm-opus-setup.md` |

## Mudanças operacionais normativas

### Dual-LLM = 2× Opus 4.7 (não GPT-5) — ADR-0020
- Trilha A: governance agent principal.
- Trilha B: sub-agent Opus spawnado via `Agent` tool com `subagent_type=governance`, contexto isolado.
- Fallback GPT-5 preservado em `docs/operations/codex-gpt5-setup.md` (DEPRECATED mas disponível para rate-limit prolongado).

### Protocolo v1.2.4
- Changelog v1.2.3: gates L4 pre-reviews formalizados.
- Changelog v1.2.4: ADR-0017 Mudança 1 incorporada (gate `audit-tests-draft` + rastreabilidade AC-ID em `draft-tests`).
- 62 arquivos alinhados (docs/protocol, CLAUDE.md 2.8.1, scripts, agents, skills).

### `.gitattributes` hardening
- `scripts/hooks/*.sh`, `scripts/hooks/MANIFEST.sha256`, `*.sha256`, `docs/protocol/schemas/*.json`, `*.md` forçados em LF.
- Devs que clonaram antes precisam `git rm --cached -r . && git reset --hard` para re-aplicar.

### Hook branch-sync-check
- Adicionado em `scripts/hooks/session-start.sh` (linhas pós-relock).
- Avisa (não bloqueia) quando branch atual está > 10 commits atrás de origin/main.
- Threshold ajustável via `KALIB_BRANCH_SYNC_THRESHOLD`; modo fail via `KALIB_BRANCH_SYNC_FAIL=1`.

## Estado do projeto

- **Fase:** Execução (E15 em progresso)
- **Épico ativo:** E15 (PWA Shell Offline-First + Capacitor)
- **Story merged:** E15-S01 (Spike INF-007) via PR #36
- **Slice atual:** nenhum ativo (próximo = E15-S02)
- **Projeto:** 25 épicos (19 MVP + 4 post-MVP + 2 future), 80 REQs, 9 personas, 17 jornadas, 17 ADRs (0001-0020), 12 agents, 41 skills.
- **Harness:** protocol v1.2.4, CLAUDE.md 2.8.1, 22 hooks selados (+ branch-sync-check).

## Working tree ao sair

- Branch local: `main` em `1e5e097` (sincronizado com origin).
- Arquivos untracked mantidos (não entram em commit):
  - `INSTALAR-ATALHO.bat`, `scripts/install-desktop-shortcut.ps1`, `scripts/open-workspace.bat`, `scripts/pm-dashboard.ps1`, `scripts/staging/merge/`, vários `*.bak-*` da área selada.

## Próxima ação única

`/start-story E15-S02` — scaffold Capacitor/Ionic.

Depende de: `docs/frontend/api-endpoints.md` + `docs/frontend/stack-versions.md` (ambos entregues no slice 015).

## Reliquias a considerar em sessão futura

- **B-032** (telemetria sub-agents) — implementar uma das mitigações listadas em L-03 de `docs/harness-limitations.md`. Não bloqueia, só melhora observabilidade pós-slice.
- **AMPLIATION-RECOVERY-001** (project-state.json) — debito menor sobre consolidar stories de E03 no `epics_status`. Pode ser feito quando E03 retomar.
- **Harness integrity CI** — check cronicamente vermelho por drift local vs CI. Admin merge foi usado em #36, #37, #41. Precisa investigação dedicada.
