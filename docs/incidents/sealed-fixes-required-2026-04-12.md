# Correções Manuais Necessárias (Arquivos Selados)

**Data:** 2026-04-12
**Origem:** Master Independent Audit — docs/audits/master-independent-audit-2026-04-12.md
**Operador:** PM (em terminal externo, fora do Claude Code)

---

## F2 — Isolamento dos 3 gates novos em `verifier-sandbox.sh`

**Problema:** `scripts/hooks/verifier-sandbox.sh` só implementa sandbox para `verifier` e `reviewer`. Os agentes `security-reviewer`, `test-auditor` e `functional-reviewer` passam sem restrição de leitura.

**Correção necessária:** Adicionar cases no `verifier-sandbox.sh` (linha ~70) para os 3 agentes:

```bash
# Substituir:
case "$AGENT" in
  verifier|reviewer) : ;;  # continua para checks específicos abaixo

# Por:
case "$AGENT" in
  verifier|reviewer|security-reviewer|test-auditor|functional-reviewer) : ;;
```

E na seção de definição do sandbox dir (linha ~86), expandir:

```bash
# Substituir:
if [ "$AGENT" = "verifier" ]; then
  SANDBOX_DIR="verification-input"
else
  SANDBOX_DIR="review-input"
fi

# Por:
case "$AGENT" in
  verifier)            SANDBOX_DIR="verification-input" ;;
  reviewer)            SANDBOX_DIR="review-input" ;;
  security-reviewer)   SANDBOX_DIR="security-review-input" ;;
  test-auditor)        SANDBOX_DIR="test-audit-input" ;;
  functional-reviewer) SANDBOX_DIR="functional-review-input" ;;
esac
```

**Nota:** As skills de cada gate (`/security-review`, `/test-audit`, `/functional-review`) precisarão montar os input packages nos diretórios correspondentes antes do spawn, assim como `/verify-slice` monta `verification-input/`.

### Procedimento

```bash
# 1. Sair do Claude Code
# 2. Em terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2
# 3. Editar:
vim scripts/hooks/verifier-sandbox.sh
# 4. Aplicar as mudanças acima
# 5. Relock:
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# 6. Commit:
git add scripts/hooks/verifier-sandbox.sh scripts/hooks/MANIFEST.sha256 .claude/settings.json.sha256 docs/incidents/harness-relock-*.md
git commit -m "fix(harness): F2 implementa isolamento para security-reviewer, test-auditor, functional-reviewer"
```

---

## F6 — Telemetria com linhas históricas pré-schema

**Problema:** Linhas 1-10 de `.claude/telemetry/meta.jsonl` foram escritas antes do schema v1.0.0 e não têm `prev_hash` nem `schema_version`. A chain de integridade está quebrada nesse trecho.

**Opção A (recomendada):** Adicionar header de migração no topo do arquivo:

```json
{"event":"migration","timestamp":"2026-04-12T23:00:00Z","schema_version":"1.0.0","prev_hash":"GENESIS","hash":"<sha256 da linha>","note":"Linhas 1-10 pre-schema — migradas retroativamente para conformidade"}
```

E adicionar `schema_version` e `prev_hash` a cada uma das 10 linhas antigas.

**Opção B (mais simples):** Documentar que linhas 1-10 são pré-schema e excluídas da validação de chain. Adicionar flag no `record-telemetry.sh --verify-chain` para ignorar linhas sem `schema_version`.

### Procedimento (Opção B)

```bash
# 1. Sair do Claude Code
# 2. Em terminal externo:
cd /c/PROJETOS/saas/kalibrium-v2
# 3. Editar record-telemetry.sh para skip de linhas sem schema_version na verificação
vim scripts/hooks/record-telemetry.sh  # se for hook
# OU
vim scripts/record-telemetry.sh         # se for script auxiliar
# 4. Relock:
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# 5. Commit
```

---

## Checklist

- [ ] F2: Editar `verifier-sandbox.sh` + relock + commit
- [ ] F6: Escolher opção A ou B + aplicar + relock + commit
- [ ] Rodar `bash scripts/smoke-test-hooks.sh` após cada relock para confirmar 75/75
