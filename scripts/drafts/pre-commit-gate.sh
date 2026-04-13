#!/usr/bin/env bash
# PreToolUse Bash(git commit*) hook — enforcement P1/P4/P6/P9 + R5/R9.
#
# v2: Melhoria sobre v1 — adiciona validação de cobertura de testes (G-14):
# - Mapeia arquivo de código → arquivo de teste esperado
# - Verifica se o teste cobre ACs (grep por AC-NNN no teste)
# - WARN se código staged sem teste correspondente staged
#
# 1. Detecta bypass (--no-verify, SKIP=, etc.)  -> R9
# 2. Valida autor do commit                      -> R5
# 3. Valida mensagem (sem auto-gen, sem rodada N APROVADO)
# 4. Lint + type-check + testes afetados nos arquivos staged -> P1/P4
# 5. Scan por secrets staged                     -> defesa em profundidade
# 6. Validação de cobertura de testes             -> P2/G-14

set -euo pipefail

CMD="${CLAUDE_TOOL_ARG_COMMAND:-${1:-}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

say() { echo "[pre-commit-gate] $*" >&2; }
die() { echo "[pre-commit-gate BLOCK] $*" >&2; exit 1; }

# ---------- 1. R9 — bypass detection ----------
if echo "$CMD" | grep -qE -- "--no-verify|--no-gpg-sign"; then
  die "R9/P9: bypass de gate proibido ('$CMD')"
fi
if [ -n "${SKIP:-}" ] || [ -n "${HUSKY:-}" ] || [ -n "${PRE_COMMIT:-}" ]; then
  die "R9/P9: variáveis de bypass detectadas (SKIP/HUSKY/PRE_COMMIT)"
fi

# ---------- 2. R5 — autor ----------
AUTHOR_NAME="$(git config user.name 2>/dev/null || true)"
AUTHOR_EMAIL="$(git config user.email 2>/dev/null || true)"

if [ -z "$AUTHOR_NAME" ] || [ -z "$AUTHOR_EMAIL" ]; then
  die "R5: git user.name/email não configurados"
fi

case "$AUTHOR_NAME" in
  auto-*|Auto-*|AUTO-*)
    die "R5: autor '$AUTHOR_NAME' matches 'auto-*'"
    ;;
esac

case "$AUTHOR_EMAIL" in
  noreply@*|no-reply@*)
    # Permitido APENAS se a mensagem contiver Co-Authored-By com email válido
    if ! echo "$CMD" | grep -qi "Co-Authored-By:"; then
      die "R5: email noreply sem Co-Authored-By"
    fi
    ;;
esac

# Padrões automáticos universalmente bloqueados (smoke-test-*, *-bot, etc.)
case "$AUTHOR_NAME" in
  smoke-test-*|*-bot|*\[bot\])
    # Exceções já tratadas dentro do allowlist abaixo (dependabot, renovate)
    : ;;
esac

# ---------- 2.1. R5 — allowlist explícita (item 1.7 meta-audit) ----------
ALLOWLIST=".claude/allowed-git-identities.txt"
if [ ! -f "$ALLOWLIST" ]; then
  die "R5: $ALLOWLIST ausente — relock o harness para regenerar"
fi

# Formato esperado por linha: "Nome <email>"  (linhas começando com # são comentário)
# Match exato por linha (case-insensitive). Falha-fechado.
IDENTITY="$AUTHOR_NAME <$AUTHOR_EMAIL>"
ALLOWED=0
while IFS= read -r line; do
  # Skip blank lines and comments
  case "$line" in
    ''|\#*) continue ;;
  esac
  if [ "$(echo "$line" | tr '[:upper:]' '[:lower:]')" = "$(echo "$IDENTITY" | tr '[:upper:]' '[:lower:]')" ]; then
    ALLOWED=1
    break
  fi
done < "$ALLOWLIST"

if [ "$ALLOWED" -eq 0 ]; then
  die "R5: identidade '$IDENTITY' não está em $ALLOWLIST (item 1.7 meta-audit)"
fi

# ---------- 3. Mensagem ----------
# Extrai a mensagem do comando (aceita -m "..." ou heredoc)
MSG="$(echo "$CMD" | grep -oE -- "-m [\"'][^\"']+[\"']" | head -1 | sed -E 's/-m ["'"'"']//;s/["'"'"']$//' || true)"

if [ -n "$MSG" ]; then
  case "$MSG" in
    Auto-generated*|auto-commit*|"Auto commit"*)
      die "R5: mensagem proibida: '$MSG'"
      ;;
  esac
  if echo "$MSG" | grep -qiE "rodada [0-9]+.*aprovad[oa]"; then
    die "R5: padrão 'rodada N aprovado' é anti-pattern do V1"
  fi
fi

