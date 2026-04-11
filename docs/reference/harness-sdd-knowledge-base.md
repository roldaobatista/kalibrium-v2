# Base de Conhecimento — Harness Engineering + Spec-Driven Development

**Fonte única:** `C:\PROJETOS\saas\Harness + Spec-Driven Development.md` (785 linhas, 8368 palavras, 6 perspectivas consolidadas de LLMs externas)
**Data de extração:** 2026-04-11
**Método:** leitura integral do documento, linha por linha, em 4 chunks sequenciais sem filtro. Extração de 219 aprendizados brutos, consolidados em 62 temas.
**Status:** read-only. Este arquivo é **conhecimento de referência**, não um plano de ação. Qualquer ação derivada daqui precisa virar ADR ou entrada no meta-audit-tracker.

---

## Legenda

### Categoria

| Código | Nome | O que cobre |
|---|---|---|
| **SETUP** | Setup de máquina | Windows/WSL/Docker/runtimes/IDE |
| **FLUXO** | Fluxo canônico SDD | Passos ordenados: constitution → spec → clarify → plan → tasks → implement |
| **DOC** | Documentos-chave | Estrutura de cada arquivo (constitution, spec, plan, tasks, etc.) |
| **HARNESS** | Infraestrutura do agente | Hooks, sub-agents, skills, scripts determinísticos, sandbox |
| **VERIF** | Verificação e gates | Testes, verifier, reviewer, gates, AC-red |
| **OBS** | Observabilidade | Logs, traces, métricas, eval suite |
| **ENTREGA** | CI/CD e deploy | PR, pipeline, GitOps, canary, feature flags |
| **CULTURA** | Mentalidade e papéis | Filosofia, papel do humano, anti-padrões |

### Estado no Kalibrium V2

| Símbolo | Significado |
|---|---|
| ✅ | **JÁ TEMOS** — implementado no V2, confirmado funcional |
| 🟡 | **PARCIAL** — parte feita, parte falta |
| ❌ | **GAP** — não temos, precisa fazer |
| 🚫 | **CONFLITA** — viola alguma regra R1–R12 do nosso harness |
| ⚠️ | **AVALIAR** — precisa auditoria antes de decidir (sessão nova) |

### Prioridade (subjetiva, a ser revisada pelo PM)

| Código | Significado |
|---|---|
| **P0** | Crítico — sem isso, fluxo canônico não funciona |
| **P1** | Alto — afeta qualidade do primeiro slice de produto |
| **P2** | Médio — melhora robustez a médio prazo |
| **P3** | Baixo — refinamento |
| **SKIP** | Decidido descartar |

### Aplicabilidade

| Código | Significado |
|---|---|
| **USAR** | Copiar a dica diretamente, sem adaptação |
| **ADAPTAR** | Usar a ideia, mas ajustar ao contexto Kalibrium (PM humano, compliance BR, etc.) |
| **DESCARTAR** | Não é pra nós (conflito arquitetural ou fora de escopo) |
| **AVALIAR** | Auditar antes de decidir |

---

## Sumário executivo (contagens)

| Categoria | Total | ✅ | 🟡 | ❌ | 🚫 | ⚠️ |
|---|---|---|---|---|---|---|
| SETUP | 6 | 0 | 0 | 6 | 0 | 0 |
| FLUXO | 10 | 2 | 1 | 7 | 0 | 0 |
| DOC | 8 | 3 | 1 | 4 | 0 | 0 |
| HARNESS | 14 | 6 | 3 | 3 | 0 | 2 |
| VERIF | 10 | 4 | 2 | 3 | 0 | 1 |
| OBS | 4 | 0 | 1 | 3 | 0 | 0 |
| ENTREGA | 5 | 0 | 1 | 3 | 0 | 1 |
| CULTURA | 5 | 3 | 0 | 1 | 1 | 0 |
| **Total** | **62** | **18** | **9** | **30** | **1** | **4** |

**Leitura rápida:** ~29% já temos, ~48% são gap real, ~15% parcial, ~6% precisa auditar, ~2% conflita com nossa arquitetura.

---

# SETUP — Setup de máquina

## T01 — Arquitetura Windows 11 host + WSL2 Ubuntu + Docker Desktop + VS Code

**Categoria:** SETUP
**Fontes:** Seção A, linhas 1-15, 50-54
**Estado V2:** ❌ GAP
**Prioridade:** P2 (só afeta produtividade do PM na máquina, não afeta código)
**Aplicabilidade:** USAR

**O que diz:** Windows 11 como host (drivers, navegador, Office, IDE). WSL2 com Ubuntu 24.04 LTS (manutenção até maio/2029) como ambiente principal de desenvolvimento. Docker Desktop com backend WSL2 para bancos/filas/serviços. VS Code no Windows + extensão WSL + Dev Containers. Nunca dual boot. Código salvo no filesystem Linux, aberto com `code .` do terminal WSL.

**Aplicação ao V2:** quando Bloco 2 fechar e a stack estiver decidida, materializar em `docs/environment-setup.md` (ver Bloco 8.6 do external-guides-action-plan). Item de descoberta de ambiente, não de harness de código.

---

## T02 — Versões-alvo: Node 24 LTS, Python 3.13/3.14, PowerShell 7.6

**Categoria:** SETUP
**Fontes:** Seção A, linhas 17-21
**Estado V2:** ❌ GAP
**Prioridade:** P2
**Aplicabilidade:** USAR (se adotarmos Node ou Python)

**O que diz:** PowerShell 7.6.0 LTS no host (lado a lado com PS 5.1). Node 24 em Active LTS até abril/2028. Python 3.13 baseline conservadora, 3.14 opcional. PostgreSQL 16/17/18 paralelas suportadas — pinar por projeto via Docker, não "casar" o host com uma versão.

**Aplicação ao V2:** input direto pro ADR-0001 de stack. Se escolhermos Node ou Python, estes são os baselines de referência.

---

## T03 — .wslconfig com limites explícitos de recurso

**Categoria:** SETUP
**Fontes:** Seção A, linhas 83-93
**Estado V2:** ❌ GAP
**Prioridade:** P3
**Aplicabilidade:** USAR (só na máquina do PM)

**O que diz:** Em hardware 32GB RAM, colocar teto explícito no WSL para não travar quando Docker + browser + IDE abrem juntos. Ponto de partida: `memory=12GB, processors=6, swap=8GB`. Escalar para 14-16GB depois se precisar rodar mais containers.

**Aplicação ao V2:** documento operacional do setup. Entra no `docs/environment-setup.md` quando o Bloco 8.6 for implementado.

---

## T04 — Template de repositório agent-first

**Categoria:** SETUP
**Fontes:** Seção A, linhas 62-79; Seção D, linhas 263-308
**Estado V2:** 🟡 PARCIAL (temos estrutura diferente mas funcional)
**Prioridade:** P2
**Aplicabilidade:** ADAPTAR

