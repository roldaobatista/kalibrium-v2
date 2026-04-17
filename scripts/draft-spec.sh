#!/usr/bin/env bash
# draft-spec.sh — validador mecânico do spec.md preenchido pela skill /draft-spec.
# NÃO escreve conteúdo — só valida. A redação NL→AC é feita pelo agente principal.
# Fecha o hole de contrato P0-3 do meta-audit #2 (2026-04-11).
#
# Uso:
#   bash scripts/draft-spec.sh NNN --check

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:---check}"

if [ -z "$NNN" ]; then
  echo "Uso: draft-spec.sh NNN --check" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi
if [ "$MODE" != "--check" ]; then
  echo "modo não suportado: $MODE (apenas --check)" >&2
  exit 1
fi

SPEC="specs/$NNN/spec.md"
[ ! -f "$SPEC" ] && { echo "[draft-spec FAIL] $SPEC ausente — rode /new-slice $NNN primeiro" >&2; exit 1; }

ERR=0
fail() { echo "  ✗ $*" >&2; ERR=1; }
ok()   { echo "  ✓ $*"; }

echo "[draft-spec] validando $SPEC..."

# ---------------------------------------------------------------------------
# 1. Placeholders do template ainda presentes
# ---------------------------------------------------------------------------
if grep -qE '<título>|<humano>|<comportamento observável|<risco>|<item 1>|<item 2>' "$SPEC"; then
  fail "placeholders do template ainda presentes (<título>, <humano>, <comportamento observável>, <risco>, …)"
else
  ok "placeholders do template removidos"
fi

# ---------------------------------------------------------------------------
# 2. Seções obrigatórias não vazias
#    Heurística: pelo menos uma linha com 3+ palavras entre o heading e o próximo heading
# ---------------------------------------------------------------------------
# Harness v3: aceita sinônimos em PT-BR usados nas skills (Jornada do usuário, Critérios de aceite).
check_section() {
  local names="$1"  # pipe-separated alternatives
  awk -v names="$names" '
    BEGIN {
      n = split(names, arr, "|")
      found = 0; nonempty = 0
    }
    {
      if (!found) {
        for (i = 1; i <= n; i++) {
          if ($0 ~ "^##[[:space:]]+" arr[i] "[[:space:]]*$") { found = 1; next }
        }
      } else {
        if ($0 ~ /^##[[:space:]]/) { exit }
        if (NF >= 3) nonempty = 1
      }
    }
    END { exit nonempty ? 0 : 1 }
  ' "$SPEC"
}

declare -a SECTIONS=(
  "Contexto:Contexto"
  "Jornada alvo:Jornada alvo|Jornada do usuário"
  "Acceptance Criteria:Acceptance Criteria|Critérios de aceite"
  "Fora de escopo:Fora de escopo"
)

for entry in "${SECTIONS[@]}"; do
  LABEL="${entry%%:*}"
  NAMES="${entry#*:}"
  if check_section "$NAMES"; then
    ok "seção '$LABEL' preenchida"
  else
    fail "seção '$LABEL' vazia ou sem conteúdo substantivo (aceito: $NAMES)"
  fi
done

# ---------------------------------------------------------------------------
# 3. Extrai ACs — pelo menos 1, em sequência, sem buracos
#    Harness v3: aceita bullets ('- **AC-001:** …') OU headings ('### AC-001 — …' com Gherkin Dado/Quando/Então).
# ---------------------------------------------------------------------------
AC_LINES="$(grep -nE '^(\s*-\s*\*?\*?AC-[0-9]+|###+[[:space:]]+\*?\*?AC-[0-9]+)' "$SPEC" || true)"
AC_COUNT=0
if [ -n "$AC_LINES" ]; then
  AC_COUNT="$(echo "$AC_LINES" | grep -c . || echo 0)"
fi
AC_COUNT="${AC_COUNT:-0}"

if [ "$AC_COUNT" -lt 1 ]; then
  fail "nenhum AC encontrado (esperado: '- **AC-001:** descrição' ou '### AC-001 — descrição')"
else
  ok "$AC_COUNT AC(s) encontrados"

  # Sequência sem buracos
  PREV=0
  GAP=0
  LAST_OK=0
  while IFS= read -r line; do
    [ -z "$line" ] && continue
    ID_RAW="$(echo "$line" | grep -oE 'AC-[0-9]+' | head -1 | sed -E 's/AC-0*//')"
    [ -z "$ID_RAW" ] && ID_RAW=0
    EXPECTED=$((PREV + 1))
    if [ "$ID_RAW" -ne "$EXPECTED" ]; then
      fail "AC fora de sequência — esperado AC-$(printf '%03d' "$EXPECTED"), achei AC-$(printf '%03d' "$ID_RAW")"
      GAP=1
      break
    fi
    PREV="$ID_RAW"
    LAST_OK="$ID_RAW"
  done <<< "$AC_LINES"
  [ "$GAP" -eq 0 ] && [ "$LAST_OK" -gt 0 ] && ok "ACs sequenciais (AC-001..AC-$(printf '%03d' "$LAST_OK"))"
fi

# ---------------------------------------------------------------------------
# 4. ACs sem descrição
# ---------------------------------------------------------------------------
EMPTY_AC="$(grep -nE '^(\s*-\s*\*?\*?AC-[0-9]+\*?\*?:?\s*$|###+[[:space:]]+\*?\*?AC-[0-9]+\*?\*?\s*$)' "$SPEC" || true)"
if [ -n "$EMPTY_AC" ]; then
  fail "AC(s) sem descrição:"
  echo "$EMPTY_AC" | sed 's/^/      /' >&2
fi

# ---------------------------------------------------------------------------
# 5. TODO/TBD/FIXME/... dentro dos ACs
# ---------------------------------------------------------------------------
if grep -nE '^(\s*-\s*\*?\*?AC-[0-9]+|###+[[:space:]]+\*?\*?AC-[0-9]+).*(TODO|TBD|FIXME|\.\.\.)' "$SPEC" >/dev/null; then
  fail "AC(s) com TODO/TBD/FIXME/... — todos devem ser concretos e testáveis agora"
fi

# ---------------------------------------------------------------------------
# Verdict
# ---------------------------------------------------------------------------
echo ""
if [ "$ERR" -eq 0 ]; then
  echo "[draft-spec] OK — $SPEC pronto para architect"
  exit 0
else
  echo "[draft-spec FAIL] corrija os itens acima e rode de novo" >&2
  exit 1
fi
