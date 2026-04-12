#!/usr/bin/env bash
# PostToolUse Edit|Write hook — P4 + P8 enforcement.
#
# B-001 (ADR-0001): stack oficial Laravel 11 + Livewire 3 + PostgreSQL 16.
# Este é o RASCUNHO pós-ADR. Para ativar:
#   1. PM em terminal externo: copia este arquivo por cima do selado
#      cp scripts/drafts/post-edit-gate.sh scripts/hooks/post-edit-gate.sh
#   2. Roda: tools/relock.bat  (ou bash scripts/relock-and-commit.sh)
#   3. Confirma RELOCK, aceita o commit "chore(harness): B-001 ..."
#
# Estrutura (P4 + P8):
#   1. Format (nunca bloqueia)
#   2. Lint (bloqueia)
#   3. Type-check incremental (bloqueia)
#   4. Rodar SÓ o teste afetado (P8 — nunca suite full)
#
# Tolerância a ferramentas ausentes: cada passo só roda se a ferramenta
# correspondente existir. Permite operar antes do `composer install` ter
# rodado (ex.: quando o PM acabou de criar o repo e está editando docs).
#
# Linguagens cobertas:
#   - PHP (Laravel, Livewire, Pest) — stack oficial
#   - JS/TS/Vue/CSS (Vite assets do Laravel) — stack oficial
#   - Python, Rust, Go — mantidos para scripts auxiliares e sub-projetos

set -uo pipefail

FILE="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
[ -z "$FILE" ] && exit 0
[ ! -f "$FILE" ] && exit 0

say() { echo "[post-edit-gate] $*" >&2; }
die() { echo "[post-edit-gate BLOCK] $*" >&2; exit 1; }

FILE_NORM="${FILE//\\//}"

# ---------- 1. Format (grátis, não bloqueia) ----------
case "$FILE_NORM" in
  *.php)
    # Pint é o formatter oficial do Laravel (wrap de PHP-CS-Fixer).
    [ -x vendor/bin/pint ] && vendor/bin/pint "$FILE" >/dev/null 2>&1 || true
    ;;
  *.ts|*.tsx|*.js|*.jsx|*.vue|*.json|*.md|*.css|*.scss|*.html|*.yml|*.yaml)
    if command -v npx >/dev/null 2>&1 && [ -f package.json ]; then
      npx prettier --write "$FILE" >/dev/null 2>&1 || true
    fi
    ;;
  *.blade.php)
    # Blade não tem formatter padrão no Laravel; skip por enquanto.
    # Possível futura integração com `blade-formatter` via npx — ADR.
    :
    ;;
  *.py)
    command -v ruff >/dev/null 2>&1 && ruff format "$FILE" >/dev/null 2>&1 || true
    ;;
  *.rs)
    command -v rustfmt >/dev/null 2>&1 && rustfmt "$FILE" >/dev/null 2>&1 || true
    ;;
  *.go)
    command -v gofmt >/dev/null 2>&1 && gofmt -w "$FILE" >/dev/null 2>&1 || true
    ;;
esac

# ---------- 2. Lint (bloqueia) ----------
#
# PHP: Pint é formatter, não lint. PHPStan faz o papel de "lint semântico".
# Então step 2 não tem comando PHP — phpstan vai no step 3 (type-check).
# Pint --test seria redundante (já rodou --write no step 1).
case "$FILE_NORM" in
  *.ts|*.tsx|*.js|*.jsx|*.vue)
    if command -v npx >/dev/null 2>&1 && [ -f package.json ] && [ -f .eslintrc.json -o -f .eslintrc.js -o -f eslint.config.js ]; then
      npx eslint "$FILE" --max-warnings 0 2>&1 || die "ESLint falhou em $FILE"
    fi
    ;;
  *.py)
    if command -v ruff >/dev/null 2>&1; then
      ruff check "$FILE" || die "Ruff falhou em $FILE"
    fi
    ;;
esac

# ---------- 3. Type-check incremental (bloqueia) ----------
case "$FILE_NORM" in
  *.php)
    # PHPStan com Larastan nível 8 (ADR-0001). Roda só no arquivo editado.
    # Se phpstan.neon ou vendor/bin/phpstan não existe ainda (ex.: antes de
    # composer install), skip silencioso — bootstrap-friendly.
    if [ -x vendor/bin/phpstan ] && [ -f phpstan.neon ]; then
      vendor/bin/phpstan analyse "$FILE" --no-progress --error-format=raw --memory-limit=2G 2>&1 \
        || die "PHPStan falhou em $FILE"
    fi
    ;;
  *.ts|*.tsx)
    # TypeScript: tsc --noEmit cobre o projeto todo; custo baixo incremental
    # porque tsc usa --incremental por padrão em projetos com tsconfig.
    if command -v npx >/dev/null 2>&1 && [ -f tsconfig.json ]; then
      npx tsc --noEmit 2>&1 || die "tsc falhou"
    fi
    ;;
