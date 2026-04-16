# Wireframe — Formulario de Cliente (Criar / Editar)

> **Telas:** Novo Cliente / Editar Cliente
> **URLs:** `/clientes/novo` | `/clientes/{cliente}/editar`
> **Epico:** E03 — Cadastro Core
> **Stories:** E03-S01, E03-S02
> **Persona primaria:** Marcelo (admin)
> **Role minima:** `administrativo`
> **SCR-IDs:** SCR-E03-002, SCR-E03-004
> **Wireframe status:** draft

---

## Layout — Novo Cliente (`/clientes/novo`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 Buscar OS, cliente, instrumento...      🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │                                                               │
│ Acme Lab     │  Home > Clientes > Novo Cliente                               │
│ [logo]       │                                                               │
│              │  Novo Cliente                                                 │
│ ◼ Clientes   │                                                               │
│              │  ┌─ Dados da Empresa ─────────────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  CNPJ / CPF *                                          │  │
│              │  │  [__.___.___/____-__]      [Buscar na Receita Federal] │  │
│              │  │                                                        │  │
│              │  │  Razao Social *          Nome Fantasia                 │  │
│              │  │  [________________________] [______________________]   │  │
│              │  │                                                        │  │
│              │  │  Tipo de Pessoa *         Regime Tributario *          │  │
│              │  │  (•) PJ  ( ) PF           [Simples Nacional  ▼]       │  │
│              │  │                                                        │  │
│              │  │  Inscricao Estadual       Inscricao Municipal          │  │
│              │  │  [________________________] [______________________]   │  │
│              │  │                                                        │  │
│              │  │  Limite de Credito (R$)                                │  │
│              │  │  [R$ _____________]                                    │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Endereco ─────────────────────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  CEP *                                                 │  │
│              │  │  [_____-___]      [Buscar CEP]                        │  │
│              │  │                                                        │  │
│              │  │  Logradouro *               Numero *                  │  │
│              │  │  [________________________] [_______]                 │  │
│              │  │                                                        │  │
│              │  │  Complemento            Bairro *                      │  │
│              │  │  [____________________] [____________________]        │  │
│              │  │                                                        │  │
│              │  │  Cidade *               Estado *                      │  │
│              │  │  [____________________] [SP ▼]                       │  │
│              │  │                                                        │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  ┌─ Observacoes ──────────────────────────────────────────┐  │
│              │  │                                                        │  │
│              │  │  [                                                   ] │  │
│              │  │  [  Observacoes internas (nao visivel ao cliente)   ] │  │
│              │  │  [                                                   ] │  │
│              │  │                                            0/500 chars │  │
│              │  └────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  [Cancelar]                              [Salvar Cliente]     │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Layout — Editar Cliente (`/clientes/{cliente}/editar`)

Identico ao formulario de criacao com as diferencas:

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [K] Kalibrium       │ 🔍 ...                                     🔔 2  [MA]▼ │
├──────────────┬───────────────────────────────────────────────────────────────┤
│              │  Home > Clientes > Acme Metrologia Ltda > Editar              │
│              │                                                               │
│              │  Editar Cliente                                               │
│              │  Acme Metrologia Ltda — CNPJ 12.345.678/0001-90              │
│              │                                                               │
│              │  ┌─ Alert Banner (se hover dados fiscais) ───────────────┐   │
│              │  │ ⚠ Alteracoes em CNPJ ou Razao Social sao registradas  │   │
│              │  │   no audit log e exigem justificativa.                 │   │
│              │  └─────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  [Dados da Empresa — pre-preenchidos]                         │
│              │  [Endereco — pre-preenchido]                                  │
│              │  [Observacoes — pre-preenchidas]                              │
│              │                                                               │
│              │  ┌─ Justificativa de Alteracao (aparece se campo fiscal) ─┐  │
│              │  │  Por que voce esta alterando estes dados? *           │  │
│              │  │  [________________________________________]            │  │
│              │  └─────────────────────────────────────────────────────────┘  │
│              │                                                               │
│              │  [Cancelar]                      [Salvar Alteracoes]          │
│              │                                                               │
└──────────────┴───────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Section Header | `component-patterns.md #31` | "Dados da Empresa", "Endereco", "Observacoes" |
| Text Input | `component-patterns.md #5` | Todos os campos texto |
| Input com mascara | `component-patterns.md #5` | CNPJ/CPF, CEP, telefone |
| Select | `component-patterns.md #7` | Tipo pessoa, Regime tributario, Estado |
| Radio Group | `component-patterns.md #9` | Tipo de pessoa (PJ/PF) |
| Textarea | `component-patterns.md #6` | Observacoes, Justificativa |
| Number Input | `component-patterns.md #14` | Limite de credito (monetario) |
| Alert Banner | `component-patterns.md #27` | Aviso de dados fiscais no modo edicao |
| Button primary | `component-patterns.md #1` | "Salvar Cliente" / "Salvar Alteracoes" |
| Button secondary | `component-patterns.md #1` | "Cancelar" |

