# ADR-0012 — Emenda constitucional: autonomia do agente com dual-LLM e retrospectiva

**Status:** Proposta
**Data:** 2026-04-14
**Decisor:** PM
**Substitui/Emenda:** `docs/constitution.md §3.1` (definição de PM) + `§R11` (dual-verifier humano+agent)
**Supersedida por:** —

## Contexto

O PM do Kalibrium V2, por opção operacional declarada em 2026-04-14, não opera terminal nem Git/GitHub diretamente. Toda a governança original foi desenhada assumindo PM técnico com interação frequente em terminal externo (relock, smoke-test, dual-verifier R11). Manter o modelo original resulta em bloqueio operacional: decisões aprovadas não são executadas porque a etapa humano-técnica não acontece.

Ao mesmo tempo, o PM quer:
- Intervenção apenas em **bloqueio real** ou **fim de épico**
- Usar o melhor modelo disponível de cada provedor (Claude Opus 4.6 + GPT-5 via Codex CLI)
- Automatizar retrospectiva pós-épico e aprendizado de harness
- Operar relock via arquivo `.bat` clicável em vez de terminal direto

## Decisão

Esta ADR emenda a constitution em 4 pontos:

### E1 — PM como dono não-técnico (emenda §3.1)

**Antes:**
> O PM é o único humano do projeto. Opera em terminal externo para relocks, smoke-tests e revisão final.

**Depois:**
> O PM é o único humano do projeto e o dono do repositório. Aprova direção estratégica e amendments constitucionais. **Não opera terminal diretamente** — para operações que exigem terminal, usa ferramentas wrapper (`relock.bat`, botões de PR no GitHub UI, skills slash-command no Claude Code). O fluxo operacional cotidiano é delegado ao agente com governança dual-LLM (E2), retrospectiva automática (E3) e aprendizado de harness (E4).

### E2 — Dual-LLM substitui R11 como gate primário

**Antes (R11):**
> Todo plano e toda entrega passam por dual-verifier: humano PM + agent reviewer.

**Depois (R11 emendada):**
> Todo plano e toda entrega passam por **dual-LLM** (Claude Opus 4.6 + GPT-5 via Codex CLI) consolidado pelo `master-auditor`. PM é chamado apenas em bloqueio real (definido em E5). R11 original vira **R11-legacy** e aplica-se apenas a decisões que afetam constitution §1-§4 (princípios fundamentais P1-P9).

**Implementação:**
- `master-auditor` é agent novo em `.claude/agents/master-auditor.md`
- Invoca Opus 4.6 (sessão isolada) + GPT-5 (via Codex CLI MCP) em paralelo
- Consolida findings; só aprovado se ambas LLMs aprovarem independentemente
- Output: `<artifact>.master-audit.json` com verdict consensual

### E3 — Retrospectiva automatizada pós-épico

**Nova regra — R15:**
> Ao fim de cada épico (conforme definido em `epics/ENN/epic.md`), invocar automaticamente o `epic-retrospective` agent. Este agent faz scan completo do sistema buscando inconsistências, drift, débitos técnicos, ACs cobertos parcialmente. Findings disparam loop corretivo de até **10 iterações** (fixer → re-audit → retrospective). Não-convergência em 10 iterações escala para PM.

**Critério de convergência:**
- Cada iteração reduz número de findings (ou mantém zero)
- Se número de findings subir entre iterações, aborta e escala
- Deadline de 2h por iteração; total máximo 20h antes de escalar

### E4 — Harness-learner com auto-aplicação limitada

**Nova regra — R16:**
> Após `epic-retrospective` aprovar épico, invocar `harness-learner`. Este agent estuda os findings da retrospectiva e pergunta: "por que o harness deixou passar?". Gera propostas de melhoria (regra nova, hook novo, skill nova, deny ampliada).

**Auto-aplicação permitida (sem consulta ao PM):**
- Adicionar nova regra R-NN (incremental, não sobrescreve)
- Adicionar novo hook em `scripts/hooks/`
- Tornar regra existente mais estrita (ex: estender deny list)
- Adicionar nova skill operacional (não altera P/R)

