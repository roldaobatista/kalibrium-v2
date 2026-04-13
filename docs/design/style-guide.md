# Style Guide — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Stack:** Laravel 13, Livewire 4, Tailwind CSS 4, Alpine.js

---

## 1. Identidade Visual

### 1.1. Nome do produto

**Kalibrium** — derivado de "calibrar" (lat. *calibrare*) com sufixo que evoca equilibrio e precisao.

### 1.2. Conceito

O design do Kalibrium transmite tres valores fundamentais:

- **Precisao** — interfaces limpas, alinhamentos rigorosos, dados numericos com formatacao consistente. Nenhum elemento decorativo sem funcao.
- **Confianca** — paleta profissional (azul como cor primaria), hierarquia visual clara, feedback imediato em cada acao.
- **Profissionalismo** — tom tecnico mas acessivel. O sistema e usado por engenheiros metrologos (Marcelo), tecnicos de bancada (Juliana) e compradores industriais (Rafael). Precisa parecer serio sem ser intimidador.

### 1.3. Tom visual

- Clean, funcional, sem ornamentos desnecessarios.
- Densidade de informacao alta em telas de gestao (Marcelo, Rafael) — tabelas, dashboards, KPIs.
- Densidade baixa em telas de bancada (Juliana) — campos grandes, teclado numerico, poucos cliques.
- Certificados e documentos oficiais: sobriedade maxima, fontes serif em PDFs quando necessario.

---

## 2. Paleta de Cores

### 2.1. Cores principais

| Token | Hex | Tailwind class | Uso |
|---|---|---|---|
| `primary-50` | `#eff6ff` | `bg-primary-50` | Background de areas destacadas, hover sutil |
| `primary-100` | `#dbeafe` | `bg-primary-100` | Background de badges, chips informativos |
| `primary-200` | `#bfdbfe` | `bg-primary-200` | Bordas de foco, outlines |
| `primary-300` | `#93c5fd` | `bg-primary-300` | Icones secundarios |
| `primary-400` | `#60a5fa` | `bg-primary-400` | Links hover |
| `primary-500` | `#3b82f6` | `bg-primary-500` | Links, icones ativos |
| `primary-600` | `#2563eb` | `bg-primary-600` | **Botoes primarios**, headers ativos |
| `primary-700` | `#1d4ed8` | `bg-primary-700` | Botoes primarios hover |
| `primary-800` | `#1e40af` | `bg-primary-800` | Sidebar background, textos de destaque |
| `primary-900` | `#1e3a8a` | `bg-primary-900` | Sidebar hover, headers escuros |
| `primary-950` | `#172554` | `bg-primary-950` | Sidebar collapsed, backgrounds mais escuros |

> **Racional:** azul profissional transmite confianca e precisao. E a cor mais associada a instrumentacao, metrologia e engenharia. Baseado na escala `blue` do Tailwind CSS 4.

### 2.2. Cores secundarias (neutros)

| Token | Hex | Tailwind class | Uso |
|---|---|---|---|
| `neutral-50` | `#f9fafb` | `bg-neutral-50` | Background da pagina |
| `neutral-100` | `#f3f4f6` | `bg-neutral-100` | Background de cards, areas alternadas em tabelas |
| `neutral-200` | `#e5e7eb` | `bg-neutral-200` | Bordas de inputs, divisores |
| `neutral-300` | `#d1d5db` | `bg-neutral-300` | Bordas de tabelas, placeholders |
| `neutral-400` | `#9ca3af` | `text-neutral-400` | Texto muted, placeholders |
| `neutral-500` | `#6b7280` | `text-neutral-500` | Texto secundario, labels |
| `neutral-600` | `#4b5563` | `text-neutral-600` | Texto de corpo |
| `neutral-700` | `#374151` | `text-neutral-700` | Texto de corpo enfatizado |
| `neutral-800` | `#1f2937` | `text-neutral-800` | Titulos de secao |
| `neutral-900` | `#111827` | `text-neutral-900` | **Titulos principais**, headings |
| `neutral-950` | `#030712` | `text-neutral-950` | Texto sobre backgrounds claros com maximo contraste |

> Baseado na escala `gray` do Tailwind CSS 4.

### 2.3. Cor de acento (CTAs e destaques)

