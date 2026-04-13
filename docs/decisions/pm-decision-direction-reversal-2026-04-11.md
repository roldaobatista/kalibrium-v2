# Decisão do PM — Reversão da Direção A

**Data:** 2026-04-11
**Decisor:** Product Manager (único humano ativo)
**Sessão:** 2026-04-11 (mesma sessão que criou `pm-decision-direction-a-2026-04-11.md` horas antes)
**Substituição de:** `docs/decisions/pm-decision-direction-a-2026-04-11.md`
**Formato da decisão:** o PM explicitamente instruiu o agente a "tomar as melhores decisões" após ficar confuso com o excesso de jargão técnico. Esta decisão é tomada sob essa delegação explícita, registrada aqui para rastreabilidade.

---

## 1. Por que a Direção A foi revertida

A Direção A original — *"oficina 100% pronta antes de qualquer PRD, spec ou linha de código de produto"* — era **incoerente** com o fluxo canônico de Spec-Driven Development documentado em `docs/reference/harness-sdd-knowledge-base.md`:

- **T08 do knowledge base** define o fluxo canônico como: `constituição → spec funcional → clarificação → plano técnico → contratos/testes → tasks → implementação`.
- **T24 do knowledge base** explicita as 3 camadas de especificação: Product Spec → Technical Spec → Task Specs. Sem a Product Spec (PRD), as camadas seguintes ficam sem base.
- Decidir a stack (oficina) sem o PRD era fisicamente inviável: a escolha técnica depende dos requisitos não-funcionais numéricos (RNF-001 a RNF-016 do PRD), do perfil de carga multi-tenant e do modelo de NFS-e multi-município.

Em termos simples: **a Direção A invertia produto e oficina de um jeito que não funcionava**. O PM identificou isso na mesma sessão, perguntou *"e o PRD?"*, e o agente reconheceu o erro.

## 2. Nova direção — em uma frase

**Oficina mínima suficiente para o próximo passo de produto, nunca oficina preventiva.**

Na prática:

- O harness (oficina) continua existindo e sendo endurecido, mas **só onde o próximo slice de produto realmente precisa**.
- O produto avança em paralelo, desde que o harness esteja **correto para aquele passo específico**.
- Nenhum dos 7 Blocos do tracker original é mais "bloqueante" por princípio. Cada um se justifica pelo slice concreto que está destravando.

## 3. O que isso muda na prática

### 3.1. O que agora é permitido (e antes estava bloqueado pela Direção A)

- **Escrever e consolidar o PRD de produto.** Feito nesta mesma sessão: `docs/product/PRD.md`.
- **Rodar `/decide-stack`** com o PRD como entrada (próxima sessão).
- **Começar o primeiro slice de produto** assim que stack decidida, mesmo com Blocos 3/4/5/6/7 ainda pendentes.
- **Iterar produto e harness em paralelo**, priorizando o slice.

### 3.2. O que continua valendo (sem mudança)

- **Sealed files continuam selados.** Nenhum relax nas travas do Bloco 1. Segurança de auto-modificação do harness é permanente.
- **Zero bypass de gate (R9).** Pre-commit, verifier, reviewer continuam obrigatórios.
- **Tradutor R12 obrigatório** em toda saída ao PM. Vocabulário técnico é proibido nas respostas.
- **Meta-auditorias em sessão nova.** Auditorias do próprio harness continuam obrigatoriamente isoladas.
- **Admin bypass congelado em 4/5.** Restam 1 bypass, só em P0 assinado.
- **Git identity baseline, hash-lock de hooks, MANIFEST.sha256** — tudo como está.
- **Regras R1-R12** da constitution — inalteradas.

### 3.3. O que fica reordenado

A ordem obrigatória dos Blocos 2 → 3 → 4 → 5 → 6 → 7 do tracker original **deixa de ser rígida**. Os blocos continuam existindo como **trilhas de endurecimento**, mas:

