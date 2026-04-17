# Plano de remediação — auditoria de qualidade 2026-04-16

**Objetivo:** zerar todos os gaps apontados pela auditoria dual-LLM independente (agents v3 + skills v3) e elevar o harness de 4.84 / 4.82 para **média ≥ 4.95 com zero `aprovar com ressalvas`**.

**Entradas normativas:**
- `docs/audits/quality-audit-agents-2026-04-16-v3.md` (396 linhas, Auditor 1 Opus 4.7)
- `docs/audits/quality-audit-skills-2026-04-16-v3.md` (461 linhas, Auditor 2 Opus 4.7)
- `docs/protocol/` v1.2.2 (schema, RACI, severidade)
- `docs/constitution.md` (P1-P9, R1-R16)

**Restrições operacionais:**
- R1 — sem arquivos proibidos
- R3 — correções feitas por `builder:fixer` (único agente que escreve); re-auditoria em contexto isolado (`governance` em instância limpa)
- R9 — zero bypass de gate; a re-auditoria final precisa aprovar antes de declarar o plano concluído
- R16 — ciclo **não** é retrospectivo automatizado; é correção documental autorizada pelo PM, logo o limite de 3 mudanças por ciclo não se aplica. Registrar exceção em `docs/incidents/remediation-2026-04-16.md` no início do trabalho.

---

## 1. Inventário consolidado de gaps (14 itens)

### Agents (4 gaps)

| ID | Arquivo | Gap | Severidade | Custo |
|----|---------|-----|------------|-------|
| A-1 | `governance.md` linhas 102-132 e 298-320 | Schemas de `master-audit` e `guide-audit` faltam 14 campos do schema canônico | **S2** (estrutural) | 45 min |
| A-2 | `architecture-expert.md` linhas 146-155 e 215-246 | Schemas de `plan-review` e `code-review` faltam subset dos 14 campos | **S3** | 30 min |
| A-3 | `architecture-expert.md` (frontmatter), `qa-expert.md` (frontmatter), `orchestrator.md` (frontmatter) | Campo `changelog` ausente | **S4** | 10 min |
| A-4 | `data-expert.md` linha 192 (`tenant_id_coverage`), `observability-expert.md` linhas 151-152 (`structured_count`/`unstructured_count`) | Contagens absolutas sem ratio normalizado — inconsistente com `ux-designer` (padrão canônico) | **S4** | 20 min |
| A-5 | `devops-expert.md` linha 118 | Cláusula de isolamento R3 sem a frase "não pode ser invocado na mesma instância que outros modos de gate do mesmo slice" | **S5** (cosmético) | 5 min |

### Skills (10 gaps)

| ID | Skill(s) | Gap | Severidade | Custo |
|----|----------|-----|------------|-------|
| S-1 | `review-pr.md` linha 49 | Declara auto-dispatch de `/merge-slice` após verify+review. Contradiz pipeline v1.2.2 (faltam security, test-audit, functional, master-audit antes do merge). **Bloqueante em leitura literal.** | **S2** | 10 min |
| S-2 | `verify-slice.md`, `review-pr.md`, `security-review.md`, `test-audit.md`, `functional-review.md` (tabela "Agentes") | Tabela diz "worktree isolada" enquanto corpo explica "sandbox por hook". Mecanismo real = `verifier-sandbox.sh`. | **S3** | 30 min (5 skills) |
| S-3 | `security-review.md`, `test-audit.md`, demais gates | Duplicação de path de schema (`docs/schemas/*` vs `docs/protocol/schemas/gate-output.schema.json`) | **S3** | 20 min |
| S-4 | `guide-check.md` linha 38 | Declara "15k tokens, modelo haiku" — `governance` agent card real = opus, 60k | **S3** | 5 min |
| S-5 | `forbidden-files-scan.md`, `mcp-check.md` | Referenciam `docs/protocol/schemas/harness-audit-v1.schema.json` marcado "quando formalizado". Schema não existe. | **S3** | 45 min (formalizar) |
| S-6 | `master-audit.md` linhas 42-44, 96-98 | Cita "Opus 4.6" e "GPT-5.4"; ambiente real = Opus 4.7 + `gpt-5` default. Também contradiz sandbox policy (linha 44 `workspace-write` vs linha 143 `read-only`) | **S4** | 15 min |
| S-7 | `audit-stories.md` linha 101 ("até 3x") vs linha 73 ("5 ciclos") | Contagem R6 inconsistente dentro da mesma skill | **S4** | 5 min |
| S-8 | `intake.md` linha 120 | "modo: discovery/NFR" ambíguo — mapa canônico `00 §3.1` lista modos separados para `product-expert` | **S4** | 5 min |
| S-9 | `project-status.md` | Header H1 diz `/status`, mas CLAUDE.md e referências usam `/project-status` | **S4** | 2 min |
| S-10 | `_TEMPLATE.md` linha 132 | Parêntese órfão tipográfico | **S5** | 1 min |

