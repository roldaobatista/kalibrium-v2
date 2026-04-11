# Meta-audit action plan — tracker de progresso

**Origem:** `docs/audits/meta-audit-2026-04-10-action-plan.md` (commit `345b0a2`)
**Decisão do PM:** `docs/decisions/pm-decision-meta-audit-2026-04-10.md` (commit `345b0a2`)
**Status geral:** ✅ Bloco 1 completo — meta-audit #2 sessão 02 em andamento — Blocos 8+9 ✅ aceitos pelo PM 2026-04-11 — **Bloco 10 (executability gap)** 📝 rascunho aguardando decisão PM 2026-04-11
**Última atualização:** 2026-04-11

## Legenda

- `[ ]` = não iniciado
- `[~]` = em andamento
- `[x]` = completo (data + commit curto)
- `[!]` = bloqueado (ver incident)

---

## Bloco 0 — Alinhamento com o PM ✅

**Status:** ✅ completo — 2026-04-10
**Evidência:** `docs/decisions/pm-decision-meta-audit-2026-04-10.md`

- [x] 0.1 Aceitar escopo completo — 2026-04-10
- [x] 0.2 Aprovar consultor metrologia — 2026-04-10
- [x] 0.3 Aprovar consultor fiscal — 2026-04-10
- [x] 0.4 Aceitar pausa dura — 2026-04-10
- [x] 0.5 Aceitar Dia 1 em harness — 2026-04-10

---

## Bloco 1 — Selar o harness contra auto-modificação ✅

**Status:** ✅ completo — 2026-04-10 (commitado e pushado)
**Pré-requisito:** Bloco 0 ✅
**Critério de pronto:** `/guide-check` VERDE (0 findings, baseline `6a0d297` + 11 commits auditados no range) + `smoke-test-hooks.sh` 75/75 verdes (1 skip honesto: symlink test em Windows sem admin) + 2 commits ordenados:
- Commit A `345b0a2` — `docs(meta-audit)` — pré-requisito narrativo
- Commit B `c061e3c` — `chore(harness): selar contra auto-modificação (bloco 1 meta-audit)`

**Pushado via admin bypass** — ver `docs/incidents/bloco1-admin-bypass-2026-04-10.md` (commit C `a1ad97e`). Bypass count acumulado: 3. Caminho fechado pelo Bloco 5 item 5.3.

- [x] 1.1 Hash-lock de `.claude/settings.json` + `settings-lock.sh` — 2026-04-10 (commit-pending)
- [x] 1.2 Hash-lock de `scripts/hooks/*` + `MANIFEST.sha256` + `hooks-lock.sh` — 2026-04-10 (commit-pending)
  - **Adição PM 2026-04-10:** o próprio `MANIFEST.sha256` é selado via `settings-lock.sh` (PreToolUse Edit|Write bloqueia o arquivo) — fecha o caminho de "robô edita hook + regenera manifesto no mesmo commit". Atualização do manifesto requer `scripts/relock-harness.sh` que exige TTY interativa + variável `KALIB_RELOCK_AUTHORIZED` setada manualmente pelo PM em terminal externo + confirmação por digitação `RELOCK` + criação automática de `docs/incidents/harness-relock-*.md`.
- [x] 1.3 Append-only telemetria com hash-chain + `record-telemetry.sh` + `telemetry-lock.sh` + `telemetry.schema.json` — 2026-04-10 (commit-pending)
- [x] 1.4 Fail-closed no `verifier-sandbox.sh` (detecção multi-sinal de contexto sub-agent quando `CLAUDE_AGENT_NAME` vazio: `.git` arquivo + comparação toplevel vs main worktree) — 2026-04-10 (commit-pending)
- [x] 1.5 `realpath -m` resolve symlinks/.. antes de comparar caminhos (fecha symlink + traversal) — 2026-04-10 (commit-pending)
- [x] 1.6 Cobertura de `Bash` no `verifier-sandbox.sh` + novo hook `sealed-files-bash-lock.sh` (observação PM: bloqueia `echo >`, `sed -i`, `tee`, `cp`, `mv` em arquivos selados) — 2026-04-10 (commit-pending)
- [x] 1.7 `.claude/allowed-git-identities.txt` + validação em `pre-commit-gate.sh` + selo via `settings-lock.sh` + smoke-test usa padrão (a) backup/mutate/restore — 2026-04-10 (commit-pending)
- [x] 1.8 `verify-slice.sh` e `review-slice.sh` rodam `hooks-lock.sh --check` + `settings-lock.sh --check` antes de spawnar sub-agent (enforcement real, substitui instrução-de-prompt) — 2026-04-10 (commit-pending)
- [x] 1.9 `sanitize-input.sh` (blocklist EN+PT + envelope XML CDATA) integrado em `verify-slice.sh prepare` e `review-slice.sh prepare` — 2026-04-10 (commit-pending)

