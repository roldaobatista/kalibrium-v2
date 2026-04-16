# Fluxo Completo da Fabrica de Software — Visao Geral para o PM

**O que e isso:** Este documento descreve como os 12 agentes trabalham juntos para transformar uma ideia sua em software funcionando. Pense neles como uma equipe de 12 profissionais especializados, cada um com seu papel.

---

## A equipe (12 profissionais)

### Os 9 especialistas (pensam e avaliam)
| Quem | Papel na equipe | Quando voce vai perceber o trabalho dele |
|------|----------------|------------------------------------------|
| **Especialista de Produto** | O analista que entende o negocio. Traduz o que voce quer em requisitos claros. Valida se o que foi construido realmente atende o usuario. | Na descoberta (entrevista com voce) e na validacao final (confere se o produto faz o que deveria) |
| **UX Designer** | O designer que cria as telas e fluxos. Garante que o sistema e facil de usar, bonito e acessivel. | Nos wireframes e design system que voce vai aprovar antes de qualquer tela ser construida |
| **Especialista de Arquitetura** | O engenheiro-chefe. Decide como as pecas do sistema se encaixam. Projeta APIs e planos tecnicos. | Quando voce aprovar decisoes tecnicas (traduzidas em linguagem simples) |
| **Especialista de Dados** | O DBA senior. Cuida do banco de dados, garante que dados de um cliente nunca vazem para outro, e que tudo e rapido. | Invisivel para voce — trabalha nos bastidores garantindo integridade dos dados |
| **Especialista de Seguranca** | O guardiao. Procura vulnerabilidades, garante LGPD, protege dados sensiveis. Revisa o trabalho de TODOS os outros. | Quando um problema de seguranca for encontrado e precisar de decisao sua (ex: "aceitar risco X no MVP?") |
| **Especialista de QA** | O fiscal. Valida qualidade de tudo: requisitos, codigo, testes. E o mais exigente da equipe — nao deixa nada passar. | Nos gates de qualidade — ele e quem aprova ou reprova cada entrega |
| **Especialista de DevOps** | O engenheiro de infraestrutura. Monta o pipeline que leva o codigo do computador para o servidor. | Na configuracao do CI/CD e deploy |
| **Especialista de Observabilidade** | O vigia. Garante que o sistema avisa quando algo esta errado — logs, alertas, metricas de saude. | Quando o sistema em producao tiver monitoramento funcionando |
| **Especialista de Integracao** | O diplomata. Cuida das conexoes com sistemas externos — nota fiscal, pagamento, email, webhooks. | Quando o Kalibrium precisar conversar com sistemas de fora |

### Os 3 operacionais (executam e coordenam)
| Quem | Papel na equipe | Quando voce vai perceber o trabalho dele |
|------|----------------|------------------------------------------|
| **Construtor** | O programador. E o unico que escreve codigo. Escreve testes, implementa funcionalidades, corrige problemas. | Voce nao ve o trabalho dele diretamente — ve o resultado nos testes passando e funcionalidades funcionando |
| **Governanca** | O auditor-chefe. Faz a validacao final com 2 IAs diferentes (Claude + GPT-5), analisa retrospectivas, melhora o processo continuamente. | Na aprovacao final de cada entrega e nas retrospectivas |
| **Orquestrador** | O gerente de projeto. Coordena todos os outros 11. Decide quem trabalha quando, gerencia o fluxo, e o unico que fala com voce. | Em toda interacao — eu sou o orquestrador |

---

## O fluxo completo (6 fases)

Imagine que voce quer uma nova funcionalidade no Kalibrium. Esse e o caminho que ela percorre do "eu quero" ate "esta no ar":

---

### FASE A — DESCOBERTA
**Objetivo:** Entender exatamente o que voce quer e por que.
**Duracao tipica:** 1 sessao
**Sua participacao:** Alta (responde perguntas, valida documentos)

