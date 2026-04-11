# Calendário de revalidação normativa — Kalibrium

> **Status:** ativo, vivo. Item T2.16 da Trilha #2 (gap novo de cobertura). Complementa `law-watch.md` (T2.12): enquanto `law-watch.md` é **monitoramento contínuo** (processo), este arquivo é **cadência calendarizada** (datas concretas). A skill `/law-status-refresh` mensal lê daqui, não de `law-watch.md`.

## 1. Regra de cadência

Cada norma/seção rastreada nas policies por domínio (T2.10) ou em `docs/compliance/traceability-template.md` (T2.13) tem entrada obrigatória aqui com:

- **norma** — nome oficial abreviado
- **seção** — artigo ou item específico
- **última revalidação** — data em que o conteúdo foi verificado contra a fonte oficial
- **próxima revalidação** — data limite da próxima verificação
- **responsável** — consultor ou PM
- **fonte oficial** — URL ou referência documental oficial

## 2. Tabela inicial

As linhas abaixo são o estado inicial do calendário no dia em que a Trilha #2 Estado 1 é commitada. Consultores ainda não contratados (ver `procurement-tracker.md`) figuram como "PM provisório".

| Norma | Seção | Última revalidação | Próxima revalidação | Responsável | Fonte oficial |
|---|---|---|---|---|---|
| Lei 13.709/2018 (LGPD) | Art. 37 (registro operações) | 2026-04-10 | 2026-07-10 | DPO quando contratado; PM provisório | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 38 (DPIA) | 2026-04-10 | 2026-07-10 | DPO; PM provisório | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 39 (contrato de operador) | 2026-04-10 | 2026-07-10 | Advogado LGPD | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 48 (notificação ANPD 72h) | 2026-04-10 | 2026-07-10 | DPO; PM provisório | Planalto |
| ISO/IEC 17025:2017 | §7.8 (conteúdo do certificado) | 2026-04-10 | 2027-04-10 | Consultor de metrologia | ABNT/ISO |
| ISO/IEC 17025:2017 | §6 (requisitos de recursos) | 2026-04-10 | 2027-04-10 | Consultor de metrologia | ABNT/ISO |
| ABNT NBR ISO/IEC 17025:2017 | §8 (sistema de gestão) | 2026-04-10 | 2027-04-10 | Consultor de metrologia | ABNT |
| GUM/JCGM 100:2008 | §5 (incerteza expandida) | 2026-04-10 | 2027-04-10 | Consultor de metrologia | BIPM |
| Portaria INMETRO RBC vigente | Critérios gerais de acreditação | 2026-04-10 | 2026-10-10 | Consultor de metrologia | INMETRO |
| Lei 8.666/1993 / Lei 14.133/2021 | Aplicabilidade ao laboratório em contratação pública | 2026-04-10 | 2027-04-10 | Advogado LGPD | Planalto |
| Lei Complementar 123/2006 | Simples Nacional (faixas e alíquotas) | 2026-04-10 | 2026-07-10 | Consultor fiscal | Receita Federal |
| Legislação tributária municipal (SP, Campinas, BH, Curitiba, POA) | NFS-e, ISS, código de serviço 14.01/17.01 | 2026-04-10 | 2026-07-10 | Consultor fiscal | SEFAZ municipais |
| Reforma tributária federal (IBS/CBS) | Cronograma de transição aplicável a serviço | 2026-04-10 | 2026-06-10 | Consultor fiscal | Receita Federal |
| ANPD Resoluções vigentes | Incidente, agente de tratamento | 2026-04-10 | 2026-07-10 | DPO | ANPD |

## 3. Processo de atualização

1. No início de cada mês, a skill `/law-status-refresh` lê esta tabela e gera `docs/reports/revalidation-due-YYYY-MM.md` listando tudo com `próxima revalidação` em menos de 30 dias.
2. O responsável tem até a data-limite para verificar a fonte, trazer eventuais mudanças e atualizar a linha: `última revalidação` vira a data de hoje, `próxima revalidação` é recalculada pela cadência definida.
3. Se houver mudança que exige atualização de policy, o responsável edita o arquivo correspondente em `docs/compliance/` e abre commit.
4. Se houver mudança que exige slice, registrar em `specs/` e abrir slice.
5. Se a data-limite passa sem verificação, registrar em `docs/incidents/revalidation-miss-<norma>-YYYY-MM-DD.md`.

## 4. Cadências recomendadas por categoria

- **LGPD (fonte ANPD):** trimestral (90 dias).
- **Fiscal (SEFAZ, RF):** trimestral (90 dias).
- **Normas metrológicas (ISO, BIPM, INMETRO):** anual (365 dias) com exceção do RBC (semestral).
- **Reforma tributária:** bimestral enquanto o cronograma estiver em transição.
- **Trabalhista (aplicável indiretamente):** anual.

## 5. Dependências

- `law-watch.md` (T2.12) — fonte do processo contínuo de monitoramento, consome esta tabela.
- `traceability-template.md` (T2.13) — cada requisito da matriz aponta para uma linha aqui.
- `procurement-tracker.md` (T2.14) — quando consultor é contratado, a coluna `responsável` é atualizada.
