# Slice 010 — E02-S07: Base legal LGPD e consentimentos de contato

**Status:** draft
**Data de criação:** 2026-04-15
**Autor:** roldaobatista (PM) + orquestrador
**Depende de:** slice-008 (TEN-001: tenant existe), slice-009 (TEN-002: papeis gerente/administrativo existem)
**Story Contract:** `epics/E02/stories/E02-S07.md`
**ERD de referencia:** `docs/architecture/data-models/erd-e02-auth.md` (tabelas `lgpd_categories`, `consent_records` com colunas `ip_address` / `user_agent_hash` / `revocation_reason`, `consent_subjects`, `revocation_tokens`)

**Wireframe de referencia:** `docs/design/wireframes/wireframes-e02-auth.md` SCR-E02-009 (`/settings/privacy` e `/settings/privacy/consentimentos`).

---

## Contexto

O Kalibrium cadastra e processa dados pessoais de clientes, contatos e titulares de instrumentos (nome, e-mail, WhatsApp, CNPJ/CPF em alguns casos). A LGPD (Lei 13.709/2018) exige que cada categoria de dado pessoal processado tenha uma **base legal** explicitamente registrada (Art. 7 para dados pessoais gerais, Art. 11 para dados sensiveis), e que o titular consinta ativamente quando o processamento for baseado em consentimento.

Hoje o slice 009 ja cadastra usuarios internos do laboratorio com 2FA, mas ainda nao existe nenhum registro de base legal nem captura de consentimento no fluxo. Se o epico E03 (cadastro de clientes e contatos) abrir em cima desse buraco, cada cliente cadastrado nasce em violacao direta de REQ-CMP-004. Este slice fecha o E02 plugando a ausencia LGPD antes do primeiro cliente real entrar na base.

## Jornada alvo

Gerente abre o painel do laboratorio em `/settings/privacy`, declara ate 4 bases legais (execucao de contrato, obrigacao legal, interesse legitimo, consentimento) por categoria de dado pessoal (identificacao, contato, financeiro, tecnico). A declaracao fica auditavel.

Quando qualquer parte do sistema precisar capturar consentimento de um titular (um `consent_subject`), exige opt-in explicito via checkbox NAO pre-marcado para cada canal de comunicacao (e-mail, WhatsApp). O opt-in grava timestamp UTC, IP e user agent. Titular pode revogar qualquer canal a qualquer momento via link self-service sem autenticacao (token unico por revogacao). Revogacao tambem gravada em auditoria append-only. Gerente visualiza o status de consentimento de todos os `consent_subjects` no painel `/settings/privacy/consentimentos`.

**Nota sobre Contact (E03):** neste slice o `consent_subject` e a unica entidade titular. A tabela `consent_subjects` e criada aqui com fixture/factory para testes; o acoplamento com `Contact` (entidade de negocio do E03) acontece no slice que cria `Contact`, nao aqui. Isto desacopla LGPD de Cadastro e permite fechar E02 antes do E03.

## Acceptance Criteria

**Regra:** cada AC vira **pelo menos um** teste automatizado (P2). ACs nao-testaveis sao reescritos.

### Happy path

