# Roadmap de Épicos — Kalibrium V2

> **Gerado por:** epic-decomposer (versão original 2026-04-12) + 3 ampliações em 2026-04-16 (v1 offline-first + v2 auditoria comparativa + v3 re-auditoria independente)
> **Base:** PRD + mvp-scope.md (80 REQs após v1+v2+v3) + domain-model.md ampliado + ADR-0001 (backend) + ADR-0015 (stack offline-first mobile) + **ADR-0016 (isolamento multi-tenant, v3)** + `PRD-ampliacao-2026-04-16-v2.md` + `PRD-ampliacao-2026-04-16-v3.md`
> **Primeiro cliente-âncora:** empresa de serviço técnico em campo + laboratório de calibração de bancada acreditado Cgcre, opera 90% offline, **produção alvo 2026** (RTC 2026 aplicável)
> **Status:** AMPLIADO 2026-04-16 — 11 épicos novos adicionados (E15-E20 v1 + E21-E23 v2 + E24-E25 v3); nenhum épico original removido. Backup do estado anterior em `ROADMAP-backup-2026-04-16.md`.

---

## Visão Geral

| Indicador | Original 2026-04-12 | v1 | v2 | **v3** |
|---|---|---|---|---|
| Total de épicos | 14 | 20 | 23 | **25** |
| Épicos MVP (P0) | 8 | 14 | 17 | **19** (8 originais + E15-E20 + E21-E23 + **E24-E25**) |
| Épicos pós-MVP (P1) | 4 | 4 | 4 | 4 |
| Épicos visão futura (P2/P3) | 2 | 2 | 2 | 2 |
| Stories estimadas (MVP) | ~63 | ~125 | ~150 | **~175** |
| Stories estimadas (total) | ~104 | ~170 | ~195 | **~220** |

**Nota importante:** nenhum épico foi removido. E01/E02/E03 já foram mergidos (implementados); serão auditados para reaproveitamento de backend e dados. Frontend será refeito na nova stack (ver ADR-0015). A ordem dos épicos restantes (E04-E14) permanece, mas **depende de E15 e E16 ficarem prontos antes** — foundational offline-first.

---

## Sequência de Execução Ampliada

```
E01 — Setup e Infraestrutura                                                       [MERGED]
 └─► E02 — Multi-tenancy, Auth e Planos                                             [MERGED, precisa ampliar com device binding + biometria via E15/E16]
      └─► E03 — Cadastro Core (Clientes, Instrumentos, Padrões)                     [MERGED, frontend será refeito]

──────── PAUSA E03 ATÉ AQUI + NOVOS ÉPICOS FOUNDATIONAL ────────

      └─► E15 — PWA Shell Offline-First + Capacitor Wrapper (NOVO, P0)              [foundational]
           └─► E16 — Sync Engine (PowerSync/ElectricSQL + merge por campo)          [NOVO, P0, foundational]
                ├─► E17 — UMC e Frota Operacional (NOVO, P0)
                ├─► E18 — Caixa de Despesa por OS (NOVO, P0)
                ├─► E19 — Estoque Multinível (NOVO, P0)
                ├─► E20 — CRM Offline do Vendedor (NOVO, P0)
                └─► E04 — Ordens de Serviço e Fluxo Operacional (ORIGINAL, agora offline-capable)
                     └─► E05 — Laboratório e Calibração (bancada + campo 4 domínios + balança rodoviária + massa industrial grande)
                          └─► E06 — Certificado de Calibração (PDF/A + RBC, geração offline no dispositivo)
                               ├─► E07 — Fiscal: NFS-e Rondonópolis/MT (emissão pelo gestor em campo ou escritório)
                               │    └─► E08 — Financeiro e Contas a Receber
                               └─► E09 — Portal do Cliente Final
 E03 ─► E10 — GED: Gestão Documental (transversal)
 E04 ─► E11 — Dashboard Operacional e Relatórios (com custo real por OS + ocupação UMC)
 E02 ─► E12 — Comunicação: E-mail e Notificações (transversal, com push offline-first)
 E07 ─► E21 — Compliance Fiscal + LGPD + Backup + Push (NOVO v2, P0)
 E05 ─► E22 — SPC + Drift + Qualidade Metrológica ISO 17025 (NOVO v2, P0)
 E20 ─► E23 — Revalidação Proativa + Engajamento de Cliente Recorrente (NOVO v2, P0)
 E04 + E22 ─► E24 — Operação Robusta + Qualidade ISO 17025 Ampliada (NOVO v3, P0)
                   (competência + dual sign-off + suspensão retroativa + re-despacho + preventiva + garantia + round-robin)
 E07 ─► E25 — Reforma Tributária 2026 (IBS/CBS/cIndOp) (NOVO v3, P0, alvo <2026-01)
 E08 ─► E13 — Procurement e Fornecedores        [P1]
 E05 ─► E14 — LMS e Habilitações Técnicas       [P1]
```

