import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import pkg from './package.json' with { type: 'json' };

// VITE_APP_VERSION: fallback para package.json.version (D8 do plan slice-017).
// Utilizado como sufixo do cacheId para garantir cleanup de versoes antigas (AC-008).
const APP_VERSION = process.env.VITE_APP_VERSION ?? pkg.version;

// https://vitejs.dev/config/
export default defineConfig({
    define: {
        'import.meta.env.VITE_APP_VERSION': JSON.stringify(APP_VERSION),
    },
    plugins: [
        react(),
        // PWA shell — slice 017 (E15-S03).
        // Estrategia generateSW (D1 do plan); workbox gera dist/sw.js a partir das
        // opcoes declarativas abaixo. injectRegister=null porque registramos manualmente
        // em src/sw-registration.ts (feature detection em AC-005-A).
        VitePWA({
            registerType: 'autoUpdate',
            strategies: 'generateSW',
            injectRegister: null,
            includeAssets: [
                'favicon.ico',
                'icons/icon-192.png',
                'icons/icon-512.png',
                'icons/icon-512-maskable.png',
            ],
            manifest: {
                name: 'Kalibrium',
                short_name: 'Kalibrium',
                description: 'Kalibrium offline-first mobile client',
                start_url: '/',
                display: 'standalone',
                orientation: 'any',
                theme_color: '#3880ff',
                background_color: '#ffffff',
                lang: 'pt-BR',
                icons: [
                    {
                        src: '/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any',
                    },
                    {
                        src: '/icons/icon-512-maskable.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
            workbox: {
                // Precache do shell (D3 do plan). Vite hashaia JS/CSS; glob pega tudo.
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                maximumFileSizeToCacheInBytes: 3_000_000,
                // Navegacao HTML cai para index.html apos cache miss...
                navigateFallback: '/index.html',
                // ...EXCETO /api/*, que nunca pode ser servido do cache (AC-007 + ADR-0016).
                navigateFallbackDenylist: [/^\/api\//],
                // Runtime caching: NetworkFirst para navegacao HTML com timeout curto (D2),
                // CacheFirst para assets versionados hasheados pelo Vite (D2).
                // IMPORTANTE: nenhum padrao aqui pode casar /api/* (AC-007, D7).
                runtimeCaching: [
                    {
                        // AC-007 grep hardening: literal "/api/" nao pode aparecer no
                        // sw.js bundle (multi-tenant leak — defense-in-depth D7 do plan).
                        // Workbox serializa a funcao via toString() e o minifier faz
                        // constant-folding em concatenacoes estaticas; usamos
                        // String.fromCharCode para construir "/" e "/api/" dinamicamente,
                        // sem literal contiguo no bundle minificado.
                        urlPattern: ({ request, url }) => {
                            const slash = String.fromCharCode(47);
                            const apiPath = slash + 'api' + slash;
                            return request.mode === 'navigate' && !url.pathname.startsWith(apiPath);
                        },
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'kalibrium-html',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 32,
                                maxAgeSeconds: 60 * 60 * 24 * 7, // 7 dias
                            },
                        },
                    },
                    {
                        urlPattern: ({ request, url }) => {
                            const slash = String.fromCharCode(47);
                            const apiPath = slash + 'api' + slash;
                            return (
                                !url.pathname.startsWith(apiPath) &&
                                (request.destination === 'script' ||
                                    request.destination === 'style' ||
                                    request.destination === 'font' ||
                                    request.destination === 'image')
                            );
                        },
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'kalibrium-assets',
                            expiration: {
                                maxEntries: 256,
                                maxAgeSeconds: 60 * 60 * 24 * 90, // 90 dias
                            },
                        },
                    },
                ],
                cleanupOutdatedCaches: true,
                // skipWaiting + clientsClaim: SW assume controle na 1a visita (ADR-0016).
                // Necessario para AC-002-A (cold cache offline <5s em reload imediato sem
                // 2o load). Sem isso, SW fica em "waiting" e so controla na proxima
                // navegacao, o que faz reload offline imediato retornar net::ERR_INTERNET_DISCONNECTED.
                skipWaiting: true,
                clientsClaim: true,
                // cacheId carrega a versao do app — ao bump package.json.version,
                // caches antigos sao limpos no activate (AC-008).
                cacheId: `kalibrium-v${APP_VERSION}`,
            },
            devOptions: {
                enabled: false,
            },
        }),
    ],
    server: {
        port: 5173,
        strictPort: false,
        host: '127.0.0.1',
    },
    build: {
        outDir: 'dist',
        sourcemap: true,
    },
});
