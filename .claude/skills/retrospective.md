---
description: Cria docs/retrospectives/slice-NNN.md combinando slice-report + template qualitativo. OBRIGATÓRIA após cada slice concluído. Uso: /retrospective NNN.
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
- <bullets — com evidência: gate X bloqueou Y vezes, verifier reprovou com razão Z>

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

## Implementação
```bash
bash scripts/retrospective.sh "$1"
```

## Quando NÃO pular
- Nunca pular. Slice sem retrospectiva = dívida de aprendizado.
- Se o slice foi abandonado/escalado, a retrospectiva é **ainda mais importante** — o que deu errado é o que queremos capturar.
