# 06 — Estrategia de Evidencias

Versao: 1.2.0 — 2026-04-16

Changelog 1.2.0: path do harness-learner corrigido para `docs/governance/harness-learner-ENN.md` alinhado com 03 §7.2 (antes divergente em 2.6 e 5.2).

Todo gate e toda transicao de fase devem produzir evidencia verificavel. Nenhuma aprovacao pode ser inferida — deve ser comprovada por artefato rastreavel.

---

## 1. Evidencias por gate

### 1.1 Gate verify (qa-expert)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/verification.json` |
| Evidencia secundaria | Log de saida do Pest (stdout), saida do PHPStan, saida do Pint |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente (versionada no repositorio) |
| Conteudo minimo | verdict, findings, pest_exit_code, pest_tests_passed, pest_execution_time_s, phpstan_errors, pint_changes, commit_hash, timestamp |
| Verificacao de autenticidade | `commit_hash` deve corresponder ao HEAD do branch no momento da execucao; timestamp em ISO-8601; arquivo commitado no repositorio |

### 1.2 Gate review (architecture-expert, modo: code-review)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/review.json` |
| Evidencia secundaria | `git diff` do slice (gerado automaticamente) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, max_cyclomatic_complexity, max_class_length, max_method_length, adr_adherence_checked, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + arquivo commitado |

### 1.3 Gate security-gate (security-expert)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/security-review.json` |
| Evidencia secundaria | Saida do `composer audit` (texto completo) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, owasp_categories_checked, composer_audit_high_critical, unscoped_queries, hardcoded_secrets, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + saida do composer audit anexada ou referenciada |

### 1.4 Gate audit-tests (qa-expert)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/test-audit.json` |
| Evidencia secundaria | Relatorio de cobertura (HTML em `storage/coverage/` ou texto no JSON) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, ac_coverage_map, line_coverage_percent, assertion_density_min, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + cobertura verificavel via re-execucao |

### 1.5 Gate functional-gate (product-expert)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/functional-review.json` |
| Evidencia secundaria | Screenshots de testes E2E (se slice tem UI), armazenados em `specs/NNN/screenshots/` |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, ac_verification (por AC: happy_path, error_paths, multi_tenant, rbac), tenants_tested, permission_levels_tested, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + screenshots com nome padronizado `AC-NNN-<cenario>.png` |

### 1.6 Gate data-gate (data-expert) [condicional]

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/data-review.json` |
| Evidencia secundaria | Saida de migration dry-run (`php artisan migrate --pretend`) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, migrations_reversible, missing_indexes, n_plus_1_queries, max_queries_per_page, migration_estimated_time_s, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + saida do dry-run anexada |

### 1.7 Gate observability-gate (observability-expert) [condicional]

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/observability-review.json` |
| Evidencia secundaria | Amostra de saida de log (formato JSON estruturado) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, structured_logging, pii_in_logs, health_check_updated, audit_trail_entries, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp |

