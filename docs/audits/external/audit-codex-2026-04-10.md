# Auditoria externa — GPT-5 Codex

**Data:** 2026-04-10
**Auditor:** GPT-5 Codex (Codex desktop; context window não exposta)
**Escopo:** harness Kalibrium V2 (commit `cf9efdf` na branch `main`)
**Duração aproximada da auditoria:** 75 minutos

---

## A. Enforcement real vs teatral

P1: teatral — `docs/constitution.md:27` declara gate objetivo; `scripts/hooks/post-edit-gate.sh:9-10` admite esqueleto stack-agnóstico e `scripts/hooks/post-edit-gate.sh:118` aceita arquivo sem teste mapeado; facilidade de contornar: 2.

P2: teatral — `docs/constitution.md:31-33` promete AC-test red antes de código; `scripts/hooks/pre-commit-gate.sh:126` só emite WARN quando há código sem teste, e `scripts/verify-slice.sh:149-175` extrai ACs mas nenhum validador compara `ac-list.json` contra `verification.json`; facilidade de contornar: 1.

P3: teatral — `docs/constitution.md:35-37` promete worktree descartável; `scripts/hooks/verifier-sandbox.sh:17-22` depende de `CLAUDE_AGENT_NAME` e sai 0 se a variável não for `verifier|reviewer`; `scripts/verify-slice.sh:207-220` apenas imprime instrução de spawn; facilidade de contornar: 1.

P4: teatral — `docs/constitution.md:39-41` exige que hooks executem testes; `scripts/hooks/post-edit-gate.sh:73-93` mapeia por convenção estreita e `scripts/hooks/post-edit-gate.sh:118` aprova sem teste quando não acha candidato; facilidade de contornar: 2.

P5: teatral — `docs/constitution.md:43-45` diz "nada além"; `scripts/hooks/session-start.sh:37-65` só checa arquivos/pastas proibidas na raiz, enquanto `scripts/hooks/forbidden-files-scan.sh:61-63` trata instruções órfãs como WARN; facilidade de contornar: 2.

P6: teatral — `docs/constitution.md:47-49` exige commit atômico e autor humano-identificável; `scripts/hooks/pre-commit-gate.sh:36-46` só bloqueia `auto-*` e alguns `noreply`, e o histórico contém `smoke-test-user <smoke@test.local>`; facilidade de contornar: 1.

P7: teatral — `docs/constitution.md:51-53` assume cultura + `CLAUDE.md`; `scripts/hooks/user-prompt-submit.sh:1-6` injeta lembrete, mas nenhum gate bloqueia uma afirmação falsa de "pronto"; facilidade de contornar: 1.

P8: teatral — `docs/constitution.md:55-57` promete pirâmide de testes; `scripts/hooks/pre-push-gate.sh:47-52` diz que a testsuite de domínio ainda não está configurada e `docs/guide-backlog.md:18-22` deixa CI externo em aberto; facilidade de contornar: 1.

P9: teatral — `docs/constitution.md:59-61` proíbe bypass; `scripts/hooks/pre-commit-gate.sh:20-25` e `scripts/hooks/block-project-init.sh:41-43` bloqueiam flags óbvias, mas `scripts/hooks/stop-gate.sh:27-49` só avisa sobre mudança em settings e termina `exit 0`; facilidade de contornar: 2.

R1: teatral — `docs/constitution.md:86-89` proíbe múltiplas fontes; `scripts/hooks/session-start.sh:37-65` não faz varredura recursiva nem bloqueia padrões `^You are` fora da whitelist, e `scripts/hooks/forbidden-files-scan.sh:61-63` só avisa; facilidade de contornar: 2.

R2: teatral — `docs/constitution.md:91-93` depende de verificação manual e auditoria posterior; `scripts/guide-check.sh:56` procura apenas `auto-|[bot]|noreply`, então não detecta autores como `smoke-test-user`; facilidade de contornar: 1.

R3: teatral — `docs/constitution.md:95-97` afirma `isolation: worktree`; `scripts/verify-slice.sh:141-147` só recria `verification-input/` no repo corrente e `scripts/hooks/verifier-sandbox.sh:20-22` desliga se o nome do agente não vier certo; facilidade de contornar: 1.

R4: real — `docs/constitution.md:99-120` exige JSON; `docs/schemas/verification.schema.json:7-18` fixa campos e enums, e `scripts/validate-verification.sh:38-64` usa `jsonschema` quando disponível; facilidade de contornar: 3, emitindo JSON válido mas enganoso.

