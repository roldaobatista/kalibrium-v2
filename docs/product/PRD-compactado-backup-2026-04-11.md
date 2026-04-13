# PRD — Kalibrium

**Versão:** 1.0 (consolidação inicial — 2026-04-11)
**Status:** aprovado pelo PM como baseline; revisões via PR com ADR específico
**Documento de referência cruzada:** este PRD é o ponto único de leitura. Os 8 arquivos em `docs/product/` continuam sendo a fonte detalhada de cada seção — este documento é a vista consolidada.

---

## 1. O que é o Kalibrium

**Em uma frase:** o Kalibrium é um SaaS business-to-business, multi-tenant, desenhado para laboratórios brasileiros de calibração e metrologia operarem **toda a rotina** do laboratório — da entrada do pedido do cliente até a emissão do certificado assinado e a cobrança — dentro de um único produto, com rastreabilidade metrológica suficiente para sobreviver a uma auditoria da Rede Brasileira de Calibração (RBC) e fiscalização tributária estadual.

**Em uma metáfora:** "o sistema nervoso do seu laboratório de calibração".

**Fonte detalhada:** `docs/product/ideia-v1.md §1`.

---

## 2. O problema que o Kalibrium resolve

Laboratórios brasileiros de calibração de pequeno e médio porte operam hoje com uma mistura heterogênea de:

- Planilhas Excel não versionadas
- Software legado "de prateleira" sem suporte ativo
- Arquivos Word em pastas compartilhadas
- Certificados escritos à mão quando o software dá problema

**Consequências observadas:**

- **Rastreabilidade metrológica frágil** — auditorias anuais viram caça ao tesouro.
- **Retrabalho silencioso** — o mesmo técnico refaz o mesmo orçamento de incerteza toda vez que calibra o mesmo instrumento.
- **Dependência do gerente** — quando o sócio-gerente sai de férias, o laboratório trava.
- **Cobrança atrasada ou esquecida** — emissão de NFS-e manual, controle de recebíveis em planilha.
- **Risco fiscal e regulatório** — certificados emitidos sem trilha completa, NFS-e emitida com delay, conflito tributário entre municípios.

**Fonte detalhada:** `docs/product/ideia-v1.md §2`.

---

## 3. Para quem é o Kalibrium (personas)

### 3.1. Persona 1 — Marcelo (gerente/sócio do laboratório) — **comprador real**

- 48 anos, Engenharia Mecânica + especialização em metrologia pelo Inmetro.
- 12 anos como técnico antes de abrir o próprio laboratório.
- Hoje é sócio-gerente de laboratório acreditado com 5 técnicos + 2 administrativos + 600-2000 calibrações/mês.
- **Ele decide a compra e assina o contrato.**
- **Dor principal:** gerencia na ponta do dedo; perde noites fechando fatura e preparando auditoria anual.

### 3.2. Persona 2 — Juliana (técnica calibradora) — **usuária diária**

- 32 anos, técnica em mecânica com especialização em metrologia.
- 6 anos no laboratório. Calibra instrumentos dimensionais e de pressão.
- É a pessoa que mais domina o orçamento de incerteza entre os técnicos.
- **Não decide compra, mas se ela rejeita a ferramenta, o produto não é adotado.**
- **Dor principal:** refaz cálculo de incerteza toda vez que calibra o mesmo instrumento; perde tempo preenchendo certificado Word.

### 3.3. Persona 3 — Rafael (cliente final do laboratório) — **usuário esporádico**

- 40 anos, comprador técnico numa fábrica de autopeças de médio porte.
- Entre muitas responsabilidades, organiza o envio semestral de instrumentos para calibração externa.
- **Não é cliente do Kalibrium — é cliente do laboratório que USA o Kalibrium.** Mas aparece em jornadas críticas (pedido, consulta futura, revalidação).
- **Dor principal:** não recebe retorno do status do seu pedido; só sabe que está pronto quando a nota fiscal chega.

### 3.4. Personas explicitamente NÃO-alvo do MVP

- Responsável pela qualidade da indústria cliente (consome certificado mas não operacionaliza a relação).
- Auditores da Cgcre (precisam ver trilha, mas não são usuários diários).
- Fornecedores de padrões de referência (interação futura via API, fora do MVP).

**Fonte detalhada:** `docs/product/personas.md`.

---

## 4. Laboratório-tipo (o cliente canônico)

### 4.1. Perfil

