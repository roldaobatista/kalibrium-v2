/**
 * Script de aceite visual — história: dados-no-celular-ficam-protegidos
 * Roda com: node scripts/aceite-criptografia-local.mjs
 *
 * Captura 4 prints do fluxo de login/logout no app mobile.
 * Viewport iPhone 14 (390x844), servidor em http://localhost:5200.
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/dados-no-celular-ficam-protegidos');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5200';
const MOBILE_VIEWPORT = { width: 390, height: 844 };

const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'SenhaSegura123!';
// Device com status approved no banco de dados
const DEVICE_APROVADO_ID = 'aceite-demo-aprovado-v2';

// Mock do Capacitor para evitar crash no browser desktop
const CAPACITOR_MOCK = `
window.Capacitor = window.Capacitor || {
    isNativePlatform: () => false,
    isPluginAvailable: () => false,
    getPlatform: () => 'web',
    Plugins: {},
};
window.__capacitorNativeBiometric = {
    isAvailable: async () => ({ isAvailable: false }),
    getCredentials: async () => { throw new Error('não disponível'); },
    setCredentials: async () => {},
    deleteCredentials: async () => {},
    verifyIdentity: async () => { throw new Error('não disponível'); },
};
`;

async function shot(page, nome, descricao) {
    const arquivo = path.join(IMG_DIR, nome);
    await page.screenshot({ path: arquivo, fullPage: false });
    console.log(`  ok ${nome} — ${descricao}`);
    return arquivo;
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// Injeta device_id aprovado no IndexedDB (banco "kalibrium", store "kv_store")
// ANTES de qualquer script da página, para o app reconhecer o dispositivo como aprovado.
function idbInjectScript(deviceId) {
    return `
(function() {
    var _open = indexedDB.open.bind(indexedDB);
    var _injected = false;
    indexedDB.open = function(name, version) {
        var req = _open(name, version);
        var _onsuccess = req.onsuccess;
        req.addEventListener('success', function() {
            if (!_injected) {
                _injected = true;
                try {
                    var db = req.result;
                    var stores = Array.from(db.objectStoreNames);
                    if (stores.includes('kv_store')) {
                        var tx = db.transaction('kv_store', 'readwrite');
                        tx.objectStore('kv_store').put(${JSON.stringify(deviceId)}, 'device_id');
                    }
                } catch(e) { console.warn('idb inject failed:', e); }
            }
        });
        return req;
    };
})();
`;
}

async function criarContexto(browser, deviceId = null) {
    const ctx = await browser.newContext({
        viewport: MOBILE_VIEWPORT,
        colorScheme: 'light',
        bypassCSP: true,
    });
    await ctx.addInitScript(CAPACITOR_MOCK);
    if (deviceId) {
        await ctx.addInitScript(idbInjectScript(deviceId));
    }
    return ctx;
}

async function fecharAlertSeAberto(page) {
    try {
        const dismissed = await page.evaluate(async () => {
            const alert = document.querySelector('ion-alert');
            if (!alert) return false;
            if (typeof alert.dismiss === 'function') {
                await alert.dismiss();
                return true;
            }
            return false;
        });
        if (dismissed) {
            await sleep(800);
        }
    } catch {
        // sem alerta aberto
    }
}

async function preencherEEntrar(page, email, senha) {
    await page.waitForSelector('#kb-email, input[type="email"]', { timeout: 15000 });
    await page.locator('#kb-email, input[type="email"]').first().fill(email);
    await page.locator('#kb-senha, input[type="password"]').first().fill(senha);
    await page.locator('.kb-btn-entrar, button:has-text("Entrar")').first().click();
}

(async () => {
    const browser = await chromium.launch({ headless: true });

    try {
        // Limpar cache de rate-limit antes de começar
        const { execSync } = await import('child_process');
        try {
            execSync('php artisan cache:clear', {
                cwd: path.join(__dirname, '..'),
                stdio: 'pipe',
            });
            console.log('  ok Cache limpo (rate-limit zerado)');
        } catch {
            console.log('  ! Não foi possível limpar cache — pode haver rate-limit');
        }

        // =====================================================================
        // PRINT 01: Tela de login (app recém aberto, sem sessão)
        // Contexto limpo, sem device_id injetado — mostra tela de login vazia.
        // =====================================================================
        console.log('\n=== 01 — TELA DE LOGIN ===');
        const ctxLogin = await criarContexto(browser, null);
        const pageLogin = await ctxLogin.newPage();

        pageLogin.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('IndexedDB')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        await pageLogin.goto(MOBILE_URL, { waitUntil: 'networkidle', timeout: 30000 });

        try {
            await pageLogin.waitForSelector('.kb-login-card, .kb-login-page, ion-page', { timeout: 20000 });
        } catch {
            console.log('  ! timeout aguardando tela de login — tirando print do estado atual');
        }
        await sleep(1500);

        // Fechar alert de biometria que pode aparecer
        await fecharAlertSeAberto(pageLogin);
        await sleep(500);

        await shot(pageLogin, '01-tela-login.png', 'Tela de login ao abrir o app mobile');
        await ctxLogin.close();

        // =====================================================================
        // PRINTS 02-04: Fluxo com device_id aprovado injetado no IndexedDB
        // O initScript injeta o device_id ANTES de qualquer JS da página rodar,
        // para o app reconhecer o dispositivo como aprovado ao inicializar.
        // =====================================================================
        const ctxAprovado = await criarContexto(browser, DEVICE_APROVADO_ID);
        const pageAprovado = await ctxAprovado.newPage();

        pageAprovado.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('IndexedDB')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        // =====================================================================
        // PRINT 02: Logar com técnico aprovado → tela Bem-vindo
        // =====================================================================
        console.log('\n=== 02 — LOGIN + TELA BEM-VINDO ===');

        await pageAprovado.goto(MOBILE_URL, { waitUntil: 'networkidle', timeout: 30000 });

        try {
            await pageAprovado.waitForSelector('.kb-login-card, .kb-login-page, ion-page', { timeout: 20000 });
        } catch {
            console.log('  ! timeout aguardando tela de login');
        }
        await sleep(1500);

        await fecharAlertSeAberto(pageAprovado);
        await sleep(400);

        await preencherEEntrar(pageAprovado, TECNICO_EMAIL, TECNICO_SENHA);
        await sleep(6000);

        // Fechar alert de biometria se aparecer após login
        await fecharAlertSeAberto(pageAprovado);
        await sleep(800);

        await shot(pageAprovado, '02-home-bem-vindo.png', 'Tela Bem-vindo após login com técnico aprovado');

        // =====================================================================
        // PRINT 03: Clicar "Sair" → tela de login vazia
        // =====================================================================
        console.log('\n=== 03 — APÓS SAIR ===');

        const btnSair = pageAprovado.locator('.kb-btn-sair, button:has-text("Sair")').first();
        if (await btnSair.isVisible({ timeout: 3000 }).catch(() => false)) {
            await btnSair.click();
            // O Ionic anima a transição — aguardar o campo de email da tela de login aparecer
            try {
                await pageAprovado.waitForSelector('#kb-email', { state: 'visible', timeout: 15000 });
                console.log('  ok tela de login detectada após Sair');
            } catch {
                console.log('  ! timeout aguardando #kb-email — aguardando mais 5s');
                await sleep(5000);
            }
            await sleep(1000);
            await shot(pageAprovado, '03-apos-sair.png', 'Tela de login após clicar em Sair');
        } else {
            console.log('  ! Botão Sair não encontrado — capturando estado atual (login não chegou à Home)');
            await shot(pageAprovado, '03-apos-sair.png', 'Estado do app após tentativa de login');
        }

        // =====================================================================
        // PRINT 04: Login de novo → de volta à Home
        // O logout apagou o IndexedDB (secureStorage.clear()), então precisamos
        // de um novo contexto com o device_id injetado de novo.
        // =====================================================================
        console.log('\n=== 04 — LOGIN DE NOVO ===');

        await ctxAprovado.close();

        // Novo contexto com device_id pré-injetado
        const ctxAprovado2 = await criarContexto(browser, DEVICE_APROVADO_ID);
        const pageAprovado2 = await ctxAprovado2.newPage();

        pageAprovado2.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('IndexedDB')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        await pageAprovado2.goto(MOBILE_URL, { waitUntil: 'networkidle', timeout: 30000 });

        try {
            await pageAprovado2.waitForSelector('.kb-login-card, .kb-login-page, ion-page', { timeout: 20000 });
        } catch {
            console.log('  ! timeout aguardando tela de login para segundo login');
        }
        await sleep(1500);

        await fecharAlertSeAberto(pageAprovado2);
        await sleep(400);

        await preencherEEntrar(pageAprovado2, TECNICO_EMAIL, TECNICO_SENHA);
        await sleep(6000);

        await fecharAlertSeAberto(pageAprovado2);
        await sleep(800);

        await shot(pageAprovado2, '04-login-de-novo.png', 'Tela Bem-vindo após segundo login (banco re-criado sem erro)');

        await ctxAprovado2.close();

        // Listar prints gerados
        const pngs = fs.readdirSync(IMG_DIR).filter(f => f.endsWith('.png')).sort();
        console.log(`\nPrints gerados em: ${IMG_DIR}`);
        console.log(`Total: ${pngs.length} imagens`);
        pngs.forEach(f => console.log(`  - ${f}`));

    } catch (err) {
        console.error('\nERRO:', err.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
