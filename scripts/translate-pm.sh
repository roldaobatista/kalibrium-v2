#!/usr/bin/env bash
# translate-pm.sh — tradução automática técnica → linguagem de produto (R12).
#
# B-010 do guide-backlog. Substitui o stub do explain-slice.sh (Dia 0, Fase 2)
# pela implementação que lê artefatos reais do slice e aplica regras de
# tradução mecânicas baseadas em:
#
#   - docs/product/glossary-pm.md (fonte canônica de traduções termo→PT-BR)
#   - categoria/severidade de findings (review.json)
#   - regras P/R violadas (verification.json violations)
#   - ACs declarados no spec.md + resultado em verification.json ac_checks
#
# Output: docs/explanations/slice-NNN.md — relatório PM-ready em PT-BR puro,
# sem jargão técnico, sem caminhos de arquivo, sem nomes de função, sem
# vocabulário proibido pelo R12.
#
# Fallback: se verification.json/review.json não existem (slice em andamento),
# gera relatório reduzido "slice em andamento — aguardando verificação".
#
# Nota arquitetural: primeiro corte é script shell puro (zero tokens).
# Se a qualidade mecânica mostrar limites em slices reais, promove-se para
# sub-agent `translator-pm` (item B-010.1 a abrir no backlog).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"
if [ -z "$NNN" ] || ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "Uso: translate-pm.sh NNN" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
GLOSSARY="docs/product/glossary-pm.md"
OUT="docs/explanations/slice-${NNN}.md"

[ ! -d "$SLICE_DIR" ] && { echo "[translate-pm FAIL] slice $NNN não existe em $SLICE_DIR" >&2; exit 1; }
[ ! -f "$SLICE_DIR/spec.md" ] && { echo "[translate-pm FAIL] spec.md ausente" >&2; exit 1; }

mkdir -p docs/explanations

# ============================================================================
# Helpers de extração JSON (parsing puro bash — sem dependência de jq)
# ============================================================================

# Extrai o valor string de um campo top-level: j_str FILE "verdict"
j_str() {
  local file="$1" key="$2"
  grep -o "\"$key\"[[:space:]]*:[[:space:]]*\"[^\"]*\"" "$file" 2>/dev/null \
    | head -1 \
    | sed -E "s/.*\"([^\"]*)\"$/\1/"
}

# ============================================================================
# Lookup no glossary-pm (regra R12)
#
# glossary-pm.md tem formato:
#   | termo técnico | tradução em PT-BR |
# Esta função recebe um termo e devolve a tradução, ou o próprio termo se
# não houver entrada (fallback seguro).
# ============================================================================

glossary_lookup() {
  local term="$1"
  [ ! -f "$GLOSSARY" ] && { echo "$term"; return; }

  # Match case-insensitive em termo na 1ª coluna da tabela markdown
  local translated
  translated="$(awk -F'|' -v t="$term" '
    BEGIN { IGNORECASE=1 }
    /^\|/ {
      gsub(/^[ \t]+|[ \t]+$/, "", $2)
      gsub(/^[ \t]+|[ \t]+$/, "", $3)
      if (tolower($2) == tolower(t)) {
        print $3
        exit
      }
    }
  ' "$GLOSSARY" 2>/dev/null)"

  if [ -n "$translated" ]; then
    echo "$translated"
  else
    echo "$term"
  fi
}

# ============================================================================
# 1. Título do slice + data
# ============================================================================
TITLE="$(grep -m1 '^# ' "$SLICE_DIR/spec.md" | sed 's/^# //' || echo "slice $NNN")"
DATE_UTC="$(date -u +%Y-%m-%d)"

