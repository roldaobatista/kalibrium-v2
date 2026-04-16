# 02 — Trilhas de Complexidade

> Documento normativo. Versao 1.0.0 — 2026-04-16.
> Define as 4 trilhas de complexidade que determinam quais fases, gates e aprovacoes sao obrigatorios para cada slice.

---

## 1. Visao geral

Toda unidade de trabalho (slice) deve ser classificada em exatamente uma trilha antes do inicio da execucao. A trilha determina:

- Quais fases do pipeline (A-F) sao obrigatorias
- Quais gates devem ser executados
- Quais aprovacoes sao necessarias
- Qual o tempo maximo permitido

| Trilha | Nome | Fases | Gates minimos | Tempo maximo |
|---|---|---|---|---|
| L1 | HOTFIX | D, E | 2 | 2 horas |
| L2 | SMALL CHANGE | C (simplificado), D, E | 2 | 1 sessao |
| L3 | STANDARD | A-F (conforme epico) | 6 (full pipeline) | Sem limite fixo |
| L4 | HIGH-RISK | A-F (completo) | 6 + extras obrigatorios | Sem limite fixo |

---

## 2. Trilha L1 — HOTFIX

### 2.1. Quando usar

O orchestrator deve classificar como L1 quando TODAS as condicoes abaixo forem verdadeiras:

- Existe bug confirmado em producao, OU
- Existe incidente de seguranca ativo, OU
- Existe corrupcao de dados detectada
- E o PM aprovou explicitamente o uso da trilha HOTFIX

### 2.2. Fases obrigatorias

| Fase | Obrigatoria? | Observacao |
|---|---|---|
| A — Descoberta | Nao | Pulada integralmente. |
| B — Estrategia Tecnica | Nao | Pulada integralmente. |
| C — Planejamento | Nao | Pulada. O builder (fixer) recebe descricao do bug como input direto. |
| D — Execucao | Sim | Implementacao + teste de regressao obrigatorio. |
| E — Pipeline de Gates | Sim | Apenas gates minimos (ver 2.3). |
| F — Encerramento | Sim | Incident report obrigatorio apos merge. |

### 2.3. Gates obrigatorios

1. **verify-slice** — Validacao mecanica do slice.
2. **security-review** — Revisao de seguranca obrigatoria (hotfix pode introduzir vulnerabilidade sob pressao).

Nenhum outro gate e obrigatorio. O governance (master-audit) NAO e executado em L1.

### 2.4. Aprovacao

- O PM deve aprovar o uso da trilha HOTFIX por escrito antes do inicio.
- O orchestrator deve registrar a aprovacao em `specs/NNN/hotfix-approval.json`:
  ```json
  {
    "lane": "L1",
    "approved_by": "pm",
    "reason": "Bug em producao: tenant_id nao filtrado em /api/customers",
    "timestamp": "2026-04-16T10:00:00Z"
  }
  ```

### 2.5. Evidencia pos-merge

O orchestrator deve criar `docs/incidents/hotfix-NNN-YYYY-MM-DD.md` com:

- Descricao do incidente
- Causa raiz identificada
- Correcao aplicada
- Teste de regressao adicionado
- Timestamp de deteccao ate merge
- Avaliacao: o hotfix introduziu divida tecnica? Se sim, criar entrada em `docs/governance/tech-debt.md`.

### 2.6. Tempo maximo

O slice L1 deve ser concluido (merged) em no maximo **2 horas** a partir da deteccao do incidente. Se o prazo nao for cumprido, o orchestrator deve escalar ao PM com opcoes:

1. Continuar na trilha L1 com prazo estendido (PM aprova novo deadline).
2. Reverter para trilha L3 (STANDARD) e aplicar rollback temporario.

---

## 3. Trilha L2 — SMALL CHANGE

### 3.1. Quando usar

O orchestrator deve classificar como L2 quando TODAS as condicoes abaixo forem verdadeiras:

- A mudanca toca apenas arquivos nao-logicos, OU
- A mudanca e exclusivamente de texto/copy, CSS, configuracao, documentacao, ou variavel de ambiente

**Arquivos nao-logicos (lista exaustiva):**

- `resources/views/**/*.blade.php` — somente alteracoes de texto/copy
- `resources/js/Components/**/*.vue` — somente alteracoes de `<template>` ou `<style>` sem logica
- `resources/css/**/*.css` — Tailwind/CSS puro
- `config/*.php` — alteracao de valor (nao de estrutura)
- `.env.example` — adicao/alteracao de variavel
- `docs/**/*.md` — documentacao
- `lang/**/*.php` — traducoes
- `public/**` — assets estaticos
- `tailwind.config.js` — ajuste de tema/cores

