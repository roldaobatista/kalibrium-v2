---
description: Congela o PRD para a fase de estrategia tecnica. Valida que intake foi feito, PRD esta completo, NFRs existem. Muda status do PRD para 'frozen'. Nenhuma decisao tecnica antes deste gate. Uso: /freeze-prd.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005"
---

# /freeze-prd

## Uso
```
/freeze-prd
```

## Por que existe
O PRD e a base de todas as decisoes tecnicas. Se ele mudar durante a fase de arquitetura/implementacao, o retrabalho e enorme. Este gate garante que o PM revisou e aceitou o PRD antes de qualquer decisao tecnica.

## Quando invocar
Apos `/intake` e revisao do PRD pelo PM. Antes de qualquer decisao de stack/arquitetura.

## Pre-condicoes (validadas)
1. `docs/product/intake-responses.md` existe e nao tem perguntas pendentes criticas
2. `docs/product/PRD.md` existe (pode ser o PRD compactado existente ou um novo)
3. `docs/product/nfr.md` existe com pelo menos 3 NFRs
4. `docs/product/domain-model.md` existe
5. `docs/product/glossary-domain.md` existe
6. `docs/product/personas.md` existe
7. `docs/product/mvp-scope.md` existe

## O que faz

### 1. Validacao de completude
Verifica cada pre-condicao. Se alguma falhar, lista o que falta e para.

### 2. Validacao de consistencia
- NFRs referenciam termos do glossario
- MVP scope esta dentro do PRD
- Personas mencionadas no PRD existem em personas.md
- Nenhuma contradição obvia entre PRD e intake-responses

### 3. Checklist para o PM
Apresenta em linguagem R12:
```
Estou pronto para congelar o PRD. Isso significa que:

✅ As respostas do intake estao incorporadas
✅ O escopo MVP esta definido
✅ Os requisitos nao-funcionais estao documentados
✅ As personas e jornadas estao mapeadas
✅ O glossario de dominio existe

Apos congelar, mudancas no PRD so via processo formal
(novo intake parcial + revisao).

Confirma o congelamento? (sim/nao)
```

### 4. Congelamento
Se PM confirmar:
1. Adicionar header ao PRD: `Status: FROZEN — YYYY-MM-DD`
2. Atualizar `project-state.json`:
   ```json
   { "discovery": { "prd_status": "frozen" } }
   ```
3. Criar snapshot: `docs/product/snapshots/prd-frozen-YYYY-MM-DD.md`
4. Registrar em telemetria

### 5. Proximo passo
```
PRD congelado. Proximo passo: definir a estrategia tecnica.

Opcoes:
1. /decide-stack — se a stack ainda nao foi decidida
2. /freeze-architecture — se ADRs e arquitetura ja estao prontos

Qual prefere?
```

## Agentes
Nenhum — executada pelo orquestrador.

## Erros e Recuperacao

| Erro | Recuperacao |
|---|---|
| Pre-condicao falha (arquivo ausente) | Listar todos os arquivos faltantes de uma vez. Sugerir a skill que gera cada um (ex: `/intake` para intake-responses.md). |
| Inconsistencia detectada entre PRD e intake-responses | Apresentar a inconsistencia ao PM em linguagem R12. Nao congelar ate resolver. PM decide qual versao prevalece. |
| `project-state.json` nao existe ou esta corrompido | Criar/recriar o arquivo com estado minimo antes de registrar o freeze. Informar PM que estado foi reconstruido. |

## Handoff
- PM confirma → congelar e sugerir proximo passo
- PM recusa → listar o que quer mudar, voltar ao PRD
- Pre-condicao falha → listar faltantes, sugerir skill adequada

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador executa validação + muta status).
- **Gates produzidos:** gate de fase (transição Descoberta → Estratégia); não é gate de slice.
- **Output:** `docs/product/PRD.md` com header `Status: FROZEN — YYYY-MM-DD` + snapshot imutável.
- **Schema formal:** snapshot em `docs/product/snapshots/prd-frozen-YYYY-MM-DD.md`.
- **Isolamento R3:** não aplicável (sem sub-agent).
- **Ordem no pipeline:** após `/intake` + revisão PM; precede `/decide-stack` e `/freeze-architecture`.
