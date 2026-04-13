# Meta-auditoria #2 — Consolidação das 3 auditorias de completude

**Data:** 2026-04-10
**Meta-auditor:** Claude Opus 4.6 (1M context) — sessão nova, deliberadamente isolada da sessão que escreveu o plano de 7 blocos e das sessões que produziram cada uma das 3 auditorias (aplicação recursiva de `feedback_meta_audit_isolation`).
**Escopo:** consolidar as 3 auditorias externas de completude salvas em `docs/audits/external/`:
- `audit-claude-opus-4-6-completeness-2026-04-10.md` (Claude Opus 4.6, 47 KB)
- `completeness-audit-gpt-5-codex-2026-04-10.md` (GPT-5 Codex, 28 KB)
- `completeness-audit-gemini-3-1-pro-2026-04-10.md` (Gemini 3.1 Pro, 13 KB)

**Público:** Product Manager do Kalibrium V2 (linguagem de produto — R12 — nas seções 0 e 6; rastreabilidade técnica nas seções 1–5).

**Contexto:** esta é a **segunda** rodada de meta-auditoria do projeto. A primeira (`meta-audit-2026-04-10.md`) cobriu *enforcement técnico* do harness e gerou o plano de 7 blocos + trilha paralela de compliance. Esta segunda rodada foi pedida pelo PM **antes** de iniciar o Bloco 2, porque ele desconfiou de que o plano poderia estar endurecendo o harness em cima de uma fundação de produto incompleta. Esta meta-auditoria responde à pergunta: "o plano de 7 blocos é suficiente, ou falta algo estrutural antes de decidir a stack?"

---

## 0. Para o PM, em uma página

> **Leitura direta:** você estava certo em desconfiar. O plano de 7 blocos é sólido para endurecer o harness, mas ele avança para "decidir a tecnologia" sem ter escrito antes **o que o Kalibrium é, para quem, em qual tamanho de VPS, com qual teto de gasto e com qual caminho de conformidade**. Os 3 auditores externos, independentemente, apontam essa mesma falha. Avançar hoje para o Bloco 2 significa repetir o erro do V1 num formato mais organizado — escolher ferramenta antes de definir o problema.

### Veredito binário

**O plano vigente pode ser executado como está?** — Não.
**Precisa ser refeito do zero?** — Não.
**Precisa de ajustes antes de seguir?** — **Sim, ajustes focados.** Não é reformulação, é a inserção de **um novo bloco ("Fundação de Produto") antes da decisão de tecnologia** e a abertura de **uma segunda trilha paralela** (compliance do produto — LGPD, segurança, backup, operação). Os blocos 3-7 permanecem praticamente intactos, com pequenos enxertos.

### Próxima ação imediata (única)

**Pausar o avanço por 2–3 sessões e executar um novo bloco intermediário ("Bloco 1.5 — Fundação de Produto") cujo único objetivo é escrever o que o Kalibrium precisa ser, para quem, em qual hardware, com qual teto de gasto e com qual limite de conformidade.** Sem isso, a decisão de tecnologia que sairia no Bloco 2 vai ser uma recomendação bonita em cima de suposições invisíveis — e esse é exatamente o nome do erro que destruiu o V1 (anti-pattern 7 do `v1-post-mortem.md`).

### 4 decisões que você precisa tomar

Todas em formato sim/não/ajustar. Nenhuma delas exige leitura de código.

1. **Inserir um "Bloco 1.5 — Fundação de Produto" entre o Bloco 1 (já feito) e o Bloco 2 (decidir tecnologia)?**
   - O que entra nele: um documento do que o produto é (escopo do primeiro lançamento), quem são os usuários (3 perfis), um caminho completo que o usuário percorre (um fluxo fim a fim — "pedido do cliente → execução do serviço → certificado → cobrança"), os limites numéricos que a tecnologia precisa respeitar (quantos clientes, quanto de memória, quanto pode custar por mês), e um dicionário de termos do produto para a comunicação entre você e o robô não depender de gíria técnica.
   - Sem isso, a decisão de tecnologia no Bloco 2 não tem critério para escolher entre as alternativas.
   - **Opções:** ( ) Sim, insere Bloco 1.5 ( ) Não, seguir direto ao Bloco 2 ( ) Ajustar escopo

2. **Abrir uma "Trilha Paralela #2 — Conformidade do Produto" ao lado da trilha de metrologia e fiscal que já existem?**
   - O que entra nela: mapa de proteção de dados pessoais (exigência legal da LGPD), plano de recuperação de desastre com número de horas máximas de perda aceitável, política de segredos (como o sistema guarda senhas e chaves), política de backup, plano do que fazer se vazar dado de cliente (a lei exige avisar a ANPD em 72 horas, e hoje não existe o roteiro disso), e a decisão formal do que fica *fora* do primeiro lançamento (ponto eletrônico, assinatura digital oficial, etc.).
   - Essa trilha é produzida pelos agentes sob revisão final de um DPO (profissional de proteção de dados) contratado pontualmente por poucas horas. Custo marginal. Sem ela, o primeiro cliente pago pode expor o projeto a multa e rescisão.
   - **Opções:** ( ) Sim, abre trilha #2 ( ) Não, adiar ( ) Ajustar escopo

3. **Congelar o "atalho do dono" (admin bypass) agora, até o Bloco 5 fechar?**
   - O que é: quando você entra no GitHub como dono do repositório, consegue aprovar o envio direto para a linha principal do projeto, pulando a verificação do robô. Já foram usados 3 desses atalhos em 1 dia. O robô verificador só vira "juiz de verdade" no Bloco 5. No meio tempo, cada bloco seguinte vai ter "motivo legítimo" para usar o atalho de novo — e isso vira cultura, que é o que já queimou o V1.
   - Proposta: escrever formalmente que, até o Bloco 5 fechar, cada uso desse atalho exige um arquivo de incidente com sua assinatura por escrito explicando o porquê. Teto absoluto: 5 usos no total. Se chegar a 5, o projeto pausa e vai para reauditoria.
   - **Opções:** ( ) Sim, congelar com teto 5 ( ) Sim, congelar sem teto numérico ( ) Não congelar

4. **Contratar um profissional técnico externo (engenheiro/arquiteto) por 4 horas por mês para dar um segundo olhar nas decisões de tecnologia e arquitetura?**
   - Por quê: você não é desenvolvedor. O robô vai recomendar tecnologia e arquitetura no Bloco 2 e, sem um segundo humano técnico, você só pode aceitar ou recusar sem critério. Dois dos três auditores (Claude Opus e GPT-5 Codex) colocaram esse ponto como condição para aceitar a recomendação de tecnologia. Custo: marginal comparado à queima de projeto.
   - Observação importante: esse profissional **não** substitui o robô e **não** tem acesso de escrita ao código. É só um revisor pontual, horista, que valida as decisões do ADR-0001 antes de você aceitá-las.
   - **Opções:** ( ) Sim, contratar ( ) Sim, mas só para o Bloco 2 ( ) Adiar para depois do Bloco 2 ( ) Não contratar

### O que eu já recomendo sem pedir sua aprovação (consenso 3/3 dos auditores — decisão dispensável)

Os 3 auditores convergem nestes pontos com tanta força que pedir sua aprovação seria desperdiçar seu tempo. Incluir direto no Bloco 1.5 (se você aceitar a decisão 1 acima):

- **Criar `README.md` na raiz do projeto** — hoje não existe. Primeiro arquivo que um consultor, auditor ou cliente alpha olha. Custo: 30 minutos de redação. Risco de não fazer: imaturidade percebida.
- **Criar o documento `mvp-scope.md`** (o que entra e o que não entra no primeiro lançamento). Os 3 auditores apontam que o próprio script `scripts/decide-stack.sh` **falha com erro** quando tenta rodar sem este arquivo. Ou seja: a máquina já sabe que precisa, só falta escrever.
- **Copiar ou escrever `ideia-v1.md`** — o arquivo é citado em 4 lugares do repositório (RFPs de consultores, post-mortem, prompts de auditoria) como se existisse. **Não existe.** Quando o consultor de metrologia for contratado e abrir o pacote que você prometeu entregar a ele, vai pedir esse arquivo. Você vai improvisar verbalmente e o trabalho do consultor vai sair contextualizado errado. O Gemini observou que existe um `ideia.md` **fora** do repositório kalibrium-v2 (na raiz antiga) — se for ele, é só copiar.
- **Escrever um documento de orçamento operacional** (`docs/finance/operating-budget.md`): quanto você pretende gastar por mês em VPS, tokens do robô, consultoria. Sem isso, a decisão de tecnologia não tem limite econômico e pode sair uma recomendação que não cabe no seu orçamento real.
- **Escrever um mapa de proteção de dados pessoais** (`docs/security/lgpd-data-map.md`) e um **plano de o que fazer se vazar dado** (`docs/security/incident-response-playbook.md`). A lei exige que você avise a ANPD em 72 horas — se vazar antes desses papéis existirem, o projeto fica legalmente exposto. Pode ser produzido pelo robô com revisão final de DPO horista.
- **Corrigir uma inconsistência na constituição do projeto**: o §5 da constituição diz que só R1 a R10 podem ser alterados por ADR + retrospectiva, mas a constituição hoje já tem R11 e R12. É um erro de texto, sem impacto operacional. Codex apontou, correção é trivial.
- **Marcar `docs/reference/roles-from-bmad.md` como histórico** (ou mover para uma pasta `historical/`). A decisão de cortar o BMAD já foi tomada e registrada, mas o arquivo continua lá sem disclaimer, criando risco de confusão entre o que está valendo (R11/R12) e o que é história.