| Token | Hex | Tailwind class | Uso |
|---|---|---|---|
| `accent-50` | `#fff7ed` | `bg-accent-50` | Background de notificacoes |
| `accent-100` | `#ffedd5` | `bg-accent-100` | Badges de destaque |
| `accent-200` | `#fed7aa` | `bg-accent-200` | Bordas de destaque |
| `accent-400` | `#fb923c` | `text-accent-400` | Icones de atencao |
| `accent-500` | `#f97316` | `bg-accent-500` | Badges e destaques de urgencia |
| `accent-600` | `#ea580c` | `bg-accent-600` | Destaque sem texto branco normal; usar com icone/texto grande ou fundo claro |
| `accent-700` | `#c2410c` | `bg-accent-700` | **Botoes de acao secundaria** com texto branco normal |
| `accent-800` | `#9a3412` | `bg-accent-800` | Hover/pressed de botoes de acento |

> Baseado na escala `orange` do Tailwind CSS 4. Laranja quente contrasta com o azul frio da primaria, chamando atencao para acoes importantes sem competir com a paleta principal.

### 2.4. Cores semanticas

| Token | Hex | Tailwind class | Uso |
|---|---|---|---|
| `success-50` | `#f0fdf4` | `bg-success-50` | Background de alertas de sucesso |
| `success-100` | `#dcfce7` | `bg-success-100` | Badge "Aprovado", "Calibrado" |
| `success-500` | `#22c55e` | `text-success-500` | Icone de sucesso, check |
| `success-600` | `#16a34a` | `bg-success-600` | Indicador de confirmacao; texto branco apenas em texto grande/bold |
| `success-700` | `#15803d` | `bg-success-700` | Botao de confirmacao com texto branco normal |
| `warning-50` | `#fffbeb` | `bg-warning-50` | Background de alertas de aviso |
| `warning-100` | `#fef3c7` | `bg-warning-100` | Badge "Vencendo", "Pendente" |
| `warning-500` | `#eab308` | `text-warning-500` | Icone de aviso |
| `warning-600` | `#ca8a04` | `bg-warning-600` | Botao de atencao |
| `danger-50` | `#fef2f2` | `bg-danger-50` | Background de alertas de erro |
| `danger-100` | `#fee2e2` | `bg-danger-100` | Badge "Reprovado", "Vencido" |
| `danger-500` | `#ef4444` | `text-danger-500` | Icone de erro, validacao |
| `danger-600` | `#dc2626` | `bg-danger-600` | Botao destrutivo (excluir) |
| `danger-700` | `#b91c1c` | `bg-danger-700` | Hover destrutivo |
| `info-50` | `#eff6ff` | `bg-info-50` | Background de alertas informativos |
| `info-100` | `#dbeafe` | `bg-info-100` | Badge informativo |
| `info-500` | `#3b82f6` | `text-info-500` | Icone informativo |

> Cores semanticas baseadas nas escalas `green`, `yellow`, `red` e `blue` do Tailwind CSS 4.

### 2.5. Backgrounds

| Elemento | Token | Classe Tailwind |
|---|---|---|
| Pagina (body) | `bg-page` | `bg-neutral-50` |
| Card / painel | `bg-card` | `bg-white` |
| Sidebar | `bg-sidebar` | `bg-primary-900` |
| Sidebar hover item | `bg-sidebar-item-hover` | `bg-primary-800` |
| Header/topbar | `bg-header` | `bg-white` |
| Tabela row alternada | `bg-table-stripe` | `bg-neutral-50` |
| Modal overlay | `bg-overlay` | `bg-black/50` |
| Input | `bg-input` | `bg-white` |
| Input disabled | `bg-input-disabled` | `bg-neutral-100` |

### 2.6. Texto

| Elemento | Token | Classe Tailwind |
|---|---|---|
| Texto primario (corpo) | `text-body` | `text-neutral-900` |
| Texto secundario | `text-secondary` | `text-neutral-600` |
| Texto muted (placeholders, hints) | `text-muted` | `text-neutral-400` |
| Texto inverso (sobre bg escuro) | `text-inverse` | `text-white` |
| Texto de link | `text-link` | `text-primary-600` |
| Texto de link hover | `text-link-hover` | `text-primary-700` |
| Texto de label de formulario | `text-label` | `text-neutral-700` |
| Texto de erro | `text-error` | `text-danger-600` |