```
VOCE: "Quero que o Kalibrium faca X"
                    ↓
        ┌───────────────────────┐
        │  Especialista de      │
        │  Produto (discovery)  │
        │                       │
        │  Faz 10 perguntas     │
        │  estrategicas para    │
        │  entender o problema  │
        │  real, nao so o       │
        │  pedido superficial.  │
        └───────────┬───────────┘
                    ↓
            Produz:
            • Glossario (vocabulario do negocio)
            • Modelo de dominio (entidades e relacoes)
            • Requisitos nao-funcionais (performance, seguranca, etc)
            • Riscos e suposicoes
                    ↓
        ┌───────────────────────┐
        │  UX Designer          │
        │  (research)           │
        │                       │
        │  Pesquisa quem sao    │
        │  os usuarios, como    │
        │  trabalham, o que     │
        │  esperam do sistema.  │
        └───────────┬───────────┘
                    ↓
            Produz:
            • Personas (perfis de usuario)
            • Jornadas de usuario (passo a passo do que fazem)
            • Benchmarks (como outros sistemas resolvem)
                    ↓
    ╔═══════════════════════════════════╗
    ║  CROSS-REVIEW (conferencia)       ║
    ║                                   ║
    ║  QA confere: requisitos sao       ║
    ║  testaveis? Tem metricas claras?  ║
    ║                                   ║
    ║  Seguranca confere: dados         ║
    ║  sensiveis foram mapeados?        ║
    ║                                   ║
    ║  Produto confere jornadas do UX:  ║
    ║  batem com a realidade?           ║
    ╚═══════════════════════════════════╝
                    ↓
            Se algo nao passou:
            → Corrige → Confere de novo
            → Repete ate tudo limpo
                    ↓
    ┌───────────────────────────────────┐
    │  VOCE aprova os documentos        │
    │  (em linguagem de produto,        │
    │   sem termos tecnicos)            │
    └───────────────────────────────────┘
                    ↓
            ✅ /freeze-prd
            "PRD congelado — ninguem muda mais
             sem sua autorizacao"
```

---

### FASE B — ESTRATEGIA TECNICA
**Objetivo:** Decidir COMO construir o que voce quer.
**Duracao tipica:** 1-2 sessoes
**Sua participacao:** Media (aprova decisoes traduzidas em linguagem simples)

```
    ┌─────────────────────────────────────────────────┐
    │          7 especialistas trabalham               │
    │          em paralelo, cada um no                 │
    │          seu dominio:                            │
    │                                                  │
    │  Arquitetura: "como as pecas se encaixam"        │
    │  Dados: "como o banco de dados vai funcionar"    │
    │  Seguranca: "quais ameacas existem e como        │
    │              proteger"                            │
    │  UX: "como as telas vao parecer e funcionar"     │
    │  Observabilidade: "como vamos saber se algo      │
    │                    quebrou"                       │
    │  Integracao: "como conversar com nota fiscal,    │
    │               pagamento, etc"                    │
    │  DevOps: "como o codigo vai do computador        │
    │           para o servidor"                       │
    └────────────────────┬────────────────────────────┘
                         ↓
    ╔═══════════════════════════════════════════════════╗
    ║  CROSS-REVIEW (cada um confere o trabalho        ║
    ║  dos outros, do seu angulo de expertise)         ║
    ║                                                  ║
    ║  Seguranca revisa TUDO — e o mais critico.       ║
    ║  "A arquitetura e segura? O banco protege        ║
    ║   dados de clientes? O logging nao vaza          ║
    ║   informacao sensivel? As integracoes            ║
    ║   externas sao autenticadas?"                    ║
    ║                                                  ║
    ║  Arquitetura confere viabilidade tecnica.        ║
    ║  "O plano do DBA e viavel? A integracao          ║
    ║   esta desacoplada? O pipeline faz sentido?"     ║
    ║                                                  ║
    ║  Produto confere alinhamento com usuario.        ║
    ║  "O design atende as jornadas? O threat model    ║
    ║   cobre os fluxos de negocio?"                   ║
    ║                                                  ║
    ║  QA confere testabilidade.                       ║
    ║  "As decisoes sao testaveis? Cobertura OK?"      ║
    ║                                                  ║
    ║  Dados confere consistencia.                     ║
    ║  "APIs batem com modelo de dados?"               ║
    ╚═══════════════════════════════════════════════════╝
                         ↓
                Se algo nao passou:
                → Corrige → Confere de novo
                → Repete ate tudo limpo
                         ↓
    ┌───────────────────────────────────────────┐
    │  VOCE aprova as decisoes tecnicas         │
    │  (apresentadas como recomendacoes:        │
    │   "minha recomendacao e X porque Y,       │
    │    alternativa e Z, voce aceita?")        │
    └───────────────────────────────────────────┘
                         ↓
            ✅ /freeze-architecture
            "Arquitetura congelada — hora de planejar
             a construcao"
```

