# Policy por domínio — Fiscal

> **Status:** ativo (aguarda assinatura do consultor fiscal no item F2). Item T2.10 da Trilha #2. Consome `laboratorio-tipo.md §2.6` e `rfp-consultor-fiscal.md` como insumos.

## 1. Normas e datas aplicáveis

| Norma | Seção de interesse | Data/versão | Fonte oficial |
|---|---|---|---|
| Lei Complementar 116/2003 | Lista de serviços tributáveis pelo ISS | 2003 | Planalto |
| Lei Complementar 123/2006 | Simples Nacional | Atualizada periodicamente | Receita Federal |
| Reforma tributária (EC 132/2023, LC em edição) | IBS/CBS cronograma de transição | 2023-2033 (transição) | Receita Federal |
| Legislação municipal SP | NFS-e, ISS código 14.01/17.01 | Vigente | Prefeitura SP |
| Legislação municipal Campinas | NFS-e, ISS | Vigente | Prefeitura Campinas |
| Legislação municipal BH | NFS-e, ISS | Vigente | Prefeitura BH |
| Legislação municipal Curitiba | NFS-e, ISS | Vigente | Prefeitura Curitiba |
| Legislação municipal POA | NFS-e, ISS | Vigente | Prefeitura POA |
| Portaria SPED NF-e (quando aplicável) | Layout XML | Monitorada | SEFAZ |

## 2. Decisão de escopo no MVP

Dentro do MVP: emissão de NFS-e para 5 municípios iniciais (SP, Campinas, BH, Curitiba, POA), suporte a Simples Nacional e Lucro Presumido, numeração fiscal controlada pelo sistema, envio automático do XML ao cliente, baixa automática no contas a receber, conciliação manual com o banco. Fora do MVP: Lucro Real, NF-e de mercadoria, emissão ICMS, retenção de ISS na fonte por cliente, integração com ERP do cliente, SPED Fiscal completo, REP-P (ver `out-of-scope.md`).

## 3. Consultor responsável

Consultor fiscal contratado no item F2 do `procurement-tracker.md`. Enquanto não contratado, o PM é responsável provisório apenas por registro — nenhuma decisão fiscal operacional sem consultor.

## 4. Matriz norma → requisito → golden test → slice

| norma | seção | requisito | teste golden | slice | data de revalidação |
|---|---|---|---|---|---|
| LC 116/2003 | Lista de serviços | Código de serviço 14.01 ou 17.01 corretamente aplicado | tests/golden/fiscal/code-mapping.csv (F3) | (F4 define) | 2026-07-10 |
| LC 123/2006 | Simples Nacional | Cálculo correto da alíquota efetiva por faixa de faturamento | tests/golden/fiscal/simples-rates.csv (F3) | (F4 define) | 2026-07-10 |
| Legislação municipal SP | NFS-e layout | XML no layout da prefeitura de SP aceito na sandbox | tests/golden/fiscal/nfs-e-sp.csv (F3) | (F4 define) | 2026-07-10 |
| Legislação municipal Campinas | NFS-e layout | Idem Campinas | (F3 define) | (F4 define) | 2026-07-10 |
| Legislação municipal BH | NFS-e layout | Idem BH | (F3 define) | (F4 define) | 2026-07-10 |
| Legislação municipal Curitiba | NFS-e layout | Idem Curitiba | (F3 define) | (F4 define) | 2026-07-10 |
| Legislação municipal POA | NFS-e layout | Idem POA | (F3 define) | (F4 define) | 2026-07-10 |
| Reforma tributária (IBS/CBS) | Cronograma | Identificação do ano em que a transição afeta o código de serviço | (F3 define) | (F4 define) | 2026-06-10 |

## 5. Frequência de revalidação

- **Fiscal municipal:** trimestral (próxima 2026-07-10).
- **Simples Nacional:** anual + sempre que a LC for alterada.
- **Reforma tributária IBS/CBS:** bimestral enquanto a transição estiver em vigor.

## 6. Módulos proibidos para IA sem revisão externa

- Integração SEFAZ via webservice por UF → terceirizar com provedor (item 1 de `ia-no-go.md`).
- Cálculo de alíquota efetiva Simples Nacional para tenant real → consultor fiscal obrigatório na primeira ativação por tenant.
- Reforma tributária IBS/CBS → consultor fiscal obrigatório, área em movimento constante.

## 7. Cross-ref

`rfp-consultor-fiscal.md`, `laboratorio-tipo.md §2.6`, `out-of-scope.md`, `ia-no-go.md §1`, `vendor-matrix.md` (linha NFS-e).