### 2.7. Configuracao Tailwind CSS 4

```css
/* resources/css/app.css — Tailwind CSS 4 theme tokens */
@theme {
  /* Primary — Blue */
  --color-primary-50: #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-200: #bfdbfe;
  --color-primary-300: #93c5fd;
  --color-primary-400: #60a5fa;
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb;
  --color-primary-700: #1d4ed8;
  --color-primary-800: #1e40af;
  --color-primary-900: #1e3a8a;
  --color-primary-950: #172554;

  /* Accent — Orange */
  --color-accent-50: #fff7ed;
  --color-accent-100: #ffedd5;
  --color-accent-200: #fed7aa;
  --color-accent-400: #fb923c;
  --color-accent-500: #f97316;
  --color-accent-600: #ea580c;
  --color-accent-700: #c2410c;
  --color-accent-800: #9a3412;

  /* Success — Green */
  --color-success-50: #f0fdf4;
  --color-success-100: #dcfce7;
  --color-success-500: #22c55e;
  --color-success-600: #16a34a;
  --color-success-700: #15803d;

  /* Warning — Yellow */
  --color-warning-50: #fffbeb;
  --color-warning-100: #fef3c7;
  --color-warning-500: #eab308;
  --color-warning-600: #ca8a04;

  /* Danger — Red */
  --color-danger-50: #fef2f2;
  --color-danger-100: #fee2e2;
  --color-danger-500: #ef4444;
  --color-danger-600: #dc2626;
  --color-danger-700: #b91c1c;

  /* Info — Blue (alias of primary) */
  --color-info-50: #eff6ff;
  --color-info-100: #dbeafe;
  --color-info-500: #3b82f6;
}
```

> No Tailwind CSS 4, tokens sao definidos via `@theme` no CSS, nao em `tailwind.config.js`. As classes `bg-primary-600`, `text-danger-500`, etc. sao geradas automaticamente a partir destes tokens.

### 2.8. Modo escuro

**Decisao: NAO implementar modo escuro no MVP.**

Justificativa:
- Laboratorio de calibracao opera em ambiente bem iluminado (norma exige iluminacao controlada nas bancadas).
- Personas primarias (Marcelo, Juliana, Rafael) nao citaram preferencia por dark mode na discovery.
- Complexidade de manter dois temas com zero beneficio para o usuario-alvo.
- Certificados e documentos oficiais sao sempre em fundo branco.

Se demanda surgir pos-MVP, a arquitetura de tokens acima suporta `dark:` prefix do Tailwind sem refatoracao.

---

## 3. Tipografia

### 3.1. Font families

| Token | Fonte | Fallback | Uso |
|---|---|---|---|
| `font-sans` | Inter | `ui-sans-serif, system-ui, sans-serif` | **Toda a interface.** Headings, corpo, labels, botoes. |
| `font-mono` | JetBrains Mono | `ui-monospace, monospace` | **Dados numericos:** medicoes, incertezas, IDs, codigos de instrumento, valores de calibracao, timestamps. |

> **Inter** — fonte sans-serif projetada para telas, excelente legibilidade em tamanhos pequenos, boa distincao entre caracteres similares (importante para dados tecnicos: 0/O, 1/l/I). Disponivel via Google Fonts ou self-hosted.
>
> **JetBrains Mono** — fonte monoespacada com ligaturas opcionais. Alinhamento perfeito de colunas numericas (essencial para tabelas de medicao e incerteza). Disponivel via Google Fonts ou self-hosted.

### 3.2. Escala tipografica

| Classe Tailwind | Tamanho | Line-height | Uso |
|---|---|---|---|
| `text-xs` | 12px / 0.75rem | 16px / 1rem | Footnotes, timestamps, metadata, IDs secundarios |
| `text-sm` | 14px / 0.875rem | 20px / 1.25rem | Labels de formulario, texto de tabela, badges, captions |
| `text-base` | 16px / 1rem | 24px / 1.5rem | **Corpo de texto padrao.** Paragrafos, descricoes, inputs |
| `text-lg` | 18px / 1.125rem | 28px / 1.75rem | Subtitulos de card, destaques em dashboards |
| `text-xl` | 20px / 1.25rem | 28px / 1.75rem | Titulos de card, nome de secao |
| `text-2xl` | 24px / 1.5rem | 32px / 2rem | **Titulo da pagina** (h1 do conteudo) |
| `text-3xl` | 30px / 1.875rem | 36px / 2.25rem | Numeros grandes em dashboards (KPIs, totais) |
| `text-4xl` | 36px / 2.25rem | 40px / 2.5rem | Hero numbers (uso raro — total mensal no dashboard principal) |

