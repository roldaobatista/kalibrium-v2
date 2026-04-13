# Accessibility Policy — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** B.7
> **Dependencias:** `docs/design/style-guide.md` v1.0.0, `docs/design/component-patterns.md` v1.0.0

---

## 1. WCAG Level Alvo: AA

**Decisao:** WCAG 2.2 Level AA.

**Justificativa:**
- AA cobre as necessidades reais dos usuarios do Kalibrium (engenheiros, tecnicos de bancada, compradores industriais) sem o custo desproporcional do AAA.
- AA e o padrao exigido pela legislacao brasileira (LBI 13.146/2015) e pela maioria dos contratos corporativos B2B.
- AAA sera aplicado pontualmente onde o custo for baixo (ex.: contrast ratio 7:1 em texto de corpo ja atendido pela paleta atual).

---

## 2. Color Contrast

### 2.1. Ratios minimos

| Tipo | Ratio minimo (AA) | Referencia WCAG |
|---|---|---|
| Texto normal (< 18pt / < 14pt bold) | **4.5:1** | 1.4.3 |
| Texto grande (>= 18pt / >= 14pt bold) | **3:1** | 1.4.3 |
| Componentes UI e graficos | **3:1** | 1.4.11 |

### 2.2. Verificacao da paleta do style-guide

