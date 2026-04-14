# Slice 009 — TEN-002 - Usuarios, papeis e plano do laboratorio

**Status:** revisão aprovada; aguardando gates finais
**Data:** 2026-04-14
**Slice:** 009

---

## O que foi feito

Esta entrega cobre os seguintes critérios:

- **AC-001** — Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando acessar `GET /settings/users`, entao o sistema retorna HTTP 200 e exibe usuarios do tenant atual com nome, e-mail, papel, status, obrigatoriedade de 2FA e filtros por busca textual e papel.
- **AC-002** — Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando convidar um novo usuario com nome, e-mail valido, papel valido e empresa/filial do tenant atual, entao o sistema cria usuario quando necessario, cria vinculo pendente no tenant atual, marca `requires_2fa=true` para papeis `gerente` e `administrativo`, bloqueia acesso privilegiado desses papeis ate a conclusao da 2FA, envia convite e registra auditoria sem expor senha ou token.
- **AC-003** — Dado um convite valido e ainda nao usado, quando o convidado definir senha com pelo menos 12 caracteres e aceitar o convite, entao o sistema ativa o vinculo do usuario no tenant correto, registra `accepted_at` e permite login apenas dentro do tenant, empresa e filial vinculados.
- **AC-004** — Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando alterar o papel de outro usuario do mesmo tenant para `gerente`, `tecnico`, `administrativo` ou `visualizador`, entao o sistema atualiza o papel, ajusta a obrigatoriedade de 2FA para gerente/administrativo e registra auditoria da mudanca.
- **AC-005** — Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando desativar um usuario do mesmo tenant que nao seja o ultimo gerente ativo, entao o sistema marca o vinculo como removido ou suspenso, impede novo acesso desse usuario ao tenant e registra auditoria.
- **AC-006** — Dado um usuario gerente autenticado em tenant com assinatura e limites configurados, quando acessar `GET /settings/plans`, entao o sistema retorna HTTP 200 e exibe plano atual, status, uso de usuarios, uso de OS no mes, armazenamento consumido, percentual usado e status dos modulos do plano.
- **AC-007** — Dado um modulo fora do plano atual, quando o gerente visualizar `/settings/plans` e pedir upgrade desse modulo, entao o sistema registra a solicitacao de upgrade para acompanhamento e confirma o pedido sem executar cobranca real.
- **AC-008** — Dado um usuario autenticado sem papel `gerente`, quando acessar `/settings/users` ou tentar convidar, alterar papel ou desativar usuario, entao o sistema bloqueia a acao, nao mostra dados administrativos e nao altera usuarios.
- **AC-009** — Dado um usuario gerente autenticado sem 2FA concluido ou com 2FA obrigatorio pendente, quando tentar convidar, alterar papel ou desativar usuario, entao o sistema exige concluir 2FA antes da acao e nao altera dados.
- **AC-010** — Dado nome vazio, e-mail invalido, papel invalido, empresa de outro tenant ou filial de outro tenant, quando o gerente enviar convite, entao o sistema retorna erro de validacao, mantem os dados seguros na tela e nao cria usuario, vinculo, convite nem auditoria de sucesso.
- **AC-011** — Dado e-mail ja vinculado ao tenant atual como usuario ativo ou convidado pendente, quando o gerente enviar novo convite para o mesmo e-mail, entao o sistema bloqueia duplicidade e nao cria segundo vinculo.
- **AC-012** — Dado um tenant com apenas um gerente ativo, quando esse gerente tentar remover a si mesmo, remover o ultimo gerente ou alterar o ultimo gerente para outro papel, entao o sistema bloqueia a operacao e mantem ao menos um gerente ativo.
- **AC-013** — Dado um usuario, convite, empresa, filial, plano ou limite pertencente a outro tenant, quando o gerente tentar acessar ou alterar esses dados por parametro enviado na requisicao, entao o sistema rejeita a acao e nao revela dados do outro tenant.
- **AC-014** — Dado um tenant `suspended` com sessao em modo somente leitura, quando o gerente acessar `/settings/users` ou `/settings/plans`, entao o sistema permite leitura permitida, mas bloqueia convite, alteracao de papel, desativacao de usuario e pedido de upgrade.
- **AC-015** — Dado convite expirado, ja usado ou pertencente a outro tenant, quando alguem tentar aceitar o convite, entao o sistema bloqueia a ativacao, nao altera senha, nao ativa vinculo e orienta solicitar novo convite.
- **AC-016** — Dado senha com menos de 12 caracteres ou confirmacao divergente, quando o convidado aceitar convite, entao o sistema retorna erro de validacao e mantem o convite pendente.
- **AC-017** — Dado consumo de usuarios, OS mensal ou armazenamento maior ou igual a 80% do limite, quando o gerente acessar `/settings/plans`, entao o sistema exibe alerta leve; dado consumo maior ou igual a 95%, entao exibe alerta forte.
- **AC-018** — Dado usuario sem papel `gerente`, quando acessar `/settings/plans`, entao o sistema permite visualizar informacoes basicas do plano quando autorizado, mas nao exibe botao de pedido de upgrade.

