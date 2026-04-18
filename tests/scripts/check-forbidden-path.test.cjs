// Testes RED para AC-007-A (slice 018)
// scripts/check-forbidden-path.sh + docs/protocol/forbidden-paths.txt
// Execucao: node --test tests/scripts/check-forbidden-path.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'check-forbidden-path.sh');
const LIST = path.join(REPO_ROOT, 'docs', 'protocol', 'forbidden-paths.txt');

function run(arg) {
  return spawnSync('bash', [SCRIPT, arg], { encoding: 'utf8' });
}

test('AC-007-A: scripts/check-forbidden-path.sh existe', () => {
  assert.ok(fs.existsSync(SCRIPT), 'check-forbidden-path.sh deve existir');
});

test('AC-007-A: docs/protocol/forbidden-paths.txt existe e contem 4 prefixos', () => {
  assert.ok(fs.existsSync(LIST), 'forbidden-paths.txt deve existir');
  const c = fs.readFileSync(LIST, 'utf8');
  for (const p of ['frontend/', 'backend/', 'mobile/', 'apps/']) {
    assert.match(c, new RegExp(p.replace('/', '\\/')), `forbidden-paths.txt deve conter prefixo "${p}"`);
  }
});

test('AC-007-A: "frontend/foo.ts" retorna exit 1 com mensagem ContractViolation', () => {
  const r = run('frontend/foo.ts');
  assert.equal(r.status, 1, 'exit 1 esperado para path proibido');
  assert.match((r.stdout + r.stderr), /ContractViolation/, 'mensagem deve conter "ContractViolation"');
  assert.match(
    (r.stdout + r.stderr),
    /docs\/protocol\/forbidden-paths\.txt/,
    'mensagem deve referenciar docs/protocol/forbidden-paths.txt'
  );
  assert.match((r.stdout + r.stderr), /frontend\//, 'mensagem deve ecoar o path violador');
});

test('AC-007-A: "backend/foo.php" retorna exit 1', () => {
  const r = run('backend/foo.php');
  assert.equal(r.status, 1);
  assert.match((r.stdout + r.stderr), /ContractViolation/);
});

test('AC-007-A: "mobile/app.ts" retorna exit 1', () => {
  const r = run('mobile/app.ts');
  assert.equal(r.status, 1);
});

test('AC-007-A: "apps/x/y.ts" retorna exit 1', () => {
  const r = run('apps/x/y.ts');
  assert.equal(r.status, 1);
});

test('AC-007-A: "src/main.tsx" retorna exit 0 (path valido)', () => {
  const r = run('src/main.tsx');
  assert.equal(r.status, 0, `exit 0 esperado para path valido. stderr: ${r.stderr}`);
});

test('AC-007-A: "tests/e2e/foo.spec.ts" retorna exit 0', () => {
  const r = run('tests/e2e/foo.spec.ts');
  assert.equal(r.status, 0);
});

test('AC-007-A: "docs/protocol/00-protocolo-operacional.md" retorna exit 0', () => {
  const r = run('docs/protocol/00-protocolo-operacional.md');
  assert.equal(r.status, 0);
});
