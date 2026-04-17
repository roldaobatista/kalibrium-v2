# 01 — Sistema de Severidade de Findings

> Documento normativo. Versao 1.2.0 — 2026-04-16.
> Changelog 1.2.0: cascata S4→S3 no proximo slice removida; substituida por promocao diferida avaliada pelo governance (retrospective) no fim do epico.
> Fonte de verdade para classificacao de findings em todos os gates do pipeline Kalibrium V2.

---

## 1. Niveis de severidade

### S1 — Blocker

| Campo | Valor |
|---|---|
| **Efeito no gate** | Bloqueia o gate. Correcao imediata obrigatoria. |
| **SLA** | O finding deve ser corrigido antes de qualquer re-run do gate. |
| **Excecao possivel?** | Nao. Nenhuma excecao e permitida para S1. |

**Definicao:** Finding que representa risco imediato de seguranca, perda de dados, violacao de isolamento multi-tenant ou indisponibilidade total do sistema.

**Exemplos concretos (stack Laravel 13 / PHP 8.5 / PostgreSQL / Vue 3):**

- SQL injection em query raw sem binding (`DB::raw($userInput)`)
- Vazamento de dados entre tenants (query sem escopo `tenant_id` em Model com `ScopedByTenant`)
- Credencial hardcoded em codigo-fonte (API key, DB password em `.php` ou `.vue`)
- Falha de autenticacao que permite acesso anonimo a rota protegida
- Migration que executa `DROP TABLE` ou `TRUNCATE` sem rollback
- XSS persistente em campo renderizado via `v-html` sem sanitizacao
- Exposicao de `APP_KEY` ou `DB_PASSWORD` em resposta HTTP ou log publico
- Falha no middleware de tenant que permite request sem tenant resolvido acessar dados

**Quem pode classificar:** Qualquer agent de gate (qa-expert verify, architecture-expert code-review, security-expert security-gate, qa-expert audit-tests, product-expert functional-gate, governance master-audit).

**Quem pode reclassificar:** Ninguem. S1 nao pode ser rebaixado.

**Registro no JSON de gate:**

```json
{
  "id": "F-001",
  "severity": "S1",
  "severity_label": "blocker",
  "gate_blocking": true,
  "exception_allowed": false,
  "description": "...",
  "file": "app/Models/Customer.php",
  "line": 42,
  "evidence": "...",
  "agent": "security-expert (security-gate)"
}
```

---

### S2 — Critical

| Campo | Valor |
|---|---|
| **Efeito no gate** | Bloqueia o gate. Correcao obrigatoria antes do merge. |
| **SLA** | O finding deve ser corrigido na mesma sessao. |
| **Excecao possivel?** | Somente com aprovacao escrita do PM + security-expert. Owner e deadline obrigatorios. |

**Definicao:** Finding que causa degradacao severa de performance, falha funcional em fluxo critico, ou violacao de conformidade (LGPD, fiscal) sem risco imediato de seguranca.

**Exemplos concretos:**

- N+1 query em listagem principal de entidade (`Customer::all()` sem `with()` em index)
- Falta de indice em coluna usada em `WHERE` de query frequente no PostgreSQL
- Rota de API que retorna dados sem paginacao (potencial OOM em producao)
- Job Redis sem retry/dead-letter configurado em operacao financeira
- Migration sem transacao em operacao multi-tabela
- Componente Vue que faz chamada API no `setup()` sem tratamento de erro
- Cache Redis sem TTL em dados que mudam frequentemente
- Validacao `FormRequest` ausente em endpoint que altera dados financeiros
- Falta de `$casts` em campo `decimal` usado em calculo monetario (Eloquent)
- Policy/Gate de autorizacao ausente em controller action critico

**Quem pode classificar:** Qualquer agent de gate.

**Quem pode reclassificar para S1:** Qualquer agent de gate, sem aprovacao adicional.

**Quem pode reclassificar para S3:** Somente o governance (master-audit), com justificativa registrada no campo `reclassification_reason`.

**Registro no JSON de gate:**

