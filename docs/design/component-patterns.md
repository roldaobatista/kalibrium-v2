# Component Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Stack:** Laravel 13, Livewire 4, Tailwind CSS 4, Alpine.js
> **Dependencia:** `docs/design/style-guide.md` v1.0.0

---

## Convencoes deste documento

- **Tokens semanticos** referenciam `docs/design/style-guide.md` (cores, tipografia, espacamento).
- **Classes Tailwind** sao as classes CSS finais usadas em templates Blade/Livewire.
- **Variantes** definem estilos visuais distintos do mesmo componente.
- **Estados** definem comportamento visual em resposta a interacao ou dados.
- **Tamanhos** definem dimensoes (sm, md, lg) — `md` e sempre o padrao.
- **ARIA** indica atributos de acessibilidade obrigatorios.
- Exemplos em ASCII/Markdown sao representacoes visuais; a implementacao usa Blade components.

---

## Indice

### Acoes
- [1. Button](#1-button)
- [2. Link Button](#2-link-button)
- [3. Dropdown Menu](#3-dropdown-menu)
- [4. Bulk Actions Bar](#4-bulk-actions-bar)

### Formularios
- [5. Text Input](#5-text-input)
- [6. Textarea](#6-textarea)
- [7. Select / Combobox](#7-select--combobox)
- [8. Checkbox / Checkbox Group](#8-checkbox--checkbox-group)
- [9. Radio Group](#9-radio-group)
- [10. Toggle Switch](#10-toggle-switch)
- [11. Date Picker](#11-date-picker)
- [12. Date Range Picker](#12-date-range-picker)
- [13. File Upload](#13-file-upload)
- [14. Number Input](#14-number-input)

### Dados
- [15. Table](#15-table)
- [16. Card](#16-card)
- [17. Badge / Tag](#17-badge--tag)
- [18. Avatar](#18-avatar)
- [19. Stat / KPI](#19-stat--kpi)
- [20. Empty State](#20-empty-state)
- [21. Skeleton Loader](#21-skeleton-loader)

### Navegacao
- [22. Breadcrumb](#22-breadcrumb)
- [23. Tabs](#23-tabs)
- [24. Pagination](#24-pagination)
- [25. Step Indicator](#25-step-indicator)

### Feedback
- [26. Toast / Notification](#26-toast--notification)
- [27. Alert Banner](#27-alert-banner)
- [28. Modal / Dialog](#28-modal--dialog)
- [29. Progress Bar](#29-progress-bar)
- [30. Spinner / Loading Indicator](#30-spinner--loading-indicator)

### Layout
- [31. Section Header](#31-section-header)
- [32. Divider](#32-divider)
- [33. Accordion / Collapsible](#33-accordion--collapsible)

---

# Acoes

---

## 1. Button

Componente de acao primario do sistema. Dispara operacoes: salvar, criar, excluir, navegar.

### Variantes

| Variante | Background | Texto | Borda | Uso |
|---|---|---|---|---|
| `primary` | `bg-primary-600` | `text-white` | nenhuma | **Acao principal da pagina.** Salvar, Criar, Confirmar. Maximo 1 por tela. |
| `secondary` | `bg-white` | `text-neutral-700` | `border border-neutral-300` | Acoes complementares. Cancelar, Voltar, Exportar. |
| `outline` | `bg-transparent` | `text-primary-600` | `border border-primary-300` | Acoes terciarias. Filtrar, Editar inline. |
| `ghost` | `bg-transparent` | `text-neutral-600` | nenhuma | Acoes minimas. Fechar, Recolher, links disfarçados de botao. |
| `danger` | `bg-danger-600` | `text-white` | nenhuma | Acoes destrutivas. Excluir, Revogar, Desativar. Sempre com confirmacao. |
| `icon-only` | herda da variante base | — | herda | Botao quadrado sem texto. Tabelas, toolbars. Requer `aria-label`. |

### Tamanhos

| Tamanho | Padding | Font | Icone | Altura aprox. |
|---|---|---|---|---|
| `sm` | `px-3 py-1.5` | `text-sm` | `w-4 h-4` | 32px |
| `md` (padrao) | `px-4 py-2` | `text-sm` | `w-5 h-5` | 36px |
| `lg` | `px-6 py-3` | `text-base` | `w-5 h-5` | 44px |

### Estados

| Estado | Visual | Classe adicional |
|---|---|---|
| **default** | Cor da variante | — |
| **hover** | Background 1 shade mais escuro | `hover:bg-primary-700` (primary), `hover:bg-neutral-50` (secondary) |
| **focus** | Ring azul ao redor | `focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:ring-offset-2` |
| **active/pressed** | Background 2 shades mais escuro | `active:bg-primary-800` |
| **disabled** | Opacidade reduzida, cursor bloqueado | `disabled:opacity-50 disabled:cursor-not-allowed` |
| **loading** | Spinner substituindo icone, texto mantido | Spinner `animate-spin` + `pointer-events-none` |

### Acessibilidade

- `role="button"` (implicito em `<button>`).
- `aria-disabled="true"` quando desabilitado (alem de `disabled` attribute).
- `aria-label` obrigatorio em `icon-only`.
- `aria-busy="true"` durante loading.
- Keyboard: `Enter` ou `Space` ativa. `Tab` navega.
- Focus visible via `focus-visible:ring-2`.

### Exemplo

```
┌───────────────────┐   ┌───────────────────┐   ┌──────────┐
│ + Novo Certificado│   │    Cancelar       │   │  🗑 Excl. │
│   [primary, md]   │   │  [secondary, md]  │   │ [danger]  │
└───────────────────┘   └───────────────────┘   └──────────┘

┌──────┐  ┌──────┐  ┌──────┐
│  ✏️  │  │  📋  │  │  ⋮   │    <- icon-only, sm
└──────┘  └──────┘  └──────┘
```

### Composicao com icone

```
┌───────────────────────┐
│  [+]  Novo Certificado│   <- icone a esquerda do texto
└───────────────────────┘

┌───────────────────────┐
│  Exportar PDF  [↓]    │   <- icone a direita do texto
└───────────────────────┘
```

- Icone sempre com `gap-2` do texto.
- Icone `w-5 h-5` (md), `w-4 h-4` (sm), `w-5 h-5` (lg).

### Loading state

```
┌───────────────────────┐
│  (○) Salvando...      │   <- spinner + texto alterado
└───────────────────────┘
```

- Spinner substitui o icone (se houver) ou aparece a esquerda do texto.
- Texto muda para gerundio: "Salvar" -> "Salvando...", "Excluir" -> "Excluindo...".
- `pointer-events-none` impede duplo-clique.

---

## 2. Link Button

Botao com aparencia de link. Semanticamente um `<a>` ou `<button>` com estilo textual.

### Variantes

| Variante | Cor | Uso |
|---|---|---|
| `default` | `text-primary-600` | Navegacao inline, "Ver todos", "Saiba mais" |
| `muted` | `text-neutral-500` | Links secundarios, "Pular", "Talvez depois" |
| `danger` | `text-danger-600` | "Remover", "Cancelar conta" |

### Tamanhos

| Tamanho | Font |
|---|---|
| `sm` | `text-sm` |
| `md` (padrao) | `text-sm` |
| `lg` | `text-base` |

### Estados

| Estado | Visual |
|---|---|
| **default** | Cor da variante, `underline-offset-4` |
| **hover** | 1 shade mais escuro, `underline` |
| **focus** | `focus:ring-2 focus:ring-primary-500/20 rounded` |
| **disabled** | `opacity-50 cursor-not-allowed` |

### Acessibilidade

- Se navega: usar `<a href="...">`.
- Se executa acao: usar `<button>`; `role="button"` e implicito.
- Nunca usar `<a>` sem `href`.
- Texto descritivo — evitar "clique aqui". Usar "Ver todos os certificados".

### Exemplo

```
Ver todos os certificados →    [default]
Pular esta etapa               [muted]
Remover instrumento             [danger]
```

---

## 3. Dropdown Menu

Menu de acoes contextuais acionado por botao trigger (tipicamente icone `⋮` ou botao com chevron).

### Variantes

| Variante | Trigger | Uso |
|---|---|---|
| `actions` | Botao `⋮` (icon-only) | Menu de acoes por linha de tabela |
| `button-dropdown` | Botao com chevron `▼` | Botao com opcoes alternativas (ex: "Salvar ▼" -> Salvar como rascunho) |
| `context` | Right-click (opcional) | Menu de contexto em areas especificas |

### Estrutura

```
┌──────────────────────┐
│  ⋮  │  <- trigger (icon-only button)
└──┬───────────────────┘
   │
   ▼
┌──────────────────────┐
│  ✏️  Editar          │  <- item com icone
│  📋  Duplicar        │
│ ─────────────────── │  <- divider
│  🗑  Excluir         │  <- item danger
└──────────────────────┘
```

### Menu item

| Tipo | Visual | Uso |
|---|---|---|
| `default` | `text-neutral-700`, hover `bg-neutral-50` | Acoes normais |
| `danger` | `text-danger-600`, hover `bg-danger-50` | Acoes destrutivas |
| `disabled` | `text-neutral-400`, sem hover | Acoes indisponiveis |
| `divider` | `border-t border-neutral-200 my-1` | Separador de grupos |

### Estados

| Estado | Visual |
|---|---|
| **closed** | Menu oculto, trigger visivel |
| **open** | Menu visivel, `shadow-md`, `rounded-lg`, `bg-white`, `border border-neutral-200` |
| **item hover** | `bg-neutral-50` (default) ou `bg-danger-50` (danger) |
| **item focus** | `bg-neutral-100` + `outline-none` |

### Tamanhos

| Tamanho | Min-width | Padding item |
|---|---|---|
| `sm` | 160px | `px-3 py-1.5` |
| `md` (padrao) | 200px | `px-4 py-2` |
| `lg` | 240px | `px-4 py-2.5` |

### Acessibilidade

- Trigger: `aria-haspopup="true"`, `aria-expanded="true/false"`.
- Menu: `role="menu"`.
- Cada item: `role="menuitem"`.
- Items desabilitados: `aria-disabled="true"`.
- Keyboard: `Enter`/`Space` abre menu e ativa item. `Escape` fecha. `ArrowUp`/`ArrowDown` navega entre items. `Home`/`End` vai ao primeiro/ultimo item.
- Focus trap: foco fica dentro do menu enquanto aberto.
- Ao fechar, foco retorna ao trigger.

### Implementacao Alpine.js

```
x-data="{ open: false }"
@click.away="open = false"
@keydown.escape="open = false"
```

---

## 4. Bulk Actions Bar

Barra de acoes que aparece quando 1+ items sao selecionados em tabela/lista.

### Estrutura

```
┌──────────────────────────────────────────────────────────────┐
│  ☑ 3 selecionados    [Exportar]  [Mover]  [🗑 Excluir]  [✕] │
└──────────────────────────────────────────────────────────────┘
```

### Variantes

| Variante | Posicao | Uso |
|---|---|---|
| `sticky-bottom` | `fixed bottom-0` | Padrao para tabelas longas |
| `inline-top` | Acima da tabela | Tabelas curtas ou quando bottom e ocupado |

### Visual

- Background: `bg-primary-50 border border-primary-200`.
- Texto: `text-primary-800 font-medium`.
- Contador: numero em `font-semibold`.
- Botoes: variante `secondary` (sm) para acoes normais, `danger` (sm) para acoes destrutivas.
- Botao fechar (✕): `ghost` icon-only, limpa selecao.

### Estados

| Estado | Visual |
|---|---|
| **hidden** | Nenhum item selecionado — barra oculta |
| **visible** | 1+ items selecionados — barra aparece com animacao slide-up |
| **loading** | Acao em execucao — botoes disabled, spinner no botao acionado |

### Acessibilidade

- `role="toolbar"`, `aria-label="Acoes em lote"`.
- `aria-live="polite"` para anunciar quantidade de items selecionados.
- Keyboard: `Tab` navega entre botoes da barra.

---

# Formularios

---

## 5. Text Input

Campo de entrada de texto de uma linha.

### Estrutura

```
  Nome do instrumento *              <- label
  ┌──────────────────────────────┐
  │ Ex: Paquimetro digital       │   <- input com placeholder
  └──────────────────────────────┘
  Identificacao unica do instrumento  <- helper text

  --- ou, com erro: ---

  Nome do instrumento *              <- label
  ┌──────────────────────────────┐
  │ [valor invalido]             │   <- input com borda vermelha
  └──────────────────────────────┘
  ⚠ Campo obrigatorio               <- error message
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Entrada de texto padrao |
| `with-prefix` | Icone ou texto a esquerda (ex: icone de busca, prefixo "R$") |
| `with-suffix` | Icone ou texto a direita (ex: unidade "mm", icone de limpar) |
| `with-prefix-suffix` | Ambos (ex: "R$" a esquerda + ".00" a direita) |

### Tamanhos

| Tamanho | Altura | Padding | Font |
|---|---|---|---|
| `sm` | 32px | `px-3 py-1.5` | `text-sm` |
| `md` (padrao) | 36px | `px-3 py-2` | `text-sm` |
| `lg` | 44px | `px-4 py-3` | `text-base` |

### Estados

| Estado | Borda | Background | Extras |
|---|---|---|---|
| **default** | `border-neutral-300` | `bg-white` | — |
| **hover** | `border-neutral-400` | `bg-white` | — |
| **focus** | `border-primary-500` | `bg-white` | `ring-2 ring-primary-500/20` |
| **disabled** | `border-neutral-200` | `bg-neutral-100` | `cursor-not-allowed text-neutral-500` |
| **error** | `border-danger-500` | `bg-white` | `ring-2 ring-danger-500/20` |
| **success** | `border-success-500` | `bg-white` | `ring-2 ring-success-500/20` (uso raro, validacao inline) |

### Label

- Fonte: `text-sm font-medium text-neutral-700`.
- Obrigatorio: asterisco `*` em `text-danger-500` apos o texto.
- Gap label-input: `space-y-1.5` (6px).

### Helper text

- Fonte: `text-sm text-neutral-500`.
- Posicao: abaixo do input, `mt-1.5`.

### Error message

- Fonte: `text-sm text-danger-600`.
- Icone: `exclamation-circle` mini (w-4 h-4) antes do texto.
- Posicao: substitui helper text quando em erro.
- `aria-live="polite"` para anunciar erro.

### Acessibilidade

- `<label>` com `for` apontando para `id` do input.
- `aria-required="true"` para campos obrigatorios.
- `aria-invalid="true"` quando em erro.
- `aria-describedby` apontando para helper text ou error message.
- `autocomplete` correto (ex: `name`, `email`, `tel`).

---

## 6. Textarea

Campo de entrada de texto multi-linha.

### Estrutura

```
  Observacoes da calibracao
  ┌──────────────────────────────┐
  │                              │
  │                              │
  │                              │
  └──────────────────────────────┘
  0/500 caracteres                  <- contador (opcional)
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Texto livre (observacoes, descricoes) |
| `auto-resize` | Cresce conforme conteudo (Alpine.js `x-resize`) |
| `fixed` | Altura fixa com scroll interno |

### Tamanhos

| Tamanho | Min-height | Rows padrao |
|---|---|---|
| `sm` | 64px | 2 |
| `md` (padrao) | 96px | 3 |
| `lg` | 160px | 6 |

### Estados

Mesmos do [Text Input](#5-text-input).

### Acessibilidade

- Mesmos do Text Input.
- `aria-multiline="true"` (implicito em `<textarea>`).
- Contador de caracteres: `aria-live="polite"` para anunciar quando proximo do limite.

---

## 7. Select / Combobox

Campo de selecao de opcao unica ou multipla. Pode ser nativo (`<select>`) ou custom (searchable combobox).

### Variantes

| Variante | Uso |
|---|---|
| `native` | `<select>` nativo. Listas curtas (< 10 opcoes) sem busca. |
| `searchable` | Combobox com campo de busca. Listas longas (instrumentos, clientes, municipios). |
| `multi` | Combobox multi-select com chips/tags. Selecionar multiplos (tags, categorias). |
| `creatable` | Combobox que permite criar nova opcao. Campos de texto livre com sugestoes. |

### Estrutura (searchable)

```
  Instrumento *
  ┌──────────────────────────── ▼ ┐
  │ Selecione...                  │   <- closed
  └───────────────────────────────┘

  --- aberto: ---

  Instrumento *
  ┌───────────────────────────────┐
  │ 🔍 Buscar instrumento...      │   <- search input
  ├───────────────────────────────┤
  │  Paquimetro digital 150mm    │   <- option (hover)
  │  Micrometro externo 0-25mm   │
  │  Relogio comparador 0.01mm  │
  │  ─────────────────────────── │
  │  Nenhum resultado             │   <- empty state (se busca vazia)
  └───────────────────────────────┘
```

### Estrutura (multi com chips)

```
  Categorias
  ┌───────────────────────────────┐
  │ [Dimensional ✕] [Pressao ✕]  │   <- chips selecionados
  │ Buscar...                     │
  └───────────────────────────────┘
```

### Tamanhos

| Tamanho | Altura trigger | Font |
|---|---|---|
| `sm` | 32px | `text-sm` |
| `md` (padrao) | 36px | `text-sm` |
| `lg` | 44px | `text-base` |

### Estados

| Estado | Visual |
|---|---|
| **default** | Borda `border-neutral-300`, placeholder `text-neutral-400` |
| **hover** | `border-neutral-400` |
| **focus/open** | `border-primary-500`, `ring-2 ring-primary-500/20`, dropdown visivel |
| **disabled** | `bg-neutral-100`, `cursor-not-allowed` |
| **error** | `border-danger-500`, `ring-2 ring-danger-500/20` |
| **loading** | Spinner no lugar do chevron |

### Dropdown do combobox

- Background: `bg-white`.
- Sombra: `shadow-md`.
- Border: `border border-neutral-200 rounded-lg`.
- Max-height: `max-h-60 overflow-y-auto`.
- Item hover: `bg-primary-50`.
- Item selecionado: `bg-primary-50` + icone check `text-primary-600`.

### Acessibilidade

- `role="combobox"` no trigger.
- `aria-expanded="true/false"`.
- `aria-controls` apontando para o listbox.
- Listbox: `role="listbox"`.
- Opcoes: `role="option"`, `aria-selected="true/false"`.
- Multi: `aria-multiselectable="true"` no listbox.
- Keyboard: `ArrowDown`/`ArrowUp` navega opcoes. `Enter` seleciona. `Escape` fecha. Typing filtra.

---

## 8. Checkbox / Checkbox Group

Selecao de opcoes booleanas ou multiplas escolhas.

### Variantes

| Variante | Uso |
|---|---|
| `single` | Um checkbox isolado (ex: "Aceito os termos") |
| `group` | Lista vertical de checkboxes (ex: "Selecione os servicos") |
| `indeterminate` | Estado intermediario (ex: "Selecionar todos" quando parcialmente selecionado) |

### Estrutura

```
  Servicos incluidos
  ☑ Calibracao                     <- checked
  ☐ Reparo                         <- unchecked
  ☐ Ajuste                         <- unchecked
  ☑ Emissao de certificado         <- checked
```

### Tamanhos

| Tamanho | Box size | Font do label |
|---|---|---|
| `sm` | `w-4 h-4` | `text-sm` |
| `md` (padrao) | `w-4 h-4` | `text-sm` |
| `lg` | `w-5 h-5` | `text-base` |

### Estados

| Estado | Visual |
|---|---|
| **unchecked** | Borda `border-neutral-300`, `bg-white` |
| **checked** | `bg-primary-600`, `border-primary-600`, icone check branco |
| **indeterminate** | `bg-primary-600`, `border-primary-600`, icone minus branco |
| **hover** | `border-primary-500` |
| **focus** | `ring-2 ring-primary-500/20 ring-offset-2` |
| **disabled** | `opacity-50 cursor-not-allowed` |
| **error** | `border-danger-500` + mensagem de erro abaixo do grupo |

### Acessibilidade

- `role="checkbox"` (implicito em `<input type="checkbox">`).
- `aria-checked="true/false/mixed"` (mixed para indeterminate).
- Grupo: preferir `<fieldset>` + `<legend>`; se custom, usar `role="group"` e `aria-labelledby` apontando para titulo do grupo.
- Keyboard: `Space` toggle. `Tab` navega entre checkboxes.

---

## 9. Radio Group

Selecao exclusiva (uma opcao entre varias).

### Estrutura

```
  Tipo de servico *
  ○ Calibracao com acreditacao RBC    <- unselected
  ● Calibracao sem acreditacao        <- selected
  ○ Apenas reparo                      <- unselected
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Lista vertical de radios |
| `card` | Cada opcao e um card selecionavel (para escolhas complexas com descricao) |
| `horizontal` | Radios em linha (maximo 3-4 opcoes curtas) |

### Estrutura (card variant)

```
  Plano de servico *

  ┌─── ● ────────────────────────┐  ┌─── ○ ────────────────────────┐
  │  Starter                     │  │  Professional                │
  │  Ate 5 usuarios              │  │  Ate 25 usuarios             │
  │  R$ 200/mes                  │  │  R$ 800/mes                  │
  └──────────────────────────────┘  └──────────────────────────────┘
     selecionado (borda primary)        nao selecionado
```

### Tamanhos

| Tamanho | Radio size | Font do label |
|---|---|---|
| `sm` | `w-4 h-4` | `text-sm` |
| `md` (padrao) | `w-4 h-4` | `text-sm` |
| `lg` | `w-5 h-5` | `text-base` |

### Estados

| Estado | Visual |
|---|---|
| **unselected** | Borda `border-neutral-300`, `bg-white` |
| **selected** | `border-primary-600`, dot interno `bg-primary-600` |
| **hover** | `border-primary-500` |
| **focus** | `ring-2 ring-primary-500/20 ring-offset-2` |
| **disabled** | `opacity-50 cursor-not-allowed` |
| **error** | `border-danger-500` + mensagem de erro |

### Acessibilidade

- Preferir `<fieldset>` + `<legend>` no grupo; se custom, usar `role="radiogroup"` no container.
- Cada opcao: `role="radio"` (implicito em `<input type="radio">`).
- `aria-checked="true/false"`.
- `aria-required="true"` no radiogroup se obrigatorio.
- Keyboard: `ArrowDown`/`ArrowRight` seleciona proximo. `ArrowUp`/`ArrowLeft` seleciona anterior. `Tab` sai do grupo.

---

## 10. Toggle Switch

Controle binario on/off para configuracoes.

### Estrutura

```
  Emitir certificado metrologico
  ┌──────┐
  │  ●━━━│  ON    <- ativo (bg-primary-600)
  └──────┘

  ┌──────┐
  │━━━●  │  OFF   <- inativo (bg-neutral-200)
  └──────┘
```

### Tamanhos

| Tamanho | Track size | Knob size |
|---|---|---|
| `sm` | `w-8 h-4` | `w-3 h-3` |
| `md` (padrao) | `w-11 h-6` | `w-5 h-5` |
| `lg` | `w-14 h-7` | `w-6 h-6` |

### Estados

| Estado | Visual |
|---|---|
| **off** | Track `bg-neutral-200`, knob a esquerda |
| **on** | Track `bg-primary-600`, knob a direita |
| **hover** | Sombra sutil no knob |
| **focus** | `ring-2 ring-primary-500/20` ao redor do track |
| **disabled** | `opacity-50 cursor-not-allowed` |

### Label

- Label a esquerda do toggle: `text-sm font-medium text-neutral-700`.
- Descricao opcional abaixo: `text-sm text-neutral-500`.
- Gap label-toggle: `justify-between` em flex container.

### Acessibilidade

- `role="switch"`.
- `aria-checked="true/false"`.
- `aria-label` ou `aria-labelledby` obrigatorio.
- Keyboard: `Space` toggle. `Enter` toggle (recomendado).

---

## 11. Date Picker

Seletor de data unica.

### Estrutura

```
  Data da calibracao *
  ┌──────────────────────── 📅 ┐
  │ 12/04/2026                 │    <- input com icone de calendario
  └────────────────────────────┘

  --- aberto: ---

  ┌────────────────────────────┐
  │  ◀  Abril 2026          ▶ │    <- navegacao mes/ano
  ├────────────────────────────┤
  │ Dom Seg Ter Qua Qui Sex Sab│
  │             1   2   3   4  │
  │  5   6   7   8   9  10  11 │
  │ [12] 13  14  15  16  17  18│    <- dia selecionado
  │  19  20  21  22  23  24  25│
  │  26  27  28  29  30        │
  └────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Seletor de data com calendario dropdown |
| `inline` | Calendario sempre visivel (raro, formularios de agendamento) |

### Tamanhos

Mesmos do [Text Input](#5-text-input) para o trigger.

### Estados

| Estado | Visual |
|---|---|
| **default** | Input com icone de calendario |
| **open** | Calendario dropdown, `shadow-md`, `rounded-lg` |
| **date hover** | `bg-primary-50 rounded-full` |
| **date selected** | `bg-primary-600 text-white rounded-full` |
| **date today** | `border border-primary-300 rounded-full` (se nao selecionado) |
| **date disabled** | `text-neutral-300 cursor-not-allowed` |
| **error** | Borda vermelha no input (mesmo que Text Input) |

### Formato

- Exibicao: `DD/MM/AAAA` (padrao brasileiro).
- Valor interno: `YYYY-MM-DD` (ISO 8601).
- Placeholder: `dd/mm/aaaa`.

### Acessibilidade

- `role="dialog"` no calendario dropdown.
- `aria-label="Selecionar data"` no botao de calendario.
- Grid do calendario: `role="grid"`.
- Dias: `role="gridcell"`.
- Keyboard: `ArrowLeft/Right/Up/Down` navega dias. `PageUp/PageDown` navega meses. `Enter` seleciona. `Escape` fecha.

---

## 12. Date Range Picker

Seletor de intervalo de datas (inicio e fim).

### Estrutura

```
  Periodo de calibracoes
  ┌──────────────┐  ate  ┌──────────────┐  📅
  │ 01/03/2026   │       │ 31/03/2026   │
  └──────────────┘       └──────────────┘

  --- aberto: ---

  ┌──────────────────────────────────────────────────────────┐
  │  ◀  Marco 2026                    Abril 2026          ▶ │
  ├────────────────────────────┬─────────────────────────────┤
  │ Dom Seg Ter Qua Qui Sex Sab│ Dom Seg Ter Qua Qui Sex Sab│
  │  [1]  2   3   4   5   6   7│          1   2   3   4     │
  │   8 ░░░░░░░░░░░░░░░░ 14   │  5   6   7   8   9  10  11 │
  │  15  16  17  18  19  20  21│ 12  13  14  15  16  17  18 │
  │  22  23  24  25  26  27  28│ 19  20  21  22  23  24  25 │
  │  29  30 [31]               │ 26  27  28  29  30         │
  └────────────────────────────┴─────────────────────────────┘
  ░ = dias dentro do range (bg-primary-50)
  [ ] = dia inicio/fim (bg-primary-600 text-white)
```

### Presets rapidos

```
  [Hoje] [Ultimos 7 dias] [Este mes] [Ultimo mes] [Personalizado]
```

- Presets como botoes `ghost` (sm) acima do calendario.
- Preset ativo: `text-primary-600 font-medium`.

### Acessibilidade

- Dois inputs: `aria-label="Data inicio"` e `aria-label="Data fim"`.
- Calendario: mesmo do Date Picker com navegacao de range.
- Keyboard: `Tab` entre campos de inicio e fim.

---

## 13. File Upload

Area de upload de arquivos com drag-and-drop.

### Estrutura (area)

```
  Certificado do padrao de referencia
  ┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
  │                                     │
  │       📁 Arraste o arquivo aqui     │
  │         ou clique para selecionar   │
  │                                     │
  │       PDF, PNG, JPG ate 10MB        │    <- restricoes
  │                                     │
  └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘

  --- com arquivo selecionado: ---

  ┌──────────────────────────────────────┐
  │  📄 certificado-padrao.pdf   2.3 MB │
  │  ████████████████████░░░░  78%      │   <- progress bar
  │                              [✕]    │   <- remover
  └──────────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `dropzone` | Area grande de drag-and-drop (padrao) |
| `compact` | Botao de upload simples (quando espaco e limitado) |
| `avatar` | Upload de foto com preview circular |

### Estados

| Estado | Visual |
|---|---|
| **default** | Borda dashed `border-neutral-300`, icone e texto `text-neutral-400` |
| **hover** | `border-primary-300 bg-primary-50` |
| **drag-over** | `border-primary-500 bg-primary-50 border-2` |
| **uploading** | Progress bar dentro da area, botao de cancelar |
| **success** | Icone check verde, nome do arquivo, botao de remover |
| **error** | Borda `border-danger-500`, mensagem de erro (tipo invalido, tamanho excedido) |
| **disabled** | `opacity-50 cursor-not-allowed`, drag-and-drop desabilitado |

### Acessibilidade

- `role="button"` na area de drop (clicavel).
- `aria-label="Upload de arquivo"`.
- `<input type="file">` oculto visualmente mas acessivel por teclado.
- `aria-describedby` para restricoes (tipos aceitos, tamanho maximo).
- Progress: `role="progressbar"`, `aria-valuenow`, `aria-valuemin="0"`, `aria-valuemax="100"`.

---

## 14. Number Input

Campo de entrada numerica com suporte a unidade de medida (essencial para laboratorio de calibracao).

### Estrutura

```
  Leitura do instrumento *
  ┌──────────────────────┬──────┐
  │ 25.347               │  mm  │    <- input + unidade fixa
  └──────────────────────┴──────┘

  --- com controles: ---

  Temperatura ambiente *
  ┌───┬──────────────────┬───┬──────┐
  │ - │ 23.0             │ + │  °C  │    <- steppers + unidade
  └───┴──────────────────┴───┴──────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Numero sem unidade |
| `with-unit` | Numero com unidade de medida fixa a direita |
| `with-stepper` | Botoes +/- para incremento/decremento |
| `with-stepper-unit` | Stepper + unidade |

### Tamanhos

Mesmos do [Text Input](#5-text-input).

### Formatacao

- Separador decimal: **virgula** (padrao BR) na exibicao, **ponto** no valor interno.
- Alinhamento do texto: `text-right` (numeros sempre alinhados a direita).
- Font: `font-mono` (JetBrains Mono) para alinhamento de colunas numericas.

### Sufixo de unidade

- Background: `bg-neutral-50`.
- Texto: `text-neutral-500 text-sm`.
- Borda: `border-l border-neutral-300`.
- Padding: `px-3`.

### Steppers (+/-)

- Botoes: `ghost` icon-only, `w-8`.
- Icones: `minus` e `plus` (Heroicons mini).
- Long-press: incremento continuo (Alpine.js `x-on:mousedown` com interval).

### Validacao

- `min`, `max`, `step` como atributos HTML.
- Decimais: `step="0.001"` para medicoes de alta precisao.
- Erro se fora do range: "Valor deve estar entre {min} e {max}".

### Estados

Mesmos do [Text Input](#5-text-input).

### Acessibilidade

- `role="spinbutton"` quando com stepper.
- `aria-valuenow`, `aria-valuemin`, `aria-valuemax`.
- `aria-label` inclui a unidade: "Leitura em milimetros".
- Keyboard: `ArrowUp`/`ArrowDown` incrementa/decrementa. `Home`/`End` para min/max.

---

# Dados

---

## 15. Table

Componente de tabela de dados com ordenacao, filtros, paginacao e selecao.

### Estrutura

```
┌──────────────────────────────────────────────────────────────────┐
│  ┌─ Filters ───────────────────────────────────────────────────┐ │
│  │ Status ▼  |  Data ▼  |  🔍 Buscar...                       │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ☐ │ # │ Instrumento ↕     │ Status     │ Data       │ Acoes    │
│  ──┼───┼───────────────────┼────────────┼────────────┼──────── │
│  ☐ │ 1 │ Paquimetro 150mm  │ ● Aprovado │ 12/04/2026 │  ⋮      │
│  ☐ │ 2 │ Micrometro 0-25mm │ ○ Pendente │ 11/04/2026 │  ⋮      │
│  ☑ │ 3 │ Relogio comp.     │ ● Aprovado │ 10/04/2026 │  ⋮      │
│  ☐ │ 4 │ Manometro digital │ ✕ Reprov.  │ 09/04/2026 │  ⋮      │
│                                                                  │
│  Mostrando 1-4 de 47           < 1 [2] 3 4 ... 12 >             │
└──────────────────────────────────────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Tabela padrao com bordas entre linhas |
| `striped` | Linhas alternadas `bg-neutral-50` |
| `compact` | Padding reduzido (`px-2 py-1.5`) para tabelas densas |
| `borderless` | Sem bordas entre linhas (para listas simples) |

### Cabecalho

- Background: `bg-neutral-50`.
- Texto: `text-xs font-medium text-neutral-500 uppercase tracking-wider`.
- Borda inferior: `border-b-2 border-neutral-200`.
- Sortable: icone `chevron-up-down` (neutro), `chevron-up` (asc), `chevron-down` (desc).

### Celulas

- Padding: `px-3 py-3` (default), `px-2 py-1.5` (compact).
- Texto: `text-sm text-neutral-700`.
- Numeros: `font-mono text-right`.
- Borda: `border-b border-neutral-100`.

### Selecao

- Checkbox na primeira coluna.
- Header: checkbox "selecionar todos" (indeterminate quando parcial).
- Row selecionada: `bg-primary-50`.
- Selecao dispara Bulk Actions Bar.

### Coluna de acoes

- Ultima coluna, alinhada a direita.
- Botao `⋮` (dropdown menu) ou icones inline para 1-2 acoes.
- Sticky right em tabelas com scroll horizontal.

### Ordenacao

- Click no header alterna: neutro -> asc -> desc -> neutro.
- Indicador visual: icone chevron + `text-primary-600` no header ativo.
- Apenas uma coluna ordenada por vez.

### Filtros

- Acima da tabela, em linha.
- Select para filtros categoricos (Status, Tipo).
- Date Range para filtros de data.
- Text Input com icone de busca para busca textual.
- Chips de filtro ativo abaixo da barra: `[Status: Aprovado ✕] [Data: Marco 2026 ✕]`.

### Scroll horizontal

- `overflow-x-auto` no container da tabela.
- Primeira coluna (checkbox + ID) e ultima (acoes): `sticky` com `bg-white`.
- Sombra de scroll: gradiente sutil indicando mais conteudo.

### Responsive

- Mobile: tabela converte para layout de card (cada row vira um card).
- Tablet: scroll horizontal com colunas sticky.
- Desktop: tabela completa.

### Estados

| Estado | Visual |
|---|---|
| **loading** | Skeleton loader (ver componente #21) |
| **empty** | Empty state (ver componente #20) |
| **error** | Alert banner inline (ver componente #27) |
| **row hover** | `bg-neutral-50` |
| **row selected** | `bg-primary-50` |

### Acessibilidade

- `<table>` com `role="table"` (implicito).
- `<thead>` / `<tbody>` semanticos.
- Headers: `scope="col"`.
- Sortable headers: `aria-sort="ascending/descending/none"`.
- Checkbox "selecionar todos": `aria-label="Selecionar todas as linhas"`.
- Navegacao por teclado: `Tab` entre celulas interativas (checkboxes, botoes).
- `aria-rowcount` e `aria-rowindex` para tabelas paginadas.

---

## 16. Card

Container visual para agrupar informacoes relacionadas.

### Variantes

| Variante | Uso |
|---|---|
| `info` | Exibicao de dados (detalhes do instrumento, informacoes do cliente) |
| `stat` | KPI ou metrica (numero grande + label + tendencia) |
| `action` | Card clicavel que dispara navegacao ou acao |

### Estrutura (info)

```
┌──────────────────────────────────┐
│  Dados do Instrumento            │   <- titulo (text-lg font-semibold)
│  ──────────────────────────────  │   <- divider
│  Tipo:        Paquimetro         │
│  Fabricante:  Mitutoyo           │
│  Modelo:      500-196-30         │
│  Faixa:       0 - 150 mm        │
│  Resolucao:   0,01 mm           │
└──────────────────────────────────┘
```

### Estrutura (stat)

```
┌──────────────────────────────────┐
│  Calibracoes este mes            │   <- label (text-sm text-neutral-500)
│                                  │
│  147                             │   <- numero (text-3xl font-bold)
│  ↑ 12% vs mes anterior          │   <- trend (text-sm text-success-600)
└──────────────────────────────────┘
```

### Estrutura (action)

```
┌──────────────────────────────────┐
│  📊  Relatorio Mensal            │   <- icone + titulo
│  Gerar relatorio de calibracoes  │   <- descricao
│  do periodo selecionado.         │
│                         →        │   <- indicador de acao
└──────────────────────────────────┘
```

### Visual

- Background: `bg-white`.
- Borda: `border border-neutral-200`.
- Radius: `rounded-lg`.
- Sombra: `shadow-sm`.
- Padding: `p-4` (mobile), `p-6` (desktop).

### Estados

| Estado | Visual |
|---|---|
| **default** | Estilo base |
| **hover** (action card) | `shadow-md border-primary-200`, cursor pointer |
| **loading** | Conteudo substituido por skeleton |

### Tamanhos

| Tamanho | Padding | Uso |
|---|---|---|
| `sm` | `p-3` | Cards em grid compacto (4 colunas) |
| `md` (padrao) | `p-4` mobile, `p-6` desktop | Cards padrao |
| `lg` | `p-6` mobile, `p-8` desktop | Card de destaque (hero stat) |

### Acessibilidade

- Action card que navega: `<a href="...">` semantico. Action card que executa acao: `<button>`. `Enter` ativa em ambos.
- Stat card: valores importantes com `aria-label` descritivo (ex: "147 calibracoes este mes, 12 por cento acima do mes anterior").

---

## 17. Badge / Tag

Indicador visual compacto de status, categoria ou quantidade.

### Variantes de status

| Status | Background | Texto | Dot | Uso |
|---|---|---|---|---|
| `success` | `bg-success-50` | `text-success-700` | `● bg-success-500` | Aprovado, Calibrado, Ativo, Conforme |
| `warning` | `bg-warning-50` | `text-warning-700` | `● bg-warning-500` | Pendente, Vencendo, Em analise |
| `danger` | `bg-danger-50` | `text-danger-700` | `● bg-danger-500` | Reprovado, Vencido, Inativo, Nao conforme |
| `info` | `bg-info-50` | `text-info-700` | `● bg-info-500` | Em andamento, Informativo |
| `neutral` | `bg-neutral-100` | `text-neutral-700` | `● bg-neutral-400` | Rascunho, Nao iniciado |

### Estrutura

```
  ● Aprovado        <- com dot
  Dimensional       <- sem dot (tag de categoria)
  3                 <- badge numerico (notificacao)
```

### Tamanhos

| Tamanho | Padding | Font | Dot |
|---|---|---|---|
| `sm` | `px-2 py-0.5` | `text-xs` | `w-1.5 h-1.5` |
| `md` (padrao) | `px-2.5 py-1` | `text-xs font-medium` | `w-2 h-2` |
| `lg` | `px-3 py-1` | `text-sm font-medium` | `w-2 h-2` |

### Formas

| Forma | Radius | Uso |
|---|---|---|
| `rounded` | `rounded-md` | Status badges, tags de categoria |
| `pill` | `rounded-full` | Contadores, badges numericos |

### Removivel

```
  [Dimensional ✕]     <- tag com botao de remover
```

- Botao `✕`: `w-4 h-4`, hover `bg-black/10 rounded-full`.
- Usado em filtros ativos e selecoes multi.

### Acessibilidade

- Texto descritivo suficiente (nao depender so da cor).
- Dot: `aria-hidden="true"` (decorativo; o texto ja informa o status).
- Badge numerico: `aria-label="3 notificacoes nao lidas"`.
- Removivel: botao `✕` com `aria-label="Remover filtro Dimensional"`.

---

## 18. Avatar

Representacao visual de usuario ou tenant.

### Variantes

| Variante | Conteudo | Uso |
|---|---|---|
| `image` | Foto do usuario | Quando usuario tem foto cadastrada |
| `initials` | Iniciais (2 letras) | Quando nao ha foto |
| `icon` | Icone generico (user/building) | Fallback final |

### Estrutura

```
  ┌────┐   ┌────┐   ┌────┐
  │ 📷 │   │ MF │   │ 👤 │
  └────┘   └────┘   └────┘
  image    initials  icon
```

### Tamanhos

| Tamanho | Dimensao | Font (iniciais) | Uso |
|---|---|---|---|
| `xs` | `w-6 h-6` | `text-xs` | Inline em listas compactas |
| `sm` | `w-8 h-8` | `text-xs` | Tabelas, comentarios |
| `md` (padrao) | `w-10 h-10` | `text-sm` | Header, cards |
| `lg` | `w-12 h-12` | `text-base` | Perfil, detalhes |
| `xl` | `w-16 h-16` | `text-lg` | Pagina de perfil, tenant |

### Visual

- Formato: `rounded-full`.
- Iniciais: `bg-primary-100 text-primary-700 font-medium`.
- Icone fallback: `bg-neutral-100 text-neutral-400`.
- Borda: `ring-2 ring-white` (quando sobreposto em grupo).

### Avatar group (stack)

```
  ┌──┐
  │  │┌──┐
  │  ││  │┌──┐
  └──┘│  ││  │┌──────┐
      └──┘│  ││ +3   │
           └──┘└──────┘
```

- Sobreposicao: `-space-x-2`.
- Contador de overflow: badge `pill` com `bg-neutral-100 text-neutral-600`.

### Acessibilidade

- `alt` descritivo na imagem: "Foto de Marcelo Ferreira".
- Iniciais: `aria-label="Marcelo Ferreira"`.
- Icone fallback: `aria-label="Usuario"` ou `aria-label="Empresa"`.

---

## 19. Stat / KPI

Exibicao de metrica numerica com contexto.

### Estrutura

```
┌──────────────────────────────────┐
│  Calibracoes concluidas          │   <- label
│                                  │
│  1.247                           │   <- valor (text-3xl font-bold font-mono)
│  ↑ 12,3% vs mes anterior        │   <- trend
└──────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Numero + label + trend |
| `with-icon` | Icone decorativo a esquerda do numero |
| `with-chart` | Mini sparkline ou barra a direita |
| `compact` | Label e numero na mesma linha (para tabelas/listas) |

### Trend

| Direcao | Icone | Cor |
|---|---|---|
| Positiva (bom) | `↑` arrow-up | `text-success-600` |
| Negativa (ruim) | `↓` arrow-down | `text-danger-600` |
| Neutra | `→` arrow-right | `text-neutral-500` |
| Positiva (ruim, ex: reclamacoes) | `↑` arrow-up | `text-danger-600` |
| Negativa (bom, ex: defeitos) | `↓` arrow-down | `text-success-600` |

> A cor do trend depende do contexto de negocio, nao so da direcao. O implementer deve receber a semantica (bom/ruim) junto com o valor.

### Formatacao de numeros

- Inteiros: separador de milhar com ponto (1.247).
- Decimais: virgula (12,3%).
- Moeda: R$ 15.420,00.
- Font: `font-mono` para alinhamento.

### Tamanhos

| Tamanho | Numero | Label |
|---|---|---|
| `sm` | `text-xl font-bold` | `text-xs` |
| `md` (padrao) | `text-3xl font-bold` | `text-sm` |
| `lg` | `text-4xl font-bold` | `text-base` |

### Acessibilidade

- `aria-label` descritivo completo: "1247 calibracoes concluidas, 12,3 por cento acima do mes anterior".
- Trend: `aria-hidden="true"` no icone (texto ja descreve).

---

## 20. Empty State

Exibido quando uma area/lista/tabela nao tem dados.

### Estrutura

```
┌──────────────────────────────────────┐
│                                      │
│            📋                        │   <- icone (w-12 h-12, text-neutral-300)
│                                      │
│     Nenhum certificado encontrado    │   <- titulo (text-lg font-semibold)
│                                      │
│     Crie seu primeiro certificado    │   <- descricao (text-sm text-neutral-500)
│     para comecar a usar o sistema.   │
│                                      │
│        [+ Novo Certificado]          │   <- CTA (button primary, sm ou md)
│                                      │
└──────────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Icone + titulo + descricao + CTA |
| `search` | Resultado de busca vazio — icone de busca, "Nenhum resultado para '{termo}'" |
| `filter` | Filtro sem resultados — icone de filtro, "Nenhum item corresponde aos filtros", link "Limpar filtros" |
| `error` | Erro ao carregar — icone de erro, mensagem, botao "Tentar novamente" |
| `permission` | Sem permissao — icone de cadeado, "Voce nao tem acesso a este recurso" |

### Tamanhos

| Tamanho | Icone | Titulo | Padding |
|---|---|---|---|
| `sm` | `w-8 h-8` | `text-base` | `py-8` |
| `md` (padrao) | `w-12 h-12` | `text-lg` | `py-12` |
| `lg` | `w-16 h-16` | `text-xl` | `py-16` |

### Acessibilidade

- Container: `role="status"`.
- Icone: `aria-hidden="true"`.
- CTA acessivel por teclado.

---

## 21. Skeleton Loader

Placeholder animado exibido enquanto dados estao carregando.

### Estrutura (tabela)

```
┌──────────────────────────────────────────────────┐
│  ░░░░░ │ ░░░░░░░░░░░ │ ░░░░░░ │ ░░░░░░░ │ ░░░ │
│  ░░░░░ │ ░░░░░░░░░   │ ░░░░░░ │ ░░░░░   │ ░░░ │
│  ░░░░░ │ ░░░░░░░░░░░ │ ░░░░░░ │ ░░░░░░░ │ ░░░ │
│  ░░░░░ │ ░░░░░░░░    │ ░░░░░░ │ ░░░░░░  │ ░░░ │
│  ░░░░░ │ ░░░░░░░░░░  │ ░░░░░░ │ ░░░░░   │ ░░░ │
└──────────────────────────────────────────────────┘
```

### Estrutura (card)

```
┌──────────────────────────────────┐
│  ░░░░░░░░░░░░░                   │   <- titulo
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░   │   <- corpo linha 1
│  ░░░░░░░░░░░░░░░░░░░            │   <- corpo linha 2
│                                  │
│  ░░░░░░░░                        │   <- badge/meta
└──────────────────────────────────┘
```

### Estrutura (stat)

```
┌──────────────────────┐
│  ░░░░░░░░░           │   <- label
│  ░░░░░░░             │   <- numero
│  ░░░░░               │   <- trend
└──────────────────────┘
```

### Visual

- Elemento base: `bg-neutral-200 rounded`.
- Animacao: `animate-pulse` (Tailwind built-in).
- Altura do elemento: proporcional ao conteudo que sera carregado.
- Larguras variadas para parecer natural (60%, 80%, 45%, etc.).

### Regras

- Skeleton deve espelhar a estrutura do conteudo final.
- Tabelas: 5 rows de skeleton e suficiente (mesmo que a pagina tenha 25).
- Tempo maximo de skeleton: 10 segundos. Apos isso, exibir empty state com "Tentar novamente".
- Nao usar skeleton para acoes (botoes, links). Desabilitar o botao diretamente.

### Acessibilidade

- Container: `role="status"`, `aria-label="Carregando dados"`.
- Elementos individuais: `aria-hidden="true"`.

---

# Navegacao

---

## 22. Breadcrumb

Indicador de localizacao na hierarquia de paginas.

### Estrutura

```
  Home  >  Calibracoes  >  Certificado #1247
  ^link    ^link            ^pagina atual (nao clicavel)
```

### Visual

- Container: flex, `gap-2`, `text-sm`.
- Links: `text-neutral-500 hover:text-primary-600`.
- Separador: `>` (chevron-right) em `text-neutral-400`, `w-4 h-4`.
- Pagina atual: `text-neutral-900 font-medium`.
- Truncamento: em mobile, mostrar apenas ultimo nivel + "..." para niveis anteriores.

### Tamanhos

| Tamanho | Font |
|---|---|
| `sm` | `text-xs` |
| `md` (padrao) | `text-sm` |

### Acessibilidade

- `<nav aria-label="Breadcrumb">`.
- `<ol>` semantico com `<li>` por nivel.
- Pagina atual: `aria-current="page"`.
- Separadores: `aria-hidden="true"`.

---

## 23. Tabs

Navegacao horizontal entre secoes de conteudo na mesma pagina.

### Estrutura

```
┌────────────┬────────────┬────────────┬────────────┐
│  Detalhes  │ Historico  │ Documentos │ Auditoria  │
│  ═══════   │            │            │            │
└────────────┴────────────┴────────────┴────────────┘
  (conteudo da aba "Detalhes")
```

### Visual

- Container: `border-b border-neutral-200`.
- Tab inativa: `text-neutral-500 hover:text-neutral-700 hover:border-neutral-300`.
- Tab ativa: `text-primary-600 border-b-2 border-primary-600 font-medium`.
- Padding: `px-4 py-3`.
- Gap entre tabs: `gap-0` (tabs sao adjacentes).

### Variantes

| Variante | Uso |
|---|---|
| `underline` (padrao) | Borda inferior indica tab ativa |
| `pill` | Background `bg-primary-50 rounded-md` indica tab ativa |

### Tamanhos

| Tamanho | Font | Padding |
|---|---|---|
| `sm` | `text-sm` | `px-3 py-2` |
| `md` (padrao) | `text-sm` | `px-4 py-3` |

### Com badge/counter

```
  Detalhes   Historico (24)   Documentos (3)
```

- Counter: badge `pill` `neutral` apos o texto, `text-xs`.

### Estados

| Estado | Visual |
|---|---|
| **default** | `text-neutral-500` |
| **hover** | `text-neutral-700` |
| **active** | `text-primary-600 border-b-2 border-primary-600` |
| **disabled** | `text-neutral-300 cursor-not-allowed` |

### Acessibilidade

- Container: `role="tablist"`.
- Cada tab: `role="tab"`, `aria-selected="true/false"`, `aria-controls="panel-id"`.
- Painel: `role="tabpanel"`, `aria-labelledby="tab-id"`.
- Keyboard: `ArrowLeft`/`ArrowRight` navega entre tabs. `Home`/`End` primeiro/ultimo. `Enter`/`Space` ativa (se nao ativar automaticamente).

---

## 24. Pagination

Navegacao entre paginas de resultados.

### Estrutura

```
  Mostrando 1-25 de 247 resultados     < 1 [2] 3 4 ... 10 >
  ^info                                 ^controles
```

### Visual

- Info: `text-sm text-neutral-500`.
- Botao pagina: `w-8 h-8 text-sm text-neutral-700 hover:bg-neutral-50 rounded-md`.
- Pagina ativa: `bg-primary-600 text-white rounded-md`.
- Setas prev/next: `w-8 h-8`, icone `chevron-left`/`chevron-right`.
- Setas desabilitadas (primeira/ultima pagina): `text-neutral-300 cursor-not-allowed`.
- Ellipsis: `...` em `text-neutral-400`.

### Variantes

| Variante | Uso |
|---|---|
| `default` | Info + controles completos |
| `simple` | Apenas `< Anterior | Proximo >` |
| `compact` | `< 2/10 >` (mobile) |

### Tamanhos

| Tamanho | Botao | Font info |
|---|---|---|
| `sm` | `w-7 h-7 text-xs` | `text-xs` |
| `md` (padrao) | `w-8 h-8 text-sm` | `text-sm` |

### Regras de truncamento

- Ate 7 paginas: mostrar todas.
- 8+ paginas: `1 2 3 ... 8 9 10` (mostrar 3 em volta da atual + primeira + ultima).

### Acessibilidade

- Container: `<nav aria-label="Paginacao">`.
- Pagina ativa: `aria-current="page"`.
- Botoes: `aria-label="Pagina 3"`, `aria-label="Pagina anterior"`, `aria-label="Proxima pagina"`.
- Disabled: `aria-disabled="true"`.

---

## 25. Step Indicator

Indicador de progresso em fluxos multi-etapa (wizard).

### Estrutura

```
  ✓ Dados basicos  ──  ● Medicoes  ──  ○ Revisao  ──  ○ Aprovacao
     concluido         atual            pendente        pendente
```

### Visual

- Step concluido: circulo `bg-success-600 text-white` com icone check, linha `bg-success-600`.
- Step atual: circulo `bg-primary-600 text-white` com numero ou dot, texto `font-medium`.
- Step pendente: circulo `border-2 border-neutral-300 text-neutral-400`, linha `bg-neutral-200`.
- Linha conectora: `h-0.5` entre circulos.
- Texto: `text-sm`, abaixo do circulo.

### Variantes

| Variante | Uso |
|---|---|
| `horizontal` (padrao) | Wizard horizontal (desktop) |
| `vertical` | Wizard vertical (mobile, fluxos com muitas etapas) |
| `compact` | Apenas circulos + linha, sem texto (mobile) |

### Tamanhos

| Tamanho | Circulo | Texto |
|---|---|---|
| `sm` | `w-6 h-6` | `text-xs` |
| `md` (padrao) | `w-8 h-8` | `text-sm` |
| `lg` | `w-10 h-10` | `text-sm` |

### Estados do step

| Estado | Visual |
|---|---|
| **completed** | Check verde, linha verde |
| **current** | Circulo primario, pulse sutil (opcional) |
| **pending** | Circulo outline neutro |
| **error** | Circulo vermelho com icone `!`, linha vermelha |
| **disabled** | Opacity reduzida |

### Acessibilidade

- Container: `role="navigation"`, `aria-label="Progresso do fluxo"`.
- Cada step: `aria-current="step"` no step atual.
- Steps concluidos: `aria-label="Etapa 1: Dados basicos - concluida"`.
- Linhas conectoras: `aria-hidden="true"`.

---

# Feedback

---

## 26. Toast / Notification

Mensagem temporaria de feedback apos acao do usuario.

### Estrutura

```
┌──────────────────────────────────────────┐
│  ✓  Certificado salvo com sucesso    [✕] │
└──────────────────────────────────────────┘
```

### Variantes

| Variante | Icone | Background | Borda esquerda | Uso |
|---|---|---|---|---|
| `success` | `check-circle` | `bg-white` | `border-l-4 border-success-500` | Acao concluida |
| `error` | `x-circle` | `bg-white` | `border-l-4 border-danger-500` | Acao falhou |
| `warning` | `exclamation-triangle` | `bg-white` | `border-l-4 border-warning-500` | Atencao necessaria |
| `info` | `information-circle` | `bg-white` | `border-l-4 border-info-500` | Informacao neutra |

### Posicao

- Desktop: `top-4 right-4`, stack vertical com `gap-3`.
- Mobile: `top-4 inset-x-4` (full-width).
- `fixed` ou `absolute` conforme o contexto.

### Comportamento

- Aparece com animacao slide-in da direita (desktop) ou slide-down (mobile).
- Auto-dismiss: 5 segundos (success/info), nao auto-dismiss (error/warning).
- Botao fechar `✕` sempre presente.
- Hover pausa o timer de auto-dismiss.
- Maximo 3 toasts visiveis simultaneamente. Novos empurram os antigos.

### Tamanhos

| Tamanho | Padding | Font | Max-width |
|---|---|---|---|
| `sm` | `px-3 py-2` | `text-sm` | 320px |
| `md` (padrao) | `px-4 py-3` | `text-sm` | 400px |

### Com acao

```
┌──────────────────────────────────────────────────┐
│  ✓  Certificado excluido.  [Desfazer]        [✕] │
└──────────────────────────────────────────────────┘
```

- Link de acao: `text-primary-600 font-medium hover:underline`.

### Acessibilidade

- Container stack: `aria-live="polite"` (success/info) ou `aria-live="assertive"` (error/warning).
- `role="alert"` para error/warning.
- `role="status"` para success/info.
- Botao fechar: `aria-label="Fechar notificacao"`.

### Implementacao Livewire

- Evento `dispatch('toast', { type: 'success', message: '...' })`.
- Componente global `<x-toast-stack />` no layout.
- Alpine.js gerencia stack e timers.

---

## 27. Alert Banner

Mensagem persistente (nao auto-dismiss) para informar status ou avisos.

### Estrutura (inline)

```
┌──────────────────────────────────────────────────────┐
│  ⚠ Este certificado esta vencido desde 01/03/2026.  │
│     Agende uma recalibracao.  [Agendar]              │
└──────────────────────────────────────────────────────┘
```

### Estrutura (page-level)

```
════════════════════════════════════════════════════════
  ⚠ Manutencao programada: o sistema ficara indisponivel
  dia 15/04 das 02h as 04h.                        [✕]
════════════════════════════════════════════════════════
```

### Variantes

| Variante | Icone | Background | Borda | Uso |
|---|---|---|---|---|
| `success` | `check-circle` | `bg-success-50` | `border border-success-200` | Operacao concluida, resultado positivo |
| `warning` | `exclamation-triangle` | `bg-warning-50` | `border border-warning-200` | Atencao, vencimento proximo |
| `danger` | `x-circle` | `bg-danger-50` | `border border-danger-200` | Erro, vencido, bloqueio |
| `info` | `information-circle` | `bg-info-50` | `border border-info-200` | Informacao, dica, status |

### Posicao

| Tipo | Posicao |
|---|---|
| `inline` | Dentro do conteudo, acima da area relevante |
| `page-level` | Topo da pagina, abaixo do header, full-width |

### Tamanhos

| Tamanho | Padding | Font |
|---|---|---|
| `sm` | `px-3 py-2` | `text-sm` |
| `md` (padrao) | `px-4 py-3` | `text-sm` |

### Estados

| Estado | Visual |
|---|---|
| **visible** | Exibido normalmente |
| **dismissible** | Botao `✕` presente, pode ser fechado |
| **persistent** | Sem botao fechar (erros criticos, bloqueios) |

### Acessibilidade

- `role="alert"` para danger/warning.
- `role="status"` para success/info.
- Icone: `aria-hidden="true"`.
- Botao fechar: `aria-label="Fechar alerta"`.
- Link de acao: texto descritivo, nao "clique aqui".

---

## 28. Modal / Dialog

Overlay que exige atencao do usuario antes de prosseguir.

### Estrutura

```
┌──────────────────────────────────────────┐
│                                          │
│   ┌──────────────────────────────────┐   │
│   │  Confirmar exclusao          [✕] │   │   <- header
│   │  ────────────────────────────── │   │   <- divider
│   │                                  │   │
│   │  Tem certeza que deseja excluir  │   │   <- body
│   │  o instrumento "Paquimetro       │   │
│   │  150mm"? Esta acao nao pode      │   │
│   │  ser desfeita.                   │   │
│   │                                  │   │
│   │  ──────────────────────────────  │   │   <- divider
│   │           [Cancelar] [Excluir]   │   │   <- footer
│   └──────────────────────────────────┘   │
│                                          │
└──────────────────────────────────────────┘
  ^overlay (bg-black/50)
```

### Variantes

| Variante | Uso |
|---|---|
| `confirmation` | Confirmar acao (especialmente destrutiva). Corpo curto, 2 botoes. |
| `form` | Formulario dentro do modal. Inputs, validacao, salvar/cancelar. |
| `info` | Exibicao de informacao. Corpo longo, botao "Fechar". |
| `danger` | Confirmacao destrutiva. Icone de aviso, botao `danger`. |

### Tamanhos

| Tamanho | Max-width | Uso |
|---|---|---|
| `sm` | `max-w-sm` (384px) | Confirmacoes simples |
| `md` (padrao) | `max-w-lg` (512px) | Formularios curtos, informacoes |
| `lg` | `max-w-2xl` (672px) | Formularios longos |
| `xl` | `max-w-4xl` (896px) | Tabelas, conteudo complexo |
| `full` | `max-w-full mx-4` | Uso raro — previsoes, graficos grandes |

### Visual

- Overlay: `bg-black/50`, `backdrop-blur-sm` (opcional).
- Container: `bg-white rounded-lg shadow-lg`.
- Header: `px-6 py-4`, titulo `text-lg font-semibold`.
- Body: `px-6 py-4`.
- Footer: `px-6 py-4 bg-neutral-50 rounded-b-lg`, botoes alinhados a direita com `gap-3`.
- Animacao: fade-in overlay + scale-up container.

### Danger variant

```
┌──────────────────────────────────┐
│  ⚠ Excluir instrumento       [✕]│
│  ────────────────────────────── │
│                                  │
│  [!] Esta acao nao pode ser      │
│  desfeita. Todos os historicos   │
│  de calibracao serao perdidos.   │
│                                  │
│  Digite "EXCLUIR" para confirmar:│
│  ┌──────────────────────────┐    │
│  │                          │    │
│  └──────────────────────────┘    │
│                                  │
│  ────────────────────────────── │
│          [Cancelar]  [Excluir]   │
└──────────────────────────────────┘
```

- Confirmacao por texto para acoes criticas (excluir tenant, revogar certificado).
- Botao `danger` desabilitado ate texto correto ser digitado.

### Estados

| Estado | Visual |
|---|---|
| **closed** | Modal oculto, overlay oculto |
| **open** | Overlay + modal visiveis, animacao de entrada |
| **loading** | Body com skeleton ou spinner, botoes desabilitados |

### Acessibilidade

- `role="dialog"`, `aria-modal="true"`.
- `aria-labelledby` apontando para o titulo.
- `aria-describedby` apontando para o corpo (quando relevante).
- **Focus trap:** foco nao sai do modal enquanto aberto. `Tab` cicla entre elementos interativos.
- Ao abrir: foco vai para o primeiro elemento interativo (ou o botao primario em confirmacao).
- Ao fechar: foco retorna ao elemento que abriu o modal.
- `Escape` fecha o modal (exceto danger com confirmacao por texto).
- Click no overlay fecha (exceto modais de formulario com dados nao salvos).

### Implementacao Livewire

```
<!-- Trigger -->
<button wire:click="$dispatch('open-modal', { id: 'confirm-delete' })">
    Excluir
</button>

<!-- Modal component -->
<x-modal id="confirm-delete" max-width="sm">
    ...
</x-modal>
```

---

## 29. Progress Bar

Indicador visual de progresso de uma operacao.

### Estrutura

```
  Gerando certificado...               78%
  ████████████████████░░░░░░░░░░░░░░░░░░░
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Progresso determinado (porcentagem conhecida) |
| `indeterminate` | Progresso indeterminado (animacao de vai-e-vem) |
| `segmented` | Etapas discretas (3 de 5 concluidas) |

### Visual

- Track: `bg-neutral-200 rounded-full`.
- Fill: `bg-primary-600 rounded-full`, com transicao `transition-all duration-300`.
- Indeterminate: fill com `animate-indeterminate` (translacao horizontal em loop).

### Tamanhos

| Tamanho | Altura | Uso |
|---|---|---|
| `sm` | `h-1` (4px) | Barra sutil em headers, cards |
| `md` (padrao) | `h-2` (8px) | Upload, geracao de PDF |
| `lg` | `h-3` (12px) | Destaque (progresso de importacao) |

### Com label

```
  ┌──────────────────────────────────┐
  │  Importando dados... 147/500     │
  │  ████████████░░░░░░░░░  29%     │
  └──────────────────────────────────┘
```

- Label acima: `text-sm font-medium text-neutral-700`.
- Porcentagem a direita: `text-sm text-neutral-500`.

### Estados

| Estado | Visual |
|---|---|
| **in-progress** | Fill crescendo, cor `bg-primary-600` |
| **complete** | Fill 100%, cor muda para `bg-success-600` |
| **error** | Fill para no ponto de falha, cor muda para `bg-danger-600` |

### Acessibilidade

- `role="progressbar"`.
- `aria-valuenow="78"`, `aria-valuemin="0"`, `aria-valuemax="100"`.
- `aria-label="Gerando certificado, 78 por cento concluido"`.
- Indeterminate: `aria-valuenow` ausente, `aria-label="Processando..."`.

---

## 30. Spinner / Loading Indicator

Indicador de carregamento para operacoes curtas.

### Estrutura

```
    ◌       <- spinner (circulo animado)
  Carregando...  <- texto opcional
```

### Variantes

| Variante | Uso |
|---|---|
| `spinner` | Circulo animado (padrao) |
| `dots` | 3 pontos pulsando (inline, dentro de texto) |

### Visual (spinner)

- SVG circulo com `stroke` parcial.
- Animacao: `animate-spin` (Tailwind built-in).
- Cor default: `text-primary-600`.
- Cor sobre primary: `text-white`.
- Cor neutral: `text-neutral-400`.

### Tamanhos

| Tamanho | Dimensao | Uso |
|---|---|---|
| `xs` | `w-4 h-4` | Dentro de botoes (loading state) |
| `sm` | `w-5 h-5` | Inline com texto |
| `md` (padrao) | `w-8 h-8` | Centro de containers pequenos |
| `lg` | `w-12 h-12` | Centro de pagina ou secao |

### Contextos de uso

| Contexto | Tamanho | Posicao |
|---|---|---|
| Botao loading | `xs` | Substitui icone do botao |
| Inline loading (texto) | `sm` | Antes do texto |
| Card loading | `md` | Centro do card |
| Pagina loading | `lg` | Centro da area de conteudo |
| Overlay loading | `lg` | Centro com overlay semi-transparente |

### Acessibilidade

- `role="status"`.
- `aria-label="Carregando"`.
- Elemento visualmente oculto com texto para screen readers: `<span class="sr-only">Carregando...</span>`.

---

# Layout

---

## 31. Section Header

Cabecalho de secao com titulo e acoes opcionais.

### Estrutura

```
┌──────────────────────────────────────────────────────┐
│  Instrumentos cadastrados          [Filtrar] [+ Novo]│
│  ────────────────────────────────────────────────────│
└──────────────────────────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `page` | Titulo da pagina (h1). `text-2xl font-bold`. Com breadcrumb acima. |
| `section` | Titulo de secao dentro de pagina. `text-xl font-semibold`. |
| `card` | Titulo de card/painel. `text-lg font-semibold`. |

### Estrutura detalhada

```
  [Breadcrumb: Home > Modulo > Pagina]        <- so na variante `page`

  Titulo da secao                              <- titulo
  Descricao breve da secao (opcional)          <- subtitulo
                                    [Acao 1] [Acao 2]  <- botoes a direita
  ─────────────────────────────────────────── <- divider (opcional)
```

### Layout

- Flex container: `flex items-center justify-between`.
- Titulo a esquerda, acoes a direita.
- Mobile: acoes vao para baixo do titulo (stack vertical).

### Tamanhos

| Variante | Titulo | Subtitulo |
|---|---|---|
| `page` | `text-2xl font-bold text-neutral-900` | `text-sm text-neutral-500 mt-1` |
| `section` | `text-xl font-semibold text-neutral-900` | `text-sm text-neutral-500 mt-1` |
| `card` | `text-lg font-semibold text-neutral-800` | `text-sm text-neutral-500 mt-0.5` |

### Acessibilidade

- Titulo: heading semantico (`<h1>` para page, `<h2>` para section, `<h3>` para card).
- Hierarquia de headings respeitada (nao pular niveis).

---

## 32. Divider

Separador visual entre secoes ou elementos.

### Variantes

| Variante | Classe | Uso |
|---|---|---|
| `horizontal` | `border-t border-neutral-200` | Separacao entre secoes |
| `vertical` | `border-l border-neutral-200 h-full` | Separacao em flex row |
| `with-text` | Linha + texto centralizado + linha | "ou", "opcoes avancadas" |

### Estrutura (with-text)

```
  ─────────── ou ───────────
```

### Visual

- Cor: `border-neutral-200`.
- Margem vertical: `my-6` (padrao), `my-4` (compacto), `my-8` (generoso).
- Texto central: `text-sm text-neutral-400 bg-white px-3` (background para "cortar" a linha).

### Tamanhos

| Tamanho | Margem |
|---|---|
| `sm` | `my-4` |
| `md` (padrao) | `my-6` |
| `lg` | `my-8` |

### Acessibilidade

- `role="separator"` (implicito em `<hr>`).
- `aria-orientation="horizontal"` ou `"vertical"`.
- With-text: texto acessivel naturalmente.

---

## 33. Accordion / Collapsible

Secao que pode ser expandida/recolhida.

### Estrutura

```
┌──────────────────────────────────────────┐
│  ▼ Informacoes adicionais                │   <- header (expanded)
│  ────────────────────────────────────── │
│  Conteudo expandido aparece aqui.        │
│  Pode conter qualquer tipo de conteudo:  │
│  texto, formularios, tabelas, etc.       │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│  ▶ Historico de calibracoes              │   <- header (collapsed)
└──────────────────────────────────────────┘
```

### Variantes

| Variante | Uso |
|---|---|
| `default` | Accordion individual (um item, toggle independente) |
| `group` | Grupo de accordions (apenas um aberto por vez) |
| `borderless` | Sem borda ao redor (usado dentro de cards) |

### Visual

- Header: `flex items-center justify-between`, `px-4 py-3`.
- Texto header: `text-sm font-medium text-neutral-700`.
- Icone: `chevron-down` (expanded, rotacao 0), `chevron-right` (collapsed).
- Rotacao do icone: `transition-transform duration-200`.
- Body: `px-4 pb-4`, com animacao de altura `transition-all duration-200`.
- Borda: `border border-neutral-200 rounded-lg`.
- Hover: header `bg-neutral-50`.

### Tamanhos

| Tamanho | Header padding | Font |
|---|---|---|
| `sm` | `px-3 py-2` | `text-sm` |
| `md` (padrao) | `px-4 py-3` | `text-sm` |
| `lg` | `px-6 py-4` | `text-base` |

### Estados

| Estado | Visual |
|---|---|
| **collapsed** | Apenas header visivel, icone chevron-right |
| **expanded** | Header + body visiveis, icone chevron-down |
| **hover** | Header `bg-neutral-50` |
| **focus** | `ring-2 ring-primary-500/20` ao redor do header |
| **disabled** | `opacity-50 cursor-not-allowed` |

### Acessibilidade

- Header: `<button>` com `aria-expanded="true/false"`.
- `aria-controls` apontando para o id do body.
- Body: `role="region"`, `aria-labelledby` apontando para o header.
- Group: apenas um `aria-expanded="true"` por vez.
- Keyboard: `Enter`/`Space` toggle. `Tab` navega entre headers.

### Implementacao Alpine.js

```
<div x-data="{ open: false }">
    <button @click="open = !open" :aria-expanded="open">
        <span>Titulo</span>
        <x-heroicon-s-chevron-down class="transition-transform"
            :class="{ 'rotate-180': open }" />
    </button>
    <div x-show="open" x-collapse>
        Conteudo
    </div>
</div>
```

---

# Apendice A — Resumo de tokens por componente

Referencia rapida para o implementer.

| Componente | bg | text | border | radius | shadow |
|---|---|---|---|---|---|
| Button primary | `primary-600` | `white` | — | `rounded-md` | — |
| Button secondary | `white` | `neutral-700` | `neutral-300` | `rounded-md` | — |
| Button danger | `danger-600` | `white` | — | `rounded-md` | — |
| Input | `white` | `neutral-900` | `neutral-300` | `rounded` | — |
| Input focus | `white` | `neutral-900` | `primary-500` | `rounded` | `ring-primary-500/20` |
| Input error | `white` | `neutral-900` | `danger-500` | `rounded` | `ring-danger-500/20` |
| Card | `white` | — | `neutral-200` | `rounded-lg` | `shadow-sm` |
| Modal | `white` | — | — | `rounded-lg` | `shadow-lg` |
| Dropdown | `white` | — | `neutral-200` | `rounded-lg` | `shadow-md` |
| Badge success | `success-50` | `success-700` | — | `rounded-md` | — |
| Badge warning | `warning-50` | `warning-700` | — | `rounded-md` | — |
| Badge danger | `danger-50` | `danger-700` | — | `rounded-md` | — |
| Toast | `white` | `neutral-700` | left `*-500` | `rounded-lg` | `shadow-md` |
| Alert | `*-50` | `*-700` | `*-200` | `rounded-lg` | — |
| Table header | `neutral-50` | `neutral-500` | `neutral-200` | — | — |
| Table cell | — | `neutral-700` | `neutral-100` | — | — |
| Sidebar | `primary-900` | `primary-100` | — | — | — |

---

# Apendice B — Checklist de acessibilidade por componente

| Componente | ARIA obrigatorio | Keyboard |
|---|---|---|
| Button | `aria-label` (icon-only), `aria-disabled`, `aria-busy` | Enter, Space |
| Text Input | `aria-required`, `aria-invalid`, `aria-describedby` | Tab |
| Select/Combobox | `role="combobox"`, `aria-expanded`, `aria-controls` | Arrows, Enter, Escape |
| Checkbox | `aria-checked` (mixed para indeterminate) | Space |
| Radio | `role="radiogroup"`, `aria-checked` | Arrows |
| Toggle | `role="switch"`, `aria-checked` | Space, Enter |
| Dropdown Menu | `aria-haspopup`, `aria-expanded`, `role="menu"` | Arrows, Enter, Escape |
| Modal | `role="dialog"`, `aria-modal`, focus trap | Tab cycle, Escape |
| Tabs | `role="tablist"`, `role="tab"`, `aria-selected` | Arrows, Home, End |
| Accordion | `aria-expanded`, `aria-controls` | Enter, Space |
| Toast | `role="alert"` ou `role="status"`, `aria-live` | — |
| Breadcrumb | `aria-label="Breadcrumb"`, `aria-current="page"` | Tab |
| Pagination | `aria-current="page"`, `aria-label` | Tab |
| Progress | `role="progressbar"`, `aria-valuenow` | — |
| Table | `scope="col"`, `aria-sort`, `aria-rowcount` | Tab (interativos) |
| Date Picker | `role="dialog"`, `role="grid"` | Arrows, PageUp/Down, Escape |
| Step Indicator | `aria-current="step"` | — |
| Avatar | `alt` ou `aria-label` | — |
| Spinner | `role="status"`, `aria-label` | — |
| Empty State | `role="status"` | — |

---

# Apendice C — Mapeamento componente → Blade component

Convencao de nomenclatura para componentes Blade/Livewire.

| Componente pattern | Blade component |
|---|---|
| Button | `<x-btn>` |
| Link Button | `<x-link-btn>` |
| Dropdown Menu | `<x-dropdown>` + `<x-dropdown.item>` |
| Bulk Actions Bar | `<x-bulk-actions>` |
| Text Input | `<x-input>` |
| Textarea | `<x-textarea>` |
| Select / Combobox | `<x-select>` |
| Checkbox | `<x-checkbox>` |
| Radio Group | `<x-radio-group>` + `<x-radio>` |
| Toggle Switch | `<x-toggle>` |
| Date Picker | `<x-date-picker>` |
| Date Range Picker | `<x-date-range>` |
| File Upload | `<x-file-upload>` |
| Number Input | `<x-number-input>` |
| Table | `<x-table>` + `<x-table.head>` + `<x-table.row>` + `<x-table.cell>` |
| Card | `<x-card>` |
| Badge | `<x-badge>` |
| Avatar | `<x-avatar>` |
| Stat / KPI | `<x-stat>` |
| Empty State | `<x-empty-state>` |
| Skeleton | `<x-skeleton>` |
| Breadcrumb | `<x-breadcrumb>` + `<x-breadcrumb.item>` |
| Tabs | `<x-tabs>` + `<x-tab>` + `<x-tab-panel>` |
| Pagination | `<x-pagination>` |
| Step Indicator | `<x-steps>` + `<x-step>` |
| Toast | `<x-toast-stack>` (global) |
| Alert Banner | `<x-alert>` |
| Modal | `<x-modal>` |
| Progress Bar | `<x-progress>` |
| Spinner | `<x-spinner>` |
| Section Header | `<x-section-header>` |
| Divider | `<x-divider>` |
| Accordion | `<x-accordion>` + `<x-accordion.item>` |

---

> **Proximo documento:** `docs/design/interaction-patterns.md` (B.3) — loading states, empty states, error patterns, auto-save, confirmacao destrutiva.
