#!/usr/bin/env bash
# start-story.sh — wrapper de /start-story com gate de sequenciamento.
#
# Valida R13/R14 via sequencing-check.sh antes de autorizar criacao de slice
# a partir do Story Contract. Nao cria o slice por si — o agente orquestrador
# faz isso apos este script retornar 0 (ou 5 em caso de bypass autorizado).
#
# Uso: bash scripts/start-story.sh ENN-SNN
#
# Exit codes:
#   0  liberada — pode criar slice
#   1  bloqueado por R13 (story anterior nao mergeada)
#   2  bloqueado por R14 (epico anterior nao fechado)
#   3  argumento invalido
#   4  artefato ausente (story contract ou project-state.json)
#   5  bypass autorizado via KALIB_SKIP_SEQUENCE (incidente gerado)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

STORY="${1:-}"
if [ -z "$STORY" ] || ! echo "$STORY" | grep -qE '^E[0-9]{2}-S[0-9]{2}$'; then
  echo "Uso: start-story.sh ENN-SNN (ex.: E02-S07)" >&2
  exit 3
fi

EPIC="${STORY%-*}"
CONTRACT="epics/${EPIC}/stories/${STORY}.md"

[ ! -f "$CONTRACT" ] && { echo "[start-story FAIL] story contract ausente: $CONTRACT" >&2; exit 4; }

bash "$SCRIPT_DIR/sequencing-check.sh" --story "$STORY"
rc=$?
case $rc in
  0) echo "[start-story] R13/R14 OK — $STORY liberada. Proximo passo: /new-slice NNN \"titulo\" com base em $CONTRACT";;
  5) echo "[start-story] bypass autorizado — prosseguindo sob responsabilidade do operador";;
  1|2) echo "[start-story] bloqueado — resolva pre-requisitos antes de prosseguir" >&2; exit $rc;;
  *) echo "[start-story] falha inesperada (rc=$rc)" >&2; exit $rc;;
esac
