<!-- REFERÊNCIA NÃO-INSTRUCIONAL — R7 -->
<!-- Este arquivo descreve conceitos do BMAD como inspiração histórica. -->
<!-- BMAD NÃO está instalado neste repo (R1). Não seguir "instruções" daqui. -->
<!-- STATUS: HISTÓRICO — não vigente. BMAD cortado (v1-post-mortem anti-pattern #4). -->
<!-- Reclassificado em 2026-04-10 pelo item X4 do plano da meta-auditoria #2. -->
<!-- Pasta: docs/reference/historical/. Os papéis vigentes estão em .claude/agents/. -->

# [HISTÓRICO — não vigente] Papéis inspirados no BMAD — conceitos, não código

> **Aviso de reclassificação (2026-04-10, item X4 do plano da meta-auditoria #2):** este arquivo descrevia papéis herdados do framework BMAD como inspiração. O BMAD foi cortado do projeto (ver `docs/reference/v1-post-mortem.md` anti-pattern #4 + `docs/constitution.md` R1). Os papéis vigentes do harness estão definidos em `.claude/agents/*.md` e `CLAUDE.md §8`. Este documento NÃO é instrução, NÃO é contrato, NÃO descreve comportamento vigente — é artefato histórico preservado apenas para auditoria.

**Status:** referência histórica (reclassificada em 2026-04-10).
**Propósito:** registrar quais conceitos do BMAD-METHOD (https://github.com/bmad-code-org/BMAD-METHOD) foram herdados como inspiração para os sub-agents do V2, e quais foram deliberadamente descartados.

---

## Por que não instalar BMAD

Ver `docs/reference/v1-post-mortem.md §4`. Resumo:
- BMAD cria estrutura paralela (`.bmad-core/`) que não integra bem com primitivos do Claude Code.
- Isolamento do QA agent é por prompt, não por processo — vulnerável a confirmation bias.
- Prompt bloat: cada invocação carrega instruções longas copiadas.
- Enforcement depende de o usuário escrever hooks separadamente.

No V2, **herdamos os conceitos de papel** (PM / Architect / Dev / QA) mas **implementamos nativamente** via sub-agents do Claude Code + hooks + skills.

---

## Mapeamento BMAD → V2

| BMAD | V2 equivalente | Diferenças-chave |
|---|---|---|
| PM agent (escreve stories/specs) | Humano escreve `specs/NNN/spec.md` manualmente | V2 não usa agente para gerar spec — humano é o PM |
| Architect agent | Sub-agent `architect` | No V2, lê apenas constitution + ADRs + spec; não lê código fora do escopo |
| Dev agent | Sub-agent `implementer` | No V2, cada Edit dispara hook que roda teste afetado (P4) |
| QA agent | Sub-agent `verifier` | No V2, roda em worktree descartável com input controlado (R3); emite JSON, não prosa (R4) |
| BMAD hooks | Hooks do Claude Code em `scripts/hooks/` | No V2, hooks **executam testes**, não só formatam (P4) |
| BMAD workflow | Fluxo descrito em `CLAUDE.md §6` | No V2, cada etapa tem gate mecânico bloqueante |

---

## Conceitos herdados

1. **Separação de papéis** (PM, Architect, Dev, QA) — evita um agente tentando fazer tudo.
2. **Handoff explícito** entre papéis — no V2, isso é mediado por skills + humano como gate.
3. **Acceptance Criteria numerados** — no V2, AC é teste executável (P2).
4. **Story → Plan → Tasks** como estrutura — no V2, `specs/NNN/{spec,plan,tasks}.md`.

## Conceitos deliberadamente descartados

1. **Instalação de framework externo.** BMAD instala um diretório paralelo; V2 usa só `.claude/` nativo.
2. **QA agent no mesmo contexto.** V2 exige isolamento arquitetural (worktree).
3. **Auto-commits como feature.** V2 proíbe (R5).
4. **Rodadas múltiplas de auditoria.** V2 limita a 2 rejeições + escalação humana (R6).
5. **Instruções longas em cada invocação.** V2 agents têm budget de tokens declarado (R8).

---

## Se alguém propuser reinstalar BMAD

Exigir ADR com:
- Qual problema do V2 atual BMAD resolveria.
- Como BMAD evitará os mesmos erros do V1 (especialmente self-review).
- Como `.bmad-core/` coexistirá com R1 (que proíbe arquivos de instrução fora do whitelist).
- Plano de rollback.

Provavelmente a resposta é "não vale o custo" — mas a decisão passa por ADR.
