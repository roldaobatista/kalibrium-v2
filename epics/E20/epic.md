# E20 — CRM Offline do Vendedor

## Objetivo

Implementar o módulo de CRM mobile para o vendedor externo: carteira de clientes offline com até 500 fichas, registro de visita com nota de voz, criação de orçamento em campo offline com geração de PDF e envio via WhatsApp, conversão de orçamento aceito em OS e pipeline em tempo real para o gerente.

## Valor entregue

Patrícia (vendedora externa) opera 100% no campo com celular pessoal, sem depender de conexão: vê a ficha completa de qualquer cliente da carteira, registra visita com voz + foto, cria orçamento no formato correto e envia por WhatsApp em segundos. Gerente vê pipeline atualizado conforme vendedores sincronizam. Elimina o caderninho, o WhatsApp pessoal como CRM e a versão de orçamento errada enviada ao cliente.

## Escopo

### Carteira de clientes por vendedor (REQ-CRM-001, REQ-CRM-002)
- Cada vendedor vê apenas a própria carteira (isolamento por `vendedor_id`)
- Gerente vê carteiras de todos os vendedores
- Ficha completa do cliente disponível offline: razão social, endereços, contatos, histórico de OS, instrumentos cadastrados, último orçamento
- Capacidade offline: até 500 fichas de clientes no dispositivo do vendedor
- Transferência de carteira: ação explícita do gerente; log de transferência registrado
- Entidades: `CarteiraVendedor`, `VinculoClienteVendedor`

### Registro de visita (REQ-CRM-003)
- Registro offline com: nota de voz (gravação local + transcrição automática quando online), foto da fachada ou crachá, observações textuais, GPS opcional, timestamp automático
- Visita associada obrigatoriamente a um cliente da carteira
- Histórico de visitas na ficha do cliente
- Entidades: `VisitaCliente`, `NotaVozVisita`

### Orçamento em campo offline (REQ-CRM-004, REQ-CRM-005)
- Criação de orçamento em campo: seleção de itens de serviço (tabela de preços sincronizada), quantidades, condições de pagamento, observações
- Geração de PDF local (offline) com logo da empresa + dados do cliente + itens + valor total
- Envio imediato via compartilhamento nativo do dispositivo (WhatsApp, e-mail, link)
- Versionamento de orçamento: nova versão preserva a anterior; cliente recebe sempre a mais recente
- Conversão de orçamento aceito em OS: quando cliente aceita (confirmado pelo vendedor), orçamento é marcado `aceito` e gera OS rascunho ao sincronizar
- Entidades: `Orcamento`, `ItemOrcamento`, `VersaoOrcamento`

### Follow-up automático (REQ-CRM-006)
- Lembrete automático para o vendedor quando orçamento enviado não recebeu resposta em X dias
- X configurável por tenant (padrão: 3 dias úteis)
- Lembrete aparece como notificação no app e em lista de "ações pendentes"

### Pipeline do gerente (REQ-CRM-007)
- Visão de pipeline por vendedor e por fase (visita realizada, orçamento enviado, orçamento aceito, OS gerada)
- Atualizado em tempo real conforme vendedores sincronizam
- Filtros: vendedor, período, cliente, valor de orçamento

## Fora de escopo
- Integração com CRM externo (Salesforce, Hubspot) — pós-MVP
- Scoring automático de lead por IA — pós-MVP
- Transcrição de nota de voz por engine local no dispositivo — pós-MVP (transcrição requer conexão)
- Portal self-service do cliente final para aceitar orçamento online — pós-MVP

## Acceptance Criteria do épico

- **AC-E20-01:** Vendedor offline consulta ficha completa de qualquer cliente da carteira, incluindo histórico de OS e instrumentos, sem conexão.
- **AC-E20-02:** Vendedor registra visita com nota de voz e foto offline; ao sincronizar, visita aparece no histórico do cliente.
- **AC-E20-03:** Vendedor cria orçamento offline, gera PDF local e envia via WhatsApp sem precisar de conexão (PDF gerado localmente).
- **AC-E20-04:** Orçamento aceito pelo vendedor gera OS rascunho automaticamente ao sincronizar, visível para o escritório.
- **AC-E20-05:** Vendedor A não consegue ver ou editar fichas da carteira do Vendedor B (isolamento de carteira).
- **AC-E20-06:** Gerente vê pipeline de todos os vendedores atualizado com a última sincronização de cada um.
- **AC-E20-07:** Follow-up: lembrete aparece no app do vendedor após 3 dias (configurável) sem resposta de orçamento enviado.

## Dependências

### Diretas (bloqueiam início)
- E16 merged (sync offline — fichas de clientes + orçamentos + visitas sincronizadas)
- ADR-0016 aceita (isolamento de carteira por `vendedor_id` + `tenant_id`)

### Transitivas
- E03 (clientes cadastrados) — fichas de clientes base; E20 amplia com dados CRM
- E04 (OS) — conversão de orçamento em OS usa entidades de E04
- E12 (email/comunicação) — envio de orçamento por email usa infra de E12; não bloqueia (app usa compartilhamento nativo)
- E21 (push) — lembretes de follow-up via push; funciona sem E21 via polling/badge

## ADRs relacionadas
- ADR-0015 — Stack offline-first mobile
- ADR-0016 — Isolamento multi-tenant
- ADR-0017 — Sync engine

## Definition of Done
- Carteira de 500 clientes carregável e consultável offline no dispositivo
- Registro de visita com nota de voz funcional
- Geração de PDF offline do orçamento funcional (sem conexão)
- Conversão de orçamento em OS funcional via sync
- Isolamento de carteira entre vendedores testado e verde no CI
- Testes: unit (Pest + Vitest) + E2E (Playwright) — verdes no CI

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E20-S01 | Carteira de clientes por vendedor + transferência de carteira (RBAC + offline) | alta |
| E20-S02 | Ficha completa do cliente offline (500 registros + histórico OS + instrumentos) | alta |
| E20-S03 | Registro de visita (nota de voz + foto + GPS + histórico na ficha) | alta |
| E20-S04 | Criação de orçamento offline + geração de PDF local | alta |
| E20-S05 | Conversão de orçamento aceito em OS (sync → OS rascunho) | média |
| E20-S06 | Follow-up automático (lembrete configurável por dias sem resposta) | média |
| E20-S07 | Pipeline do gerente (visão por vendedor, por fase, filtros) | média |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| PDF gerado offline difere do modelo "oficial" enviado online | médio | Template PDF único compartilhado entre frontend e backend (PDF gerado no cliente via jsPDF com template idêntico ao do servidor) |
| 500 fichas offline com histórico completo = volume grande no SQLite local | médio | Compressão de histórico: apenas últimas 5 OS e último orçamento por cliente offline; histórico completo disponível online |
| Transcrição de nota de voz exige conexão → falha offline | baixo | Nota de voz armazenada como arquivo de áudio local; transcrição enfileirada para quando online; vendedor vê "aguardando transcrição" |
| Carteira com 500 clientes leva tempo para sincronizar em primeira instalação | médio | Sync incremental após primeiro bulk download; barra de progresso na primeira sincronização |

## Estimativa
- Stories: 7
- Complexidade relativa: alta
- Duração estimada: 3 semanas

## Referências
- PRD-ampliacao-2026-04-16.md §7 (CRM Offline do Vendedor, REQ-CRM-001..007)
- docs/product/personas.md Persona 5 (Patrícia, vendedora externa)
- docs/product/journeys.md Jornada 9 (vendedor externo offline)
