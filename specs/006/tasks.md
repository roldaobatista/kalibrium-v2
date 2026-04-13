# Tasks do slice 006

**Status:** implemented
**Spec:** `specs/006/spec.md`
**Plan:** `specs/006/plan.md`

---

## Ordem de execução

Tasks atômicas. Cada uma deve caber em um commit. Executar em ordem.

### T01 — Testes red da base frontend

- **Status:** done
- **AC relacionado:** AC-001 a AC-010, AC-SEC-001
- **Arquivos:** `tests/slice-006/FrontendBaseTest.php`
- **Definition of done da task:**
  - Testes do slice 006 existem e cobrem build, manifest, rota `/ping`, PHPStan e `livewire:list`.
  - Pelo menos um teste falha antes da implementação, demonstrando red state.
  - Commit `test(slice-006): AC tests red`

### T02 — Dependência Livewire e layout base

- **Status:** done
- **AC relacionado:** AC-002, AC-004, AC-005, AC-009, AC-010
- **Arquivos:** `composer.json`, `composer.lock`, `resources/views/layouts/app.blade.php`
- **Depende de:** T01
- **Definition of done da task:**
  - Livewire 4 instalado e descoberto pelo Laravel.
  - Layout base inclui Vite e diretivas Livewire.
  - `php artisan livewire:list` executa sem erro depois da implementação do componente na T03.
  - Commit `feat(slice-006): T02 configura Livewire e layout base`

### T03 — Componente Ping e rota protegida por ambiente

- **Status:** done
- **AC relacionado:** AC-002, AC-007, AC-SEC-001
- **Arquivos:** `app/Livewire/Ping.php`, `resources/views/livewire/ping.blade.php`, `routes/web.php`
- **Depende de:** T02
- **Definition of done da task:**
  - `/ping` retorna HTTP 200 com `Livewire OK` fora de production.
  - `/ping` retorna 404 em production.
  - View do componente não expõe stack trace, configuração sensível ou detalhes internos.
  - Commit `feat(slice-006): T03 adiciona ping Livewire`

### T04 — Build Vite/Tailwind e validação de manifest

- **Status:** done — sem diff próprio; a base Vite/Tailwind existente foi validada no fechamento T05
- **AC relacionado:** AC-001, AC-003, AC-006, AC-008
- **Arquivos:** `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, `package.json`, `package-lock.json`
- **Depende de:** T03
- **Definition of done da task:**
  - `npm run build` retorna exit 0.
  - Manifest tem entradas para CSS e JS com hash de conteúdo.
  - Nenhuma configuração Tailwind 3 desnecessária é introduzida.
  - Commit `feat(slice-006): T04 valida build frontend`

### T05 — Fechamento do slice e gates locais

- **Status:** done
- **AC relacionado:** todos
- **Arquivos:** `specs/006/tasks.md`, arquivos tocados nas tasks anteriores
- **Depende de:** T04
- **Definition of done da task:**
  - `.\vendor\bin\pest tests\slice-006\FrontendBaseTest.php` retorna exit 0.
  - `npm run build` retorna exit 0.
  - `.\vendor\bin\phpstan analyse app\Livewire\Ping.php --level=8 --no-progress` retorna exit 0.
  - `php artisan livewire:list` retorna exit 0 e lista `ping`.
  - `.\vendor\bin\pint --test app\Livewire\Ping.php routes\web.php tests\slice-006\FrontendBaseTest.php` retorna exit 0.
  - Commit `chore(slice-006): T05 fecha frontend base`

## Checklist final (antes de `/verify-slice`)

- [x] Todas as tasks T01..T05 marcadas done
- [x] Todos os AC-tests verdes rodando isolados
- [x] Lint/types verdes no grupo do módulo
- [x] Nenhum hook foi desabilitado
- [x] Commits com autor válido (R5)
- [x] `specs/006/verification.json` ainda não existe (será criado pelo verifier)
