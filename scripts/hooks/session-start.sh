#!/usr/bin/env bash
# SessionStart hook — valida o harness antes de qualquer ação do agente.
# Falha dura (exit 1) se qualquer regra fundamental for violada.
#
# Responsável por:
#   - Verificar arquivos obrigatórios (CLAUDE.md, constitution.md, settings.json)
#   - R1 — rejeitar arquivos de instrução proibidos
#   - Verificar que hooks referenciados em settings.json existem
#   - Emitir mensagem estruturada para o Claude Code carregar na sessão

set -euo pipefail

# Resolve repo root (pasta que contém CLAUDE.md)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

ERRORS=0
err() { echo "[session-start ERR] $*" >&2; ERRORS=$((ERRORS+1)); }
ok()  { echo "[session-start OK ] $*" >&2; }

# ---------- 1. Arquivos obrigatórios ----------
REQUIRED=(
  "CLAUDE.md"
  "docs/constitution.md"
  ".claude/settings.json"
)
for f in "${REQUIRED[@]}"; do
  if [ ! -f "$f" ]; then
    err "arquivo obrigatório ausente: $f"
  else
    ok "encontrado $f"
  fi
done

# ---------- 2. R1 — arquivos/pastas proibidas ----------
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

R1_HITS=0
for f in "${FORBIDDEN_FILES[@]}"; do
  if [ -f "$f" ]; then
    err "R1 violada: arquivo proibido encontrado: $f"
    R1_HITS=$((R1_HITS+1))
  fi
done
for d in "${FORBIDDEN_DIRS[@]}"; do
  if [ -d "$d" ]; then
    err "R1 violada: diretório proibido encontrado: $d"
    R1_HITS=$((R1_HITS+1))
  fi
done
[ "$R1_HITS" -eq 0 ] && ok "R1 nenhum arquivo/pasta proibido"

# ---------- 3. Hooks referenciados existem ----------
if [ -f .claude/settings.json ]; then
  # grep simples sem jq (cross-platform)
  while IFS= read -r line; do
    script="$(echo "$line" | grep -oE 'scripts/hooks/[a-z-]+\.sh' || true)"
    if [ -n "$script" ] && [ ! -f "$script" ]; then
      err "hook referenciado mas ausente: $script"
    fi
  done < .claude/settings.json
  ok "hooks referenciados verificados"
fi

# ---------- 4. Pastas de suporte ----------
for d in ".claude/telemetry" ".claude/snapshots" "docs/audits" "docs/incidents" "docs/retrospectives"; do
  if [ ! -d "$d" ]; then
    mkdir -p "$d" 2>/dev/null && ok "criou $d" || err "não consegui criar $d"
  fi
done

# ---------- 4.5. Drift checks (meta-audit 2026-04-10 itens 1.1, 1.2) ----------
# settings-lock --check: valida que .claude/settings.json não foi alterado
# fora do fluxo autorizado de relock-harness.sh.
if [ -f scripts/hooks/settings-lock.sh ]; then
  if bash scripts/hooks/settings-lock.sh --check >/tmp/settings-lock-check.out 2>&1; then
    ok "settings-lock --check: settings.json íntegro"
  else
    err "settings-lock --check FAIL"
    sed 's/^/  /' /tmp/settings-lock-check.out >&2
  fi
fi

# hooks-lock --check: valida MANIFEST.sha256 contra hashes correntes de scripts/hooks/.
if [ -f scripts/hooks/hooks-lock.sh ]; then
  if bash scripts/hooks/hooks-lock.sh --check >/tmp/hooks-lock-check.out 2>&1; then
    ok "hooks-lock --check: scripts/hooks/ íntegro"
  else
    err "hooks-lock --check FAIL"
    sed 's/^/  /' /tmp/hooks-lock-check.out >&2
  fi
fi

# ---------- 5. Resultado ----------
if [ "$ERRORS" -eq 0 ]; then
  # Formato JSON que o Claude Code interpreta como mensagem de sistema
  cat <<'JSON'
{"systemMessage":"[SessionStart OK] Leia obrigatoriamente antes de qualquer acao: CLAUDE.md + docs/constitution.md + docs/TECHNICAL-DECISIONS.md. Regras P1-P9 e R1-R10 aplicam-se a TODA interacao. Verificacao de fato antes de afirmacao (P7)."}
JSON
  exit 0
else
  echo "[session-start] $ERRORS erro(s) — abortando sessão" >&2
  echo "Corrija os erros acima antes de continuar." >&2
  exit 1
fi
