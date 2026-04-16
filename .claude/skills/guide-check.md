---
description: Roda o guide-auditor sub-agent e gera relatório de drift em docs/audits/. Use periodicamente (semanal ou ao fim de slice) e ao suspeitar de drift. Uso: /guide-check.
---

# /guide-check

## Uso
```
/guide-check
```

## O que faz

1. Spawn do sub-agent `governance` (modo: guide-audit) (tools read-only).
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

## Pré-condições

Nenhuma — pode ser executada a qualquer momento. Recomenda-se que o harness esteja íntegro (hooks e settings selados), mas a skill funciona mesmo com drift detectado (esse é justamente o propósito dela).

## Agentes

- `governance` (modo: guide-audit) (budget: 15k tokens, modelo haiku) — executa com ferramentas read-only, roda os 9 checks de drift descritos em `.claude/agents/guide-auditor.md`.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Script `scripts/guide-check.sh` não existe | Verificar integridade do harness. Pode indicar que o scaffold inicial não foi concluído. |
| Sub-agent `governance` (modo: guide-audit) falha ou excede budget (15k tokens) | Re-invocar. Se persistir, rodar os checks manualmente (leitura dos arquivos listados em `guide-auditor.md §Checks obrigatórios`). |
| Resultado vermelho (intervenção humana obrigatória) | Parar todo trabalho em progresso. Criar `docs/incidents/audit-<date>.md`. Invocar `/explain-slice` se houver slice ativo para traduzir o problema ao PM. |
| Snapshots anteriores ausentes (primeiro run) | Gerar snapshot baseline sem diff. Próxima execução terá referência para comparação. |

## Handoff

- **Verde:** nenhuma ação necessária. Registrar em telemetria.
- **Amarelo:** abrir item em `docs/guide-backlog.md` para endereçar no próximo slice.
- **Vermelho:** **parar** qualquer trabalho em progresso. Abrir `docs/incidents/audit-<date>.md`. Humano decide.
