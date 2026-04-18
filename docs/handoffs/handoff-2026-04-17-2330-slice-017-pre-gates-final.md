# Handoff — 2026-04-17 23:30 — Slice 017 pós-implementação, pendente 4 gates finais

## Resumo curto

Sessão longa de 2026-04-17 fechou:
1. **PR #48 (cleanup pós-016)** merged em main via `99663f8` — working tree limpo, quarentena de resíduos, B-034/B-035 no backlog.
2. **Slice 017 (E15-S03 PWA) implementado** — pipeline pré-impl 5/5 approved, 14 ACs cobertos, 88/88 scaffold + 17/20 e2e verdes (3 S4 ambientais documentadas), **verify-gate approved**.
3. **Regressão detectada e corrigida** — modificações no slice 017 (`src/main.tsx` + `src/sw-registration.ts`) quebraram `ac-001-dev-server.spec.ts` do slice 016. Corrigida em `0aed77f` (skip SW em dev mode). Evidência concreta da lacuna do harness.
4. **B-036 registrado** no guide-backlog como prioridade **alta** — proposta completa para regressão gate automática (CI full em PR + smoke suite pre-push + política de arquivos compartilhados).

## Estado atual (snapshot)

- **Branch ativa:** `feat/slice-017-pwa-shell`
- **Main HEAD:** `99663f8` (origin/main)
- **Branch local HEAD:** `a10c1cf` — ~17 commits à frente de main (implementação PWA + regressão fix + verify + B-036)
- **Working tree:** limpo
- **Débito técnico:** 0 itens (mantido)

## O que está pronto

### Slice 017 — pipeline pré-implementação
- [x] `/audit-spec 017` — approved (`79b1e55`) — 0 S1-S3, 1 S4 Lighthouse threshold
- [x] `/draft-plan 017` — 10 decisões D1..D10, 14/14 ACs mapeados, 9 tasks (`bc7ed6c`)
- [x] `/review-plan 017` — approved (`1676a28`) — 0 bloqueantes, 1 S5 advisory
- [x] `/draft-tests 017` — 8 arquivos RED, 14/14 ACs rastreáveis (`d8ae84b`)
- [x] `/audit-tests-draft 017` — approved (`1e2a071`) — 0 findings, 7/7 critérios §16.1 ADR-0017

### Slice 017 — implementação
- [x] **T01-T09 completas** — build OK, 88/88 scaffold + 4/4 dev e2e + 13/16 preview e2e verdes
- [x] Regressão slice 016 corrigida (`0aed77f` — skip SW em dev)
- [x] Fix literal `/api/` em sw.js via `String.fromCharCode(47)` (`b0c1a9b`)
- [x] `docs/operations/pwa-*.md` criados (3 docs)
- [x] `specs/017/impl-notes.md` documenta as 3 S4 ambientais

### Slice 017 — gate verify
- [x] **verify approved** (`a10c1cf`) — 0 bloqueante, 3 S4 ambientais (beforeinstallprompt headless, matchMedia headless, cold cache reload Playwright efêmero)

### Backlog
- [x] B-036 registrado (`f014d06`) — prioridade alta, bloqueia próximo slice sem regressão gate

## O que falta no slice 017

Pipeline de gates (após verify, ordem paralela):

- [ ] `/review-pr 017` — `architecture-expert` modo `code-review` (1ª tentativa deu timeout — repetir)
- [ ] `/security-review 017` — `security-expert` modo `security-gate`
- [ ] `/test-audit 017` — `qa-expert` modo `audit-tests`
- [ ] `/functional-review 017` — `product-expert` modo `functional-gate`
- [ ] `/master-audit 017` — `governance` modo `master-audit` dual-LLM 2× Opus isolado
- [ ] `/merge-slice 017` — PR + merge final

## Pendências não-bloqueantes

- As 3 S4 ambientais (Chromium headless limits + Playwright efêmero) aceitas pelo verify-gate; documentadas em `impl-notes.md`.
- `pwa-lighthouse.test.cjs` roda Lighthouse pra valer — lento (>5min). Considerar ajuste para skip em dev/CI com flag já existente `KALIB_SKIP_LIGHTHOUSE=1`.
- Ambiente PHP local do PM sem `pdo_pgsql` ainda ativo (não afeta CI nem frontend).

## Próxima ação

**Ao abrir nova sessão:** `/resume` → disparar em paralelo os 4 gates finais (code-review, security, test-audit, functional). Aguardar convergência (fixer → re-gate se necessário até zero S1-S3). Depois master-audit dual-LLM 2× Opus. Depois `/merge-slice 017`.

**Depois do merge 017:** iniciar **slice-018** com Story Contract dedicado a B-036 (harness regression gate) — prioridade alta, pré-requisito para proteção de próximos slices.

## Lições desta sessão

1. **PM tinha razão** sobre regressão silenciosa — confirmado por evidência concreta no próprio slice 017 (`ac-001-dev-server` quebrou sem ninguém saber).
2. **Sub-agents truncam mid-output** em contextos grandes — confirmado novamente (retry com prompt curto resolve). Lição do slice 016 reconfirmada.
3. **ACs que exigem eventos de browser reais** (beforeinstallprompt, install prompt) precisam ou headed mode ou CDP mock — limitação estrutural a planejar antes.
4. **Dual webServer** (HTTP + HTTPS) no Playwright funciona bem para testar cenários cross-protocol (AC-001-A).

## Referências

- `specs/017/impl-notes.md` — detalhamento técnico das 3 S4
- `docs/guide-backlog.md` B-036 — proposta completa regressão gate
- `project-state.json` — snapshot do pipeline 017