### 1.8 Gate integration-gate (integration-expert) [condicional]

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/integration-review.json` |
| Evidencia secundaria | Saida de validacao de contrato de API (se aplicavel) |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, external_calls_without_try_catch, missing_timeout, max_timeout_s, max_retry_count, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp |

### 1.9 Gate master-audit (governance)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia primaria | `specs/NNN/master-audit.json` |
| Evidencia secundaria | Saida do Codex CLI (trilha GPT-5), armazenada em `specs/NNN/codex-output.log` |
| Local de armazenamento | `specs/NNN/` |
| Retencao | Permanente |
| Conteudo minimo | verdict, findings, claude_verdict, gpt5_verdict, verdicts_agree, reconciliation_rounds, gate_inputs_analyzed, justification, commit_hash, timestamp |
| Verificacao de autenticidade | commit_hash + timestamp + codex-output.log com hash do modelo GPT-5 |

---

## 2. Evidencias de transicao de fase

### 2.1 Fase A → Fase B (freeze-prd)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | `project-state.json` com `prd_status: "frozen"` + registro de aprovacao do PM na conversa |
| Quem assina | PM |
| Local | `project-state.json` (raiz do repositorio) |
| Conteudo minimo | timestamp da aprovacao, commit hash do PRD frozen, referencia ao intake |
| Verificacao | `project-state.json[prd_status] === "frozen"` + commit que alterou o status presente no historico |

### 2.2 Fase B → Fase C (freeze-architecture)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | Todos os ADRs criados em `docs/adr/` + threat model em `docs/security/` + `project-state.json` com `architecture_status: "frozen"` + aprovacao do PM |
| Quem assina | PM |
| Local | `project-state.json` + `docs/adr/` + `docs/security/` |
| Conteudo minimo | lista de ADRs referenciados, timestamp, commit hash |
| Verificacao | Existencia dos ADRs listados + `project-state.json[architecture_status] === "frozen"` |

### 2.3 Fase C → Fase D (plan approved)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | `specs/NNN/spec-audit.json` com `verdict: "approved"` + `specs/NNN/plan-review.json` com `verdict: "approved"` (ambos com `findings: []`) |
| Quem assina | Automatico (orchestrator) — nao requer PM |
| Local | `specs/NNN/` |
| Conteudo minimo | verdicts de ambos os auditores, zero findings |
| Verificacao | Ambos os JSONs presentes com `verdict: "approved"` e `findings: []` |

### 2.4 Fase D → Fase E (code ready)

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | Todos os testes verdes (Pest exit code 0) + codigo commitado no branch do slice |
| Quem assina | builder (via commit) |
| Local | Branch do slice no repositorio |
| Conteudo minimo | commit hash com testes passando, mensagem de commit padronizada |
| Verificacao | `git log` mostra commit do builder + re-execucao de `pest` confirma exit 0 |

### 2.5 Fase E → Merge

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | TODOS os gates aprovados com zero findings S1-S3 (`blocking_findings_count == 0`) + `specs/NNN/master-audit.json` com `verdict: "approved"` |
| Quem assina | governance (via master-audit) |
| Local | `specs/NNN/` (todos os JSONs de gate) |
| Conteudo minimo | Todos os JSONs de gate presentes com `verdict: "approved"` e `blocking_findings_count == 0` |
| Verificacao | Script de merge (`scripts/merge-slice.sh`) deve validar existencia e conteudo de todos os JSONs antes de prosseguir |

### 2.6 Fase F — Retrospectiva

| Aspecto | Especificacao |
|---------|--------------|
| Evidencia | `docs/retrospectives/epic-ENN.md` + `docs/governance/harness-learner-ENN.md` |
| Quem assina | governance |
| Local | `docs/retrospectives/` (retrospectiva do epico) + `docs/governance/` (harness-learner) |
| Conteudo minimo | metricas do epico, licoes aprendidas, acoes corretivas, mudancas de harness propostas |
| Verificacao | Arquivos presentes e commitados + referenciados em `project-state.json` |

---

## 3. Regras de integridade de evidencia

### 3.1 Evidencia ausente

Quando qualquer evidencia obrigatoria estiver ausente:
- O orchestrator nao pode inferir aprovacao.
- O gate correspondente deve ser re-executado por completo.
- O motivo da ausencia deve ser registrado em `.claude/telemetry/` via `scripts/record-telemetry.sh`.

### 3.2 Evidencia corrompida

Quando qualquer JSON de evidencia estiver malformado ou com campos obrigatorios ausentes:
- O arquivo deve ser descartado (nao deletado — renomeado para `<nome>.corrupted.<timestamp>`).
- O gate deve ser re-executado.
- Incidente deve ser registrado em `docs/incidents/evidence-corruption-<timestamp>.md`.

### 3.3 Evidencia desatualizada

Evidencia e considerada desatualizada quando:
- O `commit_hash` no JSON nao corresponde ao HEAD atual do branch do slice.
- Houve commits adicionais apos a emissao da evidencia.

Neste caso:
- O gate deve ser re-executado contra o commit atual.
- A evidencia anterior permanece no historico git para auditoria.

### 3.4 Imutabilidade

- Nenhum agente pode editar um JSON de evidencia apos sua emissao.
- Correcoes geram nova execucao do gate, que sobrescreve o JSON com novo commit hash e timestamp.
- O historico de versoes anteriores fica preservado no git.

### 3.5 Rastreabilidade

Todo JSON de evidencia deve conter obrigatoriamente:
- `timestamp`: ISO-8601 com timezone
- `commit_hash`: SHA do commit avaliado
- `agent`: nome do agente que executou o gate
- `gate`: nome do gate
- `slice`: numero do slice
- `verdict`: "approved" ou "rejected"
- `findings`: array (pode conter S4/S5 mesmo se aprovado; vazio de S1-S3 se aprovado)

Estes campos permitem reconstruir a cadeia completa de evidencia para qualquer slice, em qualquer momento futuro.

---

## 4. Armazenamento e organizacao

```
specs/
  NNN/
    spec.md                      # Especificacao do slice
    plan.md                      # Plano tecnico
    tasks.md                     # Lista de tasks
    verification.json            # Gate verify
    review.json                  # Gate review
    security-review.json         # Gate security
    test-audit.json              # Gate audit-tests
    functional-review.json       # Gate functional
    data-review.json             # Gate data (condicional)
    observability-review.json    # Gate observability (condicional)
    integration-review.json      # Gate integration (condicional)
    master-audit.json            # Gate master-audit
    codex-output.log             # Saida GPT-5 (master-audit)
    screenshots/                 # Screenshots E2E (condicional)
      AC-001-happy-path.png
      AC-002-error-unauthorized.png
