# Plano técnico do slice 005 — Healthcheck endpoint `/health`

**Gerado por:** architect sub-agent
**Status:** draft
**Spec de origem:** `specs/005/spec.md`

---

## Contexto de estado atual

Slices 001 (scaffold Laravel 13), 002 (PostgreSQL + Redis), 003 (CI pipeline) e 004 (deploy staging) estão mergeados em `main`. Este slice adiciona o endpoint `GET /health` que inspeciona ativamente a conectividade com banco e Redis, retornando JSON estruturado com HTTP 200 (tudo ok) ou HTTP 503 (degradado). Não introduz nova dependência de pacote — usa primitivas nativas do Laravel.

---

## Decisões arquiteturais

### D1: Estrutura do controller — invocável vs resource vs closure na rota

**Opções consideradas:**
- **Opção A: Controller invocável (`__invoke`)** — uma única classe com um único método público; prós: sem método morto, totalmente tipável, PHPStan nível 8 satisfeito sem anotações extras, padrão Laravel para ações pontuais; contras: nenhum relevante para este escopo.
- **Opção B: Resource controller (`HealthCheckController@show`)** — prós: convencional; contras: gera 6 métodos vazios não utilizados, PHPStan nível 8 reporta métodos sem retorno tipado — esforço desnecessário.
- **Opção C: Closure direta em `routes/web.php`** — prós: mínimo de arquivos; contras: não testável por injeção, PHPStan não analisa closures de rota com o mesmo rigor, viola separação de responsabilidades mínima exigida pelo spec.

**Escolhida:** A (controller invocável)

**Razão:** O spec declara explicitamente "controller invocável". PHPStan nível 8 (AC-005) é satisfeito de forma natural com uma classe de método único totalmente tipada. Testável de forma isolada sem instanciar o kernel HTTP completo.

**Reversibilidade:** fácil — renomear método e ajustar rota.

---

### D2: Verificação de conectividade com o banco — `DB::select('SELECT 1')` vs `DB::connection()->getPdo()` vs `DB::statement`

**Opções consideradas:**
- **Opção A: `DB::select('SELECT 1')`** — executa query real; prós: detecta falhas de conexão, autenticação e servidor simultaneamente; contras: gera entrada no query log, ~1 ms adicional — irrelevante em prática.
- **Opção B: `DB::connection()->getPdo()`** — tenta obter a conexão PDO subjacente; prós: não executa query; contras: PDO pode ser criado lazy em algumas versões do driver, não detectando falha de autenticação com certeza.
- **Opção C: `DB::statement('SELECT 1')`** — similar ao A, retorna bool em vez de array; prós: semântica clara; contras: equivalente ao A na prática, diferença mínima.

**Escolhida:** A (`DB::select('SELECT 1')`)

**Razão:** Detecta a maior classe de falhas (conexão + autenticação + servidor). A sobrecarga de ~1 ms é aceitável para um endpoint de monitoramento. Envolto em `try/catch` conforme mitigação de risco do spec.

**Reversibilidade:** fácil — alterar uma linha no controller.

---

### D3: Verificação de conectividade com Redis — `Redis::ping()` vs `Cache::store('redis')->get()` vs `Redis::connection()->ping()`

**Opções consideradas:**
- **Opção A: `Redis::ping()`** — comando mais leve possível no protocolo Redis; prós: direto, mínimo overhead; contras: pode lançar `\RedisException` (phpredis) ou `\Predis\Connection\ConnectionException` (predis) — coberto por catch amplo.
- **Opção B: `Cache::store('redis')->get('__health__')`** — passa pela camada de Cache do Laravel; prós: abstração uniforme; contras: serialização desnecessária; `null` retornado tanto quando Redis está up (chave ausente) quanto down — não distingue sem try/catch de qualquer forma.
- **Opção C: `Redis::connection('default')->ping()`** — explícito sobre qual conexão usar; prós: sem ambiguidade; contras: verboso sem benefício adicional sobre a Opção A.

**Escolhida:** A (`Redis::ping()` com try/catch amplo)

