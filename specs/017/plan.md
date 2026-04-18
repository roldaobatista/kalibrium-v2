---
title: "Plano tecnico — Slice 017 — PWA Service Worker + manifest + instalabilidade offline"
lane: L3
slice: "017"
story: E15-S03
epic: E15
status: draft
type: feature
depends_on: ["slice-016"]
adrs: ["ADR-0015", "ADR-0016"]
reqs: ["REQ-FLD-001", "REQ-SEC-001"]
---

# Slice 017 — Plano tecnico (E15-S03)

> Transformar o scaffold entregue em slice-016 (React 18 + TS 5.4 + Vite 5.2 + Ionic 8.2 + Capacitor 6.1) em uma PWA W3C-compliant: service worker, manifest.webmanifest, icones adaptativos, cache-de-shell, ausencia garantida de cache em `/api/*`, versionamento de cache e score Lighthouse PWA >= 0.85 em CI / >= 0.90 em producao.

**Gerado por:** architecture-expert (modo plan)
**Spec de origem:** `specs/017/spec.md` (14 ACs, verdict `approved` em `specs/017/spec-audit.json` com 1 finding S4 nao-bloqueante sobre threshold Lighthouse — respeitado neste plano).
**isolation_context:** `slice-017-plan-instance-01`

---

## 1. Contexto e resumo

O slice-016 (E15-S02, merged em 2026-04-17) entregou o scaffold web: `vite.config.ts` minimo com `@vitejs/plugin-react`, `capacitor.config.ts` sem `server.url`, rotas React (`/login`, `/home`, `/admin/devices`), ESLint flat config, Playwright rodando contra `npm run dev` e testes Node em `tests/scaffold/*.test.cjs`. Nao ha service worker, nao ha `manifest.webmanifest`, nao ha icones PWA e `dist/` nao contem nenhum asset de instalabilidade.

Este slice adiciona a camada de shell PWA sobre o scaffold existente **sem alterar a stack**: `vite-plugin-pwa` (wrapper Vite oficial de Workbox) como unica nova runtime dep (fica em `devDependencies` porque sua saida e apenas build-time); `pwa-asset-generator` e `lighthouse` como dev-deps de geracao/auditoria; opcionalmente `mkcert` como tool externa para certificados locais. Toda essa cadeia se justifica como ferramenta de build/teste sob ADR-0015 (nao exige novo ADR) porque nao muda framework, router, UI kit nem wrapper nativo — apenas entrega os artefatos PWA exigidos pela story.

Limites claros: autenticacao offline (E15-S07), sync de dados (E16), cache de resposta de `/api/*` (proibido por AC-007), push notifications (E15-S08) e prompt de update do SW (E15-S05) **nao** estao neste slice. O entregavel e o shell: assets estaticos funcionam offline; qualquer chamada `/api/*` offline falha naturalmente (comportamento correto pois auth/sync ainda nao existem).

---

## 2. Decisoes arquiteturais

### D1 — Plugin Vite: `vite-plugin-pwa` com estrategia `generateSW` (nao `injectManifest`)

**Contexto:** o SW precisa fazer precache do shell (HTML/JS/CSS/fontes/icones), aplicar cache runtime em navegacao e garantir zero interceptacao em `/api/*`. Duas estrategias sao oferecidas pelo plugin.

**Alternativas:**
- **A. `generateSW` (Workbox gera o SW):** o plugin escreve `dist/sw.js` integralmente a partir de configuracao declarativa (`globPatterns`, `runtimeCaching`, `navigateFallback`, `navigateFallbackDenylist`). Zero codigo custom. Rapido de auditar (AC-007 e AC-008).
- **B. `injectManifest` (SW escrito a mao, plugin injeta manifest de precache):** arquivo-fonte `src/sw.ts` controlado pelo dev; plugin so faz `self.__WB_MANIFEST` injection. Permite handlers custom.
- **C. Service worker escrito 100% a mao sem plugin:** maximo controle, zero abstracao, mas reimplementa Workbox.

**Escolhida:** A.

**Justificativa:** o requisito deste slice e **shell cache + deny `/api/*`** — exatamente o caso de uso canonico de `generateSW`. `navigateFallbackDenylist: [/^\/api\//]` + ausencia de qualquer `runtimeCaching` com `urlPattern` casando `/api/*` ja satisfaz AC-007 por construcao. Zero codigo custom reduz superficie de bug e simplifica auditoria (grep em `dist/sw.js` e suficiente). A alternativa B so se justificaria se quisessemos Background Sync ou handlers de push — ambos fora de escopo (E15-S08 / E16).

