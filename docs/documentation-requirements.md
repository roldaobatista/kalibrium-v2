# Requisitos de Documentacao — Gate de Documentacao Pre-Implementacao

> **Status:** ativo.
> **Versao:** 1.1.0 — 2026-04-12.
> **Proposito:** definir TODA a documentacao necessaria antes de iniciar implementacao de funcionalidades com UI. Este documento e a "gate de documentacao" que bloqueia codigo ate docs completas.
> **Fundamentacao:** pesquisa sobre BMAD Method (26 agentes, 68 workflows), GitHub spec-kit (87k stars), e melhores praticas de SaaS maduro.

---

## Sumario

| Secao | Conteudo |
|---|---|
| A | Documentacao de Produto (global criado; docs por epico ainda sob demanda) |
| B | Documentacao de Design/UX (global criado; wireframes por epico ainda sob demanda) |
| C | Documentacao Tecnica de Frontend (global criado; contratos por epico ainda sob demanda) |
| D | Documentacao por Epico/Feature (sob demanda por epico) |
| E | Gate de Documentacao — regra formal |
| F | Agentes ativos para o gate |

---

## A. Documentacao de Produto

### A.1. Inventario do que JA TEMOS

| Documento | Caminho | Status |
|---|---|---|
| PRD completo (122 FRs, 55 NFRs, 11 dominios) | `docs/product/PRD.md` | frozen |
| Personas detalhadas (3 primarias + 4 nao-alvo) | `docs/product/personas.md` | ativo |
| Jornadas fim a fim (5, Jornada 1 detalhada) | `docs/product/journeys.md` | ativo |
| Glossario de dominio | `docs/product/glossary-domain.md` | ativo |
| Glossario PM | `docs/product/glossary-pm.md` | ativo |
| Escopo MVP | `docs/product/mvp-scope.md` | ativo |
| Laboratorio tipo | `docs/product/laboratorio-tipo.md` | ativo |
| NFRs | `docs/product/nfr.md` | ativo |
| Pricing | `docs/product/pricing-assumptions.md` | ativo |
| Modelo de dominio | (dentro do PRD, secao Arquitetura Funcional) | frozen |
| Threat model | `docs/security/threat-model.md` | ativo |
| LGPD/DPIA | `docs/security/lgpd-base-legal.md`, `docs/security/dpia.md` | ativo |
| Compliance (fiscal, metrologico, REPP, ICP-Brasil) | `docs/compliance/*.md` | ativo |
| ADRs (stack, MCP) | `docs/adr/0001-stack-choice.md`, `docs/adr/0002-mcp-policy.md` | ativo |
| Foundation constraints | `docs/architecture/foundation-constraints.md` | ativo |
| 14 epicos com stories (E01 decomposto) | `epics/E01-E14/` | ativo |
| Roadmap | `epics/ROADMAP.md` | ativo |

### A.2. Documentos de Produto do gate global

#### A.2.1. Mapa de Navegacao / Sitemap

| Campo | Valor |
|---|---|
| **Nome** | Mapa de Navegacao (Sitemap) |
| **Caminho** | `docs/product/sitemap.md` |
| **Descricao** | Arvore hierarquica de todas as paginas/telas do sistema, agrupadas por modulo. Inclui: URL pattern, titulo da pagina, persona primaria, nivel de acesso (RBAC role). Serve como indice mestre para wireframes e rotas. |
| **Fase** | Planning (apos freeze-architecture, antes de implementacao UI) |
| **Agente responsavel** | `ux-designer` com input do `architect` |
| **Bloqueante para** | Implementacao de qualquer epico com UI (E02-E14) |

#### A.2.2. Fluxos de Tela (UI Flows)

| Campo | Valor |
|---|---|
| **Nome** | Fluxos de Tela (UI Flows) |
| **Caminho** | `docs/product/ui-flows.md` |
| **Descricao** | Diagramas (em Mermaid ou texto estruturado) mostrando a navegacao do usuario entre telas para cada jornada. Inclui: tela de origem, acao do usuario, tela de destino, dados transmitidos, estados de erro. Complementa `journeys.md` com nivel de tela. |
| **Fase** | Planning |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer epico com UI |

#### A.2.3. Cenarios de Persona Expandidos

