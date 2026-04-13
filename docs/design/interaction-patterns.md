# Interaction Patterns — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.1
> **Data:** 2026-04-12
> **Stack:** Laravel 13, Livewire 4, Tailwind CSS 4, Alpine.js
> **Dependencias:** `docs/design/style-guide.md` v1.0.0, `docs/design/component-patterns.md` v1.0.0
> **Documento:** B.3 do Design System

---

## Convencoes deste documento

- **Componentes** referenciados por nome e numero conforme `component-patterns.md` (ex: "Toast (#26)").
- **Tokens** referenciados conforme `style-guide.md` (cores, tipografia, espacamento).
- **Personas** referenciadas conforme `docs/product/personas.md`: Marcelo (gerente), Juliana (tecnica), Rafael (cliente).
- Para cada padrao: **quando usar**, **quando NAO usar**, **wireframe ASCII**, **acessibilidade**.

---

## Indice

1. [Loading States](#1-loading-states)
2. [Empty States](#2-empty-states)
3. [Error States](#3-error-states)
4. [Success Feedback](#4-success-feedback)
5. [Confirmacao Destrutiva](#5-confirmacao-destrutiva)
6. [Auto-save](#6-auto-save)
7. [Optimistic UI](#7-optimistic-ui)
8. [Drag & Drop](#8-drag--drop)
9. [Paginacao Incremental vs Scroll Automatico](#9-paginacao-incremental-vs-scroll-automatico)
10. [Keyboard Shortcuts](#10-keyboard-shortcuts)
11. [Offline Behavior](#11-offline-behavior)

---

## 1. Loading States

Feedback visual durante operacoes assincronas. O objetivo e manter a percepcao de performance e evitar que o usuario repita acoes.

### 1.1. Skeleton Loaders

Usa o componente Skeleton Loader (#21) de `component-patterns.md`.

**Quando usar:**
- Carregamento inicial de pagina (tabelas, cards, stats/KPIs).
- Navegacao entre telas via Livewire (wire:navigate).
- Qualquer area cujo conteudo depende de query ao banco.

**Quando NAO usar:**
- Acoes do usuario que demoram < 300ms (ex: toggle de checkbox).
- Recarregamento parcial de um campo especifico — usar spinner inline.
- Botoes — desabilitar o botao diretamente, nunca substituir por skeleton.

**Wireframe — Tabela:**

```
┌──────────────────────────────────────────────────────┐
│  ░░░░░░░  │  ░░░░░░░░░░░░  │  ░░░░░░  │  ░░░░░░░  │  <- header (opacity-50)
├──────────────────────────────────────────────────────┤
│  ░░░░░░░  │  ░░░░░░░░░░    │  ░░░░░░  │  ░░░░░    │  <- row 1
│  ░░░░░    │  ░░░░░░░░░░░░  │  ░░░░░░  │  ░░░░░░░  │  <- row 2
│  ░░░░░░░  │  ░░░░░░░░      │  ░░░░░░  │  ░░░░░░   │  <- row 3
│  ░░░░░    │  ░░░░░░░░░░    │  ░░░░░░  │  ░░░░░    │  <- row 4
│  ░░░░░░░  │  ░░░░░░░░░░░   │  ░░░░░░  │  ░░░░░░░  │  <- row 5
└──────────────────────────────────────────────────────┘
```

- Exibir exatamente 5 rows de skeleton, independente do page size.
- Larguras variadas por coluna (60%, 80%, 45%) para parecer natural.
- Animacao: `animate-pulse` do Tailwind (opacity 100% → 50% → 100%).

**Wireframe — Cards (grid 3 colunas):**

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│  ░░░░░░░░░░░░   │  │  ░░░░░░░░░░░░   │  │  ░░░░░░░░░░░░   │
│  ░░░░░░░░░░░░░░ │  │  ░░░░░░░░░░░░░░ │  │  ░░░░░░░░░░░░░░ │
│  ░░░░░░░░░░     │  │  ░░░░░░░░░░     │  │  ░░░░░░░░░░     │
│                  │  │                  │  │                  │
│  ░░░░░░░        │  │  ░░░░░░░        │  │  ░░░░░░░        │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

- Exibir 3 ou 6 cards skeleton conforme o grid (3 em desktop, 2 em tablet, 1 em mobile).

**Wireframe — Formulario:**

```
  ░░░░░░░░░              <- label skeleton
  ┌──────────────────┐
  │  ░░░░░░░░░░      │   <- input skeleton
  └──────────────────┘

  ░░░░░░░░░░░            <- label skeleton
  ┌──────────────────┐
  │  ░░░░░░░░        │   <- input skeleton
  └──────────────────┘
```

**Regra de timeout:** skeleton exibido por no maximo 10 segundos. Apos isso, substituir por Empty State variante `error` com botao "Tentar novamente".

**Acessibilidade:**
- Container: `role="status"`, `aria-label="Carregando dados"`.
- Elementos individuais: `aria-hidden="true"`.
- Screen readers anunciam "Carregando dados" uma vez, sem repetir a cada pulse.

### 1.2. Spinners (em botoes e paginas)

Usa o componente Spinner (#30) de `component-patterns.md`.

**Quando usar:**
- Botao apos clique que dispara acao (salvar, enviar, gerar PDF).
- Carregamento de secao parcial via Livewire (lazy loading).
- Overlay de pagina durante operacao bloqueante (ex: geracao de certificado em lote).

**Quando NAO usar:**
- Carregamento inicial de pagina — usar skeleton.
- Operacoes que levam > 5 segundos — usar progress bar.
- Listas e tabelas — usar skeleton.

**Wireframe — Botao com spinner:**

```
  Estado normal:          Estado loading:
  ┌──────────────────┐    ┌──────────────────┐
  │  💾  Salvar       │    │  ◌  Salvando...  │
  └──────────────────┘    └──────────────────┘
                          ^spinner xs (w-4 h-4)
                          ^texto muda para gerundio
                          ^botao fica disabled
                          ^cursor: not-allowed
```

- Spinner substitui o icone do botao (mesma posicao, mesmo tamanho).
- Texto muda para gerundio: "Salvar" → "Salvando...", "Enviar" → "Enviando...".
- Botao recebe `pointer-events-none opacity-75`.
- TODOS os botoes de submit do formulario ficam desabilitados (evita duplo-submit).

**Wireframe — Pagina com overlay:**

```
┌──────────────────────────────────────┐
│  ┌──────────────────────────────┐    │
│  │                              │    │
│  │        ◌                     │    │  <- spinner lg (w-12 h-12)
│  │   Gerando certificados...   │    │  <- texto descritivo
│  │   12 de 47                  │    │  <- progresso (se disponivel)
│  │                              │    │
│  └──────────────────────────────┘    │
│         ^card centralizado           │
│  ^overlay bg-white/80               │
└──────────────────────────────────────┘
```

- Overlay `bg-white/80` (nao `bg-black/50` — manter contexto visivel).
- Spinner `lg` centralizado com texto descritivo.
- Se houver informacao de progresso, exibir contagem.

**Acessibilidade:**
- Spinner: `role="status"`, `aria-label="Processando"`.
- Botao loading: `aria-disabled="true"`, `aria-busy="true"`.
- Overlay: `aria-live="polite"` para anunciar texto de progresso.

### 1.3. Progress Bar (uploads e imports)

Usa o componente Progress Bar (#29) de `component-patterns.md`.

**Quando usar:**
- Upload de arquivos (certificados, fotos de instrumentos).
- Importacao de dados (CSV de instrumentos, planilha de clientes).
- Geracao de lote de certificados (progresso conhecido).
- Qualquer operacao > 5 segundos com progresso mensuravel.

**Quando NAO usar:**
- Operacoes < 5 segundos — usar spinner.
- Progresso desconhecido — usar spinner ou progress bar `indeterminate`.
- Download de arquivos — o browser ja mostra progresso.

**Wireframe — Upload de arquivo:**

```
  ┌──────────────────────────────────────────┐
  │  📎 relatorio-calibracao.pdf             │
  │  ████████████████░░░░░░░░░░░  67%       │  <- progress bar md
  │  2.1 MB de 3.2 MB — 4s restantes        │  <- info line
  │                                [Cancelar]│
  └──────────────────────────────────────────┘
```

- Exibir: nome do arquivo, barra de progresso, bytes transferidos, tempo estimado.
- Botao "Cancelar" disponivel durante upload.
- Ao completar: barra muda para `bg-success-600`, icone check aparece.

**Wireframe — Importacao com etapas:**

```
  Importando instrumentos...
  Etapa 2 de 3: Validando dados

  ████████████████████████░░░░░░░░░  72%
  147 de 204 linhas processadas

  [Cancelar importacao]
```

- Label descreve a etapa atual.
- Contagem de itens processados (nao apenas porcentagem).
- Cancelamento disponivel (com confirmacao se > 50% processado).

**Acessibilidade:**
- `role="progressbar"`, `aria-valuenow`, `aria-valuemin="0"`, `aria-valuemax="100"`.
- `aria-label` descritivo: "Upload do arquivo relatorio-calibracao.pdf, 67 por cento concluido".
- `aria-live="polite"` no container para anunciar mudancas de etapa.

---

## 2. Empty States

Telas sem dados. Momento critico para orientar o usuario e evitar sensacao de "sistema quebrado".

### 2.1. Lista vazia (primeiro uso)

**Quando usar:**
- Primeira vez que o usuario acessa uma listagem sem nenhum registro.
- Marcelo criou o tenant mas ainda nao cadastrou instrumentos/clientes/padroes.

**Quando NAO usar:**
- Se ha registros mas o filtro nao retornou nada — usar variante `filter`.
- Se houve erro ao carregar — usar variante `error`.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    📋                                │  <- icone contextual
│                                                      │  <- w-16 h-16, text-neutral-300
│           Nenhum instrumento cadastrado              │  <- text-xl font-semibold
│                                                      │
│     Cadastre o primeiro instrumento do laboratorio   │  <- text-sm text-neutral-500
│     para comecar a registrar calibracoes.            │
│                                                      │
│              [+ Novo Instrumento]                    │  <- button primary md
│                                                      │
│     Ou importe de uma planilha  [Importar CSV]       │  <- link secundario
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Icones por contexto:**

| Tela | Icone | Titulo | CTA |
|---|---|---|---|
| Instrumentos | clipboard-list | Nenhum instrumento cadastrado | + Novo Instrumento |
| Clientes | users | Nenhum cliente cadastrado | + Novo Cliente |
| Ordens de servico | document-text | Nenhuma OS encontrada | + Nova OS |
| Certificados | document-check | Nenhum certificado gerado | Criar certificado |
| Padroes | beaker | Nenhum padrao cadastrado | + Novo Padrao |
| Procedimentos | book-open | Nenhum procedimento cadastrado | + Novo Procedimento |

### 2.2. Busca sem resultados

**Quando usar:**
- Usuario digitou termo de busca e nenhum registro corresponde.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    🔍                                │  <- icone busca
│                                                      │
│      Nenhum resultado para "paquimetro digital"     │  <- texto com termo
│                                                      │
│      Tente buscar com outros termos ou verifique    │
│      a ortografia.                                   │
│                                                      │
│              [Limpar busca]                          │  <- button ghost
│                                                      │
└──────────────────────────────────────────────────────┘
```

- O termo buscado aparece entre aspas no texto.
- Botao "Limpar busca" reseta o campo e recarrega a lista completa.
- NAO exibir CTA de "Novo registro" neste caso (o usuario esta buscando algo que espera existir).

### 2.3. Filtro sem resultados

**Quando usar:**
- Usuario aplicou filtros e a combinacao nao retorna registros.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    ⚙                                 │  <- icone filtro
│                                                      │
│      Nenhum item corresponde aos filtros             │
│                                                      │
│      Status: "Vencido" + Periodo: "Ultimo mes"      │  <- filtros ativos
│                                                      │
│              [Limpar filtros]                        │  <- button ghost
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Exibir os filtros ativos para que o usuario saiba o que remover.
- "Limpar filtros" remove TODOS os filtros (nao um por um).

### 2.4. Portal do cliente vazio (Rafael)

**Quando usar:**
- Rafael (persona 3) acessa o portal e nao tem certificados ainda.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    📄                                │
│                                                      │
│         Voce ainda nao tem certificados             │
│                                                      │
│     Quando o laboratorio finalizar as calibracoes,  │
│     seus certificados aparecerao aqui               │
│     automaticamente.                                 │
│                                                      │
│     Duvidas? Entre em contato com o laboratorio.    │
│              [Falar com o laboratorio]               │  <- link externo ou mailto
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Tom tranquilizador — Rafael nao precisa fazer nada, so aguardar.
- SEM botao de "criar" — Rafael nao cria certificados.

**Acessibilidade (todos os empty states):**
- Container: `role="status"`.
- Icone: `aria-hidden="true"`.
- CTA acessivel por teclado (Tab + Enter).
- Texto descritivo suficiente para screen readers (sem depender apenas do icone).

---

## 3. Error States

Feedback quando algo deu errado. O tom deve ser tecnico-profissional sem ser alarmista.

### 3.1. Erro inline (campo de formulario)

**Quando usar:**
- Validacao de campo falhou (required, formato, range).
- Validacao server-side retornou erro para campo especifico.

**Quando NAO usar:**
- Erro geral da requisicao (500) — usar page-level.
- Erro de negocio que afeta multiplos campos — usar Alert Banner (#27).

**Wireframe:**

```
  Numero de serie *
  ┌──────────────────────────────────┐
  │  ABC-123                         │  <- border-danger-500, ring-danger-500/20
  └──────────────────────────────────┘
  ✕ Este numero de serie ja esta cadastrado.   <- text-danger-600, text-sm
```

- Borda do input muda para `border-danger-500`.
- Ring de foco muda para `ring-danger-500/20`.
- Mensagem de erro abaixo do campo: `text-sm text-danger-600`.
- Icone `x-circle` antes da mensagem (opcional, melhora escaneabilidade).
- Mensagem descreve o problema E sugere correcao quando possivel.

**Mensagens boas vs ruins:**

| Ruim | Bom |
|---|---|
| "Campo invalido" | "O numero de serie deve ter entre 5 e 20 caracteres" |
| "Erro de validacao" | "Este e-mail ja esta cadastrado. Usar outro?" |
| "Valor fora do range" | "A temperatura deve estar entre 15.0 e 30.0 C" |

**Acessibilidade:**
- Campo com erro: `aria-invalid="true"`.
- `aria-describedby` apontando para a mensagem de erro.
- Mensagem de erro: `role="alert"` para anuncio imediato.

### 3.2. Toast de erro (acao falhou)

**Quando usar:**
- Acao do usuario falhou (salvar, excluir, enviar).
- Erro de rede temporario.
- Timeout de requisicao.

**Quando NAO usar:**
- Erro de validacao de campo — usar inline.
- Erro permanente (404, 403) — usar page-level.

**Wireframe:**

```
                              ┌──────────────────────────────────────┐
                              │  ✕  Nao foi possivel salvar o       │
                              │     certificado. Tente novamente.   │
                              │                                     │
                              │     [Tentar novamente]          [✕] │
                              └──────────────────────────────────────┘
                              ^toast error, top-4 right-4
```

- Variante `error` do Toast (#26): `border-l-4 border-danger-500`.
- NAO auto-dismiss (erros ficam visiveis ate o usuario fechar ou agir).
- Botao de acao: "Tentar novamente" quando a operacao e retry-able.
- Se o erro incluir acao alternativa: exibir link (ex: "Salvar como rascunho").

**Acessibilidade:**
- `role="alert"`, `aria-live="assertive"`.
- Screen reader anuncia imediatamente.

### 3.3. Pagina de erro (500, 404, 403)

**Quando usar:**
- Erro HTTP que impede a pagina de carregar.
- 404: URL invalida ou recurso removido.
- 403: sem permissao para acessar.
- 500: erro inesperado no servidor.

**Wireframe — 404:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    🔍                                │
│                                                      │
│                  Pagina nao encontrada               │  <- text-2xl font-bold
│                                                      │
│     O endereco que voce acessou nao existe ou foi   │  <- text-base text-neutral-500
│     removido. Verifique o link ou volte ao inicio.  │
│                                                      │
│     [Ir para o inicio]    [Voltar]                  │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Wireframe — 403:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    🔒                                │
│                                                      │
│                  Acesso restrito                     │
│                                                      │
│     Voce nao tem permissao para acessar este        │
│     recurso. Se acredita que isto e um erro,        │
│     entre em contato com o administrador.            │
│                                                      │
│     [Ir para o inicio]    [Falar com admin]         │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Wireframe — 500:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                    ⚠                                 │
│                                                      │
│                  Algo deu errado                     │
│                                                      │
│     Ocorreu um erro inesperado. Nossa equipe ja     │
│     foi notificada. Tente novamente em alguns       │
│     instantes.                                       │
│                                                      │
│     [Tentar novamente]    [Ir para o inicio]        │
│                                                      │
│     Codigo do erro: ERR-20260412-A3F2               │  <- text-xs text-neutral-400
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Codigo de erro unico (para suporte) exibido discretamente (`text-xs text-neutral-400`).
- "Tentar novamente" recarrega a pagina.
- NUNCA exibir stack trace, SQL ou detalhes tecnicos ao usuario.

### 3.4. Estado offline (tecnico em campo)

**Quando usar:**
- Juliana (persona 2) esta em campo sem internet.
- Conexao caiu durante uso do sistema.

**Wireframe — Banner offline:**

```
═══════════════════════════════════════════════════════════
  ⚠  Voce esta sem conexao. As alteracoes serao          [✕]
  sincronizadas quando a internet voltar.
═══════════════════════════════════════════════════════════
^alert banner warning, page-level, persistent (nao fecha)
```

- Banner `warning` persistente no topo da pagina.
- NAO pode ser fechado pelo usuario (persiste enquanto offline).
- Desaparece automaticamente quando conexao retorna.
- Exibe indicador de dados pendentes: "3 registros aguardando sincronizacao".

**Acessibilidade:**
- Paginas de erro: heading semantico (`<h1>`), links acessiveis.
- Banner offline: `role="alert"`, `aria-live="assertive"`.
- Paginas de erro nao devem ter apenas icone — texto descritivo obrigatorio.

---

## 4. Success Feedback

Confirmacao de que a acao do usuario foi concluida. Deve ser satisfatoria mas nao interruptiva.

### 4.1. Toast de sucesso

**Quando usar:**
- Acao CRUD concluida (salvar, criar, atualizar, excluir).
- Envio de e-mail/notificacao.
- Qualquer acao que o usuario iniciou e completou.

**Quando NAO usar:**
- Acoes automaticas (auto-save) — usar indicador sutil.
- Navegacao simples — nao precisa de feedback.

**Wireframe:**

```
                              ┌──────────────────────────────────────┐
                              │  ✓  Certificado #1247 salvo         │
                              │     com sucesso.                [✕] │
                              └──────────────────────────────────────┘
                              ^toast success, auto-dismiss 5s
```

- Variante `success` do Toast (#26): `border-l-4 border-success-500`.
- Auto-dismiss em 5 segundos.
- Hover pausa o timer.
- Mensagem especifica: incluir identificador do recurso ("Certificado #1247", nao apenas "Certificado salvo").

### 4.2. Redirect + Flash message

**Quando usar:**
- Apos criar um recurso novo (redireciona para a pagina do recurso).
- Apos excluir (redireciona para a lista).

**Wireframe:**

```
  [Redirect para /instrumentos/42]

  ┌──────────────────────────────────────────────────────┐
  │  ✓  Instrumento "Paquimetro 150mm" criado com       │  <- alert banner success
  │     sucesso.                                    [✕] │     no topo da pagina destino
  └──────────────────────────────────────────────────────┘

  Detalhes do Instrumento
  ...
```

- Flash message via sessao do Laravel (`session()->flash()`).
- Exibido como Alert Banner (#27) variante `success` no topo da pagina destino.
- Dismissible (usuario pode fechar com `x`).
- Auto-dismiss apos 8 segundos (mais longo que toast porque o redirect ja consumiu tempo).

### 4.3. Confirmacao inline

**Quando usar:**
- Acoes menores dentro de uma pagina (copiar link, marcar como lido).
- Feedback que nao precisa de toast (poluiria a tela).

**Wireframe — Copiar link:**

```
  Estado normal:                    Apos clique:
  [📋 Copiar link]                  [✓ Copiado!]
                                    ^texto muda por 2 segundos
                                    ^volta ao estado normal
```

**Wireframe — Toggle feito (dentro de tabela):**

```
  Status: [  ●───] Ativo    →    Status: [───●  ] Inativo
                                  ^badge atualiza instantaneamente
                                  ^nenhum toast necessario
```

**Acessibilidade:**
- Toast: `role="status"`, `aria-live="polite"`.
- Flash: semantica de Alert Banner.
- Inline: `aria-live="polite"` no container do texto que muda.

---

## 5. Confirmacao Destrutiva

Acoes irreversiveis exigem confirmacao explicita. O custo de um falso-negativo (impedir acao legitima) e sempre menor que o custo de um falso-positivo (destruir dados).

### 5.1. Niveis de confirmacao

| Nivel | Acao | Componente |
|---|---|---|
| **Leve** | Remover item de lista, desmarcar opcao | Nenhum (acao direta com undo via toast) |
| **Medio** | Excluir instrumento, cancelar OS | Modal confirmation (#28) com 2 botoes |
| **Critico** | Excluir tenant, revogar certificado acreditado, excluir OS com calibracoes | Modal danger (#28) com input de confirmacao |

### 5.2. Confirmacao leve (undo)

**Quando usar:**
- Acoes que podem ser desfeitas tecnicamente (soft-delete, remocao de tag).

**Wireframe:**

```
  [Usuario clica "Remover" em um tag]

  ┌──────────────────────────────────────────────────────┐
  │  ✓  Tag "Dimensional" removida.  [Desfazer]     [✕] │
  └──────────────────────────────────────────────────────┘
  ^toast success com acao "Desfazer"
  ^item some da lista imediatamente (optimistic UI)
  ^se usuario clica "Desfazer" em ate 10s: item volta
  ^apos 10s: exclusao confirmada no backend
```

### 5.3. Confirmacao media (modal simples)

**Quando usar:**
- Excluir registro que tem dependencias (instrumento com calibracoes).
- Cancelar OS em andamento.
- Revogar acesso de usuario.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   ┌──────────────────────────────────────────┐      │
│   │  Excluir instrumento                 [✕] │      │
│   │  ──────────────────────────────────────  │      │
│   │                                          │      │
│   │  Tem certeza que deseja excluir o        │      │
│   │  instrumento "Paquimetro 150mm"?         │      │
│   │                                          │      │
│   │  Esta acao ira remover tambem:           │      │
│   │  - 24 registros de calibracao            │      │  <- impacto listado
│   │  - 12 certificados associados            │      │
│   │                                          │      │
│   │  ──────────────────────────────────────  │      │
│   │               [Cancelar]  [Excluir]      │      │
│   │                            ^danger btn   │      │
│   └──────────────────────────────────────────┘      │
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Modal `sm` ou `md`.
- Listar impacto concreto (quantos registros serao afetados).
- Botao de confirmacao em variante `danger` (`bg-danger-600`).
- Botao "Cancelar" a esquerda, "Excluir" a direita.
- Focus inicial no botao "Cancelar" (nao no destrutivo).

### 5.4. Confirmacao critica (input obrigatorio)

**Quando usar:**
- Excluir tenant inteiro (todos os dados do laboratorio).
- Revogar certificado acreditado (implicacao regulatoria).
- Qualquer acao com impacto regulatorio ou financeiro irreversivel.

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   ┌──────────────────────────────────────────┐      │
│   │  ⚠ Revogar certificado #1247        [✕] │      │
│   │  ──────────────────────────────────────  │      │
│   │                                          │      │
│   │  Esta acao nao pode ser desfeita.        │      │
│   │  O certificado sera marcado como         │      │
│   │  REVOGADO permanentemente e o cliente    │      │
│   │  sera notificado automaticamente.        │      │
│   │                                          │      │
│   │  Motivo da revogacao: *                  │      │
│   │  ┌──────────────────────────────────┐   │      │
│   │  │  Selecione...                  ▼ │   │      │  <- select obrigatorio
│   │  └──────────────────────────────────┘   │      │
│   │                                          │      │
│   │  Digite "REVOGAR" para confirmar:        │      │
│   │  ┌──────────────────────────────────┐   │      │
│   │  │                                  │   │      │  <- input de confirmacao
│   │  └──────────────────────────────────┘   │      │
│   │                                          │      │
│   │  ──────────────────────────────────────  │      │
│   │        [Cancelar]  [Revogar certificado] │      │
│   │                     ^disabled ate digitar │      │
│   └──────────────────────────────────────────┘      │
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Palavra de confirmacao em CAIXA ALTA, correspondendo a acao (EXCLUIR, REVOGAR, CANCELAR).
- Botao `danger` desabilitado ate o texto exato ser digitado.
- Campo de motivo obrigatorio para rastreabilidade (auditoria Cgcre).
- `Escape` NAO fecha este modal (evitar fechamento acidental).
- Click no overlay NAO fecha este modal.

**Acessibilidade:**
- Modal: `role="alertdialog"` (nao `dialog`) para acoes destrutivas.
- `aria-describedby` no corpo da mensagem de aviso.
- Focus trap ativo.
- Input de confirmacao: `aria-label="Digite REVOGAR para confirmar"`.

---

## 6. Auto-save

Salvamento automatico para formularios longos. Evita perda de trabalho sem exigir acao explicita.

### 6.1. Quando usar

- Rascunho de Ordem de Servico (formulario com 10+ campos).
- Edicao de procedimento de calibracao (documento longo).
- Qualquer formulario que o usuario pode levar > 2 minutos para preencher.

### 6.2. Quando NAO usar

- Formularios curtos (< 5 campos) — salvar no submit e suficiente.
- Formularios com impacto imediato (criar usuario, enviar certificado) — requer acao explicita.
- Telas de busca/filtro — nao ha o que salvar.

### 6.3. Comportamento

1. **Trigger:** auto-save dispara 2 segundos apos ultima alteracao (debounce).
2. **Salvamento:** via Livewire `wire:model.blur` ou `wire:model.live.debounce.2000ms`.
3. **Status:** indicador visual no header do formulario.
4. **Versao:** cada auto-save cria versao de rascunho (nao publica).

### 6.4. Indicador visual

**Wireframe — Header do formulario com auto-save:**

```
  Nova Ordem de Servico (rascunho)
  ──────────────────────────────────────────────────────

  Estado: salvando         Estado: salvo              Estado: erro
  ◌ Salvando...            ✓ Salvo as 14:32           ⚠ Erro ao salvar
                                                       [Tentar novamente]
  ^spinner xs + texto      ^check icon + timestamp     ^warning icon + retry
  ^text-neutral-400        ^text-neutral-400            ^text-warning-600
```

**Posicao:** canto superior direito do formulario, alinhado com o titulo.

**Wireframe — Timeline de estados:**

```
  [Usuario digita]
       |
       v  (debounce 2s)
  [◌ Salvando...]
       |
       v  (resposta 200)           v  (resposta erro)
  [✓ Salvo as 14:32]          [⚠ Erro ao salvar]
       |                            |
       v  (3s)                      v  (persiste ate retry)
  [texto some]                 [⚠ Erro ao salvar] [Tentar novamente]
```

- Indicador "Salvo" some apos 3 segundos (nao polui visualmente).
- Indicador "Erro" persiste ate retry com sucesso.
- Se 3 erros consecutivos: exibir Alert Banner (#27) `warning` persistente.

### 6.5. Recuperacao de rascunho

**Wireframe — Ao abrir formulario com rascunho existente:**

```
┌──────────────────────────────────────────────────────┐
│  ℹ Existe um rascunho salvo em 12/04/2026 14:32.    │
│    [Continuar rascunho]    [Descartar e comecar novo]│
└──────────────────────────────────────────────────────┘
^alert banner info, dismissible
```

**Acessibilidade:**
- Indicador de status: `aria-live="polite"` (nao anunciar a cada save, apenas erros).
- Status textual completo para screen readers (nao depender apenas do icone).

---

## 7. Optimistic UI

Atualizar a interface antes da resposta do servidor. Da sensacao de velocidade mas exige rollback confiavel.

### 7.1. Quando usar (SIM)

| Acao | Justificativa |
|---|---|
| Toggle de status (ativo/inativo) | Operacao atomica, rollback simples |
| Marcar notificacao como lida | Baixo risco, alta frequencia |
| Adicionar/remover tag | Operacao atomica |
| Reordenar itens em lista | Visual imediato, confirmacao assincrona |
| Favoritar/desfavoritar | Operacao atomica |

### 7.2. Quando NAO usar (NUNCA)

| Acao | Justificativa |
|---|---|
| Criar registro (instrumento, OS, certificado) | Precisa de ID do servidor, validacao complexa |
| Pagamento / cobranca | Impacto financeiro irreversivel |
| Enviar certificado ao cliente | Impacto externo, nao reversivel |
| Excluir registro | Destrutivo — sempre confirmar primeiro |
| Alterar dados de calibracao | Impacto regulatorio, precisa de validacao |

### 7.3. Comportamento

**Wireframe — Toggle otimista:**

```
  Passo 1: Usuario clica toggle
  Status: [───●  ] Inativo  →  Status: [  ●───] Ativo
  ^UI atualiza IMEDIATAMENTE

  Passo 2a: Servidor confirma (200 OK)
  Status: [  ●───] Ativo   ← estado mantido, nada muda

  Passo 2b: Servidor rejeita (422/500)
  Status: [  ●───] Ativo  →  Status: [───●  ] Inativo
  ^UI reverte ao estado anterior
  ^Toast error: "Nao foi possivel alterar o status. Tente novamente."
```

**Regras de rollback:**
- Se o servidor rejeitar, reverter ao estado anterior SEM piscar (transicao suave).
- Exibir toast `error` explicando a falha.
- Desabilitar o controle por 2 segundos apos rollback (evitar clique repetido).

### 7.4. Implementacao Livewire

```
<!-- Toggle com optimistic UI via Alpine.js -->
<div x-data="{ optimistic: @entangle('status').live }">
    <button
        x-on:click="optimistic = !optimistic; $wire.toggleStatus()"
        :class="optimistic ? 'bg-success-600' : 'bg-neutral-300'"
    >
    </button>
</div>
```

- `@entangle` sincroniza Alpine (client) com Livewire (server).
- Alpine atualiza instantaneamente; Livewire confirma/reverte apos roundtrip.

**Acessibilidade:**
- Toggle: `role="switch"`, `aria-checked="true/false"`.
- Rollback: `aria-live="polite"` anuncia mudanca de estado.
- Toast de erro: `aria-live="assertive"`.

---

## 8. Drag & Drop

Interacao de arrastar e soltar para reordenacao e upload.

### 8.1. Reordenacao de itens

**Quando usar:**
- Reordenar etapas de procedimento de calibracao.
- Reordenar campos customizados de formulario.
- Priorizar itens em fila de trabalho.

**Quando NAO usar:**
- Listas ordenadas por criterio fixo (data, alfabetico) — nao faz sentido reordenar.
- Tabelas paginadas — complexidade excessiva, usuario perde referencia.
- Mobile — area de toque muito pequena para drag preciso. Usar botoes ↑/↓.

**Wireframe — Lista reordenavel:**

```
  Etapas do procedimento
  ┌──────────────────────────────────────────────┐
  │  ⠿  1. Verificacao visual do instrumento    │  <- handle (drag grip)
  ├──────────────────────────────────────────────┤
  │  ⠿  2. Limpeza e preparacao                │
  ├──────────────────────────────────────────────┤
  │  ⠿ ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄ │  <- drop zone indicator
  ├──────────────────────────────────────────────┤
  │  ⠿  3. Medicao (arrastando)       ↕        │  <- item sendo arrastado
  ├──────────────────────────────────────────────┤     (shadow-lg, bg-primary-50,
  │  ⠿  4. Calculo de incerteza                │      opacity-90)
  ├──────────────────────────────────────────────┤
  │  ⠿  5. Registro de resultados              │
  └──────────────────────────────────────────────┘
```

- Handle de drag: icone `⠿` (grip dots) a esquerda, `cursor-grab` / `cursor-grabbing`.
- Item arrastado: `shadow-lg`, `bg-primary-50`, `opacity-90`, escala levemente maior (`scale-[1.02]`).
- Drop zone: linha tracejada `border-2 border-dashed border-primary-300` no ponto de insercao.
- Apos soltar: animacao de "snap" para a posicao final (`transition-all duration-200`).
- Salvar nova ordem automaticamente (auto-save, indicador no header).

### 8.2. Upload de arquivos (drop zone)

**Quando usar:**
- Upload de certificados PDF, fotos de instrumentos, planilhas CSV.
- Componente File Upload (#13) com area de drop.

**Wireframe — Drop zone:**

```
  Estado normal:
  ┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
  │                                        │
  │         📁  Arraste arquivos aqui      │  <- border-dashed border-neutral-300
  │         ou  [Selecionar arquivo]       │     bg-neutral-50
  │                                        │
  │         PDF, JPG, PNG ate 10MB         │  <- restricoes visiveis
  └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘

  Estado hover (arquivo sobre a area):
  ┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
  │                                        │
  │         📁  Solte o arquivo aqui       │  <- border-primary-500 bg-primary-50
  │                                        │     border-2 (mais grossa)
  └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘

  Estado com arquivo:
  ┌──────────────────────────────────────────┐
  │  📎 certificado-1247.pdf    1.2 MB  [✕] │  <- arquivo selecionado
  │  ████████████████░░░░░░░░░░░  67%       │  <- progress bar (durante upload)
  └──────────────────────────────────────────┘
```

- Validacao de tipo e tamanho ANTES do upload (client-side).
- Se arquivo invalido: borda muda para `border-danger-500`, mensagem "Tipo de arquivo nao aceito".
- Multiplos arquivos: lista vertical com progresso individual.

**Acessibilidade:**
- Reordenacao: alternativa de teclado obrigatoria (botoes ↑/↓ ou `Space` para grab, `Arrow` para mover, `Space` para soltar).
- Handle: `aria-roledescription="item reordenavel"`, `aria-label="Etapa 3, Medicao. Use Space para pegar, setas para mover."`.
- Drop zone: `aria-label="Area de upload. Arraste arquivos ou pressione Enter para selecionar."`.
- File input hidden acessivel via label (`<label for="file-input">`).

---

## 9. Paginacao Incremental vs Scroll Automatico

**Decisao:** pagination e o padrao. Infinite scroll apenas em timeline/chat.

### 9.1. Pagination (padrao)

Usa o componente Pagination (#24) de `component-patterns.md`.

**Quando usar:**
- Todas as listagens e tabelas do sistema.
- Instrumentos, clientes, OS, certificados, padroes.
- Qualquer lista onde o usuario precisa de referencia de posicao ("estou na pagina 3 de 10").

**Por que:**
- Marcelo precisa de referencia de posicao ao navegar 1.400 calibracoes/mes.
- Rafael precisa encontrar um certificado especifico — paginacao + busca e mais previsivel.
- Performance do Livewire: pagination carrega page size fixo, sem acumulo de DOM.
- URL bookmarkable: `/instrumentos?page=3&per_page=25` — o usuario pode compartilhar link.

**Configuracao padrao:**

| Parametro | Valor | Notas |
|---|---|---|
| Page size padrao | 25 | Balanco entre info e performance |
| Opcoes de page size | 10, 25, 50, 100 | Seletor no rodape da tabela |
| Posicao | Rodape da tabela, alinhado a direita | Info a esquerda, controles a direita |
| Variante mobile | `compact` (`< 3/10 >`) | Economiza espaco horizontal |

### 9.2. Infinite scroll (excecao)

**Quando usar:**
- Timeline de atividades / historico de auditoria.
- Chat/mensagens com o laboratorio (portal do cliente).
- Feed de notificacoes.

**Quando NAO usar:**
- Qualquer listagem principal (instrumentos, clientes, OS, certificados).
- Tabelas de dados.
- Resultados de busca.

**Wireframe — Timeline com paginacao incremental:**

```
┌──────────────────────────────────────────┐
│  Historico de atividades                 │
│  ──────────────────────────────────────  │
│  14:32  Certificado #1247 gerado        │
│  14:28  Calibracao #892 finalizada      │
│  14:15  OS #456 iniciada                │
│  13:50  Instrumento #789 recebido       │
│  ...                                     │
│                                          │
│              [Carregar mais]             │  <- botao acessivel
│                                          │
└──────────────────────────────────────────┘
```

- Trigger padrao: usuario aciona "Carregar mais".
- Carrega proximo lote (20 itens) via Livewire.
- Spinner `sm` centralizado enquanto carrega.
- Se nao ha mais itens: "Voce chegou ao inicio do historico" (text-neutral-400).
- Botao "Voltar ao topo" (floating, canto inferior direito) apos scroll > 2 telas.

**Acessibilidade:**
- Pagination: semantica completa (`<nav>`, `aria-label`, `aria-current`).
- Lista incremental: `aria-live="polite"` no container de novos itens.
- Scroll automatico so e permitido como aprimoramento progressivo quando o botao "Carregar mais" continuar disponivel.
- "Voltar ao topo": `aria-label="Voltar ao topo da lista"`.

---

## 10. Keyboard Shortcuts

Atalhos de teclado para usuarios avancados (Marcelo, que usa o sistema o dia todo).

### 10.1. Atalhos globais

| Atalho | Acao | Contexto |
|---|---|---|
| `Ctrl+K` | Abrir busca global (command palette) | Qualquer tela |
| `Escape` | Fechar modal/dropdown/busca | Quando modal/dropdown/busca aberto |
| `?` | Abrir cheatsheet de atalhos | Qualquer tela (exceto dentro de input) |

### 10.2. Navegacao

| Atalho | Acao | Contexto |
|---|---|---|
| `G` depois `H` | Ir para Home/Dashboard | Fora de inputs |
| `G` depois `I` | Ir para Instrumentos | Fora de inputs |
| `G` depois `O` | Ir para Ordens de Servico | Fora de inputs |
| `G` depois `C` | Ir para Certificados | Fora de inputs |
| `G` depois `P` | Ir para Padroes | Fora de inputs |

### 10.3. Acoes em listagens

| Atalho | Acao | Contexto |
|---|---|---|
| `N` | Novo registro (abre form de criacao) | Tela de listagem, fora de inputs |
| `J` / `K` | Navegar para proximo/anterior item na lista | Tela de listagem |
| `Enter` | Abrir item selecionado | Item focado na lista |
| `/` | Focar campo de busca da listagem | Tela de listagem |

### 10.4. Formularios

| Atalho | Acao | Contexto |
|---|---|---|
| `Tab` | Proximo campo | Dentro de formulario |
| `Shift+Tab` | Campo anterior | Dentro de formulario |
| `Ctrl+Enter` | Submeter formulario | Dentro de formulario |
| `Escape` | Cancelar/fechar formulario (se modal) | Dentro de formulario |

### 10.5. Tabelas

| Atalho | Acao | Contexto |
|---|---|---|
| `Arrow Up/Down` | Navegar entre linhas | Tabela focada |
| `Space` | Selecionar/desselecionar linha | Linha focada |
| `Ctrl+A` | Selecionar todos (da pagina) | Tabela focada |

### 10.6. Command Palette (Ctrl+K)

**Wireframe:**

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│   ┌──────────────────────────────────────────┐      │
│   │  🔍 Buscar comandos, paginas, registros..│      │
│   ├──────────────────────────────────────────┤      │
│   │  Recentes                                │      │
│   │  📄 Certificado #1247                    │      │
│   │  📋 OS #456                              │      │
│   │  🔧 Paquimetro Digital 150mm             │      │
│   │  ──────────────────────────────────────  │      │
│   │  Acoes                                   │      │
│   │  + Novo instrumento              Ctrl+N  │      │
│   │  + Nova OS                               │      │
│   │  ⚙ Configuracoes                        │      │
│   └──────────────────────────────────────────┘      │
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Abre centralizado com overlay `bg-black/50`.
- Campo de busca com foco automatico.
- Resultados agrupados: Recentes, Paginas, Registros, Acoes.
- Navegacao por `Arrow Up/Down`, selecao por `Enter`.
- `Escape` fecha.
- Busca fuzzy: "paq 150" encontra "Paquimetro Digital 150mm".

**Quando NAO ativar atalhos:**
- Dentro de `<input>`, `<textarea>`, `<select>` — teclas sao para digitacao.
- Dentro de modal de confirmacao destrutiva — apenas `Tab` e atalhos do modal.
- Excecao: `Escape` sempre funciona para fechar.

**Acessibilidade:**
- Command palette: `role="dialog"`, `aria-label="Busca rapida"`.
- Lista de resultados: `role="listbox"`, items `role="option"`.
- Atalhos documentados no cheatsheet (`?`).
- Todos os atalhos tem equivalente clicavel (atalho e acelerador, nao unico caminho).
- `aria-keyshortcuts` nos elementos que tem atalho.

---

## 11. Offline Behavior

Juliana (persona 2) trabalha em campo, nem sempre com internet. O Kalibrium deve funcionar em modo degradado.

### 11.1. Estrategia PWA

| Camada | Tecnologia | O que cacheia |
|---|---|---|
| **App shell** | Service Worker (Workbox) | HTML, CSS, JS, fontes, icones |
| **Dados estaticos** | Cache-first | Procedimentos de calibracao, lista de padroes, unidades de medida |
| **Dados dinamicos** | Network-first, fallback cache | Lista de instrumentos, OS do dia, dados de clientes |
| **Acoes offline** | IndexedDB queue | Lancamentos de medicao, atualizacoes de status |

### 11.2. Cenarios de uso offline

**Cenario 1 — Juliana em campo (sem internet):**

```
  1. Juliana abre o app (PWA) no tablet
  2. App detecta offline → banner warning no topo
  3. Juliana acessa OS #456 (cacheada)
  4. Lanca medicoes normalmente → salvas em IndexedDB
  5. Indicador: "3 registros pendentes de sincronizacao"
  6. Internet volta → sync automatico em background
  7. Banner some → toast success: "3 registros sincronizados"
```

**Cenario 2 — Conexao cai durante uso:**

```
  1. Marcelo esta editando um instrumento
  2. Conexao cai → banner warning aparece
  3. Auto-save falha → indicador muda para "⚠ Erro ao salvar"
  4. Marcelo continua editando → dados ficam no estado local
  5. Conexao volta → auto-save retoma → "✓ Salvo as 14:32"
```

### 11.3. Indicadores visuais de modo offline

**Wireframe — Banner de conexao:**

```
  Online (normal):
  [nenhum banner — estado padrao]

  Offline:
  ═══════════════════════════════════════════════════════════
    ⚠  Sem conexao. Alteracoes serao sincronizadas         │
       quando a internet voltar.    3 pendentes            │
  ═══════════════════════════════════════════════════════════
  ^alert banner warning, page-level, persistent

  Sincronizando:
  ═══════════════════════════════════════════════════════════
    ◌  Sincronizando 3 registros...                        │
    ████████████░░░░░░░░░░░░░░  33%                       │
  ═══════════════════════════════════════════════════════════
  ^alert banner info, page-level, progress bar sm

  Conflito:
  ═══════════════════════════════════════════════════════════
    ⚠  1 registro com conflito de sincronizacao.           │
       [Resolver conflito]                                 │
  ═══════════════════════════════════════════════════════════
  ^alert banner warning, page-level, persistent + CTA
```

### 11.4. Resolucao de conflitos

**Quando ocorre:** o mesmo registro foi alterado offline por Juliana e online por Marcelo.

**Wireframe — Modal de conflito:**

```
┌──────────────────────────────────────────────────────┐
│  Conflito de sincronizacao                       [✕] │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  O registro abaixo foi alterado por outra pessoa     │
│  enquanto voce estava offline.                       │
│                                                      │
│  Campo: Temperatura ambiente                         │
│  ┌─────────────────┐  ┌─────────────────┐           │
│  │  Sua versao     │  │  Versao atual   │           │
│  │  23.5 C         │  │  23.2 C         │           │
│  │  (14:32 offline)│  │  (14:28 online) │           │
│  └─────────────────┘  └─────────────────┘           │
│                                                      │
│  [Manter minha versao]  [Aceitar versao atual]      │
│                                                      │
└──────────────────────────────────────────────────────┘
```

- Exibir ambas as versoes lado a lado.
- Nunca resolver conflito automaticamente para dados de medicao (impacto regulatorio).
- Para dados nao-criticos (status, notas): last-write-wins com log de auditoria.

### 11.5. O que NAO funciona offline

| Funcionalidade | Motivo |
|---|---|
| Gerar certificado PDF | Requer backend (calculo de incerteza, assinatura digital) |
| Criar novo cliente/instrumento | Requer validacao de unicidade no banco |
| Enviar certificado ao cliente | Requer e-mail/notificacao |
| Alterar permissoes de usuario | Requer validacao de seguranca server-side |
| Consultar historico completo | Apenas dados cacheados do dia disponíveis |

Para essas funcionalidades, exibir mensagem inline:

```
  ┌──────────────────────────────────────┐
  │  ⚠ Esta funcionalidade requer       │
  │  conexao com a internet.             │
  │  Conecte-se para continuar.          │
  └──────────────────────────────────────┘
```

**Acessibilidade:**
- Banner offline: `role="alert"`, `aria-live="assertive"`.
- Mudanca de status (offline→online): `aria-live="polite"`.
- Funcionalidades indisponiveis: `aria-disabled="true"`, tooltip explicativo.
- Conflitos: modal com `role="alertdialog"`, descricao clara das duas versoes.

---

## Apendice A — Mapa de padroes por tela

| Tela | Loading | Empty | Error | Success | Destrutivo | Auto-save | Offline |
|---|---|---|---|---|---|---|---|
| Dashboard | skeleton (cards/stats) | primeiro uso | page-level 500 | — | — | — | cache-first |
| Lista instrumentos | skeleton (tabela) | primeiro uso / busca / filtro | toast / page-level | toast | modal medio | — | cache dados do dia |
| Form instrumento | skeleton (form) | — | inline + toast | redirect + flash | — | nao (form curto) | indisponivel (criar) |
| Lista OS | skeleton (tabela) | primeiro uso / busca / filtro | toast / page-level | toast | modal medio | — | cache dados do dia |
| Form OS (novo) | — | — | inline + toast | redirect + flash | — | sim (rascunho) | queue offline |
| Form OS (editar) | skeleton (form) | — | inline + toast | toast | modal medio (cancelar) | sim | queue offline |
| Lancamento medicao | skeleton (form) | — | inline + toast | inline | — | sim | queue offline |
| Certificado | skeleton (card) | — | page-level | toast | modal critico (revogar) | — | cache PDF |
| Portal cliente | skeleton (tabela) | portal vazio | page-level | — | — | — | cache certificados |
| Timeline | skeleton (lista) | "Sem atividades" | toast | — | — | — | cache ultimas 50 |

---

## Apendice B — Decisoes de design

| Decisao | Escolha | Alternativa descartada | Justificativa |
|---|---|---|---|
| Pagination vs infinite scroll | Pagination padrao | Infinite scroll em tudo | Marcelo precisa de referencia de posicao; Livewire performa melhor com page size fixo |
| Auto-save trigger | Debounce 2s | On-blur / interval fixo | Debounce balanca responsividade e carga no servidor |
| Confirmacao destrutiva | 3 niveis | Modal unico para tudo | Acoes leves nao devem ter friccao; acoes criticas precisam de barreira proporcional |
| Optimistic UI | Whitelist explicita | Default otimista | Dados de calibracao tem impacto regulatorio; otimismo so onde o rollback e trivial |
| Offline storage | IndexedDB + Service Worker | localStorage | IndexedDB suporta dados estruturados e queries; localStorage tem limite de 5MB |
| Conflict resolution | Manual para medicoes | Last-write-wins geral | Dados de medicao tem implicacao regulatoria (Cgcre); resolver automaticamente e inaceitavel |
| Command palette | Ctrl+K | Nenhum | Acelerador para usuarios avancados (Marcelo usa 8h/dia); nao substitui navegacao normal |
| Keyboard shortcuts | Whitelist conservadora | Atalhos extensivos | Evitar conflito com atalhos do browser; so atalhos de alta frequencia |
