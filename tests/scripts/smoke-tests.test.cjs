// Testes RED para AC-002 (slice 018) — smoke runner + pre-push integration
// scripts/smoke-tests.sh + scripts/pre-push
// Execucao: node --test tests/scripts/smoke-tests.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SMOKE = path.join(REPO_ROOT, 'scripts', 'smoke-tests.sh');
const PRE_PUSH = path.join(REPO_ROOT, 'scripts', 'pre-push');

test('AC-002: scripts/smoke-tests.sh existe', () => {
  assert.ok(fs.existsSync(SMOKE), 'scripts/smoke-tests.sh deve existir');
});

test('AC-002: smoke-tests.sh invoca playwright com filtro @smoke', () => {
  const content = fs.readFileSync(SMOKE, 'utf8');
  assert.match(content, /^#!.*bash/, 'Shebang bash obrigatorio');
  assert.match(content, /playwright\s+test[\s\S]*@smoke/, 'Deve invocar playwright test filtrando tag @smoke');
});

test('AC-002: smoke-tests.sh usa set -euo pipefail', () => {
  const content = fs.readFileSync(SMOKE, 'utf8');
  assert.match(content, /set\s+-euo\s+pipefail/, 'Script deve ter set -euo pipefail');
});

test('AC-002: scripts/pre-push invoca detect-shared-file-change.sh', () => {
  assert.ok(fs.existsSync(PRE_PUSH), 'scripts/pre-push deve existir');
  const content = fs.readFileSync(PRE_PUSH, 'utf8');
  assert.match(
    content,
    /detect-shared-file-change\.sh/,
    'pre-push deve invocar scripts/detect-shared-file-change.sh'
  );
});

test('AC-002: pre-push invoca smoke-tests.sh quando shared_changed=true', () => {
  const content = fs.readFileSync(PRE_PUSH, 'utf8');
  assert.match(content, /smoke-tests\.sh/, 'pre-push deve invocar scripts/smoke-tests.sh');
  // Deve existir logica condicional shared_changed=true
  assert.match(
    content,
    /shared_changed=true/,
    'pre-push deve ter branch condicional verificando shared_changed=true'
  );
});

test('AC-002: existe ao menos 1 teste tageado @smoke em tests/e2e/', () => {
  const e2eDir = path.join(REPO_ROOT, 'tests', 'e2e');
  if (!fs.existsSync(e2eDir)) {
    assert.fail('tests/e2e/ deve existir');
  }
  const files = fs.readdirSync(e2eDir).filter((f) => f.endsWith('.spec.ts'));
  assert.ok(files.length > 0, 'Deve haver specs em tests/e2e/');
  const anyTagged = files.some((f) => {
    const c = fs.readFileSync(path.join(e2eDir, f), 'utf8');
    return /tag:\s*\[[^\]]*['"]@smoke['"]/.test(c) || /@smoke/.test(c);
  });
  assert.ok(anyTagged, 'Deve haver pelo menos 1 teste tageado @smoke em tests/e2e/');
});
