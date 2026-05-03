/**
 * Script de aceite visual — história: tecnico-tem-tela-inicial-no-app
 * Roda com: node scripts/aceite-tecnico-home.mjs
 *
 * Captura 5 prints do shell de Home do técnico no app mobile.
 * Viewport iPhone 14 (390x844), servidor em http://localhost:5200.
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/tecnico-tem-tela-inicial-no-app');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5200';
const MOBILE_VIEWPORT = { width: 390, height: 844 };

const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'SenhaSegura123!';
const DEVICE_APROVADO_ID = 'aceite-demo-aprovado-v2';

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

function idbInjectScript(deviceId) {
    return `
(function() {
    var _open = indexedDB.open.bind(indexedDB);
    var _injected = false;
    indexedDB.open = function(name, version) {
        var req = _open(name, version);
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
        if (dismissed) await sleep(800);
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

async function aguardarHome(page) {
    try {
        await page.waitForSelector('.kb-home-page, .kb-home-header', { timeout: 15000 });
        console.log('  ok tela Home detectada');
    } catch {
        console.log('  ! timeout aguardando tela Home — continuando com estado atual');
    }
    await sleep(1500);
    await fecharAlertSeAberto(page);
    await sleep(500);
}

(async () => {
    const browser = await chromium.launch({ headless: true });

    try {
        // Limpar cache de rate-limit
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
        // PRINT 01: Tela inicial após login — layout completo do shell
        // =====================================================================
        console.log('\n=== 01 — TELA INICIAL APÓS LOGIN ===');
        const ctx1 = await criarContexto(browser, DEVICE_APROVADO_ID);
        const page1 = await ctx1.newPage();

        page1.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('IndexedDB')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        await page1.goto(MOBILE_URL, { waitUntil: 'networkidle', timeout: 30000 });

        try {
            await page1.waitForSelector('.kb-login-card, .kb-login-page, ion-page', { timeout: 20000 });
        } catch {
            console.log('  ! timeout aguardando tela de login');
        }
        await sleep(1500);

        await fecharAlertSeAberto(page1);
        await sleep(400);

        await preencherEEntrar(page1, TECNICO_EMAIL, TECNICO_SENHA);
        await sleep(5000);

        await aguardarHome(page1);

        await shot(page1, '01-tela-inicial-home.png',
            'Tela inicial após login: cabeçalho "Olá, {nome}", cards de resumo e lista vazia');

        // =====================================================================
        // PRINT 02: Drawer aberto
        // =====================================================================
        console.log('\n=== 02 — DRAWER ABERTO ===');

        const btnMenu = page1.locator('.kb-home-menu-btn, button[aria-label="Abrir menu"]').first();
        if (await btnMenu.isVisible({ timeout: 5000 }).catch(() => false)) {
            await btnMenu.click();
            await sleep(800);
            await shot(page1, '02-drawer-aberto.png',
                'Drawer lateral aberto: avatar, nome, email, item Início ativo, botão Sair');
        } else {
            console.log('  ! Botão de menu não encontrado — capturando estado atual');
            await shot(page1, '02-drawer-aberto.png', 'Estado atual (botão menu não localizado)');
        }

        // =====================================================================
        // PRINT 03: Drawer fechado pelo backdrop
        // =====================================================================
        console.log('\n=== 03 — DRAWER FECHADO PELO BACKDROP ===');

        const backdrop = page1.locator('.kb-drawer-backdrop').first();
        if (await backdrop.isVisible({ timeout: 3000 }).catch(() => false)) {
            // Clicar na parte direita da tela (fora do drawer) via coordenada absoluta.
            // O drawer ocupa ~80% da largura à esquerda; clicar em x=370 (direita) fecha.
            await page1.mouse.click(370, 400);
            await sleep(800);
            await shot(page1, '03-drawer-fechado-backdrop.png',
                'Tela voltando ao normal após clicar no fundo do drawer');
        } else {
            console.log('  ! Backdrop não encontrado — tentando fechar pelo botão X');
            const btnFechar = page1.locator('.kb-drawer-fechar, button[aria-label="Fechar menu"]').first();
            if (await btnFechar.isVisible({ timeout: 3000 }).catch(() => false)) {
                await btnFechar.click();
                await sleep(800);
            }
            await shot(page1, '03-drawer-fechado-backdrop.png',
                'Tela após fechar drawer');
        }

        // =====================================================================
        // PRINT 04: Modo offline — card mostrando "Sem sinal"
        // =====================================================================
        console.log('\n=== 04 — MODO OFFLINE ===');

        await page1.context().setOffline(true);
        await sleep(1000);

        await shot(page1, '04-modo-offline.png',
            'Card de conexão mostrando "Sem sinal" com indicador cinza');

        // Voltar online para o logout funcionar
        await page1.context().setOffline(false);
        await sleep(500);

        // =====================================================================
        // PRINT 05: Logout pelo drawer — tela de login após Sair
        // =====================================================================
        console.log('\n=== 05 — LOGOUT PELO DRAWER ===');

        // Abrir drawer novamente
        const btnMenu2 = page1.locator('.kb-home-menu-btn, button[aria-label="Abrir menu"]').first();
        if (await btnMenu2.isVisible({ timeout: 5000 }).catch(() => false)) {
            await btnMenu2.click();
            await sleep(800);

            const btnSair = page1.locator('.kb-drawer-sair, button:has-text("Sair")').first();
            if (await btnSair.isVisible({ timeout: 5000 }).catch(() => false)) {
                await btnSair.click();

                try {
                    await page1.waitForSelector('#kb-email, .kb-login-card', { state: 'visible', timeout: 15000 });
                    console.log('  ok tela de login detectada após logout');
                } catch {
                    console.log('  ! timeout aguardando tela de login — aguardando 5s');
                    await sleep(5000);
                }
                await sleep(800);
                await shot(page1, '05-logout-tela-login.png',
                    'Tela de login após clicar em Sair no drawer');
            } else {
                console.log('  ! Botão Sair não encontrado no drawer');
                await shot(page1, '05-logout-tela-login.png', 'Estado atual (Sair não localizado)');
            }
        } else {
            console.log('  ! Botão de menu não encontrado para logout');
            await shot(page1, '05-logout-tela-login.png', 'Estado atual (menu não localizado)');
        }

        await ctx1.close();

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
