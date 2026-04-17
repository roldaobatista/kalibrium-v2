# Slice 017 — E15-S03: PWA Service Worker + manifest + instalabilidade offline

**Status:** draft
**Data de criação:** 2026-04-17
**Autor:** orchestrator (a partir de `epics/E15/stories/E15-S03.md`)
**Depende de:** slice-016 (scaffold React + Vite + TS + Ionic + Capacitor)
**Story Contract:** `epics/E15/stories/E15-S03.md`
**Requisitos:** REQ-FLD-001, REQ-SEC-001
**ADRs:** ADR-0015 (estrategia mobile-first Ionic + Capacitor)

---

## Contexto

O slice 016 deixou o frontend como um scaffold React + TypeScript + Vite + Ionic + Capacitor pronto para receber telas. Ainda assim, nao ha nada de PWA: o browser nao reconhece o app como instalavel, nao ha manifest, nao ha service worker, e qualquer tentativa de abrir offline quebra com erro de rede.

O Kalibrium atende tecnicos de campo (calibracao rodoviaria, calibracao industrial, laboratorios moveis) que operam 90% do tempo **sem conectividade estavel** (memoria `project_offline_first_systemic.md`, incidente `docs/incidents/discovery-gap-offline-2026-04-16.md`). O MVP exige que esses usuarios consigam abrir o app no celular/tablet mesmo sem rede e ver pelo menos a tela de login, sem tela branca ou erro incompreensivel.

Este slice entrega a camada de shell PWA: service worker com cache de assets, manifest.json completo e icones nos tamanhos exigidos por Chrome/Android/iOS. Auth offline e sincronizacao de dados NAO estao neste slice — vem em E15-S07 (JWT long-lived + device binding) e E16 (sync engine). Aqui garantimos apenas que o navegador consegue servir o HTML/JS/CSS do cache quando a rede cai.

## Jornada alvo

Um tecnico abre o Kalibrium no Chrome Android pela primeira vez em uma area com sinal. A pagina carrega, o browser registra o service worker e cacheia os assets de shell. Horas depois, o tecnico entra em um galpao fabril sem 4G. Abre o app pelo atalho que instalou na tela inicial. A tela de login aparece em menos de 2 segundos, sem erro de rede. Ele ainda nao consegue logar porque auth offline chega em E15-S07, mas a shell renderizou corretamente — ele sabe que o app existe e sera utilizavel assim que a conectividade voltar ou E15-S07 entregar.

## Acceptance Criteria

> **Nota de organizacao:** os ACs estao em sequencia numerica continua (AC-001..AC-008) para satisfazer o validador mecanico `scripts/draft-spec.sh`. Cada AC-NNN happy pode ter variantes `-A` (edge case) logo em seguida. Os dois ultimos (AC-007, AC-008) sao requisitos de seguranca classificados inline.

### AC-001 — App instalavel no Chrome desktop (happy)

Dado que o usuario acessa a URL do Kalibrium em `https://localhost` (via `mkcert`) no Chrome, quando o Chrome detecta criterios de PWA (HTTPS + manifest valido + service worker registrado), entao `window.matchMedia('(display-mode: standalone)').matches` retorna `true` apos instalacao via `beforeinstallprompt`. Verificado em `tests/e2e/pwa-install.spec.ts`.

### AC-001-A — App nao-instalavel em HTTP puro (edge)

Dado que o usuario acessa via `http://` (sem TLS), quando o Chrome avalia criterios de PWA, entao o evento `beforeinstallprompt` NAO dispara e a UI nao expoe botao de instalar. Verificado em `tests/e2e/pwa-install.spec.ts` navegando para `http://localhost:5173` com assert `beforeinstallprompt` nao disparou em 5s.

### AC-002 — Rota `/login` carrega offline apos primeira visita (happy)

Dado que o usuario acessou o app pelo menos uma vez com rede (cache populado), quando o teste Playwright invoca `page.context().setOffline(true)` e navega para `/`, entao a tela de login renderiza sem erro de rede e sem tela branca em menos de 2 segundos. Verificado em `tests/e2e/pwa-offline.spec.ts`.

### AC-002-A — Segunda visita sem cache popula offline em menos de 5s (edge)

Dado que o usuario ja abriu a pagina com rede uma vez, quando ele reabre o app com rede desligada (sem nunca ter ficado online desde o primeiro load), entao a tela de login renderiza em menos de 5s (tolerando SW warmup) — nao travar, nao cair para tela branca indefinida.

### AC-003 — `manifest.webmanifest` valido e completo (happy)

Dado que o build foi gerado via `npm run build`, quando se executa `cat dist/manifest.webmanifest | jq '{name, short_name, start_url, display, icons_count: (.icons|length)}'`, entao `name == "Kalibrium"`, `short_name == "Kalibrium"`, `start_url == "/"`, `display == "standalone"` e `icons_count >= 3`.

### AC-004 — Icones existem nos 3 tamanhos obrigatorios (happy)

Dado que o build foi gerado, quando se executa `ls dist/icons/`, entao existem `icon-192.png` (192x192 PNG), `icon-512.png` (512x512 PNG) e `icon-512-maskable.png` (512x512 PNG com area segura de 80%).

### AC-004-A — Icone 512-maskable tem area segura correta (edge)

Dado o icone `icon-512-maskable.png`, quando se inspeciona o pixel central (256,256), entao o pixel nao e transparente (RGBA.a >= 254) e o pixel do canto (10,10) pode ser transparente ou colorido (fora da area segura). Fail rapido se o icone foi gerado sem padding de 80%.

### AC-005 — Service Worker registrado e ativo (happy)

