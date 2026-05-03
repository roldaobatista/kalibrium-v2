/**
 * Script de aceite — Gerente cadastra técnico no laboratório
 * Roda com: node scripts/aceite-tecnicos.mjs (do diretório raiz do projeto)
 *
 * Cobre:
 *   01  Item "Técnicos" na sidebar
 *   02  Lista de técnicos com badges "Ativo"
 *   03  Estado vazio (tenant sem técnicos)
 *   04  Modal de cadastro aberto
 *   05  Erro de email duplicado
 *   06  Cadastro bem-sucedido — novo técnico na lista
 *   07  Modal de edição preenchido
 *   08  Confirmação de desativar
 *   09  Badge "Inativo" após desativar
 *   10  Badge "Ativo" voltando após reativar
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SLUG    = 'gerente-cadastra-tecnico';
const IMG_DIR = path.join(__dirname, `../docs/backlog/aceites/imagens/${SLUG}`);
fs.mkdirSync(IMG_DIR, { recursive: true });

const WEB_URL   = 'http://localhost:8000';
const EMAIL     = 'gerente@teste.local';
const SENHA     = 'password';
const EMAIL_T2  = 'gerente2@demo.local';
const SENHA_T2  = 'Senha123';

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

async function fazerLogin(page, email, senha) {
  await page.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 20000 });
  await sleep(600);
  await page.locator('input[name="email"]').first().fill(email);
  await page.locator('input[name="password"]').first().fill(senha);
  await page.locator('button[type="submit"]').first().click();
  await page.waitForURL(`${WEB_URL}/dashboard`, { timeout: 15000 });
  await sleep(1200);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await ctx.newPage();

  // ─────────────────────────────────────────────────────────────
  // TENANT PRINCIPAL — gerente@teste.local / Kalibrium Demo
  // ─────────────────────────────────────────────────────────────
  try {
    await fazerLogin(page, EMAIL, SENHA);

    // 01 — Sidebar com "Técnicos"
    const sidebar = page.locator('nav, aside, [role="navigation"]').first();
    const sidebarBox = await sidebar.boundingBox().catch(() => null);
    if (sidebarBox) {
      await page.screenshot({
        path: path.join(IMG_DIR, '01-sidebar-tecnicos.png'),
        clip: { x: sidebarBox.x, y: sidebarBox.y, width: Math.min(sidebarBox.width + 20, 320), height: Math.min(sidebarBox.height, 800) },
      });
      feitos.push({ nome: '01-sidebar-tecnicos.png', descricao: 'Sidebar com item "Técnicos" visível' });
      console.log('  ok  01-sidebar-tecnicos.png — Sidebar com item Técnicos');
    } else {
      await shot(page, '01-sidebar-tecnicos.png', 'Sidebar (fallback viewport completo)');
    }

    // Navegar para /technicians
    await page.goto(`${WEB_URL}/technicians`, { waitUntil: 'networkidle', timeout: 20000 });
    await sleep(1000);

    // 02 — Lista de técnicos com badges
    await shot(page, '02-lista-tecnicos.png', 'Lista de técnicos com badges Ativo');

    // 04 — Abrir modal de cadastro
    const btnCadastrar = page.locator('button').filter({ hasText: /cadastrar técnico/i }).first();
    await btnCadastrar.click();
    await sleep(800);
    await shot(page, '04-modal-cadastro.png', 'Modal de cadastro com 3 campos (Nome, E-mail, Senha)');

    // 05 — Validação: email duplicado
    await page.locator('input[wire\\:model="name"]').fill('Teste Duplicado');
    await sleep(200);
    await page.locator('input[wire\\:model="email"]').fill('carlos.silva@demo.local');
    await sleep(200);
    await page.locator('input[wire\\:model="password"]').fill('Senha123');
    await sleep(200);
    // Submeter
    await page.locator('button[type="submit"]').filter({ hasText: /cadastrar/i }).click();
    await sleep(1500);
    await shot(page, '05-erro-email-duplicado.png', 'Mensagem de erro para e-mail já cadastrado no laboratório');

    // Fechar modal se ainda aberto
    const fecharBtn = page.locator('button').filter({ hasText: /cancelar/i }).first();
    const fecharVisivel = await fecharBtn.isVisible().catch(() => false);
    if (fecharVisivel) await fecharBtn.click();
    await sleep(500);

    // 06 — Cadastro bem-sucedido
    const btnCadastrar2 = page.locator('button').filter({ hasText: /cadastrar técnico/i }).first();
    await btnCadastrar2.click();
    await sleep(600);

    const timestamp = Date.now();
    const novoEmail = `tecnico.novo.${timestamp}@demo.local`;
    await page.locator('input[wire\\:model="name"]').fill('Rodrigo Novo');
    await sleep(200);
    await page.locator('input[wire\\:model="email"]').fill(novoEmail);
    await sleep(200);
    await page.locator('input[wire\\:model="password"]').fill('Senha123');
    await sleep(200);
    await page.locator('button[type="submit"]').filter({ hasText: /cadastrar/i }).click();
    await sleep(2000);
    await shot(page, '06-cadastro-bem-sucedido.png', 'Lista atualizada com "Rodrigo Novo" e badge Ativo');

    // 07 — Modal de edição
    // Usa nth(0) para pegar o primeiro botão Editar da tabela desktop
    const btnEditar = page.locator('button').filter({ hasText: /Editar/ }).first();
    const editarVisivel = await btnEditar.isVisible().catch(() => false);
    if (editarVisivel) {
      await btnEditar.click();
      await sleep(800);
      await shot(page, '07-modal-edicao.png', 'Modal de edição com campos Nome e E-mail preenchidos');
      // Fechar modal — clicar no X do cabeçalho para garantir fechamento
      const fecharX = page.locator('[x-on\\:keydown\\.escape\\.window]').locator('button').first();
      const fecharXVisivel = await fecharX.isVisible().catch(() => false);
      if (fecharXVisivel) {
        await fecharX.click();
      } else {
        // Fallback: ESC
        await page.keyboard.press('Escape');
      }
      // Aguarda o modal desaparecer completamente
      await page.waitForFunction(() => !document.querySelector('[x-on\\:keydown\\.escape\\.window]'), { timeout: 5000 }).catch(() => {});
      await sleep(600);
    } else {
      falhas.push('Print 07: botão Editar não encontrado na listagem');
      console.log('  ! Print 07: botão Editar não encontrado');
    }

    // 08 — Confirmação de desativar
    // O wire:confirm usa window.confirm() nativo — registrar handler ANTES do clique
    const btnDesativar = page.locator('button').filter({ hasText: /Desativar/ }).first();
    const desativarVisivel = await btnDesativar.isVisible().catch(() => false);
    if (desativarVisivel) {
      // Registra handler para aceitar o confirm nativo automaticamente
      page.once('dialog', async dialog => {
        await dialog.accept();
      });
      await btnDesativar.click();
      await sleep(2500);

      // Print do estado após confirmação (badge Inativo já visível)
      await shot(page, '08-confirmacao-desativar.png', 'Lista após confirmar desativação — técnico com badge "Inativo"');
      await sleep(500);

      // 09 — Badge "Inativo"
      await shot(page, '09-badge-inativo.png', 'Badge "Inativo" no técnico que foi desativado');

      // 10 — Reativar
      const btnReativar = page.locator('button').filter({ hasText: /Reativar/ }).first();
      const reativarVisivel = await btnReativar.isVisible().catch(() => false);
      if (reativarVisivel) {
        await btnReativar.click();
        await sleep(1500);
        await shot(page, '10-badge-ativo-reativado.png', 'Badge "Ativo" voltando após reativar o técnico');
      } else {
        falhas.push('Print 10: botão Reativar não encontrado após desativar');
        console.log('  ! Print 10: botão Reativar não encontrado');
      }
    } else {
      falhas.push('Print 08-10: botão Desativar não encontrado');
      console.log('  ! Prints 08-10: botão Desativar não encontrado');
    }

  } catch (err) {
    console.error('  ERRO no bloco tenant principal:', err.message);
    falhas.push('Bloco principal encerrou com erro: ' + err.message);
    await shot(page, '0x-erro.png', 'Estado da tela no momento do erro').catch(() => {});
  } finally {
    await ctx.close();
  }

  // ─────────────────────────────────────────────────────────────
  // TENANT SECUNDÁRIO — estado vazio (sem técnicos)
  // ─────────────────────────────────────────────────────────────
  const ctx2 = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page2 = await ctx2.newPage();

  try {
    await fazerLogin(page2, EMAIL_T2, SENHA_T2);
    await page2.goto(`${WEB_URL}/technicians`, { waitUntil: 'networkidle', timeout: 20000 });
    await sleep(1000);
    await page2.screenshot({
      path: path.join(IMG_DIR, '03-estado-vazio.png'),
      fullPage: false,
    });
    feitos.push({ nome: '03-estado-vazio.png', descricao: 'Mensagem "Nenhum técnico cadastrado ainda" em laboratório sem técnicos' });
    console.log('  ok  03-estado-vazio.png — Estado vazio');
  } catch (err) {
    console.error('  ERRO no bloco tenant 2:', err.message);
    falhas.push('Estado vazio (tenant 2): ' + err.message);
  } finally {
    await ctx2.close();
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
