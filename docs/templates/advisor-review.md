# Template — Parecer do advisor técnico externo

> **Uso:** o advisor contratado conforme `docs/governance/external-advisor-policy.md` (A1) copia este arquivo para `docs/reviews/advisor/YYYY-MM-DD-<topico>.md` e preenche cada seção. Item A2 da meta-auditoria #2.
>
> **Regra:** nenhuma seção pode ser deletada. Se não for aplicável, escrever "não aplicável" e justificar em uma linha.

---

## 1. Metadados

- **Nome do advisor:** (nome completo)
- **Função / formação:** (ex.: engenheiro de software sênior, 12 anos em SaaS multi-tenant)
- **Empresa ou atuação:** (autônomo / empresa / universidade)
- **Data deste parecer:** AAAA-MM-DD
- **Documento revisado:** caminho relativo ao repositório + commit hash curto. Exemplo: `docs/adr/0001-stack.md @ a1b2c3d`.
- **Escopo da revisão:** (frase curta). Exemplo: "Avaliar a escolha da stack proposta para o Kalibrium MVP, considerando `foundation-constraints.md` e `nfr.md`."
- **Tempo gasto:** horas aproximadas. Usado para controle contra o teto do contrato.

## 2. Contexto compreendido

De 2 a 4 parágrafos descrevendo, em palavras do próprio advisor, o que ele entendeu do problema. Deve cobrir:

- Qual é o produto e qual é o problema que ele resolve.
- Quais são as restrições duras (restrições arquiteturais de `foundation-constraints.md` §§1, 2, 6, 8 e 9; RNFs de `nfr.md`; orçamento de `operating-budget.md`).
- Qual é a decisão específica sendo tomada no documento revisado.

A razão desta seção ser obrigatória: detectar mal-entendido antes de o parecer ficar pesado. Se o advisor descreveu errado, o PM pede correção antes de continuar.

## 3. Pontos fortes

Lista numerada do que o advisor considera bem resolvido. Mínimo 2 itens. Evitar elogio genérico — cada item deve apontar **o que** está bom e **por quê**.

1. ...
2. ...
3. ...

## 4. Riscos identificados

Tabela com riscos encontrados. Mínimo 1 risco ou declaração explícita de "nenhum risco material identificado" com justificativa.

| # | Risco | Severidade | Probabilidade | Recomendação |
|---|---|---|---|---|
| R-1 | ... | alta / média / baixa | alta / média / baixa | ... |
| R-2 | ... | ... | ... | ... |

Severidade é o impacto se o risco se concretizar; probabilidade é a chance de ocorrer dentro de 12 meses. Ambos em 3 níveis.

## 5. Alternativas consideradas

Se o advisor conhece alternativa técnica relevante à proposta, descrever brevemente. Máximo 3 alternativas, cada uma com:

- **Nome curto da alternativa**
- **Por que seria considerada:** vantagem principal
- **Por que possivelmente não foi escolhida:** desvantagem principal
- **Veredito do advisor:** "eu ainda prefiro a proposta atual" / "eu prefiro esta alternativa" / "ambas são defensáveis"

Se o advisor não conhece alternativa relevante, escrever "nenhuma alternativa material a sugerir".

## 6. Veredito formal

Uma única linha, exatamente com um dos 3 valores abaixo:

- `veredito: aprovo`
- `veredito: aprovo com ressalvas`
- `veredito: rejeito`

O hook `pre-commit-gate.sh` (item A3 da meta-auditoria #2) usa este campo literal para decidir se o ADR associado pode ir para `status: accepted`. Por isso, escrever exatamente no formato acima.

## 7. Ressalvas (quando aplicável)

Preencher apenas se o veredito for `aprovo com ressalvas`. Lista numerada de condições que, se não forem atendidas, **mudam** o veredito para `rejeito`. Cada ressalva é auditável — deve ser algo que o PM possa verificar como cumprido ou não.

1. Exemplo: "A alternativa X só pode ser aceita se o teste Y nascer vermelho e passar verde antes do primeiro merge do slice correspondente."
2. ...

Se o veredito for `aprovo`, escrever "nenhuma ressalva".

Se o veredito for `rejeito`, escrever as razões formais da rejeição em lista numerada.

## 8. Pedidos de informação ao PM

Opcional. Lista de perguntas que surgiram durante a revisão e cuja resposta poderia afetar o veredito. O PM responde no próprio arquivo em uma seção nova `## 8-resposta-pm` e, se mudar o veredito, o advisor emite nova versão do parecer.

## 9. Assinatura

- **Nome completo:** ...
- **Data:** AAAA-MM-DD
- **Método de assinatura:** digital (veredito no próprio arquivo, hash do commit do repositório) ou ICP-Brasil se disponível (ver `out-of-scope.md §2` — ICP-Brasil está diferido no Kalibrium mas o advisor pode ter a própria).

---

## Observações sobre o uso

- **Nenhum campo pode ficar em branco.** Se não se aplica, escrever "não aplicável" + razão curta.
- **Dados pessoais do advisor** (CPF completo, endereço, telefone) **não** vão neste arquivo. Eles ficam no contrato off-repo (ver `docs/decisions/advisor-contract-YYYY-MM-DD.md`).
- **Versões:** se o advisor precisar emitir novo parecer após resposta do PM ou mudança do documento revisado, cria novo arquivo em `docs/reviews/advisor/YYYY-MM-DD-<topico>-v2.md`. A versão anterior **não** é deletada.
