#!/usr/bin/env bash
# Slice 018 — AC-004-A (B-037)
#
# Set-difference entre 2 rodadas de findings de auditoria.
# Assinatura semantica = categoria + descricao_normalizada + path_sem_linha.
# Resiliente a movimentacao de codigo (linha diferente casa; path e categoria iguais).
#
# Uso:
#   scripts/audit-set-difference.sh --previous <prev.json> --current <curr.json>
#
# Saida: JSON com 3 chaves (resolved, unresolved, new) em stdout.
#
# Tolerancia: ~5% falso positivo documentado em 06-estrategia-evidencias.md (R2 spec).

set -euo pipefail

if ! command -v jq >/dev/null 2>&1; then
  echo "error: dependencia jq nao instalada" >&2
  exit 2
fi

PREV=""
CURR=""

while [ $# -gt 0 ]; do
  case "$1" in
    --previous) PREV="$2"; shift 2 ;;
    --current) CURR="$2"; shift 2 ;;
    *) echo "error: argumento desconhecido: $1" >&2; exit 2 ;;
  esac
done

if [ -z "$PREV" ] || [ -z "$CURR" ]; then
  echo "usage: $0 --previous <prev.json> --current <curr.json>" >&2
  exit 2
fi

if [ ! -f "$PREV" ] || [ ! -f "$CURR" ]; then
  echo "error: arquivos previous/current devem existir" >&2
  exit 2
fi

# Normalizacao de assinatura:
#   assinatura = (category | ascii lower | trim) + "::" + (description | ascii lower | trim)
#              + "::" + (file | sem sufixo :line)
#
# Saida: objeto {signature, finding} para cada input.
#
# Depois usa assinaturas como chave de set-difference.

NORMALIZE_FILTER='
  .findings
  | map({
      signature: (
        ((.category // "") | ascii_downcase | gsub("^\\s+|\\s+$"; "")) + "::" +
        ((.description // "") | ascii_downcase | gsub("^\\s+|\\s+$"; "")) + "::" +
        ((.file // "") | gsub(":\\d+$"; ""))
      ),
      finding: .
    })
'

prev_sigs="$(jq -c "$NORMALIZE_FILTER" "$PREV")"
curr_sigs="$(jq -c "$NORMALIZE_FILTER" "$CURR")"

# Computa 3 listas usando jq set-diff semantico.
jq -n \
  --argjson prev "$prev_sigs" \
  --argjson curr "$curr_sigs" '
    ($prev | map(.signature)) as $prev_keys
    | ($curr | map(.signature)) as $curr_keys
    | {
        resolved: ($prev | map(select(.signature as $s | ($curr_keys | index($s)) == null)) | map(.finding)),
        unresolved: ($curr | map(select(.signature as $s | ($prev_keys | index($s)) != null)) | map(.finding)),
        new: ($curr | map(select(.signature as $s | ($prev_keys | index($s)) == null)) | map(.finding))
      }
  '

exit 0
