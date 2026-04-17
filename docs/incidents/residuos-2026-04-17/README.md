# Residuos 2026-04-17 — isolamento pos-slice-016

**Data:** 2026-04-17
**Operacao:** cleanup do working tree pos-merge do slice 016 (PR #47 commit `101d922`).
**Motivacao:** o PM solicitou "resolver todas pendencias" apos `/resume`. Varios arquivos untracked sobraram da sessao de execucao do slice 016 — backups automaticos, dirs temporarios de gates, staging de conflito. Ao inves de `rm -rf`, PM pediu isolamento com documentacao para auditoria.

Todos os itens isolados aqui sao **regeneraveis ou historicos** — nenhum e fonte de verdade viva. Podem ser removidos em limpeza futura sem perda operacional. Registros canonicos equivalentes existem em outros lugares (ver subpastas).

---

## Indice

| Pasta | Origem | Quantidade | Regeneravel? | Descarte seguro? |
|---|---|---|---|---|
| `hooks-bak/` | `scripts/hooks/*.bak-*` (backups do relock-harness.sh) | 4 arquivos | Sim (via relock) | Sim — existe `docs/incidents/harness-relock-*.md` canonico |
| `scripts-bak/` | `scripts/merge-slice.sh.bak-*` | 1 arquivo | Sim (git log) | Sim |
| `staging-merge/merge-conflict/` | `scripts/staging/merge/*` — saida de `git checkout --conflict` do PR #39/#40 | 27 arquivos (`d_*`, `ours_*`, `theirs_*`) | Nao — historico de conflito ja resolvido | Sim — resolucao aplicada em commits `a13fe36` e `4d8c007` |
| `staging-merge/relock-v3-flow.sh` | `scripts/staging/` — script candidato a promocao | 1 arquivo | Parcial — codigo vivo, nao promovido | **Nao** — material em andamento (decidir promocao ou descarte) |
| `staging-merge/verifier-sandbox-v3.sh` | `scripts/staging/` — hook candidato a promocao para `scripts/hooks/verifier-sandbox.sh` (relock necessario) | 1 arquivo | Parcial — codigo vivo, nao promovido | **Nao** — material em andamento (alinhar com protocolo v1.2.2) |
| `gate-inputs/` | 4 dirs de gate-input (master-audit, functional-review, security-review, test-audit) com sufixo `-residuo` para escapar do `.gitignore` | 4 dirs (~40 arquivos) | Sim — regerados pelo fluxo de gates | Sim — snapshots pontuais do slice 016 ja consolidados em `specs/016/` |

---

## Por subpasta

### `hooks-bak/`

Backups automaticos criados pelo `relock-harness.sh` (e variantes `.bat`) durante re-selagem de hooks em 2026-04-16/17. O harness hoje mantem registro canonico via `docs/incidents/harness-relock-<timestamp>.md` (hashes antes/depois, operador, host). Os `.bak-*` ficam redundantes apos o commit do relock.

- `pre-commit-gate.sh.bak-2026-04-17T03-55-01Z`
- `session-start.sh.bak-2026-04-17T03-55-01Z`
- `session-start.sh.bak-2026-04-17T15-55-03Z`
- `verifier-sandbox.sh.bak-20260416-171054`

**Descarte:** seguro. `git log scripts/hooks/<arquivo>` reconstroi historico completo.

### `scripts-bak/`

- `merge-slice.sh.bak-2026-04-17T03-55-01Z` — backup pre-update do merge-slice para a versao v1.2.2-aware.

**Descarte:** seguro.

### `staging-merge/merge-conflict/`

27 arquivos emitidos pelo `git checkout --conflict` durante resolucao dos PRs #39/#40 (recuperacao de artefatos de ampliacao via union merge). Padrao `d_<arquivo>`, `ours_<arquivo>`, `theirs_<arquivo>` — parciais da resolucao manual.

**Descarte:** seguro. Resultado final ja esta em `main` via commits `a13fe36` (PR #39) e `4d8c007` (PR #40).

### `staging-merge/relock-v3-flow.sh` (ATIVO)

Fluxo interno chamado por um `.bat` de relock v1.2.2. Tarefas: backup do hook atual, copiar staging v3, validar sintaxe, rodar `relock-harness.sh`, comitar. Ainda nao promovido para `scripts/` porque depende de `verifier-sandbox-v3.sh` ser aceito primeiro.

**Descarte:** **NAO descartar.** Material em andamento. Acao pendente: decidir se promove para `scripts/relock-v3-flow.sh` (via `.bat` + `relock-harness.sh`) ou abandona em favor de outro fluxo.

### `staging-merge/verifier-sandbox-v3.sh` (ATIVO)

Versao v3 do hook `verifier-sandbox.sh`, alinhada com protocolo operacional v1.2.2 (aceita nomes v2 legados + v3 canonicos). Relacionado ao residuo `HARNESS-MIGRATION-001` (ja classificado como **falso-positivo** em 2026-04-17 — `scripts/hooks/verifier-sandbox.sh` atual, commit `5a7c992`, ja tem allowlist v3 completa e SHA bate com `MANIFEST.sha256`).

**Descarte:** seguro se confirmado que o hook atual selado ja contem tudo o que esta no staging. Caso contrario, material em andamento. Recomendacao: comparar lado-a-lado com `scripts/hooks/verifier-sandbox.sh` antes de descartar.

### `gate-inputs/`

Dirs de trabalho dos gates — populados por `scripts/<gate>.sh` com os inputs que cada gate consome em contexto isolado (R3/R11). Apos o gate emitir o JSON final para `specs/NNN/<gate>.json`, os inputs ficam residuais no working tree ate o proximo rebuild.

- `master-audit-input-residuo/` (10 arquivos: adr-index, constitution-snapshot, diff, plan, spec, e os 4 JSONs consolidados)
- `functional-review-input-residuo/`
- `security-review-input-residuo/`
- `test-audit-input-residuo/`

**Descarte:** seguro. Os outputs canonicos dos gates do slice 016 estao em `specs/016/*.json`.

---

## Por que nao foram ignorados desde o inicio

O `.gitignore` pre-cleanup so tinha `verification-input/` e `review-input/`. Os outros 4 gate-input dirs (master-audit, functional-review, security-review, test-audit) e `scripts/staging/` nao estavam listados porque foram adicionados ao fluxo depois que o `.gitignore` foi escrito originalmente.

Este commit de cleanup corrige isso: `.gitignore` agora cobre todos os gate-input dirs + `scripts/staging/` + `scripts/hooks/*.bak-*`.

---

## Proxima acao recomendada

1. **Decidir destino de `staging-merge/verifier-sandbox-v3.sh`** comparando com `scripts/hooks/verifier-sandbox.sh` atual (ja v3 selado em commit `5a7c992`).
2. **Decidir destino de `staging-merge/relock-v3-flow.sh`** — promover ou descartar.
3. **Em 30 dias** (gatilho: `2026-05-17`), se nenhum dos dois foi promovido, remover a pasta inteira `docs/incidents/residuos-2026-04-17/`.
