# Decisões Técnicas

Índice vivo de todos os ADRs do projeto. Atualizar a cada novo ADR. Ordem = cronológica.

## Como usar
- Cada decisão arquitetural relevante vira ADR em `docs/adr/NNNN-<slug>.md`.
- Este arquivo é o índice — cada linha aponta para um ADR.
- **Não colocar conteúdo técnico aqui** — só links.
- `/adr NNNN "título"` cria um ADR novo a partir do template.

## ADRs

| # | Título | Status | Data | Escopo |
|---|---|---|---|---|
| [0001](adr/0001-stack-choice.md) | Escolha da stack (Laravel 13 + Livewire 4 + PostgreSQL 18) | accepted | 2026-04-11 | Stack principal |
| [0002](adr/0002-mcp-policy.md) | Política de MCP servers autorizados | accepted | 2026-04-10 | Tooling |

## Decisões Pendentes

ADRs que precisam ser criados antes do primeiro slice de código:

| # | Título Proposto | Bloqueador de | Responsável |
|---|---|---|---|
| 0003 | Estratégia de autenticação e multi-tenancy | E02 (Auth) | `/decide-stack` ou ADR manual |
| 0004 | Modelo de dados e estratégia de migrations | E01 (Setup) | architect |
| 0005 | Estratégia de deploy e CI/CD | Release | PM + orquestrador |
| 0006 | Política de observabilidade e logging | Operação | architect |
| 0007 | Estratégia de testes (unit/feature/E2E) | Todos os slices | architect |

> Estes números são reservas. O ADR real pode ter título diferente. Remover da tabela quando criado.

## Regras

- Toda mudança de stack, biblioteca crítica, estratégia de tenancy, esquema de autenticação, contrato de API público, ou política de teste **requer** ADR.
- ADR não pode ser deletado — apenas `superseded por NNNN`.
- ADR que altera P1-P9 ou R1-R10 segue `docs/constitution.md §5` (amendment process).
