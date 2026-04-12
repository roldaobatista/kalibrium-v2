---
description: Monta verification-input/, spawn verifier em worktree isolada, valida JSON resultante contra schema, atualiza contador de reprovações (R6), dispara relatório PM-ready automaticamente (G-11). Use após todos os AC-tests estarem verdes. Uso: /verify-slice NNN.
---

# /verify-slice

## Uso
```
/verify-slice NNN
```

## Pré-condições (validadas pelo script)

- `specs/NNN/spec.md` existe e tem ACs numerados
- `specs/NNN/plan.md` existe
- Todos os AC-tests do slice estão verdes (rodados com filtro, não suite full)
- Nenhum hook foi desabilitado durante o slice (check em telemetry)

## O que faz

1. **Monta `verification-input/`:**
   - `spec.md` (cópia de `specs/NNN/spec.md`)
   - `ac-list.json` (parse dos ACs do spec, formato `[{"id":"AC-001","text":"..."}]`)
   - `test-results.txt` (output de rodar os AC-tests filtrados pelo ID)
   - `files-changed.txt` (`git diff --name-only base...HEAD` do slice)
   - `constitution-snapshot.md` (cópia de `docs/constitution.md` no estado atual)

2. **Spawn do verifier em worktree isolada** via Claude Code `Agent` com `subagent_type: verifier`, `isolation: worktree`. O hook `verifier-sandbox.sh` bloqueia reads fora de `verification-input/`.

3. **Aguarda** o verifier escrever `verification-input/verification.json`.

4. **Valida JSON contra schema** (R4). Rejeita outputs:
   - Sem `verdict`, `ac_checks`, `violations`, `next_action`
   - Com `verdict` fora de `{approved, rejected}`
   - Com prosa adicional fora do JSON
   - Faltando ACs que estão em `ac-list.json`

5. **Atualiza telemetria** em `.claude/telemetry/slice-NNN.jsonl`:
   ```json
   {"event":"verify","timestamp":"...","verdict":"approved","reject_count":0}
   ```

6. **Aplica R6:** se for o segundo `rejected` consecutivo do mesmo slice:
   - Força `next_action: escalate_human`
   - Cria `docs/incidents/slice-NNN-escalation-<date>.md` com cópia do verification.json
   - **Bloqueia** novas tentativas do implementer até humano registrar decisão

7. **Descarta a worktree.** Copia apenas `verification.json` para `specs/NNN/verification.json`.

8. **G-11 — dispara relatório PM-ready automaticamente.** Em **qualquer verdict** (approved, rejected 1x, ou R6 escalation), o script invoca `scripts/explain-slice.sh` que delega para `scripts/translate-pm.sh` (B-010). O resultado é `docs/explanations/slice-NNN.md` em linguagem de produto pura (R12), com:
   - ACs que passaram/falharam traduzidos pelo título do AC (não pelo nome de teste)
   - Violations P/R traduzidas por mapa fixo (ex.: P2 → "parte da funcionalidade ficou sem teste")
   - Findings estruturais categorizados por severidade + frase-base em PT-BR
   - Bloco "Sua decisão é necessária" aparece automaticamente em rejected
   - Detalhes técnicos em `<details>` collapsado — PM não precisa abrir

   **O PM não precisa mais invocar `/explain-slice` manualmente.** O relatório nasce junto com o verdict.

## Implementação

Executar:
```bash
bash scripts/verify-slice.sh "$1"
```

(O script monta o input package, spawn-a o sub-agent, valida JSON e atualiza telemetry.)

## Output esperado no chat

**Caso approved:**
```
[verify-slice] validando verification-input/verification.json contra schema R4...
[verify-slice] schema OK
[verify-slice] verdict=approved next_action=open_pr
[verify-slice] gerando relatório PM-ready...
[verify-slice] ✓ approved — abrir PR (next_action=open_pr)
[verify-slice]   relatório PM: docs/explanations/slice-NNN.md
```

**Caso rejected 1x:**
```
[verify-slice] verdict=rejected next_action=return_to_implementer
[verify-slice] gerando relatório PM-ready...
[verify-slice] ✗ rejected (1/2) — implementer deve corrigir violations e re-verificar
[verify-slice]   relatório PM (leia este, não o JSON): docs/explanations/slice-NNN.md
```

**Caso R6 (rejected 2x):**
```
[verify-slice] gerando relatório PM-ready (R6 escalation)...
================================================================
  R6 ESCALAÇÃO HUMANA OBRIGATÓRIA — slice-NNN
================================================================
  Rejeições consecutivas: 2
  Incidente criado: docs/incidents/slice-NNN-escalation-YYYY-MM-DD.md
  Relatório PM (em PT-BR): docs/explanations/slice-NNN.md
  Implementer BLOQUEADO até decisão humana.
================================================================
```