R5: teatral — `docs/constitution.md:122-133` define autor humano-identificável; `scripts/hooks/pre-commit-gate.sh:36-46` não tem allowlist de identidades humanas e o histórico mostra commits `smoke-test-user`; facilidade de contornar: 1.

R6: teatral — `docs/constitution.md:135-137` promete travar após 2 rejeições consecutivas; `scripts/verify-slice.sh:60-78` conta rejeições históricas por grep e `scripts/verify-slice.sh:105-111` apenas imprime bloqueio, sem impedir tecnicamente nova edição; facilidade de contornar: 2.

R7: teatral — `docs/constitution.md:139-141` trata `docs/reference/**` como dados; `scripts/hooks/edit-scope-check.sh:19-21` bloqueia edição dessas referências, mas nada impede um agente de obedecer prompt injection lida de lá; facilidade de contornar: 1.

R8: teatral — `docs/constitution.md:143-145` promete telemetria de tokens; `.claude/agents/*.md` declara budgets, mas `scripts/record-tokens.sh:2-18` é manual e `scripts/hooks/collect-telemetry.sh:27-30` só grava evento de commit; facilidade de contornar: 1.

R9: teatral — `docs/constitution.md:147-149` promete incidente em bypass; `scripts/hooks/pre-commit-gate.sh:20-25` pega flags óbvias, mas `scripts/hooks/stop-gate.sh:27-49` não bloqueia settings alterado e o bypass por admin está documentado em `docs/incidents/pr-1-admin-merge.md:37-67`; facilidade de contornar: 2.

R10: real — `docs/constitution.md:151-160` bloqueia init sem ADR; `scripts/hooks/block-project-init.sh:10-33` bloqueia comandos listados se `docs/adr/0001-stack-choice.md` não existir; facilidade de contornar: 2, porque basta criar arquivo vazio/proposed ou usar comando não listado.

R11: teatral — `docs/constitution.md:162-179` promete dual-verifier isolado; `scripts/review-slice.sh:130-137` só valida que o verifier aprovou antes de preparar review, e `scripts/review-slice.sh:166-176` apenas imprime instrução de spawn; facilidade de contornar: 1.

R12: teatral — `docs/constitution.md:181-200` exige linguagem de produto; `scripts/explain-slice.sh:1-6` declara stub e `docs/guide-backlog.md:36-40` assume que a tradução automática ainda está aberta; facilidade de contornar: 1.

## B. Isolamento dos sub-agents

O isolamento é simulado por prompt + convenção de ferramenta, não por arquitetura robusta. O hook crítico é `scripts/hooks/verifier-sandbox.sh`, que lê `CLAUDE_AGENT_NAME` em `scripts/hooks/verifier-sandbox.sh:17` e só aplica bloqueio para `verifier|reviewer` em `scripts/hooks/verifier-sandbox.sh:20-22`. Se a variável não for setada, ou se vier com outro valor, o hook retorna sucesso e a sandbox não existe.

Nada no repo prova que `isolation: worktree` é executado. `scripts/verify-slice.sh:207-220` e `scripts/review-slice.sh:166-176` imprimem instruções para o agente principal spawnar outro agente; não há `git worktree add`, path temporário, ou remoção de worktree no script. Os diretórios `verification-input/` e `review-input/` são globais e persistentes no repo corrente até o próximo `rm -rf` em `scripts/verify-slice.sh:141-142` ou `scripts/review-slice.sh:140-141`.

O reviewer não recebe `verification.json` no input, mas sabe por design que só roda depois de `verdict=approved` (`.claude/agents/reviewer.md:3` e `scripts/review-slice.sh:130-137`). Isso reduz, mas não remove, confirmation bias: o reviewer começa a tarefa sabendo que outro Sonnet já aprovou.

A probabilidade de correlated failure é alta. `verifier` e `reviewer` usam `model: sonnet` (`.claude/agents/verifier.md:4`, `.claude/agents/reviewer.md:4`) e compartilham o mesmo conjunto de crenças do modelo base. Inputs diferentes ajudam contra vazamento narrativo, mas não criam diversidade real de raciocínio, nem oráculo de domínio, nem ferramenta estática especializada.