**Razão:** Mínimo overhead, detecta falha de conexão com certeza. O `catch (\Exception $e)` cobre tanto `\RedisException` (phpredis) quanto `\Predis\Connection\ConnectionException` (predis) sem acoplamento ao driver. ADR-0001 não especifica phpredis vs predis — o catch amplo é a mitigação correta.

**Reversibilidade:** fácil — alterar uma linha no controller.

---

### D4: Rate limiting — driver `array` vs driver Redis vs sem rate limit

**Opções consideradas:**
- **Opção A: Middleware dedicado com `Cache::store('array')`** — in-memory por processo; prós: sem dependência de Redis, evita dependência circular (se Redis estiver down, o rate limiter de `/health` não falha antes de chegar ao controller); contras: estado não compartilhado entre workers PHP-FPM — aceitável para proteção básica.
- **Opção B: Rate limiter global com driver Redis** — prós: estado compartilhado entre todos os workers; contras: cria dependência circular crítica — se Redis estiver down, a requisição que deveria reportar "Redis down" pode ser bloqueada pelo rate limiter antes de chegar ao controller.
- **Opção C: Sem rate limiting** — prós: sem complexidade; contras: endpoint exposto a flood sem proteção mínima.

**Escolhida:** A (middleware dedicado com driver `array`)

**Razão:** O spec declara explicitamente "rate limiting com driver array (não Redis) para evitar dependência circular". A ausência de estado compartilhado entre workers é aceitável: o objetivo é proteção básica contra flood acidental, não controle preciso entre processos.

**Reversibilidade:** fácil — alterar o store do middleware ou migrar para limiter global.

---

### D5: Localização da rota — `routes/web.php` vs `routes/api.php` vs arquivo dedicado

**Opções consideradas:**
- **Opção A: `routes/web.php`** — declarado no spec; GET não tem problema de CSRF; middleware `HealthCheckRateLimit` aplicado diretamente na rota; prós: consistente com o spec.
- **Opção B: `routes/api.php`** — rotas API já têm `throttle:api` por padrão mas sem CSRF; prós: semanticamente mais correto para endpoint JSON sem estado; contras: o spec especifica `routes/web.php` explicitamente — não inventar requisito diferente.
- **Opção C: Arquivo dedicado `routes/health.php`** — prós: isolamento total; contras: requer registro em `bootstrap/app.php`, overhead desnecessário para uma rota.

**Escolhida:** A (`routes/web.php` conforme spec)

**Razão:** O spec mapeia explicitamente `routes/web.php`. GET não tem problema de CSRF. O middleware `HealthCheckRateLimit` é aplicado diretamente na rota. Seguir o spec sem inventar requisito.

**Reversibilidade:** fácil — mover a rota de arquivo.

---

## Mapeamento AC → arquivos

| AC | Descrição | Arquivos tocados | Teste principal |
|---|---|---|---|
| AC-001 | HTTP 200 + `"status": "ok"` quando banco e Redis up | `app/Http/Controllers/HealthCheckController.php`, `routes/web.php` | `tests/Feature/HealthCheckTest.php` — cenário ok |
| AC-002 | JSON com campos `status`, `db`, `redis`, `timestamp` | `app/Http/Controllers/HealthCheckController.php` | `tests/Feature/HealthCheckTest.php` — campos obrigatórios |
| AC-003 | HTTP 503 + `"status": "degraded"` quando banco indisponível | `app/Http/Controllers/HealthCheckController.php` | `tests/Feature/HealthCheckTest.php` — cenário db-fail |
| AC-004 | `composer test --filter=HealthCheckTest` exit 0, ≥ 3 testes | `tests/Feature/HealthCheckTest.php` | suite completa do arquivo |
| AC-005 | PHPStan nível 8 passa no controller | `app/Http/Controllers/HealthCheckController.php` | `./vendor/bin/phpstan analyse ... --level=8` |

---

## Novos arquivos

