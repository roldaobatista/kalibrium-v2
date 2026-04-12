---
description: Invoca o sub-agent reviewer em contexto isolado INDEPENDENTE do verifier. Monta review-input/, spawn do reviewer, valida review.json contra schema. Parte do modelo humano=PM (R11). Uso: /review-pr NNN.
---

# /review-pr

## Uso
```
/review-pr NNN
```

## Quando invocar
Após o `verifier` ter emitido `verification.json` com `verdict: approved`. **Nunca** antes — reviewer não deve rodar em slice que nem passou no verifier (desperdício de tokens + semântica errada).

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

2. **Spawn do sub-agent `reviewer`** via Agent tool:
   - `subagent_type: "reviewer"`
   - `isolation: "worktree"`
   - Hook `verifier-sandbox.sh` é estendido para **também** bloquear leitura de `verification-input/` quando o agente for `reviewer` (R11: reviewer não vê output do verifier)

3. **Aguarda** `review-input/review.json` ser escrito pelo reviewer

4. **Valida JSON** contra `docs/schemas/review.schema.json` via `scripts/validate-review.sh`

5. **Atualiza telemetria**:
   ```json
   {"event":"review","timestamp":"...","slice":"slice-NNN","verdict":"approved","reject_count":0}
   ```

6. **Aplica R6** para reviewer também: 2 rejeições consecutivas → `escalate_human` + incident file

7. **Copia review.json** para `specs/NNN/review.json` (persistência)

8. **Se verifier.json=approved E review.json=approved**: skill dispara `/merge-slice NNN` automaticamente

## Implementação

```bash
bash scripts/review-slice.sh "$1"
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `verification.json` não existe ou não tem `verdict: approved` | Abortar. Rodar `/verify-slice NNN` primeiro — reviewer nunca roda antes do verifier. |
| `review.json` não passa na validação contra schema | Re-spawn reviewer. Se falhar 2x, escalar humano (R6). |
| Reviewer reprova pela 2ª vez consecutiva (R6) | Criar incident file, bloquear implementer, invocar `/explain-slice NNN` para traduzir ao PM. |
| Worktree isolada falha ao ser criada | Verificar espaço em disco e estado do git. Tentar novamente. Se persistir, reportar erro ao PM. |

## Agentes

| Sub-agent | Isolamento | Budget |
|---|---|---|
| `reviewer` | worktree isolada | 30k tokens |

## Handoff

- **Ambos aprovam** → merge automático via `/merge-slice NNN`
- **Reviewer reprova** (1ª vez) → volta ao implementer com `findings` + sugestão
- **Reviewer reprova 2ª vez** → `/explain-slice NNN` gera relatório em português pro humano decidir
