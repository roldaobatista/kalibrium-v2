---
description: Invoca o sub-agent builder (fixer) para corrigir findings de qualquer gate canonico. Le apenas o JSON do gate rejeitado, aplica correcoes cirurgicas minimas, re-run do MESMO gate (nao pula). Uso: /fix NNN [gate].
protocol_version: "1.2.2"
---

# /fix

## Uso
```
/fix NNN                  # corrige findings do ultimo gate rejeitado do slice NNN
/fix NNN verify           # findings do qa-expert (verify)
/fix NNN review           # findings do architecture-expert (code-review)
/fix NNN security-gate    # findings do security-expert (security-gate)
/fix NNN audit-tests      # findings do qa-expert (audit-tests)
/fix NNN functional-gate  # findings do product-expert (functional-gate)
/fix NNN data-gate        # findings do data-expert (data-gate) — condicional
/fix NNN observability-gate  # findings do observability-expert (observability-gate) — condicional
/fix NNN integration-gate # findings do integration-expert (integration-gate) — condicional
/fix NNN master-audit     # findings do governance (master-audit) — dual-LLM
/fix NNN audit-spec       # findings do qa-expert (audit-spec)
/fix NNN plan-review      # findings do architecture-expert (plan-review)
```

**Nomes canonicos de gate (enum) conforme 00 §3.1:** `verify`, `review`, `security-gate`, `audit-tests`, `functional-gate`, `data-gate`, `observability-gate`, `integration-gate`, `master-audit`, `audit-spec`, `audit-story`, `audit-planning`, `plan-review`, `spec-security`, `guide-audit`.

## Por que existe
Quando um gate canonico rejeita um slice, alguem precisa corrigir. O `builder` (fixer) e separado do `builder` (implementer) para evitar vies de auto-revisao. O fixer le **apenas** findings estruturados do gate rejeitado (nunca outros gates) e aplica correcoes cirurgicas minimas.

**Regras normativas (protocolo v1.2.2):**
- Fixer recebe apenas findings do gate rejeitado, nao findings de outros gates.
- Apos correcao, **re-run do MESMO gate** (nao pula para o proximo) — enforce em 04 §Zero-tolerance.
- Escopo fechado: so corrige o que esta nos findings. Nao expande escopo.
- R6 (ADR-0012): 6ª rejeição consecutiva do MESMO gate escala PM via `/explain-slice NNN`.

## Quando invocar
Apos qualquer gate de review retornar `rejected` ou `return_to_fixer`.

## Pre-condicoes (validadas)
1. `specs/NNN/` existe
2. Pelo menos um JSON de gate existe com findings S1-S3:
   - `specs/NNN/verification.json` (qa-expert verify)
   - `specs/NNN/review.json` (architecture-expert code-review)
   - `specs/NNN/security-review.json` (security-expert security-gate)
   - `specs/NNN/test-audit.json` (qa-expert audit-tests)
   - `specs/NNN/functional-review.json` (product-expert functional-gate)
   - `specs/NNN/data-review.json` (data-expert data-gate) — condicional
   - `specs/NNN/observability-review.json` (observability-expert observability-gate) — condicional
   - `specs/NNN/integration-review.json` (integration-expert integration-gate) — condicional
   - `specs/NNN/master-audit.json` (governance master-audit)
3. Slice nao esta bloqueado por R6

## O que faz

### 1. Coletar findings
Ler o JSON do gate especificado (ou o ultimo gate rejeitado se nenhum especificado).
Filtrar apenas findings bloqueantes (S1-S3) conforme `docs/protocol/01-sistema-severidade.md`.
**Nao agregar findings de outros gates** — cada run do fixer trata um gate especifico.

### 2. Apresentar ao PM
```
Encontrei N problemas para corrigir no slice NNN:

🔴 Critical: N
🟠 High: N
🟡 Medium: N

Problemas principais (gate `security-gate`):
1. [F-001] S1: query SQL sem parametrizacao em src/foo.php:42
2. [F-002] S2: token de API exposto em config/services.php:15
3. [F-003] S3: mensagem de erro revela stack trace interno

Vou corrigir agora. Posso prosseguir? (sim/nao)
```

### 3. Spawn do fixer
Se PM confirmar:
- Spawn sub-agent `builder` (modo: fixer) com os JSONs de findings como input
- Builder (modo: fixer) aplica correcoes (ver `.claude/agents/builder.md`)
- Cada correcao dispara hook post-edit para validar teste

### 4. Apos correcoes
```
Correcoes aplicadas (gate `security-gate`):
✅ F-001: corrigido (query parametrizada)
✅ F-002: corrigido (secret movido para env)
✅ F-003: corrigido (mensagem amigavel, sem stack trace)

Proximo passo: **re-run do MESMO gate** (nao pular).
→ /security-review NNN
```

## Implementacao

```
1. Ler JSONs de review do slice
2. Filtrar findings relevantes
3. Apresentar resumo ao PM (R12)
4. Se PM confirmar, spawn Agent(subagent_type="builder") com prompt contendo os findings
5. Apos fixer terminar, reportar resultado
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Nenhum JSON de gate encontrado com findings S1-S3 | Abortar. Nenhum gate rejeitou — não há o que corrigir. Sugerir verificar estado com `/project-status`. |
| Slice bloqueado por R6 (6 rejeições consecutivas no mesmo gate) | Bloquear execução do fixer. Invocar `/explain-slice NNN` para traduzir problema ao PM. Aguardar decisão humana. |
| Fixer não consegue resolver um finding após tentativa | Reportar finding não resolvido ao PM. Se for a 6ª falha consecutiva no mesmo gate, escalar via R6. |
| Gate especificado não existe ou nome inválido | Listar enum canônico (verify, review, security-gate, audit-tests, functional-gate, data-gate, observability-gate, integration-gate, master-audit, audit-spec, audit-story, audit-planning, plan-review, spec-security, guide-audit) e pedir ao PM para especificar novamente. |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `builder` (modo: fixer) | mesmo contexto | 60k tokens |

## Handoff
- Fixes aplicados → sugerir **re-run do MESMO gate que reprovou** (nao pular para o proximo)
- Algum fix nao convergiu → reportar e sugerir escalacao
- R6 ativo → bloquear e informar PM

## Conformidade com protocolo v1.2.2

- **Agent invocado:** `builder (fixer)` — conforme mapa canonico 00 §3.1
- **Input:** apenas o JSON do gate rejeitado especificado (nao agrega findings de outros gates)
- **Output:** commit atomico `fix(slice-NNN): [gate-name] correcoes`
- **Regra normativa:** re-run do MESMO gate (nao pula) — enforce em 04 §Zero-tolerance
- **Escopo fechado:** so corrige findings listados no JSON do gate; nao expande escopo
- **R6 (ADR-0012):** 6ª rejeição consecutiva escala PM via `/explain-slice NNN`
