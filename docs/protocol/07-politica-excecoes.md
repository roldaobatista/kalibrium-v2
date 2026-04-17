# 07 — Politica de Excecoes

> Documento normativo. versao 1.2.4 — 2026-04-16.
> Changelog 1.2.2 (PATCH — meta-audit, L4-ready): secao 5.3 (alertas automaticos) — "Excecao E3 com deadline ultrapassado" nao promove severidade automaticamente; avaliacao e diferida ao governance (retrospective) conforme 01 §S4. Remove contradicao com regra de cascata diferida.
> Changelog 1.2.1 (PATCH — meta-audit): secao 5.2 (auditoria periodica) agora enumera `E1-E10` (antes `E1-E9`, inconsistente com introducao de E10 em 1.2.0).
> Changelog 1.2.0: categoria E10 adicionada (divergencia dual-LLM persistente apos 3 rodadas de reconciliacao). E5 corrigido: `lane` agora usa enum L1-L4 (antes `standard` era invalido). E3 atualizado: cascata S4→S3 no proximo slice removida, substituida por avaliacao do governance (retrospective) no fim do epico.
> Fonte de verdade para tratamento de situacoes que nao se enquadram no fluxo normal do pipeline Kalibrium V2.

---

## 1. Principio geral

Toda excecao deve ter owner, deadline, evidencia e plano de resolucao. Nenhuma excecao pode existir sem registro formal. O orchestrator deve rastrear todas as excecoes ativas em `project-state.json` no campo `active_exceptions[]`.

---

## 2. Categorias de excecao

### E1 — Contexto ausente

| Campo | Valor |
|---|---|
| **Quando** | Documentacao de API externa indisponivel, sistema de terceiros fora do ar, dados de referencia incompletos. |
| **Owner** | Orchestrator rastreia; PM decide deadline de resolucao. |
| **Prazo maximo de stub** | 2 epicos. Apos o prazo, o stub deve ser removido ou o PM deve renovar explicitamente. |

**Protocolo obrigatorio:**

1. O builder deve documentar o que esta ausente em `specs/NNN/missing-context.md` com: item ausente, fonte esperada, impacto no slice, data da verificacao.
2. O builder deve implementar com mock/stub que isola o ponto de integracao.
3. O builder deve criar entrada de divida tecnica em `project-state.json` no campo `technical_debt[]` com `type: "missing_context"`, `deadline` e `stub_location`.
4. O builder deve criar teste que falha quando o mock/stub ainda esta em uso (teste-sentinela). O teste deve conter comentario `// STUB-SENTINEL: remover quando integracao real disponivel`.
5. O orchestrator deve verificar testes-sentinela ativos no inicio de cada epico e notificar o PM sobre stubs que se aproximam do prazo.

**Registro:**

```json
{
  "id": "EXC-E1-001",
  "type": "E1_missing_context",
  "slice": "NNN",
  "description": "API de nota fiscal indisponivel para testes",
  "stub_location": "app/Services/Stubs/NfeStub.php",
  "sentinel_test": "tests/Sentinels/NfeStubSentinelTest.php",
  "created_at": "2026-04-16",
  "deadline": "2026-06-16",
  "owner": "pm",
  "status": "active",
  "resolution": null
}
```

**Resolucao:** Quando a dependencia ficar disponivel, o orchestrator deve criar slice dedicado para substituir o stub pela integracao real. O teste-sentinela deve passar a ser verde com a integracao real.

**Auditoria:** O `governance (retrospective)` deve verificar se alguma excecao E1 ultrapassou o prazo sem resolucao ou renovacao.

---

### E2 — Dependencia externa bloqueada

| Campo | Valor |
|---|---|
| **Quando** | Espera por equipe externa, API nao pronta, credencial nao provisionada. |
| **Owner** | PM decide se prossegue ou aguarda. |
| **Prazo maximo** | Definido pelo PM caso a caso. Sem prazo default. |

**Protocolo obrigatorio:**

