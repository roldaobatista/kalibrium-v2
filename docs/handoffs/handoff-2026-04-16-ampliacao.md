# Handoff — 2026-04-16 — Ampliação offline-first

## Estado consolidado da sessão

Sessão disparada por feedback crítico do PM: "**nosso trabalho é 90% offline**. O sistema tem que ser operacional offline e online automaticamente. Vendedor tem CRM offline, etc." Isso revelou **gap de descoberta S1** no intake original — o PRD capturou apenas "laboratório de bancada online" mas a operação real é híbrida bancada+campo com conectividade intermitente (até 4 dias sem sinal em mina, usina, obra).

PRD, personas, jornadas, escopo MVP, modelo de domínio, glossário e roadmap foram **ampliados** (nunca substituídos — princípio aditivo reafirmado pelo PM). Nenhum conteúdo original foi deletado. Backup de todo arquivo tocado em `*-backup-2026-04-16.md`.

## Decisão de produto

- Kalibrium sempre foi produto completo (bancada + campo + UMC + vendedor). O intake enxergou só um pedaço. Nome mantido.
- **Offline-first é requisito sistêmico**, não feature de um épico.
- **Ambiente duplo:** bancada + campo operam no mesmo app, mesmo certificado, mesmo fluxo de qualidade.
- Três modos de OS: bancada, campo-veículo-operacional, campo-UMC.
- UMC (Unidade Móvel de Calibração): 1 ou várias; motorista/operador de guindaste é papel próprio.
- Veículo operacional: assinado (1 técnico) ou compartilhado (pool).
- Caixa de despesa: foto obrigatória + OS obrigatória + 3 origens (cartão corp, adiantamento, reembolso).
- Estoque multinível: lab + UMC + veículo operacional + carro pessoal do técnico.
- Segurança mobile: biometria + criptografia local + wipe remoto + device binding.
- PM aprovou tudo no flow conversacional; esqueleto corrigido e documentado.

## Entregas

### Camada 1 — Incidente registrado

- `docs/incidents/discovery-gap-offline-2026-04-16.md` — incidente S1 com causa raiz, impacto, decisão do PM e aprendizado pro harness (R16: `/intake` deve perguntar perfil de conectividade como obrigatório).

### Camada 2 — Docs de produto ampliados (todos com backup)

| Documento | Estado anterior | Estado atual |
|---|---|---|
| `docs/product/personas.md` | 3 personas | **8 personas** (Marcelo ampliado + Juliana + Rafael intactos + Carlos técnico campo + Lúcio motorista UMC + Patrícia vendedora + Diego gestor campo + Cláudia atendente) |
| `docs/product/journeys.md` | 5 jornadas | **11 jornadas** (5 originais + visita campo veículo + visita campo UMC + caixa despesa por OS + vendedor offline + colaboração multi-pessoa + admin frota) |
| `docs/product/mvp-scope.md` | 29 REQs | **62 REQs** (29 originais + 33 novos: FLD, UMC, VHL, DSP, INV, CRM, SEC, SYN); seção "perfil de conectividade e offline-first" nova; 3 modos de operação |
| `docs/product/domain-model.md` | ~34 entidades | **+15 entidades novas** (UMC, Veículo operacional, Equipe da OS, Deslocamento, Assinatura do cliente, Estoque por localização, Movimentação de estoque, Diário de bordo, Despesa, Cartão corporativo, Adiantamento, Reembolso, Fatura cartão, Dispositivo registrado, Registro de sync, Conflito de sync, Fila local, Visita comercial). 30+ eventos novos. 6 estados novos. |
| `docs/product/glossary-domain.md` | ~50 termos | **+30 termos novos** (UMC, veículo operacional, estoque multinível, despesa por OS, adiantamento, reembolso, cartão corporativo, triagem, offline-first, sync engine, merge por campo, conflito de sync, device binding, wipe remoto, criptografia local, biometria, PWA, Capacitor, janela offline, modos bancada/campo, equipe da OS, etc) |
| `docs/product/PRD.md` | frozen | header atualizado com link para ampliação; corpo intacto |

### Camada 3 — Documento de ampliação dedicado

- `docs/product/PRD-ampliacao-2026-04-16.md` — **16 capítulos novos** que complementam o PRD principal. Será incorporado inline ao PRD.md em próxima `/freeze-prd`. Até lá, os dois são lidos juntos.

