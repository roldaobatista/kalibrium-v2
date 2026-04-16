# Wireframe — Formulario de Contato + Consentimentos LGPD

> **Tela:** Novo Contato / Editar Contato
> **URL:** Modal em `/clientes/{cliente}` ou `/clientes/{cliente}/editar`
> **Epico:** E03 — Cadastro Core
> **Story:** E03-S02
> **Persona primaria:** Marcelo (admin)
> **Role minima:** `administrativo`
> **SCR-ID:** SCR-E03-002 (sub-secao contatos)
> **Wireframe status:** draft

---

## Contexto de uso

O formulario de contato nao e uma tela separada — aparece como:
1. **Modal** ao clicar em [+ Adicionar Contato] na tela de detalhe do cliente
2. **Secao em linha** no fluxo de criacao de novo cliente (apos salvar dados da empresa)

O wireframe cobre ambos os casos. O conteudo do formulario e identico.

---

## Layout — Modal de Contato

```
┌──────────────────────────────────────────────────────────────────────────┐
│  Backdrop bg-black/50                                                    │
│                                                                          │
│    ┌─ Modal ──────────────────────────────────────────────────────────┐  │
│    │  Adicionar Contato                                        [X]    │  │
│    │  Acme Metrologia Ltda                                            │  │
│    │  ─────────────────────────────────────────────────────────────  │  │
│    │                                                                  │  │
│    │  ┌─ Dados do Contato ──────────────────────────────────────┐   │  │
│    │  │                                                          │   │  │
│    │  │  Nome completo *                                         │   │  │
│    │  │  [____________________________________________]          │   │  │
│    │  │                                                          │   │  │
│    │  │  E-mail *                                                │   │  │
│    │  │  [____________________________________________]          │   │  │
│    │  │                                                          │   │  │
│    │  │  WhatsApp (com DDD)                                      │   │  │
│    │  │  [(__) _____-_____]                                     │   │  │
│    │  │                                                          │   │  │
│    │  │  Papel / Cargo *                                         │   │  │
│    │  │  [Selecionar papel...                      ▼]           │   │  │
│    │  │                                                          │   │  │
│    │  │  Contato principal?                                      │   │  │
│    │  │  [●] Sim, este e o contato principal deste cliente       │   │  │
│    │  │                                                          │   │  │
│    │  └──────────────────────────────────────────────────────────┘   │  │
│    │                                                                  │  │
│    │  ┌─ Consentimentos LGPD ──────────────────────────────────┐    │  │
│    │  │                                                          │   │  │
│    │  │  ⓘ Registre apenas os consentimentos que o contato     │   │  │
│    │  │    forneceu de forma ativa e documentada.               │   │  │
│    │  │                                                          │   │  │
│    │  │  Canal           Consentimento      Data        Base     │   │  │
│    │  │  ─────────────────────────────────────────────────────  │   │  │
│    │  │  E-mail          [✓] Consentido     [15/04/2026] [Leg.▼]│   │  │
│    │  │  marketing       [ ] Nao consentido                     │   │  │
│    │  │                                                          │   │  │
│    │  │  WhatsApp        [ ] Consentido     [__/__/____] [   ▼] │   │  │
│    │  │  marketing       [●] Nao consentido                     │   │  │
│    │  │                                                          │   │  │
│    │  │  E-mail          [✓] Consentido     [15/04/2026] [Leg.▼]│   │  │
│    │  │  transacional    (fixo — sempre ativo para relacao       │   │  │
│    │  │                   contratual; base legal = contrato)     │   │  │
│    │  │                                                          │   │  │
│    │  └──────────────────────────────────────────────────────────┘   │  │
│    │                                                                  │  │
│    │  [Cancelar]                              [Salvar Contato]        │  │
│    │                                                                  │  │
│    └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Layout — Contatos na tela de detalhe do cliente

Aparece como secao na aba "Contatos" do detalhe do cliente:

```
┌─ Aba: Contatos ──────────────────────────────────────────────────────────┐
│                                                          [+ Adicionar]   │
│                                                                          │
│  ┌─ Card de Contato ──────────────────────────────────────────────────┐  │
│  │  [Avatar JM]  Rafael Mendes              ★ Contato principal       │  │
│  │               Comprador Tecnico                                    │  │
│  │                                                                    │  │
│  │  ✉ rafael.mendes@acme.com.br                                      │  │
│  │  📱 (11) 98765-4321                                                │  │
│  │                                                                    │  │
│  │  Consentimentos:                                                   │  │
│  │  ● E-mail marketing    Consentido em 15/04/2026                   │  │
│  │  ○ WhatsApp marketing  Nao consentido                             │  │
│  │  ● E-mail transacional Sempre ativo (contrato)                    │  │
│  │                                                                    │  │
│  │                                     [Editar]  [Remover]           │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌─ Card de Contato ──────────────────────────────────────────────────┐  │
│  │  [Avatar AS]  Ana Souza                                            │  │
│  │               Responsavel Qualidade                                │  │
│  │                                                                    │  │
│  │  ✉ ana.souza@acme.com.br                                          │  │
│  │  📱 nao informado                                                  │  │
│  │                                                                    │  │
│  │  Consentimentos:                                                   │  │
│  │  ● E-mail marketing    Consentido em 10/03/2026                   │  │
│  │  ○ WhatsApp marketing  Nao consentido                             │  │
│  │  ● E-mail transacional Sempre ativo (contrato)                    │  │
│  │                                                                    │  │
│  │                                     [Editar]  [Remover]           │  │
│  └────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Componentes

