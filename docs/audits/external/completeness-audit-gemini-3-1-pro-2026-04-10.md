# Auditoria externa de completude — Gemini 3.1 Pro

**Data:** 10 de abril de 2026
**Auditor:** Gemini 3.1 Pro
**Duração da auditoria:** 45 minutos
**Materiais lidos:** `CLAUDE.md`, `docs/constitution.md`, `docs/audits/meta-audit-2026-04-10.md`, `docs/audits/meta-audit-2026-04-10-action-plan.md`, `docs/audits/progress/meta-audit-tracker.md`, `docs/glossary-domain.md`, `docs/adr/0002-mcp-policy.md`, `docs/incidents/bloco1-admin-bypass-2026-04-10.md`, `docs/incidents/pr-1-admin-merge.md`, `docs/decisions/pm-decision-meta-audit-2026-04-10.md`, árvore do repositório (`tree /f kalibrium-v2`).

## A. Documentação fundacional de produto
**Ausente** — Não foram encontrados arquivos como `PRD.md`, `specs/000-mvp/spec.md`, personas ou jornadas detalhadas no repositório `kalibrium-v2`.
O projeto carece de uma definição explícita do que constitui o MVP "funcional" (o "Caminho Dourado"). O arquivo `ideia.md` na raiz (externo ao repositório `kalibrium-v2`) é um brainstorm exaustivo, mas não um documento de escopo acionável para o V2. Sem isso, o agente `architect` gerará planos baseados em suposições, aumentando o risco de *scope creep*.
**Recomendação:** Criar `docs/mvp-scope.md` definindo as 3-5 funcionalidades verticais essenciais, personas primárias e o que está explicitamente fora do MVP.

## B. Decisões arquiteturais fundacionais
**Parcial** — Existe o `docs/TECHNICAL-DECISIONS.md` e o `ADR-0002`, mas o `ADR-0001` (Stack) é apenas um placeholder.
Decisões críticas como "Estratégia de Multi-tenancy" (essencial dado o P13 da constituição) e "Modelo de Deployment" (Hostinger VPS vs Single Box) precedem a stack, pois ditam restrições de recurso. O `meta-audit-action-plan.md` (Bloco 2) já prevê o ADR-0001, mas ele deve incluir a justificativa da separação de dados.
**Recomendação:** Expandir o Bloco 2 para incluir um ADR específico sobre o isolamento de dados (Row Level Security vs Database per Tenant) antes de iniciar qualquer código de domínio.

## C. Governança de segurança e compliance
**Parcial** — Os princípios P13 (isolamento) e P15 (não mockar domínio) são fortes, mas falta o "viver o dia a dia".
Não há um `docs/security/threat-model.md` nem um Plano de Resposta a Incidentes funcional (apenas registros de incidentes passados). Para um SaaS regulado, a falta de um DPIA (LGPD) e de uma política de segredos (como o agente lida com chaves de API em produção na Hostinger) é um risco de "Bloco 1" (bloqueante).
**Recomendação:** Criar `docs/security/secrets-policy.md` definindo que o agente nunca lê `.env` de produção e como o PM deve configurar segredos manualmente na VPS.

## D. Processos operacionais
**Ausente** — O repositório foca em "como construir", mas ignora "como rodar".
Não existem artefatos para Backup/Restore (crítico para Hostinger single-box), monitoramento ou atualização legislativa (cutoff do LLM). O PM sendo não-técnico não saberá se o sistema caiu ou se o backup falhou.
**Recomendação:** Adicionar ao Bloco 4 a criação de um `docs/ops/runbook-emergency.md` para o PM, com comandos simples de "verificar status" e "restaurar último backup".

## E. Artefatos de domínio regulado
**Parcial** — O `docs/glossary-domain.md` é excelente, e o plano para consultores externos (M1/F1) é robusto.
Falta a ponte entre o glossário e o código. O `domain-expert` (Bloco 6.1) é a solução correta, mas ele precisa de "Golden Tests" (M3/F3) como fonte de verdade, não apenas texto. A ausência de um mapeamento de normas (ISO 17025) para requisitos funcionais é um gap de rastreabilidade.
**Recomendação:** Criar `docs/compliance/traceability-matrix.md` ligando requisitos da ISO 17025 aos futuros IDs de AC (Acceptance Criteria).

## F. Estrutura do repositório e organização
**Presente** — A estrutura é exemplar para automação por agentes de IA.
A separação de `specs/`, `scripts/hooks/`, `docs/adr/` e `telemetry/` é lógica e facilita o isolamento de contexto (P3). O uso de `MANIFEST.sha256` e scripts de lock (Bloco 1) demonstra maturidade incomum em projetos iniciados por IA.
**Recomendação:** Manter a disciplina. Evitar a criação de pastas na raiz do repositório; tudo deve estar sob `docs/`, `src/`, `tests/` ou `specs/`.

