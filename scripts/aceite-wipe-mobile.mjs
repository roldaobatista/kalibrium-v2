/**
 * Captura as telas mobile para o aceite do wipe remoto.
 * O app usa capacitor-native-biometric que falha no browser — resolvemos
 * injetando um mock do Capacitor antes da execução dos scripts da página.
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const IMG_DIR = path.join(ROOT, 'docs/backlog/aceites/imagens/gerente-limpa-celular-roubado');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5200';
const MOBILE_VIEWPORT = { width: 390, height: 844 };

// Mock do plugin Capacitor — evita o crash ao abrir no browser
const CAPACITOR_MOCK = `
window.Capacitor = window.Capacitor || {
    isNativePlatform: () => false,
    isPluginAvailable: () => false,
    getPlatform: () => 'web',
    Plugins: {},
};
// Mock do NativeBiometric (capacitor-native-biometric)
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
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function criarContexto(browser) {
    const ctx = await browser.newContext({
        viewport: MOBILE_VIEWPORT,
        colorScheme: 'light',
        bypassCSP: true,
    });
    // Injeta mock ANTES de qualquer script da página
    await ctx.addInitScript(CAPACITOR_MOCK);
    return ctx;
}

(async () => {
    const browser = await chromium.launch({ headless: true });

    try {
        // ===================================================================
        // CAMINHO 5: Tela /blocked
        // ===================================================================
        console.log('\n=== TELA BLOQUEADO ===');
        const ctx1 = await criarContexto(browser);
        const page1 = await ctx1.newPage();

        page1.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('auth')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        await page1.goto(`${MOBILE_URL}/blocked`, { waitUntil: 'load' });

        // Aguardar o React montar — procura qualquer elemento do Ionic
        try {
            await page1.waitForFunction(
                () => document.querySelector('ion-page') !== null || document.querySelector('.kb-blocked-content') !== null,
                { timeout: 15000 }
            );
        } catch {
            console.log('  ! timeout aguardando ion-page — tirando print do estado atual');
        }
        await sleep(2000);

        await shot(page1, '05-tela-celular-bloqueado.png', 'Tela "Celular bloqueado" no app mobile');
        await ctx1.close();

        // ===================================================================
        // CAMINHO 6: Reinstalação volta pro login
        // ===================================================================
        console.log('\n=== REINSTALACAO / LOGIN ===');
        const ctx2 = await criarContexto(browser);
        const page2 = await ctx2.newPage();

        page2.on('pageerror', err => {
            if (!err.message.includes('capacitor') && !err.message.includes('auth')) {
                console.log('  ! erro JS:', err.message.split('\n')[0]);
            }
        });

        await page2.goto(`${MOBILE_URL}/login`, { waitUntil: 'load' });

        try {
            await page2.waitForFunction(
                () => document.querySelector('ion-page') !== null || document.querySelector('.kb-login-card') !== null,
                { timeout: 15000 }
            );
        } catch {
            console.log('  ! timeout — tirando print do estado atual');
        }
        await sleep(2000);

        await shot(page2, '06-reinstalacao-volta-login.png',
            'Após limpar dados do app (reinstalação) — tela de login do zero');

        // Tentar preencher login com device_id novo para demonstrar "Aguardando aprovação"
        const novoDeviceId = 'aceite-wipe-reinstalado-' + Date.now();
        await page2.evaluate((did) => {
            localStorage.setItem('kalibrium.device_id', did);
        }, novoDeviceId);

        const emailOk = await page2.locator('#kb-email, input[type="email"]').first()
            .isVisible({ timeout: 5000 }).catch(() => false);

        if (emailOk) {
            await page2.locator('#kb-email, input[type="email"]').first().fill('tecnico@teste.local');
            await page2.locator('#kb-senha, input[type="password"]').first().fill('senha123456');
            await page2.locator('.kb-btn-entrar, button:has-text("Entrar")').first().click();
            await sleep(4000);
            await shot(page2, '06b-aguardando-nova-aprovacao.png',
                'Técnico tenta entrar no celular novo — sistema exibe "Aguardando aprovação"');
        } else {
            console.log('  ! campos de login não encontrados');
            await shot(page2, '06b-aguardando-nova-aprovacao.png',
                'Estado do app na tela de login (campos não encontrados)');
        }

        await ctx2.close();

        const gerados = fs.readdirSync(IMG_DIR).filter(f => f.endsWith('.png')).sort();
        console.log(`\nTotal de prints mobile: ${gerados.filter(f => f.startsWith('05') || f.startsWith('06')).length}`);

    } catch (err) {
        console.error('\nERRO:', err.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
