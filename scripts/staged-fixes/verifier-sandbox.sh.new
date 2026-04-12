#!/usr/bin/env bash
# PreToolUse hook — enforce R3 (verifier isolation) + R11 (dual-verifier).
#
# Cobertura:
#   - Read|Grep|Glob: path-based sandbox para verifier/reviewer/security-reviewer/
#                      test-auditor/functional-reviewer
#   - Bash:           comandos de inspeção (cat/less/head/tail/...) bloqueados
#                     fora dos diretórios sandbox quando AGENT é gate isolado
#                     (item 1.6 meta-audit)
#
# Cinco contextos isolados:
#   - verifier:            só pode ler verification-input/       (R3)
#   - reviewer:            só pode ler review-input/             (R11)
#   - security-reviewer:   só pode ler security-review-input/    (F2 audit 2026-04-12)
#   - test-auditor:        só pode ler test-audit-input/         (F2 audit 2026-04-12)
#   - functional-reviewer: só pode ler functional-review-input/  (F2 audit 2026-04-12)
#              + Cada gate é BLOQUEADO de ler output de outros gates
#
# Defesa contra CLAUDE_AGENT_NAME vazio (item 1.4 meta-audit):
#   detecção multi-sinal de contexto sub-agent quando o env var não foi setado
#   pelo orquestrador. Vetor coberto: audit-claude-opus §B.2 + audit-codex §Ameaça #2.
#
# Canonicalização de path (item 1.5 meta-audit):
#   resolve symlinks/.. ANTES do match para fechar §D.2 (symlink escape).
#
# Variáveis injetadas pelo Claude Code:
#   CLAUDE_AGENT_NAME       — nome do sub-agent atual ("" no agente principal)
#   CLAUDE_TOOL_ARG_FILE    — caminho do arquivo que o tool tentou acessar
#   CLAUDE_TOOL_ARG_COMMAND — comando completo (Bash matcher)
#   (fallback: $1)

set -euo pipefail

AGENT="${CLAUDE_AGENT_NAME:-}"
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
COMMAND="${CLAUDE_TOOL_ARG_COMMAND:-}"

# ----------------------------------------------------------------------
# Detecção multi-sinal de contexto sub-agent (item 1.4 meta-audit).
# Defesa em profundidade — basta UMA camada disparar.
# ----------------------------------------------------------------------
detect_subagent_context() {
  # Sinal 1: working dir é uma worktree git? (.git é arquivo, não diretório)
  # Worktrees criados via Agent tool com isolation:"worktree" têm .git como
  # arquivo apontando para .git/worktrees/<nome> do main repo.
  [ -f .git ] && return 0

  # Sinal 2: comparar toplevel atual com main worktree do repo
  local toplevel main_wt
  toplevel="$(git rev-parse --show-toplevel 2>/dev/null || echo '')"
  main_wt="$(git worktree list --porcelain 2>/dev/null | awk '/^worktree/ {print $2; exit}')"
  if [ -n "$toplevel" ] && [ -n "$main_wt" ] && [ "$toplevel" != "$main_wt" ]; then
    return 0
  fi
  return 1  # main context
}

# ----------------------------------------------------------------------
# Tratamento de CLAUDE_AGENT_NAME vazio
# ----------------------------------------------------------------------
if [ -z "$AGENT" ]; then
  if detect_subagent_context; then
    echo "[verifier-sandbox BLOCK] CLAUDE_AGENT_NAME vazio em contexto de sub-agent (worktree) — fail-closed (R3)" >&2
    echo "  Origem: meta-audit 2026-04-10 item 1.4 + audit-claude-opus §B.2 + audit-codex §Ameaça #2" >&2
    exit 1
  fi
  exit 0  # main agent legítimo
fi

