---
title: "Plano técnico — Scaffold React + TypeScript + Ionic + Capacitor + Vite"
lane: L3
slice: "016"
story: E15-S02
epic: E15
status: draft
type: feature
depends_on: ["slice-015"]
adrs: ["ADR-0015", "ADR-0016"]
reqs: ["REQ-FLD-001", "REQ-SEC-001"]
---

# Slice 016 — Plano técnico (E15-S02)

> Scaffold compilável do cliente offline-first: React 18 + TypeScript 5 + Ionic 8 + Capacitor 6 + Vite 5. Nenhuma regra de negócio é implementada. Este plano consolida as versões pinadas em `docs/frontend/stack-versions.md` (gerado em slice-015) e aplica a stack da ADR-0015 ao repositório real.

**Gerado por:** architecture-expert (modo plan)
**Spec de origem:** `specs/016/spec.md` (14 ACs, verdict approved)

---

## 1. Contexto e objetivo técnico

O slice-015 (spike INF-007) validou as versões-alvo de ADR-0015 em PoC descartável (`spike-inf007/`) e fixou `docs/frontend/stack-versions.md`. Este slice **cria o projeto frontend real** na raiz do repositório (`package.json`, `vite.config.ts`, `src/`, `tests/`, `ios/`, `android/`, `capacitor.config.ts`), removendo formalmente o frontend legado Livewire/Blade (20 Blades + 2 JS + 1 CSS inventariados em slice-015).

Saída: scaffold que abre em `http://localhost:5173/login`, faz `npm run build` verde, gera `ios/App/App.xcworkspace` (macOS) e `android/build.gradle`, passa `npm run lint` e tem suite Playwright mínima cobrindo layout adaptativo. Backend Laravel continua intocado (API-only — ADR-0001).

---

## 2. Decisões arquiteturais

### D1 — Bundler: Vite 5 (re-ratificação de ADR-0015)

**Opções consideradas:**
- **A. Vite 5.2.x:** HMR instantâneo, nativo ESM, plugin oficial React, integração pronta com Ionic 8; já validado em slice-015.
- **B. Webpack 5 + CRA:** maduro mas lento; CRA descontinuado; peer deps pesadas.
- **C. Rsbuild / Turbopack:** rápidos, mas sem track record em Capacitor+Ionic.

**Escolhida:** A.

**Razão:** ADR-0015 já fixou Vite; slice-015 comprovou compatibilidade com Ionic 8 + React 18 no `package.json` do PoC; reverter pós-scaffold seria refactor de 100% do build.

**Reversibilidade:** difícil (migração de bundler exige refazer `vite.config.ts`, plugins, scripts e adaptar Capacitor `webDir`).

---

### D2 — Router: `@ionic/react-router` (não TanStack Router)

**Opções consideradas:**
- **A. `@ionic/react-router` 8.2.x:** casado com `@ionic/react`, transições nativas (slide iOS, fade Material), `IonReactRouter` + `IonRouterOutlet` lidam com stack de navegação mobile.
- **B. TanStack Router 1.x:** type-safe, excelente DX em desktop, mas não conhece stacks mobile; animações Ionic exigiriam glue manual.
- **C. React Router 6 puro:** sem integração com ciclo `IonLifecycle`.

**Escolhida:** A.

**Razão:** produto é mobile-first (iOS/Android via Capacitor); transições nativas e `ionViewWillEnter`/`ionViewDidEnter` são UX crítica. TanStack só justificaria em produto desktop-first, o que contraria ADR-0015.

**Reversibilidade:** média (rotas são poucas e declarativas; trocar router em E15 adiantado custaria ~1 slice).

---

### D3 — Estrutura de diretórios em `src/`

**Opções consideradas:**
- **A. Flat por tipo:** `src/pages/`, `src/components/`, `src/hooks/`, `src/db/`, `src/auth/`, `src/wipe/`, `src/observability/` (7 diretórios — literalmente o que AC-005 pede).
- **B. Feature-sliced:** `src/features/<dominio>/{components,hooks,pages}` — over-engineering para scaffold sem domínio.
- **C. Hexagonal:** `src/{domain,application,infrastructure,presentation}/` — prematuro, força abstrações antes de requisitos.