**O que diz:** Estrutura sugerida pelas fontes externas combina:
```
AGENTS.md (ou CLAUDE.md)
docs/{ARCHITECTURE, PRODUCT, RELIABILITY, SECURITY, decisions, runbooks, exec-plans, quality}/
.agents/skills/{create-feature, fix-bug, review-pr, deploy-service, cleanup-entropy}/
scripts/{bootstrap, dev, lint, typecheck, test, smoke, evals}.sh
apps/ services/ packages/
observability/{dashboards, alerts, queries}/
harness/{pipelines, templates, policies, environments}/
specs/NNN-feature/{spec, plan, research, data-model, contracts/, quickstart, tasks}.md
src/ tests/
```

**Aplicação ao V2:** nosso repo tem `docs/`, `scripts/`, `.claude/agents/`, `docs/compliance/`, `docs/policies/`. **Gaps vs template ideal:** `observability/`, `scripts/evals.sh`, `scripts/smoke.sh` (smoke hooks temos, smoke produto não), `apps|services|packages/` (ainda não existem porque stack não decidida). Também não temos `exec-plans/active|completed/`. Decidir se adotamos sub-tree `exec-plans/` ou se slices em `specs/NNN/` já cumprem a função.

---

## T05 — Base de máquina neutra de linguagem

**Categoria:** SETUP
**Fontes:** Seção A, linhas 138-161
**Estado V2:** ❌ GAP (stack não decidida)
**Prioridade:** P1 (destrava Bloco 2)
**Aplicabilidade:** USAR

**O que diz:** Não amarrar a máquina a Node ou Python no host. Base neutra (Windows+WSL+Docker+VS Code), linguagem definida por repo via Dev Containers ou Docker. Plano sugerido: (a) base neutra, (b) padronizar mesmo harness em qualquer linguagem, (c) criar 2 templates (Node/TS e Python), (d) só depois eleger um padrão.

**Aplicação ao V2:** coerente com nossa intenção de decidir stack via ADR-0001 (Bloco 2). Valida a decisão de não amarrar nada antes do `/decide-stack` rodar.

---

## T06 — Checklist de validação da máquina

**Categoria:** SETUP
**Fontes:** Seção A, linhas 99-126
**Estado V2:** ❌ GAP
**Prioridade:** P3
**Aplicabilidade:** USAR

**O que diz:** Comandos de validação:
- Host: `winget --version`, `pwsh -v`, `wsl --version`
- WSL: `node -v`, `python3 --version`, `git --version`, `docker version`, `docker compose version`, `code .`
- Smoke Docker: `docker run hello-world`
- Fluxo ideal: abrir Ubuntu → `~/workspace/projeto` → `code .` → `docker compose up -d` → `npm test` ou `pytest`

**Aplicação ao V2:** checklist operacional entra no `docs/environment-setup.md` (Bloco 8.6).

---

# FLUXO — Fluxo canônico SDD

## T07 — Fórmula central: Agente = Modelo + Harness

**Categoria:** FLUXO / CULTURA
**Fontes:** Seção B linha 167-178; Seção D linhas 248-260; Seção H linhas 681-691
**Estado V2:** ✅ JÁ TEMOS (implícito em toda a arquitetura)
**Prioridade:** P0 (é a filosofia base)
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** O modelo importa menos do que o sistema ao redor dele. Harness = tudo exceto o modelo (restrições, feedbacks, ferramentas, documentação, testes). **Prova empírica:** LangChain saltou de 52,8% → 66,5% no Terminal Bench 2.0 **sem trocar o modelo**, só mudando o harness. Progressão histórica: Prompt Engineering → Context Engineering → Harness Engineering.

**Aplicação ao V2:** alinhado com o investimento do Bloco 1 (selar harness antes de tudo). Documentar esta fórmula explicitamente em `docs/constitution.md` pode ajudar o PM a entender por que tanto trabalho de harness antes do produto.

---

## T08 — Fluxo canônico SDD-anchored (ordem obrigatória)

**Categoria:** FLUXO
**Fontes:** Seção E linhas 405-408, 428-455 (o mais detalhado); Seção I linhas 739-749
**Estado V2:** 🟡 PARCIAL (temos os passos mas nunca documentamos como SEQUÊNCIA oficial)
**Prioridade:** P0 (é o mapa do trabalho inteiro)
**Aplicabilidade:** USAR

**O que diz:** `Constituição → spec funcional → clarificação → plano técnico → contratos/testes → tasks → implementação → validação → atualização da spec`. É o ponto ótimo para software de produção real. Spec-first é pra protótipos, spec-as-source exige tooling muito confiável.

**9 passos detalhados (Spec Kit GitHub):**
1. Definir princípios do projeto (constitution + AGENTS.md)
2. Escrever spec funcional SEM entrar na stack
3. Etapa explícita de clarificação (ambiguidades bloqueantes)
4. SÓ DEPOIS gerar plano técnico (plan.md, research.md, data-model.md, contracts/, quickstart.md)
5. Desconhecidos → perguntas de pesquisa específicas (não "pesquise React")
6. Contratos e testes ANTES do código da feature
7. Quebrar implementação em tarefas pequenas ordenadas (tasks.md)
8. Implementar por fatias, não big bang
9. Fechar ciclo sincronizando spec ↔ código ↔ teste

**Aplicação ao V2:** **este é o fluxo que deve virar a espinha dorsal do `docs/constitution.md`.** Hoje temos peças (slice, verify, review) mas não temos o fluxo canônico explícito. **Ação:** adicionar seção "Fluxo canônico SDD" em `docs/constitution.md`, citar os 9 passos, mapear cada um para os comandos (`/new-slice`, `/clarify-slice` a criar, `/verify-slice`, `/review-pr`, etc).

---

## T09 — 3 níveis de maturidade SDD (spec-first, spec-anchored, spec-as-source)

**Categoria:** FLUXO / CULTURA
**Fontes:** Seção E linhas 420-426
**Estado V2:** 🟡 PARCIAL (operamos em spec-anchored sem ter decidido explicitamente)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:**
- **Spec-first:** escreve a spec antes, pode derivar depois. Bom pra protótipos, spikes, features isoladas.
- **Spec-anchored:** spec mantida junto do código durante toda a vida do sistema. Quando comportamento muda, spec e testes mudam juntos. **Melhor equilíbrio clareza + disciplina pra maioria dos sistemas de produção.**
- **Spec-as-source:** humano edita só a spec, código é gerado e não deve ser editado à mão. Exige tooling muito confiável.

**Aplicação ao V2:** Kalibrium é SaaS B2B de produção, com compliance crítico — **spec-anchored é a escolha certa**. Formalizar esta decisão em ADR curto ou direto no `docs/constitution.md`.

---

## T10 — Plan → Act → Verify → Reflect (padrão ReAct/Reflexion)

**Categoria:** FLUXO
**Fontes:** Seção B, linhas 172-173
**Estado V2:** ✅ JÁ TEMOS (implícito no fluxo de slice)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Cada tarefa termina com gate objetivo (teste passa, lint limpo, type-check ok). É o padrão ReAct/Reflexion transposto pra engenharia.

**Aplicação ao V2:** nosso fluxo de slice já faz isso (architect → ac-to-test → implementer → verifier → reviewer). Documentar que este é o padrão ReAct aplicado.

---

## T11 — Vertical slices pequenos (uma feature por loop)

