#!/usr/bin/env node
/**
 * Slice 017 — E15-S03 (PWA Service Worker + manifest + instalabilidade offline)
 *
 * Servidor HTTPS local para testes de PWA.
 *
 * Por que HTTPS: Service Workers so registram em contextos seguros
 * (HTTPS ou http://localhost). Como a rede de dev pode usar IPs ou
 * hostnames externos, servimos sempre por HTTPS com cert auto-assinado.
 *
 * Estrategia de certificado (tres camadas de fallback):
 *   1. Variaveis de ambiente KALIB_HTTPS_CERT / KALIB_HTTPS_KEY
 *      -> quando o PM quer usar mkcert previamente gerado.
 *   2. Cache local em .kalibrium/https-dev-cert/ (reuso entre execucoes).
 *   3. Geracao via OpenSSL no PATH (spawn). Se nao disponivel, aborta
 *      com mensagem clara orientando instalacao ou uso de mkcert.
 *
 * Uso:
 *   node scripts/pwa/serve-https.mjs --port 4173 --dir dist
 *
 * Flags:
 *   --port <n>   porta HTTPS (default 4173)
 *   --dir <p>    diretorio estatico a servir (default dist)
 *   --host <h>   host bind (default 0.0.0.0)
 *
 * Headers de seguranca aplicados:
 *   Cache-Control: no-cache para HTML e /sw.js (evita SW velho grudado).
 *   Cross-Origin-Opener-Policy: same-origin (pre-requisito de alguns APIs PWA).
 */

import fs from 'node:fs';
import path from 'node:path';
import https from 'node:https';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const REPO_ROOT = path.resolve(__dirname, '..', '..');

// --------- argv parsing minimo (sem depender de minimist/yargs) ----------
function parseArgs(argv) {
    const out = { port: 4173, dir: 'dist', host: '0.0.0.0' };
    for (let i = 2; i < argv.length; i++) {
        const a = argv[i];
        if (a === '--port') out.port = Number(argv[++i]);
        else if (a === '--dir') out.dir = argv[++i];
        else if (a === '--host') out.host = argv[++i];
    }
    return out;
}

// --------- cert auto-assinado ou externo ----------
function loadOrGenerateCert() {
    const envCert = process.env.KALIB_HTTPS_CERT;
    const envKey = process.env.KALIB_HTTPS_KEY;
    if (envCert && envKey && fs.existsSync(envCert) && fs.existsSync(envKey)) {
        return {
            cert: fs.readFileSync(envCert),
            key: fs.readFileSync(envKey),
            source: 'env',
        };
    }

    const persistDir = path.join(REPO_ROOT, '.kalibrium', 'https-dev-cert');
    const certPath = path.join(persistDir, 'cert.pem');
    const keyPath = path.join(persistDir, 'key.pem');
    if (fs.existsSync(certPath) && fs.existsSync(keyPath)) {
        return {
            cert: fs.readFileSync(certPath),
            key: fs.readFileSync(keyPath),
            source: 'cache',
        };
    }

    // Se chegou aqui sem env vars e sem cache, main() ja tentou gerar via
    // openSSLCertAsync antes de nos chamar. Se ainda assim nao existe cert,
    // e porque OpenSSL falhou e KALIB_HTTPS_CERT/KEY nao foram setados.
    throw new Error(
        'Nao foi possivel carregar certificado HTTPS.\n' +
            'Opcoes:\n' +
            '  1. Instale OpenSSL no PATH (https://slproweb.com/products/Win32OpenSSL.html).\n' +
            '  2. Gere certs manualmente com mkcert e exporte KALIB_HTTPS_CERT/KEY.\n' +
            '  3. Rode: openssl req -x509 -newkey rsa:2048 -nodes -days 365 \\\n' +
            `       -keyout ${keyPath} -out ${certPath} \\\n` +
            '       -subj "/CN=localhost" \\\n' +
            '       -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"',
    );
}

