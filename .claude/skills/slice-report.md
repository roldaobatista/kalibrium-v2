---
description: Gera relatório quantitativo de um slice a partir da telemetria (.claude/telemetry/slice-NNN.jsonl). Use após /verify-slice para ter os números antes da retrospectiva. Uso: /slice-report NNN.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005"
---

# /slice-report

## Uso
```
/slice-report NNN
```

## O que faz

Lê `.claude/telemetry/slice-NNN.jsonl` e gera `docs/retrospectives/slice-NNN-report.md` com:

- **Tempo total** do slice (do primeiro evento ao `verdict: approved`)
- **Tokens por sub-agent** (architecture-expert / builder test-writer / builder implementer / qa-expert verify)
- **Total de tokens** do slice
- **Gates disparados** (quantos `post-edit-gate`, `pre-commit-gate`, bloqueios)
- **Gates que bloquearam** (agente teve que corrigir)
- **Reprovações do `qa-expert` (modo: verify)** (deveria ser 0 ou 1)
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
| Tokens (architecture-expert) | 12.3k |
| Tokens (builder test-writer) | 28.7k |
| Tokens (builder implementer) | 64.1k |
| Tokens (qa-expert verify) | 18.9k |
| **Tokens totais** | **124.0k** |
| Gates disparados | 47 |
| Gates bloqueando | 3 |
| Reprovações qa-expert (verify) | 0 |

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

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `.claude/telemetry/slice-NNN.jsonl` não existe | Verificar se o slice NNN foi executado com telemetria habilitada. Sem dados, relatório não pode ser gerado. |
| Telemetria com eventos corrompidos (JSON inválido em alguma linha) | Pular linhas inválidas e gerar relatório parcial, alertando sobre dados incompletos. |
| Diretório `docs/retrospectives/` não existe | Criar o diretório antes de gerar o relatório. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- Slice NNN merged (ou pelo menos com `/verify-slice` concluído).
- `.claude/telemetry/slice-NNN.jsonl` existe com dados de telemetria.

## Handoff
Output alimenta `/retrospective NNN`, que adiciona análise qualitativa em cima dos números.

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador lê telemetria e agrega métricas).
- **Gates produzidos:** não é gate; é relatório quantitativo derivado de telemetria.
- **Output:** `docs/retrospectives/slice-NNN-report.md` (markdown com tabelas fixas).
- **Schema formal:** seções quantitativas (métricas, ACs, commits) padronizadas.
- **Isolamento R3:** não aplicável (consome artefatos imutáveis).
- **Ordem no pipeline:** após `/merge-slice NNN`; precede `/retrospective NNN`.
