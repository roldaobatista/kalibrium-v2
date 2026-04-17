# E18 — Caixa de Despesa por OS

## Objetivo

Implementar o ciclo completo de gestão de despesas de campo: registro com foto obrigatória atrelado a uma OS, três origens de dinheiro (cartão corporativo, adiantamento e reembolso pessoal), triagem e aprovação em alçada, reembolso por PIX em lote e conciliação com fatura do cartão corporativo.

## Valor entregue

Técnico, motorista e vendedor registram cada gasto em campo em segundos (foto + valor + categoria + OS), sem papel. Escritório recebe tudo triado por OS, aprova em alçada e processa reembolso em lote. Gestor vê custo real de cada OS consolidado, eliminando planilhas de prestação de contas e atraso no fechamento financeiro.

## Escopo

### Registro de despesa (REQ-DSP-001, REQ-DSP-002)
- Campo obrigatório: foto do cupom/nota, valor, tipo de despesa (combustível, pedágio, alimentação, hospedagem, outro), OS atrelada, origem do dinheiro
- Três origens de dinheiro: `cartao_corporativo`, `adiantamento_dinheiro`, `proprio_bolso` (reembolso)
- Registro funciona 100% offline; foto armazenada localmente e sincronizada via E16 (upload S3 presigned URL)
- App rejeita despesa sem foto localmente (validação antes de salvar, não só no servidor)
- App rejeita despesa sem OS atrelada — despesa órfã não existe
- Entidades: `Despesa`, `FotoDespesa`

### Saldo otimista em tempo real (REQ-DSP-003)
- Saldo otimista: soma das despesas pendentes de aprovação exibida em tempo real no app do técnico/motorista
- Saldo por OS e saldo pessoal acumulado do usuário no período

### Triagem e aprovação em alçada (REQ-DSP-004, REQ-DSP-005)
- Triagem pelo escritório (Cláudia): aprovar, rejeitar ou reclassificar tipo/OS de cada despesa
- Aprovação em alçada: valor até limiar X → escritório aprova; acima de X → gerente aprova
  <!-- TBD: refinar com PM o valor de limiar antes de /start-story — sugestão inicial R$ 500,00 para "alçada gerente" -->
- Motivo obrigatório ao rejeitar despesa
- Notificação push ao usuário ao rejeitar ou solicitar correção (via E21 push)
- Estados da despesa: `rascunho` → `enviada` → `triagem` → `aprovada` | `rejeitada`

### Reembolso por PIX em lote (REQ-DSP-006)
- Lote de reembolso: administrativo seleciona despesas aprovadas com origem `proprio_bolso`, confirma PIX ou transferência por usuário
- Registro de pagamento com data e comprovante (opcional)
- Despesas pagas marcadas como `reembolsada`

### Conciliação com cartão corporativo (REQ-DSP-007)
- Importação de fatura CSV do cartão corporativo
- Matching automático linha-a-linha por valor + data (±1 dia) com despesas registradas
- Exibição de: itens conciliados, itens apenas na fatura (gasto não registrado), itens apenas no app (pendente na fatura)
- Exportação do relatório de conciliação em CSV/PDF

### Relatório de custo por OS (REQ-DSP-008)
- Relatório de custo real por OS: soma por categoria, por origem, por usuário
- Filtros: período, OS, usuário, status de aprovação
- Exportação CSV e PDF

## Fora de escopo
- Integração direta com operadora de cartão por API — pós-MVP (importação CSV cobre MVP)
- Adiantamento automático de dinheiro via transferência — pós-MVP (registro manual de adiantamento cobre MVP)
- Workflow de solicitação de adiantamento — pós-MVP
- Aprovação multi-nível além de 2 alçadas — pós-MVP

## Acceptance Criteria do épico

- **AC-E18-01:** Técnico registra despesa offline com foto; app bloqueia tentativa de salvar sem foto com mensagem clara.
- **AC-E18-02:** Despesa sem OS selecionada é bloqueada pelo app antes de salvar.
- **AC-E18-03:** Escritório vê fila de triagem, aprova despesas abaixo do limiar, e despesas acima do limiar exigem aprovação do gerente.
- **AC-E18-04:** Reembolso em lote: administrativo processa PIX para todos os técnicos com despesas `proprio_bolso` aprovadas de uma vez.
- **AC-E18-05:** Conciliação CSV: fatura do cartão importada, itens conciliados com despesas do app, relatório mostra divergências.
- **AC-E18-06:** Relatório de custo de OS consolida todas as despesas aprovadas por categoria.
- **AC-E18-07:** Isolamento multi-tenant: despesas de tenant A não visíveis para tenant B.

## Dependências

### Diretas (bloqueiam início)
- E16 merged (sync de despesas offline + upload de fotos)
- ADR-0016 aceita (tenant_id em despesas)

### Transitivas
- E21 (push notification) — notificação ao rejeitar despesa; funciona sem E21 via polling/badge; E21 melhora UX
- E07 (NFS-e / financeiro) — correlação futura entre despesas e custo de OS no financeiro; não bloqueia E18

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- ADR-0017 — Sync engine (fotos via S3 presigned URL)

## Definition of Done
- Fluxo completo: registro offline → sync → triagem → aprovação em alçada → reembolso em lote
- Conciliação CSV funcional com matching automático
- Foto de despesa sincronizando corretamente via E16
- Testes: unit (Pest) + E2E (Playwright) para fluxo completo — verdes no CI
- Validação de regra de negócio: despesa sem foto e sem OS rejeitadas localmente

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E18-S01 | Registro de despesa offline (foto + valor + tipo + OS + origem) | alta |
| E18-S02 | Saldo otimista por OS e por usuário em tempo real | média |
| E18-S03 | Fila de triagem (escritório): aprovar / rejeitar / reclassificar | média |
| E18-S04 | Aprovação em alçada (gerente para valores acima do limiar) | média |
| E18-S05 | Reembolso por PIX em lote (seleção + confirmação + registro) | média |
| E18-S06 | Conciliação com fatura do cartão corporativo (CSV + matching) | alta |
| E18-S07 | Relatório de custo real por OS (por categoria, origem, usuário) | baixa |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Upload de foto em conexão 3G lenta bloqueia sync | médio | Upload assíncrono após metadados da despesa sincronizados; indicador de "foto pendente de upload" |
| CSV de cartão corporativo com formatos diferentes por banco | médio | Mapeamento configurável de colunas no import; suporte inicial para os 3 maiores bancos (Itaú, Bradesco, BB) |
| Limiar de alçada varia por empresa cliente | baixo | Configuração por tenant no painel de admin; valor padrão R$ 500,00 |

## Estimativa
- Stories: 7
- Complexidade relativa: alta
- Duração estimada: 2-3 semanas

## Referências
- PRD-ampliacao-2026-04-16.md §5 (Caixa de Despesa por OS, REQ-DSP-001..008)
- docs/product/journeys.md Jornada 8 (caixa de despesa por OS)
- docs/product/personas.md Personas 2B (Carlos), 4 (Lúcio), 7 (Cláudia), 1 (Marcelo)
