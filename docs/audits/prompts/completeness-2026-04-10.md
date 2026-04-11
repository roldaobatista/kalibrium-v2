# Auditoria externa — Completude do ambiente de desenvolvimento do Kalibrium V2

## 1. Identificação do pedido

Este pedido vem do **Product Manager** (PM) do Kalibrium V2. O PM assumirá o projeto de pé à cabeça como único humano ativo. Ele **não é desenvolvedor, não toma decisões técnicas sem recomendação, não revisa código**. Todo o trabalho técnico é feito por agentes de IA (Claude Code + harness disciplinado).

Você é um auditor externo independente. Não conhece este projeto. Não tem obrigação de concordar com nada. Sua opinião honesta e rigorosa é o valor que se espera de você.

## 2. Contexto do projeto

- **Produto:** Kalibrium V2, SaaS B2B brasileiro para gestão de laboratórios de calibração e metrologia
- **Domínio regulado:** setor sujeito a INMETRO, ISO/IEC 17025, emissão fiscal (NF-e/NFS-e multi-UF), REP-P (Portaria 671/2021), assinatura ICP-Brasil, LGPD, reforma tributária brasileira 2026-2033
- **Modelo operacional:** 1 humano (PM, não-técnico) + N sub-agentes de IA construindo o software, com contextos isolados, hooks de enforcement, dual-verifier, golden tests de compliance
- **Status:** V1 do produto existiu, foi abandonado por falta de gates de qualidade. V2 é recomeço do zero com harness disciplinado
- **Fase atual:** "Bloco 1" do plano de endurecimento foi completado (harness selado contra auto-modificação: hash-locks, sandbox fail-closed, sealed files, append-only telemetry, allowlist de autores git, prompt-injection sanitizer). Próximos blocos do plano: decidir stack, gates reais de teste, tradutor PM, CI externo, defesas adicionais, re-auditoria
- **Onde o projeto vai rodar:** VPS Hostinger (single box Linux, sem managed services)
- **Orçamento operacional implícito:** pequeno (PM individual + consultores pontuais de metrologia/fiscal)

## 3. Sua missão

Auditar a **completude do ambiente de desenvolvimento e governança do projeto** no estado atual. Especificamente: identificar **o que está faltando** para que este projeto tenha chance real de entregar um SaaS regulado brasileiro em produção, assumindo que:

1. O enforcement técnico do harness (hooks, sandbox, dual-verifier, golden tests) será implementado conforme o plano já em andamento
2. O humano PM não vai fazer code review, arquitetura ou threat modeling sozinho — essas coisas precisam ser **produzidas por agentes a partir de artefatos e processos que existam no repositório**

A pergunta central que você deve responder é:

> **"Para um ambiente de desenvolvimento 100% por agentes de IA construir um SaaS regulado com um PM não-técnico como único humano, quais documentos, decisões, processos, estruturas e artefatos precisam estar prontos no repositório — e quais desses não estão?"**

## 4. Dimensões obrigatórias de avaliação

Cubra **todas** as seções abaixo. Para cada uma, responda: "está presente", "está parcialmente presente" ou "está ausente"; cite arquivos específicos quando existirem; explique o que faltaria para estar completo.

### A. Documentação fundacional de produto
O que um projeto SaaS B2B regulado precisa ter documentado **antes** de escrever código, e o que existe neste repositório nesse sentido. Inclua (mas não se limite a): PRD ou especificação do MVP, personas, jornadas do usuário, requisitos funcionais e não-funcionais, escopo do MVP versus fora-do-MVP, roadmap de versões, modelo de negócio/pricing.

### B. Decisões arquiteturais fundacionais
Decisões que precedem a decisão de stack e que precisam estar registradas (geralmente em ADRs): modelo arquitetural (monolito/modular/microserviços), estratégia de multi-tenancy, modalidade de cliente (web-only/PWA/app nativo/híbrido), modelo de dados inicial, estratégia de deployment, ambientes (dev/staging/prod), estratégia de autenticação e autorização. **Observe:** o projeto tem planejado um ADR-0001 de stack. Avalie se isso é suficiente ou se precedem ADRs conceitualmente anteriores.

### C. Governança de segurança e compliance
Threat model do produto, plano de resposta a incidentes, base legal de tratamento LGPD, DPIA, contratos de operador, política de retenção, plano de backup e disaster recovery (RPO/RTO), estratégia de criptografia em repouso e em trânsito, gestão de segredos, plano de auditoria de segurança recorrente, política de dependências e supply chain.

### D. Processos operacionais
Pipeline de deploy (manual/automatizado), estratégia de rollback, monitoramento e observabilidade (logs, métricas, alertas, SLO/SLI), pager e escalação, plano de suporte aos primeiros clientes, canal de relato de bugs, plano de atualização legislativa contínua (dado que o modelo LLM tem cutoff fixo e legislação fiscal brasileira muda via diário oficial).

