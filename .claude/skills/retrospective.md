---
description: Cria docs/retrospectives/slice-NNN.md combinando slice-report + template qualitativo. Tambem dispara retrospectiva de épico (governance retrospective) quando todos os slices do épico estão merged, gerando docs/retrospectives/epic-ENN.md + proposta harness-learner em docs/governance/harness-learner-ENN.md. OBRIGATÓRIA após cada slice concluído. Uso: /retrospective NNN.
protocol_version: "1.2.2"
---

# /retrospective

## Uso
```
/retrospective NNN
```

## O que faz

1. Lê `docs/retrospectives/slice-NNN-report.md` (gerado por `/slice-report`). Se não existir, aborta — rodar `/slice-report NNN` primeiro.
2. Cria `docs/retrospectives/slice-NNN.md` com seções fixas:

```markdown
# Retrospectiva slice-NNN

**Data:** YYYY-MM-DD
**Duração:** (do slice-report)
**Resultado:** approved / escalated / abandoned

## Números (resumo do slice-report)
<tabela curta>

## O que funcionou
- <bullets — apenas fatos, não opiniões vagas>

## O que não funcionou
- <bullets — com evidência: gate X bloqueou Y vezes, qa-expert (verify) reprovou com razão Z>

## Gates que dispararam em falso
- <hook> bloqueou por <razão> mas comportamento estava correto — ajustar regra? ADR?

## Gates que deveriam ter disparado e não dispararam
- <caso observado> — adicionar check ao hook X

## Mudanças propostas
- [ ] Alterar hook X para Y (ticket em guide-backlog.md)
- [ ] Revisar budget de tokens do sub-agent Z
- [ ] Adicionar regra R11 (requer amendment via ADR — §5 constitution)

## Lições para o guia
- <o que aprendemos que vale salvar em docs/constitution.md ou CLAUDE.md?>
```

3. **Obrigatório:** alimentar `docs/guide-backlog.md` com qualquer mudança proposta.
4. Se houver proposta de alteração de P/R, lembrar humano de seguir §5 da constitution (ADR + aprovação).
5. **Cascata S4 diferida (01 §cascata):** findings S4 do slice NÃO são promovidos para S3 automaticamente ao fim do slice. A promoção é avaliada no final do ÉPICO, quando `governance` (modo: retrospective) examina padrões recorrentes.
6. **Retrospectiva de épico (R15, ADR-0012 E3):** se todos os slices do épico ENN estão `merged` em `project-state.json[epics_status]`, disparar automaticamente `governance` (modo: retrospective) para gerar:
   - `docs/retrospectives/epic-ENN.md` — retrospectiva qualitativa do épico (local canonico)
   - `docs/governance/harness-learner-ENN.md` — propostas do `governance` (modo: harness-learner) para evolução incremental do harness (R16: max 3 mudanças PATCH/MINOR por ciclo; NÃO pode alterar P1-P9/R1-R14)

## Implementação
```bash
bash scripts/retrospective.sh "$1"
```

## Quando NÃO pular
- Nunca pular. Slice sem retrospectiva = dívida de aprendizado.
- Se o slice foi abandonado/escalado, a retrospectiva é **ainda mais importante** — o que deu errado é o que queremos capturar.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `docs/retrospectives/slice-NNN-report.md` não existe | Rodar `/slice-report NNN` primeiro. Retrospectiva depende do relatório quantitativo. |
| Telemetria do slice vazia ou incompleta | Gerar retrospectiva com dados parciais, marcando seções sem dados como "dados indisponíveis". |
| `docs/guide-backlog.md` não existe | Criar o arquivo com cabeçalho antes de alimentar mudanças propostas. |

## Agentes

- `governance` (modo: retrospective) — disparado automaticamente no fim de épico (R15). Gera `docs/retrospectives/epic-ENN.md`.
- `governance` (modo: harness-learner) — disparado após retrospective de épico (R16, ADR-0012 E4). Gera `docs/governance/harness-learner-ENN.md` com até 3 propostas PATCH/MINOR.

## Conformidade com protocolo v1.2.2

- **Paths canônicos:**
  - Retrospectiva de slice: `docs/retrospectives/slice-NNN.md`
  - Retrospectiva de épico: `docs/retrospectives/epic-ENN.md` (NÃO `docs/retrospectives/epics/` nem outros)
  - Harness-learner: `docs/governance/harness-learner-ENN.md` (NÃO `docs/retrospectives/`)
- **Cascata S4 diferida:** promoção S4→S3 avaliada no fim de épico, não no próximo slice (01 §cascata diferida; 07 §5.3 alinhado com v1.2.2).
- **R16 harness-learner:** máximo 3 mudanças por ciclo retrospectivo; apenas PATCH/MINOR; NÃO pode revogar ou afrouxar P1-P9/R1-R14.

## Pré-condições

- Slice NNN merged (todos os gates aprovados e merge concluído).
- `/slice-report NNN` já executado (gera o relatório quantitativo base).