- **AC-001:** Dado gerente autenticado com 2FA, quando acessa `/settings/privacy` e marca base legal "execucao de contrato" para a categoria "contato", entao o registro fica persistido em `lgpd_categories` com `tenant_id`, `categoria=contato`, `base=execucao_contrato`, timestamp UTC e `created_by_user_id` do gerente.
- **AC-002:** Dado tenant SEM nenhuma base legal registrada, quando qualquer fluxo tenta criar `consent_subject` ou `consent_record`, entao o sistema bloqueia com HTTP 422 e mensagem "Registre a base legal LGPD em Configuracoes > LGPD antes de capturar consentimentos".
- **AC-003:** Dado tenant com base legal "consentimento" declarada, quando um `consent_subject` e criado e recebe opt-in explicito no canal `email`, entao grava `consent_records` com `consent_subject_id`, `channel=email`, `status=ativo`, timestamp UTC, `ip_address`, `user_agent`.
- **AC-004:** Dado `consent_subject` com consentimento ativo para canal `whatsapp`, quando o titular clica no link self-service enviado por e-mail e confirma revogacao, entao grava novo registro `consent_records` com `channel=whatsapp`, `status=revogado`, timestamp UTC, `ip_address`, `user_agent`, e `revocation_reason` escolhido em lista enumerada (nao texto livre).
- **AC-005:** Dado consulta `ConsentSubject::canReceiveOn('email')`, quando existe `consent_records` ativo mais recente que qualquer revogacao no mesmo canal para o mesmo subject, entao retorna `true`; caso contrario, `false`.
- **AC-006:** Dado gerente na pagina `/settings/privacy/consentimentos`, quando carrega, entao ve tabela paginada com **50 linhas por pagina** exibindo identificador do subject (sem PII crua), canal, status, data ultima mudanca, e filtro por status (`ativo` | `revogado` | `nao_informado`).
- **AC-007:** Dado um titular abre o link de revogacao com token valido, quando clica em "revogar", entao confirma revogacao em tela, registra `consent_records` com `status=revogado`, e envia e-mail de confirmacao ao titular com data/hora UTC do evento.
- **AC-008:** Dado qualquer tentativa de `UPDATE` ou `DELETE` na tabela `consent_records` por qualquer role (exceto migration role), entao o banco recusa via trigger PostgreSQL `consent_records_append_only_trigger` com mensagem "audit append-only".

### Edge cases e erros (obrigatorios)

- **AC-001a:** Dado gerente tenta marcar 5 bases legais numa mesma categoria, quando submete, entao sistema rejeita com mensagem "Maximo 4 bases por categoria" e nenhuma linha nova e criada.
- **AC-001b:** Dado gerente tenta submeter base legal sem estar autenticado com 2FA (sessao 2FA pendente), quando acessa `/settings/privacy`, entao middleware `EnsureTwoFactorAuthenticated` responde HTTP 302 com redirect para `/2fa-challenge` sem gravar.
- **AC-002a:** Dado tenant com base legal registrada mas suspenso, quando qualquer fluxo tenta criar `consent_subject`, entao bloqueia com mensagem de tenant suspenso (precede a validacao LGPD).
- **AC-003a:** Dado opt-in vem com checkbox NAO marcado, quando submete, entao nao grava `consent_records` e campo fica em estado `nao_informado`.
- **AC-003b:** Dado opt-in e gerado novamente para canal que ja tem registro ativo, quando submete, entao grava novo registro `consent_records` (append-only mantem historico — consulta `canReceiveOn` usa apenas o mais recente ordenado por `created_at DESC`).
- **AC-004a:** Dado titular tenta revogar com token cujo `revocation_tokens.expires_at` ja passou (>30 dias apos `created_at`), quando clica, entao sistema rejeita com "Link expirado. Solicite um novo" e gera novo registro `revocation_tokens` enviando novo e-mail.
- **AC-004b:** Dado titular tenta revogar canal sem consentimento ativo, quando clica, entao exibe "Voce nao tem consentimento ativo para este canal" sem gerar registro novo.
- **AC-007a:** Dado link de revogacao tem token invalido ou adulterado, quando acessado, entao retorna HTTP 404 sem revelar existencia de subjects.
- **AC-008a:** Dado tentativa de `TRUNCATE consent_records` por role de aplicacao (nao migration), quando executada, entao recusada por trigger.

### Seguranca

