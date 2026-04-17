# Handoff — 2026-04-17 — Slice 016 (E15-S02) com planejamento + testes red fechados

## Resumo curto

Slice 016 ("Scaffold React + TypeScript + Ionic + Capacitor + Vite" — story E15-S02) concluiu toda a **fase pré-implementação** na branch `feat/slice-016-scaffold-frontend`:

- 14 ACs escritos, sequenciais e auditados (AC-001..AC-014)
- 10 decisões arquiteturais com alternativas + reversibilidade
- 42 testes red (Playwright e2e + Node --test scaffold) com AC-ID rastreável via dupla âncora (`@covers AC-NNN` docblock + `describe('AC-NNN: ...')`)
- Todos os 6 gates de pré-implementação APPROVED

Próximo passo obrigatório: invocar `builder:implementer` (Opus, budget 80k) para fazer os 14 ACs virarem verdes.

## Estado ao sair

- **Branch ativa:** `feat/slice-016-scaffold-frontend`
- **Último commit:** `0e1f7bf` (testes red + audit-tests-draft approved)
- **Main:** `1d0f6f8` (PR #46 merged — correção de falso-positivo AMPLIATION-RECOVERY-001)
- **Working tree:** limpo (todas mudanças da sessão commitadas; os `.bak` antigos e outros untracked são pré-existentes).

## Commits da sessão (6 ao todo)

Via PR #46 (main):
- `1d0f6f8` `chore(state): corrige falso positivo AMPLIATION-RECOVERY-001`

Na branch feat/slice-016-scaffold-frontend:
1. `1212e8b` `feat(slice-016): inicia E15-S02 scaffold React+Ionic+Capacitor` — spec inicial com 14 ACs
2. `c60004d` `chore(slice-016): aplica findings F-001/F-003 + re-audit approved` — audit-spec loop (frontmatter + AC-002 ampliado + nota AC-ID)
3. `a1afeaf` `feat(slice-016): plan.md + audits approved (spec emendada AC-008)` — plan 10 decisões + plan-review loop (emenda AC-008 `/emails/`)
4. `fdfab2c` `test(slice-016): AC tests red (14/14 ACs cobertos)` — 42 testes red por Playwright + Node --test
5. `0e1f7bf` `test(slice-016): audit-tests-draft approved + fix AC-012 seed` — correção `eslint-disable` + audit-tests-draft approved

## Pipeline de gates (slice 016) — todos pré-implementação

| Gate | Agente/Modo | Instância | Verdict | Findings |
|---|---|---|---|---|
| draft-spec (mecânico) | scripts/draft-spec.sh | — | OK | 14 ACs AC-001..AC-014 sequenciais |
| **audit-spec** | qa-expert:audit-spec | **03** | **approved** | 0 bloqueantes, 1 S4 (HARNESS-MIGRATION-002) |
| draft-plan (mecânico) | scripts/draft-plan.sh | — | OK | 10 decisões, 14/14 ACs mapeados |
| **plan-review** | architecture-expert:plan-review | **02** | **approved** | 0 findings em todas severidades |
| draft-tests (mecânico) | builder:test-writer | — | 42 tests, 14/14 ACs rastreáveis | red confirmado (64 falhas) |
| **audit-tests-draft** | qa-expert:audit-tests-draft | **03** | **approved** | 0 bloqueantes, 7/7 critérios §16.1 true |

### Findings resolvidos durante ciclo (todos os ciclos fixer→re-audit ficaram dentro de 5 iterações — R6 não disparou)

- **F-001 (S2, audit-spec)** — frontmatter YAML ausente → aplicado
- **F-002 (S2→S4, audit-spec)** — formato `AC-NNN` vs `AC-NNN-XXX` → reclassificado S4 por HARNESS-MIGRATION-002 (conflito protocol §10.1 vs validador mecânico; trace preserved)
- **F-003 (S3, audit-spec)** — AC-002 sem verificar conteúdo dist/assets → aplicado (exige ≥1 .js e ≥1 .css)
- **F-001 (S3, plan-review)** — plan D8 divergia da spec em AC-008 (exceção `/emails/`) → spec emendada (AC-008 + AC-013 + fora-de-escopo) + re-audit completo (audit-spec instance_03 + plan-review instance_02 ambos approved)
- **F-S2 (audit-tests-draft)** — AC-012 seed com `eslint-disable-next-line` neutralizaria violação → linha removida

## Arquivos criados/modificados

### specs/016/ (8 arquivos)
- `spec.md` — 14 ACs, frontmatter YAML, nota AC-ID format, emenda AC-008/AC-013 `/emails/`
- `plan.md` — 10 decisões (D1 Vite · D2 Ionic Router · D3 flat structure · D4 Capacitor subset · D5 ESLint flat · D6 Ionic Grid · D7 Playwright · D8 descarte Blade · D9 server config · D10 Node tests)
- `spec-audit.json` — instance_03 approved
- `plan-review.json` — instance_02 approved
- `tests-draft-audit.json` — instance_03 approved
- `test-run-output.txt` — evidência red (64 falhas semânticas)
- `tasks.md` — esqueleto (implementer vai preencher)

### tests/ (13 arquivos)
- `tests/e2e/ac-001-dev-server.spec.ts` (AC-001, AC-009 — 2 tests Playwright)
- `tests/e2e/ac-006-layout-adaptive.spec.ts` (AC-006 — 2 tests)
- `tests/scaffold/ac-002-build-web.test.cjs` (AC-002 + AC-010 — 5 tests)
- `tests/scaffold/ac-003-cap-ios.test.cjs` (AC-003, skip !darwin — 4 tests)
- `tests/scaffold/ac-004-cap-android.test.cjs` (AC-004 — 5 tests)
- `tests/scaffold/ac-005-structure.test.cjs` (AC-005 + AC-011 — 12 tests)
- `tests/scaffold/ac-007-lint.test.cjs` (AC-007 + AC-012 — 4 tests, fix `eslint-disable` aplicado)
- `tests/scaffold/ac-008-legacy-removed.test.cjs` (AC-008 + AC-013 — 5 tests)
- `tests/scaffold/ac-014-capacitor-security.test.cjs` (AC-014 — 3 tests)
- `tests/scaffold/README.md`, `tests/Unit/README.md`
- `playwright.config.ts`

### Raiz
- `package.json` — scripts (dev/build/lint/test/test:e2e/test:scaffold) + devDependencies declaradas (React 18.3.1, TS 5.4.5, Ionic 8.2.6, Capacitor 6.1.2, Vite 5.2.11, Playwright 1.48.0, ESLint 9.11.0, Prettier 3.3.3) — **sem `npm install` rodado**

## Débito técnico (15 itens — sem nada novo nesta sessão)

Já registrado em `project-state.json → technical_debt`. Destaques relevantes:
- **HARNESS-MIGRATION-002**: AC-ID format `AC-NNN-XXX` (protocol §10.1) vs `AC-NNN` (validador mecânico + §16.1 + §4 + §8). Candidato a harness-learner R16.

## Próxima sessão — passo a passo

### 1. `/resume` (obrigatório em toda nova sessão)
Lê project-state.json + latest.md para restaurar contexto.

### 2. Confirmar branch ativa
```bash
git checkout feat/slice-016-scaffold-frontend
git status --short  # deve estar limpo
git log --oneline -5
# deve mostrar 0e1f7bf como HEAD (ou este handoff_commit)
```

### 3. Invocar `builder:implementer` em nova instância

**Budget esperado:** 80k tokens. **Duração estimada:** 20-45 min (dominado por I/O: npm install ~500MB, Vite builds, Playwright browser install, `npx cap add`).

**Pré-condições no ambiente do PM:**
- Node.js >= 20 (confirmado: v24.14.0)
- npm >= 10 (confirmado: 11.9.0)
- Android SDK (para AC-004 — `npx cap add android`): **verificar se está instalado** antes de disparar; se não, pode instalar via Android Studio ou SDK manager; alternativa: o implementer pode criar o projeto sem rodar `cap add android` e deixar AC-004 rodar no CI Linux/macOS
- Xcode: não existe no Windows — AC-003 vai skip (`process.platform !== 'darwin'`). Esperado e documentado em D4 do plan.

**Prompt sugerido para o implementer:**

> Modo **implementer** no slice 016. Faça os 14 ACs verdes seguindo fielmente `specs/016/plan.md` (10 decisões D1-D10). Zero desvio do plan sem justificar. Rode `npm install` na raiz, crie scaffold (`vite.config.ts`, `tsconfig.json`, `capacitor.config.ts`, `src/`, `eslint.config.js`, `.prettierrc`, etc.), delete `resources/views/*.blade.php` exceto `/emails/`, delete `resources/js/` e `resources/css/` legados, limpe `routes/web.php`. Execute `npm run build`, `npm run lint`, `node --test tests/scaffold/*.test.cjs`, `npx playwright test`. Meta: todos os 14 ACs verdes (AC-003 com skip em !darwin OK). Respeite ADR-0015, ADR-0016, e a decisão D8 `/emails/` preservado. Commits atômicos conforme proposta em plan §9.

### 4. Após implementação verde, rodar gates principais (5+1)

Ordem definida no orchestrator:
1. `/verify-slice 016` → qa-expert:verify (contexto isolado)
2. `/review-pr 016` → architecture-expert:code-review (contexto isolado — R11 dual-gate)
3. Em paralelo: `/security-review 016` + `/test-audit 016` + `/functional-review 016` + (se aplicável) data-gate + observability-gate + integration-gate
4. `/master-audit 016` → governance:master-audit dual-LLM (2× Opus 4.7 em contextos isolados — política 2026-04-17)
5. Se todos approved com `blocking_findings_count == 0`: `/merge-slice 016`

### 5. Após merge
- `/slice-report 016` + `/retrospective 016` (obrigatórios R15)
- Atualizar `project-state.epics_status.E15.stories.E15-S02 = "merged"`
- Próxima story: `/start-story E15-S03` (service worker + PWA manifest — pode desbloquear paralelismo em S04/S05)

## Comandos úteis

```bash
# Retomar contexto
/resume

# Ver estado atual
/project-status

# Ver próxima ação recomendada
/next-slice

# Verificar saúde do contexto em nova sessão
/context-check

# Auditoria do harness (periódica)
/guide-check
```

## Nota sobre branch não pushed

A branch `feat/slice-016-scaffold-frontend` foi **pushada ao final desta sessão** para preservar o trabalho. **Nenhum PR foi aberto** — PR será aberto apenas pelo `/merge-slice 016` após a implementação e todos os 5+1 gates approved.

Se precisar inspecionar remotamente antes disso:
- https://github.com/roldaobatista/kalibrium-v2/tree/feat/slice-016-scaffold-frontend

## Avisos importantes

- **Não abra PR prematuro** — fluxo prescreve PR só em `/merge-slice` após todos os gates.
- **Não rode `npm install` nesta sessão** — isso é trabalho do implementer; fazer antes tirará o controle do dependency resolution dele e pode gerar lockfile inconsistente com scaffold.
- **AC-003 skip em Windows é esperado** — CI macOS cobrirá depois (follow-up `CI-MACOS-001` em plan §Riscos).
- **HARNESS-MIGRATION-002 (S4)** segue como débito — não corrigir neste slice. Corrigir via harness-learner R16 ou quando auditoria apontar prioridade.
