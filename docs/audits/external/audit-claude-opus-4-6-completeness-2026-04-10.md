# Auditoria externa de completude — Claude Opus 4.6 (1M context)

**Data:** 2026-04-10
**Auditor:** Claude Opus 4.6 (1M context)
**Duração da auditoria:** ~30 minutos
**Materiais lidos:**

- `CLAUDE.md` (raiz)
- `docs/constitution.md` (v1.1.0, P1–P9 + R1–R12 + DoD)
- `docs/audits/meta-audit-2026-04-10.md` (consolidação das 3 auditorias técnicas)
- `docs/audits/meta-audit-2026-04-10-action-plan.md` (plano de 7 blocos + trilha paralela)
- `docs/audits/progress/meta-audit-tracker.md` (status atual)
- `docs/glossary-domain.md` (10,3KB)
- `docs/adr/0000-template.md`, `docs/adr/0002-mcp-policy.md` (apenas 2 ADRs no repo)
- `docs/incidents/pr-1-admin-merge.md`, `docs/incidents/bloco1-admin-bypass-2026-04-10.md`
- `docs/decisions/pm-decision-meta-audit-2026-04-10.md`
- `docs/compliance/rfp-consultor-metrologia.md`, `docs/compliance/rfp-consultor-fiscal.md`
- `docs/guide-backlog.md`, `docs/harness-limitations.md`
- `docs/reference/v1-post-mortem.md`, `docs/reference/roles-from-bmad.md`
- `docs/TECHNICAL-DECISIONS.md` (929 bytes — esqueleto)
- `docs/slice-registry.md` (238 bytes — esqueleto)
- Árvore completa do repositório (`find C:/PROJETOS/saas/kalibrium-v2/docs -type f -name '*.md'` → 28 arquivos)

**Materiais obrigatórios da seção 6 do prompt que NÃO existem (registrados como finding):**

- ❌ `docs/reference/ideia-v1.md` — **NÃO EXISTE**. A pasta `docs/reference/` contém apenas `roles-from-bmad.md` e `v1-post-mortem.md`. Este arquivo é citado como leitura obrigatória pelo próprio prompt da auditoria (seção 6, item 6) e referenciado como "visão de produto congelada" em pelo menos 4 outros lugares do repositório (ver A e K1).
- ❌ `README.md` (raiz) — **NÃO EXISTE**. O item 11 do prompt diz "se existir" — não existe.

---

## A. Documentação fundacional de produto

**Status:** **AUSENTE quase por completo**.

**Evidências:**

- `find docs -type f -name '*.md'` retorna 28 arquivos. **Zero** são sobre produto. Todos cobrem governança (constitution, audits, incidents, decisions, compliance) ou referência (glossary, post-mortem).
- Não existem: PRD, MVP-SCOPE, personas, jornadas, requisitos funcionais/não-funcionais, roadmap de versões, modelo de negócio, pricing.
- `docs/reference/ideia-v1.md` é citado como autoridade canônica de produto em **4 lugares**:
  - `docs/compliance/rfp-consultor-metrologia.md §8 Confidencialidade` — lista o arquivo no pacote a ser entregue ao consultor
  - `docs/compliance/rfp-consultor-fiscal.md §8 Confidencialidade` — idem
  - `docs/reference/v1-post-mortem.md > O que preservar` — declara que o arquivo é preservado
  - `docs/audits/completeness-audit-prompt-2026-04-10.md §6` — leitura obrigatória desta auditoria

  **O arquivo não existe.** É uma referência fantasma.
- O glossário (`docs/glossary-domain.md`) é vocabulário, não escopo. Não diz **quais** módulos entram no MVP, **quem** é o usuário-alvo, **qual** é o caminho crítico, **como** o produto se cobra.
- O `docs/reference/v1-post-mortem.md > O que V1 fez certo` declara: "ideia.md é base para `docs/MVP-SCOPE.md` do V2." **`docs/MVP-SCOPE.md` também não existe.**

**Por que importa:**

1. RFPs já foram redigidos a consultores externos referenciando um documento de produto que não existe. Quando o consultor de metrologia for contratado (decisão 0.2 já aprovada) e abrir o pacote, vai pedir o arquivo. PM vai improvisar verbalmente. 50 casos golden vão sair contextualizados no laboratório errado.
2. O `verifier`/`reviewer` precisa de uma fonte canônica para validar "este slice está dentro do MVP?". Sem `mvp-scope.md`, qualquer agente aceita slice fora de escopo se o PM perguntar "isso cabe?" — exatamente o loop subjetivo que P1 ("gate objetivo precede opinião") quer eliminar.
3. O `/decide-stack` (Bloco 2) precisa de RNFs escritos para escolher entre opções. Se o RNF é "VPS Hostinger 8GB RAM, 200 tenants, 50 calibrações/dia/tenant", isso muda completamente a stack viável. Sem RNF, ADR-0001 vai sair "vibe-based" — exatamente o anti-pattern 7 que o V1 post-mortem critica.

