# Proposta: Reorganizacao de Agentes por Dominio

**Data:** 2026-04-16
**Status:** Proposta para decisao do PM
**Versao atual:** 25 agentes organizados por tarefa
**Proposta:** 12 agentes organizados por dominio de conhecimento (9 especialistas + construtor + governanca + orquestrador)

---

## Problema atual

Os 25 agentes estao organizados por **tarefa** (um agente = uma acao especifica). Isso causa:

1. **Fragmentacao de conhecimento** — o conhecimento de seguranca esta espalhado entre security-reviewer, spec-auditor (que checa requisitos de seguranca) e functional-reviewer (que valida LGPD na pratica)
2. **Dificuldade de manutencao** — 25 arquivos .md para manter sincronizados
3. **Sobreposicao** — 4 auditores de planejamento (spec-auditor, plan-reviewer, planning-auditor, story-auditor) com logica similar
4. **Agentes muito pequenos** — domain-analyst e nfr-analyst tem escopo tao limitado que nao justificam agentes separados

---

## Proposta: 5 Especialistas de Dominio + 3 Agentes de Suporte

### Os 5 Dominios (que o PM pediu)

| # | Agente | Substitui | O que faz |
|---|--------|-----------|-----------|
| 1 | **product-expert** | domain-analyst, nfr-analyst, functional-reviewer | Dono do conhecimento de produto: entende o usuario, o dominio, as jornadas, NFRs, e valida se o que foi construido atende as necessidades reais |
| 1b | **ux-designer** | ux-designer (com escopo expandido) | Dono do design de experiencia: design system, wireframes, fluxos de interacao, acessibilidade, responsividade, inventario de telas, padroes visuais |
| 2 | **architecture-expert** | architect, api-designer, plan-reviewer | Dono do design de sistema: projeta APIs, planos de implementacao, e valida que a arquitetura e solida |
| 3 | **data-expert** | data-modeler (com escopo expandido) | Dono dos dados: modelagem de banco, migrations, integridade referencial, performance de queries, estrategia de tenant isolation, reportes e analytics |
| 4 | **security-expert** | security-reviewer (com escopo expandido) | Dono da seguranca: OWASP, LGPD, secrets, threat model. Atua em todas as fases, nao so no gate final |
| 5 | **qa-expert** | verifier, reviewer, test-auditor, spec-auditor, story-auditor, planning-auditor | Dono da qualidade: valida specs, stories, codigo e testes. Roda em contextos isolados diferentes conforme o gate |
| 6 | **devops-expert** | (novo) | Dono da infraestrutura: CI/CD, deploy, containers, pipelines de build |
| 7 | **observability-expert** | (novo) | Dono da observabilidade: logging, monitoramento, alertas, tracing, health checks, metricas de performance |
| 8 | **integration-expert** | (novo) | Dono das integracoes: APIs externas, webhooks, filas, eventos entre modulos, NF-e, gateways de pagamento, resiliencia de chamadas externas |

### Os 3 Agentes de Suporte

| # | Agente | Substitui | O que faz |
|---|--------|-----------|-----------|
| 6 | **builder** | ac-to-test, implementer, fixer | Executa: escreve testes, implementa codigo, corrige findings. E o "bracos" do time |
| 7 | **governance** | master-auditor, guide-auditor, harness-learner, epic-retrospective | Guardiao do processo: auditoria final dual-LLM, retrospectivas, melhoria continua do harness |
| 8 | **orchestrator** | orchestrator (sem mudanca) | Coordena tudo. Maquina de estados. Nao muda |

**Total: 25 agentes → 12 agentes** (reducao de 52%)

---

## Como funciona a isolacao (R3, R11)

A regra R11 exige que verifier e reviewer sejam **independentes em contextos isolados**. Com agentes por dominio, isso se resolve assim:

- O **qa-expert** tem **modos** de operacao: `verify`, `review`, `audit-spec`, `audit-story`, `audit-plan`, `audit-tests`
- O orquestrador spawna o qa-expert em contexto isolado para cada modo
- Cada instancia so ve o **pacote de input** do seu modo — nunca o output de outra instancia
- **R11 preservado**: mesma expertise, contextos separados

Analogamente:
- **product-expert** roda em modo `discovery` (intake) ou modo `functional-gate` (gate E)
- **architecture-expert** roda em modo `design` (fase B) ou modo `plan-review` (fase C)
- **security-expert** roda em modo `threat-model` (fase B) ou modo `security-gate` (fase E)

---

## Novo fluxo do pipeline

### Fase A — Descoberta
```
product-expert (modo: discovery)
  → glossario, modelo de dominio, NFRs, riscos
ux-designer (modo: research)
  → personas, jornadas de usuario, benchmarks visuais
```

### Fase B — Estrategia Tecnica
```
architecture-expert (modo: design)
  → ADRs, API contracts, threat model input
data-expert (modo: modeling)
  → ERDs, migrations, estrategia de tenant isolation
security-expert (modo: threat-model)
  → threat model, requisitos LGPD
ux-designer (modo: design)
  → design system, style guide, wireframes, screen inventory, component patterns
observability-expert (modo: strategy)
  → plano de logging, metricas, health checks
integration-expert (modo: strategy)
  → mapa de integracoes externas, contratos de webhook, estrategia de filas
```

