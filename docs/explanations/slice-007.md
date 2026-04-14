# Slice 007 — SEG-001 - Login seguro do laboratorio

**Status:** ⚠ precisa da sua decisão
**Data:** 2026-04-14
**Slice:** 007

---

## O que foi feito

- A tentativa autorizada corrigiu os formulários de autenticação para voltarem à tela com erro visível quando o envio vem de HTML.
- A tentativa também renovou a sessão antes de marcar o desafio de 2FA como pendente.
- Os testes do slice passaram e o verifier aprovou.

## O que o usuário final vai ver

- Nos formulários de login, reset de senha e 2FA, erros esperados voltam para a tela com mensagem de erro, em vez de mostrar uma resposta técnica em JSON.

## O que funcionou

- Verificação mecânica aprovada: todos os 21 critérios de aceite continuam cobertos.
- Testes do slice aprovados: 31 testes e 207 verificações.
- Verifier aprovado.

## O que precisa de atenção

**Encontrados na revisão estrutural:**

- **⚠ IMPORTANTE:** problema de segurança — se aparecer um status novo ou inesperado para tenant/vinculo, o sistema pode liberar acesso em vez de bloquear por seguranca.
- **⚠ IMPORTANTE:** problema de segurança — os codigos de recuperacao do 2FA ainda podem ficar tratados como valores reversiveis; o reviewer pediu tratar esses codigos como segredo.

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
