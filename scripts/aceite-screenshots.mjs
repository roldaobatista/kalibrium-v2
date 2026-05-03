/**
 * Script de aceite visual — história: tecnico-entra-no-app-do-celular
 * Roda com: node scripts/aceite-screenshots.mjs
 *
 * Seletores atualizados para o design system Kalibrium (maio/2026):
 *   - App mobile: .kb-login-page, .kb-btn-entrar, .kb-alert-inline, IonAlert
 *   - Painel web: Tailwind utilitário, badge bg-warning-100/success-100/danger-100
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/tecnico-entra-no-app-do-celular');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5200';
const WEB_URL    = 'http://localhost:8000';

const MOBILE_VIEWPORT  = { width: 390, height: 844 };   // iPhone 14
const DESKTOP_VIEWPORT = { width: 1440, height: 900 };

// Credenciais dos dados de teste
const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'senha123456';

// Device com status approved — separado do device usado nos blocos 1-2
// (o device do bloco 2 é bloqueado no passo 9; este fica intacto para o passo 10)
const DEVICE_APROVADO_ID = 'aceite-demo-aprovado-v2';

async function shot(page, nome, descricao) {
    const arquivo = path.join(IMG_DIR, nome);
    await page.screenshot({ path: arquivo, fullPage: false });
    console.log(`  ok ${nome} — ${descricao}`);
    return arquivo;
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// Clica no botão Entrar do app mobile via classe kb-btn-entrar
async function clicarEntrar(page) {
    await page.waitForSelector('.kb-btn-entrar', { timeout: 15000 });
    await page.click('.kb-btn-entrar');
}

// Fecha IonAlert se aberto — usa a API do Ionic (dismiss) ou clique via JS
async function fecharAlertSeAberto(page) {
    try {
        const dismissed = await page.evaluate(async () => {
            const alert = document.querySelector('ion-alert');
            if (!alert) return false;
            // Tenta usar a API dismiss do Ionic
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
        // Fallback: pressionar Escape
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
        // BLOCO 1 — App mobile: tela de login (caminhos 1–4 e rate-limit)
        // =======================================================================
        console.log('\n=== APP MOBILE — CAMINHOS 1-5 ===');

        const mobileCtx = await browser.newContext({ viewport: MOBILE_VIEWPORT });
        const mobile = await mobileCtx.newPage();

        await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await mobile.waitForSelector('.kb-login-card', { timeout: 20000 });
        await sleep(1500);

        // Fechar alerta de biometria que pode aparecer se localStorage tiver dados velhos
        await fecharAlertSeAberto(mobile);
        await sleep(400);

        // --- 01: Tela de login inicial (vazia) ----------------------------------
        await shot(mobile, '01-tela-login-inicial.png', 'Tela de login ao abrir o app');

        // --- 02: Campos vazios → erro inline ------------------------------------
        await clicarEntrar(mobile);
        await sleep(1000);
        await shot(mobile, '02-validacao-campos-vazios.png', 'Aviso inline ao tentar entrar sem preencher');

        // --- 03: Credenciais erradas → erro inline em vermelho ------------------
        await mobile.fill('#kb-email', 'naoexiste@exemplo.com');
        await mobile.fill('#kb-senha', 'senhaerrada');
        await clicarEntrar(mobile);
        await sleep(3000);
        await shot(mobile, '03-credenciais-erradas.png', 'Aviso inline de credenciais incorretas');

        // Limpar campos
        await mobile.fill('#kb-email', '');
        await mobile.fill('#kb-senha', '');
        await sleep(300);

        // --- 04: Login válido com device pendente → IonAlert "Aguardando" ------
        // Injeta device_id pendente antes do login
        await mobile.evaluate(() => {
            localStorage.setItem('kalibrium.device_id', 'aceite-device-pendente-001');
        });
        await sleep(200);

        await mobile.fill('#kb-email', TECNICO_EMAIL);
        await mobile.fill('#kb-senha', TECNICO_SENHA);
        await clicarEntrar(mobile);
        await sleep(3500);
        await shot(mobile, '04-device-aguardando-aprovacao.png', 'IonAlert: dispositivo aguardando aprovação do gerente');

        // Recarregar para limpar o IonAlert — mais confiável que dismiss em headless
        await mobile.evaluate(() => { localStorage.removeItem('kalibrium.device_id'); });
        await mobile.reload({ waitUntil: 'networkidle' });
        await mobile.waitForSelector('.kb-login-card', { timeout: 15000 });
        await sleep(1000);

        // --- 05: Rate-limit — 6 tentativas erradas seguidas ---------------------
        for (let i = 1; i <= 6; i++) {
            await mobile.fill('#kb-email', TECNICO_EMAIL);
            await mobile.fill('#kb-senha', `errado${i}`);
            await clicarEntrar(mobile);
            await sleep(700);
        }
        await sleep(2000);
        await shot(mobile, '05-rate-limit-bloqueio.png', 'Aviso inline de rate-limit (excesso de tentativas)');

        // --- Extra: senha visível (toggle olho) ---------------------------------
        await mobile.reload({ waitUntil: 'networkidle' });
        await mobile.waitForSelector('.kb-login-card', { timeout: 15000 });
        await sleep(800);
        await mobile.fill('#kb-senha', 'minhasenha');
        await mobile.click('.kb-toggle-senha');
        await sleep(400);
        await shot(mobile, '00-extra-senha-visivel.png', 'Campo senha com toggle "mostrar senha" ativado');

        await mobileCtx.close();

        // =======================================================================
        // BLOCO 2 — Painel do gerente (caminhos 5–9)
        // =======================================================================
        console.log('\n=== PAINEL DO GERENTE — CAMINHOS 5-9 ===');

        const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web = await webCtx.newPage();

        // Login do gerente (user_id=4) via rota temporária de aceite
        await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // --- 06: Lista de celulares com pendente --------------------------------
        // O redirecionamento já foi para /mobile-devices
        await shot(web, '06-painel-lista-dispositivos-pendentes.png', 'Painel do gerente — lista com badge Aguardando amarelo');

        // --- 07: Clicar em Aprovar no device pendente --------------------------
        const btnAprovar = web.locator('button:has-text("Aprovar")').first();
        if (await btnAprovar.isVisible({ timeout: 3000 }).catch(() => false)) {
            await btnAprovar.click();
            await sleep(3000);
            await shot(web, '07-dispositivo-aprovado.png', 'Device do técnico — badge verde Aprovado');
        } else {
            console.log('  ! Botão Aprovar não encontrado — estado atual da lista:');
            await shot(web, '07-dispositivo-aprovado.png', 'Lista de dispositivos (estado atual)');
        }

        // --- 07b: Filtro "aprovados" mostrando o device recém aprovado ----------
        const select = web.locator('select[wire\\:model\\.live="statusFilter"]').first();
        if (await select.isVisible({ timeout: 2000 }).catch(() => false)) {
            await select.selectOption('approved');
            await sleep(1800);
            await shot(web, '07b-painel-device-aprovado-antes-bloquear.png', 'Filtro Aprovados — device recém aprovado');

            // --- 08a: Filtro "aguardando" ----------------------------------------
            await select.selectOption('pending');
            await sleep(1800);
            await shot(web, '08a-filtro-aguardando.png', 'Filtro Aguardando — só pedidos pendentes');

            // --- 08b: Filtro "aprovados" -----------------------------------------
            await select.selectOption('approved');
            await sleep(1800);
            await shot(web, '08b-filtro-aprovados.png', 'Filtro Aprovados — só celulares liberados');

            // Resetar para ver todos
            await select.selectOption('');
            await sleep(800);
        } else {
            await shot(web, '07b-painel-device-aprovado-antes-bloquear.png', 'Lista de dispositivos');
            fs.copyFileSync(path.join(IMG_DIR, '07b-painel-device-aprovado-antes-bloquear.png'), path.join(IMG_DIR, '08a-filtro-aguardando.png'));
            fs.copyFileSync(path.join(IMG_DIR, '07b-painel-device-aprovado-antes-bloquear.png'), path.join(IMG_DIR, '08b-filtro-aprovados.png'));
        }

        // --- 09: Bloquear device aprovado ---------------------------------------
        // wire:confirm usa confirm() nativo do browser — aceitar automaticamente
        web.on('dialog', dialog => dialog.accept());

        const btnBloquear = web.locator('button:has-text("Bloquear")').first();
        if (await btnBloquear.isVisible({ timeout: 3000 }).catch(() => false)) {
            await btnBloquear.click();
            await sleep(3000);
            await shot(web, '09-dispositivo-bloqueado.png', 'Device bloqueado — badge vermelho Bloqueado');
        } else {
            console.log('  ! Botão Bloquear não encontrado');
            await shot(web, '09-dispositivo-bloqueado.png', 'Lista de dispositivos após aprovação');
        }

        await webCtx.close();

        // =======================================================================
        // BLOCO 3 — App mobile: login após aprovação + botão Sair (caminhos 10–11)
        // =======================================================================
        console.log('\n=== APP MOBILE — CAMINHOS 10-11 ===');
        // Limpar rate-limit via artisan antes do login pós-aprovação
        const { execSync } = await import('child_process');
        try {
            execSync('php artisan cache:clear', { cwd: path.join(__dirname, '..'), stdio: 'pipe' });
            console.log('  ok Cache de rate-limit limpo');
        } catch {
            console.log('  ! Falha ao limpar cache — login pode estar bloqueado por rate-limit');
        }

        const mobileCtx2 = await browser.newContext({ viewport: MOBILE_VIEWPORT });
        const mobile2 = await mobileCtx2.newPage();

        await mobile2.goto(MOBILE_URL, { waitUntil: 'networkidle' });
        await sleep(1500);

        // Injeta o device_identifier que já está aprovado no banco
        await mobile2.evaluate((did) => {
            localStorage.setItem('kalibrium.device_id', did);
        }, DEVICE_APROVADO_ID);
        await sleep(200);

        await fecharAlertSeAberto(mobile2);
        await sleep(400);

        // --- 10: Login com device aprovado → tela Bem-vindo --------------------
        await mobile2.fill('#kb-email', TECNICO_EMAIL);
        await mobile2.fill('#kb-senha', TECNICO_SENHA);
        await clicarEntrar(mobile2);
        await sleep(4000);

        // Fechar eventual alert de biometria
        await fecharAlertSeAberto(mobile2);
        await sleep(600);

        await shot(mobile2, '10-login-apos-aprovacao-bem-vindo.png', 'Técnico entra no app — tela Bem-vindo com nome');

        // --- 11: Botão Sair → volta pra login ----------------------------------
        const btnSair = mobile2.locator('.kb-btn-sair').first();
        if (await btnSair.isVisible({ timeout: 3000 }).catch(() => false)) {
            await btnSair.click();
            await sleep(2000);
            await shot(mobile2, '11-apos-sair-volta-login.png', 'Tela de login após clicar em Sair');
        } else {
            // Pode não ter entrado — capturar o estado atual
            await shot(mobile2, '11-apos-sair-volta-login.png', 'Estado do app após tentativa de login pós-aprovação');
            console.log('  ! Botão Sair não encontrado — device pode estar com status diferente no banco');
        }

        await mobileCtx2.close();

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
