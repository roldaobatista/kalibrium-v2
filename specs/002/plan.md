# Plano técnico do slice 002 — Configurar PostgreSQL 18 + Redis 8

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/002/spec.md`

---

## Decisões arquiteturais

### D1: Driver PHP para Redis — phpredis vs Predis

**Opções consideradas:**
- **Opção A: phpredis (extensão C nativa)** — extensão instalada no PHP como .so; prós: máxima performance, suporte nativo a pipelines e conexões persistentes, recomendado para produção; contras: requer instalação via `pecl`/`apt` no servidor, pode não estar disponível no PHP 8.5.5 do ambiente de desenvolvimento sem intervenção manual.
- **Opção B: Predis (pacote Composer puro PHP)** — instala via `composer require predis/predis`; prós: zero dependência de extensão C, funciona em qualquer ambiente imediatamente, gerenciado pelo lockfile; contras: ~20-30% mais lento em operações de alta frequência (irrelevante para o volume do MVP), consome ligeiramente mais CPU em workloads de fila intensiva.

**Escolhida:** B (Predis) como default de desenvolvimento, com caminho claro para phpredis em produção

**Razão:** O spec exige que a conexão Redis funcione verificável e imediatamente no ambiente de desenvolvimento sem intervenção manual além do Composer. O PHP 8.5.5 instalado pode não ter `phpredis` compilado. O Predis atende todos os ACs deste slice (AC-002, AC-004) e o volume do MVP não justifica o delta de performance no ambiente local. Em produção (E01-S04, provisionamento VPS), o phpredis será instalado via script de setup. A transição é transparente para o Laravel: basta alterar `REDIS_CLIENT=phpredis` no `.env` sem trocar código de aplicação.

**Reversibilidade:** fácil — troca de `REDIS_CLIENT` no `.env` + instalação/remoção do pacote Predis via Composer.

---

### D2: Estratégia de habilitação de RLS — migration vs script SQL externo vs AppServiceProvider

**Opções consideradas:**
- **Opção A: Migration dedicada que executa DDL de RLS** — migration `enable_rls_setup` que roda `SET row_security = on` e `set_config('rls.enabled', 'true', false)`; prós: versionado no histórico de schema, reprodutível via `migrate:fresh`; contras: mistura configuração de servidor com schema de domínio, pode gerar confusão sobre o propósito da migration.
- **Opção B: Script SQL `database/setup/enable-rls.sql` invocado por uma migration** — o script é o artefato documentado, a migration apenas o executa via `DB::unprepared(file_get_contents(...))`; prós: separa claramente configuração de servidor (arquivo SQL comentado) de schema de domínio (migrations numeradas), spec prevê explicitamente este arquivo, permite execução manual ou via migration; contras: dependência de caminho de arquivo em runtime da migration.
- **Opção C: AppServiceProvider::boot() com `DB::statement('SET row_security = on')`** — aplica em cada request; prós: zero configuração adicional de banco; contras: aplica RLS globalmente sem tenant ativo (risco de queries quebrando antes de E02 implementar policies reais), e o spec indica que a implementação de RLS por tenant é escopo de E02.

**Escolhida:** B

**Razão:** O spec define explicitamente o arquivo `database/setup/enable-rls.sql` com documentação inline. O AC-003 valida que `current_setting('rls.enabled', true)` não lança exception — não que RLS esteja aplicado em tabelas de negócio (que não existem ainda neste slice). A opção B isola corretamente a configuração de servidor da evolução do schema e produz o artefato documentado exigido pelo spec. A opção C foi descartada por risco de interferência antes de E02 criar policies.

**Reversibilidade:** média — reverter RLS requer SQL manual (`SELECT set_config('rls.enabled', 'false', false)`) e remoção das migrations; após E02 criar policies RLS em tabelas de negócio, reverter seria disruptivo para toda a camada de isolamento de tenant.

---

### D3: Artisan command `db:check` — Command de console vs rota HTTP de healthcheck

**Opções consideradas:**
- **Opção A: Artisan Command `app/Console/Commands/DbCheckCommand.php`** — implementa `php artisan db:check`; prós: atende exatamente o AC-002, testável via `$this->artisan('db:check')` no Pest, sem expor endpoint HTTP público, alinhado com spec; contras: não serve como healthcheck HTTP para load balancer (não é escopo deste slice).
- **Opção B: Rota `/healthz` em `routes/web.php`** — retorna JSON com status de banco e Redis; prós: usável por load balancers e ferramentas de monitoramento externo; contras: expõe endpoint HTTP sem autenticação, não atende o AC-002 que especifica explicitamente `php artisan db:check`, é escopo de observabilidade (ADR-0006 / E01-S06).

**Escolhida:** A

**Razão:** O spec define explicitamente `php artisan db:check` retornando `{"db":"connected","redis":"connected"}` em stdout com exit 0. Rota HTTP é fora de escopo e pertence ao slice de observabilidade. A implementação como Command é testável, versionada e não expõe superfície de ataque. A rota `/healthz` pode ser adicionada em E01-S06 reutilizando a mesma lógica do Command.

**Reversibilidade:** fácil — o Command pode ser estendido ou reutilizado por uma rota futura sem alteração de interface.

---

## Mapeamento AC → arquivos

| AC | Arquivos tocados | Teste principal |
|---|---|---|
| AC-001 | `config/database.php`, `.env`, `database/migrations/0001_01_01_000000_create_sanity_check.php` | `tests/Feature/DatabaseConnectionTest.php` — assert `migrate` sem exception, tabela `_sanity_check` existe |
| AC-002 | `app/Console/Commands/DbCheckCommand.php`, `composer.json` (predis) | `tests/Feature/DbCheckCommandTest.php` — assert exit 0 + output JSON `{"db":"connected","redis":"connected"}` |
| AC-003 | `database/setup/enable-rls.sql`, `database/migrations/0001_01_01_000001_enable_rls_setup.php` | `tests/Feature/DatabaseConnectionTest.php` — assert `current_setting('rls.enabled', true)` sem exception |
| AC-004 | `config/database.php`, `config/cache.php`, `config/queue.php`, `config/session.php` | `tests/Feature/ConfigCacheTest.php` — assert `config:cache` exit 0 |
| AC-005 | `database/migrations/0001_01_01_000000_create_sanity_check.php` | `tests/Feature/DatabaseConnectionTest.php` — assert `migrate:status` lista migration como "Ran" |

---

## Novos arquivos

- `app/Console/Commands/DbCheckCommand.php` — comando `db:check`: verifica PostgreSQL via `DB::select('SELECT 1')` e Redis via `Redis::ping()`, imprime JSON em stdout, exit 0 em sucesso e exit 1 em falha (AC-002)
- `database/setup/enable-rls.sql` — script SQL que executa `SET row_security = on` e `SELECT set_config('rls.enabled', 'true', false)`, com comentário inline explicando a decisão e referência a ADR-0001 (AC-003)
- `database/migrations/0001_01_01_000000_create_sanity_check.php` — migration de sanidade sem tabelas de negócio; cria tabela `_sanity_check (id bigserial primary key, created_at timestamp)` para validar que o runner conecta ao PostgreSQL e registra execução (AC-001, AC-005)
- `database/migrations/0001_01_01_000001_enable_rls_setup.php` — migration que executa `database/setup/enable-rls.sql` via `DB::unprepared(file_get_contents(database_path('setup/enable-rls.sql')))` (AC-003)
- `tests/Feature/DatabaseConnectionTest.php` — testes Pest cobrindo AC-001, AC-003, AC-005
- `tests/Feature/DbCheckCommandTest.php` — testes Pest cobrindo AC-002
- `tests/Feature/ConfigCacheTest.php` — teste Pest cobrindo AC-004

## Arquivos modificados

- `config/database.php` — driver `pgsql` como padrão em `DB_CONNECTION`; parâmetros via `.env` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`); seção `redis` com `'client' => env('REDIS_CLIENT', 'predis')` (AC-001, AC-004)
- `config/cache.php` — `CACHE_STORE` default para `redis`; store `redis` aponta para conexão `default` do `config/database.php` (AC-004)
- `config/queue.php` — `QUEUE_CONNECTION` default para `redis` (AC-004)
- `config/session.php` — `SESSION_DRIVER` default para `redis` (AC-004)
- `.env` — confirmar/ajustar: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=kalibrium`, `DB_USERNAME=postgres`, `DB_PASSWORD=` (vazio), `REDIS_CLIENT=predis`, `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, `REDIS_PASSWORD=null`, `REDIS_DB=0`
- `.env.example` — adicionar: `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, `REDIS_PASSWORD=null`, `REDIS_DB=0`, `REDIS_CLIENT=predis` com comentários de documentação (spec §Escopo)
- `composer.json` — adicionar `"predis/predis": "^2.0"` em `require` (D1)

## Schema / migrations

- `database/migrations/0001_01_01_000000_create_sanity_check.php` — cria tabela `_sanity_check` (id bigserial, created_at timestamp). Não é tabela de negócio; serve apenas para validar conectividade e funcionamento do runner de migrations.
- `database/migrations/0001_01_01_000001_enable_rls_setup.php` — executa o conteúdo de `database/setup/enable-rls.sql`. Comandos são idempotentes (`set_config` pode ser re-executado sem efeito colateral). Nenhum `ALTER TABLE` neste slice — policies RLS por tabela são escopo de E02.

## APIs / contratos

Nenhuma API pública neste slice. O `db:check` é exclusivamente um command de console.

**Output esperado de `php artisan db:check` (stdout):**
```
{"db":"connected","redis":"connected"}
```
Exit code: `0` em sucesso. Exit code: `1` em falha de qualquer conexão, com mensagem de erro em stderr.

---

## Tarefas de execução

| # | Tarefa | ACs atendidos |
|---|---|---|
| T01 | Instalar Predis: `composer require predis/predis`; confirmar que `vendor/predis/predis` existe | D1 |
| T02 | Ajustar `config/database.php`: seção `pgsql` com variáveis de `.env`; seção `redis` com `'client' => env('REDIS_CLIENT', 'predis')` | AC-001, AC-004 |
| T03 | Ajustar `config/cache.php`: `'default' => env('CACHE_STORE', 'redis')` | AC-004 |
| T04 | Ajustar `config/queue.php`: `'default' => env('QUEUE_CONNECTION', 'redis')` | AC-004 |
| T05 | Ajustar `config/session.php`: `'driver' => env('SESSION_DRIVER', 'redis')` | AC-004 |
| T06 | Atualizar `.env` com variáveis Redis e PostgreSQL confirmadas; atualizar `.env.example` com as mesmas variáveis documentadas | AC-002 (indiretamente) |
| T07 | Criar `database/setup/enable-rls.sql` com `SET row_security = on;` e `SELECT set_config('rls.enabled', 'true', false);`, comentário referenciando ADR-0001 | AC-003 |
| T08 | Criar migration de sanidade `0001_01_01_000000_create_sanity_check.php` | AC-001, AC-005 |
| T09 | Criar migration RLS `0001_01_01_000001_enable_rls_setup.php` que executa o script via `DB::unprepared(file_get_contents(database_path('setup/enable-rls.sql')))` | AC-003 |
| T10 | Executar `php artisan migrate` e coletar output + exit code | AC-001 |
| T11 | Criar `app/Console/Commands/DbCheckCommand.php`; registrar no sistema (Laravel 13 autodiscover via `app/Console/Commands/`) | AC-002 |
| T12 | Executar `php artisan db:check` e coletar output JSON + exit code | AC-002 |
| T13 | Executar `php artisan tinker --execute="DB::statement('SELECT current_setting(\'rls.enabled\', true)')"` e coletar output sem exception | AC-003 |
| T14 | Executar `php artisan config:cache` e coletar output + exit code | AC-004 |
| T15 | Executar `php artisan config:clear` (limpar cache após T14); executar `php artisan migrate:fresh` e em seguida `php artisan migrate:status`; coletar output | AC-005 |
| T16 | Escrever `tests/Feature/DatabaseConnectionTest.php`, `tests/Feature/DbCheckCommandTest.php`, `tests/Feature/ConfigCacheTest.php` cobrindo AC-001 a AC-005 | AC-001 a AC-005 |
| T17 | Executar `composer test` e confirmar exit 0 com todos os testes passando | AC-001 a AC-005 |

---

## Riscos e mitigações

- **phpredis não instalado no PHP 8.5.5 local** → mitigação: D1 usa Predis como default (`REDIS_CLIENT=predis`); nenhuma intervenção manual necessária. Se a extensão estiver instalada, o Laravel a usará automaticamente quando `REDIS_CLIENT=phpredis`; este slice não força isso.
- **PostgreSQL 18 com extensões não disponíveis (`pgcrypto`, `pg_trgm`)** → mitigação: este slice não requer extensões — RLS é nativo do core PostgreSQL. As extensões serão verificadas em E02 ao criar o schema de domínio. Incluir nota no `enable-rls.sql` sobre dependências futuras.
- **Distinção entre `SET row_security = on` (GUC de sessão) e `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` (estado da tabela)** → mitigação: o AC-003 valida `current_setting('rls.enabled', true)` que é um GUC customizado definido via `set_config`, não o estado de RLS em tabelas específicas. Documentar claramente no comentário do script SQL que `ALTER TABLE ENABLE ROW LEVEL SECURITY` por tabela é escopo de E02, quando as tabelas de negócio existirem.
- **Migration RLS re-executada em `migrate:fresh`** → mitigação: `set_config` é idempotente; a migration pode ser re-executada sem efeito colateral. Não há `ALTER TABLE` nem criação de policies neste slice.
- **Config cache com driver Redis e Redis indisponível em CI** → mitigação: usar `CACHE_STORE=array`, `SESSION_DRIVER=array` e `QUEUE_CONNECTION=sync` no `.env.testing` para isolar testes de integração da disponibilidade do Redis. Testes do `DbCheckCommand` que precisam de Redis real devem ser marcados como `@group integration` e documentados como dependentes de infraestrutura local.
- **Predis e PHPStan nível 8** → mitigação: verificar se `predis/predis ^2.0` tem stubs PHPStan. Se necessário, adicionar `ignoreErrors` pontual no `phpstan.neon` para métodos dinâmicos do Predis, documentando o motivo. Não relaxar o nível de análise (violaria ADR-0001).

---

## Dependências de outros slices

- `slice-001` — scaffold Laravel 13 com `composer.json`, diretório `config/`, binário `artisan`, estrutura PSR-4 e suite Pest funcional. Este slice pressupõe `composer install` executado e `php artisan` respondendo.

---

## Fora de escopo deste plano (confirmando spec)

- Migrations de tabelas de domínio (Tenant, User, OrdemDeServico etc.) — épicos E02+
- `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` e criação de policies RLS por tabela — E02 (quando tabelas existirem)
- Configuração de Laravel Horizon e supervisão de workers de fila — E01-S06
- Provisionamento do servidor VPS (instalação de PostgreSQL 18 / Redis 8 / Nginx / PHP-FPM) — E01-S04
- Replicação, backup WAL archiving, RPO/RTO — ADR-0005
- Redis Sentinel, Redis Cluster ou alta disponibilidade de Redis
- Rota HTTP `/healthz` para load balancer — ADR-0006 / E01-S06
- Configuração de `search_path` customizado por tenant (`DB_SCHEMA`) — E02
