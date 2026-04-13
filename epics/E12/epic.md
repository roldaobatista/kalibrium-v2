# E12 — Comunicação: E-mail e Notificações

## Objetivo
Implementar a infraestrutura de comunicação transversal: templates de e-mail configuráveis por evento de negócio, log de envio auditável e opt-out LGPD por canal. É o canal pelo qual todos os eventos do sistema chegam ao cliente externo (certificado pronto, NFS-e emitida, OS criada).

## Valor entregue
Quando o certificado é aprovado, o cliente do laboratório recebe automaticamente um e-mail com link de download — sem nenhuma ação manual do atendente. O laboratório pode personalizar o texto do e-mail. Toda comunicação enviada é rastreável. Cliente pode se descadastrar de e-mails de marketing sem perder notificações transacionais (certificado, cobrança).

## Escopo

### Templates de e-mail (FR-EML-01)
- Templates configuráveis por evento de negócio (Livewire admin):
  - OS criada (confirmação ao cliente)
  - Certificado emitido (link de download)
  - NFS-e autorizada (XML em anexo)
  - Lembrete de calibração vencendo (N dias antes)
  - Convite de acesso ao portal do cliente
  - Cobrança / lembrete de vencimento de título
- Variáveis dinâmicas por template (nome do cliente, número da OS, link, etc.)
- Preview do template antes de salvar
- Fallback para template padrão do sistema se tenant não configurou

### Envio transacional
- Provedor de e-mail configurável via adapter: Mailgun ou Amazon SES (plano A), SendGrid (plano B)
- Envio assíncrono via fila Laravel (nunca bloqueia a requisição HTTP)
- Retry automático com backoff exponencial em caso de falha do provedor
- Entidades: (sem entidade própria — registros no log de comunicação)

### Log de comunicação (FR-EML-03)
- Registro de toda comunicação enviada: destinatário, canal (e-mail/WhatsApp), evento de origem, timestamp, status (entregue/bounced/aberto/clicado quando provedor suportar webhook)
- Vinculação ao evento de origem (OS, certificado, NFS-e, cobrança)
- Log consultável pelo administrativo do tenant

### Opt-out LGPD por canal (FR-EML-04)
- Distinção entre comunicações transacionais (certificado, cobrança, segurança) e comunicações de marketing (newsletter, promoções)
- Link de opt-out de marketing em todo e-mail de marketing
- Opt-out registrado com timestamp e canal — respeitado imediatamente em envios futuros
- Comunicações transacionais são enviadas independentemente do opt-out de marketing

### WhatsApp (REQ-FLX-004)
- Notificações por WhatsApp opcionais: somente se cliente consentiu no cadastro
- Adapter para provedor (Z-API ou Twilio WhatsApp) — configurado por tenant
- Mesmo log de comunicação do e-mail

## Fora de escopo
- Mural corporativo interno com confirmação de leitura (FR-EML-02, P2 — pós-MVP)
- SMS (pós-MVP)
- Omnichannel / chat integrado (pós-MVP)

## Critérios de entrada
- E02 completo (auth, tenants com dados de empresa configurados)

## Critérios de saída
- E-mail enviado automaticamente ao criar OS (via fila assíncrona)
- E-mail enviado automaticamente ao emitir certificado (integração com E06)
- Log de envio registrado para todos os e-mails
- Opt-out de marketing funcionando: e-mail de marketing não enviado após opt-out (verificado por teste)
- Comunicação transacional enviada mesmo após opt-out de marketing (verificado por teste)
- Template personalizado pelo tenant aplicado no envio

## Stories previstas
- E12-S01 — Infraestrutura de envio: adapter de provedor + fila + retry
- E12-S02 — Templates de e-mail configuráveis por evento (Livewire admin)
- E12-S03 — Log de comunicação auditável
- E12-S04 — Opt-out LGPD por canal (marketing vs transacional)

## Dependências
- E02 (tenants e configuração de empresa)

## Riscos
- Credenciais de provedor de e-mail em staging podem ter rate limit baixo — usar Mailtrap em staging para captura
- WhatsApp via Z-API pode exigir número de telefone homologado — usar mock em staging

## Complexidade estimada
- Stories: 4
- Complexidade relativa: baixa
- Duração estimada: 1 semana
