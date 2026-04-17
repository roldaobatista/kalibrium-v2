---
description: Dispara o sub-agent builder (test-writer) para gerar testes red a partir de spec.md + plan.md aprovados. Valida pré-condições, spawna builder (test-writer), confirma que testes nascem vermelhos, e apresenta resultado ao PM em linguagem de produto (R12). Uso: /draft-tests NNN.
protocol_version: "1.2.4"
changelog:
  - "2026-04-16 — quality audit fix SK-005R"
  - "2026-04-16 — ADR-0017 Mudanca 1: testes gerados com AC-ID rastreavel obrigatorio; proximo passo apos draft-tests e /audit-tests-draft NNN (nao mais direto para implementer)"
---

# /draft-tests

## Uso
```
/draft-tests NNN
```

## Por que existe
Sem esta skill, o PM precisa saber que "agora é hora de gerar testes" e o agente principal precisa lembrar da sequência spec → plan → testes. `/draft-tests` é o handoff explícito entre plan aprovado e geração de testes que sobrevive ao fim de sessão.

**Resolve G-07 da auditoria de operabilidade PM 2026-04-12.**

## Quando invocar
Depois que `specs/NNN/plan.md` passou por `/review-plan NNN`, foi aprovado com `findings: []`, e o PM aprovou o plano. Rodar **antes** de qualquer código de produção.

## Pré-condições
- `specs/NNN/spec.md` existe com ACs numerados
- `specs/NNN/plan.md` existe com status `approved`
- `specs/NNN/plan-review.json` existe com `agent: architecture-expert`, `mode: plan-review`, `verdict: approved`, `blocking_findings_count: 0` (conforme schema `docs/protocol/schemas/gate-output.schema.json`)
- Nenhum código de produção do slice foi commitado ainda

## O que faz

### Fase 1 — Validação mecânica

```bash
bash scripts/draft-tests.sh NNN --check
```

Se falhar, mostra ao PM o que falta em linguagem R12 e para.

### Fase 2 — Disparar builder (test-writer)

Spawna o sub-agent `builder` (modo: test-writer) (`.claude/agents/builder.md`) com:
- `subagent_type: "builder"`
- Prompt contendo o NNN do slice
- O `builder` (modo: test-writer) lê spec.md + plan.md e gera testes em `tests/`

**Instrucao obrigatoria ao test-writer (ADR-0017 Mudanca 1):**
- Cada teste DEVE ter AC-ID rastreavel por um dos 3 metodos:
  1. Nome do teste contem AC-ID (ex: `it('AC-001: retorna 422 quando ...', ...)`)
  2. Docblock com `@covers AC-NNN`
  3. `describe('AC-NNN: ...', ...)` agrupando testes correlatos
- Testes auxiliares (helpers, setup) devem declarar `@helper` ou `@setup`
- Nenhum teste "solto" (sem AC-ID e sem tag) sera aceito pelo gate `audit-tests-draft` subsequente

### Fase 3 — Confirmar testes vermelhos

O `builder` (modo: test-writer) já roda cada teste e confirma que falham (red). Se algum nascer verde, é rejeitado pelo hook `post-edit-gate.sh`.

### Fase 4 — Validar cobertura

```bash
bash scripts/draft-tests.sh NNN --validate
```

Confirma que cada AC do spec tem pelo menos 1 teste correspondente.

### Fase 5 — Apresentar ao PM (R12)

Traduz o resultado em linguagem de produto. Exemplo:

```
Os testes automáticos do slice NNN foram criados.

O que foi gerado:
- N testes, um para cada requisito que você definiu
- Todos estão "vermelhos" (falhando) de propósito — isso é esperado
  (eles vão ficar verdes quando o código for implementado)

Cobertura:
- AC-001 (cadastro de cliente) → 1 teste ✓
- AC-002 (validação de CNPJ) → 1 teste ✓
- AC-003 (lista paginada) → 1 teste ✓

Próximo passo:
[ ] Aceito — commitar testes e começar implementação
[ ] Quero revisar — diga o que ajustar
```

**NUNCA** mostrar nomes de arquivo de teste, código-fonte, stack traces, ou exit codes ao PM.

## Handoff
- **PM aceita** → commitar com `test(slice-NNN): AC tests red` e orquestrador invoca **`/audit-tests-draft NNN`** automaticamente (ADR-0017 Mudanca 1). Implementer **nao** e invocado direto.
- **PM pede ajuste** → re-disparar `builder` (modo: test-writer) com instruções adicionais
- **PM quer pausar** → registrar estado e encerrar

## Agentes
- `builder` (modo: test-writer) — gera testes red a partir de spec.md + plan.md, um teste por AC no mínimo (conforme mapa canonico 00 §3.1)

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `specs/NNN/plan.md` não existe ou não está `approved` | Abortar e sugerir `/draft-plan NNN` para gerar/aprovar o plan primeiro. |
| `specs/NNN/plan-review.json` ausente, sem proveniencia do `architecture-expert` (modo: plan-review), reprovado ou com findings | Abortar e rodar `/review-plan NNN`; se houver findings, corrigir todos e reauditar. |
| `builder` (modo: test-writer) gera teste que nasce verde (não falha) | Rejeitar o teste via `post-edit-gate.sh`. Re-spawnar `builder` (modo: test-writer) com instrução para garantir red. Fazer até 5 ciclos automáticos; na 6ª falha consecutiva, escalar humano (R6). |
| `draft-tests.sh --validate` detecta AC sem teste correspondente | Listar os ACs descobertos e re-spawnar `builder` (modo: test-writer) com foco nos ACs faltantes. |
| Stack/framework ainda não está instalado para rodar testes | Informar PM que a infraestrutura de testes precisa ser configurada primeiro. Sugerir resolução antes de prosseguir. |

## Regras
- Todo AC do spec DEVE ter pelo menos 1 teste (P2)
- Nunca gerar testes sem `plan-review.json` aprovado, com proveniencia do `architecture-expert` (modo: plan-review) em contexto `isolated` e `findings: []`
- Todo teste DEVE falhar na primeira execução (red) — nascer verde é bug do teste
- Não mockar o módulo sob teste (regra anti-teste-tautológico C1)
- Não inventar testes para requisitos que não estão no spec
- Até 5 ciclos automáticos de re-geração. Na 6ª falha consecutiva, escalar humano (R6)

## Conformidade com protocolo v1.2.4

- **Agents invocados:** `builder (test-writer)` — conforme mapa canonico 00 §3.1
- **Gates produzidos:** n/a — skill de geracao de artefatos de teste, nao gate
- **Output:** arquivos de teste em `tests/Feature/SliceNNN/` e `tests/Unit/SliceNNN/` (Pest 4), 1+ teste por AC do spec
- **Schema formal:** nao aplicavel (output e codigo de teste, nao JSON de gate)
- **Isolamento R3:** nao aplicavel — builder (test-writer) roda no contexto principal; isolamento ocorre no verify subsequente
- **Zero-tolerance:** todo teste deve nascer red (P2); teste green sem implementacao e bloqueado pelo hook `post-edit-gate.sh`
- **Ordem no pipeline:** pre-requisito: `/review-plan NNN` approved com `findings: []`; proximo: implementacao pelo `builder (implementer)`, depois `/verify-slice NNN`
- **Referencia normativa:** `CLAUDE.md §6 Fase D`; `docs/constitution.md §2 P2` (AC e teste executavel escrito antes do codigo), §4 R9 (zero bypass)
