# Responsive Strategy — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** B.6 (ver ux-designer.md)
> **Depende de:** style-guide.md (B.1), layout-master.md (B.4), personas.md

---

## 1. Breakpoints

Mapeamento direto dos breakpoints do Tailwind CSS 4. Todos os valores sao **min-width** (mobile-first por padrao do Tailwind).

| Nome | Largura min | Classe Tailwind | Dispositivo tipico | Persona primaria |
|---|---|---|---|---|
| `sm` | 640px | `sm:` | Smartphone landscape, smartphone grande | Juliana (bancada) |
| `md` | 768px | `md:` | Tablet portrait, iPad mini | Juliana (bancada), Marcelo (campo) |
| `lg` | 1024px | `lg:` | Tablet landscape, laptop pequeno | Marcelo (gestao), Rafael (portal) |
| `xl` | 1280px | `xl:` | Laptop, desktop | Juliana (gestao), Marcelo (gestao) |
| `2xl` | 1536px | `2xl:` | Monitor externo, ultrawide | Rafael (admin), Marcelo (dashboard) |

### Decisao: breakpoints efetivos para layout

O layout-master.md define 3 faixas funcionais que mapeiam sobre os breakpoints Tailwind:

| Faixa | Breakpoint | Sidebar | Header | Justificativa |
|---|---|---|---|---|
| **Mobile** | `< md` (< 768px) | Hidden, drawer overlay | Simplificado (hamburger + icones) | Juliana na bancada com smartphone; Rafael consultando portal no celular |
| **Tablet** | `md` a `lg` (768-1023px) | Collapsed permanente (64px), expand on hover | Completo, search compacto | Juliana com tablet na bancada; Marcelo em campo com tablet |
| **Desktop** | `>= lg` (>= 1024px) | Expanded permanente (256px), toggle manual | Completo com search expandido | Marcelo no escritorio; Juliana no PC do lab; Rafael no desktop |

> **Por que nao usar `sm` como breakpoint de layout?** Entre 640-767px a tela ainda e estreita demais para sidebar collapsed. Manter tratamento mobile ate `md` (768px) garante que tablets small e smartphones landscape nao quebrem.

---

## 2. Persona x Device mapping

### 2.1. Marcelo (Gerente, 48 anos) — smartphone, tablet, desktop

| Contexto | Dispositivo | Frequencia | Modulos usados |
|---|---|---|---|
| Escritorio | Desktop/laptop (>= 1024px) | Diario, 70% do tempo | Dashboard, OS, Financeiro, Fiscal, Relatorios, Config |
| Em campo / reuniao com cliente | Tablet 10" ou smartphone | Semanal, 20% | OS (consulta), Clientes, Certificados |
| Fora do horario (urgencia) | Smartphone | Esporadico, 10% | Notificacoes, OS (status), Dashboard (KPIs) |

**Estrategia:** Desktop-first para modulos de gestao (Dashboard, Financeiro, Fiscal, Relatorios). Telas de consulta rapida (OS status, notificacoes) devem funcionar bem em mobile.

### 2.2. Juliana (Tecnica, 32 anos) — smartphone, tablet

| Contexto | Dispositivo | Frequencia | Modulos usados |
|---|---|---|---|
| Bancada de calibracao | Tablet 10" ou smartphone | Diario, 80% do tempo | Laboratorio (lancamento de medicao), OS (detalhes) |
| PC do laboratorio | Desktop | Diario, 20% | Laboratorio (procedimentos), Certificados (revisao) |

**Estrategia:** Mobile-first para modulos de bancada (Laboratorio > Calibracoes, lancamento de medicoes). Campos grandes, teclado numerico, minimo de toques. Desktop e secundario para revisao.

### 2.3. Rafael (Cliente, 40 anos) — desktop, smartphone

| Contexto | Dispositivo | Frequencia | Modulos usados |
|---|---|---|---|
| Escritorio da fabrica | Desktop | Semanal | Portal do Cliente (certificados, historico, downloads) |
| Em auditoria (chao de fabrica) | Smartphone | Esporadico | Portal do Cliente (consulta rapida de certificado) |