**Totais por severidade:**
- S2 (bloqueante): 2 (A-1, S-1)
- S3 (estrutural): 5 (A-2, S-2, S-3, S-4, S-5)
- S4 (ajuste): 6 (A-3, A-4, S-6, S-7, S-8, S-9)
- S5 (cosmético): 2 (A-5, S-10)

**Custo estimado agregado:** ~4h de trabalho de `builder:fixer` + 1-2h de re-auditoria dual-LLM.

---

## 2. Fases de execução (em ordem de severidade)

### Fase 0 — Abertura (obrigatória)

**Owner:** `orchestrator`

1. Criar `docs/incidents/remediation-2026-04-16.md` registrando: autorização do PM, lista dos 14 gaps, escopo fechado, critério de aceite.
2. Abrir branch `chore/remediation-audits-2026-04-16` a partir de `chore/checkpoint-2026-04-16`.
3. Commit inicial "chore(audits): abre ciclo de remediação — 14 gaps autorizados".

**Critério de saída:** incidente registrado, branch criada, commit inicial.

---

### Fase 1 — Gaps S2 bloqueantes (primeiro)

**Owner:** `builder:fixer` (2 instâncias serializadas).

| Ordem | ID | Arquivo | Ação precisa |
|-------|----|----|-----|
| 1.1 | **S-1** | `.claude/skills/review-pr.md` linha 49 | Remover "skill dispara `/merge-slice NNN` automaticamente" e substituir por: "após aprovação, orchestrator invoca a cadeia de gates restantes (security-review → test-audit → functional-review → master-audit) antes de `/merge-slice`". Adicionar referência a `docs/protocol/04-criterios-gate.md`. |
| 1.2 | **A-1** | `.claude/agents/governance.md` linhas 102-132 (master-audit schema) e 298-320 (guide-audit schema) | Expandir ambos os schemas JSON para os 14 campos obrigatórios do `gate-output.schema.json` v1: `$schema`, `slice` (renomear de `slice_id`), `gate`, `lane`, `agent`, `mode`, `verdict`, `timestamp`, `isolation_context`, `commit_hash`, `blocking_findings_count`, `non_blocking_findings_count`, `findings_by_severity`, `findings`, `evidence`, `summary`. Manter extensões dual-LLM (`trail_primary`, `trail_secondary`, `reconciliation_rounds`, `consensus`) em `evidence.dual_llm`. |

**Commits:** 2 (um por gap).

**Critério de saída Fase 1:** leitura literal de `review-pr.md` não mais sugere merge pós-review; schemas em `governance.md` validam contra `docs/protocol/schemas/gate-output.schema.json`.

---

### Fase 2 — Gaps S3 estruturais

**Owner:** `builder:fixer` (serializado — 5 correções)

| Ordem | ID | Arquivo(s) | Ação precisa |
|-------|----|----|-----|
| 2.1 | **A-2** | `architecture-expert.md` linhas 146-155 (plan-review) e 215-246 (code-review) | Mesmo tratamento do A-1 — expandir para 14 campos. |
| 2.2 | **S-2** | 5 skills de gate (verify-slice, review-pr, security-review, test-audit, functional-review) | Na tabela "Agentes", substituir "worktree isolada" por "sandbox via `verifier-sandbox.sh` (read-only mount)". Alinhar com o texto explicativo de cada skill. |
| 2.3 | **S-3** | 4 skills de gate que duplicam path | Remover referências a `docs/schemas/<gate>.schema.json`. Consolidar em `docs/protocol/schemas/gate-output.schema.json` (fonte única). |
| 2.4 | **S-4** | `guide-check.md` linha 38 | Alinhar com `governance` agent card: `budget: 60k, modelo: opus`. Atualizar também o texto da linha 16 se referenciar budget/modelo. |
| 2.5 | **S-5** | Formalizar `docs/protocol/schemas/harness-audit-v1.schema.json` | Criar o schema JSON com campos obrigatórios inferidos de `forbidden-files-scan.md` (linhas 75-103) e `mcp-check.md` (linhas 65-90): `$schema`, `audit_type`, `timestamp`, `checks`, `findings`, `verdict`. Remover "quando formalizado" de ambas as skills. |

