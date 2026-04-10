#!/usr/bin/env bash
# verify-slice.sh — monta verification-input/, orienta o spawn do verifier,
# valida verification.json contra R4 e aplica R6.
#
# Modos:
#   bash scripts/verify-slice.sh NNN             → monta input + imprime instrução de spawn
#   bash scripts/verify-slice.sh NNN --validate  → valida verification.json já gerado + aplica R6
#
# B-002 do guide-backlog.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:-prepare}"

if [ -z "$NNN" ]; then
  echo "Uso:"
  echo "  verify-slice.sh NNN              # monta verification-input/"
  echo "  verify-slice.sh NNN --validate   # valida verification.json e aplica R6"
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
INPUT_DIR="verification-input"
TELEMETRY=".claude/telemetry/slice-${NNN}.jsonl"

say()  { echo "[verify-slice] $*"; }
fail() { echo "[verify-slice FAIL] $*" >&2; exit 1; }

[ ! -d "$SLICE_DIR" ] && fail "slice $NNN não existe em $SLICE_DIR"

# ==========================================================================
# MODE: --validate
# ==========================================================================
if [ "$MODE" = "--validate" ]; then
  VJSON="$INPUT_DIR/verification.json"
  [ ! -f "$VJSON" ] && VJSON="$SLICE_DIR/verification.json"
  [ ! -f "$VJSON" ] && fail "verification.json não encontrado (procurei em $INPUT_DIR e $SLICE_DIR)"

  say "validando $VJSON contra schema R4..."
  if ! bash "$SCRIPT_DIR/validate-verification.sh" "$VJSON"; then
    fail "schema inválido — verifier emitiu output fora do formato (R4)"
  fi
  say "schema OK"

  # Extrai verdict e next_action
  VERDICT="$(grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$VJSON" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
  NEXT="$(grep -o '"next_action"[[:space:]]*:[[:space:]]*"[^"]*"' "$VJSON" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"

  say "verdict=$VERDICT next_action=$NEXT"

  # Conta rejeições anteriores na telemetria (R6)
  mkdir -p ".claude/telemetry"
  touch "$TELEMETRY"
  # wc -l é seguro contra arquivos vazios (sempre retorna número), grep -c + || echo produz "0\n0" corrompido
  PREV_REJECTS=$(grep '"event":"verify".*"verdict":"rejected"' "$TELEMETRY" 2>/dev/null | wc -l | tr -d ' \n\r')
  PREV_REJECTS="${PREV_REJECTS:-0}"

  # Grava evento atual
  TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  if [ "$VERDICT" = "rejected" ]; then
    CURRENT_REJECTS=$((PREV_REJECTS + 1))
  else
    CURRENT_REJECTS=0
  fi
  printf '{"event":"verify","timestamp":"%s","slice":"slice-%s","verdict":"%s","next_action":"%s","reject_count":%d}\n' \
    "$TS" "$NNN" "$VERDICT" "$NEXT" "$CURRENT_REJECTS" >> "$TELEMETRY"

  # R6: 2 rejeições consecutivas = escalar humano
  if [ "$VERDICT" = "rejected" ] && [ "$CURRENT_REJECTS" -ge 2 ]; then
    INCIDENT="docs/incidents/slice-${NNN}-escalation-$(date -u +%Y-%m-%d).md"
    mkdir -p docs/incidents
    cat > "$INCIDENT" <<EOF
# Incidente — escalação R6: slice-${NNN}

**Data:** $(date -u +%Y-%m-%dT%H:%M:%SZ)
**Rejeições consecutivas:** $CURRENT_REJECTS
**Último verdict:** $VERDICT
**Snapshot do verification.json:** $VJSON

## Contexto

R6 da constitution: 2 reprovações consecutivas do verifier forçam escalação humana. Implementer **não pode** tentar novamente sem decisão humana (reescopar, reiniciar, ou matar o slice).

## Ação humana requerida

- [ ] Ler $VJSON e entender as violations
- [ ] Decidir: reescopar / reiniciar / matar o slice
- [ ] Registrar decisão abaixo
- [ ] Se reiniciar: resetar contador criando um novo evento em $TELEMETRY com reject_count=0

## Decisão
_(preencher)_
EOF
    echo ""
    echo "================================================================"
    echo "  R6 ESCALAÇÃO HUMANA OBRIGATÓRIA — slice-${NNN}"
    echo "================================================================"
    echo "  Rejeições consecutivas: $CURRENT_REJECTS"
    echo "  Incidente criado: $INCIDENT"
    echo "  Implementer BLOQUEADO até decisão humana."
    echo "================================================================"
    exit 2
  fi

  # Copia verification.json para specs/NNN/ (persistência)
  cp "$VJSON" "$SLICE_DIR/verification.json"

  case "$VERDICT" in
    approved)
      say "✓ approved — abrir PR (next_action=$NEXT)"
      exit 0
      ;;
    rejected)
      say "✗ rejected ($CURRENT_REJECTS/2) — implementer deve corrigir violations e re-verificar"
      exit 1
      ;;
    *)
      fail "verdict inesperado: $VERDICT"
      ;;
  esac
  # Nunca chega aqui, mas garante que o modo --validate não vaza para o modo prepare
  exit 1
