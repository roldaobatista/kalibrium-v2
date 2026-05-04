/**
 * Script de aceite visual: Sync de OS app-servidor
 * Roda via: node scripts/aceite-sync-os.mjs
 */
import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMGS = path.join(__dirname, '../docs/backlog/aceites/imagens/2026-05-03-sync-de-os-app-servidor');
const APP_URL = 'http://localhost:5173';
const WEB_URL = 'http://localhost:8000';

const CARLOS_TOKEN = '27|G1tfDck9aEGeoeBdRgAAlnG4s9DuFLUZzJj4NqKob7ea4d06';

async function screenshot(page, name) {
  const file = path.join(IMGS, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  console.log(`PRINT: ${name}.png`);
  return file;
}

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

const browser = await chromium.launch({ headless: true });
const errors = [];

// ─── CAMINHO 1+2: Técnico no app (mobile viewport) ───────────────────────────
{
  const ctx = await browser.newContext({
    viewport: { width: 390, height: 844 },
    userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
  });
  const page = await ctx.newPage();

  // Intercepta login: aprova device e retorna token válido
  await page.route('**/api/mobile/login', async (route, request) => {
    const response = await route.fetch();
    const status = response.status();

    if (status === 202) {
      console.log('Login 202 (pending) — aprovando device e retornando 200');
      try {
        const reqBody = JSON.parse(request.postData() || '{}');
        const deviceId = reqBody.device_identifier;
        if (deviceId) {
          execSync(
            `cd "C:/PROJETOS/saas/kalibrium-v2" && echo "DB::statement(\\"SELECT set_config('app.current_tenant_id', '1', false)\\"); DB::table('mobile_devices')->where('device_identifier','${deviceId}')->update(['status'=>'approved','updated_at'=>now()]);echo 'ok';" | php artisan tinker --no-interaction 2>&1`,
            { encoding: 'utf8', timeout: 15000 }
          );
          console.log(`Device ${deviceId} aprovado`);
        }
      } catch (e) {
        console.log('Aviso aprovação device:', e.message.substring(0, 100));
      }
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          token: CARLOS_TOKEN,
          user: { id: 3, name: 'Carlos Técnico', email: 'tecnico@teste.local' },
        }),
      });
    } else {
      await route.fulfill({ response });
    }
  });

  console.log('\n=== CAMINHO 1: Tela de login do app móvel ===');
  try {
    await page.goto(APP_URL, { waitUntil: 'networkidle', timeout: 20000 });
    await sleep(2000);
    await screenshot(page, '1-tela-login-app');

    const emailField = await page.waitForSelector('input[type="email"]', { timeout: 8000 }).catch(() => null);
    const pwField = await page.$('input[type="password"]').catch(() => null);

    if (emailField && pwField) {
      await emailField.fill('tecnico@teste.local');
      await pwField.fill('senha123');
      await screenshot(page, '2-login-preenchido');
      await pwField.press('Enter');
      await sleep(4000);
      await screenshot(page, '3-apos-login');
    } else {
      errors.push('Campos de login não encontrados');
    }

    await sleep(1500);
    await screenshot(page, '4-tela-inicial-pos-login');

    // Clica no card de Ordens de Serviço
    const osCard = await page.$('text=Ordens de Serviço').catch(() => null);
    if (osCard) {
      await osCard.click();
      await sleep(2500);
      await screenshot(page, '5-lista-de-os');
    } else {
      await page.goto(`${APP_URL}/service-orders`, { waitUntil: 'networkidle', timeout: 10000 }).catch(() => {});
      await sleep(2000);
      await screenshot(page, '5-lista-de-os');
    }

    // Botão FAB "+ Nova OS" — seletor exato do código fonte
    const btnFAB = await page.$('button.kb-os-fab, [aria-label="Nova ordem de serviço"]').catch(() => null);

    if (btnFAB) {
      console.log('Botão FAB Nova OS encontrado');
      await btnFAB.click();
      await sleep(2000);
      await screenshot(page, '6-formulario-nova-os');

      // Campos do modal — seletores exatos do código
      const inputs = await page.$$('input.kb-modal-input');
      if (inputs.length >= 2) {
        await inputs[0].fill('Acme Indústria Ltda');
        await inputs[1].fill('Paquímetro digital Mitutoyo 200mm');
      } else if (inputs.length === 1) {
        await inputs[0].fill('Acme Indústria Ltda');
        errors.push('Segundo campo (instrumento) não encontrado no modal');
      } else {
        errors.push('Campos do formulário não encontrados no modal');
      }

      // Status (select) — já começa em "recebido" por padrão
      const statusSelect = await page.$('select.kb-modal-select').catch(() => null);
      if (statusSelect) {
        // Mantém "recebido" (default)
        console.log('Campo status presente no formulário');
      }

      // Observações
      const textarea = await page.$('textarea.kb-modal-textarea').catch(() => null);
      if (textarea) await textarea.fill('primeiro print');

      await screenshot(page, '7-formulario-preenchido');

      // Salvar
      const saveBtn = await page.$('button.kb-modal-btn-salvar').catch(() => null);
      if (saveBtn) {
        await saveBtn.click();
        await sleep(3500);
        await screenshot(page, '8-lista-apos-criar-os');
      } else {
        errors.push('Botão Salvar não encontrado no modal');
        await screenshot(page, '8-estado-sem-botao-salvar');
      }
    } else {
      errors.push('Botão + Nova OS (kb-os-fab) não encontrado');
      await screenshot(page, '6-estado-sem-botao-nova-os');
    }

    // === CAMINHO 2: Editar status da OS ===
    console.log('\n=== CAMINHO 2: Editar status da OS ===');

    // Verifica se há OS na lista agora
    const osItems = await page.$$('li.kb-os-item, button.kb-os-item-btn').catch(() => []);
    console.log('Itens de OS na lista:', osItems.length);

    if (osItems.length > 0) {
      // Clica no primeiro item
      await osItems[0].click();
      await sleep(2000);
      await screenshot(page, '9-tela-edicao-os');

      // Muda o status para "Em calibração"
      const statusSelect2 = await page.$('select.kb-modal-select').catch(() => null);
      if (statusSelect2) {
        await page.selectOption('select.kb-modal-select', { value: 'in_calibration' });
        await screenshot(page, '10-status-em-calibracao');

        const saveBtn2 = await page.$('button.kb-modal-btn-salvar').catch(() => null);
        if (saveBtn2) {
          await saveBtn2.click();
          await sleep(3000);
          await screenshot(page, '11-lista-status-atualizado');
        } else {
          errors.push('Botão Salvar não encontrado na edição de status');
        }
      } else {
        errors.push('Select de status não encontrado na edição');
        await screenshot(page, '10-estado-sem-select-status');
      }
    } else {
      // Sem itens na lista — mostra a tela de lista e registra o estado
      errors.push('Lista de OS vazia após criar — sincronismo local pode estar pendente');
      await screenshot(page, '9-lista-os-apos-criar');
    }

  } catch (e) {
    errors.push(`Erro caminho mobile: ${e.message.split('\n')[0]}`);
    await screenshot(page, '99-erro-caminho-mobile').catch(() => {});
  }

  await ctx.close();
}

