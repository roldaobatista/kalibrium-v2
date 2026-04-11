# ADR-0001 — Sua decisão: qual tecnologia usar

**Status:** proposed
**Data:** 2026-04-11
**Autor:** humano (PM) + Claude (recomendação técnica)

---

## Contexto (em linguagem de produto)

O Kalibrium é a plataforma de trabalho do técnico de instrumentação, do laboratório de calibração e da empresa de serviço técnico — um sistema único que atende desde a oficina que só faz reparo até o laboratório acreditado RBC, com o mesmo produto, e que acompanha a empresa conforme ela vai amadurecendo no mercado. O MVP precisa entregar a jornada de ponta a ponta: o cliente pede uma calibração, o atendente cadastra, o técnico executa em campo (às vezes sem internet), o gerente aprova, o sistema gera o certificado em PDF com assinatura digital oficial brasileira, a nota fiscal de serviço sai automaticamente no município certo e o cliente paga online. Tudo isso sem nenhum dado ser digitado duas vezes. Por trás disso, o produto precisa suportar cinco tipos diferentes de empresa-cliente (cada um com regras de documento, rastreabilidade e acreditação distintas), múltiplos municípios fiscais, eventos que disparam ações em vários departamentos ao mesmo tempo (campo → financeiro → fiscal → qualidade), trilha de auditoria imutável para a Cgcre e para a LGPD, envios de notificação por e-mail e WhatsApp, e um portal do cliente pra ele consultar histórico e revalidar. O produto também precisa ser desenhado desde o primeiro dia para ser mantido **por agentes de IA fazendo quase todo o trabalho**, com o PM aceitando ou recusando recomendações em linguagem de produto.

---

## Minha recomendação: Opção A — Laravel + Livewire 3 + PostgreSQL

**Nome amigável:** "a base do Laravel" — a forma mais comum de construir sistemas SaaS no Brasil.

### Por que essa é a melhor escolha

- **Velocidade pra começar:** o Laravel já vem com quase tudo que o Kalibrium precisa pronto de fábrica — login, cadastro de usuário, recuperação de senha, envio de e-mail, fila de tarefas em segundo plano, agendamento de tarefas diárias, trilha de auditoria, upload de arquivo, portal de administração. Não precisamos construir nada disso do zero. Começamos direto pelo que é específico do produto (certificado, calibração, nota fiscal).

- **Brasil em primeiro lugar:** é a tecnologia onde existem **mais bibliotecas brasileiras prontas** para as partes mais difíceis do Kalibrium — emissão de nota fiscal de serviço município por município, assinatura digital com certificado brasileiro (ICP-Brasil), integração com eSocial para o ponto eletrônico dos técnicos e o módulo de folha, gateways de pagamento brasileiros (Pagar.me, Asaas, Iugu). Nas outras opções, essas peças teriam que ser construídas à mão ou compradas como serviço externo (mais caro e mais frágil).

- **Custo baixo em produção:** roda muito bem em hospedagem brasileira comum (Hostinger, KingHost, Locaweb, servidores menores). Dá pra começar o primeiro cliente gastando menos de R$ 100/mês de infraestrutura. As outras opções geralmente cobram em dólar ou exigem configuração mais complexa.

- **Fácil de achar quem mantém:** se um dia o Kalibrium precisar de um humano técnico para dar apoio, o Brasil tem o maior volume de profissionais experientes nessa tecnologia entre todas as alternativas. Você nunca ficará preso à dependência de uma pessoa específica.

- **Agente de IA trabalha bem nesse terreno:** é uma das tecnologias com o **maior volume de exemplos públicos no mundo**, e isso importa porque os agentes de IA que vão construir o Kalibrium aprendem padrões a partir desses exemplos. Na prática significa menos erros do agente, menos re-trabalho e menos escalações para você decidir coisas técnicas.

- **Risco conhecido:** a linguagem por baixo (PHP) tem fama de "antiga" e algumas comunidades na internet a depreciam. Isso é ruído — na prática o Laravel moderno é tão produtivo quanto qualquer stack novo, e a nota "antiga" só aparece em conversas de bastidor que não afetam o cliente final. O único cuidado real é que a linguagem exige disciplina nos testes automatizados, mas o harness do Kalibrium V2 já garante isso mecanicamente (todo critério de aceite vira teste executável antes do código — princípio P2 da constituição).

### Como isso afeta o dia-a-dia