### E. Artefatos de domínio regulado
O projeto reconhece que metrologia, fiscal, REP-P, ICP-Brasil e LGPD são domínios onde IA sozinha erra com consequência jurídica. Há planejamento de consultores externos e golden tests. Avalie se:
- A rastreabilidade normativa dos cálculos está endereçada (não só "teste passa", mas "está documentado que este cálculo segue tal norma, tal seção, tal data")
- O processo de revalidação periódica da base legal está definido
- Há plano para módulos que **não** podem ser implementados por IA mesmo com consultor (ex.: integração direta com SEFAZ via webservice, assinatura HSM ICP-Brasil A3)
- O glossário de domínio existente é suficiente como autoridade canônica para o sub-agent verificador

### F. Estrutura do repositório e organização
A árvore de arquivos suporta trabalho por agentes de IA? Convenções de nomeação, separação de artefatos (docs, specs, code, tests, fixtures, ADRs, incidents), catalogação de decisões, rastreabilidade entre spec → teste → código → ADR → incident. Identifique o que a estrutura atual facilita e o que ela dificulta.

### G. Papéis, responsabilidades e limites
Quem (ou qual sub-agente) é responsável por cada tipo de decisão? Há definição clara de:
- O que o PM decide (produto, escopo, trade-offs)
- O que um agente pode decidir sozinho
- O que exige escalação a consultor humano externo
- O que precisa de dois agentes concordando
- Como o PM delega responsabilidade e onde delegação é proibida (pausa dura)

### H. Tradução técnico-para-produto
O PM é não-técnico. Sempre que um agente precisa reportar algo ao PM, como isso é feito? Há:
- Vocabulário/glossário de produto (palavras permitidas e proibidas)
- Templates de relatório para decisão do PM (formato de apresentação, não só vocabulário)
- Mecanismo de "pergunta única" (em vez de listar 10 problemas, oferecer uma decisão clara)
- Fallback para quando a tradução é impossível (ex.: escalar para consultor humano)

### I. Governança financeira e sustentabilidade
Custo mensal estimado de infra (VPS Hostinger), custo de consumo de tokens dos agentes de IA, custo de consultoria externa, custo hora/mês do PM, plano para justificar a operação. Isto afeta decisões técnicas diretas (ex.: uma stack que exige 32GB RAM em VPS Hostinger pode tornar o projeto financeiramente inviável).

### J. Plano de evolução do próprio harness
O harness (hooks, sub-agents, skills, constitution) evolui ao longo do tempo. Como? Quem controla mudanças de R1-R12 ao longo do tempo? Como versionar constitution? Há retrospectiva obrigatória? Há processo para propor nova regra ou revogar regra existente?

### K. Outros pontos que você considerar relevantes
Use este espaço para levantar qualquer dimensão que você considere importante e que não caiu em A-J. Sua bagagem de auditor pode cobrir áreas que o PM e o autor do plano nem cogitaram. **Pense em bases**: o que um CTO experiente pediria antes de aceitar liderar este projeto? O que um advogado tributarista exigiria antes de acreditar na conformidade? O que uma equipe de SRE levantaria antes de aceitar operar este sistema em produção?

## 5. FORA DE ESCOPO (não reavalie)

Não reavalie o **enforcement técnico do harness** nas seguintes áreas — estas foram auditadas por três outros auditores em 2026-04-10 e estão endereçadas no "Bloco 1" (recém concluído) ou planejadas nos blocos seguintes:

- Selos de hash em `settings.json`, hooks e telemetria
- Sandbox do verifier/reviewer via `CLAUDE_AGENT_NAME` + detecção de worktree
- Symlink escape, path traversal, realpath
- Fail-closed quando variável de ambiente ausente
- Prompt injection via `spec.md` ou glossário (já tem `sanitize-input.sh`)
- Allowlist de autores git e baseline histórico
- Append-only telemetria com hash-chain
- CI + GitHub Action + auto-reviewer (planejado, Bloco 5)
- Dual-verifier (R11) e tradutor R12 como mecanismo técnico (embora A-K podem tratar da qualidade do conteúdo que passa por eles)

Isso não significa "não pode mencionar". Significa: sua contribuição única está no que **não foi auditado** antes, que é **governança de produto, completude de fundações conceituais, processos operacionais, compliance de domínio, sustentabilidade financeira e organização geral do projeto**.

## 6. Materiais de entrada obrigatórios

Antes de escrever qualquer parágrafo da auditoria, você deve ler (ou pedir acesso, se estiver em ambiente sem filesystem):

