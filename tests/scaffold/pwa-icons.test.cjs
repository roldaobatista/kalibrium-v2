// @covers AC-004
// @covers AC-004-A
//
// Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
// AC-004: dist/icons/ contem icon-192.png (192x192), icon-512.png (512x512),
//          icon-512-maskable.png (512x512) apos npm run build.
// AC-004-A: pixel central (256,256) de icon-512-maskable.png tem alpha >= 254
//           (area segura de 80% preservada).
//
// Red ate que pwa-asset-generator tenha gerado e commitado os PNGs em public/icons/
// e VitePWA copie-os para dist/icons/ no build.

'use strict';

const { describe, test, before } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const DIST_DIR = path.join(REPO_ROOT, 'dist');
const DIST_ICONS_DIR = path.join(DIST_DIR, 'icons');
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

// Parser PNG minimo para extrair width/height/bitDepth/colorType/alpha central.
// Evita dep pngjs no red teste — implementer pode substituir por pngjs se preferir.
function parsePngDimensions(filePath) {
    const buf = fs.readFileSync(filePath);
    const sig = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
    if (buf.slice(0, 8).compare(sig) !== 0) {
        throw new Error(`arquivo nao e PNG valido: ${filePath}`);
    }
    // IHDR chunk comeca em offset 8, length(4) type(4) data(13): width em offset 16.
    const width = buf.readUInt32BE(16);
    const height = buf.readUInt32BE(20);
    const bitDepth = buf.readUInt8(24);
    const colorType = buf.readUInt8(25);
    return { width, height, bitDepth, colorType };
}

describe('AC-004: 3 icones obrigatorios existem com dimensoes corretas', () => {
    before(() => {
        // Se dist/ nao tem os icones, roda build pra popular.
        if (!fs.existsSync(DIST_ICONS_DIR)) {
            rmrf(DIST_DIR);
            const res = runNpm(['run', 'build']);
            assert.equal(
                res.status,
                0,
                `npm run build deve exit 0. status=${res.status}\nstdout:\n${res.stdout}\nstderr:\n${res.stderr}`,
            );
        }
    });

    test('AC-004: dist/icons/icon-192.png existe e tem 192x192', () => {
        const p = path.join(DIST_ICONS_DIR, 'icon-192.png');
        assert.ok(fs.existsSync(p), `dist/icons/icon-192.png deve existir, got: ${p}`);
        const { width, height } = parsePngDimensions(p);
        assert.equal(width, 192, `icon-192.png width esperado 192, got ${width}`);
        assert.equal(height, 192, `icon-192.png height esperado 192, got ${height}`);
    });

    test('AC-004: dist/icons/icon-512.png existe e tem 512x512', () => {
        const p = path.join(DIST_ICONS_DIR, 'icon-512.png');
        assert.ok(fs.existsSync(p), `dist/icons/icon-512.png deve existir, got: ${p}`);
        const { width, height } = parsePngDimensions(p);
        assert.equal(width, 512, `icon-512.png width esperado 512, got ${width}`);
        assert.equal(height, 512, `icon-512.png height esperado 512, got ${height}`);
    });

    test('AC-004: dist/icons/icon-512-maskable.png existe e tem 512x512', () => {
        const p = path.join(DIST_ICONS_DIR, 'icon-512-maskable.png');
        assert.ok(
            fs.existsSync(p),
            `dist/icons/icon-512-maskable.png deve existir (area segura 80%), got: ${p}`,
        );
        const { width, height } = parsePngDimensions(p);
        assert.equal(width, 512, `icon-512-maskable.png width esperado 512, got ${width}`);
        assert.equal(height, 512, `icon-512-maskable.png height esperado 512, got ${height}`);
    });
});

describe('AC-004-A: icon-512-maskable.png tem area segura correta (pixel central opaco)', () => {
    test('AC-004-A: pixel central (256,256) tem alpha >= 254', () => {
        const p = path.join(DIST_ICONS_DIR, 'icon-512-maskable.png');
        assert.ok(
            fs.existsSync(p),
            `pre-condicao: dist/icons/icon-512-maskable.png deve existir para testar pixel central`,
        );

        // Usa pngjs (dev dep adicionada pelo implementer) para ler o pixel RGBA.
        // Se pngjs nao existe ainda, teste falha com mensagem clara (RED esperado).
        let PNG;
        try {
            ({ PNG } = require('pngjs'));
        } catch (err) {
            throw new Error(
                `pngjs nao esta instalado (dev dep). Implementer deve adicionar "pngjs": "^7.0.0" em devDependencies.\n` +
                    `Erro: ${err.message}`,
            );
        }

        const png = PNG.sync.read(fs.readFileSync(p));
        assert.equal(png.width, 512, `maskable png width deve ser 512 para ler pixel (256,256)`);
        assert.equal(png.height, 512, `maskable png height deve ser 512 para ler pixel (256,256)`);

        // PNG.sync.read retorna buffer RGBA (4 bytes por pixel).
        const x = 256;
        const y = 256;
        const idx = (png.width * y + x) * 4;
        const alpha = png.data[idx + 3];

        assert.ok(
            alpha >= 254,
            `pixel central (256,256) deve ter alpha >= 254 (area segura 80%), got alpha=${alpha}`,
        );
    });
});