- **AC-SEC-001:** Dado input com HTML/JS em nome do titular ou comentario de base legal, quando submetido, entao sanitizado via `strip_tags()` antes de persistir e no render (defesa em profundidade: Blade escape cobre render, strip_tags cobre armazenamento).
- **AC-SEC-002:** Dado tenant A cadastra base legal, quando tenant B consulta `/settings/privacy`, entao NAO ve nada do tenant A (escopo global RLS + `ScopesToCurrentTenant`).
- **AC-SEC-003:** Dado payload de `consent_records`, quando inspecionado, entao NAO contem valores raw de PII (`name`, `email`, `whatsapp`, `cpf`) — apenas `consent_subject_id` (UUID opaco), canal, status, timestamps e `revocation_reason` de enum fixo.
- **AC-SEC-004:** Dado token de revogacao, quando gerado, entao persiste apenas o `token_hash` (SHA-256 de random de 32 bytes) em `revocation_tokens` com `expires_at = granted_at + 30 dias`. O valor raw do token so existe na URL enviada por e-mail, nunca persistido. A validacao de "token valido" compara o hash do input com o `token_hash` armazenado via `hash_equals`.
- **AC-SEC-005:** Dado tentativa de timing attack no endpoint `/lgpd/revoke?token=...`, quando comparado ao hash armazenado, entao comparacao e constant-time (`hash_equals`).

## Fora de escopo

- Interface do titular (cliente final) para exercer direitos LGPD complexos — acesso/retificacao/eliminacao (Art. 18 II/III/VI) sao do E09 (Portal do Cliente).
- Acoplamento com `Contact` (entidade de negocio do E03) — slice que cria `Contact` adiciona FK para `consent_subjects`.
- DPO / encarregado por dados (documental, fora do MVP).
- Relatorio de impacto a protecao de dados (RIPD).
- Exportacao automatica de dados para o titular (Art. 18.V) — slice futuro no E09.
- Integracao com Agencia Nacional de Protecao de Dados (ANPD).
- Notificacao de incidente de vazamento (Art. 48).
- Cookies e pixel tracking (sem cookies no MVP).

## Dependências externas

- **PostgreSQL 18** com suporte a triggers (append-only via `BEFORE UPDATE/DELETE OR TRUNCATE` trigger que raises exception).
- **Laravel 13 + Livewire 4** (stack ADR-0001).
- **Laravel Mail** para envio de link de revogacao (queue worker ja existe desde slice 008).
- **ADR-0004** (Fortify/Sanctum como IdP) — contem o fluxo de autenticacao que precede o cadastro de base legal.
- **Middleware `EnsureTwoFactorAuthenticated`** (existente desde slice 007) — enforce 2FA em `/settings/privacy` e `/settings/privacy/consentimentos`.
- **Tabela `consent_subjects`** criada neste slice como entidade independente. Quando E03 criar `Contact`, adicionar FK `consent_subject_id`. ERD em `docs/architecture/data-models/erd-e02-auth.md` ja preve essa relacao.
- **Nenhum ADR novo requerido.**

## Riscos conhecidos

- **Trigger append-only pode impactar migrations futuras** → mitigacao: trigger desabilitado apenas via `SET LOCAL session_replication_role = replica` em migration role, com gate de teste garantindo que nenhum `UPDATE/DELETE` direto funciona em produto (role de aplicacao).
- **Definicao juridica das bases legais por categoria** → baseline proposto neste slice; consultor juridico revisa em slice futuro se necessario, mas sem bloquear entrega.
- **Volume de consentimentos pode crescer rapido** → mitigacao: indice em `(consent_subject_id, channel, status, created_at DESC)` desde migration inicial.
- **Envio de e-mail de revogacao pode falhar silenciosamente** → mitigacao: job com retry automatico (3 tentativas), alerta ao gerente em `/settings/privacy/consentimentos` se `failed_mail_at` nao e null.
- **Enum fixo de `revocation_reason` pode nao cobrir casos reais** → baseline inclui `['automated', 'privacy_concern', 'duplicate_contact', 'no_longer_interested', 'other_without_details']`; valor `other_without_details` substitui texto livre para evitar PII vazando no audit.

## Notas do PM (humano)

- LGPD e bloqueio juridico antes do E03 (cadastro de clientes/contatos). Este slice entrega o minimo necessario para liberar E03 em conformidade.
- Apos merge, E02 fica com S08 (testes estruturais de isolamento) pendente para fechar.
- Consent_subjects e a entidade LGPD canonica. Contact (E03) acopla depois via FK, mantendo LGPD independente de cadastro.
- Sem AC de exportacao de dados para titular (LGPD Art. 18) — assumido que E09 (Portal do Cliente) tratara.
