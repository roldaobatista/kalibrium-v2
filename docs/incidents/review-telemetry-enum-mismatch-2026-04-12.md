# Incidente: Mismatch de enum entre review.schema.json e record-telemetry.sh

**Data:** 2026-04-12
**Severidade:** baixa (nao bloqueia funcionalidade, apenas telemetria)
**Slice afetado:** 001

## Descricao

O schema `docs/schemas/review.schema.json` define `next_action` com enum:
- `approve_pr`, `return_to_implementer`, `escalate_human`

O script `scripts/record-telemetry.sh` aceita enum:
- `open_pr`, `return_to_implementer`, `escalate_human`, `n/a`

O `review-slice.sh` passa o valor do JSON direto para o telemetry sem mapear `approve_pr` -> `open_pr`.

## Impacto

Telemetria do evento `review` nao e registrada quando verdict=approved.
Verificacao funcional e schema validation passam normalmente.

## Contorno aplicado

Review.json gravado com `approve_pr` (conforme schema). Telemetria do review pulada neste slice.
O review.json foi copiado manualmente para `specs/001/review.json`.

## Correcao necessaria

O PM deve atualizar `scripts/review-slice.sh` (via `relock-harness.sh`) para mapear:
```bash
# Antes de chamar record-telemetry:
[[ "$NEXT" == "approve_pr" ]] && TELEM_NEXT="open_pr" || TELEM_NEXT="$NEXT"
```

Ou unificar o enum em ambos os arquivos.
