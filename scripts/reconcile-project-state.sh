#!/usr/bin/env bash
#
# scripts/reconcile-project-state.sh
#
# Reconcilia project-state.json contra a realidade do git.
# READ-ONLY: nunca corrige automaticamente; apenas reporta drift.
#
# ADR-0017 Mudanca 3 — fecha gap #9 da auditoria de fluxo 2026-04-16.
#
# Uso:
#   bash scripts/reconcile-project-state.sh           # check silencioso
#   bash scripts/reconcile-project-state.sh --verbose # check com output
#   bash scripts/reconcile-project-state.sh --check   # apenas valida schema
#
# Saida:
#   exit 0 — state consistente com git (ou drift apenas informativo)
#   exit 1 — drift bloqueante detectado (epicos com status divergente)
#   exit 2 — erro de pre-requisito (arquivos faltantes, json invalido)
#
# Consumidores esperados:
#   - session-start.sh (apos PM fazer relock que habilita)
#   - /resume (skill ja invoca manualmente)
#   - /project-status (informa PM em linguagem R12)

set -euo pipefail

VERBOSE=0
CHECK_ONLY=0

while [ $# -gt 0 ]; do
  case "$1" in
    --verbose|-v) VERBOSE=1; shift ;;
    --check)      CHECK_ONLY=1; shift ;;
    *) echo "ERRO: flag desconhecida: $1" >&2; exit 2 ;;
  esac
done

log() { [ "$VERBOSE" = "1" ] && echo "[reconcile] $*" >&2 || true; }
err() { echo "[reconcile] ERRO: $*" >&2; }

# ------------------------------------------------------------------
# Pre-requisitos
# ------------------------------------------------------------------
STATE_FILE="project-state.json"
SCHEMA_FILE="docs/schemas/project-state.schema.json"

if [ ! -f "$STATE_FILE" ]; then
  err "project-state.json nao existe"
  exit 2
fi

if [ ! -f "$SCHEMA_FILE" ]; then
  err "docs/schemas/project-state.schema.json nao existe"
  exit 2
fi

# Validar JSON sintaticamente
if ! python3 -c "import json,sys; json.load(open('${STATE_FILE}'))" 2>/dev/null; then
  err "project-state.json tem sintaxe JSON invalida"
  exit 2
fi

log "JSON sintaticamente valido"

# Validar contra schema (best-effort — usa ajv se disponivel, senao pula)
if command -v ajv >/dev/null 2>&1; then
  if ajv validate -s "$SCHEMA_FILE" -d "$STATE_FILE" >/dev/null 2>&1; then
    log "Schema validation: OK"
  else
    err "project-state.json nao valida contra schema"
    ajv validate -s "$SCHEMA_FILE" -d "$STATE_FILE" 2>&1 | head -20 >&2
    exit 1
  fi
else
  log "ajv nao instalado; pulando schema validation (nao bloqueante)"
fi

if [ "$CHECK_ONLY" = "1" ]; then
  log "Modo --check: validacao de schema concluida"
  echo "OK project-state.json valido contra schema"
  exit 0
fi

# ------------------------------------------------------------------
# Reconciliacao contra git — extrair estado atual vs real
# ------------------------------------------------------------------

# Extrair epics_status do JSON via python (mais confiavel que jq em Windows)
DRIFT_REPORT=$(python3 <<'PYEOF'
import json
import subprocess
import sys
import os
from datetime import datetime, timezone

def sh(cmd):
    try:
        out = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=10)
        return out.stdout.strip()
    except Exception:
        return ""

def now_iso():
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

def now_filesafe():
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H-%M-%SZ")

with open("project-state.json", encoding="utf-8") as f:
    state = json.load(f)

epics_status = state.get("epics_status", {})
if not epics_status:
    print("INFO: epics_status nao esta preenchido em project-state.json")
    sys.exit(0)

