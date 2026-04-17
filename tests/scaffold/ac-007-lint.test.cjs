// @covers AC-007
// @covers AC-012
//
// Slice 016 — E15-S02
// AC-007: npm run lint sai com exit 0 e zero erros.
// AC-012: npm run lint detecta violação em src/__lint_check__.tsx (exit !=0) e,
//         após remoção do arquivo seed, volta a sair exit 0.

'use strict';

const { describe, test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SRC_DIR = path.join(REPO_ROOT, 'src');
const LINT_SEED = path.join(SRC_DIR, '__lint_check__.tsx');
const NPM_CMD = os.platform() === 'win32' ? 'npm.cmd' : 'npm';

function runNpm(args) {
    return spawnSync(NPM_CMD, args, {
        cwd: REPO_ROOT,
        shell: false,
        encoding: 'utf8',
        env: { ...process.env, CI: '1' },
        timeout: 180_000,
    });
}

describe('AC-007: npm run lint exits 0 with zero errors on clean scaffold', () => {
    test('AC-007: package.json declares a lint script', () => {
        const pkg = JSON.parse(fs.readFileSync(path.join(REPO_ROOT, 'package.json'), 'utf8'));
        assert.ok(
            pkg.scripts && typeof pkg.scripts.lint === 'string' && pkg.scripts.lint.length > 0,
            `package.json must declare scripts.lint (got: ${JSON.stringify(pkg.scripts)})`,
        );
    });

    test('AC-007: npm run lint exits 0 with no errors', () => {
        const res = runNpm(['run', 'lint']);
        assert.equal(
            res.status,
            0,
            `npm run lint must exit 0 on clean scaffold. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
    });
});

describe('AC-012: npm run lint detects violation in src/__lint_check__.tsx and recovers after removal', () => {
    const CONTENT_WITH_VIOLATION =
        '// AC-012 seed: unused variable violation. Removed by test afterwards.\n' +
        '/* eslint-disable-next-line -- intentionally left off; the next line must violate */\n' +
        'const unused_variable_for_ac_012: number = 42;\n' +
        'export {};\n';

    let hadSrcBefore = false;

    before(() => {
        hadSrcBefore = fs.existsSync(SRC_DIR);
        if (!hadSrcBefore) {
            fs.mkdirSync(SRC_DIR, { recursive: true });
        }
        fs.writeFileSync(LINT_SEED, CONTENT_WITH_VIOLATION, 'utf8');
    });

    after(() => {
        if (fs.existsSync(LINT_SEED)) {
            fs.unlinkSync(LINT_SEED);
        }
        if (!hadSrcBefore && fs.existsSync(SRC_DIR)) {
            const leftover = fs.readdirSync(SRC_DIR);
            if (leftover.length === 0) {
                fs.rmdirSync(SRC_DIR);
            }
        }
    });

    test('AC-012: npm run lint exits non-zero while seed file with violation exists', () => {
        const res = runNpm(['run', 'lint']);
        assert.notEqual(
            res.status,
            0,
            `npm run lint must FAIL while __lint_check__.tsx has unused-vars violation.\n` +
                `status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
        const combined = `${res.stdout || ''}\n${res.stderr || ''}`;
        assert.match(
            combined,
            /(no-unused-vars|is defined but never used|is assigned a value but never used|__lint_check__)/i,
            `ESLint output should mention the unused-vars violation.\nGot:\n${combined}`,
        );
    });

    test('AC-012: after removing seed file, npm run lint exits 0 again', () => {
        if (fs.existsSync(LINT_SEED)) {
            fs.unlinkSync(LINT_SEED);
        }
        const res = runNpm(['run', 'lint']);
        assert.equal(
            res.status,
            0,
            `After removing __lint_check__.tsx, npm run lint must exit 0.\n` +
                `status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
    });
});