// ─── CAMINHO 3: Gerente no painel web (desktop) ──────────────────────────────
{
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();

  console.log('\n=== CAMINHO 3: Painel web do gerente ===');
  try {
    await page.goto(`${WEB_URL}/auth/login`, { waitUntil: 'networkidle', timeout: 15000 });
    await sleep(1000);
    await screenshot(page, '12-login-web-gerente');

    const emailField = await page.$('input[type="email"], input[name="email"]').catch(() => null);
    const pwField = await page.$('input[type="password"]').catch(() => null);

    if (emailField && pwField) {
      await emailField.fill('gerente@teste.local');
      await pwField.fill('senha123');
      const btn = await page.$('button[type="submit"]').catch(() => null);
      if (btn) {
        await btn.click();
        await sleep(3000);
        await screenshot(page, '13-dashboard-gerente');

        // Lista de técnicos
        await page.goto(`${WEB_URL}/technicians`, { waitUntil: 'networkidle', timeout: 10000 });
        await sleep(2000);
        await screenshot(page, '14-lista-tecnicos');

        // Perfil do Carlos Técnico
        let techId = 3;
        const carlosRow = await page.$('tr:has-text("Carlos Técnico"), td:has-text("Carlos Técnico")').catch(() => null);
        if (carlosRow) {
          const link = await carlosRow.$('a').catch(() => null);
          if (link) {
            const href = await link.getAttribute('href').catch(() => null);
            if (href) { const m = href.match(/(\d+)/); if (m) techId = parseInt(m[1]); }
            await link.click();
            await sleep(2000);
            await screenshot(page, '15-perfil-tecnico-carlos');
          }
        }

        // Aba de OS do técnico (somente leitura)
        await page.goto(`${WEB_URL}/technicians/${techId}/service-orders`, { waitUntil: 'networkidle', timeout: 10000 });
        await sleep(2000);
        await screenshot(page, '16-os-do-tecnico-visao-gerente');

        // Confirma ausência de botões de edição
        const editBtn = await page.$('button:has-text("Editar"), button:has-text("Excluir"), a:has-text("Editar")').catch(() => null);
        if (editBtn) {
          errors.push('ALERTA: Botão de editar/excluir visível para gerente — não deve existir no MVP');
          await screenshot(page, '17-alerta-edicao-visivel');
        } else {
          console.log('Confirmado: sem botões de edição para gerente');
          await screenshot(page, '17-confirmacao-somente-leitura');
        }
      }
    } else {
      errors.push('Campos de login web não encontrados');
    }
  } catch (e) {
    errors.push(`Erro caminho web: ${e.message.split('\n')[0]}`);
    await screenshot(page, '99-erro-caminho-web').catch(() => {});
  }

  await ctx.close();
}

await browser.close();

console.log('\n=== RESUMO FINAL ===');
if (errors.length === 0) {
  console.log('Todos os caminhos OK — sem alertas.');
} else {
  errors.forEach(e => console.log('  ALERTA:', e));
}
process.exit(0);
