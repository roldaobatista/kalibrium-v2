---
description: Inicia implementacao de uma story aprovada. Valida Story Contract, cria slice(s) correspondente(s), atualiza project-state. Ponte entre planejamento e execucao. Uso: /start-story ENN-SNN.
protocol_version: "1.2.4"
changelog:
  - "2026-04-16 — quality audit fix SK-005R"
  - "2026-04-16 — ADR-0017 Mudanca 2: docs-gate-check obrigatorio antes de criar slice (gate documental mecanicamente enforcado — fecha gap #7 da auditoria de fluxo 2026-04-16)"
---

# /start-story

## Uso
```
/start-story ENN-SNN
```

Exemplo: `/start-story E01-S01`

## Por que existe
Ponte entre planejamento e execucao. Garante que a story tem contrato aprovado antes de criar slices e iniciar implementacao. Atualiza o estado do projeto para rastrear progresso.

## Quando invocar
Apos `/decompose-stories` e aprovacao do Story Contract pelo PM.

## Pre-condicoes (validadas)
1. `epics/ENN/stories/ENN-SNN.md` existe (Story Contract)
2. Story Contract esta aprovado (marcado pelo PM)
3. **Branch != main (B-023):** verificar `git branch --show-current`. Se retornar `main`, **bloquear** e orientar:
   > "Voce esta em `main`. Crie uma feature branch antes de iniciar o slice: `git checkout -b feat/ENN-SNN` ou `git worktree add ../ENN-SNN feat/ENN-SNN`."
   Nao prosseguir ate que a branch atual seja diferente de `main`. Bypass: `KALIB_SKIP_BRANCH_CHECK="<motivo>"` registra incidente em `docs/incidents/`.
4. **R13/R14 (ADR-0011):** `scripts/start-story.sh ENN-SNN` (ou `scripts/sequencing-check.sh --story ENN-SNN`) retorna 0 ou 5. Gate bloqueia se:
   - stories anteriores do mesmo epico nao estao `merged` em `project-state.json[epics_status]` (R13), ou
   - e a primeira story do epico e o epico anterior MVP nao esta 100% `merged` (R14).
   - Paralelismo intra-epico permitido quando o Story Contract declara `dependencies: []` no frontmatter.
   - Bypass: `KALIB_SKIP_SEQUENCE="<motivo>"` registra incidente e autoriza.
4b. **Docs-gate-check (ADR-0017 Mudanca 2):** `bash scripts/docs-gate-check.sh --story ENN-SNN` deve retornar exit 0. Gate bloqueia se:
   - qualquer doc global obrigatorio esta ausente (`docs/product/PRD.md`, `personas.md`, `journeys.md`, `mvp-scope.md`, `nfr.md`, `constitution.md`, `TECHNICAL-DECISIONS.md`, `documentation-requirements.md`), OU
   - se a story declara `ui: true` no frontmatter, qualquer doc de UI esta ausente (`sitemap.md`, `ui-flows.md`, `persona-scenarios.md`)
   - Relatorio detalhado em `docs/audits/docs-gate-<story>-<ts>.json` quando falha.
   - Orquestrador apresenta ao PM via `/explain-slice` em linguagem R12: "Nao posso iniciar esta story porque faltam N documentos obrigatorios. Quer que eu crie os documentos primeiro ou pular este gate com justificativa?"
   - Bypass: `KALIB_SKIP_DOCS_GATE="<motivo>"` registra incidente.
5. Nenhum slice ativo bloqueado por R6.
6. Arquitetura congelada.

## O que faz

### 1. Validar Story Contract
Verificar que todas as secoes obrigatorias estao preenchidas:
- [ ] Objetivo nao vazio
- [ ] Escopo com pelo menos 1 item
- [ ] Fora de escopo definido
- [ ] Pelo menos 2 ACs
- [ ] ACs numerados sequencialmente
- [ ] ACs testáveis (nao subjetivos)
- [ ] Riscos documentados
- [ ] Evidencia necessaria definida

Se algum check falhar, reportar e parar.

### 2. Criar slice(s)
Para cada slice mapeado na story (normalmente 1, pode ser 2-3 para stories grandes):

```bash
# Equivalente a /new-slice NNN "ENN-SNN: titulo"
```

Criar esqueleto em `specs/NNN/`:
- `spec.md` — preenchido a partir do Story Contract (ACs ja vem prontos)
- `plan.md` — vazio (sera gerado pelo `architecture-expert` (modo: plan))
- `tasks.md` — vazio

### 3. Atualizar project-state
```json
{
  "execution": {
    "current_epic": "E01",
    "current_story": "E01-S01",
    "current_slice": "slice-013",
    "slice_status": "spec",
    "consecutive_rejections": 0,
    "blocked": false
  }
}
```

### 4. Apresentar ao PM
```
Story E01-S01 iniciada!

📋 "Scaffold do projeto Laravel conforme ADR-0001"
   Slice criado: slice-013
   ACs: 4 criterios de aceite

O spec.md ja esta preenchido com os ACs do contrato.

Proximo passo: auditar a spec antes do plano tecnico.
→ /audit-spec 013

Ou, se quiser revisar o spec primeiro:
→ Abra specs/013/spec.md

Posso prosseguir com /audit-spec?
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Story Contract não encontrado em `epics/ENN/stories/ENN-SNN.md` | Verificar se `/decompose-stories ENN` foi executado. Se não, executar primeiro. |
| Story Contract incompleto (seções obrigatórias faltando) | Listar seções faltantes ao PM. Não prosseguir até que o contrato esteja completo. |
| Dependências da story não satisfeitas | Listar stories bloqueantes e sugerir executá-las primeiro ou reordenar prioridades. |
| Slice ativo bloqueado por R6 | Resolver escalação R6 pendente antes de iniciar nova story. Sugerir `/explain-slice NNN` para o PM entender o bloqueio. |

## Agentes

Nenhum — executada pelo orquestrador.

## Handoff
- PM confirma → `/audit-spec NNN` → `/draft-plan NNN` → `/review-plan NNN` → `/draft-tests NNN` → implementacao
- PM quer ajustar spec → editar `specs/NNN/spec.md` e reapresentar
- Pre-condicao falha → listar o que falta

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum sub-agent — executada pelo orquestrador (orquestrador valida Story Contract, cria slice, atualiza project-state)
- **Gates produzidos:** n/a — gate mecanico pre-execucao (R13/R14 sequencing via `scripts/sequencing-check.sh`), nao gate JSON
- **Output:** `specs/NNN/spec.md` (esqueleto preenchido com ACs do Story Contract) + mutacao em `project-state.json[execution]`
- **Schema formal:** nao aplicavel — skill cria esqueleto, nao gate output
- **Isolamento R3:** nao aplicavel — skill de orquestracao; gates subsequentes aplicam isolamento
- **Zero-tolerance:** bloqueia duro se R13/R14 violados (story anterior nao-merged) ou branch == main; bypass somente via `KALIB_SKIP_SEQUENCE` com incidente
- **Ordem no pipeline:** pre-requisito: Story Contract aprovado em `epics/ENN/stories/ENN-SNN.md`; proximo: `/audit-spec NNN`
- **Referencia normativa:** `CLAUDE.md §6 Fase D`; `docs/constitution.md §4 R13` (ordem intra-epico), §4 R14 (ordem inter-epico MVP); ADR-0011
