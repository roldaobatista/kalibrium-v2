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

## Edição externa de hooks por humano fora do Claude Code

### Contexto

A partir do Bloco 1 da meta-auditoria #1 (2026-04-10), todos os arquivos
críticos do harness — `scripts/hooks/*.sh`, `.claude/settings.json`,
`.claude/settings.json.sha256`, `scripts/hooks/MANIFEST.sha256`,
`.claude/allowed-git-identities.txt`, `.claude/git-identity-baseline` e
`.claude/telemetry/*` — são **selados**. O agente Claude Code não consegue
editá-los via `Edit`, `Write` ou `Bash` (`echo >>`, `sed -i`, `tee`, `cp`,
`mv`). Os hooks `settings-lock.sh`, `hooks-lock.sh`, `telemetry-lock.sh` e
`sealed-files-bash-lock.sh` bloqueiam mecanicamente todas as tentativas.

**A limitação:** o PM, em terminal externo (fora do Claude Code), **pode**
editar qualquer um desses arquivos com um editor de texto comum, rodar o
procedimento de relock (`KALIB_RELOCK_AUTHORIZED=1 bash
scripts/relock-harness.sh`), e o harness aceita a alteração como legítima.
Isso é **esperado** — o relock manual é o único caminho documentado para
evoluir hooks (CLAUDE.md §9). Mas significa que o PM pode, em tese,
enfraquecer ou remover uma regra de enforcement e o harness seguirá rodando
a versão enfraquecida a partir do próximo `SessionStart`.

Este documento aceita essa capacidade como **limitação documentada** e não
como falha. A defesa não é impedir o PM — o PM é o dono. A defesa é deixar
a alteração **rastreável** e **auditável**.

### Por que não é falha

O papel do harness é proteger contra:

- **Deriva do próprio agente** (o Claude Code modificar hooks sorrateiramente
  para facilitar o próprio trabalho). Isso é coberto pelos hooks de
  selamento, que bloqueiam mecanicamente qualquer tentativa do agente.
- **Mudança não-intencional** (um sub-agent editar um hook por engano).
  Isso também é coberto pelos hooks de selamento.

O papel do harness **não é** proteger contra o PM tomando uma decisão
deliberada, em outro terminal, com acesso administrativo ao próprio
repositório. Essa barreira exigiria infraestrutura externa (assinatura de
commit por chave física, revisão obrigatória por segundo humano, separação
de ambiente de desenvolvimento de ambiente de produção) que está fora do
escopo de um harness de agente.

Quando o PM é, por definição operacional, o único humano e o dono do
projeto (CLAUDE.md §3.1), a única defesa aplicável é a **rastreabilidade**.

### Política operacional

1. **Toda edição externa de hook ou de arquivo selado exige rodar o
   `relock-harness.sh`.** O script é autocontido: exige
   `KALIB_RELOCK_AUTHORIZED=1`, TTY real, digitação literal `RELOCK`, e
   cria automaticamente `docs/incidents/harness-relock-<timestamp>.md`
   com o operador, host, hashes antes/depois, lista de hooks alterados
   e motivo declarado.
2. **O arquivo de incidente de relock é obrigatório.** Não existe relock
   sem registro. O próprio `relock-harness.sh` cria o arquivo — o PM
   preenche o campo "motivo" antes de rodar o relock ou, no máximo,
   imediatamente depois, no mesmo commit que inclui os arquivos selados
   atualizados.
3. **O diff do hook alterado é parte obrigatória da revisão pós-relock.**
   Em sessão nova do Claude Code, qualquer sub-agent com budget de
   auditoria deve conseguir ler `docs/incidents/harness-relock-*.md` e
   reconstruir o que mudou e por quê via `git log -p scripts/hooks/`.
4. **Alterações em hooks que enfraquecem enforcement exigem justificativa
   explícita no arquivo de incidente.** O PM descreve: qual regra ficou
   mais permissiva, qual era o comportamento antes, qual é o comportamento
   depois, por quanto tempo a mudança vale, e qual é o critério de
   reversão. Exemplo: reduzir o cooldown de 24h para 12h em
   `cooldown-policy.md` via edição direta do `pre-commit-gate.sh` **exige**
   um ADR aceito **e** a justificativa no arquivo de incidente.
5. **Alterações em hooks que fortalecem enforcement** (adicionam regra
   nova, fecham brecha) são aceitas com justificativa leve no arquivo de
   incidente: "adiciona check X por incidente Y" é suficiente.
6. **Auditoria periódica dos relocks passados é obrigatória no
   `guide-check.sh`.** O hook `guide-auditor` deve listar, a cada
   execução mensal, todos os `docs/incidents/harness-relock-*.md` dos
   últimos 90 dias, verificar se cada um tem motivo declarado, se cada
   um deixou rastro no `git log -p`, e se o contador de hashes
   antes/depois bate com o SHA atual dos arquivos.
7. **O PM não pode usar a edição externa para contornar uma rejeição de
   verifier/reviewer nas categorias do `docs/policies/r6-r7-policy.md`
   §3.** Se um verifier rejeitou um commit por falta de teste de cálculo
   (§3.1), editar o hook que valida o teste é uma forma de override — e
   override nessas categorias é proibido. A auditoria periódica descrita
   no item 6 detecta esse padrão e reporta como incidente crítico.

### Plano de remoção

Esta limitação **não sai** do documento. É estrutural ao modelo "agente
autônomo + humano dono do repositório". Pode ser reduzida em impacto por
medidas externas (assinatura de commit por chave física, revisão humana
adicional, separação de ambiente de desenvolvimento de ambiente de
produção) mas não eliminada.

O que pode evoluir com o tempo é o **rigor da auditoria pós-relock**, que
hoje é manual e pode, em uma iteração futura, ser automatizada por um
sub-agent independente que compara o comportamento do hook antes e depois
de cada relock e emite relatório em linguagem de produto (R12) para o PM.

---
