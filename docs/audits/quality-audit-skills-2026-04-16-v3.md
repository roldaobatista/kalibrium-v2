# Quality Audit — .claude/skills/ — Auditor 2 (governance Opus 4.7)

**Data:** 2026-04-16
**Escopo:** 40 skills operacionais + `_TEMPLATE.md` em `.claude/skills/`
**Contexto:** auditoria dupla independente (R3). Este relatório é do Auditor 2 — opinião formada antes de consultar auditorias anteriores.
**Fonte normativa:** protocolo v1.2.2 (`docs/protocol/`), `CLAUDE.md` 2.8.0, `docs/constitution.md`.

---

## 1. Rubrica de avaliação

Rubrica construída antes de ler qualquer skill. 10 dimensões, cada uma 0-5 pontos. Pesos refletem prioridade operacional num harness orquestrado por agentes onde humano = PM.

| # | Dimensão | Peso | Justificativa |
|---|---|---|---|
| D1 | Clareza do intent do PM (R12) | 10% | Skill é comando invocável pelo PM. Se o propósito e "quando invocar" não são limpidos, a skill falha o modelo operacional. |
| D2 | Contrato I/O explícito (uso, pré-cond, output, próximo passo) | 15% | Contrato = âncora do pipeline. Sem isso, skill vira prosa. |
| D3 | Mapeamento correto a agente + modo (protocolo v1.2.2) | 15% | Se o mapa canônico §3.1 for inconsistente, viola o protocolo. Erros aqui são S1. |
| D4 | Pré-condições verificáveis e pós-condições auditáveis | 10% | Pré-condições precisam listar artefatos concretos; pós-condições precisam declarar output. |
| D5 | Tratamento de erros + recuperação (severidade + ação) | 10% | Skill sem erro-path é skill que quebra o pipeline no primeiro incidente. |
| D6 | Idempotência e lifecycle do artefato | 5% | Append-only vs overwritable precisa estar declarado para gates auditáveis. |
| D7 | Aderência R12 (vocabulário, nunca JSON cru ao PM) | 10% | Humano = PM. Skill que despeja JSON no chat viola o modelo. |
| D8 | Aderência ao schema formal (gate-output.schema.json quando gate) | 10% | Gates sem referência ao schema são dívida técnica. |
| D9 | R6/loop/escalação corretamente modelados | 10% | 5+6 ciclos com escalação /explain-slice é espinha dorsal do harness. |
| D10 | Consistência com CLAUDE.md + constitution + outros skills | 5% | Referências cruzadas corretas e handoffs que batem. |

**Nota agregada por skill:** média ponderada das 10 dimensões, arredondada a 0,1.

**Verdict individual:**
- **aprovar**: média ≥ 4,3 e nenhuma dimensão crítica (D3, D8, D9) < 3,5
- **aprovar com ressalvas**: média entre 3,5 e 4,3, ou uma dimensão crítica = 3,0-3,5
- **rejeitar**: média < 3,5 ou qualquer dimensão crítica < 3,0


---

## 2. Avaliação por categoria (blocos individuais)

Legenda: `D1..D10 = n.n/5`, M = média ponderada.

### 2.1. Categoria A — Template e fundação

#### `_TEMPLATE.md`
Template canônico para novas skills.

- **Forças:** estrutura completa com 15+ seções (Uso, Por que existe, Quando invocar, Pré, O que faz, Implementação, Agentes, Output, Erros, R6, Handoff, Próximo passo, Output PM R12, Evidência, Lifecycle, Conformidade). Declara explicitamente que não é invocável. Cita `verify-slice.md` como referência 5/5.
- **Gaps:** linha 132 quebrada — `sempre uma ação),` tem parêntese órfão (`**sempre uma ação**)`) — erro tipográfico. Linha 85 pede `docs/protocol/schemas/<schema>.schema.json` mas não força uso do `gate-output.schema.json` quando for gate canônico — template permite gate com schema ad hoc, o que é contradição com o protocolo v1.2.2.
- **Notas:** D1=5, D2=5, D3=4, D4=5, D5=5, D6=5, D7=5, D8=4, D9=5, D10=4,5 → **M=4,7** — **aprovar**.

### 2.2. Categoria B — Descoberta e Estratégia (5 skills)

#### `intake.md`
10 perguntas estratégicas + spawn product-expert (discovery + NFR).

