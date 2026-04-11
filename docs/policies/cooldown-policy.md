# Política de cooldown de 24 horas para classes críticas

**Versão:** 1.0.0 — 2026-04-11
**Origem:** item 6.5 do plano de ação da meta-auditoria #2 (`docs/audits/meta-audit-completeness-2026-04-10-action-plan.md`), seção "Bloco 6 — governança e operação".
**Autoridade de alteração:** ver §7.

---

## 1. Objetivo

Forçar um intervalo mínimo de **24 horas** entre dois commits consecutivos que tocam a mesma classe crítica de arquivo. O cooldown existe para evitar dois modos de falha observados no Kalibrium V1 e na meta-auditoria #2:

- **Deriva cultural por pressa** — uma decisão constitucional (regra R-x, princípio P-y) alterada às 10h e "corrigida" às 11h do mesmo dia por desconforto social não deu tempo para o agente, para o PM e para os sub-agents verifier/reviewer absorverem a mudança. O resultado é drift silencioso: a regra oficial muda várias vezes por dia e ninguém sabe mais qual é a atual.
- **Correção emocional de ADR** — ADRs aceitas ganham status `accepted` justamente porque foram deliberadas. Alterar um ADR `accepted` menos de 24h depois da aceitação sinaliza que a deliberação não estava madura. A política força o agente e o PM a abrirem um novo ADR (`supersedes`) em vez de sobrescrever o anterior.

O cooldown **não** é uma regra de estilo ou de ritmo. É uma defesa contra o mesmo modo de falha que matou o V1: mudar o alicerce sem tempo de validar.

---

## 2. Classes críticas

Cada classe lista os caminhos que estão sob cooldown. Um commit é "de uma classe" se o diff staged tocar **pelo menos um** arquivo listado nessa classe.

### 2.1 Constitution

**Caminhos:**
- `docs/constitution.md`
- `CLAUDE.md` (o arquivo raiz de instruções, referenciado por R1 e por `session-start.sh`)

**Motivo:** esses dois arquivos são a fonte de autoridade do projeto (P5). Qualquer alteração em R1-R12 ou P1-P9 precisa ser absorvida por todos os sub-agents, por hooks e pelo PM antes do próximo commit relacionado.

### 2.2 ADR aceito

**Caminhos:**
- Qualquer arquivo em `docs/adr/` cujo conteúdo staged contenha a linha `status: accepted` (após aplicar o diff).
- `docs/TECHNICAL-DECISIONS.md` (índice vivo de ADRs).

**Motivo:** transição para `accepted` é o ponto em que o ADR vira obrigatório. Alterar uma ADR `accepted` no mesmo dia é sinal de deliberação incompleta. A saída correta é abrir ADR novo com `supersedes: NNNN` referenciando o anterior.

**Exceção registrada:** transições de `draft` para `proposed` ou de `proposed` para `accepted` **dentro** do mesmo ADR não contam como alteração da classe — o que conta é uma alteração **no corpo** de uma ADR que já está `accepted`.

### 2.3 Finance / budget

**Caminhos:**
- Qualquer arquivo em `docs/finance/` (operating-budget, pricing-assumptions, fiscal-policy).
- Qualquer arquivo em `docs/procurement/` (procurement-tracker, contratos em curso).

**Motivo:** decisão financeira errada tem consequência legal (fisco) e contratual (fornecedor, cliente). Uma margem recalculada três vezes no mesmo dia indica que a base numérica não estava estável. O cooldown obriga a revisar a hipótese em vez de iterar valores.

### 2.4 Compliance

**Caminhos:**
- Qualquer arquivo em `docs/security/` (threat-model, lgpd-base-legal, dpia, rot, incident-response-playbook, backup-dr-policy).
- Qualquer arquivo em `docs/compliance/` (policies por domínio, RFPs de consultor fiscal/metrologia).
- Qualquer arquivo em `docs/policies/` cujo conteúdo trate de §3 desta política ou da r6-r7-policy.