**Categoria:** FLUXO
**Fontes:** Seção B, linha 174
**Estado V2:** ✅ JÁ TEMOS
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Uma feature por loop, atômica, com teste de aceitação. Evita drift de contexto.

**Aplicação ao V2:** nosso conceito de "slice" é exatamente isso. Confirmar explicitamente no `docs/constitution.md` que um slice = um vertical slice pequeno.

---

## T12 — Externalizar contexto em artefatos versionados

**Categoria:** FLUXO / CULTURA
**Fontes:** Seção E linhas 414-416; Seção D linha 255
**Estado V2:** ✅ JÁ TEMOS (política R7 e constitution)
**Prioridade:** P0
**Aplicabilidade:** USAR

**O que diz:** Sessões longas degradam (janela de contexto enche → agente esquece instruções antigas → começa a errar mais). O que é importante não pode ficar só no chat: precisa virar arquivo estável, curto, versionado e recuperável. SDD é uma forma de context engineering — tira a intenção da conversa efêmera e coloca em artefatos de alto sinal que o agente consulta just-in-time.

**Aplicação ao V2:** princípio já incorporado (R1 fonte única, constitution, slices em arquivo). Confirmação de que o caminho é certo.

---

## T13 — Clarificação explícita da spec antes do plano técnico

**Categoria:** FLUXO
**Fontes:** Seção E linhas 436-437 (Passo 3); Seção E linhas 509-510 (Prompt 2)
**Estado V2:** ❌ GAP
**Prioridade:** P0 (bloqueia qualidade de TODO slice futuro)
**Aplicabilidade:** USAR

**O que diz:** NÃO tratar primeira spec gerada pela IA como definitiva. Pedir ao agente: listar ambiguidades bloqueantes, inconsistências, decisões pendentes, itens faltantes numa checklist de aceitação. Objetivo: reduzir retrabalho **antes** de qualquer plano técnico. Prompt canônico: *"Audite a spec. Liste ambiguidades bloqueantes e faça perguntas objetivas até a spec ficar implementável."*

**Aplicação ao V2:** **não temos skill /clarify-slice.** É o item 8.1 do external-guides-action-plan. Bloqueia a qualidade de todo slice futuro.

---

## T14 — Autonomia graduada (começar simples, crescer com provas)

**Categoria:** FLUXO / CULTURA
**Fontes:** Seção D linha 258; Seção D passo 1 linha 326
**Estado V2:** 🟡 PARCIAL (começamos trancando tudo — o oposto de "simples")
**Prioridade:** P2
**Aplicabilidade:** AVALIAR

**O que diz:** Comece com o desenho mais simples possível. Só aumentar complexidade quando isso provar ganho real. Não tente "agente engenheiro full-stack autônomo" logo de cara. Comece com fluxos estreitos: corrigir bug com teste reprodutível, gerar pipeline de CI, criar feature CRUD simples, revisar PR.

**Aplicação ao V2:** tensão real com nossa abordagem (começar cheio de gates por causa do V1). Não é contradição necessariamente — nós **começamos com um fluxo simples** (um slice de cada vez) mas **dentro de um harness robusto**. Vale discutir no próximo ADR se estamos sendo "graduados" ou "prematuros".

---

## T15 — Definir "pronto" antes de escrever prompt

**Categoria:** FLUXO / VERIF
**Fontes:** Seção D linhas 328-330 (Passo 2)
**Estado V2:** ✅ JÁ TEMOS (P1 "gate objetivo precede opinião" + P7 "verificação antes de afirmação")
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Antes de pensar em prompt, definir a saída verificável: testes que precisam passar, checks de lint e typecheck, smoke test, screenshots de UI, logs esperados, critérios de segurança, definição de rollback. **Sem isso → agente otimiza pra "parece certo". Com isso → agente otimiza pra "provou que funciona".**

**Aplicação ao V2:** nosso P1+P7 + AC como teste executável (P2) já materializa isso. Confirmação de que o caminho está certo.

---

## T16 — Fatias de implementação (não big bang)

**Categoria:** FLUXO
**Fontes:** Seção E linhas 451-452 (Passo 8); Seção I linhas 745-747
**Estado V2:** ✅ JÁ TEMOS (slices)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Ciclos curtos: "implemente T3" → "rode testes" → "mostre o diff" → "corrija as falhas" → "atualize docs". Separar explorar → planejar → implementar → consolidar. Reduz over-engineering, mantém contexto limpo, melhora aderência à intenção.

**Aplicação ao V2:** nosso slice é exatamente isso. Confirmação.

---

# DOC — Documentos-chave e estrutura

## T17 — constitution.md (princípios permanentes do projeto)

**Categoria:** DOC
**Fontes:** Seção E linhas 430-431, 484-485
**Estado V2:** ✅ JÁ TEMOS
**Prioridade:** P0
**Aplicabilidade:** USAR

**O que diz:** Contém regras permanentes do projeto: qualidade, testes, segurança, performance, UX, naming, observabilidade, política de dependências. É a memória durável do processo.

**Aplicação ao V2:** temos `docs/constitution.md` com P1–P9 + R1–R12. **Ação possível:** revisar se cobre todos os tópicos recomendados (qualidade, testes, segurança, performance, UX, naming, observabilidade, política de dependências). Essa é uma auditoria natural do Bloco 9.3.

---

## T18 — AGENTS.md / CLAUDE.md curto e operacional

**Categoria:** DOC
**Fontes:** Seção D linha 314; Seção E linha 431, 487-488; Seção H linha 699
**Estado V2:** 🟡 PARCIAL (temos CLAUDE.md mas é longo — 241 linhas)
**Prioridade:** P2
**Aplicabilidade:** AVALIAR

**O que diz:** Deve ser **curto e operacional**. Conteúdo: layout do repo, comandos de run/build/test/lint, convenções, "do not rules", critérios de pronto, quando escalar para humano. OpenAI recomenda explicitamente manter **conciso** e empurrar detalhes para markdowns específicos. Relato de falha: "AGENTS.md gigante falhou na OpenAI".

**Aplicação ao V2:** nosso CLAUDE.md tem 241 linhas — pode ser mais longo que o ideal. Pode fazer sentido migrar partes (seção §9 sobre relock, por exemplo) para `docs/operations/relock-procedure.md` e manter o CLAUDE.md como mapa. **Isso é o gap 9.3 do external-guides-action-plan** — auditoria focada em sessão nova. Não decidir nesta sessão.

---

## T19 — spec.md por feature (SEM entrar na stack)

**Categoria:** DOC / FLUXO
**Fontes:** Seção E linhas 433-434 (Passo 2), 490-491; Seção F linhas 570-577; Seção I linhas 733-735
**Estado V2:** 🟡 PARCIAL (temos `docs/templates/spec.md`, mas nenhuma spec de feature preenchida)
**Prioridade:** P0 (bloqueia primeiro slice)
**Aplicabilidade:** USAR

**O que diz:** Em `spec.md`: problema, usuário, objetivo, fluxos, regras de negócio, critérios de aceitação, edge cases, non-goals, o que seria "pronto". **Princípio central: definir o "O QUÊ" e o "POR QUÊ" ANTES do "COMO". Nunca discutir stack nesta etapa.**