| Campo | Valor |
|---|---|
| **Nome** | Cenarios de Uso por Persona |
| **Caminho** | `docs/product/persona-scenarios.md` |
| **Descricao** | Para cada persona (Marcelo, Juliana, Rafael): 5-8 cenarios concretos com pre-condicao, passos, resultado esperado. Mais granular que jornadas, mais proximo de test cases de aceitacao de UX. Inclui cenarios de erro e edge cases (ex: Juliana tenta usar padrao vencido). |
| **Fase** | Planning |
| **Agente responsavel** | `ux-designer` com input de `domain-analyst` |
| **Bloqueante para** | Wireframes e testes funcionais |

#### A.2.4. Mapa de Permissoes por Tela

| Campo | Valor |
|---|---|
| **Nome** | Matriz RBAC por Tela |
| **Caminho** | `docs/product/rbac-screen-matrix.md` |
| **Descricao** | Tabela cruzando roles (admin-tenant, gerente, tecnico, atendente, cliente-final, auditor) com telas/acoes. Cada celula indica: pode ver, pode editar, pode aprovar, bloqueado. Derivado do PRD secao RBAC mas detalhado por tela. |
| **Fase** | Planning (apos sitemap) |
| **Agente responsavel** | `architect` (existente) com revisao do `ux-designer` |
| **Bloqueante para** | Implementacao de auth/middleware em E02 e todas as telas |

---

## B. Documentacao de Design/UX

Os documentos globais desta categoria foram criados em 2026-04-12. Os wireframes por epico continuam sendo produzidos sob demanda antes da implementacao do respectivo epico.

#### B.1. Design System / Style Guide

| Campo | Valor |
|---|---|
| **Nome** | Design System |
| **Caminho** | `docs/design/style-guide.md` |
| **Descricao** | Documento fundacional de identidade visual do produto. Inclui: paleta de cores (primaria, secundaria, semantica — success/warning/error/info), tipografia (font family, tamanhos, pesos, line-height), espacamento (grid system, gaps, padding), sombras, border-radius, iconografia (biblioteca escolhida, regras de uso), logotipo e variantes. Define tokens de design reutilizaveis. |
| **Fase** | Strategy (apos freeze-architecture, antes de qualquer tela) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Todos os wireframes, todos os componentes, toda implementacao UI |

#### B.2. Padroes de Componentes

| Campo | Valor |
|---|---|
| **Nome** | Component Patterns Library |
| **Caminho** | `docs/design/component-patterns.md` |
| **Descricao** | Catalogo de componentes padrao do sistema com especificacao por componente: Tabelas (sortable, filterable, paginacao, bulk actions, empty state, loading state), Formularios (layout, labels, placeholders, validacao inline, agrupamento, steps), Modais (confirmacao, formulario, alerta, tamanhos), Toasts/Notifications (posicao, duracao, tipos, stacking), Cards (informacao, acao, status), Badges/Tags (cores, tamanhos, semantica), Buttons (hierarquia: primary/secondary/ghost/danger, tamanhos, estados), Breadcrumbs, Tabs, Dropdowns, Search (global, contextual, autocomplete), Date pickers, File upload (drag-drop, progress, validacao). Cada componente com: anatomia, variantes, estados (default, hover, active, disabled, error, loading), regras de quando usar/nao usar. |
| **Fase** | Strategy (apos style-guide) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer componente UI |

#### B.3. Padroes de Interacao

| Campo | Valor |
|---|---|
| **Nome** | Interaction Patterns |
| **Caminho** | `docs/design/interaction-patterns.md` |
| **Descricao** | Regras de comportamento transversais a todo o sistema. Inclui: Loading states (skeleton screens vs spinners, quando usar cada), Empty states (ilustracao, mensagem, call-to-action por contexto), Error states (inline validation, page-level errors, toast errors, 404/403/500 customizados), Success states (toast, redirect, inline confirmation), Confirmacao destrutiva (modal com input de confirmacao para deletes), Bulk operations (selecao, feedback de progresso, resultado parcial), Infinite scroll vs paginacao (regra por contexto), Optimistic UI (onde aplicar, fallback em falha), Debounce/throttle (regras para search, autosave), Autosave vs save explicito (regra por tipo de formulario), Drag and drop (onde aplicar, feedback visual), Keyboard shortcuts (atalhos globais, atalhos por contexto). |
| **Fase** | Strategy (apos component-patterns) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer interacao UI |

#### B.4. Layout Master

