#!/usr/bin/env bash
# Skill /guide-check — roda checks do guide-auditor localmente (sem invocar sub-agent).
# Versão standalone: não precisa de Claude Code para rodar.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

DATE="$(date -u +%Y-%m-%d)"
OUT="docs/audits/audit-${DATE}.md"
mkdir -p docs/audits .claude/snapshots

FINDINGS=0
fail() { echo "  - [FAIL] $*" >> "$OUT"; FINDINGS=$((FINDINGS+1)); }
warn() { echo "  - [WARN] $*" >> "$OUT"; }
pass() { echo "  - [OK]   $*" >> "$OUT"; }

cat > "$OUT" <<EOF
# Audit $DATE

**Gerado por:** scripts/guide-check.sh
**Modo:** standalone (sem sub-agent)

## Findings
EOF

# ---------- Check 1: arquivos proibidos ----------
echo "" >> "$OUT"
echo "### [CHECK-1] R1 — arquivos proibidos" >> "$OUT"
if bash scripts/hooks/forbidden-files-scan.sh >/dev/null 2>&1; then
  pass "nenhum arquivo/diretório proibido encontrado"
else
  fail "forbidden-files-scan reportou violação — rode /forbidden-files-scan"
fi

# ---------- Check 2: settings.json diff vs snapshot ----------
echo "" >> "$OUT"
echo "### [CHECK-2] Hooks — settings.json vs snapshot anterior" >> "$OUT"
LATEST_SNAPSHOT="$(ls -t .claude/snapshots/settings-*.json 2>/dev/null | head -1 || true)"
if [ -z "$LATEST_SNAPSHOT" ]; then
  warn "nenhum snapshot anterior — gravando primeiro snapshot"
  cp .claude/settings.json ".claude/snapshots/settings-${DATE}.json"
else
  if diff -q "$LATEST_SNAPSHOT" .claude/settings.json >/dev/null 2>&1; then
    pass "settings.json inalterado desde $(basename "$LATEST_SNAPSHOT")"
  else
    warn "settings.json mudou desde $(basename "$LATEST_SNAPSHOT") — revisar diff"
    cp .claude/settings.json ".claude/snapshots/settings-${DATE}.json"
  fi
fi

# ---------- Check 3: autores fora do allowlist (R5 + item 1.7 meta-audit) ----------
# Audita apenas commits no range ${baseline}..HEAD. Commits anteriores ao
# baseline são débito bootstrap anistiado em docs/harness-limitations.md
# (decisão PM Q1 do meta-audit 2026-04-10).
echo "" >> "$OUT"
echo "### [CHECK-3] R5 — autores de commit vs allowlist (range \${baseline}..HEAD)" >> "$OUT"
ALLOWLIST=".claude/allowed-git-identities.txt"
BASELINE_FILE=".claude/git-identity-baseline"

if [ ! -f "$ALLOWLIST" ]; then
  fail "$ALLOWLIST ausente — pre-commit-gate não tem fonte de verdade para R5"
elif [ ! -f "$BASELINE_FILE" ]; then
  fail "$BASELINE_FILE ausente — CHECK-3 não tem baseline de auditoria"
else
  # Extrai SHA do baseline (primeira linha não-comentário, não-vazia)
  BASELINE_SHA="$(grep -vE '^[[:space:]]*(#|$)' "$BASELINE_FILE" | head -1 | tr -d '[:space:]')"
  if [ -z "$BASELINE_SHA" ]; then
    fail "$BASELINE_FILE não contém SHA válido"
  elif ! git rev-parse --verify "$BASELINE_SHA" >/dev/null 2>&1; then
    fail "baseline SHA $BASELINE_SHA não existe no repo (corrompido?)"
  else
    # Constrói lista normalizada de identidades permitidas (lowercase, sem comentários/blanks)
    ALLOWED_LIST="$(grep -vE '^[[:space:]]*(#|$)' "$ALLOWLIST" | tr '[:upper:]' '[:lower:]')"
    # Identidades únicas APENAS no range ${baseline}..HEAD (exclui débito anistiado)
    RECENT="$(git log "${BASELINE_SHA}..HEAD" --format='%an <%ae>' 2>/dev/null | sort -u || true)"
    SCOPE_COUNT="$(git log "${BASELINE_SHA}..HEAD" --format='%h' 2>/dev/null | wc -l | tr -d ' ')"
    ROGUE=""
    while IFS= read -r ident; do
      [ -z "$ident" ] && continue
      if ! printf '%s\n' "$ALLOWED_LIST" | grep -qFx "$(echo "$ident" | tr '[:upper:]' '[:lower:]')"; then
        ROGUE="$ROGUE$ident\n"
      fi
    done <<< "$RECENT"
    if [ -z "$ROGUE" ]; then
      pass "todas as ${SCOPE_COUNT} identidades em \${baseline}..HEAD estão no allowlist (baseline=${BASELINE_SHA:0:12})"
    else
      echo '  ```' >> "$OUT"
      echo -e "$ROGUE" >> "$OUT"
      echo '  ```' >> "$OUT"
      fail "identidades fora do allowlist no range \${baseline}..HEAD (item 1.7 meta-audit)"
    fi
  fi
fi

# ---------- Check 4: bypass history ----------
echo "" >> "$OUT"
echo "### [CHECK-4] R9 — bypass history" >> "$OUT"
BYPASS="$(git log --all --format='%s' 2>/dev/null | grep -iE '(bypass|no.?verify|skip.?test)' || true)"
if [ -z "$BYPASS" ]; then
  pass "nenhum sinal de bypass no histórico"
else
  echo '  ```' >> "$OUT"
  echo "$BYPASS" >> "$OUT"
  echo '  ```' >> "$OUT"
  fail "mensagens de commit suspeitas"
fi

# ---------- Check 5: referências não marcadas ----------
echo "" >> "$OUT"
echo "### [CHECK-5] R7 — referências sem cabeçalho não-instrucional" >> "$OUT"
UNMARKED=""
for f in docs/reference/*.md; do
  [ ! -f "$f" ] && continue
  if ! head -3 "$f" | grep -q "REFERÊNCIA NÃO-INSTRUCIONAL"; then
    UNMARKED="$UNMARKED$f\n"
  fi
done
if [ -z "$UNMARKED" ]; then
  pass "todas as referências marcadas"
else
  echo -e "$UNMARKED" | sed 's/^/    /' >> "$OUT"
  fail "arquivos sem marcação R7"
fi

# ---------- Score final ----------
echo "" >> "$OUT"
echo "## Score" >> "$OUT"
if [ "$FINDINGS" -eq 0 ]; then
  echo "**VERDE** — nenhum finding bloqueante." >> "$OUT"
  echo "[guide-check VERDE] $OUT"
  exit 0
elif [ "$FINDINGS" -le 2 ]; then
  echo "**AMARELO** — $FINDINGS finding(s) não-bloqueante(s). Endereçar no próximo slice." >> "$OUT"
  echo "[guide-check AMARELO] $OUT"
  exit 0
else
  echo "**VERMELHO** — $FINDINGS finding(s). Parar trabalho em progresso; humano decide." >> "$OUT"
  echo "[guide-check VERMELHO] $OUT" >&2
  exit 1
fi
