// @covers AC-014
//
// Slice 016 — E15-S02
// AC-014: capacitor.config.ts não expõe servidor remoto de dev em produção.
// Valida: arquivo existe, campo `server.url` ausente OU condicionado a
// `process.env.NODE_ENV !== 'production'`.

'use strict';

const { describe, test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const CAP_CONFIG = path.join(REPO_ROOT, 'capacitor.config.ts');

describe('AC-014: capacitor.config.ts does not expose a remote dev server URL in production', () => {
    test('AC-014: capacitor.config.ts exists', () => {
        assert.ok(fs.existsSync(CAP_CONFIG), `capacitor.config.ts must exist: ${CAP_CONFIG}`);
    });

    test('AC-014: capacitor.config.ts does not contain an unconditional server.url literal', () => {
        const raw = fs.readFileSync(CAP_CONFIG, 'utf8');

        // Remove line comments (//...) and block comments (/* ... */) so commented-out
        // examples are allowed. After stripping, any `url:` inside a `server` block must
        // be surrounded by a `NODE_ENV !== 'production'` guard OR not exist at all.
        const stripped = raw
            .replace(/\/\*[\s\S]*?\*\//g, '')
            .replace(/(^|[^:])\/\/.*$/gm, '$1');

        // Find the server block. Lightweight: look for `server` followed by `{ ... }`
        // balanced at the top-level object property. Regex is approximate but enough
        // for the single `server:` key present in cap config templates.
        const serverBlockMatch = stripped.match(/server\s*:\s*\{([\s\S]*?)\}/);

        if (!serverBlockMatch) {
            // No server block at all — PASSES (no risk of leaking a remote URL).
            assert.ok(true, 'capacitor.config.ts has no `server` block → safe by construction');
            return;
        }

        const serverBody = serverBlockMatch[1];
        const hasUrlField = /\burl\s*:/.test(serverBody);

        if (!hasUrlField) {
            // server block exists but no url field — safe.
            assert.ok(true, 'capacitor.config.ts has `server` block but no `url` field → safe');
            return;
        }

        // url field exists → must be gated by NODE_ENV !== 'production'.
        const guarded =
            /process\.env\.NODE_ENV\s*!==\s*['"]production['"]/.test(serverBody) ||
            /process\.env\.NODE_ENV\s*===\s*['"]development['"]/.test(serverBody);

        assert.ok(
            guarded,
            `capacitor.config.ts has a server.url literal that is NOT gated by NODE_ENV !== 'production'.\n` +
                `server block body:\n${serverBody}`,
        );
    });

    test('AC-014: capacitor.config.ts has no hardcoded http(s) URL pointing at an external host in server block', () => {
        const raw = fs.readFileSync(CAP_CONFIG, 'utf8');
        const stripped = raw
            .replace(/\/\*[\s\S]*?\*\//g, '')
            .replace(/(^|[^:])\/\/.*$/gm, '$1');

        const serverBlockMatch = stripped.match(/server\s*:\s*\{([\s\S]*?)\}/);
        if (!serverBlockMatch) {
            assert.ok(true, 'no server block → no external URL risk');
            return;
        }
        const serverBody = serverBlockMatch[1];

        // Any raw http(s):// literal inside server block is a red flag unless it is
        // localhost / 127.0.0.1 AND gated by NODE_ENV. We flag any non-localhost host.
        const urlLiterals = [...serverBody.matchAll(/https?:\/\/([^'"\s\/]+)/g)].map((m) => m[1]);
        const externalHosts = urlLiterals.filter(
            (h) => !/^(localhost|127\.0\.0\.1|0\.0\.0\.0)(:\d+)?$/i.test(h),
        );
        assert.deepEqual(
            externalHosts,
            [],
            `capacitor.config.ts must not embed external host URLs in server block. Found: ${JSON.stringify(externalHosts)}`,
        );
    });
});
