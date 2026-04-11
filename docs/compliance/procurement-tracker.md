# Tracker de contratação de consultores — Kalibrium

> **Status:** ativo, vivo. Item T2.14 da Trilha #2. Lista **quem** precisa ser contratado, **quando**, **quanto** custa, **qual é o plano B** se não entregar. Independente do Bloco 2.

## Consultores previstos

| ID | Consultor | Papel | Custo estimado | Data alvo contratação | Data alvo entrega | Status | Risco de atraso | Fallback documentado |
|---|---|---|---|---|---|---|---|---|
| M1-M6 | Consultor de metrologia (perfil RBC, GUM, ISO 17025) | Validar 50 casos GUM, revisar escopo MVP dos 4 domínios, assinar `metrology-policy.md` | R$ 7.200/ano (~R$ 600/mês amortizado) | 2026-05-15 | 2026-07-30 | Aguardando 1.5.1+1.5.7 commitados (gate atendido) | Médio (mercado pequeno, poucas pessoas qualificadas) | Se não entregar até 2026-08-15, o módulo `metrologia` vai para `out-of-scope.md` até conseguir novo consultor. Bloco 2 segue sem golden metrologia, com lição aprendida em retrospectiva. |
| F1-F5 | Consultor fiscal (NFS-e multi-UF, reforma tributária) | Validar 30 casos NF-e/NFS-e/ICMS, assinar `fiscal-policy.md`, cobrir 5 municípios-alvo | R$ 6.000/ano (~R$ 500/mês amortizado) | 2026-05-15 | 2026-07-30 | Aguardando 1.5.1+1.5.7 commitados (gate atendido) | Médio | Se não entregar até 2026-08-15, o módulo `fiscal` começa com 2 municípios ao invés de 5; ajuste registrado em `out-of-scope.md`. |
| DPO | Data Protection Officer (LGPD) | Revisar T2.1-T2.5 (threat-model, lgpd-base-legal, dpia, rot, incident-response), assinar como DPO responsável | R$ 4.800/ano (~R$ 400/mês amortizado) | 2026-06-15 | 2026-08-30 | Não iniciado (decisão #2 do PM — segundo passo) | Alto (mercado escasso para DPO fracionário) | Se não entregar até 2026-09-30, itens T2.1-T2.5 permanecem em `draft-awaiting-dpo` e o primeiro tenant real não é onboardado até a contratação fechar. Gate permanente. |
| Advogado LGPD | Especialista em contratos de operador e DPIA | Revisar `contrato-operador-template.md` (T2.9) — NÃO é o DPO | R$ 2.500 pontual (não mensal) | 2026-06-30 | 2026-07-31 | Aguardando draft-awaiting-dpo de T2.9 | Baixo (mercado maior) | Se não fechar até 2026-08-30, usar contrato modelo de entidade setorial (ABRAD ou similar) como base e abrir incident de cobertura jurídica insuficiente. |
| Advisor técnico | Revisão independente do ADR-0001 (Bloco 2) | Parecer de até 8h sobre a escolha da stack | R$ 2.100 pontual (amortizado ~R$ 350/mês por 6 meses) | 2026-05-30 | 2026-06-30 | Não iniciado (decisão #4 PM) | Médio (precisa perfil sênior em SaaS multi-tenant brasileiro) | Se não fechar até 2026-06-30, ADR-0001 entra em `status: provisional` e é revisitado 90 dias depois com dados reais de operação. |

## Regras do tracker

1. **Atualização obrigatória:** sempre que o PM tem uma conversa nova com candidato, atualizar a coluna `status`.
2. **Alerta automático:** 30 dias antes da `data alvo contratação`, registrar em `docs/reports/procurement-alert-<consultor>-YYYY-MM.md`.
3. **Fallback não é sugestão — é compromisso.** Se a data alvo passa, a ação de fallback executa sem discussão.
4. **Contrato assinado:** quando qualquer consultor assina, criar `docs/contracts/<consultor>-YYYY-MM-DD.md` com termo de confidencialidade e escopo.

## Dependências

- `laboratorio-tipo.md` (1.5.7) — contexto canônico que todos os consultores recebem como input.
- `ideia-v1.md` (1.5.1) — visão do produto que completa o contexto.
- `operating-budget.md` (1.5.9) — teto mensal que justifica os valores acima.
- `out-of-scope.md` (1.5.10) — destino dos módulos em caso de fallback acionado.
- `ia-no-go.md` (T2.15) — lista dos módulos que exigem consultor obrigatório.
