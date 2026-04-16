# Wireframe — Listagem de Clientes

> **Tela:** Clientes
> **URL:** `/clientes`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S01
> **Persona primaria:** Marcelo (admin)
> **Role minima:** `administrativo`
> **SCR-ID:** SCR-E03-001
> **Wireframe status:** draft

---

## Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar OS, cliente, instrumento...      🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│ Acme Lab     │  Home > Clientes                                              │
│ [logo]       │                                                               │
│              │  Clientes                                    [+ Novo Cliente] │
│ ━━━━━━━━━━━━ │  248 clientes cadastrados                                     │
│              │                                                               │
│ ◻ Dashboard  │  ┌─ Filtros ─────────────────────────────────────────────────┐│
│ ◻ OS         │  │ [Status ▼]  [Cidade ▼]  [Regime Fiscal ▼]  🔍 CNPJ/nome │││
│ ▼ Laborat.  │  └───────────────────────────────────────────────────────────┘│
│   ◻ Calib.  │                                                               │
│   ◻ Proced. │  ┌─ Tabela ──────────────────────────────────────────────────┐│
│   ◻ Padroes │  │ □  CNPJ            │ Razao Social         │ Cidade │ Status │ Ações │
│ ◼ Clientes  │  │ □  12.345.678/0001 │ Acme Metrologia Ltda │ SP     │ ●Ativo │  ⋮   │
│ ◻ Certific. │  │ □  98.765.432/0001 │ Delta Engenharia SA  │ ABC    │ ●Ativo │  ⋮   │
│ ◻ Financ.   │  │ □  11.222.333/0001 │ Beta Calibracoes ME  │ Campin │ ●Inativ│  ⋮   │
│ ◻ Fiscal    │  │ □  44.555.666/0001 │ Gama Instrumentacao  │ Santos │ ●Ativo │  ⋮   │
│ ◻ Docs      │  │ □  77.888.999/0001 │ Omega Industrial SA  │ SP     │ ●Ativo │  ⋮   │
│ ◻ Relat.    │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│ ━━━━━━━━━━━━ │  < 1  2  3  ...  10 >              Mostrando 1-25 de 248     │
│ ◻ Config.   │                                                               │
│ ◻ Ajuda     │  ─────────────────────────────────────────────────────────────│
│    « ◁       │  Kalibrium v1.0.0  •  Termos  •  Privacidade  •  Suporte     │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

### Menu de acoes (⋮) por linha

```
┌──────────────────┐
│ Ver detalhe      │
│ Editar           │
│ Novo Instrumento │
│ ────────────     │
│ Desativar        │  ← danger color
└──────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Header | `layout-master.md §3` | Global, fixo |
| Sidebar | `layout-master.md §2` | Item "Clientes" ativo |
| Breadcrumb | `component-patterns.md #22` | Home > Clientes |
| Page title + actions | `layout-master.md §4.3` | h1 + botao [+ Novo Cliente] primario |
| Filtros | `component-patterns.md #7, #5` | Status Select, Cidade Select, Regime Select, Search Input |
| Table | `component-patterns.md #15` | Sortable por Razao Social, CNPJ; bulk select; 25 por pagina |
| Badge Status | `component-patterns.md #17` | `success` = Ativo; `neutral` = Inativo |
| Dropdown Actions | `component-patterns.md #3` | Menu ⋮ por linha |
| Pagination | `component-patterns.md #24` | Numeros + prev/next |
| Bulk Actions Bar | `component-patterns.md #4` | Aparece ao selecionar linhas: "Desativar selecionados" |

---

## Campos da tabela

| Coluna | Tipo | Sortavel | Obrigatorio | Observacao |
|---|---|---|---|---|
| Checkbox | Selecao | — | — | Bulk actions |
| CNPJ/CPF | Texto formatado | Nao | Sim | Mascara `XX.XXX.XXX/XXXX-XX` ou `XXX.XXX.XXX-XX` |
| Razao Social | Texto | Sim | Sim | Sortavel A-Z (default) |
| Cidade | Texto | Sim | Nao | Cidade do endereco principal |
| Status | Badge | Nao | Sim | Ativo / Inativo |
| Acoes | Menu | — | — | Icone ⋮ com dropdown |

