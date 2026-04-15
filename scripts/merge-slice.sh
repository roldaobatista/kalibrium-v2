#!/usr/bin/env bash
# merge-slice.sh — valida os gates obrigatórios + prepara/executa PR do slice.
# Fecha a cadeia verify→review→security→test-audit→functional→merge.
#
# Uso: bash scripts/merge-slice.sh NNN
#
# Exit codes:
#   0  merge preparado (PR criado OU, se permissão selada, roteiro PM impresso)
#   1  falha de pré-condição (gate divergente, harness drift, branch errado)
#   2  bypass detectado (reservado para futuras checagens)
#   3  permissão de push selada — PM deve executar em terminal externo

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

NNN="${1:-}"

say()  { echo "[merge-slice] $*"; }
fail() { echo "[merge-slice FAIL] $*" >&2; exit 1; }

if [ -z "$NNN" ]; then
  echo "Uso: merge-slice.sh NNN" >&2
  exit 1
fi
if ! echo "$NNN" | grep -qE '^[0-9]{3}$'; then
  echo "NNN deve ter 3 dígitos (ex.: 001)" >&2
  exit 1
fi

SLICE_DIR="specs/$NNN"
TELEMETRY=".claude/telemetry/slice-${NNN}.jsonl"

[ ! -d "$SLICE_DIR" ] && fail "slice $NNN não existe em $SLICE_DIR"

VJSON="$SLICE_DIR/verification.json"
RJSON="$SLICE_DIR/review.json"
SJSON="$SLICE_DIR/security-review.json"
TJSON="$SLICE_DIR/test-audit.json"
FJSON="$SLICE_DIR/functional-review.json"

[ ! -f "$VJSON" ] && fail "verification.json ausente — rode /verify-slice $NNN primeiro"
[ ! -f "$RJSON" ] && fail "review.json ausente — rode /review-pr $NNN primeiro (R11)"
[ ! -f "$SJSON" ] && fail "security-review.json ausente — rode /security-review $NNN primeiro"
[ ! -f "$TJSON" ] && fail "test-audit.json ausente — rode /test-audit $NNN primeiro"
[ ! -f "$FJSON" ] && fail "functional-review.json ausente — rode /functional-review $NNN primeiro"

# Valida os cinco gates, não só a dupla R11. Zero tolerance: qualquer finding,
# violation, anti-pattern ou AC funcional não atendido bloqueia o merge.
if ! GATE_CHECK_OUTPUT="$(SLICE_NNN="$NNN" python3 <<'PY' 2>&1
import json
import os
import sys
from pathlib import Path

nnn = os.environ["SLICE_NNN"]
slice_id = f"slice-{nnn}"
slice_dir = Path("specs") / nnn

gates = [
    ("verifier", "verification.json", ["violations"]),
    ("reviewer", "review.json", ["findings"]),
    ("security-reviewer", "security-review.json", ["findings"]),
    ("test-auditor", "test-audit.json", ["findings", "anti_patterns"]),
    (
        "functional-reviewer",
        "functional-review.json",
        ["ux_findings", "consistency_findings", "business_rule_findings"],
    ),
]

