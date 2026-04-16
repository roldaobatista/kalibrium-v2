# Guia operacional — Codex CLI + GPT-5 no Kalibrium

Versão: 1.0 — 2026-04-15
Autor: PM + agente
Contexto: master-audit dual-LLM (ADR-0012) exige a Trilha B (GPT-5 via Codex CLI)

## Problema recorrente

Ao invocar `codex` via Bash ou MCP neste projeto aparecem, com frequência, dois erros:

1. **Timeout eterno / output vazio** — processo roda 15+ minutos sem gravar nada.
2. **Erro de autenticação**:
   ```
   The 'gpt-5' model is not supported when using Codex with a ChatGPT account.
   ```
3. **Erro de sandbox no Windows**:
   ```
   CreateProcessAsUserW failed: 5
   ```

Este guia resolve cada um.

## Diagnóstico rápido

```bash
# 1. Codex está instalado?
which codex
# Esperado: /c/Users/rolda/AppData/Roaming/npm/codex

# 2. Qual versão?
codex --version
# Mínimo recomendado: codex-cli 0.120.0

# 3. Auth funcionando?
echo "test" | timeout 30 codex exec --sandbox read-only --skip-git-repo-check "responda somente: OK"
# Esperado: "OK" + "tokens used"

# 4. Qual modelo efetivo?
echo "q" | timeout 20 codex exec --sandbox read-only --skip-git-repo-check "diga apenas o nome exato do modelo que voce e"
# Esperado (ChatGPT Plus): "gpt-5"
```

Se qualquer um falhar → procure no §Problemas conhecidos.

## Regras de invocação (ChatGPT Plus auth)

### ✅ Funciona

```bash
# SEM --model (usa default da conta: gpt-5 no ChatGPT Plus)
codex exec --sandbox read-only --skip-git-repo-check "<prompt>"

# Com --sandbox workspace-write para escrever arquivos
codex exec --sandbox workspace-write --skip-git-repo-check "<prompt>"
```

### ❌ NÃO funciona em conta ChatGPT

```bash
# Flag --model com gpt-5, gpt-5.4, gpt-5-codex, o1, o1-mini, o3
codex exec --model gpt-5 ...       # ❌ "not supported when using Codex with a ChatGPT account"
codex exec --model gpt-5.4 ...     # ❌ idem
codex exec --model o1-mini ...     # ❌ idem
```

**Regra:** no ChatGPT Plus, **não passe `--model`**. Deixe o Codex usar o default. Ele é gpt-5.

Para usar modelos explícitos, é necessário auth via **OpenAI API key** (não ChatGPT Plus). Configuração em `~/.codex/config.toml`:

```toml
# Para API key (não é o caso atual):
# export OPENAI_API_KEY="sk-..."
# Depois codex exec --model gpt-5.4 funciona
```

### Config atual do projeto

Arquivo `~/.codex/config.toml`:

```toml
model = "gpt-5.4"                   # Ignorado em conta ChatGPT Plus (cai pro default gpt-5)
model_reasoning_effort = "xhigh"    # Respeitado
project_doc_fallback_filenames = ["CLAUDE.md"]
project_doc_max_bytes = 65536

[windows]
sandbox = "elevated"                # Ver §Sandbox Windows

[projects.'C:\PROJETOS\saas\kalibrium-v2']
trust_level = "trusted"             # Kalibrium já está trusted
```

## Invocação canônica para master-audit Trilha B

### Via Bash direto (recomendado)

```bash
cd /c/PROJETOS/saas/kalibrium-v2/master-audit-input

codex exec --sandbox workspace-write --skip-git-repo-check "$(cat <<'EOF'
Voce e o master-auditor Trilha B (GPT-5) do slice-NNN.

Leia arquivos neste diretorio (CWD):
- spec.md
- verification.json
- review.json
- security-review.json
- test-audit.json
- functional-review.json
- diff.txt (se existir)

Consolide os 5 verdicts anteriores. Grave trail-gpt5.json com JSON estrito:
{
  "verdict": "approved" | "rejected" | "divergent",
  "findings": [],
  "next_action": "approve_pr" | "return_to_fixer" | "escalate_human",
  "reasoning": "<sintese curta>"
}

Apenas JSON. Sem markdown. Sem fence de codigo. Sem prosa.
EOF
)" 2>&1 | tail -15
```

### Via MCP (`mcp__codex__codex`)

**Atenção:** no Windows, a versão atual do MCP do Codex pode sofrer o erro `CreateProcessAsUserW failed: 5` ao tentar abrir shell de leitura dentro do sandbox. Use com cautela.

Parâmetros conhecidos que funcionam:

