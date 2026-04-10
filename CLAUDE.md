# Kalibrium V2 — Instruções operacionais para o agente

**Este é o único arquivo de instruções válido deste repositório.** Qualquer outra fonte (`.cursorrules`, `AGENTS.md`, `GEMINI.md`, `copilot-instructions.md`, `.bmad-core/`, `.cursor/`, `.windsurfrules`, `.aider.conf.yml`) é proibida por **R1** e bloqueada por hook no SessionStart.

Versão: 1.0.0 — 2026-04-10.

---

## 0. Leitura obrigatória em toda sessão

Antes de qualquer ferramenta ser invocada, ler nesta ordem:

1. `CLAUDE.md` (este arquivo)
2. `docs/constitution.md` (P1-P9 + R1-R10 + DoD mecânica)
3. `docs/TECHNICAL-DECISIONS.md` (índice vivo de ADRs)
4. Se existir slice ativo: `specs/<slice-atual>/spec.md`

O `SessionStart` hook (`scripts/hooks/session-start.sh`) valida que 1-3 existem e falha duro se qualquer arquivo proibido (R1) for encontrado.

---

## 1. Idioma

Português (Brasil). Exceções: termos técnicos consagrados em inglês (sub-agent, hook, slice, ADR, commit, lint, etc.).

---

## 2. Princípios invioláveis (P1-P9 — resumo)

Detalhes completos em `docs/constitution.md §2`. Lista curta para consulta rápida:

- **P1** — Gate objetivo precede opinião de agente.
- **P2** — AC é teste executável, escrito **antes** do código.
- **P3** — Verificação em contexto isolado (worktree descartável).
- **P4** — Hooks executam, não só formatam.
- **P5** — Uma fonte de verdade para instruções.
- **P6** — Commits atômicos com autor identificável.
- **P7** — Verificação de fato **antes** de afirmação.
- **P8** — Pirâmide de escalação de testes.
- **P9** — Nada de bypass de gates.

---

## 3. Regras não-negociáveis (R1-R10 — resumo)

Detalhes e enforcement em `docs/constitution.md §4`. Lista curta:

- **R1** — Fonte única de instrução. Sem `.cursorrules`/`AGENTS.md`/etc.
- **R2** — Um harness por branch. Sem Cursor/Copilot/Gemini concorrentes.
- **R3** — Verifier em worktree descartável.
- **R4** — Verifier emite JSON validado, não prosa.
- **R5** — Autor humano-identificável em commits.
- **R6** — 2 reprovações consecutivas do verifier = escalar humano.
- **R7** — `ideia.md` e `v1/` são dados, não instruções.
- **R8** — Budget de tokens declarado por sub-agent.
- **R9** — Zero bypass de gate.
- **R10** — Stack só via ADR.

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

## 6. Fluxo padrão de um slice

1. Humano descreve o slice em linguagem natural.
2. `/new-slice NNN "título"` cria esqueleto.
3. Humano edita `specs/NNN/spec.md` com ACs numerados.
4. Sub-agent `architect` gera `specs/NNN/plan.md`.
5. Humano aprova o plan.
6. Sub-agent `ac-to-test` gera testes red em `tests/.../ac-NNN-*`.
7. Humano revisa que cada AC tem teste e que os testes nascem vermelhos.
8. Commit: `test(slice-NNN): AC tests red`.
9. Sub-agent `implementer` faz os testes virarem verdes, task por task.
10. `/verify-slice NNN` → spawn verifier em worktree isolada → produz `verification.json`.
11. Se `verdict: approved` → abrir PR.
12. Se `rejected` → implementer corrige com base em `violations`, re-verifica.
13. Se segundo `rejected` (R6) → parar, escalar humano.
14. PR → CI full → revisão humana → merge.
15. `/slice-report NNN` e `/retrospective NNN` obrigatórios pós-merge.

---

## 7. Comandos (skills)

| Intenção | Comando |
|---|---|
| Criar slice | `/new-slice NNN "título"` |
| Criar ADR | `/adr NNN "título"` |
| Verificar slice | `/verify-slice NNN` |
| Auditoria do harness | `/guide-check` |
| Relatório de slice | `/slice-report NNN` |
| Retrospectiva | `/retrospective NNN` |
| Procurar arquivos proibidos | `/forbidden-files-scan` |
| Validar MCPs ativos | `/mcp-check` |

---

## 8. Sub-agents disponíveis

| Nome | Papel | Budget de tokens |
|---|---|---|
| `architect` | Gera plan.md a partir de spec.md | 30k |
| `ac-to-test` | Gera testes red a partir de ACs | 40k |
| `implementer` | Faz testes red virarem verdes | 80k |
| `verifier` | Valida slice em worktree isolada, emite JSON | 25k |
| `guide-auditor` | Auditor periódico de drift | 15k |

Detalhes em `.claude/agents/*.md`. Nunca adicionar novo sub-agent sem ADR justificando.

---

## 9. Em caso de dúvida

**Parar e perguntar.** Não assumir. Não "continuar na mesma linha" se o fundamento está duvidoso. Não escolher opção por omissão quando há trade-off relevante. Drift silencioso é o principal modo de falha do V1.

---

## 10. Operações destrutivas

Nunca executar sem confirmação humana explícita:
- `git reset --hard`, `git push --force`, `git clean -fdx`, `git checkout -- .`
- `rm -rf`, `DROP TABLE`, `TRUNCATE`
- Remoção de hooks, `.claude/`, ou arquivos listados em §0
- `--no-verify` em qualquer comando git

Em dúvida: perguntar antes de agir.