# ----------------------------------------------------------------------
# Sub-agents NÃO isolados (implementer, architect, ac-to-test, etc)
# ----------------------------------------------------------------------
case "$AGENT" in
  verifier|reviewer|security-reviewer|test-auditor|functional-reviewer) : ;;  # continua para checks específicos abaixo
  *)
    if detect_subagent_context; then
      echo "[verifier-sandbox BLOCK] sub-agent '$AGENT' rodando em worktree (apenas gates isolados permitidos)" >&2
      exit 1
    fi
    exit 0  # implementer/architect rodando no main repo: passa
    ;;
esac

# ======================================================================
# Daqui em diante: AGENT é um dos 5 gates isolados
# ======================================================================

# Define o sandbox dir esperado para este agente
case "$AGENT" in
  verifier)            SANDBOX_DIR="verification-input" ;;
  reviewer)            SANDBOX_DIR="review-input" ;;
  security-reviewer)   SANDBOX_DIR="security-review-input" ;;
  test-auditor)        SANDBOX_DIR="test-audit-input" ;;
  functional-reviewer) SANDBOX_DIR="functional-review-input" ;;
esac

# ----------------------------------------------------------------------
# Item 1.6: cobertura de Bash para sub-agents
# Bloqueia comandos de leitura/inspeção quando o alvo claramente não está
# dentro do sandbox dir do agente.
# ----------------------------------------------------------------------
if [ -n "$COMMAND" ]; then
  # Padrão de comandos de inspeção que poderiam ler arquivos sensíveis
  case "$COMMAND" in
    cat\ *|*\ cat\ *|less\ *|*\ less\ *|head\ *|*\ head\ *|tail\ *|*\ tail\ *|more\ *|od\ *|xxd\ *|hexdump\ *|strings\ *|dd\ if=*|*\ dd\ if=*)
      # Heurística conservadora: se o comando NÃO menciona o sandbox dir, bloqueia.
      if ! echo "$COMMAND" | grep -qE "(^|[[:space:]/])${SANDBOX_DIR}(/|[[:space:]]|$)"; then
        echo "[verifier-sandbox BLOCK] $AGENT tentou comando de inspeção fora de $SANDBOX_DIR/" >&2
        echo "  Comando: $COMMAND" >&2
        echo "  Origem: meta-audit 2026-04-10 item 1.6" >&2
        exit 1
      fi
      ;;
  esac
  # Outros comandos via Bash em contexto sub-agent: permite (validate-verification, sha256sum, etc.)
fi

# ----------------------------------------------------------------------
# Path-based sandbox (Read|Grep|Glob) com canonicalização (item 1.5)
# ----------------------------------------------------------------------
# Sem TARGET (foi um Bash matcher e já passou nos checks acima): permite
[ -z "$TARGET" ] && exit 0

# Normaliza path: backslash → slash
TARGET_NORM="${TARGET//\\//}"

# Item 1.5: canonicaliza path (resolve symlinks + ..) ANTES de match.
# Fecha vetor §D.2: ln -s /etc/hosts verification-input/innocent.md OU
# verification-input/../../.env → ambos seriam aceitos pelo match textual antigo.
#
# Se o sandbox dir não existir ainda (caso comum em smoke-test sem fixture),
# pula a canonicalização e cai direto no path-text matching abaixo.
SANDBOX_ABS=""
if [ -d "$SANDBOX_DIR" ]; then
  SANDBOX_ABS="$(cd "$SANDBOX_DIR" && pwd -P)"
