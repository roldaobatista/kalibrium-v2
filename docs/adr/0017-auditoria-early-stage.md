# ADR-0017 — Auditoria early-stage (testes red, gate documental e integridade de estado)

**Data:** 2026-04-16
**Status:** accepted
**Decisor:** PM (aceito 2026-04-16, sessão de auditoria de gaps)
**Origem:** auditoria de gaps de fluxo 2026-04-16 — gaps #0, #7, #9 identificados em contexto isolado. PM aprovou agrupamento em ADR único (resposta "(a)" na sessão de discovery de gaps).

---

## Contexto

O pipeline atual tem 15 gates formais concentrados na Fase E (após implementação). Três artefatos produzidos **antes** da Fase E seguem sem auditor independente entre produção e consumo:

1. **Testes red** (`builder test-writer` → `builder implementer`): se os testes cobrirem ACs errados, o implementer "faz ficar verde" e o erro só é descoberto no gate `audit-tests` (tardio). Retrabalho garantido.

2. **Gate documental** (`/decompose-stories` → `/start-story`): `orchestrator.md` menciona validação obrigatória dos docs em `docs/documentation-requirements.md` antes de stories com UI, mas não existe script/agente que valide mecanicamente. Depende da memória do orquestrador. Drift já observado em sessões passadas (memória "Docs antes de código").

3. **`project-state.json` + handoffs**: `/checkpoint` grava e `/resume` lê, mas não há validador de schema nem reconciliação contra git HEAD / PRs merged. Se o estado divergir da realidade (ex: `epics_status` desatualizado), R13/R14 falha em cascata ao criar próximas stories.

Sintoma comum: artefato crítico consumido como input sem verificação formal. Viola o espírito de P1 (gate objetivo precede opinião) e P7 (verificação antes de afirmação).

## Opções consideradas

### Opção A — Resolver apenas o gap #0 (testes red), deixar #7 e #9 em aberto

- **Descrição:** criar só o modo `audit-tests-draft` em `qa-expert` + skill `/audit-tests-draft NNN` + estado S7.1.
- **Prós:** mudança pequena; risco operacional baixo; resolve o gap que o PM identificou sozinho.
- **Contras:** #7 e #9 continuam gerando drift silencioso; dois ADRs futuros para fechar o resto; custo acumulado maior.
- **Custo de reverter:** baixo (1 agente, 1 skill, 1 estado).

### Opção B — Resolver os 3 gaps em um único ADR (agrupamento por tema "auditoria early-stage")

- **Descrição:** 3 mudanças coordenadas:
  1. Novo modo `audit-tests-draft` em `qa-expert` + skill `/audit-tests-draft NNN` + estado S7.1 + regra AC-ID no `builder`
  2. Script `scripts/docs-gate-check.sh` validando presença dos docs obrigatórios; hook pré-`/start-story` falha duro se ausente
  3. Schema JSON formal para `project-state.json` + reconciliação no `SessionStart` (compara `epics_status` com branches merged)
- **Prós:** fecha a zona "artefato produzido sem auditor antes do consumo" em um golpe; documentação de decisão centralizada; menos commits de harness.
- **Contras:** mais arquivos afetados em um único commit (9 arquivos); maior superfície para algo dar errado na implementação.
- **Custo de reverter:** médio (mas cada mudança é independente — dá para reverter isoladamente).

### Opção C — Resolver só #7 e #9 (infra) agora, tratar #0 separado na próxima rodada

- **Descrição:** começar pelas salvaguardas de estado + gate documental, deixar audit-tests-draft para ADR futuro.
- **Prós:** #7 e #9 são transversais (afetam todo slice), ganho operacional imediato.
- **Contras:** PM já sinalizou que #0 (testes) incomoda; adiar é contraintuitivo.
- **Custo de reverter:** baixo.

## Decisão

**Opção B — 3 mudanças coordenadas.**

### Justificativa

- O PM já aprovou agrupamento em ADR único via resposta "(a)" na sessão de gaps.
- Os 3 gaps compartilham o mesmo antipattern (artefato sem auditor intermediário) — tratar juntos preserva coerência conceitual.
- Cada mudança é independente em implementação: dá para aplicar em sequência com verificação entre cada uma.
- R16 limita harness-learner a 3 mudanças incrementais por ciclo — este ADR cabe dentro desse envelope sem violar a regra (é decisão explícita do PM, não auto-aplicação do governance).

### Detalhe das 3 mudanças