errors = []
for gate, filename, zero_array_keys in gates:
    path = slice_dir / filename
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except FileNotFoundError:
        errors.append(f"{gate}: {filename} ausente")
        continue
    except json.JSONDecodeError as exc:
        errors.append(f"{gate}: {filename} JSON invalido ({exc})")
        continue

    if data.get("slice_id") != slice_id:
        errors.append(f"{gate}: slice_id={data.get('slice_id')!r}, esperado {slice_id!r}")

    verdict = data.get("verdict")
    if verdict != "approved":
        errors.append(f"{gate}: verdict={verdict!r}, esperado 'approved'")

    for key in zero_array_keys:
        value = data.get(key)
        if value != []:
            count = len(value) if isinstance(value, list) else "campo ausente/invalido"
            errors.append(f"{gate}: {key} precisa estar vazio ({count})")

    if filename == "security-review.json":
        severity_summary = data.get("severity_summary", {})
        for severity in ("critical", "high", "medium", "low", "info"):
            if severity_summary.get(severity) != 0:
                errors.append(f"{gate}: severity_summary.{severity}={severity_summary.get(severity)!r}")
        failed_lgpd = [
            check.get("check", "<sem nome>")
            for check in data.get("lgpd_checks", [])
            if check.get("status") == "fail"
        ]
        if failed_lgpd:
            errors.append(f"{gate}: LGPD falhou em {', '.join(failed_lgpd)}")

    if filename == "test-audit.json":
        coverage = data.get("coverage_summary", {})
        total = coverage.get("acs_total")
        covered = coverage.get("acs_covered")
        if isinstance(total, int) and isinstance(covered, int) and covered != total:
            errors.append(f"{gate}: acs_covered={covered}, acs_total={total}")
        insufficient = [
            item.get("ac", "<sem AC>")
            for item in data.get("ac_coverage", [])
            if item.get("status") != "adequate"
        ]
        if insufficient:
            errors.append(f"{gate}: ACs sem cobertura adequada: {', '.join(insufficient)}")

    if filename == "functional-review.json":
        not_met = [
            item.get("ac", "<sem AC>")
            for item in data.get("ac_assessment", [])
            if item.get("met") is not True
        ]
        if not_met:
            errors.append(f"{gate}: ACs funcionais nao atendidos: {', '.join(not_met)}")

if errors:
    print("\n".join(errors))
    sys.exit(1)

for gate, filename, _ in gates:
    print(f"{gate}=approved ({slice_dir / filename})")
PY
)"; then
  echo "$GATE_CHECK_OUTPUT" >&2
  fail "gates obrigatorios incompletos ou rejeitados — merge abortado"
fi
while IFS= read -r line; do
  [ -n "$line" ] && say "$line"
done <<< "$GATE_CHECK_OUTPUT"

# Integridade do harness (itens 1.1 e 1.8 meta-audit)
say "validando integridade do harness..."
if ! bash "$SCRIPT_DIR/hooks/hooks-lock.sh" --check; then
  fail "harness drift detectado — merge abortado"
fi
if ! bash "$SCRIPT_DIR/hooks/settings-lock.sh" --check; then
  fail "settings.json drift detectado — merge abortado"
fi
say "harness íntegro"

# Branch e diff
BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '')"
[ -z "$BRANCH" ] && fail "não foi possível detectar branch atual"
if [ "$BRANCH" = "main" ]; then
  fail "você está em main — merge-slice precisa rodar na feature branch do slice"
fi

if ! git rev-parse --verify main >/dev/null 2>&1; then
  fail "branch main não existe — contexto inválido para merge"
fi
DIFF_COUNT="$(git diff --name-only main...HEAD 2>/dev/null | wc -l | tr -d ' \n\r')"
[ "${DIFF_COUNT:-0}" -eq 0 ] && fail "nenhum diff contra main — nada a mergear"

# ---------------------------------------------------------------------------
# PR body
# ---------------------------------------------------------------------------
PR_BODY="$SLICE_DIR/pr-body.md"
SLICE_TITLE="$(grep -m1 '^# ' "$SLICE_DIR/spec.md" 2>/dev/null | sed -E 's/^#\s*//')"
[ -z "$SLICE_TITLE" ] && SLICE_TITLE="slice-$NNN"

AC_COUNT="$(grep -cE '^\s*-\s*\*?\*?AC-[0-9]+' "$SLICE_DIR/spec.md" 2>/dev/null || echo 0)"

