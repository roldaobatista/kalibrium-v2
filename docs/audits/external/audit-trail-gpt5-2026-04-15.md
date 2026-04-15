# Auditoria Externa — Trilha GPT-5 (Codex CLI)
**Data:** 2026-04-15
**Auditor:** GPT-5 (gpt-5.4) via Codex CLI exec (sessão independente, contexto limpo)
**Verdict final:** CONDITIONAL_RESUME

## Sumário executivo

A ADR-0012 é defensável como resposta de governança ao fato operacional central: o PM é dono e decisor de produto, mas não é operador técnico cotidiano. Substituir revisão humana técnica por dual-LLM independente é uma política aceitável se o enforcement for mecânico, auditável e se as situações de escalação ficarem preservadas.

O estado atual, porém, ainda não sustenta retomada plena. A constituição declara a ADR-0012 vigente, mas o próprio ADR lido permanece com status "Proposta" e checklist de aprovação não marcado; além disso, CLAUDE.md e orchestrator.md ainda descrevem o fluxo antigo de cinco gates, sem incorporar master-auditor como gate decisório primário.

Os cinco bypasses são explicáveis pelo contexto de bootstrap e pelo bloqueio operacional do modelo anterior, mas a sequência demonstra que o item Bloco 5.3 deixou de ser dívida futura e virou pré-requisito de retomada: enquanto `current_user_can_bypass: always` existir, o contador continua gerando exceções e ambiguidades.

Meu verdict é **CONDITIONAL_RESUME**: o projeto pode sair do estado pausado sem reverter o slice-010, desde que as condições objetivas abaixo sejam atendidas antes de iniciar novo slice, aprovar novo plan ou fechar nova entrega.

## Respostas Q1-Q6

**Q1. ADR-0012 é defensável como política?**
Sim, parcialmente. A premissa de PM não-técnico é coerente com CLAUDE.md, constitution e incident P0 do PR #14.
A política de dual-LLM reduz o risco de single-model self-approval e é melhor que exigir revisão técnica humana inexistente.
Ela não está pronta como enforcement: o ADR ainda diz "Status: Proposta", o checklist de aprovação está pendente, e os documentos operacionais principais ainda apontam para o fluxo antigo.
Defensável como direção; condicionalmente aceitável como política vigente após correção documental e mecânica.

**Q2. Os 5 bypasses são proporcionais? Alguma classificação foi indevida?**
Bypasses 1-4 são proporcionais como dívida de bootstrap, mas mostram tolerância alta demais a um ruleset incompleto por vários dias.
O bypass 4 agregou 36 commits sob uma única autorização; isso é aceitável só porque foi explicitamente registrado, não como padrão futuro.
O bypass 5 tem classificação P0 plausível por inviabilidade operacional do modelo PM não-técnico, embora "P0" esteja sendo usado para governança, não para produção.
Não recomendo reclassificar retroativamente; recomendo encerrar a categoria por remoção do caminho manual.

**Q3. Merge do slice-010 durante pausa: automação legítima ou 6º bypass?**
Não classifico como 6º admin bypass, porque o merge do PR #15 não usou `--admin` e seguiu o auto-merge já armado.
Também não é automação plenamente legítima: violou uma regra explícita do estado pausado que dizia para não fechar slices em andamento.
A classificação adequada é P1 de enforcement: política declarativa sem bloqueio mecânico.
Aceitar como fato consumado é mais proporcional que reverter, desde que a retomada inclua guard contra pausa e cancelamento de auto-merges pendentes.

**Q4. Os 4 gaps técnicos (§5) são corrigíveis no escopo atual ou requerem novo ADR?**
Gaps 1 e 2 são correções obrigatórias de alinhamento com a ADR-0012 já aprovada politicamente; não exigem novo ADR se apenas implementarem o fluxo decidido.
Gap 3 parece corrigido no arquivo lido: master-auditor.md já declara `mcp__codex__codex` e `mcp__codex__codex-reply`.
Gap 4 é higiene de numeração; o incidente afirma rename para ADR-0013, mas a verificação direta do diretório ADR ficou fora do escopo autorizado desta trilha.
Se a correção mudar semântica de R11/R15/R16 ou relaxar zero tolerance, aí sim precisa de novo ADR.

**Q5. Política de congelamento do contador continua vigente?**
Continua vigente como freio de emergência, mas precisa de emenda imediata.
A exceção "bypass técnico autorizado" é defensável para material de auditoria, mas não pode virar rota normal de trabalho.
A política deve separar contador de bypass governança/produto de bypass técnico de auditoria, com whitelist de paths, incidente obrigatório e validade limitada ao estado pausado.
Substituição completa só deve ocorrer após Bloco 5.3 remover `current_user_can_bypass`.

