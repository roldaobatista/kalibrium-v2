/**
 * Script de aceite — Técnico recupera senha esquecida
 * Roda com: node scripts/aceite-recuperacao-senha.mjs (do diretório raiz do projeto)
 *
 * Cobre:
 *   1–4  App mobile (iPhone 14 viewport 390×844) em http://localhost:5200
 *   5–8  Web gerente (desktop 1280×800) em http://localhost:8000
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import { execSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/tecnico-recupera-senha-esquecida');
fs.mkdirSync(IMG_DIR, { recursive: true });

const MOBILE_URL = 'http://localhost:5200';
const WEB_URL    = 'http://localhost:8000';

const feitos   = [];
const falhas   = [];

async function shot(page, nome, descricao) {
  const arquivo = path.join(IMG_DIR, nome);
  await page.screenshot({ path: arquivo, fullPage: false });
  feitos.push({ nome, descricao });
  console.log(`  ok  ${nome} — ${descricao}`);
}

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

/** Gera token de reset via artisan tinker e devolve { token, email } */
function gerarTokenReset(email) {
  try {
    const saida = execSync(
      `php artisan tinker --execute="` +
      `use Illuminate\\\\Support\\\\Facades\\\\Password;` +
      `use App\\\\Models\\\\User;` +
      `\\$u = User::where('email','${email}')->first();` +
      `if(!\\$u){ echo 'SEM_USER'; } else { echo Password::createToken(\\$u); }"`,
      { cwd: path.join(__dirname, '..'), encoding: 'utf-8', timeout: 30000 }
    ).trim();
    const token = saida.split('\n').pop().trim();
    return token && token !== 'SEM_USER' ? { token, email } : null;
  } catch {
    return null;
  }
}

