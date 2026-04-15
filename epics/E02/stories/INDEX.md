# E02 — Stories Index

Epic: **Multi-tenancy, Auth e Planos**
Status: em progresso (6/8 stories mergeadas; 2 pendentes)
Atualizado: 2026-04-15

## Stories

| ID | Titulo | Slice | Status | Notas |
|---|---|---|---|---|
| E02-S01 | Scaffold stancl/tenancy + RLS PostgreSQL | 008 | merged | Coberto por TEN-001 |
| E02-S02 | Entidades Tenant, Empresa, Filial com migrations | 008 | merged | Coberto por TEN-001 |
| E02-S03 | Autenticacao: login, 2FA, recuperacao de senha | 007 | merged | Coberto por SEG-001 |
| E02-S04 | RBAC: papeis canonicos + spatie/laravel-permission | 009 | merged | Coberto por TEN-002 |
| E02-S05 | Ciclo de vida do tenant (estados e transicoes) | 008+009 | merged | Coberto parcialmente por TEN-001 e TEN-002 |
| E02-S06 | Motor de planos e feature gates (FR-PRI-01..03) | 009 | merged | Coberto por TEN-002 |
| **E02-S07** | **Base legal LGPD e consentimentos (REQ-CMP-004, FR-SEG-03)** | 010 | **pending** | SEG-002; obrigatorio antes de E03 |
| **E02-S08** | **Testes de isolamento entre tenants (seguranca estrutural)** | 011 | **pending** | SEG-003; obrigatorio antes de E03 |

## Observacao retroativa

As stories S01..S06 foram implementadas antes da adocao formal de Story Contracts para E02. Os slices 007, 008 e 009 cobriram o escopo dessas stories; os contratos formais em `epics/E02/stories/E02-S*.md` foram criados retroativamente em 2026-04-15 para fechar o gap de sequenciamento (R13/R14, ADR-0011). Os artefatos de entrega estao em `specs/007`, `specs/008` e `specs/009`.

## Proxima acao

- `/start-story E02-S07` — liberado por `sequencing-check.sh`, gera slice 010.
- Apos E02-S07 mergear: `/start-story E02-S08` — gera slice 011.
- Apos E02-S08 mergear: epico E02 fechado; libera inicio do E03 via R14.
