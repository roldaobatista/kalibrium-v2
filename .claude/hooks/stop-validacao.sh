#!/usr/bin/env bash
# Stop — antes de a maestra encerrar a vez, lembra que mudanças PHP exigem
# Pint + PHPStan + Pest no escopo. Não bloqueia (Stop pode bloquear, mas
# preferimos avisar e deixar a maestra decidir vs travar o usuário).

set -uo pipefail

# Quantos arquivos PHP tocados desde último commit?
# grep retorna 1 quando não acha nada — não tratamos como erro
mudados_php=$(git diff --name-only HEAD 2>/dev/null | grep -E '\.php$' 2>/dev/null | wc -l | tr -d ' ' || echo "0")

if [ -z "$mudados_php" ] || [ "$mudados_php" = "0" ]; then
  exit 0
fi

# Devolve aviso pra a maestra ver no transcript
cat <<EOF
{
  "hookSpecificOutput": {
    "hookEventName": "Stop",
    "additionalContext": "📋 $mudados_php arquivo(s) PHP foram alterados nesta sessão. Antes de afirmar 'feito' ao Roldão, rodar /conferir (ou Pint + PHPStan + Pest no escopo) e relatar resultado em pt-BR pelo efeito visível, não stack trace."
  }
}
EOF

exit 0
