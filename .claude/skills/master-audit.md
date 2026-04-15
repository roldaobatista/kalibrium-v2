---
description: Consolidação dual-LLM (Claude Opus 4.6 + GPT-5 via Codex CLI) das 5 trilhas de gate. Ativa master-auditor (ADR-0012 E2). Use como 4º passo da Fase E, após verifier/reviewer/security/test/functional todos approved. Uso: /master-audit NNN.
---

# /master-audit

## Uso
```
/master-audit NNN
```

## Pré-condições (validadas pelo script)

- `specs/NNN/verification.json` com `verdict: approved` e `findings: []`
- `specs/NNN/review.json` com `verdict: approved` e `findings: []`
- `specs/NNN/security-review.json` com `verdict: approved` e `findings: []`
- `specs/NNN/test-audit.json` com `verdict: approved` e `findings: []`
- `specs/NNN/functional-review.json` com `verdict: approved` e `findings: []`
- `specs/NNN/spec.md` e `specs/NNN/plan.md` existem

## O que faz

1. **Monta `master-audit-input/`** (input package idêntico para as duas trilhas):
   - `spec.md`, `plan.md` (cópias de `specs/NNN/`)
   - `verification.json`, `review.json`, `security-review.json`, `test-audit.json`, `functional-review.json` (os 5 vereditos dos gates anteriores)
   - `diff.txt` (`git diff --name-status base...HEAD`)
   - `adr-index.md` (lista de ADRs aceitas referenciadas pelo slice)
   - `constitution-snapshot.md` (cópia de `docs/constitution.md` no estado atual)

2. **Spawn paralelo de duas trilhas independentes** (princípio P3 + R11/ADR-0012 E2):

   **Trilha A — Claude Opus 4.6:**
   - Agent tool com `subagent_type: master-auditor` (contexto limpo, sandbox de leitura restrita a `master-audit-input/`)
   - Produz `master-audit-input/trail-opus.json`

   **Trilha B — GPT-5 via Codex CLI:**
   - `mcp__codex__codex` com `model: gpt-5.4` (ou fallback default da config), `sandbox: read-only`, `cwd: master-audit-input/`
   - Produz `master-audit-input/trail-gpt5.json`

   **Regras de simetria** (para garantir independência genuína):
   - Prompts idênticos em estrutura e conteúdo
   - Nenhuma trilha lê o output da outra
   - Cada trilha emite JSON validado contra `docs/schemas/master-audit-trail.schema.json`

3. **Consolidação pelo orquestrador:**
   - Se **ambas as trilhas** retornam `verdict: approved` com `findings: []` → **consenso approved**
   - Se **ambas** retornam `verdict: rejected` → **consenso rejected** (findings mergeados sem duplicação)
   - Se **divergem** (uma approved, outra rejected) → entra em **reconciliação**

4. **Reconciliação (até 3 rodadas, ADR-0012 E5):**
   - Orquestrador formula pergunta específica para a trilha com verdict minoritário
   - Usa `mcp__codex__codex-reply` (GPT-5) ou SendMessage ao Agent (Opus) para refinar
   - Se alguma trilha mudar veredito → consenso alcançado
   - Se persistir discordância após 3 rodadas → `escalate_human`

5. **Grava `specs/NNN/master-audit.json`** com:
   - `verdict`: approved | rejected | escalate_human
   - `trails`: [{llm_id, verdict, reconciliation_rounds, output_file}]
   - `consensus`: true | false
   - `divergences`: array (se houver)
   - `next_action`: merge | return_to_fixer | escalate_human

6. **Atualiza telemetria** em `.claude/telemetry/slice-NNN.jsonl`:
   ```json
   {"event":"master-audit","timestamp":"...","verdict":"approved","trails":2,"reconciliation_rounds":0,"tokens_opus":..., "tokens_gpt5":...}
   ```

