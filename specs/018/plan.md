# Plano técnico do slice 018 — Harness: CI regression + bias-free audit + schema uniformity

**Gerado por:** architecture-expert (modo: plan)
**Status:** draft
**Spec de origem:** `specs/018/spec.md`
**Branch:** `feat/slice-018-harness-regression-bias-schema`
**HEAD base:** `14bc83a`
**Lane:** L3 (harness; impacto cross-cutting em hooks, workflows e agent files)

---

## Estratégia global

Slice puramente de harness — nenhuma mudança em produto (`src/`) ou migrations. Endereça 4 débitos (B-036, B-037, B-038, B-041) criando **7 scripts bash novos** + **1 workflow CI** + **3 documentos de protocolo** + edições em **12 agent files** + um manifesto de atualização do `scripts/merge-slice.sh` (arquivo **selado** — aplicação exige relock manual pelo PM). Estratégia: primeiro fixtures + scripts isolados com testes, depois cablear hooks/workflows, depois atualizar agent files (textual), por último o manifesto do merge-slice para o PM aplicar via `relock-harness.sh`. Preserva R1-R16 e P1-P9; não revoga nenhum princípio; atua como **operacionalização mecânica** de R3/R11 já existentes na constitution.

---

## Decisões arquiteturais

### D1: Workflow CI dedicado `test-regression.yml` vs estender workflow existente
**Opções consideradas:**
- **Opção A (escolhida):** criar `.github/workflows/test-regression.yml` dedicado com triggers `pull_request` + `push` e matrix de projects Playwright.
- **Opção B:** estender workflow existente (se houver) adicionando job `test-regression`.

**Escolhida:** A.

**Razão:** separação de responsabilidades, evita colisão com jobs existentes, idempotente em PRs abertos (check novo não re-dispara checks antigos). Workflow dedicado facilita desativação cirúrgica se causar falso positivo.

**Reversibilidade:** fácil — deletar o arquivo + ajustar ruleset.

**AC coberta:** AC-001, AC-001-A.

---

### D2: Detector de arquivo compartilhado stateless via lista fechada
**Opções consideradas:**
- **Opção A (escolhida):** `scripts/detect-shared-file-change.sh` com **lista hardcoded** de 7 paths (`src/main.tsx`, `vite.config.ts`, `package.json`, `capacitor.config.ts`, `playwright.config.ts`, `.claude/settings*.json`), exit 0 sempre, stdout `shared_changed=true|false`.
- **Opção B:** lista configurável em arquivo externo `docs/protocol/shared-files.txt`.

**Escolhida:** A.

**Razão:** lista pequena, muda raramente, script auto-contido (mais fácil revisar em PR). Opção B agrega indireção sem benefício. Convenção Unix (exit 0 + stdout) evita ambiguidade no pre-push hook.

**Reversibilidade:** fácil.

**AC coberta:** AC-002, AC-002-A.

---

### D3: Smoke suite por tag Playwright `@smoke` (10-15 testes), cap <30s
**Opções consideradas:**
- **Opção A (escolhida):** tag `@smoke` nos testes críticos existentes + `scripts/smoke-tests.sh` que invoca `npx playwright test --grep @smoke`.
- **Opção B:** lista manual de arquivos em `scripts/smoke-tests.sh`.
- **Opção C:** subfolder `tests/smoke/` duplicando testes.

**Escolhida:** A.

**Razão:** tag é padrão nativo do Playwright, zero duplicação, seleção declarativa. B cria drift (adicionar teste sem atualizar lista); C duplica código.

**Reversibilidade:** fácil — remover tags.

**AC coberta:** AC-002. **Risco:** R1 do spec.

---

### D4: Template + validator de prompt de auditoria
**Opções consideradas:**
- **Opção A (escolhida):** template markdown `docs/protocol/audit-prompt-template.md` (6 campos obrigatórios) + validator bash `scripts/validate-audit-prompt.sh --mode=(1st-pass|re-audit)` que lê lista fechada `docs/protocol/blocked-tokens-re-audit.txt`.
- **Opção B:** validator Python com tokenizer semântico (word embeddings).

**Escolhida:** A.

**Razão:** tokens proibidos são **literais** (não ambíguos), regex bash suficiente, auditável em code-review. B introduz dependência Python + ML, custo desproporcional.

**Reversibilidade:** fácil.

**AC coberta:** AC-003, AC-003-A.

