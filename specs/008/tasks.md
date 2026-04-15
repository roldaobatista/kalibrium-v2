# Tasks do slice 008

**Status:** done
**Spec:** `specs/008/spec.md`
**Plan:** `specs/008/plan.md`

---

## Ordem de execução

### T01 — Modelo de dados do laboratorio
- **AC relacionado:** AC-002, AC-003, AC-005, AC-006, AC-012, AC-SEC-001
- **Arquivos:** migrations `2026_04_14_000200` a `2026_04_14_000230`, `app/Models/Tenant.php`, `app/Models/Company.php`, `app/Models/Branch.php`, `app/Models/TenantAuditLog.php`, factories do slice.
- **Status:** done
- **Evidência:** `php artisan test tests/slice-008` -> exit 0, 19 passed.

### T02 — Regra de CNPJ e atualizacao transacional
- **AC relacionado:** AC-002, AC-003, AC-004, AC-006, AC-007, AC-008, AC-011, AC-012
- **Arquivos:** `app/Rules/Cnpj.php`, `app/Support/Tenancy/CurrentTenantResolver.php`, `app/Support/Tenancy/TenantSettingsUpdater.php`, `app/Support/Tenancy/TenantAuditRecorder.php`.
- **Status:** done
- **Evidência:** `vendor/bin/phpstan analyse ... --level=8 --no-progress` -> exit 0, No errors.

### T03 — Contexto de tenant e rotas protegidas
- **AC relacionado:** AC-005, AC-009, AC-010, AC-011, AC-SEC-001, AC-SEC-003
- **Arquivos:** `app/Http/Middleware/SetCurrentTenantContext.php`, `bootstrap/app.php`, `routes/web.php`, `app/Http/Controllers/TenantSettingsController.php`.
- **Status:** done
- **Evidência:** `php artisan test tests/slice-007/AuthLoginTest.php tests/slice-007/AuthTwoFactorTest.php --stop-on-failure` -> exit 0, 35 passed.

### T04 — Tela `/settings/tenant`
- **AC relacionado:** AC-001, AC-009, AC-010, AC-SEC-002
- **Arquivos:** `app/Livewire/Pages/Settings/TenantPage.php`, `resources/views/livewire/pages/settings/tenant-page.blade.php`.
- **Status:** done
- **Evidência:** `php artisan test tests/slice-008` -> exit 0, 19 passed.

### T05 — Ajuste dos AC-tests do slice
- **AC relacionado:** AC-007 e estabilidade dos testes do slice 008.
- **Arquivos:** `tests/slice-008/TestHelpers.php`, testes do diretório `tests/slice-008`, `phpunit.xml`.
- **Status:** done
- **Evidência:** `bash scripts/draft-tests.sh 008 --validate` -> exit 0, 4 arquivos de teste, 12 ACs cobertos, nenhum TODO/skip/FIXME.

---

## Checklist final (antes de `/verify-slice`)

- [x] Todas as tasks T01..T05 marcadas done
- [x] Todos os AC-tests verdes rodando isolados
- [x] Lint/types verdes no grupo do módulo
- [x] Nenhum hook foi desabilitado
- [x] Commits com autor válido (R5) pendentes de checkpoint desta implementação
- [x] `specs/008/verification.json` ainda não existe (será criado pelo verifier)