---

## Bloco 2 — Decidir a stack (ADR-0001)

**Status:** ⏸ bloqueado por Bloco 1

- [ ] 2.1 Rodar `/decide-stack` com ≥2 alternativas → `docs/adr/0001-stack.md`
- [ ] 2.2 Endurecer `block-project-init.sh` (bloquear Write direto em `package.json`, `tsconfig.json`, etc.)
- [ ] 2.3 Gate de ADR-0001 no `session-start.sh` (modo `harness-bootstrap-only`)
- [ ] 2.4 `docs/stack.json` canônico (test_cmd, lint_cmd, type_cmd, affected_test_cmd, coverage_cmd)

---

## Bloco 3 — Gates reais de execução de teste

**Status:** ⏸ bloqueado por Bloco 2

- [ ] 3.1 Execução real dos testes no `verify-slice.sh` → `test-output.json` imutável hash-verificado
- [ ] 3.2 `ac-red-check.sh` — P2 de verdade (rejeita teste AC que nasce verde)
- [ ] 3.3 `post-edit-gate.sh` obrigatório por arquivo (WARN → die para `src/**`, allowlist para docs/config)
- [ ] 3.4 `pre-push-gate.sh` roda testsuite do domínio de verdade
- [ ] 3.5 `validate-ac-coverage.sh` valida `ac-list.json` vs `ac-coverage-map.json`

---

## Bloco 4 — Tradutor PM + pausa dura

**Status:** ⏸ bloqueado por Bloco 3

- [ ] 4.1 `explain-slice.sh` real (traduz categorias técnicas → linguagem de produto)
- [ ] 4.2 `check-r12-vocabulary.sh` (blocklist técnica em outputs PM)
- [ ] 4.3 Canal duplo P7 + R12 (`technical-evidence.json` + `pm-report.md`)
- [ ] 4.4 Reviewer e verifier em paralelo (fechar vazamento do "prior positivo")
- [ ] 4.5 Pausa dura em categorias críticas + `docs/policies/r6-r7-policy.md`
- [ ] 4.6 Log automático de "PM aprovou override" em `docs/incidents/pm-override-NNN.md`

---

## Bloco 5 — Juiz externo (CI + GitHub Action)

**Status:** ⏸ bloqueado por Bloco 4

- [ ] 5.1 `.github/workflows/ci.yml` (lint + types + test + smoke-hooks + harness-integrity)
- [ ] 5.2 `.github/workflows/auto-approve.yml` + GitHub App `kalibrium-auto-reviewer`
- [ ] 5.3 Ruleset de `main` endurecido (remover `current_user_can_bypass`)
- [ ] 5.4 Fechar B-009 do guide-backlog; admin bypass só em `hotfix/*`

---

## Bloco 6 — Defesas adicionais

**Status:** ⏸ bloqueado por Bloco 5

- [ ] 6.1 Sub-agent `domain-expert` + `.claude/agents/domain-expert.md` + `specs/NNN/domain-review.json`
- [ ] 6.2 `scripts/metrics-check.sh` (complexidade, duplicação, dead code, coverage ≥80%)
- [ ] 6.3 `/decide-stack` + validador exigindo ≥2 alternativas (`validate-decide-output.sh`)
- [ ] 6.4 `scripts/hooks/mcp-check.sh` + `.claude/allowed-mcps.txt`
- [ ] 6.5 Incorporar golden tests da trilha paralela (metrologia + fiscal)

---

