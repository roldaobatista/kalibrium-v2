# Policy por domínio — Metrologia

> **Status:** ativo (aguarda assinatura do consultor de metrologia no item M2). Item T2.10 da Trilha #2. Consome `laboratorio-tipo.md` (1.5.7), `mvp-scope.md` (1.5.2), `ideia-v1.md` (1.5.1) e `rfp-consultor-metrologia.md` como insumos.

## 1. Normas e datas aplicáveis

| Norma | Seção de interesse | Data/versão | Fonte oficial |
|---|---|---|---|
| ABNT NBR ISO/IEC 17025:2017 | §6, §7.8, §8 | 2017 | ABNT |
| GUM / JCGM 100:2008 | §5 (incerteza expandida) | 2008-09-01 | BIPM |
| VIM — Vocabulário Internacional de Metrologia (JCGM 200:2012) | Termos canônicos | 2012 | BIPM |
| Resoluções do INMETRO sobre RBC (versão vigente) | Critérios gerais de acreditação | Monitorada mensal | INMETRO |
| Portaria INMETRO aplicável ao domínio dimensional/pressão/massa/temperatura | Quando houver | Monitorada mensal | INMETRO |

## 2. Decisão de escopo no MVP

Dentro do MVP: calibração nos 4 domínios decididos em `mvp-scope.md §2` (dimensional, pressão, massa, temperatura), emissão de certificado RBC e certificado não-acreditado (variantes visuais distintas), cálculo de incerteza conforme GUM básico, cadastro de padrões com cadeia de rastreabilidade. Fora do MVP: domínios não listados (elétrico, torque, vazão, vibração, umidade, óptica), certificado em formato customizado fora do padrão RBC, orçamento de incerteza avançado além do GUM básico (este último exige consultor per `ia-no-go.md` §5).

## 3. Consultor responsável

Consultor de metrologia contratado no item M2 do tracker (`procurement-tracker.md`). Enquanto não contratado, o PM é responsável provisório apenas por registro e consulta — nenhuma decisão metrológica operacional é tomada antes da contratação.

## 4. Matriz norma → requisito → golden test → slice

Seguindo o template de `traceability-template.md` (T2.13). Linhas iniciais com `teste golden` e `slice` vazios serão preenchidas pelo consultor no item M3-M4.

| norma | seção | requisito | teste golden | slice | data de revalidação |
|---|---|---|---|---|---|
| ABNT NBR ISO/IEC 17025:2017 | §7.8.1 | Certificado com conteúdo mínimo (identificação, rastreabilidade, incerteza, resultado, validade) | (M4 define) | (M5 define) | 2027-04-10 |
| ABNT NBR ISO/IEC 17025:2017 | §7.8.3 | Declaração de conformidade com regra de decisão explícita | (M4 define) | (M5 define) | 2027-04-10 |
| GUM / JCGM 100:2008 | §5 | Cálculo de incerteza expandida U=k·uc com fator k declarado | tests/golden/metrology/gum-cases.csv (M3) | (M5 define) | 2027-04-10 |
| GUM / JCGM 100:2008 | §6 | Orçamento de incerteza escrito com todas as componentes | (M4 define) | (M5 define) | 2027-04-10 |
| Portaria INMETRO RBC vigente | critérios gerais | Rastreabilidade ao padrão nacional ou internacional declarada | (M4 define) | (M5 define) | 2026-10-10 |

## 5. Frequência de revalidação

- **RBC (INMETRO):** semestral (próxima 2026-10-10).
- **ISO 17025, GUM, VIM:** anual (próxima 2027-04-10).
- **Quando publicar nova edição da ISO:** reavaliação extraordinária em até 30 dias.

## 6. Módulos proibidos para IA sem revisão externa

- Orçamento de incerteza para procedimento novo → consultor obrigatório (item 5 de `ia-no-go.md`).
- Regra de decisão para declaração de conformidade de instrumento crítico → consultor obrigatório.
- Alteração do leiaute do certificado em tenant acreditado → consultor obrigatório.

## 7. Cross-ref

`rfp-consultor-metrologia.md`, `traceability-template.md`, `revalidation-calendar.md`, `ia-no-go.md`, `laboratorio-tipo.md`, `out-of-scope.md`.
