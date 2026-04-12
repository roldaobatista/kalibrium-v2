---
description: Roda gate de seguranca independente em worktree isolada. Monta security-review-input/, spawn security-reviewer, valida JSON contra schema. Gate obrigatorio antes de merge. Uso: /security-review NNN.
---

# /security-review

## Uso
```
/security-review NNN
```

## Por que existe
Seguranca nao pode ser avaliada pelo mesmo agente que implementou. O security-reviewer opera em worktree isolada, sem acesso ao contexto do implementer, e avalia OWASP top 10, LGPD, secrets e input validation.

## Quando invocar
Apos `/verify-slice NNN` retornar `approved`. Parte do pipeline de gates antes do merge.

## Pre-condicoes (validadas)
1. `specs/NNN/spec.md` existe
2. `specs/NNN/verification.json` existe com `verdict: approved`
3. `specs/NNN/review.json` existe com `verdict: approved`
4. Arquivos de codigo do slice identificados

## O que faz

### 1. Montar `security-review-input/`
- `spec.md` — copia do spec
- `files-changed.txt` — `git diff --name-only` do slice
- `source/` — copia dos arquivos de codigo alterados
- `threat-model.md` — copia de `docs/security/threat-model.md`
- `lgpd-base-legal.md` — copia de `docs/security/lgpd-base-legal.md`
- `constitution-snapshot.md` — copia da constitution

### 2. Spawn security-reviewer em worktree isolada
```
Agent(subagent_type="security-reviewer", isolation="worktree")
```

### 3. Validar output
Validar `security-review.json` contra `docs/schemas/security-review.schema.json`.
Rejeitar outputs invalidos.

### 4. Apresentar ao PM

**Caso approved:**
```
🔒 Revisao de seguranca: APROVADO

Nenhum problema critico ou alto encontrado.
Verificacoes LGPD: todas passaram.

Proximo gate: /test-audit NNN
```

**Caso rejected:**
```
🔒 Revisao de seguranca: REPROVADO

Problemas encontrados:
🔴 SEC-001 [critico]: Query SQL sem parametrizacao em src/foo.php:42
🟠 SEC-002 [alto]: Token de API exposto em config/services.php:15

Acao necessaria: corrigir os problemas de seguranca.
→ /fix NNN security
```

### 5. Persistir resultado
Copiar `security-review.json` para `specs/NNN/security-review.json`.
Atualizar `project-state.json` gates_status.
Registrar em telemetria.

## Handoff
- `approved` → proximo gate (`/test-audit NNN`)
- `rejected` → `/fix NNN security` → re-run `/security-review NNN`
- 2 rejeicoes consecutivas → R6 escalacao
