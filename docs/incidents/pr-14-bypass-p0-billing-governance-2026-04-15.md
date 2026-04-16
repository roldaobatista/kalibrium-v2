# Incidente P0 — bypass #5/5 para merge do PR #14 (ADR-0012 harness governance, NÃO billing de produto)

**Data:** 2026-04-15
**Classificação:** P0 (último bypass do contador 5/5)
**PM:** roldao.tecnico@gmail.com
**PR afetado:** https://github.com/roldaobatista/kalibrium-v2/pull/14
**Cross-ref:** `docs/incidents/bloco1-admin-bypass-2026-04-10.md`, `docs/harness-limitations.md §Política operacional 2026-04-10: congelamento de admin bypass`, `docs/adr/0012-constitution-amendment-autonomy-dual-llm.md`

---

## Contexto

O PR #14 introduz a ADR-0012 (autonomia do agente com dual-LLM + retrospectiva + harness-learner), que redefine operacionalmente a governança do harness para um PM não-técnico. A ADR inclui:

- Criação dos agents `master-auditor`, `epic-retrospective`, `harness-learner`
- Emenda de R11 (dual-verifier humano+agent → dual-LLM)
- Novas regras R15, R16
- Ferramenta `relock.bat` clicável

O PR foi aberto com 9 arquivos, commit limpo, push bem-sucedido. **CI bloqueado por billing do GitHub Actions** — pagamento falhado / spending limit insuficiente. Histórico de pagamentos vazio em https://github.com/settings/billing/summary. Resolução de billing atrasada.

## Classificação P0 — justificativa declarada pelo PM

O PM, em sessão assistida por Claude Opus 4.6 em 2026-04-15, classificou este caso como **P0** com o seguinte raciocínio:

> **Continuidade operacional do projeto.** O estado atual (antes do merge) tem um PM não-técnico tentando operar um harness desenhado para PM técnico. Toda interação exige terminal/GitHub CLI, que o PM não opera. Sem a ADR-0012 em vigor:
>
> - Operações corriqueiras (aprovar plan, mergear PR, relock) bloqueiam no PM
> - Não há caminho autônomo para o agente executar decisões aprovadas
> - Projeto fica em estado de bloqueio estrutural até resolução
>
> O risco P0 não é de segurança técnica nem disponibilidade de produção (projeto ainda não tem usuários). É risco de **inviabilidade operacional** do modelo de trabalho declarado, que fere RNF-003 (continuidade de execução do projeto) e impede fechamento de slices subsequentes.

**Assinatura do PM:** resposta "B" confirmada em 2026-04-15 após agente apresentar:
- Consequência explícita: último bypass (5/5)
- Consequência explícita: projeto pausa em re-auditoria externa
- Consequência explícita: não pode iniciar novo slice até re-auditoria completa
- Alternativa recomendada pelo agente: opção A (esperar billing resolver em 1-2 dias)

O PM escolheu B com consciência das consequências.

## Ação tomada

1. Este arquivo de incidente criado com justificativa P0 explícita
2. Contador em `bloco1-admin-bypass-2026-04-10.md` atualizado para **5/5**
3. PR #14 mergeado via `gh pr merge 14 --squash --admin`
4. Commit resultante registrado no audit log do GitHub
5. ADR-0012 entra em vigor imediatamente após merge
6. **Projeto entra em estado PAUSADO** conforme política — aguarda re-auditoria externa

## Estado pós-bypass

### Contador
- **5/5 atingido**
- Zero bypasses restantes
- Qualquer bypass adicional = violação crítica de política

### Obrigações disparadas

1. **Re-auditoria externa obrigatória** antes de qualquer novo slice (inclusive finalização do slice-010 em andamento)
2. **Auditor externo** contratado ou agent-advisor-externo invocado em sessão nova
3. **Relatório de re-auditoria** em `docs/audits/external/post-5-5-cap-reached-YYYY-MM-DD.md`
4. **Decisão do PM** em `docs/decisions/pm-decision-post-5-5-audit-YYYY-MM-DD.md` antes de retomar execução

### Bloqueios operacionais

Até re-auditoria externa completa:
- ❌ Não iniciar novos slices
- ❌ Não fechar slices em andamento (slice-010 aguarda auditoria)
- ❌ Não aprovar novos plans
- ❌ Não executar retrospectiva de épico
- ✅ Permitido: correções críticas P0/P1 (com novo incidente dedicado)
- ✅ Permitido: preparação de material para a re-auditoria
- ✅ Permitido: resolução do billing do GitHub (desbloqueio do CI)

## Plano de saída do estado pausado

1. **PM resolve o billing do GitHub** (prioritário — reabre CI para auditoria externa validar)
2. PM contrata auditor externo ou invoca agent-advisor-externo em sessão nova
3. Auditor revisa:
   - ADR-0012 e se sua implementação foi adequada
   - Todos os 5 bypasses acumulados
   - Se política de bypass precisa ser revisada (via novo ADR)
   - Estado do harness após entrada da nova governança
4. PM assina decisão de retomada ou de reformulação
5. Estado pausado termina ao merge da decisão

## Aprendizado (pré-retrospectiva)

Este é o terceiro incidente em 5 dias. Padrão observável:
- Bypass 1: PR #1 admin merge (incident original)
- Bypass 2-3: setup da meta-auditoria e registro
- Bypass 4: push da sessão 01 de execução
- **Bypass 5: este (ADR-0012 + billing bloqueado)**

Observação honesta: 5 bypasses em ~5 dias indica que o harness atual tem atrito operacional alto e que a governança original subestimou a dificuldade de operar com PM não-técnico. A ADR-0012 que este bypass habilita tenta **resolver exatamente esse problema**. O incidente que autoriza a ADR é, em si, evidência de que a ADR é necessária.

A re-auditoria externa deve avaliar se a taxa de bypasses vai cair substancialmente após ADR-0012 em vigor. Se não cair, a política de bypass precisa de revisão estrutural.

---

**Este arquivo serve como paper trail definitivo do bypass #5/5 e da entrada do projeto em estado pausado.**