**Commits:** 5 (um por gap).

**Critério de saída Fase 2:** todos os schemas de gate JSON validam contra `gate-output.schema.json`; tabelas "Agentes" alinhadas; schema `harness-audit-v1` existe.

---

### Fase 3 — Gaps S4 (padronização e ajuste)

**Owner:** `builder:fixer`

| Ordem | ID | Arquivo(s) | Ação precisa |
|-------|----|----|-----|
| 3.1 | **A-3** | `architecture-expert.md`, `qa-expert.md`, `orchestrator.md` | Adicionar `changelog:` no frontmatter: `- 2026-04-16: v1.2.2 alignment + remediação auditoria`. |
| 3.2 | **A-4** | `data-expert.md` linha 192, `observability-expert.md` linhas 151-152 | Substituir contagens absolutas por ratios float em `[0.0, 1.0]` com threshold explícito, seguindo o padrão `ux-designer.md` linhas 207-246. Ex.: `structured_ratio: 0.98`, threshold `>= 0.95`. |
| 3.3 | **S-6** | `master-audit.md` linhas 42-44, 96-98 | Substituir "Opus 4.6" por "Opus 4.7"; "GPT-5.4" por "gpt-5". Resolver contradição de sandbox: alinhar políticas (linhas 44 e 143) — documentar que `workspace-write` é necessário em Windows e `read-only` em Linux/Mac, ou padronizar para uma única política. |
| 3.4 | **S-7** | `audit-stories.md` linha 101 | Alterar "até 3x" para "5 ciclos automáticos (6ª escala ao PM)" conforme R6. |
| 3.5 | **S-8** | `intake.md` linha 120 | Substituir "modo: discovery/NFR" por passagem serializada explícita: "fase 1: `product-expert` (modo: discovery) → fase 2: `product-expert` (modo: nfr-analysis)" conforme mapa canônico. |
| 3.6 | **S-9** | `project-status.md` header H1 | Renomear `# /status` para `# /project-status`. |

**Commits:** 6 (um por gap).

---

### Fase 4 — Gaps S5 cosméticos

**Owner:** `builder:fixer`

| Ordem | ID | Arquivo | Ação precisa |
|-------|----|----|-----|
| 4.1 | **A-5** | `devops-expert.md` linha 118 | Adicionar cláusula "não pode ser invocado na mesma instância que outros modos de gate do mesmo slice". |
| 4.2 | **S-10** | `_TEMPLATE.md` linha 132 | Corrigir parêntese órfão. |

**Commits:** 1 (agregado S5).

---

### Fase 5 — Re-auditoria dual-LLM (gate final obrigatório)

**Owner:** `governance` (2 instâncias R3 isoladas) — dual-LLM Opus 4.7 (primária) + GPT-5 via Codex CLI (secundária)

**Entradas:**
- Os 14 arquivos alterados
- Schema canônico `docs/protocol/schemas/gate-output.schema.json`
- Schema novo `docs/protocol/schemas/harness-audit-v1.schema.json`
- Relatórios anteriores (`quality-audit-agents-2026-04-16-v3.md`, `quality-audit-skills-2026-04-16-v3.md`) como baseline

**Saídas:**
- `docs/audits/quality-audit-agents-2026-04-16-v4.md`
- `docs/audits/quality-audit-skills-2026-04-16-v4.md`
- `docs/audits/remediation-verdict-2026-04-16.json` (dual-LLM consenso sobre se as correções fecharam os 14 gaps)

