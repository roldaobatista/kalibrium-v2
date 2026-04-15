# ADR-0014 — Política formal de bypass técnico autorizado

**Status:** Accepted
**Data:** 2026-04-15
**Decisor:** PM (roldaobatista)
**Substitui/Emenda:** acrescenta cláusula à política de `docs/incidents/bloco1-admin-bypass-2026-04-10.md` (contador 5/5) e complementa `§R9` da constitution (zero bypass de gate)
**Origem:** re-auditoria externa dual-LLM em `docs/audits/external/post-5-5-cap-reached-2026-04-15.md §Reconciliação item 6`
**Decisão PM:** `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md §2 Q5`

## Contexto

Em 2026-04-15, durante preparação do próprio dossiê de re-auditoria pós-5/5 bypasses, o orquestrador descobriu um **catch-22**: qualquer push em `main` de material explicitamente autorizado pelo PM (dossiê de auditoria, incidentes, decisões) dispara admin bypass no ruleset do GitHub enquanto `current_user_can_bypass: always` existir (Bloco 5 item 5.3 nunca implementado).

O PM emitiu uma decisão ad-hoc "X.1" classificando esses bypasses como categoria separada que **não consome slot do contador 5/5**. As duas trilhas da re-auditoria (Opus 4.6 e GPT-5.4) concordaram que a classificação é razoável em mérito, mas exigiram **formalização via ADR** porque decisão ad-hoc do PM sem ADR viola §R9 (zero bypass de gate) e o §5 amendment procedure da constituição.

Esta ADR formaliza a política.

## Decisão

### Categoria "bypass técnico autorizado"

É criada uma **categoria separada** de bypass administrativo, distinta de `bypass de governança/produto`:

- **Bypass de governança/produto:** consome slot do contador 5/5 original. Exemplos: merge de PR com findings pendentes, push direto com código de produção.
- **Bypass técnico autorizado:** NÃO consome slot do contador 5/5. Exemplos: push de documentos de auditoria externa, incidentes, decisões PM, retrospectivas.

### Whitelist de paths autorizados

Bypass técnico é aceitável **apenas** para paths dentro desta whitelist selada:

```
docs/audits/**
docs/decisions/**
docs/incidents/**
docs/retrospectives/**
docs/handoffs/**
docs/adr/**              (apenas ADRs puramente declarativos, sem mudança de hook/settings)
```

**Fora desta lista, bypass é automaticamente classificado como governança/produto** e consome slot.

### Requisitos obrigatórios

Para cada bypass técnico:

1. **Autorização explícita do PM** registrada no mesmo dia (no chat, em incidente próprio, ou em decisão PM prévia).
2. **Incidente dedicado** em `docs/incidents/bypass-N-<slug>-YYYY-MM-DD.md` com:
   - Contexto
   - Paths afetados (todos dentro da whitelist)
   - Justificativa do bypass
   - Commit SHA resultante
   - Referência à autorização do PM
3. **Commit** com `Co-Authored-By: Claude` (ou modelo relevante) + autor humano identificável (R5).
4. **Contador separado** em `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Nota de exceção`.

### Limites

- **Validade limitada:** esta política vale apenas enquanto o pré-requisito 1 da decisão pós-auditoria não for totalmente implementado (ruleset endurecido + auto-reviewer + CI obrigatório). Quando o ruleset deixar de ter `current_user_can_bypass: always`, bypass técnico automático deixa de existir — pushes em `docs/**` passam a ser aprovados pelo auto-reviewer e entram no fluxo normal de PR.
- **Sem reclassificação retroativa:** bypasses 1-5 consumidos antes desta ADR permanecem no contador 5/5. Bypasses técnicos 6, 7 e subsequentes entram no contador separado.
- **Máximo 10 bypasses técnicos** entre esta ADR e o cumprimento do pré-requisito 1. Ao atingir o limite, **projeto pausa até ruleset estar pronto**.

### Ordem de precedência

Se houver dúvida se um bypass é técnico ou de governança:

1. Se todos os paths estão na whitelist E há autorização explícita do PM → técnico.
2. Se qualquer path está fora da whitelist → governança/produto (consome slot).
3. Se há ambiguidade → governança/produto por padrão (conservador).

## Consequências

**Positivas:**
- Quebra o catch-22 que impedia preparação de material de auditoria.
- Mantém rastreabilidade — cada bypass técnico tem incidente dedicado.
- Força migração para ruleset endurecido por limite de 10 slots.

**Negativas / riscos:**
- Precedente de categoria separada pode ser abusado para reclassificar bypasses genuínos como "técnicos".
- Whitelist pode crescer silenciosamente se não auditada.

**Mitigações:**
- Auditoria externa trimestral revisa a whitelist e o contador separado.
- Hook `validate-bypass-whitelist.sh` valida que paths de commits pós-bypass estão dentro da whitelist (implementação no pré-requisito 2 da decisão pós-auditoria).
- Retrospectiva humana pós-5-bypasses (pré-requisito 8) documenta por que bypasses técnicos precisaram existir.

## Aprovação

- [x] PM assina via commit desta ADR ratificada em `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md §2 Q5` (commit `f70f376`).
- [ ] Hook `validate-bypass-whitelist.sh` implementado (pré-requisito 2 da decisão pós-auditoria).
- [ ] Constitution §R9 atualizada com referência a esta ADR (a ser feita junto com pré-requisito 2, no mesmo relock).

## Cross-ref

- `docs/audits/external/post-5-5-cap-reached-2026-04-15.md §Reconciliação item 6`
- `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md §2 Q5`
- `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Nota de exceção`
- `docs/incidents/bypass-6-audit-prep-push-2026-04-15.md`
- `docs/incidents/bypass-7-pm-decision-push-2026-04-15.md`
- `docs/incidents/ci-blocked-by-actions-quota-2026-04-15.md` (dependência do pré-requisito 1)
- `docs/constitution.md §R9`
