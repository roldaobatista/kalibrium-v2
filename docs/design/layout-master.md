# Layout Master — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** B.4 (ver ux-designer.md)
> **Depende de:** style-guide.md (B.1)

---

## 1. Estrutura geral

O template principal da aplicacao segue o padrao **sidebar + header + content area**, otimizado para alta densidade de dados (gestao) e baixa friccao (bancada).

```
┌──────────────────────────────────────────────────────────────────┐
│                        HEADER (h=64px)                           │
│  [Logo]  [Search global ...............]  [🔔 3]  [Avatar ▼]    │
├──────────┬───────────────────────────────────────────────────────┤
│          │                                                       │
│ SIDEBAR  │  CONTENT AREA                                         │
│ (w=256px │  ┌─────────────────────────────────────────────────┐  │
│  or 64px)│  │ Breadcrumb: Home > Modulo > Acao                │  │
│          │  │ Page Title (h1)               [+ Novo] [Filtro] │  │
│ Tenant   │  │                                                 │  │
│ logo     │  │ ┌─────────────────────────────────────────────┐ │  │
│          │  │ │                                             │ │  │
│ • Dash   │  │ │           BODY (componentes variam)         │ │  │
│ • OS     │  │ │                                             │ │  │
│ • Calib  │  │ │                                             │ │  │
│ • Client │  │ └─────────────────────────────────────────────┘ │  │
│ • Cert   │  │                                                 │  │
│ • Financ │  │                                                 │  │
│ • Docs   │  └─────────────────────────────────────────────────┘  │
│ • Config │                                                       │
│          │  ┌─────────────────────────────────────────────────┐  │
│          │  │ FOOTER (opcional): versao, links legais         │  │
│          │  └─────────────────────────────────────────────────┘  │
└──────────┴───────────────────────────────────────────────────────┘
```

### Dimensoes fixas

| Elemento | Dimensao | Classes Tailwind |
|---|---|---|
| Header | altura 64px, largura 100% | `h-16 w-full` |
| Sidebar expanded | largura 256px | `w-64` |
| Sidebar collapsed | largura 64px | `w-16` |
| Content area | flex-1, scroll independente | `flex-1 overflow-y-auto` |
| Footer | altura auto, max 48px | `h-12` |

### Posicionamento

- Header: `fixed top-0 left-0 right-0 z-40` — sempre visivel no topo.
- Sidebar: `fixed top-16 left-0 bottom-0 z-30` — abaixo do header, scroll proprio se necessario.
- Content area: `ml-64 mt-16` (expanded) ou `ml-16 mt-16` (collapsed) — offset pela sidebar e header.
- Footer: dentro do content area, no final da pagina (nao fixo).

### Backgrounds (do style-guide.md)

| Elemento | Token | Classe |
|---|---|---|
| Header | `bg-header` | `bg-white shadow` |
| Sidebar | `bg-sidebar` | `bg-primary-900` |
| Content area | `bg-page` | `bg-neutral-50` |
| Footer | — | `bg-neutral-50 border-t border-neutral-200` |

---

## 2. Sidebar

### 2.1. Estrutura

```
┌──────────────────────┐
│  [Tenant Logo/Name]  │  ← topo: identidade do tenant
│  Acme Calibracoes    │
├──────────────────────┤
│                      │
│  ◻ Dashboard         │  ← item de navegacao
│  ◻ Ordens de Servico │
│  ▼ Laboratorio       │  ← grupo com submenu
│    ◻ Calibracoes     │
│    ◻ Procedimentos   │
│    ◻ Padroes         │
│  ◻ Clientes          │
│  ◻ Certificados      │
│  ◻ Financeiro        │
│  ◻ Fiscal            │
│  ◻ Documentos        │
│  ◻ Relatorios        │
│                      │
├──────────────────────┤
│  ◻ Configuracoes     │  ← base: separado por divisor
│  ◻ Ajuda             │
└──────────────────────┘
```

### 2.2. Itens de navegacao por modulo

Os modulos derivam dos epicos do PRD. Cada item mapeia para um grupo funcional:

| Item sidebar | Icone (Heroicons) | Epico(s) | Rota base |
|---|---|---|---|
| Dashboard | `chart-bar-square` | E11 | `/app/dashboard` |
| Ordens de Servico | `clipboard-document-list` | E04 | `/ordens-servico` |
| Laboratorio | `beaker` | E05, E06, E14 | `/bancada` |
| — Calibracoes | `adjustments-horizontal` | E05 | `/calibracoes` |
| — Procedimentos | `document-text` | E05 | `/procedimentos` |
| — Padroes | `scale` | E03/E05 | `/padroes` |
| Clientes | `building-office-2` | E03 | `/clientes` |
| Certificados | `document-check` | E06 | `/certificados` |
| Financeiro | `banknotes` | E08 | `/financeiro` |
| Fiscal | `receipt-percent` | E07 | `/fiscal` |
| Documentos | `folder-open` | E10 | `/documentos` |
| Relatorios | `chart-pie` | E11 | `/relatorios` |
| Configuracoes | `cog-6-tooth` | E02 | `/settings/tenant` |

> **Visibilidade por RBAC:** itens da sidebar sao filtrados pelo papel do usuario. Exemplo: Juliana (tecnica) nao ve Financeiro nem Fiscal. Rafael (cliente externo) usa o shell separado do portal e nao ve a sidebar do back-office. Marcelo (gerente) ve os modulos do tenant liberados para o plano.

### 2.3. Estados do item

| Estado | Visual | Classes Tailwind |
|---|---|---|
| Default | Texto `text-primary-200`, icone `text-primary-300` | `text-primary-200 hover:bg-primary-800` |
| Hover | Background sutil | `bg-primary-800 text-white` |
| Ativo | Background destacado, borda esquerda, icone solid | `bg-primary-800 text-white border-l-4 border-accent-500` |
| Disabled (RBAC) | Oculto (nao cinza — se o usuario nao tem permissao, o item nao aparece) | — |

### 2.4. Estado collapsed (64px)

```
┌────────┐
│ [Logo] │  ← tenant logo (icone pequeno, 32px)
├────────┤
│  ◻     │  ← so icones, centralizados
│  ◻     │
│  ◻     │
│  ◻     │
│  ◻     │
│  ◻     │
│  ◻     │
├────────┤
│  ◻     │  ← config
│  ◻     │  ← ajuda
└────────┘
```

- Icones centralizados, `w-6 h-6`, sem texto.
- Tooltip aparece em hover e focus de teclado; `title` sozinho nao basta.
- Submenu de "Laboratorio" abre como popover a direita por hover, focus ou clique; `Esc` fecha o popover.
- Botao de expand no rodape da sidebar: icone `chevron-double-right`.

### 2.5. Estado expanded (256px)

- Icone (`w-6 h-6`) + texto (`text-sm font-medium`) lado a lado.
- Padding do item: `px-4 py-3`.
- Submenus: indentados com `pl-12`, icone menor (`w-5 h-5`).
- Seta de expand/collapse no grupo: `chevron-down` / `chevron-right`.
- Botao de collapse no rodape da sidebar: icone `chevron-double-left`.
- Transicao de largura: `transition-all duration-300 ease-in-out`.

### 2.6. Tenant identity (topo da sidebar)

| Estado | Conteudo | Layout |
|---|---|---|
| Expanded | Logo do tenant (max 40x40px) + nome (`text-sm font-semibold text-white`, truncate) | `flex items-center gap-3 px-4 py-4` |
| Collapsed | Logo do tenant (max 32x32px), centralizado | `flex justify-center py-4` |
| Sem logo | Iniciais do tenant em circulo (`bg-primary-700 text-white rounded-full w-10 h-10`) | Fallback automatico |

### 2.7. Comportamento mobile (< 768px)

- Sidebar **oculta** por padrao.
- Hamburguer menu no header (esquerda) abre a sidebar como **drawer overlay**.
- Drawer: largura 256px (expanded), `z-50`, com backdrop `bg-black/50`.
- Fechar: clique no backdrop, botao X no topo do drawer, `Esc`, ou swipe left.
- Drawer prende o foco enquanto aberto e devolve o foco ao botao hamburger ao fechar.
- Ao selecionar um item, o drawer fecha automaticamente.

---

## 3. Header

### 3.1. Estrutura

