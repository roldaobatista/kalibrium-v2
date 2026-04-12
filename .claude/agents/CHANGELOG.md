# Changelog de Agentes

Histórico de mudanças nos sub-agents da fábrica. Atualizar a cada modificação em `.claude/agents/*.md`.

## Formato

```
### YYYY-MM-DD — agente(s) afetado(s)
- Mudança descrita
- Motivação (gap/audit/incident que originou)
```

---

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
