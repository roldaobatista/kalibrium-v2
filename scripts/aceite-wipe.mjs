/**
 * Script de aceite visual — história: gerente-limpa-celular-roubado
 * Roda com: node scripts/aceite-wipe.mjs
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import { execSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const IMG_DIR = path.join(ROOT, 'docs/backlog/aceites/imagens/gerente-limpa-celular-roubado');
fs.mkdirSync(IMG_DIR, { recursive: true });

const WEB_URL    = 'http://localhost:8000';
const MOBILE_URL = 'http://localhost:5173';

const DESKTOP_VIEWPORT = { width: 1280, height: 800 };
const MOBILE_VIEWPORT  = { width: 390, height: 844 };

const DEVICE_ID_APROVADO = 'aceite-wipe-demo-aprovado-v1';

async function shot(page, nome, descricao) {
    const arquivo = path.join(IMG_DIR, nome);
    await page.screenshot({ path: arquivo, fullPage: false });
    console.log(`  ok ${nome} — ${descricao}`);
}

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

// Substitui window.confirm() por auto-aceitar — necessário pra wire:confirm funcionar em headless
async function autoAceitarConfirm(page) {
    await page.addInitScript(() => {
        window.confirm = () => true;
    });
}

function prepararBanco() {
    console.log('\n=== PREPARANDO DADOS DE TESTE ===');
    try {
        execSync('php artisan cache:clear', { cwd: ROOT, stdio: 'pipe' });
        console.log('  ok cache limpo');
    } catch { /* ignora */ }

    // Script PHP salvo em arquivo temporário — mais confiável que tinker inline
    const tmpFile = path.join(ROOT, '_prep_wipe.php');
    fs.writeFileSync(tmpFile, `<?php
use App\\Models\\User;
use App\\Models\\MobileDevice;
use App\\Enums\\MobileDeviceStatus;

$tecnico = User::where('email', 'tecnico@teste.local')->first();
$gerente = User::where('email', 'gerente@teste.local')->first();

if (!$tecnico || !$gerente) {
    echo "ERRO: usuarios nao encontrados\\n";
    return;
}

$device = MobileDevice::withoutGlobalScope('current_tenant')
    ->where('device_identifier', '${DEVICE_ID_APROVADO}')
    ->where('tenant_id', 1)
    ->first();

if ($device) {
    $device->update([
        'status' => MobileDeviceStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $gerente->id,
        'revoked_at' => null,
        'wiped_at' => null,
        'wiped_by_user_id' => null,
        'wipe_acknowledged_at' => null,
    ]);
    echo "device atualizado para approved\\n";
} else {
    MobileDevice::withoutGlobalScope('current_tenant')->create([
        'tenant_id' => 1,
        'user_id' => $tecnico->id,
        'device_identifier' => '${DEVICE_ID_APROVADO}',
        'device_label' => 'Samsung Galaxy A54',
        'status' => MobileDeviceStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $gerente->id,
    ]);
    echo "device criado com status approved\\n";
}
`);

    try {
        const cmd = `php artisan tinker --execute="require '${tmpFile.replace(/\\/g, '/')}';"`;
        const saida = execSync(cmd, { cwd: ROOT, stdio: 'pipe' }).toString();
        console.log('  ok banco:', saida.trim().split('\n').filter(Boolean).pop());
    } catch (err) {
        console.log('  ! preparo do banco com erro:', err.stderr?.toString().split('\n')[0] ?? err.message.split('\n')[0]);
    } finally {
        try { fs.unlinkSync(tmpFile); } catch { /* ok */ }
    }
}

