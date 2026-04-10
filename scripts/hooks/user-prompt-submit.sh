#!/usr/bin/env bash
# UserPromptSubmit hook — injeta reminder leve a cada prompt.
# Reforça P7 (verificação de fato antes de afirmação) sem poluir contexto.
set -euo pipefail

cat <<'JSON'
{"systemMessage":"[reminder] P7: nao afirmar pronto/corrigido/funcionando sem mostrar comando + output + exit code. P8: nunca rodar suite full no meio de uma task."}
JSON
exit 0