---

### D5: Recusa pelo agente via `rejection_reason: "contaminated_prompt"`
**Opções consideradas:**
- **Opção A (escolhida):** agent file instrui o sub-agent a emitir JSON canônico com `verdict: "rejected"` + campo extra `rejection_reason` + `contamination_evidence`, sem preencher `evidence.ac_coverage_map` nem `evidence.checks`.
- **Opção B:** hook externo `auditor-input-lint.sh` que intercepta mecanicamente antes do sub-agent ler o prompt.

**Escolhida:** A.

**Razão:** spec já declarou B fora-de-escopo ("S5 advisory futuro"). A é suficiente: agente auto-disciplinado + `validate-audit-prompt.sh` como 1ª barreira. Dual-layer barato: validator mecânico (forte) + recusa do agente (complementar).

**Reversibilidade:** média — requer edição de 5 agent files.

**AC coberta:** AC-004.

---

### D6: Set-difference por assinatura semântica (script auxiliar)
**Opções consideradas:**
- **Opção A (escolhida):** `scripts/audit-set-difference.sh` lê 2 JSONs (rodada N e N-1), normaliza findings como `categoria + descrição_normalizada + path_sem_linha`, emite 3 listas (`resolved`, `unresolved`, `new`).
- **Opção B:** lógica embutida no orchestrator (markdown + manual).

**Escolhida:** A.

**Razão:** mecanizar garante consistência entre rodadas. Script único consumível por qualquer skill (`/fix`, `/retrospective`).

**Reversibilidade:** fácil.

**AC coberta:** AC-004-A. **Risco:** R2 do spec — aceitar 5% falso match como dívida documentada.

---

### D7: Validator de gate output com enum lido do schema JSON (não hardcoded)
**Opções consideradas:**
- **Opção A (escolhida):** `scripts/validate-gate-output.sh` usa `jq` para extrair `enum` de `docs/protocol/schemas/gate-output.schema.json` em runtime e valida contra o JSON do gate.
- **Opção B:** enum hardcoded no validator.

**Escolhida:** A.

**Razão:** single source of truth (schema JSON canônico). Adicionar novo gate ao protocolo = apenas editar schema, validator segue automaticamente. B cria drift inevitável.

**Reversibilidade:** fácil.

**AC coberta:** AC-005, AC-006-A.

---

### D8: Manifesto de alteração do `merge-slice.sh` (não editar direto — selado)
**Opções consideradas:**
- **Opção A (escolhida):** criar `specs/018/merge-slice-update-manifest.md` descrevendo (a) invocação de `validate-gate-output.sh` como pré-check, (b) mapeamento de aliases legacy (`"code-review" → "review"`, `"security" → "security-gate"`, `"functional" → "functional-gate"`) para compat com slices 001-017, (c) instruções exatas para o PM aplicar via `relock-harness.sh` em terminal externo. Slice 018 **NÃO edita** `merge-slice.sh` diretamente.
- **Opção B:** editar `merge-slice.sh` diretamente (bloqueado mecanicamente por hooks-lock).

**Escolhida:** A.

**Razão:** respeita CLAUDE.md §9 (arquivos selados). Manifesto é artefato consumível pelo PM sem ambiguidade. Acoplamento temporal: slice 018 merge primeiro, depois PM executa relock.

**Reversibilidade:** fácil — manifesto é texto.

**AC coberta:** AC-006 (preparação; execução fica com PM).

---

### D9: Seção "Saída obrigatória" em 5 agent files com exemplo JSON canônico inline
**Opções consideradas:**
- **Opção A (escolhida):** bloco markdown padronizado com literais do schema + exemplo JSON mínimo válido + referência ao validator.
- **Opção B:** delegação total ao protocolo (apenas link).

**Escolhida:** A.

**Razão:** o sub-agent lê seu agent file completo na invocação; exemplo inline elimina 1 hop de leitura e reduz inferência errada. Custo: 5 arquivos, ~30 linhas cada.

**Reversibilidade:** fácil.

**AC coberta:** AC-005-A.

---

### D10: Seção "Paths do repositório" em 12 agent files + `check-forbidden-path.sh`
**Opções consideradas:**
- **Opção A (escolhida):** seção idêntica em todos os 12 agent files + script mecânico validador de prefixos proibidos (`frontend/`, `backend/`, `mobile/`, `apps/`).
- **Opção B:** apenas script + guideline no CLAUDE.md.

