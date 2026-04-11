# Evolução do harness — cadência e critérios

> **Status:** ativo. Item 6.4 dos micro-ajustes da meta-auditoria #2. Define **quando** e **como** as regras do harness (P1-P9, R1-R12, hooks, sub-agents, skills) são revisadas, criadas, alteradas ou revogadas. Complementa `docs/constitution.md §5` (processo de alteração) com cadência operacional.

## 1. Cadência de revisão

### Revisão mensal (primeira segunda de cada mês)

- Checagem do tracker `docs/audits/progress/meta-audit-tracker.md` — status geral, próximos blocos.
- Verificação do contador de admin bypass em `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Contador oficial`.
- Consumo de tokens do agente contra o teto do `operating-budget.md` (RNF-013).
- Status do procurement de consultores (`procurement-tracker.md`).
- Abertura de relatório `docs/reports/monthly-YYYY-MM.md` com os 4 itens acima.

### Revisão trimestral (primeira semana de janeiro, abril, julho, outubro)

- Retrospectiva de blocos fechados no trimestre.
- Revisão das 12 regras `R1-R12` — para cada uma:
  - Está sendo seguida na prática? Evidência do tracker/telemetria.
  - Sofreu pressão para flexibilização? Justificativa.
  - Deveria ser alterada? Abrir ADR de amendment se sim.
- Revisão dos hooks `scripts/hooks/*.sh` — existem hooks desabilitados em staging? Há drift? `/guide-check` está verde?
- Revisão dos sub-agents em `.claude/agents/*.md` — budgets reais vs declarados.
- Relatório `docs/retrospectives/quarterly-YYYY-QN.md`.

### Revisão após cada bloco

- Retrospectiva obrigatória em `docs/retrospectives/bloco-N-YYYY-MM-DD.md`.
- Itens: o que funcionou, o que falhou, lições aprendidas, ações corretivas, atualização do tracker.
- Verificação se o bloco recebeu algum bypass, e justificativa se sim.

### Revisão após incidente severo

Obrigatória após qualquer incidente classificado P0 ou P1 conforme `docs/security/incident-response-playbook.md §2`:

- Postmortem formal em `docs/incidents/postmortem-<slug>-YYYY-MM-DD.md` seguindo o template `docs/templates/postmortem-prod.md`.
- Retrospectiva derivada em `docs/retrospectives/incident-<slug>-YYYY-MM-DD.md`.
- Revisão pontual do subset de P1-P9 e R1-R12 relevante ao incidente.
- Eventual ADR de amendment se a lição aprendida exigir alteração de regra vigente.

## 2. Critérios para propor regra nova

Nova regra (R-NN) só entra se:

1. **Há motivo estrutural.** Não é preferência de autor, é padrão observado que falhou ou vai falhar.
2. **Há mecanismo de enforcement.** Constituição §1 diz "enforcement por arquitetura, não por prompt". Se a nova regra depende de "o agente deve lembrar", não entra — ou entra só quando houver hook que enforce.
3. **Há motivo escrito.** ADR de amendment em `docs/adr/NNNN-constitution-amendment-<slug>.md` com incident ou retrospectiva que motiva + redação proposta + impacto em hooks/sub-agents + plano de rollback (constituição §5).
4. **Não duplica regra existente.** Cada nova R-NN precisa explicar por que as regras existentes são insuficientes.
5. **Tem número seguinte.** R-NN é sempre `R<(última) + 1>`. Não há "R-5b" ou "R-11.1".

## 3. Critérios para revogar ou afrouxar regra

Remoção ou relaxamento exige mais rigor do que adição:

1. **Duas retrospectivas consecutivas** mostrando que a regra é barreira sem benefício proporcional.
2. **ADR de amendment** explícito, com:
   - Regra a revogar (ID + redação atual)
   - Por que deixou de ser necessária
   - Como o objetivo original passa a ser atingido (outra regra existente, novo hook, processo externo)
   - Impacto na segurança e na cultura do projeto
   - Plano de reintrodução caso a revogação se mostre erro
3. **Aprovação do PM** por commit assinado (constituição §5).
4. **Anúncio explícito** na retrospectiva do trimestre em que a remoção acontece.
5. **Nunca remover regra em resposta a incidente.** Incidente pode exigir nova regra, nunca justifica remoção de regra existente — a tentação de "afrouxar para evitar dor futura" é o caminho de drift.

## 4. Critérios para criar novo sub-agent ou nova skill

- **Sub-agent novo:** exige `.claude/agents/<nome>.md` com frontmatter declarando `max_tokens_per_invocation` (R8). Criação precisa de ADR justificando por que os sub-agents existentes são insuficientes (constituição §8).
- **Skill nova:** pode ser criada sem ADR se for operacional (não altera P/R). Skill que muda comportamento estrutural (por exemplo, `/promote-to-prod`) exige ADR.
- **Nova skill precisa ter:** propósito único, comando de invocação, entrada esperada, saída esperada, dependências, tratamento de erro.

## 5. Critérios para criar novo hook

- **Hook novo:** `scripts/hooks/<slug>.sh` com propósito único, declarado na cabeceira.
- **Evento:** SessionStart, PreToolUse, PostToolUse, Stop, UserPromptSubmit — sempre um deles, nunca "múltiplos".
- **Exit code:** 0 = passa, 1 = bloqueia. Zero valor intermediário.
- **Teste:** todo hook novo exige caso em `scripts/smoke-test-hooks.sh`.
- **Relock:** criação de hook exige relock manual pelo PM (procedimento §9 de `CLAUDE.md`). O agente **não** cria hook sozinho.

## 6. Cadência de auditoria do próprio harness

- **Semanal:** `/guide-check` rodado pelo PM ou agente, resultado em log diário.
- **Mensal:** auditoria interna do tracker + telemetria.
- **Trimestral:** auditoria externa (reviewer sub-agent auditando o próprio harness) em sessão nova.
- **Semestral:** meta-auditoria externa — contrata advisor para olhar tudo (pós-MVP, se houver tenants pagantes).
- **Anual:** revisão completa da constituição.

## 7. Telemetria obrigatória

Cada revisão mensal gera linha em `.claude/telemetry/harness-evolution.jsonl` com:

```json
{"date":"YYYY-MM","status":"verde/amarelo/vermelho","notes":"..."}
```

Status amarelo ou vermelho por 2 meses seguidos aciona revisão trimestral extraordinária.

## 8. Cross-ref

`docs/constitution.md §5` (processo formal), `docs/governance/raci.md` (quem decide o quê), `docs/audits/progress/meta-audit-tracker.md` (status geral), `docs/retrospectives/` (cadência de retrospectivas), `docs/incidents/` (pós-incidente).
