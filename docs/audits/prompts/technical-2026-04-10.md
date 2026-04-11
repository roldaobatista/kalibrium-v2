# Prompt de Auditoria Externa — Harness Kalibrium V2

**Propósito:** este prompt deve ser colado em **sessões independentes** de LLMs diferentes para obter avaliações cruzadas do harness. Cada LLM auditora deve ter acesso ao filesystem do repositório (via ferramentas de código nativas do agente).

**Auditoras planejadas:**
1. Claude Opus 4.6 (1M context) — em **nova sessão** isolada, sem contexto desta
2. Codex / GPT-5 Codex
3. Gemini 2.5/3 Pro

---

## PROMPT (copie tudo abaixo desta linha e cole na LLM auditora)

---

Você é um engenheiro de software sênior conduzindo uma **auditoria crítica e independente** de um harness de desenvolvimento 100% por agentes de IA. Sua função é atacar, não defender.

# Regras da auditoria

1. **Você NÃO é o autor.** Outro modelo escreveu este harness. Sua função é encontrar falhas, não elogiar.
2. **Assuma bias cognitivo do autor.** Questione cada escolha. "Parece bem pensado" não é análise — é rendição.
3. **Priorize falhas silenciosas** (que passam sem erro aparente) sobre falhas óbvias. Silêncios são mais perigosos que explosões.
4. **Tome posição.** Não escreva "depende do contexto" ou "é uma escolha válida". Escolha um lado.
5. **Zero elogios genéricos.** Se algo é bom, explique concretamente por que funciona. Se é ruim, explique concretamente o que quebra.
6. **Busque ativamente:** prompt injection, race conditions, confirmation bias embutido, enforcement teatral, regras inaplicáveis, falsos negativos, drift silencioso, correlated failures.
7. **Compare com o estado da arte** em agentic coding: BMAD-METHOD, Devin, Aider batch mode, Cursor Agent, Sweep, GitHub Copilot Workspace, claude-code-spec-workflow, GitHub Spec Kit, claude-sdd-toolkit.
8. **Não commite nenhum arquivo ao git.** Apenas crie o arquivo de auditoria no filesystem. O humano decide o que commitar.

# Contexto essencial

**Projeto:** Kalibrium V2 — SaaS multi-tenant de calibração/metrologia para laboratórios brasileiros.

**Equipe real:**
- 1 humano (@roldaobatista) — **Product Manager, NÃO desenvolvedor**
- 0 devs humanos
- 100% do código é escrito por agentes de IA (Claude Code)
- Nenhuma review técnica humana substantiva é possível

**Por que V2 existe:** V1 falhou com BMAD-METHOD. Os 10 anti-patterns observados estão em `docs/reference/v1-post-mortem.md`.

**Premissa central do V2:** enforcement por arquitetura, não por prompt. Hooks bash + schemas JSON + sub-agents isolados em vez de instruções textuais.

# Sua tarefa

