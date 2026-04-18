// @covers AC-003
//
// Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
// AC-003: manifest.webmanifest valido e completo em dist/ apos npm run build.
//
// Red ate que vite.config.ts tenha VitePWA plugin configurado com manifest completo.

'use strict';

const { describe, test, before } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const DIST_DIR = path.join(REPO_ROOT, 'dist');
const MANIFEST_PATH = path.join(DIST_DIR, 'manifest.webmanifest');
const NPM_CMD = os.platform() === 'win32' ? 'npm.cmd' : 'npm';

function runNpm(args, opts = {}) {
    return spawnSync(NPM_CMD, args, {
        cwd: REPO_ROOT,
        shell: true, // CVE-2024-27980 — Node 24 endureceu spawn de .cmd no Windows
        encoding: 'utf8',
        env: { ...process.env, CI: '1' },
        timeout: 180_000,
        ...opts,
    });
}

function rmrf(p) {
    if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
}

describe('AC-003: manifest.webmanifest valido e completo', () => {
    let manifest = null;

    before(() => {
        rmrf(DIST_DIR);
        const res = runNpm(['run', 'build']);
        assert.equal(
            res.status,
            0,
            `npm run build deve exit 0 para popular dist/. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );

        assert.ok(
            fs.existsSync(MANIFEST_PATH),
            `dist/manifest.webmanifest deve existir apos build (vite-plugin-pwa gera). got: ${MANIFEST_PATH}`,
        );

        const raw = fs.readFileSync(MANIFEST_PATH, 'utf8');
        try {
            manifest = JSON.parse(raw);
        } catch (err) {
            throw new Error(`manifest.webmanifest nao e JSON valido: ${err.message}\nraw:\n${raw}`);
        }
    });

    test('AC-003: manifest.name === "Kalibrium"', () => {
        assert.equal(manifest.name, 'Kalibrium', `manifest.name esperado "Kalibrium", got: ${manifest.name}`);
    });

    test('AC-003: manifest.short_name === "Kalibrium"', () => {
        assert.equal(
            manifest.short_name,
            'Kalibrium',
            `manifest.short_name esperado "Kalibrium", got: ${manifest.short_name}`,
        );
    });

    test('AC-003: manifest.start_url === "/"', () => {
        assert.equal(manifest.start_url, '/', `manifest.start_url esperado "/", got: ${manifest.start_url}`);
    });

    test('AC-003: manifest.display === "standalone"', () => {
        assert.equal(
            manifest.display,
            'standalone',
            `manifest.display esperado "standalone", got: ${manifest.display}`,
        );
    });

    test('AC-003: manifest.icons tem length >= 3', () => {
        assert.ok(Array.isArray(manifest.icons), `manifest.icons deve ser array, got: ${typeof manifest.icons}`);
        assert.ok(
            manifest.icons.length >= 3,
            `manifest.icons.length deve ser >= 3 (192, 512, 512-maskable), got: ${manifest.icons.length}`,
        );
    });

    test('AC-003: manifest.icons inclui um icone maskable 512x512', () => {
        const maskable = manifest.icons.find(
            (i) => typeof i.purpose === 'string' && /maskable/.test(i.purpose) && /512/.test(i.sizes ?? ''),
        );
        assert.ok(
            maskable,
            `manifest.icons deve conter entrada maskable 512x512. icons: ${JSON.stringify(manifest.icons)}`,
        );
    });

    test('AC-003: manifest.theme_color e background_color presentes', () => {
        assert.ok(
            typeof manifest.theme_color === 'string' && manifest.theme_color.length > 0,
            `manifest.theme_color deve ser string nao-vazia, got: ${manifest.theme_color}`,
        );
        assert.ok(
            typeof manifest.background_color === 'string' && manifest.background_color.length > 0,
            `manifest.background_color deve ser string nao-vazia, got: ${manifest.background_color}`,
        );
    });
});
