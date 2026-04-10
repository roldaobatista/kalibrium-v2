---
description: Lista MCP servers ativos e valida que apenas os autorizados em .claude/allowed-mcps.txt estão em uso. Previne contaminação de contexto por MCPs desconhecidos. Uso: /mcp-check.
---

# /mcp-check

## Uso
```
/mcp-check
```

## O que faz

1. Lê `.claude/allowed-mcps.txt` (lista autorizada; uma entrada por linha).
2. Obtém MCPs ativos no ambiente atual (via output da sessão Claude Code ou comando `claude mcp list` se disponível).
3. Compara: qualquer MCP ativo **não presente** na allowlist = alerta.
4. Qualquer MCP na allowlist **ausente** do ambiente = aviso (provavelmente ok, só notar).

## Por que importa

V1 teve drift por múltiplas fontes de instrução. MCPs podem injetar system prompts, ferramentas ocultas ou permissões amplas. Deriva silenciosa.

Esta skill é complementar a `/forbidden-files-scan` (arquivos locais) — cobre o vetor de **contaminação via servidor externo**.

## Implementação
```bash
bash scripts/hooks/mcp-check.sh
```

## Allowlist inicial sugerida para `.claude/allowed-mcps.txt`

```
plugin:context-mode:context-mode
plugin:context7:context7
plugin:github:github
```

(Ajustar conforme `docs/adr/NNNN-mcp-policy.md` — quando for criado.)
