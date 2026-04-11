# Bloco 1.5 — Fundação de Produto (tracker)

**Origem:** `docs/audits/meta-audit-completeness-2026-04-10-action-plan.md §2`
**Status geral:** Estado 1 (13/15) ✅ — 2026-04-10
**Estado 2:** pendente do Bloco 2 (faltam 1.5.11 e 1.5.14)
**Relatório de execução:** `docs/reports/execution-meta-audit-2-2026-04-10-session01.md`

## Legenda

- `[x]` = completo (data + commit curto)
- `[ ]` = não iniciado
- `[⏸]` = pending-block-2 (dependência legítima do Bloco 2)

## Estado 1 — destrava o Bloco 2 (13/15 commitados)

### Nível 0 — sem dependências

- [x] 1.5.1 `docs/product/ideia-v1.md` — 2026-04-10 (`5db89cf`)
- [x] 1.5.6 `docs/product/glossary-pm.md` — 2026-04-10 (`2d4b775`)
- [x] 1.5.12 `docs/reference/historical/roles-from-bmad.md` — 2026-04-10 (`ed241d8`, via item X4)
- [x] 1.5.13 `docs/constitution.md` §4+§5 (R1-R10 → R1-R12) — 2026-04-10 (`6cc9c2f`, via item X2)

### Nível 1 — dependem do Nível 0

- [x] 1.5.2 `docs/product/mvp-scope.md` — 2026-04-10 (`84d9d4a`)
- [x] 1.5.7 `docs/product/laboratorio-tipo.md` — 2026-04-10 (`6497525`)

### Nível 2 — dependem do Nível 1

- [x] 1.5.3 `docs/product/personas.md` — 2026-04-10 (`ac73e9d`)
- [x] 1.5.5 `docs/product/nfr.md` — 2026-04-10 (`ec3f7be`)
- [x] 1.5.10 `docs/compliance/out-of-scope.md` — 2026-04-10 (`7647bb1`)

### Nível 3 — dependem do Nível 2

- [x] 1.5.4 `docs/product/journeys.md` — 2026-04-10 (`683e7f0`)
- [x] 1.5.8 `docs/architecture/foundation-constraints.md` — 2026-04-10 (`4230d2a`)
- [x] 1.5.9 `docs/finance/operating-budget.md` — 2026-04-10 (`8a96637`)
- [x] 1.5.15 `docs/product/pricing-assumptions.md` — 2026-04-10 (`847d017`)

### Nível 4 — depende do Nível 3

- [x] 1.5.0 `README.md` raiz — 2026-04-10 (`adb37bc`)

## Estado 2 — fecha definitivamente (2 itens pending-block-2)

- [⏸] 1.5.11 `docs/TECHNICAL-DECISIONS.md` preenchido + gate `wc -l ≥ 20` no `session-start.sh` — aguarda item 2.7 do Bloco 2 (ADRs 0001, 0003-0006).
- [⏸] 1.5.14 Selar `docs/decisions/*.md` no MANIFEST — aguarda relock manual do PM (procedimento §9 do CLAUDE.md). Instrução em `docs/reports/pm-manual-actions-2026-04-10.md`.

## Consequência prática

O Estado 1 (13/15) destrava o Bloco 2. Quando o Bloco 2 rodar o item 2.7, o 1.5.11 pode ser executado. Quando o PM fizer o relock manual (C4 → 1.5.14), o Bloco 1.5 fecha em 15/15.
