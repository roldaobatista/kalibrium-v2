# Auditoria externa de completude — GPT-5 Codex

**Data:** 2026-04-10
**Auditor:** GPT-5 Codex (Codex desktop)
**Duração da auditoria:** ~90 minutos
**Materiais lidos:** `docs/audits/completeness-audit-prompt-2026-04-10.md`; `CLAUDE.md`; `docs/constitution.md`; `docs/audits/meta-audit-2026-04-10.md`; `docs/audits/meta-audit-2026-04-10-action-plan.md`; `docs/audits/progress/meta-audit-tracker.md`; `docs/reference/ideia-v1.md` (não existe); `docs/glossary-domain.md`; `docs/adr/0000-template.md`; `docs/adr/0002-mcp-policy.md`; `docs/incidents/pr-1-admin-merge.md`; `docs/incidents/bloco1-admin-bypass-2026-04-10.md`; árvore completa via `rg --files`; `README.md` (não existe); `docs/decisions/pm-decision-meta-audit-2026-04-10.md`. Materiais adicionais lidos para contexto: `docs/TECHNICAL-DECISIONS.md`; `docs/reference/v1-post-mortem.md`; `docs/reference/roles-from-bmad.md`; `docs/compliance/rfp-consultor-metrologia.md`; `docs/compliance/rfp-consultor-fiscal.md`; `docs/guide-backlog.md`; `docs/harness-limitations.md`; `docs/slice-registry.md`; `docs/templates/spec.md`; `docs/templates/plan.md`; `docs/templates/tasks.md`; `.claude/skills/decide-stack.md`; `.claude/skills/explain-slice.md`; `.claude/agents/verifier.md`; `.claude/agents/reviewer.md`; `scripts/decide-stack.sh`; `scripts/explain-slice.sh`.

## A. Documentação fundacional de produto

**Status:** parcialmente presente, mas insuficiente para decidir stack ou iniciar produto.

