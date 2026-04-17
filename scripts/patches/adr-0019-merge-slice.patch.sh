
# =====================================================================
# ADR-0019 Mudanca 2 — validacao de referenced_artifacts no master-audit
# =====================================================================
# Bloqueia merge do slice quando master-audit.json nao tem bloco
# evidence.referenced_artifacts[] com todos os gates obrigatorios
# aplicaveis ao slice (verify, review, security-gate, audit-tests,
# functional-gate) E seus sha256 atuais batem com os arquivos.
#
# Protege contra master-audit desatualizado (race: arquivo de gate
# modificado entre master-audit ler e merge-slice rodar).
#
# Motivacao: fecha gap #5 da auditoria de fluxo 2026-04-16.
# Dual-LLM mitiga vies mas nao audita integridade do proprio master-audit.

# Variavel NNN ja definida pelo merge-slice.sh antes deste trecho.
MASTER_AUDIT="specs/${NNN}/master-audit.json"

if [ -f "$MASTER_AUDIT" ]; then
  python3 - "$MASTER_AUDIT" <<'PYEOF' || exit 1
import json
import sys
import hashlib
import os

master_audit_path = sys.argv[1]
slice_dir = os.path.dirname(master_audit_path)

with open(master_audit_path, encoding="utf-8") as f:
    master = json.load(f)

evidence = master.get("evidence", {})
ref_artifacts = evidence.get("referenced_artifacts", [])

if not ref_artifacts:
    print("❌ [ADR-0019 M2] master-audit.json sem evidence.referenced_artifacts[]", file=sys.stderr)
    print("   master-audit deve citar cada gate anterior com path + sha256 + read_at", file=sys.stderr)
    sys.exit(1)

# Mapear gates presentes
present = {a.get("gate"): a for a in ref_artifacts}

# Gates obrigatorios basicos
required = {"verify", "review", "security-gate", "audit-tests", "functional-gate"}
missing = required - set(present.keys())

if missing:
    print(f"❌ [ADR-0019 M2] master-audit faltando gates obrigatorios: {sorted(missing)}", file=sys.stderr)
    sys.exit(1)

# Validar sha256 de cada referenced_artifact
drift = []
for artifact in ref_artifacts:
    path = artifact.get("path")
    declared_hash = artifact.get("sha256")
    if not path or not declared_hash:
        drift.append(f"{artifact.get('gate', '?')}: path ou sha256 ausente")
        continue
    if not os.path.exists(path):
        drift.append(f"{artifact.get('gate')}: path {path} nao existe")
        continue
    with open(path, "rb") as f:
        actual_hash = hashlib.sha256(f.read()).hexdigest()
    if not actual_hash.startswith(declared_hash[:8]):  # aceita hash parcial ou completo
        drift.append(f"{artifact.get('gate')}: sha256 diverge (declarado={declared_hash[:16]}... atual={actual_hash[:16]}...)")

if drift:
    print("❌ [ADR-0019 M2] Drift detectado em referenced_artifacts:", file=sys.stderr)
    for d in drift:
        print(f"   - {d}", file=sys.stderr)
    print("", file=sys.stderr)
    print("   Master-audit precisa ser re-executado — arquivo de gate mudou apos leitura.", file=sys.stderr)
    sys.exit(1)

print(f"✓ [ADR-0019 M2] master-audit referencia {len(ref_artifacts)} gates com sha256 validos")
PYEOF
fi
