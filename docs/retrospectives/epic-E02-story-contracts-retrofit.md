# Retrofit de Story Contracts — E02

**Data:** 2026-04-16
**Motivacao:** Fechamento de ER-001 e ER-005 da epic-retrospective E02
**Agente:** story-decomposer (modo retroativo)

---

## Resumo executivo

Os 6 Story Contracts de E02-S01 a E02-S06 foram reescritos formalmente com estrutura padrao (frontmatter, objetivo, escopo, ACs em Given/When/Then com evidencia file:line, gaps explicitados). Os stubs anteriores continham apenas prosa descritiva sem ACs testáveis, violando P2/R13 historicamente.

---

## Tabela resumo por story

| Story | ACs documentados | Covered | Partial | Gap |
|---|---|---|---|---|
| E02-S01 — Scaffold stancl/tenancy + RLS | 5 | 5 | 0 | 0 |
| E02-S02 — Entidades Tenant/Empresa/Filial | 6 | 6 | 0 | 0 |
| E02-S03 — Autenticacao, 2FA, recuperacao | 14 | 14 | 0 | 0 |
| E02-S04 — RBAC + spatie/laravel-permission | 7 | 7 | 0 | 0 |
| E02-S05 — Ciclo de vida do tenant | 6 | 6 | 0 | 1 |
| E02-S06 — Motor de planos e feature gates | 8 | 8 | 0 | 1 |
| **TOTAL** | **46** | **46** | **0** | **2** |

---

## Lista consolidada de gaps reais

### GAP-S05-001 — Automacao da transicao trial → expired/suspended
- **Story:** E02-S05
- **AC correspondente no epic.md:** "Estados: trial (30 dias) → ativo → [dunning] → suspenso"
- **Evidencia de ausencia:** nenhum job, command ou scheduler encontrado em `app/Jobs/`, `app/Console/Commands/` ou `app/Console/Kernel.php` relacionado a expiracao de trial ou transicao automatica de estado
- **Situacao:** o campo `trial_ends_at` existe no model `Tenant`, mas a transicao automatizada quando o prazo expira nao foi implementada
- **Severidade:** media

### GAP-S06-001 — Upgrade/downgrade com calculo de pro-rata automatico (ER-005c)
- **Story:** E02-S06
- **AC correspondente no epic.md linha 34:** "Upgrade/downgrade de plano com cálculo pro-rata automático"
- **Evidencia de ausencia:** `grep -r "prorate\|pro.rata\|ProRat" app/` retornou zero resultados; `specs/009/spec.md` coloca explicitamente em fora de escopo
- **Situacao:** o slice 009 registra apenas pedidos de upgrade; nenhum calculo de pro-rata foi implementado
- **Severidade:** alta — requisito do epic.md nao implementado

---

## Findings vs. previsao da epic-retrospective

| Finding previsto | Resultado real |
|---|---|
| ER-005(a): "2FA obrigatório para gerente/administrativo sem enforcement por papel" | **NAO E GAP.** `tests/slice-007/AuthLoginTest.php:70` testa explicitamente que gerente e administrativo exigem 2FA *mesmo sem flag manual* no vinculo. `RequireTwoFactorSession` middleware implementa. AC-002 de E02-S03 documenta e prova. |
| ER-005(b): "Alertas em 80% e 95% dos limites sem teste" | **NAO E GAP.** `tests/slice-009/PlansPageTest.php:258` e `:284` testam ambos os niveis para cada metrica (usuarios, OS mensal, armazenamento). `PlanSummaryService::alerts()` implementa em `app/Support/Settings/PlanSummaryService.php:202`. AC-002 e AC-003 de E02-S06 documentam. |
| ER-005(c): "Upgrade/downgrade com pro-rata automatico sem implementacao" | **CONFIRMADO GAP.** Nenhum codigo ou teste encontrado. Explicitamente fora de escopo no specs/009. GAP-S06-001 documentado. |
| ER-001: "stories S01-S06 sem ACs formais" | **RESOLVIDO.** 46 ACs formais criados com evidencia file:line para todos os 6 contratos. |

---

## Recomendacao de proximo passo

### Gaps que precisam de implementacao nova (antes do go-live)

**GAP-S06-001 (Alta):** implementar calculo de pro-rata em upgrade/downgrade. Requer decisao de produto: credito interno (sem gateway) vs. integracao com gateway de pagamento. Sugerido como nova story E02-S06-gap ou story em epico de billing pós-MVP.

**GAP-S05-001 (Media):** implementar job/command de expiracao de trial (`php artisan tenants:expire-trials`). Simples de implementar; recomendado antes do go-live para evitar tenants em `trial` indefinidamente.

### Divida aceitavel para MVP fechado

Ambos os gaps sao aceitáveis se o go-live for em ambiente controlado (tenants gerenciados manualmente pela equipe Kalibrium). Tornam-se bloqueadores quando o produto for aberto para auto-registro de tenants.

---

## Arquivos criados/modificados

- `epics/E02/stories/E02-S01.md` — reescrito (5 ACs, 0 gaps)
- `epics/E02/stories/E02-S02.md` — reescrito (6 ACs, 0 gaps)
- `epics/E02/stories/E02-S03.md` — reescrito (14 ACs, 0 gaps, ER-005a refutado)
- `epics/E02/stories/E02-S04.md` — reescrito (7 ACs, 0 gaps)
- `epics/E02/stories/E02-S05.md` — reescrito (6 ACs, 1 gap: GAP-S05-001)
- `epics/E02/stories/E02-S06.md` — reescrito (8 ACs, 1 gap: GAP-S06-001)
- `epics/E02/stories/INDEX.md` — atualizado (secao retroativa removida, tabela de gaps adicionada)
