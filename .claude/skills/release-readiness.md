---
description: Valida se o projeto esta pronto para release. Checa todos os epicos/stories completos, gates aprovados, documentacao atualizada, testes verdes, seguranca OK. Gera relatorio final para PM. Uso: /release-readiness.
protocol_version: "1.2.4"
changelog: "2026-04-16 — quality audit fix SK-005"
---

# /release-readiness

## Uso
```
/release-readiness
```

## Por que existe
Release nao e "parece pronto". E um checklist objetivo que valida que tudo foi feito, testado, revisado e documentado. Nenhum release sem este gate.

## Quando invocar
Quando todos os epicos do MVP estiverem completos e o PM quiser lancar.

## Pre-condicoes
- Pelo menos 1 epico completo
- `project-state.json` existe

## O que faz

### 1. Checklist de Release

#### Produto
- [ ] Todos os epicos do MVP marcados como completos
- [ ] Todas as stories de cada epico com merge feito
- [ ] Nenhuma story com rejeicao R6 pendente
- [ ] PRD frozen e escopo respeitado

#### Qualidade
- [ ] Todos os slices com verification.json `approved`
- [ ] Todos os slices com review.json `approved`
- [ ] Todos os slices com security-review.json `approved`
- [ ] Todos os slices com test-audit.json `approved`
- [ ] Todos os slices com functional-review.json `approved`
- [ ] Nenhum finding critical/high pendente

#### Testes
- [ ] Suite completa roda verde
- [ ] Cobertura de AC: 100% dos ACs tem teste
- [ ] Testes E2E dos fluxos criticos passam

#### Seguranca
- [ ] Threat model revisado e atualizado
- [ ] Nenhum secret no repositorio
- [ ] Dependencias sem vulnerabilidades conhecidas criticas
- [ ] LGPD compliance checklist completo

#### Documentacao
- [ ] README atualizado
- [ ] ADRs completos e aceitos
- [ ] API documentada (se aplicavel)
- [ ] Runbook de operacao existe
- [ ] Changelog gerado

#### Operacao
- [ ] Healthcheck endpoint funcional
- [ ] Logs estruturados configurados
- [ ] Backup configurado
- [ ] Rollback testado
- [ ] Variaveis de ambiente documentadas
- [ ] Migracoes testadas em ambiente limpo

### 2. Executar validacoes automaticas
- Rodar suite de testes completa (unico momento permitido — P8)
- Verificar dependencias com vulnerabilidades
- Validar que nenhum .env ou secret esta commitado
- Verificar que todas as migracoes rodam em banco limpo

### 3. Emitir artefato JSON auditavel

Emitir `docs/release-readiness/<release_id>.json` conforme `docs/protocol/schemas/release-readiness.schema.json`.

**Exemplo de output (verdict `ready`):**
```json
{
  "$schema": "release-readiness-v1",
  "release_id": "v1.0.0-mvp",
  "timestamp": "2026-04-30T18:00:00Z",
  "verdict": "ready",
  "commit_hash": "abc1234",
  "pillars": {
    "produto": {"status": "ok", "epics_completed": 12, "epics_total": 12, "stories_merged": 47, "stories_total": 47, "prd_frozen": true, "r6_escalations_pending": 0},
    "qualidade": {"status": "ok", "slices_verified": 47, "slices_code_reviewed": 47, "slices_security_reviewed": 47, "slices_test_audited": 47, "slices_functional_reviewed": 47, "slices_master_audited": 47, "findings_s1_s3_open": 0},
    "testes": {"status": "ok", "full_suite_green": true, "ac_coverage_pct": 100, "e2e_critical_passing": true, "tests_total": 842, "tests_failing": 0},
    "seguranca": {"status": "ok", "threat_model_updated": true, "secrets_clean": true, "critical_vulnerabilities": 0, "lgpd_checklist_complete": true},
    "documentacao": {"status": "ok", "readme_updated": true, "adrs_accepted": true, "api_docs_present": true, "runbook_present": true, "changelog_generated": true},
    "operacao": {"status": "ok", "healthcheck_functional": true, "structured_logs": true, "backup_configured": true, "rollback_tested": true, "env_vars_documented": true, "migrations_clean_db_tested": true}
  },
  "findings_count": 0,
  "findings": [],
  "automatic_validations": {
    "full_test_suite": {"ran": true, "exit_code": 0, "tests_total": 842, "tests_passed": 842, "tests_failed": 0, "duration_seconds": 124.3},
    "vulnerability_scan": {"ran": true, "cves_critical": 0, "cves_high": 0, "cves_total": 0},
    "secrets_scan": {"ran": true, "secrets_found": 0},
    "migrations_clean_db": {"ran": true, "success": true, "migrations_total": 23}
  },
  "summary": "Projeto pronto para deploy em producao. Todos os 6 pilares verdes, zero findings, todas as validacoes automaticas passaram.",
  "next_action": "Aprovar deploy em producao"
}
```

