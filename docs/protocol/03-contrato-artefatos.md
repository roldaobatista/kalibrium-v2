# 03 — Contrato de Artefatos

> Documento normativo. Versao 1.1.0 — 2026-04-16.
> Define o contrato de producao, revisao, formato e aceitacao de cada artefato do pipeline Kalibrium V2.

---

## 1. Estrutura de cada contrato

Todo artefato listado neste documento possui os seguintes campos obrigatorios:

| Campo | Descricao |
|---|---|
| **Objetivo** | Proposito do artefato em 1 linha. |
| **Owner** | Agent que produz o artefato. |
| **Reviewer** | Agent(s) que revisam o artefato em contexto isolado. |
| **Input obrigatorio** | Artefatos que devem existir antes da producao. |
| **Output** | Caminho do arquivo e formato esperado. |
| **Versionamento** | Como alteracoes sao rastreadas. |
| **Criterio de aceitacao** | Condicao binaria que define "done". |
| **Destino** | Quem consome este artefato na proxima fase. |

---

## 2. Fase A — Descoberta

### 2.1. glossary.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir termos do dominio com significado unico e nao-ambiguo. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | Transcricao da sessao `/intake` com PM. |
| **Output** | `docs/glossary-domain.md` — Markdown com tabela termo/definicao/exemplo. |
| **Versionamento** | Git diff. Cada alteracao deve ser commitada com `docs(glossary):`. |
| **Criterio de aceitacao** | Todo termo usado no PRD deve ter entrada no glossario. Nenhum termo com definicao ambigua ou circular. |
| **Destino** | Todos os agents (referencia transversal). |

### 2.2. domain-model.md

| Campo | Valor |
|---|---|
| **Objetivo** | Mapear entidades, agregados e relacoes do dominio de negocio. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | glossary.md + transcricao `/intake`. |
| **Output** | `docs/architecture/domain-model.md` — Markdown com diagrama Mermaid (classDiagram) + descricao textual de cada entidade. |
| **Versionamento** | Git diff. Commit com `docs(domain):`. |
| **Criterio de aceitacao** | Toda entidade do glossario deve aparecer no modelo. Relacoes devem ter cardinalidade explicita. Nenhuma entidade orfã. |
| **Destino** | architecture-expert (plan), data-expert (modeling), product-expert (decompose). |

### 2.3. nfr.md

| Campo | Valor |
|---|---|
| **Objetivo** | Documentar requisitos nao-funcionais com metricas mensuraveis. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | Transcricao `/intake` + glossary.md. |
| **Output** | `docs/architecture/nfr.md` — Markdown com tabela NFR-ID/categoria/descricao/metrica/threshold. |
| **Versionamento** | Git diff. Commit com `docs(nfr):`. |
| **Criterio de aceitacao** | Todo NFR deve ter metrica numerica mensuravel (ex: "p95 < 200ms", nao "deve ser rapido"). Categorias obrigatorias: performance, seguranca, disponibilidade, escalabilidade, conformidade. |
| **Destino** | architecture-expert (plan), security-expert (security-gate), qa-expert (audit-tests). |

### 2.4. risks.md

| Campo | Valor |
|---|---|
| **Objetivo** | Catalogar riscos do projeto com probabilidade, impacto e mitigacao. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | Transcricao `/intake` + domain-model.md + nfr.md. |
| **Output** | `docs/architecture/risks.md` — Markdown com tabela RISK-ID/descricao/probabilidade(1-5)/impacto(1-5)/score/mitigacao. |
| **Versionamento** | Git diff. Commit com `docs(risks):`. |
| **Criterio de aceitacao** | Score = probabilidade x impacto. Todo risco com score >= 12 deve ter mitigacao concreta (nao generica). |
| **Destino** | architecture-expert (plan), security-expert (security-gate), PM (via R12). |

### 2.5. personas.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir arquetipos de usuario com necessidades, dores e objetivos. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | Transcricao `/intake`. |
| **Output** | `docs/design/personas.md` — Markdown com secao por persona: nome/papel/necessidades/dores/objetivos. |
| **Versionamento** | Git diff. Commit com `docs(personas):`. |
| **Criterio de aceitacao** | Toda persona referenciada no PRD deve ter entrada. Cada persona deve ter pelo menos 2 necessidades e 2 dores. |
| **Destino** | ux-designer, product-expert (functional-gate), product-expert (decompose). |

### 2.6. user-journeys.md

| Campo | Valor |
|---|---|
| **Objetivo** | Documentar jornadas de usuario ponta-a-ponta para cada fluxo critico. |
| **Owner** | product-expert (discovery) |
| **Reviewer** | qa-expert (audit-planning), ux-designer |
| **Input obrigatorio** | personas.md + domain-model.md. |
| **Output** | `docs/design/user-journeys.md` — Markdown com secao por jornada: persona/trigger/passos/resultado esperado/pontos de dor. |
| **Versionamento** | Git diff. Commit com `docs(journeys):`. |
| **Criterio de aceitacao** | Todo fluxo critico do PRD deve ter jornada mapeada. Cada jornada deve referenciar persona existente. |
| **Destino** | ux-designer, product-expert (functional-gate), product-expert (decompose). |

---

## 3. Fase B — Estrategia Tecnica