## Bloco 7 — Re-auditoria em sessão nova (go/no-go do Dia 1)

**Status:** ⏸ bloqueado por Bloco 6

- [ ] 7.1 Re-auditoria interna em sessão nova → `docs/audits/meta-audit-revalidation-YYYY-MM-DD.md`
- [ ] 7.2 Smoke test integração `specs/000-smoke/` (7 cenários de ataque)
- [ ] 7.3 Decisão formal Dia 1 → `docs/decisions/day1-go-no-go-YYYY-MM-DD.md` com 4 checkboxes

---

## Blocos 8 e 9 — Gaps derivados de guias externos (extensão 2026-04-11)

**Status:** ✅ **aceito pelo PM em 2026-04-11** — execução em sessão nova
**Plano completo:** [`docs/audits/progress/external-guides-action-plan.md`](external-guides-action-plan.md)
**Decisão formal:** [`docs/decisions/pm-decision-external-guides-2026-04-11.md`](../../decisions/pm-decision-external-guides-2026-04-11.md)
**Origem:** análise do documento externo `C:\PROJETOS\saas\Harness + Spec-Driven Development.md` (6 perspectivas consolidadas de Anthropic/OpenAI/Spec Kit/Cursor/Harness.io/acadêmico).
**Sessão de análise e decisão:** 2026-04-11.
**Próxima execução:** sessão nova, itens 9.1 e 9.3 em paralelo (auditorias sem dependência).

### Bloco 8 — 8 gaps endereçáveis

- [ ] 8.1 Skill `/clarify-slice` (etapa formal de clarificação de spec) — pós-Bloco 2, não selado
- [ ] 8.2 Eval suite / benchmark de regressão — pós-Bloco 3, **SELADO** (pre-commit-gate)
- [ ] 8.3 Observabilidade estruturada dos sub-agents (traces com hash-chain) — pós-Bloco 5, **SELADO**
- [ ] 8.4 Cleanup workflow recorrente (garbage collection automático) — pós-Bloco 5, parcial
- [ ] 8.5 Feedback loop <30s como KPI do harness — pós-Bloco 3, depende de auditoria
- [ ] 8.6 `docs/environment-setup.md` consolidado — pós-Bloco 2, não selado
- [ ] 8.7 `observability/` versionado no repo — pós-deploy, futuro
- [ ] 8.8 Feature flags (decouple deploy/release) — pós-produção, futuro

### Bloco 9 — 3 auditorias focadas (todas em sessão nova)

- [ ] 9.1 Auditar `validate-review.sh` / `validate-verification.sh` (mechanical diff check?)
- [ ] 9.2 Auditar ADR-0002 MCP Policy (code-exec ou tool-spam?)
- [ ] 9.3 Auditar tamanho e escopo do `CLAUDE.md` (curto + docs/ vs carga PM-only?)

**Restrições operacionais obrigatórias** (detalhadas no plano externo §1):
1. Nenhum item toca selados sem passar por `relock-harness.sh` em terminal externo pelo PM.
2. Auditorias do Bloco 9 **nunca** rodam na mesma sessão que o código que auditam (viés confirmatório).
3. R9 zero bypass aplicado a todos os gates novos.
4. Toda saída de decisão PM passa por R12.

**Ação do PM (concluída 2026-04-11):** resposta `(a)` — aceitou integralmente como extensão oficial. Registro em `docs/decisions/pm-decision-external-guides-2026-04-11.md`.

**Próxima ação do agente (sessão nova):** abrir sessão Claude Code fresh, ler a decisão + plano externo, executar 9.1 e 9.3 em paralelo, produzir os entregáveis em `docs/audits/internal/validate-scripts-audit-YYYY-MM-DD.md` e `docs/audits/internal/claude-md-sizing-audit-YYYY-MM-DD.md`.

---

## Bloco 10 — Executability Gap (extensão 2026-04-11)

