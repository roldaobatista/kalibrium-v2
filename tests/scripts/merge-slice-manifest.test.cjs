// Testes RED para AC-006 + AC-006-A (slice 018)
// Manifesto de atualizacao de merge-slice.sh (selado) + integracao validator
// Execucao: node --test tests/scripts/merge-slice-manifest.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const MANIFEST = path.join(REPO_ROOT, 'specs', '018', 'merge-slice-update-manifest.md');

test('AC-006: specs/018/merge-slice-update-manifest.md existe', () => {
  assert.ok(fs.existsSync(MANIFEST), 'merge-slice-update-manifest.md deve existir');
});

test('AC-006: manifesto documenta invocacao de validate-gate-output.sh como pre-check', () => {
  const c = fs.readFileSync(MANIFEST, 'utf8');
  assert.match(c, /validate-gate-output\.sh/, 'manifesto deve referenciar validate-gate-output.sh');
  assert.match(c, /pre-?check|antes|pre[- ]valida[çc]/i, 'manifesto deve descrever uso como pre-check');
});

test('AC-006: manifesto declara mapeamento de aliases legacy canonicos', () => {
  const c = fs.readFileSync(MANIFEST, 'utf8');
  assert.match(c, /code-review[\s\S]*review/, 'manifesto deve mapear code-review -> review');
  assert.match(c, /"security"[\s\S]*security-gate|security\s*->\s*security-gate/, 'manifesto deve mapear security -> security-gate');
  assert.match(c, /functional[\s\S]*functional-gate/, 'manifesto deve mapear functional -> functional-gate');
});

test('AC-006: manifesto instrui aplicacao via relock-harness.sh pelo PM', () => {
  const c = fs.readFileSync(MANIFEST, 'utf8');
  assert.match(c, /relock-harness\.sh/, 'manifesto deve citar relock-harness.sh');
  assert.match(c, /KALIB_RELOCK_AUTHORIZED=1/, 'manifesto deve conter flag de autorizacao');
  assert.match(c, /PM|terminal externo/i, 'manifesto deve indicar que aplicacao e pelo PM em terminal externo');
});

test('AC-006: manifesto declara explicitamente que slice 018 NAO edita merge-slice.sh', () => {
  const c = fs.readFileSync(MANIFEST, 'utf8');
  assert.match(
    c,
    /n[aã]o\s+edita|not\s+editing|selad[oa]/i,
    'manifesto deve deixar claro que merge-slice.sh e selado e nao e editado neste slice'
  );
});

test('AC-006-A: manifesto referencia as 3 violacoes cobertas por fixtures ($schema, gate, slice)', () => {
  const c = fs.readFileSync(MANIFEST, 'utf8');
  // Deve documentar o comportamento do validator invocado pelo merge
  assert.match(c, /\$schema/, 'manifesto deve citar violacao $schema');
  assert.match(c, /\bgate\b/, 'manifesto deve citar violacao de gate');
  assert.match(c, /\bslice\b/, 'manifesto deve citar violacao de slice');
});