**Motivo:** conformidade regulatória não tem espaço para "ajuste rápido". Mudar a base legal LGPD, o DPIA ou o playbook de incidente exige o mesmo tempo de absorção que uma ADR — aliás, frequentemente exige um ADR.

---

## 3. Regra operacional

### 3.1 A regra

Para cada classe crítica listada em §2:

> Se o commit anterior que tocou **qualquer arquivo dessa classe** foi criado há **menos de 24 horas**, o novo commit é bloqueado pelo `pre-commit-gate.sh` com mensagem clara apontando o commit anterior, a classe envolvida e o tempo faltante para liberar.

O cálculo de 24 horas usa o timestamp do commit anterior (`git log -1 --format=%ct`) vs o timestamp atual do sistema (`date +%s`).

### 3.2 Classes independentes

O cooldown é **por classe, não global**. Commits em classes diferentes podem rodar sem espera entre si. Um commit em `docs/finance/` não bloqueia um commit em `docs/security/` no mesmo dia, e vice-versa.

### 3.3 Escopo do cooldown

O cooldown vale para commits locais e para commits feitos via skill (`/commit`). Não vale para **reverts** dentro de um incidente registrado em `docs/incidents/` — o revert está no fluxo de recuperação, não no fluxo de alteração deliberada.

### 3.4 Como desbloquear um commit legítimo dentro de 24h

O cooldown **não tem bypass pelo PM em modo normal**. A política é uma defesa ativa, não uma sugestão. As duas saídas legítimas:

1. **Esperar.** O intervalo passa sozinho. Esse é o caminho desejado em 90% dos casos.
2. **Abrir incidente.** Se o motivo for hotfix de segurança (§3.3 da `r6-r7-policy.md`) ou correção de dado pessoal vazado, o PM abre `docs/incidents/security-YYYY-MM-DD.md` **antes** do commit, descreve o motivo, e o hook aceita o commit consultando o arquivo de incidente como prova de contexto.

Hotfix sem incidente registrado **não** é aceito. A política prefere um bloqueio falso-positivo a um bypass silencioso.

---

## 4. Implementação no `pre-commit-gate.sh`

O hook `scripts/hooks/pre-commit-gate.sh` é **selado** (CLAUDE.md §9). Edição dele passa pelo procedimento de relock manual do PM em terminal externo. Esta seção descreve o comportamento que o hook deve ter — a implementação depende do relock, da mesma forma que os itens 4.2, 4.5 e 5.3 do plano.

### 4.1 Pseudocódigo esperado

```
STAGED = git diff --cached --name-only

# Para cada classe, verificar se algum arquivo staged pertence a ela
for CLASS in constitution adr-accepted finance compliance:
  FILES_IN_CLASS = match(STAGED, class_paths[CLASS])
  if FILES_IN_CLASS vazio: continue

  # Achar último commit que tocou essa classe
  LAST_COMMIT = git log -1 --format=%H --  <class_paths[CLASS]>
  if LAST_COMMIT vazio: continue   # nunca houve commit na classe — libera

  LAST_TS = git log -1 --format=%ct $LAST_COMMIT
  NOW = date +%s
  DELTA = NOW - LAST_TS

  if DELTA < 86400:
    # Checar exceção de incidente aberto
    if classe for compliance e existe docs/incidents/security-YYYY-MM-DD.md com data = hoje:
      log "cooldown-policy: exceção de incidente aceita ($INCIDENTE)"
      continue
    echo "cooldown-policy: classe $CLASS ainda em cooldown — $((86400-DELTA))s restantes (último commit: $LAST_COMMIT)" >&2
    exit 1
```

### 4.2 Telemetria

Cada bloqueio e cada liberação por exceção gera uma linha em `.claude/telemetry/cooldown-policy.log` via `scripts/record-telemetry.sh` (arquivo append-only). O log carrega: data/hora, classe, último commit, motivo do bloqueio ou exceção aplicada, operador.

### 4.3 Falso-positivo conhecido e aceito

