# Wireframe — Formulario de Padrao de Referencia + Cadeia de Rastreabilidade

> **Telas:** Novo Padrao / Detalhe do Padrao
> **URLs:** `/padroes/novo` | `/padroes/{padrao}`
> **Epico:** E03 — Cadastro Core
> **Stories:** E03-S04, E03-S05
> **Persona primaria:** Marcelo (gerente)
> **Role minima:** `gerente` (criacao); `tecnico` (leitura)
> **SCR-IDs:** SCR-E03-009, SCR-E03-010
> **Wireframe status:** draft

---

## Layout — Novo Padrao (`/padroes/novo`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Laboratorio > Padroes > Novo Padrao                   │
│              │                                                               │
│ ▼ Laborat.  │  Novo Padrao de Referencia                                    │
│   ◼ Padroes │                                                               │
│              │  ┌─ Identificacao do Padrao ──────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  Modelo / Descricao *                                  │  │
│              │  │  [____________________________________________]        │  │
│              │  │  ex: Bloco padrao M2 150mm Mitutoyo                   │  │
│              │  │                                                        │  │
│              │  │  Numero de Serie *          Patrimonio / Tag           │  │
│              │  │  [_______________________]  [____________________]    │  │
│              │  │                                                        │  │
│              │  │  Fabricante                  Dominio Metrologico *     │  │
│              │  │  [____________________]      [Selecionar...    ▼]    │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Certificado de Calibracao Vigente ─────────────────────┐ │
│              │  │                                                         │ │
│              │  │  Laboratorio Calibrador *                               │ │
│              │  │  [____________________________________________]         │ │
│              │  │  Nome do laboratorio que emitiu o certificado atual     │ │
│              │  │                                                         │ │
│              │  │  Numero do Certificado *    Data de Calibracao *        │ │
│              │  │  [______________________]  [__/__/____]                │ │
│              │  │                                                         │ │
│              │  │  Data de Validade *                                     │ │
│              │  │  [__/__/____]                                           │ │
│              │  │  ⚠ Alertas automaticos 30 dias antes do vencimento    │ │
│              │  │                                                         │ │
│              │  │  Arquivo do Certificado (PDF) *                         │ │
│              │  │  ┌───────────────────────────────────────────────┐    │ │
│              │  │  │  Arraste o PDF aqui ou  [Selecionar arquivo]  │    │ │
│              │  │  │  PDF, max 10MB                                │    │ │
│              │  │  └───────────────────────────────────────────────┘    │ │
│              │  │                                                         │ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  ┌─ Cadeia de Rastreabilidade ─────────────────────────────┐ │
│              │  │                                                         │ │
│              │  │  Rastreavel ao padrao anterior?                         │ │
│              │  │  [●] Sim    [ ] Nao (referencia primaria RBC)           │ │
│              │  │                                                         │ │
│              │  │  [Se Sim] Padrao de referencia anterior:                │ │
│              │  │  [Buscar padrao por NS ou modelo...          ▼]        │ │
│              │  │                                                         │ │
│              │  │  Ou adicionar rastreabilidade manual:                   │ │
│              │  │  Laboratorio de origem   Certificado   Validade         │ │
│              │  │  [___________________]  [__________]  [__/__/____]     │ │
│              │  │                                                         │ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  [Cancelar]                           [Salvar Padrao]         │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Layout — Detalhe do Padrao (`/padroes/{padrao}`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Laboratorio > Padroes > Bloco Padrao M2 150mm         │
│              │                                                               │
│ ▼ Laborat.  │  Bloco Padrao M2 150mm Mitutoyo          [Editar] [Renovar]   │
│   ◼ Padroes │  SN-BLC-001  •  Dimensional  •  ●Vigente                     │
│              │                                                               │
│              │  ┌─ Se padrao vencido: Alert Banner ───────────────────────┐ │
│              │  │ ✕ PADRAO VENCIDO — bloqueado para uso em calibracoes    │ │
│              │  │   Venceu em 15/01/2026. Renove o certificado ou         │ │
│              │  │   desative este padrao.     [Renovar Certificado]       │ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  ┌─ Tabs ──────────────────────────────────────────────────┐ │
│              │  │ [Dados]  [Certificado Vigente]  [Rastreabilidade]  [Log]│ │
│              │  └─────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Dados ---                                           │
│              │  ┌──────────────────────────────────────────────────────────┐ │
│              │  │  Modelo      Bloco Padrao M2 150mm Mitutoyo              │ │
│              │  │  Nro Serie   SN-BLC-001                                  │ │
│              │  │  Fabricante  Mitutoyo                                    │ │
│              │  │  Dominio     Dimensional                                 │ │
│              │  │  Status      ● Vigente                                   │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Certificado Vigente ---                             │
│              │  ┌──────────────────────────────────────────────────────────┐ │
│              │  │  Lab. Calibrador   INMETRO / RBC                        │ │
│              │  │  Nro Certificado   INMETRO-2026-00123                   │ │
│              │  │  Data Calibracao   15/01/2026                           │ │
│              │  │  Validade          30/06/2026    [⚠ Vence em 76 dias]  │ │
│              │  │                                                          │ │
│              │  │  [Download PDF do Certificado]                           │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
│              │  --- Aba: Rastreabilidade ---                                 │
│              │  ┌──────────────────────────────────────────────────────────┐ │
│              │  │  Bloco Padrao M2 150mm  ←─ Este padrao                  │ │
│              │  │  SN-BLC-001  •  Vigente                                  │ │
│              │  │       |                                                  │ │
│              │  │       ▼                                                  │ │
│              │  │  Padrao Ref. Dimensional  ←─ Padrao anterior             │ │
│              │  │  INMETRO / RBC Lab         Lab: INMETRO                  │ │
│              │  │  Cert: INM-2025-0099       Val: 01/07/2025               │ │
│              │  │       |                                                  │ │
│              │  │       ▼                                                  │ │
│              │  │  Referencia Primaria RBC  ←─ Topo da cadeia             │ │
│              │  │  BIPM / SI                                               │ │
│              │  └──────────────────────────────────────────────────────────┘ │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Section Header | `component-patterns.md #31` | Secoes do formulario |
| Text Input | `component-patterns.md #5` | Modelo, NS, Fabricante, Lab, Nr. Cert. |
| Select | `component-patterns.md #7` | Dominio metrologico |
| Date Picker | `component-patterns.md #11` | Data de calibracao, Data de validade |
| File Upload | `component-patterns.md #13` | Upload de PDF do certificado; drag-and-drop |
| Toggle / Radio | `component-patterns.md #9` | Rastreavel ao padrao anterior |
| Combobox | `component-patterns.md #7` | Busca de padrao anterior (rastreabilidade) |
| Alert Banner | `component-patterns.md #27` | Padrao vencido (`variant: danger`); vencendo (`variant: warning`) |
| Tabs | `component-patterns.md #23` | Dados / Certificado Vigente / Rastreabilidade / Log |
| Button primary | `component-patterns.md #1` | "Salvar Padrao" |
| Button secondary | `component-patterns.md #1` | "Cancelar", "Download PDF" |
| Button outline | `component-patterns.md #1` | "Renovar Certificado" |

---

## Campos — Identificacao

| Campo | Tipo | Obrigatorio | Validacao |
|---|---|---|---|
| Modelo / Descricao | Text Input | Sim | Max 200 chars |
| Numero de Serie | Text Input | Sim | Unicidade no tenant |
| Patrimonio / Tag | Text Input | Nao | Max 50 chars |
| Fabricante | Text Input | Nao | Max 100 chars |
| Dominio Metrologico | Select | Sim | Dimensional, Pressao, Massa, Temperatura |

---

## Campos — Certificado Vigente

| Campo | Tipo | Obrigatorio | Validacao |
|---|---|---|---|
| Laboratorio Calibrador | Text Input | Sim | Max 150 chars |
| Numero do Certificado | Text Input | Sim | Max 100 chars |
| Data de Calibracao | Date Picker | Sim | Nao pode ser futura |
| Data de Validade | Date Picker | Sim | Deve ser >= Data de calibracao |
| Arquivo PDF | File Upload | Sim | PDF, max 10MB; armazenado no GED |

---

## Campos — Cadeia de Rastreabilidade

| Campo | Tipo | Obrigatorio | Validacao |
|---|---|---|---|
| Rastreavel ao padrao anterior | Radio | Sim | Sim / Nao |
| Padrao anterior (interno) | Combobox | Condicional | Se Sim e padrao existe no sistema |
| Lab de origem (manual) | Text Input | Condicional | Se padrao nao esta no sistema |
| Certificado de origem | Text Input | Condicional | — |
| Validade da rastreabilidade | Date Picker | Condicional | — |

---

## Alerta de Vencimento

Regra disparada por evento `PadraoReferencia.vencendo` (backend):
- `data_validade <= hoje + 30 dias`: alerta de vencimento proximo
- `data_validade < hoje`: padrao bloqueado

Bloqueio de uso (aplicado em E05 — Execucao):
- Padrao vencido nao aparece na lista de selecao de bancada
- Tentativa de uso via API retorna erro 422 com mensagem clara

---

## Acessibilidade

- File upload com `aria-label="Upload do certificado PDF"`; estado de progresso com `aria-valuenow`
- Alert banner de padrao vencido com `role="alert"`
- Cadeia de rastreabilidade: representacao em arvore com `role="tree"` e `aria-level` em cada no
- Tabs com `role="tablist"`, `role="tab"`, `aria-selected`, `aria-controls`
- Date pickers com `aria-label` descritivo e formato esperado no placeholder
