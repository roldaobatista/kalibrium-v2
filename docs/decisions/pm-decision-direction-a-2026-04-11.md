# Decisão do PM — Direção A: oficina 100% antes do produto

**Data:** 2026-04-11
**Decisor:** Product Manager (único humano ativo — `CLAUDE.md §3.1`)
**Sessão:** 2026-04-11 (mesma sessão da aceitação dos Blocos 8/9)
**Formato da pergunta:** 3 direções possíveis apresentadas em linguagem de produto. Resposta literal do PM: `direcao a`.

---

## 1. O que foi perguntado

Em resposta à dúvida "já criamos o PRD? os documentos necessários antes dessa fase?", foi apresentado ao PM o estado real do repositório em linguagem de produto:

- ✅ Descoberta de produto: ~80% feita (personas, journeys, laboratorio-tipo, mvp-scope, nfr, pricing-assumptions, glossary-pm, ideia-v1, compliance de LGPD/metrologia/fiscal/ICP-Brasil)
- ❌ PRD preenchido: zero (só existe `docs/templates/prd.md` como formato)
- ❌ Specs de módulo: zero (pasta `specs/` vazia)
- ❌ Código do produto: zero (`src/`, `apps/`, `services/` não existem)
- ❌ Stack decidida: não
- 🟡 Oficina (harness): ~20% (Bloco 1 completo, Blocos 2-7 + 8-9 pendentes)

E foram apresentadas 3 direções em linguagem de produto:

- **A** — terminar oficina 100% antes de escrever qualquer PRD ou spec
- **B** — começar slice de produto agora, com oficina incompleta
- **C** — meio-a-meio: decidir stack, começar produto, oficina evolui conforme necessário

## 2. Decisão

**Direção A: oficina 100% antes do produto.**

O PM aceitou que os próximos ~12-15 sessões novas de Claude Code (estimativa em granularidade de sessão, não em tempo cronológico) sejam gastas **exclusivamente** em terminar o harness antes que qualquer PRD de produto seja escrito, qualquer spec técnica seja criada, qualquer código de produto seja implementado.

### 2.1 Escopo que passa a estar "dentro" da Direção A

Em ordem de execução obrigatória:

1. **Decidir a tecnologia** (Bloco 2 do tracker — ADR-0001 via `/decide-stack`)
2. **Garantir que testes automáticos rodam de verdade** (Bloco 3 — execução real no verifier, não leitura de arquivo)
3. **Ensinar o agente a te explicar tudo em português claro** (Bloco 4 — tradutor R12 real + pausa dura + canal duplo)
4. **Colocar um "juiz externo" na nuvem** (Bloco 5 — CI GitHub Action + remover admin bypass)
5. **Adicionar defesas contra erros críticos** (Bloco 6 — domain-expert, métricas, mcp-check real, golden tests de compliance)
6. **Re-auditoria final antes de liberar produto** (Bloco 7 — em sessão nova + smoke test + go/no-go formal de Dia 1)
7. **Itens dos Blocos 8 e 9** (extensão dos guias externos) — rodam em paralelo conforme as dependências forem sendo cumpridas

### 2.2 Escopo que passa a estar "fora" da Direção A (por enquanto)

Tudo que tem "produto" no nome, incluindo:
- Escrever o primeiro PRD de qualquer módulo do MVP
- Criar o primeiro arquivo em `specs/`
- Escrever a primeira linha de código em `src/`
- Contratar qualquer serviço ligado a infraestrutura de produto (hospedagem, CDN, banco gerenciado)
- Tocar qualquer feature específica de calibração/metrologia no código
- Modelagem de dados do domínio

Tudo isso fica explicitamente **bloqueado** até o go/no-go do Dia 1 (última etapa da oficina).

### 2.3 Trilha paralela que **não** é bloqueada pela Direção A

Estas atividades podem e devem acontecer em paralelo, fora do Claude Code, tocadas pelo PM manualmente:

- **RFP + contratação do consultor de metrologia** (golden tests GUM/ISO 17025)
- **RFP + contratação do consultor fiscal** (golden tests NF-e/NFS-e/ICMS)
- **Contratação do DPO fracionário** (assinar 5 arquivos em `draft-awaiting-dpo`)
- **NDA + proposta do advisor técnico externo** (ação manual A4)
- **Selar `docs/harness-limitations.md` no MANIFEST via relock manual** (ação manual C4)
- **Gate de `advisor-review` no `pre-commit-gate.sh` via relock manual** (ação manual A3)

