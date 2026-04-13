# API Contracts — Convenções

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Documento:** C.3
> **Escopo:** convencoes para contratos de API por epico em `docs/architecture/api-contracts/`.

---

## 1. Decisao

Contratos de API por epico serao escritos antes da implementacao das stories que expõem endpoints, acoes Livewire publicas ou payloads consumidos por telas. O formato padrao e Markdown com tabelas e exemplos JSON; OpenAPI 3.1 pode ser adicionado quando houver API externa publica.

---

## 2. Arquivos por epico

Padrao:

```text
docs/architecture/api-contracts/api-eNN-<slug>.md
```

Exemplos:

```text
api-e02-auth.md
api-e03-cadastro.md
api-e06-certificados.md
```

---

## 3. Estrutura obrigatoria

Cada arquivo de contrato deve conter:

- contexto do epico;
- lista de endpoints ou actions;
- headers obrigatorios;
- payload de request;
- payload de response;
- success status code esperado;
- codigos de erro;
- regras de paginacao, filtro e ordenacao;
- regras de autorizacao;
- exemplos de sucesso e erro;
- relacao com wireframes e ACs.

---

## 4. Formato por endpoint

```text
### GET /instrumentos

Uso: lista instrumentos do tenant atual.
Autorizacao: usuario com permissao `instrument.read`.
Headers: `Accept: application/json`, `Authorization: Bearer <token>` quando API HTTP externa exigir token.
Success: 200 OK.

Request:
| Campo | Tipo | Obrigatorio | Regra |
|---|---|---|---|
| search | string | nao | busca por identificacao, serie ou cliente |
| status | string | nao | enum documentado no epico |

Response 200:
| Campo | Tipo | Regra |
|---|---|---|
| data | array | lista paginada |
| meta | object | paginacao |
```

---

## 5. Erros padrao

| Codigo | Uso |
|---|---|
| 400 | request malformado |
| 401 | nao autenticado |
| 403 | sem permissao |
| 404 | recurso nao encontrado no tenant |
| 409 | conflito de estado |
| 422 | validacao de formulario |
| 429 | limite de requisicoes |
| 500 | erro inesperado |

Erros devem retornar mensagem segura para usuario e codigo rastreavel para suporte quando aplicavel.

---

## 6. Livewire actions

Quando a tela nao expõe endpoint HTTP publico, documentar action publica do componente:

```text
Component: App\Livewire\Instrumentos\IndexPage
Action: applyFilters()
Entrada: propriedades publicas `search`, `status`, `page`
Saida: atualiza listagem paginada
Autorizacao: `instrument.read`
```

---

## 7. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Endpoint/action esta ligado a uma tela ou AC? | Sim |
| Payload tem tipos e obrigatoriedade? | Sim |
| Erros esperados foram listados? | Sim |
| Autorizacao foi definida? | Sim |
| Paginacao/filtros/sort foram definidos quando aplicavel? | Sim |
| Exemplo de request/response existe? | Sim |