Explore o repositório **diretamente pelo filesystem**. Pasta raiz: `C:\PROJETOS\saas\kalibrium-v2\` (use Read/Grep/Glob/Bash nativos do seu agente).

**Leitura obrigatória antes de responder** (não pule):
1. `CLAUDE.md` — fonte única de instruções operacionais
2. `docs/constitution.md` — P1-P9 (princípios) + R1-R12 (regras)
3. `docs/reference/v1-post-mortem.md` — anti-patterns do V1
4. `docs/incidents/pr-1-admin-merge.md` — por que o modelo virou "humano=PM"
5. `.claude/agents/*.md` — os 6 sub-agents (architect, ac-to-test, implementer, verifier, reviewer, guide-auditor)
6. `.claude/settings.json` — wire-up dos 12 hooks
7. `scripts/hooks/*.sh` — scripts de enforcement (os 12)
8. `docs/schemas/*.json` — verification e review schemas
9. `docs/audits/audit-initial-2026-04-10.md` — auditoria interna anterior (para calibrar contra ela)
10. `docs/guide-backlog.md` — o que ainda está aberto

Depois explore livremente: `docs/`, `scripts/`, `.claude/skills/`, `docs/templates/`, `docs/adr/`, `docs/glossary-domain.md`, etc.

# O que avaliar (obrigatório — não pule seções)

## A. Enforcement real vs teatral
Para **cada regra P1-P9 e R1-R12**, diga:
- Está enforçada por **arquitetura** (hook/schema/processo) ou apenas por **prompt** (instrução que o agente pode ignorar)?
- Em qual arquivo específico e trecho (`file:line`)?
- Quão fácil é contornar? (1=trivial, 5=requer comprometer o repo)

## B. Isolamento dos sub-agents (R3 + R11)
R3 e R11 afirmam que `verifier` e `reviewer` rodam em **contextos isolados** sem ver output um do outro.
- O isolamento é **real** ou **simulado por prompt**?
- `scripts/hooks/verifier-sandbox.sh` depende de `$CLAUDE_AGENT_NAME`. O que impede um agente de não setar essa variável ou setá-la errada?
- Qual a probabilidade de **correlated failure** (ambos erram no mesmo ponto) já que rodam no mesmo modelo base (Claude Sonnet)?
- O isolamento por worktree é descartável ou persistente entre invocações?

## C. Modelo operacional "humano = PM"
- É **viável** operar este harness sem um único humano técnico? Argumente.
- Há pontos no fluxo onde o humano **será forçado** a decidir sobre algo técnico mesmo com `/explain-slice` ativado?
- `/decide-stack` promete traduzir trade-offs de framework para linguagem de produto. Isso é possível ou ilusão?
- Qual o risco do humano aprovar algo que não entende?

## D. Vetores de ataque/drift NÃO cobertos
Aponte **pelo menos 5 cenários concretos** onde:
- A IA consegue introduzir bug sem o harness detectar
- O harness pode ser silenciosamente desabilitado sem disparar gate
- Prompt injection vindo de `docs/reference/`, `ideia.md`, ou dados de tenant escapa
- Regras se contradizem ou anulam
- Gates que validam processo mas não resultado

## E. Comparação com alternativas
- **BMAD-METHOD** foi rejeitado como framework instalado. A rejeição é justa ou exagerada?
- **Devin / Aider batch / Cursor Agent / Sweep / GitHub Copilot Workspace / claude-code-spec-workflow / Spec Kit** — alguma encaixaria melhor para o perfil humano=PM? Qual e por quê?
- O que o harness ganhou por **não** instalar BMAD? O que perdeu?

## F. Regras inaplicáveis ou contraditórias
- Há regra P ou R que é **inenforceable** na prática? Qual?
- Há par (Pi, Pj) ou (Ri, Rj) que se contradizem? Aponte.
- O que muda no harness se ADR-0001 escolher Laravel vs Next.js vs Rust? Os hooks genéricos realmente funcionam ou precisam de reescrita?
- Se stack escolhida for monorepo com múltiplos apps, algo quebra?

## G. Compliance brasileiro (domínio alto risco)
Kalibrium envolve: calibração ISO 17025, cálculo de incerteza GUM, emissão NF-e/NFS-e, regras ICMS por UF, REP-P (Portaria 671/2021), ICP-Brasil, LGPD, eSocial.

- Para estes domínios regulados, o harness garante qualidade suficiente **sem** consultor humano especialista?
- Quais módulos você **nunca** deixaria para IA sem revisão humana técnica + revisão especialista de domínio?
- O reviewer atual tem capacidade de detectar erros sutis em cálculo metrológico ou regras fiscais? Por quê?

## H. 5 maiores ameaças de falha em produção
Liste os 5 riscos mais graves ordenados por **(probabilidade × impacto)**. Use este formato:

```markdown
### Ameaça #N: <título curto>
**Probabilidade:** alta | média | baixa
**Impacto:** alto | médio | baixo
**Descrição:** <1 parágrafo>
**Cenário concreto:** <como acontece na prática>
**Mitigação proposta:** <ação específica, não "melhorar documentação">
```

## I. Veredito binário

Responda 1 palavra cada, depois 1 parágrafo justificando:
- O harness é **viável** para o objetivo declarado? (sim/não)
- É **seguro** iniciar o Dia 1 (primeiro slice de produto) com esta estrutura? (sim/não/com-condições)
- Há mudanças **bloqueantes** antes de iniciar? (sim/não)

Se sim para a terceira, liste-as numeradas em ordem de criticidade (até 5 itens).

## J. 10 sugestões acionáveis (não genéricas)

As 10 mudanças mais impactantes que você faria, ordenadas por **impacto × facilidade**. Formato:

```markdown
1. **[esforço: baixo|médio|alto] <título curto>**
   - Por quê: <1 frase justificando o impacto>
   - Como: <ação concreta citando arquivo + mudança específica>
```

Não sugira "adicionar mais testes", "melhorar documentação", "considerar code review" como itens soltos. Seja específico.

# Onde salvar sua auditoria

Crie um arquivo novo em:

```
docs/audits/external/audit-<SEU-MODELO>-2026-04-10.md
```

Onde `<SEU-MODELO>` é o slug do seu modelo:

| Você é | Nome do arquivo |
|---|---|
| Claude Opus 4.6 (1M context) em nova sessão | `audit-claude-opus-4-6-2026-04-10.md` |
| GPT-5 Codex | `audit-codex-2026-04-10.md` |
| Gemini 2.5 Pro / 3 | `audit-gemini-2026-04-10.md` |
| Outro modelo | `audit-<seu-slug>-2026-04-10.md` |

# Estrutura obrigatória do arquivo

```markdown
# Auditoria externa — <seu modelo exato>

**Data:** 2026-04-10
**Auditor:** <modelo + versão + context window>
**Escopo:** harness Kalibrium V2 (commit atual na branch main)
**Duração aproximada da auditoria:** <minutos>

---

## A. Enforcement real vs teatral

[sua análise — uma linha por regra, formato "Pi/Ri: real|teatral — onde — facilidade de contornar (1-5)"]

## B. Isolamento dos sub-agents

[sua análise]

## C. Modelo operacional humano=PM

[sua análise]

## D. Vetores de ataque/drift não cobertos

1. ...
2. ...
3. ...
4. ...
5. ...

## E. Comparação com alternativas

[sua análise — especificamente: BMAD justa/injusta + qual alternativa encaixaria melhor]

## F. Regras inaplicáveis ou contraditórias

[sua análise]

## G. Compliance brasileiro

[sua análise]

## H. 5 maiores ameaças

### Ameaça #1: ...
### Ameaça #2: ...
### Ameaça #3: ...
### Ameaça #4: ...
### Ameaça #5: ...

## I. Veredito binário

- **Viável?** sim/não — <1 parágrafo>
- **Seguro iniciar Dia 1?** sim/não/com-condições — <1 parágrafo>
- **Mudanças bloqueantes?** sim/não — <1 parágrafo>

[se sim, listar 1-5 mudanças bloqueantes numeradas]

## J. 10 sugestões acionáveis

1. ...
2. ...
3. ...
4. ...
5. ...
6. ...
7. ...
8. ...
9. ...
10. ...

---

## Comentário livre (opcional)

[qualquer coisa que não encaixou nas seções acima]

---

## Declaração de independência

Esta auditoria foi conduzida **sem acesso** a:
- Outras auditorias externas (se houver) em `docs/audits/external/`
- A conversa que gerou o harness original
- Opiniões de outros modelos

Li apenas os arquivos do repositório conforme listados em "Leitura obrigatória" do prompt e os que explorei adicionalmente.
```

# O que você NÃO deve fazer

- ❌ Elogiar "estrutura organizada", "documentação extensa", "boas práticas" sem evidência concreta
- ❌ Repetir o que o CLAUDE.md ou constitution.md já afirma, a menos que seja para criticar
- ❌ Ser diplomático. Seja honesto, direto, específico.
- ❌ Sugerir "adicionar mais documentação" como resposta genérica
- ❌ Usar "é subjetivo", "depende do contexto", "cada caso é um caso"
- ❌ Concluir com "no geral, um bom harness" se achou 5 problemas graves
- ❌ Commitar o arquivo ao git. Apenas criar no filesystem.
- ❌ Ler auditorias de outros modelos em `docs/audits/external/` antes de terminar a sua (contamina a independência)

# Confirmação final

Quando terminar, responda apenas:

```
auditoria salva em docs/audits/external/audit-<seu-modelo>-2026-04-10.md
veredito resumido: <viável? | seguro Dia 1? | bloqueantes?>
3 achados mais críticos: <item1>, <item2>, <item3>
```

Sem mais nada além disso. O relatório completo fica no arquivo.

---

## FIM DO PROMPT
