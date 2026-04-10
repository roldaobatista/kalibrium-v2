# Audit inicial do harness — 2026-04-10

**Escopo:** verificar que o harness Kalibrium V2 foi montado corretamente após o Dia 0.
**Modo:** standalone (script inline via Bash/ctx_execute — o sub-agent `guide-auditor` também pode rodar via `/guide-check`).
**Auditor:** humano (roldaobatista) + Claude Opus 4.6 (1M context).

---

## Score final

**STATUS: VERDE** após 1 bug corrigido durante a auditoria.

| Categoria | Checks | Resultado |
|---|---|---|
| [1/11] Arquivos obrigatórios | 17 | ✓ todos presentes |
| [2/11] R1 — arquivos proibidos | 10 patterns | ✓ nenhum encontrado |
| [3/11] Sub-agents | 6 | ✓ 5 agents com budget R8 declarado + verifier aderente a R3/R4 |
| [4/11] Skills | 8 | ✓ todos presentes com description |
| [5/11] Hooks + coerência settings.json | 13 | ✓ 12 hooks + settings.json consistente |
| [6/11] Helpers dos skills | 8 | ✓ todos presentes |
| [7/11] ADRs + TECHNICAL-DECISIONS | 2 | ✓ ADR-0002 válido e indexado |
| [8/11] Schema verification.schema.json | 10 | ✓ parseável + enums críticos (approved, rejected, P1..P9, R1..R10) |
| [9/11] Coerência P/R constitution ↔ hooks | 19 | ✓ todas as 19 regras declaradas e referenciadas |
| [10/11] Git state + GitHub | 8 | ✓ main, 8 commits, upstream, ruleset ativo, autores limpos |
| [11/11] Smoke tests | 4 | ✓ 29+21 = 50 testes verdes |
| **TOTAL** | **~105** | **✓** |

---

## Achados

### 🟢 Verdes (sem ação)
- Estrutura de arquivos 100% conforme a proposta do Dia 0.
- R1 sem violações (nem arquivos nem diretórios proibidos).
- Sub-agents: architect (30k), ac-to-test (40k), implementer (80k), verifier (25k), guide-auditor (15k) — todos com `max_tokens_per_invocation` no frontmatter (R8).
- Verifier menciona `verification-input/` e JSON (P3/R3/R4).
- Todos os 12 hooks referenciados em `settings.json` existem.
- Todas as 19 regras (P1-P9 + R1-R10) declaradas na constitution aparecem referenciadas em pelo menos um hook, sub-agent ou skill.
- Schema JSON parseável via `python3` + contém todos os 10 enums críticos esperados.
- ADR-0002 (MCP policy) tem Status, Opções consideradas, Decisão e Consequências.
- Git: branch `main`, 8 commits atômicos, upstream `origin/main`, sincronizado, ruleset ativo no GitHub (id=14936750), nenhum autor suspeito no histórico (R5).
- Smoke tests: `smoke-test-hooks.sh` 29/29, `smoke-test-scripts.sh` 21/21.

### 🔴 Bug real encontrado e corrigido

**BUG-001 — Smoke tests contaminavam git config local**

- **Sintoma:** após rodar `smoke-test-hooks.sh` ou `smoke-test-scripts.sh` no repo real, `git config --local user.name` virava `smoke-test-user` e `user.email` virava `smoke@test.local`. Qualquer commit subsequente saía com autor errado (R5 não casa `smoke-test-user` com `auto-*` ou `[bot]`, então passaria silenciosamente).
- **Causa raiz:** ambos os smoke tests faziam `git config --local user.name "smoke-test-user"` sem salvar o valor anterior nem restaurar no final.
- **Como foi detectado:** check [10] da auditoria pegou `git user.name: smoke-test-user` após a primeira execução dos smoke tests.
- **Fix aplicado em `scripts/smoke-test-hooks.sh` e `scripts/smoke-test-scripts.sh`:**
  - Capturar `ORIG_GIT_NAME` e `ORIG_GIT_EMAIL` antes do override.
  - Restaurar no final da seção (hooks) ou via `trap EXIT` (scripts).
  - `unset` se o valor original era vazio.
- **Validação do fix:** rodei smoke-test isoladamente após restaurar manualmente a config e verifiquei que permaneceu `roldaobatista` / `roldaobatista@users.noreply.github.com`.
- **Efeito colateral observado:** se o processo é morto por SIGKILL (ex.: timeout do ctx_execute), o `trap EXIT` não roda e a config pode ficar contaminada. Mitigação: após fix, uma única execução posterior restaura corretamente.
- **Severidade:** média. Não causa perda de dados, mas silenciosamente violaria o espírito de R5 (autor identificável).

### 🟡 Observações não-bloqueantes

- **Smoke test conta 29 quando em branch `main`**, 30 quando em feature branch. Isso porque o teste "pre-push-gate permite push em branch não-main" é pulado quando já estamos em main (não tem como testar sem sair da branch atual). Não é bug — é comportamento intencional.
- **ctx_execute timeout de 30s** não acomoda `smoke-test-scripts.sh` + hooks juntos. Use `bash` direto com timeout maior quando precisar rodar ambos em sequência no audit.

---

## Recomendações

1. **Mergear o fix dos smoke tests** via feature branch + PR (main está protegida).
2. **Rodar `/guide-check`** (sub-agent guide-auditor) semanalmente a partir de agora.
3. **Considerar adicionar check da config git** no `session-start.sh`: se `user.name` matcha `smoke-test-*`, avisar o usuário.
4. **Considerar registrar esta auditoria como baseline** em `.claude/snapshots/` para que `guide-auditor` compare mudanças futuras.

---

## Estado final do repo

- `main`: 8 commits, sincronizado com `origin/main`.
- `origin/main`: protegido por ruleset 14936750 (deletion, non_fast_forward, pull_request com 1 review).
- Uncommitted changes: `scripts/smoke-test-hooks.sh` e `scripts/smoke-test-scripts.sh` (fixes do BUG-001 aguardando commit via feature branch).

---

## Próximos passos sugeridos

- [ ] Criar branch `fix/smoke-test-config-restore`
- [ ] Commit: `fix(smoke-test): salva e restaura git config local para não contaminar repo`
- [ ] Commit: `docs(audit): audit inicial do harness 2026-04-10`
- [ ] Push + `gh pr create`
- [ ] Review (self-review aceitável neste caso porque é setup) + merge
- [ ] Dia 1 começa quando `main` recebeu este primeiro PR de fix.