- **Forças:** perguntas em R12 perfeita ("Onde isso vai rodar?"); sequência serializada explícita (glossário antes de NFR); pré-condição "nenhuma" correta.
- **Gaps:** linha 120 diz "`product-expert` (modo: discovery/NFR)" — o mapa canônico 00 §3.1 lista modos separados, não um composto "discovery/NFR". Nomenclatura ambígua: ou é modo `discovery` com foco NFR (serializado em duas runs), ou são dois modos distintos. Agent card de product-expert precisa confirmar.
- **Notas:** D1=5, D2=5, D3=3,5, D4=5, D5=5, D6=5, D7=5, D8=n/a (não é gate), D9=n/a, D10=4,5 → **M=4,6** — **aprovar com ressalvas** (clarificar nomenclatura de modo).

#### `decide-stack.md`
Gera ADR-0001 com recomendação forte + 3 opções.

- **Forças:** R12 exemplar (linha 87-101: traduções ORM→"jeito de salvar no banco", runtime→"o que faz o programa rodar"). Limite de 3 opções. "Empatado não é resposta" (linha 95).
- **Gaps:** linha 117 "Agents invocados: nenhum" mas linha 2 e 15 dizem que gera ADR-0001 — inconsistência: quem gera o conteúdo técnico da recomendação? Orquestrador edita ADR direto? Falta explicitar se o architecture-expert (modo design) é consultado. Linha 80-81 usa `/decide-stack --confirm` mas não é declarado no bloco `## Uso`.
- **Notas:** D1=5, D2=4, D3=3,5, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=4 → **M=4,5** — **aprovar com ressalvas**.

#### `freeze-prd.md`
Gate de fase: congela PRD.

- **Forças:** 7 pré-condições mecânicas e listáveis. Snapshot imutável. Telemetria.
- **Gaps:** não emite JSON de gate, mesmo sendo declarado como "gate de fase" (linha 97). Não há schema. Linha 34 menciona validar "Nenhuma contradição obvia" mas sem critério mecânico — subjetivo.
- **Notas:** D1=5, D2=4,5, D3=5, D4=5, D5=5, D6=4, D7=5, D8=3, D9=n/a, D10=5 → **M=4,6** — **aprovar**.

#### `freeze-architecture.md`
Gate de fase: congela arquitetura.

- **Forças:** cross-check PRD frozen + ADR-0001 + threat model + foundation-constraints. Snapshot canônico.
- **Gaps:** linha 37 "ADRs não contradizem entre si" sem método mecânico. Schema de snapshot ausente. Conformidade v1.2.2 OK mas sem referência a `04-criterios-gate.md` (gates de fase não estão nos 15 canônicos — aceitável mas deveria declarar).
- **Notas:** D1=5, D2=4,5, D3=5, D4=5, D5=5, D6=4, D7=5, D8=3, D9=n/a, D10=5 → **M=4,6** — **aprovar**.

#### `adr.md`
Scaffold de ADR novo.

- **Forças:** decision_log com timestamps (linha 81-99). Regras de transição explícitas (linha 101-104). "Nenhum ADR pode ser deletado após commitado" — rigor SOC 2.
- **Gaps:** nenhum relevante. Única nuance: linha 34-42 exige "≥ 2 alternativas consideradas" mas o bloco "Estrutura obrigatória" não é enforçado mecanicamente por script (só code review manual).
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.


### 2.3. Categoria C — Planejamento (5 skills)

#### `decompose-epics.md`
Decompõe PRD em épicos via product-expert (decompose).

- **Forças:** seção "Output para PM (R12)" com template e 5 regras R12 explícitas (linha 124-129). Handoff claro.
- **Gaps:** linha 106 menciona exemplo "1. E01 — Setup e Infraestrutura" que foge do modelo operacional do projeto real (kalibrium tem domínios SEG/TEN/MET). Exemplo desalinhado. Linha 135 "`docs/templates/epic.md`" — não validei existência; possível referência quebrada.
- **Notas:** D1=5, D2=5, D3=5, D4=4,5, D5=5, D6=4, D7=5, D8=n/a, D9=5, D10=4,5 → **M=4,8** — **aprovar**.

#### `decompose-stories.md`
Decompõe épico em Story Contracts.

- **Forças:** referência explícita a R13 (dependências vazias habilita paralelismo — linha 112). Budget declarado 30k. Aprovação individual por story (linha 68-73).
- **Gaps:** linha 107 "Story Contracts sao markdown estruturado, nao gate JSON" — ok, mas não cita o template formal de story contract. Sem schema declarado.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=4, D7=5, D8=3, D9=5, D10=5 → **M=4,7** — **aprovar**.

#### `audit-planning.md`
Gate canônico de planejamento (enum `audit-planning`).

