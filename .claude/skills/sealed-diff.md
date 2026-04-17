---
description: Verifica sob demanda se os arquivos selados do harness (settings.json, MANIFEST.sha256, git identity baseline) estão íntegros. Complementa o check automático do SessionStart — roda entre gates ou quando suspeitar de tampering. Uso: /sealed-diff.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit Cat C polishing"
---

# /sealed-diff

## Uso
```
/sealed-diff
```

## O que faz

Executa `scripts/sealed-diff.sh`, que chama em modo leitura:

1. `bash scripts/hooks/settings-lock.sh --check` — valida `.claude/settings.json` contra `.claude/settings.json.sha256`.
2. `bash scripts/hooks/hooks-lock.sh --check` — valida todos os arquivos em `scripts/hooks/` contra `scripts/hooks/MANIFEST.sha256`.
3. Confere `.claude/git-identity-baseline` (se existir) contra a identidade git atual.

Consolida output num resumo verde/vermelho/amarelo.

## Por que importa

O `SessionStart` hook (`session-start.sh`) já valida selos no início de cada sessão. Entre sessões longas ou cadeias de gates (verify → review → security-gate → audit-tests → functional-gate → master-audit), um arquivo selado pode ser tocado inadvertidamente (ex: edição manual sem relock, merge de branch, rebase). Sem check intermediário, só descobrimos o drift no próximo SessionStart — que pode ser horas depois.

`/sealed-diff` dá visibilidade mid-session, respeitando o modelo de enforcement por arquitetura:
- **Não altera** nada — só lê e compara hashes.
- **Não requer** relock — só reporta estado.
- **Complementa** `session-start.sh` sem duplicar lógica (reusa os mesmos `--check` canônicos).

## Quando rodar

- Entre gates num slice longo (ex: após `/verify-slice`, antes de `/review-pr`).
- Quando uma sessão Codex CLI é encerrada e o PM quer confirmar antes de iniciar Claude Code na mesma branch (R2).
- Após operações de git envolvendo `.claude/` ou `scripts/hooks/` (rebase, cherry-pick, merge de branch externa).
- Antes de `/merge-slice NNN` como sanity-check final do harness.
- Quando `post-edit-gate.sh` ou outro hook se comportar de forma inesperada.

## Implementação

```bash
bash scripts/sealed-diff.sh
```

## Pré-condições

Nenhuma — pode ser executada a qualquer momento, em qualquer estado do projeto.

## Agentes

Nenhum — executada diretamente pelo orquestrador ativo.

## Interpretação de saída

| Output | Significado | Ação |
|---|---|---|
| `SELOS OK` (exit 0) | Todos os selos batem | Prosseguir normalmente. |
| `DRIFT DETECTADO` (exit 1) | Pelo menos um arquivo selado diverge do hash armazenado | **NÃO rodar relock-harness.sh imediatamente.** Seguir CLAUDE.md §9: investigar `git status .claude/ scripts/hooks/`, checar `docs/incidents/` para último relock legítimo, entender origem do drift antes de qualquer ação. |
| `ERRO DE EXECUÇÃO` (exit 2) | Script de check ausente ou sem permissão | Harness pode estar comprometido. Rodar `/guide-check` para diagnóstico completo e verificar se `scripts/hooks/settings-lock.sh` ou `hooks-lock.sh` foram removidos. |

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Script `scripts/sealed-diff.sh` não existe | Ver `docs/incidents/` — pode ter sido removido por tampering. Restaurar via `git checkout HEAD -- scripts/sealed-diff.sh`. |
| Drift em `.claude/settings.json` após merge | Investigar qual commit mudou o arquivo (`git log --oneline -- .claude/settings.json`). Se for merge de branch que fez relock legítimo, atualizar o hash seguindo CLAUDE.md §9. Se for drift não-autorizado, tratar como incidente de segurança. |
| Drift em `scripts/hooks/*` sem commit recente naquele arquivo | Tampering direto no filesystem. Abrir `docs/incidents/harness-tampering-YYYY-MM-DD.md` e investigar antes de qualquer outra ação. |
| Identidade git atual não aparece em `allowed-git-identities.txt` | Rodar `git config user.email` / `user.name` e confirmar se é identidade autorizada. Se for PM legítimo em máquina nova, adicionar via procedimento de relock. Se for inesperado, parar e investigar. |

## Referências

- `CLAUDE.md` §9 — procedimento legítimo de alteração de arquivos selados.
- `docs/constitution.md` §4 R9 — zero bypass de gates.
- `scripts/hooks/settings-lock.sh`, `scripts/hooks/hooks-lock.sh` — geradores canônicos dos hashes.
- `scripts/hooks/session-start.sh` — onde esses mesmos checks rodam automaticamente no início da sessão.

## Handoff

- `SELOS OK` → prosseguir com fluxo normal (próximo gate/merge)
- `DRIFT` → parar, investigar `docs/incidents/` e `git log`, seguir CLAUDE.md §9
- `ERRO EXEC` → tratar como possível tampering; abrir incident file

## Próximo passo

- OK → continuar (próximo gate, merge, commit)
- Drift detectado → **não rodar relock antes de entender origem**; investigar + escalar PM se suspeito
- Erro de execução → `/guide-check` para diagnóstico completo do harness

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador executa checks `--check` dos hooks selados).
- **Gates produzidos:** não é gate de slice; é health-check do harness entre gates.
- **Output:** mensagem no chat (SELOS OK / DRIFT / ERRO) + exit code.
- **Schema formal:** reutiliza hashes de `settings.json.sha256` e `MANIFEST.sha256`.
- **Isolamento R3:** não aplicável (read-only do harness).
- **Ordem no pipeline:** invocado entre gates de slice longo; antes de `/merge-slice` como sanity final.
