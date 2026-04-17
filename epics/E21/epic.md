# E21 — Compliance Fiscal, LGPD, Backup e Push

## Objetivo

Fechar as lacunas de operação real que impedem um laboratório pagante de ir a produção: retransmissão de NFS-e rejeitada pela prefeitura, retenções fiscais corretas por regime tributário, jornada LGPD completa do titular, backup por tenant com verificação de integridade e push notification nativo para todos os papéis móveis.

## Valor entregue

Laboratório em produção consegue operar sem intervenção manual em casos de rejeição fiscal (a prefeitura não aceita a NFS-e na primeira tentativa em ~15% dos casos reais). Titular pode exercer direitos LGPD sem o gestor precisar manusear o banco de dados. Backup diário auditável protege o tenant contra perda catastrófica. Push notification entrega alertas críticos ao técnico, motorista e vendedor mesmo quando o app está fechado.

## Escopo

### Retransmissão de NFS-e rejeitada (REQ-FIS-007)
- Novo estado no ciclo da NFS-e: `rejeitada_prefeitura` → `aguardando_correcao` → `retransmitida`
- Tela de diagnóstico: código de erro da prefeitura, campo(s) inválidos em destaque, sugestão de correção
- Fluxo de correção: administrativo edita o campo inválido na NFS-e e retransmite sem precisar criar nova
- Histórico de tentativas de transmissão (timestamp, código de resposta, payload enviado)
- Alerta automático ao administrativo quando NFS-e fica em estado `rejeitada_prefeitura` por mais de 24h
- Entidades: `HistoricoTransmissaoNfse`, estado `rejeitada_prefeitura` em `NotaFiscalServico`

### Retenções fiscais por regime (REQ-FIS-008)
- Cálculo correto de ISS (municipal), IR (federal), INSS (previdenciário) conforme regime tributário do prestador e do tomador
- Half-even rounding (arredondamento bancário) em todos os cálculos fiscais
- Configuração por tenant: alíquotas, regimes, isenções municipais por código IBGE
- Validação automática das retenções antes de emitir NFS-e
- Cobertura mínima: ISS sobre serviços, IR fonte sobre PJ, INSS sobre PF — os 3 mais comuns no setor de calibração

### Push notification nativo (REQ-FLX-007)
- Integração com Firebase Cloud Messaging (FCM) via Capacitor plugin
- Notificações para: técnico (OS atribuída, prazo próximo, despesa rejeitada), motorista UMC (agenda alterada), vendedor (follow-up de orçamento), gestor em campo (aprovação de despesa de alta alçada)
- Backend: `POST /api/v1/notifications/send` + fila de envio (job Laravel)
- Controle de preferências: usuário pode configurar quais tipos de notificação recebe
- Entidades: `PushSubscription`, `NotificacaoEnviada`

### Jornada LGPD do titular (REQ-CMP-006)
- Jornada 12: titular (cliente final ou ex-colaborador) solicita via formulário público (sem login): acesso, retificação ou exclusão de dados pessoais
- Triagem pelo DPO (papel: `gestor` ou `administrativo`): aceitar, indeferir, solicitar documentação adicional
- Prazo legal de atendimento (15 dias úteis conforme LGPD)
- Log imutável de cada etapa: solicitação, triagem, resposta, entrega ao titular
- Exclusão: marca titular como `excluido_lgpd` em todas as entidades relevantes; dado anonimizado, não deletado fisicamente (preserva integridade referencial + auditoria fiscal)
- Entidades: `RequisicaoLgpd`, `EtapaAtendimentoLgpd`

### Backup por tenant (REQ-CMP-007)
- Job agendado diário: dump completo do schema do tenant + upload para S3 com criptografia server-side (SSE-S3)
- Verificação de integridade: hash SHA-256 do dump armazenado junto; job de verificação semanal confirma que backup é restaurável
- Retenção configurável (padrão: 30 dias)
- Tela de status do backup no painel admin do tenant: último backup, tamanho, status (ok/falha), hash
- Entidades: `BackupJob`, `StatusBackupTenant`

