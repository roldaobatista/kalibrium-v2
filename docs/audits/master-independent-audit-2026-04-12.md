# Master Independent Audit — 2026-04-12

**Auditor:** Claude Opus 4.6 (master auditor independente)
**Escopo:** Verificação dos 9 achados da auditoria externa + contra-parecer interno
**Método:** Leitura direta de código, execução de scripts, validação cruzada schema×JSON

---

## Status de Correção (atualizado 2026-04-12 noite)

| # | Achado | Status | Evidência |
|---|---|---|---|
| F1 | Gate JSONs inválidos | **CORRIGIDO** | 4 JSONs reescritos conforme schema. `verify-slice.sh 900 --validate` → exit 0 |
| F2 | Isolamento 3 gates | **CORRIGIDO** | `verifier-sandbox.sh` com sandbox para 5 gates. Selos OK. Smoke 75/75. Commit `a96d588` |
| F3 | Smoke test destrutivo | **CORRIGIDO** | `smoke-test-hooks.sh` usa mv/restore. 75/75 passam |
| F4 | CRLF smoke-test-scripts | **CORRIGIDO** | Arquivo reescrito com LF. 21/21 passam |
| F5 | PHP no Bash | **FALSO POSITIVO** | `mechanical-gates.sh 900 --quick` → exit 0 |
| F6 | Telemetria quebrada | **CORRIGIDO** | `record-telemetry.sh` tolera linhas pré-schema. Chain validada: 22 linhas OK |
| F7 | Estado divergente | **FALSO POSITIVO** | Contextos complementares, não contraditórios |
| F8 | Caminhos fantasma | **CORRIGIDO** | `domain_model_exists: false`, 6 refs `prd.md` → `PRD.md` |
| F9 | Worktree contraditória | **CORRIGIDO** | constitution.md R3 atualizado para refletir mecanismo real |
| — | orchestrator.md frontmatter | **CORRIGIDO** | tools: normalizado para string |
| — | smoke-test-hooks.sh test 21 | **CORRIGIDO** | ADR escondido temporariamente para teste de bloqueio |

---

## Sumário Executivo

Dos 9 achados originais, **5 são confirmados**, **2 são confirmados parcialmente**, e **2 são falsos positivos**. O contra-parecer interno (que deu nota 9/10 ao harness) **subestimou problemas reais** — particularmente os findings 1 e 2, que são blockers legítimos.

---

## Matriz de Achados

| # | Severidade | Achado Original | Veredicto | Justificativa |
|---|---|---|---|---|
| F1 | **BLOCKER** | 4/5 gate JSONs falham contra schema | **CONFIRMADO** | verification.json: `slice` vs `slice_id`, `next_action` fora do enum, `id` vs `ac`. security-review.json: `lgpd_checks.status: "na"` vs `"not_applicable"`, `next_action: "proceed"` fora do enum. test-audit.json: falta `timestamp`, `coverage_summary`, `anti_patterns`; estrutura de `ac_coverage` completamente diferente do schema. functional-review.json: falta `ac_assessment`, `ux_findings`, `consistency_findings`, `business_rule_findings`. Único que passa: review.json. |
| F2 | **BLOCKER** | Isolamento dos 3 gates novos não implementado | **CONFIRMADO** | `verifier-sandbox.sh` tem `case` apenas para `verifier` e `reviewer`. `security-reviewer`, `test-auditor` e `functional-reviewer` passam pelo `*)` default que faz `exit 0` sem restrição. 6 arquivos (3 skills + 3 agents) prometem isolamento que não existe mecanicamente. |
| F3 | **MAJOR** | smoke-test-hooks.sh destrói ADR real | **CONFIRMADO** (com precisão) | Linha 148: `rm -f docs/adr/0001-stack-choice.md` é incondicional. Se o arquivo real existir, é deletado. O `touch` (linha 145) não é destrutivo, mas o `rm -f` é. |
| F4 | **MAJOR** | smoke-test-scripts.sh CRLF | **CONFIRMADO** | `file` confirma: `with CRLF line terminators`. Causa `set: pipefail\r: invalid option name` em Bash. |
| F5 | **MAJOR** | mechanical-gates.sh falha por falta de PHP no Bash | **FALSO POSITIVO** | `which php` retorna caminho válido. Executei `bash scripts/mechanical-gates.sh 900 --quick` — saiu 0, todos os gates passaram. O script tem guard `command -v php` que faz skip gracioso. |
| F6 | **MAJOR** | Telemetria quebrada (sem prev_hash/schema_version) | **PARCIALMENTE CONFIRMADO** | Linhas 1-10 de meta.jsonl: formato antigo sem `prev_hash` nem `schema_version` — viola schema v1.0.0. Linhas 11+: conformes. Causa: migração não retroagiu sobre entradas pré-schema. Chain de integridade quebrada no trecho histórico. |
| F7 | **MAJOR** | Estado divergente (project-state vs session-start) | **FALSO POSITIVO** | São contextos distintos: project-state.json fala da fase do projeto (Fase C → /decompose-epics), session-start.sh fala do estado pendente do slice-900. Complementares, não contraditórios. |
| F8 | **MAJOR** | Caminhos/artefatos inconsistentes | **CONFIRMADO** | `domain_model_exists: true` mas `docs/product/domain-model.md` não existe. Case mismatch confirmado: 3 skills (`decompose-epics.md`, `freeze-architecture.md`, `status.md`) + 1 agent (`epic-decomposer.md`) referenciam `prd.md` (minúsculo); arquivo real é `PRD.md`. Em Windows funciona por acaso (case-insensitive), mas quebraria em Linux/CI. |
| F9 | **MAJOR** | Regra de worktree contraditória | **PARCIALMENTE CONFIRMADO** | constitution.md R3 diz "verifier em worktree descartável". verify-slice.sh e verify-slice.md dizem "NÃO usar worktree" (porque input package é untracked). O isolamento funciona por mecanismo alternativo (verifier-sandbox.sh), mas a constituição não foi atualizada. Inconsistência documental, não falha operacional. |

