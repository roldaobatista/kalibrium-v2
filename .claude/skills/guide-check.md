---
description: Roda o guide-auditor sub-agent e gera relatório de drift em docs/audits/. Use periodicamente (semanal ou ao fim de slice) e ao suspeitar de drift. Uso: /guide-check.
---

# /guide-check

## Uso
```
/guide-check
```

## O que faz

1. Spawn do sub-agent `guide-auditor` (tools read-only).
2. Roda os 9 checks descritos em `.claude/agents/guide-auditor.md §Checks obrigatórios`.
3. Escreve relatório em `docs/audits/audit-YYYY-MM-DD.md`.
4. Atualiza `.claude/snapshots/settings-YYYY-MM-DD.json` com estado atual de `settings.json` (para próximo diff).
5. Imprime score final: **verde** (tudo ok), **amarelo** (findings sem bloqueio) ou **vermelho** (intervenção humana obrigatória).

## Quando rodar
- Toda segunda-feira (humano agenda)
- Após cada slice concluído
- Antes de qualquer mudança em `docs/constitution.md` ou `.claude/settings.json`
- Quando suspeitar de drift (ex.: sensação de que "algo mudou e ninguém documentou")

## Implementação
```bash
bash scripts/guide-check.sh
```

## Handoff

- **Verde:** nenhuma ação necessária. Registrar em telemetria.
- **Amarelo:** abrir item em `docs/guide-backlog.md` para endereçar no próximo slice.
- **Vermelho:** **parar** qualquer trabalho em progresso. Abrir `docs/incidents/audit-<date>.md`. Humano decide.
