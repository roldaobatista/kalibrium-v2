# Wireframe — Listagem de Procedimentos de Calibracao

> **Tela:** Procedimentos
> **URL:** `/procedimentos`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S06
> **Persona primaria:** Juliana (tecnica), Marcelo (gerente)
> **Role minima:** `tecnico`
> **SCR-ID:** SCR-E03-011
> **Wireframe status:** draft

---

## Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar OS, cliente, instrumento...      🔔 2  [JM]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│ Acme Lab     │  Home > Laboratorio > Procedimentos                           │
│              │                                                               │
│ ◻ Dashboard  │  Procedimentos                              [+ Novo Procedimento]
│ ◻ OS         │  24 procedimentos                                             │
│ ▼ Laborat.  │                                                               │
│   ◻ Calib.  │  ┌─ Filtros ─────────────────────────────────────────────────┐│
│   ◼ Proced. │  │ [Dominio ▼]  [Status ▼]  🔍 Nome ou codigo              │││
│   ◻ Padroes │  └───────────────────────────────────────────────────────────┘│
│ ◻ Clientes  │                                                               │
│              │  ┌─ Tabela ──────────────────────────────────────────────────┐│
│              │  │  Codigo    │ Nome              │ Dominio  │ Versao │ Status │ ⋮ │
│              │  │  PROC-D-01 │ Calibracao Paquim.│ Dimens.  │ v3     │ ●Ativo │ ⋮ │
│              │  │  PROC-P-05 │ Calibracao Manom. │ Pressao  │ v2     │ ●Ativo │ ⋮ │
│              │  │  PROC-M-02 │ Calibracao Balanca│ Massa    │ v1     │ ●Ativo │ ⋮ │
│              │  │  PROC-T-03 │ Calibracao Termom.│ Temp.    │ v2     │ ●Ativo │ ⋮ │
│              │  │  PROC-D-08 │ Calibracao Micromt│ Dimens.  │ v1     │ ●Inati │ ⋮ │
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│ ━━━━━━━━━━━━ │  < 1 >                                 Mostrando 1-24 de 24  │
│ ◻ Config.   │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

### Menu de acoes (⋮) por linha

```
┌──────────────────────────┐
│ Ver detalhe              │
│ Nova versao              │
│ Ver historico versoes    │
│ ────────────────────     │
│ Desativar                │  ← danger; so se nao ha OS ativas com este procedimento
└──────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Filtros | `component-patterns.md #7, #5` | Dominio Select, Status Select, Search Input |
| Table | `component-patterns.md #15` | Sortavel por Codigo, Nome; sem bulk actions |
| Badge Dominio | `component-patterns.md #17` | Mesmas cores de instrumentos e padroes |
| Badge Versao | `component-patterns.md #17` | `neutral` com texto "vN" |
| Badge Status | `component-patterns.md #17` | `success` = Ativo; `neutral` = Inativo |
| Dropdown Actions | `component-patterns.md #3` | Menu ⋮ |
| Pagination | `component-patterns.md #24` | 25 por pagina |

---

## Campos da tabela

| Coluna | Conteudo | Sortavel |
|---|---|---|
| Codigo | Codigo interno (PROC-D-01) | Sim |
| Nome | Nome descritivo do procedimento | Sim |
| Dominio | Badge colorido | Sim |
| Versao | Badge neutral "v1", "v2"... | Nao |
| Status | Badge Ativo/Inativo | Nao |
| Acoes | Menu ⋮ | — |

---

## Filtros

| Filtro | Tipo | Opcoes |
|---|---|---|
| Dominio metrologico | Select | Todos, Dimensional, Pressao, Massa, Temperatura |
| Status | Select | Todos, Ativo, Inativo |
| Busca livre | Text Input | Nome ou codigo |

---

## Estados

### Vazio

```
┌──────────────────────────────────────────────────┐
│           [icone document-text]                  │
│     Nenhum procedimento cadastrado               │
│   Procedimentos definem como cada tipo de        │
│   instrumento e calibrado no laboratorio.        │
│           [+ Novo Procedimento]                  │
└──────────────────────────────────────────────────┘
```

### Desativar procedimento com OS ativas

Se procedimento tem OS em andamento, exibe modal:

```
┌─ Modal ────────────────────────────────────────────────────────┐
│  Nao e possivel desativar PROC-D-01                            │
│                                                                │
│  Este procedimento esta em uso por 3 ordens de servico         │
│  em andamento. Encerre ou reatribua as OS antes de             │
│  desativar o procedimento.                                     │
│                                                                │
│  [Ver OS em andamento]                        [Fechar]         │
└────────────────────────────────────────────────────────────────┘
```

---

## Acessibilidade

- Tabela com `<caption>Procedimentos de Calibracao</caption>`
- `<th scope="col">` em todos os cabecalhos
- Badge de versao com texto acessivel: `aria-label="Versao 3"`
- Menu ⋮ com `aria-label="Acoes para procedimento {codigo}"`