---

## Avaliação do Contra-Parecer Interno

O contra-parecer que deu nota 9/10 aos agents e skills e "veredicto geral profissional" **é excessivamente otimista**:

| Claim do contra-parecer | Avaliação |
|---|---|
| "Agents 9/10 — só 2 precisam fix" | **Justo.** 13/15 agents têm frontmatter correto. guide-auditor e orchestrator têm issues menores. |
| "Skills 9/10 — auditor se confundiu com formato" | **Parcialmente justo.** Skills são bem escritos, mas o claim ignora que 4+ skills referenciam caminhos que não existem (`prd.md` minúsculo, `domain-model.md`). |
| "Schemas 10/10" | **Justo para os schemas em si.** Mas irrelevante se os JSONs produzidos não os respeitam (F1). |
| "Hooks 7/10 — débito menor, não bloqueante" | **Subestimado.** F2 (isolamento não implementado) é blocker, não débito menor. F3 (smoke test destrutivo) é major. |
| "Veredicto: harness profissional e de alto nível" | **Prematuro.** Com 2 blockers e 5 majors confirmados, o harness tem base sólida mas não está operacionalmente confiável. |

---

## Classificação de Severidade

### Blockers (devem ser corrigidos antes de qualquer slice real)

1. **F1 — Gate JSONs inválidos.** Os 5 gates do slice 900 são declarados "approved" no project-state.json, mas 4/5 JSONs não validam contra schema. Isso significa que o pipeline de gates **nunca foi realmente validado end-to-end**. Qualquer futuro slice que passe pelos mesmos gates herdará JSONs inválidos.

2. **F2 — Isolamento de 3 gates inexistente.** security-reviewer, test-auditor e functional-reviewer operam sem sandbox. Podem ler CLAUDE.md, plan.md, git history — tudo que o princípio P3 proíbe. Os resultados desses gates para o slice 900 não são confiáveis.

### Majors (devem ser corrigidos antes do pipeline ser usado novamente)

3. **F3 — Smoke test destrutivo.** `rm -f docs/adr/0001-stack-choice.md` pode deletar ADR real.
4. **F4 — CRLF em smoke-test-scripts.sh.** Script inutilizável em Bash.
5. **F6 — Telemetria parcialmente quebrada.** 10 linhas históricas violam schema. Chain de integridade comprometida.
6. **F8 — Caminhos fantasma.** `domain-model.md` declarado existente mas ausente. Case mismatch `prd.md`/`PRD.md` quebraria em Linux.
7. **F9 — Constituição desatualizada.** R3 promete worktree que não é usada. Doc deve refletir realidade.

---

## Ordem de Correção Recomendada

### Fase 1 — Blockers (imediato)

1. **Regenerar JSONs do slice 900** conformes aos schemas, OU marcar slice 900 como "smoke-test/não-confiável" no project-state.json.
2. **Implementar isolamento** em `verifier-sandbox.sh` para `security-reviewer`, `test-auditor`, `functional-reviewer`.

### Fase 2 — Majors (antes do próximo slice)

3. **Corrigir smoke-test-hooks.sh** — usar arquivo temporário em vez de ADR real.
4. **Corrigir CRLF** em smoke-test-scripts.sh (`dos2unix` ou `sed`).
5. **Migrar telemetria** — adicionar `prev_hash: "GENESIS"` e `schema_version` às 10 linhas antigas, ou documentar como "pre-schema, excluído da validação de chain".
6. **Corrigir project-state.json** — `domain_model_exists: false` (ou criar o arquivo).
7. **Normalizar referências de caso** — `prd.md` → `PRD.md` nos 4 arquivos afetados.
8. **Atualizar constitution.md R3** — refletir que isolamento é via hook, não worktree.

### Fase 3 — Menores (melhoria contínua)

9. Frontmatter do guide-auditor.md e orchestrator.md.
10. `set -euo pipefail` nos 8 hooks que faltam.
11. Alinhar versões constitution (1.1.0) vs CLAUDE.md (2.1.0).

---

## Veredicto Final

**O harness tem arquitetura ambiciosa e bem pensada, mas não está operacionalmente validado.** A base conceitual (15 agents, 33 skills, 5 gates, hooks de proteção, schemas formais) é sólida. Porém, o pipeline de gates — peça central da confiabilidade — nunca funcionou corretamente end-to-end: os JSONs não respeitam os schemas, 3 de 5 gates não têm isolamento real, e o smoke test que deveria validar tudo é ele próprio destrutivo.

**Nota geral: 6/10** — base excelente, execução incompleta. Com as correções das Fases 1 e 2, sobe para 8/10.
