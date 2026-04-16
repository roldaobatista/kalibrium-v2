# Changelog de Agentes

Histórico de mudanças nos sub-agents da fábrica. Atualizar a cada modificação em `.claude/agents/*.md`.

## Formato

```
### YYYY-MM-DD — agente(s) afetado(s)
- Mudança descrita
- Motivação (gap/audit/incident que originou)
```

---

## 2026-04-16 — REDESIGN v3.0: reorganização por domínio (25 → 12 agentes)

- **Removidos 23 agentes** task-based: domain-analyst, nfr-analyst, architect, api-designer, data-modeler, plan-reviewer, spec-auditor, story-auditor, planning-auditor, epic-decomposer, story-decomposer, ac-to-test, implementer, fixer, verifier, reviewer, security-reviewer, test-auditor, functional-reviewer, master-auditor, guide-auditor, harness-learner, epic-retrospective
- **Criados 10 novos agentes** domain-based: product-expert, architecture-expert, data-expert, security-expert, qa-expert, devops-expert (novo), observability-expert (novo), integration-expert (novo), builder, governance
- **Reescritos 2 agentes** existentes: ux-designer (escopo expandido), orchestrator (novo fluxo com 12 agentes)
- Cada agente agora opera em **múltiplos modos** conforme a fase do projeto (ex: qa-expert tem 6 modos: verify, review, audit-spec, audit-story, audit-planning, audit-tests)
- Isolamento R3/R11 preservado: mesmo agente, contextos isolados separados por modo
- **Perfis de elite** definidos para cada agente: persona senior, mentalidade, especialidades profundas, padrões de qualidade, anti-padrões, referências de mercado
- Pipeline de gates expandido com gates condicionais: data-gate, observability-gate, integration-gate (ativados quando slice envolve esses domínios)
- 18 skills atualizadas para referenciar novos nomes de agentes
- CLAUDE.md seções 6 e 8 atualizadas
- **Motivação:** PM pediu reorganização por domínio de conhecimento (~5 áreas) para centralizar expertise e reduzir fragmentação. Proposta em docs/proposals/agent-redesign-v3.md

## 2026-04-11 — orchestrator (NOVO)

- Criado `orchestrator.md` como agente formal do orquestrador mestre
- Máquina de estados S0-S13 cobrindo fases A-F do projeto
- Regras de paralelismo: domain-analyst → nfr-analyst (serializado), gates 3-5 (paralelo)
- Cadeia fixer → re-gate com contadores de rejeição em telemetria
- Protocolo de checkpoint automático em transições de estado
- Protocolo de comunicação R12 com templates de mensagem para PM
- **Motivação:** G-01 + G-05 + G-08 da auditoria factory-audit-2026-04-11

## 2026-04-11 — todos (14 sub-agents)

- Adicionada seção `## Erros e Recuperação` em cada sub-agent file (via skills)
- Adicionada seção `## Agentes` declarando spawning explícito em cada skill
- Adicionada seção `## Pré-condições` onde faltava
- **Motivação:** G-02, G-03, G-10 da auditoria factory-audit-2026-04-11

## 2026-04-11 — guide-auditor

- Criado `docs/schemas/guide-audit.schema.json` com 10 categorias de auditoria
- Schema define output estruturado: sealed_files, hooks_integrity, forbidden_files, git_identity, telemetry, orphan_instructions, settings_drift, token_usage, r7_headers, constitution_version
- **Motivação:** G-16 da auditoria factory-audit-2026-04-11

## 2026-04-11 — versão inicial (v2.0.0)

- 14 sub-agents criados: domain-analyst, nfr-analyst, epic-decomposer, story-decomposer, architect, ac-to-test, implementer, fixer, verifier, reviewer, security-reviewer, test-auditor, functional-reviewer, guide-auditor
- Organizados em 5 núcleos: Descoberta, Planejamento, Execução, Qualidade, Governança
- Todos com contratos de I/O, budgets declarados e isolamento por worktree (gates)
- **Motivação:** commit eb2292a (fábrica agentic v2.0)
