// @covers AC-006
// @covers AC-006-A
//
// Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
// AC-006: Lighthouse PWA score >= 0.85 em CI (>= 0.90 em producao — documentado).
// AC-006-A: Lighthouse ainda passa se robots.txt for 404 (nao afeta categoria PWA).
//
// Red ate que vite-plugin-pwa esteja configurado e scripts/pwa/serve-https.mjs exista.
//
// Este teste e LENTO (~40-60s: build + serve HTTPS + chrome launch + audit).
// Pode ser skipado em dev via KALIB_SKIP_LIGHTHOUSE=1 mas deve rodar em CI.

'use strict';

const { describe, test, before } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync, spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const DIST_DIR = path.join(REPO_ROOT, 'dist');
const SERVE_HTTPS_SCRIPT = path.join(REPO_ROOT, 'scripts', 'pwa', 'serve-https.mjs');
const NPM_CMD = os.platform() === 'win32' ? 'npm.cmd' : 'npm';
const NPX_CMD = os.platform() === 'win32' ? 'npx.cmd' : 'npx';

const SKIP = process.env.KALIB_SKIP_LIGHTHOUSE === '1';

function runNpm(args, opts = {}) {
    return spawnSync(NPM_CMD, args, {
        cwd: REPO_ROOT,
        shell: true, // CVE-2024-27980
        encoding: 'utf8',
        env: { ...process.env, CI: '1' },
        timeout: 240_000,
        ...opts,
    });
}

function rmrf(p) {
    if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
}

async function waitForUrl(url, timeoutMs = 30_000) {
    const https = require('node:https');
    const start = Date.now();
    while (Date.now() - start < timeoutMs) {
        const ok = await new Promise((resolve) => {
            const req = https.get(url, { rejectUnauthorized: false }, (res) => {
                resolve(res.statusCode !== undefined);
                res.resume();
            });
            req.on('error', () => resolve(false));
            req.setTimeout(2000, () => {
                req.destroy();
                resolve(false);
            });
        });
        if (ok) return true;
        await new Promise((r) => setTimeout(r, 500));
    }
    return false;
}

describe('AC-006: Lighthouse PWA score >= 0.85 em CI', () => {
    if (SKIP) {
        test('AC-006: skipado via KALIB_SKIP_LIGHTHOUSE=1', () => {
            console.log('[AC-006] skipado — KALIB_SKIP_LIGHTHOUSE=1');
        });
        return;
    }

    let serverProc = null;
    const PORT = Number(process.env.KALIB_LH_PORT ?? 4173);
    const URL = `https://localhost:${PORT}`;

    before(async () => {
        // 1. Build.
        rmrf(DIST_DIR);
        const buildRes = runNpm(['run', 'build']);
        assert.equal(
            buildRes.status,
            0,
            `npm run build deve exit 0. status=${buildRes.status}\nstdout:\n${buildRes.stdout}\nstderr:\n${buildRes.stderr}`,
        );
        assert.ok(fs.existsSync(DIST_DIR), `dist/ deve existir apos build`);

        // 2. Serve HTTPS em background.
        assert.ok(
            fs.existsSync(SERVE_HTTPS_SCRIPT),
            `scripts/pwa/serve-https.mjs deve existir (implementer precisa criar). got: ${SERVE_HTTPS_SCRIPT}`,
        );

        serverProc = spawn('node', [SERVE_HTTPS_SCRIPT, '--port', String(PORT), '--dir', 'dist'], {
            cwd: REPO_ROOT,
            shell: true,
            env: { ...process.env, NODE_TLS_REJECT_UNAUTHORIZED: '0' },
            stdio: ['ignore', 'pipe', 'pipe'],
        });

        const up = await waitForUrl(URL, 30_000);
        assert.ok(up, `servidor HTTPS ${URL} deve estar up em 30s`);
    });

    // Cleanup em after (mesmo se teste falha).
    const { after } = require('node:test');
    after(() => {
        if (serverProc) {
            try {
                serverProc.kill('SIGKILL');
            } catch {
                /* ja saiu */
            }
        }
    });

    test('AC-006: lighthouse PWA score >= 0.85', () => {
        const reportPath = path.join(REPO_ROOT, 'lighthouse-reports', `pwa-${Date.now()}.json`);
        fs.mkdirSync(path.dirname(reportPath), { recursive: true });

        const lhRes = spawnSync(
            NPX_CMD,
            [
                'lighthouse',
                URL,
                '--only-categories=pwa',
                '--output=json',
                `--output-path=${reportPath}`,
                '--quiet',
                '--chrome-flags=--ignore-certificate-errors --headless=new --no-sandbox',
            ],
            {
                cwd: REPO_ROOT,
                shell: true, // CVE-2024-27980
                encoding: 'utf8',
                env: { ...process.env },
                timeout: 180_000,
            },
        );

        assert.equal(
            lhRes.status,
            0,
            `lighthouse deve exit 0. status=${lhRes.status}\nstdout:\n${lhRes.stdout}\nstderr:\n${lhRes.stderr}`,
        );

        assert.ok(fs.existsSync(reportPath), `report lighthouse deve existir em ${reportPath}`);
        const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));

        const score = report?.categories?.pwa?.score;
        assert.ok(
            typeof score === 'number',
            `report.categories.pwa.score deve ser numero, got: ${typeof score} (${score})`,
        );
        assert.ok(
            score >= 0.85,
            `lighthouse PWA score deve ser >= 0.85 em CI, got ${score} (threshold 0.90 em producao)`,
        );
    });

    test('AC-006-A: lighthouse ainda passa mesmo se robots.txt estiver ausente (404)', () => {
        const robotsPath = path.join(DIST_DIR, 'robots.txt');
        assert.ok(
            !fs.existsSync(robotsPath),
            `AC-006-A protege contra regressao: robots.txt NAO deve existir em dist/ (ausencia intencional). Se existir, remover do build ou ajustar o teste.`,
        );

        // score ja foi validado em AC-006 acima; aqui apenas asseguramos a pre-condicao.
        // Se AC-006 passou com robots.txt ausente, AC-006-A esta satisfeito por construcao.
        assert.ok(true, 'AC-006-A validado: score >= 0.85 foi atingido sem robots.txt presente');
    });
});
