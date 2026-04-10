# Incident — Push do Bloco 1 do meta-audit via admin bypass

**Data:** 2026-04-10
**Severidade:** informativa (bypass permitido, coerente com incident precedente `pr-1-admin-merge.md`)
**Commits afetados:** `345b0a2` + `c061e3c` (e este próprio commit C — ver §Auto-referência)
**Pusher:** roldaobatista (admin bypass via ruleset existente)

---

## Contexto

Push de dois commits atômicos do Bloco 1 do meta-audit 2026-04-10 direto em `main`:

| SHA | Mensagem |
|---|---|
| `345b0a2` | `docs(meta-audit): consolidacao externa + action plan + decisao PM` |
| `c061e3c` | `chore(harness): selar contra auto-modificacao (bloco 1 meta-audit)` |

O ruleset de `main` continua exigindo PR + 1 review desde a configuração permanente do `pr-1-admin-merge.md` Fase 2. Como o **Bloco 5 item 5.3** (substituir `current_user_can_bypass: always` por GitHub Action `auto-approve.yml` + GitHub App `kalibrium-auto-reviewer`) **ainda não foi implementado**, `current_user_can_bypass: always` continua sendo o único caminho operacional disponível para o PM (owner) merger trabalho legítimo em `main`.

O `git push origin main` executado a partir do working tree limpo após a sessão de implementação do Bloco 1 disparou o admin bypass automaticamente. Output do remote:

```
remote: Bypassed rule violations for refs/heads/main:
remote: - Changes must be made through a pull request.
   016c783..c061e3c  main -> main
```

---

## Motivo

Push do Bloco 1 do meta-audit. O Bloco 5 item 5.3 ainda não está implementado — `current_user_can_bypass: always` é o único caminho até lá. Coerente com `pr-1-admin-merge.md §Correção permanente`, que registra esse caminho como **bypass permitido** enquanto a substituição arquitetural não chega.

---

## Validações feitas antes do push

Pré-requisitos atendidos pela sessão executora antes de invocar `git push`:

| Validação | Resultado |
|---|---|
| `/guide-check` | **VERDE** — 0 findings, 11 commits auditados em `${baseline}..HEAD`, baseline `6a0d297717c7` |
| `smoke-test-hooks.sh` | **75/75 verdes** (1 skip honesto: symlink test em Git Bash Windows sem admin) |
| `settings-lock --check` | OK — `.claude/settings.json` íntegro |
| `hooks-lock --check` | OK — todos os 16 hooks íntegros, `MANIFEST.sha256` bate |
| Hashes selados registrados | sim, no body do commit B + neste incident |
| Working tree | `clean` antes do push |
| Review prévia pelo PM | **sim** — esta sessão de planejamento/implementação consistiu de 7 turnos com PM aprovando explicitamente cada decisão arquitetural (1.4 multi-sinal, 1.7 padrão (a) backup/mutate/restore, observação 1.6 Bash redirects, Q1 baseline anistia, Q2 dois commits separados P6-compliant, Q3 gitignore, Q5 push) |
| Atomicidade P6 | **sim** — dois commits separados (A docs / B código), commit B referencia A no body, ambos com proposito único |

---

## Contagem de bypasses acumulados

**2 bypasses** desde a criação do ruleset:

| # | Data | Origem | Commits | Justificativa |
|---|---|---|---|---|
| 1 | 2026-04-10 | PR #1 admin merge (`pr-1-admin-merge.md`) | merge `182a7ca` | Fase 2 setup — humano=PM, sem revisor técnico no loop |
| 2 | 2026-04-10 | Push do Bloco 1 do meta-audit (este incident) | `345b0a2` + `c061e3c` | Bloco 5 item 5.3 ainda não implementado |

**Observabilidade para retrospectiva:** este contador deve ser atualizado a cada bypass adicional até o Bloco 5 item 5.3 fechar o caminho. Na retrospectiva final do Dia 1 (Bloco 7), reportar o número total e validar que cada bypass tem incident correspondente em `docs/incidents/`.

---

## Auto-referência honesta

**Este próprio commit (C) também disparará admin bypass ao ser pushado**, pelo mesmo motivo: o ruleset continua ativo e o Bloco 5 item 5.3 ainda não fechou o caminho do `current_user_can_bypass: always`. A recursão é aceita até o Bloco 5 substituir o bypass manual pelo auto-reviewer.

Quando este arquivo for pushado, a contagem real será **3 bypasses acumulados** — o contador acima reflete o estado **antes** do push do commit C, conforme visível no working tree no momento da escrita.

---

## Quando será removido

**Bloco 5 item 5.3** do action plan (`docs/audits/meta-audit-2026-04-10-action-plan.md`):

> 5.3 Ruleset de `main` endurecido (remover `current_user_can_bypass`)

Sequência operacional:
1. Bloco 5 item 5.1 cria `.github/workflows/ci.yml` (lint + types + test + smoke-hooks + harness-integrity)
2. Bloco 5 item 5.2 cria `.github/workflows/auto-approve.yml` + GitHub App `kalibrium-auto-reviewer`
3. Bloco 5 item 5.3 remove `current_user_can_bypass` do ruleset via API
4. A partir desse momento, todo merge para `main` exige PR + auto-review do GitHub App + CI verde

Após o item 5.3, este incident vira **histórico permanente** — não há mais caminho para bypass manual.

---

## Por que o admin bypass continua aceitável neste caso específico

1. Bloco 1 é **infraestrutura defensiva** (selos contra auto-modificação) — não é código de produto que afeta usuários finais.
2. Ambos os commits passaram **smoke-test 75/75** e **/guide-check VERDE** antes do push.
3. As decisões arquiteturais foram **explicitamente aprovadas pelo PM** turno-a-turno (não foi self-review do agente).
4. O harness ainda não tem o Bloco 5 — o caminho de PR + auto-reviewer literalmente não existe ainda.
5. Tentar abrir PR + self-aprovar pela UI seria **teatro** (mesmo problema do PR #1).
6. O admin bypass fica registrado no **audit log do GitHub** automaticamente — rastreável e contável.
7. Este arquivo serve como **paper trail** que mantém o contador de bypasses observável.

---

## Lições

1. **Cada bypass tem incident correspondente** — sem exceção. A obrigatoriedade do incident impede que bypasses se acumulem invisíveis.
2. **A recursão é honesta** — o próprio commit que documenta o bypass também usa o bypass. Tentar "salvar" o commit C por outro caminho seria tentar esconder o problema; documentá-lo de frente é mais honesto.
3. **Bloco 5 é prioridade real** — quanto mais tempo o bypass continuar disponível, maior o risco de uso indevido. A contagem de 2 → 3 já mostra que o caminho é fácil.

---

## Referências

- Incident precedente: `docs/incidents/pr-1-admin-merge.md`
- Action plan: `docs/audits/meta-audit-2026-04-10-action-plan.md` (commit `345b0a2`)
- Decisão PM: `docs/decisions/pm-decision-meta-audit-2026-04-10.md` (commit `345b0a2`)
- Bloco 1 implementação: commit `c061e3c`
- Constitution R5 + R9: `docs/constitution.md §4`
- Ruleset GitHub: https://github.com/roldaobatista/kalibrium-v2/rules/14936750
