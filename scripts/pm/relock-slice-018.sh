#!/usr/bin/env bash
# ============================================================================
# relock-slice-018.sh (chamado por relock-slice-018.bat)
#
# 1. git checkout main + pull
# 2. Aplica aliases legacy em scripts/merge-slice.sh
# 3. Valida sintaxe do script atualizado (python -c)
# 4. Chama scripts/relock-harness.sh (pede RELOCK ao PM)
# 5. git add + commit + push em main
#
# Referencia: specs/018/merge-slice-update-manifest.md
# ============================================================================

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$REPO_ROOT"

echo ""
echo "=========================================="
echo "  [1/5] Atualizando main local..."
echo "=========================================="
git checkout main
git fetch origin main

# Detecta divergencia e oferece reset --hard (seguro: main do PM nao deveria ter
# commits proprios nao-pushed). Se tiver, aborta.
LOCAL_HEAD="$(git rev-parse HEAD)"
REMOTE_HEAD="$(git rev-parse origin/main)"

if [ "$LOCAL_HEAD" != "$REMOTE_HEAD" ]; then
  AHEAD="$(git rev-list --count origin/main..HEAD)"
  BEHIND="$(git rev-list --count HEAD..origin/main)"

  if [ "$AHEAD" -gt 0 ]; then
    echo ""
    echo "AVISO: main local tem $AHEAD commit(s) que NAO estao em origin/main."
    echo "Isso geralmente e leftover de sessoes anteriores."
    echo ""
    echo "Vou descartar esses commits e alinhar main local com origin/main."
    echo "Se voce fez algum commit manual em main que quer preservar, cancele AGORA."
    echo ""
    read -r -p "Digite 'RESET' para continuar ou qualquer outra coisa para abortar: " confirm
    if [ "$confirm" != "RESET" ]; then
      echo "Abortando."
      exit 1
    fi
  fi

  git reset --hard origin/main
  echo "[OK] main local alinhado com origin/main ($(git rev-parse --short HEAD))"
else
  echo "[OK] main local ja alinhado com origin/main"
fi

TARGET="scripts/merge-slice.sh"
if [ ! -f "$TARGET" ]; then
  echo "ERRO: $TARGET nao encontrado" >&2
  exit 1
fi

echo ""
echo "=========================================="
echo "  [2/5] Aplicando aliases legacy em $TARGET..."
echo "=========================================="

# Backup inline (para rollback em caso de falha)
cp "$TARGET" "$TARGET.pre-slice-018-relock.bak"

# Aplica patch via python: troca 'code-review' (valor esperado) por 'review'
# (canonico) e adiciona logica de alias. O merge-slice.sh original:
#
#   required_gates = [
#       ("review.json", "code-review", "code-review"),
#       ...
#   ]
#
# Vira:
#
#   required_gates = [
#       ("review.json", "review", "code-review", ["code-review"]),  # alias
#       ...
#   ]
#
# Como a tupla muda de aridade, precisamos ajustar o loop tambem. Faz-se
# pythonicamente via arquivo-patch.

python3 - <<'PYEOF'
import re
from pathlib import Path

path = Path("scripts/merge-slice.sh")
original = path.read_text(encoding="utf-8")

# --- Mudanca 1: required_gates agora inclui "aliases" como 4o elemento
# Antes:
#   ("review.json",             "code-review",    "code-review"),
# Depois:
#   ("review.json",             "review",         ["code-review"],       "code-review"),
#
# E idem para os outros 2 gates legacy (security, functional). Os demais
# permanecem com aliases=[].
#
# Estrategia: substituimos a lista inteira por uma nova versao parametrizada
# com aliases. Para nao quebrar o arquivo se ja tiver sido rodado (idempotente),
# checamos se a palavra "aliases" ja existe na linha de required_gates.

if re.search(r'# Aliases legacy aplicados pelo relock do slice 018', original):
    print("[relock-slice-018] ja aplicado — nada a fazer (idempotente)")
    raise SystemExit(0)

# Padroes das 5 linhas canonicas da lista
before = r'''    ("verification.json",       "verify",         "verify"),
    ("review.json",             "code-review",    "code-review"),
    ("security-review.json",    "security-gate",  "security-gate"),
    ("test-audit.json",          "audit-tests",    "audit-tests"),
    ("functional-review.json",   "functional-gate","functional-gate"),'''

after = r'''    # Aliases legacy aplicados pelo relock do slice 018 (2026-04-18)
    # Tupla: (filename, canonico, aliases_legacy, label_humano)
    ("verification.json",       "verify",         [],                "verify"),
    ("review.json",             "review",         ["code-review"],   "code-review"),
    ("security-review.json",    "security-gate",  ["security"],      "security-gate"),
    ("test-audit.json",         "audit-tests",    [],                "audit-tests"),
    ("functional-review.json",  "functional-gate",["functional"],    "functional-gate"),'''

