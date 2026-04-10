#!/usr/bin/env bash
# retrospective.sh — gera docs/retrospectives/slice-NNN.md a partir do slice-report.
# Se o report não existir, roda slice-report primeiro.
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
if [ -z "$NNN" ] || ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "Uso: retrospective.sh NNN" >&2
  exit 1
fi

REPORT="docs/retrospectives/slice-${NNN}-report.md"
OUT="docs/retrospectives/slice-${NNN}.md"
DATE="$(date -u +%Y-%m-%d)"

# Gera report se não existir
if [ ! -f "$REPORT" ]; then
  echo "[retrospective] $REPORT não existe — rodando slice-report.sh primeiro..."
  bash "$SCRIPT_DIR/slice-report.sh" "$NNN" || {
    echo "[retrospective] slice-report.sh falhou — abortando" >&2
    exit 1
  }
fi

if [ -f "$OUT" ]; then
  echo "[retrospective] $OUT já existe — não sobrescrevendo. Remova antes de regenerar." >&2
  exit 1
fi

# Extrai métricas chave do report
COMMITS=$(grep -E '^\| Commits no slice \|' "$REPORT" 2>/dev/null | sed -E 's/.*\| ([0-9]+) \|/\1/' | head -1)
APPROVED=$(grep -E '^\| Verificações \(approved\) \|' "$REPORT" 2>/dev/null | sed -E 's/.*\| ([0-9]+) \|/\1/' | head -1)
REJECTED=$(grep -E '^\| Verificações \(rejected\) \|' "$REPORT" 2>/dev/null | sed -E 's/.*\| ([0-9]+) \|/\1/' | head -1)
TOTAL_TOK=$(grep -E '^\| \*\*TOTAL\*\* \|' "$REPORT" 2>/dev/null | sed -E 's/.*\*\*([0-9]+)\*\*.*/\1/' | head -1)

RESULT="?"
if [ "${REJECTED:-0}" -ge 2 ] 2>/dev/null; then
  RESULT="escalated (R6)"
elif [ "${APPROVED:-0}" -ge 1 ] 2>/dev/null; then
  RESULT="approved"
fi

cat > "$OUT" <<EOF
# Retrospectiva slice-${NNN}

**Data:** $DATE
**Resultado:** ${RESULT}
**Fonte numérica:** [slice-${NNN}-report.md](slice-${NNN}-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits | ${COMMITS:-?} |
| Verificações approved | ${APPROVED:-?} |
| Verificações rejected | ${REJECTED:-?} |
| Tokens totais | ${TOTAL_TOK:-?} |

## O que funcionou
- _(preencher com fatos, não impressões — cite evidência)_

## O que não funcionou
- _(preencher — idem; se o verifier rejeitou, dizer a regra e onde)_

## Gates que dispararam em falso
- _(hook X bloqueou caso Y mas o comportamento estava correto → considerar ajustar regra; se for ajuste de P/R, lembrar §5 da constitution)_

## Gates que deveriam ter disparado e não dispararam
- _(incidente observado que passou pelos hooks → adicionar check ao hook Z)_

## Mudanças propostas
- [ ] _(mudança em hook → item em docs/guide-backlog.md)_
- [ ] _(mudança em P/R → exige ADR seguindo constitution §5)_
- [ ] _(mudança em sub-agent → item em docs/guide-backlog.md)_

## Lições para o guia
- _(o que vale salvar em docs/constitution.md, CLAUDE.md, ou guide-backlog.md?)_

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R10 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit \`chore(harness):\` + item em \`docs/guide-backlog.md\`.
EOF

echo "[retrospective] gerado em $OUT"
echo "  Resultado pré-preenchido: $RESULT"
echo "  Preencha as seções antes de commitar."