## 3. O que muda a partir de agora

### 3.1 No ritmo do agente

Toda próxima sessão Claude Code tem **apenas** 2 tipos legítimos de trabalho:
- Avançar a oficina (Blocos 2-7 + extensões 8-9, na ordem do tracker)
- Auditorias focadas do Bloco 9 em sessões fresh

Qualquer outro tipo de trabalho — incluindo "ah, vou só esboçar o primeiro PRD enquanto a stack não é decidida" — passa a ser **violação de escopo** e o agente deve se recusar até a oficina terminar.

### 3.2 No que o PM vê

Durante o período da Direção A, o PM **não vai ver tela de produto**, **não vai ver código sendo escrito**, **não vai ver feature nova**. O que ele vai ver é:

- ADRs fechados (decisões técnicas traduzidas em linguagem de produto via `/decide-stack`)
- Hooks + gates + defesas ganhando robustez
- Relatórios de verificação do harness
- Checklists avançando
- Eventualmente, no final, um "go" formal do Dia 1

Se em alguma sessão o PM sentir angústia de "cadê meu produto?", esta decisão é a resposta: **ele escolheu conscientemente que o produto só começa depois do go/no-go**. Reverter antes disso precisa ser uma decisão formal nova (novo arquivo em `docs/decisions/`).

### 3.3 Nas ações manuais do PM

As 4 ações manuais abertas em `docs/reports/pm-manual-actions-2026-04-10.md` (C4, A3, A4, DPO) passam a ser **bloqueantes críticas** da Direção A. Sem elas:

- C4 deixa `docs/harness-limitations.md` editável pelo agente (fura R1 nuances importantes)
- A3 deixa brecha no gate de revisão humana em categorias sensíveis
- A4 deixa o projeto sem o segundo par de olhos técnico externo
- DPO deixa 5 políticas de compliance em estado "draft-awaiting-dpo" — e sem DPO, a trilha paralela de compliance não pode avançar

Se essas 4 ações não forem destravadas, **a oficina nunca chega a 100%** e a Direção A vira prisão.

## 4. Reafirmação das restrições operacionais

A Direção A não relaxa nada que já estava valendo:

1. Sealed files continuam selados.
2. Meta-auditorias continuam obrigatoriamente em sessão nova (regra `memory/feedback_meta_audit_isolation.md`).
3. R9 zero bypass continua.
4. Tradutor R12 continua obrigatório em toda saída pro PM.
5. Ordem dos blocos 2→3→4→5→6→7 é obrigatória.
6. Admin bypass continua congelado em 4/5, só P0 assinado.

## 5. Próxima ação única imediata

**Abrir sessão nova de Claude Code e rodar `/decide-stack`.** Isso vai:

- Gerar `docs/adr/0001-stack.md` com 2 ou 3 alternativas de tecnologia lado a lado, cada uma traduzida pra linguagem de produto.
- Apresentar ao PM uma tabela "Alternativa A vs B vs C" com prós, contras, risco, custo, facilidade de ler código depois.
- Pedir ao PM a resposta na forma "aceito A" ou "aceito B" ou "aceito C" ou "nenhuma dessas, me explique D".
- O que o PM aceitar vira `status: accepted` no ADR-0001 e destrava os próximos passos da oficina.

Nenhum desses passos toca arquivo selado. Nenhum exige relock. Nenhum exige decisão técnica do PM que ele não consiga fazer com a tradução R12.

## 6. Como reverter esta decisão

Se em qualquer momento futuro o PM quiser sair da Direção A (por exemplo, decidir que precisa ver tela de produto antes de terminar a oficina toda), a reversão acontece assim:

1. Criar novo arquivo `docs/decisions/pm-decision-direction-change-YYYY-MM-DD.md`
2. Declarar que a Direção A está sendo substituída por B ou C
3. Listar que itens da oficina **obrigatoriamente** continuam como gate de qualquer produto (provavelmente Bloco 1 já fechado + Bloco 2 stack decidida + Bloco 3 testes reais — esses 3 são não-negociáveis mesmo na rota mais permissiva)
4. O novo documento vira o override do atual

**Esta decisão aqui não é editada retroativamente.** O arquivo de reversão é gravado do lado, para auditoria de por que o PM mudou de ideia.
