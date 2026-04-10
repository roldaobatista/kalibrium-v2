#!/usr/bin/env bash
# PreToolUse Bash hook — R10 enforcement.
# Bloqueia comandos de inicialização de projeto se ADR-0001 não existir.
set -euo pipefail

CMD="${CLAUDE_TOOL_ARG_COMMAND:-${1:-}}"
[ -z "$CMD" ] && exit 0

# Padrões de init que exigem ADR-0001
FORBIDDEN_PATTERNS=(
  "npm init"
  "npm create"
  "yarn create"
  "pnpm create"
  "bun create"
  "deno init"
  "composer create-project"
  "cargo init"
  "cargo new"
  "django-admin startproject"
  "rails new"
  "dotnet new sln"
  "dotnet new webapi"
  "dotnet new mvc"
  "go mod init"
  "mix phx.new"
  "mix new"
)

for pat in "${FORBIDDEN_PATTERNS[@]}"; do
  if echo "$CMD" | grep -qF "$pat"; then
    if [ ! -f "docs/adr/0001-stack-choice.md" ]; then
      echo "[block-project-init BLOCK] R10: '$pat' bloqueado — crie docs/adr/0001-stack-choice.md primeiro" >&2
      echo "  Comando tentado: $CMD" >&2
      exit 1
    fi
  fi
done

# Detecta bypass de gates
if echo "$CMD" | grep -qE -- "--no-verify|--no-gpg-sign|HUSKY=0|SKIP="; then
  echo "[block-project-init BLOCK] R9/P9: bypass de gate detectado em '$CMD'" >&2
  exit 1
fi

exit 0
