# Prompts de auditoria — Kalibrium

> **Status:** ativo. Item 6.10 dos micro-ajustes da meta-auditoria #2. Este diretório centraliza os prompts usados para solicitar auditorias externas (Claude, GPT, Gemini, Codex). Antes do item 6.10, os prompts estavam espalhados em `docs/` raiz e em `docs/audits/` misturados com resultados — o que confundia o leitor entre "pergunta" e "resposta".

## Estrutura

Cada prompt vira um arquivo `<tipo>-<YYYY-MM-DD>.md`. Os tipos atuais:

| Arquivo | Tipo de auditoria | Quando usar | Origem anterior |
|---|---|---|---|
| [`technical-2026-04-10.md`](./technical-2026-04-10.md) | Auditoria técnica / de drift | Após fechar bloco do plano — verificar se hooks, sub-agents e skills estão alinhados com constituição | `docs/external-audit-prompt.md` |
| [`completeness-2026-04-10.md`](./completeness-2026-04-10.md) | Auditoria de completude | Quando há suspeita de lacunas estruturais (ausência de arquivos, políticas, artefatos que deveriam existir) | `docs/audits/completeness-audit-prompt-2026-04-10.md` |
| [`completeness-meta-2026-04-10.md`](./completeness-meta-2026-04-10.md) | Meta-auditoria de completude | Quando o plano de ação da auditoria de completude já foi gerado e precisa ser revisado independentemente antes de ser executado | `docs/audits/completeness-meta-audit-prompt-2026-04-10.md` |

## Regras de uso

1. **Prompts são imutáveis depois de publicados.** Se um prompt precisa de correção, cria-se uma nova versão com a data do dia em que foi corrigido, mantendo a anterior como referência histórica.
2. **Os resultados ficam em `docs/audits/external/`** — nunca misturar prompt com resposta no mesmo arquivo.
3. **Ao usar um prompt:** copiar literalmente o conteúdo e enviar ao agente externo (outro modelo) como mensagem inicial. Não editar no meio do caminho.
4. **Novo tipo de prompt exige entrada nova aqui:** adicionar linha à tabela acima no commit que cria o arquivo novo.

## Quando NÃO usar um prompt daqui

- **Auditoria contínua interna** (`/guide-check`) — não precisa de prompt externo, é skill operacional.
- **Retrospectiva de slice** — usa template em `docs/templates/` futuro, não prompt de auditoria.
- **Postmortem** — usa `docs/templates/postmortem-prod.md`, não prompt de auditoria.

## Histórico de uso

A lista abaixo registra quando cada prompt foi executado, contra qual agente externo, e onde ficou o resultado. Atualizar a cada nova rodada de auditoria.

| Data | Prompt | Agente externo | Resultado em |
|---|---|---|---|
| 2026-04-10 | `technical-2026-04-10.md` | Claude Opus 4.6 | `docs/audits/external/audit-claude-opus-4-6-2026-04-10.md` |
| 2026-04-10 | `technical-2026-04-10.md` | Codex / GPT-5 | `docs/audits/external/audit-codex-2026-04-10.md` |
| 2026-04-10 | `technical-2026-04-10.md` | Gemini 3.1 Pro | `docs/audits/external/audit-gemini-2026-04-10.md` |
| 2026-04-10 | `completeness-2026-04-10.md` | Claude Opus 4.6 | `docs/audits/external/audit-claude-opus-4-6-completeness-2026-04-10.md` |
| 2026-04-10 | `completeness-2026-04-10.md` | Codex / GPT-5 | `docs/audits/external/completeness-audit-gpt-5-codex-2026-04-10.md` |
| 2026-04-10 | `completeness-2026-04-10.md` | Gemini 3.1 Pro | `docs/audits/external/completeness-audit-gemini-3-1-pro-2026-04-10.md` |
| 2026-04-10 | `completeness-meta-2026-04-10.md` | Claude Opus 4.6 | `docs/audits/meta-audit-completeness-2026-04-10.md` |
