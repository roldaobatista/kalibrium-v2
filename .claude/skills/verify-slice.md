---
description: Monta verification-input/, spawn verifier em worktree isolada, valida JSON resultante contra schema, atualiza contador de reprovações (R6). Use após todos os AC-tests estarem verdes. Uso: /verify-slice NNN.
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

## Implementação

Executar:
```bash
bash scripts/verify-slice.sh "$1"
```

(O script monta o input package, spawn-a o sub-agent, valida JSON e atualiza telemetry.)

## Output esperado no chat

```
[verify-slice] Montando verification-input/ para slice-NNN...
[verify-slice] AC-list: 5 ACs encontrados (AC-001..AC-005)
[verify-slice] Rodando testes filtrados...
[verify-slice] Spawn do verifier em worktree /tmp/verify-NNN.xxx...
[verify-slice] verification.json recebido, validando schema...
[verify-slice] verdict: approved | next_action: open_pr
[verify-slice] Telemetria atualizada.
```
