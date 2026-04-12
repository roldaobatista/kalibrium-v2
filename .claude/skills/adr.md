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

## Pré-condições

1. Diretório `docs/adr/` existe no repositório.
2. Template `docs/adr/0000-template.md` existe.
3. `docs/TECHNICAL-DECISIONS.md` existe (para registrar referência ao novo ADR).
4. `NNNN` informado é 4 dígitos e `docs/adr/NNNN-*.md` ainda não existe.

## Agentes

Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `docs/adr/` ou template não existe | Criar diretório e template antes de invocar. Verificar se o scaffold do projeto foi executado. |
| ADR com número `NNNN` já existe | Escolher outro número sequencial. Consultar `docs/TECHNICAL-DECISIONS.md` para ver o próximo disponível. |
| Script `scripts/adr-new.sh` falha (permissão, path) | Verificar que o script existe e tem permissão de execução. Rodar `bash scripts/adr-new.sh` manualmente para diagnóstico. |
| PM cancela antes de preencher o ADR | Remover o arquivo gerado (`docs/adr/NNNN-*.md`) e a linha adicionada em `TECHNICAL-DECISIONS.md`. Nenhum commit foi feito. |
