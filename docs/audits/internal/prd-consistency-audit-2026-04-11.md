# Auditoria de consistência do PRD — 2026-04-11

**Arquivo auditado:** `docs/product/PRD.md` (commit `c320505`)
**Tamanho:** 7745 linhas / 633 KB / 688 headings (0 H1, 115 H2, 474 H3, 99 H4)
**Origem:** conteúdo canônico de `ideia.md` (anteriormente em `C:/PROJETOS/saas/ideia.md`, movido para o repo em 2026-04-11)
**Metodologia:** (1) análise estrutural mecânica via script Python, (2) cobertura temática contra checklist de PRD enterprise (22 itens), (3) busca de contradições internas e referências quebradas.
**Auditor:** agente — esta sessão. **Observação de viés:** a sessão que criou este arquivo *também* foi a que moveu o PRD, mas **não** editou o conteúdo (operação mecânica byte-a-byte verificada por SHA256). O viés confirmatório principal (agente auditando código que ele próprio escreveu) não se aplica ao **conteúdo**, só à **operação de move** — que é auditada trivialmente via diff hash. Auditoria de conteúdo é considerada legítima nesta sessão.

---

## 1. Sumário executivo (para o PM)

- O novo PRD tem **cobertura muito maior** que o compactado (319 linhas → 7745 linhas). Onze domínios de FR (**119 códigos únicos**, 11 domínios: COM, OPS, LAB, FIN, RH, QUA, LOG, POR, INT, SEG, BI), 29 CAPs, 8 personas completas com jornadas end-to-end, 8 fluxos integrados, RBAC detalhado, modelo de pricing, GTM, LGPD, segurança.
- **Mas** tem **208 marcadores de conteúdo não-resolvido** (131 TODO + 67 PENDENTE + 9 Gap + 1 "a definir"), **duas tabelas de navegação com contagens conflitantes** (6 jornadas vs 8 vs 12 personas), **zero RNFs codificados** apesar do texto declarar "55 NFRs", **zero seção de modelo de dados / riscos / dependências**, e **uma referência externa quebrada** (`docs/IDEIA.md` não existe).
- **Não está pronto para virar spec.** Precisa de um round de **clarificação editorial** (fechar os 208 pendentes + consolidar duplicatas + resolver contradições) antes de alimentar `/decide-stack` ou gerar slices.
- **Está pronto para refinamento iterativo.** A base é sólida; o que falta é trabalho editorial mecânico + 3-4 decisões do PM (quantas personas canônicas, quantas jornadas canônicas, quais gaps ficam OUT).

---

## 2. Análise estrutural mecânica

| Métrica | Valor | Observação |
|---|---|---|
| Linhas | 7745 | 23x o compactado anterior |
| Bytes | 633 698 | |
| Headings totais | 688 | |
| H1 | 0 | **não tem título principal** (começa direto em H2) |
| H2 | 115 | 70 das 115 são seções substantivas, 45 são sub-módulos de FRs |
| H3 | 474 | |
| H4 | 99 | |
| Códigos FR únicos | 119 | PRD text declara "122 FRs" — delta de 3 |
| Códigos CAP únicos | 29 | |
| Códigos RNF/NFR | 0 | PRD text declara "55 NFRs" — todos em prosa, sem ID |
| Códigos AC | 0 | esperado (AC vem em slices) |
| Códigos KPI | 0 | KPIs são mencionados em headings mas sem ID numerável |
| Marcadores TODO | 131 | |
| Marcadores PENDENTE | 67 | |
| Marcadores "Gap" | 9 | seções explicitamente marcadas como gap no próprio PRD |
| Marcadores "a definir" | 1 | |
| **Total não-resolvido** | **208** | **crítico para um documento "canônico"** |
| Referências externas a `docs/*.md` | 1 | todas quebradas (`docs/IDEIA.md` não existe) |
| Headings duplicados (H1-H3) | 10 | maioria reutilização de "Contexto" (x11), "Funcionalidades" (x6) em subseções de diferentes personas — manejável |

---

## 3. Cobertura contra checklist de PRD enterprise (22 itens)

