// @covers AC-002
// @covers AC-010
//
// Slice 016 — E15-S02
// AC-002: npm run build produz dist/ com index.html e assets/ (>=1 .js, >=1 .css) e exit 0.
// AC-010: build falha com exit !=0 quando há erro de tipo em src/main.tsx.

'use strict';

const { describe, test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const DIST_DIR = path.join(REPO_ROOT, 'dist');
const SRC_DIR = path.join(REPO_ROOT, 'src');
const NPM_CMD = os.platform() === 'win32' ? 'npm.cmd' : 'npm';

function runNpm(args, opts = {}) {
    // shell: true no Windows (Node 24+ CVE-2024-27980 endureceu spawn de .cmd).
    return spawnSync(NPM_CMD, args, {
        cwd: REPO_ROOT,
        shell: true,
        encoding: 'utf8',
        env: { ...process.env, CI: '1' },
        timeout: 180_000,
        ...opts,
    });
}

function rmrf(p) {
    if (fs.existsSync(p)) {
        fs.rmSync(p, { recursive: true, force: true });
    }
}

describe('AC-002: npm run build produces web artifacts with exit 0', () => {
    before(() => {
        rmrf(DIST_DIR);
    });

    test('AC-002: npm run build exits with code 0', () => {
        const res = runNpm(['run', 'build']);
        assert.equal(
            res.status,
            0,
            `npm run build must exit 0 — got status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
    });

    test('AC-002: dist/index.html exists after build', () => {
        assert.ok(
            fs.existsSync(path.join(DIST_DIR, 'index.html')),
            `dist/index.html must exist after npm run build (got: ${DIST_DIR})`,
        );
    });

    test('AC-002: dist/assets/ contains at least one .js file', () => {
        const assetsDir = path.join(DIST_DIR, 'assets');
        assert.ok(fs.existsSync(assetsDir), `dist/assets/ must exist`);
        const entries = fs.readdirSync(assetsDir);
        const jsFiles = entries.filter((f) => f.endsWith('.js'));
        assert.ok(
            jsFiles.length >= 1,
            `dist/assets/ must contain >=1 .js file, got: ${JSON.stringify(entries)}`,
        );
    });

    test('AC-002: dist/assets/ contains at least one .css file', () => {
        const assetsDir = path.join(DIST_DIR, 'assets');
        assert.ok(fs.existsSync(assetsDir), `dist/assets/ must exist`);
        const entries = fs.readdirSync(assetsDir);
        const cssFiles = entries.filter((f) => f.endsWith('.css'));
        assert.ok(
            cssFiles.length >= 1,
            `dist/assets/ must contain >=1 .css file, got: ${JSON.stringify(entries)}`,
        );
    });
});

describe('AC-010: npm run build fails with non-zero exit when there is a type error', () => {
    const TYPE_CHECK_FILE = path.join(SRC_DIR, '__type_check__.tsx');
    const CONTENT_WITH_TYPE_ERROR =
        '// Intentional type error for AC-010 red test. Removed by test afterwards.\n' +
        'export const x: number = "string-not-number";\n';

    let hadSrcBefore = false;

    before(() => {
        hadSrcBefore = fs.existsSync(SRC_DIR);
        if (!hadSrcBefore) {
            // src/ does not exist yet (red state). Create a temporary skeleton so the test
            // can at least place the file — implementer will keep src/ after this.
            fs.mkdirSync(SRC_DIR, { recursive: true });
        }
        fs.writeFileSync(TYPE_CHECK_FILE, CONTENT_WITH_TYPE_ERROR, 'utf8');
    });

    after(() => {
        if (fs.existsSync(TYPE_CHECK_FILE)) {
            fs.unlinkSync(TYPE_CHECK_FILE);
        }
        if (!hadSrcBefore && fs.existsSync(SRC_DIR)) {
            // Only remove src/ if we created it here AND it is empty.
            const leftover = fs.readdirSync(SRC_DIR);
            if (leftover.length === 0) {
                fs.rmdirSync(SRC_DIR);
            }
        }
    });

    test('AC-010: npm run build exits with non-zero when src/__type_check__.tsx has a type error', () => {
        const res = runNpm(['run', 'build']);
        assert.notEqual(
            res.status,
            0,
            `npm run build must FAIL (non-zero exit) when there is a TS type error.\n` +
                `Got status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
        );
        // And the output should mention the TS diagnostic somehow.
        const combined = `${res.stdout || ''}\n${res.stderr || ''}`;
        assert.match(
            combined,
            /(TS\d{3,4}|Type\s+.*is not assignable|type error|tsc\s)/i,
            `npm run build stderr/stdout should mention a TypeScript diagnostic.\nGot:\n${combined}`,
        );
    });
});