- **Bloco 2 (stack)** — continua como pré-requisito do primeiro slice (impossível codar sem stack).
- **Bloco 3 (testes reais)** — vira pré-requisito do primeiro slice que tenha AC críticos (primeiro slice de MET, por exemplo). Pode não ser pré-requisito do primeiro slice de TEN (cadastro de tenant), que pode rodar em modo "WARN + review humano" antes de ter testes reais mecânicos.
- **Bloco 4 (tradutor R12 real)** — já é P1 e vira paralelo a tudo.
- **Bloco 5 (CI externo)** — vira pré-requisito do primeiro merge em `main` que afete código de produto.
- **Bloco 6 (defesas adicionais)** — vira pré-requisito do primeiro slice em categorias críticas (metrologia, fiscal, compliance).
- **Bloco 7 (re-auditoria go/no-go)** — vira pré-requisito do **primeiro cliente pagante**, não do primeiro código escrito.

## 4. O novo caminho do PM — referência única

**O PM a partir desta decisão lê apenas:** `docs/product/CAMINHO.md`. Esse arquivo é a **única tradução em linguagem de produto** do que o agente está fazendo. Tudo mais (tracker, blocos, knowledge base, external plan) continua existindo mas é ferramenta interna do agente.

## 5. O que o PM precisa fazer para honrar esta reversão

Absolutamente nada de imediato. A próxima ação do PM é a mesma que seria na Direção A:

> Abrir sessão nova do Claude Code e pedir `/decide-stack`.

A diferença é que agora isso destrava o produto **logo na sessão seguinte**, não daqui a 12 sessões.

## 6. Rastreabilidade e reversão desta reversão

- **Arquivos afetados:**
  - `docs/decisions/pm-decision-direction-a-2026-04-11.md` — original, continua existindo como histórico.
  - `docs/decisions/pm-decision-direction-reversal-2026-04-11.md` — este arquivo, a nova direção.
  - `docs/product/PRD.md` — novo, consolida os 8 arquivos de descoberta.
  - `docs/product/CAMINHO.md` — novo, arquivo-único de leitura do PM.
  - `memory/project_direction_a.md` — atualizado para marcar a Direção A como revertida.
- **Regra para reverter esta reversão:** criar novo arquivo `docs/decisions/pm-decision-direction-change-YYYY-MM-DD.md`. Não editar este arquivo in-place.

## 7. Como o agente se comporta a partir daqui

Em toda sessão nova Claude Code, ao validar o harness e carregar contexto, o agente deve:

1. Ler `docs/product/CAMINHO.md` primeiro.
2. Ler `docs/product/PRD.md` como constituição de produto.
3. Ler `docs/constitution.md` como constituição de harness.
4. Sempre que for responder ao PM, passar pelo tradutor R12.
5. Nunca mencionar "Bloco N", "sealed file", "MANIFEST", "sub-agent" em resposta ao PM sem explicação em português claro.
6. Se o PM disser "R12", o agente para, reescreve a última resposta em linguagem de produto, e continua.

---

## 8. Adendo crítico — segurança máxima + intervenção humana mínima (registrado ainda na sessão 2026-04-11)

Logo após esta reversão ser escrita, o PM emitiu instrução complementar **explícita e vinculante**:

> "Todo o ambiente tem que ser desenhado para ter o mínimo possível de intervenção humana, só quando obrigado, tipo iniciar uma nova sessão e etc. Eu não sei nada de código, e etc, mas o sistema tem que ser seguro, o ambiente tem que reduzir a zero o risco do código ser gerado errado."

Esta instrução **tem precedência** sobre qualquer afrouxamento que a reversão acima possa ter implicado. Interpretação vinculante:

### 8.1. Quando o humano OBRIGATORIAMENTE intervém (lista fechada, exaustiva)

O PM intervém somente nestas 5 situações. **Nenhuma outra.**

1. **Iniciar uma sessão nova do Claude Code** (abrir o cliente, invocar comando).
2. **Responder decisões de produto em linguagem de produto** (sim/não, aceito A ou B, faltou X, prefere Y) — **nunca** decisões técnicas.
3. **Testar tela/feature entregue** — clicar, ver, validar visualmente em linguagem de produto.
4. **Executar as 4 ações manuais administrativas** (C4, A3, A4, DPO — listadas em `docs/reports/pm-manual-actions-2026-04-10.md`). Essas dependem de terminal externo + relock ou de negociação contratual.
5. **Incidentes P0 assinados** (último bypass permitido, só em emergência crítica com assinatura explícita).

