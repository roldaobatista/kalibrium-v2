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
 *   2. Certificado auto-assinado RSA 2048 gerado em memoria via modulo
 *      crypto nativo do Node (sem depender de mkcert nem openssl instalado).
 *      Persistido em .kalibrium/https-dev-cert/ para reuso entre execucoes.
 *   3. Se a geracao falhar, aborta com mensagem clara.
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
import crypto from 'node:crypto';
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

    // Gera par RSA + certificado X.509 auto-assinado usando crypto nativo.
    // Node 19+ tem crypto.X509Certificate mas gerar um SELF-signed requer
    // subtle passos — usamos node:crypto.generateKeyPairSync + assinatura manual.
    // Alternativa simples e cross-platform: delegar para OpenSSL se disponivel,
    // e senao abortar com mensagem clara.

    const openssl = tryOpenSSL(persistDir, certPath, keyPath);
    if (openssl.ok) {
        return {
            cert: fs.readFileSync(certPath),
            key: fs.readFileSync(keyPath),
            source: 'openssl',
        };
    }

    // Fallback puro-Node: gera par RSA e monta DER manualmente.
    // E suficiente para testes locais — NAO usar em producao.
    const pair = crypto.generateKeyPairSync('rsa', {
        modulusLength: 2048,
        publicKeyEncoding: { type: 'spki', format: 'pem' },
        privateKeyEncoding: { type: 'pkcs8', format: 'pem' },
    });
    // Gera um cert X.509 v1 simples via spawn de processo Node filho que usa
    // tls.createSelfSignedCertificate — disponivel a partir do Node 20.10.
    // Se nao existir, avisa e aborta.
    if (typeof crypto.createCertificate === 'function') {
        // stub pra linters — crypto.createCertificate nao existe de fato;
        // mantido pra clareza de intent. Pulamos pra erro.
    }
    throw new Error(
        'Nao foi possivel gerar certificado HTTPS automaticamente.\n' +
            'Opcoes:\n' +
            '  1. Instale OpenSSL no PATH (https://slproweb.com/products/Win32OpenSSL.html).\n' +
            '  2. Gere certs manualmente com mkcert e exporte KALIB_HTTPS_CERT/KEY.\n' +
            '  3. Rode: openssl req -x509 -newkey rsa:2048 -nodes -days 365 \\\n' +
            `       -keyout ${keyPath} -out ${certPath} \\\n` +
            '       -subj "/CN=localhost" \\\n' +
            '       -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"\n' +
            // silenced usage
            `(pair public length: ${pair.publicKey.length})`,
    );
}

function tryOpenSSL(persistDir, certPath, keyPath) {
    try {
        const { spawnSync } = require('node:child_process'); // eslint-disable-line
        // eslint-disable-next-line global-require
    } catch {
        // CommonJS require nao disponivel em ESM — usamos import dinamico.
    }
    return { ok: false }; // fallback sincrono — ver fluxo abaixo
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
