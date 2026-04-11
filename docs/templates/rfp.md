# Template — RFP (Request for Proposal) para consultor externo

> **Uso:** copiar para `docs/compliance/rfp-<dominio>-<YYYY>.md` quando o PM precisa pedir proposta formal a um consultor (metrologia, fiscal, DPO, advogado, advisor técnico, etc.). Item 6.9 dos micro-ajustes da meta-auditoria #2. Template derivado das RFPs já existentes (`rfp-consultor-metrologia.md` e `rfp-consultor-fiscal.md`) e adaptado para ser genérico.

## 1. Identificação do pedido

- **Título:** "RFP — consultor de [domínio], [AAAA-MM]"
- **Emissor:** Kalibrium (PM)
- **Data do pedido:** AAAA-MM-DD
- **Prazo para resposta:** AAAA-MM-DD (mínimo 7 dias corridos)
- **Forma de envio da resposta:** e-mail para [endereço]
- **Contato para dúvida:** e-mail do PM

## 2. Contexto do Kalibrium

Parágrafo curto (4-6 linhas) descrevendo o que é o Kalibrium, em linguagem de produto. Evitar jargão técnico. Apontar para `docs/product/ideia-v1.md` e `docs/product/laboratorio-tipo.md` como referências canônicas.

## 3. Por que este consultor é necessário

Justificativa de 1 parágrafo: por que o time interno (PM + agentes) não pode resolver, qual é o risco de prosseguir sem o consultor.

## 4. Escopo do trabalho esperado

Lista numerada com os entregáveis esperados. Cada item descritivo, testável.

1. Entregável 1 — descrição + formato + critério de aceitação.
2. Entregável 2 — ...
3. ...

## 5. Perfil desejado do consultor

- **Formação mínima:** ...
- **Experiência prática:** (exemplo: "mínimo 5 anos em laboratório acreditado pela RBC")
- **Conhecimento específico:** (exemplo: "domínio de GUM/JCGM 100:2008 aplicado a calibração dimensional")
- **Disponibilidade:** horas/mês esperadas
- **Localização:** remoto aceito / presencial obrigatório em [cidade]

## 6. Escopo fora do pedido

Lista explícita do que o consultor **não** precisa fazer. Protege o contrato de escopo-fantasma.

- ...
- ...

## 7. Acesso que será concedido

- **Documentos internos:** lista dos documentos do repositório que o consultor pode ler.
- **Ambientes técnicos:** zero acesso / staging com TTL / outro.
- **Dados pessoais:** zero acesso a dado real de titular (ver `docs/compliance/lgpd-base-legal.md`).
- **Credenciais:** zero acesso a credencial de produção.

## 8. Confidencialidade

- NDA obrigatório antes do primeiro acesso.
- Modelo de NDA: a ser fornecido pelo advogado LGPD (ver `procurement-tracker.md`).
- Prazo da confidencialidade: indeterminado após término do contrato.

## 9. Formato do parecer / entregável

- **Formato:** markdown ou PDF entregue por e-mail.
- **Localização no repositório:** `docs/reviews/<dominio>/YYYY-MM-DD-<slug>.md` (o PM copia o arquivo recebido para o repositório).
- **Idioma:** português brasileiro.
- **Linguagem:** técnica quando necessário, mas com um resumo executivo de 5 linhas em linguagem de produto.

## 10. Remuneração

- **Faixa pretendida:** R$ [mínimo] a R$ [máximo] por [hora/dia/projeto fechado].
- **Forma de pagamento:** pagamento por entrega, 50% na assinatura, 50% na aprovação.
- **Prazo de pagamento:** até 15 dias corridos após aprovação da entrega.

## 11. Critério de seleção

- **Peso 1 — aderência técnica:** 40% (conhecimento específico do domínio)
- **Peso 2 — experiência prática:** 30% (casos reais entregues)
- **Peso 3 — disponibilidade imediata:** 15%
- **Peso 4 — custo vs orçamento:** 15%

## 12. Prazo para contratação

Data-limite para assinar contrato com o candidato escolhido. Depois desse prazo, o PM reabre a RFP ou aciona plano B do `procurement-tracker.md`.

## 13. Perguntas frequentes

Opcional. Quando a mesma pergunta aparecer em 2 ou mais respostas, adicionar aqui para os próximos candidatos.

## 14. Assinatura do emissor

- **Nome:** PM do Kalibrium
- **Data:** AAAA-MM-DD

---

**Regra final:** a RFP entregue ao candidato é sempre uma cópia final sem campos abertos do template. Se um campo não se aplica, preencher com "não aplicável" + razão.
