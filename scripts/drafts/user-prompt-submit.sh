#!/usr/bin/env bash
# UserPromptSubmit hook — sanitização de input + reminders contextuais.
#
# Melhoria sobre v1 (placeholder de 374B). Agora:
# 1. Injeta reminder P7/P8 (como antes)
# 2. Detecta padrões de prompt injection no input do PM
# 3. Detecta comandos perigosos mencionados pelo PM
# 4. Reminder contextual R12 quando slice ativo
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_USER_PROMPT — texto completo do prompt do usuário

set -euo pipefail

PROMPT="${CLAUDE_USER_PROMPT:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

WARNINGS=""

# ---------- 1. Detecção de prompt injection ----------
# Padrões que indicam tentativa de manipular o agente
if echo "$PROMPT" | grep -qiE "ignore (previous|all|above|prior) instructions"; then
  WARNINGS="${WARNINGS}[WARN] Padrão de prompt injection detectado: 'ignore instructions'. "
fi
if echo "$PROMPT" | grep -qiE "you are now|your new role is|forget your rules"; then
  WARNINGS="${WARNINGS}[WARN] Padrão de prompt injection detectado: role override. "
fi
if echo "$PROMPT" | grep -qiE "disable (hooks|gates|verification|sealed)"; then
  WARNINGS="${WARNINGS}[WARN] Solicitação de desabilitar mecanismos de segurança detectada. "
fi

# ---------- 2. Detecção de comandos destrutivos ----------
if echo "$PROMPT" | grep -qiE "rm -rf|drop table|truncate|reset --hard|push --force|--no-verify"; then
  WARNINGS="${WARNINGS}[WARN] Operação destrutiva mencionada — agente deve confirmar antes de executar (§11 CLAUDE.md). "
fi

# ---------- 3. Reminder contextual ----------
REMINDERS="P7: não afirmar pronto/corrigido sem comando + output + exit code. P8: nunca rodar suite full no meio de uma task."

# Se há slice ativo, adicionar reminder de foco
if [ -f "$REPO_ROOT/specs/.current" ]; then
  CURRENT_SLICE="$(cat "$REPO_ROOT/specs/.current" 2>/dev/null || echo '')"
  if [ -n "$CURRENT_SLICE" ]; then
    REMINDERS="${REMINDERS} Slice ativo: $CURRENT_SLICE — manter foco no escopo."
  fi
fi

# ---------- 4. Output ----------
SYSTEM_MSG="[reminder] ${REMINDERS}"
if [ -n "$WARNINGS" ]; then
  SYSTEM_MSG="${SYSTEM_MSG} ${WARNINGS}"
fi

cat <<JSON
{"systemMessage":"$SYSTEM_MSG"}
JSON
exit 0
