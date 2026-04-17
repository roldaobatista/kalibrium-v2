---
slice: "015"
story: E15-S01
epic: E15
title: "Plano técnico — Spike INF-007: Auditoria de reaproveitamento E01/E02/E03 e validação de stack"
lane: L3
status: approved
type: spike
dependencies: []
reqs: ["REQ-SEC-001", "REQ-FLD-001", "REQ-SYN-001"]
adrs: ["ADR-0015", "ADR-0016"]
---

# Slice 015 — Plano técnico (Spike INF-007)

> Este é o plano de execução de um **spike de investigação**. A saída são documentos de referência em `docs/frontend/` e um PoC descartável em `spike-inf007/`. **Nenhum código de produção é escrito.** Nenhum arquivo em `app/`, `resources/`, `routes/`, `database/` é alterado.

## 1. Contexto e objetivo técnico

O épico E15 inicia a reconstrução do cliente offline-first (PWA + Capacitor + SQLCipher) conforme ADR-0015, com isolamento multi-tenant conforme ADR-0016. Antes que o scaffold Capacitor comece em E15-S02, duas incertezas precisam ser fechadas:

1. **Superfície de integração do backend existente.** Os épicos E01/E02/E03 (tenants, auth, CRUD cliente/contato) foram merged em Laravel 13. O cliente novo vai consumi-los via REST + JWT. Hoje não há inventário canônico de endpoints mapeando URL, método, payload, auth e tabelas espelhadas localmente — consumir por "tentativa e erro" durante E15-S02 causaria retrabalho.
2. **Compatibilidade mútua da stack declarada.** ADR-0015 fixa versões-alvo (React 18, TypeScript 5, Ionic 8, Capacitor 6, `@capacitor-community/sqlite`, SQLCipher, Vite 5). Conflitos de peer deps e issues abertos de SQLCipher em iOS 17+ / Android 14+ precisam ser validados empiricamente antes de apostar a infra do frontend neles.

O objetivo técnico deste spike é **produzir evidência documental** que desbloqueie E15-S02 sem suposições: dois documentos vivos em `docs/frontend/` e um PoC `spike-inf007/` com `npm install` reprodutível. Se bloqueadores forem detectados, o spike gera recomendação de emenda à ADR-0015 em vez de mascarar o problema.

## 2. Abordagem

Executar em 8 passos numerados, em ordem. Cada passo é individualmente verificável.

1. **Inventário de rotas do backend.** Rodar `grep -rn "Route::" routes/` e `grep -rn "->group" routes/` para listar registros. Abrir `routes/api.php` e `routes/web.php` integralmente. Listar cada rota candidata com: path, método, middleware, controller alvo.
2. **Documentação de cada endpoint.** Para cada rota encontrada no passo 1, abrir o controller/action, extrair: request body esperado (FormRequest ou DTO), response (Resource ou array), status codes emitidos, middleware de auth (`auth:sanctum`, `auth:api`, etc.), policy/gate aplicada. Registrar tudo em `docs/frontend/api-endpoints.md` (seção "Endpoints"). Priorizar endpoints de auth (E02), tenants (E01) e healthcheck — AC-001 exige sem lacunas nesses três.
3. **Mapeamento de tabelas para espelho local.** Varrer `database/migrations/` e o ERD vigente. Listar tabelas que o cliente offline precisará mirrorar em SQLite local (clientes, contatos, usuário-tenant, configurações). Para cada tabela, anotar: colunas essenciais, presença de `tenant_id` (ADR-0016), colunas sensíveis (PII), estratégia de chave primária (UUID vs autoincrement). Registrar em `docs/frontend/api-endpoints.md` seção "Schema local".
4. **Criação do PoC `spike-inf007/`.** Criar diretório `spike-inf007/` na raiz, fora de qualquer caminho de produção. Rodar `npm init -y` para gerar `package.json`. Declarar `"private": true` e adicionar um `README.md` dizendo explicitamente que o diretório é descartável pós-spike. Não commitar `node_modules/` (adicionar a `.gitignore` local do diretório).
5. **Instalação das versões-alvo.** Declarar em `spike-inf007/package.json` as versões exatas de ADR-0015: React 18.x, React-DOM 18.x, TypeScript 5.x, `@ionic/react` 8.x, `@capacitor/core` 6.x, `@capacitor/cli` 6.x, `@capacitor-community/sqlite` (versão compatível com Capacitor 6), Vite 5.x. Rodar `npm install 2>&1 | tee npm-install.log`. Capturar o log completo (sucessos, warnings, erros de peer deps). Se houver conflito, **não usar `--force`**: documentar em `stack-versions.md` seção "Conflitos resolvidos" a decisão (aceitar warning, usar `overrides`, ou recomendar emenda à ADR-0015). Fallback documentado: `--legacy-peer-deps` é aceitável **apenas** se justificado por incompatibilidade conhecida e registrado no log com rationale.
6. **Auditoria de issues de SQLCipher.** Consultar o repositório `@capacitor-community/sqlite` no GitHub. Filtrar issues abertos cujos títulos/labels mencionam: `iOS 17`, `iOS 18`, `Android 14`, `Android 15`, `SQLCipher`, `encryption`, `crash`. Para cada issue relevante: registrar número, título, data de abertura, status, último comentário, workaround se mencionado. Consolidar em `docs/frontend/stack-versions.md` seção "Riscos de plataforma". Declarar verdict explícito: `(a) sem bloqueador — seguir com SQLCipher` ou `(b) bloqueador X identificado — plano B é Y`.
7. **Registro do descarte do frontend antigo.** Executar `find resources/views -name "*.blade.php" | wc -l`, `find resources/js -type f \( -name "*.js" -o -name "*.ts" -o -name "*.vue" \) -not -path "*/node_modules/*" | wc -l` e `find resources/css -type f -not -path "*/node_modules/*" | wc -l`. Listar os caminhos principais encontrados (agrupados por diretório, não arquivo a arquivo). Registrar em `docs/frontend/stack-versions.md` seção "Descarte" com confirmação explícita: nenhum destes arquivos será reaproveitado em E15.
8. **Consolidação do checklist pré-condições E15-S02.** Em `docs/frontend/stack-versions.md` seção "Pré-condições E15-S02", escrever checklist com 6 itens: (a) versões exatas de pacotes validadas, (b) `npm install` limpo comprovado, (c) verdict SQLCipher claro (seguir ou plano B), (d) endpoint de auth E02 testado manualmente (curl + JWT válido), (e) lista de tabelas para mirror SQLite consolidada, (f) descarte do frontend antigo confirmado. Cada item marcado `[x] verificado` com referência à evidência (arquivo:linha ou log) ou `[ ] pendência: <descrição>` com owner.

