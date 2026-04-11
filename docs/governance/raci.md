# Matriz RACI de decisões — Kalibrium

> **Status:** ativo. Item 6.3 dos micro-ajustes da meta-auditoria #2. Define quem é **R**esponsável, **A**prova, é **C**onsultado e **I**nformado em cada tipo de decisão recorrente no projeto. Base para resolver ambiguidade quando alguém não sabe "quem decide isso".

## Legenda

- **R — Responsável pela execução.** Quem faz o trabalho.
- **A — Aprova.** Quem dá o veredito final e assume responsabilidade pelo resultado. **Sempre uma única pessoa ou papel por linha.**
- **C — Consultado.** Quem precisa ser ouvido antes da decisão. Opinião opcional.
- **I — Informado.** Quem é avisado depois da decisão, sem voto.

## Atores

- **PM** — Product Manager (humano único)
- **Architect** — sub-agent `architect` (gera plan.md)
- **Implementer** — sub-agent `implementer` (escreve código)
- **Verifier** — sub-agent `verifier` (gate mecânico em worktree)
- **Reviewer** — sub-agent `reviewer` (gate estrutural independente, R11)
- **DPO** — encarregado de proteção de dados (quando contratado)
- **Consultor metrologia** — consultor da RBC/GUM/ISO 17025 (quando contratado)
- **Consultor fiscal** — consultor de NFS-e multi-UF (quando contratado)
- **Advisor técnico** — advisor externo pontual (decisão #4 PM, Bloco 2)

## Matriz

| Tipo de decisão | PM | Architect | Implementer | Verifier | Reviewer | DPO | Consult. metrologia | Consult. fiscal | Advisor técnico |
|---|---|---|---|---|---|---|---|---|---|
| Custo mensal até R$ 500 (ferramenta operacional) | **A** | — | — | — | — | — | — | — | — |
| Custo mensal R$ 501 a R$ 2.000 | **A** | C | — | — | — | — | — | — | C |
| Custo mensal acima de R$ 2.000 | **A** | C | — | — | — | — | — | — | C |
| Adicionar novo MCP server | **A** | C | — | — | — | — | — | — | — |
| Remover MCP server | **A** | C | — | — | — | — | — | — | — |
| Abrir novo slice (feature) | **A** | R | — | — | — | — | — | — | — |
| Aprovar spec.md de um slice | **A** | C | — | — | — | — | C | C | — |
| Aprovar plan.md de um slice | **A** | R | — | — | — | — | — | — | — |
| Aprovar teste vermelho (AC → teste) | — | — | — | **A** | — | — | — | — | — |
| Aprovar implementação que fecha slice | — | — | — | **A** (verifier) | **A** (reviewer — R11) | — | — | — | — |
| Merge para main após dual-verifier ok | **A** | — | — | I | I | — | — | — | — |
| Abrir novo ADR | **A** | R | — | — | — | — | — | — | — |
| Aprovar ADR de stack (0001) | **A** | C | — | — | — | — | — | — | **A parcial** (veredito via A3) |
| Aprovar ADR de política de segurança | **A** | C | — | — | — | **C** | — | — | C |
| Aprovar ADR de política fiscal | **A** | C | — | — | — | — | — | **C** | — |
| Aprovar ADR de política metrológica | **A** | C | — | — | — | — | **C** | — | — |
| Alterar constituição (P1-P9 ou R1-R12) | **A** | C | — | — | C | C | — | — | C |
| Contratar consultor externo | **A** | — | — | — | — | — | — | — | — |
| Pausar projeto (teto de bypass 5/5) | **A** | — | — | — | — | — | — | — | — |
| Primeiro deploy a produção | **A** | C | — | I | I | **C** | C | C | C |
| Comunicação a titular após incidente P0 | **A** | — | — | — | — | **R** | — | — | — |
| Comunicação a ANPD após incidente P0 (Art. 48 LGPD) | **A** | — | — | — | — | **R** | — | — | — |
| Adicionar fornecedor novo ao vendor-matrix | **A** | — | — | — | — | C (se vendor toca dado pessoal) | — | — | — |
| Trocar fornecedor crítico (VPS, NFS-e, backup) | **A** | C | — | — | — | C | — | C | C |
| Encerrar contrato com consultor | **A** | — | — | — | — | — | — | — | — |
| Reclassificar item do escopo (out-of-scope) | **A** | C | — | — | — | C | C | C | — |
| Reavaliar a política de admin bypass | **A** | — | — | — | — | — | — | — | — |
| Definir cadência de retrospectiva | **A** | — | — | — | — | — | — | — | — |
| Aprovar gasto extraordinário fora do orçamento | **A** | C | — | — | — | — | — | — | — |
| Criar novo tipo de slice (experimental) | **A** | C | — | — | — | — | — | — | — |

## Regras de interpretação

1. **Cada linha tem exatamente um A.** Exceção explícita na linha "Aprovar implementação que fecha slice" onde verifier e reviewer são os dois A independentes por exigência da R11 (dual-verifier); e na linha do ADR-0001 onde o A do PM depende de um A parcial do advisor via o gate A3.
2. **Ausência de A do PM não significa que ele não pode vetar.** O PM sempre mantém veto formal, mas a linha indica onde a decisão **deveria** ser tomada em condições normais.
3. **Quando uma decisão não está na tabela:** o PM decide por padrão (fallback), e a decisão é adicionada à tabela na próxima retrospectiva.
4. **Mudança na tabela:** exige ADR + aprovação do PM conforme `docs/constitution.md §5`.

## Cross-ref

`docs/constitution.md §3.1` (modelo humano=PM), `docs/governance/harness-evolution.md` (quando revisar esta tabela), `docs/compliance/procurement-tracker.md` (quando contratar os consultores).
