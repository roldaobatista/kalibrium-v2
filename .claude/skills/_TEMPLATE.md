---
description: <1 linha — intenção da skill em português, menciona comando e contexto de uso. Ex.: "Valida <gate> via <agent> em contexto isolado; produz <artefato>. Uso: /<comando> <args>.">
protocol_version: "1.2.2"
changelog: "YYYY-MM-DD — criação/última alteração"
---

# SKILL TEMPLATE (não invocar)

> **Atenção:** este arquivo é o **template canônico** para criação de novas skills no harness Kalibrium V2. Não é uma skill invocável. Copiar para `.claude/skills/<nome>.md` e preencher.
>
> Referência de skill 5/5: `.claude/skills/verify-slice.md`.
> Auditoria de qualidade: `docs/audits/quality-audit-skills-2026-04-16.md`.

---

## Uso

```
/<comando> [NNN] [args]
```

Exemplo:
```
/<comando> 001
```

## Por que existe

<1-2 parágrafos explicando o problema de negócio que a skill resolve. R12: escrever em linguagem de produto sempre que possível.>

## Quando invocar

Mínimo **3 cenários** de uso (obrigatório para Cat C; recomendado para todas):

- <cenário 1 — quando no fluxo normal>
- <cenário 2 — caso de retomada/fallback>
- <cenário 3 — ação ad hoc>

## Pré-condições

1. <pré-condição 1 — estado do repo>
2. <pré-condição 2 — artefatos necessários>
3. <pré-condição 3 — gates anteriores aprovados, se houver>

Se alguma pré-condição falha, a skill aborta e sugere o caminho correto.

## O que faz

### 1. <Etapa 1 — descrever em 1 parágrafo>

<detalhes>

### 2. <Etapa 2>

<detalhes>

### 3. <Etapa 3>

<detalhes>

### Validações automáticas

<listar checks obrigatórios — schema, integridade, etc.>

## Implementação

```bash
bash scripts/<script-da-skill>.sh "$@"
```

## Agentes

| Sub-agent | Modo | Isolamento | Budget |
|---|---|---|---|
| `<agente>` | `<modo>` | worktree isolada / sandbox hook | <N>k tokens |

(Ou: "Nenhum — executada pelo orquestrador." se a skill não spawn-a sub-agent.)

## Output

Declaração explícita do artefato gerado:

- **Formato:** JSON estruturado / Markdown R12 / mensagem no chat
- **Caminho:** `<path absoluto relativo à raiz do repo>`
- **Schema:** `docs/protocol/schemas/<schema>.schema.json` (se aplicável)
- **Lifecycle:** append-only / overwritable / versionado por data

### Output no chat

**Caso sucesso:**
```
[skill] <mensagem resumida para o PM em R12>
[skill] próximo passo: /<próximo-comando>
```

**Caso falha:**
```
[skill] ✗ <motivo> — ação: <recomendação em R12>
```

## Erros e Recuperação

| Cenário | Severidade | Ação |
|---|---|---|
| <cenário 1> | S1/S2/S3/S4/S5 | <ação em R12> |
| <cenário 2> | S1/S2/S3/S4/S5 | <ação em R12> |
| <cenário 3> | S1/S2/S3/S4/S5 | <ação em R12> |

Escala de severidade:
- **S1** — bloqueante, possível tampering, escalar PM imediatamente
- **S2** — bloqueante funcional, não prosseguir sem correção
- **S3** — requer atenção, pode degradar parcialmente
- **S4** — aviso, não bloqueia mas registrar
- **S5** — informativo

## R6 (aplicável apenas a gates)

Se a skill for um gate com dual-verifier (R11) ou loop de correção:

- Máximo **5 rejeições consecutivas** do mesmo gate entram no loop automático
- **6ª rejeição consecutiva** → `/explain-slice NNN` obrigatório + escalação PM + `docs/incidents/slice-NNN-escalation-YYYY-MM-DD.md`
- Implementer/fixer bloqueado até decisão humana

## Handoff

- <caso feliz> → `<próxima skill>`
- <caso rejeitado> → `/fix NNN [gate]` + re-run do mesmo gate
- <escalação> → `/explain-slice NNN` + PM

## Próximo passo

Sugestão única e clara ao PM em R12 (regra: **sempre uma ação), dependente do verdict:

- Sucesso → `<comando único>`
- Falha corrigível → `<comando de correção>`
- Escalação → aguardar decisão PM

## Output para PM (R12)

Toda invocação que gere artefato visível ao PM termina com mensagem em **linguagem de produto**. Nunca apresentar JSON cru, stack trace ou vocabulário técnico proibido (ver R12 em `CLAUDE.md §3.1`).

### Template de apresentação

```
<frase curta em PT-BR resumindo o que aconteceu>.
<frase sobre impacto no produto / usuário final>.

Próximo passo:
- <ação única em R12>

[ ] Aceito
[ ] Quero ajustar: <explicar>
```

## Evidência de execução

A skill não é considerada executada sem o artefato gravado/mensagem emitida. O orquestrador valida a presença do output antes de prosseguir.

## Lifecycle do artefato

- **Retenção:** permanente / 90 dias / sobrescrito a cada invocação (escolher)
- **Localização:** `<path>`
- **Referência em retrospectivas:** <sim/não>
- **Auditoria SOC 2:** <se compõe audit trail>

## Conformidade com protocolo v1.2.2

- **Agents invocados:** `<agent (mode)>` conforme mapa canônico §3.1
- **Gates produzidos:** `<enum canônico>` (ex.: verify, review, security-review, etc.) ou "não é gate"
- **Output:** `<path>` (schema formal se aplicável)
- **Schema formal:** `docs/protocol/schemas/<schema>.schema.json`
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §<N>` (se gate)
- **Isolamento R3:** descrição do isolamento (worktree / sandbox hook / não aplicável)
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0` (se gate)
- **Ordem no pipeline:** pré-requisito: `<skill anterior>`; próximo: `<skill posterior>`

## Referências

- `CLAUDE.md §<N>` — <tópico relevante>
- `docs/constitution.md §<N>` — P/R relacionado
- `.claude/agents/<agent>.md` — especificação do sub-agent invocado
- `docs/audits/quality-audit-skills-2026-04-16.md` — audit de qualidade do harness