| Atributo | Valor |
|---|---|
| **Porte** | Pequeno a médio — 600 a 2.000 calibrações/mês no pico |
| **Equipe** | 5 técnicos + 2 administrativos + 1 gerente/sócio |
| **Acreditação** | RBC/Cgcre vigente em pelo menos 1 dos 4 domínios do MVP |
| **Distribuição de domínios** | Dimensional 40% / Pressão 25% / Massa 20% / Temperatura 15% |
| **Procedimentos técnicos escritos** | Entre 15 e 40, um por combinação (instrumento × faixa × método) |
| **Municípios fiscais suportados no MVP** | 5 capitais brasileiras (lista exata no `mvp-scope.md`) |
| **Clientes atendidos** | Indústrias de pequeno-médio porte — metal-mecânica, alimentos, química leve, farmacêutica, autopeças, embalagem |

### 4.2. O que o laboratório-tipo NÃO é

- **Não é laboratório de ensaio.** Ensaios destrutivos (tração, flexão) ou químicos (pH, condutividade) estão **fora do escopo**.
- **Não é laboratório acadêmico.** Não atende laboratório universitário de pesquisa.
- **Não é laboratório interno de montadora.** Laboratório cativo de uso próprio está fora do MVP.
- **Não é laboratório multi-unidade.** MVP assume laboratório com 1 endereço físico. Multi-site fica para depois.

**Fonte detalhada:** `docs/product/laboratorio-tipo.md`.

---

## 5. Escopo do MVP — os 6 módulos (IN)

O MVP está dividido em 6 domínios funcionais. Cada um tem requisitos identificados no formato `REQ-DOM-NNN`.

### 5.1. TEN — Cadastro e tenant

Cadastro inicial de um laboratório como tenant isolado, gestão de usuários internos, papéis e permissões mínimas, configuração de dados fiscais básicos (CNPJ, município, inscrição municipal).

### 5.2. MET — Metrologia e calibração (núcleo técnico)

Cadastro de padrões de referência com rastreabilidade, cadastro de procedimentos técnicos versionados, registro de condições ambientais, cálculo de orçamento de incerteza (GUM/JCGM 100:2008), geração do certificado PDF com trilha completa, assinatura do responsável técnico, numeração do certificado conforme regra do laboratório.

### 5.3. FLX — Fluxo fim a fim

Agendamento de coleta/entrega, recebimento do instrumento, fila de trabalho por técnico, execução da calibração, revisão e aprovação pelo gerente, entrega ao cliente final, consulta futura pelo cliente.

### 5.4. FIS — Fiscal

Emissão de NFS-e para o município do laboratório quando o certificado é aprovado. MVP começa cobrindo **5 municípios-alvo**. Integração direta com portais municipais. Controle de recebíveis mínimo (pago / em aberto / vencido).

### 5.5. OPL — Operação do laboratório

Dashboard operacional com pedidos atrasados, pedidos na fila, pedidos esperando aprovação. Visão diária do gerente e da técnica. Exportação CSV do mês.

### 5.6. CMP — Compliance mínimo

Registro imutável (append-only) de todas as calibrações. Auditoria interna rastreável. Retenção de 10 anos de certificados. Suporte a auditoria externa RBC (exportação em PDF consolidado). Trilha LGPD mínima.

**Fonte detalhada:** `docs/product/mvp-scope.md §3`.

---

## 6. O que está EXPLICITAMENTE fora do MVP (OUT)

Os itens abaixo estão documentados como "fora do primeiro produto" para evitar escopo-fantasma. Cada um tem um gatilho de reentrada (condição que faz o item voltar ao radar).

- **Laboratório multi-site.** Um tenant = um endereço físico no MVP.
- **Ensaios não-metrológicos.** Tração, flexão, químicos, microbiológicos — fora.
- **Domínios metrológicos além dos 4 iniciais.** Elétrica, vazão, acústica, química analítica — fora do MVP.
- **Integrações com ERPs do cliente final.** Cliente consome certificado via portal, não via API.
- **Aplicativo móvel.** MVP é web-first; mobile vem depois se houver demanda comprovada.
- **Assinatura eletrônica ICP-Brasil no certificado.** MVP usa assinatura visual + trilha de auditoria. ICP-Brasil entra em release posterior.
- **Internacionalização.** Português brasileiro only no MVP. i18n fica para release futura.
- **Compras/estoque/folha.** Kalibrium NÃO é ERP generalista. Fica fora.
- **Marketplace de calibração.** Não é uber de calibração.

**Fontes detalhadas:** `docs/product/mvp-scope.md §4` + `docs/compliance/out-of-scope.md`.

---

## 7. Jornada crítica do MVP — Pedido novo, do começo ao pagamento

**Esta é a jornada-âncora do MVP.** Se qualquer outra jornada conflitar com ela, esta manda. Todas as 3 personas aparecem ao longo do fluxo.

### 7.1. Os 10 passos