```json
{
  "id": "F-002",
  "severity": "S2",
  "severity_label": "critical",
  "gate_blocking": true,
  "exception_allowed": true,
  "exception_requires": ["pm_approval", "security_expert_approval"],
  "exception_fields": ["owner", "deadline"],
  "description": "...",
  "file": "...",
  "line": null,
  "evidence": "...",
  "agent": "architecture-expert (code-review)"
}
```

**Procedimento de excecao S2:**

1. O builder (fixer) deve registrar pedido de excecao em `specs/NNN/exceptions.json`.
2. O orchestrator deve notificar o PM com texto traduzido (R12).
3. O PM deve aprovar ou rejeitar por escrito.
4. O security-expert deve aprovar ou rejeitar por escrito (contexto isolado).
5. Se ambos aprovarem, o finding recebe `exception_granted: true`, `exception_owner: "<nome>"`, `exception_deadline: "YYYY-MM-DD"`.
6. O finding permanece no JSON de gate como registro permanente.

---

### S3 — Major

| Campo | Valor |
|---|---|
| **Efeito no gate** | Bloqueia o gate. Correcao obrigatoria antes do merge. |
| **SLA** | O finding deve ser corrigido na mesma sessao. |
| **Excecao possivel?** | Com aprovacao escrita do PM. Registrado como incidente. |

**Definicao:** Finding que representa falha de qualidade significativa, violacao de padrao arquitetural, ou lacuna funcional em fluxo secundario.

**Exemplos concretos:**

- Validacao de input ausente em campo nao-critico de formulario (ex: campo `notes` sem `max:5000`)
- Teste que valida apenas happy path sem edge case documentado no AC
- Controller com logica de negocio que deveria estar em Service/Action
- Componente Vue sem `defineProps` tipado (usa `any` ou objeto generico)
- Rota de API sem rate limiting em endpoint publico
- Migration que adiciona coluna nullable sem valor default em tabela com dados
- Falta de `try/catch` em operacao de I/O (file upload, chamada externa)
- Tailwind CSS com classes inline duplicadas ou conflitantes
- Inertia `router.visit()` sem tratamento de erro de rede
- Falta de log estruturado (`Log::info()` sem contexto) em operacao auditavel
- Enum PHP 8.5 nao utilizado onde string magica e usada

**Quem pode classificar:** Qualquer agent de gate.

**Quem pode reclassificar para S2:** Qualquer agent de gate, sem aprovacao adicional.

**Quem pode reclassificar para S4:** Somente via processo de reclassificacao (secao 3).

**Registro no JSON de gate:**

```json
{
  "id": "F-003",
  "severity": "S3",
  "severity_label": "major",
  "gate_blocking": true,
  "exception_allowed": true,
  "exception_requires": ["pm_approval"],
  "exception_fields": ["owner", "deadline"],
  "incident_required": true,
  "description": "...",
  "file": "...",
  "line": null,
  "evidence": "...",
  "agent": "product-expert (functional-gate)"
}
```

**Procedimento de excecao S3:**

1. O builder (fixer) deve registrar pedido em `specs/NNN/exceptions.json` com justificativa.
2. O orchestrator deve notificar o PM com texto traduzido (R12).
3. O PM deve aprovar ou rejeitar por escrito.
4. Se aprovado, o orchestrator deve criar `docs/incidents/exception-S3-FXXX-YYYY-MM-DD.md`.
5. O finding recebe `exception_granted: true`, `exception_owner`, `exception_deadline`.

---

### S4 — Minor

| Campo | Valor |
|---|---|
| **Efeito no gate** | NAO bloqueia o gate. |
| **SLA** | O finding deve ser corrigido no proximo slice do mesmo epico. |
| **Excecao possivel?** | Auto-aceito com rastreamento. Entrada de divida tecnica criada automaticamente. |

**Definicao:** Finding de baixo impacto que nao afeta funcionalidade, seguranca ou performance, mas representa desvio de padrao ou oportunidade de melhoria incremental.

**Exemplos concretos:**

