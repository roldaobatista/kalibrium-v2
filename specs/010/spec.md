# Slice 010 — E02-S07: Base legal LGPD e consentimentos de contato

**Status:** draft
**Data de criação:** 2026-04-15
**Autor:** roldaobatista (PM) + orquestrador
**Depende de:** slice-008 (TEN-001: tenant existe), slice-009 (TEN-002: papeis gerente/administrativo existem)
**Story Contract:** `epics/E02/stories/E02-S07.md`

---

## Contexto

O Kalibrium cadastra e processa dados pessoais de clientes, contatos e titulares de instrumentos (nome, e-mail, WhatsApp, CNPJ/CPF em alguns casos). A LGPD (Lei 13.709/2018) exige que cada categoria de dado pessoal processado tenha uma **base legal** explicitamente registrada (Art. 7 para dados pessoais gerais, Art. 11 para dados sensiveis), e que o titular consinta ativamente quando o processamento for baseado em consentimento.

Hoje o slice 009 ja cadastra usuarios internos do laboratorio com 2FA, mas ainda nao existe nenhum registro de base legal nem captura de consentimento no fluxo. Se o epico E03 (cadastro de clientes e contatos) abrir em cima desse buraco, cada cliente cadastrado nasce em violacao direta de REQ-CMP-004. Este slice fecha o E02 plugando a ausencia LGPD antes do primeiro cliente real entrar na base.

## Jornada alvo

Gerente abre o painel do laboratorio em `/settings/lgpd`, declara ate 4 bases legais (execucao de contrato, obrigacao legal, interesse legitimo, consentimento) por categoria de dado pessoal (identificacao, contato, financeiro, tecnico). A declaracao fica auditavel.

Quando o cliente cadastrar contatos (fluxo do E03), cada canal de comunicacao (e-mail, WhatsApp) exige opt-in explicito via checkbox NAO pre-marcado. O opt-in grava timestamp UTC, IP e user agent. Contato pode revogar qualquer canal a qualquer momento via link self-service sem autenticacao (token unico por revogacao). Revogacao tambem gravada em auditoria append-only. Gerente visualiza o status de consentimento de todos os contatos no painel `/settings/lgpd/consentimentos`.

## Acceptance Criteria

**Regra:** cada AC vira **pelo menos um** teste automatizado (P2). ACs nao-testaveis sao reescritos.

### Happy path

- **AC-001:** Dado gerente autenticado com 2FA, quando acessa `/settings/lgpd` e marca base legal "execucao de contrato" para a categoria "contato", entao o registro fica persistido em `tenant_legal_bases` com `tenant_id`, `categoria=contato`, `base=execucao_contrato`, timestamp UTC e `user_id` do gerente.
- **AC-002:** Dado tenant SEM nenhuma base legal registrada, quando o gerente tenta cadastrar contato de cliente em E03 (cenario simulado), entao o sistema bloqueia com HTTP 422 e mensagem "Registre a base legal LGPD em Configuracoes > LGPD antes de cadastrar contatos".
- **AC-003:** Dado tenant com base legal "consentimento" declarada, quando gerente cadastra contato com canal `email` e marca checkbox de opt-in, entao grava `contact_consents` com `canal=email`, `status=ativo`, timestamp UTC, `ip_address`, `user_agent`.
- **AC-004:** Dado contato com consentimento ativo para canal `whatsapp`, quando o contato clica no link self-service enviado por e-mail e confirma revogacao, entao grava novo registro `contact_consents` com `status=revogado`, timestamp UTC, `ip_address`, `user_agent`, `revocation_reason` livre opcional.
- **AC-005:** Dado consulta `Contact::pode_receber_por('email')`, quando existe opt-in ativo mais recente que qualquer revogacao, entao retorna `true`; caso contrario, `false`.
- **AC-006:** Dado gerente na pagina `/settings/lgpd/consentimentos`, quando carrega, entao ve tabela paginada com contato, canal, status, data ultima mudanca, e filtro por status (ativo | revogado | nao_informado).
- **AC-007:** Dado um contato abre o link de revogacao com token valido, quando clica em "revogar", entao confirma revogacao em tela e envia e-mail de confirmacao ao contato com data/hora do evento.
- **AC-008:** Dado gerente tenta editar registro de auditoria (`PATCH/DELETE` em `contact_consents`), entao o banco recusa via trigger PostgreSQL com mensagem "audit append-only".

### Edge cases e erros (obrigatorios)

