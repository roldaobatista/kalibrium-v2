# Slice 007 — SEG-001 - Login seguro do laboratorio

**Status:** draft
**Data de criação:** 2026-04-13
**Autor:** roldaobatista
**Depende de:** slice-006

---

## Contexto

Este slice abre o primeiro fluxo real de acesso do Kalibrium depois da base de infraestrutura e frontend. Ele existe para permitir que usuários internos do laboratório entrem no sistema com e-mail e senha, recuperem acesso quando esquecerem a senha e passem por 2FA quando tiverem papel crítico.

A decisão de identidade já foi tomada na ADR-0004: o MVP usará Laravel Fortify + Sanctum. O gate documental do E02 também já existe, cobrindo wireframes, contratos de API, ERD, fluxos e migrations. Este slice fica restrito ao acesso seguro; tenant setup, gestão de usuários, papéis avançados, plano e LGPD ficam para os próximos slices do E02.

## Jornada alvo

Marcelo ou Juliana acessa `/auth/login`, informa e-mail e senha, e o sistema valida as credenciais sem revelar se o e-mail existe. Se o vínculo do usuário estiver ativo e não houver exigência de 2FA, a sessão é criada e o usuário segue para `/app`.

Quando o usuário for gerente ou administrativo com 2FA exigido, o login redireciona para `/auth/two-factor-challenge`. O usuário informa código TOTP ou código de recuperação; se o código for válido, o sistema cria a sessão e registra auditoria segura do evento. Se o usuário esquecer a senha, o fluxo de recuperação usa mensagem neutra, token válido e senha mínima de 12 caracteres.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). Para cada happy path, ha pelo menos um edge case ou erro correspondente.

### Happy path

- **AC-001:** Dado um usuário ativo com tenant ativo e senha correta, quando `POST /auth/login` receber e-mail, senha e CSRF válidos, então o sistema autentica o usuário, cria sessão web e redireciona para `/app`.
- **AC-002:** Dado um usuário ativo com papel `gerente` ou `administrativo` e 2FA exigido, quando `POST /auth/login` receber credenciais válidas, então o sistema não abre `/app` diretamente e redireciona para `/auth/two-factor-challenge`.
- **AC-003:** Dado um usuário com desafio 2FA pendente e código TOTP válido, quando `POST /auth/two-factor-challenge` for enviado, então o sistema conclui a autenticação, cria sessão web e redireciona para `/app`.
- **AC-004:** Dado um usuário com desafio 2FA pendente e código de recuperação válido, quando `POST /auth/two-factor-challenge` for enviado usando `recovery_code`, então o sistema conclui a autenticação, invalida o código de recuperação usado e registra auditoria do uso.
- **AC-005:** Dado qualquer e-mail em formato válido, quando `POST /auth/forgot-password` for enviado, então o sistema responde com mensagem neutra sem revelar se o e-mail existe.
- **AC-006:** Dado um token de recuperação válido, quando `POST /auth/reset-password` receber e-mail, nova senha com pelo menos 12 caracteres e confirmação igual, então o sistema altera a senha, invalida o token e redireciona para `/auth/login` com confirmação.
- **AC-007:** Dado qualquer tentativa de login, sucesso ou falha, quando o fluxo terminar, então o sistema grava `login_audit_logs` com evento, `user_id` quando conhecido, `tenant_id` quando conhecido, IP e hash de user agent, sem persistir senha, token, segredo TOTP ou código de recuperação.

### Edge cases e erros

