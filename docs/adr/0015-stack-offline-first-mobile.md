# ADR-0015 — Stack offline-first para operação de campo e mobile

**Status:** proposed
**Data:** 2026-04-16
**Autor:** roldao-tecnico (PM) + Co-Authored-By Claude Opus 4.7

**Complementa:** ADR-0001 (stack backend Laravel + PostgreSQL permanece) — este ADR adiciona a dimensão offline-first e mobile que estava ausente do ADR-0001.

---

## Contexto

O incidente `docs/incidents/discovery-gap-offline-2026-04-16.md` revelou que a descoberta original do Kalibrium perdeu um fundamento crítico: a operação é **90% offline em campo**, envolvendo técnicos, vendedores, motoristas de UMC e gestores que trabalham em locais com conectividade intermitente (até 4 dias sem sinal em zonas rurais, minas, usinas, obras).

O ADR-0001 escolheu Laravel + PostgreSQL + SPA para um cenário que se acreditava ser "laboratório de bancada online". Essa decisão está correta para o **backend** (Laravel + PostgreSQL continuam sendo a stack de servidor). Mas o **frontend** precisa ser completamente repensado para suportar:

1. Operação 100% offline-capable em smartphone, tablet, notebook e desktop.
2. Janelas de até 4 dias offline com sincronização silenciosa quando conectar.
3. Merge por campo com detecção de conflito (colaboração multi-pessoa na mesma OS).
4. Biometria obrigatória, wipe remoto e criptografia local (LGPD + perda de dispositivo).
5. APIs nativas em iOS/Android (câmera, GPS, push, SQLite nativo, biometria) que PWA pura não acessa de forma estável — especialmente no iOS.
6. Emissão de NFS-e com estado "preparada offline, aguardando transmissão".
7. Geração local de PDF de certificado (funciona sem sinal).

A decisão a tomar: **qual stack de aplicação cliente (mobile + web) atende esses requisitos**, mantendo o backend Laravel + PostgreSQL definido em ADR-0001?

## Restrições e forças

- **Não fragmentar código por plataforma.** Com 4 dispositivos (smartphone Android, smartphone iOS, tablet, notebook/desktop) e 8 personas distintas, ter uma base de código para cada plataforma é inviável para um MVP.
- **Backend é intocável.** ADR-0001 já foi implementado nos épicos E01-E03 (merged). Laravel + PostgreSQL continuam. O que muda é a camada cliente e a integração de sync.
- **Experiência consistente entre plataformas.** Vendedora que passa do notebook no escritório para o smartphone no cliente precisa ver o "mesmo produto", não "dois produtos".
- **Loja de apps (App Store + Play Store) é opcional, não obrigatória.** PM preferiu inicialmente não depender do processo de review da Apple/Google para cada atualização. Mas aceita publicar se for a única forma viável para iOS.
- **LGPD + perda de dispositivo.** Dados sensíveis de cliente e calibração ficam no dispositivo por dias. Criptografia local forte é requisito inegociável.
- **PM não-técnico.** A decisão técnica precisa ser apresentada em termos de "o que funciona, o que não funciona" — não em termos de framework vs framework.

## Opções consideradas

### Opção A — PWA puro (sem wrapper nativo)

Progressive Web App instalável via navegador (Chrome, Safari, Edge). Service Worker para cache + sync. IndexedDB (via Dexie) para dados locais. Web Crypto API para criptografia. WebAuthn para biometria.

- **Prós:**
  - Um código só (JavaScript/TypeScript) para desktop, notebook, tablet, Android e iOS.
  - Zero fricção de distribuição (instala via navegador, atualiza automático).
  - Sem passar por review de loja de app.
  - Infraestrutura web conhecida (HTML/CSS/JS).
- **Contras:**
  - **iOS é limitador crítico.** Safari tem quotas agressivas de IndexedDB (a partir de ~50 MB começa a pedir permissão; Apple historicamente limpa dados de PWA não-usado há 7 dias). Push notifications em PWA iOS só funcionam desde iOS 16.4 (2023) e com limitações. Background sync em iOS é não-confiável.
  - Biometria via WebAuthn funciona mas é menos fluida que biometria nativa.
  - Acesso à câmera funciona mas qualidade de foto é inferior à câmera nativa.
  - Sem acesso a SQLite local — limitado a IndexedDB (mais lento, menos robusto para milhares de registros).