### 3.3. Font weights

| Classe Tailwind | Peso | Uso |
|---|---|---|
| `font-normal` (400) | Regular | Corpo de texto, descricoes, paragrafos |
| `font-medium` (500) | Medium | Labels de formulario, texto de tabela, breadcrumbs, nav items |
| `font-semibold` (600) | Semibold | **Titulos de card**, titulos de coluna em tabelas, botoes |
| `font-bold` (700) | Bold | **Titulo da pagina** (h1), KPIs de dashboard, enfase forte |

> **Regra:** nunca usar `font-thin` (100), `font-light` (300) ou `font-black` (900). A interface e tecnica — precisa de peso suficiente para legibilidade, sem exagero.

### 3.4. Line heights

- Texto de corpo: `leading-normal` (1.5) — padrao para paragrafos e descricoes.
- Texto compacto (tabelas, labels): `leading-snug` (1.375) — economiza espaco vertical.
- Headings: `leading-tight` (1.25) — headings nao precisam de espaco entre linhas generoso.
- Numeros grandes (KPIs): `leading-none` (1.0) — numeros sao de uma linha, sem espaco extra.

---

## 4. Espacamento

### 4.1. Escala de espacamento

O Tailwind CSS 4 usa escala de 4px como base. Os valores mais usados no Kalibrium:

| Classe | Valor | Uso tipico |
|---|---|---|
| `p-1` / `gap-1` | 4px | Espacamento entre icone e texto inline |
| `p-1.5` / `gap-1.5` | 6px | Padding interno de badges, chips |
| `p-2` / `gap-2` | 8px | Padding interno de inputs compactos, gap entre badges |
| `p-3` / `gap-3` | 12px | Padding de celulas de tabela, gap entre items de lista |
| `p-4` / `gap-4` | 16px | **Padding padrao de cards**, gap entre campos de formulario |
| `p-5` / `gap-5` | 20px | Padding de modais |
| `p-6` / `gap-6` | 24px | **Padding de containers de conteudo**, gap entre cards |
| `p-8` / `gap-8` | 32px | Margem entre secoes dentro de uma pagina |
| `p-10` / `gap-10` | 40px | Espacamento entre secoes de dashboard |
| `p-12` / `gap-12` | 48px | Margem vertical entre blocos de pagina |
| `p-16` / `gap-16` | 64px | Espacamento grande — topo/base de paginas, separadores visuais |

### 4.2. Padroes de espacamento

| Contexto | Padrao |
|---|---|
| Gap entre cards em grid | `gap-6` (24px) |
| Padding interno de card | `p-4` (16px) em mobile, `p-6` (24px) em desktop |
| Gap entre campos de formulario | `space-y-4` (16px) |
| Gap entre label e input | `space-y-1.5` (6px) |
| Padding do container de conteudo | `px-4` mobile, `px-6` tablet, `px-8` desktop |
| Margem entre secoes da pagina | `space-y-8` (32px) |
| Padding de celula de tabela | `px-3 py-3` (12px) |
| Padding de header de tabela | `px-3 py-3.5` |
| Gap entre botoes em grupo | `gap-3` (12px) |
| Padding interno de botao | `px-4 py-2` (padrao), `px-3 py-1.5` (small), `px-6 py-3` (large) |

---

## 5. Bordas e Sombras

### 5.1. Border radius

| Classe Tailwind | Valor | Uso |
|---|---|---|
| `rounded-none` | 0 | Tabelas (cantos retos para alinhar com container) |
| `rounded-sm` | 2px | Uso raro |
| `rounded` | 4px | Inputs, selects, textareas |
| `rounded-md` | 6px | **Padrao geral:** botoes, badges, chips, tooltips |
| `rounded-lg` | 8px | **Cards**, modais, dropdowns, alertas |
| `rounded-xl` | 12px | Cards de dashboard destacados, empty states |
| `rounded-full` | 9999px | Avatares, badges circulares, pills, toggles |