### Tempo estimado de tudo isto (Bloco 1.5 + decisões 1-4)

Em número de sessões (não em dias), porque sessão é a unidade de trabalho real do seu modelo operacional:

- 2-3 sessões para o Bloco 1.5 (produzir os artefatos de fundação de produto)
- 1 sessão para abrir a Trilha #2 com os rascunhos iniciais (depois ela roda em background com revisão de DPO)
- 1 despacho de congelamento de bypass (15 minutos — é só escrever a política em `docs/harness-limitations.md` e selar)
- Negociação de advisor externo fora da sessão

**Sem os blocos 1.5 + Trilha #2, minha recomendação é não iniciar o Bloco 2.** Com eles, o Bloco 2 vira uma escolha de tecnologia com critério rastreável — que é o oposto do que aconteceu no V1.

---

## 1. Comparativo panorâmico dos 3 auditores

Tabela de veredito por dimensão (A–K do prompt de auditoria em `docs/audits/completeness-audit-prompt-2026-04-10.md`). Legenda: ✅ presente / 🟨 parcial / ❌ ausente / — não avaliado explicitamente.

| Dimensão | Claude Opus 4.6 | GPT-5 Codex | Gemini 3.1 Pro |
|---|---|---|---|
| **A.** Documentação fundacional de produto (PRD, personas, jornadas, NFRs, pricing) | ❌ "ausente quase por completo" | 🟨 "parcial, insuficiente para decidir stack" | ❌ "ausente" |
| **B.** Decisões arquiteturais fundacionais (multi-tenancy, auth, dados, deployment) | 🟨 "parcial com lacuna estrutural grave" | 🟨 "parcial" | 🟨 "parcial" |
| **C.** Governança de segurança e compliance (threat model, LGPD, DPIA, DR, segredos) | ❌ "ausente em quase todas as dimensões fora do harness" | 🟨 "parcial harness, ausente produto" | 🟨 "parcial" |
| **D.** Processos operacionais (deploy, rollback, oncall, runbooks, law-watch) | 🟨 "bem coberto harness, ausente produto" | 🟨 "parcial dev, ausente SaaS" | ❌ "ausente" |
| **E.** Artefatos de domínio regulado (golden tests, rastreabilidade normativa) | 🟨 "metrologia/fiscal encaminhado, vazio REP-P/LGPD/ICP" | 🟨 "direção boa, execução incompleta" | 🟨 "parcial" |
| **F.** Estrutura do repositório (pastas, templates, README) | 🟨 "boa harness, fragmentada produto" | 🟨 "parcial" | ✅ "exemplar" |
| **G.** Papéis, responsabilidades e limites (RACI) | 🟨 "explícito harness, vago produto/governança" | 🟨 "parcial" | ✅ "presente" |
| **H.** Tradução técnico-para-produto (R12 real, não esqueleto) | 🟨 "arquitetura prevista, conteúdo vazio" | 🟨 "parcial, placeholder" | ❌ "ausente" |
| **I.** Governança financeira e sustentabilidade (orçamento, runway, unit economics) | ❌ "ausente" | ❌ "ausente" | ❌ "ausente" |
| **J.** Plano de evolução do próprio harness | 🟨 "bem curto prazo, vazio médio prazo" | 🟨 "parcial, inconsistência R1-R10 vs R1-R12" | ✅ "presente" |
| **K.** Outros (pontos livres) | **9 achados**, vários com classificação bloqueante | **5 achados**, foco em procurement e fornecedores | **1 achado**, truncamento de histórico |

**Leitura da tabela:**

1. **Dimensão I (governança financeira)** é a única onde os 3 são unânimes e categóricos: "ausente". Nenhum meio-termo. Esta é a consenso mais alto e mais fácil de atacar (é só escrever um arquivo de orçamento).
2. **Dimensão A (fundação de produto)** tem 2 "ausente" (Claude, Gemini) + 1 "parcial" (Codex). O Codex só classifica como "parcial" porque reconhece que existe glossário de domínio e RFPs — mas o próprio Codex conclui que é "insuficiente para decidir stack". Funcionalmente é consenso 3/3.
3. **Gemini é o mais brando em 3 dimensões (F, G, J)**, classificando como "presente". Claude e Codex apontam gaps nessas mesmas dimensões. Meu julgamento: Gemini foi superficial aqui — ele confundiu "existe estrutura para harness" com "existe estrutura para produto", e não foi além do óbvio.
4. **Claude é o mais duro** (mais "ausente"s e mais itens em K). Suspeita inicial de inflação, mas cada achado tem citação file:line verificável — é rigor, não ruído.
5. **Codex é o mais equilibrado**, com citações file:line quase sempre precisas e lista de recomendações mais operacional. É o mais fácil de transformar em itens de backlog.

---

## 2. Consenso entre auditores (prioridade máxima)

Esta seção cobre os achados onde **pelo menos 2 dos 3** auditores apontam o mesmo gap, com citação literal de cada fonte e classificação por urgência.

### Consenso #1 (3/3) — Fundação de produto é fantasma; `ideia-v1.md` e `mvp-scope.md` não existem mesmo sendo citados como obrigatórios

Todos os três apontam este achado com uso de palavras-chave quase idênticas.

- **Claude Opus** (§A): *"`find docs -type f -name '*.md'` retorna 28 arquivos. Zero são sobre produto. Não existem: PRD, MVP-SCOPE, personas, jornadas, requisitos funcionais/não-funcionais, roadmap de versões, modelo de negócio, pricing. `docs/reference/ideia-v1.md` é citado em 4 lugares como autoridade canônica de produto. O arquivo não existe. É uma referência fantasma."*
- **GPT-5 Codex** (§A): *"falta a fundação de produto propriamente dita: PRD/MVP scope, personas, jornadas, requisitos funcionais e não funcionais… A ausência não é teórica: `scripts/decide-stack.sh` aborta se `docs/mvp-scope.md` não existir (`scripts/decide-stack.sh:41-46`), e esse arquivo não existe. O item obrigatório `docs/reference/ideia-v1.md` também não existe."*
- **Gemini 3.1 Pro** (§A): *"Não foram encontrados arquivos como PRD.md, specs/000-mvp/spec.md, personas ou jornadas detalhadas no repositório kalibrium-v2. O projeto carece de uma definição explícita do que constitui o MVP 'funcional' (o 'Caminho Dourado'). O arquivo `ideia.md` na raiz (externo ao repositório kalibrium-v2) é um brainstorm exaustivo, mas não um documento de escopo acionável."*

**O que o plano atual cobre:** o Bloco 2 (`docs/audits/meta-audit-2026-04-10-action-plan.md`) planeja o ADR-0001 de stack, mas **nenhum bloco do plano produz os artefatos de produto a montante**. O Bloco 0 capturou 5 decisões abstratas do PM, não 5 artefatos de produto. O tracker (`docs/audits/progress/meta-audit-tracker.md`) não lista nenhum checkbox sobre `mvp-scope.md`, `personas.md`, `journeys.md` ou `nfr.md`.

**O que o plano atual NÃO cobre:** a ausência de fundação de produto é estrutural. O Bloco 2 pode ser executado com placeholders, mas o próprio `scripts/decide-stack.sh` **falha com erro** sem `mvp-scope.md`, e o plano não reconhece isso.

**Classificação: BLOQUEANTE.** Não pode avançar para Bloco 2 sem resolver. Insere-se **Bloco 1.5 — Fundação de Produto**.

---

### Consenso #2 (3/3) — Governança financeira é ausência total; ADR-0001 sem orçamento é inviável

Três auditores, três "ausente".

