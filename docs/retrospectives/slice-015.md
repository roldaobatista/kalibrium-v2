# Retrospectiva slice-015

**Data:** 2026-04-17
**Resultado:** approved (merged via PR #36)
**Fonte numérica:** [slice-015-report.md](slice-015-report.md)

## Contexto
Slice 015 = Spike INF-007 (story E15-S01): investigação documental que mapeia reaproveitamento do backend Laravel (E01/E02/E03) para a PWA offline-first (E15) e valida versões pinnadas da stack frontend. Produz 2 documentos vivos em `docs/frontend/` + PoC descartável em `spike-inf007/` + 8 testes Pest (40 assertions).

## Números (resumo)

| Métrica | Valor |
|---|---|
| Gates principais aprovados | 5/5 (verify, code-review, security, audit-tests, functional) |
| Gates de planejamento aprovados | 3/3 (spec-audit, plan-review, tests-draft-audit) |
| Master-audit dual-LLM | Consenso pleno 2× Opus 4.7, zero divergência |
| ACs | 6 (todos com rastreabilidade ADR-0017: @covers, test('AC-NNN:…'), ->group()) |
| Testes Pest | 8 passed, 40 assertions, 0.24s |
| Findings bloqueantes | 0 em todos os gates |
| Findings não-bloqueantes | 12 S4 + 2 S5 (transparência sobre débitos diferidos a E15-S02) |
| Código de produção alterado | 0 linhas (spike documental puro) |
| Commits no PR final | 2 (slice content + compat bi-schema) |
| PRs abertos | 2 (#35 abandonado por conflito com main; #36 mergeado) |
| Tentativas de merge | 2 |

## O que funcionou

- **Disciplina de escopo:** `git diff main...HEAD -- app/ routes/ database/ resources/` retorna vazio. O spike não vazou para código de produção mesmo com 8 gates rodando.
- **Rastreabilidade AC-ID (ADR-0017):** 6 ACs mapeiam 1:1 para 8 testes com tripla marcação (nome do teste, `@covers`, `->group`). Primeira story completa sob ADR-0017 passou sem ajustes.
- **Dual-LLM 2× Opus em contextos isolados:** Trilha A (governance) e Trilha B (sub-agente Opus em instância separada) aprovaram de forma independente com zero rodadas de reconciliação. Substituir GPT-5/Codex por uma segunda instância Opus eliminou a fricção do setup Windows/ChatGPT Plus sem perder o isolamento R3/R11.
- **Bi-schema pragmático:** adicionar campos do schema legado aos JSONs v1.2.2 (via `setdefault` + `findings_v1_2_2`) permitiu o merge-slice de main passar sem bloquear o merge do slice em schema mismatch, enquanto preserva o payload canônico v1.2.2 para auditorias futuras.

## O que não funcionou

- **Branch `work/offline-discovery-2026-04-16` envelheceu durante a pausa** e divergiu de main (que recebeu a migração oficial do protocolo v1.2.2). O merge originou ~15 conflitos, incluindo em arquivo selado `scripts/hooks/MANIFEST.sha256`. Primeiro PR (#35) foi abandonado; nova branch `feat/slice-015-spike-inf007` criada a partir de main com cherry-pick recuperou o trabalho.
- **Line endings CRLF no Windows quebraram `hooks-lock --check`:** `sha256sum -c MANIFEST.sha256` falhou porque `autocrlf=true` deixou os `.sh` e o próprio MANIFEST em CRLF. Exigiu normalização manual com `sed -i 's/\r$//'` antes do merge-slice.sh aceitar a branch.
- **Merge-slice.sh em main ainda espera schema legado** (slice_id, violations, findings, severity_summary, lgpd_checks, ac_coverage, ac_assessment) enquanto os gates deste slice emitiram gate-output-v1 (protocolo v1.2.2). Foi resolvido com bi-schema, mas é **débito HARNESS-MIGRATION pendente**: o script precisa ser migrado formalmente.
- **Telemetria zerada:** `.claude/telemetry/slice-015.jsonl` existe mas relatórios vieram 0 commits / 0 approved / 0 tokens. Sub-agents isolados provavelmente não estão emitindo eventos para o JSONL do slice.

## Gates que dispararam em falso

- Nenhum falso positivo observado.
- `hooks-lock --check` falhou legitimamente por drift de line endings (não é falso positivo — o hash realmente diverge quando CRLF é interpretado).

## Gates que deveriam ter disparado e não dispararam

- **Gate de compatibilidade schema legado ↔ v1.2.2:** ideal seria o próprio `merge-slice.sh` reconhecer ambos os schemas (ou um script de pré-validação sinalizar a divergência antes de chegar no merge). A descoberta só aconteceu na hora do merge-slice.
- **Gate de sincronia com main:** não há hook que verifique se a branch atual está atrasada em relação a main antes de começar um slice novo. A divergência grande que causou o problema do PR #35 só ficou visível no `git merge`.

## Mudanças propostas

- [ ] **Migrar `scripts/merge-slice.sh` para gate-output-v1** (ler `gate`, `findings_by_severity`, `blocking_findings_count`). Débito HARNESS-MIGRATION-004 — adicionar em `docs/guide-backlog.md`.
- [ ] **Adicionar `.gitattributes` forçando LF** para `scripts/hooks/**` e `docs/protocol/schemas/**` para que `sha256sum` não quebre em clones Windows. Débito HARNESS-MIGRATION-005.
- [ ] **Hook `session-start.sh` ou `pre-commit-gate.sh` checar sincronia com `origin/main`** e avisar quando a branch está > N commits atrás. Débito HARNESS-MIGRATION-006.
- [ ] **Telemetria dos sub-agents isolados** — verificar por que `slice-015.jsonl` ficou vazio. Pode ser que o Agent tool (worktree/foreground) não esteja herdando o pipe de `.claude/telemetry/`. Débito HARNESS-MIGRATION-007.
- [ ] **Atualizar ADR-0012 + `docs/operations/codex-gpt5-setup.md`** para refletir a nova política dual-LLM = 2× Opus 4.7 (GPT-5 descontinuado como Trilha B).

## Lições para o guia

- **Branches longas são dívida silenciosa.** Quando o projeto fica pausado, a branch de trabalho precisa de `git merge origin/main` periódico ou um hook que alerte. Caso contrário, todo retomar vira uma sessão de resolução de conflitos — e quando há arquivo selado no meio, pode ser bloqueante.
- **2× Opus em contextos isolados satisfaz dual-LLM sem o overhead cross-vendor.** A exigência do protocolo v1.2.2 (E10) é "duas trilhas independentes", não "dois modelos diferentes". Usar duas instâncias Opus no Agent tool (worktree/subagent) é suficiente para R3/R11. Ver `feedback_dual_llm_two_opus.md`.
- **Schemas migrados precisam de compat ou migração total — não parcial.** O projeto ficou num estado intermediário (protocolo publicado v1.2.2, scripts ainda v1), e isso criou fricção silenciosa. Quando a migração começar, precisa terminar no mesmo PR ou ter rollback claro.
- **CRLF no Windows é risco real para arquivos hash-locked.** Qualquer arquivo listado em `MANIFEST.sha256` precisa ter line-ending fixo no `.gitattributes` ou vai quebrar o hook em máquinas Windows.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R16 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
