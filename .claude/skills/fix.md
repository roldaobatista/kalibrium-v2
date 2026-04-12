---
description: Invoca o sub-agent fixer para corrigir violations/findings de qualquer gate de review. Le os JSONs de review do slice e aplica correcoes minimas. Uso: /fix NNN [gate].
---

# /fix

## Uso
```
/fix NNN              # corrige todos os findings pendentes do slice NNN
/fix NNN security     # corrige apenas findings do security-reviewer
/fix NNN tests        # corrige apenas findings do test-auditor
/fix NNN functional   # corrige apenas findings do functional-reviewer
/fix NNN verifier     # corrige apenas violations do verifier
/fix NNN reviewer     # corrige apenas findings do reviewer
```

## Por que existe
Quando um gate de review rejeita um slice, alguem precisa corrigir. O fixer e separado do implementer para evitar vies de auto-revisao. O fixer le findings estruturados e aplica correcoes cirurgicas.

## Quando invocar
Apos qualquer gate de review retornar `rejected` ou `return_to_fixer`.

## Pre-condicoes (validadas)
1. `specs/NNN/` existe
2. Pelo menos um JSON de review existe com findings:
   - `specs/NNN/verification.json` (verifier)
   - `specs/NNN/review.json` (reviewer)
   - `specs/NNN/security-review.json` (security-reviewer)
   - `specs/NNN/test-audit.json` (test-auditor)
   - `specs/NNN/functional-review.json` (functional-reviewer)
3. Slice nao esta bloqueado por R6

## O que faz

### 1. Coletar findings
Ler os JSONs de review especificados (ou todos se nenhum gate especificado).
Filtrar apenas findings com status `rejected`/`fail` ou severity `critical`/`high`/`medium`.

### 2. Apresentar ao PM
```
Encontrei N problemas para corrigir no slice NNN:

🔴 Critical: N
🟠 High: N
🟡 Medium: N

Problemas principais:
1. [SEC-001] Seguranca: query SQL sem parametrizacao em src/foo.php:42
2. [TEST-001] Testes: AC-003 sem teste de erro em tests/ac-003.test.ts
3. [UX-001] Funcional: mensagem de erro tecnica em vez de amigavel

Vou corrigir agora. Posso prosseguir? (sim/nao)
```

### 3. Spawn do fixer
Se PM confirmar:
- Spawn sub-agent `fixer` com os JSONs de findings como input
- Fixer aplica correcoes (ver `.claude/agents/fixer.md`)
- Cada correcao dispara hook post-edit para validar teste

### 4. Apos correcoes
```
Correcoes aplicadas:
✅ SEC-001: corrigido (query parametrizada)
✅ TEST-001: corrigido (teste de erro adicionado)
✅ UX-001: corrigido (mensagem amigavel)

Proximo passo: re-rodar o gate que reprovou.
→ /verify-slice NNN (para re-verificar)
→ /security-review NNN (para re-verificar seguranca)
```

## Implementacao

```
1. Ler JSONs de review do slice
2. Filtrar findings relevantes
3. Apresentar resumo ao PM (R12)
4. Se PM confirmar, spawn Agent(subagent_type="fixer") com prompt contendo os findings
5. Apos fixer terminar, reportar resultado
```

## Handoff
- Fixes aplicados → sugerir re-run do gate que reprovou
- Algum fix nao convergiu → reportar e sugerir escalacao
- R6 ativo → bloquear e informar PM
