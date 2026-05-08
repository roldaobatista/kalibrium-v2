/**
 * Script de aceite visual — história: FLX-002 (Agenda, fila e status da OS)
 * Roda com: node scripts/aceite-flx-002.mjs
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/2026-05-07-flx-002-agenda-fila-status-os');
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
        // =====================================================================
        // BLOCO 1 — App mobile: login com device novo
        // =====================================================================
        console.log('\n=== APP MOBILE — LOGIN INICIAL ===');

        const mobileCtx = await browser.newContext({ viewport: MOBILE_VIEWPORT });
        const mobile = await mobileCtx.newPage();

        await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await mobile.waitForSelector('.kb-login-card', { timeout: 20000 });
        await sleep(1000);
        await fecharAlertSeAberto(mobile);
        await sleep(400);

        await shot(mobile, '01-tela-login.png', 'Tela de login do app mobile');

        await mobile.fill('#kb-email', TECNICO_EMAIL);
        await mobile.fill('#kb-senha', TECNICO_SENHA);
        await mobile.click('.kb-btn-entrar');
        await sleep(3000);

        await shot(mobile, '02-device-pendente.png', 'IonAlert: dispositivo aguardando aprovação');
        await fecharAlertSeAberto(mobile);
        await sleep(400);

        // =====================================================================
        // BLOCO 2 — Aprova o device pendente via PHP
        // =====================================================================
        console.log('\n=== APROVAR DEVICE VIA PHP ===');
        const { execSync } = await import('child_process');
        try {
            execSync('php tmp/aprova-ultimo-device.php', { cwd: path.join(__dirname, '..'), stdio: 'pipe' });
            console.log('  ok Device aprovado');
        } catch (e) {
            console.log('  ! Falha ao aprovar device:', e.message);
        }
        await sleep(1000);

        // =====================================================================
        // BLOCO 3 — App mobile: login pós-aprovação
        // =====================================================================
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

        await mobile.waitForSelector('.kb-home-page', { timeout: 20000 });
        await shot(mobile, '03-tela-inicial.png', 'Tela inicial com card Minha fila visível');

        // =====================================================================
        // BLOCO 4 — Criar OS de campo para aparecer na fila
        // =====================================================================
        console.log('\n=== APP MOBILE — CRIAR OS DE CAMPO ===');

        await mobile.goto(`${MOBILE_URL}/service-orders`, { waitUntil: 'networkidle' });
        await sleep(3000);
        await mobile.waitForSelector('.kb-os-page', { timeout: 20000 });

        await mobile.click('.kb-os-fab');
        await sleep(800);

        await mobile.fill('.kb-modal-input[placeholder*="Cliente"]', 'Indústria Kalibrium');
        await mobile.fill('.kb-modal-input[placeholder*="Instrumento"]', 'Balança analítica 0,1mg');

        const selectModo = mobile.locator('.kb-modal-select').nth(1);
        await selectModo.selectOption('field_vehicle');
        await sleep(400);

        // Seleciona 1 membro da equipe (o próprio técnico precisa estar na equipe para ver na fila por membro)
        const checkboxes = await mobile.locator('.kb-os-equipe-item input[type="checkbox"]').all();
        if (checkboxes.length >= 1) {
            await checkboxes[0].check();
        }
        await sleep(400);

        await shot(mobile, '04-nova-os-campo.png', 'Formulário nova OS — modo Campo-veículo com equipe');

        await mobile.click('.kb-modal-btn-salvar');
        await sleep(1500);

        // =====================================================================
        // BLOCO 5 — Navegar para Minha fila
        // =====================================================================
        console.log('\n=== APP MOBILE — MINHA FILA ===');

        await mobile.goto(`${MOBILE_URL}/queue`, { waitUntil: 'networkidle' });
        await sleep(3000);

        await shot(mobile, '05-fila-todas.png', 'Tela Minha fila — lista de OSs do técnico');

        // Filtro Hoje
        const btnHoje = mobile.locator('.kb-queue-filter-btn').filter({ hasText: /Hoje/i });
        if (await btnHoje.count() > 0) {
            await btnHoje.click();
            await sleep(800);
            await shot(mobile, '06-fila-hoje.png', 'Fila filtrada por Hoje');
        }

        // Filtro Concluídas
        const btnConcluidas = mobile.locator('.kb-queue-filter-btn').filter({ hasText: /Concluídas/i });
        if (await btnConcluidas.count() > 0) {
            await btnConcluidas.click();
            await sleep(800);
            await shot(mobile, '07-fila-concluidas.png', 'Fila filtrada por Concluídas');
        }

        // Volta para Todas
        const btnTodas = mobile.locator('.kb-queue-filter-btn').filter({ hasText: /Todas/i });
        if (await btnTodas.count() > 0) {
            await btnTodas.click();
            await sleep(800);
        }

        // =====================================================================
        // BLOCO 6 — Detalhes da OS com timeline e botões de ação
        // =====================================================================
        console.log('\n=== APP MOBILE — DETALHES DA OS ===');

        // Clica na primeira OS da fila
        const cards = await mobile.locator('.kb-queue-card').all();
        if (cards.length > 0) {
            await cards[0].click();
            await sleep(2000);

            await shot(mobile, '08-detalhes-os.png', 'Tela de detalhes da OS — dados gerais e timeline');

            // Botão Iniciar deslocamento (status = received, modo campo)
            const btnIniciar = mobile.locator('.kb-so-btn-action').filter({ hasText: /Iniciar/i });
            if (await btnIniciar.count() > 0) {
                await shot(mobile, '09-botao-iniciar.png', 'Botão Iniciar deslocamento visível');
                await btnIniciar.click();
                await sleep(1500);
                await shot(mobile, '10-apos-iniciar.png', 'Após tocar Iniciar — status mudou para Despachado');
            }

            // Botão Cheguei no cliente
            const btnCheguei = mobile.locator('.kb-so-btn-action').filter({ hasText: /Cheguei/i });
            if (await btnCheguei.count() > 0) {
                await shot(mobile, '11-botao-cheguei.png', 'Botão Cheguei no cliente visível');
                await btnCheguei.click();
                await sleep(1500);
                await shot(mobile, '12-apos-cheguei.png', 'Após tocar Cheguei — status mudou para No cliente');
            }

            // Botão Sair do cliente
            const btnSair = mobile.locator('.kb-so-btn-action').filter({ hasText: /Sair/i });
            if (await btnSair.count() > 0) {
                await shot(mobile, '13-botao-sair.png', 'Botão Sair do cliente visível');
                await btnSair.click();
                await sleep(1500);
                await shot(mobile, '14-apos-sair.png', 'Após tocar Sair — status mudou para Saindo');
            }

            // Botão Concluir
            const btnConcluir = mobile.locator('.kb-so-btn-action').filter({ hasText: /Concluir/i });
            if (await btnConcluir.count() > 0) {
                await shot(mobile, '15-botao-concluir.png', 'Botão Concluir visível');
                await btnConcluir.click();
                await sleep(1500);
                await shot(mobile, '16-apos-concluir.png', 'Após tocar Concluir — status mudou para Concluído');
            }
        }

        await mobileCtx.close();

        // =====================================================================
        // BLOCO 7 — Painel web: gerente vê OS com timeline
        // =====================================================================
        console.log('\n=== PAINEL WEB — GERENTE VÊ OS ===');

        const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web = await webCtx.newPage();

        await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2000);

        await web.goto(`${WEB_URL}/technicians/3/service-orders`, { waitUntil: 'networkidle' });
        await sleep(2000);

        await shot(web, '17-painel-os-lista.png', 'Painel do gerente — lista de OS com status atualizado');

        // Abre a primeira OS para ver timeline
        const osLinks = await web.locator('a[href*="service-orders"]').all();
        if (osLinks.length > 0) {
            await osLinks[0].click();
            await sleep(2000);
            await shot(web, '18-painel-os-timeline.png', 'Painel do gerente — timeline da OS com eventos de status');
        }

        await webCtx.close();

        // =====================================================================
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
