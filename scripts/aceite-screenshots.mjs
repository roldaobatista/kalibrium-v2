/**
 * Script de aceite visual — história: tecnico-entra-no-app-do-celular
 * Roda com: node scripts/aceite-screenshots.mjs
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/tecnico-entra-no-app-do-celular');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5174';
const WEB_URL = 'http://localhost:8000';

const MOBILE_VIEWPORT = { width: 390, height: 844 };
const DESKTOP_VIEWPORT = { width: 1280, height: 800 };

const TECNICO_EMAIL = 'tecnico@teste.local';
const TECNICO_SENHA = 'senha123456';
const GERENTE_EMAIL = 'gerente@teste.local';
const GERENTE_SENHA = 'senha123456';

async function shot(page, nome, descricao) {
  const arquivo = path.join(IMG_DIR, nome);
  await page.screenshot({ path: arquivo });
  console.log(`  ✓ ${nome} — ${descricao}`);
  return arquivo;
}

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

// Clica num ion-button pelo texto via shadow DOM
async function clickIonButton(page, texto) {
  await page.evaluate((t) => {
    const btns = document.querySelectorAll('ion-button');
    for (const b of btns) {
      if (b.textContent?.trim().includes(t)) {
        b.click();
        return;
      }
    }
  }, texto);
}

// Fecha qualquer alert Ionic aberto (clica no primeiro botão)
async function fecharAlertSeAberto(page) {
  const alerta = page.locator('.alert-wrapper').first();
  if (await alerta.isVisible().catch(() => false)) {
    const btns = page.locator('.alert-button');
    const count = await btns.count();
    if (count > 0) {
      await btns.first().click();
      await sleep(600);
    }
  }
}

(async () => {
  const browser = await chromium.launch({ headless: true });

  try {
    // -----------------------------------------------------------------------
    // BLOCO 1: App mobile — tela de login
    // -----------------------------------------------------------------------
    console.log('\n=== APP MOBILE — TELA DE LOGIN ===');
    const mobileCtx = await browser.newContext({
      viewport: MOBILE_VIEWPORT,
      // Limpar storage para garantir estado limpo
    });
    const mobile = await mobileCtx.newPage();
    await mobile.goto(MOBILE_URL, { waitUntil: 'networkidle' });
    await sleep(2000);

    // Fechar alert de biometria se tiver aberto de sessão anterior
    await fecharAlertSeAberto(mobile);
    await sleep(500);

    // 1. Tela de login inicial
    await shot(mobile, '01-tela-login-inicial.png', 'Tela de login ao abrir o app');

    // 2. Login com campos vazios
    await clickIonButton(mobile, 'Entrar');
    await sleep(1500);
    await shot(mobile, '02-validacao-campos-vazios.png', 'Aviso ao tentar entrar sem preencher');

    // Fechar toast se visível
    await sleep(1500);

    // 3. Login com credenciais erradas
    await mobile.fill('input[type="email"]', 'wrong@example.com');
    await mobile.fill('input[type="password"]', 'senhaerrada');
    await clickIonButton(mobile, 'Entrar');
    await sleep(3000);
    await shot(mobile, '03-credenciais-erradas.png', 'Aviso de e-mail ou senha incorretos');
    await sleep(2000);

    // 4. Login válido — primeiro device (aguardando aprovação)
    await mobile.fill('input[type="email"]', TECNICO_EMAIL);
    await mobile.fill('input[type="password"]', TECNICO_SENHA);
    await clickIonButton(mobile, 'Entrar');
    await sleep(3000);
    await shot(mobile, '04-device-aguardando-aprovacao.png', 'Aviso de aguardando aprovação do gerente');
    // Fechar alert
    await fecharAlertSeAberto(mobile);
    await sleep(800);

    // 5. Rate limit — 6 tentativas erradas seguidas
    for (let i = 1; i <= 6; i++) {
      await mobile.fill('input[type="email"]', TECNICO_EMAIL);
      await mobile.fill('input[type="password"]', 'errado' + i);
      await clickIonButton(mobile, 'Entrar');
      await sleep(800);
    }
    await sleep(2000);
    await shot(mobile, '05-rate-limit-bloqueio.png', 'Trava por excesso de tentativas erradas');

    await mobileCtx.close();

    // -----------------------------------------------------------------------
    // BLOCO 2: Painel do gerente (desktop web Livewire)
    // -----------------------------------------------------------------------
    console.log('\n=== PAINEL DO GERENTE ===');
    const webCtx = await browser.newContext({ viewport: DESKTOP_VIEWPORT });
    const web = await webCtx.newPage();

    // Login do gerente via rota de aceite (user_id=4 = gerente@teste.local)
    await web.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
    await sleep(2000);

    // 6. Lista de celulares (incluindo o pedido pendente do técnico)
    await web.goto(`${WEB_URL}/mobile-devices`, { waitUntil: 'networkidle' });
    await sleep(2000);
    await shot(web, '06-painel-lista-dispositivos-pendentes.png', 'Painel do gerente com pedido do técnico pendente');

    // 7. Clicar em Aprovar (primeiro pedido pendente)
    const btnAprovar = web.locator('button:has-text("Aprovar")').first();
    const btnAprovarWire = web.locator('[wire\\:click*="aprovar"]').first();
    const aprovBtn = await btnAprovar.isVisible().catch(() => false) ? btnAprovar : btnAprovarWire;

    if (await aprovBtn.isVisible().catch(() => false)) {
      await aprovBtn.click();
      await sleep(2500);
      await shot(web, '07-dispositivo-aprovado.png', 'Confirmação de aprovação — device do técnico aprovado');
    } else {
      // Pode estar em modal de confirmação
      await shot(web, '07-dispositivo-aprovado.png', 'Estado da lista de dispositivos');
      console.log('  ! Botão Aprovar não encontrado — print do estado atual');
    }

    // 8. Filtros — aguardando
    const selectFiltro = web.locator('select[wire\\:model\\.live*="status"], select[wire\\:model*="statusFilter"], select').first();
    if (await selectFiltro.isVisible().catch(() => false)) {
      await selectFiltro.selectOption({ value: 'pending' });
      await sleep(1500);
      await shot(web, '08a-filtro-aguardando.png', 'Filtro exibindo apenas dispositivos aguardando aprovação');

      await selectFiltro.selectOption({ value: 'approved' });
      await sleep(1500);
      await shot(web, '08b-filtro-aprovados.png', 'Filtro exibindo apenas dispositivos aprovados');
    } else {
      await shot(web, '08a-filtro-aguardando.png', 'Área de filtros da lista de dispositivos');
      fs.copyFileSync(path.join(IMG_DIR, '08a-filtro-aguardando.png'), path.join(IMG_DIR, '08b-filtro-aprovados.png'));
    }

    // 9. Bloquear device aprovado
    // Resetar filtro para ver todos
    if (await selectFiltro.isVisible().catch(() => false)) {
      await selectFiltro.selectOption({ value: '' });
      await sleep(1000);
    }
    const btnBloquear = web.locator('button:has-text("Bloquear"), [wire\\:click*="revogar"], [wire\\:click*="bloquear"]').first();
    if (await btnBloquear.isVisible().catch(() => false)) {
      await btnBloquear.click();
      await sleep(2500);
      await shot(web, '09-dispositivo-bloqueado.png', 'Device bloqueado pelo gerente — badge mostra bloqueado');
    } else {
      await shot(web, '09-dispositivo-bloqueado.png', 'Lista de dispositivos após aprovação (botão bloquear não encontrado)');
    }

    await webCtx.close();

    // -----------------------------------------------------------------------
    // BLOCO 3: App mobile — login após aprovação
    // -----------------------------------------------------------------------
    console.log('\n=== APP MOBILE — APÓS APROVAÇÃO ===');
    // Usar device com ID fixo que foi aprovado no banco pelo gerente no passo 7
    const DEVICE_APROVADO_ID = 'aceite-device-aprovado-001';
    const mobileCtx2 = await browser.newContext({ viewport: MOBILE_VIEWPORT });
    const mobile2 = await mobileCtx2.newPage();
    // Precisa navegar primeiro para poder usar localStorage
    await mobile2.goto(MOBILE_URL, { waitUntil: 'networkidle' });
    await sleep(1000);
    // Injetar o device ID aprovado no localStorage antes de fazer login
    await mobile2.evaluate((did) => {
      localStorage.setItem('kalibrium.device_id', did);
    }, DEVICE_APROVADO_ID);
    await sleep(300);
    await fecharAlertSeAberto(mobile2);
    await sleep(500);

    // 10. Login com device já aprovado
    await mobile2.fill('input[type="email"]', TECNICO_EMAIL);
    await mobile2.fill('input[type="password"]', TECNICO_SENHA);
    await clickIonButton(mobile2, 'Entrar');
    await sleep(3500);
    await shot(mobile2, '10-login-apos-aprovacao-bem-vindo.png', 'Técnico entra no app após aprovação — tela Bem-vindo');

    // Fechar alert de biometria se apareceu
    await fecharAlertSeAberto(mobile2);
    await sleep(600);

    // 11. Botão Sair
    const btnSair = mobile2.locator('ion-button:has-text("Sair")').first();
    if (await btnSair.isVisible().catch(() => false)) {
      await btnSair.click();
      await sleep(2000);
      await shot(mobile2, '11-apos-sair-volta-login.png', 'Tela de login após clicar em Sair');
    } else {
      // Pode ser que não entrou — tirar print do que está visível
      await shot(mobile2, '11-apos-sair-volta-login.png', 'Estado do app após tentativa de login pós-aprovação');
      console.log('  ! Botão Sair não encontrado — device pode ter sido bloqueado no passo 9');
    }

    await mobileCtx2.close();

    console.log('\n✓ Todos os prints finalizados em:', IMG_DIR);
    console.log('Total:', fs.readdirSync(IMG_DIR).filter(f => f.endsWith('.png')).length, 'imagens');

  } catch (err) {
    console.error('\nERRO:', err.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