**Escolhida:** A.

**Razão:** sub-agent lê seu próprio agent file na invocação, não CLAUDE.md completo. Duplicar a seção nos 12 arquivos tem custo de edição único e benefício contínuo.

**Reversibilidade:** média — requer edição de 12 arquivos.

**AC coberta:** AC-007, AC-007-A.

---

### D11: Estratégia de PR — 1 PR único (não 4 fragmentados)
**Opções consideradas:**
- **Opção A (escolhida):** 1 PR para o slice inteiro (B-036+B-037+B-038+B-041).
- **Opção B:** 4 PRs, um por débito.

**Escolhida:** A.

**Razão:** os 4 débitos compartilham contexto (agent files, schemas, scripts auxiliares) e a spec descreve 1 slice único. 4 PRs causariam rebase/conflict em agent files (AC-005-A e AC-007 tocam overlapping set). Aceitamos custo de review maior por PR em troca de coerência.

**Reversibilidade:** fácil.

---

## Mapeamento AC → Decisões → Arquivos

| AC | Decisões | Arquivos tocados | Teste principal |
|---|---|---|---|
| AC-001 | D1 | `.github/workflows/test-regression.yml` (novo) | CI dispara e falha em PR quebrado (teste manual + fixture branch) |
| AC-001-A | D1 | mesmo + fixture branch com teste quebrado | CI log aponta AC violado (`ac-001-dev-server`) |
| AC-002 | D2, D3 | `scripts/detect-shared-file-change.sh` (novo), `scripts/smoke-tests.sh` (novo), `scripts/pre-push` (editar) | `tests/harness/test-pre-push-smoke.sh` |
| AC-002-A | D2 | `scripts/detect-shared-file-change.sh` | fixture: commit só em `docs/` → pre-push passa direto |
| AC-003 | D4 | `docs/protocol/audit-prompt-template.md` (novo), `scripts/validate-audit-prompt.sh` (novo) | fixture `prompts/1st-pass-valid.md` + `invalid.md` |
| AC-003-A | D4 | `docs/protocol/blocked-tokens-re-audit.txt` (novo), validator `--mode=re-audit` | fixtures com cada token proibido |
| AC-004 | D5 | 5 agent files (`qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`, `governance.md`) — seção "Recusa por contaminação" | fixture JSON de rejeição validado por `validate-gate-output.sh` |
| AC-004-A | D6 | `scripts/audit-set-difference.sh` (novo) | fixtures 2 JSONs → 3 listas esperadas |
| AC-005 | D7 | `scripts/validate-gate-output.sh` (novo) | 3 JSONs fixture (1 válido + 3 inválidos) |
| AC-005-A | D9 | 5 agent files (seção `## Saída obrigatória`) | `grep -q "gate-output-v1"` + `jq` parse no exemplo inline |
| AC-006 | D8 | `specs/018/merge-slice-update-manifest.md` (novo); **NÃO edita** `scripts/merge-slice.sh` | manifesto validado por revisão; execução real pós-merge pelo PM |
| AC-006-A | D7 | mesmo validator + 3 fixtures inválidas | `tests/harness/validate-gate-output.test.sh` |
| AC-007 | D10 | 12 agent files (seção `## Paths do repositório`) | grep automatizado: cada agent file contém a seção |
| AC-007-A | D10 | `scripts/check-forbidden-path.sh` (novo), `docs/protocol/forbidden-paths.txt` (novo) | fixture com 4 paths (2 válidos + 2 proibidos) |

**Cobertura:** 14/14 ACs (100%). Toda AC tem ≥1 decisão e ≥1 teste mecânico.

---

## Tasks numeradas

> Convenção: cada task tem Arquivos, ACs cobertas, Pré-condição, Critério mecânico de sucesso.

### T01 — Preparar `tests/harness/` e fixtures comuns
- **Arquivos:**
  - `tests/harness/fixtures/gates/valid-verify.json`
  - `tests/harness/fixtures/gates/invalid-schema-url.json`
  - `tests/harness/fixtures/gates/invalid-gate-name.json`
  - `tests/harness/fixtures/gates/invalid-missing-slice.json`
  - `tests/harness/fixtures/prompts/1st-pass-valid.md`
  - `tests/harness/fixtures/prompts/1st-pass-invalid-missing-output-contract.md`
  - `tests/harness/fixtures/prompts/re-audit-clean.md`
  - `tests/harness/fixtures/prompts/re-audit-contaminated-token-fix.md`
  - `tests/harness/fixtures/prompts/re-audit-contaminated-finding-id.md`
  - `tests/harness/fixtures/prompts/re-audit-contaminated-commit-hash.md`
  - `tests/harness/fixtures/sets/previous-findings.json`
  - `tests/harness/fixtures/sets/current-findings.json`
