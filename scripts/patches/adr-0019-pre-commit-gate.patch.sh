
# =====================================================================
# ADR-0019 Mudanca 1 — revisor externo obrigatorio do harness-learner
# =====================================================================
# Bloqueia commits em arquivos selados/do harness quando mensagem indica
# mudanca do harness-learner (chore(harness), ADR-0019, harness-review)
# E nao existe par de arquivos de aprovacao:
#   - docs/governance/harness-learner-review-ENN.json (aprovado pelo
#     architecture-expert em modo harness-review, instancia isolada)
#   - docs/governance/harness-learner-pm-approval-ENN.md (confirmacao
#     explicita do PM via skill /explain-harness-change)
#
# Motivacao: fecha gap #1 da auditoria de fluxo 2026-04-16.
# R16 (harness-learner com auto-aplicacao limitada) nao e mais
# auto-aprovavel; exige R11 (dual-verifier) aplicado ao proprio harness.

# Detecta se o commit atual toca arquivos sensiveis do harness.
# Arquivos sensiveis: .claude/agents/, .claude/skills/, scripts/hooks/,
# docs/protocol/, docs/constitution.md, CLAUDE.md (meta-documentos).
STAGED_HARNESS=$(git diff --cached --name-only | grep -E '^(\.claude/(agents|skills)/|scripts/hooks/|docs/protocol/|docs/constitution\.md|CLAUDE\.md)' || true)

if [ -n "$STAGED_HARNESS" ]; then
  # Este commit toca o harness. Verificar se e mudanca do harness-learner.
  COMMIT_MSG_FILE=".git/COMMIT_EDITMSG"
  IS_HARNESS_LEARNER_COMMIT=0
  if [ -f "$COMMIT_MSG_FILE" ]; then
    if grep -qiE '(chore\(harness\)|harness-learner|ADR-0019 M1|harness-review)' "$COMMIT_MSG_FILE" 2>/dev/null; then
      IS_HARNESS_LEARNER_COMMIT=1
    fi
  fi

  if [ "$IS_HARNESS_LEARNER_COMMIT" = "1" ]; then
    # Exige par revisao tecnica + aprovacao PM em docs/governance/
    HAS_REVIEW=$(find docs/governance -name "harness-learner-review-*.json" 2>/dev/null | head -1)
    HAS_PM_APPROVAL=$(find docs/governance -name "harness-learner-pm-approval-*.md" 2>/dev/null | head -1)

    if [ -z "$HAS_REVIEW" ] || [ -z "$HAS_PM_APPROVAL" ]; then
      printf '\n❌ [ADR-0019] Commit do harness BLOQUEADO\n' >&2
      printf '    Mudancas no harness exigem R11 (dual-verifier):\n' >&2
      printf '    1. Revisao tecnica: docs/governance/harness-learner-review-ENN.json (architecture-expert modo harness-review)\n' >&2
      printf '    2. Aprovacao PM:    docs/governance/harness-learner-pm-approval-ENN.md (via /explain-harness-change)\n' >&2
      printf '\n' >&2
      printf '    Arquivos ausentes:\n' >&2
      [ -z "$HAS_REVIEW" ] && printf '      - harness-learner-review-*.json\n' >&2
      [ -z "$HAS_PM_APPROVAL" ] && printf '      - harness-learner-pm-approval-*.md\n' >&2
      printf '\n' >&2
      printf '    Bypass: KALIB_SKIP_HARNESS_REVIEW="<motivo>" (registra incidente)\n\n' >&2

      if [ -z "${KALIB_SKIP_HARNESS_REVIEW:-}" ]; then
        exit 1
      else
        mkdir -p docs/incidents
        ts=$(date -u +%Y-%m-%dT%H-%M-%SZ)
        printf '# Bypass ADR-0019 M1 — harness-review\n\n**Data:** %s\n**Motivo:** %s\n' \
          "$ts" "$KALIB_SKIP_HARNESS_REVIEW" > "docs/incidents/bypass-harness-review-${ts}.md"
        printf '    [AVISO] Bypass autorizado. Incidente registrado em docs/incidents/.\n\n' >&2
      fi
    fi
  fi
fi
