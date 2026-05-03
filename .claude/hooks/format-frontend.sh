#!/usr/bin/env bash
# PostToolUse Edit|Write — formata arquivo de frontend (.js/.ts/.jsx/.tsx/.vue/.css/.json)
# usando Prettier se disponível. Não bloqueia, não falha em silêncio gritando.

set -euo pipefail

input=$(cat)

file_path=$(printf '%s' "$input" | sed -n 's/.*"file_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)

if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
  exit 0
fi

case "$file_path" in
  *.js|*.jsx|*.ts|*.tsx|*.vue|*.css|*.scss|*.json|*.md) ;;
  *) exit 0 ;;
esac

# Prefere binário local do projeto (npm install)
if [ -x "node_modules/.bin/prettier" ]; then
  ./node_modules/.bin/prettier --write --log-level=silent "$file_path" >/dev/null 2>&1 || true
fi

exit 0