```
┌──────────────────────────────────────────────────────────────────────┐
│ [≡]  [K] Kalibrium   │ 🔍 Buscar OS, cliente, instrumento...     │ 🔔 3 │ [JM] Juliana ▼ │
└──────────────────────────────────────────────────────────────────────┘
  ↑      ↑                 ↑                                           ↑       ↑
  hamburger  logo+nome      search global                             notif   user menu
  (mobile)
```

### 3.2. Elementos do header

| Posicao | Elemento | Detalhes |
|---|---|---|
| Esquerda | Hamburguer (mobile only) | `≡` icone `bars-3`, visivel apenas `< md`. Abre sidebar drawer. |
| Esquerda | Logo | Icone Kalibrium 32x32px. Em desktop com sidebar expanded: pode omitir texto (sidebar ja mostra tenant). Em desktop com sidebar collapsed: mostrar "Kalibrium" ao lado. |
| Centro | Search global | Combobox com placeholder "Buscar OS, cliente, instrumento...". Atalho `Ctrl+K`. |
| Direita | Notificacoes | Icone `bell`, badge numerico vermelho (`bg-danger-500 text-white text-xs rounded-full`). |
| Direita | User menu | Avatar (32x32, `rounded-full`) + nome (`text-sm`) + role (`text-xs text-neutral-400`). Chevron down. |

### 3.3. Search global

O search global e um **combobox** com busca por tipo:

```
┌─────────────────────────────────────┐
│ 🔍 Buscar OS, cliente, instrumento │  ← input (foco via Ctrl+K)
├─────────────────────────────────────┤
│ Ordens de Servico                   │  ← grupo de resultados
│   OS-2024-0142 — Acme Ltda         │
│   OS-2024-0139 — Delta Engenharia  │
├─────────────────────────────────────┤
│ Clientes                            │
│   Acme Calibracoes Ltda             │
├─────────────────────────────────────┤
│ Instrumentos                        │
│   Paquimetro 150mm — SN: 4821      │
│   Manometro 0-10bar — SN: 7733     │
├─────────────────────────────────────┤
│ Pressione Enter para busca completa │  ← footer do dropdown
└─────────────────────────────────────┘
```

- Resultados agrupados por tipo (OS, Cliente, Instrumento, Certificado).
- Max 3 resultados por grupo na busca rapida.
- Cada resultado mostra identificador principal + dado secundario.
- Navegacao por teclado: `Arrow Up/Down` entre resultados, `Enter` para abrir, `Esc` para fechar.
- O combobox usa `aria-expanded`, `aria-controls` e `aria-activedescendant`; em mobile fullscreen, foco fica preso no search ate fechar.
- Debounce de 300ms antes de buscar.
- Em mobile: icone de busca que expande para fullscreen search.

### 3.4. Notificacoes

- Icone `bell` (Heroicons outline).
- Badge numerico: posicao `absolute -top-1 -right-1`, `min-w-5 h-5 text-xs font-semibold`.
- Badge so aparece quando count > 0.
- Clique abre dropdown com lista das ultimas 5 notificacoes.
- Cada notificacao: icone + titulo + tempo relativo ("ha 5 min").
- Link "Ver todas" no rodape do dropdown → `/notificacoes`.
- Tipos: certificado emitido, OS atribuida, vencimento proximo, pagamento recebido.

### 3.5. User menu

Dropdown ao clicar no avatar/nome:

```
┌──────────────────────┐
│  [Avatar 48px]       │
│  Juliana Mendes      │
│  Tecnica             │
│  Acme Calibracoes    │
├──────────────────────┤
│  ◻ Meu Perfil       │
│  ◻ Configuracoes    │
├──────────────────────┤
│  ◻ Sair             │  ← danger color
└──────────────────────┘
```

- Avatar maior no dropdown (48x48px) que no header (32x32px).
- Nome completo + papel (role) + nome do tenant.
- Divisor entre info e acoes.
- "Sair" em `text-danger-600` para destaque semantico.

---

## 4. Content area

### 4.1. Estrutura