### Fase C — Planejamento
```
product-expert (modo: decompose)
  → epicos, stories com Story Contract
qa-expert (modo: audit-planning)
  → audita epicos e stories
qa-expert (modo: audit-spec)
  → audita spec.md
architecture-expert (modo: plan)
  → gera plan.md (consulta data-expert para modelagem)
architecture-expert (modo: plan-review)  [contexto isolado]
  → valida plan.md
data-expert (modo: review)  [contexto isolado]
  → valida migrations e modelo de dados do plan
```

### Fase D — Execucao
```
builder (modo: test-writer)
  → testes red a partir dos ACs
builder (modo: implementer)
  → faz testes ficarem verdes
```

### Fase E — Pipeline de Gates (isolados)
```
1. qa-expert (modo: verify)         → verification.json
2. qa-expert (modo: review)         → review.json        [contexto isolado separado]
3. Em paralelo:
   - security-expert (modo: gate)   → security-review.json
   - qa-expert (modo: audit-tests)  → test-audit.json
   - product-expert (modo: functional-gate) → functional-review.json
   - data-expert (modo: data-gate)  → data-review.json   [se slice tem migrations]
   - observability-expert (modo: gate) → observability-review.json [se slice tem logging/metrics]
   - integration-expert (modo: gate)   → integration-review.json  [se slice tem APIs externas/webhooks]
4. governance (modo: master-audit)  → master-audit.json   [dual-LLM]

Fixer: builder (modo: fix) → corrige findings → re-run do gate
```

### Fase F — Encerramento
```
governance (modo: retrospective)
  → analisa epico, gera findings
governance (modo: harness-learner)
  → melhora harness a partir dos findings
governance (modo: guide-audit)     [continuo]
  → detecta drift
```

---

## Politica de modelos (custo vs qualidade)

| Agente | Modelo padrao | Modos que usam Opus | Modos que usam Sonnet |
|--------|--------------|---------------------|----------------------|
| product-expert | sonnet | functional-gate | discovery, decompose |
| ux-designer | sonnet | — | research, design, ux-gate |
| architecture-expert | opus | plan, plan-review | design (API) |
| data-expert | sonnet | data-gate | modeling, review |
| security-expert | opus | gate | threat-model |
| qa-expert | sonnet | review | verify, audit-* |
| devops-expert | sonnet | — | todos |
| observability-expert | sonnet | — | todos |
| integration-expert | sonnet | gate | strategy |
| builder | opus | implementer, fix | test-writer |
| governance | opus | master-audit, retrospective | guide-audit, harness-learner |
| orchestrator | opus | sempre | — |

---

## Comparacao: Antes vs Depois

| Aspecto | Antes (25 agentes) | Depois (12 agentes) |
|---------|--------------------|--------------------|
| Arquivos .md para manter | 25 | 12 |
| Conhecimento de seguranca | espalhado em 3+ agentes | centralizado em security-expert |
| Conhecimento de produto | espalhado em 4 agentes | centralizado em product-expert + ux-designer |
| Conhecimento de dados | misturado com arquitetura | centralizado em data-expert |
| Auditores de planejamento | 4 agentes separados | 1 agente (qa-expert) com modos |
| Isolacao R11 | por agente separado | por modo em contexto isolado |
| CI/CD/Deploy | nenhum agente | devops-expert (novo) |
| Observabilidade | nenhum agente | observability-expert (novo) |
| Integracoes externas | nenhum agente | integration-expert (novo) |
| Custo estimado | maior (muitos spawns Opus) | menor (modos Sonnet onde possivel) |
| Manutencao | alta (25 prompts para sincronizar) | baixa (12 prompts bem definidos) |

---

## Riscos e mitigacoes

| Risco | Mitigacao |
|-------|-----------|
| Agentes mais complexos (prompts maiores) | Cada modo tem secao clara no prompt. Modos nao usados nao entram no contexto |
| Perda de isolacao R11 | Orquestrador garante contexto isolado por modo. Mesmo mecanismo atual, so muda o nome do agente |
| Agente "sabe demais" de um dominio | Cada modo recebe apenas o pacote de input relevante. Nao tem acesso cruzado |
| devops-expert e novo e sem historico | Comeca vazio, ganha corpo conforme CI/deploy evolui. Nao bloqueia nada |
| observability-expert e novo e sem historico | Comeca com health checks existentes (slice 005), cresce com o produto |
| integration-expert e novo e sem historico | Ativa apenas quando slice envolve APIs externas/webhooks. Ate la, dormente |
| data-expert + architecture-expert podem ter fronteira ambigua | Regra clara: se e sobre banco/queries/migrations → data-expert; se e sobre APIs/sistema/componentes → architecture-expert |

---

## Proximo passo

Se voce aprovar esta proposta, eu:
1. Crio os 12 novos arquivos .md em `.claude/agents/`
2. Atualizo o orchestrator.md para o novo fluxo
3. Atualizo as skills que referenciam agentes antigos
4. Removo os 25 arquivos antigos
5. Atualizo CLAUDE.md secao 8
6. Rodo `/guide-check` para validar consistencia
7. Commito tudo como `refactor(harness): reorganize agents by domain`

**Nenhum impacto no codigo de producao** — apenas na configuracao do harness.
