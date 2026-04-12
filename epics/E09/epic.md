# E09 — Portal do Cliente Final

## Objetivo
Implementar o portal de acesso externo para o cliente do laboratório (o empresa que manda o instrumento calibrar). O cliente consegue consultar o status do seu pedido, baixar certificados emitidos e ver o histórico dos seus instrumentos — sem ligar para o laboratório ou enviar e-mail.

## Valor entregue
Rafael (técnico de qualidade do cliente, persona identificada) entra no portal com e-mail e senha, vê todos os instrumentos da sua empresa em calibração, baixa o certificado mais recente de cada um, confere a validade e arquiva. Não depende mais de e-mail ou telefonema para saber se o certificado está pronto.

## Escopo

### Autenticação do usuário externo (REQ-FLX-005)
- Cadastro de acesso externo: laboratório envia convite por e-mail ao contato do cliente
- Contato do cliente ativa a conta com senha própria
- Login separado do sistema interno (domínio ou rota distinta — ex: `portal.kalibrium.com.br` ou `/portal`)
- Escopo de acesso limitado: somente dados da empresa do contato (nunca dados de outros clientes do laboratório)
- Entidades: Usuário externo
- Sessão com timeout configurável (default: 8 horas)

### Visão do cliente no portal
- Listagem de instrumentos da empresa com status da calibração mais recente
- Status visível em linguagem do cliente: "Em calibração", "Certificado pronto", "Aguardando retirada", "Calibração vencendo em X dias"
- Filtros: por instrumento, por status, por período

### Download de certificado (REQ-FLX-005)
- Download do certificado PDF mais recente de cada instrumento
- Histórico de certificados anteriores do mesmo instrumento
- Log de download registrado (quem baixou, quando — REQ-CMP-002)
- Verificação de autenticidade via QR code (link para página pública de verificação)

### Status do pedido em tempo real (REQ-FLX-002)
- Linha do tempo do pedido: datas de entrada, início de calibração, aprovação, emissão de certificado
- Estimativa de conclusão (quando informada pelo laboratório no agendamento)

### LGPD no portal
- Consentimentos visíveis pelo usuário externo (quais dados são coletados e para quê)
- Opt-out de notificações por e-mail/WhatsApp acessível no portal
- Direito de acesso: usuário pode exportar seus dados (FR-SEG-06 — portabilidade)

## Fora de escopo
- Pagamento online pelo portal (pós-MVP — gateway de pagamento)
- Chat com o laboratório pelo portal (pós-MVP — omnichannel)
- Upload de documentos pelo cliente (pós-MVP)
- App mobile nativo (explicitamente fora do MVP — portal é web responsivo)

## Critérios de entrada
- E06 completo (certificados emitidos)
- E02 completo (gestão de usuários e papéis — Usuário externo usa auth derivada)

## Critérios de saída
- Usuário externo consegue logar com convite enviado pelo laboratório
- Certificado disponível para download imediatamente após emissão (sem delay perceptível)
- Log de download registrado a cada acesso
- Usuário externo não consegue ver dados de outro cliente do mesmo laboratório (verificado por teste de isolamento)
- Opt-out de notificação funcional pelo portal
- Página de verificação de autenticidade via QR code acessível sem login

## Stories previstas
- E09-S01 — Autenticação do usuário externo (convite, ativação, login)
- E09-S02 — Listagem de instrumentos e status de calibrações
- E09-S03 — Download de certificado + histórico por instrumento
- E09-S04 — Status do pedido em linha do tempo
- E09-S05 — LGPD no portal: consentimentos, opt-out, portabilidade
- E09-S06 — Página pública de verificação de autenticidade (QR code)

## Dependências
- E06 (certificados emitidos)
- E02 (auth — base de Usuário externo)

## Riscos
- Isolamento de dados do usuário externo é crítico: acesso indevido entre clientes de um mesmo laboratório é incidente severo — testes de isolamento obrigatórios
- Portal responsivo precisa funcionar bem em mobile (cliente acessa pelo celular) — testar em viewport 375px

## Complexidade estimada
- Stories: 6
- Complexidade relativa: média
- Duração estimada: 1-2 semanas
