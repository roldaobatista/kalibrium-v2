#!/usr/bin/env node
// Gera os 3 icones PWA (192, 512, 512-maskable) programaticamente via pngjs.
//
// Slice 017 — E15-S03. Helper proprio substitui pwa-asset-generator porque:
//   (a) pwa-asset-generator exige Chromium headless, o que fragiliza CI;
//   (b) nosso SVG fonte e geometricamente simples (retangulos + poligonos +
//       circulo), viavel de rasterizar manualmente sem motor SVG completo;
//   (c) AC-004-A exige pixel (256,256) alpha>=254 — garantido com padding 10%.
//
// Uso:
//   npm run generate:icons
//   node scripts/pwa/generate-icons.mjs
//
// Saida: public/icons/icon-192.png, icon-512.png, icon-512-maskable.png.
// Commitar os PNGs gerados no repo (D4 do plan).

import { PNG } from 'pngjs';
import fs from 'node:fs';
import path from 'node:path';
import url from 'node:url';

const __filename = url.fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const REPO_ROOT = path.resolve(__dirname, '..', '..');
const OUT_DIR = path.join(REPO_ROOT, 'public', 'icons');

// Paleta Kalibrium (Ionic primary + accent).
const BG_TOP = [56, 128, 255, 255];   // #3880ff
const BG_BOT = [31, 92, 212, 255];    // #1f5cd4
const FG_WHITE = [255, 255, 255, 255];
const FG_ACCENT = [255, 224, 102, 255]; // #ffe066
const TRANSPARENT = [0, 0, 0, 0];

// Desenha icone em canvas RGBA de size x size.
// maskable=true: toda area fica dentro do inner (sem transparencia nas bordas)
//   — o centro 80% contem o logo completo.
// maskable=false (standard): logo ocupa 90% com margem pequena.
function drawIcon(size, { maskable }) {
    const png = new PNG({ width: size, height: size });
    const data = png.data;

    // Gradiente vertical de fundo (ou transparente nas bordas se nao maskable).
    const pad = maskable ? 0 : Math.floor(size * 0.05); // margem 5% (area logo 90%) para standard
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const idx = (y * size + x) * 4;
            const inBounds = x >= pad && x < size - pad && y >= pad && y < size - pad;
            if (!inBounds) {
                // Standard: canto transparente para visual redondo
                data[idx] = TRANSPARENT[0];
                data[idx + 1] = TRANSPARENT[1];
                data[idx + 2] = TRANSPARENT[2];
                data[idx + 3] = TRANSPARENT[3];
                continue;
            }
            // Gradiente linear vertical.
            const t = (y - pad) / (size - 2 * pad);
            data[idx] = Math.round(BG_TOP[0] + (BG_BOT[0] - BG_TOP[0]) * t);
            data[idx + 1] = Math.round(BG_TOP[1] + (BG_BOT[1] - BG_TOP[1]) * t);
            data[idx + 2] = Math.round(BG_TOP[2] + (BG_BOT[2] - BG_TOP[2]) * t);
            data[idx + 3] = 255;
        }
    }

    // Desenha a letra K dentro de um viewBox logico 1024.
    // Escala 1024 -> size.
    const s = size / 1024;
    const scaleX = (v) => Math.round(v * s);
    const scaleY = (v) => Math.round(v * s);

    // Haste vertical: rect(300, 240, w=110, h=544)
    fillRect(data, size, scaleX(300), scaleY(240), scaleX(410), scaleY(784), FG_WHITE);

    // Diagonal superior: polygon (410,500)-(690,240)-(760,300)-(480,548)
    fillPolygon(
        data,
        size,
        [
            [scaleX(410), scaleY(500)],
            [scaleX(690), scaleY(240)],
            [scaleX(760), scaleY(300)],
            [scaleX(480), scaleY(548)],
        ],
        FG_WHITE,
    );

    // Diagonal inferior: polygon (480,476)-(760,740)-(690,800)-(410,560)
    fillPolygon(
        data,
        size,
        [
            [scaleX(480), scaleY(476)],
            [scaleX(760), scaleY(740)],
            [scaleX(690), scaleY(800)],
            [scaleX(410), scaleY(560)],
        ],
        FG_WHITE,
    );

    // Ponto amarelo: circle (740, 380, r=38)
    fillCircle(data, size, scaleX(740), scaleY(380), Math.round(38 * s), FG_ACCENT);

    return PNG.sync.write(png);
}