**Q6. Critérios objetivos para fim do estado pausado?**
O estado pausado termina somente com decisão PM registrada após as duas trilhas externas, incluindo esta trilha GPT-5.
Antes disso, precisam estar corrigidos ou verificados: CLAUDE.md, orchestrator.md, master-auditor tools, numeração ADR e política de pausa com enforcement.
O ruleset deve deixar de depender de `current_user_can_bypass: always`, ou haver prova equivalente de que push/merge direto não será usado para trabalho de produto.
Também deve existir registro explícito aceitando o slice-010 como fato consumado ou definindo seu tratamento, sem deixar auto-merge pendente fora do controle do harness.

## Análise dos 5 gaps (tabela)

| Gap # | Classificação | Justificativa | Prioridade |
|---|---|---|---|
| 1 | Alta / bloqueante para retomada | CLAUDE.md é fonte operacional primária e ainda instrui Fase E com os cinco gates antigos. Sessões novas podem seguir o fluxo pré-ADR-0012 e ignorar master-auditor. | P0 antes de novo slice |
| 2 | Alta / bloqueante para retomada | orchestrator.md ainda define verifier -> reviewer -> security/test/functional como fluxo decisório. Sem referência ao master-auditor, a ADR não é executável pelo orquestrador. | P0 antes de novo slice |
| 3 | Crítica no dossiê; aparentemente corrigida no arquivo lido | master-auditor.md agora declara `mcp__codex__codex` e `mcp__codex__codex-reply`, não o tool inexistente citado no dossiê. Ainda requer smoke-test real de invocação GPT-5 antes de usar como gate. | P0 validação |
| 4 | Média; verificação direta pendente por escopo | Duplicidade de número ADR quebra rastreabilidade. O incidente de bypass técnico afirma rename para ADR-0013, mas esta trilha não leu o diretório ADR fora da lista autorizada. | P1 confirmar antes da decisão PM |
| 5 | Crítica / falha de enforcement | Slice-010 mergeou durante pausa porque a política não tinha estado mecânico nem bloqueio em merge-slice, pre-push ou auto-merge pendente. Demonstra que markdown sozinho não congela execução. | P0 antes de novo merge |

## Verdict final e condições

**Verdict final:** CONDITIONAL_RESUME.

Condições mínimas antes de retomar execução de produto:

1. Atualizar CLAUDE.md e orchestrator.md para refletir a ADR-0012: master-auditor como gate dual-LLM, sequência de reconciliação, critérios de escalação e interação com os cinco gates legados.
2. Resolver a inconsistência formal da ADR-0012: status, checklist de aprovação, referência de decisão PM e codificação explícita de R15/R16 na constitution ou rollback documentado do trecho que diz que já estão vigentes.
3. Executar verificação real do master-auditor com o tool Codex correto, registrando evidência de que a trilha GPT-5 abre sessão, responde em JSON e permite reconciliação.
4. Confirmar numeração única dos ADRs e registrar a resolução do antigo conflito 0012/0013.
5. Implementar enforcement de estado pausado: estado legível por script, bloqueio em merge-slice/pre-push para trabalho de produto e procedimento para cancelar auto-merges pendentes ao declarar pausa.
6. Emendar a política de bypass: categoria técnica autorizada com whitelist e incidente obrigatório, sem ampliar o contador de produto; remover ou neutralizar `current_user_can_bypass: always` antes de novo slice.
7. PM assinar decisão pós-auditoria citando explicitamente as conclusões da trilha Opus e desta trilha GPT-5.

Não recomendo reverter o slice-010 por padrão. A reversão só se justificaria se revisão funcional ou técnica posterior encontrar defeito material no conteúdo mergeado; como questão de governança, a correção proporcional é aceitar o fato consumado e fechar a brecha.

## Riscos residuais não cobertos pelo dossiê

- A constitution declara versão 1.5.0 com R15/R16, mas o corpo lido ainda enumera regras até R14; isso torna a ADR parcialmente invisível no documento normativo principal.
- ADR-0012 mantém "Status: Proposta" e checklist de aprovação pendente, apesar de estar referenciada como vigente pela constitution.
- master-auditor.md permite `approved` com findings minor aceitáveis, o que conflita com a política "ZERO TOLERANCE" de CLAUDE.md e orchestrator.md.
- harness-learner propõe relock programático e edição automática de hooks/settings; isso tensiona CLAUDE.md §9 e harness-limitations.md, que tratam relock como operação humana rastreável.
- O plano de rollback da ADR-0012 pede que o PM edite settings/relock, mas o próprio contexto da ADR afirma que o PM não opera terminal diretamente; esse rollback precisa de wrapper real testado.
- A independência dual-LLM depende de prompts e pacotes de input idênticos; ainda não há schema ou script lido que garanta que as duas trilhas não recebam contextos diferentes.
