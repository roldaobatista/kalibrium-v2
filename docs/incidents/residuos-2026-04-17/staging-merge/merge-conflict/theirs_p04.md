# 04 — Criterios Objetivos de Gate

Versao: 1.2.2 — 2026-04-16

Changelog 1.2.2 (PATCH — meta-audit, L4-ready): criterios objetivos adicionados para os 6 gates que estavam no enum do schema mas nao em 04 — audit-spec (§10), audit-story (§11), audit-planning (§12), plan-review (§13), spec-security (§14, somente L4), guide-audit (§15). Secao 2 (gate review) agora explicita `gate_name: "review"` e `mode: "code-review"`.

Changelog 1.2.1 (PATCH — meta-audit): 9 exemplos JSON (§§1.3-9.3) atualizados para conformar com `docs/protocol/schemas/gate-output.schema.json` — adicionados campos obrigatorios `$schema`, `lane`, `mode`, `isolation_context`, `findings_by_severity`. Campo `agent` agora contem apenas nome do agent; campo `mode` separado conforme schema. Master-audit example inclui `reconciliation_failed: false`.

Changelog 1.2.0: protocolo formal de reconciliacao dual-LLM adicionado em 9.4 (prompt literal, rodadas 1-3, persistencia via E10). Schemas de evidencia agora referenciam 03 §8 como fonte unica; campos especificos movidos para bloco `evidence`.

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

O exemplo abaixo e conforme schema formal `docs/protocol/schemas/gate-output.schema.json` (14 campos obrigatorios + bloco `evidence` livre).

```json
{
  "$schema": "gate-output-v1",
  "gate": "verify",
  "slice": "NNN",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "verify",
  "verdict": "approved",
  "timestamp": "2026-04-16T14:30:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-verify-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
}
```

---

## 2. Gate: review

**Nome canonico do gate (enum):** `review`
**Agente responsavel:** architecture-expert (modo: `code-review`)
**Regra de nomenclatura:** no JSON de saida, `gate: "review"` e `mode: "code-review"`. Nao confundir — gate_name e `review`, modo do agente e `code-review`.
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
  "$schema": "gate-output-v1",
  "gate": "review",
  "slice": "NNN",
  "lane": "L3",
  "agent": "architecture-expert",
  "mode": "code-review",
  "verdict": "approved",
  "timestamp": "2026-04-16T14:45:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-review-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
  "$schema": "gate-output-v1",
  "gate": "security-gate",
  "slice": "NNN",
  "lane": "L3",
  "agent": "security-expert",
  "mode": "security-gate",
  "verdict": "approved",
  "timestamp": "2026-04-16T15:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-security-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
  "$schema": "gate-output-v1",
  "gate": "audit-tests",
  "slice": "NNN",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "audit-tests",
  "verdict": "approved",
  "timestamp": "2026-04-16T15:15:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-audit-tests-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
  "$schema": "gate-output-v1",
  "gate": "functional-gate",
  "slice": "NNN",
  "lane": "L3",
  "agent": "product-expert",
  "mode": "functional-gate",
  "verdict": "approved",
  "timestamp": "2026-04-16T15:30:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-functional-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
  "$schema": "gate-output-v1",
  "gate": "data-gate",
  "slice": "NNN",
  "lane": "L3",
  "agent": "data-expert",
  "mode": "data-gate",
  "verdict": "approved",
  "timestamp": "2026-04-16T15:45:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-data-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
  "$schema": "gate-output-v1",
  "gate": "observability-gate",
  "slice": "NNN",
  "lane": "L3",
  "agent": "observability-expert",
  "mode": "observability-gate",
  "verdict": "approved",
  "timestamp": "2026-04-16T16:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-observability-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "structured_logging": true,
    "pii_in_logs": [],
    "health_check_updated": true,
    "audit_trail_entries": ["ClientCreated", "ClientUpdated"],
    "inappropriate_log_levels": []
  }
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
  "$schema": "gate-output-v1",
  "gate": "integration-gate",
  "slice": "NNN",
  "lane": "L3",
  "agent": "integration-expert",
  "mode": "integration-gate",
  "verdict": "approved",
  "timestamp": "2026-04-16T16:15:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-integration-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
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
  }
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
- [ ] Verdict da trilha Claude (Opus) registrado
- [ ] Verdict da trilha GPT-5 registrado (via Codex CLI)
- [ ] Ambos os verdicts concordam OU reconciliacao documentada conforme secao 9.4
- [ ] Verdict final justificado com evidencia de cada gate