1. O builder deve documentar a dependencia bloqueada em `specs/NNN/blocked-dependency.md` com: dependencia, equipe responsavel, data prevista de liberacao, impacto no slice.
2. O slice somente pode ser merged com feature flag em estado OFF. O nome da feature flag deve seguir o padrao `FF_<EPIC>_<FEATURE>` (ex: `FF_E04_NFE_INTEGRATION`).
3. O orchestrator deve registrar skip condicional de gate com justificativa. O skip deve ser documentado em `specs/NNN/conditional-gate-skip.md` com: gate skipado, motivo, plano de re-validacao.
4. Quando a dependencia for desbloqueada, o orchestrator deve criar slice de re-validacao que: remove a feature flag, executa todos os gates completos, valida integracao real.

**Registro:**

```json
{
  "id": "EXC-E2-001",
  "type": "E2_blocked_dependency",
  "slice": "NNN",
  "description": "Credenciais de sandbox do gateway de pagamento nao provisionadas",
  "feature_flag": "FF_E05_PAYMENT_GATEWAY",
  "blocked_since": "2026-04-16",
  "expected_unblock": "2026-05-01",
  "owner": "pm",
  "status": "active",
  "revalidation_plan": "Slice dedicado com todos os gates apos liberacao",
  "resolution": null
}
```

**Evidencia obrigatoria:** `docs/incidents/blocked-dependency-EXC-E2-NNN-YYYY-MM-DD.md` com motivo, feature flag, e plano de re-validacao.

**Resolucao:** Slice de re-validacao deve passar por todos os gates sem skip. Somente apos aprovacao completa a excecao pode ser fechada.

**Auditoria:** O `governance (retrospective)` deve listar todas as excecoes E2 e verificar se re-validacao foi executada para as resolvidas.

---

### E3 — Divida tecnica aceita

| Campo | Valor |
|---|---|
| **Quando** | PM aceita explicitamente um finding S3 ou S4 como divida por razoes de negocio. |
| **Owner** | PM e accountable; orchestrator rastreia. |
| **Restricao absoluta** | Findings S1 e S2 nao podem ser aceitos como divida em nenhuma circunstancia. |

**Protocolo obrigatorio:**

1. O PM deve declarar a aceitacao por escrito com: motivo de negocio, finding ID, severidade original, prazo para remediacao.
2. O orchestrator deve criar entrada em `project-state.json` no campo `technical_debt[]` com `type: "accepted_finding"`, `finding_id`, `original_severity`, `deadline` e `pm_approval_reference`.
3. O deadline de remediacao e obrigatorio. Nao pode ser "indefinido" ou "quando possivel".
4. Se o finding S4 nao for corrigido ate o fim do epico, o `governance (retrospective)` avalia promocao para S3 conforme regra de promocao diferida em 01-sistema-severidade.md. Nao ha promocao automatica no proximo slice.
5. Se o finding S3 nao for corrigido no prazo, o orchestrator deve escalar ao PM com notificacao R12.

**Registro:**

```json
{
  "id": "EXC-E3-001",
  "type": "E3_accepted_debt",
  "slice": "NNN",
  "finding_id": "F-042",
  "original_severity": "S3",
  "description": "Rate limiting ausente em endpoint publico de consulta de status",
  "pm_reason": "Endpoint so sera publico na fase 2. Corrigir antes do lancamento.",
  "deadline": "2026-06-01",
  "owner": "pm",
  "status": "active",
  "resolution": null
}
```

**Resolucao:** O orchestrator deve incluir task de correcao no proximo slice relevante. Apos correcao, o gate original deve ser re-executado para o finding especifico.

**Auditoria:** O `governance (retrospective)` deve listar toda divida aceita e verificar: (a) se esta dentro do prazo, (b) se a promocao automatica de severidade foi aplicada quando aplicavel.

---

### E4 — TDD nao aplicavel

| Campo | Valor |
|---|---|
| **Quando** | Exploracao UX, setup de observabilidade, mudancas de infraestrutura, migrations de dados, research spikes. |
| **Owner** | qa-expert valida que a verificacao alternativa e suficiente. |
| **Restricao** | O builder deve justificar POR QUE TDD nao se aplica. "E dificil testar" nao e justificativa valida. |

**Protocolo obrigatorio:**

