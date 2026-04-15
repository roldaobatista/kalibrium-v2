#!/usr/bin/env bash
# scripts/sealed-diff.sh — verifica integridade dos arquivos selados sob demanda.
#
# Invocado por /sealed-diff. Não altera nada, apenas reporta.
# Chama os hooks canônicos em modo --check e consolida a saída.
#
# Exit codes:
#   0 — todos os selos batem
#   1 — drift detectado em pelo menos um selo
#   2 — erro de execução (script de check ausente, etc.)

set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

STATUS=0

print_header() {
  printf "${BOLD}=== %s ===${NC}\n" "$1"
}

run_check() {
  local label="$1"
  local cmd="$2"
  print_header "$label"
  if ! eval "$cmd"; then
    STATUS=1
    printf "${RED}✗ drift detectado em %s${NC}\n" "$label"
  else
    printf "${GREEN}✓ %s OK${NC}\n" "$label"
  fi
  echo
}

# 1. Settings selado (.claude/settings.json)
if [[ -x scripts/hooks/settings-lock.sh ]]; then
  run_check "settings-lock (.claude/settings.json)" \
    "bash scripts/hooks/settings-lock.sh --check"
else
  printf "${RED}✗ scripts/hooks/settings-lock.sh ausente ou sem +x${NC}\n"
  STATUS=2
fi

# 2. Manifest de hooks (scripts/hooks/MANIFEST.sha256)
if [[ -x scripts/hooks/hooks-lock.sh ]]; then
  run_check "hooks-lock (scripts/hooks/*)" \
    "bash scripts/hooks/hooks-lock.sh --check"
else
  printf "${RED}✗ scripts/hooks/hooks-lock.sh ausente ou sem +x${NC}\n"
  STATUS=2
fi

# 3. Identidade git vs allowlist autorizada
#    .claude/allowed-git-identities.txt é a fonte real da allowlist de emails.
#    .claude/git-identity-baseline é apenas uma âncora de SHA para auditoria histórica
#    (usada por scripts/guide-check.sh CHECK-3), não uma lista de emails.
if [[ -f .claude/allowed-git-identities.txt ]]; then
  print_header "git-identity (allowlist)"
  CURRENT_AUTHOR="$(git config user.name 2>/dev/null || echo '<unset>')"
  CURRENT_EMAIL="$(git config user.email 2>/dev/null || echo '<unset>')"
  printf "atual: %s <%s>\n" "$CURRENT_AUTHOR" "$CURRENT_EMAIL"
  # allowlist file tem uma entrada de email por linha (ignora comentários e linhas vazias)
  if grep -v '^\s*#' .claude/allowed-git-identities.txt \
     | grep -v '^\s*$' \
     | grep -qF "$CURRENT_EMAIL"; then
    printf "${GREEN}✓ identidade em allowed-git-identities.txt${NC}\n"
  else
    printf "${YELLOW}! identidade não está em allowed-git-identities.txt — commits podem falhar no author-check${NC}\n"
  fi
  echo
fi

# Resumo final
print_header "resumo"
case "$STATUS" in
  0) printf "${GREEN}${BOLD}SELOS OK${NC} — nenhum drift detectado.\n" ;;
  1) printf "${RED}${BOLD}DRIFT DETECTADO${NC} — investigar antes de qualquer ação.\n"
     printf "Consultar CLAUDE.md §9 e docs/incidents/ antes de usar relock-harness.sh.\n" ;;
  2) printf "${RED}${BOLD}ERRO DE EXECUÇÃO${NC} — script de check ausente. Harness pode estar comprometido.\n" ;;
esac

exit "$STATUS"
