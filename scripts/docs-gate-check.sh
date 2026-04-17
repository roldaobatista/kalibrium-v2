#!/usr/bin/env bash
#
# scripts/docs-gate-check.sh
#
# Gate de documentacao — valida presenca dos documentos globais obrigatorios
# ANTES de iniciar story com UI. Fecha gap #7 da auditoria de fluxo 2026-04-16.
#
# ADR-0017 Mudanca 2.
#
# Uso:
#   bash scripts/docs-gate-check.sh --story ENN-SNN [--has-ui]
#
# Flags:
#   --story ENN-SNN   (obrigatorio) identificador da story sendo iniciada
#   --has-ui          (opcional) indica que a story implementa UI; se presente,
#                     valida docs globais de UI + docs especificos do epico
#                     (se o arquivo de contrato da story estiver disponivel,
#                     a flag e inferida automaticamente via frontmatter
#                     `ui: true` no Story Contract).
#
# Saida:
#   - exit 0 + STDOUT "OK" quando todos os docs existem e tem tamanho minimo
#   - exit 1 + JSON estruturado (docs/audits/docs-gate-<story>-<ts>.json) quando falha
#
# Nao depende de nenhuma ferramenta externa alem de bash + coreutils.
# Nao toca em arquivos selados. E chamado por skills (/start-story) e
# eventualmente por hook pre-start-story (quando PM fizer relock).

set -euo pipefail

# ------------------------------------------------------------------
# Parse de flags
# ------------------------------------------------------------------
STORY_ID=""
HAS_UI=0

while [ $# -gt 0 ]; do
  case "$1" in
    --story)
      STORY_ID="${2:-}"
      shift 2
      ;;
    --has-ui)
      HAS_UI=1
      shift
      ;;
    *)
      echo "ERRO: flag desconhecida: $1" >&2
      echo "Uso: bash scripts/docs-gate-check.sh --story ENN-SNN [--has-ui]" >&2
      exit 2
      ;;
  esac
done

if [ -z "$STORY_ID" ]; then
  echo "ERRO: --story e obrigatorio" >&2
  echo "Uso: bash scripts/docs-gate-check.sh --story ENN-SNN [--has-ui]" >&2
  exit 2
fi

# ------------------------------------------------------------------
# Localizar Story Contract e inferir HAS_UI via frontmatter
# ------------------------------------------------------------------
EPIC_NUM="${STORY_ID%-*}"
STORY_FILE="epics/${EPIC_NUM}/stories/${STORY_ID}.md"

if [ -f "$STORY_FILE" ] && [ "$HAS_UI" = "0" ]; then
  if grep -q "^ui:[[:space:]]*true" "$STORY_FILE" 2>/dev/null; then
    HAS_UI=1
  fi
fi

# ------------------------------------------------------------------
# Lista de docs globais obrigatorios
# ------------------------------------------------------------------
# Sempre obrigatorios (qualquer story):
REQUIRED_ALWAYS=(
  "docs/constitution.md"
  "docs/TECHNICAL-DECISIONS.md"
  "docs/documentation-requirements.md"
  "docs/product/PRD.md"
  "docs/product/personas.md"
  "docs/product/journeys.md"
  "docs/product/mvp-scope.md"
  "docs/product/nfr.md"
)

# Obrigatorios para stories com UI:
REQUIRED_UI=(
  "docs/product/sitemap.md"
  "docs/product/ui-flows.md"
  "docs/product/persona-scenarios.md"
)

# ------------------------------------------------------------------
# Validar existencia e tamanho minimo (100 bytes como sentinel)
# ------------------------------------------------------------------
MISSING=()
EMPTY=()

check_file() {
  local f="$1"
  if [ ! -f "$f" ]; then
    MISSING+=("$f")
    return
  fi
  local size
  size=$(wc -c <"$f" | tr -d '[:space:]')
  if [ "$size" -lt 100 ]; then
    EMPTY+=("$f (size=${size}B)")
  fi
}

for f in "${REQUIRED_ALWAYS[@]}"; do
  check_file "$f"
done

if [ "$HAS_UI" = "1" ]; then
  for f in "${REQUIRED_UI[@]}"; do
    check_file "$f"
  done
fi

# ------------------------------------------------------------------
# Emitir resultado
# ------------------------------------------------------------------
if [ ${#MISSING[@]} -eq 0 ] && [ ${#EMPTY[@]} -eq 0 ]; then
  echo "OK docs-gate-check passou para ${STORY_ID} (has_ui=${HAS_UI})"
  exit 0
fi

# Falhou — escrever relatorio JSON
TIMESTAMP=$(date -u +%Y-%m-%dT%H-%M-%SZ)
OUT_DIR="docs/audits"
OUT_FILE="${OUT_DIR}/docs-gate-${STORY_ID}-${TIMESTAMP}.json"

mkdir -p "$OUT_DIR"

# Montar arrays JSON
missing_json="["
for i in "${!MISSING[@]}"; do
  [ "$i" -gt 0 ] && missing_json+=","
  missing_json+="\"${MISSING[$i]}\""
done
missing_json+="]"

empty_json="["
for i in "${!EMPTY[@]}"; do
  [ "$i" -gt 0 ] && empty_json+=","
  empty_json+="\"${EMPTY[$i]}\""
done
empty_json+="]"

cat >"$OUT_FILE" <<EOF
{
  "\$schema": "docs-gate-check-v1",
  "story": "${STORY_ID}",
  "has_ui": ${HAS_UI},
  "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "verdict": "rejected",
  "missing_files": ${missing_json},
  "empty_or_placeholder_files": ${empty_json},
  "note": "Gate documental ADR-0017 Mudanca 2. Story nao pode iniciar sem os documentos globais obrigatorios."
}
EOF

echo "FALHA docs-gate-check rejeitou ${STORY_ID}" >&2
echo "  missing : ${#MISSING[@]} arquivo(s)" >&2
echo "  empty   : ${#EMPTY[@]} arquivo(s)" >&2
echo "  detalhe : ${OUT_FILE}" >&2
echo "" >&2
if [ ${#MISSING[@]} -gt 0 ]; then
  echo "  Arquivos faltando:" >&2
  for f in "${MISSING[@]}"; do
    echo "    - ${f}" >&2
  done
fi
if [ ${#EMPTY[@]} -gt 0 ]; then
  echo "  Arquivos vazios/placeholder:" >&2
  for f in "${EMPTY[@]}"; do
    echo "    - ${f}" >&2
  done
fi

exit 1
