# Índice do Projeto Kalibrium V2

> Mapa de navegação rápida para qualquer agente (Claude, Kimi, Gemini, Codex, etc.)
> Última atualização: 2026-05-07

## 📋 Visão Geral

- **O que é:** SaaS para laboratórios de calibração de instrumentos de medição
- **Stack:** Laravel 12 + React + Ionic + Capacitor + PostgreSQL + Redis
- **Docs centrais do produto:**
  - [`docs/product/PRD.md`](./product/PRD.md) — Requisitos (80 REQs, 25 épicos)
  - [`docs/product/roadmap.md`](./product/roadmap.md) — Roadmap com sequência de entregas
  - [`docs/product/mvp-scope.md`](./product/mvp-scope.md) — Escopo do MVP por domínio

## 📁 Documentação por área

| Área | Onde está |
|------|-----------|
| Arquitetura & decisões | [`docs/architecture/`](./architecture/) |
| ADRs (decisões arquiteturais) | [`docs/adr/`](./adr/) |
| Design (wireframes, fluxos) | [`docs/design/`](./design/) |
| Frontend (componentes, telas) | [`docs/frontend/`](./frontend/) |
| Backlog (histórias, épicos, ideias) | [`docs/backlog/`](./backlog/) |
| Operações (deploy, infra, logs) | [`docs/operations/`](./operations/) |
| Compliance & qualidade | [`docs/compliance/`](./compliance/) |
| Financeiro | [`docs/finance/`](./finance/) |

## 🎯 Backlog — O que importa agora

- **Em andamento:** [`docs/backlog/AGORA.md`](./backlog/AGORA.md)
- **Roadmap (épicos):** [`docs/backlog/ROADMAP.md`](./backlog/ROADMAP.md)
- **Histórias aguardando:** `docs/backlog/historias/aguardando/`
- **Histórias ativas:** `docs/backlog/historias/ativas/`
- **Histórias feitas:** `docs/backlog/historias/feitas/`
- **Ideias brutas:** `docs/backlog/ideias/`
- **Aceites (prints):** `docs/backlog/aceites/`

## 🏗️ Código-fonte — Estrutura principal

| Caminho | O que tem |
|---------|-----------|
| `app/Domain/` | Entidades, repositórios, regras de negócio |
| `app/Http/Controllers/` | Controllers API + Web |
| `app/Livewire/` | Componentes Livewire (painel web) |
| `app/Infrastructure/` | Implementações de infra (cache, filas, etc.) |
| `src/` | Código frontend mobile (React + Ionic) |
| `mobile/src/pages/` | Telas principais do app |
| `mobile/src/components/` | Componentes React reutilizáveis |
| `mobile/src/db/` | IndexedDB (local + sync) |
| `mobile/src/hooks/` | React hooks customizados |
| `resources/views/` | Views Blade (painel web) |
| `routes/` | Rotas (web.php, api.php, dev.php, console.php) |
| `tests/Feature/` | Testes de integração (Pest) |
| `tests/e2e/` | Testes end-to-end (Playwright) |

## 🧪 Testes & Qualidade

- **Testes:** `composer pest` ou `vendor/bin/pest`
- **Análise estática:** `vendor/bin/phpstan analyse`
- **Formatação PHP:** `vendor/bin/pint`
- **Lint frontend:** `cd mobile && npm run lint`
- **Aceite e2e:** scripts em `scripts/aceite-*.mjs`

## 🔧 Ambiente de desenvolvimento

| Serviço | URL / Porta |
|---------|-------------|
| Laravel | http://127.0.0.1:8000 |
| Vite (mobile) | http://127.0.0.1:5173 (ou 5174) |
| Redis | 127.0.0.1:6379 |
| PostgreSQL | Ver `config/database.php` |

**Credenciais de aceite:**
- Técnico: `tecnico@teste.local` / `password`
- Gerente: `gerente@teste.local` / `password`

## 🚀 Próximo épico recomendado

Baseado no roadmap: **FLX-001 — Nova ordem de serviço**
- Criação offline de OS com modos: bancada, campo-veículo, campo-UMC
- Equipe de até 5 pessoas
- Detalhes técnicos em [`docs/product/PRD.md`](./product/PRD.md) (buscar "FLX-001")
