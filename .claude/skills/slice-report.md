---
description: Gera relatório quantitativo de um slice a partir da telemetria (.claude/telemetry/slice-NNN.jsonl). Use após /verify-slice para ter os números antes da retrospectiva. Uso: /slice-report NNN.
---

# /slice-report

## Uso
```
/slice-report NNN
```

## O que faz

Lê `.claude/telemetry/slice-NNN.jsonl` e gera `docs/retrospectives/slice-NNN-report.md` com:

- **Tempo total** do slice (do primeiro evento ao `verdict: approved`)
- **Tokens por sub-agent** (architect / ac-to-test / implementer / verifier)
- **Total de tokens** do slice
- **Gates disparados** (quantos `post-edit-gate`, `pre-commit-gate`, bloqueios)
- **Gates que bloquearam** (agente teve que corrigir)
- **Reprovações do verifier** (deveria ser 0 ou 1)
- **Tempo até primeiro green** (por AC)
- **Commits no slice** (nomes e mensagens)
- **Arquivos tocados**
- **Lint/type-check failures** ao longo do slice

## Saída: seções quantitativas fixas, sem opinião

```markdown
# slice-NNN-report

## Métricas
| Métrica | Valor |
|---|---|
| Tempo total | 2h13m |
| Tokens (architect) | 12.3k |
| Tokens (ac-to-test) | 28.7k |
| Tokens (implementer) | 64.1k |
| Tokens (verifier) | 18.9k |
| **Tokens totais** | **124.0k** |
| Gates disparados | 47 |
| Gates bloqueando | 3 |
| Reprovações verifier | 0 |

## ACs
| AC | Teste | Primeiro green | Notas |
|---|---|---|---|
| AC-001 | tests/foo.test.ts | 18min | - |

## Commits
- test(slice-NNN): AC tests red — <hash>
- feat(slice-NNN): implementa X — <hash>
- ...
```

## Implementação
```bash
bash scripts/slice-report.sh "$1"
```

## Handoff
Output alimenta `/retrospective NNN`, que adiciona análise qualitativa em cima dos números.
