# ADR-0018 — Auditoria independente nas fases iniciais (PRD, ADRs, UX, api-contracts)

**Data:** 2026-04-16
**Status:** accepted (escopo prospectivo — não retroativo)
**Decisor:** PM (aceito 2026-04-16, sessão de auditoria de gaps)
**Origem:** auditoria de gaps de fluxo 2026-04-16 — gaps #2, #3, #4, #8 identificados em contexto isolado. Padrão detectado: fases A e B têm auditoria fraca ou circular, enquanto Fase E tem 9+ gates independentes.

## Escopo aprovado pelo PM (2026-04-16)

**Aplicação:** prospectiva apenas.

- **Aplica-se:** a partir do próximo ADR escrito (ADR-0020 em diante), a partir da próxima sessão de descoberta de projeto novo, e a partir da próxima atualização de PRD/jornadas/personas.
- **NÃO se aplica retroativamente:** PRD do Kalibrium já está congelado; os 16 ADRs já aceitos (0001-0016) não serão re-auditados.
- **Razão:** custo de auditar retroativamente 16 ADRs e PRD congelado seria alto (dias de trabalho) com valor limitado (projeto já em Fase D, múltiplos slices merged). Investimento em auditoria rigorosa vale para decisões futuras, não para fechar dívida de decisões aceitas.
- **Implementação:** este ADR fica em `accepted pending implementation` — novos modos e skills serão criados, mas só serão invocados para artefatos produzidos a partir da data deste ADR.

---

## Contexto

O pipeline Kalibrium é robusto na Fase E (pipeline de gates após implementação), mas as fases iniciais (A — descoberta, B — estratégia técnica) dependem de auditores únicos ou circulares. Quatro gaps foram identificados:

1. **Gap #4 — PRD/NFRs/personas sem auditor antes de `/freeze-prd`**: reviewer declarado é `qa-expert (audit-planning)`, mas audit-planning só está formalizado depois da decomposição de épicos (Fase C). `/freeze-prd` valida completude, não rigor. PRD ambíguo envenena a escolha de stack (ADR-0001) e todos os épicos subsequentes.

2. **Gap #2 — ADRs entram em `/freeze-architecture` sem auditor técnico profundo**: reviewer formal é `qa-expert (audit-planning)` — QA não avalia trade-offs arquiteturais com profundidade (multi-tenancy, acoplamento, custo). `/freeze-architecture` checa existência e consistência superficial, não rigor. ADR ruim (como ocorreria sem ADR-0016) contamina todos os épicos.

3. **Gap #3 — Artefatos UX sem gate independente antes da Fase D**: `ux-designer (design)` produz wireframes, flows, screen inventory e não há reviewer formal listado. O `ux-gate` existe mas roda **depois** (na Fase E, via `product-expert functional-gate`). Wireframes inconsistentes com personas/jornadas geram retrabalho de implementação descoberto tarde.

4. **Gap #8 — `api-contracts.md` com reviewer circular**: o próprio `architecture-expert` em modo `plan` revisa o api-contracts que ele mesmo produziu em modo `design`. Security-expert só valida via `security-gate` na Fase E — já com código. Contratos de API ruins propagam por todos os épicos que consomem aquela API.

Padrão comum: os 4 gaps estão em Fase A ou B, todos envolvem artefatos que viram input para **todos** os épicos/slices futuros. Custo de descobrir o erro tarde é altíssimo (afeta escopo inteiro, não slice isolado).

## Opções consideradas

### Opção A — Resolver só os S1 (gaps #2 e #4), deixar S2 e S3 em aberto