---

### FASE C — PLANEJAMENTO
**Objetivo:** Dividir o trabalho em partes pequenas e gerenciaveis.
**Duracao tipica:** 1 sessao por epico
**Sua participacao:** Media (aprova stories, nao precisa ver planos tecnicos)

```
    ┌───────────────────────────────────┐
    │  Especialista de Produto          │
    │  (decompose)                      │
    │                                   │
    │  Divide o PRD em "epicos"         │
    │  (blocos grandes de trabalho)     │
    │  e cada epico em "stories"        │
    │  (tarefas menores com criterios   │
    │   claros de pronto/nao-pronto)    │
    └───────────────┬───────────────────┘
                    ↓
    ╔═══════════════════════════════════╗
    ║  QA audita as stories:            ║
    ║  "Criterios de aceite sao        ║
    ║   testaveis? Escopo fechado?     ║
    ║   Dependencias consistentes?"    ║
    ╚═══════════════════════════════════╝
                    ↓
            Se findings → corrige → re-audita
                    ↓
    ┌───────────────────────────────────┐
    │  VOCE aprova as stories           │
    │  (cada uma com criterios claros   │
    │   de "quando esta pronto")        │
    └───────────────────────────────────┘
                    ↓

    ══════ Para cada slice (pedaco de story): ══════

    ┌───────────────────────────────────┐
    │  QA (audit-spec)                  │
    │  Confere a especificacao          │
    │  do slice contra o plano geral    │
    └───────────────┬───────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │  Arquitetura (plan)               │
    │  Cria o plano tecnico:            │
    │  "quais arquivos mudar, como,     │
    │   e quais riscos existem"         │
    │                                   │
    │  Consulta Dados se necessario:    │
    │  "o modelo de dados do plano      │
    │   esta correto?"                  │
    └───────────────┬───────────────────┘
                    ↓
    ╔═══════════════════════════════════╗
    ║  CROSS-REVIEW do plano:           ║
    ║  QA confere: plano viavel?        ║
    ║  Seguranca confere: aspectos      ║
    ║  de seguranca cobertos?           ║
    ╚═══════════════════════════════════╝
                    ↓
            Se findings → corrige → re-review
                    ↓
            ✅ Auto-aprovado se todos concordam
            (voce so e chamado se houver problema)
```

---

### FASE D — EXECUCAO (CONSTRUCAO)
**Objetivo:** Escrever o codigo.
**Duracao tipica:** Varia (minutos a horas por slice)
**Sua participacao:** Nenhuma (so o Construtor trabalha aqui)

```
    ┌───────────────────────────────────┐
    │  Construtor (test-writer)         │
    │                                   │
    │  PRIMEIRO escreve os testes.      │
    │  Cada criterio de aceite vira     │
    │  um teste automatico.             │
    │                                   │
    │  Os testes DEVEM FALHAR neste     │
    │  momento — ainda nao tem codigo.  │
    │  Isso e proposital: prova que     │
    │  o teste realmente verifica algo. │
    └───────────────┬───────────────────┘
                    ↓
            Salva: "testes red" (falhando)
                    ↓
    ┌───────────────────────────────────┐
    │  Construtor (implementer)         │
    │                                   │
    │  DEPOIS escreve o codigo que      │
    │  faz os testes passarem.          │
    │                                   │
    │  Trabalha teste por teste:        │
    │  escreve codigo → roda teste →    │
    │  se passou, proximo teste.        │
    │  Se falhou, corrige e tenta       │
    │  de novo.                         │
    └───────────────┬───────────────────┘
                    ↓
            Salva: "testes green" (passando)
                    ↓
            Pronto para validacao! →→→ Fase E
```

**Por que testes primeiro?** Porque garante que o codigo faz exatamente o que foi combinado nos criterios de aceite. Sem teste antes, e facil "achar que funciona" sem realmente verificar.

---

