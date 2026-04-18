// Testes RED para AC-001 + AC-001-A (slice 018)
// Workflow .github/workflows/test-regression.yml
// Execucao: node --test tests/ci/test-regression-workflow.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const WORKFLOW = path.join(REPO_ROOT, '.github', 'workflows', 'test-regression.yml');

function readWorkflow() {
  return fs.readFileSync(WORKFLOW, 'utf8');
}

test('AC-001: workflow test-regression.yml existe em .github/workflows/', () => {
  assert.ok(
    fs.existsSync(WORKFLOW),
    'Arquivo .github/workflows/test-regression.yml nao existe — esperado ser criado pelo slice 018'
  );
});

test('AC-001: workflow dispara em pull_request e push', () => {
  const yml = readWorkflow();
  assert.match(yml, /on:\s*[\s\S]*pull_request/, 'Workflow deve ter trigger pull_request');
  assert.match(yml, /on:\s*[\s\S]*push/, 'Workflow deve ter trigger push');
});

test('AC-001: workflow executa npm run test:scaffold', () => {
  const yml = readWorkflow();
  assert.match(yml, /npm run test:scaffold/, 'Workflow deve invocar npm run test:scaffold');
});

test('AC-001: workflow executa npx playwright test', () => {
  const yml = readWorkflow();
  assert.match(yml, /npx playwright test/, 'Workflow deve invocar npx playwright test');
});

test('AC-001: workflow cobre projects dev-chromium e chromium preview', () => {
  const yml = readWorkflow();
  // Pode estar como matrix ou invocacao direta com --project
  const hasDev = /dev-chromium/.test(yml);
  const hasPreview = /chromium[\s\S-]*preview|preview[\s\S-]*chromium/.test(yml);
  assert.ok(hasDev, 'Workflow deve cobrir project dev-chromium');
  assert.ok(hasPreview, 'Workflow deve cobrir project chromium preview');
});

test('AC-001-A: workflow falha explicitando AC violado (nao apenas exit code)', () => {
  const yml = readWorkflow();
  // Deve ter um step que capture/publique o output com AC
  // Ex.: continue-on-error + step subsequente que lista testes falhos, ou uso de --reporter=list
  const hasReporter = /--reporter[=\s](list|line|github)/.test(yml) || /reporter:\s*(list|line|github)/.test(yml);
  assert.ok(
    hasReporter,
    'Workflow deve usar reporter que expoe nome do AC violado (list|line|github)'
  );
});

test('AC-001-A: workflow nomeado de forma a aparecer como check no PR', () => {
  const yml = readWorkflow();
  assert.match(yml, /^name:\s*.+/m, 'Workflow deve ter nome declarado (aparece como check no PR)');
});