---

## Épicos — visão completa

| ID | Título | Prioridade | Dependências | Stories est. | Complexidade | Status |
|---|---|---|---|---|---|---|
| E01 | Setup e Infraestrutura | P0 | — | 6 | média | **merged** |
| E02 | Multi-tenancy, Auth e Planos | P0 | E01 | 8 | alta | **merged** (precisa ampliar device binding + biometria) |
| E03 | Cadastro Core | P0 | E02 | 7 | média | **merged** (frontend será refeito em E15+) |
| **E15** | **PWA Shell Offline-First + Capacitor Wrapper** | **P0** | **E03 + ADR-0015** | **~10** | **muito alta** | **backlog (novo)** |
| **E16** | **Sync Engine + Merge por Campo + Conflito** | **P0** | **E15** | **~12** | **muito alta** | **backlog (novo)** |
| **E17** | **UMC e Frota Operacional (veículo, diário de bordo, agenda)** | **P0** | **E16** | **~8** | **alta** | **backlog (novo)** |
| **E18** | **Caixa de Despesa por OS (foto obrigatória, 3 origens de dinheiro, triagem)** | **P0** | **E16** | **~10** | **alta** | **backlog (novo)** |
| **E19** | **Estoque Multinível (4 locais + movimentação)** | **P0** | **E16** | **~8** | **média** | **backlog (novo)** |
| **E20** | **CRM Offline do Vendedor (500 clientes offline + orçamento em campo)** | **P0** | **E16** | **~10** | **alta** | **backlog (novo)** |
| E04 | Ordens de Serviço e Fluxo Operacional (agora offline-capable) | P0 | E15 + E16 | 12 (ampliado de 9) | muito alta | backlog (ampliado) |
| E05 | Laboratório e Calibração (bancada + campo 6 domínios) | P0 | E04 | 12 (ampliado de 9) | muito alta | backlog (ampliado) |
| E06 | Certificado de Calibração (PDF/A + RBC, geração offline) | P0 | E05 | 7 | alta | backlog |
| E07 | Fiscal: NFS-e | P0 | E06 | 7 (ampliado de 6) | alta | backlog (ampliado — estado "preparada offline") |
| E08 | Financeiro e Contas a Receber | P0 | E07 | 5 | média | backlog |
| E09 | Portal do Cliente Final | P0 | E06 | 6 | média | backlog |
| E10 | GED: Gestão Documental | P0 | E03 | 5 | média | backlog |
| E11 | Dashboard Operacional e Relatórios (com custo real/OS + UMC) | P0 | E04 | 7 (ampliado de 5) | média | backlog (ampliado) |
| E12 | Comunicação: E-mail e Notificações (com push offline-first) | P0 | E02 + E15 | 6 (ampliado de 4) | média | backlog (ampliado) |
| **E21** | **Compliance Fiscal + LGPD + Backup + Push (v2)** | **P0** | **E07 + E12** | **~10** | **alta** | **backlog (novo v2)** |
| **E22** | **SPC + Drift + Qualidade Metrológica ISO 17025 (v2)** | **P0** | **E05** | **~8** | **alta** | **backlog (novo v2)** |
| **E23** | **Revalidação Proativa + Engajamento de Cliente Recorrente (v2)** | **P0** | **E20 + E12** | **~7** | **média** | **backlog (novo v2)** |
| **E24** | **Operação Robusta + Qualidade ISO 17025 Ampliada (v3)** — competência + dual sign-off + suspensão retroativa + re-despacho + preventiva + garantia + round-robin | **P0** | **E04 + E22 + ADR-0016** | **~15** | **alta** | **backlog (novo v3)** |
| **E25** | **Reforma Tributária 2026 (IBS/CBS/cIndOp) (v3)** — produção em 2026 | **P0** | **E07** | **~8** | **alta** | **backlog (novo v3, alvo <2026-01)** |
| E13 | Procurement e Fornecedores | P1 | E08 | 6 | média | backlog |
| E14 | LMS e Habilitações Técnicas (extensões) | P1 | E05 | 5 | média | backlog |

