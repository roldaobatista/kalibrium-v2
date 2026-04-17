---
description: Lista MCP servers ativos e valida que apenas os autorizados em .claude/allowed-mcps.txt estão em uso. Previne contaminação de contexto por MCPs desconhecidos. Uso: /mcp-check.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-001"
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
bash scripts/mcp-check.sh
```

> **Nota (meta-audit #2, item P1):** o script vive em `scripts/mcp-check.sh`, fora de `scripts/hooks/`. Antes da correção a skill apontava para `scripts/hooks/mcp-check.sh`, que nunca foi criado (e teria que ser selado pelo `hooks-lock`, dificultando evolução). Foi realocado para `scripts/` conforme decisão do meta-audit.

## Allowlist inicial sugerida para `.claude/allowed-mcps.txt`

```
plugin:context-mode:context-mode
plugin:context7:context7
plugin:github:github
```

(Ajustar conforme `docs/adr/NNNN-mcp-policy.md` — quando for criado.)

## Pré-condições

Nenhuma — pode ser executada a qualquer momento. Se `.claude/allowed-mcps.txt` não existir, a skill alerta que a allowlist precisa ser criada.

## Agentes

Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `.claude/allowed-mcps.txt` não existe | Alertar PM. Criar o arquivo com a allowlist inicial sugerida (ver seção acima) ou rodar `/adr` para formalizar política de MCPs primeiro. |
| MCP ativo não está na allowlist (alerta de contaminação) | Investigar imediatamente qual MCP é e quem o adicionou. Se não for reconhecido, desativar e registrar incidente em `docs/incidents/`. |
| Script `scripts/mcp-check.sh` não existe | Verificar integridade do harness. O script vive em `scripts/` (fora de `hooks/`). Pode precisar ser criado se o scaffold não o incluiu. |
| Comando `claude mcp list` não disponível no ambiente | Usar método alternativo de detecção (inspecionar output da sessão). Registrar limitação no relatório. |

## Output obrigatório (auditável)

Toda invocação de `/mcp-check` emite o artefato JSON rastreável em `docs/audits/mcp-check-YYYY-MM-DD.json` antes de encerrar. O arquivo é append-only — nunca sobrescrito no mesmo dia; se já existir, adicionar sufixo `-HHMM`.

### Schema

```json
{
  "$schema": "harness-audit-v1",
  "skill": "mcp-check",
  "timestamp": "2026-04-16T14:23:05Z",
  "verdict": "pass | fail",
  "findings_count": 0,
  "findings": [
    {
      "severity": "S1 | S2 | S3",
      "mcp_name": "plugin:unknown:foo",
      "reason": "not_in_allowlist | allowlist_missing | detection_failed",
      "recommendation": "desativar e investigar origem"
    }
  ],
  "evidence": {
    "allowlist_path": ".claude/allowed-mcps.txt",
    "allowlist_entries": ["plugin:context-mode:context-mode", "plugin:context7:context7"],
    "detected_mcps": ["plugin:context-mode:context-mode"],
    "missing_from_env": [],
    "contamination_candidates": [],
    "detection_method": "claude mcp list | session_inspection"
  }
}
```

- `verdict = pass` quando `contamination_candidates` é vazio.
- `verdict = fail` em qualquer finding S1-S3 (MCP não autorizado detectado).
- Findings S4-S5 (MCP listado mas ausente do ambiente) não bloqueiam mas são registrados.

## Evidência de execução

A skill não é considerada executada sem o artefato JSON gravado. O orquestrador valida a existência do arquivo antes de prosseguir. Sem o artefato, a invocação é reexecutada.

## Lifecycle do artefato

- **Retenção:** permanente (append-only histórico).
- **Localização:** `docs/audits/mcp-check-*.json`.
- **Referência em retrospectivas:** `governance` (modo retrospective) consulta a série temporal para detectar drift de allowlist.
- **Auditoria SOC 2:** compõe o audit trail de guardrail R1 junto com `forbidden-files-scan`.
- **Nunca deletar:** incidentes antigos são contexto para compliance.

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador executa script local).
- **Gates produzidos:** não é gate de slice; é guardrail de harness.
- **Output:** `docs/audits/mcp-check-YYYY-MM-DD.json` (schema `harness-audit-v1`).
- **Schema formal:** `docs/protocol/schemas/harness-audit-v1.schema.json` (v1.0.0 — formalizado em 2026-04-16; campos obrigatorios: `$schema`, `audit_type`, `timestamp`, `verdict`, `findings_count`, `findings`, `evidence`).
- **Isolamento R3:** não aplicável (sem sub-agent).
- **Ordem no pipeline:** pré-requisito opcional antes de `/guide-check`; invocado ad hoc.
