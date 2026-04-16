# 05 — Matriz RACI

Versao: 1.0.0 — 2026-04-16

Legenda: **R** = Responsavel (executa), **A** = Accountable (responde pelo resultado), **C** = Consultado, **I** = Informado.

Regras estruturais:
1. Somente 1 agente pode ser **A** por atividade.
2. O agente que produz nao pode ser o que revisa (principio de cross-review).
3. PM nunca e **R** em atividades tecnicas.
4. PM e **A** somente em decisoes de produto.
5. Orchestrator e **A** para sequenciamento/coordenacao, nunca para conteudo.
6. Builder e o unico **R** para escrita de codigo/testes.

---

## Fase A — Descoberta

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Entrevista de intake | A | R | C | C | — | — | — | — | — | — | — | — | I |
| Analise de dominio | I | R | C | C | C | — | — | — | — | — | — | — | A |
| Extracao de NFRs | I | C | — | C | — | C | C | C | C | — | — | — | A |
| Pesquisa de personas | A | C | R | — | — | — | — | — | — | — | — | — | I |
| Mapeamento de jornadas | A | C | R | — | — | — | — | — | — | — | — | — | I |
| Cross-review Fase A | I | C | C | C | — | — | — | — | — | — | — | R | A |
| Aprovacao freeze PRD | A | C | C | — | — | — | — | — | — | — | — | I | R |

---

## Fase B — Estrategia Tecnica

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Criacao de ADRs | I | — | — | R | C | C | C | C | C | C | — | — | A |
| Design de contratos API | I | C | — | C | — | C | — | — | — | R | — | — | A |
| Design de ERD | I | — | — | C | R | — | — | — | — | — | — | — | A |
| Modelagem de ameacas | I | — | — | C | — | R | — | C | — | C | — | — | A |
| Criacao do design system | I | C | R | — | — | — | — | — | — | — | — | — | A |
| Planejamento de observabilidade | I | — | — | C | — | — | — | C | R | — | — | — | A |
| Mapeamento de integracoes | I | C | — | C | — | C | — | — | — | R | — | — | A |
| Design de CI/CD | I | — | — | C | — | — | C | R | — | — | — | — | A |
| Cross-review Fase B | I | C | C | C | C | C | C | C | C | C | — | R | A |
| Aprovacao freeze arquitetura | A | C | — | C | — | — | — | — | — | — | — | I | R |

---

## Fase C — Planejamento

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Decomposicao em epicos | I | R | C | C | — | — | — | — | — | — | — | — | A |
| Decomposicao em stories | I | R | C | C | C | — | — | — | — | — | — | — | A |
| Auditoria de planejamento | I | C | — | C | — | — | C | — | — | — | — | R | A |
| Auditoria de stories | I | C | C | — | — | — | — | — | — | — | — | R | A |
| Criacao de spec.md | I | R | C | C | C | C | — | — | — | C | — | — | A |
| Auditoria de spec | I | C | — | — | — | — | — | — | — | — | — | R | A |
| Criacao de plan.md | I | — | — | R | C | C | C | C | C | C | — | — | A |
| Review de plan | I | — | — | C | — | — | — | — | — | — | — | R | A |
| Aprovacao de plan | I | — | — | — | — | — | — | — | — | — | — | — | A |

Nota: a aprovacao de plan e automatica (auto-approval) quando qa-expert (audit-spec) E architecture-expert (plan-review) aprovam com zero findings. PM somente e envolvido em escalacao R6 ou decisao de produto explicita.

---

## Fase D — Execucao

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Escrita de testes (red) | I | — | — | — | — | — | C | — | — | — | R | — | A |
| Implementacao (green) | I | — | — | C | C | — | — | — | — | — | R | — | A |
| Commit | — | — | — | — | — | — | — | — | — | — | R | — | A |

---

## Fase E — Pipeline de Gates

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Gate verify | — | — | — | — | — | — | R | — | — | — | — | — | A |
| Gate review (code-review) | — | — | — | R | — | — | — | — | — | — | — | — | A |
| Gate security | — | — | — | — | — | R | — | — | — | — | — | — | A |
| Gate audit-tests | — | — | — | — | — | — | R | — | — | — | — | — | A |
| Gate functional | — | R | C | — | — | — | — | — | — | — | — | — | A |
| Gate data (cond) | — | — | — | — | R | — | — | — | — | — | — | — | A |
| Gate observability (cond) | — | — | — | — | — | — | — | — | R | — | — | — | A |
| Gate integration (cond) | — | — | — | — | — | — | — | — | — | R | — | — | A |
| Gate master-audit | — | — | — | — | — | — | — | — | — | — | — | R | A |
| Correcao de findings | — | — | — | C | C | C | C | — | — | — | R | — | A |
| Merge | — | — | — | — | — | — | — | — | — | — | — | I | A |

Nota sobre correcao de findings: builder e o unico **R** para correcoes de codigo. O agente que identificou o finding pode ser **C** (consultado) para esclarecer o problema, mas nao pode corrigir diretamente.

---

## Fase F — Encerramento

| Atividade | PM | product-expert | ux-designer | architecture-expert | data-expert | security-expert | qa-expert | devops-expert | observability-expert | integration-expert | builder | governance | orchestrator |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Retrospectiva de epico | I | C | C | C | C | C | C | C | C | C | — | R | A |
| Harness learning | I | — | — | — | — | — | — | — | — | — | — | R | A |
| Guide audit | I | — | — | — | — | — | — | — | — | — | — | R | A |
| Cross-review retrospectiva | I | C | — | C | — | C | C | — | — | — | — | R | A |

---

## Restricoes de cross-review

As seguintes combinacoes sao proibidas (quem produz nao revisa):

| Produtor | Nao pode revisar |
|----------|-----------------|
| product-expert (spec.md) | spec audit |
| architecture-expert (plan.md) | plan review |
| builder (codigo/testes) | verify, code-review, audit-tests |
| qa-expert (verify) | audit-tests do mesmo slice (se inputs se sobrepuserem) |
| architecture-expert (plan.md) | code-review do mesmo slice se plan.md for input direto |
| security-expert (threat model) | security-gate do mesmo slice se threat model for input direto |

O orchestrator deve garantir que estas restricoes sejam respeitadas na atribuicao de gates.