---

## Campos — Dados da Empresa

| Campo | Tipo | Obrigatorio | Validacao | Observacao |
|---|---|---|---|---|
| CNPJ / CPF | Input com mascara | Sim | Algoritmo MOD11; unicidade no tenant | Campo inteligente: muda mascara por Tipo Pessoa |
| Tipo de Pessoa | Radio | Sim | PJ / PF | Default: PJ |
| Razao Social | Text Input | Sim | Min 3 chars, max 150 | Auto-preenche via Receita Federal |
| Nome Fantasia | Text Input | Nao | Max 150 | — |
| Regime Tributario | Select | Sim (PJ) | — | Simples Nacional, Lucro Presumido, Lucro Real |
| Inscricao Estadual | Text Input | Nao | — | |
| Inscricao Municipal | Text Input | Nao | — | Para prestadores de servico |
| Limite de Credito | Number Input | Nao | >= 0, ate 2 casas decimais | Moeda BRL |
| Observacoes | Textarea | Nao | Max 500 chars | Uso interno, nao aparece no portal |

---

## Campos — Endereco

| Campo | Tipo | Obrigatorio | Validacao | Observacao |
|---|---|---|---|---|
| CEP | Input com mascara | Sim | 8 digitos, valido no ViaCEP | Auto-preenche logradouro/bairro/cidade/estado |
| Logradouro | Text Input | Sim | Max 200 | — |
| Numero | Text Input | Sim | Max 10 | — |
| Complemento | Text Input | Nao | Max 100 | — |
| Bairro | Text Input | Sim | Max 100 | — |
| Cidade | Text Input | Sim | Max 100 | Auto-preenchido pelo CEP |
| Estado | Select | Sim | UF 2 letras | Auto-preenchido pelo CEP |

---

## Comportamentos e validacoes

### Busca por CNPJ (criacao)

```
Sequencia:
1. Usuario digita CNPJ e clica "Buscar na Receita Federal"
2. Spinner no campo; botao desabilitado
3a. Sucesso: Razao Social + Endereco preenchidos automaticamente
    Toast success: "Dados preenchidos via Receita Federal"
3b. CNPJ ja cadastrado no tenant:
    Alert banner: "Cliente ja existe: [Acme Ltda] - Ver cadastro"
3c. CNPJ nao encontrado na Receita (ou API indisponivel):
    Toast warning: "Receita Federal indisponivel. Preencha manualmente."
    Campos habilitados normalmente
```

### Busca por CEP

```
Sequencia:
1. Usuario preenche CEP e sai do campo (blur)
2. Auto-chamada ao ViaCEP (sem botao)
3a. Sucesso: logradouro, bairro, cidade, estado preenchidos
3b. CEP invalido: erro inline no campo
3c. Servico indisponivel: sem preencher automaticamente; campos normais
```

### Campos fiscais no modo edicao

Quando usuario altera CNPJ, Razao Social, Regime Tributario ou IE/IM:
- Campo "Justificativa de Alteracao" aparece (obrigatorio)
- Alert banner de aviso de audit log

---

## Estados de formulario

| Estado | Comportamento |
|---|---|
| Campos validos | Border `neutral-200`, label normal |
| Campo com erro | Border `danger-500`, label `danger-600`, mensagem inline abaixo |
| Campo valido apos correcao | Border `success-500` (breve); volta a `neutral-200` |
| Campo desabilitado | `opacity-50`, cursor not-allowed (CNPJ apos criacao) |
| Formulario em submissao | Botao "Salvar" em loading; campos desabilitados |

---

## Acoes

| Acao | Tipo | Resultado |
|---|---|---|
| [Salvar Cliente] | Button primary | POST `/clientes`; redirect para `/clientes/{id}` com toast success |
| [Salvar Alteracoes] | Button primary | PUT `/clientes/{id}`; redirect para `/clientes/{id}` com toast success |
| [Cancelar] | Button secondary | Volta para `/clientes` sem salvar; sem confirmacao (nao ha dados criticos perdidos) |
| [Buscar na Receita Federal] | Button outline | GET externo; preenche campos |
| [Buscar CEP] | Automatico (blur) | GET ViaCEP; preenche endereco |

---

## Acessibilidade

- Todos os campos com `<label for="...">` associado
- Campos obrigatorios com `aria-required="true"` e indicador visual `*`
- Erros com `aria-describedby` apontando para `<p id="campo-error">`
- Fieldsets agrupam secoes relacionadas: `<fieldset><legend>Dados da Empresa</legend>`
- CNPJ/CPF: `aria-label="CNPJ ou CPF do cliente"`, `inputmode="numeric"`
- CEP: `autocomplete="postal-code"`
- Estado: `autocomplete="address-level1"`
- Limite de credito: `inputmode="decimal"`, `aria-label="Limite de credito em reais"`
- Focus inicial no campo CNPJ ao abrir a tela de criacao