// ESM-friendly openssl attempt
async function openSSLCertAsync(persistDir, certPath, keyPath) {
    const { spawnSync } = await import('node:child_process');
    fs.mkdirSync(persistDir, { recursive: true });
    const args = [
        'req',
        '-x509',
        '-newkey',
        'rsa:2048',
        '-nodes',
        '-days',
        '365',
        '-keyout',
        keyPath,
        '-out',
        certPath,
        '-subj',
        '/CN=localhost',
        '-addext',
        'subjectAltName=DNS:localhost,IP:127.0.0.1',
    ];
    const res = spawnSync('openssl', args, { encoding: 'utf8', shell: true });
    return res.status === 0;
}

// --------- static file server ----------
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

function isHtmlOrSw(p) {
    return /\.html$/.test(p) || /\/sw\.js$/.test(p) || /\/registerSW\.js$/.test(p);
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
        console.error(`[serve-https] diretorio nao encontrado: ${staticDir}`);
        console.error(`[serve-https] rode 'npm run build' primeiro.`);
        process.exit(2);
    }

    // Tenta OpenSSL via spawn se nao tem cert cacheado.
    const persistDir = path.join(REPO_ROOT, '.kalibrium', 'https-dev-cert');
    const certPath = path.join(persistDir, 'cert.pem');
    const keyPath = path.join(persistDir, 'key.pem');
    if (!fs.existsSync(certPath) || !fs.existsSync(keyPath)) {
        fs.mkdirSync(persistDir, { recursive: true });
        const ok = await openSSLCertAsync(persistDir, certPath, keyPath);
        if (!ok && !(process.env.KALIB_HTTPS_CERT && process.env.KALIB_HTTPS_KEY)) {
            console.error(
                '[serve-https] OpenSSL nao disponivel e KALIB_HTTPS_CERT/KEY nao setados.\n' +
                    '  Opcoes:\n' +
                    '   1. Instale OpenSSL no PATH.\n' +
                    '   2. Gere cert com mkcert e exporte KALIB_HTTPS_CERT + KALIB_HTTPS_KEY.\n' +
                    '  Ver docs/operations/pwa-local-https.md',
            );
            process.exit(3);
        }
    }

    const { cert, key, source } = loadOrGenerateCert();
    console.log(`[serve-https] usando cert de fonte: ${source}`);

    const server = https.createServer({ cert, key }, (req, res) => {
        const reqUrl = req.url || '/';
        let target = safeResolve(staticDir, reqUrl);
        if (!target) {
            res.writeHead(400).end('bad path');
            return;
        }

        // SPA fallback: se path nao existe e nao tem extensao, serve index.html
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

        const headers = {
            'Content-Type': contentType(target),
            'Cross-Origin-Opener-Policy': 'same-origin',
            'Cross-Origin-Embedder-Policy': 'credentialless',
        };
        if (isHtmlOrSw(target)) {
            headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            headers['Pragma'] = 'no-cache';
            headers['Expires'] = '0';
        } else {
            headers['Cache-Control'] = 'public, max-age=31536000, immutable';
        }

        res.writeHead(200, headers);
        fs.createReadStream(target).pipe(res);
    });

    server.listen(args.port, args.host, () => {
        console.log(`[serve-https] https://${args.host === '0.0.0.0' ? 'localhost' : args.host}:${args.port}/`);
        console.log(`[serve-https] servindo ${staticDir}`);
    });

    // Graceful shutdown
    const shutdown = (sig) => {
        console.log(`\n[serve-https] recebido ${sig}, encerrando...`);
        server.close(() => process.exit(0));
    };
    process.on('SIGINT', () => shutdown('SIGINT'));
    process.on('SIGTERM', () => shutdown('SIGTERM'));
}

main().catch((err) => {
    console.error('[serve-https] erro fatal:', err);
    process.exit(1);
});