**Tradeoff:** menos flexibilidade para evolucao. Risco #3 da spec ja registra que, quando E15-S06/E16 precisarem de handlers custom (ex: queue de mutations offline), este slice vai precisar migrar para `injectManifest`. Migracao estimada em 0.5 slice quando vier — custo conhecido e aceito.

**Reversibilidade:** media (migrar `generateSW` -> `injectManifest` exige reescrever o SW, mas config do plugin nao muda drasticamente; testes Playwright e assertions sobre `dist/sw.js` seguem validas).

**ADR relacionado:** coberto por ADR-0015 (stack offline-first) como ferramenta de build; nao exige novo ADR.

---

### D2 — Estrategia de cache do shell: `CacheFirst` para assets versionados, `NetworkFirst` para navegacao HTML

**Contexto:** assets JS/CSS/fontes/icones sao hasheados pelo Vite (ex: `assets/index-abc123.js`) — uma vez cacheados, nunca mudam. O HTML raiz (`index.html`) nao tem hash e e o ponto onde o browser descobre o novo bundle. AC-002 exige render offline em < 2s; AC-002-A tolera 5s no pior cenario.

**Alternativas:**
- **A. Todos assets `CacheFirst` + HTML `NetworkFirst` com fallback ao cache:** bundle hasheado servido instantaneamente do cache; HTML tenta rede rapido (timeout curto), cai para cache quando offline.
- **B. Tudo `StaleWhileRevalidate`:** resposta imediata do cache + revalidacao em background. Bom para DX mas expoe-se a servir HTML stale apontando para bundle antigo (inconsistencia).
- **C. Tudo `NetworkFirst`:** offline funciona mas latencia de 2-3s por timeout de rede — pode quebrar AC-002 em 2G.

**Escolhida:** A.

**Justificativa:** assets Vite com hash sao imutaveis por definicao — `CacheFirst` e correto e mais rapido (0ms de lookup, sem round-trip). HTML precisa ser `NetworkFirst` com timeout curto (3s) porque e o carrier da referencia ao novo bundle; sem isso, usuario que nunca ficar offline pode ficar meses em versao antiga. Fallback automatico para cache preserva AC-002.

**Tradeoff:** primeira visita apos update quebra cache de HTML antigo — comportamento correto. Segunda visita offline serve HTML antigo (ok, single-user UX).

**Reversibilidade:** facil (trocar strategy no `runtimeCaching` do plugin config).

**ADR relacionado:** nenhum (decisao interna ao slice, coberta por Workbox strategies padrao).

---

### D3 — Precache: manifesto Workbox auto-gerado via `globPatterns`

**Contexto:** Workbox/vite-plugin-pwa pode gerar o precache manifest a partir de padroes glob sobre `dist/` no momento do build (`self.__WB_MANIFEST` injetado pelo plugin).

**Alternativas:**
- **A. Glob automatico:** `globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}']` pega tudo em `dist/` apos `vite build`.
- **B. Lista manual em config:** dev lista cada arquivo explicitamente. Frageis a renames/hash mismatches.
- **C. Sem precache (so runtime cache):** primeira visita offline quebra — viola AC-002.

**Escolhida:** A.

**Justificativa:** Vite e quem decide o nome final dos bundles (com hash); qualquer lista manual fica desatualizada. Glob coincide com a estrategia adotada na comunidade (documentacao oficial do `vite-plugin-pwa`). Limite de tamanho (`maximumFileSizeToCacheInBytes`) configurado em 3 MB — bundle JS atual do scaffold e ~400 KB; margem folgada.

**Tradeoff:** se algum asset gigante (>3MB) for adicionado, build falha com mensagem clara — melhor do que entregar precache silenciosamente quebrado.

**Reversibilidade:** facil.

**ADR relacionado:** nenhum.

---

### D4 — Geracao de icones: `pwa-asset-generator` a partir de SVG fonte, 3 PNGs obrigatorios

**Contexto:** AC-004 exige `icon-192.png` (192x192), `icon-512.png` (512x512) e `icon-512-maskable.png` (512x512 com area segura de 80%). AC-004-A exige pixel central opaco no maskable.

**Alternativas:**
- **A. `pwa-asset-generator` (CLI) com SVG fonte em `public/icons/source/kalibrium-logo.svg`:** script npm `generate:icons` roda manual uma vez + commit dos PNGs; build nao depende do gerador.
- **B. Plugin Vite que gera em build-time (`@vite-pwa/assets-generator`):** zero intervencao manual, mas roda em CI e pode flaky se SVG fonte estiver corrompido.
- **C. Icones desenhados manualmente em ferramenta externa (Figma export):** sem rastreabilidade de fonte.

**Escolhida:** A.

