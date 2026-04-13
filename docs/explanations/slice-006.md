# Slice 006 — Base visual

**Status:** pronto para testar
**Data:** 2026-04-13
**Slice:** 006

---

## O que foi feito

- A base visual para as próximas telas foi preparada.
- A tela de apoio para confirmar que essa base carrega corretamente ficou disponível fora do ambiente final de produção.
- Essa tela de apoio fica indisponível no ambiente final de produção.
- As verificações de qualidade, segurança, testes e comportamento passaram sem pontos pendentes.

## O que o usuário final vai ver

- Usuários finais ainda não recebem uma tela de negócio nova.
- Em ambiente de desenvolvimento ou validação, a equipe consegue abrir uma tela simples de confirmação da base visual.

## O que funcionou

- A base visual carrega.
- A tela de apoio responde corretamente fora de produção.
- A tela de apoio não aparece em produção.
- A checagem que estava fraca foi corrigida e validada de novo.

## O que NÃO está neste slice (fica pra depois)

- Design system completo do Kalibrium.
- Componentes reutilizáveis de formulário, botão, tabela, card ou modal.
- Qualquer tela de negócio de autenticação, tenant, cliente, instrumento, padrão, OS ou certificado.
- PWA, service worker, modo offline e cache de aplicação.
- Testes browser E2E com Pest Browser ou Playwright.
- ESLint e Prettier para JS/CSS, salvo se já estiverem exigidos pelo CI existente.

## Próximo passo

Testar visualmente a tela de apoio no ambiente de validação e então seguir para o encerramento da fatia.
