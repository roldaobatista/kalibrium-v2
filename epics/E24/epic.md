# E24 — Operação Robusta e Qualidade ISO 17025 Ampliada

## Objetivo

Elevar o nível de conformidade metrológica e operacional do Kalibrium aos requisitos estritos da ISO 17025:2017: bloqueio de técnico sem competência vigente, dual sign-off obrigatório no certificado, suspensão retroativa de certificados quando padrão falha na recalibração, agendamento automático de recalibração de padrões e despacho resiliente com re-despacho automático de OS.

## Valor entregue

Laboratório acreditado pelo Cgcre/Inmetro consegue demonstrar em auditoria RBC que nenhuma calibração foi executada por técnico não habilitado, que nenhum certificado foi emitido sem verificação independente e que certificados emitidos com padrão que falhou posteriormente são retroativamente suspensos e clientes notificados — três requisitos críticos que hoje exigem controle manual em planilha. Para operação, re-despacho automático de OS quando técnico fica indisponível reduz cancelamentos e atrasos.

## Escopo

### Bloqueio por competência técnica (REQ-MET-011)
- Cada técnico tem perfil de competências vigentes: domínio metrológico (ex.: massa, temperatura, pressão), faixa de medição habilitada, validade (data de vencimento do treinamento/certificação interna)
- Ao atribuir OS a técnico: validação de competência obrigatória — técnico sem competência vigente no domínio da OS é bloqueado com mensagem explicativa
- Bloqueio no front (impossibilita seleção) e no back (validação server-side)
- Gestão de competências: gerente registra competência, data de início, data de vencimento, documento comprobatório (upload)
- Entidades: `CompetenciaTecnicaVigente`, `DominioMetrologico`

### Dual sign-off no certificado (REQ-MET-012)
- Certificado de calibração requer dois assinantes: executor (quem fez a calibração) e verificador técnico (quem revisou — pode ser o gerente técnico ou outro técnico habilitado no mesmo domínio)
- Executor e verificador não podem ser a mesma pessoa
- Fluxo: executor envia para revisão → verificador revisa e assina → certificado liberado para emissão
- Certificado não pode ser emitido sem os dois sign-offs confirmados (bloqueio hard)
- Estado adicional no ciclo do certificado: `aguardando_verificacao`
- Entidade: `DualSignOffCertificado`

### Suspensão retroativa de certificados (REQ-MET-013)
- Quando padrão de referência é reprovado em recalibração externa: sistema identifica todos os certificados emitidos com aquele padrão no período de uso suspeito
- Suspensão em lote: todos os certificados afetados passam para estado `suspenso_retroativo`
- Notificação automática aos clientes afetados: e-mail com lista de certificados suspensos, motivo e orientação para reagendamento de calibração
- Registro de auditoria: quem acionou a suspensão, quais certificados foram afetados, quais clientes foram notificados, timestamps
- Entidade: `SuspensaoRetroativa`

### Agendamento automático de recalibração (REQ-MET-014)
- Scheduler: 90 dias antes do vencimento da calibração de cada padrão de referência, cria automaticamente OS interna de recalibração
- OS interna de recalibração: `tipo = recalibracao_interna`, atribuída ao responsável de qualidade (Aline)
- Se padrão não for recalibrado antes do vencimento: bloqueio automático (estado `bloqueado_recalibracao_vencida`) — não pode ser associado a OS de cliente
- Histórico de recalibrações de cada padrão disponível para auditoria

### Despacho round-robin e re-despacho automático (REQ-OPL-005, REQ-OPL-006)
- Despacho round-robin MVP: OS atribuída automaticamente ao próximo técnico disponível na fila (rotação simples, sem skill-match geográfico — pós-MVP)
- Re-despacho automático: quando técnico atribuído fica indisponível (marcado `indisponivel`, UMC em manutenção, ou conflito de agenda detectado), sistema reatribui automaticamente a OS para próximo técnico disponível
- Notificação automática ao cliente quando OS é redistribuída (e-mail via E12): novo técnico, data mantida ou sugestão de nova data
- Log de re-despacho: técnico original, motivo, novo técnico, timestamp

### OS de garantia (REQ-OPL-007)
- OS criada com `tipo = garantia`: custo zero para o cliente, campos de classificação de motivo (defeito do padrão, erro de execução, reclamação do cliente)
- OS de garantia é rastreada separadamente no relatório de qualidade
- Não gera NFS-e nem cobrança
- Custo interno da OS de garantia registrado (mão de obra + materiais) para análise de causa-raiz

### Agendamento de manutenção preventiva (REQ-UMC-005)
- Scheduler por KM ou por tempo: quando UMC ou veículo operacional atinge o threshold configurado (ex.: 5.000 km ou 6 meses), cria automaticamente evento de manutenção preventiva
- Evento bloqueia agenda do veículo/UMC até manutenção ser concluída e registrada
- Alerta ao gestor e ao administrativo 15 dias antes do threshold estimado

