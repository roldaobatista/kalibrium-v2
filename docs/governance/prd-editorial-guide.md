# PRD Editorial Guide — Kalibrium V2

**Origem:** extraído de `docs/product/PRD.md` em 2026-04-11 (commit a ser gerado nesta operação).
**Motivo:** o conteúdo abaixo são **diretrizes de como editar o PRD** (fonte canônica por tema, regras de resolução de conflitos entre seções duplicadas, matriz de rastreabilidade, consolidações obrigatórias de leitura). Não é o PRD em si — é meta-governança do PRD. Manter esse bloco dentro do próprio PRD confundia o leitor (apontado na auditoria `docs/audits/internal/prd-consistency-audit-2026-04-11.md §4.2`).
**Status:** vigente. Alterações exigem novo arquivo em `docs/decisions/` (não editar in-place).
**Referenciado por:** `docs/product/PRD.md` (pointer no topo do PRD).

---
## Diretriz Editorial do PRD

Este documento descreve o **produto-alvo completo** do Kalibrium em linguagem de negócio, domínio, operação, compliance, fluxos, capacidades e critérios de sucesso. Ele não é um plano de implementação, não define MVP e não deve determinar stack, framework, provedor de nuvem, biblioteca, linguagem, banco de dados, arquitetura física ou fornecedor tecnológico específico.

Quando o documento citar integrações, canais ou capacidades técnicas, a leitura correta é funcional: o produto deve suportar o comportamento de negócio descrito, preservando rastreabilidade, segurança, configurabilidade e continuidade operacional. A escolha de tecnologia, plataforma, fornecedor, protocolo ou desenho de infraestrutura pertence a artefatos posteriores de arquitetura e planejamento de implementação.

### Fonte Canônica e Uso das Seções

As seções principais deste PRD são a fonte canônica para tese de produto, ICP, escopo, módulos, fluxos, requisitos funcionais, requisitos não funcionais, pricing, governança e operação. Seções complementares e apêndices devem ser lidos como material de referência e detalhamento: preservam conteúdo de domínio, mas não podem contradizer as decisões canônicas consolidadas nas seções principais.

Quando houver duplicidade, prevalece a versão consolidada mais próxima da seção canônica correspondente:
- **planos e limites:** `Modelo de Pricing e Planos SaaS`
- **personas e permissões:** `RBAC — Papéis e Permissões`
- **fluxos críticos:** `Fluxos End-to-End Prioritários`
- **requisitos funcionais:** `Requisitos Funcionais`
- **SLOs, RPO, RTO e suporte:** `Requisitos Não Funcionais`, `SLOs de Performance por Módulo` e `Onboarding e Suporte SaaS`
- **glossário:** `Glossário de Produto e Domínio`

Conteúdo ainda marcado como "Gap", "Relevância", "roadmap", "fase" ou similar deve ser tratado como insumo de produto incorporado ou candidato a incorporação, não como plano de implementação nem como decisão de ciclo de entrega.

### Mapa de Canonicalidade e Resolução de Conflitos

Este PRD consolida conteúdo originalmente distribuído entre visão, backlog, apêndices e notas de domínio. Para evitar ambiguidade na leitura do produto completo, as seguintes regras são mandatórias:

| Tema | Fonte canônica | Regra de resolução |
|---|---|---|
| Planos, limites, entitlements e monetização | `Modelo de Pricing e Planos SaaS` | Qualquer outra tabela de plano deve ser lida como resumo comercial, não como fonte de limite ou cobrança |
| Personas, papéis e permissões | `RBAC — Papéis e Permissões` | Matrizes duplicadas devem ser consolidadas nesta seção antes de virar especificação modular |
| Fluxos críticos | `Fluxos End-to-End Prioritários` | Fluxos em apêndices só complementam comportamento se declararem gatilho, owner, estados, exceções e efeitos downstream |
| Requisitos funcionais | `Requisitos Funcionais` | Se uma capacidade aparece em narrativa, gap ou apêndice, deve ser promovida a FR/CAP ou marcada como referência |
| Segurança, privacidade, disponibilidade, RPO/RTO e suporte | `Requisitos Não Funcionais`, `SLOs de Performance por Módulo` e `Onboarding e Suporte SaaS` | Valores de senha, retenção, severidade, resposta, resolução e recuperação devem usar a versão canônica mais restritiva ou mais recente explicitamente indicada |
| Lifecycle do tenant e offboarding | `Modelo de Pricing e Planos SaaS` e `Regras Transversais do Sistema` | Cancelamento, carência, exportação, suspensão, deleção e reativação devem preservar contrato, LGPD, retenção legal e continuidade auditável |
| Apêndices incorporados | `Seções Complementares Incorporadas do IDEIA.md` | Preservam conhecimento de domínio, mas não podem contradizer seções canônicas; cada item deve ser classificado como migrado, referência ou candidato modular |

