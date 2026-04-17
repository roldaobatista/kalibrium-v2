---
description: Merge final do slice após todos os gates canônicos terem aprovado (verify, review, security-gate, audit-tests, functional-gate + master-audit + gates condicionais). Valida JSONs contra schema formal, prepara PR body e — se permissões de push estiverem liberadas — executa git push + gh pr create. Se o push ainda estiver selado, imprime o roteiro exato para o PM. Uso: /merge-slice NNN.
protocol_version: "1.2.2"
---

# /merge-slice

## Uso
```
/merge-slice NNN
```

## Quando invocar
Após `/verify-slice NNN`, `/review-pr NNN`, `/security-review NNN`, `/test-audit NNN`, `/functional-review NNN`, gates condicionais aplicáveis (data-gate, observability-gate, integration-gate) e `/master-audit NNN` terem emitido `verdict: approved` e `blocking_findings_count == 0`. Nunca antes — R11 exige dupla-aprovação independente e o pipeline atual exige todos os gates canônicos antes de merge.

Fecha o último elo da cadeia happy-path identificada como blocker P0-1 no meta-audit #2 (2026-04-11).

## Pré-condições (validadas pelo script)

- `specs/NNN/verification.json` existe, conforme schema `docs/protocol/schemas/gate-output.schema.json`, `verdict: approved`, `blocking_findings_count: 0`
- `specs/NNN/review.json` idem (gate `review`)
- `specs/NNN/security-review.json` idem (gate `security-gate`)
- `specs/NNN/test-audit.json` idem (gate `audit-tests`)
- `specs/NNN/functional-review.json` idem (gate `functional-gate`)
- `specs/NNN/master-audit.json` idem (gate `master-audit`, dual-LLM)
- Gates condicionais (se aplicaveis): `specs/NNN/data-review.json`, `observability-review.json`, `integration-review.json` — idem
- **E10 (reconciliation_failed dual-LLM):** se `master-audit.json` tem `reconciliation_failed: true`, exige `specs/NNN/master-audit-pm-decision.json` com decisão registrada do PM conforme 07 §E10
- **R13/R14 (sequencing):** `bash scripts/sequencing-check.sh --slice NNN` retorna 0 (stories/épicos anteriores `merged` em `project-state.json[epics_status]`)
- Branch atual ≠ `main` e tem diff contra `main`
- Harness íntegro (`hooks-lock --check` e `settings-lock --check`)
- Nenhum arquivo proibido (R1) e autor identificável (R5)

## O que faz

1. **Gates obrigatórios:** lê todos os JSONs de gate canônicos (`verification.json`, `review.json`, `security-review.json`, `test-audit.json`, `functional-review.json`, `master-audit.json`) e gates condicionais aplicáveis. Cada JSON é validado contra `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatórios). Qualquer gate ausente, rejeitado, `blocking_findings_count > 0`, ou fora do schema aborta com exit 1.
2. **R13/R14:** `scripts/sequencing-check.sh --slice NNN` bloqueia se stories/épicos anteriores não estão `merged`.
3. **E10 (reconciliation_failed):** se `master-audit.json` tem `reconciliation_failed: true`, exige `specs/NNN/master-audit-pm-decision.json`. Sem decisão do PM, abort.
4. **Integridade do harness:** drift em settings ou hooks = abort.
5. **Gera PR body** em `specs/NNN/pr-body.md` com resumo dos ACs verificados, referência a todos os gates canônicos, e bloco R12 em linguagem de PM.
6. **Grava telemetria** evento `merge` (append-only, hash-chain).
7. **Detecta se push está autorizado** em `.claude/settings.json`:
   - Se `Bash(git push origin*)` **e** `Bash(gh pr create*)` estiverem em `permissions.allow` → executa `git push -u origin <branch>` + `gh pr create` + grava URL em `specs/NNN/pr-url.txt`.
   - Se **não** estiverem → imprime banner vermelho apontando para `docs/explanations/meta-audit-2-fixes.md §1` e sai com exit 3. **Nunca** tenta `--no-verify`, `--force` ou bypass.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Qualquer gate JSON ausente, rejeitado ou com findings | Abortar com exit 1. Rodar o gate faltante primeiro (`/verify-slice NNN`, `/review-pr NNN`, `/security-review NNN`, `/test-audit NNN` ou `/functional-review NNN`). |
| Drift detectado no harness (hooks-lock ou settings-lock falham) | Abortar. PM deve investigar drift e rodar `relock-harness.sh` em terminal externo se a alteração for legítima. |
| Push não autorizado em `.claude/settings.json` | Sair com exit 3 e imprimir roteiro para PM liberar push em terminal externo. Não tentar bypass. |
| Branch atual é `main` | Abortar. Merge só ocorre via PR de feature branch. Verificar se o slice foi implementado em branch dedicada. |

## Agentes

Nenhum — executada pelo orquestrador. Validações são feitas por scripts selados (`hooks-lock`, `settings-lock`).

## Handoff

- **Push autorizado + PR criado** → próximo passo = PM abre URL, testa visualmente (se UI), aprova merge no GitHub.
- **Push bloqueado (exit 3)** → roteiro PM em `docs/explanations/meta-audit-2-fixes.md §1`. PM executa em terminal externo, depois volta e roda `/merge-slice NNN` de novo.
- **Verdict divergente** → sugere `/explain-slice NNN` para traduzir o problema.

## Implementação

```bash
bash scripts/merge-slice.sh "$1"
```

## Por que não está em `scripts/hooks/`

`scripts/hooks/` é selado pelo `hooks-lock.sh`. A skill precisa iterar durante o setup do harness; morou em `scripts/` para permitir edição sem `relock-harness.sh`. As validações de integridade continuam sendo feitas por hooks selados (`hooks-lock`, `settings-lock`).

## Conformidade com protocolo v1.2.2

- **Validação canônica:** cada JSON de gate validado contra `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatórios, enum de `gate` com os 15 nomes canônicos).
- **Gate names esperados (enum):** `verify`, `review`, `security-gate`, `audit-tests`, `functional-gate`, `data-gate` (condicional), `observability-gate` (condicional), `integration-gate` (condicional), `master-audit`.
- **Zero-tolerance:** merge somente com `blocking_findings_count == 0` em TODOS os gates canônicos aplicáveis (S4/S5 não bloqueiam).
- **E10 (reconciliation_failed):** 07 §E10 — se `master-audit.json` tem `reconciliation_failed: true`, exige `master-audit-pm-decision.json` antes de merge.
- **R13/R14 (sequencing):** enforce de ordem intra-épico e inter-épico MVP conforme `scripts/sequencing-check.sh`.
