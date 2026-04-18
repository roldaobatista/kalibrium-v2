import { test, expect } from '@playwright/test';

/**
 * @covers AC-002 rota /login carrega offline apos primeira visita (happy)
 * @covers AC-002-A segunda visita sem cache popula offline em <5s (edge)
 * @covers AC-007 SW nao intercepta /api/* — runtime fetch offline falha sem resposta cacheada
 *
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * RED ate que vite-plugin-pwa + SW cache shell estejam implementados.
 */

const BASE = process.env.KALIB_PWA_HTTPS_URL ?? 'https://localhost:4173';

test.describe('AC-002: /login carrega offline apos primeira visita', () => {
    test('AC-002: apos primeira visita online, setOffline(true) + reload serve /login em <2s sem erro de rede @smoke', async ({
        page,
        context,
    }) => {
        // 1ª visita online para popular o cache shell.
        await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });

        // Aguarda SW ativar e controlar a pagina (SW warmup).
        await page.waitForFunction(
            () => navigator.serviceWorker.controller !== null,
            undefined,
            { timeout: 10_000 },
        );

        // Captura erros de console e de rede apos offline.
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        page.on('pageerror', (err) => consoleErrors.push(`pageerror: ${err.message}`));

        // Corta a rede.
        await context.setOffline(true);

        const start = Date.now();
        const resp = await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 10_000 });
        const elapsedMs = Date.now() - start;

        expect(resp, 'resposta offline deve existir (vinda do cache do SW)').not.toBeNull();
        expect(elapsedMs, `/login deve renderizar offline em <2000ms, foi ${elapsedMs}ms`).toBeLessThan(2000);

        // Conteudo minimo da tela de login deve estar presente (IonContent ou fallback).
        const ionRoot = await page.locator('ion-app, ion-page, ion-content').count();
        expect(ionRoot, 'shell Ionic deve renderizar offline vindo do cache do SW').toBeGreaterThan(0);

        // Nenhum erro de rede no console.
        const netErrors = consoleErrors.filter((e) => /Failed to fetch|net::ERR|NetworkError/i.test(e));
        expect(netErrors, `nao pode haver erro de rede offline, got:\n${netErrors.join('\n')}`).toEqual([]);
    });
});

test.describe('AC-002-A: segunda visita offline com cache parcial (edge)', () => {
    test('AC-002-A: reload imediato com rede OFF logo apos 1o DOMContentLoaded renderiza em <5s', async ({
        page,
        context,
    }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });

        // Nao espera networkidle nem SW activated — simula "cold cache".
        await context.setOffline(true);

        const start = Date.now();
        const resp = await page.reload({ waitUntil: 'domcontentloaded', timeout: 10_000 });
        const elapsedMs = Date.now() - start;

        expect(resp, 'reload offline deve retornar resposta (do cache do SW)').not.toBeNull();
        expect(elapsedMs, `cold cache offline deve tolerar <5000ms, foi ${elapsedMs}ms`).toBeLessThan(5000);

        // Tela nao pode ser "tela branca indefinida".
        const bodyHtml = await page.locator('body').innerHTML();
        expect(bodyHtml.length, 'body nao pode estar vazio (tela branca)').toBeGreaterThan(50);
    });
});

test.describe('AC-007 runtime: SW nao serve /api/* do cache', () => {
    test('AC-007: fetch(/api/ping) offline rejeita (nao ha resposta cacheada do SW)', async ({ page, context }) => {
        await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
        await page.waitForFunction(() => navigator.serviceWorker.controller !== null, undefined, {
            timeout: 10_000,
        });

        await context.setOffline(true);

        const result = await page.evaluate(async () => {
            try {
                const r = await fetch('/api/ping', { method: 'GET', cache: 'no-store' });
                return { ok: true, status: r.status };
            } catch (err) {
                return { ok: false, error: (err as Error).message };
            }
        });

        expect(result.ok, `fetch(/api/ping) offline deve FALHAR (sem cache SW). Got: ${JSON.stringify(result)}`).toBe(
            false,
        );
    });
});
