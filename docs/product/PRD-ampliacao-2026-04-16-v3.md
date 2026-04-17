# PRD — Ampliação v3 (2026-04-16)

> **Status:** aditivo — consolida decisões de produto do PM pós re-auditoria comparativa independente (`docs/audits/comparativa-externa-reaudit-2026-04-16.md`).
> **Antecedentes:** ampliação v1 (offline-first sistêmico) + v2 (fiscal+LGPD+push+backup+SPC+drift+revalidação+Persona 8). Esta v3 incorpora achados do auditor independente (contexto isolado R3/R11) que identificou 13 gaps alto impacto no baseline pós-v2.
> **Decisões do PM (2026-04-16):**
> - Aceita as 4 recomendações fortes do Claude Code (G2, G3, G6, G8).
> - Produção em 2026 → RTC 2026 (IBS/CBS/cIndOp) entra como MVP (G9).
> - "Preparado para tudo" → absorve o pacote completo de alto impacto na versão MVP viável.
> **Princípio:** aditivo, nada removido do baseline ampliado.

---

## 1. Itens aprovados para o MVP v3 (10 adições)

### 1.1. Pacote A — Metrologia e Qualidade ISO 17025 robusta

| # | Gap | REQ novo | Épico |
|---|---|---|---|
| G2 | Bloqueio de técnico sem competência vigente para o domínio metrológico (ISO 17025 §6.2) | `REQ-MET-011` | E24 |
| G1 | Dual sign-off no certificado (executor + verificador técnico) antes de emitir | `REQ-MET-012` | E24 |
| G3 | Suspensão retroativa de certificados quando padrão falha na recalibração externa | `REQ-MET-013` | E24 |
| G8a | Agendamento automático de recalibração de padrões de referência (scheduler + bloqueio de agenda) | `REQ-MET-014` | E24 |

### 1.2. Pacote B — Operação e Despacho Resiliente

| # | Gap | REQ novo | Épico |
|---|---|---|---|
| G4 | Despacho automático round-robin (versão MVP; skill-match + geo fica PÓS) | `REQ-OPL-005` | E24 |
| G6 | Re-despacho automático quando técnico/motorista fica indisponível (doença, UMC quebrou, conflito) com notificação ao cliente | `REQ-OPL-006` | E24 |
| G7 | OS de garantia com classificação + custo zero (cost allocation completa fica PÓS) | `REQ-OPL-007` | E24 |
| G8b | Agendamento automático de manutenção preventiva de UMC + veículos operacionais (scheduler por KM/tempo + bloqueio de agenda) | `REQ-UMC-005` | E24 |

### 1.3. Pacote C — Fiscal RTC 2026 (produção em 2026 confirmada)

| # | Gap | REQ novo | Épico |
|---|---|---|---|
| G9 | Reforma Tributária 2026 — IBS/CBS/cIndOp: cálculo, transmissão e exibição dos novos tributos conforme cronograma oficial da RFB para NFS-e em 2026 | `REQ-FIS-009` | E25 |

### 1.4. Pacote D — Arquitetura e Governança

| # | Gap | Entrega | Épico |
|---|---|---|---|
| G10 | Isolamento multi-tenant formalizado via ADR (hoje implícito em `REQ-TEN-005`) | **ADR-0016** criado + `REQ-TEN-007` explícito | E01 (retrofit) / E24 |
| G12 | Refinamento da Persona 1 (Marcelo, sócio-gerente) incluindo dimensão financeira/CFO — sem criar nova persona | Atualização em `personas.md` Persona 1 | — |

---

## 2. Itens diferidos para PÓS-MVP

| # | Gap | Razão |
|---|---|---|
| G5 | SLA completo com timer + pausa/retoma + escalonamento multi-nível | Complexo, alto valor; entra junto com service-tickets/helpdesk do backlog v2 |
| G11 | Billing SaaS nativo + self-service onboarding do tenant (Stripe/provedor direto) | MVP pode operar com cobrança manual do tenant pelo fabricante Kalibrium; self-service vem quando escala justificar |
| G13 | Cobrança automática do cliente final por faixa de atraso (reminder, escalação, negativação) | MVP opera com cobrança manual; automação pós |

