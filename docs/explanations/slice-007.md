# Slice 007 — SEG-001 - Login seguro do laboratorio

**Status:** ⚠ precisa da sua decisão
**Data:** 2026-04-14
**Slice:** 007

---

## O que foi feito

- A tentativa autorizada passou a revalidar o acesso no final do 2FA antes de criar a sessao da aplicacao.
- Se o tenant mudar para somente leitura antes do codigo 2FA, a sessao final passa a entrar em modo somente leitura.
- Se o acesso for cancelado/removido antes do codigo 2FA, o sistema bloqueia a conclusao do login.
- O verifier aprovou depois que o metadado do spec foi alinhado para `approved`.

## O que o usuário final vai ver

- A experiencia do 2FA continua igual quando tudo esta valido.
- Se o acesso mudar durante o 2FA, o login e bloqueado com seguranca.

## O que funcionou

- Verificacao mecanica aprovada: todos os 21 criterios de aceite continuam cobertos.
- Testes do slice aprovados: 36 testes e 243 verificacoes.
- Verifier aprovado.

## O que precisa de atenção

**Encontrados na revisão estrutural:**

- **⚠ IMPORTANTE:** problema de segurança — o fluxo real de `/app` com 2FA pendente ainda nao esta coberto corretamente: o middleware de login roda antes do middleware do desafio 2FA, entao o usuario pode voltar para `/auth/login` em vez de ser mantido em `/auth/two-factor-challenge`.

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
- **Artefatos:**
    - `specs/007/spec.md`
    - `specs/007/verification.json`
    - `specs/007/review.json`

Tradução gerada automaticamente por `scripts/translate-pm.sh` (B-010).

</details>