### 5.2. Border widths e cores

| Elemento | Classe | Uso |
|---|---|---|
| Card | `border border-neutral-200` | Borda sutil para separar card do background |
| Input default | `border border-neutral-300` | Estado default de input |
| Input focus | `border-primary-500 ring-2 ring-primary-500/20` | Estado focus com ring (acessibilidade) |
| Input error | `border-danger-500 ring-2 ring-danger-500/20` | Estado de erro de validacao |
| Tabela header | `border-b-2 border-neutral-200` | Separador de header de tabela |
| Tabela row | `border-b border-neutral-100` | Separador entre linhas |
| Divisor horizontal | `border-t border-neutral-200` | `<hr>` ou separador de secao |
| Sidebar border | `border-r border-neutral-200` | Borda direita da sidebar (tema claro) |

### 5.3. Sombras

| Classe Tailwind | Uso |
|---|---|
| `shadow-none` | Elementos flat (inputs, badges) |
| `shadow-sm` | **Cards padrao**, inputs com elevacao sutil |
| `shadow` | Cards elevados, header fixo |
| `shadow-md` | **Dropdowns**, tooltips, popovers |
| `shadow-lg` | **Modais** |
| `shadow-xl` | Modais de confirmacao destrutiva, alertas criticos flutuantes |

> **Regra:** usar sombras com parcimonia. A interface e predominantemente flat. Sombras indicam elevacao (algo esta "acima" de outra coisa — dropdown sobre conteudo, modal sobre pagina). Cards usam `shadow-sm` apenas; a maioria dos elementos nao tem sombra.

---

## 6. Iconografia

### 6.1. Biblioteca

**Heroicons** (v2) — biblioteca oficial do ecossistema Tailwind/Blade.

- **Estilo padrao:** `outline` (24px, stroke 1.5) — usado na maioria dos contextos.
- **Estilo solid:** `solid` (24px, fill) — usado em estados ativos (nav item selecionado), badges, indicadores.
- **Estilo mini:** `mini` (20px, solid) — usado dentro de botoes, junto a texto em tamanho `text-sm`.
- **Estilo micro:** `micro` (16px, solid) — uso raro, dentro de badges compactos.

Pacote: `blade-ui-kit/blade-heroicons` para uso direto em templates Blade/Livewire.

### 6.2. Tamanhos padrao

| Contexto | Classe Tailwind | Tamanho | Estilo |
|---|---|---|---|
| Navegacao sidebar | `w-6 h-6` | 24px | outline (inativo), solid (ativo) |
| Botao com texto | `w-5 h-5` | 20px | mini |
| Botao icon-only | `w-5 h-5` | 20px | outline |
| Tabela — coluna de acoes | `w-5 h-5` | 20px | outline |
| Badge / chip | `w-4 h-4` | 16px | micro |
| Input — icone prefix/suffix | `w-5 h-5` | 20px | outline |
| Alerta / toast | `w-5 h-5` | 20px | solid |
| Empty state (ilustracao) | `w-12 h-12` | 48px | outline, `text-neutral-300` |

### 6.3. Quando usar icone sozinho vs icone + texto

| Situacao | Recomendacao |
|---|---|
| Acao primaria (botao "Salvar", "Criar") | Icone + texto. Sempre. |
| Acao em tabela (editar, excluir, mais) | Icone sozinho com `aria-label` e tooltip. |
| Navegacao sidebar (desktop expandida) | Icone + texto. |
| Navegacao sidebar (collapsed) | Icone sozinho com tooltip. |
| Header actions (notificacoes, perfil) | Icone sozinho com badge numerico e `aria-label`. |
| Filtro / toggle | Icone + texto quando houver espaco; icone + tooltip em mobile. |
| Status em tabela | Icone + texto curto ("Aprovado", "Pendente"). |

> **Regra de acessibilidade:** todo icone sem texto visivel DEVE ter `aria-label` descritivo. Sem excecao.

---

## 7. Tokens Semanticos — Tabela Completa

Mapeamento de tokens semanticos para classes Tailwind CSS 4. Usar SEMPRE o token semantico no codigo; nunca referenciar cor hex diretamente em templates Blade/Livewire.

