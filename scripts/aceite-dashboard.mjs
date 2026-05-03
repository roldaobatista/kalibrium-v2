/**
 * Script de aceite — Gerente tem uma tela inicial quando entra no painel
 * Roda com: node scripts/aceite-dashboard.mjs (do diretório raiz do projeto)
 *
 * Cobre:
 *   01  Login e chegada no dashboard
 *   02  Cards com números reais (pendentes, aprovados, bloqueados)
 *   03  Saudação no cabeçalho com horário real
 *   04  Sidebar com "Início" em destaque
 *   05  Atalhos rápidos
 *   06  Clique em "Aprovar pedidos pendentes" → lista filtrada
 *   07  Layout responsivo em viewport mobile 390×844
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/gerente-tem-tela-inicial');
fs.mkdirSync(IMG_DIR, { recursive: true });

const WEB_URL = 'http://localhost:8000';
const EMAIL   = 'gerente@teste.local';
const SENHA   = 'password';

const feitos = [];
const falhas = [];

async function shot(page, nome, descricao) {
  const arquivo = path.join(IMG_DIR, nome);
  await page.screenshot({ path: arquivo, fullPage: false });
  feitos.push({ nome, descricao });
  console.log(`  ok  ${nome} — ${descricao}`);
}

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

async function fazerLogin(page) {
  await page.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 20000 });
  await sleep(800);

  const inputEmail = page.locator('input[name="email"]').first();
  const inputSenha = page.locator('input[name="password"]').first();
  const btnLogin   = page.locator('button[type="submit"]').first();

  await inputEmail.fill(EMAIL);
  await inputSenha.fill(SENHA);
  await btnLogin.click();

  // Aguardar redirecionamento para /dashboard
  await page.waitForURL(`${WEB_URL}/dashboard`, { timeout: 15000 });
  await sleep(1500);
}

(async () => {
  const browser = await chromium.launch({ headless: true });

  // ─────────────────────────────────────────────────────────────
  // DESKTOP — 1280×800
  // ─────────────────────────────────────────────────────────────
  console.log('\n=== DESKTOP (1280×800) ===');
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await ctx.newPage();

  try {
    await fazerLogin(page);

    // 01 — Dashboard completo após login
    await shot(page, '01-pos-login-dashboard.png',
      'Tela /dashboard completa logo após o login do gerente');

    // 02 — Cards com números reais
    // Tenta localizar a grade de cards
    const cards = page.locator('[data-testid="dashboard-cards"], .grid, section').first();
    const cardsBox = await cards.boundingBox().catch(() => null);
    if (cardsBox) {
      await page.screenshot({
        path: path.join(IMG_DIR, '02-cards-com-pendencias.png'),
        clip: { x: cardsBox.x, y: cardsBox.y, width: cardsBox.width, height: Math.min(cardsBox.height, 350) },
      });
      feitos.push({ nome: '02-cards-com-pendencias.png', descricao: 'Grade dos 3 cards com contadores' });
      console.log('  ok  02-cards-com-pendencias.png — Grade dos 3 cards com contadores');
    } else {
      // Fallback: scroll pra área dos cards e tira fullpage
      await shot(page, '02-cards-com-pendencias.png', 'Grade dos 3 cards com contadores (viewport completo)');
    }

    // 03 — Saudação no cabeçalho (parte superior)
    await page.screenshot({
      path: path.join(IMG_DIR, '03-saudacao-cabecalho.png'),
      clip: { x: 0, y: 0, width: 1280, height: 200 },
    });
    feitos.push({ nome: '03-saudacao-cabecalho.png', descricao: 'Topo da tela com saudação real do horário' });
    console.log('  ok  03-saudacao-cabecalho.png — Topo com saudação');

    // 04 — Sidebar à esquerda
    // Tenta localizar a sidebar por papel semântico ou seletores comuns
    const sidebar = page.locator('nav, aside, [role="navigation"]').first();
    const sidebarBox = await sidebar.boundingBox().catch(() => null);
    if (sidebarBox) {
      await page.screenshot({
        path: path.join(IMG_DIR, '04-sidebar-inicio-ativo.png'),
        clip: { x: sidebarBox.x, y: sidebarBox.y, width: Math.min(sidebarBox.width, 300), height: sidebarBox.height },
      });
      feitos.push({ nome: '04-sidebar-inicio-ativo.png', descricao: 'Sidebar mostrando "Início" destacado' });
      console.log('  ok  04-sidebar-inicio-ativo.png — Sidebar com Início ativo');
    } else {
      await shot(page, '04-sidebar-inicio-ativo.png', 'Sidebar (fallback viewport completo)');
    }

    // 05 — Atalhos rápidos (parte inferior da página)
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await sleep(600);
    await shot(page, '05-atalhos.png', 'Atalhos rápidos no rodapé do dashboard');
    // Rolar de volta ao topo
    await page.evaluate(() => window.scrollTo(0, 0));
    await sleep(400);

    // 06 — Clicar em "Aprovar pedidos pendentes"
    const btnAprovar = page.locator('a, button').filter({ hasText: /aprovar pedidos pendentes/i }).first();
    const btnVisivel = await btnAprovar.isVisible().catch(() => false);
    if (btnVisivel) {
      await btnAprovar.click();
      await page.waitForURL(/mobile-devices/, { timeout: 10000 });
      await sleep(1200);
      await shot(page, '06-clique-aprovar-pendentes.png',
        'Lista de celulares filtrada por status=pending após clicar no atalho');
    } else {
      // Talvez o botão esteja fora do viewport — rolar e tentar
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await sleep(600);
      const btnAprovarScroll = page.locator('a, button').filter({ hasText: /aprovar pedidos pendentes/i }).first();
      if (await btnAprovarScroll.isVisible().catch(() => false)) {
        await btnAprovarScroll.click();
        await page.waitForURL(/mobile-devices/, { timeout: 10000 });
        await sleep(1200);
        await shot(page, '06-clique-aprovar-pendentes.png',
          'Lista de celulares filtrada por status=pending após clicar no atalho');
      } else {
        console.log('  ! Botão "Aprovar pedidos pendentes" não encontrado');
        falhas.push('Print 06: botão "Aprovar pedidos pendentes" não encontrado na página');
        // Navegar direto para confirmar a URL funciona
        await page.goto(`${WEB_URL}/mobile-devices?status=pending`, { waitUntil: 'networkidle', timeout: 15000 });
        await sleep(1000);
        await shot(page, '06-clique-aprovar-pendentes.png',
          'Lista /mobile-devices?status=pending (navegação direta — botão não encontrado)');
      }
    }

  } catch (err) {
    console.error('  ERRO no bloco desktop:', err.message);
    falhas.push('Bloco desktop encerrou com erro: ' + err.message);
    await shot(page, '0x-desktop-estado-erro.png', 'Estado da tela no momento do erro').catch(() => {});
  } finally {
    await ctx.close();
  }

  // ─────────────────────────────────────────────────────────────
  // MOBILE — 390×844 (responsivo)
  // ─────────────────────────────────────────────────────────────
  console.log('\n=== MOBILE (390×844) ===');
  const ctxMob = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 3,
    isMobile: true,
  });
  const mob = await ctxMob.newPage();

  try {
    await fazerLogin(mob);
    await sleep(800);
    await shot(mob, '07-mobile-responsive.png',
      'Dashboard em viewport 390×844 com cards empilhados verticalmente');
  } catch (err) {
    console.error('  ERRO no bloco mobile:', err.message);
    falhas.push('Bloco mobile encerrou com erro: ' + err.message);
    await shot(mob, '0y-mobile-estado-erro.png', 'Estado da tela no momento do erro (mobile)').catch(() => {});
  } finally {
    await ctxMob.close();
  }

  await browser.close();

  // ─────────────────────────────────────────────────────────────
  // Resumo
  // ─────────────────────────────────────────────────────────────
  console.log('\n=== RESUMO ===');
  console.log(`Prints gerados: ${feitos.length}`);
  feitos.forEach(f => console.log(`  ok  ${f.nome}`));
  if (falhas.length) {
    console.log(`\nBloqueios (${falhas.length}):`);
    falhas.forEach(f => console.log('  !  ' + f));
  } else {
    console.log('\nNenhum bloqueio.');
  }
})();
