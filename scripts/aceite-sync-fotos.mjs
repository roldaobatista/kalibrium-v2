/**
 * Script de aceite visual — história: sync-de-fotos-anexadas-a-os
 * Roda com: node scripts/aceite-sync-fotos.mjs
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/2026-05-03-sync-de-fotos-anexadas-a-os');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://127.0.0.1:5173';
const WEB_URL    = 'http://127.0.0.1:8000';

const MOBILE_VIEWPORT  = { width: 390, height: 844 };
const DESKTOP_VIEWPORT = { width: 1440, height: 900 };

const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'password';
const SERVER_OS_ID  = '019df074-f850-7198-8024-ca9d03443f98';

// Cria imagem de teste 1x1 pixel PNG (base64)
const TEST_IMG_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
const TEST_IMG_PATH = path.join(__dirname, '../tmp/test-photo.png');
fs.mkdirSync(path.dirname(TEST_IMG_PATH), { recursive: true });
fs.writeFileSync(TEST_IMG_PATH, Buffer.from(TEST_IMG_B64, 'base64'));

async function shot(page, nome, descricao) {
    const arquivo = path.join(IMG_DIR, nome);
    await page.screenshot({ path: arquivo, fullPage: false });
    console.log(`  ok ${nome} — ${descricao}`);
    return arquivo;
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
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
            return;
        }
        await page.keyboard.press('Escape');
        await sleep(800);
    } catch {
        // sem alerta aberto
    }
}

(async () => {
    const browser = await chromium.launch({ headless: true });

    try {
        // =======================================================================
        // BLOCO 1 — App mobile: login com device novo (vai ficar pendente)
        // =======================================================================
        console.log('\n=== APP MOBILE — LOGIN INICIAL ===');

        const mobileCtx = await browser.newContext({ viewport: MOBILE_VIEWPORT });
        const mobile = await mobileCtx.newPage();

        await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await mobile.waitForSelector('.kb-login-card', { timeout: 20000 });
        await sleep(1000);

        await fecharAlertSeAberto(mobile);
        await sleep(400);

        // --- 01: Tela de login -------------------------------------------------
        await shot(mobile, '01-tela-login.png', 'Tela de login do app mobile');

        // Preenche credenciais
        await mobile.fill('#kb-email', TECNICO_EMAIL);
        await mobile.fill('#kb-senha', TECNICO_SENHA);
        await mobile.click('.kb-btn-entrar');
        await sleep(3000);

        // --- 02: Device pendente → alerta de aprovação -------------------------
        await shot(mobile, '02-device-pendente.png', 'IonAlert: dispositivo aguardando aprovação');
        await fecharAlertSeAberto(mobile);
        await sleep(400);

        // =======================================================================
        // BLOCO 2 — Aprova o device pendente via PHP
        // =======================================================================
        console.log('\n=== APROVAR DEVICE VIA PHP ===');
        const { execSync } = await import('child_process');
        try {
            execSync('php tmp/aprova-ultimo-device.php', { cwd: path.join(__dirname, '..'), stdio: 'pipe' });
            console.log('  ok Device aprovado');
        } catch (e) {
            console.log('  ! Falha ao aprovar device:', e.message);
        }
        await sleep(1000);

        // =======================================================================
        // BLOCO 3 — App mobile: login pós-aprovação + navegação até OS
        // =======================================================================
        console.log('\n=== APP MOBILE — LOGIN PÓS-APROVAÇÃO ===');

        // Recarrega a página no MESMO contexto para manter o device_id no IndexedDB
        await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await sleep(1500);

        await fecharAlertSeAberto(mobile);
        await sleep(400);

        // Preenche credenciais
        await mobile.fill('#kb-email', TECNICO_EMAIL);
        await mobile.fill('#kb-senha', TECNICO_SENHA);
        await mobile.click('.kb-btn-entrar');
        await sleep(4000);

        await fecharAlertSeAberto(mobile);
        await sleep(600);

        // --- 03: Tela inicial (home) ------------------------------------------
        await mobile.waitForSelector('.kb-home-page', { timeout: 15000 });
        await shot(mobile, '03-tela-inicial.png', 'Tela inicial do técnico após login aprovado');

        // Injeta OS no IndexedDB para garantir server_id
        console.log('  Injetando OS no IndexedDB...');
        await mobile.evaluate((osId) => {
            return new Promise((resolve, reject) => {
                const req = indexedDB.open('kalibrium', 4);
                req.onsuccess = () => {
                    const db = req.result;
                    const tx = db.transaction('service_orders', 'readwrite');
                    const store = tx.objectStore('service_orders');
                    const now = new Date().toISOString();
                    const putReq = store.put({
                        id: 'local-' + Date.now(),
                        server_id: osId,
                        client_name: 'Acme Indústria Ltda',
                        instrument_description: 'Paquímetro digital Mitutoyo 200mm',
                        status: 'received',
                        notes: '',
                        updated_at: now,
                        pending_sync: 0,
                        deleted: 0,
                    });
                    putReq.onsuccess = () => resolve();
                    putReq.onerror = () => reject(putReq.error);
                };
                req.onerror = () => reject(req.error);
            });
        }, SERVER_OS_ID);
        await sleep(500);

        // Navega para Ordens de Serviço
        await mobile.goto(`${MOBILE_URL}/service-orders`, { waitUntil: 'networkidle' });
        await sleep(1500);

        // --- 04: Lista de OS --------------------------------------------------
        await mobile.waitForSelector('.kb-os-page', { timeout: 15000 });
        await shot(mobile, '04-lista-os.png', 'Lista de ordens de serviço');

        // Abre a OS da Acme
        const osItem = mobile.locator('.kb-os-item-btn:has-text("Acme Indústria Ltda")').first();
        if (await osItem.isVisible({ timeout: 3000 }).catch(() => false)) {
            await osItem.click();
        } else {
            await mobile.click('.kb-os-item-btn');
        }
        await sleep(1500);

        // --- 05: Formulário da OS com seção de fotos vazia --------------------
        await mobile.waitForSelector('.kb-os-fotos', { timeout: 15000 });
        await shot(mobile, '05-os-fotos-vazia.png', 'Formulário da OS — seção Fotos do serviço vazia');

        // =======================================================================
        // BLOCO 4 — App mobile: anexar foto
        // =======================================================================
        console.log('\n=== APP MOBILE — ANEXAR FOTO ===');

        // Clica em "+ Adicionar foto" e seleciona arquivo
        const inputFile = mobile.locator('input[type="file"]');
        await inputFile.setInputFiles(TEST_IMG_PATH);
        await sleep(500);

        // --- 06: Foto anexada com indicador "⏳ Enviando" ----------------------
        await shot(mobile, '06-foto-enviando.png', 'Miniatura com indicador Enviando');

        // Aguarda upload completar (máx 15s)
        let tentativas = 0;
        while (tentativas < 30) {
            const temPendente = await mobile.locator('.kb-os-foto-pendente').isVisible().catch(() => false);
            if (!temPendente) break;
            await sleep(500);
            tentativas++;
        }
        await sleep(800);

        // --- 07: Foto salva (sem indicador) ------------------------------------
        await shot(mobile, '07-foto-salva.png', 'Miniatura após upload completo');

        // Aguarda sync engine enviar foto ao servidor (uploadOutbox flush)
        console.log('  Aguardando sync uploadOutbox (20s)...');
        await sleep(20000);

        // --- 08: Toca na miniatura → viewer fullscreen ------------------------
        await mobile.click('.kb-os-foto-thumb');
        await sleep(800);
        await shot(mobile, '08-foto-ampliada-app.png', 'Viewer fullscreen da foto no app');

        // Fecha viewer
        await mobile.click('.kb-os-foto-overlay');
        await sleep(600);

        // =======================================================================
        // BLOCO 5 — Painel web: gerente vê fotos (antes de remover no app)
        // =======================================================================
        console.log('\n=== PAINEL WEB — GERENTE VÊ FOTOS ===');

        const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web = await webCtx.newPage();

        // Auto-login como gerente
        await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // Navega para OS do técnico Carlos (user_id=3)
        await web.goto(`${WEB_URL}/technicians/3/service-orders`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // --- 09: Lista de OS do técnico com fotos -----------------------------
        await shot(web, '09-painel-os-com-fotos.png', 'Painel do gerente — OS com grade de fotos');

        // Clica na primeira miniatura → modal ampliado
        const thumb = web.locator('button[aria-label^="Ampliar foto"]').first();
        if (await thumb.isVisible({ timeout: 3000 }).catch(() => false)) {
            await thumb.click();
            await sleep(1000);
            await shot(web, '10-painel-foto-ampliada.png', 'Modal com foto ampliada no painel');
        } else {
            console.log('  ! Miniatura não encontrada — foto pode não ter sincronizado para o painel');
            await shot(web, '10-painel-foto-ampliada.png', 'Painel do gerente (sem foto visível)');
        }

        await webCtx.close();

        // --- 11: Remove a foto no app -----------------------------------------
        console.log('\n=== APP MOBILE — REMOVER FOTO ===');
        mobile.on('dialog', dialog => dialog.accept());
        await mobile.click('.kb-os-foto-remover');
        await sleep(1000);
        await shot(mobile, '11-foto-removida.png', 'Seção de fotos vazia após remoção');

        await mobileCtx.close();

        // =======================================================================
        const pngs = fs.readdirSync(IMG_DIR).filter(f => f.endsWith('.png'));
        console.log(`\nPrints finalizados em: ${IMG_DIR}`);
        console.log(`Total: ${pngs.length} imagens`);
        console.log(pngs.map(f => `  - ${f}`).join('\n'));

    } catch (err) {
        console.error('\nERRO:', err.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
