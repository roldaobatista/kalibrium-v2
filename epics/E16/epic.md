# E16 — Sync Engine + Merge por Campo + Conflito

## Objetivo

Implementar o motor de sincronização offline-first que torna o Kalibrium operacional em campo: sync silencioso em background, merge por campo (last-write-wins), detecção de conflito real com resolução manual e audit log completo de cada operação de sync.

## Valor entregue

Técnicos, motoristas e vendedores acumulam trabalho por até 4 dias sem conexão e sincronizam silenciosamente ao pegar sinal — sem perder dado, sem duplicata, sem surpresa. Quando dois usuários editam o mesmo campo offline, o sistema apresenta os dois valores com timestamps e autores para o responsável resolver, sem perda de nenhuma das versões. Resultado: confiança operacional total no campo.

## Escopo

### Sync engine (REQ-SYN-001, REQ-SYN-002)
- Fila de operações locais com timestamp local e número de sequência por dispositivo
- Sync silencioso em background acionado ao detectar conectividade (NetworkInformation API + polling de fallback)
- Protocolo de sync: delta incremental (somente registros modificados desde o último checkpoint de sync)
- Merge por campo (last-write-wins por campo individual, não por registro inteiro) — cada campo carrega `edited_by`, `edited_at`, `device_id`
- Reconciliação de chaves primárias geradas offline (UUID v7 no cliente, aceitos diretamente no servidor)
- Entidades: `SyncQueue`, `SyncCheckpoint`, `FieldChange`

### Detecção e resolução de conflito (REQ-SYN-003)
- Conflito real = mesmo campo editado por dois usuários diferentes, ambos offline, sem nenhum ter visto a versão do outro
- Sistema sinaliza "conflito detectado" na OS/entidade afetada
- Tela de resolução: exibe campo conflitante, valor A (usuário A + timestamp), valor B (usuário B + timestamp), histórico da última versão server-side; responsável da OS escolhe qual prevalece
- Resolução registrada em audit log com responsável e motivo
- Entidades: `ConflictRecord`, `ConflictResolution`

### Sync em tempo real online (REQ-SYN-004)
- Quando todos os membros de uma OS estão online simultaneamente: atualizações em tempo real via WebSocket (Laravel Echo + Reverb)
- Fallback gracioso para modo delta-sync quando WebSocket indisponível
- Indicador de status de conexão por membro da OS visível na tela da OS

### Audit log de sync (REQ-SYN-005)
- Cada operação de sync registra: dispositivo, usuário, timestamp de envio, timestamp de aplicação no servidor, tabelas afetadas, registros afetados, bytes transferidos
- Log imutável (append-only), retido por 90 dias
- Entidade: `SyncAuditLog`

### Modo avião forçado (REQ-SYN-006)
- Botão de teste na tela de configurações do técnico: simula modo offline (corta requests de rede)
- Garante que fluxo local funciona antes de viajar para área sem sinal

### Spike técnico (pré-requisito E16-S01)
- Comparativo PowerSync vs ElectricSQL vs sync custom (avaliação: custo, lock-in, capacidade de merge por campo, suporte a PostgreSQL RLS do backend)
- Decisão formal em ADR-0017 antes de implementar E16-S02+

## Fora de escopo
- Sync peer-to-peer via Bluetooth/Wi-Fi local entre dispositivos — pós-MVP
- Resolução automática de conflito por IA — pós-MVP
- Compressão binária do payload de sync — otimização pós-MVP
- Sincronização de arquivos binários (fotos de despesa) — tratado em E18

## Acceptance Criteria do épico

