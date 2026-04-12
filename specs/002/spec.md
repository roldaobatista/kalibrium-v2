# Slice 002 — Configurar PostgreSQL 18 + Redis 8

**Story:** E01-S02
**Épico:** E01 — Setup e Infraestrutura
**Status:** spec

---

## Objetivo

Provisionar e conectar o banco de dados PostgreSQL 18 (com Row-Level Security habilitado desde o início) e o Redis 8 ao projeto Laravel. Ao final, migrations rodam sem erro, conexão com banco e cache estão verificáveis programaticamente e o ambiente está pronto para receber o schema de domínio nas stories de negócio.

## Contexto

Segundo slice do projeto. O scaffold Laravel 13 está funcional (slice 001 concluído). As configs de database/cache/queue/session estão nos valores default do Laravel. Este slice configura PostgreSQL como banco principal e Redis como driver de cache, sessão e fila.

## Jornada alvo

N/A — slice de infraestrutura. Nenhuma interface visível ao usuário final. O agente de IA executa a configuração e valida que banco e Redis respondem corretamente.

## Escopo

- Configuração do driver `pgsql` no `config/database.php` com parâmetros de conexão via `.env`
- Migration inicial de verificação (migration de sanidade sem tabelas de negócio)
- Habilitação de RLS no banco: script SQL `database/setup/enable-rls.sql` executado via migration ou artisan command
- Configuração do driver Redis no `config/database.php` e `config/cache.php` (driver `redis` como padrão de cache e sessão)
- Configuração do driver de fila como `redis` no `config/queue.php`
- Configuração do `config/session.php` com driver `redis`
- Artisan command `php artisan db:check` que verifica conexão com PostgreSQL e Redis e retorna exit 0 quando ambos respondem
- `.env.example` atualizado com variáveis Redis: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_DB`
- Documentação inline do script de setup de RLS com comentário explicando a decisão (link para ADR-0001)

## Fora de escopo

- Migrations de tabelas de domínio (Tenant, User, OS etc. — épicos seguintes)
- Configuração de Laravel Horizon (E01-S06)
- Provisionamento do servidor VPS (feito manualmente pelo PM antes do deploy — E01-S04)
- Replicação ou backup (ADR-0005, fora do E01)
- Redis Sentinel ou Cluster

## Acceptance Criteria

- AC-001: `php artisan migrate` executa sem erros em banco PostgreSQL 18 local (ou de staging)
- AC-002: `php artisan db:check` retorna exit 0 e imprime `{"db":"connected","redis":"connected"}` em stdout
- AC-003: `php artisan tinker --execute="DB::statement('SELECT current_setting(\'rls.enabled\', true)')"` retorna sem exception (RLS habilitado na sessão)
- AC-004: `php artisan config:cache` executa sem erros — todas as configs de db/cache/queue/session estão sintaticamente corretas
- AC-005: `php artisan migrate:fresh` executa sem erros e `php artisan migrate:status` lista a migration de sanidade como "Ran"

## Riscos

- PostgreSQL 18 pode requerer extensões (`pgcrypto`, `pg_trgm`) não disponíveis em hosting sem permissão de superusuário — mitigação: verificar disponibilidade no VPS antes do merge
- RLS no Laravel com Eloquent pode gerar overhead de configuração em cada conexão — mitigação: usar `DB::statement('SET row_security = on')` via `AppServiceProvider::boot()` apenas quando em contexto de tenant (implementação real em E02)

## Dependências técnicas

- Slice 001 concluído (scaffold Laravel 13)
- PostgreSQL 18 acessível no ambiente de desenvolvimento
- Redis 8 acessível no ambiente de desenvolvimento
- ADR-0001 aceito (PostgreSQL como banco principal)

## Evidência necessária para aprovação

- Output de `php artisan migrate` com exit 0
- Output de `php artisan db:check` mostrando `{"db":"connected","redis":"connected"}`
- Output de `php artisan migrate:status` listando migration de sanidade como "Ran"
- Output de `composer test` verde (testes de integração do DbCheckCommand)
