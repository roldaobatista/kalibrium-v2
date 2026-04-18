# Tests Draft Manifest — Slice 019

**Slice:** 019 — Harness — Hook git nativo + paths filter tenant atualizado
**Autor:** builder (modo: test-writer)
**Data:** 2026-04-18
**Framework escolhido:** **Pest (PHP)** com `shell_exec` / `proc_open` / `file_get_contents`
**Registro phpunit.xml:** `<testsuite name="Slice019"><directory>tests/slice-019</directory></testsuite>` (adicionado)

## Decisão de framework

Conforme **plan-review F-003 (S4 não-bloqueante)** e **R10 (stack só via ADR)**, descartamos
`bats-core` (dependência nova sem ADR). Usamos **Pest+shell_exec**, já autorizado
pelo stack do repo (ADR-0001) e consistente com `tests/slice-011/` (referência para
testes de CI/YAML e shell).

## Arquivos de teste

| AC | Arquivo | Framework | Como rodar |
|---|---|---|---|
| AC-001 | `tests/slice-019/AC001InstallIdempotentTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC001InstallIdempotentTest.php` |
| AC-002 | `tests/slice-019/AC002PrePushNativeBlocksMainTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC002PrePushNativeBlocksMainTest.php` |
| AC-003 | `tests/slice-019/AC003SessionStartReinstallTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC003SessionStartReinstallTest.php` |
| AC-004 | `tests/slice-019/AC004NativeHookBlocksPushTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC004NativeHookBlocksPushTest.php` |
| AC-005 | `tests/slice-019/AC005CiPathsFilterTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC005CiPathsFilterTest.php` |
| AC-006 | `tests/slice-019/AC006CheckerOutputTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC006CheckerOutputTest.php` |
| AC-007 | `tests/slice-019/AC007DocsRequirementsSectionTest.php` | Pest | `vendor/bin/pest tests/slice-019/AC007DocsRequirementsSectionTest.php` |

**Comando agregado:** `vendor/bin/pest --testsuite=Slice019`

## Rastreabilidade (ADR-0017)

Cada arquivo declara:
- `@covers AC-NNN` no docblock de topo
- `@covers AC-NNN` em cada teste individual (docblock)
- `describe('AC-NNN: ...')` envolvendo os testes
- Nome literal do arquivo com `ACNNN` (`AC001InstallIdempotentTest.php`)

4 mecanismos redundantes — passa no `audit-tests-draft` sem ambiguidade.

## ACs cobertos (7/7)

| AC | Foco | Motivo do RED esperado na 1ª execução |
|---|---|---|
| AC-001 | Instalador idempotente (installed / already-current, SHA-256 estável) | `scripts/install-git-hooks.sh` não existe |
| AC-002 | Hook nativo bloqueia main/master, permite feature | `scripts/hooks/pre-push-native.sh` não existe |
| AC-003 | session-start.sh reinstala hook ausente em --silent | session-start.sh ainda não contém bloco 4.7 |
| AC-004 | Push real em main via CLI é bloqueado (cenário E2E com repo temp) | scripts do slice inexistentes |
| AC-005 | ci.yml tem paths novos + sem Livewire | ci.yml atual tem `app/Livewire/**` e não tem Services/Domain/migrations |
| AC-006 | Checker emite `uncovered:` / `[SUSPECT]`, exit 0 | `scripts/check-tenant-filter-coverage.sh` não existe |
| AC-007 | Seção nova em documentation-requirements.md | Seção ainda não adicionada |

## Notas de implementação dos testes

- **AC-003 sandbox:** conforme R-04 do plan, session-start.sh é selado. O teste valida a
  lógica via (i) grep do conteúdo esperado no próprio session-start.sh (`install-git-hooks.sh --silent`,
  `pre-push-native.sh`, `[session-start] reinstalled git hook`) e (ii) simulação do bloco 4.7
  em diretório temp. Verificação real pós-relock é manual pelo PM (T-14).
- **AC-004 isolamento:** usa repo git bare temporário como remote simulado. Cleanup em
  `finally`. Não toca no repo real.
- **AC-006 heurística [SUSPECT]:** cria diretório efêmero `app/TestSuspectSliceOneNine_tmp/`
  com `.php` contendo "tenant", exercita o checker e limpa no `finally`.
- **Cross-platform:** cleanup usa `rmdir /S /Q` em Windows e `rm -rf` em POSIX.
