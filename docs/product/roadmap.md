# Roadmap de slices - Kalibrium MVP

**Versao:** 2 (ampliada 2026-04-16)
**Data:** 2026-04-12 (original) + 2026-04-16 (ampliação offline-first)
**Construido por:** /next-slice wizard + ampliação manual
**Base:** PRD congelado → **EM REVISÃO** (ver `docs/incidents/discovery-gap-offline-2026-04-16.md`), escopo MVP ampliado, jornadas 1-11, 8 personas, `epics/ROADMAP.md` ampliado (20 épicos), ADR-0001 (backend) e ADR-0015 (stack offline-first mobile)
**Backup v1 original:** `roadmap-backup-2026-04-16.md`

## 🔄 STATUS 2026-04-16

Este roadmap foi **ampliado** após discovery de offline-first sistêmico. Os slices 001-014 já executados (merged) permanecem válidos no backend. O frontend de todos esses slices será refeito em stack nova (ver ADR-0015).

**Decisão do PM em 2026-04-16:** pausar a execução de slices de E03 (015, 016+ estão em planejamento) até que E15 (PWA shell offline-first) + E16 (sync engine) estejam prontos. Esses dois novos épicos precisam ser decompostos em stories e implementados **antes** de retomar os slices de negócio (E04+).

## Convencoes

- Ordem reflete dependencias hard, nao preferencia subjetiva.
- Codigo `DOMAIN-NNN` e semantico; `specs/NNN/` e posicional.
- `specs/001` a `specs/014` ja foram usados (E01 completo + E02 completo + E03 stories 001a-003a).
- `specs/900` e smoke test de harness, nao e slice de produto.
- ADRs bloqueantes devem ser decididos antes do slice iniciar.
- Slices com UI tambem dependem do pacote documental do epico correspondente: wireframes, ERD, API contracts, user flows e migrations spec.
- **Novo 2026-04-16:** slices de frontend (com UI) a partir de 015+ usam stack nova (PWA + Ionic + Capacitor) conforme ADR-0015. Stories anteriores tinham frontend Livewire/Blade que será descartado.

## Lista ordenada (original — slices 001-014)

### 0. INF-006 - Frontend base do sistema

- **NNN sugerido:** 006
- **Dominio:** INF
- **Epico base:** E01 - Setup e Infraestrutura
- **Depende de:** E01-S01 (`specs/001`)
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** nenhum bloqueio de produto; e slice de infraestrutura sem tela de negocio
- **Tamanho:** medio
- **O que entrega:** build de frontend, Tailwind, Livewire, Alpine e uma pagina tecnica de sanidade para confirmar que a base visual funciona
- **Por que antes do produto:** sem a base de frontend, qualquer tela real do E02 comeca em terreno instavel.

### 1. SEG-001 - Login seguro do laboratorio

- **NNN sugerido:** 007
- **Dominio:** SEG
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** E01 completo (`specs/001` a `specs/005`)
- **ADRs bloqueantes:** ADR-0004 (IdP final: Fortify/Sanctum vs Keycloak vs WorkOS)
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** login por e-mail e senha, recuperacao de senha e 2FA para gerente/administrativo
- **Por que primeiro:** sem acesso seguro, nenhuma tela de negocio deve existir.

### 2. TEN-001 - Primeiro laboratorio isolado

- **NNN sugerido:** 008
- **Dominio:** TEN
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** SEG-001
- **ADRs bloqueantes:** ADR-0004 se ainda nao estiver decidido
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** cadastro inicial do laboratorio, empresa/filial raiz e isolamento verificavel dos dados
- **Por que nessa ordem:** o laboratorio precisa existir como espaco isolado antes de clientes, instrumentos e operacao.

### 3. TEN-002 - Usuarios, papeis e plano do laboratorio

- **NNN sugerido:** 009
- **Dominio:** TEN
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** TEN-001
- **ADRs bloqueantes:** nenhum adicional apos ADR-0004
- **Outros bloqueios:** documentacao por epico E02 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** usuarios com papeis gerente, tecnico, administrativo e visualizador, alem de plano/limites basicos
- **Por que nessa ordem:** os proximos cadastros precisam respeitar permissoes desde o primeiro dia.

### 3b. SEG-002 - Base legal LGPD + consentimentos (E02-S07)

- **NNN sugerido:** 010
- **Dominio:** SEG / CMP
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** TEN-002
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E02 ja consumida
- **Tamanho:** medio
- **O que entrega:** registro de base legal LGPD por categoria de dado pessoal no tenant, consentimento por canal (e-mail/WhatsApp), opt-out por contato (REQ-CMP-004, FR-SEG-03, FR-EML-04)
- **Por que antes de E03:** sem base legal registrada, qualquer cadastro de cliente/contato em E03 ja nasce em violacao LGPD.