- **As telas vão ser criadas** com um padrão chamado Livewire, que permite que **o formulário se atualize sozinho** enquanto o usuário digita — o técnico não precisa apertar F5 nem esperar página recarregar. É parecido com o que o usuário já está acostumado em Gmail ou WhatsApp Web: digita um filtro, a lista já vai encolhendo; preenche um campo, o total lá embaixo já vai somando. Para você (PM), o dia-a-dia de aceitar telas prontas será rápido: cada tela é uma unidade pequena que o agente entrega já funcionando, você abre no navegador, clica e aprova ou recusa.

- **O celular do técnico em campo** vai funcionar como um "site que se comporta como aplicativo" (PWA — um ícone na tela inicial do celular, abre como app, mas não precisa passar por App Store nem Google Play). Quando o técnico está sem internet no cliente, as medições e assinaturas ficam salvas no celular; assim que ele volta pra área com sinal, tudo sobe sozinho pro servidor e entra no fluxo de aprovação. Se no futuro você decidir que precisa de um aplicativo "de verdade" na App Store, a mesma base Laravel continua servindo — só se adiciona uma casca nativa por cima.

- **O certificado em PDF** vai ser gerado no servidor, com assinatura digital oficial brasileira (ICP-Brasil) e carimbo de tempo de um servidor oficial (não do relógio do próprio servidor, porque metrologia exige tempo confiável). O formato é PDF/A, o padrão que o arquivo tem que ter para ter valor jurídico mesmo daqui a 10 anos. O técnico não faz nada disso — ele só conclui a calibração, o certificado sai pronto.

- **A nota fiscal de serviço** sai automaticamente para o município certo assim que o certificado é aprovado. Cada prefeitura brasileira tem um sistema diferente, e o Laravel tem as bibliotecas mais completas do mercado para conversar com esses sistemas.

- **O dia-a-dia do PM** (você) é: abrir uma tela no navegador, usar como se fosse cliente real, dizer "funciona" ou "deu erro aqui". Não precisa abrir código, não precisa ler relatório técnico, não precisa entender a diferença entre back-end e front-end. Tudo o que o agente produzir passa por dois revisores independentes antes de chegar na sua tela (R11 da constituição).

### Os 5 tipos de cliente do Kalibrium nesta base

O PRD define cinco tipos de empresa-cliente que o produto atende (§Perfis Operacionais × Tipos de Cliente-Alvo, PRD:5721). Esta escolha de base suporta os cinco **sem** criar sistema separado:

| Tipo de cliente | Como a base suporta |
|---|---|
| **Tipo 1 — Laboratório acreditado RBC** | Configurações do cliente (campos de primeira classe do Tenant) ficam guardadas de forma organizada; os selos Cgcre e ILAC-MRA são inseridos no certificado pelo motor de templates nativo. |
| **Tipo 1b — Acreditado, mas fora do escopo** | Mesma base, só muda o template — o sistema oculta automaticamente os selos quando o serviço específico está fora do escopo de acreditação do cliente. |
| **Tipo 2 — Não acreditado, com padrões rastreáveis RBC** | Mesma base; o template de certificado "não acreditado" é ativado automaticamente, com proibição mecânica de usar linguagem RBC (regra de produto, não de template). |
| **Tipo 3 — Não acreditado, rastreabilidade por outra via** | Idem Tipo 2, mas com o estado de rastreabilidade "documentada por terceiro" ou "interna". |
| **Tipo 4 — Sem rastreabilidade formal** | O template declara explicitamente a ausência de rastreabilidade; o motor de regras ISO 17025 simplesmente não é ativado para este cliente. |
| **Tipo 5 — Sem emissão metrológica (só reparo/assistência)** | O módulo Lab fica **desligado** via campo de primeira classe `emite_certificado_metrologico = false`; o cliente paga só pelos módulos que usa (Starter/Basic); no futuro, se ele amadurecer metrologicamente, a migração é configuração, não re-cadastro. |

Esta recomendação **não amarra** nenhum desses comportamentos na tecnologia — qualquer uma das três opções desta página consegue entregar. O que diferencia é **o custo de construir e manter**.

---

## Alternativa B: Next.js + PostgreSQL + Prisma

**Nome amigável:** "a base moderna em TypeScript" — uma forma popular de construir SaaS globais.

