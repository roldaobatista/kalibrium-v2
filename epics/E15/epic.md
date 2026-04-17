# E15 — PWA Shell Offline-First + Capacitor Wrapper

## Objetivo
Criar a fundação do aplicativo cliente (PWA + Capacitor wrapper para iOS/Android) que todos os módulos subsequentes (E16-E25, E04-E14) usarão. Substitui o frontend antigo Livewire/Blade (descartado conforme ADR-0015) por stack offline-first moderna (React + TypeScript + Ionic + Capacitor + SQLite local com SQLCipher). Este épico é **foundational** — nenhum módulo de negócio offline funciona sem ele.

## Valor entregue
Vendedor, técnico e gestor conseguem instalar o app (iOS/Android/PWA web) em seus dispositivos, autenticar (JWT long-lived + biometria + device binding), operar 100% offline com banco local criptografado, e ter o dispositivo bloqueado remotamente (wipe) em caso de perda/demissão. PRD e PM confirmam: 90% do trabalho do cliente-âncora é offline; sem E15, o produto não entrega valor real no campo.

## Escopo

### Shell PWA + Capacitor (REQ-SEC-001, REQ-FLD-001)
- Scaffold frontend: React 18+ + TypeScript + Ionic 8 + Capacitor 6 + Vite
- PWA instalável: Service Worker (Workbox), manifest.json, ícones adaptativos, splash screen
- Wrapper Capacitor configurado: projeto Xcode (iOS 15+) + projeto Gradle (Android 10+ / API 29+)
- Build pipeline estendido no CI para gerar artefatos web + `.ipa` (ad-hoc) + `.aab` (internal track)
- Estrutura de rotas com layout adaptativo (mobile-first, desktop responsivo)
- Entidades: nenhuma persistida server-side; arquivos de configuração de build

### Banco local offline (REQ-SYN-001, REQ-SEC-002)
- SQLite local via `@capacitor-community/sqlite` com SQLCipher ativado
- IndexedDB fallback para navegador desktop (quando Capacitor não está disponível)
- Schema local espelha tabelas essenciais do backend com **`tenant_id` obrigatório** em toda tabela (ADR-0016 Opção C)
- Helper de query com enforcement automático de `tenant_id` no frontend (Global Scope local equivalente)
- Migrations locais versionadas (compatíveis com sync do E16)
- Entidades: infra de persistência local; dados de domínio chegam em E16+

### Autenticação e identidade (REQ-SEC-003, REQ-SEC-004)
- JWT long-lived (30d) com refresh silencioso em background
- Device binding: cada dispositivo registra par de chaves ed25519 no backend (endpoint novo `POST /api/v1/devices`)
- Biometria via `@capacitor-community/biometric-auth` + WebAuthn fallback no desktop
- Criptografia local AES-256-GCM (chave derivada via Argon2id do PIN/biometria)
- Ampliação de E02: novo endpoint de registro/revogação de device + tabela `registered_devices` (com `tenant_id` e RLS pela ADR-0016)
- Entidades: Dispositivo Registrado (nova, tenant-scoped)

### Wipe remoto (REQ-SEC-005)
- Flag `device.wiped_at` no backend; quando o app faz refresh e recebe `403 WIPED`, apaga banco local + cache + tokens e redireciona para tela de bloqueio
- UI de admin (Persona 1 — Marcelo) para wipar dispositivo de usuário via listagem
- Endpoint `POST /api/v1/devices/{id}/wipe` (admin only, audit-logged)
- Teste automatizado: simular wipe → próxima abertura apaga tudo

### Observabilidade do cliente (REQ-CMP-005)
- Crashlytics/Sentry no shell para captura de exceções em produção
- Log estruturado local (persistido em SQLite) com rotação
- Ping periódico de health ao backend (quando online) reportando versão do app + último sync

### Testes
- Unit tests (Vitest) para helpers de persistência + auth + wipe
- E2E web (Playwright) dos fluxos: install → login → biometria → offline → wipe
- E2E mobile: XCUITest (iOS) + Espresso (Android) dos mesmos fluxos no wrapper Capacitor
- Teste obrigatório de isolamento multi-tenant (ADR-0016): dois tenants no mesmo dispositivo (simulado em modo dev) não enxergam dados um do outro
- Teste de criptografia: abrir `.sqlite` sem chave → banco ilegível

## Fora de escopo
- Sync engine completo (fluxo de fila, merge por campo, resolução de conflito) — E16
- Módulos de negócio (CRUD cliente, OS, calibração, etc.) — E17+ e E04+
- Push notifications (E12/E21)
- PoC de sync engine (PowerSync vs ElectricSQL) — spike do início de E16
- Distribuição App Store / Play Store pública — decisão em ADR-0019 (pós-E15)
- Onboarding de contas developer Apple/Google — responsabilidade do PM (pré-requisito externo)

## Critérios de entrada
- **E03 merged** (backend Laravel + endpoints REST disponíveis) — ✅ já merged
- **ADR-0015 aprovada** (stack offline-first mobile) — ✅ aceita
- **ADR-0016 aprovada** (isolamento multi-tenant row-level + RLS) — ✅ aceita (validada em 2026-04-16)
- **Spike INF-007 concluído** (auditoria técnica de reaproveitamento de E01/E02/E03) — ⚠️ pendente
- Contas developer Apple + Google provisionadas pelo PM — ⚠️ pendente (ação externa)
- `sequencing-check.sh` ajustado para ordem E03 → E15 → E16 → E04+ (débito AMPLIATION-002)

