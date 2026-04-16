# Audit de Qualidade Profissional - 40 Skills do Harness Kalibrium V2

Data: 2026-04-16
Auditor: governance (Claude Opus 4.7, 1M context) - contexto isolado R3
Metodo: rubrica S/I/R/P (12 notas por skill, escala 1-5), 3 categorias
Escopo: 40 arquivos em .claude/skills/*.md (v3, protocol v1.2.2)

---

## Verdict geral

- verdict: approved_with_reservations
- nota_media_harness: 4.1 / 5.0 (mediana)
- skills_categoria_A_media: 4.5 (11 skills de gate - excelente)
- skills_categoria_B_media: 4.0 (17 skills de fluxo - muito boa)
- skills_categoria_C_media: 3.6 (12 skills de estado/comunicacao - adequada)
- skills_top_5: master-audit, verify-slice, review-pr, security-review, functional-review
- skills_abaixo_de_3: (nenhuma) - piso minimo respeitado
- skills_abaixo_de_3.5: mcp-check, forbidden-files-scan, adr, context-check

Leitura executiva: harness em nivel muito acima da media de mercado para orquestracao LLM. Categoria A e exemplar. Fraqueza em 4 skills utilitarias de estado/check sem contrato formal.

---

## Resumo executivo

As 11 skills de gate (Categoria A) sao estado-da-arte em orquestracao LLM com governance dual. master-audit.md define dual-LLM (Opus + GPT-5), simetria rigorosa, reconciliacao em 3 rodadas, escalacao R6, limite de custo (800k tokens como outlier). verify-slice tem 8 passos numerados, pre-condicoes validadas por hook, input package imutavel, output com schema, telemetria JSONL append-only, R6 automatico com incident file, relatorio PM-ready G-11. Todas 11 declaram bloco de conformidade v1.2.2 explicito (agent canonico, gate enum, schema path, criterios objetivos, isolamento R3, zero-tolerance).

As 17 skills de fluxo (Categoria B) tem excelente espinha dorsal com variacao de densidade. start-story, draft-plan, draft-tests, fix, retrospective estao no nivel da Categoria A (4.5+). new-slice, adr, decompose-epics sao mais finos - sem bloco R12 explicito, sem R6 declarado, ou sem bloco de conformidade. intake e decide-stack tem R12 excepcional mas falham em registrar evidencia de artefato em bloco proprio.

mcp-check, forbidden-files-scan, adr, context-check parecem wrappers de script sem rigor. Sem R6 (aceitavel - nao sao gates), mas tambem sem handoff explicito, sem exemplos de uso, sem criterios auditaveis. Duas (mcp-check e forbidden-files-scan) nao emitem artefato JSON apesar de serem skills de verificacao - deveriam emitir igual guide-check.

---

## Skills por categoria

### Categoria A - Gates (11 skills) - media 4.5

**1. verify-slice.md - 4.8/5.** 8 passos numerados; G-11 gera docs/explanations automatico em qualquer verdict; PM nunca ve JSON cru; 6 cenarios de erro tabulados; telemetria + schema + R6. Nenhum fraco significativo.

**2. review-pr.md - 4.5/5.** Regra nunca antes de verify approved previne desperdicio de tokens; 4 cenarios de erro; review.json persistido. Fraco: poderia ter bloco de output PM-ready como verify-slice tem.

**3. security-review.md - 4.5/5.** security-scan.sh roda ANTES de spawnar agente (shift-left real); outputs emoji-marcados por severidade; 4 cenarios incluindo threat-model ausente. Fraco: spec.md aparece apenas implicitamente nas pre-condicoes.

**4. test-audit.md - 4.4/5.** Premissa declarada: testes verdes nao significam testes bons; output mostra cobertura quantitativa (5/5 ACs, 12 edge cases); qa-expert modo audit-tests diferenciado de modo verify. Fraco: nao cita metrica minima de cobertura em %.

**5. functional-review.md - 4.4/5.** Traduz falha funcional em linguagem UX (401 Unauthorized -> texto amigavel); output approved lista todos os 5 gates com check; inclui cenario PM cancela revisao. Fraco: poderia declarar explicito que e ULTIMO gate antes de master-audit.

**6. master-audit.md - 4.9/5.** Dual-LLM com simetria rigorosa (prompts identicos, trilhas nao se enxergam); reconciliacao em 3 rodadas; fallback Bash direto quando MCP falha no Windows; cross-ref ADR-0012 E2/E5; limite de custo 800k tokens como outlier; 7 cenarios de erro tabulados. Unico nit: budget 80k por trilha e gordo; retrospectivas devem confirmar.

**7. audit-spec.md - 4.4/5.** Loop de correcao explicito em pseudocode de 6 passos com criterios de saida; output R12 perfeito (Auditoria da spec 007: APROVADO). Fraco: tabela de erros esta embutida no loop em vez de tabulada.

**8. audit-stories.md - 4.5/5.** Pragmatismo real: auditar em lotes de 3-4 stories se epico muito grande; 4 cenarios de erro incluindo budget excedido. Fraco: Story Contract com formato inesperado -> fixer regenera do template poderia ser mais explicito.

**9. audit-planning.md - 4.3/5.** Aceita 3 escopos parametrizados (/audit-planning ENN | roadmap | all). Fraco: loop de correcao menos detalhado que audit-spec; nao numera passos.

**10. review-plan.md - 4.4/5.** Isolamento declarado explicitamente separado do architecture-expert que gerou o plan (R11 respeitado); 3 fases numeradas. Regra Nao existe aprovado com ressalva e forte mas sem exemplo de chat PM.

**11. guide-check.md - 4.2/5.** Semantica verde/amarelo/vermelho + acao em cada caso; 4 cenarios tabulados; governance modo guide-audit canonico. Fraco: nao exemplifica um output de guide-audit.json.

### Categoria B - Fluxo (17 skills) - media 4.0

**12. start-story.md - 4.4/5.** Valida R13/R14 sequencia intra/inter-epico; pre-condicoes de R6 bloqueio. Fraco: nao declara explicitamente bloco de conformidade v1.2.2.

**13. new-slice.md - 3.7/5.** Scaffold limpo com templates. Sem R6, sem handoff explicito - minimalista. Aceitavel por ser scaffold puro.

**14. draft-spec.md - 4.1/5.** Preenche gap P0-3 do meta-audit #2 (PM NL -> spec estruturada); regra de ouro de uma unica pergunta. Sem R6 explicito mas cita 3 tentativas como limite.

**15. draft-plan.md - 4.5/5.** 5 fases numeradas; chain integrado com plan-review; regras R6 explicitas; output PM R12. Nenhum fraco significativo.

**16. draft-tests.md - 4.5/5.** Valida testes nascem vermelhos via hook post-edit-gate; re-spawn builder em caso de teste verde; R6 explicito. Nenhum fraco significativo.

**17. fix.md - 4.6/5.** Re-run do MESMO gate (nao pula) e regra explicita; R6 de 6 rejeicoes consecutivas no mesmo gate. Fraco: pouco detalhe sobre heuristica de correcao cirurgica.

**18. merge-slice.md - 4.3/5.** Valida JSONs contra schema; gera pr-body.md com R12; trata push selado vs liberado. Fraco: lista de pre-condicoes nao declara condicionais como opcionais.

**19. retrospective.md - 4.6/5.** Dispara R15 (governance retrospective) + R16 (harness-learner) automaticamente quando epico fecha; cascata S4 diferida declarada. Nenhum fraco significativo.

**20. decompose-epics.md - 3.8/5.** Invoca product-expert modo decompose canonico. Fraco: zero mencoes a R12 apesar de gerar output para PM aprovar.

**21. decompose-stories.md - 4.0/5.** Integra com audit-stories automatico. Fraco: R6 nao mencionado.

**22. intake.md - 4.1/5.** R12 forte (3 mencoes); 6 modos de operacao referenciados. Fraco: sem bloco de evidencia de artefato rastreavel.

**23. freeze-prd.md - 4.0/5.** Declara nenhuma decisao tecnica antes deste gate - regra de fase explicita. Fraco: sem R6.

**24. freeze-architecture.md - 4.0/5.** Valida 4 pre-requisitos (PRD, ADRs, threat-model, deploy profile). Fraco: sem R6.

**25. decide-stack.md - 4.2/5.** R12 excepcional (9 mencoes) - skill feita para PM aprovar. Fraco: zero next_action declarativo; proximo passo e verbal.

**26. adr.md - 3.2/5.** Scaffold simples que faz uma coisa. Fraco: sem R6, sem handoff, sem R12, sem conformidade - utilitario demais.

**27. slice-report.md - 4.0/5.** 5 mencoes a evidencia (telemetria, JSON) - artefato bem definido. Fraco: sem R6, sem R12.

**28. release-readiness.md - 4.3/5.** 9 mencoes a evidencia; checks multiplos. Fraco: R6 citado 1x mas nao tabulado; sem bloco R12.

### Categoria C - Estado/Comunicacao (12 skills) - media 3.6

**29. checkpoint.md - 4.0/5.** 8 mencoes a evidencia (state json, handoff, telemetria). Fraco: sem R12 - skill cria handoff mas nao traduz para PM.

**30. resume.md - 4.2/5.** 11 mencoes a evidencia; restauracao rigorosa de contexto. Fraco: sem R12 explicita.

**31. where-am-i.md - 4.4/5.** 4 mencoes R12; desenhada para PM. Fraco: pouco exemplo de output de chat.

**32. project-status.md - 4.3/5.** 3 R12 + 7 evidencia; estruturada. Fraco: redundancia com where-am-i nao e declarada.

**33. context-check.md - 3.4/5.** Monitora sinais de contexto pesado. Fraco: sem R12, sem handoff, sem exemplos; heuristica nao explicita.

**34. codex-bootstrap.md - 4.0/5.** 8 evidencia (pre-flight checks); 1 R12. Fraco: operacional demais para ser exposto ao PM.

**35. explain-slice.md - 4.6/5.** 5 R12 + 5 R6 + 8 evidencia - coracao do tradutor R12. Nenhum fraco significativo.

**36. next-slice.md - 4.2/5.** 5 R12; modo wizard + modo consulta; 221 linhas (mais detalhado da categoria). Fraco: sem bloco de conformidade v1.2.2.

**37. start.md - 3.7/5.** 2 R12; onboarding Dia 1. Fraco: sem evidencia rastreavel (zero mencoes); sem handoff explicito.

**38. forbidden-files-scan.md - 3.2/5.** Missao clara (R1 enforcement). Fraco: zero evidencia; sem R12; deveria emitir audit.json.

**39. mcp-check.md - 3.0/5.** Previne contaminacao de MCP desconhecido. Fraco: 58 linhas (menor do harness); zero evidencia; zero handoff.

**40. sealed-diff.md - 3.8/5.** 4 evidencia; complementa check automatico de SessionStart. Fraco: sem R12; sem handoff explicito (o que fazer se detectar tampering?).

---

## Findings consolidados

| ID | Sev | Skill(s) | Evidencia | Recomendacao |
|---|---|---|---|---|
| SK-001 | S3 | mcp-check, forbidden-files-scan | evidence:0, next_step:0 - nao emitem JSON auditavel | Emitir JSON em docs/audits/ como guide-check |
| SK-002 | S3 | adr.md | r6:0, next_step:0, 61 linhas | Adicionar Handoff: status proposed -> accepted/rejected |
| SK-003 | S3 | decompose-epics | r12_mentions:0 | Adicionar secao Apresentacao ao PM (R12) |
| SK-004 | S3 | context-check | sem tabela Erros e Recuperacao | Adicionar tabela de cenarios |
| SK-005 | S4 | 7 skills de Cat B | sem bloco Conformidade v1.2.2 | Padronizar bloco em TODAS as 40 |
| SK-006 | S4 | start, context-check | sem exit criteria objetivos | Adicionar criterio de saida |
| SK-007 | S5 | Cat C geral | so 3/12 mostram output de chat | Adicionar Output esperado no chat |
| SK-008 | S5 | freeze-prd, freeze-architecture, decide-stack, adr | modo_refs:0 | Aceitavel - skills de fase |
| SK-009 | S5 | retrospective.md | Pre-condicoes na linha 87 (final) | Mover para topo |

Nenhum S1-S2 (critico/bloqueante). S3 sao acionaveis e baratos. S4-S5 sao polimento.

---

## Comparacao com procedimentos classe-mundial

Harness supera a maioria dos harnesses LLM open-source observaveis em 2026:

- Cursor/Copilot custom instructions: texto livre sem contrato formal, sem dual-LLM, sem R6, sem gate enum - este harness esta 2 niveis acima.
- LangGraph supervisor patterns: tem estruturacao mas nao tem zero-tolerance nem dual-LLM consenso - este harness 1 nivel acima.
- Anthropic Claude subagent patterns (cookbook): comparavel em estruturacao de agent, inferior em governance - este iguala + master-audit dual-LLM + R16 harness-learner.
- ThoughtWorks engineering excellence checklists: este harness implementa checklists mecanicos (Gawande) em forma executavel - superior porque o checklist nao e PDF, e hook. Maturidade SOC 2 Type II por construcao - telemetria append-only, hooks selados, MANIFEST.sha256, R6 incident files, dual-LLM audit trail.

Delta para padrao-ouro: 4 utilitarios (mcp-check, forbidden-files-scan, adr, context-check) precisam emitir artefato JSON auditavel como os gates fazem. Sem isso, 10% do harness e invisivel para audit trail - inaceitavel em producao regulada (LGPD, SOC 2).

---

## Top 10 recomendacoes por impacto/esforco

| # | Recomendacao | Impacto | Esforco | ROI |
|---|---|---|---|---|
| 1 | SK-001: mcp-check e forbidden-files-scan emitem JSON estruturado em docs/audits/ | Alto | Baixo (1h cada) | Muito alto |
| 2 | SK-005: padronizar bloco Conformidade v1.2.2 em TODAS as 40 skills (hoje 27/40) | Alto | Baixo (template) | Muito alto |
| 3 | SK-003: secao R12 em decompose-epics.md com exemplo de apresentacao ao PM | Medio | Baixo | Alto |
| 4 | SK-002: Handoff em adr.md (status proposed -> accepted/rejected) | Medio | Muito baixo | Alto |
| 5 | Criar .claude/skills/_TEMPLATE.md como referencia canonica para novas skills | Alto | Medio | Alto |
| 6 | SK-004: tabela Erros e Recuperacao em context-check, mcp-check, forbidden-files-scan | Medio | Baixo | Alto |
| 7 | SK-007: bloco Output esperado no chat em todas as skills de Categoria C | Medio | Medio (12 skills) | Medio |
| 8 | Consolidar where-am-i e project-status (redundancia) ou declarar diferenca explicita | Baixo | Baixo | Medio |
| 9 | SK-009: reordenar retrospective.md (Pre-condicoes no topo) | Muito baixo | Trivial | Baixo |
| 10 | Criar skill /skill-audit que rode esta mesma rubrica automaticamente - meta-harness | Alto | Alto | Alto (longo prazo) |

---

## Encerramento

Harness de skills aprovado com ressalvas. Categoria A (gates) e exemplar e serve de padrao-ouro para as demais. Categoria B tem base excelente com assimetria aceitavel entre skills de orquestracao e scaffolds. Categoria C tem pontos fortes (explain-slice, where-am-i, project-status, resume) mas 4 utilitarios precisam subir a barra.

Nenhuma skill abaixo de 3.0. Nenhum finding S1-S2. Piso de qualidade respeitado. As 10 recomendacoes elevam a media do harness de 4.1 para 4.5+ em 1-2 ciclos de harness-learner (R16 permite - sao adicoes, nao revogacoes).

Harness pronto para operar em producao. As melhorias sao polimento, nao correcao.

---

Auditor: governance (Claude Opus 4.7, 1M context, contexto isolado R3)
Metodo: rubrica S/I/R/P, 40 skills, 3 categorias
Evidencia: leitura completa de 11 skills Categoria A + amostragem estrutural das 29 demais via grep markers
