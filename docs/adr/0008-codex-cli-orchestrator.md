# ADR-0008 — Codex CLI como orquestrador alternativo exclusivo

**Status:** accepted
**Data:** 2026-04-12
**Autor:** humano (PM) + Codex (recomendação técnica)

---

## Contexto

O PM quer poder usar o Codex CLI normal no terminal deste projeto e trabalhar com o mesmo nível de autonomia que o Claude Code vinha exercendo: ler o harness, conduzir tarefas, chamar papéis especializados quando possível, executar verificações e seguir o pipeline existente.

A regra R2 anterior dizia: "Só Claude Code toca o código na branch ativa." Essa redação bloqueava o Codex CLI mesmo quando o PM queria usar apenas um agente ativo por vez. O risco real que R2 pretende evitar não é o nome da ferramenta; é concorrência entre harnesses automáticos na mesma branch, com dois agentes alterando código sem um único dono operacional.

Também existe uma diferença técnica importante: hooks como `SessionStart`, `PreToolUse` e `PostToolUse` são eventos do Claude Code. Quando o Codex CLI é o orquestrador ativo, esses eventos não disparam automaticamente. Portanto, o Codex só pode operar no mesmo nível se assumir a obrigação de executar os checks equivalentes por comando antes de afirmar status ou avançar gates.

---

## Opções consideradas

### Opção A: Manter Claude Code como único orquestrador
- Descrição: R2 continua dizendo que só Claude Code toca a branch ativa.
- Prós:
  - Menor mudança no harness.
  - Mantém todos os hooks e sub-agents nativos no fluxo original.
- Contras:
  - Bloqueia o uso do Codex CLI mesmo quando ele seria o único agente ativo.
  - Mantém dependência operacional rígida de uma única ferramenta.
- Custo de reverter: baixo.

### Opção B: Autorizar Codex apenas como consultor MCP
- Descrição: Codex pode ser usado via MCP para análise e sugestão, mas não como orquestrador CLI normal.
- Prós:
  - Preserva o fluxo Claude Code sem mudanças profundas.
  - Reduz risco de hooks não disparados.
- Contras:
  - Não atende ao pedido do PM de usar Codex CLI normal no terminal.
  - Mantém Codex em papel auxiliar, sem autonomia equivalente.
- Custo de reverter: baixo.

### Opção C: Autorizar Codex CLI como orquestrador alternativo exclusivo
- Descrição: Claude Code ou Codex CLI podem ser o orquestrador ativo, mas apenas um por vez na branch ativa. O orquestrador ativo segue as mesmas fontes de verdade e gates. Quando não houver hook automático, executa o check equivalente manualmente.
- Prós:
  - Atende ao pedido do PM sem permitir concorrência de dois agentes na mesma branch.
  - Mantém R2 focada no risco correto: multi-harness simultâneo.
  - Preserva comandos, documentos e gates existentes como fonte operacional.
- Contras:
  - Codex CLI não aciona automaticamente todos os eventos de hook do Claude Code.
  - Sub-agents `.claude/agents` continuam sendo contratos operacionais; se a plataforma não tiver sub-agent nativo equivalente, o Codex precisa executar o papel de forma explícita ou usar subprocessos/sessões isoladas quando disponível.
- Custo de reverter: médio.

---

## Decisão

**Opção escolhida:** C — Autorizar Codex CLI como orquestrador alternativo exclusivo.

**Razão:** a regra deve impedir dois agentes automáticos concorrentes, não impedir que o PM escolha uma ferramenta diferente quando ela for o único orquestrador ativo. O projeto continua com fontes operacionais permitidas (`CLAUDE.md`, `docs/constitution.md`, `.claude/agents`, `.claude/skills`) e com um único dono operacional por branch. ADRs permanecem como registros de decisão consultivos, usados quando essas fontes operacionais os referenciam.

**Reversibilidade:** média. Para reverter, restaurar R2 para Claude Code exclusivo, remover esta ADR do índice e ajustar `CLAUDE.md`/`orchestrator.md`.

---

## Consequências

### Positivas
- O PM pode usar Codex CLI normal no terminal sem violar R2, desde que Claude Code não esteja editando a mesma branch em paralelo.
- A política fica explícita: um orquestrador ativo por branch.
- Codex MCP e Codex CLI ficam separados: MCP é consultoria; CLI pode ser orquestrador quando escolhido como ativo.
- Codex CLI carrega `CLAUDE.md` como instrução de projeto via `project_doc_fallback_filenames = ["CLAUDE.md"]` em `~/.codex/config.toml`, sem criar `AGENTS.md` no repositório.

### Negativas
- Os hooks específicos do Claude Code não disparam automaticamente no Codex CLI.
- A verificação de "orquestrador ativo" ainda depende de disciplina operacional e auditoria, não de trava mecânica completa.
- Alguns papéis de sub-agent podem precisar ser executados por equivalência quando o runtime não oferecer os agentes `.claude` nativamente.

### Riscos
- Codex e Claude abertos ao mesmo tempo editando a branch ativa recriam o problema que R2 tenta evitar.
- Codex pode declarar progresso sem rodar os checks equivalentes se o operador não seguir a constituição.
- Drift de telemetria: métricas de uso do Claude Code e Codex podem ter formatos diferentes.

### Impacto em outros artefatos
- Hooks afetados: nenhum hook alterado nesta ADR. Quando Codex CLI é o orquestrador ativo, checks equivalentes devem ser chamados explicitamente por comando.
- Sub-agents afetados: `.claude/agents/orchestrator.md` passa a declarar "orquestrador ativo" em vez de "Claude Code" como único papel principal.
- Configuração Codex afetada: `~/.codex/config.toml` deve incluir `project_doc_fallback_filenames = ["CLAUDE.md"]` para carregar o harness do projeto sem violar R1.
- ADRs relacionados: ADR-0002 separa Codex MCP de Codex CLI.
- Constitution: R2 alterada para "um orquestrador ativo por branch".

---

## Nova redação de R2

> Claude Code ou Codex CLI podem tocar o código na branch ativa, mas apenas um orquestrador por vez. Sessões concorrentes com outro LLM-tool editando código (Cursor, Copilot inline suggestions, Gemini CLI, Aider, Continue, Windsurf, ou o outro orquestrador não-ativo) continuam proibidas. O orquestrador ativo deve seguir `CLAUDE.md`, esta constituição, `.claude/agents/*.md`, `.claude/skills/*.md` e gates locais; ADRs são registros de decisão consultivos quando esses documentos os referenciam. Quando a plataforma não disparar hooks do Claude Code, deve executar manualmente os checks equivalentes antes de afirmar status.

---

## Plano de rollback

1. Restaurar `docs/constitution.md` R2 para "Só Claude Code toca o código na branch ativa".
2. Restaurar `CLAUDE.md` para declarar Claude Code como único orquestrador.
3. Restaurar `.claude/agents/orchestrator.md` para a redação anterior.
4. Remover a linha ADR-0008 de `docs/TECHNICAL-DECISIONS.md`.
5. Marcar esta ADR como `deprecated` ou como `superseded` apontando para o ADR de rollback real quando ele existir.

---

## Referências

- `docs/constitution.md` §4 R2
- `CLAUDE.md` §3 e §8
- `.claude/agents/orchestrator.md`
- `docs/adr/0002-mcp-policy.md`
- Decisão do PM em conversa de 2026-04-12: "pode fazer a recomendação"
