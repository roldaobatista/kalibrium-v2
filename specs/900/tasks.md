# Tasks do slice 900

**Status:** in-progress
**Spec:** `specs/900/spec.md`
**Plan:** `specs/900/plan.md`

---

## Ordem de execucao

### T01 — Configurar runtime PHP + Pest
- **AC relacionado:** AC-003 (pre-requisito)
- **Arquivos:** `composer.json`, `tests/Pest.php`
- **Definition of done da task:**
  - `composer install` funciona
  - `vendor/bin/pest --version` funciona
  - Commit `chore(slice-900): T01 setup PHP + Pest runtime`

### T02 — Criar testes red para greet()
- **AC relacionado:** AC-001, AC-002, AC-003
- **Arquivos:** `tests/Unit/GreetingTest.php`
- **Depende de:** T01
- **Definition of done da task:**
  - Testes existem e falham (red) por falta de implementacao
  - Commit `test(slice-900): T02 AC tests red`

### T03 — Implementar greet() e fazer testes green
- **AC relacionado:** AC-001, AC-002
- **Arquivos:** `src/Utils/Greeting.php`
- **Depende de:** T02
- **Definition of done da task:**
  - `vendor/bin/pest tests/Unit/GreetingTest.php` → exit 0, todos passam
  - Commit `feat(slice-900): T03 implement greet()`

---

## Checklist final (antes de `/verify-slice`)

- [ ] Todas as tasks T01..T03 marcadas done
- [ ] Todos os AC-tests verdes rodando isolados
- [ ] Nenhum hook foi desabilitado
- [ ] Commits com autor valido (R5)
- [ ] `specs/900/verification.json` ainda nao existe