**Aplicação ao V2:** temos template em `docs/templates/spec.md` mas nenhum slice tem spec preenchida (pasta `specs/` vazia). Assim que Bloco 2 fechar e primeiro slice começar, usar este template. Importante: spec de feature ≠ PRD global do produto.

---

## T20 — plan.md (arquitetura, contratos, riscos — SÓ DEPOIS da spec clarificada)

**Categoria:** DOC / FLUXO
**Fontes:** Seção E linhas 439-440 (Passo 4), 493-494
**Estado V2:** 🟡 PARCIAL (temos sub-agent `architect` que gera plan.md)
**Prioridade:** P0
**Aplicabilidade:** USAR

**O que diz:** Com spec estável, aí sim entra `plan.md`: stack, arquitetura, módulos, fluxos, boundaries, data model, APIs, contratos, migração, observabilidade, riscos, quickstart. No fluxo Spec Kit, isso costuma gerar também `research.md`, `data-model.md`, `contracts/` (ex: openapi.yaml), `quickstart.md`.

**Aplicação ao V2:** nosso `architect` sub-agent gera `plan.md`. **Gaps:** não sei se gera também `research.md`, `data-model.md`, `contracts/openapi.yaml`, `quickstart.md` separadamente. **Auditar na sessão nova** (fica no meta-audit).

---

## T21 — research.md (perguntas específicas, não pesquisa ampla)

**Categoria:** DOC / FLUXO
**Fontes:** Seção E linhas 442-443 (Passo 5), 496-497
**Estado V2:** ❌ GAP
**Prioridade:** P2
**Aplicabilidade:** USAR

**O que diz:** NÃO "pesquise React" ou "pesquise .NET". Separar pesquisa de implementação e formular perguntas estreitas: "como implementar upload com retry nesse stack?", "qual o padrão de autenticação já usado neste repo?", "qual versão e breaking change afetam esse módulo?". Evita resolver o problema errado.

**Aplicação ao V2:** não temos `research.md` como artefato nomeado. Pode fazer sentido adicionar na próxima versão do template de slice.

---

## T22 — tasks.md (tarefas pequenas, ordenadas, com dependências)

**Categoria:** DOC / FLUXO
**Fontes:** Seção E linhas 448-449 (Passo 7), 499-500
**Estado V2:** ❌ GAP
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** `tasks.md` com tarefas executáveis, com dependências, paths, checkpoints, critério de pronto. No Spec Kit, tasks organizadas por user story, respeitam dependências, marcam paralelismo, indicam arquivos afetados, seguem ordem compatível com TDD. **É o artefato que impede IA de "atropelar" o repositório.**

**Aplicação ao V2:** nosso template de slice não tem `tasks.md` explícito. Adicionar ao fluxo depois do plan.md.

---

## T23 — Estrutura de diretório recomendada para SDD

**Categoria:** DOC
**Fontes:** Seção E linhas 459-478
**Estado V2:** 🟡 PARCIAL
**Prioridade:** P1
**Aplicabilidade:** ADAPTAR

**O que diz:**
```
project/
  AGENTS.md
  .spec/
    constitution.md
  specs/
    001-nome-da-feature/
      spec.md
      plan.md
      research.md
      data-model.md
      contracts/
        openapi.yaml
      quickstart.md
      tasks.md
  src/
  tests/
```
Nome exato das pastas é secundário — o que importa é separar bem: princípios permanentes, intenção funcional, decisão técnica, pesquisa, contratos, execução, teste.

**Aplicação ao V2:** temos `docs/constitution.md` (em vez de `.spec/`), `specs/` vazia, `docs/templates/spec.md` e `docs/templates/prd.md`. **Gap:** template de slice não prevê subdir `contracts/`, `data-model.md` separado. Expandir template de slice na próxima auditoria.

---

## T24 — 3 camadas de especificação (Product → Technical → Task)

**Categoria:** DOC / FLUXO
**Fontes:** Seção I linhas 731-737
**Estado V2:** ❌ GAP (não temos PRD consolidado nem Technical Spec global)
**Prioridade:** P0 (é onde estamos travados agora)
**Aplicabilidade:** USAR

**O que diz:** Organizar specs em camadas, do abstrato ao concreto:
1. **Product Spec (o "porquê")** — problema, público, objetivos. Documento CURTO em linguagem humana.
2. **Technical Spec (o "o quê")** — stack, arquitetura, modelos de dados, endpoints, fluxos de tela. Entidades, relacionamentos, regras de negócio.
3. **Task Specs (o "como", fatiado)** — cada feature/módulo vira spec isolada que IA executa de uma vez. **Se task é grande demais pra IA resolver numa única interação, quebrar menor.**

**Aplicação ao V2:**
- **Product Spec** = precisa ser consolidado a partir dos 8 docs em `docs/product/` (personas, journeys, mvp-scope, nfr, laboratorio-tipo, pricing-assumptions, glossary-pm, ideia-v1).
- **Technical Spec** = conteúdo do ADR-0001 + docs de arquitetura + stack.json + data model.
- **Task Specs** = nossos slices (`specs/NNN/`).

**Este é o mapa exato do que falta.**

---

# HARNESS — Infraestrutura do agente

## T25 — Repositório como memória oficial (o que não está no repo não existe)

**Categoria:** HARNESS / CULTURA
**Fontes:** Seção D linhas 256; Seção H linha 705
**Estado V2:** ✅ JÁ TEMOS (constitution + compliance + R1)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Para o agente, o que não está acessível no repositório praticamente "não existe". Conhecimento em Slack, call, Notion solto ou cabeça da equipe não escala para autonomia. Tudo precisa estar versionado no repo.

**Aplicação ao V2:** princípio cumprido pelo nosso R1 (fonte única) + compliance versionada. Confirmação.

---

## T26 — Invariantes mecânicos > regras vagas em prompt

**Categoria:** HARNESS
**Fontes:** Seção D linhas 257, 348-350 (Passo 7)
**Estado V2:** ✅ JÁ TEMOS (hooks, gates, hash-lock)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Arquitetura, bordas de domínio, direction of dependencies, naming rules, schemas, políticas e segurança precisam ser verificáveis por máquina. Coerência em codebase agent-generated veio de limites rígidos com enforcement mecânico, não de prompts longos sobre "bom gosto".

**Aplicação ao V2:** exatamente nosso Bloco 1 (hash-lock + sealed files + hooks determinísticos). Já aplicado.

---

## T27 — Sub-agents especializados (arquiteto, implementer, verifier, reviewer, QA)

**Categoria:** HARNESS
**Fontes:** Seção B linha 175; Seção D passo 8 linha 354
**Estado V2:** ✅ JÁ TEMOS (6 sub-agents)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Orquestrador + sub-agentes especializados: arquiteto (planeja), implementador (codifica), revisor (crítica adversarial), QA (testes). Inspirado em AutoGen/CrewAI/LangGraph.

**Aplicação ao V2:** temos `architect`, `ac-to-test`, `implementer`, `verifier`, `reviewer`, `guide-auditor` — mais granular que o documento sugere. Confirmação de que o padrão está certo.

---

## T28 — Sandbox isolado (container/devcontainer/worktree)