### 3c. SEG-003 - Testes estruturais de isolamento cross-tenant (E02-S08)

- **NNN sugerido:** 011
- **Dominio:** SEG / TEN
- **Epico base:** E02 - Multi-tenancy, Auth e Planos
- **Depende de:** SEG-002
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** nenhum
- **Tamanho:** pequeno
- **O que entrega:** suite de testes de seguranca estrutural que prova que dados do tenant A nunca aparecem para tenant B em qualquer query, endpoint ou job (garantia P1 do E02)
- **Por que antes de E03:** rede de protecao antes de E03 cadastrar dados reais de clientes.

### 4. TEN-003 - Clientes e contatos

- **NNN sugerido:** 012
- **Dominio:** TEN
- **Epico base:** E03 - Cadastro Core
- **Depende de:** SEG-003 (E02 fechado)
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI
- **Tamanho:** medio
- **O que entrega:** cadastro de cliente por CNPJ/CPF, contatos, e consentimentos por e-mail/WhatsApp
- **Por que nessa ordem:** a primeira jornada real comeca pelo cliente que pede a calibracao.

### 5. MET-001 - Instrumentos do cliente

- **NNN sugerido:** 013
- **Dominio:** MET
- **Epico base:** E03 - Cadastro Core
- **Depende de:** TEN-003
- **ADRs bloqueantes:** nenhum
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI; PD-003/ASS-002 se a terminologia instrumento/equipamento afetar a tela
- **Tamanho:** pequeno
- **O que entrega:** cadastro de instrumentos por modelo, numero de serie, faixa, resolucao e dominio metrologico
- **Por que nessa ordem:** sem instrumento cadastrado nao existe ordem de servico de calibracao.

### 6. MET-002 - Padroes e procedimentos

- **NNN sugerido:** 014
- **Dominio:** MET
- **Epico base:** E03 - Cadastro Core
- **Depende de:** MET-001
- **ADRs bloqueantes:** ADR-0005 se houver upload de certificado do padrao neste slice
- **Outros bloqueios:** documentacao por epico E03 antes de implementar UI; PD-003/ASS-002 se a terminologia ainda estiver aberta
- **Tamanho:** medio
- **O que entrega:** cadastro de padroes de referencia, validade/rastreabilidade e procedimentos de calibracao versionados
- **Por que nessa ordem:** a calibracao so pode usar padroes vigentes e procedimentos definidos.

---

## 🔄 INSERIDO EM 2026-04-16 — NOVOS SLICES FOUNDATIONAL ANTES DE E04+

Antes de prosseguir com FLX-001 (slice 015, criação de OS), **E15 e E16 precisam ser decompostos em stories e implementados**. Isso reverte a intenção original de seguir para E04 logo após E03, mas é inevitável: sem PWA shell + sync engine, a OS não vai funcionar offline, e o negócio real do cliente exige offline.

### 7a. INF-007 — Spike técnico: auditoria de reaproveitamento E01/E02/E03

- **NNN sugerido:** 015 (mudança: este era FLX-001 antes; agora é spike técnico)
- **Dominio:** INF
- **Epico base:** E15 - PWA Shell Offline-First (pré-trabalho)
- **Depende de:** ADR-0015 aprovada
- **ADRs bloqueantes:** ADR-0015
- **Tamanho:** pequeno (1-2 dias de spike)
- **O que entrega:** relatório técnico do que de E01/E02/E03 é aproveitável (backend, rotas, modelos, migrations, auth) e o que precisa ser refeito (frontend Livewire/Blade descartado, sessão → JWT long-lived, device binding, biometria).
- **Por que primeiro:** sem este relatório, E15 pode duplicar trabalho já feito ou perder aproveitamento.

### 7b. INF-008 — E15-S01: scaffold do projeto PWA (React + Ionic + Capacitor)

- **NNN sugerido:** 016
- **Dominio:** INF / PWA
- **Epico base:** E15
- **Depende de:** INF-007 (spike)
- **Tamanho:** medio
- **O que entrega:** projeto frontend novo (React + TS + Ionic + Capacitor) buildando para web/iOS/Android; CI configurado; deploy de ambiente de teste.

### 7c. SEG-004 — E15-S02: auth JWT long-lived + refresh + device binding + biometria

- **NNN sugerido:** 017
- **Dominio:** SEG
- **Epico base:** E15 (complementa E02)
- **Depende de:** INF-008
- **ADRs bloqueantes:** ADR-0015
- **Tamanho:** medio-grande
- **O que entrega:** login com JWT (7-14 dias), refresh transparente, device binding (primeiro login aprovado pelo admin), biometria via Capacitor Biometric, WebAuthn fallback.

