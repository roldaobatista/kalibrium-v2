# E19 — Estoque Multinível

## Objetivo

Implementar o controle de estoque em quatro locais distintos (laboratório central, UMC, veículo operacional, carro pessoal do técnico), com movimentação rastreada entre locais, consulta offline pelo técnico e alerta de padrão vencendo — garantindo que nenhum técnico use instrumento sem rastreabilidade vigente no campo.

## Valor entregue

Técnico em campo vê no próprio dispositivo, offline, quais padrões e ferramentas estão disponíveis para ele (no veículo ou carro pessoal), sem depender do escritório. Gestor rastreia onde cada padrão está em tempo real, recebe alerta 30 dias antes do vencimento e sabe o custo real de cada OS com os itens consumidos. Elimina uso de padrão vencido por desconhecimento — risco direto de não conformidade ISO 17025.

## Escopo

### Quatro locais de estoque (REQ-INV-001)
- Local 1 — `laboratorio`: almoxarifado central, gestão pelo escritório
- Local 2 — `umc`: a bordo da UMC (caminhão), gerido pelo motorista/técnico UMC
- Local 3 — `veiculo_operacional`: a bordo da caminhonete/carro operacional, gerido pelo técnico
- Local 4 — `carro_pessoal`: estoque portátil pessoal do técnico (ferramentas básicas, padrões de referência)
- Cada item tem saldo próprio por local (não saldo global único)
- Entidades: `ItemEstoque`, `LocalEstoque`, `SaldoEstoquePorLocal`

### Movimentação entre locais (REQ-INV-002)
- Transferência entre locais registra: item, quantidade, local origem, local destino, responsável, timestamp
- Transferências offline: registradas localmente e sincronizadas via E16
- Rastreabilidade: cada item tem histórico de movimentações com responsável e timestamp
- Entidade: `MovimentacaoEstoque`

### Consulta offline do estoque local (REQ-INV-003)
- Técnico em campo consulta saldo do seu local (veículo ou carro pessoal) 100% offline
- App valida localmente ao iniciar OS de campo: instrumento/padrão necessário está no estoque acessível do técnico?
- Se padrão necessário não estiver no local do técnico → alerta com sugestão de transferência antes de partir
- Dados de estoque local sincronizados via E16 na última conexão

### Alerta de padrão vencendo (REQ-INV-004)
- Alerta 30 dias antes do vencimento da calibração/certificado de qualquer item com rastreabilidade
- Alerta por local (técnico vê alerta do seu local; gestor vê alertas de todos os locais)
- Bloqueio condicional: item com certificado vencido não pode ser associado a OS nova (validação server-side e local)
- Entidade: `AlertaValidadeItem`

### Regra de negócio dura
- Técnico em campo só pode usar padrão que está no estoque acessível ao dispositivo dele (veículo ou carro pessoal ou UMC em operação com ele). App valida localmente offline antes de permitir uso na OS.

## Fora de escopo
- Gestão de compras/requisições — pós-MVP
- Custo de item e gestão patrimonial — pós-MVP
- Código de barras/RFID para entrada/saída — pós-MVP
- Estoque de insumos de escritório — fora do escopo do Kalibrium

## Acceptance Criteria do épico

- **AC-E19-01:** Gestor transfere padrão do laboratório para UMC; saldo atualiza em ambos os locais e movimentação aparece no histórico com responsável e timestamp.
- **AC-E19-02:** Técnico inicia OS de campo offline; app verifica localmente se o padrão necessário está no seu veículo e alerta se não estiver.
- **AC-E19-03:** Item com certificado vencido gera alerta 30 dias antes e não pode ser associado a OS nova após o vencimento.
- **AC-E19-04:** Consulta offline do estoque do veículo do técnico responde sem conexão, com dados do último sync.
- **AC-E19-05:** Movimentação offline entre locais sincroniza corretamente via E16 sem duplicar saldo.
- **AC-E19-06:** Isolamento multi-tenant: estoque de tenant A não visível para tenant B.

## Dependências

### Diretas (bloqueiam início)
- E16 merged (sync de movimentações offline)
- ADR-0016 aceita (tenant_id em toda entidade de estoque)

### Transitivas
- E17 (UMC) — local `umc` usa entidades de E17; E19 pode usar stub antes de E17 merged
- E04 (OS) — vínculo de padrão com OS; E19 pode usar referência por ID

## ADRs relacionadas
- ADR-0016 — Isolamento multi-tenant
- ADR-0017 — Sync engine

## Definition of Done
- CRUD de itens de estoque com saldo por local funcional
- Movimentação entre locais com rastreabilidade completa
- Consulta offline funcional no dispositivo do técnico
- Alertas de validade disparando 30 dias antes
- Bloqueio de uso de item vencido em OS funcional (validação local + server-side)
- Testes: unit (Pest) + integração + E2E (Playwright) — verdes no CI

## Stories previstas

| ID | Título | Complexidade |
|---|---|---|
| E19-S01 | Cadastro de itens de estoque e quatro locais (CRUD + saldo por local) | média |
| E19-S02 | Movimentação entre locais (transferência com rastreabilidade + offline) | alta |
| E19-S03 | Consulta offline do estoque local no dispositivo do técnico | alta |
| E19-S04 | Alerta de padrão vencendo (30 dias) + bloqueio de uso em OS | alta |
| E19-S05 | Relatório de posição de estoque por local e por item | baixa |

## Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Conflito de saldo após movimentações offline simultâneas de locais diferentes | alto | Movimentação é append-only (event sourcing); saldo derivado do log — sem campo `saldo` mutável |
| Técnico em campo com dados de estoque desatualizados (offline por 4 dias) | médio | Exibir timestamp do último sync; alerta se estoque local tem mais de 48h sem sync |
| Volume de alertas de validade gera ruído para gestor com muitos itens | baixo | Agrupamento por local + criticidade (vencido hoje vs 30 dias) |

## Estimativa
- Stories: 5
- Complexidade relativa: alta
- Duração estimada: 2 semanas

## Referências
- PRD-ampliacao-2026-04-16.md §6 (Estoque Multinível, REQ-INV-001..004)
- docs/product/personas.md Personas 2B (Carlos técnico campo), 4 (Lúcio motorista UMC)
- docs/product/journeys.md Jornada 6 (campo veículo), Jornada 7 (campo UMC)
