# Decisão do PM — Executability Gap Action Plan (Bloco 10)

**Data da decisão:** _(preencher quando assinar)_
**Decisor:** Product Manager (único humano ativo — `CLAUDE.md §3.1`)
**Status deste arquivo:** 🟡 **TEMPLATE** — aguardando assinatura do PM
**Formato de resposta aceito:** `(a)`, `(b)` ou `(c)` (ver §3)
**Registro:** esta decisão é o equivalente, para o Bloco 10, do que `pm-decision-external-guides-2026-04-11.md` é para os Blocos 8+9.

---

## 1. O que está sendo apresentado ao PM

**Plano completo:** [`docs/audits/progress/executability-gap-action-plan.md`](../audits/progress/executability-gap-action-plan.md)

**Resumo em 1 parágrafo:** o harness atual (Blocos 0-9) pode fechar o go/no-go do Dia 1 com todos os gates mecânicos verdes e ninguém nunca ter bootado o Kalibrium ou clicado numa tela. Esse é o risco exato levantado pelo PM em 2026-04-11. O Bloco 10 propõe 5 gates novos que, juntos, garantem que toda feature mergeada passou por: (1) o produto bootou em preview env, (2) o fluxo PRD que essa feature atravessa rodou verde em Playwright, (3) o PM clicou na sequência e marcou `APROVO` num arquivo de walkthrough. Sem os 5, o gap persiste e o PM aceita conscientemente o risco.

**Princípio operacional proposto (novo P10):**

> **"Se o PM não pode clicar, não está pronto."**

---

## 2. Os 5 itens do Bloco 10 (resumo PM-friendly)

### 10.1 — Walking skeleton obrigatório no Slice 1
**O que resolve:** garante que o primeiríssimo slice boote a aplicação com uma tela visível. Hoje, nada força isso.
**O que significa na prática para o PM:** o Slice 1 vai ser "app funcionando com uma tela vazia onde escreve Kalibrium". Slices 2+ ficam bloqueados até isso existir.
**Custo:** 6 unidades.
**Toca arquivo selado?** Sim (hook novo). Requer relock pelo PM.

### 10.2 — Gate de fluxo-PRD ponta-a-ponta
**O que resolve:** garante que fluxos do PRD (ex.: "técnico emite certificado de calibração") rodem de ponta a ponta, não só cada pedaço isolado.
**O que significa na prática para o PM:** quando o PM descreve um fluxo no PRD (em PT-BR), o agente traduz em um teste automatizado que exercita o fluxo inteiro. Se o fluxo quebra, o slice não merge, mesmo que cada AC individual esteja verde.
**Custo:** 8 unidades.
**Toca arquivo selado?** Parcial (modifica verify-slice.sh). Requer relock.

### 10.3 — PM Browser Walkthrough Gate (peça central)
**O que resolve:** garante que o PM efetivamente abra o navegador e teste o fluxo antes de merge. Hoje, nada força isso.
**O que significa na prática para o PM:** antes de merge, o agente escreve um passo-a-passo em PT-BR ("abra essa URL, clique aqui, preencha isso, verifique aquilo") num arquivo `docs/pm-walkthroughs/slice-NNN-walkthrough-*.md`. O PM segue os passos, marca `APROVO` ou `REPROVO` no arquivo, e só aí o push é liberado. Se marcar `REPROVO`, incident é criado automaticamente.
**Custo:** 5 unidades.
**Toca arquivo selado?** Sim (hook novo). Requer relock.

### 10.4 — Demo environment sempre ligado
**O que resolve:** em vez de o PM rodar comandos no terminal (`pnpm dev`), cada slice tem uma URL clicável pública (`https://slice-NNN.kalibrium-preview.<provider>`) que o PM abre no navegador.
**O que significa na prática para o PM:** o PM nunca mais precisa ver um terminal. Abre URL, testa, marca walkthrough.
**Custo:** 5 unidades.
**Toca arquivo selado?** Não (GitHub Action + config de hosting).

