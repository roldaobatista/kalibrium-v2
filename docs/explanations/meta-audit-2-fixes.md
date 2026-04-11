# Roteiro PM — correções meta-audit #2 (itens selados)

**Data:** 2026-04-11
**Para quem:** PM do Kalibrium V2 (não-técnico).
**Tempo estimado:** 15-25 min, tudo em um terminal fora do Claude Code.
**Bloqueio atual:** sem executar este roteiro, o primeiro slice real não consegue ser mergeado (cadeia happy-path trava no último elo).

---

## Por que este arquivo existe

Rodei uma auditoria do harness em 2026-04-11. Encontrei 7 coisas que eu mesmo consegui consertar dentro de uma sessão normal, e **4 coisas que eu não consigo mexer** porque estão em arquivos selados (proteção contra drift silencioso que você e eu combinamos em abril).

As 7 primeiras já foram feitas e estão no commit `chore(harness): aplica correções meta-audit #2 (P0 itens não-selados)`.

Este arquivo lista as **4 que você precisa rodar manualmente** no seu terminal, fora do Claude Code. Cada bloco tem:

- **O que muda** (em português, sem jargão)
- **Por que importa** (qual problema real isso resolve)
- **Comandos exatos** (copia e cola — eu testei o formato)
- **O que você deve ver** (diff esperado, para confirmar que deu certo)
- **Como voltar atrás** se algo der errado

No final, todos os 4 itens precisam do mesmo passo de "selar de novo" (`relock-harness.sh`). Faça os 4 de uma vez e sele no final.

---

## Antes de começar

1. **Encerre a sessão do Claude Code** (saia do agente). Os arquivos selados só podem ser editados fora do agente.
2. Abra um terminal bash em `C:\PROJETOS\saas\kalibrium-v2\`.
3. Confirme que está limpo:
   ```bash
   cd /c/PROJETOS/saas/kalibrium-v2
   git status
   ```
   Deve mostrar `nothing to commit, working tree clean`.

---

## 1. Liberar `git push` e `gh pr` (desbloqueia o /merge-slice)

### O que muda
Hoje, mesmo quando o verifier e o reviewer aprovam um slice, o agente não consegue dar `git push` nem criar PR via `gh`. O `.claude/settings.json` bloqueia esses comandos. Resultado: a cadeia `verify → review → merge` trava no último elo.

### Por que importa
Você pediu "um PR chega ao fim sem eu precisar intervir tecnicamente". Sem este item, você precisa abrir o PR manualmente toda vez. Com este item, o `/merge-slice` faz sozinho.

### Comandos

Abra `.claude/settings.json` no seu editor preferido (VS Code, Notepad++, o que quiser). Encontre o array `permissions.allow`. Adicione estas 3 entradas (ordem não importa):

```json
"Bash(git push origin*)",
"Bash(gh pr create*)",
"Bash(gh pr merge*)"
```

### Diff esperado

```diff
 {
   "permissions": {
     "allow": [
       "Bash(git status*)",
       "Bash(git diff*)",
       ...
+      "Bash(git push origin*)",
+      "Bash(gh pr create*)",
+      "Bash(gh pr merge*)",
       ...
     ]
   }
 }
```

### Como confirmar
Depois de salvar:
```bash
grep -E "git push origin|gh pr create|gh pr merge" .claude/settings.json
```
Tem que listar as 3 linhas.

### Risco
Baixo. As 3 permissões são escopadas (`git push origin*` só permite push para o remote origin, não para repositórios arbitrários). O `gh pr merge` ainda passa pelo admin merge do GitHub — o ruleset do repositório é quem decide se o merge passa.

---

## 2. Hook `SubagentStop` (tokens precisos por sub-agent)

### O que muda
Hoje, o `/slice-report` mostra consumo de tokens por sub-agent de forma aproximada (só captura no momento do commit). Com este hook, cada vez que um sub-agent termina (ex: verifier, reviewer), o harness grava exatamente quantos tokens ele consumiu em `.claude/telemetry/slice-NNN.jsonl`.

### Por que importa
Sem isso, você não consegue ver quando um sub-agent estoura budget silenciosamente. O verifier, por exemplo, deveria usar no máximo 25k tokens — se um dia começar a usar 80k, é sinal de leak de contexto e você quer saber **antes** do blow-up.

### Comandos

**Passo 1** — criar o novo script de hook:

```bash
cat > scripts/hooks/record-subagent-usage.sh <<'SCRIPT'
#!/usr/bin/env bash
# record-subagent-usage.sh — SubagentStop hook.
# Grava tokens consumidos por sub-agent na telemetria do slice ativo.
#
# Input esperado (via env vars do Claude Code harness):
#   CLAUDE_SUBAGENT          — nome do sub-agent (ex: verifier, reviewer)
#   CLAUDE_SUBAGENT_TOKENS   — tokens totais consumidos na invocação
#   CLAUDE_SUBAGENT_VERDICT  — (opcional) verdict final
#
# Slice ativo: lê specs/.current (ou "harness" se não houver).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"