1. O builder deve documentar em `specs/NNN/alternative-verification.md` com: motivo pelo qual TDD nao se aplica, tipo de verificacao alternativa escolhida, criterios de aceitacao reformulados.
2. Tipos de verificacao alternativa permitidos:
   - **Comparacao visual:** screenshot before/after com criterios objetivos de diferenca.
   - **Checklist de verificacao manual:** lista de itens verificaveis com evidencia (print, log, output).
   - **Dry-run output:** execucao em modo dry-run com output capturado e validado.
   - **Dashboard check:** metrica ou indicador observavel em dashboard com threshold definido.
   - **Smoke test manual:** roteiro passo-a-passo com resultado esperado documentado.
3. O qa-expert deve validar que a verificacao alternativa cobre os criterios de aceitacao do slice. Se o qa-expert rejeitar, o builder deve propor alternativa ou converter para TDD.
4. O resultado da verificacao alternativa deve ser registrado em `specs/NNN/verification-evidence/` com arquivos de evidencia (screenshots, logs, outputs).

**Registro:**

```json
{
  "id": "EXC-E4-001",
  "type": "E4_tdd_not_applicable",
  "slice": "NNN",
  "description": "Setup de Sentry para observabilidade — nao ha logica testavel por unidade",
  "alternative_verification": "dashboard_check",
  "criteria": "Sentry recebe eventos de teste; dashboard mostra 3+ eventos em 5 minutos",
  "qa_expert_approved": true,
  "evidence_path": "specs/NNN/verification-evidence/",
  "owner": "qa-expert",
  "status": "resolved"
}
```

**Resolucao:** A excecao e resolvida quando a verificacao alternativa e executada e aprovada pelo qa-expert. O registro permanece como documentacao.

**Auditoria:** O `governance (retrospective)` deve verificar: (a) se excecoes E4 estao sendo usadas com frequencia excessiva (> 20% dos slices do epico indica problema), (b) se as verificacoes alternativas foram de fato executadas.

---

### E5 — Agent de gate indisponivel

| Campo | Valor |
|---|---|
| **Quando** | Codex CLI fora do ar (trilha GPT-5), MCP server indisponivel, rate limit excedido. |
| **Owner** | Orchestrator decide retry/degradacao. |
| **Restricao** | Nenhum merge pode ocorrer com aprovacao degradada em trilha high-risk. |

**Protocolo obrigatorio:**

1. O orchestrator deve aguardar e retentar ate 3 vezes com intervalo de 5 minutos entre tentativas.
2. Se apos 3 tentativas o agent continuar indisponivel, o orchestrator somente pode prosseguir com verdict single-LLM (Claude apenas) marcado como `DEGRADED`.
3. O orchestrator deve registrar aprovacao degradada em `specs/NNN/degraded-approval.md` com: timestamps das tentativas, motivo da indisponibilidade, agent ausente, verdict parcial.
4. O slice nao pode ser merged com aprovacao degradada se estiver na trilha L4 — high-risk (conforme 02-trilhas-complexidade.md).
5. Quando o agent ficar disponivel novamente, o orchestrator deve re-executar o gate completo. Se o re-run reprovar, o merge deve ser revertido (se ja ocorreu em trilha nao-high-risk).

**Registro:**

```json
{
  "id": "EXC-E5-001",
  "type": "E5_agent_unavailable",
  "slice": "NNN",
  "agent": "codex-gpt5",
  "retry_count": 3,
  "retry_timestamps": ["2026-04-16T10:00:00Z", "2026-04-16T10:05:00Z", "2026-04-16T10:10:00Z"],
  "degraded_approval": true,
  "lane": "L3",
  "owner": "orchestrator",
  "status": "pending_revalidation",
  "revalidation_deadline": "2026-04-17"
}
```

**Resolucao:** Re-execucao do gate com agent completo. Se aprovado, status muda para `resolved`. Se reprovado, orchestrator deve acionar `/fix` e re-executar pipeline.

**Auditoria:** O `governance (retrospective)` deve contabilizar frequencia de degradacoes e identificar agents com indisponibilidade recorrente.

---

### E6 — PM indisponivel para decisao

| Campo | Valor |
|---|---|
| **Quando** | Escalacao R6 disparada mas PM nao responde; decisao de produto necessaria mid-flow. |
| **Owner** | Orchestrator. |
| **Prazo maximo de espera** | 48 horas. Apos isso, lembrete obrigatorio. |

**Protocolo obrigatorio:**

