#!/usr/bin/env bash
# Cria esqueleto de um slice novo: specs/NNN/{spec,plan,tasks}.md a partir dos templates.
# Uso: bash scripts/new-slice.sh NNN "título"
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
TITLE="${2:-}"

if [ -z "$NNN" ] || [ -z "$TITLE" ]; then
  echo "Uso: new-slice.sh NNN \"título do slice\"" >&2
  exit 1
fi

# Valida formato NNN
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001, 042)" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
if [ -e "$SLICE_DIR" ]; then
  echo "slice $NNN já existe em $SLICE_DIR — abortando" >&2
  exit 1
fi

mkdir -p "$SLICE_DIR"

DATE="$(date -u +%Y-%m-%d)"

for tpl in spec plan tasks; do
  src="docs/templates/$tpl.md"
  dst="$SLICE_DIR/$tpl.md"
  if [ ! -f "$src" ]; then
    echo "template ausente: $src" >&2
    exit 1
  fi
  # Substitui NNN e título no cabeçalho
  sed -e "s/Slice NNN/Slice $NNN/" \
      -e "s/slice NNN/slice $NNN/g" \
      -e "s/<título>/$TITLE/" \
      -e "s/YYYY-MM-DD/$DATE/" \
      "$src" > "$dst"
done

# Registra no slice-registry
if [ -f docs/slice-registry.md ]; then
  # insere antes da linha "(vazio — ...)" se existir, ou no fim
  tmp="$(mktemp)"
  awk -v nnn="$NNN" -v title="$TITLE" -v date="$DATE" '
    /\(vazio/ { print "| " nnn " | " title " | draft | " date " | — |"; inserted=1; next }
    { print }
    END { if (!inserted) print "| " nnn " | " title " | draft | " date " | — |" }
  ' docs/slice-registry.md > "$tmp"
  mv "$tmp" docs/slice-registry.md
fi

# Marca como slice ativo
echo "$NNN" > specs/.current

echo "[new-slice] criado: $SLICE_DIR"
echo "  - spec.md (draft — humano deve preencher ACs)"
echo "  - plan.md (vazio — gerado pelo architect depois do spec aprovado)"
echo "  - tasks.md (vazio — preenchido pelo architect ou humano)"
echo ""
echo "Próximo passo: editar $SLICE_DIR/spec.md"
