#!/usr/bin/env bash
# Stop hook — valida estado antes de encerrar a sessão.
#
# Checa que o agente não está deixando para trás:
#  - Teste vermelho sem commit
#  - Hook desabilitado
#  - Arquivo proibido recém-criado
#  - Slice em estado inconsistente (spec aprovado mas plan ausente, etc.)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

WARN=0
warn() { echo "[stop-gate WARN] $*" >&2; WARN=$((WARN+1)); }

# R1 quick re-check
for f in .cursorrules AGENTS.md GEMINI.md copilot-instructions.md .windsurfrules .aider.conf.yml; do
  [ -f "$f" ] && warn "R1: $f apareceu durante a sessão"
done
for d in .bmad-core .agents .cursor .continue; do
  [ -d "$d" ] && warn "R1: diretório $d apareceu durante a sessão"
done

# settings.json tocado?
if git diff --quiet .claude/settings.json 2>/dev/null; then
  : # limpo
else
  warn ".claude/settings.json foi modificado — /guide-check recomendado"
fi

# Arquivos modificados não commitados
UNCOMMITTED="$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')"
if [ "$UNCOMMITTED" -gt 0 ]; then
  warn "há $UNCOMMITTED arquivo(s) não commitados — verificar antes de encerrar"
fi

# Slice ativo coerente?
if [ -f specs/.current ]; then
  SLICE="$(cat specs/.current)"
  if [ -n "$SLICE" ]; then
    [ ! -f "specs/$SLICE/spec.md" ] && warn "slice $SLICE ativo mas spec.md ausente"
  fi
fi

[ "$WARN" -gt 0 ] && echo "[stop-gate] $WARN aviso(s) — não-bloqueante mas revisar" >&2
exit 0
