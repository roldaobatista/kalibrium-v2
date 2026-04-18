// Testes RED para AC-005-A (slice 018)
// 5 agent files devem ter secao "## Saida obrigatoria" com literais canonicos
// Execucao: node --test tests/agents/output-section-present.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const AGENTS_DIR = path.join(REPO_ROOT, '.claude', 'agents');
const SCHEMA = path.join(REPO_ROOT, 'docs', 'protocol', 'schemas', 'gate-output.schema.json');

// Modo canonico esperado por agente (AC-005-A)
const AGENT_GATE = {
  'qa-expert.md': ['verify', 'audit-tests', 'audit-spec', 'audit-tests-draft'],
  'architecture-expert.md': ['review', 'plan-review'],
  'security-expert.md': ['security-gate'],
  'product-expert.md': ['functional-gate'],
  'governance.md': ['master-audit']
};

function readAgent(file) {
  return fs.readFileSync(path.join(AGENTS_DIR, file), 'utf8');
}

function extractJsonBlocks(md) {
  const rx = /```json\s*([\s\S]*?)```/g;
  const out = [];
  let m;
  while ((m = rx.exec(md))) out.push(m[1].trim());
  return out;
}

for (const agent of Object.keys(AGENT_GATE)) {
  test(`AC-005-A: ${agent} possui secao "## Saida obrigatoria"`, () => {
    const c = readAgent(agent);
    assert.match(
      c,
      /^##\s+Sa[ií]da\s+obrigat[óo]ria/mi,
      `${agent} deve conter heading "## Saida obrigatoria"`
    );
  });

  test(`AC-005-A: ${agent} contem literais do schema ($schema="gate-output-v1", gate, slice)`, () => {
    const c = readAgent(agent);
    assert.match(c, /"\$schema"\s*:\s*"gate-output-v1"/, `${agent} deve conter literal "$schema":"gate-output-v1"`);
    assert.match(c, /"gate"\s*:/, `${agent} deve conter campo "gate"`);
    assert.match(c, /"slice"\s*:/, `${agent} deve conter campo "slice"`);
  });

  test(`AC-005-A: ${agent} contem bloco JSON inline parseavel`, () => {
    const c = readAgent(agent);
    const blocks = extractJsonBlocks(c);
    assert.ok(blocks.length > 0, `${agent} deve ter ao menos 1 bloco \`\`\`json\`\`\` inline`);
    let parsed = null;
    for (const b of blocks) {
      try { parsed = JSON.parse(b); if (parsed && parsed['$schema']) break; } catch {}
    }
    assert.ok(parsed, `${agent} deve ter pelo menos 1 bloco json parseavel com $schema`);
    assert.equal(parsed['$schema'], 'gate-output-v1');
    const expected = AGENT_GATE[agent];
    assert.ok(
      expected.includes(parsed.gate),
      `${agent} exemplo deve ter gate em ${JSON.stringify(expected)}, recebeu "${parsed.gate}"`
    );
    assert.match(String(parsed.slice || ''), /^[0-9]{3}$/, 'slice deve ser formato NNN');
  });

  test(`AC-005-A: ${agent} bloco JSON conforme enum canonico do schema`, () => {
    assert.ok(fs.existsSync(SCHEMA), 'schema canonico pre-existente');
    const schema = JSON.parse(fs.readFileSync(SCHEMA, 'utf8'));
    // Localizar enum de gate em qualquer profundidade
    function findGateEnum(node) {
      if (!node || typeof node !== 'object') return null;
      if (node.properties && node.properties.gate && Array.isArray(node.properties.gate.enum)) {
        return node.properties.gate.enum;
      }
      for (const v of Object.values(node)) {
        const r = findGateEnum(v);
        if (r) return r;
      }
      return null;
    }
    const enumGate = findGateEnum(schema) || [];
    assert.ok(enumGate.length > 0, 'schema deve declarar enum de gate');
    const c = readAgent(agent);
    const blocks = extractJsonBlocks(c);
    let ok = false;
    for (const b of blocks) {
      try {
        const p = JSON.parse(b);
        if (enumGate.includes(p.gate)) { ok = true; break; }
      } catch {}
    }
    assert.ok(ok, `${agent} exemplo JSON deve usar gate dentro do enum canonico do schema`);
  });
}
