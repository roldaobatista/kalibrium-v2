/**
 * Script de aceite visual — história: FLX-001 (Nova ordem de serviço)
 * Roda com: node scripts/aceite-flx-001.mjs
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/2026-05-07-flx-001-nova-ordem-de-servico');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://127.0.0.1:5173';
const WEB_URL    = 'http://127.0.0.1:8000';

const MOBILE_VIEWPORT  = { width: 390, height: 844 };
const DESKTOP_VIEWPORT = { width: 1440, height: 900 };

const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'password';

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

        await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await sleep(1500);

        await fecharAlertSeAberto(mobile);
        await sleep(400);

        await mobile.fill('#kb-email', TECNICO_EMAIL);
        await mobile.fill('#kb-senha', TECNICO_SENHA);
        await mobile.click('.kb-btn-entrar');
        await sleep(6000);

        await fecharAlertSeAberto(mobile);
        await sleep(600);

        // --- 03: Tela inicial (home) ------------------------------------------
        await shot(mobile, '03-tela-pos-login.png', 'Tela após login (para debug)');
        await mobile.waitForSelector('.kb-home-page', { timeout: 20000 });
        await shot(mobile, '03-tela-inicial.png', 'Tela inicial do técnico após login aprovado');

        // Navega para Ordens de Serviço
        await mobile.goto(`${MOBILE_URL}/service-orders`, { waitUntil: 'networkidle' });
        await sleep(3000);
        await shot(mobile, '04-debug-apos-navegar.png', 'Debug após navegar para service-orders');

        // --- 04: Lista de OS vazia --------------------------------------------
        await mobile.waitForSelector('.kb-os-page', { timeout: 20000 });
        await shot(mobile, '04-lista-os-vazia.png', 'Lista de ordens de serviço vazia');

        // =======================================================================
        // BLOCO 4 — Criar OS modo bancada
        // =======================================================================
        console.log('\n=== APP MOBILE — CRIAR OS MODO BANCADA ===');

        await mobile.click('.kb-os-fab');
        await sleep(800);

        await mobile.fill('.kb-modal-input[placeholder*="Cliente"]', 'Acme Indústria Ltda');
        await mobile.fill('.kb-modal-input[placeholder*="Instrumento"]', 'Paquímetro Mitutoyo 200mm');
        // modo já é bancada por padrão
        await shot(mobile, '05-nova-os-bancada.png', 'Formulário nova OS — modo Bancada selecionado');

        await mobile.click('.kb-modal-btn-salvar');
        await sleep(1500);

        // --- 06: Lista com OS bancada -----------------------------------------
        await shot(mobile, '06-lista-os-bancada.png', 'Lista com OS recém-criada (modo Bancada)');

        // =======================================================================
        // BLOCO 5 — Criar OS modo campo-veículo com equipe
        // =======================================================================
        console.log('\n=== APP MOBILE — CRIAR OS CAMPO-VEÍCULO COM EQUIPE ===');

        await mobile.click('.kb-os-fab');
        await sleep(800);

        await mobile.fill('.kb-modal-input[placeholder*="Cliente"]', 'Prefeitura de São Paulo');
        await mobile.fill('.kb-modal-input[placeholder*="Instrumento"]', 'Trena laser 50m');

        // Seleciona modo campo-veículo
        const selectModo = mobile.locator('.kb-modal-select').nth(1);
        await selectModo.selectOption('field_vehicle');
        await sleep(400);

        // Espera carregar equipe
        await sleep(1000);

        // Seleciona membros da equipe (checkboxes)
        const checkboxes = await mobile.locator('.kb-os-equipe-item input[type="checkbox"]').all();
        if (checkboxes.length >= 2) {
            await checkboxes[0].check();
            await checkboxes[1].check();
        }
        await sleep(400);

        await shot(mobile, '07-nova-os-campo-equipe.png', 'Formulário nova OS — modo Campo-veículo com 2 membros na equipe');

        await mobile.click('.kb-modal-btn-salvar');
        await sleep(1500);

        // --- 08: Lista com duas OS mostrando modos ----------------------------
        await shot(mobile, '08-lista-duas-os.png', 'Lista com duas OS: Bancada e Campo-veículo');

        // =======================================================================
        // BLOCO 6 — Editar OS e mudar modo/equipe
        // =======================================================================
        console.log('\n=== APP MOBILE — EDITAR OS MUDANDO MODO E EQUIPE ===');

        // Clica na segunda OS (Prefeitura)
        const items = await mobile.locator('.kb-os-item-btn').all();
        if (items.length >= 2) {
            await items[1].click();
        } else if (items.length === 1) {
            await items[0].click();
        }
        await sleep(800);

        // Muda modo para campo-UMC
        const selectModoEdit = mobile.locator('.kb-modal-select').nth(1);
        await selectModoEdit.selectOption('field_umc');
        await sleep(400);

        // Adiciona mais um membro (se houver)
        const checkboxesEdit = await mobile.locator('.kb-os-equipe-item input[type="checkbox"]').all();
        for (const cb of checkboxesEdit) {
            const isChecked = await cb.isChecked();
            if (!isChecked) {
                await cb.check();
                break;
            }
        }
        await sleep(400);

        await shot(mobile, '09-editar-os-modo-umc.png', 'Edição de OS — modo alterado para Campo-UMC com equipe aumentada');

        await mobile.click('.kb-modal-btn-salvar');
        await sleep(1500);

        // --- 10: Lista atualizada ---------------------------------------------
        await shot(mobile, '10-lista-atualizada.png', 'Lista atualizada após edição de modo e equipe');

        await mobileCtx.close();

        // =======================================================================
        // BLOCO 7 — Painel web: gerente vê OS com modo e equipe
        // =======================================================================
        console.log('\n=== PAINEL WEB — GERENTE VÊ OS ===');

        const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web = await webCtx.newPage();

        // Auto-login como gerente
        await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // Navega para OS do técnico (user_id=3)
        await web.goto(`${WEB_URL}/technicians/3/service-orders`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // --- 11: Painel do gerente — lista de OS -----------------------------
        await shot(web, '11-painel-os-lista.png', 'Painel do gerente — lista de OS com modo e informações');

        await webCtx.close();

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