**NAO pode ser L2 se:**

- Altera qualquer arquivo em `app/` (Models, Controllers, Services, Actions, Policies, Middleware)
- Altera qualquer arquivo em `database/migrations/`
- Altera qualquer arquivo em `routes/`
- Altera logica em `<script>` de componente Vue
- Altera testes em `tests/`
- Adiciona ou remove dependencia em `composer.json` ou `package.json`

### 3.2. Fases obrigatorias

| Fase | Obrigatoria? | Observacao |
|---|---|---|
| A — Descoberta | Nao | Pulada. |
| B — Estrategia Tecnica | Nao | Pulada. |
| C — Planejamento | Simplificado | Apenas spec.md obrigatorio. plan.md e tasks.md opcionais. |
| D — Execucao | Sim | Implementacao. Testes opcionais se mudanca e puramente visual/textual. |
| E — Pipeline de Gates | Sim | Apenas gates minimos (ver 3.3). |
| F — Encerramento | Opcional | Retrospectiva nao obrigatoria para L2. |

### 3.3. Gates obrigatorios

1. **verify-slice** — Validacao mecanica.
2. **review-pr** — Revisao estrutural.

Nenhum outro gate e obrigatorio. Security-gate, audit-tests, functional-gate e governance (master-audit) NAO sao executados em L2.

### 3.4. Aprovacao

- Auto-aprovado se a mudanca toca apenas arquivos da lista nao-logica (3.1).
- O orchestrator deve validar automaticamente os arquivos tocados via `git diff --name-only`.
- Se qualquer arquivo fora da lista for detectado, o orchestrator deve reclassificar para L3 automaticamente.

### 3.5. Tempo maximo

O slice L2 deve ser concluido em no maximo **1 sessao** (definida como o intervalo entre `/resume` e `/checkpoint`).

---

## 4. Trilha L3 — STANDARD

### 4.1. Quando usar

L3 e a trilha **padrao**. Todo slice e classificado como L3 a menos que o orchestrator proponha explicitamente outra trilha.

**Cenarios tipicos:**

- Feature nova (CRUD, endpoint de API, tela nova)
- Logica de negocio
- Regra de validacao
- Componente Vue com logica
- Migration de banco de dados (criacao de tabela, adicao de coluna)
- Integracao interna entre modulos

### 4.2. Fases obrigatorias

| Fase | Obrigatoria? | Observacao |
|---|---|---|
| A — Descoberta | Sim, se epico novo | Pulada se epico ja planejado. |
| B — Estrategia Tecnica | Sim, se epico novo | Pulada se arquitetura ja congelada. |
| C — Planejamento | Sim (completo) | spec.md + plan.md + tasks.md obrigatorios. audit-spec + review-plan obrigatorios. |
| D — Execucao | Sim | Testes red → green. Implementacao completa. |
| E — Pipeline de Gates | Sim (full) | Todos os 6 gates do pipeline completo. |
| F — Encerramento | Sim | slice-report + retrospectiva obrigatorios. |

### 4.3. Gates obrigatorios

Ordem definida no orchestrator.md:

1. **verify-slice** — Validacao mecanica.
2. **review-pr** — Revisao estrutural (somente se verify aprovado).
3. Em paralelo:
   - **security-review** — Revisao de seguranca.
   - **test-audit** — Auditoria de testes.
   - **functional-review** — Revisao funcional.
4. **governance (master-audit)** — Consolidacao dual-LLM (somente se todos os anteriores aprovados).

### 4.4. Gates condicionais

Alem dos 6 gates obrigatorios, gates condicionais sao ativados quando o slice toca determinado dominio:

| Dominio tocado | Gate condicional | Criterio de ativacao |
|---|---|---|
| `database/migrations/` | data-review | Qualquer arquivo de migration novo ou alterado |
| Integracao externa (API, webhook) | integration-review | Arquivo em `app/Services/External/` ou `app/Integrations/` |
| `config/logging.php`, `app/Observers/`, telemetria | observability-review | Alteracao em infra de observabilidade |

O orchestrator deve avaliar os arquivos tocados (`git diff --name-only`) e ativar gates condicionais automaticamente.

### 4.5. Aprovacao

- Nenhuma aprovacao adicional do PM e necessaria para iniciar L3.
- O PM e notificado apenas em escalacoes R6 ou decisoes de produto.

---

## 5. Trilha L4 — HIGH-RISK

### 5.1. Quando usar

O orchestrator deve classificar como L4 quando QUALQUER uma das condicoes abaixo for verdadeira:

- Integracao de pagamento (gateway, cobranca, estorno)
- Operacao fiscal (NF-e, SPED, calculo de imposto)
- Migracao de dados (alteracao de dados existentes em producao)
- Alteracao de autenticacao ou permissao (Policies, Guards, Middleware de auth)
- Alteracao de isolamento multi-tenant (scopes, middleware de tenant)
- Tratamento de dados regulados pela LGPD (dados pessoais, consentimento, exclusao)
- Alteracao de criptografia ou hashing (APP_KEY, bcrypt, encryption)
- Alteracao de infra de deploy (Dockerfile, CI pipeline, secrets management)

### 5.2. Fases obrigatorias

| Fase | Obrigatoria? | Observacao |
|---|---|---|
| A — Descoberta | Sim, se epico novo | Pulada se epico ja planejado. |
| B — Estrategia Tecnica | Sim, se epico novo | Pulada se arquitetura ja congelada. |
| C — Planejamento | Sim (completo + extras) | spec.md + plan.md + tasks.md + pre-review de seguranca. |
| D — Execucao | Sim | Testes red → green. Implementacao completa. |
| E — Pipeline de Gates | Sim (full + todos condicionais) | TODOS os gates, incluindo condicionais, mesmo se o slice nao tocar diretamente o dominio. |
| F — Encerramento | Sim | slice-report + retrospectiva + incident assessment. |

### 5.3. Gates obrigatorios

Todos os gates de L3 mais:

- **TODOS os gates condicionais** sao obrigatorios (data-review, integration-review, observability-review), independentemente dos arquivos tocados.
- Nenhum gate condicional pode ser pulado em L4.

### 5.4. Revisoes extras (pre-implementacao)

Antes da Fase D (execucao), as seguintes revisoes extras devem ser realizadas:

#### 5.4.1. Pre-review de seguranca

- **Owner:** security-expert (security-gate)
- **Input:** plan.md do slice
- **Output:** `specs/NNN/security-pre-review.json`
- **Criterio:** O security-expert (security-gate) deve avaliar o plan.md e emitir `approved` ou `rejected` com findings. Findings S1 ou S2 no pre-review bloqueiam o inicio da Fase D.
- **Objetivo:** Prevenir implementacao de design inseguro antes de escrever codigo.

#### 5.4.2. Validacao de migration (se aplicavel)

- **Owner:** data-expert (review)
- **Input:** plan.md + migration SQL planejada
- **Output:** `specs/NNN/data-migration-review.json`
- **Criterio:** Deve conter estrategia de rollback testada. Migration sem rollback viavel e S1.
- **Campos obrigatorios no JSON:**
  ```json
  {
    "has_rollback": true,
    "rollback_tested": true,
    "data_loss_risk": "none|partial|total",
    "estimated_downtime": "0s",
    "requires_maintenance_window": false
  }
  ```

#### 5.4.3. Revisao de contratos externos (se aplicavel)

- **Owner:** integration-expert (integration-gate)
- **Input:** plan.md + contratos de API externa
- **Output:** `specs/NNN/integration-pre-review.json`
- **Criterio:** Contratos de API externa devem ter versionamento, timeout, retry e fallback documentados.

### 5.5. Aprovacao

- O PM deve ser notificado no inicio de cada slice L4.
- O PM deve ser notificado no final de cada slice L4 (antes do merge).
- O orchestrator deve registrar ambas as notificacoes em `specs/NNN/pm-notifications.json`.

### 5.6. Tempo maximo

Sem limite fixo, mas o orchestrator deve emitir alerta ao PM se um slice L4 ultrapassar 3 sessoes sem conclusao.

---

## 6. Processo de classificacao

### 6.1. Quem propoe a trilha

O **orchestrator** deve propor a trilha para cada slice no momento da criacao (`/new-slice` ou `/start-story`).

### 6.2. Como o orchestrator decide

O orchestrator deve avaliar os seguintes criterios na ordem:

1. **L1 (HOTFIX):** Existe incidente ativo? Se sim, propor L1.
2. **L4 (HIGH-RISK):** O slice toca algum dominio listado em 5.1? Se sim, propor L4.
3. **L2 (SMALL CHANGE):** O slice toca apenas arquivos nao-logicos (lista 3.1)? Se sim, propor L2.
4. **L3 (STANDARD):** Caso contrario, propor L3 (default).

### 6.3. Quem confirma a trilha

| Trilha proposta | Confirmacao necessaria |
|---|---|
| L1 — HOTFIX | PM deve confirmar explicitamente. |
| L2 — SMALL CHANGE | Auto-confirmado. Reclassificado automaticamente para L3 se arquivos logicos forem detectados. |
| L3 — STANDARD | Auto-confirmado. |
| L4 — HIGH-RISK | PM deve ser notificado. PM pode rebaixar para L3 com justificativa escrita. |