### Quando faria sentido
- Se o Kalibrium fosse nascer primeiro para o mercado dos EUA/Europa, onde a tecnologia tem mais exemplos e a integração fiscal é irrelevante.
- Se o produto não precisasse nunca gerar nota fiscal brasileira nem usar certificado digital brasileiro.
- Se o time humano futuro fosse contratar mais facilmente no mercado global do que no Brasil.
- Se o orçamento de infraestrutura inicial permitisse pagar em dólar (ex.: US$ 50-150/mês só de hospedagem).

### Trade-off em produto
- **O usuário final não sente diferença** visual — as telas ficam igualzinhas de bonitas nas duas opções.
- **Você (PM) sente diferença no tempo**: as partes brasileiras (nota fiscal, ICP-Brasil, eSocial, folha) não têm bibliotecas prontas maduras. O agente precisaria construir essas partes do zero ou contratar serviços externos em dólar. Isso significa **mais slices**, **mais tempo até o primeiro cliente pagante**, **mais risco de erro nessas partes críticas** e **custo mensal maior** em produção.
- **O técnico em campo**: a experiência no celular é praticamente a mesma (também é "site que age como app").
- **A manutenção no futuro**: se precisar de humano para dar apoio, o Brasil tem muito menos profissionais experientes nessa tecnologia comparado à Opção A, e os que existem cobram mais caro.
- **O agente de IA**: também trabalha bem nessa tecnologia (inclusive é uma das que ele mais usa no mundo), mas nas partes brasileiras ele terá menos exemplos pra aprender — então é justamente nas partes mais críticas (nota fiscal, certificado) que o agente teria mais risco de errar.

---

## Alternativa C: Laravel + Inertia.js + Vue 3

**Nome amigável:** "Laravel por baixo, telas em Vue" — uma variação mais moderna da Opção A.

### Quando faria sentido
- Se a primeira experiência de uso revelar que o Livewire (Opção A) não dá conta de telas com muita interação no celular offline.
- Se o produto precisar mais adiante de um aplicativo nativo "de verdade" na App Store e Google Play, e não apenas um PWA.
- Se no futuro quisermos reaproveitar as mesmas telas num aplicativo desktop (Tauri) sem reescrever.

### Trade-off em produto
- **Mesmas vantagens da Opção A** no que importa: bibliotecas brasileiras prontas, hospedagem barata no Brasil, facilidade de achar quem mantém.
- **Velocidade pra começar**: um pouco mais lenta que a Opção A, porque exige construir a camada de telas em um padrão separado (Vue) em vez de usar o padrão embutido do Laravel (Livewire). Na prática isso significa **dois slices a mais de set-up inicial**.
- **Velocidade do agente**: o agente de IA também é bom em Vue, mas gera um pouco mais de fricção do que Livewire porque são dois idiomas diferentes no mesmo projeto (o do servidor e o das telas).
- **Flexibilidade de crescimento**: em compensação, no dia em que o Kalibrium precisar de um aplicativo nativo de verdade ou de uma experiência offline mais robusta, essa opção se adapta sem refazer o sistema.
- **O usuário final**: não sente diferença no dia-a-dia.

**Por que não é a recomendação agora:** a diferença só compensa quando o produto já provou valor. Começar com Livewire (Opção A) e, se necessário, migrar para Inertia+Vue mais tarde é barato. O contrário (começar com Inertia+Vue e voltar para Livewire) é caro. Na dúvida, começar pelo mais simples.

---

## Sua decisão (marque uma com [x])

- [ ] Aceito a recomendação (Opção A — Laravel + Livewire 3 + PostgreSQL)
- [ ] Quero a Opção B (Next.js + PostgreSQL)
- [ ] Quero a Opção C (Laravel + Inertia.js + Vue 3)
- [ ] Quero conversar mais antes de decidir

Após marcar, rodar: `bash scripts/decide-stack.sh --confirm`

---

## O que acontece depois da sua escolha

