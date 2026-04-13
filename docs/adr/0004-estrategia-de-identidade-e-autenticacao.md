# ADR-0004 — Estratégia de identidade e autenticação

**Status:** accepted
**Data:** 2026-04-13
**Autor:** roldaobatista (PM) + Codex (recomendação técnica)

---

## Contexto

O próximo slice de produto recomendado é `SEG-001 — Login seguro do laboratório` em `specs/007`. Esse slice fica bloqueado até decidir a estratégia de identidade: usar autenticação nativa do Laravel no MVP ou adotar desde o início um provedor corporativo externo.

O Kalibrium precisa entregar login por e-mail e senha, recuperação de senha, 2FA para papéis críticos, sessões seguras, RBAC por papel e isolamento por tenant. O PRD também prevê identidade corporativa avançada para clientes Enterprise, mas isso não precisa ser o primeiro movimento do MVP.

Restrições relevantes:
- ADR-0001 já escolheu Laravel + Livewire + PostgreSQL como base do produto.
- `docs/architecture/foundation-constraints.md` define autenticação local para usuários de laboratório no MVP, com suporte opcional a login federado a partir do 6º mês.
- O primeiro cliente precisa de um login seguro e barato de operar, não de uma plataforma Enterprise de identidade antes de existir receita.

## Opções consideradas

### Opção A: Laravel Fortify + Sanctum
- Descrição: usar autenticação nativa do ecossistema Laravel para login por e-mail/senha, recuperação de senha, 2FA por TOTP, sessões web e tokens de API internos quando necessário.
- Prós:
  - Caminho mais simples e coerente com ADR-0001.
  - Cobre o MVP: login, recuperação de senha, 2FA, sessão e proteção das rotas.
  - Baixo custo operacional: roda no próprio monolito, sem serviço externo obrigatório.
  - Menos pontos de falha no começo do produto.
  - Mais fácil para os agentes implementarem e testarem com o harness atual.
  - Permite evoluir depois para OAuth corporativo ou SSO Enterprise sem trocar a base do MVP.
- Contras:
  - Não entrega SAML, SCIM e governança Enterprise avançada no primeiro dia.
  - Exige disciplina de segurança no próprio produto: rate limit, logs de login, políticas de senha, rotação de sessão e testes de acesso.
  - Integrações corporativas futuras precisarão de ADR incremental.
- Custo de reverter: médio

### Opção B: Keycloak self-hosted
- Descrição: operar um servidor Keycloak separado para identidade, login, 2FA, federação e SSO.
- Prós:
  - Plataforma madura para SSO, OIDC, SAML e federação corporativa.
  - Separa a identidade do monolito desde o início.
  - Pode atender clientes Enterprise com exigências avançadas.
- Contras:
  - Aumenta a infraestrutura e a operação antes do primeiro cliente pagante.
  - Introduz mais um serviço crítico para monitorar, atualizar, fazer backup e debugar.
  - Aumenta o custo de setup do staging e do ambiente local.
  - É excesso para Starter/Basic e para o primeiro fluxo de login do MVP.
- Custo de reverter: médio

### Opção C: WorkOS
- Descrição: usar um provedor SaaS externo focado em identidade Enterprise, com SSO/SAML/SCIM gerenciados.
- Prós:
  - Excelente para vender Enterprise quando SSO e SCIM forem exigência contratual.
  - Reduz manutenção técnica de SSO complexo.
  - Entrega recursos corporativos prontos.
- Contras:
  - Custo em dólar e dependência de fornecedor externo antes do produto provar receita.
  - Pode criar atrito LGPD/contratual por envolver fornecedor internacional.
  - Não é necessário para login básico do laboratório no MVP.
  - Requer desenho comercial Enterprise antes de existir demanda validada.
- Custo de reverter: médio

## Decisão

**Opção escolhida:** A — Laravel Fortify + Sanctum.

**Razão:** o MVP precisa colocar o laboratório para entrar no sistema com segurança, com senha, recuperação de senha e 2FA, gastando pouco e sem criar uma operação paralela de identidade. Fortify/Sanctum atende esse objetivo agora e mantém uma porta de evolução para Keycloak ou WorkOS quando um cliente Enterprise exigir SAML, OIDC, SCIM ou governança corporativa.

**Reversibilidade:** média. A autenticação local pode continuar como base para Starter/Basic, enquanto SSO Enterprise entra depois por adapter/provedor adicional. A troca total de identidade depois de muitos usuários cadastrados exigiria migração cuidadosa de contas e sessões.

## Consequências

### Positivas
- Desbloqueia `SEG-001 — Login seguro do laboratório` em `specs/007`.
- Mantém o MVP simples, barato e testável.
- Alinha com ADR-0001 e com `foundation-constraints.md`.
- Permite implementar 2FA obrigatório para gerente/administrativo sem contratar serviço externo.
- Reduz risco operacional no primeiro cliente porque há menos serviços para manter.

### Negativas
- SSO Enterprise completo fica fora do primeiro slice de login.
- Clientes que exigirem SAML/SCIM desde o início precisarão aguardar evolução Enterprise ou exceção comercial.
- A aplicação passa a ser responsável por controles de segurança de autenticação no MVP: rate limit, auditoria de login, timeout de sessão e testes de acesso.

### Riscos
- Implementação permissiva demais pode expor telas internas. Mitigação: testes de acesso por papel e por tenant desde `SEG-001`.
- 2FA mal configurado pode travar usuários legítimos. Mitigação: recovery codes, fluxo claro de reset e testes de recuperação.
- Crescimento Enterprise pode exigir troca parcial para Keycloak ou WorkOS. Mitigação: encapsular integrações de identidade e não acoplar regras de domínio diretamente ao provedor.

### Impacto em outros artefatos
- Hooks afetados: nenhum.
- Sub-agents afetados: `architect` deve considerar Fortify/Sanctum nos planos de E02; `security-reviewer` deve validar rate limiting, sessão, 2FA, RBAC e ausência de vazamento entre tenants.
- ADRs relacionados: ADR-0001 (stack principal), ADR-0002 (MCP policy), ADR-0008 (Codex CLI como orquestrador).
- Roadmap: remove o bloqueio de ADR de `SEG-001`; ainda permanece o gate documental por épico E02 antes de implementar UI.

## Referências

- Slice que motivou: `specs/007/` (a criar) — `SEG-001 — Login seguro do laboratório`.
- Roadmap: `docs/product/roadmap.md` — próximo slice de produto recomendado.
- Épico: `epics/E02/epic.md` — Multi-tenancy, Auth e Planos.
- ADR base: `docs/adr/0001-stack-choice.md`.
- Constraint base: `docs/architecture/foundation-constraints.md` §6.
- Discussão: PM aprovou em chat em 2026-04-13 a recomendação de seguir com Fortify/Sanctum antes de iniciar `specs/007`.

---

## Checklist de aceitação (revisor)

- [x] Pelo menos 2 opções reais consideradas
- [x] Decisão justificada sem "porque sim"
- [x] Reversibilidade declarada
- [x] Consequências negativas listadas
- [x] Não contradiz ADR anterior (ou declara `superseded by`)
- [x] Impacto em hooks/agents/constitution endereçado
