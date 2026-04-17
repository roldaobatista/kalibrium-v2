# PRD — Ampliação v2 (2026-04-16)

> **Status:** aditivo — consolida decisões de produto do PM tomadas após auditoria comparativa externa (`docs/audits/comparativa-externa-2026-04-16.md`) contra `C:\PROJETOS\KALIBRIUM SAAS` e `C:\PROJETOS\sistema`.
> **Princípio operacional:** PRD só amplia — nenhum requisito da ampliação v1 (mesma data) foi removido; todos os REQs adicionados aqui entram **em conjunto** com os 62 REQs da ampliação v1.
> **Origem:** brief `docs/audits/BRIEF-auditoria-comparativa-externa.md`; relatório `docs/audits/comparativa-externa-2026-04-16.md`.
> **Aprovações:** PM aprovou 7+1 itens para MVP e 7 itens para PÓS-MVP via conversa em 2026-04-16.

---

## 1. Itens aprovados para o MVP (aditivos)

Oito itens novos, agrupados em 3 pacotes temáticos. Cada item gera 1 REQ novo em `mvp-scope.md` e conecta a 1 ou 2 épicos (novos ou ampliados).

### 1.1. Pacote A — Operação Fiscal, LGPD e Infraestrutura Compliance

**Objetivo:** fechar as lacunas de operação real que impedem um laboratório pagante de operar em produção.

| # | REQ novo | Item | Domínio | Épico |
|---|---|---|---|---|
| 1 | `REQ-FIS-007` | Retransmissão de NFS-e rejeitada pela prefeitura (diagnóstico → correção → retransmissão) | FIS | E21 |
| 2 | `REQ-FIS-008` | Retenção fiscal correta por regime (ISS/IR/INSS + half-even rounding) | FIS | E21 |
| 3 | `REQ-FLX-007` | Push notification nativo (técnico, vendedor, gestor em campo, motorista UMC) | FLX | E21 (+ E12 ampliado) |
| 4 | `REQ-CMP-006` | Jornada LGPD do titular completa (solicitação → triagem DPO → atendimento → log) | CMP | E21 |
| 5 | `REQ-CMP-007` | Backup por tenant com verificação de integridade (job agendado) | CMP | E21 |

### 1.2. Pacote B — SPC e Drift de Padrões (ISO 17025)

**Objetivo:** entregar a capacidade de monitoria metrológica exigida por laboratório acreditado Cgcre/Inmetro.

| # | REQ novo | Item | Domínio | Épico |
|---|---|---|---|---|
| 6 | `REQ-MET-009` | Gráficos de controle (SPC) dos padrões de referência com limites de estabilidade | MET | E22 |
| 7 | `REQ-MET-010` | Drift automático de padrão (valor afastando do nominal) com alerta + bloqueio condicional | MET | E22 |

### 1.3. Pacote C — Revalidação Proativa e Engajamento Recorrente

**Objetivo:** transformar o histórico de calibração em receita recorrente automática.

| # | REQ novo | Item | Domínio | Épico |
|---|---|---|---|---|
| 8 | `REQ-CRM-008` | Revalidação proativa (90 dias antes do vencimento → e-mail/WhatsApp ao cliente → oferta de agendamento → conversão em OS) | CRM | E23 |

### 1.4. Persona nova

Além dos 8 REQs acima, a ampliação v2 adiciona **uma persona primária nova** ao MVP:

- **Persona 8 — Responsável de Qualidade / ISO 17025 owner (Aline, 38 anos).** Dona formal da acreditação Cgcre. Hoje coberta implicitamente por Marcelo/Juliana; com SPC + drift (Pacote B), precisa de voz própria. Detalhes em `docs/product/personas.md`.

### 1.5. Jornadas impactadas

- **Jornada 1 passo 9 (emissão fiscal)** — amplia com estado "rejeitada pela prefeitura" → "aguardando correção" → "retransmitida".
- **Jornada nova 12 — Titular exerce direito LGPD.** Cliente final ou ex-cliente solicita acesso/retificação/exclusão; DPO tria; sistema responde com log.
- **Jornada nova 13 — Revalidação proativa.** Sistema detecta certificado próximo do vencimento → dispara cadência → vendedor/atendente confirma → OS nova criada.
- **Jornada nova 14 — Monitoria de qualidade (Aline).** Aline revisa gráficos de controle semanalmente, decide ação quando drift é detectado, responde auditoria RBC anual.

---

## 2. Itens diferidos para PÓS-MVP

Sete itens registrados formalmente como **pós-MVP** para não serem esquecidos. Cada um tem gatilho de reentrada.