---

## Detalhamento dos épicos novos (E15-E20)

### E15 — PWA Shell Offline-First + Capacitor Wrapper (NOVO, P0)

**Objetivo:** criar a fundação do aplicativo cliente (PWA + Capacitor) que todos os módulos subsequentes usarão. Substitui o frontend antigo (Livewire/Blade) por stack offline-first (React + Ionic + Capacitor + SQLite local).

**Escopo:**
- Scaffold do projeto frontend (React + TypeScript + Ionic + Capacitor + Vite).
- PWA instalável (Service Worker, manifesto, ícones, splash).
- Wrapper Capacitor configurado para iOS e Android (Xcode project + Gradle project).
- Banco local SQLite via `@capacitor-community/sqlite` com SQLCipher.
- IndexedDB fallback para desktop web.
- Biometria via Capacitor Biometric plugin + WebAuthn fallback.
- Autenticação JWT long-lived + refresh + device binding (integra com E02 ampliado).
- Criptografia local AES-256 (chave derivada do login).
- Wipe remoto (bloqueio na próxima abertura quando flag está acionada).
- Testes unitários + E2E (Playwright no web, XCUITest/Espresso nos wrappers).
- CI/CD estendido para buildar iOS/Android além de web.

**Dependências:**
- E03 merged (backend Laravel + endpoints REST disponíveis).
- ADR-0015 aprovado.
- Setup de contas de developer Apple + Google (responsabilidade do PM).

**Não-objetivo:**
- Módulos de negócio (CRUD de cliente, OS, etc) — ficam para E17-E20 e E04+.
- Sync engine completo — fica para E16.

### E16 — Sync Engine + Merge por Campo + Conflito (NOVO, P0)

**Objetivo:** implementar a camada de sincronização entre dispositivo offline e backend. É o motor que faz tudo funcionar offline-first.

**Escopo:**
- PoC técnico: avaliar PowerSync vs ElectricSQL vs custom sync engine (primeira spike do épico).
- Sync engine escolhido integrado (ADR-0016 criada após PoC).
- Merge por campo (last-write-wins granular).
- Detecção de conflito real (edição dupla no mesmo campo).
- Tela de resolução de conflito para o responsável da OS.
- Fila de sincronização local (observável: quantos registros pendentes, idade do mais antigo).
- Modo avião forçado (toggle de teste).
- Audit log de sync server-side (append-only).
- Tempo real online (quando todos conectados, mudanças fluem em tempo real).
- Sync silenciosa em background.
- Testes de 4 dias offline com 8 OS + 500 clientes (volume realista).

**Dependências:**
- E15 merged (PWA shell + banco local funcionando).

### E17 — UMC e Frota Operacional (NOVO, P0)

**Objetivo:** cadastro, agenda e operação da UMC (Unidade Móvel de Calibração) e dos veículos operacionais.

**Escopo:**
- Cadastro de UMC (placa, chassi, capacidade guindaste, massas-padrão a bordo).
- Cadastro de veículo operacional (placa, modo_uso assinado/compartilhado, técnico responsável).
- Agenda da UMC (bloqueio por OS + manutenção preventiva).
- Reserva de veículo compartilhado.
- Diário de bordo do motorista UMC (KM, abastecimento com foto, pedágio com foto).
- Diário de bordo de veículo operacional.
- Integração com OS (OS do tipo campo-UMC bloqueia agenda).

**Dependências:** E16 merged.

### E18 — Caixa de Despesa por OS (NOVO, P0)

**Objetivo:** ciclo completo da despesa de campo, do gasto à aprovação e reembolso.