### 8.2. Quando o humano NUNCA intervém (proibido pelo harness)

O agente **não pode** pedir ao PM:

- Revisar diff de código
- Analisar stack trace
- Escolher entre alternativas técnicas sem tradução R12
- Validar teste unitário ou resultado de lint
- Decidir sobre migração de banco, schema, índice, query
- Avaliar mensagem de erro
- Interpretar qualquer output JSON, YAML, log ou saída de hook

Se o agente precisar de algo assim, **é falha do harness e o agente deve escalar internamente** (verifier + reviewer + pausa dura R6/R7), nunca passar a bola para o PM.

### 8.3. "Zero risco de código errado" — o que isso significa na prática

A frase "reduzir a zero o risco do código ser gerado errado" é interpretada como regra vinculante:

1. **Nenhum código vai para `main` sem ter passado por verifier + reviewer (independentes) + CI externo verde.** Regra já existente (R3 + R4 + R11), agora elevada a condição inegociável de merge.

2. **Nenhum slice de categoria crítica começa sem a oficina daquela categoria estar 100%.** Categorias críticas: `metrology`, `fiscal`, `compliance`, `security`, `numerical_correctness`, `adr_compliance`, `simplicity`. Lista em `docs/policies/r6-r7-policy.md`. Se um slice toca código de MET (metrologia), golden tests GUM têm que estar no ar **antes** de o slice começar.

3. **Slices de categoria não-crítica** (ex: TEN cadastro de tenant, OPL dashboard) **podem começar com oficina parcial**, mas ainda assim exigem:
   - `post-edit-gate.sh` rodando com execução real de testes
   - `verify-slice` + `review-pr` aprovados
   - CI externo verde antes do merge
   - Nenhum bypass de gate

4. **Pausa dura obrigatória** em qualquer categoria crítica. O PM **não pode** aprovar override. Se o harness rejeitar 2x, o slice é abortado e registrado como incidente.

5. **Todo merge dispara auditoria automática de drift** (guide-auditor em sub-agent isolado). Se guide-check encontrar drift pós-merge, incidente automático é criado e push futuro fica bloqueado até resolução.

### 8.4. O que isso muda no caminho do produto

- **Etapa 2 (escolher stack)** — continua como próximo passo. Sem mudança.
- **Etapa 3 (primeira feature)** — sugestão inicial era TEN (cadastro de tenant). **TEN é categoria não-crítica**, então pode começar com oficina parcial. Continua sendo a escolha certa.
- **Qualquer slice futuro em MET/FIS/CMP** — vai exigir que golden tests da categoria estejam no ar **antes** de o slice começar. Isso pode reordenar a lista "provisória" de features em `CAMINHO.md §Etapa 4`.

A reordenação não é decisão do PM. É consequência mecânica da restrição de segurança. O agente faz automaticamente.

### 8.5. Consequência para a trilha paralela de compliance

As 4 ações manuais do PM (C4/A3/A4/DPO) + as 2 contratações de consultor (metrologia + fiscal) tornam-se **bloqueantes do primeiro slice em categoria crítica**. Sem golden tests metrologia aprovados pelo consultor, **zero código de calibração (MET) pode ser escrito**. Sem golden tests fiscais aprovados pelo consultor, **zero código de NFS-e (FIS) pode ser escrito**.

**Cadastro de tenant (TEN), dashboard operacional (OPL), fluxo de trabalho não-metrológico (FLX parcial)** podem começar sem as contratações, porque são categorias não-críticas.

### 8.6. Revisão deste adendo

Este adendo **só é reversível via novo arquivo de decisão em `docs/decisions/`** — não editando este arquivo in-place. A reversão exige assinatura explícita do PM com consciência do trade-off ("estou aceitando aumentar o risco de código errado em troca de velocidade X").