### 7d. SEG-005 — E15-S03: criptografia local + wipe remoto

- **NNN sugerido:** 018
- **Dominio:** SEG
- **Epico base:** E15
- **Depende de:** SEG-004
- **Tamanho:** medio
- **O que entrega:** SQLite + SQLCipher criptografado localmente; fluxo de wipe remoto (admin aciona pelo backend, próxima abertura do app limpa dados).

### 7e. SYN-001 — E16-S01: PoC técnico sync engine (PowerSync vs ElectricSQL vs custom)

- **NNN sugerido:** 019
- **Dominio:** SYN
- **Epico base:** E16
- **Depende de:** SEG-005 + INF-008
- **Tamanho:** grande (spike técnico com escolha)
- **O que entrega:** PoC das 3 opções com dataset realista (500 clientes + 8 OS + 30 dias histórico); ADR-0016 criada com decisão; integração do escolhido no projeto.

### 7f+ — E16 restante (stories S02 a S12)

- **NNN sugerido:** 020-030
- **Dominio:** SYN
- **Epico base:** E16
- **Tamanho:** variado
- **O que entrega:** merge por campo, detecção de conflito, tela de resolução, fila local observável, modo avião forçado, audit log, tempo real online, sync silenciosa.

### 8+. Slices originais (FLX-001, FLX-002, MET-003, MET-004, CMP-001, FIS-001) — renumerados

Os slices originais **não foram removidos**, apenas empurrados para depois de E15+E16. Ordem preservada, numeração posicional (`specs/NNN`) vai depender de onde E15/E16 param.

- **FLX-001 — Nova ordem de serviço** (era slice 015): agora vai depender de E16 merged. Ganhará capacidade offline: criação de OS offline, atribuição de equipe (até 5 pessoas), modo bancada/campo-veículo/campo-UMC.
- **FLX-002 — Agenda, fila e status da OS** (era slice 016): ampliado com estados de campo (deslocamento iniciado, chegou cliente, saiu cliente, sincronizando).
- **MET-003 — Execução de calibração na bancada** (era slice 017): mantém escopo original (bancada). Versão campo fica em slice separado da E05 ampliado.
- **MET-003b (novo) — Execução de calibração em campo** — similar a MET-003 mas offline-capable, com foto do selo, assinatura do cliente, padrões da UMC ou veículo operacional.
- **MET-004 — Incerteza, histórico técnico e lacres/selos** — mantém escopo, cálculo já é offline-capable (roda no dispositivo).
- **CMP-001 — Aprovação, certificado e entrega ao cliente** — ampliado: certificado pode ser gerado offline em campo e transmitido na sync.
- **FIS-001 — NFS-e, contas a receber e painel mínimo** — ampliado: NFS-e pode ser "preparada offline" quando gestor em campo sem sinal.

## Proximo slice recomendado agora

**Antes da ampliação de 2026-04-16**: TEN-003 (E03-S01) ou continuação de E03 no slice 013+. Esses já foram executados (slices 012, 013, 014 mergidos).

**Após ampliação de 2026-04-16:** o próximo slice é **INF-007 — spike técnico de auditoria de E01/E02/E03**. Isso deve rodar ainda nesta sessão ou na próxima, antes de decompor E15 em stories formais.

**Sequência recomendada imediata:**
1. PM aprova o roadmap ampliado (este arquivo + `epics/ROADMAP.md`).
2. `/decompose-stories E15` — decompor E15 em stories.
3. Auditoria de planejamento do E15.
4. Spike técnico (slice INF-007) em paralelo à aprovação das stories de E15.
5. Execução de E15 story por story com auditoria dual (verifier + reviewer + master-audit).
6. `/decompose-stories E16` após E15 merged.
7. E16 execução.
8. Reabertura de E04 com frontend novo.

## Regras de sequenciamento

Regra de sequenciamento (ADR-0011 / R13 + R14): nenhuma story nova pode iniciar se stories anteriores do mesmo épico não estão `merged`; primeiro slice de um épico MVP só inicia se o épico anterior tem todas as stories `merged` em `project-state.json[epics_status]`.

**Nota importante 2026-04-16:** a ordem inter-épico do MVP foi **reordenada**. E15 e E16 foram inseridos entre E03 e E04 (não substituindo — inserindo). E04-E14 permanecem em ordem, mas agora dependem de E16 ter sido merged. O `scripts/sequencing-check.sh` pode precisar ser ajustado para refletir a nova ordem (isso é tarefa de harness, fora deste roadmap).