- `app/Http/Controllers/HealthCheckController.php` — controller invocável; tenta `DB::select('SELECT 1')` e `Redis::ping()` em blocos try/catch independentes; monta array com `status`, `db`, `redis`, `timestamp`; retorna `response()->json()` com HTTP 200 ou 503.
- `app/Http/Middleware/HealthCheckRateLimit.php` — middleware dedicado; usa `Cache::store('array')` para rate limiting (60 req/min por IP); retorna HTTP 429 com JSON `{"error": "too many requests"}` quando excedido.
- `tests/Feature/HealthCheckTest.php` — testes de feature cobrindo: (1) tudo up → 200 + ok, (2) campos obrigatórios presentes, (3) DB down → 503 + degraded, (4) Redis down → 503 + degraded. Usa mocks de facade do Laravel/Pest para simular falhas sem banco real.

## Arquivos modificados

- `routes/web.php` — adicionar `Route::get('/health', HealthCheckController::class)->middleware(HealthCheckRateLimit::class)`.
- `bootstrap/app.php` — registrar middleware `HealthCheckRateLimit` se o Laravel 13 não o detectar automaticamente pela rota (verificar comportamento do auto-discovery no Laravel 13).

## Schema / migrations

Nenhuma migration neste slice. O endpoint é stateless — não persiste dados.

## APIs / contratos

### GET /health

**Response 200 (tudo ok):**
```json
{
  "status": "ok",
  "db": "connected",
  "redis": "connected",
  "timestamp": "2026-04-12T21:00:00+00:00"
}
```

**Response 503 (degradado):**
```json
{
  "status": "degraded",
  "db": "disconnected",
  "redis": "connected",
  "timestamp": "2026-04-12T21:00:00+00:00"
}
```

**Campos:**
- `status`: `"ok"` | `"degraded"` — `"degraded"` se qualquer componente estiver `"disconnected"`
- `db`: `"connected"` | `"disconnected"`
- `redis`: `"connected"` | `"disconnected"`
- `timestamp`: ISO 8601 com timezone (`now()->toIso8601String()`)

**Headers:** `Content-Type: application/json`. Sem autenticação. Protegido apenas por rate limit.

---

## Tasks numeradas

### TASK-001 — Criar `HealthCheckController` (AC-001, AC-002, AC-003, AC-005)

**Arquivo:** `app/Http/Controllers/HealthCheckController.php`

