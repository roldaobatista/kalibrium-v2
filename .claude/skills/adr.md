---
description: Scaffold de um ADR novo em docs/adr/ a partir do template. Use para decisões arquiteturais relevantes. Uso: /adr NNNN "título da decisão".
---

# /adr

## Uso
```
/adr NNNN "título da decisão"
```

Exemplo:
```
/adr 0001 "escolha da stack"
/adr 0003 "estrategia de multi-tenancy"
```

## O que faz

1. Valida que `NNNN` é 4 dígitos e `docs/adr/NNNN-*.md` **não** existe.
2. Cria `docs/adr/NNNN-<slug>.md` a partir de `docs/adr/0000-template.md`.
3. Preenche metadados (número, título, data, status `proposed`).
4. Adiciona linha em `docs/TECHNICAL-DECISIONS.md` referenciando o novo ADR.
5. **Não commita.** Humano preenche Contexto, Opções, Decisão, Consequências.

## Implementação
```bash
bash scripts/adr-new.sh "$1" "$2"
```

## Estrutura obrigatória do ADR

- **Status:** proposed | accepted | superseded por NNNN | deprecated
- **Contexto:** qual problema
- **Opções consideradas:** ≥ 2, com prós/contras
- **Decisão:** qual opção + razão
- **Consequências:** positivas, negativas, riscos, reversibilidade
- **Referências:** links para specs/slices afetados

Sem alternativas consideradas, o ADR é rejeitado em code review.