if before not in original:
    print(f"[relock-slice-018] ERRO: bloco required_gates original nao encontrado — merge-slice.sh pode ter sido alterado manualmente", file=__import__("sys").stderr)
    raise SystemExit(2)

patched = original.replace(before, after)

# --- Mudanca 2: optional_gates tambem passa a ter aliases=[] para compat de aridade
optional_before = r'''    ("data-review.json",          "data-gate",          "data-gate"),
    ("observability-review.json", "observability-gate", "observability-gate"),
    ("integration-review.json",   "integration-gate",   "integration-gate"),'''

optional_after = r'''    ("data-review.json",          "data-gate",          [], "data-gate"),
    ("observability-review.json", "observability-gate", [], "observability-gate"),
    ("integration-review.json",   "integration-gate",   [], "integration-gate"),'''

if optional_before in patched:
    patched = patched.replace(optional_before, optional_after)

# --- Mudanca 3: validate_gate passa a desempacotar aliases
validate_before = r'''def validate_gate(filename, expected_gate, label, required):'''
validate_after = r'''def validate_gate(filename, expected_gate, aliases, label, required):'''

if validate_before in patched:
    patched = patched.replace(validate_before, validate_after)

# --- Mudanca 4: dentro do validate_gate, quando gate_val != expected_gate,
# verifica aliases antes de rejeitar.
gate_check_before = r'''    gate_val = data.get("gate")
    if gate_val != expected_gate:
        errors.append(f"{label}: gate={gate_val!r}, esperado {expected_gate!r}")'''

gate_check_after = r'''    gate_val = data.get("gate")
    if gate_val != expected_gate:
        if gate_val in aliases:
            sys.stderr.write(f"[relock-slice-018 alias] {label}: gate={gate_val!r} e legacy — aceito como compat-shim (canonico: {expected_gate!r})\n")
        else:
            errors.append(f"{label}: gate={gate_val!r}, esperado {expected_gate!r} ou alias {aliases!r}")'''

if gate_check_before in patched:
    patched = patched.replace(gate_check_before, gate_check_after)

# --- Mudanca 5: loops que iteram required_gates / optional_gates
# precisam desempacotar o 4o elemento.
for_required_before = r'''for filename, expected_gate, label in required_gates:
    validate_gate(filename, expected_gate, label, required=True)'''
for_required_after = r'''for filename, expected_gate, aliases, label in required_gates:
    validate_gate(filename, expected_gate, aliases, label, required=True)'''

if for_required_before in patched:
    patched = patched.replace(for_required_before, for_required_after)

for_optional_before = r'''for filename, expected_gate, label in optional_gates:
    validate_gate(filename, expected_gate, label, required=False)'''
for_optional_after = r'''for filename, expected_gate, aliases, label in optional_gates:
    validate_gate(filename, expected_gate, aliases, label, required=False)'''

if for_optional_before in patched:
    patched = patched.replace(for_optional_before, for_optional_after)

# --- Adiciona import de sys se necessario (para sys.stderr.write nos alias warns)
if 'import sys' not in patched.split('validate_gate')[0][:2000]:
    patched = patched.replace('import json', 'import json\nimport sys', 1)

path.write_text(patched, encoding="utf-8")
print("[relock-slice-018] scripts/merge-slice.sh atualizado com aliases legacy")
PYEOF

echo ""
echo "=========================================="
echo "  [3/5] Validando sintaxe..."
echo "=========================================="
# Extrai o bloco Python inline e roda `python -c` para checar sintaxe
# (merge-slice.sh tem python3 -c '...' inline).
if ! bash -n "$TARGET"; then
  echo "ERRO: sintaxe bash invalida apos patch — restaurando backup"
  mv "$TARGET.pre-slice-018-relock.bak" "$TARGET"
  exit 3
fi
echo "[OK] sintaxe valida"

echo ""
echo "=========================================="
echo "  [4/5] Relock dos selos (precisa voce digitar RELOCK)..."
echo "=========================================="
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh

echo ""
echo "=========================================="
echo "  [5/5] Commit + push em main..."
echo "=========================================="

# Stage apenas os arquivos esperados
git add scripts/merge-slice.sh scripts/hooks/MANIFEST.sha256 .claude/settings.json.sha256
if ls docs/incidents/harness-relock-*.md 1>/dev/null 2>&1; then
  git add docs/incidents/harness-relock-*.md
fi

# Remove backup para nao vazar no repo
rm -f scripts/merge-slice.sh.pre-slice-018-relock.bak

# Commit
git commit -m "chore(harness): merge-slice aceita aliases legacy (slices 001-017) + relock pos-slice-018

Compat shim: campo gate aceita canonico (review/security-gate/functional-gate)
ou alias legacy (code-review/security/functional). Slices 001-017 permanecem
validaveis; slices 018+ usam apenas canonico.

Justificativa: specs/018/merge-slice-update-manifest.md
              docs/incidents/harness-relock-pending-slice-018.md"

git push origin main

echo ""
echo "=========================================="
echo "  [OK] Slice 018 relock concluido."
echo "=========================================="