## O que o usuário final vai ver

- Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando acessar `GET /settings/users`, entao o sistema retorna HTTP 200 e exibe usuarios do tenant atual com nome, e-mail, papel, status, obrigatoriedade de 2FA e filtros por busca textual e papel.
- Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando convidar um novo usuario com nome, e-mail valido, papel valido e empresa/filial do tenant atual, entao o sistema cria usuario quando necessario, cria vinculo pendente no tenant atual, marca `requires_2fa=true` para papeis `gerente` e `administrativo`, bloqueia acesso privilegiado desses papeis ate a conclusao da 2FA, envia convite e registra auditoria sem expor senha ou token.
- Dado um convite valido e ainda nao usado, quando o convidado definir senha com pelo menos 12 caracteres e aceitar o convite, entao o sistema ativa o vinculo do usuario no tenant correto, registra `accepted_at` e permite login apenas dentro do tenant, empresa e filial vinculados.
- Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando alterar o papel de outro usuario do mesmo tenant para `gerente`, `tecnico`, `administrativo` ou `visualizador`, entao o sistema atualiza o papel, ajusta a obrigatoriedade de 2FA para gerente/administrativo e registra auditoria da mudanca.
- Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando desativar um usuario do mesmo tenant que nao seja o ultimo gerente ativo, entao o sistema marca o vinculo como removido ou suspenso, impede novo acesso desse usuario ao tenant e registra auditoria.
- Dado um usuario gerente autenticado em tenant com assinatura e limites configurados, quando acessar `GET /settings/plans`, entao o sistema retorna HTTP 200 e exibe plano atual, status, uso de usuarios, uso de OS no mes, armazenamento consumido, percentual usado e status dos modulos do plano.
- Dado um modulo fora do plano atual, quando o gerente visualizar `/settings/plans` e pedir upgrade desse modulo, entao o sistema registra a solicitacao de upgrade para acompanhamento e confirma o pedido sem executar cobranca real.
- Dado um usuario autenticado sem papel `gerente`, quando acessar `/settings/users` ou tentar convidar, alterar papel ou desativar usuario, entao o sistema bloqueia a acao, nao mostra dados administrativos e nao altera usuarios.
- Dado um usuario gerente autenticado sem 2FA concluido ou com 2FA obrigatorio pendente, quando tentar convidar, alterar papel ou desativar usuario, entao o sistema exige concluir 2FA antes da acao e nao altera dados.
- Dado nome vazio, e-mail invalido, papel invalido, empresa de outro tenant ou filial de outro tenant, quando o gerente enviar convite, entao o sistema retorna erro de validacao, mantem os dados seguros na tela e nao cria usuario, vinculo, convite nem auditoria de sucesso.
- Dado e-mail ja vinculado ao tenant atual como usuario ativo ou convidado pendente, quando o gerente enviar novo convite para o mesmo e-mail, entao o sistema bloqueia duplicidade e nao cria segundo vinculo.
- Dado um tenant com apenas um gerente ativo, quando esse gerente tentar remover a si mesmo, remover o ultimo gerente ou alterar o ultimo gerente para outro papel, entao o sistema bloqueia a operacao e mantem ao menos um gerente ativo.
- Dado um usuario, convite, empresa, filial, plano ou limite pertencente a outro tenant, quando o gerente tentar acessar ou alterar esses dados por parametro enviado na requisicao, entao o sistema rejeita a acao e nao revela dados do outro tenant.
- Dado um tenant `suspended` com sessao em modo somente leitura, quando o gerente acessar `/settings/users` ou `/settings/plans`, entao o sistema permite leitura permitida, mas bloqueia convite, alteracao de papel, desativacao de usuario e pedido de upgrade.
- Dado convite expirado, ja usado ou pertencente a outro tenant, quando alguem tentar aceitar o convite, entao o sistema bloqueia a ativacao, nao altera senha, nao ativa vinculo e orienta solicitar novo convite.
- Dado senha com menos de 12 caracteres ou confirmacao divergente, quando o convidado aceitar convite, entao o sistema retorna erro de validacao e mantem o convite pendente.
- Dado consumo de usuarios, OS mensal ou armazenamento maior ou igual a 80% do limite, quando o gerente acessar `/settings/plans`, entao o sistema exibe alerta leve; dado consumo maior ou igual a 95%, entao exibe alerta forte.
- Dado usuario sem papel `gerente`, quando acessar `/settings/plans`, entao o sistema permite visualizar informacoes basicas do plano quando autorizado, mas nao exibe botao de pedido de upgrade.

