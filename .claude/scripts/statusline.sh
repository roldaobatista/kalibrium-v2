#!/usr/bin/env bash
# Statusline em pt-BR sem jargão — mostra ramo, mudanças pendentes, história ativa.

set -euo pipefail

# Ramo de trabalho atual
branch=$(git branch --show-current 2>/dev/null || echo "—")

# Mudanças não salvas (working tree + staged)
pending=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
if [ "$pending" = "0" ]; then
  pending_label="tudo salvo"
else
  pending_label="$pending mudança(s) pendente(s)"
fi

# História ativa (primeiro arquivo em ativas/, se houver)
agora="sem história ativa"
if [ -d docs/backlog/historias/ativas ]; then
  ativa=$(find docs/backlog/historias/ativas -maxdepth 1 -name "*.md" -type f 2>/dev/null | head -1)
  if [ -n "$ativa" ]; then
    titulo=$(grep -m1 -E '^#\s+' "$ativa" 2>/dev/null | sed -E 's/^#\s+//; s/^História:\s*//i' | head -c 60)
    [ -n "$titulo" ] && agora="$titulo"
  fi
fi

printf "ramo: %s · %s · agora: %s" "$branch" "$pending_label" "$agora"
