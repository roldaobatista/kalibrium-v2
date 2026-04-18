#!/usr/bin/env node
/**
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * Servidor HTTP puro (sem TLS) para teste AC-001-A.
 *
 * AC-001-A exige que o app NAO seja instalavel em HTTP puro
 * (beforeinstallprompt NAO dispara). Como o Chromium exige contexto seguro
 * para registrar Service Worker (requisito do spec), este servidor intencionalmente
 * expoe o app sem TLS para provar que a instalacao falha.
 *
 * Uso:
 *   node scripts/pwa/serve-http.mjs --port 5173 --dir dist
 */

import fs from 'node:fs';
import http from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const REPO_ROOT = path.resolve(__dirname, '..', '..');

function parseArgs(argv) {
    const out = { port: 5173, dir: 'dist', host: '0.0.0.0' };
    for (let i = 2; i < argv.length; i++) {
        const a = argv[i];
        if (a === '--port') out.port = Number(argv[++i]);
        else if (a === '--dir') out.dir = argv[++i];
        else if (a === '--host') out.host = argv[++i];
    }
    return out;
}

function contentType(p) {
    const ext = path.extname(p).toLowerCase();
    const map = {
        '.html': 'text/html; charset=utf-8',
        '.js': 'application/javascript; charset=utf-8',
        '.mjs': 'application/javascript; charset=utf-8',
        '.css': 'text/css; charset=utf-8',
        '.json': 'application/json; charset=utf-8',
        '.webmanifest': 'application/manifest+json',
        '.png': 'image/png',
        '.svg': 'image/svg+xml',
        '.ico': 'image/x-icon',
        '.woff': 'font/woff',
        '.woff2': 'font/woff2',
        '.map': 'application/json; charset=utf-8',
        '.txt': 'text/plain; charset=utf-8',
    };
    return map[ext] ?? 'application/octet-stream';
}

function safeResolve(base, reqPath) {
    const clean = decodeURIComponent(reqPath.split('?')[0]).replace(/^\/+/, '');
    const full = path.resolve(base, clean);
    if (!full.startsWith(base)) return null;
    return full;
}

async function main() {
    const args = parseArgs(process.argv);
    const staticDir = path.resolve(REPO_ROOT, args.dir);
    if (!fs.existsSync(staticDir)) {
        console.error(`[serve-http] diretorio nao encontrado: ${staticDir}`);
        console.error(`[serve-http] rode 'npm run build' primeiro.`);
        process.exit(2);
    }

    const server = http.createServer((req, res) => {
        const reqUrl = req.url || '/';
        let target = safeResolve(staticDir, reqUrl);
        if (!target) {
            res.writeHead(400).end('bad path');
            return;
        }

        if (!fs.existsSync(target) || fs.statSync(target).isDirectory()) {
            const maybeIndex = path.join(target, 'index.html');
            if (fs.existsSync(maybeIndex) && fs.statSync(maybeIndex).isFile()) {
                target = maybeIndex;
            } else if (!path.extname(reqUrl)) {
                target = path.join(staticDir, 'index.html');
            }
        }

        if (!fs.existsSync(target) || !fs.statSync(target).isFile()) {
            res.writeHead(404).end('not found');
            return;
        }

        const headers = { 'Content-Type': contentType(target) };
        res.writeHead(200, headers);
        fs.createReadStream(target).pipe(res);
    });

    server.listen(args.port, args.host, () => {
        console.log(`[serve-http] http://${args.host === '0.0.0.0' ? 'localhost' : args.host}:${args.port}/`);
        console.log(`[serve-http] servindo ${staticDir} (sem TLS — AC-001-A)`);
    });

    const shutdown = (sig) => {
        console.log(`\n[serve-http] recebido ${sig}, encerrando...`);
        server.close(() => process.exit(0));
    };
    process.on('SIGINT', () => shutdown('SIGINT'));
    process.on('SIGTERM', () => shutdown('SIGTERM'));
}

main().catch((err) => {
    console.error('[serve-http] erro fatal:', err);
    process.exit(1);
});
