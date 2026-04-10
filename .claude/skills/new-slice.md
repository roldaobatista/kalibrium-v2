---
description: Cria esqueleto de um slice novo em specs/NNN/ com spec.md, plan.md e tasks.md a partir dos templates. Use quando for iniciar trabalho em um slice novo. Uso: /new-slice NNN "título do slice".
---

# /new-slice

## Uso
```
/new-slice NNN "título do slice"
```

Exemplo:
```
/new-slice 001 "login por email e senha com 2FA TOTP"
```

## O que faz

1. Valida que `NNN` é 3 dígitos (001-999) e que `specs/NNN/` **não** existe.
2. Cria `specs/NNN/`.
3. Copia `docs/templates/spec.md`, `docs/templates/plan.md` e `docs/templates/tasks.md` para `specs/NNN/`.
4. Preenche título, data, status `draft` no cabeçalho de cada um.
5. Adiciona linha em `docs/slice-registry.md`: `| NNN | título | draft | <data> |`.
6. **Não commita.** O humano revisa `spec.md` manualmente antes.

## Implementação

Executar:
```bash
bash scripts/new-slice.sh "$1" "$2"
```

## Handoff

Após criação bem-sucedida:
1. Humano edita `specs/NNN/spec.md` preenchendo:
   - Contexto
   - ACs numerados (AC-001, AC-002, ...)
   - Fora de escopo
   - Dependências
2. Quando aprovado, invocar sub-agent `architect` para gerar `plan.md`.
3. Nunca pular para `ac-to-test` ou `implementer` antes de spec + plan aprovados.
