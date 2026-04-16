# 04 — Criterios Objetivos de Gate

Versao: 1.0.0 — 2026-04-16

Cada gate do pipeline possui checklist binario (passa/falha) e limiares numericos mensuraveis. Zero findings S1-S3 bloqueiam aprovacao. Findings S4 e S5 nao bloqueiam aprovacao mas devem ser registrados no JSON de saida. Evidencia obrigatoria: JSON de saida + logs de ferramentas.

---

## 0. Inputs por trilha

Os inputs obrigatorios de cada gate variam conforme a trilha de complexidade do slice. Consultar `docs/protocol/02-trilhas-complexidade.md` secao "Matriz de inputs por trilha" para a tabela completa.

**Regras por trilha para o gate verify:**

- **L1 (Hotfix):** verify checa somente: testes passam (incluindo teste de regressao), lint OK (Pint), types OK (PHPStan), nenhum arquivo proibido tocado. spec.md e plan.md NAO sao inputs obrigatorios — substituidos por bug-brief.md e fix-strategy.md respectivamente.
- **L2 (Small Change):** verify checa: testes passam (se existem), lint OK (Pint), types OK (PHPStan), spec-lite.md existe. plan.md NAO e input obrigatorio.
- **L3 (Standard):** verify checa todos os criterios completos conforme secao 1 abaixo. spec.md e plan.md sao inputs obrigatorios.
- **L4 (High-Risk):** mesmos criterios de L3 com adicao de security pre-review aprovado como pre-condicao.

**Regra normativa:** o gate deve verificar o campo `lane` no frontmatter do spec (ou bug-brief/spec-lite) e aplicar os criterios correspondentes. Se o campo `lane` estiver ausente, o gate deve emitir finding S2 com `description: "Campo lane ausente no frontmatter"`.

---

## 1. Gate: qa-expert (verify)

**Agente responsavel:** qa-expert
**Trigger:** apos commit do builder
**Input:** codigo commitado no branch do slice + spec.md + plan.md
**Output:** `specs/NNN/verification.json`
**Evidencia:** saida do Pest, PHPStan, Pint anexada ao JSON

### 1.1 Checklist binario

- [ ] Todos os testes Pest passam (exit code 0)
- [ ] PHPStan level 9 passa com 0 erros
- [ ] Pint formatting passa com 0 alteracoes
- [ ] Nenhum arquivo fora do escopo do slice foi modificado
- [ ] Nenhum arquivo proibido foi tocado (.env, credentials, arquivos selados conforme CLAUDE.md §9)
- [ ] Migrations sao reversiveis (possuem metodo `down()`)
- [ ] Nenhum `dd()`, `dump()`, `var_dump()` ou `Log::debug()` presente no codigo commitado

### 1.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Tempo de execucao dos testes do slice | < 60 segundos | `pest --filter=slice-NNN` tempo de wall-clock |
| Delta de arquivos vs plan.md | plan.md expected files +/- 2 | `git diff --stat` comparado com plan.md §files |

### 1.3 Formato de evidencia