## Fora de escopo
- Skill-match geográfico no despacho (distância + especialidade) — pós-MVP (G4 diferido)
- SLA com timer e escalonamento multi-nível — pós-MVP (G5 diferido)
- Emissão de NF-e para OS de garantia — fora do escopo (serviço sem nota)
- Acreditação Cgcre automática (submissão de evidências via API Inmetro) — não existe API pública

## Acceptance Criteria do épico

- **AC-E24-01:** Tentativa de atribuir OS de calibração de temperatura a técnico sem competência em temperatura vigente é bloqueada no front e no back com mensagem do domínio faltante.
- **AC-E24-02:** Certificado não pode ser emitido sem sign-off do executor E do verificador; executor e verificador não podem ser a mesma pessoa.
- **AC-E24-03:** Padrão reprovado em recalibração: todos os certificados emitidos no período suspeito passam para `suspenso_retroativo` e clientes recebem e-mail de notificação em até 1 hora.
- **AC-E24-04:** 90 dias antes do vencimento do padrão, OS interna de recalibração criada automaticamente e atribuída ao responsável de qualidade.
- **AC-E24-05:** OS redistribuída automaticamente quando técnico original é marcado indisponível; cliente notificado por e-mail.
- **AC-E24-06:** OS de garantia criada com custo zero para cliente, motivo registrado e visível no relatório de qualidade.
- **AC-E24-07:** Scheduler de manutenção preventiva de UMC dispara 15 dias antes do threshold; agenda bloqueada ao atingir threshold.

## Dependências

### Diretas (bloqueiam início)
- E04 merged (OS — bloqueio por competência e dual sign-off são extensões do fluxo de OS)
- E22 merged (SPC e drift — suspensão retroativa complementa o monitoramento de E22)
- ADR-0016 aceita (competências e sign-offs tenant-scoped)

### Transitivas
- E17 merged (UMC — agendamento de manutenção preventiva de frota)
- E05 merged (certificados — suspensão retroativa afeta certificados de E05)
- E12 merged (e-mail — notificação de suspensão e re-despacho)

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- ISO 17025:2017 §6.2 (Pessoal), §6.4 (Equipamentos), §7.8 (Relatório de resultados)

## Definition of Done
- Bloqueio de competência funcional em OS (front + back)
- Dual sign-off funcional com bloqueio de emissão
- Suspensão retroativa em lote com notificação de clientes funcional em staging
- Schedulers de recalibração e manutenção preventiva rodando em cron
- Re-despacho automático testado com técnico sendo marcado indisponível
- OS de garantia com custo zero funcional
- Testes: unit (Pest) + E2E (Playwright) + testes de regressão ISO 17025 — verdes no CI

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E24-S01 | Competências técnicas vigentes por técnico (CRUD + upload + bloqueio em OS) | alta |
| E24-S02 | Dual sign-off no certificado (fluxo executor → verificador → emissão) | alta |
| E24-S03 | Suspensão retroativa de certificados por falha de padrão (lote + notificação) | alta |
| E24-S04 | Agendamento automático de recalibração de padrão (scheduler 90d + bloqueio) | média |
| E24-S05 | Despacho round-robin + re-despacho automático com notificação ao cliente | alta |
| E24-S06 | OS de garantia (tipo + custo zero + relatório de qualidade) | média |
| E24-S07 | Agendamento automático de manutenção preventiva de UMC e veículos | média |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Suspensão retroativa em lote pode gerar volume alto de e-mails simultâneos | médio | Fila de envio com rate limiting (máx. 100 e-mails/minuto); log de entrega |
| Dual sign-off pode criar gargalo operacional em laboratórios com 1 técnico | alto | Configuração por tenant: habilitar/desabilitar dual sign-off (padrão: habilitado para tenants acreditados); <!-- TBD: confirmar com PM se dual sign-off é opcional ou mandatório para todos os tenants --> |
| Bloqueio de competência bloqueia tenant que não cadastrou competências ainda | alto | Período de graça: 90 dias pós-implantação sem bloqueio (modo aviso); depois bloqueio hard |

## Estimativa
- Stories: 7
- Complexidade relativa: muito alta (ISO 17025 compliance + despacho + suspensão retroativa)
- Duração estimada: 3-4 semanas

## Referências
- PRD-ampliacao-2026-04-16-v3.md §1.1 (Pacote A — ISO 17025, REQ-MET-011..014) e §1.2 (Pacote B — Operação, REQ-OPL-005..007, REQ-UMC-005)
- ISO 17025:2017 §6.2, §6.4, §7.8
- docs/product/journeys.md Jornadas 1 (ampliada), 15 (suspensão retroativa), 16 (re-despacho), 17 (OS garantia)