fi

# ==========================================================================
# MODE: prepare (default)
# ==========================================================================

[ ! -f "$SLICE_DIR/spec.md" ] && fail "$SLICE_DIR/spec.md ausente"
[ ! -f "$SLICE_DIR/plan.md" ] && fail "$SLICE_DIR/plan.md ausente"

# Limpa e recria verification-input
rm -rf "$INPUT_DIR"
mkdir -p "$INPUT_DIR"

# 1. Copia spec e constitution snapshot
cp "$SLICE_DIR/spec.md" "$INPUT_DIR/spec.md"
cp docs/constitution.md "$INPUT_DIR/constitution-snapshot.md"

# 2. Extrai AC-list do spec.md para ac-list.json
# Procura linhas matching:  - **AC-NNN:** descrição  ou  - AC-NNN: descrição
TMP_ACS="$(mktemp)"
grep -nE '^\s*-\s*\*?\*?(AC-[0-9]+)' "$SLICE_DIR/spec.md" | \
  sed -E 's/^[0-9]+:\s*-\s*\*{0,2}(AC-[0-9]+)\*{0,2}[:\s]*\s*(.*)$/\1|\2/' > "$TMP_ACS" || true

{
  echo "["
  first=1
  while IFS='|' read -r id text; do
    [ -z "$id" ] && continue
    # Escapa aspas duplas no texto
    text_escaped="${text//\"/\\\"}"
    if [ $first -eq 1 ]; then
      first=0
    else
      echo ","
    fi
    printf '  {"id":"%s","text":"%s"}' "$id" "$text_escaped"
  done < "$TMP_ACS"
  echo ""
  echo "]"
} > "$INPUT_DIR/ac-list.json"
rm -f "$TMP_ACS"

AC_COUNT="$(grep -c '"id"' "$INPUT_DIR/ac-list.json" 2>/dev/null || echo 0)"
say "AC-list: $AC_COUNT ACs extraídos para $INPUT_DIR/ac-list.json"

# 3. files-changed.txt (arquivos do slice)
if git rev-parse --verify HEAD >/dev/null 2>&1; then
  # Heurística simples: arquivos tocados nos últimos commits que mencionem slice-NNN
  git log --format='%H' --grep="slice-${NNN}" 2>/dev/null | while read -r h; do
    git show --name-only --format='' "$h" 2>/dev/null
  done | sort -u > "$INPUT_DIR/files-changed.txt"
  if [ ! -s "$INPUT_DIR/files-changed.txt" ]; then
    # Fallback: arquivos não-commitados + commits recentes
    git diff --name-only HEAD 2>/dev/null > "$INPUT_DIR/files-changed.txt"
  fi
else
  echo "(sem git log ainda)" > "$INPUT_DIR/files-changed.txt"
fi

# 4. test-results.txt (placeholder — implementer preenche rodando AC-tests)
cat > "$INPUT_DIR/test-results.txt" <<EOF
# test-results — preencher rodando AC-tests filtrados pelo ID
# Exemplo:
#   npx vitest run tests/ -t "AC-"
#   vendor/bin/pest --filter="AC-"
# Cole aqui o output completo (incluindo exit code).

(implementer: substitua este placeholder pelo output real)
EOF

say ""
say "verification-input/ montado:"
ls -la "$INPUT_DIR"
say ""
say "======================================================================"
say "  PRÓXIMO PASSO — spawn do verifier em worktree isolada"
say "======================================================================"
say ""
say "  No Claude Code principal, invoque o Agent tool com:"
say ""
say '    subagent_type: "verifier"'
say '    isolation:     "worktree"'
say '    description:   "Verify slice-'"$NNN"'"'
say '    prompt:        "Leia APENAS verification-input/. Escreva'
say '                    verification-input/verification.json seguindo o'
say '                    schema de R4 (docs/schemas/verification.schema.json).'
say '                    Sem prosa, apenas o JSON."'
say ""
say "  Após o verifier gravar verification.json, rode:"
say ""
say "    bash scripts/verify-slice.sh $NNN --validate"
say ""
say "======================================================================"
exit 0