**Status:** 📝 **rascunho — aguardando decisão do PM**
**Plano completo:** [`docs/audits/progress/executability-gap-action-plan.md`](executability-gap-action-plan.md)
**Template de decisão:** [`docs/decisions/pm-decision-executability-gap-TEMPLATE-2026-04-11.md`](../../decisions/pm-decision-executability-gap-TEMPLATE-2026-04-11.md)
**Origem:** pergunta do PM 2026-04-11 — *"nesse ambiente tem o risco do sistema ser construído e não conseguirmos executar os fluxos do prd, funções etc?"*
**Restrição de desenho:** PM = não-desenvolvedor; única validação humana possível = walkthrough em navegador (`CLAUDE.md §3.1`).
**Princípio P10 proposto:** *"Se o PM não pode clicar, não está pronto."*

### Gap estrutural identificado

Harness atual (Blocos 0-9) garante gates de slice/AC mas **não garante** que: o produto boota, fluxos PRD atravessam o sistema integrado, PM toca o produto em tela, slices compõem ou UI bate com a intenção do PM. Dia 1 pode fechar com todos os gates verdes e zero execução real.

### Os 5 itens do Bloco 10

- [ ] 10.1 Walking skeleton obrigatório no Slice 1 — **SELADO** (hook `block-slice-without-skeleton.sh`), depende de Bloco 2 + ADR-0003
- [ ] 10.2 Gate de fluxo-PRD ponta-a-ponta — parcial **SELADO** (modifica `verify-slice.sh`), depende de Bloco 2 + Bloco 3 + 10.1
- [ ] 10.3 **PM Browser Walkthrough Gate (peça central)** — **SELADO** (hook `block-merge-without-walkthrough.sh`), depende de 10.1 + Bloco 4
- [ ] 10.4 Demo environment sempre ligado — não selado, depende de Bloco 2 + Bloco 5 + ADR-0004
- [ ] 10.5 Visual regression + screenshot baseline — parcial **SELADO**, depende de 10.1 + 10.3

### Alterações em artefatos vigentes

- `docs/constitution.md §3` (DoD mecânica) — 3 itens novos (skeleton, flow coverage, walkthrough)
- `docs/constitution.md §2` — princípio **P10** (executabilidade antes de "pronto")
- `CLAUDE.md §6` (fluxo padrão) — passo 14 muda para incluir walkthrough
- `CLAUDE.md §7` — comandos `/walkthrough NNN` e `/skeleton-gate`
- `CLAUDE.md §8` — sub-agent novo `e2e-driver`

### Restrições operacionais (mesmas dos Blocos 8+9)

1. Nenhum item toca selados sem `relock-harness.sh` em terminal externo pelo PM.
2. Auditoria deste plano (item **9.4** novo) roda em sessão nova — este arquivo foi escrito na mesma sessão em que o risco foi levantado pelo PM, viés confirmatório é inevitável.
3. R9 zero bypass.
4. R12 aplicado a toda saída PM (walkthrough files, mensagens de bloqueio, explain-slice).
5. Admin bypass do PM **não pode** pular walkthrough — walkthrough é o próprio mecanismo pelo qual o PM exerce aprovação.

### Próxima ação

**Aguarda decisão do PM.** Template pronto em `docs/decisions/pm-decision-executability-gap-TEMPLATE-2026-04-11.md`. Três opções:
- **(a)** Aceito integralmente como extensão oficial (análogo aos Blocos 8+9).
- **(b)** Aceito com recortes — marca quais dos 5 itens aceita, justifica rejeições.
- **(c)** Rejeita — registra justificativa e PM aceita conscientemente o risco residual.

Enquanto o PM não decide, o Bloco 10 fica em rascunho e **não altera a sequência atual do tracker principal** (próxima execução real ainda é Bloco 2 via `/decide-stack` em sessão nova).

---

## Trilha paralela — Compliance regulado

**Status:** 🟡 aguardando RFP do PM (pode começar imediatamente)
**Independente dos blocos técnicos.**

### Metrologia (aprovada em decisão 0.2)

- [ ] M1 RFP consultor metrologia (perfil RBC, GUM/JCGM 100:2008, ISO 17025)
- [ ] M2 Contratar consultor
- [ ] M3 50 casos GUM validados (`tests/golden/metrology/gum-cases.csv`)
- [ ] M4 `tests/golden/metrology/` + test runner
- [ ] M5 Hook `pre-push-gate` bloqueia sem golden verde quando diff toca `src/metrology/**`
- [ ] M6 `docs/compliance/metrology-policy.md`

