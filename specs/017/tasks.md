# Tasks do slice 017 — PWA Service Worker + manifest + instalabilidade offline

**Status:** draft
**Spec:** `specs/017/spec.md`
**Plan:** `specs/017/plan.md`
**Story:** `epics/E15/stories/E15-S03.md`

---

## Ordem de execucao

Tasks atomicas. Cada uma cabe em um commit atomico. Executar em ordem (nao paralelizar dentro do slice). Commits T06 e T07 seguem a regra P2: testes red antes da implementacao.

---

### T01 — Adicionar devDeps PWA no `package.json`

- **AC relacionado:** pre-requisito para AC-001..AC-008.
- **Arquivos:** `package.json`, `package-lock.json`.
- **Decisoes cobertas:** D1, D3, D4, D9, D10.
- **Acoes:**
  - Adicionar em `devDependencies`: `vite-plugin-pwa ^0.20.5`, `workbox-window ^7.3.0`, `pwa-asset-generator ^6.4.0`, `lighthouse ^12.2.0`, `serve ^14.2.4`, `pngjs ^7.0.0`.
  - Adicionar em `scripts`: `generate:icons`, `serve:https`, `test:lighthouse`.
  - `npm install` para gerar lock.
- **DoD:** `npm install` exit 0; `npm run lint` continua exit 0; nada em `dependencies` (tudo dev).
- **Commit:** `chore(slice-017): add vite-plugin-pwa + workbox-window + lighthouse dev deps`.

---

### T02 — Criar SVG fonte do logo Kalibrium

- **AC relacionado:** AC-004 (pre-requisito).
- **Arquivos:** `public/icons/source/kalibrium-logo.svg` (novo).
- **Decisoes cobertas:** D4.
- **Acoes:**
  - Criar SVG quadrado (1024x1024 viewBox) com marca Kalibrium (placeholder ok se branding final nao disponivel).
  - Commitar como fonte oficial.
- **DoD:** arquivo valido, abre em navegador.
- **Commit:** `chore(slice-017): add SVG source para icones PWA`.

---

### T03 — Gerar 3 icones PNG (192, 512, 512-maskable)

- **AC relacionado:** AC-004, AC-004-A (pre-requisito para build).
- **Arquivos:** `public/icons/icon-192.png`, `public/icons/icon-512.png`, `public/icons/icon-512-maskable.png` (novos), `scripts/pwa/generate-icons.mjs` (opcional, wrapper do `pwa-asset-generator`).
- **Decisoes cobertas:** D4.
- **Acoes:**
  - Rodar `npx pwa-asset-generator public/icons/source/kalibrium-logo.svg public/icons/ --icon-only --type png --padding '20%'` (cria maskable com area segura 80%).
  - Renomear / duplicar arquivos para bater com nomes exigidos (`icon-192.png`, `icon-512.png`, `icon-512-maskable.png`).
  - Commitar PNGs gerados.
- **DoD:** `ls public/icons/` mostra os 3 PNGs + `source/kalibrium-logo.svg`.
- **Commit:** `feat(slice-017): gera icones PWA (192, 512, maskable)`.

---

### T04 — Configurar `VitePWA` em `vite.config.ts`

- **AC relacionado:** AC-002, AC-003, AC-005, AC-007, AC-008 (base de implementacao).
- **Arquivos:** `vite.config.ts`, `tsconfig.json`.
- **Decisoes cobertas:** D1, D2, D3, D5, D7, D8.
- **Acoes:**
  - Import `VitePWA` de `vite-plugin-pwa`.
  - Config:
    - `registerType: 'autoUpdate'`, `strategies: 'generateSW'`, `injectRegister: null` (registro manual em T05).
    - `manifest`: `{ name: 'Kalibrium', short_name: 'Kalibrium', description: 'Kalibrium offline-first mobile client', start_url: '/', display: 'standalone', orientation: 'any', theme_color: '#3880ff', background_color: '#ffffff', icons: [...3 entries with purpose correto] }`.
    - `workbox`: `{ globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'], maximumFileSizeToCacheInBytes: 3_000_000, navigateFallback: '/index.html', navigateFallbackDenylist: [/^\/api\//], runtimeCaching: [ documents-NetworkFirst-3s-timeout, assets-CacheFirst-90d ], cleanupOutdatedCaches: true, cacheId: 'kalibrium-v' + process.env.VITE_APP_VERSION }`.
  - `define`: `{ 'import.meta.env.VITE_APP_VERSION': JSON.stringify(process.env.VITE_APP_VERSION ?? pkg.version) }`.
  - `tsconfig.json`: `compilerOptions.types` adiciona `"vite-plugin-pwa/client"`.