**Estrategia:** Desktop-first para Portal do Cliente (tabelas de certificados, downloads em lote). Mobile deve permitir consulta rapida e download individual.

---

## 3. Mobile-first vs Desktop-first — por modulo

| Modulo | Abordagem | Justificativa |
|---|---|---|
| **Laboratorio > Calibracoes** | Mobile-first | Juliana usa tablet/smartphone na bancada. Interface de lancamento e o core. |
| **Laboratorio > Procedimentos** | Desktop-first | Documentos longos, tabelas de incerteza. Consulta em PC do lab. |
| **Laboratorio > Padroes** | Desktop-first | Cadastro e gestao de padroes, raramente acessado em mobile. |
| **Ordens de Servico** | Mobile-first | Marcelo consulta em campo; Juliana ve detalhes na bancada. |
| **Dashboard** | Desktop-first | KPIs, graficos, tabelas resumo. Marcelo usa no escritorio. |
| **Clientes** | Desktop-first | Cadastro e gestao. Consulta eventual em tablet por Marcelo. |
| **Certificados** | Desktop-first | Revisao e emissao em PC. Download mobile via Portal do Cliente. |
| **Financeiro** | Desktop-first | Gestao financeira, tabelas densas. Exclusivamente desktop (Marcelo). |
| **Fiscal** | Desktop-first | NF-e, integracao fiscal. Exclusivamente desktop. |
| **Documentos** | Desktop-first | Gestao documental, uploads, visualizacao. Desktop. |
| **Portal do Cliente** | Desktop-first | Rafael usa desktop. Mobile e consulta rapida. |
| **Relatorios** | Desktop-first | Graficos e tabelas. Marcelo no escritorio. |
| **Configuracoes** | Desktop-first | Setup raro, formularios complexos. Desktop. |

> **Regra pratica:** se o modulo e usado com as maos sujas, luvas ou na bancada → mobile-first. Se e modulo de escritorio → desktop-first.

---

## 4. Component adaptations

### 4.1. Tabelas → Cards em mobile

**Decisao:** abaixo de `md` (768px), tabelas de listagem se transformam em cards empilhados.