```
┌─────────────────────────────────────────────────────────────┐
│ Home > Ordens de Servico > OS-2024-0142                     │  ← breadcrumb
│                                                             │
│ OS-2024-0142 — Acme Ltda                [Editar] [Imprimir] │  ← page title + actions
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │                                                         │ │
│ │                    BODY                                 │ │  ← componentes variam
│ │           (tabelas, forms, cards, etc.)                 │ │
│ │                                                         │ │
│ │                                                         │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ ─────────────────────────────────────────────────────────── │  ← footer (opcional)
│ Kalibrium v1.0 — Termos de Uso — Politica de Privacidade   │
└─────────────────────────────────────────────────────────────┘
```

### 4.2. Breadcrumb

- Formato: `Home > Modulo > Submodulo > Acao` (max 4 niveis).
- Separador: `chevron-right` (Heroicons micro, `text-neutral-400`).
- Ultimo item: `text-neutral-900 font-medium` (nao e link).
- Demais itens: `text-primary-600 hover:text-primary-700` (links).
- Classe do container: `text-sm font-medium`.
- Padding: `px-0 py-2` (dentro do container de conteudo).

### 4.3. Page title + actions

```
┌───────────────────────────────────────────────────────────────┐
│  Ordens de Servico                        [+ Nova OS]        │
│  156 registros                                                │
└───────────────────────────────────────────────────────────────┘
```

- Titulo: `text-2xl font-bold text-neutral-900` (h1).
- Subtitulo opcional: `text-sm text-neutral-500` (contagem, status, contexto).
- Action buttons: alinhados a direita, `flex gap-3`.
- Botao primario unico por pagina (ex: `[+ Nova OS]`). Demais acoes em botoes secundarios ou dropdown "Mais acoes".
- Layout: `flex items-start justify-between`.
- Margem inferior: `mb-6` antes do body.

### 4.4. Body

A area de body e flexivel e recebe componentes conforme o tipo de pagina:

| Tipo de pagina | Componentes tipicos |
|---|---|
| Listagem | Filtros + tabela paginada + bulk actions |
| Detalhe | Tabs ou secoes + cards de dados + timeline |
| Formulario | Campos agrupados em secoes + botoes de acao no rodape |
| Dashboard | Grid de KPI cards + graficos + tabelas resumo |

### 4.5. Scroll independente

- Content area faz scroll vertical **independente** da sidebar e do header.
- Header: `position: fixed` (sempre visivel).
- Sidebar: `position: fixed`, scroll proprio se necessario (`overflow-y-auto`).
- Content area: `overflow-y-auto`, com `height: calc(100vh - 64px)`.
- Isso garante que o usuario sempre ve o header e a navegacao, mesmo em paginas longas.

### 4.6. Container de conteudo

- Max-width: `max-w-7xl` (1280px) em telas muito largas, para manter legibilidade.
- Padding horizontal: `px-4` (mobile), `px-6` (tablet), `px-8` (desktop).
- Centralizado: `mx-auto`.

---

## 5. Footer

O footer e **minimalista e opcional**. Aparece no final do content area (nao fixo).

```
─────────────────────────────────────────────────────────────
Kalibrium v1.0.0   •   Termos de Uso   •   Privacidade   •   Suporte
```

- Texto: `text-xs text-neutral-400`.
- Links: `text-primary-500 hover:text-primary-600`.
- Separador: `•` (`text-neutral-300`).
- Padding: `py-4 px-8`.
- Borda superior: `border-t border-neutral-200`.
- Visivel apenas em desktop. Em mobile, omitir (espaco e precioso).

---

## 6. Wireframes ASCII — 4 viewports

