import { test, expect } from '@playwright/test';

/**
 * @covers AC-007 cache nao intercepta rotas /api/* (seguranca multi-tenant)
 *
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * Teste complementar runtime ao grep estatico em tests/scaffold/pwa-cache-version.test.cjs.
 * Garante que em runtime (SW ativo, rede OFF), chamadas a /api/* NAO retornam resposta
 * cacheada — falham naturalmente com erro de rede.
 *
 * RED ate vite-plugin-pwa estar configurado com navigateFallbackDenylist: [/^\/api\//]
 * e sem runtimeCaching casando /api/*.
 */

const BASE = process.env.KALIB_PWA_HTTPS_URL ?? 'https://localhost:4173';

test.describe('AC-007: SW nao serve /api/* do cache em runtime', () => {
    test('AC-007: offline, fetch(/api/customers) falha com erro de rede (sem vazamento cross-tenant)', async ({
        page,
        context,
    }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        await context.setOffline(true);

        const result = await page.evaluate(async () => {
            try {
                const r = await fetch('/api/customers', { method: 'GET', cache: 'no-store' });
                const text = await r.text().catch(() => '');
                return { ok: true, status: r.status, bodyLength: text.length };
            } catch (err) {
                return { ok: false, error: (err as Error).message };
            }
        });

        expect(
            result.ok,
            `offline + fetch /api/customers deve FALHAR (SW nao pode ter cacheado /api/*). Got: ${JSON.stringify(result)}`,
        ).toBe(false);
    });

    test('AC-007: caches.match(/api/ping) retorna undefined (API nunca foi cacheada)', async ({ page }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        // Faz uma requisicao online primeiro a /api/ping (pode 404, nao importa —
        // importa que o SW NAO cacheie a resposta).
        await page.evaluate(() => fetch('/api/ping').catch(() => null));

        const cached = await page.evaluate(async () => {
            const match = await caches.match('/api/ping');
            if (!match) return { cached: false };
            return { cached: true, status: match.status };
        });

        expect(
            cached.cached,
            `caches.match("/api/ping") deve ser undefined — SW nao pode cachear /api/*. Got: ${JSON.stringify(cached)}`,
        ).toBe(false);
    });

    test('AC-007: caches.keys() nao contem nome que referencie api', async ({ page }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        const keys = await page.evaluate(() => caches.keys());
        const apiCaches = keys.filter((k) => /api/i.test(k));
        expect(
            apiCaches,
            `nenhum cache pode ter "api" no nome (isolamento multi-tenant). Got: ${JSON.stringify(keys)}`,
        ).toEqual([]);
    });
});
