#!/usr/bin/env bash
# Claude Code status line — v2 (com colorização por zona de contexto)
# Destino final: ~/.claude/statusline-command.sh
# Formato: opus-4.7[1M] | 🟢 87k/1M (91% livre) | $2.14 | kalibrium-v2 (main)
#
# Zonas:
#   🟢 VERDE    : 0-60% usado  (ou 40-100% livre)
#   🟡 AMARELO  : 60-80% usado (20-40% livre)  — sugerir checkpoint em marco
#   🟠 LARANJA  : 80-90% usado (10-20% livre)  — checkpoint agora
#   🔴 VERMELHO : 90%+   usado (<10% livre)    — resetar imediatamente
#
# ANSI colors (fg):
#   verde   = \033[32m
#   amarelo = \033[33m
#   laranja = \033[38;5;208m
#   vermelho= \033[31m
#   reset   = \033[0m

input=$(cat)

# --- Model ---
model_id=$(printf '%s' "$input" | grep -o '"id":"[^"]*"' | head -1 | sed 's/"id":"//;s/"//')
model_display=$(printf '%s' "$input" | grep -o '"display_name":"[^"]*"' | head -1 | sed 's/"display_name":"//;s/"//')

if [ -n "$model_id" ]; then
  model_short=$(printf '%s' "$model_id" \
    | sed 's/claude-//Ig' \
    | sed 's/-20[0-9]\{6\}$//' \
    | sed 's/claude//Ig')
else
  model_short="$model_display"
fi

# Context window size — detectar pelo model_id (opus-4.x = 1M, resto = 200k)
if printf '%s' "$model_id" | grep -qi 'opus.*4\|opus-4'; then
  ctx_size=1000000
  ctx_label="1M"
elif printf '%s' "$model_id" | grep -qi 'opus'; then
  ctx_size=200000
  ctx_label="200k"
else
  ctx_size=200000
  ctx_label="200k"
fi

json_ctx=$(printf '%s' "$input" | grep -o '"context_window_size":[0-9]*' | head -1 | grep -o '[0-9]*$')
if [ -n "$json_ctx" ] && [ "$json_ctx" -gt 0 ] 2>/dev/null; then
  ctx_size="$json_ctx"
  if [ "$ctx_size" -ge 900000 ]; then
    ctx_label="1M"
  else
    ctx_label="200k"
  fi
fi

model_str="${model_short}[${ctx_label}]"

# --- Tokens ---
input_tokens=$(printf '%s' "$input" | grep -o '"input_tokens":[0-9]*' | head -1 | grep -o '[0-9]*$')
output_tokens=$(printf '%s' "$input" | grep -o '"output_tokens":[0-9]*' | head -1 | grep -o '[0-9]*$')
cache_read=$(printf '%s' "$input" | grep -o '"cache_read_input_tokens":[0-9]*' | head -1 | grep -o '[0-9]*$')

input_tokens=${input_tokens:-0}
output_tokens=${output_tokens:-0}
cache_read=${cache_read:-0}

used_tokens=$(( input_tokens + output_tokens + cache_read ))

if [ "$used_tokens" -ge 1000 ]; then
  used_k=$(( used_tokens / 1000 ))
  used_str="${used_k}k"
else
  used_str="${used_tokens}"
fi

if [ "$ctx_size" -ge 1000000 ]; then
  ctx_str="1M"
elif [ "$ctx_size" -ge 1000 ]; then
  ctx_k=$(( ctx_size / 1000 ))
  ctx_str="${ctx_k}k"
else
  ctx_str="${ctx_size}"
fi

# --- Percentual + zona + cor ---
pct_used=0
pct_livre=100
if [ "$ctx_size" -gt 0 ] && [ "$used_tokens" -gt 0 ]; then
  pct_used=$(( used_tokens * 100 / ctx_size ))
  pct_livre=$(( 100 - pct_used ))
fi

remaining_pct=$(printf '%s' "$input" | grep -o '"remaining_percentage":[0-9.]*' | head -1 | grep -o '[0-9.]*$')
if [ -n "$remaining_pct" ]; then
  pct_livre=$(printf '%.0f' "$remaining_pct" 2>/dev/null || echo "$remaining_pct")
  pct_used=$(( 100 - pct_livre ))
fi

# Determina zona + emoji + cor ANSI
if [ "$pct_used" -lt 60 ]; then
  zone_emoji="🟢"
  zone_color="\033[32m"       # verde
elif [ "$pct_used" -lt 80 ]; then
  zone_emoji="🟡"
  zone_color="\033[33m"       # amarelo
elif [ "$pct_used" -lt 90 ]; then
  zone_emoji="🟠"
  zone_color="\033[38;5;208m" # laranja
else
  zone_emoji="🔴"
  zone_color="\033[31m"       # vermelho
fi
color_reset="\033[0m"

pct_str="(${pct_livre}% livre)"
tokens_colored="${zone_color}${zone_emoji} ${used_str}/${ctx_str} ${pct_str}${color_reset}"

# --- Custo acumulado ---
cost=$(printf '%s' "$input" | grep -o '"total_cost_usd":[0-9.]*' | head -1 | grep -o '[0-9.]*$')
if [ -n "$cost" ] && [ "$cost" != "0" ] && [ "$cost" != "0.0" ]; then
  cost_str=$(printf '$%.2f' "$cost" 2>/dev/null || echo "\$$cost")
else
  cost_str="\$0.00"
fi

# --- Diretório atual (basename) ---
cwd=$(printf '%s' "$input" | grep -o '"current_dir":"[^"]*"' | head -1 | sed 's/"current_dir":"//;s/"//')
if [ -z "$cwd" ]; then
  cwd=$(printf '%s' "$input" | grep -o '"cwd":"[^"]*"' | head -1 | sed 's/"cwd":"//;s/"//')
fi
dir_name=$(basename "${cwd:-$(pwd)}")

# --- Git branch ---
branch=""
if [ -n "$cwd" ] && command -v git >/dev/null 2>&1; then
  branch=$(GIT_OPTIONAL_LOCKS=0 git -C "$cwd" symbolic-ref --short HEAD 2>/dev/null \
    || GIT_OPTIONAL_LOCKS=0 git -C "$cwd" rev-parse --short HEAD 2>/dev/null)
fi
if [ -n "$branch" ]; then
  branch_str="(${branch})"
else
  branch_str=""
fi

# --- Montar linha final ---
parts="${model_str} | ${tokens_colored} | ${cost_str} | ${dir_name}"
if [ -n "$branch_str" ]; then
  parts="${parts} ${branch_str}"
fi

printf '%b\n' "$parts"