### 6.1. Desktop — sidebar expanded (>= 1024px)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium          │ 🔍 Buscar OS, cliente...              🔔 3  [JM]▼│
├────────────┬─────────────────────────────────────────────────────────────┤
│            │                                                             │
│ Acme Lab   │  Home > Ordens de Servico                                   │
│ [logo]     │                                                             │
│            │  Ordens de Servico                          [+ Nova OS]     │
│ ━━━━━━━━━━ │  156 registros                                              │
│            │                                                             │
│ ◼ Dashb    │  ┌─ Filtros ──────────────────────────────────────────────┐ │
│ ◻ OS       │  │ Status ▼    Periodo ▼    Cliente ▼    🔍 Buscar...    │ │
│ ▼ Labor    │  └────────────────────────────────────────────────────────┘ │
│   ◻ Calib  │                                                             │
│   ◻ Proced │  ┌─ Tabela ──────────────────────────────────────────────┐ │
│   ◻ Padroe │  │ □  # OS         │ Cliente       │ Status   │ Acoes   │ │
│ ◻ Client   │  │ □  OS-2024-0142 │ Acme Ltda     │ ●Aberta  │   ⋮     │ │
│ ◻ Certif   │  │ □  OS-2024-0141 │ Delta Eng.    │ ●Exec.   │   ⋮     │ │
│ ◻ Financ   │  │ □  OS-2024-0140 │ Beta Metr.    │ ●Fechada │   ⋮     │ │
│ ◻ Fiscal   │  │ □  OS-2024-0139 │ Gama Ind.     │ ●Aberta  │   ⋮     │ │
│ ◻ Docs     │  └────────────────────────────────────────────────────────┘ │
│ ◻ Portal   │                                                             │
│ ◻ Relat    │  < 1  2  3  ...  10 >           Mostrando 1-25 de 156      │
│            │                                                             │
│ ━━━━━━━━━━ │  ─────────────────────────────────────────────────────────  │
│ ◻ Config   │  Kalibrium v1.0.0  •  Termos  •  Privacidade  •  Suporte   │
│ ◻ Ajuda    │                                                             │
│     « ◁    │                                                             │
└────────────┴─────────────────────────────────────────────────────────────┘
  w=256px                            flex-1
```

### 6.2. Desktop — sidebar collapsed (>= 1024px, usuario recolheu)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium          │ 🔍 Buscar OS, cliente...              🔔 3  [JM]▼│
├────────┬─────────────────────────────────────────────────────────────────┤
│        │                                                                 │
│ [logo] │  Home > Ordens de Servico                                       │
│        │                                                                 │
│ ◼      │  Ordens de Servico                              [+ Nova OS]     │
│ ◻      │  156 registros                                                  │
│ ◻      │                                                                 │
│ ◻      │  ┌─ Filtros ──────────────────────────────────────────────────┐ │
│ ◻      │  │ Status ▼    Periodo ▼    Cliente ▼    🔍 Buscar...        │ │
│ ◻      │  └────────────────────────────────────────────────────────────┘ │
│ ◻      │                                                                 │
│ ◻      │  ┌─ Tabela ──────────────────────────────────────────────────┐ │
│ ◻      │  │ □  # OS         │ Cliente       │ Status   │ Prazo │ ⋮   │ │
│ ◻      │  │ □  OS-2024-0142 │ Acme Ltda     │ ●Aberta  │ 15/04 │ ⋮   │ │
│ ◻      │  │ □  OS-2024-0141 │ Delta Eng.    │ ●Exec.   │ 18/04 │ ⋮   │ │
│        │  │ □  OS-2024-0140 │ Beta Metr.    │ ●Fechada │ 10/04 │ ⋮   │ │
│ ━━━━━  │  └────────────────────────────────────────────────────────────┘ │
│ ◻      │                                                                 │
│ ◻      │  < 1  2  3  ...  10 >             Mostrando 1-25 de 156        │
│  ▷ »   │                                                                 │
└────────┴─────────────────────────────────────────────────────────────────┘
  w=64px                              flex-1 (mais espaco horizontal)
```

### 6.3. Tablet (768px — 1023px)

```
┌──────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium    │ 🔍 Buscar...                       🔔 3  [JM]▼│
├────────┬─────────────────────────────────────────────────────────┤
│        │                                                         │
│ [logo] │  Home > Ordens de Servico                               │
│        │                                                         │
│ ◼  ←─┐ │  Ordens de Servico                      [+ Nova OS]    │
│ ◻    │ │  156 registros                                          │
│ ◻    │ │                                                         │
│ ◻  hover│ ┌─ Filtros ───────────────────────────────────────────┐│
│ ◻  expande│ Status ▼  Periodo ▼  🔍 Buscar...                 ││
│ ◻    │ │  └─────────────────────────────────────────────────────┘│
│ ◻    │ │                                                         │
│      │ │  ┌─ Tabela ───────────────────────────────────────────┐│
│ ━━━━ │ │  │ □  # OS         │ Cliente    │ Status   │ ⋮       ││
│ ◻    │ │  │ □  OS-2024-0142 │ Acme Ltda  │ ●Aberta  │ ⋮       ││
│ ◻    │ │  │ □  OS-2024-0141 │ Delta Eng. │ ●Exec.   │ ⋮       ││
│      │ │  └─────────────────────────────────────────────────────┘│
└────────┴─────────────────────────────────────────────────────────┘
  w=64px   sidebar collapsed permanente; expand on hover (overlay)
           tabela pode ocultar colunas menos prioritarias
```

