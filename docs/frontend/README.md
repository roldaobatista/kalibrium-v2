# Kalibrium Frontend — Guia rápido

Cliente mobile-first React 18 + TypeScript 5 + Ionic 8 + Capacitor 6 + Vite 5 (slice-016).

## Pré-requisitos

- Node.js LTS 20.x (veja `engines` em `package.json`).
- npm 10.x.
- Android SDK (para `npx cap add android` / `cap sync android`). Instale via Android Studio ou `sdkmanager` CLI.
- Xcode + macOS **apenas para iOS** (`npx cap add ios`, `cap sync ios`). Em Windows/Linux o AC-003 é skipado.

## Comandos

| Comando | O que faz |
|---|---|
| `npm install` | Instala dependencias (React, Ionic, Capacitor, Vite, ESLint). |
| `npm run dev` | Sobe Vite em `http://localhost:5173` com HMR. |
| `npm run build` | `tsc -b` + `vite build` → `dist/`. |
| `npm run lint` | ESLint flat config com zero warnings. |
| `npm run test:scaffold` | Roda `node --test tests/scaffold/*.test.cjs`. |
| `npm run test:e2e` | Playwright (`tests/e2e/*.spec.ts`) — sobe Vite automaticamente via `webServer`. |
| `npm run test` | Roda scaffold + e2e em sequencia. |
| `npx cap add android` | Gera `android/` (Gradle project). Uma vez por repo. |
| `npx cap sync android` | Copia `dist/` e plugins nativos para o projeto Android. |
| `npx cap add ios` | `macOS only`. Gera `ios/App/App.xcworkspace`. |
| `npx cap sync ios` | `macOS only`. Mesmo para iOS. |

## Estrutura de `src/`

```
src/
├── App.tsx                  # Root Ionic + router (IonReactRouter)
├── main.tsx                 # Bootstrap React 18 (createRoot)
├── theme-variables.css      # Tokens Ionic (nao e diretorio)
├── vite-env.d.ts            # Types do Vite
├── pages/                   # IonPage components (LoginPage, HomePage, AdminDevicesPage)
├── components/              # UI compartilhada (vazio no scaffold)
├── hooks/                   # composables React (vazio no scaffold)
├── db/                      # Integracao SQLite (E15-S06)
├── auth/                    # Device binding + biometria (E15-S07)
├── wipe/                    # Wipe remoto runtime (E15-S09)
└── observability/           # Logs, metricas, crash reports
```

AC-005 exige exatamente esses 7 subdiretorios; AC-011 proibe qualquer outro dir (nao criar `src/legacy/`, `src/old/`, `src/todo/` etc.).

## Rotas iniciais

- `/login` — stub de tela de login (integracao em E15-S07)
- `/home` — stub de home com Ionic Grid responsivo (cobre AC-006)
- `/admin/devices` — stub admin (integracao em E15-S09)
- `/` → redirect para `/login`

## Capacitor

`capacitor.config.ts` e o baseline de producao. Nao contem `server.url` hardcoded (AC-014). Para desenvolvimento com live-reload no emulador, crie `capacitor.config.dev.ts` (ignorado pelo git) apontando para `http://<ip-da-maquina>:5173`.

## Qualidade

- ESLint flat config (`eslint.config.js`) com `@typescript-eslint` + react hooks + `eslint-config-prettier`.
- Prettier (`.prettierrc.json`).
- TypeScript strict (`tsconfig.json`).
- Playwright E2E cobrindo AC-001 e AC-006.

## Troubleshooting

- **`npm install` com ERESOLVE:** fixe versoes exatas conforme `docs/frontend/stack-versions.md`. Ordem de fallback: (1) aceitar warning, (2) `overrides` em `package.json`, (3) `--legacy-peer-deps` com rationale. `--force` e proibido.
- **`npx cap add android` sem SDK:** exporte `ANDROID_SDK_ROOT=/c/Users/<voce>/AppData/Local/Android/Sdk` e re-rode.
- **Porta 5173 ocupada:** o Vite anuncia e sobe em porta alternativa (AC-009).

## Referencias

- ADR-0015 — Stack offline-first
- ADR-0016 — Multi-tenancy
- `docs/frontend/stack-versions.md` — versoes pinadas
- `specs/016/spec.md` — 14 ACs deste scaffold
