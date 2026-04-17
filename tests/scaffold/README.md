# tests/scaffold — testes mecânicos do scaffold slice-016

Estes testes validam ACs do slice-016 (E15-S02) que são verificáveis com `node`
puro + `child_process` + `fs` — sem precisar subir navegador ou servidor.

## Por que `.test.cjs`?

- Usam **Node Test Runner nativo** (`node --test`), sem dependência de Vitest/Jest.
- Extensão `.cjs` para não conflitar com `"type": "module"` no `package.json` raiz
  (o runner chama `require('node:test')` / `require('node:assert')` em CommonJS).
- Rodam em Windows, macOS e Linux sem shim de shell (decisão D10 do plan.md).

## Como rodar

```bash
node --test tests/scaffold/*.test.cjs
```

Ou pelo script do `package.json`:

```bash
npm run test:scaffold
```

## Convenção de AC-ID (ADR-0017 Mudança 1)

Cada arquivo de teste declara **no topo**, em comentário:

```js
// @covers AC-NNN[, AC-NNN]
```

E cada `describe()` / `test()` inclui o prefixo `AC-NNN:` no nome:

```js
describe('AC-002: npm run build produz artefatos', () => {
    test('AC-002: dist/index.html existe após build', () => { ... });
});
```

A auditoria `audit-tests-draft` (qa-expert) valida a rastreabilidade AC → teste
via `name_contains_ac_id` ou `@covers AC-NNN` no docblock.

## Mapeamento AC → arquivo

| AC | Arquivo |
|---|---|
| AC-002 + AC-010 | `ac-002-build-web.test.cjs` |
| AC-003 | `ac-003-cap-ios.test.cjs` (skip fora de macOS) |
| AC-004 | `ac-004-cap-android.test.cjs` |
| AC-005 + AC-011 | `ac-005-structure.test.cjs` |
| AC-007 + AC-012 | `ac-007-lint.test.cjs` |
| AC-008 + AC-013 | `ac-008-legacy-removed.test.cjs` |
| AC-014 | `ac-014-capacitor-security.test.cjs` |

ACs **não cobertos aqui** (cobertos em `tests/e2e/`):
- AC-001 + AC-009 → `tests/e2e/ac-001-dev-server.spec.ts`
- AC-006 → `tests/e2e/ac-006-layout-adaptive.spec.ts`

Total: 14/14 ACs cobertos.

## Estado red esperado

No commit de test-writer (antes do implementer), **todos** os testes aqui
devem falhar com mensagens explícitas:
- `dist/` não existe
- `src/pages/` não existe
- `capacitor.config.ts` não existe
- `package.json` sem script `lint`

Isso é o **red do TDD**. O implementer cria o scaffold e esses testes
ficam verdes.
