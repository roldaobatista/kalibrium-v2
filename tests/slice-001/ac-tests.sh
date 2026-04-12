#!/usr/bin/env bash
# Testes de aceite do slice 001 — Scaffold Laravel 13 com dependências core
# Cada AC do spec (specs/001/spec.md) tem um teste correspondente.
# Todos nascem RED (falhando) — ficam GREEN após implementação pelo sub-agent implementer.
#
# Uso: bash tests/slice-001/ac-tests.sh
# Exit 0 = todos os ACs passaram. Exit 1 = ao menos um AC falhou.

set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

PASS=0
FAIL=0
TOTAL=5

pass() { echo "[PASS] $1"; PASS=$((PASS + 1)); }
fail() { echo "[FAIL] $1"; FAIL=$((FAIL + 1)); }

echo "=== Slice 001 — AC Tests ==="
echo "Diretório: $REPO_ROOT"
echo ""

# ---------------------------------------------------------------------------
# AC-001: php artisan serve inicia sem erros e responde HTTP 200 na rota /
# Pré-condição: artisan existe (criado pelo scaffold Laravel)
# ---------------------------------------------------------------------------
echo "--- AC-001: php artisan serve responde HTTP 200 na rota / ---"

if [ ! -f "$REPO_ROOT/artisan" ]; then
    fail "AC-001: arquivo artisan não encontrado (Laravel não scaffoldado)"
else
    php "$REPO_ROOT/artisan" serve --port=18001 &>/tmp/kalibrium-serve.log &
    SERVE_PID=$!

    # Aguarda servidor subir (máx 8s, sem travar)
    HTTP_CODE=""
    for i in $(seq 1 8); do
        sleep 1
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:18001/ 2>/dev/null || true)
        [ "$HTTP_CODE" = "200" ] && break
    done

    kill "$SERVE_PID" 2>/dev/null || true
    wait "$SERVE_PID" 2>/dev/null || true

    if [ "$HTTP_CODE" = "200" ]; then
        pass "AC-001: HTTP 200 recebido na rota /"
    else
        fail "AC-001: esperado HTTP 200, obteve '${HTTP_CODE:-sem resposta}'"
    fi
fi

echo ""

# ---------------------------------------------------------------------------
# AC-002: composer test executa Pest e retorna exit 0
# Pré-condições:
#   - bootstrap/app.php existe (scaffold Laravel presente)
#   - vendor/bin/pest existe (pestphp/pest instalado)
#   - script "test" declarado no composer.json apontando para pest
# ---------------------------------------------------------------------------
echo "--- AC-002: composer test executa Pest com exit 0 ---"

if [ ! -f "$REPO_ROOT/bootstrap/app.php" ]; then
    fail "AC-002: bootstrap/app.php não encontrado (Laravel não scaffoldado)"
elif [ ! -f "$REPO_ROOT/vendor/bin/pest" ]; then
    fail "AC-002: vendor/bin/pest não encontrado (pestphp/pest não instalado)"
elif ! grep -q '"test"' "$REPO_ROOT/composer.json"; then
    fail "AC-002: script 'test' não declarado no composer.json"
else
    composer --working-dir="$REPO_ROOT" test --no-interaction 2>&1
    TEST_EXIT=$?
    if [ "$TEST_EXIT" -eq 0 ]; then
        pass "AC-002: composer test retornou exit 0"
    else
        fail "AC-002: composer test retornou exit $TEST_EXIT"
    fi
fi

echo ""

# ---------------------------------------------------------------------------
# AC-003: ./vendor/bin/phpstan analyse --level=8 retorna exit 0
# Pré-condições:
#   - bootstrap/app.php existe (scaffold Laravel presente — sem ele phpstan
#     não tem código Laravel para analisar e o teste seria tautológico)
#   - phpstan.neon contém a extensão Larastan (nunomaduro/larastan), que é
#     o que o spec exige; o phpstan.neon atual do harness aponta para src/
#     sem Larastan — logo falha na ausência do scaffold correto
#   - vendor/bin/phpstan existe
# ---------------------------------------------------------------------------
echo "--- AC-003: phpstan analyse --level=8 retorna exit 0 (Laravel + Larastan) ---"

if [ ! -f "$REPO_ROOT/bootstrap/app.php" ]; then
    fail "AC-003: bootstrap/app.php não encontrado (scaffold Laravel ausente)"
elif [ ! -f "$REPO_ROOT/vendor/bin/phpstan" ]; then
    fail "AC-003: vendor/bin/phpstan não encontrado"
elif [ ! -f "$REPO_ROOT/phpstan.neon" ]; then
    fail "AC-003: phpstan.neon não encontrado"
elif ! grep -qE 'larastan|nunomaduro' "$REPO_ROOT/phpstan.neon"; then
    fail "AC-003: phpstan.neon não inclui extensão Larastan (nunomaduro/larastan) exigida pelo spec"
