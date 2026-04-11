# Foundation constraints — Kalibrium

> **Status:** ativo. Item 1.5.8 do plano da meta-auditoria #2 (Bloco 1.5 Nível 3). **Gate dependente:** o ADR-0001 (escolha da stack, Bloco 2) é **rejeitado** se não citar este arquivo. Qualquer candidata de stack que viole uma das restrições abaixo deve ser descartada antes de chegar ao comparativo final. Este arquivo **não escolhe linguagem, framework ou banco** — ele define os **padrões arquiteturais** que a stack precisa suportar.

## 1. Modelo arquitetural

**Decisão:** monolito modular.

**Justificativa:** o MVP atende até 50 tenants ativos no primeiro ano (RNF-006). A complexidade operacional de microserviços (orquestração, mensageria, tracing distribuído, múltiplos deploys) consumiria o teto mensal de infra (RNF-012, R$ 800) e o teto de tokens de observação do harness (RNF-013). A cultura R11/R12 (dual-verifier + tradução para PM) pressupõe que o verifier consiga rodar o sistema inteiro em uma worktree descartável — microserviços quebrariam esse pressuposto. O monolito modular preserva a separação interna de domínios (metrologia, fiscal, cadastro, compliance) para viabilizar divisão futura quando o tráfego justificar.

**Consequência para o Bloco 2:** frameworks que favorecem monolito (Django, Rails, Laravel, Spring Boot, AdonisJS, Phoenix) são candidatos naturais. Frameworks que só fazem sentido em microserviços (Dapr, Istio) estão fora.

## 2. Estratégia de multi-tenancy

**Decisão:** `shared database, schema compartilhado, isolamento por `tenant_id` + RLS (row-level security)`.

**Justificativa:** três alternativas foram consideradas formalmente:

| Alternativa | Isolamento | Custo de infra | Custo operacional | Complexidade backup | Decisão |
|---|---|---|---|---|---|
| DB-per-tenant | Forte (fisicamente separado) | Alto (um banco por cliente, replica por banco) | Alto (migrations × N tenants) | Alto (backup por banco) | **Rejeitada** — estoura RNF-012 já com 10 tenants. |
| Schema-per-tenant | Médio-forte | Médio | Alto (migrations × N schemas) | Médio | **Rejeitada** — custo operacional ainda alto e ganho de isolamento parcial não justifica. |
| Shared DB + `tenant_id` + RLS | Depende do enforcement | Baixo | Baixo | Baixo | **Escolhida** — com RLS no banco, o isolamento vira propriedade do banco e não da aplicação. |

**Consequência para o Bloco 2:** o banco escolhido no ADR-0001 precisa ter RLS nativo e maduro. Bancos sem RLS nativo (ou com RLS em preview) são descartados nesse critério. Alternativas equivalentes em termos de garantia de isolamento (por exemplo, policies executadas por middleware + verificação automática de todas as queries) podem ser aceitas apenas se o ADR-0001 provar a equivalência com teste real.

**Contrato de isolamento (R11 verifier precisa poder verificar):**
1. Toda tabela que carrega dado de tenant tem coluna `tenant_id` NOT NULL.
2. Toda query que roda em contexto de tenant tem o filtro `tenant_id = :current` aplicado automaticamente via RLS/middleware/ambos.
3. Testes de "negação cruzada": tenant A não pode ler dado de tenant B mesmo se tentar injetar SQL ou trocar `current_setting`.
4. Vazamento cruzado é **incidente crítico** — registro em `docs/incidents/` + rollback do slice responsável.

## 3. Modelo de cliente (interface do usuário)

**Decisão:** web responsivo como primeiro alvo; PWA como incremento; aplicativo nativo está fora do MVP (`mvp-scope.md §4`).

**Justificativa:**
- Técnico calibrador (Persona 2 Juliana) opera no tablet ou celular em bancada — web responsivo bem feito cobre o caso sem dependência de loja de aplicativo.
- Gerente (Persona 1 Marcelo) opera no desktop — web natural.
- Cliente final (Persona 3 Rafael) opera no desktop ou celular esporadicamente — web natural.
- PWA permite "salvar na tela inicial" + modo offline parcial para lançamento de bancada, atendendo Juliana sem investir em app nativo.

