# Kalibrium V2 — Limitações conhecidas do harness

Este documento lista limitações, débitos técnicos e exceções conhecidas do harness.
Cada item deve ter motivo, política operacional e (quando aplicável) plano de remoção.

---

## Débito histórico de identidade git (pré-meta-audit 2026-04-10)

### Contexto

Durante o bootstrap do V2 (pré-meta-audit), 8 commits foram autorados como
`smoke-test-user <smoke@test.local>`. A causa raiz foi `git config user.name`
sujo de testes do smoke-test-hooks.sh em uma sessão anterior, combinado com o
fato de que o `pre-commit-gate.sh` ainda não tinha o check de allowlist do
**item 1.7 do meta-audit 2026-04-10**.

Esses commits estão **anistiados** porque:

- Reescrever histórico (`git filter-branch` ou `rebase -i` + force-push)
  alteraria os SHAs e quebraria referências em `docs/incidents/pr-1-admin-merge.md`
  e no PR #1 já mergeado.
- A operação destrutiva foi **explicitamente recusada** pelo PM em decisão
  Q1 do relatório final do Bloco 1 (2026-04-10).

### Os 8 commits débito

| SHA curto | Mensagem |
|---|---|
| `6a0d297` | test: smoke tests hooks (30) + scripts (21) — 51/51 verdes no Windows |
| `7369027` | chore(scripts): helpers dos skills + validador do verification.json |
| `ed5daba` | chore(scripts): 12 hooks Windows-safe (enforcement por arquitetura) |
| `0d3ad4a` | chore(.claude): 5 sub-agents + 8 skills + settings.json + allowed-mcps |
| `3211e3e` | docs(adr): 0000 template + 0002 MCP policy + schemas + glossary |
| `a0d2331` | docs: templates de slice (spec/plan/tasks) + registry + backlog |
| `0d83b4f` | docs(reference): v1-post-mortem + roles-from-bmad (read-only) |
| `7be9bc9` | chore: harness inicial — .gitignore + CLAUDE.md + constitution |

SHA completo do commit mais recente da cadeia (usado como baseline):
`6a0d297717c70f5024597f58f13088e83bc78b7f`

### Política operacional (vinculante a partir de 2026-04-10)

1. **Nenhum commit futuro** pode usar `smoke-test-user <smoke@test.local>`
   ou qualquer outra identidade fora de `.claude/allowed-git-identities.txt`.
   Sem exceção. O `pre-commit-gate.sh` (item 1.7) bloqueia mecanicamente.
2. O `scripts/guide-check.sh` CHECK-3 audita o range
   `${baseline}..HEAD` lendo o SHA de `.claude/git-identity-baseline`.
   Commits anteriores ao baseline são considerados débito anistiado.
3. Para mover o baseline (avançar a fronteira de auditoria):
   - Editar `.claude/git-identity-baseline` manualmente em terminal externo
   - Rodar `KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh`
   - O baseline é arquivo selado (settings-lock.sh) — robô não pode movê-lo.
4. O smoke-test-hooks.sh continua usando a identidade `smoke-test-user`
   para fixtures de teste, mas:
   - Injeta a identidade temporariamente no allowlist via append
   - Rola back o append no fim da seção
   - **Nunca cria commit real** com essa identidade

### Plano de remoção do débito

Não há plano. Os 8 commits permanecem na história permanentemente. A política
acima garante que o débito não cresce. Se um dia os PRs antigos forem
abandonados/refeitos, esta seção pode ser removida.

---

## Symlinks no smoke-test em Windows sem admin

### Contexto

O caso de teste 1.5 ("verifier BLOCK em symlink fora do sandbox") em
`scripts/smoke-test-hooks.sh` é pulado quando rodado em Git Bash no Windows
sem privilégios de admin (ou Developer Mode), porque `ln -s` cai para cópia
ao invés de criar symlink real. O teste valida a presença de symlink real
via `[ -L file ]` antes de rodar.

### Política

- Em Linux/macOS: o teste roda normalmente.
- Em CI (qualquer SO): o teste roda normalmente (admin/devmode disponível).
- Em Windows local sem privilégios: o teste é pulado com aviso explícito,
  contagem total de smoke-test fica em 75/75 ao invés de 76/76.
- A canonicalização real (item 1.5 — `realpath -m`) está coberta pelo caso
  60 ("path traversal verification-input/../../etc/passwd"), que não depende
  de symlinks.

---

## Política operacional 2026-04-10: congelamento de admin bypass

### Contexto

Durante o Bloco 1 da meta-auditoria #1, o PM usou o admin bypass do ruleset
de `main` para desbloquear o push de hooks selados. Três envios diretos foram
feitos, todos registrados em `docs/incidents/bloco1-admin-bypass-2026-04-10.md`
e autorizados pelo próprio PM no mesmo arquivo de incidente.

A meta-auditoria #2 (completude, 2026-04-10) recomendou congelar novos
admin bypasses até que o Bloco 5 item 5.3 ("ruleset de `main` endurecido,
remover `current_user_can_bypass`") feche em definitivo o caminho.

### Regra vigente desde 2026-04-10

1. **Zero novos envios diretos autorizados pelo dono do repositório** até o
   Bloco 5 item 5.3 fechar.
2. **Única exceção:** incidente classificado P0 em `docs/incidents/` com
   assinatura do PM **dentro do próprio arquivo de incidente** declarando
   que o admin bypass foi usado e por quê.
3. **Teto absoluto: 5 envios diretos totais**, contando os 3 já usados.
   Se o teto for atingido, o projeto **pausa** e entra em re-auditoria
   externa antes de qualquer novo slice.
4. Todo uso de exceção registra-se no contador oficial mantido em
   `docs/incidents/bloco1-admin-bypass-2026-04-10.md` (campo
   "Contador oficial").
5. A tentativa de usar admin bypass sem incident file é tratada como
   **incidente crítico** de processo e gera retrospectiva obrigatória.

### Plano de remoção

Esta seção só pode ser removida quando:
- O Bloco 5 item 5.3 estiver concluído (ruleset de `main` endurecido)
- A meta-auditoria subsequente confirmar que `current_user_can_bypass`
  foi removido do ruleset e o contador final ficou congelado

---
