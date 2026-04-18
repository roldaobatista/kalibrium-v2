import { test, expect } from '@playwright/test';

/**
 * @covers AC-005 service worker registrado e ativo (happy)
 * @covers AC-005-A SW indisponivel nao quebra a UI (edge)
 *
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * RED enquanto src/sw-registration.ts e VitePWA config nao existem.
 */

const BASE = process.env.KALIB_PWA_HTTPS_URL ?? 'https://localhost:4173';

test.describe('AC-005: Service Worker registrado e ativo em HTTPS', () => {
    test('AC-005: navigator.serviceWorker.controller !== null apos primeiro load', async ({ page }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });

        // Aguarda ate 10s para o SW ativar (primeira visita: registra + ativa).
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        const hasController = await page.evaluate(() => navigator.serviceWorker.controller !== null);
        expect(hasController, 'navigator.serviceWorker.controller deve ser !== null apos primeira visita').toBe(true);
    });

    test('AC-005: registrations[0].active.state === "activated"', async ({ page }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        const state = await page.evaluate(async () => {
            const regs = await navigator.serviceWorker.getRegistrations();
            if (regs.length === 0) return { count: 0, state: null as string | null };
            return { count: regs.length, state: regs[0].active?.state ?? null };
        });

        expect(state.count, 'deve existir pelo menos 1 registration do SW').toBeGreaterThan(0);
        expect(state.state, `registrations[0].active.state deve ser "activated", got: ${state.state}`).toBe('activated');
    });
});

test.describe('AC-005-A: navegador sem SW nao quebra a UI (edge)', () => {
    test('AC-005-A: com navigator.serviceWorker=undefined, app carrega sem Uncaught TypeError', async ({
        browser,
    }) => {
        // Context novo com init script removendo o SW API antes do load.
        const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
        await ctx.addInitScript(() => {
            // Simula navegador legado: remove navigator.serviceWorker.
            try {
                Object.defineProperty(navigator, 'serviceWorker', {
                    configurable: true,
                    get: () => undefined,
                });
            } catch {
                // se ja for read-only, fallback: redefine via proto.
                (navigator as unknown as { serviceWorker?: unknown }).serviceWorker = undefined;
            }
        });

        const page = await ctx.newPage();
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        page.on('pageerror', (err) => consoleErrors.push(`pageerror: ${err.message}`));

        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });

        // UI base deve renderizar mesmo sem SW.
        const ionRoot = await page.locator('ion-app, ion-page, ion-content, #root').count();
        expect(ionRoot, 'shell da UI base deve renderizar mesmo sem suporte a SW').toBeGreaterThan(0);

        // Nenhum Uncaught TypeError vindo da tentativa de registro do SW.
        const typeErrors = consoleErrors.filter((e) => /Uncaught TypeError|Cannot read propert/i.test(e));
        expect(
            typeErrors,
            `nao pode haver Uncaught TypeError ao tentar registrar SW sem suporte. Got:\n${typeErrors.join('\n')}`,
        ).toEqual([]);

        await ctx.close();
    });
});
