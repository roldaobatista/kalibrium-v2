import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for slice-016 + slice-017 E2E tests.
 *
 * Slice-017 (PWA) exige contexto seguro (HTTPS) para Service Worker, entao
 * roda sobre build estatico em https://localhost:4173 (projeto preview-chromium).
 *
 * Slice-016 (scaffold dev) depende do Vite dev server em http://localhost:5173
 * e nao pode rodar sobre build (AC-001/AC-009 exercitam o dev server). Portanto
 * ac-001-dev-server.spec.ts so e executado quando `KALIB_E2E_MODE=dev` e habilita
 * o projeto `dev-chromium`.
 *
 * Execucao padrao (npm run test:e2e) executa apenas preview-chromium (slice 017).
 * Execucao dev (KALIB_E2E_MODE=dev npm run test:e2e) executa apenas dev-chromium.
 */
const isDevMode = process.env.KALIB_E2E_MODE === 'dev';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: 1,
    reporter: [['list']],
    use: {
        trace: 'on-first-retry',
        headless: true,
        ignoreHTTPSErrors: true,
    },
    projects: isDevMode
        ? [
              {
                  name: 'dev-chromium',
                  testMatch: /ac-001-dev-server\.spec\.ts|ac-006-layout-adaptive\.spec\.ts/,
                  use: {
                      ...devices['Desktop Chrome'],
                      baseURL: 'http://localhost:5173',
                  },
              },
          ]
        : [
              {
                  name: 'preview-chromium',
                  testIgnore: /ac-001-dev-server\.spec\.ts|_diagnose-sw\.spec\.ts/,
                  use: {
                      ...devices['Desktop Chrome'],
                      baseURL: 'https://localhost:4173',
                      // Chromium recusa registrar Service Worker sobre cert self-signed
                      // mesmo com ignoreHTTPSErrors (requisito estrito de seguranca do
                      // SW). As flags abaixo permitem o registro apontando o localhost:4173
                      // como origem segura aceita, para rodar os testes PWA com nosso
                      // cert auto-assinado local. Em producao isso nao se aplica (cert real).
                      launchOptions: {
                          args: [
                              '--ignore-certificate-errors',
                              '--unsafely-treat-insecure-origin-as-secure=https://localhost:4173',
                              '--allow-insecure-localhost',
                              // beforeinstallprompt nao dispara em Chromium headless
                              // por padrao. Estas flags habilitam a heuristica de
                              // engagement zerada (AppBannerManager) para testes.
                              '--enable-features=WebAppManifestProcessedContent,WebAppEnableUniversalInstall',
                              '--bypass-app-banner-engagement-checks',
                          ],
                      },
                  },
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