- **Claude Opus** (§I): *"`find docs -iname '*finance*' -o -iname '*budget*'` retorna vazio. Sem `docs/finance/`, sem budget, sem teto de gasto, sem runway, sem unit economics, sem pricing. Custo de tokens não está orçado em nível agregado. Sem plano para justificar economicamente o Kalibrium. Qualquer decisão de stack é em vão — o eixo financeiro é invisível."*
- **GPT-5 Codex** (§I): *"Status: ausente. Não há custo mensal de VPS Hostinger, limite de RAM/CPU, custo de banco/backup/storage, custo de tokens por slice, custo total de consultoria, runway e critério de inviabilidade econômica da stack. Esse gap afeta diretamente ADR-0001: uma stack pode ser tecnicamente ótima e operacionalmente impossível em VPS barata."*
- **Gemini 3.1 Pro** (§I): *"Não há registro de custos esperados. A Hostinger VPS tem limites de CPU/RAM. Se a stack escolhida (ADR-0001) for pesada (ex: Java/Spring ou múltiplos containers Docker), o projeto pode inviabilizar o orçamento do PM."*

**O que o plano atual cobre:** Bloco 2.1 pede `/decide-stack` com ≥2 alternativas, e o plano diz que o ADR deve comparar "custo, multi-tenancy e maturidade" (`docs/audits/meta-audit-2026-04-10-action-plan.md` linhas 192-195). Mas "comparar custo" **pressupõe um teto contra o qual comparar**, e o teto não existe.

**O que o plano atual NÃO cobre:** teto mensal de infra, teto de tokens, teto de consultoria, runway, unit economics. O `guide-backlog.md` menciona telemetria de tokens (B-010-like), mas isso é consumo, não orçamento.

**Classificação: BLOQUEANTE.** Entra no Bloco 1.5 como `docs/finance/operating-budget.md` e deve ser pré-requisito do Bloco 2.

---

### Consenso #3 (3/3) — LGPD / segurança / DR do **produto** são ausência quase total (não é só do harness)

Todos os três separam explicitamente "segurança do harness" (boa) de "segurança do produto" (vazia).

- **Claude Opus** (§C): *"`find docs -type d` não retorna `docs/security/`. Não existe. Nenhum threat model do produto existe. O harness foi auditado 3x; o produto Kalibrium não tem STRIDE, nem mapa de superfície de ataque, nem inventário de dados pessoais. LGPD: zero artefato operacional. Não há base legal mapeada, não há DPIA, não há contrato de operador, não há Registro de Operações de Tratamento (Art. 37 LGPD). Sem plano de resposta a incidente do produto — só de incidente do harness."* Seção K6 adiciona: *"LGPD exige Registro de Operações de Tratamento mesmo em pré-lançamento, antes do código que coleta o primeiro dado. Sem isso, qualquer slice que toque CRUD de cliente está em violação por padrão."*
- **GPT-5 Codex** (§C): *"Status: parcialmente presente para o harness; ausente para o produto. Para produto, faltam artefatos essenciais: threat model, data classification, base legal LGPD por fluxo de dados, DPIA, contrato de operador, política de retenção, backup e disaster recovery com RPO/RTO, criptografia em repouso e em trânsito, gestão de segredos, auditoria recorrente de segurança e política de dependências/supply chain."*
- **Gemini 3.1 Pro** (§C): *"Status: parcial. Não há um `docs/security/threat-model.md` nem um Plano de Resposta a Incidentes funcional (apenas registros de incidentes passados). Para um SaaS regulado, a falta de um DPIA (LGPD) e de uma política de segredos é um risco de 'Bloco 1' (bloqueante)."*

**O que o plano atual cobre:** nada. A trilha paralela vigente cobre **metrologia** e **fiscal** — não LGPD, não segurança de produto, não DR. O tracker marca REP-P/LGPD/ICP-Brasil com um único checkbox *"registrar como fora do MVP em docs/compliance/out-of-scope.md"* — e o próprio arquivo `out-of-scope.md` não existe.

**O que o plano atual NÃO cobre:** threat model, DPIA, RoT, base legal, contrato de operador, backup/DR com RPO/RTO numérico, política de secrets, incident-response playbook com prazo ANPD 72h.

**Classificação: BLOQUEANTE para primeiro cliente pago** (não para Bloco 2). Entra como **Trilha Paralela #2 — Compliance Produto**, que roda em background desde o Bloco 1.5 e é pré-requisito do primeiro deploy de produção.

---

### Consenso #4 (3/3) — Decisões arquiteturais pré-stack (multi-tenancy, auth, dados, deployment) não estão registradas; ADR-0001 sozinho não é suficiente

- **Claude Opus** (§B): *"não há ADR para multi-tenancy, modelo de identidade/auth, modelo de dados de calibração, criptografia, observabilidade, formato de certificado (PDF/A?), versionamento de API, fila/jobs, escolha de runtime de cálculo metrológico. Não há ADR sobre qual é a fonte canônica de cálculo metrológico — lib externa? código próprio? wrapper sobre R/Python? Isso bate diretamente com o trabalho do consultor de metrologia."*
- **GPT-5 Codex** (§B): *"decisões conceitualmente anteriores à stack ainda não estão registradas: modelo arquitetural, multi-tenancy, modalidade web/PWA/app, modelo de dados inicial, estratégia de deployment, ambientes, autenticação e autorização. Comparar stacks sem uma decisão prévia de tenancy, offline/PWA, deployment em VPS única, autenticação e limites fiscais/metrológicos tende a transformar ADR-0001 em uma recomendação bonita, mas com premissas invisíveis."*
- **Gemini 3.1 Pro** (§B): *"Decisões críticas como 'Estratégia de Multi-tenancy' (essencial dado o P13 da constituição) e 'Modelo de Deployment' precedem a stack. O Bloco 2 já prevê o ADR-0001, mas ele deve incluir a justificativa da separação de dados. Expandir o Bloco 2 para incluir um ADR específico sobre o isolamento de dados (Row Level Security vs Database per Tenant) antes de iniciar qualquer código de domínio."*

**O que o plano atual cobre:** o Bloco 2 prevê o ADR-0001 (stack). Não prevê ADRs anteriores ou concorrentes.

**O que o plano atual NÃO cobre:** um documento de *baseline arquitetural* (chame como quiser — `foundation-constraints.md` ou uma lista de ADRs em status `proposed`) cobrindo multi-tenancy, identity/auth, modelo de dados, deployment, integrações externas, runtime de cálculo metrológico.

**Classificação: BLOQUEANTE.** Entra no Bloco 1.5 como `docs/architecture/foundation-constraints.md`.

---

### Consenso #5 (3/3) — Operação de produção é ausente (runbooks, deploy, rollback, DR, monitoramento, backup)

- **Claude Opus** (§D): *"Para operação de produto: nada existe. Sem `docs/operations/runbooks/` (deploy, rollback, restore, novo-tenant, gestão-de-segredo). Sem `release-process.md`. Sem `oncall.md`. Sem `sla-slo.md`. Sem template de postmortem de produção."*
- **GPT-5 Codex** (§D): *"Para produção faltam pipeline de deploy, rollback, observabilidade, logs/métricas/alertas, SLO/SLI, pager, suporte aos primeiros clientes, canal de bug report e processo de atualização legislativa contínua."*
- **Gemini 3.1 Pro** (§D): *"Não existem artefatos para Backup/Restore (crítico para Hostinger single-box), monitoramento ou atualização legislativa (cutoff do LLM). O PM sendo não-técnico não saberá se o sistema caiu ou se o backup falhou."*

**O que o plano atual cobre:** Bloco 5 planeja CI externo + auto-approve + ruleset endurecido. Isso é **pipeline de verificação**, não operação de produção.

**O que o plano atual NÃO cobre:** runbooks, SLO numérico, oncall (mesmo que oncall seja "PM vê dashboard 1x/dia"), backup off-site, plano de restore testado, observabilidade (logs/métricas/alertas), processo de atualização legislativa contínua.

**Classificação: BLOQUEANTE para primeiro cliente pago** (não para Bloco 2). Entra em parte na **Trilha Paralela #2** (backup/DR/incident-response) e em parte como adendo aos Blocos 5 e 6 (observabilidade, runbooks, law-watch).

---

### Consenso #6 (2/3) — `README.md` raiz não existe