| # | Item enterprise | Status no PRD | Localização / nota |
|---|---|---|---|
| 1 | Vision / tese de produto | ✅ | §Sumário Executivo (L113) + §Contexto e Oportunidade (L145) |
| 2 | Problema / dor / contexto | ✅ | §Contexto e Oportunidade |
| 3 | ICP (Ideal Customer Profile) | ✅ | §Contexto › Perfil de Cliente (ICP) |
| 4 | Personas canônicas | ⚠️ | §Personas e Jobs To Be Done (L553) + §Personas Completas (L587) — **duas seções paralelas, a Diretriz Editorial declara §Personas e Jobs To Be Done como canônica mas ambas continuam no PRD** |
| 5 | User journeys | ✅ | §Jornadas do Usuário (L599) — 8 jornadas J1-J8 |
| 6 | End-to-end flows | ✅ | §Fluxos End-to-End Prioritários (L810) — 6 fluxos numerados + 1 fluxo adicional de contrato→faturamento |
| 7 | Escopo IN (MVP + roadmap) | ✅ | §Escopo do Produto (L1114) + §Escopo e Coerência de Ativação (L1192) + §Classificação de FRs por Fase |
| 8 | Escopo OUT (explicit) | ⚠️ | §Escopo Fora do Objetivo (L1166) — **existe mas curto**; a lista de "o que é gap" está espalhada em 9 seções marcadas "Gap" pelo documento |
| 9 | Princípios de produto | ✅ | §Princípios de Produto (L197) |
| 10 | Requisitos funcionais codificados | ✅ | §Requisitos Funcionais (L2129) — 119 FRs em 11 domínios |
| 11 | Requisitos não-funcionais codificados | ❌ | §Requisitos Não Funcionais (L4092) + §NFRs Detalhados (L4204) existem **em prosa**. **Zero RNFs com ID**. O compactado tinha 16 RNFs numerados — **regressão** |
| 12 | SLAs / SLOs por módulo | ⚠️ | referenciado no guia de navegação ("SLOs por módulo") mas seção `SLOs de Performance por Módulo` não foi localizada pela busca estrutural |
| 13 | Modelo de dados / entidades | ❌ | **nenhuma seção dedicada** — entidades aparecem diluídas nos FRs |
| 14 | RBAC / permissões | ✅ | §RBAC (L2012) + §Matriz de Permissões RBAC por Módulo (L2119) — duas seções, Diretriz declara consolidação em §RBAC como canônica |
| 15 | Pricing / planos | ✅ | §Modelo de Pricing e Planos SaaS (L1458) — Starter / Professional / Enterprise |
| 16 | Go-to-market | ✅ | §Estratégia Go-to-Market (L1595) |
| 17 | Métricas de sucesso / KPIs | ⚠️ | §Métricas de Sucesso (L287) + §KPIs de Produto — Matriz Subordinada (L333) — duas seções, Diretriz declara §Métricas de Sucesso como canônica. **Nenhum KPI tem ID numerável** |
| 18 | Compliance regulatório | ✅ | §Conformidade LGPD (L1952) + §Requisitos de Domínio (L1787) — ISO 17025, LGPD, eSocial, INMETRO |
| 19 | Multi-tenant / isolamento | ✅ | §Multi-Empresa e Multi-Filial (L1401) + §SaaS B2B (L1883) |
| 20 | Segurança / criptografia | ✅ | §40.4 Segurança e Criptografia (TLS 1.3, AES-256, KMS, MFA, SSO SAML/OIDC, senhas bcrypt cost 12, WebAuthn FIDO2) |
| 21 | Integrações externas | ✅ | §10.11 Integrações, Comunicação e Orquestração Externa (3 sub-seções) — API REST OpenAPI 3.x, webhooks HMAC, rate limiting, SFTP, EDI, ERPs bidirecionais |
| 22 | Glossário de produto embutido | ❌ | PRD referencia glossário mas **não tem seção inline**. Existe `docs/product/glossary-pm.md` referenciado pelo compactado como fonte? Não — é um dos 8 arquivos-fonte fantasma do compactado |
| 23 | Riscos / assumptions / premissas | ❌ | **nenhuma seção dedicada** |
| 24 | Dependencies register | ❌ | **nenhuma seção dedicada** |
| 25 | Open questions / decision log | ❌ | **nenhuma seção dedicada** — os 208 pendentes não estão consolidados em nenhum lugar |

**Resultado da cobertura:** 14 ✅ · 5 ⚠️ · 6 ❌ · de 25 itens = **56 % coberto, 20 % parcial, 24 % faltando**.