**Escolhida:** A.

**Razão:** spec AC-005 exige literalmente esta lista. B/C são over-engineering num scaffold zero-domínio; feature-sliced pode ser adotado em E15-S07+ quando houver features reais — migração gradual sem breaking.

**Reversibilidade:** média (mover arquivos é mecânico; imports atualizam com codemod).

---

### D4 — Capacitor: plataformas nativas geradas, com subset commitado

**Opções consideradas:**
- **A. Gerar `ios/`+`android/` via `npx cap add`, commitar só arquivos-raiz** (`build.gradle`, `settings.gradle`, `app/build.gradle`, `Info.plist`, `Podfile`); `.gitignore` bloqueia transitórios (`Pods/`, `build/`, `DerivedData/`, `.gradle/`).
- **B. Commitar `ios/` e `android/` completos:** ~300 MB, ruído em diffs, conflitos constantes.
- **C. Monorepo com `apps/mobile/ios/`:** over-engineering para MVP.

**Escolhida:** A.

**Razão:** evidência mecânica de AC-003/AC-004 exige arquivos-chave presentes; transitórios gerados localmente/CI. Plugins **oficiais** (`@capacitor/ios`, `@capacitor/android`) preferidos sobre comunitários onde ambos existem.

**Reversibilidade:** fácil (deletar `ios/`/`android/` e rodar `npx cap add` re-gera).

---

### D5 — Lint: ESLint flat config + Prettier integrado

**Opções consideradas:**
- **A. ESLint flat config (`eslint.config.js`) + `@typescript-eslint` + `eslint-plugin-react-hooks` + Prettier via `eslint-config-prettier`:** padrão 2026 para Node 20+.
- **B. Legacy `.eslintrc.cjs`:** deprecated; ESLint 9 já default flat.
- **C. Biome:** rápido, mas ecosystem Ionic/Capacitor ainda usa ESLint; menos plugins.

**Escolhida:** A.

**Razão:** flat config é a direção oficial do ESLint 9; Vite 5 + React 18 + TS 5.4 são compatíveis; Prettier como formatter (não linter) elimina conflito de regras.

**Reversibilidade:** fácil (swap de config é contido).

---

### D6 — Layout adaptativo: Ionic Grid + media queries, sem framework CSS extra

**Opções consideradas:**
- **A. Ionic Grid (`IonGrid`/`IonRow`/`IonCol`) + CSS variables de breakpoint (`--ion-grid-width-xs..xl`) + `@media`:** já vem no `@ionic/react`.
- **B. Tailwind CSS:** popular, mas duplica utilitários Ionic e aumenta bundle.
- **C. CSS-in-JS (styled-components/emotion):** runtime overhead, incompatível com critical CSS inline do Vite.

**Escolhida:** A.

**Razão:** Ionic 8 entrega grid responsivo pronto; AC-006 exige apenas `scrollWidth <= clientWidth` em 375px e 1280px — não precisa framework utility.

**Reversibilidade:** fácil (adicionar Tailwind depois é non-breaking).

---

### D7 — Testes E2E: Playwright (headless chromium em CI)

**Opções consideradas:**
- **A. Playwright 1.4x:** cross-browser, ótimo DX para Ionic, screenshots/traces nativos; spec AC-006 cita nominalmente.
- **B. Cypress:** bom DX, mas só Chromium-like; sem suporte oficial a Capacitor nativo.
- **C. Detox / Appium:** voltados a nativo real; overkill para scaffold web.

**Escolhida:** A.

**Razão:** E2E de scaffold roda contra dev-server web; nativo real só vira assunto em E15-S04/S05. Cache de browsers do Playwright viabiliza CI rápido.

**Reversibilidade:** fácil (suite é pequena, reescrever em Cypress custaria ~0.5 slice).

---

