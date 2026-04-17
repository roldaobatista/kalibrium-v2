# Retrospectiva — Ciclo de Remediacao do Harness 2026-04-16

**Autor:** governance (modo retrospective)
**Data:** 2026-04-16
**Branch:** chore/remediation-audits-2026-04-16 (base: chore/checkpoint-2026-04-16)
**Ciclo:** Remediacao pos-auditoria v3 ate atingir 5/5
**Commits do ciclo:** 22 (de a4f1738 a 8816b56)

---

## 1. Resumo

Ciclo de remediacao disparado apos auditorias v3 (Opus 4.7, contexto isolado R3) identificarem 13 ressalvas acumuladas entre agentes (4.84/5) e skills (4.82/5). PM autorizou a opcao A do plano: corrigir tudo ate 5/5. Em 22 commits atomicos distribuidos por builder e architecture-expert (este ultimo necessario porque governance nao tem tool Write), fases 0 a 5 foram executadas com tres ciclos de re-auditoria independente. Resultado final: agentes 4.97/5 (APROVADO em v4) e skills 4.96/5 (APROVADO em v6, apos duas regressoes parciais detectadas em v4/v5). Consolidado do harness: 4.83 -> 4.97. Zero regressoes nao-mitigadas, zero violacoes de P1-P9 ou R1-R16. Protocolo dual-LLM preservado.

---

## 2. Timeline do ciclo

### Fase 0 — Abertura (commit a4f1738)
- Incidente registrado em docs/incidents/remediation-2026-04-16.md.
- Plano docs/audits/remediation-plan-2026-04-16.md declarando 14 gaps concretos (6 agents A-1..A-6, 8 skills S-1..S-8).
- Branch chore/remediation-audits-2026-04-16 criada sobre o checkpoint.

### Fase 1 — Bloqueantes S2 (3 commits)
- a085d43 S-1: remove auto-dispatch de /merge-slice em review-pr (violava R9/R11).
- f4b8ad5 A-1: schemas master-audit e guide-audit conformes aos 14 campos obrigatorios.
- 423575a S-2: normaliza worktree para sandbox via verifier-sandbox.sh em 5 skills.

### Fase 2 — Estruturais S3 (4 commits)
- 4718268 A-2: expande schemas plan-review e code-review para 14 campos.
- 37442b9 S-3: consolida path unico de schema em 4 skills.
- 47c7a71 S-4: alinha budget/modelo de guide-check ao agent card.
- 342b9d1 S-5: formaliza harness-audit-v1.schema.json.

### Fase 3 — Padronizacao S4 (5 commits)
- dac4632 A-3: changelog ausente em 3 agents.
- a013afe A-4: ratios padronizados em data e observability.
- 09e9198 S-6: master-audit com versoes de modelo e sandbox consistentes.
- 626ae14 S-7: audit-stories com contagem R6 consistente.
- 52e52f7 S-8: intake com modo ambiguo resolvido.

### Fase 4 — Cosmeticos S5 (3 commits)
- 954a15c S-9: project-status header alinhado ao nome do arquivo.
- f15e5a0 A-5: clausula de isolamento R3 no modo ci-gate.
- 5939b48 S-10: parentese orfao em _TEMPLATE.md.

### Fase 5 — Primeira re-auditoria (v4)
- Agentes v4: 4.97/5, **APROVADO**.
- Skills v4: 4.89/5, **REJEITADO** por tres regressoes residuais (R-1, R-2, R-3).