### 3.1. ADR-NNNN.md

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar decisao arquitetural com contexto, alternativas e consequencias. |
| **Owner** | architecture-expert (design) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | Problema identificado + alternativas avaliadas. PRD frozen. |
| **Output** | `docs/adr/ADR-NNNN.md` — Markdown seguindo template em `docs/adr/TEMPLATE.md`. Campos: titulo, status, contexto, decisao, alternativas, consequencias. |
| **Versionamento** | Imutavel apos status `accepted`. Nova ADR para superseder (status `superseded by ADR-MMMM`). |
| **Criterio de aceitacao** | Deve ter pelo menos 2 alternativas avaliadas. Consequencias devem listar trade-offs concretos. Status deve ser `proposed`, `accepted` ou `superseded`. |
| **Destino** | Todos os agents (referencia tecnica). PM via `/decide-stack` (R12). |

### 3.2. api-contracts.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir contratos REST de todos os endpoints do epico. |
| **Owner** | architecture-expert (design) |
| **Reviewer** | architecture-expert (plan), security-expert (security-gate) |
| **Input obrigatorio** | domain-model.md + ADRs relevantes + PRD frozen. |
| **Output** | `docs/architecture/api-contracts.md` — Markdown com tabela por endpoint: metodo/path/request body/response/status codes/auth. Opcionalmente OpenAPI YAML em `docs/architecture/openapi/`. |
| **Versionamento** | Git diff. Commit com `docs(api):`. Versionamento semantico por epico. |
| **Criterio de aceitacao** | Todo endpoint deve ter request/response tipado. Status codes devem incluir 4xx e 5xx. Auth deve referenciar Policy/Gate. Paginacao obrigatoria em listagens. |
| **Destino** | builder (implementer), qa-expert (audit-tests), integration-review. |

### 3.3. erd.md

| Campo | Valor |
|---|---|
| **Objetivo** | Documentar modelo de dados relacional com diagrama Mermaid. |
| **Owner** | data-expert (modeling) |
| **Reviewer** | architecture-expert (plan) |
| **Input obrigatorio** | domain-model.md + api-contracts.md. |
| **Output** | `docs/architecture/erd.md` — Markdown com diagrama Mermaid (erDiagram) + descricao textual de cada tabela/coluna. |
| **Versionamento** | Git diff. Commit com `docs(erd):`. |
| **Criterio de aceitacao** | Toda entidade do domain-model deve ter tabela correspondente. Foreign keys explicitas. Indices documentados. Coluna `tenant_id` obrigatoria em tabelas multi-tenant. Tipos PostgreSQL especificos (nao generico). |
| **Destino** | builder (implementer, migrations), data-review gate. |

### 3.4. threat-model.md

| Campo | Valor |
|---|---|
| **Objetivo** | Identificar ameacas, vetores de ataque e contramedidas. |
| **Owner** | security-expert (threat-model) |
| **Reviewer** | architecture-expert (plan) |
| **Input obrigatorio** | domain-model.md + api-contracts.md + nfr.md. |
| **Output** | `docs/compliance/threat-model.md` — Markdown com STRIDE por componente: ameaca/vetor/impacto/contramedida/status. |
| **Versionamento** | Git diff. Commit com `docs(security):`. |
| **Criterio de aceitacao** | Todo endpoint de API deve ter analise STRIDE. Toda contramedida deve referenciar implementacao concreta (middleware, policy, encryption). |
| **Destino** | security-gate, builder (implementer). |

### 3.5. lgpd-base-legal.md

| Campo | Valor |
|---|---|
| **Objetivo** | Mapear base legal LGPD para cada tipo de dado pessoal tratado. |
| **Owner** | security-expert (spec-security) |
| **Reviewer** | architecture-expert (plan) |
| **Input obrigatorio** | domain-model.md + erd.md + threat-model.md. |
| **Output** | `docs/compliance/lgpd-base-legal.md` — Markdown com tabela: dado pessoal/base legal (art. 7)/finalidade/retencao/titular pode excluir?. |
| **Versionamento** | Git diff. Commit com `docs(lgpd):`. |
| **Criterio de aceitacao** | Todo campo PII identificado no ERD deve ter base legal mapeada. Dados sem base legal valida devem ser removidos do modelo. |
| **Destino** | security-review gate, functional-review gate. |

### 3.6. style-guide.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir padroes visuais, tokens de design e convencoes de UI. |
| **Owner** | ux-designer |
| **Reviewer** | product-expert (functional-gate) |
| **Input obrigatorio** | personas.md + user-journeys.md. |
| **Output** | `docs/design/style-guide.md` — Markdown com secoes: cores (Tailwind tokens), tipografia, espacamento, componentes base, estados (loading, empty, error). |
| **Versionamento** | Git diff. Commit com `docs(design):`. |
| **Criterio de aceitacao** | Todas as cores devem ser tokens Tailwind validos. Componentes base devem ter exemplo de uso. Estados obrigatorios documentados. |
| **Destino** | builder (implementer, UI), functional-gate. |

### 3.7. wireframes/

