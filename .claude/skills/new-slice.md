---
description: Cria esqueleto de um slice novo em specs/NNN/ com spec.md, plan.md e tasks.md a partir dos templates. Use quando for iniciar trabalho em um slice novo. Uso: /new-slice NNN "título do slice".
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit fix SK-005"
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
2. **Branch != main (B-023):** verifica `git branch --show-current`. Se retornar `main`, **bloqueia** e orienta criar feature branch (`git checkout -b feat/slice-NNN`) ou worktree. Nao prosseguir ate branch != `main`. Bypass: `KALIB_SKIP_BRANCH_CHECK="<motivo>"` registra incidente em `docs/incidents/`.
3. Valida gate R13/R14 (ADR-0011): se o título começar com `ENN-SNN:` (ex.: `E02-S07: LGPD + consentimentos`), executa `scripts/sequencing-check.sh --story ENN-SNN` e bloqueia se stories/épicos anteriores não estão `merged` em `project-state.json[epics_status]`. Slices standalone (sem prefixo de story) não passam pelo gate. Bypass: `KALIB_SKIP_SEQUENCE="<motivo>"` registra incidente.
4. Cria `specs/NNN/`.
5. Copia `docs/templates/spec.md`, `docs/templates/plan.md` e `docs/templates/tasks.md` para `specs/NNN/`.
6. Preenche título, data, status `draft` no cabeçalho de cada um.
7. Adiciona linha em `docs/slice-registry.md`: `| NNN | título | draft | <data> |`.
8. **Não commita.** O humano revisa `spec.md` manualmente antes.

## Implementação

Executar:
```bash
bash scripts/new-slice.sh "$1" "$2"
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `specs/NNN/` já existe | Escolher outro número ou verificar se o slice existente é o desejado. |
| Templates não encontrados em `docs/templates/` | Verificar que `docs/templates/spec.md`, `plan.md` e `tasks.md` existem. Se não, restaurar do commit inicial do harness. |
| `docs/slice-registry.md` não existe | Criar o arquivo com o cabeçalho da tabela antes de rodar novamente. |
| NNN fora do formato 3 dígitos | Usar formato correto (001-999). O script valida e rejeita formatos inválidos. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- Arquitetura congelada (`/freeze-architecture` executado).
- Templates existem em `docs/templates/` (spec.md, plan.md, tasks.md).
- `docs/slice-registry.md` existe.

## Handoff

Após criação bem-sucedida:
1. Humano edita `specs/NNN/spec.md` preenchendo:
   - Contexto
   - ACs numerados (AC-001, AC-002, ...)
   - Fora de escopo
   - Dependências
2. Quando aprovado, invocar sub-agent `architecture-expert` (modo: plan) para gerar `plan.md` (conforme mapa canonico 00 §3.1).
3. Nunca pular para `builder` (modo: test-writer) ou `builder` (modo: implementer) antes de spec + plan aprovados.

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador invoca scaffold script).
- **Gates produzidos:** não é gate; é scaffold de diretório + metadados.
- **Output:** `specs/NNN/{spec.md,plan.md,tasks.md}` em status `draft`.
- **Schema formal:** templates em `docs/templates/{spec.md,plan.md,tasks.md}`.
- **Isolamento R3:** não aplicável (sem sub-agent).
- **Ordem no pipeline:** precede `/draft-spec NNN` e invocação de `architecture-expert (plan)`.
