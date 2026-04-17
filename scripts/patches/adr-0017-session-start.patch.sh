
# =====================================================================
# ADR-0017 Mudanca 3 — reconcile project-state vs git (2026-04-16)
# =====================================================================
# Roda scripts/reconcile-project-state.sh em modo --check na abertura da
# sessao. Detecta drift entre project-state.json e a realidade do git
# (PRs merged, slices que deveriam existir, epics_status desatualizado).
#
# READ-ONLY: nunca corrige automaticamente. Se drift bloqueante for
# detectado, relatorio completo em docs/audits/project-state-reconcile-*.json
# e orienta o PM via mensagem R12.
#
# Motivacao: fecha gap #9 da auditoria de fluxo 2026-04-16.

if [ -x "$REPO_ROOT/scripts/reconcile-project-state.sh" ]; then
  if ! bash "$REPO_ROOT/scripts/reconcile-project-state.sh" --check >/dev/null 2>&1; then
    printf '\n⚠️  [ADR-0017] Estado do projeto pode estar desalinhado com git.\n' >&2
    printf '    Rode: bash scripts/reconcile-project-state.sh --verbose\n' >&2
    printf '    Relatorio em: docs/audits/project-state-reconcile-*.json\n\n' >&2
    # NAO aborta sessao — apenas alerta. Drift bloqueante so vira erro
    # quando PM deliberadamente rodar com --strict (reservado para CI).
  fi
fi