**Auto-aplicação PROIBIDA (escala obrigatoriamente):**
- Revogar regra existente (R-NN → removida)
- Afrouxar regra existente (ex: reduzir cooldown, aumentar teto)
- Remover hook existente
- Alterar constitution §1-§4 (princípios P1-P9)
- Modificar lista de P/R core
- Qualquer mudança em `allowed-git-identities.txt`, `git-identity-baseline`

**Limite operacional:**
- Máximo 3 mudanças auto-aplicadas por ciclo retrospectivo
- Toda mudança auto gera arquivo `docs/incidents/auto-learn-YYYYMMDD-<slug>.md` com diff completo + justificativa
- Toda mudança auto dispara relock via script (não via `relock.bat`; hash atualizado programaticamente e registrado no incidente)
- PM recebe notificação consolidada semanal de todas as mudanças auto

### E5 — Definição operacional de "bloqueio real"

PM é chamado apenas nas situações:

| Categoria | Gatilho |
|---|---|
| Falha técnica | LLM falha 3x consecutivas (Claude ou GPT-5) |
| Desacordo | Dual-LLM discorda após 3 rodadas de reconciliação |
| Governança | Mudança que harness-learner não pode auto-aplicar (E4 lista proibida) |
| Incidente | P0 ou P1 conforme `docs/security/incident-response-playbook.md` |
| Relock manual | Mudança em arquivo selado requer decisão humana (PM clica `relock.bat`) |
| Fim de épico | `epic-retrospective` aprovou + `harness-learner` executou — PM recebe relatório |

Fora dessas situações, agente opera autonomamente.

## Consequências

**Positivas:**
- PM não-técnico consegue operar projeto sem travar fluxo
- Dual-LLM reduz risco de viés single-model
- Retrospectiva automatizada captura débitos sem ação humana
- harness-learner torna o harness melhor ao longo do tempo sem overhead

**Negativas / riscos:**
- Custo de tokens ~2x (dual-LLM) — aceito pelo PM (sem teto)
- Risco de drift do harness se harness-learner ignorar guardrails de E4 — mitigado por regra explícita + auditoria trimestral (ver Mitigações)
- Auto-aplicação de mudanças em hooks aumenta superfície de ataque — mitigado por limite de 3/ciclo + arquivo de incidente obrigatório
- Dependência de 2 provedores LLM — se um cair, dual-LLM degrada; escala para PM

**Mitigações:**
- Auditoria trimestral externa revisa todas as mudanças auto-aplicadas do trimestre
- `harness-learner` tem max_tokens_per_invocation declarado e orçamento por ciclo
- Logs estruturados de toda decisão auto em `.claude/telemetry/harness-learner.jsonl`
- Relock via `relock.bat` continua disponível como override manual para PM

## Plano de rollback

Se esta emenda gerar drift não aceitável:

1. PM clica `relock.bat` e edita settings.json para desabilitar `master-auditor` e `harness-learner`
2. Reverter para constitution original (R11 com humano) via `git revert` desta ADR
3. Re-auditoria externa obrigatória antes de qualquer novo épico
4. Incidente P1 registrado em `docs/incidents/constitution-rollback-<data>.md`

## Plano de relock

Esta ADR exige relock pelo PM (clica `relock.bat`):
1. Agente atualiza `docs/constitution.md` com texto das emendas E1-E4 + referência a esta ADR
2. Agente cria os 3 agent files em `.claude/agents/`
3. Agente atualiza `.claude/settings.json` adicionando hooks/configs necessários
4. PM clica `relock.bat` → digita RELOCK → hashes atualizados
5. Arquivo de incidente de relock registra a mudança constitucional

## Cross-ref

- `docs/constitution.md §3.1`, `§5` (processo de amendment), `§R11`
- `docs/governance/harness-evolution.md §2` (critérios para nova regra)
- `docs/harness-limitations.md §Edição externa de hooks` (continua válido)
- `docs/audits/progress/harness-local-upgrade-action-plan.md` (plano operacional que esta ADR habilita)
- `.claude/agents/master-auditor.md`, `epic-retrospective.md`, `harness-learner.md`

## Aprovação

- [ ] PM assina via commit em `docs/decisions/pm-decision-adr-0002-2026-04-14.md`
- [ ] Relock executado (evidência em `docs/incidents/harness-relock-*.md`)
- [ ] Constitution atualizada com referência a esta ADR
- [ ] 3 agents criados e smoke-tested