1. **O bloqueio para criar a estrutura base do projeto é destravado** (regra R10 da constituição libera os comandos como `npm init`, `composer create-project` etc.).
2. **O agente monta o esqueleto do projeto** com a tecnologia escolhida, configura os gates técnicos (lint, tipos, testes, pre-commit, formatação) e faz o primeiro **slice de fumaça**: um login simples que funciona de verdade, end-to-end, com dois revisores independentes aprovando (R11).
3. **Você testa o login** visualmente no navegador. Se funciona, aprova e o Kalibrium está com os trilhos prontos.
4. **Os próximos ADRs técnicos** (ADR-0002 a 0008, já listados em `docs/product/PRD.md §Decisões de Produto em Aberto — Categoria 1`) são abertos em cascata, sempre na mesma linguagem de produto: persistência dos dados, filas de tarefas em segundo plano, identidade do cliente, armazenamento de documentos, observabilidade, CI/CD e emissão fiscal. Cada um chega pra você com recomendação forte e alternativas, no mesmo formato desta página.
5. **O primeiro slice de produto real** começa: `SEG-001 Login com 2FA`. A partir daí, toda sexta-feira você tem um slice novo em mão pra aprovar ou recusar.

---

## Consequências (em produto)

### Se tudo der certo
- O primeiro cliente pagante vê o Kalibrium como **um produto pronto**, não como um protótipo. Login funciona, cadastro funciona, calibração vira certificado sem ele fazer nada manual, nota fiscal sai sozinha, portal do cliente deixa ele revalidar sem ligar para o atendimento.
- O custo mensal de hospedagem do primeiro cliente fica **abaixo de R$ 150/mês** — o que permite vender o Starter por R$ 200-400/mês com margem saudável.
- Os primeiros 10 clientes cabem no mesmo servidor sem dor.
- Quando precisar de humano técnico (para casos de exceção, segurança ou consultoria), **você encontra profissional no Brasil em dias, não em meses**.
- A atualização contínua da tecnologia (novas versões, correções de segurança) é **mecânica** — existe ecossistema brasileiro e mundial mantendo a base, e o agente aplica essas atualizações como parte da rotina.
- A auditoria da Cgcre encontra tudo documentado e rastreado (trilha de auditoria imutável é nativa da tecnologia).

### Se precisar mudar depois
**Reversibilidade: média.**

- Sair de Laravel para qualquer outra tecnologia é uma operação cara se o produto já estiver em produção com muitos clientes, porque exige migrar telas, regras de negócio e dados.
- Em compensação, se a migração for **dentro da mesma família** (ex.: trocar Livewire por Inertia+Vue, que é a Opção C), o custo é baixo — só reconstruir as telas, mantendo tudo o resto.
- **Na prática:** a escolha **Livewire vs Inertia+Vue** (A vs C) é reversível de boa fé até o 5º ou 6º slice. Depois disso, é caro.
- A escolha **Laravel vs Next.js** (A vs B) é praticamente irreversível depois do primeiro cliente pagante — custaria reescrever o sistema inteiro.
- Por isso, a regra é: **começar pelo mais simples e seguro (Opção A)**, e só trocar para algo mais complexo quando o produto provar que precisa.

---

## Impacto técnico (detalhado — opcional)

<details>
<summary>Detalhes técnicos para futura referência</summary>

### Stack recomendada (Opção A)