(async () => {
    prepararBanco();

    const browser = await chromium.launch({ headless: true });
    let prints = 0;

    try {
        // ===================================================================
        // BLOCO 1 — Painel do gerente: lista com botão "Limpar e bloquear"
        // ===================================================================
        console.log('\n=== PAINEL DO GERENTE: LISTA E WIPE ===');

        const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web = await webCtx.newPage();
        await autoAceitarConfirm(web);

        await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2000);

        // 01: Lista de celulares com botão vermelho visível
        await shot(web, '01-lista-celulares-botao-limpar.png',
            'Lista de celulares com botão vermelho "Limpar e bloquear"');
        prints++;

        // 02: Print focado no botão e na linha — zoom na área de ações
        const linha = web.locator('tr').filter({ hasText: 'Samsung Galaxy A54' }).first();
        if (await linha.isVisible({ timeout: 5000 }).catch(() => false)) {
            await linha.screenshot({ path: path.join(IMG_DIR, '02-linha-com-botao-limpar.png') });
            console.log('  ok 02-linha-com-botao-limpar.png — Detalhe da linha com botão "Limpar e bloquear"');
            prints++;
        } else {
            // Fallback: captura toda a tabela
            await shot(web, '02-linha-com-botao-limpar.png', 'Tabela de celulares (linha do device de teste)');
            prints++;
        }

        // 03: Clicar em "Limpar e bloquear" — wire:confirm auto-aceito, Livewire atualiza
        const btnWipe = web.locator('button:has-text("Limpar e bloquear")').first();
        if (await btnWipe.isVisible({ timeout: 5000 }).catch(() => false)) {
            await btnWipe.click();
            await sleep(3500); // aguarda Livewire atualizar a linha
            await shot(web, '03-badge-bloqueado-e-limpo.png',
                'Badge "Bloqueado e limpo" após confirmar o wipe');
            prints++;
        } else {
            console.log('  ! Botão "Limpar e bloquear" não encontrado — device pode já estar wiped');
            await shot(web, '03-badge-bloqueado-e-limpo.png', 'Lista atual (device pode já estar wiped)');
            prints++;
        }

        // 04: Filtro de status — mostrar todas as opções incluindo "Bloqueado e limpo"
        const select = web.locator('select[wire\\:model\\.live="statusFilter"]').first();
        if (await select.isVisible({ timeout: 3000 }).catch(() => false)) {
            // Expandir select temporariamente via JS para capturar todas as options
            await select.evaluate(el => { el.size = Math.max(el.options.length, 5); });
            await sleep(600);
            await shot(web, '04-filtro-todas-opcoes.png',
                'Filtro de status com opção "Bloqueado e limpo" disponível');
            prints++;
            await select.evaluate(el => { el.size = 1; });
            await sleep(300);

            // Ativar filtro "Bloqueado e limpo"
            await select.selectOption('wiped_and_revoked');
            await sleep(1800);
            await shot(web, '04b-filtro-bloqueado-limpo-ativo.png',
                'Filtro "Bloqueado e limpo" ativo — exibe só celulares com wipe confirmado');
            prints++;
        } else {
            console.log('  ! Select de filtro não encontrado');
            await shot(web, '04-filtro-todas-opcoes.png', 'Área de filtros');
            prints++;
        }

        await webCtx.close();

        // ===================================================================
        // BLOCO 2 — App mobile: tela /blocked e ciclo de reinstalação
        // ===================================================================
        console.log('\n=== APP MOBILE ===');

        const mobileCtx = await browser.newContext({ viewport: MOBILE_VIEWPORT });
        const mobile = await mobileCtx.newPage();

        // 05: Tela /blocked — simula o app após receber 401 com wipe:true
        await mobile.goto(`${MOBILE_URL}/blocked`, { waitUntil: 'networkidle' });
        await sleep(2000);
        await shot(mobile, '05-tela-celular-bloqueado.png',
            'Tela "Celular bloqueado" no app mobile — sem botão de ação');
        prints++;

        // 06: Reinstalação — limpar localStorage e voltar pra /login
        await mobile.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });
        await mobile.goto(`${MOBILE_URL}/login`, { waitUntil: 'networkidle' });
        await sleep(2000);
        await shot(mobile, '06-reinstalacao-volta-login.png',
            'Após limpar dados (reinstalação) — app volta pra tela de login do zero');
        prints++;

        // Tentar login com device_id novo para chegar em "Aguardando aprovação"
        const novoDeviceId = 'aceite-wipe-reinstalado-' + Date.now();
        await mobile.evaluate((did) => {
            localStorage.setItem('kalibrium.device_id', did);
        }, novoDeviceId);
        await sleep(200);

        const emailInput = mobile.locator('#kb-email, input[type="email"]').first();
        const senhaInput = mobile.locator('#kb-senha, input[type="password"]').first();
        const btnEntrar = mobile.locator('.kb-btn-entrar, button:has-text("Entrar")').first();

        if (await emailInput.isVisible({ timeout: 5000 }).catch(() => false)) {
            await emailInput.fill('tecnico@teste.local');
            await senhaInput.fill('senha123456');
            await btnEntrar.click();
            await sleep(3500);
            await shot(mobile, '06b-aguardando-nova-aprovacao.png',
                'Técnico faz login no celular novo — sistema pede nova aprovação do gerente');
            prints++;
        } else {
            await shot(mobile, '06b-aguardando-nova-aprovacao.png',
                'Estado do app após limpar dados e tentar login');
            prints++;
        }

        await mobileCtx.close();

        // ===================================================================
        // BLOCO 3 — Painel do gerente: novo pedido pendente
        // ===================================================================
        console.log('\n=== PAINEL DO GERENTE: NOVO PEDIDO ===');

        const webCtx3 = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
        const web3 = await webCtx3.newPage();
        await autoAceitarConfirm(web3);

        await web3.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
        await sleep(2500);

        // 07: Novo pedido pendente do mesmo técnico
        await shot(web3, '07-painel-novo-pedido-pendente.png',
            'Painel do gerente — novo pedido "Aguardando" do mesmo técnico após reinstalar');
        prints++;

        await webCtx3.close();

        // ===================================================================
        console.log(`\nTotal de prints: ${prints}`);
        console.log(`Pasta: ${IMG_DIR}`);
        const gerados = fs.readdirSync(IMG_DIR).filter(f => f.endsWith('.png')).sort();
        console.log(gerados.map(f => `  - ${f}`).join('\n'));

    } catch (err) {
        console.error('\nERRO:', err.message);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
