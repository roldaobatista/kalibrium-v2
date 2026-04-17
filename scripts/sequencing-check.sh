#!/usr/bin/env bash
# sequencing-check.sh — valida ordem Story × Epic antes de iniciar novo trabalho.
#
# Implementa R13 (ordem intra-epico) e R14 (ordem inter-epico, MVP apenas).
#
# Uso:
#   sequencing-check.sh --story ENN-SNN        # valida antes de /start-story
#   sequencing-check.sh --epic ENN             # valida antes de /decompose-stories
#
# Regras:
#   R13 Intra-epico: stories sem `dependencies: []` explicito seguem ordem
#       numerica. Story com `dependencies: []` (vazio) pode rodar em paralelo
#       apos suas pre-condicoes declaradas.
#   R14 Inter-epico (MVP): primeiro slice do epico N so pode iniciar se
#       epico N-1 tem todas as stories com status `merged` em
#       project-state.json[epics_status]. Aplica apenas aos 12 epicos MVP
#       (lista em docs/product/mvp-scope.md); epicos post-MVP (E13, E14)
#       sao isentos.
#
# Bypass:
#   KALIB_SKIP_SEQUENCE="<motivo>" pula a validacao e grava incidente em
#   docs/incidents/sequence-bypass-<timestamp>.md com o motivo e contexto.
#
# Exit codes:
#   0  OK — pode prosseguir
#   1  Story anterior no epico nao esta merged (R13)
#   2  Epico anterior nao esta fechado (R14)
#   3  Parametros invalidos
#   4  project-state.json ou arquivos esperados ausentes
#   5  Bypass autorizado via KALIB_SKIP_SEQUENCE (incidente gerado)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

MODE=""
TARGET=""

while [ $# -gt 0 ]; do
  case "$1" in
    --story) MODE="story"; TARGET="${2:-}"; shift 2;;
    --epic)  MODE="epic";  TARGET="${2:-}"; shift 2;;
    -h|--help)
      sed -n '2,30p' "$0"; exit 0;;
    *)
      echo "[sequencing-check FAIL] argumento desconhecido: $1" >&2
      exit 3;;
  esac
done

if [ -z "$MODE" ] || [ -z "$TARGET" ]; then
  echo "Uso: sequencing-check.sh --story ENN-SNN | --epic ENN" >&2
  exit 3
fi

STATE_FILE="project-state.json"
[ ! -f "$STATE_FILE" ] && { echo "[sequencing-check FAIL] $STATE_FILE ausente" >&2; exit 4; }

MVP_SCOPE="docs/product/mvp-scope.md"
ROADMAP="docs/product/roadmap.md"

say()  { echo "[sequencing-check] $*"; }
fail_r13() { echo "[sequencing-check BLOCK-R13] $*" >&2; }
fail_r14() { echo "[sequencing-check BLOCK-R14] $*" >&2; }

# Bypass autorizado (requer motivo nao-vazio)
if [ -n "${KALIB_SKIP_SEQUENCE:-}" ]; then
  TS="$(date -u +%Y-%m-%dT%H-%M-%SZ)"
  INCIDENT="docs/incidents/sequence-bypass-${TS}.md"
  mkdir -p docs/incidents
  {
    echo "# Bypass de sequenciamento — ${TS}"
    echo ""
    echo "**Motivo informado:** ${KALIB_SKIP_SEQUENCE}"
    echo "**Modo:** ${MODE}"
    echo "**Alvo:** ${TARGET}"
    echo "**Operador (git):** $(git config user.name) <$(git config user.email)>"
    echo "**Branch:** $(git rev-parse --abbrev-ref HEAD)"
    echo "**Commit:** $(git rev-parse --short HEAD)"
    echo ""
    echo "## Contexto"
    echo ""
    echo "Gate R13/R14 foi pulado explicitamente via KALIB_SKIP_SEQUENCE. Este"
    echo "bypass deve ser justificado tecnicamente (hotfix, correcao de tooling,"
    echo "reescopo autorizado) e referenciado no proximo /retrospective."
  } > "$INCIDENT"
  say "bypass registrado em $INCIDENT"
  exit 5
fi

# ---------------------------------------------------------------------------
# parse helpers (puro jq-free para evitar dependencia)
# ---------------------------------------------------------------------------
python3 - "$MODE" "$TARGET" "$STATE_FILE" "$MVP_SCOPE" "$ROADMAP" <<'PY'
import json
import os
import re
import sys
from pathlib import Path

mode, target, state_path, mvp_path, roadmap_path = sys.argv[1:6]

state = json.loads(Path(state_path).read_text(encoding="utf-8"))
epics_status = state.get("epics_status") or {}

