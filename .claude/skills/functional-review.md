---
description: Roda revisao funcional independente (isolado por hook, sem worktree). Avalia se ACs sao atendidos do ponto de vista do produto/usuario. Monta functional-review-input/, spawn functional-reviewer. Uso: /functional-review NNN.
---

# /functional-review

## Uso
```
/functional-review NNN
```

## Por que existe
Testes podem passar mas o comportamento nao atender o que o PM descreveu. O functional-reviewer avalia do ponto de vista do produto: UX, consistencia, regras de negocio, terminologia.

## Quando invocar
Apos `/test-audit NNN` retornar `approved`. Ultimo gate antes de apresentar ao PM para merge.

## Pre-condicoes (validadas)
1. `specs/NNN/spec.md` existe com ACs
2. `specs/NNN/verification.json` existe com `verdict: approved`
3. `specs/NNN/review.json` existe com `verdict: approved`
4. `specs/NNN/security-review.json` existe com `verdict: approved`
5. `specs/NNN/test-audit.json` existe com `verdict: approved`

## O que faz

### 1. Montar `functional-review-input/`
- `spec.md` — copia do spec
- `ac-list.json` — ACs parseados
- `source/` — copia dos arquivos de codigo alterados
- `test-results.txt` — output dos testes
- `prd-excerpt.md` — trecho relevante do PRD
- `personas.md` — copia de personas
- `journeys.md` — copia de jornadas
- `glossary-pm.md` — glossario de produto

### 2. Spawn functional-reviewer (sem worktree)
```
Agent(subagent_type="product-expert")
```
**Nota:** NAO usar `isolation: "worktree"`. O input package e untracked e nao existiria na worktree. O isolamento e garantido pelo hook `verifier-sandbox.sh` que restringe reads ao diretorio de input.

### 3. Validar output
Validar `functional-review.json` contra `docs/schemas/functional-review.schema.json`.

### 4. Apresentar ao PM

**Caso approved:**
```
✅ Revisao funcional: APROVADO

Todos os criterios de aceite atendidos: 5/5
Experiencia do usuario: sem problemas
Consistencia de produto: OK
Regras de negocio: OK

🎉 Todos os gates passaram! Slice NNN pronto para merge.

Pipeline completo:
✅ Verificador mecanico (verifier)
✅ Revisor de codigo (reviewer)
✅ Revisor de seguranca (security-reviewer)
✅ Auditor de testes (test-auditor)
✅ Revisor funcional (functional-reviewer)

Proximo passo: /merge-slice NNN
```

**Caso rejected:**
```
⚠️ Revisao funcional: REPROVADO

Problemas encontrados:
🔴 AC-003 nao atendido: comportamento difere do descrito no spec
🟠 UX-001: mensagem de erro tecnica ("401 Unauthorized") em vez de texto amigavel

Acao necessaria: corrigir problemas funcionais.
→ /fix NNN functional
```

### 5. Persistir resultado
Copiar `functional-review.json` para `specs/NNN/functional-review.json`.
Atualizar `project-state.json` gates_status.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Pré-condição falha (gates anteriores não aprovados) | Listar quais gates faltam. Orientar a rodar o pipeline na ordem: `/verify-slice` → `/review-pr` → `/security-review` → `/test-audit` → `/functional-review`. |
| Sub-agent `product-expert` (modo: functional-gate) falha ou excede budget (25k tokens) | Re-invocar com contexto reduzido. Se persistir, verificar se o slice é muito grande e sugerir decomposição. |
| 6º `rejected` consecutivo (R6) | Parar imediatamente. Invocar `/explain-slice NNN` para traduzir o problema ao PM. Escalar decisão humana — não tentar corrigir sem orientação. |
| PM cancela a revisão funcional | Registrar estado parcial em `project-state.json`. O slice não avança para merge. PM pode retomar com `/functional-review NNN` quando desejar. |

## Agentes

- `product-expert` (modo: functional-gate) (budget: 25k tokens) — executa em worktree isolada, avalia ACs do ponto de vista de produto/UX. Emite `functional-review.json`.

## Handoff
- `approved` (todos os gates) → `/merge-slice NNN`
- `rejected` → `/fix NNN functional` → re-run `/functional-review NNN`