SUBAGENT="${CLAUDE_SUBAGENT:-unknown}"
TOKENS="${CLAUDE_SUBAGENT_TOKENS:-0}"
VERDICT="${CLAUDE_SUBAGENT_VERDICT:-n/a}"

SLICE="harness"
[ -f specs/.current ] && SLICE="slice-$(cat specs/.current | tr -d ' \n\r')"

TELEMETRY=".claude/telemetry/${SLICE}.jsonl"
mkdir -p .claude/telemetry
touch "$TELEMETRY"

bash "$REPO_ROOT/scripts/record-telemetry.sh" \
  --event="subagent-stop" \
  --slice="$SLICE" \
  --verdict="$VERDICT" \
  --next-action="monitor" \
  --reject-count="0" \
  --extra="subagent=${SUBAGENT};tokens=${TOKENS}" \
  >/dev/null 2>&1 || true

exit 0
SCRIPT
chmod +x scripts/hooks/record-subagent-usage.sh
```

**Passo 2** — registrar o hook em `.claude/settings.json`. Abra o arquivo, encontre o bloco `"hooks"` e adicione:

```json
"SubagentStop": [
  {
    "matcher": "*",
    "hooks": [
      { "type": "command", "command": "bash scripts/hooks/record-subagent-usage.sh" }
    ]
  }
]
```

### Diff esperado

```diff
 "hooks": {
   "SessionStart": [...],
   "PreToolUse": [...],
   "PostToolUse": [...],
+  "SubagentStop": [
+    {
+      "matcher": "*",
+      "hooks": [
+        { "type": "command", "command": "bash scripts/hooks/record-subagent-usage.sh" }
+      ]
+    }
+  ]
 }
