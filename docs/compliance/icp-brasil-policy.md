# Policy por domínio — ICP-Brasil (assinatura digital qualificada)

> **Status:** diferido até 2026-12-31. Item T2.10 da Trilha #2. Ver `out-of-scope.md §2` para justificativa.

## 1. Normas e datas aplicáveis

| Norma | Seção | Data/versão | Fonte |
|---|---|---|---|
| Medida Provisória 2.200-2/2001 | Institui ICP-Brasil | 2001 | Planalto |
| Instrução Normativa ITI aplicável | Padrões técnicos de A1 e A3 | Monitorada | ITI |
| Decreto 10.278/2020 | Assinatura eletrônica por entes privados | 2020 | Planalto |

## 2. Decisão de escopo no MVP

**Diferido até 2026-12-31.** A assinatura ICP-Brasil no certificado de calibração é conforto, não obrigação regulatória da RBC. Custo de integração (A3 com HSM, A1 com módulo PKCS#11) + custo de armazenamento seguro da chave não cabe no `operating-budget.md` do MVP.

## 3. Consultor responsável

Enquanto estiver diferido: nenhum. Se a reavaliação em 2026-12-31 decidir reentrar no escopo, consultor de segurança da informação + fornecedor certificado na ICP-Brasil.

## 4. Matriz norma → requisito → golden test → slice

Vazia por design. Se reentrar no escopo, a matriz é preenchida no momento da decisão.

## 5. Frequência de revalidação

- **Reavaliação formal agendada:** 2026-12-31 (registrada em `revalidation-calendar.md`).
- **Gatilho antes da data:** exigência documentada de cliente pagante ativo ou mudança de posição da Cgcre/Inmetro.
- **Reavaliação anual pós-2026-12-31:** caso a decisão continue sendo "diferir".

## 6. Módulos proibidos para IA sem revisão externa

Ver `ia-no-go.md §2` — "Assinatura ICP-Brasil A3 em HSM". Mesmo após reentrar no escopo, a implementação não pode ser feita por agente.

## 7. Cross-ref

`out-of-scope.md §2`, `ia-no-go.md §2`, `vendor-matrix.md` (linha ICP-Brasil marcada fora de escopo), `revalidation-calendar.md`, `law-watch.md`.
