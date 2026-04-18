// Testes RED para AC-004-A (slice 018)
// scripts/audit-set-difference.sh — 3 listas nomeadas (resolved, unresolved, new)
// Execucao: node --test tests/scripts/audit-set-difference.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'audit-set-difference.sh');
const PREV = path.join(REPO_ROOT, 'tests', 'fixtures', 'set-diff', 'previous-findings.json');
const CURR = path.join(REPO_ROOT, 'tests', 'fixtures', 'set-diff', 'current-findings.json');

function run() {
  return spawnSync('bash', [SCRIPT, '--previous', PREV, '--current', CURR], { encoding: 'utf8' });
}

test('AC-004-A: scripts/audit-set-difference.sh existe', () => {
  assert.ok(fs.existsSync(SCRIPT), 'audit-set-difference.sh deve existir');
});

test('AC-004-A: script retorna exit 0 em inputs validos', () => {
  const r = run();
  assert.equal(r.status, 0, `exit 0 esperado. stderr: ${r.stderr}`);
});

test('AC-004-A: output contem chaves nomeadas resolved/unresolved/new', () => {
  const r = run();
  // Saida pode ser JSON ou YAML ou texto estruturado — exigir pelo menos as 3 chaves
  const out = r.stdout;
  assert.match(out, /resolved/, 'output deve conter secao "resolved"');
  assert.match(out, /unresolved/, 'output deve conter secao "unresolved"');
  assert.match(out, /\bnew\b/, 'output deve conter secao "new"');
});

test('AC-004-A: unresolved contem assertion_missing (mesma categoria+descricao, linha diferente)', () => {
  const r = run();
  // Finding previo VER-019-001 (line 42) e atual VER-019-010 (line 55), ambos "AC-001 sem assertion concreta"
  // Assinatura normalizada (categoria+descricao+path_sem_linha) deve casar → unresolved
  let parsed;
  try { parsed = JSON.parse(r.stdout); } catch { parsed = null; }
  if (parsed) {
    assert.ok(
      (parsed.unresolved || []).some((f) => /assertion_missing|AC-001/i.test(JSON.stringify(f))),
      'unresolved deve conter finding AC-001 assertion_missing'
    );
  } else {
    // Fallback textual
    assert.match(r.stdout, /unresolved[\s\S]*(assertion_missing|AC-001)/, 'unresolved deve listar AC-001');
  }
});

test('AC-004-A: resolved contem test_red_expected (finding VER-019-002)', () => {
  const r = run();
  let parsed; try { parsed = JSON.parse(r.stdout); } catch { parsed = null; }
  if (parsed) {
    assert.ok(
      (parsed.resolved || []).some((f) => /test_red_expected|AC-002/i.test(JSON.stringify(f))),
      'resolved deve conter AC-002 test_red_expected'
    );
  } else {
    assert.match(r.stdout, /resolved[\s\S]*(test_red_expected|AC-002)/, 'resolved deve listar AC-002');
  }
});

test('AC-004-A: new contem mock_misuse (VER-019-011)', () => {
  const r = run();
  let parsed; try { parsed = JSON.parse(r.stdout); } catch { parsed = null; }
  if (parsed) {
    assert.ok(
      (parsed.new || []).some((f) => /mock_misuse|AC-004/i.test(JSON.stringify(f))),
      'new deve conter AC-004 mock_misuse'
    );
  } else {
    assert.match(r.stdout, /\bnew\b[\s\S]*(mock_misuse|AC-004)/, 'new deve listar AC-004');
  }
});

test('AC-004-A: normalizacao remove linha — finding com mesma descricao em linhas diferentes casa', () => {
  // Test de regressao: previous line 42, current line 55 — deve casar em "unresolved"
  const r = run();
  let parsed; try { parsed = JSON.parse(r.stdout); } catch { parsed = null; }
  if (parsed) {
    const unresolved = parsed.unresolved || [];
    assert.ok(unresolved.length >= 1, 'deve haver >=1 unresolved apesar da linha diferente');
  } else {
    assert.match(r.stdout, /unresolved/, 'output deve declarar categoria unresolved nao-vazia');
  }
});