---

## 4. Contradições internas e inconsistências

### 4.1. Navegação conflitante (CRÍTICO)

O PRD tem **dois guias de navegação** que dão contagens diferentes:

| Métrica | Guia A (§Sumário Executivo L113) | Guia B (outra ocorrência) | Contagem real (script) |
|---|---|---|---|
| Personas | "12 personas" | "Perfis canônicos internos, externos e de operação SaaS" (sem número) | 8 com hits substantivos (Carlos, Ana, Beatriz, Roberto, Fernanda, Diego, Carla, Luiza) |
| Jornadas | "6 jornadas end-to-end" | "8 jornadas end-to-end com FRs" | 8 jornadas numeradas J1-J8 |
| FRs | "122 FRs principais" | "122 FRs em 11 domínios (COM, OPS, LAB, FIN, RH, QUA, LOG, POR, INT, SEG, BI)" | 119 FRs únicos no texto |
| NFRs | "55 NFRs + SLOs" | "55 NFRs + SLOs por módulo" | 0 NFRs com ID |

**Impacto:** o PRD não pode simultaneamente ter 6 e 8 jornadas. Não pode ter 12 personas sem listá-las. Não pode declarar 122 FRs e ter 119. Essas contradições precisam ser normalizadas antes de o PRD servir como fonte canônica.

### 4.2. Diretriz Editorial dentro do PRD

Linhas 3-110 contêm `§Diretriz Editorial do PRD`, `§Mapa de Canonicalidade e Resolução de Conflitos`, `§Consolidações Obrigatórias de Leitura`, `§Matriz de Rastreabilidade Canônica`, `§Registro de Consolidação Editorial`. **Esse é conteúdo sobre como editar o PRD, não é o PRD.** Deveria migrar para `docs/governance/prd-editorial-guide.md` ou apêndice separado.

### 4.3. Seções duplicadas declaradas mas não resolvidas

A Diretriz Editorial lista explicitamente 5 pares de seções duplicadas que "devem ser consolidadas" mas o PRD atual **ainda tem as duas versões de cada par**:

1. `Personas e Jobs To Be Done` **vs** `Personas Completas — Todos os Perfis` — ambas presentes
2. `Métricas de Sucesso` **vs** `KPIs de Produto — Matriz Subordinada` — ambas presentes
3. `RBAC — Papéis e Permissões` **vs** `Matriz de Permissões RBAC por Modulo` — ambas presentes
4. `Requisitos Não Funcionais` **vs** `NFRs Detalhados — Requisitos Nao Funcionais Expandidos` — ambas presentes
5. `Trilhas Permanentes de Evolucao do Produto` (L229) **vs** `Roadmap e Trilhas Permanentes` (mencionado na navegação) — duas versões

**Impacto:** a Diretriz declara fonte canônica para cada par, mas as versões redundantes continuam no texto — o leitor não sabe qual ler sem ter lido a Diretriz primeiro.

### 4.4. Nomes inconsistentes para o mesmo conceito

- "Requisitos Não Funcionais" (L4092) vs "NFRs Detalhados" (L4204) vs "NFR" (navegação) vs "RNF" (terminologia antiga do compactado). **Canônico deveria ser um só.**
- "Fluxos End-to-End Prioritários" (L810) vs "Fluxo End-to-End: Contrato → Faturamento → Cobrança" (L1047) — o segundo é um fluxo isolado fora da seção que deveria conter todos os fluxos.

### 4.5. Acentuação inconsistente

Várias seções estão sem acentuação ("Operacao", "Workflow Configuravel", "Manutencao Preventiva") e outras com ("Manutenção", "Execução"). É um documento em PT-BR — acentuação deve ser consistente.

---

## 5. Marcadores de conteúdo não-resolvido

**208 marcadores totais:**

- `TODO` — 131 ocorrências
- `PENDENTE` — 67
- `Gap` — 9
- `a definir` — 1

