# Wireframe — Listagem de Padroes de Referencia

> **Tela:** Padroes de Referencia
> **URL:** `/padroes`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S04, E03-S05
> **Persona primaria:** Juliana (tecnica), Marcelo (gerente)
> **Role minima:** `tecnico`
> **SCR-ID:** SCR-E03-008
> **Wireframe status:** draft

---

## Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar OS, cliente, instrumento...      🔔 2  [JM]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│ Acme Lab     │  Home > Laboratorio > Padroes de Referencia                  │
│              │                                                               │
│ ◻ Dashboard  │  ┌─ Alert Banner (danger, se houver padroes vencidos) ─────┐ │
│ ◻ OS         │  │ ✕ 2 padroes vencidos bloqueados para uso em calibracoes  │ │
│ ▼ Laborat.  │  │   [Ver padroes vencidos]                                  │ │
│   ◻ Calib.  │  └───────────────────────────────────────────────────────────┘ │
│   ◻ Proced. │                                                               │
│   ◼ Padroes │  Padroes de Referencia                         [+ Novo Padrao]│
│ ◻ Clientes  │  38 padroes                                                   │
│              │                                                               │
│              │  ┌─ Filtros ─────────────────────────────────────────────────┐│
│              │  │ [Status ▼]  [Dominio ▼]  🔍 NS ou modelo                │││
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│              │  ┌─ Tabela ──────────────────────────────────────────────────┐│
│              │  │  Modelo / NS        │ Dominio  │ Lab Calibrador │ Validade │ Status │ ⋮ │
│              │  │  Bloq. Padrao 150mm │ Dimens.  │ INMETRO        │ 30/06/26 │ ●Vigent│ ⋮ │
│              │  │  SN-BLC-001         │          │                │          │        │   │
│              │  │  ─────────────────────────────────────────────────────────│   │
│              │  │  Manometro ref 10b  │ Pressao  │ REDE          │ 01/03/26 │ ⚠Venc. │ ⋮ │
│              │  │  SN-MAN-007         │          │                │          │ em 30d │   │
│              │  │  ─────────────────────────────────────────────────────────│   │
│              │  │  Balanca ref 1kg    │ Massa    │ Lab Alfa       │ 15/01/26 │ ✕Vencid│ ⋮ │
│              │  │  SN-BAL-003         │          │                │          │        │   │
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│ ━━━━━━━━━━━━ │  < 1  2 >                              Mostrando 1-25 de 38  │
│ ◻ Config.   │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

### Menu de acoes (⋮) por linha

```
┌──────────────────────────┐
│ Ver detalhe              │
│ Editar                   │
│ Renovar certificado      │
│ Ver rastreabilidade      │
│ ────────────────────     │
│ Desativar                │  ← danger
└──────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Alert Banner (global) | `component-patterns.md #27` | `variant: danger` — padroes vencidos; aparece no topo |
| Alert Banner (linha) | Inline na celula | `variant: warning` para "vencendo em 30d" |
| Filtros | `component-patterns.md #7, #5` | Status Select, Dominio Select, Search |
| Table | `component-patterns.md #15` | Sortavel; 25 por pagina |
| Badge Status | `component-patterns.md #17` | Ver tabela de badges abaixo |
| Dropdown Actions | `component-patterns.md #3` | Menu ⋮ |
| Pagination | `component-patterns.md #24` | — |

---

## Badges de Status

| Status | Badge variant | Icone | Condicao |
|---|---|---|---|
| Vigente | `success` (verde) | ● | `data_validade >= hoje + 30 dias` |
| Vencendo | `warning` (amarelo) | ⚠ | `hoje < data_validade < hoje + 30 dias` |
| Vencido | `danger` (vermelho) | ✕ | `data_validade < hoje` |
| Inativo | `neutral` (cinza) | — | Desativado manualmente |

---

## Campos da tabela

| Coluna | Conteudo | Sortavel |
|---|---|---|
| Modelo / NS | Modelo em bold + NS em menor abaixo | Sim |
| Dominio | Badge colorido (ver instrumentos-list.md) | Sim |
| Lab Calibrador | Nome abreviado do laboratorio que calibrou o padrao | Sim |
| Validade | DD/MM/AAAA; negrito vermelho se vencido | Sim |
| Status | Badge | Nao |
| Acoes | Menu ⋮ | — |

---

## Filtros

| Filtro | Tipo | Opcoes |
|---|---|---|
| Status | Select | Todos, Vigente, Vencendo, Vencido, Inativo |
| Dominio | Select | Todos, Dimensional, Pressao, Massa, Temperatura |
| Busca livre | Text Input | NS ou modelo |

---

## Estados

### Vazio

```
┌──────────────────────────────────────────────────┐
│              [icone scale]                       │
│      Nenhum padrao de referencia cadastrado      │
│   Cadastre os padroes do laboratorio para        │
│   comecar a executar calibracoes.                │
│              [+ Novo Padrao]                     │
└──────────────────────────────────────────────────┘
```

### Todos vigentes (sem alertas)

Banner de alerta nao aparece. Listagem normal.

### Com padroes vencidos

Banner danger no topo (permanece ate que os padroes sejam renovados ou desativados).

---

## Acessibilidade

- Alert banner de padroes vencidos com `role="alert"` — leitura imediata
- Linha de padrao vencido com `aria-label="Padrao vencido: {modelo}"`
- Badge de status com texto visivel completo
- Tabela com `<caption>Padroes de Referencia</caption>`
- Link "Ver padroes vencidos" no banner aplica filtro `?status=vencido` na listagem