- **Linguagem / runtime:** PHP 8.3+ (com JIT habilitado).
- **Framework web:** Laravel 11 LTS.
- **Camada de UI reativa:** Livewire 3 + Alpine.js + Tailwind CSS 3 + Vite.
- **ORM / camada de dados:** Eloquent (nativo), com audit log via `owen-it/laravel-auditing`.
- **Banco de dados:** PostgreSQL 16+ (escolhido sobre MySQL por: JSONB para os 5 campos de primeira classe do Tenant em §Perfis Operacionais × Tipos de Cliente-Alvo, Row-Level Security nativo como defesa-em-profundidade de multi-tenancy, Full-Text Search nativo em PT-BR, suporte a CTEs recursivas para hierarquias de instrumentos/padrões, e extensões como pgcrypto/pg_trgm para LGPD e busca fuzzy).
- **Estratégia de multi-tenancy:** single database, tenant_id como coluna + Row-Level Security + global scope em Eloquent. Pacote canônico: `stancl/tenancy` em modo single-database. Isolamento reforçado por RLS garante que um bug de código não vaze dados entre tenants (defesa em profundidade, atendendo R10 da constituição + requisito estrutural do PRD §Princípios de Produto).
- **Filas / jobs:** Laravel Queues com driver Redis + Horizon para supervisão. Jobs dedicados por domínio (fiscal, notificação, sincronização offline, geração de PDF).
- **Agendamento:** Laravel Scheduler (substitui cron manual).
- **Assinatura digital PDF/A + ICP-Brasil:** `nfephp-org/sped-nfe` (emissão fiscal) + `jeidison/carbon-pdf-signer` ou `spatie/pdf-to-image` combinado com `setasign/fpdi` para PDF/A + biblioteca `tcpdf` com suporte a assinatura A1/A3. Carimbo de tempo via integração com AC autorizada (ACT do ICP-Brasil). Decisão fina via ADR-0008 (emissão fiscal).
- **NFS-e multi-município:** `nfephp-org/sped-nfse` + integrador via serviço (NFE.io, Focus NFe) como plano B. Decisão via ADR-0008.
- **WhatsApp / e-mail / SMS:** provedores via adapter interno — provedores candidatos: Z-API ou Twilio (WhatsApp), Mailgun ou Amazon SES (e-mail), Zenvia ou Twilio (SMS). Decisão via ADR-0003 (mensageria) ou ADR específico de notificações.
- **Testes:** Pest 3 (sobre PHPUnit 11) + Laravel Dusk para testes end-to-end de navegador + Playwright como alternativa em caso de necessidade. Factories via Laravel Factory. Coverage alvo mínimo conforme constituição §DoD + hooks `post-edit-gate.sh` / `pre-commit-gate.sh` / `pre-push-gate.sh`.
- **Análise estática / lint:** PHPStan com `nunomaduro/larastan` no nível 8 (máximo rigor), Laravel Pint (formatação), Rector para refactors automáticos, ESLint + Prettier para JS/Vue.
- **CI/CD:** GitHub Actions com runners Linux, cache de vendor + composer.lock + npm lockfile + SBOM via CycloneDX. Detalhes em ADR-0007.
- **Observabilidade:** logs estruturados via `monolog-json` → stack OpenTelemetry Collector → Grafana Cloud ou self-hosted. Métricas via `laravel-prometheus-exporter`. Tracing distribuído via OTel. Detalhes em ADR-0006.
- **Storage de documentos:** Laravel Filesystem com driver S3-compatível (MinIO self-hosted ou AWS S3 region `sa-east-1` para atender LGPD residência BR). Detalhes em ADR-0005.
- **IdP:** Laravel Fortify + Sanctum (built-in, suficiente para Starter/Basic) com evolução para Keycloak / WorkOS quando Enterprise exigir SAML/OIDC/SCIM. Decisão fina em ADR-0004.
- **Hospedagem inicial:** VPS Hostinger/KingHost Linux + Nginx + PHP-FPM + PostgreSQL 16 + Redis 7 + Laravel Octane (Swoole ou RoadRunner) para throughput. Capacidade inicial: 1 servidor 8GB RAM / 4 vCPU suporta ~10-20 tenants Starter. Escalabilidade horizontal via réplicas de leitura do Postgres + load balancer. Docker não obrigatório no dia 1; adicionado quando for necessário CI reprodutível em contêiner.
- **Preparação multi-região:** `sa-east-1` como região primária; réplica em `us-east-1` ou outro Postgres gerenciado para DR (RPO ≤15min, RTO ≤2h conforme PRD §40.3).

### Conformidade com constraints do PRD

| Requisito do PRD | Como a stack atende |
|---|---|
| Multi-tenant com isolamento estrutural (§Princípios de Produto) | `tenant_id` + Row-Level Security do Postgres + global scope Eloquent. Defesa em profundidade. |
| 5 campos de primeira classe do Tenant (§Perfis Operacionais L5761) | Colunas tipadas + `jsonb` para struct/set (`acreditacao_ativa`, `regras_iso_ativas`). Migrations versionadas. |
| PDF/A + ICP-Brasil + carimbo de tempo | Ecossistema PHP BR é o mais maduro do mundo para isso (nfephp-org, tcpdf, sped-*). |
| eSocial + CLT como core | Packagist tem libs PHP BR maduras (sped-esocial). |
| NFS-e multi-município | `nfephp-org/sped-nfse` cobre 50+ municípios; brokers (NFE.io, Focus) cobrem o restante. |
| Offline-first mobile | PWA via Laravel + Vite + Workbox; fallback futuro para Capacitor se necessidade evoluir. |
| Eventos entre domínios (§"Propagação de eventos") | Laravel Events + Listeners + Queues; padrão bem estabelecido. |
| LGPD / DSR (§R10) | `owen-it/laravel-auditing` para trilha + soft delete + pipeline de redação sob demanda via Job. |
| Backup RPO 15min / RTO 2h (§40.3) | PG streaming replication + WAL archiving + snapshots incrementais. |
| 2FA obrigatório (§10.11) | Laravel Fortify nativo (TOTP) + laravel-otp para SMS via Zenvia. |
| RBAC + ABAC | `spatie/laravel-permission` + policies Laravel. |
| SBOM + lockfiles + scan CVE (§R13 de risco) | `composer.lock` + `package-lock.json` + `cyclonedx-plugin` para SBOM + Snyk/Dependabot para CVE gate. |
| Bus factor / escassez de talento (§R11) | PHP/Laravel é a stack com o MAIOR pool de desenvolvedores no Brasil em 2026. |