## 3. Decisões de design (candidatas a ADR)

O spike em si não cria ADRs novas — ele **informa** a possível necessidade de emenda à ADR-0015. Decisões a serem explicitamente tomadas e documentadas:

- **Decisão D-1 — Manter ou recomendar emenda à ADR-0015 sobre SQLCipher.** Se o passo 6 detectar bloqueador de plataforma confirmado, o spike não implementa plano B; ele **recomenda** emenda à ADR-0015 e escala ao PM via `/explain-slice 015`. Plano B candidato (a ser validado no próprio E15-S06, fora deste spike): SQLite puro (`@capacitor-community/sqlite` sem o modo cipher) + criptografia de campos sensíveis em camada de aplicação usando libsodium (`tweetnacl` ou `@noble/ciphers`) com chave derivada do JWT + PBKDF2. Custo: criptografia por-registro em vez de encryption-at-rest transparente, perda do wipe automático. Reversibilidade: **média** — troca do cipher manual por SQLCipher se o upstream resolver o issue é ~1 slice futuro.
- **Decisão D-2 — Política de `--legacy-peer-deps`.** Se o passo 5 exigir fallback para `--legacy-peer-deps`, o spike documenta qual peer dep específico está em conflito, se é ignorável (warning semântico apenas) ou real (conflito de runtime), e por que o fallback foi escolhido em vez de pinagem via `overrides`. Reversibilidade: **fácil** — ajustável em E15-S02 quando upstream lançar versão corrigida.
- **Decisão D-3 — Formato do inventário de endpoints.** Adotar tabela Markdown (não OpenAPI YAML nesta fase) em `docs/frontend/api-endpoints.md`. Rationale: o cliente é o único consumidor interno; gerar OpenAPI formal é investimento desproporcional antes de E15-S02. Reversibilidade: **fácil** — converter Markdown para OpenAPI quando Scramble for adotado (backlog E17+).

Nenhuma dessas decisões bloqueia o spike. Todas são registradas em `docs/frontend/stack-versions.md` seção "Decisões de design do spike" com reversibilidade declarada.

## 4. Arquivos a criar / alterar

### A criar

| Caminho | Tipo | Descrição |
|---|---|---|
| `docs/frontend/api-endpoints.md` | novo | Inventário de endpoints E01/E02/E03 + schema local (AC-001, AC-004). |
| `docs/frontend/stack-versions.md` | novo | Versões validadas, riscos SQLCipher, descarte frontend antigo, pré-condições E15-S02 (AC-002, AC-003, AC-005, AC-006). |
| `spike-inf007/package.json` | novo | PoC declarando versões exatas de ADR-0015. |
| `spike-inf007/package-lock.json` | gerado | Produzido por `npm install`; commitado para reprodutibilidade. |
| `spike-inf007/npm-install.log` | novo | Captura integral do stdout+stderr do `npm install` (AC-002). |
| `spike-inf007/README.md` | novo | Explica que o diretório é PoC descartável e documenta como reproduzir o `npm install`. |
| `spike-inf007/.gitignore` | novo | Ignora `node_modules/` dentro do PoC. |

