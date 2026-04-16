# Wireframe — Tela de Consulta Audit Log (Cadastros)

> **Tela:** Audit Log de Cadastros
> **URL:** Embutida como aba em `/clientes/{cliente}`, `/instrumentos/{instrumento}`, `/padroes/{padrao}`, `/procedimentos/{procedimento}`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S07
> **Persona primaria:** Marcelo (gerente), Visualizador
> **Role minima:** `visualizador`
> **SCR-ID:** SCR-E03-001 a SCR-E03-012 (sub-componente transversal)
> **Wireframe status:** draft

---

## Contexto de uso

O audit log nao e uma tela separada — aparece como **aba "Historico"** ou **aba "Audit Log"** em cada entidade do modulo de cadastro. O mesmo componente e reutilizado em clientes, instrumentos, padroes e procedimentos.

---

## Layout — Aba de Audit Log em Detalhe de Cliente

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Clientes > Acme Metrologia Ltda                       │
│              │                                                               │
│ ◼ Clientes   │  Acme Metrologia Ltda                         [Editar]        │
│              │  CNPJ 12.345.678/0001-90  •  ●Ativo                          │
│              │                                                               │
│              │  ┌─ Tabs ──────────────────────────────────────────────────┐ │
│              │  │ [Dados]  [Contatos]  [Instrumentos]  [OS]  [Historico] │ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Historico (Audit Log) ---                           │
│              │                                                               │
│              │  ┌─ Filtros ─────────────────────────────────────────────────┐│
│              │  │ [Periodo ▼]  [Usuario ▼]  [Tipo de alteracao ▼]         │││
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│              │  ┌─ Timeline de Alteracoes ──────────────────────────────────┐│
│              │  │                                                           ││
│              │  │  15/04/2026 14:32  •  Marcelo Engenheiro                 ││
│              │  │  [icon edit]  Alteracao em dados da empresa               ││
│              │  │  Razao Social: "Acme Ltda" → "Acme Metrologia Ltda"     ││
│              │  │  Justificativa: "Razao social atualizada apos alteracao   ││
│              │  │  contratual registrada no CNPJ"                          ││
│              │  │  ┌─ Diff ──────────────────────────────────────────────┐ ││
│              │  │  │ - Razao Social: Acme Ltda                           │ ││
│              │  │  │ + Razao Social: Acme Metrologia Ltda                │ ││
│              │  │  └─────────────────────────────────────────────────────┘ ││
│              │  │                                                           ││
│              │  │  ─────────────────────────────────────────────────────── ││
│              │  │                                                           ││
│              │  │  10/03/2026 09:15  •  Ana Atendente                      ││
│              │  │  [icon user-plus]  Contato adicionado                     ││
│              │  │  Contato: Rafael Mendes (Comprador Tecnico)               ││
│              │  │  E-mail: rafael.mendes@acme.com.br                       ││
│              │  │  Consentimento: E-mail marketing (coletado 10/03/2026)    ││
│              │  │                                                           ││
│              │  │  ─────────────────────────────────────────────────────── ││
│              │  │                                                           ││
│              │  │  05/01/2026 11:00  •  Ana Atendente                      ││
│              │  │  [icon plus-circle]  Cadastro criado                     ││
│              │  │  CNPJ: 12.345.678/0001-90                                ││
│              │  │  Razao Social: Acme Ltda                                  ││
│              │  │                                                           ││
│              │  └───────────────────────────────────────────────────────────┘│
│              │                                                               │
│ ━━━━━━━━━━━━ │  < 1 >                              Mostrando 1-10 de 3       │
│ ◻ Config.   │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Layout — Diff expandido

Cada evento de alteracao pode ter um diff colapsavel:

```
┌─ Alteracao em dados da empresa ────────────────────────────────────────────┐
│  15/04/2026 14:32  •  Marcelo Engenheiro  •  IP 192.168.0.1               │
│                                                                             │
│  [▼ Ver detalhes]                                                          │
│                                                                             │
│  ┌─ Diff ───────────────────────────────────────────────────────────────┐  │
│  │  Campo             Antes                  Depois                     │  │
│  │  ──────────────────────────────────────────────────────────────────  │  │
│  │  Razao Social      Acme Ltda              Acme Metrologia Ltda       │  │
│  │  (sem alteracao)   Regime Tributario      Simples Nacional           │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
│  Justificativa: "Razao social atualizada apos alteracao contratual..."     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Tabs | `component-patterns.md #23` | "Historico" como uma das abas da entidade |
| Filtros | `component-patterns.md #12, #7` | Date Range Picker, Select de usuario, Select de tipo |
| Timeline / List | `component-patterns.md` | Lista de eventos com icone + timestamp + descricao |
| Accordion | `component-patterns.md #33` | Diff colapsavel por evento |
| Pagination | `component-patterns.md #24` | 10 eventos por pagina |
| Badge | `component-patterns.md #17` | Tipo de evento colorido |