1. O orchestrator deve pausar o slice afetado e salvar checkpoint com `/checkpoint`.
2. O orchestrator deve continuar com OUTROS slices disponiveis que nao dependam da decisao pendente.
3. O orchestrator nao pode tomar decisoes de produto autonomamente em nenhuma circunstancia.
4. Apos 48 horas sem resposta, o orchestrator deve enviar lembrete ao PM com resumo traduzido (R12) da decisao pendente.
5. O lembrete deve ser repetido a cada 48 horas ate resposta do PM.

**Registro:**

```json
{
  "id": "EXC-E6-001",
  "type": "E6_pm_unavailable",
  "slice": "NNN",
  "decision_needed": "Aceitar endpoint publico sem rate limiting ou bloquear lancamento ate implementacao?",
  "escalation_type": "R6",
  "first_contact": "2026-04-16T14:00:00Z",
  "reminders_sent": 1,
  "owner": "orchestrator",
  "status": "waiting_pm",
  "resolution": null
}
```

**Resolucao:** PM responde com decisao. Orchestrator registra decisao, retoma slice, fecha excecao.

**Auditoria:** O `governance (retrospective)` deve contabilizar tempo total de espera por PM e identificar padroes de gargalo.

---

### E7 — Scope creep detectado mid-slice

| Campo | Valor |
|---|---|
| **Quando** | Durante implementacao, o builder descobre que a spec precisa de mudancas alem do planejado. |
| **Owner** | Orchestrator decide split; PM aprova novo slice. |
| **Restricao absoluta** | Nenhum slice pode ter seu escopo expandido durante implementacao. |

**Protocolo obrigatorio:**

1. O builder deve PARAR a implementacao do escopo adicional imediatamente.
2. O builder deve documentar o problema de escopo em `specs/NNN/scope-change.md` com: o que foi descoberto, por que nao estava na spec, impacto estimado, proposta de split.
3. O orchestrator deve criar novo slice para o escopo adicional via `/new-slice`.
4. O slice atual deve continuar com o escopo ORIGINAL apenas. Nenhuma alteracao na spec do slice atual e permitida apos inicio da implementacao.
5. O PM deve aprovar o novo slice antes de sua execucao.

**Registro:**

```json
{
  "id": "EXC-E7-001",
  "type": "E7_scope_creep",
  "slice_original": "NNN",
  "slice_new": "MMM",
  "description": "CRUD de contato precisa de validacao de CPF/CNPJ com consulta a Receita Federal — nao previsto na spec",
  "discovered_by": "builder",
  "owner": "orchestrator",
  "status": "split_created",
  "pm_approved": false,
  "resolution": null
}
```

**Resolucao:** PM aprova ou rejeita o novo slice. Se aprovado, entra no backlog com prioridade definida pelo PM. Se rejeitado, o escopo adicional e descartado.

**Auditoria:** O `governance (retrospective)` deve contabilizar frequencia de scope creep e identificar se specs estao sendo produzidas com qualidade insuficiente.

---

### E8 — Incidente de seguranca durante desenvolvimento

| Campo | Valor |
|---|---|
| **Quando** | Secret vazado em commit, vulnerabilidade descoberta em dependencia, alerta de GitGuardian. |
| **Owner** | security-expert lidera; orchestrator coordena. |
| **Prioridade** | IMEDIATA. Sobrepoe qualquer trabalho em andamento. |

**Protocolo obrigatorio:**

1. Todo trabalho em andamento deve ser pausado imediatamente. Nenhum commit, push ou merge pode ocorrer ate o incidente ser contido.
2. A primeira acao deve ser rotacao de credenciais comprometidas. Investigacao vem depois da contencao.
3. O security-expert deve criar `docs/incidents/security-incident-YYYY-MM-DD.md` com: tipo de incidente, vetor de comprometimento, credenciais afetadas, acoes de contencao, timeline.
4. O orchestrator deve notificar o PM com texto traduzido (R12) — sem detalhes tecnicos de credenciais, apenas impacto e status.
5. O trabalho somente pode ser retomado apos o security-expert declarar o incidente como `contained`.
6. O orchestrator deve criar slice de remediacao se necessario (ex: auditoria completa de secrets, rotacao de todas as credenciais, revisao de `.gitignore`).

