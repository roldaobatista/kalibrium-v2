# Kalibrium V2 — Instruções operacionais para o agente

**Este é o arquivo raiz de instruções operacionais deste repositório.** As fontes operacionais permitidas por **R1** são `CLAUDE.md`, `docs/constitution.md`, `.claude/agents/*.md` e `.claude/skills/*.md`. Qualquer outra fonte (`.cursorrules`, `AGENTS.md`, `GEMINI.md`, `copilot-instructions.md`, `.bmad-core/`, `.cursor/`, `.windsurfrules`, `.aider.conf.yml`) é proibida e bloqueada por hook no SessionStart.

Versão: 2.7.0 — 2026-04-15 (auto-approval do plano: dual-gate spec-auditor + plan-reviewer dispensa aprovação manual do PM).
<!-- Contagem: 22 agents em .claude/agents/ (21 sub-agents + 1 orchestrator), 38 skills em .claude/skills/ -->

---

## 0. Leitura obrigatória em toda sessão

### 0.0. Bootstrap obrigatório quando o orquestrador ativo é Codex CLI

O Codex CLI **não dispara automaticamente** todos os eventos de hook do Claude Code. Portanto, quando o orquestrador ativo for Codex CLI, o primeiro ato operacional da sessão é executar mentalmente e por comando o equivalente a `/codex-bootstrap` antes de qualquer trabalho de produto, código, documentação ou auditoria.

Sequência obrigatória no início de toda sessão Codex neste repositório:

1. Ler `CLAUDE.md`.
2. Ler `docs/constitution.md`.
3. Ler `docs/TECHNICAL-DECISIONS.md`.
4. Ler `docs/documentation-requirements.md`.
5. Ler `project-state.json`.
6. Ler `docs/handoffs/latest.md`.
7. Ler `.claude/agents/orchestrator.md`.
8. Rodar `git status --short`.
9. Rodar `bash scripts/hooks/session-start.sh`.
10. Rodar `bash scripts/hooks/settings-lock.sh --check`.
11. Rodar `bash scripts/hooks/hooks-lock.sh --check`.
12. Confirmar ao PM o estado restaurado, a branch/commit atual e a próxima ação antes de alterar arquivos.

Sequência obrigatória antes de encerrar uma sessão Codex:

1. Atualizar `project-state.json`.
2. Criar `docs/handoffs/handoff-YYYY-MM-DD-HHMM.md`.
3. Atualizar `docs/handoffs/latest.md`.
4. Validar JSON e `git diff --check`.
5. Confirmar que arquivos selados não foram alterados.
6. Commitar o checkpoint/handoff ou declarar explicitamente por que ele ficará pendente.

Para que o Codex CLI carregue este arquivo automaticamente sem violar R1, a configuração global do Codex deve conter `project_doc_fallback_filenames = ["CLAUDE.md"]`. Não criar `AGENTS.md` neste repositório.

---

Antes de qualquer ferramenta ser invocada, ler nesta ordem:

1. `CLAUDE.md` (este arquivo)
2. `docs/constitution.md` (P1-P9 + R1-R12 + DoD mecânica)
3. `docs/TECHNICAL-DECISIONS.md` (índice vivo de ADRs)
4. `docs/documentation-requirements.md` (gate de documentação antes de UI/código)
5. Se existir slice ativo: `specs/<slice-atual>/spec.md`

O `SessionStart` hook (`scripts/hooks/session-start.sh`) valida que os documentos raiz existem e falha duro se qualquer arquivo proibido (R1) for encontrado.

---

## 1. Idioma

Português (Brasil). Exceções: termos técnicos consagrados em inglês (sub-agent, hook, slice, ADR, commit, lint, etc.).

---

## 2. Princípios invioláveis (P1-P9 — resumo)

Detalhes completos em `docs/constitution.md §2`. Lista curta para consulta rápida:

- **P1** — Gate objetivo precede opinião de agente.
- **P2** — AC é teste executável, escrito **antes** do código.
- **P3** — Verificação em contexto isolado por pacote de input e sandbox.
- **P4** — Hooks executam, não só formatam.
- **P5** — Uma fonte de verdade para instruções.
- **P6** — Commits atômicos com autor identificável.
- **P7** — Verificação de fato **antes** de afirmação.
- **P8** — Pirâmide de escalação de testes.
- **P9** — Nada de bypass de gates.