Existe um glossário canônico de domínio (`docs/glossary-domain.md:3-4`) e há decisões pontuais do PM de que metrologia e fiscal entram no MVP (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:18` e `docs/decisions/pm-decision-meta-audit-2026-04-10.md:22`). Também existem RFPs de consultores que descrevem pedaços de escopo regulado, por exemplo o motor de cálculo sob ISO/IEC 17025 (`docs/compliance/rfp-consultor-metrologia.md:12-16`) e emissão fiscal (`docs/compliance/rfp-consultor-fiscal.md:12-16`).

O que falta é a fundação de produto propriamente dita: PRD/MVP scope, personas, jornadas, requisitos funcionais e não funcionais, escopo fora do MVP, roadmap e modelo de negócio/pricing. A ausência não é teórica: `scripts/decide-stack.sh` aborta se `docs/mvp-scope.md` não existir (`scripts/decide-stack.sh:41-46`), e esse arquivo não existe. O item obrigatório `docs/reference/ideia-v1.md` também não existe, embora `docs/reference/v1-post-mortem.md:81` diga que o escopo funcional do `ideia.md` seria base para `docs/MVP-SCOPE.md` do V2. As próprias RFPs ainda referenciam a visão congelada ausente (`docs/compliance/rfp-consultor-metrologia.md:160-163`; `docs/compliance/rfp-consultor-fiscal.md:171-175`).

**Recomendação específica:** antes de aceitar ADR de stack, criar `docs/mvp-scope.md` com: problema-alvo, laboratório-tipo, personas mínimas, 3-5 jornadas críticas, escopo MVP, explicitamente fora do MVP, requisitos não funcionais, restrições de VPS Hostinger e orçamento. Criar também `docs/product/personas.md`, `docs/product/journeys.md`, `docs/product/pricing-assumptions.md` e repor `docs/reference/ideia-v1.md` ou remover todas as dependências a ele.

## B. Decisões arquiteturais fundacionais

**Status:** parcialmente presente.

Há uma disciplina de ADRs: o índice diz que decisões de stack, biblioteca crítica, tenancy, autenticação, API pública e política de teste exigem ADR (`docs/TECHNICAL-DECISIONS.md:18-22`), e o template exige pelo menos duas opções e consequências negativas (`docs/adr/0000-template.md:15-17`, `docs/adr/0000-template.md:46-60`). Existe ADR aceito para política de MCPs (`docs/adr/0002-mcp-policy.md:1-5`). Porém a stack está pendente (`docs/TECHNICAL-DECISIONS.md:15`), e as decisões conceitualmente anteriores à stack ainda não estão registradas: modelo arquitetural, multi-tenancy, modalidade web/PWA/app, modelo de dados inicial, estratégia de deployment, ambientes, autenticação e autorização.

O plano atual reconhece parte do problema ao exigir que ADR-0001 compare custo, multi-tenancy e maturidade (`docs/audits/meta-audit-2026-04-10-action-plan.md:192-195`) e crie `docs/stack.json` (`docs/audits/meta-audit-2026-04-10-action-plan.md:208-224`). Isso não basta: comparar stacks sem uma decisão prévia de tenancy, offline/PWA, deployment em VPS única, autenticação e limites fiscais/metrológicos tende a transformar ADR-0001 em uma recomendação bonita, mas com premissas invisíveis.

**Recomendação específica:** criar antes do ADR de stack um `docs/architecture/foundation-constraints.md` ou ADR específico de baseline arquitetural cobrindo: monolito modular vs outro modelo, tenancy, dados iniciais, ambientes, authn/authz, deployment/rollback em VPS Hostinger, integração com serviços externos e limites de compliance. O ADR de stack deve ser rejeitado se não citar esse baseline.

## C. Governança de segurança e compliance

**Status:** parcialmente presente para o harness; ausente para o produto.

O repositório trata bem alguns riscos do ambiente de agentes: MCPs têm política aceita e allowlist planejada (`docs/adr/0002-mcp-policy.md:11-23`, `docs/adr/0002-mcp-policy.md:63-71`), incidentes do harness estão documentados (`docs/incidents/pr-1-admin-merge.md:1-7`; `docs/incidents/bloco1-admin-bypass-2026-04-10.md:1-6`), e o glossário cobre termos LGPD básicos como controlador, operador, direito ao esquecimento, portabilidade e log imutável (`docs/glossary-domain.md:102-115`).

Para produto, faltam artefatos essenciais: threat model, data classification, base legal LGPD por fluxo de dados, DPIA, contrato de operador, política de retenção, backup e disaster recovery com RPO/RTO, criptografia em repouso e em trânsito, gestão de segredos, auditoria recorrente de segurança e política de dependências/supply chain. Não encontrei `docs/security/`, `docs/policies/`, `docs/operations/` ou equivalente. A RFP fiscal ainda explicita que LGPD fiscal está fora daquele contrato e será tratada separadamente (`docs/compliance/rfp-consultor-fiscal.md:214-215`).

**Recomendação específica:** criar `docs/security/product-threat-model.md`, `docs/compliance/lgpd-data-map.md`, `docs/compliance/dpia.md`, `docs/security/secrets-policy.md`, `docs/security/dependency-policy.md` e `docs/ops/backup-dr.md` antes de qualquer código que persista dados de cliente, certificado, marcação de ponto ou nota fiscal.

## D. Processos operacionais

**Status:** parcialmente presente para desenvolvimento; ausente para operação de SaaS.

Há processo de slice e DoD (`CLAUDE.md:122-138`; `docs/constitution.md:67-78`), tracker de blocos (`docs/audits/progress/meta-audit-tracker.md:1-6`) e plano de CI externo (`docs/audits/meta-audit-2026-04-10-action-plan.md:337-368`). Porém o próprio tracker mostra que Blocos 3-5, incluindo gates reais, tradutor PM e CI, ainda não começaram (`docs/audits/progress/meta-audit-tracker.md:64-96`). O backlog também declara CI externo aberto e bloqueado por ADR-0001 (`docs/guide-backlog.md:18-22`), e o fluxo atual ainda convive com admin bypass até o Bloco 5 (`docs/incidents/bloco1-admin-bypass-2026-04-10.md:81-87`).

Para produção faltam pipeline de deploy, rollback, observabilidade, logs/métricas/alertas, SLO/SLI, pager, suporte aos primeiros clientes, canal de bug report e processo de atualização legislativa contínua. A RFP fiscal reconhece que legislação muda por diário oficial e cutoff de modelo (`docs/compliance/rfp-consultor-fiscal.md:14`) e pede data de corte/revalidação (`docs/compliance/rfp-consultor-fiscal.md:74-79`), mas não há processo operacional no repo para monitorar, triagem e implementar mudanças legais.

**Recomendação específica:** criar `docs/ops/production-readiness.md`, `docs/ops/deploy-rollback.md`, `docs/ops/observability.md`, `docs/ops/customer-support.md` e `docs/compliance/law-watch.md`. O `law-watch` deve definir fontes oficiais, frequência, responsável, gatilho de incidente e como agentes transformam mudança legal em spec/teste.

## E. Artefatos de domínio regulado

**Status:** parcialmente presente, com boa direção e execução ainda incompleta.

O glossário é útil como vocabulário canônico: cobre GUM, incerteza, rastreabilidade e ISO 17025 (`docs/glossary-domain.md:14-27`), REP-P/AFD/eSocial (`docs/glossary-domain.md:54-65`), NF-e/NFS-e/ICMS/IBS/CBS (`docs/glossary-domain.md:72-83`), ICP-Brasil/A1/A3/PDF-A (`docs/glossary-domain.md:87-98`) e LGPD (`docs/glossary-domain.md:102-115`). As RFPs são fortes: metrologia pede 50 casos com referência normativa específica (`docs/compliance/rfp-consultor-metrologia.md:48-65`) e critérios de aceite com fonte normativa e responsabilidade técnica (`docs/compliance/rfp-consultor-metrologia.md:118-128`); fiscal pede casos com `legal_reference`, data de corte e mapa de risco (`docs/compliance/rfp-consultor-fiscal.md:49-89`, `docs/compliance/rfp-consultor-fiscal.md:131-139`).

O gap é que esses artefatos ainda são rascunhos, não pacotes de conformidade prontos. O tracker marca consultores, golden tests, policies e out-of-scope de REP-P/LGPD/ICP-Brasil como pendentes (`docs/audits/progress/meta-audit-tracker.md:122-146`). A rastreabilidade normativa ainda está planejada nos CSVs/RFPs, não implementada em `tests/golden/`, `docs/compliance/*-policy.md` ou matriz norma -> requisito -> teste -> código. Além disso, a RFP fiscal declara integração direta com SEFAZ fora de escopo (`docs/compliance/rfp-consultor-fiscal.md:96-100`), o que é correto, mas falta uma decisão de produto/arquitetura dizendo se o MVP usará provedor terceiro, emissão manual assistida ou nenhum módulo fiscal até homologação.

O glossário não é autoridade canônica suficiente para o verifier/reviewer. O reviewer avalia categorias genéricas como duplicação, complexidade, security, glossary e ADR compliance (`.claude/agents/reviewer.md:56-61`); isso não valida cálculo GUM, norma fiscal municipal, A3/HSM ou retenção LGPD. O próprio plano reconhece a necessidade de `domain-expert` e golden tests no Bloco 6 (`docs/audits/meta-audit-2026-04-10-action-plan.md:380-411`).

**Recomendação específica:** criar um pacote por domínio: `docs/compliance/metrology-policy.md`, `docs/compliance/fiscal-policy.md`, `docs/compliance/repp-policy.md`, `docs/compliance/icp-brasil-policy.md`, `docs/compliance/lgpd-policy.md`; cada um com norma/data/seção, decisão de escopo MVP, consultor responsável, matriz `norma -> requisito -> golden test -> slice`, frequência de revalidação e módulos proibidos para IA sem revisão externa.

## F. Estrutura do repositório e organização

**Status:** parcialmente presente.

A estrutura suporta bem o harness: há `docs/adr`, `docs/audits`, `docs/incidents`, `docs/schemas`, `docs/templates`, scripts e agentes. O template de spec pede contexto, jornada, ACs, fora de escopo e dependências externas (`docs/templates/spec.md:10-39`), e o template de plan mapeia AC para arquivos e teste principal (`docs/templates/plan.md:28-38`). O slice registry existe, mas está vazio porque o primeiro slice vem após Dia 1 (`docs/slice-registry.md:1-7`).

O que dificulta o trabalho por agentes é a ausência de áreas de produto, arquitetura, segurança e operações. A árvore atual tem `.claude`, `docs`, `scripts`, `specs`, mas não tem `src`, `tests`, `.github/workflows`, `README.md`, `docs/product`, `docs/security`, `docs/operations` ou `docs/policies`. Isso é aceitável para bootstrap de harness, mas insuficiente para o próximo gate decisório. A ausência de `README.md` também fere o item obrigatório do prompt e reduz onboarding para agentes/sessões novas.

**Recomendação específica:** introduzir uma árvore explícita: `docs/product/`, `docs/architecture/`, `docs/security/`, `docs/ops/`, `docs/compliance/`, `tests/golden/`, `docs/traceability/`. Criar `README.md` com mapa do repo, ordem de leitura, estado atual e "não iniciar produto antes de X".

## G. Papéis, responsabilidades e limites

**Status:** parcialmente presente.

O repo é claro sobre o PM não técnico: ele descreve produto, aceita/recusa recomendações, testa visualmente e aprova deploy, mas não faz review de código nem escolhe trade-offs arquiteturais sem recomendação (`CLAUDE.md:63-71`). Também define roles de sub-agents (`CLAUDE.md:160-168`) e R11 como substituição da review humana por verifier + reviewer (`docs/constitution.md:163-178`). A decisão do PM aceita pausa dura em categorias críticas e proíbe override nessas categorias (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:24-31`).

Ainda falta um RACI operacional: o que o PM decide sozinho, o que o agente recomenda, o que exige consultor externo, o que exige dois agentes concordando, e onde existe pausa dura sem opção de override. A política R6/R7 de pausa dura ainda está planejada no Bloco 4 (`docs/audits/progress/meta-audit-tracker.md:80-85`) e não existe em `docs/policies/`. Também não há definição de "consultor técnico externo" para decisões de stack, tenancy, LGPD, ICP-Brasil e operação em VPS.

**Recomendação específica:** criar `docs/governance/roles-and-escalation.md` com matriz de decisão por categoria: produto, UX, stack, dados, tenancy, segurança, LGPD, fiscal, metrologia, ICP, REP-P, deploy e incidente. Cada linha deve ter dono, aprovadores, evidência exigida e se override do PM é permitido.

## H. Tradução técnico-para-produto

**Status:** parcialmente presente; mecanismo atual é placeholder.

R12 define vocabulário permitido e proibido (`docs/constitution.md:181-200`), e as skills prometem tradução para PM (`.claude/skills/explain-slice.md:1-11`; `.claude/skills/decide-stack.md:1-11`). A skill de stack exige `docs/mvp-scope.md` como precondição (`.claude/skills/decide-stack.md:18-21`) e orienta traduzir trade-offs em termos de tempo, custo, manutenção e impacto no usuário (`.claude/skills/decide-stack.md:86-92`).

Na prática, os scripts ainda não são tradutores. `scripts/explain-slice.sh` declara que é stub e que o agente principal preencherá placeholders (`scripts/explain-slice.sh:1-6`, `scripts/explain-slice.sh:57-97`). `scripts/decide-stack.sh` também é stub e gera um esqueleto com campos "a ser preenchido" (`scripts/decide-stack.sh:1-7`, `scripts/decide-stack.sh:61-81`). O backlog reconhece B-010 como aberto (`docs/guide-backlog.md:36-40`). Há uma forma de pergunta única no plano (`docs/audits/meta-audit-2026-04-10-action-plan.md:279-288`), mas ainda não está implementada.

**Recomendação específica:** antes de expor qualquer rejeição técnica ao PM, implementar `scripts/check-r12-vocabulary.sh`, `docs/templates/pm-decision.md` e `docs/policies/translation-fallback.md`. O fallback deve ser "pausar e chamar consultor" quando a tradução for impossível, não "mostrar JSON ao PM".

## I. Governança financeira e sustentabilidade

**Status:** ausente.

Há estimativas de esforço para consultores nas decisões do PM: metrologia 20-40h (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:18`) e fiscal 30 casos com trilha fiscal aprovada (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:22`). A RFP fiscal menciona revalidação anual opcional (`docs/compliance/rfp-consultor-fiscal.md:163-165`). Também há budgets de tokens por sub-agent em `CLAUDE.md:160-168` e telemetria estrutural no backlog (`docs/guide-backlog.md:78-85`).

Isso não chega a governança financeira. Não há custo mensal de VPS Hostinger, limite de RAM/CPU, custo de banco/backup/storage, custo de e-mail/WhatsApp/PDF/assinatura, custo de tokens por slice, custo total de consultoria, custo mensal do PM, runway e critério de inviabilidade econômica da stack. Esse gap afeta diretamente ADR-0001: uma stack pode ser tecnicamente ótima e operacionalmente impossível em VPS barata.

**Recomendação específica:** criar `docs/finance/operating-budget.md` antes de ADR-0001. Campos mínimos: teto mensal de infra, teto de tokens por bloco/slice, teto de consultoria, custo do PM, margem esperada, preço alvo do produto, número mínimo de clientes para break-even e restrições que a stack deve respeitar.

## J. Plano de evolução do próprio harness

**Status:** parcialmente presente.

Há um processo de alteração da constitution por ADR + retrospectiva (`docs/constitution.md:205-218`), backlog de melhorias do harness (`docs/guide-backlog.md:1-5`), tracker de blocos (`docs/audits/progress/meta-audit-tracker.md:1-15`) e plano de re-auditoria em sessão nova (`docs/audits/meta-audit-2026-04-10-action-plan.md:418-447`). Isso é uma base boa.

O ponto frágil é que o processo de amendment menciona explicitamente R1-R10 (`docs/constitution.md:206`), mas a constitution já tem R11 e R12 (`docs/constitution.md:3-8`). A regra geral no topo diz alteração apenas via ADR + retrospectiva (`docs/constitution.md:3-5`), então a intenção é clara, mas a redação operacional está inconsistente. Além disso, `docs/retrospectives/` existe, mas não contém retrospectivas; e não há cadência obrigatória para revisar regras R1-R12, revogar regra obsoleta ou medir custo do próprio harness.

**Recomendação específica:** criar `docs/governance/harness-evolution.md` e corrigir `docs/constitution.md §5` para "P1-P9 ou R1-R12". Definir cadence mensal/trimestral, critérios para propor/revogar regra, quem aprova, como medir custo/benefício, e exigir retrospectiva após cada bloco e após incidentes severos.

## K. Outros pontos relevantes

1. **A decisão de stack precisa de parecer técnico externo ou pelo menos review externo assinado.** O PM não tem como auditar premissas de tenancy, segurança, PDF/A, ICP, fiscal e operação em VPS. O incidente PR #1 já identificou a causa raiz: o projeto assumia humano técnico, mas a realidade é PM não desenvolvedor (`docs/incidents/pr-1-admin-merge.md:21-31`).

2. **A cadeia de V1 está quebrada por ausência do artefato central.** `docs/reference/v1-post-mortem.md:81-83` diz que escopo funcional e multi-tenancy do V1 seriam insumos para V2, mas o arquivo obrigatório `docs/reference/ideia-v1.md` não existe. Isso cria risco de agentes reconstituírem produto por memória/contexto em vez de artefato versionado.

3. **A estratégia "VPS single box" precisa virar restrição testável.** Hoje é contexto do prompt, não artefato de repo. Precisa entrar no baseline de arquitetura, no orçamento e no plano de DR.

4. **Falta plano de dados sintéticos e migração.** Para SaaS regulado, agentes precisam de fixtures realistas sem vazar dados reais: laboratório, certificado, padrão, instrumento, nota, jornada, usuário. Não há `docs/data/` nem política de fixtures.

5. **Contratos e procurement de terceiros ainda não aparecem.** Fiscal pode depender de provedor NFS-e/NF-e; ICP pode depender de certificado/serviço de assinatura; WhatsApp/e-mail podem depender de fornecedores. Falta matriz de fornecedores, riscos e contratos.

## L. 5 maiores ameaças à entrega do MVP em produção

### Ameaça #1: Stack decidida antes do produto estar definido
**Probabilidade:** alta
**Impacto:** alto
**Descrição:** o agente gera ADR-0001 com recomendações tecnicamente plausíveis, mas sem PRD/MVP, personas, jornadas, NFRs e orçamento. A stack fica otimizada para um produto imaginado, não para o Kalibrium real.
**Por que o plano atual não cobre:** o plano manda rodar `/decide-stack`, mas `docs/mvp-scope.md` não existe e `scripts/decide-stack.sh` aborta sem ele (`scripts/decide-stack.sh:41-46`). `docs/reference/ideia-v1.md` também está ausente.
**Mitigação proposta:** tornar `docs/mvp-scope.md` e `docs/architecture/foundation-constraints.md` bloqueantes antes de qualquer ADR-0001 accepted.

### Ameaça #2: Compliance do MVP depende de consultores ainda não contratados
**Probabilidade:** alta
**Impacto:** alto
**Descrição:** metrologia e fiscal entram no MVP, mas os golden tests dependem de consultores externos, CSVs, metodologia, handoff e políticas ainda pendentes. O produto pode ficar parado ou, pior, avançar sem oráculo regulado.
**Por que o plano atual não cobre:** a decisão do PM aprovou consultores (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:18-22`), mas o tracker marca M1-M6 e F1-F5 como não iniciados (`docs/audits/progress/meta-audit-tracker.md:128-142`).
**Mitigação proposta:** abrir `docs/compliance/procurement-tracker.md` com status, responsável, prazo, risco de atraso e decisão de fallback "módulo fora do MVP se consultor não entregar até data X".

### Ameaça #3: Tradução PM vira teatro em decisões difíceis
**Probabilidade:** média
**Impacto:** alto
**Descrição:** quando verifier/reviewer rejeitarem por arquitetura, segurança, cálculo ou compliance, o PM receberá template com placeholders, não uma decisão de produto pronta. A fadiga pode levar a "aprovar para seguir".
**Por que o plano atual não cobre:** R12 existe, mas `scripts/explain-slice.sh` é stub (`scripts/explain-slice.sh:1-6`) e B-010 está aberto (`docs/guide-backlog.md:36-40`). A pausa dura está aceita, mas ainda não implementada (`docs/audits/progress/meta-audit-tracker.md:80-85`).
**Mitigação proposta:** implementar Bloco 4 antes de qualquer slice de produto e criar `docs/policies/r6-r7-policy.md` com categorias sem override.

### Ameaça #4: Produto chega a "verde" sem estar operável em produção
**Probabilidade:** média
**Impacto:** alto
**Descrição:** agentes entregam slices com testes, mas não há deploy, rollback, observabilidade, suporte, backup/DR, SLO ou plano de atualização legal. O MVP funciona localmente e falha como SaaS.
**Por que o plano atual não cobre:** Bloco 5 cobre CI/auto-approve (`docs/audits/meta-audit-2026-04-10-action-plan.md:337-368`), mas não cobre operação de VPS, alertas, suporte e rollback de produto.
**Mitigação proposta:** criar `docs/ops/production-readiness.md` com checklist bloqueante para qualquer deploy real, incluindo DR, observabilidade, restore testado e suporte dos primeiros clientes.

### Ameaça #5: Modelo financeiro inviabiliza a arquitetura escolhida
**Probabilidade:** média
**Impacto:** médio/alto
**Descrição:** stack, serviços externos, tokens e consultoria excedem o orçamento real de um PM individual. O projeto fica tecnicamente correto e economicamente inviável.
**Por que o plano atual não cobre:** há budgets de tokens por sub-agent (`CLAUDE.md:160-168`) e estimativas de horas de consultoria (`docs/decisions/pm-decision-meta-audit-2026-04-10.md:18`), mas não existe orçamento mensal total nem teto por decisão técnica.
**Mitigação proposta:** criar `docs/finance/operating-budget.md` e exigir que ADR-0001 respeite seus limites.

## M. Veredito binário

- **O projeto está pronto para avançar para o "Bloco 2" (decisão de stack) no estado atual?** não
- **Quais são as mudanças bloqueantes (se houver) antes de decidir stack?**
1. Criar `docs/mvp-scope.md` com escopo MVP, fora-do-MVP, jornadas, personas e NFRs mínimos.
2. Repor `docs/reference/ideia-v1.md` ou remover as dependências a ele nas skills/RFPs.
3. Criar baseline arquitetural pré-stack: tenancy, cliente web/PWA, deployment em VPS, ambientes, auth, dados e integrações externas.
4. Criar envelope financeiro para stack: infra, tokens, consultores, serviços externos e runway.
5. Registrar decisão de escopo para REP-P, ICP-Brasil e LGPD: MVP, fora do MVP ou bloqueado até consultor/política.
6. Exigir parecer técnico externo ou revisão independente do ADR-0001 antes de `accepted`, dado que o PM não é técnico.
- **Quais artefatos de fundação de produto deveriam existir antes de qualquer commit que toque código-fonte de produto?**
1. `docs/mvp-scope.md`
2. `docs/product/personas.md`
3. `docs/product/journeys.md`
4. `docs/product/non-functional-requirements.md`
5. `docs/architecture/foundation-constraints.md`
6. ADR de stack aceito, com `docs/stack.json`
7. `docs/security/product-threat-model.md`
8. `docs/compliance/lgpd-data-map.md`
9. `docs/compliance/metrology-policy.md` e/ou decisão formal de excluir metrologia até golden tests
10. `docs/compliance/fiscal-policy.md` e/ou decisão formal de excluir emissão fiscal até golden tests
11. `docs/ops/production-readiness.md`
12. `docs/finance/operating-budget.md`
13. `docs/governance/roles-and-escalation.md`
14. `README.md`

## N. 10 sugestões acionáveis em ordem de impacto

**[esforço: baixo] Criar `docs/mvp-scope.md` mínimo**
- **Por quê:** é precondição real de `scripts/decide-stack.sh` e hoje falta o artefato mais importante de produto.
- **Como:** usar uma página com problema, laboratório-tipo, 3 jornadas, funcionalidades MVP, fora-do-MVP, NFRs e restrições de orçamento/VPS. Linkar em ADR-0001.

**[esforço: baixo] Resolver a ausência de `docs/reference/ideia-v1.md`**
- **Por quê:** o prompt, RFPs e post-mortem dependem desse arquivo; a ausência torna a "visão congelada" inexistente.
- **Como:** copiar o `ideia.md` original para `docs/reference/ideia-v1.md` com header R7 não-instrucional, ou remover a referência de `scripts/decide-stack.sh` e das RFPs se a visão não deve mais ser usada.

**[esforço: médio] Criar baseline arquitetural antes de ADR-0001**
- **Por quê:** stack sem tenancy, auth, deployment e dados iniciais é decisão prematura.
- **Como:** criar `docs/architecture/foundation-constraints.md` e exigir link dele em ADR-0001.

**[esforço: médio] Criar pacote inicial de segurança/LGPD**
- **Por quê:** glossário LGPD não substitui base legal, DPIA, retenção, segredos e threat model.
- **Como:** criar `docs/security/product-threat-model.md`, `docs/compliance/lgpd-data-map.md`, `docs/security/secrets-policy.md` e `docs/ops/backup-dr.md`.

**[esforço: médio] Criar plano operacional de produção**
- **Por quê:** CI não é operação de SaaS.
- **Como:** criar `docs/ops/deploy-rollback.md`, `docs/ops/observability.md`, `docs/ops/customer-support.md` e `docs/compliance/law-watch.md`.

**[esforço: médio] Transformar RFPs em trilha de procurement rastreável**
- **Por quê:** consultores são caminho crítico do MVP regulado.
- **Como:** criar `docs/compliance/procurement-tracker.md` com status M1-M6/F1-F5, responsável, data alvo, fallback e risco.

**[esforço: médio] Criar matriz de rastreabilidade regulatória**
- **Por quê:** "teste passa" não prova "norma atendida".
- **Como:** criar `docs/compliance/traceability-template.md` com colunas `norma`, `seção`, `data`, `requisito`, `teste`, `slice`, `consultor`, `revalidação`.

**[esforço: baixo] Formalizar papéis e pausas duras**
- **Por quê:** R11/R12 reduzem risco, mas não dizem quem decide cada classe de problema.
- **Como:** criar `docs/governance/roles-and-escalation.md` e `docs/policies/r6-r7-policy.md`.

**[esforço: baixo] Criar orçamento operacional**
- **Por quê:** orçamento afeta stack, CI, observabilidade, terceiros e ritmo de agentes.
- **Como:** criar `docs/finance/operating-budget.md` e tornar a seção de custo obrigatória no ADR-0001.

**[esforço: baixo] Corrigir o processo de evolução da constitution para R1-R12**
- **Por quê:** a constitution menciona R11/R12, mas o amendment operacional cita R1-R10.
- **Como:** alterar `docs/constitution.md §5` para R1-R12 e criar `docs/governance/harness-evolution.md` com cadence e critérios de revogação/adicionamento de regras.

## O. Comentário livre (opcional, máximo 300 palavras)

O achado principal não é "o harness está ruim". Pelo contrário: para um recomeço, o repositório já tem uma cultura de auditoria rara. O problema é que o harness amadureceu antes do produto. Hoje existe mais material sobre como impedir o agente de se autoenganar do que sobre o que o Kalibrium V2 deve entregar, para quem, em qual sequência, com que restrições econômicas e com que responsabilidade regulatória.

Minha recomendação é não transformar o Bloco 2 em "decidir framework". Transforme-o em "decidir o produto mínimo que torna a escolha de framework responsável". A decisão de stack deve ser consequência de MVP, tenancy, compliance, orçamento e operação em VPS, não o primeiro ato técnico do produto. Se ADR-0001 sair antes disso, o projeto estará repetindo o erro do V1 em forma mais organizada: formalizar premissas ausentes num documento bonito.