**Recomendação:**
Criar **antes do Bloco 2** (não depois):
- `docs/product/ideia-v1.md` (restaurar do `C:\PROJETOS\KALIBRIUM SAAS\` mencionado no V1 post-mortem ou redigir do zero)
- `docs/product/mvp-scope.md` (módulos IN/OUT, com decisão registrada)
- `docs/product/personas.md` (mín. 3 personas: gerente de lab, técnico calibrador, cliente final do certificado)
- `docs/product/journeys.md` (1 fluxo crítico: pedido → execução → certificado → cobrança)
- `docs/product/nfr.md` (RNFs numéricos: RPS, p95, RAM/CPU teto, RPO/RTO, custo de token/slice teto, tenants alvo)

---

## B. Decisões arquiteturais fundacionais

**Status:** **PARCIAL com lacuna estrutural grave**.

**Evidências:**

- `ls docs/adr/` retorna apenas 2 arquivos: `0000-template.md` (template) e `0002-mcp-policy.md`. **ADR-0001 está intencionalmente ausente** (Bloco 2). Isso é aceitável.
- **Mas:** não há ADR para multi-tenancy, modelo de identidade/auth, modelo de dados de calibração, criptografia, observabilidade, formato de certificado (PDF/A?), versionamento de API, fila/jobs, escolha de runtime de cálculo metrológico.
- O V1 post-mortem cita `multi-tenancy schema-per-tenant é válida se a stack final suportar; decisão reentra via ADR-0001/0003`. **ADR-0003 não é nem reservado** no repo.
- `docs/TECHNICAL-DECISIONS.md` (929 bytes) é declarado como **leitura obrigatória em toda sessão** pelo `CLAUDE.md §0` ("índice vivo de ADRs"). 929 bytes = praticamente vazio. O `session-start.sh` valida que existe — mas não que tem conteúdo útil.
- O salto numérico 0000 → 0002 é fragilidade organizacional: o `architect` ao gerar `plan.md` pode nem perceber o slot vazio 0001.
- Não há ADR sobre **qual é a fonte canônica de cálculo metrológico** (lib externa? código próprio? wrapper sobre R/Python?). Isso bate diretamente com o trabalho do consultor de metrologia (RFP §3.1 E1) — os 50 casos golden vão validar **o quê**, exatamente?

**Recomendação:**

- Renumerar `0002-mcp-policy.md` ou criar `0001-stack.md` como rascunho com `status: draft, blocked-by: bloco-2` — para manter contiguidade.
- Abrir como `status: proposed` (não decididos, mas registrados): ADRs de multi-tenancy, identity/auth, modelo de dados de calibração, criptografia em repouso, formato de certificado, versionamento de API, observabilidade, fila/jobs, runtime de cálculo metrológico. Isso é o **mapa do território** que o `architect` vai usar — sem ele, cada slice vai inventar a roda.
- Preencher `docs/TECHNICAL-DECISIONS.md` com tabela mínima `| ADR | Título | Status | Última revisão |`. Adicionar gate `wc -l` no `session-start.sh` (mín. 20 linhas) para detectar esqueleto vazio.

---

## C. Governança de segurança e compliance

**Status:** **AUSENTE em quase todas as dimensões fora do harness técnico**.

**Evidências:**

- `find docs -type d` não retorna `docs/security/`. Não existe.
- Nenhum threat model do **produto** existe. O harness foi auditado 3x; o produto Kalibrium não tem STRIDE, nem mapa de superfície de ataque, nem inventário de dados pessoais.
- LGPD: zero artefato operacional. Não há base legal mapeada por finalidade (calibração? cobrança? marketing?), não há DPIA, não há contrato de operador (Kalibrium é controlador? operador? co-controlador com o lab?), não há Registro de Operações de Tratamento (Art. 37 LGPD). O glossário tem termos LGPD, mas vocabulário ≠ política.
- Sem plano de resposta a incidente do **produto** — só de incidente do harness. Quando vazar dado de cliente, qual é o playbook? Quem comunica ANPD em 72h?
- Sem política de retenção, sem RPO/RTO numérico, sem plano de backup, sem DR. `docs/harness-limitations.md` cobre só débito git e symlinks Windows.
- Sem política de dependências/supply-chain. Sem SBOM. Sem assinatura de release. Sem política de CVE.
- Sem criptografia documentada (em repouso, em trânsito, gestão de chaves, rotação).
- Sem gestão de segredos documentada (cofre? variável de ambiente? quem rotaciona? cadência?).

**Por que importa:** o Bloco 7 (re-auditoria pré Dia 1) vai aprovar um harness pronto para construir slice de feature, mas o **produto** continua não-defensável legalmente. PM, sendo não-técnico, não vai conseguir responder consulta de DPO no dia que um cliente perguntar.

**Recomendação:**

Criar **trilha paralela "Compliance Produto"** (análoga à trilha de consultores, mas conduzida por agentes sob revisão final de DPO horista):

- `docs/security/threat-model.md` (STRIDE sobre arquitetura proposta no Bloco 2)
- `docs/security/lgpd-base-legal.md` (matriz finalidade × base legal × titular × controlador/operador)
- `docs/security/dpia.md`
- `docs/security/rot.md` (Registro de Operações de Tratamento — exigência Art. 37)
- `docs/security/incident-response-playbook.md` (incluindo prazo ANPD 72h, comunicação a titular)
- `docs/security/backup-dr-policy.md` (RPO/RTO numérico)
- `docs/security/secrets-policy.md`
- `docs/security/dependency-policy.md`
- `docs/security/contrato-operador-template.md`

---

## D. Processos operacionais

**Status:** **PARCIAL — bem coberto para harness, ausente para produto.**

**Evidências:**

- Para o ciclo de slice: `CLAUDE.md §6` define 15 passos, há `/new-slice`, `/verify-slice`, `/slice-report`, `/retrospective`. Forte.
- Para incidentes de governança: `docs/incidents/` tem 2 arquivos com schema consistente, e é parte do DoD via R9 (commit `pre-commit-gate.sh` detecta `--no-verify`).
- **Para operação de produto: nada existe.**
  - Sem `docs/operations/runbooks/` (deploy, rollback, restore, novo-tenant, gestão-de-segredo).
  - Sem `docs/operations/release-process.md`. Como sai uma versão? Tag? Changelog? Quem aprova?
  - Sem `docs/operations/oncall.md` (mesmo que oncall seja "PM verifica dashboard 1x/dia").
  - Sem `docs/operations/sla-slo.md`.
  - Sem template de postmortem de produção (existe só template de retrospectiva de slice).
- Sem cadência definida de "auditoria de harness recorrente". `guide-auditor` é mencionado no `CLAUDE.md §7` mas não há `.github/workflows/` que o roda em cron, nem `docs/operations/audit-cadence.md`.

**Recomendação:**

- `docs/operations/runbooks/` com pelo menos: `deploy.md`, `rollback.md`, `restore.md`, `novo-tenant.md`, `recuperacao-de-desastre.md`, `rotacao-de-segredos.md`.
- `docs/operations/release-process.md` (tag → changelog → smoke → canário → 100%).
- `docs/operations/oncall.md`.
- `docs/operations/sla-slo.md` com SLO numérico e janela de manutenção.
- Cron `weekly-harness-audit.yml` que roda `guide-auditor` semanalmente (após Bloco 5).

---

## E. Artefatos de domínio regulado

**Status:** **PARCIAL — bem encaminhado em metrologia/fiscal, vazio em REP-P/LGPD/ICP-Brasil.**

**Evidências:**

- RFPs metrologia (`docs/compliance/rfp-consultor-metrologia.md`) e fiscal (`docs/compliance/rfp-consultor-fiscal.md`) são **muito bem feitos**. Escopo concreto, entregáveis numerados (E1–E4 / F1–F5), critério de aceite, cronograma, modelo de pagamento, FAQ antecipado. Trabalho de qualidade — registro como força real.
- O glossário cobre vocabulário metrologia, REP-P, ICP-Brasil, LGPD, fiscal — adequado como dicionário para o `verifier`/`reviewer`.
- **Mas:** o tracker (`docs/audits/progress/meta-audit-tracker.md`) marca REP-P/LGPD/ICP-Brasil como `[ ] Registrar como fora do MVP em docs/compliance/out-of-scope.md (requer advogado trabalhista + DPO, decisão futura)`. **O arquivo `docs/compliance/out-of-scope.md` não existe.** A decisão de "fora do MVP" não foi registrada formalmente.
- **Rastreabilidade normativa dos cálculos** está endereçada apenas no formato CSV do RFP (`normative_reference` por caso) — mas não há decisão sobre **onde** essa rastreabilidade vai morar no produto: coluna na tabela de calibração? Anexo do certificado? Log de auditoria? Sem isso definido **antes** do consultor entregar, o trabalho do consultor pode virar dado órfão.
- **Revalidação periódica**: o RFP fiscal §7 menciona "revalidação anual (não obrigatória)" como opção. Nenhum cronograma de revalidação está registrado em local executável (cron, calendário PM, script). Vai ser esquecido em 12 meses.
- **O que IA não pode fazer mesmo com consultor**: nenhum documento lista isso. Integração SEFAZ via webservice exige certificado A1/A3 ICP-Brasil + endpoints específicos por UF; assinatura HSM A3 não pode ser feita por agente. Isso precisa de `docs/compliance/ia-no-go.md` ("módulos que IA não constrói, mesmo com consultor — precisam de integrador humano").
- **Glossário como autoridade canônica para o verifier**: o `verifier`/`reviewer` precisa **rodar** uma checagem de glossário. `check-r12-vocabulary.sh` está no Bloco 4.2 — só blocklist técnica, não validação semântica de termos do domínio. Sub-agent `domain-expert` aparece em Bloco 6.1 mas não implementado.

**Recomendação:**

- Criar `docs/compliance/out-of-scope.md` **agora** (não esperar Bloco 6) com decisão formal sobre REP-P/LGPD/ICP-Brasil.
- Criar `docs/compliance/normative-traceability-policy.md` definindo **onde** a citação normativa de cada cálculo persiste no produto (data model + UI + audit log).
- Criar `docs/compliance/ia-no-go.md` listando módulos que **não** podem ser implementados por agente mesmo com consultor (SEFAZ, HSM A3, ICP-Brasil A3), com decisão sobre caminho alternativo (terceirizar via emissor de mercado, adiar, integrar via API de gateway fiscal).
- Adicionar item ao Bloco 6 ou trilha paralela: `docs/compliance/revalidation-calendar.md` com cronograma de revalidação anual + responsável + cron de notificação.
- Antecipar parcialmente o `domain-expert` sub-agent (Bloco 6.1): mesmo um agente que apenas valida `docs/glossary-domain.md` contra `spec.md` já reduz erros conceituais.

---

## F. Estrutura do repositório e organização

**Status:** **PARCIAL — boa para harness, fragmentada para produto/governança.**

**Evidências:**

- A estrutura `docs/audits/`, `docs/decisions/`, `docs/incidents/`, `docs/compliance/`, `docs/adr/`, `scripts/hooks/`, `.claude/agents/`, `.claude/skills/` é coerente.
- **Mas:** não existem `docs/product/`, `docs/security/`, `docs/operations/`, `docs/runbooks/`, `docs/finance/`, `docs/governance/`. As pastas que deveriam abrigar documentação ausente também não existem.
- `docs/templates/` tem `plan.md`, `spec.md`, `tasks.md` — mas zero template para PRD, ADR (existe 0000), threat-model, runbook, postmortem-de-prod, RFP (RFPs foram criados ad-hoc, sem template).
- `docs/external-audit-prompt.md` (11KB) e `docs/audits/completeness-audit-prompt-2026-04-10.md` (14KB) coexistem — duplicação aparente. O primeiro é a rodada técnica anterior; o segundo é desta auditoria. Política de "qual é a fonte canônica de prompt de auditoria" não está documentada.
- **Não existe `README.md` na raiz do repo.** Para um projeto público no GitHub (`github.com/roldaobatista/kalibrium-v2`), é o primeiro arquivo que qualquer auditor externo, consultor recém-chegado, ou potencial cliente alpha abre. Ausência total = sinal de imaturidade que afeta credibilidade externa, mesmo com internals fortes.
- `docs/slice-registry.md` tem 238 bytes — provavelmente esqueleto. É declarado como fonte de verdade sobre o que foi entregue. Vazio = primeiro slice vai inaugurá-lo sem contrato definido.

**Recomendação:**

- Criar pastas vazias com `.gitkeep` + `README.md` interno por pasta: `docs/product/`, `docs/security/`, `docs/operations/`, `docs/finance/`, `docs/governance/`, `docs/runbooks/`. Isso reserva espaço e força próximos slices a colocar artefato no lugar certo.
- Criar `README.md` raiz mínimo (5 seções: o que é, status alpha/pre-launch, links para constitution/audits/compliance, instruções de execução local — placeholder até Bloco 2 — e como contribuir).
- Consolidar prompts de auditoria em `docs/audits/prompts/`: mover `docs/external-audit-prompt.md` para `prompts/technical-2026-04-10.md` e o atual prompt para `prompts/completeness-2026-04-10.md`.
- Criar templates faltantes em `docs/templates/`: `prd.md`, `threat-model.md`, `runbook.md`, `postmortem-prod.md`, `rfp.md`.

---

## G. Papéis, responsabilidades e limites

**Status:** **PARCIAL — explícito para harness, vago para produto/governança.**

**Evidências:**

- `docs/reference/roles-from-bmad.md` (3,1KB) lista papéis BMAD herdados. Mas o V1 post-mortem (anti-pattern 4) diz que BMAD foi cortado. O arquivo continua no repo — risco de confusão entre o que está vigente (R11/R12) e o que é histórico.
- Constitution R11 estabelece "humano = PM, agentes = equipe técnica" e R12 estabelece linguagem de produto. **Mas:** não há matriz RACI explícita. Quem assina (mesmo como rubber-stamp) decisão de:
  - Custo de infra > X reais/mês?
  - Custo de tokens > Y reais/mês?
  - Adicionar novo MCP (ADR-0002 não diz quem aprova adições)?
  - Comprar contrato com consultor?
  - Aceitar PR em main fora de horário comercial?
- O incident `docs/incidents/bloco1-admin-bypass-2026-04-10.md > Contagem de bypasses acumulados` declara: **"3 bypasses acumulados"** após o push do próprio commit do incident. Isso é a vulnerabilidade exata que P9 ("zero bypass de gate") proíbe. Há plano para fechar (Bloco 5.3 e 5.4) — **mas não há limite numérico explícito** de "se chegar a X bypasses antes do Bloco 5 fechar, projeto pausa". Risco de normalização real.
- O documento `docs/decisions/pm-decision-meta-audit-2026-04-10.md` registra decisões mas tem apenas "**Assinatura: roldaobatista**" como texto. Não está selado (`relock-harness.sh` não inclui `docs/decisions/`). Em sessão futura, agente pode editar e ninguém perceberá. É o mesmo problema que justificou o Bloco 1 (selar harness contra auto-modificação) — mas a decisão de aceitar o Bloco 1 não está protegida pelo Bloco 1.
- Não está claro **quem aprova ADRs** quando o PM é não-técnico. ADR-0002 foi aceito, mas por quem? O `architect` propõe e o PM assina sem entender? O `reviewer` valida? Sem isso, ADR vira rubber-stamp.

**Recomendação:**

- Criar `docs/governance/raci.md` com matriz: linha = tipo de decisão (custo, MCP, ADR, pausa, contrato, mudança constitution), coluna = (PM | architect | reviewer | verifier | consultor externo | advisor mensal). Cada célula = R/A/C/I.
- Estabelecer teto numérico de bypasses no `docs/harness-limitations.md`: "se ≥5 admin bypasses antes do Bloco 5.3 fechar, projeto pausa para reauditoria. Cada bypass exige incident file + assinatura no incident". Atualizar contador no tracker.
- Selar `docs/decisions/*.md` adicionando ao `MANIFEST.sha256` via `relock-harness.sh`. Procedimento documentado em `CLAUDE.md §9`.
- Mover `docs/reference/roles-from-bmad.md` para `docs/reference/historical/` ou prefixar com `[HISTORICO]` no título — explicitando que não é vigente.

---

## H. Tradução técnico-para-produto

**Status:** **PARCIAL — arquitetura prevista, conteúdo vazio.**

**Evidências:**

- R12 (`docs/constitution.md §4`) e Bloco 4 do plano (`/explain-slice` + `check-r12-vocabulary.sh`) estabelecem **mecânica** de tradução. Correto.
- **Mas:** R12 está descrito como vocabulário **proibido** (lista de termos como `class`, `function`, `endpoint`, `migration`, `commit`, `merge`). Não há vocabulário **canônico positivo** equivalente. Qual é o termo de produto que substitui "endpoint"? "Função da página"? "Tela"? "Ponto de acesso"? Sem dicionário positivo, o `explain-slice` vai inventar tradução por slice.
- Não há **exemplo executado** de `pm-report.md` em parte alguma do repo. O PM nunca viu como um relatório real é. No primeiro slice, vai descobrir que a tradução não comunica e vai ser tarde demais.
- O `meta-audit-2026-04-10.md > consenso #8` declara que R12 é "esqueleto vazio". Esse achado foi reconhecido, mas o **conteúdo** do esqueleto não foi preenchido — só o gate técnico foi planejado.
- Não há `docs/product/glossary-pm.md` (que seria o oposto de `glossary-domain.md` — vocabulário canônico positivo para uso com o humano PM).

**Recomendação:**

- Criar `docs/product/glossary-pm.md` com pares (técnico → produto), por exemplo:
  - `endpoint` → "ponto de acesso da API"
  - `migration` → "atualização da estrutura do banco"
  - `commit atômico` → "registro único de alteração"
  - `worktree descartável` → "pasta de trabalho temporária"
- Criar 2 exemplos completos (mockados) de `specs/000-example/pm-report.md`: um aprovação, uma rejeição. PM revisa, dá feedback, vira corpus de calibração para `explain-slice.sh`.
- Adicionar gate de "exemplo positivo": `check-r12-vocabulary.sh` deve aceitar termos do `glossary-pm.md` e rejeitar termos da blocklist — não apenas rejeitar termos técnicos.

---

## I. Governança financeira e sustentabilidade

**Status:** **AUSENTE.**

**Evidências:**

- `find docs -iname '*finance*' -o -iname '*budget*' -o -iname '*cost*' -o -iname '*pricing*'` retorna **vazio**.
- Sem `docs/finance/`, sem `budget.md`, sem teto de gasto, sem projeção de runway, sem unit economics, sem modelo de pricing.
- **Custo de tokens dos agentes** não está orçado em nível agregado. R8 (constitution §4) menciona que cada `.claude/agents/*.md` declara `max_tokens_per_invocation`, mas não há teto AGREGADO mensal. Em sessões longas (a deste meta-audit já consumiu provavelmente >100k tokens), isso escala linearmente com slice.
- **Custo de consultoria externa** está implícito nos RFPs ("a definir na negociação", "freelance, faixa de referência inicial: a definir") — mas não há ordem de magnitude, não há orçamento aprovado, não há teto. PM pode receber proposta de R$ 30k e não saber se está caro ou barato porque não há linha de base.
- **Custo de infra** não tem ADR. CLAUDE.md menciona "VPS Hostinger" no contexto da V1 informalmente, mas não há `docs/infra.md`, não há decisão sobre tier (8GB? 16GB? 32GB?), não há projeção por tenant.
- **Hora/mês do PM**: o V1 queimou. O V2 tem PM operando solo + agentes. Se este projeto tomar 30h/semana do PM, o custo de oportunidade é real e não está reconhecido em lugar nenhum. Risco de burnout antes do Dia 1.
- Sem plano para justificar economicamente o Kalibrium. Quantos labs precisam pagar quanto/mês para o projeto valer? Sem isso, qualquer decisão de stack ("Postgres dedicado vs pooled? Redis ou não? K8s ou docker compose?") é em vão — o eixo financeiro é invisível.

**Recomendação:**

- Criar `docs/finance/` com:
  - `budget.md` — teto mensal explícito por categoria (tokens Anthropic, infra Hostinger, consultoria, terceirização, contas legais, advisor)
  - `unit-economics.md` — quanto custa servir 1 tenant/mês × quanto pretende cobrar
  - `runway.md` — quantos meses de burn cabem no orçamento atual
  - `cost-tracking.md` — método mensal de medição (Anthropic console, fatura Hostinger, bank statement)
- **Adicionar gate**: variável `KALIB_TOKEN_BUDGET_MONTH` obrigatória no `session-start.sh`. Se não houver budget definido, sessão de "trabalho de produto" não inicia (sessão de harness pode, com warning).
- Antes do Bloco 2: **`docs/product/nfr.md` deve incluir restrição econômica numérica**: "stack tem que rodar em VPS de até R$ X/mês para 200 tenants ativos". Sem isso, ADR-0001 vira teatro.

---

## J. Plano de evolução do próprio harness

**Status:** **PARCIAL — bem estruturado para curto prazo, vazio para médio prazo.**

**Evidências:**

- O plano de 7 blocos (`docs/audits/meta-audit-2026-04-10-action-plan.md`) é **muito bem estruturado** para curto prazo (Dia 1). Tracker está vivo (`docs/audits/progress/meta-audit-tracker.md`).
- `docs/guide-backlog.md` (5,9KB) existe mas é esqueleto vazio (a busca retornou apenas a estrutura inicial). Apenas B-009 é referenciado no Bloco 5.4. Não há backlog priorizado de itens pós-Bloco 7.
- **Não há plano para "harness 1.x → 2.0"**: quando se aprende que algo está errado depois do primeiro slice, qual é o processo? `guide-backlog.md` deveria capturar isso, mas precisa de cadência.
- **Não há métrica de "saúde do harness" agregada**. O `guide-auditor` roda mas não emite KPI: "este mês: 0 bypass, 12 verifications, 2 rejected, 1 escalated, 0 R2 violations". Sem isso, não dá para perceber regressão antes de virar incidente.
- **Não há plano de upgrade do Claude Code/modelo**: quando o Claude Code (CLI) ou o modelo (Opus → Opus N+1) atualizar, o que muda nos hooks? Bloco 5.1 menciona `smoke-hooks` no CI, mas isso não cobre upgrade de tooling — só regressão dentro da mesma versão.
- **Não há plano de degradação graciosa**: se a API Anthropic ficar indisponível por 12h, o que o PM faz? Sem playbook = paralisia.

**Recomendação:**

- Promover `docs/guide-backlog.md` a backlog priorizado real (`| id | descrição | evidência | prioridade | status |`). Já está no caminho — só precisa de conteúdo.
- Criar `scripts/health-report.sh` que roda mensal (ou via `/health-report` skill) e gera `docs/audits/health-YYYY-MM.md` com KPIs do harness. Versionar.
- Criar `docs/operations/claude-code-upgrade-policy.md` com smoke test obrigatório antes de aceitar upgrade.
- Criar `docs/operations/anthropic-outage-playbook.md` (mesmo que seja "pause + aguarde + comunique cliente").

---

## K. Outros pontos relevantes

### K1. `docs/reference/ideia-v1.md` é referência fantasma — bloqueante absoluto antes de qualquer contratação externa

Esse arquivo é citado em **4 lugares** como autoridade canônica de produto (RFPs metrologia + fiscal §8, post-mortem "O que preservar", prompt desta auditoria §6). **Não existe.** Quando o consultor de metrologia for contratado e abrir o pacote, vai pedir o arquivo. PM vai improvisar verbalmente. Os 50 casos golden vão ser produzidos com base em conversa, não documento. Esse é exatamente o anti-pattern 6 ("AC narrativo") e 10 ("ideia.md como spec técnica") do `v1-post-mortem.md`. **Bloqueante absoluto antes de assinar contrato com qualquer consultor.**

### K2. Ruleset de main aceita admin bypass — e o próprio meta-audit já usou 3 vezes

`docs/incidents/bloco1-admin-bypass-2026-04-10.md > Auto-referência honesta` declara: "este próprio commit que documenta o bypass também usa o bypass". A solução está prevista em Bloco 5.3 — **mas Bloco 5 só vem depois dos blocos 2, 3, 4.** Risco real: durante esse intervalo, número de bypasses cresce, normalizando o uso. Cada bloco intermediário vai ter "necessidade legítima". Recomendação: congelar admin bypass agora — qualquer commit que precisar dele deve ser rotulado como `hotfix/*` (regra do Bloco 5.4 antecipada manualmente como convenção operacional). Sem código, sem CI — só decisão escrita + incident por bypass.

### K3. PM aprovando spec sem segundo olhar é o ponto único de falha real

Constitution R11 (dual-verifier) e Bloco 4.5 (pausa dura) cobrem o caso "PM aprova rejeição técnica por esgotamento". Mas não há trava sobre **PM aprovando spec sem revisão**. Quem revisa o `spec.md` antes do `architect` partir para o `plan.md`? Apenas o PM. Se o PM cola um requisito mal escrito, todo o pipeline downstream constrói algo errado corretamente. O dual-verifier valida implementação contra spec — não a spec contra realidade. Recomendação: `/spec-review` sub-agent (não dual-verifier — sub-agent independente que recebe spec e bate contra glossário + MVP-SCOPE + RNFs e devolve `spec-review.json` com `verdict: ok/needs-revision`).

### K4. Não há gate "produto pronto para primeiro tenant real"

Bloco 7 valida que o harness está pronto para construir produto. Mas não há gate equivalente para "produto está pronto para receber tenant real" (alpha cliente, pago ou não). Sem isso, o primeiro deploy pode entrar em produção sem RPO/RTO, sem suporte definido, sem contrato — repetindo o erro de "construir e ver no que dá" do V1.

### K5. `docs/TECHNICAL-DECISIONS.md` (929 bytes) é declarado leitura obrigatória, mas é esqueleto vazio

`CLAUDE.md §0` declara: "Antes de qualquer ferramenta ser invocada, ler nesta ordem: 1. CLAUDE.md, 2. constitution.md, 3. **TECHNICAL-DECISIONS.md**". 929 bytes = praticamente vazio. `session-start.sh` valida que existe — não que é útil. Agente novo abre, vê esqueleto, segue. Adicionar gate `wc -l` mínimo no `session-start.sh`.

### K6. Sem inventário de dados pessoais (RoT — LGPD Art. 37)

LGPD exige Registro de Operações de Tratamento mesmo em pré-lançamento, **antes** do código que coleta o primeiro dado. Sem isso, qualquer slice que toque CRUD de cliente está em violação por padrão. Não há `docs/security/rot.md`.

### K7. Risco de fusão entre "trabalho de harness" e "trabalho de produto" no mesmo budget de tokens

Sem separação contábil, o orçamento de tokens será consumido pelo trabalho de governança (auditorias, refatorações de hooks, retrospectivas) e quando sobrar para produto, será insuficiente. Recomendação: dois budgets distintos rastreados em `docs/finance/cost-tracking.md`.

### K8. `docs/reference/roles-from-bmad.md` continua no repo após decisão de cortar BMAD

Risco de confusão entre o que é vigente (R11/R12 do constitution) e o que é histórico. Mover para `docs/reference/historical/` ou anexar disclaimer no topo.

### K9. Hooks selados protegem o harness contra agente, mas não contra usuário humano fora do Claude Code

`scripts/relock-harness.sh` exige TTY interativo (camada 2) — bom contra agente. Mas o PM (humano) pode editar o hook em editor externo, rodar relock manualmente, e mudar o comportamento sem que ninguém perceba. Não há gate de "alteração de hook por humano também precisa de PR + reviewer humano externo". Para PM solo, isso é aceitável — mas precisa ser **decisão registrada**, não omissão.

---

## L. 5 maiores ameaças à entrega do MVP em produção

### Ameaça #1: Arquivo fantasma `ideia-v1.md` quebra o contrato com consultores externos

**Probabilidade:** alta
**Impacto:** alto
**Descrição:** PM contrata consultor de metrologia (decisão 0.2 já aprovada). Consultor abre pacote (RFP §8 lista `docs/reference/ideia-v1.md` como entregável de leitura). Arquivo não existe. PM improvisa verbalmente em call. Consultor produz 50 casos golden contextualizados no laboratório errado. Trabalho rejeitado ou — pior — aceito sem revisão crítica porque PM não tem base técnica para criticar. R$ X mil queimados, prazo perdido, módulo metrologia atrasado. Mesma armadilha vai se repetir com fiscal.
**Por que o plano atual não cobre:** o tracker marca metrologia M1 (RFP) como entregável a iniciar, mas o **pré-requisito do RFP** (o documento citado) nunca foi auditado. Bloco 0 aprovou contratação sem aprovar a base de informação que o consultor precisa.
**Mitigação proposta:** **antes** de enviar RFP a qualquer consultor, criar `docs/product/ideia-v1.md` real (restaurar do backup `C:\PROJETOS\KALIBRIUM SAAS\` mencionado no v1-post-mortem ou redigir do zero) + `docs/product/laboratorio-tipo.md` (descrição do lab modelo). Adicionar gate operacional: trilha-paralela só destrava após esses 2 arquivos existirem e serem revisados pelo `reviewer`.

### Ameaça #2: Decisão de stack (Bloco 2) sem RNFs numéricos vira religião

**Probabilidade:** alta
**Impacto:** alto
**Descrição:** Bloco 2 manda rodar `/decide-stack` com ≥2 alternativas. Mas o `/decide-stack` vai escolher entre (Next.js+Postgres, Rails+Postgres, Phoenix+Postgres, Laravel+MySQL) com base em **quê**? Sem `docs/product/nfr.md` numérico (RPS, p95, RAM/CPU budget, custo/mês teto, tenants alvo, latência aceita), o critério de comparação é gut feeling. ADR-0001 sai "escolhi X porque é o que está na moda" — exatamente o anti-pattern 7 do v1-post-mortem ("Stack decidida sem ADR"), só que agora **com** ADR mas vazio de critério.
**Por que o plano atual não cobre:** Bloco 2.1 exige "≥2 alternativas" e `validate-decide-output.sh` (item 6.3) — mas valida formato, não substância. Não há checklist obrigatório de RNFs no input do `/decide-stack`. Não há `docs/product/nfr.md` no plano.
**Mitigação proposta:** Bloco 2 ganha pré-requisito formal: `docs/product/nfr.md` deve existir e ter ≥10 RNFs numéricos. `/decide-stack` rejeita execução se NFR ausente ou se input contiver placeholder ("a definir"). Inserir Bloco 1.5 — Foundation produto entre Bloco 1 e Bloco 2.

### Ameaça #3: Bypass admin vai virar normal antes do Bloco 5 fechar

**Probabilidade:** média-alta
**Impacto:** alto (estrutural — destrói confiabilidade do harness)
**Descrição:** Já são 3 admin bypasses em 1 dia (PR #1, push do Bloco 1, commit do incident do Bloco 1). Bloco 5 (juiz externo + ruleset endurecido) está bloqueado por blocos 2, 3, 4 em sequência. Cada bloco intermediário vai ter "necessidade legítima" de bypass (sem CI ainda, sem auto-reviewer ainda, sem branch protection eficaz). A cada bypass, atrito desce. Quando Bloco 5 fechar, cultura interna já vai estar "bypass é normal quando preciso" — e isso vai vazar para o pós-Dia 1. P9 ("zero bypass") vira ficção.
**Por que o plano atual não cobre:** plano confia que cada bypass tem incident, mas não impõe **teto numérico** nem pausa automática.
**Mitigação proposta:** congelar bypass agora por decisão operacional registrada em `docs/harness-limitations.md`: nenhum novo bypass até Bloco 5.3 fechar — exceto incident classificado P0 + decisão escrita do PM no incident file. Se chegar a 5 bypasses totais antes do Bloco 5.3, **pausar projeto** e abrir reauditoria. Hard limit. Acrescentar item ao tracker.

### Ameaça #4: Vazio total de governança LGPD/segurança do produto bloqueia primeiro alpha

**Probabilidade:** alta
**Impacto:** crítico (legal, não recuperável)
**Descrição:** Plano de 7 blocos termina. Harness está sólido. PM diz "vamos lançar alpha". Primeiro tenant real entra. PM nunca produziu DPIA, threat model, base legal, RoT, contrato de operador, política de retenção, plano de incident response do **produto**. Primeiro vazamento, denúncia ANPD, ou pedido de portabilidade — o projeto precisa **parar produto** para fazer o que deveria existir desde o início. Cliente alpha cancela. Reputação inicial queima. Multa LGPD potencial.
**Por que o plano atual não cobre:** os 7 blocos são 100% sobre harness técnico; a trilha paralela é só metrologia + fiscal. LGPD/REP-P/ICP-Brasil têm um único checkbox ("registrar como fora do MVP") — e o arquivo sequer existe.
**Mitigação proposta:** criar **trilha paralela #2 — "Compliance Produto"** com: threat-model, DPIA, RoT, base legal por finalidade, contrato de operador, política de retenção, RPO/RTO, incident-response-playbook. **Bloquear primeiro deploy de produção até essa trilha entregar.** Não depende de consultor externo — pode ser produzido por agente sob revisão final de DPO horista (custo marginal de algumas horas/mês).

### Ameaça #5: PM como ponto único de falha — sem trava de fadiga estrutural fora do dual-verifier

**Probabilidade:** alta (em horizonte 3–6 meses)
**Impacto:** alto
**Descrição:** Modelo "humano = PM solo, agentes = equipe técnica" é por design. Bloco 4.5 protege contra "PM aprova override técnico por esgotamento". **Mas** não protege contra: "PM aprova spec ruim por esgotamento" (entrada do pipeline), "PM aprova budget por esgotamento" (financeiro), "PM aprova mudança de constituição por esgotamento" (governança), "PM aprova contrato com consultor por esgotamento". Em 3–6 meses, PM exausto + slice complexo + agente convincente = decisão errada permanente. Não há buddy, não há advisor de fora, não há cooldown obrigatório, não há revisão diferida.
**Por que o plano atual não cobre:** R6 escala para humano — mas o humano é o mesmo PM. Não há "humano fora do loop diário" (mentor, advisor, board) para validar decisões críticas. Não há cooldown.
**Mitigação proposta:** instituir **decisão diferida** para classes críticas: qualquer commit que altera `docs/constitution.md`, ADR aceito, ou commitment financeiro >R$ X exige cooldown de 24h **e** segundo canal de registro (email para si mesmo, mensagem em canal externo). Hook `pre-commit-gate.sh` bloqueia commit que altera classes críticas se commit anterior em mesma classe < 24h. Adicionar advisor humano externo (engenheiro/arquiteto) horista 4h/mês para sanity check. Custo: marginal vs queima de projeto.

---

## M. Veredito binário

- **O projeto está pronto para avançar para o "Bloco 2" (decisão de stack) no estado atual?**
  **NÃO — com-condições**. Bloco 1 está completo (e bem feito), mas avançar para Bloco 2 sem fundação de produto é repetir o anti-pattern 7 do V1.

- **Quais são as mudanças bloqueantes (se houver) antes de decidir stack?**
  1. Criar `docs/product/ideia-v1.md` real (restaurar do backup do V1 ou redigir) — referência fantasma é bloqueante absoluto.
  2. Criar `docs/product/mvp-scope.md` com módulos IN/OUT decididos por escrito.
  3. Criar `docs/product/nfr.md` com ≥10 RNFs numéricos (carga, latência, RAM/CPU, custo teto/mês, tenants alvo, RPO/RTO). **Sem isso, ADR-0001 não tem critério de comparação.**
  4. Criar `docs/product/personas.md` (mín. 3) e `docs/product/journeys.md` (1 fluxo crítico ponta a ponta).
  5. Criar `docs/finance/budget.md` com teto explícito de tokens, infra e consultoria — para `/decide-stack` ter restrição econômica numérica.
  6. Criar `docs/compliance/out-of-scope.md` formalizando REP-P/LGPD/ICP-Brasil fora do MVP (ou, se dentro, com plano).
  7. Congelar admin bypass por decisão operacional escrita em `docs/harness-limitations.md` (até Bloco 5.3 fechar).
  8. Criar `README.md` raiz mínimo.
  9. Inserir formalmente um "Bloco 1.5 — Foundation produto" entre Bloco 1 e Bloco 2 no `meta-audit-2026-04-10-action-plan.md` e no tracker.

- **Quais artefatos de fundação de produto deveriam existir antes de qualquer commit que toque código-fonte de produto?**
  1. `docs/product/ideia-v1.md` (visão congelada)
  2. `docs/product/mvp-scope.md` (módulos IN/OUT)
  3. `docs/product/personas.md`
  4. `docs/product/journeys.md` (mín. 1 fluxo crítico)
  5. `docs/product/nfr.md` (RNFs numéricos)
  6. `docs/product/glossary-pm.md` (vocabulário canônico positivo PM-side)
  7. `docs/product/laboratorio-tipo.md` (descrição do lab modelo — pré-requisito do RFP de metrologia)
  8. `docs/security/threat-model.md` (STRIDE)
  9. `docs/security/lgpd-base-legal.md`
  10. `docs/security/dpia.md`
  11. `docs/security/rot.md` (Registro de Operações de Tratamento — LGPD Art. 37)
  12. `docs/security/incident-response-playbook.md`
  13. `docs/security/backup-dr-policy.md` (RPO/RTO numérico)
  14. `docs/security/secrets-policy.md`
  15. `docs/security/dependency-policy.md`
  16. `docs/finance/budget.md`
  17. `docs/finance/unit-economics.md`
  18. `docs/operations/runbooks/*.md` (deploy, rollback, restore, novo-tenant, recuperacao-de-desastre)
  19. `docs/operations/release-process.md`
  20. `docs/operations/oncall.md`
  21. `docs/operations/sla-slo.md`
  22. `docs/governance/raci.md`
  23. `docs/governance/cooldown-policy.md`
  24. `docs/compliance/normative-traceability-policy.md`
  25. `docs/compliance/ia-no-go.md`
  26. `docs/compliance/out-of-scope.md`
  27. `docs/adr/0001-stack.md` (status: accepted, com critério rastreável até `nfr.md`)
  28. ADRs `status: proposed` para multi-tenancy, identity/auth, modelo de dados de calibração, criptografia em repouso, formato de certificado, versionamento de API, observabilidade, fila/jobs, runtime de cálculo metrológico
  29. `README.md` raiz
  30. `docs/TECHNICAL-DECISIONS.md` preenchido (não esqueleto)

---

## N. 10 sugestões acionáveis em ordem de impacto

**[esforço: baixo] 1. Criar `docs/product/ideia-v1.md` ANTES de enviar qualquer RFP**
- **Por quê:** referência citada em 4 documentos não pode ser fantasma. Quebra contrato com consultor externo. Bloqueante absoluto da trilha paralela.
- **Como:** restaurar do `C:\PROJETOS\KALIBRIUM SAAS\` (mencionado em `docs/reference/v1-post-mortem.md > O que preservar`). Se não existir lá, redigir do zero (1–2h, baseado no glossário). Commitar como `feat(product): congela visão V1 do Kalibrium`. Adicionar ao MANIFEST de selos.

**[esforço: médio] 2. Inserir formalmente "Bloco 1.5 — Foundation produto" entre Bloco 1 e Bloco 2**
- **Por quê:** Bloco 2 (`/decide-stack`) sem produto fundacional vira ADR-religião. Os 7 blocos cobrem harness, não produto. Falta um bloco que produza os artefatos, não as decisões abstratas (Bloco 0).
- **Como:** editar `docs/audits/meta-audit-2026-04-10-action-plan.md` e `docs/audits/progress/meta-audit-tracker.md`. Bloco 1.5 entrega: `ideia-v1.md`, `mvp-scope.md`, `personas.md`, `journeys.md`, `nfr.md`, `glossary-pm.md`, `laboratorio-tipo.md`, `finance/budget.md`, `security/threat-model.md` (rascunho), `security/lgpd-base-legal.md` (rascunho). Critério de pronto: cada arquivo ≥800 palavras (gate `wc -w`) e revisado pelo `reviewer` em modo "spec-review". ADR-0001 só destrava após Bloco 1.5.

**[esforço: baixo] 3. `README.md` raiz mínimo**
- **Por quê:** repo público sem README sinaliza imaturidade para consultor, investidor, auditor, alpha cliente. Custo trivial.
- **Como:** 5 seções: o que é o Kalibrium (1 parágrafo), status (alpha/pre-launch), links para `docs/constitution.md` + `docs/audits/` + `docs/compliance/`, como rodar (placeholder até Bloco 2), como contribuir (placeholder até trilha paralela). Selar via Bloco 1 manifest após criação.

**[esforço: baixo] 4. Trava de admin bypass: numérica + congelamento operacional**
- **Por quê:** 3 bypasses em 1 dia + janela longa até Bloco 5.3 = normalização garantida.
- **Como:** editar `docs/harness-limitations.md` adicionando seção "Política operacional 2026-04-10: congelamento de admin bypass". Conteúdo: "Zero novos admin bypass até Bloco 5.3 fechar. Exceções: incident classificado P0 + assinatura no incident file. Teto absoluto: 5 bypasses totais. Se atingido, projeto pausa para reauditoria." Atualizar contador no `bloco1-admin-bypass-2026-04-10.md`.

**[esforço: médio] 5. Trilha paralela #2 — Compliance Produto (LGPD/segurança)**
- **Por quê:** vazio total bloqueia primeiro alpha. ANPD não negocia. Multa LGPD é até 2% do faturamento.
- **Como:** adicionar ao `docs/audits/meta-audit-2026-04-10-action-plan.md` uma "Trilha paralela #2 — Compliance Produto". Entregáveis: `threat-model.md`, `lgpd-base-legal.md`, `dpia.md`, `rot.md`, `contrato-operador-template.md`, `incident-response-playbook.md`, `backup-dr-policy.md`. Conduzida por agentes; revisão final por DPO horista (4–8h, custo marginal). Bloquear primeiro deploy de produção até trilha entregar.

**[esforço: baixo] 6. `docs/product/nfr.md` numérico antes de Bloco 2**
- **Por quê:** sem RNFs numéricos, `/decide-stack` é teatro. Sem orçamento numérico de infra, nenhuma stack é objetivamente melhor.
- **Como:** PM responde 12 perguntas (tenants alvo? calibrações/dia/tenant? p95 alvo? RAM teto na VPS? custo/mês teto? RPO? RTO? retenção de dados? backup frequency? deploy frequency? regiões?). Agente formaliza em `docs/product/nfr.md`. Hook bloqueia `/decide-stack` se NFR não existir ou contiver placeholder.

**[esforço: baixo] 7. `docs/governance/raci.md`**
- **Por quê:** quem aprova ADR? Quem autoriza gasto >X? Sem isso, PM aprova tudo por default e cada decisão é tomada por padrão de "quem tem credencial".
- **Como:** matriz: tipo-de-decisão × ator. Atores: PM, architect, reviewer, verifier, consultor externo, advisor mensal. Tipos: ADR, custo (3 faixas), MCP, mudança de constitution, contrato, pausa de projeto, primeiro deploy. ~1h de redação. Revisar a cada Bloco 7.

**[esforço: médio] 8. `/spec-review` sub-agent independente do dual-verifier**
- **Por quê:** dual-verifier valida implementação contra spec. Não valida spec contra realidade. PM cola spec ruim e cadeia downstream constrói corretamente algo errado.
- **Como:** novo `.claude/agents/spec-reviewer.md`. Recebe `specs/NNN/spec.md`. Bate contra `glossary-domain.md`, `mvp-scope.md`, `nfr.md`, `personas.md`. Devolve `specs/NNN/spec-review.json` com `verdict: ok | needs-revision` + lista de gaps. Hook bloqueia `architect` até `verdict: ok`. Adicionar ao §6 do `CLAUDE.md` como passo 3.5.

**[esforço: baixo] 9. Selar `docs/decisions/*.md` no MANIFEST do Bloco 1**
- **Por quê:** decisões formais do PM não estão protegidas. Em sessão futura, agente pode editar o histórico. Mesmo problema que justificou o Bloco 1.
- **Como:** adicionar `docs/decisions/*.md` à lista de arquivos selados em `scripts/relock-harness.sh`. Rodar relock manualmente seguindo `CLAUDE.md §9` (terminal externo, TTY, digitar `RELOCK`, gerar incident automático).

**[esforço: alto] 10. Cooldown 24h para decisão crítica + advisor externo mensal**
- **Por quê:** PM solo é ponto único de falha estrutural. Pausa dura cobre rejeição técnica, não decisão de governança/spec/finanças.
- **Como:** `docs/governance/cooldown-policy.md` define classes críticas (constitution, ADR aceito, finance/budget, compliance). Hook `pre-commit-gate.sh` bloqueia commit que altera arquivos dessas classes se commit anterior em mesma classe < 24h. Adicionar advisor humano externo (engenheiro/arquiteto sênior) horista 4h/mês para sanity check de decisões críticas. Custo: marginal vs queima de projeto.

---

## O. Comentário livre

A força mais real deste projeto é a honestidade auto-referencial. O incident `bloco1-admin-bypass-2026-04-10.md` admite literalmente: "este próprio commit que documenta o bypass também usa o bypass". Essa sinceridade técnica é rara e é o que dá ao projeto chance real de sobreviver. O conjunto de RFPs (metrologia + fiscal) é trabalho de qualidade profissional, não rascunho. A constitution v1.1.0 com R11/R12 mostra autoconsciência sobre o modelo "humano=PM solo".

Mas a mesma honestidade revela um padrão preocupante: o harness está sendo construído primeiro, e a expectativa parece ser "depois construímos o produto em cima". Esse "depois" não tem nome no plano. Não há "Bloco Foundation produto", não há "Trilha Compliance produto", e o Bloco 7 valida o harness para "Dia 1 de produto" sem definir o que "produto" significa neste projeto. O Bloco 0 capturou 5 decisões abstratas, não 5 artefatos.

O risco que ninguém ainda viu é que o harness ficará tão bem construído que o PM vai começar a Dia 1 e descobrir que **não sabe o que pedir**. Vai construir slice sobre slice de coisas óbvias (CRUD de usuário, login, listagem) e nunca chegar perto do diferencial regulado (cálculo GUM, certificado ISO 17025, NF-e correta) porque as fundações desses módulos não foram desenhadas. Vai gastar 6 meses construindo um WordPress de calibração e queimar o segundo orçamento de tokens da mesma forma que o V1 queimou o primeiro orçamento de horas humanas.

A recomendação central desta auditoria não é "adicione mais hooks". É: **antes do Bloco 2, pause os blocos técnicos por 2–3 sessões, faça um Bloco 1.5 de produto, e só depois decida stack**. Custo: 2–3 sessões. Retorno: ADR-0001 vai ter critério rastreável, consultores externos vão receber pacote real (não fantasma), primeiro alpha vai ser legalmente defensável, e o PM vai saber o que pedir no Dia 1.
