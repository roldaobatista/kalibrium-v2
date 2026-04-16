# Fluxo Completo dos Agentes v3 — Com Cross-Review

**Data:** 2026-04-16
**Principio:** todo artefato importante e conferido por pelo menos 1 especialista de outro dominio.

---

## Fase A — Descoberta

### A.1 — Intake do produto
```
PM descreve o que quer (/intake)
        ↓
product-expert (discovery)
  Produz: glossario, modelo de dominio, NFRs, riscos, suposicoes
        ↓
Cross-review:
  ├─ qa-expert (audit-planning): NFRs tem metricas mensuraveis? ACs testaveis?
  └─ security-expert (spec-security): dados sensiveis mapeados? LGPD prevista no dominio?
        ↓
Se findings → product-expert corrige → re-review
```

### A.2 — Pesquisa de UX
```
ux-designer (research)
  Produz: personas, jornadas de usuario, benchmarks visuais
        ↓
Cross-review:
  └─ product-expert (discovery): jornadas batem com dominio real? Personas refletem usuarios reais?
        ↓
Se findings → ux-designer corrige → re-review
```

### A.3 — Gate de fase
```
PM revisa artefatos (traduzidos em linguagem de produto via R12)
        ↓
/freeze-prd
```

---

## Fase B — Estrategia Tecnica

### B.1 — Arquitetura do sistema
```
architecture-expert (design)
  Produz: ADRs, API contracts, design de componentes
        ↓
Cross-review:
  ├─ data-expert (review): modelo de dados consistente com APIs?
  ├─ security-expert (spec-security): vulnerabilidades na arquitetura? Autenticacao/autorizacao OK?
  └─ qa-expert (audit-planning): decisoes testaveis? Cobertura de NFRs?
        ↓
Se findings → architecture-expert corrige → re-review
```

### B.2 — Modelagem de dados
```
data-expert (modeling)
  Produz: ERDs, migrations, estrategia de tenant isolation, indices
        ↓
Cross-review:
  ├─ architecture-expert (design): alinhado com design de sistema? Nao contradiz ADRs?
  └─ security-expert (spec-security): tenant isolation seguro? Dados sensiveis identificados?
        ↓
Se findings → data-expert corrige → re-review
```

### B.3 — Threat model e LGPD
```
security-expert (threat-model)
  Produz: threat model STRIDE, requisitos LGPD, base legal
        ↓
Cross-review:
  ├─ architecture-expert (design): mitigacoes sao viaveis tecnicamente?
  └─ product-expert (discovery): requisitos LGPD batem com fluxos de negocio?
        ↓
Se findings → security-expert corrige → re-review
```

### B.4 — Design system e UX
```
ux-designer (design)
  Produz: design system, style guide, wireframes, screen inventory, component patterns
        ↓
Cross-review:
  ├─ product-expert (discovery): atende jornadas do usuario? Fluxos fazem sentido?
  └─ security-expert (spec-security): campos sensiveis protegidos na UI? Mascaramento de PII?
        ↓
Se findings → ux-designer corrige → re-review
```

### B.5 — Observabilidade
```
observability-expert (strategy)
  Produz: plano de logging estruturado, metricas, health checks, alertas
        ↓
Cross-review:
  ├─ security-expert (spec-security): nao loga dados sensiveis (LGPD)? Audit trail protegido?
  └─ devops-expert (ci-design): viavel na infra? Performance de logging aceitavel?
        ↓
Se findings → observability-expert corrige → re-review
```

### B.6 — Integracoes externas
```
integration-expert (strategy)
  Produz: mapa de integracoes, contratos de webhook, estrategia de filas, resilience patterns
        ↓
Cross-review:
  ├─ security-expert (spec-security): autenticacao das APIs externas? Secrets protegidos?
  └─ architecture-expert (design): alinhado com design de sistema? Desacoplamento OK?
        ↓
Se findings → integration-expert corrige → re-review
```

### B.7 — DevOps e CI/CD
```
devops-expert (ci-design)
  Produz: pipeline CI/CD, Dockerfile, estrategia de deploy
        ↓
Cross-review:
  ├─ security-expert (spec-security): secrets protegidos? Imagens seguras? Principio do menor privilegio?
  └─ architecture-expert (design): pipeline cobre necessidades do sistema? Ambientes OK?
        ↓
Se findings → devops-expert corrige → re-review
```

### B.8 — Gate de fase
```
PM aprova decisoes tecnicas (traduzidas em linguagem de produto via R12)
        ↓
/freeze-architecture
```

---

## Fase C — Planejamento

### C.1 — Decomposicao em epicos e stories
```
product-expert (decompose)
  Produz: epicos com dependencias, stories com Story Contract
        ↓
Cross-review:
  ├─ qa-expert (audit-planning): cobertura do PRD? Dependencias consistentes?
  └─ qa-expert (audit-story): cada story tem ACs testaveis? Escopo fechado?
        ↓
Se findings → product-expert corrige → re-review
        ↓
PM aprova stories
```

### C.2 — Spec do slice
```
qa-expert (audit-spec)  [contexto isolado]
  Valida: spec.md contra roadmap, epico, ADRs, constitution
        ↓
Se findings → builder (fixer) corrige → re-audit
```

### C.3 — Plano tecnico
```
architecture-expert (plan)
  Produz: plan.md com decisoes, mapeamento AC→arquivo, riscos
     ↓ (consulta quando necessario)
     data-expert (review): valida modelo de dados no plan
        ↓
Cross-review:
  ├─ qa-expert (novo modo: review-plan) [contexto isolado]: plano viavel? Coerente com spec?
  └─ security-expert (spec-security): aspectos de seguranca do plano OK?
        ↓
Se findings → architecture-expert corrige → re-review
        ↓
Auto-approval se qa-expert + security-expert aprovaram com zero findings
```

