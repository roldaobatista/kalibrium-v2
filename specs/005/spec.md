# Slice 005 — Healthcheck endpoint `/health`

**Story:** E01-S05
**Status:** draft

---

## Contexto

O projeto Kalibrium V2 tem scaffold Laravel 13 (slice 001), PostgreSQL + Redis (slice 002), CI (slice 003) e deploy staging (slice 004). Esta slice implementa o endpoint `GET /health` que retorna JSON estruturado com status dos componentes críticos (banco e Redis), servindo como evidência objetiva de ambiente íntegro e base para monitoramento futuro.

## Jornada alvo

PM (ou uptime robot) faz `GET /health` em staging → recebe JSON com status de cada componente → se tudo ok, HTTP 200 com `"status": "ok"` → se algo falhar, HTTP 503 com `"status": "degraded"` e componente marcado como `"disconnected"`.

## Acceptance Criteria

- **AC-001:** `curl -s http://localhost:8000/health | jq .status` retorna `"ok"` com HTTP 200 quando banco e Redis estão disponíveis.
- **AC-002:** `curl -s http://localhost:8000/health` retorna JSON com campos `status`, `db`, `redis` e `timestamp` — validável com `jq '. | has("status") and has("db") and has("redis") and has("timestamp")'` retornando `true`.
- **AC-003:** Com banco de dados indisponível, `curl -o /dev/null -w "%{http_code}" http://localhost:8000/health` retorna `503`.
- **AC-004:** `composer test --filter=HealthCheckTest` retorna exit 0 com pelo menos 3 testes passando (ok, db-fail, redis-fail).
- **AC-005:** `./vendor/bin/phpstan analyse app/Http/Controllers/HealthCheckController.php --level=8` retorna exit 0.

## Fora de escopo

- Verificação de storage de arquivos (MinIO/S3)
- Verificação de filas/jobs (Horizon status)
- Autenticação no endpoint
- Métricas Prometheus
- Dashboard de monitoramento

## Arquivos/módulos impactados

- `routes/web.php` (nova rota `/health`)
- `app/Http/Controllers/HealthCheckController.php`
- `app/Http/Middleware/HealthCheckRateLimit.php`
- `tests/Feature/HealthCheckTest.php`
- `bootstrap/app.php` (registro do middleware, se necessário)

## Riscos

- `DB::connection()->getPdo()` pode lançar exception não capturada — mitigação: try/catch com fallback para `"disconnected"`
- Rate limiting com Redis como backend pode criar dependência circular — mitigação: usar driver array no middleware de `/health`
