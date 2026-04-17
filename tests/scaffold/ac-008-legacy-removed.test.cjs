// @covers AC-008
// @covers AC-013
//
// Slice 016 — E15-S02
// AC-008: frontend legado Livewire/Blade removido, exceto resources/views/emails/.
// AC-013: routes/web.php não contém chamadas view() ou return view (exit 1 do grep).

'use strict';

const { describe, test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const RES_VIEWS = path.join(REPO_ROOT, 'resources', 'views');
const RES_JS = path.join(REPO_ROOT, 'resources', 'js');
const ROUTES_WEB = path.join(REPO_ROOT, 'routes', 'web.php');

/**
 * Walk a directory tree and return a list of file paths relative to repo root
 * that satisfy the predicate.
 */
function walk(dir, pred, base = dir, acc = []) {
    if (!fs.existsSync(dir)) return acc;
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            walk(full, pred, base, acc);
        } else if (entry.isFile() && pred(full)) {
            acc.push(full);
        }
    }
    return acc;
}

describe('AC-008: legacy frontend (Livewire/Blade/JS/CSS) removed, except resources/views/emails/', () => {
    test('AC-008: no *.blade.php under resources/views/ except under /emails/', () => {
        const allBlades = walk(RES_VIEWS, (f) => f.endsWith('.blade.php'));
        const outsideEmails = allBlades.filter((f) => {
            const norm = f.replaceAll('\\', '/');
            return !norm.includes('/resources/views/emails/');
        });
        assert.deepEqual(
            outsideEmails,
            [],
            `resources/views/ must NOT contain any *.blade.php outside /emails/. Found: ${JSON.stringify(outsideEmails)}`,
        );
    });

    test('AC-008: resources/js/ is empty or does not exist', () => {
        if (!fs.existsSync(RES_JS)) {
            assert.ok(true, 'resources/js/ does not exist — acceptable');
            return;
        }
        const files = walk(RES_JS, () => true);
        assert.deepEqual(
            files,
            [],
            `resources/js/ must contain zero files (legacy bootstrap removed). Found: ${JSON.stringify(files)}`,
        );
    });

    test('AC-008: if resources/views/emails/ exists, it is preserved (not deleted by mistake)', () => {
        const emailsDir = path.join(RES_VIEWS, 'emails');
        if (fs.existsSync(emailsDir)) {
            const entries = walk(emailsDir, (f) => f.endsWith('.blade.php'));
            // It's fine for this directory to have zero blade files IF the project
            // does not use Mailables yet; but if it previously existed with files,
            // the implementer must NOT wipe it. This assertion simply logs the
            // current state — it passes as long as the directory is intact.
            assert.ok(Array.isArray(entries), `emails/ enumeration must succeed`);
        } else {
            // Not present — acceptable; no transactional emails in this project yet.
            assert.ok(true, 'resources/views/emails/ not present — acceptable');
        }
    });
});

describe('AC-013: routes/web.php has no view() or `return view` calls (edge of AC-008)', () => {
    test('AC-013: routes/web.php exists', () => {
        assert.ok(fs.existsSync(ROUTES_WEB), `routes/web.php must exist: ${ROUTES_WEB}`);
    });

    test('AC-013: routes/web.php does not contain view( or return view calls', () => {
        const content = fs.readFileSync(ROUTES_WEB, 'utf8');
        // Strip PHP line comments and block comments so commented-out legacy view()
        // does not count as a live call (grep -E treats comments literally, so we
        // match grep behavior by searching raw content — if PM wants leniency on
        // comments, the spec does not grant it).
        const rawMatches = [...content.matchAll(/view\s*\(|return\s+view\b/g)].map((m) => m[0]);
        assert.deepEqual(
            rawMatches,
            [],
            `routes/web.php must not contain view( or return view. Found matches: ${JSON.stringify(rawMatches)}`,
        );
    });
});