### D8 — Descarte do frontend legado Livewire/Blade

**Opções consideradas:**
- **A. Delete imediato de `resources/views/*.blade.php` (exceto `/emails/`), `resources/js/`, `resources/css/`; migrar `routes/web.php` para redirect/health:** limpo, evidência trivial em AC-008/AC-013.
- **B. Manter deprecated com flag e remover em E15-S10:** arrasta dívida; risco de import órfão.
- **C. Mover para `legacy/`:** ruído no repo.

**Escolhida:** A.

**Razão:** slice-015 inventariou e **confirmou** todos não-reaproveitáveis. Exceção: `resources/views/emails/*.blade.php` (e-mails transacionais server-side) **permanece** — não é "frontend SPA". AC-008 é verificável com `find resources/views -name "*.blade.php" -not -path "*/emails/*"` = 0 arquivos.

**Reversibilidade:** difícil (recuperar Blade exige `git revert` + reconectar rotas; efetivamente decisão de não-volta).

---

### D9 — Capacitor `server` config: ausente em prod, override local em dev (AC-014)

**Opções consideradas:**
- **A. `server.url` ausente do arquivo commitado; dev usa `capacitor.config.dev.ts` não-commitado (no `.gitignore`) ou variável de ambiente `CAP_SERVER_URL`:** prod nunca expõe URL por construção.
- **B. `server.url` comentado em `capacitor.config.ts`:** depende de disciplina humana, facilmente esquecido.
- **C. `server.url` inline condicional ao `NODE_ENV`:** risca leak se build prod ler config errada.

**Escolhida:** A.

**Razão:** AC-014 exige prova mecânica de ausência de URL remota em prod; arquivo commitado sem `server.url` + override local não-commitado elimina o risco por construção.

**Reversibilidade:** fácil.

---

### D10 — Testes de ACs "comando retorna exit 0": Node puro (`node:test`), não shell

**Opções consideradas:**
- **A. Harness Node (`node:test` built-in) invocando `child_process.execSync` e assertando exit code + artefatos:** portável Windows/macOS/Linux, zero dependência extra.
- **B. Scripts bash em `tests/shell/`:** quebra em Windows sem Git Bash; dev local do PM (Windows) não roda.
- **C. Playwright também para isso:** overkill e lento.

**Escolhida:** A.

**Razão:** PM usa Windows; AC-002/AC-004/AC-007/AC-012 são verificáveis de Node puro. `node:test` (built-in ≥ Node 18) evita nova dependência.

**Reversibilidade:** fácil.

---

## 3. Mapeamento AC → arquivos

