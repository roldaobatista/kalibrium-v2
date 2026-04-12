#!/usr/bin/env bash
# review-slice.sh — monta review-input/ e orienta spawn do reviewer agent.
# Complementa verify-slice.sh. Parte do modelo humano=PM (R11).
#
# Pré-condição: verify-slice já rodou e verification.json tem verdict=approved.
#
# Modos:
#   bash scripts/review-slice.sh NNN             → monta input + instrução de spawn
#   bash scripts/review-slice.sh NNN --validate  → valida review.json + R6

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
MODE="${2:-prepare}"

if [ -z "$NNN" ]; then
  echo "Uso:"
  echo "  review-slice.sh NNN              # monta review-input/"
  echo "  review-slice.sh NNN --validate   # valida review.json e aplica R6"
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
INPUT_DIR="review-input"
TELEMETRY=".claude/telemetry/slice-${NNN}.jsonl"

say()  { echo "[review-slice] $*"; }
fail() { echo "[review-slice FAIL] $*" >&2; exit 1; }

[ ! -d "$SLICE_DIR" ] && fail "slice $NNN não existe em $SLICE_DIR"

# ==========================================================================
# MODE: --validate
# ==========================================================================
if [ "$MODE" = "--validate" ]; then
  RJSON="$INPUT_DIR/review.json"
  [ ! -f "$RJSON" ] && RJSON="$SLICE_DIR/review.json"
  [ ! -f "$RJSON" ] && fail "review.json não encontrado (procurei em $INPUT_DIR e $SLICE_DIR)"

  say "validando $RJSON contra schema review.schema.json..."
  if ! bash "$SCRIPT_DIR/validate-review.sh" "$RJSON"; then
    fail "schema inválido — reviewer emitiu output fora do formato"
  fi
  say "schema OK"

  VERDICT="$(grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$RJSON" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
  NEXT="$(grep -o '"next_action"[[:space:]]*:[[:space:]]*"[^"]*"' "$RJSON" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"

  say "verdict=$VERDICT next_action=$NEXT"

  # Telemetria append-only com hash-chain (item 1.3 meta-audit).
  mkdir -p ".claude/telemetry"
  touch "$TELEMETRY"
  if ! bash "$SCRIPT_DIR/record-telemetry.sh" --verify-chain "$TELEMETRY" >/dev/null 2>&1; then
    fail "telemetria $TELEMETRY corrompida (hash-chain inválida) — possível tampering, ver record-telemetry.sh --verify-chain"
  fi

  PREV_REJECTS=$(grep '"event":"review".*"verdict":"rejected"' "$TELEMETRY" 2>/dev/null | wc -l | tr -d ' \n\r')
  PREV_REJECTS="${PREV_REJECTS:-0}"

  if [ "$VERDICT" = "rejected" ]; then
    CURRENT_REJECTS=$((PREV_REJECTS + 1))
  else
    CURRENT_REJECTS=0
  fi

  bash "$SCRIPT_DIR/record-telemetry.sh" \
    --event=review \
    --slice="slice-${NNN}" \
    --verdict="$VERDICT" \
    --next-action="$NEXT" \
    --reject-count="$CURRENT_REJECTS" >/dev/null || fail "record-telemetry falhou"

  # R6 estendido ao reviewer
  if [ "$VERDICT" = "rejected" ] && [ "$CURRENT_REJECTS" -ge 2 ]; then
    INCIDENT="docs/incidents/slice-${NNN}-review-escalation-$(date -u +%Y-%m-%d).md"
    mkdir -p docs/incidents
    cat > "$INCIDENT" <<EOF
# Incidente — escalação R6 (reviewer): slice-${NNN}

**Data:** $(date -u +%Y-%m-%dT%H:%M:%SZ)
**Rejeições consecutivas do reviewer:** $CURRENT_REJECTS
**Review.json:** $RJSON

## Contexto

R6 + R11: reviewer reprovou 2x consecutivamente. Humano é PM não-técnico — decisão requer linguagem de produto.

## Ação automática

- \`/explain-slice ${NNN}\` deve ser invocado para gerar relatório em PT-BR
- Implementer BLOQUEADO até decisão humana

## Decisão humana
_(preencher em docs/explanations/slice-${NNN}.md)_
EOF

    # Copia review.json para specs/NNN/ antes de traduzir (translate-pm lê de lá)
    cp "$RJSON" "$SLICE_DIR/review.json"

    # B-016 / G-11-estendido: dispara tradução PM-ready antes de escalar
    say "gerando relatório PM-ready (R6 reviewer escalation)..."
    bash "$SCRIPT_DIR/explain-slice.sh" "$NNN" >/dev/null || \
      say "aviso: translate-pm falhou — relatório não gerado, PM verá JSON cru"

    echo ""
    echo "================================================================"
    echo "  R6 ESCALAÇÃO — reviewer reprovou slice-${NNN} 2x"
    echo "================================================================"
    echo "  Incidente: $INCIDENT"
    echo "  Relatório PM (em PT-BR): docs/explanations/slice-${NNN}.md"
    echo "  Implementer BLOQUEADO até decisão humana."
    echo "================================================================"
    exit 2
  fi

  cp "$RJSON" "$SLICE_DIR/review.json"

  # B-016 / G-11-estendido: em qualquer verdict, dispara explain-slice
  # automaticamente. Relatório cresce a cada handoff (verify + review + merge).
  say "gerando relatório PM-ready..."
  bash "$SCRIPT_DIR/explain-slice.sh" "$NNN" >/dev/null || \
    say "aviso: translate-pm falhou — relatório não gerado, PM verá JSON cru"
  PM_REPORT="docs/explanations/slice-${NNN}.md"

  case "$VERDICT" in
    approved)
      say "✓ reviewer aprovou — próximo passo: /merge-slice ${NNN} (se verifier também aprovou)"
      say "  relatório PM: $PM_REPORT"
      exit 0
      ;;
    rejected)
      say "✗ reviewer rejeitou ($CURRENT_REJECTS/2) — implementer deve tratar findings"
      say "  relatório PM (leia este, não o JSON): $PM_REPORT"
      exit 1
      ;;
    *)
      fail "verdict inesperado: $VERDICT"
      ;;
  esac
  exit 1
fi

# ==========================================================================
# MODE: prepare (default)
# ==========================================================================

[ ! -f "$SLICE_DIR/spec.md" ] && fail "$SLICE_DIR/spec.md ausente"

# R11: reviewer só roda se verifier já aprovou
if [ ! -f "$SLICE_DIR/verification.json" ]; then
  fail "verification.json ausente — rode /verify-slice $NNN primeiro (R11)"
fi

VERIF_OUT="$(grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$SLICE_DIR/verification.json" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
if [ "$VERIF_OUT" != "approved" ]; then
  fail "verifier não aprovou (verdict=$VERIF_OUT) — reviewer só roda após verifier approved (R11)"
fi

# Item 1.8 meta-audit: integridade do harness ANTES de spawnar reviewer.
say "validando integridade do harness (item 1.8)..."
if ! bash "$SCRIPT_DIR/hooks/hooks-lock.sh" --check; then
  fail "harness drift detectado — reviewer NÃO será spawnado (item 1.8 meta-audit)"
fi
if ! bash "$SCRIPT_DIR/hooks/settings-lock.sh" --check; then
  fail "settings.json drift detectado — reviewer NÃO será spawnado (item 1.1 meta-audit)"
fi
say "harness íntegro"

# Item 1.9 meta-audit: sanitize-input antes de copiar para o sandbox do reviewer.
say "sanitizando spec.md + glossário (item 1.9)..."
if ! bash "$SCRIPT_DIR/sanitize-input.sh" --check "$SLICE_DIR/spec.md"; then
  fail "spec.md contém padrões de prompt injection — corrija antes de re-rodar"
fi
if [ -f docs/glossary-domain.md ]; then
  if ! bash "$SCRIPT_DIR/sanitize-input.sh" --check docs/glossary-domain.md; then
    fail "docs/glossary-domain.md contém padrões de prompt injection — corrija antes de re-rodar"
  fi
fi
say "inputs limpos"

# Limpa e recria review-input/
rm -rf "$INPUT_DIR"
mkdir -p "$INPUT_DIR"

bash "$SCRIPT_DIR/sanitize-input.sh" --wrap "$SLICE_DIR/spec.md" "$INPUT_DIR/spec.md" || \
  fail "sanitize-input --wrap (spec) falhou"
cp docs/constitution.md "$INPUT_DIR/constitution-snapshot.md"
if [ -f docs/glossary-domain.md ]; then
  bash "$SCRIPT_DIR/sanitize-input.sh" --wrap docs/glossary-domain.md "$INPUT_DIR/glossary-snapshot.md" || \
    fail "sanitize-input --wrap (glossary) falhou"
else
  echo "(glossário ausente)" > "$INPUT_DIR/glossary-snapshot.md"
fi

# Diff do slice contra main (ou base)
if git rev-parse --verify main >/dev/null 2>&1; then
  git diff main...HEAD -- 2>/dev/null > "$INPUT_DIR/diff.patch" || true
  git diff --name-only main...HEAD 2>/dev/null > "$INPUT_DIR/files-changed.txt" || true
else
  echo "(sem main ainda)" > "$INPUT_DIR/diff.patch"
  echo "" > "$INPUT_DIR/files-changed.txt"
fi

# Snapshot dos ADRs (cópia completa)
mkdir -p "$INPUT_DIR/adr-snapshot"
cp docs/adr/*.md "$INPUT_DIR/adr-snapshot/" 2>/dev/null || true

say ""
say "review-input/ montado:"
ls -la "$INPUT_DIR"
say ""
say "======================================================================"
say "  PRÓXIMO PASSO — spawn do sub-agent reviewer em worktree isolada"
say "======================================================================"
say ""
say "  Agent({"
say '    subagent_type: "reviewer",'
say '    isolation:     "worktree",'
say '    description:   "Review estrutural slice-'"$NNN"'",'
say '    prompt:        "Leia APENAS review-input/. Escreva'
say '                    review-input/review.json seguindo'
say '                    docs/schemas/review.schema.json. Sem prosa."'
say "  })"
say ""
say "  Após o reviewer gravar review.json, rode:"
say ""
say "    bash scripts/review-slice.sh $NNN --validate"
say ""
say "======================================================================"
exit 0