## Critérios de saída
- App PWA instalável em navegador desktop (Chrome/Edge/Safari), abre offline após primeiro load
- App Capacitor rodando em dispositivo iOS real (TestFlight ad-hoc) e Android real (internal track)
- Login funcional com JWT long-lived + refresh em background + biometria (Face ID / fingerprint)
- Banco SQLite local criado, criptografado com SQLCipher, populado com schema do MVP (tabelas com `tenant_id`)
- Device binding: dispositivo registra par ed25519 no backend; endpoint `/api/v1/devices` retorna 201
- Wipe remoto: admin aciona wipe na UI → próxima abertura do app apaga banco + redireciona
- Suite de testes: unit (Vitest) + E2E web (Playwright) + E2E iOS + E2E Android todos verdes no CI
- Teste de isolamento multi-tenant verde (ADR-0016 operante no cliente)
- Crashlytics/Sentry integrado e reportando em ambiente de staging
- Documentação: `docs/frontend/README.md` com stack, scripts, build para iOS/Android e troubleshooting

## Stories previstas (estimativa ~10)
- E15-S01 — Scaffold React + TypeScript + Ionic + Capacitor + Vite (esqueleto compilável)
- E15-S02 — PWA: Service Worker + manifest + ícones + splash + instalabilidade
- E15-S03 — Wrapper Capacitor iOS (projeto Xcode + build ad-hoc TestFlight)
- E15-S04 — Wrapper Capacitor Android (projeto Gradle + build internal track)
- E15-S05 — Banco local SQLite + SQLCipher + helper com enforcement `tenant_id` (ADR-0016)
- E15-S06 — Autenticação: JWT long-lived + refresh silencioso + tela de login
- E15-S07 — Device binding + endpoint `/api/v1/devices` (ampliação E02)
- E15-S08 — Biometria (Face ID / fingerprint) + WebAuthn fallback desktop
- E15-S09 — Wipe remoto (flag + endpoint admin + UI de admin + lógica de limpeza no cliente)
- E15-S10 — Observabilidade: Sentry + logs locais + ping de health + suite E2E completa

A decomposição final vai para `/decompose-stories E15`; sub-agente pode ajustar granularidade (ex: dividir S01 em scaffolding + layout, ou fundir S03+S04).

## Dependências

### Diretas (bloqueiam início)
- E03 merged ✅
- ADR-0015 aceita ✅
- ADR-0016 aceita ✅ (governa schema local e teste de isolamento em CI)
- Spike INF-007 (auditoria de reaproveitamento E01/E02/E03) — deve rodar antes da primeira story de schema
- Contas Apple Developer + Google Play Console — PM

### Transitivas (impactam stories finais)
- Backend Laravel deve expor endpoints de auth + devices em versão estável (`/api/v1/*`)

## Riscos e mitigações
| Risco | Impacto | Mitigação |
|---|---|---|
| Capacitor 6 incompatível com plugins escolhidos | alto | Prova de conceito em S01-S04 antes de integrar biometria/SQLCipher |
| SQLCipher + Capacitor SQLite tem bugs conhecidos em iOS 17+ | alto | Verificar issues upstream em S05; plano B: cipher manual com libsodium |
| Biometria em Android varia por fabricante | médio | WebAuthn fallback já previsto; testar em pelo menos 3 dispositivos |
| Wipe remoto pode falhar se dispositivo nunca mais abrir o app | médio | Aceitar limitação; expirar JWT em 30d garante bloqueio eventual |
| Isolamento multi-tenant no cliente (ADR-0016) deve ser reforçado no frontend também, não só no backend | alto | Helper de query local com Global Scope equivalente + teste obrigatório no CI |
| Build iOS exige macOS + certificados — CI Linux não cobre | médio | Adicionar runner macOS no GitHub Actions (plano Team ou self-hosted) |
| Reaproveitamento de E01/E02/E03 desconhecido sem spike | médio | Spike INF-007 rodar antes da primeira story |

## Complexidade estimada
- Stories: ~10
- Complexidade relativa: **muito alta** (foundational, stack inteira nova, multi-plataforma, segurança crítica)
- Duração estimada: 4-6 semanas (paralelizável por plataforma após S01-S02)

## Referências
- ADR-0015 — Stack offline-first mobile (React + Ionic + Capacitor)
- ADR-0016 — Isolamento multi-tenant (row-level `tenant_id` + RLS em 10 tabelas críticas)
- PRD-ampliacao-2026-04-16.md — REQ-SEC-001..005, REQ-FLD-001..006, REQ-SYN-001..006
- PRD-ampliacao-2026-04-16-v3.md §1.4 — formalização do isolamento
- docs/incidents/discovery-gap-offline-2026-04-16.md — origem do trabalho offline-first
- docs/audits/comparativa-externa-reaudit-2026-04-16.md — gap F-GOV-02 (isolamento)
