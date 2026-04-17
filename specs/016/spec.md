---
title: "Scaffold React + TypeScript + Ionic + Capacitor + Vite"
lane: L3
story: E15-S02
epic: E15
---

# Slice 016 — E15-S02: Scaffold React + TypeScript + Ionic + Capacitor + Vite

**Status:** draft
**Data de criação:** 2026-04-17
**Autor:** orchestrator (expandido de `epics/E15/stories/E15-S02.md`)
**Depende de:** slice-015 (E15-S01 Spike INF-007 — versões validadas em `docs/frontend/stack-versions.md`)

---

## Contexto

Kalibrium está migrando do frontend legado Livewire/Blade para uma PWA offline-first mobile-first com Capacitor para gerar binários iOS e Android. O spike E15-S01 (slice 015, merged via PR #36) validou as versões exatas da stack: React 18 + TypeScript 5 + Ionic 8 + Capacitor 6 + Vite 5.

Este slice cria o esqueleto compilável que abre em navegador, emulador Android e simulador iOS, com roteamento mínimo (`/login`, `/home`, `/admin/devices`), layout adaptativo, configuração do Capacitor, pipeline de build web+mobile, ESLint/Prettier e remoção formal do frontend Livewire/Blade legado. Nenhuma regra de negócio é implementada — este é o ponto de partida para todas as demais stories de E15.

Stakeholder beneficiado: engenharia (desbloqueia S03-S10 em paralelo) e indiretamente todos os usuários do produto (o novo frontend é pré-requisito de toda a operação offline-first).

## Jornada alvo

Um desenvolvedor clona o repositório, executa `npm install && npm run dev` e vê a rota `/login` renderizando no Chrome sem erros no console. Ao executar `npm run build` obtém `dist/` com artefatos web prontos. Com `npx cap add ios && npx cap sync ios` (em macOS) gera um workspace Xcode válido em `ios/`. Com `npx cap add android && npx cap sync android` gera um projeto Gradle válido em `android/`. O layout adaptativo renderiza sem overflow horizontal em viewport 375px (mobile) e 1280px (desktop). `npm run lint` sai com exit 0. O diretório `resources/views/` (ou arquivos Blade legados) não existe mais no repositório.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). ACs são escritos antes do código.

### Happy path

- **AC-001 — App abre no navegador com rota `/login`**
  Dado que o desenvolvedor executa `npm install && npm run dev`, quando acessa `http://localhost:5173/login` no Chrome, então a página renderiza (componente Ionic visível, zero erros no console) e o processo Vite permanece em exit 0.

- **AC-002 — Build web produz artefatos sem erros**
  Dado o repositório com dependências instaladas, quando o desenvolvedor executa `npm run build`, então o diretório `dist/` é gerado contendo `index.html`, e `dist/assets/` contém **pelo menos um arquivo `.js` e um arquivo `.css`**; o processo termina com exit 0.

- **AC-003 — Projeto iOS gerado e sincronizável**
  Dado um host macOS com Xcode instalado, quando o desenvolvedor executa `npx cap add ios && npx cap sync ios`, então o diretório `ios/App/App.xcworkspace` existe e os comandos saem com exit 0.

- **AC-004 — Projeto Android gerado e sincronizável**
  Dado o repositório, quando o desenvolvedor executa `npx cap add android && npx cap sync android`, então o diretório `android/` existe contendo `build.gradle` na raiz e `app/build.gradle`, e os comandos saem com exit 0.

- **AC-005 — Estrutura de diretórios conforme declarado**
  Dado o scaffold criado, quando se executa `ls src/`, então os diretórios `pages/`, `components/`, `hooks/`, `db/`, `auth/`, `wipe/`, `observability/` existem (com `.gitkeep` quando vazios).

- **AC-006 — Layout adaptativo renderiza sem overflow em mobile e desktop**
  Dado o dev server rodando na rota `/home`, quando Playwright navega em viewport 375x667 (mobile) e 1280x800 (desktop), então `document.documentElement.scrollWidth <= clientWidth` em ambas as viewports (zero scroll horizontal) — verificável em `tests/e2e/layout.spec.ts`.

- **AC-007 — ESLint e Prettier passam sem erros**
  Dado o scaffold criado, quando se executa `npm run lint`, então o comando termina com exit 0 e zero erros reportados (warnings permitidos).

- **AC-008 — Frontend legado removido (exceto templates de e-mail transacional)**
  Dado o scaffold criado, quando se executa `find resources/views -name "*.blade.php" -not -path "*/emails/*"` e `find resources/js -type f`, então o resultado combinado é zero arquivos (ou os diretórios não existem); o diretório `resources/views/emails/` é preservado por conter templates de notificação server-side do Laravel (Mail facade), que não fazem parte do frontend SPA e continuam sob responsabilidade do backend; `routes/web.php` não referencia nenhuma view Blade que não seja de e-mail (apenas health-check ou redirect para API).

### Edge cases e erros (obrigatórios)

- **AC-009 — `npm run dev` comunica porta ocupada (edge de AC-001)**
  Dado que a porta 5173 está ocupada por outro processo, quando o desenvolvedor executa `npm run dev`, então Vite imprime no stdout a mensagem de porta ocupada e, caso use porta alternativa, anuncia o novo número — nunca sobe silenciosamente.

- **AC-010 — Build falha com erro se houver erro de tipo (edge de AC-002)**
  Dado um erro de tipagem proposital em `src/main.tsx` (ex.: `const x: number = "foo"`), quando se executa `npm run build`, então o processo falha com exit não-zero e a mensagem do TypeScript é exibida (verificado em teste dedicado que cria arquivo com erro, roda build, aguarda falha, limpa).