- Nome de variavel nao-ideal (ex: `$d` ao inves de `$customer`)
- Comentario de codigo desatualizado ou redundante
- Import nao utilizado em arquivo PHP ou Vue
- Ordem de metodos em controller fora do padrao convencional
- Teste com `assertDatabaseHas` quando `assertModelExists` seria mais preciso
- Componente Vue com `<style>` nao-scoped em componente de pagina
- Falta de `readonly` em propriedade PHP que nunca e reatribuida
- String hardcoded que poderia usar translation key (i18n) em label secundario
- Tailwind: classe utilitaria que poderia ser extraida para `@apply` em componente reutilizado
- PHPDoc ausente em metodo publico de Service

**Quem pode classificar:** Qualquer agent de gate.

**Quem pode reclassificar para S3:** Qualquer agent de gate, sem aprovacao adicional.

**Quem pode reclassificar para S5:** Somente o architecture-expert (code-review) ou governance (master-audit).

**Registro no JSON de gate:**

```json
{
  "id": "F-004",
  "severity": "S4",
  "severity_label": "minor",
  "gate_blocking": false,
  "auto_accepted": true,
  "tech_debt_entry": true,
  "fix_deadline_slice": "next slice in same epic",
  "description": "...",
  "file": "...",
  "line": null,
  "evidence": "...",
  "agent": "architecture-expert (code-review)"
}
```

**Rastreamento automatico:**

1. O gate deve emitir o finding no JSON com `tech_debt_entry: true`.
2. O orchestrator deve adicionar entrada em `docs/governance/tech-debt.md` com ID do finding, slice de origem e **slice-alvo preferencial** (proximo slice do mesmo epico que tocar area relacionada).
3. Se houver slice-alvo natural, incluir task de correcao no `tasks.md` do slice-alvo.
4. **Promocao diferida (final de epico):** findings S4 nao resolvidos durante o epico sao avaliados pelo `governance (retrospective)` no fim do epico. A retrospectiva pode:
   - Agrupar S4 pendentes em slice dedicado de cleanup criado no backlog do proximo epico.
   - Promover para S3 apenas aqueles que ainda sao relevantes e cuja nao-correcao comprometeria o epico seguinte.
   - Manter como S4 (com tag `overdue`) os que sao cosmetica ou dependem de mudancas futuras de stack.
5. **Nao ha promocao automatica no proximo slice.** A cascata S4→S3 por ausencia de correcao no "slice-alvo" foi removida porque criava inversao de prioridade (slice seguinte herdava divida aleatoria do epico).
6. O PM e notificado da promocao via R12 no relatorio de retrospectiva. A promocao formal requer aceitacao implicita (PM nao veta) ou explicita.

---

### S5 — Advisory

| Campo | Valor |
|---|---|
| **Efeito no gate** | Nenhum. Informativo apenas. |
| **SLA** | Nenhuma correcao obrigatoria. |
| **Excecao possivel?** | N/A — registrado apenas para aprendizado. |

**Definicao:** Sugestao de melhoria futura, observacao sobre padrao emergente, ou nota informativa sem impacto no slice atual.

**Exemplos concretos:**

- Sugestao de extrair componente Vue reutilizavel a partir de duplicacao observada
- Nota sobre feature futura do PHP 8.5 que simplificaria implementacao atual
- Observacao sobre padrao de API que poderia ser adotado em epicos futuros
- Sugestao de indice parcial no PostgreSQL para query que ainda nao e frequente
- Nota sobre melhoria de DX (developer experience) em tooling
- Observacao sobre oportunidade de pre-computacao com Redis para dashboard futuro

**Quem pode classificar:** Qualquer agent de gate.

**Quem pode reclassificar:** Nao aplicavel. S5 nao bloqueia nem rastreia.

**Registro no JSON de gate:**

```json
{
  "id": "F-005",
  "severity": "S5",
  "severity_label": "advisory",
  "gate_blocking": false,
  "logged_for_learning": true,
  "description": "...",
  "agent": "architecture-expert (code-review)"
}
```

---

## 2. Impacto na politica "zero tolerance"

A politica "zero tolerance" definida no CLAUDE.md e redefinida como:

> **Zero S1-S3:** Nenhum finding de severidade S1, S2 ou S3 pode existir sem correcao (ou excecao formalmente aprovada) no momento do merge.

**Regras de aplicacao:**