| AC | Arquivos criados/editados/removidos | Teste principal |
|---|---|---|
| AC-001 | `package.json`, `vite.config.ts`, `src/main.tsx`, `src/App.tsx`, `src/pages/LoginPage.tsx`, `index.html`, `tsconfig.json`, `tsconfig.node.json` | `tests/e2e/dev-server.spec.ts` (Playwright: abre `/login`, asserta Ionic render + console limpo) |
| AC-002 | `package.json` (scripts `build`), `vite.config.ts`, `src/main.tsx` | `tests/node/build.test.mjs` (invoca `npm run build`, checa `dist/index.html`, `dist/assets/*.js`, `dist/assets/*.css`, exit 0) |
| AC-003 | `capacitor.config.ts`, `package.json` (`@capacitor/ios`, `@capacitor/cli`), `ios/App/App.xcworkspace/**` (subset), `ios/App/Podfile`, `.gitignore` | `tests/node/cap-ios.test.mjs` (skip se `process.platform !== 'darwin'`; valida estrutura de `ios/App/App.xcworkspace`) |
| AC-004 | `capacitor.config.ts`, `package.json` (`@capacitor/android`), `android/build.gradle`, `android/settings.gradle`, `android/app/build.gradle`, `android/gradle.properties`, `.gitignore` | `tests/node/cap-android.test.mjs` (valida existência dos arquivos-raiz Gradle) |
| AC-005 | `src/pages/.gitkeep`, `src/components/.gitkeep`, `src/hooks/.gitkeep`, `src/db/.gitkeep`, `src/auth/.gitkeep`, `src/wipe/.gitkeep`, `src/observability/.gitkeep` | `tests/node/dir-structure.test.mjs` (asserta 7 diretórios presentes) |
| AC-006 | `src/pages/HomePage.tsx`, `src/App.tsx` (rota `/home`), CSS responsivo via Ionic Grid | `tests/e2e/layout.spec.ts` (Playwright: 375x667 + 1280x800, `scrollWidth <= clientWidth`) |
| AC-007 | `eslint.config.js`, `.prettierrc.json`, `.prettierignore`, `package.json` (script `lint`), devDeps ESLint/Prettier/plugins | `tests/node/lint.test.mjs` (`npm run lint` exit 0) |
| AC-008 | **REMOVE:** `resources/views/*.blade.php` exceto `/emails/`, `resources/js/app.js`, `resources/js/bootstrap.js`, `resources/css/app.css`; **EDITA:** `routes/web.php` (remove rotas Blade; mantém `/health` e eventual redirect `/ → /api/docs`) | `tests/node/legacy-removed.test.mjs` (zero Blade em `resources/views/livewire/`, `layouts/`, `welcome.blade.php`, etc.) |
| AC-009 | `package.json` (script `dev` — comportamento default do Vite já emite mensagem de porta ocupada) | `tests/node/port-conflict.test.mjs` (abre socket em 5173, roda `npm run dev` em subprocess, parseia stdout por "Port 5173 is in use" ou "using ... instead") |
| AC-010 | Teste cria `src/__type_check__.tsx` temporário, roda build, espera falha, limpa | `tests/node/build-type-error.test.mjs` |
| AC-011 | Mesma lista de AC-005 — validação é "apenas esses, nada além" | `tests/node/dir-structure.test.mjs` (asserta conjunto exato, não subset) |
| AC-012 | Teste cria `src/__lint_check__.tsx` temporário com `const unused = 1`, roda `npm run lint`, espera exit não-zero, remove, re-roda espera exit 0 | `tests/node/lint-violation.test.mjs` |
| AC-013 | `routes/web.php` editado (sem `view(` nem `return view`) | `tests/node/routes-clean.test.mjs` (`grep -E "view\(\|return view" routes/web.php` retorna zero ocorrências) |
| AC-014 | `capacitor.config.ts` sem `server.url` hardcoded; `.gitignore` para `capacitor.config.dev.ts` | `tests/node/cap-config-prod.test.mjs` (parseia AST/regex do `capacitor.config.ts`; asserta ausência de `url:` literal em `server`) |

---

## 4. Novos arquivos (scaffold principal)

- `package.json` (raiz) — projeto Node do frontend
- `package-lock.json` — lockfile commitado (`npm ci` em CI)
- `vite.config.ts` — Vite + `@vitejs/plugin-react`
- `tsconfig.json`, `tsconfig.node.json`
- `eslint.config.js` — ESLint flat config
- `.prettierrc.json`, `.prettierignore`
- `index.html` — entry Vite
- `capacitor.config.ts` — sem `server.url` em prod
- `src/main.tsx`, `src/App.tsx` — bootstrap React + `IonReactRouter`
- `src/pages/LoginPage.tsx`, `src/pages/HomePage.tsx`, `src/pages/AdminDevicesPage.tsx` — stubs
- `src/pages/.gitkeep`, `src/components/.gitkeep`, `src/hooks/.gitkeep`, `src/db/.gitkeep`, `src/auth/.gitkeep`, `src/wipe/.gitkeep`, `src/observability/.gitkeep`
- `tests/e2e/dev-server.spec.ts`, `tests/e2e/layout.spec.ts`
- `tests/node/build.test.mjs`, `cap-ios.test.mjs`, `cap-android.test.mjs`, `dir-structure.test.mjs`, `lint.test.mjs`, `legacy-removed.test.mjs`, `port-conflict.test.mjs`, `build-type-error.test.mjs`, `lint-violation.test.mjs`, `routes-clean.test.mjs`, `cap-config-prod.test.mjs`
- `playwright.config.ts` — chromium headless, `webServer` aponta para `npm run dev`
- `ios/App/App.xcworkspace/**`, `ios/App/Podfile`, `ios/App/App/Info.plist` — gerados via `npx cap add ios`
- `android/build.gradle`, `android/settings.gradle`, `android/gradle.properties`, `android/app/build.gradle`, `android/app/src/main/AndroidManifest.xml` — gerados via `npx cap add android`
- `.gitignore` (raiz atualizado) — `node_modules/`, `dist/`, `ios/App/Pods/`, `ios/App/build/`, `ios/App/DerivedData/`, `android/build/`, `android/.gradle/`, `android/app/build/`, `capacitor.config.dev.ts`, `playwright-report/`, `test-results/`
- `docs/frontend/README.md` — como rodar dev, build, lint, test, cap sync

