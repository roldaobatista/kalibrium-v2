# Meta-audit action plan — tracker de progresso

**Origem:** `docs/audits/meta-audit-2026-04-10-action-plan.md` (commit `345b0a2`)
**Decisão do PM:** `docs/decisions/pm-decision-meta-audit-2026-04-10.md` (commit `345b0a2`)
**Status geral:** ✅ Bloco 1 completo
**Última atualização:** 2026-04-10

## Legenda

- `[ ]` = não iniciado
- `[~]` = em andamento
- `[x]` = completo (data + commit curto)
- `[!]` = bloqueado (ver incident)

---

## Bloco 0 — Alinhamento com o PM ✅

**Status:** ✅ completo — 2026-04-10
**Evidência:** `docs/decisions/pm-decision-meta-audit-2026-04-10.md`

- [x] 0.1 Aceitar escopo completo — 2026-04-10
- [x] 0.2 Aprovar consultor metrologia — 2026-04-10
- [x] 0.3 Aprovar consultor fiscal — 2026-04-10
- [x] 0.4 Aceitar pausa dura — 2026-04-10
- [x] 0.5 Aceitar Dia 1 em harness — 2026-04-10

---

## Bloco 1 — Selar o harness contra auto-modificação ✅

**Status:** ✅ completo — 2026-04-10
**Pré-requisito:** Bloco 0 ✅
**Critério de pronto:** `/guide-check` VERDE (0 findings, baseline `6a0d297` + 11 commits auditados no range) + `smoke-test-hooks.sh` 75/75 verdes (1 skip honesto: symlink test em Windows sem admin) + 2 commits ordenados:
- Commit A `345b0a2` — `docs(meta-audit)` — pré-requisito narrativo
- Commit B (este bloco) — `chore(harness): selar contra auto-modificação (bloco 1 meta-audit)`

- [x] 1.1 Hash-lock de `.claude/settings.json` + `settings-lock.sh` — 2026-04-10 (commit-pending)
- [x] 1.2 Hash-lock de `scripts/hooks/*` + `MANIFEST.sha256` + `hooks-lock.sh` — 2026-04-10 (commit-pending)
  - **Adição PM 2026-04-10:** o próprio `MANIFEST.sha256` é selado via `settings-lock.sh` (PreToolUse Edit|Write bloqueia o arquivo) — fecha o caminho de "robô edita hook + regenera manifesto no mesmo commit". Atualização do manifesto requer `scripts/relock-harness.sh` que exige TTY interativa + variável `KALIB_RELOCK_AUTHORIZED` setada manualmente pelo PM em terminal externo + confirmação por digitação `RELOCK` + criação automática de `docs/incidents/harness-relock-*.md`.
- [x] 1.3 Append-only telemetria com hash-chain + `record-telemetry.sh` + `telemetry-lock.sh` + `telemetry.schema.json` — 2026-04-10 (commit-pending)
- [x] 1.4 Fail-closed no `verifier-sandbox.sh` (detecção multi-sinal de contexto sub-agent quando `CLAUDE_AGENT_NAME` vazio: `.git` arquivo + comparação toplevel vs main worktree) — 2026-04-10 (commit-pending)
- [x] 1.5 `realpath -m` resolve symlinks/.. antes de comparar caminhos (fecha symlink + traversal) — 2026-04-10 (commit-pending)
- [x] 1.6 Cobertura de `Bash` no `verifier-sandbox.sh` + novo hook `sealed-files-bash-lock.sh` (observação PM: bloqueia `echo >`, `sed -i`, `tee`, `cp`, `mv` em arquivos selados) — 2026-04-10 (commit-pending)
- [x] 1.7 `.claude/allowed-git-identities.txt` + validação em `pre-commit-gate.sh` + selo via `settings-lock.sh` + smoke-test usa padrão (a) backup/mutate/restore — 2026-04-10 (commit-pending)
- [x] 1.8 `verify-slice.sh` e `review-slice.sh` rodam `hooks-lock.sh --check` + `settings-lock.sh --check` antes de spawnar sub-agent (enforcement real, substitui instrução-de-prompt) — 2026-04-10 (commit-pending)
- [x] 1.9 `sanitize-input.sh` (blocklist EN+PT + envelope XML CDATA) integrado em `verify-slice.sh prepare` e `review-slice.sh prepare` — 2026-04-10 (commit-pending)

---

## Bloco 2 — Decidir a stack (ADR-0001)

**Status:** ⏸ bloqueado por Bloco 1

- [ ] 2.1 Rodar `/decide-stack` com ≥2 alternativas → `docs/adr/0001-stack.md`
- [ ] 2.2 Endurecer `block-project-init.sh` (bloquear Write direto em `package.json`, `tsconfig.json`, etc.)
- [ ] 2.3 Gate de ADR-0001 no `session-start.sh` (modo `harness-bootstrap-only`)
- [ ] 2.4 `docs/stack.json` canônico (test_cmd, lint_cmd, type_cmd, affected_test_cmd, coverage_cmd)

---

## Bloco 3 — Gates reais de execução de teste

**Status:** ⏸ bloqueado por Bloco 2