### 9.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta de medicao |
|---------|--------|-----------------------|
| Concordancia | Ambos os LLMs devem chegar ao mesmo verdict | Comparacao de verdicts |
| Rodadas de reconciliacao | Maximo 3 se divergentes | Contagem de rodadas |

### 9.4 Protocolo formal de reconciliacao dual-LLM

A reconciliacao e o mecanismo normativo que garante que o master-audit nao aprove ou rejeite um slice com base em opiniao unica. A independencia das trilhas deve ser preservada em cada rodada.

**Principio de independencia:** cada trilha (Claude + GPT-5) recebe o MESMO pacote de inputs (JSONs de gate + codigo + spec + plan) em contextos isolados e SEM acesso ao output da outra trilha na rodada atual.

**Rodada 1 — avaliacao independente:**

1. O orchestrator monta o pacote de inputs e dispara a trilha Claude e a trilha GPT-5 em paralelo, em contextos isolados.
2. Cada trilha produz um verdict parcial: `verdict`, `findings[]`, `justification`.
3. Se ambos concordarem (mesmo verdict E mesmo conjunto de findings S1-S3, a menos de equivalencia semantica), a reconciliacao encerra com sucesso e o master-audit.json registra `reconciliation_rounds: 0`.

**Rodada 2 — troca de evidencias:**

1. Se divergirem, cada trilha recebe o verdict + findings + justificativa da OUTRA trilha como input adicional.
2. Cada trilha deve reavaliar. Se for mudar de verdict, deve justificar em `reconciliation_reason`. Se mantiver, deve responder ponto-a-ponto aos findings divergentes do par.
3. O prompt de reconciliacao e o seguinte (normativo, literal):

```
Voce produziu o verdict <V1> para o slice <NNN> com os findings abaixo. Outra instancia LLM, auditando os mesmos inputs em contexto isolado, produziu o verdict <V2> com findings diferentes.

Verdict do par: <V2>
Findings do par: <FINDINGS_JSON>
Justificativa do par: <JUSTIFICATION>

Sua tarefa: reavaliar sua analise. Voce pode (a) manter seu verdict original, respondendo ponto-a-ponto aos findings do par; (b) mudar seu verdict com justificativa registrada em `reconciliation_reason`. Nao e aceitavel simplesmente deferir ao par sem analise propria.

Responda no mesmo schema do seu output original, acrescido dos campos: `reconciliation_round: 2`, `peer_verdict_considered: <V2>`, `reconciliation_reason: <texto ou null>`, `points_addressed: [{finding_id, agree, reasoning}]`.
```

**Rodada 3 — ultima tentativa:**

1. Mesmo protocolo da rodada 2, mas com `reconciliation_round: 3`. E o ultimo ponto em que reconciliacao automatica e permitida.

**Persistencia de divergencia apos 3 rodadas:**

1. O governance (master-audit) deve emitir verdict consolidado com campo `reconciliation_failed: true` e persistir ambos os verdicts finais no arquivo `specs/NNN/master-audit.json`.
2. O orchestrator deve parar o pipeline do slice e emitir `exception_triggered` tipo E10 (escalacao PM por divergencia dual-LLM) no telemetry.
3. O orchestrator deve invocar `/explain-slice NNN` (R12) para traduzir a divergencia em linguagem de produto. O relatorio ao PM deve conter:
   - O que os dois auditores concordaram (fatos comuns).
   - O ponto especifico de divergencia (findings em disputa).
   - Impacto de produto de aprovar vs rejeitar (traduzido, sem jargao).
   - Recomendacao forte do governance (baseada em evidencia, nao em media).
4. O PM decide por escolher uma das trilhas OU solicitar rodada humana adicional. A decisao do PM e registrada em `specs/NNN/master-audit-pm-decision.json` com: `chosen_trail: "claude|gpt5"`, `reason`, `timestamp`.
5. Se o PM escolher uma trilha, o verdict dessa trilha torna-se o verdict final. O verdict da outra trilha permanece registrado como dissenting opinion para auditoria.
6. Se o PM solicitar rodada humana, o slice fica bloqueado ate review humana com acesso aos dois verdicts e ao codigo.

**Regra absoluta:** nenhum slice pode ser merged com `reconciliation_failed: true` sem decisao explicita do PM em `master-audit-pm-decision.json`.

