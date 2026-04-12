# Livewire Component Architecture — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.1 / G.9
> **Stack:** Laravel 13, Livewire 4, Blade, Alpine.js, Tailwind CSS 4

---

## 1. Decisao

O frontend autenticado do Kalibrium usa Livewire 4 como camada principal de interacao. Blade define estrutura e composicao visual; Alpine.js fica restrito a estado local efemero; JavaScript customizado so entra quando Livewire/Alpine nao cobrem o caso.

### 1.1. Hierarquia de componentes

| Nivel | Tipo | Exemplo | Regra |
|---|---|---|---|
| Page | Livewire full-page | `Instrumentos\IndexPage` | Uma rota/tela principal |
| Section | Livewire child | `Instrumentos\FiltersPanel` | Estado proprio e comportamento reutilizavel |
| Widget | Blade component | `x-status-badge` | Sem acesso direto a banco |
| Primitive | Blade component | `x-button`, `x-input` | Apenas apresentacao |
| Local behavior | Alpine | dropdown, disclosure, tabs locais | Sem regra de dominio |

### 1.2. Quando usar cada camada

| Necessidade | Escolha |
|---|---|
| Buscar, paginar, salvar, validar, emitir evento de dominio | Livewire |
| Renderizar componente visual sem estado de servidor | Blade component |
| Abrir/fechar menu, mostrar senha, alternar aba local | Alpine.js |
| Grafico interativo ou captura de device API | JavaScript dedicado, documentado no slice |
| Fluxo offline complexo | PWA/Service Worker definido em ADR futura |

---

## 2. Estrutura de pastas

```text
app/Livewire/
  Dashboard/
    IndexPage.php
  Instrumentos/
    IndexPage.php
    CreatePage.php
    EditPage.php
    Partials/
      FiltersPanel.php

resources/views/livewire/
  dashboard/index-page.blade.php
  instrumentos/index-page.blade.php
  instrumentos/create-page.blade.php
  instrumentos/edit-page.blade.php
  instrumentos/partials/filters-panel.blade.php

resources/views/components/
  ui/
    button.blade.php
    input.blade.php
    status-badge.blade.php
```

Regras:
- Page component termina com `Page`.
- Componentes filhos podem ficar em `Partials` quando pertencem a uma tela.
- Componentes Blade compartilhados ficam em `resources/views/components/ui`.
- Nenhum component Livewire acessa diretamente `request()` para estado persistente; usar propriedades e query string.

---

## 3. Estado e dados

### 3.1. Propriedades Livewire

- Propriedades publicas representam estado de tela, filtro, formulario ou paginacao.
- Dados sensiveis nao ficam em propriedade publica se nao precisarem ser renderizados.
- Objetos complexos de formulario usam Form Objects do Livewire quando disponivel.
- Computed properties sao preferidas para dados derivados.

### 3.2. Query string

Persistir na URL:
- filtros de listagem;
- termo de busca;
- pagina atual;
- ordenacao;
- tab principal quando impacta compartilhamento de link.

Nao persistir na URL:
- dados de formulario;
- estado de modal;
- token, segredo ou informacao sensivel;
- estado de hover/focus.

---

## 4. Comunicacao entre componentes

| Caso | Padrao |
|---|---|
| Filho avisa pai sobre mudanca | Livewire event nomeado por dominio |
| Pai atualiza filho | Propriedade passada ou key reativa |
| Toast global | evento `toast.show` |
| Notificacao em tempo real | canal de notificacao definido em `notification-patterns.md` |
| Acao de tabela em massa | page component orquestra, filhos apenas disparam intencao |

Nomes de eventos usam kebab-case:

```text
instrumento-created
os-status-updated
toast-show
```

---

## 5. Performance

- Paginar listagens no servidor.
- Lazy-load apenas secoes pesadas, nao campos essenciais da tela.
- Polling precisa de justificativa e intervalo explicito.
- Uploads usam componente dedicado, com progress bar de `interaction-patterns.md`.
- Evitar N+1 no render: preparar query no component ou service.
- Componentes com tabelas devem usar keys estaveis.

---

## 6. Testes

Todo page component com regra de negocio precisa de:
- teste de renderizacao basica;
- teste de autorizacao;
- teste da acao principal;
- teste de validacao quando houver formulario;
- teste de estado de filtro/paginacao quando houver listagem.

Detalhes em `docs/architecture/ui-testing-strategy.md`.

---

## 7. Checklist

| Pergunta | Obrigatorio |
|---|---|
| O component tem responsabilidade unica? | Sim |
| Estado compartilhavel esta na query string? | Sim |
| Estado sensivel ficou fora da URL? | Sim |
| Componente visual sem estado virou Blade component? | Sim |
| Alpine foi limitado a estado local? | Sim |
| Acoes principais tem teste? | Sim |
| Listagem usa paginacao server-side? | Sim |