**Consequência:** o framework escolhido no Bloco 2 precisa ter solução madura para PWA e para acessibilidade (contraste AA, foco visível, teclado) — critério de eliminação no ADR-0001.

## 4. Modelo de dados inicial

**Decisão:** modelo relacional normalizado com entidades centrais identificadas abaixo. Dados temporais (histórico de estado do pedido) em tabela de eventos imutáveis (append-only). Dados de compliance (calibrações emitidas) em armazenamento imutável pós-emissão.

**Entidades centrais do MVP:**
- `tenant` (um por laboratório)
- `user` (pessoas do laboratório)
- `client` (empresa atendida)
- `instrument` (equipamento do cliente)
- `standard` (padrão de referência do laboratório)
- `procedure` (procedimento técnico versionado)
- `order` (pedido de calibração)
- `calibration` (execução técnica)
- `certificate` (documento emitido imutável)
- `fiscal_invoice` (NFS-e vinculada)
- `audit_log` (imutável, append-only)

Relações e cardinalidades vão ao ADR-0001 como anexo. O modelo de dados **não depende** do banco escolhido — depende de o banco ter transações ACID, integridade referencial e coluna `tenant_id` com RLS.

## 5. Estratégia de deployment

**Decisão:** deploy em VPS Hostinger (decisão já tomada pelo orçamento — RNF-012 e `operating-budget.md`), container-based, 2 instâncias da aplicação por VPS + 1 banco local + proxy reverso terminando TLS. Zero downtime deploy por rolling restart.

**Ambientes:**
- **dev** — máquina do agente/sandbox, banco local efêmero, dados sintéticos.
- **staging** — VPS paralelo menor (aproximadamente 50% do plano de produção), dados sintéticos ampliados, espelhado em estrutura.
- **produção** — VPS Hostinger plano intermediário, dados reais, backups conforme RNF-007/RNF-008.

**Deploy em produção:** ver RNF-010 (frequência 2/semana), RNF-011 (janela protegida terças e quintas 22-00).

**Consequência para o Bloco 2:** a stack escolhida precisa rodar em container razoavelmente leve (imagem final menor que 600 MB), iniciar em menos de 30 segundos e permitir rolling restart sem perder conexões WebSocket (caso existam). Stacks que só fazem sentido em Kubernetes full-blown são descartadas.

## 6. Estratégia de autenticação e autorização

**Decisão:** autenticação local (e-mail + senha com hash forte) para usuários de laboratório; suporte opcional a login federado via OAuth 2.0 (Google / Microsoft 365) a partir do 6º mês; autorização baseada em papéis (RBAC) com os papéis definidos em `mvp-scope.md §3.1` (gerente, técnico, administrativo, visualizador) + isolamento por tenant como dimensão obrigatória de toda verificação.

**Regras duras:**
- Senha mínima de 12 caracteres, hash adaptativo (Argon2id ou bcrypt com parâmetros modernos).
- MFA opcional por TOTP; obrigatório para o papel "gerente" no primeiro tenant pagante.
- Sessão expira após 12 horas de inatividade ou 7 dias totais, o que vier primeiro.
- Token de portal público do cliente final é link assinado de curta duração (máximo 72h) — não usa sessão.

**Consequência:** a stack precisa ter biblioteca de auth madura no framework ou precisa escrever camada auth com cobertura de teste 100% antes de produção.

## 7. Limites fiscais e metrológicos

- **Fiscal:** o Kalibrium emite NFS-e apenas nos 5 municípios-alvo iniciais listados em `laboratorio-tipo.md §2.6`. Extensão é trabalho de domínio com consultor fiscal (item F3 da Trilha paralela) e **não** é decisão do Bloco 2.
- **Metrologia:** o Kalibrium cobre apenas os 4 domínios do MVP (`mvp-scope.md §2`). Extensão depende de validação pelo consultor de metrologia (item M1) antes do lançamento para cliente pagante.
- **Imutabilidade regulatória:** calibrações emitidas não podem ser alteradas. Correção obriga emissão de novo certificado com referência ao anterior (substituição, não edição).
- **Retenção:** 10 anos mínimos (RNF-009).