**Categoria:** HARNESS
**Fontes:** Seção B linha 184; Seção B linhas 212-215
**Estado V2:** ✅ JÁ TEMOS (R3 verifier em worktree descartável)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Agente pode errar sem quebrar nada. Opções: Devcontainer, E2B, Daytona. Hoje é onde o V2 já está.

**Aplicação ao V2:** R3 já aplica. Verifier e reviewer rodam em worktree descartável.

---

## T29 — Feedback loop curto (<30s ideal)

**Categoria:** HARNESS / OBS
**Fontes:** Seção B linha 185
**Estado V2:** ⚠️ AVALIAR (não medimos cycle time hoje)
**Prioridade:** P2
**Aplicabilidade:** USAR

**O que diz:** Hot reload, testes rápidos, type-check incremental. **Agente aprende pelo erro imediato.** Sem feedback loop curto, agente não consegue iterar direito.

**Aplicação ao V2:** é o item 8.5 do external-guides-action-plan. Não medimos hoje. Decidir métrica + alerta quando Bloco 3 (testes reais) fechar.

---

## T30 — Tools granulares, idempotentes, com mensagens de erro acionáveis

**Categoria:** HARNESS
**Fontes:** Seção B linha 198
**Estado V2:** 🟡 PARCIAL
**Prioridade:** P2
**Aplicabilidade:** AVALIAR

**O que diz:** Tools bem desenhadas = granulares, idempotentes, com mensagens de erro acionáveis (não só "erro 500").

**Aplicação ao V2:** nossos scripts em `scripts/` são granulares. Não sei se todos retornam mensagens de erro acionáveis. **Auditar na próxima iteração do harness.**

---

## T31 — Code execution + MCP, não tool spam

**Categoria:** HARNESS
**Fontes:** Seção D linhas 344-346 (Passo 6); Seção D linhas 340-342 (Passo 5)
**Estado V2:** ⚠️ AVALIAR
**Prioridade:** P2
**Aplicabilidade:** AVALIAR

**O que diz:** Interface ideal: agente escreve código pequeno que usa APIs MCP, filtra dados, devolve só o necessário ao contexto. Não expor "200 endpoints crus" — expor poucos tools consolidados ou filesystem navegável. Tool definitions demais incham contexto, aumentam custo e erro.

**Aplicação ao V2:** temos ADR-0002 MCP Policy e `allowed-mcps.txt`. **Auditoria item 9.2** do external-guides-action-plan: nosso ADR-0002 segue code-exec pattern ou é tool-spam disfarçado? Decidir em sessão nova.

---

## T32 — Skills como workflows especializados (progressive disclosure)

**Categoria:** HARNESS
**Fontes:** Seção D linhas 352-354 (Passo 8)
**Estado V2:** ✅ JÁ TEMOS (`.claude/skills/`)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Um skill por tarefa recorrente: create-feature, fix-bug, review-pr, debug-pipeline, deploy-service, cleanup-entropy. Cada skill diz: quando usar, quando NÃO usar, quais arquivos consultar, quais scripts rodar, quais artefatos produzir, qual o critério de sucesso. **Progressive disclosure:** carrega instruções completas só quando necessário.

**Aplicação ao V2:** temos skills `/new-slice`, `/verify-slice`, `/review-pr`, `/explain-slice`, `/decide-stack`, `/guide-check`, `/slice-report`, `/retrospective`. **Gap aparente:** não temos `/clarify-slice` (T13), `/fix-bug`, `/cleanup-entropy` — os dois últimos podem vir depois.

---

## T33 — Scripts determinísticos (agente NÃO inventa como roda testes)

**Categoria:** HARNESS
**Fontes:** Seção D linhas 320; Seção A estrutura linha 73
**Estado V2:** 🟡 PARCIAL
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** `scripts/` elimina ambiguidade. Agente deveria chamar `./scripts/test.sh`, `./scripts/evals.sh`, `./scripts/smoke.sh`. Reduz variância, aumenta auditabilidade.

**Aplicação ao V2:** temos muitos scripts (`new-slice.sh`, `verify-slice.sh`, `review-slice.sh`, `guide-check.sh`, `smoke-test-hooks.sh`, etc). **Gap:** `scripts/test.sh` e `scripts/evals.sh` não existem porque stack não decidida. Uma vez decidida, devem ser criados.

---

## T34 — Hooks pré/pós tool-use para enforçar regras

**Categoria:** HARNESS / VERIF
**Fontes:** Seção B linha 201; Seção C linha 236
**Estado V2:** ✅ JÁ TEMOS
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** PreToolUse/PostToolUse para enforçar regras mecanicamente. Exemplo: bloquear commit sem testes; após Edit/Write disparar lint + types + testes afetados; se falhar, agente não segue adiante.

**Aplicação ao V2:** Bloco 1 já aplica com 16 hooks selados. Bloco 3 vai tornar `post-edit-gate.sh` obrigatório com execução real de testes.

---

## T35 — Handoff limpo entre sessões (initializer pattern)

**Categoria:** HARNESS / FLUXO
**Fontes:** Seção D linhas 364-366 (Passo 11)
**Estado V2:** 🟡 PARCIAL
**Prioridade:** P2
**Aplicabilidade:** AVALIAR

**O que diz:** Para tarefas longas, não esperar que agente "lembre" magicamente. Padrão: ambiente inicial preparado por initializer; cada sessão deixa artefatos claros e estado limpo para próxima. Decisivo em tarefas que atravessam muitas janelas de contexto.

**Aplicação ao V2:** temos `session-start.sh` hook + slice-report.sh + retrospective.sh. Isso é quase o initializer pattern, mas pode ser reforçado. **Auditar** se o session-start atual carrega o contexto de slice ativo corretamente.

---

## T36 — Cleanup de entropia institucionalizado

**Categoria:** HARNESS
**Fontes:** Seção D linhas 368-370 (Passo 12); Seção D linha 259
**Estado V2:** 🟡 PARCIAL (temos skill `/guide-check` e sub-agent `guide-auditor`, mas uso ad-hoc)
**Prioridade:** P2
**Aplicabilidade:** USAR

**O que diz:** Entropia é inevitável. Agentes replicam padrões existentes, inclusive os ruins. Workflow recorrente para detectar desvio de padrão, atualizar score de qualidade, abrir PRs de refatoração, corrigir "slop" continuamente. "Garbage collection da codebase agent-first."

**Aplicação ao V2:** é o item 8.4 do external-guides-action-plan. Temos o sub-agent; falta agendar automaticamente (cron ou threshold-based).

---

## T37 — Estrutura ideal de repo (`.agents/skills/`, `scripts/`, `observability/`, `harness/`)

**Categoria:** HARNESS / DOC
**Fontes:** Seção D linhas 263-308
**Estado V2:** 🟡 PARCIAL
**Prioridade:** P2
**Aplicabilidade:** ADAPTAR

**O que diz:** Estrutura expandida com pastas dedicadas: `docs/{exec-plans/{active,completed}, quality}`, `.agents/skills/NOME/{SKILL.md, scripts/, references/}`, `scripts/{bootstrap, dev, lint, typecheck, test, smoke, evals}.sh`, `observability/{dashboards, alerts, queries}/`, `harness/{pipelines, templates, policies, environments}/`.