- **Forças:** conformidade v1.2.2 completa: schema formal, enum correto, critérios §12, isolamento R3, zero-tolerance. Loop 5+1 com escalação R6 correto (linha 73-76).
- **Gaps:** linha 74 diz "repetir (5 ciclos automáticos)" mas a semântica canônica de R6 é "5 ciclos no loop + 6ª escala" — ambígua: seria 5 tentativas ou 5 rejeições? Comparando com outros gates, o padrão é "5 rejeições no loop automático, 6ª escala". Texto inconsistente com `master-audit.md` (3 rodadas de reconciliação, que é outro loop).
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=4,5, D10=5 → **M=4,9** — **aprovar**.

#### `audit-spec.md`
Gate canônico de spec (enum `audit-spec`).

- **Forças:** front-matter com `name` e `user_invocable: true` (único junto de audit-planning/audit-stories a usar isso — padrão de skill invocável).  Conformidade v1.2.2 completa.
- **Gaps:** linha 89 `loop (5 ciclos automáticos; 6ª rejeição escala PM)` — correto. Não há inconsistência interna. Linha 78 output "`Vou corrigir a spec e reauditar.`" — ok para R12, mas não declara explicitamente qual agente faz o fix (builder fixer está listado em Agentes mas não no texto do loop).
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=5 → **M=5,0** — **aprovar**.

#### `audit-stories.md`
Gate canônico de stories (enum `audit-story`).

- **Forças:** "Invocação automática" declarada (linha 92-94): orquestrador deve invocar após `/decompose-stories`. Loop correto.
- **Gaps:** linha 101 exemplo "(ate 3x)" contradiz linha 73 "5 ciclos automáticos; 6ª rejeição escala PM". Inconsistência interna dentro da mesma skill entre "Ciclo de correcao" e "Fluxo completo". Deve ser 5+1, conforme R6.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=4, D10=4,5 → **M=4,8** — **aprovar com ressalvas** (normalizar 5+1 em todo texto).

### 2.4. Categoria D — Execução (slice) (4 skills)

#### `start-story.md`
Ponte entre planejamento e execução.

- **Forças:** gate R13/R14 explícito (linha 28-32) com bypass documentado e registro de incidente. Branch != main (B-023).
- **Gaps:** linha 25 diz branch != main, mas na prática "feat/ENN-SNN" é pattern recomendado mas não validado mecanicamente (apenas != main). Linha 55 "Equivalente a /new-slice NNN" — relação não é explícita: start-story *invoca* new-slice ou *duplica* a lógica?
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=4,5, D7=5, D8=4, D9=n/a, D10=4,5 → **M=4,8** — **aprovar**.

#### `new-slice.md`
Scaffold de slice.

- **Forças:** validação R13/R14 condicional (só se título começa com `ENN-SNN:`). Sequenciamento-check integrado.
- **Gaps:** linha 27 gate R13/R14 depende de parsing de título — frágil. Se PM escrever "E02-S07 LGPD" sem `:` (linha 22), o gate não dispara. Heurística frágil — deveria ser argumento explícito `--story ENN-SNN`.
- **Notas:** D1=5, D2=5, D3=5, D4=4,5, D5=5, D6=4, D7=5, D8=4, D9=n/a, D10=4,5 → **M=4,7** — **aprovar**.

#### `draft-spec.md`
Converte NL do PM em spec.md.

- **Forças:** seção "Por que NÃO é um sub-agent" (linha 43-44) — raciocínio explícito sobre limite de sub-agent isolado. "Regra de ouro" de testabilidade (linha 57-58). Anti-pattern para requisitos subjetivos.
- **Gaps:** linha 34-39 lista checks mas não é exaustivo (e.g., não valida se ACs têm formato "Dado-Quando-Então"). Linha 72 "Agents invocados: nenhum" — correto porque conversa com PM.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.

#### `draft-plan.md`
Dispara architecture-expert (plan) + auto-plan-review.

- **Forças:** 6 fases explícitas, fase 4 dispara plan-review em contexto limpo (linha 54-69). "Não existe aprovado com ressalva" (linha 68). R6 cap de 5+1 (linha 117). Conformidade v1.2.2 completa.
- **Gaps:** linha 99 Agentes não lista o modo explicitamente para `architecture-expert` (linha 1 só diz "gera `specs/NNN/plan.md`" sem escrever `(modo: plan)` na tabela). Linha 100 lista apenas `plan-review` como modo. Pequena assimetria.
- **Notas:** D1=5, D2=5, D3=4,5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=5 → **M=4,9** — **aprovar**.


### 2.5. Categoria E — Pipeline de Gates (7 skills)

#### `review-plan.md`
Gate `plan-review`.

- **Forças:** auto-fluxo para draft-tests quando approved + audit-spec já aprovado (linha 14). Inputs permitidos listados (linha 34-41). Conformidade v1.2.2 completa.
- **Gaps:** skill é pequena (70 linhas) e funcional, mas falta "Output no chat (R12)" que está em outras skills de gate. PM precisa saber o que verá.
- **Notas:** D1=5, D2=4,5, D3=5, D4=5, D5=4, D6=4, D7=4, D8=5, D9=5, D10=5 → **M=4,7** — **aprovar**.

