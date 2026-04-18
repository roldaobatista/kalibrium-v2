#!/usr/bin/env bash
# Slice 018 — AC-007-A (B-041)
#
# Checker de contrato de paths do repositorio. Rejeita path que comeca com
# qualquer prefixo listado em docs/protocol/forbidden-paths.txt.
#
# Uso:
#   scripts/check-forbidden-path.sh <path>
#
# Exit 0 = path valido
# Exit 1 = ContractViolation (stderr mensagem canonica)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LIST="$REPO_ROOT/docs/protocol/forbidden-paths.txt"

PATH_ARG="${1:-}"

if [ -z "$PATH_ARG" ]; then
  echo "usage: $0 <path>" >&2
  exit 2
fi

if [ ! -f "$LIST" ]; then
  echo "error: forbidden-paths.txt nao encontrado em $LIST" >&2
  exit 2
fi

while IFS= read -r prefix; do
  [ -z "$prefix" ] && continue
  case "$prefix" in
    \#*) continue ;;
  esac
  case "$PATH_ARG" in
    "$prefix"*)
      echo "ContractViolation: path \"$PATH_ARG\" proibido (prefix: \"$prefix\") — ver docs/protocol/forbidden-paths.txt" >&2
      exit 1
      ;;
  esac
done < "$LIST"

exit 0
