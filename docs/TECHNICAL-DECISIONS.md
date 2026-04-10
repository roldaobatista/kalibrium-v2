# Decisões Técnicas

Índice vivo de todos os ADRs do projeto. Atualizar a cada novo ADR. Ordem = cronológica.

## Como usar
- Cada decisão arquitetural relevante vira ADR em `docs/adr/NNNN-<slug>.md`.
- Este arquivo é o índice — cada linha aponta para um ADR.
- **Não colocar conteúdo técnico aqui** — só links.
- `/adr NNNN "título"` cria um ADR novo a partir do template.

## ADRs

| # | Título | Status | Data |
|---|---|---|---|
| (0001) | Escolha da stack | _pendente (Dia 1)_ | — |
| [0002](adr/0002-mcp-policy.md) | Política de MCP servers autorizados | accepted | 2026-04-10 |

## Regras

- Toda mudança de stack, biblioteca crítica, estratégia de tenancy, esquema de autenticação, contrato de API público, ou política de teste **requer** ADR.
- ADR não pode ser deletado — apenas `superseded por NNNN`.
- ADR que altera P1-P9 ou R1-R10 segue `docs/constitution.md §5` (amendment process).