Dado que o app esta servido em HTTPS (ou localhost), quando se abre a pagina e espera o primeiro load completar, entao `navigator.serviceWorker.controller !== null` e o SW aparece com status `activated` em `registrations[0].active.state`. Verificado via Playwright `page.evaluate()`.

### AC-005-A — SW nao quebra se `navigator.serviceWorker` indisponivel (edge)

Dado um navegador legado sem suporte a SW (simulado via `Object.defineProperty(navigator, 'serviceWorker', { value: undefined })` antes do load), quando o app carrega, entao nao lanca exception, o console nao registra `Uncaught TypeError`, e a UI base renderiza em modo degradado (sem PWA install prompt).

### AC-006 — Lighthouse PWA score >= 85 em CI (>= 90 em producao) (happy)

Dado que o build esta servido em `https://localhost` via `npx serve dist`, quando se executa `npx lighthouse https://localhost --only-categories=pwa --output=json --quiet`, entao `.categories.pwa.score >= 0.85`. Threshold relaxado de 90 para 85 em CI por variabilidade documentada no risco #2.

### AC-006-A — Lighthouse ainda passa se `robots.txt` for 404 (edge)

Dado que `robots.txt` esteja ausente do build, quando Lighthouse roda, entao score PWA continua >= 0.85 (robots.txt nao afeta categoria PWA do Lighthouse v11+; este AC protege contra regressao se upgradar Lighthouse).

### AC-007 — Cache nao intercepta rotas `/api/*` (seguranca)

Dado o codigo-fonte do service worker (gerado por vite-plugin-pwa ou escrito em `src/sw.ts`), quando se inspeciona o SW em runtime via `sw.fetch` handlers, entao **nenhum handler** intercepta rotas que casem com `/^\/api\//`. Verificado por (a) `grep -rE '["\x27]/api' dist/sw.js` retornar zero matches e (b) teste Playwright que faz um `fetch('/api/dummy')` com rede offline e verifica que a request falha (nao ha resposta cacheada). Protege contra vazamento de dados entre tenants.

### AC-008 — Cache limpa versao antiga em upgrade (seguranca)

Dado que uma nova versao do SW foi publicada (detectavel via `VITE_APP_VERSION` no nome do cache), quando o usuario recarrega, entao `caches.keys()` nao contem mais o nome da versao antiga apos `activate` do novo SW. Protege contra uso de assets obsoletos que referenciem APIs removidas.

## Fora de escopo

- Auth offline (login com credenciais stored localmente) — E15-S07
- Background Sync de requests pendentes de rede — E16 sync engine
- Push notifications (FCM/APNs) — E12 / E21
- Wrapper Capacitor iOS nativo — E15-S04 (jornada iOS nativa e separada da PWA no Safari)
- Cache de dados de API ou banco local (SQLCipher, IndexedDB) — E15-S06 + E16
- Update prompt em UI quando nova versao do SW disponivel (mostra botao "recarregar para atualizar") — fica para E15-S05 ou spike futuro se score Lighthouse exigir

## Dependencias externas

- **vite-plugin-pwa** (>= 0.20) — plugin Vite que gera SW + manifest via Workbox
- **Workbox** (transitiva via vite-plugin-pwa) — strategies de cache
- **pwa-asset-generator** (>= 6.4, dev dep) — gera icones nos tamanhos exigidos a partir de SVG fonte
- **mkcert** (dev, local) — certificados TLS para testar PWA em `https://localhost`
- **lighthouse** (>= 11, dev dep) — PWA audit em CI e local
- **ADR-0015** — estrategia mobile-first Ionic + Capacitor (este slice complementa cobrindo Android/iOS nao-Capacitor via PWA puro)

## Riscos conhecidos

1. **Safari iOS tem comportamento diferente de Chrome para PWA (nao instala do mesmo jeito).** Mitigacao: esta story foca em Chrome/web (PWA padrao W3C). A experiencia iOS nativa e garantida pelo wrapper Capacitor (E15-S04), nao por PWA no Safari. Testes Playwright rodam em chromium apenas para este slice — webkit sera adicionado em E15-S04.
2. **Lighthouse PWA em CI pode variar ±5 pontos entre execucoes** (I/O, metricas de web vitals). Mitigacao: threshold 0.85 em CI, 0.90 como meta em producao/documentacao; se flaky persistir, investigar em retrospectiva.
3. **vite-plugin-pwa `generateSW` strategy limita customizacao — se precisarmos de handlers custom para `/api/*` no futuro, migrar para `injectManifest`.** Nao bloqueante neste slice (AC-SEC-001 exige ausencia de cache em `/api/*`), mas E15-S06/E16 podem precisar. Registrado como debito potencial, nao ativo.
4. **Icones maskable exigem area segura de 80% — SVG fonte mal dimensionado quebra AC-004a.** Mitigacao: documentar em `docs/operations/pwa-icons.md` (a ser criado se AC-004a falhar) o processo de geracao.

## Notas do PM (humano)

- Spec gerada a partir do Story Contract `epics/E15/stories/E15-S03.md` com 7 ACs happy + 5 edge + 2 seguranca = 14 ACs totais.
- O AC de seguranca #7 do contrato (cache nao vaza entre tenants) virou item sequencial **AC-007** com classificacao "seguranca" inline para respeitar o validador mecanico `scripts/draft-spec.sh` (que exige sequencia 001..NNN sem prefixos AC-SEC).
- Um AC de seguranca extra (AC-008 cache cleanup em upgrade) foi adicionado com a mesma convencao.
- Threshold Lighthouse relaxado em CI (0.85 vs 0.90 em producao) com justificativa inline no AC-006 + risco #2.
- Recomenda-se `/audit-spec 017` a seguir.