### A NÃO alterar (proibido no spike)

- Qualquer arquivo em `app/` (Controllers, Models, Services, Actions, Requests, Resources, Policies).
- Qualquer arquivo em `resources/` (Blade, Livewire, Vue, JS, CSS legados).
- Qualquer arquivo em `routes/` (`api.php`, `web.php`, `channels.php`, `console.php`).
- Qualquer arquivo em `database/` (migrations, seeders, factories).
- Qualquer arquivo em `.claude/`, `scripts/hooks/`, `docs/protocol/`, `docs/constitution.md`, `CLAUDE.md` (selados).
- ADR-0015 e ADR-0016 (se precisarem emenda, gerar nova ADR via `/adr`, fora deste slice).

### Opcional (somente se o PoC for integralmente inline)

Se o executor decidir não manter `spike-inf007/` permanentemente (opção prevista no spec), os arquivos `spike-inf007/*` podem ser descartados após coleta do `npm-install.log`, desde que o log completo seja colado em `stack-versions.md` seção "Evidência `npm install`". A recomendação do plano é **manter `spike-inf007/`** por reprodutibilidade.

## 5. Riscos e mitigações

Consolidados do spec + riscos técnicos específicos da execução do spike.

- **R1 (do spec) — Issues de SQLCipher em iOS 17+/Android 14+ podem ser bloqueadores.**
  Mitigação: passo 6 é obrigatório antes de qualquer afirmação "seguir com SQLCipher". Se bloqueador for confirmado, D-1 (plano B libsodium) é registrado em `stack-versions.md` e o spike recomenda emenda à ADR-0015 via `/explain-slice 015` → PM decide antes de E15-S02 começar.
- **R2 (do spec) — Backend pode ter endpoints não documentados.**
  Mitigação: passo 1 usa `grep` direto em `routes/` como fonte primária, não documentação derivada. Se algum controller resolver rotas dinamicamente (raro em Laravel, mas possível via `Route::resource` ou `Route::apiResource`), expandir no passo 2 listando o set completo de métodos gerados.
- **R3 (do spec) — Versões declaradas em ADR-0015 podem estar em conflito de peer deps.**
  Mitigação: passo 5 captura o log integral. Não mascarar com `--force`. Ordem de fallback: (1) aceitar warning documentado, (2) usar `overrides` em `package.json` com rationale, (3) `--legacy-peer-deps` com rationale explícito, (4) recomendar emenda à ADR-0015 se incompatibilidade for real de runtime.
- **R4 (novo) — `npm install` pode falhar por ambiente local (versão do Node, proxy, registry).**
  Mitigação: registrar no `spike-inf007/README.md` a versão do Node usada (target: Node LTS 20.x) e o registry (default `registry.npmjs.org`). Se o ambiente do executor for incompatível, registrar pendência explícita em AC-006 com owner "quem executar E15-S02".
- **R5 (novo) — Issues do GitHub podem ter sido fechados/renomeados entre a leitura e a redação.**
  Mitigação: no passo 6, registrar data da consulta (YYYY-MM-DD) e permalink ao issue no formato `https://github.com/capacitor-community/sqlite/issues/NNNN`. Se o link quebrar no futuro, número + título são suficientes para re-busca.
- **R6 (novo) — Inventário de endpoints pode ficar desatualizado rapidamente.**
  Mitigação: documento marca na seção "Validade" que o snapshot é do commit HEAD no momento do spike. E15-S02 deve re-validar antes de começar (item do checklist AC-006).

## 6. Rollback / plano B

Como o spike não altera produção, rollback é trivial: `git revert` do commit do spike remove os artefatos. Não há estado persistido em banco, filas, ou integrações externas.

Plano B operacional conforme resultado do spike:

- **Caso A — Tudo verde (SQLCipher OK, `npm install` limpo, endpoints mapeados):** merge do slice; E15-S02 começa imediatamente.
- **Caso B — Bloqueador SQLCipher confirmado:** slice **ainda é merged** (a evidência documental é o entregável), mas em vez de E15-S02 seguir automaticamente, orchestrator invoca `/explain-slice 015` com recomendação clara ao PM: "SQLCipher inviável, plano B proposto é libsodium por-campo conforme D-1 de `stack-versions.md`, exige emenda à ADR-0015 antes de E15-S06". PM decide se aceita plano B ou se convoca spike adicional.
- **Caso C — Conflito irreconciliável de peer deps:** mesmo tratamento do Caso B — merge do slice com relatório, escalar ao PM para decidir se congela versões alternativas (ex: downgrade Ionic 8 → 7) ou se aceita pinning via `overrides`.
- **Caso D — Endpoints insuficientes para fluxo offline:** registrar cada lacuna como débito técnico em `project-state.json[technical_debt]` com severidade S3 (não bloqueia E15-S02; cada épico posterior de CRUD no cliente cria o endpoint faltante quando precisar dele).