### 9.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "master-audit",
  "slice": "NNN",
  "lane": "L3",
  "agent": "governance",
  "mode": "master-audit",
  "verdict": "approved",
  "timestamp": "2026-04-16T16:30:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-master-audit-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "claude_verdict": "approved",
    "gpt5_verdict": "approved",
    "verdicts_agree": true,
    "reconciliation_rounds": 0,
    "reconciliation_failed": false,
    "gate_inputs_analyzed": [
      "verification.json",
      "review.json",
      "security-review.json",
      "test-audit.json",
      "functional-review.json"
    ],
    "conditional_gates_analyzed": [],
    "justification": "Todos os gates aprovaram com zero findings S1-S3. Codigo aderente a spec e ADRs."
  }
}
```

---

## 10. Gate: audit-spec

**Nome canonico do gate:** `audit-spec`
**Agente responsavel:** qa-expert (modo: `audit-spec`)
**Trigger:** apos producao de `specs/NNN/spec.md` pelo product-expert (decompose) ou orchestrator (`/draft-spec`)
**Input:** spec.md + roadmap.md + contrato de epico (`docs/epics/ENN/README.md`) + ADRs relevantes + constitution.md
**Output:** `specs/NNN/spec-audit.json`
**Evidencia:** mapeamento AC → teste de validade; referencias aos documentos comparados

### 10.1 Checklist binario

- [ ] Todas as ACs numeradas no formato `AC-NNN-XXX`
- [ ] Toda AC segue estrutura "Dado X, quando Y, entao Z" ou equivalente testavel
- [ ] Nenhuma AC ambigua (verbos vagos: "melhorar", "otimizar", "facilitar" sao finding S3)
- [ ] Frontmatter YAML presente com `title`, `lane`, `story`, `epic`
- [ ] Secoes obrigatorias presentes: contexto, jornada do usuario, ACs numerados, fora-de-escopo
- [ ] Nenhuma AC contradiz roadmap.md ou ADRs
- [ ] Nenhum termo usado sem entrada no glossary.md
- [ ] Campo `lane` valido (L1/L2/L3/L4)

### 10.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| Numero minimo de ACs | >= 1 | Contagem de `AC-NNN-XXX` em spec.md |
| ACs ambiguas | 0 | Analise manual do auditor |
| Cobertura do fora-de-escopo | Cada AC cobre um caso; fora-de-escopo cobre o inverso relevante | Analise manual |

### 10.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "audit-spec",
  "slice": "NNN",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "audit-spec",
  "verdict": "approved",
  "timestamp": "2026-04-16T11:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-audit-spec-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "acs_count": 8,
    "ambiguous_acs": [],
    "frontmatter_valid": true,
    "glossary_terms_unmapped": [],
    "roadmap_consistency": true,
    "adr_consistency": true
  }
}
```

---

## 11. Gate: audit-story

**Nome canonico do gate:** `audit-story`
**Agente responsavel:** qa-expert (modo: `audit-story`)
**Trigger:** apos producao de `docs/epics/ENN/stories/ENN-SNN.md` pelo product-expert (decompose)
**Input:** story contract + contrato de epico + domain-model.md + api-contracts.md (se UI/API)
**Output:** `docs/epics/ENN/stories/ENN-SNN-audit.json`
**Evidencia:** validacao do frontmatter e ACs

### 11.1 Checklist binario

- [ ] Frontmatter YAML presente com `dependencies[]` e `lane` sugerida
- [ ] Campo `dependencies[]` declarado explicitamente (vazio ou com outras stories do epico)
- [ ] ACs numerados e testaveis
- [ ] Secoes obrigatorias: objetivo, ACs, fora-de-escopo, notas tecnicas
- [ ] Nenhum termo fora do glossary.md
- [ ] Lane sugerida compativel com conteudo (ex: integracao de pagamento → L4)

### 11.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| ACs minimos por story | >= 1 | Contagem |
| Dependencias validas | Todas referenciam stories existentes em INDEX.md | Comparacao |

