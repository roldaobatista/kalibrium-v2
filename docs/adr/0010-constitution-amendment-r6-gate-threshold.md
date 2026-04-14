# ADR-0010 — Alterar R6 para 5 ciclos automáticos e escalação na 6ª rejeição

**Status:** accepted
**Data:** 2026-04-14
**Tipo:** constitution amendment
**Regra afetada:** R6

## Contexto

Durante o slice 007, o reviewer escalou o PM repetidas vezes após o limite antigo de duas reprovações consecutivas. O PM autorizou novas tentativas focadas, e o fluxo mostrou que a escalação precoce estava criando interrupções operacionais em vez de uma decisão de produto útil.

Pedido explícito do PM em 2026-04-14:

> mude a regra para os loop de review e auditoria para 5 vezes, na sexta trás ate mim

## Redação anterior

R6 definia que 2 reprovações consecutivas do verifier escalavam ao humano. R11 estendia o mesmo padrão ao reviewer.

## Decisão

Alterar R6 para:

- manter zero tolerance: nenhum finding de qualquer severidade é aceito;
- manter a obrigação de re-rodar o mesmo gate que rejeitou;
- permitir 5 ciclos automáticos de correção nos loops de review/auditoria;
- escalar ao PM na 6ª reprovação consecutiva do mesmo gate;
- manter `/explain-slice NNN` obrigatório em qualquer escalação R6.

## Impacto

Arquivos operacionais atualizados:

- `docs/constitution.md`: versão 1.3.0 e nova redação de R6/R11.
- `CLAUDE.md`: resumo operacional e pipeline de gates.
- `.claude/agents/orchestrator.md`: protocolo fixer -> re-gate, contadores e budgets.
- `.claude/agents/verifier.md`, `.claude/agents/reviewer.md`, `.claude/agents/implementer.md`: handoff de rejeições.
- `.claude/commands/*.md` e `.claude/skills/*.md`: comandos de verify, review, auditoria, fix e explicação.
- `scripts/verify-slice.sh` e `scripts/review-slice.sh`: threshold mecânico `R6_REJECT_THRESHOLD=6`.
- `scripts/smoke-test-scripts.sh`: smoke test agora valida que tentativas 1-5 retornam ao loop e a 6ª escala.
- `docs/schemas/verification.schema.json` e `docs/schemas/review.schema.json`: descrição do `next_action`.
- `docs/policies/r6-r7-policy.md`: política sem override alinhada ao novo limite.

## Consequências

Benefícios:

- reduz interrupções ao PM quando o agente ainda consegue corrigir;
- preserva a cadeia de auditoria, porque cada reprovação continua registrada em telemetria;
- mantém zero tolerance e não autoriza merge com findings.

Custos:

- aumenta consumo potencial de tokens por slice;
- pode atrasar uma escalação quando o problema exige decisão de produto desde a primeira reprovação;
- exige disciplina do orquestrador para não mudar de gate antes de zerar findings.

## Rollback

Para retornar ao limite antigo:

1. Criar novo ADR de amendment.
2. Alterar `R6_REJECT_THRESHOLD` para `2` em `scripts/verify-slice.sh` e `scripts/review-slice.sh`.
3. Reverter a redação de R6/R11 em `docs/constitution.md`, `CLAUDE.md`, agentes, commands, skills e schemas.
4. Atualizar smoke tests para validar escalação na 2ª reprovação.