# ============================================================================
# 2. Status do verifier + reviewer → status amigável PM
# ============================================================================
VJSON="$SLICE_DIR/verification.json"
RJSON="$SLICE_DIR/review.json"
SJSON="$SLICE_DIR/security-review.json"
TAJSON="$SLICE_DIR/test-audit.json"
FJSON="$SLICE_DIR/functional-review.json"
VERIF_STATUS="-"
REVIEW_STATUS="-"
SECURITY_STATUS="-"
TEST_AUDIT_STATUS="-"
FUNCTIONAL_STATUS="-"
[ -f "$VJSON" ] && VERIF_STATUS="$(j_str "$VJSON" verdict)"
[ -f "$RJSON" ] && REVIEW_STATUS="$(j_str "$RJSON" verdict)"
[ -f "$SJSON" ] && SECURITY_STATUS="$(j_str "$SJSON" verdict)"
[ -f "$TAJSON" ] && TEST_AUDIT_STATUS="$(j_str "$TAJSON" verdict)"
[ -f "$FJSON" ] && FUNCTIONAL_STATUS="$(j_str "$FJSON" verdict)"

if [ "$VERIF_STATUS" = "rejected" ] || [ "$REVIEW_STATUS" = "rejected" ] || [ "$SECURITY_STATUS" = "rejected" ] || [ "$TEST_AUDIT_STATUS" = "rejected" ] || [ "$FUNCTIONAL_STATUS" = "rejected" ]; then
  STATUS_FRIENDLY="⚠ precisa da sua decisão"
elif [ "$VERIF_STATUS" = "approved" ] && [ "$REVIEW_STATUS" = "approved" ] && [ "$SECURITY_STATUS" = "approved" ] && [ "$TEST_AUDIT_STATUS" = "approved" ] && [ "$FUNCTIONAL_STATUS" = "approved" ]; then
  STATUS_FRIENDLY="✓ pronto para usar"
elif [ "$VERIF_STATUS" = "approved" ] && [ "$REVIEW_STATUS" = "approved" ]; then
  STATUS_FRIENDLY="revisão aprovada; aguardando gates finais"
elif [ "$VERIF_STATUS" = "approved" ] && [ "$REVIEW_STATUS" = "-" ]; then
  STATUS_FRIENDLY="verificação aprovada; aguardando revisão"
elif [ "$VERIF_STATUS" = "-" ] && [ "$REVIEW_STATUS" = "-" ]; then
  STATUS_FRIENDLY="em andamento (aguardando verificação)"
else
  STATUS_FRIENDLY="em andamento"
fi

# ============================================================================
# 3. Extrai ACs do spec.md
#
# Formatos esperados:
#   ### AC-001 — Título do AC
#   - **AC-001:** Dado/quando/então...
# A linha seguinte (dado/quando/então) é o comportamento visível.
# ============================================================================
AC_LIST_FILE="$(mktemp)"
grep -E '(^### AC-[0-9]{3}|^[[:space:]]*-[[:space:]]*\*\*AC-[0-9]{3}:?\*\*)' "$SLICE_DIR/spec.md" > "$AC_LIST_FILE" 2>/dev/null || true

# ============================================================================
# 4. Contagem de ac_checks do verification.json (quantos pass / fail)
# ============================================================================
AC_PASS_COUNT=0
AC_FAIL_COUNT=0
if [ -f "$VJSON" ]; then
  AC_PASS_COUNT="$(grep -c '"status"[[:space:]]*:[[:space:]]*"pass"' "$VJSON" 2>/dev/null || true)"
  AC_FAIL_COUNT="$(grep -c '"status"[[:space:]]*:[[:space:]]*"fail"' "$VJSON" 2>/dev/null || true)"
  AC_PASS_COUNT="${AC_PASS_COUNT:-0}"
  AC_FAIL_COUNT="${AC_FAIL_COUNT:-0}"
fi

ac_title_from_line() {
  echo "$1" | sed -E \
    -e 's/^### AC-[0-9]{3}[[:space:]]*[—-][[:space:]]*//' \
    -e 's/^[[:space:]]*-[[:space:]]*\*\*AC-[0-9]{3}:?\*\*[[:space:]]*//'
}

# ============================================================================
# 5. Traduz findings do review.json para bullets em PT-BR
#
# Categorias esperadas (baseado em review.schema.json):
#   duplicated_logic | security | naming | glossary_deviance | adr_deviance
#   performance | test_quality | complexity
# Severidades: blocker | major | minor | nit
#
# Estratégia: mapa fixo category→frase base; severidade vira prefixo visual;
# message técnica é reformulada removendo vocabulário proibido.
# ============================================================================

