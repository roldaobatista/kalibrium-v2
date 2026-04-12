# Form Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.5 / G.12
> **Dependencias:** `docs/design/component-patterns.md`, `docs/design/interaction-patterns.md`, `docs/architecture/state-management.md`

---

## 1. Decisao

Formularios usam Livewire para estado, validacao e persistencia. Alpine.js pode auxiliar mascaras e comportamento visual local. Toda validacao de seguranca e dominio acontece no servidor.

---

## 2. Tipos de formulario

| Tipo | Exemplo | Padrao |
|---|---|---|
| Curto | cadastro simples | uma tela, salvar explicito |
| Longo | ordem de servico | secoes, autosave opcional |
| Wizard | onboarding tenant | etapas com progresso |
| Tecnico | lancamento de medicao | campos grandes, unidade visivel |
| Destrutivo | revogar certificado | confirmacao critica |

---

## 3. Validacao

- Erro de campo aparece abaixo do campo.
- Erro global aparece em banner no topo do formulario.
- Toast de erro so complementa, nao substitui erro inline.
- Campo com erro usa borda `danger-600` e texto `danger-700`.
- Primeiro erro recebe focus apos submit invalido.

Formato:

```text
Label
[ input com borda danger ]
Mensagem objetiva: "Informe o numero de serie."
```

---

## 4. Acoes

| Acao | Posicao | Regra |
|---|---|---|
| Salvar | canto inferior direito ou barra fixa | primary |
| Cancelar | ao lado de salvar | secondary/ghost |
| Excluir | area separada | danger |
| Voltar | topo ou breadcrumb | link |
| Salvar rascunho | formulario longo | secondary |

Duplo submit:
- desabilitar botao durante submit;
- mostrar spinner inline se passar de 300ms;
- nao limpar campos ate confirmacao de sucesso.

---

## 5. Campos condicionais

- Campo escondido nao deve validar como obrigatorio.
- Mudanca que remove dados ja preenchidos exige aviso quando houver perda.
- Dependencias entre campos devem ser visiveis no label ou help text.

Exemplo:

```text
Tipo de certificado: [RBC]
Escopo RBC: [obrigatorio porque Tipo = RBC]
```

---

## 6. Autosave

Autosave permitido:
- OS em edicao longa;
- lancamento de medicao;
- observacoes longas;
- drafts de relatorio.

Autosave proibido:
- mudanca de permissao;
- revogacao;
- emissao de certificado;
- pagamento;
- qualquer acao irreversivel.

Usar os estados visuais de `interaction-patterns.md`.

---

## 7. Mascaras

| Campo | Mascara | Observacao |
|---|---|---|
| CPF | `000.000.000-00` | validar no servidor |
| CNPJ | `00.000.000/0000-00` | validar no servidor |
| Telefone | `(00) 00000-0000` | aceitar fixo/celular |
| CEP | `00000-000` | busca externa nao bloqueia digitacao |
| Moeda | `R$ 0,00` | valor canonico decimal |
| Medicao | conforme metodo | nao forcar casas erradas |

---

## 8. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Validacao server-side existe? | Sim |
| Erro inline aparece perto do campo? | Sim |
| Botao evita duplo submit? | Sim |
| Campos condicionais nao perdem dados em silencio? | Sim |
| Autosave e permitido para este tipo? | Sim |
| Mascara nao substitui validacao? | Sim |