### Fiscal (aprovada em decisão 0.3)

- [ ] F1 RFP consultor contábil (NF-e/NFS-e multi-UF, reforma tributária)
- [ ] F2 Contratar consultor
- [ ] F3 30 casos NF-e/NFS-e/ICMS por UF (CSV)
- [ ] F4 `tests/golden/fiscal/` + runner
- [ ] F5 Hook + `docs/compliance/fiscal-policy.md`

### REP-P / LGPD / ICP-Brasil

- [x] Registrar como fora do MVP em `docs/compliance/out-of-scope.md` — 2026-04-10 (7647bb1, item 1.5.10 da meta-audit #2)

---

## Meta-auditoria #2 — Sessão 01 (execução do plano de ação)

**Data:** 2026-04-10 → 2026-04-11
**Relatório:** `docs/reports/execution-meta-audit-2-2026-04-10-session01.md`
**Escopo da sessão:** executar tudo o que o agente podia entregar sem tocar em arquivo selado e sem depender do Bloco 2.

### Resumo de entrega

| Passo | Itens entregues | Status |
|---|---|---|
| A — Consensuais (X2, X4) | 2/2 | ✅ |
| B — Bloco 1.5 Estado 1 | 13/15 (faltam 1.5.11 e 1.5.14 pending-block-2) | ✅ |
| C — Trilha #2 Estado 1 | 13/16 (T2.6/7/8 pending-block-2; T2.1-T2.5 em draft-awaiting-dpo) | ✅ |
| D — Trilha #3 Estado 1 | 3/12 (os 9 restantes dependem do Bloco 2) | ✅ |
| E — C1, C2, C3 | 3/4 (C4 = ação manual do PM) | ✅ |
| F — A1, A2 | 2/4 (A3 e A4 = ação manual do PM) | ✅ |
| G — Micro-ajustes não bloqueantes | 5/22 (os demais dependem de Bloco 2 ou relock) | ✅ |
| H — Relatório + 5 progress files + atualização deste tracker | 1/1 | ✅ |

### Trackers específicos

Os 5 trackers granulares desta sessão estão em:

- `docs/audits/progress/block-1.5-product-foundation.md`
- `docs/audits/progress/trilha2-compliance-produto.md`
- `docs/audits/progress/trilha3-operacao-producao.md`
- `docs/audits/progress/operational-immediate.md`
- `docs/audits/progress/adjustments-blocks-2-7.md`

### Ações manuais do PM abertas após esta sessão

Lista detalhada em `docs/reports/pm-manual-actions-2026-04-10.md`:

1. **C4** — selar `docs/harness-limitations.md` no MANIFEST via relock manual.
2. **A3** — adicionar gate de advisor-review ao `pre-commit-gate.sh` via relock manual.
3. **A4** — negociar e assinar NDA + proposta comercial do advisor técnico externo.
4. **DPO** — contratar DPO fracionário para assinar e promover os 5 arquivos em `draft-awaiting-dpo`.

### Próximo passo único

**Executar o passo 1 de `docs/reports/pm-manual-actions-2026-04-10.md`** (selar `docs/harness-limitations.md` no MANIFEST). Isso fecha o C4 e protege a política de congelamento de admin bypass contra auto-edição pelo agente.

---

## Meta-auditoria #2 — Sessão 02 (continuação do plano de ação)

**Data:** 2026-04-11
**Relatório:** `docs/reports/execution-meta-audit-2-2026-04-10-session02.md`
**Escopo da sessão:** itens independentes abertos pela sessão 01 que não exigem relock, não dependem do Bloco 2 e não precisam de ação humana externa. Nenhum push. Nenhum arquivo selado tocado. Nenhum uso de admin bypass.

### Resumo de entrega

| Item do plano | Entregável | Commit |
|---|---|---|
| 4.8 | `docs/policies/r6-r7-policy.md` — categorias sem override (cálculo, conformidade, segurança crítica) | `956708b` |
| 6.5 | `docs/policies/cooldown-policy.md` — 24h entre commits em classes críticas | `ecadcf2` |
| 6.8 | seção "Edição externa de hooks por humano fora do Claude Code" em `docs/harness-limitations.md` (a outra seção exigida, admin bypass, já havia sido adicionada pela sessão 01) | `141f860` |
| ajuste | `docs/audits/progress/adjustments-blocks-2-7.md` — reclassifica 6.6/6.7 como pending-block-2 e atualiza contador 5/22 → 8/22 | `5621c7a` |

Cada item foi revisado por um segundo sub-agent em contexto isolado (R11) com orçamento de 30k tokens, veredito `ok` antes do commit, e varrido contra a lista de marcadores proibidos do plano.

### Ações manuais do PM — estado entre sessões

Nenhuma das quatro ações manuais abertas pela sessão 01 foi concluída entre sessões. `docs/reports/pm-manual-actions-2026-04-10.md` permanece com status `aberto`. Resumo:

| Ação | Descrição | Estado |
|---|---|---|
| C4 | Selar `docs/harness-limitations.md` no MANIFEST via relock manual | aberto |
| A3 | Gate de `advisor-review` no `pre-commit-gate.sh` via relock manual | aberto |
| A4 | NDA + proposta comercial do advisor técnico externo | aberto |
| DPO | Contratar DPO fracionário que vai assinar os 5 arquivos `draft-awaiting-dpo` | aberto |

Evidências: `docs/reviews/` ainda não existe, `docs/decisions/` contém apenas `pm-decision-meta-audit-2026-04-10.md`, e `docs/harness-limitations.md` continua não selado no MANIFEST (motivo pelo qual a sessão 02 pôde adicionar a seção 6.8).

### Arquivos `draft-awaiting-dpo`

Os 5 arquivos (T2.1 a T2.5) permanecem em `draft-awaiting-dpo`. Nenhuma promoção foi feita nesta sessão porque o DPO ainda não foi contratado.

### Push

**Os 4 commits desta sessão ficam em `main` local, sem push.** O envio fica congelado até:
- o Bloco 5 item 5.3 remover `current_user_can_bypass` do ruleset, ou
- o PM autorizar explicitamente o consumo do último bypass (4/5 → 5/5), apenas para incidente P0 assinado.

### Próximo passo único

**Executar o passo 1 de `docs/reports/pm-manual-actions-2026-04-10.md`** (selar `docs/harness-limitations.md` no MANIFEST). Esse passo fecha o C4, protege as duas novas seções da sessão 02 (edição externa de hooks + admin bypass) contra alteração pelo agente, e desbloqueia o fechamento definitivo do Bloco 1.5 (item 1.5.14).

---

## Operacional — congelamento de admin bypass (item C3 da meta-audit #2)

**Congelamento de admin bypass ativo desde 2026-04-10.** Contador oficial: **4/5** (inalterado pela sessão 02 — nenhum envio direto foi feito; último uso registrado foi o push da sessão 01 em 2026-04-11). Política vigente em `docs/harness-limitations.md §Política operacional 2026-04-10: congelamento de admin bypass`. Exceção permitida: incidente classificado P0 com assinatura do PM dentro do próprio arquivo de incidente. Teto absoluto: 5 envios diretos totais. Restam **1 bypass** disponível, e apenas mediante incident P0 assinado pelo PM. Se atingir 5/5, o projeto pausa para re-auditoria externa antes de qualquer novo slice.

Incident file com o contador oficial: `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Contador oficial (após política de congelamento 2026-04-10)`.

---

## Como atualizar este arquivo

Ao completar um item:
- Trocar `[ ]` por `[x]`
- Adicionar data e hash curto do commit: `[x] 1.1 ... — 2026-04-11 (a3f2d8e)`

Ao iniciar um bloco:
- Trocar status para `[~] em andamento` no cabeçalho do bloco
- Atualizar **Status geral** no topo do arquivo

Ao bloquear:
- Trocar por `[!]` + link para `docs/incidents/block-N-failure-YYYY-MM-DD.md`

Ao completar um bloco:
- Trocar status para ✅ completo com data
- Atualizar **Status geral** no topo do arquivo para o próximo bloco
- Commitar este arquivo como parte do mesmo commit que fechou o bloco