esac

# ---------- 4. Mapear arquivo → teste (P8 pirâmide) ----------
#
# Convenções Laravel + Pest (ADR-0001):
#
#   app/Models/User.php               → tests/Unit/Models/UserTest.php
#                                    OU  tests/Feature/Models/UserTest.php
#   app/Http/Controllers/FooCtrl.php → tests/Feature/Http/Controllers/FooCtrlTest.php
#   app/Livewire/Foo.php             → tests/Feature/Livewire/FooTest.php
#   app/Services/FooService.php      → tests/Unit/Services/FooServiceTest.php
#   app/Jobs/FooJob.php              → tests/Unit/Jobs/FooJobTest.php
#                                    OU  tests/Feature/Jobs/FooJobTest.php
#
# Estratégia: procurar o mesmo caminho relativo em tests/Unit/ E tests/Feature/,
# rodar o(s) que existir(em). Cobre ambas as convenções Pest sem forçar uma.
#
# Arquivos de configuração/migration/seed/factory/routes: não têm 1:1 com teste.
# Skip silencioso do step 4 (não bloqueia — ok editar sem teste direto).

TEST_FILES=""
case "$FILE_NORM" in
  # -------- Laravel PHP --------
  app/*.php)
    rel="${FILE_NORM#app/}"
    base="${rel%.php}"
    for kind in Unit Feature; do
      c="tests/${kind}/${base}Test.php"
      [ -f "$c" ] && TEST_FILES="$TEST_FILES $c"
    done
    ;;

  # Arquivos Laravel sem mapeamento 1:1 (config/migration/seed/factory/route/bootstrap).
  # Deixa passar sem teste mapeado — comentário explícito pra não confundir.
  config/*.php|database/migrations/*.php|database/seeders/*.php|database/factories/*.php|routes/*.php|bootstrap/*.php)
    :
    ;;

  # Blade views — sem teste direto (integração é via Feature test do controller/Livewire).
  resources/views/*.blade.php)
    :
    ;;

  # Arquivos de teste editados diretamente: rodar o próprio arquivo.
  tests/*.php)
    TEST_FILES="$FILE_NORM"
    ;;

  # -------- JS/TS (assets Vite do Laravel) --------
  # Convenção: resources/js/foo/bar.ts → tests/js/foo/bar.test.ts
  # (evita colisão com tests/ do Pest que é PHP).
  resources/js/*.ts|resources/js/*.tsx|resources/js/*.js|resources/js/*.jsx|resources/js/*.vue)
    rel="${FILE_NORM#resources/js/}"
    ext="${rel##*.}"
    base="${rel%.*}"
    candidate="tests/js/${base}.test.${ext}"
    [ -f "$candidate" ] && TEST_FILES="$candidate"
    ;;

  # -------- Python (sub-projetos) --------
  src/*.py)
    dir="$(dirname "$FILE_NORM")"
    name="$(basename "$FILE_NORM" .py)"
    candidate="tests/${dir#src/}/test_${name}.py"
    [ -f "$candidate" ] && TEST_FILES="$candidate"
    ;;
esac

# ---------- 5. Rodar SÓ o(s) teste(s) afetado(s) (P8 — nunca suite full) ----------
if [ -n "$TEST_FILES" ]; then
  # Trim leading space
  TEST_FILES="${TEST_FILES# }"
  say "rodando testes afetados: $TEST_FILES"

  # PHP (Pest) — roda todos os arquivos encontrados em uma única invocação.
  case "$TEST_FILES" in
    *Test.php*|*.php)
      if [ -x vendor/bin/pest ]; then
        # shellcheck disable=SC2086
        vendor/bin/pest $TEST_FILES --compact 2>&1 || die "teste falhou: $TEST_FILES"
      elif [ -x vendor/bin/phpunit ]; then
        # Fallback raro: PHPUnit direto. Pest é preferido por ADR-0001.
        # shellcheck disable=SC2086
        vendor/bin/phpunit $TEST_FILES 2>&1 || die "teste falhou: $TEST_FILES"
      else
        say "Pest não instalado (vendor/bin/pest ausente) — skipping $TEST_FILES"
      fi
      ;;
    *.test.ts|*.test.tsx|*.test.js|*.test.jsx)
      if command -v npx >/dev/null 2>&1; then
        npx vitest run "$TEST_FILES" 2>&1 || die "teste falhou: $TEST_FILES"
      fi
      ;;
    test_*.py)
      if command -v pytest >/dev/null 2>&1; then
        pytest "$TEST_FILES" -q 2>&1 || die "teste falhou: $TEST_FILES"
      fi
      ;;
  esac
else
  say "sem teste mapeado para $FILE (ok se for template/config/migration/route/blade/doc)"
fi

say "OK: $FILE"
exit 0