O isolamento por worktree, no estado atual, é uma promessa operacional. Se o Agent tool do Claude Code realmente fornecer worktree isolada fora deste repo, isso ainda não está testado por script local e não aparece como invariável no filesystem auditado.

## C. Modelo operacional humano=PM

Não é viável operar este harness sem nenhum humano técnico para o objetivo declarado. O projeto envolve escolhas de stack, tenancy, segurança, cálculo metrológico, fiscal, assinatura digital e privacidade. `CLAUDE.md:63-71` diz corretamente que o humano não revisa código nem decide escalações técnicas sem tradução, mas isso cria um buraco: alguém ainda precisa ser responsável por reconhecer quando a recomendação técnica é ruim.

O humano será forçado a decidir tecnicamente mesmo com `/explain-slice`. Exemplos: aceitar ADR-0001, resolver R6 quando verifier/reviewer discordarem, aprovar trade-off de multi-tenancy, decidir sobre retenção LGPD, escolher estratégia de NF-e/NFS-e por UF, e aceitar/rejeitar uma mitigação de cálculo GUM. Tradução ajuda na experiência de decisão; não transfere competência técnica.

`/decide-stack` promete traduzir trade-offs para linguagem de produto (`.claude/skills/decide-stack.md:86-100`). Isso é útil para reduzir jargão, mas vira ilusão quando a diferença entre Laravel, Next.js e Rust envolve manutenção, segurança, ecossistema fiscal brasileiro, filas, PDF/A, assinatura ICP-Brasil, offline/PWA e CI. Uma recomendação forte pode ser correta, mas o PM não tem como auditar a premissa.

O risco do humano aprovar algo que não entende é alto e já aconteceu em miniatura: `docs/incidents/pr-1-admin-merge.md:31` reconhece que review humana seria teatro, e `docs/incidents/pr-1-admin-merge.md:67` mantém owner bypass como última camada. O modelo reduz o teatro explícito, mas ainda deixa o PM como aprovador final de decisões cujo erro pode ser regulatório.

## D. Vetores de ataque/drift não cobertos

1. `verification.json` pode omitir ACs e ainda passar schema. `docs/schemas/verification.schema.json:25-43` exige apenas `minItems: 1`; não há comparação com `verification-input/ac-list.json` gerado em `scripts/verify-slice.sh:149-175`.

2. O output de teste do verifier é texto manual. `scripts/verify-slice.sh:191-199` cria placeholder e pede ao implementer substituir; o verifier valida texto, não executa testes em ambiente limpo.

3. Código sem teste mapeado passa no edit gate. `scripts/hooks/post-edit-gate.sh:118` diz "ok se for template/config/doc" sem saber se é produto; `scripts/hooks/pre-commit-gate.sh:126` só avisa.

4. Sandbox desliga silenciosamente se `CLAUDE_AGENT_NAME` não vier certo. `scripts/hooks/verifier-sandbox.sh:20-22` sai 0 para qualquer agente não reconhecido.

5. R10 cai com arquivo vazio. `scripts/hooks/block-project-init.sh:32-33` só checa existência de `docs/adr/0001-stack-choice.md`, não status `accepted` nem opção marcada.

6. MCP drift não está implementado. `.claude/skills/mcp-check.md:27` manda rodar `scripts/hooks/mcp-check.sh`, mas esse arquivo não existe no workspace auditado.

7. `review.schema.json` permite `approved` com `quality_checks[].status="fail"` e findings `major`. O schema só proíbe blocker em `docs/schemas/review.schema.json:107-118`; as regras mais fortes existem só no prompt do reviewer em `.claude/agents/reviewer.md:75-84`.

8. Alterar `.claude/settings.json` vira warning no Stop, não bloqueio. `scripts/hooks/stop-gate.sh:27-49` termina `exit 0`.

9. Autor fake passa. `scripts/hooks/pre-commit-gate.sh:36-46` bloqueia padrões específicos; o histórico já contém `smoke-test-user <smoke@test.local>`.

10. Prompt injection de `docs/reference/**` é mitigado por cabeçalho, mas não sanitizado. `scripts/hooks/edit-scope-check.sh:19-21` bloqueia edição; não há hook que envolve conteúdo lido com tratamento de dados nem detector de instrução imperativa nesses arquivos durante leitura.

11. Monorepo ou app fora de `src/` quebra o mapeamento de teste. `scripts/hooks/post-edit-gate.sh:74-93` cobre `src/*`, `app/*.php` e `src/*.py`, mas não `packages/*`, `apps/*`, `frontend/*`, `backend/*` ou Next.js `app/`.

