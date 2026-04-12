# Data Models

Diretório dos artefatos de modelagem de dados produzidos antes da implementação de cada épico.

Arquivos esperados:

- `master-erd.md` — ERD global do sistema, atualizado a cada épico que altera o modelo.
- `erd-eNN-*.md` — ERD específico do épico.
- `migrations-eNN-*.md` — especificação de migrations, seeds e rollback do épico.

Regras:

- Tabelas tenant-scoped devem declarar `tenant_id`.
- Exceções globais ou operacionais sem tenant devem documentar a justificativa.
- Diagramas usam Mermaid e tabelas Markdown.