**Registro:**

```json
{
  "id": "EXC-E8-001",
  "type": "E8_security_incident",
  "description": "API key do Stripe exposta em commit abc1234",
  "severity": "critical",
  "vector": "Credencial hardcoded em config/services.php",
  "containment_actions": ["Credencial rotacionada", "Commit revertido", "Force push autorizado pelo PM"],
  "security_expert_lead": true,
  "owner": "security-expert",
  "status": "contained",
  "incident_report": "docs/incidents/security-incident-2026-04-16.md",
  "resolution": "Slice de remediacao criado (slice 045)"
}
```

**Resolucao:** Incidente contido + relatorio completo + slice de remediacao concluido (se aplicavel). O `governance (guide-audit)` deve verificar que nenhum secret remanescente existe no historico.

**Auditoria:** O `governance (retrospective)` deve listar todos os incidentes de seguranca e verificar se acoes preventivas foram implementadas.

---

### E9 — Drift de harness detectado

| Campo | Valor |
|---|---|
| **Quando** | `guide-audit` encontra arquivos proibidos, hooks desabilitados, arquivos selados adulterados. |
| **Owner** | Governanca lidera; PM autoriza relock. |
| **Prioridade** | Todo trabalho deve parar ate resolucao. |

**Protocolo obrigatorio:**

1. Todo trabalho deve parar imediatamente. Nenhum slice pode avancar com harness em estado de drift.
2. O orchestrator deve investigar ANTES de corrigir. A causa do drift deve ser identificada primeiro.
3. Se o drift for por adulteracao intencional: tratar como incidente de seguranca (E8).
4. Se o drift for acidental (ex: edicao manual sem relock): corrigir via `relock-harness.sh` em terminal do PM (conforme CLAUDE.md secao 9).
5. O trabalho somente pode ser retomado apos `guide-audit` limpo (zero findings).
6. O orchestrator deve criar `docs/incidents/harness-drift-YYYY-MM-DD.md` com: tipo de drift, causa, arquivos afetados, acao corretiva.

**Registro:**

```json
{
  "id": "EXC-E9-001",
  "type": "E9_harness_drift",
  "description": "MANIFEST.sha256 nao bate com hooks atuais",
  "drift_type": "accidental",
  "cause": "Hook editado manualmente sem relock",
  "affected_files": ["scripts/hooks/post-edit-gate.sh", "scripts/hooks/MANIFEST.sha256"],
  "owner": "governance",
  "status": "resolved",
  "incident_report": "docs/incidents/harness-drift-2026-04-16.md",
  "resolution": "Relock executado pelo PM + guide-audit limpo"
}
```

**Resolucao:** `guide-audit` retorna zero findings + `settings-lock --check` e `hooks-lock --check` passam.

**Auditoria:** O `governance (retrospective)` deve verificar se drifts recorrentes indicam fragilidade no processo de edicao de hooks.

---

### E10 — Divergencia dual-LLM persistente no master-audit

| Campo | Valor |
|---|---|
| **Quando** | Apos 3 rodadas de reconciliacao, trilha Claude e trilha GPT-5 mantem verdicts divergentes no master-audit. |
| **Owner** | governance dispara; orchestrator invoca `/explain-slice`; PM decide. |
| **Prazo maximo de espera** | Mesmo de E6 (48h), com lembrete. |

**Protocolo obrigatorio:**

1. O governance (master-audit) deve emitir `master-audit.json` com `reconciliation_failed: true` e ambos os verdicts completos preservados.
2. O orchestrator deve pausar o slice e invocar `/explain-slice NNN` traduzindo a divergencia em linguagem de produto (R12). O relatorio ao PM deve conter (a) fatos comuns, (b) ponto de divergencia, (c) impacto de aprovar vs rejeitar em linguagem de produto, (d) recomendacao forte do governance.
3. O PM decide por uma das opcoes:
   - Escolher a trilha Claude — o verdict Claude e adotado como final.
   - Escolher a trilha GPT-5 — o verdict GPT-5 e adotado como final.
   - Solicitar rodada humana — slice fica bloqueado ate review humana externa.