### 6.4. Mobile (< 768px)

```
┌──────────────────────────────────┐
│ [≡]  [K]              🔍  🔔 3  │  ← header simplificado
├──────────────────────────────────┤
│                                  │
│ Home > OS                        │  ← breadcrumb compacto
│                                  │
│ Ordens de Servico    [+ Nova OS] │
│ 156 registros                    │
│                                  │
│ ┌─ Filtros (collapsed) ───────┐ │  ← filtros em accordion
│ │ [Abrir filtros ▼]           │ │
│ └─────────────────────────────┘ │
│                                  │
│ ┌─ Card ──────────────────────┐ │  ← cards em vez de tabela
│ │ OS-2024-0142                │ │
│ │ Acme Ltda                   │ │
│ │ ●Aberta    Prazo: 15/04    │ │
│ │                     [⋮]    │ │
│ └─────────────────────────────┘ │
│ ┌─ Card ──────────────────────┐ │
│ │ OS-2024-0141                │ │
│ │ Delta Engenharia            │ │
│ │ ●Em execucao  Prazo: 18/04 │ │
│ │                     [⋮]    │ │
│ └─────────────────────────────┘ │
│                                  │
│ [Carregar mais]                  │  ← paginacao incremental
│                                  │
└──────────────────────────────────┘

  Sidebar como drawer overlay ao tocar [≡]:

  ┌─────────────────────┐
  │ X  Acme Calibracoes │
  │                     │
  │ ◼ Dashboard         │
  │ ◻ Ordens de Servico │
  │ ▼ Laboratorio       │
  │   ◻ Calibracoes     │
  │   ◻ Procedimentos   │
  │   ◻ Padroes         │
  │ ◻ Clientes          │
  │ ◻ Certificados      │
  │ ◻ Financeiro        │
  │ ◻ Fiscal            │
  │ ◻ Documentos        │
  │ ◻ Portal do Cliente │
  │ ◻ Relatorios        │
  │ ━━━━━━━━━━━━━━━━━━━ │
  │ ◻ Configuracoes     │
  │ ◻ Ajuda             │
  └─────────────────────┘
  ← backdrop bg-black/50
```

---

## 7. Responsividade

### 7.1. Breakpoints

| Breakpoint | Largura | Sidebar | Header | Content |
|---|---|---|---|---|
| `lg` (>= 1024px) | Desktop | Expanded permanente (256px). Usuario pode colapsar manualmente. | Completo: logo + search + notif + user | Offset `ml-64` ou `ml-16` |
| `md` (768-1023px) | Tablet | Collapsed permanente (64px). Expand on hover (overlay, nao empurra content). | Completo, search compacto | Offset `ml-16` |
| `< md` (< 768px) | Mobile | Hidden. Drawer overlay via hamburguer. | Simplificado: hamburguer + logo + icone search + notif + avatar | Full width, sem offset |

### 7.2. Transicoes de layout

| Componente | Desktop | Tablet | Mobile |
|---|---|---|---|
| Sidebar | Expanded (256px), toggle para collapsed | Collapsed (64px), hover expand | Hidden, drawer overlay |
| Search | Input expandido com placeholder | Input compacto | Icone que abre search fullscreen |
| Tabelas | Todas as colunas | Colunas prioritarias (ocultar secundarias) | Cards empilhados (sem tabela) |
| Page actions | Botoes inline ao lado do titulo | Botoes inline | Botao primario + dropdown "Mais" |
| Filtros | Sempre visiveis acima da tabela | Sempre visiveis | Accordion collapsed por padrao |
| Pagination | Numeros de pagina + prev/next | Prev/next + pagina atual | "Carregar mais" com paginas do servidor |
| Breadcrumb | Caminho completo | Caminho completo | Compacto (ultimo 2 niveis) |
| Footer | Visivel | Visivel | Oculto |

### 7.3. Persona vs viewport (principio de design)