### Consolidações Obrigatórias de Leitura

Para evitar que o PRD seja interpretado como múltiplas versões concorrentes do produto, as duplicidades abaixo devem ser lidas como resumo + detalhamento, nunca como requisitos independentes:

| Duplicidade identificada | Fonte de verdade | Uso das seções duplicadas |
|---|---|---|
| Métricas de Sucesso vs KPIs de Produto | `Métricas de Sucesso` como seção canônica; `KPIs de Produto` como matriz subordinada | Consolidar nomes, owners, baseline, método de medição e meta antes de especificação analítica |
| Personas e Jobs To Be Done vs Personas Completas | `Personas e Jobs To Be Done` | Usar a seção completa apenas como detalhamento narrativo de jornada e dor |
| RBAC vs Matriz de Permissões RBAC por Módulo | `RBAC — Papéis e Permissões` | Usar a matriz duplicada apenas como refinamento, preservando menor privilégio |
| NFRs, NFRs detalhados e SLOs por módulo | `Requisitos Não Funcionais`; `SLOs de Performance por Módulo` como alvo operacional subordinado | Em conflito de valor, prevalece a regra canônica ou a meta mais restritiva compatível com contrato |
| Onboarding e Suporte SaaS vs Jornada de Suporte e Customer Success | `Onboarding e Suporte SaaS` | Usar a jornada como playbook operacional e não como nova tabela contratual |
| Exceções Operacionais vs Cenários de Exceção e Fallback | `Cenários de Exceção e Fallback — Casos Críticos` | Manter a primeira como resumo e a segunda como comportamento completo |
| Arquitetura Funcional vs apêndices de orquestração/eventos | `Arquitetura Funcional do Produto` e `Ownership de Dados, Eventos e Padrões Transacionais` | Usar apêndices apenas quando não contradisserem ownership e contratos funcionais canônicos |

### Normalização Necessária para PRD Enterprise

Para transformar este documento em um PRD operacionalmente robusto, todo módulo ou capacidade relevante deve responder, no mínimo:

- **quem usa:** persona interna, externa, técnica, gestora, admin, auditor ou parceiro;
- **qual objeto muda de estado:** entidade principal, status permitido, reabertura, cancelamento e bloqueio;
- **qual evento inicia e encerra o fluxo:** gatilho, pré-condição, saída de valor e efeito downstream;
- **quem decide exceções:** owner funcional, alçada, prazo, justificativa e evidência;
- **como o produto monetiza ou limita a capacidade:** plano, add-on, volume, serviço profissional ou contrato Enterprise;
- **como se opera em escala:** alertas com owner, fila de exceções, suporte, auditoria, reversão funcional e governança de mudança;
- **como o cliente confia no resultado:** evidência, trilha, documento, assinatura, protocolo, exportação e rastreabilidade.

### Estado Editorial Consolidado

Este PRD deve ser lido como documento de produto final, não como backlog bruto. A partir desta versão, cada bloco de conteúdo deve estar em uma das seguintes condições editoriais:

| Condição | Como interpretar | Ação esperada |
|---|---|---|
| Canônico | Define comportamento, regra, fluxo, plano, permissão, métrica ou compromisso de produto | Usar como fonte de verdade em especificações modulares e decisões de produto |
| Resumo subordinado | Repete uma decisão canônica em formato executivo ou comercial | Manter apenas se ajudar a leitura; em conflito, prevalece a seção canônica indicada |
| Referência de domínio | Traz detalhe regulatório, operacional ou histórico útil | Preservar como material de apoio, sem criar requisito concorrente |
| Candidato modular | Descreve capacidade real, mas ainda depende de ICP, plano, monetização, owner ou métrica | Não tratar como requisito fechado até ganhar classificação modular explícita |