| Combinacao | Foreground | Background | Ratio aprox. | Status |
|---|---|---|---|---|
| Texto corpo | `neutral-900` (#111827) | `neutral-50` (#f9fafb) | ~15.8:1 | OK |
| Texto secundario | `neutral-600` (#4b5563) | `white` (#ffffff) | ~7.0:1 | OK |
| Texto muted | `neutral-400` (#9ca3af) | `white` (#ffffff) | ~3.5:1 | ATENCAO (1) |
| Texto inverso | `white` | `primary-800` (#1e40af) | ~7.2:1 | OK |
| Botao primario | `white` | `primary-600` (#2563eb) | ~4.6:1 | OK |
| Botao danger | `white` | `danger-600` (#dc2626) | ~4.6:1 | OK |
| Link | `primary-600` (#2563eb) | `white` (#ffffff) | ~4.6:1 | OK |
| Texto erro | `danger-600` (#dc2626) | `white` (#ffffff) | ~4.6:1 | OK |
| Warning icon | `warning-500` (#eab308) | `white` (#ffffff) | ~2.1:1 | ATENCAO (2) |

**Notas:**
1. `neutral-400` como placeholder/hint sobre branco atinge ~3.5:1. Aceito para placeholders (nao sao conteudo essencial), mas labels e mensagens de ajuda devem usar `neutral-500` ou mais escuro.
2. `warning-500` sobre branco falha contraste. Usar `warning-600` (#ca8a04, ~4.1:1) para texto. Icones warning devem ter label textual acessivel — nao depender apenas da cor.

### 2.3. Regras

- **Nunca** transmitir informacao apenas por cor. Sempre combinar com icone, texto ou pattern.
- Testar contraste ao criar qualquer nova combinacao foreground/background.
- Dark mode (futuro): manter os mesmos ratios minimos.

---

## 3. Keyboard Navigation

### 3.1. Tab order

- Tab order segue a ordem visual do DOM (left-to-right, top-to-bottom).
- Nunca usar `tabindex` > 0. Usar `tabindex="0"` para elementos custom focaveis e `tabindex="-1"` para foco programatico.
- Todos os elementos interativos devem ser alcancaveis via `Tab`.

### 3.2. Focus visible

- Indicador de foco: `focus-visible:ring-2 focus-visible:ring-primary-500/20 focus-visible:ring-offset-2`.
- **Nunca** remover `outline` sem substituir por indicador visual equivalente.
- Contrast ratio do indicador de foco: minimo 3:1 contra o background adjacente.

### 3.3. Focus trap em modais

- Ao abrir modal/dialog, foco move para o primeiro elemento focavel dentro do modal.
- `Tab` e `Shift+Tab` circulam apenas dentro do modal (focus trap).
- `Escape` fecha o modal e retorna foco ao elemento que o abriu.
- Background (overlay) recebe `aria-hidden="true"` e `inert` enquanto modal esta aberto.

### 3.4. Skip links

- Primeiro elemento focavel da pagina: link "Pular para conteudo principal" (`#main-content`).
- Visivel apenas em `:focus` (oculto visualmente, mas acessivel via teclado).
- Implementacao: `<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute ...">`.

---

## 4. Screen Readers

### 4.1. ARIA roles

- Usar elementos HTML semanticos nativos sempre que possivel (`<button>`, `<nav>`, `<main>`, `<table>`, `<dialog>`).
- ARIA roles apenas quando HTML semantico nao supre (ex.: `role="tablist"`, `role="tab"`, `role="tabpanel"`).
- Primeira regra de ARIA: "Nao use ARIA se voce pode usar HTML nativo."

### 4.2. Labels

- Todo elemento interativo deve ter nome acessivel: `<label>`, `aria-label`, ou `aria-labelledby`.
- Icones decorativos: `aria-hidden="true"`.
- Icones informativos (sem texto adjacente): `aria-label` descritivo.

### 4.3. Live regions

- Toasts/notificacoes: `role="status"` + `aria-live="polite"` (informativas) ou `role="alert"` + `aria-live="assertive"` (erros/urgencias).
- Loading indicators globais: `aria-live="polite"` + texto "Carregando..." visivel ou via `sr-only`.
- Contadores atualizados (KPIs, badges): `aria-live="polite"` se atualizacao automatica.

### 4.4. Alt text

- Imagens informativas: `alt` descritivo e conciso.
- Imagens decorativas: `alt=""` + `aria-hidden="true"`.
- Logos: `alt="Kalibrium"`.
- Graficos complexos: ver secao 12 (Excecoes).

---

## 5. Formularios

### 5.1. Labels

- Todo input deve ter `<label for="id">` associado. Nunca usar placeholder como substituto de label.
- Labels posicionados acima do input (padrao do style-guide).

### 5.2. Mensagens de erro

- Erros de validacao vinculados via `aria-describedby` apontando para o elemento de erro.
- Mensagem de erro inclui o que esta errado e como corrigir.
- Container de erros marcado com `role="alert"` para anuncio imediato.

```html
<label for="serial">Numero de serie</label>
<input id="serial" aria-describedby="serial-error" aria-invalid="true" />
<p id="serial-error" role="alert" class="text-danger-600 text-sm">
  Numero de serie deve ter 10 caracteres.
</p>
```

### 5.3. Campos obrigatorios

- Indicar com `aria-required="true"` (alem do atributo `required`).
- Indicador visual: asterisco `*` com legenda no topo do formulario ("* campo obrigatorio").
- Nao depender apenas do asterisco — screen readers leem `aria-required`.

### 5.4. Grupos de campos

- Agrupar campos relacionados com `<fieldset>` + `<legend>`.
- Radio groups e checkbox groups sempre dentro de `<fieldset>`.

---

## 6. Tabelas

### 6.1. Estrutura semantica

- Usar `<table>`, `<thead>`, `<tbody>`, `<th>`, `<td>` — nunca `<div>` simulando tabela.
- Headers com `scope="col"` (colunas) ou `scope="row"` (linhas).

### 6.2. Caption

- Toda tabela com `<caption>` descritivo (pode ser visualmente oculto via `sr-only`).
- Exemplo: `<caption class="sr-only">Lista de certificados de calibracao</caption>`.

### 6.3. Tabelas complexas

- Tabelas com headers multi-nivel: usar `id` + `headers` nos `<td>`.
- Tabelas de dados extensas: resumo via `aria-describedby` apontando para paragrafo explicativo.

### 6.4. Tabelas interativas

- Checkboxes de selecao: `aria-label="Selecionar certificado [ID]"`.
- Botoes de acao em linha: `aria-label` descritivo (ex.: "Editar certificado 2024-001").
- Sort headers: `aria-sort="ascending"` / `"descending"` / `"none"`.

---

## 7. Componentes Interativos

### 7.1. Botoes

- `<button>` nativo sempre que possivel (inclui `role="button"` implicito).
- Links que parecem botoes: se navega, usar `<a>`. Se executa acao, usar `<button>`.
- `aria-disabled="true"` em botoes desabilitados (alem de `disabled`).
- `aria-busy="true"` durante loading state.

### 7.2. Links vs Buttons

| Elemento | Quando usar | Keyboard |
|---|---|---|
| `<a href>` | Navega para URL/rota | `Enter` |
| `<button>` | Executa acao (submit, toggle, delete) | `Enter` ou `Space` |

Nunca usar `<a>` sem `href`. Nunca usar `<div onclick>`.

### 7.3. Dropdown Menu

- Trigger: `aria-haspopup="true"` + `aria-expanded="false/true"`.
- Menu: `role="menu"`, itens com `role="menuitem"`.
- Keyboard: `Enter`/`Space` abre, `Escape` fecha, setas navegam itens, `Home`/`End` vao ao primeiro/ultimo.

### 7.4. Accordion / Collapsible

- Trigger: `<button>` com `aria-expanded="false/true"` + `aria-controls="panel-id"`.
- Painel: `id` correspondente, `role="region"` + `aria-labelledby="trigger-id"`.
- Keyboard: `Enter`/`Space` toggle. Opcionalmente setas up/down entre triggers.

### 7.5. Tabs

- Container: `role="tablist"`.
- Aba: `role="tab"` + `aria-selected="true/false"` + `aria-controls="panel-id"`.
- Painel: `role="tabpanel"` + `aria-labelledby="tab-id"`.
- Keyboard: setas left/right navegam tabs, `Tab` move para dentro do painel.

### 7.6. Modal / Dialog

- `<dialog>` nativo ou `role="dialog"` + `aria-modal="true"`.
- `aria-labelledby` apontando para titulo do modal.
- Focus trap obrigatorio (ver secao 3.3).

---

## 8. Motion

### 8.1. prefers-reduced-motion

- Toda animacao/transicao CSS deve respeitar `prefers-reduced-motion: reduce`.
- Implementacao Tailwind: `motion-safe:transition-all motion-safe:duration-200`.
- Alternativa: `@media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }`.

### 8.2. Regras

- Nenhuma informacao transmitida exclusivamente por animacao.
- Animacoes sao decorativas (feedback visual), nunca essenciais para compreensao.
- Spinners/loading: texto alternativo ("Carregando...") alem da animacao.
- Auto-play proibido para qualquer conteudo.

---

## 9. Texto

### 9.1. Sem texto em imagem

- Todo texto renderizado como texto HTML, nunca como imagem.
- Excecao unica: logotipo do Kalibrium (com `alt="Kalibrium"`).

### 9.2. Zoom ate 200%

- Layout funcional com zoom ate 200% sem perda de conteudo ou funcionalidade (WCAG 1.4.4).
- Nenhum overflow horizontal em viewport >= 320px de largura CSS.
- Usar unidades relativas (`rem`, `em`, `%`) para tipografia. Evitar `px` para font-size.
- Testar: `Ctrl+` ate 200% — nenhum conteudo cortado, nenhum scroll horizontal.

### 9.3. Espacamento de texto

- Suportar customizacao de espacamento (WCAG 1.4.12): line-height 1.5x, paragraph spacing 2x, letter-spacing 0.12em, word-spacing 0.16em — sem perda de conteudo.

---

## 10. Checklist por Componente

Referencia rapida para o implementer. Coluna "ARIA obrigatorio" lista o minimo; detalhes completos na secao correspondente.

| # | Componente | ARIA obrigatorio | Secao |
|---|---|---|---|
| 1 | Button | `aria-label` (icon-only), `aria-disabled`, `aria-busy` (loading) | 7.1 |
| 2 | Link Button | `href` obrigatorio, `aria-label` se texto ambiguo | 7.2 |
| 3 | Dropdown Menu | `aria-haspopup`, `aria-expanded`, `role="menu"`, `role="menuitem"` | 7.3 |
| 4 | Bulk Actions Bar | `aria-live="polite"` para contagem selecionada | 7.1 |
| 5 | Text Input | `<label for>`, `aria-describedby` (erro/ajuda), `aria-invalid`, `aria-required` | 5 |
| 6 | Textarea | idem Text Input | 5 |
| 7 | Select / Combobox | `role="combobox"`, `aria-expanded`, `aria-activedescendant`, `aria-autocomplete` | 7.3 |
| 8 | Checkbox | `role="checkbox"` (se custom), `aria-checked` | 5.4 |
| 9 | Radio Group | `<fieldset>` + `<legend>`, `role="radiogroup"` | 5.4 |
| 10 | Toggle Switch | `role="switch"`, `aria-checked` | 7.1 |
| 11 | Date Picker | `role="dialog"` no popup, `aria-label` no input | 7.6 |
| 12 | Date Range Picker | idem Date Picker, labels "Data inicio" / "Data fim" | 7.6 |
| 13 | File Upload | `aria-label`, `aria-describedby` (tipos aceitos/tamanho max) | 5 |
| 14 | Number Input | `<label for>`, `aria-valuemin`, `aria-valuemax`, `aria-valuenow` (se slider) | 5 |
| 15 | Table | `<caption>`, `scope="col/row"`, `aria-sort` (se ordenavel) | 6 |
| 16 | Card | heading semantico (`<h2>`, `<h3>`), link/botao acessivel | 4.1 |
| 17 | Badge / Tag | `aria-label` se cor e unico diferenciador | 2.3 |
| 18 | Avatar | `alt` com nome do usuario | 4.4 |
| 19 | Stat / KPI | `aria-live="polite"` se atualiza automaticamente | 4.3 |
| 20 | Empty State | texto descritivo, CTA como `<button>` ou `<a>` | 4.1 |
| 21 | Skeleton Loader | `aria-busy="true"`, `aria-label="Carregando"` | 8 |
| 22 | Breadcrumb | `<nav aria-label="Breadcrumb">`, `aria-current="page"` no ultimo | 4.1 |
| 23 | Tabs | `role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected` | 7.5 |
| 24 | Pagination | `<nav aria-label="Paginacao">`, `aria-current="page"` | 4.1 |
| 25 | Step Indicator | `aria-current="step"`, `aria-label` descritivo por step | 4.1 |
| 26 | Toast | `role="status"` ou `role="alert"`, `aria-live` | 4.3 |
| 27 | Alert Banner | `role="alert"` (erros) ou `role="status"` (info) | 4.3 |
| 28 | Modal / Dialog | `role="dialog"`, `aria-modal`, `aria-labelledby`, focus trap | 7.6 |
| 29 | Progress Bar | `role="progressbar"`, `aria-valuenow`, `aria-valuemin`, `aria-valuemax`, `aria-label` | 4.1 |
| 30 | Spinner | `aria-busy="true"`, texto sr-only "Carregando" | 8 |
| 31 | Section Header | heading semantico (`<h2>`-`<h4>`) na hierarquia correta | 4.1 |
| 32 | Divider | `role="separator"` (se semantico) ou `aria-hidden="true"` (se decorativo) | 4.1 |
| 33 | Accordion | `aria-expanded`, `aria-controls`, `role="region"` no painel | 7.4 |

---

## 11. Ferramentas de Teste

### 11.1. Desenvolvimento (obrigatorio)

| Ferramenta | Uso | Quando |
|---|---|---|
| **axe DevTools** (extensao Chrome) | Scan automatico de violations WCAG | A cada componente novo ou alterado |
| **Lighthouse** (Chrome DevTools > Accessibility) | Score geral + audit detalhado | Antes de merge de cada slice |

### 11.2. Teste manual (obrigatorio)

| Teste | Como | Frequencia |
|---|---|---|
| Keyboard-only | Navegar toda a tela sem mouse, verificar tab order e focus visible | Cada tela nova |
| Zoom 200% | `Ctrl+` ate 200%, verificar layout sem overflow horizontal | Cada tela nova |
| prefers-reduced-motion | Ativar em OS settings, verificar que animacoes param | Cada componente com animacao |

### 11.3. Screen reader (por sprint)

| Ferramenta | Plataforma | Uso |
|---|---|---|
| **NVDA** | Windows (gratuito) | Teste primario de screen reader |
| VoiceOver | macOS/iOS | Teste secundario (se disponivel) |

### 11.4. CI (futuro)

- `axe-core` integrado ao Pest/Playwright para testes automatizados de acessibilidade.
- Threshold: zero violations de nivel "critical" ou "serious".

---

## 12. Excecoes

### 12.1. Graficos complexos

- Graficos de tendencia, dashboards com multiplas series: impossivel descrever em `alt` curto.
- **Solucao:** `alt` descritivo generico ("Grafico de tendencia de calibracoes 2024") + tabela de dados acessivel como alternativa (visivel via link "Ver dados em tabela" ou `<details>`).
- `role="img"` + `aria-label` no container do grafico.

### 12.2. PDFs de certificado

- Certificados de calibracao gerados como PDF.
- **Solucao:** gerar como Tagged PDF (PDF/UA) com estrutura semantica (headings, tabelas, reading order).
- Alternativa HTML do certificado sempre disponivel na tela do sistema.
- `alt` no link de download: "Baixar certificado PDF - [ID do certificado]".

### 12.3. Conteudo legado importado

- Dados migrados do V1 podem conter conteudo sem metadados de acessibilidade.
- **Solucao:** flag `needs_a11y_review` em conteudo importado; backlog de correcao progressiva.

---

## Historico de revisoes

| Versao | Data | Mudanca |
|---|---|---|
| 1.0.0 | 2026-04-12 | Criacao inicial — 12 secoes cobrindo WCAG 2.2 AA |