- **AC-008:** Dado e-mail ou senha incorretos, quando `POST /auth/login` for enviado, então o sistema retorna erro de validação com mensagem neutra e não revela se o e-mail existe.
- **AC-009:** Dado excesso de tentativas de login para o mesmo e-mail/IP dentro da janela configurada, quando `POST /auth/login` for enviado novamente, então o sistema retorna `429` e não tenta autenticar a senha.
- **AC-010:** Dado usuário com tenant `suspended` ou vínculo `suspended`, quando `POST /auth/login` receber credenciais válidas, então o sistema bloqueia acesso à sessão de aplicação e registra auditoria do bloqueio.
- **AC-011:** Dado um desafio 2FA pendente e código TOTP inválido, quando `POST /auth/two-factor-challenge` for enviado, então o sistema retorna `422`, mantém o desafio pendente e não cria sessão de aplicação.
- **AC-012:** Dado um desafio 2FA pendente e código de recuperação já usado ou inexistente, quando `POST /auth/two-factor-challenge` for enviado com `recovery_code`, então o sistema retorna `422` e não cria sessão de aplicação.
- **AC-013:** Dado senha nova com menos de 12 caracteres ou confirmação divergente, quando `POST /auth/reset-password` for enviado, então o sistema retorna `422` e não altera a senha atual.
- **AC-014:** Dado token de recuperação inválido ou expirado, quando `POST /auth/reset-password` for enviado, então o sistema retorna `422`, não altera a senha e orienta o usuário a pedir novo link.

### Segurança

- **AC-015:** Dado input de login contendo HTML, JavaScript ou payload SQL comum nos campos `email` ou `password`, quando `POST /auth/login` for enviado, então o sistema trata o input como dado, retorna erro seguro quando inválido e não executa nem reflete payload sem escape.
- **AC-016:** Dado usuário não autenticado, quando acessar rota protegida de aplicação como `/app`, então o sistema redireciona para `/auth/login` sem expor dados de tenant.
- **AC-017:** Dado usuário autenticado sem 2FA concluído quando 2FA é exigido, quando tentar acessar `/app`, então o sistema bloqueia a rota protegida e mantém o usuário no fluxo de `/auth/two-factor-challenge`.
- **AC-018:** Dado qualquer response dos fluxos de login, recuperação de senha, reset de senha ou 2FA, quando o conteúdo e logs forem inspecionados, então não aparecem senha, token de reset, segredo TOTP, código TOTP nem código de recuperação.

## Fora de escopo

- Registro público de novo laboratório.
- Tela de configuração do tenant em `/settings/tenant`.
- Cadastro de empresa e filial raiz.
- Gestão de usuários e papéis em `/settings/users`.
- Motor de planos e feature gates.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Portal do cliente final.
- UI visual final fora das telas públicas de autenticação necessárias para o fluxo.

## Dependências externas

- ADR-0004: Laravel Fortify + Sanctum como estratégia de identidade do MVP.
- ADR-0001: Laravel 13, Livewire 4, PostgreSQL 18 e PHP 8.4+.
- Gate documental E02: `docs/design/wireframes/wireframes-e02-auth.md`, `docs/architecture/api-contracts/api-e02-auth.md`, `docs/architecture/data-models/erd-e02-auth.md`, `docs/architecture/data-models/migrations-e02-auth.md` e `docs/product/flows/flows-e02-auth.md`.
- slice-006: base de frontend com Vite, Tailwind, Livewire e Alpine.
- Laravel Fortify/Sanctum.
- PostgreSQL para `users`, vínculo de tenant e `login_audit_logs`.
- Serviço de e-mail configurável para recuperação de senha; em testes, usar fake de mailer.

## Riscos conhecidos

- 2FA pode bloquear gerente legítimo se o fluxo de recuperação não for coberto -> mitigação: AC-004 e AC-012 cobrem recovery code válido e inválido.
- Mensagens de erro podem enumerar usuários -> mitigação: AC-005 e AC-008 exigem mensagem neutra.
- Auditoria pode vazar credenciais se salvar payload cru -> mitigação: AC-007 e AC-018 proíbem senha, token, segredo TOTP e recovery code em logs.
- Tenant suspenso pode entrar por credencial válida se o bloqueio ficar só na UI -> mitigação: AC-010 exige bloqueio no fluxo de autenticação.
- Rate limit mal configurado pode permitir brute force -> mitigação: AC-009 exige resposta `429` e bloqueio de nova tentativa na janela configurada.

## Notas do PM

Este slice entrega o acesso seguro mínimo para o primeiro laboratório: login, recuperação de senha e 2FA para papéis críticos. O restante do E02 entra em slices separados para manter o escopo controlado.