## G. Papéis, responsabilidades e limites
**Presente** — Definido em `CLAUDE.md` e `constitution.md`.
A distinção Humano=PM (não-técnico) e Agentes=Execução está bem estabelecida. O sistema de "Pausa Dura" (Decisão 0.4) é a maior salvaguarda contra o erro humano por fadiga.
**Recomendação:** Garantir que o `explain-slice.sh` (Bloco 4.1) explicite quando uma decisão é "Escalação para Consultor Humano" (Membro Externo).

## H. Tradução técnico-para-produto
**Ausente** — Conforme meta-auditoria anterior, o `explain-slice.sh` é um stub.
O PM hoje receberia erros técnicos puros ou logs de verificação JSON. O "Bloco 4" é crítico e deve ser priorizado logo após a stack, pois o PM perderá a confiança no processo se não entender as rejeições do verifier.
**Recomendação:** Criar `docs/templates/pm-report-template.md` com exemplos de traduções (ex: "Falha de Integridade" em vez de "Foreign Key Constraint Error").

## I. Governança financeira e sustentabilidade
**Ausente** — Não há registro de custos esperados.
A Hostinger VPS tem limites de CPU/RAM. Se a stack escolhida (ADR-0001) for pesada (ex: Java/Spring ou múltiplos containers Docker), o projeto pode inviabilizar o orçamento do PM. O custo de tokens (Bloco 1.3/record-tokens) é um bom começo, mas falta o teto.
**Recomendação:** No ADR-0001, incluir uma seção "Custo Operacional Estimado" comparando o consumo de RAM/CPU das alternativas.

## J. Plano de evolução do próprio harness
**Presente** — O Bloco 1 selou o harness e o Bloco 7 prevê a re-auditoria.
O processo de "Relock" interativo (`scripts/relock-harness.sh`) é uma excelente barreira contra drift. A constituição (§5) define o processo de alteração via ADR.
**Recomendação:** Criar `docs/retrospectives/000-harness-setup.md` para registrar o que foi aprendido durante o selamento do Bloco 1 antes de iniciar o Bloco 2.

## K. Outros pontos relevantes
**Truncamento de histórico e contexto:** Como o PM não revisa código, a telemetria (`meta.jsonl`) e os relatórios de slice (`slice-report.sh`) são os únicos olhos dele. Se esses arquivos crescerem demais, o PM deixará de ler.
**Recomendação:** Implementar uma skill `/project-status` que resuma a telemetria em um dashboard Markdown visual (tabela de slices prontos vs pendentes) em linguagem de produto.

## L. 5 maiores ameaças à entrega do MVP em produção

### Ameaça #1: "Alucinação Regulada" em Cálculos Metrológicos
**Probabilidade:** Alta
**Impacto:** Crítico (Perda de acreditação do laboratório cliente)
**Descrição:** O agente implementa o motor de cálculo (GUM) que passa nos testes unitários criados por ele mesmo, mas o cálculo está sutilmente errado para um caso de borda real.
**Por que o plano atual não cobre:** O Bloco 1-5 foca em segurança do harness, não em correção de domínio. Depende da Trilha Paralela (Golden Tests).
**Mitigação proposta:** Tornar o Bloco 6.5 (Golden Tests) bloqueante para qualquer slice que altere arquivos em `src/metrology/`.

### Ameaça #2: Inviabilidade Financeira na VPS Hostinger
**Probabilidade:** Média
**Impacto:** Alto (Sistema lento ou quedas constantes)
**Descrição:** A stack decidida no ADR-0001 consome 90% da RAM da VPS apenas no boot, deixando pouco espaço para concorrência de usuários ou execução de cron jobs.
**Por que o plano atual não cobre:** O foco está no desenvolvimento, não no dimensionamento de infraestrutura.
**Mitigação proposta:** No Bloco 2, realizar um "Stress Test de Scaffolding" na VPS antes de aprovar a stack.

### Ameaça #3: Perda de Dados por Falta de Plano de Backup
**Probabilidade:** Baixa
**Impacto:** Crítico (Morte do projeto)
**Descrição:** Falha de hardware na Hostinger ou erro de script de migração deleta o banco multi-tenant sem backup externo.
**Por que o plano atual não cobre:** Documentação de Ops é "Ausente" (Seção D).
**Mitigação proposta:** Criar script de backup off-site (ex: S3 ou Dropbox) como requisito para o Bloco 5 (CI/CD).

### Ameaça #4: Drift Legislativo Pós-Cutoff do LLM
**Probabilidade:** Alta
**Impacto:** Médio (Rejeição de notas fiscais pelo SEFAZ)
**Descrição:** Mudança no leiaute da NF-e em 2026 que o modelo de IA desconhece.
**Por que o plano atual não cobre:** IA não lê notícias em tempo real de forma estruturada.
**Mitigação proposta:** Criar um "Sentinel Hook" que obriga o PM a atualizar um arquivo `docs/compliance/legal-status.md` semanalmente com as novidades dos consultores.

