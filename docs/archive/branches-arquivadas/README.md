# Branches arquivadas — fotografias antes de deletar

Em 2026-05-02, durante a limpeza pós-reset do harness, deletamos todas as branches abandonadas que não tinham PR identificado. Antes de deletar, salvamos uma "fotografia" (patch) de cada uma aqui, pra caso algum dia alguém queira resgatar conteúdo específico.

## Como ler uma fotografia (patch)

Cada arquivo `.patch` é a diferença entre o `main` daquele momento e a branch arquivada. Pra aplicar (resgatar conteúdo):

```bash
git apply docs/archive/branches-arquivadas/<arquivo>.patch
```

Para apenas inspecionar sem aplicar:

```bash
less docs/archive/branches-arquivadas/<arquivo>.patch
```

## Inventário

| Arquivo | Origem | Tamanho | Conteúdo provável |
|---------|--------|---------|-------------------|
| `archive_harness-v3-completo.patch` | branch `archive/harness-v3-completo` | 176 KB | Estado completo do harness v3 antes do reset |
| `chore_remediation-audits-2026-04-16.patch` | branch `chore/remediation-audits-2026-04-16` | 1.4 MB | Auditorias de remediação de abr/2026 |
| `feat_adr-0012-autonomia-dual-llm.patch` | branch `feat/adr-0012-autonomia-dual-llm` | 341 KB | Trabalho exploratório de ADR-0012 (autonomia dual-LLM) |
| `post-audit-amendment-unpause-2026-04-15.patch` | branch `post-audit-amendment-unpause-2026-04-15` | 8 KB | Amendment pós-auditoria de unpause |
| `post-audit-prereqs-2026-04-15.patch` | branch `post-audit-prereqs-2026-04-15` | 52 KB | Pré-requisitos pós-auditoria |
| `post-audit-relock-tool-2026-04-15.patch` | branch `post-audit-relock-tool-2026-04-15` | 4 KB | Tool de relock pós-auditoria |
| `retrospective-slice-010-2026-04-15.patch` | branch `retrospective-slice-010-2026-04-15` | 14 KB | Retrospectiva do slice 010 |

## Por que arquivamos em vez de só deletar

Essas branches **não tinham PR identificado** no GitHub — então não dava pra ter certeza se tinham conteúdo único valioso ou se eram só lixo. Salvar como patch custou alguns megabytes; perder trabalho real custaria muito mais. Em 6 meses, se ninguém precisou de nada aqui, esta pasta pode ser deletada.
