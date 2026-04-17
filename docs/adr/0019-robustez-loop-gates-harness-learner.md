# ADR-0019 — Robustez do loop de gates e do harness-learner (meta-audit, fixer scope e revisor cruzado)

**Data:** 2026-04-16
**Status:** accepted (com ajuste na Mudança 3)
**Decisor:** PM (aceito 2026-04-16, sessão de auditoria de gaps)
**Origem:** auditoria de gaps de fluxo 2026-04-16 — gaps #1, #5, #6 identificados em contexto isolado. Padrão detectado: o próprio mecanismo de auto-melhoria do harness e o loop de correção de gates têm pontos únicos de falha ou auditoria circular.

## Ajuste aprovado pelo PM (2026-04-16)

A **Mudança 3 (detecção de escopo do fixer)** será implementada em **duas camadas**:

- **Camada 1 (imediata):** fixer é obrigado a **declarar explicitamente** em `docs/governance/fix-scope-expansion-NNN-<gate>.md` quando tocar arquivos fora do escopo do finding, com justificativa. Sem re-run automático de gates anteriores. Auditável em retrospectiva.
- **Camada 2 (condicional, após 3 slices rodados com Camada 1):** se a declaração manual se mostrar insuficiente (fixer "esquecer" de declarar, detectado via retrospectiva), ativar re-run automático descrito na Mudança 3 original.

Razão: evitar falsos positivos em refactors legítimos (imports reorganizados, lockfiles, formatação) que causariam loops longos desnecessários. Começar pela proteção mais leve e escalar se necessário.

---

## Contexto

Três gaps foram identificados no loop de gates e no mecanismo de evolução do harness:

1. **Gap #1 (S1) — Harness se audita sozinho**: `governance (harness-learner)` propõe mudanças no harness (regras, hooks, skills) e o revisor declarado é `governance (guide-audit)` — mesmo agente, família idêntica. R16 permite até 3 mudanças incrementais por ciclo retrospectivo; R11 exige dual-verifier por princípio. A configuração atual viola o espírito de R11 no ponto mais sensível do sistema: o harness que governa tudo.

2. **Gap #5 (S2) — Master-audit não tem meta-revisor**: `governance (master-audit)` roda dual-LLM (Claude Opus + GPT-5) e emite verdict final. `/merge-slice` valida apenas schema/verdict do JSON, não checa se o master-audit **realmente leu** os outputs de todos os gates anteriores ou se a reconciliação foi conduzida corretamente. Dual-LLM mitiga viés mas não audita integridade do próprio master-audit.

3. **Gap #6 (S2) — Correções do fixer não são re-revisadas estruturalmente**: no loop de Fase E, quando `builder (fixer)` corrige findings de um gate (ex: `security-gate`), o orquestrador re-roda apenas o gate que rejeitou. Se o fixer tocou arquivos fora do escopo do finding (refactor colateral, alteração de assinatura de método), um gate anterior que já aprovou (ex: `review`) pode ter sua aprovação invalidada sem ninguém verificar.

Padrão comum: todos os 3 gaps estão em **loops** ou pontos de **convergência final** onde erro é caro (harness degradado afeta todos os projetos futuros; master-audit defeituoso deixa bug ir pra produção; fixer fora de escopo corrompe aprovações já dadas).

## Opções consideradas

### Opção A — Resolver só o S1 (gap #1), deixar os S2 em aberto

- **Descrição:** adicionar revisor externo ao harness-learner (ex: `architecture-expert` ou PM via R12) antes de qualquer mudança no harness ser committada.
- **Prós:** fecha o mais crítico; mudança pequena; risco operacional baixo.
- **Contras:** gaps #5 e #6 continuam como vulnerabilidades silenciosas; ADRs futuros para fechar o resto.
- **Custo de reverter:** baixo.

### Opção B — Resolver os 3 gaps em um ADR único

- **Descrição:** 3 mudanças coordenadas:
  1. Harness-learner ganha revisor externo obrigatório (`architecture-expert` + confirmação PM via R12) antes de commit; pre-commit-gate bloqueia se faltar assinatura cruzada
  2. Master-audit ganha validação de citações cruzadas (cada gate anterior referenciado no bloco `evidence`); `merge-slice.sh` bloqueia se faltar referência
  3. Fixer ganha detecção de escopo: se diff tocar arquivo fora do escopo do finding, orchestrator dispara re-run de gates anteriores (não só do rejeitado)
- **Prós:** fecha zona inteira de risco; coerência conceitual (todos os 3 são "robustez de loops críticos"); menos ADRs futuros.
- **Contras:** mais arquivos afetados; complexidade operacional aumenta (detecção de escopo de fixer exige lógica nova).
- **Custo de reverter:** médio.

### Opção C — Resolver #1 e #6 (loops), deixar #5 (master-audit) para depois

