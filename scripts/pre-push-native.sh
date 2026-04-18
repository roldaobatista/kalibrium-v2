#!/usr/bin/env bash
# ============================================================================
# pre-push-native.sh — Hook git nativo (contrato git pre-push).
#
# Slice: 019 (AC-002, AC-004)
# Plan:  specs/019/plan.md  D-01, D-02
# Spec:  specs/019/spec.md   AC-002, AC-004
#
# Contrato git nativo:
#   - Invocado por .git/hooks/pre-push (criado por scripts/install-git-hooks.sh).
#   - stdin: linhas "<local_ref> <local_sha> <remote_ref> <remote_sha>"
#   - args:  $1=remote_name, $2=remote_url
#   - exit 0 = permite push; exit != 0 = bloqueia push.
#
# Regras aplicadas (equivalencia funcional com scripts/hooks/pre-push-gate.sh
# que e PreToolUse do Claude Code):
#   1. Bloqueia push para refs/heads/main (R2).
#   2. Bloqueia push para refs/heads/master (R2).
#   3. Bloqueia force-push em main/master (detectado via merge-base).
#
# NAO substitui pre-push-gate.sh (PreToolUse). Complementa para cobrir
# pushers FORA do Claude Code. Defesa em profundidade (R3/R11).
#
# Autor: builder (modo implementer), slice-019.
# ============================================================================

set -euo pipefail

REMOTE_NAME="${1:-}"
# REMOTE_URL="${2:-}"  # disponivel para futuras regras; nao usado hoje

say() { printf '[pre-push-native] %s\n' "$*" >&2; }
die() { say "BLOCK: $*"; exit 1; }

# stdin vem via heredoc do git. Se estiver vazio (sem refs para push),
# o git ja teria recusado antes de invocar o hook — mas tratamos graciosamente.
HAS_INPUT=0

# Le stdin linha a linha: cada linha e um update proposto.
while IFS=' ' read -r local_ref local_sha remote_ref remote_sha; do
  HAS_INPUT=1

  # Linhas vazias ocorrem em alguns casos-borda; ignora.
  if [ -z "${remote_ref:-}" ]; then
    continue
  fi

  # ----------------------------------------------------------------------
  # Regra 1/2: push direto em refs/heads/main ou refs/heads/master
  # ----------------------------------------------------------------------
  case "$remote_ref" in
    refs/heads/main|refs/heads/master)
      die "push direto para $remote_ref e proibido. Abra PR via branch feature."
      ;;
  esac

  # ----------------------------------------------------------------------
  # Regra 3 (defensiva): force-push detectado por nao-ancestralidade.
  # Se local_sha nao descende de remote_sha (e remote_sha nao e zero-sha),
  # trata-se de force-push.
  #
  # NOTA: regras 1/2 ja bloqueiam main/master — esta checagem adiciona
  # cobertura caso surjam outras refs protegidas no futuro. Hoje so
  # registra em stderr quando detectado em qualquer ref (nao bloqueia
  # fora de main/master, alinhando com pre-push-gate.sh).
  # ----------------------------------------------------------------------
  ZERO_SHA="0000000000000000000000000000000000000000"
  if [ "${remote_sha:-$ZERO_SHA}" != "$ZERO_SHA" ] && [ -n "${local_sha:-}" ] && [ "$local_sha" != "$ZERO_SHA" ]; then
    if ! git merge-base --is-ancestor "$remote_sha" "$local_sha" 2>/dev/null; then
      case "$remote_ref" in
        refs/heads/main|refs/heads/master)
          die "force-push detectado para $remote_ref (remote nao e ancestral de local)."
          ;;
      esac
    fi
  fi
done

# Sem input (ex: git push sem refs) — permite. Git ja teria decidido.
if [ "$HAS_INPUT" -eq 0 ]; then
  exit 0
fi

exit 0
