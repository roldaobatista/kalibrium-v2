# ADR-0011 — Amendment: ordem Story × Epic enforced (R13 + R14)

**Status:** Aceito
**Data:** 2026-04-15
**Autor:** roldaobatista (PM) + orquestrador Claude Code
**Constitution bump:** 1.3.0 → 1.4.0

## Contexto

Durante o fechamento do slice 009 (TEN-002, E02-S04..S06), o orquestrador recomendou ao PM iniciar TEN-003 (E03-S01, Clientes e Contatos) como proximo slice. A recomendacao seguia o `docs/product/roadmap.md` literalmente, mas nao cruzou o roadmap com o `epics/E02/epic.md` — que lista 8 stories previstas. Apenas 6 (S01..S06) tinham sido cobertas pelos slices 007, 008 e 009. **E02-S07 (Base legal LGPD + consentimentos) e E02-S08 (Testes estruturais de isolamento cross-tenant) ficaram orfas no roadmap.**

Se a recomendacao tivesse sido aceita, o Kalibrium comecaria a cadastrar dados pessoais de clientes reais (E03) sem base legal LGPD registrada (violando REQ-CMP-004) e sem rede de protecao de isolamento cross-tenant (aumentando risco de vazamento).

**Causa raiz:** o harness nao tinha nenhum gate mecanico que impedisse pular stories dentro de um epico ou iniciar um epico novo com o anterior ainda aberto. Toda a ordem dependia de:
1. Roadmap gerado uma unica vez (modo WIZARD) e nao revisado;
2. `/start-story` declarar "dependencias satisfeitas" como pre-condicao **sem validacao mecanica**;
3. Agente orquestrador cruzar mentalmente roadmap × epic × project-state.

P4 (hooks executam, nao so formatam) e P7 (verificacao antes de afirmacao) foram violados: a "ordem" existia como intencao documental, nunca como verificacao executavel.

## Decisao

Adotar R13 e R14 na constituicao, com enforcement mecanico via `scripts/sequencing-check.sh` plumbed em `new-slice.sh` e `start-story.sh`.

### R13 — Ordem intra-epico

- Story nova so inicia se stories anteriores do mesmo epico (ENN-S01..ENN-S(N-1)) estao com `status: merged` em `project-state.json[epics_status]`.
- Excecao: story pode declarar `dependencies: []` (array vazio explicito) no frontmatter do contrato, permitindo rodar em paralelo. Apenas as dependencias declaradas sao validadas.

### R14 — Ordem inter-epico (MVP)

- Primeiro slice de um epico MVP (E01..E12) so inicia se o epico anterior tem **todas** as stories `merged`.
- Epicos post-MVP (E13, E14) sao isentos.

### Bypass

`KALIB_SKIP_SEQUENCE="<motivo>"` autoriza pulo, gera incidente em `docs/incidents/sequence-bypass-<timestamp>.md` com operador, branch, motivo. Uso legitimo esperado: hotfix, slice abandonado documentado, reescopamento autorizado.

## Consequencias

**Positivas:**
- Elimina a classe de bug "pulei uma story/epico sem perceber" de forma mecanica.
- Forca o PM a decidir explicitamente quando quer quebrar a ordem (via bypass), criando audit trail.
- Roadmap deixa de ser a unica fonte da verdade — `project-state.json[epics_status]` passa a ser o registro canonico de progresso.

**Negativas:**
- Qualquer slice novo com titulo `ENN-SNN: ...` paga o custo de 1 chamada a `sequencing-check.sh` (<1s).
- Exige manutencao de `project-state.json[epics_status]` — novo campo que precisa ser atualizado apos cada `/merge-slice`. Risco de drift silencioso se nao automatizado.
- Epic-decomposer e story-decomposer precisam passar a gravar stories previstas em `project-state.json[epics_status]` com `status: pending` quando decompostos.

## Plano de rollback

Reverter para 1.3.0:
1. Remover R13 e R14 do `docs/constitution.md`.
2. Remover gate em `scripts/new-slice.sh`.
3. Deletar `scripts/sequencing-check.sh` e `scripts/start-story.sh`.
4. Remover campo `epics_status` de `project-state.json` (opcional — pode manter como documental sem enforcement).
5. Bump constitution para 1.4.1 registrando rollback.

Motivador de rollback: se o gate gerar mais bypass que fluxos OK (ruido > sinal) ou se o overhead de manter `epics_status` se mostrar maior que o beneficio.

## Impacto em hooks e sub-agents

| Componente | Alteracao |
|---|---|
| `scripts/sequencing-check.sh` | Novo — implementa R13/R14. |
| `scripts/start-story.sh` | Novo — wrapper do gate para o skill `/start-story`. |
| `scripts/new-slice.sh` | Novo gate R13/R14 quando titulo inicia com `ENN-SNN:`. |
| `scripts/next-slice.sh` | **Follow-up:** cruzar roadmap × epic.md × epics_status antes de recomendar proximo epico. |
| `.claude/skills/start-story.md` | Atualizar pre-condicoes com referencia explicita ao gate. |
| `.claude/skills/new-slice.md` | Documentar o gate. |
| `.claude/skills/next-slice.md` | Documentar novo cruzamento de fontes. |
| `.claude/agents/story-decomposer.md` | **Follow-up:** popular `project-state.json[epics_status][ENN][stories]` quando decompor. |
| `.claude/agents/epic-decomposer.md` | **Follow-up:** popular `project-state.json[epics_status][ENN].status = planned` quando decompor. |
| `scripts/merge-slice.sh` | **Follow-up:** atualizar `epics_status[ENN].stories[SNN] = merged` apos merge bem-sucedido. |

Follow-ups marcados: fora do escopo imediato deste ADR, mas criam backlog B-030..B-033 em `docs/guide-backlog.md`.

## Referencias

- Retrospectiva `docs/retrospectives/slice-009.md` — registrou o sintoma.
- `docs/product/roadmap.md` — atualizado para incluir SEG-002 (E02-S07) e SEG-003 (E02-S08) antes de TEN-003.
- `project-state.json` — novo campo `epics_status` retroativo para E01 e E02.
- `CLAUDE.md §6` — fluxo atualizado para incluir os gates.
