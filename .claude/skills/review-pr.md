---
description: Invoca o sub-agent architecture-expert (code-review) em contexto isolado INDEPENDENTE do qa-expert (verify). Monta review-input/, spawn do code-reviewer, valida review.json contra schema. Parte do modelo humano=PM (R11). Uso: /review-pr NNN.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-A1 (Output no chat R12)"
---

# /review-pr

## Uso
```
/review-pr NNN
```

## Quando invocar
Após o `qa-expert` (modo: verify) ter emitido `verification.json` com `verdict: approved`. **Nunca** antes — code-review não deve rodar em slice que nem passou no `qa-expert` (modo: verify) (desperdício de tokens + semântica errada).

## Pré-condições
- `specs/NNN/verification.json` existe e tem `verdict: approved`
- `specs/NNN/spec.md` aprovado
- Slice está em feature branch com diff contra main

## O que faz

1. **Monta `review-input/`** (separado de `verification-input/`):
   - `spec.md` (cópia do spec aprovado)
   - `diff.patch` — `git diff main...HEAD` do slice completo
   - `files-changed.txt` — lista plana
   - `constitution-snapshot.md`
   - `glossary-snapshot.md`
   - `adr-snapshot/` — cópias dos ADRs mencionados no plan (se houver)

2. **Spawn do sub-agent `architecture-expert` (modo: code-review)** via Agent tool:
   - `subagent_type: "architecture-expert"`
   - **Sem** `isolation: "worktree"` — o input package (`review-input/`) é untracked e não existiria na worktree. O isolamento é garantido pelo hook `verifier-sandbox.sh`, que também bloqueia leitura de `verification-input/` quando o agente for `architecture-expert` em modo code-review (R11: code-review não vê output do verify)

3. **Aguarda** `review-input/review.json` ser escrito pelo `architecture-expert` (modo: code-review)

4. **Valida JSON** contra `docs/schemas/review.schema.json` via `scripts/validate-review.sh`

5. **Atualiza telemetria**:
   ```json
   {"event":"review","timestamp":"...","slice":"slice-NNN","verdict":"approved","reject_count":0}
   ```

6. **Aplica R6** para code-review também: 5 ciclos automáticos; 6ª rejeição consecutiva → `escalate_human` + incident file

7. **Copia review.json** para `specs/NNN/review.json` (persistência)

8. **Se verification.json=approved E review.json=approved**: skill dispara `/merge-slice NNN` automaticamente

## Implementação

```bash
bash scripts/review-slice.sh "$1"
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `verification.json` não existe ou não tem `verdict: approved` | Abortar. Rodar `/verify-slice NNN` primeiro — code-review nunca roda antes do `qa-expert` (modo: verify). |
| `review.json` não passa na validação contra schema | Re-spawn `architecture-expert` (modo: code-review). Se falhar 5 vezes consecutivas, escalar humano na 6ª (R6). |
| `architecture-expert` (modo: code-review) reprova pela 6ª vez consecutiva (R6) | Criar incident file, bloquear implementer, invocar `/explain-slice NNN` para traduzir ao PM. |
| Worktree isolada falha ao ser criada | Verificar espaço em disco e estado do git. Tentar novamente. Se persistir, reportar erro ao PM. |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `architecture-expert` (modo: code-review) | worktree isolada | 30k tokens |

## Conformidade com protocolo v1.2.2

- **Agent invocado:** `architecture-expert (code-review)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `review`
- **Output:** `specs/NNN/review.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §2`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`

## Handoff

- **Ambos aprovam** → merge automático via `/merge-slice NNN`
- **Reviewer reprova** (1ª vez) → volta ao implementer com `findings` + sugestão
- **Reviewer reprova 6ª vez consecutiva** → `/explain-slice NNN` gera relatório em português pro humano decidir

## Output no chat (para PM — R12)

Ao fim da execucao, apresentar ao PM em ate 3 linhas de linguagem de produto:

1. **Veredicto:** frase unica em PT-BR sem jargao — ex: "A revisao tecnica do slice NNN passou sem pontos abertos."
2. **Proxima etapa:** acao unica recomendada — ex: "Posso seguir para a revisao de seguranca (/security-review NNN)."
3. **Se rejeitado:** "Encontrei N pontos de ajuste na revisao tecnica. Vou corrigir automaticamente e reexecutar o gate."

Nunca jogar o review.json cru, stack trace ou diff ao PM. Qualquer detalhe tecnico fica em `specs/NNN/review.json` para auditoria do agente, nao para o PM.