**Aplicação ao V2:** nosso repo já tem `docs/policies/`, `docs/compliance/`, `.claude/agents/`, `.claude/skills/`. **Gaps:** `observability/`, `docs/exec-plans/`, `scripts/evals.sh`, `harness/` dir. Algumas são futuras (8.7), outras podem ser só renaming.

---

## T38 — Harness "mínimo" recomendado para começar

**Categoria:** HARNESS
**Fontes:** Seção D linhas 382-384
**Estado V2:** ✅ JÁ TEMOS (e mais)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Harness mínimo viável: `AGENTS.md` curto + `docs/` como fonte de verdade + 3 ou 4 skills bem definidos + scripts determinísticos de validação + MCP para ferramentas externas + deploy sempre via PR/pipeline. Depois disso, aumentar autonomia só quando evals e observability mostrarem estabilidade.

**Aplicação ao V2:** já superamos o mínimo. Temos 8 skills, harness selado, hooks determinísticos. Confirmação de que não estamos aquém.

---

# VERIF — Verificação e gates

## T39 — AC = teste executável escrito ANTES do código (red → green → refactor)

**Categoria:** VERIF
**Fontes:** Seção C linha 234; Seção B linhas 171-173
**Estado V2:** ✅ JÁ TEMOS (P2 na constitution)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Sem teste falhando no início, task não começa. Se teste não passa, task não termina. É o oráculo operacional.

**Aplicação ao V2:** P2 da constitution + sub-agent `ac-to-test`. Bloco 3 item 3.2 (`ac-red-check.sh`) vai reforçar mecanicamente.

---

## T40 — Hook PostToolUse bloqueante (lint + types + testes afetados)

**Categoria:** VERIF
**Fontes:** Seção C linhas 236-237
**Estado V2:** 🟡 PARCIAL (hook existe, execução real depende de Bloco 3)
**Prioridade:** P0
**Aplicabilidade:** USAR

**O que diz:** Após Edit/Write, dispara lint + types + testes afetados automaticamente. Se falhar, agente não consegue seguir adiante — hook retorna erro e força nova iteração.

**Aplicação ao V2:** temos `post-edit-gate.sh`. Bloco 3 item 3.3 vai torná-lo obrigatório por arquivo (WARN → die para `src/**`). Completa este item.

---

## T41 — Verifier em contexto isolado, não vê a story

**Categoria:** VERIF
**Fontes:** Seção C linhas 238-239
**Estado V2:** ✅ JÁ TEMOS (R3 + verifier sub-agent)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Sub-agent verifier não vê a story — só recebe "rode esses testes, me diga o que passou/falhou" contra a codebase real. **Imune a confirmation bias.**

**Aplicação ao V2:** exatamente o que R3 faz. Confirmação.

---

## T42 — Mechanical diff check (AST/grep valida promessas da story)

**Categoria:** VERIF
**Fontes:** Seção C linhas 240-241
**Estado V2:** ⚠️ AVALIAR
**Prioridade:** P1
**Aplicabilidade:** AVALIAR

**O que diz:** Script lê a story, extrai a lista de arquivos/funções/endpoints prometidos e verifica existência via AST/grep. Se story diz "criei o endpoint POST /calibrations" e o arquivo não existe, falha automática.

**Aplicação ao V2:** temos `validate-review.sh` e `validate-verification.sh`. **Auditoria item 9.1** do external-guides-action-plan: esses scripts já fazem isso ou só validam schema JSON? Decidir em sessão nova.

---

## T43 — Fresh context pra review (revisor não vê narrativa do implementer)

**Categoria:** VERIF
**Fontes:** Seção C linha 242
**Estado V2:** ✅ JÁ TEMOS (R11 dual-verifier)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Revisor roda numa sessão nova, lê só o código + a spec, não vê a narrativa do implementador.

**Aplicação ao V2:** R11 + sub-agent `reviewer` isolado. Confirmação.

---

## T44 — "Done" definido por CI verde, não por fala do agente

**Categoria:** VERIF
**Fontes:** Seção C linha 243
**Estado V2:** ✅ JÁ TEMOS (R4 verification.json + P7)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** A frase "está pronto" deve ser **proibida** até o pipeline fechar.

**Aplicação ao V2:** R4 (JSON validado, não prosa) + P7 (verificação de fato antes de afirmação). Bloco 5 vai adicionar CI externo como juiz final.

---

## T45 — Quality gates automáticos pre-commit (lint, format, types, unit tests) — nunca --no-verify

**Categoria:** VERIF
**Fontes:** Seção B linha 186
**Estado V2:** ✅ JÁ TEMOS (`pre-commit-gate.sh` + R9)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Quality gates no pre-commit. **Nunca `--no-verify`.**

**Aplicação ao V2:** nosso `pre-commit-gate.sh` + R9 (zero bypass) aplicam isso. Confirmação.

---

## T46 — Dual-verifier / generator-evaluator pattern

**Categoria:** VERIF
**Fontes:** Seção D linha 358 (Passo 9)
**Estado V2:** ✅ JÁ TEMOS (R11)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Quando houver critérios claros, vale usar o padrão evaluator-optimizer ou generator-evaluator para refinamento iterativo.

**Aplicação ao V2:** R11 dual-verifier + reviewer independentes. Já aplicado.

---

## T47 — Eval suite / benchmark de regressão

**Categoria:** VERIF / OBS
**Fontes:** Seção B linha 202; Seção D linhas 356-358 (Passo 9)
**Estado V2:** ❌ GAP
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Suite de tarefas-benchmark para medir regressão quando muda prompt ou modelo. Sem eval, você acaba num ciclo reativo (descobre problema só em produção). Anthropic recomenda evals automatizadas durante o desenvolvimento.

**Aplicação ao V2:** é o item 8.2 do external-guides-action-plan. Depende de Bloco 3 + Bloco 5 para ser implementado.

---

## T48 — Guardrails (revisor adversarial + testes de segurança automatizados)

**Categoria:** VERIF
**Fontes:** Seção B linha 203
**Estado V2:** ✅ JÁ TEMOS (reviewer + `sanitize-input.sh` + `read-secrets-block.sh`)
**Prioridade:** P1
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Revisor adversarial + testes de segurança automatizados.

**Aplicação ao V2:** temos `reviewer` sub-agent + `sanitize-input.sh` (blocklist EN+PT + envelope XML) + `read-secrets-block.sh` hook. Confirmação.

---

# OBS — Observabilidade

## T49 — Logs estruturados + traces de agente (LangSmith, Langfuse)

**Categoria:** OBS
**Fontes:** Seção B linha 187
**Estado V2:** 🟡 PARCIAL (temos telemetria de harness, não de sub-agents)
**Prioridade:** P2
**Aplicabilidade:** ADAPTAR

**O que diz:** Observabilidade: logs estruturados, traces do agente (LangSmith, Langfuse), replay de sessões.

**Aplicação ao V2:** temos telemetria append-only hash-chain do harness (Bloco 1). **Gap:** não temos traces estruturados dos sub-agents trabalhando (inputs, outputs, tool calls, decisões). É o item 8.3 do external-guides-action-plan. Depende de Bloco 5.

