# PWA — Estratégia de Cache do Service Worker

Slice 017 · Operação · ADR-0016

## Princípio central

**Dados da API (`/api/**`) NUNCA são servidos do Service Worker cache.** Essa é a regra de ouro do offline-first híbrido do Kalibrium:

1. O SW cuida apenas do **app shell** (HTML, JS, CSS, fontes, ícones) — código estático versionado pelo Vite.
2. Dados mutáveis (clientes, contatos, calibrações, etc.) passam por um **cache de dados separado** gerenciado por IndexedDB + sync explícito — NÃO pelo SW.

Isso evita:

- Servir resposta API velha enquanto o usuário editou o recurso offline.
- Conflitos silenciosos de versão entre cache-first e a fonte canônica (PostgreSQL).
- Problemas de autorização expirada (JWT no cache serve 401 stale).

## Estratégias aplicadas

Configuradas em `vite.config.ts` via `VitePWA.workbox`:

### 1. Precache — App shell completo

```ts
globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}']
```

Todos os arquivos do build com estas extensões entram no precache do SW, servidos via **cache-first** com validação por hash do Vite. Quando o build gera um novo hash no nome do arquivo, o SW detecta o diff no `sw.js` e atualiza no próximo ciclo.

### 2. Navigation requests (HTML) — NetworkFirst com timeout curto

```ts
{
    urlPattern: ({ request, url }) =>
        request.mode === 'navigate' && !url.pathname.startsWith('/api/'),
    handler: 'NetworkFirst',
    options: {
        cacheName: 'kalibrium-html',
        networkTimeoutSeconds: 3,
        expiration: { maxEntries: 32, maxAgeSeconds: 60 * 60 * 24 * 7 },
    },
}
```

Racional: queremos sempre HTML fresh quando tem rede (deploy novo chega rápido no usuário), mas fallback para cache em 3s se a rede é lenta ou offline. O SPA shell é pequeno (<50KB), então 7 dias de TTL é seguro.

### 3. Assets versionados — CacheFirst

```ts
{
    urlPattern: ({ request, url }) =>
        !url.pathname.startsWith('/api/') &&
        (request.destination === 'script' ||
            request.destination === 'style' ||
            request.destination === 'font' ||
            request.destination === 'image'),
    handler: 'CacheFirst',
    options: {
        cacheName: 'kalibrium-assets',
        expiration: { maxEntries: 256, maxAgeSeconds: 60 * 60 * 24 * 90 },
    },
}
```

Safe por construção: Vite emite `app.abc123.js` com hash único no nome. Cache-first 90 dias porque um asset hasheado nunca muda de conteúdo dado um hash fixo.

### 4. API requests — ZERO handler, ZERO cache

Nenhum handler cobre `/api/*`. E mais: temos uma **denylist explícita** para garantir:

```ts
navigateFallbackDenylist: [/^\/api\//]
```

Isso garante que o fallback de navegação (que serve `/index.html` para rotas 404) **NÃO** se aplica a rotas de API. Se `/api/clientes` retorna 404, o browser vê 404 real — não o index.html fantasma.

O teste `tests/scaffold/pwa-cache-version.test.cjs` (AC-007) faz grep estático no `dist/sw.js` procurando por literais `"/api"` / `'/api'` e **falha se encontrar qualquer um** (exceto o regex de denylist `/^\\/api\\//`).

## Versionamento de cache (AC-008)

```ts
cacheId: `kalibrium-v${APP_VERSION}`,
cleanupOutdatedCaches: true,
```

Onde `APP_VERSION = process.env.VITE_APP_VERSION ?? package.json.version`.

### Como funciona

- `cacheId` prefixa **todos os caches** do SW: `kalibrium-v0.1.0-precache-v2-...`, `kalibrium-v0.1.0-kalibrium-html`, etc.
- Quando o `package.json.version` bumpa (ou quando CI seta `VITE_APP_VERSION=<commit-sha>`), o prefixo muda.
- `cleanupOutdatedCaches` apaga caches que não batem com o prefixo atual no evento `activate` do SW.
- Resultado: **usuário em versão antiga recebe cache new + cache old some no próximo ciclo**.

### Quando bumpar

| Trigger                                      | Bump necessário?                  |
| -------------------------------------------- | --------------------------------- |
| Adicionar rota / tela nova                   | Não (Vite hashaia JS)             |
| Mudar shell de forma profunda (index.html)   | Sim (bump patch)                  |
| Alterar política de cache do próprio SW      | Sim (bump minor ou maior)         |
| Alterar manifest (nome, ícones)              | Sim (força re-install do PWA)     |
| Mudar conteúdo de asset **sem** mudar o path | **Nunca deve acontecer** — Vite hashaia sempre |

## Teste da estratégia

| AC       | Teste                                           | O que valida                                             |
| -------- | ----------------------------------------------- | -------------------------------------------------------- |
| AC-007   | `pwa-cache-version.test.cjs` § AC-007           | `dist/sw.js` não contém `"/api"` ou `'/api'` como handler |
| AC-007   | `pwa-api-no-cache.spec.ts` (E2E)                | Runtime: request a `/api/*` não é servido do cache      |
| AC-008   | `pwa-cache-version.test.cjs` § AC-008           | `sw.js` contém `"kalibrium-v<version>"` + `cleanupOutdatedCaches` |
| AC-008   | `pkg.version` aparece literal no `sw.js`         | Versão do package.json foi embutida via define()        |

## Troubleshooting

| Sintoma                                                     | Causa provável                                            | Fix                                                      |
| ----------------------------------------------------------- | --------------------------------------------------------- | -------------------------------------------------------- |
| `/api/clientes` retorna JSON fantasma depois do logout      | Algum handler customizado casou `/api/*`                  | Grep `runtimeCaching` no vite.config.ts, remover padrão  |
| Usuário em v0.1.0 recebe shell v0.2.0 mas asset velho       | `cleanupOutdatedCaches: false` OU asset sem hash no nome  | Ativar flag; garantir Vite rollup hashing                |
| `sw.js` contém literal `/api` mas é só texto em comentário  | Build source-map inline                                   | Revisar `build.sourcemap` no vite.config.ts              |
| Lighthouse reclama "Service worker does not control page"   | Timing: teste rodou antes do `activate`                   | `waitForFunction` por `navigator.serviceWorker.controller !== null` |

## Referência cruzada

- `docs/adr/adr-0016-offline-first-architecture.md` — decisão de cache dual (SW shell + IndexedDB dados).
- `docs/operations/pwa-icons.md` — ícones e manifest.
- `docs/operations/pwa-local-https.md` — servir em HTTPS local para testar.