```

Telemetria operacional (tempos, contagens, metricas de processo) deve ser registrada em `.claude/telemetry/slice-NNN.jsonl` via `scripts/record-telemetry.sh` — formato append-only, protegido por `telemetry-lock.sh`.

---

## 5. Tabela resumo

### 5.1 Evidencias por gate

| Gate | Evidencia primaria | Evidencia secundaria | Local |
|------|--------------------|---------------------|-------|
| verify | verification.json + log Pest | PHPStan output, Pint output | specs/NNN/ |
| review | review.json | git diff do slice | specs/NNN/ |
| security-gate | security-review.json | composer audit output | specs/NNN/ |
| audit-tests | test-audit.json | relatorio de cobertura | specs/NNN/ |
| functional-gate | functional-review.json | screenshots E2E (se UI) | specs/NNN/ |
| data-gate | data-review.json | migration dry-run output | specs/NNN/ |
| observability-gate | observability-review.json | amostra de log | specs/NNN/ |
| integration-gate | integration-review.json | validacao de contrato API | specs/NNN/ |
| master-audit | master-audit.json | codex-output.log (GPT-5) | specs/NNN/ |

### 5.2 Evidencias de transicao de fase

| Transicao | Evidencia | Quem assina |
|-----------|----------|-------------|
| A→B (freeze-prd) | project-state.json[prd_status: frozen] + aprovacao PM | PM |
| B→C (freeze-architecture) | ADRs + threat model + project-state.json[architecture_status: frozen] + aprovacao PM | PM |
| C→D (plan approved) | spec-audit.json approved + plan-review.json approved (ambos zero findings) | Automatico (orchestrator) |
| D→E (code ready) | Testes verdes + codigo commitado | builder |
| E→merge | TODOS gates approved (zero S1-S3) + master-audit approved | governance |
| F (retrospective) | `docs/retrospectives/epic-ENN.md` + `docs/governance/harness-learner-ENN.md` | governance |

---

## 6. Auditoria sem bias (re-audit cego) — ADR slice 018

Auditoria inaugural e re-auditoria de um mesmo gate dentro de um slice seguem regras distintas para preservar R3/R11 (contextos isolados, decisão independente).

### 6.1 Princípio

- **1ª auditoria (inaugural):** auditor recebe o perímetro funcional completo (story + slice + paths-raiz autorizados) e decide onde investigar. Perímetro livre.
- **Re-auditoria (rodadas ≥ 2 do mesmo gate):** auditor recebe **o mesmo prompt inaugural, menos qualquer menção a rodadas anteriores**. Não deve saber que é re-auditoria.

### 6.2 Artefatos versionados

- `docs/protocol/audit-prompt-template.md` — template obrigatório com 6 campos: `story_id`, `slice_id`, `mode`, `perimeter_files`, `criteria_checklist`, `output_contract`.
- `docs/protocol/blocked-tokens-re-audit.txt` — lista fechada de tokens proibidos em re-auditoria (findings anteriores, verdicts prévios, commit hashes de fix, IDs de findings, `rodada N`).
- `scripts/validate-audit-prompt.sh --mode=(1st-pass|re-audit) <prompt-file>` — validator mecânico (exit 0 = limpo, exit 1 = contaminação com linha+token reportados).

### 6.3 Set-difference entre rodadas (orchestrator, não auditor)

Após cada rodada de re-auditoria, o orchestrator compara findings antigos e atuais por **assinatura semântica** (`categoria + descrição_normalizada + path_sem_linha`) via `scripts/audit-set-difference.sh --previous <a.json> --current <b.json>`:

- `resolved = prévios \ atuais` — findings fechados pelo fixer
- `unresolved = prévios ∩ atuais` — findings que persistem (fixer precisa trabalhar de novo)
- `new = atuais \ prévios` — findings novos (regressão ou gap do auditor anterior)

### 6.4 Recusa mecânica pelo sub-agent

Se um auditor detectar um token proibido no prompt apesar do validator (ex.: paráfrase criativa), o agent file instrui recusa antes de investigar artefatos:

```json
{
  "verdict": "rejected",
  "rejection_reason": "contaminated_prompt",
  "contamination_evidence": "<token ou passagem>"
}
```

O JSON passa pelo `validate-gate-output.sh` (schema-valid) e NÃO contém campos `evidence.ac_coverage_map` nem `evidence.checks` — provando abort antes de investigar (verificável via `jq '(.evidence // {} | has("ac_coverage_map") or has("checks"))' → false`).

### 6.5 Trade-offs

- **Custo:** auditoria inaugural ampla = mais tokens. Aceitável — viés custa mais caro (findings perdidos ou carimbados).
- **Set-difference semântico** é frágil quando fix move código entre arquivos. Normalização padrão (lowercase + trim + remove linenum) + tolerância de 5% de falso positivo aceita como dívida conhecida.
- **Retry por truncagem** respeita princípio: nova instância recebe o mesmo prompt original (mais imperativo), nunca a resposta pronta. R6 aplica (5 truncagens → escalar PM com prompt em texto puro).