- **Descrição:** fechar os dois loops que afetam qualidade de cada slice, deixar gap #5 (meta-audit do master-audit) para ADR futuro.
- **Prós:** mudança focada; mantém master-audit como está (já tem dual-LLM — mitigação parcial).
- **Contras:** dual-LLM não audita se master-audit **executou** corretamente — só se dois LLMs chegam ao mesmo verdict. Um master-audit que "pulou" metade dos gates pode gerar verdict approved defeituoso.
- **Custo de reverter:** baixo.

## Decisão

**Opção B — 3 mudanças coordenadas.**

### Justificativa

- Gap #1 é S1 crítico: harness degradado afeta **todos** os slices e projetos futuros. Gap #5 e #6 são S2 mas estão em pontos de convergência final — erro ali vai pra produção. Tratar juntos evita deixar vulnerabilidade silenciosa enquanto fecha a visível.
- Os 3 gaps compartilham o tema "robustez de loops críticos". Resolver em ADR único preserva coerência conceitual.
- Custo operacional adicional é aceitável: harness-learner com revisor externo roda no máximo 1x por épico (R16 — 3 mudanças/ciclo); validação de citações do master-audit é script leve; detecção de escopo de fixer é 1 diff analysis (`git diff --name-only`).
- R11 (dual-verifier) é princípio do projeto. Aplicá-lo ao próprio mecanismo de auto-melhoria do harness é consistente, não excesso.

### Detalhe das 3 mudanças