| Campo | Valor |
|---|---|
| **Nome** | Layout Master (Shell da Aplicacao) |
| **Caminho** | `docs/design/layout-master.md` |
| **Descricao** | Especificacao do layout principal da aplicacao (app shell). Inclui: Sidebar (largura, collapsed/expanded, itens de menu por role, indicadores de notificacao, footer com tenant info), Header (logo, breadcrumb, search global, notifications bell, user menu, tenant switcher se aplicavel), Content area (max-width, padding, scroll behavior), Footer (se houver). Variantes: layout autenticado, layout publico (login, portal do cliente), layout de impressao. Com medidas em px/rem e comportamento responsivo. |
| **Fase** | Strategy (apos style-guide) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer pagina (define o "esqueleto" onde todo conteudo vive) |

#### B.5. Wireframes por Modulo

| Campo | Valor |
|---|---|
| **Nome** | Wireframes (low-fidelity, texto/markdown) |
| **Caminho** | `docs/design/wireframes/` (um arquivo por modulo: `wireframes-e02-auth.md`, `wireframes-e03-cadastro.md`, etc.) |
| **Descricao** | Wireframes em formato texto (ASCII art ou markdown tables simulando layout) para cada tela do sistema. Low-fidelity — nao e mockup visual, e a disposicao de elementos na tela. Cada wireframe inclui: nome da tela, URL pattern, descricao funcional, layout dos elementos (header, filters, table, form fields, actions), anotacoes de comportamento, dados mockados de exemplo. Um arquivo por epico, todas as telas do epico no mesmo arquivo. |
| **Fase** | Planning (apos layout-master e component-patterns) |
| **Agente responsavel** | `ux-designer` por epico, validado pelo PM |
| **Bloqueante para** | Implementacao do respectivo epico |

#### B.6. Mapa de Telas (Screen Inventory)

| Campo | Valor |
|---|---|
| **Nome** | Screen Inventory |
| **Caminho** | `docs/design/screen-inventory.md` |
| **Descricao** | Tabela mestre com TODAS as telas do sistema. Colunas: ID da tela, nome, URL pattern, epico de origem, persona primaria, tipo (list, detail, form, dashboard, modal, wizard), status do wireframe (pending/done), componentes usados (referencia ao component-patterns), dados consumidos (entidades/endpoints). Serve como checklist de progresso e indice para os wireframes. |
| **Fase** | Planning (apos sitemap, antes dos wireframes) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Rastreabilidade tela-a-tela de todo o projeto |

#### B.7. Responsividade e Breakpoints

| Campo | Valor |
|---|---|
| **Nome** | Responsive Strategy |
| **Caminho** | `docs/design/responsive-strategy.md` |
| **Descricao** | Define os breakpoints do sistema (mobile, tablet, desktop, wide), como cada componente se adapta, quais telas sao mobile-first vs desktop-first, e quais telas nao precisam de versao mobile (ex: dashboards complexos podem ser "desktop recomendado"). Decisao especifica: a interface de bancada de Juliana (tablet) e prioridade mobile-first. Portal do cliente (Rafael) e responsive. Back-office (Marcelo) e desktop-first. |
| **Fase** | Strategy (apos layout-master) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de CSS/layout de qualquer tela |

#### B.8. Acessibilidade (WCAG)