- **ACs cobertas:** suporta AC-003/003-A/004/004-A/005/006-A.
- **Pré-condição:** nenhuma.
- **Critério mecânico:** 12 arquivos criados; `jq . <json>` ok em cada JSON; `.md` com frontmatter mínimo.

### T02 — `scripts/detect-shared-file-change.sh`
- **Arquivos:** `scripts/detect-shared-file-change.sh`.
- **ACs cobertas:** AC-002, AC-002-A.
- **Pré-condição:** T01.
- **Critério mecânico:** `set -euo pipefail`; `shellcheck scripts/detect-shared-file-change.sh` exit 0; commit só em `docs/` → stdout `shared_changed=false` exit 0; commit tocando `src/main.tsx` → stdout `shared_changed=true` exit 0.

### T03 — Taggear testes `@smoke` + criar `scripts/smoke-tests.sh`
- **Arquivos:** 10-15 `test()` em `tests/e2e/**.spec.ts` recebem tag `{ tag: ['@smoke'] }`; `scripts/smoke-tests.sh` novo.
- **ACs cobertas:** AC-002.
- **Pré-condição:** T02.
- **Critério mecânico:** `npx playwright test --grep @smoke --list` retorna 10-15 testes; `scripts/smoke-tests.sh` exit 0 em suite verde; total <30s.

### T04 — Atualizar hook `scripts/pre-push`
- **Arquivos:** `scripts/pre-push`.
- **ACs cobertas:** AC-002, AC-002-A.
- **Pré-condição:** T02, T03.
- **Critério mecânico:** push com diff só em `docs/` → smoke não executa; push tocando `package.json` → smoke executa; smoke vermelho bloqueia push.

### T05 — Workflow `.github/workflows/test-regression.yml`
- **Arquivos:** `.github/workflows/test-regression.yml`.
- **ACs cobertas:** AC-001, AC-001-A.
- **Pré-condição:** nenhuma.
- **Critério mecânico:** `actionlint .github/workflows/test-regression.yml` exit 0 (se disponível); PR de teste dispara o check; check fica `failure` se `npm run test:scaffold` falha; log aponta AC violado.

### T06 — `docs/protocol/audit-prompt-template.md` + `blocked-tokens-re-audit.txt`
- **Arquivos:** `docs/protocol/audit-prompt-template.md`, `docs/protocol/blocked-tokens-re-audit.txt`.
- **ACs cobertas:** AC-003, AC-003-A.
- **Pré-condição:** T01.
- **Critério mecânico:** template contém literalmente 6 campos (`story_id`, `slice_id`, `mode`, `perimeter_files`, `criteria_checklist`, `output_contract`); `blocked-tokens-re-audit.txt` contém ≥12 tokens do AC-003-A.

### T07 — `scripts/validate-audit-prompt.sh`
- **Arquivos:** `scripts/validate-audit-prompt.sh`.
- **ACs cobertas:** AC-003, AC-003-A.
- **Pré-condição:** T01, T06.
- **Critério mecânico:** `--mode=1st-pass` em fixture válida exit 0; sem `output_contract` exit 1 apontando campo; `--mode=re-audit` em fixture limpa exit 0; em fixture com `foi corrigido` exit 1 reportando linha+token.

### T08 — `scripts/validate-gate-output.sh`
- **Arquivos:** `scripts/validate-gate-output.sh`.
- **ACs cobertas:** AC-005, AC-006-A.
- **Pré-condição:** T01.
- **Critério mecânico:** requer `jq`; lê enum direto do schema; `valid-verify.json` exit 0; `invalid-schema-url.json` exit 1 msg `$schema`; `invalid-gate-name.json` exit 1 msg `gate`; `invalid-missing-slice.json` exit 1 msg `slice`.

