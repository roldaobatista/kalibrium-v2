# Tasks do slice 009

**Status:** done
**Spec:** `specs/009/spec.md`
**Plan:** `specs/009/plan.md`

---

## Ordem de execucao

### T01 — Modelo de dados, services e factories
- **AC relacionado:** AC-002, AC-004, AC-005, AC-007, AC-010, AC-011, AC-012, AC-013, AC-SEC-002
- **Arquivos:** migrations do slice, `app/Models/*`, `app/Support/Settings/*`, factories novas
- **Definition of done da task:**
  - [x] Convites usam hash de token, expiracao e vinculo pendente no tenant atual
  - [x] Troca de papel recalcula 2FA para gerente/administrativo
  - [x] Desativacao bloqueia ultimo gerente ativo
  - [x] Pedido de upgrade fica registrado sem cobranca real
  - [x] Auditoria nao grava senha, token, TOTP ou recovery code

### T02 — Tela `/settings/users`
- **AC relacionado:** AC-001, AC-008, AC-009, AC-014, AC-SEC-001, AC-SEC-003
- **Arquivos:** `app/Livewire/Pages/Settings/UsersPage.php`, view de usuarios, `routes/web.php`
- **Definition of done da task:**
  - [x] Gerente com 2FA concluido ve usuarios do tenant atual
  - [x] Filtro por busca e papel fica disponivel
  - [x] Usuario sem papel gerente nao ve dados administrativos
  - [x] Tenant suspended permite leitura e bloqueia acoes mutaveis

### T03 — Aceite publico do convite
- **AC relacionado:** AC-003, AC-015, AC-016
- **Arquivos:** `app/Livewire/Pages/Auth/AcceptInvitationPage.php`, view de aceite, `routes/web.php`
- **Definition of done da task:**
  - [x] Convite valido ativa vinculo correto e redireciona para login
  - [x] Convite expirado, usado ou invalido nao ativa vinculo
  - [x] Senha curta ou confirmacao divergente mantem convite pendente

### T04 — Tela `/settings/plans`
- **AC relacionado:** AC-006, AC-007, AC-014, AC-017, AC-018
- **Arquivos:** `app/Livewire/Pages/Settings/PlansPage.php`, view de planos, services de plano
- **Definition of done da task:**
  - [x] Plano, status, uso, limites e modulos aparecem na tela
  - [x] Alertas de 80% e 95% aparecem por severidade
  - [x] Nao gerente autorizado ve informacoes basicas sem botao de upgrade
  - [x] Modo somente leitura bloqueia pedido de upgrade

### T05 — Regressao, seguranca e validacao focada
- **AC relacionado:** AC-001..AC-018, AC-SEC-001..AC-SEC-003
- **Arquivos:** `tests/slice-009/*`, `tests/Pest.php`, `phpunit.xml`
- **Definition of done da task:**
  - [x] `bash scripts/draft-tests.sh 009 --validate`
  - [x] `php artisan test tests/slice-009`
  - [x] `php artisan test tests/slice-007 tests/slice-008 tests/slice-009`
  - [x] `vendor/bin/pint --test ...`
  - [x] `vendor/bin/phpstan analyse ...`

---

## Checklist final (antes de `/verify-slice`)

- [x] Todas as tasks T01..T05 marcadas done
- [x] Todos os AC-tests verdes rodando isolados
- [x] Lint/types verdes no grupo do modulo
- [x] Nenhum hook foi desabilitado
- [ ] Commits com autor valido (R5)
- [x] `specs/009/verification.json` ainda nao existe (sera criado pelo verifier)