---

## T50 — Replay de sessões

**Categoria:** OBS
**Fontes:** Seção B linha 187
**Estado V2:** ❌ GAP
**Prioridade:** P3
**Aplicabilidade:** AVALIAR

**O que diz:** Capacidade de re-executar sessão anterior pra debugging.

**Aplicação ao V2:** não temos. Valor marginal vs custo de implementar — avaliar depois de 8.3 (traces) estar pronto.

---

## T51 — Observability/ no repo (dashboards, alerts, queries versionados)

**Categoria:** OBS / DOC
**Fontes:** Seção D linhas 298-301
**Estado V2:** ❌ GAP
**Prioridade:** P3 (pós-primeiro deploy)
**Aplicabilidade:** USAR

**O que diz:** Pasta `observability/{dashboards,alerts,queries}/` com dashboards, alertas e queries versionados junto com o código.

**Aplicação ao V2:** é o item 8.7 do external-guides-action-plan. Depende de primeiro deploy em produção. Crítico para trilha de compliance (LGPD + metrologia + fiscal exigem auditabilidade de alertas).

---

## T52 — Verificação contínua com ML (canary + rollback automático)

**Categoria:** OBS / ENTREGA
**Fontes:** Seção G linhas 668-670
**Estado V2:** ❌ GAP (futuro)
**Prioridade:** P3 (pós-primeiro deploy em produção)
**Aplicabilidade:** AVALIAR

**O que diz:** Deploy Canary (10% usuários inicialmente). ML cria baseline do comportamento normal. Deploy gradativo automático. Abortar se detectar degradação de performance.

**Aplicação ao V2:** plataforma Harness.io oferece isso nativo. Alternativas open-source: Argo Rollouts, Flagger. Decisão fica para pós-produção.

---

# ENTREGA — CI/CD e deploy

## T53 — Git disciplinado (branch por task, PR pequeno, CI verde antes do merge)

**Categoria:** ENTREGA
**Fontes:** Seção B linha 190
**Estado V2:** 🟡 PARCIAL (temos fluxo de PR mas CI externo ainda não operacional)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Branch por task, PR pequeno, CI verde obrigatório antes do merge.

**Aplicação ao V2:** Bloco 5 do tracker original materializa isso (CI GitHub Action + ruleset de main endurecido).

---

## T54 — PR + pipeline + GitOps

**Categoria:** ENTREGA
**Fontes:** Seção D linhas 360-362 (Passo 10)
**Estado V2:** ❌ GAP (Bloco 5 pendente)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Mudança do agente vira PR curta com checks, revisão e deploy controlado. Agents rodam como passos nativos de pipeline. GitOps PR pipelines mantêm Git como fonte de verdade com trilha de auditoria.

**Aplicação ao V2:** Bloco 5 do tracker original. PR → CI → merge é o caminho obrigatório.

---

## T55 — Feature flags (decouple deploy/release)

**Categoria:** ENTREGA
**Fontes:** Seção G linhas 634-636; linhas 664-666
**Estado V2:** ❌ GAP (futuro)
**Prioridade:** P2 (crítico pós-produção)
**Aplicabilidade:** USAR

**O que diz:** Deploy do código o tempo todo; ligar funcionalidade (release) só quando atestar que está seguro. Permite testar em produção com segurança.

**Aplicação ao V2:** é o item 8.8 do external-guides-action-plan. Crítico para SaaS multi-tenant com compliance — poder desligar feature sem rollback = diferença entre incidente e toggle.

---

## T56 — Pipeline as Code (YAML) + GitOps

**Categoria:** ENTREGA
**Fontes:** Seção G linha 634
**Estado V2:** 🟡 PARCIAL (Bloco 5 vai materializar)
**Prioridade:** P1
**Aplicabilidade:** USAR

**O que diz:** Tudo no Git, desde infra até deploy. IA lê/gera arquivos estruturados melhor que interfaces visuais.

**Aplicação ao V2:** Bloco 5 item 5.1 (`.github/workflows/ci.yml`).

---

## T57 — Plataforma Harness.io (produto comercial) como control plane

**Categoria:** ENTREGA
**Fontes:** Seção D linhas 372-378; Seção G completa
**Estado V2:** ⚠️ AVALIAR
**Prioridade:** SKIP (provavelmente)
**Aplicabilidade:** DESCARTAR (provavelmente)

**O que diz:** Harness.io como control plane: editor → lê AGENTS.md + docs/ + skills/ → usa Harness MCP Server → abre PR → Harness Agents rodam pipelines (build/test/security/deploy) → GitOps PR pipeline promove via Git → observabilidade e feedback alimentam novos skills. **Harness não substitui harness engineering; fornece peças.**

**Aplicação ao V2:** nossa direção é **GitHub Actions + scripts próprios**, não Harness.io como plataforma comercial. Mesmo que o documento externo recomende, não é nossa escolha. **Descartar conscientemente** salvo mudança de direção explícita do PM.

---

# CULTURA — Mentalidade e papéis

## T58 — Humano = arquiteto/PM, IA = digitadora

**Categoria:** CULTURA
**Fontes:** Seção F linhas 548-549; Seção I linhas 757
**Estado V2:** ✅ JÁ TEMOS (§3.1 CLAUDE.md — humano = PM)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Seu esforço mental sai da sintaxe (como fazer um loop, estruturar endpoint) e vai para lógica de negócios, regras de domínio, arquitetura. Escreve specs, revisa outputs, toma decisões de design. **Valor humano = julgamento, não digitação.**

**Aplicação ao V2:** nosso modelo é ainda mais estrito: humano = PM (não arquiteto técnico). R12 tradutor traduz tudo pra linguagem de produto. Mais restritivo que a recomendação, por design.

---

## T59 — Ansiedade de ver código rodando rápido = dívida técnica imediata

**Categoria:** CULTURA
**Fontes:** Seção F linhas 608-612
**Estado V2:** ✅ JÁ TEMOS (Direção A + R9)
**Prioridade:** P0
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Maior erro é ansiedade de ver código rodando rápido. Prompts vagos como "Crie uma tela de ordem de serviço" geram dívida técnica imediata (IA assume decisões de negócio que deveriam ser suas). SDD força pensar devagar. Recompensa: refatoração que antes levava dias acontece em minutos com alta precisão.

**Aplicação ao V2:** exatamente o raciocínio por trás da Direção A. Confirmação.

---

## T60 — Especificidade mata ambiguidade

**Categoria:** CULTURA
**Fontes:** Seção I linha 755
**Estado V2:** ✅ JÁ TEMOS (P1 + P2 + constitution)
**Prioridade:** P1
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** "Faz um login" gera lixo. "Faz um login com email/senha, validação client-side, rate limiting de 5 tentativas, retorno de JWT com refresh token e mensagens de erro em português" gera algo utilizável.

**Aplicação ao V2:** P2 AC = teste executável materializa isso.

---

## T61 — Iteração > perfeição (não tentar spec perfeita de primeira)

**Categoria:** CULTURA
**Fontes:** Seção I linha 759
**Estado V2:** ❌ GAP (nosso modelo atual força perfeccionismo no harness)
**Prioridade:** P2
**Aplicabilidade:** ADAPTAR

