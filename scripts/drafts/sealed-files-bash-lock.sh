#!/usr/bin/env bash
# sealed-files-bash-lock.sh v2 — bloqueia escrita Bash em arquivos selados.
#
# Melhoria sobre v1: adiciona detecção de:
#   - xargs com write: echo path | xargs cp
#   - bash -c / sh -c com escrita aninhada
#   - node -e / ruby -e / php -r com file writes
#   - install command (coreutils install)
#   - tee sem pipe (tee file < input)
#   - cat > file (heredoc redirect)
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_TOOL_ARG_COMMAND — comando completo do Bash tool

set -uo pipefail

COMMAND="${CLAUDE_TOOL_ARG_COMMAND:-${1:-}}"
[ -z "$COMMAND" ] && exit 0

# Lista de paths selados que NÃO podem aparecer em comandos Bash do agente.
# Mantida em sincronia com settings-lock.sh + telemetry-lock.sh.
SEALED_PATHS=(
  ".claude/settings.json"
  ".claude/settings.json.sha256"
  "scripts/hooks/MANIFEST.sha256"
  ".claude/allowed-git-identities.txt"
  ".claude/git-identity-baseline"
  ".claude/telemetry/"
  ".claude/keybindings.json"
)

# Exceção: relock-harness.sh é o caminho legítimo para mutar selos.
case "$COMMAND" in
  *relock-harness.sh*) exit 0 ;;
esac

# Detecta intenção de escrita — v2 expandida
contains_write_intent() {
  local cmd="$1"
  case "$cmd" in
    # Operadores de redireção
    *">"*|*">>"*|*">"*) return 0 ;;
  esac

  # Padrões de comando com escrita (mais granulares)
  if echo "$cmd" | grep -qE \
    "tee |tee$|sed -i|sed --in-place|cp |mv |dd of=|truncate|chmod |chown |rm |rm$|install "; then
    return 0
  fi

  # Interpretadores inline que podem escrever arquivos
  if echo "$cmd" | grep -qE \
    "python[23]? -c|perl -[epi]|ruby -e|node -e|php -r"; then
    return 0
  fi

  # xargs com comandos de escrita
  if echo "$cmd" | grep -qE "xargs.*(cp|mv|rm|tee|dd|install|chmod|chown)"; then
    return 0
  fi

  # bash -c / sh -c com escrita aninhada
  if echo "$cmd" | grep -qE "(bash|sh|zsh) -c"; then
    # Verifica se o subcomando contém escrita
    local subcmd
    subcmd="$(echo "$cmd" | grep -oE "(bash|sh|zsh) -c ['\"].*['\"]" || true)"
    if [ -n "$subcmd" ]; then
      if echo "$subcmd" | grep -qE ">|>>|tee |sed -i|cp |mv |dd |truncate|rm "; then
        return 0
      fi
    fi
  fi

  # echo/printf/cat com pipe to file
  if echo "$cmd" | grep -qE "(echo|printf|cat).*\|.*(tee|dd)"; then
    return 0
  fi

  return 1
}

for sealed in "${SEALED_PATHS[@]}"; do
  if echo "$COMMAND" | grep -qF "$sealed"; then
    # Path selado mencionado. Verifica se há intenção de escrita.
    if contains_write_intent "$COMMAND"; then
      echo "[sealed-files-bash-lock BLOCK] comando Bash tenta mutar arquivo selado: $sealed" >&2
      echo "  Comando: $COMMAND" >&2
      echo "  Origem: meta-audit 2026-04-10 item 1.6 (observação PM ao 1.1/1.2)" >&2
      echo "" >&2
      echo "  Para mutar arquivo selado:" >&2
      echo "    1. Saia do Claude Code." >&2
      echo "    2. Edite manualmente em terminal externo." >&2
      echo "    3. Rode: KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh" >&2
      exit 1
    fi
    # Path selado mencionado mas sem intent de escrita: bloqueia mesmo assim,
    # como defesa em profundidade. Reads legítimos devem usar tool Read.
    echo "[sealed-files-bash-lock BLOCK] comando Bash menciona arquivo selado: $sealed" >&2
    echo "  Use o tool Read em vez de Bash para inspecionar arquivos selados." >&2
    echo "  Comando: $COMMAND" >&2
    exit 1
  fi
done

exit 0
