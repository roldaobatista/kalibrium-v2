#!/usr/bin/env bash
# relock-harness.sh — único caminho autorizado para regenerar selos do harness.
#
# Origem: meta-audit 2026-04-10 — adição do PM ao item 1.2.
#
# Atualiza:
#   .claude/settings.json.sha256          (selo do settings.json)
#   scripts/hooks/MANIFEST.sha256         (selo de cada hook)
#
# Salvaguardas (camadas redundantes — todas precisam passar):
#   1. Variável manual: KALIB_RELOCK_AUTHORIZED=1
#      O agente Claude Code pode tentar setar isso inline. Não basta.
#   2. TTY interativa em stdin: [ -t 0 ]
#      A ferramenta Bash do Claude Code não conecta um TTY real.
#      Esta camada bloqueia tentativas via agente.
#   3. Confirmação interativa por digitação: usuário digita "RELOCK".
#      Mesmo que algum dia exista um exec do agente com TTY, exige
#      input que o agente não pode forjar sem ver o stdin.
#   4. Incidente automático: cria docs/incidents/harness-relock-*.md
#      registrando data, usuário git, arquivos alterados e diff de hash.
#
# Como rodar (PM, em terminal externo — fora do Claude Code):
#   cd /c/PROJETOS/saas/kalibrium-v2
#   KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#   (será pedido para digitar "RELOCK" para confirmar)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

die() { echo "[relock-harness FAIL] $*" >&2; exit 1; }
say() { echo "[relock-harness] $*"; }

# ----------------------------------------------------------------------
# Camada 1: variável de ambiente manual
# ----------------------------------------------------------------------
if [ "${KALIB_RELOCK_AUTHORIZED:-}" != "1" ]; then
  die "KALIB_RELOCK_AUTHORIZED não setada — relock só roda com autorização explícita do PM"
fi

# ----------------------------------------------------------------------
# Camada 2: TTY interativa
# ----------------------------------------------------------------------
if [ ! -t 0 ]; then
  die "stdin não é TTY — relock-harness só roda em terminal interativo (não via agente)"
fi

# ----------------------------------------------------------------------
# Camada 3: confirmação por digitação
# ----------------------------------------------------------------------
say "ATENÇÃO: você está prestes a regenerar os selos do harness."
say ""
say "Arquivos que serão re-hashados:"
say "  .claude/settings.json"
say "  scripts/hooks/*.sh (todos)"
say ""
say "Um incidente será criado em docs/incidents/."
say ""
printf "Digite RELOCK para confirmar (qualquer outra coisa cancela): "
read -r CONFIRM
if [ "$CONFIRM" != "RELOCK" ]; then
  die "cancelado pelo usuário"
fi

# ----------------------------------------------------------------------
# Captura estado anterior (para diff de hash no incidente)
# ----------------------------------------------------------------------
TS="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
INCIDENT_FILE="docs/incidents/harness-relock-${TS}.md"
mkdir -p docs/incidents

PREV_SETTINGS_HASH="(novo)"
if [ -f .claude/settings.json.sha256 ]; then
  PREV_SETTINGS_HASH="$(awk '{print $1}' .claude/settings.json.sha256 | head -1)"
fi
PREV_MANIFEST_SHA="(novo)"
if [ -f scripts/hooks/MANIFEST.sha256 ]; then
  PREV_MANIFEST_SHA="$(sha256sum scripts/hooks/MANIFEST.sha256 | awk '{print $1}')"
fi

GIT_USER_NAME="$(git config user.name 2>/dev/null || echo 'unknown')"
GIT_USER_EMAIL="$(git config user.email 2>/dev/null || echo 'unknown')"
HOSTNAME_VAL="$(hostname 2>/dev/null || echo 'unknown')"

# ----------------------------------------------------------------------
# Regenera .claude/settings.json.sha256
# ----------------------------------------------------------------------
[ ! -f .claude/settings.json ] && die ".claude/settings.json não existe"
NEW_SETTINGS_HASH="$(sha256sum --text .claude/settings.json | awk '{print $1}')"
printf '%s  settings.json\n' "$NEW_SETTINGS_HASH" > .claude/settings.json.sha256
say "selado: .claude/settings.json → ${NEW_SETTINGS_HASH:0:12}…"

# ----------------------------------------------------------------------
# Regenera scripts/hooks/MANIFEST.sha256
# ----------------------------------------------------------------------
(
  cd scripts/hooks
  # Lista todos os .sh (basenames). Ordenado para reprodutibilidade.
  HOOK_FILES=$(find . -maxdepth 1 -type f -name '*.sh' -printf '%f\n' 2>/dev/null | sort)
  [ -z "$HOOK_FILES" ] && { echo "[relock-harness FAIL] nenhum hook encontrado em scripts/hooks/" >&2; exit 1; }
  # --text força modo portátil (dois espaços, sem '*' do Windows binary mode)
  echo "$HOOK_FILES" | xargs sha256sum --text > MANIFEST.sha256
)
NEW_MANIFEST_SHA="$(sha256sum scripts/hooks/MANIFEST.sha256 | awk '{print $1}')"
HOOK_COUNT="$(wc -l < scripts/hooks/MANIFEST.sha256 | tr -d ' ')"
say "selado: scripts/hooks/MANIFEST.sha256 ($HOOK_COUNT hooks) → ${NEW_MANIFEST_SHA:0:12}…"

# ----------------------------------------------------------------------
# Camada 4: incidente automático
# ----------------------------------------------------------------------
cat > "$INCIDENT_FILE" <<EOF
# Incidente — relock do harness

**Data UTC:** $(date -u +%Y-%m-%dT%H:%M:%SZ)
**Operador (git):** $GIT_USER_NAME <$GIT_USER_EMAIL>
**Host:** $HOSTNAME_VAL
**Origem:** \`scripts/relock-harness.sh\` invocado manualmente.

## Por que existe este registro

Cada relock do harness é uma operação privilegiada (recria os selos que
protegem \`.claude/settings.json\` e \`scripts/hooks/*\` contra auto-modificação).
Toda execução cria um incidente para auditoria — exigido pelo PM em
2026-04-10 como parte do item 1.2 (adição) do meta-audit action plan.

## Selos antes → depois

| Arquivo | Hash anterior | Hash novo |
|---|---|---|
| \`.claude/settings.json\` | \`${PREV_SETTINGS_HASH:0:16}…\` | \`${NEW_SETTINGS_HASH:0:16}…\` |
| \`scripts/hooks/MANIFEST.sha256\` (sha do próprio manifesto) | \`${PREV_MANIFEST_SHA:0:16}…\` | \`${NEW_MANIFEST_SHA:0:16}…\` |

## Hooks atualmente catalogados ($HOOK_COUNT)

\`\`\`
$(cat scripts/hooks/MANIFEST.sha256)
\`\`\`

## Ação requerida do PM

- [ ] Validar que o relock é esperado (ex: você acabou de editar um hook intencionalmente)
- [ ] Se NÃO esperado: investigar como ocorreu (possível bypass dos hooks de segurança)
- [ ] Commitar este incidente junto com as mudanças do harness para rastreabilidade

EOF

say ""
say "incidente criado: $INCIDENT_FILE"
say ""
say "PRÓXIMOS PASSOS:"
say "  1. Revise as alterações: git diff .claude/settings.json scripts/hooks/"
say "  2. Stage: git add .claude/settings.json scripts/hooks/ docs/incidents/$(basename "$INCIDENT_FILE")"
say "  3. Commit normal (pre-commit-gate vai validar)"
say ""
say "DONE"