```json
{
  "gate": "verify",
  "agent": "qa-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "pest_exit_code": 0,
    "pest_tests_passed": 12,
    "pest_execution_time_s": 8.3,
    "phpstan_errors": 0,
    "pint_changes": 0,
    "files_outside_scope": [],
    "forbidden_files_touched": [],
    "migrations_reversible": true,
    "debug_statements_found": []
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 2. Gate: architecture-expert (code-review)

**Agente responsavel:** architecture-expert (modo: code-review)
**Trigger:** somente apos gate verify aprovado
**Input:** diff do slice + codigo fonte + ADRs ativos
**Output:** `specs/NNN/review.json`
**Evidencia:** trechos de codigo referenciados por file:line

### 2.1 Checklist binario

- [ ] Nenhuma duplicacao de codigo (>10 linhas identicas)
- [ ] Todas as classes/metodos seguem convencoes de nomenclatura (PSR-12, convencoes Laravel)
- [ ] Nenhuma god class (>300 linhas) ou fat controller (>5 metodos)
- [ ] Nenhum SQL cru sem parameter binding
- [ ] Nenhuma logica de negocio em controllers (deve estar em Services/Actions)
- [ ] Todas as rotas novas possuem middleware
- [ ] Nenhum import/variavel nao utilizado
- [ ] Aderencia aos ADRs ativos verificada

### 2.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Complexidade ciclomatica | < 10 por metodo | Analise estatica (PHPStan/Psalm ou manual) |
| Tamanho de classe | < 300 linhas | `wc -l` por arquivo de classe |
| Tamanho de metodo | < 50 linhas | Contagem manual ou ferramenta |

### 2.3 Formato de evidencia

```json
{
  "gate": "review",
  "agent": "architecture-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "max_cyclomatic_complexity": 7,
    "max_class_length": 180,
    "max_method_length": 32,
    "duplications_found": 0,
    "god_classes": [],
    "fat_controllers": [],
    "raw_sql_without_binding": [],
    "business_logic_in_controllers": [],
    "routes_without_middleware": [],
    "unused_imports": [],
    "adr_adherence_checked": ["ADR-0001", "ADR-0002"]
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 3. Gate: security-expert (security-gate)

**Agente responsavel:** security-expert
**Trigger:** em paralelo com gates condicionais, apos review aprovado
**Input:** diff do slice + codigo fonte + composer.lock
**Output:** `specs/NNN/security-review.json`
**Evidencia:** saida do `composer audit` + trechos de codigo referenciados

### 3.1 Checklist binario

- [ ] Nenhum vetor de SQL injection (todas as queries usam Eloquent ou parameter binding)
- [ ] Nenhum vetor de XSS (toda saida escapada via Blade `{{ }}` ou props Inertia)
- [ ] Nenhuma vulnerabilidade de mass assignment (`$fillable` ou `$guarded` em todos os models)
- [ ] Nenhum secret/credencial/API key hardcoded
- [ ] Protecao CSRF em todas as rotas que alteram estado
- [ ] Verificacao de autorizacao em todo metodo de controller (Policy ou Gate)
- [ ] Isolamento de tenant: todas as queries filtradas por `tenant_id`
- [ ] Nenhum PII logado sem mascaramento
- [ ] `composer audit` retorna 0 CVEs high/critical
- [ ] Rate limiting em endpoints publicos

### 3.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Cobertura OWASP Top 10 | 10/10 categorias verificadas | Checklist manual documentado |
| Isolamento de tenant | 100% das queries filtradas | Grep por queries sem tenant_id scope |

### 3.3 Formato de evidencia

```json
{
  "gate": "security-gate",
  "agent": "security-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "sql_injection_vectors": [],
    "xss_vectors": [],
    "mass_assignment_unprotected": [],
    "hardcoded_secrets": [],
    "csrf_unprotected_routes": [],
    "unauthorized_controller_methods": [],
    "unscoped_queries": [],
    "pii_logged_unmasked": [],
    "composer_audit_high_critical": 0,
    "rate_limiting_missing": [],
    "owasp_categories_checked": 10
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 4. Gate: qa-expert (audit-tests)

**Agente responsavel:** qa-expert
**Trigger:** em paralelo com security-gate, apos review aprovado
**Input:** testes do slice + spec.md (ACs) + relatorio de cobertura
**Output:** `specs/NNN/test-audit.json`
**Evidencia:** relatorio de cobertura + mapeamento AC→teste

### 4.1 Checklist binario

- [ ] Todo AC possui ao menos 1 teste
- [ ] Casos de borda testados (null, vazio, limite, nao autorizado)
- [ ] Nenhum teste depende de ordem de execucao
- [ ] Nenhum teste usa `sleep()` ou timestamps fixos
- [ ] Nenhum teste mocka o banco de dados (deve usar DB real conforme ADR)
- [ ] Assertions sao especificas (nenhum `assertTrue(true)` ou equivalente)

### 4.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Cobertura de linhas em codigo novo | >= 80% | `pest --coverage --min=80` |
| Cobertura de ACs | 100% (todo AC tem teste) | Mapeamento manual AC→teste |
| Densidade de assertions | >= 2 assertions por metodo de teste | Contagem de assert* por metodo |

### 4.3 Formato de evidencia

```json
{
  "gate": "audit-tests",
  "agent": "qa-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "ac_coverage_map": {
      "AC-001": ["tests/Feature/ExampleTest.php::test_ac_001"],
      "AC-002": ["tests/Feature/ExampleTest.php::test_ac_002"]
    },
    "line_coverage_percent": 85.2,
    "assertion_density_min": 2,
    "order_dependent_tests": [],
    "sleep_usage": [],
    "db_mocks": [],
    "trivial_assertions": []
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 5. Gate: product-expert (functional-gate)

**Agente responsavel:** product-expert
**Trigger:** em paralelo com security-gate, apos review aprovado
**Input:** spec.md (ACs + jornadas) + codigo + testes
**Output:** `specs/NNN/functional-review.json`
**Evidencia:** mapeamento AC→jornada + resultado de testes E2E (se aplicavel)

### 5.1 Checklist binario

- [ ] Cada AC verificado contra a jornada do usuario (nao apenas codigo)
- [ ] Happy path testado
- [ ] Caminhos de erro testados (input invalido, acesso nao autorizado, not found)
- [ ] Multi-tenant: verificado com 2+ contextos de tenant diferentes
- [ ] RBAC: verificado com diferentes niveis de permissao
- [ ] Testes de UI (Pest Browser ou Playwright) para slices com alteracoes de frontend

### 5.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Cobertura de ACs | 100% | Mapeamento AC→verificacao |
| Isolamento de tenant verificado | Minimo 2 tenants | Testes com tenant_id distintos |

### 5.3 Formato de evidencia

```json
{
  "gate": "functional-gate",
  "agent": "product-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "ac_verification": {
      "AC-001": {"happy_path": true, "error_paths": true, "multi_tenant": true, "rbac": true},
      "AC-002": {"happy_path": true, "error_paths": true, "multi_tenant": true, "rbac": true}
    },
    "tenants_tested": 2,
    "permission_levels_tested": ["admin", "user", "guest"],
    "ui_tests_required": false,
    "ui_tests_passed": null
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 6. Gate: data-expert (data-gate) [condicional]

**Agente responsavel:** data-expert
**Trigger:** condicional — somente quando o slice inclui migrations, alteracoes de schema ou queries novas significativas
**Input:** migrations + queries novas + ERD do epico
**Output:** `specs/NNN/data-review.json`
**Evidencia:** saida de migration dry-run + analise de queries

### 6.1 Checklist binario

- [ ] Migration possui metodo `down()` reversivel
- [ ] Nenhum `ALTER TABLE` com lock exclusivo em tabelas grandes sem estrategia documentada
- [ ] Foreign keys possuem comportamento `ON DELETE` correto
- [ ] Indices existem para todas as colunas em `WHERE`/`JOIN` de queries novas
- [ ] `tenant_id` presente em todas as chaves compostas/indices onde aplicavel
- [ ] Nenhuma query N+1 (verificado com Laravel Debugbar ou assertions de contagem de queries)

### 6.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Contagem de queries por page load | < 20 | Query count assertions ou Debugbar |
| Tempo estimado de execucao de migration | < 30 segundos em 100k linhas | Estimativa baseada em tipo de operacao |

### 6.3 Formato de evidencia

```json
{
  "gate": "data-gate",
  "agent": "data-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "migrations_reversible": true,
    "exclusive_locks_without_strategy": [],
    "foreign_key_on_delete_correct": true,
    "missing_indexes": [],
    "tenant_id_in_composites": true,
    "n_plus_1_queries": [],
    "max_queries_per_page": 12,
    "migration_estimated_time_s": 5
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 7. Gate: observability-expert (observability-gate) [condicional]

**Agente responsavel:** observability-expert
**Trigger:** condicional — somente quando o slice adiciona dependencias, endpoints criticos ou operacoes auditaveis
**Input:** codigo do slice + configuracao de logging + health checks
**Output:** `specs/NNN/observability-review.json`
**Evidencia:** amostra de log + resultado do health check

### 7.1 Checklist binario

- [ ] Logging usa formato estruturado (JSON via Monolog)
- [ ] Nenhum PII em mensagens de log (email, CPF, telefone devem ser mascarados)
- [ ] Endpoint de health check atualizado se nova dependencia adicionada
- [ ] Operacoes criticas possuem entradas de audit trail

### 7.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Nivel de log apropriado | Nenhum INFO para debug, nenhum ERROR para fluxos esperados | Revisao manual de log statements |

### 7.3 Formato de evidencia

```json
{
  "gate": "observability-gate",
  "agent": "observability-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "structured_logging": true,
    "pii_in_logs": [],
    "health_check_updated": true,
    "audit_trail_entries": ["ClientCreated", "ClientUpdated"],
    "inappropriate_log_levels": []
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 8. Gate: integration-expert (integration-gate) [condicional]

**Agente responsavel:** integration-expert
**Trigger:** condicional — somente quando o slice envolve chamadas a APIs externas, webhooks ou integracao com servicos terceiros
**Input:** codigo de integracao + contratos de API + configuracao de retry
**Output:** `specs/NNN/integration-review.json`
**Evidencia:** validacao de contrato + configuracao de timeout/retry

### 8.1 Checklist binario

- [ ] Chamadas a APIs externas encapsuladas em try/catch com timeout
- [ ] Circuit breaker ou retry com backoff exponencial configurado
- [ ] Chave de idempotencia em todas as chamadas externas que alteram estado
- [ ] Endpoints de webhook validam assinatura/origem
- [ ] Credenciais de APIs externas em variaveis de ambiente, nunca hardcoded

### 8.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Timeout configurado | <= 30 segundos por chamada externa | Revisao de configuracao HTTP client |
| Limite de retry | <= 3 tentativas | Revisao de configuracao de retry |

### 8.3 Formato de evidencia

```json
{
  "gate": "integration-gate",
  "agent": "integration-expert",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "external_calls_without_try_catch": [],
    "missing_timeout": [],
    "missing_circuit_breaker": [],
    "missing_idempotency_key": [],
    "webhook_without_signature_validation": [],
    "hardcoded_credentials": [],
    "max_timeout_s": 15,
    "max_retry_count": 3
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 9. Gate: governance (master-audit)

**Agente responsavel:** governance
**Trigger:** somente apos TODOS os gates anteriores aprovados com zero findings S1-S3
**Input:** todos os JSONs de gate anteriores + codigo do slice
**Output:** `specs/NNN/master-audit.json`
**Evidencia:** verdicts de ambas as trilhas LLM + justificativa consolidada

### 9.1 Checklist binario

- [ ] Todas as saidas de gates anteriores coletadas e analisadas
- [ ] Verdict de Claude Opus 4.6 registrado
- [ ] Verdict de GPT-5 registrado (via Codex CLI)
- [ ] Ambos os verdicts concordam OU reconciliacao documentada
- [ ] Verdict final justificado com evidencia de cada gate

### 9.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Concordancia | Ambos os LLMs devem chegar ao mesmo verdict | Comparacao de verdicts |
| Rodadas de reconciliacao | Maximo 3 se divergentes | Contagem de rodadas |

### 9.3 Formato de evidencia

```json
{
  "gate": "master-audit",
  "agent": "governance",
  "slice": "NNN",
  "verdict": "approved|rejected",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings": [],
  "evidence": {
    "claude_verdict": "approved",
    "gpt5_verdict": "approved",
    "verdicts_agree": true,
    "reconciliation_rounds": 0,
    "gate_inputs_analyzed": [
      "verification.json",
      "review.json",
      "security-review.json",
      "test-audit.json",
      "functional-review.json"
    ],
    "conditional_gates_analyzed": [],
    "justification": "Todos os gates aprovaram com zero findings S1-S3. Codigo aderente a spec e ADRs."
  },
  "timestamp": "ISO-8601",
  "commit_hash": "sha"
}
```

---

## 10. Regras transversais

1. **Zero tolerance (S1-S3):** nenhum gate pode aprovar com findings S1, S2 ou S3 pendentes. Findings S4 (minor) e S5 (advisory) nao bloqueiam aprovacao mas devem ser registrados no JSON de saida. S4 findings sao rastreados como divida tecnica em `project-state.json`. S5 findings sao registrados em telemetria apenas. Um gate emite `verdict: approved` quando `blocking_findings_count == 0`, mesmo que `non_blocking_findings_count > 0`.

2. **Loop de correcao:** gate rejeita → builder (fixer) corrige TODOS os findings S1-S3 → re-run do MESMO gate → repete ate `blocking_findings_count == 0`. As 5 primeiras reprovacoes consecutivas do mesmo gate ficam no loop automatico; a 6a escala ao PM via `/explain-slice NNN` (R6).

3. **Contexto isolado:** cada gate deve executar em contexto isolado conforme R3. O agente que executa o gate nao pode ter visto a saida de outro gate do mesmo slice.

4. **Imutabilidade de evidencia:** apos o gate emitir o JSON, o arquivo nao pode ser editado. Nova execucao gera novo arquivo (versionado por timestamp ou sobrescreve com novo commit hash).

5. **Ordem de execucao:**
   - verify (1o, qa-expert) → code-review (2o, architecture-expert, somente se verify aprovado) → [security-gate + audit-tests + functional-gate + gates condicionais] (3o, em paralelo) → master-audit (4o, somente se todos os anteriores aprovados com zero findings S1-S3).

6. **Gates condicionais:** data-gate, observability-gate e integration-gate somente devem executar quando o slice inclui alteracoes relevantes ao dominio do gate. O orchestrator decide a ativacao com base no plan.md do slice.
