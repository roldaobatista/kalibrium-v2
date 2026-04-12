# Plano técnico do slice 004 — Deploy staging automatizado (GitHub Actions → VPS)

**Gerado por:** architect sub-agent
**Status:** approved
**Spec de origem:** `specs/004/spec.md`

---

## Contexto de estado atual

Os slices 001 (scaffold Laravel 13), 002 (PostgreSQL + Redis) e 003 (pipeline CI com 7 jobs) estão mergeados em `main`. O CI roda via `ci.yml` e o VPS Hostinger KVM 1 é o ambiente de staging designado (ADR-0001 menciona KVM 1 como ambiente de homologação; ADR-0007 formalizará a estratégia de CI/CD). Este slice configura o deploy contínuo: após `ci.yml` verde em `main`, o workflow `deploy-staging.yml` publica a aplicação no KVM 1 via SSH + rsync, sem Docker (ADR-0001 explicita "Docker não obrigatório no dia 1").

---

## Decisões arquiteturais

### D1: Mecanismo de cópia — rsync via SSH vs `git pull` no servidor

**Opções consideradas:**
- **Opção A: rsync via SSH** — o runner do GitHub Actions copia os arquivos para o VPS; prós: controle total do que vai (exclui `.git/`, `node_modules/` locais, `storage/logs/`), sem credencial do repositório no servidor, idempotente; contras: requer secret `STAGING_SSH_KEY`.
- **Opção B: `git pull` no servidor via SSH** — runner executa `ssh user@host "cd /path && git pull"`; prós: simples; contras: requer repositório clonado no VPS com deploy key ou PAT, `git pull` pode falhar com conflitos de arquivo local editado no servidor, expõe histórico do repositório no document root.
- **Opção C: Plataforma SaaS (Envoyer / Laravel Forge)** — atomic deploys, rollback built-in; contras: custo mensal adicional (~US$ 10-20/mês), lock-in, contradiz o princípio de infraestrutura mínima para MVP definido em ADR-0001.

**Escolhida:** A (rsync via SSH)

**Razão:** rsync é idempotente, evita `.git/` exposto no document root, não exige credencial do repositório no VPS. O script `deploy.sh` encapsula o comportamento de forma reprodutível e auditável. Alinhado com ADR-0001 ("VPS sem Docker", sem serviços externos de deploy no MVP).

**Reversibilidade:** fácil — trocar a estratégia de cópia no `deploy.sh` sem impacto no `deploy-staging.yml` além dos parâmetros.

---

### D2: Trigger do workflow — `workflow_run` vs `push` direto em `main`

**Opções consideradas:**
- **Opção A: `on: workflow_run` com `workflows: ["CI"]` e `branches: [main]`** — deploy só dispara se `ci.yml` completar com `conclusion: success`; prós: garante mecanicamente que código quebrado nunca vai para staging, atende AC-001 diretamente; contras: latência adicional (~5-10min aguardando CI completo).
- **Opção B: `on: push` em `main` diretamente** — deploy roda em paralelo com CI; prós: mais rápido; contras: código que quebra o CI pode ser deployado antes do CI terminar — viola AC-001 ("após CI verde").
- **Opção C: `workflow_dispatch` manual** — PM aciona manualmente; prós: controle total; contras: não é automatizado, contradiz o spec e AC-001.

**Escolhida:** A (`workflow_run`)

**Razão:** AC-001 exige que o deploy só ocorra após CI verde. `workflow_run` é o único trigger que garante isso mecanicamente sem polling. A latência adicional (~5-10min) é aceitável para um ambiente de staging.

**Reversibilidade:** fácil — alterar o bloco `on:` em `deploy-staging.yml`.

---

### D3: Reinício do Horizon — `horizon:terminate` vs `supervisorctl restart`

**Opções consideradas:**
- **Opção A: `php artisan horizon:terminate`** — Horizon finaliza workers em execução de forma graciosa; o Supervisor o reinicia automaticamente; prós: zero jobs perdidos (workers terminam a tarefa atual antes de sair), padrão oficial do Laravel Horizon; contras: requer Supervisor configurado com `autorestart=true`.
- **Opção B: `supervisorctl restart horizon`** — mata o processo imediatamente; prós: mais rápido; contras: jobs em execução podem ser perdidos ou ficar em estado inconsistente no Redis.
- **Opção C: `pkill -f horizon`** — kill direto sem Supervisor; contras: sem graceful shutdown, inseguro, Horizon não sobe automaticamente.