## 5. Arquivos modificados

- `routes/web.php` — remove rotas Blade/Livewire, mantém `/health`; sem `view(`/`return view`
- `composer.json` — **não editar** neste slice; remoção de pacotes Livewire é follow-up `FE-COMPOSER-01` (E15-S10)
- `docs/frontend/stack-versions.md` — marcar pendências do checklist §"Pré-condições E15-S02" como `[x]` com evidência do `npm-install.log` real
- `project-state.json` — `current_slice: "016"`, `frontend_stack_bootstrapped: true`, encerrar pendências do spike 015

## 6. Arquivos removidos

- `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/livewire/**/*.blade.php` (~16 arquivos)
- `resources/js/app.js`, `resources/js/bootstrap.js`
- `resources/css/app.css`
- **MANTIDOS:** `resources/views/emails/*.blade.php` (server-side; não frontend SPA)

## 7. Schema / migrations

**N/A — slice sem acesso a dados; ORM decisions adiadas para E15-S06.** Nenhuma migration Laravel é criada ou alterada.

## 8. APIs / contratos

**N/A — sem novas rotas de backend.** Frontend consome API existente (`/health` e endpoints E02/E03 já documentados em `docs/frontend/api-endpoints.md`). Stubs de página são estáticos; integração runtime é E15-S07+.

## 9. Eager loading strategy

**N/A — slice sem ORM; sem queries Eloquent novas.**

## 10. Middleware pipeline

**N/A — sem novas rotas de API.** `routes/web.php` perde rotas Blade; `/health` existente mantém middleware atual sem alteração.

---

## 11. Estrutura de testes para `/draft-tests 016`

O builder (test-writer) criará em commit separado, antes da implementação:

**Playwright E2E (2 arquivos):**
- `tests/e2e/dev-server.spec.ts` → cobre AC-001 (`describe('AC-001: dev server renders /login', ...)`)
- `tests/e2e/layout.spec.ts` → cobre AC-006 (375x667 e 1280x800)

**Node tests (11 arquivos `.mjs` usando `node:test`):**
- `build.test.mjs` → AC-002
- `cap-ios.test.mjs` → AC-003 (skip non-darwin)
- `cap-android.test.mjs` → AC-004
- `dir-structure.test.mjs` → AC-005 + AC-011
- `lint.test.mjs` → AC-007
- `legacy-removed.test.mjs` → AC-008
- `port-conflict.test.mjs` → AC-009
- `build-type-error.test.mjs` → AC-010
- `lint-violation.test.mjs` → AC-012
- `routes-clean.test.mjs` → AC-013
- `cap-config-prod.test.mjs` → AC-014

**Rastreabilidade AC-ID (ADR-0017 Mudança 1):** cada Playwright spec usa `test.describe('AC-NNN: ...')`; cada `.test.mjs` começa com `// @covers AC-NNN`. `audit-tests-draft` valida.

---

## 12. Plano de commits atômicos