Estes três itens são adicionados ao `docs/product/post-mvp-backlog.md` com gatilhos de reentrada.

---

## 3. Impacto nos artefatos do baseline (v3 sobre v2)

| Artefato | Ação |
|---|---|
| `docs/product/mvp-scope.md` | +10 REQs (MET, OPL, UMC, FIS, TEN). Total: 70 → **80**. |
| `docs/product/personas.md` | Refinamento da Persona 1 com dimensão financeira/CFO. Total permanece **9 personas**. |
| `docs/product/journeys.md` | Ampliação de Jornada 1 (passo 6 com dual sign-off + bloqueio por competência) e Jornada 11 (agenda com preventiva automática). +3 jornadas novas: J15 Suspensão retroativa, J16 Re-despacho automático, J17 OS de garantia. Total: 14 → **17 jornadas**. |
| `docs/product/domain-model.md` | +6 entidades (`CompetenciaTecnicaVigente`, `DualSignOffCertificado`, `SuspensaoRetroativa`, `AgendamentoPreventivo`, `ConfiguracaoTributariaRTC`, `RegraDespacho`), +5 estados, +12 eventos. |
| `epics/ROADMAP.md` | +2 épicos: E24 (Operação Robusta + Qualidade ISO 17025 ampliada) e E25 (Reforma Tributária 2026). Total: 23 → **25 épicos**. |
| `docs/adr/0016-multi-tenant-isolation.md` | **ADR NOVO** formalizando estratégia de isolamento (row-level + tenant_id discriminator enforçado em query scope). |
| `docs/product/post-mvp-backlog.md` | +3 itens (SLA completo, Billing SaaS, Cobrança automática). |

---

## 4. Sequência recomendada

1. E01 → E02 → E03 permanecem merged.
2. E15-E20 (v1 offline-first foundational) seguem como bloco de foundational.
3. E21-E23 (v2 — fiscal/LGPD/push/SPC/drift/revalidação) dependem de E15+E16.
4. **E24 (v3 — operação+qualidade robusta)** — depende de E04 + E05 + E17 + E20.
5. **E25 (v3 — RTC 2026)** — depende de E07; data-alvo: operacional antes de janeiro 2026 (produção 2026 confirmada).
6. ADR-0016 (multi-tenant) precisa ficar pronto antes de E15 iniciar (afeta decisão de schema).

---

## 5. Critério de sucesso atualizado (v3)

Além dos critérios das ampliações v1 e v2, o MVP v3 está "no ar" quando:

- Calibração não pode ser executada por técnico sem habilitação vigente no domínio (`REQ-MET-011` enforcement).
- Certificado não é emitido sem dual sign-off (`REQ-MET-012`).
- Falha de padrão dispara suspensão retroativa e notificação aos clientes afetados (`REQ-MET-013`).
- Sistema agenda automaticamente recalibração de padrão e manutenção preventiva de veículo/UMC (`REQ-MET-014`, `REQ-UMC-005`).
- OS é redistribuída automaticamente quando técnico fica indisponível (`REQ-OPL-006`).
- NFS-e é emitida com IBS/CBS/cIndOp corretos a partir de 2026-01-01 (`REQ-FIS-009`).
- Isolamento multi-tenant é validado por teste automatizado (ADR-0016 + `REQ-TEN-007`).

---

## 6. Observação crítica do auditor independente

O auditor independente destacou: **épicos E15-E25 existem no ROADMAP mas sem diretório em `epics/`**. Recomendação: antes de iniciar trabalho técnico em qualquer épico, executar `/decompose-stories ENN` para gerar `epics/ENN/stories/INDEX.md` + story contracts. Sem isso, há risco de drift entre ROADMAP e execução.

Esta observação fica como **lembrete operacional** — não é gap de produto, é gap de governança de planejamento.
