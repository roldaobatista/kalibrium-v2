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
| [0004](adr/0004-estrategia-de-identidade-e-autenticacao.md) | Estratégia de identidade e autenticação | accepted | 2026-04-13 | Identidade e autenticação |
| [0010](adr/0010-constitution-amendment-r6-gate-threshold.md) | Alterar R6 para 5 ciclos automáticos e escalação na 6ª rejeição | accepted | 2026-04-14 | Harness |
| [0017](adr/0017-auditoria-early-stage.md) | Auditoria early-stage (testes red, gate documental, integridade de estado) | accepted | 2026-04-16 | Harness / Pipeline |
| [0018](adr/0018-auditoria-fases-iniciais.md) | Auditoria independente nas fases iniciais (PRD, ADRs, UX, api-contracts) | accepted (prospectivo) | 2026-04-16 | Harness / Pipeline |
| [0019](adr/0019-robustez-loop-gates-harness-learner.md) | Robustez do loop de gates e do harness-learner (meta-audit, fixer scope, revisor cruzado) | accepted (Mudança 3 em duas camadas) | 2026-04-16 | Harness / Pipeline |

> Nota: ADRs 0011-0016 existem em `docs/adr/` mas ainda não estão indexados aqui (drift do índice — relacionado ao gap #9 da auditoria 2026-04-16, endereçado pelo ADR-0017 Mudança 3).

## Decisões Pendentes

ADRs que precisam ser criados antes do primeiro slice de código:

| # | Título Proposto | Bloqueador de | Responsável |
|---|---|---|---|
| 0003 | Mensageria e filas (Redis vs RabbitMQ vs SQS) | Fluxos assíncronos | architect |
| 0005 | Storage de documentos (MinIO vs S3 sa-east-1) | GED/certificados fiscais | architect |
| 0006 | Stack de observabilidade (Grafana Cloud vs Prometheus+Grafana self-hosted) | Operação | architect |
| 0007 | Pipeline CI/CD detalhado | Release | PM + orquestrador |
| 0009 | Provedor de emissão fiscal (SEFAZ/prefeitura direto vs broker terceiro) | Slices FIS | architect + consultor fiscal |

> Estes números são reservas. O ADR real pode ter título diferente. Remover da tabela quando criado.

## Regras

- Toda mudança de stack, biblioteca crítica, estratégia de tenancy, esquema de autenticação, contrato de API público, ou política de teste **requer** ADR.
- ADR não pode ser deletado — apenas `superseded por NNNN`.
- ADR que altera P1-P9 ou R1-R12 segue `docs/constitution.md §5` (amendment process).