- **DoD:** `npm run build` gera `dist/sw.js`, `dist/manifest.webmanifest`, `dist/workbox-*.js`; `npm run lint` exit 0.
- **Commit:** `feat(slice-017): config VitePWA com manifest + cache shell + deny /api/*`.

---

### T05 — Registrar SW no cliente com feature detection

- **AC relacionado:** AC-005, AC-005-A.
- **Arquivos:** `src/sw-registration.ts` (novo), `src/main.tsx` (modificado), `index.html` (modificado).
- **Decisoes cobertas:** D1 (lado cliente), D5.
- **Acoes:**
  - `src/sw-registration.ts`: funcao `registerServiceWorker()` que (1) guarda `if ('serviceWorker' in navigator)` — se falso, retorna sem lancar; (2) usa `registerSW` do `virtual:pwa-register` para registrar com `onRegistered`/`onRegisterError`.
  - `src/main.tsx`: import `registerServiceWorker` e chamar apos `createRoot(...).render(...)`.
  - `index.html`: `<meta name="theme-color" content="#3880ff">`, `<link rel="apple-touch-icon" href="/icons/icon-192.png">`.
- **DoD:** `npm run build` ok; `npm run dev` abre `/` sem erro; tipagem TS verde (`tsc -b`).
- **Commit:** `feat(slice-017): registro SW com feature detection + theme-color meta`.

---

### T06 — Scripts HTTPS local + certs + docs operacionais

- **AC relacionado:** AC-001, AC-001-A, AC-006 (infraestrutura de teste).
- **Arquivos:** `scripts/pwa/serve-https.mjs` (novo), `scripts/pwa/generate-local-cert.mjs` (novo), `.gitignore` (modificado), `docs/operations/pwa-icons.md` (novo), `docs/operations/pwa-lighthouse.md` (novo), `docs/operations/pwa-https-local.md` (novo).
- **Decisoes cobertas:** D4 (docs), D6 (docs), D9.
- **Acoes:**
  - `scripts/pwa/generate-local-cert.mjs`: prefere `mkcert` se instalado; fallback para `openssl req -x509 -newkey rsa:2048 -nodes -subj '/CN=localhost'` em `certs/local.{key,crt}`.
  - `scripts/pwa/serve-https.mjs`: abre `dist/` via `serve` com `--ssl-cert certs/local.crt --ssl-key certs/local.key` em porta configuravel (default 5174).
  - `.gitignore`: adicionar `certs/`, `lighthouse-reports/`.
  - `docs/operations/pwa-icons.md`: comando `generate:icons`, como trocar SVG fonte, validar maskable.
  - `docs/operations/pwa-lighthouse.md`: threshold CI=0.85 prod=0.90, como rodar local, interpretar falhas.
  - `docs/operations/pwa-https-local.md`: instalar mkcert no Windows/macOS/Linux, fallback openssl.
- **DoD:** `node scripts/pwa/generate-local-cert.mjs` gera `certs/local.crt` e `certs/local.key`; `npm run serve:https` sobe em `https://localhost:5174`.
- **Commit:** `feat(slice-017): scripts https serve + cert local + docs operacionais`.

---

### T07 — Escrever testes RED (test-writer)

**Pre-condicao:** T01..T06 merged no branch do slice. NAO implementar nada deste T07 em diante antes do builder (test-writer) ser invocado via `/draft-tests 017`.

- **AC relacionado:** AC-001..AC-008 (14 ACs).
- **Arquivos criados:**
  - `tests/e2e/pwa-install.spec.ts` — `describe('AC-001: PWA install on HTTPS', ...)` + `describe('AC-001-A: no install on HTTP', ...)`.
  - `tests/e2e/pwa-offline.spec.ts` — `describe('AC-002: /login offline <2s', ...)` + `describe('AC-002-A: cold cache <5s', ...)` + `describe('AC-007 runtime: /api/* rejects offline', ...)`.
  - `tests/e2e/pwa-sw.spec.ts` — `describe('AC-005: SW registered + activated', ...)` + `describe('AC-005-A: legacy browser graceful', ...)`.
  - `tests/e2e/pwa-upgrade.spec.ts` — `describe('AC-008: cleanup caches on upgrade', ...)`.
  - `tests/scaffold/ac-003-manifest.test.cjs` — header `// @covers AC-003`, valida manifest com `node:test`.
  - `tests/scaffold/ac-004-icons.test.cjs` — header `// @covers AC-004, AC-004-A`, pngjs check.
  - `tests/scaffold/ac-006-lighthouse.test.cjs` — header `// @covers AC-006, AC-006-A`, child_process chain (build + serve:https + lighthouse + assert).
  - `tests/scaffold/ac-007-api-no-cache.test.cjs` — header `// @covers AC-007`, grep `dist/sw.js`.
