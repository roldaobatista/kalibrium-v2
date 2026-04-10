#!/usr/bin/env bash
# Cria um novo ADR a partir do template.
# Uso: bash scripts/adr-new.sh NNNN "título"
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNNN="${1:-}"
TITLE="${2:-}"

if [ -z "$NNNN" ] || [ -z "$TITLE" ]; then
  echo "Uso: adr-new.sh NNNN \"título\"" >&2
  exit 1
fi

if ! echo "$NNNN" | grep -qE '^[0-9]{4}$'; then
  echo "NNNN deve ter 4 dígitos (ex.: 0001, 0042)" >&2
  exit 1
fi

# Slug simples: lowercase, espaços → -, remove não alfanuméricos
SLUG="$(echo "$TITLE" | tr '[:upper:]' '[:lower:]' | tr ' ' '-' | tr -cd '[:alnum:]-' | sed 's/--*/-/g')"
DEST="docs/adr/${NNNN}-${SLUG}.md"

if ls docs/adr/${NNNN}-*.md >/dev/null 2>&1; then
  echo "já existe ADR com número $NNNN" >&2
  exit 1
fi

DATE="$(date -u +%Y-%m-%d)"

sed -e "s/ADR-0000 — Template/ADR-$NNNN — $TITLE/" \
    -e "s/YYYY-MM-DD/$DATE/" \
    docs/adr/0000-template.md > "$DEST"

# Atualiza índice
if [ -f docs/TECHNICAL-DECISIONS.md ]; then
  tmp="$(mktemp)"
  awk -v n="$NNNN" -v t="$TITLE" -v d="$DATE" -v dest="$DEST" '
    /\(0001\) \| Escolha da stack/ && n == "0001" {
      print "| [" n "](" dest ") | " t " | proposed | " d " |"; inserted=1; next
    }
    /\| _\(pendente|\| _\(vazio/ { print "| [" n "](" dest ") | " t " | proposed | " d " |"; inserted=1; next }
    { print }
  ' docs/TECHNICAL-DECISIONS.md > "$tmp"
  mv "$tmp" docs/TECHNICAL-DECISIONS.md
fi

echo "[adr-new] criado: $DEST"
echo "Próximo passo: preencher Contexto, Opções consideradas (≥2), Decisão, Consequências."
