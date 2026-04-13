[![CI](https://github.com/roldaobatista/kalibrium-v2/actions/workflows/ci.yml/badge.svg)](https://github.com/roldaobatista/kalibrium-v2/actions/workflows/ci.yml)

# Kalibrium

SaaS B2B multi-tenant para laboratórios brasileiros de calibração e metrologia. Atende o fluxo completo do laboratório — do pedido do cliente até a emissão do certificado e a cobrança — com rastreabilidade suficiente para auditoria da Rede Brasileira de Calibração (RBC) e conformidade fiscal multi-municipal.

## O que é

O Kalibrium substitui a mistura atual de planilha + software legado + portal fiscal externo por um único produto que cobre cadastro de cliente, cadastro de padrão e instrumento, agendamento, execução técnica da calibração com cálculo de incerteza conforme GUM/JCGM 100:2008, aprovação, emissão do certificado em PDF compatível com a RBC, emissão da NFS-e municipal e portal do cliente final para consulta de histórico. O escopo completo do primeiro produto está em [`docs/product/mvp-scope.md`](docs/product/mvp-scope.md). A visão original e as hipóteses de valor estão em [`docs/product/ideia-v1.md`](docs/product/ideia-v1.md).

## Status do projeto

Pré-implementação. O harness (robô que constrói o produto) está estável a partir do Bloco 1 da meta-auditoria #1 (commit `c061e3c`) e a fundação de produto foi completada em 2026-04-10 como parte do Bloco 1.5 da meta-auditoria #2. A decisão da stack (ADR-0001) acontece no Bloco 2 e ainda não ocorreu. Nenhum código de produto foi escrito. Nenhum tenant real foi atendido. O modelo operacional é "humano = Product Manager, agentes de IA = equipe técnica completa" — detalhado em [`docs/constitution.md §3.1`](docs/constitution.md) e [`CLAUDE.md §3.1`](CLAUDE.md).

## Links principais

- **Instruções operacionais para o agente:** [`CLAUDE.md`](CLAUDE.md) — fonte única permitida de instrução.
- **Constituição do projeto:** [`docs/constitution.md`](docs/constitution.md) — princípios P1-P9, regras R1-R12, DoD mecânica, processo de alteração.
- **Visão de produto e escopo:** [`docs/product/`](docs/product/) — `ideia-v1.md`, `mvp-scope.md`, `personas.md`, `journeys.md`, `laboratorio-tipo.md`, `glossary-pm.md`, `nfr.md`, `pricing-assumptions.md`.
- **Arquitetura pré-stack:** [`docs/architecture/foundation-constraints.md`](docs/architecture/foundation-constraints.md) — consumido pelo ADR-0001 quando ele for escrito no Bloco 2.
- **Orçamento operacional:** [`docs/finance/operating-budget.md`](docs/finance/operating-budget.md).
- **Compliance:** [`docs/compliance/`](docs/compliance/) — `out-of-scope.md`, `rfp-consultor-metrologia.md`, `rfp-consultor-fiscal.md`, mais itens da Trilha #2 em produção.
- **Auditorias externas e plano de ação:** [`docs/audits/`](docs/audits/) — auditorias externas publicadas, plano de ação da meta-auditoria #2, tracker de progresso em `docs/audits/progress/meta-audit-tracker.md`.
- **Decisões do PM:** [`docs/decisions/`](docs/decisions/).
- **Incidentes do harness:** [`docs/incidents/`](docs/incidents/).
- **Registros de decisão técnica:** [`docs/TECHNICAL-DECISIONS.md`](docs/TECHNICAL-DECISIONS.md) — índice vivo de ADRs.

## Como contribuir

Este projeto é operado por um único humano (Product Manager, sócio) auxiliado por agentes de IA (Claude Code). Não há equipe de desenvolvimento humana. Qualquer intervenção técnica passa pelo harness e pelos gates automatizados definidos na constituição — hooks de pre-edit, post-edit, pre-commit, pre-push, verifier em worktree descartável, reviewer independente (R11) e tradução para linguagem de produto (R12). A mecânica de alteração do harness (arquivos selados) está em [`CLAUDE.md §9`](CLAUDE.md). A mecânica de alteração da constituição está em [`docs/constitution.md §5`](docs/constitution.md).

Para entender como o projeto é construído antes de propor qualquer coisa, comece lendo `CLAUDE.md` e `docs/constitution.md` nessa ordem. Depois, revise o plano da meta-auditoria #2 em `docs/audits/meta-audit-completeness-2026-04-10-action-plan.md` e o tracker de progresso para ver o que já foi feito e o que está em aberto.
