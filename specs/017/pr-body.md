# Slice 017 — E15-S03: PWA Service Worker + manifest + instalabilidade offline

Slice **017** — pronto para aceitação do PM.

## Gates obrigatórios aprovados

- Verifier (mecânico): **approved** → `specs/017/verification.json`
- Reviewer (estrutural): **approved** → `specs/017/review.json`
- Security-reviewer (segurança/LGPD): **approved** → `specs/017/security-review.json`
- Test-auditor (cobertura/qualidade dos testes): **approved** → `specs/017/test-audit.json`
- Functional-reviewer (produto/UX/ACs): **approved** → `specs/017/functional-review.json`

Os gates obrigatórios foram concluídos com verdict approved e sem findings bloqueantes.

## Acceptance Criteria verificados

0
0 AC(s) no spec — todos passaram no verifier. Detalhes mecânicos em `specs/017/verification.json`.

## Para o PM (linguagem de produto, R12)

Este PR entrega o comportamento descrito em `specs/017/spec.md`. Antes de aceitar o merge:

1. Ler `specs/017/spec.md` (contexto + ACs em português).
2. Se houver UI: testar visualmente no ambiente de staging.
3. Aceitar (merge) ou comentar ajustes — o agente aplica na próxima iteração.

## Arquivos alterados

- .gitignore
- docs/guide-backlog.md
- docs/handoffs/handoff-2026-04-17-2330-slice-017-pre-gates-final.md
- docs/handoffs/latest.md
- docs/operations/pwa-cache-strategy.md
- docs/operations/pwa-icons.md
- docs/operations/pwa-local-https.md
- docs/slice-registry.md
- eslint.config.js
- index.html
- package-lock.json
- package.json
- playwright.config.ts
- project-state.json
- public/icons/icon-192.png
- public/icons/icon-512-maskable.png
- public/icons/icon-512.png
- public/icons/source/kalibrium-logo.svg
- scripts/pwa/generate-icons.mjs
- scripts/pwa/serve-http.mjs
- scripts/pwa/serve-https.mjs
- specs/.current
- specs/017/impl-notes.md
- specs/017/plan-review.json
- specs/017/plan.md
- specs/017/spec-audit.json
- specs/017/spec.md
- specs/017/tasks.md
- specs/017/tests-draft-audit.json
- specs/017/verification.json
- src/main.tsx
- src/sw-registration.ts
- tests/e2e/pwa-api-no-cache.spec.ts
- tests/e2e/pwa-install.spec.ts
- tests/e2e/pwa-offline.spec.ts
- tests/e2e/pwa-service-worker.spec.ts
- tests/scaffold/pwa-cache-version.test.cjs
- tests/scaffold/pwa-icons.test.cjs
- tests/scaffold/pwa-lighthouse.test.cjs
- tests/scaffold/pwa-manifest.test.cjs
- tsconfig.json
- vite.config.ts

---
Gerado por `/merge-slice 017`.