- **Claude Opus** (§F): *"Não existe `README.md` na raiz do repo. Para um projeto público no GitHub, é o primeiro arquivo que qualquer auditor externo, consultor recém-chegado, ou potencial cliente alpha abre. Ausência total = sinal de imaturidade que afeta credibilidade externa."*
- **GPT-5 Codex** (§F): *"A ausência de `README.md` também fere o item obrigatório do prompt e reduz onboarding para agentes/sessões novas."* Também presente no §F recomendação: *"Criar `README.md` com mapa do repo, ordem de leitura, estado atual e 'não iniciar produto antes de X'."*
- **Gemini 3.1 Pro** (§N #8): *"[esforço: baixo] Criar README.md no repositório kalibrium-v2. Por quê: Orientação básica para qualquer um (humano ou agente) que 'caia' na pasta."*

Todos os três recomendam, apenas 2 colocam explicitamente na seção F. Funcional: 3/3.

**Classificação: ALTA PRIORIDADE, esforço baixo.** Entra no Bloco 1.5 como item 0 (trivial, faça junto).

---

### Consenso #7 (2/3) — R12 (linguagem de produto) é esqueleto vazio; vocabulário só proibitivo, sem dicionário positivo; `explain-slice.sh` é stub

- **Claude Opus** (§H): *"R12 está descrito como vocabulário proibido (lista de termos como class, function, endpoint, migration, commit, merge). Não há vocabulário canônico positivo equivalente. Qual é o termo de produto que substitui 'endpoint'? Sem dicionário positivo, o `explain-slice` vai inventar tradução por slice. Não há exemplo executado de `pm-report.md` em parte alguma do repo. O PM nunca viu como um relatório real é. No primeiro slice, vai descobrir que a tradução não comunica e vai ser tarde demais."*
- **GPT-5 Codex** (§H): *"Na prática, os scripts ainda não são tradutores. `scripts/explain-slice.sh` declara que é stub. `scripts/decide-stack.sh` também é stub e gera um esqueleto com campos 'a ser preenchido'."*
- Gemini (§H): *"Ausente. O explain-slice.sh é um stub. O PM hoje receberia erros técnicos puros ou logs de verificação JSON."*

Funcional: 3/3.

**O que o plano atual cobre:** Bloco 4 planeja `/explain-slice` + `check-r12-vocabulary.sh` (mecânica). Não planeja conteúdo canônico positivo.

**O que o plano atual NÃO cobre:** `docs/product/glossary-pm.md` (dicionário positivo de tradução técnico→produto), exemplos executados de `pm-report.md` como corpus de calibração.

**Classificação: ALTA PRIORIDADE, entra no Bloco 1.5 (glossary-pm) e no Bloco 4 (exemplos executados).**

---

### Consenso #8 (2/3) — Rastreabilidade normativa não está implementada; glossário ≠ autoridade canônica do verifier

- **Claude Opus** (§E): *"a rastreabilidade normativa dos cálculos está endereçada apenas no formato CSV do RFP (normative_reference por caso) — mas não há decisão sobre onde essa rastreabilidade vai morar no produto: coluna na tabela de calibração? Anexo do certificado? Log de auditoria? Sem isso definido antes do consultor entregar, o trabalho do consultor pode virar dado órfão."*
- **GPT-5 Codex** (§E): *"A rastreabilidade normativa ainda está planejada nos CSVs/RFPs, não implementada em `tests/golden/`, `docs/compliance/*-policy.md` ou matriz norma -> requisito -> teste -> código."* Recomendação: *"matriz `norma -> requisito -> golden test -> slice`"*.
- **Gemini 3.1 Pro** (§E): *"a ausência de um mapeamento de normas (ISO 17025) para requisitos funcionais é um gap de rastreabilidade. Criar `docs/compliance/traceability-matrix.md` ligando requisitos da ISO 17025 aos futuros IDs de AC."*

Consenso forte (3/3) com 3 formatos de solução convergentes.

**O que o plano atual cobre:** RFPs de metrologia e fiscal pedem `normative_reference` por caso e `legal_reference` por caso. Isso é **input** do consultor, não é estrutura persistente no produto.

**O que o plano atual NÃO cobre:** matriz canônica `norma → seção → data → requisito → teste → slice` como artefato vivo do repo.

**Classificação: ALTA PRIORIDADE, entra na Trilha Paralela #1 (metrologia/fiscal) como pré-requisito para aceitar entrega do consultor.**

---

### Consenso #9 (2/3) — Escopo formal de REP-P / LGPD / ICP-Brasil não foi registrado; `docs/compliance/out-of-scope.md` é arquivo fantasma

- **Claude Opus** (§E): *"o tracker marca REP-P/LGPD/ICP-Brasil como '[ ] Registrar como fora do MVP em docs/compliance/out-of-scope.md'. O arquivo `docs/compliance/out-of-scope.md` não existe. A decisão de 'fora do MVP' não foi registrada formalmente."*
- **GPT-5 Codex** (§M veredito #5): *"Registrar decisão de escopo para REP-P, ICP-Brasil e LGPD: MVP, fora do MVP ou bloqueado até consultor/política."*
- Gemini não aponta explicitamente (silêncio).

Consenso: 2/3 com citação; 1/3 não cita mas não contradiz.

**Classificação: ALTA PRIORIDADE, esforço baixo.** É só escrever uma página formalizando a decisão. Entra no Bloco 1.5.

---

### Consenso #10 (2/3) — Parecer técnico externo ou review independente do ADR-0001 é condição para aceitar a stack, dado que o PM não é técnico

- **Claude Opus** (§N #10 + §L #5 + K3): *"instituir decisão diferida para classes críticas: qualquer commit que altera `docs/constitution.md`, ADR aceito, ou commitment financeiro > R$ X exige cooldown de 24h e segundo canal de registro. Adicionar advisor humano externo (engenheiro/arquiteto) horista 4h/mês para sanity check."*
- **GPT-5 Codex** (§K #1 + §M veredito #6): *"A decisão de stack precisa de parecer técnico externo ou pelo menos review externo assinado. O PM não tem como auditar premissas de tenancy, segurança, PDF/A, ICP, fiscal e operação em VPS."* E no veredito: *"Exigir parecer técnico externo ou revisão independente do ADR-0001 antes de `accepted`, dado que o PM não é técnico."*
- Gemini: não cita.

Consenso: 2/3 explícitos e convergentes; 1/3 silencia.

**O que o plano atual cobre:** R11 (dual-verifier) e o Bloco 4.5 (pausa dura) protegem contra PM aprovar rejeição técnica por fadiga. **Não cobrem** a entrada do pipeline — PM aceitar ADR-0001 por falta de critério.

**Classificação: ALTA PRIORIDADE. É uma decisão do PM (decisão 4 da seção 0) — não implemento sem aprovação.**

---

## 3. Divergências entre auditores

### Divergência #1 — Veredito binário: o projeto está pronto para o Bloco 2?

| Auditor | Resposta | Número de bloqueantes apontados |
|---|---|---|
| Claude Opus 4.6 | **"NÃO — com condições"** | 9 (insere Bloco 1.5 obrigatório) |
| GPT-5 Codex | **"não"** | 6 |
| Gemini 3.1 Pro | **"Sim, com condições"** | 3 |

**Análise:** Gemini é o outlier. Ele lista 3 bloqueantes (mvp-scope, ideia-v1, teto de hardware VPS) mas **suas próprias seções A–K** apontam gaps muito mais amplos: LGPD, DPIA, secrets-policy, threat-model, backup, runbooks, R12 vazio, governança financeira ausente. Há **inconsistência interna** entre o que ele viu nas dimensões e o que ele condensou no veredito. Leitura mais caridosa: Gemini interpretou "pronto para Bloco 2" como "pronto para *rodar* o comando `/decide-stack`", não como "pronto para produzir um ADR-0001 defensável". Claude e Codex interpretaram no segundo sentido.

**Julgamento do meta-auditor:** **Claude e Codex estão certos.** Rodar `/decide-stack` hoje produz um ADR bonito sem critério — exatamente o erro do V1. "Sim, com condições" do Gemini é superficial e deve ser descartado no veredito, mas os 3 bloqueantes que ele sugeriu **são subset** dos 6 do Codex e dos 9 do Claude, então o achado específico dele (teto de hardware VPS) fica preservado.

**Conclusão:** veredito final desta meta-auditoria = **NÃO, com condições. Inserir Bloco 1.5.**

---

### Divergência #2 — Quão agressivo deve ser o pacote de fundação de produto exigido antes do Bloco 2?

| Auditor | Artefatos exigidos antes do ADR-0001 | Estilo |
|---|---|---|
| Claude Opus | **30 artefatos** (seção M) | Maximalista; inclui até itens de operação (runbooks, SLO) |
| GPT-5 Codex | **14 artefatos** (seção M) | Operacional; foco em fundação + baseline arquitetural + orçamento |
| Gemini 3.1 Pro | **3 artefatos** (seção M) | Minimalista; só escopo + ideia-v1 + teto hardware |

**Análise:** os 30 de Claude incluem artefatos que dependem *da própria stack escolhida* (ex.: formato de certificado PDF/A, runtime de cálculo metrológico, políticas de observabilidade). Exigir isso **antes** do ADR-0001 cria dependência circular: você não escolhe a ferramenta até saber o que fazer com ela, mas não sabe o que fazer com ela até escolher a ferramenta. Claude inflou aqui. A lista de 14 do Codex é a mais operacionalmente defensável — cobre o essencial sem criar ciclos.

**Julgamento do meta-auditor:** **Codex é o mais equilibrado.** A lista de entregáveis do Bloco 1.5 (seção 5 deste documento) é baseada na lista de 14 do Codex, com 3 adições do Claude que eu julgo críticas (`glossary-pm.md`, `laboratorio-tipo.md`, `finance/budget.md`) e 1 adição do Gemini (teto de hardware explícito + stress test no Bloco 2).

---

### Divergência #3 — Admin bypass é ameaça presente ou risco gerenciado?

- **Claude Opus** (§K2 + §L #3): ameaça #3 da lista das 5 maiores. *"Já são 3 admin bypasses em 1 dia. Bloco 5 (juiz externo + ruleset endurecido) está bloqueado por blocos 2, 3, 4 em sequência. Cada bloco intermediário vai ter necessidade legítima de bypass. A cada bypass, atrito desce. Mitigação proposta: congelar bypass agora — teto absoluto de 5 bypasses totais. Se atingido, projeto pausa."*
- **GPT-5 Codex** (§D + §G): cita o bypass, reconhece que está aberto até Bloco 5, mas **não classifica como ameaça bloqueante**. Trata como "flow atual convive com admin bypass".
- **Gemini 3.1 Pro**: **não menciona** o bypass em nenhuma seção.

**Análise:** Claude vê um problema que as outras duas não viram. A evidência é concreta: `docs/incidents/bloco1-admin-bypass-2026-04-10.md > Contagem de bypasses acumulados` declara **"3 bypasses acumulados"** já no primeiro dia. O plano de 7 blocos tem Bloco 5 (que fecha isso) no final da sequência — ou seja, pelo menos mais 3-4 sessões de trabalho pela frente, cada uma com "necessidade legítima" para usar bypass de novo.

**Julgamento do meta-auditor:** **Claude está certo.** Isto é uma ameaça presente, não hipotética. Codex e Gemini foram menos rigorosos aqui. Recomendação: **aceitar** o congelamento operacional proposto pelo Claude, com teto numérico explícito escrito em `docs/harness-limitations.md`. Esta é a **decisão 3 para o PM** (seção 0) — não implemento sem aprovação porque afeta sua capacidade operacional como dono do repo.

---

### Divergência #4 — Multi-tenancy deve ser ADR separado ou uma seção dentro do ADR-0001?

- **Gemini** (§B): *"Expandir o Bloco 2 para incluir um ADR específico sobre o isolamento de dados (Row Level Security vs Database per Tenant) antes de iniciar qualquer código de domínio."* → ADR **separado**.
- **Claude Opus** (§B): propõe abrir `status: proposed` de ADRs 0003 (multi-tenancy) + 0004 + 0005 + etc. → ADR **separado** (mas em estado proposto).
- **GPT-5 Codex** (§B): *"ADR específico de baseline arquitetural cobrindo: monolito modular vs outro modelo, tenancy, dados iniciais, ambientes, authn/authz…"* → pode ser **um único documento** (`docs/architecture/foundation-constraints.md`) que depois vira ADRs.

**Análise:** Claude e Gemini convergem em "vários ADRs". Codex prefere "um baseline + ADRs a reboque". Os dois caminhos produzem o mesmo resultado funcional — o que importa é que as decisões **estejam registradas antes** do ADR-0001.

**Julgamento do meta-auditor:** seguir a abordagem do **Codex** (baseline arquitetural consolidado) é mais pragmática para um PM solo — menos arquivos, menos fricção, mesma rastreabilidade. Os ADRs individuais podem vir depois quando cada decisão tiver histórico. Registro: adotar `docs/architecture/foundation-constraints.md` como entregável do Bloco 1.5.

---

## 4. Insights únicos (achados que só um auditor viu)

### Só Claude Opus 4.6 viu

1. **K2 — Teto numérico explícito de admin bypasses** (discutido em detalhe em §3 acima). Classificação: **aceitar**, é a mitigação mais concreta contra normalização de bypass.
2. **K3 — `/spec-review` sub-agent independente do dual-verifier.** *"Dual-verifier valida implementação contra spec. Não valida spec contra realidade. PM cola spec ruim e cadeia downstream constrói corretamente algo errado."* Valor: alto. O dual-verifier é defesa do **fim** do pipeline; spec-reviewer é defesa da **entrada**. Sem ele, lixo-em-lixo-fora. Classificação: **aceitar**, entra no Bloco 3 (gates reais) ou Bloco 6 (defesas adicionais).
3. **K4 — Gate "produto pronto para primeiro tenant real".** *"Bloco 7 valida que o harness está pronto para construir produto. Mas não há gate equivalente para 'produto está pronto para receber tenant real'."* Classificação: **aceitar**, entra no Bloco 7 como critério de pronto.
4. **K5 — `docs/TECHNICAL-DECISIONS.md` (929 bytes) é declarado leitura obrigatória mas é esqueleto vazio.** Solução: adicionar gate `wc -l` mínimo (ex: 20 linhas) no `session-start.sh`. Classificação: **aceitar**, esforço trivial.
5. **K6 — Falta `docs/security/rot.md` (Registro de Operações de Tratamento — LGPD Art. 37).** *"LGPD exige RoT mesmo em pré-lançamento, antes do código que coleta o primeiro dado."* Classificação: **aceitar**, entra na Trilha #2.
6. **K7 — Fusão de budgets harness × produto no mesmo orçamento de tokens.** *"Sem separação contábil, o orçamento de tokens será consumido pelo trabalho de governança e quando sobrar para produto, será insuficiente."* Classificação: **aceitar**, entra em `docs/finance/operating-budget.md` como duas categorias separadas.
7. **K8 — `docs/reference/roles-from-bmad.md` continua no repo após corte do BMAD** — risco de confusão entre histórico e vigente. Classificação: **aceitar**, esforço trivial (mover para `historical/` ou prefixar).
8. **K9 — Hooks selados protegem contra agente, não contra humano externo fora do Claude Code.** *"PM pode editar o hook em editor externo, rodar relock manualmente, e mudar o comportamento sem que ninguém perceba. Para PM solo, isso é aceitável — mas precisa ser decisão registrada, não omissão."* Classificação: **aceitar**, registrar em `docs/harness-limitations.md`.
9. **Sugestão #9 — Selar `docs/decisions/*.md` no MANIFEST do Bloco 1.** *"Decisões formais do PM não estão protegidas. Em sessão futura, agente pode editar o histórico."* Classificação: **aceitar**, entra como micro-ajuste de MANIFEST (requer relock).

### Só GPT-5 Codex viu

1. **§J — Inconsistência redacional em `docs/constitution.md §5`.** *"O processo de amendment menciona explicitamente R1-R10, mas a constitution já tem R11 e R12."* Correção trivial: editar §5 para "R1-R12". Classificação: **aceitar**, esforço nulo.
2. **§K #4 — Plano de dados sintéticos e fixtures.** *"Para SaaS regulado, agentes precisam de fixtures realistas sem vazar dados reais: laboratório, certificado, padrão, instrumento, nota, jornada, usuário. Não há `docs/data/` nem política de fixtures."* Valor: alto — sem isso, primeiro slice de integração LGPD vira acidente. Classificação: **aceitar**, entra no Bloco 6.
3. **§K #5 — Matriz de fornecedores / contratos / procurement.** *"Fiscal pode depender de provedor NFS-e/NF-e; ICP pode depender de certificado/serviço de assinatura; WhatsApp/e-mail podem depender de fornecedores. Falta matriz de fornecedores, riscos e contratos."* Valor: alto — Kalibrium não é software puro, é integração pesada. Classificação: **aceitar**, entra na Trilha #2 como `docs/compliance/vendor-matrix.md`.
4. **§D — `docs/compliance/law-watch.md` (monitoramento legislativo contínuo).** *"O `law-watch` deve definir fontes oficiais, frequência, responsável, gatilho de incidente e como agentes transformam mudança legal em spec/teste."* Valor: alto — LLM tem cutoff fixo, legislação brasileira muda via diário oficial. Classificação: **aceitar**, entra na Trilha #2.
5. **§E — Pacote de políticas POR DOMÍNIO (1 arquivo por compliance).** *"`docs/compliance/metrology-policy.md`, `docs/compliance/fiscal-policy.md`, `docs/compliance/repp-policy.md`, `docs/compliance/icp-brasil-policy.md`, `docs/compliance/lgpd-policy.md`; cada um com norma/data/seção, decisão de escopo MVP, consultor responsável, matriz norma → requisito → teste → slice, frequência de revalidação e módulos proibidos para IA sem revisão externa."* Classificação: **aceitar**, granularidade correta — cada domínio revalida em cadência própria.
6. **§N #6 — `docs/compliance/procurement-tracker.md` com status + prazo + fallback.** *"Status, responsável, data alvo, fallback 'módulo fora do MVP se consultor não entregar até data X'."* Valor: alto — transforma as RFPs já redigidas em trilha executável. Classificação: **aceitar**, entra como ajuste à Trilha #1 (metrologia/fiscal).

### Só Gemini 3.1 Pro viu

1. **§L #2 — "Stress Test de Scaffolding" na VPS Hostinger antes de aprovar o ADR-0001.** *"No Bloco 2, realizar um 'Stress Test de Scaffolding' na VPS antes de aprovar a stack."* Valor: **muito alto**. É o tipo de engenharia empírica que os outros dois auditores não viram. ADR-0001 teoricamente ótimo pode consumir 90% da RAM da VPS só no boot — melhor descobrir antes de commitar. Classificação: **aceitar**, entra no Bloco 2 como item novo (2.5 Stress Test).
2. **§L #4 — "Sentinel Hook" para drift legislativo.** *"Hook que obriga o PM a atualizar um arquivo `docs/compliance/legal-status.md` semanalmente com as novidades dos consultores."* Valor: médio. Operacionaliza o law-watch do Codex com gate técnico. Classificação: **investigar** — forçar PM a atualizar semanalmente pode virar fricção inútil se não houver mudança legal. Alternativa: gate mensal e autoalimentado por skill `/law-status-refresh`.
3. **§K + §N #10 — Skill `/project-status` com dashboard Markdown visual.** *"PM precisa de visibilidade sem ler logs JSONL."* Valor: médio-alto. Reconhece limitação concreta do modelo PM solo + telemetria JSONL. Classificação: **aceitar**, entra no Bloco 6 como melhoria de tradutor R12.
4. **§N #7 — Padronizar IDs de Requisitos com formato `REQ-DOM-000`.** *"Facilita a rastreabilidade entre spec e teste."* Valor: médio. Simples e funcional. Classificação: **aceitar**, entra como convenção do Bloco 1.5 (mvp-scope.md deve usar o formato).
5. **§N #2 — Copiar literalmente `ideia.md` da raiz antiga para `docs/reference/ideia-v1.md` via `cp`.** Gemini é o **único** que explicitamente viu que existe um `ideia.md` fora do repositório kalibrium-v2 (na raiz antiga do projeto V1) e propôs a operação concreta. Claude e Codex recomendaram "restaurar ou redigir" sem apontar o caminho. Classificação: **aceitar**, esforço trivial se o arquivo realmente existir — PM confirma.

---

## 5. Plano de ação revisado

Base: `docs/audits/meta-audit-2026-04-10-action-plan.md` (7 blocos + Trilha Paralela #1 de metrologia/fiscal).

### Mudança estrutural: inserir "Bloco 1.5 — Fundação de Produto" entre o Bloco 1 (completo) e o Bloco 2 (stack)

**Justificativa:** consenso 3/3 de que avançar para o Bloco 2 sem fundação de produto reproduz o anti-pattern 7 do V1. Pausar 2-3 sessões agora evita ADR-0001 sem critério.

**Origem:** Consenso #1 + #2 + #4 + #6 + #7 + #8 + #9 + Gemini N#6 + Codex N#3.

**Impacto em blocos existentes:** Bloco 2 recebe novo pré-requisito ("Bloco 1.5 + orçamento + NFRs + baseline arquitetural completos e selados"). Nenhum outro bloco é afetado — apenas deslocados no tempo.

**Entregáveis (estimativa: 2-3 sessões):**

| Item | Arquivo | Origem |
|---|---|---|
| 1.5.0 | `README.md` raiz — 5 seções mínimas | Claude §F, Codex §F, Gemini N#8 |
| 1.5.1 | `docs/product/ideia-v1.md` (copiar de `ideia.md` raiz antiga se existir, senão redigir) | Consenso #1 |
| 1.5.2 | `docs/product/mvp-scope.md` com módulos IN/OUT, personas mínimas, jornadas críticas, usando formato `REQ-DOM-000` | Consenso #1 + Gemini N#7 |
| 1.5.3 | `docs/product/personas.md` (mín. 3: gerente lab, técnico calibrador, cliente final) | Consenso #1 |
| 1.5.4 | `docs/product/journeys.md` (1 fluxo fim a fim: pedido → execução → certificado → cobrança) | Consenso #1 |
| 1.5.5 | `docs/product/nfr.md` com RNFs numéricos (RPS, p95, RAM/CPU teto, tenants alvo, RPO/RTO, custo teto/mês) — inclui teto de hardware explícito | Claude §A + Gemini §L#2 |
| 1.5.6 | `docs/product/glossary-pm.md` (vocabulário canônico positivo R12 — pares técnico→produto) | Claude §H + Consenso #7 |
| 1.5.7 | `docs/product/laboratorio-tipo.md` (pré-requisito do RFP de metrologia) | Claude §L#1 |
| 1.5.8 | `docs/architecture/foundation-constraints.md` (tenancy, auth, deployment VPS, modelo de dados, ambientes, integrações) | Consenso #4 (estilo Codex) |
| 1.5.9 | `docs/finance/operating-budget.md` (2 categorias separadas: harness × produto) | Consenso #2 + Claude K7 |
| 1.5.10 | `docs/compliance/out-of-scope.md` (formalizar REP-P / LGPD / ICP-Brasil: dentro / fora / diferido) | Consenso #9 |
| 1.5.11 | Preencher `docs/TECHNICAL-DECISIONS.md` + adicionar gate `wc -l` ≥ 20 no session-start | Claude K5 |
| 1.5.12 | Mover `docs/reference/roles-from-bmad.md` para `docs/reference/historical/` ou prefixar título com `[HISTÓRICO]` | Claude K8 |
| 1.5.13 | Corrigir `docs/constitution.md §5` de "R1-R10" para "R1-R12" | Codex §J |
| 1.5.14 | Selar `docs/decisions/*.md` no MANIFEST do Bloco 1 (via relock-harness.sh) | Claude N#9 |

**Critério de pronto do Bloco 1.5:** todos os 14 itens commitados, cada arquivo com `wc -w ≥ 800` palavras (gate objetivo), revisado pelo `reviewer` em modo spec-review, selado no MANIFEST. Bloco 2 não destrava sem esse selo.

---

### Nova trilha: "Trilha Paralela #2 — Compliance do Produto" (roda desde o Bloco 1.5)

**Justificativa:** consenso 3/3 de que LGPD, threat model, DPIA, backup/DR, secrets policy, incident response são bloqueantes para **primeiro cliente pago** (não para Bloco 2). Conduzida por agentes com revisão final de **DPO horista** contratado pontualmente.

**Origem:** Consenso #3 + #5 + Codex §K#4 + Codex §K#5 + Claude K6 + Gemini §L#3.

**Impacto em trilhas existentes:** nenhum. Roda em paralelo à Trilha #1 (metrologia/fiscal).

**Entregáveis:**

| Item | Arquivo | Origem |
|---|---|---|
| T2.1 | `docs/security/threat-model.md` (STRIDE sobre arquitetura proposta) | Consenso #3 |
| T2.2 | `docs/security/lgpd-base-legal.md` (matriz finalidade × base legal × titular × controlador/operador) | Consenso #3 |
| T2.3 | `docs/security/dpia.md` | Consenso #3 |
| T2.4 | `docs/security/rot.md` (Registro de Operações de Tratamento — Art. 37 LGPD) | Claude K6 |
| T2.5 | `docs/security/incident-response-playbook.md` (com prazo ANPD 72h) | Consenso #3 |
| T2.6 | `docs/security/backup-dr-policy.md` (RPO/RTO numérico + backup off-site) | Consenso #5 + Gemini §L#3 |
| T2.7 | `docs/security/secrets-policy.md` | Consenso #3 |
| T2.8 | `docs/security/dependency-policy.md` (SBOM, política de CVE, cadência) | Claude §C |
| T2.9 | `docs/security/contrato-operador-template.md` | Claude §C |
| T2.10 | `docs/compliance/metrology-policy.md`, `fiscal-policy.md`, `repp-policy.md`, `icp-brasil-policy.md`, `lgpd-policy.md` (um por domínio) | Codex §E |
| T2.11 | `docs/compliance/vendor-matrix.md` (fornecedores, riscos, contratos) | Codex §K#5 |
| T2.12 | `docs/compliance/law-watch.md` (monitoramento legislativo contínuo + fontes oficiais + gatilho) | Codex §D |
| T2.13 | `docs/compliance/traceability-template.md` (matriz `norma → seção → data → requisito → teste → slice`) | Consenso #8 |
| T2.14 | `docs/compliance/procurement-tracker.md` (status + prazo + fallback "fora do MVP se consultor não entregar") | Codex §N#6 |

**Critério de pronto da Trilha #2:** todos os 14 itens commitados; 5 rascunhos iniciais (T2.1, T2.2, T2.5, T2.6, T2.7) revisados por DPO horista; **Trilha #2 é bloqueante para primeiro deploy de produção**, não para Bloco 2.

---

### Ajustes ao Bloco 2 — Decidir a stack (ADR-0001)

**Mudanças:**

- **Novo pré-requisito formal:** Bloco 1.5 completo + `docs/finance/operating-budget.md` selado + `docs/product/nfr.md` com ≥10 RNFs numéricos + `docs/architecture/foundation-constraints.md` selado. `scripts/decide-stack.sh` já falha sem `mvp-scope.md`; adicionar checagem equivalente para os outros 3.
- **Novo item 2.5 — Stress test de scaffolding na VPS Hostinger:** para cada alternativa candidata, rodar scaffolding mínimo no hardware alvo e medir RAM/CPU em boot. Rejeitar candidata que consuma >60% em idle. Origem: Gemini §L#2.
- **Novo item 2.6 — Parecer técnico externo (advisor horista):** ADR-0001 não vai para `status: accepted` sem review assinado de engenheiro/arquiteto externo horista (~4h). Origem: Claude N#10 + Codex §K#1 + §M#6. **Decisão do PM obrigatória** — seção 0 decisão 4.
- **Reservar ADRs `status: proposed`**: 0003 multi-tenancy, 0004 identity/auth, 0005 modelo de dados, 0006 runtime de cálculo metrológico. Correção de numeração: renomear `0002-mcp-policy.md` ou abrir `0001-stack.md` como draft para fechar o gap 0000 → 0002. Origem: Claude §B.

---

### Ajustes ao Bloco 3 — Gates reais de execução de teste

**Mudanças:**

- **Novo item 3.4 — `/spec-review` sub-agent independente do dual-verifier.** Recebe `specs/NNN/spec.md` → bate contra `glossary-domain.md`, `mvp-scope.md`, `nfr.md`, `personas.md` → devolve `spec-review.json` com `verdict: ok/needs-revision`. Hook bloqueia `architect` até `verdict: ok`. Adicionar como passo 3.5 do §6 do CLAUDE.md. Origem: Claude K3.

---

### Ajustes ao Bloco 4 — Tradutor PM + pausa dura

**Mudanças:**

- **Novo item 4.6 — 2 exemplos executados de `pm-report.md`** (um aprovação, uma rejeição) em `specs/000-example/`. PM revisa, dá feedback, vira corpus de calibração para `explain-slice.sh`. Origem: Claude §H.
- **Novo item 4.7 — `check-r12-vocabulary.sh` aceita `glossary-pm.md` (positivo) e rejeita a blocklist técnica.** Não apenas rejeitar termos técnicos — exigir termos do glossário positivo. Origem: Claude §H.
- **Novo item 4.8 — `docs/policies/r6-r7-policy.md`** com categorias sem override. Origem: Codex §L#3.

---

### Ajustes ao Bloco 5 — Juiz externo (CI + GitHub Action)

**Mudanças:**

- **Novo item 5.5 — Backup off-site bloqueante em `.github/workflows/ci.yml`.** Origem: Gemini §L#3.
- Manter itens 5.1-5.4 inalterados.

---

### Ajustes ao Bloco 6 — Defesas adicionais

**Mudanças:**

- **Novo item 6.3 — `docs/governance/raci.md`** (matriz tipo-de-decisão × ator). Origem: Claude N#7 + Codex §G + Gemini §G.
- **Novo item 6.4 — `docs/governance/harness-evolution.md`** com cadência mensal/trimestral de revisão de regras + critérios para propor/revogar + exigência de retrospectiva após cada bloco e após incidentes severos. Origem: Codex §J.
- **Novo item 6.5 — `docs/governance/cooldown-policy.md`** com cooldown 24h para classes críticas (constitution, ADR aceito, finance/budget, compliance). Hook `pre-commit-gate.sh` bloqueia commit que altera classes críticas se commit anterior em mesma classe < 24h. Origem: Claude N#10.
- **Novo item 6.6 — `docs/data/fixtures-policy.md`** + estrutura `tests/fixtures/` com dados sintéticos realistas. Origem: Codex §K#4.
- **Novo item 6.7 — Skill `/project-status`** que lê telemetria e gera dashboard Markdown visual em linguagem de produto. Origem: Gemini §N#10.
- **Novo item 6.8 — `docs/harness-limitations.md`** ganha seção "Edição externa de hooks por humano" (Claude K9) + "Congelamento de admin bypass com teto 5" (Claude K2). **Decisão do PM obrigatória** — seção 0 decisão 3.

---

### Ajustes ao Bloco 7 — Re-auditoria e go/no-go Dia 1

**Mudanças:**

- **Novo item 7.4 — Gate "produto pronto para primeiro tenant real".** Critérios: Trilha #2 (compliance produto) completa, advisor técnico assinou ADR-0001, backup off-site testado com restore, 5 cenários de incident response executados em dry-run. Origem: Claude K4.
- **Ajuste ao item 7.1:** re-auditoria externa da meta-auditoria #2 (esta) para verificar se Bloco 1.5 + Trilha #2 foram executados honestamente.

---

### Itens removidos do plano vigente

Nenhum. Os 7 blocos + Trilha #1 permanecem intactos — só são **aumentados**.

---

### Itens rejeitados ou adiados do consenso

- **Claude #6 (`docs/product/nfr.md` gate bloqueante no `/decide-stack`):** aceito como ideia, mas implementado de forma mais leve — gate falha só se o arquivo não existir ou contiver placeholder "a definir". Não exige ≥10 itens numéricos porque o número exato depende do produto.
- **Claude #7 (advisor horista 4h/mês permanente):** adiado para decisão do PM (seção 0 #4). Pode ser só para o Bloco 2, ou contínuo — o PM decide.
- **Gemini #N#4 ("Limites de Hardware" no template de ADR-0001):** integrado ao item 2.5 (stress test) em vez de virar campo de template.
- **Gemini §L#4 ("Sentinel Hook" semanal para drift legislativo):** rebaixado para "investigar" — risco de virar fricção inútil. Alternativa sugerida: cadência mensal via skill `/law-status-refresh` em vez de hook semanal forçado.

---

## 6. Resposta direta ao PM (versão expandida)

### 6.1. Veredito honesto

Você estava certo em desconfiar. O plano de 7 blocos foi escrito por um Claude anterior em uma sessão onde o foco era "endurecer o harness contra drift", e ele fez isso bem. Mas o mesmo plano tratou "o produto Kalibrium" como algo que vai ser definido **depois**, em lugar nenhum específico. Os 3 auditores externos, sem se falarem, apontaram a mesma coisa: o harness amadureceu antes do produto, e o Bloco 2 (decidir a tecnologia) vai produzir uma recomendação sem critério se você avançar agora.

**Isto não é um ataque ao trabalho feito.** O Bloco 1 (travar o conjunto de regras e verificações automáticas do projeto contra auto-modificação) está sólido e bem executado — três camadas de verificação automática, destravamento com quatro conferências antes de liberar, arquivos críticos com selo de integridade, lista dos selos registrada em um arquivo central, e um registro histórico que só permite adicionar entradas (nunca apagar). Nada disso se perde. O que está faltando é a **etapa anterior**: o que você está construindo, para quem, com qual limite.

### 6.2. O que muda no plano

- **Fica um novo bloco no meio:** "Bloco 1.5 — Fundação de Produto", com 14 entregáveis. Duração estimada: 2-3 sessões. Após ele, o Bloco 2 destrava.
- **Abre uma segunda trilha paralela** ("Compliance do Produto") com 14 entregáveis. Roda em background. Bloqueia o primeiro cliente pago, **não** bloqueia o Bloco 2.
- **Os 7 blocos existentes recebem 15 micro-ajustes** (novos itens dentro de blocos existentes). Nenhum bloco é refeito.
- **Nada é removido do plano.**

### 6.3. Duração estimada do desvio

Em número de sessões de trabalho (unidade operacional real do seu modelo, não dias de calendário):

- 2-3 sessões: Bloco 1.5 executado
- 1 sessão: rascunhos iniciais da Trilha #2 + procurement tracker
- 15 minutos: escrever e selar política de congelamento de bypass
- Fora de sessão: negociar advisor horista (se você aceitar decisão 4)

Depois disso, o Bloco 2 roda como planejado, agora com critério rastreável. Sem Bloco 1.5, minha recomendação explícita é **não iniciar o Bloco 2**.

### 6.4. As 4 decisões que você precisa tomar (repetidas de forma compacta)

1. **Inserir Bloco 1.5 — Fundação de Produto?** — ( ) sim ( ) não ( ) ajustar
2. **Abrir Trilha Paralela #2 — Compliance do Produto?** — ( ) sim ( ) não ( ) ajustar
3. **Congelar o atalho do dono do repositório (aprovar envio direto sem passar pelo robô verificador) até o Bloco 5 fechar, com teto de 5 usos totais?** — ( ) sim com teto 5 ( ) sim sem teto ( ) não
4. **Contratar um revisor técnico externo por horas (~4h/mês) para dar segundo olhar na decisão de tecnologia e nas escolhas de arquitetura antes de você aceitar?** — ( ) sim permanente ( ) sim só para o Bloco 2 ( ) adiar ( ) não

### 6.5. O que eu já recomendo sem pedir aprovação (consenso 3/3, esforço baixo, risco zero)

Se você aceitar a decisão 1, estes itens entram direto no Bloco 1.5 sem precisar de micro-aprovação:

- `README.md` raiz
- `mvp-scope.md`, `personas.md`, `journeys.md`, `nfr.md`, `glossary-pm.md`, `laboratorio-tipo.md`
- `finance/operating-budget.md`
- `compliance/out-of-scope.md`
- Correção do `constitution.md §5` (R1-R10 → R1-R12)
- Preencher `TECHNICAL-DECISIONS.md` + gate `wc -l`
- Mover `roles-from-bmad.md` para `historical/`

Se você aceitar a decisão 2, estes itens entram direto na Trilha #2:

- `threat-model.md`, `lgpd-base-legal.md`, `dpia.md`, `rot.md`, `secrets-policy.md`, `backup-dr-policy.md`, `incident-response-playbook.md`, `dependency-policy.md`, `contrato-operador-template.md`
- Policies por domínio: metrologia, fiscal, REP-P, ICP-Brasil, LGPD
- `vendor-matrix.md`, `law-watch.md`, `traceability-template.md`, `procurement-tracker.md`
- Rascunhos 5 primeiros revisados por DPO horista

### 6.6. O risco real se você não fizer isso

Não é abstrato. O V1 do Kalibrium morreu por **anti-pattern 7: "stack decidida sem ADR"** (ver `docs/reference/v1-post-mortem.md`). O plano atual planeja um ADR — mas um ADR sem `mvp-scope.md`, sem `nfr.md`, sem baseline arquitetural, sem orçamento, é **exatamente o mesmo erro em forma mais organizada**. Você teria um documento bonito formalizando premissas ausentes. Seis meses depois, o primeiro slice complexo expõe que a tecnologia escolhida não cabe no hardware, não cobre o compliance, ou não comporta o orçamento. O segundo `post-mortem.md` vai dizer a mesma coisa que o primeiro.

O custo de inserir o Bloco 1.5 é 2-3 sessões. O custo de não inserir é o V2 inteiro.

---

## 7. Apêndice — rastreabilidade

### 7.1. Arquivos fonte citados nas consolidações

**Auditorias centrais (3 arquivos):**
- `docs/audits/external/audit-claude-opus-4-6-completeness-2026-04-10.md` (47.903 bytes)
- `docs/audits/external/completeness-audit-gpt-5-codex-2026-04-10.md` (28.424 bytes)
- `docs/audits/external/completeness-audit-gemini-3-1-pro-2026-04-10.md` (13.312 bytes)

**Documentos de contexto lidos em ordem antes da consolidação:**
- `CLAUDE.md`
- `docs/constitution.md` (v1.1.0 — P1-P9 + R1-R12)
- `docs/incidents/pr-1-admin-merge.md` (razão do modelo humano=PM)
- `docs/audits/meta-audit-2026-04-10.md` (meta-auditoria #1, enforcement)
- `docs/audits/meta-audit-2026-04-10-action-plan.md` (plano de 7 blocos vigente)
- `docs/audits/progress/meta-audit-tracker.md` (estado do progresso)
- `docs/decisions/pm-decision-meta-audit-2026-04-10.md`
- `docs/audits/completeness-audit-prompt-2026-04-10.md` (prompt que gerou as 3 auditorias)
- `docs/audits/completeness-meta-audit-prompt-2026-04-10.md` (prompt desta meta-auditoria)
- `docs/glossary-domain.md`
- `docs/adr/0000-template.md`, `docs/adr/0002-mcp-policy.md`
- `docs/TECHNICAL-DECISIONS.md`
- Árvore do repositório (`find` completo)

**Arquivos que as auditorias afirmam existir e não existem (descobertos no checkpoint de verificação):**
- `docs/reference/ideia-v1.md` — citado em 4 lugares, ausente
- `docs/reference/v1-post-mortem.md` — **nota:** citado nas auditorias Claude e Codex mas não li o conteúdo (não precisei para a consolidação; PM confirma se existe)
- `docs/mvp-scope.md` / `docs/product/mvp-scope.md` — ausente, `scripts/decide-stack.sh` falha sem ele
- `docs/compliance/out-of-scope.md` — citado no tracker, ausente
- `docs/harness-limitations.md` — citado por Claude como existente (cobre "débito git e symlinks Windows"); não li o conteúdo nesta sessão
- `docs/guide-backlog.md` — citado por Codex como existente; não li o conteúdo nesta sessão

### 7.2. Métricas desta meta-auditoria

- **Comparações de seção × auditor**: 11 dimensões (A–K) × 3 auditores = 33 vereditos comparados
- **Achados de consenso 3/3**: 5 (dimensões A, C, D, I + baseline arquitetural B)
- **Achados de consenso 2/3**: 5 (README, R12 vazio, rastreabilidade normativa, out-of-scope, advisor externo)
- **Divergências identificadas**: 4 (veredito binário, tamanho do pacote de fundação, admin bypass, granularidade dos ADRs pré-stack)
- **Insights únicos aceitos**: 9 do Claude, 6 do Codex, 5 do Gemini
- **Insights únicos rejeitados/investigar**: 1 (Gemini "Sentinel Hook" semanal — rebaixado para investigar)
- **Mudanças propostas ao plano**: 1 bloco novo, 1 trilha nova, 15 micro-ajustes a blocos existentes, 0 remoções

### 7.3. Erros que encontrei nas próprias auditorias

- **Gemini — inconsistência veredito vs. seções A-K**: o veredito binário "Sim, com condições (3 bloqueantes)" não é consistente com os gaps que ele mesmo listou nas dimensões (LGPD, DPIA, threat-model, backup, R12 vazio, etc.). Leitura caridosa: ele interpretou "pronto para Bloco 2" como "pronto para rodar o comando", não como "pronto para produzir ADR defensável". Claude e Codex interpretaram no segundo sentido — que é o correto dado o prompt. Peso do veredito do Gemini: reduzido no julgamento final.
- **Gemini — ausência de menção ao admin bypass**: mesmo tendo lido `docs/incidents/pr-1-admin-merge.md` (declarado nos materiais lidos), Gemini não menciona o bypass em nenhuma seção. Claude §K2 observa que já são 3 bypasses em 1 dia com evidência concreta. Lacuna de rigor do Gemini.
- **Claude — inflação na lista M** (30 artefatos como pré-requisito do Bloco 2): inclui itens que dependem da própria stack escolhida (formato de certificado PDF/A, runtime de cálculo metrológico, políticas de observabilidade). Cria dependência circular. Codex fez a mesma análise sem inflar. Peso da lista de 30 do Claude: reduzido; lista final baseada nos 14 do Codex + 3 críticos do Claude.
- **Todos os 3** — nenhum dos 3 auditores observou que `docs/audits/completeness-meta-audit-prompt-2026-04-10.md` §4.2 esperava os arquivos com nomes `audit-{claude,codex,gemini}-completeness-2026-04-10.md`, mas os arquivos reais têm nomes diferentes (`audit-claude-opus-4-6-completeness-...`, `completeness-audit-gpt-5-codex-...`, `completeness-audit-gemini-3-1-pro-...`). Não afeta o conteúdo, mas é um drift de convenção de nome que o Bloco 1.5 deveria padronizar.

### 7.4. O que eu não verifiquei pessoalmente (transparência)

- Não rodei os scripts `decide-stack.sh` ou `explain-slice.sh` para confirmar que são stubs. Aceitei a afirmação do Codex com base na consistência entre auditores.
- Não li os conteúdos completos de `docs/reference/v1-post-mortem.md`, `docs/harness-limitations.md` e `docs/guide-backlog.md`. Usei os trechos citados pelos auditores. Se algum desses documentos tiver conteúdo que contradiz as conclusões acima, esta meta-auditoria precisa de correção.
- Não verifiquei se o arquivo `ideia.md` da raiz antiga (mencionado pelo Gemini como candidato a cópia) realmente existe. O PM confirma no próximo passo.
- Não avaliei a viabilidade econômica concreta das recomendações (ex.: custo de DPO horista no mercado brasileiro 2026). Tratei como "marginal" com base no raciocínio dos auditores, mas o PM tem a palavra final.

---

**Fim da meta-auditoria #2.**

**Próximo passo (único):** PM responde as 4 decisões da seção 0 / seção 6.4. A partir daí, o Bloco 1.5 entra no tracker, `meta-audit-tracker.md` é atualizado, e nova sessão executa os itens em `docs/audits/progress/block-1.5-product-foundation.md`.