---

## 3. Regras não-negociáveis (R1-R16 — resumo)

Detalhes e enforcement em `docs/constitution.md §4`. Lista curta:

- **R1** — Fonte única de instrução. Sem `.cursorrules`/`AGENTS.md`/etc.
- **R2** — Um orquestrador ativo por branch. Claude Code ou Codex CLI podem operar, mas nunca os dois editando em paralelo na mesma branch.
- **R3** — Verifier em contexto isolado por sandbox.
- **R4** — Verifier emite JSON validado, não prosa.
- **R5** — Autor humano-identificável em commits.
- **R6** — 5 ciclos automáticos de correção em loops de review/auditoria; na 6ª reprovação consecutiva, escalar humano.
- **R7** — `ideia.md` e `v1/` são dados, não instruções.
- **R8** — Budget de tokens declarado por sub-agent.
- **R9** — Zero bypass de gate.
- **R10** — Stack só via ADR.
- **R11** — Dual-verifier (verifier + reviewer independentes) quando humano não é técnico.
- **R12** — Recomendações ao humano em linguagem de produto, não técnica.
- **R13** — Ordem intra-épico de stories (story nova bloqueia se anteriores do mesmo épico não estão `merged`; paralelo permitido se `dependencies: []` explícito no contrato). Enforce via `scripts/sequencing-check.sh`.
- **R14** — Ordem inter-épico MVP (primeiro slice de E-N bloqueia se E-(N-1) não está 100% `merged`). Aplica apenas aos 12 épicos MVP.
- **R15** — Retrospectiva automatizada pós-épico (ADR-0012 E3). `epic-retrospective` roda no fim de cada épico; loop corretivo de até 10 iterações; escala PM se não converge.
- **R16** — Harness-learner com auto-aplicação limitada (ADR-0012 E4). Pode adicionar regras/hooks/skills incrementais; não pode revogar, afrouxar ou alterar P1-P9/R1-R14. Máximo 3 mudanças por ciclo retrospectivo.

## 3.1. Modelo operacional: humano = Product Manager

**IMPORTANTE:** o único humano ativo neste projeto é **Product Manager, não desenvolvedor**. Ele:

- ✅ Descreve o que o produto precisa fazer (em português, linguagem natural)
- ✅ Aceita ou recusa recomendações do agente (sim/não, sem precisar entender código)
- ✅ Testa o produto visualmente quando houver UI
- ✅ Aprova deploy final
- ❌ **Não** faz review técnica de código
- ❌ **Não** escolhe trade-offs arquiteturais sem recomendação forte do agente
- ❌ **Não** decide escalações R6 sem relatório traduzido via `/explain-slice`

**Consequências operacionais:**

1. **Toda saída para o humano passa pelo tradutor R12.** Skills `/explain-slice` e `/decide-stack` fazem isso. Nunca apresentar verification.json cru, plan.md cru, ou stack trace ao humano.

2. **Review de PR é feita por 2 sub-agents em contextos isolados** (R11): verifier + reviewer. Ambos devem aprovar antes do merge automático. Nenhum vê output do outro.

3. **Decisões arquiteturais (ADRs)** chegam ao humano como "minha recomendação forte é X, alternativas B e C estão aqui, você marca qual aceita". Exemplo: `/decide-stack` gera ADR-0001 pronto pra decisão de produto.

4. **Escalações R6** (verifier, reviewer ou auditoria reprovou pela 6ª vez consecutiva no mesmo gate) **obrigatoriamente** invocam `/explain-slice NNN` para traduzir o problema em linguagem de produto antes de mostrar ao humano.

5. **Admin merge do humano (owner)** é um recurso registrado no ruleset como bypass permitido (ver `docs/incidents/pr-1-admin-merge.md` §Correção permanente). Fica auditável no log do GitHub. Usar apenas quando ambos os verificadores concordam — nunca para bypassar rejeições.

**Como agente, você deve:**
- Ao gerar qualquer conteúdo destinado ao humano, aplicar o vocabulário permitido de R12.
- Ao terminar um slice, rodar `/review-pr NNN` após `/verify-slice NNN` — ambos devem aprovar.
- Nunca pedir ao humano para "revisar o diff" ou "olhar o plan.md".
- Sempre oferecer próximo passo único e claro ("aceitar A", "testar na tela X", "decidir entre sim/não").