translate_finding_category() {
  case "$1" in
    duplicated_logic)  echo "código repetido que precisa ser limpado" ;;
    security)          echo "problema de segurança" ;;
    naming)            echo "nome confuso em uma parte da funcionalidade" ;;
    glossary_deviance) echo "termo usado fora do dicionário de produto" ;;
    adr_deviance)      echo "saída do caminho decidido anteriormente" ;;
    performance)       echo "parte que pode ficar lenta" ;;
    test_quality)      echo "teste fraco ou sem cobertura do cenário" ;;
    complexity)        echo "parte complicada demais para manter" ;;
    *)                 echo "problema estrutural" ;;
  esac
}

translate_severity_prefix() {
  case "$1" in
    blocker) echo "🛑 IMPEDITIVO" ;;
    major)   echo "⚠ IMPORTANTE" ;;
    minor)   echo "• MENOR" ;;
    nit)     echo "· POLIMENTO" ;;
    *)       echo "•" ;;
  esac
}

REVIEW_BULLETS_FILE="$(mktemp)"
if [ -f "$RJSON" ]; then
  # Findings são blocos JSON { "severity": ..., "category": ..., ... }
  # Parsing linha-a-linha: assume um finding por linha OR multi-line.
  # Para cobrir ambos, passamos por python-like extraction com awk.
  #
  # Simplificação aceitável: extrai pares severity+category+message em ordem.
  # Se o arquivo tiver findings desalinhados, fallback conservador.

  awk '
    BEGIN { RS="}"; FS="," }
    /"severity"/ {
      sev=""; cat=""; msg=""
      for (i=1; i<=NF; i++) {
        if ($i ~ /"severity"/) {
          gsub(/.*"severity"[[:space:]]*:[[:space:]]*"/, "", $i)
          gsub(/".*/, "", $i)
          sev=$i
        }
        if ($i ~ /"category"/) {
          gsub(/.*"category"[[:space:]]*:[[:space:]]*"/, "", $i)
          gsub(/".*/, "", $i)
          cat=$i
        }
        if ($i ~ /"message"/) {
          gsub(/.*"message"[[:space:]]*:[[:space:]]*"/, "", $i)
          gsub(/".*/, "", $i)
          msg=$i
        }
      }
      if (sev != "" && cat != "") {
        print sev "|" cat "|" msg
      }
    }
  ' "$RJSON" 2>/dev/null | while IFS='|' read -r sev cat msg; do
    prefix="$(translate_severity_prefix "$sev")"
    base="$(translate_finding_category "$cat")"
    # Omitir message técnica se tiver vocabulário proibido óbvio
    if [ -n "$msg" ] && ! echo "$msg" | grep -qiE 'function|class|method|endpoint|refactor|import|async|callback|commit|branch|merge|SQL|query|JOIN|transaction'; then
      echo "- **${prefix}:** ${base} — ${msg}"
    else
      echo "- **${prefix}:** ${base}"
    fi
  done > "$REVIEW_BULLETS_FILE"
fi

# ============================================================================
# 6. Traduz violations do verification.json (rule P/R + reason)
# ============================================================================
translate_rule() {
  case "$1" in
    P1) echo "verificação automática foi pulada" ;;
    P2) echo "parte da funcionalidade ficou sem teste" ;;
    P3) echo "verificação rodou no lugar errado" ;;
    P4) echo "verificação não rodou depois da mudança" ;;
    P5) echo "havia mais de uma fonte de instrução no repositório" ;;
    P6) echo "um registro de mudança ficou misturado" ;;
    P7) echo "algo foi dado como pronto sem evidência" ;;
    P8) echo "teste foi pulado ou rodado fora de ordem" ;;
    P9) echo "uma trava de segurança do harness foi contornada" ;;
    R1) echo "arquivo proibido apareceu no repositório" ;;
    R2) echo "outra ferramenta de IA tocou o código em paralelo" ;;
    R3) echo "verificação leu arquivos que não devia" ;;
    R4) echo "resultado da verificação veio fora do formato esperado" ;;
    R5) echo "um registro de mudança ficou sem autor válido" ;;
    R6) echo "verificação falhou seis vezes seguidas — precisa da sua decisão" ;;
    R7) echo "arquivo de referência foi tratado como instrução" ;;
    R8) echo "custo da verificação passou do limite previsto" ;;
    R9) echo "uma trava foi contornada" ;;
    R10) echo "tecnologia mudou sem registro de decisão" ;;
    R11) echo "revisor estrutural discordou do verificador" ;;
    R12) echo "saída do harness usou vocabulário técnico demais" ;;
    *) echo "regra do harness violada ($1)" ;;
  esac
}