| # | Item | Gatilho de reentrada |
|---|---|---|
| 1 | Despacho automático de OS (location + skills + ETA) | Primeiro cliente pagante com 5+ técnicos em campo simultâneos |
| 2 | Escalação automática de SLA (monitor + rules + actions) | Primeiro cliente pagante que vende SLA formal em contrato |
| 3 | Contratos recorrentes com renovação automática | Primeiro cliente pagante que pede contrato guarda-chuva anual |
| 4 | Portal do cliente self-service ampliado (abrir chamado, agendar, NF-e, LGPD) | 3+ clientes pagantes pedirem ampliação do portal |
| 5 | Service tickets + helpdesk interno com SLA | Conjunto: SLA (#2) + chamado (#4) — vem junto com eles |
| 6 | Persona "Especialista remoto / backoffice técnico" | Primeiro cliente pagante com 5+ técnicos e 1-2 seniores dedicados |
| 7 | Persona 3 multi-usuário (comprador + financeiro + qualidade do cliente) | Primeiro cliente pagante com indústria grande (>500 funcionários) |

Estes itens devem ser preservados em `docs/product/post-mvp-backlog.md` (a criar na próxima sessão de ajuste fino).

---

## 3. Itens descartados (confirmação)

Os seguintes itens aparecem nas fontes externas mas **continuam fora** do Kalibrium, conforme `mvp-scope.md §4`:

- eSocial + folha + benefícios + departamentos
- Ponto eletrônico com geofence
- Anvisa RDC validação regulatória
- Contas a pagar
- Gestão de ativos internos (patrimônio)
- Renegociação de dívidas
- NF-e de mercadoria (Kalibrium é serviço)
- Integração Auvo ERP legado (customização por cliente)
- Personas RH, Supplier, Inmetro Coordinator, Employment/Payroll

---

## 4. Impacto nos artefatos do baseline

| Artefato | Ação |
|---|---|
| `docs/product/mvp-scope.md` | +8 REQs (§3.4, §3.3, §3.6, §3.2, §3.11). Total passa de 62 → 70. |
| `docs/product/personas.md` | +1 persona (Aline). Total passa de 8 → 9. |
| `docs/product/journeys.md` | +3 jornadas (LGPD titular, revalidação proativa, monitoria de qualidade) + estado novo na Jornada 1 (NFS-e rejeitada). |
| `epics/ROADMAP.md` | +3 épicos (E21, E22, E23). Total passa de 20 → 23. |
| `docs/product/domain-model.md` | Entidades novas: `RequisicaoLgpd`, `ControlChartConfig`, `ControlChartReading`, `DriftAlert`, `BackupJob`, `PushSubscription`. Eventos novos: `NfseRejeitada`, `RequisicaoLgpdAberta`, `RevalidacaoDisparada`, `DriftDetectado`, `BackupConcluido`. A ampliar em sessão subsequente. |

---

## 5. Sequência de implementação recomendada

1. **Ajustes documentais (imediato):** atualizar `mvp-scope.md`, `personas.md`, `ROADMAP.md` com esta ampliação v2 (feito nesta mesma sessão).
2. **Jornadas novas:** ampliar `journeys.md` com Jornada 12, 13, 14 + estado novo na Jornada 1 (item pendente de sessão subsequente).
3. **Domain model:** adicionar entidades e eventos listados em §4 (item pendente de sessão subsequente).
4. **Backlog pós-MVP:** criar `docs/product/post-mvp-backlog.md` com os 7 itens deferidos.
5. **Decomposição:** só então decompor E15 em stories (`/decompose-stories E15`). E21, E22, E23 decompostos no devido tempo.

---

## 6. Critério de sucesso atualizado

O MVP está "no ar" quando, além do critério da ampliação v1, o laboratório-cliente consegue:

- Emitir NFS-e, receber rejeição da prefeitura, corrigir e retransmitir sem sair do Kalibrium.
- Receber pelo menos uma requisição LGPD de titular e atendê-la dentro do prazo legal.
- Monitorar pelo menos um padrão via SPC com gráfico de controle atualizado automaticamente e receber alerta de drift antes do vencimento.
- Disparar revalidação proativa em pelo menos 10 clientes recorrentes e converter ao menos 3 em OS nova dentro do primeiro mês pós-MVP.
- Ter backup diário validado para o tenant.

---

## 7. Observação R1

Fontes externas auditadas contêm arquivos proibidos (`.cursorrules`, `AGENTS.md`, `GEMINI.md`, `.superpowers/`, cache `bmad`). Nenhum foi copiado para este repo. Recomenda-se rodar `/forbidden-files-scan` ao final desta sessão por higiene.