- **Custo de reverter:** baixo (é o ponto de partida; qualquer opção pode migrar para cá voltando).

### Opção B — App nativo por plataforma (Swift iOS + Kotlin Android + web separada)

Três bases de código: app nativo iOS em Swift/SwiftUI, app nativo Android em Kotlin/Compose, e web separada em React/Vue para desktop/notebook/tablet.

- **Prós:**
  - Performance máxima.
  - Todas as APIs nativas disponíveis (câmera, biometria, SQLite, background sync profundo).
  - UX 100% nativa por plataforma.
- **Contras:**
  - **Três bases de código.** Custo de desenvolvimento e manutenção triplicado para o MVP.
  - Divergência de feature entre plataformas (feature nova precisa ser implementada 3x).
  - Review da Apple e Google para cada update (2-7 dias de ciclo).
  - Exige desenvolvedores especializados em 3 stacks.
  - MVP sai muito depois.
- **Custo de reverter:** alto (três bases exigem refactor grande para consolidar).

### Opção C — PWA + Capacitor (wrapper híbrido para iOS/Android) [ESCOLHIDA]

PWA como base (mesmo código para desktop, notebook, tablet). Para iOS e Android, o mesmo código é **empacotado** via Capacitor como app nativo, ganhando acesso completo às APIs nativas (câmera, GPS, biometria, SQLite local com SQLCipher, push notifications, background sync). Publicado na App Store e Play Store.

- **Prós:**
  - **Um código só** (TypeScript + framework web) que serve desktop, notebook, tablet, Android e iOS.
  - **APIs nativas acessíveis** via Capacitor plugins (biometria, câmera HD, SQLite com SQLCipher criptografado, push profundo, background sync robusto).
  - **Resolve limitação do iOS** — no Safari, PWA iOS seria limitada; no wrapper Capacitor, o app tem storage generoso e todas as APIs nativas.
  - Atualizações frequentes sem passar por loja (via "live updates" do Capacitor / Ionic — código JS/CSS atualiza sem submeter novo binário).
  - Loja só para release grande (novo plugin nativo, permissão nova).
  - Ecosistema maduro: Ionic, StencilJS, Capacitor têm comunidade grande.
- **Contras:**
  - Uma camada de abstração a mais (Capacitor traduz chamadas JS para nativas).
  - Primeira publicação na App Store e Play Store exige processo único de setup (developer accounts, certificados, provisioning).
  - Algumas APIs muito específicas (ex: NFC, leitura de XML offline de NFS-e local) podem exigir plugins customizados.
  - Se o time é web-only e nunca fez app mobile, curva de aprendizado inicial é real.
- **Custo de reverter:** médio (voltar pra PWA pura é fácil; ir para nativo por plataforma é refactor grande).

## Decisão

**Opção escolhida: C — PWA + Capacitor (wrapper híbrido para iOS/Android).**

### Stack detalhada

- **Framework UI:** React + TypeScript (pela maturidade do ecosistema offline-first — TanStack Query/Router, React Native equivalentes para Web).
  - Alternativa considerada: Vue + Nuxt + Ionic. Descartada por React ter ecosistema offline mais rico e time tender a saber React mais que Vue.
- **UI Components:** Ionic Framework (componentes web mobile-first, adaptação automática para iOS look & feel vs Material Design vs web).
- **Wrapper híbrido:** Capacitor (da mesma empresa do Ionic, integração nativa).
- **Banco local:** SQLite via `@capacitor-community/sqlite` com SQLCipher habilitado (criptografia AES-256). Para desktop web (onde não há Capacitor), fallback para IndexedDB via Dexie (menos robusto mas cobre o caso).
- **Sync engine:** **PowerSync** (primária, sync engine maduro com PostgreSQL como backend) ou **ElectricSQL** (alternativa) ou **custom sync com CRDT lite** (fallback se as opções prontas não atenderem aos requisitos de merge por campo + detecção de conflito).
  - Decisão de sync engine fica para uma ADR separada (ADR-0016 — Sync Engine) após PoC técnico no primeiro épico de offline-first.
