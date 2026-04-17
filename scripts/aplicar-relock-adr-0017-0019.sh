#!/usr/bin/env bash
#
# scripts/aplicar-relock-adr-0017-0019.sh
#
# Aplica os patches dos ADRs 0017 e 0019 nos 3 hooks selados e
# regera os selos via relock-harness.sh.
#
# CHAMADO por APLICAR-RELOCK-ADR-0017-0019.bat (raiz do projeto).
# Nao deve ser executado dentro do Claude Code (seria bloqueado pelos
# hooks sealed-files-bash-lock e settings-lock).
#
# Uso (fora do Claude Code):
#   export KALIB_RELOCK_AUTHORIZED=1
#   bash scripts/aplicar-relock-adr-0017-0019.sh
#
# O relock-harness.sh vai pedir digitar "RELOCK" uma vez.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ts_filesafe() { date -u +%Y-%m-%dT%H-%M-%SZ; }

log() { printf '\e[36m[relock]\e[0m %s\n' "$*"; }
ok()  { printf '\e[32m[relock] ✓ %s\e[0m\n' "$*"; }
err() { printf '\e[31m[relock] ✗ %s\e[0m\n' "$*" >&2; }

# ------------------------------------------------------------------
# Pre-checks
# ------------------------------------------------------------------
log "Verificando pre-requisitos..."

if [ ! -f "CLAUDE.md" ]; then
  err "CLAUDE.md nao encontrado. Rode este script na raiz do projeto."
  exit 1
fi

if [ ! -f "scripts/relock-harness.sh" ]; then
  err "scripts/relock-harness.sh nao encontrado."
  exit 1
fi

for patch in scripts/patches/adr-0017-session-start.patch.sh \
             scripts/patches/adr-0019-pre-commit-gate.patch.sh \
             scripts/patches/adr-0019-merge-slice.patch.sh; do
  if [ ! -f "$patch" ]; then
    err "Patch nao encontrado: $patch"
    exit 1
  fi
done

for hook in scripts/hooks/session-start.sh \
            scripts/hooks/pre-commit-gate.sh \
            scripts/merge-slice.sh; do
  if [ ! -f "$hook" ]; then
    err "Hook/script alvo nao encontrado: $hook"
    exit 1
  fi
done

ok "pre-checks ok"

# ------------------------------------------------------------------
# Backup dos 3 arquivos alvo
# ------------------------------------------------------------------
TS=$(ts_filesafe)
log "Criando backups (.bak-${TS})..."

cp scripts/hooks/session-start.sh    "scripts/hooks/session-start.sh.bak-${TS}"
cp scripts/hooks/pre-commit-gate.sh  "scripts/hooks/pre-commit-gate.sh.bak-${TS}"
cp scripts/merge-slice.sh            "scripts/merge-slice.sh.bak-${TS}"

ok "backups criados"

# ------------------------------------------------------------------
# Aplicar patches (idempotente — checa marker antes de aplicar)
# ------------------------------------------------------------------
apply_patch() {
  local target="$1"
  local patch_file="$2"
  local marker="$3"

  if grep -q "$marker" "$target" 2>/dev/null; then
    log "$target: patch ja aplicado (skip)"
    return 0
  fi

  printf '\n' >> "$target"
  cat "$patch_file" >> "$target"
  ok "$target: patch aplicado ($(wc -l < "$patch_file" | tr -d ' ') linhas)"
}

log "Aplicando patches nos 3 alvos..."

apply_patch \
  "scripts/hooks/session-start.sh" \
  "scripts/patches/adr-0017-session-start.patch.sh" \
  "ADR-0017 Mudanca 3"

apply_patch \
  "scripts/hooks/pre-commit-gate.sh" \
  "scripts/patches/adr-0019-pre-commit-gate.patch.sh" \
  "ADR-0019 Mudanca 1"

apply_patch \
  "scripts/merge-slice.sh" \
  "scripts/patches/adr-0019-merge-slice.patch.sh" \
  "ADR-0019 Mudanca 2"

# ------------------------------------------------------------------
# Chamar relock-harness.sh (regera selos SHA-256)
# ------------------------------------------------------------------
printf '\n'
log "Chamando scripts/relock-harness.sh para regerar selos..."
log "Digite 'RELOCK' quando solicitado (mudancas nos hooks selados)"
printf '\n'

export KALIB_RELOCK_AUTHORIZED=1

if bash scripts/relock-harness.sh; then
  printf '\n'
  ok "Relock concluido com sucesso"
else
  rc=$?
  printf '\n'
  err "relock-harness.sh retornou $rc"
  err "Backups preservados: *.bak-${TS}"
  err "Para reverter: mv scripts/hooks/session-start.sh.bak-${TS} scripts/hooks/session-start.sh (etc)"
  exit $rc
fi

# ------------------------------------------------------------------
# Registrar incidente de aplicacao (auditavel)
# ------------------------------------------------------------------
INCIDENT="docs/incidents/aplicacao-adr-0017-0019-${TS}.md"
mkdir -p docs/incidents
cat > "$INCIDENT" <<EOF
# Aplicacao ADR-0017 + ADR-0019 — relock autorizado pelo PM

**Data:** $(date -u +%Y-%m-%dT%H:%M:%SZ)
**Operador:** $(whoami 2>/dev/null || echo "unknown")@$(hostname 2>/dev/null || echo "unknown")
**Autorizacao:** KALIB_RELOCK_AUTHORIZED=1 + confirmacao RELOCK
**Metodo:** APLICAR-RELOCK-ADR-0017-0019.bat (wrapper) + scripts/aplicar-relock-adr-0017-0019.sh

## Arquivos modificados

- scripts/hooks/session-start.sh (patch ADR-0017 Mudanca 3)
- scripts/hooks/pre-commit-gate.sh (patch ADR-0019 Mudanca 1)
- scripts/merge-slice.sh (patch ADR-0019 Mudanca 2)

## Backups

- scripts/hooks/session-start.sh.bak-${TS}
- scripts/hooks/pre-commit-gate.sh.bak-${TS}
- scripts/merge-slice.sh.bak-${TS}

## Objetivo

Ativar enforcement mecanico das 3 validacoes procedurais ja implementadas
nos agents e skills (commit e2abdfc, 2026-04-16).

- ADR-0017 M3 ativa: reconcile project-state no SessionStart
- ADR-0019 M1 ativa: bloqueio de commit no harness sem review+PM-approval
- ADR-0019 M2 ativa: validacao de referenced_artifacts no merge-slice

## Referencias

- docs/adr/0017-auditoria-early-stage.md
- docs/adr/0019-robustez-loop-gates-harness-learner.md
- docs/handoffs/handoff-2026-04-16-adr-017-018-019.md
- CLAUDE.md §9 (procedimento de relock)
EOF

ok "Incidente registrado em $INCIDENT"

printf '\n'
printf '=====================================================\n'
printf ' RELOCK ADR-0017 + ADR-0019 COMPLETO\n'
printf '=====================================================\n'
printf '\n'
printf 'Proximos passos sugeridos:\n'
printf '  1. git add scripts/hooks/ scripts/merge-slice.sh scripts/patches/ \\\n'
printf '        .claude/settings.json.sha256 scripts/hooks/MANIFEST.sha256 \\\n'
printf '        docs/incidents/aplicacao-adr-0017-0019-*.md\n'
printf '  2. git commit -m "chore(harness): relock ADR-0017 M3 + ADR-0019 M1+M2"\n'
printf '  3. Voltar ao Claude Code — SessionStart vai validar que selos batem.\n'
printf '\n'