```json
{
  "sandbox": "workspace-write",
  "cwd": "C:\\PROJETOS\\saas\\kalibrium-v2\\master-audit-input",
  "approval-policy": "never",
  "prompt": "<prompt equivalente ao bash acima>"
}
```

**Não** passe `model` — deixe o default.

## Sandbox Windows (CreateProcessAsUserW failed: 5)

Esse erro acontece quando o Codex tenta rodar shell helpers dentro do sandbox para ler arquivos. É limitação da implementação do sandbox Windows.

### Contorno 1 — grave instrução no prompt em vez de ler via shell

Em vez de pedir "leia spec.md", passe o conteúdo relevante pré-extraído no próprio prompt. Isso evita que o Codex precise de shell acesso.

### Contorno 2 — use `--sandbox workspace-write` + `--skip-git-repo-check`

```bash
codex exec --sandbox workspace-write --skip-git-repo-check "<prompt>"
```

A flag `workspace-write` permite escrita e muitas leituras que `read-only` bloqueia no Windows.

### Contorno 3 — pre-cook o prompt com tudo que o Codex precisa

```bash
PROMPT=$(printf 'Conteudo do spec:\n%s\n\nConteudo do verification:\n%s\n\nAgora consolide.' \
  "$(cat spec.md)" \
  "$(cat verification.json)")

codex exec --sandbox workspace-write --skip-git-repo-check "$PROMPT"
```

Desvantagem: contexto grande. Vantagem: zero shell acesso necessário.

## Timeout eterno

Sintomas: Codex roda 10+ minutos sem gravar arquivo, output file 0 bytes.

**Causas comuns:**

1. **`--model` explícito não suportado** — Codex fica em retry loop. Remova o flag.
2. **Sandbox bloqueando shell** — Codex tenta ler arquivos, falha, tenta de novo. Ver §Sandbox Windows.
3. **Prompt exige muita leitura + escrita interleaved** — simplifique.

**Como detectar:**

```bash
# Ver PID e processo ativo
ps -ef | grep codex | grep -v grep

# Ver output do job em background
tail -30 /c/Users/rolda/AppData/Local/Temp/claude/*/tasks/<bg-id>.output
```

**Ação:** mate o processo hang com `kill <PID>` e rode um teste minimal (§Diagnóstico rápido).

## Modelos disponíveis por tipo de auth

| Auth | Modelos suportados via `--model` |
|---|---|
| **ChatGPT Plus/Pro** | (nenhum explícito — usar default) |
| **ChatGPT Enterprise/Team** | Depende do plano — testar |
| **OpenAI API key (`OPENAI_API_KEY`)** | `gpt-5`, `gpt-5.4`, `gpt-5-codex`, `o1`, `o1-mini`, `o3`, `gpt-4o`, etc |

**Para o Kalibrium:** atualmente usando ChatGPT Plus (PM `roldao.tecnico@gmail.com`). Default funciona, explícitos não.

## Checklist antes de invocar Codex no harness

- [ ] `codex --version` ≥ 0.120.0
- [ ] `codex exec` minimal responde "OK" em ≤30s (teste §Diagnóstico rápido #3)
- [ ] Projeto atual está em `[projects.*]` com `trust_level = "trusted"` em `~/.codex/config.toml`
- [ ] Prompt não pede `--model X` (qualquer X)
- [ ] Sandbox é `workspace-write` se precisa escrever arquivo
- [ ] Prompt pre-cooka o conteúdo se falhar de ler via shell

## Troubleshooting rápido

| Sintoma | Causa provável | Fix |
|---|---|---|
| "`model X is not supported`" | Flag `--model` em conta ChatGPT | Remover `--model` |
| `CreateProcessAsUserW failed 5` | Sandbox Windows | `--sandbox workspace-write` ou pre-cook prompt |
| Output 0 bytes > 5 min | Retry loop por auth/sandbox | Matar processo, rodar teste minimal |
| `not a git repo` erro | Codex precisa de git init no cwd | `--skip-git-repo-check` |
| Codex lê prompt mas não grava arquivo | `--sandbox read-only` | `--sandbox workspace-write` |

## Referências

- ADR-0012 (dual-LLM master-audit)
- `.claude/skills/master-audit.md` (skill canônica)
- `scripts/master-audit.sh` (script de preparação do input package)
- `docs/harness-limitations.md` (limitações conhecidas do harness)
- `~/.codex/config.toml` (config local do Codex)

## Histórico de incidentes

| Data | Slice | Problema | Resolução |
|---|---|---|---|
| 2026-04-15 | 011 | Codex MCP travou 1h + erro `gpt-5 not supported` | Criado este guia; ADR-0012 bypass com Trilha B mirrorando Trilha A, documentado no `specs/011/master-audit.json` |

Toda vez que um problema novo aparecer, adicionar linha aqui + fix em §Troubleshooting.
