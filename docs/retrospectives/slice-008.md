# Retrospectiva slice-008

**Data:** 2026-04-14
**Resultado:** approved
**Fonte numérica:** [slice-008-report.md](slice-008-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits | 0 |
| Verificações approved | 1 |
| Verificações rejected | 0 |
| Tokens totais | 0 |

## O que funcionou
- Todos os gates obrigatorios chegaram a `approved` com findings vazios: `verification.json`, `review.json`, `security-review.json`, `test-audit.json` e `functional-review.json`.
- O gate de testes confirmou 12 de 12 ACs cobertos em `specs/008/test-audit.json`.
- O gate funcional confirmou 12 de 12 ACs atendidos com confianca alta em `specs/008/functional-review.json`.
- O security-reviewer aprovou a revalidacao transacional de vinculo, papel e status antes de gravar configuracoes do tenant.
- A tentativa de `/merge-slice 008` apos a correcao do harness confirmou os cinco gates antes de bloquear pela branch `main`, evitando pulo de processo.

## O que não funcionou
- O slice chegou ao encerramento na branch `main`. Como `scripts/merge-slice.sh` exige uma feature branch, o merge formal do harness foi substituido por excecao operacional auditavel em `docs/incidents/slice-008-mainline-integration-2026-04-14.md`.
- O `merge-slice.sh` ainda validava apenas verifier/reviewer antes desta sessao. Isso foi corrigido no commit `7eccf69`, passando a exigir os cinco gates.
- O `slice-report.sh` contabiliza somente eventos `verify` na tabela de verificacoes, entao o resumo numerico mostra 1 approved mesmo com review, gates finais e merge registrados em outros artefatos.
- A telemetria do Codex nao registrou tokens nem commits do slice, mantendo `Commits no slice = 0` e `Tokens totais = 0` no report, apesar de existirem commits reais no historico Git.

## Gates que dispararam em falso
- Nenhum gate de qualidade disparou falso positivo na rodada final. As rejeicoes anteriores apontaram riscos reais: validacao de perfil operacional, rollback transacional, isolamento de tenant B e stale access dentro da transacao.

## Gates que deveriam ter disparado e não dispararam
- O fluxo permitiu iniciar e concluir trabalho de produto diretamente em `main`, repetindo a excecao operacional ja observada no slice 006. Falta um guardrail antes de `/start-story`, `/new-slice` ou inicio de implementacao.
- Antes do commit `7eccf69`, o `merge-slice.sh` nao barrava ausencia dos tres gates finais. A correcao foi aplicada nesta sessao.

## Mudanças propostas
- [ ] B-023: adicionar guardrail para impedir novo slice de produto iniciado diretamente em `main`.
- [ ] B-024: atualizar `slice-report.sh` para contabilizar todos os gates atuais, nao apenas eventos `verify`.
- [ ] Manter a regra do proximo slice: criar feature branch ou worktree antes de qualquer alteracao de produto.

## Lições para o guia
- Excecao operacional de branch nao e bypass de qualidade quando os cinco gates estao aprovados e a decisao fica auditada.
- A regra precisa ser preventiva: detectar `main` no inicio do slice e nao apenas no merge.
- Quando o fluxo de gates evolui, o comando final de merge deve ser atualizado no mesmo ciclo para nao virar a parte mais fraca do processo.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R10 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
