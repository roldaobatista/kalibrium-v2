# Slice 007 — SEG-001 - Login seguro do laboratorio

**Status:** ⚠ precisa da sua decisão
**Data:** 2026-04-14
**Slice:** 007

---

## O que foi feito

- A tentativa autorizada corrigiu o comportamento de seguranca para status inesperados de tenant/vinculo.
- A tentativa tambem passou a tratar codigos de recuperacao do 2FA como segredo, armazenando hash em vez de texto puro.
- Os testes do slice passaram e o verifier aprovou.

## O que o usuário final vai ver

- Se o status de acesso mudar para um valor inesperado, o login falha com seguranca em vez de liberar acesso.
- O uso de recovery code do 2FA continua funcionando para o usuario final, mas o codigo nao fica guardado como texto puro.

## O que funcionou

- Verificacao mecanica aprovada: todos os 21 criterios de aceite continuam cobertos.
- Testes do slice aprovados: 33 testes e 221 verificacoes.
- Verifier aprovado.

## O que precisa de atenção

**Encontrados na revisão estrutural:**

- **⚠ IMPORTANTE:** problema de segurança — depois que a senha e aceita e antes de concluir o 2FA, o status do acesso pode mudar; o reviewer pediu revalidar esse acesso no momento final do 2FA antes de criar a sessao da aplicacao.

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
