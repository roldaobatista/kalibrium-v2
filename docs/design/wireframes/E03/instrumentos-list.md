# Wireframe — Listagem de Instrumentos

> **Tela:** Instrumentos
> **URL:** `/instrumentos`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S03
> **Persona primaria:** Marcelo (admin), Juliana (leitura)
> **Role minima:** `administrativo`
> **SCR-ID:** SCR-E03-005
> **Wireframe status:** draft

---

## Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar OS, cliente, instrumento...      🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│ Acme Lab     │  Home > Instrumentos                                          │
│              │                                                               │
│ ◻ Dashboard  │  Instrumentos                              [+ Novo Instrumento]│
│ ◻ OS         │  1.842 instrumentos cadastrados                               │
│ ▼ Laborat.  │                                                               │
│   ◻ Calib.  │  ┌─ Filtros ─────────────────────────────────────────────────┐│
│   ◻ Proced. │  │ [Dominio ▼]  [Cliente ▼]  [Status ▼]  🔍 NS ou modelo   │││
│   ◻ Padroes │  └───────────────────────────────────────────────────────────┘│
│ ◻ Clientes  │                                                               │
│ ◻ Certific. │  ┌─ Tabela ──────────────────────────────────────────────────┐│
│ ◻ Financ.   │  │ □  Nro Serie   │ Modelo           │ Dominio  │ Cliente │ Ult.Cal │ Status │ ⋮ │
│              │  │ □  SN-4821     │ Paquimetro 150mm │ Dimens.  │ Acme L. │ 01/2026 │ ●Ativ  │ ⋮ │
│              │  │ □  SN-7733     │ Manometro 0-10b  │ Pressao  │ Delta E.│ 12/2025 │ ●Ativ  │ ⋮ │
│              │  │ □  SN-0012     │ Balanca 5kg      │ Massa    │ Acme L. │ —       │ ●Ativ  │ ⋮ │
│              │  │ □  SN-1199     │ Termometro -20°C │ Temp.    │ Beta C. │ 02/2026 │ ●Ativ  │ ⋮ │
│              │  │ □  SN-8844     │ Micrômetro 25mm  │ Dimens.  │ Gama I. │ 11/2025 │ ●Inati │ ⋮ │
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│ ━━━━━━━━━━━━ │  < 1  2  3  ...  74 >            Mostrando 1-25 de 1.842     │
│ ◻ Config.   │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

### Menu de acoes (⋮) por linha

```
┌──────────────────────┐
│ Ver detalhe          │
│ Editar               │
│ Nova Calibracao      │
│ Historico tecnico    │
│ ────────────────     │
│ Desativar            │  ← danger
└──────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Header | `layout-master.md §3` | Global |
| Sidebar | `layout-master.md §2` | Item "Clientes" ativo (instrumentos e sub-modulo) |
| Filtros | `component-patterns.md #7, #5` | Dominio Select, Cliente Combobox, Status Select, Search |
| Table | `component-patterns.md #15` | Sortavel por Modelo, Nro Serie; bulk select |
| Badge Dominio | `component-patterns.md #17` | Dimensional=`primary`, Pressao=`purple`, Massa=`orange`, Temperatura=`teal` |
| Badge Status | `component-patterns.md #17` | Ativo=`success`, Inativo=`neutral` |
| Pagination | `component-patterns.md #24` | 25 por pagina |
| Dropdown Actions | `component-patterns.md #3` | Menu ⋮ |

---

## Campos da tabela

| Coluna | Tipo | Sortavel | Observacao |
|---|---|---|---|
| Checkbox | Selecao | — | Bulk actions |
| Numero de Serie | Texto | Sim | Identificador principal |
| Modelo | Texto | Sim | Ex: "Paquimetro 150mm Mitutoyo" |
| Dominio Metrologico | Badge colorido | Sim | Dimensional / Pressao / Massa / Temperatura |
| Cliente | Texto truncado | Sim | Nome da empresa proprietaria |
| Ultima Calibracao | MM/AAAA | Sim | "—" se nunca calibrado |
| Status | Badge | Nao | Ativo / Inativo |
| Acoes | Menu | — | — |

---

## Filtros

| Filtro | Tipo | Opcoes |
|---|---|---|
| Dominio metrologico | Select | Todos, Dimensional, Pressao, Massa, Temperatura |
| Cliente | Combobox com busca | Lista de clientes do tenant |
| Status | Select | Todos, Ativo, Inativo |
| Busca livre | Text Input | Busca por NS ou modelo; debounce 300ms |

---

## Dados

- **Fonte:** `GET /api/v1/instrumentos?page=1&sort=modelo`
- **Entidade:** `Instrumento`
- **Campos:** `id`, `numero_serie`, `modelo`, `dominio_metrologico`, `cliente_id`, `cliente_razao_social`, `ultima_calibracao`, `status`
- **Paginacao:** 25 por pagina

---

## Estados

### Vazio

```
┌──────────────────────────────────────────────────┐
│           [icone adjustments-horizontal]         │
│         Nenhum instrumento cadastrado            │
│   Cadastre o primeiro instrumento de um cliente  │
│   para comecar o historico de calibracoes.       │
│              [+ Novo Instrumento]                │
└──────────────────────────────────────────────────┘
```

### Carregando

- Skeleton de 5 linhas com colunas em cinza

### Erro

- Toast `danger`: "Erro ao carregar instrumentos. Tente novamente."

### Sem resultado de busca

```
┌──────────────────────────────────────────────────┐
│         Nenhum instrumento encontrado            │
│   Tente ajustar os filtros ou o numero de serie  │
│   [Limpar filtros]     [+ Novo Instrumento]      │
└──────────────────────────────────────────────────┘
```

---

## Acessibilidade

- `<th scope="col">` em todos os cabecalhos
- Badge de dominio com texto visivel (nao so cor)
- Ordenacao de coluna: `aria-sort` dinamico
- Linha clicavel inteira navega para detalhe: `role="link"` implícito via `<a>` envolvendo a linha
- Menu ⋮ com `aria-label="Acoes para instrumento {nroSerie}"`
