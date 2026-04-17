---
description: Roda gate de seguranca independente (isolado por hook, sem worktree). Monta security-review-input/, spawn security-expert (security-gate), valida JSON contra schema. Gate obrigatorio antes de merge. Uso: /security-review NNN.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-A1 (Output no chat R12)"
---

# /security-review

## Uso
```
/security-review NNN
```

## Por que existe
Seguranca nao pode ser avaliada pelo mesmo agente que implementou. O `security-expert` (modo: security-gate) opera em sandbox via `scripts/hooks/verifier-sandbox.sh`, sem acesso ao contexto do builder (implementer), e avalia OWASP top 10, LGPD, secrets e input validation.

## Quando invocar
Apos `/verify-slice NNN` retornar `approved`. Parte do pipeline de gates antes do merge.

## Pre-condicoes (validadas)
1. `specs/NNN/spec.md` existe
2. `specs/NNN/verification.json` existe com `verdict: approved`
3. `specs/NNN/review.json` existe com `verdict: approved`
4. Arquivos de codigo do slice identificados

## O que faz

### 0. Rodar scans mecanicos ANTES do agente
```bash
bash scripts/security-scan.sh NNN
```
Se falhar (exit != 0), o `security-expert` (modo: security-gate) NAO e spawnado. Corrigir vulnerabilidades primeiro.

### 1. Montar `security-review-input/`
- `spec.md` — copia do spec
- `files-changed.txt` — `git diff --name-only` do slice
- `source/` — copia dos arquivos de codigo alterados
- `threat-model.md` — copia de `docs/security/threat-model.md`
- `lgpd-base-legal.md` — copia de `docs/security/lgpd-base-legal.md`
- `constitution-snapshot.md` — copia da constitution

### 2. Spawn security-expert (modo: security-gate) (sem worktree)
```
Agent(subagent_type="security-expert")
```
**Nota:** NAO usar `isolation: "worktree"`. O input package e untracked e nao existiria na worktree. O isolamento e garantido pelo hook `verifier-sandbox.sh` que restringe reads ao diretorio de input.

### 3. Validar output
Validar `security-review.json` contra `docs/protocol/schemas/gate-output.schema.json`.
Rejeitar outputs invalidos.

### 4. Apresentar ao PM

**Caso approved:**
```
🔒 Revisao de seguranca: APROVADO

Nenhum problema critico ou alto encontrado.
Verificacoes LGPD: todas passaram.

Proximo gate: /test-audit NNN
```

**Caso rejected:**
```
🔒 Revisao de seguranca: REPROVADO

Problemas encontrados:
🔴 SEC-001 [critico]: Query SQL sem parametrizacao em src/foo.php:42
🟠 SEC-002 [alto]: Token de API exposto em config/services.php:15

Acao necessaria: corrigir os problemas de seguranca.
→ /fix NNN security
```

### 5. Persistir resultado
Copiar `security-review.json` para `specs/NNN/security-review.json`.
Atualizar `project-state.json` gates_status.
Registrar em telemetria.

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `verification.json` ou `review.json` não existe ou não está `approved` | Rodar `/verify-slice NNN` e `/review-pr NNN` primeiro. Security review é o 3o gate. |
| Sandbox via `verifier-sandbox.sh` falha ao ser criada | Verificar espaço em disco e permissões de `$TMPDIR`. Tentar novamente. Se persistir, reportar erro ao PM. |
| `security-review.json` não passa validação do schema | Descartar output inválido e re-executar o `security-expert` (modo: security-gate). Se persistir, verificar schema em `docs/protocol/schemas/gate-output.schema.json`. |
| `docs/security/threat-model.md` não existe | Alertar PM que threat model é necessário. Criar esqueleto mínimo antes de prosseguir. |

## Agentes

- **security-expert** (modo: security-gate) — executado em sandbox via `scripts/hooks/verifier-sandbox.sh` (read-only mount), sem acesso ao contexto do builder. Emite `security-review.json`.

## Pré-condições

- `specs/NNN/verification.json` existe com `verdict: approved`.
- `specs/NNN/review.json` existe com `verdict: approved`.
- `specs/NNN/spec.md` existe.
- Arquivos de código do slice identificados via `git diff`.

## Handoff
- `approved` → proximo gate (`/test-audit NNN`)
- `rejected` → `/fix NNN security-gate` → re-run `/security-review NNN`
- 6 rejeicoes consecutivas → R6 escalacao

## Conformidade com protocolo v1.2.4

- **Agent invocado:** `security-expert (security-gate)` — conforme mapa canonico 00 §3.1
- **Gate name (enum):** `security-gate`
- **Output:** `specs/NNN/security-review.json`
- **Schema:** `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios)
- **Criterios objetivos:** `docs/protocol/04-criterios-gate.md §3`
- **Isolamento R3:** gate roda em instancia isolada com `isolation_context` unico
- **Zero-tolerance:** `verdict: approved` somente com `blocking_findings_count == 0`

## Output no chat (para PM — R12)

Ao fim da execucao, apresentar ao PM em ate 3 linhas de linguagem de produto:

1. **Veredicto:** frase unica em PT-BR sem jargao — ex: "A revisao de seguranca do slice NNN passou sem pontos abertos."
2. **Proxima etapa:** acao unica recomendada — ex: "Posso seguir para a auditoria de testes (/test-audit NNN)."
3. **Se rejeitado:** "Encontrei N pontos de seguranca para ajustar. Vou corrigir automaticamente e reexecutar o gate."

Nunca jogar o security-review.json cru, CVE codes, stack trace ou trechos de codigo vulneravel ao PM. Detalhes tecnicos ficam em `specs/NNN/security-review.json` para uso do builder (fixer).
