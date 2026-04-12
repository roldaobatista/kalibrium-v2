#!/usr/bin/env bash
# start.sh — skill de onboarding Dia 1 para o PM.
#
# G-01 do guide-backlog (auditoria PM 2026-04-12). Resolve o gap
# "PM abre o Claude Code pela primeira vez e não sabe o que fazer".
#
# O que faz:
#   1. Mensagem de boas-vindas em PT-BR
#   2. Chama where-am-i.sh para mostrar estado atual (reuso G-10)
#   3. Lista decisões iniciais prioritárias (ADRs pendentes)
#   4. Menu de próximos passos possíveis
#   5. Nenhuma ação destrutiva — só leitura e orientação

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

# ============================================================================
# Cabeçalho
# ============================================================================
cat <<'HEADER'

================================================================
  /start — Bem-vindo ao Kalibrium V2
================================================================

Esta skill te orienta no primeiro uso ou sempre que você abrir o
projeto e não souber o que fazer. Tudo em PT-BR, linguagem de produto.

HEADER

# ============================================================================
# Seção 1 — Estado atual (reusa G-10)
# ============================================================================
if [ -x "$SCRIPT_DIR/where-am-i.sh" ] || [ -f "$SCRIPT_DIR/where-am-i.sh" ]; then
  bash "$SCRIPT_DIR/where-am-i.sh"
  echo
else
  echo "(where-am-i.sh não encontrado — estado atual indisponível)"
  echo
fi

# ============================================================================
# Seção 2 — Decisões pendentes (ADRs)
# ============================================================================
echo "================================================================"
echo "  DECISÕES DE PRODUTO PENDENTES"
echo "================================================================"
echo

# Conta ADRs aceitos e não-aceitos
ADR_DIR="docs/adr"
ACCEPTED_COUNT=0
PENDING_COUNT=0
PENDING_LIST=""

if [ -d "$ADR_DIR" ]; then
  for adr in "$ADR_DIR"/*.md; do
    [ ! -f "$adr" ] && continue

    # Pula template
    case "$(basename "$adr")" in
      0000-template.md) continue ;;
    esac

    # Verifica status. Formato real nos ADRs do Kalibrium: "**Status:** accepted"
    # (asteriscos de fechamento vêm depois do colon, não antes).
    # Aceita também "Status: accepted" (sem bold) e variantes PT-BR.
    if grep -qiE '^(\*\*)?status(\*\*)?:[[:space:]]*(\*\*)?[[:space:]]*(accepted|aceito|aprovad[oa])' "$adr" 2>/dev/null; then
      ACCEPTED_COUNT=$((ACCEPTED_COUNT+1))
    elif grep -qiE '^(\*\*)?status(\*\*)?:[[:space:]]*(\*\*)?[[:space:]]*(draft|proposed|proposto|rascunho)' "$adr" 2>/dev/null; then
      PENDING_COUNT=$((PENDING_COUNT+1))
      # Extrai título
      title="$(grep -m1 '^#[[:space:]]' "$adr" | sed 's/^#[[:space:]]*//')"
      PENDING_LIST="${PENDING_LIST}  • $(basename "$adr" .md) — ${title}"$'\n'
    fi
  done
fi

echo "Decisões já tomadas: $ACCEPTED_COUNT"
echo "Decisões pendentes: $PENDING_COUNT"
if [ -n "$PENDING_LIST" ]; then
  echo
  echo "Pendentes:"
  printf '%s' "$PENDING_LIST"
fi
echo

# ============================================================================
# Seção 3 — Menu de próximos passos
# ============================================================================
cat <<'MENU'
================================================================
  O QUE VOCÊ PODE FAZER AGORA
================================================================

Cada opção abaixo é um comando que você pode digitar. Peça a opção
que fizer mais sentido pro seu momento — eu te guio a partir daí.

📋 QUERO ENTENDER ONDE ESTOU
  /where-am-i               Ver estado atual de todos os slices
  /guide-check              Auditar integridade do harness

🚀 QUERO COMEÇAR ALGO NOVO
  /next-slice               Me diga qual é o próximo slice pra fazer
                            (recomendado se você não sabe por onde começar)
  /new-slice NNN "título"   Criar um slice específico com título em PT-BR
                            (use se já sabe exatamente o que quer)

🏗️ QUERO DECIDIR UMA TECNOLOGIA
  /decide-stack             Escolher a base tecnológica (se ainda não fez)
  /adr NNNN "título"        Registrar uma decisão arquitetural específica

🔍 JÁ TENHO UM SLICE EM ANDAMENTO
  /verify-slice NNN         Validar que o slice cumpre os critérios
  /review-pr NNN            Segunda validação (revisão estrutural)
  /merge-slice NNN          Finalizar e integrar o slice (se ambos aprovaram)
  /explain-slice NNN        Ver relatório em linguagem de produto de qualquer slice

📊 FECHAMENTO DE CICLO
  /slice-report NNN         Gerar relatório numérico de um slice
  /retrospective NNN        Refletir o que funcionou / não funcionou

MENU

# ============================================================================
# Seção 4 — Dica do dia
# ============================================================================
echo "================================================================"
echo "  DICA"
echo "================================================================"
echo

if [ ! -d specs ] || [ -z "$(ls -A specs 2>/dev/null)" ]; then
  cat <<'DICA_PRIMEIRO'
Ainda não há slice nenhum criado. Se você está começando agora,
a melhor primeira ação é:

  → /next-slice

Eu vou ler o PRD e te sugerir qual é o primeiro slice fazer, com
justificativa clara do porquê. Você aceita ou recusa.

Se você já sabe por onde quer começar, pode usar direto:

  → /new-slice 001 "título do slice"

DICA_PRIMEIRO
else
  cat <<'DICA_CONTINUAR'
Você já tem slice(s) em andamento (veja acima). Próxima ação recomendada:

  → /where-am-i              (pra confirmar estado de cada um)
  → depois rode o comando apontado no "Próximo passo" de cada slice ativo

Se quiser começar um slice novo mesmo assim:

  → /next-slice              (recomendação automática do próximo)

DICA_CONTINUAR
fi

echo "================================================================"
echo
exit 0