| # | Passo | Persona principal | Descrição curta |
|---|---|---|---|
| 1 | Gatilho | Rafael (cliente) | Cliente final pede calibração ao laboratório |
| 2 | Cadastro/reuso de cliente | Atendente (papel de Marcelo) | Cliente existente ou novo no tenant |
| 3 | Cadastro/reuso de instrumento | Atendente | Cada instrumento vinculado ao histórico do próprio equipamento, não criado do zero |
| 4 | Escolha de procedimento + prazo | Atendente | Procedimento técnico vigente correto; prazo do cliente registrado |
| 5 | Agendamento na fila | Atendente | Distribuição do trabalho entre técnicos respeitando prazo |
| 6 | Execução técnica | Juliana (técnica) | Calibração executada seguindo procedimento; dados ambientais e leituras registrados |
| 7 | Revisão e aprovação | Marcelo (gerente/responsável técnico) | Revisão do trabalho técnico + aprovação formal |
| 8 | Geração do certificado | Sistema | PDF definitivo com numeração, trilha e assinatura |
| 9 | Emissão fiscal (NFS-e) | Sistema | Nota fiscal de serviço emitida no portal do município do laboratório |
| 10 | Entrega + consulta futura | Rafael | Cliente recebe certificado rápido; volta para consulta/revalidação |

### 7.2. Métrica de sucesso da jornada

**Dias corridos entre passo 1 e passo 9: alvo < 7 dias úteis no caso comum.**

### 7.3. Jornadas secundárias (em ordem de relevância)

- **Execução técnica em lote** — cliente grande manda 40 instrumentos de uma vez (variação da jornada 1).
- **Cliente consulta e revalida** — Rafael entra no portal para ver histórico ou agendar próxima calibração.
- **Auditoria RBC** — auditor da Cgcre sorteia certificado e pede trilha completa.
- **Administração do tenant** — Marcelo cadastra usuário, padrão, procedimento.

**Fonte detalhada:** `docs/product/journeys.md`.

---

## 8. Requisitos não-funcionais (16 RNFs numéricos)

Todos os requisitos abaixo são **números concretos**, não intenção. Se o número está errado, corrige-se aqui **antes** de decidir stack.

| # | Requisito | Alvo MVP |
|---|---|---|
| **RNF-001** | Taxa de requisições em pico (tenant mais movimentado) | 8 RPS |
| **RNF-002** | Latência p95 de tela crítica (leitura) | 400 ms |
| **RNF-003** | Latência p99 de tela crítica (leitura/escrita) | 1.200 ms / 2.500 ms |
| **RNF-004** | Teto de memória RAM por instância | 1,5 GB |
| **RNF-005** | Teto de CPU por instância | 1 vCPU (pico curto até 2 vCPUs) |
| **RNF-006** | Tenants ativos simultâneos no primeiro ano | 50 |
| **RNF-007** | RPO (Recovery Point Objective) | 1 hora de perda máxima |
| **RNF-008** | RTO (Recovery Time Objective) | 4 horas para restauração completa |
| **RNF-009** | Retenção de certificados emitidos | 10 anos |
| **RNF-010** | Frequência de deploy em produção | 2 deploys/semana em janela protegida |
| **RNF-011** | Janela de manutenção | Terça/quinta, 22:00-00:00 horário Brasília |
| **RNF-012** | Teto de custo mensal de infra (VPS + domínios + storage + backup) | R$ 800/mês |
| **RNF-013** | Teto mensal de tokens do agente de IA | US$ 250/mês |
| **RNF-014** | Concorrência de calibrações simultâneas por tenant | 10 |
| **RNF-015** | Tamanho máximo de certificado em PDF | 2 MB |
| **RNF-016** | Disponibilidade (uptime) mensal | 99,5% (≤ 3h 40min de downtime/mês) |

**Fonte detalhada:** `docs/product/nfr.md`.

---

## 9. Precificação-alvo

### 9.1. Modelo de cobrança escolhido para o MVP

**Tier único:** R$ 899 / tenant / mês, sem limite de calibrações dentro do volume esperado do laboratório-tipo (até 2.000 calibrações/mês).

### 9.2. Janela de preço aceitável (hipótese a testar)

- **Abaixo de R$ 599:** Marcelo suspeita da qualidade.
- **Entre R$ 599 e R$ 1.299:** aceitável sem debate.
- **Acima de R$ 1.299:** Marcelo pede desconto.

### 9.3. Break-even

- **Custo operacional mensal total:** R$ 8.960/mês (harness + produto + consultoria amortizada + custo do PM).
- **Break-even:** **10 tenants pagantes** no modelo R$ 899/mês.
- **Meta 12 meses:** 50 tenants ativos (RNF-006) → receita-alvo ~R$ 45.000/mês.

