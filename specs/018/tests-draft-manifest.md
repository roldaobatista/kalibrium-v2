# Slice 018 — Manifesto de testes RED

**Gerado por:** builder (modo: test-writer)
**Data:** 2026-04-18
**Estado esperado:** RED (todos os testes devem falhar antes da implementacao)
**Rastreabilidade (ADR-0017):** 100% dos testes referenciam AC-NNN no nome (`AC-001:`, `AC-002:`, ...)

---

## Runner

- Runner: `node --test` (Node builtin test runner — zero dependencia)
- Formato: `*.test.cjs` (CommonJS; repo ja usa ESM em src/, CJS em scripts de teste)

### Comando master (roda tudo)

```bash
node --test tests/ci/ tests/scripts/ tests/agents/
```

### Comandos individuais

```bash
node --test tests/ci/test-regression-workflow.test.cjs
node --test tests/scripts/detect-shared-file-change.test.cjs
node --test tests/scripts/smoke-tests.test.cjs
node --test tests/scripts/validate-audit-prompt.test.cjs
node --test tests/agents/contamination-refusal.test.cjs
node --test tests/scripts/audit-set-difference.test.cjs
node --test tests/scripts/validate-gate-output.test.cjs
node --test tests/agents/output-section-present.test.cjs
node --test tests/scripts/merge-slice-manifest.test.cjs
node --test tests/scripts/check-forbidden-path.test.cjs
node --test tests/agents/paths-section-present.test.cjs
```

---

## Mapa arquivo -> ACs -> #testes

| Arquivo | ACs cobertos | #tests | Modulo alvo |
|---|---|---|---|
| `tests/ci/test-regression-workflow.test.cjs` | AC-001, AC-001-A | 7 | `.github/workflows/test-regression.yml` |
| `tests/scripts/detect-shared-file-change.test.cjs` | AC-002, AC-002-A | 9 | `scripts/detect-shared-file-change.sh` |
| `tests/scripts/smoke-tests.test.cjs` | AC-002 | 6 | `scripts/smoke-tests.sh` + `scripts/pre-push` |
| `tests/scripts/validate-audit-prompt.test.cjs` | AC-003, AC-003-A | 11 | `scripts/validate-audit-prompt.sh` + `docs/protocol/audit-prompt-template.md` + `blocked-tokens-re-audit.txt` |
| `tests/agents/contamination-refusal.test.cjs` | AC-004 | 6 (5 parametrizados + 1 fixture) | 5 agent files (`qa-expert`, `architecture-expert`, `security-expert`, `product-expert`, `governance`) |
| `tests/scripts/audit-set-difference.test.cjs` | AC-004-A | 7 | `scripts/audit-set-difference.sh` |
| `tests/scripts/validate-gate-output.test.cjs` | AC-005, AC-006-A | 7 | `scripts/validate-gate-output.sh` |
| `tests/agents/output-section-present.test.cjs` | AC-005-A | 20 (5 agents × 4 testes) | 5 agent files |
| `tests/scripts/merge-slice-manifest.test.cjs` | AC-006, AC-006-A | 6 | `specs/018/merge-slice-update-manifest.md` |
| `tests/scripts/check-forbidden-path.test.cjs` | AC-007-A | 9 | `scripts/check-forbidden-path.sh` + `docs/protocol/forbidden-paths.txt` |
| `tests/agents/paths-section-present.test.cjs` | AC-007 | 49 (1 existencia + 12 agents × 4 testes) | 12 agent files |
| **TOTAL** | **14/14 ACs** | **~137 tests** | — |

---

## Fixtures criadas (T01 do plan)

### `tests/fixtures/audit-prompts/`
- `1st-pass-valid.md` — 6 campos presentes
- `1st-pass-invalid-missing-output-contract.md` — falta output_contract
- `re-audit-clean.md` — sem tokens proibidos
- `re-audit-contaminated-token-fix.md` — "foi corrigido" + "Verifique se X foi"
- `re-audit-contaminated-finding-id.md` — ID `VER-019-003`
- `re-audit-contaminated-commit-hash.md` — `a1b2c3d4e5f` adjacente a "fix"

### `tests/fixtures/gate-output/`
- `valid-verify.json` — JSON canonico correto
- `invalid-schema-url.json` — `$schema` como URL
- `invalid-gate-name.json` — `gate: "security"` (fora do enum)
- `invalid-missing-slice.json` — campo `slice` ausente
- `rejection-contaminated-prompt.json` — exemplo de rejeicao do AC-004

