# Slice 009 — TEN-002 - Usuarios, papeis e plano do laboratorio

**Status:** approved
**Data de criação:** 2026-04-14
**Autor:** roldaobatista
**Depende de:** slice-008

---

## Contexto

Este slice continua o E02 depois do login seguro e da configuracao inicial do laboratorio. Ele existe para que Marcelo, como gerente, consiga montar a equipe do laboratorio dentro do Kalibrium, atribuir papeis claros e entender o plano atual com seus limites basicos.

O resultado esperado e que o laboratorio deixe de operar com um unico gerente inicial e passe a ter usuarios internos controlados por papel: gerente, tecnico, administrativo e visualizador. O controle precisa respeitar tenant, empresa, filial, 2FA obrigatorio em papeis criticos, modo somente leitura e auditoria de alteracoes.

## Jornada alvo

Marcelo entra no sistema, acessa `/settings/users`, ve a lista de usuarios do laboratorio, filtra por nome, e-mail ou papel, convida uma pessoa nova e define o papel dela. Se o papel for gerente ou administrativo, o sistema exige 2FA para esse usuario. O convidado recebe um convite, define senha, aceita o acesso e passa a entrar somente no laboratorio, empresa e filial permitidos.

Depois, Marcelo acessa `/settings/plans` e ve o plano atual, status, limites de usuarios, OS por mes e armazenamento. Se um limite passar de 80% ou 95%, o sistema exibe alerta proporcional. Se um modulo estiver fora do plano, o gerente ve o bloqueio e pode pedir upgrade, mas nenhuma cobranca real e executada neste slice.

## Acceptance Criteria

**Regra:** cada AC vira pelo menos um teste automatizado (P2). Para cada happy path, ha pelo menos um edge case ou erro correspondente.

### Happy path

- **AC-001:** Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando acessar `GET /settings/users`, entao o sistema retorna HTTP 200 e exibe usuarios do tenant atual com nome, e-mail, papel, status, obrigatoriedade de 2FA e filtros por busca textual e papel.
- **AC-002:** Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando convidar um novo usuario com nome, e-mail valido, papel valido e empresa/filial do tenant atual, entao o sistema cria usuario quando necessario, cria vinculo pendente no tenant atual, marca `requires_2fa=true` para papeis `gerente` e `administrativo`, bloqueia acesso privilegiado desses papeis ate a conclusao da 2FA, envia convite e registra auditoria sem expor senha ou token.
- **AC-003:** Dado um convite valido e ainda nao usado, quando o convidado definir senha com pelo menos 12 caracteres e aceitar o convite, entao o sistema ativa o vinculo do usuario no tenant correto, registra `accepted_at` e permite login apenas dentro do tenant, empresa e filial vinculados.
- **AC-004:** Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando alterar o papel de outro usuario do mesmo tenant para `gerente`, `tecnico`, `administrativo` ou `visualizador`, entao o sistema atualiza o papel, ajusta a obrigatoriedade de 2FA para gerente/administrativo e registra auditoria da mudanca.
- **AC-005:** Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando desativar um usuario do mesmo tenant que nao seja o ultimo gerente ativo, entao o sistema marca o vinculo como removido ou suspenso, impede novo acesso desse usuario ao tenant e registra auditoria.
- **AC-006:** Dado um usuario gerente autenticado em tenant com assinatura e limites configurados, quando acessar `GET /settings/plans`, entao o sistema retorna HTTP 200 e exibe plano atual, status, uso de usuarios, uso de OS no mes, armazenamento consumido, percentual usado e status dos modulos do plano.
- **AC-007:** Dado um modulo fora do plano atual, quando o gerente visualizar `/settings/plans` e pedir upgrade desse modulo, entao o sistema registra a solicitacao de upgrade para acompanhamento e confirma o pedido sem executar cobranca real.

### Edge cases e erros

