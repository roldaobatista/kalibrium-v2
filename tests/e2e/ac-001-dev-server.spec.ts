import { test, expect } from '@playwright/test';

/**
 * @covers AC-001 app abre no navegador com rota /login
 * @covers AC-009 npm run dev comunica porta ocupada
 *
 * Slice 016 — E15-S02 (Scaffold React + TS + Ionic + Capacitor + Vite)
 * These tests exercise the dev server end-to-end. They must be red until
 * builder:implementer creates the React/Ionic scaffold.
 */

test.describe('AC-001: dev server renders /login with Ionic component and clean console', () => {
    test('AC-001: /login renders an Ionic-rooted page with zero console errors @smoke', async ({ page, baseURL }) => {
        const consoleErrors: string[] = [];

        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });
        page.on('pageerror', (err) => {
            consoleErrors.push(`pageerror: ${err.message}`);
        });

        const response = await page.goto(`${baseURL}/login`, { waitUntil: 'networkidle' });

        expect(response, 'navigation response should exist').not.toBeNull();
        expect(response!.status(), 'GET /login must return 2xx').toBeGreaterThanOrEqual(200);
        expect(response!.status()).toBeLessThan(400);

        // Ionic root element must be present (IonApp renders <ion-app> custom element).
        const ionAppCount = await page.locator('ion-app').count();
        expect(ionAppCount, 'at least one <ion-app> must be rendered (Ionic mounted)').toBeGreaterThan(0);

        // Some Ionic page component rendered inside (IonPage → <ion-page> or an IonContent).
        const ionPageOrContent = await page.locator('ion-page, ion-content').count();
        expect(ionPageOrContent, 'login route must render an Ionic page component').toBeGreaterThan(0);

        // AC-001 explicitly requires zero errors in console during render.
        expect(consoleErrors, `console must be clean on /login, got: ${consoleErrors.join(' | ')}`).toEqual([]);
    });
});

test.describe('AC-009: npm run dev announces port occupation (edge of AC-001)', () => {
    /**
     * Pure E2E check that when the dev server is already running (webServer in
     * playwright.config.ts keeps it up), a second `vite` invocation on the same
     * port prints a "Port 5173 is in use" message or announces an alternative
     * port. This test is kept here (and not in tests/scaffold/) because it
     * depends on the Playwright-managed dev server already listening on 5173.
     *
     * Implementation strategy: spawn a second `npm run dev` in a subprocess,
     * capture stdout for a few seconds, then kill it. Assert that stdout
     * contains the expected marker text.
     */
    test('AC-009: second npm run dev on occupied port 5173 announces the conflict', async () => {
        const { spawn } = await import('node:child_process');
        const os = await import('node:os');

        const cwd = process.cwd();
        const isWin = os.platform() === 'win32';
        const cmd = isWin ? 'npm.cmd' : 'npm';

        const child = spawn(cmd, ['run', 'dev'], {
            cwd,
            // shell: true no Windows (Node 24 CVE-2024-27980 endureceu spawn de .cmd)
            shell: true,
            env: { ...process.env, CI: '1' },
        });

        let stdout = '';
        let stderr = '';
        child.stdout.on('data', (d) => {
            stdout += d.toString();
        });
        child.stderr.on('data', (d) => {
            stderr += d.toString();
        });

        // Give Vite up to ~6s to realize the port is busy and announce it.
        await new Promise((resolve) => setTimeout(resolve, 6000));

        try {
            child.kill('SIGKILL');
        } catch {
            /* already exited */
        }

        const combined = `${stdout}\n${stderr}`;
        const announcedBusy =
            /Port\s+5173\s+is\s+in\s+use/i.test(combined) ||
            /using\s+\d+\s+instead/i.test(combined) ||
            /address already in use/i.test(combined);

        expect(
            announcedBusy,
            `Vite must announce port 5173 busy or advertise fallback port. Got:\n${combined}`,
        ).toBe(true);
    });
});