| Campo | Valor |
|---|---|
| **Objetivo** | Representar layout e fluxo de cada tela antes da implementacao. |
| **Owner** | ux-designer |
| **Reviewer** | product-expert (functional-gate) |
| **Input obrigatorio** | user-journeys.md + style-guide.md + api-contracts.md. |
| **Output** | `docs/design/wireframes/` — Diretorio com 1 arquivo por tela. Formato: Markdown com ASCII art, ou imagem PNG com descricao textual. Nome: `ENN-SNN-nome-tela.md`. |
| **Versionamento** | Git diff. Commit com `docs(wireframes):`. |
| **Criterio de aceitacao** | Toda tela referenciada em user-journeys deve ter wireframe. Cada wireframe deve indicar componentes Vue planejados. |
| **Destino** | builder (implementer, UI), functional-gate. |

### 3.8. screen-inventory.md

| Campo | Valor |
|---|---|
| **Objetivo** | Catalogar todas as telas do sistema com rota, componente e permissao. |
| **Owner** | ux-designer |
| **Reviewer** | architecture-expert (plan), product-expert (functional-gate) |
| **Input obrigatorio** | wireframes/ + api-contracts.md. |
| **Output** | `docs/design/screen-inventory.md` — Markdown com tabela: tela/rota Inertia/componente Vue/permissao/persona. |
| **Versionamento** | Git diff. Commit com `docs(screens):`. |
| **Criterio de aceitacao** | Toda rota Inertia planejada deve ter entrada. Toda tela deve referenciar persona e permissao. |
| **Destino** | builder (implementer), functional-gate. |

### 3.9. observability-plan.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir estrategia de logging, metricas e alertas. |
| **Owner** | architecture-expert (design) |
| **Reviewer** | security-expert (security-gate) |
| **Input obrigatorio** | nfr.md + api-contracts.md. |
| **Output** | `docs/architecture/observability-plan.md` — Markdown com secoes: logs estruturados (canais Laravel), metricas (quais/como), alertas (condicao/canal), dashboards planejados. |
| **Versionamento** | Git diff. Commit com `docs(observability):`. |
| **Criterio de aceitacao** | Todo endpoint critico deve ter log de entrada/saida. Todo NFR de performance deve ter metrica correspondente. Alertas devem ter threshold numerico. |
| **Destino** | builder (implementer), observability-review gate. |

### 3.10. integration-map.md

| Campo | Valor |
|---|---|
| **Objetivo** | Mapear todas as integracoes externas com contratos e fallbacks. |
| **Owner** | architecture-expert (design) |
| **Reviewer** | architecture-expert (plan), security-expert (security-gate) |
| **Input obrigatorio** | PRD + api-contracts.md. |
| **Output** | `docs/architecture/integration-map.md` — Markdown com tabela por integracao: servico/protocolo/auth/timeout/retry/fallback/versao da API. |
| **Versionamento** | Git diff. Commit com `docs(integrations):`. |
| **Criterio de aceitacao** | Toda integracao externa deve ter timeout, retry e fallback documentados. Nenhuma integracao sem contrato versionado. |
| **Destino** | builder (implementer), integration-review gate. |

### 3.11. ci-pipeline.yml

| Campo | Valor |
|---|---|
| **Objetivo** | Definir pipeline de CI/CD com jobs, triggers e ambientes. |
| **Owner** | devops-expert (ci-design) |
| **Reviewer** | security-expert (security-gate) |
| **Input obrigatorio** | ADRs de deploy + stack decision. |
| **Output** | `.github/workflows/ci.yml` — GitHub Actions YAML. |
| **Versionamento** | Git diff. Commit com `ci:`. |
| **Criterio de aceitacao** | Pipeline deve incluir: lint (Pint), types (PHPStan/Larastan), testes (Pest), build frontend (Vite). Secrets nao podem ser hardcoded. |
| **Destino** | Todos (execucao automatica em push/PR). |

---

## 4. Fase C — Planejamento

### 4.1. roadmap.md

| Campo | Valor |
|---|---|
| **Objetivo** | Sequenciar epicos MVP com dependencias e prioridades. |
| **Owner** | product-expert (decompose) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | PRD frozen + domain-model.md. |
| **Output** | `docs/roadmap.md` — Markdown com tabela: epico/titulo/dependencias/prioridade/status. |
| **Versionamento** | Git diff. Commit com `docs(roadmap):`. |
| **Criterio de aceitacao** | Todo epico do PRD deve ter entrada. Dependencias devem formar DAG (sem ciclos). Prioridade deve ser unica (sem empates). |
| **Destino** | PM (via R12), orchestrator (sequenciamento R13/R14). |

### 4.2. Contrato de epico (epics/ENN/)

| Campo | Valor |
|---|---|
| **Objetivo** | Definir escopo, objetivos e criterios de sucesso de um epico. |
| **Owner** | product-expert (decompose) |
| **Reviewer** | qa-expert (audit-planning) |
| **Input obrigatorio** | roadmap.md + PRD. |
| **Output** | `docs/epics/ENN/README.md` — Markdown com: objetivo, escopo, fora-de-escopo, metricas de sucesso, dependencias. |
| **Versionamento** | Git diff. Commit com `docs(epic-ENN):`. |
| **Criterio de aceitacao** | Escopo e fora-de-escopo devem ser listas concretas (nao vagas). Metricas devem ser numericas. |
| **Destino** | product-expert (decompose), PM (via R12). |

