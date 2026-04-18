import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for slice-016 + slice-017 E2E tests.
 *
 * Slice-017 (PWA) exige contexto seguro (HTTPS) para Service Worker. Portanto
 * o webServer executa `npm run build && npm run serve:https` (porta 4173, HTTPS
 * com cert auto-assinado). Testes PWA apontam para https://localhost:4173 e
 * Playwright aceita cert self-signed via `ignoreHTTPSErrors: true`.
 *
 * Override para slice-016 puro (dev server): definir KALIB_E2E_MODE=dev para
 * voltar ao Vite dev HTTP:5173 — mantido para compatibilidade.
 */
const isDevMode = process.env.KALIB_E2E_MODE === 'dev';

const baseURL = isDevMode ? 'http://localhost:5173' : 'https://localhost:4173';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL,
        trace: 'on-first-retry',
        headless: true,
        ignoreHTTPSErrors: true,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: isDevMode
        ? {
              command: 'npm run dev',
              url: 'http://localhost:5173',
              reuseExistingServer: !process.env.CI,
              timeout: 60_000,
              stdout: 'pipe',
              stderr: 'pipe',
          }
        : {
              command: 'npm run build && npm run serve:https',
              url: 'https://localhost:4173',
              reuseExistingServer: !process.env.CI,
              timeout: 240_000,
              ignoreHTTPSErrors: true,
              stdout: 'pipe',
              stderr: 'pipe',
          },
});
