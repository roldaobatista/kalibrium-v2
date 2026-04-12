# Notification Architecture — Kalibrium V2

> **Status:** ativo
> **Versao:** 1.0.0
> **Data:** 2026-04-12
> **Documento:** C.7 / G.16

---

## 1. Decisao

Notificacoes sao eventos de produto entregues por canais diferentes. No MVP, os canais principais sao in-app e e-mail. WhatsApp/SMS entram apenas quando houver ADR e provedor definidos.

---

## 2. Tipos

| Tipo | Canal | Exemplo |
|---|---|---|
| Feedback imediato | toast | "Certificado salvo" |
| Tarefa pendente | in-app bell/inbox | "Revisar OS" |
| Comunicacao ao cliente | e-mail | certificado emitido |
| Alerta operacional | in-app + e-mail | falha de envio |
| Digest | e-mail agrupado | resumo semanal |

---

## 3. Triggers

| Evento | Destinatario | Canal inicial |
|---|---|---|
| OS criada | tecnico/gestor | in-app |
| OS aguardando revisao | gestor | in-app + e-mail opcional |
| Certificado emitido | cliente | e-mail |
| Certificado revogado | cliente + gestor | e-mail + in-app |
| Pagamento vencido | financeiro | in-app |
| Falha de integracao | suporte interno | in-app |

Todo trigger precisa declarar:
- evento de dominio;
- destinatario;
- canal;
- template;
- regra de opt-out quando aplicavel;
- trilha de auditoria.

---

## 4. Preferencias

- Usuario pode silenciar notificacoes informativas quando nao forem obrigatorias.
- Notificacao legal/regulatoria nao deve ser silenciada sem regra de produto.
- Cliente controla preferencias de comunicacao no portal quando essa tela existir.
- E-mail transacional deve ser separado de marketing.

---

## 5. Rate limit e agrupamento

- Evitar enviar multiplos e-mails para eventos repetidos da mesma OS.
- Agrupar notificacoes informativas em digest quando possivel.
- Alertas criticos nao entram em digest.
- Falha de envio deve gerar evento interno, nao loop de retry infinito.

---

## 6. UI

| Elemento | Regra |
|---|---|
| Toast | feedback transitorio, sem historico |
| Bell | pendencias e eventos recentes |
| Inbox | historico de notificacoes relevantes |
| Badge contador | apenas nao lidas acionaveis |
| Mark as read | individual e em massa |

Toast nao substitui inbox quando a informacao precisa ser recuperada depois.

---

## 7. Entrega em tempo real

Padrao inicial:
- polling para bell/inbox;
- intervalo definido por tela;
- fallback para refresh manual.

Reverb/WebSocket:
- permitido por ADR futura;
- recomendado quando volume e experiencia justificarem;
- deve manter fallback.

---

## 8. Checklist

| Pergunta | Obrigatorio |
|---|---|
| Evento de dominio esta claro? | Sim |
| Destinatario esta definido? | Sim |
| Canal e template estao definidos? | Sim |
| Existe regra de opt-out? | Quando aplicavel |
| Existe rate limit ou agrupamento? | Sim |
| Notificacao recuperavel vai para inbox? | Sim |
| Falha de envio e auditavel? | Sim |