(async () => {
  const browser = await chromium.launch({ headless: true });

  // ─────────────────────────────────────────────────
  // MOBILE — viewport iPhone 14
  // ─────────────────────────────────────────────────
  console.log('\n=== APP MOBILE (iPhone 14 390×844) ===');
  const ctxMobile = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 3,
    isMobile: true,
  });
  const mob = await ctxMobile.newPage();

  try {
    // 1. Tela de login com link "Esqueci minha senha"
    await mob.goto(`${MOBILE_URL}/login`, { waitUntil: 'networkidle', timeout: 15000 });
    await sleep(1500);
    await shot(mob, '01-mobile-login-com-link-esqueci.png',
      'Tela de login do app mostrando o link "Esqueci minha senha" abaixo do botão Entrar');

    // 2. Tela "Esqueci minha senha" — clicar no link
    const linkEsqueci = mob.locator('a, button, [role="button"]').filter({ hasText: /esqueci/i }).first();
    if (await linkEsqueci.isVisible().catch(() => false)) {
      await linkEsqueci.click();
      await sleep(1500);
    } else {
      // Navegar direto se link não encontrado
      console.log('  ! Link "Esqueci" não visível na tela de login — navegando direto para /forgot-password');
      await mob.goto(`${MOBILE_URL}/forgot-password`, { waitUntil: 'networkidle', timeout: 15000 });
      await sleep(1000);
      falhas.push('Caminho 1→2: link "Esqueci minha senha" não encontrado por clique; rota aberta diretamente');
    }
    await shot(mob, '02-mobile-tela-esqueci-senha.png',
      'Tela "Esqueci minha senha" com campo de e-mail e botão Enviar instruções');

    // 3. Email vazio + tentar enviar — erro inline
    const btnEnviar = mob.locator('button[type="submit"], button').filter({ hasText: /enviar/i }).first();
    if (await btnEnviar.isVisible().catch(() => false)) {
      await btnEnviar.click();
      await sleep(1200);
    }
    await shot(mob, '03-mobile-erro-email-vazio.png',
      'Erro exibido ao tentar enviar sem preencher o e-mail');

    // 4. Email preenchido + mensagem de confirmação
    const campoEmail = mob.locator('input[type="email"], input[name="email"]').first();
    if (await campoEmail.isVisible().catch(() => false)) {
      await campoEmail.fill('carlos@laboratorio.com');
      await sleep(400);
      if (await btnEnviar.isVisible().catch(() => false)) {
        await btnEnviar.click();
        await sleep(2500);
      }
    }
    await shot(mob, '04-mobile-confirmacao-enviado.png',
      'Mensagem genérica de confirmação: "Se este e-mail estiver cadastrado, você vai receber..."');

  } catch (err) {
    console.error('  ERRO no mobile:', err.message);
    falhas.push('Bloco mobile encerrou com erro: ' + err.message);
    // Tenta tirar print do estado atual
    await shot(mob, '0x-mobile-estado-erro.png', 'Estado da tela no momento do erro (mobile)').catch(() => {});
  } finally {
    await ctxMobile.close();
  }

  // ─────────────────────────────────────────────────
  // WEB — viewport desktop 1280×800
  // ─────────────────────────────────────────────────
  console.log('\n=== WEB DO GERENTE (desktop 1280×800) ===');
  const ctxWeb = await browser.newContext({
    viewport: { width: 1280, height: 800 },
  });
  const web = await ctxWeb.newPage();

  try {
    // 5. Tela de login web
    await web.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 15000 });
    await sleep(1000);
    await shot(web, '05-web-login-design-system.png',
      'Tela de login web do gerente com card centralizado, fonte Inter e link "Esqueci minha senha"');

    // 6. Login com credenciais erradas — erro inline
    const inputEmail = web.locator('input[name="email"]').first();
    const inputSenha = web.locator('input[name="password"]').first();
    const btnLogin   = web.locator('button[type="submit"]').first();

    if (await inputEmail.isVisible().catch(() => false)) {
      await inputEmail.fill('gerente@teste.local');
      await inputSenha.fill('senha-errada-123');
      await btnLogin.click();
      await sleep(2000);
    }
    await shot(web, '06-web-login-credenciais-erradas.png',
      'Mensagem de erro em vermelho ao digitar senha errada na tela de login web');

    // 7. Tela "Esqueci minha senha" web
    // Voltar ao login e clicar no link
    await web.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 15000 });
    await sleep(800);
    const linkEsqueciWeb = web.locator('a').filter({ hasText: /esqueci/i }).first();
    if (await linkEsqueciWeb.isVisible().catch(() => false)) {
      await linkEsqueciWeb.click();
      await sleep(1500);
    } else {
      console.log('  ! Link "Esqueci" não visível na tela de login web — navegando direto');
      await web.goto(`${WEB_URL}/auth/forgot-password`, { waitUntil: 'networkidle', timeout: 15000 });
      await sleep(1000);
      falhas.push('Caminho 5→7: link "Esqueci minha senha" não clicável na tela de login web; rota aberta diretamente');
    }
    await shot(web, '07-web-tela-esqueci-senha.png',
      'Tela "Esqueci minha senha" na versão web com campo de e-mail');

    // 8. Tela "Definir nova senha" com token válido
    const tokenInfo = gerarTokenReset('gerente@teste.local');
    if (tokenInfo) {
      const url = `${WEB_URL}/auth/reset-password/${tokenInfo.token}?email=${encodeURIComponent(tokenInfo.email)}`;
      await web.goto(url, { waitUntil: 'networkidle', timeout: 15000 });
      await sleep(1200);
      await shot(web, '08-web-definir-nova-senha.png',
        'Tela "Definir nova senha" com campos Nova senha e Confirme, botão Salvar e regras de validação');
    } else {
      console.log('  ! Não foi possível gerar token via artisan tinker');
      falhas.push('Caminho 8: token de reset não gerado (tinker falhou) — print não disponível');
    }

  } catch (err) {
    console.error('  ERRO no web:', err.message);
    falhas.push('Bloco web encerrou com erro: ' + err.message);
    await shot(web, '0y-web-estado-erro.png', 'Estado da tela no momento do erro (web)').catch(() => {});
  } finally {
    await ctxWeb.close();
  }

  await browser.close();

  // ─────────────────────────────────────────────────
  // Resumo
  // ─────────────────────────────────────────────────
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
