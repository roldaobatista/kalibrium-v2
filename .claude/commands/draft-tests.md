---
description: Dispara o sub-agent ac-to-test para gerar testes red a partir de spec.md + plan.md aprovados. Valida pré-condições, spawna ac-to-test, confirma que testes nascem vermelhos, e apresenta resultado ao PM em linguagem de produto (R12). Uso: /draft-tests NNN.
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
Depois que o PM aprovou `specs/NNN/plan.md` (via `/draft-plan NNN`) e **antes** de qualquer código de produção.

## Pré-condições
- `specs/NNN/spec.md` existe com ACs numerados
- `specs/NNN/plan.md` existe com status `approved`
- Nenhum código de produção do slice foi commitado ainda

## O que faz

### Fase 1 — Validação mecânica

```bash
bash scripts/draft-tests.sh NNN --check
```

Se falhar, mostra ao PM o que falta em linguagem R12 e para.

### Fase 2 — Disparar ac-to-test

Spawna o sub-agent `ac-to-test` (`.claude/agents/ac-to-test.md`) com:
- `subagent_type: "ac-to-test"`
- Prompt contendo o NNN do slice
- O ac-to-test lê spec.md + plan.md e gera testes em `tests/`

### Fase 3 — Confirmar testes vermelhos

O ac-to-test já roda cada teste e confirma que falham (red). Se algum nascer verde, é rejeitado pelo hook `post-edit-gate.sh`.

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
- **PM aceita** → commitar com `test(slice-NNN): AC tests red` e sugerir início da implementação
- **PM pede ajuste** → re-disparar ac-to-test com instruções adicionais
- **PM quer pausar** → registrar estado e encerrar

## Agentes
- `ac-to-test` — gera testes red a partir de spec.md + plan.md, um teste por AC no mínimo

## Erros e Recuperação

| Erro | Recuperação |
|---|---|
| `specs/NNN/plan.md` não existe ou não está `approved` | Abortar e sugerir `/draft-plan NNN` para gerar/aprovar o plan primeiro. |
| `ac-to-test` gera teste que nasce verde (não falha) | Rejeitar o teste via `post-edit-gate.sh`. Re-spawnar ac-to-test com instrução para garantir red. Se falhar 2x, escalar humano (R6). |
| `draft-tests.sh --validate` detecta AC sem teste correspondente | Listar os ACs descobertos e re-spawnar ac-to-test com foco nos ACs faltantes. |
| Stack/framework ainda não está instalado para rodar testes | Informar PM que a infraestrutura de testes precisa ser configurada primeiro. Sugerir resolução antes de prosseguir. |

## Regras
- Todo AC do spec DEVE ter pelo menos 1 teste (P2)
- Todo teste DEVE falhar na primeira execução (red) — nascer verde é bug do teste
- Não mockar o módulo sob teste (regra anti-teste-tautológico C1)
- Não inventar testes para requisitos que não estão no spec
- Máximo 2 tentativas de re-geração. Na 3ª falha, escalar humano (R6)
