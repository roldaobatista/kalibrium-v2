# Retrospectiva — R2 e Codex CLI como orquestrador alternativo

**Data:** 2026-04-12
**ADR:** `docs/adr/0008-codex-cli-orchestrator.md`

## O que aprendemos

R2 estava correta ao bloquear multi-harness concorrente, mas sua redação antiga confundia o risco real com uma ferramenta específica. O problema a evitar é dois agentes automáticos editando a mesma branch ao mesmo tempo, não o uso exclusivo de uma ferramenta diferente pelo PM.

## Mudança aplicada

R2 passou a autorizar Claude Code ou Codex CLI como orquestrador ativo, desde que apenas um opere por vez na branch ativa. O Codex CLI precisa seguir as mesmas fontes de verdade e executar checks equivalentes quando hooks específicos do Claude Code não dispararem automaticamente.

## Risco residual

A exclusividade ainda depende de disciplina operacional e auditoria; não há trava mecânica completa impedindo que o PM abra Claude Code e Codex CLI ao mesmo tempo na mesma branch. Esse risco deve ser revisitado quando houver hook ou telemetria comum entre ferramentas.