### `tests/fixtures/set-diff/`
- `previous-findings.json` — 3 findings (VER-019-001/002/003)
- `current-findings.json` — 2 findings (VER-019-010/011), 1 mantido (AC-001), 2 removidos, 1 novo

### `tests/fixtures/pre-push/`
- `docs-only-diff.txt` — apenas docs/
- `shared-file-diff.txt` — src/main.tsx + vite.config.ts
- `src-non-shared-diff.txt` — src/ sem arquivos compartilhados

---

## Rastreabilidade AC-ID (ADR-0017)

**Metodo adotado:** nome do teste comeca com `AC-NNN:` ou `AC-NNN-A:`.

Exemplos:
- `test('AC-001: workflow test-regression.yml existe em .github/workflows/', ...)`
- `test('AC-007-A: "frontend/foo.ts" retorna exit 1 com mensagem ContractViolation', ...)`

Nenhum teste e `@helper` ou `@setup` — todos exercitam ACs diretamente.

---

## Estado esperado na primeira execucao: RED

Motivos de RED (todos esperados):

1. `scripts/detect-shared-file-change.sh` nao existe → `ENOENT` em spawnSync
2. `scripts/smoke-tests.sh` nao existe → `fs.existsSync` false
3. `scripts/pre-push` nao invoca os scripts novos → match falha
4. `scripts/validate-audit-prompt.sh` nao existe → exit code !== 0|1 esperado
5. `docs/protocol/audit-prompt-template.md` nao existe → `fs.existsSync` false
6. `docs/protocol/blocked-tokens-re-audit.txt` nao existe → `fs.existsSync` false
7. `scripts/audit-set-difference.sh` nao existe
8. `scripts/validate-gate-output.sh` nao existe
9. `scripts/check-forbidden-path.sh` nao existe
10. `docs/protocol/forbidden-paths.txt` nao existe
11. `.github/workflows/test-regression.yml` nao existe
12. 5 agent files sem secao `## Saida obrigatoria` com literais canonicos
13. 12 agent files sem secao `## Paths do repositorio`
14. `specs/018/merge-slice-update-manifest.md` nao existe
15. Nenhum teste em `tests/e2e/` esta tagueado `@smoke`

---

## Decisoes nao-obvias de formato (reportadas ao orchestrator)

1. **Runner Node builtin vs Pest/Vitest:** optei por `node --test` (CJS) porque (a) testes aqui sao de harness (shell scripts + YAML + markdown + JSON), nao de produto PHP; (b) `node --test` e zero-dep, ja embarcado no Node 18+; (c) Pest 4 e especifico para PHP e Vitest nao e usado neste repo ainda. Se o orchestrator preferir Vitest (`tests/harness/*.test.ts`), e migracao mecanica com `describe/it`.
2. **Fixtures em `tests/fixtures/` vs `tests/harness/fixtures/` (do plan T01):** adotei `tests/fixtures/` por simetria com convencao do prompt. Plan T01 usa `tests/harness/fixtures/` — implementer pode renomear se preferir seguir o plan literalmente; ajuste e 1 find-replace nos paths.
3. **Detect-shared-file stdin vs argv:** o script recebe diff via stdin (1 arquivo por linha) em vez de argv. Razao: compat natural com `git diff --name-only @{push}..HEAD | ./detect-shared-file-change.sh`. Se plan preferir argv, trocar nas 9 invocacoes do test.
4. **audit-set-difference args:** usei `--previous X --current Y` (CLI explicita). Plan T09 nao fixou forma — implementer pode mudar para `$1 $2` posicional.
5. **output-section test: enum lido do schema:** o teste valida que o exemplo JSON do agent file usa `gate` dentro do enum do schema canonico. Se o schema for atualizado, testes re-executam contra novo enum automaticamente.

---

## Proximas etapas (fora do escopo deste modo)

1. `qa-expert` (audit-tests-draft) audita este manifesto + os 11 arquivos de teste → `specs/018/tests-draft-audit.json`
2. Se aprovado (verdict=approved, findings=[]), `builder:implementer` inicia T01→T16 do plan
3. Cada task do plan deve virar pelo menos 1 grupo de testes green aqui
