# Fluxos E02 — Multi-tenancy, Auth e Planos

> **Status:** draft — aguardando revisão do PM.
> **Data:** 2026-04-13.
> **Epico:** E02 — Multi-tenancy, Auth e Planos.
> **Documento do gate:** D.4 — User Flows Detalhados.
> **Base:** `epics/E02/epic.md`, ADR-0004, `docs/product/persona-scenarios.md`, `docs/product/sitemap.md`.

---

## 1. Login do usuário interno

Persona: Marcelo ou Juliana.

Pré-condições:
- Usuário existe.
- Tenant está `trial` ou `ativo`.
- Usuário está vinculado ao tenant.

Passos:
1. Usuário acessa `/auth/login`.
2. Informa e-mail e senha.
3. Sistema valida credenciais sem revelar se o e-mail existe.
4. Sistema verifica tenant ativo e vínculo do usuário.
5. Se papel exige 2FA, sistema redireciona para `/auth/two-factor-challenge`.
6. Usuário informa código TOTP ou código de recuperação.
7. Sistema cria sessão e redireciona para `/app`.

Erros:
- Senha inválida: mensagem neutra.
- Muitas tentativas: bloqueio temporário com mensagem segura.
- Tenant suspenso: autenticação permitida em modo somente leitura; ações de escrita ficam bloqueadas.
- 2FA inválido: erro inline sem revelar segredo.

Resultado esperado:
- Usuário autorizado entra no sistema com tenant ativo carregado ou, se o tenant estiver suspenso, com contexto somente leitura carregado.
- Tentativas de login ficam registradas.

---

## 2. Recuperação de senha

Persona: Marcelo ou Juliana.

Passos:
1. Usuário acessa `/auth/forgot-password`.
2. Informa e-mail.
3. Sistema mostra mensagem neutra.
4. Usuário abre link recebido.
5. Usuário informa nova senha e confirmação.
6. Sistema valida token e força mínima de 12 caracteres.
7. Sistema salva nova senha e redireciona para login.

Erros:
- Token expirado: orientar pedir novo link.
- Senha fraca: mostrar regra no campo.
- E-mail inválido: mostrar erro de formato.

Resultado esperado:
- Usuário recupera acesso sem expor se o e-mail existe para terceiros.

---

## 3. Criação inicial do laboratório

Persona: Marcelo.

Pré-condições:
- Usuário gerente autenticado.
- E01 completo.

Passos:
1. Gerente acessa `/settings/tenant`.
2. Preenche razão social, CNPJ, nome fantasia, e-mail e telefone.
3. Informa perfil operacional e se emite certificado metrológico.
4. Sistema cria ou atualiza tenant, empresa raiz e filial raiz.
5. Sistema grava auditoria da alteração.
6. Sistema mostra confirmação.

Erros:
- CNPJ inválido: erro inline.
- Perfil operacional incompatível com emissão metrológica: erro explicando o ajuste necessário.
- Usuário sem papel gerente: acesso negado.

Resultado esperado:
- Laboratório tem tenant, empresa e filial raiz configurados.

---

## 4. Convidar usuário e atribuir papel

Persona: Marcelo.

Pré-condições:
- Gerente com 2FA ativo.
- Tenant ativo.

Passos:
1. Gerente acessa `/settings/users`.
2. Clica em "Convidar usuário".
3. Informa nome, e-mail, papel e empresa/filial.
4. Sistema exige 2FA obrigatório se papel for gerente ou administrativo.
5. Sistema cria vínculo pendente e envia convite.
6. Convidado aceita convite, define senha e confirma e-mail.
7. Sistema ativa vínculo no tenant.

Erros:
- E-mail já existe: vincular usuário existente ao tenant quando seguro.
- Tentativa de remover último gerente: bloquear com mensagem clara.
- Usuário sem 2FA tenta alterar papel: exigir 2FA antes da alteração.

Resultado esperado:
- Usuário entra apenas no tenant/empresa/filial permitidos.

---

## 5. Bloqueio por papel

Persona: Juliana (técnico).

Passos:
1. Juliana entra no sistema.
2. Tenta acessar `/settings/users`.
3. Sistema avalia policy.
4. Sistema retorna acesso negado ou redireciona para área permitida.
5. Tentativa fica registrada quando aplicável.

Resultado esperado:
- Técnico não consegue ver nem alterar usuários e papéis.

---

## 6. Plano e limite de uso

Persona: Marcelo.

Passos:
1. Marcelo acessa `/settings/plans`.
2. Sistema mostra plano atual, status e limites.
3. Se uso passa de 80%, sistema mostra alerta leve.
4. Se uso passa de 95%, sistema mostra alerta forte.
5. Se módulo está fora do plano, gerente vê bloqueio com CTA de upgrade.
6. Usuário sem papel gerente não vê CTA de upgrade.

Resultado esperado:
- O laboratório entende o limite do plano sem bloquear fluxo já permitido.

---

## 7. Registrar consentimento LGPD

Persona: Marcelo ou administrativo.

Passos:
1. Usuário acessa `/settings/privacy`.
2. Seleciona categoria LGPD.
3. Registra canal de consentimento: e-mail, WhatsApp ou SMS.
4. Marca consentimento como concedido ou revogado.
5. Sistema grava data/hora, usuário e origem.

Erros:
- Categoria sem base legal: bloquear salvamento.
- Consentimento de marketing revogado: impedir apenas comunicações de marketing.

Resultado esperado:
- Consentimento fica rastreável sem bloquear comunicação obrigatória de serviço.

---

## 8. Suporte Kalibrium com justificativa

Persona: suporte Kalibrium.

Passos:
1. Suporte acessa `/admin/tenants`.
2. Filtra tenant.
3. Solicita acesso ao detalhe operacional.
4. Sistema exige justificativa.
5. Sistema grava `support_audit_log`.
6. Sistema abre somente a visão permitida.

Resultado esperado:
- Todo acesso interno fica auditado e não expõe dados técnicos sem necessidade.

---

## 9. Critérios de aceite derivados para `specs/007`

- Login válido redireciona para `/app`.
- Login inválido não revela se o e-mail existe.
- Recuperação de senha mostra resposta neutra.
- Gerente com 2FA habilitado passa por desafio antes de entrar.
- Técnico não acessa tela de gestão de usuários.