**Fonte detalhada:** `docs/product/pricing-assumptions.md` e `docs/finance/operating-budget.md`.

---

## 10. Critério de sucesso do MVP

**O MVP está "no ar" quando um único laboratório real (primeiro cliente pagante) consegue, dentro do Kalibrium:**

1. Registrar um pedido de calibração novo (jornada 1, passos 1-5)
2. Executar a calibração até a emissão do certificado em PDF (passos 6-8)
3. Emitir a NFS-e correspondente (passo 9)
4. Receber o pagamento via contas a receber (fora da jornada 1 mas parte do MVP)

**Tudo sem usar planilha Excel, Word ou ferramenta externa.**

**Fonte detalhada:** `docs/product/mvp-scope.md §7`.

---

## 11. Restrições e princípios não-negociáveis conhecidos desde o dia zero

### 11.1. Técnicos

- **Multi-tenant com isolamento forte.** Vazamento de certificado entre tenants = consequência regulatória.
- **Registro imutável de calibrações.** Append-only, sem delete (CMP).
- **Retenção de 10 anos** de certificados (RNF-009).
- **Conformidade com GUM/JCGM 100:2008** para cálculo de incerteza.

### 11.2. Regulatórios

- **LGPD:** política em `docs/compliance/lgpd-policy.md`.
- **RBC/Cgcre:** política em `docs/compliance/metrology-policy.md`.
- **NF-e/NFS-e multi-município:** política em `docs/compliance/fiscal-policy.md`.
- **REP-P e ICP-Brasil:** fora do MVP, ver `docs/compliance/out-of-scope.md`.

### 11.3. De produto

- **Um tenant = um laboratório físico.** Multi-site fica fora.
- **Kalibrium não é ERP generalista.** Compras/estoque/folha estão fora.
- **Português brasileiro only** no MVP.
- **Web-first.** Sem aplicativo móvel no MVP.

---

## 12. O que este PRD NÃO decide (e nunca vai decidir)

Este PRD **não escolhe**:

- **Stack tecnológica.** Isso fica em `docs/adr/0001-stack.md` quando for gerado.
- **Modelo de dados detalhado.** Fica em `docs/product/data-model.md` quando for gerado.
- **Lista de endpoints.** Fica nos `contracts/` de cada slice.
- **Estratégia de deploy.** Fica em ADR específico.
- **Quantos ambientes existem.** Fica em ADR específico.

**Este PRD existe para ser a "constituição de produto"** — a referência única que todas as decisões técnicas precisam respeitar.

---

## 13. Como este PRD é atualizado

- **Não se edita este arquivo in-place.** Se algo precisa mudar, cria-se um ADR ou atualização versionada.
- **Fontes detalhadas continuam sendo fonte.** Se mudar algo em `personas.md`, este PRD precisa refletir.
- **Revisão semestral obrigatória.** A cada 6 meses, PM re-lê e decide se atualiza.
- **Revisão disparada por evento.** Primeiro cliente pagante, primeira auditoria RBC, primeiro incidente grave — tudo dispara revisão.

---

## 14. Rastreabilidade para os 8 arquivos de descoberta

Este PRD foi consolidado a partir de:

| Arquivo fonte | Linhas | Seções deste PRD que consomem |
|---|---|---|
| `docs/product/ideia-v1.md` | 104 | §1, §2 |
| `docs/product/personas.md` | 42 | §3 |
| `docs/product/laboratorio-tipo.md` | 83 | §4 |
| `docs/product/mvp-scope.md` | 107 | §5, §6, §10 |
| `docs/product/journeys.md` | 112 | §7 |
| `docs/product/nfr.md` | 88 | §8 |
| `docs/product/pricing-assumptions.md` | 69 | §9 |
| `docs/product/glossary-pm.md` | 109 | dicionário (referência cruzada) |

Os 8 arquivos **continuam sendo fonte de verdade detalhada**. Este PRD é a vista consolidada.

---

## 15. O próximo passo que este PRD destrava

Com este PRD fechado, pela primeira vez o projeto tem a informação necessária para:

- **Escolher a stack** (`/decide-stack` → `docs/adr/0001-stack.md`) com base em RNFs concretos (RPS, latência, RAM, custo, retenção).
- **Esboçar o modelo de dados** (entidades, relacionamentos, campos obrigatórios).
- **Listar os primeiros slices de produto** em ordem de valor (TEN primeiro? MET primeiro? FLX primeiro?).

**Nada do que vem depois deste PRD pode contradizer as decisões aqui.** Se contradizer, ou este PRD é atualizado via ADR, ou a decisão posterior é reprovada.
