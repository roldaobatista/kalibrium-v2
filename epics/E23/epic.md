# E23 — Revalidação Proativa e Engajamento de Cliente Recorrente

## Objetivo

Implementar o mecanismo de revalidação proativa que detecta certificados de calibração próximos do vencimento e dispara automaticamente uma cadência de comunicação ao cliente com oferta de agendamento, transformando o histórico de calibração do laboratório em receita recorrente previsível.

## Valor entregue

Laboratório não perde renovação de calibração por esquecimento do cliente — o sistema detecta 90 dias antes do vencimento, dispara e-mail/WhatsApp automático com link de agendamento e converte aceitação em OS nova sem intervenção do atendente. Vendedor e gestor têm visão clara do pipeline de revalidações. Estimativa: laboratório com 200 instrumentos recorrentes recupera 20-30% das renovações que hoje se perdem por falta de follow-up ativo.

## Escopo

### Detecção proativa de vencimento (REQ-CRM-008)
- Job diário escaneia todos os certificados de calibração ativos do tenant com `data_validade` nos próximos 90 dias
- Fases de comunicação configuráveis por tenant (padrão: D-90, D-60, D-30, D-7)
- Cada fase dispara comunicação distinta (tom de urgência progressivo)
- Entidades: `ConfiguracaoRevalidacao`, `CampanhaRevalidacao`

### Cadência de comunicação (REQ-CRM-008)
- Canal primário: e-mail via E12 (template de revalidação com dados do instrumento + link de agendamento)
- Canal secundário: WhatsApp (mensagem via API Business — link de aceite)
- Link de agendamento: URL pública com token de acesso (sem login), válido por 30 dias, permite cliente selecionar data disponível
- Entidades: `ComunicacaoRevalidacao`, `LinkAgendamentoRevalidacao`

### Conversão em OS (REQ-CRM-008)
- Cliente aceita via link → sistema cria OS rascunho com instrumento, tipo de calibração e data escolhida
- Vendedor/atendente recebe notificação de OS gerada via revalidação proativa
- OS gerada por revalidação tem flag `origem: revalidacao_proativa` para rastreamento de conversão

### Visão de pipeline de revalidações
- Painel para vendedor/gestor: instrumentos por fase (90d, 60d, 30d, 7d), status da comunicação (enviada, aberta, link acessado, OS gerada, perdida)
- Taxa de conversão por fase e por período
- Exportação da lista de revalidações em CSV para uso externo (ligação ativa pela equipe)

### Regras de negócio
- Instrumento com OS de calibração já agendada não entra na campanha de revalidação
- Campanha pode ser pausada por tenant (período de férias, por exemplo)
- Cliente pode descadastrar de comunicações de revalidação (opt-out LGPD — link de descadastramento no e-mail)
- Log de todas as comunicações enviadas com status de entrega (via webhook do provedor de e-mail)

## Fora de escopo
- Scoring de probabilidade de churn por IA — pós-MVP
- Cobrança automática integrada ao link de agendamento — pós-MVP
- Portal self-service completo do cliente — pós-MVP
- Integração com agenda do Google Calendar do cliente — pós-MVP

## Acceptance Criteria do épico

- **AC-E23-01:** Job diário detecta instrumentos com vencimento em 90 dias e cria campanha de revalidação; e-mail D-90 enviado ao contato do cliente com link de agendamento.
- **AC-E23-02:** Cliente clica no link, seleciona data disponível e confirma; OS rascunho criada automaticamente com flag `origem: revalidacao_proativa`.
- **AC-E23-03:** Instrumento já com OS de calibração agendada não recebe comunicação de revalidação.
- **AC-E23-04:** Cliente opta por descadastrar via link no e-mail; comunicações cessam e opt-out é registrado no log (LGPD).
- **AC-E23-05:** Painel de pipeline exibe instrumentos por fase (D-90/D-60/D-30/D-7) com taxa de conversão calculada.
- **AC-E23-06:** Isolamento multi-tenant: campanhas e comunicações de tenant A não impactam tenant B.

## Dependências

### Diretas (bloqueiam início)
- E20 merged (CRM base — dados de cliente com contatos para comunicação)
- E12 merged (infraestrutura de e-mail e comunicação)
- ADR-0016 aceita (campanhas tenant-scoped)

### Transitivas
- E05 merged (certificados de calibração com data de validade — fonte do job de detecção)
- E21 (push notification para OS gerada por revalidação — melhora UX, não bloqueia)

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- LGPD — opt-out de comunicação (art. 7, inciso I — consentimento)

## Definition of Done
- Job de detecção rodando em staging com dados reais de certificados
- E-mail de revalidação enviado e recebido no e-mail de teste (template aprovado pelo PM)
- Fluxo de link de agendamento → OS criada funcional end-to-end
- Opt-out de LGPD funcional e registrado
- Taxa de conversão calculada corretamente no painel
- Testes: unit (Pest) + E2E (Playwright) — verdes no CI

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E23-S01 | Detecção proativa de vencimento (job diário + fases D-90/60/30/7 + configuração por tenant) | alta |
| E23-S02 | Cadência de comunicação (e-mail + WhatsApp + log de entrega + opt-out LGPD) | alta |
| E23-S03 | Link de agendamento público (token + seleção de data + conversão em OS) | alta |
| E23-S04 | Painel de pipeline de revalidações (fases + status + taxa de conversão + exportação CSV) | média |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Cliente com múltiplos instrumentos recebe dezenas de e-mails em curto prazo | médio | Agrupamento de comunicação: um e-mail por cliente com lista de instrumentos prestes a vencer |
| Link de agendamento exige disponibilidade do calendário do laboratório em tempo real | médio | E23-S03 lê slots disponíveis de E04 (OS agendadas); se E04 não expor slots, mostra formulário simples de "prefiro ser contatado" |
| API WhatsApp Business exige aprovação de templates pela Meta (tempo de aprovação 1-7 dias) | médio | Iniciar aprovação de template durante sprint de S02; fallback para só e-mail no MVP |

## Estimativa
- Stories: 4
- Complexidade relativa: alta (cadência de comunicação + integração externa + LGPD)
- Duração estimada: 2 semanas

## Referências
- PRD-ampliacao-2026-04-16-v2.md §1.3 (Pacote C — Revalidação Proativa, REQ-CRM-008)
- docs/product/journeys.md Jornada 13 (revalidação proativa)
- docs/product/personas.md Persona 5 (Patrícia vendedora), Persona 7 (Cláudia administrativa)