### 6.4. Reclassificacao durante execucao

Se durante a Fase D o escopo do slice mudar (ex: feature L3 que precisa alterar autenticacao), o orchestrator deve:

1. Detectar a mudanca de escopo via analise dos arquivos tocados.
2. Propor reclassificacao ao PM.
3. Se reclassificado para L4, executar os gates extras retroativamente.
4. Registrar a reclassificacao em `specs/NNN/lane-history.json`:
   ```json
   {
     "history": [
       {
         "lane": "L3",
         "reason": "Feature CRUD padrao",
         "timestamp": "2026-04-16T10:00:00Z"
       },
       {
         "lane": "L4",
         "reason": "Slice passou a alterar Policy de autorizacao",
         "reclassified_by": "orchestrator",
         "confirmed_by": "pm",
         "timestamp": "2026-04-16T14:00:00Z"
       }
     ]
   }
   ```

### 6.5. Registro da trilha

A trilha classificada deve ser registrada em:

1. `specs/NNN/spec.md` — campo `lane:` no frontmatter.
2. `project-state.json` — campo `lane` no objeto do slice ativo.
3. JSON de cada gate — campo `lane` no output.

---

## 7. Tabela de referencia rapida

| Criterio | L1 HOTFIX | L2 SMALL | L3 STANDARD | L4 HIGH-RISK |
|---|---|---|---|---|
| Fases | D, E, F | C*, D, E | A*-F | A*-F |
| spec.md | Nao | Sim | Sim | Sim |
| plan.md | Nao | Nao | Sim | Sim |
| audit-spec | Nao | Nao | Sim | Sim |
| review-plan | Nao | Nao | Sim | Sim |
| verify-slice | Sim | Sim | Sim | Sim |
| review-pr | Nao | Sim | Sim | Sim |
| security-review | Sim | Nao | Sim | Sim |
| test-audit | Nao | Nao | Sim | Sim |
| functional-review | Nao | Nao | Sim | Sim |
| governance (master-audit) | Nao | Nao | Sim | Sim |
| Gates condicionais | Nao | Nao | Se aplicavel | TODOS obrigatorios |
| Pre-review seguranca | Nao | Nao | Nao | Sim |
| PM aprova inicio | Sim | Nao | Nao | Notificado |
| Retrospectiva | Nao | Nao | Sim | Sim |
| Incident report | Sim | Nao | Nao | Nao |
| Tempo maximo | 2h | 1 sessao | — | — |

*A e B puladas se epico ja planejado. C simplificado em L2.

---

## 8. Matriz de inputs por trilha

A tabela abaixo define quais artefatos sao obrigatorios, opcionais ou substituidos em cada trilha. Os gates devem consultar esta matriz para determinar seus inputs obrigatorios.

| Artefato | L1 (Hotfix) | L2 (Small Change) | L3 (Standard) | L4 (High-Risk) |
|---|---|---|---|---|
| spec.md | NAO — substituido por bug-brief.md (descricao do bug, impacto, teste de regressao) | spec-lite.md (ACs simplificados, sem jornada completa) | OBRIGATORIO (completo) | OBRIGATORIO (completo) |
| plan.md | NAO — substituido por fix-strategy.md (1 paragrafo: o que mudar e por que) | OPCIONAL — se omitido, o diff serve como plano | OBRIGATORIO (completo) | OBRIGATORIO (completo + security pre-review) |
| tasks.md | NAO | NAO | OBRIGATORIO | OBRIGATORIO |
| testes red (pre-implementation) | NAO — teste de regressao escrito junto com fix | OPCIONAL — se mudanca e so texto/CSS | OBRIGATORIO | OBRIGATORIO |
| verification.json input package | bug-brief.md + diff + testes de regressao | spec-lite.md + diff + testes (se existem) | spec.md + plan.md + diff + testes | spec.md + plan.md + diff + testes |

**Templates:**

- L1: `docs/templates/bug-brief.md` + `docs/templates/fix-strategy.md`
- L2: `docs/templates/spec-lite.md`
- L3/L4: templates padrao de spec.md, plan.md e tasks.md

**Regra normativa:** todo gate deve validar que os inputs obrigatorios para a trilha do slice estao presentes antes de iniciar a auditoria. Se um input obrigatorio estiver ausente, o gate deve emitir finding S1 com `description: "Input obrigatorio ausente: <artefato>"`.

---

## 9. Vigencia

Este documento entra em vigor imediatamente e aplica-se a todos os slices criados a partir da data de publicacao. Slices em andamento mantem a classificacao implicita L3 (STANDARD) a menos que reclassificados explicitamente.