### 4.3. Contrato de story (ENN-SNN.md)

| Campo | Valor |
|---|---|
| **Objetivo** | Definir escopo atomico de uma story com ACs testaveis. |
| **Owner** | product-expert (decompose) |
| **Reviewer** | qa-expert (audit-story) |
| **Input obrigatorio** | Contrato de epico + domain-model.md + api-contracts.md (se UI/API). |
| **Output** | `docs/epics/ENN/stories/ENN-SNN.md` — Markdown com frontmatter YAML (dependencies, lane) + secoes: objetivo, ACs numerados, fora-de-escopo, notas tecnicas. |
| **Versionamento** | Git diff. Commit com `docs(story-ENN-SNN):`. |
| **Criterio de aceitacao** | Toda AC deve ser testavel (verbo + condicao + resultado esperado). Frontmatter deve declarar `dependencies` e `lane` sugerida. |
| **Destino** | orchestrator (`/start-story`), qa-expert (audit-spec). |

### 4.4. spec.md

| Campo | Valor |
|---|---|
| **Objetivo** | Especificar o que o slice deve fazer, com ACs numerados e testaveis. |
| **Owner** | orchestrator (via `/draft-spec`) ou product-expert (decompose) |
| **Reviewer** | qa-expert (audit-spec) (contexto isolado) |
| **Input obrigatorio** | Story contract + glossary.md. |
| **Output** | `specs/NNN/spec.md` — Markdown com frontmatter YAML (title, lane, story, epic) + secoes: contexto, jornada do usuario, ACs numerados (AC-NNN-XXX), fora-de-escopo. |
| **Versionamento** | Git diff. Commit com `spec(slice-NNN):`. |
| **Criterio de aceitacao** | Toda AC deve ter formato "Dado X, quando Y, entao Z". Nenhuma AC ambigua. qa-expert (audit-spec) deve emitir `verdict: approved` com `findings: []`. |
| **Destino** | architecture-expert (plan), builder (test-writer), product-expert (functional-gate). |

### 4.5. plan.md

| Campo | Valor |
|---|---|
| **Objetivo** | Definir como o slice sera implementado tecnicamente. |
| **Owner** | architecture-expert (plan) |
| **Reviewer** | architecture-expert (plan-review) (contexto isolado) |
| **Input obrigatorio** | spec.md auditada + ADRs relevantes + erd.md + api-contracts.md. |
| **Output** | `specs/NNN/plan.md` — Markdown com secoes: arquivos a criar/modificar, migrations, rotas, components Vue, services, testes planejados. |
| **Versionamento** | Git diff. Commit com `plan(slice-NNN):`. |
| **Criterio de aceitacao** | Todo arquivo mencionado deve ter path completo. Toda migration deve ter up e down. architecture-expert (plan-review) deve emitir `verdict: approved` com `findings: []`. |
| **Destino** | builder (implementer), builder (test-writer), todos os gates (referencia). |

### 4.6. tasks.md

| Campo | Valor |
|---|---|
| **Objetivo** | Decompor plan.md em tarefas atomicas ordenadas para o builder (implementer). |
| **Owner** | architecture-expert (plan) |
| **Reviewer** | architecture-expert (plan-review) |
| **Input obrigatorio** | plan.md aprovado. |
| **Output** | `specs/NNN/tasks.md` — Markdown com checklist numerado: T-NNN-XX / descricao / arquivos / dependencia de task anterior. |
| **Versionamento** | Git diff. Commit com `plan(slice-NNN):`. |
| **Criterio de aceitacao** | Toda task deve ser completavel em 1 commit. Nenhuma task deve ter mais de 3 arquivos. Ordem deve respeitar dependencias. |
| **Destino** | builder (implementer). |

### 4.7. spec-audit.json

| Campo | Valor |
|---|---|
| **Objetivo** | Validar spec.md contra roadmap, contrato de epico, ADRs e constitution.md. |
| **Owner** | qa-expert (modo: audit-spec) |
| **Reviewer** | Nenhum (gate independente). |
| **Input obrigatorio** | spec.md + roadmap.md + contrato de epico (ENN/README.md) + ADRs relevantes + constitution.md. |
| **Output** | `specs/NNN/spec-audit.json` — JSON com schema base de gate (secao 8). |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). |
| **Destino** | architecture-expert (plan) — plan.md so pode ser gerado apos spec-audit aprovada. |

### 4.8. plan-review.json

| Campo | Valor |
|---|---|
| **Objetivo** | Validar plan.md em contexto isolado, verificando consistencia tecnica e alinhamento com spec e ADRs. |
| **Owner** | qa-expert (modo: review-plan) |
| **Reviewer** | Nenhum (gate independente). |
| **Input obrigatorio** | plan.md + spec.md auditada + ADRs relevantes + constitution.md. |
| **Output** | `specs/NNN/plan-review.json` — JSON com schema base de gate (secao 8). |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). |
| **Destino** | builder (test-writer) (testes red) — testes so podem ser gerados apos plan-review aprovada. |

### 4.9. security-pre-review.json (somente L4 — high-risk)