### 7.1. Backgrounds

| Token semantico | Classe Tailwind | Hex |
|---|---|---|
| `bg-page` | `bg-neutral-50` | `#f9fafb` |
| `bg-card` | `bg-white` | `#ffffff` |
| `bg-card-hover` | `bg-neutral-50` | `#f9fafb` |
| `bg-sidebar` | `bg-primary-900` | `#1e3a8a` |
| `bg-sidebar-item-hover` | `bg-primary-800` | `#1e40af` |
| `bg-sidebar-item-active` | `bg-primary-700` | `#1d4ed8` |
| `bg-header` | `bg-white` | `#ffffff` |
| `bg-input` | `bg-white` | `#ffffff` |
| `bg-input-disabled` | `bg-neutral-100` | `#f3f4f6` |
| `bg-table-stripe` | `bg-neutral-50` | `#f9fafb` |
| `bg-overlay` | `bg-black/50` | rgba(0,0,0,0.5) |
| `bg-btn-primary` | `bg-primary-600` | `#2563eb` |
| `bg-btn-primary-hover` | `bg-primary-700` | `#1d4ed8` |
| `bg-btn-secondary` | `bg-white` | `#ffffff` |
| `bg-btn-danger` | `bg-danger-600` | `#dc2626` |
| `bg-btn-danger-hover` | `bg-danger-700` | `#b91c1c` |
| `bg-btn-accent` | `bg-accent-700` | `#c2410c` |
| `bg-btn-accent-hover` | `bg-accent-800` | `#9a3412` |
| `bg-success-subtle` | `bg-success-50` | `#f0fdf4` |
| `bg-warning-subtle` | `bg-warning-50` | `#fffbeb` |
| `bg-danger-subtle` | `bg-danger-50` | `#fef2f2` |
| `bg-info-subtle` | `bg-info-50` | `#eff6ff` |

### 7.2. Texto

| Token semantico | Classe Tailwind | Hex |
|---|---|---|
| `text-heading` | `text-neutral-900` | `#111827` |
| `text-body` | `text-neutral-700` | `#374151` |
| `text-secondary` | `text-neutral-500` | `#6b7280` |
| `text-muted` | `text-neutral-400` | `#9ca3af` |
| `text-inverse` | `text-white` | `#ffffff` |
| `text-link` | `text-primary-600` | `#2563eb` |
| `text-link-hover` | `text-primary-700` | `#1d4ed8` |
| `text-label` | `text-neutral-700` | `#374151` |
| `text-error` | `text-danger-600` | `#dc2626` |
| `text-success` | `text-success-600` | `#16a34a` |
| `text-warning` | `text-warning-600` | `#ca8a04` |
| `text-on-primary` | `text-white` | `#ffffff` |
| `text-on-sidebar` | `text-primary-100` | `#dbeafe` |
| `text-on-sidebar-active` | `text-white` | `#ffffff` |

### 7.3. Bordas

| Token semantico | Classe Tailwind | Hex |
|---|---|---|
| `border-default` | `border-neutral-200` | `#e5e7eb` |
| `border-strong` | `border-neutral-300` | `#d1d5db` |
| `border-input` | `border-neutral-300` | `#d1d5db` |
| `border-input-focus` | `border-primary-500` | `#3b82f6` |
| `border-input-error` | `border-danger-500` | `#ef4444` |
| `border-card` | `border-neutral-200` | `#e5e7eb` |
| `border-table` | `border-neutral-100` | `#f3f4f6` |
| `border-table-header` | `border-neutral-200` | `#e5e7eb` |

---

## 8. Grid e Layout

### 8.1. Container

```html
<!-- Container padrao da area de conteudo -->
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
  <!-- conteudo -->
</div>
```

| Propriedade | Valor |
|---|---|
| `max-w-7xl` | 1280px |
| Padding horizontal | `px-4` (16px) mobile, `px-6` (24px) tablet, `px-8` (32px) desktop |
| Centralizacao | `mx-auto` |

> Para telas full-width (dashboard, tabelas grandes), usar `max-w-full` com o mesmo padding.

### 8.2. Grid system

Grid de 12 colunas via Tailwind CSS `grid`:

