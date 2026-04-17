// @covers AC-005
// @covers AC-011
//
// Slice 016 — E15-S02
// AC-005: src/{pages,components,hooks,db,auth,wipe,observability}/ existem.
// AC-011: scaffold não cria diretórios extras fora do escopo declarado
//         (proibidos: src/legacy, src/old, src/todo).

'use strict';

const { describe, test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SRC_DIR = path.join(REPO_ROOT, 'src');

const REQUIRED_DIRS = ['pages', 'components', 'hooks', 'db', 'auth', 'wipe', 'observability'];
const FORBIDDEN_DIRS = ['legacy', 'old', 'todo'];

describe('AC-005: src/ contains the 7 required directories declared in the spec', () => {
    test('AC-005: src/ exists as a directory', () => {
        assert.ok(fs.existsSync(SRC_DIR), `src/ must exist at repo root: ${SRC_DIR}`);
        assert.ok(fs.statSync(SRC_DIR).isDirectory(), `src/ must be a directory`);
    });

    for (const dir of REQUIRED_DIRS) {
        test(`AC-005: src/${dir}/ exists`, () => {
            const p = path.join(SRC_DIR, dir);
            assert.ok(fs.existsSync(p), `src/${dir}/ must exist (with .gitkeep when empty)`);
            assert.ok(fs.statSync(p).isDirectory(), `src/${dir}/ must be a directory`);
        });
    }
});

describe('AC-011: src/ does not contain forbidden/legacy directories (edge of AC-005)', () => {
    for (const dir of FORBIDDEN_DIRS) {
        test(`AC-011: src/${dir}/ must NOT exist`, () => {
            const p = path.join(SRC_DIR, dir);
            assert.ok(
                !fs.existsSync(p),
                `src/${dir}/ is forbidden by AC-011 — scaffold must not create extra directories. Found at: ${p}`,
            );
        });
    }

    test('AC-011: every directory directly under src/ is in the allowed list', () => {
        assert.ok(fs.existsSync(SRC_DIR), `src/ must exist to enumerate children`);
        const entries = fs.readdirSync(SRC_DIR, { withFileTypes: true });
        const dirNames = entries.filter((e) => e.isDirectory()).map((e) => e.name);
        const extras = dirNames.filter((d) => !REQUIRED_DIRS.includes(d));
        assert.deepEqual(
            extras,
            [],
            `src/ must contain only the declared directories. Unexpected: ${JSON.stringify(extras)}. Allowed: ${JSON.stringify(REQUIRED_DIRS)}.`,
        );
    });
});
