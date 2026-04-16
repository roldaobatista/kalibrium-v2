#!/usr/bin/env bash
# PreToolUse hook — enforce R3 (isolamento de gate) + R11 (dual-verifier).
#
# Versao v3 (2026-04-16) — alinhada com protocolo operacional v1.2.2.
# Aceita tanto nomes de agent v2 (legado) quanto v3 (mapa canonico 00 §3.1).
#
# Cobertura:
#   - Read|Grep|Glob: path-based sandbox por gate
#   - Bash:           comandos de inspecao bloqueados fora do sandbox
#
# Determinacao do sandbox:
#   1. v3: detecta qual *-input/ existe em cwd (worktree isolado)
#   2. v2 (fallback): mapeamento fixo AGENT -> SANDBOX
#
# Sandboxes suportados:
#   - verification-input/         (gate verify)
#   - review-input/               (gate review / code-review)
#   - security-review-input/      (gate security-gate)
#   - test-audit-input/           (gate audit-tests)
#   - functional-review-input/    (gate functional-gate)
#   - data-review-input/          (gate data-gate) — novo v3
#   - observability-review-input/ (gate observability-gate) — novo v3
#   - integration-review-input/   (gate integration-gate) — novo v3
#
# Variaveis injetadas pelo Claude Code:
#   CLAUDE_AGENT_NAME       — nome do sub-agent atual ("" no agente principal)
#   CLAUDE_TOOL_ARG_FILE    — caminho do arquivo que o tool tentou acessar
#   CLAUDE_TOOL_ARG_COMMAND — comando completo (Bash matcher)

set -euo pipefail

AGENT="${CLAUDE_AGENT_NAME:-}"
TARGET="${CLAUDE_TOOL_ARG_FILE:-${1:-}}"
COMMAND="${CLAUDE_TOOL_ARG_COMMAND:-}"

# ----------------------------------------------------------------------
# Deteccao de contexto sub-agent
# ----------------------------------------------------------------------
detect_subagent_context() {
  [ -f .git ] && return 0
  local toplevel main_wt
  toplevel="$(git rev-parse --show-toplevel 2>/dev/null || echo '')"
  main_wt="$(git worktree list --porcelain 2>/dev/null | awk '/^worktree/ {print $2; exit}')"
  if [ -n "$toplevel" ] && [ -n "$main_wt" ] && [ "$toplevel" != "$main_wt" ]; then
    return 0
  fi
  return 1
}

# ----------------------------------------------------------------------
# CLAUDE_AGENT_NAME vazio
# ----------------------------------------------------------------------
if [ -z "$AGENT" ]; then
  if detect_subagent_context; then
    echo "[verifier-sandbox BLOCK] CLAUDE_AGENT_NAME vazio em contexto de sub-agent (worktree) — fail-closed (R3)" >&2
    exit 1
  fi
  exit 0
fi

# ----------------------------------------------------------------------
# Lista de agentes que operam gates isolados
# v2 (legado) + v3 (mapa canonico 00 §3.1)
# ----------------------------------------------------------------------
case "$AGENT" in
  # v2 legado — aceita para compat
  verifier|reviewer|security-reviewer|test-auditor|functional-reviewer) : ;;
  # v3 — agents de especialidade que podem executar gates
  qa-expert|architecture-expert|security-expert|product-expert|data-expert|observability-expert|integration-expert|governance|ux-designer|devops-expert) : ;;
  *)
    if detect_subagent_context; then
      echo "[verifier-sandbox BLOCK] sub-agent '$AGENT' nao autorizado a rodar em worktree" >&2
      exit 1
    fi
    exit 0
    ;;
esac

# ======================================================================
# Determinacao do SANDBOX_DIR
# ======================================================================

SANDBOX_DIR=""

# Estrategia v3: detectar por diretorio de input presente no worktree
for candidate in verification-input review-input security-review-input test-audit-input functional-review-input data-review-input observability-review-input integration-review-input; do
  if [ -d "$candidate" ]; then
    if [ -n "$SANDBOX_DIR" ]; then
      echo "[verifier-sandbox BLOCK] multiplos sandbox dirs presentes ('$SANDBOX_DIR' + '$candidate') — violacao de isolamento R3" >&2
      exit 1
    fi
    SANDBOX_DIR="$candidate"
  fi
done

# Fallback v2: se nao detectou por diretorio, usar mapeamento fixo por AGENT
if [ -z "$SANDBOX_DIR" ]; then
  case "$AGENT" in
    verifier)            SANDBOX_DIR="verification-input" ;;
    reviewer)            SANDBOX_DIR="review-input" ;;
    security-reviewer)   SANDBOX_DIR="security-review-input" ;;
    test-auditor)        SANDBOX_DIR="test-audit-input" ;;
    functional-reviewer) SANDBOX_DIR="functional-review-input" ;;
  esac
fi

