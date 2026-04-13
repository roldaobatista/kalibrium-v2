# Auditoria de operabilidade PM — 2026-04-12

**Objetivo:** enumerar cada momento concreto do dia-a-dia do Product Manager **não-técnico** operando o harness Kalibrium V2 e marcar cada gap (fricção, cegueira, perda de estado, sem-recuperação). Não é revisão técnica; é simulação da experiência operacional.

**Método:** percorrer mentalmente o ciclo de vida de 1-2 semanas de PM (do Dia 1 ao merge do slice 3), um momento por vez. Para cada momento: o que o PM vê, o que ele precisa digitar, como o agente responde hoje, onde tem fricção técnica, e se existe gap [NOVO] ou já está no backlog B-NNN.

**Anti-bias:** esta auditoria **não leu** a conversa da sessão anterior. Os 21 itens do `docs/guide-backlog.md` (B-001..B-021) foram lidos **apenas** para cruzar correlação, não tratados como verdade absoluta. Divergências estão marcadas explicitamente.

**Estado real verificado (2026-04-12):** 13 skills, 6 sub-agents, 18 hooks, 21 scripts, push autorizado em settings.json, `specs/` vazio, `docs/retrospectives/` vazio, ADR-0001 + 0002 aceitos, cadeia verifier→reviewer→merge-slice nunca exercida end-to-end.

---

## Categorização usada

**Tipo de gap:**
- **fricção** — PM precisa executar uma ação técnica (digitar comando, abrir terminal externo, ver vocabulário proibido por R12)
- **cegueira** — o harness opera em silêncio ou com output que o PM não consegue ler
- **perda-de-estado** — sessão encerrou, dia passou, ou semana passou, e o PM não tem como saber onde parou
- **sem-recuperação** — um caminho de falha existe mas não há skill/automação que leve o PM de volta ao caminho feliz

**Severidade:**
- **Crítica** — PM trava completamente; precisa pedir ajuda externa
- **Alta** — PM avança mas com frustração repetitiva
- **Média** — incômodo recorrente mas tolerável
- **Baixa** — polimento

---

## Momentos auditados

### M-01. Dia 1, manhã — PM abre Claude Code pela primeira vez no projeto

**O que o PM vê hoje:**
```
[session-start] verificando harness...
[session-start] constitution + ADRs carregados
[session-start] nenhum arquivo proibido (R1)
[session-start] selos OK
```
E então o prompt volta em branco.

**O que ele precisa saber:** "beleza, e agora?" — primeiro passo concreto.

**O que ele precisa digitar:** ele não sabe.

**Fricção:** nenhuma skill `/start`, `/dia1` ou `/onboarding` diz ao PM o que fazer. CLAUDE.md §6 descreve o fluxo ("PM descreve o slice em linguagem natural") mas o PM não lê CLAUDE.md — ele é PM não-técnico, não desenvolvedor.

**Gap — G-01 [NOVO] — Skill de onboarding Dia 1 ausente.** Tipo: cegueira. Severidade: **Crítica**. Correlação backlog: nenhuma.

---

### M-02. Dia 1, tarde — PM quer criar o primeiro slice

**O que o PM faria:** "quero criar um slice". O agente responde sugerindo `/new-slice NNN "título"`.

**Problemas:**

1. **PM não sabe qual NNN usar.** Deve ser `000` (smoke-test da cadeia, B-013) ou `001` (primeiro real)? Ele precisaria consultar `docs/slice-registry.md` e inferir. Vocabulário "NNN" e "slice" são técnicos mas aceitáveis por R12.
2. **PM não sabe qual ordem de slices faz sentido.** O PRD canônico tem múltiplas funcionalidades do MVP, mas não existe skill que leia o PRD e recomende ordem. **Este é o problema original que motivou esta sessão inteira.**
3. **Depois que o skeleton é criado, PM não sabe escrever `spec.md`.** A skill copia template e manda PM editar. Template tem "AC em formato dado-quando-então" — PM não entende. Existe `scripts/draft-spec.sh --check` (validador mecânico) mas não existe skill `/draft-spec` conversacional que extraia ACs de uma descrição natural do PM.