### T09 — `scripts/audit-set-difference.sh`
- **Arquivos:** `scripts/audit-set-difference.sh`.
- **ACs cobertas:** AC-004-A.
- **Pré-condição:** T01.
- **Critério mecânico:** 2 JSONs de fixture → 3 listas (`resolved`, `unresolved`, `new`) com contagens esperadas; normalização (lowercase, trim, sem linha) aplicada.

### T10 — `scripts/check-forbidden-path.sh` + `forbidden-paths.txt`
- **Arquivos:** `scripts/check-forbidden-path.sh`, `docs/protocol/forbidden-paths.txt`.
- **ACs cobertas:** AC-007-A.
- **Pré-condição:** nenhuma.
- **Critério mecânico:** `frontend/foo.ts` → exit 1 msg `ContractViolation`; `src/main.tsx` → exit 0; `backend/x` → exit 1; `mobile/app.ts` → exit 1.

### T11 — Atualizar 5 agent files com `## Saída obrigatória` + política de recusa
- **Arquivos:** `.claude/agents/qa-expert.md`, `architecture-expert.md`, `security-expert.md`, `product-expert.md`, `governance.md`.
- **ACs cobertas:** AC-004, AC-005-A.
- **Pré-condição:** T08.
- **Critério mecânico:** `grep -l "## Saída obrigatória" .claude/agents/*.md` retorna 5 arquivos; cada contém literais `gate-output-v1`, `slice`, `gate`; JSON exemplo parseia com `jq`; seção "Recusa por contaminação" presente com `rejection_reason: "contaminated_prompt"`.

### T12 — Atualizar 12 agent files com `## Paths do repositório`
- **Arquivos:** 12 em `.claude/agents/` (orchestrator + 11 sub-agents).
- **ACs cobertas:** AC-007.
- **Pré-condição:** T10.
- **Critério mecânico:** `grep -l "## Paths do repositório" .claude/agents/*.md` retorna 12; cada contém 9 paths raiz + guardrail `NÃO existe subpasta frontend/` + instrução `Glob antes de Read`.

### T13 — Atualizar `docs/protocol/06-estrategia-evidencias.md`
- **Arquivos:** `docs/protocol/06-estrategia-evidencias.md`.
- **ACs cobertas:** suporta AC-003/003-A/004 (referência normativa).
- **Pré-condição:** T06, T07.
- **Critério mecânico:** nova seção "Auditoria sem bias" descreve template + tokens proibidos + set-difference; link para scripts; sem revogar nada existente.

### T14 — Criar `specs/018/merge-slice-update-manifest.md`
- **Arquivos:** `specs/018/merge-slice-update-manifest.md`.
- **ACs cobertas:** AC-006.
- **Pré-condição:** T08.
- **Critério mecânico:** manifesto documenta (a) trecho exato a adicionar em `merge-slice.sh` invocando `validate-gate-output.sh`; (b) tabela de aliases legacy `{"code-review":"review","security":"security-gate","functional":"functional-gate"}`; (c) comando `KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh`; (d) aviso "slice 018 não edita merge-slice.sh; aplicação pós-merge pelo PM em terminal externo".

### T15 — Testes mecânicos de validação dos scripts
- **Arquivos:** `tests/harness/run-all.sh` (+ scripts auxiliares bash se necessário).
- **ACs cobertas:** AC-002/002-A/003/003-A/004/004-A/005/005-A/006-A/007-A.
- **Pré-condição:** T01-T12.
- **Critério mecânico:** `bash tests/harness/run-all.sh` exit 0; cobertura: detect-shared, validate-prompt, validate-gate-output, audit-set-difference, check-forbidden-path, grep de seções em agent files.

### T16 — Commit atômico final + gates
- **Arquivos:** nenhum novo.
- **ACs cobertas:** organiza entrega.
- **Pré-condição:** T01-T15.
- **Critério mecânico:** `bash scripts/sequencing-check.sh --story` passa; `pre-commit-gate` + `settings-lock` + `hooks-lock` exit 0; commit `feat(slice-018): harness — CI regression + bias-free audit + schema uniformity`.

---

## Ordem de execução (DAG)

