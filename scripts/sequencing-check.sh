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
    e = epics_status.get(epic_code)
    if not e:
        return None
    return e.get("stories") or {}

def epic_status(epic_code):
    e = epics_status.get(epic_code)
    if not e:
        return None
    return e.get("status")

def mvp_epics():
    """Lista codigos de epicos MVP do mvp-scope.md. Fallback para E01..E12."""
    try:
        text = Path(mvp_path).read_text(encoding="utf-8")
    except FileNotFoundError:
        return [f"E{i:02d}" for i in range(1, 13)]
    codes = re.findall(r"\bE(\d{2})\b", text)
    codes = sorted(set(codes))
    # filtrar apenas MVP (01..12 por padrao do Kalibrium)
    mvp = [f"E{c}" for c in codes if 1 <= int(c) <= 12]
    return mvp or [f"E{i:02d}" for i in range(1, 13)]

def previous_epic(code):
    num = int(code[1:])
    if num <= 1:
        return None
    return f"E{num - 1:02d}"

def story_number(story_code):
    m = re.match(r"E\d{2}-S(\d+)$", story_code)
    return int(m.group(1)) if m else None

def epic_of_story(story_code):
    return story_code.split("-")[0]

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

        # R14 primeiro: se for primeira story do epico, validar epico anterior
        if num == 1 and epic in mvp_epics():
            prev = previous_epic(epic)
            if prev and prev in mvp_epics():
                prev_stories = epic_stories(prev)
                if prev_stories is None:
                    print(f"[sequencing-check BLOCK-R14] epico anterior {prev} nao registrado em project-state.json[epics_status]", file=sys.stderr)
                    sys.exit(2)
                pending = [s for s, st in prev_stories.items() if st != "merged"]
                if pending:
                    print(f"[sequencing-check BLOCK-R14] epico {prev} tem {len(pending)} story(s) pendentes antes de iniciar {epic}: {', '.join(sorted(pending))}", file=sys.stderr)
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
        prev = previous_epic(epic)
        if prev is None or prev not in mvp_epics():
            print(f"[sequencing-check OK] {epic} e o primeiro MVP ou anterior e pre-MVP")
            sys.exit(0)
        prev_stories = epic_stories(prev)
        if prev_stories is None:
            print(f"[sequencing-check BLOCK-R14] epico anterior {prev} nao registrado em project-state.json[epics_status]", file=sys.stderr)
            sys.exit(2)
        pending = [s for s, st in prev_stories.items() if st != "merged"]
        if pending:
            print(f"[sequencing-check BLOCK-R14] epico {prev} tem pendencias: {', '.join(sorted(pending))}", file=sys.stderr)
            sys.exit(2)
        print(f"[sequencing-check OK] {epic} liberado (R14)")
        sys.exit(0)

    print(f"[sequencing-check FAIL] modo desconhecido: {mode}", file=sys.stderr)
    sys.exit(3)

main()
PY