1. `chore(slice-016): scaffold React + TypeScript + Vite base` → AC-001, AC-002, AC-005
2. `chore(slice-016): integra Ionic 8 + layout adaptativo` → AC-006
3. `chore(slice-016): adiciona Capacitor + plataformas iOS/Android` → AC-003, AC-004, AC-014
4. `chore(slice-016): ESLint + Prettier + Playwright scaffold` → AC-007, AC-010, AC-012
5. `chore(slice-016): remove frontend legado Livewire/Blade` → AC-008, AC-013
6. `test(slice-016): ACs 001-014 red` (test-writer)
7. `feat(slice-016): implementação para ACs red virarem verdes` (implementer — pode subdividir se diff ficar grande)

---

## 13. Dependências de outros slices

- **slice-015 (E15-S01, merged):** versões pinadas em `docs/frontend/stack-versions.md` são input direto; `spike-inf007/package.json` é template.
- **ADR-0015:** stack oficial — não contradizer.
- **ADR-0016:** multi-tenancy — **não tocado** (sem DB, sem auth real).

### Pré-requisitos externos de runtime

- Node.js LTS 20.x
- npm 10.x
- Playwright browsers (`npx playwright install --with-deps chromium`)
- Xcode + macOS **somente para AC-003** (skip em Windows/Linux)
- Android SDK via Android Studio ou `sdkmanager` — para AC-004
- Backend Laravel **não é pré-requisito** para `npm run dev` (stubs estáticos)

---

## 14. Riscos e mitigações

- **R1 — `npm install` falha por peer deps (Ionic 8 × React 18 × Vite 5).** Usar versões exatas de `docs/frontend/stack-versions.md` (React 18.3.1, TS 5.4.5, Ionic 8.2.6, Capacitor 6.1.2, Vite 5.2.11). Se `ERESOLVE`, ordem: (1) aceitar warning documentado, (2) `overrides` em `package.json`, (3) `--legacy-peer-deps` com rationale. `--force` proibido.
- **R2 — AC-003 não-verificável em Windows (host do PM).** Teste Node/Playwright pula com `it.skip` quando `process.platform !== 'darwin'`; CI macOS cobre. Follow-up `CI-MACOS-001` em `project-state.json[technical_debt]`.
- **R3 — Playwright em CI precisa baixar browsers (~300 MB).** Step dedicado `npx playwright install --with-deps chromium`; cache `~/.cache/ms-playwright` por hash de `package-lock.json`.
- **R4 — Remoção de Blade quebra rotas do backend.** Antes de deletar, `grep -n "view(" routes/web.php` + `grep -n "->group" routes/web.php`; mover/remover cada rota. Smoke: `php artisan route:list | grep -v api | grep -v health` vazio.
- **R5 — Divergência `package.json` vs `docs/frontend/stack-versions.md`.** Commit 1 copia versões exatas; follow-up opcional: `tests/node/stack-parity.test.mjs` em CI.
- **R6 — `--legacy-peer-deps` silencia conflito real.** Se precisar, registrar `stack-versions.md §"Conflitos resolvidos"` com rationale + ticket upstream; avaliar emenda à ADR-0015.
- **R7 — SQLCipher ainda em investigação (verdict preliminar slice-015).** Este slice **não toca SQLite** — risco deferido para E15-S06.
- **R8 — Arquivos gerados em `ios/`/`android/` muito grandes.** `.gitignore` agressivo para `Pods/`, `build/`, `DerivedData/`, `.gradle/`; só arquivos-raiz versionados.
- **R9 — `capacitor.config.dev.ts` vazar para produção.** `.gitignore` + teste AC-014 valida `capacitor.config.ts` commitado; CI de build usa apenas arquivo commitado.
- **R10 — E-mails transacionais confundidos com frontend legado.** Decisão em D8: manter `resources/views/emails/`. Teste AC-008 filtra `-not -path "*/emails/*"`.

### Follow-ups (não criam ADR neste slice)

