# Guide Backlog

Backlog de melhorias ao próprio harness (constitution, hooks, sub-agents, skills). Cada item deve referenciar a evidência que motivou (slice, retrospectiva, incidente, audit).

Itens resolvidos movem para o histórico no final.

---

## Aberto

### [B-001] Operacionalizar post-edit-gate pós ADR-0001

- **Origem:** `post-edit-gate.sh` atual é stack-agnóstico e faz format/lint/testes apenas se as ferramentas existem.
- **Ação:** após ADR-0001 (stack escolhida), atualizar `post-edit-gate.sh` com comandos concretos e convenção de mapeamento arquivo→teste validada.
- **Status:** **bloqueado por ADR-0001** (stack ainda não decidida). Item ativo no Dia 1.
- **Bloqueia:** slice 1.

### [B-007] Integração com CI externo

- **Origem:** P8 (suite full em CI).
- **Ação:** quando stack estiver definida, configurar GitHub Actions (ou equivalente) rodando lint + types + suite full + security scan.
- **Status:** **bloqueado por ADR-0001**. Item ativo no Dia 1.

---

## Resolvido

### [B-003] Smoke-test dos hooks no Windows — RESOLVIDO 2026-04-10

- **Origem:** R5 do `GUIA-KALIBRIUM-V2-HARNESS-SETUP.md`.
- **Resolução:** `scripts/smoke-test-hooks.sh` criado com 29 testes cobrindo os 12 hooks. Rodado no Windows 11 + Git Bash → `29/29 OK`.
- **Bugs corrigidos no processo:**
  - `read-secrets-block.sh` — ordem de cases invertida bloqueava `.env.example`.
  - `collect-telemetry.sh` — `git log -1` saía com 128 em repo sem commits.
- **Evidência:** output `[smoke-test OK] todos os hooks funcionam neste ambiente`.

### [B-002] Scripts auxiliares dos skills — RESOLVIDO 2026-04-10

- **Origem:** skills referenciavam `scripts/new-slice.sh`, `scripts/verify-slice.sh`, `scripts/adr-new.sh`, `scripts/slice-report.sh`, `scripts/retrospective.sh`, `scripts/guide-check.sh`.
- **Resolução:**
  - `new-slice.sh`, `adr-new.sh`, `guide-check.sh` já estavam funcionais.
  - `verify-slice.sh` implementado: valida pré-condições, monta `verification-input/`, parseia ACs, modo `--validate` lê `verification.json`, aplica schema (B-005) e R6 (escalação após 2 rejeições consecutivas).
  - `slice-report.sh` implementado: agrega eventos do JSONL (commits, gates, rejeições, tokens), gera markdown com métricas.
  - `retrospective.sh` polido: carrega números do slice-report e gera template com seções fixas.
- **Evidência:** script `verify-slice.sh --validate` aplica schema e atualiza telemetria; smoke test estendido cobre `validate-verification.sh`.

### [B-004] Política de MCPs — RESOLVIDO 2026-04-10

- **Origem:** `/mcp-check` existe, `.claude/allowed-mcps.txt` tem lista inicial mas sem justificativa formal.
- **Resolução:** `docs/adr/0002-mcp-policy.md` criado explicando critérios de autorização, MCPs aprovados inicialmente e processo de adição.

### [B-005] Schema do verification.json + validador — RESOLVIDO 2026-04-10

- **Origem:** R4 + skill `/verify-slice`.
- **Resolução:**
  - `docs/schemas/verification.schema.json` escrito em JSON Schema draft-07 com enums para `verdict`, `rule`, `next_action`.
  - `scripts/validate-verification.sh` em bash puro (zero dependência externa) valida estrutura, enums e coerência entre `verdict` e `next_action`.
  - Integrado com `verify-slice.sh --validate`.

### [B-006] Telemetria de tokens por sub-agent — RESOLVIDO 2026-04-10 (estrutura)

- **Origem:** R8.
- **Resolução:**
  - `scripts/record-tokens.sh AGENT SLICE TOKENS` — API simples para gravar eventos de token em `.claude/telemetry/<slice>.jsonl`.
  - Invocável manualmente ao fim de uma invocação de sub-agent, ou via hook custom se o harness futuro expor tokens.
  - `slice-report.sh` agrega por sub-agent e compara com `max_tokens_per_invocation` do frontmatter dos agents.
- **Observação:** parsing automático dos tokens diretamente do Claude Code depende da API do harness, que pode evoluir. Reabrir quando houver fonte confiável.

### [B-008] Glossário de domínio — RESOLVIDO 2026-04-10

- **Origem:** agentes precisam entender OS, GUM, ICP-Brasil, REP-P antes de escrever código de domínio.
- **Resolução:** `docs/glossary-domain.md` destilado do `ideia.md` como referência **canônica** do V2 (não confundir com `docs/reference/` que é read-only histórico). Agentes DEVEM consultar ao tocar código de domínio com terminologia técnica.

---

## Histórico de versões deste backlog

- 2026-04-10 — inicial (B-001..B-008)
- 2026-04-10 — B-003 resolvido pós smoke-test
- 2026-04-10 — B-002, B-004, B-005, B-006, B-008 resolvidos; B-001 e B-007 marcados como bloqueados por ADR-0001