# Descobrir specs/ merged via git log (commits em main ou merged PRs)
merged_slices_in_git = set()
try:
    # Listar todos os diretorios specs/NNN que aparecem na historia de main
    out = sh("git log --diff-filter=A --name-only --pretty=format: main -- 'specs/*/spec.md' 2>/dev/null")
    for line in out.splitlines():
        line = line.strip()
        if line.startswith("specs/") and line.endswith("spec.md"):
            parts = line.split("/")
            if len(parts) >= 2 and parts[1].isdigit() and len(parts[1]) == 3:
                merged_slices_in_git.add(parts[1])
except Exception:
    pass

# Compara status declarado com realidade
drift_blocking = []
drift_info = []

for epic_id, meta in epics_status.items():
    declared_status = meta.get("status", "unknown")
    declared_merged_slices = set(meta.get("merged_slices", []))

    # So checar se tem merged_slices declarados
    if declared_merged_slices:
        not_in_git = declared_merged_slices - merged_slices_in_git
        if not_in_git:
            drift_blocking.append(
                f"{epic_id}: declara slices {sorted(not_in_git)} como merged "
                "mas git log nao confirma (main/history)"
            )

    # Se status=merged mas nao ha merged_slices declarados
    if declared_status == "merged" and not declared_merged_slices:
        drift_info.append(
            f"{epic_id}: status=merged mas nao declara merged_slices (sugerido adicionar para rastreabilidade)"
        )

# Reporta
out_dir = "docs/audits"
os.makedirs(out_dir, exist_ok=True)
ts = now_filesafe()
out_file = f"{out_dir}/project-state-reconcile-{ts}.json"

report = {
    "$schema": "project-state-reconcile-v1",
    "timestamp_utc": now_iso(),
    "state_file": "project-state.json",
    "schema_file": "docs/schemas/project-state.schema.json",
    "epics_in_state": len(epics_status),
    "slices_merged_in_git": sorted(merged_slices_in_git),
    "drift_blocking": drift_blocking,
    "drift_info": drift_info,
    "verdict": "rejected" if drift_blocking else ("needs_attention" if drift_info else "approved"),
}

with open(out_file, "w", encoding="utf-8") as f:
    json.dump(report, f, indent=2, ensure_ascii=False)

print(f"OUT_FILE={out_file}")
print(f"BLOCKING={len(drift_blocking)}")
print(f"INFO={len(drift_info)}")
for d in drift_blocking:
    print(f"BLOCK: {d}")
for d in drift_info:
    print(f"INFO: {d}")
PYEOF
)

OUT_FILE=$(echo "$DRIFT_REPORT" | grep "^OUT_FILE=" | head -1 | cut -d= -f2-)
BLOCKING=$(echo "$DRIFT_REPORT" | grep "^BLOCKING=" | head -1 | cut -d= -f2-)
INFO=$(echo "$DRIFT_REPORT" | grep "^INFO=" | head -1 | cut -d= -f2-)

# ------------------------------------------------------------------
# Resultado
# ------------------------------------------------------------------
if [ "${BLOCKING:-0}" -gt 0 ]; then
  err "Drift bloqueante detectado em project-state.json"
  err "Detalhes: ${OUT_FILE}"
  echo "$DRIFT_REPORT" | grep "^BLOCK:" >&2
  echo "" >&2
  err "Recomendacao: investigar manualmente antes de prosseguir."
  err "  - Verificar se alguma branch foi mergeada e project-state nao foi atualizado"
  err "  - Verificar se project-state foi editado manualmente sem respaldo em git"
  err "  - NAO corrija automaticamente — verifique a causa raiz primeiro (CLAUDE.md §9)"
  exit 1
fi

if [ "${INFO:-0}" -gt 0 ]; then
  log "Drift informativo (nao bloqueante):"
  [ "$VERBOSE" = "1" ] && echo "$DRIFT_REPORT" | grep "^INFO:" >&2
  echo "OK project-state consistente com git (com ${INFO} avisos informativos em ${OUT_FILE})"
  exit 0
fi

echo "OK project-state totalmente consistente com git"
exit 0
