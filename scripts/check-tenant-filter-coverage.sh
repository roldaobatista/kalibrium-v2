#!/usr/bin/env bash
# ============================================================================
# check-tenant-filter-coverage.sh — Auditoria de cobertura do paths filter
# do job tenant-isolation em .github/workflows/ci.yml.
#
# Slice: 019 (AC-006)
# Plan:  specs/019/plan.md  D-03, D-05
# Spec:  specs/019/spec.md   AC-006
#
# Objetivo: detectar subdirs novos em `app/` que NAO estao cobertos pelo
# paths filter do job tenant-isolation. Primeira versao e WARNING-ONLY
# (exit code sempre 0 — AC-006.d). Pode ser promovido a gate duro em
# slice futuro via flag --strict (slot reservado).
#
# Saida (stdout):
#   uncovered: app/<dir>/            — subdir nao coberto pelo filter
#   [SUSPECT] uncovered: app/<dir>/  — idem, contem .php com "tenant"
#                                      (case-insensitive) — alta chance
#                                      de precisar entrar no filter.
#
# Parser YAML: shell puro (grep + sed). Sem dependencia externa (yq/python).
# Fragilidade ao formato do YAML e aceita — teste AC-005 atua como canario.
#
# Autor: builder (modo implementer), slice-019.
# ============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
REPO_ROOT="$(cd -- "${SCRIPT_DIR}/.." >/dev/null 2>&1 && pwd)"

CI_YML="${REPO_ROOT}/.github/workflows/ci.yml"
APP_DIR="${REPO_ROOT}/app"

# Flags (reservadas; hoje usamos apenas default + --verbose)
VERBOSE=0
# STRICT=0  # slot reservado para slice-020 (nao bloqueia nesta versao)
for arg in "$@"; do
  case "$arg" in
    --verbose) VERBOSE=1 ;;
    --strict)  : ;;  # reservado — nao altera comportamento hoje
    *) ;;
  esac
done

# --------------------------------------------------------------------------
# 1. Validacao de pre-condicoes
# --------------------------------------------------------------------------
if [ ! -f "$CI_YML" ]; then
  printf 'error: ci.yml not found at %s\n' "$CI_YML" >&2
  exit 0  # warning-only: nao quebra caller
fi

if [ ! -d "$APP_DIR" ]; then
  printf 'error: app/ not found at %s\n' "$APP_DIR" >&2
  exit 0
fi

# --------------------------------------------------------------------------
# 2. Parse do bloco `filters: run: paths:` dentro de `tenant-isolation:`
#
# Algoritmo:
#   a. Localiza linha `tenant-isolation:` (ancora).
#   b. A partir dela, acha primeira linha `paths:` indentada.
#   c. Le linhas subsequentes comecando com `- '...'` ate sair do bloco
#      (linha com indentacao menor ou sem `- '`).
# --------------------------------------------------------------------------
EXTRACT="$(awk '
  /^[[:space:]]*tenant-isolation:/ { in_job=1; next }
  in_job && /^[[:space:]]*paths:[[:space:]]*$/ { in_paths=1; next }
  in_paths {
    # Linha de path: comeca com - seguido de aspas
    if ($0 ~ /^[[:space:]]*-[[:space:]]*'\''[^'\'']+'\''[[:space:]]*$/) {
      gsub(/^[[:space:]]*-[[:space:]]*'\''/, "", $0)
      gsub(/'\''[[:space:]]*$/, "", $0)
      print $0
    } else if ($0 ~ /^[[:space:]]*$/) {
      # linha em branco — tolera (ainda no bloco)
      next
    } else {
      # qualquer outra coisa — saiu do bloco
      in_paths=0
      in_job=0
    }
  }
' "$CI_YML")"

# --------------------------------------------------------------------------
# 3. Normaliza lista de paths para "top-level dir under app/"
#    Ex: "app/Models/**" -> "Models"
#        "app/Services/**" -> "Services"
#        "database/migrations/**" -> (descartado, nao comeca com app/)
# --------------------------------------------------------------------------
COVERED_DIRS="$(printf '%s\n' "$EXTRACT" \
  | grep -E '^app/[^/]+/' \
  | sed -E 's|^app/([^/]+)/.*$|\1|' \
  | sort -u)"

# --------------------------------------------------------------------------
# 4. Lista subdirs reais de app/
# --------------------------------------------------------------------------
# Usa find com maxdepth para portabilidade Git Bash/Linux.
APP_SUBDIRS="$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2>/dev/null \
  | sort -u)"

if [ -z "$APP_SUBDIRS" ]; then
  # Fallback para shells sem -printf
  APP_SUBDIRS="$(cd "$APP_DIR" && for d in */; do [ -d "$d" ] && printf '%s\n' "${d%/}"; done | sort -u)"
fi

# --------------------------------------------------------------------------
# 5. Emite uncovered: / [SUSPECT] uncovered:
# --------------------------------------------------------------------------
while IFS= read -r dir; do
  [ -z "$dir" ] && continue

  if printf '%s\n' "$COVERED_DIRS" | grep -qx "$dir"; then
    if [ "$VERBOSE" -eq 1 ]; then
      printf 'covered: app/%s/\n' "$dir"
    fi
    continue
  fi

  # Uncovered — checa heuristica [SUSPECT]: .php com "tenant" (case-insensitive).
  SUSPECT_HIT=0
  if [ -d "${APP_DIR}/${dir}" ]; then
    # grep -r case-insensitive; redireciona stderr para evitar poluicao;
    # -l para listar arquivos (so precisamos de 1 hit).
    if grep -r -l -i 'tenant' "${APP_DIR}/${dir}" --include='*.php' >/dev/null 2>&1; then
      SUSPECT_HIT=1
    fi
  fi

  if [ "$SUSPECT_HIT" -eq 1 ]; then
    printf '[SUSPECT] uncovered: app/%s/\n' "$dir"
  else
    printf 'uncovered: app/%s/\n' "$dir"
  fi
done <<< "$APP_SUBDIRS"

# Warning-only (AC-006.d): exit 0 sempre.
exit 0
