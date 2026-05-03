#!/usr/bin/env bash
# PostToolUse Bash — detecta falha em testes/build/deploy e abre uma "ideia
# automática" em docs/backlog/ideias/auto-*.md pra maestra olhar na próxima
# sessão. Substitui ter Sentry/email — captura é toda local, dentro do repo.
#
# Não bloqueia, não interrompe. Só registra. A maestra (Claude) traduz pra
# pt-BR sem jargão na próxima sessão.

set -uo pipefail

input=$(cat)

# Extrai comando e saída do JSON do hook (defensivo: jq pode não existir)
command=$(printf '%s' "$input" | sed -n 's/.*"command"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)
stdout=$(printf '%s' "$input" | sed -n 's/.*"stdout"[[:space:]]*:[[:space:]]*"\(.*\)".*/\1/p' | head -1)
stderr=$(printf '%s' "$input" | sed -n 's/.*"stderr"[[:space:]]*:[[:space:]]*"\(.*\)".*/\1/p' | head -1)

if [ -z "$command" ]; then
  exit 0
fi

# Só age em comandos de teste/build/deploy. Resto ignora pra não gerar barulho.
case "$command" in
  *"composer test"*|*"vendor/bin/pest"*|*"vendor/bin/phpstan"*|*"vendor/bin/pint"*|\
  *"npm run test"*|*"npm run build"*|*"npm run lint"*|\
  *"scripts/deploy"*|*"php artisan test"*|*"php artisan migrate"*) ;;
  *) exit 0 ;;
esac

combinado="${stdout}
${stderr}"

# Padrões que indicam falha real (não "0 failed", não "0 errors")
falhou=0
if printf '%s' "$combinado" | grep -qiE '([1-9][0-9]*[[:space:]]*(failed|failures|errors))|FAIL[[:space:]]|FAILED|✘|fatal:|Error:[[:space:]]|Cannot find|MODULE_NOT_FOUND|SyntaxError|TypeError|exit code [1-9]'; then
  # Filtra falsos positivos comuns
  if ! printf '%s' "$combinado" | grep -qiE '0[[:space:]]+(failed|errors)|no errors|all tests passed|tests:[[:space:]]+[0-9]+[[:space:]]+passed'; then
    falhou=1
  fi
fi

if [ "$falhou" -eq 0 ]; then
  exit 0
fi

# Cria arquivo de ideia automática
ideias_dir="docs/backlog/ideias"
mkdir -p "$ideias_dir" 2>/dev/null || true

timestamp=$(date +'%Y%m%d-%H%M%S')
data_iso=$(date +'%Y-%m-%dT%H:%M:%S')

# Tipo de problema (pra nome do arquivo)
tipo="erro"
case "$command" in
  *test*|*pest*) tipo="teste-falhou" ;;
  *phpstan*) tipo="analise-estatica" ;;
  *pint*) tipo="formatacao" ;;
  *build*) tipo="build-falhou" ;;
  *lint*) tipo="lint-falhou" ;;
  *deploy*) tipo="deploy-falhou" ;;
  *migrate*) tipo="migrate-falhou" ;;
esac

arquivo="$ideias_dir/auto-${timestamp}-${tipo}.md"

# Pega últimas 40 linhas do output combinado pra contexto
trecho=$(printf '%s' "$combinado" | tail -40)

cat > "$arquivo" <<EOF
---
tipo: problema-auto
estado: nao-refinado
detectado-em: $data_iso
origem: hook captura-problema (PostToolUse Bash)
categoria: $tipo
---

# Problema detectado automaticamente

Sistema rodou um comando que aparenta ter falhado. Esta ideia foi aberta sozinha
pelo hook \`captura-problema\`. Próxima sessão, a maestra deve:

1. Ler o trecho abaixo
2. Traduzir o problema em pt-BR pelo **efeito visível** (não pelo stack trace)
3. Propor ao Roldão: vira história? é bug pra corrigir agora? é falso alarme?

## Comando que falhou

\`\`\`
$command
\`\`\`

## Trecho relevante da saída (interno — não mostrar cru ao Roldão)

\`\`\`
$trecho
\`\`\`

## Próximo passo

- [ ] Maestra leu e traduziu pra pt-BR
- [ ] Roldão decidiu: vira história / corrige direto / arquiva como falso alarme
EOF

exit 0