12. CI está ausente. `docs/guide-backlog.md:18-22` deixa B-007 aberto e bloqueado por ADR-0001.

## E. Comparação com alternativas

A rejeição de instalar BMAD é justa, mas a conclusão foi exagerada. Justa porque V1 sofreu com `.bmad-core/`, múltiplas fontes de instrução e QA no mesmo contexto (`docs/reference/v1-post-mortem.md:38-42`). Exagerada porque o V2 herdou a coreografia de papéis sem importar maturidade operacional equivalente: ainda faltam execução real em sandbox, CI e validação de cobertura AC. Eu não reinstalaria BMAD no repo, mas roubaria seus artefatos de lifecycle como contrato verificável, não como prompt.

Devin é mais adequado que o harness atual para tarefas autônomas longas, mas não substitui especialista de domínio. A documentação oficial de Devin orienta usar o agente como parte de um fluxo de equipe existente, e não como oráculo regulatório. Para o perfil humano=PM, Devin ajudaria em execução, issues e PRs, mas o mesmo problema central permanece: quem valida fiscal/metrologia?

Aider batch mode encaixa melhor para automações pequenas e repetíveis. Aider tem comandos de `/run` e `/test` documentados e um modo de scripting, o que favorece loops com teste real. Ele perderia para o V2 em modelagem multi-agente e PM-friendly, mas ganharia na honestidade: comando falha, loop para.

Cursor Agent/Background Agents e GitHub Copilot Cloud Agent encaixariam melhor que o V2 para um PM acompanhar status e assumir/pausar trabalho em UI. O GitHub Copilot atual também já documenta cloud agent, hooks, custom agents, MCP e auditoria em Docs oficiais. Para este repo, eu preferiria GitHub Copilot Cloud Agent + Actions para PR/CI se o objetivo for menos harness caseiro e mais guardrail hospedado.

Sweep parece menos encaixado hoje: historicamente útil para issue-to-PR, mas não é uma resposta forte para domínio regulado nem para specs executáveis profundas. Copilot Workspace foi relevante como experimento de tarefas repo-wide, mas o próprio manual o descreve como experimento GitHub Next; eu trataria como antecedente conceitual, não base operacional nova.

GitHub Spec Kit e `claude-code-spec-workflow` encaixam melhor no problema de "spec antes de código" do que BMAD instalado. Ambos empurram especificação como artefato central; o V2 já faz isso, mas precisa transformar spec em checks mecânicos.

`claude-sdd-toolkit` é pequeno, mas o objetivo declarado é apoiar Spec Driven Development no Claude Code. Eu o avaliaria como fonte de scripts/contratos, não como dependência cega. O harness atual perdeu por não instalar nada: está reinventando partes fundamentais e errando no enforcement.