| Campo | Valor |
|---|---|
| **Nome** | Accessibility Policy |
| **Caminho** | `docs/design/accessibility.md` |
| **Descricao** | Define o nivel de conformidade WCAG alvo (recomendado: AA para todo o sistema, AAA para formularios criticos). Inclui: contraste minimo de cores, navegacao por teclado (tab order, focus indicators), leitores de tela (aria-labels, roles, live regions), textos alternativos para icones, tamanho minimo de area clicavel (44x44px), reducao de motion (prefers-reduced-motion). Checklist por componente. |
| **Fase** | Strategy (junto com style-guide) |
| **Agente responsavel** | `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer componente (acessibilidade nao e retrofit) |

#### B.9. Padroes de Dados em Tela

| Campo | Valor |
|---|---|
| **Nome** | Data Display Patterns |
| **Caminho** | `docs/design/data-display-patterns.md` |
| **Descricao** | Regras de como dados sao exibidos na UI. Inclui: formatacao de datas (ISO vs local, com/sem hora, relative time), formatacao de numeros (decimais, milhar, moeda BRL), formatacao de CPF/CNPJ (mascara), status badges (cores e labels por status de OS, pedido, certificado), truncamento de texto (max chars, tooltip), null/empty values (como mostrar campo sem dado), unidades de medida (posicao, formatacao), temperaturas (graus, casas decimais), incertezas de medicao (notacao cientifica vs decimal). Especifico para metrologia. |
| **Fase** | Planning (apos component-patterns) |
| **Agente responsavel** | `ux-designer` com input de `domain-analyst` |
| **Bloqueante para** | Implementacao de qualquer tela que exibe dados tecnicos |

#### B.10. Padroes de Impressao e PDF

| Campo | Valor |
|---|---|
| **Nome** | Print and PDF Patterns |
| **Caminho** | `docs/design/print-patterns.md` |
| **Descricao** | Especificacao de como o sistema gera documentos imprimiveis. Inclui: layout de certificado de calibracao (formato RBC/INMETRO), template de relatorio gerencial, template de OS para impressao, cabecalho/rodape por tenant (logo, dados do laboratorio), paginacao, numeracao, marca d'agua para rascunho. Define: CSS @print rules, engine de PDF (DomPDF/wkhtmltopdf), resolucao, fontes embeddadas. Critico para E06 (Certificados). |
| **Fase** | Planning (antes de E06) |
| **Agente responsavel** | `ux-designer` com input de `domain-analyst` (formato RBC) |
| **Bloqueante para** | E06 (Certificado de Calibracao) e qualquer modulo que gera PDF |

---

## C. Documentacao Tecnica de Frontend

#### C.1. Arquitetura de Componentes Livewire

| Campo | Valor |
|---|---|
| **Nome** | Livewire Component Architecture |
| **Caminho** | `docs/architecture/livewire-architecture.md` |
| **Descricao** | Define a estrategia de componentizacao com Livewire 4 + Blade. Inclui: hierarquia de componentes (page-level, section-level, widget-level), regra de quando usar Livewire component vs Blade partial vs Alpine.js inline, comunicacao entre componentes (events, dispatch, $wire), lazy loading, polling (onde aplicar, frequencia), file upload pattern, forma components (form objects), paginacao. Nomenclatura de classes e views. |
| **Fase** | Strategy (apos ADR de stack) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | Implementacao de qualquer componente Livewire |

#### C.2. Convencoes de Naming

| Campo | Valor |
|---|---|
| **Nome** | Naming Conventions |
| **Caminho** | `docs/architecture/naming-conventions.md` |
| **Descricao** | Documento unico com TODAS as convencoes de nomes do projeto. Inclui: CSS classes (BEM? Tailwind utility-first? Hybrid?), Livewire components (PascalCase, prefixo por modulo?), Blade views (dot notation, estrutura de pastas), Routes (kebab-case, RESTful, prefixo por modulo), Controllers, Models, Migrations, Seeders, Tests, Events, Listeners, Jobs, Notifications, Policies. Inclui exemplos concretos para cada. ADR se houver decisao controversa. |
| **Fase** | Strategy (apos ADR de stack) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | Qualquer implementacao (evita inconsistencia desde o inicio) |

#### C.3. Contratos de API

| Campo | Valor |
|---|---|
| **Nome** | API Contracts |
| **Caminho** | `docs/architecture/api-contracts/README.md` para convencoes globais; `docs/architecture/api-contracts/api-eNN-*.md` para contratos por epico |
| **Descricao** | Especificacao formal de cada endpoint da API. Inclui: metodo HTTP, URL pattern, request headers, request body (JSON Schema ou tabela Markdown), success status code, response codes (200, 201, 204, 400, 401, 403, 404, 422, 500), response body, paginacao, filtros, sorting, exemplos de request/response. Para APIs internas (Livewire actions), documentar os metodos publicos dos components com tipos de parametros e retorno. |
| **Fase** | Planning (por epico, antes de implementacao) |
| **Agente responsavel** | `api-designer` com input de `architect` |
| **Bloqueante para** | Implementacao do respectivo epico |

#### C.4. State Management Patterns

| Campo | Valor |
|---|---|
| **Nome** | State Management Strategy |
| **Caminho** | `docs/architecture/state-management.md` |
| **Descricao** | Como o estado e gerenciado na aplicacao. Inclui: Livewire component state (properties, computed, lifecycle), Alpine.js local state (x-data, $store), Server-side session state (o que vai na session, o que vai no cache, o que vai no DB), URL state (query params, fragments), Form state (dirty tracking, autosave, unsaved changes warning), Multi-tab behavior (tab-specific vs shared state), Real-time updates (polling vs WebSocket, Reverb para notificacoes). |
| **Fase** | Strategy (apos livewire-architecture) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | Implementacao de formularios complexos e dashboards |

#### C.5. Padroes de Formulario

| Campo | Valor |
|---|---|
| **Nome** | Form Patterns |
| **Caminho** | `docs/architecture/form-patterns.md` |
| **Descricao** | Especificacao de como formularios sao construidos no sistema. Inclui: validacao (server-side via Livewire rules, client-side preview via Alpine), exibicao de erros (inline sob o campo, toast para erros globais, banner para erros de permissao), success feedback (toast + redirect vs inline), multi-step forms (wizard pattern, progress indicator, save parcial), dependencias entre campos (campo B depende de campo A), campos condicionais (show/hide), defaults inteligentes (pre-fill baseado em contexto), masks (CPF, CNPJ, telefone, CEP). |
| **Fase** | Planning (apos component-patterns e state-management) |
| **Agente responsavel** | `architect` (existente) com input de `ux-designer` |
| **Bloqueante para** | Implementacao de qualquer formulario |

#### C.6. Padroes de Tabela e Listagem

| Campo | Valor |
|---|---|
| **Nome** | Table and List Patterns |
| **Caminho** | `docs/architecture/table-patterns.md` |
| **Descricao** | Especificacao tecnica de como tabelas/listagens funcionam. Inclui: paginacao (server-side, tamanho default, opcoes), sorting (server-side, multi-column, default sort), filtros (sidebar vs inline, tipos de filtro por tipo de dado, reset, save/bookmark), search (debounce, campos buscaveis, highlight), bulk actions (selecao, confirmation, progress), export (CSV, XLS, PDF — quais tabelas suportam), column visibility (toggle por usuario), responsive table (horizontal scroll vs card-on-mobile). Implementacao via Livewire component reutilizavel. |
| **Fase** | Planning (apos component-patterns) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | Implementacao de qualquer listagem |

#### C.7. Padroes de Notificacao e Comunicacao

| Campo | Valor |
|---|---|
| **Nome** | Notification Architecture |
| **Caminho** | `docs/architecture/notification-patterns.md` |
| **Descricao** | Como notificacoes funcionam end-to-end. Inclui: tipos (in-app toast, in-app bell/inbox, e-mail, future: SMS/WhatsApp), triggers (quais eventos de dominio geram notificacao), template por tipo, preferencias do usuario (opt-in/opt-out por canal), rate limiting (nao spammar), agrupamento (digest), real-time delivery (Reverb/WebSocket para in-app), fallback (se WebSocket falha, polling), mark-as-read, notification center UI. Relevante para E12 mas afeta todos os modulos. |
| **Fase** | Planning (antes de E12, mas design basico antes de E04) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | E12 (Comunicacao) e parcialmente E04+ (notificacoes de OS) |

#### C.8. Estrategia de Testes de UI

| Campo | Valor |
|---|---|
| **Nome** | UI Testing Strategy |
| **Caminho** | `docs/architecture/ui-testing-strategy.md` |
| **Descricao** | Como testes de interface sao feitos. Inclui: Livewire component tests (Pest + Livewire test helpers), Browser tests (Pest Browser por padrao; Playwright complementar quando justificado), Visual regression (se aplicavel), Accessibility tests (axe-core no CI), Test data (factories, seeders por cenario), Fixtures (HTML snapshots), Coverage target para componentes UI. Define a piramide de testes especifica para frontend: unit (Pest) > component (Livewire) > browser (Pest Browser/Playwright) > visual. |
| **Fase** | Strategy (apos livewire-architecture) |
| **Agente responsavel** | `architect` (existente) |
| **Bloqueante para** | Escrita de testes de qualquer componente UI |

---

## D. Documentacao por Epico/Feature

Para cada epico com UI (E02 a E14), antes de iniciar implementacao, os seguintes documentos fisicos devem existir **especificos para aquele epico**. O inventario de telas do epico fica como secao obrigatoria dentro do arquivo de wireframes.

### D.1. Checklist por Epico

| # | Documento | Caminho | Descricao | Agente |
|---|---|---|---|---|
| D.1 | Wireframes + Screen Inventory do Epico | `docs/design/wireframes/wireframes-eNN-*.md` | Low-fidelity wireframes em texto para cada tela, com secao inicial listando todas as telas do epico, URL, tipo, persona e status | `ux-designer` |
| D.2 | Data Model Visual (ERD) | `docs/architecture/data-models/erd-eNN-*.md` | Diagrama entidade-relacionamento em Mermaid para as entidades do epico. Inclui: tabelas, colunas com tipos, PKs, FKs, indices, constraints, relacoes (1:1, 1:N, N:N) | `data-modeler` |
| D.3 | API Contracts do Epico | `docs/architecture/api-contracts/api-eNN-*.md` | Endpoints, headers, payloads, responses, success status codes, erros e permissao — especificos para o epico | `api-designer` |
| D.4 | User Flows Detalhados | `docs/product/flows/flows-eNN-*.md` | Fluxos passo-a-passo de cada jornada do usuario dentro do epico, com decisoes e branches | `ux-designer` |
| D.5 | Migrations Spec | `docs/architecture/data-models/migrations-eNN-*.md` | Ordem das migrations, dependencias entre tabelas, seeds iniciais, dados de tenant-setup | `data-modeler` |

### D.2. Mapa de Epicos com UI

| Epico | Nome | Tem UI? | Prioridade de Docs |
|---|---|---|---|
| E01 | Setup e Infraestrutura | Nao (infra) | N/A — ja concluido |
| E02 | Multi-tenancy, Auth e Planos | Sim (login, registro, tenant setup, perfil) | Alta — primeiro com UI |
| E03 | Cadastro Core | Sim (CRUD clientes, instrumentos, padroes) | Alta — base de dados |
| E04 | Ordens de Servico e Fluxo Operacional | Sim (OS, fila, agendamento) | Alta — jornada core |
| E05 | Laboratorio e Calibracao | Sim (tela de bancada, execucao tecnica) | Critica — interface principal de Juliana |
| E06 | Certificado de Calibracao | Sim (geracao PDF, visualizacao, aprovacao) | Alta — entrega principal |
| E07 | Fiscal: NFS-e | Sim (emissao, consulta, cancelamento) | Media |
| E08 | Financeiro e Contas a Receber | Sim (faturamento, cobranca, conciliacao) | Media |
| E09 | Portal do Cliente Final | Sim (portal externo, certificados, historico) | Alta — interface de Rafael |
| E10 | GED: Gestao Eletronica de Documentos | Sim (upload, busca, versionamento) | Media |
| E11 | Dashboard Operacional e Relatorios | Sim (graficos, KPIs, filtros) | Media |
| E12 | Comunicacao: E-mail e Notificacoes | Parcial (configuracao, templates, inbox) | Media |
| E13 | Procurement e Fornecedores | Sim (CRUD fornecedores, compras) | Baixa |
| E14 | LMS e Habilitacoes Tecnicas | Sim (habilitacoes, treinamentos, validades) | Baixa |

---

## E. Gate de Documentacao — Regra Formal

### E.1. Gate Global (uma vez, antes de qualquer epico com UI)

**Regra:** nenhum epico com UI (E02-E14) pode iniciar implementacao sem que TODOS os documentos abaixo existam e estejam aprovados pelo fluxo do projeto. O status abaixo indica existencia do arquivo; aprovacao de conteudo continua sendo decisao do gate/PM.

| # | Documento | Caminho | Status atual |
|---|---|---|---|
| G.1 | Design System / Style Guide | `docs/design/style-guide.md` | criado |
| G.2 | Component Patterns Library | `docs/design/component-patterns.md` | criado |
| G.3 | Interaction Patterns | `docs/design/interaction-patterns.md` | criado |
| G.4 | Layout Master | `docs/design/layout-master.md` | criado |
| G.5 | Screen Inventory (completo) | `docs/design/screen-inventory.md` | criado |
| G.6 | Responsive Strategy | `docs/design/responsive-strategy.md` | criado |
| G.7 | Accessibility Policy | `docs/design/accessibility.md` | criado |
| G.8 | Data Display Patterns | `docs/design/data-display-patterns.md` | criado |
| G.9 | Livewire Component Architecture | `docs/architecture/livewire-architecture.md` | criado |
| G.10 | Naming Conventions | `docs/architecture/naming-conventions.md` | criado |
| G.11 | State Management Strategy | `docs/architecture/state-management.md` | criado |
| G.12 | Form Patterns | `docs/architecture/form-patterns.md` | criado |
| G.13 | Table and List Patterns | `docs/architecture/table-patterns.md` | criado |
| G.14 | UI Testing Strategy | `docs/architecture/ui-testing-strategy.md` | criado |
| G.15 | Print and PDF Patterns | `docs/design/print-patterns.md` | criado |
| G.16 | Notification Architecture | `docs/architecture/notification-patterns.md` | criado |
| G.17 | Sitemap | `docs/product/sitemap.md` | criado |
| G.18 | UI Flows | `docs/product/ui-flows.md` | criado |
| G.19 | Persona Scenarios | `docs/product/persona-scenarios.md` | criado |
| G.20 | RBAC Screen Matrix | `docs/product/rbac-screen-matrix.md` | criado |

**Validacao operacional:** antes de `/start-story` de qualquer story com UI, o orquestrador deve checar a existencia destes arquivos. `/verify-slice` revalida o gate antes de aprovar o slice, como defesa redundante. Se faltar algum documento, o gate rejeita com finding `DOC-GATE-GLOBAL-MISSING`.

### E.2. Gate por Epico (antes de cada epico com UI)

**Regra:** alem do gate global, cada epico com UI so pode iniciar implementacao quando seus documentos especificos existirem:

| # | Documento | Caminho pattern |
|---|---|---|
| E.1 | Wireframes + screen inventory do epico | `docs/design/wireframes/wireframes-eNN-*.md` |
| E.2 | ERD do epico | `docs/architecture/data-models/erd-eNN-*.md` |
| E.3 | API Contracts do epico | `docs/architecture/api-contracts/api-eNN-*.md` |
| E.4 | User Flows do epico | `docs/product/flows/flows-eNN-*.md` |
| E.5 | Migrations Spec do epico | `docs/architecture/data-models/migrations-eNN-*.md` |

**Validacao:** o skill `/start-story` deve checar existencia destes arquivos para o epico da story. Se faltar algum, bloqueia com finding `DOC-GATE-EPIC-MISSING`.

### E.3. Ordem de Producao dos Documentos

```
Fase Strategy (blocos fundacionais, uma vez):
  1. Style Guide (B.1)
  2. Layout Master (B.4) + Accessibility Policy (B.8) [paralelo]
  3. Component Patterns (B.2)
  4. Interaction Patterns (B.3) + Responsive Strategy (B.7) [paralelo]
  5. Data Display Patterns (B.9) + Print Patterns (B.10) [paralelo]
  6. Livewire Architecture (C.1) + Naming Conventions (C.2) [paralelo]
  7. State Management (C.4)
  8. Form Patterns (C.5) + Table Patterns (C.6) [paralelo]
  9. UI Testing Strategy (C.8)
  10. Notification Architecture (C.7)

