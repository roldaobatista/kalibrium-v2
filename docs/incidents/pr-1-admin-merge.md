# Incident — PR #1 merged via admin override sem review técnica

**Data:** 2026-04-10
**Severidade:** informativa (não é bug, é decisão operacional registrada)
**PR:** https://github.com/roldaobatista/kalibrium-v2/pull/1
**Autor do PR:** roldaobatista + Claude Opus 4.6 (1M context)
**Merger:** roldaobatista (admin bypass via updated ruleset)

---

## Contexto

O PR #1 continha:
- `fix(smoke-test): salva e restaura git config local` (BUG-001 detectado pela auditoria inicial)
- `docs(audit): audit inicial do harness — 2026-04-10 (status verde)`

O ruleset original de `main` (criado antes deste incident) exigia 1 approving review e tinha `current_user_can_bypass: never`. O próprio autor do PR não pode se auto-aprovar no GitHub, então o merge ficou bloqueado.

---

## Causa raiz

**Discrepância entre o modelo assumido pelo harness original e a realidade da equipe.**

O harness V2 foi desenhado assumindo que haveria ao menos **um humano técnico no loop** para:
- Revisar PRs tecnicamente
- Aprovar ADRs (escolha de stack, tenancy, etc.)
- Decidir escalações R6 quando o verifier reprova 2x
- Avaliar retrospectivas e propor mudanças em P/R

**Realidade:** o único humano ativo no projeto (roldaobatista) é **Product Manager, não desenvolvedor**. Não tem conhecimento técnico para fazer review substantivo de código. Forçar a review humana seria teatro (aprovaria sem entender, violando o espírito da regra pior do que simplesmente bypassar).

---

## Ação tomada

1. **Ruleset atualizado** (`PUT /repos/.../rulesets/14936750`):
   - `bypass_actors` populado com `{actor_id: 5, actor_type: "RepositoryRole", bypass_mode: "always"}` (Repository Admin)
   - `current_user_can_bypass: always` (para o owner)
   - Todas as outras regras preservadas: `deletion`, `non_fast_forward`, `pull_request` com 1 review e thread resolution

2. **Merge executado** via `gh pr merge --admin --merge 1`. Merge commit: `182a7ca`.

3. **Este incident documento** registrado permanentemente como trilha auditável.

---

## Por que o admin bypass é aceitável neste caso específico

1. É fix de **infraestrutura de teste** (smoke test), não de código de produto que afeta usuários.
2. Os **smoke tests automáticos** rodaram verdes antes do merge (50/50).
3. O bug corrigido **foi detectado pela própria auditoria** — ou seja, o harness se auto-detectou.
4. Self-review pelo mesmo Claude que escreveu o fix seria teatro (V1 falhou por isso).
5. O único humano disponível não tem capacidade técnica para review substantivo.
6. O admin bypass **fica registrado no log de auditoria do GitHub** — rastreável.

---

## Correção permanente — Fase 2 (este PR)

Este incident é o **último admin merge planejado sem compensação arquitetural**. A partir da Fase 2, o modelo muda para **"humano = Product Manager, agentes = equipe técnica completa"**:

- **Novo sub-agent `reviewer`** — contexto isolado independente do `verifier`. Executa review estrutural (duplicação, segurança, nomes, aderência ao glossary) e emite `review.json` contra `docs/schemas/review.schema.json`.
- **Nova R11** — "Dual-verifier quando humano não é técnico": verifier + reviewer (ambos em contextos isolados, sem ver output um do outro) devem ambos aprovar para merge automático.
- **Nova R12** — "Recomendações ao humano em linguagem de produto": nenhum vocabulário técnico em outputs destinados ao PM. Skills `/explain-slice` e `/decide-stack` implementam esse tradutor.
- **Skills novas** — `/review-pr`, `/explain-slice`, `/decide-stack`.
- **Ruleset mantido** mas com owner bypass permitido como última camada. No futuro (B-009), substituir o bypass manual por GitHub Action que aprova PRs automaticamente quando verifier.json + review.json commitados na branch estão ambos approved.

---

## Lições

1. **Harness precisa ser adaptado à equipe real**, não à equipe idealizada. Verificar "quem revisa?" antes de desenhar protecoes.
2. **Self-review pelo mesmo contexto é pior que admin bypass honesto.** Ambos são falhas de enforcement, mas o admin bypass é auditável; o self-review é silencioso.
3. **Rulesets do GitHub são configuráveis** via API — bypass por RepositoryRole é a ferramenta certa para admin override documentado.

---

## Referências

- Audit que detectou o BUG-001: `docs/audits/audit-initial-2026-04-10.md`
- PR #1: https://github.com/roldaobatista/kalibrium-v2/pull/1
- Ruleset atualizado: https://github.com/roldaobatista/kalibrium-v2/rules/14936750
- Constitution §4 R11 e R12 (adicionadas neste PR de fase 2)
