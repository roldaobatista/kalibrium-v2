---
description: Procura arquivos de instrução proibidos (R1) no repo — .cursorrules, AGENTS.md, GEMINI.md, copilot-instructions.md, .bmad-core/, etc. Use quando suspeitar de contaminação do harness. Uso: /forbidden-files-scan.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-001"
---

# /forbidden-files-scan

## Uso
```
/forbidden-files-scan
```

## O que faz

Executa o script `scripts/hooks/forbidden-files-scan.sh` manualmente. Mesma lógica do `SessionStart` hook, mas rodável sob demanda.

## Lista de padrões proibidos (R1)

Arquivos:
- `.cursorrules`
- `AGENTS.md`
- `GEMINI.md`
- `copilot-instructions.md`
- `.windsurfrules`
- `.aider.conf.yml`

Diretórios:
- `.bmad-core/`
- `.agents/`
- `.cursor/`
- `.continue/`

Além disso, grep por arquivos fora de `CLAUDE.md`/`docs/constitution.md`/`.claude/*` contendo padrões de instrução:
- `^You are`
- `^You're`
- `^Your role`
- `^As an agent`

## Output

Imprime lista de violações no terminal. Exit 0 se limpo, exit 1 se encontrou qualquer violação.

## Implementação
```bash
bash scripts/hooks/forbidden-files-scan.sh
```

## Quando rodar
- Ao clonar o repo em máquina nova
- Após merge de PR que tocou configs
- Quando `session-start.sh` falhar sem motivo aparente
- Antes de cada `/guide-check`

## Pré-condições

Nenhuma — pode ser executada a qualquer momento, em qualquer estado do projeto.

## Agentes

Nenhum — executada pelo orquestrador.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| Script `scripts/hooks/forbidden-files-scan.sh` não existe ou sem permissão | Verificar integridade do harness. Rodar `/guide-check` para diagnóstico completo. O script é selado — se ausente, pode indicar tampering. |
| Scan encontra arquivos proibidos (exit 1) | Listar cada violação. Remover os arquivos proibidos (com confirmação do PM). Registrar incidente em `docs/incidents/` se a origem for desconhecida. |
| Falso positivo (arquivo legítimo contém padrão `^You are`) | Verificar se o arquivo está na lista de exceções (`CLAUDE.md`, `docs/constitution.md`, `.claude/*`). Se for legítimo, ajustar o script de scan (via procedimento de relock do harness). |

## Output obrigatório (auditável)

Toda invocação de `/forbidden-files-scan` emite o artefato JSON rastreável em `docs/audits/forbidden-files-scan-YYYY-MM-DD.json` antes de encerrar. O arquivo é append-only — nunca sobrescrito no mesmo dia; se já existir, adicionar sufixo `-HHMM`.

### Schema

```json
{
  "$schema": "harness-audit-v1",
  "skill": "forbidden-files-scan",
  "timestamp": "2026-04-16T14:23:05Z",
  "verdict": "pass | fail",
  "findings_count": 0,
  "findings": [
    {
      "severity": "S1",
      "path": ".cursorrules",
      "pattern_matched": ".cursorrules",
      "type": "file | directory | content_pattern",
      "recommendation": "remover após confirmação PM; registrar incidente"
    }
  ],
  "evidence": {
    "patterns_checked_files": [".cursorrules", "AGENTS.md", "GEMINI.md", "copilot-instructions.md", ".windsurfrules", ".aider.conf.yml"],
    "patterns_checked_dirs": [".bmad-core/", ".agents/", ".cursor/", ".continue/"],
    "content_patterns": ["^You are", "^You're", "^Your role", "^As an agent"],
    "exceptions": ["CLAUDE.md", "docs/constitution.md", ".claude/*"],
    "files_scanned_count": 0,
    "violations_found": [],
    "script_exit_code": 0
  }
}
```

- `verdict = pass` quando `violations_found` é vazio e `script_exit_code == 0`.
- `verdict = fail` em qualquer match (todos S1 — violação de R1 é crítica).

## Evidência de execução

A skill não é considerada executada sem o artefato JSON gravado. O orquestrador valida a existência do arquivo antes de prosseguir. Sem o artefato, a invocação é reexecutada.

## Lifecycle do artefato

- **Retenção:** permanente (append-only histórico).
- **Localização:** `docs/audits/forbidden-files-scan-*.json`.
- **Referência em retrospectivas:** `governance` (modo retrospective) consulta para detectar tentativas de contaminação recorrentes.
- **Auditoria SOC 2:** compõe audit trail de enforcement de R1 (fonte única de instrução).
- **Nunca deletar:** incidentes antigos são prova de enforcement contínuo.

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador executa hook selado).
- **Gates produzidos:** não é gate de slice; é guardrail de R1.
- **Output:** `docs/audits/forbidden-files-scan-YYYY-MM-DD.json` (schema `harness-audit-v1`).
- **Schema formal:** `docs/protocol/schemas/harness-audit-v1.schema.json` (v1.0.0 — formalizado em 2026-04-16; campos obrigatorios: `$schema`, `audit_type`, `timestamp`, `verdict`, `findings_count`, `findings`, `evidence`).
- **Isolamento R3:** não aplicável (sem sub-agent).
- **Ordem no pipeline:** complementar a SessionStart hook; invocado ad hoc ou antes de `/guide-check`.
