# E02 — Stories Index

Epic: **Multi-tenancy, Auth e Planos**
Status: em progresso (6/10 stories mergeadas; 2 pendentes + 2 gap-fill agendadas)
Atualizado: 2026-04-17

## Stories

| ID | Titulo | Slice | Status | ACs | Gaps | Notas |
|---|---|---|---|---|---|---|
| E02-S01 | Scaffold stancl/tenancy + RLS PostgreSQL | 008 | merged | 5 | 0 | Contrato formal retroativo completo |
| E02-S02 | Entidades Tenant, Empresa, Filial com migrations | 008 | merged | 6 | 0 | Contrato formal retroativo completo |
| E02-S03 | Autenticacao: login, 2FA, recuperacao de senha | 007 | merged | 14 | 0 | ER-005(a) confirmado como coberto |
| E02-S04 | RBAC: papeis canonicos + spatie/laravel-permission | 009 | merged | 7 | 0 | Contrato formal retroativo completo |
| E02-S05 | Ciclo de vida do tenant (estados e transicoes) | 008+009 | merged | 6 | — | Gap GAP-S05-001 **endereçado em E02-S09** |
| E02-S06 | Motor de planos e feature gates (FR-PRI-01..03) | 009 | merged | 8 | — | Gap GAP-S06-001 **endereçado em E02-S10** |
| **E02-S07** | **Base legal LGPD e consentimentos (REQ-CMP-004, FR-SEG-03)** | 010 | **pending** | - | - | SEG-002; obrigatorio antes de E03 |
| **E02-S08** | **Testes de isolamento entre tenants (seguranca estrutural)** | 011 | **pending** | - | - | SEG-003; obrigatorio antes de E03 |
| **E02-S09** | **Gap-fill: Automacao da transicao trial→expired/suspended** | TBD | **backlog** | 7 | - | Fecha GAP-S05-001; agendada pre-go-live; criada 2026-04-17 |
| **E02-S10** | **Gap-fill: Upgrade/downgrade de plano com pro-rata** | TBD | **backlog** | 8 | - | Fecha GAP-S06-001 (ER-005c); agendada pre-go-live; criada 2026-04-17 |

## Resumo de gaps (endereçados)

Todos os gaps identificados na retrospectiva E02 (2026-04-16) foram convertidos em stories formais com Story Contract. Nao ha gaps abertos sem plano.

| Gap ID | Story original | Story gap-fill | Descricao | Severidade | Status |
|---|---|---|---|---|---|
| GAP-S05-001 | E02-S05 | **E02-S09** | Automacao trial→expired via Artisan command + Scheduler | Media | backlog (pre-go-live) |
| GAP-S06-001 | E02-S06 | **E02-S10** | Upgrade/downgrade + calculo pro-rata + validacao de uso | Alta | backlog (pre-go-live) |

## Proxima acao

- `/start-story E02-S07` — liberado por `sequencing-check.sh`, gera slice 010.
- Apos E02-S07 mergear: `/start-story E02-S08` — gera slice 011.
- Apos E02-S08 mergear: epico E02 fecha modulo MVP; libera E03/E04+ via R14.
- **E02-S09 e E02-S10** sao gap-fills agendadas para janela pre-go-live (apos E20, antes do release MVP). Podem ser adiantadas se houver demanda do PM antes.