### Camada 4 — ADR nova

- `docs/adr/0015-stack-offline-first-mobile.md` — **ADR complementar** à ADR-0001 (que permanece). Escolha de stack cliente: PWA + Capacitor + SQLite criptografado (SQLCipher) + sync engine a definir em ADR-0016. Backend Laravel + PostgreSQL mantidos.

### Camada 5 — Roadmap ampliado

- `epics/ROADMAP.md` — **14 → 20 épicos**. E15-E20 inseridos (PWA shell, sync engine, UMC+frota, despesa por OS, estoque multinível, CRM vendedor). Ordem reorganizada: E15 e E16 entram **antes** de E04 (foundational). E01-E14 preservados.
- `docs/product/roadmap.md` — slices ordenados ampliados com novos spikes (INF-007 auditoria de aproveitamento E01/E02/E03) e stories de E15/E16 inseridos.

### Camada 6 — Memória permanente

- `project_offline_first_systemic.md` (memória de projeto).
- `feedback_prd_only_grows.md` (memória de feedback — PRD é aditivo, nunca diminui).
- `feedback_intake_must_ask_connectivity.md` (memória de feedback — `/intake` precisa perguntar conectividade como obrigatório).

## Estado do projeto

- **Fase:** discovery-ampliation.
- **E01, E02:** merged. Backend aproveitável, frontend antigo (Blade/Livewire) será descartado.
- **E03:** `paused-for-ampliation`. Stories 012-014 merged; 015+ drafts pausadas até E15/E16 prontos.
- **Gates:** todos approved no último slice. Nenhum gate novo pendente.
- **Ampliação 2026-04-16:** `completed` (arquivos escritos). Aprovação formal do PM pendente.

## Próximo passo imediato (PM)

1. **PM aprova a ampliação** (sim/não após ler este handoff ou abrir os arquivos principais).
2. Se aprovada: `/decompose-stories E15` — decompor E15 em stories com Story Contract.
3. Auditoria de planejamento de E15 pelo `planning-auditor`.
4. Spike técnico INF-007 em paralelo: auditoria de reaproveitamento de E01/E02/E03 (o que do backend serve, o que do frontend será refeito).
5. Execução de E15 story por story com auditoria dual (verifier + reviewer + master-audit dual-LLM).
6. `/decompose-stories E16` após E15 merged.
7. E16 execução.
8. Reabertura de E04+ com frontend novo (offline-capable).

## Decisões pendentes

- **ADR-0015** (stack offline-first) está `proposed`. Precisa revisão independente do `architecture-expert` antes de `accepted`.
- **ADR-0016** (sync engine PowerSync vs ElectricSQL vs custom) será criada após PoC técnico no início de E16.
- **Setup Apple + Google developer accounts** — PM vai precisar providenciar quando for publicar nos stores (não é urgente, só no final de E15).

## Débitos técnicos adicionados

- **AMPLIATION-001:** PRD.md precisa incorporar inline o conteúdo de `PRD-ampliacao-2026-04-16.md` em próxima `/freeze-prd`.
- **AMPLIATION-002:** `sequencing-check.sh` (R13/R14) precisa ajustar ordem E03→E15→E16→E04+.
- **AMPLIATION-003:** Frontend Livewire/Blade de E01/E02/E03 será descartado; auditoria pendente em spike INF-007.

## Memória restaurável

Sessão nova em Claude Code ou Codex CLI que rodar `/resume` encontra neste handoff e em `project-state.json` tudo necessário para entender: (a) por que E03 está pausado, (b) por que há 20 épicos agora, (c) por que ADR-0015 complementa ADR-0001, (d) o princípio aditivo do PRD, (e) como a stack cliente mudou, (f) o que cada persona/jornada significa. Memórias permanentes de agente (`project_offline_first_systemic.md`, `feedback_prd_only_grows.md`, `feedback_intake_must_ask_connectivity.md`) reforçam o princípio.

## Observação final

Esta sessão é exemplo canônico de **gap de descoberta crítico detectado tarde** (após E01/E02/E03 merged). O tratamento: incidente → ampliação aditiva (nunca substituição) → preservação de todo trabalho original → stack nova apenas onde realmente precisa → aproveitamento máximo do backend já feito. A disciplina do princípio "PRD só amplia" evitou desperdício de reescrita total e deu ao PM visibilidade de continuidade.
