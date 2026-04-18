// Testes RED para AC-005 + AC-006-A (slice 018)
// scripts/validate-gate-output.sh — enum lido do schema, nao hardcoded
// Execucao: node --test tests/scripts/validate-gate-output.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'validate-gate-output.sh');
const FIX = path.join(REPO_ROOT, 'tests', 'fixtures', 'gate-output');
const SCHEMA = path.join(REPO_ROOT, 'docs', 'protocol', 'schemas', 'gate-output.schema.json');

function run(file) {
  return spawnSync('bash', [SCRIPT, file], { encoding: 'utf8' });
}

test('AC-005: scripts/validate-gate-output.sh existe', () => {
  assert.ok(fs.existsSync(SCRIPT), 'validate-gate-output.sh deve existir');
});

test('AC-005: schema canonico gate-output.schema.json existe (pre-condicao)', () => {
  assert.ok(fs.existsSync(SCHEMA), 'gate-output.schema.json deve existir em docs/protocol/schemas/');
});

test('AC-005: valid-verify.json passa validacao (exit 0)', () => {
  const r = run(path.join(FIX, 'valid-verify.json'));
  assert.equal(r.status, 0, `exit 0 esperado para JSON valido. stderr: ${r.stderr}`);
});

test('AC-006-A: invalid-schema-url.json rejeitado apontando campo $schema', () => {
  const r = run(path.join(FIX, 'invalid-schema-url.json'));
  assert.equal(r.status, 1, 'exit 1 esperado para $schema URL invalido');
  assert.match(
    (r.stdout + r.stderr),
    /\$schema|schema/i,
    'mensagem deve apontar campo $schema violador'
  );
});

test('AC-006-A: invalid-gate-name.json rejeitado apontando campo gate', () => {
  const r = run(path.join(FIX, 'invalid-gate-name.json'));
  assert.equal(r.status, 1, 'exit 1 esperado para gate fora do enum');
  assert.match((r.stdout + r.stderr), /\bgate\b/i, 'mensagem deve apontar campo gate');
  assert.match(
    (r.stdout + r.stderr),
    /security(?!-gate)/,
    'mensagem deve mencionar valor invalido "security" (esperado "security-gate")'
  );
});

test('AC-006-A: invalid-missing-slice.json rejeitado apontando campo slice', () => {
  const r = run(path.join(FIX, 'invalid-missing-slice.json'));
  assert.equal(r.status, 1, 'exit 1 esperado para slice ausente');
  assert.match((r.stdout + r.stderr), /\bslice\b/i, 'mensagem deve apontar campo slice ausente');
});

test('AC-005: validator le enum direto do schema (regressao — nao hardcoded)', () => {
  // Verifica que o script referencia o schema por path
  const content = fs.readFileSync(SCRIPT, 'utf8');
  assert.match(
    content,
    /docs\/protocol\/schemas\/gate-output\.schema\.json/,
    'validate-gate-output.sh deve referenciar o schema canonico (nao enum hardcoded)'
  );
  // Deve usar jq para extrair o enum
  assert.match(content, /\bjq\b/, 'validator deve usar jq para parsear schema');
});
