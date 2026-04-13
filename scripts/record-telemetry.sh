#!/usr/bin/env bash
# record-telemetry.sh — único caminho autorizado para escrever em .claude/telemetry/.
#
# Origem: meta-audit 2026-04-10, item 1.3.
# Vetor coberto: §H ameaça #4 (telemetria mutável reseta R6) +
# §D vetor 3 do audit-claude-opus-4-6-2026-04-10.md.
#
# Como funciona:
#   - Escreve via shell append (>>), não via tool Edit/Write.
#     telemetry-lock.sh bloqueia tool-based mutation; este script
#     é o único caminho via Bash.
#   - Cada linha contém prev_hash = sha256 da linha anterior.
#     Apagar/zerar o arquivo invalida toda a cadeia → detectável
#     por --verify-chain.
#
# Modos:
#   --append          → grava novo evento (default se houver --event)
#   --verify-chain F  → valida que a cadeia em F está íntegra
#
# Uso (append):
#   bash scripts/record-telemetry.sh \
#     --event=verify \
#     --slice=slice-001 \
#     --verdict=approved \
#     --next-action=open_pr \
#     --reject-count=0
#
# Uso (verify-chain):
#   bash scripts/record-telemetry.sh --verify-chain .claude/telemetry/slice-001.jsonl

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

die() { echo "[record-telemetry FAIL] $*" >&2; exit 1; }

SCHEMA_VERSION="1.0.0"

# ----------------------------------------------------------------------
# MODE: --verify-chain
# ----------------------------------------------------------------------
if [ "${1:-}" = "--verify-chain" ]; then
  TELEM_FILE="${2:-}"
  [ -z "$TELEM_FILE" ] && die "--verify-chain requer caminho do arquivo"
  [ ! -f "$TELEM_FILE" ] && die "arquivo não existe: $TELEM_FILE"

  # Arquivo vazio é válido (cadeia ainda não iniciada).
  if [ ! -s "$TELEM_FILE" ]; then
    echo "[record-telemetry] cadeia vazia OK: $TELEM_FILE"
    exit 0
  fi

  EXPECTED_PREV="GENESIS"
  LINE_NUM=0
  PRE_SCHEMA_LINES=0
  while IFS= read -r line; do
    LINE_NUM=$((LINE_NUM + 1))
    [ -z "$line" ] && continue

    # Extrai prev_hash declarado na linha
    DECLARED_PREV="$(echo "$line" | grep -o '"prev_hash"[[:space:]]*:[[:space:]]*"[^"]*"' | sed -E 's/.*"([^"]*)"$/\1/')"

    # Tolera linhas pré-schema (antes de schema_version 1.0.0).
    # Essas linhas foram escritas antes do meta-audit 2026-04-10 e não têm
    # prev_hash nem schema_version. Não quebram a cadeia — são ignoradas
    # e a cadeia começa na primeira linha com prev_hash.
    # Ref: F6 master-independent-audit-2026-04-12.
    if [ -z "$DECLARED_PREV" ]; then
      HAS_SCHEMA="$(echo "$line" | grep -o '"schema_version"' || true)"
      if [ -z "$HAS_SCHEMA" ]; then
        PRE_SCHEMA_LINES=$((PRE_SCHEMA_LINES + 1))
        EXPECTED_PREV="$(printf '%s\n' "$line" | sha256sum | awk '{print $1}')"
        continue
      fi
      echo "[record-telemetry CHAIN BROKEN] linha $LINE_NUM sem prev_hash (pos-schema): $TELEM_FILE" >&2
      exit 1
    fi

    if [ "$DECLARED_PREV" != "$EXPECTED_PREV" ]; then
      echo "[record-telemetry CHAIN BROKEN] linha $LINE_NUM em $TELEM_FILE" >&2
      echo "  prev_hash declarado: $DECLARED_PREV" >&2
      echo "  prev_hash esperado:  $EXPECTED_PREV" >&2
      echo "  Possível tampering: linha removida, arquivo zerado, ou edição manual." >&2
      exit 1
    fi

    # Próximo prev_hash esperado = sha256 desta linha (com newline final, como gravado)
    EXPECTED_PREV="$(printf '%s\n' "$line" | sha256sum | awk '{print $1}')"
  done < "$TELEM_FILE"

  echo "[record-telemetry] cadeia válida ($LINE_NUM linhas) em $TELEM_FILE"
  exit 0