### 10.5 — Visual regression + screenshot baseline
**O que resolve:** walkthrough depende de humano atento. Humano cansa. Entre um walkthrough e outro, a UI pode regredir (botão sumiu, texto mudou, cor quebrou) sem ninguém notar. O sistema compara pixel-a-pixel e avisa quando uma tela muda sem ter sido declarada no slice.
**O que significa na prática para o PM:** se alguém acidentalmente quebra o visual de uma tela que não era pra mudar, o slice é bloqueado automaticamente antes do PM precisar ver.
**Custo:** 7 unidades.
**Toca arquivo selado?** Parcial (modifica verify-slice.sh).

---

## 3. Decisão do PM

**Opções de resposta:**

### (a) Aceito integralmente
Bloco 10 vira extensão oficial do plano de ação, mesmo status dos Blocos 8+9. Todos os 5 itens entram no tracker com dependências declaradas. Execução começa em sessão nova após o Bloco 2 (Stack via ADR-0001) fechar. Item 9.4 (auditar este plano em sessão nova) é adicionado ao Bloco 9 do plano externo.

**Assinatura:** _(preencher)_
**Data:** _(preencher)_

### (b) Aceito com recortes
Aceita alguns dos 5 itens e rejeita outros. Marque abaixo:

- [ ] 10.1 Walking skeleton obrigatório — ACEITO / REJEITADO
  - Se rejeitado, justificativa: _(preencher)_
- [ ] 10.2 Gate de fluxo-PRD ponta-a-ponta — ACEITO / REJEITADO
  - Se rejeitado, justificativa: _(preencher)_
- [ ] 10.3 PM Browser Walkthrough Gate — ACEITO / REJEITADO
  - Se rejeitado, justificativa: _(preencher)_
- [ ] 10.4 Demo environment — ACEITO / REJEITADO
  - Se rejeitado, justificativa: _(preencher)_
- [ ] 10.5 Visual regression — ACEITO / REJEITADO
  - Se rejeitado, justificativa: _(preencher)_

**Observação importante:** se 10.1 ou 10.3 forem rejeitados, o risco central **permanece aberto**. 10.2, 10.4, 10.5 são multiplicadores; 10.1 e 10.3 são os dois gates obrigatórios.

**Assinatura:** _(preencher)_
**Data:** _(preencher)_

### (c) Rejeito integralmente
O PM decide que o risco identificado é aceitável no modelo operacional atual e não quer adicionar os 5 gates. O gap fica documentado neste arquivo e no tracker principal, e o PM assume conscientemente que:

1. Pode haver features mergeadas que nunca foram bootadas como sistema integrado.
2. O "pronto" continua sendo só gates mecânicos de slice/AC.
3. O fluxo do PRD pode quebrar em integração sem ninguém notar até um cliente reclamar.

**Justificativa:** _(preencher)_
**Assinatura:** _(preencher)_
**Data:** _(preencher)_

---

## 4. Restrições operacionais reafirmadas (independente da opção escolhida)

1. **Arquivos selados só via `relock-harness.sh` em terminal externo pelo PM.** Se a decisão for (a) ou (b) incluindo itens 10.1, 10.2, 10.3 ou 10.5, **o PM terá que rodar relock** na ocasião em que esses itens forem implementados (em sessão nova). Cada relock gera incident file auditável em `docs/incidents/harness-relock-*.md`.

2. **Auditoria do plano em sessão nova.** Item **9.4** novo (adicionado ao Bloco 9 do plano externo se a resposta for (a) ou (b)):
   > 9.4 — Auditar `executability-gap-action-plan.md` em sessão nova. Os 5 itens cobrem o risco? Algum item é over-engineering? Há alternativa mais barata? Sair com entregável em `docs/audits/internal/executability-gap-audit-YYYY-MM-DD.md`.

3. **R9 zero bypass.** Nenhum gate novo tem flag de pular. Se o walkthrough estiver faltando, o push bloqueia. Sem exceções "só dessa vez".

4. **R12 aplicado a toda saída PM.** Walkthrough files, mensagens de bloqueio, explain-slice output — tudo em PT-BR sem jargão técnico.

5. **Admin bypass do PM (owner merge) não pode pular walkthrough.** O walkthrough **é** o mecanismo de aprovação PM — bypassar é contradição lógica, não só uma violação de regra.