### FASE E — VALIDACAO (PIPELINE DE GATES)
**Objetivo:** Garantir que o que foi construido tem qualidade maxima.
**Duracao tipica:** Automatico (minutos)
**Sua participacao:** So se houver problema grave (6 rejeicoes seguidas)

**Imagine uma linha de inspecao de fabrica.** O codigo passa por ate 9 inspetores, cada um olhando de um angulo diferente. Se QUALQUER um encontrar QUALQUER problema (por menor que seja), o codigo volta para correcao.

```
    ════════════════════════════════════════════
    GATE 1: Inspecao mecanica (QA - verify)
    ════════════════════════════════════════════
    "Testes passam? Codigo formatado? Tipos corretos?"
    
    Pense como: a peca tem as dimensoes corretas?
    Se NAO → Construtor corrige → volta pro Gate 1
    Se SIM ↓

    ════════════════════════════════════════════
    GATE 2: Revisao estrutural (Arquitetura - code-review)
    ════════════════════════════════════════════
    "Codigo duplicado? Nomes claros? Segue os padroes?
     Segue as decisoes arquiteturais (ADRs)?"
    
    Pense como: a peca esta bem acabada e segue o projeto?
    ⚠️ Este inspetor NAO ve o resultado do Gate 1
       (independencia total — principio R11)
    Se NAO → Construtor corrige → volta pro Gate 2
    Se SIM ↓

    ════════════════════════════════════════════
    GATE 3: Inspecoes especializadas (em paralelo)
    ════════════════════════════════════════════
    
    SEMPRE rodam (3 inspetores):
    ┌──────────────────────────────────────┐
    │  Seguranca (security-gate)           │
    │  "Tem vulnerabilidade? Dados         │
    │   protegidos? LGPD OK?"             │
    ├──────────────────────────────────────┤
    │  QA (audit-tests)                    │
    │  "Testes cobrem todos os cenarios?   │
    │   Edge cases? Testes frageis?"       │
    ├──────────────────────────────────────┤
    │  Produto (functional-gate)           │
    │  "Funciona do ponto de vista do      │
    │   usuario? Criterios de aceite       │
    │   realmente atendidos?"              │
    └──────────────────────────────────────┘

    CONDICIONAIS (so se o slice mexe nesse dominio):
    ┌──────────────────────────────────────┐
    │  Dados (data-gate)                   │
    │  → se tem mudancas no banco          │
    │  "Performance OK? Tenant isolation   │
    │   seguro? Integridade referencial?"  │
    ├──────────────────────────────────────┤
    │  Observabilidade (gate)              │
    │  → se tem logging/metricas           │
    │  "Logging estruturado? Nao loga      │
    │   dados sensiveis?"                  │
    ├──────────────────────────────────────┤
    │  Integracao (gate)                   │
    │  → se tem APIs externas              │
    │  "Resiliencia OK? Retry com jitter?  │
    │   Idempotencia garantida?"           │
    └──────────────────────────────────────┘

    Se QUALQUER um encontrou problema:
    → Construtor corrige → re-roda O MESMO gate
    → Repete ate ZERO problemas
    
    Se SIM (todos aprovaram) ↓

    ════════════════════════════════════════════
    GATE 4: Aprovacao final (Governanca)
    ════════════════════════════════════════════
    "2 IAs diferentes (Claude + GPT-5)
     analisam TODOS os resultados dos gates
     e precisam CONCORDAR."
    
    Pense como: o diretor de qualidade assina
    a liberacao final olhando todos os relatorios.
    
    Se as 2 IAs discordam:
    → Tentam resolver em ate 3 rodadas
    → Se nao resolvem → chama VOCE para decidir
    
    Se AMBAS aprovaram ↓

    ════════════════════════════════════════════
    ✅ MERGE — codigo vai para a branch principal
    ════════════════════════════════════════════
```

**Regra de ouro:** ZERO tolerancia para problemas serios (S1-S3). Qualquer problema S1-S3 e corrigido antes do merge. Findings menores (S4) sao rastreados como divida tecnica. Sugestoes (S5) sao apenas registradas.

**Rede de seguranca:** se o mesmo gate rejeitar 6 vezes seguidas, para tudo e chama voce. Isso evita loops infinitos.

---