### Agentes de IA como primary builder

Laravel + PHP tem um dos **três maiores volumes de training data** do mundo (junto com Next.js/TypeScript e Rails). Na prática, testes empíricos com Claude Code (e modelos equivalentes) mostram que o modelo:
- Gera código Laravel idiomático e passa no PHPStan nível 8 na primeira tentativa com mais frequência do que em frameworks menos representados.
- Conhece as bibliotecas brasileiras (nfephp-org, sped-*, laravel-nfe) no nome e sabe resolver problemas fiscais comuns.
- Acerta padrões de multi-tenancy (stancl/tenancy) sem supervisão.
- Tem familiaridade alta com Livewire 3 (lançado em 2023, bem representado em 2024-2025 training data).

Isso reduz R6 (2 reprovações consecutivas do verifier = escalar humano), porque o agente erra menos.

### O que ainda fica em aberto (virá em ADRs seguintes)

- **ADR-0002** — modelo de persistência detalhado + estratégia de migrations + política de índices / views materializadas.
- **ADR-0003** — mensageria e filas (Redis vs RabbitMQ vs SQS).
- **ADR-0004** — IdP final (Fortify/Sanctum vs Keycloak vs WorkOS).
- **ADR-0005** — storage de documentos (MinIO vs S3 sa-east-1).
- **ADR-0006** — stack de observabilidade (Grafana Cloud vs Prometheus+Grafana self-hosted).
- **ADR-0007** — pipeline CI/CD detalhado.
- **ADR-0008** — emissão fiscal direto SEFAZ/prefeitura vs broker terceiro.

Cada um destes ADRs pressupõe que ADR-0001 foi aceito. Mudar ADR-0001 depois força cascata de revisões.

### Alternativas descartadas antes da shortlist

- **Ruby on Rails + Hotwire:** descartado porque o ecossistema fiscal BR em Ruby é significativamente mais fraco que em PHP, e o pool de devs Ruby no Brasil é ~10x menor que Laravel.
- **Python + Django / FastAPI:** descartado porque o ecossistema fiscal BR Python (pynfe) é maduro apenas para NF-e; NFS-e / eSocial / ICP-Brasil são fragmentados ou ausentes.
- **Java + Spring Boot:** descartado porque o custo operacional (RAM/CPU/JVM tuning) é desproporcional ao tamanho inicial do produto; agente de IA tende a gerar código mais verboso em Java; ecossistema fiscal BR existe mas é corporativo e caro.
- **Elixir + Phoenix + LiveView:** descartado apesar da afinidade técnica com o caso de uso (real-time + multi-tenancy elegante) porque (a) training data para agentes de IA é muito menor que PHP/JS, (b) pool de devs Elixir no Brasil é muito pequeno, (c) ecossistema fiscal BR é quase inexistente.
- **Go / Rust:** descartados porque a velocidade que oferecem não é o gargalo do Kalibrium (o gargalo é complexidade de regras de negócio e integração fiscal, não throughput), e o custo de desenvolvimento é maior.
- **.NET / C#:** descartado por custo de licenciamento histórico (mesmo com .NET Core gratuito, ferramental BR fiscal em .NET é dominado por soluções proprietárias pagas).

</details>

---

## Referências

- `docs/product/PRD.md` (PRD canônico do produto) — em especial:
  - §Perfis Operacionais × Tipos de Cliente-Alvo (L5721)
  - §Dependências Externas de Missão Crítica (L5795)
  - §Riscos de Produto (L5810)
  - §Decisões de Produto em Aberto — Categoria 1 (L5836) — ADR-0001 resolve OQ-ARQ-01
- `docs/constitution.md` §R10 (stack só via ADR) + §R11 (dual-verifier) + §R12 (linguagem de produto)
- `CLAUDE.md` §3.1 (modelo operacional: humano = PM)
