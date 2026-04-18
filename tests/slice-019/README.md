# tests/slice-019/

Testes RED do slice-019 — Hook git nativo + paths filter tenant atualizado.

## Framework

**Pest (PHP) + `shell_exec`** — escolhido pela recomendação do plan-review F-003 (S4):
usar stack já autorizada (Pest/PHPUnit) em vez de introduzir `bats-core` sem ADR (R10).

Todos os testes residem em arquivos `.php` e usam `shell_exec`, `proc_open`,
`file_get_contents` e `str_contains` para asserções sobre scripts shell, hooks git,
conteúdo de YAML e markdown.

## Como rodar

```bash
# Todos os testes do slice-019
vendor/bin/pest --testsuite=Slice019

# Teste individual
vendor/bin/pest tests/slice-019/AC001InstallIdempotentTest.php
```

## ACs cobertos

| AC | Arquivo |
|---|---|
| AC-001 | `AC001InstallIdempotentTest.php` |
| AC-002 | `AC002PrePushNativeBlocksMainTest.php` |
| AC-003 | `AC003SessionStartReinstallTest.php` |
| AC-004 | `AC004NativeHookBlocksPushTest.php` |
| AC-005 | `AC005CiPathsFilterTest.php` |
| AC-006 | `AC006CheckerOutputTest.php` |
| AC-007 | `AC007DocsRequirementsSectionTest.php` |

## Rastreabilidade (ADR-0017)

Cada teste declara `@covers AC-NNN` no docblock e no `describe()`/nome. Auditor
`audit-tests-draft` valida a vinculação mecanicamente.