**Escopo:**
- Registro de despesa com foto obrigatória do cupom + OS obrigatória.
- 3 origens de dinheiro: cartão corporativo, adiantamento, próprio bolso.
- Saldo otimista em tempo real por cartão/adiantamento.
- Triagem pelo escritório (aprovar/rejeitar/reclassificar).
- Aprovação final em alçada (escritório até X, gerente acima).
- Fila de reembolso + execução PIX/transferência em lote.
- Conciliação com fatura do cartão corporativo (CSV + matching).
- Relatório de custo real por OS.

**Dependências:** E16 merged.

### E19 — Estoque Multinível (NOVO, P0)

**Objetivo:** controlar estoque de padrões, peças e materiais em 4 locais distintos.

**Escopo:**
- 4 locais: laboratório, UMC, veículo operacional, carro pessoal do técnico.
- Cadastro + conferência de estoque por local.
- Movimentação entre locais com responsável + timestamp.
- Consulta offline do estoque local do dispositivo.
- Alertas de padrão vencendo (30 dias antes) por local.
- Integração com execução de calibração (técnico só pode usar padrão que está no estoque acessível a ele).

**Dependências:** E16 merged.

### E20 — CRM Offline do Vendedor (NOVO, P0)

**Objetivo:** CRM mobile offline para vendedor externo (Persona 5 — Patrícia).

**Escopo:**
- Carteira de clientes por vendedor (500 por vendedor).
- Ficha completa offline (cliente + histórico + certificados + contatos).
- Registro de visita com nota de voz + transcrição + foto + GPS.
- Orçamento em campo offline (PDF gerado localmente + envio por WhatsApp).
- Conversão de orçamento aceito em OS.
- Follow-up automático (lembrete pro vendedor).
- Pipeline em tempo real pro gerente (sincroniza conforme vendedor conecta).
- Conflito de carteira (cliente pertence a 1 vendedor, exceto gerente).

**Dependências:** E16 merged.

---

## Detalhamento dos épicos novos (E21-E23) — ampliação v2

### E21 — Compliance Fiscal + LGPD + Backup + Push (NOVO v2, P0)

**Objetivo:** fechar lacunas de operação real identificadas na auditoria comparativa — emissão fiscal confiável com ciclo de rejeição, jornada LGPD ponta-a-ponta, backup auditável e push notification nativo.

**Escopo:**
- Retransmissão de NFS-e rejeitada (`REQ-FIS-007`): captura motivo de rejeição, apresenta ao operador, permite correção e retransmite sem perder trilha.
- Retenção fiscal correta por regime (`REQ-FIS-008`): ISS, IR, INSS, PIS, COFINS conforme Simples e Lucro Presumido; arredondamento half-even; conformidade NT CGNFS-e vigente.
- Push notification nativo (`REQ-FLX-007`): iOS + Android via Capacitor Push, integra com FCM/APNs, categorização, consentimento no onboarding.
- Jornada LGPD do titular (`REQ-CMP-006`): canal de requisição, triagem DPO, atendimento em 15 dias, log imutável, alerta de breach em 72h.
- Backup por tenant (`REQ-CMP-007`): job agendado, restore em ambiente isolado, checksum, retenção 30d + snapshot mensal 12m, alerta de falha.

**Dependências:** E07 (NFS-e) e E12 (notificações) merged.

### E22 — SPC + Drift + Qualidade Metrológica ISO 17025 (NOVO v2, P0)

**Objetivo:** entregar a camada de monitoria metrológica exigida por laboratório acreditado Cgcre — SPC dos padrões, drift automático, trilha de auditoria navegável.

**Escopo:**
- Gráfico de controle SPC (`REQ-MET-009`): histórico de cada padrão plotado com UCL/LCL, atualização automática.
- Drift automático (`REQ-MET-010`): comparação com limites de aceitabilidade, alerta para Responsável de Qualidade (Persona 8), bloqueio automático se drift cruzar limite crítico.
- Tela de monitoria para Responsável de Qualidade (Persona 8 nova): lista de padrões com status, última leitura, próximo vencimento, drift.
- Trilha de auditoria navegável: busca por padrão → calibrações feitas → certificados emitidos → clientes.
- Export CSV para análise complementar em Excel (compatibilidade com planilhas legadas).

**Dependências:** E05 (Laboratório e Calibração) merged.

### E23 — Revalidação Proativa + Engajamento de Cliente Recorrente (NOVO v2, P0)

**Objetivo:** transformar histórico de calibração em receita recorrente automática — o Kalibrium avisa o cliente antes do vencimento e oferece agendamento com 1 clique.

