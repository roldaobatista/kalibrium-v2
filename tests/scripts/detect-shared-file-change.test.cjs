// Testes RED para AC-002 + AC-002-A (slice 018)
// scripts/detect-shared-file-change.sh
// Execucao: node --test tests/scripts/detect-shared-file-change.test.cjs

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const SCRIPT = path.join(REPO_ROOT, 'scripts', 'detect-shared-file-change.sh');
const FIXTURES = path.join(REPO_ROOT, 'tests', 'fixtures', 'pre-push');

function runScript(stdin) {
  // Script le diff da stdin (convencao: 1 arquivo por linha)
  return spawnSync('bash', [SCRIPT], { input: stdin, encoding: 'utf8' });
}

test('AC-002: scripts/detect-shared-file-change.sh existe e e executavel', () => {
  assert.ok(fs.existsSync(SCRIPT), 'Script detect-shared-file-change.sh nao existe');
  const stat = fs.statSync(SCRIPT);
  // No Windows NTFS mode bits sao limitados; basta arquivo existir + ter shebang
  const head = fs.readFileSync(SCRIPT, 'utf8').split('\n')[0];
  assert.match(head, /^#!.*bash/, 'Script deve comecar com shebang bash');
});

test('AC-002: shared_changed=true quando diff toca src/main.tsx', () => {
  const diff = fs.readFileSync(path.join(FIXTURES, 'shared-file-diff.txt'), 'utf8');
  const r = runScript(diff);
  assert.equal(r.status, 0, `exit code esperado 0, recebido ${r.status}. stderr: ${r.stderr}`);
  assert.match(r.stdout, /shared_changed=true/, 'stdout deve conter shared_changed=true');
});

test('AC-002: shared_changed=true quando diff toca vite.config.ts', () => {
  const r = runScript('vite.config.ts\n');
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=true/);
});

test('AC-002: shared_changed=true quando diff toca package.json', () => {
  const r = runScript('package.json\n');
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=true/);
});

test('AC-002: shared_changed=true quando diff toca capacitor.config.ts', () => {
  const r = runScript('capacitor.config.ts\n');
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=true/);
});

test('AC-002: shared_changed=true quando diff toca playwright.config.ts', () => {
  const r = runScript('playwright.config.ts\n');
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=true/);
});

test('AC-002: shared_changed=true quando diff toca .claude/settings.json', () => {
  const r = runScript('.claude/settings.json\n');
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=true/);
});

test('AC-002-A: shared_changed=false quando diff toca apenas docs/', () => {
  const diff = fs.readFileSync(path.join(FIXTURES, 'docs-only-diff.txt'), 'utf8');
  const r = runScript(diff);
  assert.equal(r.status, 0, `exit code deve ser 0 mesmo quando nao ha match. stderr: ${r.stderr}`);
  assert.match(r.stdout, /shared_changed=false/, 'diff so em docs/ nao deve disparar smoke');
});

test('AC-002-A: shared_changed=false quando diff toca src/ nao-compartilhado', () => {
  const diff = fs.readFileSync(path.join(FIXTURES, 'src-non-shared-diff.txt'), 'utf8');
  const r = runScript(diff);
  assert.equal(r.status, 0);
  assert.match(r.stdout, /shared_changed=false/);
});