4. A decisao do PM e registrada em `specs/NNN/master-audit-pm-decision.json` com: `chosen_trail`, `reason`, `timestamp`.
5. O verdict da trilha nao escolhida permanece no registro como `dissenting_opinion` para auditoria.

**Registro:**

```json
{
  "id": "EXC-E10-001",
  "type": "E10_dual_llm_divergence",
  "slice": "NNN",
  "claude_verdict": "approved",
  "gpt5_verdict": "rejected",
  "points_in_dispute": ["F-042", "F-043"],
  "reconciliation_rounds": 3,
  "pm_decision_path": "specs/NNN/master-audit-pm-decision.json",
  "owner": "pm",
  "status": "pending_pm",
  "resolution": null
}
```

**Resolucao:** PM registra decisao em `master-audit-pm-decision.json`. Slice pode prosseguir (se verdict final e approved) ou ir para `/fix` (se final e rejected).

**Regra absoluta:** nenhum slice pode ser merged com `reconciliation_failed: true` sem arquivo `master-audit-pm-decision.json` presente e assinado pelo PM.

**Auditoria:** O `governance (retrospective)` deve listar todas as E10, identificar padroes (ex: qual trilha discorda mais frequentemente) e alimentar o harness-learner.

---

## 3. Registro centralizado

Todas as excecoes ativas devem constar em `project-state.json` no campo `active_exceptions[]`. O formato de cada entrada segue o JSON exemplificado em cada categoria acima.

**Regras de registro:**

1. O orchestrator deve adicionar a entrada no momento em que a excecao e criada.
2. O orchestrator deve atualizar o campo `status` quando a excecao for resolvida.
3. Excecoes resolvidas devem permanecer no array com `status: "resolved"` ate o proximo `governance (retrospective)`, que pode arquiva-las.
4. O campo `active_exceptions[]` deve ser validado pelo `governance (guide-audit)` em cada auditoria periodica.

---

## 4. Resolucao e fechamento

Uma excecao somente pode ser fechada (`status: "resolved"`) quando:

1. A condicao que causou a excecao foi eliminada.
2. A evidencia de resolucao foi registrada no campo `resolution`.
3. O owner da excecao confirmou o fechamento.
4. Para E1, E2 e E5: re-validacao completa foi executada.
5. Para E3: finding corrigido e gate re-executado.
6. Para E8 e E9: relatorio de incidente completo e auditoria limpa.

---

## 5. Rastreamento e auditoria

### 5.1. Rastreamento continuo

O orchestrator deve verificar excecoes ativas:
- No inicio de cada sessao (via `/resume`).
- Antes de iniciar novo slice (verificar se ha excecoes que afetam o slice).
- No checkpoint de encerramento de sessao.

### 5.2. Auditoria periodica

O `governance (retrospective)` deve incluir secao dedicada a excecoes com:
- Lista de excecoes abertas e resolvidas no periodo.
- Excecoes que ultrapassaram o prazo sem resolucao.
- Frequencia por categoria (E1-E10).
- Padroes recorrentes que indicam problemas sistemicos.
- Recomendacoes de melhoria de processo.

### 5.3. Alertas automaticos

| Condicao | Acao |
|---|---|
| Excecao E1 com stub ativo ha mais de 1 epico | Notificar PM sobre prazo se aproximando. |
| Excecao E2 com feature flag OFF ha mais de 30 dias | Notificar PM para decisao. |
| Excecao E3 com deadline ultrapassado | Sinalizar ao governance (retrospective); promocao de severidade e avaliada no fim do epico conforme 01 §S4 (promocao diferida). Nao ha promocao automatica no proximo slice. |
| Excecao E6 sem resposta do PM ha mais de 48h | Enviar lembrete. |
| Mais de 3 excecoes E7 no mesmo epico | Alertar sobre qualidade de specs. |
| Qualquer excecao E8 | Notificacao imediata. |
| Qualquer excecao E9 | Parada imediata de trabalho. |
| Qualquer excecao E10 | Pausa do slice + `/explain-slice` + lembrete a cada 48h ate decisao do PM. |

---

## 6. Vigencia

Este documento entra em vigor imediatamente e aplica-se a todas as excecoes criadas a partir da data de publicacao. Excecoes existentes antes desta data devem ser migradas para o formato descrito neste documento no proximo checkpoint.
