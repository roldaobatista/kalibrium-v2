# Policy por domínio — REP-P (ponto eletrônico)

> **Status:** ativo, inerte. Item T2.10 da Trilha #2. Este arquivo existe porque a Trilha #2 exige policy para cada domínio sensível — mas o REP-P está **deliberadamente fora do MVP** conforme `out-of-scope.md §1`. A policy aqui formaliza o "fora" para o caso de ressurgir a pergunta e tirar o assunto da memória individual.

## 1. Normas e datas aplicáveis

| Norma | Seção | Data/versão | Fonte |
|---|---|---|---|
| Portaria MTP 671/2021 | Art. 78 (REP-P modalidade programa) | 2021-11-11 | DOU |
| Portaria MTP 3.626/1991 | Regulamenta REP convencional (antigo) | 1991 | DOU |
| Lei 13.874/2019 | Declaração de Direitos de Liberdade Econômica | 2019 | Planalto |
| CLT | Art. 74 (registro de jornada) | Atualizada | CLT consolidada |

## 2. Decisão de escopo no MVP

**Fora do MVP. Gatilho de reentrada: nenhum.** Ver `out-of-scope.md §1` para justificativa. Resumo:

- Não é o problema do Kalibrium. O produto resolve calibração, não relação trabalhista.
- Risco regulatório desproporcional (homologação, eSocial, auditoria trabalhista).
- Mercado já atendido por soluções homologadas.

## 3. Consultor responsável

Nenhum. O REP-P está fora de escopo permanentemente. Se algum dia surgir necessidade, o caminho é integração com sistema especializado, nunca implementação interna.

## 4. Matriz norma → requisito → golden test → slice

Vazia por design. Este domínio não gera requisito de produto no MVP nem no roadmap atual.

## 5. Frequência de revalidação

- **Verificação anual:** apenas para confirmar que a decisão de "fora de escopo" continua válida (nenhum cliente pagante reclamou, nenhuma mudança regulatória forçou o assunto).
- **Gatilho de reavaliação extraordinária:** demanda formal de cliente pagante.

## 6. Módulos proibidos para IA sem revisão externa

Ver `ia-no-go.md §4` — "Conexão com AFD do REP-P". Mesmo que a decisão de fora-de-escopo fosse revertida, a implementação não poderia ser feita por agente de IA.

## 7. Cross-ref

`out-of-scope.md §1`, `ia-no-go.md §4`, `law-watch.md` (monitoramento do DOU para mudanças).