### 11.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "audit-story",
  "slice": "NNN",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "audit-story",
  "verdict": "approved",
  "timestamp": "2026-04-16T10:30:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "story-ENN-SNN-audit-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "story_id": "ENN-SNN",
    "acs_count": 5,
    "dependencies_declared": true,
    "dependencies_valid": true,
    "lane_suggested": "L3",
    "glossary_terms_unmapped": []
  }
}
```

---

## 12. Gate: audit-planning

**Nome canonico do gate:** `audit-planning`
**Agente responsavel:** qa-expert (modo: `audit-planning`)
**Trigger:** apos decomposicao em epicos ou apos producao de artefatos de Fase A/B
**Input:** roadmap.md + contratos de epicos + domain-model.md + glossary.md + nfr.md + risks.md + ADRs
**Output:** `docs/audits/audit-planning-YYYY-MM-DD.json`
**Evidencia:** validacao de consistencia global de planejamento

### 12.1 Checklist binario

- [ ] roadmap.md tem toda a lista do PRD
- [ ] Dependencias de epicos formam DAG (sem ciclos)
- [ ] Toda NFR tem metrica numerica
- [ ] Todo risco score >= 12 tem mitigacao concreta
- [ ] Todo termo do glossary.md aparece pelo menos uma vez em spec/plan/adr
- [ ] Ordem de epicos respeita R14 (inter-epico MVP)

### 12.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| Cobertura de NFR por ADR | >= 80% das NFRs tem ADR relacionado | Mapeamento manual |
| Riscos de score >= 12 sem mitigacao | 0 | Analise de risks.md |

### 12.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "audit-planning",
  "slice": "N/A",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "audit-planning",
  "verdict": "approved",
  "timestamp": "2026-04-16T09:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "planning-audit-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "epics_in_roadmap": 14,
    "dag_valid": true,
    "nfr_coverage_percent": 92,
    "high_risks_without_mitigation": 0,
    "orphan_glossary_terms": []
  }
}
```

---

## 13. Gate: plan-review

**Nome canonico do gate:** `plan-review`
**Agente responsavel:** architecture-expert (modo: `plan-review`)
**Trigger:** apos producao de `specs/NNN/plan.md` pelo architecture-expert (plan); roda em instancia isolada R3
**Input:** plan.md + spec.md auditada + ADRs relevantes + erd.md + api-contracts.md + constitution.md
**Output:** `specs/NNN/plan-review.json`
**Evidencia:** citacoes literais do plan + contracitacoes de ADRs

### 13.1 Checklist binario

- [ ] Todo arquivo mencionado tem path completo
- [ ] Toda migration tem `up()` e `down()` reversiveis
- [ ] Plan adere a ADRs ativos
- [ ] Componentes Vue, Services e Actions seguem padroes do stack
- [ ] Testes planejados cobrem todas as ACs da spec
- [ ] Nenhuma abstracao prematura (YAGNI)
- [ ] Sequenciamento de tasks em tasks.md respeita dependencias

### 13.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| Arquivos listados no plan vs ACs | cada arquivo justificado por >= 1 AC | Mapeamento |
| Migracoes reversiveis | 100% | Revisao manual |
| ADRs referenciados | Todos os ADRs aplicaveis citados | Comparacao |

### 13.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "plan-review",
  "slice": "NNN",
  "lane": "L3",
  "agent": "architecture-expert",
  "mode": "plan-review",
  "verdict": "approved",
  "timestamp": "2026-04-16T12:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-plan-review-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "files_listed": 12,
    "migrations_reversible": true,
    "adr_references": ["ADR-0001", "ADR-0004"],
    "tests_cover_all_acs": true,
    "premature_abstractions": [],
    "task_sequencing_valid": true
  }
}
```

---

## 14. Gate: spec-security (somente L4)

**Nome canonico do gate:** `spec-security`
**Agente responsavel:** security-expert (modo: `spec-security`)
**Trigger:** apos plan-review aprovado em trilha L4 — pre-review de seguranca antes da Fase D
**Input:** plan.md aprovado + spec.md auditada + threat-model.md + lgpd-base-legal.md
**Output:** `specs/NNN/security-pre-review.json`
**Evidencia:** STRIDE aplicado a cada componente novo; avaliacao de vetor de ataque

### 14.1 Checklist binario

- [ ] Todo novo endpoint tem entrada no threat-model
- [ ] Toda operacao sobre PII tem base legal LGPD mapeada
- [ ] Nenhum secret planejado em config/ ou hardcoded
- [ ] Toda integracao externa tem contrato de auth (OAuth, API key via vault)
- [ ] Autorizacao planejada (Policy/Gate) para todo controller novo
- [ ] Rate limiting planejado para endpoints publicos

### 14.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| Vetores STRIDE avaliados | 100% dos componentes novos | threat-model |
| PII sem base legal | 0 | Comparacao erd.md vs lgpd-base-legal.md |

### 14.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "spec-security",
  "slice": "NNN",
  "lane": "L4",
  "agent": "security-expert",
  "mode": "spec-security",
  "verdict": "approved",
  "timestamp": "2026-04-16T13:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-spec-security-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "stride_coverage_percent": 100,
    "threat_vectors_assessed": ["STRIDE-S", "STRIDE-T", "STRIDE-R", "STRIDE-I", "STRIDE-D", "STRIDE-E"],
    "pii_without_legal_base": [],
    "mitigation_plan_adequate": true,
    "hardcoded_secrets_planned": [],
    "rate_limiting_planned": true
  }
}
```