- **AC-008:** Dado um usuario autenticado sem papel `gerente`, quando acessar `/settings/users` ou tentar convidar, alterar papel ou desativar usuario, entao o sistema bloqueia a acao, nao mostra dados administrativos e nao altera usuarios.
- **AC-009:** Dado um usuario gerente autenticado sem 2FA concluido ou com 2FA obrigatorio pendente, quando tentar convidar, alterar papel ou desativar usuario, entao o sistema exige concluir 2FA antes da acao e nao altera dados.
- **AC-010:** Dado nome vazio, e-mail invalido, papel invalido, empresa de outro tenant ou filial de outro tenant, quando o gerente enviar convite, entao o sistema retorna erro de validacao, mantem os dados seguros na tela e nao cria usuario, vinculo, convite nem auditoria de sucesso.
- **AC-011:** Dado e-mail ja vinculado ao tenant atual como usuario ativo ou convidado pendente, quando o gerente enviar novo convite para o mesmo e-mail, entao o sistema bloqueia duplicidade e nao cria segundo vinculo.
- **AC-012:** Dado um tenant com apenas um gerente ativo, quando esse gerente tentar remover a si mesmo, remover o ultimo gerente ou alterar o ultimo gerente para outro papel, entao o sistema bloqueia a operacao e mantem ao menos um gerente ativo.
- **AC-013:** Dado um usuario, convite, empresa, filial, plano ou limite pertencente a outro tenant, quando o gerente tentar acessar ou alterar esses dados por parametro enviado na requisicao, entao o sistema rejeita a acao e nao revela dados do outro tenant.
- **AC-014:** Dado um tenant `suspended` com sessao em modo somente leitura, quando o gerente acessar `/settings/users` ou `/settings/plans`, entao o sistema permite leitura permitida, mas bloqueia convite, alteracao de papel, desativacao de usuario e pedido de upgrade.
- **AC-015:** Dado convite expirado, ja usado ou pertencente a outro tenant, quando alguem tentar aceitar o convite, entao o sistema bloqueia a ativacao, nao altera senha, nao ativa vinculo e orienta solicitar novo convite.
- **AC-016:** Dado senha com menos de 12 caracteres ou confirmacao divergente, quando o convidado aceitar convite, entao o sistema retorna erro de validacao e mantem o convite pendente.
- **AC-017:** Dado consumo de usuarios, OS mensal ou armazenamento maior ou igual a 80% do limite, quando o gerente acessar `/settings/plans`, entao o sistema exibe alerta leve; dado consumo maior ou igual a 95%, entao exibe alerta forte.
- **AC-018:** Dado usuario sem papel `gerente`, quando acessar `/settings/plans`, entao o sistema permite visualizar informacoes basicas do plano quando autorizado, mas nao exibe botao de pedido de upgrade.

### Seguranca

- **AC-SEC-001:** Dado input contendo HTML, JavaScript ou payload SQL comum nos campos nome, e-mail, busca ou justificativa de upgrade, quando o sistema salvar ou exibir esses dados, entao trata o conteudo como dado, valida o que for invalido e nao executa nem reflete conteudo sem escape.
- **AC-SEC-002:** Dado qualquer auditoria de convite, aceite, alteracao de papel, desativacao de usuario ou pedido de upgrade, quando os registros forem inspecionados, entao nao aparecem senha, token de convite, segredo TOTP, codigo TOTP nem codigo de recuperacao.
- **AC-SEC-003:** Dado dois tenants com usuarios, papeis, planos e limites distintos, quando usuarios de cada tenant acessarem `/settings/users` e `/settings/plans`, entao cada um enxerga somente dados do proprio tenant.

## Fora de escopo

- Registro publico de novo laboratorio por visitante anonimo.
- Tela de configuracao cadastral do laboratorio em `/settings/tenant`.
- Base legal, consentimentos LGPD e opt-out em `/settings/privacy`.
- Cobranca real, pagamento, nota fiscal, upgrade automatico, downgrade e calculo pro-rata.
- Criacao de clientes, instrumentos, padroes, ordens de servico ou certificados.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Console interno de suporte Kalibrium para trocar plano de tenants.
- Permissoes finas por modulo alem dos papeis canonicos e bloqueios basicos de plano deste slice.

## Dependencias externas

- slice-007: login seguro, recuperacao de senha, 2FA e auditoria de autenticacao.
- slice-008: tenant, empresa raiz, filial raiz, modo somente leitura e auditoria de configuracao do laboratorio.
- ADR-0004: Laravel Fortify + Sanctum como estrategia de identidade do MVP.
- ADR-0001: Laravel 13, Livewire 4, PostgreSQL 18, tenant_id com RLS e RBAC com policies Laravel.
- Gate documental E02: `docs/design/wireframes/wireframes-e02-auth.md`, `docs/architecture/api-contracts/api-e02-auth.md`, `docs/architecture/data-models/erd-e02-auth.md`, `docs/architecture/data-models/migrations-e02-auth.md` e `docs/product/flows/flows-e02-auth.md`.
- Servico de e-mail configuravel para envio de convites; em testes, usar fake de mailer.

## Riscos conhecidos

- Remover ou rebaixar o ultimo gerente pode deixar o laboratorio sem administracao -> mitigacao: AC-012 bloqueia a operacao.
- Convite pode ativar usuario no tenant errado se o token nao carregar contexto seguro -> mitigacao: AC-003, AC-013 e AC-015 exigem validacao de tenant.
- Papel critico sem 2FA pode enfraquecer seguranca do laboratorio -> mitigacao: AC-002, AC-004 e AC-009 exigem 2FA para gerente/administrativo e para a acao do gerente.
- Dados de usuarios ou planos de outro tenant podem vazar por parametro manipulado -> mitigacao: AC-013 e AC-SEC-003 exigem isolamento.
- Tela de planos pode prometer cobranca ou upgrade automatico antes do produto estar pronto -> mitigacao: AC-007 e Fora de escopo deixam claro que este slice so registra pedido.
- Auditoria pode salvar segredo se registrar payload cru -> mitigacao: AC-002 e AC-SEC-002 proíbem senha, token e segredos nos registros.

## Notas do PM

Este slice entrega a gestao inicial da equipe e a visao basica do plano do laboratorio. Depois dele, a sequencia recomendada continua com cadastro de clientes e contatos do laboratorio.