else
    "$REPO_ROOT/vendor/bin/phpstan" analyse --level=8 --no-progress 2>&1
    PHPSTAN_EXIT=$?
    if [ "$PHPSTAN_EXIT" -eq 0 ]; then
        pass "AC-003: phpstan --level=8 retornou exit 0"
    else
        fail "AC-003: phpstan --level=8 retornou exit $PHPSTAN_EXIT"
    fi
fi

echo ""

# ---------------------------------------------------------------------------
# AC-004: ./vendor/bin/pint --test retorna exit 0
# Pré-condições:
#   - bootstrap/app.php existe (scaffold Laravel presente — sem código
#     Laravel real o pint --test não exerce o comportamento exigido pelo AC)
#   - pint.json existe com configuração do slice (não o do harness)
#   - pint.json contém preset Laravel (exigido pelo spec: "PSR-12 + opinionado Laravel")
# ---------------------------------------------------------------------------
echo "--- AC-004: pint --test retorna exit 0 (código Laravel formatado) ---"

if [ ! -f "$REPO_ROOT/bootstrap/app.php" ]; then
    fail "AC-004: bootstrap/app.php não encontrado (scaffold Laravel ausente)"
elif [ ! -f "$REPO_ROOT/vendor/bin/pint" ]; then
    fail "AC-004: vendor/bin/pint não encontrado"
elif [ ! -f "$REPO_ROOT/pint.json" ]; then
    fail "AC-004: pint.json não encontrado"
elif ! grep -q '"laravel"' "$REPO_ROOT/pint.json"; then
    fail "AC-004: pint.json não declara preset 'laravel' exigido pelo spec"
else
    "$REPO_ROOT/vendor/bin/pint" --test 2>&1
    PINT_EXIT=$?
    if [ "$PINT_EXIT" -eq 0 ]; then
        pass "AC-004: pint --test retornou exit 0"
    else
        fail "AC-004: pint --test retornou exit $PINT_EXIT"
    fi
fi

echo ""

# ---------------------------------------------------------------------------
# AC-005: .env.example contém todas as variáveis obrigatórias
# Grupos verificados individualmente: APP_*, DB_*, REDIS_*, QUEUE_CONNECTION,
# MAIL_*, HORIZON_*
# ---------------------------------------------------------------------------
echo "--- AC-005: .env.example contém todas as variáveis obrigatórias ---"

ENV_EXAMPLE="$REPO_ROOT/.env.example"

if [ ! -f "$ENV_EXAMPLE" ]; then
    fail "AC-005: .env.example não encontrado"
else
    AC005_OK=true

    # Grupo APP_*
    for VAR in APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL; do
        if ! grep -q "^${VAR}=" "$ENV_EXAMPLE"; then
            echo "  AUSENTE: $VAR"
            AC005_OK=false
        fi
    done

    # Grupo DB_*
    for VAR in DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
        if ! grep -q "^${VAR}=" "$ENV_EXAMPLE"; then
            echo "  AUSENTE: $VAR"
            AC005_OK=false
        fi
    done

    # Grupo REDIS_*
    for VAR in REDIS_HOST REDIS_PASSWORD REDIS_PORT; do
        if ! grep -q "^${VAR}=" "$ENV_EXAMPLE"; then
            echo "  AUSENTE: $VAR"
            AC005_OK=false
        fi
    done

    # QUEUE_CONNECTION (variável única obrigatória)
    if ! grep -q "^QUEUE_CONNECTION=" "$ENV_EXAMPLE"; then
        echo "  AUSENTE: QUEUE_CONNECTION"
        AC005_OK=false
    fi

    # Grupo MAIL_*
    for VAR in MAIL_MAILER MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME; do
        if ! grep -q "^${VAR}=" "$ENV_EXAMPLE"; then
            echo "  AUSENTE: $VAR"
            AC005_OK=false
        fi
    done

    # Grupo HORIZON_*
    for VAR in HORIZON_DOMAIN HORIZON_PATH; do
        if ! grep -q "^${VAR}=" "$ENV_EXAMPLE"; then
            echo "  AUSENTE: $VAR"
            AC005_OK=false
        fi
    done

    if [ "$AC005_OK" = true ]; then
        pass "AC-005: todas as variáveis obrigatórias presentes no .env.example"
    else
        fail "AC-005: .env.example incompleto — variáveis acima estão ausentes"
    fi
fi

echo ""

# ---------------------------------------------------------------------------
# Resultado final
# ---------------------------------------------------------------------------
echo "=== Resultado: $PASS/$TOTAL passed, $FAIL/$TOTAL failed ==="

[ "$FAIL" -eq 0 ] && exit 0 || exit 1