**Gap — G-02 [NOVO] — `/new-slice` não sugere o número automaticamente.** Tipo: fricção. Severidade: **Baixa** (mas elimina uma pergunta por slice).

**Gap — G-03 [NOVO] — Falta sub-agent `roadmap-planner` (ou skill `/next-slice`) que lê o PRD e sugere qual slice fazer agora.** Tipo: cegueira + fricção. Severidade: **Crítica**. Correlação: B-022 (proposto mas não registrado no backlog). **Este é o gap central da sessão.**

**Gap — G-04 [NOVO] — Falta skill conversacional `/draft-spec NNN` que converte descrição NL do PM em ACs numerados estruturados.** Tipo: fricção. Severidade: **Alta**. Correlação parcial: `scripts/draft-spec.sh --check` só valida (fecha hole P0-3 do meta-audit #2) mas não redige. Correlação parcial com B-017 (slice-kits) — kits são templates de padrão (CRUD, auth), não captura interativa da descrição inicial.

---

### M-03. Dia 1, tarde — PM "aprovou o spec", e agora?

**Fluxo esperado (CLAUDE.md §6 passo 4):** "Sub-agent architect gera plan.md". Mas nenhuma skill dispara o architect explicitamente. O agente principal tem que invocar `Agent(subagent_type: "architect")` manualmente — e o PM só sabe isso se o agente principal orquestrar.

**Gap — G-05 [NOVO] — Não existe skill `/draft-plan NNN` que dispare o architect explicitamente.** O agente principal precisa lembrar de chamar na hora certa. Se o PM encerra sessão depois do spec e volta no dia seguinte, o contexto da orquestração some. Tipo: perda-de-estado + fricção. Severidade: **Alta**.

**Gap — G-06 [NOVO] — plan.md gerado é técnico, PM não pode aprovar o que não entende.** Tipo: cegueira. Severidade: **Crítica**. **Correlação: B-016** (tradução contínua técnico→produto em TODOS os sub-agents, produzindo `plan-pm.md` automático). B-016 é exatamente o remédio; esta auditoria **confirma** que B-016 deve ser tratado como **Crítico**, não como "alta prioridade" genérica.

---

### M-04. Dia 1, fim do dia — PM "aprovou o plan", agora os testes

**Fluxo esperado:** sub-agent `ac-to-test` gera testes red em `tests/.../ac-NNN-*`. PM revisa. Commit "test(slice-NNN): AC tests red".

**Problemas:**
1. Nenhuma skill `/draft-tests NNN` dispara o `ac-to-test`. Mesmo problema do G-05.
2. PM não entende código de teste. Ver o arquivo `.test.ts` é vocabulário proibido por R12.
3. PM não consegue verificar que os testes "nasceram vermelhos" — precisa rodar lint/runner e ler output. Vocabulário proibido.

**Gap — G-07 [NOVO] — Não existe skill `/draft-tests NNN` que dispare o ac-to-test.** Tipo: fricção + perda-de-estado. Severidade: **Alta**. Mesmo padrão do G-05 — agora está claro que **todos os handoffs de sub-agent precisam de skill dedicada** pra sobreviver ao fim de sessão.

**Gap — G-08 [NOVO] — Não existe relatório PM-ready "testes vermelhos escritos, aqui está o que cada um verifica (em linguagem de produto)".** Tipo: cegueira. Severidade: **Alta**. **Correlação: B-016** (outputs-pm em todos os handoffs, `tests-pm.md`). Confirma criticidade de B-016.

---

### M-05. Dia 2, manhã — PM abre Claude Code de novo no meio do slice 001

**O que o PM vê hoje:**
```
[session-start] verificando harness...
[session-start] constitution + ADRs carregados
[session-start] selos OK
```
Mesma saída do Dia 1. **Nenhuma informação sobre o slice em andamento.**

**O que o PM espera:** "bom dia, ontem você parou no slice 001 depois dos testes red. Próximo passo: implementar. Posso começar?"

**O que o agente faz hoje:** nada. O `session-start.sh` não lê `.claude/telemetry/slice-*.jsonl` pra montar um resumo. O agente principal também não tem `/where-am-i` — o PM teria que digitar "onde paramos?" e torcer pra conversa anterior ter ficado salva no `.claude/projects/<hash>` do CLI.

**Gap — G-09 [NOVO] — `session-start.sh` não lê telemetria do último slice ativo e não imprime "Slice NNN em andamento. Último evento: X. Próximo passo: Y."** Tipo: perda-de-estado + cegueira. Severidade: **Crítica**. Correlação: nenhuma no backlog atual. **Este é o gap que quebra qualquer sessão que dure mais de 1 dia.**

**Gap — G-10 [NOVO] — Não existe skill `/where-am-i` ou `/status` que o PM possa digitar a qualquer momento pra saber o estado do slice ativo.** Tipo: perda-de-estado. Severidade: **Alta**. Relacionado a G-09 mas rodável on-demand.

---

### M-06. Dia 2, tarde — implementer rodou, PM "aprovou", e agora?

**Fluxo esperado:** `/verify-slice NNN` monta `verification-input/`, spawna verifier em worktree, emite `verification.json`.

**Problemas:**
1. **Output imediato do verifier é JSON cru**. `verification.json` tem `verdict, ac_checks, violations, next_action` — tudo vocabulário proibido por R12.
2. O §3.1 regra 4 diz "escalações R6 obrigatoriamente invocam `/explain-slice NNN`". Mas:
   - (a) Na **1ª** rejeição, o PM já precisa entender o que falhou — não existe auto-tradução na 1ª.
   - (b) O PM tem que se lembrar de invocar `/explain-slice`. Não é automático.
3. Mesmo se `verdict: approved`, o PM não sabe se o próximo passo é `/review-pr` manualmente ou se o harness dispara sozinho. Lendo `.claude/skills/review-pr.md`, o disparo é manual.

**Gap — G-11 [NOVO] — `/verify-slice` não invoca `/explain-slice` automaticamente no handoff (nem em approved nem em rejected).** Tipo: cegueira + fricção. Severidade: **Crítica**. Correlação: **B-016** (tradução contínua) + **B-010** (tradutor automático técnico→produto, hoje `explain-slice.sh` só cria template com placeholders). B-010 é pré-requisito estrutural, B-016 é aplicação em cada handoff.

**Gap — G-12 [NOVO] — Cadeia `/verify-slice` → `/review-pr` → `/merge-slice` não é automática mesmo quando ambos aprovam.** Cada uma exige comando explícito. Tipo: fricção. Severidade: **Alta**. Correlação parcial: `review-slice.sh` **dispara** `/merge-slice NNN` automaticamente após aprovação dupla (conforme skill `/review-pr` §8). Verificar se existe o mesmo gatilho de `/verify-slice` pra `/review-pr`. **Divergência a verificar na Fase A.**

---

### M-07. Dia 3 — 1ª rejeição do verifier, PM vê `verification.json`

**O que o PM vê hoje:** mesmo que o agente principal traduza o JSON, a tradução é **ad-hoc, dependente da memória do agente**, não usa vocabulário permitido de R12 enforced mechanically. Variação de qualidade entre execuções.

**Gap — G-13 [NOVO] — Tradução do verifier não é enforced mecanicamente (hoje é cultural + ad-hoc).** Tipo: cegueira. Severidade: **Alta**. Correlação: **B-010** (tradutor automático estruturado) + **B-016** (em todos os handoffs). Confirma que B-010 e B-016 são **blocker duplo** — sem os dois, cada slice que falha é traumático pro PM.

---

### M-08. Dia 3, meio-dia — PM quer pausar o slice por algumas horas e pedir mudança de escopo

**Cenário:** PM percebe que o spec está errado — precisa adicionar 1 AC ou mudar 1 critério. Architect já rodou, testes já existem, implementer está no meio.

**Problemas:**
1. Não existe skill `/rescope NNN` ou `/edit-spec NNN`.
2. PM editando manualmente `specs/NNN/spec.md` é vocabulário proibido (caminho, arquivo, editar).
3. Se o spec mudar, os testes red existentes podem virar irrelevantes — quem decide? Não há fluxo formal "mudou o spec → reavalia testes".
4. Risco: PM simplesmente ignora a mudança e entrega slice faltando AC. Pior ainda, adiciona AC sem teste e o DoD §3 bloqueia merge.

**Gap — G-14 [NOVO] — Sem fluxo de mudança de escopo em slice ativo.** Tipo: sem-recuperação. Severidade: **Alta**. Correlação: nenhuma.

---

### M-09. Dia 3, tarde — PM decide abortar o slice

**O que ele precisa fazer hoje:**
- `git checkout main`
- `git branch -D slice-001` (se deu commit)
- `rm -rf specs/001/`
- Remover linha de `docs/slice-registry.md`
- Atualizar telemetria

**Tudo vocabulário proibido. Fricção máxima.**

**Gap — G-15 [NOVO] — Sem skill `/abort-slice NNN`.** Tipo: fricção + sem-recuperação. Severidade: **Alta**. Correlação: nenhuma.

---

### M-10. Dia 3, fim do dia — PM quer "fechar bonito" e parar

**O que o PM gostaria:** "obrigado pelo dia, amanhã continua no slice 001 passo X". Salvar estado, anotar progresso, fechar sessão com tranquilidade.

**O que existe hoje:** `stop-gate.sh` (hook de Stop event) — mas é mecânico, não gera resumo PM-ready.

**Gap — G-16 [NOVO] — Sem skill `/end-day` ou `/checkpoint` que gere mini-resumo PM-ready + marque estado do slice.** Tipo: perda-de-estado. Severidade: **Média**. Correlação parcial: `stop-gate.sh` existe como hook mas não produz output conversacional.

---

### M-11. Dia 10 — PM volta depois de 1 semana fora

**Mesma dor de M-05 amplificada.** O agente não tem memória do estado exato, a sessão anterior do CLI pode ter sido compactada, e o PM esqueceu completamente. Se G-09 for implementado (session-start lê telemetria + imprime resumo), isso cobre ~80% da pausa longa.

**Gap — G-17 [NOVO] — `session-start.sh` não gera "snapshot semanal" com agregado de último evento por slice ativo + ADRs novos + arquivos mudados em `main`.** Tipo: perda-de-estado. Severidade: **Média**. É extensão de G-09 mas foca em **múltiplos slices ativos** e **contexto ADR/product**. Se G-09 for bem feito, G-17 vira polimento.

---

### M-12. Ao longo do slice — PM fica cego entre handoffs de sub-agent

**Problema estrutural:** architect → ac-to-test → implementer → verifier → reviewer → merge. Cada transição é **silenciosa pro PM**. Ele só vê output quando explicitamente pede `/explain-slice` (depois do fato) ou quando um comando específico imprime algo no chat.

**Gap — G-18 [NOVO] — Cada handoff de sub-agent deve gerar output-PM automaticamente, não sob demanda.** Tipo: cegueira. Severidade: **Crítica**. Correlação: **B-016** (exato remédio). Esta auditoria confirma B-016 como **topo de prioridade** junto com G-03 e G-09.

---

### M-13. Durante o slice — PM quer testar visualmente a tela antes do código

**Cenário Hercules/Lovable:** PM imagina uma tela, quer ver preview antes de investir tokens do implementer. Hoje o único output "visual" antes do código é `plan.md` (texto).

**Gap — G-19 [NOVO] — Sem sub-agent `designer` e skill `/preview NNN`.** Tipo: cegueira. Severidade: **Alta**. Correlação: **B-018** (proposto no backlog, prioridade média). Esta auditoria **eleva** a prioridade para Alta — preview visual é fundamental pra PM aprovar direção antes de gastar ciclo completo.

---

### M-14. Após merge do slice — testes no ambiente de hom

**Cenário:** `/merge-slice` executou, PR mergeado, CI rodou, código em `main`. PM quer abrir uma URL e ver funcionando.

**Hoje:** não existe ambiente de homologação. PM teria que rodar localmente — vocabulário proibido (`php artisan serve`, `npm run dev`, etc.).

**Gap — G-20 [NOVO] — Sem ambiente de hom automático + URL clicável.** Tipo: fricção + cegueira. Severidade: **Alta**. Correlação: **B-019** (publish-to-staging, bloqueado por ADR-0005). Confirmação: B-019 é bloqueador crítico da experiência PM.

---

### M-15. Após merge do slice — PM esquece de rodar retrospective

**Fluxo CLAUDE.md §6 passo 15:** "`/slice-report NNN` e `/retrospective NNN` obrigatórios pós-merge". Mas:
- Obrigatório por quem? Nenhum hook dispara automaticamente após `/merge-slice`.
- Se o PM esquece, a retrospectiva não acontece. Telemetria de aprendizados cessa.

**Gap — G-21 [NOVO] — `/merge-slice` não invoca `/slice-report` + `/retrospective` automaticamente no handoff final.** Tipo: perda-de-estado. Severidade: **Alta**. Correlação: nenhuma.

---

### M-16. Decisão de próximo ADR (ADR-0003 testing, 0004 deploy, 0005 staging, etc.)

**Hoje:** existe `/decide-stack` que gera ADR-0001 em linguagem PM. Mas **só** para stack. Para ADRs futuros, o PM precisaria usar `/adr NNNN "título"` — skill de template genérico, **sem** tradução PM, sem recomendação estruturada, sem opções A/B/C com trade-offs em produto.

**Gap — G-22 [NOVO] — Faltam skills/agents equivalentes a `/decide-stack` para outros ADRs decisivos.** Tipo: fricção + cegueira. Severidade: **Alta**. Correlação: nenhuma. Proposta: skill `/decide NNNN "título do trade-off"` que invoca um sub-agent genérico `decision-advisor` que estrutura qualquer decisão em formato "Recomendação + 2 alternativas + trade-off em produto".

---

### M-17. Hooks bloquearam uma ação do agente no meio do slice

**Cenário:** implementer tentou editar `.claude/settings.json` sem relock. `settings-lock.sh` bloqueou. Mensagem técnica aparece no chat do PM:
```
[settings-lock] drift detectado: .claude/settings.json modificado fora do relock-harness.sh
exit 1
```

PM vê isso e entra em pânico — "o que é drift?".

**Gap — G-23 [NOVO] — Hooks não traduzem mensagens de bloqueio para linguagem PM.** Tipo: cegueira + fricção. Severidade: **Média-Alta**. Correlação: parcial com **B-016** (tradução contínua) mas B-016 foca em sub-agents, não em hooks. Proposta: wrapper que pós-processa output de hook → tradução amigável ("Tentativa de alterar trava de segurança do harness. Nada foi feito. Se você quis alterar uma trava, me avise e eu explico o caminho correto.").

---

### M-18. Relock externo quando uma trava precisa mudar

**Cenário B-020 já documentado.** PM precisa abrir PowerShell/Git Bash, rodar comando, digitar `RELOCK`, commitar, voltar.

**Confirmação:** esta auditoria **eleva** B-020 de "baixa prioridade" para **Alta**, pelas mesmas razões de M-10/M-11 (fricção é frequente quando se está evoluindo hooks/skills — e esse é precisamente o trabalho dessa sessão atual).

---

### M-19. Slice-000-smoke (primeiro teste real da cadeia)

**B-013 documenta:** cadeia `verifier → reviewer → merge-slice` nunca foi exercida end-to-end. Esta auditoria **confirma** via verificação de `specs/` (vazio) e `docs/retrospectives/` (vazio). B-013 é **mandatório antes de qualquer slice real**, e esta auditoria **não altera** a prioridade — mantém **Crítico**.

**Observação importante:** vários dos gaps acima (G-05, G-07, G-08, G-11, G-12, G-18, G-21, G-23) só ficam completamente visíveis **quando a cadeia rodar pelo menos uma vez**. Priorizar o smoke-slice antes de implementar os gaps resolve a ordem — sem rodada real, estamos debugando teorias. **Proposta:** rodar o smoke-slice **imediatamente**, capturar toda a fricção observada, e usar como insumo pra priorizar o resto.

---

### M-20. Auditoria periódica do harness (/guide-check)

`/guide-check` existe. B-011 (drift semântico skill↔script) e B-012 (CHECK-4 false-positive) documentam dois problemas reais. Esta auditoria **confirma** prioridade:
- B-011: **Média-Alta** — confiança na skill é frágil se ela mente sobre impl
- B-012: **Alta** — false-positive recorrente mascara findings reais, inutiliza o /guide-check

---

### M-21. Auditoria fresca do harness por outro agente ("Sessão 3")

B-014 documenta o caso: agente de validação independente **modificou** o working tree. Esta auditoria confirma o diagnóstico e a recomendação (worktree isolada). Severidade: **Alta** — qualquer futura validação/auditoria precisa disso.

---

## Lista consolidada de gaps [NOVOS] da Fase B

| ID | Gap | Tipo | Severidade | Momento | Correlação backlog |
|---|---|---|---|---|---|
| G-01 | Skill de onboarding Dia 1 ausente | cegueira | Crítica | M-01 | — |
| G-02 | `/new-slice` não sugere número automaticamente | fricção | Baixa | M-02 | — |
| G-03 | Falta sub-agent `roadmap-planner` / skill `/next-slice` | cegueira+fricção | **Crítica** | M-02 | B-022 (proposto mas não registrado) |
| G-04 | Falta skill conversacional `/draft-spec NNN` | fricção | Alta | M-02 | parcial B-017 |
| G-05 | Não existe skill `/draft-plan NNN` | perda-estado+fricção | Alta | M-03 | — |
| G-06 | `plan.md` é técnico; PM não aprova o que não entende | cegueira | **Crítica** | M-03 | **B-016** |
| G-07 | Não existe skill `/draft-tests NNN` | fricção+perda-estado | Alta | M-04 | — |
| G-08 | Falta `tests-pm.md` automático | cegueira | Alta | M-04 | **B-016** |
| G-09 | `session-start` não lê telemetria e não mostra "onde paramos" | perda-estado+cegueira | **Crítica** | M-05 | — |
| G-10 | Não existe `/where-am-i` ou `/status` | perda-estado | Alta | M-05 | — |
| G-11 | `/verify-slice` não invoca `/explain-slice` automaticamente | cegueira+fricção | **Crítica** | M-06 | **B-010 + B-016** |
| G-12 | Cadeia `/verify-slice` → `/review-pr` → `/merge-slice` não é automática | fricção | Alta | M-06 | — (verificar divergência) |
| G-13 | Tradução do verifier não é enforced mecanicamente | cegueira | Alta | M-07 | **B-010 + B-016** |
| G-14 | Sem fluxo de mudança de escopo em slice ativo | sem-recuperação | Alta | M-08 | — |
| G-15 | Sem skill `/abort-slice NNN` | fricção+sem-recuperação | Alta | M-09 | — |
| G-16 | Sem skill `/end-day` ou `/checkpoint` | perda-estado | Média | M-10 | — |
| G-17 | `session-start` não gera snapshot semanal | perda-estado | Média | M-11 | — (extensão de G-09) |
| G-18 | Handoffs silenciosos entre sub-agents | cegueira | **Crítica** | M-12 | **B-016** |
| G-19 | Sem `designer` e `/preview NNN` | cegueira | Alta | M-13 | B-018 (elevar prioridade) |
| G-20 | Sem ambiente de hom + URL clicável | fricção+cegueira | Alta | M-14 | B-019 (bloqueado ADR-0005) |
| G-21 | `/merge-slice` não dispara `/slice-report` + `/retrospective` | perda-estado | Alta | M-15 | — |
| G-22 | Faltam skills `/decide-X` para ADRs futuros | fricção+cegueira | Alta | M-16 | — |
| G-23 | Hooks não traduzem mensagens de bloqueio para linguagem PM | cegueira+fricção | Média-Alta | M-17 | parcial B-016 |

**Total:** 23 gaps novos.

---

## Cruzamento com backlog existente — revisões propostas

| Item backlog | Status atual | Revisão proposta pela auditoria |
|---|---|---|
| B-001 (post-edit-gate pós stack) | bloqueado por ADR-0001 (já aceito) | **Destravar** — ADR-0001 aceito 2026-04-11. Virar ativo. |
| B-007 (CI externo) | bloqueado por ADR-0001 | **Destravar** — mesmo motivo. |
| B-009 (auto-approve workflow) | aberto | Manter. Depende de primeiro slice real. |
| B-010 (tradução auto técnico→produto) | aberto | **ELEVAR prioridade** — Crítica. Bloqueia G-11, G-13. |
| B-011 (drift semântico /guide-check) | aberto | Manter Média-Alta. |
| B-012 (CHECK-4 false-positive) | aberto | **ELEVAR** para Alta (hoje implícito Média). |
| B-013 (slice-000-smoke) | mandatório antes slice real | Manter. **Reforçar ordem:** rodar antes dos gaps de fluxo PM, pra não teorizar. |
| B-014 (Sessão 3 read-only via worktree) | aberto | Manter Alta. |
| B-015 (snapshot false-positive) | descartado | OK. |
| B-016 (tradução contínua sub-agents) | "alta prioridade" | **ELEVAR a Crítica.** Resolve G-06, G-08, G-11, G-13, G-18. Bloqueia tudo. |
| B-017 (slice-kits) | alta | Manter. Não cobre G-04 (captura NL→AC). |
| B-018 (designer + /preview) | média | **ELEVAR a Alta** (G-19). |
| B-019 (publish-staging) | média (bloqueado ADR-0005) | Manter, mas **criar ADR-0005** como item próprio. |
| B-020 (wrapper relock) | baixa | **ELEVAR a Alta** (M-18 — fricção frequente durante fase atual). |
| B-021 (implementer paralelo) | baixa | Manter. Otimização tardia. |

---

## Recomendação de ordem de execução (input para Fase A)

A Fase A vai consolidar esta lista + decisão do PM. Minha recomendação antes de discutir:

**Bloco 0 — Destravar.** (horas)
1. **B-001** — operacionalizar post-edit-gate com comandos Laravel (stack já decidida).
2. **B-007** — setup de CI GitHub Actions (stack já decidida).

**Bloco 1 — Crítico de cegueira PM.** (dias)
3. **B-010** — tradutor automático técnico→produto (sub-agent `translator-pm` ou equivalente).
4. **B-016** — aplicar tradutor em todos os handoffs de sub-agent.
5. **G-09** — `session-start.sh` lê telemetria do slice ativo e imprime resumo.
6. **G-11** — `/verify-slice` dispara `/explain-slice` automaticamente em qualquer verdict.
7. **G-18** — auto-output-PM em cada handoff (é a consequência prática de B-016, mas listar como item entregável separado).

**Bloco 2 — Crítico de roteiro PM.** (dias)
8. **G-03 / B-022** — sub-agent `roadmap-planner` + skill `/next-slice` (lê PRD, sugere próximo slice com justificativa em PT-BR).
9. **G-01** — skill `/start` de onboarding Dia 1.
10. **G-04** — skill conversacional `/draft-spec NNN` (captura descrição NL→ACs).
11. **G-05 / G-07** — skills `/draft-plan NNN` e `/draft-tests NNN` que disparam architect e ac-to-test explicitamente.
12. **G-10** — skill `/where-am-i` on-demand.

**Bloco 3 — Smoke de cadeia.** (horas)
13. **B-013** — rodar `slice-000-smoke` pra exercitar a cadeia end-to-end com tudo que está instalado até aqui. Capturar toda fricção residual e alimentar o próximo bloco.

**Bloco 4 — Recuperação de falhas PM.** (dias)
14. **G-14** — fluxo formal de mudança de escopo.
15. **G-15** — skill `/abort-slice NNN`.
16. **G-23** — wrapper de tradução de mensagens de hook.
17. **B-012** — refinar CHECK-4 regex (ruído mascara findings).
18. **B-011** — alinhar doc↔impl de `/guide-check`.

**Bloco 5 — Polimento.** (dias)
19. **G-16** — `/end-day`.
20. **G-21** — auto-disparo `/slice-report` + `/retrospective` pós-merge.
21. **B-020** — wrapper `.bat` relock.
22. **G-02** — auto-numbering `/new-slice`.
23. **B-014** — Sessão 3 em worktree isolada.

**Bloco 6 — Dependente de ADRs futuros.** (dias, mas só depois das decisões de produto)
24. **G-22** — skills `/decide-X` para ADRs 0003-0006.
25. **B-018 / G-19** — designer + /preview (depende da stack UI estabilizada).
26. **B-019 / G-20** — publish-staging (depende de ADR-0005).
27. **B-009** — auto-approve GitHub Action (depende de primeiro slice real).

**Bloco 7 — Otimização tardia.**
28. **B-021** — implementer paralelo.

**Itens removidos por serem product-work, não harness-work:**
- Nenhum — a auditoria é focada em operabilidade do harness.

**Itens removidos por serem obsoletos:**
- **B-015** já está marcado como descartado. OK.

---

## Divergências com o backlog existente que precisam decisão PM

1. **B-016 prioridade.** Backlog diz "alta prioridade". Auditoria diz **Crítica**. Peso: resolve 5 gaps independentes. **Recomendação: Crítica.**
2. **B-018 prioridade.** Backlog diz "média, depois do smoke-slice". Auditoria diz **Alta**. Peso: preview visual antes de gastar ciclo completo de implementer é economia grande. **Recomendação: Alta.**
3. **B-020 prioridade.** Backlog diz "baixa". Auditoria diz **Alta** (pra fase atual de evolução do harness). Peso: nessa fase específica o atrito é constante. **Recomendação: Alta temporariamente, rebaixar a Baixa quando o harness parar de evoluir com tanta frequência.**
4. **B-012 prioridade.** Backlog diz "média". Auditoria diz **Alta** (ruído mascara findings reais). **Recomendação: Alta.**
5. **Ordem B-013 (smoke) vs. blocos de cegueira PM.** Backlog implícito coloca B-013 cedo. Auditoria diz: rodar B-013 **depois** do Bloco 1 (tradução automática), pra que a cadeia do smoke já exercite o B-016 e produza resultado rico em telemetria PM-ready. **Recomendação: B-013 depois do Bloco 1.**
6. **G-12 divergência técnica a verificar.** A skill `/review-pr` §8 diz que review-slice dispara `/merge-slice` automaticamente. Mas `/verify-slice` não dispara `/review-pr`. Confirmar na Fase A se isso é bug ou desenho intencional. **Ação: ler `scripts/verify-slice.sh` final-handoff.**

---

## Próximo passo único e claro (para o PM)

Marcar uma opção abaixo:

- [ ] **A** — Aceito a ordem proposta (Blocos 0 → 7) e começo pelo Bloco 0 (destravar B-001 + B-007).
- [ ] **B** — Quero discutir as divergências 1-5 antes de aprovar a ordem.
- [ ] **C** — Quero priorizar um bloco diferente — diga qual.
- [ ] **D** — Pergunto primeiro: o que significa "destravar B-001 + B-007"? (peço explicação em PT-BR).
