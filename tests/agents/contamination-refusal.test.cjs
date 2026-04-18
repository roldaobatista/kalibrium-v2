// Testes RED para AC-004 (slice 018)
// Agent files devem instruir recusa com rejection_reason="contaminated_prompt"
// Execucao: node --test tests/agents/contamination-refusal.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const AGENTS_DIR = path.join(REPO_ROOT, '.claude', 'agents');
const FIX_JSON = path.join(REPO_ROOT, 'tests', 'fixtures', 'gate-output', 'rejection-contaminated-prompt.json');

// Agentes que emitem JSON de gate (ADR-0017, spec slice 018 AC-004)
const GATE_AGENTS = [
  'qa-expert.md',
  'architecture-expert.md',
  'security-expert.md',
  'product-expert.md',
  'governance.md'
];

for (const agent of GATE_AGENTS) {
  test(`AC-004: ${agent} possui instrucao de recusa por contaminacao`, () => {
    const file = path.join(AGENTS_DIR, agent);
    assert.ok(fs.existsSync(file), `${agent} deve existir`);
    const c = fs.readFileSync(file, 'utf8');
    assert.match(
      c,
      /contaminated_prompt/,
      `${agent} deve mencionar rejection_reason "contaminated_prompt"`
    );
    assert.match(
      c,
      /contamination_evidence/,
      `${agent} deve mencionar campo contamination_evidence`
    );
    // Deve indicar que NAO preenche ac_coverage_map nem checks
    assert.match(
      c,
      /ac_coverage_map|aborta[rn]?[^\n]*investig/i,
      `${agent} deve indicar que aborta investigacao (nao preenche ac_coverage_map)`
    );
  });
}

test('AC-004: fixture de rejeicao por contaminacao nao contem evidence.ac_coverage_map', () => {
  assert.ok(fs.existsSync(FIX_JSON), 'fixture rejection-contaminated-prompt.json deve existir');
  const j = JSON.parse(fs.readFileSync(FIX_JSON, 'utf8'));
  assert.equal(j.verdict, 'rejected');
  assert.equal(j.rejection_reason, 'contaminated_prompt');
  assert.ok(typeof j.contamination_evidence === 'string' && j.contamination_evidence.length > 0);
  const evi = j.evidence || {};
  assert.equal('ac_coverage_map' in evi, false, 'evidence.ac_coverage_map NAO deve aparecer');
  assert.equal('checks' in evi, false, 'evidence.checks NAO deve aparecer');
});