```

### Como confirmar
```bash
grep -A2 SubagentStop .claude/settings.json
ls -la scripts/hooks/record-subagent-usage.sh
```

### Observação importante sobre `record-telemetry.sh --extra`
Se o `record-telemetry.sh` ainda não aceitar a flag `--extra`, ele vai simplesmente ignorar e o hook continua funcionando (o evento é gravado, só sem os campos extras). Na próxima iteração dele (fora deste roteiro) podemos melhorar — não é bloqueante para o primeiro slice.

---

## 3. Estender `verifier-sandbox.sh` (enforcement real do R11)

### O que muda
A regra R11 (dupla-verificação independente) diz que o `reviewer` NÃO pode ler o output do `verifier`. Hoje essa regra está escrita no `.claude/skills/review-pr.md` **como se fosse aplicada**, mas eu não consegui confirmar que o hook `verifier-sandbox.sh` realmente bloqueia essa leitura. Se não bloquear, R11 é nominal, não de fato.

### Por que importa
R11 existe porque você não é técnico e precisa de 2 aprovações independentes para confiar no merge. Se o reviewer pode espiar a nota do verifier, o segundo voto vira confirmation bias automático — você perde a independência.

### Comandos

**Passo 1** — auditar o estado atual:
```bash
grep -n "CLAUDE_SUBAGENT\|reviewer\|verification-input" scripts/hooks/verifier-sandbox.sh
```

Você vai ver uma de duas coisas:

**(a) Já tem o bloqueio** — vai aparecer algo como `if [ "$CLAUDE_SUBAGENT" = "reviewer" ]` e um check sobre `verification-input/`. Se for este caso: pule para o passo 3 (smoke-test), marque este item como "já estava ok" e vá pro 4.

**(b) NÃO tem o bloqueio** — grep retorna nada ou só o nome `verifier` genérico. Neste caso, siga o passo 2.

**Passo 2** — adicionar o bloco de bloqueio. Edite `scripts/hooks/verifier-sandbox.sh` e adicione, logo depois da linha `set -uo pipefail`:

```bash
# R11: reviewer NÃO pode ler verification-input/
if [ "${CLAUDE_SUBAGENT:-}" = "reviewer" ]; then
  TARGET="${CLAUDE_TOOL_INPUT_PATH:-${1:-}}"
  case "$TARGET" in
    *verification-input/*|*verification-input)
      echo "[verifier-sandbox] BLOCK: reviewer tentou ler $TARGET (violação R11)" >&2
      exit 2
      ;;
  esac
fi
```

**Passo 3** — smoke-test dedicado:
```bash
CLAUDE_SUBAGENT=reviewer CLAUDE_TOOL_INPUT_PATH=verification-input/verification.json \
  bash scripts/hooks/verifier-sandbox.sh verification-input/verification.json
echo "exit=$?"
```

Tem que mostrar `[verifier-sandbox] BLOCK: reviewer tentou ler ...` e `exit=2`. Se der `exit=0`, o hook não está bloqueando — me avise antes de selar.

### Diff esperado
Só as linhas novas do bloco R11 em `scripts/hooks/verifier-sandbox.sh`. Pequeno.

### Risco
Baixo. O bloqueio é específico (só age quando `CLAUDE_SUBAGENT=reviewer`). Não afeta o fluxo normal do verifier.

---

## 4. Selar tudo de novo (`relock-harness.sh`)

### O que muda
Os passos 1, 2 e 3 editaram arquivos selados (`.claude/settings.json` e `scripts/hooks/verifier-sandbox.sh`). Os SHAs guardados em `.claude/settings.json.sha256` e `scripts/hooks/MANIFEST.sha256` **não batem mais**. O `SessionStart` do Claude Code vai abortar ao detectar drift, mesmo sendo uma mudança legítima sua.

Este passo regenera os SHAs e registra um arquivo de auditoria em `docs/incidents/` com o timestamp, o operador (você), e a lista de hashes antes/depois.

### Comandos (rode EXATAMENTE como está)

```bash
cd /c/PROJETOS/saas/kalibrium-v2

# 1. Pré-check: garanta que você está fora do Claude Code
[ -z "${CLAUDE_CODE_SESSION_ID:-}" ] && echo "OK: fora do Claude Code" || { echo "ABORTE: dentro do Claude Code"; exit 1; }

# 2. Exporta a flag de autorização e roda o relock
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
# → o script vai pedir para você DIGITAR literalmente: RELOCK
# → se você errar, ele aborta. Isso é proposital.

# 3. Confira que o arquivo de incidente foi criado
ls -la docs/incidents/harness-relock-*.md | tail -1
```

### Diff esperado

```
 .claude/settings.json          (editado nos passos 1 e 2)
 .claude/settings.json.sha256   (novo hash)
 scripts/hooks/verifier-sandbox.sh      (editado no passo 3)
 scripts/hooks/record-subagent-usage.sh (novo arquivo do passo 2)
 scripts/hooks/MANIFEST.sha256          (novo hash)
 docs/incidents/harness-relock-<timestamp>.md  (auditoria automática)
```

### Commit

```bash
git add .claude/settings.json \
        .claude/settings.json.sha256 \
        scripts/hooks/verifier-sandbox.sh \
        scripts/hooks/record-subagent-usage.sh \
        scripts/hooks/MANIFEST.sha256 \
        docs/incidents/harness-relock-*.md

git commit -m "chore(harness): relock pós-meta-audit #2 (P0 ❌ itens selados)"
```

### Como voltar atrás se algo der errado
Antes do commit, `git restore --staged .` desfaz o stage. Se ainda não salvou os arquivos editados, o editor te pergunta. Se já salvou mas ainda não commitou: `git checkout -- .claude/settings.json scripts/hooks/verifier-sandbox.sh` volta pro último commit.

Se já commitou e quer desfazer: **não** rode `git reset --hard`. Me avise primeiro — eu ajudo a fazer um `git revert` em cima que preserva o histórico.

---

## Depois de fazer os 4

1. Abra uma sessão nova do Claude Code dentro de `kalibrium-v2/`.
2. O `SessionStart` hook vai rodar `settings-lock --check` e `hooks-lock --check` automaticamente. Se passarem, está tudo certo.
3. Me diga "pronto, rodei o roteiro". Eu rodo:
   - `/guide-check` (audit completa)
   - `bash scripts/smoke-test-hooks.sh`
   - `bash scripts/smoke-test-scripts.sh`
4. Com tudo verde, estamos prontos para o primeiro slice real.

---

## Se algo falhar no meio

Pare e me chame. **Não** tente consertar com `git reset --hard` ou deletando arquivos de `docs/incidents/`. O histórico de incidentes é dado de auditoria — quando eu ver a sessão nova, vou olhar o último incidente lá para entender o que você fez.

## Referência rápida

| Item | Arquivo tocado | Selado? | Dono da execução |
|---|---|---|---|
| 1 | `.claude/settings.json` (allow list) | Sim | PM (terminal externo) |
| 2 | `.claude/settings.json` + `scripts/hooks/record-subagent-usage.sh` | Sim | PM (terminal externo) |
| 3 | `scripts/hooks/verifier-sandbox.sh` | Sim | PM (terminal externo) |
| 4 | `.sha256` + `MANIFEST.sha256` | Sim (regenerados) | PM (`relock-harness.sh`) |

Os 7 itens não-selados (✅ da meta-audit) estão no commit anterior — não aparecem aqui porque não precisam da sua ação.