Fontes externas consultadas para esta seção: [BMAD-METHOD](https://github.com/bmad-code-org/BMAD-METHOD), [Devin docs](https://docs.devin.ai/), [Aider scripting](https://aider.chat/docs/scripting.html), [Aider commands](https://aider.chat/docs/usage/commands.html), [Cursor Background Agent](https://docs.cursor.com/ja/background-agent), [GitHub Copilot docs](https://docs.github.com/en/copilot), [GitHub Spec Kit](https://github.com/github/spec-kit), [Copilot Workspace manual](https://github.com/githubnext/copilot-workspace-user-manual), [Sweep](https://github.com/sweepai/sweep), [claude-code-spec-workflow](https://github.com/Pimzino/claude-code-spec-workflow), [claude-sdd-toolkit](https://github.com/tylerburleigh/claude-sdd-toolkit).

## F. Regras inaplicáveis ou contraditórias

P7 é inenforceable no harness atual: nenhuma ferramenta consegue bloquear uma frase falsa no chat. R12 também é inenforceable enquanto `scripts/explain-slice.sh:1-6` for stub e `docs/guide-backlog.md:36-40` deixar a tradução automática em aberto. R2 é quase inenforceable localmente: não há detector confiável de Cursor/Copilot/Aider se eles escrevem com o mesmo autor git.

Há contradição entre R11 e o fluxo real: o reviewer não vê `verification.json`, mas só é chamado depois que `scripts/review-slice.sh:130-137` confirma `verdict=approved`. Isso vaza o fato da aprovação e cria prior positivo. Não é tão ruim quanto ler o output inteiro, mas a independência não é pura.

Há contradição entre R12 e P7. P7 exige comando + output + exit code; R12 proíbe vocabulário técnico ao PM. O harness precisa de dois canais formais: evidência técnica machine-readable e resumo PM-readable. Hoje isso depende de bom senso do agente.

Há contradição operacional em R10: "Stack só via ADR" implica decisão aceita, mas `scripts/hooks/block-project-init.sh:32-33` libera com mera existência do arquivo. Um ADR `proposed`, vazio ou mal preenchido destrava o projeto.

Laravel vs Next.js vs Rust muda tudo. Laravel exigiria Pest/PHPStan/Pint, migrations e filas; Next.js exigiria Vitest/Playwright/turborepo ou app router; Rust exigiria `cargo test`, `clippy`, feature flags e possivelmente frontend separado. `post-edit-gate.sh` formata Rust em `scripts/hooks/post-edit-gate.sh:39-40`, mas não roda `cargo test`. Next.js fora de `src/` escapa do teste afetado. Laravel pode até mapear `app/` para `tests/Unit/`, mas feature tests, HTTP, jobs e migrations não entram.

Monorepo quebra a suposição de raiz única. `pre-commit-gate.sh:90-99` olha `package.json` e `tsconfig.json` na raiz; `post-edit-gate.sh:80-93` assume `tests/` na raiz; `review-slice.sh:150-151` usa `git diff main...HEAD`, o que faz pouco sentido quando a branch atual é `main` ou quando há múltiplos apps com bases diferentes.

## G. Compliance brasileiro

O harness não garante qualidade suficiente sem consultor humano especialista. O próprio glossário reconhece domínios de alto risco: GUM/ISO 17025 em `docs/glossary-domain.md:16-29`, REP-P/eSocial em `docs/glossary-domain.md:58-65`, NF-e/NFS-e/ICMS em `docs/glossary-domain.md:76-80`, ICP-Brasil em `docs/glossary-domain.md:91-93` e LGPD em `docs/glossary-domain.md:102-115`.

Eu nunca deixaria apenas para IA sem revisão técnica + especialista: cálculo de incerteza GUM; certificado de calibração ISO 17025; cadeia de rastreabilidade; emissão/cancelamento/carta de correção de NF-e/NFS-e; regras ICMS por UF e reforma tributária; REP-P/AFD/ACJEF/eSocial; assinatura ICP-Brasil, PDF/A e carimbo de tempo; isolamento multi-tenant; retenção/exclusão/portabilidade LGPD; logs imutáveis e acesso a dado sensível.

O reviewer atual não tem capacidade de detectar erro sutil nesses domínios. Ele avalia categorias genéricas como duplication, complexity, naming, security e glossary (`.claude/agents/reviewer.md:56-67`). Isso detecta "WorkOrder" vs "OS" e talvez um secret hardcoded; não detecta fórmula GUM incorreta, arredondamento de incerteza, CST/CSOSN errado, hash chain REP-P inválida, ou retenção LGPD ilegal. Sem fixtures oficiais, golden files, simuladores fiscais, biblioteca normativa validada e revisão especialista, o reviewer é um linter sem domínio.

## H. 5 maiores ameaças

### Ameaça #1: Falso verde por validar artefato, não resultado
**Probabilidade:** alta
**Impacto:** alto
**Descrição:** O harness pode aprovar `verification.json` e `review.json` válidos enquanto comportamento real está errado ou sequer testado.
**Cenário concreto:** Implementer preenche `verification-input/test-results.txt` manualmente com output antigo; verifier lê o texto, emite `approved`; schema aceita porque campos existem.
**Mitigação proposta:** Em `scripts/verify-slice.sh`, executar os testes filtrados dentro do script, capturar stdout/stderr/exit code, gravar imutável no input e validar AC coverage contra `ac-list.json`.

### Ameaça #2: Isolamento falso dos verificadores
**Probabilidade:** alta
**Impacto:** alto
**Descrição:** R3/R11 dependem de variável de ambiente e promessa de Agent tool, não de sandbox local verificável.
**Cenário concreto:** `CLAUDE_AGENT_NAME` não é setado; verifier consegue ler `plan.md`, histórico, comentários do implementer e output do reviewer; confirmation bias volta.
**Mitigação proposta:** Criar worktree temporária real com `git worktree add`, copiar apenas o pacote permitido, setar identidade do agente no launcher controlado pelo script e falhar fechado quando metadata do agente estiver ausente.

### Ameaça #3: PM aprova decisão técnica sem entender risco
**Probabilidade:** alta
**Impacto:** alto
**Descrição:** R12 traduz, mas não cria competência técnica nem responsabilidade regulatória.
**Cenário concreto:** `/decide-stack` recomenda uma stack que acelera telas, mas dificulta NFS-e municipal, PDF/A assinado ou offline do técnico; PM aceita pelo texto "mais rápido e barato".
**Mitigação proposta:** Exigir parecer técnico externo para ADR-0001, multi-tenancy, fiscal, metrologia, identidade digital e LGPD antes de qualquer slice que toque esses módulos.

### Ameaça #4: Domínio regulado errado em produção
**Probabilidade:** média
**Impacto:** alto
**Descrição:** O reviewer genérico não detecta erros normativos sutis.
**Cenário concreto:** cálculo GUM arredonda incerteza fora do critério aceito; certificado ISO 17025 sai com rastreabilidade incompleta; cliente usa laudo inválido.
**Mitigação proposta:** Criar suíte de conformidade por domínio com casos dourados validados por especialista e bloquear merge de módulos regulados sem revisão especialista registrada.

### Ameaça #5: Drift silencioso do próprio harness
**Probabilidade:** média
**Impacto:** alto
**Descrição:** Hooks, MCPs, settings e identidade git podem mudar sem gate duro.
**Cenário concreto:** `.claude/settings.json` perde o PostToolUse; Stop só avisa; `/guide-check` roda dias depois; commits já entraram.
**Mitigação proposta:** Adicionar snapshot assinado de `.claude/settings.json`, validar no SessionStart e PreToolUse, implementar `/mcp-check` real e bloquear identidades git fora de allowlist.

## I. Veredito binário

- **Viável?** não — A direção é correta, mas o estado atual não sustenta a promessa "100% agentes sem humano técnico". Há enforcement real em R4 e parte de R10, mas as regras mais importantes contra falso positivo, isolamento, teste real, CI, R12 e domínio regulado são prompt, warning ou stub.

- **Seguro iniciar Dia 1?** com-condições — Não é seguro iniciar um slice de produto agora. Seria aceitável fazer apenas um Dia 1 de bootstrap: escrever `docs/mvp-scope.md`, decidir ADR-0001, operacionalizar gates reais da stack e instalar CI. Produto real antes disso é pedir falso verde.

- **Mudanças bloqueantes?** sim — Existem bloqueios antes do primeiro slice de produto, inclusive reconhecidos em `docs/guide-backlog.md:11-22` (B-001 e B-007).

1. Fazer `/decide-stack` produzir ADR-0001 aceita, mas exigir status `accepted` real no `block-project-init.sh`, não só existência de arquivo.
2. Operacionalizar `post-edit-gate.sh`, `pre-commit-gate.sh` e `pre-push-gate.sh` para a stack escolhida, falhando quando código de produto não tiver teste mapeado.
3. Substituir `test-results.txt` manual por execução automática e validar cobertura AC completa no `verification.json`.
4. Implementar worktree isolada real para verifier/reviewer e falhar fechado quando `CLAUDE_AGENT_NAME` ou metadata de isolamento não existir.
5. Configurar CI + GitHub Action que só aprova/mergeia PR com testes, verifier e reviewer aprovados; admin bypass não pode ser caminho normal.

## J. 10 sugestões acionáveis

1. **[esforço: baixo] Trocar R5 para allowlist de identidades**
   - Por quê: O histórico já contém autor falso e o gate atual não pega.
   - Como: Em `scripts/hooks/pre-commit-gate.sh:29-46` e `scripts/guide-check.sh:56`, validar `user.name/email` contra allowlist em `.claude/allowed-git-identities.txt` e bloquear `smoke-test-user`, `*@test.local`, `auto-*`, bots não permitidos e noreply sem coautoria.

2. **[esforço: baixo] Fazer código sem teste virar bloqueio, não WARN**
   - Por quê: P2/P4 são a defesa central contra falso verde.
   - Como: Em `scripts/hooks/pre-commit-gate.sh:122-126`, trocar `say "WARN"` por `die` para arquivos de produto; em `scripts/hooks/post-edit-gate.sh:118`, permitir sem teste só para allowlist explícita de docs/config/templates.

3. **[esforço: baixo] Implementar ou remover `/mcp-check`**
   - Por quê: A ADR-0002 promete allowlist rígida, mas a skill aponta para script inexistente.
   - Como: Criar `scripts/hooks/mcp-check.sh` ou corrigir `.claude/skills/mcp-check.md:27`; depois chamar o check em `scripts/hooks/session-start.sh` com falha dura para MCP ativo fora de `.claude/allowed-mcps.txt`.

4. **[esforço: baixo] Exigir ADR-0001 accepted em R10**
   - Por quê: Arquivo vazio/proposed destrava stack.
   - Como: Em `scripts/hooks/block-project-init.sh:32-33`, exigir `**Status:** accepted` e uma opção marcada; rejeitar ADR com placeholders.

5. **[esforço: médio] Capturar testes dentro de `/verify-slice`**
   - Por quê: `test-results.txt` manual é vetor de mentira acidental ou intencional.
   - Como: Substituir `scripts/verify-slice.sh:191-199` por execução stack-aware dos AC-tests, gravando comando, output e exit code; remover instrução "implementer substitua".

6. **[esforço: médio] Validar cobertura AC exata**
   - Por quê: `verification.json` pode omitir ACs.
   - Como: Em `scripts/validate-verification.sh`, ler `verification-input/ac-list.json` e garantir que o conjunto de `ac_checks[].ac` seja idêntico; rejeitar duplicatas e AC desconhecido.

7. **[esforço: médio] Tornar R11 schema-enforced**
   - Por quê: Regras de reviewer existem no prompt, não no schema.
   - Como: Em `docs/schemas/review.schema.json:107-118`, fazer `approved` exigir todos `quality_checks.status=pass`, zero blocker e menos de 3 major; em `scripts/validate-review.sh`, implementar o mesmo no fallback bash.

8. **[esforço: médio] Criar launcher real de verifier/reviewer**
   - Por quê: Hoje os scripts imprimem instruções; o isolamento não é comprovado.
   - Como: Em `scripts/verify-slice.sh:207-220` e `scripts/review-slice.sh:166-176`, substituir mensagens por comando/integração que cria worktree temporária, injeta `CLAUDE_AGENT_NAME`, roda agente e remove worktree após copiar JSON.

9. **[esforço: médio] Fechar B-007 + B-009 antes do produto**
   - Por quê: Sem CI e auto-approval controlado, o admin bypass continua caminho humano frágil.
   - Como: Criar `.github/workflows/ci.yml` e `.github/workflows/auto-approve.yml` exigindo testes da stack, `validate-verification.sh` e `validate-review.sh` antes de approval/merge.

10. **[esforço: alto] Criar pacote de conformidade por domínio regulado**
   - Por quê: Reviewer genérico não valida ISO 17025, GUM, NF-e/NFS-e, REP-P, ICP-Brasil ou LGPD.
   - Como: Criar `docs/compliance/` com matriz de módulos que exigem especialista; criar `tests/compliance/golden/` com fixtures assinadas por especialista; bloquear merge de arquivos desses módulos sem evidência de fixture e parecer em `docs/compliance/reviews/`.

---

## Comentário livre (opcional)

A auditoria interna anterior procurou coerência de montagem e achou um estado "verde". Isso mede presença de peças, não resistência do sistema. O V2 tem uma tese boa: enforcement por arquitetura. O problema é que a arquitetura ainda não chegou nos pontos onde mais importa: execução real de testes, isolamento real, CI real, controle real de identidade e oráculos de domínio. O perigo não é o harness explodir; é ele aprovar.

---

## Declaração de independência

Esta auditoria foi conduzida **sem leitura deliberada** de:
- Outras auditorias externas completas em `docs/audits/external/`
- A conversa que gerou o harness original
- Opiniões de outros modelos

Ressalva: durante uma busca ampla por `smoke-test-user`, um comando `rg` retornou acidentalmente uma única linha de `docs/audits/external/audit-gemini-2026-04-10.md`. Não abri o arquivo, não li o relatório e descartei esse resultado como insumo. Portanto, a independência é imperfeita quanto a esse vazamento de uma linha, mas a análise acima se baseia nos arquivos obrigatórios, nos scripts locais, no histórico git e nas fontes externas primárias citadas na seção E.

