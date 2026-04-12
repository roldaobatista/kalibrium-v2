# State Management Strategy — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.4 / G.11

---

## 1. Decisao

O estado principal da aplicacao fica no servidor via Livewire e banco de dados. Alpine.js gerencia apenas estado local e descartavel. URL guarda estado compartilhavel de navegacao. Session/cache guardam apenas estado temporario com regra explicita.

---

## 2. Camadas de estado

| Camada | Uso | Exemplo | Persistencia |
|---|---|---|---|
| Banco | Fonte de verdade | OS, instrumento, certificado | Permanente |
| Livewire property | Estado de tela | filtro, form, ordenacao | Durante interacao |
| Query string | Link compartilhavel | `?status=issued&page=2` | URL |
| Session | Preferencia temporaria | tenant ativo | Sessao |
| Cache | Resultado derivado | KPI do dashboard | TTL explicito |
| Alpine local | UI efemera | dropdown aberto | Local, descartavel |
| IndexedDB/PWA | fila offline | medicoes pendentes | Ate sincronizar |

---

## 3. URL state

Persistir na URL:
- busca;
- filtros;
- ordenacao;
- pagina;
- tab principal;
- periodo de dashboard.

Nao persistir:
- formulario em edicao;
- dados sensiveis;
- estado de modal;
- token;
- draft offline.

---

## 4. Form state

| Tipo de formulario | Estado | Regra |
|---|---|---|
| Curto | Livewire property | salvar explicito |
| Longo | Livewire Form Object | autosave se risco de perda |
| Wizard | banco ou draft persistido | progresso por etapa |
| Medicao offline | IndexedDB + fila | sync manual/automatico |
| Busca/filtro | query string | debounce |

Unsaved changes:
- avisar ao sair se formulario longo estiver dirty;
- nao bloquear saida de formulario curto salvo explicitamente sem alteracao;
- exibir timestamp do ultimo autosave quando aplicavel.

---

## 5. Multi-tab

- Estado de formulario e tab-specific.
- Tenant ativo deve ser compartilhado por sessao.
- Logout em uma aba deve invalidar as demais no proximo request.
- Conflito de edicao deve usar regra de `interaction-patterns.md` para conflito offline/concurrente.

---

## 6. Real-time e polling

| Necessidade | Padrao inicial |
|---|---|
| Health/status leve | polling com intervalo documentado |
| Notificacao in-app | polling inicial; Reverb/WebSocket por ADR futura |
| Progresso de job | polling enquanto job esta ativo |
| Dashboard gerencial | refresh manual + cache |
| Medicao offline | sync por evento online + botao manual |

Polling nunca pode ser default sem justificativa; cada caso declara intervalo e condicao de parada.

---

## 7. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Existe uma fonte de verdade clara? | Sim |
| Estado compartilhavel esta na URL? | Sim |
| Estado sensivel ficou fora da URL/session indevida? | Sim |
| Cache tem TTL e invalidacao? | Sim |
| Polling tem intervalo e parada? | Sim |
| Multi-tab foi considerado? | Sim |