Commit em que o único arquivo tocado é um tracker de progresso (`docs/audits/progress/*.md`) que **referencia** um arquivo de classe crítica, mas **não altera** o arquivo da classe em si, **não** é bloqueado. O hook olha o caminho do arquivo staged, não o conteúdo referenciado.

---

## 5. Por que 24h e não outro intervalo

- Menos que 12h sobe demais o custo de operação em dia de trabalho contínuo. Dois commits seguidos legítimos (ex.: adicionar uma subseção e corrigir um erro de português) virariam bloqueio constante.
- Mais que 48h trava o progresso em casos em que o PM e o agente estão de fato alinhados.
- 24h coincide com o ciclo natural de trabalho (um dia útil + pernoite). É o menor intervalo que força pelo menos uma noite de separação entre a decisão e a revisão.
- Em caso de dúvida sobre o intervalo, a política prefere o intervalo mais longo (mais seguro) em vez do mais curto.

A escolha não é um valor mágico. Pode ser revisada via §7, mas a justificativa tem de vir de dado observado em `docs/incidents/` ou em retrospectiva — não de conforto operacional.

---

## 6. Como o agente aplica esta política

1. Antes de qualquer commit que toca uma das classes críticas, o agente **anuncia no chat** qual é a classe e em qual horário o cooldown anterior expirou (ou expira), em linguagem de produto (R12).
2. Se o cooldown não expirou, o agente **não** tenta commitar e **não** pede bypass. Oferece ao PM duas opções: esperar (indicando o horário exato em que libera) ou abrir incidente justificado.
3. Se o PM insistir em commit fora do cooldown sem incidente, o agente recusa e aponta esta política.
4. O agente **não** tenta contornar o hook via rename, split, `git stash`, ou edição fora do Claude Code. Tentativa de contorno é tratada como violação de R9 (zero bypass de gate) e registrada como incidente.

---

## 7. Como esta política evolui

Qualquer alteração em §2 (caminhos das classes), §3 (regra operacional) ou §5 (intervalo de 24h) exige:

- ADR na pasta `docs/adr/` com `status: accepted` justificando a mudança.
- Dado observado: pelo menos um registro em `docs/incidents/` ou um item em retrospectiva pós-bloco mostrando um falso-positivo sistemático (bloqueio legítimo recusado) ou um falso-negativo (drift passou por causa do cooldown curto demais).
- Atualização simultânea do `pre-commit-gate.sh` via procedimento de relock manual (§9 do CLAUDE.md).

Alterações em §4 (implementação) seguem o fluxo normal de alteração de hook: relock manual do PM em terminal externo.

Alterações em §6 (como o agente aplica) seguem o fluxo normal de alteração de política: commit atômico, reviewer independente, vocabulário R12 mantido.

---

## 8. Relação com outras políticas

- **`docs/policies/r6-r7-policy.md`** — a r6-r7-policy define as **categorias em que o PM não pode dar override** na rejeição do verifier/reviewer. A cooldown-policy é complementar: define **o intervalo mínimo** entre duas alterações legítimas, mesmo quando ambas passariam no verifier. As duas políticas atuam em eixos diferentes (conteúdo vs tempo) e se reforçam.
- **`docs/governance/harness-evolution.md`** — define a cadência mensal de revisão de regras R1-R12. Quando a revisão mensal propõe uma alteração constitucional, a cooldown-policy garante que a alteração tenha pelo menos 24h de absorção antes da próxima alteração relacionada.
- **`docs/governance/raci.md`** — identifica quem é **Accountable** por cada classe. Em caso de bloqueio por cooldown, o agente consulta a RACI para saber a quem remeter o pedido de espera ou de incidente.

---

## 9. Histórico

- **2026-04-11** — criação do arquivo na sessão 02 da execução do plano da meta-auditoria #2, item 6.5. Implementação no `pre-commit-gate.sh` depende de relock manual do PM, consistente com os itens 4.2, 4.5 e 5.3 do plano.
