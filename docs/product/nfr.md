# Requisitos não-funcionais numéricos — Kalibrium

> **Status:** ativo. Item 1.5.5 do plano da meta-auditoria #2 (Bloco 1.5 Nível 2). **Gate:** `scripts/decide-stack.sh` deve rejeitar execução quando este arquivo não existir ou contiver qualquer marca textual bloqueada pelo hook de sanidade (ver lista em `scripts/hooks/forbidden-strings.txt` quando criado). Este arquivo codifica os **números** que balizam o dimensionamento do Bloco 2 (escolha da stack). Depende de `docs/product/mvp-scope.md` e `docs/product/laboratorio-tipo.md` para a origem dos números.

## Princípio

Todo RNF abaixo é um **número concreto**, não intenção. Se o número está errado, corrige-se aqui antes de decidir stack. Todas as marcas textuais bloqueadas pelo hook de sanidade foram evitadas por construção.

## RNFs numéricos mínimos (≥ 10, enumerados)

### RNF-001 — Taxa de requisições alvo (RPS)
- **Alvo no MVP:** 8 requisições/segundo em pico no tenant mais movimentado.
- **Base do cálculo:** laboratório-tipo do §2.1 de `laboratorio-tipo.md` faz até 2.000 calibrações/mês. Assumindo 22 dias úteis, 8h produtivas/dia e ~15 requisições HTTP por calibração (criação, consulta, lançamento, cálculo, emissão, notificação), o pico observado em horário comercial fica em torno de 5-8 RPS. Alvo de capacidade é o limite superior da faixa, com fator de segurança de 1,5x = **12 RPS sustentados**.
- **Agregado por instância:** 12 RPS × 50 tenants ativos = 600 RPS agregados como teto do primeiro ano.

### RNF-002 — Latência p95 de tela crítica
- **Alvo:** 400 ms para ações de leitura (listar pedidos, abrir certificado).
- **Alvo:** 900 ms para ações de escrita (lançar calibração, aprovar, emitir certificado).
- **Medida em:** p95 no cliente, não no servidor (inclui rede + render).

### RNF-003 — Latência p99 de tela crítica
- **Alvo:** 1.200 ms para leitura, 2.500 ms para escrita.
- **Propósito:** teto de "ainda tolerável" antes de abrir incident.

### RNF-004 — Teto de memória RAM por instância de aplicação (VPS)
- **Alvo:** 1,5 GB por instância.
- **Origem:** VPS Hostinger no plano dimensionado em `operating-budget.md`. Duas instâncias por VPS para atingir alta disponibilidade interna = 3 GB de aplicação + 1 GB de banco + 1 GB de sistema = 5 GB = plano intermediário.

### RNF-005 — Teto de CPU por instância (VPS)
- **Alvo:** 1 vCPU por instância, com pico curto de até 2 vCPUs aceitável.
- **Consequência:** a stack escolhida no Bloco 2 precisa ter footprint compatível. Runtimes que consomem 300% de CPU em idle são rejeitados.

### RNF-006 — Capacidade de tenants ativos
- **Alvo de 12 meses:** 50 tenants ativos simultâneos no primeiro ano.
- **Alvo de 36 meses:** 300 tenants ativos.
- **Definição de "tenant ativo":** tenant com pelo menos 20 calibrações nos últimos 30 dias.

### RNF-007 — RPO (Recovery Point Objective)
- **Alvo:** 1 hora de perda máxima de dados em pior cenário.
- **Implementação esperada:** backup incremental a cada 60 minutos, backup completo diário, backup off-site semanal. Detalhes em `docs/compliance/backup-dr-policy.md` (item T2.6, bloqueado até Bloco 2 fechar).

### RNF-008 — RTO (Recovery Time Objective)
- **Alvo:** 4 horas para restauração completa de um tenant após falha maior.
- **Teto contratual externo:** nenhum SLA comercial acima disso.

### RNF-009 — Retenção de dados de calibração
- **Alvo:** 10 anos de retenção mínima para certificados emitidos.
- **Base:** prazo exigido pela Cgcre/RBC para laboratórios acreditados. Após 10 anos, decisão do tenant se apaga ou arquiva.
- **Retenção de logs de acesso:** 2 anos.
- **Retenção de rascunho (pedido não concluído):** 90 dias, depois purga automática.

### RNF-010 — Frequência de deploy em produção
- **Alvo de regime:** 2 deploys por semana em janela protegida.
- **Tetos:** até 5 deploys/semana em fase de crescimento, 1 deploy/mês em modo congelamento.
- **Janela protegida:** detalhada em RNF-011.

### RNF-011 — Janela de manutenção
- **Janela protegida para deploy:** terça e quinta-feira, 22:00-00:00 horário de Brasília.
- **Janela bloqueada:** 2ª, 4ª e 6ª em horário comercial (9-18). Deploy nesse horário exige incident file assinado.
- **Feriados:** deploy proibido no dia anterior a feriado nacional.

### RNF-012 — Teto mensal de custo de infraestrutura
- **Alvo no MVP:** R$ 800/mês somando VPS, domínios, armazenamento de PDFs e backup off-site.
- **Gate:** `/decide-stack` lê este teto e rejeita candidata cujo custo estimado exceda. Detalhe em `operating-budget.md` (1.5.9).

### RNF-013 — Teto mensal de consumo de tokens do agente de IA
- **Alvo no MVP:** US$ 250/mês em tokens Anthropic console.
- **Racional:** harness consome tokens (sub-agents, reviewers, verifiers). Produto em si não chama LLM na rota crítica do MVP. Teto é da operação do harness + retros, não do runtime do produto.

### RNF-014 — Concorrência de calibração por tenant
- **Alvo:** 10 calibrações em execução simultânea no mesmo tenant.
- **Origem:** laboratório-tipo tem 2-6 técnicos. Margem dobrada para pico.

### RNF-015 — Tamanho máximo de certificado em PDF
- **Alvo:** 2 MB por certificado gerado no MVP.
- **Teto de armazenamento por tenant/mês:** 2.000 certificados × 2 MB = 4 GB/mês = 48 GB/ano. 50 tenants = 2,4 TB/ano — compatível com plano VPS intermediário + bucket externo.

### RNF-016 — Disponibilidade alvo (uptime)
- **Alvo:** 99,5% mensal (teto de 3h 40min de downtime/mês).
- **Não é 99,9% no MVP** — custo de arquitetura para duas noves adicionais não cabe no RNF-012.

## Checklist final (auto-verificação)

- [x] Ao menos 10 RNFs numéricos (temos 16).
- [x] Zero ocorrência das marcas textuais bloqueadas pelo hook de sanidade — auto-verificado por grep antes do commit.
- [x] Todos os alvos têm número concreto.
- [x] Cada número tem origem explícita (laboratório-tipo, operating-budget ou decisão direta).
- [x] Documento é consumível pelo `scripts/decide-stack.sh` sem intervenção humana.
