#!/usr/bin/env bash
# instalar.sh — Instala statusline-command.sh v2 (colorida) em ~/.claude/
# Invocado apenas por INSTALAR-CONTEXT-MONITOR.bat.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/statusline-command.sh"

say()  { echo "[ctx-monitor] $*"; }
fail() { echo "[ctx-monitor FAIL] $*" >&2; exit 1; }

# Resolve CLAUDE_HOME (~/.claude) de forma robusta em Windows/Linux/Mac.
# Quando bash e invocado por cmd.exe, $HOME pode estar vazio — por isso
# checamos tambem $USERPROFILE (Windows) e convertemos para estilo posix.
resolve_claude_home() {
  local up="" drive="" rest="" candidate=""

  # 1. Tenta $HOME (Git Bash interativo, Linux, Mac)
  if [ -n "${HOME:-}" ] && [ -d "$HOME/.claude" ]; then
    echo "$HOME/.claude"
    return 0
  fi

  # 2. Windows: $USERPROFILE (ex.: C:\Users\rolda). Git Bash aceita
  # tanto "C:\Users\rolda/.claude" quanto "/c/Users/rolda/.claude" no teste -d.
  if [ -n "${USERPROFILE:-}" ]; then
    # Tentativa direta (Git Bash resolve C:\... para -d)
    if [ -d "$USERPROFILE/.claude" ]; then
      # Normaliza barras para estilo posix
      up="${USERPROFILE//\\/\/}"
      echo "$up/.claude"
      return 0
    fi

    # Fallback: converter C:\... para /c/... manualmente
    up="${USERPROFILE//\\/\/}"          # C:\Users\rolda -> C:/Users/rolda
    drive="${up:0:1}"                    # C
    rest="${up:2}"                       # /Users/rolda
    drive="$(echo "$drive" | tr 'A-Z' 'a-z')"
    candidate="/$drive$rest"
    if [ -d "$candidate/.claude" ]; then
      echo "$candidate/.claude"
      return 0
    fi
  fi

  return 1
}

CLAUDE_HOME="$(resolve_claude_home)" || fail "~/.claude/ nao encontrado. HOME='${HOME:-}' USERPROFILE='${USERPROFILE:-}'. Claude Code esta instalado?"
DST="$CLAUDE_HOME/statusline-command.sh"
say "~/.claude/ detectado em: $CLAUDE_HOME"

[ -f "$SRC" ] || fail "script fonte ausente: $SRC"

# Backup com timestamp
TS="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
if [ -f "$DST" ]; then
  BACKUP="$DST.bak-$TS"
  cp "$DST" "$BACKUP" || fail "falha ao criar backup em $BACKUP"
  say "backup criado: $BACKUP"
else
  say "nenhum statusline anterior — instalacao limpa"
fi

# Instalar
cp "$SRC" "$DST" || fail "falha ao copiar $SRC para $DST"
chmod +x "$DST" 2>/dev/null || true
say "instalado: $DST"

# Validacao minima de sintaxe
if ! bash -n "$DST" 2>/dev/null; then
  fail "novo script tem erro de sintaxe — restaurando backup"
fi
say "sintaxe validada"

# Smoke test: rodar com JSON minimo e ver se sai alguma coisa
SMOKE_OUTPUT=$(echo '{"id":"claude-opus-4-7","display_name":"Opus 4.7","context_window_size":1000000,"input_tokens":100000,"output_tokens":50000,"cache_read_input_tokens":0,"total_cost_usd":0.42,"current_dir":"/tmp"}' | bash "$DST" 2>&1 || true)
if [ -z "$SMOKE_OUTPUT" ]; then
  fail "smoke test falhou — script nao produziu saida"
fi
say "smoke test OK: $SMOKE_OUTPUT"

echo ""
say "======================================================"
say " CONTEXT MONITOR v1 INSTALADO"
say "======================================================"
say ""
say "Feche e reabra o Claude Code para ver a statusline colorida."
exit 0
