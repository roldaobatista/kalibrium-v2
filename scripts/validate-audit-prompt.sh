#!/usr/bin/env bash
# Slice 018 — AC-003, AC-003-A (B-037)
#
# Validator mecanico de prompts de auditoria/re-auditoria.
#
# Uso:
#   scripts/validate-audit-prompt.sh --mode=1st-pass <prompt-file>
#   scripts/validate-audit-prompt.sh --mode=re-audit <prompt-file>
#
# Exit 0 = prompt limpo
# Exit 1 = contaminacao detectada (stderr reporta linha+token)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TEMPLATE="$REPO_ROOT/docs/protocol/audit-prompt-template.md"
BLOCKED_TOKENS="$REPO_ROOT/docs/protocol/blocked-tokens-re-audit.txt"

MODE=""
FILE=""

for arg in "$@"; do
  case "$arg" in
    --mode=*)
      MODE="${arg#--mode=}"
      ;;
    *)
      if [ -z "$FILE" ]; then FILE="$arg"; fi
      ;;
  esac
done

if [ -z "$MODE" ] || [ -z "$FILE" ]; then
  echo "usage: $0 --mode=(1st-pass|re-audit) <prompt-file>" >&2
  exit 2
fi

if [ ! -f "$FILE" ]; then
  echo "error: prompt file '$FILE' nao existe" >&2
  exit 2
fi

# ------------------- 1st-pass validation -------------------
if [ "$MODE" = "1st-pass" ]; then
  # Exige presenca dos 6 campos obrigatorios como "secao/campo estrutural"
  # (nao apenas como palavra solta em prosa).
  #
  # Campos de frontmatter YAML (story_id, slice_id, mode):
  #   esperados como "campo: valor" no inicio de linha.
  # Campos de secao Markdown (perimeter_files, criteria_checklist, output_contract):
  #   esperados como "## campo" ou heading equivalente no inicio de linha.
  MISSING=()

  # Frontmatter fields
  for field in story_id slice_id mode; do
    if ! grep -qE "^${field}:" "$FILE"; then
      MISSING+=("$field")
    fi
  done

  # Section heading fields (## campo)
  for field in perimeter_files criteria_checklist output_contract; do
    if ! grep -qE "^##[[:space:]]+${field}" "$FILE"; then
      MISSING+=("$field")
    fi
  done

  if [ ${#MISSING[@]} -gt 0 ]; then
    echo "[validate-audit-prompt] REJECTED 1st-pass: campos obrigatorios ausentes" >&2
    for f in "${MISSING[@]}"; do
      echo "  - campo violador: $f" >&2
    done
    exit 1
  fi

  echo "[validate-audit-prompt] OK 1st-pass: todos os 6 campos presentes"
  exit 0
fi

# ------------------- re-audit validation -------------------
if [ "$MODE" = "re-audit" ]; then
  if [ ! -f "$BLOCKED_TOKENS" ]; then
    echo "error: blocked-tokens-re-audit.txt nao encontrado em $BLOCKED_TOKENS" >&2
    exit 2
  fi

  VIOLATIONS=0

  # 1) Scan tokens literais da lista fechada (case-insensitive).
  while IFS= read -r token; do
    # Ignora comentarios e linhas vazias.
    [ -z "$token" ] && continue
    case "$token" in
      \#*) continue ;;
    esac
    # case-insensitive line matching via awk (evita bug do grep -iF com tokens
    # contendo espaco no Git Bash Windows). Trata token como substring literal.
    matches="$(awk -v tok="$token" 'BEGIN{IGNORECASE=1} index(tolower($0), tolower(tok)){print NR":"$0}' "$FILE" || true)"
    if [ -n "$matches" ]; then
      echo "[validate-audit-prompt] REJECTED re-audit: token proibido \"$token\"" >&2
      while IFS= read -r hit; do
        echo "  - linha $hit" >&2
      done <<< "$matches"
      VIOLATIONS=$((VIOLATIONS + 1))
    fi
  done < "$BLOCKED_TOKENS"

  # 2) Finding IDs no formato [A-Z]{1,4}-[0-9]{3}-[0-9]{3} (ex: VER-019-003)
  id_matches="$(grep -inE '\b[A-Z]{1,4}-[0-9]{3}-[0-9]{3}\b' "$FILE" || true)"
  if [ -n "$id_matches" ]; then
    echo "[validate-audit-prompt] REJECTED re-audit: finding_id previo detectado" >&2
    while IFS= read -r hit; do
      echo "  - linha $hit" >&2
    done <<< "$id_matches"
    VIOLATIONS=$((VIOLATIONS + 1))
  fi

  # 3) Commit hash [a-f0-9]{7,40} adjacente a palavras fix|correcao|corrigir
  hash_matches="$(grep -inE '(fix|correc[ãa]o|corrigir|corrigiu)[^\n]*\b[a-f0-9]{7,40}\b|\b[a-f0-9]{7,40}\b[^\n]*(fix|correc[ãa]o|corrigir|corrigiu)' "$FILE" || true)"
  if [ -n "$hash_matches" ]; then
    echo "[validate-audit-prompt] REJECTED re-audit: commit hash de fix detectado" >&2
    while IFS= read -r hit; do
      echo "  - linha $hit" >&2
    done <<< "$hash_matches"
    VIOLATIONS=$((VIOLATIONS + 1))
  fi

  if [ $VIOLATIONS -gt 0 ]; then
    echo "[validate-audit-prompt] total de violacoes: $VIOLATIONS" >&2
    exit 1
  fi

  echo "[validate-audit-prompt] OK re-audit: prompt limpo"
  exit 0
fi

echo "error: mode '$MODE' invalido — use 1st-pass ou re-audit" >&2
exit 2
