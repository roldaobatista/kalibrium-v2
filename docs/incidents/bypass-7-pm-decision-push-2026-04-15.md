# Incidente — Bypass técnico #7: push de ratificação da decisão PM pós-auditoria

**Data:** 2026-04-15
**Classificação:** bypass técnico sob whitelist `docs/decisions/**` (política X.1 interim)
**Consome slot do contador 5/5?** Não — categoria separada de bypass governança/produto (conforme `bloco1-admin-bypass-2026-04-10.md §Nota de exceção` e recomendação convergente das duas trilhas de re-auditoria)

## Contexto

Após conclusão da re-auditoria externa dual-LLM (trilhas Opus 4.6 + GPT-5.4), o PM ratificou a decisão pré-preenchida em `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md` escolhendo a opção 1 ("eu committo agora") em 2026-04-15.

O push desta ratificação para `main` dispara admin bypass inevitavelmente porque o ruleset continua com `current_user_can_bypass: always` — Bloco 5 item 5.3 continua pendente (é o pré-requisito 1 de `CONDITIONAL_RESUME` que acabou de ser aprovado por esta mesma decisão).

## Arquivos pushados

Todos dentro da whitelist técnica autorizada:

- `docs/audits/external/post-5-5-cap-reached-2026-04-15.md` (atualização: §Conclusões do auditor consolidada)
- `docs/audits/external/audit-trail-opus-2026-04-15.md` (nova — trilha Opus 4.6)
- `docs/audits/external/audit-trail-gpt5-2026-04-15.md` (nova — trilha GPT-5.4)
- `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md` (nova — decisão PM ratificada)
- `docs/incidents/bypass-7-pm-decision-push-2026-04-15.md` (este arquivo)

Nenhum arquivo de código de produção, harness ou selado.

## Justificativa do bypass

1. **Autorização explícita do PM** em 2026-04-15: "1" (opção 1 = "eu committo agora").
2. **Paths sob whitelist** definida em `bypass-6-audit-prep-push-2026-04-15.md §Mitigações` para material de auditoria e decisões PM.
3. **Não reverter o estado pausado.** Este push é parte da preparação — implementar Bloco 5.3 é o pré-requisito 1 da decisão; sem essa decisão ratificada e pushada, não há como iniciar 5.3.
4. **Consenso das duas trilhas:** ambas recomendam decisão PM assinada como pré-requisito explícito (item 7 da Opus e item 7 da GPT-5).

## Pós-condições

- Contador de bypass governança/produto permanece em **5/5**.
- Contador de bypass técnico whitelist passa para **3** (bypasses 6, 7 e o push de `cbf95d1` que abriu a categoria).
- Próxima ação imediata (pré-requisito 1): implementar Bloco 5 item 5.3 do ruleset em terminal externo do PM.

## Mitigação permanente

Este bypass desaparece quando o pré-requisito 1 for implementado. A whitelist técnica só existe porque o ruleset atual não distingue push em `docs/**` de push em código — uma vez que o GitHub App auto-reviewer estiver ativo e `current_user_can_bypass` for removido, pushes em `docs/**` passam a ser aprovados pelo auto-reviewer e não geram bypass.
