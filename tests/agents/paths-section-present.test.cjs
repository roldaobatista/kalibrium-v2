// Testes RED para AC-007 (slice 018)
// 12 agent files devem ter secao "## Paths do repositorio"
// Execucao: node --test tests/agents/paths-section-present.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const AGENTS_DIR = path.join(REPO_ROOT, '.claude', 'agents');

const ALL_AGENTS = [
  'orchestrator.md',
  'architecture-expert.md',
  'builder.md',
  'data-expert.md',
  'devops-expert.md',
  'governance.md',
  'integration-expert.md',
  'observability-expert.md',
  'product-expert.md',
  'qa-expert.md',
  'security-expert.md',
  'ux-designer.md'
];

const ROOT_DIRS = ['src/', 'tests/', 'specs/', 'docs/', 'scripts/', 'public/', 'epics/', '.claude/', '.github/'];

function readAgent(file) {
  return fs.readFileSync(path.join(AGENTS_DIR, file), 'utf8');
}

test('AC-007: exatamente 12 agent files existem em .claude/agents/', () => {
  for (const a of ALL_AGENTS) {
    assert.ok(
      fs.existsSync(path.join(AGENTS_DIR, a)),
      `agent file ${a} deve existir (ADR-0017 / spec slice 018)`
    );
  }
});

for (const agent of ALL_AGENTS) {
  test(`AC-007: ${agent} contem secao "## Paths do repositorio"`, () => {
    const c = readAgent(agent);
    assert.match(
      c,
      /^##\s+Paths\s+do\s+reposit[óo]rio/mi,
      `${agent} deve conter heading "## Paths do repositorio"`
    );
  });

  test(`AC-007: ${agent} lista os 9 dirs raiz canonicos`, () => {
    const c = readAgent(agent);
    for (const d of ROOT_DIRS) {
      assert.match(
        c,
        new RegExp(d.replace('/', '\\/')),
        `${agent} deve listar dir raiz "${d}" na secao de paths`
      );
    }
  });

  test(`AC-007: ${agent} possui guardrail explicito "NAO existe subpasta frontend/"`, () => {
    const c = readAgent(agent);
    assert.match(
      c,
      /N[ÃA]O\s+existe\s+subpasta\s+`?frontend\/?`?/i,
      `${agent} deve conter guardrail "NAO existe subpasta frontend/"`
    );
  });

  test(`AC-007: ${agent} instrui "Glob antes de Read" em path suspeito`, () => {
    const c = readAgent(agent);
    assert.match(
      c,
      /Glob\s+antes\s+de\s+Read/i,
      `${agent} deve conter instrucao "Glob antes de Read"`
    );
  });
}