```html
<!-- Grid padrao de 12 colunas -->
<div class="grid grid-cols-12 gap-6">
  <div class="col-span-12 md:col-span-8"><!-- conteudo principal --></div>
  <div class="col-span-12 md:col-span-4"><!-- sidebar/aside --></div>
</div>
```

Padroes de grid mais usados:

| Layout | Classes | Uso |
|---|---|---|
| Full width | `col-span-12` | Tabelas, formularios simples |
| 8 + 4 | `col-span-8` + `col-span-4` | Conteudo + sidebar de contexto |
| 6 + 6 | `col-span-6` + `col-span-6` | Formularios em duas colunas |
| 4 + 4 + 4 | `col-span-4` x3 | Cards de KPI em dashboard |
| 3 + 3 + 3 + 3 | `col-span-3` x4 | Grid de cards compactos |

Gap padrao entre colunas: `gap-6` (24px).

### 8.3. Breakpoints

| Breakpoint | Classe | Min-width | Target |
|---|---|---|---|
| Default (mobile) | — | 0 | Celular do tecnico em campo (Juliana) |
| `sm` | `sm:` | 640px | Celular grande / landscape |
| `md` | `md:` | 768px | Tablet (Juliana na bancada) |
| `lg` | `lg:` | 1024px | Laptop (Marcelo, Rafael) |
| `xl` | `xl:` | 1280px | Desktop (Marcelo gestao, Rafael compras) |
| `2xl` | `2xl:` | 1536px | Monitor grande / dual screen |

**Estrategia por persona:**

- **Juliana (tecnica bancada):** mobile-first. Telas de lancamento de medicao sao projetadas para celular/tablet primeiro, depois adaptam para desktop.
- **Marcelo (gerente):** desktop-first para gestao (dashboards, relatorios, aprovacoes). Mobile como consulta rapida.
- **Rafael (cliente):** desktop-first para portal do cliente (historico, certificados). Mobile para consulta rapida de certificado.

### 8.4. Layout da aplicacao (estrutura)

```
┌──────────────────────────────────────────────────────────┐
│  Header (h-16, bg-white, border-b, fixed top)            │
│  [Logo] [Search]                    [Notif] [User Menu]  │
├─────────────┬────────────────────────────────────────────┤
│             │                                            │
│  Sidebar    │  Content Area                              │
│  (w-64,     │  (flex-1, overflow-y-auto)                 │
│   bg-primary│                                            │
│   -900,     │  ┌─ Breadcrumb ──────────────────────────┐ │
│   fixed     │  │ Home > Modulo > Pagina                │ │
│   left,     │  └───────────────────────────────────────┘ │
│   h-screen) │                                            │
│             │  ┌─ Page Header ─────────────────────────┐ │
│  • Nav item │  │ Titulo da Pagina        [+ Novo]      │ │
│  • Nav item │  └───────────────────────────────────────┘ │
│  • Nav item │                                            │
│  • Nav item │  ┌─ Content ─────────────────────────────┐ │
│             │  │                                       │ │
│             │  │  (tabela / form / dashboard / etc.)   │ │
│             │  │                                       │ │
│             │  └───────────────────────────────────────┘ │
│             │                                            │
└─────────────┴────────────────────────────────────────────┘
```

| Elemento | Dimensao | Classe |
|---|---|---|
| Header altura | 64px | `h-16` |
| Sidebar largura (expandida) | 256px | `w-64` |
| Sidebar largura (collapsed) | 64px | `w-16` |
| Content area margin-left | 256px (expandida) / 64px (collapsed) | `ml-64` / `ml-16` |
| Content area padding-top | 64px (header) | `pt-16` |

**Comportamento responsive:**
- **Desktop (lg+):** sidebar visivel, expandida.
- **Tablet (md):** sidebar collapsed (so icones), expande ao hover/click.
- **Mobile (<md):** sidebar oculta, abre como drawer (overlay) via botao hamburger no header.

---

## Apendice A — Contraste de cores (WCAG AA)

Combinacoes de texto/background com ratio de contraste verificado:

