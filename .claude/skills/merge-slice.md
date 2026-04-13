---
description: Merge final do slice após verifier e reviewer terem aprovado. Valida dupla-aprovação (R11), prepara PR body e — se permissões de push estiverem liberadas — executa git push + gh pr create. Se o push ainda estiver selado, imprime o roteiro exato para o PM. Uso: /merge-slice NNN.
---

# /merge-slice

## Uso
```
/merge-slice NNN
```

## Quando invocar
Após `/verify-slice NNN` **e** `/review-pr NNN` terem ambos emitido `verdict: approved`. Nunca antes — R11 exige dupla-aprovação independente.

Fecha o último elo da cadeia happy-path identificada como blocker P0-1 no meta-audit #2 (2026-04-11).

## Pré-condições (validadas pelo script)

- `specs/NNN/verification.json` existe e tem `verdict: approved`
- `specs/NNN/review.json` existe e tem `verdict: approved`
- Branch atual ≠ `main` e tem diff contra `main`
- Harness íntegro (`hooks-lock --check` e `settings-lock --check`)
- Nenhum arquivo proibido (R1) e autor identificável (R5)

## O que faz

1. **Dupla-verificação (R11):** lê `specs/NNN/verification.json` e `specs/NNN/review.json`. Qualquer um sem `approved` aborta com exit 1.
2. **Integridade do harness:** drift em settings ou hooks = abort.
3. **Gera PR body** em `specs/NNN/pr-body.md` com resumo dos ACs verificados, referência às duas aprovações, e bloco R12 em linguagem de PM.
4. **Grava telemetria** evento `merge` (append-only, hash-chain).
5. **Detecta se push está autorizado** em `.claude/settings.json`:
   - Se `Bash(git push origin*)` **e** `Bash(gh pr create*)` estiverem em `permissions.allow` → executa `git push -u origin <branch>` + `gh pr create` + grava URL em `specs/NNN/pr-url.txt`.
   - Se **não** estiverem → imprime banner vermelho apontando para `docs/explanations/meta-audit-2-fixes.md §1` e sai com exit 3. **Nunca** tenta `--no-verify`, `--force` ou bypass.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `verification.json` ou `review.json` sem `verdict: approved` | Abortar com exit 1. Rodar o gate faltante primeiro (`/verify-slice NNN` ou `/review-pr NNN`). |
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