---

## 4. Verificação de fato antes de afirmação (P7)

**Proibido** dizer "pronto", "corrigido", "implementado", "funcionando" sem:
1. O comando que validou
2. O output (ou trecho relevante)
3. O exit code

Exemplo correto:
> Rodei `npx vitest run tests/foo.test.ts` → exit 0, 3 passed. AC-001 verde.

Exemplo proibido:
> Pronto, implementei AC-001.

---

## 5. Pirâmide de escalação de testes (P8)

| Momento | Escopo do teste |
|---|---|
| Durante Edit/Write | Somente o teste afetado |
| Ao fechar uma task | Grupo de testes do módulo |
| Antes de commit | Testes afetados pelo staged diff |
| Antes de push | Testsuite do domínio |
| CI | Suite full |

Agente **nunca** roda suite full no meio de uma task. Hook `post-edit-gate.sh` garante isso.

---

## 6. Fluxo completo do projeto

### Fase A — Descoberta
1. `/intake` — entrevista guiada com as 10 perguntas estratégicas.
2. Sub-agents `domain-analyst` + `nfr-analyst` produzem glossário, modelo de domínio, riscos, NFRs.
3. PM revisa artefatos.
4. `/freeze-prd` — congela PRD. Nenhuma decisão técnica antes deste gate.

### Fase B — Estratégia Técnica
5. `/decide-stack` — gera recomendação de stack (ADR-0001).
6. ADRs adicionais conforme necessário (auth, dados, deploy).
7. `/freeze-architecture` — congela arquitetura. Nenhum código antes deste gate.

### Fase C — Planejamento
8. `/decompose-epics` — decompõe PRD em épicos com roadmap.
9. PM aprova sequência e prioridades.
10. `/decompose-stories ENN` — decompõe épico em stories com Story Contract.
11. PM aprova cada Story Contract.
12. Antes de iniciar qualquer story com UI ou tela, validar `docs/documentation-requirements.md`: documentos globais obrigatórios e documentos do épico precisam existir ou a story não começa.

### Fase D — Execução (por story)
13. `/start-story ENN-SNN` — cria slice(s) a partir do Story Contract.
    - **Gate R13/R14 (obrigatório):** `scripts/sequencing-check.sh --story ENN-SNN` executa antes de criar slice. Bloqueia se stories anteriores do mesmo épico não estão `merged` em `project-state.json[epics_status]`, ou se é a 1ª story de um épico MVP novo e o épico anterior não está fechado. Paralelismo intra-épico permitido quando o contrato declara `dependencies: []` no frontmatter. Bypass só via `KALIB_SKIP_SEQUENCE="<motivo>"` (registra incidente).
14. `/audit-spec NNN` → sub-agent `spec-auditor` valida spec.md; se houver findings, fixer corrige e re-audita até zero findings.
15. `/draft-plan NNN` → sub-agent `architect` gera plan.md.
16. `/review-plan NNN` → sub-agent `plan-reviewer` valida plan.md em contexto limpo; se houver findings, corrige plan e re-audita até zero findings.
17. **Auto-approval do plano:** quando `spec-auditor` E `plan-reviewer` ambos retornam `verdict: approved` com `findings: []`, o orquestrador prossegue automaticamente para a próxima etapa (draft-tests) **sem pausar para o PM**. O PM só é envolvido em: (a) escalação R6 (6º rejected consecutivo), (b) decisão de produto explícita que apareça mid-flow, (c) PM solicita pausa via `/checkpoint` ou conversa direta. Esta política reduz fricção do PM mantendo R11 (dual-verifier) preservado.
18. `/draft-tests NNN` → sub-agent `ac-to-test` gera testes red.
19. Commit: `test(slice-NNN): AC tests red`.
20. Sub-agent `implementer` faz testes virarem verdes, task por task.

### Fase E — Pipeline de Gates (por slice)