---

## 15. Gate: guide-audit

**Nome canonico do gate:** `guide-audit`
**Agente responsavel:** governance (modo: `guide-audit`)
**Trigger:** periodico (semanal ou fim de epico) e sob demanda quando suspeitar de drift
**Input:** CLAUDE.md + constitution.md + todos os `.claude/agents/*.md` + todos os `scripts/hooks/*.sh` + `.claude/settings.json` + MANIFEST.sha256
**Output:** `docs/audits/guide-audit-YYYY-MM-DD.json`
**Evidencia:** comparacao entre harness documentado e harness implementado

### 15.1 Checklist binario

- [ ] Nenhum arquivo de instrucao proibido presente (R1: .cursorrules, AGENTS.md, GEMINI.md, copilot-instructions.md, .bmad-core/, etc.)
- [ ] settings-lock passa (SHA256 de .claude/settings.json bate)
- [ ] hooks-lock passa (MANIFEST.sha256 de scripts/hooks/ bate)
- [ ] Todo agent referenciado em CLAUDE.md existe em .claude/agents/
- [ ] Todo skill referenciado em skills da CLI existe em .claude/skills/
- [ ] MCPs ativos sao apenas os autorizados em .claude/allowed-mcps.txt
- [ ] Nenhum hook referenciado em settings.json esta ausente no filesystem

### 15.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| Arquivos proibidos | 0 | find + regex |
| Drift de selos | 0 bytes de diff | sha256sum |