**Justificativa:** icones sao ativos estaveis — geracao single-shot commitada e suficiente e elimina dependencia de CI. `pwa-asset-generator` e maduro (>= 6.x) e suporta explicitamente maskable com `--padding '20%'` (area segura 80%). Documentamos o processo em `docs/operations/pwa-icons.md` (item do risco #4 da spec). SVG fonte fica versionado em `public/icons/source/kalibrium-logo.svg`.

**Tradeoff:** troca de branding exige rerodar CLI e commitar — aceitavel para MVP (branding estavel).

**Reversibilidade:** facil (rerodar gerador regenera tudo).

**ADR relacionado:** coberto por ADR-0015 como ferramenta de build.

---

### D5 — `manifest.webmanifest`: campos minimos + `orientation: any` + theme/background color do design-system

**Contexto:** AC-003 exige `name=="Kalibrium"`, `short_name=="Kalibrium"`, `start_url=="/"`, `display=="standalone"`, pelo menos 3 icones. Story original E15-S03 tambem pede `description`, `theme_color`, `background_color`, `orientation: any`.

**Alternativas:**
- **A. Declarar inline em `vite.config.ts` (opcao `manifest` do `VitePWA`):** plugin emite `manifest.webmanifest`. Type-safe.
- **B. Arquivo `public/manifest.webmanifest` estatico:** mais simples visualmente, mas perde integracao com hash de icones e com `injectRegister`.
- **C. Gerar via JS script:** over-engineering.

**Escolhida:** A.

**Justificativa:** `VitePWA` consome o objeto `manifest` e emite em `dist/manifest.webmanifest` com referencias corretas aos icones. Campos obrigatorios vao inline com constantes: `theme_color: '#3880ff'` (Ionic primary) e `background_color: '#ffffff'` (evita flash branco/preto — validado no Lighthouse). `orientation: 'any'` para permitir retrato e paisagem (tecnicos usam tablet landscape em galpao).

**Tradeoff:** alteracao do manifest exige rebuild — aceitavel.

**Reversibilidade:** facil.

**ADR relacionado:** nenhum.

---

### D6 — Threshold Lighthouse em CI: 0.85 (AC-006); 0.90 em producao (documentado)

**Contexto:** finding S4 do spec-audit (F-001) aponta divergencia entre story contract (0.90 flat) e spec (0.85 CI / 0.90 prod). Risco #2 da spec ja justifica a relaxacao por variabilidade de 5pp em CI.

**Alternativas:**
- **A. 0.85 em CI + 0.90 como meta documentada em `docs/operations/pwa-lighthouse.md`:** pratico, aceita variancia natural, mantem teto de qualidade documentado.
- **B. 0.90 flat em CI:** risco alto de flaky (risco #2 da spec); pode gerar reruns constantes.
- **C. 0.80 em CI (margem maior):** erode a barra de qualidade; inaceitavel.

**Escolhida:** A.

**Justificativa:** alinhada com a decisao da spec (AC-006) e com o finding S4 do audit. O audit nao bloqueia mas recomenda corrigir o Story Contract para 0.85/0.90 — ficara como follow-up no `/slice-report 017` (nao atrasa implementacao). Em producao, CI secundario pode enforcar 0.90 sobre build reproduzivel (fora de escopo deste slice).

**Tradeoff:** divergencia documental temporaria entre spec e contrato — endereca em retrospectiva.

**Reversibilidade:** facil.

**ADR relacionado:** nenhum (decisao operacional).

---

### D7 — Ausencia de cache em `/api/*` (AC-007): garantia por tres camadas

**Contexto:** isolamento multi-tenant (ADR-0016) exige que nenhuma resposta de `/api/*` seja reusada entre sessoes/usuarios pelo SW — cache de `/api/*` poderia vazar dados de tenant A para tenant B apos troca de login.

**Alternativas:**
- **A. Tres camadas defensivas:** (1) config do plugin sem nenhum `runtimeCaching` casando `/api/*`; (2) `navigateFallbackDenylist: [/^\/api\//]`; (3) teste de build que grep `dist/sw.js` por literal `"/api"` e `'/api'` retornando zero ocorrencias; (4) teste Playwright runtime: offline + `fetch('/api/ping')` deve rejeitar (TypeError de rede, nao resposta cacheada).
- **B. Confiar so na config do plugin:** passa para AC-007 happy mas silenciosamente quebra se algum futuro runtimeCaching for adicionado sem revisao.
- **C. Comentario no config:** zero enforcement.

**Escolhida:** A.

**Justificativa:** multi-tenant leak e risco de seguranca S1. Defesa em profundidade: config + denylist + grep estatico + teste runtime. Qualquer um dos 4 falhando trava o gate. Custo: 2 assertions extras (trivial).

**Tradeoff:** mais verboso. Aceitavel — o custo de um leak seria catastrofico.

**Reversibilidade:** facil (remover denylist se a politica mudar).

**ADR relacionado:** ADR-0016 (isolamento multi-tenant) — este AC e implementacao concreta do principio arquitetural.

---

### D8 — Versionamento de cache (AC-008): `VITE_APP_VERSION` em `cacheId`

**Contexto:** AC-008 exige que apos upgrade do SW os caches da versao antiga sejam limpos, evitando assets obsoletos. Workbox usa `cacheId` como prefixo nos nomes dos caches.

**Alternativas:**
- **A. `cacheId: 'kalibrium-v' + VITE_APP_VERSION` com `VITE_APP_VERSION` vinda de `package.json.version` ou env:** nome unico por release; Workbox `cleanupOutdatedCaches: true` apaga caches de prefixos antigos na ativacao.
- **B. Fixo `cacheId: 'kalibrium'`:** quebras de versao sobrescrevem in-place — menos limpo em caso de mudanca de estrategia.
- **C. Hash do build como cacheId:** nome muda a cada rebuild, mesmo sem mudanca real — churn excessivo.

**Escolhida:** A.

**Justificativa:** `VITE_APP_VERSION` sera lida pelo `vite.config.ts` via `process.env.VITE_APP_VERSION ?? pkg.version`, fallback para `package.json.version`. `cleanupOutdatedCaches: true` no config do plugin fecha o loop. Teste: Playwright instala SW, publica-se versao nova (alterando `VITE_APP_VERSION`), rebuild/reload, assertion `caches.keys()` nao contem nome antigo.

**Tradeoff:** versao precisa ser atualizada a cada release — processo de release ja prevera isso (follow-up E15-S05 se precisar automatizar).

**Reversibilidade:** facil.

**ADR relacionado:** nenhum.

---

### D9 — Mkcert + `npx serve` para `https://localhost` em dev e em CI-Lighthouse

**Contexto:** AC-001 exige HTTPS para o Chrome reconhecer instalabilidade; AC-001-A exige HTTP nao-instalavel. AC-006 (Lighthouse) roda contra `https://localhost`.

**Alternativas:**
- **A. `mkcert` local (dev) gera CA e cert para `localhost`; no CI, usa cert auto-assinado gerado on-the-fly por `openssl` com `--ignore-certificate-errors` em `chrome` do Playwright e Lighthouse com flag `--chrome-flags="--ignore-certificate-errors"`:** padrao da industria; zero custo recorrente.
- **B. Servidor HTTP puro + SW registrado manualmente mockando HTTPS:** contorna o requisito real — invalida AC-001.
- **C. Deploy em staging real para cada CI:** caro, fora de escopo.

**Escolhida:** A.

**Justificativa:** mkcert e o padrao; ja existe documentacao publica. No CI (GitHub Actions), cert auto-assinado + flag Chrome evita ter que gerenciar secret de certificado. Playwright ja suporta `ignoreHTTPSErrors: true` por config. Lighthouse suporta `--chrome-flags`.

**Tradeoff:** PM (Windows) precisa instalar mkcert uma vez — documentado em `docs/operations/pwa-https-local.md`.

**Reversibilidade:** facil.

**ADR relacionado:** nenhum (ferramenta de dev/test).

---

### D10 — Testes: Playwright para AC runtime-browser, Node --test para AC build-artefato

**Contexto:** o scaffold ja tem essa piramide (slice-016). Mantemos consistencia.

**Alternativas:**
- **A. Playwright para AC que exige browser real (SW runtime, offline mode, beforeinstallprompt, matchMedia, fetch offline) + Node `--test` com child_process para AC que inspeciona artefatos em `dist/` (grep SW, jq manifest, ls icones, imagem pixel):** piramide correta.
- **B. Tudo Playwright:** lento e frageis para checagem de arquivo.
- **C. Tudo Node puro com puppeteer manual:** reinventa Playwright.

**Escolhida:** A.

**Justificativa:** consistente com convencao de slice-016 (`tests/e2e/*.spec.ts` + `tests/scaffold/*.test.cjs`). Lighthouse roda via `child_process` em `.test.cjs` dedicado. Pixel check do maskable via `pngjs` puro (dev dep minima, zero libnative).

**Tradeoff:** duas pilhas de teste, mas e a que ja existe — zero surprise.

**Reversibilidade:** facil.

**ADR relacionado:** nenhum.

---

## 3. Mapeamento AC -> implementacao

| AC | Descricao curta | Arquivos tocados | Teste que valida |
|---|---|---|---|
| AC-001 | App instalavel em HTTPS | `vite.config.ts` (VitePWA), `index.html` (meta theme-color), `public/icons/*.png`, `scripts/pwa/serve-https.mjs` | `tests/e2e/pwa-install.spec.ts` (HTTPS via cert local; espera `beforeinstallprompt` em ate 5s; post-install valida `matchMedia('(display-mode: standalone)').matches`) |
| AC-001-A | HTTP nao-instalavel (edge) | mesmos arquivos; teste navega em `http://localhost:5174` sem TLS | `tests/e2e/pwa-install.spec.ts` — secao HTTP: `beforeinstallprompt` nao dispara em 5s; sem botao de instalar visivel |
| AC-002 | `/login` carrega offline em <2s | `vite.config.ts` (runtimeCaching HTML NetworkFirst + fallback), `src/sw-registration.ts` | `tests/e2e/pwa-offline.spec.ts` (primeira visita online, setOffline(true), reload, `/login` renderiza < 2s, zero erro de rede no console) |
| AC-002-A | Segunda visita popula offline em <5s (edge) | mesmos arquivos | `tests/e2e/pwa-offline.spec.ts` — caso "cold cache" controlado (reload com rede OFF imediatamente apos primeiro DOMContentLoaded; tolera SW warmup; <5s) |
| AC-003 | `manifest.webmanifest` valido | `vite.config.ts` (manifest: name, short_name, start_url, display, orientation, theme_color, background_color, icons[]) | `tests/scaffold/ac-003-manifest.test.cjs` (child_process `npm run build`; le `dist/manifest.webmanifest`; assert 5 campos + `icons.length >= 3`) |
| AC-004 | 3 icones obrigatorios | `public/icons/icon-192.png`, `public/icons/icon-512.png`, `public/icons/icon-512-maskable.png` (commitados), `package.json` (script `generate:icons`) | `tests/scaffold/ac-004-icons.test.cjs` (existencia + dimensoes via `pngjs` — 192/512/512) |
| AC-004-A | Maskable tem area segura (edge) | mesmo `icon-512-maskable.png` | `tests/scaffold/ac-004-icons.test.cjs` (pngjs: pixel (256,256) alpha>=254; pixel (10,10) pode ser transparente ou colorido — so valida que centro nao e transparente) |
| AC-005 | SW registrado e ativo | `src/sw-registration.ts` (registerSW com feature detection), `src/main.tsx` (chama registro) | `tests/e2e/pwa-sw.spec.ts` (`navigator.serviceWorker.controller !== null` e `registrations[0].active.state === 'activated'`) |
| AC-005-A | SW ausente nao quebra (edge) | `src/sw-registration.ts` (guard `if ('serviceWorker' in navigator)`) | `tests/e2e/pwa-sw.spec.ts` — secao legado (`Object.defineProperty(navigator, 'serviceWorker', {value: undefined})` no `init script` do Playwright; load; zero `Uncaught TypeError` no console; base UI renderiza) |
| AC-006 | Lighthouse PWA >= 0.85 em CI | build config + `docs/operations/pwa-lighthouse.md` | `tests/scaffold/ac-006-lighthouse.test.cjs` (child_process: `npm run build`, `npx serve dist` em background HTTPS, `npx lighthouse https://localhost:NNNN --only-categories=pwa --output=json --quiet --chrome-flags="--ignore-certificate-errors --headless"`, parse JSON, assert `.categories.pwa.score >= 0.85`) |
| AC-006-A | Sem `robots.txt` nao quebra (edge) | `public/` sem `robots.txt` (intencional) | mesmo `ac-006-lighthouse.test.cjs` (roda sem robots.txt; assert score continua >= 0.85) |
| AC-007 | SW nao intercepta `/api/*` (seguranca) | `vite.config.ts` (`navigateFallbackDenylist: [/^\/api\//]`; zero `runtimeCaching` com `/api/*`) | `tests/scaffold/ac-007-api-no-cache.test.cjs` (grep `dist/sw.js` por `"/api"` e `'/api'` — zero matches) + `tests/e2e/pwa-offline.spec.ts` (offline + `fetch('/api/ping')` rejeita com `TypeError: Failed to fetch` ou equivalente — nao retorna resposta cacheada) |
| AC-008 | Cleanup de versao antiga (seguranca) | `vite.config.ts` (`cacheId: 'kalibrium-v' + version`, `cleanupOutdatedCaches: true`) | `tests/e2e/pwa-upgrade.spec.ts` (instala SW v1; simula upgrade bumping `VITE_APP_VERSION=0.2.0`; rebuild pelo teste; reload; assert `caches.keys()` nao contem nome com `-v0.1.0`) |

Cobertura: **14/14 ACs** mapeados.

---

## 4. Arquivos criados, modificados e removidos

### Novos
- `public/icons/source/kalibrium-logo.svg` — SVG fonte commitado (input do `pwa-asset-generator`).
- `public/icons/icon-192.png`, `public/icons/icon-512.png`, `public/icons/icon-512-maskable.png` — gerados + commitados.
- `src/sw-registration.ts` — registra SW com feature detection (guard `'serviceWorker' in navigator`).
- `scripts/pwa/serve-https.mjs` — helper Node que sobe `serve dist` com cert auto-assinado (usado por AC-001, AC-006 em CI).
- `scripts/pwa/generate-local-cert.mjs` — helper que emite cert local com `openssl` se mkcert nao estiver presente (CI-friendly).
- `tests/e2e/pwa-install.spec.ts` — AC-001 + AC-001-A.
- `tests/e2e/pwa-offline.spec.ts` — AC-002 + AC-002-A + AC-007 runtime.
- `tests/e2e/pwa-sw.spec.ts` — AC-005 + AC-005-A.
- `tests/e2e/pwa-upgrade.spec.ts` — AC-008.
- `tests/scaffold/ac-003-manifest.test.cjs` — AC-003.
- `tests/scaffold/ac-004-icons.test.cjs` — AC-004 + AC-004-A.
- `tests/scaffold/ac-006-lighthouse.test.cjs` — AC-006 + AC-006-A.
- `tests/scaffold/ac-007-api-no-cache.test.cjs` — AC-007 (grep estatico).
- `docs/operations/pwa-icons.md` — processo de geracao/atualizacao de icones (mitigacao do risco #4 da spec).
- `docs/operations/pwa-lighthouse.md` — como rodar Lighthouse local, threshold CI vs prod (mitigacao do risco #2).
- `docs/operations/pwa-https-local.md` — setup do mkcert no Windows/macOS/Linux (suporta PM Windows).

### Modificados
- `package.json` — novas devDeps: `vite-plugin-pwa ^0.20.5`, `workbox-window ^7.3.0` (registro cliente), `pwa-asset-generator ^6.4.0` (dev), `lighthouse ^12.2.0` (dev), `serve ^14.2.4` (dev), `pngjs ^7.0.0` (dev, para pixel check). Novos scripts: `generate:icons`, `serve:https`, `test:lighthouse`. `version` usada via `VITE_APP_VERSION` durante build.
- `package-lock.json` — regenerado.
- `vite.config.ts` — importa `VitePWA` de `vite-plugin-pwa`; adiciona plugin com config detalhada (ver D1-D3-D5-D7-D8); expoe `VITE_APP_VERSION` via `define`.
- `src/main.tsx` — import de `src/sw-registration.ts` e chamada ao registro apos `createRoot`.
- `index.html` — `<meta name="theme-color" content="#3880ff">`; `<link rel="apple-touch-icon" href="/icons/icon-192.png">` para iOS splash (conforme Escopo do Story Contract).
- `.gitignore` — adiciona `certs/` (certs locais do mkcert), `lighthouse-reports/`.
- `playwright.config.js` — adiciona `ignoreHTTPSErrors: true` no projeto; preserva webServer atual.
- `tsconfig.json` — `types`: incluir `vite-plugin-pwa/client` para tipagem de `virtual:pwa-register`.
- `eslint.config.js` — ignorar `dist/sw.js` e `dist/workbox-*.js` (gerados).
- `project-state.json` — `current_slice: "017"`, `frontend_pwa_shell: true`.

### Removidos
Nenhum arquivo e removido neste slice. O scaffold de 016 permanece intacto.

---

## 5. Estrategia de testes (piramide)

**Playwright E2E (4 specs, runtime browser com SW):**
- `pwa-install.spec.ts` — 2 cases (AC-001, AC-001-A). Usa HTTPS projeto + HTTP projeto lado-a-lado.
- `pwa-offline.spec.ts` — 3 cases (AC-002, AC-002-A, AC-007-runtime).
- `pwa-sw.spec.ts` — 2 cases (AC-005, AC-005-A).
- `pwa-upgrade.spec.ts` — 1 case (AC-008).

**Node --test (4 specs .cjs, inspecao de artefato):**
- `ac-003-manifest.test.cjs` — AC-003.
- `ac-004-icons.test.cjs` — AC-004 + AC-004-A.
- `ac-006-lighthouse.test.cjs` — AC-006 + AC-006-A. **Este e o mais lento** (~40-60s: build + serve + chrome launch + audit). Separado dos outros para permitir rodar isolado se flaky.
- `ac-007-api-no-cache.test.cjs` — AC-007 estatico (grep).

**Rastreabilidade AC-ID (ADR-0017 Mudanca 1):**
- Cada spec Playwright: `test.describe('AC-NNN: titulo curto', () => { test('caso happy', ...) })`. Nome do arquivo tambem carrega AC.
- Cada `.test.cjs`: primeira linha `// @covers AC-NNN (+AC-NNN-A se edge)` e `describe('AC-NNN: ...')`.
- Audit `/audit-tests-draft 017` validara tudo antes do implementer comecar.

**Fluxo P2 (testes antes do codigo):**
1. `/draft-tests 017` cria as 8 specs vermelhas (acordo com convencoes acima).
2. Commit: `test(slice-017): ACs 001-008 red`.
3. `/audit-tests-draft 017` valida AC-IDs e semantica — zero findings.
4. `builder (implementer)` implementa tasks ordenadas (§7).
5. Cada AC verde dispara commit atomico (§7).

---

## 6. Riscos tecnicos (alem dos ja listados na spec)

- **R-A — Workbox transitiva desatualizada vs `vite-plugin-pwa`:** `vite-plugin-pwa@0.20.5` puxa Workbox ~7.x. Mitigacao: pinar `vite-plugin-pwa` e confiar em peer deps; lock regenerado de forma reproduzivel; rodar `npm audit` no CI ja existente do slice-016.
- **R-B — Node 24 (versao engine do projeto) e ESM puro (package.json `type: module`) podem colidir com `.test.cjs`:** hoje slice-016 ja usa `.test.cjs` com sucesso — logo a convencao continua funcional. Se algum teste novo precisar de ESM, usa `.test.mjs` como alternativa (sem novo risco).
- **R-C — CI sem Chrome headless pre-instalado para Lighthouse:** GH Actions `ubuntu-latest` inclui Chrome; Windows runner exigiria setup. Mitigacao: Lighthouse roda em job Ubuntu dedicado; Playwright cobre outros ACs em qualquer SO.
- **R-D — `serve` com `--ssl-cert` nao aceita cert self-signed sem flag:** mitigacao: `scripts/pwa/serve-https.mjs` define `NODE_TLS_REJECT_UNAUTHORIZED=0` so no contexto filho do teste; producao nunca usa este caminho.
- **R-E — `pwa-asset-generator` falha em headless em CI (precisa Chromium):** decisao D4 gera icones localmente e commita; CI nao roda o gerador. Zero risco em CI.
- **R-F — Flake Lighthouse (risco #2 da spec) ainda presente mesmo em 0.85:** mitigacao secundaria: teste Lighthouse tolera 1 retry automatico (`--runs=2` com `median`); se ainda flaky, escalar em retrospectiva.
- **R-G — `VITE_APP_VERSION` nao setada em dev default:** fallback para `pkg.version` em `vite.config.ts`; testes explicitamente setam `VITE_APP_VERSION` via env.

Mitigacoes de R1-R4 da spec ja documentadas la; este slice herda e reforca.

---

## 7. Ordem de implementacao (tasks)

Detalhado em `specs/017/tasks.md`. Sumario dos 7 commits atomicos planejados:

1. `chore(slice-017): add vite-plugin-pwa + workbox-window dev deps` — package.json + lock.
2. `feat(slice-017): gera icones PWA (192, 512, maskable) + SVG fonte` — D4.
3. `feat(slice-017): config VitePWA com manifest + cache shell + deny /api/*` — D1..D3, D5, D7, D8.
4. `feat(slice-017): registro SW com feature detection + theme-color meta` — D1 cliente + index.html.
5. `feat(slice-017): scripts https serve + cert local + docs operacionais` — D9.
6. `test(slice-017): ACs 001-008 red` — test-writer.
7. `feat(slice-017): ajustes finais para ACs virarem verdes` — implementer (pode subdividir se diff grande).

---

## 8. Dependencias de outros slices

- **slice-016 (E15-S02, merged):** scaffold React/TS/Vite/Ionic/Capacitor. Input direto. **Nao** modificamos nenhuma decisao do slice-016 — apenas adicionamos a camada PWA.
- **ADR-0015:** stack offline-first — este slice implementa o subcomponente "PWA web" da opcao C.
- **ADR-0016:** multi-tenant isolation — AC-007 e implementacao concreta do principio (zero cache de `/api/*`).
- **Desbloqueia:** E15-S04 (Capacitor iOS release), E15-S05 (update prompt do SW), E15-S07 (auth offline — vai usar o shell cacheado).

### Pre-requisitos externos de runtime
- Node.js >= 20 (ja engine declarado em `package.json` slice-016).
- Playwright browsers (ja instalados em slice-016).
- Chrome/Chromium disponivel em dev/CI para Lighthouse (Ubuntu padrao em GH Actions).
- mkcert (dev local — opcional, PM usa `scripts/pwa/generate-local-cert.mjs` como fallback).

---

## 9. Middleware / API / Schema

**Middleware pipeline:** N/A — slice nao toca rotas Laravel. O SW serve apenas assets estaticos do `dist/`.

**APIs novas:** zero. Pelo contrario — AC-007 garante que `/api/*` NAO e cacheado.

**Schema / migrations:** N/A — zero acesso a dados.

**Eager loading strategy:** N/A — zero Eloquent.

---

## 10. Definition of Done (checklist mecanico)

- [ ] `npm install` exit 0 com novas deps instaladas (vite-plugin-pwa, workbox-window, pwa-asset-generator, lighthouse, serve, pngjs).
- [ ] `npm run build` gera `dist/sw.js`, `dist/manifest.webmanifest`, `dist/workbox-*.js` (AC-002, AC-003).
- [ ] `dist/icons/` contem `icon-192.png`, `icon-512.png`, `icon-512-maskable.png` com dimensoes corretas (AC-004).
- [ ] Pixel (256,256) de `icon-512-maskable.png` tem alpha >= 254 (AC-004-A).
- [ ] Playwright `pwa-install.spec.ts` verde para AC-001 (HTTPS) e AC-001-A (HTTP).
- [ ] Playwright `pwa-offline.spec.ts` verde para AC-002, AC-002-A e fetch-`/api/*`-rejeita.
- [ ] Playwright `pwa-sw.spec.ts` verde para AC-005 e AC-005-A.
- [ ] Playwright `pwa-upgrade.spec.ts` verde para AC-008.
- [ ] Node test `ac-003-manifest.test.cjs` verde.
- [ ] Node test `ac-004-icons.test.cjs` verde.
- [ ] Node test `ac-006-lighthouse.test.cjs` verde com score >= 0.85 (AC-006, AC-006-A).
- [ ] Node test `ac-007-api-no-cache.test.cjs` verde: grep em `dist/sw.js` por `"/api"` e `'/api'` retorna zero.
- [ ] `npm run lint` exit 0.
- [ ] 8 specs de teste estavam vermelhas antes do commit `feat(slice-017): ajustes finais...` e verdes depois (P2).
- [ ] Cada spec tem AC-ID rastreavel (ADR-0017 Mudanca 1).
- [ ] Commit de testes precede commit de implementacao.
- [ ] Nenhuma alteracao em `.claude/`, `scripts/hooks/`, `docs/protocol/`, `docs/constitution.md`, `CLAUDE.md`.
- [ ] `project-state.json` atualizado (`current_slice: "017"`, `frontend_pwa_shell: true`).

---

## 11. Rollback / plano B

- `git revert` do merge remove toda a camada PWA; scaffold slice-016 continua funcional (PWA so adiciona, nao substitui).
- Se `vite-plugin-pwa` apresentar bug bloqueante: plano B e migrar para `injectManifest` (D1 alternativa B) em follow-up; scaffold continua apto a merge.
- Se Lighthouse ficar consistentemente abaixo de 0.85 em CI: investigar na retrospectiva + emenda de threshold para 0.80 via ADR emenda (nao via bypass).

---

## 12. Follow-ups (nao criam ADR neste slice)

- `FE-PWA-CONTRACT-01`: atualizar `epics/E15/stories/E15-S03.md` AC-006 para CI=0.85/prod=0.90 (fecha F-001 do audit). Responsavel: orchestrator em `/slice-report 017`.
- `FE-PWA-02`: prompt de update do SW em UI (E15-S05) — quando nova versao ativada, mostrar botao "recarregar para atualizar".
- `FE-PWA-03`: migrar para `injectManifest` quando E15-S06/E16 exigirem Background Sync.
- `CI-LIGHTHOUSE-01`: job dedicado Ubuntu para Lighthouse no GH Actions (se ainda nao existir do slice-016).
- `FE-ICONS-01`: pipeline de regeracao de icones em CI quando SVG fonte muda (detectar por hash).

---

## 13. Fora de escopo (reconfirmando a spec e a ADR-0015)

- Autenticacao offline (E15-S07).
- Background Sync de mutations offline (E16).
- Push notifications FCM/APNs (E15-S08 / E12 / E21).
- Wrapper Capacitor iOS nativo (E15-S04).
- SQLite/SQLCipher local (E15-S06).
- Update prompt em UI (E15-S05).
- Remocao de pacotes Livewire do `composer.json` (follow-up FE-COMPOSER-01 em E15-S10).

---

**Rastreabilidade:**
- Spec: `specs/017/spec.md` (14 ACs, verdict approved 2026-04-17).
- Audit: `specs/017/spec-audit.json` (approved com F-001 S4 sobre threshold — respeitado em D6).
- Story: `epics/E15/stories/E15-S03.md`.
- Slice anterior: `slice-016` (scaffold, merged).
- ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenant — AC-007 concretiza).
- REQs: REQ-FLD-001, REQ-SEC-001.
- Desbloqueia: E15-S04, E15-S05, E15-S07.
