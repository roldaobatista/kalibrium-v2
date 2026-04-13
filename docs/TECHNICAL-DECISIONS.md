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
| [0001](adr/0001-stack-choice.md) | Sua decisão: qual tecnologia usar | accepted | 2026-04-11 | Stack principal |
| [0002](adr/0002-mcp-policy.md) | Política de MCP servers autorizados | accepted | 2026-04-10 | Tooling |
| [0008](adr/0008-codex-cli-orchestrator.md) | Codex CLI como orquestrador alternativo exclusivo | accepted | 2026-04-12 | Harness |

## Decisões Pendentes

ADRs que precisam ser criados antes do primeiro slice de código:

| # | Título Proposto | Bloqueador de | Responsável |
|---|---|---|---|
| 0003 | Mensageria e filas (Redis vs RabbitMQ vs SQS) | Fluxos assíncronos | architect |
| 0004 | IdP final (Fortify/Sanctum vs Keycloak vs WorkOS) | Identidade Enterprise | architect |
| 0005 | Storage de documentos (MinIO vs S3 sa-east-1) | GED/certificados fiscais | architect |
| 0006 | Stack de observabilidade (Grafana Cloud vs Prometheus+Grafana self-hosted) | Operação | architect |
| 0007 | Pipeline CI/CD detalhado | Release | PM + orquestrador |
| 0009 | Provedor de emissão fiscal (SEFAZ/prefeitura direto vs broker terceiro) | Slices FIS | architect + consultor fiscal |

> Estes números são reservas. O ADR real pode ter título diferente. Remover da tabela quando criado.

## Regras

- Toda mudança de stack, biblioteca crítica, estratégia de tenancy, esquema de autenticação, contrato de API público, ou política de teste **requer** ADR.
- ADR não pode ser deletado — apenas `superseded por NNNN`.
- ADR que altera P1-P9 ou R1-R12 segue `docs/constitution.md §5` (amendment process).