| Componente | Referencia | Detalhes |
|---|---|---|
| Modal / Dialog | `component-patterns.md #28` | Largura `max-w-lg`; focus trap; fecha com Esc ou X |
| Text Input | `component-patterns.md #5` | Nome, e-mail |
| Input com mascara | `component-patterns.md #5` | WhatsApp `(XX) XXXXX-XXXX` |
| Select | `component-patterns.md #7` | Papel do contato |
| Toggle Switch | `component-patterns.md #10` | "Contato principal" |
| Checkbox | `component-patterns.md #8` | Consentimento por canal |
| Date Picker | `component-patterns.md #11` | Data do consentimento |
| Select | `component-patterns.md #7` | Base legal do consentimento |
| Alert Banner | `component-patterns.md #27` | Info sobre LGPD (`variant: info`) |
| Card | `component-patterns.md #16` | Card de exibicao de contato |
| Avatar | `component-patterns.md #18` | Iniciais do nome em circulo |
| Button primary | `component-patterns.md #1` | "Salvar Contato" |
| Button secondary | `component-patterns.md #1` | "Cancelar" |
| Button danger | `component-patterns.md #1` | "Remover" (com confirmacao) |

---

## Campos — Dados do Contato

| Campo | Tipo | Obrigatorio | Validacao | Observacao |
|---|---|---|---|---|
| Nome completo | Text Input | Sim | Min 3, max 100 chars | — |
| E-mail | Text Input | Sim | Formato de e-mail valido | Unicidade por cliente (nao por tenant) |
| WhatsApp | Input mascara | Nao | 10 ou 11 digitos com DDD | `+55` implicito |
| Papel / Cargo | Select | Sim | Ver opcoes abaixo | — |
| Contato principal | Toggle | Nao | Apenas 1 por cliente | Se marcado, desativa o anterior |

### Opcoes de Papel

- Comprador
- Responsavel Tecnico
- Responsavel Qualidade
- Financeiro
- Direcao
- Outro

---

## Campos — Consentimentos LGPD

| Canal | Opcoes | Data | Base Legal | Observacao |
|---|---|---|---|---|
| E-mail marketing | Consentido / Nao consentido | Date picker (se consentido) | Select base legal | Data e base obrigatorios se consentido |
| WhatsApp marketing | Consentido / Nao consentido | Date picker (se consentido) | Select base legal | Exige numero de WhatsApp informado |
| E-mail transacional | Sempre ativo | — | Contrato (fixo) | Nao editavel; base legal = execucao de contrato LGPD art. 7° II |

### Opcoes de Base Legal (LGPD art. 7°)

- Consentimento (art. 7° I)
- Execucao de contrato (art. 7° II)
- Obrigacao legal (art. 7° II)
- Interesse legitimo (art. 7° IX)

---

## Validacoes especificas LGPD

| Regra | Comportamento |
|---|---|
| WhatsApp marketing consentido sem numero | Erro: "Informe o WhatsApp antes de registrar consentimento" |
| Data de consentimento no futuro | Erro: "Data de consentimento nao pode ser futura" |
| Consentido sem data | Erro: "Informe a data em que o consentimento foi coletado" |
| Consentido sem base legal | Erro: "Selecione a base legal para este consentimento" |

---

## Comportamentos

### Remover contato

```
1. Clique em [Remover]
2. Modal de confirmacao:
   "Remover Rafael Mendes?"
   "Esta acao remove o contato do cliente. O historico de
    comunicacoes anteriores permanece no audit log."
   [Cancelar]  [Remover Contato]  ← danger
```

Contato principal nao pode ser removido se for o unico contato do cliente.
Erro inline: "Adicione outro contato principal antes de remover este."

### Trocar contato principal

```
1. Marca toggle "Contato principal" em outro contato
2. Sistema exibe: "O contato [Nome Anterior] perdera o status
   de principal. Confirmar?"
3. Sim: atualiza ambos; toast success
4. Nao: toggle reverte
```

---

## Acessibilidade

- Modal: `role="dialog"`, `aria-modal="true"`, `aria-labelledby="modal-title"`
- Focus trap enquanto modal aberto; foco vai para o primeiro campo (Nome) ao abrir
- `Esc` fecha o modal (sem salvar)
- Ao fechar, foco retorna ao botao que abriu o modal
- Checkboxes de consentimento com `aria-describedby` apontando para a descricao do canal
- Toggle "Contato principal" com `aria-label="Definir como contato principal"`
- Date pickers com `aria-label="Data do consentimento para e-mail marketing"`
- Indicadores de status de consentimento nas cards: texto visivel, nao so icone/cor