**Exemplo de output (verdict `not_ready`):**
```json
{
  "$schema": "release-readiness-v1",
  "release_id": "v1.0.0-mvp-candidate-02",
  "timestamp": "2026-04-28T14:00:00Z",
  "verdict": "not_ready",
  "pillars": {
    "produto": {"status": "ok"},
    "qualidade": {"status": "blocked", "findings_s1_s3_open": 1},
    "testes": {"status": "blocked", "tests_failing": 2},
    "seguranca": {"status": "ok"},
    "documentacao": {"status": "pending", "runbook_present": false},
    "operacao": {"status": "pending", "rollback_tested": false}
  },
  "findings_count": 4,
  "findings": [
    {"id": "RR-001", "pillar": "qualidade", "severity": "S2", "slice": "015", "description": "slice-015 com security-review pendente", "recommendation": "Rodar /security-review 015"},
    {"id": "RR-002", "pillar": "testes", "severity": "S2", "description": "2 testes E2E de checkout falhando", "recommendation": "Corrigir testes E2E antes do release"},
    {"id": "RR-003", "pillar": "documentacao", "severity": "S3", "description": "Runbook de operacao nao existe", "recommendation": "Criar runbook em docs/ops/runbook.md"},
    {"id": "RR-004", "pillar": "operacao", "severity": "S3", "description": "Rollback nao testado em staging", "recommendation": "Rodar teste de rollback em staging antes do release"}
  ],
  "summary": "Release NAO esta pronto. 4 itens pendentes distribuidos em 4 pilares. Prioridade: corrigir slice-015 security-review e testes E2E (S2) antes de avancar para runbook e rollback (S3).",
  "next_action": "Rodar /security-review 015"
}
```

### 4. Apresentar ao PM (R12)

O conteudo do JSON e traduzido para linguagem de produto e apresentado ao PM assim:

**Caso pronto:**
```
🚀 Release Readiness: PRONTO

Checklist completo:
✅ Produto: 3/3 epicos completos, 12/12 stories merged
✅ Qualidade: todos os gates aprovados em todos os slices
✅ Testes: suite verde, cobertura 100% dos ACs
✅ Seguranca: sem findings pendentes, LGPD OK
✅ Documentacao: README, ADRs, runbook atualizados
✅ Operacao: healthcheck, logs, backup, rollback OK

O projeto esta pronto para deploy em producao.
Proximo passo: aprovar o deploy. Confirma? (sim/nao)
```

**Caso nao pronto:**
```
⚠️ Release Readiness: NAO PRONTO

Itens pendentes:
🔴 Qualidade: slice-015 com security-review pendente
🔴 Testes: 2 testes E2E falhando
🟠 Documentacao: runbook de operacao nao existe
🟡 Operacao: rollback nao testado

Acao recomendada:
1. Rodar /security-review 015
2. Corrigir testes E2E
3. Criar runbook em docs/ops/runbook.md

Quer que eu ajude com algum desses itens?
```

## Erros e Recuperação

| Cenário | Recuperação |
|---|---|
| `project-state.json` não existe | Rodar `/checkpoint` para gerar o estado atual antes de validar readiness. |
| Suite de testes falha durante validação | Identificar testes falhando, listar ao PM, e sugerir `/fix` para cada slice afetado. |
| Slice sem todos os gates aprovados | Listar gates pendentes e sugerir a sequência de gates faltantes para cada slice. |
| Vulnerabilidades críticas em dependências | Bloquear release, listar CVEs encontrados, e recomendar atualização de dependências. |

## Agentes

Nenhum — executada pelo orquestrador.

## Pré-condições

- Todos os épicos do MVP completos (todas as stories merged).
- `project-state.json` existe.
- Pelo menos 1 épico com todos os slices passando por todos os 5 gates.

## Handoff
- Tudo verde → PM aprova deploy
- Itens pendentes → listar acoes e ajudar a resolver

## Conformidade com protocolo v1.2.4

- **Agents invocados:** nenhum (orquestrador executa checklist agregado de todos os gates).
- **Gates produzidos:** meta-gate de release (NAO-canonico do pipeline por slice; nao pertence ao enum de 15 gates canonicos de `docs/protocol/00-protocolo-operacional.md §3.1`). Agrega outputs ja emitidos por todos os gates de slice (`gate-output.schema.json`) + validacoes automaticas de projeto (suite full, scan de CVEs, scan de secrets, teste de migrations em banco limpo).
- **Output:** `docs/release-readiness/<release_id>.json` conforme `docs/protocol/schemas/release-readiness.schema.json` (schema formalizado em 2026-04-16 — auditoria de qualidade v5 gap D8). Emite tambem relatorio PM-ready em linguagem de produto (R12) derivado dos campos `summary` e `next_action` do JSON.
- **Schema formal:** `docs/protocol/schemas/release-readiness.schema.json` (identificador `release-readiness-v1`, draft-07). Distinto de `gate-output.schema.json` (gates de slice emitem `approved|rejected`; este emite `ready|not_ready`) e de `harness-audit-v1.schema.json` (guardrails R1/R3/R16 emitem `pass|fail`). Os 6 pilares (produto, qualidade, testes, seguranca, documentacao, operacao) sao obrigatorios; verdict `ready` exige todos os 6 com status `ok` e `findings_count == 0`.
- **Isolamento R3:** nao aplicavel como gate de slice (este nao e gate de pipeline por slice; e meta-gate agregador). Os gates de slice que alimentam este relatorio ja passaram por R3 individualmente.
- **Ordem no pipeline:** ultimo meta-gate antes de deploy; roda apos todos os epicos MVP `merged` e apos `/retrospective` do ultimo epico.
