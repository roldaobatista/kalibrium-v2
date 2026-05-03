#!/usr/bin/env bash
# Bloqueia comandos destrutivos antes de executarem.
# Recebe JSON do hook via stdin; encerra com exit 2 (bloqueio) se detectar padrão perigoso.

set -euo pipefail

input=$(cat)

# Extrai o comando do JSON. Formato: {"tool_input":{"command":"..."}}
command=$(printf '%s' "$input" | sed -n 's/.*"command"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)

if [ -z "$command" ]; then
  exit 0
fi

# Padrões destrutivos que exigem confirmação explícita do Roldão.
# Lista alinhada com memory/feedback_destrutivo.md
patterns=(
  'git[[:space:]]+push[[:space:]]+(-f|--force)'
  'git[[:space:]]+push[[:space:]]+.*--force-with-lease'
  'git[[:space:]]+reset[[:space:]]+--hard'
  'git[[:space:]]+branch[[:space:]]+-D'
  'git[[:space:]]+clean[[:space:]]+-f'
  'git[[:space:]]+checkout[[:space:]]+\.'
  'git[[:space:]]+restore[[:space:]]+\.'
  'rm[[:space:]]+-rf'
  '\brm[[:space:]]+-fr\b'
  'DROP[[:space:]]+TABLE'
  'DROP[[:space:]]+DATABASE'
  'TRUNCATE[[:space:]]+TABLE'
  'migrate:fresh'
  'migrate:reset'
  'migrate:rollback'
)

for pattern in "${patterns[@]}"; do
  if printf '%s' "$command" | grep -qiE "$pattern"; then
    cat >&2 <<EOF
Operação destrutiva bloqueada pelo hook de proteção:
  Comando: $command

Esta ação é irreversível ou cara. Antes de executar, pergunte ao Roldão em pt-BR
sem jargão. Se ele confirmar, a maestra deve aprovar manualmente o comando.

Lista de operações que sempre exigem confirmação:
memory/feedback_destrutivo.md
EOF
    exit 2
  fi
done

exit 0
