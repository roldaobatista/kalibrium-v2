#!/usr/bin/env bash
# explain-slice.sh — gera relatório do slice em linguagem de produto (R12).
#
# Pré-B-010: este script era um stub que só criava template com placeholders
# que o agente principal preenchia ad-hoc.
#
# Pós-B-010 (2026-04-12): delega para scripts/translate-pm.sh, que é a
# implementação automática (tradução mecânica via glossary-pm + templates de
# severity/category). Este arquivo fica fino — apenas validação de pré-condições
# e roteamento para o translator.
#
# Compatibilidade: mantém mesma interface CLI (`explain-slice.sh NNN`) e mesma
# saída (`docs/explanations/slice-NNN.md`) pra não quebrar a skill /explain-slice.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
if [ -z "$NNN" ] || ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "Uso: explain-slice.sh NNN" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
[ ! -d "$SLICE_DIR" ] && { echo "[explain-slice FAIL] slice $NNN não existe em $SLICE_DIR" >&2; exit 1; }
[ ! -f "$SLICE_DIR/spec.md" ] && { echo "[explain-slice FAIL] spec.md ausente em $SLICE_DIR" >&2; exit 1; }

TRANSLATOR="$SCRIPT_DIR/translate-pm.sh"
if [ ! -f "$TRANSLATOR" ]; then
  echo "[explain-slice FAIL] translator ausente ($TRANSLATOR) — B-010 não está instalado" >&2
  exit 1
fi

# Delega tradução automática
bash "$TRANSLATOR" "$NNN"
