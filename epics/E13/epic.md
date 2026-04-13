# E13 — Procurement e Fornecedores

## Objetivo
Implementar o fluxo de compras e gestão de fornecedores: requisição → cotação → pedido de compra → recebimento. Permite ao laboratório controlar a aquisição de materiais consumíveis, padrões de referência e serviços terceirizados com rastreabilidade documental.

## Valor entregue
Técnico solicita um material consumível, gestor aprova a compra com alçada definida, sistema compara cotações de fornecedores e gera o pedido de compra. Recebimento é conferido contra o pedido. Toda a trilha de compra fica documentada para auditoria.

## Escopo (FR-PRO-01..04)

### Requisição de compra (FR-PRO-01)
- Criação de requisição: item, quantidade, justificativa, urgência, centro de custo
- Vinculação opcional a OS ou projeto
- Aprovação pelo gestor (alçada configurável por valor e categoria)

### Cotação com fornecedores (FR-PRO-02)
- Convite a fornecedores cadastrados (mínimo 3, configurável)
- Registro manual da proposta recebida (valor, prazo, condições)
- Comparativo automático de cotações com critérios configuráveis
- Seleção justificada do fornecedor vencedor

### Pedido de compra (FR-PRO-03)
- Geração do pedido a partir da cotação aprovada
- Alçadas de aprovação por valor (configurável por tenant)
- Bloqueio de pedido para fornecedor com status "suspenso" ou "em homologação"
- Geração automática de título a pagar vinculado ao pedido (integração com financeiro)

### Recebimento com conferência (FR-PRO-04)
- 3-way matching: pedido × NF do fornecedor × recebimento físico
- Registro de divergências (quantidade, preço, especificação)
- Ação corretiva em caso de divergência antes de confirmar entrada no estoque

### Cadastro de fornecedor
- CRUD de fornecedor: CNPJ, razão social, contatos, categoria
- Estados: em homologação → aprovado → suspenso
- Documentos de habilitação vinculados ao GED (certidões, alvarás)

## Fora de escopo
- Portal do fornecedor (FR-PRO-06, P2)
- Avaliação periódica de fornecedor com score automático (FR-PRO-05, P2)
- Integração com ERP do fornecedor (pós-MVP)

## Critérios de entrada
- E08 completo (financeiro — títulos a pagar se integram aqui)
- E10 completo (GED — documentos de fornecedor)

## Critérios de saída
- Fluxo completo: requisição → cotação → pedido aprovado → recebimento conferido
- Título a pagar criado automaticamente ao confirmar pedido
- Divergência no recebimento bloqueia confirmação de entrada no estoque
- Fornecedor suspenso bloqueado para novos pedidos

## Stories previstas
- E13-S01 — Cadastro de fornecedor com ciclo de homologação
- E13-S02 — Requisição de compra e aprovação por alçada
- E13-S03 — Cotação: convite, proposta e comparativo
- E13-S04 — Pedido de compra e integração com título a pagar
- E13-S05 — Recebimento com 3-way matching
- E13-S06 — Relatório de compras e exportação

## Dependências
- E08 (financeiro — títulos a pagar)
- E10 (GED — documentos de fornecedor)

## Riscos
- Alçadas de aprovação podem ser complexas para alguns tenants — configuração simples (valor máximo por papel) é suficiente para MVP deste épico
- 3-way matching manual pode gerar atrito — interface deve ser direta e simples

## Complexidade estimada
- Stories: 6
- Complexidade relativa: média
- Prioridade: P1 (pós-MVP)
- Duração estimada: 1-2 semanas