## O que funcionou

- ✓ Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando acessar `GET /settings/users`, entao o sistema retorna HTTP 200 e exibe usuarios do tenant atual com nome, e-mail, papel, status, obrigatoriedade de 2FA e filtros por busca textual e papel.
- ✓ Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando convidar um novo usuario com nome, e-mail valido, papel valido e empresa/filial do tenant atual, entao o sistema cria usuario quando necessario, cria vinculo pendente no tenant atual, marca `requires_2fa=true` para papeis `gerente` e `administrativo`, bloqueia acesso privilegiado desses papeis ate a conclusao da 2FA, envia convite e registra auditoria sem expor senha ou token.
- ✓ Dado um convite valido e ainda nao usado, quando o convidado definir senha com pelo menos 12 caracteres e aceitar o convite, entao o sistema ativa o vinculo do usuario no tenant correto, registra `accepted_at` e permite login apenas dentro do tenant, empresa e filial vinculados.
- ✓ Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando alterar o papel de outro usuario do mesmo tenant para `gerente`, `tecnico`, `administrativo` ou `visualizador`, entao o sistema atualiza o papel, ajusta a obrigatoriedade de 2FA para gerente/administrativo e registra auditoria da mudanca.
- ✓ Dado um usuario gerente autenticado, com 2FA concluido e tenant `active` ou `trial`, quando desativar um usuario do mesmo tenant que nao seja o ultimo gerente ativo, entao o sistema marca o vinculo como removido ou suspenso, impede novo acesso desse usuario ao tenant e registra auditoria.
- ✓ Dado um usuario gerente autenticado em tenant com assinatura e limites configurados, quando acessar `GET /settings/plans`, entao o sistema retorna HTTP 200 e exibe plano atual, status, uso de usuarios, uso de OS no mes, armazenamento consumido, percentual usado e status dos modulos do plano.
- ✓ Dado um modulo fora do plano atual, quando o gerente visualizar `/settings/plans` e pedir upgrade desse modulo, entao o sistema registra a solicitacao de upgrade para acompanhamento e confirma o pedido sem executar cobranca real.
- ✓ Dado um usuario autenticado sem papel `gerente`, quando acessar `/settings/users` ou tentar convidar, alterar papel ou desativar usuario, entao o sistema bloqueia a acao, nao mostra dados administrativos e nao altera usuarios.
- ✓ Dado um usuario gerente autenticado sem 2FA concluido ou com 2FA obrigatorio pendente, quando tentar convidar, alterar papel ou desativar usuario, entao o sistema exige concluir 2FA antes da acao e nao altera dados.
- ✓ Dado nome vazio, e-mail invalido, papel invalido, empresa de outro tenant ou filial de outro tenant, quando o gerente enviar convite, entao o sistema retorna erro de validacao, mantem os dados seguros na tela e nao cria usuario, vinculo, convite nem auditoria de sucesso.
- ✓ Dado e-mail ja vinculado ao tenant atual como usuario ativo ou convidado pendente, quando o gerente enviar novo convite para o mesmo e-mail, entao o sistema bloqueia duplicidade e nao cria segundo vinculo.
- ✓ Dado um tenant com apenas um gerente ativo, quando esse gerente tentar remover a si mesmo, remover o ultimo gerente ou alterar o ultimo gerente para outro papel, entao o sistema bloqueia a operacao e mantem ao menos um gerente ativo.
- ✓ Dado um usuario, convite, empresa, filial, plano ou limite pertencente a outro tenant, quando o gerente tentar acessar ou alterar esses dados por parametro enviado na requisicao, entao o sistema rejeita a acao e nao revela dados do outro tenant.
- ✓ Dado um tenant `suspended` com sessao em modo somente leitura, quando o gerente acessar `/settings/users` ou `/settings/plans`, entao o sistema permite leitura permitida, mas bloqueia convite, alteracao de papel, desativacao de usuario e pedido de upgrade.
- ✓ Dado convite expirado, ja usado ou pertencente a outro tenant, quando alguem tentar aceitar o convite, entao o sistema bloqueia a ativacao, nao altera senha, nao ativa vinculo e orienta solicitar novo convite.
- ✓ Dado senha com menos de 12 caracteres ou confirmacao divergente, quando o convidado aceitar convite, entao o sistema retorna erro de validacao e mantem o convite pendente.
- ✓ Dado consumo de usuarios, OS mensal ou armazenamento maior ou igual a 80% do limite, quando o gerente acessar `/settings/plans`, entao o sistema exibe alerta leve; dado consumo maior ou igual a 95%, entao exibe alerta forte.
- ✓ Dado usuario sem papel `gerente`, quando acessar `/settings/plans`, entao o sistema permite visualizar informacoes basicas do plano quando autorizado, mas nao exibe botao de pedido de upgrade.

## O que NÃO está neste slice (fica pra depois)

- Registro publico de novo laboratorio por visitante anonimo.
- Tela de configuracao cadastral do laboratorio em `/settings/tenant`.
- Base legal, consentimentos LGPD e opt-out em `/settings/privacy`.
- Cobranca real, pagamento, nota fiscal, upgrade automatico, downgrade e calculo pro-rata.
- Criacao de clientes, instrumentos, padroes, ordens de servico ou certificados.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Console interno de suporte Kalibrium para trocar plano de tenants.
- Permissoes finas por modulo alem dos papeis canonicos e bloqueios basicos de plano deste slice.

## Próximo passo

Seguir para as revisões de segurança, testes e funcionalidade antes de qualquer merge.

---

<details>
<summary>Detalhes técnicos (não precisa abrir)</summary>

- **Verifier verdict:** approved
- **Reviewer verdict:** approved
- **Security verdict:** -
- **Test audit verdict:** -
- **Functional verdict:** -
- **ACs pass/fail:** 18 / 0
- **Artefatos:**
    - `specs/009/spec.md`
    - `specs/009/verification.json`
    - `specs/009/review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