| Campo | Valor |
|---|---|
| **Objetivo** | Pre-review de seguranca do plano antes da implementacao. Aplicavel somente a slices classificados como L4 (high-risk). |
| **Owner** | security-expert (modo: spec-security) |
| **Reviewer** | Nenhum (gate independente). |
| **Input obrigatorio** | plan.md aprovado + spec.md auditada + threat-model.md. |
| **Output** | `specs/NNN/security-pre-review.json` — JSON com schema base de gate (secao 8) + campos extras: `threat_vectors_assessed[]`, `mitigation_plan_adequate`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). |
| **Destino** | builder (modo: test-writer) — implementacao so inicia apos pre-review de seguranca em trilha L4. |

### 4.10. data-migration-review.json (somente L4 — high-risk)

| Campo | Valor |
|---|---|
| **Objetivo** | Validar plano de migracao de dados com estrategia de rollback antes da implementacao. Aplicavel somente a slices L4 com migrations. |
| **Owner** | data-expert (modo: review) |
| **Reviewer** | Nenhum (gate independente). |
| **Input obrigatorio** | plan.md (secao de migrations) + erd.md + schema atual do banco. |
| **Output** | `specs/NNN/data-migration-review.json` — JSON com schema base de gate (secao 8) + campos extras: `rollback_strategy_defined`, `data_loss_risk`, `estimated_downtime`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). `rollback_strategy_defined` deve ser `true`. `data_loss_risk` deve ser `none`. |
| **Destino** | builder — implementacao de migrations so inicia apos aprovacao em trilha L4. |

### 4.11. integration-pre-review.json (somente L4 — high-risk)

| Campo | Valor |
|---|---|
| **Objetivo** | Validar contratos de API externa e padroes de resiliencia antes da implementacao. Aplicavel somente a slices L4 com integracoes externas. |
| **Owner** | integration-expert (modo: strategy) |
| **Reviewer** | Nenhum (gate independente). |
| **Input obrigatorio** | plan.md (secao de integracoes) + integration-map.md + documentacao da API externa. |
| **Output** | `specs/NNN/integration-pre-review.json` — JSON com schema base de gate (secao 8) + campos extras: `contracts_validated`, `resilience_patterns_defined`, `fallback_strategy`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). `contracts_validated` e `resilience_patterns_defined` devem ser `true`. |
| **Destino** | builder — implementacao de integracoes so inicia apos aprovacao em trilha L4. |

---

## 5. Fase D — Execucao

### 5.1. Arquivos de teste (tests/*.php)

| Campo | Valor |
|---|---|
| **Objetivo** | Validar cada AC do spec.md com teste executavel em Pest 4. |
| **Owner** | builder (test-writer) |
| **Reviewer** | qa-expert (audit-tests) (na Fase E) |
| **Input obrigatorio** | spec.md auditada + plan.md aprovado. |
| **Output** | `tests/Feature/SliceNNN/*.php` e/ou `tests/Unit/SliceNNN/*.php` — Pest 4 PHP. Nomenclatura: `AC_NNN_XXX_DescricaoTest.php`. |
| **Versionamento** | Git diff. Commit inicial: `test(slice-NNN): AC tests red`. Commit pos-green: `feat(slice-NNN): <descricao>`. |
| **Criterio de aceitacao** | Toda AC do spec.md deve ter pelo menos 1 teste. Testes devem falhar (red) no commit inicial. Testes devem passar (green) apos implementacao. |
| **Destino** | builder (implementer, faz green), qa-expert (audit-tests, valida cobertura). |

### 5.2. Arquivos de codigo-fonte

| Campo | Valor |
|---|---|
| **Objetivo** | Implementar a funcionalidade descrita no spec.md e plan.md. |
| **Owner** | builder (implementer) |
| **Reviewer** | qa-expert (verify) + architecture-expert (code-review) (na Fase E) |
| **Input obrigatorio** | plan.md aprovado + tasks.md + testes red. |
| **Output** | Arquivos em `app/` (Models, Controllers, Services, Actions, Policies, Requests, Resources), `resources/` (Vue components, CSS), `routes/`, `config/`. Paths conforme plan.md. |
| **Versionamento** | Git diff. Commits atomicos por task: `feat(slice-NNN): T-NNN-XX <descricao>`. |
| **Criterio de aceitacao** | Todos os testes red devem estar green. Lint (Pint) deve passar. Types (Larastan) deve passar. Nenhum arquivo fora do plan.md deve ser alterado sem justificativa. |
| **Destino** | Todos os gates da Fase E. |

### 5.3. Arquivos de migration

| Campo | Valor |
|---|---|
| **Objetivo** | Alterar o schema do banco PostgreSQL conforme erd.md e plan.md. |
| **Owner** | builder (implementer) |
| **Reviewer** | data-review gate (se ativado) |
| **Input obrigatorio** | plan.md + erd.md. |
| **Output** | `database/migrations/YYYY_MM_DD_HHMMSS_<descricao>.php` — Laravel migration com `up()` e `down()`. |
| **Versionamento** | Git diff. Commit com `feat(slice-NNN):` ou `db(slice-NNN):`. |
| **Criterio de aceitacao** | `up()` e `down()` devem ser inversos exatos. `down()` deve ser testavel via `php artisan migrate:rollback`. Coluna `tenant_id` obrigatoria em tabelas multi-tenant. Indices documentados no plan.md devem existir na migration. |
| **Destino** | data-review gate, verify-slice gate. |

