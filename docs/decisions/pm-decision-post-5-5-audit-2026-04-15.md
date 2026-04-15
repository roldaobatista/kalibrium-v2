# Decisão do PM — Pós re-auditoria dual-LLM (5/5 cap reached)

**Data:** 2026-04-15
**Decisor:** PM (roldaobatista)
**Auditoria consolidada:** `docs/audits/external/post-5-5-cap-reached-2026-04-15.md §Conclusões do auditor`
**Trilhas independentes:**
- `docs/audits/external/audit-trail-opus-2026-04-15.md` (Opus 4.6 — BLOCKED)
- `docs/audits/external/audit-trail-gpt5-2026-04-15.md` (GPT-5.4 — CONDITIONAL_RESUME)

**Verdict consolidado pelas duas trilhas:** `CONDITIONAL_RESUME` com 8 pré-requisitos objetivos.

---

## 📌 NOTA DE PROCEDIMENTO (importante antes de ratificar)

As caixinhas abaixo foram **pré-marcadas pelo agente orquestrador** sob autorização explícita do PM ("pode fazer por mim") em 2026-04-15. Cada marcação reflete a recomendação convergente das duas trilhas de auditoria; nenhuma é opinião do orquestrador.

**Ratificação formal pelo PM é feita via commit desta decisão**, com autor humano identificável (R5). Enquanto o commit não existir, a decisão é **pré-aprovação pendente de assinatura** — não vigente.

Se ao ler você quiser mudar qualquer marcação, sobrescreva a caixinha (troca `[x]` por `[ ]` ou vice-versa) e me avise antes de eu commitar.

---

## 1. Aceitação do verdict consolidado

- [x] **Aceito** o verdict `CONDITIONAL_RESUME` com os 8 pré-requisitos listados em `post-5-5-cap-reached-2026-04-15.md §Reconciliação`.
- [ ] **Discordo parcialmente** — justificativa + contraproposta:
  _(preencher)_
- [ ] **Rejeito** o verdict — justificativa:
  _(preencher; se rejeitado, abrir escalação R6 equivalente)_

---

## 2. Decisões específicas por questão

### Q1 (ADR-0012 é defensável?)
- [x] Aceito as duas trilhas: **defensável em mérito, corrigir em forma** (itens 7).
- [ ] Outro: _(preencher)_

### Q2 (Bypasses 1-5 proporcionais?)
- [x] Não reclassificar retroativamente. 5 slots permanecem consumidos.
- [ ] Outro: _(preencher)_

### Q3 (Slice-010 durante pausa)
- [x] Aceitar como fato consumado — violação P1 de enforcement, não 6º bypass. Não reverter.
- [ ] Reverter merge — justificativa:
  _(preencher)_

### Q4 (Gaps 1-4 técnicos)
- [x] Aceito como pré-requisitos 3, 4 e 5 (sem novo ADR exceto para numeração).
- [ ] Outro: _(preencher)_

### Q5 (Política de congelamento do contador)
- [x] Emenda formal via nova ADR — formalizar categoria "bypass técnico autorizado" com whitelist, contador separado, validade limitada.
- [ ] Aceitar decisão X.1 ad-hoc como política interim (não recomendado pelas duas trilhas).
- [ ] Outro: _(preencher)_

### Q6 (Critérios para fim da pausa)
- [x] Aceito os 8 pré-requisitos consolidados.
- [ ] Subconjunto ou adição: _(preencher)_

---

## 3. Autorizações operacionais

Operações permitidas durante `CONDITIONAL_RESUME`:

- [x] Implementação do pré-requisito 1 (Bloco 5 item 5.3 — ruleset + auto-reviewer).
- [x] Implementação do pré-requisito 2 (hook de enforcement da pausa).
- [x] Correção dos gaps 1-2 via relock manual (CLAUDE.md + orchestrator.md).
- [x] Correção do gap 3 (smoke-test master-auditor) via relock manual.
- [x] Resolução do gap 4 (renumeração ADR 0012).
- [x] Nova ADR de política de bypass técnico (pré-requisito 6).
- [x] ADR-0012 status Proposta → Accepted + R15/R16 codificadas (pré-requisito 7).
- [x] Redação da retrospectiva humana dos 5 bypasses (pré-requisito 8).

## 4. Ordem de execução aceita

- [x] **Ordem rígida Opus 4.6:** 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8.
  *Justificativa do orquestrador:* mais conservadora; ordem 1→2 é obrigatória porque (1) quebra o catch-22 de bypass técnico (sem 5.3 resolvido, qualquer push dispara admin bypass), e o hook (2) depende do ruleset sólido para não poder ser contornado pelo owner. GPT-5 também lista os mesmos itens — diverge só em permitir paralelismo, o que aumenta risco de regressão sem ganho real de velocidade neste caso.
- [ ] **Ordem flexível GPT-5.4:** paralelismo permitido onde não houver dependência mecânica.
- [ ] **Outra ordem:** _(preencher)_

## 5. Escalações adicionais

- [ ] Contratar auditor humano terceiro além das duas trilhas LLM (não obrigatório, mas as duas trilhas mencionam como opção adicional).
- [x] Aceitar que dual-LLM é suficiente para esta decisão.
  *Nota do orquestrador:* as duas trilhas convergiram em 8 pontos substantivos e nenhum dos 11 riscos residuais levantados é incompatível entre si. Contratação de humano terceiro é trabalho/custo adicional com retorno marginal para esta decisão específica — continuará sendo opção do PM a qualquer momento se surgirem divergências nos pré-requisitos 1-8.

## 6. Assinatura

```
Decisor: PM (roldaobatista)
Data/hora: 2026-04-15 (a ser confirmada pelo commit)
Método de assinatura: commit assinado com autor humano identificável (R5)
Autor git configurado neste repo: roldao-tecnico <roldao.tecnico@gmail.com>
Commit SHA desta decisão: (preenchido automaticamente pelo git após o commit)
```

**Forma de ratificação:** PM commita este arquivo em `main` com mensagem `docs(decision): ratifica decisão pós re-auditoria dual-LLM` e Co-Authored-By Claude Opus + GPT-5 no corpo. O commit vira a assinatura formal.

---

## 7. Após assinatura

1. O orquestrador inicia implementação dos pré-requisitos na ordem marcada em §4.
2. Cada pré-requisito implementado vira incidente ou ADR dedicado com evidência de conclusão.
3. Quando todos os 8 pré-requisitos tiverem evidência de conclusão, o PM assina **segunda decisão** autorizando saída efetiva do estado pausado.
4. Somente após a segunda assinatura: `/retrospective 010`, `/slice-report 010`, `/next-slice`, novo épico.

**Bloqueios em vigor até segunda assinatura:**
- ❌ Novo slice (incluindo E02-S08).
- ❌ Retrospectiva automatizada E02 (epic-retrospective bloqueado porque E02 está travado, não fechado).
- ❌ `/next-slice`.
- ❌ Qualquer auto-aplicação do `harness-learner`.
- ❌ Nova decisão de produto não-emergencial.
- ✅ Correções P0/P1 com incidente dedicado.
- ✅ Trabalho nos 8 pré-requisitos.