**Critério de aceite (APROVAÇÃO FINAL):**
1. Média agregada agents ≥ 4.95
2. Média agregada skills ≥ 4.95
3. Zero verdict `aprovar com ressalvas` em qualquer arquivo crítico (governance, architecture-expert, qa-expert, orchestrator, review-pr, master-audit)
4. Zero findings S1-S3 remanescentes
5. Consenso dual-LLM: ambas as trilhas verdict `approved` (se divergirem → E10 → até 3 rodadas → se persistir, escalar PM)

**Se algum critério falhar:**
- Loop `fixer → re-audit` com R6 (5 ciclos → 6ª escala PM)
- Documentar em `docs/incidents/remediation-2026-04-16.md` qualquer divergência

---

### Fase 6 — Fechamento

**Owner:** `orchestrator`

1. Merge da branch via `/merge-slice` adaptado (ou squash manual com pre-commit-gate).
2. Atualizar `project-state.json` com marco "harness-quality-5-of-5 achieved".
3. Gerar `docs/retrospectives/remediation-2026-04-16.md` (retrospectiva do ciclo).
4. Comunicação R12 ao PM: "Harness validado em 5/5 por auditoria dual-LLM independente."

---

## 3. Riscos e mitigações

| Risco | Probabilidade | Mitigação |
|-------|---------------|-----------|
| Correção de schema em `governance.md` introduz inconsistência com outros agentes | Média | Usar `qa-expert.md` linhas 75-107 como padrão-ouro de shape. Re-auditoria na Fase 5 detectaria. |
| Renomeação `slice_id → slice` quebra schemas existentes em produção | Baixa | Grep por `slice_id` em todo `.claude/` e `docs/` antes da mudança; atualizar onde for usado. |
| `harness-audit-v1` schema novo exigir breaking change em `forbidden-files-scan.sh` | Baixa | Schema deriva dos campos já emitidos pelo script; não requer mudança funcional. |
| Re-auditoria GPT-5 indisponível (Codex CLI offline) | Média | Fallback: auditoria single-LLM com marcação explícita `dual_llm: skipped` + requerer aprovação PM manual. |
| Escopo expande para além dos 14 gaps durante correção | Alta | Regra: qualquer gap novo encontrado durante correção é registrado em `docs/audits/gaps-backlog-2026-04-16.md` e NÃO corrigido neste ciclo (R16 — escopo fechado). |

---

## 4. Matriz de rastreabilidade

Cada commit segue o template:
```
fix(audits): <ID> — <descrição curta>

Resolve gap <ID> da auditoria 2026-04-16:
- Arquivo: <path>:<linha>
- Severidade: <S2-S5>
- Ação: <o que foi feito>

Ref: docs/audits/remediation-plan-2026-04-16.md Fase <N>.
```

Após a Fase 5 aprovada, registrar em `project-state.json`:
```json
{
  "quality_baseline": {
    "date": "2026-04-16",
    "agents_score": ">=4.95",
    "skills_score": ">=4.95",
    "gaps_remediated": 14,
    "dual_llm_verdict": "approved",
    "audit_reports": [
      "docs/audits/quality-audit-agents-2026-04-16-v4.md",
      "docs/audits/quality-audit-skills-2026-04-16-v4.md",
      "docs/audits/remediation-verdict-2026-04-16.json"
    ]
  }
}
```

---

## 5. Próxima ação

Aguardar autorização do PM com uma das 3 respostas:
- **Sim, executar plano completo** → `orchestrator` entra em Fase 0 e executa Fase 1-6 sem novas pausas (só para E10 ou R6).
- **Sim, mas em lotes** → executar Fase 1 (S2) agora, voltar ao PM para confirmar Fases 2-4, depois Fase 5-6.
- **Não, refinar plano antes** → PM aponta o que quer ajustar, plano é reemitido.

**Tempo total estimado:** ~5-6h de execução (~4h fixer + ~1h auditoria + overhead).

---

**Metadata do plano:**
- Autor: `orchestrator` (Claude Opus 4.7, sessão 2026-04-16)
- Fonte: auditoria dual-LLM v3 (Auditor 1 agents + Auditor 2 skills, ambos Opus 4.7, R3 isolados)
- Versão: 1.0.0
- Data: 2026-04-16
