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

# ---------------------------------------------------------------------------
# Gate: retrospectiva do slice anterior (meta-audit #2, item P1)
#
# Regra: se existir algum slice em specs/ com NNN menor que o atual,
# o mais recente desses precisa ter retrospectiva registrada em
# docs/retrospectives/slice-NNN.md. Sem isso, /new-slice recusa.
#
# Bypass legítimo (slice anterior abandonado ou ainda em andamento ativo):
#   KALIB_SKIP_RETRO_GATE=1 bash scripts/new-slice.sh NNN "título"
# Bypass fica registrado no output (auditável via telemetria do chamador).
# ---------------------------------------------------------------------------
if [ -d specs ]; then
  PREV_NNN="$(
    find specs -mindepth 1 -maxdepth 1 -type d 2>/dev/null | sed 's|.*/||' \
      | grep -E '^[0-9]{3}$' \
      | awk -v cur="$NNN" '$0 < cur' \
      | sort -n | tail -1
  )"
  if [ -n "$PREV_NNN" ]; then
    RETRO="docs/retrospectives/slice-${PREV_NNN}.md"
    if [ ! -f "$RETRO" ]; then
      if [ "${KALIB_SKIP_RETRO_GATE:-0}" = "1" ]; then
        echo "[new-slice] WARN: retrospectiva de slice-${PREV_NNN} ausente ($RETRO)" >&2
        echo "[new-slice] WARN: criando slice-${NNN} mesmo assim por KALIB_SKIP_RETRO_GATE=1" >&2
      else
        cat >&2 <<EOF
[new-slice FAIL] retrospectiva do slice anterior ausente
  Slice anterior detectado: slice-${PREV_NNN}
  Retrospectiva esperada: $RETRO
  Esse arquivo não existe.

  Regra: cada slice precisa de retrospectiva antes de abrir o próximo.
  Evita acúmulo de lições não capturadas. (meta-audit #2, P1.)

  Próximos passos possíveis:
    1. Rodar /retrospective ${PREV_NNN} para criar a retrospectiva
    2. Se slice-${PREV_NNN} foi abandonado, documentar isso lá e rodar de novo
    3. Bypass de emergência (só se slice-${PREV_NNN} ainda está em execução):
         KALIB_SKIP_RETRO_GATE=1 bash scripts/new-slice.sh $NNN "$TITLE"
EOF
        exit 1
      fi
    fi
  fi
fi

# ---------------------------------------------------------------------------
# Gate: sequenciamento R13/R14 (ordem intra-epico e inter-epico, MVP).
#
# Ativo quando o titulo comeca com codigo de story (ENN-SNN:). Slices
# standalone (ex.: smoke tests, spikes) nao sao submetidos ao gate.
#
# Bypass legitimo: KALIB_SKIP_SEQUENCE="<motivo>" — grava incidente.
# ---------------------------------------------------------------------------
STORY_CODE="$(echo "$TITLE" | grep -oE '^E[0-9]{2}-S[0-9]{2}' || true)"
if [ -n "$STORY_CODE" ]; then
  bash "$SCRIPT_DIR/sequencing-check.sh" --story "$STORY_CODE"
  seq_rc=$?
  case $seq_rc in
    0) echo "[new-slice] sequencing-check OK para $STORY_CODE";;
    5) echo "[new-slice] sequencing-check bypass autorizado — prosseguindo";;
    *) echo "[new-slice FAIL] sequencing-check bloqueou $STORY_CODE (rc=$seq_rc)" >&2; exit 1;;
  esac
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
