# Orçamento operacional — Kalibrium

> **Status:** ativo. Item 1.5.9 do plano da meta-auditoria #2 (Bloco 1.5 Nível 3). **Gate:** `scripts/decide-stack.sh` lê o teto de infra deste arquivo e rejeita candidata que exceda. Este arquivo é **custo**. A contraparte receita vive em `docs/product/pricing-assumptions.md` (item 1.5.15).

## Princípio

Duas colunas **sempre separadas**: **harness** (o robô que constrói o produto) e **produto** (o runtime que serve os clientes). As duas colunas somam o custo total mensal da operação, mas precisam ser discutidas separadamente porque entram em ADRs diferentes, escalam diferentes e têm natureza contábil diferente.

## Tabela-mestre mensal (referência 2026-04)

Valores em reais (R$) por mês, salvo onde anotado. Alvo do MVP pré-primeiro tenant.

| Categoria | Harness (construir) | Produto (rodar) | Total |
|---|---|---|---|
| Tokens do agente (Anthropic console) | R$ 1.250 | R$ 0 | R$ 1.250 |
| Infra VPS (Hostinger) | R$ 80 (staging compartilhado) | R$ 800 (produção) | R$ 880 |
| Armazenamento de PDFs e backup | R$ 0 | R$ 120 | R$ 120 |
| Domínio + TLS | R$ 15 | R$ 15 | R$ 30 |
| Consultoria pontual metrologia | R$ 600 (amortizada) | R$ 0 | R$ 600 |
| Consultoria pontual fiscal | R$ 500 (amortizada) | R$ 0 | R$ 500 |
| Consultoria pontual DPO | R$ 400 (amortizada) | R$ 0 | R$ 400 |
| Advisor técnico externo (Bloco 2) | R$ 350 (amortizada) | R$ 0 | R$ 350 |
| Custo humano do PM (40h/mês) | R$ 4.000 | R$ 0 | R$ 4.000 |
| Ferramentas de desenvolvimento (IDE, Git hosting) | R$ 60 | R$ 0 | R$ 60 |
| Margem de segurança (10%) | R$ 676 | R$ 94 | R$ 770 |
| **Total mensal** | **R$ 7.931** | **R$ 1.029** | **R$ 8.960** |

## Observações por linha

### Tokens do agente (harness)
- Orçamento de R$ 1.250/mês equivale a aproximadamente US$ 250 (câmbio referencial R$ 5,00/US$). Alinhado com **RNF-013**.
- Composição: sub-agents (architect, ac-to-test, implementer, verifier, reviewer) + meta-auditorias + retros. O produto em si não chama LLM na rota crítica do MVP, portanto a coluna produto fica zero.
- Se o ritmo de slices ultrapassar 4/semana por um período sustentado, a linha cresce linearmente. Alerta em `docs/audits/progress/meta-audit-tracker.md` quando o consumo do mês passar dos R$ 1.125 (90% do teto).

### Infra VPS (produto)
- R$ 800/mês é o **teto contratual do RNF-012**. Plano intermediário do Hostinger (ou equivalente quando a decisão de fornecedor for revisitada) suficiente para suportar até 50 tenants ativos conforme RNF-006.
- Se o candidato de stack no ADR-0001 precisar de plano superior, a candidata é rejeitada pelo `/decide-stack`.

### Infra VPS (harness)
- R$ 80/mês cobre o ambiente de staging compartilhado onde os verifiers/reviewers podem spawnar worktree de teste.

### Armazenamento de PDFs e backup
- RNF-015 estima 2,4 TB/ano para 50 tenants. Em base mensal com backup off-site semanal, bucket externo compatível com S3 custa ~R$ 120/mês.

### Consultoria pontual
- Os consultores de metrologia, fiscal e DPO são **horistas pontuais**, não contratados permanentes. O valor na tabela é uma amortização mensal do contrato previsto na Trilha paralela / Trilha #2.
- Metrologia: ~R$ 600/mês ≈ R$ 7.200/ano ≈ 40h do consultor RBC no ano — suficiente para revisão dos 50 casos GUM (item M3) + parecer sobre o escopo do MVP.
- Fiscal: ~R$ 500/mês ≈ R$ 6.000/ano ≈ 30h de consultor fiscal nos 5 municípios iniciais.
- DPO: ~R$ 400/mês ≈ R$ 4.800/ano ≈ 20h de advogada/o especialista em LGPD.

### Advisor técnico externo (Bloco 2)
- Única finalidade: revisão técnica independente do ADR-0001 conforme decisão #4 do PM. Contrato pontual de R$ 2.100 para um parecer de até 8h + NDA. Dividido em 6 meses = R$ 350/mês.

### Custo humano do PM
- Estimativa conservadora do custo de oportunidade do tempo do Product Manager dedicado ao projeto (40h/mês em taxa de mercado de R$ 100/h). É custo real, mesmo sendo o próprio sócio.

### Margem de segurança
- 10% sobre o subtotal. Absorve variação cambial de token, ajuste de VPS e consultoria esporádica urgente. Não é folga para escopo — gastar margem de segurança gera retrospectiva.

## Gate para o `/decide-stack`

O `/decide-stack` consome duas linhas deste arquivo como input duro:
1. **Teto da coluna Produto / Infra VPS:** R$ 800/mês. Candidata de stack cuja estimativa de infra ultrapasse é rejeitada.
2. **Teto da coluna Harness / Tokens do agente:** R$ 1.250/mês. Stacks que demandem sub-agents adicionais (por exemplo, um agente de tradução de ORM para SQL) precisam caber dentro desse teto.

## Break-even (referência cruzada com `pricing-assumptions.md`)

Assumindo modelo de cobrança do `pricing-assumptions.md` com ticket mensal médio-alto de **R$ 899/tenant/mês** e retenção alvo de 24 meses, o número mínimo de tenants pagantes simultâneos para o Kalibrium sair do vermelho é:

- **Cenário conservador:** R$ 8.960 / R$ 899 = ~10 tenants.
- **Cenário otimista (ticket R$ 1.199):** R$ 8.960 / R$ 1.199 = ~8 tenants.

Dez tenants pagantes é o marco de "break-even operacional do harness + produto". Abaixo disso, o projeto consome caixa do sócio. Acima disso, paga por si. Esse número é o gatilho de revisão do pricing a cada 90 dias a partir do primeiro tenant.

## Frequência de revisão

- **Mensal:** checagem de consumo real vs tetos — especialmente tokens do agente e infra VPS.
- **Trimestral:** revisão da margem, do câmbio e das horas de consultoria amortizadas.
- **Quando um teto é estourado:** incidente em `docs/incidents/budget-overrun-YYYY-MM.md` e retrospectiva obrigatória.