fi
if [ -n "$SANDBOX_ABS" ]; then
  TARGET_ABS="$(realpath -m "$TARGET_NORM" 2>/dev/null || echo "")"
  if [ -n "$TARGET_ABS" ]; then
    case "$TARGET_ABS" in
      "$SANDBOX_ABS"|"$SANDBOX_ABS"/*)
        # Dentro do sandbox canônico → segue para checks de cross-block abaixo
        :
        ;;
      *)
        # Fora do sandbox canônico — BLOCK incondicional.
        echo "[verifier-sandbox BLOCK] $AGENT path traversal/symlink escape detectado" >&2
        echo "  TARGET:    $TARGET" >&2
        echo "  Canônico:  $TARGET_ABS" >&2
        echo "  Sandbox:   $SANDBOX_ABS" >&2
        echo "  Origem: meta-audit 2026-04-10 item 1.5" >&2
        exit 1
        ;;
    esac
  fi
fi

# ----------------------------------------------------------------------
# VERIFIER (R3 + R11)
# ----------------------------------------------------------------------
if [ "$AGENT" = "verifier" ]; then
  case "$TARGET_NORM" in
    verification-input/*|*/verification-input/*|./verification-input/*|verification-input)
      exit 0
      ;;
    *review-input/*|*review.json)
      echo "[verifier-sandbox BLOCK] R11: verifier nao pode ver output do reviewer ('$TARGET')" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] R3: verifier so pode acessar verification-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

# ----------------------------------------------------------------------
# REVIEWER (R11)
# ----------------------------------------------------------------------
if [ "$AGENT" = "reviewer" ]; then
  case "$TARGET_NORM" in
    review-input/*|*/review-input/*|./review-input/*|review-input)
      exit 0
      ;;
    *verification-input/*|*verification.json)
      echo "[verifier-sandbox BLOCK] R11: reviewer nao pode ver output do verifier ('$TARGET')" >&2
      exit 1
      ;;
    *plan.md|*tasks.md)
      echo "[verifier-sandbox BLOCK] R11: reviewer nao pode ler plan/tasks (narrativa do implementer)" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] R11: reviewer so pode acessar review-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

# ----------------------------------------------------------------------
# SECURITY-REVIEWER (F2 master-audit 2026-04-12)
# ----------------------------------------------------------------------
if [ "$AGENT" = "security-reviewer" ]; then
  case "$TARGET_NORM" in
    security-review-input/*|*/security-review-input/*|./security-review-input/*|security-review-input)
      exit 0
      ;;
    *verification-input/*|*verification.json|*review-input/*|*review.json|*test-audit-input/*|*test-audit.json|*functional-review-input/*|*functional-review.json)
      echo "[verifier-sandbox BLOCK] security-reviewer nao pode ver output de outros gates ('$TARGET')" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] security-reviewer so pode acessar security-review-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

# ----------------------------------------------------------------------
# TEST-AUDITOR (F2 master-audit 2026-04-12)
# ----------------------------------------------------------------------
if [ "$AGENT" = "test-auditor" ]; then
  case "$TARGET_NORM" in
    test-audit-input/*|*/test-audit-input/*|./test-audit-input/*|test-audit-input)
      exit 0
      ;;
    *verification-input/*|*verification.json|*review-input/*|*review.json|*security-review-input/*|*security-review.json|*functional-review-input/*|*functional-review.json)
      echo "[verifier-sandbox BLOCK] test-auditor nao pode ver output de outros gates ('$TARGET')" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] test-auditor so pode acessar test-audit-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

# ----------------------------------------------------------------------
# FUNCTIONAL-REVIEWER (F2 master-audit 2026-04-12)
# ----------------------------------------------------------------------
if [ "$AGENT" = "functional-reviewer" ]; then
  case "$TARGET_NORM" in
    functional-review-input/*|*/functional-review-input/*|./functional-review-input/*|functional-review-input)
      exit 0
      ;;
    *verification-input/*|*verification.json|*review-input/*|*review.json|*security-review-input/*|*security-review.json|*test-audit-input/*|*test-audit.json)
      echo "[verifier-sandbox BLOCK] functional-reviewer nao pode ver output de outros gates ('$TARGET')" >&2
      exit 1
      ;;
    *)
      echo "[verifier-sandbox BLOCK] functional-reviewer so pode acessar functional-review-input/ (tentou: $TARGET)" >&2
      exit 1
      ;;
  esac
fi

exit 0
