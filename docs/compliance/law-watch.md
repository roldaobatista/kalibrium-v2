# Monitoramento legislativo — Kalibrium

> **Status:** ativo, processo recorrente. Item T2.12 da Trilha #2. Define **como** o projeto monitora mudanças legais e regulatórias que possam exigir atualização de código ou de policy. Complementado pela cadência calendarizada em `docs/compliance/revalidation-calendar.md` (T2.16).

## 1. Fontes oficiais monitoradas

| Fonte | O que monitorar | Frequência mínima |
|---|---|---|
| Diário Oficial da União (DOU) — Seção 1 | Portarias do INMETRO, resoluções da ANPD, decretos que impactem LGPD ou calibração | Semanal |
| Portal do INMETRO | Resoluções sobre RBC, vocabulário metrológico, aprovações de modelo | Semanal |
| Portal da Cgcre | Critérios gerais da acreditação ISO/IEC 17025, requisitos específicos RBC | Mensal |
| Portal da ANPD | Pareceres, resoluções, orientações sobre LGPD | Semanal |
| Portais da SEFAZ das UFs-alvo (SP, MG, RS, PR) | NFS-e, ICMS quando aplicável, prazos de adesão a novas versões | Mensal |
| Portal da Receita Federal | Reforma tributária (IBS/CBS), obrigações acessórias | Mensal |
| Boletins dos consultores contratados | Resumos especializados de metrologia, fiscal, LGPD | Conforme cadência do contrato |

## 2. Responsável

Enquanto o laboratório não tem consultor fiscal/metrologia contratado, o responsável provisório é o próprio PM. Após contratação:

- **Metrologia e RBC** → consultor de metrologia (item M2 da Trilha paralela).
- **Fiscal (NFS-e, ICMS, reforma tributária)** → consultor fiscal (item F2).
- **LGPD** → DPO (item do Trilha #2 decisão #2).

## 3. Processo

1. Verificação na frequência mínima da tabela §1.
2. Para cada item identificado, abrir entrada nova em `docs/compliance/revalidation-calendar.md` com data, fonte, resumo e prioridade (P0/P1/P2).
3. Se a mudança exigir atualização de código do produto, abrir slice em `specs/NNN/` referenciando a fonte e com AC que cubra o novo requisito.
4. Se a mudança exigir atualização de política, editar o arquivo correspondente em `docs/compliance/` e abrir commit.
5. Se a mudança invalidar escopo do MVP, registrar em `docs/incidents/law-change-YYYY-MM-DD.md` e reavaliar `out-of-scope.md`.

## 4. Classificação de prioridade

- **P0 — blocking.** Muda requisito já implementado ou cria obrigação com prazo menor que 30 dias. Gera incident imediato.
- **P1 — importante.** Muda requisito com prazo entre 30 e 180 dias. Entra no tracker de slice e vira AC em no máximo 2 semanas.
- **P2 — informativo.** Contexto ou tendência sem prazo rígido. Entra em `law-watch-log-YYYY-QN.md` (log trimestral).

## 5. Skill `/law-status-refresh`

Skill a ser criada em `.claude/skills/law-status-refresh.md` (pós-Bloco 2, sob relock) que:
- Lista todas as entradas de `revalidation-calendar.md` com próxima data vencendo em menos de 30 dias.
- Gera relatório em `docs/reports/revalidation-due-YYYY-MM.md`.
- Dispara alerta no tracker quando prazo está dentro de 7 dias.

## 6. Auditoria deste processo

- **Mensal:** registro do que foi verificado em cada fonte, no mesmo arquivo do relatório do mês.
- **Trimestral:** retrospectiva no formato `docs/retrospectives/law-watch-YYYY-QN.md`.
- **Anual:** reavaliação das fontes monitoradas (pode adicionar ou remover).

## 7. Falha de processo

Se o processo deixar de rodar por mais de 60 dias sem justificativa, é **incidente crítico** registrado em `docs/incidents/law-watch-drift-YYYY-MM-DD.md`.