## Fora de escopo
- Substituição tributária e ICMS — Kalibrium é prestador de serviço (sem NF-e de mercadoria)
- Retenções municipais exóticas fora dos 3 cobertos — configuração de alíquota manual cobre casos raros
- Backup multi-region — S3 com versionamento já oferece durabilidade suficiente para MVP
- Portal self-service do titular para acompanhar pedido LGPD — pós-MVP (tela de status via token já cobre)
- Automação de exclusão em cascata em sistemas legados externos — fora do escopo

## Acceptance Criteria do épico

- **AC-E21-01:** Administrativo visualiza NFS-e rejeitada com código de erro da prefeitura em destaque, corrige o campo inválido e retransmite sem criar nova nota; histórico de tentativas visível.
- **AC-E21-02:** Cálculo de ISS + IR + INSS respeita regime tributário do tenant; arredondamento half-even aplicado; valor calculado bate com memória de cálculo exibida.
- **AC-E21-03:** Técnico recebe push notification quando OS é atribuída a ele, mesmo com app fechado.
- **AC-E21-04:** Titular submete solicitação LGPD via formulário público; DPO recebe triagem; atendimento registrado no log imutável com timestamps.
- **AC-E21-05:** Job de backup executa diariamente; painel admin mostra status do último backup, tamanho e hash; job de verificação semanal confirma restaurabilidade.
- **AC-E21-06:** Exclusão LGPD anonimiza dados do titular em todas as entidades; registros fiscais preservam integridade referencial.

## Dependências

### Diretas (bloqueiam início)
- E07 merged (NFS-e base — retransmissão é ampliação do ciclo existente)
- E12 merged (infraestrutura de comunicação — push amplia E12)
- ADR-0016 aceita (backup por tenant; isolamento nos logs de notificação)

### Transitivas
- E15 merged (Capacitor plugin de push requer shell mobile do E15)

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant (backup por tenant, log LGPD tenant-scoped)
- ADR a criar — Política de retenção de dados LGPD e anonimização

## Definition of Done
- Fluxo de retransmissão de NFS-e rejeitada funcional em staging com mock de prefeitura
- Cálculo de retenções validado contra tabelas oficiais da RFB e prefeitura-piloto
- Push notification entregue em dispositivo iOS e Android real via FCM
- Jornada LGPD completa: solicitação → triagem → atendimento → log — funcional
- Backup diário executando e verificação semanal verde no CI
- Testes: unit (Pest) + E2E (Playwright) + testes de regressão fiscal — verdes no CI
- `docs/compliance/lgpd-atendimento.md` com procedimento DPO documentado

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E21-S01 | Retransmissão de NFS-e rejeitada (estado + tela diagnóstico + fluxo correção) | alta |
| E21-S02 | Retenções fiscais por regime (ISS + IR + INSS + half-even rounding) | alta |
| E21-S03 | Push notification nativo (FCM + Capacitor + preferências do usuário) | alta |
| E21-S04 | Jornada LGPD do titular (formulário + triagem DPO + log imutável + anonimização) | alta |
| E21-S05 | Backup por tenant (job diário + S3 + hash + painel de status) | média |
| E21-S06 | Verificação de integridade do backup (job semanal + restauração automatizada) | média |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Prefeitura muda layout de XML sem aviso → retransmissão quebra | alto | Parser de resposta defensivo; modo de fallback com retransmissão manual |
| FCM tem latência variável no Brasil em redes 3G | médio | Timeout de entrega de 24h no FCM; fallback para in-app badge ao abrir |
| Alíquotas municipais de ISS variam por município (5.570 municípios) | médio | Tabela configurável por IBGE; pré-cadastro dos 50 municípios mais frequentes do setor |
| Anonimização incompleta deixa dado pessoal residual em tabelas de log | alto | Checklist de entidades com dados pessoais (DPO audita antes de S04 merged) |

## Estimativa
- Stories: 6
- Complexidade relativa: alta (fiscal + LGPD + infra)
- Duração estimada: 3 semanas

## Referências
- PRD-ampliacao-2026-04-16-v2.md §1 (Pacote A — Fiscal, LGPD, Infra, REQ-FIS-007/008, REQ-FLX-007, REQ-CMP-006/007)
- docs/product/journeys.md Jornada 1 ampliada (NFS-e rejeitada), Jornada 12 (LGPD titular)
- LGPD (Lei 13.709/2018), Art. 15-22 (direitos do titular)
