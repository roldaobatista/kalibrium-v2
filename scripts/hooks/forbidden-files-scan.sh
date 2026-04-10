#!/usr/bin/env bash
# Scan on-demand por arquivos/pastas proibidos (R1) + instruções órfãs.
# Mesma lógica do session-start.sh mas rodável via /forbidden-files-scan.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

ERRORS=0

FORBIDDEN_FILES=(
  ".cursorrules"
  "AGENTS.md"
  "GEMINI.md"
  "copilot-instructions.md"
  ".windsurfrules"
  ".aider.conf.yml"
)
FORBIDDEN_DIRS=(
  ".bmad-core"
  ".agents"
  ".cursor"
  ".continue"
)

echo "[forbidden-files-scan] verificando R1..."

# Arquivos
for f in "${FORBIDDEN_FILES[@]}"; do
  # busca em todo o repo, não só na raiz
  HITS="$(find . -name "$f" -not -path "./.git/*" -not -path "./node_modules/*" -not -path "./vendor/*" 2>/dev/null || true)"
  if [ -n "$HITS" ]; then
    echo "  [FAIL] $f encontrado:" >&2
    echo "$HITS" | sed 's/^/    /' >&2
    ERRORS=$((ERRORS+1))
  fi
done

# Pastas
for d in "${FORBIDDEN_DIRS[@]}"; do
  HITS="$(find . -type d -name "$d" -not -path "./.git/*" -not -path "./node_modules/*" 2>/dev/null || true)"
  if [ -n "$HITS" ]; then
    echo "  [FAIL] diretório $d encontrado:" >&2
    echo "$HITS" | sed 's/^/    /' >&2
    ERRORS=$((ERRORS+1))
  fi
done

# Instruções órfãs: arquivos .md fora de CLAUDE.md/.claude/docs/constitution.md com pattern
echo "[forbidden-files-scan] verificando instruções órfãs..."
ORPHAN_HITS="$(find . -type f -name "*.md" \
  -not -path "./.git/*" \
  -not -path "./node_modules/*" \
  -not -path "./vendor/*" \
  -not -path "./.claude/*" \
  -not -path "./docs/constitution.md" \
  -not -path "./CLAUDE.md" \
  -exec grep -l -E "^(You are|You're|Your role|As an agent) " {} \; 2>/dev/null || true)"

if [ -n "$ORPHAN_HITS" ]; then
  echo "  [WARN] arquivos com padrão de instrução fora do whitelist:" >&2
  echo "$ORPHAN_HITS" | sed 's/^/    /' >&2
fi

if [ "$ERRORS" -eq 0 ]; then
  echo "[forbidden-files-scan OK] nenhuma violação de R1"
  exit 0
else
  echo "[forbidden-files-scan FAIL] $ERRORS violação(ões) de R1" >&2
  exit 1
fi
