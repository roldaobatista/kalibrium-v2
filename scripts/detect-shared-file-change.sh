#!/usr/bin/env bash
# Slice 018 — AC-002, AC-002-A (B-036)
#
# Detector de mudancas em arquivos compartilhados (lista fechada).
#
# Convencao Unix:
#   - Le diff via stdin (1 path por linha).
#   - Sempre exit 0 em operacao bem-sucedida.
#   - Imprime em stdout: `shared_changed=true` ou `shared_changed=false`.
#   - Exit != 0 so em erro interno do script.
#
# Uso em pipeline:
#   git diff --name-only @{push}..HEAD | scripts/detect-shared-file-change.sh
#
# Lista fechada (decisao D2 do plan.md). Editar aqui requer PR dedicado.

set -euo pipefail

# Lista de arquivos/patterns compartilhados cujo toque obriga smoke.
SHARED_FILES=(
  "src/main.tsx"
  "vite.config.ts"
  "package.json"
  "capacitor.config.ts"
  "playwright.config.ts"
)

# Pattern glob para settings do Claude (casa settings.json e settings.local.json)
SHARED_GLOB_PATTERNS=(
  ".claude/settings"
)

shared_changed=false

# Le stdin linha a linha
while IFS= read -r line; do
  # Ignora linhas vazias
  [ -z "$line" ] && continue
  # Remove \r eventual (stdin vindo de Windows CRLF)
  line="${line%$'\r'}"

  # 1) Match exato em SHARED_FILES
  for f in "${SHARED_FILES[@]}"; do
    if [ "$line" = "$f" ]; then
      shared_changed=true
      break
    fi
  done
  [ "$shared_changed" = "true" ] && break

  # 2) Match prefixo/padrao para .claude/settings*
  for pat in "${SHARED_GLOB_PATTERNS[@]}"; do
    case "$line" in
      "$pat"*.json)
        shared_changed=true
        break
        ;;
    esac
  done
  [ "$shared_changed" = "true" ] && break
done

echo "shared_changed=$shared_changed"
exit 0