---

## Filtros disponiveis

| Filtro | Tipo | Opcoes | Comportamento |
|---|---|---|---|
| Status | Select | Todos, Ativo, Inativo | Filtro imediato ao selecionar |
| Cidade | Select com busca | Lista dinamica de cidades cadastradas | Filtro imediato |
| Regime Fiscal | Select | Todos, Simples Nacional, Lucro Presumido, Lucro Real | Filtro imediato |
| Busca livre | Text Input | — | Busca por CNPJ, CPF ou razao social; debounce 300ms |

---

## Dados

- **Fonte:** `GET /api/v1/clientes?page=1&status=active&sort=razao_social`
- **Entidade:** `Cliente`
- **Campos retornados:** `id`, `cnpj`, `cpf`, `razao_social`, `cidade`, `status`, `created_at`
- **Paginacao:** 25 por pagina (configuravel em settings do tenant)
- **Ordenacao default:** `razao_social ASC`

---

## Estados

### Estado vazio

```
┌─────────────────────────────────────────────────┐
│                                                 │
│           [icone building-office-2]             │
│                                                 │
│         Nenhum cliente cadastrado               │
│                                                 │
│   Cadastre o primeiro cliente do laboratorio    │
│   para comecar a criar ordens de servico.       │
│                                                 │
│              [+ Novo Cliente]                   │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Estado carregando

- Skeleton: 5 linhas de tabela com colunas em cinza (`animate-pulse`)
- Filtros permanecem interativos

### Estado de erro

- Toast `danger`: "Erro ao carregar clientes. Tente novamente."
- Botao de retry inline: "Tentar novamente"

### Estado sem resultado de busca

```
┌─────────────────────────────────────────────────┐
│                                                 │
│         Nenhum cliente encontrado               │
│                                                 │
│   Tente ajustar os filtros ou cadastre um       │
│   novo cliente.                                 │
│                                                 │
│   [Limpar filtros]        [+ Novo Cliente]      │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## Acessibilidade

- Tabela com `role="table"`, `<th scope="col">` em todos os cabecalhos
- Checkbox de selecao com `aria-label="Selecionar cliente {razaoSocial}"`
- Checkbox de selecao global com `aria-label="Selecionar todos os clientes desta pagina"`
- Badge de status com texto visivel (nao so cor)
- Menu ⋮ com `aria-label="Acoes para {razaoSocial}"` e `aria-haspopup="true"`
- Ordenacao de coluna: `aria-sort="ascending"` / `"descending"` / `"none"`
- Paginacao com `role="navigation"` e `aria-label="Paginacao de clientes"`
- Filtros com `<label>` associado a cada select/input

---

## Mobile (< 768px)

```
┌──────────────────────────────────┐
│ [≡]  [K]              🔍  🔔 2  │
├──────────────────────────────────┤
│ Home > Clientes                  │
│                                  │
│ Clientes              [+ Novo]   │
│ 248 clientes                     │
│                                  │
│ [Filtros ▼]     🔍 Buscar...     │
│                                  │
│ ┌─ Card ──────────────────────┐ │
│ │ Acme Metrologia Ltda        │ │
│ │ 12.345.678/0001             │ │
│ │ SP  •  ●Ativo          [⋮] │ │
│ └─────────────────────────────┘ │
│ ┌─ Card ──────────────────────┐ │
│ │ Delta Engenharia SA         │ │
│ │ 98.765.432/0001             │ │
│ │ ABC •  ●Ativo          [⋮] │ │
│ └─────────────────────────────┘ │
│                                  │
│ [Carregar mais]                  │
└──────────────────────────────────┘
```
