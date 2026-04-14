# Retrospectiva slice-007

**Data:** 2026-04-14
**Resultado:** approved
**Fonte numérica:** [slice-007-report.md](slice-007-report.md)

## Números (resumo)

| Métrica | Valor |
|---|---|
| Commits | 0 |
| Verificações approved | 9 |
| Verificações rejected | 1 |
| Tokens totais | 0 |

## O que funcionou
- O slice 007 fechou com todos os gates aprovados e `findings: []`: `verification.json`, `review.json`, `security-review.json`, `test-audit.json` e `functional-review.json`.
- A validação local completa passou antes do merge: `php scripts/test-scope.php all` retornou exit 0 com fast `109 passed`, integration `6 passed`, build `4 passed`, tooling `12 passed` e mutates-config `1 passed`.
- O bloqueio do CI privado foi separado de falha de produto: o GitHub Actions privado não iniciou por billing/spending limit, enquanto o mesmo snapshot sanitizado passou no repositório público.
- O PR #9 foi mergeado em `main` com merge commit `81597e6dca6e95364fb3171262978b2f154ddb6e`, preservando evidência pública verde no run `24395439095`.
- O ajuste final de portabilidade aceitou hashes Vite URL-safe no Linux sem permitir asset sem versão como `assets/app.js`.

## O que não funcionou
- O CI privado não pôde ser usado como gate automático porque o job `Harness integrity` não iniciou por billing/spending limit; os demais jobs foram pulados por dependência do harness.
- O primeiro snapshot público expôs fragilidade de portabilidade: o teste do slice 006 aceitava apenas hash alfanumérico contínuo, mas o Vite no Linux gerou `app-p6bprD-q.css`.
- O snapshot público inicial carregava teste privado de deploy/staging do slice 004 sem os artefatos privados correspondentes. A correção foi manter os artefatos de staging fora do público e remover o teste privado do espelho público.
- A telemetria do relatório não capturou commits nem tokens de sub-agents nesta execução Codex; `slice-007-report.md` registra commits `0` e tokens totais `0`, embora os commits estejam no histórico Git.

## Gates que dispararam em falso
- O check privado `Harness integrity` apareceu como failure no PR, mas o próprio GitHub informou que o job não iniciou por billing/spending limit. Não foi reprovação de código ou de harness.
- O teste de build do slice 006 falhou no CI público por regex estreita de hash Vite; o build estava versionado corretamente e o teste foi corrigido para aceitar caracteres URL-safe.

## Gates que deveriam ter disparado e não dispararam
- O bloqueio por billing do GitHub Actions privado não foi detectável pelos hooks locais antes do push. A mitigação adotada foi validação pública sanitizada, sem publicar fontes operacionais privadas.
- A rotina de geração de relatório produziu quebra de linha indevida em budgets de sub-agents em ambiente Windows/CRLF; a tabela foi corrigida manualmente neste relatório.

## Mudanças propostas
- [ ] Avaliar um check operacional para avisar cedo quando GitHub Actions privado estiver bloqueado por billing/spending limit.
- [ ] Melhorar `scripts/slice-report.sh` para tratar CRLF em `max_tokens_per_invocation` antes de montar a tabela de budgets.
- [ ] Documentar o padrão de repositório público de validação sanitizada para casos em que o CI privado esteja indisponível.

## Lições para o guia
- Repositório público de validação é útil como evidência externa quando o CI privado não inicia, mas precisa ter escopo explícito: sem `docs/`, `specs/`, `epics/`, `.claude/` e sem artefatos privados de staging.
- Um teste de build que valida hash precisa aceitar o formato real do bundler no ambiente de CI, incluindo caracteres URL-safe.
- A regra de merge administrativo continua aceitável apenas quando os gates independentes aprovaram e o bloqueio remanescente não é uma reprovação técnica.

---

**Lembrete operacional:**
- Alterações em P1-P9 ou R1-R10 → ADR + aprovação humana + bump de versão em constitution.md (constitution §5).
- Outras mudanças (hooks, agents, skills) → commit `chore(harness):` + item em `docs/guide-backlog.md`.
