# Decisão PM — ADR-0012 (autonomia do agente + dual-LLM + retrospectiva + harness-learner)

**Data:** 2026-04-15
**PM:** roldao.tecnico@gmail.com
**ADR referenciada:** `docs/adr/0012-constitution-amendment-autonomy-dual-llm.md`
**Plano operacional:** `docs/audits/progress/harness-local-upgrade-action-plan.md` (draft-v4)

---

## Contexto

PM declarou em 2026-04-14/15 que:
- Não opera terminal diretamente
- Não opera Git/GitHub CLI diretamente
- Quer intervenção humana **apenas** em bloqueio real ou fim de épico
- Quer dual-LLM (Claude Opus 4.6 + melhor GPT disponível via Codex CLI)
- Tem assinatura Codex
- Autoriza harness-learner aplicar melhorias automaticamente dentro de guardrails
- Sem teto de tokens

Esta decisão formaliza a adoção da ADR-0012 como mudança constitucional estrutural.

## O que estou aprovando

### Mudanças na constitution (versão 1.4.0 → 1.5.0)

1. **§3.1** — PM redefinido como dono não-técnico, operando via `relock.bat` + GitHub UI.
2. **R11** — Dual-verifier humano+agent substituído por dual-LLM (Claude + GPT-5) consolidado pelo `master-auditor`. R11-legacy mantida apenas para decisões que afetem constitution §1-§4.
3. **R15 (nova)** — Retrospectiva automatizada pós-épico com loop corretivo de até 10 iterações.
4. **R16 (nova)** — `harness-learner` com auto-aplicação limitada por guardrails E4.

### Novos agents criados

1. `.claude/agents/master-auditor.md` — dual-LLM (Opus 4.6 + GPT-5 via Codex)
2. `.claude/agents/epic-retrospective.md` — scan pós-épico com loop 10x
3. `.claude/agents/harness-learner.md` — aprendizado com auto-aplicação

### Nova ferramenta operacional

- `relock.bat` (raiz do projeto) — clicável, digitar RELOCK, hashes atualizados

### Definição de "bloqueio real" (E5 da ADR)

PM chamado **apenas** em:
- Falha técnica LLM 3x consecutivas
- Divergência dual-LLM persistente após 3 rodadas
- Mudança que harness-learner não pode auto-aplicar (guardrails E4)
- Incidente P0/P1
- Relock manual exigido
- Fim de épico

## Guardrails do harness-learner (reforço)

**Auto-aplica sem consulta:**
- ✅ Adicionar regra nova / hook novo / deny ampliada / skill nova
- ✅ Tornar regra existente mais estrita

**Sempre escala:**
- ❌ Revogar ou afrouxar regra
- ❌ Remover hook existente
- ❌ Alterar constitution §1-§4 (P1-P9)
- ❌ Mudar `allowed-git-identities.txt` / `git-identity-baseline`

**Limites:**
- Máximo 3 mudanças auto por ciclo retrospectivo
- Toda mudança gera `docs/incidents/auto-learn-*.md`
- Auditoria trimestral externa das mudanças auto

## Riscos aceitos

1. **Drift do harness** — mitigado por guardrails E4 + auditoria trimestral + rollback via `relock.bat`
2. **Custo ~2x em auditoria** — aceito (sem teto declarado)
3. **Dependência de 2 provedores LLM** — se um cair, escala ao PM
4. **Auto-aplicação de hooks** — limitado a 3/ciclo + arquivo de incidente

## Plano de rollback

Se a ADR-0012 gerar drift inaceitável:
1. PM clica `relock.bat` → edita settings.json removendo hooks dos 3 agents
2. `git revert` da ADR-0012 e desta decisão
3. Re-auditoria externa obrigatória antes de novo épico
4. Incidente P1 em `docs/incidents/constitution-rollback-<data>.md`

---

## Assinatura

**PM:** Aprovado
**Data:** 2026-04-15
**Evidência de aprovação:** este commit

Com esta decisão assinada, o agente fica autorizado a:
1. Aplicar mudanças no `docs/constitution.md` descritas em ADR-0012 (já feito — versão 1.5.0 registrada)
2. Implementar `scripts/relock-harness.sh --auto-learn` (flag `KALIB_AUTO_LEARN_AUTHORIZED=1`)
3. Atualizar `scripts/guide-check.sh` com auditoria de mudanças auto-aplicadas
4. Rodar o `master-auditor` sobre `docs/audits/progress/harness-local-upgrade-action-plan.md` (v4) como primeiro ato do novo regime

Mudanças em arquivos selados (`scripts/hooks/*.sh`, `MANIFEST.sha256`) exigem PM clicar `relock.bat`.
