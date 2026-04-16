# Wireframe — Formulario de Instrumento (Criar / Editar)

> **Telas:** Novo Instrumento / Editar Instrumento
> **URLs:** `/instrumentos/novo` | `/instrumentos/{instrumento}/editar`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S03
> **Persona primaria:** Marcelo (admin)
> **Role minima:** `administrativo`
> **SCR-IDs:** SCR-E03-006
> **Wireframe status:** draft

---

## Layout — Novo Instrumento

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar...                               🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Instrumentos > Novo Instrumento                       │
│              │                                                               │
│ ◻ Clientes   │  Novo Instrumento                                             │
│              │                                                               │
│              │  ┌─ Vinculacao ───────────────────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  Cliente proprietario *                                │  │
│              │  │  [Buscar cliente por CNPJ ou razao social...  ▼]      │  │
│              │  │  ℹ O instrumento sera vinculado ao historico do       │  │
│              │  │    cliente selecionado.                                │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Identificacao do Instrumento ─────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  Numero de Serie *                                     │  │
│              │  │  [___________________________________]                 │  │
│              │  │                                                        │  │
│              │  │  Modelo / Descricao *                                  │  │
│              │  │  [___________________________________]                 │  │
│              │  │  ex: Paquimetro 150mm Mitutoyo 530-312                │  │
│              │  │                                                        │  │
│              │  │  Fabricante                     Ano de Fabricacao      │  │
│              │  │  [_______________________]      [____]                │  │
│              │  │                                                        │  │
│              │  │  Patrimonio / Tag interna                              │  │
│              │  │  [___________________________________]                 │  │
│              │  │  Numero interno do cliente (opcional)                  │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Dominio Metrologico ──────────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  Dominio *                                             │  │
│              │  │  ( ) Dimensional   ( ) Pressao                        │  │
│              │  │  ( ) Massa         ( ) Temperatura                    │  │
│              │  │                                                        │  │
│              │  │  Faixa de Medicao                                      │  │
│              │  │  De [_____________] Ate [_____________] [Unidade ▼]   │  │
│              │  │                                                        │  │
│              │  │  Resolucao / Menor Divisao                             │  │
│              │  │  [_____________] [Unidade ▼]                          │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Observacoes ──────────────────────────────────────────┐  │
│              │  │  [                                                   ] │  │
│              │  │  [  Observacoes tecnicas (uso interno)               ] │  │
│              │  │                                            0/500 chars │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  [Cancelar]                          [Salvar Instrumento]     │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Alerta de Numero de Serie Duplicado

Exibido inline logo apos o campo NS perder foco (`blur`):

```
┌─ Alert Banner (warning) ────────────────────────────────────────────────┐
│ ⚠ Este numero de serie ja esta vinculado a Delta Engenharia SA          │
│   Isso pode indicar transferencia de propriedade do instrumento.        │
│   [Ver cadastro existente]                    [Continuar assim mesmo]   │
└──────────────────────────────────────────────────────────────────────────┘
```

Se o NS pertence ao mesmo cliente, exibe:

```
┌─ Alert Banner (danger) ─────────────────────────────────────────────────┐
│ ✕ Instrumento com NS SN-4821 ja cadastrado para este cliente.           │
│   [Ver instrumento existente]                                            │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Section Header | `component-patterns.md #31` | "Vinculacao", "Identificacao", "Dominio Metrologico", "Observacoes" |
| Combobox | `component-patterns.md #7` | Busca de cliente; min 2 chars para buscar |
| Text Input | `component-patterns.md #5` | NS, Modelo, Fabricante, Patrimonio |
| Number Input | `component-patterns.md #14` | Ano de fabricacao, Faixa De/Ate, Resolucao |
| Radio Group | `component-patterns.md #9` | Dominio metrologico (4 opcoes) |
| Select | `component-patterns.md #7` | Unidade de medida (filtrado pelo dominio) |
| Textarea | `component-patterns.md #6` | Observacoes |
| Alert Banner | `component-patterns.md #27` | Aviso de NS duplicado |
| Button primary | `component-patterns.md #1` | "Salvar Instrumento" |
| Button secondary | `component-patterns.md #1` | "Cancelar" |

---

## Campos

| Campo | Tipo | Obrigatorio | Validacao | Observacao |
|---|---|---|---|---|
| Cliente proprietario | Combobox | Sim | Deve existir no tenant | Pre-selecionado se vindo da tela do cliente |
| Numero de Serie | Text Input | Sim | Max 100 chars; verificacao de duplicata (blur) | Identificador unico por cliente |
| Modelo / Descricao | Text Input | Sim | Max 200 chars | — |
| Fabricante | Text Input | Nao | Max 100 chars | — |
| Ano de Fabricacao | Number Input | Nao | 1900 a ano atual | — |
| Patrimonio / Tag | Text Input | Nao | Max 50 chars | Numero interno do cliente |
| Dominio Metrologico | Radio Group | Sim | 4 opcoes MVP | Determina unidades disponiveis |
| Faixa De | Number Input | Nao | Numerico | — |
| Faixa Ate | Number Input | Nao | >= Faixa De | — |
| Unidade de medida | Select | Condicional | Se faixa informada | Opcoes filtradas por dominio |
| Resolucao | Number Input | Nao | > 0 | — |
| Observacoes | Textarea | Nao | Max 500 chars | — |

### Unidades por dominio

| Dominio | Unidades disponiveis |
|---|---|
| Dimensional | mm, cm, m, pol, µm |
| Pressao | kPa, MPa, bar, psi, kgf/cm² |
| Massa | g, kg, mg, ton |
| Temperatura | °C, °F, K |

---

## Comportamento ao mudar Dominio

Ao selecionar um dominio diferente:
- Limpa os campos Faixa De, Faixa Ate, Resolucao e Unidade
- Atualiza o select de Unidade com as opcoes do novo dominio
- Nenhum toast; comportamento silencioso

---

## Estados

| Estado | Comportamento |
|---|---|
| Formulario em submissao | Botao "Salvar" em loading; campos desabilitados |
| Erro de validacao | Erros inline por campo; scroll para o primeiro campo com erro |
| Erro de servidor | Toast `danger`; dados preservados |
| Sucesso | Redireciona para `/instrumentos/{id}` com toast success |

---

## Acessibilidade

- Radio group de dominio com `role="radiogroup"`, `aria-labelledby="dominio-label"`
- Select de unidade com `aria-label="Unidade de medida da faixa"`
- Campos numericos com `inputmode="decimal"`
- Combobox de cliente com `aria-autocomplete="list"`, `aria-expanded`, `aria-activedescendant`
- Alert de NS duplicado com `role="alert"` para leitura imediata por screen reader
- Fieldsets por secao com `<legend>` semantico