{
  echo "# $SLICE_TITLE"
  echo ""
  echo "Slice **$NNN** — pronto para aceitação do PM."
  echo ""
  echo "## Gates obrigatórios aprovados"
  echo ""
  echo "- Verifier (mecânico): **approved** → \`$VJSON\`"
  echo "- Reviewer (estrutural): **approved** → \`$RJSON\`"
  echo "- Security-reviewer (segurança/LGPD): **approved** → \`$SJSON\`"
  echo "- Test-auditor (cobertura/qualidade dos testes): **approved** → \`$TJSON\`"
  echo "- Functional-reviewer (produto/UX/ACs): **approved** → \`$FJSON\`"
  echo ""
  echo "Os gates obrigatórios foram concluídos com verdict approved e sem findings bloqueantes."
  echo ""
  echo "## Acceptance Criteria verificados"
  echo ""
  echo "$AC_COUNT AC(s) no spec — todos passaram no verifier. Detalhes mecânicos em \`$VJSON\`."
  echo ""
  echo "## Para o PM (linguagem de produto, R12)"
  echo ""
  echo "Este PR entrega o comportamento descrito em \`specs/$NNN/spec.md\`. Antes de aceitar o merge:"
  echo ""
  echo "1. Ler \`specs/$NNN/spec.md\` (contexto + ACs em português)."
  echo "2. Se houver UI: testar visualmente no ambiente de staging."
  echo "3. Aceitar (merge) ou comentar ajustes — o agente aplica na próxima iteração."
  echo ""
  echo "## Arquivos alterados"
  echo ""
  git diff --name-only main...HEAD 2>/dev/null | sed 's/^/- /'
  echo ""
  echo "---"
  echo "Gerado por \`/merge-slice $NNN\`."
} > "$PR_BODY"

say "PR body gerado em $PR_BODY"

# ---------------------------------------------------------------------------
# Telemetria
# ---------------------------------------------------------------------------
mkdir -p ".claude/telemetry"
touch "$TELEMETRY"
if ! bash "$SCRIPT_DIR/record-telemetry.sh" --verify-chain "$TELEMETRY" >/dev/null 2>&1; then
  fail "telemetria $TELEMETRY corrompida — abort"
fi
bash "$SCRIPT_DIR/record-telemetry.sh" \
  --event=merge \
  --slice="slice-${NNN}" \
  --verdict="approved" \
  --next-action="human_merge" \
  --reject-count="0" >/dev/null || fail "record-telemetry falhou"

# ---------------------------------------------------------------------------
# Detecta se push + gh estão autorizados no settings.json
# ---------------------------------------------------------------------------
PUSH_ALLOWED=0
if grep -q 'Bash(git push origin' .claude/settings.json 2>/dev/null && \
   grep -q 'Bash(gh pr create' .claude/settings.json 2>/dev/null; then
  PUSH_ALLOWED=1
fi

if [ "$PUSH_ALLOWED" -eq 0 ]; then
  cat >&2 <<'BANNER'

======================================================================
  MERGE BLOQUEADO — permissão de push ainda selada
======================================================================

  verifier, reviewer e gates finais aprovaram, mas `.claude/settings.json` ainda
  não libera `git push origin*` nem `gh pr create*`. Isso é o item
  P0-2 do meta-audit #2 (selado — só PM resolve).

  PM: abra docs/explanations/meta-audit-2-fixes.md §1 e execute o
  bloco "Liberar git push + gh pr" em terminal externo. Depois,
  volte ao Claude Code e rode /merge-slice NNN novamente.

  Enquanto isso, o PR body já está pronto no repo.
  Exit 3 (bloqueado por permissão selada — não é erro do slice).
======================================================================
BANNER
  exit 3
fi

# ---------------------------------------------------------------------------
# Caminho feliz: push + gh pr create
# ---------------------------------------------------------------------------
say "push autorizado — publicando branch $BRANCH"
git push -u origin "$BRANCH" || fail "git push falhou"

PR_URL="$(gh pr create \
  --base main \
  --head "$BRANCH" \
  --title "$SLICE_TITLE" \
  --body-file "$PR_BODY" 2>/dev/null | tail -1)" || fail "gh pr create falhou"

say "PR criado: $PR_URL"
echo "$PR_URL" > "$SLICE_DIR/pr-url.txt"

cat <<DONE

======================================================================
  MERGE-SLICE CONCLUÍDO — $NNN
======================================================================
  Verifier: approved
  Reviewer: approved
  Security: approved
  Test audit: approved
  Functional: approved
  PR:       $PR_URL
  Branch:   $BRANCH

  Próximo passo (humano PM):
    1. Abrir o PR no navegador
    2. Rodar testes visuais se houver UI
    3. Aceitar (merge) ou comentar ajustes
======================================================================
DONE

exit 0
