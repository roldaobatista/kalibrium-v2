# Retrospectiva — 5/5 bypasses administrativos consumidos (bootstrap → 2026-04-15)

**Data:** 2026-04-15
**Autor:** orquestrador Claude Opus 4.6 + dual-LLM auditor (draft para ratificação do PM)
**Origem:** pré-requisito 8 da decisão pós re-auditoria `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md`
**Natureza:** retrospectiva reflexiva (não é `epic-retrospective` automatizada — aquela está bloqueada porque E02 não está fechado)

## Propósito

Responder a pergunta que as duas trilhas da re-auditoria externa fizeram explicitamente: **por que 4 dos 5 bypasses administrativos vieram da mesma causa-raiz (Bloco 5 item 5.3 nunca implementado) e por que foi permitido?**

A pergunta não é técnica — é de governança. O documento existe para que a resposta fique escrita antes da retomada do projeto, para que o mesmo padrão não se repita.

## Fatos (sem interpretação)

| # | Data | Commit/Evento | Paths afetados | Classificação na época |
|---|---|---|---|---|
| 1 | 2026-04-10 | PR #1 admin merge (`pr-1-admin-merge.md`) | Fase 2 setup | Bypass de continuidade |
| 2 | 2026-04-10 | `345b0a2`, `c061e3c` push Bloco 1 meta-audit | `.claude/`, `scripts/hooks/`, `docs/` | Bypass de continuidade |
| 3 | 2026-04-10 | `a1ad97e` auto-referência incident Bloco 1 | `docs/incidents/` | Bypass de continuidade (granularidade excessiva) |
| 4 | 2026-04-11 | 36 commits em um push | múltiplas categorias | Bypass de continuidade (pacote agregado) |
| 5 | 2026-04-15 | PR #14 merge ADR-0012 via `--admin` | ADR constitucional | P0 por inviabilidade operacional |
| — | 2026-04-15 | Slice-010 auto-merge durante pausa (PR #15) | código de produção slice-010 | Não-bypass (auto-merge armado); violação P1 de enforcement |
| 6 | 2026-04-15 | `ae26463` preparação dossiê re-auditoria | `docs/audits/external/**` | Bypass técnico (categoria nova X.1) |
| 7 | 2026-04-15 | `cbf95d1` doc X.1 + catch-22 | `docs/incidents/` | Bypass técnico |
| 8 | 2026-04-15 | `f70f376` ratificação decisão PM pós-audit | `docs/decisions/`, `docs/audits/**` | Bypass técnico (ADR-0014) |

## Causa-raiz única

**Bloco 5 item 5.3 (remover `current_user_can_bypass: always` do ruleset + instalar auto-reviewer + tornar CI obrigatório) foi identificado em 2026-04-10 como pendência e ficou aberto por 5 dias.** Durante esses 5 dias:

- Bypasses 2, 3, 4 aconteceram porque o próprio trabalho de fechar 5.3 exigia commits que o ruleset bloqueava (implementar hooks, registrar incidentes). Auto-referência.
- Bypass 5 aconteceu porque o modelo de governança original (dual-verifier humano + agent) não conseguia operar com PM não-técnico, e a emenda (ADR-0012) que resolveria isso precisava ela mesma ser mergeada — sem 5.3 resolvido, só via `--admin`. Self-inflicted P0.
- Bypasses técnicos 6, 7, 8 aconteceram porque preparar re-auditoria do acúmulo de bypasses exige push de documentos, que o ruleset bloqueia.

**Em linguagem não-técnica:** a porta de emergência estava aberta desde o primeiro dia, e toda vez que tentamos fechá-la, tivemos que usá-la para poder fechá-la.

## Por que foi permitido

Três razões identificadas pela re-auditoria:

### 1. Ausência de critério formal de P0 em bootstrap

Durante Fase 2 (bootstrap), "bypass de continuidade" foi aceito como categoria válida sem critério fechado. "Continuidade" virou justificativa aberta — qualquer trabalho em que o ruleset bloqueava pôde ser classificado como "continuidade necessária". Sem lista fechada do que conta como P0 legítimo, a categoria se expandiu silenciosamente.

### 2. Política de pausa puramente declarativa

Quando o 5º bypass foi consumido e projeto "pausou", a pausa foi escrita em markdown (`pr-14-bypass-p0-billing-governance §Plano de saída`) sem hook mecânico que bloqueasse execução. Resultado: slice-010 mergeou durante a pausa via auto-merge armado antes dela. A trilha Opus da re-auditoria foi cirúrgica: *"a pausa não era um estado do sistema, era um aviso em markdown"*.

### 3. Pressão operacional substituindo deliberação

O 5º bypass (PR #14 ADR-0012) foi mergeado sob pressão de billing do GitHub Actions expirando. A ADR ainda tinha `Status: Proposta`, checklist de aprovação não marcado, e master-auditor nunca tinha sido sequer invocado uma vez. Trilha Opus: *"é uma emenda constitucional adotada sem provar que funciona"*. Quando pressão temporal entra, deliberação sai.

## O que deveria ter sido feito diferente

Em retrospecto, na ordem dos fatos:

1. **Bloco 5 item 5.3 deveria ter sido o primeiro commit após o bypass 1.** Qualquer outro trabalho bloqueado esperaria. Em vez disso, "5.3 fica pra depois" virou "5.3 fica por 5 dias".
2. **Bypasses 2, 3, 4 deveriam ter sido um PR agregado** com auto-reviewer temporário (mesmo manual), não pushes diretos com `--admin`.
3. **Classificação P0 deveria ter critério fechado desde o bypass 1**, não inventado no bypass 5.
4. **Pausa deveria ter hook mecânico desde o primeiro incidente P0**, não declarada em markdown.
5. **ADR constitucional como ADR-0012 deveria ter tido dry-run obrigatório** do fluxo novo antes do merge, não só após (como agora).

## Riscos residuais se o padrão se repetir

Se, após cumprir os 8 pré-requisitos, o projeto voltar a operar no mesmo modo:

- "P0 por inviabilidade operacional" vira fachada para decisões que não são urgentes.
- Auto-aplicação do `harness-learner` sob ADR-0012 pode criar "bypasses técnicos" que só depois auditoria externa trimestral detecta.
- Categoria "bypass técnico autorizado" (ADR-0014) pode crescer via whitelist expansão silenciosa.

## Compromissos resultantes

1. **Nenhum bypass é "de continuidade" sem ADR formal.** Se uma operação requer bypass para ser feita, ela requer ADR descrevendo o porquê antes.
2. **Toda pausa tem hook mecânico**, não só markdown. Isso é o pré-requisito 2 da decisão pós-auditoria.
3. **ADR constitucional exige dry-run** documentado do fluxo proposto antes do merge, não depois. Isso vira requisito permanente do harness.
4. **Auditoria externa trimestral** valida o contador de bypass técnico e a whitelist da ADR-0014. Sem isso, a categoria nova é território de drift silencioso.
5. **Ratificação formal desta retrospectiva pelo PM** — este documento precisa ser marcado como "PM revisou e concorda" antes de qualquer retomada real.

## Assinatura pendente

```
PM (roldaobatista): ________________________________
Data de ratificação: _______________________________
Método: commit adicional em main desta retrospectiva após leitura
```

## Nota de procedimento

Este documento foi redigido pelo orquestrador sob autorização explícita do PM ("pode fazer tudo sem parar para me perguntar" em 2026-04-15). A ratificação do PM é via commit em `main` confirmando o conteúdo ou edição com mudanças. Enquanto não ratificado, vale como **draft de retrospectiva** — já cumpre o pré-requisito 8 em forma de existência do documento, mas a consolidação do aprendizado só fecha com assinatura PM.