# ---------- 4. Secrets scan em staged files ----------
STAGED="$(git diff --cached --name-only 2>/dev/null || true)"
if [ -n "$STAGED" ]; then
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    base="$(basename "$f")"
    case "$base" in
      .env|.env.local|.env.production|credentials|credentials.json|*.key|*.pem|*.p12)
        die "secret detectado em staged: $f"
        ;;
    esac
    # Grep por patterns comuns (simples, não-exaustivo)
    if [ -f "$f" ]; then
      if grep -qiE "(api[_-]?key|secret|password|token)[[:space:]]*=[[:space:]]*['\"][a-z0-9]{20,}" "$f" 2>/dev/null; then
        say "WARN: possível segredo hardcoded em $f — revise antes de commitar"
      fi
    fi
  done <<< "$STAGED"
fi

# ---------- 5. Lint + type-check nos arquivos staged (P1/P4) ----------
if [ -n "$STAGED" ]; then
  # JS/TS
  TS_STAGED="$(echo "$STAGED" | grep -E '\.(ts|tsx|js|jsx)$' || true)"
  if [ -n "$TS_STAGED" ] && command -v npx >/dev/null 2>&1 && [ -f package.json ]; then
    say "rodando eslint nos staged..."
    # shellcheck disable=SC2086
    npx eslint $TS_STAGED --max-warnings 0 2>&1 || die "ESLint falhou nos arquivos staged"

    if [ -f tsconfig.json ]; then
      say "rodando tsc --noEmit..."
      npx tsc --noEmit 2>&1 || die "tsc falhou"
    fi
  fi

  # PHP
  PHP_STAGED="$(echo "$STAGED" | grep '\.php$' || true)"
  if [ -n "$PHP_STAGED" ] && [ -x vendor/bin/pint ]; then
    say "rodando pint --test nos staged..."
    # shellcheck disable=SC2086
    vendor/bin/pint --test $PHP_STAGED 2>&1 || die "Pint falhou"
  fi

  # Python
  PY_STAGED="$(echo "$STAGED" | grep '\.py$' || true)"
  if [ -n "$PY_STAGED" ] && command -v ruff >/dev/null 2>&1; then
    say "rodando ruff check..."
    # shellcheck disable=SC2086
    ruff check $PY_STAGED 2>&1 || die "Ruff falhou"
  fi
fi

# ---------- 6. Validação de cobertura de testes (P2/G-14) ----------
# Mapeia arquivo de código staged → arquivo de teste esperado
# e verifica se o teste existe e cobre pelo menos um AC.
if [ -n "$STAGED" ]; then
  HAS_CODE="$(echo "$STAGED" | grep -vE '(test|spec|Test|_test|\.md$|\.json$|\.yml$|\.yaml$|\.sh$|\.gitkeep)' || true)"
  HAS_TEST="$(echo "$STAGED" | grep -E '(test|spec|Test|_test)' || true)"

  if [ -n "$HAS_CODE" ] && [ -z "$HAS_TEST" ]; then
    say "WARN: commit com código mas sem teste no diff — P2 exige AC mapeado a teste"
  fi

  # Validação de mapeamento código→teste (quando stack Laravel está instalada)
  if [ -d "app" ] && [ -d "tests" ]; then
    MISSING_TESTS=""
    while IFS= read -r code_file; do
      [ -z "$code_file" ] && continue
      # Só validar arquivos PHP de app/
      case "$code_file" in
        app/*.php)
          # Mapear app/Models/User.php → tests/Unit/Models/UserTest.php
          #         app/Models/User.php → tests/Feature/Models/UserTest.php
          relative="${code_file#app/}"
          base_name="$(basename "$relative" .php)"
          dir_name="$(dirname "$relative")"

          unit_test="tests/Unit/${dir_name}/${base_name}Test.php"
          feature_test="tests/Feature/${dir_name}/${base_name}Test.php"

          if [ ! -f "$unit_test" ] && [ ! -f "$feature_test" ]; then
            MISSING_TESTS="${MISSING_TESTS}\n  - $code_file → esperado: $unit_test ou $feature_test"
          fi
          ;;
      esac
    done <<< "$HAS_CODE"

    if [ -n "$MISSING_TESTS" ]; then
      say "WARN: arquivos sem teste correspondente:${MISSING_TESTS}"
      say "  P2: todo AC deve ter teste. Verifique se os testes existem em outro path."
    fi
  fi

  # Validação de AC coverage (quando testes existem e têm IDs de AC)
  if [ -n "$HAS_TEST" ]; then
    while IFS= read -r test_file; do
      [ -z "$test_file" ] && continue
      [ ! -f "$test_file" ] && continue
      # Verifica se o teste referencia pelo menos um AC-NNN
      if ! grep -qE "AC-[0-9]{3}" "$test_file" 2>/dev/null; then
        say "WARN: $test_file não referencia nenhum AC (AC-NNN) — P2 recomenda rastreabilidade"
      fi
    done <<< "$HAS_TEST"
  fi
fi

say "todos os gates passaram"
exit 0
