---
description: Audita specs/NNN/spec.md antes de gerar plan.md. Roda spec-auditor em contexto limpo e usa loop de correcao ate findings vazio. Uso: /audit-spec NNN.
user_invocable: true
---

# /audit-spec

## Uso

```
/audit-spec NNN
```

## Quando invocar

- Depois de `/draft-spec NNN`.
- Antes de `/draft-plan NNN`.
- Sempre que o spec for editado manualmente.

## Pre-condicoes

```bash
bash scripts/audit-spec.sh NNN --check
```

## O que faz

1. Spawna o sub-agent `spec-auditor` em contexto limpo.
2. O auditor lê apenas os artefatos permitidos no agent card.
3. O auditor escreve `specs/NNN/spec-audit.json`.
4. O orquestrador valida:

```bash
bash scripts/audit-spec.sh NNN --validate
```

5. Se `rejected`, o `fixer` corrige somente `specs/NNN/spec.md` e a auditoria roda de novo.
6. Se `approved` com `findings: []`, liberar `/draft-plan NNN`:

```bash
bash scripts/audit-spec.sh NNN --approved
```

## Handoff

- Approved -> `/draft-plan NNN`.
- Rejected e corrigido -> reauditar automaticamente.
- Rejected 3x -> escalar PM em linguagem de produto.
