#!/usr/bin/env bash
# next-slice.sh — verificador de pré-condições + orientação para /next-slice.
#
# G-03 do guide-backlog (auditoria PM 2026-04-12). Complementa a skill
# `.claude/skills/next-slice.md` que contém a lógica conversacional.
#
# Este script NÃO decide o próximo slice sozinho — apenas:
#   1. Verifica que os insumos necessários existem (PRD + mvp-scope, ou roadmap)
#   2. Identifica o modo (wizard inicial vs. consulta a roadmap existente)
#   3. Lista slices já executados (cruzando specs/ + telemetria) para o agente
#      principal saber o que pular
#   4. Imprime instruções claras do que o agente principal deve fazer em seguida
#
# O agente principal, guiado pela skill, faz a análise semântica (ler PRD,
# recomendar, etc.) e conversa com o PM. Este script é apenas o scaffolding.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

ROADMAP="docs/product/roadmap.md"
PRD="docs/product/PRD.md"
MVP_SCOPE="docs/product/mvp-scope.md"

say() { echo "[next-slice] $*"; }
fail() { echo "[next-slice FAIL] $*" >&2; exit 1; }

echo "================================================================"
echo "  /next-slice — recomendação do próximo slice"
echo "================================================================"
echo

# ---------- 1. Verifica pré-condições mínimas ----------
if [ ! -f "$PRD" ]; then
  fail "$PRD não existe. Não há fonte de produto para derivar próximo slice."
fi
if [ ! -f "$MVP_SCOPE" ]; then
  fail "$MVP_SCOPE não existe. Escopo do MVP precisa estar definido primeiro."
fi
say "PRD + mvp-scope encontrados"

# ---------- 2. Lista slices já executados (ativos + merged) ----------
# Cruza specs/*/ com telemetria pra saber o que já foi iniciado.
EXISTING_SLICES=""
if [ -d specs ]; then
  for s in specs/*/; do
    [ ! -d "$s" ] && continue
    nnn="$(basename "$s")"
    if ! echo "$nnn" | grep -qE '^[0-9]{3}$'; then continue; fi
    [ ! -f "${s}spec.md" ] && continue
    EXISTING_SLICES="${EXISTING_SLICES}${nnn} "
  done
fi
EXISTING_SLICES="${EXISTING_SLICES% }"

if [ -n "$EXISTING_SLICES" ]; then
  say "Slices já iniciados: $EXISTING_SLICES"
else
  say "Nenhum slice iniciado ainda — este vai ser o primeiro"
fi
echo

# ---------- 3. Determina modo ----------
if [ -f "$ROADMAP" ]; then
  MODE="consulta"
  echo "Modo: CONSULTA (roadmap.md existe, próximo slice é derivado dele)"
  echo
  echo "================================================================"
  echo "  INSTRUÇÕES PARA O AGENTE PRINCIPAL"
  echo "================================================================"
  echo
  echo "1. Leia $ROADMAP — contém lista ordenada de slices com:"
  echo "   - código (ex.: SEG-001, TEN-002, MET-003)"
  echo "   - título em PT-BR"
  echo "   - dependências (outros slices que devem vir antes)"
  echo "   - NNN sugerido (numeração do specs/NNN/)"
  echo
  echo "2. Cruze a lista com os slices já iniciados:"
  if [ -n "$EXISTING_SLICES" ]; then
    echo "   já iniciados: $EXISTING_SLICES"
  else
    echo "   já iniciados: nenhum"
  fi
  echo
  echo "3. Encontre o PRIMEIRO slice do roadmap que:"
  echo "   - NÃO está na lista de iniciados"
  echo "   - TEM todas as dependências satisfeitas (marcadas como concluídas"
  echo "     no roadmap OU já mergeadas conforme telemetria)"
  echo
  echo "4. Apresente ao PM em linguagem de produto (R12):"
  echo "   - Nome amigável do slice (do roadmap)"
  echo "   - O que o usuário final vai ver quando esse slice terminar"
  echo "   - Por que este é o próximo (dependências satisfeitas, ordem)"
  echo "   - Tamanho estimado (pequeno/médio/grande, do roadmap)"
  echo "   - Próximo passo: '/new-slice NNN \"título\"'"
  echo
  echo "5. Pergunte ao PM: [ ] Aceito, começa  [ ] Pula este, quero outro"
  echo
else
  MODE="wizard"
  echo "Modo: WIZARD (roadmap.md não existe — vamos construir a primeira versão)"
  echo
  echo "================================================================"
  echo "  INSTRUÇÕES PARA O AGENTE PRINCIPAL"
  echo "================================================================"
  echo
  echo "$ROADMAP não existe. Este é o primeiro /next-slice do projeto."
  echo "O agente principal deve construir o roadmap inicial."
  echo
  echo "1. LEIA (não resumir pro PM — use como base pra construir o roadmap):"
  echo "   - $MVP_SCOPE (módulos IN: TEN, MET, FLX, FIS, OPL, CMP, SEG)"
  echo "   - docs/product/personas.md (quem são os usuários finais)"
  echo "   - docs/product/journeys.md (jornadas críticas do MVP)"
  echo "   - Seção '## Critérios de Priorização Continua' do PRD"
  echo "   - Seção '§Decisões de Produto em Aberto' do PRD (ADRs que travam slices)"
  echo
  echo "2. CONSTRUA um roadmap ORDENADO de 8-12 slices cobrindo o MVP:"
  echo "   - Cada slice deve ser pequeno (1-3 dias de trabalho)"
  echo "   - Convenção de código: DOMAIN-NNN (ex.: SEG-001, TEN-002)"
  echo "   - Cada slice tem: código, título PT-BR, domínio, dependências,"
  echo "     ADRs bloqueantes (se houver), tamanho estimado, por que nessa ordem"
  echo "   - Começar por dependências hard (autenticação antes de cadastro de"
  echo "     cliente, tenant antes de certificado, etc.)"
  echo
  echo "3. ESCREVA $ROADMAP seguindo estrutura:"
  echo "   # Roadmap de slices — Kalibrium MVP"
  echo "   ## Convenções"
  echo "   ## Lista ordenada"
  echo "   ### 1. SEG-001 — Login com senha + 2FA"
  echo "      - NNN sugerido: 001"
  echo "      - Domínio: SEG"
  echo "      - Depende de: ADR-0004 (IdP strategy — fica bloqueado até aceitar)"
  echo "      - Tamanho: médio"
  echo "      - Por que primeiro: toda operação depende de ter usuário logado"
  echo "   ### 2. TEN-001 — Cadastro do laboratório (tenant root)"
  echo "      - ..."
  echo
  echo "4. APRESENTE o roadmap completo ao PM em linguagem de produto:"
  echo "   - Lista numerada com título em PT-BR, sem código técnico visível"
  echo "   - Para cada slice: 1 linha de 'o que o usuário vai ver'"
  echo "   - Destaque quais slices estão bloqueados por ADRs pendentes"
  echo
  echo "5. PERGUNTE:"
  echo "   [ ] Aceito o roadmap, começar pelo slice 1"
  echo "   [ ] Aceito mas trocar ordem — diga qual vem primeiro"
  echo "   [ ] Não aceito, refazer considerando: ___"
  echo
  echo "6. APÓS aprovação, INICIE o primeiro slice via /new-slice."
  echo
fi

echo "================================================================"
echo "  Modo detectado: $MODE"
echo "================================================================"
exit 0