def epic_stories(epic_code):
    """Retorna dict de stories do epico. None significa 'epico nao decomposto
    ainda' (diferente de {} que seria 'sem stories cadastradas')."""
    e = epics_status.get(epic_code)
    if not e:
        return None
    if "stories" not in e:
        return None
    return e.get("stories") or {}

def epic_done(epic_code):
    """True se o epico esta 100% concluido OU pausado intencionalmente.
    - status merged/completed => concluido
    - todas stories merged => concluido
    - status em KALIBRIUM_BYPASS_STATUSES => pausa autorizada (libera cadeia)"""
    status = epic_status(epic_code)
    if status in ("merged", "completed"):
        return True
    if status in KALIBRIUM_BYPASS_STATUSES:
        return True
    stories = epic_stories(epic_code)
    if not stories:
        return False
    return all(st == "merged" for st in stories.values())

def epic_status(epic_code):
    e = epics_status.get(epic_code)
    if not e:
        return None
    return e.get("status")

# Ordem canonica dos epicos MVP pos-ampliacao v3 (2026-04-17).
# Fonte: docs/product/roadmap.md §7 + mvp-scope §8 (decisao PM 2026-04-16):
#   "pausar E03 ate que E15 + E16 estejam prontos — E15/E16 sao foundational
#    para toda UI/sync pos-ampliacao, e E03 sera retomado com frontend novo".
# Por isso E03 fica DEPOIS de E15/E16 na ordem canonica pos-ampliacao.
# Epicos E13, E14 sao post-MVP (fora da lista).
KALIBRIUM_MVP_ORDER = [
    "E01", "E02",                      # Fundacao: backend API + auth
    "E15", "E16",                      # Foundational pos-ampliacao: PWA shell + sync engine
    "E03",                             # CRUD cliente (retomado com frontend novo apos E15/E16)
    "E04", "E05", "E06", "E07", "E08", # MVP original: fluxo/calibracao/NFSe/financeiro/relatorios
    "E09", "E10", "E11", "E12",        # MVP original: dashboard/planos/OS/notificacoes
    "E17", "E18", "E19", "E20",        # Ampliacao v1: UMC/caixa/estoque/CRM vendedor
    "E21", "E22", "E23",               # Ampliacao v2: compliance/SPC/revalidacao
    "E24", "E25",                      # Ampliacao v3: ISO 17025+ / reforma tributaria
]

# Status especiais que NAO bloqueiam (epico foi pausado/desviado por decisao do PM).
# Usado pelo epic_done() para tratar o epico como "liberado" na cadeia de ordem.
KALIBRIUM_BYPASS_STATUSES = {"paused-for-ampliation"}

def mvp_epics():
    """Lista codigos de epicos MVP na ordem canonica.
    Prioriza docs/product/mvp-scope.md se ele declarar uma lista explicita,
    senao usa KALIBRIUM_MVP_ORDER. Fallback legado: E01..E12."""
    try:
        text = Path(mvp_path).read_text(encoding="utf-8")
    except FileNotFoundError:
        return list(KALIBRIUM_MVP_ORDER)
    # Se o mvp-scope.md declarar "MVP ORDER:" ou "## Ordem canonica", respeita
    explicit = re.search(r"(?ms)^##\s+Ordem canonica.*?\n(.+?)(?:\n##|\Z)", text)
    if explicit:
        codes = re.findall(r"\bE(\d{2})\b", explicit.group(1))
        ordered = []
        seen = set()
        for c in codes:
            code = f"E{c}"
            if code not in seen:
                ordered.append(code); seen.add(code)
        if ordered:
            return ordered
    # Senao usa a ordem canonica hard-coded (aditiva: nao diminui do que ja existia)
    return list(KALIBRIUM_MVP_ORDER)

def previous_epic(code):
    """Retorna o epico anterior na ordem MVP canonica (nao a ordem numerica).
    Pos-ampliacao: apos E03 vem E15, depois E16, so entao E04. Epicos fora
    do MVP caem em fallback numerico."""
    order = mvp_epics()
    if code in order:
        idx = order.index(code)
        if idx == 0:
            return None
        return order[idx - 1]
    # Fallback numerico para epicos nao-MVP (E13, E14)
    num = int(code[1:])
    if num <= 1:
        return None
    return f"E{num - 1:02d}"

def story_number(story_code):
    """Extrai numero base da story, aceitando sufixo alfabetico opcional.
    Ex.: E03-S01 -> 1, E03-S01a -> 1, E03-S02b -> 2."""
    m = re.match(r"E\d{2}-S(\d+)[A-Za-z]?$", story_code)
    return int(m.group(1)) if m else None

def story_suffix(story_code):
    """Retorna sufixo alfabetico da story ou ''. Ex.: E03-S01a -> 'a'."""
    m = re.match(r"E\d{2}-S\d+([A-Za-z])?$", story_code)
    return (m.group(1) or "") if m else ""