- **AC-011 — Scaffold não cria diretórios extras fora do escopo declarado (edge de AC-005)**
  Dado o scaffold criado, quando se lista `src/`, então existem apenas os diretórios declarados em AC-005 + os arquivos raiz padrão (`main.tsx`, `App.tsx`, `vite-env.d.ts` e similares); nenhum diretório extra tipo `src/legacy/`, `src/old/`, `src/todo/`.

- **AC-012 — ESLint detecta violação em arquivo seed e falha (edge de AC-007)**
  Dado um arquivo `src/__lint_check__.tsx` com violação proposital (ex.: variável não usada com regra `no-unused-vars` ativa), quando se executa `npm run lint`, então o comando sai com exit não-zero e reporta a violação; após remover o arquivo, `npm run lint` sai com exit 0.

- **AC-013 — `routes/web.php` limpo pós-descarte (edge de AC-008)**
  Dado o scaffold aplicado, quando se executa `grep -E "view\\(|return view" routes/web.php`, então o resultado é zero ocorrências de chamadas a views (comando sai com exit 1 por não encontrar nada); templates em `resources/views/emails/` são acessados via `Mail::send(...)` e não via `view()` em rotas web — essa exceção é consistente com AC-008.

### Segurança

- **AC-014 — `capacitor.config.ts` não expõe servidor remoto de dev em produção**
  Dado o arquivo `capacitor.config.ts`, quando inspecionado, então o campo `server` não contém `url` hardcoded apontando para host externo e, se houver bloco `server.url`, ele está condicionado a `process.env.NODE_ENV !== 'production'` ou comentado. Verificável via teste que parseia o arquivo e afirma ausência de URL remota em ambiente prod.

## Convenção de AC-ID

Este slice usa o formato **AC-NNN** (AC-001..AC-014) por compatibilidade com o validador mecânico `scripts/draft-spec.sh` (regex `AC-[0-9]+` linha 96). O formato canônico `AC-NNN-XXX` do protocolo §10.1 é incompatível com este validador — conflito registrado em `project-state.json → technical_debt → HARNESS-MIGRATION-002`. A rastreabilidade AC-ID → teste é garantida pelo `audit-tests-draft` (ADR-0017 Mudança 1) via `name_contains_ac_id` ou `@covers AC-NNN`.

## Fora de escopo

- Migração/remoção de templates de e-mail transacional (`resources/views/emails/*.blade.php`) — **preservados** neste slice; são notificações server-side do Laravel (Mail facade), não frontend SPA. Auditoria de refactor/migração desses templates, se necessária, fica para backlog pós-MVP.
- Service Worker e `manifest.webmanifest` (E15-S03)
- Build de distribuição (IPA/AAB) no CI (E15-S04 e E15-S05)
- Banco local SQLite via `@capacitor-community/sqlite` (E15-S06)
- Autenticação funcional + device binding + biometria (E15-S07)
- Qualquer tela de negócio (login form preenchido, listagem, CRUD)
- Sync engine e resolução de conflito (E16)
- Push notification setup (E15-S08)
- Wipe remoto runtime (E15-S09)

## Dependências externas

- **ADR-0015** — stack frontend oficial (React + TypeScript + Ionic + Capacitor + Vite)
- **ADR-0016** — isolamento multi-tenant (relevante apenas para stories futuras; este slice não toca schema)
- **docs/frontend/stack-versions.md** (gerado em E15-S01) — versões exatas pinnadas
- **docs/frontend/README.md** — será criado/atualizado neste slice
- Node.js LTS (>= 20) instalado no host
- Xcode + macOS para validar AC-003 (Windows/Linux pulam esse AC em ambiente local; CI macOS cobre)
- Android SDK instalado (via Android Studio ou `sdkmanager` CLI) para AC-004

## Riscos conhecidos

- **Conflito de peer dependencies entre Ionic 8, React 18 e Vite 5** → mitigação: usar versões exatas do spike E15-S01; se o `npm install` emitir `ERESOLVE`, documentar em `docs/frontend/stack-versions.md` e ajustar para versão compatível já validada.
- **`npx cap add ios` falha em Windows/Linux (esperado — requer macOS)** → mitigação: teste AC-003 marcado como skip quando `process.platform !== 'darwin'`; CI macOS executa; README do frontend documenta pré-requisito.
- **Remoção de `resources/views/` pode quebrar rotas do backend Laravel** → mitigação: antes de deletar, inspecionar `routes/web.php` e mover qualquer rota web que sirva Blade para `routes/api.php` ou remover; backend vira API-only; smoke test: `php artisan route:list` não lista rotas `web` que retornem view.
- **Divergência de lockfile ao executar `npm install` em máquinas diferentes** → mitigação: commit do `package-lock.json` exato + usar `npm ci` em CI.
- **Playwright em CI precisa baixar browsers** → mitigação: step dedicado `npx playwright install --with-deps chromium` no workflow antes dos testes e2e; cache do `~/.cache/ms-playwright`.

## Notas do PM (humano)

Este slice é **pré-requisito absoluto** para S03-S10 do E15. Qualquer atraso aqui bloqueia todo o E15. O PM aceita ACs marcados como "skip em plataforma X" desde que cobertos no CI macOS (AC-003).

Referência do Story Contract: [`epics/E15/stories/E15-S02.md`](../../epics/E15/stories/E15-S02.md).