Fase Planning (por epico, na ordem do roadmap):
  1. Sitemap (A.2.1) — pode comecar em paralelo com Strategy
  2. Screen Inventory (B.6)
  3. UI Flows (A.2.2) + RBAC Matrix (A.2.4) [paralelo]
  4. Persona Scenarios (A.2.3)
  5. [Por epico]: Wireframes com inventario → ERD → API Contracts → Migrations Spec → User Flows
```

---

## F. Agentes Ativos para o Gate

### F.1. `ux-designer`

| Campo | Valor |
|---|---|
| **Nome** | `ux-designer` |
| **Papel** | Gera wireframes, style guide, component patterns, interaction patterns, layout master, responsive strategy, accessibility policy, data display patterns, print patterns, screen inventory, UI flows |
| **Budget** | 50k tokens |
| **Input** | PRD, personas, journeys, sitemap, domain model |
| **Output** | Documentos em `docs/design/` e `docs/product/` |
| **Arquivo** | `.claude/agents/ux-designer.md` |
| **Skills associadas** | Sem skill dedicada ainda; acionado pelo orquestrador conforme `.claude/agents/ux-designer.md` |
| **Justificativa** | Nenhum agente existente cobre UX/design. O architect foca em arquitetura tecnica, nao em experiencia do usuario. O domain-analyst entende o dominio mas nao traduz em telas. Este agente preenche o gap mais critico do projeto. |

### F.2. `api-designer`

| Campo | Valor |
|---|---|
| **Nome** | `api-designer` |
| **Papel** | Gera contratos de API formais (endpoints, payloads, responses, erros) a partir do ERD e dos wireframes. Produz specs em formato OpenAPI-like ou Markdown tables |
| **Budget** | 30k tokens |
| **Input** | ERD do epico, wireframes, domain model, PRD (secao FRs) |
| **Output** | Documentos em `docs/architecture/api-contracts/` |
| **Arquivo** | `.claude/agents/api-designer.md` |
| **Skills associadas** | Sem skill dedicada ainda; acionado pelo orquestrador conforme `.claude/agents/api-designer.md` |
| **Justificativa** | Contratos de API devem ser definidos antes de implementacao para evitar retrabalho. O architect pode cobrir parcialmente, mas a especializacao em contratos (validacao de payloads, error codes, paginacao) justifica agente dedicado. Alternativa: skill do architect ao inves de agente separado. |

### F.3. `data-modeler`

| Campo | Valor |
|---|---|
| **Nome** | `data-modeler` |
| **Papel** | Gera ERDs (diagramas entidade-relacionamento) em Mermaid, especificacao de migrations (ordem, dependencias, seeds), e valida consistencia do modelo de dados entre epicos |
| **Budget** | 25k tokens |
| **Input** | PRD (secao FRs e Arquitetura Funcional), domain model, glossario, ERDs anteriores |
| **Output** | Documentos em `docs/architecture/data-models/` |
| **Arquivo** | `.claude/agents/data-modeler.md` |
| **Skills associadas** | Sem skill dedicada ainda; acionado pelo orquestrador conforme `.claude/agents/data-modeler.md` |
| **Justificativa** | O modelo de dados e fundacao de tudo. Inconsistencias entre epicos (ex: tabela de instrumento referenciada por E03, E04, E05 e E06 com expectativas diferentes) sao o bug mais caro de corrigir. Agente dedicado garante visao cross-epic do modelo. Alternativa: sub-skill do architect. |

### F.4. Analise: agentes existentes que podem absorver parte do trabalho

| Documento | Agente existente | Pode cobrir? | Observacao |
|---|---|---|---|
| Livewire Architecture (C.1) | `architect` | Sim | Ja faz plan.md, naturalmente estende |
| Naming Conventions (C.2) | `architect` | Sim | Decisao arquitetural |
| State Management (C.4) | `architect` | Sim | Decisao arquitetural |
| Form Patterns (C.5) | `architect` + `ux-designer` | Parcial | Architect faz o tecnico, UX faz o comportamento |
| Table Patterns (C.6) | `architect` | Sim | Pattern tecnico |
| Notification Architecture (C.7) | `architect` | Sim | Pattern tecnico |
| UI Testing Strategy (C.8) | `architect` | Sim | Decisao arquitetural |
| Persona Scenarios (A.2.3) | `domain-analyst` + `ux-designer` | Parcial | Domain analyst conhece o dominio, UX traduz em cenarios |
| RBAC Matrix (A.2.4) | `architect` + `ux-designer` | Parcial | Architect define roles, UX mapeia por tela |

### F.5. Resumo de agentes

| Agente | Tipo | Documentos sob responsabilidade | Prioridade |
|---|---|---|---|
| `ux-designer` | Ativo | B.1-B.10, A.2.1-A.2.4, D.1, D.4 | Critica — bloqueia toda UI |
| `api-designer` | Ativo | C.3, D.3 | Alta — bloqueia implementacao |
| `data-modeler` | Ativo | D.2, D.5 | Alta — bloqueia migrations |
| `architect` | Existente (expandido) | C.1, C.2, C.4-C.8, A.2.4 | Ja existe, so adiciona skills |

---

## G. Proximos Passos Recomendados

1. **Produzir os documentos por epico** antes de iniciar qualquer story com UI.
2. **Garantir que `/start-story` seja executado apenas depois do gate documental do epico**.
3. **Usar `/verify-slice` como defesa redundante**: se o slice tiver UI e algum documento global/por-epico estiver ausente, rejeitar com finding documental.
4. **Atualizar este documento** quando novos artefatos entrarem no gate.

---

## H. Rastreabilidade

| Fonte | O que inspirou |
|---|---|
| BMAD Method | Fase de Solutioning com UX Designer agent produzindo wireframes antes de code |
| BMAD Method | Handoff explicito entre agentes com artefatos tipados |
| GitHub spec-kit | `spec.md` + `plan.md` + `data-model.md` + `api-spec.json` por feature |
| GitHub spec-kit | Templates customizaveis, constitution.md como regras |
| Kalibrium V1 post-mortem | Drift silencioso por falta de documentacao pre-implementacao |
| ISO 17025 / metrologia | Necessidade de formatos especificos de certificado (print patterns) |
| LGPD / compliance | Acessibilidade e dados sensiveis em tela |
| PRD secao personas | Interfaces diferenciadas por persona (bancada vs desktop vs portal) |

---

> **Regra final:** este documento e vivo. Novos documentos podem ser adicionados conforme necessidade, mas NUNCA removidos sem ADR justificando. A remocao de um documento do gate e uma decisao arquitetural.