**Escolhida:** A (`php artisan horizon:terminate`)

**Razão:** AC-003 exige `horizon:status` retornando `running` após deploy. O `horizon:terminate` com Supervisor configurado (`autorestart=true`) é o único fluxo que garante Horizon reiniciado em estado `running` sem perda de jobs. Padrão da documentação oficial do Laravel Horizon.

**Reversibilidade:** fácil — alterar o step de restart no `deploy.sh`.

---

### D4: Logging JSON — canal `daily` com `JsonFormatter` vs `single` vs driver externo

**Opções consideradas:**
- **Opção A: Canal `single` com `JsonFormatter` do Monolog** — um arquivo `laravel.json` fixo, sem rotação; prós: `tail -1 storage/logs/laravel.json` trivial para AC-004; contras: arquivo cresce indefinidamente em produção.
- **Opção B: Canal `daily` com `JsonFormatter` e `filename` configurado** — rotação diária automática; prós: arquivo gerenciável para produção; o nome do arquivo do dia pode ser resolvido no AC-004 via `$(date +%Y-%m-%d)` ou symlink; contras: verificação do AC-004 requer apontar para o arquivo do dia correto.
- **Opção C: Driver externo (Papertrail, Logtail)** — logs em SaaS externo; contras: custo adicional, dependência externa, fora do escopo do MVP.

**Escolhida:** B (canal `daily` com `JsonFormatter`)

**Razão:** Canal `daily` é o padrão Laravel para staging/produção (rotação automática evita disco cheio). AC-004 é verificado com `tail -1 storage/logs/laravel-$(date +%Y-%m-%d).json` no VPS — explicitamente documentado na verificação do slice. `JsonFormatter` do Monolog é nativo, sem dependência adicional.

**Reversibilidade:** fácil — alteração em `config/logging.php`.

---

### D5: Provisioning do VPS — script idempotente vs documentação manual

**Opções consideradas:**
- **Opção A: Script `infra/scripts/provision-staging.sh`** — idempotente, instala PHP 8.4 + extensões, Nginx, Supervisor, Composer; prós: reprodutível, auditável, agente pode rodar novamente sem efeitos colaterais; contras: requer execução manual uma única vez (bootstrap).
- **Opção B: Documentação `infra/README.md` com passos manuais** — prós: mais simples de escrever; contras: não reprodutível, sujeito a erro humano, não auditável, viola P7.

**Escolhida:** A (script `provision-staging.sh`)

**Razão:** VPS pode precisar ser re-provisionado. Um script idempotente garante que o ambiente de staging pode ser reconstituído sem perda de documentação. Mitiga diretamente o risco documentado no spec ("VPS pode não ter PHP 8.4 instalado").

**Reversibilidade:** fácil — script é independente do fluxo de deploy.

---

## Mapeamento AC → arquivos

| AC | Descrição | Arquivos tocados | Como é verificado mecanicamente |
|---|---|---|---|
| AC-001 | Push para `main` com CI verde dispara deploy automaticamente | `.github/workflows/deploy-staging.yml` | Job `deploy` em status `success` na aba Actions após `workflow_run` de `ci.yml` com `conclusion: success` |
| AC-002 | `curl -s https://staging.kalibrium.com.br/` responde HTTP 200 com conteúdo Laravel | `infra/nginx/kalibrium-staging.conf`, `scripts/deploy.sh` | `curl -s -o /dev/null -w "%{http_code}" https://staging.kalibrium.com.br/` retorna `200` |
| AC-003 | `php artisan horizon:status` retorna `running` após deploy | `composer.json`, `infra/supervisor/horizon-staging.conf`, `scripts/deploy.sh` | Saída do comando no VPS contém a string `running` |
| AC-004 | `tail -1 storage/logs/laravel-$(date +%Y-%m-%d).json` retorna JSON válido | `config/logging.php` | `python3 -c "import json,sys; json.load(sys.stdin)"` aplicado ao output retorna exit 0 |
| AC-005 | `php artisan schedule:list` lista heartbeat e `schedule:run` executa sem erro | `routes/console.php`, `infra/crontab/staging.txt` | Exit code 0 em ambos os comandos no VPS |