> **Ordem definida no orchestrator.md:** verifier (1º) → reviewer (2º, só se verifier aprovou) → [security + test-audit + functional] (3º, em paralelo) → **master-auditor (4º, ADR-0012: consolidação dual-LLM Claude Opus 4.6 + GPT-5 via Codex CLI)**. **ZERO TOLERANCE:** nenhum finding de qualquer severidade é aceito. Gate só aprova com `findings: []`. Loop: gate rejeita → fixer corrige TODOS → re-run do mesmo gate → repete até zero findings; as 5 primeiras reprovações consecutivas do mesmo gate ficam no loop automático e a 6ª escala ao PM.

19. `/verify-slice NNN` → verifier em contexto isolado → `verification.json`.
20. `/review-pr NNN` → reviewer em contexto isolado → `review.json`.
21. `/security-review NNN` → security-reviewer em contexto isolado → `security-review.json`.
22. `/test-audit NNN` → test-auditor em contexto isolado → `test-audit.json`.
23. `/functional-review NNN` → functional-reviewer em contexto isolado → `functional-review.json`.
24. `/master-audit NNN` → master-auditor consolida as 5 saídas anteriores em verdict dual-LLM (Opus + GPT-5) em contexto isolado → `master-audit.json`. Se as duas trilhas divergirem, master-auditor tenta reconciliar em até 3 rodadas; persistindo, escala PM via `/explain-slice NNN` (R12). **Invocação da Trilha GPT-5 (Codex CLI):** ler `docs/operations/codex-gpt5-setup.md` — em ChatGPT Plus auth, NÃO passar `--model` (default = gpt-5); no Windows usar `--sandbox workspace-write` (não `read-only`, evita `CreateProcessAsUserW failed: 5`).
25. Se qualquer gate emitir findings (mesmo minor/low/info) → `/fix NNN [gate]` → fixer corrige TODOS → **re-run do mesmo gate** (não pula). Repete até `findings: []`.
26. Se 6º `rejected` consecutivo no mesmo gate (R6) → parar, escalar humano via `/explain-slice NNN`.
27. Todos os 5 gates + master-auditor `approved` com zero findings → `/merge-slice NNN`.

### Fase F — Encerramento
27. `/slice-report NNN` e `/retrospective NNN` obrigatórios pós-merge.
28. Quando todos os épicos MVP completos → `/release-readiness`.

### Gestão de estado (transversal)
- `/checkpoint` — salva estado em `project-state.json` + handoff a qualquer momento.
- `/resume` — restaura contexto no início de sessão.
- `/project-status` — mostra estado atual em linguagem de produto.

---

## 7. Comandos (skills)

### Descoberta e Estratégia
| Intenção | Comando |
|---|---|
| Entrevista de descoberta (10 perguntas) | `/intake` |
| Congelar PRD | `/freeze-prd` |
| Gerar recomendação de stack (ADR-0001) | `/decide-stack` |
| Congelar arquitetura | `/freeze-architecture` |
| Criar ADR | `/adr NNN "título"` |

### Planejamento
| Intenção | Comando |
|---|---|
| Decompor PRD em épicos | `/decompose-epics` |
| Decompor épico em stories | `/decompose-stories ENN` |
| Iniciar story (criar slice) | `/start-story ENN-SNN` |

### Execução (slice)
| Intenção | Comando |
|---|---|
| Criar slice manual | `/new-slice NNN "título"` |
| Gerar spec a partir de descrição PM | `/draft-spec NNN` |
| Auditar spec antes do plano | `/audit-spec NNN` |
| Gerar plan técnico | `/draft-plan NNN` |
| Auditar plan antes do PM/testes | `/review-plan NNN` |
| Gerar testes red | `/draft-tests NNN` |

### Pipeline de Gates
| Intenção | Comando |
|---|---|
| Verificar slice (mecânico) | `/verify-slice NNN` |
| Revisar slice (estrutural, R11) | `/review-pr NNN` |
| Revisão de segurança (OWASP, LGPD) | `/security-review NNN` |
| Auditoria de testes (cobertura, qualidade) | `/test-audit NNN` |
| Revisão funcional (produto/UX) | `/functional-review NNN` |
| Corrigir findings de gate | `/fix NNN [gate]` |
| Merge após todos os gates | `/merge-slice NNN` |

