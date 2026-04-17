# Procedimento de relock do harness — 2026-04-16

## Contexto

O harness v3 foi alinhado com protocolo operacional v1.2.2 (agents/skills/CLAUDE.md atualizados). Um hook selado precisa de atualização porque valida nomes de agente por lista estática que ainda contem nomes v2.

## Bloqueador

`scripts/hooks/verifier-sandbox.sh` enforça isolamento R3/R11 comparando `CLAUDE_AGENT_NAME` contra lista estatica:

- Atual (v2): `verifier | reviewer | security-reviewer | test-auditor | functional-reviewer`
- Esperado (v3): nomes canonicos por agent (ou agent+mode)

Sem atualizacao, todo gate v3 sera bloqueado pelo fail-closed do hook (linha 74-77). Pipeline trava no primeiro slice real.

## O que precisa mudar

### Arquivo 1: `scripts/hooks/verifier-sandbox.sh`

**Linhas 74-77 e 90-94** — atualizar o case statement para reconhecer agentes v3 por `CLAUDE_AGENT_NAME` + `CLAUDE_AGENT_MODE`.

Proposta de patch (fornecida como referencia — o PM deve revisar):

```bash
# Antes (linha 74):
case "$AGENT" in
  verifier|reviewer|security-reviewer|test-auditor|functional-reviewer) : ;;
  # ...
esac

# Depois (v3 — usar AGENT + MODE para distinguir):
AGENT_MODE="${CLAUDE_AGENT_MODE:-}"
AGENT_KEY="${AGENT}:${AGENT_MODE}"

case "$AGENT_KEY" in
  qa-expert:verify|\
  architecture-expert:code-review|\
  security-expert:security-gate|\
  qa-expert:audit-tests|\
  product-expert:functional-gate|\
  data-expert:data-gate|\
  observability-expert:observability-gate|\
  integration-expert:integration-gate|\
  governance:master-audit|\
  qa-expert:audit-spec|\
  qa-expert:audit-story|\
  qa-expert:audit-planning|\
  architecture-expert:plan-review|\
  security-expert:spec-security|\
  governance:guide-audit|\
  ux-designer:ux-gate|\
  devops-expert:ci-gate) : ;;
  *)
    if [ "$IN_WORKTREE" = "1" ]; then
      echo "[verifier-sandbox BLOCK] agent:mode '$AGENT_KEY' nao autorizado em worktree" >&2
      exit 2
    fi
    ;;
esac
```

**Linhas 90-94 (sandbox dirs por agent):**

```bash
# Antes:
case "$AGENT" in
  verifier)            SANDBOX_DIR="verification-input" ;;
  reviewer)            SANDBOX_DIR="review-input" ;;
  security-reviewer)   SANDBOX_DIR="security-review-input" ;;
  test-auditor)        SANDBOX_DIR="test-audit-input" ;;
  functional-reviewer) SANDBOX_DIR="functional-review-input" ;;
esac

# Depois (v3):
case "$AGENT_KEY" in
  qa-expert:verify)              SANDBOX_DIR="verification-input" ;;
  architecture-expert:code-review) SANDBOX_DIR="review-input" ;;
  security-expert:security-gate) SANDBOX_DIR="security-review-input" ;;
  qa-expert:audit-tests)         SANDBOX_DIR="test-audit-input" ;;
  product-expert:functional-gate) SANDBOX_DIR="functional-review-input" ;;
  data-expert:data-gate)         SANDBOX_DIR="data-review-input" ;;
  observability-expert:observability-gate) SANDBOX_DIR="observability-review-input" ;;
  integration-expert:integration-gate) SANDBOX_DIR="integration-review-input" ;;
esac
```

**Linhas 161-194 (regras R11 verifier vs reviewer):**

Atualizar as condicoes:

```bash
# Antes: if [ "$AGENT" = "verifier" ]
# Depois: if [ "$AGENT_KEY" = "qa-expert:verify" ]

# Antes: if [ "$AGENT" = "reviewer" ]
# Depois: if [ "$AGENT_KEY" = "architecture-expert:code-review" ]
```

Mensagens de erro devem continuar referenciando R11 e citar os novos nomes.

### Arquivo 2 (opcional): `scripts/hooks/record-subagent-usage.sh`

Linha 10 tem comentario de exemplo citando "verifier, reviewer, ..." — apenas comentario, nao afeta comportamento. Atualizar para v3 quando conveniente.

## Procedimento para o PM

Em terminal externo (bash, fora do agente):

```bash
cd /c/PROJETOS/saas/kalibrium-v2

# 1. Editar hook manualmente com editor de preferencia
# Exemplo: nano scripts/hooks/verifier-sandbox.sh
# Aplicar patches acima.

# 2. Verificar sintaxe:
bash -n scripts/hooks/verifier-sandbox.sh

# 3. Rodar relock (4 camadas de salvaguarda):
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# Vai pedir para digitar literal "RELOCK"
# Cria automaticamente docs/incidents/harness-relock-YYYY-MM-DDTHHMMSS.md

# 4. Stage + commit:
git add scripts/hooks/verifier-sandbox.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): update verifier-sandbox.sh para agents v3 + relock"

# 5. Voltar ao Claude Code. SessionStart valida selos automaticamente.
```

## Teste smoke pos-relock

Rodar um slice L1 ou L2 simples antes de tentar L3 completo, para confirmar que:

1. Hook nao bloqueia invocacao de `qa-expert` modo `verify`
2. Hook bloqueia corretamente se um agent tenta ler fora do seu sandbox
3. R11 ainda funciona: `qa-expert:verify` nao pode ler output de `architecture-expert:code-review`

## Alternativa (se PM preferir)

Se nao quiser rodar relock agora, pode rodar o pipeline manualmente (fora do worktree do hook) ate o relock ser feito. Nesse caso o hook so ativa o fail-closed quando `IN_WORKTREE=1`. Slices podem ser executados no branch principal (fora de worktree) ate o relock ser aplicado — com perda de isolamento R3/R11 por falta de worktree.

Nao recomendado para L3/L4 reais. Para smoke tests em L1/L2 pode funcionar.

## Decisao recomendada

Fazer o relock antes do proximo slice L3. E trabalho pontual (~15 minutos em terminal) e destrava o pipeline completamente para v1.2.2.