fi

# ----------------------------------------------------------------------
# MODE: --append (default)
# ----------------------------------------------------------------------
EVENT=""
SLICE=""
VERDICT="n/a"
NEXT_ACTION="n/a"
REJECT_COUNT=0
ACTOR="${KALIB_TELEMETRY_ACTOR:-agent}"

for arg in "$@"; do
  case "$arg" in
    --event=*)        EVENT="${arg#--event=}" ;;
    --slice=*)        SLICE="${arg#--slice=}" ;;
    --verdict=*)      VERDICT="${arg#--verdict=}" ;;
    --next-action=*)  NEXT_ACTION="${arg#--next-action=}" ;;
    --reject-count=*) REJECT_COUNT="${arg#--reject-count=}" ;;
    --actor=*)        ACTOR="${arg#--actor=}" ;;
    --append) ;;
    *) die "argumento desconhecido: $arg" ;;
  esac
done

[ -z "$EVENT" ] && die "--event obrigatório"
[ -z "$SLICE" ] && die "--slice obrigatório"

# Valida event enum
case "$EVENT" in
  verify|review|commit|merge|session_start|session_stop|relock) ;;
  *) die "event inválido: '$EVENT' (válidos: verify|review|commit|merge|session_start|session_stop|relock)" ;;
esac

# Valida verdict enum
case "$VERDICT" in
  approved|rejected|n/a) ;;
  *) die "verdict inválido: '$VERDICT' (válidos: approved|rejected|n/a)" ;;
esac

# Valida next_action enum
case "$NEXT_ACTION" in
  open_pr|approve_pr|human_merge|return_to_implementer|escalate_human|n/a) ;;
  *) die "next_action inválido: '$NEXT_ACTION'" ;;
esac

# Valida slice pattern
case "$SLICE" in
  slice-[0-9][0-9][0-9]|meta|harness) ;;
  *) die "slice inválido: '$SLICE' (esperado slice-NNN, meta, ou harness)" ;;
esac

# Valida reject_count integer
case "$REJECT_COUNT" in
  ''|*[!0-9]*) die "reject_count deve ser inteiro: '$REJECT_COUNT'" ;;
esac

mkdir -p .claude/telemetry
TELEM_FILE=".claude/telemetry/${SLICE}.jsonl"
touch "$TELEM_FILE"

# Computa prev_hash da última linha (ou GENESIS se vazio)
if [ -s "$TELEM_FILE" ]; then
  PREV_HASH="$(tail -n 1 "$TELEM_FILE" | sha256sum | awk '{print $1}')"
else
  PREV_HASH="GENESIS"
fi

TS="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# Escreve linha JSONL.
# Ordem das chaves estável (importante para reprodutibilidade do sha256).
LINE=$(printf '{"schema_version":"%s","event":"%s","timestamp":"%s","slice":"%s","verdict":"%s","next_action":"%s","reject_count":%d,"actor":"%s","prev_hash":"%s"}' \
  "$SCHEMA_VERSION" "$EVENT" "$TS" "$SLICE" "$VERDICT" "$NEXT_ACTION" "$REJECT_COUNT" "$ACTOR" "$PREV_HASH")

printf '%s\n' "$LINE" >> "$TELEM_FILE"

# Retorno: imprime o hash da linha que acabou de gravar (útil para callers).
THIS_HASH="$(printf '%s\n' "$LINE" | sha256sum | awk '{print $1}')"
echo "$THIS_HASH"