---

## 6. Fase E — Saidas de Gate

### 6.1. verification.json

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da validacao mecanica do slice. |
| **Owner** | qa-expert (verify) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | Todos os arquivos do slice (codigo, testes, migrations, spec.md, plan.md). |
| **Output** | `specs/NNN/verification.json` — JSON com schema: `{verdict, agent, timestamp, lane, blocking_findings_count, non_blocking_findings_count, findings_by_severity, findings[]}`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0` (zero findings S1-S3). |
| **Destino** | orchestrator (decide proximo gate), governance (master-audit), merge-slice. |

### 6.2. review.json

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao estrutural de codigo. |
| **Owner** | architecture-expert (code-review) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | verification.json com `verdict: approved` + todos os arquivos do slice. |
| **Output** | `specs/NNN/review.json` — JSON com mesmo schema de verification.json. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.3. security-review.json

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao de seguranca (OWASP, LGPD, secrets). |
| **Owner** | security-expert (security-gate) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | review.json com `verdict: approved` + threat-model.md + lgpd-base-legal.md + todos os arquivos do slice. |
| **Output** | `specs/NNN/security-review.json` — JSON com mesmo schema base + campos extras: `owasp_categories_checked[]`, `lgpd_compliance`, `secrets_scan_result`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. `secrets_scan_result` deve ser `clean`. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.4. test-audit.json

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da auditoria de cobertura e qualidade de testes. |
| **Owner** | qa-expert (audit-tests) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | review.json com `verdict: approved` + spec.md + todos os testes do slice. |
| **Output** | `specs/NNN/test-audit.json` — JSON com mesmo schema base + campos extras: `ac_coverage` (mapa AC→teste), `edge_cases_covered`, `test_quality_score`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. Toda AC do spec.md deve ter pelo menos 1 teste mapeado. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.5. functional-review.json

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao funcional (produto/UX/ACs). |
| **Owner** | product-expert (functional-gate) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | review.json com `verdict: approved` + spec.md + wireframes (se UI) + screen-inventory.md (se UI). |
| **Output** | `specs/NNN/functional-review.json` — JSON com mesmo schema base + campos extras: `ac_validation` (mapa AC→pass/fail), `ux_compliance`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. Toda AC deve ter `pass`. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.6. data-review.json (gate condicional)

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao de migrations e alteracoes de dados. |
| **Owner** | data-expert (data-gate) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | Arquivos de migration do slice + erd.md. |
| **Output** | `specs/NNN/data-review.json` — JSON com mesmo schema base + campos extras: `has_rollback`, `rollback_tested`, `data_loss_risk`, `index_coverage`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. `has_rollback` deve ser `true`. `data_loss_risk` deve ser `none`. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.7. observability-review.json (gate condicional)

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao de logging, metricas e alertas. |
| **Owner** | observability-expert (observability-gate) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | Arquivos de observabilidade do slice + observability-plan.md. |
| **Output** | `specs/NNN/observability-review.json` — JSON com mesmo schema base + campos extras: `log_coverage`, `metrics_coverage`, `alert_coverage`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. Endpoints criticos devem ter log de entrada/saida. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.8. integration-review.json (gate condicional)

| Campo | Valor |
|---|---|
| **Objetivo** | Registrar resultado da revisao de integracoes externas. |
| **Owner** | integration-expert (integration-gate) |
| **Reviewer** | governance (master-audit) (consolidacao) |
| **Input obrigatorio** | Arquivos de integracao do slice + integration-map.md. |
| **Output** | `specs/NNN/integration-review.json` — JSON com mesmo schema base + campos extras: `contract_version_locked`, `timeout_configured`, `retry_configured`, `fallback_exists`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se `blocking_findings_count == 0`. Todos os campos extras devem ser `true`. |
| **Destino** | orchestrator, governance (master-audit), merge-slice. |

### 6.9. master-audit.json

| Campo | Valor |
|---|---|
| **Objetivo** | Consolidar verdicts de todos os gates em auditoria final dual-LLM. |
| **Owner** | governance (master-audit) |
| **Reviewer** | Nenhum (instancia final). |
| **Input obrigatorio** | Todos os JSONs de gate do slice (verification, review, security, test-audit, functional + condicionais se aplicavel). |
| **Output** | `specs/NNN/master-audit.json` — JSON com: `verdict`, `trail_opus` (verdict Opus), `trail_gpt5` (verdict GPT-5), `reconciliation` (se divergiram), `consolidated_findings[]`, `gate_summary{}`. |
| **Versionamento** | Sobrescrito a cada re-run. Historico via git log. |
| **Criterio de aceitacao** | `verdict: "approved"` somente se ambas as trilhas (Opus + GPT-5) emitirem approved com `blocking_findings_count == 0`. Se divergirem, reconciliacao em ate 3 rodadas. Se persistir, escalar PM. |
| **Destino** | orchestrator (autoriza merge), merge-slice. |

---

## 7. Fase F — Encerramento

### 7.1. epic-retrospective.md

| Campo | Valor |
|---|---|
| **Objetivo** | Documentar licoes aprendidas e melhorias apos conclusao de um epico. |
| **Owner** | orchestrator (via `/retrospective`) |
| **Reviewer** | governance (guide-audit) |
| **Input obrigatorio** | Todos os slice-reports do epico + project-state.json. |
| **Output** | `docs/retrospectives/epic-ENN.md` — Markdown com secoes: metricas agregadas, problemas recorrentes, melhorias propostas, acoes concretas. |
| **Versionamento** | Git diff. Commit com `docs(retro-ENN):`. |
| **Criterio de aceitacao** | Toda melhoria proposta deve ter acao concreta com owner e deadline. Metricas devem incluir: tempo medio por slice, taxa de rejeicao por gate, findings por severidade. |
| **Destino** | PM (via R12), governance (harness-learner). |

### 7.2. harness-learner-report.md

| Campo | Valor |
|---|---|
| **Objetivo** | Documentar ajustes incrementais no harness propostos pelo learner pos-epico. |
| **Owner** | orchestrator (via governance harness-learner, ADR-0012 E4) |
| **Reviewer** | governance (guide-audit) |
| **Input obrigatorio** | governance (retrospective) epic-retrospective.md. |
| **Output** | `docs/governance/harness-learner-ENN.md` — Markdown com: regras adicionadas, hooks ajustados, skills criadas. Maximo 3 mudancas por ciclo (R16). |
| **Versionamento** | Git diff. Commit com `chore(harness):`. |
| **Criterio de aceitacao** | Nenhuma mudanca pode revogar, afrouxar ou alterar P1-P9 ou R1-R14. Maximo 3 mudancas. Cada mudanca deve ter justificativa baseada em dados da retrospectiva. |
| **Destino** | PM (para aprovacao), orchestrator (para implementacao). |

### 7.3. guide-audit.json

| Campo | Valor |
|---|---|
| **Objetivo** | Detectar drift entre o harness documentado e o harness implementado. |
| **Owner** | governance (guide-audit) |
| **Reviewer** | Nenhum (audit independente). |
| **Input obrigatorio** | CLAUDE.md + constitution.md + todos os agents/*.md + todos os hooks. |
| **Output** | `docs/audits/guide-audit-YYYY-MM-DD.json` — JSON com: `drift_detected`, `findings[]`, `files_checked`, `timestamp`. |
| **Versionamento** | Arquivo novo a cada execucao (nao sobrescreve). |
| **Criterio de aceitacao** | `drift_detected: false` para aprovacao. Qualquer drift detectado deve gerar finding com file:line e descricao. |
| **Destino** | orchestrator (correcao de drift), PM (se drift critico). |

---

## 8. Schema JSON base para saidas de gate

Todo JSON de gate deve seguir este schema minimo:

```json
{
  "$schema": "gate-output-v1",
  "gate": "<nome-do-gate>",
  "slice": "<NNN>",
  "lane": "<L1|L2|L3|L4>",
  "agent": "<nome-do-agent>",
  "verdict": "<approved|rejected>",
  "timestamp": "<ISO 8601>",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {
    "S1": 0,
    "S2": 0,
    "S3": 0,
    "S4": 0,
    "S5": 0
  },
  "findings": [
    {
      "id": "F-NNN",
      "severity": "S1|S2|S3|S4|S5",
      "severity_label": "blocker|critical|major|minor|advisory",
      "gate_blocking": true,
      "description": "...",
      "file": "...",
      "line": null,
      "evidence": "...",
      "recommendation": "...",
      "reclassification_requested": false,
      "exception_granted": false
    }
  ]
}
```

Campos adicionais especificos de cada gate estao documentados na secao 6 deste documento.

---

## 9. Regras transversais

### 9.1. Ordem de dependencia entre artefatos

Nenhum artefato pode ser produzido antes que todos os seus inputs obrigatorios existam. O orchestrator deve validar a existencia dos inputs antes de delegar producao ao agent owner.

### 9.2. Imutabilidade pos-gate

Apos um gate emitir `verdict: approved`, os arquivos de input daquele gate nao podem ser alterados sem re-execucao do gate. Se forem alterados, todos os gates que os consumiram devem ser re-executados.

### 9.3. Rastreabilidade

Todo artefato deve ser commitado em git com mensagem que identifica o slice e a fase. O orchestrator deve rejeitar commits que nao seguem o padrao de nomenclatura definido neste documento.

### 9.4. Contexto isolado

Artefatos de gate (secao 6) devem ser produzidos por agents em contexto isolado (R3). O agent de gate nao pode ter acesso ao output de outro gate do mesmo slice durante sua execucao.

---

## 10. Schemas de telemetria e estado

### 10.1. Eventos de telemetria (slice-NNN.jsonl)

Cada slice deve ter um arquivo de telemetria em `.claude/telemetry/slice-NNN.jsonl` (append-only). O arquivo deve conter os seguintes eventos canonicos, um por linha, em formato JSON:

| Evento | Campos obrigatorios | Descricao |
|---|---|---|
| `slice_started` | `timestamp` (ISO 8601), `slice_id` (string), `story_id` (string), `epic_id` (string), `lane` (L1\|L2\|L3\|L4) | Emitido quando o orchestrator inicia um slice via `/start-story` ou `/new-slice`. |
| `gate_submitted` | `timestamp` (ISO 8601), `gate_name` (string), `agent` (string), `mode` (string) | Emitido quando o orchestrator delega execucao de um gate a um agent. |
| `gate_result` | `timestamp` (ISO 8601), `gate_name` (string), `verdict` (approved\|rejected), `blocking_findings` (int), `non_blocking_findings` (int), `duration_ms` (int), `tokens_used` (int) | Emitido quando um agent de gate retorna seu verdict. |
| `fix_applied` | `timestamp` (ISO 8601), `gate_name` (string), `findings_fixed` (int), `iteration` (int) | Emitido quando o fixer corrige findings de um gate e submete para re-run. |
| `r6_escalation` | `timestamp` (ISO 8601), `gate_name` (string), `consecutive_rejections` (int) | Emitido quando o mesmo gate atinge 6 rejeicoes consecutivas (R6) e escala ao PM. |
| `slice_merged` | `timestamp` (ISO 8601), `slice_id` (string), `pr_number` (int), `commit_hash` (string), `total_gates` (int), `total_fix_cycles` (int) | Emitido quando o slice e merged via `/merge-slice`. |
| `exception_triggered` | `timestamp` (ISO 8601), `exception_type` (E1-E9), `description` (string), `owner` (string) | Emitido quando uma excecao da politica 07 e acionada durante o slice. |

**Regras:**

1. O arquivo deve ser append-only. Nenhum evento pode ser removido ou alterado apos escrita.
2. Todo evento deve ter o campo `timestamp` em formato ISO 8601 com timezone UTC.
3. O orchestrator deve emitir `slice_started` antes de qualquer outro evento do slice.
4. O orchestrator deve emitir `slice_merged` como ultimo evento do slice.
5. Eventos fora da lista acima sao permitidos como extensao, desde que tenham `timestamp` e `event_type`.

### 10.2. project-state.json

O arquivo `project-state.json` na raiz do repositorio e a fonte de verdade do estado do projeto. O orchestrator deve mante-lo atualizado a cada checkpoint. Schema de chaves de primeiro nivel:

```json
{
  "version": "string — versao do schema (semver)",
  "project": "string — nome do projeto",
  "phase": "string — fase atual (discovery|strategy|planning|execution|closing)",
  "updated_at": "string — ISO 8601 do ultimo update",

  "discovery": {
    "intake_complete": "boolean",
    "prd_status": "string — draft|frozen",
    "nfr_status": "string — draft|approved",
    "domain_model_status": "string — draft|approved",
    "glossary_status": "string — draft|approved"
  },

  "strategy": {
    "architecture_status": "string — open|frozen",
    "adrs_count": "integer — total de ADRs aceitas",
    "threat_model_status": "string — draft|approved",
    "design_docs_status": "string — pending|complete"
  },

  "planning": {
    "epics_total": "integer",
    "stories_total": "integer",
    "epics_decomposed": "integer",
    "stories_decomposed": "integer"
  },

  "execution": {
    "current_epic": "string — ENN ou null",
    "current_story": "string — ENN-SNN ou null",
    "current_slice": "string — NNN ou null",
    "slice_status": "string — spec|plan|tests|impl|gates|merged",
    "lane": "string — L1|L2|L3|L4 ou null"
  },

  "gates_status": {
    "<gate_name>": {
      "verdict": "string — approved|rejected|pending",
      "blocking_findings": "integer",
      "non_blocking_findings": "integer"
    }
  },

  "epics_status": {
    "<ENN>": {
      "status": "string — planning|active|merged|blocked",
      "stories": {
        "<ENN-SNN>": "string — planning|active|merged|blocked"
      }
    }
  },

  "technical_debt": [
    {
      "id": "string — TD-NNN",
      "description": "string",
      "severity": "string — S3|S4",
      "owner": "string",
      "deadline": "string — ISO 8601",
      "status": "string — open|resolved"
    }
  ],

  "active_exceptions": [
    {
      "type": "string — E1-E9",
      "description": "string",
      "owner": "string",
      "deadline": "string — ISO 8601",
      "status": "string — active|resolved|expired"
    }
  ],

  "harness": {
    "agents": "integer — total de agents configurados",
    "skills": "integer — total de skills disponiveis",
    "hooks": "integer — total de hooks ativos",
    "protocol_version": "string — versao do protocolo operacional"
  }
}
```

**Regras:**

1. Toda chave de primeiro nivel deve existir. Chaves ausentes sao finding S2 em guide-audit.
2. `updated_at` deve ser atualizado em todo checkpoint.
3. `gates_status` deve refletir o estado do slice ativo. Ao iniciar novo slice, todos os gates voltam a `pending`.
4. `epics_status` deve ser atualizado quando uma story muda de status.
5. `technical_debt` somente aceita severidades S3 e S4. S1 e S2 nao podem ser divida tecnica.
6. `active_exceptions` deve ter deadline. Excecoes sem deadline sao finding S2 em guide-audit.

---

## 11. Vigencia

Este documento entra em vigor imediatamente e aplica-se a todos os artefatos produzidos a partir da data de criacao. Artefatos existentes nao precisam ser retroativamente ajustados, mas novos artefatos do mesmo tipo devem seguir o contrato aqui definido.