---

## Fase D — Execucao

```
builder (test-writer)
  Produz: testes Pest red a partir dos ACs
        ↓
Commit: test(slice-NNN): AC tests red
        ↓
builder (implementer)
  Produz: codigo que faz testes ficarem verdes
        ↓
Commit: feat(slice-NNN): implementacao
```

**So o builder trabalha aqui.** Validacao vem na Fase E.

---

## Fase E — Pipeline de Gates

### E.1 — Gate mecanico
```
qa-expert (verify) [contexto isolado]
  Valida: testes passam, lint OK, types OK, sem arquivos proibidos
  Emite: verification.json
        ↓ (so se approved)
```

### E.2 — Review estrutural
```
architecture-expert (code-review) [contexto isolado]
  Valida: duplicacao, nomes, aderencia a ADRs, simplicidade, god classes, fat controllers
  Emite: review.json
        ↓ (so se approved)
```

### E.3 — Gates paralelos (sempre)
```
Em paralelo:
  ├─ security-expert (security-gate) [isolado]
  │    Valida: OWASP, LGPD, secrets, input validation
  │    Emite: security-review.json
  │
  ├─ qa-expert (audit-tests) [isolado]
  │    Valida: cobertura, edge cases, testes frageis
  │    Emite: test-audit.json
  │
  └─ product-expert (functional-gate) [isolado]
       Valida: ACs atendidos do ponto de vista do usuario
       Emite: functional-review.json
```

### E.4 — Gates paralelos condicionais
```
Em paralelo (so se slice toca o dominio):
  ├─ data-expert (data-gate) [isolado]        → se tem migrations/queries
  │    Valida: integridade, performance, tenant isolation
  │    Emite: data-review.json
  │
  ├─ observability-expert (gate) [isolado]     → se tem logging/metrics
  │    Valida: logging estruturado, nao loga PII, health checks
  │    Emite: observability-review.json
  │
  └─ integration-expert (gate) [isolado]       → se tem APIs externas
       Valida: resiliencia, idempotencia, timeout, retry
       Emite: integration-review.json
```

### E.5 — Gate final dual-LLM
```
governance (master-audit) [isolado]
  Consolida: TODOS os gate outputs anteriores
  Dual-LLM: Claude Opus 4.6 + GPT-5 via Codex CLI
  Ambos devem concordar
  Emite: master-audit.json
```

### E.6 — Loop de correcao
```
Se ANY gate tem findings:
  builder (fixer) corrige TODOS os findings
  → re-run do MESMO gate (nao pula)
  → repete ate findings: []

Se 6x rejected consecutivo no mesmo gate (R6):
  → escala PM via /explain-slice
```

### E.7 — Merge
```
Todos os gates approved com zero findings
  → /merge-slice NNN
```

---

## Fase F — Encerramento

### F.1 — Retrospectiva de epico
```
governance (retrospective)
  Produz: scan completo do epico, inconsistencias, drift, ACs parciais
        ↓
Cross-review:
  └─ qa-expert (audit-planning): findings da retrospectiva sao validos? Cobertura completa?
        ↓
Se findings → builder (fixer) corrige → re-audit (ate 10 iteracoes)
```

### F.2 — Melhoria do harness
```
governance (harness-learner)
  Produz: melhorias incrementais no harness (max 3 por ciclo, R16)
        ↓
Cross-review:
  └─ security-expert (spec-security): mudancas no harness nao enfraquecem seguranca?
```

### F.3 — Auditoria continua
```
governance (guide-audit)  [periodico, nao por slice]
  Detecta: drift silencioso, hooks desabilitados, arquivos orfaos
  Emite: guide-audit.json
  NAO corrige — so reporta
```

---

## Resumo: quem entra em cada fase (com cross-review)

| Agente | A | B | C | D | E | F |
|--------|---|---|---|---|---|---|
| product-expert | PRODUZ + REVISA | REVISA | PRODUZ | | GATE | |
| ux-designer | PRODUZ | PRODUZ | | | | |
| architecture-expert | | PRODUZ | PRODUZ | | GATE | |
| data-expert | | PRODUZ + REVISA | REVISA | | GATE* | |
| security-expert | REVISA | PRODUZ + REVISA | REVISA | | GATE | REVISA |
| qa-expert | REVISA | REVISA | AUDITA | | GATE | REVISA |
| devops-expert | | PRODUZ + REVISA | | | GATE* | |
| observability-expert | | PRODUZ | | | GATE* | |
| integration-expert | | PRODUZ | | | GATE* | |
| builder | | | | PRODUZ | FIX | FIX |
| governance | | | | | GATE | PRODUZ |
| orchestrator | COORD | COORD | COORD | COORD | COORD | COORD |

PRODUZ = cria artefato | REVISA = confere artefato de outro | GATE = validacao isolada
AUDITA = auditoria formal com JSON | FIX = corrige findings | COORD = coordena
*condicional

---

## Regra de ouro do cross-review

> **Quem produz NUNCA confere seu proprio trabalho.**
> Conferencia e sempre por especialista de OUTRO dominio.
> O angulo da conferencia depende do tipo de risco:
> - Risco de qualidade → qa-expert
> - Risco de seguranca → security-expert
> - Risco de viabilidade tecnica → architecture-expert
> - Risco de produto/usuario → product-expert
> - Risco de dados → data-expert