**Impacto:** cada marcador é potencialmente uma decisão pendente do PM ou uma informação faltante. Nenhum está consolidado em um lugar único (ver gap #25 acima — falta seção de Open Questions / Decision Log).

**Ação recomendada antes de refinar:** gerar `docs/audits/internal/prd-open-questions-2026-04-11.md` listando cada uma das 208 ocorrências com: linha, contexto (10 linhas antes e 10 depois), categoria sugerida (decisão PM / dado faltante / ambiguidade / duplicata / obsoleto), e draft de resolução.

---

## 6. Referências externas quebradas

Apenas **1 referência** externa encontrada no PRD inteiro:

- `docs/IDEIA.md` — **não existe no repo**

Comparação com o compactado anterior (que tinha 8 arquivos-fonte fantasmas em §14 rastreabilidade): o canonical tem muito menos referências mas a única que tem também está quebrada. Rastreabilidade externa do PRD = inexistente.

**Recomendação:** ou remover a referência a `docs/IDEIA.md` (já que agora o próprio PRD É o conteúdo da ideia) ou criar um símlink/pointer explicando a mudança.

---

## 7. Pontos fortes (não esquecer)

O novo PRD tem coisas boas que o compactado não tinha e que precisam ser **preservadas** no refinamento:

1. **Cobertura de domínio**: 119 FRs em 11 domínios, cobrindo COM/OPS/LAB/FIN/RH/QUA/LOG/POR/INT/SEG/BI.
2. **Jornadas end-to-end**: 8 jornadas cruzando FRs, com resumo de capacidades por jornada (tabela em L?? cruzando J1-J8 com FRs específicos).
3. **Segurança detalhada**: TLS 1.3, AES-256-GCM por tenant, KMS, MFA, SSO SAML 2.0 + OIDC, WebAuthn FIDO2, bcrypt cost 12, rotação de chaves, HSM.
4. **Integrações profundas**: API REST OpenAPI 3.x, webhooks HMAC, rate limiting por plano, ERPs bidirecionais SAP/Totvs/Oracle, SFTP, EDI.
5. **LGPD estruturado**: DPO, titular, controlador, operador, direito ao esquecimento, portabilidade, log imutável de acesso a dado sensível.
6. **Multi-tenant + multi-empresa**: isolamento por tenant + grupo empresarial (admin de grupo = Jornada 7).
7. **Pricing multi-plano**: Starter / Professional / Enterprise com diferenciação por limites e features.
8. **Compliance ISO 17025**: rastreabilidade metrológica completa, padrões, incerteza GUM, Monte Carlo (GUM S2 para casos complexos).
9. **Mobilidade real**: Jornada 1 (Carlos, técnico de campo) com offline PWA, sincronização, coleta de evidências.
10. **Workflow no-code**: motor configurável por tenant, checklists dinâmicos com lógica condicional, SLA configurável por tipo de OS.

---

## 8. Ação recomendada ao PM — 5 passos para "PRD pronto para virar spec"

Esses 5 passos transformam o PRD canônico atual em um documento pronto para alimentar `/decide-stack` e `/new-slice`. Cada um é uma sessão ou bloco curto.

### Passo 1 — Decisões do PM (bloqueantes)

Você precisa decidir, em linguagem de produto (sim/não, A/B/C), 4 coisas:

1. **Quantas personas canônicas ficam no MVP?** O PRD tem 8 jornadas (Carlos/Ana/Beatriz/Roberto/Fernanda/Diego/Carla/Luiza) mas o guia de navegação fala em "12 personas". Recomendação: ficar com as **8 canônicas J1-J8** no MVP, mover as 4 extras para "roadmap de personas".
2. **Quantas jornadas canônicas ficam no MVP?** Guia A diz 6, Guia B diz 8, contagem real = 8. Recomendação: **8 jornadas** (bate com o mapa J1-J8), corrigir Guia A.
3. **Você quer números codificados para NFRs (NFR-001 a NFR-NNN)** ou deixa em prosa? **Recomendação forte:** codificar. Sem ID, NFRs não conseguem ser rastreados em slices nem virar gates de CI. O compactado tinha 16 NFRs numerados; essa prática deve ser recuperada e expandida para os 55 declarados.
4. **Os 9 "Gaps" explícitos + 208 marcadores de pendência** — você quer revisar item a item, ou autorizar o agente a categorizar em 4 baldes (decisão PM / dado faltante / duplicata / obsoleto) e trazer para você só os que precisam de decisão real?

### Passo 2 — Consolidação editorial mecânica (agente, sem decisão PM)

Trabalho mecânico que o agente pode fazer sozinho após Passo 1 decidido:

- Extrair `Diretriz Editorial do PRD` para `docs/governance/prd-editorial-guide.md` (remove L3-L110 do PRD, deixa só pointer).
- Resolver os 5 pares de seções duplicadas seguindo a Diretriz (canônica fica, redundante é absorvida como detalhamento ou removida).
- Normalizar nomenclatura: um único termo canônico entre "RNF", "NFR", "Requisitos Não Funcionais".
- Normalizar acentuação PT-BR em todas as 115 H2s.
- Remover a única referência quebrada (`docs/IDEIA.md`).

### Passo 3 — Preencher gaps de checklist enterprise (agente, com mini-decisões PM)

Adicionar as 6 seções faltantes:

- §Modelo de Dados (lista de entidades principais, sem schema de banco — isso é ADR)
- §Riscos, Assumptions e Premissas (lista numerada RA-001 a RA-NNN)
- §Dependencies (externas + internas)
- §Open Questions / Decision Log (os 208 pendentes consolidados)
- §Glossário de Produto (embed ou pointer para `docs/product/glossary-pm.md` quando criado)
- §SLOs de Performance por Módulo (referenciado mas não achado)

Cada seção pode ser gerada pelo agente em draft e validada pelo PM em linguagem de produto.

### Passo 4 — Codificar NFRs (se Passo 1.3 = sim)

Varrer o texto de `§Requisitos Não Funcionais` + `§NFRs Detalhados`, extrair cada afirmação numérica / mensurável, atribuir ID NFR-001, NFR-002... até completar os ~55 declarados. Consolidar em tabela com: ID, domínio, afirmação, métrica, fonte de verificação, gate onde será validado.

### Passo 5 — Auditoria em sessão nova (pós-refinamento)

Depois do Passo 4, rodar esta mesma auditoria em **sessão nova** do Claude Code, com o PRD refinado, verificar que:

- 0 TODO / PENDENTE / Gap não-resolvidos
- 0 contradições de navegação
- NFRs codificados
- 6 seções faltantes preenchidas
- 1 única terminologia canônica

Se tudo verde, PRD está pronto para alimentar `/decide-stack` (Bloco 2 do tracker principal).

---

## 9. Estimativa de esforço

| Passo | Unidades | Dependência |
|---|---|---|
| 1 — Decisões PM | 0 (mecânico para agente, depende do PM) | — |
| 2 — Consolidação editorial | 6 | Passo 1 |
| 3 — Preencher 6 gaps enterprise | 10 | Passo 1 |
| 4 — Codificar NFRs | 4 | Passo 3 |
| 5 — Auditoria sessão nova | 2 | Passos 2+3+4 |
| **Total** | **22 unidades** | ≈ 1x o Bloco 1 do tracker principal |

---

## 10. Rastreabilidade

- **PRD auditado:** `docs/product/PRD.md` (commit `c320505`)
- **Backup do compactado anterior:** `docs/product/PRD-compactado-backup-2026-04-11.md`
- **Origem do conteúdo canônico:** `C:/PROJETOS/saas/ideia.md` (movido)
- **Origem desta auditoria:** PM solicitou em 2026-04-11 antes de refinar o PRD
- **Próxima ação sugerida:** PM responde as 4 decisões do Passo 1, em linguagem de produto, e sinaliza que pode começar Passo 2 em sessão nova (ou esta mesma, a critério do PM)

---

## 11. Observações sobre limites desta auditoria

1. **Não li o PRD inteiro linha a linha.** Usei (a) análise estrutural via Python (lê o arquivo todo, extrai métricas), (b) buscas temáticas via context-mode (amostras de contexto). Varredura completa linha-a-linha precisa de uma segunda passada.
2. **Não validei semântica de domínio.** Não verifiquei se os 119 FRs fazem sentido metrologicamente ou se a regra fiscal do MVP bate com a legislação. Isso é escopo do consultor metrologia (item M2 da trilha paralela) + consultor fiscal (F2).
3. **Não comparei com `ideia 2.md`.** A versão preservada em `C:/PROJETOS/saas/ideia 2.md` pode ter conteúdo único que não passou para `ideia.md`. Se o PM quiser, auditoria separada cobre isso.
4. **Não audite as 9 seções marcadas explicitamente como "Gap"** no texto — listei que existem 9, não detalhei o conteúdo de cada uma. Faz parte do Passo 1 / Passo 3 do roteiro de refinamento.