- **AC-E16-01:** Técnico acumula 4 dias de OS sem conexão; ao reconectar, sync completo ocorre em background em menos de 30s para até 50 registros delta, sem interação do usuário.
- **AC-E16-02:** Dois técnicos editam o mesmo campo de OS offline; ao sincronizar, sistema detecta conflito e exibe tela de resolução com ambos os valores antes de aceitar qualquer um.
- **AC-E16-03:** Merge por campo preserva edições independentes no mesmo registro (ex.: técnico A edita `observacao`, técnico B edita `resultado` — ambas sobrevivem sem conflito).
- **AC-E16-04:** Audit log registra cada sync com dispositivo, usuário, timestamp e delta enviado; log disponível para admin consultar.
- **AC-E16-05:** Modo avião forçado ativa em 1 toque; app opera normalmente offline enquanto ativo.
- **AC-E16-06:** Isolamento multi-tenant: sync nunca entrega dados de tenant B para dispositivo de tenant A (ADR-0016 operante no protocolo de sync).

## Dependências

### Diretas (bloqueiam início)
- E15 merged (PWA Shell + banco local SQLite + SQLCipher operacionais)
- ADR-0016 aceita (isolamento multi-tenant no sync obrigatório)
- Spike INF-007 do E15-S01 concluído

### Transitivas
- ADR-0017 (decisão PowerSync / ElectricSQL / custom) — gerada em E16-S01

## ADRs relacionadas
- ADR-0015 — Stack offline-first mobile (React + Ionic + Capacitor)
- ADR-0016 — Isolamento multi-tenant (row-level + RLS)
- ADR-0017 — Sync engine: tecnologia escolhida (a criar em E16-S01)

## Definition of Done
- Suite de testes: unit (Vitest) para merge/conflict logic + integração (Pest) para protocolo de sync + E2E (Playwright) para fluxo offline→sync→resolução de conflito — todos verdes no CI
- Teste de isolamento multi-tenant no sync verde (ADR-0016)
- Audit log populado em staging com pelo menos 2 ciclos de sync reais
- ADR-0017 criada e aceita (decisão de tecnologia de sync documentada)
- `docs/frontend/sync-engine.md` com protocolo, tabelas de estado e troubleshooting

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E16-S01 | Spike: Comparativo PowerSync vs ElectricSQL vs custom + ADR-0017 | média |
| E16-S02 | Fila de operações locais + UUID v7 offline + SyncQueue/SyncCheckpoint | alta |
| E16-S03 | Protocolo delta-sync: upload de delta + reconciliação server-side | alta |
| E16-S04 | Merge por campo (last-write-wins por campo + metadata `edited_by/at/device`) | alta |
| E16-S05 | Detecção de conflito real + tela de resolução manual | alta |
| E16-S06 | Sync em tempo real online via WebSocket (Laravel Echo + Reverb) | alta |
| E16-S07 | Audit log de sync (append-only, 90 dias retenção) | média |
| E16-S08 | Modo avião forçado + indicador de status de conexão | baixa |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| PowerSync/ElectricSQL não suportam merge por campo granular | alto | Spike obrigatório em S01; plano B é sync custom sobre PostgreSQL LISTEN/NOTIFY |
| Volume de delta após 4 dias offline excede payload aceitável | médio | Paginar delta em chunks de 500 registros; compressão gzip |
| Conflito em cascata (campo A depende de campo B, ambos conflitantes) | médio | Resolver por registro inteiro quando campos relacionados conflitam; registrar como "conflito estrutural" |
| RLS do PostgreSQL (ADR-0016) filtra corretamente no sync server-side | alto | Teste de penetração multi-tenant obrigatório no CI |
| Latência de WebSocket em redes 3G do interior | médio | Fallback automático para delta-sync polling a cada 30s |

## Estimativa
- Stories: 8
- Complexidade relativa: muito alta (protocolo de sync é um dos problemas mais complexos do produto)
- Duração estimada: 3-5 semanas

## Referências
- PRD-ampliacao-2026-04-16.md §3 (Offline-first sistêmico, REQ-SYN-001..006)
- ADR-0015, ADR-0016
- docs/product/journeys.md Jornada 10 (Colaboração multi-pessoa offline)
