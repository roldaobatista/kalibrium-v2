# Hipóteses de precificação — Kalibrium

> **Status:** ativo. Item 1.5.15 do plano da meta-auditoria #2 (Bloco 1.5 Nível 3, item novo por cobertura). Separado de `operating-budget.md` porque este arquivo é **receita**, não custo. Depende de `mvp-scope.md` (para o laboratório-alvo) e de `personas.md` (para quem é o comprador). Todas as hipóteses aqui são **explicitamente não validadas** — o objetivo é ter uma referência única para discussão e teste de mercado, não uma tabela final.

## 1. Premissas de segmentação

- **Comprador real:** Persona 1 (Marcelo, gerente/sócio do laboratório). Ele decide a compra e assina o contrato. Técnico (Juliana) e cliente final (Rafael) são usuários mas não compram.
- **Sensibilidade a preço:** laboratório brasileiro pequeno-médio é sensível a custo fixo recorrente — mais do que a custo variável por calibração. Preferência observada: previsibilidade sobre upside.
- **Ticket médio de sistema concorrente (referência externa):** soluções legadas de gestão de laboratório de calibração no Brasil praticam mensalidades na faixa de R$ 400 a R$ 2.500/mês, sem transparência pública e com variação grande por negociação.

## 2. Candidatas de modelo de cobrança

| Modelo | Como cobra | Vantagem | Desvantagem | Avaliação inicial |
|---|---|---|---|---|
| **A. Mensal fixo por tenant** | Mensalidade única por laboratório, sem limite de calibrações | Previsível para Marcelo, simples de explicar | Cliente grande paga igual ao pequeno | **Preferido no MVP** |
| **B. Mensal fixo com tiers por volume** | 3 faixas (até 600 / 601-1.200 / 1.201-2.000 calibrações/mês) | Captura valor do cliente grande | Complica a venda; Marcelo tem que estimar volume | Aceitável após 6 meses |
| **C. Fixo + variável por calibração** | Base pequena + R$ X por certificado emitido | Ajusta ao uso real | Rejeitada: cria reação adversa ("quanto mais eu trabalho, mais caro fica") | **Rejeitada** |
| **D. Por usuário ativo do laboratório** | R$ Y por usuário/mês | Simples de entender | Desincentiva adicionar usuário = limita adoção | Rejeitada |
| **E. Anual com desconto** | Contrato de 12 meses com 15% de desconto contra mensal | Melhora previsão de caixa | Exige confiança inicial que o MVP ainda não construiu | Aceitável no 2º ano |

**Decisão inicial:** modelo **A (mensal fixo por tenant)** no MVP, com migração para **B (tiers)** prevista quando houver ≥ 10 tenants pagantes e dados reais de volume. Modelo E (anual) entra como opção voluntária a partir do 12º mês.

## 3. Preço-alvo (modelo A)

- **Tier único do MVP:** R$ 899/tenant/mês, sem limite de calibrações dentro do volume esperado do laboratório-tipo (até 2.000 calibrações/mês).
- **Primeiros 3 tenants pagantes:** **R$ 599/mês** como preço de lançamento (desconto de ~33%) com contrato de 3 meses. Recuperação do preço cheio no mês 4 da relação.
- **Nota contratual:** preço de lançamento é um contrato bilateral com cláusula de congelamento por 12 meses — o laboratório aceita ser o parceiro de validação inicial, a empresa garante o preço baixo por um ano em troca de feedback estruturado.
- **Reajuste anual:** IPCA ou índice equivalente, com teto de 8% ao ano. Explicitado no contrato para remover ansiedade de Marcelo.

## 4. Sensibilidade a preço (hipóteses para teste)

- **H-P1.** A faixa R$ 599 a R$ 1.299 é a janela aceitável para Marcelo no modelo mensal fixo sem debate. Abaixo de R$ 599, Marcelo suspeita da qualidade. Acima de R$ 1.299, Marcelo pede desconto como reflexo.
- **H-P2.** Desconto de primeiro ano (R$ 599) acelera o fechamento em até 2x comparado com oferta de preço cheio, e tem taxa de renovação para o preço cheio maior que 70% quando o MVP entrega a Jornada 1 dentro da métrica de sucesso.
- **H-P3.** Cliente que começa no modelo mensal aceita migrar para contrato anual com 15% de desconto após ver 3 meses de operação estável.
- **H-P4.** Laboratório pequeno (até 600 calibrações/mês) tem elasticidade maior — provável que aceite R$ 499-R$ 699 no lançamento.

Nenhuma das quatro foi testada. São entradas para o discovery inicial.

## 5. Comparação com concorrentes conhecidos

O mercado brasileiro de sistema de gestão para laboratório de calibração é fragmentado e com pouca transparência pública de preço. A lista abaixo é referencial e precisa ser revisada anualmente.

- **Sistemas legados nacionais de cobertura ampla.** Faixa de R$ 1.200 a R$ 2.500/mês, setup entre R$ 3.000 e R$ 10.000. Normalmente com venda consultiva e customização obrigatória.
- **Soluções horizontais de ERP + módulo customizado.** R$ 800 a R$ 1.800/mês de licença + custo alto de customização inicial, sem profundidade em metrologia.
- **Planilhas avançadas + Word + portal fiscal do município.** "Custo zero" aparente, mas não entrega rastreabilidade para auditoria RBC — o custo real fica escondido em hora do gerente.

O Kalibrium a R$ 899/mês sem setup e sem contrato obrigatório de longo prazo posiciona-se **claramente abaixo dos sistemas legados** e **claramente acima da planilha**, ocupando a faixa intermediária inexistente no mercado hoje.

## 6. Sensibilidade e break-even (referência cruzada)

O custo mensal operacional total está em `docs/finance/operating-budget.md`: **R$ 8.960/mês** somando harness + produto + consultoria amortizada + custo do PM.

- **Cenário A, preço cheio R$ 899:** break-even em ~10 tenants ativos simultâneos.
- **Cenário B, preço de lançamento R$ 599:** break-even em ~15 tenants ativos simultâneos.
- **Cenário C, mistura 3 primeiros no R$ 599 + resto no R$ 899:** break-even em ~11 tenants.

Nenhum dos três cenários considera taxa de churn ainda desconhecida. Quando a taxa de renovação dos 3 primeiros tenants for observada, esses números são recalculados e o arquivo recebe nova revisão.

## 7. Itens que este arquivo NÃO decide

- **Plataforma de cobrança.** Emissão real da NFS-e acontece pelo módulo fiscal (FIS). Integração com gateway para cartão de crédito ou PIX recorrente é decisão posterior — provavelmente ADR específico no Bloco 3 ou depois.
- **Pacote de serviços adicionais.** Treinamento, migração de dados do laboratório antigo, consultoria de acreditação não são precificados aqui. Entram como adicional pontual, cobrado por hora.
- **Desconto para cliente educacional.** Laboratórios ligados a universidade ou instituto federal podem receber política específica. Fica para decisão individual, não entra como linha de tabela.

## 8. Revisão

- **A cada novo tenant pagante:** registro em `docs/audits/progress/meta-audit-tracker.md` do preço efetivamente praticado e do desvio em relação a este arquivo.
- **Trimestral:** revisão formal das 4 hipóteses H-P1 a H-P4 contra dados reais.
- **Quando o 5º tenant fechar:** revisão do preço de lançamento — decisão binária de manter R$ 599 ou recuperar R$ 899 nos próximos.
