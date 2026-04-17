#!/usr/bin/env bash
# aplicar-relock-branch-sync-check.sh
#
# Fecha o débito B-031 (guide-backlog): ativa o hook branch-sync-check no
# session-start.sh para avisar quando a branch de trabalho está atrasada
# vs origin/main.
#
# Origem: retrospectiva do slice-015 — a branch work/offline-discovery-2026-04-16
# ficou >50 commits atrás de main durante a pausa e causou conflito em arquivo
# selado (MANIFEST.sha256), forçando abandono de PR.
#
# Uso (apenas via APLICAR-RELOCK-BRANCH-SYNC-CHECK.bat):
#   KALIB_RELOCK_AUTHORIZED=1 bash scripts/aplicar-relock-branch-sync-check.sh
#
# Exit codes:
#   0 sucesso
#   1 pré-condição falhou (arquivo ausente, variável faltando)
#   2 relock-harness.sh falhou
#   3 patch já aplicado (idempotente — sai limpo mas avisa)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

say()  { echo "[relock-bsc] $*"; }
fail() { echo "[relock-bsc FAIL] $*" >&2; exit 1; }

# Autorização: vem do próprio ato de rodar o .bat (cmd.exe não propaga env
# vars confiavelmente para bash.exe no Git Bash; padrão usado pelo script
# gêmeo aplicar-relock-adr-0017-0019.sh é exportar a var antes do relock).
# A autorização real vem da TTY + digitação literal RELOCK em relock-harness.sh.

SRC="scripts/staging/branch-sync-check.sh"
DST="scripts/hooks/branch-sync-check.sh"
SS="scripts/hooks/session-start.sh"

[ -f "$SRC" ] || [ -f "$DST" ] || fail "$SRC não existe e $DST também não — nada a fazer"
[ -f "$SS" ] || fail "$SS ausente — repo em estado inesperado"

# Detecta idempotência
ALREADY_IN_HOOKS=0
ALREADY_IN_SESSION=0
[ -f "$DST" ] && ALREADY_IN_HOOKS=1
grep -q "branch-sync-check.sh" "$SS" 2>/dev/null && ALREADY_IN_SESSION=1

if [ "$ALREADY_IN_HOOKS" = "1" ] && [ "$ALREADY_IN_SESSION" = "1" ]; then
  say "patch já aplicado — nada a fazer (idempotente)."
  say "se o MANIFEST.sha256 estiver defasado, rode relock-harness.sh direto."
  exit 3
fi

# 1. Mover staging → hooks (se ainda não foi)
if [ "$ALREADY_IN_HOOKS" = "0" ]; then
  say "movendo $SRC → $DST..."
  mv "$SRC" "$DST"
  chmod +x "$DST"
  say "movido + chmod +x"
else
  say "$DST já existe — preservando"
  # Remove staging se ainda tiver
  [ -f "$SRC" ] && rm -f "$SRC" && say "staging $SRC removido (duplicata)"
fi

# 2. Backup do session-start antes de patch
TS="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
if [ "$ALREADY_IN_SESSION" = "0" ]; then
  BACKUP="$SS.bak-$TS"
  cp "$SS" "$BACKUP"
  say "backup criado: $BACKUP"

  # 3. Adiciona invocação do hook ANTES da linha final do session-start
  # Estratégia: procura pela última ocorrência de 'exit 0' ou final de arquivo
  # e insere bloco não-bloqueante.
  python3 - "$SS" <<'PY'
import sys, pathlib
p = pathlib.Path(sys.argv[1])
text = p.read_text(encoding="utf-8")
block = '''
# B-031 — aviso de branch desatualizada vs origin/main (não-bloqueante)
if [ -x "$SCRIPT_DIR/branch-sync-check.sh" ]; then
  bash "$SCRIPT_DIR/branch-sync-check.sh" || true
fi

'''
# Tenta inserir antes de "exit 0" final
if "exit 0\n" in text:
    idx = text.rfind("exit 0\n")
    text = text[:idx] + block + text[idx:]
else:
    if not text.endswith("\n"):
        text += "\n"
    text += block
p.write_text(text, encoding="utf-8")
print("[relock-bsc] session-start.sh patched")
PY
else
  say "$SS já contém invocação do branch-sync-check — não re-patching"
fi

# 4. Rodar relock-harness.sh
say "invocando relock-harness.sh (você precisará digitar RELOCK)..."
echo ""
export KALIB_RELOCK_AUTHORIZED=1
if ! bash scripts/relock-harness.sh; then
  say "relock-harness.sh falhou (exit $?)"
  say "você pode reverter com: mv $SS.bak-$TS $SS"
  exit 2
fi

# 5. Sucesso
echo ""
say "======================================================"
say " RELOCK APLICADO COM SUCESSO — B-031 FECHADO"
say "======================================================"
say ""
say "Próximos passos (já indicados pelo .bat):"
say "  git add $DST $SS scripts/hooks/MANIFEST.sha256 docs/incidents/harness-relock-*.md"
say "  git commit -m 'chore(harness): ativar branch-sync-check (fecha B-031)'"
say "  git push"
say ""
say "Teste manual rápido:"
say "  bash scripts/hooks/branch-sync-check.sh"
say "  # em main: sem output. Em branch atrasada: banner de aviso."
exit 0
