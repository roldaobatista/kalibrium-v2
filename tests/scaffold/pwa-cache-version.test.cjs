// @covers AC-007
// @covers AC-008
//
// Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
//
// AC-007: dist/sw.js NAO pode conter literais "/api" ou '/api' (nenhum handler
//          intercepta rotas /api/*). Complementa runtime tests em
//          tests/e2e/pwa-api-no-cache.spec.ts e tests/e2e/pwa-offline.spec.ts.
// AC-008: cache versionado por VITE_APP_VERSION — nome do cache contem o prefixo
//          "kalibrium-v" e cleanupOutdatedCaches esta ativo (grep em dist/sw.js
//          por "cleanupOutdatedCaches" / skipWaiting / cacheId com versao).
//
// Red ate que vite.config.ts tenha VitePWA plugin com:
//   - navigateFallbackDenylist: [/^\/api\//]
//   - nenhum runtimeCaching com urlPattern casando /api/*
//   - cleanupOutdatedCaches: true
//   - cacheId contendo VITE_APP_VERSION

'use strict';

const { describe, test, before } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const DIST_DIR = path.join(REPO_ROOT, 'dist');
const SW_PATH = path.join(DIST_DIR, 'sw.js');
const NPM_CMD = os.platform() === 'win32' ? 'npm.cmd' : 'npm';

function runNpm(args, opts = {}) {
    return spawnSync(NPM_CMD, args, {
        cwd: REPO_ROOT,
        shell: true, // CVE-2024-27980
        encoding: 'utf8',
        env: { ...process.env, CI: '1' },
        timeout: 180_000,
        ...opts,
    });
}

function rmrf(p) {
    if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
}

describe('AC-007: dist/sw.js nao contem rotas /api/* (grep estatico)', () => {
    let swContent = '';

    before(() => {
        if (!fs.existsSync(SW_PATH)) {
            rmrf(DIST_DIR);
            const res = runNpm(['run', 'build']);
            assert.equal(
                res.status,
                0,
                `npm run build deve exit 0. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
            );
        }
        assert.ok(
            fs.existsSync(SW_PATH),
            `dist/sw.js deve existir apos build (vite-plugin-pwa gera). got: ${SW_PATH}`,
        );
        swContent = fs.readFileSync(SW_PATH, 'utf8');
    });

    test('AC-007: dist/sw.js nao contem literal "/api"', () => {
        // Busca "/api" dentro de strings duplas.
        const matches = swContent.match(/"\/api(?:\/|"|\b)/g) ?? [];
        assert.equal(
            matches.length,
            0,
            `dist/sw.js nao pode conter literal "/api" (multi-tenant leak). Matches: ${JSON.stringify(matches)}`,
        );
    });

    test("AC-007: dist/sw.js nao contem literal '/api'", () => {
        // Busca /api dentro de strings simples.
        const matches = swContent.match(/'\/api(?:\/|'|\b)/g) ?? [];
        assert.equal(
            matches.length,
            0,
            `dist/sw.js nao pode conter literal '/api' (multi-tenant leak). Matches: ${JSON.stringify(matches)}`,
        );
    });

    test('AC-007: dist/sw.js tem regex de denylist para /^\\/api\\//', () => {
        // VitePWA com navigateFallbackDenylist: [/^\/api\//] serializa no SW.
        // Aceitamos forma /^\\/api\\// OU /^\/api\// no bundle.
        const hasDenylist = /\^\\?\/api\\?\//.test(swContent);
        assert.ok(
            hasDenylist,
            `dist/sw.js deve conter regex de denylist para /api (/^\\/api\\// ou equivalente). ` +
                `Verifique navigateFallbackDenylist em vite.config.ts.`,
        );
    });
});

describe('AC-008: cache versionado + cleanupOutdatedCaches ativo', () => {
    let swContent = '';

    before(() => {
        if (!fs.existsSync(SW_PATH)) {
            rmrf(DIST_DIR);
            const res = runNpm(['run', 'build']);
            assert.equal(res.status, 0, `npm run build deve exit 0`);
        }
        swContent = fs.readFileSync(SW_PATH, 'utf8');
    });

    test('AC-008: dist/sw.js contem cacheId com prefixo "kalibrium-v"', () => {
        // cacheId: 'kalibrium-v' + VITE_APP_VERSION vira string literal no bundle.
        // Aceita aspas duplas ou simples.
        const hasCacheId = /["']kalibrium-v[\d.]+["']/.test(swContent);
        assert.ok(
            hasCacheId,
            `dist/sw.js deve conter cacheId com prefixo "kalibrium-v<version>" (ex: "kalibrium-v0.1.0"). ` +
                `Verifique cacheId em VitePWA config usando VITE_APP_VERSION.`,
        );
    });

    test('AC-008: dist/sw.js contem cleanupOutdatedCaches ativo (ou chamada equivalente)', () => {
        // Workbox com cleanupOutdatedCaches: true emite chamada a
        // cleanupOutdatedCaches() no SW bundle.
        const hasCleanup = /cleanupOutdatedCaches\s*\(/.test(swContent);
        assert.ok(
            hasCleanup,
            `dist/sw.js deve chamar cleanupOutdatedCaches() para apagar caches de versoes antigas. ` +
                `Verifique cleanupOutdatedCaches: true em VitePWA config.`,
        );
    });

    test('AC-008: VITE_APP_VERSION esta disponivel em build (package.json version fallback)', () => {
        const pkg = JSON.parse(fs.readFileSync(path.join(REPO_ROOT, 'package.json'), 'utf8'));
        assert.ok(
            typeof pkg.version === 'string' && pkg.version.length > 0,
            `package.json.version deve ser string nao-vazia (fallback para VITE_APP_VERSION), got: ${pkg.version}`,
        );

        // A versao do package.json deve aparecer literalmente no sw.js (via cacheId).
        const versionInSw = swContent.includes(pkg.version);
        assert.ok(
            versionInSw,
            `dist/sw.js deve conter a versao "${pkg.version}" do package.json ` +
                `(via cacheId: 'kalibrium-v' + VITE_APP_VERSION).`,
        );
    });
});