**O que diz:** Escrever → executar → ver resultado → refinar. Ciclo: spec → execução → feedback → spec refinada.

**Aplicação ao V2:** tensão com nosso approach "selar o harness primeiro". Não é conflito — podemos aplicar iteração > perfeição **dentro do harness robusto**. Ponto a revisitar quando primeiro slice rodar.

---

## T62 — Anti-padrões: os 6 pecados do SDD com IA

**Categoria:** CULTURA
**Fontes:** Seção E linhas 525-527
**Estado V2:** ✅ JÁ TEMOS (constitution cobre todos)
**Prioridade:** P1
**Aplicabilidade:** USAR (já aplicado)

**O que diz:** Os 6 anti-padrões convergentes nos guias:
1. Pedir "faz um app X" e soltar o volante
2. Misturar requisito e stack cedo demais
3. Deixar a intenção só no histórico do chat
4. Fazer pesquisa ampla e vaga
5. Pular verificação
6. Aceitar primeira saída da IA como especificação final

**Riscos associados:** código que parece certo mas não atende à intenção, contexto inchado, agentes "over-eager" adicionando coisas não pedidas, falta de mecanismos de auto-verificação.

**Aplicação ao V2:** nossa constitution + R1-R12 + sub-agents + hooks cobrem todos. Bom checklist para auto-auditoria.

---

# Apêndice A — Temas rejeitados conscientemente

## R01 — `.cursorrules` como fonte de regras do agente

**Fonte:** Seção F linhas 566-568
**Motivo da rejeição:** viola **R1** (fonte única de instrução). Nossa fonte é CLAUDE.md + docs/constitution.md.
**Nota:** a metodologia SDD em si, que a Seção F propõe, é ótima e está absorvida em outros itens. O que rejeitamos é especificamente o arquivo `.cursorrules`.

## R02 — Adotar plataforma Harness.io como control plane

**Fonte:** Seção D linhas 372-378; Seção G completa
**Motivo da rejeição:** direção arquitetural é GitHub Actions + scripts próprios, não SaaS comercial. Reavaliar apenas se aparecer ganho desproporcional.

## R03 — Modelo "humano = desenvolvedor/arquiteto técnico"

**Fonte:** implícito em várias seções (B, D, E, F, I)
**Motivo da rejeição:** nosso modelo é mais restritivo — humano = PM (CLAUDE.md §3.1). Recomendações que pressupõem humano técnico precisam passar pelo tradutor R12 antes de chegar ao PM.

---

# Apêndice B — Mapa de rastreabilidade (tema → fonte bruta)

Cada tema T0N consolida múltiplos aprendizados brutos. Para auditoria futura:

| Tema | Ks brutos consolidados | Seções do documento |
|---|---|---|
| T01 | K001-K005 | A (linhas 1-15) |
| T02 | K006-K009 | A (17-21) |
| T03 | K022, K023 | A (83-93) |
| T04 | K018, K019, K082, K083, K084 | A (62-79), D (263-308) |
| T05 | K029, K030, K034, K035, K036 | A (138-161) |
| T06 | K025, K026, K027 | A (99-126) |
| T07 | K037, K042, K051, K052, K075, K076, K188, K189, K190 | B, D, H |
| T08 | K106-K114, K124-K132, K207-K211 | E (405-455), I (739-749) |
| T09 | K121, K122, K123 | E (420-426) |
| T10 | K039 | B |
| T11 | K040 | B |
| T12 | K115-K118 | E (414-416), D (255) |
| T13 | K126, K143 | E (436-437) |
| T14 | K080, K089 | D (258, 326) |
| T15 | K091 | D (328-330) |
| T16 | K131, K210 | E (451-452), I (745-747) |
| T17 | K124, K136 | E (430-431, 484-485) |
| T18 | K077, K085, K137, K195 | D (255, 314), E (431, 487-488), H (699) |
| T19 | K125, K138, K164, K165, K206 | E (433-434, 490-491), F (570-577), I (733-735) |
| T20 | K127, K139 | E (439-440, 493-494) |
| T21 | K128, K140 | E (442-443, 496-497) |
| T22 | K130, K141 | E (448-449, 499-500) |
| T23 | K133, K134 | E (459-478) |
| T24 | K206-K211 | I (731-737) |
| T25 | K078, K198 | D (256), H (705) |
| T26 | K079, K096 | D (257, 348-350) |
| T27 | K041, K097 | B (175), D (354) |
| T28 | K044, K060 | B (184, 212-215) |
| T29 | K045 | B (185) |
| T30 | K053 | B (198) |
| T31 | K094, K095 | D (340-346) |
| T32 | K097, K087 | D (352-354), D (318) |
| T33 | K088 | D (320) |
| T34 | K055, K066 | B (201), C (236) |
| T35 | K100 | D (364-366) |
| T36 | K081, K101 | D (259, 368-370) |
| T37 | K082 | D (263-308) |
| T38 | K105 | D (382-384) |
| T39 | K065, K038 | C (234), B (171) |
| T40 | K066 | C (236-237) |
| T41 | K067 | C (238-239) |
| T42 | K068 | C (240-241) |
| T43 | K069 | C (242) |
| T44 | K070 | C (243) |
| T45 | K046 | B (186) |
| T46 | K098 | D (358) |
| T47 | K056, K098 | B (202), D (356-358) |
| T48 | K057 | B (203) |
| T49 | K047, K061 | B (187) |
| T50 | K047 | B (187) |
| T51 | K082 | D (298-301) |
| T52 | K187 | G (668-670) |
| T53 | K050 | B (190) |
| T54 | K099 | D (360-362) |
| T55 | K178, K186 | G (634-636, 664-666) |
| T56 | K176 | G (634) |
| T57 | K102, K103 | D (372-378) |
| T58 | K158, K214 | F (548-549), I (757) |
| T59 | K169, K170, K171 | F (608-612) |
| T60 | K213 | I (755) |
| T61 | K215 | I (759) |
| T62 | K148-K154 | E (525-527) |

---

# Conclusão

**O que este arquivo É:**
- Inventário completo e linha por linha do documento externo, reorganizado em 62 temas com rastreabilidade para as linhas originais.
- Classificação honesta de cada tema contra o estado atual do harness V2.
- Separação explícita do que já temos (18), do que falta (30), do que é parcial (9), do que precisa auditar (4) e do que rejeitamos (3).

**O que este arquivo NÃO É:**
- Um plano de ação. Para isso existe o `external-guides-action-plan.md`.
- Uma decisão do PM. Todas as classificações de prioridade são **sugestões**, a ser revisadas pelo PM.
- A palavra final sobre nada. Memórias e recomendações podem estar desatualizadas em meses.

**Próximo passo possível (a decidir pelo PM):**
- Revisar as 62 classificações e ajustar prioridades que não fazem sentido.
- Alinhar o `external-guides-action-plan.md` com este inventário (vários temas aqui apontam para itens 8.x e 9.x que já estão planejados).
- Usar a seção **Apêndice A (rejeitados)** para documentar explicitamente no `CLAUDE.md` ou `constitution.md` por que descartamos `.cursorrules` e Harness.io.