### 15.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "guide-audit",
  "slice": "N/A",
  "lane": "L3",
  "agent": "governance",
  "mode": "guide-audit",
  "verdict": "approved",
  "timestamp": "2026-04-16T18:00:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "guide-audit-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "forbidden_files_found": [],
    "settings_lock_status": "pass",
    "hooks_lock_status": "pass",
    "missing_agents": [],
    "missing_skills": [],
    "unauthorized_mcps": [],
    "orphan_hooks_in_settings": []
  }
}
```

---

## 16. Gate: audit-tests-draft (ADR-0017 Mudanca 1 — pre-implementacao)

**Nome canonico do gate:** `audit-tests-draft`
**Agente responsavel:** qa-expert (modo: `audit-tests-draft`)
**Trigger:** apos `/draft-tests NNN` commitar os testes red com AC-ID rastreavel. Roda ANTES do builder (implementer) iniciar.
**Input:** `tests-draft-audit-input/` (spec.md, ac-list.json, test-files/, test-run-output.txt, constitution-snapshot.md)
**Output:** `specs/NNN/tests-draft-audit.json`
**Evidencia:** cada AC da spec mapeado a teste rastreavel por AC-ID; `test-run-output.txt` confirma red; metricas de qualidade das assertions
**Estado na maquina:** S7 (testes red) -> `audit-tests-draft` -> S7.1 (approved) -> S8 (implementacao). Pre-requisito absoluto para `/start implementer`.

### 16.1 Checklist binario (zero tolerance)

- [ ] **Toda AC do spec.md tem pelo menos 1 teste rastreavel.** Trace via nome contendo AC-NNN, OU docblock `@covers AC-NNN`, OU `describe('AC-NNN: ...')` agrupando. Ausencia = finding S1.
- [ ] **Todo teste nao-helper referencia um AC-ID.** Testes sem AC-ID e sem tag `@helper`/`@setup` = finding S2.
- [ ] **Todos os testes estao realmente red.** `test-run-output.txt` mostra exit != 0 e falhas esperadas. Teste que passa sem implementacao (unexpectedly_passing > 0) = finding S1.
- [ ] **Nenhum parse error nos testes.** `parse_errors == 0`. Teste que nao roda por erro de sintaxe = finding S1.
- [ ] **Assertions tem semantica.** `expect($x)->toBeTruthy()` ou `assertTrue(true)` sem contexto = finding S3. Lista em `trivial_assertions[]`.
- [ ] **Mocks respeitam nivel de integracao do AC.** AC que descreve integracao banco/servico externo mockada = finding S2 (deveria ser feature test).
- [ ] **Sem testes duplicados ou redundantes.** Dois testes com mesma assertion sobre mesmo codigo = finding S4 em `duplicate_tests[]`.
- [ ] **Edge cases declarados na spec tem teste correspondente.** Cenarios de erro ou casos limite listados na spec sem teste = finding S2.

### 16.2 Limiares mensuraveis

| Metrica | Limiar | Ferramenta |
|---|---|---|
| ACs sem teste rastreavel | 0 | parser de testes + ac-list.json |
| Testes sem AC-ID (nao-helper) | 0 | parser de testes |
| Testes unexpectedly_passing | 0 | pest exit code analysis |
| Parse errors | 0 | pest --dry-run |
| Trivial assertions | 0 | AST scan por padroes triviais |
| Duplicate tests | 0 | hash de assertions |

### 16.3 Formato de evidencia

```json
{
  "$schema": "gate-output-v1",
  "gate": "audit-tests-draft",
  "slice": "NNN",
  "lane": "L3",
  "agent": "qa-expert",
  "mode": "audit-tests-draft",
  "verdict": "approved",
  "timestamp": "2026-04-16T19:30:00Z",
  "commit_hash": "abc1234",
  "isolation_context": "slice-NNN-audit-tests-draft-instance-01",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 0,
  "findings_by_severity": {"S1": 0, "S2": 0, "S3": 0, "S4": 0, "S5": 0},
  "findings": [],
  "evidence": {
    "checks": {
      "every_ac_has_test": true,
      "every_test_references_ac": true,
      "tests_are_red": true,
      "assertions_have_semantic_meaning": true,
      "mocks_respect_ac_integration_level": true,
      "no_duplicate_tests": true,
      "edge_cases_from_spec_covered": true
    },
    "ac_coverage_map": {
      "AC-001": {
        "tests": ["tests/Feature/ExampleTest.php::test_ac_001_should_return_422"],
        "trace_method": "name_contains_ac_id"
      }
    },
    "test_run_summary": {
      "total_tests": 12,
      "red_as_expected": 12,
      "unexpectedly_passing": 0,
      "parse_errors": 0
    },
    "unlinked_tests": [],
    "trivial_assertions": [],
    "duplicate_tests": []
  }
}
```

### 16.4 Isolamento R3/R11

`audit-tests-draft` **nao pode** rodar na mesma instancia que o `builder (test-writer)` que produziu os testes (R11 — quem escreve nao audita). `isolation_context` obrigatorio e unico por invocacao.

### 16.5 Loop fixer -> re-audit

Rejeicao (qualquer finding) dispara: builder (fixer em modo test-writer) corrige -> re-roda `audit-tests-draft` (mesmo gate, nao pula). 6a rejeicao consecutiva = R6, escala PM via `/explain-slice NNN`.

---

## 17. Regras transversais

1. **Zero tolerance (S1-S3):** nenhum gate pode aprovar com findings S1, S2 ou S3 pendentes. Findings S4 (minor) e S5 (advisory) nao bloqueiam aprovacao mas devem ser registrados no JSON de saida. S4 findings sao rastreados como divida tecnica em `project-state.json`. S5 findings sao registrados em telemetria apenas. Um gate emite `verdict: approved` quando `blocking_findings_count == 0`, mesmo que `non_blocking_findings_count > 0`.

2. **Loop de correcao:** gate rejeita → builder (fixer) corrige TODOS os findings S1-S3 → re-run do MESMO gate → repete ate `blocking_findings_count == 0`. As 5 primeiras reprovacoes consecutivas do mesmo gate ficam no loop automatico; a 6a escala ao PM via `/explain-slice NNN` (R6).

3. **Contexto isolado:** cada gate deve executar em contexto isolado conforme R3. O agente que executa o gate nao pode ter visto a saida de outro gate do mesmo slice.

4. **Imutabilidade de evidencia:** apos o gate emitir o JSON, o arquivo nao pode ser editado. Nova execucao gera novo arquivo (versionado por timestamp ou sobrescreve com novo commit hash).

5. **Ordem de execucao:**
   - verify (1o, qa-expert) → code-review (2o, architecture-expert, somente se verify aprovado) → [security-gate + audit-tests + functional-gate + gates condicionais] (3o, em paralelo) → master-audit (4o, somente se todos os anteriores aprovados com zero findings S1-S3).

6. **Gates condicionais:** data-gate, observability-gate e integration-gate somente devem executar quando o slice inclui alteracoes relevantes ao dominio do gate. O orchestrator decide a ativacao com base no plan.md do slice.