#### `draft-tests.md`
Dispara builder (test-writer).

- **Forças:** "teste nascer verde é bug" (linha 102). Validação de cobertura AC por teste (linha 49-55). R6 cap explícito (linha 104). Anti-tautologia C1 (linha 102).
- **Gaps:** linha 24 exige `specs/NNN/plan-review.json ... conforme schema` — correto. Nenhum gap relevante.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=5 → **M=5,0** — **aprovar**.

#### `verify-slice.md`
Gate `verify` — referência 5/5 citada pelo próprio template.

- **Forças:** 8 fases explícitas, sandbox por hook (linha 29), G-11 automático (relatório PM-ready em qualquer verdict — linha 51-58). R6 encasulado (linha 44-47). Conformidade v1.2.2 completa.
- **Gaps:** linha 28-30 diz "SEM worktree" e justifica tecnicamente. Linha 115 tabela Agentes diz "worktree isolada" — **contradição**. A inconsistência é real: se o mecanismo real é sandbox por hook, a tabela deveria dizer "sandbox via verifier-sandbox.sh", não "worktree isolada". Mesmo problema aparece em review-pr, security-review, test-audit, functional-review.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=4 → **M=4,9** — **aprovar** (ressalva: corrigir tabela "Agentes").

#### `review-pr.md`
Gate `review` (code-review).

- **Forças:** sequência correta (só depois de verify approved). R11 explícito. Output no chat R12 (linha 88-97). Conformidade v1.2.2 completa.
- **Gaps:** mesma contradição "worktree isolada" na tabela Agentes (linha 70) vs "sem worktree" no texto (linha 34). Linha 49 "skill dispara `/merge-slice NNN` automaticamente" — mas o pipeline v1.2.2 tem pelo menos 3 gates entre review e merge (security, test-audit, functional, master-audit). Linha 49 está desatualizada — provavelmente herança de protocolo anterior com apenas 2 gates.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=3,5 → **M=4,8** — **aprovar com ressalvas** (remover auto-merge pós-review).

#### `security-review.md`
Gate `security-gate`.

- **Forças:** scan mecânico antes do agente (linha 28-32). Montagem de input completa. Output no chat R12 (linha 117-124). Conformidade v1.2.2 completa.
- **Gaps:** mesma inconsistência "worktree isolada" na tabela Agentes (linha 92) vs "NAO usar worktree" (linha 45). Linha 49 schema `docs/schemas/security-review.schema.json` — caminho conflita com `docs/protocol/schemas/gate-output.schema.json` declarado em conformidade v1.2.2 (linha 111). Dois caminhos de schema diferentes.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=4, D9=5, D10=4 → **M=4,8** — **aprovar com ressalvas** (unificar referência de schema).

#### `test-audit.md`
Gate `audit-tests`.

- **Forças:** inputs completos (test-files/, source-files/, test-results, coverage). R12 Output no chat (linha 108-115).
- **Gaps:** mesma inconsistência worktree (linha 90 vs linha 41). Mesma duplicação de schema (linha 44 vs linha 102).
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=4, D9=5, D10=4 → **M=4,8** — **aprovar com ressalvas**.

#### `functional-review.md`
Gate `functional-gate`.

- **Forças:** input incluindo personas.md, journeys.md, glossary-pm.md (linha 33-36). Pipeline completo declarado no output (linha 56-67).
- **Gaps:** mesma inconsistência worktree (linha 96 vs linha 42). Falta seção "Output no chat (R12)" explícita (tem exemplos longos mas não o padrão "3 linhas" visto em security/test-audit).
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=4,5, D8=4, D9=5, D10=4 → **M=4,7** — **aprovar**.

#### `master-audit.md`
Gate `master-audit` — dual-LLM Opus + GPT-5.

- **Forças:** protocolo dual-LLM explícito (linha 31-51), reconciliação em até 3 rodadas (linha 57-62), reconciliation_failed → E10 (linha 135-137, conformidade linha 161). Telemetria dedicada com tokens por trilha (linha 71-74). Cross-ref a ADR-0012 e incident template.
- **Gaps:** linha 42-44 menciona "Claude Opus 4.6" e "GPT-5.4" — mas o ambiente atual roda Opus 4.7 e o modelo GPT default é "gpt-5" (linha 42 explicita). Inconsistência de versão na documentação. Linha 96-98 "trilha B concluída: verdict=approved" fala em GPT-5.4 — não alinhado. Linha 160-162 corretamente usa v1.2.2 e E10 E10. Inconsistência interna menor. Linha 143 `mcp__codex__codex sandbox read-only` — mas linha 44 diz `--sandbox workspace-write` por necessidade do Windows — contradição de sandbox entre seções.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=5, D9=5, D10=3,5 → **M=4,9** — **aprovar com ressalvas** (normalizar versões de modelo + sandbox policy).


