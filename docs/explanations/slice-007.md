# Slice 007 — SEG-001 - Login seguro do laboratorio

**Status:** ⚠ precisa da sua decisão
**Data:** 2026-04-14
**Slice:** 007

---

## O que foi feito

_Sem critérios declarados no spec ainda._

## O que o usuário final vai ver

_Nada visível ainda — slice em estágio inicial._

## O que funcionou

_A verificação encontrou problemas (ver abaixo)._

## O que precisa de atenção

**Encontrados na revisão estrutural:**

- **⚠ IMPORTANTE:** problema de segurança — O backend retorna JSON puro em caminhos de erro de login/2FA/reset
- **⚠ IMPORTANTE:** problema de segurança — O fluxo que exige 2FA grava `auth.two_factor_pending` na sessao sem renovar o ID da sessao apos validar a senha

## O que NÃO está neste slice (fica pra depois)

- Registro público de novo laboratório.
- Tela de configuração do tenant em `/settings/tenant`.
- Cadastro de empresa e filial raiz.
- Gestão de usuários e papéis em `/settings/users`.
- Motor de planos e feature gates.
- SSO, SAML, SCIM, OIDC Enterprise, Keycloak ou WorkOS.
- Portal do cliente final.
- UI visual final fora das telas públicas de autenticação necessárias para o fluxo.

## Sua decisão é necessária

A entrega não ficou pronta nesta tentativa. Os problemas acima foram
encontrados por uma verificação automática — não é opinião minha,
é resultado mecânico.

**Opções:**

- [ ] **Pedir nova tentativa** — o agente implementador corrige os problemas e tenta de novo
- [ ] **Reescopar** — o slice é grande demais; dividir em pedaços menores
- [ ] **Pausar** — prefiro discutir antes de decidir

## Próximo passo

Marque uma opção acima e me avise. Não vou continuar sem sua decisão.

---

<details>
<summary>Detalhes técnicos (não precisa abrir)</summary>

- **Verifier verdict:** approved
- **Reviewer verdict:** rejected
- **ACs pass/fail:** 21 / 0
0
- **Artefatos:**
    - `specs/007/spec.md`
    - `specs/007/verification.json`
    - `specs/007/review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