| Persona | Viewport primario | Prioridade de layout |
|---|---|---|
| Marcelo (gerente) | Desktop | Alta densidade de dados, tabelas completas, dashboard com KPIs |
| Juliana (tecnica) | Tablet / Mobile | Baixa friccao, campos grandes, teclado numerico, poucos toques |
| Rafael (cliente) | Desktop / Mobile | Portal simples, download rapido, consulta de status |

---

## 8. Tokens de layout (resumo para implementacao)

### 8.1. CSS custom properties sugeridas

```css
/* resources/css/layout.css */
:root {
  --layout-header-height: 4rem;      /* 64px */
  --layout-sidebar-expanded: 16rem;  /* 256px */
  --layout-sidebar-collapsed: 4rem;  /* 64px */
  --layout-content-max-width: 80rem; /* 1280px */
  --layout-transition-duration: 300ms;
  --layout-transition-easing: cubic-bezier(0.4, 0, 0.2, 1);
}
```

### 8.2. Z-index scale

| Camada | z-index | Elemento |
|---|---|---|
| Base | `z-0` | Content area |
| Sticky | `z-10` | Table headers sticky |
| Sidebar | `z-30` | Sidebar (fixed) |
| Header | `z-40` | Header (fixed) |
| Drawer | `z-50` | Sidebar drawer (mobile) + backdrop |
| Dropdown | `z-50` | Dropdowns, popovers, tooltips |
| Modal | `z-50` | Modais + backdrop |
| Toast | `z-[60]` | Toasts / notificacoes flutuantes |

### 8.3. Classe Tailwind do shell principal

```html
<!-- Layout shell (Blade component: layouts/app.blade.php) -->
<div class="min-h-screen bg-neutral-50">
  <!-- Header -->
  <header class="fixed top-0 left-0 right-0 h-16 bg-white shadow z-40
                 flex items-center px-4 lg:px-6">
    <!-- ... -->
  </header>

  <!-- Sidebar -->
  <aside class="fixed top-16 left-0 bottom-0 z-30
                w-64 lg:w-64 md:w-16
                bg-primary-900 text-primary-200
                transition-all duration-300 ease-in-out
                overflow-y-auto
                max-md:hidden">
    <!-- ... -->
  </aside>

  <!-- Mobile drawer (hidden by default) -->
  <div x-show="sidebarOpen"
       class="fixed inset-0 z-50 md:hidden">
    <div class="absolute inset-0 bg-black/50" @click="sidebarOpen = false"></div>
    <aside class="absolute top-0 left-0 bottom-0 w-64 bg-primary-900">
      <!-- ... -->
    </aside>
  </div>

  <!-- Content -->
  <main class="mt-16 lg:ml-64 md:ml-16
               min-h-[calc(100vh-4rem)]
               overflow-y-auto">
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 py-6">
      <!-- Breadcrumb -->
      <!-- Page title + actions -->
      <!-- Body -->
    </div>
    <!-- Footer -->
  </main>
</div>
```

> **Nota:** o snippet acima e referencia de estrutura, nao codigo final. O implementer adapta conforme o setup real de Livewire 4 + Alpine.js.

---

## 9. Checklist de implementacao

| Item | Criterio |
|---|---|
| Header fixo no topo | `position: fixed`, sempre visivel durante scroll |
| Sidebar fixed a esquerda | Nao scrolla com o conteudo |
| Content area scroll independente | Scroll vertical proprio |
| Sidebar expanded/collapsed | Toggle funcional com transicao suave |
| Mobile drawer | Overlay com backdrop, fecha ao clicar fora |
| Search global Ctrl+K | Abre combobox, busca por tipo, navegacao por teclado |
| Notificacoes com badge | Badge some quando count = 0 |
| User menu dropdown | Avatar + nome + role + acoes |
| Breadcrumb dinamico | Reflete a rota atual ate 4 niveis |
| Page title + actions | h1 a esquerda, botoes a direita |
| RBAC na sidebar | Itens filtrados por permissao do usuario |
| Tenant identity | Logo/iniciais + nome no topo da sidebar |
| Responsivo 3 breakpoints | lg, md, < md com comportamento distinto |
| Acessibilidade nav | `role="navigation"`, `aria-current="page"`, `aria-label` em icones |
