# Table and List Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.6 / G.13
> **Dependencias:** `docs/design/component-patterns.md`, `docs/design/data-display-patterns.md`

---

## 1. Decisao

Listagens usam paginacao, filtros e ordenacao no servidor via Livewire. Infinite scroll e excecao e exige justificativa. Tabelas densas sao otimizadas para escritorio; em mobile, virar cards apenas quando a leitura tabular deixar de funcionar.

---

## 2. Paginacao

| Contexto | Page size padrao | Opcoes |
|---|---:|---|
| Lista administrativa | 25 | 10, 25, 50 |
| Dashboard compacto | 10 | 10 |
| Portal cliente | 10 | 10, 25 |
| Auditoria/timeline | 50 | 25, 50, 100 |

Regras:
- Server-side sempre.
- Page size maximo precisa de limite por tela.
- Resetar para pagina 1 quando filtro ou busca mudar.

---

## 3. Sorting

- Ordenacao default deve ser explicita por tela.
- Uma coluna primaria por vez; multi-column so com necessidade documentada.
- Ordenar pelo valor canonico, nao pelo label formatado.
- Colunas sem ordenacao nao exibem icone de sort.

---

## 4. Filtros

| Tipo de filtro | Componente |
|---|---|
| Status | select ou checkbox group |
| Data | date range |
| Cliente | autocomplete |
| Tecnico | select pesquisavel |
| Valor numerico | min/max |
| Texto livre | search input com debounce |

Regras:
- Botao "Limpar filtros" remove todos os filtros.
- Filtros ativos aparecem como chips.
- Filtros importantes persistem na query string.

---

## 5. Busca

- Debounce padrao: 300ms.
- Buscar em campos documentados por tela.
- Mostrar empty state especifico para busca sem resultado.
- Highlight pode ser usado em listas simples; evitar em tabelas muito densas.

---

## 6. Bulk actions

Bulk action so existe quando:
- a acao faz sentido para varios registros;
- permissao e validada por item;
- resultado parcial pode ser explicado ao usuario.

Fluxo:
1. usuario seleciona registros;
2. barra de bulk action aparece;
3. usuario confirma;
4. progresso e resultado parcial aparecem;
5. selecao limpa apos concluir.

---

## 7. Responsividade

| Tela | Mobile |
|---|---|
| Back-office denso | scroll horizontal controlado |
| Portal cliente | card list |
| Bancada tecnica | card list com acoes grandes |
| Auditoria | lista/timeline |

Nunca esconder coluna critica sem alternativa. Em mobile, dados secundarios podem ir para area expandida.

---

## 8. Exportacao

| Formato | Uso |
|---|---|
| CSV | dados simples e importacao externa |
| XLSX | financeiro/gestao com multiplas colunas |
| PDF | relatorio visual ou documento de leitura |

Exportacao respeita filtros ativos e deve indicar periodo, timezone e usuario que exportou quando houver auditoria.

---

## 9. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Paginacao e server-side? | Sim |
| Filtros persistem quando precisam? | Sim |
| Busca tem debounce? | Sim |
| Ordenacao usa valor canonico? | Sim |
| Bulk action tem confirmacao e resultado parcial? | Sim |
| Mobile tem alternativa legivel? | Sim |
| Exportacao informa filtros/periodo? | Sim |
