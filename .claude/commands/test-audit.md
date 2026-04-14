---
description: Roda auditoria independente de testes (isolado por hook, sem worktree). Monta test-audit-input/, spawn test-auditor, valida JSON contra schema. Verifica cobertura de ACs, edge cases e anti-patterns. Uso: /test-audit NNN.
---

# /test-audit

## Uso
```
/test-audit NNN
```

## Por que existe
Testes verdes nao significam testes bons. O test-auditor verifica se cada AC realmente tem teste adequado, se edge cases estao cobertos e se nao ha anti-patterns (testes frageis, assertions vazias, etc.).

## Quando invocar
Apos `/security-review NNN` retornar `approved`. Parte do pipeline de gates.

## Pre-condicoes (validadas)
1. `specs/NNN/spec.md` existe com ACs
2. Testes do slice existem e passam
3. `specs/NNN/verification.json` existe com `verdict: approved`
4. `specs/NNN/review.json` existe com `verdict: approved`
5. `specs/NNN/security-review.json` existe com `verdict: approved`

## O que faz

### 1. Montar `test-audit-input/`
- `spec.md` — copia do spec com ACs
- `ac-list.json` — ACs parseados
- `test-files/` — copia dos arquivos de teste do slice
- `source-files/` — copia dos arquivos de producao do slice
- `test-results.txt` — output da execucao dos testes
- `coverage-report.json` — relatorio de cobertura (se disponivel)

### 2. Spawn test-auditor (sem worktree)
```
Agent(subagent_type="test-auditor")
```
**Nota:** NAO usar `isolation: "worktree"`. O input package e untracked e nao existiria na worktree. O isolamento e garantido pelo hook `verifier-sandbox.sh` que restringe reads ao diretorio de input.

### 3. Validar output
Validar `test-audit.json` contra `docs/schemas/test-audit.schema.json`.

### 4. Apresentar ao PM

**Caso approved:**
```
🧪 Auditoria de testes: APROVADO

Cobertura de ACs: 5/5 (100%)
Testes totais: 15
Caminho feliz: ✅ todos cobertos
Caminho de erro: ✅ todos cobertos
Edge cases: 12 cobertos

Proximo gate: /functional-review NNN
```

**Caso rejected:**
```
🧪 Auditoria de testes: REPROVADO

Problemas encontrados:
🔴 TEST-001: AC-003 sem teste de caminho de erro
🟠 TEST-002: Teste fragil em tests/ac-002.test.ts (depende de timestamp)
🟡 TEST-003: AC-005 sem edge case para input vazio

Acao necessaria: corrigir testes.
→ /fix NNN tests
```

### 5. Persistir resultado
Copiar `test-audit.json` para `specs/NNN/test-audit.json`.
Atualizar `project-state.json` gates_status.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `security-review.json` não existe ou não tem `verdict: approved` | Abortar. Rodar `/security-review NNN` primeiro — pipeline de gates é sequencial. |
| Testes do slice falham durante montagem de `test-audit-input/` | Abortar. Testes devem estar verdes antes do audit. Sugerir `/fix NNN verifier` ou corrigir manualmente. |
| `test-audit.json` não passa na validação contra schema | Re-spawn test-auditor. Se falhar 5 vezes consecutivas, escalar humano na 6ª (R6). |
| Cobertura de ACs parcial (AC sem teste correspondente) | test-auditor emite `rejected` com finding por AC descoberto. Sugerir `/fix NNN tests` para adicionar testes faltantes. |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `test-auditor` | worktree isolada | 25k tokens |

## Handoff
- `approved` → proximo gate (`/functional-review NNN`)
- `rejected` → `/fix NNN tests` → re-run `/test-audit NNN`
