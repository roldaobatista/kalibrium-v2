---
description: Scaffold de um ADR novo em docs/adr/ a partir do template. Use para decisões arquiteturais relevantes. Uso: /adr NNNN "título da decisão".
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-002"
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

## Handoff de status

Um ADR é criado em status `proposed`. Transições válidas:

| De | Para | Quem autoriza | Quando |
|---|---|---|---|
| proposed | accepted | PM | Aceita a decisão. Registra timestamp + identificação no `decision_log`. |
| proposed | rejected | PM | Rejeita. Registra motivo em `decision_log`. ADR fica no repo como histórico. |
| accepted | superseded | PM + new-ADR | Nova ADR substitui esta. Cita ID da sucessora no campo `status`. |
| rejected | - | - | Terminal. Mantido para histórico. |

Nenhum ADR pode ser deletado após commitado. Rejeição é registro legítimo, não falha.

Evidência: campo `status` no frontmatter YAML + `decision_log[]` array com timestamps.

### Exemplo de decision_log

```yaml
---
id: 0007
title: Estratégia de multi-tenancy
status: accepted
superseded_by: null
decision_log:
  - timestamp: "2026-04-16T10:00:00Z"
    from: null
    to: proposed
    actor: "architecture-expert"
    reason: "Criado via /adr 0007"
  - timestamp: "2026-04-16T14:32:00Z"
    from: proposed
    to: accepted
    actor: "PM (roldao.tecnico@gmail.com)"
    reason: "Aceitou opção B (schema-per-tenant) conforme recomendação"
---
```

### Regra de transição

- Qualquer alteração do campo `status` DEVE ser acompanhada de entrada no `decision_log`.
- Alterações de status fora de `proposed → accepted | rejected` ou `accepted → superseded` são inválidas.
- Em caso de dúvida, o agente pergunta ao PM antes de mover o status.

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador invoca script de scaffold).
- **Gates produzidos:** não é gate; é scaffold de artefato de decisão.
- **Output:** `docs/adr/NNNN-<slug>.md` (status inicial `proposed`).
- **Schema formal:** template em `docs/adr/0000-template.md`.
- **Isolamento R3:** não aplicável.
- **Ordem no pipeline:** invocado durante `/decide-stack`, `/freeze-architecture` ou ad hoc para decisões relevantes.