Regras editoriais permanentes:
- blocos marcados como `Gap`, `Relevância`, `Baixo`, `Médio`, `Alto` ou termos similares não devem permanecer como backlog solto; devem ser migrados para CAP/FR, marcados como referência de domínio ou classificados como candidato modular;
- tabelas resumidas de três famílias de plano (`Starter`, `Professional`, `Enterprise`) são apenas leituras comerciais ou operacionais; a taxonomia comercial canônica continua sendo a de seis planos;
- prazos legais, suporte, RPO/RTO, retenção, permissões e entitlements devem aparecer uma única vez como fonte de verdade e, quando repetidos em apêndices, devem referenciar a seção canônica;
- termos técnicos como evento, fila, conector, interface pública, rotina assíncrona ou sincronização devem ser interpretados funcionalmente: o PRD exige o comportamento de negócio, rastreabilidade e governança, não a tecnologia específica.

### Registro de Consolidação Editorial

A partir desta revisão, o PRD passa a tratar os blocos históricos incorporados como material de produto já triado. Nenhum item de apêndice deve ser interpretado como backlog livre ou decisão concorrente. Cada bloco deve obedecer a uma das classificações abaixo:

| Classificação | Quando usar | Efeito no PRD |
|---|---|---|
| Migrado para FR/CAP | A capacidade já é necessária para o produto-alvo completo ou sustenta fluxo enterprise, compliance, monetização ou operação diária | Deve aparecer na seção `Requisitos Funcionais`, `Capacidades Funcionais Complementares Canônicas`, matriz modular ou fluxo canônico correspondente |
| Referência de domínio | O conteúdo é tecnicamente ou regulatoriamente útil, mas detalhado demais para a seção canônica | Permanece em apêndice e não cria requisito concorrente; em conflito, prevalece a fonte canônica |
| Candidato modular | A capacidade pode gerar valor, mas depende de ICP, plano, monetização, ativação, governança ou validação de demanda | Deve declarar owner, público-alvo, plano/add-on, métrica de valor, dependências e critério de ativação antes de virar requisito fechado |
| Fora do core técnico-operacional | A capacidade pode ser vendável, mas não sustenta a tese principal de evidência operacional vertical | Deve permanecer como expansão avaliada, sem deslocar prioridade, pricing ou narrativa do core |

Critério de precedência editorial: quando uma capacidade aparecer em mais de um lugar, a formulação canônica deve ficar no módulo, fluxo, FR/CAP ou pricing correspondente; a versão duplicada deve ser convertida em referência, resumo subordinado ou removida em consolidação futura apenas se o conteúdo já estiver preservado.

### Matriz de Rastreabilidade Canônica

Toda especificação modular posterior deve manter rastreabilidade entre tese de produto, fluxo, requisito, monetização, experiência e métrica. A matriz abaixo é a leitura mínima para impedir que o documento vire uma coleção de módulos desconectados:

| Camada | Pergunta que deve responder | Fonte canônica |
|---|---|---|
| Tese e ICP | Para quem a capacidade existe e qual dor econômica/regulatória resolve? | `Contexto e Oportunidade`, `Estratégia Go-to-Market` |
| Jornada | Qual persona executa ou sofre o impacto do fluxo? | `Personas e Jobs To Be Done`, `Jornadas do Usuário` |
| Fluxo | Qual gatilho, owner, estado, exceção e efeito downstream existem? | `Fluxos End-to-End Prioritários` |
| Módulo | Qual domínio é dono da entidade, regra e experiência diária? | `Mapa Consolidado de Módulos` |
| Requisito | Qual FR/CAP preserva a capacidade como produto-alvo completo? | `Requisitos Funcionais` |
| Plano e monetização | Em qual plano, add-on, overage, serviço profissional ou contrato Enterprise a capacidade entra? | `Modelo de Pricing e Planos SaaS` |
| Operação | Como a capacidade é suportada, auditada, reprocessada, bloqueada, reaberta ou escalada? | `Modelo de Operação do Produto`, `Requisitos Não Funcionais` |
| Métrica | Como saber se a capacidade gerou valor e reduziu risco/retrabalho? | `KPIs de Produto — Métricas Quantificáveis` |
| Experiência | Qual fila, tela, autosserviço, estado vazio, erro, bloqueio e ação em lote a persona usa no dia a dia? | `Jornadas do Usuário`, `Modelo de Operação do Produto` |
| Evidência | Qual documento, protocolo, trilha, versão, assinatura, exportação ou pacote auditável prova o resultado? | `Critérios de Aceite por Macrodomínio`, `Enterprise Trust` |
| Critério de aceite | O que precisa ser verdadeiro para considerar a capacidade suficientemente especificada no PRD? | `Critérios de Aceite por Macrodomínio` |

---
