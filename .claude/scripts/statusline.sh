#!/usr/bin/env bash
# Statusline em pt-BR sem jargão — ramo, mudanças, história ativa, fila de espera, alertas.

set -euo pipefail

# Ramo de trabalho atual
branch=$(git branch --show-current 2>/dev/null || echo "—")

# Mudanças não salvas (working tree + staged)
pending=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
if [ "$pending" = "0" ]; then
  pending_label="tudo salvo"
else
  pending_label="$pending mudança(s)"
fi

# História ativa (primeiro arquivo em ativas/, se houver)
agora="sem história ativa"
if [ -d docs/backlog/historias/ativas ]; then
  ativa=$(find docs/backlog/historias/ativas -maxdepth 1 -name "*.md" -type f 2>/dev/null | head -1)
  if [ -n "$ativa" ]; then
    titulo=$(grep -m1 -E '^#\s+' "$ativa" 2>/dev/null | sed -E 's/^#\s+//; s/^História:\s*//i' | head -c 50)
    [ -n "$titulo" ] && agora="$titulo"
  fi
fi

# Fila — quantas histórias aguardando aprovação
aguardando=$(find docs/backlog/historias/aguardando -maxdepth 1 -name "*.md" -type f 2>/dev/null | wc -l | tr -d ' ')

# Alertas: testes vermelhos do último run (Pest cache)
alerta=""
if [ -f .phpunit.cache/test-results ]; then
  vermelhos=$(grep -c '"failed"' .phpunit.cache/test-results 2>/dev/null || echo 0)
  vermelhos=$(printf '%s' "$vermelhos" | tr -d ' \n')
  if [ -n "$vermelhos" ] && [ "$vermelhos" != "0" ]; then
    alerta=" ⚠ $vermelhos teste(s) vermelho(s)"
  fi
fi

# Monta linha final
fila_label=""
[ "$aguardando" != "0" ] && fila_label=" · fila: $aguardando"

printf "ramo: %s · %s · agora: %s%s%s" "$branch" "$pending_label" "$agora" "$fila_label" "$alerta"