7. **Aplica R6:** 6ª rejeição consecutiva do master-auditor no mesmo slice → `escalate_human` + incident file + bloqueia fixer.

8. **Dispara relatório PM-ready** (G-11) via `scripts/explain-slice.sh` quando:
   - Verdict for `escalate_human`
   - Ou quando houver qualquer divergência entre trilhas (mesmo se consenso final for approved)

## Implementação

Executar:
```bash
bash scripts/master-audit.sh "$1"
```

O script monta o input package, invoca as duas trilhas em paralelo via tools do Claude Code, consolida, aplica reconciliação se necessária, valida JSON contra schema e atualiza telemetria.

## Output esperado no chat

**Caso consenso approved:**
```
[master-audit] input package montado em master-audit-input/
[master-audit] invocando trilha A (Opus 4.6) + trilha B (GPT-5.4) em paralelo...
[master-audit] trilha A concluída: verdict=approved, findings=[]
[master-audit] trilha B concluída: verdict=approved, findings=[]
[master-audit] ✓ consenso approved — merge autorizado (next_action=merge)
```

**Caso divergência reconciliada:**
```
[master-audit] trilha A: approved
[master-audit] trilha B: rejected (1 finding sobre cobertura AC-003)
[master-audit] divergência detectada — iniciando reconciliação (rodada 1/3)
[master-audit] enviando pergunta de reconciliação para trilha A...
[master-audit] trilha A revisou: agora rejected (acordo com finding de B)
[master-audit] ✓ consenso rejected após reconciliação — return_to_fixer
[master-audit]   relatório PM: docs/explanations/slice-NNN.md
```

**Caso escalação:**
```
[master-audit] 3 rodadas de reconciliação sem convergência
[master-audit] persistem: trilha A approved, trilha B rejected
================================================================
  ESCALAÇÃO HUMANA — slice-NNN (divergência dual-LLM persistente)
================================================================
  Incidente: docs/incidents/slice-NNN-master-audit-disagreement-YYYY-MM-DD.md
  Relatório PM (PT-BR): docs/explanations/slice-NNN.md
  Fixer BLOQUEADO até decisão humana.
================================================================
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Algum dos 5 JSONs de pré-condição ausente ou `verdict != approved` | Abortar. Orquestrador deve voltar ao gate anterior pendente. |
| Trilha Opus falha (timeout, erro de tool) | Retry automático 1x. Se falhar, escalar humano com `next_action: escalate_human`. |
| Trilha GPT-5 falha (erro de MCP, cota, model indisponível) | Retry 1x com fallback para model default da config Codex. Se falhar, escalar. |
| JSON emitido por alguma trilha fora do schema | Rejeitar output, re-spawnar a trilha com prompt reforçado. Se 3 falhas de schema, escalar. |
| Divergência persiste após 3 rodadas de reconciliação | Criar incident file, gerar relatório PM via R12, aguardar decisão humana. |
| Ambas trilhas rejected com findings idênticos | Consenso rejected — orquestrador invoca `/fix NNN master-auditor`, loop padrão até findings=[] ou R6. |
| Custo explosivo (>800k tokens combinados) | Registrar em telemetria como outlier; continuar mas alertar na próxima retrospectiva. |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `master-auditor` (trilha Opus) | sandbox via `verifier-sandbox.sh`, input restrito a `master-audit-input/` | 80k tokens |
| GPT-5 (trilha Codex) | `mcp__codex__codex` sandbox read-only, cwd = `master-audit-input/` | 80k tokens |

## Cross-ref

- `docs/adr/0012-constitution-amendment-autonomy-dual-llm.md` §E2, §E5
- `docs/constitution.md §R11` (emendada), §R15, §R16
- `.claude/agents/master-auditor.md`
- `docs/audits/external/master-audit-smoke-test-2026-04-15.json` (primeiro smoke-test documentado)
- `docs/schemas/master-audit.schema.json` (quando criado — item de backlog)