- [ ] 3.1 Execução real dos testes no `verify-slice.sh` → `test-output.json` imutável hash-verificado
- [ ] 3.2 `ac-red-check.sh` — P2 de verdade (rejeita teste AC que nasce verde)
- [ ] 3.3 `post-edit-gate.sh` obrigatório por arquivo (WARN → die para `src/**`, allowlist para docs/config)
- [ ] 3.4 `pre-push-gate.sh` roda testsuite do domínio de verdade
- [ ] 3.5 `validate-ac-coverage.sh` valida `ac-list.json` vs `ac-coverage-map.json`

---

## Bloco 4 — Tradutor PM + pausa dura

**Status:** ⏸ bloqueado por Bloco 3

- [ ] 4.1 `explain-slice.sh` real (traduz categorias técnicas → linguagem de produto)
- [ ] 4.2 `check-r12-vocabulary.sh` (blocklist técnica em outputs PM)
- [ ] 4.3 Canal duplo P7 + R12 (`technical-evidence.json` + `pm-report.md`)
- [ ] 4.4 Reviewer e verifier em paralelo (fechar vazamento do "prior positivo")
- [ ] 4.5 Pausa dura em categorias críticas + `docs/policies/r6-r7-policy.md`
- [ ] 4.6 Log automático de "PM aprovou override" em `docs/incidents/pm-override-NNN.md`

---

## Bloco 5 — Juiz externo (CI + GitHub Action)

**Status:** ⏸ bloqueado por Bloco 4

- [ ] 5.1 `.github/workflows/ci.yml` (lint + types + test + smoke-hooks + harness-integrity)
- [ ] 5.2 `.github/workflows/auto-approve.yml` + GitHub App `kalibrium-auto-reviewer`
- [ ] 5.3 Ruleset de `main` endurecido (remover `current_user_can_bypass`)
- [ ] 5.4 Fechar B-009 do guide-backlog; admin bypass só em `hotfix/*`

---

## Bloco 6 — Defesas adicionais

**Status:** ⏸ bloqueado por Bloco 5

- [ ] 6.1 Sub-agent `domain-expert` + `.claude/agents/domain-expert.md` + `specs/NNN/domain-review.json`
- [ ] 6.2 `scripts/metrics-check.sh` (complexidade, duplicação, dead code, coverage ≥80%)
- [ ] 6.3 `/decide-stack` + validador exigindo ≥2 alternativas (`validate-decide-output.sh`)
- [ ] 6.4 `scripts/hooks/mcp-check.sh` + `.claude/allowed-mcps.txt`
- [ ] 6.5 Incorporar golden tests da trilha paralela (metrologia + fiscal)

---

## Bloco 7 — Re-auditoria em sessão nova (go/no-go do Dia 1)

**Status:** ⏸ bloqueado por Bloco 6

- [ ] 7.1 Re-auditoria interna em sessão nova → `docs/audits/meta-audit-revalidation-YYYY-MM-DD.md`
- [ ] 7.2 Smoke test integração `specs/000-smoke/` (7 cenários de ataque)
- [ ] 7.3 Decisão formal Dia 1 → `docs/decisions/day1-go-no-go-YYYY-MM-DD.md` com 4 checkboxes

---

## Trilha paralela — Compliance regulado

**Status:** 🟡 aguardando RFP do PM (pode começar imediatamente)
**Independente dos blocos técnicos.**

### Metrologia (aprovada em decisão 0.2)

- [ ] M1 RFP consultor metrologia (perfil RBC, GUM/JCGM 100:2008, ISO 17025)
- [ ] M2 Contratar consultor
- [ ] M3 50 casos GUM validados (`tests/golden/metrology/gum-cases.csv`)
- [ ] M4 `tests/golden/metrology/` + test runner
- [ ] M5 Hook `pre-push-gate` bloqueia sem golden verde quando diff toca `src/metrology/**`
- [ ] M6 `docs/compliance/metrology-policy.md`

### Fiscal (aprovada em decisão 0.3)

- [ ] F1 RFP consultor contábil (NF-e/NFS-e multi-UF, reforma tributária)
- [ ] F2 Contratar consultor
- [ ] F3 30 casos NF-e/NFS-e/ICMS por UF (CSV)
- [ ] F4 `tests/golden/fiscal/` + runner
- [ ] F5 Hook + `docs/compliance/fiscal-policy.md`

### REP-P / LGPD / ICP-Brasil

- [ ] Registrar como fora do MVP em `docs/compliance/out-of-scope.md` (requer advogado trabalhista + DPO, decisão futura)

---

## Como atualizar este arquivo

Ao completar um item:
- Trocar `[ ]` por `[x]`
- Adicionar data e hash curto do commit: `[x] 1.1 ... — 2026-04-11 (a3f2d8e)`

Ao iniciar um bloco:
- Trocar status para `[~] em andamento` no cabeçalho do bloco
- Atualizar **Status geral** no topo do arquivo

Ao bloquear:
- Trocar por `[!]` + link para `docs/incidents/block-N-failure-YYYY-MM-DD.md`

Ao completar um bloco:
- Trocar status para ✅ completo com data
- Atualizar **Status geral** no topo do arquivo para o próximo bloco
- Commitar este arquivo como parte do mesmo commit que fechou o bloco
