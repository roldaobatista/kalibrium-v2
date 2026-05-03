#!/usr/bin/env bash
# SessionStart — ao abrir/retomar sessão, mostra estado do trabalho em pt-BR
# E ofertas de trabalho pronto pra maestra puxar (sem precisar Roldão pedir):
#   - problemas auto-capturados (testes/build falhando)
#   - ideias paradas há mais de 24h aguardando refino
#   - histórias paradas em aguardando aprovação há mais de 48h
#
# Tudo local, sem depender de email/Slack/Sentry. Substitui a "fábrica que
# anda sozinha" — quando Roldão chega, já tem trabalho refinado pra aprovar.

set -uo pipefail

agora_file="docs/backlog/AGORA.md"
agora_resumo=""
if [ -f "$agora_file" ]; then
  agora_resumo=$(grep -E '^(## |_\(vazio)' "$agora_file" 2>/dev/null | head -6 | sed 's/^## /• /; s/^_(/  ↳ (/' || echo "")
fi

# Contagens base
aguardando=$(find docs/backlog/historias/aguardando -maxdepth 1 -name "*.md" -type f 2>/dev/null | wc -l | tr -d ' ' || echo "0")
ativas=$(find docs/backlog/historias/ativas -maxdepth 1 -name "*.md" -type f 2>/dev/null | wc -l | tr -d ' ' || echo "0")
ideias_pend=$(find docs/backlog/ideias -maxdepth 1 -name "*.md" -type f 2>/dev/null | wc -l | tr -d ' ' || echo "0")
pending=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ' || echo "0")
ultima_feita=$(find docs/backlog/historias/feitas -maxdepth 1 -name "*.md" -type f 2>/dev/null | sort -r | head -1 | xargs -I {} basename {} .md 2>/dev/null || echo "")

# ── Ofertas de trabalho pronto pra maestra puxar ─────────────────────────

ofertas=""

# 1. Problemas auto-capturados (não refinados ainda)
auto_count=$(find docs/backlog/ideias -maxdepth 1 -name "auto-*.md" -type f 2>/dev/null | wc -l | tr -d ' ' || echo "0")
if [ "$auto_count" -gt 0 ]; then
  auto_lista=$(find docs/backlog/ideias -maxdepth 1 -name "auto-*.md" -type f 2>/dev/null | sort -r | head -3 | xargs -I {} basename {} .md 2>/dev/null | sed 's/^/    - /' || echo "")
  ofertas="${ofertas}
🔴 $auto_count problema(s) detectado(s) automaticamente sem análise:
$auto_lista
   → Ação automática: ler arquivo, traduzir em pt-BR pelo efeito visível, virar história e seguir. Só parar se exigir decisão de produto não coberta pelo PRD."
fi

# 2. Ideias paradas há mais de 24h (sem ser auto-*)
ideias_velhas=$(find docs/backlog/ideias -maxdepth 1 -name "*.md" -not -name "auto-*" -type f -mtime +1 2>/dev/null | head -3 | xargs -I {} basename {} .md 2>/dev/null | sed 's/^/    - /' || echo "")
if [ -n "$ideias_velhas" ]; then
  ofertas="${ofertas}

💡 Ideias paradas há mais de 24h aguardando refino:
$ideias_velhas
   → Ação automática: refinar e transformar em história. Só parar se exigir decisão de produto não coberta pelo PRD."
fi

# 3. Histórias paradas em aguardando há mais de 48h
hist_velhas=$(find docs/backlog/historias/aguardando -maxdepth 1 -name "*.md" -type f -mtime +2 2>/dev/null | head -3 | xargs -I {} basename {} .md 2>/dev/null | sed 's/^/    - /' || echo "")
if [ -n "$hist_velhas" ]; then
  ofertas="${ofertas}

📋 Histórias prontas há mais de 48h:
$hist_velhas
   → Ação automática: aprovar (se só traduzem REQ-XXX do PRD), mover pra ativas/ e executar. Só parar se envolver decisão de produto não documentada."
fi

# ── Saída ────────────────────────────────────────────────────────────────

cat <<EOF
📍 Estado do projeto Kalibrium V2

Histórias: $ativas em andamento · $aguardando aguardando aprovação · $ideias_pend ideias capturadas
Mudanças não salvas no código: $pending arquivo(s)
$( [ -n "$ultima_feita" ] && echo "Última história entregue: $ultima_feita" )

$agora_resumo
EOF

if [ -n "$ofertas" ]; then
  cat <<EOF

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🤖 Modo autônomo — frentes pra puxar e executar SEM perguntar:
$ofertas

Regra: Roldão deu autonomia total. Quando ele mandar mensagem genérica
("vamos continuar", "segue", "ok"), NÃO perguntar "qual desses?" / "abrir
qual?" / "quer que eu faça?". Escolher a frente mais prioritária (problemas
auto-capturados > histórias prontas > ideias paradas) e EXECUTAR direto,
reportando "fiz X, segui pra Y" no fim. Só parar nos bloqueios reais
listados no CLAUDE.md (decisão de produto não coberta, destrutivo, deploy).
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EOF
fi

cat <<EOF

Lembrete: comunicar com Roldão em pt-BR sem jargão. Antes de afirmar "feito", rodar /conferir.
EOF

exit 0
