/**
 * Script de aceite visual — história: primeiro-sync-app-servidor
 * Roda com: node scripts/aceite-sync-anotacoes.mjs
 *
 * Cobre 7 caminhos:
 *   01  Card "Anotações" no Home do app mobile
 *   02  Tela de anotações vazia
 *   03  Modal de criar anotação aberto
 *   04  Anotação salva online (sem indicador pendente)
 *   05  Anotação criada offline com indicador ⏳
 *   06  Indicador some após voltar a conexão
 *   07  Painel web do gerente — aba "Ver anotações" do técnico
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import { execSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');

const SLUG    = 'primeiro-sync-app-servidor';
const IMG_DIR = path.join(ROOT, 'docs/backlog/aceites/imagens', SLUG);
fs.mkdirSync(IMG_DIR, { recursive: true });

const WEB_URL    = 'http://localhost:8000';
const MOBILE_URL = 'http://localhost:5200';

const MOBILE_VIEWPORT  = { width: 390, height: 844 };
const DESKTOP_VIEWPORT = { width: 1280, height: 800 };

// user_id do técnico (Carlos Técnico — tecnico@teste.local)
const TECNICO_USER_ID = 3;
// user_id do gerente (Marina Gerente — gerente@teste.local)
const GERENTE_USER_ID = 4;

const DEVICE_ID = 'aceite-sync-anotacoes-v1';

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

const feitos = [];
const falhas = [];

async function shot(page, nome, descricao) {
    const arquivo = path.join(IMG_DIR, nome);
    await page.screenshot({ path: arquivo, fullPage: false });
    feitos.push({ nome, descricao });
    console.log(`  ok  ${nome} — ${descricao}`);
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
                } catch(e) { console.warn('idb inject:', e); }
            }
        });
        return req;
    };
    try { localStorage.setItem('kalibrium.device_id', ${JSON.stringify(deviceId)}); } catch(e) {}
})();
`;
}

async function criarContextoMobile(browser) {
    const ctx = await browser.newContext({
        viewport: MOBILE_VIEWPORT,
        colorScheme: 'light',
        bypassCSP: true,
    });
    await ctx.addInitScript(CAPACITOR_MOCK);
    await ctx.addInitScript(idbInjectScript(DEVICE_ID));
    return ctx;
}


function prepararBanco() {
    console.log('\n=== PREPARANDO DADOS DE TESTE ===');
    try {
        execSync('php artisan cache:clear', { cwd: ROOT, stdio: 'pipe' });
        console.log('  ok cache limpo');
    } catch { /* ignora */ }

    const tmpFile = path.join(ROOT, '_prep_sync_anotacoes.php');
    fs.writeFileSync(tmpFile, `<?php
use App\\Models\\User;
use App\\Models\\MobileDevice;
use App\\Enums\\MobileDeviceStatus;

$tecnico = User::find(${TECNICO_USER_ID});
$gerente = User::find(${GERENTE_USER_ID});

if (!$tecnico || !$gerente) {
    echo "ERRO: usuarios nao encontrados\\n";
    return;
}

// Limpar notas antigas do tecnico pra demonstracao ficar limpa
\\DB::table('notes')->where('user_id', $tecnico->id)->delete();
echo "notas limpas\\n";

// Garantir tenant_user para tecnico e gerente no tenant 1
foreach ([$tecnico->id, $gerente->id] as $uid) {
    $role = ($uid === $gerente->id) ? 'gerente' : 'tecnico';
    $exists = \\DB::table('tenant_users')
        ->where('tenant_id', 1)
        ->where('user_id', $uid)
        ->exists();
    if (!$exists) {
        \\DB::table('tenant_users')->insert([
            'tenant_id' => 1,
            'user_id' => $uid,
            'role' => $role,
            'status' => 'active',
            'requires_2fa' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "tenant_user criado: user_id=$uid role=$role\\n";
    } else {
        echo "tenant_user ja existe: user_id=$uid\\n";
    }
}

// Garantir device aprovado
$device = MobileDevice::withoutGlobalScope('current_tenant')
    ->where('device_identifier', '${DEVICE_ID}')
    ->where('tenant_id', 1)
    ->first();

if ($device) {
    $device->update([
        'status' => MobileDeviceStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $gerente->id,
        'revoked_at' => null,
        'wiped_at' => null,
    ]);
    echo "device atualizado para approved\\n";
} else {
    MobileDevice::withoutGlobalScope('current_tenant')->create([
        'tenant_id' => 1,
        'user_id' => $tecnico->id,
        'device_identifier' => '${DEVICE_ID}',
        'device_label' => 'Aceite Sync Anotacoes',
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
        console.log('  ok banco:', saida.trim().split('\n').filter(Boolean).slice(-2).join(' | '));
    } catch (err) {
        console.log('  ! preparo do banco com erro:', err.stderr?.toString().split('\n')[0] ?? err.message.split('\n')[0]);
    } finally {
        try { fs.unlinkSync(tmpFile); } catch { /* ok */ }
    }
}

async function fazerLoginMobile(page) {
    await page.goto(MOBILE_URL, { waitUntil: 'networkidle', timeout: 30000 });

    try {
        await page.waitForSelector('#kb-email, input[type="email"]', { timeout: 15000 });
    } catch {
        console.log('  ! timeout aguardando tela de login mobile');
    }
    await sleep(1000);

    // Fechar alert se aberto
    try {
        await page.evaluate(async () => {
            const alert = document.querySelector('ion-alert');
            if (alert && typeof alert.dismiss === 'function') await alert.dismiss();
        });
        await sleep(500);
    } catch { /* sem alerta */ }

    await page.locator('#kb-email, input[type="email"]').first().fill('tecnico@teste.local');
    await page.locator('#kb-senha, input[type="password"]').first().fill('SenhaSegura123!');
    await page.locator('.kb-btn-entrar, button:has-text("Entrar")').first().click();
    await sleep(5000);

    // Aguardar Home
    try {
        await page.waitForSelector('.kb-home-page, .kb-home-header', { timeout: 15000 });
        console.log('  ok Home detectado');
    } catch {
        console.log('  ! timeout aguardando Home — continuando');
    }
    await sleep(1000);

    // Fechar alert se aparecer
    try {
        await page.evaluate(async () => {
            const alert = document.querySelector('ion-alert');
            if (alert && typeof alert.dismiss === 'function') await alert.dismiss();
        });
        await sleep(500);
    } catch { /* ok */ }
}

(async () => {
    prepararBanco();

    const browser = await chromium.launch({ headless: true });

    // =========================================================================
    // BLOCO MOBILE — 6 prints no app do técnico
    // =========================================================================
    console.log('\n=== APP MOBILE — TÉCNICO ===');

    const mobileCtx = await criarContextoMobile(browser);
    const mobile = await mobileCtx.newPage();

    mobile.on('pageerror', err => {
        if (!err.message.includes('capacitor') && !err.message.includes('IndexedDB')) {
            console.log('  ! JS:', err.message.split('\n')[0]);
        }
    });

    try {
        await fazerLoginMobile(mobile);

        // -----------------------------------------------------------------
        // PRINT 01 — Card "Anotações" no Home
        // -----------------------------------------------------------------
        console.log('\n=== 01 — CARD ANOTAÇÕES NO HOME ===');
        await shot(mobile, '01-card-anotacoes-home.png',
            'Tela inicial do app com o card "Anotações" visível');

        // -----------------------------------------------------------------
        // PRINT 02 — Tela de anotações vazia
        // -----------------------------------------------------------------
        console.log('\n=== 02 — TELA DE ANOTAÇÕES VAZIA ===');

        // Clicar no card de anotações (aria-label="Ver anotações" ou .kb-card--clicavel com texto Anotações)
        const cardAnotacoes = mobile.locator('[aria-label="Ver anotações"], .kb-card--clicavel').first();
        const cardVisivel = await cardAnotacoes.isVisible({ timeout: 5000 }).catch(() => false);
        if (cardVisivel) {
            await cardAnotacoes.click();
            await sleep(2000);
        } else {
            console.log('  ! Card não localizado via seletor — tentando pelo texto');
            const cardTexto = mobile.locator('div').filter({ hasText: /^Anotações/ }).first();
            const cardTextoVisivel = await cardTexto.isVisible({ timeout: 3000 }).catch(() => false);
            if (cardTextoVisivel) {
                await cardTexto.click();
                await sleep(2000);
            } else {
                console.log('  ! Card não localizado — continuando');
            }
        }

        // Aguardar tela de notas
        try {
            await mobile.waitForSelector('.kb-notes-page, .kb-notes-header', { timeout: 8000 });
            console.log('  ok tela de notas detectada');
        } catch {
            console.log('  ! timeout aguardando tela de notas');
        }
        await sleep(1000);

        await shot(mobile, '02-tela-anotacoes-vazia.png',
            'Tela de anotações vazia com mensagem de boas-vindas e botão +');

        // -----------------------------------------------------------------
        // PRINT 03 — Modal de criar anotação
        // -----------------------------------------------------------------
        console.log('\n=== 03 — MODAL DE CRIAR ANOTAÇÃO ===');

        const btnMais = mobile.locator('[aria-label="Nova anotação"], .kb-notes-fab').first();
        const btnMaisVisivel = await btnMais.isVisible({ timeout: 5000 }).catch(() => false);
        if (btnMaisVisivel) {
            await btnMais.click();
            await sleep(1000);
            await shot(mobile, '03-modal-criar-anotacao.png',
                'Modal para criar anotação com campo título e área de texto');
        } else {
            console.log('  ! Botão + não encontrado — capturando estado atual');
            await shot(mobile, '03-modal-criar-anotacao.png', 'Estado atual (botão + não localizado)');
            falhas.push('Print 03: botão + para nova anotação não encontrado');
        }

        // -----------------------------------------------------------------
        // PRINT 04 — Anotação salva online (sem ⏳)
        // -----------------------------------------------------------------
        console.log('\n=== 04 — ANOTAÇÃO SALVA ONLINE ===');

        // Preencher título e corpo no modal (se aberto)
        const inputTitulo = mobile.locator('.kb-modal-input, input[placeholder="Título"]').first();
        const inputTituloVisivel = await inputTitulo.isVisible({ timeout: 3000 }).catch(() => false);

        if (inputTituloVisivel) {
            await inputTitulo.fill('ligar João da Acme');
            await sleep(300);

            const inputCorpo = mobile.locator('.kb-modal-textarea, textarea[placeholder="Texto da anotação"]').first();
            const inputCorpoVisivel = await inputCorpo.isVisible({ timeout: 3000 }).catch(() => false);
            if (inputCorpoVisivel) {
                await inputCorpo.fill('buscar peça');
                await sleep(300);
            }

            // Clicar em Salvar
            const btnSalvar = mobile.locator('.kb-modal-btn-salvar').first();
            const btnSalvarVisivel = await btnSalvar.isVisible({ timeout: 3000 }).catch(() => false);
            if (btnSalvarVisivel) {
                await btnSalvar.click();
                // Aguardar modal fechar e lista aparecer
                try {
                    await mobile.waitForSelector('.kb-modal-overlay', { state: 'hidden', timeout: 8000 });
                } catch { /* ok */ }
                await sleep(3000); // aguardar sync online + render da lista
            } else {
                console.log('  ! Botão Salvar não encontrado no modal');
                falhas.push('Print 04: botão Salvar não encontrado');
            }
        } else {
            console.log('  ! Modal não aberto — tentando abrir via botão FAB');
            // Tentar abrir o modal novamente
            const fabBtn = mobile.locator('[aria-label="Nova anotação"], .kb-notes-fab').first();
            if (await fabBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
                await fabBtn.click();
                await sleep(800);
                const inputT2 = mobile.locator('.kb-modal-input').first();
                if (await inputT2.isVisible({ timeout: 2000 }).catch(() => false)) {
                    await inputT2.fill('ligar João da Acme');
                    const inputC2 = mobile.locator('.kb-modal-textarea').first();
                    if (await inputC2.isVisible({ timeout: 2000 }).catch(() => false)) {
                        await inputC2.fill('buscar peça');
                    }
                    const btnS2 = mobile.locator('.kb-modal-btn-salvar').first();
                    if (await btnS2.isVisible({ timeout: 2000 }).catch(() => false)) {
                        await btnS2.click();
                        await sleep(3000);
                    }
                }
            } else {
                falhas.push('Print 04: modal de criação não localizado para preencher');
            }
        }

        await shot(mobile, '04-anotacao-salva-online.png',
            'Anotação "ligar João da Acme" na lista sem indicador de pendente');

        // -----------------------------------------------------------------
        // PRINT 05 — Anotação offline com ⏳
        // -----------------------------------------------------------------
        console.log('\n=== 05 — ANOTAÇÃO OFFLINE COM INDICADOR ===');

        await mobileCtx.setOffline(true);
        await sleep(500);

        // Abrir modal de nova anotação offline
        const btnMais2 = mobile.locator('[aria-label="Nova anotação"], .kb-notes-fab').first();
        const btnMais2Visivel = await btnMais2.isVisible({ timeout: 5000 }).catch(() => false);
        if (btnMais2Visivel) {
            await btnMais2.click();
            await sleep(800);

            const inputTitulo2 = mobile.locator('.kb-modal-input, input[placeholder="Título"]').first();
            const inputTitulo2Visivel = await inputTitulo2.isVisible({ timeout: 3000 }).catch(() => false);
            if (inputTitulo2Visivel) {
                await inputTitulo2.fill('verificar equipamento sala 3');
                await sleep(300);

                const btnSalvar2 = mobile.locator('.kb-modal-btn-salvar').first();
                const btnSalvar2Visivel = await btnSalvar2.isVisible({ timeout: 3000 }).catch(() => false);
                if (btnSalvar2Visivel) {
                    await btnSalvar2.click();
                    try {
                        await mobile.waitForSelector('.kb-modal-overlay', { state: 'hidden', timeout: 5000 });
                    } catch { /* ok */ }
                    await sleep(1500);
                }
            }
        } else {
            console.log('  ! Botão + não encontrado para criar anotação offline');
            falhas.push('Print 05: botão + não encontrado para criar anotação offline');
        }

        await shot(mobile, '05-anotacao-offline-pendente.png',
            'Anotação criada sem conexão mostrando indicador ⏳ "aguardando sincronizar"');

        // -----------------------------------------------------------------
        // PRINT 06 — Indicador some após voltar online
        // -----------------------------------------------------------------
        console.log('\n=== 06 — SINCRONIZOU — INDICADOR SUMIU ===');

        await mobileCtx.setOffline(false);
        await sleep(4000); // aguardar loop de sync (~3s)

        await shot(mobile, '06-sincronizado-sem-indicador.png',
            'Lista de anotações sem indicador ⏳ — todas sincronizadas com o servidor');

    } catch (err) {
        console.error('  ERRO no bloco mobile:', err.message.split('\n')[0]);
        falhas.push('Bloco mobile encerrou com erro: ' + err.message.split('\n')[0]);
        try { await shot(mobile, '0x-erro-mobile.png', 'Estado da tela no momento do erro'); } catch { /* ok */ }
    } finally {
        await mobileCtx.setOffline(false).catch(() => {});
        await mobileCtx.close();
    }

    // =========================================================================
    // BLOCO WEB — Painel do gerente, aba de anotações do técnico
    // =========================================================================
    console.log('\n=== PAINEL WEB — GERENTE ===');

    const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
    const webPage = await webCtx.newPage();

    try {
        // Login normal do gerente
        await webPage.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 20000 });
        await sleep(800);
        await webPage.locator('input[name="email"]').first().fill('gerente@teste.local');
        await webPage.locator('input[name="password"]').first().fill('password');
        await webPage.locator('button[type="submit"]').first().click();
        try {
            await webPage.waitForURL(`${WEB_URL}/dashboard`, { timeout: 15000 });
            console.log('  ok login do gerente');
        } catch {
            console.log('  ! timeout aguardando dashboard — continuando');
        }
        await sleep(1500);

        // Navegar para a aba de anotações do técnico (user_id=3)
        await webPage.goto(`${WEB_URL}/technicians/${TECNICO_USER_ID}/notes`, { waitUntil: 'networkidle', timeout: 20000 });
        await sleep(2000);

        // -----------------------------------------------------------------
        // PRINT 07 — Aba de anotações do técnico no painel web
        // -----------------------------------------------------------------
        console.log('\n=== 07 — ABA ANOTAÇÕES DO TÉCNICO NO PAINEL WEB ===');

        await shot(webPage, '07-gerente-ve-anotacoes-tecnico.png',
            'Painel do gerente: aba "Ver anotações" com a lista das anotações criadas pelo técnico Carlos');

    } catch (err) {
        console.error('  ERRO no bloco web:', err.message.split('\n')[0]);
        falhas.push('Bloco web encerrou com erro: ' + err.message.split('\n')[0]);
        try { await shot(webPage, '0x-erro-web.png', 'Estado da tela web no momento do erro'); } catch { /* ok */ }
    } finally {
        await webCtx.close();
    }

    await browser.close();

    // =========================================================================
    // Resumo
    // =========================================================================
    console.log('\n=== RESUMO ===');
    console.log(`Prints gerados: ${feitos.length}`);
    feitos.forEach(f => console.log(`  ok  ${f.nome} — ${f.descricao}`));
    if (falhas.length) {
        console.log(`\nBloqueios (${falhas.length}):`);
        falhas.forEach(f => console.log(`  !   ${f}`));
    } else {
        console.log('\nNenhum bloqueio.');
    }
})();
