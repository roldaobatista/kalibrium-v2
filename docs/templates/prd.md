# Template — PRD (Product Requirements Document)

> **Uso:** copiar para `specs/NNN-<slug>/prd.md` ou para `docs/product/<slug>-prd.md` quando for um artefato de produto de maior fôlego que um spec de slice. Item 6.9 dos micro-ajustes da meta-auditoria #2. Distinto do `spec.md` de slice (que é task-level) e do `plan.md` (que é implementação).

## 1. Identificação

- **Título:** frase curta em português
- **Autor:** nome do PM
- **Data desta versão:** AAAA-MM-DD
- **Versão:** v1, v2, ...
- **Status:** rascunho / em revisão / aprovado / substituído
- **Público-alvo deste documento:** PM, agentes, eventual advisor

## 2. Problema

De 1 a 3 parágrafos respondendo: qual dor o usuário sente hoje e por que é problema grande o suficiente para justificar este trabalho. Evitar solução — aqui é só sintoma.

## 3. Persona afetada

Referenciar a persona em `docs/product/personas.md` por nome (Persona 1 Marcelo, Persona 2 Juliana, Persona 3 Rafael). Descrever como **cada persona envolvida** sente a dor.

## 4. Hipótese de valor

Uma frase única no formato "Se fizermos X, acreditamos que Y, medido por Z". Evitar vaguidade — cada termo precisa ser falsificável.

## 5. Escopo IN (o que entra)

Lista numerada dos entregáveis funcionais. Cada item é testável. Seguir o padrão `REQ-DOM-NNN` quando for requisito de produto, conforme `mvp-scope.md`.

1. ...
2. ...
3. ...

## 6. Escopo OUT (o que fica de fora)

Lista explícita do que o documento **não** cobre. Cada item com gatilho de reentrada ("quando X, reavaliamos"). Se faltar essa seção, o documento vira guarda-chuva e perde foco.

- ...
- ...

## 7. Métricas de sucesso

Três métricas máximas, cada uma com alvo numérico e janela temporal. Exemplo: "Taxa de retrabalho cai de 8% para menos de 3% nos primeiros 60 dias de uso."

1. ...
2. ...
3. ...

## 8. Riscos conhecidos

Lista de riscos técnicos, de produto e regulatórios. Para cada: severidade (alta/média/baixa), probabilidade (alta/média/baixa), mitigação prevista.

| # | Risco | Severidade | Probabilidade | Mitigação |
|---|---|---|---|---|
| R-1 | ... | ... | ... | ... |

## 9. Dependências

Outros documentos, slices, consultores ou decisões que **precisam** estar prontos antes deste trabalho começar.

## 10. Alternativas consideradas

Mínimo 2, máximo 4 alternativas que foram avaliadas e descartadas, com 1 parágrafo por alternativa explicando por que não foi escolhida. Evita o viés de "só existiu a solução que o autor pensou primeiro".

## 11. Plano de validação pós-entrega

Como vamos saber, depois de entregue, se o trabalho foi bem-sucedido? Referência às métricas §7, telemetria específica, entrevistas com usuários, etc.

## 12. Anexos

Mockups, diagramas, pesquisas, entrevistas, dados de campo. Opcional mas recomendado quando houver.

---

**Regra final:** nenhuma seção pode ficar em branco. Se não se aplica, escrever "não aplicável" + razão de 1 linha.