1. Um gate deve emitir `verdict: approved` somente quando `findings` com severidade S1, S2 ou S3 estiverem vazios (ou todos tiverem `exception_granted: true`).
2. Findings S4 e S5 nao impedem `verdict: approved`.
3. O governance (master-audit) deve validar que nenhum gate emitiu approved com S1-S3 pendentes.
4. O merge-slice deve rejeitar merge se qualquer gate contiver S1-S3 sem correcao ou excecao.

**Campo obrigatorio no JSON de gate para suportar a nova politica:**

```json
{
  "verdict": "approved",
  "blocking_findings_count": 0,
  "non_blocking_findings_count": 2,
  "findings_by_severity": {
    "S1": 0,
    "S2": 0,
    "S3": 0,
    "S4": 1,
    "S5": 1
  },
  "findings": [...]
}
```

---

## 3. Processo de reclassificacao

Quando um agent classifica um finding e o builder (fixer) discorda da severidade, o processo de reclassificacao deve ser seguido:

### 3.1. Quem pode solicitar reclassificacao

- O builder (fixer) pode solicitar reclassificacao de qualquer finding S2, S3 ou S4.
- Nenhum agent pode solicitar reclassificacao de S1 (blocker e absoluto).
- Reclassificacao de S5 nao e aplicavel (nao tem efeito operacional).

### 3.2. Processo

1. O builder (fixer) deve adicionar ao finding no JSON:
   ```json
   {
     "reclassification_requested": true,
     "reclassification_from": "S3",
     "reclassification_to": "S4",
     "reclassification_justification": "O campo 'notes' e opcional e nao aparece em nenhum fluxo critico. Impacto limitado a UX secundaria."
   }
   ```

2. O orchestrator deve encaminhar o pedido ao agent que emitiu o finding original (o "classificador").

3. O classificador deve avaliar a justificativa em contexto isolado e emitir uma das decisoes:
   - `reclassification_accepted: true` — o finding assume a nova severidade.
   - `reclassification_rejected: true` com `rejection_reason` — o finding mantem a severidade original.

4. Se o classificador rejeitar e o builder (fixer) ainda discordar, o pedido deve ser escalado ao governance (master-audit).

5. O governance (master-audit) deve decidir com finalidade. A decisao do governance (master-audit) e irrevogavel.

6. Se o governance (master-audit) reclassificar de S3 para S4, o finding deixa de bloquear o gate e segue o fluxo S4 (tech-debt tracking).

### 3.3. Restricoes

- Reclassificacao para CIMA (ex: S4 para S3) nao precisa de processo — qualquer agent de gate pode faze-lo diretamente.
- Reclassificacao para BAIXO (ex: S3 para S4, S2 para S3) obriga o processo completo descrito acima.
- O numero maximo de reclassificacoes por slice e 5. Acima disso, o orchestrator deve escalar ao PM.
- Toda reclassificacao deve ser registrada no JSON de gate com historico completo.

### 3.4. Registro de reclassificacao

```json
{
  "id": "F-003",
  "severity": "S4",
  "severity_original": "S3",
  "reclassification_history": [
    {
      "from": "S3",
      "to": "S4",
      "requested_by": "builder (fixer)",
      "decided_by": "governance (master-audit)",
      "decision": "accepted",
      "reason": "Campo opcional sem impacto funcional critico.",
      "timestamp": "2026-04-16T14:30:00Z"
    }
  ]
}
```

---

## 4. Tabela de referencia rapida

| Nivel | Nome | Bloqueia gate? | Excecao? | SLA | Quem decide excecao |
|---|---|---|---|---|---|
| S1 | blocker | Sim | Nao | Antes do re-run | N/A |
| S2 | critical | Sim | Sim | Mesma sessao | PM + security-expert |
| S3 | major | Sim | Sim | Mesma sessao | PM |
| S4 | minor | Nao | Auto-aceito | Proximo slice do epico | Automatico |
| S5 | advisory | Nao | N/A | Nenhum | N/A |

---

## 5. Vigencia

Este documento entra em vigor imediatamente e aplica-se a todos os gates executados a partir da data de criacao. Findings emitidos antes desta data nao sao reclassificados retroativamente.
