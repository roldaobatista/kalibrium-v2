---
description: Inicializa uma sessao Codex CLI neste projeto seguindo o harness. Uso obrigatorio no inicio de toda sessao Codex e antes de qualquer trabalho.
---

# /codex-bootstrap

Este comando espelha `.claude/skills/codex-bootstrap.md`.

Use quando o orquestrador ativo for Codex CLI. Ele deve:

1. Ler `CLAUDE.md`.
2. Ler `docs/constitution.md`.
3. Ler `docs/TECHNICAL-DECISIONS.md`.
4. Ler `docs/documentation-requirements.md`.
5. Ler `project-state.json`.
6. Ler `docs/handoffs/latest.md`.
7. Ler `.claude/agents/orchestrator.md`.
8. Rodar `git status --short`.
9. Rodar `bash scripts/hooks/session-start.sh`.
10. Rodar `bash scripts/hooks/settings-lock.sh --check`.
11. Rodar `bash scripts/hooks/hooks-lock.sh --check`.
12. Confirmar ao PM o estado restaurado e a proxima acao antes de editar arquivos.

Antes de encerrar sessao Codex, sempre atualizar `project-state.json`, criar `docs/handoffs/handoff-YYYY-MM-DD-HHMM.md`, atualizar `docs/handoffs/latest.md`, validar os checks leves e commitar ou declarar explicitamente por que o checkpoint ficara pendente.