---

## Novos arquivos

- `.github/workflows/deploy-staging.yml` — workflow de deploy contínuo; trigger `workflow_run` após `ci.yml` verde em `main`
- `scripts/deploy.sh` — sequência de deploy no VPS: maintenance mode, rsync (executado no runner), migrate, cache, horizon:terminate, up
- `infra/nginx/kalibrium-staging.conf` — virtual host Nginx para `staging.kalibrium.com.br`; document root `public/`, PHP-FPM socket, SSL via Let's Encrypt
- `infra/php-fpm/kalibrium-staging.conf` — pool PHP-FPM `kalibrium_staging`; `pm = dynamic`, limites conservadores para KVM 1 (1 vCPU, 4 GB RAM)
- `infra/supervisor/horizon-staging.conf` — programa Supervisor `horizon`; `autostart=true`, `autorestart=true`, `stopasgroup=true`
- `infra/crontab/staging.txt` — entrada cron para `www-data`: `* * * * * cd /var/www/kalibrium && php artisan schedule:run >> /dev/null 2>&1`
- `infra/scripts/provision-staging.sh` — bootstrap idempotente do VPS: PHP 8.4 + extensões, Nginx, Supervisor, Composer 2, permissões de `storage/` e `bootstrap/cache/`

## Arquivos modificados

- `config/logging.php` — adicionar canal `daily_json` com `Monolog\Formatter\JsonFormatter`; configurar como padrão via `LOG_CHANNEL=daily_json` no ambiente staging/production
- `composer.json` — adicionar `laravel/horizon:^5.0` em `require` (não `require-dev`)
- `routes/console.php` — adicionar job de heartbeat do Scheduler (AC-005)

## Schema / migrations

Nenhuma migration nova neste slice. O `deploy.sh` executa `php artisan migrate --force` para aplicar migrations pendentes de slices anteriores; este slice não adiciona tabelas.

## APIs / contratos

Nenhum endpoint novo. Exposições deste slice:
- URL pública `https://staging.kalibrium.com.br/` — retorna a aplicação Laravel existente (AC-002)
- Logs de deploy na aba Actions do GitHub (AC-001)

---

## Tasks numeradas

### TASK-001 — Criar workflow `deploy-staging.yml` (AC-001)

**Arquivo:** `.github/workflows/deploy-staging.yml`

Estrutura:
```yaml
on:
  workflow_run:
    workflows: ["CI"]
    types: [completed]
    branches: [main]

jobs:
  deploy:
    if: github.event.workflow_run.conclusion == 'success'
    runs-on: ubuntu-latest
    timeout-minutes: 15
```

Steps:
1. `actions/checkout@v4` com `ref: main`
2. Setup PHP 8.4 + Composer v2 + cache de vendor (apenas extensões de produção)
3. `composer install --no-dev --optimize-autoloader --no-interaction`
4. `npm ci && npm run build` (compilação de assets)
5. rsync para o VPS excluindo `.git/`, `node_modules/`, `storage/logs/`, `storage/framework/cache/`, `tests/`
6. SSH remoto executando `scripts/deploy.sh` no VPS

Secrets referenciados: `STAGING_SSH_KEY`, `STAGING_HOST`, `STAGING_USER`, `STAGING_PATH`.

---

### TASK-002 — Criar `scripts/deploy.sh` (AC-001, AC-002, AC-003)

**Arquivo:** `scripts/deploy.sh`

Sequência executada no VPS via SSH remoto (após rsync):
1. `php artisan down --retry=5`
2. `composer install --no-dev --optimize-autoloader --no-interaction`
3. `php artisan migrate --force`
4. `php artisan config:cache`
5. `php artisan route:cache`
6. `php artisan view:cache`
7. `php artisan event:cache`
8. `php artisan horizon:terminate`
9. `php artisan up`

Variáveis: `DEPLOY_PATH` (padrão `/var/www/kalibrium`), `PHP_BIN` (padrão `php`).

---

### TASK-003 — Configurar logging JSON (AC-004)

