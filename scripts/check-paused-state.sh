#!/usr/bin/env bash
# check-paused-state.sh
#
# Enforcement mecânico do estado pausado (pré-requisito 2 da decisão pós re-auditoria
# dual-LLM 2026-04-15 em docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md).
#
# Comportamento:
# - Lê project-state.json → busca .paused (bool) e .paused_reason (string).
# - Se paused != true → exit 0 (nada a fazer).
# - Se paused == true:
#     - Detecta paths tocados no push/PR (via GITHUB_BASE_REF ou git diff HEAD~1).
#     - Compara cada path contra whitelist da ADR-0014.
#     - Se todos estão na whitelist → exit 0 (bypass técnico autorizado).
#     - Se qualquer path fora da whitelist → exit 1 (BLOQUEIO).
#
# Exit codes:
# 0 = OK (não pausado OU pausado + todos os paths em whitelist)
# 1 = violação (pausado + paths fora da whitelist)
# 2 = erro operacional (project-state.json ausente, jq indisponível, etc)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

STATE_FILE="project-state.json"

# Whitelist ADR-0014 (paths que podem ser modificados mesmo em estado pausado)
WHITELIST_PATTERNS=(
  "^docs/audits/"
  "^docs/decisions/"
  "^docs/incidents/"
  "^docs/retrospectives/"
  "^docs/handoffs/"
  "^docs/adr/"
  "^\.github/workflows/pause-enforcement\.yml$"
  "^scripts/check-paused-state\.sh$"
  "^scripts/pm/"
  "^\.gitignore$"
  "^MEMORY\.md$"
  "^CLAUDE\.md$"
  "^docs/constitution\.md$"
  "^\.claude/agents/orchestrator\.md$"
  "^project-state\.json$"
)

log() { echo "[pause-enforcement] $*"; }
fail() { log "ERROR: $*" >&2; exit "${2:-1}"; }

# ---------- 1. Project state ----------

if [ ! -f "$STATE_FILE" ]; then
  log "project-state.json não encontrado — assumindo NÃO pausado"
  echo "violations=0" >> "${GITHUB_OUTPUT:-/dev/null}"
  exit 0
fi

if ! command -v jq >/dev/null 2>&1; then
  fail "jq não disponível no PATH" 2
fi

PAUSED=$(jq -r '.paused // false' "$STATE_FILE")
PAUSED_REASON=$(jq -r '.paused_reason // ""' "$STATE_FILE")

log "project-state.paused = $PAUSED"
[ -n "$PAUSED_REASON" ] && log "paused_reason = $PAUSED_REASON"

if [ "$PAUSED" != "true" ]; then
  log "Projeto NÃO está pausado — gate passa."
  echo "violations=0" >> "${GITHUB_OUTPUT:-/dev/null}"
  exit 0
fi

# ---------- 2. Coletar paths modificados ----------

if [ -n "${GITHUB_BASE_REF:-}" ]; then
  log "Detectado GITHUB_BASE_REF=$GITHUB_BASE_REF (PR context)"
  git fetch origin "$GITHUB_BASE_REF" --depth=50 2>/dev/null || true
  DIFF_BASE="origin/$GITHUB_BASE_REF"
elif [ -n "${GITHUB_EVENT_BEFORE:-}" ] && [ "$GITHUB_EVENT_BEFORE" != "0000000000000000000000000000000000000000" ]; then
  log "Detectado push context, base=$GITHUB_EVENT_BEFORE"
  DIFF_BASE="$GITHUB_EVENT_BEFORE"
else
  log "Contexto local — comparando com HEAD~1"
  DIFF_BASE="HEAD~1"
fi

CHANGED_FILES=$(git diff --name-only "$DIFF_BASE"..HEAD 2>/dev/null || git diff --name-only HEAD)

if [ -z "$CHANGED_FILES" ]; then
  log "Nenhum arquivo modificado — gate passa."
  echo "violations=0" >> "${GITHUB_OUTPUT:-/dev/null}"
  exit 0
fi

log "Arquivos modificados:"
echo "$CHANGED_FILES" | sed 's/^/  /'

# ---------- 3. Validar contra whitelist ----------

VIOLATIONS=()
while IFS= read -r file; do
  [ -z "$file" ] && continue
  matched=0
  for pattern in "${WHITELIST_PATTERNS[@]}"; do
    if echo "$file" | grep -qE "$pattern"; then
      matched=1
      break
    fi
  done
  if [ "$matched" -eq 0 ]; then
    VIOLATIONS+=("$file")
  fi
done <<< "$CHANGED_FILES"

VIOLATION_COUNT=${#VIOLATIONS[@]}
echo "violations=$VIOLATION_COUNT" >> "${GITHUB_OUTPUT:-/dev/null}"

if [ "$VIOLATION_COUNT" -eq 0 ]; then
  log "Projeto pausado, mas todos os paths estão na whitelist ADR-0014 — gate passa como bypass técnico autorizado."
  log "IMPORTANTE: commit deve ter incidente dedicado em docs/incidents/bypass-*.md (ADR-0014)."
  exit 0
fi

log "BLOQUEIO: projeto em estado pausado ($PAUSED_REASON) e os seguintes paths estão FORA da whitelist ADR-0014:"
for file in "${VIOLATIONS[@]}"; do
  log "  ❌ $file"
done
log ""
log "Ações possíveis:"
log "  1) Aguardar fim do estado pausado (ver docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md)."
log "  2) Se é trabalho emergencial P0/P1, abrir incidente em docs/incidents/ e atualizar este whitelist via ADR amendment."
log "  3) Se é bypass técnico autorizado, mover o push apenas para paths na whitelist ADR-0014."

exit 1
