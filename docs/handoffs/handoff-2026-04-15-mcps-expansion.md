# Handoff 2026-04-15 — Expansão de ferramental MCP

**Contexto:** PM pediu para avaliar ferramentas/MCPs que facilitariam o trabalho do agente. Recomendações foram feitas e PM aprovou ("pode fazer").

## Decisões tomadas nesta sessão

1. **ADR-0012 aceita (2026-04-15)** — adicionar MCP oficial Postgres (`@modelcontextprotocol/server-postgres`). Figma MCP adiado até E03 provar a dor.
2. **Opção B escolhida** sobre A e C (ver ADR-0012 §Opções).
3. **`/sealed-diff` criada** — skill nova que consolida `settings-lock --check` + `hooks-lock --check` + validação de identidade git em qualquer momento da sessão. Já commitada em `5531e6e`.

## Estado do install do Postgres MCP

- **Preparado:** `instalar-postgres-mcp.bat` na raiz + `scripts/install-postgres-mcp.sh` + ADR-0012 aceita.
- **Pendente (PM):** duplo-clique em `instalar-postgres-mcp.bat` com Claude Code **fechado**. O script faz tudo (claude mcp add, update de allowlist, bump ADR-0002, relock).
- **Pós-install:** ao reabrir Claude Code, rodar `/mcp-check` e `/sealed-diff` — ambos devem ficar verdes.

## O que o próximo agente (Claude/Codex) deve fazer ao retomar

### Se `/resume` detectar arquivos modificados em `.claude/settings.json`, `.claude/allowed-mcps.txt`, `docs/adr/0002-mcp-policy.md`:

Isso significa que o PM **já rodou** o `.bat` com sucesso. Fluxo:

1. Rodar `/sealed-diff` — confirmar selos OK (o relock atualizou os hashes).
2. Rodar `/mcp-check` — confirmar que `postgres` aparece como ativo + allowlist bate.
3. Stage dos arquivos e commit:
   ```
   git add .claude/settings.json .claude/settings.json.sha256 \
           .claude/allowed-mcps.txt \
           docs/adr/0002-mcp-policy.md \
           docs/incidents/harness-relock-*.md
   git commit -m "chore(harness): adiciona MCP postgres (ref: ADR-0012)"
   ```
4. Atualizar `.claude/agents/data-modeler.md` e `.claude/agents/security-reviewer.md` com seção "Uso de Postgres MCP" (regras: schema-first, não dumpar PII sem justificativa). Ver ADR-0012 §Riscos.
5. Atualizar `docs/handoffs/latest.md` + `project-state.json`.

### Se NADA foi modificado fora deste commit:

PM não rodou o `.bat` ainda. Flow:

1. Retomar o slice anterior (slice-010 E02-S07, 28/28 green, aguardando merge).
2. Lembrar o PM suavemente que o install do Postgres MCP está preparado (`instalar-postgres-mcp.bat` na raiz) — mas **não é bloqueante** para nenhum slice MVP.
3. Continuar com a próxima prioridade do roadmap.

### Se o `.bat` falhou:

PM vai trazer o output do erro. Diagnóstico comum:

| Sintoma | Causa provável | Correção |
|---|---|---|
| "Claude CLI não encontrado" | PATH incompleto | Rodar o `.bat` de dentro do diretório do projeto, conferir `where claude` |
| "DB_DATABASE ausente em .env" | .env não configurado | PM precisa configurar DB_* antes |
| "claude mcp add falhou" | Sessão Claude ainda aberta | Fechar Claude Code antes de rodar |
| "relock-harness.sh falhou" | TTY não detectada | Garantir que o `.bat` foi **duplo-clicado** (não chamado de dentro de outro shell) |

## Referências

- ADR-0002: política geral de MCPs (v2 atual → v3 pós-install)
- ADR-0012: decisão de adicionar Postgres MCP (aceita)
- CLAUDE.md §9: procedimento legítimo de alteração de arquivos selados
- `scripts/install-postgres-mcp.sh`: lógica detalhada do install
- `.claude/skills/sealed-diff.md`: verificação sob demanda dos selos

## Figma MCP (adiado)

Reavaliar quando o primeiro slice de E03 (UI/frontend) começar. Se o ciclo atual de screenshots + texto funcionar, manter adiado. Se houver dor empírica, abrir nova ADR.