**Arquivo:** `config/logging.php`

Adicionar canal `daily_json`:
- `driver: daily`
- `path: storage_path('logs/laravel.json')`
- `level: env('LOG_LEVEL', 'debug')`
- `days: 14`
- `formatter: Monolog\Formatter\JsonFormatter::class`
- `formatter_with: ['appendNewline' => true]`

O arquivo gerado pelo Monolog com driver `daily` será `storage/logs/laravel-YYYY-MM-DD.json`. AC-004 verifica com `tail -1 storage/logs/laravel-$(date +%Y-%m-%d).json`.

Configurar `LOG_CHANNEL=daily_json` no `.env` de staging (injetado pelo workflow via secret `STAGING_ENV_FILE` ou gerado pelo `deploy.sh` a partir de variáveis de ambiente do runner).

---

### TASK-004 — Instalar e configurar Laravel Horizon (AC-003)

**Arquivos:** `composer.json`, `infra/supervisor/horizon-staging.conf`

1. `composer require laravel/horizon:^5.0`
2. `php artisan horizon:install` — gera `config/horizon.php` e publica assets em `public/vendor/horizon/`
3. Criar `infra/supervisor/horizon-staging.conf`:

```ini
[program:horizon]
command=php /var/www/kalibrium/artisan horizon
directory=/var/www/kalibrium
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/horizon.log
stopwait=3600
stopasgroup=true
killasgroup=true
```

4. `provision-staging.sh` copia para `/etc/supervisor/conf.d/horizon.conf` e executa `supervisorctl reread && supervisorctl update && supervisorctl start horizon`.

---

### TASK-005 — Configurar Scheduler com heartbeat (AC-005)

**Arquivos:** `routes/console.php`, `infra/crontab/staging.txt`

Adicionar em `routes/console.php`:

```php
Schedule::call(function (): void {
    cache()->put('scheduler.heartbeat', now()->toIso8601String(), seconds: 120);
})->everyMinute()->name('scheduler:heartbeat')->withoutOverlapping();
```

Criar `infra/crontab/staging.txt`:
```
* * * * * www-data cd /var/www/kalibrium && php artisan schedule:run >> /dev/null 2>&1
```

O `provision-staging.sh` instala esta entrada via `crontab -u www-data /var/www/kalibrium/infra/crontab/staging.txt`.

---

### TASK-006 — Criar configurações Nginx e PHP-FPM (AC-002)

**Arquivos:** `infra/nginx/kalibrium-staging.conf`, `infra/php-fpm/kalibrium-staging.conf`

Nginx (`kalibrium-staging.conf`):
- `server_name staging.kalibrium.com.br`
- `root /var/www/kalibrium/public`
- `index index.php`
- `location /` com `try_files $uri $uri/ /index.php?$query_string`
- Bloco PHP-FPM com socket `unix:/run/php/php8.4-fpm-kalibrium.sock`
- SSL via certificado Let's Encrypt (Certbot provisionado pelo `provision-staging.sh`)

PHP-FPM (`kalibrium-staging.conf`):
- Pool `kalibrium_staging`
- `listen = /run/php/php8.4-fpm-kalibrium.sock`
- `pm = dynamic`, `pm.max_children = 10`, `pm.start_servers = 2`, `pm.min_spare_servers = 1`, `pm.max_spare_servers = 4`

---

### TASK-007 — Criar `provision-staging.sh` (mitigação risco PHP 8.4)

**Arquivo:** `infra/scripts/provision-staging.sh`

Script idempotente (verifica existência antes de instalar cada componente):
1. `apt-get update && apt-get install -y nginx supervisor certbot python3-certbot-nginx curl`
2. Adiciona `ppa:ondrej/php`; instala `php8.4-fpm php8.4-cli php8.4-pgsql php8.4-redis php8.4-bcmath php8.4-mbstring php8.4-xml php8.4-zip php8.4-curl php8.4-intl php8.4-pcov`
3. Instala Composer 2 via `getcomposer.org/installer`
4. Cria `/var/www/kalibrium` com `chown -R www-data:www-data`
5. Copia `infra/nginx/kalibrium-staging.conf` para `/etc/nginx/sites-available/` e habilita
6. Copia `infra/php-fpm/kalibrium-staging.conf` para `/etc/php/8.4/fpm/pool.d/`
7. Copia `infra/supervisor/horizon-staging.conf` para `/etc/supervisor/conf.d/`
8. Instala entrada de crontab para `www-data`
9. Executa Certbot para SSL (apenas se certificado não existir)
10. Recarrega Nginx, PHP-FPM, Supervisor
11. Emite `echo "PROVISION OK — $(date)"` (verificável mecanicamente)