- **Decisoes cobertas:** D10.
- **Acoes:**
  - Escrever cada spec conforme mapeamento AC -> teste no plan §3.
  - Cada spec RED (falha) antes da implementacao do T08.
  - AC-ID rastreavel em filename + `describe` + header (ADR-0017 Mudanca 1).
- **DoD:** `npm run test:scaffold` falha; `npm run test:e2e` falha; `/audit-tests-draft 017` aprova (zero findings).
- **Commit:** `test(slice-017): ACs 001-008 red`.

---

### T08 — Implementacao ate verde (implementer)

**Pre-condicao:** T07 merged + `/audit-tests-draft 017` com `verdict: approved` e `findings: []`.

- **AC relacionado:** todos.
- **Arquivos potencialmente tocados:** `vite.config.ts`, `src/sw-registration.ts`, `src/main.tsx`, `index.html`, config/scripts, conforme tasks T04-T06 (ajustes finos).
- **Acoes:**
  - Rodar cada teste isolado, corrigir config/codigo ate verde.
  - NUNCA alterar arquivo de teste para "fazer passar" — apenas corrigir implementacao.
  - Piramide de escalacao (P8): teste individual -> grupo (`pwa-*.spec.ts`) -> grupo scaffold -> `npm run test` completo no final.
  - Se AC-006 (Lighthouse) flaky, aplicar mitigacao R-F do plan (2 runs median).
- **DoD:** todos os itens do `§10 Definition of Done` do plan marcados.
- **Commits:** `feat(slice-017): ajustes ate AC-NNN verde` (atomizar por AC ou por cluster — implementer decide).

---

### T09 — Atualizar `project-state.json`

- **AC relacionado:** rastreabilidade.
- **Arquivos:** `project-state.json`.
- **Acoes:**
  - `current_slice: "017"`.
  - `frontend_pwa_shell: true` em flags (ou campo equivalente ja presente).
- **DoD:** json valido (`jq empty`).
- **Commit:** incluir junto ao ultimo commit T08 ou standalone `chore(slice-017): project-state update`.

---

## Checklist final (antes de `/verify-slice 017`)

- [ ] T01..T09 concluidas e commitadas.
- [ ] Todos os 14 ACs cobertos por teste rastreavel (AC-ID em filename + describe + header).
- [ ] `npm run lint` exit 0.
- [ ] `npm run test:scaffold` exit 0 (4 specs verdes).
- [ ] `npm run test:e2e` exit 0 (4 specs verdes).
- [ ] `npm run build` exit 0 com `dist/sw.js`, `dist/manifest.webmanifest`, 3 icones.
- [ ] Lighthouse PWA score >= 0.85 registrado em `lighthouse-reports/` (se CI salvar).
- [ ] Zero `runtimeCaching` casando `/api/*` em `vite.config.ts`; grep `dist/sw.js` por `"/api"` retorna zero.
- [ ] `navigateFallbackDenylist: [/^\/api\//]` presente.
- [ ] Nenhum arquivo selado (`.claude/`, `scripts/hooks/`, `docs/protocol/`, `docs/constitution.md`, `CLAUDE.md`) alterado.
- [ ] Commits com autor valido (R5).
- [ ] Commit de testes T07 precede implementacao T08 (P2).
- [ ] `specs/017/verification.json` ainda nao existe (sera criado pelo verifier).

---

**Dependencias entre tasks:**

```
T01 -> T02 -> T03 -> T04 -> T05 -> T06 -> T07 -> T08 -> T09
```

T01-T06 podem ser consolidadas em PR unico (scaffold de infra PWA).
T07 (testes red) e um commit separado obrigatorio (P2).
T08 pode ter N commits intermedios; cada um apenas atomic fix ate o proximo AC verde.
T09 sempre no fim.
