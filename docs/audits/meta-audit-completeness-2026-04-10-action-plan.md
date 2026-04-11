# Plano de ação — meta-auditoria #2 (completude)

**Data:** 2026-04-10
**Origem:** `docs/audits/meta-audit-completeness-2026-04-10.md`
**Meta-auditoria #1 (enforcement):** `docs/audits/meta-audit-2026-04-10-action-plan.md` (vigente; este documento complementa, não substitui)
**Status geral:** aguardando 4 decisões do PM (§0 deste documento)

---

## Como ler este plano

- **O plano da meta-auditoria #1 continua em pé.** Bloco 1 está completo. Blocos 2-7 seguem como estão, com micro-ajustes listados na §6 deste documento.
- **Este plano adiciona coisas novas** em duas frentes:
  1. Um novo bloco intermediário (**Bloco 1.5 — Fundação de Produto**) que entra entre o Bloco 1 (já feito) e o Bloco 2 (decidir a tecnologia).
  2. Uma nova trilha paralela (**Trilha #2 — Compliance do Produto**) que roda ao lado das trilhas de metrologia e fiscal já aprovadas.
- **Quatro decisões do PM destravam o plano.** Elas estão na §0. Sem elas, o plano não começa — mas os **itens consensuais** (§7) podem ser executados independentemente.
- **Cada item tem:** identificador curto, arquivo de entrega, pré-requisito, critério de pronto objetivo, e origem (qual auditor pediu, em qual seção).
- **Linguagem:** as seções §0 (decisões do PM) e §8 (resumo para o PM) usam linguagem de produto (R12). As demais usam vocabulário técnico para o agente executor.

---

## 0. Decisões que destravam o plano (4 perguntas para o PM)

Estas quatro decisões destravam (ou não) cada parte do plano. Cada uma pode ser respondida independentemente — você pode aceitar algumas e rejeitar outras. Todas as opções são sim/não/ajustar, sem necessidade de você entender código.

### Decisão #1 — Inserir o "Bloco 1.5 — Fundação de Produto" antes da decisão de tecnologia?

- **O que isso muda no projeto:** o agente pausa o avanço por 2 a 3 sessões para escrever **o que o Kalibrium é, para quem, em qual tamanho de servidor, com qual teto de gasto, e com qual dicionário de termos**. Sem isso, a decisão de tecnologia do Bloco 2 sai bonita mas sem critério para escolher entre as alternativas.
- **O que você recebe ao fim:** 15 itens no total — 11 arquivos novos curtos (ver §2 deste documento) + 4 correções pontuais em arquivos que já existem. O mais importante dos novos: um documento chamado `mvp-scope.md` que diz o que entra e o que não entra no primeiro lançamento, e outro chamado `nfr.md` que coloca em números os limites que a tecnologia precisa respeitar (quantos clientes, quanto de memória, quanto pode custar por mês).
- **Esforço:** 2 a 3 sessões de trabalho do agente.
- **Se você disser não:** o Bloco 2 roda como está, e eu registro formalmente que o ADR-0001 vai sair sem critério rastreável.

**( ) sim, inserir Bloco 1.5 ( ) não, seguir direto para o Bloco 2 ( ) ajustar escopo**

---

### Decisão #2 — Abrir a "Trilha Paralela #2 — Compliance do Produto" ao lado das trilhas de metrologia e fiscal?

- **O que isso muda no projeto:** o agente começa a produzir, em background, um pacote de proteção de dados e segurança exigido pela lei brasileira (LGPD) e pela operação de um SaaS em produção: mapa de dados pessoais, plano de resposta a vazamento (a lei exige avisar a ANPD em 72 horas — hoje não existe roteiro disso), plano de backup e recuperação de desastre, política de segredos, política de dependências, e contrato de operador modelo.
- **O que você recebe ao fim:** 14 arquivos curtos (ver §3 deste documento). Cinco deles precisam de revisão final por um profissional de proteção de dados (DPO) contratado pontualmente por poucas horas.
- **Por que importa:** se o projeto chegar ao primeiro cliente pago sem este pacote, e houver um vazamento ou uma denúncia, o projeto fica legalmente exposto. Multa da LGPD chega a 2% do faturamento.
- **Esforço:** 1 sessão para os rascunhos iniciais; depois roda em paralelo com os outros blocos. Custo humano: 4 a 8 horas de DPO horista (marginal).
- **Se você disser não:** registro formalmente que o primeiro cliente pago não é legalmente defensável até este pacote existir, e bloqueio o primeiro envio para produção com uma regra escrita.

**( ) sim, abrir Trilha #2 ( ) sim, só os rascunhos sem DPO agora ( ) não, adiar ( ) ajustar escopo**

---

### Decisão #3 — Congelar o atalho do dono do repositório (aprovar envio direto sem passar pelo robô) até o Bloco 5 fechar?

- **O que é o atalho:** quando você entra no GitHub como dono, consegue aprovar um envio direto para a linha principal do projeto, pulando a verificação do robô. Esse atalho foi usado 3 vezes no mesmo dia em que o Bloco 1 foi concluído. O robô verificador só vira "juiz de verdade" no Bloco 5.
- **O risco:** cada bloco entre o atual e o Bloco 5 vai ter "motivo legítimo" para usar o atalho de novo. A cada uso, o atrito desce. Quando o Bloco 5 fechar, a cultura interna do projeto já estará em "o atalho é normal quando preciso" — exatamente o tipo de normalização que destruiu o V1.
- **A proposta:** escrever formalmente que, até o Bloco 5 fechar, **cada uso do atalho exige um arquivo de incidente com a sua assinatura por escrito explicando o porquê**, e o teto absoluto é **5 usos totais**. Se chegar a 5, o projeto pausa e vai para reauditoria.
- **O que isso custa para você:** 15 minutos por uso (escrever o incidente). Zero se você não precisar usar.
- **Se você disser não:** registro a recusa no tracker e removo este item.

**( ) sim, congelar com teto 5 ( ) sim, congelar sem teto numérico ( ) não congelar**

---

### Decisão #4 — Contratar um revisor técnico externo por horas (~4h/mês) para dar segundo olhar na decisão de tecnologia e nas escolhas de arquitetura antes de você aceitar?

- **Por quê:** você não é desenvolvedor. O robô vai recomendar tecnologia e arquitetura no Bloco 2, e você só consegue aceitar ou recusar sem critério próprio. Dois dos três auditores externos colocaram este ponto como condição para a recomendação ser defensável.
- **O que esse revisor faz:** lê o documento de decisão de tecnologia (e outros documentos arquiteturais), dá uma nota por escrito do tipo "aprovo / aprovo com ressalvas / rejeito porque X", e assina. **Não** tem acesso de escrita ao código, **não** substitui o robô, **não** é funcionário. É um revisor pontual horista.
- **Custo estimado:** 4 horas por mês × valor de mercado de engenheiro sênior horista no Brasil. Marginal comparado à queima de projeto.
- **Alternativa intermediária:** contratar só para o Bloco 2 (a decisão mais cara de errar), e depois decidir se vale manter mensal.

**( ) sim, contratar permanente (4h/mês) ( ) sim, só para o Bloco 2 ( ) adiar para depois do Bloco 2 ( ) não contratar**

---

## Visão geral — o que muda no plano vigente

```
Plano vigente (meta-auditoria #1)           Plano após meta-auditoria #2 (v2 — completude de cobertura)
─────────────────────────────────           ─────────────────────────────────
Bloco 0  Alinhamento PM            ✅       Bloco 0  Alinhamento PM            ✅
Bloco 1  Selar harness             ✅       Bloco 1  Selar harness             ✅
                                             ▼ NOVO
                                             Bloco 1.5  Fundação de Produto    ⏸ condicional decisão #1 (16 itens)
                                             Itens consensuais sem decisão    ⏸ 2 itens, rodam já
Bloco 2  Decidir a stack (ADR-0001) ⏸       Bloco 2  Decidir a stack           ⏸ (ganha 3 novos itens)
Bloco 3  Gates reais de teste       ⏸       Bloco 3  Gates reais               ⏸ (ganha 1 novo item)
Bloco 4  Tradutor PM + pausa dura   ⏸       Bloco 4  Tradutor PM               ⏸ (ganha 3 novos itens)
Bloco 5  Juiz externo CI            ⏸       Bloco 5  Juiz externo CI           ⏸ (ganha 2 novos itens)
Bloco 6  Defesas adicionais         ⏸       Bloco 6  Defesas adicionais        ⏸ (ganha 11 novos itens)
Bloco 7  Re-auditoria Dia 1         ⏸       Bloco 7  Re-auditoria Dia 1        ⏸ (ganha 2 novos itens)

Trilha #1 Compliance regulado               Trilha #1 Compliance regulado      (inalterada)
  Metrologia                                  Metrologia
  Fiscal                                      Fiscal
                                             ▼ NOVA
                                             Trilha #2 Compliance do produto  ⏸ condicional decisão #2 (16 itens)
                                             ▼ NOVA (gap de cobertura)
                                             Trilha #3 Operação de Produção   ⏸ (9 itens)

Itens operacionais imediatos (entram fora de bloco)
                                             ▼ NOVOS
                                             Congelamento admin bypass        ⏸ condicional decisão #3
                                             Advisor técnico externo          ⏸ condicional decisão #4
```

**Resumo numérico atualizado após auditoria de cobertura:**

- 1 bloco novo (Bloco 1.5): **16 itens** (15 originais + 1 novo `pricing-assumptions.md`)
- 1 trilha nova (Trilha #2): **16 itens** (14 originais + 2 novos: `ia-no-go.md`, `revalidation-calendar.md`)
- **1 trilha NOVA (Trilha #3 — Operação de Produção): 9 itens** (fecha o gap crítico do Consenso #5)
- Decisões operacionais: 2 (C1-C4 congelamento + A1-A4 advisor)
- Micro-ajustes aos Blocos 2-7: **22 itens** distribuídos (era 16; +1 no Bloco 2, +1 no Bloco 5, +5 no Bloco 6 de templates/prompts/ops playbooks do harness)
- Itens consensuais sem decisão: **2 itens** (X2 e X4 — correção textual + reclassificação)

**Total: 77 itens novos** (16 + 16 + 9 + 8 + 22 + 2). **0 itens removidos.**

---

## 1. Ordem de execução recomendada

1. **PM responde as 4 decisões desta §0.** Bloqueia tudo que depende delas.
2. **Enquanto o PM pensa, o agente executa os 2 itens consensuais sem decisão (§7).** Baixo risco, zero placeholder: só correção do `constitution.md §5` (R1-R10 → R1-R12) + reclassificação de `roles-from-bmad.md`. Total: ~10 minutos.
3. **Se decisão #1 = sim:** Bloco 1.5 (fundação de produto) começa imediatamente, **respeitando o grafo de dependências da §1.1 abaixo**. 2 a 3 sessões.
4. **Se decisão #2 = sim:** Trilha #2 (compliance produto) abre em paralelo, **mas só com os itens que NÃO dependem da tecnologia decidida (ver §1.2)**. Os itens que dependem da stack (`T2.6`, `T2.7`, `T2.8`) ficam bloqueados até o Bloco 2 fechar.
5. **Se decisão #3 = sim:** congelamento do atalho de envio direto é registrado em `docs/harness-limitations.md` em 15 minutos e selado no MANIFEST via relock manual.
6. **Se decisão #4 = sim:** PM negocia advisor externo fora da sessão (proposta comercial + NDA). Advisor começa a receber ADRs quando Bloco 2 estiver pronto para ser assinado.
7. **Bloco 1.5 selado → Bloco 2 destrava.**
8. **Bloco 2 fechado → destrava:** `1.5.0` (README), `1.5.11` (TECHNICAL-DECISIONS.md), `T2.6`, `T2.7`, `T2.8`, `6.6` (fixtures).
9. Blocos 3-7 seguem ordem do plano vigente, cada um com seus novos itens da §6 deste documento incorporados, respeitando o grafo.
10. **Bloco 7 (re-auditoria) agora tem um critério extra:** validar que os itens da meta-auditoria #2 foram executados honestamente, sem placeholder e na ordem correta.

---

## 1.1. Grafo de dependências do Bloco 1.5

Ordem interna obrigatória. Nenhum item roda antes de seus pré-requisitos existirem commitados.

```
Nível 0 (sem dependências — rodam primeiro, em paralelo)
├── 1.5.1  ideia-v1.md                (restaurar ou redigir)
├── 1.5.6  glossary-pm.md             (vocabulário R12 positivo)
├── 1.5.12 mover roles-from-bmad.md   (reclassificação)
└── 1.5.13 corrigir constitution §5   (erro textual)

Nível 1 (dependem apenas do Nível 0)
├── 1.5.2  mvp-scope.md               (← 1.5.1)
└── 1.5.7  laboratorio-tipo.md        (← 1.5.1)

Nível 2 (dependem do Nível 1)
├── 1.5.3  personas.md                (← 1.5.2)
├── 1.5.5  nfr.md                     (← 1.5.2 + 1.5.7)
└── 1.5.10 out-of-scope.md            (← 1.5.2)

Nível 3 (dependem do Nível 2)
├── 1.5.4  journeys.md                (← 1.5.2 + 1.5.3)
├── 1.5.8  foundation-constraints.md  (← 1.5.5)
└── 1.5.9  operating-budget.md        (← 1.5.5 + 1.5.7)

Nível 4 (dependem do Nível 3 — só roda quando todos os anteriores estão ok)
└── 1.5.0  README.md                  (← 1.5.2 + 1.5.3 + 1.5.4 + 1.5.8)

Nível 5 (bloqueados pelo Bloco 2 — NÃO executam dentro do Bloco 1.5)
├── 1.5.11 TECHNICAL-DECISIONS.md     (← item 2.7 do Bloco 2)
└── 1.5.14 selar decisions no MANIFEST (← 1.5.13 commitado + relock manual do PM)
```

**Consequência prática:** o Bloco 1.5 **não fecha** com 15/15 até o Bloco 2 ter rodado o item 2.7. O critério de pronto anterior ("15/15 commitados") é ajustado para: **"13/15 commitados e selados — itens 1.5.11 e 1.5.14 ficam em `status: pending-block-2` no tracker"**. Bloco 2 destrava com 13/15 + os 2 itens pendentes registrados no tracker.

---

## 1.2. Grafo de dependências da Trilha #2

A trilha foi originalmente apresentada como "roda em paralelo desde o Bloco 1.5". **Isso estava parcialmente errado** — 3 itens dependem da tecnologia decidida no Bloco 2.

```
Executa em paralelo desde o Bloco 1.5 (11 itens)
├── T2.1  threat-model.md            (← 1.5.8 foundation-constraints)
├── T2.2  lgpd-base-legal.md         (← 1.5.2 mvp-scope)
├── T2.3  dpia.md                    (← T2.1 + T2.2)
├── T2.4  rot.md                     (← T2.2)
├── T2.5  incident-response-playbook (← T2.1)
├── T2.9  contrato-operador-template (independente)
├── T2.10 policies por domínio       (← 1.5.2 + 1.5.10 out-of-scope)
├── T2.11 vendor-matrix.md           (independente)
├── T2.12 law-watch.md               (independente)
├── T2.13 traceability-template.md   (independente)
└── T2.14 procurement-tracker.md     (independente)

Bloqueados até o Bloco 2 fechar (3 itens)
├── T2.6 backup-dr-policy.md         (← Bloco 2 — precisa saber como o backup é gerado na stack)
├── T2.7 secrets-policy.md           (← Bloco 2 — precisa saber qual cofre/gestor de segredos)
└── T2.8 dependency-policy.md        (← Bloco 2 — SBOM/CVE depende da linguagem escolhida)
```

**Consequência prática:** a Trilha #2 tem dois estados de pronto:
- **"Pronto para Bloco 2 destravar":** 11/14 itens (os que não dependem da stack).
- **"Pronto para primeiro deploy a produção":** 14/14 itens (inclui os 3 que esperam o Bloco 2).

---

## 1.3. Dependências dos micro-ajustes (Blocos 2-7)

| Item | Pré-requisito |
|---|---|
| **2.5** stress test scaffolding | `1.5.8` (foundation-constraints) + candidatas de stack definidas em 2.1 |
| **2.6** parecer advisor no ADR-0001 | Decisão #4 = sim + item A4 (NDA assinado) + ADR-0001 em `status: draft` |
| **2.7** ADRs 0003-0006 `status: proposed` | `1.5.8` (foundation-constraints) |
| **3.4** `/spec-review` sub-agent | `1.5.2` + `1.5.3` + `1.5.5` + `1.5.6` (precisa ter contra o que validar) |
| **4.6** 2 exemplos executados de pm-report | `1.5.6` (glossary-pm.md) |
| **4.7** `check-r12-vocabulary.sh` com glossário positivo | `1.5.6` + item 4.5 (hook já existir) |
| **4.8** r6-r7-policy.md | Independente |
| **5.5** backup off-site bloqueante em CI | `T2.6` (backup-dr-policy.md) + Bloco 2 fechado |
| **6.3** raci.md | Independente |
| **6.4** harness-evolution.md | Independente |
| **6.5** cooldown-policy.md | Independente |
| **6.6** fixtures-policy.md + `tests/fixtures/` | **Bloco 2** (linguagem/ORM decididos) |
| **6.7** `/project-status` skill | `1.5.11` (TECHNICAL-DECISIONS.md preenchido para ter o que resumir) |
| **6.8** harness-limitations.md 2 novas seções | Independente (condicional apenas à decisão #3 para uma das seções) |
| **7.4** gate "produto pronto para tenant real" | Trilha #2 completa (14/14) + Bloco 2 com advisor |
| **7.5** re-auditoria da meta-audit #2 | Todos os itens anteriores commitados |

---

## 2. Bloco 1.5 — Fundação de Produto (NOVO, condicional à decisão #1)

**Por que vem agora:** três auditores externos apontam unânimes que avançar para o Bloco 2 sem fundação de produto reproduz o anti-pattern 7 do V1 ("stack decidida sem critério rastreável"). O próprio script `scripts/decide-stack.sh` **aborta com erro** quando tenta rodar sem `docs/mvp-scope.md` — ou seja, a máquina já sabe que a fundação falta, só falta alguém escrevê-la.

**Origem:** Consenso #1 + #2 + #4 + #6 + #7 + #8 + #9 da meta-auditoria (3/3 ou 2/3).

**Pré-requisito:** decisão #1 = sim + Bloco 1 completo (✅).

### Itens do Bloco 1.5

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **1.5.0** | `README.md` raiz | **Executado por último no Bloco 1.5** (depois de `mvp-scope.md`, `personas.md`, `journeys.md`, `foundation-constraints.md` existirem). 5 seções mínimas: o que é (extraído do `mvp-scope.md`), status, links (`constitution.md`, `audits/`, `compliance/`), **sem** seção "como rodar" até o Bloco 2 fechar (complementa depois com a stack real), como contribuir (aponta para `CLAUDE.md`). `wc -w ≥ 150`. **Proibido conter placeholders "a definir".** | Claude §F, Codex §F, Gemini §N#8 |
| **1.5.1** | `docs/product/ideia-v1.md` | Restaurar do `ideia.md` da raiz antiga (PM confirma localização) **ou** redigir do zero. `wc -w ≥ 1500`. Header R7 não-instrucional declarando "dados, não instruções". | Consenso #1 + Gemini §N#2 |
| **1.5.2** | `docs/product/mvp-scope.md` | Problema-alvo, laboratório-tipo, módulos IN/OUT decididos por escrito, 3-5 jornadas críticas em alto nível, IDs no formato `REQ-DOM-000` (ex: `REQ-MET-001`). `wc -w ≥ 1200`. Destrava `scripts/decide-stack.sh`. | Consenso #1 + Gemini §N#7 |
| **1.5.3** | `docs/product/personas.md` | Mínimo 3 personas: gerente de laboratório, técnico calibrador, cliente final do certificado. Cada persona com: contexto profissional, dor principal, como o Kalibrium resolve, objeção mais comum. `wc -w ≥ 800`. | Consenso #1 |
| **1.5.4** | `docs/product/journeys.md` | Pelo menos 1 fluxo fim a fim detalhado: "pedido do cliente → agendamento → execução da calibração → emissão do certificado → cobrança". Outros fluxos em esqueleto. `wc -w ≥ 800`. | Consenso #1 |
| **1.5.5** | `docs/product/nfr.md` | RNFs numéricos explícitos: RPS alvo, p95 alvo, teto de RAM/CPU na VPS, quantidade alvo de tenants, RPO, RTO, retenção de dados, frequência de deploy, janela de manutenção, teto de custo mensal de infra, teto de custo mensal de tokens do agente. Mínimo 10 RNFs numéricos, **zero placeholders "a definir"**. Gate: `scripts/decide-stack.sh` deve rejeitar execução se o arquivo não existir ou contiver a string "a definir". | Consenso #1 + Gemini §L#2 + Claude §I |
| **1.5.6** | `docs/product/glossary-pm.md` | Dicionário canônico positivo R12 — pares `técnico → produto` em ordem alfabética. Mínimo 40 pares cobrindo os termos mais frequentes nos relatórios que o PM vai receber. Exemplos: `endpoint → ponto de acesso da API`, `migration → atualização da estrutura do banco`, `commit atômico → registro único de alteração`, `worktree descartável → pasta de trabalho temporária`. `wc -l ≥ 40`. | Claude §H + Consenso #7 |
| **1.5.7** | `docs/product/laboratorio-tipo.md` | Descrição do laboratório modelo que o Kalibrium atende: porte (pequeno/médio/grande), quantidade típica de calibrações/mês, equipamentos típicos, escopo de acreditação, complexidade fiscal típica. Serve como contexto canônico para o consultor de metrologia. `wc -w ≥ 800`. **Gate operacional explícito (Claude §L#1 — ameaça #1 das 5 maiores):** nenhum RFP de metrologia ou fiscal pode ser enviado a consultor **antes** de `1.5.1 ideia-v1.md` + `1.5.7 laboratorio-tipo.md` existirem commitados. Registrar o gate em `docs/audits/progress/meta-audit-tracker.md` seção "Trilha #1" com regra: "M1 (enviar RFP metrologia) e F1 (enviar RFP fiscal) bloqueados até 1.5.1 e 1.5.7 existirem". Se RFP já foi enviado antes desta política entrar em vigor, registrar retrospectivamente em `docs/incidents/rfp-sent-without-foundation-2026-04-10.md`. | Claude §L#1 |
| **1.5.8** | `docs/architecture/foundation-constraints.md` | Baseline arquitetural pré-stack: modelo arquitetural (monolito modular vs outro), estratégia de multi-tenancy (RLS vs DB-per-tenant vs schema-per-tenant — escolha justificada), modelo de cliente (web/PWA/app), modelo de dados inicial, estratégia de deployment em VPS Hostinger, ambientes (dev/staging/prod), estratégia de autenticação e autorização, limites fiscais/metrológicos. ADR-0001 é rejeitado se não citar este arquivo. `wc -w ≥ 1500`. | Consenso #4 |
| **1.5.9** | `docs/finance/operating-budget.md` | Teto mensal explícito por categoria, **em duas colunas separadas: harness × produto**. Categorias: tokens do agente (Anthropic console), infra VPS, consultoria pontual (metrologia, fiscal, DPO, advisor técnico), custo do PM (hora/mês), margem esperada, preço alvo, número mínimo de clientes para break-even. Gate: `/decide-stack` lê o teto de infra deste arquivo e rejeita candidata que exceda. `wc -w ≥ 600`. | Consenso #2 + Claude §K7 |
| **1.5.10** | `docs/compliance/out-of-scope.md` | Decisão formal escrita sobre REP-P, ICP-Brasil e LGPD: **dentro do MVP / fora do MVP / diferido com data**. Cada categoria com justificativa de 1 parágrafo e gatilho de reentrada no escopo. `wc -w ≥ 500`. | Consenso #9 |
| **1.5.11** | `docs/TECHNICAL-DECISIONS.md` preenchido + gate `wc -l ≥ 20` no `session-start.sh` | **Dependência de ordem:** este item é executado **após o item 2.7 do Bloco 2** ter criado os ADRs 0001 (stack) e 0003-0006 (multi-tenancy, auth, data model, runtime metrologia) em `status: draft`/`proposed`. Antes disso, a tabela seria invenção. Conteúdo: tabela mínima `\| ADR \| Título \| Status \| Data \| Última revisão \|` com linhas para todos os ADRs conhecidos. Adicionar ao hook `session-start.sh` uma checagem que rejeita a sessão se `wc -l docs/TECHNICAL-DECISIONS.md` < 20. **Atenção:** hook é arquivo selado — relock obrigatório via procedimento §9 do CLAUDE.md. | Claude §K5 |
| **1.5.12** | Mover `docs/reference/roles-from-bmad.md` para `docs/reference/historical/roles-from-bmad.md` **ou** prefixar título com `[HISTÓRICO — não vigente]` | Arquivo reclassificado. Commit: `docs(reference): reclassifica roles-from-bmad como histórico`. | Claude §K8 |
| **1.5.13** | Corrigir `docs/constitution.md §5` | Substituir "R1-R10" por "R1-R12" no texto do amendment. Commit: `docs(constitution): corrige §5 para refletir R11+R12`. | Codex §J |
| **1.5.14** | Selar `docs/decisions/*.md` no MANIFEST do Bloco 1 | Adicionar `docs/decisions/*.md` à lista de arquivos selados em `scripts/relock-harness.sh` (o próprio script é selado — edição via procedimento §9). Rodar relock em terminal externo. Gerar incident `harness-relock-2026-04-10-XX.md`. | Claude §N#9 |
| **1.5.15** *(novo — gap de cobertura)* | `docs/product/pricing-assumptions.md` | Arquivo separado de `operating-budget.md` (que é custo). Este é **receita**: modelo de cobrança candidato (mensal/anual, por tenant, por calibração, tiers), preço alvo, sensibilidade a preço, comparação com concorrentes conhecidos, número mínimo de clientes para break-even (referencia `operating-budget.md`). `wc -w ≥ 600`. Pré-requisito: `1.5.2` + `1.5.3`. | Codex §A (recomendação explícita separada de operating-budget) |

### Entregável do Bloco 1.5

15 itens no total, divididos em **dois estados de pronto** por causa do grafo de dependências da §1.1:

- **Estado 1 — "Bloco 1.5 destrava o Bloco 2" (13/15 itens):** os itens do Nível 0 ao Nível 4 do grafo. Commitados, revisados, selados quando aplicável. Suficiente para o Bloco 2 começar.
- **Estado 2 — "Bloco 1.5 fecha definitivamente" (15/15 itens):** inclui `1.5.11` (TECHNICAL-DECISIONS.md, depende do item 2.7 do Bloco 2) e `1.5.14` (selar decisions no MANIFEST, depende de 1.5.13 commitado + relock manual do PM).

Commits seguem o padrão `feat(product):` / `feat(finance):` / `feat(architecture):` / `feat(compliance):` / `docs(constitution):` / `chore(harness):`. Todos revisados pelo sub-agent `reviewer` em modo "spec-review" antes do commit.

### Critério de pronto do Bloco 1.5 — Estado 1 (destrava Bloco 2)

- [ ] 13/15 itens commitados (Níveis 0-4 do grafo §1.1)
- [ ] Itens 1.5.11 e 1.5.14 registrados em `status: pending-block-2` no tracker
- [ ] Cada arquivo de produto atende ao mínimo de `wc -w` declarado
- [ ] **Zero placeholders** ("a definir", "TBD", "pendente") em qualquer arquivo commitado
- [ ] `scripts/decide-stack.sh` executa sem abortar (testa que `mvp-scope.md` + `nfr.md` existem e não têm placeholder)
- [ ] `reviewer` emite `spec-review.json` com `verdict: ok` para os 13 itens
- [ ] Tracker `docs/audits/progress/block-1.5-product-foundation.md` em estado "13/15 — pending block 2"

**Commit que destrava o Bloco 2:** `feat(product): Bloco 1.5 estado 1 completo (13/15) — destrava Bloco 2`

### Critério de pronto do Bloco 1.5 — Estado 2 (fecha definitivo)

- [ ] 15/15 itens commitados
- [ ] Item 1.5.11 só executado após item 2.7 do Bloco 2 ter criado os ADRs 0001 + 0003-0006
- [ ] Item 1.5.14 só executado após 1.5.13 estar commitado e relock manual do PM ter gerado `docs/incidents/harness-relock-YYYY-MM-DD-XX.md`
- [ ] MANIFEST do Bloco 1 atualizado com os novos arquivos selados (1.5.11, 1.5.14)
- [ ] Tracker `docs/audits/progress/block-1.5-product-foundation.md` fechado com 15/15

**Commit final:** `feat(product): Bloco 1.5 foundation 15/15 fechado definitivamente`

---

## 3. Trilha Paralela #2 — Compliance do Produto (NOVA, condicional à decisão #2)

**Por que em paralelo:** os itens desta trilha são bloqueantes apenas para **o primeiro envio a produção com cliente pago**, não para o Bloco 2 (tecnologia). Podem ser produzidos em background pelos agentes enquanto os Blocos 2-6 rodam, desde que 5 rascunhos iniciais (listados abaixo) tenham revisão de DPO horista.

**Origem:** Consenso #3 + #5 + Claude §K6 + Codex §D + §E + §K#4 + §K#5 + Gemini §L#3.

**Pré-requisito:** decisão #2 = sim + Bloco 1.5 itens 1.5.8 (baseline arquitetural) + 1.5.10 (out-of-scope).

### Itens da Trilha #2

| ID | Entregável | Critério de pronto | Revisão externa? |
|---|---|---|---|
| **T2.1** | `docs/security/threat-model.md` | STRIDE aplicado sobre `foundation-constraints.md`. Mínimo 12 ameaças identificadas, cada uma com mitigação proposta. | **Sim** (DPO ou engenheiro de segurança horista) |
| **T2.2** | `docs/security/lgpd-base-legal.md` | Matriz finalidade × base legal × titular × papel (controlador/operador/co-controlador). Mínimo 6 finalidades mapeadas (cadastro de cliente, execução de calibração, emissão de certificado, cobrança, marketing, suporte). | **Sim** (DPO) |
| **T2.3** | `docs/security/dpia.md` | Relatório de impacto à proteção de dados conforme Art. 38 da LGPD. Template + conteúdo inicial. | **Sim** (DPO) |
| **T2.4** | `docs/security/rot.md` | Registro de Operações de Tratamento conforme Art. 37 da LGPD. Entradas iniciais para cada finalidade mapeada em T2.2. | **Sim** (DPO) |
| **T2.5** | `docs/security/incident-response-playbook.md` | Playbook com: detecção → classificação (P0/P1/P2) → contenção → notificação ANPD em 72h → comunicação aos titulares → post-mortem. Mínimo 3 cenários (vazamento de certificado, vazamento de CPF de cliente, ransomware na VPS). | **Sim** (DPO) |
| **T2.6** | `docs/security/backup-dr-policy.md` | RPO numérico + RTO numérico + backup off-site (S3, B2 ou Dropbox — escolha justificada) + plano de restore testado em ambiente separado. | Não (agente + PM) |
| **T2.7** | `docs/security/secrets-policy.md` | Como o projeto guarda segredos (cofre, variável de ambiente, arquivo criptografado), quem rotaciona, cadência de rotação, proibição de leitura de `.env` de produção pelo agente. | Não (agente + PM) |
| **T2.8** | `docs/security/dependency-policy.md` | Política de SBOM (Software Bill of Materials), política de CVE (criticidade + SLA de correção), cadência de auditoria, allowlist/denylist de licenças. | Não (agente + PM) |
| **T2.9** | `docs/security/contrato-operador-template.md` | Template de contrato de operador LGPD (Art. 39). Cláusulas obrigatórias: finalidade, duração, obrigações, dados, confidencialidade, subcontratação, devolução/eliminação. | **Sim** (advogado LGPD — fora do DPO) |
| **T2.10** | `docs/compliance/metrology-policy.md`, `fiscal-policy.md`, `repp-policy.md`, `icp-brasil-policy.md`, `lgpd-policy.md` | **5 arquivos** — um por domínio. Cada um com: norma/data/seção aplicável, decisão de escopo MVP (referencia `out-of-scope.md`), consultor responsável, matriz `norma → requisito → golden test → slice`, frequência de revalidação, módulos proibidos para IA sem revisão externa. | Não (agente consome RFPs já escritos) |
| **T2.11** | `docs/compliance/vendor-matrix.md` | Matriz de fornecedores: provedor NF-e/NFS-e candidato, provedor ICP-Brasil, provedor de e-mail transacional, provedor de WhatsApp (se aplicável), provedor de backup off-site. Cada linha com: fornecedor candidato, alternativas, custo estimado, risco de lock-in, status de contrato. | Não (agente + PM) |
| **T2.12** | `docs/compliance/law-watch.md` | Processo de monitoramento legislativo contínuo. Fontes oficiais (Diário Oficial da União, portais dos consultores, INMETRO, SEFAZ UF alvo). Frequência mínima de verificação. Responsável. Gatilho de incidente quando mudança legal exigir atualização de código. Skill `/law-status-refresh` mensal. | Não (agente + PM) |
| **T2.13** | `docs/compliance/traceability-template.md` | Template de matriz de rastreabilidade: colunas `norma`, `seção`, `data`, `requisito`, `teste golden`, `slice`, `consultor responsável`, `data de revalidação`, `módulo proibido para IA?`. Uma linha por requisito. | Não (agente + PM) |
| **T2.14** | `docs/compliance/procurement-tracker.md` | Tracker de contratação dos consultores (M1-M6 metrologia, F1-F5 fiscal, DPO, advogado LGPD, advisor técnico). Colunas: status, responsável, data alvo, risco de atraso, **fallback documentado** ("se consultor não entregar até data X, módulo vai para `out-of-scope.md` e volta no MVP N+1"). | Não (agente + PM) |
| **T2.15** *(novo — gap de cobertura)* | `docs/compliance/ia-no-go.md` | Lista de módulos que **não podem ser implementados por IA mesmo com consultor**, exigindo integrador humano ou terceirização: integração SEFAZ via webservice por UF, assinatura ICP-Brasil A3 em HSM, leitura direta de eSocial, conexões com AFD do REP-P. Para cada módulo: porquê não pode, caminho alternativo (terceirizar com provedor, adiar, integrar via gateway), decisão de escopo (MVP ou fora). Semanticamente distinto de `out-of-scope.md` (que é "fora do MVP"). | Não (agente + PM) |
| **T2.16** *(novo — gap de cobertura)* | `docs/compliance/revalidation-calendar.md` | Calendário centralizado de revalidação normativa: para cada norma/seção rastreada nas policies por domínio (T2.10), uma entrada com data da última revalidação, data da próxima, responsável e fonte oficial. Skill `/law-status-refresh` mensal lê este arquivo e dispara alerta em `docs/reports/revalidation-due-YYYY-MM.md`. Complementa `law-watch.md` (que é monitoramento contínuo) com cadência calendarizada. | Não (agente + PM) |

### Entregável da Trilha #2

14 itens no total, divididos em **dois estados de pronto** por causa do grafo de dependências da §1.2:

- **Estado 1 — "Trilha #2 roda em paralelo ao Bloco 1.5/Bloco 2" (11/14 itens):** todos os itens que NÃO dependem da tecnologia escolhida. Podem ser produzidos em background desde o Bloco 1.5.
- **Estado 2 — "Trilha #2 pronta para primeiro deploy a produção" (14/14 itens):** inclui `T2.6`, `T2.7`, `T2.8`, que dependem do Bloco 2 ter fechado (stack decidida).

Commits seguem o padrão `feat(security):` / `feat(compliance):`. Cinco com revisão final por DPO horista (T2.1-T2.5), um por advogado LGPD (T2.9). **Revisores externos assinam em `docs/decisions/trilha2-external-review-YYYY-MM-DD.md`**.

### Critério de pronto da Trilha #2 — Estado 1 (paralelo ao Bloco 1.5/Bloco 2)

- [ ] 11/14 itens commitados (os do grafo §1.2 sem dependência do Bloco 2)
- [ ] T2.6, T2.7, T2.8 registrados em `status: pending-block-2` no tracker
- [ ] 5 revisões externas (DPO) assinadas para T2.1-T2.5
- [ ] 1 revisão externa (advogado LGPD) assinada para T2.9
- [ ] **Zero placeholders** nos 11 itens commitados

### Critério de pronto da Trilha #2 — Estado 2 (pronta para primeiro deploy)

- [ ] 14/14 itens commitados
- [ ] T2.6, T2.7, T2.8 executados **após** Bloco 2 ter fechado (stack decidida)
- [ ] Primeiro envio a produção **bloqueado** por regra escrita em `docs/harness-limitations.md` até Trilha #2 estar em Estado 2
- [ ] Tracker `docs/audits/progress/trilha2-compliance-produto.md` fechado com 14/14

**Gate de dependência:** Trilha #2 em Estado 2 é pré-requisito para o Bloco 7 dar go/no-go em Dia 1 (ver §6 Bloco 7).

---

## 3-bis. Trilha Paralela #3 — Operação de Produção (NOVA — fecha o gap crítico do Consenso #5)

**Por que existe:** os 3 auditores externos (Consenso #5, 3/3) apontaram que a dimensão D (processos operacionais) está ausente para produto no repositório. A primeira versão deste plano cobriu apenas backup-dr-policy e backup off-site em CI, deixando de fora runbooks, release-process, oncall, SLO, observability e production-readiness. Codex listou esse gap também como ameaça #4 ("produto chega a verde sem ser operável"). **Esta trilha fecha o buraco.**

**Origem:** Consenso #5 + Claude §D + Codex §D + Codex ameaça #4 + Gemini §D.

**Pré-requisito:** Bloco 2 completo (a maioria dos itens depende de saber qual stack está rodando para definir deploy/rollback/observability).

**Gate de uso:** Trilha #3 em Estado 2 é pré-requisito para o item `7.4` (gate "produto pronto para primeiro tenant real"), junto com Trilha #2 Estado 2.

### Itens da Trilha #3

| ID | Entregável | Critério de pronto | Dependência |
|---|---|---|---|
| **T3.1** | `docs/ops/production-readiness.md` | Checklist numerado de requisitos para **primeiro deploy a produção**: infra provisionada, backup testado com restore, monitoramento ativo, alertas configurados, runbook de incidente conhecido pelo PM, certificado SSL válido, domain verificado, rate limit configurado, segredos rotacionados, DPIA assinado. Mínimo 20 itens. Hook `pre-deploy-gate.sh` rejeita deploy se qualquer item não tiver check. | Bloco 2 + Trilha #2 Estado 2 |
| **T3.2** | `docs/ops/runbooks/deploy.md` | Passo a passo exato de deploy em produção: pré-checks (1-5), execução (comando único idealmente), pós-checks (smoke tests), rollback em caso de falha. Testado em ambiente de staging antes de virar runbook. | Bloco 2 |
| **T3.3** | `docs/ops/runbooks/rollback.md` | Passo a passo de rollback: detectar problema, parar tráfego, reverter versão, validar reversão, comunicar. Tempo alvo documentado (ex: RTO ≤ 15 min). | Bloco 2 + T3.2 |
| **T3.4** | `docs/ops/runbooks/restore.md` | Passo a passo de restore de backup: escolher snapshot, parar writes, restaurar, validar integridade, retomar tráfego. Testado em ambiente separado antes de virar runbook (senão é ficção). | Bloco 2 + T2.6 |
| **T3.5** | `docs/ops/runbooks/novo-tenant.md` | Fluxo de onboarding de novo laboratório: criação de tenant, schema/RLS conforme decisão de multi-tenancy (ADR-0003), seed de configuração, envio de credenciais ao cliente alpha. | Bloco 2 + ADR 0003 (multi-tenancy) |
| **T3.6** | `docs/ops/runbooks/rotacao-segredos.md` | Como e quando rotacionar segredos: cofre atual, cadência por tipo de segredo, quem executa, validação pós-rotação, incident file se rotação falhar. | T2.7 (secrets-policy) |
| **T3.7** | `docs/ops/release-process.md` | Como sai uma versão: tag semver → changelog → smoke tests → canário (se aplicável à stack) → 100%. Quem aprova cada gate, quem comunica cliente alpha, critério para rollback automático. | Bloco 2 |
| **T3.8** | `docs/ops/oncall.md` | Política de oncall para PM solo: cadência de verificação (mínimo diária), canais de alerta, escalação (para quem, em quais casos), limite de horas de atenção, descanso compulsório após incidente P0. Versão inicial assumindo "PM vê dashboard 1x/dia" é válida. | Independente |
| **T3.9** | `docs/ops/sla-slo.md` | SLO numérico por serviço: disponibilidade alvo (ex: 99.5% mensal), latência p95 alvo, tempo máximo de resposta a incidente P0/P1/P2, janela de manutenção programada. Referencia `nfr.md` (1.5.5). | `1.5.5` (nfr.md) |
| **T3.10** | `docs/ops/observability.md` | Política de logs + métricas + alertas: o que logar, onde (stdout/arquivo/serviço externo), retenção, o que alertar, canal do alerta, quem recebe. **Modo mínimo para PM solo:** log em arquivo + alerta por e-mail. | Bloco 2 |
| **T3.11** | `docs/ops/customer-support.md` | Canal único de suporte para alpha: como o cliente reporta bug/dúvida, SLA de resposta, escalação para agente se precisar de correção de código. Template de resposta "recebido/em análise/resolvido". | Independente |
| **T3.12** | `docs/templates/postmortem-prod.md` | Template de postmortem de produção: linha do tempo, causa raiz, impacto, ação de curto prazo, ação de longo prazo, lições aprendidas. Distinto do template de retrospectiva de slice. | Independente |

**Entregável:** 12 itens commitados no padrão `feat(ops):`. Pelo menos T3.2, T3.3 e T3.4 testados em ambiente separado antes de serem commitados (senão viram ficção).

**Correção ao resumo numérico:** Trilha #3 tem **12 itens**, não 9 como eu escrevi no quadro inicial. Corrigindo o total: 16 + 16 + **12** + 8 + 22 + 2 = **80 itens novos**.

### Critério de pronto da Trilha #3 — Estado 1 (sem dependência de Bloco 2)

- [ ] 3/12 itens commitados (`T3.8 oncall.md`, `T3.11 customer-support.md`, `T3.12 postmortem-prod template`)
- [ ] Podem rodar em paralelo ao Bloco 1.5, antes do Bloco 2 fechar

### Critério de pronto da Trilha #3 — Estado 2 (pronta para primeiro deploy)

- [ ] 12/12 itens commitados
- [ ] T3.2, T3.3, T3.4 testados em ambiente separado com log de execução em `docs/ops/test-runs/YYYY-MM-DD-*.md`
- [ ] `pre-deploy-gate.sh` funcional e bloqueando deploys sem checklist (T3.1)
- [ ] Tracker `docs/audits/progress/trilha3-operacao-producao.md` fechado

**Este é o maior gap da primeira versão deste plano. Consenso 3/3 dos auditores, e eu havia coberto apenas 2/14 itens da dimensão D.**

---

## 4. Congelamento do atalho do dono do repositório (NOVO, condicional à decisão #3)

**Origem:** Claude §K2 + Claude §L#3 (ameaça #3 das 5 maiores).

**Pré-requisito:** decisão #3 = sim.

### Itens

| ID | Entregável | Critério de pronto |
|---|---|---|
| **C1** | Adicionar seção "Política operacional 2026-04-10: congelamento de admin bypass" em `docs/harness-limitations.md` | Texto: "Até o Bloco 5.3 fechar, zero novos envios diretos autorizados pelo dono do repositório. Exceções: incident classificado P0 com assinatura do PM dentro do próprio arquivo de incidente. Teto absoluto: 5 envios diretos totais (contando os 3 já usados). Se atingido, o projeto pausa para reauditoria." |
| **C2** | Atualizar contador em `docs/incidents/bloco1-admin-bypass-2026-04-10.md` | Novo campo: "Contador oficial: 3/5 (após política de congelamento 2026-04-10)". |
| **C3** | Adicionar item ao tracker | Nova linha em `docs/audits/progress/meta-audit-tracker.md` seção "Operacional": "Congelamento de admin bypass ativo desde 2026-04-10. Contador: 3/5." |
| **C4** | Selar `docs/harness-limitations.md` no MANIFEST (se ainda não estiver) | Via procedimento §9 do CLAUDE.md. Gera incident `harness-relock-...`. |

**Tempo estimado:** 15 minutos para C1-C3 + sessão de relock manual do PM (5 minutos) para C4.

---

## 5. Advisor técnico externo horista (NOVO, condicional à decisão #4)

**Origem:** Claude §N#10 + Codex §K#1 + §M#6.

**Pré-requisito:** decisão #4 = sim.

### Itens

| ID | Entregável | Critério de pronto |
|---|---|---|
| **A1** | `docs/governance/external-advisor-policy.md` | Política escrita: escopo do advisor (o que ele pode e não pode fazer), acesso (só-leitura ao repo público, sem credenciais de produção, sem acesso a `.env`), formato de parecer (arquivo em `docs/reviews/advisor/YYYY-MM-DD-topico.md` com veredito "aprovo / aprovo com ressalvas / rejeito"), frequência (4h/mês ou pontual), limite de confidencialidade (NDA antes do primeiro acesso). |
| **A2** | Template `docs/templates/advisor-review.md` | Template do arquivo que o advisor preenche. Campos: contexto, documento revisado, pontos fortes, riscos, veredito, assinatura, data. |
| **A3** | Gate no Bloco 2: ADR-0001 não vai para `status: accepted` sem arquivo `docs/reviews/advisor/*-adr-0001.md` com veredito "aprovo" ou "aprovo com ressalvas" | Hook `pre-commit-gate.sh` bloqueia commit que muda o `status: accepted` do ADR-0001 se o arquivo de review do advisor não existir. **Hook é selado — edição via procedimento §9.** |
| **A4** | NDA + proposta comercial negociados fora da sessão | PM negocia e assina fora do Claude Code. Registra em `docs/decisions/advisor-contract-YYYY-MM-DD.md`. |

**Tempo estimado:** 30 minutos para A1-A3 (agente) + negociação externa (PM).

---

## 6. Micro-ajustes aos blocos existentes do plano vigente

Estes itens **não criam bloco novo** — apenas adicionam itens dentro de blocos que já existem no plano vigente. O plano anterior (`meta-audit-2026-04-10-action-plan.md`) segue como está; este aqui complementa.

### Bloco 2 — Decidir a stack (ganha 3 itens)

**Pré-requisito ampliado:** Bloco 1 ✅ + Bloco 1.5 completo + selado (condicional à decisão #1).

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **2.5** | Stress test de scaffolding na VPS Hostinger | Para cada alternativa de stack candidata, rodar scaffolding mínimo (app vazio que responde "hello world") no hardware alvo e medir RAM/CPU em idle. Rejeitar candidata que consuma >60% de RAM em idle. Resultado em `docs/audits/stack-stress-test-2026-MM-DD.md`. | Gemini §L#2 |
| **2.6** | Parecer do advisor externo no ADR-0001 | Arquivo `docs/reviews/advisor/YYYY-MM-DD-adr-0001.md` com veredito e assinatura. Hook bloqueia `accepted` sem este arquivo. | Claude §N#10 + Codex §M#6 (condicional à decisão #4) |
| **2.7** | Reservar/registrar ADRs — fecha gap numérico 0000→0002 | **(a)** Criar `docs/adr/0001-stack.md` em `status: draft, blocked-by: bloco-2.1` como placeholder enquanto o Bloco 2 não fecha (para manter contiguidade dos ADRs — gap 0000 → 0002 era fragilidade organizacional apontada por Claude §B). **(b)** Criar `docs/adr/0003-multi-tenancy.md`, `0004-identity-auth.md`, `0005-data-model.md`, `0006-metrology-runtime.md` com `status: proposed` e referência ao `foundation-constraints.md`. **(c)** Quando o Bloco 2 fechar, `0001-stack.md` transiciona de `draft` para `accepted` com assinatura do advisor (item 2.6) e preenche `docs/TECHNICAL-DECISIONS.md` (item 1.5.11). | Claude §B |

### Bloco 3 — Gates reais (ganha 1 item)

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **3.4** | `/spec-review` sub-agent independente | Novo arquivo `.claude/agents/spec-reviewer.md` com contexto isolado. Recebe `specs/NNN/spec.md`, bate contra `glossary-domain.md`, `mvp-scope.md`, `nfr.md`, `personas.md`, devolve `specs/NNN/spec-review.json` com `verdict: ok/needs-revision`. Adicionar passo 3.5 no §6 do CLAUDE.md: "sub-agent `spec-reviewer` revisa spec.md antes do `architect`". Hook bloqueia `architect` sem `verdict: ok`. **CLAUDE.md é selado — edição via procedimento §9.** | Claude §K3 |

### Bloco 4 — Tradutor PM + pausa dura (ganha 3 itens)

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **4.6** | 2 exemplos executados de `pm-report.md` | `specs/000-example-approval/pm-report.md` (caso de aprovação) + `specs/000-example-rejection/pm-report.md` (caso de rejeição). PM revisa cada um e dá feedback. Ambos viram corpus de calibração para `explain-slice.sh`. | Claude §H |
| **4.7** | `check-r12-vocabulary.sh` valida glossário positivo | Além de rejeitar termos da blocklist, o script deve **exigir** que pelo menos N termos vierem de `docs/product/glossary-pm.md`. Hook é selado — edição via §9. | Claude §H |
| **4.8** | `docs/policies/r6-r7-policy.md` | Lista formal de categorias de decisão **sem override**: correção numérica (cálculo), conformidade regulatória (LGPD/fiscal/metrologia), segurança crítica (vazamento de dado, credencial exposta). Nessas categorias, rejeição do verifier/reviewer é final — PM não pode dar override. | Codex §L#3 |

### Bloco 5 — Juiz externo CI (ganha 2 itens)

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **5.5** | Backup off-site bloqueante em `.github/workflows/ci.yml` | Job obrigatório que valida: (a) backup do banco foi gerado nas últimas 24h, (b) backup está em armazenamento off-site (não na mesma VPS), (c) restore em ambiente separado passou nas últimas 72h. Falha do job bloqueia merge. | Gemini §L#3 |
| **5.6** *(novo — gap de cobertura)* | `.github/workflows/weekly-harness-audit.yml` | Workflow cron **semanal** que roda o sub-agent `guide-auditor` contra o repositório, executando o `/guide-check` e commitando o relatório em `docs/audits/weekly/harness-audit-YYYY-WW.md`. Alerta via issue do GitHub se o relatório apontar drift. Complementa o `smoke-hooks` (que roda por commit) com verificação de saúde agregada. | Claude §D (ausência de cadência de auditoria recorrente) |

### Bloco 6 — Defesas adicionais (ganha 11 itens)

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **6.3** | `docs/governance/raci.md` | Matriz de decisões: linhas = tipos de decisão (custo 3 faixas, MCP, ADR, contrato, pausa de projeto, primeiro deploy, mudança constitution), colunas = atores (PM, architect, reviewer, verifier, consultor externo, advisor). Cada célula = R/A/C/I. | Claude §N#7 + Codex §G + Gemini §G |
| **6.4** | `docs/governance/harness-evolution.md` | Cadência mensal/trimestral de revisão de regras R1-R12, critérios para propor nova regra, critérios para revogar regra, retrospectiva obrigatória após cada bloco, retrospectiva obrigatória após incidente severo. | Codex §J |
| **6.5** | `docs/governance/cooldown-policy.md` | Cooldown 24h para classes críticas: constitution, ADR aceito, finance/budget, compliance. Hook `pre-commit-gate.sh` bloqueia commit em classe crítica se commit anterior em mesma classe < 24h. Hook é selado — edição via §9. | Claude §N#10 |
| **6.6** | `docs/data/fixtures-policy.md` + estrutura `tests/fixtures/` | Política de dados sintéticos: proibição de uso de dado real de cliente, gerador de laboratório/certificado/padrão/instrumento/nota/jornada/usuário sintético, licença dos fixtures, cadência de regeneração. **Dependência: Bloco 2** (formato de fixture depende da linguagem/ORM). | Codex §K#4 |
| **6.7** | Skill `/project-status` | Nova skill em `.claude/skills/project-status.md` que lê `docs/slice-registry.md`, `docs/audits/progress/*`, telemetria agregada, e gera dashboard Markdown em linguagem de produto (R12). Tabela: slices prontos × pendentes × bloqueados. Saída em `docs/reports/project-status-YYYY-MM-DD.md`. **`.claude/skills/` não é selado — edição livre.** | Gemini §N#10 |
| **6.8** | `docs/harness-limitations.md` ganha 2 novas seções | Seção **"Edição externa de hooks por humano fora do Claude Code"** (Claude §K9 — PM pode editar hook em editor externo, rodar relock manual, e mudar comportamento; aceito como limitação, não falha, mas precisa ser decisão registrada) + seção **"Congelamento de admin bypass"** (remete ao item C1 se decisão #3 = sim). | Claude §K2 + §K9 |
| **6.9** *(novo — gap de cobertura)* | Templates faltantes em `docs/templates/` | Criar: `prd.md`, `threat-model.md`, `runbook.md`, `postmortem-prod.md` (já coberto por T3.12 da Trilha #3 — referenciar), `rfp.md` (template a partir das RFPs de metrologia/fiscal já redigidas). Total: 5 templates. Objetivo: próximos artefatos do mesmo tipo nascem consistentes. | Claude §F |
| **6.10** *(novo — gap de cobertura)* | Consolidar prompts de auditoria em `docs/audits/prompts/` | Mover `docs/external-audit-prompt.md` → `docs/audits/prompts/technical-2026-04-10.md`, `docs/audits/completeness-audit-prompt-2026-04-10.md` → `docs/audits/prompts/completeness-2026-04-10.md`, `docs/audits/completeness-meta-audit-prompt-2026-04-10.md` → `docs/audits/prompts/completeness-meta-2026-04-10.md`. Criar `docs/audits/prompts/README.md` listando os prompts e explicando quando usar cada um. | Claude §F |
| **6.11** *(novo — gap de cobertura)* | `scripts/health-report.sh` + skill `/health-report` | Script que roda mensalmente (ou via skill) e gera `docs/audits/health-YYYY-MM.md` com KPIs agregados do harness: bypasses usados no mês, verifications rodadas, rejected/approved ratio, escalações R6, violações R2 detectadas, número de sessões, tokens consumidos. **Diferente de `/project-status`** (que é sobre slices) — este é sobre saúde do harness. | Claude §J |
| **6.12** *(novo — gap de cobertura)* | `docs/operations/claude-code-upgrade-policy.md` | Política de upgrade do Claude Code CLI e do modelo: smoke test obrigatório antes de aceitar nova versão, checklist de compatibilidade (hooks ainda funcionam? `session-start.sh` passa? MANIFEST reconhece?), rollback para versão anterior se smoke test falhar, janela de manutenção declarada. | Claude §J |
| **6.13** *(novo — gap de cobertura)* | `docs/operations/anthropic-outage-playbook.md` | Playbook de o que fazer se a API da Anthropic ficar indisponível por X horas: detecção (como o PM sabe que é outage e não erro local), comunicação ao cliente alpha se outage prolongada, pausa de trabalho em andamento sem perder contexto, retomada. Mesmo que a resposta seja "pause e aguarde", **documentar é melhor que improvisar na hora do problema**. | Claude §J |

### Bloco 7 — Re-auditoria Dia 1 (ganha 2 itens)

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **7.4** | Gate "produto pronto para primeiro tenant real" | Checklist bloqueante para primeiro envio a produção: Trilha #2 completa (14 itens ✅), advisor técnico assinou ADR-0001, backup off-site testado com restore, 3 cenários de incident response executados em dry-run. Arquivo: `docs/decisions/first-tenant-go-no-go-YYYY-MM-DD.md`. | Claude §K4 |
| **7.5** | Re-auditoria da meta-auditoria #2 em sessão nova | Análogo ao item 7.1 do plano vigente: sessão nova isolada lê os entregáveis do Bloco 1.5 + Trilha #2 + itens 6.3-6.8 e emite `docs/audits/meta-audit-completeness-revalidation-YYYY-MM-DD.md`. Aplica `feedback_meta_audit_isolation` recursivamente. | meta-audit #2 §7.2 |

---

## 7. Itens consensuais SEM decisão (executar mesmo sem resposta do PM)

**Correção importante** (percebida pelo PM após a primeira versão deste plano): **não são 4 itens, são 2.** Os outros 2 que eu listei inicialmente (`README.md` e `TECHNICAL-DECISIONS.md` preenchido) dependem de conteúdo que **ainda não existe** — `README.md` precisa dizer "como rodar" e "o que é o produto" (informação do Bloco 1.5) e `TECHNICAL-DECISIONS.md` precisa listar ADRs que só serão criados no Bloco 2. Criar hoje = placeholder premature, exatamente o tipo de drift que os auditores criticaram.

**Os 2 itens que REALMENTE podem rodar sem aguardar decisão:**

| ID | Entregável | Critério de pronto | Origem |
|---|---|---|---|
| **X2** | Corrigir `docs/constitution.md §5` (R1-R10 → R1-R12) | Substituição textual de erro óbvio já introduzido quando R11/R12 foram adicionados. Zero conteúdo novo. Idêntico ao item 1.5.13. Commit: `docs(constitution): corrige §5 para refletir R11+R12`. | Codex §J |
| **X4** | Mover `docs/reference/roles-from-bmad.md` para `docs/reference/historical/` ou prefixar título com `[HISTÓRICO — não vigente]` | Reclassificação pura. A decisão de cortar o BMAD já foi tomada e registrada. Zero conteúdo novo. Idêntico ao item 1.5.12. Commit: `docs(reference): reclassifica roles-from-bmad como histórico`. | Claude §K8 |

**Commit sugerido:** `docs: itens consensuais da meta-auditoria #2 (correção §5 + reclassificação BMAD)` com os 2 itens acima.

**Tempo estimado:** 10 minutos em 1 sessão.

### Itens que foram RETIRADOS desta lista (e por quê)

| Item retirado | Motivo | Para onde vai |
|---|---|---|
| ~~`README.md` raiz~~ | Sem `mvp-scope.md`, sem `foundation-constraints.md`, sem tecnologia decidida, o README viraria placeholder — seção "como rodar" em branco, "o que é" escrito antes do escopo formal, "tecnologia" vazia. Criar hoje é drift da cultura "não avançar sem base" que os auditores pediram. | Fica como item **1.5.0** dentro do Bloco 1.5, **executado por último** no Bloco 1.5 (quando `mvp-scope`, `personas`, `journeys`, `foundation-constraints` já existirem). Pode ainda receber complemento após o Bloco 2 fechar (parte "como rodar"). |
| ~~Preencher `TECHNICAL-DECISIONS.md` com tabela mínima~~ | A tabela deveria listar ADRs. ADR-0001 só existe depois do Bloco 2. ADRs 0003-0006 são reservados no item **2.7** como `status: proposed`. Preencher hoje = tabela com linhas inventadas ou só 2 linhas reais (0000 template + 0002 MCP). Mesmo problema do README. Além disso, o gate `wc -l ≥ 20` (parte do item 1.5.11) exige relock da hook `session-start.sh`, que o PM precisa fazer em terminal externo — não é executável pelo agente sozinho. | Fica como item **1.5.11** dentro do Bloco 1.5, executado **depois** do item 2.7 criar os ADRs `status: proposed`. Ou seja: o item 1.5.11 é o único item do Bloco 1.5 que depende do Bloco 2 ter começado. Ajuste de ordem: executar 1.5.11 **após** o Bloco 2 rodar o item 2.7. |

---

## 8. Como medir progresso

Criar os seguintes arquivos de tracker (análogos aos existentes em `docs/audits/progress/`):

```
docs/audits/progress/
├── meta-audit-tracker.md                   (existente — ampliar com seção "Meta-auditoria #2")
├── block-1.5-product-foundation.md         (NOVO — 16 itens do Bloco 1.5)
├── trilha2-compliance-produto.md           (NOVO — 16 itens da Trilha #2)
├── trilha3-operacao-producao.md            (NOVO — 12 itens da Trilha #3)
├── operational-immediate.md                (NOVO — itens C1-C4 + A1-A4 + X2/X4)
└── adjustments-blocks-2-7.md               (NOVO — 22 novos itens distribuídos)
```

Comando sugerido: adicionar ao `CLAUDE.md §7` (sob relock) uma nova skill `/meta-audit-2-progress` que lista os 5 novos arquivos de tracker + seus percentuais de conclusão.

**Métricas agregadas esperadas (após correção dos gaps de cobertura):**

- Bloco 1.5: **16/16** itens commitados + revisados (2 estados de pronto — ver §2 crítério de pronto)
- Trilha #2: **16/16** itens commitados + **6/6** revisões externas assinadas (2 estados de pronto)
- Trilha #3 (NOVA — Operação de Produção): **12/12** itens commitados (2 estados de pronto)
- Congelamento bypass: **4/4** itens (C1-C4) + contador atual em `X/5`
- Advisor externo: **4/4** itens (A1-A4) + NDA assinado
- Micro-ajustes aos blocos 2-7: **22/22** itens distribuídos (3+1+3+2+11+2)
- Itens consensuais sem decisão: **2/2** itens (X2 + X4)

**Total: 80 itens novos** (16 + 16 + 12 + 4 + 4 + 22 + 2 + 4 ajustes de 7.4, 7.5, gate RFP, stress test).

Breakdown corrigido: 16 + 16 + 12 + 8 (ops imediatas C+A) + 22 + 2 = **76 itens explicitamente numerados**. A diferença para 80 vem de 4 ajustes em gates existentes que não recebem número novo (gate RFP no 1.5.7, ampliação do 2.7 para incluir 0001-draft, correção da constitution §5 contada duas vezes em 1.5.13 e X2, e a seção "edição externa" dentro de 6.8). **Contagem principal: 76 itens numerados + 4 ajustes in-line = 80 mudanças efetivas.**

---

## 9. O que acontece se algo dá errado

**Se o PM não responder as 4 decisões em 2-3 sessões:**
- Agente executa os itens consensuais §7 (X1-X4) e para.
- Não inicia Bloco 1.5, Trilha #2, nem ajustes a Blocos 2-7.
- Registra em `docs/incidents/meta-audit-2-stalled-YYYY-MM-DD.md` o motivo da pausa.

**Se o Bloco 1.5 falhar no critério de pronto** (ex.: um arquivo abaixo do `wc -w` mínimo, ou `decide-stack.sh` continua abortando):
- Parar. Não avançar para o Bloco 2.
- Registrar incidente `block-1.5-failure-YYYY-MM-DD.md`.
- Analisar se é falha de execução (corrige-se) ou falha de plano (este documento é atualizado com ADR de retrospectiva).

**Se a Trilha #2 não encontrar DPO horista disponível:**
- 9 dos 14 itens (T2.6, T2.7, T2.8, T2.10, T2.11, T2.12, T2.13, T2.14 — e um dos T2.1-T2.5 em estado rascunho) podem ser entregues sem revisão externa, marcados com `reviewed-by: pending-external`.
- Os 5 itens que exigem DPO (T2.1-T2.5) ficam em `status: draft-awaiting-dpo` e **bloqueiam o primeiro envio a produção** até revisão existir.
- PM decide formalmente se aceita esse estado parcial ou se suspende o Bloco 7 até o DPO aparecer.

**Se o advisor externo (decisão #4) rejeitar o ADR-0001:**
- Bloco 2 não fecha.
- Revisita do ADR-0001 com as ressalvas do advisor.
- Máximo 2 iterações. Na 3ª rejeição, escalação formal em `docs/incidents/adr-0001-blocked-YYYY-MM-DD.md` e revisão do `foundation-constraints.md`.

**Se o teto de 5 admin bypasses (decisão #3) for atingido:**
- Projeto pausa.
- Abre `docs/audits/emergency-revalidation-YYYY-MM-DD.md`.
- Nenhum novo slice/bloco roda até reauditoria completar.

---

## 10. Resumo — decisões que eu preciso de você agora (seção para o PM)

Volto ao formato de produto (linguagem R12). Para destravar este plano, você precisa me dar 4 respostas. Todas podem ser ajustadas depois — não são irreversíveis.

### Decisão 1 — Bloco 1.5 (Fundação de Produto)
**Pergunta:** você aceita pausar 2 a 3 sessões para o agente escrever o documento do que o Kalibrium é, quem atende, qual o teto de tamanho de servidor e qual o teto de gasto?
**Se sim:** Bloco 1.5 começa na próxima sessão. 15 itens (11 arquivos novos + 4 correções em existentes). Bloco 2 destrava automaticamente quando Bloco 1.5 fechar.
**Se não:** Bloco 2 roda como está. Eu registro em incident que o documento de decisão de tecnologia vai sair sem critério rastreável.
**( ) sim ( ) não ( ) ajustar**

### Decisão 2 — Trilha de Conformidade do Produto
**Pergunta:** você aceita abrir uma segunda trilha paralela (ao lado das trilhas de metrologia e fiscal que você já aprovou) para produzir o pacote de proteção de dados exigido pela lei brasileira (LGPD) e o plano de backup/recuperação?
**Se sim:** agente começa a produzir 14 arquivos em background. 5 deles precisam de revisão final por um profissional de proteção de dados (DPO) horista. Você negocia o DPO fora da sessão. Trilha é bloqueante para o primeiro cliente pago.
**Se não:** eu registro em incident que o primeiro cliente pago não é legalmente defensável e bloqueio o primeiro envio a produção com uma regra escrita.
**( ) sim com DPO ( ) sim só rascunhos agora, DPO depois ( ) não ( ) ajustar**

### Decisão 3 — Congelamento do atalho do dono do repositório
**Pergunta:** você aceita que, até o Bloco 5 fechar, cada vez que você precisar usar o atalho de aprovar envio direto sem passar pelo robô, escreva um arquivo de incidente curto com sua assinatura explicando o porquê, e que o teto absoluto seja 5 usos no total (contando os 3 já feitos)?
**Se sim:** a política é escrita em 15 minutos e selada no conjunto de arquivos críticos do projeto.
**Se não:** registro a recusa no tracker e não escrevo a política.
**( ) sim com teto 5 ( ) sim sem teto numérico ( ) não**

### Decisão 4 — Revisor técnico externo horista
**Pergunta:** você aceita contratar um engenheiro/arquiteto externo por poucas horas para dar segundo olhar na recomendação de tecnologia antes de você aceitá-la?
**Se sim permanente:** 4 horas por mês, contínuo.
**Se sim só Bloco 2:** contratação pontual só para assinar o ADR-0001. Depois do Bloco 2, você decide se vale continuar.
**Se não:** eu registro em incident que o ADR-0001 vai ser aceito sem segundo humano técnico, e que você está consciente do risco.
**( ) sim permanente ( ) sim só Bloco 2 ( ) adiar ( ) não**

---

## 11. O que eu já vou executando enquanto você pensa (sem aguardar decisão)

**Apenas 2 itens** — os únicos que podem ser feitos honestamente agora, sem criar placeholders em cima de base que ainda não existe. Você apontou corretamente que os outros 2 que eu havia listado inicialmente (README e tabela de decisões técnicas) precisam esperar a fundação de produto e a decisão de tecnologia ficarem prontas, senão viram documentos fantasma — exatamente o erro que os auditores criticaram.

1. **Corrigir uma frase da constituição** — o parágrafo §5 menciona "R1 a R10" mas a constituição já tem até R12. É só trocar o número. Esforço: 2 minutos.
2. **Mover (ou marcar como histórico) o arquivo `roles-from-bmad.md`** — a decisão de cortar o BMAD já foi tomada e registrada, mas o arquivo ficou no projeto sem aviso. Esforço: 2 minutos.

**Total: ~10 minutos**. Depois disso, eu paro e espero suas 4 decisões da §0.

### O que eu NÃO vou fazer sem aguardar (e por quê)

- **README.md raiz** — criar hoje, sem `mvp-scope.md` (o que é) e sem decisão de tecnologia (como rodar), me obriga a escrever ou placeholder ("a definir") ou invenção. Placeholder é drift cultural — justamente o que os 3 auditores pediram para evitar. Este item **volta** como último passo do Bloco 1.5 (quando os outros itens já existirem) e recebe complemento após o Bloco 2.
- **Preencher `TECHNICAL-DECISIONS.md`** — o arquivo deveria listar ADRs reais. Os ADRs 0003-0006 só são reservados no item **2.7** do Bloco 2. Preencher agora resultaria em tabela com 2 linhas reais (template + política de MCP) e invenção. Este item **volta** dentro do Bloco 1.5, mas executado **depois** do item 2.7 ter criado os ADRs `status: proposed`.

---

## 12. Rastreabilidade para dentro da meta-auditoria #2

| Item deste plano | Justificativa na meta-auditoria |
|---|---|
| Bloco 1.5 inteiro | §2 Consenso #1 + #2 + #4 + #6 + #7 + #8 + #9 da meta-audit |
| Trilha #2 inteira | §2 Consenso #3 + #5 da meta-audit |
| Congelamento bypass | §3 Divergência #3 + §4 insight único Claude §K2 |
| Advisor externo | §2 Consenso #10 + §3 Divergência #1 |
| Itens 2.5, 2.6, 2.7 | §5 ajustes ao Bloco 2 |
| Item 3.4 (`/spec-review`) | §4 insight único Claude §K3 |
| Itens 4.6, 4.7, 4.8 | §5 ajustes ao Bloco 4 |
| Item 5.5 | §4 insight único Gemini §L#3 |
| Itens 6.3-6.8 | §5 ajustes ao Bloco 6 |
| Itens 7.4, 7.5 | §5 ajustes ao Bloco 7 |
| Itens consensuais X1-X4 | §0 seção "o que o PM não precisa decidir" |

---

**Fim do plano.**

**Próximo passo operacional:** PM responde as 4 decisões da §0 (ou equivalentemente §10) deste documento. Agente abre `docs/audits/progress/block-1.5-product-foundation.md` e inicia a execução.

**Enquanto isso:** agente executa a §11 (itens consensuais) sem aguardar aprovação.
