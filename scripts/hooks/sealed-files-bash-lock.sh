#!/usr/bin/env bash
# sealed-files-bash-lock.sh — bloqueia escrita Bash em arquivos selados.
#
# Origem: meta-audit 2026-04-10, item 1.6 (observação do PM ao 1.1/1.2).
#
# Vetor coberto: settings-lock.sh / hooks-lock.sh / telemetry-lock.sh
# matcher PreToolUse Edit|Write não dispara para Bash. O agente pode escrever
# em arquivo selado via:
#   echo '...' >> .claude/settings.json
#   sed -i '...' scripts/hooks/post-edit-gate.sh
#   tee .claude/allowed-git-identities.txt
#   cp /tmp/evil .claude/settings.json
#   mv /tmp/evil .claude/settings.json
#   truncate -s 0 .claude/telemetry/slice-001.jsonl
#   dd of=.claude/settings.json
#   python -c "open('.claude/settings.json','w').write(...)"
#   awk ... > .claude/settings.json
#
# Estratégia: bloqueia QUALQUER comando Bash que mencione um path selado.
# É conservador (também bloqueia leituras como `cat .claude/settings.json`),
# mas todos os reads legítimos podem usar o tool Read (que não passa por
# este hook). O único caminho legítimo de mutação é scripts/relock-harness.sh,
# que mutua via shell sem expor os paths selados na linha de comando.
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
)

# Exceção: relock-harness.sh é o caminho legítimo para mutar selos.
# Reconhece a invocação por nome do script (ele mesmo não menciona os paths
# na linha de comando — usa os paths internamente).
case "$COMMAND" in
  *relock-harness.sh*) exit 0 ;;
esac

# Exceção: read-only via tools dedicados (git, ls) com path selado é
# permitido se o comando for claramente de inspeção sem mutação.
# Sub-shell helper: detecta se o comando tem qualquer indicador de escrita
# nos paths selados.
contains_write_intent() {
  local cmd="$1"
  case "$cmd" in
    *">"*|*">>"*|*"|"*tee*|*sed\ -i*|*sed\ -i\'*|*sed\ --in-place*|\
*cp\ *|*mv\ *|*dd\ of=*|*truncate*|*">| "*|*"chmod"*|*"chown"*|*"rm "*|*"rm -"*|\
*"python -c"*|*"perl -e"*|*"perl -pi"*|*"awk"*|*"echo"*">"*|*"printf"*">"*)
      return 0
      ;;
  esac
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
