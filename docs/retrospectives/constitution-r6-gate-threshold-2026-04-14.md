# Retrospectiva — R6 com 5 ciclos automáticos

**Data:** 2026-04-14
**Origem:** slice 007 e pedido explícito do PM
**ADR:** `docs/adr/0010-constitution-amendment-r6-gate-threshold.md`

## O que aconteceu

O limite antigo de R6 escalava ao PM depois de duas reprovações consecutivas. No slice 007, isso gerou sucessivas pausas para autorização humana, mesmo quando a decisão do PM era sempre "corrigir e tentar de novo".

## Lição aprendida

Escalar cedo demais não protege o produto quando o PM não tem uma decisão de produto real para tomar. Para loops de review/auditoria, o harness deve tentar corrigir automaticamente mais vezes, desde que:

- o mesmo gate seja re-rodado;
- todos os findings sejam corrigidos;
- zero tolerance continue valendo;
- a escalação ao PM aconteça na 6ª reprovação consecutiva.

## Mudança aplicada

R6 agora permite 5 ciclos automáticos de correção e escala ao PM na 6ª rejeição consecutiva do mesmo gate.

## Risco residual

O novo limite pode aumentar consumo de tokens e tempo por slice. Se um problema for claramente decisão de produto, o orquestrador ainda pode escalar antes por outra regra aplicável, mas não deve usar R6 como atalho antes da 6ª rejeição.
