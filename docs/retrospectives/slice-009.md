# Retrospectiva slice-009

**Data:** 2026-04-15
**Resultado:** approved
**Fonte numérica:** [slice-009-report.md](slice-009-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits totais no slice | 26+ (branch slice-009-ten-002) |
| Commits de fix nesta sessão | 4 (seguranca + UX + UX-008 + artefatos) |
| Gates verdicts: approved | 9 |
| Gates verdicts: rejected | 1 (functional 1ª rodada) |
| R6 triggers registrados | 2 (PD-012, PD-013) |
| PR | #10 (merged) |

## O que funcionou

- **Gates paralelos descobriram problemas complementares:** security encontrou 4 findings técnicos (token exposto, blocklist contornável, escopo tenant faltando, PII em auditoria) e functional encontrou 7 findings de UX (AC-002 falhando, labels em inglês, email sem contexto) — sem sobreposição entre os dois. Validou o desenho R11 de gates independentes em contexto isolado.
- **Test-audit aprovou de primeira (18/18 ACs, 67 testes, 329 assertions)** mesmo com 2 refatorações de teste (AC-017 labels PT-BR e AC-SEC-001 mudança de exception para sanitização). Sinal de que o ac-to-test gerou cobertura robusta.
- **Fixer conseguiu corrigir 11 findings em 2 commits atômicos** (seguranca + UX) mantendo testes verdes. Em 2ª rodada, só 1 finding novo (UX-008) apareceu — escopo convergiu.
- **Auto-merge configurado mid-session funcionou:** PR #11 (chore harness) foi o primeiro a usar e mergeou sozinho. Fluxo `/merge-slice NNN` → PR → auto-merge → branch deletada passa a ser contínuo.

## O que não funcionou

- **Commit message enganosa `b270cd2 fix(slice-009): corrige achados dos gates paralelos`** — foi criado ANTES dos gates paralelos terem rodado. Apontou inconsistência no kickoff desta sessão que exigiu diagnóstico manual antes de prosseguir. Sessão anterior deixou artefatos `*-input/` untracked mas não gravou `*.json` em `specs/009/` nem atualizou `project-state.json`.
- **Ausência de script para montar input packages dos 3 gates paralelos.** Só existia `scripts/security-scan.sh` para a parte mecânica. Tive que criar `scripts/build-gate-inputs.sh` ad-hoc. Sem ele, cada rodada dos 3 gates dependia de re-montagem manual.
- **Local `main` 14 commits fora do origin (slice 008).** Slice 008 foi integrado via fluxo diferente e nunca empurrado ao remote. Só descoberto quando tentei `git pull` após mergear PR #10. Exigiu `git reset origin/main` com autorização do PM.
- **Functional-reviewer pausou 2x no meio da execução** (antes de emitir JSON) — tive que respawnar com instrução explícita "não pause". Consumo extra de tokens.
- **R6 acionado 2x no slice 009** (PD-012 por último gerente/concorrência; PD-013 por empacotamento do reviewer vazando `verification.json`). Ambos resolvidos, mas indicam que o pacote do reviewer estava mal desenhado e o teste de concorrência para "último gerente" precisou de endurecimento.

## Gates que dispararam em falso

- Nenhum registrado nesta sessão.

## Gates que deveriam ter disparado e não dispararam

- **Consistência entre commit message e estado dos gates.** `b270cd2` alegou corrigir findings paralelos sem que esses tivessem rodado. Seria útil um hook `pre-commit-gate.sh` que bloqueie mensagens do tipo `corrige achados dos gates paralelos` quando `specs/NNN/security-review.json` / `test-audit.json` / `functional-review.json` não existirem ou não estiverem `rejected`.
- **Detecção de drift entre local main e origin/main.** `session-start.sh` hoje valida harness sealed files, mas não avisa quando local main tem N commits não empurrados. Poderia ser um `warn` (não `fail`) no SessionStart.
- **Pacote do reviewer vazando artefatos proibidos.** Foi o PD-013 — `review-input/files-changed.txt` e `review-input/diff.patch` continham caminhos de `verification.json`. Fix aplicado em `scripts/review-slice.sh`. Já corrigido e testado, mas deveria ter dado red flag desde a 1ª rodada do reviewer via verificação automática no próprio `review-slice.sh` (hoje tem, porém só após o fix).

## Mudanças propostas

- [ ] **Adicionar `scripts/build-gate-inputs.sh` ao repositório** (já criado nesta sessão; commitar em branch separada ou como parte de próxima refatoração do harness). Torna os 3 gates paralelos reproduzíveis.
- [ ] **Espelhar gates em GitHub Actions como required_status_checks** — hoje os gates rodam só local; com CI verde obrigatório, auto-merge fica seguro mesmo sem PM clicar. Item de backlog de harness.
- [ ] **Hook leve para validar consistência commit message ↔ estado dos gates** (nível `warn`, não `fail`). Item de guide-backlog.
- [ ] **Orquestrador deve instruir sub-agents explicitamente a completar em 1 rodada** (padrão observado com functional-reviewer). Ajustar templates de prompt dos Skills `/functional-review`, `/security-review`, `/test-audit` para incluir "NÃO pause; complete e grave o JSON final".

## Lições para o guia

- **Nunca confiar em commit message quando o estado de gates está ambíguo** — sempre abrir `specs/NNN/*.json` e `project-state.json` para validar. Esta sessão começou com um diagnóstico que economizou retrabalho.
- **Gates paralelos só são paralelos se os input packages forem reproducíveis.** A ferramenta deve ser parte do harness, não improvisada pelo orquestrador ativo.
- **Fluxo local ↔ origin precisa ser enforced desde o início.** Slices futuros: sempre push ao criar branch, mesmo antes de terminar. Evita divergência silenciosa que só aparece no `/merge-slice`.
- **R11 (dual-verifier) funciona e pega coisas diferentes.** Security e functional encontraram zero findings sobrepostos — cada gate tem seu olhar e o conjunto é maior que as partes.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R10 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