function fillRect(data, size, x0, y0, x1, y1, color) {
    for (let y = y0; y < y1; y++) {
        if (y < 0 || y >= size) continue;
        for (let x = x0; x < x1; x++) {
            if (x < 0 || x >= size) continue;
            const idx = (y * size + x) * 4;
            data[idx] = color[0];
            data[idx + 1] = color[1];
            data[idx + 2] = color[2];
            data[idx + 3] = color[3];
        }
    }
}

function fillCircle(data, size, cx, cy, r, color) {
    const r2 = r * r;
    for (let y = cy - r; y <= cy + r; y++) {
        if (y < 0 || y >= size) continue;
        for (let x = cx - r; x <= cx + r; x++) {
            if (x < 0 || x >= size) continue;
            const dx = x - cx;
            const dy = y - cy;
            if (dx * dx + dy * dy > r2) continue;
            const idx = (y * size + x) * 4;
            data[idx] = color[0];
            data[idx + 1] = color[1];
            data[idx + 2] = color[2];
            data[idx + 3] = color[3];
        }
    }
}

// Raster simples de poligono convexo (scanline, sem antialiasing — suficiente
// para icones marcantes em tamanhos >=192).
function fillPolygon(data, size, points, color) {
    const minY = Math.max(0, Math.min(...points.map((p) => p[1])));
    const maxY = Math.min(size - 1, Math.max(...points.map((p) => p[1])));

    for (let y = minY; y <= maxY; y++) {
        const xs = [];
        for (let i = 0; i < points.length; i++) {
            const [x1, y1] = points[i];
            const [x2, y2] = points[(i + 1) % points.length];
            if ((y1 <= y && y2 > y) || (y2 <= y && y1 > y)) {
                const xi = x1 + ((y - y1) / (y2 - y1)) * (x2 - x1);
                xs.push(Math.round(xi));
            }
        }
        xs.sort((a, b) => a - b);
        for (let k = 0; k < xs.length; k += 2) {
            const xStart = Math.max(0, xs[k]);
            const xEnd = Math.min(size - 1, xs[k + 1] ?? xs[k]);
            for (let x = xStart; x <= xEnd; x++) {
                const idx = (y * size + x) * 4;
                data[idx] = color[0];
                data[idx + 1] = color[1];
                data[idx + 2] = color[2];
                data[idx + 3] = color[3];
            }
        }
    }
}

function main() {
    fs.mkdirSync(OUT_DIR, { recursive: true });

    const buf192 = drawIcon(192, { maskable: false });
    fs.writeFileSync(path.join(OUT_DIR, 'icon-192.png'), buf192);
    console.log(`[generate-icons] wrote ${path.join(OUT_DIR, 'icon-192.png')} (192x192)`);

    const buf512 = drawIcon(512, { maskable: false });
    fs.writeFileSync(path.join(OUT_DIR, 'icon-512.png'), buf512);
    console.log(`[generate-icons] wrote ${path.join(OUT_DIR, 'icon-512.png')} (512x512)`);

    const buf512m = drawIcon(512, { maskable: true });
    fs.writeFileSync(path.join(OUT_DIR, 'icon-512-maskable.png'), buf512m);
    console.log(`[generate-icons] wrote ${path.join(OUT_DIR, 'icon-512-maskable.png')} (512x512 maskable)`);
}

main();