- **AC-001a:** Dado gerente tenta marcar 5 bases legais numa mesma categoria, quando submete, entao sistema rejeita com mensagem "Maximo 4 bases por categoria" e nenhuma linha nova e criada.
- **AC-001b:** Dado gerente tenta marcar base legal sem estar autenticado com 2FA (sessao 2FA pendente), quando submete, entao redireciona para `/2fa-challenge` sem gravar.
- **AC-002a:** Dado tenant com base legal registrada mas suspenso, quando gerente tenta cadastrar contato, entao bloqueia com mensagem de tenant suspenso (precede a validacao LGPD).
- **AC-003a:** Dado opt-in vem com checkbox NAO marcado, quando submete, entao nao grava `contact_consents` e campo fica em estado `nao_informado`.
- **AC-003b:** Dado opt-in e duplicado (mesmo canal ja tem registro ativo), quando submete novo opt-in, entao grava mesmo assim com `previous_consent_id` referenciando o anterior (historico completo).
- **AC-004a:** Dado contato tenta revogar com token expirado (>30 dias), quando clica, entao sistema rejeita com "Link expirado. Solicite um novo" e gera novo token enviando novo e-mail.
- **AC-004b:** Dado contato tenta revogar canal sem consentimento ativo, quando clica, entao exibe "Voce nao tem consentimento ativo para este canal" sem gerar registro novo.
- **AC-007a:** Dado link de revogacao tem token invalido ou adulterado, quando acessado, entao retorna HTTP 404 sem revelar existencia de contatos.
- **AC-008a:** Dado tentativa de `TRUNCATE contact_consents`, quando executada, entao recusada por trigger (mesmo usuario com privilegio DDL generico).

### Seguranca

- **AC-SEC-001:** Dado input com HTML/JS em `revocation_reason` ou comentario de base legal, quando submetido, entao sanitizado via `strip_tags()` antes de persistir e no render.
- **AC-SEC-002:** Dado tenant A cadastra base legal, quando tenant B consulta `/settings/lgpd`, entao NAO ve nada do tenant A (escopo global RLS + `ScopesToCurrentTenant`).
- **AC-SEC-003:** Dado payload de audit de consentimento, quando inspecionado, entao NAO contem valor raw de PII (`name`, `email`, `whatsapp`) — apenas `contact_id` hash ou id interno, canal e timestamps.
- **AC-SEC-004:** Dado token de revogacao, quando gerado, entao e hash SHA-256 de random de 32 bytes; valor raw nunca persistido, apenas hash no banco.
- **AC-SEC-005:** Dado tentativa de timing attack no endpoint `/revoke?token=...`, quando comparado ao hash armazenado, entao comparacao e constant-time.

## Fora de escopo

- Interface do titular (cliente final) para exercer direitos LGPD complexos — acesso/retificacao/eliminacao sao do E09 (Portal do Cliente).
- DPO / encarregado por dados (documental, fora do MVP).
- Relatorio de impacto a protecao de dados (RIPD) — entregavel de governanca.
- Exportacao automatica de dados para o titular (LGPD Art. 18.V) — slice futuro.
- Integracao com Agencia Nacional de Protecao de Dados (ANPD) — nao requerido no MVP.
- Notificacao de incidente de vazamento (LGPD Art. 48) — entregavel de governanca, nao do produto.
- Cookies e pixel tracking (sem cookies no MVP do Kalibrium).

## Dependências externas

- **PostgreSQL 18** com suporte a triggers (append-only via `BEFORE UPDATE/DELETE` trigger que raises exception).
- **Laravel 13 + Livewire 4** (stack ADR-0001).
- **Laravel Mail** para envio de link de revogacao (queue worker ja existe desde slice 008).
- **ADR-0004** (Fortify/Sanctum como IdP) — contem o fluxo de autenticacao que precede o cadastro de base legal.
- **Nenhum ADR novo requerido** neste slice.

## Riscos conhecidos

- **Trigger append-only pode impactar migrations futuras** → mitigacao: trigger desabilitado apenas pelo role `migrator` em `database/migrations/`, com gate de teste garantindo que nenhum `UPDATE/DELETE` direto funciona em produto.
- **Definicao juridica das bases legais por categoria** → baseline proposto neste slice; consultor juridico revisa em slice futuro se necessario, mas sem bloquear entrega.
- **Volume de consentimentos pode crescer rapido** → mitigacao: indice em `(contact_id, canal, status, created_at DESC)` desde migration inicial; paginacao default 50 linhas.
- **Envio de e-mail de revogacao pode falhar silenciosamente** → mitigacao: job com retry automatico (3 tentativas) e alerta ao gerente em `/settings/lgpd/consentimentos` se `failed_mail_at` nao e null.

## Notas do PM (humano)

- LGPD e bloqueio juridico antes do E03 (cadastro de clientes/contatos). Este slice entrega o minimo necessario para liberar E03 em conformidade.
- Apos merge, E02 fica com S08 (testes estruturais de isolamento) pendente para fechar.
- Sem AC de exportacao de dados para titular (LGPD Art. 18) — assumido que E09 (Portal do Cliente) tratara.
