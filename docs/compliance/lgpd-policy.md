# Policy por domínio — LGPD

> **Status:** ativo, mínimo viável do MVP (ver `out-of-scope.md §3`). Item T2.10 da Trilha #2. Revisão formal do DPO obrigatória antes do primeiro tenant real — enquanto isso, itens T2.1-T2.5 ficam em `draft-awaiting-dpo`.

## 1. Normas e datas aplicáveis

| Norma | Seção | Data/versão | Fonte |
|---|---|---|---|
| Lei 13.709/2018 (LGPD) | Art. 6, Art. 7, Art. 11 (bases legais) | 2018-08-14 | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 37 (ROT) | 2018 | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 38 (DPIA) | 2018 | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 39 (contrato de operador) | 2018 | Planalto |
| Lei 13.709/2018 (LGPD) | Art. 48 (notificação ANPD 72h) | 2018 | Planalto |
| Resoluções da ANPD | Agente de tratamento, incidente | Monitoradas semanal | ANPD |
| Decreto 10.474/2020 | Estrutura da ANPD | 2020 | Planalto |

## 2. Decisão de escopo no MVP

Dentro do MVP (mínimo viável):

1. Base legal registrada por categoria de dado (item T2.2).
2. Registro de Operações de Tratamento (item T2.4).
3. Contrato de operador (item T2.9) com cláusulas do Art. 39.
4. DPIA inicial (item T2.3).
5. Playbook de resposta a incidente com rota para notificação ANPD em 72h (item T2.5).
6. Canal para exercício de direitos do titular (confirmação, acesso, correção, exclusão, portabilidade). O canal é um e-mail monitorado por um papel de "atendimento LGPD" no tenant.
7. Política de retenção vinculada aos RNFs (RNF-009).

Fora do MVP: dado pessoal sensível (saúde, biométrico, criança), transferência internacional de dados fora do Brasil, oferta a menor de idade, tratamento para finalidade de marketing ativo.

## 3. Consultor responsável

DPO fracionário contratado no item correspondente do `procurement-tracker.md` (decisão #2 do PM da meta-auditoria #2). Advogado LGPD separado para `contrato-operador-template.md` (item T2.9).

## 4. Matriz norma → requisito → golden test → slice

| norma | seção | requisito | teste golden | slice | data de revalidação |
|---|---|---|---|---|---|
| LGPD Art. 37 | ROT | Registro vivo em docs/security/rot.md | (DPO define) | (DPO define) | 2026-07-10 |
| LGPD Art. 38 | DPIA | Relatório inicial em docs/security/dpia.md | (DPO define) | (DPO define) | 2026-07-10 |
| LGPD Art. 39 | Contrato operador | Template em docs/security/contrato-operador-template.md | (DPO define) | (DPO define) | 2026-07-10 |
| LGPD Art. 48 | Notificação 72h | Playbook em docs/security/incident-response-playbook.md | (DPO define) | (DPO define) | 2026-07-10 |
| LGPD Art. 18 | Direitos do titular | Fluxo documentado no canal de atendimento | (DPO define) | (DPO define) | 2026-07-10 |
| LGPD Art. 6 III | Minimização | Nenhum dado coletado além do estritamente necessário | (DPO define) | (DPO define) | 2026-07-10 |

## 5. Frequência de revalidação

- **LGPD + ANPD:** trimestral (próxima 2026-07-10).
- **Base legal por categoria:** revisão sempre que novo tipo de dado entra no produto.
- **DPIA:** anual ou sempre que arquitetura mudar materialmente.

## 6. Módulos proibidos para IA sem revisão externa

- Parecer jurídico sobre cláusula contratual nova → advogado obrigatório (item 6 de `ia-no-go.md`).
- Aprovação formal de DPIA para ir a tenant real → DPO obrigatório.
- Decisão de base legal nova ou alteração de base existente → DPO obrigatório.

## 7. Cross-ref

`out-of-scope.md §3`, `ia-no-go.md §6`, `procurement-tracker.md`, `revalidation-calendar.md`, `traceability-template.md`, `contrato-operador-template.md`, `security/threat-model.md`, `security/lgpd-base-legal.md`, `security/dpia.md`, `security/rot.md`, `security/incident-response-playbook.md`.