---

## Riscos e mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| VPS KVM 1 sem PHP 8.4 instalado | Alta (VPS novo) | Alto — deploy falha | TASK-007: `provision-staging.sh` idempotente; rodar antes do primeiro deploy |
| Porta 22 bloqueada no firewall do VPS para IPs do GitHub Actions | Média | Alto — workflow nunca conecta | Verificar painel Hostinger; IPs do GitHub Actions disponíveis em `https://api.github.com/meta` (campo `actions`) |
| `php artisan migrate --force` falha se banco não provisionado | Baixa (slice 002 mergeado) | Alto — deploy trava | Dependência atendida; `provision-staging.sh` pode validar conectividade PG antes do deploy |
| Secrets GitHub ausentes (`STAGING_SSH_KEY` etc.) | Média (configuração manual pré-requisito) | Alto — workflow falha com erro de autenticação | Checklist de pré-requisitos documentado em `infra/scripts/setup-secrets.md`; AC-001 só passa após secrets configurados |
| `horizon:terminate` sem Supervisor não reinicia o Horizon | Alta se Supervisor não configurado | Médio — AC-003 falha | TASK-004 configura Supervisor com `autorestart=true`; `provision-staging.sh` valida `supervisorctl status horizon` antes de encerrar |
| Certificado SSL ausente na primeira execução | Média | Médio — `curl https://` falha em AC-002 | `provision-staging.sh` executa Certbot; AC-002 pode ser verificado inicialmente via HTTP enquanto SSL está sendo provisionado |
| Assets não compilados no VPS (`public/build/` ausente) | Baixa | Médio — página carrega sem CSS/JS | TASK-001 inclui `npm ci && npm run build` no runner antes do rsync; `public/build/` incluído no rsync (não excluído) |

---

## Dependências de outros slices

| Slice | Dependência | Natureza |
|---|---|---|
| slice-001 | Scaffold Laravel 13 com `composer.json`, `artisan`, `routes/console.php`, `config/logging.php` base, estrutura de diretórios | Obrigatória — mergeada |
| slice-002 | PostgreSQL 18 e Redis configurados; variáveis `DB_*` e `REDIS_*` disponíveis no `.env` do VPS | Obrigatória — mergeada |
| slice-003 | `ci.yml` com workflow nomeado `"CI"` — o nome deve corresponder exatamente ao campo `workflows:` do trigger `workflow_run` | Obrigatória — mergeada |

**Pré-condições externas (requerem ação do PM/operador fora do agente):**
- VPS KVM 1 provisionado via `provision-staging.sh` (execução manual uma vez)
- DNS `staging.kalibrium.com.br` apontando para o IP do KVM 1
- Secrets `STAGING_SSH_KEY`, `STAGING_HOST`, `STAGING_USER`, `STAGING_PATH` configurados em `Settings > Secrets > Actions` no repositório GitHub

---

## Nota sobre ADR-0005 (CI/CD — pendente)

As decisões D1 (rsync via SSH) e D2 (`workflow_run`) são relevantes além deste slice — afetam a estratégia de deploy de produção (KVM 4, pós-MVP). Recomenda-se criar `docs/adr/0005-cicd-strategy.md` após aprovação deste plan para registrar essas decisões formalmente e remover ADR-0005 da tabela de pendentes em `docs/TECHNICAL-DECISIONS.md`.

---

## Fora de escopo deste plano (confirmando spec)

- Deploy para produção (KVM 4 — pós-MVP)
- Docker / containerização
- Rollback automatizado (rollback manual via `git revert` + re-push)
- Notificações de deploy (Slack/e-mail)
- Blue-green deployment
- CDN para assets estáticos
- Laravel Octane (Swoole/RoadRunner) — definido como evolução em ADR-0001 para quando throughput for gargalo