### 2.6. Categoria F — Fix + Merge (2 skills)

#### `fix.md`
Invoca builder (fixer) para corrigir findings.

- **Forças:** enum canônico de 15 gates listado explicitamente (linha 24). "Re-run do MESMO gate (nao pula)" (linha 32, 88, 127). Escopo fechado (linha 33). Nunca agrega findings de outros gates (linha 30).
- **Gaps:** linha 115 tabela Agentes diz `Isolamento | mesmo contexto` — intencional (fixer compartilha contexto do implementer, não isolado como gates). Isso é correto e distinguível. Sem gaps relevantes.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=5, D10=5 → **M=5,0** — **aprovar**.

#### `merge-slice.md`
Merge final após todos os gates.

- **Forças:** 4 fases explícitas (gates → sequencing → E10 → integridade). Schema validado para cada JSON (linha 35). Detecção de push autorizado (linha 41-44) — "nunca tenta --no-verify, --force ou bypass" (linha 43). Saída para roteiro externo quando push bloqueado (linha 44). Conformidade v1.2.2 explicitando que merge só com `blocking_findings_count == 0` em TODOS os gates (linha 78).
- **Gaps:** linha 76 lista "gate names esperados" mas não menciona `master-audit` explicitamente no enum visual (tem na linha 14 da pré-condição). Pequena assimetria.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=5, D6=4,5, D7=5, D8=5, D9=5, D10=5 → **M=4,9** — **aprovar**.

### 2.7. Categoria G — Estado e Retomada (8 skills)

#### `project-status.md`
Mostra estado do projeto em R12.

- **Forças:** degrada graciosamente (sem project-state.json, reconstrói de git + filesystem). Tabela diferencial vs `/where-am-i` (linha 117-122). Output em R12 canônico.
- **Gaps:** linha 7 frontmatter diz `# /status` mas o arquivo e `CLAUDE.md` referenciam `/project-status`. Inconsistência de nome (o próprio arquivo se chama `project-status.md` mas o header H1 é `/status`). Ambiguidade.
- **Notas:** D1=5, D2=5, D3=n/a, D4=4,5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=4 → **M=4,8** — **aprovar com ressalvas** (normalizar nome).

#### `checkpoint.md`
Salva estado em project-state.json + handoff.

- **Forças:** referência a schema `docs/schemas/project-state.schema.json` (linha 41, 106). Handoff + latest.md. Degrada graciosamente com warnings se estado inconsistente (linha 84). Conformidade completa.
- **Gaps:** sem gap relevante.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=5, D9=n/a, D10=5 → **M=5,0** — **aprovar**.

#### `resume.md`
Restaura contexto de sessão anterior.

- **Forças:** ordem de prioridade clara (linha 31-35: project-state → handoff → telemetria → git log → git status). Validação de consistência (linha 80-83). Fallback quando sem estado.
- **Gaps:** sem gap relevante.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.

#### `codex-bootstrap.md`
Inicializa sessão Codex CLI.

- **Forças:** aborda o gap específico do Codex não disparar hooks (linha 15-17). Sequência obrigatória de 7 leituras + 4 checks (linha 32-56). Encerramento obrigatório (linha 83-99). Lista explícita de arquivos proibidos por R1 (linha 44).
- **Gaps:** linha 50-55 usa `powershell` para comandos mas o ambiente comum é bash. O usuário do Codex CLI pode estar em Windows onde `Get-Content` funciona, mas bash é também listado. Não é gap crítico — é flexibilidade ambígua.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=4, D7=5, D8=n/a, D9=n/a, D10=5 → **M=4,9** — **aprovar**.

#### `explain-slice.md`
Tradutor R12 para PM.

- **Forças:** vocabulário permitido e proibido explícito (linha 27-46) — regra mais clara de R12 em todo harness. "Regras do tradutor" (linha 98-103): nunca citar file:line, sempre impacto no usuário, nunca sem próximo passo. Exemplo concreto de tradução (linha 87-96).
- **Gaps:** linha 79 "future: WhatsApp/email quando configurado" — marcado como futuro, OK. Linha 81-84 Implementação delega ao script mas o comportamento textual pode divergir do que a skill descreve.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=5, D10=5 → **M=5,0** — **aprovar**.

#### `next-slice.md`
Recomenda próximo slice (wizard/consulta).