**Mudança 1 — Revisor externo obrigatório para harness-learner (fecha gap #1)**
- `governance (harness-learner)` gera proposta de mudança em `docs/governance/harness-learner-ENN.md` (como hoje)
- Novo gate: `architecture-expert` em modo `harness-review` (instância isolada) audita a proposta
  - Valida que mudança não revoga/afrouxa P1-P9/R1-R14 (R16 literal)
  - Valida que mudança não cria fragilidade (ex: remove validação)
  - Valida que mudança é incremental, não disruptiva
  - Emite `docs/governance/harness-learner-review-ENN.json` com verdict
- **Passo adicional R12 obrigatório**: PM recebe recomendação traduzida via `/explain-harness-change ENN` e confirma explicitamente antes de commit
- `pre-commit-gate` bloqueia commit em `.claude/`, `scripts/hooks/`, `docs/protocol/`, `CLAUDE.md`, `docs/constitution.md` se:
  - Commit tem prefixo `chore(harness)` ou similar (detecção via mensagem ou arquivos tocados)
  - E NÃO existe `docs/governance/harness-learner-review-ENN.json` com verdict approved
  - E NÃO existe assinatura PM em `docs/governance/harness-learner-pm-approval-ENN.md`
- Hook respeita arquivos já selados (settings-lock, hooks-lock) — só adiciona camada extra para mudanças via harness-learner

**Mudança 2 — Meta-audit do master-audit via citações cruzadas (fecha gap #5)**
- Schema `docs/protocol/schemas/gate-output.schema.json` já exige bloco `evidence`
- Nova regra (hook em `merge-slice.sh`): master-audit deve citar **cada gate anterior** por nome canônico no `evidence`:
  - Para cada gate obrigatório (verify, review, security-gate, audit-tests, functional-gate): referência a `specs/NNN/<gate>.json` presente em `evidence.referenced_artifacts[]`
  - Para cada gate condicional ativo: mesma regra
  - Hash ou modified_at de cada referência armazenado em `evidence.referenced_artifacts[].integrity` (garante que master-audit leu a versão atual, não uma antiga)
- `merge-slice.sh` valida:
  - Todos os gates aplicáveis ao slice (conforme triggers condicionais) estão em `evidence.referenced_artifacts[]` do master-audit
  - Hashes batem com arquivos atuais
- Se falha: merge bloqueado, master-audit precisa ser re-rodado

**Mudança 3 — Detecção de escopo do fixer + re-run estrutural (fecha gap #6)**
- Orquestrador rastreia escopo declarado em cada finding (campo `affected_files` no schema existente)
- Ao invocar `builder (fixer)`, orchestrator registra `expected_scope` do fix (união dos `affected_files` dos findings)
- Após fixer commitar, orchestrator compara `git diff --name-only` do commit de fix contra `expected_scope`:
  - Se `actual_scope ⊆ expected_scope`: re-roda apenas o gate que rejeitou (comportamento atual)
  - Se `actual_scope ⊄ expected_scope` (fixer tocou arquivos fora do escopo): dispara re-run obrigatório de TODOS os gates anteriores que já aprovaram e que têm file patterns sobrepostos ao escopo excedido
- Novo campo em `.claude/telemetry/slice-NNN.jsonl`: `fix_scope_exceeded` com `expected_scope`, `actual_scope`, `gates_to_rerun`
- Regra do `builder (fixer)`: se precisa tocar fora do escopo, declara explicitamente em `docs/governance/fix-scope-expansion-NNN-<gate>.md` com justificativa antes de commitar. Justificativa fica auditável.

**Reversibilidade:** média. Cada mudança é independente; remover desativa a respectiva salvaguarda sem afetar as outras.

## Consequências

### Positivas

- Harness fica protegido contra auto-degradação — R11 aplicado ao próprio mecanismo de evolução.
- Master-audit deixa de ser caixa-preta final — sua integridade é verificável mecanicamente.
- Fixer deixa de poder quebrar aprovações já dadas silenciosamente.
- Cada um dos 3 pontos críticos ganha "network" em vez de "ponto único de falha".
- Telemetria de fixer ganha dimensão (escopo excedido) — sinal para retrospectivas.

### Negativas

- Harness-learner roda mais lento (revisão técnica + aprovação PM) — estimativa +10-20 min por mudança.
- Master-audit precisa preencher bloco `evidence.referenced_artifacts` com rigor — se o agente esquecer, merge-slice bloqueia (pode causar confusão na primeira vez).
- Fixer com re-run estrutural pode gerar loops mais longos quando fix legítimo precisa tocar fora do escopo (ex: refactor colateral obrigatório).
- +1 modo em `architecture-expert` (harness-review) — total 7 modos nesse agente.

### Riscos

- **Risco R1:** revisor externo do harness-learner ser sempre `architecture-expert`, criando dependência circular se o ADR mudar algo sobre `architecture-expert`. Mitigação: para mudanças que tocam `.claude/agents/architecture-expert.md`, revisor externo deve ser `security-expert` ou `qa-expert` em instância isolada.
- **Risco R2:** validação de citações cruzadas do master-audit falhar em slices antigos (mergeados antes deste ADR). Mitigação: regra vale apenas para slices iniciados após aceite deste ADR; slices anteriores têm exceção documentada.
- **Risco R3:** detecção de escopo excedido de fixer gerar falsos positivos (ex: imports auto-reorganizados pelo IDE). Mitigação: whitelist de arquivos "infrastructure" (ex: lockfiles, formatação) que não dispara re-run; auditável em `docs/governance/fix-scope-whitelist.md`.
- **Risco R4:** PM aprovar mudança de harness sem entender (R12 mal traduzida). Mitigação: `/explain-harness-change ENN` exige templates específicos com 3 seções: "o que muda", "por que agora", "o que pode dar errado". PM só aprova depois das 3.

### Impacto em outros artefatos

- **Hooks afetados:** `pre-commit-gate.sh` (nova camada para harness-learner), `merge-slice.sh` (validação de citações), novo helper `scripts/check-fix-scope.sh`.
- **Sub-agents afetados:** `architecture-expert.md` (novo modo harness-review, total 7 modos), `governance.md` (harness-learner emite proposta mas não aprova sozinho), `builder.md` (fixer declara scope expansion explícita).
- **Skills afetadas:** nova `/explain-harness-change ENN` (R12 para harness).
- **Protocolo afetado:** `docs/protocol/00-protocolo-operacional.md` (harness-review no mapa canônico), `docs/protocol/03-contrato-artefatos.md` (bloco `evidence.referenced_artifacts` obrigatório para master-audit), `docs/protocol/04-criterios-gate.md` (gate harness-review + regra de re-run estrutural), `docs/protocol/07-politica-excecoes.md` (exceção para fix-scope-expansion).
- **CLAUDE.md:** §6 Fase F (harness-learner com revisor externo), §9 (procedimento de relock inclui referência ao harness-review).
- **ADRs relacionados:** ADR-0012 (autonomia dual-LLM — este ADR estende dual-verifier ao harness e ao meta-audit), ADR-0010 (R6 gate threshold — este ADR não muda threshold mas adiciona camada), ADR-0014 (bypass policy — exceções de fix-scope seguem essa política).

## Referências

- Auditoria de gaps 2026-04-16 (sessão atual, sub-agent general-purpose em contexto isolado)
- `docs/constitution.md` R16 — harness-learner com auto-aplicação limitada (limite que este ADR reforça)
- `docs/protocol/04-criterios-gate.md` §9.4 — reconciliação dual-LLM (master-audit ganha camada adicional de validação de integridade)
- `docs/protocol/schemas/gate-output.schema.json` — bloco `evidence` já existente, este ADR formaliza uso em master-audit

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas (A, B, C)
- [x] Decisão justificada com razão objetiva (pontos de convergência final + R11 aplicado ao próprio harness)
- [x] Reversibilidade declarada (média)
- [x] Consequências negativas listadas explicitamente
- [x] Não contradiz ADR anterior (reforça ADR-0012 e R16)
- [x] Impacto em hooks/agents/constitution/protocolo endereçado
- [x] PM aprovou (2026-04-16 — aceito com ajuste: Mudança 3 em duas camadas, começando só com declaração manual)
- [ ] Circular dependency resolvida: revisor externo do harness-learner varia conforme arquivo tocado (a endereçar durante implementação da Mudança 1)