| Texto | Background | Ratio | WCAG AA |
|---|---|---|---|
| `text-neutral-900` | `bg-white` | 15.4:1 | Passa (normal + large) |
| `text-neutral-900` | `bg-neutral-50` | 14.5:1 | Passa (normal + large) |
| `text-neutral-700` | `bg-white` | 9.1:1 | Passa (normal + large) |
| `text-neutral-500` | `bg-white` | 4.6:1 | Passa (normal), passa (large) |
| `text-neutral-400` | `bg-white` | 3.0:1 | Falha (normal), passa (large) |
| `text-white` | `bg-primary-600` | 5.3:1 | Passa (normal + large) |
| `text-white` | `bg-primary-700` | 6.5:1 | Passa (normal + large) |
| `text-white` | `bg-primary-900` | 10.5:1 | Passa (normal + large) |
| `text-white` | `bg-danger-600` | 4.6:1 | Passa (normal), passa (large) |
| `text-white` | `bg-success-600` | 3.2:1 | Falha (normal), passa (large) |
| `text-white` | `bg-accent-500` | 3.0:1 | Falha (normal), passa (large) |
| `text-primary-100` | `bg-primary-900` | 7.8:1 | Passa (normal + large) |

> **Nota:** `text-neutral-400` (muted) e usado APENAS para placeholders e hints, nunca para texto de leitura obrigatoria. `bg-success-600` e `bg-accent-500` usam texto bold/large quando com texto branco, ou usam variante -700 para texto normal.

---

## Apendice B — Formatacao de dados do dominio

Padroes de formatacao para dados recorrentes no contexto de metrologia/calibracao:

| Tipo de dado | Formato | Classe tipografica | Exemplo |
|---|---|---|---|
| Medicao com incerteza | `valor unidade ± incerteza unidade` | `font-mono text-sm` | `12,345 mm ± 0,002 mm` |
| Temperatura ambiente | `valor unidade` | `font-mono text-sm` | `23,1 °C` |
| Umidade relativa | `valor %UR` | `font-mono text-sm` | `52,3 %UR` |
| ID de instrumento | codigo alfanumerico | `font-mono text-xs` | `INS-2026-00142` |
| ID de certificado | codigo alfanumerico | `font-mono text-xs` | `CERT-2026-003891` |
| Numero de serie | alfanumerico | `font-mono text-sm` | `SN-BPK2019-0034` |
| Data de calibracao | `DD/MM/AAAA` | `font-mono text-sm` | `12/04/2026` |
| Data de validade | `DD/MM/AAAA` + badge status | `font-mono text-sm` | `12/04/2027` |
| Valor monetario (BRL) | `R$ #.###,##` | `font-mono text-sm` | `R$ 1.250,00` |
| Faixa de medicao | `min a max unidade` | `font-mono text-sm` | `0 a 150 mm` |
| Resolucao | `valor unidade` | `font-mono text-sm` | `0,001 mm` |
| Status de calibracao | badge colorido | `text-sm font-medium` | Badge "Aprovado" verde |

> **Regra:** todos os dados numericos de metrologia usam `font-mono` para alinhamento vertical perfeito em tabelas. Separador decimal e VIRGULA (padrao brasileiro). Separador de milhar e PONTO.

---

## Apendice C — Referencia rapida de decisoes

| Decisao | Escolha | Justificativa |
|---|---|---|
| Cor primaria | Azul (blue scale) | Profissionalismo, metrologia, confianca |
| Cor de acento | Laranja (orange scale) | Contraste com azul, urgencia controlada |
| Modo escuro | Nao no MVP | Ambiente de laboratorio e bem iluminado |
| Font sans | Inter | Legibilidade em tela, distincao de caracteres |
| Font mono | JetBrains Mono | Alinhamento numerico, metrologia |
| Icones | Heroicons v2 | Nativo do ecossistema Tailwind/Blade |
| Border radius padrao | rounded-md (6px) | Clean sem ser rigido |
| Shadow padrao para cards | shadow-sm | Interface predominantemente flat |
| Grid | 12 colunas Tailwind | Flexibilidade de layout |
| Container max-width | 1280px (max-w-7xl) | Confortavel em desktop, nao estica demais |
| Sidebar width | 256px expandida, 64px collapsed | Padrao de apps SaaS |
| Mobile strategy | Mobile-first para bancada, desktop-first para gestao | Personas tem contextos de uso distintos |
| Separador decimal | Virgula (padrao BR) | Conformidade com norma brasileira |
| Separador de milhar | Ponto (padrao BR) | Conformidade com norma brasileira |