- **Autenticação:** JWT long-lived (7-14 dias) com refresh transparente + device binding + biometria via Capacitor Biometric plugin + WebAuthn como fallback.
- **Criptografia local:** SQLCipher para banco + Web Crypto API para arquivos de evidência (fotos, PDFs).
- **Push notifications:** Firebase Cloud Messaging (FCM) via Capacitor plugin (Android + iOS).
- **Camera / GPS:** Capacitor Camera + Geolocation plugins.
- **PDF offline:** `pdfmake` ou `@react-pdf/renderer` (geração client-side funciona offline).
- **Backend:** **Laravel + PostgreSQL mantidos conforme ADR-0001.** Mudança necessária: API-only (sem Blade), endpoints REST/GraphQL com autenticação Sanctum + JWT, suporte a batch sync do PowerSync/ElectricSQL.

### Razão

- **Um código só** atende ao requisito de não fragmentar entre plataformas.
- **Capacitor resolve o gap do iOS** — que seria o bloqueador principal da Opção A (PWA pura).
- **Mantém ADR-0001** intacto no backend. O que muda é a camada cliente.
- **Custo de MVP aceitável** — time web experiente consegue ser produtivo em Capacitor rapidamente, diferente de Opção B que exige 3 skillsets.
- **Reversibilidade média** — se o Kalibrium crescer e precisar de UX mais nativa no futuro, pode migrar partes críticas para nativo (ex: só o app do técnico de campo vira Swift) mantendo o resto. Opção A isolada não oferece essa rota clara.

**Reversibilidade:** média (voltar para PWA pura é trivial; avançar para nativo é refactor significativo mas não bloqueado por esta escolha).

## Consequências

### Positivas

- MVP offline-first viável em prazo razoável com time web.
- Experiência consistente entre desktop, notebook, tablet e smartphone.
- APIs nativas disponíveis onde importam (biometria, câmera HD, SQLite criptografado, background sync, push).
- Atualizações rápidas via live updates (Capacitor/Ionic Appflow) — não precisa submeter loja para cada bugfix.
- Ecosistema de sync engines prontos (PowerSync, ElectricSQL) reduz complexidade custom.
- Portal do cliente final (Persona 3 — Rafael) continua sendo PWA web simples, sem Capacitor (baixa fricção de acesso).

### Negativas

- Uma camada de abstração (Capacitor) entre JS e APIs nativas. Bug em plugin pode exigir workaround ou contribuição upstream.
- Setup inicial de App Store + Play Store (contas de developer, certificados, provisioning profiles) é chato e exige trabalho de DevOps mobile.
- Curva de aprendizado inicial para o time se não for experiente em Capacitor.
- Dependência de uma empresa (Ionic Inc.) para o wrapper — embora o core seja open-source e a comunidade seja grande, há risco de priorização comercial em detrimento da comunidade no futuro.
- Live updates funcionam para JS/CSS; mudanças em plugins nativos ou permissões ainda exigem re-submissão à loja.

### Riscos

- **Sync engine escolhido não escalar para os volumes reais.** Mitigação: PoC técnico no primeiro épico offline-first (E15 ou similar) com dataset representativo (500 clientes, 8 OS com fotos, 30 dias de histórico) antes de congelar a escolha.
- **iOS push notifications no Capacitor falharem em produção.** Mitigação: testar no staging antes de entregar ao primeiro cliente pagante. Fallback: notificação por e-mail + WhatsApp (que já estão no escopo do MVP).
- **App Store rejeitar o app na primeira submissão.** Mitigação: seguir checklist da Apple (guidelines 4.2 — app não pode ser "only a wrapper around website"). Capacitor com SQLite local e funcionalidades nativas atende, mas vale revisar guidelines antes de submeter.
- **Capacitor plugin para NFS-e customizada não existir.** Mitigação: emissão de NFS-e continua server-side (backend Laravel fala com prefeitura). Cliente mobile só prepara o payload e transmite quando sincronizar.
- **Conflito de sync em alto volume gerar UX ruim.** Mitigação: merge por campo minimiza conflitos reais; tela de resolução de conflito é bem-desenhada e limitada a casos raros.
- **Criptografia local via SQLCipher ter bug em plataforma nova do iOS ou Android.** Mitigação: usar versão estável (não beta), seguir changelog, testar em major releases antes de atualizar base.