### Estado e Retomada
| Intenção | Comando |
|---|---|
| Ver estado do projeto (R12) | `/project-status` |
| Inicializar sessão Codex CLI pelo harness | `/codex-bootstrap` |
| Salvar checkpoint | `/checkpoint` |
| Restaurar sessão anterior | `/resume` |
| Traduzir slice para PM (R12) | `/explain-slice NNN` |
| Próximo slice recomendado | `/next-slice` |
| Onde estou (detalhes técnicos) | `/where-am-i` |
| Verificar saúde do contexto | `/context-check` |
| Onboarding dia 1 | `/start` |

### Qualidade e Governança
| Intenção | Comando |
|---|---|
| Auditoria do harness | `/guide-check` |
| Relatório de slice | `/slice-report NNN` |
| Retrospectiva | `/retrospective NNN` |
| Validar prontidão para release | `/release-readiness` |
| Procurar arquivos proibidos | `/forbidden-files-scan` |
| Validar MCPs ativos | `/mcp-check` |

---

## 8. Sub-agents disponíveis

### Núcleo de Descoberta
| Nome | Papel | Budget |
|---|---|---|
| `domain-analyst` | Extrai glossário, modelo de domínio, riscos, suposições | 30k |
| `nfr-analyst` | Extrai e estrutura NFRs com métricas mensuráveis | 25k |

### Núcleo de Planejamento
| Nome | Papel | Budget |
|---|---|---|
| `architect` | Gera plan.md a partir de spec.md | 30k |
| `epic-decomposer` | Decompõe PRD em épicos com dependências | 30k |
| `planning-auditor` | Audita roadmap/épicos antes de apresentar ao PM | 40k |
| `story-decomposer` | Decompõe épico em stories com Story Contract | 30k |
| `story-auditor` | Audita stories antes de iniciar slices | 40k |
| `spec-auditor` | Audita spec.md de slice antes do plano técnico | 25k |
| `ac-to-test` | Gera testes red a partir de ACs | 40k |
| `plan-reviewer` | Revisa plan.md antes de execução | 25k |

### Núcleo de Design e Contratos
| Nome | Papel | Budget |
|---|---|---|
| `ux-designer` | Gera UX/design docs, wireframes, inventário de telas e fluxos | 50k |
| `api-designer` | Gera contratos REST por épico e valida consistência de API | 30k |
| `data-modeler` | Gera ERDs e specs de migrations por épico | 25k |

### Núcleo de Execução
| Nome | Papel | Budget |
|---|---|---|
| `implementer` | Faz testes red virarem verdes | 80k |
| `fixer` | Corrige findings de spec-audit ou qualquer gate de review | 60k |

### Núcleo de Qualidade (gates independentes em contexto isolado)
| Nome | Papel | Budget |
|---|---|---|
| `verifier` | Valida slice mecanicamente, emite `verification.json` | 25k |
| `reviewer` | Revisão estrutural de código, emite `review.json` (R11) | 30k |
| `security-reviewer` | Revisão de segurança (OWASP, LGPD, secrets), emite `security-review.json` | 25k |
| `test-auditor` | Auditoria de cobertura e qualidade de testes, emite `test-audit.json` | 25k |
| `functional-reviewer` | Revisão funcional (produto/UX/ACs), emite `functional-review.json` | 25k |

### Núcleo de Governança
| Nome | Papel | Budget |
|---|---|---|
| `guide-auditor` | Auditor periódico de drift no harness, emite `guide-audit.json` | 15k |

### Orquestrador
| Nome | Papel | Budget |
|---|---|---|
| `orchestrator` | Coordena todos os sub-agents, máquina de estados, cadeia fixer→re-gate | 100k |

> O orquestrador não é um sub-agent — é o papel principal do orquestrador ativo (Claude Code ou Codex CLI em modo exclusivo). Definido em `.claude/agents/orchestrator.md` com regras de sequenciamento, paralelismo e checkpoint.

Detalhes em `.claude/agents/*.md`. Total: 22 agents (21 sub-agents + 1 orchestrator) organizados em 7 núcleos.

---

## 9. Como atualizar um hook ou settings.json (pós-Bloco 1)

A partir do meta-audit 2026-04-10 (Bloco 1), os arquivos críticos do harness são **selados** e não podem ser modificados pelo agente Claude Code:

- `.claude/settings.json`
- `.claude/settings.json.sha256`
- `.claude/allowed-git-identities.txt`
- `.claude/git-identity-baseline`
- `scripts/hooks/MANIFEST.sha256`
- Qualquer arquivo dentro de `scripts/hooks/`
- Qualquer arquivo dentro de `.claude/telemetry/` (append-only via `scripts/record-telemetry.sh`)

