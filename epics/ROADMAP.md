# Roadmap de Épicos — Kalibrium V2

> **Gerado por:** epic-decomposer  
> **Data:** 2026-04-12  
> **Base:** PRD congelado + mvp-scope.md (29 REQs) + prd-gaps-resolution.md (12 FRs P0 adicionais) + domain-model.md (34 entidades, 6 bounded contexts) + ADR-0001 (Laravel 13 + Livewire 4 + PostgreSQL 18)  
> **Primeiro cliente-âncora:** laboratório não-acreditado em Rondonópolis/MT  
> **Status:** aguardando aprovação PM

---

## Visão Geral

| Indicador | Valor |
|---|---|
| Total de épicos | 14 |
| Épicos MVP (P0) | 8 |
| Épicos pós-MVP (P1) | 4 |
| Épicos visão futura (P2/P3) | 2 |
| Stories estimadas (MVP) | ~63 |
| Stories estimadas (total) | ~104 |

---

## Sequência de Execução

```
E01 — Setup e Infraestrutura
 └─► E02 — Multi-tenancy, Auth e Planos
      └─► E03 — Cadastro Core (Clientes, Instrumentos, Padrões)
           └─► E04 — Ordens de Serviço e Fluxo Operacional
                └─► E05 — Laboratório e Calibração (4 domínios metrológicos)
                     └─► E06 — Certificado de Calibração (PDF/A + RBC)
                          ├─► E07 — Fiscal: NFS-e Rondonópolis/MT
                          │    └─► E08 — Financeiro e Contas a Receber
                          └─► E09 — Portal do Cliente Final
 E03 ─► E10 — GED: Gestão Documental (transversal)
 E04 ─► E11 — Dashboard Operacional e Relatórios
 E02 ─► E12 — Comunicação: E-mail e Notificações (transversal)
 E08 ─► E13 — Procurement e Fornecedores        [P1]
 E05 ─► E14 — LMS e Habilitações Técnicas       [P1]
```

---

## Épicos

| ID | Título | Prioridade | Dependências | Stories est. | Complexidade | Status |
|---|---|---|---|---|---|---|
| E01 | Setup e Infraestrutura | P0 | — | 6 | média | backlog |
| E02 | Multi-tenancy, Auth e Planos | P0 | E01 | 8 | alta | backlog |
| E03 | Cadastro Core | P0 | E02 | 7 | média | backlog |
| E04 | Ordens de Serviço e Fluxo Operacional | P0 | E03 | 9 | alta | backlog |
| E05 | Laboratório e Calibração | P0 | E04 | 9 | muito alta | backlog |
| E06 | Certificado de Calibração | P0 | E05 | 7 | alta | backlog |
| E07 | Fiscal: NFS-e | P0 | E06 | 6 | alta | backlog |
| E08 | Financeiro e Contas a Receber | P0 | E07 | 5 | média | backlog |
| E09 | Portal do Cliente Final | P0 | E06 | 6 | média | backlog |
| E10 | GED: Gestão Documental | P0 | E03 | 5 | média | backlog |
| E11 | Dashboard Operacional e Relatórios | P0 | E04 | 5 | média | backlog |
| E12 | Comunicação: E-mail e Notificações | P0 | E02 | 4 | baixa | backlog |
| E13 | Procurement e Fornecedores | P1 | E08 | 6 | média | backlog |
| E14 | LMS e Habilitações Técnicas (extensões) | P1 | E05 | 5 | média | backlog |

---

## Cobertura de Requisitos MVP

| Domínio | REQs | Coberto por |
|---|---|---|
| TEN (Cadastro e tenant) | REQ-TEN-001..005 | E02, E03 |
| MET (Metrologia e calibração) | REQ-MET-001..007 | E03, E05, E06 |
| FLX (Fluxo fim a fim) | REQ-FLX-001..005 | E04, E09, E12 |
| FIS (Fiscal) | REQ-FIS-001..005 | E07, E08 |
| OPL (Operação) | REQ-OPL-001..004 | E04, E11 |
| CMP (Compliance) | REQ-CMP-001..004 | E02, E05, E10 |
| GED (P0 adicionais) | FR-GED-01,03,06,07 | E10 |
| LMS (P0 adicionais) | FR-LMS-03,05 | E04 (bloqueio básico), E14 (extensões) |
| Pricing (P0 adicionais) | FR-PRI-01,02,03 | E02 |
| Email (P0 adicionais) | FR-EML-01,03,04 | E12 |

**Cobertura MVP: 100% dos 29 REQs + 12 FRs P0 adicionais.**