### Ameaça #5: Fadiga de Decisão do PM (Não-Técnico)
**Probabilidade:** Média
**Impacto:** Alto (Aprovação de design ruim)
**Descrição:** O PM recebe 3 rejeições consecutivas do verifier e aprova um "override" só para o projeto avançar, sem entender o débito técnico gerado.
**Por que o plano atual não cobre:** A "Pausa Dura" ajuda, mas não impede design ruim que "passa" no teste.
**Mitigação proposta:** Skill `/explain-slice` deve incluir uma seção "Impacto no Futuro" (ex: "Isso tornará a tela de cadastro mais lenta em 2 meses").

## M. Veredito binário

- **O projeto está pronto para avançar para o "Bloco 2" (decisão de stack) no estado atual?** **Sim, com condições.**
- **Quais são as mudanças bloqueantes (se houver) antes de decidir stack?**
    1. Criação do `docs/mvp-scope.md` (Fundação de produto).
    2. Criação do `docs/reference/ideia-v1.md` (Rastreabilidade de visão).
    3. Definição do teto de hardware da VPS Hostinger para guiar o ADR-0001.
- **Quais artefatos de fundação de produto deveriam existir antes de qualquer commit que toque código-fonte de produto?**
    1. `docs/mvp-scope.md` (Escopo).
    2. `docs/compliance/metrology-policy.md` (Regras de cálculo).
    3. `docs/security/secrets-policy.md` (Gestão de chaves).

## N. 10 sugestões acionáveis em ordem de impacto

1. **[esforço: baixo] Criar `docs/mvp-scope.md` imediatamente.**
    - **Por quê:** Evita que o agente `architect` perca tempo em funcionalidades que não serão usadas agora.
    - **Como:** Sintetizar o "Caminho Dourado" (Login + Tenant + Cadastro Cliente + OS Inicial) em um arquivo de spec de alto nível.

2. **[esforço: baixo] Copiar `ideia.md` para `docs/reference/ideia-v1.md`.**
    - **Por quê:** Cumpre o R7 e garante que a visão original está preservada sem poluir a raiz.
    - **Como:** `cp ideia.md kalibrium-v2/docs/reference/ideia-v1.md`.

3. **[esforço: médio] Implementar `docs/ops/backup-plan.md`.**
    - **Por quê:** Segurança de dados é o maior ativo de um SaaS B2B.
    - **Como:** Definir rotina de backup diário fora da VPS Hostinger.

4. **[esforço: baixo] Adicionar "Limites de Hardware" ao `docs/adr/0001-template.md`.**
    - **Por quê:** Força a IA a considerar o custo de RAM/CPU na recomendação da stack.
    - **Como:** Adicionar campo obrigatório no template de ADR.

5. **[esforço: médio] Criar o primeiro "Golden Test" manual.**
    - **Por quê:** Valida o fluxo do Bloco 6.5 antes mesmo de ter a stack.
    - **Como:** Criar um CSV simples com 5 casos de soma de incertezas e o resultado esperado.

6. **[esforço: baixo] Criar `docs/decisions/resource-limits.md`.**
    - **Por quê:** PM precisa declarar quanto pretende gastar por mês na Hostinger.
    - **Como:** Documentar o plano contratado (ex: VPS 2 - 4GB RAM).

7. **[esforço: baixo] Padronizar IDs de Requisitos.**
    - **Por quê:** Facilita a rastreabilidade entre spec e teste.
    - **Como:** Usar formato `REQ-DOM-000` (ex: `REQ-MET-001` para cálculo).

8. **[esforço: baixo] Criar `README.md` no repositório `kalibrium-v2`.**
    - **Por quê:** Orientação básica para qualquer um (humano ou agente) que "caia" na pasta.
    - **Como:** Breve descrição do projeto e apontamento para `CLAUDE.md`.

9. **[esforço: médio] Iniciar `docs/security/threat-model.md`.**
    - **Por quê:** Identificar vetores de ataque antes de expor o sistema à internet.
    - **Como:** Listar ameaças como "Acesso indevido de tenant A aos dados do tenant B".

10. **[esforço: baixo] Criar skill `/project-status`.**
    - **Por quê:** Visibilidade para o PM sem precisar ler logs JSONL.
    - **Como:** Script simples que lê `slice-registry.md` e gera resumo.

## O. Comentário livre
O projeto Kalibrium V2 é tecnicamente impressionante em sua arquitetura de segurança ("harness"). No entanto, ele sofre da "Maldição do Construtor": as ferramentas para construir a casa são incríveis, mas a planta da casa ainda é um esboço em guardanapo (`ideia.md`). Se o foco não mudar para a **definição do produto** (Seção A e B desta auditoria) nas próximas 48h, corre-se o risco de ter um repositório perfeitamente seguro e selado, mas que implementa a coisa errada ou de forma economicamente inviável para o hardware alvo. Priorize o `mvp-scope.md`.