VIOLATION_BULLETS_FILE="$(mktemp)"
if [ -f "$VJSON" ]; then
  awk '
    BEGIN { RS="}"; FS="," }
    /"rule"/ {
      rule=""; reason=""
      for (i=1; i<=NF; i++) {
        if ($i ~ /"rule"/) {
          gsub(/.*"rule"[[:space:]]*:[[:space:]]*"/, "", $i)
          gsub(/".*/, "", $i)
          rule=$i
        }
        if ($i ~ /"reason"/) {
          gsub(/.*"reason"[[:space:]]*:[[:space:]]*"/, "", $i)
          gsub(/".*/, "", $i)
          reason=$i
        }
      }
      if (rule != "") print rule "|" reason
    }
  ' "$VJSON" 2>/dev/null | while IFS='|' read -r rule reason; do
    # Ignora entries sem rule válido (pode pegar ac_checks.rule etc)
    if ! echo "$rule" | grep -qE '^[PR][0-9]+$'; then continue; fi
    base="$(translate_rule "$rule")"
    echo "- ${base}"
  done > "$VIOLATION_BULLETS_FILE"
fi

# ============================================================================
# 7. Seção "O que funcionou" — se approved, listar ACs que passaram em PT-BR
# ============================================================================
WORKING_BULLETS=""
if [ "$VERIF_STATUS" = "approved" ]; then
  while IFS= read -r ac_line; do
    # Extrai "AC-NNN — Título" e vira "✓ Título"
    title_part="$(ac_title_from_line "$ac_line")"
    [ -n "$title_part" ] && WORKING_BULLETS="${WORKING_BULLETS}- ✓ ${title_part}"$'\n'
  done < "$AC_LIST_FILE"
fi

# ============================================================================
# 8. Seção "Fora de escopo" — copia do spec.md
# ============================================================================
OUT_OF_SCOPE=""
if grep -q '^## Fora de escopo' "$SLICE_DIR/spec.md" 2>/dev/null; then
  OUT_OF_SCOPE="$(awk '/^## Fora de escopo/{flag=1; next} /^## /{flag=0} flag' "$SLICE_DIR/spec.md" | sed '/^$/d')"
fi

# ============================================================================
# 9. Monta relatório final
# ============================================================================
{
  echo "# ${TITLE}"
  echo
  echo "**Status:** ${STATUS_FRIENDLY}"
  echo "**Data:** ${DATE_UTC}"
  echo "**Slice:** ${NNN}"
  echo
  echo "---"
  echo
  echo "## O que foi feito"
  echo
  if [ -s "$AC_LIST_FILE" ]; then
    echo "Esta entrega cobre os seguintes critérios:"
    echo
    while IFS= read -r ac_line; do
      ac_id="$(echo "$ac_line" | grep -oE 'AC-[0-9]{3}')"
      ac_title="$(ac_title_from_line "$ac_line")"
      echo "- **${ac_id}** — ${ac_title}"
    done < "$AC_LIST_FILE"
  else
    echo "_Sem critérios declarados no spec ainda._"
  fi
  echo

  echo "## O que o usuário final vai ver"
  echo
  if [ -s "$AC_LIST_FILE" ]; then
    # Reutiliza títulos dos ACs como proxy de funcionalidades visíveis
    while IFS= read -r ac_line; do
      ac_title="$(ac_title_from_line "$ac_line")"
      echo "- ${ac_title}"
    done < "$AC_LIST_FILE"
  else
    echo "_Nada visível ainda — slice em estágio inicial._"
  fi
  echo

  echo "## O que funcionou"
  echo
  if [ -n "$WORKING_BULLETS" ]; then
    printf '%s' "$WORKING_BULLETS"
  elif [ "$VERIF_STATUS" = "-" ]; then
    echo "_Ainda não passou pela verificação automática._"
  else
    echo "_A verificação encontrou problemas (ver abaixo)._"
  fi
  echo

  if [ -s "$VIOLATION_BULLETS_FILE" ] || [ -s "$REVIEW_BULLETS_FILE" ]; then
    echo "## O que precisa de atenção"
    echo
    if [ -s "$VIOLATION_BULLETS_FILE" ]; then
      echo "**Encontrados na verificação automática:**"
      echo
      cat "$VIOLATION_BULLETS_FILE"
      echo
    fi
    if [ -s "$REVIEW_BULLETS_FILE" ]; then
      echo "**Encontrados na revisão estrutural:**"
      echo
      cat "$REVIEW_BULLETS_FILE"
      echo
    fi
  fi

  if [ -n "$OUT_OF_SCOPE" ]; then
    echo "## O que NÃO está neste slice (fica pra depois)"
    echo
    echo "$OUT_OF_SCOPE"
    echo
  fi

  if [ "$STATUS_FRIENDLY" = "⚠ precisa da sua decisão" ]; then
    echo "## Sua decisão é necessária"
    echo
    echo "A entrega não ficou pronta nesta tentativa. Os problemas acima foram"
    echo "encontrados por uma verificação automática — não é opinião minha,"
    echo "é resultado mecânico."
    echo
    echo "**Opções:**"
    echo
    echo "- [ ] **Pedir nova tentativa** — o agente implementador corrige os problemas e tenta de novo"
    echo "- [ ] **Reescopar** — o slice é grande demais; dividir em pedaços menores"
    echo "- [ ] **Pausar** — prefiro discutir antes de decidir"
    echo
  fi

  echo "## Próximo passo"
  echo
  case "$STATUS_FRIENDLY" in
    "✓ pronto para usar")
      echo "Você pode testar visualmente a funcionalidade nova. Se tudo estiver como esperado, aprove o merge."
      ;;
    "⚠ precisa da sua decisão")
      echo "Marque uma opção acima e me avise. Não vou continuar sem sua decisão."
      ;;
    "verificação aprovada; aguardando revisão")
      echo "Seguir para a revisão estrutural independente antes dos próximos gates."
      ;;
    "revisão aprovada; aguardando gates finais")
      echo "Seguir para as revisões de segurança, testes e funcionalidade antes de qualquer merge."
      ;;
    *)
      echo "A entrega ainda está em andamento. Volte aqui quando a verificação terminar."
      ;;
  esac
  echo

  echo "---"
  echo
  echo "<details>"
  echo "<summary>Detalhes técnicos (não precisa abrir)</summary>"
  echo
  echo "- **Verifier verdict:** ${VERIF_STATUS}"
  echo "- **Reviewer verdict:** ${REVIEW_STATUS}"
  echo "- **Security verdict:** ${SECURITY_STATUS}"
  echo "- **Test audit verdict:** ${TEST_AUDIT_STATUS}"
  echo "- **Functional verdict:** ${FUNCTIONAL_STATUS}"
  echo "- **ACs pass/fail:** ${AC_PASS_COUNT} / ${AC_FAIL_COUNT}"
  echo "- **Artefatos:**"
  echo "    - \`${SLICE_DIR}/spec.md\`"
  [ -f "$VJSON" ] && echo "    - \`${VJSON}\`"
  [ -f "$RJSON" ] && echo "    - \`${RJSON}\`"
  [ -f "$SJSON" ] && echo "    - \`${SJSON}\`"
  [ -f "$TAJSON" ] && echo "    - \`${TAJSON}\`"
  [ -f "$FJSON" ] && echo "    - \`${FJSON}\`"
  echo
  echo "Tradução gerada automaticamente por \`scripts/translate-pm.sh\` (B-010)."
  echo
  echo "</details>"
} > "$OUT"

# ============================================================================
# Cleanup
# ============================================================================
rm -f "$AC_LIST_FILE" "$REVIEW_BULLETS_FILE" "$VIOLATION_BULLETS_FILE"

echo "[translate-pm] relatório gerado em $OUT"
echo "  Status: $STATUS_FRIENDLY"
