import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config for slice-016 E2E tests.
 * Covers AC-001 (dev server renders /login) and AC-006 (layout adaptive).
 *
 * webServer spawns `npm run dev` (Vite) and waits until port 5173 is ready.
 * In current red state, dev script does not start React/Ionic app yet — tests
 * are expected to fail until slice-016 implementer creates the scaffold.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: 'http://localhost:5173',
        trace: 'on-first-retry',
        headless: true,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: {
        command: 'npm run dev',
        url: 'http://localhost:5173',
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