**Escopo:**
- Motor de detecção de vencimento próximo (90/60/30 dias) por instrumento calibrado.
- Cadência multicanal (e-mail + WhatsApp com consentimento) com template editável pelo tenant.
- Link de aceitação de agendamento → cria OS automaticamente → notifica vendedor responsável (via push `REQ-FLX-007`).
- Pipeline de engajamento: enviado → visto → clicado → agendado → convertido em OS.
- Relatório mensal de conversão por vendedor para o gerente (Persona 1).
- Possibilidade do cliente final recusar revalidação (registra motivo, para cadência).

**Dependências:** E20 (CRM Offline do Vendedor) + E12 (notificações) merged.

---

## Detalhamento dos épicos novos (E24-E25) — ampliação v3

### E24 — Operação Robusta + Qualidade ISO 17025 Ampliada (NOVO v3, P0)

**Objetivo:** fechar os gaps de operação crítica identificados pela re-auditoria comparativa independente. Tornar o laboratório acreditado Cgcre **operacionalmente resiliente** — sem deixar brecha de conformidade, sem deixar OS órfã, sem deixar certificado emitido com padrão comprometido.

**Escopo:**
- Bloqueio de técnico sem competência (`REQ-MET-011`): enforcement em atribuição + execução, inclusive offline.
- Dual sign-off de certificado (`REQ-MET-012`): executor + verificador distinto, com biometria. Integra com Persona 8 (Aline) ou Persona 1 (Marcelo).
- Suspensão retroativa de certificados (`REQ-MET-013`): detecção + triagem + cadência aos clientes afetados + geração de OS de garantia.
- Agendamento automático de recalibração de padrões (`REQ-MET-014`): scheduler + bloqueio de agenda + estado `aguardando_retorno`.
- Agendamento automático de manutenção preventiva de UMC e veículos (`REQ-UMC-005`): regras configuráveis por tempo/KM/evento.
- Despacho automático round-robin (`REQ-OPL-005`): considerando competência + disponibilidade + carga atual.
- Re-despacho automático quando técnico fica indisponível (`REQ-OPL-006`): inclui notificação ao cliente.
- OS de garantia com classificação e custo zero (`REQ-OPL-007`): rastreabilidade preservada, fatura R$ 0,00.
- Isolamento multi-tenant formalizado (`REQ-TEN-007` + ADR-0016): Global Scope + RLS nas 10 tabelas críticas + teste de isolamento em CI.

**Dependências:** E04 (OS), E22 (SPC/drift), ADR-0016 aprovado.

**Observação sobre ordem:** este épico é denso (~15 stories). Sugere-se decompor em sub-blocos: E24.1 Competência + Dual Sign-off, E24.2 Suspensão Retroativa, E24.3 Despacho + Re-despacho, E24.4 Agendamento Preventivo, E24.5 OS de Garantia, E24.6 Isolamento Multi-tenant — cada um executável independentemente. Decisão final no `/decompose-stories E24`.

### E25 — Reforma Tributária 2026 (IBS/CBS/cIndOp) (NOVO v3, P0, alvo antes de 2026-01-01)

**Objetivo:** garantir que o Kalibrium emita NFS-e com os novos tributos exigidos pela LC 214/2025 e NT da CGNFS-e a partir do cronograma oficial de 2026. **Produção confirmada para 2026 pelo PM** — este épico é crítico e tem prazo fixo.

**Escopo:**
- `REQ-FIS-009`: cálculo, preenchimento e transmissão de IBS, CBS, cIndOp conforme cronograma oficial.
- Versionamento de regras tributárias por vigência (matriz `ConfiguracaoTributariaRTC`).
- Matriz de compatibilidade com regimes existentes (`REQ-FIS-008` — Simples / Lucro Presumido).
- Homologação em ambiente de sandbox da RFB antes de promover a produção.
- Fallback seguro: se operação ocorrer antes da data oficial vigente, emite conforme regra anterior.
- Monitoramento de atualizações oficiais (alerta para responsável fiscal do tenant).
- Integração com ciclo `REQ-FIS-007` (rejeição/retransmissão): motivos de rejeição relacionados a RTC marcados como `rtc_2026` no log.

