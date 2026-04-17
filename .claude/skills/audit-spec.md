---
name: audit-spec
description: Audita specs/NNN/spec.md antes de gerar plan.md. Roda qa-expert (audit-spec) em contexto limpo e usa loop de correcao ate findings vazio. Uso: /audit-spec NNN.
user_invocable: true
protocol_version: "1.2.4"
---

# /audit-spec

## Uso

```
/audit-spec NNN
```

Exemplo: `/audit-spec 007`

## Por que existe

`spec.md` é a fonte de verdade para plano, testes e implementação. O validador `/draft-spec` só checa formato; este gate adiciona auditoria independente de escopo, ACs, testabilidade, segurança, dependências e alinhamento de produto antes do `architecture-expert` (modo: plan).

## Quando invocar

- Depois de `/draft-spec NNN`.
- Antes de `/draft-plan NNN`.
- Sempre que o spec for editado manualmente.

## Pre-condicoes

1. `specs/NNN/spec.md` existe.
2. `bash scripts/draft-spec.sh NNN --check` passa.
3. `.claude/agents/qa-expert.md` existe.
4. `docs/protocol/schemas/gate-output.schema.json` existe.

Validar com:

```bash
bash scripts/audit-spec.sh NNN --check
```

## O que faz

### 1. Spawn qa-expert (modo: audit-spec)

Roda `qa-expert` (modo: audit-spec) em contexto limpo, com acesso apenas aos artefatos permitidos no agent card.

Output esperado: `specs/NNN/spec-audit.json`.

### 2. Validar JSON

```bash
bash scripts/audit-spec.sh NNN --validate
bash scripts/audit-spec.sh NNN --approved
```

### 3. Resultado aprovado

Se `verdict: approved` e `findings: []`, apresentar ao PM em linguagem R12:

```
Auditoria da spec 007: APROVADO

O escopo está fechado, os critérios são testáveis e o slice pode ir para plano.
Próximo passo: /draft-plan 007.
```

### 4. Resultado reprovado

Se houver qualquer finding:

```
Auditoria da spec 007: AJUSTE NECESSÁRIO

Problemas encontrados:
1. [SP-001] linguagem de produto
2. [SP-002] linguagem de produto

Vou corrigir a spec e reauditar.
```

### 5. Loop de correcao

```
loop (5 ciclos automáticos; 6ª rejeição escala PM):
  1. qa-expert (audit-spec) emite findings
  2. builder (fixer) corrige somente specs/NNN/spec.md
  3. rodar bash scripts/draft-spec.sh NNN --check
  4. reexecutar /audit-spec NNN
  5. se approved + findings [] -> sair
  6. se 6 iteracoes sem aprovar -> escalar PM com resumo R12
```

## Conformidade com protocolo v1.2.4

- **Agent invocado:** `qa-expert (audit-spec)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `audit-spec`
- **Output:** `specs/NNN/spec-audit.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §10`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`

## Agentes

- `qa-expert` (modo: audit-spec) (budget: 25k) — audita e emite `spec-audit.json`.
- `builder` (modo: fixer) (budget: 60k) — corrige findings no spec quando necessario.

## Integração

`/draft-plan NNN` deve exigir `bash scripts/audit-spec.sh NNN --approved` antes de chamar o `architecture-expert` (modo: plan).

## Handoff

- Approved -> `/draft-plan NNN`.
- Rejected e corrigido -> reauditar automaticamente.
- Rejected 6x -> escalar PM em linguagem de produto.