```
T01 (fixtures)
 ├─→ T02 (detect-shared) ──┐
 │                         ├─→ T04 (pre-push hook) ───┐
 │        T03 (smoke) ─────┘                          │
 │                                                    │
 ├─→ T06 (template+tokens) ──→ T07 (validate-prompt) ─┤
 │                                                    │
 ├─→ T08 (validate-gate) ──┬──→ T11 (5 agent files)   │
 │                         └──→ T14 (manifest)        │
 │                                                    │
 ├─→ T09 (set-diff)                                   │
 │                                                    │
 ├─→ T10 (forbidden-path) ──→ T12 (12 agent files)    │
 │                                                    │
 └─→ T05 (CI workflow)   [independente]               │
                                                      │
             T13 (protocol doc) ←── T06, T07          │
                                                      ▼
                                          T15 (testes mecânicos)
                                                      │
                                                      ▼
                                          T16 (commit final)
```

**Paralelismo:** T02/T03/T05/T06/T08/T09/T10 após T01. T04 espera T02+T03. T11 espera T08. T12 espera T10. T13 espera T06+T07. T14 espera T08. T15 espera T01-T12. T16 espera tudo.

---

## Riscos e mitigações

- **R1 (spec) — smoke lenta:** cap 15 testes `@smoke`, paralelismo Playwright, target <30s; fallback async no pre-push se exceder; CI mantém bloqueio. **Mitigação em T03.**
- **R2 (spec) — falso match set-diff:** normalização padrão + tolerância documentada 5%; registrar em `06-estrategia-evidencias.md`. **Mitigação em T09+T13.**
- **R3 (spec) — agent files + verifier-sandbox.sh selado:** antes de T11/T12, `grep "\.claude/agents" scripts/hooks/verifier-sandbox.sh` para confirmar se há referência por nome/checksum. Se houver, abortar T11/T12 e delegar via relock manual pelo PM.
- **R4 (spec) — workflow duplicado:** T05 inspeciona `.github/workflows/*.yml` antes de criar; se existir job de test, consolidar em vez de duplicar.
- **R5 (spec) — paths desatualizam:** adicionar nota em CLAUDE.md §9 como advisory na retrospectiva (**out of scope deste slice**, registrar como débito).
- **R6 (novo) — merge-slice.sh selado:** T14 produz manifesto, NÃO edita. Execução pós-merge pelo PM. Risco: se PM esquecer relock, aliases legacy não funcionam e re-verificações retroativas em slices 001-017 rejeitam. **Mitigação:** T14 inclui checklist + lembrete na retrospectiva.
- **R7 (novo) — fixtures desincronizam do schema:** T01 cria JSONs contra schema v1.2.4 atual; se schema mudar, fixtures precisam revisão. **Mitigação:** T15 valida fixtures contra schema em runtime via `validate-gate-output.sh` — falha explícita.

---

## Dependências externas

- **Libs novas:** **nenhuma.** Usa apenas `bash`, `jq`, `git`, `npm`/`npx` (já instalados). `actionlint` (opcional em T05), `shellcheck` (opcional em T02-T10) — ambos não bloqueantes.
- **Sem `npm install`.**
- **GitHub Actions:** ativo, ruleset já bloqueia merge com check failures.
- **Permissões de repo para workflow:** `contents: read` + `pull-requests: read` (padrão).

---

## Dependências de outros slices

- **slice-017** — merged; usado como referência de evidência (teste `ac-001-dev-server` será o canário de regressão em T05).
- **Nenhum bloqueio inter-épico:** slice de harness L3 não entra em R14 (que trata épicos MVP de produto).

---

## Fora de escopo deste plano (confirmando spec)

- B-039 (telemetria de slice), B-040 (limite S4 mesmo cluster).
- Criar novos sub-agents.
- Refatorar protocolo v1.2.4 (só adição em `06-estrategia-evidencias.md`).
- Rodar gates retroativamente em slices 001-017.
- Hook mecânico `auditor-input-lint.sh` — S5 advisory futuro.
- **Editar `scripts/merge-slice.sh` diretamente** — impossibilitado por selo; delegado ao PM via manifesto T14.

---

## Política de PR

**1 PR único** para o slice 018, cobrindo B-036+B-037+B-038+B-041. Justificativa em D11. Estimativa: ~30 arquivos tocados, revisão focada por dimensão (scripts / agent files / protocolo / CI).

---

## Reversibilidade global do slice

**Fácil.** Nenhuma migration, nenhuma lib nova, nenhum acoplamento externo. Reverter = `git revert <merge-commit>` + PM desativa workflow CI (1 clique no GitHub). Agent files voltam via revert. Único ponto irreversível de facto é o manifesto T14 aplicado pelo PM — mas esse é commit separado, também revertível.