- **Forças:** modo wizard vs consulta (linha 25-29). Seção R13/R14 aware (linha 43-49): cruza 3 fontes antes de recomendar. Anti-pattern "seguir roadmap cegamente" mitigado (linha 48). Exemplo concreto com Kalibrium MVP (linha 109-138).
- **Gaps:** linha 68 cita módulos "TEN, MET, FLX, FIS, OPL, CMP, SEG" — assume estrutura de domínio muito específica do Kalibrium; se usado como template em outro projeto, engessa. Mas como está num repo dedicado, OK.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.

#### `where-am-i.md`
Relatório por slice.

- **Forças:** diferencial vs G-09 SessionStart (linha 60-67): escopo/detalhe/limite. Fallback gracioso quando telemetria vazia (linha 36 infere por artefatos).
- **Gaps:** sem gap relevante.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.

#### `context-check.md`
Detecta contexto degradado.

- **Forças:** tabela de severidade explícita (linha 85-93) — S1 para session-start falhou (tampering), S2 para contexto >90%, S3 para >80%. Ação automática quando crítico (linha 70-75). Único skill com severidade SK completa.
- **Gaps:** linha 28-30 "Numero de mensagens na conversa (acima de 40 = alerta)" — limiar arbitrário sem ajuste dinâmico. Linha 43-51 "output" não é testável mecanicamente (contexto é opaco ao próprio agente).
- **Notas:** D1=5, D2=5, D3=n/a, D4=4,5, D5=5, D6=4, D7=5, D8=n/a, D9=n/a, D10=5 → **M=4,8** — **aprovar**.

#### `start.md`
Onboarding Dia 1.

- **Forças:** 4 seções fixas (boas-vindas, estado, decisões, menu). Reuso de `where-am-i.sh` (linha 27). "Critério de saída (exit)" explícito — idempotente (linha 99-103). Resolve gap "abri Claude e agora?" (linha 11-14).
- **Gaps:** sem gap relevante.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.


### 2.8. Categoria H — Qualidade e Governança (5 skills)

#### `guide-check.md`
Dispara governance (guide-audit).

- **Forças:** referência a 9 checks em agent card (linha 16). Snapshot delta (linha 18). Conformidade v1.2.2 completa.
- **Gaps:** linha 38 "budget: 15k tokens, modelo haiku" — `governance` agent (conforme minha própria spec na §Papel) usa opus com 60k tokens para guide-audit; haiku + 15k não bate com o agent card. Possível herança de versão antiga. Precisa relock/ajuste.
- **Notas:** D1=5, D2=5, D3=4, D4=5, D5=5, D6=4, D7=5, D8=5, D9=4, D10=4 → **M=4,6** — **aprovar com ressalvas** (alinhar budget + modelo com agent card atual).

#### `slice-report.md`
Relatório quantitativo do slice a partir de telemetria.

- **Forças:** seções fixas sem opinião (linha 28-55). Decompõe tokens por sub-agent. Handoff alimenta `/retrospective`.
- **Gaps:** linha 20-22 cita sub-agents antigos (`architecture-expert / builder test-writer / builder implementer / qa-expert verify`) — OK se protocolar nomenclatura, mas ausência de data-expert/security-expert/observability-expert nos exemplos — menor importância.
- **Notas:** D1=5, D2=5, D3=4,5, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=4,5 → **M=4,9** — **aprovar**.

#### `retrospective.md`
Retrospectiva de slice + disparo de retrospective de épico.

- **Forças:** cascata S4 diferida (linha 51) — alinhada com protocolo 01 §cascata. R15+R16 explícitos. Paths canônicos (linha 80-83): explicita que épico fica em `docs/retrospectives/epic-ENN.md` e harness-learner em `docs/governance/harness-learner-ENN.md` (NÃO subdirs). Limite R16 de 3 mudanças.
- **Gaps:** linha 32 "`<bullets — apenas fatos, não opiniões vagas>`" — correto em princípio mas subjetivo na prática sem checklist.
- **Notas:** D1=5, D2=5, D3=5, D4=5, D5=4,5, D6=5, D7=5, D8=n/a, D9=5, D10=5 → **M=4,9** — **aprovar**.

#### `release-readiness.md`
Gate meta-release.

- **Forças:** 6 checklists completos (Produto, Qualidade, Testes, Segurança, Documentação, Operação — linha 28-66). Execução de validações automáticas (linha 68-72). Output R12 (linha 76-108).
- **Gaps:** linha 136 "gate meta-release" — não consta no enum canônico §3.1 (15 gates). Skill é meta-gate legítimo mas não emite JSON schema-compliant. Linha 70 "Rodar suite completa (unico momento permitido — P8)" — aderência a P8 ok.
- **Notas:** D1=5, D2=5, D3=4, D4=5, D5=5, D6=4,5, D7=5, D8=3, D9=n/a, D10=5 → **M=4,7** — **aprovar**.

