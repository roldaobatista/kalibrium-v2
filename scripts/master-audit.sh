#!/usr/bin/env bash
# master-audit.sh — prepara input package para master-auditor (ADR-0012 E2)
# e imprime instruções para o orquestrador invocar as duas trilhas dual-LLM.
#
# Uso: bash scripts/master-audit.sh NNN
#
# O script faz as partes mecânicas (validar pré-condições, montar input,
# validar schemas). A invocação real das LLMs (Agent Opus + Codex GPT-5)
# é feita pelo orquestrador principal, porque são tools do Claude Code,
# não comandos shell.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

SLICE_NNN="${1:-}"
if [ -z "$SLICE_NNN" ]; then
  echo "uso: bash scripts/master-audit.sh NNN" >&2
  exit 2
fi

SPEC_DIR="specs/$SLICE_NNN"
INPUT_DIR="master-audit-input"

log() { echo "[master-audit] $*"; }
fail() { log "ERRO: $*" >&2; exit "${2:-1}"; }

# ---------- 1. Pre-condições ----------

[ -d "$SPEC_DIR" ] || fail "specs/$SLICE_NNN não existe" 2

REQUIRED_JSONS=(
  "verification.json"
  "review.json"
  "security-review.json"
  "test-audit.json"
  "functional-review.json"
)

log "Validando pré-condições (5 gates anteriores approved com findings=[])..."

for j in "${REQUIRED_JSONS[@]}"; do
  path="$SPEC_DIR/$j"
  if [ ! -f "$path" ]; then
    fail "gate anterior ausente: $path" 2
  fi
  verdict=$(jq -r '.verdict // "missing"' "$path" 2>/dev/null)
  findings_count=$(jq -r '.findings | length' "$path" 2>/dev/null || echo "err")
  if [ "$verdict" != "approved" ]; then
    fail "$j tem verdict=$verdict (esperado: approved)" 2
  fi
  if [ "$findings_count" != "0" ]; then
    fail "$j tem $findings_count findings (esperado: 0)" 2
  fi
  log "  ✓ $j: approved, findings=[]"
done

[ -f "$SPEC_DIR/spec.md" ] || fail "specs/$SLICE_NNN/spec.md ausente" 2
[ -f "$SPEC_DIR/plan.md" ] || fail "specs/$SLICE_NNN/plan.md ausente" 2

# ---------- 2. Montar input package ----------

log "Montando $INPUT_DIR/ ..."
rm -rf "$INPUT_DIR" 2>/dev/null
mkdir -p "$INPUT_DIR"

cp "$SPEC_DIR/spec.md" "$INPUT_DIR/"
cp "$SPEC_DIR/plan.md" "$INPUT_DIR/"
for j in "${REQUIRED_JSONS[@]}"; do
  cp "$SPEC_DIR/$j" "$INPUT_DIR/"
done

# Diff vs base (main) — nomes de arquivos tocados
git diff --name-status main...HEAD > "$INPUT_DIR/diff.txt" 2>/dev/null || echo "(sem diff)" > "$INPUT_DIR/diff.txt"

# Snapshot da constitution
cp docs/constitution.md "$INPUT_DIR/constitution-snapshot.md"

# Índice de ADRs aceitas
if [ -d docs/adr ]; then
  ls docs/adr/*.md | sort > "$INPUT_DIR/adr-index.txt"
fi

log "Input package montado com $(ls "$INPUT_DIR/" | wc -l) arquivos."

# ---------- 3. Instruções para o orquestrador ----------

cat <<EOF

================================================================
  INSTRUÇÕES PARA O ORQUESTRADOR (Claude Code)
================================================================

Input package pronto em: $INPUT_DIR/

Próximas ações (em paralelo quando possível):

1. TRILHA A — Claude Opus 4.6 (master-auditor agent)
   - Invoque via tool Agent:
     subagent_type: master-auditor
     prompt: <ver §Prompt canônico em .claude/agents/master-auditor.md>
   - O sandbox hook restringe reads a $INPUT_DIR/
   - Saída esperada: $INPUT_DIR/trail-opus.json

2. TRILHA B — GPT-5 (Codex CLI via Bash direto, recomendado)
   - IMPORTANTE: em ChatGPT Plus auth NAO passe --model (default = gpt-5).
     Ver docs/operations/codex-gpt5-setup.md para regras e troubleshooting.
   - Invoque via tool Bash:
     cd $REPO_ROOT/$INPUT_DIR && codex exec \\
       --sandbox workspace-write \\
       --skip-git-repo-check \\
       "<mesmo prompt da trilha A>"
   - Alternativa (MCP, pode sofrer CreateProcessAsUserW no Windows):
     mcp__codex__codex com sandbox: "workspace-write", cwd: $REPO_ROOT/$INPUT_DIR
     (SEM passar model — deixar default)
   - Saída esperada: $INPUT_DIR/trail-gpt5.json

3. CONSOLIDAÇÃO (feita pelo orquestrador):
   - Ler $INPUT_DIR/trail-opus.json e $INPUT_DIR/trail-gpt5.json
   - Comparar verdicts:
     * Ambos approved + findings=[] → consenso approved → gravar specs/$SLICE_NNN/master-audit.json
     * Ambos rejected → consenso rejected (findings mergeados) → invocar /fix NNN master-auditor
     * Divergentes → reconciliação (até 3 rodadas com mcp__codex__codex-reply / Agent SendMessage)

4. PÓS-CONSOLIDAÇÃO:
   - Gravar specs/$SLICE_NNN/master-audit.json
   - Registrar em .claude/telemetry/slice-$SLICE_NNN.jsonl
   - Disparar scripts/explain-slice.sh se escalate_human ou divergência

================================================================
EOF

exit 0