### FASE F — ENCERRAMENTO
**Objetivo:** Aprender e melhorar o processo.
**Duracao tipica:** Automatico (fim de cada epico)
**Sua participacao:** Minima (so se houver descoberta importante)

```
    Quando um epico inteiro esta pronto:
                    ↓
    ┌───────────────────────────────────┐
    │  Governanca (retrospective)       │
    │                                   │
    │  Analisa tudo que aconteceu:      │
    │  - Quantos ciclos de correcao?    │
    │  - Quais gates mais rejeitaram?   │
    │  - Algum criterio ficou sem       │
    │    cobertura?                     │
    │  - O processo pode melhorar?      │
    └───────────────┬───────────────────┘
                    ↓
    ╔═══════════════════════════════════╗
    ║  QA confere: findings validos?    ║
    ║  Seguranca confere: mudancas no   ║
    ║  processo nao enfraquecem nada?   ║
    ╚═══════════════════════════════════╝
                    ↓
    ┌───────────────────────────────────┐
    │  Governanca (harness-learner)     │
    │                                   │
    │  Aplica melhorias no processo:    │
    │  - Nova regra de validacao        │
    │  - Novo check automatico          │
    │  - Maximo 3 mudancas por ciclo    │
    │  - NUNCA pode enfraquecer regras  │
    │    existentes                     │
    └───────────────────────────────────┘
                    ↓
            Processo melhorado para o proximo epico
```

---

## Visao geral em uma pagina

```
  VOCE (PM)
    │
    │ "Quero X"
    ↓
┌─────────────────────────────────────────────────────┐
│ FASE A: DESCOBERTA                                  │
│ Produto entrevista → UX pesquisa → QA+Seg conferem  │
│ Voce aprova → PRD congelado                         │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│ FASE B: ESTRATEGIA TECNICA                          │
│ 7 especialistas projetam em paralelo                │
│ Cada um confere o trabalho dos outros               │
│ Voce aprova decisoes (em linguagem simples)         │
│ Arquitetura congelada                               │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│ FASE C: PLANEJAMENTO                                │
│ Produto divide em partes → QA audita                │
│ Voce aprova stories                                 │
│ Arquitetura planeja cada slice → QA+Seg conferem    │
│ Auto-aprovado se tudo limpo (voce nao e chamado)    │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│ FASE D: CONSTRUCAO                                  │
│ Construtor escreve testes → depois codigo           │
│ Voce nao participa                                  │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│ FASE E: VALIDACAO (ate 9 inspetores)                │
│ Gate 1: mecanico (QA) → Gate 2: estrutural (Arq)     │
│ Gate 3: seguranca + testes + produto + [condicionais│]
│ Gate 4: 2 IAs concordam                             │
│ ZERO tolerancia — qualquer problema volta           │
│ Voce so e chamado na 6a rejeicao seguida            │
└──────────────────────┬──────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────┐
│ FASE F: ENCERRAMENTO                                │
│ Retrospectiva → Aprendizado → Processo melhora      │
│ Voce so e chamado se descoberta importante          │
└──────────────────────┬──────────────────────────────┘
                       ↓
                  ✅ ENTREGUE
```

---

## Quando VOCE e chamado (e quando nao)

| Situacao | Voce e chamado? | Por que? |
|----------|-----------------|----------|
| Descoberta (entrevista) | SIM | So voce sabe o que o produto precisa |
| Aprovar PRD | SIM | E o documento que guia tudo |
| Aprovar decisoes tecnicas | SIM | Traduzidas em linguagem simples |
| Aprovar stories | SIM | Define o que "pronto" significa |
| Plano tecnico aprovado automaticamente | NAO | QA + Seguranca ja conferem |
| Construcao do codigo | NAO | Construtor trabalha sozinho |
| Gates de validacao passaram | NAO | 9 inspetores ja conferem |
| Gate rejeitou e corrigiu | NAO | Loop automatico ate 5 vezes |
| Gate rejeitou 6 vezes seguidas | SIM | Algo serio — precisa decisao sua |
| Merge do codigo | NAO | Automatico apos todos os gates |
| Retrospectiva normal | NAO | Aprendizado automatico |
| Retrospectiva com descoberta critica | SIM | Precisa decisao sua |
| Problema de seguranca grave | SIM | Risco que precisa ser aceito ou mitigado |