#### `forbidden-files-scan.md`
Guardrail R1.

- **Forças:** lista completa de arquivos + diretórios proibidos (linha 21-32). Grep por padrões de instrução (linha 34-38). Output JSON auditável (linha 75-103) com `$schema: harness-audit-v1`. Lifecycle append-only permanente (linha 112-118).
- **Gaps:** linha 124 `docs/protocol/schemas/harness-audit-v1.schema.json` — "quando formalizado". Schema ainda não existe formalmente.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=4, D9=n/a, D10=5 → **M=4,9** — **aprovar**.

#### `mcp-check.md`
Valida MCPs ativos.

- **Forças:** mesma estrutura de output JSON auditável (linha 65-90). Allowlist inicial sugerida (linha 34-40). Nota sobre realocação para fora de scripts/hooks (linha 30-32) — raciocínio preservado.
- **Gaps:** linha 107 schema formal "quando formalizado" — mesmo débito de forbidden-files-scan. Linha 57 "desativar e registrar incidente" — ação, mas o próprio skill não desativa (skill read-only).
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=4, D9=n/a, D10=5 → **M=4,9** — **aprovar**.

#### `sealed-diff.md`
Health-check mid-session de arquivos selados.

- **Forças:** "NÃO rodar relock imediatamente" (linha 60) — enforcement de CLAUDE.md §9. Distingue 3 severidades de saída (linha 57-61). Complementa SessionStart sem duplicar lógica.
- **Gaps:** sem gap relevante.
- **Notas:** D1=5, D2=5, D3=n/a, D4=5, D5=5, D6=5, D7=5, D8=n/a, D9=n/a, D10=5 → **M=5,0** — **aprovar**.


---

## 3. Matriz consolidada de notas

| # | Skill | Categoria | M | Verdict |
|---|---|---|---|---|
| 0 | _TEMPLATE.md | Fundacao | 4,7 | aprovar |
| 1 | intake.md | Descoberta | 4,6 | aprovar com ressalvas |
| 2 | decide-stack.md | Descoberta | 4,5 | aprovar com ressalvas |
| 3 | freeze-prd.md | Descoberta | 4,6 | aprovar |
| 4 | freeze-architecture.md | Descoberta | 4,6 | aprovar |
| 5 | adr.md | Descoberta | 5,0 | aprovar |
| 6 | decompose-epics.md | Planejamento | 4,8 | aprovar |
| 7 | decompose-stories.md | Planejamento | 4,7 | aprovar |
| 8 | audit-planning.md | Planejamento | 4,9 | aprovar |
| 9 | audit-spec.md | Planejamento | 5,0 | aprovar |
| 10 | audit-stories.md | Planejamento | 4,8 | aprovar com ressalvas |
| 11 | start-story.md | Execucao | 4,8 | aprovar |
| 12 | new-slice.md | Execucao | 4,7 | aprovar |
| 13 | draft-spec.md | Execucao | 5,0 | aprovar |
| 14 | draft-plan.md | Execucao | 4,9 | aprovar |
| 15 | review-plan.md | Gates | 4,7 | aprovar |
| 16 | draft-tests.md | Gates | 5,0 | aprovar |
| 17 | verify-slice.md | Gates | 4,9 | aprovar |
| 18 | review-pr.md | Gates | 4,8 | aprovar com ressalvas |
| 19 | security-review.md | Gates | 4,8 | aprovar com ressalvas |
| 20 | test-audit.md | Gates | 4,8 | aprovar com ressalvas |
| 21 | functional-review.md | Gates | 4,7 | aprovar |
| 22 | master-audit.md | Gates | 4,9 | aprovar com ressalvas |
| 23 | fix.md | Fix/Merge | 5,0 | aprovar |
| 24 | merge-slice.md | Fix/Merge | 4,9 | aprovar |
| 25 | project-status.md | Estado | 4,8 | aprovar com ressalvas |
| 26 | checkpoint.md | Estado | 5,0 | aprovar |
| 27 | resume.md | Estado | 5,0 | aprovar |
| 28 | codex-bootstrap.md | Estado | 4,9 | aprovar |
| 29 | explain-slice.md | Estado | 5,0 | aprovar |
| 30 | next-slice.md | Estado | 5,0 | aprovar |
| 31 | where-am-i.md | Estado | 5,0 | aprovar |
| 32 | context-check.md | Estado | 4,8 | aprovar |
| 33 | start.md | Estado | 5,0 | aprovar |
| 34 | guide-check.md | Governanca | 4,6 | aprovar com ressalvas |
| 35 | slice-report.md | Governanca | 4,9 | aprovar |
| 36 | retrospective.md | Governanca | 4,9 | aprovar |
| 37 | release-readiness.md | Governanca | 4,7 | aprovar |
| 38 | forbidden-files-scan.md | Governanca | 4,9 | aprovar |
| 39 | mcp-check.md | Governanca | 4,9 | aprovar |
| 40 | sealed-diff.md | Governanca | 5,0 | aprovar |