**Mudança 1 — audit-tests-draft (fecha gap #0)**
- Novo modo `audit-tests-draft` em `.claude/agents/qa-expert.md` (5 → 6 modos)
- Valida: cada AC tem teste executável; cada teste referencia AC-ID (nome/docblock); testes de fato red; sem mocks onde o AC exige integração real; sem assertions fracas (`expect(x).toBeDefined()` sem semântica)
- Nova skill `.claude/skills/audit-tests-draft.md` → output `specs/NNN/tests-draft-audit.json` (schema `gate-output.schema.json`)
- Novo estado S7.1 em `orchestrator.md` entre S7 (red) e S8 (implementação)
- Regra AC-ID obrigatória em `.claude/agents/builder.md`:
  - Modo `test-writer`: cada teste gerado DEVE ter AC-ID no nome ou docblock
  - Modo `implementer`: recusa mecanicamente arquivo de teste sem AC-ID rastreável
- `/draft-tests` passa a gerar AC-ID; loop fixer→re-audit padrão (R6 na 6ª)
- **Aplicação retroativa:** NÃO. Slices já merged ficam como estão. Vale para slices a partir da data de aceite deste ADR.

**Mudança 2 — docs-gate-check (fecha gap #7)**
- Novo script `scripts/docs-gate-check.sh` que valida presença + completude mínima dos documentos listados em `docs/documentation-requirements.md`
- Hook pré-`/start-story` falha duro se story declara UI mas docs globais ou docs de épico estão ausentes
- Output estruturado para `/explain-slice` traduzir ao PM qual doc falta
- Não introduz novo agente — é validação mecânica, cabe em script

**Mudança 3 — project-state schema + reconciliação (fecha gap #9)**
- Schema JSON formal em `docs/protocol/schemas/project-state.schema.json`
- Hook no `SessionStart` (ou script invocado por ele) reconcilia `epics_status` com:
  - PRs merged no git (via `gh pr list --state merged`)
  - Branches que existem / não existem
  - `merged_slices` em cada épico
- Se houver drift, aborta SessionStart com relatório estruturado apontando divergência
- Reconciliação é READ-ONLY — nunca corrige automaticamente; só reporta e exige ação explícita do PM (relock do baseline ou correção manual)

**Reversibilidade:** média. Cada mudança é independente; remover qualquer uma volta ao fluxo atual sem quebrar estado.

## Consequências

### Positivas

- Pipeline Fase A-D ganha auditoria equivalente à que Fase E já tem em código.
- Retrabalho de implementação por "testes cobrem AC errado" vira evento raro (pega antes de gastar ciclo de implementer).
- Orquestrador deixa de "lembrar" do gate documental — vira obrigatoriedade mecânica.
- Drift de `project-state` vira falha visível no SessionStart, não silenciosa durante semanas.
- Rastreabilidade AC ↔ teste ↔ código vira mecanicamente enforçada (`@covers AC-NNN` obrigatório).

### Negativas

- Pipeline ganha +1 gate no caminho crítico (audit-tests-draft). Estimativa: +2-4 min por slice.
- Builder/test-writer tem restrição nova (AC-ID obrigatório) — pode quebrar templates se não for atualizado junto.
- SessionStart fica mais lento se `gh pr list` for chamado toda vez — mitigável com cache local de 5 min.
- Script docs-gate-check precisa manutenção quando `docs/documentation-requirements.md` mudar.

### Riscos

- **Risco R1:** audit-tests-draft ficar excessivamente rigoroso e gerar loops de fix de findings cosméticos. Mitigação: critérios objetivos enumerados (não "má qualidade"), zero-tolerance vale apenas S1-S3.
- **Risco R2:** reconciliação de project-state falsa positiva ao detectar PRs externos (não-Kalibrium). Mitigação: filtrar por label ou branch prefix `specs/NNN-`.
- **Risco R3:** hook docs-gate-check bloquear stories de E03 em diante se a documentação global não existir. Mitigação: validar antes do merge deste ADR que todos os docs globais obrigatórios existem; se não existirem, este ADR entra em `accepted pending` e a aplicação do hook ocorre só depois de os docs estarem prontos.

### Impacto em outros artefatos

- **Hooks afetados:** `session-start.sh` (nova chamada), novo hook pre-`/start-story`.
- **Sub-agents afetados:** `qa-expert.md` (modo 6), `builder.md` (regra AC-ID), `orchestrator.md` (estado S7.1 + fluxo).
- **Skills afetadas:** `.claude/skills/audit-tests-draft.md` (nova), `.claude/skills/draft-tests.md` (gera AC-ID), `.claude/skills/start-story.md` (hook docs).
- **Protocolo afetado:** `docs/protocol/00-protocolo-operacional.md` (mapa canônico qa-expert 6 modos), `docs/protocol/03-contrato-artefatos.md` (`tests-draft-audit.json`, `project-state.schema.json`), `docs/protocol/04-criterios-gate.md` (gate #16 audit-tests-draft).
- **CLAUDE.md:** §6 Fase D (passo 19.1 novo).
- **ADRs relacionados:** ADR-0012 (autonomia dual-LLM — este ADR não afrouxa dual-verifier, reforça), ADR-0016 (este ADR não toca isolamento mas reforça rastreabilidade).

## Referências

- Auditoria de gaps 2026-04-16 (sessão atual, sub-agent general-purpose em contexto isolado)
- `docs/protocol/04-criterios-gate.md` — estrutura dos 15 gates existentes
- `docs/protocol/schemas/gate-output.schema.json` — schema que audit-tests-draft reusa
- CLAUDE.md §6 Fase D — fluxo de testes red e implementação

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas (A, B, C)
- [x] Decisão justificada com razão objetiva
- [x] Reversibilidade declarada (média, por componente)
- [x] Consequências negativas listadas
- [x] Não contradiz ADR anterior
- [x] Impacto em hooks/agents/constitution endereçado
- [x] PM aprovou (2026-04-16 — aceito integralmente sem ajustes)
- [ ] Pré-condição atendida: docs globais obrigatórios existentes antes de ativar hook da Mudança 2 (a validar durante implementação)
