#!/usr/bin/env bash
# UserPromptSubmit — duas funções:
#   1) Intercepta pedidos sensíveis (deploy, drop, force) e força confirmação.
#   2) Detecta quando Roldão está CORRIGINDO a maestra e injeta lembrete pra
#      salvar lição em memória — assim a fábrica aprende sozinha com o tempo.
# Não bloqueia; apenas injeta contexto adicional via additionalContext.

set -uo pipefail

input=$(cat)

prompt=$(printf '%s' "$input" | tr '\n' ' ' | sed -n 's/.*"prompt"[[:space:]]*:[[:space:]]*"\(.*\)".*/\1/p' | head -1)

if [ -z "$prompt" ]; then
  exit 0
fi

# ── Bloco 1 — pedidos sensíveis (deploy, drop, force) ───────────────────
sensiveis=(
  'subir.*(produ|servidor.*cliente)'
  'deploy'
  '(apagar|deletar|drop|truncate).*(banco|tabela|cliente|tenant|produ)'
  'reset.*hard'
  'force.*push'
  'rodar.*migrate.*produ'
  'mexer.*(pagamento|cobran|fatura).*real'
)

for padrao in "${sensiveis[@]}"; do
  if printf '%s' "$prompt" | grep -qiE "$padrao"; then
    cat <<EOF
{
  "hookSpecificOutput": {
    "hookEventName": "UserPromptSubmit",
    "additionalContext": "⚠ PEDIDO SENSÍVEL DETECTADO. Antes de executar qualquer ação irreversível ou que afete cliente real, confirmar EXPLICITAMENTE em pt-BR sem jargão (ex: 'Vou subir as mudanças X, Y e Z pro servidor que o cliente usa. Posso seguir?'). Não assumir aprovação implícita do pedido original."
  }
}
EOF
    exit 0
  fi
done

# ── Bloco 2 — momento de aprendizado (Roldão está corrigindo) ───────────
# Padrões fortes de correção/insatisfação (sem acentos pra robustez no Git Bash)
correcoes=(
  'nao (e|eh|foi) (isso|esse|essa|aquilo|assim)'
  '(ta|esta) errado'
  'errou(\b| )'
  '(refaz|refaca|refaça)(\b| )'
  '(faz|faça|fazer) de novo'
  'nao (entendeu|entendi|entende)'
  'nao era (pra|para)'
  '(esqueceu|esqueci) (de|que)'
  'deixa eu (te )?(explicar|repetir)'
  '(ja|já) (te )?(falei|disse) (pra|para|que)'
  '(de novo|dnv) (esse|este) erro'
  '(porque|por que) (voce|você|cê) (fez|fez isso|nao|não|fica|sempre|continua|insiste)'
  '(porque|por que) fica (perguntando|fazendo|repetindo|me)'
  'parou de (fazer|seguir)'
  '(deveria|devia) ser (automatico|automático)'
  'sempre que (necessitar|precisar)'
  '(para|pare) de (perguntar|me perguntar|fazer)'
  'voltou a (fazer|errar)'
  'pediu (pra|para) (nao|não)'
  'nao (era|é) (pra|para) (fazer|usar|chamar)'
  'qual a parte (do|de) .* (que )?(voce|você) (nao|não) (entendeu|entende)'
)

for padrao in "${correcoes[@]}"; do
  if printf '%s' "$prompt" | grep -qiE "$padrao"; then
    cat <<EOF
{
  "hookSpecificOutput": {
    "hookEventName": "UserPromptSubmit",
    "additionalContext": "🧠 MOMENTO DE APRENDIZADO DETECTADO. Roldão aparenta estar corrigindo um comportamento. Após resolver o pedido atual: (1) identifique QUAL regra/padrão foi violado; (2) salve memória nova em C:/Users/rolda/.claude/projects/C--PROJETOS-saas-kalibrium-v2/memory/feedback_<topic_slug>.md no formato padrão (frontmatter type:feedback + corpo com **Why:** e **How to apply:**); (3) adicione linha curta no MEMORY.md; (4) confirme ao Roldão em pt-BR sem jargão: 'salvei essa lição pra não repetir'. Se já existir feedback parecido, atualize em vez de duplicar. Se for falso positivo (Roldão não estava corrigindo), ignore silenciosamente."
  }
}
EOF
    exit 0
  fi
done

exit 0