### Impacto em outros artefatos

- **Hooks afetados:** nenhum hook Claude Code afetado diretamente.
- **Sub-agents afetados:**
  - `architecture-expert`: passa a conhecer esta stack no modo `design` e `plan`.
  - `builder`: passa a implementar em Ionic/Capacitor/TypeScript além de Laravel/PHP.
  - `qa-expert`: passa a validar testes de sincronização, conflito e modo offline.
  - `security-expert`: passa a validar biometria, wipe remoto, criptografia local.
  - `devops-expert`: passa a incluir App Store + Play Store na esteira (novos pipelines).
  - `observability-expert`: passa a monitorar métricas de sync (latência, falhas, conflitos, dispositivos com fila acumulada).
  - `integration-expert`: passa a considerar sync engine como integração.
- **ADRs relacionados:**
  - ADR-0001 (stack backend): complementado, não revogado. Backend Laravel + PostgreSQL continuam.
  - ADR-0016 (a criar): Sync Engine (escolha definitiva entre PowerSync / ElectricSQL / custom).
  - ADR-0017 (a criar): Política de push notifications offline-first.
  - ADR-0018 (a criar): Distribuição do app (App Store / Play Store / live updates).
- **Constitution:** sem impacto em P1-P9 ou R1-R16.
- **CLAUDE.md:** sem impacto estrutural; apenas menção eventual desta ADR em capítulos de stack.
- **Roadmap / épicos:** novos épicos introduzidos em `epics/ROADMAP.md` (ampliação de 2026-04-16): E15 (PWA shell offline-first), E16 (sync engine), E17 (UMC e frota), E18 (caixa de despesa por OS), E19 (estoque multinível), E20 (CRM offline do vendedor). Nenhum épico original foi removido.
- **E01/E02/E03 (já merged):** backend aproveitado. Frontend antigo será descartado e refeito no novo stack; dados do banco permanecem.

## Referências

- Incidente que motivou: `docs/incidents/discovery-gap-offline-2026-04-16.md`
- Memória permanente: `project_offline_first_systemic.md` e `feedback_prd_only_grows.md` (agent memory)
- mvp-scope.md §2-ter (perfil de conectividade e offline-first)
- personas.md (mapa de offline-first por persona no final)
- journeys.md (jornadas 6, 7, 8, 9, 10 — todas offline-capable)
- Ionic Framework: https://ionicframework.com/docs (verificado 2026-04-16)
- Capacitor: https://capacitorjs.com/docs (verificado 2026-04-16)
- PowerSync: https://docs.powersync.com/ (verificado 2026-04-16)
- ElectricSQL: https://electric-sql.com/docs (verificado 2026-04-16)
- Dexie (IndexedDB wrapper): https://dexie.org/ (verificado 2026-04-16)
- SQLCipher: https://www.zetetic.net/sqlcipher/ (verificado 2026-04-16)

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas (3 consideradas: PWA pura, Nativo puro, PWA+Capacitor híbrido)
- [x] Decisão justificada sem "porque sim" (C resolve o gap do iOS que inviabilizaria A, sem o custo de B)
- [x] Reversibilidade declarada (média — pode voltar pra A ou avançar pra B com custos conhecidos)
- [x] Consequências negativas listadas (camada de abstração, App Store setup, dependência Ionic Inc., live updates limitados)
- [x] Não contradiz ADR anterior (complementa ADR-0001, não revoga)
- [x] Impacto em hooks/agents/constitution endereçado (nenhum hook afetado; 7 sub-agents ganham novo conhecimento de stack)
