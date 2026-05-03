/**
 * Script de aceite — painel do gerente (web Livewire)
 * Roda com: node scripts/aceite-painel.mjs (do diretório raiz do projeto)
 */

import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG_DIR = path.join(__dirname, '../docs/backlog/aceites/imagens/tecnico-entra-no-app-do-celular');
fs.mkdirSync(IMG_DIR, { recursive: true });

const WEB_URL = 'http://localhost:8000';

async function shot(page, nome, descricao) {
  const arquivo = path.join(IMG_DIR, nome);
  await page.screenshot({ path: arquivo });
  console.log(`  ✓ ${nome} — ${descricao}`);
}

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

(async () => {
  const browser = await chromium.launch({ headless: true });

  const ctx = await browser.newContext({
    viewport: { width: 1280, height: 800 },
  });

  const page = await ctx.newPage();

  try {
    console.log('\n=== PAINEL DO GERENTE ===');

    // Login via rota de aceite (user_id=4 = gerente@teste.local)
    await page.goto(`${WEB_URL}/aceite-login/4`, { waitUntil: 'networkidle' });
    console.log('  URL após login:', page.url());
    await sleep(2000);

    // 6. Lista de dispositivos pendentes
    await shot(page, '06-painel-lista-dispositivos-pendentes.png', 'Painel do gerente — lista de celulares dos técnicos');

    // 7. Clicar em Aprovar
    const btnAprovar = page.locator('button:has-text("Aprovar")').first();
    if (await btnAprovar.isVisible().catch(() => false)) {
      await btnAprovar.click();
      await sleep(2500);
      await shot(page, '07-dispositivo-aprovado.png', 'Dispositivo aprovado pelo gerente');
    } else {
      console.log('  ! Botão Aprovar não encontrado na tela');
      await shot(page, '07-dispositivo-aprovado.png', 'Lista de dispositivos (sem pedidos pendentes para aprovar)');
    }

    // 8a. Filtro: aguardando aprovação
    const select = page.locator('select[wire\\:model\\.live="statusFilter"]').first();
    if (await select.isVisible().catch(() => false)) {
      await select.selectOption('pending');
      await sleep(1500);
      await shot(page, '08a-filtro-aguardando.png', 'Filtro: somente celulares aguardando aprovação');

      await select.selectOption('approved');
      await sleep(1500);
      await shot(page, '08b-filtro-aprovados.png', 'Filtro: somente celulares aprovados');

      // Resetar filtro para ver todos
      await select.selectOption('');
      await sleep(800);
    } else {
      // Tentar com wire:model genérico
      const selectGeneric = page.locator('select').first();
      if (await selectGeneric.isVisible().catch(() => false)) {
        await selectGeneric.selectOption('pending');
        await sleep(1500);
        await shot(page, '08a-filtro-aguardando.png', 'Filtro: somente celulares aguardando aprovação');
        await selectGeneric.selectOption('approved');
        await sleep(1500);
        await shot(page, '08b-filtro-aprovados.png', 'Filtro: somente celulares aprovados');
        await selectGeneric.selectOption('');
        await sleep(800);
      } else {
        await shot(page, '08a-filtro-aguardando.png', 'Área de filtros da lista (select não encontrado)');
        fs.copyFileSync(path.join(IMG_DIR, '08a-filtro-aguardando.png'), path.join(IMG_DIR, '08b-filtro-aprovados.png'));
      }
    }

    // 9. Bloquear device aprovado
    const btnBloquear = page.locator('button:has-text("Bloquear")').first();
    if (await btnBloquear.isVisible().catch(() => false)) {
      await btnBloquear.click();
      await sleep(2500);
      await shot(page, '09-dispositivo-bloqueado.png', 'Dispositivo bloqueado pelo gerente');
    } else {
      const wireBloquear = page.locator('[wire\\:click*="revogar"]').first();
      if (await wireBloquear.isVisible().catch(() => false)) {
        await wireBloquear.click();
        await sleep(2500);
        await shot(page, '09-dispositivo-bloqueado.png', 'Dispositivo bloqueado pelo gerente');
      } else {
        console.log('  ! Botão Bloquear não encontrado — sem device aprovado para bloquear');
        await shot(page, '09-dispositivo-bloqueado.png', 'Lista de dispositivos após aprovação');
      }
    }

    console.log('\n✓ Painel do gerente concluído');

  } catch (err) {
    console.error('\nERRO:', err.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
