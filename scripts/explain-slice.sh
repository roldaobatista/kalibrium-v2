#!/usr/bin/env bash
# explain-slice.sh — gera relatório do slice em linguagem de produto (R12).
# Stub do Dia 0 da Fase 2: template + placeholders que o agente principal
# preenche ao ler verification.json + review.json + spec.
#
# Implementação completa (tradução automática técnica → produto) em B-010.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
if [ -z "$NNN" ] || ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "Uso: explain-slice.sh NNN" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
[ ! -d "$SLICE_DIR" ] && { echo "slice $NNN não existe" >&2; exit 1; }
[ ! -f "$SLICE_DIR/spec.md" ] && { echo "spec.md ausente" >&2; exit 1; }

mkdir -p docs/explanations
OUT="docs/explanations/slice-${NNN}.md"

# Extrai título do spec (primeira linha # que não é "slice NNN —")
TITLE="$(grep -m1 '^# ' "$SLICE_DIR/spec.md" | sed 's/^# //' || echo "slice $NNN")"

# Verifica status
VERIF_STATUS="?"
REVIEW_STATUS="?"
if [ -f "$SLICE_DIR/verification.json" ]; then
  VERIF_STATUS="$(grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$SLICE_DIR/verification.json" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
fi
if [ -f "$SLICE_DIR/review.json" ]; then
  REVIEW_STATUS="$(grep -o '"verdict"[[:space:]]*:[[:space:]]*"[^"]*"' "$SLICE_DIR/review.json" | head -1 | sed -E 's/.*"([^"]*)".*/\1/')"
fi

# Determina status amigável
if [ "$VERIF_STATUS" = "approved" ] && [ "$REVIEW_STATUS" = "approved" ]; then
  STATUS_FRIENDLY="✓ pronto para usar"
elif [ "$VERIF_STATUS" = "rejected" ] || [ "$REVIEW_STATUS" = "rejected" ]; then
  STATUS_FRIENDLY="⚠ precisa da sua decisão"
else
  STATUS_FRIENDLY="em andamento"
fi

cat > "$OUT" <<EOF
# ${TITLE}

**Status:** ${STATUS_FRIENDLY}
**Data:** $(date -u +%Y-%m-%d)

---

## O que foi feito

_(a ser preenchido pelo agente principal lendo spec.md + resultados — traduzir para linguagem de produto, sem jargão técnico)_

## O que o usuário final vai ver

_(lista de funcionalidades visíveis: telas, botões, campos, notificações)_

- …
- …

## O que funcionou

_(bullets em PT-BR de produto, com base em verification.json approved e review.json approved)_

- …

## O que NÃO está neste slice (fica pra depois)

_(ler seção "Fora de escopo" do spec.md e traduzir)_

- …

$(if [ "$STATUS_FRIENDLY" = "⚠ precisa da sua decisão" ]; then
cat <<EOF2
## Sua decisão é necessária

**Por que estou pedindo:**
_(explicar em PT-BR o que o verifier ou reviewer rejeitou, sem jargão técnico)_

**Opções:**
- [ ] **Opção A:** <descrição em PT-BR>
- [ ] **Opção B:** <descrição em PT-BR>
- [ ] Quero conversar mais antes de decidir

EOF2
fi)

## Próximo passo

_(ação única e clara para o humano: "testar na tela X", "decidir se Y", "nenhuma ação, segue")_

---

### Detalhes técnicos (opcional, só se precisar)

<details>
<summary>Clique para ver os detalhes técnicos</summary>

- Verifier: ${VERIF_STATUS}
- Reviewer: ${REVIEW_STATUS}
- Spec: \`$SLICE_DIR/spec.md\`
- Verification: \`$SLICE_DIR/verification.json\`
- Review: \`$SLICE_DIR/review.json\`

</details>
EOF

echo "[explain-slice] template gerado em $OUT"
echo "  Status: $STATUS_FRIENDLY"
echo "  IMPORTANTE: agente principal deve preencher as seções '_()_' traduzindo"
echo "  verification.json + review.json + spec.md para linguagem de produto (R12)."