1. `CLAUDE.md` — instruções operacionais do projeto
2. `docs/constitution.md` — P1-P9 + R1-R12 + DoD
3. `docs/audits/meta-audit-2026-04-10.md` — consolidação das 3 auditorias anteriores
4. `docs/audits/meta-audit-2026-04-10-action-plan.md` — plano de 7 blocos
5. `docs/audits/progress/meta-audit-tracker.md` — estado atual do progresso
6. `docs/reference/ideia-v1.md` — visão de produto congelada (brainstorm do V1)
7. `docs/glossary-domain.md` — termos do domínio regulado
8. `docs/adr/` — todos os ADRs existentes
9. `docs/incidents/` — histórico de incidentes
10. Árvore completa do repositório (`tree` ou equivalente) — para entender estrutura
11. `README.md` do projeto (se existir)
12. `docs/decisions/pm-decision-meta-audit-2026-04-10.md` — decisões do PM

Se algum arquivo da lista não existir, registre isso explicitamente — a ausência é parte do diagnóstico.

## 7. Formato de saída (siga rigorosamente)

Sua resposta **deve** seguir este formato exato, com estas seções nesta ordem, para permitir comparação lado a lado com outras auditorias:

```
# Auditoria externa de completude — [seu nome/modelo]

**Data:** [data]
**Auditor:** [modelo específico, ex.: GPT-5 Codex]
**Duração da auditoria:** [aproximada, em minutos]
**Materiais lidos:** [lista dos arquivos que você efetivamente leu]

## A. Documentação fundacional de produto
[presente / parcial / ausente — com citação de arquivos]
[o que está faltando e por quê é relevante]
[recomendação específica]

## B. Decisões arquiteturais fundacionais
[...]

## C. Governança de segurança e compliance
[...]

## D. Processos operacionais
[...]

## E. Artefatos de domínio regulado
[...]

## F. Estrutura do repositório e organização
[...]

## G. Papéis, responsabilidades e limites
[...]

## H. Tradução técnico-para-produto
[...]

## I. Governança financeira e sustentabilidade
[...]

## J. Plano de evolução do próprio harness
[...]

## K. Outros pontos relevantes
[itens que você encontrou fora das dimensões acima]

## L. 5 maiores ameaças à entrega do MVP em produção
(não à segurança do harness — à entrega do produto)
Formato por ameaça:
### Ameaça #N: [título]
**Probabilidade:** [baixa/média/alta]
**Impacto:** [baixo/médio/alto]
**Descrição:** [o cenário concreto]
**Por que o plano atual não cobre:** [evidência específica]
**Mitigação proposta:** [ação concreta]

## M. Veredito binário

- **O projeto está pronto para avançar para o "Bloco 2" (decisão de stack) no estado atual?** [sim / não / com-condições]
- **Quais são as mudanças bloqueantes (se houver) antes de decidir stack?** [lista numerada]
- **Quais artefatos de fundação de produto deveriam existir antes de qualquer commit que toque código-fonte de produto?** [lista numerada]

## N. 10 sugestões acionáveis em ordem de impacto
Cada sugestão deve ter:
**[esforço: baixo/médio/alto] Título da sugestão**
- **Por quê:** justificativa objetiva
- **Como:** ação concreta citando arquivo e/ou processo específico

## O. Comentário livre (opcional, máximo 300 palavras)
Qualquer observação que não se encaixou nas seções acima mas que você considera importante transmitir.
```

## 8. Critérios de qualidade da sua auditoria

A auditoria será lida em paralelo com outras duas auditorias de outros modelos. Você será julgado por:

1. **Rigor de leitura** — citar arquivos e linhas específicas. Afirmações sem evidência valem menos.
2. **Honestidade** — se o plano de 7 blocos atual já endereça algo, reconheça. Não invente gap para parecer útil.
3. **Profundidade** — um auditor que só repete o óbvio vale menos. Queremos os achados que **só você enxerga**.
4. **Acionabilidade** — recomendações do tipo "melhorar X" sem "como fazer" valem menos.
5. **Cobertura** — seções A-K são obrigatórias. Pular qualquer uma penaliza.
6. **Independência** — não tente adivinhar o que o PM quer ouvir. O valor está em pontos que ele não pensou.

## 9. Entregável

Um único arquivo Markdown seguindo o formato da seção 7, em português (ou em inglês se você operar melhor assim — o PM lê ambos). Entrega em uma única mensagem. Sem prefácio pedindo esclarecimento antes de auditar — você tem tudo que precisa na seção 6. Se algum arquivo faltar, registre isso dentro da auditoria como um findings.

---

Vá com honestidade cirúrgica. Este projeto vai chegar ao cliente com ou sem a sua contribuição; a qualidade do seu trabalho aqui afeta diretamente a chance de o produto não prejudicar laboratórios reais e usuários finais brasileiros. O PM leva isto a sério. Você deveria também.