def epic_of_story(story_code):
    return story_code.split("-")[0]

def all_previous_blocking_epics(code):
    """Retorna todos os epicos MVP anteriores a `code` na ordem canonica
    que NAO estao `done` (considerando bypass paused-for-ampliation).
    Vazio => cadeia anterior toda resolvida."""
    order = mvp_epics()
    if code not in order:
        return []
    idx = order.index(code)
    return [e for e in order[:idx] if not epic_done(e)]

def load_story_dependencies(story_code):
    """Le dependencies declaradas no frontmatter da story contract.
    Retorna None se nao declarado, [] se vazio explicito, lista se declarada."""
    epic = epic_of_story(story_code)
    path = Path("epics") / epic / "stories" / f"{story_code}.md"
    if not path.exists():
        return None
    text = path.read_text(encoding="utf-8")
    m = re.search(r"(?ms)^---\s*\n(.+?)\n---", text)
    if not m:
        return None
    front = m.group(1)
    dep_match = re.search(r"(?m)^dependencies\s*:\s*(.*)$", front)
    if not dep_match:
        return None
    raw = dep_match.group(1).strip()
    if raw in ("[]", "[ ]"):
        return []
    if raw.startswith("["):
        items = [x.strip().strip('"').strip("'") for x in raw.strip("[]").split(",")]
        return [i for i in items if i]
    # YAML block form (- item), sem dotall para nao engolir linha seguinte
    block = re.search(r"(?m)^dependencies\s*:\s*\n((?:[ \t]{2}-[^\n]+\n?)+)", front)
    if block:
        return [re.sub(r"^\s*-\s*", "", line).strip().strip('"').strip("'")
                for line in block.group(1).splitlines() if line.strip()]
    return None

def main():
    if mode == "story":
        story = target
        num = story_number(story)
        if num is None:
            print(f"[sequencing-check FAIL] codigo de story invalido: {story}", file=sys.stderr)
            sys.exit(3)
        epic = epic_of_story(story)

        # R14: se for primeira story do epico, validar TODA a cadeia anterior
        # (nao so o imediatamente anterior — bypass/paused pula o epico mas a
        # cadeia de dependencias continua valendo).
        if num == 1 and epic in mvp_epics():
            blocking = all_previous_blocking_epics(epic)
            if blocking:
                details = []
                for b in blocking:
                    st = epic_status(b) or "desconhecido"
                    stories = epic_stories(b)
                    if stories is None:
                        details.append(f"{b}(nao-decomposto/{st})")
                    else:
                        pending = [s for s, v in stories.items() if v != "merged"]
                        details.append(f"{b}({len(pending)}-pendentes)")
                print(f"[sequencing-check BLOCK-R14] {epic} bloqueado — epicos anteriores na ordem canonica pos-ampliacao ainda abertos: {', '.join(details)}", file=sys.stderr)
                sys.exit(2)

        # R13: stories anteriores do mesmo epico
        deps = load_story_dependencies(story)
        current_stories = epic_stories(epic) or {}

        if deps is None:
            # sem declaracao => regra padrao: todas stories numericamente anteriores
            required = [f"{epic}-S{i:02d}" for i in range(1, num)]
        elif deps == []:
            # paralelo permitido => nenhuma story obrigatoria
            required = []
        else:
            required = deps

        pending = []
        for req in required:
            st = current_stories.get(req)
            if st != "merged":
                pending.append(f"{req}({st or 'nao-registrada'})")

        if pending:
            print(f"[sequencing-check BLOCK-R13] pre-requisito(s) de {story} nao mergeado(s): {', '.join(pending)}", file=sys.stderr)
            sys.exit(1)

        print(f"[sequencing-check OK] {story} liberada (R13+R14)")
        sys.exit(0)

    if mode == "epic":
        epic = target
        if epic not in mvp_epics():
            print(f"[sequencing-check OK] {epic} nao e MVP, R14 nao se aplica")
            sys.exit(0)
        blocking = all_previous_blocking_epics(epic)
        if not blocking:
            print(f"[sequencing-check OK] {epic} liberado (R14)")
            sys.exit(0)
        details = []
        for b in blocking:
            st = epic_status(b) or "desconhecido"
            stories = epic_stories(b)
            if stories is None:
                details.append(f"{b}(nao-decomposto/{st})")
            else:
                pending = [s for s, v in stories.items() if v != "merged"]
                details.append(f"{b}({len(pending)}-pendentes)")
        print(f"[sequencing-check BLOCK-R14] {epic} bloqueado — epicos anteriores na ordem canonica ainda abertos: {', '.join(details)}", file=sys.stderr)
        sys.exit(2)

    print(f"[sequencing-check FAIL] modo desconhecido: {mode}", file=sys.stderr)
    sys.exit(3)

main()
PY
