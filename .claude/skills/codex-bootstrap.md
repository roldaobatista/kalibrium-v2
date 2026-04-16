---
description: Inicializa uma sessao Codex CLI neste projeto seguindo o harness. Uso obrigatorio no inicio de toda sessao Codex e antes de qualquer trabalho.
protocol_version: "1.2.2"
changelog: "2026-04-16 — quality audit Cat C polishing"
---

# /codex-bootstrap

## Uso
```
/codex-bootstrap
```

## Por que existe

O Codex CLI pode operar como orquestrador alternativo exclusivo, mas nao dispara automaticamente todos os hooks do Claude Code. Esta skill transforma essa diferenca em checklist obrigatorio e auditavel.

## Quando invocar

- No inicio de toda sessao Codex neste repositorio.
- Apos `codex resume` ou troca de terminal.
- Antes de iniciar auditoria, implementacao, documentacao, commit ou relock.
- Sempre que houver duvida se `CLAUDE.md` e `docs/handoffs/latest.md` foram carregados.

## Pre-condicoes

- `cwd` deve ser `C:\PROJETOS\saas\kalibrium-v2` ou subdiretorio do repo.
- Apenas um orquestrador ativo por branch (R2).

## O que fazer

### 1. Carregar fontes permitidas por R1

Ler, nesta ordem:

1. `CLAUDE.md`
2. `docs/constitution.md`
3. `docs/TECHNICAL-DECISIONS.md`
4. `docs/documentation-requirements.md`
5. `project-state.json`
6. `docs/handoffs/latest.md`
7. `.claude/agents/orchestrator.md`

Nao criar nem ler como instrucoes operacionais: `AGENTS.md`, `CODEX.md`, `.cursorrules`, `GEMINI.md`, `copilot-instructions.md`, `.bmad-core/`, `.cursor/`, `.continue/`, `.windsurfrules`, `.aider.conf.yml`.

### 2. Rodar checks equivalentes ao SessionStart

Executar e guardar output + exit code:

```powershell
git status --short
bash scripts/hooks/session-start.sh
bash scripts/hooks/settings-lock.sh --check
bash scripts/hooks/hooks-lock.sh --check
```

Se qualquer check falhar, parar e explicar o bloqueio ao PM antes de editar arquivos.

### 3. Restaurar estado

Determinar:

- branch e commit atuais
- fase do projeto
- ultimo handoff
- proxima acao recomendada
- pendencias/bloqueios
- se ha arquivo nao commitado

### 4. Confirmar inicio ao PM

Responder em linguagem R12:

```text
Bootstrap Codex concluido.
Estado: <fase/commit/branch>.
Pendencias: <lista curta>.
Proxima acao: <uma acao>.
```

Incluir comandos, outputs relevantes e exit codes.

## Encerramento obrigatorio

Antes de encerrar a sessao Codex:

1. Atualizar `project-state.json`.
2. Criar `docs/handoffs/handoff-YYYY-MM-DD-HHMM.md`.
3. Atualizar `docs/handoffs/latest.md`.
4. Rodar:

```powershell
Get-Content -Raw project-state.json | ConvertFrom-Json
git diff --check
git diff --name-only -- .claude/settings.json .claude/settings.json.sha256 scripts/hooks scripts/hooks/MANIFEST.sha256 .claude/telemetry .claude/allowed-git-identities.txt .claude/git-identity-baseline
git status --short
```

5. Commitar o checkpoint/handoff ou declarar explicitamente por que ficara pendente.

## Handoff

- PM quer encerrar -> checkpoint/handoff obrigatorio antes de responder que pode encerrar.
- PM quer abrir outro terminal -> checkpoint/handoff obrigatorio antes de entregar o prompt de retomada.
- Contexto comprimiu -> checkpoint imediato e recomendacao de nova sessao.

## Próximo passo

- Bootstrap OK → seguir `next_action` do handoff carregado
- Check falhou → parar, explicar ao PM, investigar antes de editar
- Sem handoff prévio → `/start` ou `/project-status` para orientar PM

## Conformidade com protocolo v1.2.2

- **Agents invocados:** nenhum (orquestrador Codex carrega fontes R1).
- **Gates produzidos:** gate de inicialização de sessão Codex; equivalente ao SessionStart do Claude Code.
- **Output:** mensagem R12 ao PM com estado restaurado + próxima ação.
- **Schema formal:** consome fontes canônicas R1 + `project-state.json`.
- **Isolamento R3:** não aplicável (orquestrador principal).
- **Ordem no pipeline:** **primeiro comando** de toda sessão Codex; obrigatório antes de qualquer trabalho.
