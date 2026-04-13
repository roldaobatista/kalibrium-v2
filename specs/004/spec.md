# Slice 004 — Deploy staging automatizado (GitHub Actions → VPS)

**Story:** E01-S04
**Status:** draft

---

## Contexto

O projeto Kalibrium V2 já tem scaffold Laravel 13 (slice 001), infraestrutura de dados PostgreSQL + Redis (slice 002) e pipeline CI completo (slice 003). Esta slice configura o deploy contínuo: após CI verde em `main`, a aplicação é publicada automaticamente no VPS Hostinger KVM 1 (staging), permitindo que o PM valide qualquer slice no navegador sem ação manual.

## Jornada alvo

Agente faz merge para `main` → CI roda e passa → workflow `deploy-staging.yml` dispara automaticamente → aplicação é copiada via SSH+rsync para o VPS → migrations executam, caches são gerados, Horizon reinicia → PM abre `https://staging.kalibrium.com.br/` no navegador e valida a funcionalidade.

## Acceptance Criteria

- **AC-001:** Push para `main` com CI verde dispara `deploy-staging.yml` automaticamente — verificável na aba Actions com job `deploy` em status `success`.
- **AC-002:** Após deploy, `curl -s https://staging.kalibrium.com.br/` responde HTTP 200 com conteúdo Laravel (ou `grep -c "Laravel"` retorna `1`).
- **AC-003:** `php artisan horizon:status` no VPS retorna `running` após deploy.
- **AC-004:** `tail -1 storage/logs/laravel.json` no VPS mostra linha em formato JSON válido (não texto plano).
- **AC-005:** `php artisan schedule:list` no VPS lista o job de heartbeat e `php artisan schedule:run` executa sem erro.

## Fora de escopo

- Deploy para produção (KVM 4 — pós-MVP)
- Docker / containerização
- Rollback automatizado (rollback manual via `git revert` + re-push)
- Notificações de deploy por Slack/e-mail
- Blue-green deployment
- CDN para assets estáticos

## Arquivos/módulos impactados

- `.github/workflows/deploy-staging.yml`
- `scripts/deploy.sh`
- `infra/nginx/kalibrium-staging.conf`
- `infra/php-fpm/kalibrium-staging.conf`
- `infra/supervisor/horizon-staging.conf`
- `infra/crontab/staging.txt`
- `config/logging.php`
- `composer.json` (adição de `laravel/horizon`)
- `routes/console.php` (job de heartbeat do scheduler)

## Riscos

- VPS KVM 1 pode não ter PHP 8.4 instalado — mitigação: script `infra/scripts/provision-staging.sh`
- Firewall pode bloquear SSH do GitHub Actions — mitigação: verificar porta 22 aberta para IPs do GitHub
- `php artisan migrate --force` pode falhar se banco não provisionado — mitigação: dependência de E01-S02

## Dependências

- E01-S02 completa (PostgreSQL + Redis) ✅
- E01-S03 completa (Pipeline CI) ✅
- VPS Hostinger KVM 1 provisionado (SSH ativo, IP disponível)
- DNS `staging.kalibrium.com.br` apontando para o IP do KVM 1
- Secrets `STAGING_SSH_KEY`, `STAGING_SSH_FINGERPRINT`, `STAGING_HOST`, `STAGING_USER`, `STAGING_PATH` nas GitHub Secrets