| Viewport | Componente | Exemplo |
|---|---|---|
| Desktop (`>= lg`) | Tabela completa com todas as colunas | `# OS \| Cliente \| Status \| Prazo \| Tipo \| Tecnico \| Acoes` |
| Tablet (`md`-`lg`) | Tabela com colunas prioritarias | `# OS \| Cliente \| Status \| Acoes` (oculta Prazo, Tipo, Tecnico) |
| Mobile (`< md`) | Cards empilhados | Card com titulo (# OS), subtitulo (Cliente), badge (Status), menu (Acoes) |

**Implementacao Tailwind:**

```html
<!-- Tabela (desktop/tablet) -->
<table class="hidden md:table w-full">
  <!-- thead/tbody normais -->
</table>

<!-- Cards (mobile) -->
<div class="md:hidden space-y-3">
  <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-4">
    <div class="flex justify-between items-start">
      <div>
        <p class="font-semibold text-neutral-900">OS-2024-0142</p>
        <p class="text-sm text-neutral-500">Acme Ltda</p>
      </div>
      <span class="badge badge-success">Aberta</span>
    </div>
    <div class="mt-2 flex justify-between items-center text-sm text-neutral-400">
      <span>Prazo: 15/04</span>
      <button><!-- menu ... --></button>
    </div>
  </div>
</div>
```

**Justificativa:** tabelas em mobile forcam scroll horizontal, que e pessimo para usabilidade touch. Cards permitem scan vertical natural.

### 4.2. Sidebar → Drawer

Ja definido no layout-master.md §2.7:

| Viewport | Comportamento |
|---|---|
| Desktop (`>= lg`) | Sidebar fixa expanded (256px), toggle manual para collapsed (64px) |
| Tablet (`md`-`lg`) | Sidebar collapsed (64px) permanente, expand on hover como overlay |
| Mobile (`< md`) | Sidebar oculta, abre como drawer overlay (256px) via hamburger no header |

**Drawer specs:**
- Largura: 256px (`w-64`)
- Z-index: `z-50`
- Backdrop: `bg-black/50`
- Animacao: slide-in da esquerda, `transition-transform duration-300`
- Fechar: clique no backdrop, botao X, swipe left, selecao de item
- Acessibilidade: `role="dialog"`, `aria-modal="true"`, focus trap

### 4.3. Modais → Fullscreen em mobile

**Decisao:** modais padrao em desktop/tablet, fullscreen em mobile.

| Viewport | Comportamento | Classes |
|---|---|---|
| Desktop (`>= lg`) | Modal centralizado, max-width 640px | `max-w-xl mx-auto rounded-lg` |
| Tablet (`md`-`lg`) | Modal centralizado, max-width 540px | `max-w-lg mx-auto rounded-lg` |
| Mobile (`< md`) | Fullscreen, sem rounded corners | `fixed inset-0 rounded-none` |

**Justificativa:** modais em mobile com backdrop + rounded ficam apertados. Fullscreen elimina espaco desperdicado e facilita interacao touch. Header do modal vira barra de navegacao com botao "Fechar" ou "Voltar".

```html
<div class="fixed inset-0 z-50
            md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2
            md:max-w-xl md:w-full md:rounded-lg md:shadow-xl
            bg-white">
  <!-- Conteudo do modal -->
</div>
```

### 4.4. Formularios → Empilhamento

**Decisao:** campos lado a lado em desktop, empilhados em mobile.

| Viewport | Layout de campos |
|---|---|
| Desktop (`>= lg`) | Grid 2 ou 3 colunas (`grid grid-cols-2 lg:grid-cols-3 gap-6`) |
| Tablet (`md`-`lg`) | Grid 2 colunas (`grid grid-cols-2 gap-4`) |
| Mobile (`< md`) | Coluna unica (`grid grid-cols-1 gap-4`) |

**Formulario de bancada (Juliana):** sempre coluna unica, mesmo em desktop. Campos maiores (`text-lg`, `py-3`), teclado numerico (`inputmode="decimal"`), labels acima do campo.

### 4.5. Filtros → Accordion

| Viewport | Comportamento |
|---|---|
| Desktop/Tablet | Filtros sempre visiveis acima da tabela, inline horizontal |
| Mobile | Accordion colapsado por padrao, botao "Filtros" com badge do count ativo |

### 4.6. Paginacao → Scroll infinito

| Viewport | Comportamento |
|---|---|
| Desktop | Paginacao numerica com prev/next (`< 1 2 3 ... 10 >`) + "Mostrando X de Y" |
| Tablet | Prev/next + pagina atual |
| Mobile | Botao "Carregar mais" consumindo paginas do servidor; infinite scroll automatico somente em timeline/feed com justificativa |

---

## 5. Touch targets

### 5.1. Tamanho minimo

**Decisao:** todos os elementos interativos em mobile/tablet devem ter area de toque minima de **44x44px**, conforme WCAG 2.5.5 (Enhanced) e Apple HIG.

| Elemento | Tamanho minimo | Implementacao |
|---|---|---|
| Botoes | 44x44px | `min-h-[44px] min-w-[44px]` ou `py-3 px-4` com texto |
| Links em lista | 44px de altura | `py-3` no item da lista |
| Icones de acao | 44x44px | Padding ao redor do icone: `p-2.5` com icone `w-6 h-6` |
| Checkbox/Radio | 44x44px de area tocavel | Label clicavel com padding generoso |
| Tabs | 44px de altura | `py-3 px-4` |
| Menu items (dropdown) | 44px de altura | `py-3 px-4` |

### 5.2. Spacing entre targets

**Decisao:** minimo **8px** de espaco entre elementos tocaveis adjacentes para evitar toques acidentais.

```html
<!-- Botoes de acao em mobile -->
<div class="flex gap-2">
  <button class="min-h-[44px] px-4 py-3 ...">Salvar</button>
  <button class="min-h-[44px] px-4 py-3 ...">Cancelar</button>
</div>
```

### 5.3. Formulario de bancada (Juliana)

Targets maiores para uso com luvas ou maos umidas:

| Elemento | Tamanho | Classes |
|---|---|---|
| Input de medicao | 56px de altura | `h-14 text-lg` |
| Botao "Salvar leitura" | 56px de altura, largura total | `h-14 w-full text-lg font-semibold` |
| Selecao de padrao | 56px de altura por opcao | `py-4 text-lg` |

**Justificativa:** Juliana usa o sistema na bancada, possivelmente com maos umidas ou com luvas finas. Targets maiores que o minimo de 44px reduzem erros de toque em ambiente de laboratorio.

---

## 6. Navegacao mobile

### 6.1. Decisao: Hamburger menu (drawer) + Bottom action bar contextual

**Opcoes avaliadas:**

| Opcao | Pros | Contras | Decisao |
|---|---|---|---|
| **Bottom nav permanente** | Acesso rapido aos top-5 modulos; thumb-friendly | Ocupa espaco vertical permanente; laboratorio precisa de tela maxima; dificil com 12+ modulos | Rejeitada |
| **Hamburger menu (drawer)** | Toda a navegacao em um lugar; nao ocupa espaco; pattern conhecido | Navegacao escondida, 1 toque extra para acessar | **Adotada para navegacao principal** |
| **Tab bar por modulo** | Bom para sub-navegacao dentro de um modulo | Nao resolve navegacao global; so funciona dentro de modulo | Adotada como complemento |

### 6.2. Estrutura final

```
┌──────────────────────────────────┐
│ [≡]  [K]              🔍  🔔 3  │  ← Header: hamburger abre drawer
├──────────────────────────────────┤
│                                  │
│         CONTEUDO                 │  ← Area maxima de conteudo
│                                  │
│                                  │
├──────────────────────────────────┤
│  [Tab1]  [Tab2]  [Tab3]  [Tab4] │  ← Tab bar contextual (dentro de modulo)
└──────────────────────────────────┘
```

### 6.3. Tab bar contextual (sub-navegacao)

Aparece apenas dentro de modulos com sub-paginas. Exemplo no Laboratorio:

```
┌──────────────────────────────────┐
│ Calibracoes │ Procedim. │ Padroes│  ← tab bar no modulo de bancada/laboratorio
└──────────────────────────────────┘
```

- Posicao: abaixo do header, acima do conteudo (nao no bottom).
- Sticky: `sticky top-16 z-20` (abaixo do header).
- Scroll horizontal se muitas tabs: `overflow-x-auto whitespace-nowrap`.
- Cada tab: `min-h-[44px] px-4 py-3 text-sm font-medium`.

### 6.4. Atalhos de acao (bottom action bar)

Em telas de formulario (ex: lancamento de calibracao), uma barra fixa no rodape com o botao primario:

```
┌──────────────────────────────────┐
│  [Cancelar]           [Salvar ✓] │  ← fixed bottom, bg-white, shadow-up
└──────────────────────────────────┘
```

- Classes: `fixed bottom-0 left-0 right-0 bg-white border-t border-neutral-200 px-4 py-3 z-30`
- Botao primario a direita, secundario a esquerda.
- Safe area para dispositivos com notch: `pb-safe` (via `env(safe-area-inset-bottom)`).

**Justificativa:** Hamburger + tab bar contextual + bottom action bar e o combo que maximiza espaco de conteudo (critico para Juliana na bancada) enquanto mantem toda a navegacao acessivel. Bottom nav permanente foi rejeitada porque o Kalibrium tem 12+ modulos de primeiro nivel — nao cabem em 5 icones.

---

## 7. Data density

### 7.1. Principio geral

**Desktop:** maxima densidade de informacao. Tabelas com todas as colunas, dashboards com multiplos KPIs, graficos lado a lado.

**Mobile:** informacao resumida com expand sob demanda. Mostrar o essencial, permitir drill-down.

### 7.2. Tabelas

| Viewport | Colunas visiveis | Acao para mais dados |
|---|---|---|
| Desktop (`>= lg`) | Colunas definidas no wireframe da tela | Todas as colunas obrigatorias visiveis ou exportaveis conforme regra da tela |
| Tablet (`md`-`lg`) | Prioritarias (3-4 colunas) | Colunas ocultas acessiveis via `...` ou row expand |
| Mobile (`< md`) | Card com 2-3 dados chave | Toque no card abre detalhe completo |

**Prioridade de colunas (exemplo OS):**

| Prioridade | Coluna | Desktop | Tablet | Mobile (card) |
|---|---|---|---|---|
| 1 | # OS | Sim | Sim | Sim (titulo) |
| 2 | Cliente | Sim | Sim | Sim (subtitulo) |
| 3 | Status | Sim | Sim | Sim (badge) |
| 4 | Prazo | Sim | Sim | Sim (metadado) |
| 5 | Tipo calibracao | Sim | Nao | Nao |
| 6 | Tecnico atribuido | Sim | Nao | Nao |
| 7 | Valor total | Sim | Nao | Nao |
| 8 | Data criacao | Sim | Nao | Nao |

### 7.3. Dashboard

| Viewport | Layout |
|---|---|
| Desktop | Grid 4 colunas de KPI cards + 2 graficos lado a lado + tabela resumo |
| Tablet | Grid 2 colunas de KPI cards + graficos empilhados + tabela compacta |
| Mobile | KPI cards empilhados (1 coluna) + graficos empilhados + lista resumo (sem tabela) |

```html
<!-- KPI cards grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
  <!-- KPI card -->
</div>

<!-- Graficos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
  <!-- Grafico 1 -->
  <!-- Grafico 2 -->
</div>
```

### 7.4. Formularios

| Viewport | Campos por linha | Justificativa |
|---|---|---|
| Desktop | 2-3 campos | Reduz scroll vertical, aproveita largura |
| Tablet | 2 campos | Equilibrio densidade/legibilidade |
| Mobile | 1 campo | Maximo espaco para teclado virtual |

### 7.5. Row expand (detalhes sob demanda)

Em tablet, colunas ocultas ficam acessiveis via expand da linha:

```
┌─────────────────────────────────────────────┐
│ OS-2024-0142 │ Acme Ltda │ ●Aberta │  ▼    │  ← linha da tabela
├─────────────────────────────────────────────┤
│ Prazo: 15/04  │  Tipo: Dimensional          │  ← area expandida
│ Tecnico: Juliana  │  Valor: R$ 1.250,00     │
│ Criado em: 10/04/2024                       │
└─────────────────────────────────────────────┘
```

---

## 8. PWA considerations

### 8.1. Decisao: Progressive Web App com suporte offline limitado

O Kalibrium sera entregue como PWA, nao como app nativo. Justificativas:

- Juliana e Marcelo usam dispositivos variados (Android, iOS, tablets genericos).
- Nao ha budget para app nativo separado no MVP.
- PWA permite install na home screen, funciona offline (parcial), e recebe push notifications.
- Laravel 13 + Livewire 4 sao compatíveis com service worker para cache de assets.

### 8.2. manifest.json

```json
{
  "name": "Kalibrium - Gestao de Laboratorio",
  "short_name": "Kalibrium",
  "description": "Sistema de gestao para laboratorios de calibracao acreditados",
  "start_url": "/app",
  "display": "standalone",
  "orientation": "any",
  "background_color": "#1e3a8a",
  "theme_color": "#2563eb",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ],
  "categories": ["business", "productivity"],
  "lang": "pt-BR"
}
```

- `background_color`: `primary-900` (#1e3a8a) — splash screen durante load.
- `theme_color`: `primary-600` (#2563eb) — barra de status do navegador.
- `display: standalone` — sem barra de endereco, parece app nativo.
- `orientation: any` — permite landscape e portrait (ver secao 9).

### 8.3. Service worker — estrategia de cache

| Recurso | Estrategia | Justificativa |
|---|---|---|
| Assets estaticos (CSS, JS, fontes, icones) | Cache-first | Raramente mudam. Vite gera hashes no build. |
| Paginas HTML / respostas Livewire | Network-first, fallback cache | Dados devem ser frescos, mas funciona offline com ultima versao |
| API de dados (JSON) | Network-only | Dados de calibracao sao criticos; nao servir dados stale |
| Imagens de certificados/docs | Cache-first | Documentos nao mudam apos emissao |

**Offline page:** quando totalmente offline e sem cache da pagina solicitada, exibir pagina `/offline` com:
- Logo Kalibrium
- Mensagem: "Voce esta sem conexao. Os dados salvos localmente estao disponiveis."
- Lista de paginas cacheadas acessiveis
- Botao "Tentar novamente"

### 8.4. Install prompt

**Decisao:** prompt customizado, nao o nativo do navegador.

- Exibir banner na primeira visita em mobile apos login: "Instalar Kalibrium na tela inicial para acesso rapido".
- Botoes: "Instalar" (primario) e "Agora nao" (link discreto).
- Se usuario clicou "Agora nao", re-exibir apos 7 dias.
- Se usuario instalou, nunca mais exibir.
- Armazenar estado no `localStorage`.

```html
<div class="fixed bottom-0 inset-x-0 bg-white border-t border-neutral-200 p-4 z-40
            md:hidden" x-show="showInstallBanner">
  <div class="flex items-center gap-3">
    <img src="/icons/icon-48.png" class="w-12 h-12 rounded-lg" alt="Kalibrium">
    <div class="flex-1">
      <p class="text-sm font-semibold text-neutral-900">Instalar Kalibrium</p>
      <p class="text-xs text-neutral-500">Acesso rapido na tela inicial</p>
    </div>
    <button @click="installPwa()" class="btn btn-primary btn-sm">Instalar</button>
    <button @click="dismissInstall()" class="text-neutral-400 text-sm">Depois</button>
  </div>
</div>
```

### 8.5. Push notifications

- Juliana recebe: "OS-2024-0142 atribuida a voce" (nova OS).
- Marcelo recebe: "Padrao X vence em 30 dias" (vencimento), "3 OS atrasadas" (alerta).
- Rafael recebe: "Certificado C-2024-0891 disponivel para download" (emissao).
- Implementacao via Web Push API + service worker.
- Opt-in explicito no primeiro acesso ao modulo relevante.

---

## 9. Orientacao (landscape vs portrait)

### 9.1. Decisao geral

**Permitir ambas as orientacoes.** O `manifest.json` define `orientation: "any"`. Nenhuma tela e bloqueada em orientacao fixa.

### 9.2. Recomendacoes por contexto

| Contexto | Orientacao recomendada | Justificativa |
|---|---|---|
| Formularios longos (lancamento de calibracao) em tablet | **Landscape** | Mais largura para labels + inputs lado a lado; reduz scroll vertical |
| Listagens em smartphone | **Portrait** | Cards empilhados aproveitam scroll vertical natural |
| Dashboard em tablet | **Landscape** | KPIs e graficos aproveitam a largura |
| Portal do Cliente em smartphone | **Portrait** | Consulta rapida, lista de certificados |

### 9.3. Adaptive layout por orientacao

```css
/* Formularios de bancada: 2 colunas em landscape tablet */
@media (min-width: 768px) and (orientation: landscape) {
  .bench-form {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Portrait tablet: 1 coluna para formularios de bancada */
@media (min-width: 768px) and (orientation: portrait) {
  .bench-form {
    grid-template-columns: 1fr;
  }
}
```

### 9.4. Hint visual (nao obrigatorio)

Em telas de formulario longo no tablet (portrait), exibir hint discreto:

> "Dica: gire o tablet para modo paisagem para ver mais campos"

- Exibir apenas uma vez (localStorage).
- Classe: `text-xs text-neutral-400 italic`, com icone de rotacao.

---

## 10. Testes de responsividade

### 10.1. Breakpoints a testar

Todo componente deve ser testado nos seguintes viewports:

| Viewport | Largura | Representa |
|---|---|---|
| iPhone SE | 375px | Smartphone compacto (menor referencia) |
| iPhone 14 Pro | 393px | Smartphone moderno padrao |
| iPad mini | 768px | Tablet portrait (breakpoint `md`) |
| iPad Air | 820px | Tablet portrait (entre `md` e `lg`) |
| iPad Pro 11" landscape | 1194px | Tablet landscape (proximo a `lg`) |
| Laptop | 1280px | Desktop padrao (breakpoint `xl`) |
| Desktop | 1536px | Monitor externo (breakpoint `2xl`) |
| Ultrawide | 1920px | Monitor full HD |

### 10.2. Devices de referencia

| Persona | Device primario | Device secundario |
|---|---|---|
| Juliana (bancada) | Samsung Galaxy Tab A8 (10.5", 1200x800) | Samsung Galaxy A14 (6.6", 1080x2408) |
| Marcelo (campo) | iPad 10th gen (10.9", 2360x1640) | iPhone 13 (6.1", 1170x2532) |
| Rafael (portal) | Desktop Chrome 1920x1080 | iPhone qualquer (consulta eventual) |

> **Racional device Juliana:** tablets Android de custo intermediario sao os mais comuns em laboratorios pequenos (5-10 tecnicos). Samsung Galaxy Tab A-series domina esse segmento no Brasil. O smartphone e o device pessoal que Juliana eventualmente usa.

### 10.3. Checklist de teste por componente

| Item | Verificacao |
|---|---|
| Tabela/Cards | Tabela visivel em `>= md`, cards em `< md`. Sem scroll horizontal. |
| Sidebar | Expanded em `>= lg`, collapsed em `md`-`lg`, drawer em `< md`. |
| Modal | Centered em `>= md`, fullscreen em `< md`. |
| Formulario | Multi-coluna em `>= md`, coluna unica em `< md`. |
| Touch targets | Todos >= 44x44px em `< lg`. Verificar com Chrome DevTools touch simulation. |
| Filtros | Inline em `>= md`, accordion em `< md`. |
| Paginacao | Numerica em `>= md`, "Carregar mais" em `< md`. |
| Header | Search expandido em `>= lg`, compacto em `md`, icone em `< md`. |
| Bottom action bar | Presente em formularios mobile. Safe area respeitada. |
| Landscape tablet | Formularios de bancada usam 2 colunas. Dashboard aproveita largura. |
| PWA install | Banner aparece em mobile apos login. Install funciona. |
| Offline | Pagina offline exibida quando sem conexao e sem cache. |

### 10.4. Ferramentas de teste

| Ferramenta | Uso |
|---|---|
| Chrome DevTools (Device Mode) | Teste rapido de breakpoints e touch simulation |
| BrowserStack | Teste em devices reais (Samsung Tab A8, iPad, iPhones) |
| Lighthouse (PWA audit) | Validar manifest, service worker, installability |
| axe DevTools | Verificar touch target size (WCAG 2.5.5) |

---

## 11. Resumo de decisoes

| # | Decisao | Alternativa rejeitada | Justificativa |
|---|---|---|---|
| D1 | 3 faixas de layout: mobile (< 768), tablet (768-1023), desktop (>= 1024) | 5 faixas (uma por breakpoint Tailwind) | Complexidade desnecessaria; 3 faixas cobrem os 3 contextos reais de uso |
| D2 | Mobile-first para modulos de bancada, desktop-first para gestao | Tudo mobile-first | Marcelo e Rafael usam desktop 70%+ do tempo; forcar mobile-first neles desperdicaria espaco |
| D3 | Tabelas viram cards em mobile | Tabelas responsivas com scroll horizontal | Scroll horizontal e pessimo para touch; cards sao naturais em mobile |
| D4 | Sidebar vira drawer em mobile | Bottom nav permanente | 12+ modulos nao cabem em bottom nav; drawer acomoda todos |
| D5 | Modais fullscreen em mobile | Modais com backdrop em mobile | Modais pequenos em tela pequena desperdicam espaco e dificultam interacao |
| D6 | Touch targets 44x44px minimo, 56px para bancada | 48px padrao Material Design | 44px e WCAG AAA; 56px para bancada compensa uso com luvas/maos umidas |
| D7 | Hamburger + tab bar contextual + bottom action bar | Bottom nav permanente | Maximiza espaco de conteudo; hamburger e pattern universal |
| D8 | PWA, nao app nativo | React Native / Flutter | Sem budget para app nativo; PWA cobre 95% dos casos com uma codebase |
| D9 | Orientation: any (nao travar) | Travar portrait em mobile | Landscape em tablet melhora formularios longos; travar limita Juliana |
| D10 | Infinite scroll em mobile, paginacao em desktop | Paginacao em todos | Mobile favorece scroll continuo; desktop favorece controle preciso de pagina |