# Se ainda nao ha sandbox e estamos em worktree sub-agent v3, fail-closed
if [ -z "$SANDBOX_DIR" ]; then
  if detect_subagent_context; then
    echo "[verifier-sandbox BLOCK] agent '$AGENT' em worktree sem sandbox dir reconhecido (esperado: *-input/)" >&2
    exit 1
  fi
  exit 0
fi

# ----------------------------------------------------------------------
# Bloqueio de comandos Bash de inspecao fora do sandbox
# ----------------------------------------------------------------------
if [ -n "$COMMAND" ]; then
  case "$COMMAND" in
    cat\ *|*\ cat\ *|less\ *|*\ less\ *|head\ *|*\ head\ *|tail\ *|*\ tail\ *|more\ *|od\ *|xxd\ *|hexdump\ *|strings\ *|dd\ if=*|*\ dd\ if=*)
      if ! echo "$COMMAND" | grep -qE "(^|[[:space:]/])${SANDBOX_DIR}(/|[[:space:]]|$)"; then
        echo "[verifier-sandbox BLOCK] $AGENT tentou comando de inspecao fora de $SANDBOX_DIR/" >&2
        echo "  Comando: $COMMAND" >&2
        exit 1
      fi
      ;;
  esac
fi

# ----------------------------------------------------------------------
# Path-based sandbox (Read|Grep|Glob) com canonicalizacao
# ----------------------------------------------------------------------
[ -z "$TARGET" ] && exit 0

TARGET_NORM="${TARGET//\\//}"

SANDBOX_ABS=""
if [ -d "$SANDBOX_DIR" ]; then
  SANDBOX_ABS="$(cd "$SANDBOX_DIR" && pwd -P)"
fi
if [ -n "$SANDBOX_ABS" ]; then
  TARGET_ABS="$(realpath -m "$TARGET_NORM" 2>/dev/null || echo "")"
  if [ -n "$TARGET_ABS" ]; then
    case "$TARGET_ABS" in
      "$SANDBOX_ABS"|"$SANDBOX_ABS"/*) : ;;
      *)
        echo "[verifier-sandbox BLOCK] $AGENT path traversal/symlink escape detectado" >&2
        echo "  TARGET:    $TARGET" >&2
        echo "  Canonico:  $TARGET_ABS" >&2
        echo "  Sandbox:   $SANDBOX_ABS" >&2
        exit 1
        ;;
    esac
  fi
fi

# ----------------------------------------------------------------------
# R3 + R11: cada sandbox so pode ler arquivos do proprio escopo
# e nao pode ler output de outros gates
# ----------------------------------------------------------------------

# Funcao auxiliar: bloqueia se TARGET casa com algum padrao de output de outro gate
block_if_other_gate_output() {
  local target="$1"
  local self_sandbox="$2"
  local patterns=(
    "verification-input" "verification.json"
    "review-input" "review.json"
    "security-review-input" "security-review.json"
    "test-audit-input" "test-audit.json"
    "functional-review-input" "functional-review.json"
    "data-review-input" "data-review.json"
    "observability-review-input" "observability-review.json"
    "integration-review-input" "integration-review.json"
    "master-audit.json"
  )
  for p in "${patterns[@]}"; do
    # skip self
    [ "$p" = "$self_sandbox" ] && continue
    case "$target" in
      *"$p"/*|*"$p")
        echo "[verifier-sandbox BLOCK] R3/R11: $AGENT (sandbox $self_sandbox) nao pode ler output de outro gate ('$target')" >&2
        exit 1
        ;;
    esac
  done
}

# Valida acesso ao sandbox proprio
case "$TARGET_NORM" in
  "$SANDBOX_DIR"/*|*/"$SANDBOX_DIR"/*|./"$SANDBOX_DIR"/*|"$SANDBOX_DIR")
    # Dentro do proprio sandbox: ainda assim bloqueia se o path aponta para arquivo de outro gate
    block_if_other_gate_output "$TARGET_NORM" "$SANDBOX_DIR"
    exit 0
    ;;
  *plan.md|*tasks.md)
    # review-input especificamente bloqueia plan/tasks (R11)
    if [ "$SANDBOX_DIR" = "review-input" ]; then
      echo "[verifier-sandbox BLOCK] R11: review nao pode ler plan/tasks (narrativa do implementer)" >&2
      exit 1
    fi
    # outros sandboxes: cai no catch-all abaixo
    echo "[verifier-sandbox BLOCK] R3: $AGENT (sandbox $SANDBOX_DIR) so pode acessar $SANDBOX_DIR/ (tentou: $TARGET)" >&2
    exit 1
    ;;
  *)
    block_if_other_gate_output "$TARGET_NORM" "$SANDBOX_DIR"
    echo "[verifier-sandbox BLOCK] R3: $AGENT (sandbox $SANDBOX_DIR) so pode acessar $SANDBOX_DIR/ (tentou: $TARGET)" >&2
    exit 1
    ;;
esac
