// Testes RED para AC-003 + AC-003-A (slice 018)
// scripts/validate-audit-prompt.sh
// Execucao: node --test tests/scripts/validate-audit-prompt.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'validate-audit-prompt.sh');
const TEMPLATE = path.join(REPO_ROOT, 'docs', 'protocol', 'audit-prompt-template.md');
const BLOCKED = path.join(REPO_ROOT, 'docs', 'protocol', 'blocked-tokens-re-audit.txt');
const FIX = path.join(REPO_ROOT, 'tests', 'fixtures', 'audit-prompts');

function run(mode, file) {
  return spawnSync('bash', [SCRIPT, `--mode=${mode}`, file], { encoding: 'utf8' });
}

test('AC-003: scripts/validate-audit-prompt.sh existe', () => {
  assert.ok(fs.existsSync(SCRIPT), 'validate-audit-prompt.sh deve existir');
});

test('AC-003: docs/protocol/audit-prompt-template.md existe', () => {
  assert.ok(fs.existsSync(TEMPLATE), 'audit-prompt-template.md deve existir');
});

test('AC-003: template contem os 6 campos obrigatorios', () => {
  const t = fs.readFileSync(TEMPLATE, 'utf8');
  for (const field of ['story_id', 'slice_id', 'mode', 'perimeter_files', 'criteria_checklist', 'output_contract']) {
    assert.match(t, new RegExp(field), `Template deve conter campo obrigatorio "${field}"`);
  }
});

test('AC-003: --mode=1st-pass aceita fixture valida (exit 0)', () => {
  const r = run('1st-pass', path.join(FIX, '1st-pass-valid.md'));
  assert.equal(r.status, 0, `exit code 0 esperado. stderr: ${r.stderr}`);
});

test('AC-003: --mode=1st-pass rejeita fixture sem output_contract (exit 1 apontando campo)', () => {
  const r = run('1st-pass', path.join(FIX, '1st-pass-invalid-missing-output-contract.md'));
  assert.equal(r.status, 1, 'exit code 1 esperado para prompt invalido');
  assert.match(
    (r.stdout + r.stderr).toLowerCase(),
    /output_contract/,
    'mensagem deve apontar campo violador (output_contract)'
  );
});

test('AC-003-A: docs/protocol/blocked-tokens-re-audit.txt existe', () => {
  assert.ok(fs.existsSync(BLOCKED), 'blocked-tokens-re-audit.txt deve existir');
});

test('AC-003-A: blocked-tokens contem os tokens obrigatorios da spec', () => {
  const content = fs.readFileSync(BLOCKED, 'utf8').toLowerCase();
  const required = [
    'finding anterior',
    'previously found',
    'foi corrigido',
    'fix applied',
    'fixer',
    're-audit',
    'rodada anterior'
  ];
  for (const tok of required) {
    assert.match(content, new RegExp(tok.replace(/\s+/g, '\\s+')), `Token "${tok}" deve constar no blocked-tokens`);
  }
});

test('AC-003-A: --mode=re-audit aceita fixture limpa (exit 0)', () => {
  const r = run('re-audit', path.join(FIX, 're-audit-clean.md'));
  assert.equal(r.status, 0, `exit 0 esperado em fixture limpa. stderr: ${r.stderr}`);
});

test('AC-003-A: --mode=re-audit rejeita fixture com "foi corrigido" (exit 1, reporta linha+token)', () => {
  const r = run('re-audit', path.join(FIX, 're-audit-contaminated-token-fix.md'));
  assert.equal(r.status, 1, 'exit 1 esperado para prompt contaminado');
  const out = (r.stdout + r.stderr).toLowerCase();
  assert.match(out, /foi corrigido/, 'deve reportar o token detectado');
  assert.match(out, /linha|line|:\d+/i, 'deve reportar numero da linha contaminada');
});

test('AC-003-A: --mode=re-audit rejeita finding ID previo (VER-019-NNN)', () => {
  const r = run('re-audit', path.join(FIX, 're-audit-contaminated-finding-id.md'));
  assert.equal(r.status, 1, 'exit 1 esperado para finding ID previo');
  assert.match((r.stdout + r.stderr), /VER-019-003|finding[-_ ]id/i, 'deve apontar o ID encontrado');
});

test('AC-003-A: --mode=re-audit rejeita commit hash adjacente a palavra "fix"', () => {
  const r = run('re-audit', path.join(FIX, 're-audit-contaminated-commit-hash.md'));
  assert.equal(r.status, 1, 'exit 1 esperado para commit hash de fix');
  assert.match((r.stdout + r.stderr).toLowerCase(), /commit|hash|fix/, 'deve apontar commit hash detectado');
});
