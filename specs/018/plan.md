# Plano técnico do slice 018

**Gerado por:** architect sub-agent
**Status:** draft | approved
**Spec de origem:** `specs/NNN/spec.md`

---

## Decisões arquiteturais

### D1: <decisão>
**Opções consideradas:**
- **Opção A:** <descrição> — prós: ... / contras: ...
- **Opção B:** <descrição> — prós: ... / contras: ...

**Escolhida:** A

**Razão:** <justificativa alinhada com constitution + spec>

**Reversibilidade:** fácil | média | difícil

**ADR:** `docs/adr/NNNN-<slug>.md` (se a decisão for relevante fora do slice)

### D2: ...

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `src/foo.ts`, `src/bar.ts` | `tests/foo.test.ts` |
| AC-002 | `src/foo.ts` | `tests/foo.test.ts` |

## Novos arquivos

- `src/domain/<path>` — <razão>
- `tests/<path>` — teste de AC-NNN

## Arquivos modificados

- `<path>` — <razão>

## Schema / migrations

- `migrations/<timestamp>_<slug>.<ext>` — <descrição>

## APIs / contratos

### POST /api/...
**Request:**
```json
{...}
```
**Response:**
```json
{...}
```

## Riscos e mitigações

- <risco 1> → <mitigação>
- <risco 2> → <mitigação>

## Dependências de outros slices

- `slice-NNN` — por quê

## Fora de escopo deste plano (confirmando spec)

- ...
