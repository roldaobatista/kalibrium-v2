import type { CapacitorConfig } from '@capacitor/cli';

/**
 * Capacitor config for Kalibrium client (slice-016).
 *
 * AC-014: no `server.url` hardcoded in production. Any local dev override
 * MUST live in `capacitor.config.dev.ts` (gitignored) or be injected via
 * environment at build time — this file is the production-safe baseline.
 */
const config: CapacitorConfig = {
    appId: 'app.kalibrium.client',
    appName: 'Kalibrium',
    webDir: 'dist',
    bundledWebRuntime: false,
};

export default config;