- **Descrição:** adicionar auditor independente só para PRD (gap #4) e ADRs (gap #2).
- **Prós:** mudança menor; risco operacional baixo; fecha os dois críticos.
- **Contras:** UX e api-contracts continuam com reviewer fraco; retrabalho a médio prazo garantido; gap #3 e #8 voltam como ADR futuro.
- **Custo de reverter:** baixo.

### Opção B — Resolver os 4 gaps em um ADR único (pacote de auditoria de fases iniciais)

- **Descrição:** 4 mudanças coordenadas:
  1. Novo modo `pre-freeze-audit` em `qa-expert` OU agente dedicado que audita PRD/NFRs antes de `/freeze-prd`
  2. Novo modo `adr-review` em `architecture-expert` (instância B isolada) que audita cada ADR antes de `/freeze-architecture`
  3. Novo modo `ux-gate-early` (ou dedicar `ux-designer` a audit-design em instância B isolada) que valida wireframes/flows antes de `/start-story`
  4. Novo modo `api-contract-review` em `architecture-expert` (instância B isolada) + pre-review obrigatório do `security-expert (threat-model)` sobre api-contracts antes de `/freeze-architecture`
- **Prós:** fecha zona inteira de risco; preserva coerência conceitual (todos os 4 são "auditoria de fase inicial"); menos ADRs futuros.
- **Contras:** mais agentes/modos criados; mais arquivos afetados; tempo total das fases A-B aumenta (estimativa +20-40 min no ciclo inteiro de discovery).
- **Custo de reverter:** médio-alto.

### Opção C — Reutilizar agentes existentes, só exigir instância isolada (sem criar modos novos)

- **Descrição:** em vez de novos modos, exigir que os agentes existentes rodem auditoria em instância isolada (R3/R11):
  - PRD: `product-expert (decompose)` em instância B audita output de `product-expert (discovery)` em instância A
  - ADRs: `architecture-expert (plan)` em instância B audita output de `architecture-expert (design)` em instância A
  - UX: `ux-designer (ux-gate)` em instância B audita `ux-designer (design)` em instância A
  - api-contracts: `architecture-expert (code-review)` em instância B + `security-expert (threat-model)` em instância isolada
- **Prós:** não cria novos modos; aproveita R11 (dual-verifier) já existente; implementação mais leve.
- **Contras:** modos atuais podem não ter critérios de auditoria explícitos no prompt (ex: `decompose` é para decompor, não para auditar PRD); forçar auditoria via instância sem ajustar critérios produz auditoria rasa.
- **Custo de reverter:** baixo.

## Decisão

**Opção B — 4 mudanças coordenadas com modos dedicados.**

### Justificativa

- Os 4 gaps afetam artefatos que contaminam **todo** o projeto quando errados — custo de erro é multiplicativo, investimento em auditoria robusta se paga rápido.
- Opção C economiza código mas produz auditoria rasa (modo "decompose" não é feito para auditar PRD — tem outro objetivo).
- Opção B preserva o princípio do projeto: "agente especializado por responsabilidade com critérios explícitos" (v3 do harness). Criar modo dedicado é consistente com essa filosofia.
- O custo adicional de tempo (+20-40 min nas fases A-B) é pago uma vez por projeto, não por slice. Dilui-se.
- Gap #2 (ADRs sem auditor técnico) é o mais crítico — esse sozinho já justifica o ADR. Os outros 3 vêm "de brinde" na mesma mudança estrutural.

### Detalhe das 4 mudanças

**Mudança 1 — pre-freeze-audit (fecha gap #4)**
- Novo modo `pre-freeze-audit` em `.claude/agents/qa-expert.md` (ou agente `product-expert` modo `audit-discovery` — definir em review)
- Audita antes de `/freeze-prd`:
  - PRD tem todos os campos obrigatórios preenchidos com rigor (não apenas presença)
  - NFRs têm métricas mensuráveis (não "rápido" mas "<200ms p95")
  - Personas têm contexto de uso, objetivos, dores, tecnologia disponível
  - Jornadas cobrem happy path + cenários degradados
  - Conectividade perfilada (memória do projeto: "Intake deve perguntar conectividade")
- Output: `docs/audits/prd-pre-freeze-audit.json` (schema `gate-output.schema.json`)
- Zero findings S1-S3 para permitir `/freeze-prd`

**Mudança 2 — adr-review (fecha gap #2)**
- Novo modo `adr-review` em `.claude/agents/architecture-expert.md`
- **Critério R11 crucial:** `adr-review` SEMPRE em instância isolada distinta de quem escreveu o ADR (mesmo agente, contextos separados)
- Audita cada ADR antes de `/freeze-architecture`:
  - Pelo menos 2 opções reais (não "straw man")
  - Decisão justificada objetivamente
  - Reversibilidade declarada com realismo
  - Consequências negativas explícitas (não "nenhuma")
  - Não contradiz ADR anterior (ou declara `superseded by`)
  - Impacto em hooks/agents/protocolo mapeado
- Roda em loop: 1 ADR por vez, todos aprovados antes de `/freeze-architecture`
- Output: `docs/audits/adr-NNNN-review.json` por ADR

**Mudança 3 — ux-gate-early (fecha gap #3)**
- Promover o modo `ux-gate` existente do `ux-designer` a gate early-stage
- Roda entre `/decompose-stories ENN` e `/start-story ENN-SNN` (primeira story com UI do épico)
- **Critério R11:** instância B distinta de quem produziu wireframes
- Audita:
  - Wireframes cobrem todas as jornadas das personas relevantes
  - Flows têm happy path + degradações
  - Screen inventory mapeado contra ACs das stories
  - Consistência de padrões (design system) entre telas
  - Acessibilidade básica (contraste, touch targets, keyboard navigation)
- Output: `docs/audits/ux-gate-ENN.json` por épico

**Mudança 4 — api-contract-review + security pre-review (fecha gap #8)**
- Novo modo `api-contract-review` em `.claude/agents/architecture-expert.md` (instância B isolada)
- Adicional: `security-expert (threat-model)` pre-review obrigatório sobre `api-contracts.md` (não espera até a Fase E)
- Audita antes de `/freeze-architecture`:
  - Contratos REST/GraphQL têm versionamento explícito
  - Autenticação e autorização documentadas por endpoint
  - Rate limiting / throttling definidos
  - Tratamento de erros padronizado (códigos HTTP + payload)
  - Entradas externas têm validação documentada (OWASP API Top 10)
  - Dados sensíveis (LGPD) marcados com classificação
- Output: `docs/audits/api-contracts-review.json` + `docs/audits/api-contracts-threat-model.json`

**Reversibilidade:** média. Remover qualquer um dos modos desativa a mudança correspondente sem afetar os outros.

## Consequências

### Positivas

- Fases A e B ganham auditoria equivalente à que Fase E tem em código.
- PRD/NFRs deixam de "passar no automático" no `/freeze-prd`.
- ADRs ganham revisor técnico profundo, não só validador de QA.
- UX deixa de ser "ponto cego" até o código estar pronto.
- api-contracts ganha security review antes de propagar para épicos — LGPD e OWASP entram cedo.

### Negativas

- Tempo total das fases A-B aumenta: +20-40 min no ciclo inteiro (discovery + estratégia técnica).
- 4 novos modos a manter no harness (prompts, critérios, schemas).
- Budget de tokens adicional: cada modo audita em contexto isolado → +~10-20k tokens por discovery/estratégia.
- Risco de auditoria excessiva gerar loops em projeto novo (PRD perfeito na 1ª escrita é raro).

### Riscos

- **Risco R1:** PM percebe a auditoria como "processo demais" para um projeto pequeno. Mitigação: ADR aplica-se a projetos com escopo ≥ 3 épicos (caso Kalibrium tem 25 épicos — aplica). Projetos menores podem ter escopo reduzido via exceção declarada em `/freeze-prd`.
- **Risco R2:** adr-review rejeita ADR porque "só tem 2 opções" quando genuinamente só existem 2. Mitigação: critério é "2 opções reais consideradas", não "3 opções forçadas"; justificativa explícita de por que não há opção C é aceita.
- **Risco R3:** ux-gate-early atrasa primeira story do épico. Mitigação: roda uma única vez por épico (não por story), com paralelização se múltiplas stories compartilham wireframes.
- **Risco R4:** security pre-review sobre api-contracts duplicar trabalho do `security-gate` na Fase E. Mitigação: escopo pre-review é só `api-contracts.md` (contrato); security-gate da Fase E cobre implementação.

### Impacto em outros artefatos

- **Hooks afetados:** `pre-freeze-prd` (novo), `pre-freeze-architecture` (novo), `pre-start-story` (novo para ux-gate-early).
- **Sub-agents afetados:** `qa-expert.md` OU `product-expert.md` (novo modo pre-freeze-audit), `architecture-expert.md` (novos modos adr-review + api-contract-review, total 6 modos), `security-expert.md` (threat-model aplicado pre-freeze-architecture também).
- **Skills afetadas:** nova `/audit-prd`, nova `/review-adr NNNN`, nova `/audit-ux-early ENN`, nova `/review-api-contracts`.
- **Protocolo afetado:** `docs/protocol/00-protocolo-operacional.md` (mapa canônico +4 modos), `docs/protocol/04-criterios-gate.md` (gates #17-#20), `docs/protocol/05-matriz-raci.md` (RACI dos 4 novos gates).
- **CLAUDE.md:** §6 Fase A e B (passos adicionais), §8 agentes (modos atualizados).
- **ADRs relacionados:** ADR-0001 (stack choice — este ADR protege ADRs futuros como 0001), ADR-0012 (autonomia dual-LLM — reforça R11 em fase inicial), ADR-0016 (multi-tenant — ADR-0016 é exemplo de decisão crítica que merecia o adr-review deste ADR).

## Referências

- Auditoria de gaps 2026-04-16 (sessão atual, sub-agent general-purpose em contexto isolado)
- `docs/protocol/05-matriz-raci.md` — RACI por artefato (base para definir quem audita)
- `docs/protocol/schemas/gate-output.schema.json` — schema reusado pelos 4 novos gates

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas (A, B, C)
- [x] Decisão justificada com razão objetiva (custo multiplicativo de erro em fase inicial)
- [x] Reversibilidade declarada (média)
- [x] Consequências negativas listadas explicitamente
- [x] Não contradiz ADR anterior
- [x] Impacto em hooks/agents/constitution/protocolo endereçado
- [x] PM aprovou (2026-04-16 — aceito com escopo prospectivo: aplica a partir de ADR-0020 e próximas descobertas)
- [x] Escopo aplicável definido (prospectivo — não retroativo aos 16 ADRs já aceitos)
