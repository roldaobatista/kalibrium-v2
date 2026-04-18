# Template obrigatorio de prompt de auditoria/re-auditoria

**Versao:** 1.0 (slice 018 — AC-003/AC-003-A)
**Fonte normativa:** `docs/protocol/06-estrategia-evidencias.md` (secao "Auditoria sem bias")
**Validator mecanico:** `scripts/validate-audit-prompt.sh --mode=(1st-pass|re-audit) <prompt-file>`

---

## Contexto

Para eliminar bias em auditorias e re-auditorias (R3/R11), todo prompt enviado a um sub-agent de gate DEVE seguir este template. Campos extras alem dos 6 obrigatorios sao proibidos em 1st-pass; em re-audit, qualquer token da lista `docs/protocol/blocked-tokens-re-audit.txt` rejeita o prompt.

## 6 campos obrigatorios

Todo prompt contem exatamente estes 6 campos (nada alem, nada aquem):

1. **story_id** — formato `E??-S??` (ex: `E15-S04`)
2. **slice_id** — formato `NNN` (3 digitos, ex: `019`)
3. **mode** — modo canonico do gate (deve constar no enum `docs/protocol/schemas/gate-output.schema.json`)
4. **perimeter_files** — lista de paths-raiz autorizados para leitura pelo sub-agent (R3)
5. **criteria_checklist** — lista numerada dos criterios do gate, copiada literal do agent file
6. **output_contract** — bloco JSON schema esperado de volta

## Formato canonico

```markdown
---
story_id: E??-S??
slice_id: NNN
mode: <modo-canonico>
---

# Prompt de auditoria (1st-pass | re-audit)

## perimeter_files
- path/primeiro/arquivo
- path/segundo/arquivo

## criteria_checklist
1. Primeiro criterio literal do agent file
2. Segundo criterio literal

## output_contract
\`\`\`json
{
  "$schema": "gate-output-v1",
  "gate": "<modo-canonico>",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "findings": []
}
\`\`\`
```

## Proibicoes em 1st-pass

- Veredito/findings/IDs de rodadas anteriores
- Hashes de fix commits
- Lista de arquivos tocados pelo fixer
- Campos alem dos 6 obrigatorios

## Proibicoes adicionais em re-audit

Todos os tokens listados em `docs/protocol/blocked-tokens-re-audit.txt` sao rejeitados pelo validator. Tokens cobertos incluem (nao exaustivo):

- `finding anterior`, `previously found`
- `foi corrigido`, `ja corrigido`, `fix applied`, `fixer tocou`
- `verifique se X foi`, `rodada anterior`, `rodada [0-9]+`
- IDs de finding previo (`[A-Z]{1,4}-[0-9]{3}-[0-9]{3}`)
- Commit hashes adjacentes a palavras `fix|correcao|corrigir`

Ver `docs/protocol/blocked-tokens-re-audit.txt` para lista canonica completa.