**Dependências:** E07 (NFS-e) merged. Preferencialmente E21 (compliance fiscal v2) também, para reutilizar retenção correta e ciclo de rejeição.

**Risco:** cronograma oficial da RFB pode mudar. Monitorar publicação de NT e LC complementares; manter canal de atualização.

---

## Cobertura de Requisitos MVP (ampliado)

| Domínio | REQs | Coberto por |
|---|---|---|
| TEN (Cadastro e tenant) | REQ-TEN-001..006 + **007 (v3)** | E02, E03 (+ E15 para device binding), **E24 + ADR-0016 (isolamento formalizado)** |
| MET (Metrologia e calibração) | REQ-MET-001..008 + **009..010 (v2) + 011..014 (v3)** | E03, E05, E06, **E22 (SPC + drift), E24 (competência + dual sign-off + suspensão retroativa + recalibração auto)** |
| FLX (Fluxo fim a fim) | REQ-FLX-001..006 + **007** | E04, E09, E12, **E21 (push)** |
| FIS (Fiscal) | REQ-FIS-001..006 + **007..008 (v2) + 009 (v3)** | E07, E08, **E21 (retransmissão + retenções), E25 (RTC 2026)** |
| OPL (Operação) | REQ-OPL-001..004 + **005..007 (v3)** | E04, E11, E17 (UMC agenda), **E24 (despacho + re-despacho + garantia)** |
| CMP (Compliance) | REQ-CMP-001..005 + **006..007** | E02, E05, E10, E16 (sync log), **E21 (LGPD + backup)** |
| **FLD (Operação de campo)** | **REQ-FLD-001..006** | **E04 (ampliado) + E15 + E16** |
| **UMC + VHL** | **REQ-UMC-001..004 + 005 (v3), REQ-VHL-001..003** | **E17, E24 (preventiva automática)** |
| **DSP (Despesa por OS)** | **REQ-DSP-001..008** | **E18** |
| **INV (Estoque multinível)** | **REQ-INV-001..004** | **E19** |
| **CRM (Vendedor offline)** | **REQ-CRM-001..007 + 008 (v2)** | **E20 + E23 (revalidação proativa)** |
| **SEC (Segurança móvel)** | **REQ-SEC-001..005** | **E15 (primariamente) + E16** |
| **SYN (Sync engine)** | **REQ-SYN-001..006** | **E16** |
| GED (P0 adicionais) | FR-GED-01,03,06,07 | E10 |
| LMS (P0 adicionais) | FR-LMS-03,05 | E04 (bloqueio básico), E14 (extensões) |
| Pricing (P0 adicionais) | FR-PRI-01,02,03 | E02 |
| Email (P0 adicionais) | FR-EML-01,03,04 | E12 (ampliado — push offline) |

**Cobertura MVP: 100% dos 80 REQs (29 originais + 33 v1 + 8 v2 + 10 v3) + 12 FRs P0 adicionais.**

---

## Mudanças em E01/E02/E03 (já merged)

Os 3 épicos merged precisam de auditoria técnica antes de iniciar E15:

- **E01:** setup Laravel 13 + PostgreSQL + Redis + CI. Backend permanece. Frontend antigo (Livewire/Blade) será descartado — novo frontend em E15.
- **E02:** auth funciona. Ampliação necessária via E15: JWT long-lived, refresh, device binding, biometria, wipe remoto.
- **E03:** modelo de dados aproveitável (clientes + contatos). Frontend refeito em E15+. Regras de negócio permanecem.

Auditoria técnica dos 3 deve rodar como pré-trabalho de E15 (spike técnico de 1-2 dias antes da decomposição em stories).

---

## Notas de princípio

1. **PRD e roadmap são aditivos.** Nenhum épico foi removido; E15-E20 foram inseridos; E01-E14 permanecem (alguns com ampliação de escopo).
2. **Ordem mudou por dependência técnica.** E15 (PWA shell) e E16 (sync engine) precisam vir antes de E04+ porque sem eles, nenhum módulo de negócio funciona offline-first.
3. **E01/E02/E03 não foram desperdiçados.** Backend Laravel + PostgreSQL + auth core são aproveitáveis. Frontend refeito.
4. **Próximo passo:** PM aprova este roadmap ampliado → decompor E15 em stories (`/decompose-stories E15`) → auditoria de planejamento → início de execução.