## 8. Restrições transversais sobre a stack

- **Idioma da stack do time:** qualquer um desde que a comunidade tenha tradução de erro/mensagem para português ou que o framework suporte i18n sem esforço heroico.
- **Licença:** stack precisa ter licença permissiva (MIT, BSD, Apache 2.0) ou LGPL no máximo. GPL-only, SSPL, BUSL sem contrato comercial estão fora — são risco jurídico para SaaS multi-tenant.
- **Telemetria enviada para fornecedor externo:** zero. Nada do runtime do produto pode enviar dados de tenant para fornecedor de observabilidade sem contrato explícito + base legal LGPD — ver `docs/compliance/vendor-matrix.md` (T2.11 da Trilha #2).
- **Dependências críticas com licença ambígua:** qualquer dependência direta sem licença clara é rejeitada no `/decide-stack`.

## 9. Observabilidade mínima

O monolito modular da §1, operado em 2 instâncias por VPS conforme §5, precisa de observabilidade suficiente para o verifier R11 conseguir construir uma narrativa de incidente sem abrir código. Os sinais mínimos obrigatórios antes do primeiro tenant pagante:

- **Logs estruturados** — cada linha de log tem `tenant_id`, `user_id`, `request_id`, `severity` e corpo curto em português ou inglês técnico. Logs de aplicação ficam por 30 dias em armazenamento local, depois vão para bucket off-site.
- **Métricas agregadas** — RPS, p50/p95/p99 de leitura e escrita, taxa de erro 4xx e 5xx, uso de memória e CPU por instância, profundidade da fila de NFS-e. Coleta local, sem fornecedor externo pago no MVP.
- **Traces de rota crítica** — apenas na rota de emissão do certificado (passos 1.6 a 1.9 da jornada 1). Span por passo, correlacionável com `request_id` do log.
- **Alertas mínimos** — 5xx sustentado acima de 2% por 5 minutos, p95 de escrita acima de 2s por 5 minutos, p95 de leitura acima de 1s por 10 minutos, fila de NFS-e acima de 20 pedidos por mais de 30 minutos, uso de disco acima de 85%. Destino inicial dos alertas: e-mail para o PM + WhatsApp quando houver canal configurado.

Qualquer stack escolhida no Bloco 2 precisa ter instrumentação leve para gerar os três primeiros sinais sem acrescentar custo de infra além do orçamento do RNF-012.

## 10. Como o ADR-0001 consome este arquivo

O ADR-0001 precisa, em sua seção "análise das alternativas", marcar explicitamente como cada candidata de stack atende ou viola cada uma das 9 seções acima. Candidata que viole **qualquer uma** das decisões duras das §§1, 2, 6, 8 e 9 é descartada antes do comparativo final. Candidata que viole §3, §4, §5 ou §7 pode ser discutida, com justificativa forte e ADR de amendment formalizado.

## 11. Itens que este arquivo deliberadamente não decide

Este documento fixa padrões arquiteturais pré-stack. Ele **não** define:

- Linguagem de programação, framework web, ORM específico, motor de banco de dados (PostgreSQL vs MariaDB vs outro) — fica para o ADR-0001.
- Biblioteca de autenticação, provedor OAuth específico, algoritmo concreto de hash de senha — fica para o ADR-0001 e para o slice de auth.
- Ferramenta de CI, provedor de bucket de backup off-site, gestor de segredos — fica para Bloco 2 item 2.3 e para `secrets-policy.md` (T2.7, bloqueado até Bloco 2 fechar).
- Formato de serialização na API interna — fica para o slice de API.

A separação é deliberada: as decisões de padrão aqui envelhecem devagar e permanecem verdadeiras mesmo quando a stack for trocada. As decisões de tecnologia envelhecem rápido e ficam em ADR dedicado para poder ser revisadas sem tocar neste documento.