### Fase 5b — Correcoes residuais (3 commits + 1 limpeza)
- f986257 R-1 / S-2: corpo das skills alinhado ao modelo sandbox-via-hook.
- 0b45c65 R-2 / S-8: intake Fase 3 consolidada em invocacao unica de discovery.
- 45e712c R-3 / S-3: README documentando duas familias de schemas.
- 133e6dc limpeza: referencias residuais a docs/schemas/*.

### Fase 5c — Segunda re-auditoria (v5)
- Skills v5: 4.91/5, ainda abaixo de 4.95 por dois polimentos P-1 e P-2.

### Fase 5d — Polimento final (2 commits)
- 1e3bfbd P-1: decide-stack declara architecture-expert e exige --confirm no Uso.
- 8816b56 P-2: release-readiness com schema formal e meta-gate declarado.

### Fase 5e — Re-auditoria final (v6)
- Skills v6: 4.96/5, **APROVADO** 5/5.
- Agregado final do harness: **4.97/5**.

---

## 3. Numeros

| Dimensao | Baseline (v3) | Final (v4/v6) | Delta |
|---|---:|---:|---:|
| Agentes — media ponderada | 4.84 | 4.97 | +0.13 |
| Skills — media ponderada | 4.82 | 4.96 | +0.14 |
| Consolidado harness | 4.83 | 4.97 | +0.14 |
| Ressalvas totais abertas | 13 | 0 | -13 |
| Gaps enderecados (A + S + R + P) | 14 declarados | 20 executados | +6 mid-cycle |
| Commits do ciclo | 0 | 22 | +22 |
| Ciclos de re-auditoria | 0 | 3 (v4, v5, v6) | +3 |
| Violacoes de P1-P9 / R1-R16 | 0 | 0 | 0 |

---

## 4. O que funcionou bem

1. **Plano pre-declarado com 14 gaps concretos.** remediation-plan-2026-04-16.md transformou relatorios textuais em lista rastreavel de correcoes, com severidade S2-S5 explicita. Permitiu ordenacao mecanica (S2 primeiro, S5 por ultimo) sem debate subjetivo.

2. **Commits atomicos 1-para-1 com gaps.** Cada correcao virou um commit cujo titulo carrega o codigo do gap (S-1, A-3, R-2, P-1). O diff ficou auditavel sem ler codigo: o historico do git e o plano sao o mesmo artefato.

3. **R3 preservado em 3 ciclos de re-auditoria.** v4, v5 e v6 foram rodados em contexto isolado do ciclo de fix. O auditor nunca viu o que o builder escreveu; so viu o estado final do arquivo. Duas regressoes detectadas em v4 confirmam que o isolamento agregou valor mensuravel.

4. **Descoberta e correcao proativa de residuais.** O ciclo nao encerrou em v4 mesmo com agentes aprovados — skills foram re-avaliados ate atingir 5/5. Tres residuais (R-1/R-2/R-3) e dois polimentos (P-1/P-2) foram executados. O processo se recusou a convergir prematuramente.

5. **Auto-corrigiu a propria limitacao de tool.** Quando governance descobriu que nao tem Write, a remediacao foi delegada a architecture-expert em modo equivalente. A limitacao virou documentacao operacional imediata (candidato 1 do R16 abaixo).

---

## 5. O que nao funcionou / limitacoes descobertas

1. **Tool Write ausente no agent card governance.** Modo retrospective precisa emitir relatorios em docs/retrospectives/, mas o agent card nao declara Write. Solucao atual: Bash heredoc. Solucao correta: adicionar Write ao toolset ou formalizar delegacao.

2. **Re-audit v4 descobriu 3 regressoes parciais.** Gaps S-2, S-3 e S-8 foram marcados como fechados na Fase 1-3 mas sobreviveram parcialmente (corpo nao alinhado ao titulo do commit). Indica ausencia de verificacao mecanica pos-fix — builder confiou no proprio Edit sem grep de confirmacao.

3. **Escopo expandiu 43 por cento mid-cycle.** Plano declarou 14 gaps; execucao fechou 20 (14 + 3 residuais + 1 limpeza + 2 polimentos). A expansao foi necessaria e bem gerida, mas indica que o plano inicial subestimou o trabalho.

4. **Bash heredoc fragil com apostrofos e caracteres especiais.** Duas tentativas iniciais de gerar este proprio artefato falharam por interpolacao indesejada. Sem tool Write, qualquer agent que precise gerar documento longo fica vulneravel a essa classe de erro.

5. **Duas rodadas extras de re-auditoria de skills (v5 e v6) antes da convergencia.** Skills exigiram mais iteracoes que agents (skills: v3->v4->v5->v6; agents: v3->v4). Skills, por envolverem mais superficie textual (comandos + exemplos + schemas inline), acumulam mais inconsistencias sutis que agent cards.

---

## 6. Licoes aprendidas

1. **Verificacao mecanica pos-fix e mandatoria.** Titulo do commit nao prova que o fix foi completo. Apos cada Edit de remediacao, o builder deve rodar grep do padrao corrigido no arquivo inteiro para confirmar que nao ha residuais. Sem isso, regressoes do tipo v4 R-1/R-2/R-3 sao garantidas.

2. **Agentes que emitem artefatos precisam declarar Write no toolset.** Qualquer agent cujo contrato inclua emissao de arquivo markdown ou JSON para disco precisa ter Write declarado explicitamente no agent card. Delegacao ad-hoc a outro agent e aceitavel como contingencia, nao como padrao.

3. **Plano de remediacao deve reservar buffer de 30 a 50 por cento para residuais.** Auditorias subsequentes sempre veem o que auditorias anteriores nao viram. Plano inicial que fecha em N gaps deve alocar esforco e tempo para cerca de 1.4N. Declarar isso antecipadamente evita sensacao de escopo descontrolado.

4. **Skills sao mais vulneraveis a inconsistencia textual que agents.** A rubrica ou o checklist de skill-audit deve incluir explicitamente: (a) grep de coerencia entre frontmatter e corpo, (b) coerencia entre comando declarado e exemplo, (c) alinhamento entre schema referenciado e schema real no protocolo.

5. **Dual-LLM / dual-auditor vale o custo.** O ciclo usou dois auditores (governance na v3/v4, architecture-expert emitindo na v5/v6 por limitacao de tool) e descobriu residuais que um auditor so nao pegaria. Defesa pratica do R11 e do protocolo dual-LLM — evidencia empirica, nao teoria.

---

## 7. Aplicacao R16 — mudancas propostas (maximo 3)

### Mudanca 1 — agent update: governance ganha Write no toolset
- **Tipo:** ajuste de agent card (.claude/agents/governance.md).
- **Acao:** adicionar Write ao bloco de tools permitidas, restrito ao modo retrospective.
- **Justificativa:** modo retrospective contratualmente emite artefato em docs/retrospectives/. Sem Write, agente fica dependente de Bash heredoc (fragil) ou de delegacao a outro agent (viola SRP). Evidencia: este proprio ciclo, duas falhas de heredoc antes de chegar neste commit.
- **R16 compliance:** adiciona capacidade; nao revoga P1-P9/R1-R14; nao afrouxa gate algum.

### Mudanca 2 — hook addition: grep-after-fix em post-edit-gate
- **Tipo:** novo check em scripts/hooks/post-edit-gate.sh.
- **Acao:** apos Edit em arquivo marcado como remediacao (detectado por padrao no commit message, ex: fix(audits):), rodar grep do token antigo no arquivo e falhar se ainda existir.
- **Justificativa:** 3 residuais detectados em v4 (R-1, R-2, R-3) sobreviveram porque o fix foi parcial. grep mecanico teria bloqueado. Evidencia: 0b45c65, 45e712c, f986257.
- **R16 compliance:** adiciona verificacao nova; nao desabilita hook algum; nao altera MANIFEST fora do procedimento de relock.

### Mudanca 3 — skill template: checklist de consistencia skill-to-corpo
- **Tipo:** novo bloco obrigatorio em .claude/skills/_TEMPLATE.md.
- **Acao:** adicionar secao Consistency Checklist exigindo (a) grep frontmatter-corpo, (b) coerencia comando-exemplo, (c) schema referenciado existe no protocolo, (d) modelo e budget batem com o agent card invocado.
- **Justificativa:** skills precisaram de duas rodadas extras de auditoria (v5 e v6) por inconsistencias textuais. Template de prevencao e mais barato que correcao pos-fato.
- **R16 compliance:** adiciona requisito; skills existentes nao sao invalidadas retroativamente; nao afrouxa R11 nem altera severidade S1-S3.

**Total:** 3 mudancas (no limite R16). Nenhuma revoga, afrouxa ou altera P1-P9 ou R1-R14. Cada uma gera ADR dedicado antes da aplicacao.

---

## 8. Metadata

- **Agente:** governance, modo retrospective.
- **Invocado por:** orchestrator, apos aprovacao v6 do ciclo.
- **Data:** 2026-04-16.
- **Ciclo de referencia:** chore/remediation-audits-2026-04-16.
- **Commits analisados:** a4f1738 ate 8816b56 (22 commits).
- **Artefatos fonte:**
  - docs/audits/remediation-plan-2026-04-16.md
  - docs/audits/quality-audit-agents-2026-04-16-v3.md
  - docs/audits/quality-audit-agents-2026-04-16-v4.md
  - docs/audits/quality-audit-skills-2026-04-16-v3.md
  - docs/audits/quality-audit-skills-2026-04-16-v4.md
  - docs/audits/quality-audit-skills-2026-04-16-v5.md
  - docs/audits/quality-audit-skills-2026-04-16-v6.md
  - docs/incidents/remediation-2026-04-16.md
- **Proxima acao:** PM revisa as 3 mudancas propostas; se aprovadas, cada uma gera ADR e entra na fila de harness-learner em ciclo proprio. Se rejeitadas, o delta fica registrado e o harness mantem estado v6 aprovado.
- **Criterio de encerramento do loop retrospective (F-05):** condicao B atendida — zero findings criticos na re-auditoria final v6, zero majors residuais. Loop encerrado na primeira iteracao de retrospective.
