#!/usr/bin/env bash
# mcp-check.sh — valida que só MCPs listados em .claude/allowed-mcps.txt estão ativos.
# Complementa /forbidden-files-scan: cobre o vetor "contaminação via servidor externo".
# Registra evento em telemetria global do harness.
#
# Uso:
#   bash scripts/mcp-check.sh              # imprime diff e retorna 0 (ok) / 1 (drift)
#   bash scripts/mcp-check.sh --quiet      # só exit code, sem prints
#
# Fontes de MCPs ativos (em ordem de prioridade):
#   1. Env KALIB_ACTIVE_MCPS  (lista separada por vírgula — setada pelo harness)
#   2. Arquivo .mcp.json      (legacy/fallback local)
#   3. claude mcp list        (se o binário estiver no PATH)
#
# Em NENHUM caso lê `.claude/settings.json` — é selado.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

QUIET=0
[ "${1:-}" = "--quiet" ] && QUIET=1

ALLOWLIST=".claude/allowed-mcps.txt"
TELEMETRY=".claude/telemetry/harness.jsonl"

say()  { [ "$QUIET" -eq 0 ] && echo "[mcp-check] $*"; }
warn() { echo "[mcp-check WARN] $*" >&2; }
fail() { echo "[mcp-check FAIL] $*" >&2; exit 1; }

[ ! -f "$ALLOWLIST" ] && fail "allowlist ausente: $ALLOWLIST"

# Normaliza allowlist — remove comentários, linhas vazias, espaços
ALLOWED="$(grep -vE '^\s*(#|$)' "$ALLOWLIST" | tr -d ' \t')"

# Descobre MCPs ativos
ACTIVE=""
SOURCE=""
if [ -n "${KALIB_ACTIVE_MCPS:-}" ]; then
  ACTIVE="$(echo "$KALIB_ACTIVE_MCPS" | tr ',' '\n' | tr -d ' \t' | grep -v '^$' || true)"
  SOURCE="env KALIB_ACTIVE_MCPS"
elif [ -f .mcp.json ]; then
  # Heurística: chaves de primeiro nível do objeto raiz
  ACTIVE="$(grep -oE '"[a-zA-Z0-9_:.-]+"\s*:\s*\{' .mcp.json | sed -E 's/"([^"]+)".*/\1/' | tr -d ' \t' | grep -v '^$' || true)"
  SOURCE=".mcp.json"
elif command -v claude >/dev/null 2>&1 || command -v claude.exe >/dev/null 2>&1; then
  CLAUDE_BIN="$(command -v claude 2>/dev/null || command -v claude.exe 2>/dev/null)"
  ACTIVE="$("$CLAUDE_BIN" mcp list 2>/dev/null | sed -nE 's/^(.+): .*/\1/p' | tr -d ' \t' | grep -v '^$' || true)"
  SOURCE="$CLAUDE_BIN mcp list"
else
  warn "nenhuma fonte de MCPs ativos detectada — assumindo lista vazia"
  ACTIVE=""
  SOURCE="(vazio)"
fi

say "fonte: $SOURCE"

# Diff
UNAUTHORIZED=""
MISSING=""

if [ -n "$ACTIVE" ]; then
  while IFS= read -r mcp; do
    [ -z "$mcp" ] && continue
    if ! printf '%s\n' "$ALLOWED" | grep -qxF "$mcp"; then
      UNAUTHORIZED="${UNAUTHORIZED}${mcp}"$'\n'
    fi
  done <<< "$ACTIVE"
fi

while IFS= read -r mcp; do
  [ -z "$mcp" ] && continue
  if [ -z "$ACTIVE" ] || ! printf '%s\n' "$ACTIVE" | grep -qxF "$mcp"; then
    MISSING="${MISSING}${mcp}"$'\n'
  fi
done <<< "$ALLOWED"

UNAUTHORIZED="$(printf '%s' "$UNAUTHORIZED" | grep -v '^$' || true)"
MISSING="$(printf '%s' "$MISSING" | grep -v '^$' || true)"

STATUS="ok"
[ -n "$UNAUTHORIZED" ] && STATUS="unauthorized"

say ""
say "Autorizados (allowlist):"
printf '%s\n' "$ALLOWED" | sed 's/^/  - /' | { [ "$QUIET" -eq 0 ] && cat || cat >/dev/null; }
say "Ativos (ambiente):"
if [ -z "$ACTIVE" ]; then
  say "  (vazio)"
else
  printf '%s\n' "$ACTIVE" | sed 's/^/  - /' | { [ "$QUIET" -eq 0 ] && cat || cat >/dev/null; }
fi

if [ -n "$UNAUTHORIZED" ]; then
  [ "$QUIET" -eq 0 ] && echo ""
  [ "$QUIET" -eq 0 ] && echo "[mcp-check] ✗ MCPs ativos NÃO autorizados:"
  printf '%s\n' "$UNAUTHORIZED" | sed 's/^/  ! /' >&2
fi
if [ -n "$MISSING" ]; then
  [ "$QUIET" -eq 0 ] && echo ""
  [ "$QUIET" -eq 0 ] && echo "[mcp-check] · autorizados mas ausentes do ambiente (ok, só aviso):"
  [ "$QUIET" -eq 0 ] && printf '%s\n' "$MISSING" | sed 's/^/  · /'
fi

# Telemetria — só se record-telemetry existir
if [ -f "$SCRIPT_DIR/record-telemetry.sh" ]; then
  mkdir -p ".claude/telemetry"
  touch "$TELEMETRY"
  bash "$SCRIPT_DIR/record-telemetry.sh" \
    --event=mcp-check \
    --slice="harness" \
    --verdict="$STATUS" \
    --next-action="monitor" \
    --reject-count="0" >/dev/null 2>&1 || true
fi

if [ "$STATUS" != "ok" ]; then
  say ""
  fail "MCPs não autorizados detectados — revisar e remover antes de continuar"
fi

say ""
say "OK — apenas MCPs autorizados ativos"
exit 0
