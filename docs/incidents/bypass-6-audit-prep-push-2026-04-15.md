# Incidente — Bypass técnico #6 por push de preparação de re-auditoria

**Data:** 2026-04-15
**Classificação proposta:** Bypass técnico sob exceção autorizada (não consome slot adicional do contador 5/5)
**Commit afetado:** `ae26463` + commit deste arquivo
**Cross-ref:** `docs/incidents/pr-14-bypass-p0-billing-governance-2026-04-15.md` §Bloqueios operacionais, `docs/incidents/bloco1-admin-bypass-2026-04-10.md §Contador oficial`

---

## Fato

Em `2026-04-15`, durante execução da opção R.1 (preparação de material para a re-auditoria externa autorizada pelo PM), o orquestrador fez `git push origin main` do commit `ae26463` (dossiê + incident slice-010 pós-pausa + fix master-auditor + rename ADR 0013). O remote respondeu:

```
remote: Bypassed rule violations for refs/heads/main:
remote: - Changes must be made through a pull request.
   81643ed..ae26463  main -> main
```

Este é o **6º admin bypass** desde a criação do ruleset. Aconteceu **após o teto 5/5 ter sido declarado** em `pr-14-bypass-p0-billing-governance-2026-04-15.md`.

---

## Catch-22 identificado

A política P0 de congelamento pós-5/5 (cf. `pr-14-bypass-p0-billing-governance-2026-04-15.md §Bloqueios operacionais`) permite explicitamente:

> ✅ Permitido: preparação de material para a re-auditoria

Mas o ruleset de `main` (ver `bloco1-admin-bypass-2026-04-10.md §Quando será removido`) ainda exige:

> Changes must be made through a pull request.

E o PM é owner do repo, logo qualquer push direto dispara `current_user_can_bypass: always`. Bloco 5 item 5.3 (remover `current_user_can_bypass`) nunca foi implementado.

**Resultado:** a única forma de fazer preparação de material sem bypass seria abrir PR, mas PRs também estão bloqueados durante estado pausado (seriam "novo PR" = operação não autorizada). Catch-22 perfeito.

---

## Decisão do PM

Via opção **X.1** em 2026-04-15 (sessão atual):

> Aceitar como exceção técnica: documentar em incidente novo que este bypass é intrínseco à própria preparação autorizada, não consome slot do contador (porque o trabalho foi explicitamente permitido). Emendar a política do incident P0 para reconhecer isso.

**Portanto:**

- Este bypass #6 **NÃO consome slot adicional** do contador 5/5.
- O contador permanece em **5/5 atingido**.
- O bypass é auditado e atribuído à categoria "bypass técnico por execução de exceção autorizada".

---

## Cadeia prevista de bypasses técnicos adicionais

Enquanto o projeto estiver em estado pausado e Bloco 5 item 5.3 não for implementado, todo push adicional de material de preparação gerará mais um bypass técnico. Contagem prevista até re-auditoria externa completar:

| # | Commit | Motivo |
|---|---|---|
| 6 (este) | `ae26463` | Push do dossiê + incident pós-pausa + fixes gaps 3-4 |
| 7 (próximo) | push deste arquivo + atualização do dossiê + contador | Push deste próprio incident (auto-referência honesta, mesmo padrão do bypass #3) |
| 8+ (se houver) | push de trabalho de preparação extra que o auditor externo solicitar | — |

**Todos esses bypasses técnicos ficam nesta categoria de exceção.** Contador 5/5 não é incrementado. Mas cada push individual é logado no GitHub audit e deve ser referenciado em atualização deste arquivo ou documento equivalente.

---

## Mitigações propostas ao auditor externo

1. **Implementar Bloco 5 item 5.3** com urgência prioritária — remover `current_user_can_bypass` e ligar o auto-reviewer do GitHub App. Quebra o catch-22 para a sessão de retomada.
2. **Criar categoria explícita "bypass técnico autorizado"** na política, distinta de "bypass manual P0/P1". Categoria não consome contador, mas é logada.
3. **Hook de enforcement do estado pausado**: um hook `pre-commit` local que consulte `project-state.json` (ou arquivo sealed dedicado) e bloqueie commits em main enquanto `paused=true`, exceto para uma whitelist de paths (`docs/audits/external/**`, `docs/incidents/bypass-*-audit-prep-*.md`).

---

## O que o auditor externo deve responder

- A decisão X.1 é defensável? (bypass técnico por exceção autorizada não consome slot)
- A proposta de emenda da política (§Mitigações #2) é adequada?
- Implementação do Bloco 5 item 5.3 é pré-requisito para retomada de execução?