## 7. Critérios de "done"

Mapeamento 1:1 dos 6 ACs do spec. Cada item é verificável mecanicamente.

| AC | Verificação mecânica | Evidência esperada |
|---|---|---|
| AC-001 | `test -f docs/frontend/api-endpoints.md && grep -c "^## " docs/frontend/api-endpoints.md` retorna ≥ 2 e o arquivo lista todos os endpoints de auth/tenants/healthcheck | `docs/frontend/api-endpoints.md` com seções "Endpoints" e "Schema local" preenchidas |
| AC-002 | `test -f docs/frontend/stack-versions.md && test -f spike-inf007/npm-install.log && ! grep -E "ERR!|ERROR" spike-inf007/npm-install.log` | Log de `npm install` sem `ERR!` e versões declaradas em `stack-versions.md` |
| AC-003 | `grep -A 20 "Riscos de plataforma" docs/frontend/stack-versions.md` retorna tabela de issues com verdict explícito `(a)` ou `(b)` | Seção "Riscos de plataforma" com verdict binário registrado |
| AC-004 | `grep -A 50 "Schema local" docs/frontend/api-endpoints.md` lista tabelas + coluna `tenant_id` para cada | Tabela em Markdown com colunas "tabela", "finalidade", "tenant_id", "colunas PII" |
| AC-005 | `grep -A 30 "Descarte" docs/frontend/stack-versions.md` contém os paths de `resources/views`, `resources/js`, `resources/css` com contagem | Saída dos comandos `find` do passo 7 transcrita no documento |
| AC-006 | `grep -A 20 "Pré-condições E15-S02" docs/frontend/stack-versions.md` contém 6 itens de checklist, cada um `[x]` ou `[ ] pendência:` | Checklist completo sem itens em branco (todos resolvidos ou com pendência explícita) |

Gate global do slice: nenhum arquivo de produção alterado. Verificação: `git diff --name-only main...HEAD | grep -vE "^(docs/frontend/|spike-inf007/|specs/015/|project-state.json)"` deve retornar vazio.

## 8. Dependências externas

- **Nenhuma dependência externa de runtime.** O spike é 100% offline em termos de produção.
- **Dependências de execução do spike:**
  - Node.js LTS 20.x instalado localmente (para `npm install` do passo 5).
  - Acesso à internet para (a) `npm install` baixar pacotes de `registry.npmjs.org`, (b) consulta de issues no GitHub no passo 6.
  - Se a máquina do executor estiver offline, o spike pode ser parcialmente executado (passos 1, 2, 3, 7 funcionam offline com o repositório). Passos 5 e 6 ficam como pendência explícita no AC-006.

## 9. Observabilidade

N/A para este spike. Nenhum código de produção é escrito, portanto não há logs estruturados, métricas ou alertas a configurar. O único log relevante é `spike-inf007/npm-install.log` (evidência de AC-002), que não é observabilidade de produto — é evidência de gate.

## 10. Segurança

- **Nenhum dado real entra no PoC.** `spike-inf007/` não recebe dumps de banco, credenciais, tokens reais, ou PII. O PoC é vazio em termos de dados — só prova `npm install`.
- **Nenhum secret commitado.** `spike-inf007/package.json` não inclui tokens de registry privado. `npm-install.log` é revisado antes do commit para remover eventuais tokens que o npm tenha logado (raro, mas auditável).
- **Issues consultados no GitHub são públicos.** Não há acesso a repositórios privados, tokens do GitHub API, ou rate limits autenticados — uso apenas do fetch público.
- **`.gitignore` em `spike-inf007/`** impede commit acidental de `node_modules/` (ruído + potencial para vazar paths locais da máquina do executor).
- **Lembrete ao executor:** se o PoC evoluir para testar conexão real com o backend em runtime (fora do escopo deste spike, mas tentação natural), **parar** — isso vira E15-S02, não spike. O spike termina em `npm install` + documentação.

---

**Rastreabilidade:**
- Spec: `specs/015/spec.md`
- Story: `epics/E15/stories/E15-S01.md`
- ADRs: ADR-0015 (stack offline-first), ADR-0016 (multi-tenant)
- REQs: REQ-SEC-001, REQ-FLD-001, REQ-SYN-001
- Desbloqueia: E15-S02 (scaffold Capacitor), E15-S06 (schema SQLite local)
