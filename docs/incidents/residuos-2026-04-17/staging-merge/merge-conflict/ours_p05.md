# 05 — Matriz RACI

Versao: 1.2.0 — 2026-04-16

Changelog 1.2.0: auditorias de planejamento/stories/spec transferidas de governance (R) para qa-expert (R) alinhando com mapa canonico 00 §3.1; governance agora C nessas atividades (revisao pos-fato, nao execucao). Review de plan agora architecture-expert (plan-review) R, governance C. Secao de cross-review expandida com principio de isolamento por instancia R3 (dois modos do mesmo agente em contextos isolados satisfazem cross-review).

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
| Auditoria de planejamento | I | C | — | C | — | — | R | — | — | — | — | C | A |
| Auditoria de stories | I | C | C | — | — | — | R | — | — | — | — | C | A |
| Criacao de spec.md | I | R | C | C | C | C | — | — | — | C | — | — | A |
| Auditoria de spec | I | C | — | — | — | — | R | — | — | — | — | C | A |
| Criacao de plan.md | I | — | — | R | C | C | C | C | C | C | — | — | A |
| Review de plan | I | — | — | R | — | — | — | — | — | — | — | C | A |
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

### Principio de isolamento por instancia (R3)

Dois modos diferentes do mesmo agente (ex: architecture-expert `plan` e architecture-expert `plan-review`) sao considerados instancias independentes quando invocados em contextos isolados separados. Cada invocacao recebe pacote de input proprio, sem acesso ao output da outra. Isso satisfaz o principio de cross-review P1/R3.

**Regra normativa:** o orchestrator deve garantir que invocacoes de modos distintos do mesmo agente sobre o mesmo slice ocorram em sessoes/processos separados, sem compartilhamento de contexto. A evidencia dessa separacao deve constar no log de telemetria (campo `isolation_context` no evento `gate_submitted`).

### Combinacoes proibidas (quem produz nao revisa)

Mesmo com isolamento por instancia, as seguintes combinacoes sao proibidas em qualquer circunstancia:

| Produtor (modo) | Nao pode revisar (modo) |
|----------|-----------------|
| product-expert (decompose) — spec.md | qa-expert (audit-spec) — somente se o mesmo agente fisico/modelo tiver produzido e auditado; solucao: invocacoes sempre em instancias isoladas |
| architecture-expert (plan) — plan.md | architecture-expert (plan-review) — exige instancia isolada R3 |
| builder (implementer) — codigo/testes | qa-expert (verify), architecture-expert (code-review), qa-expert (audit-tests) |
| qa-expert (verify) | qa-expert (audit-tests) do mesmo slice — exige instancia isolada R3 |
| architecture-expert (plan) — plan.md | architecture-expert (code-review) do mesmo slice se plan.md for input direto — exige instancia isolada R3 |
| security-expert (threat-model) | security-expert (security-gate) do mesmo slice se threat-model for input direto — exige instancia isolada R3 |

O orchestrator deve garantir que estas restricoes sejam respeitadas na atribuicao de gates e registrar o `isolation_context` no JSON de gate.
