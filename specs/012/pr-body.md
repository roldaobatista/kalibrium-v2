# Slice 012 — E03-S01a: Model cliente + validação CNPJ/CPF + unicidade

Slice **012** — pronto para aceitação do PM.

## Gates obrigatórios aprovados

- Verifier (mecânico): **approved** → `specs/012/verification.json`
- Reviewer (estrutural): **approved** → `specs/012/review.json`
- Security-reviewer (segurança/LGPD): **approved** → `specs/012/security-review.json`
- Test-auditor (cobertura/qualidade dos testes): **approved** → `specs/012/test-audit.json`
- Functional-reviewer (produto/UX/ACs): **approved** → `specs/012/functional-review.json`

Os gates obrigatórios foram concluídos com verdict approved e sem findings bloqueantes.

## Acceptance Criteria verificados

9 AC(s) no spec — todos passaram no verifier. Detalhes mecânicos em `specs/012/verification.json`.

## Para o PM (linguagem de produto, R12)

Este PR entrega o comportamento descrito em `specs/012/spec.md`. Antes de aceitar o merge:

1. Ler `specs/012/spec.md` (contexto + ACs em português).
2. Se houver UI: testar visualmente no ambiente de staging.
3. Aceitar (merge) ou comentar ajustes — o agente aplica na próxima iteração.

## Arquivos alterados

- .gitignore
- app/Http/Controllers/ClienteController.php
- app/Http/Requests/StoreClienteRequest.php
- app/Http/Resources/ClienteResource.php
- app/Models/Cliente.php
- app/Policies/ClientePolicy.php
- app/Providers/AppServiceProvider.php
- app/Rules/Cnpj.php
- app/Rules/CnpjFormat.php
- app/Rules/Cpf.php
- app/Support/Tenancy/TenantRole.php
- config/tenancy.php
- database/factories/ClienteFactory.php
- database/migrations/2026_04_16_000100_create_clientes_table.php
- database/seeders/ClienteSeeder.php
- database/seeders/DatabaseSeeder.php
- docs/architecture/api-contracts/E03/clientes.md
- docs/explanations/slice-012.md
- docs/handoffs/handoff-2026-04-16-0430.md
- docs/handoffs/latest.md
- phpunit.xml
- project-state.json
- routes/web.php
- specs/012/functional-review.json
- specs/012/plan-review.json
- specs/012/plan.md
- specs/012/review.json
- specs/012/security-review.json
- specs/012/spec-audit.json
- specs/012/spec.md
- specs/012/tasks.md
- specs/012/test-audit.json
- specs/012/verification.json
- tests/TenantIsolationTestCase.php
- tests/slice-012/ClienteCreationTest.php
- tests/slice-012/ClienteMigrationTest.php
- tests/slice-012/ClienteSoftDeleteTest.php
- tests/slice-012/ClienteUniquenessTest.php
- tests/slice-012/CnpjClienteValidationTest.php
- tests/slice-012/CpfValidationTest.php

---
Gerado por `/merge-slice 012`.
