# Emenda à decisão PM pós re-auditoria dual-LLM

**Data:** 2026-04-15 (mesmo dia da decisão original)
**Emenda de:** `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md` (commit `f70f376`)
**Decisor:** PM (roldaobatista)
**Autorização:** "vamos trabalhar sem resolver essa conta do github por enquanto" (PM, 2026-04-15)

## Contexto

Após o orquestrador executar os pré-requisitos 2-8 da decisão original (PRs #16 e #17 mergeados), 6 de 8 pré-requisitos ficaram fechados e 2 parcialmente:

- Pré-req 1: bypass_actors removido do ruleset ✅; **required_status_checks + auto-reviewer** bloqueados por cota esgotada do GitHub Actions (incident `ci-blocked-by-actions-quota-2026-04-15.md`).
- Pré-req 7: ADR-0012 Accepted + R15/R16 codificadas na constitution ✅; **relock dos selos** pendente.

PM decide adiar a resolução da cota do Actions e retomar execução com os itens pendentes como dívida técnica conhecida.

## Análise técnica do pré-requisito 7 (relock)

Após re-exame do escopo das edições feitas nos PRs #16 e #17:

**Arquivos modificados que NÃO estão na lista selada (`SEALED_PATHS` em `scripts/hooks/sealed-files-bash-lock.sh`):**
- `CLAUDE.md`
- `docs/constitution.md`
- `.claude/agents/orchestrator.md`
- `docs/adr/0012-...`, `docs/adr/0014-...`
- `docs/retrospectives/...`
- `docs/audits/external/...`
- `project-state.json`
- `scripts/check-paused-state.sh` (novo, fora de `scripts/hooks/`)
- `scripts/pm/relock-apos-auditoria.bat` (novo)
- `.github/workflows/pause-enforcement.yml` (novo)

**Arquivos selados (não tocados):**
- `.claude/settings.json` e `.claude/settings.json.sha256` — inalterados
- `scripts/hooks/*` e `scripts/hooks/MANIFEST.sha256` — inalterados
- `.claude/allowed-git-identities.txt` e `.claude/git-identity-baseline` — inalterados
- `.claude/telemetry/` — inalterado

**Conclusão técnica:** nenhum hash selado mudou. O relock é **vacuosamente satisfeito** — não há diferença de hash para recalcular. O próximo `SessionStart` com `settings-lock --check` e `hooks-lock --check` deve passar sem drift.

Pré-requisito 7 fica reclassificado de `parcial — relock pendente` para **`completo — relock não foi necessário por escopo das edições não tocarem arquivos selados`**.

## Decisões desta emenda

### 1. Pré-requisito 7: reclassificado como ✅ completo

Justificativa: análise técnica acima mostra que as edições feitas nos PRs #16 e #17 não tocaram em nenhum arquivo selado. Logo, não existem novos hashes para selar. O `relock-apos-auditoria.bat` criado fica disponível para uso futuro caso edições em arquivos selados venham a ser necessárias (continua sendo a ferramenta oficial para essa operação).

### 2. Pré-requisito 1: reclassificado como ⏳ dívida técnica conhecida

- Parte 1 (remover bypass_actors): ✅ completa.
- Parte 2 (required_status_checks): ⏳ **adiada** indefinidamente — PM resolve quando quiser decidir sobre a cota do Actions.
- Parte 3 (auto-reviewer): ⏳ **adiada** junto.

**Risco aceito pelo PM:** sem CI obrigatório e sem auto-reviewer, um PR com código quebrado pode ser mergeado em `main` via botão verde (required_approving_review_count=0). Historicamente foi sempre assim neste projeto — a emenda não piora a situação, só não melhora o que poderia ter melhorado. Continua dependendo de disciplina operacional (agentes rodam testes localmente antes de abrir PR; pause-enforcement workflow bloqueia paths fora da whitelist quando projeto está pausado).

**Mitigação parcial ativa:** o workflow `pause-enforcement.yml` criado no PR #16 já roda em todo push/PR. Mesmo sem ser required status check, o status aparece na UI do PR — o PM pode escolher não mergear se ver vermelho.

### 3. Estado do projeto: de `CONDITIONAL_RESUME` para `ACTIVE` com dívida

- `project-state.json.paused`: `true` → `false`
- `project-state.json.paused_reason`: limpo
- Adicionada entrada `technical_debt` apontando para este arquivo e para `ci-blocked-by-actions-quota-2026-04-15.md`.

## O que o agente pode voltar a fazer

- ✅ `/retrospective 010` e `/slice-report 010` para fechar o slice-010 formalmente.
- ✅ `/next-slice` para recomendar próximo trabalho de produto.
- ✅ Iniciar E02-S08 ou próximo épico conforme R13/R14 de sequenciamento.
- ✅ Auto-aplicação do `harness-learner` quando rodar (limites de R16/E4 em vigor).
- ✅ Novas decisões de produto conforme PM solicite.

## O que continua bloqueado / pausado

- ❌ **Nada mais está bloqueado por esta decisão.** Os dois pontos pendentes (CI obrigatório e auto-reviewer) são dívida técnica conhecida que não bloqueia operação normal.
- ⚠️ PM pode reativar o bloqueio a qualquer momento setando `project-state.json.paused = true` e adicionando `paused_reason`. O workflow `pause-enforcement` passa a bloquear paths fora da whitelist automaticamente.

## Assinatura

```
PM (roldaobatista) — autorização verbal direta em 2026-04-15:
"vamos trabalhar sem resolver essa conta do github por enquanto"

Agente consolidador: Claude Opus 4.6 (orquestrador desta sessão)
Commit SHA desta emenda: (preenchido após commit)
```

## Cross-ref

- `docs/decisions/pm-decision-post-5-5-audit-2026-04-15.md` (decisão original)
- `docs/audits/external/post-5-5-cap-reached-2026-04-15.md` (dossiê e conclusões)
- `docs/incidents/ci-blocked-by-actions-quota-2026-04-15.md`
- `docs/adr/0014-technical-bypass-policy.md`
- `docs/retrospectives/post-5-bypasses-2026-04-15.md`