Estrutura orientadora (implementer deve preservar `declare(strict_types=1)`, classe `final`, retorno `JsonResponse` explicitamente tipado e catch sem variável capturada para PHPStan nível 8):

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        $db    = 'disconnected';
        $redis = 'disconnected';

        try {
            DB::select('SELECT 1');
            $db = 'connected';
        } catch (\Exception) {
            // falha capturada — $db permanece 'disconnected'
        }

        try {
            Redis::ping();
            $redis = 'connected';
        } catch (\Exception) {
            // falha capturada — $redis permanece 'disconnected'
        }

        $status   = ($db === 'connected' && $redis === 'connected') ? 'ok' : 'degraded';
        $httpCode = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status'    => $status,
            'db'        => $db,
            'redis'     => $redis,
            'timestamp' => now()->toIso8601String(),
        ], $httpCode);
    }
}
```

**Checklist PHPStan nível 8:**
- `declare(strict_types=1)` presente.
- Classe `final` sem herança não declarada.
- Retorno `JsonResponse` tipado na assinatura.
- `catch (\Exception)` sem variável — PHP 8.0+, aceito por PHPStan 1.x.

---

### TASK-002 — Criar middleware `HealthCheckRateLimit` (D4)

**Arquivo:** `app/Http/Middleware/HealthCheckRateLimit.php`

Usa `Cache::store('array')` com chave `health_rate_limit_{$request->ip()}`. Limite: 60 requisições por janela de 60 segundos por IP. Retorna `response()->json(['error' => 'too many requests'], 429)` quando excedido. Implementar com `Cache::store('array')->remember` + contador manual ou usando `RateLimiter` do Laravel apontando para o store `array`.

---

### TASK-003 — Registrar rota (AC-001)

**Arquivo:** `routes/web.php`

```php
use App\Http\Controllers\HealthCheckController;
use App\Http\Middleware\HealthCheckRateLimit;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthCheckRateLimit::class);
```

Verificar se é necessário `->withoutMiddleware('web')` para remover cookie/session desnecessários. GET não tem CSRF — não é bloqueante, mas limpar middleware stack melhora latência.

---

### TASK-004 — Criar testes de feature (AC-004)

**Arquivo:** `tests/Feature/HealthCheckTest.php`

Quatro cenários obrigatórios (Pest 4 com `it()` ou `test()`):

1. **ok completo** — mock `DB::select` e `Redis::ping` retornando sucesso → assert HTTP 200 + `json('status') === 'ok'` + `json('db') === 'connected'` + `json('redis') === 'connected'`.
2. **campos obrigatórios** — assert `response->json()` tem as chaves `status`, `db`, `redis`, `timestamp`.
3. **db down** — mock `DB::select` lançando `\Exception('connection refused')` → assert HTTP 503 + `status === 'degraded'` + `db === 'disconnected'` + `redis === 'connected'`.
4. **redis down** — mock `Redis::ping` lançando `\Exception('connection refused')` → assert HTTP 503 + `status === 'degraded'` + `db === 'connected'` + `redis === 'disconnected'`.

Usar `$this->mock(Connection::class)` ou `DB::shouldReceive()` / `Redis::shouldReceive()` para facades. Nenhum teste requer banco ou Redis reais — isolamento total por mocks.

---

## Riscos e mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| `Redis::ping()` lança `\Throwable` puro (não `\Exception`) em versão futura do driver | Baixa | Alto — endpoint retorna 500 em vez de 503 | Se detectado, ampliar catch para `\Throwable`; monitorar changelog do phpredis/predis |
| Rate limiter com driver `array` não compartilha estado entre workers PHP-FPM | Alta (by design) | Baixo — proteção parcial, aceita pelo spec | Documentada explicitamente; aceito pelo PM no spec |
| PHPStan nível 8 rejeitar `catch (\Exception)` sem variável capturada | Baixa | Médio — AC-005 falha | PHP 8.0+ suporta; PHPStan 1.x aceita; confirmar versão instalada no slice 001 |
| `withoutMiddleware` ou middleware stack do grupo `web` interferir com a rota | Baixa | Baixo — detectado no primeiro `$this->getJson('/health')` no teste | TASK-004 testa o endpoint via HTTP — qualquer falha de middleware aparece imediatamente no CI |
| Facade `DB` ou `Redis` mocada globalmente afetar outros testes no mesmo arquivo | Média | Médio — falsos negativos em outros testes | Usar `afterEach(fn() => Mockery::close())` ou escopo de mock por teste com `$this->mock()` |

---

## Dependências de outros slices

| Slice | Dependência | Natureza |
|---|---|---|
| slice-001 | Scaffold Laravel 13: `app/Http/Controllers/`, `routes/web.php`, `bootstrap/app.php`, Pest 4 configurado, PHPStan instalado | Obrigatória — mergeada |
| slice-002 | Facades `DB` e `Redis` configuradas; `config/database.php` com conexão `pgsql` e `config/database.php` com conexão `redis` funcionais | Obrigatória — mergeada |
| slice-003 | CI pipeline executa `composer test` e `phpstan` — ACs 004 e 005 verificados automaticamente no CI | Obrigatória — mergeada |
| slice-004 | Deploy staging — o endpoint estará disponível em `https://staging.kalibrium.com.br/health` após merge (verificação manual opcional, não é AC deste slice) | Complementar — mergeada |

---

## Fora de escopo deste plano (confirmando spec)

- Verificação de storage de arquivos (MinIO/S3)
- Verificação de filas/jobs (Horizon status)
- Autenticação no endpoint `/health`
- Métricas Prometheus ou OpenMetrics
- Dashboard de monitoramento
- Verificação de uso de disco ou memória
- Endpoint `/ready` separado (Kubernetes readiness probe)