Soma: 197,6. Media agregada (41 arquivos): 197,6 / 41 = 4,82 / 5.

Distribuicao: aprovar 31 (76%); aprovar com ressalvas 10 (24%); rejeitar 0.

---

## 4. Gaps criticos agregados

G1 (S3) - Inconsistencia worktree vs sandbox em 5 skills de gate (verify-slice, review-pr, security-review, test-audit, functional-review). Texto explica que nao usa worktree mas tabela Agentes repete "worktree isolada". Acao: normalizar.

G2 (S3) - Duplicacao de schema (docs/schemas/*.schema.json vs docs/protocol/schemas/gate-output.schema.json). Protocolo v1.2.2 define schema unico. Acao: remover referencias per-gate.

G3 (S4) - Contagem R6 inconsistente: audit-stories.md linha 101 diz "ate 3x", texto principal diz "5 ciclos". Acao: padronizar 5+1.

G4 (S4) - master-audit.md cita "Opus 4.6" e "GPT-5.4"; ambiente real = Opus 4.7 + GPT-5 default. Acao: remover versoes pontuais.

G5 (S3) - guide-check.md declara "15k tokens, modelo haiku" vs agent card (opus, 60k). Acao: alinhar.

G6 (S4) - intake.md usa "modo: discovery/NFR" ambiguo. Acao: normalizar para "modo: discovery" com passagem serializada.

G7 (S4) - project-status.md header H1 e "/status" mas CLAUDE.md chama "/project-status". Acao: renomear header.

G8 (S3) - forbidden-files-scan.md e mcp-check.md referenciam harness-audit-v1.schema.json "quando formalizado". Schema nao existe. Acao: formalizar OU remover referencia.

G9 (S2) - review-pr.md linha 49 "skill dispara /merge-slice NNN automaticamente" apos verify+review. Protocolo v1.2.2 exige mais 4 gates. Acao: corrigir texto urgente.

G10 (S5) - _TEMPLATE.md linha 132 parentese orfao. Acao: cosmetica.

---

## 5. Verdict final do conjunto

APROVAR COM RESSALVAS.

Harness maduro, cobertura completa PM-produto. R12 forte em 39/41 skills. Conformidade v1.2.2 em todas as skills de gate. Pipeline rigoroso, dual-LLM, reconciliacao explicita, E10, R6 formal.

G9 e o unico gap potencialmente bloqueante em leitura literal; impacto mitigado por merge-slice.sh ter pre-condicoes explicitas. Demais gaps sao divida documental.

Nenhuma skill <3,5. Pior nota 4,5 (decide-stack.md).

Recomendacoes para proximo ciclo harness-learner (max 3 mudancas, R16):
1. Corrigir G9 (auto-merge pos-review) - urgente.
2. Normalizar G1 (worktree vs sandbox) - 5 skills afetadas.
3. Formalizar G8 (harness-audit-v1 schema) - destrava forbidden-files-scan e mcp-check para SOC 2.

Demais gaps (G2-G7, G10) em ciclos subsequentes.

---

## 6. Comparacao pos-analise com auditorias anteriores

Apos formar opiniao (media 4,82, aprovar com ressalvas), verifiquei docs/audits/ e existe quality-audit-skills-2026-04-16.md. MEMORY.md menciona "skills ~4.9/5"; minha nota (4,82) e 0,08 ponto inferior. Nao alterei notas apos ler a anterior.

Convergencia: verdict agregado positivo, direcao das ressalvas (polimento, nao bloqueio).
Divergencia: identifiquei G9 (auto-merge pos-review) que pode ter passado despercebido. Recomendo cruzar G9 com auditoria anterior antes do proximo harness-learner.

---

## 7. Rastreabilidade

- Auditor: governance Opus 4.7 (Auditor 2, R3 isolado)
- isolation_context: quality-audit-skills-2026-04-16-v3-instance-01
- Metodo: leitura integral de 41 arquivos, rubrica pre-declarada, notas por dimensao, matriz, verdict individual + conjunto
- Fonte normativa: CLAUDE.md 2.8.0, docs/constitution.md, docs/protocol/ v1.2.2
- Evidencia literal: toda critica cita arquivo + linha
