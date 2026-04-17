# Agenda de Remoções Futuras do Harness

Lembretes datados de remoções/limpezas agendadas no harness. Itens aqui NÃO são débito técnico — são tarefas de manutenção programada com gatilho por data.

Ver política em `feedback_zero_technical_debt.md`: technical_debt em `project-state.json` mantém-se em zero; lembretes datados vivem aqui.

---

## HARNESS-SCHEDULE-001 — Remover schemas JSON deprecated

**Data-gatilho:** 2026-05-17 (>30 dias após deprecação em 2026-04-17).

**Ação:** remover os 7 schemas marcados deprecated em 2026-04-17 após migração para `docs/protocol/schemas/gate-output.schema.json` (v1.2.4 gate-output-v1).

**Arquivos a remover:**
- `docs/schemas/plan-review.schema.json`
- `docs/schemas/review.schema.json`
- `docs/schemas/verification.schema.json`
- `docs/schemas/security-review.schema.json`
- `docs/schemas/test-audit.schema.json`
- `docs/schemas/functional-review.schema.json`
- `docs/schemas/guide-audit.schema.json`

**Critério de remoção:** em 2026-05-17 (ou depois), executar:

```bash
# 1. Garantir que nenhum código/skill/agente referencia os schemas deprecated
grep -rn "docs/schemas/\(plan-review\|review\|verification\|security-review\|test-audit\|functional-review\|guide-audit\)\.schema\.json" . \
  --include='*.sh' --include='*.md' --include='*.json' --include='*.yml'

# 2. Se retornar vazio OU só auto-referências (este doc + CLAUDE.md mencionando histórico),
#    remover os 7 arquivos:
rm docs/schemas/{plan-review,review,verification,security-review,test-audit,functional-review,guide-audit}.schema.json

# 3. Commitar como chore(harness): remove schemas deprecated pós-migração v1.2.4
```

**Se houver referências ativas em 2026-05-17:** investigar origem (pode ser script não migrado). Não remover sem resolver. Atualizar data-gatilho para próxima janela.

**Histórico:**
- 2026-04-17: schemas marcados deprecated (harness-migration-2026-04-17 no project-state.json).
- 2026-04-17: movido de `technical_debt` para esta agenda (HARNESS-MIGRATION-003 resolvido como schedule).

---

<!-- Próximos itens agendados entram aqui com ID HARNESS-SCHEDULE-NNN -->