6. **Meta-auditoria em sessão nova.** Este arquivo foi escrito pelo agente na mesma sessão que o PM levantou o risco — viés confirmatório é inevitável. A auditoria 9.4 existe especificamente para pegar erros de escopo neste plano.

---

## 5. Dependências (para a opção (a) ou (b))

Nada do Bloco 10 começa até o **Bloco 2** (stack via `/decide-stack`, ADR-0001) fechar. Única exceção: **10.1.1 (ADR-0003 E2E tool)** e **10.4.1 (ADR-0004 preview hosting)** podem ser decididos em paralelo com `/decide-stack`, porque são puros ADRs e não dependem de código rodando.

Sequência recomendada (detalhada no §5 do plano):

1. Bloco 2 (Stack)
2. ADR-0003 + ADR-0004 em paralelo
3. 10.1 (skeleton) em paralelo com Bloco 3 (testes reais)
4. 10.2 (flow gate) em paralelo com Bloco 4 (explain-slice real)
5. **10.3 (walkthrough) — peça central**
6. Bloco 5 (CI) + 10.4 (preview env)
7. 10.5 (visual regression)
8. Bloco 6 + 7 (re-auditoria Dia 1 — agora pergunta inclui "existe walkthrough APROVO registrado?")

---

## 6. Esforço total (para opção (a))

**35 unidades** (≈1.75x o Bloco 1). Comparação: plano externo 8+9 = 61-63 unidades.

| Item | Unidades |
|---|---|
| 10.1 walking skeleton | 6 |
| 10.2 flow gate | 8 |
| 10.3 walkthrough gate | 5 |
| 10.4 preview env | 5 |
| 10.5 visual regression | 7 |
| ADR-0003 + ADR-0004 | 2 |
| Atualizações em CLAUDE.md + constitution.md | 2 |
| **Total** | **35** |

---

## 7. Ações manuais do PM desbloqueadas por esta decisão (se (a) ou (b))

1. Rodar `scripts/relock-harness.sh` **por vez** que um hook novo (10.1.3, 10.3.3) ou modificação de `verify-slice.sh` (10.2.4, 10.5.3) for implementado em sessão nova. Não é de uma vez só — é gradual, um por entregável.
2. Assinar walkthrough files à medida que slices forem criados (após 10.3 estar ativo).
3. Validar `docs/adr/0003-e2e-testing-tool.md` quando o agente gerar (escolher entre Playwright, Cypress, WebdriverIO — agente dá recomendação forte).
4. Validar `docs/adr/0004-preview-hosting.md` quando o agente gerar (escolher provider — critério LGPD + custo).

---

## 8. Rastreabilidade

- **Plano detalhado:** `docs/audits/progress/executability-gap-action-plan.md`
- **Tracker principal atualizado:** `docs/audits/progress/meta-audit-tracker.md` §Bloco 10
- **Pergunta de origem:** PM 2026-04-11 — *"nesse ambiente tem o risco do sistema ser construído e não conseguirmos executar os fluxos do prd, funções etc?"*
- **Restrição-chave:** PM 2026-04-11 — *"não entendo nada de código, posso validar teste de produto no navegador"*
- **Constituição referenciada:** `docs/constitution.md §3.1` (humano = PM, não desenvolvedor)
- **Memória (extensão após decisão):** `memory/project_meta_audit_action_plan.md` e `memory/project_direction_a.md`

---

## 9. Instruções para o PM preencher este template

1. **Leia o resumo PM-friendly no §2.** Ele cobre o essencial sem código.
2. **Se quiser o detalhe completo**, abra o plano: `docs/audits/progress/executability-gap-action-plan.md`. Mas o §2 deste arquivo é suficiente para decidir.
3. **Escolha uma das 3 opções no §3**, preencha assinatura + data.
4. **Renomeie o arquivo** de `pm-decision-executability-gap-TEMPLATE-2026-04-11.md` para `pm-decision-executability-gap-2026-04-11.md` (remover `TEMPLATE`).
5. **Commite** o arquivo renomeado (pre-commit-gate vai validar normalmente).
6. **Abra sessão nova** do Claude Code e sinalize que a decisão foi tomada — o agente vai atualizar o tracker, adicionar item 9.4 se aceito, e começar a próxima ação concreta.
