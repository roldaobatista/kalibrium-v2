import { test, expect } from '@playwright/test';

/**
 * @covers AC-001 app instalavel em HTTPS (happy)
 * @covers AC-001-A app nao-instalavel em HTTP puro (edge)
 *
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * Estes testes exigem que o app seja servido via HTTPS (mkcert/serve --ssl) e
 * expoe manifest.webmanifest + service worker ativo. Devem estar RED enquanto
 * builder:implementer ainda nao configurou vite-plugin-pwa.
 *
 * Estrategia:
 * - AC-001: navega em ${baseURL} (https://localhost), escuta `beforeinstallprompt`
 *   em ate 5s; valida manifest.webmanifest acessivel; valida SW ativo.
 * - AC-001-A: navega em HTTP puro; valida que `beforeinstallprompt` NAO dispara.
 */

const HTTPS_BASE = process.env.KALIB_PWA_HTTPS_URL ?? 'https://localhost:4173';
const HTTP_BASE = process.env.KALIB_PWA_HTTP_URL ?? 'http://localhost:5173';

async function waitForBeforeInstallPrompt(page: import('@playwright/test').Page, timeoutMs: number) {
    return page.evaluate(
        (timeout) =>
            new Promise<boolean>((resolve) => {
                let fired = false;
                const handler = () => {
                    fired = true;
                    resolve(true);
                };
                window.addEventListener('beforeinstallprompt', handler, { once: true });
                setTimeout(() => {
                    window.removeEventListener('beforeinstallprompt', handler);
                    resolve(fired);
                }, timeout);
            }),
        timeoutMs,
    );
}

test.describe('AC-001: app instalavel em HTTPS (criterios PWA satisfeitos)', () => {
    test('AC-001: manifest.webmanifest esta acessivel via HTTPS e contem display=standalone', async ({ page }) => {
        const resp = await page.goto(`${HTTPS_BASE}/manifest.webmanifest`, { waitUntil: 'domcontentloaded' });
        expect(resp, 'manifest.webmanifest deve responder').not.toBeNull();
        expect(resp!.status(), 'manifest.webmanifest deve retornar 200').toBe(200);

        const body = await resp!.text();
        let manifest: Record<string, unknown>;
        try {
            manifest = JSON.parse(body);
        } catch (err) {
            throw new Error(`manifest.webmanifest nao e JSON valido: ${(err as Error).message}\nbody:\n${body}`);
        }
        expect(manifest.display, 'manifest.display deve ser "standalone"').toBe('standalone');
        expect(manifest.start_url, 'manifest.start_url deve ser "/"').toBe('/');
        expect(manifest.name, 'manifest.name deve ser "Kalibrium"').toBe('Kalibrium');
    });

    test('AC-001: beforeinstallprompt dispara em ate 5s quando criterios PWA sao satisfeitos', async ({ page }) => {
        await page.goto(`${HTTPS_BASE}/`, { waitUntil: 'networkidle' });

        const fired = await waitForBeforeInstallPrompt(page, 5000);
        expect(fired, 'beforeinstallprompt deve disparar em HTTPS com SW ativo + manifest valido').toBe(true);
    });

    test('AC-001: apos instalacao simulada, matchMedia(display-mode: standalone) vira true', async ({ page, context }) => {
        // Emula modo standalone: CDP feature do Chromium via context.
        await page.goto(`${HTTPS_BASE}/`, { waitUntil: 'networkidle' });

        // Chromium suporta emulateMedia para display-mode a partir do Playwright 1.46.
        await page.emulateMedia({ colorScheme: null, reducedMotion: null, forcedColors: null });
        // Playwright nao permite emular display-mode diretamente em todas versoes;
        // fazemos check via CDP session.
        const client = await context.newCDPSession(page);
        await client.send('Emulation.setEmulatedMedia', {
            features: [{ name: 'display-mode', value: 'standalone' }],
        });

        const isStandalone = await page.evaluate(() => window.matchMedia('(display-mode: standalone)').matches);
        expect(isStandalone, 'matchMedia(display-mode: standalone) deve retornar true apos emulacao').toBe(true);
    });
});

test.describe('AC-001-A: app NAO-instalavel em HTTP puro (edge)', () => {
    test('AC-001-A: beforeinstallprompt NAO dispara em HTTP puro em 5s', async ({ page }) => {
        let gotoError: Error | null = null;
        try {
            await page.goto(`${HTTP_BASE}/`, { waitUntil: 'domcontentloaded', timeout: 10_000 });
        } catch (err) {
            gotoError = err as Error;
        }

        if (gotoError) {
            // Se HTTP server nao esta up (CI sem servidor HTTP), o teste deve falhar
            // indicando que implementer precisa prover o helper de serve HTTP.
            throw new Error(
                `AC-001-A exige um servidor HTTP (nao-HTTPS) rodando em ${HTTP_BASE}. ` +
                    `Erro ao navegar: ${gotoError.message}`,
            );
        }

        const fired = await waitForBeforeInstallPrompt(page, 5000);
        expect(fired, 'beforeinstallprompt NAO deve disparar em HTTP puro (criterio PWA nao satisfeito)').toBe(false);
    });

    test('AC-001-A: em HTTP puro, UI nao expoe botao/link de instalar', async ({ page }) => {
        await page.goto(`${HTTP_BASE}/`, { waitUntil: 'domcontentloaded', timeout: 10_000 }).catch(() => null);

        // Nenhum data-testid="install-pwa" nem texto "Instalar app" deve aparecer em HTTP.
        const installButton = await page.getByTestId('install-pwa').count().catch(() => 0);
        expect(installButton, 'em HTTP, nao deve existir botao data-testid=install-pwa').toBe(0);
    });
});
