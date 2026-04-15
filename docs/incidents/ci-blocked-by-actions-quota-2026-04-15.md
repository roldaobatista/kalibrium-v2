# Incidente — CI travado por cota de GitHub Actions

**Data:** 2026-04-15
**Descoberto durante:** execução do pré-requisito 1 (Bloco 5 item 5.3) pós re-auditoria dual-LLM
**Classificação:** bloqueador parcial do pré-requisito 1 — parte "tornar CI obrigatório"
**Bloqueador não-técnico:** requer decisão de produto do PM (não pode ser resolvido via API/tools do agente)

## Sintoma

Todos os 7 jobs do workflow `CI` (run id 24474644922 e anteriores) completam em 3 segundos com `steps_count: 0`. O job "Harness integrity" termina com `conclusion: failure` e os dependentes ficam `skipped`.

Log de step individual não existe — só `system.txt` com as linhas `Job is about to start running on the hosted runner` seguidas de término abrupto.

## Causa-raiz

Repo é **privado**. GitHub Actions em repos privados consome minutos do plano. Quando a cota de minutos acaba, o GitHub marca todos os jobs como failed sem rodar nenhum step.

Evidência convergente:
- `steps_count: 0` em todos os jobs de todos os runs consultados.
- Jobs com `needs: harness` ficam `skipped` automaticamente (comportamento esperado quando o pai falha).
- `Actions permissions`: `enabled: true` — não é bloqueio administrativo do repo.
- `Repo visibility: private`.
- Billing do usuário não pode ser consultado via API com as credenciais atuais do gh CLI (requer scope `user`).

## Impacto

Pré-requisito 1 da decisão pós-auditoria (commit `f70f376`) tem 3 partes:

1. ✅ Remover `current_user_can_bypass` do ruleset — **feito** (bypass_actors = []).
2. ❌ CI obrigatório (required_status_checks) — **bloqueado** por este incidente. Só posso ativar quando os jobs conseguirem rodar de verdade.
3. ⏳ Auto-reviewer (GitHub App) — independente deste bloqueio.

O pré-requisito 1 fica **parcialmente completo** até o PM resolver a cota. Os demais pré-requisitos (2-8) podem avançar em paralelo.

## Opções de resolução (decisão de produto do PM)

1. **Tornar o repo público.** GitHub Actions em repos públicos é ilimitado. Trade-off: código exposto a qualquer um. Dado que o projeto está em Laravel SaaS mas ainda não tem segredos de produção no código, é viável.
2. **Pagar plano GitHub Team/Pro** com minutos adicionais de Actions. $4/usuário/mês no plano Team. Aumenta cota de minutos privados.
3. **Adicionar método de pagamento para "pay-as-you-go"** em Actions mesmo no plano Free. GitHub cobra por minuto extra consumido (~$0.008/min para Linux).
4. **Self-hosted runner** — rodar o Actions numa máquina que o PM controla (ex: no próprio PC ou num VPS). Sem custo direto ao GitHub mas requer manutenção do runner.

## Recomendação do agente

**Opção 3 (pay-as-you-go)** é a mais simples: PM adiciona cartão nos settings do GitHub, define limite mensal pequeno ($5-10), e Actions passa a rodar imediatamente sem mudar nada no código. Reversível a qualquer momento.

**Opção 1 (tornar público)** é "grátis" mas expõe código. Só recomendo quando o projeto estiver pronto para ser open-source.

## Como o PM resolve (sem terminal)

1. Abrir https://github.com/settings/billing no navegador (logado como roldaobatista).
2. Aba "Payment information" → adicionar método de pagamento.
3. Aba "Plans and usage" → "Spending limits" → Actions → definir limite (ex: $10/mês).
4. Aguardar ~1 minuto para o GitHub propagar.
5. Agente re-dispara CI e confirma que passou.

## Bloqueios desbloqueados após resolver

- Ativar `required_status_checks: ["CI"]` no ruleset (parte 2 do pré-requisito 1).
- Rodar o Harness integrity de verdade e ver se passa no Ubuntu (até agora, nunca rodou de fato).
- Validação real dos workflows "CI" e "Deploy Staging".

Enquanto não resolvido, os outros pré-requisitos (2-8) podem prosseguir. No commit final da decisão, PM assina novamente reconhecendo que o pré-requisito 1 está em estado "parcialmente completo — aguardando quota de Actions".
