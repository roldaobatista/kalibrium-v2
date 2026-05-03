#!/usr/bin/env bash
# Formata arquivo .php automaticamente após Edit/Write.
# Pint corrige in-place; nenhum bloqueio é feito (PostToolUse não bloqueia).

set -euo pipefail

input=$(cat)

# Extrai file_path do JSON do hook
file_path=$(printf '%s' "$input" | sed -n 's/.*"file_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)

# Só age em arquivo .php existente
if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
  exit 0
fi

case "$file_path" in
  *.php) ;;
  *) exit 0 ;;
esac

# Roda Pint silenciosamente. Erros não bloqueiam (PostToolUse).
if [ -x vendor/bin/pint ]; then
  vendor/bin/pint "$file_path" >/dev/null 2>&1 || true
fi

exit 0