---

## Tipos de Eventos no Audit Log

| Evento | Icone | Badge | Entidades |
|---|---|---|---|
| Cadastro criado | `plus-circle` | `success` | Todas |
| Dados alterados | `pencil-square` | `primary` | Todas |
| Status alterado | `arrow-path` | `warning` | Todas |
| Contato adicionado | `user-plus` | `success` | Cliente |
| Contato removido | `user-minus` | `danger` | Cliente |
| Consentimento alterado | `shield-check` | `primary` | Cliente (LGPD) |
| Instrumento vinculado | `link` | `success` | Cliente, OS |
| Padrao venceu | `exclamation-triangle` | `danger` | Padrao |
| Certificado renovado | `document-check` | `success` | Padrao |
| Nova versao criada | `arrow-up-circle` | `primary` | Procedimento |
| Desativado | `archive-box-x-mark` | `neutral` | Todas |

---

## Filtros do Audit Log

| Filtro | Tipo | Opcoes |
|---|---|---|
| Periodo | Date Range Picker | Ultimos 7d, 30d, 90d, personalizado |
| Usuario | Select | Lista de usuarios que fizeram alteracoes na entidade |
| Tipo de alteracao | Select | Ver tabela de eventos acima |

---

## Dados

- **Fonte:** `GET /api/v1/{entidade}/{id}/audit-log?page=1&period=30d`
- **Provedor:** `owen-it/laravel-auditing` (E03-S07)
- **Campos por evento:** `event_type`, `user_id`, `user_name`, `created_at`, `ip_address`, `old_values`, `new_values`, `justification`
- **Retencao:** indefinida (dados de auditoria sao imutaveis)
- **Paginacao:** 10 eventos por pagina (eventos sao verbosos)

---

## Campos por evento

| Campo | Exibicao |
|---|---|
| Timestamp | DD/MM/AAAA HH:MM — relativo abaixo em cinza ("ha 3 dias") |
| Usuario | Nome completo + avatar pequeno |
| IP Address | Visivel no diff expandido (nao na timeline principal) |
| Tipo de evento | Icone + texto descritivo |
| Campo alterado | Texto: "Campo Antes → Depois" |
| Justificativa | Texto livre; obrigatorio para campos fiscais |

---

## Estados

### Sem historico

```
┌──────────────────────────────────────────────────┐
│              [icone clock]                       │
│        Nenhuma alteracao registrada              │
│   O historico de alteracoes aparecera aqui       │
│   assim que o cadastro for modificado.           │
└──────────────────────────────────────────────────┘
```

### Carregando

- Skeleton de 3 eventos com linhas em cinza

### Sem resultados com filtro ativo

```
┌──────────────────────────────────────────────────┐
│   Nenhuma alteracao encontrada para este         │
│   periodo ou usuario.                            │
│   [Limpar filtros]                               │
└──────────────────────────────────────────────────┘
```

---

## Acessibilidade

- Timeline com `role="feed"`, cada evento com `role="article"` e `aria-labelledby`
- Timestamp com `<time datetime="2026-04-15T14:32:00">15/04/2026 14:32</time>`
- Diff colapsavel com `aria-expanded` e `aria-controls`
- Badge de tipo de evento com texto visivel (nao so icone/cor)
- Paginacao com `role="navigation"` e `aria-label="Paginacao do historico"`
- Filtros com labels associados

---

## Nota de Implementacao

O audit log usa `owen-it/laravel-auditing` configurado para registrar:
- Modelo: `Cliente`, `Contato`, `Instrumento`, `PadraoReferencia`, `Procedimento`
- Campos excluidos: `updated_at`, `created_at` (ruido)
- Campos incluidos: todos os campos de negocio
- Justificativa: armazenada em metadata customizado do evento (campo `reason`)
- IP e User Agent: capturados pelo middleware de auditoria

O componente de UI le do endpoint de API e nao tem acesso direto ao banco. A UI nao pode alterar, deletar ou exportar individualmente eventos — apenas visualizar e filtrar.