- `CI-MACOS-001`: adicionar runner macOS no CI para cobrir AC-003.
- `FE-COMPOSER-01`: remover pacotes Livewire do `composer.json` em E15-S10.
- `FE-API-01`: expor `POST /api/auth/login` JSON (hoje `/auth/login` retorna HTML Livewire) — E15-S07.
- `FE-STACK-PARITY-01`: teste opcional de paridade `package.json` vs `stack-versions.md`.

---

## 15. Rollback / plano B

- `git revert` do merge remove todo o scaffold; Blade volta via revert (só em emergência real — descarte é formal).
- `npm install` irremediável: pinar versões alternativas (Ionic 7.x) via ADR emenda, **não** ignorar.
- `cap add` falhar em ambos SO: slice fica parcial com AC-003/AC-004 skip registrados; follow-up em sprint seguinte.

---

## 16. Definition of Done (checklist mecânico)

- [ ] `npm install` exit 0 (`ERR!` ausente no `npm-install.log`)
- [ ] `npm run dev` sobe Vite em `http://localhost:5173`; `/login` renderiza (AC-001)
- [ ] `npm run build` produz `dist/index.html` + `dist/assets/*.{js,css}` exit 0 (AC-002)
- [ ] `npx cap add ios && npx cap sync ios` gera `ios/App/App.xcworkspace` em macOS (AC-003; skip non-darwin)
- [ ] `npx cap add android && npx cap sync android` gera `android/build.gradle` + `android/app/build.gradle` (AC-004)
- [ ] `ls src/` → exatamente `pages, components, hooks, db, auth, wipe, observability` + arquivos raiz (AC-005 + AC-011)
- [ ] Playwright `layout.spec.ts` verde em 375x667 e 1280x800 (AC-006)
- [ ] `npm run lint` exit 0 zero erros (AC-007)
- [ ] `find resources/views -name "*.blade.php" -not -path "*/emails/*"` vazio (AC-008)
- [ ] `port-conflict.test.mjs` valida mensagem de porta ocupada (AC-009)
- [ ] `build-type-error.test.mjs` valida exit não-zero em erro TS (AC-010)
- [ ] `lint-violation.test.mjs` valida exit não-zero em violação ESLint e exit 0 após cleanup (AC-012)
- [ ] `grep -E "view\(|return view" routes/web.php` retorna zero ocorrências (AC-013)
- [ ] `cap-config-prod.test.mjs` valida ausência de `server.url` em `capacitor.config.ts` (AC-014)
- [ ] `docs/frontend/README.md` documenta `npm run dev|build|lint|test:e2e` e `npx cap add|sync`
- [ ] `.gitignore` cobre todos os transitórios listados em §4
- [ ] 13 arquivos de teste vermelhos antes de `feat(slice-016)` e verdes depois
- [ ] Commit de testes precede commit de implementação (P2)
- [ ] Nenhuma alteração em `.claude/`, `scripts/hooks/`, `docs/protocol/`, `docs/constitution.md`, `CLAUDE.md` (selados)
- [ ] `project-state.json` atualizado

---

## 17. Fora de escopo (reconfirmando a spec)

- Service Worker / `manifest.webmanifest` (E15-S03)
- Build de distribuição IPA/AAB em CI (E15-S04, E15-S05)
- `@capacitor-community/sqlite` com SQLCipher real (E15-S06)
- Auth funcional + device binding + biometria (E15-S07)
- Telas de negócio (CRUD, listagem, formulários)
- Sync engine e resolução de conflito (E16)
- Push notifications (E15-S08)
- Wipe remoto runtime (E15-S09)
- Remoção de pacotes Livewire do `composer.json` (follow-up `FE-COMPOSER-01`, E15-S10)

---

**Rastreabilidade:**
- Spec: `specs/016/spec.md` (14 ACs)
- Story: `epics/E15/stories/E15-S02.md`
- Slice anterior: `slice-015` (spike INF-007 — `docs/frontend/stack-versions.md`)
- ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenancy — não tocado)
- REQs: REQ-FLD-001, REQ-SEC-001
- Desbloqueia: E15-S03 (PWA + Service Worker), E15-S04..S10 em paralelo