**O agente NÃO pode editar esses arquivos via `Edit`/`Write` nem via `Bash` (`echo >>`, `sed -i`, `tee`, `cp`, `mv`).** Os hooks `settings-lock.sh`, `hooks-lock.sh`, `telemetry-lock.sh` e `sealed-files-bash-lock.sh` bloqueiam todas as tentativas mecanicamente.

### Procedimento legítimo de alteração (PM, em terminal externo)

```bash
# 1. Saia do Claude Code (encerre a sessão atual).
# 2. Em terminal externo (bash, fora do agente):
cd /c/PROJETOS/saas/kalibrium-v2

# 3. Edite o arquivo manualmente com seu editor preferido.
#    Exemplo: vim scripts/hooks/post-edit-gate.sh

# 4. Regenere os selos (4 camadas de salvaguarda):
KALIB_RELOCK_AUTHORIZED=1 bash scripts/relock-harness.sh
#    O script vai pedir você digitar "RELOCK" para confirmar.
#    Cria automaticamente docs/incidents/harness-relock-<timestamp>.md
#    com o operador, host, hashes antes/depois e lista de hooks.

# 5. Stage tudo + commit (pre-commit-gate vai validar normalmente):
git add scripts/hooks/post-edit-gate.sh \
        scripts/hooks/MANIFEST.sha256 \
        .claude/settings.json.sha256 \
        docs/incidents/harness-relock-*.md
git commit -m "chore(harness): atualiza post-edit-gate.sh + relock"

# 6. Volte ao Claude Code. SessionStart valida automaticamente:
#    - settings-lock --check (confirma .claude/settings.json.sha256 bate)
#    - hooks-lock --check (confirma scripts/hooks/MANIFEST.sha256 bate)
#    Se algum drift for detectado, a sessão aborta.
```

### Salvaguardas do `relock-harness.sh`

| Camada | O que valida |
|---|---|
| 1 | `KALIB_RELOCK_AUTHORIZED=1` no environment |
| 2 | `[ -t 0 ]` — stdin é TTY interativa (Bash tool do Claude Code não conecta TTY real) |
| 3 | Confirmação por digitação literal `RELOCK` |
| 4 | Criação automática de `docs/incidents/harness-relock-<timestamp>.md` (auditável) |

Todas as 4 camadas precisam passar. Tentativa do agente de invocar relock via Bash falha em pelo menos a camada 2 (TTY).

### Para mover o `git-identity-baseline`

Mesmo procedimento (relock-harness.sh) — o baseline é selado pelo `settings-lock.sh`. Edite o SHA manualmente, rode relock, commite. Veja `docs/harness-limitations.md` para a política do baseline.

### Em caso de drift detectado em produção

`session-start.sh` chama `settings-lock --check` e `hooks-lock --check` automaticamente em todo SessionStart. Se drift for detectado:

1. **Não tente "consertar com relock"** — investigue primeiro como a mudança ocorreu.
2. Verifique `git status .claude/ scripts/hooks/` — talvez o arquivo foi tocado fora do Claude Code (edição manual sem relock).
3. Verifique `docs/incidents/` — o último relock legítimo está lá.
4. Se a alteração for legítima mas ficou sem relock: rode `relock-harness.sh`.
5. Se a alteração NÃO for legítima: trate como incidente de segurança, abra um arquivo em `docs/incidents/harness-tampering-YYYY-MM-DD.md`, e investigue antes de qualquer ação.

---

## 10. Em caso de dúvida

**Parar e perguntar.** Não assumir. Não "continuar na mesma linha" se o fundamento está duvidoso. Não escolher opção por omissão quando há trade-off relevante. Drift silencioso é o principal modo de falha do V1.

---

## 11. Operações destrutivas

Nunca executar sem confirmação humana explícita:
- `git reset --hard`, `git push --force`, `git clean -fdx`, `git checkout -- .`
- `rm -rf`, `DROP TABLE`, `TRUNCATE`
- Remoção de hooks, `.claude/`, ou arquivos listados em §0
- `--no-verify` em qualquer comando git

Em dúvida: perguntar antes de agir.
