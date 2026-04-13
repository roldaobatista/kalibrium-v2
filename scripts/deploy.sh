#!/usr/bin/env bash
set -euo pipefail

# Deploy script executado no VPS via SSH remoto após rsync.
# Variáveis configuráveis:
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/kalibrium}"
PHP_BIN="${PHP_BIN:-php}"

cd "${DEPLOY_PATH}"

echo "[deploy] Ativando modo manutenção..."
"${PHP_BIN}" artisan down --retry=5

echo "[deploy] Instalando dependências PHP (produção)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[deploy] Executando migrations..."
"${PHP_BIN}" artisan migrate --force

echo "[deploy] Gerando caches de configuração..."
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan event:cache

echo "[deploy] Reiniciando Horizon graciosamente..."
"${PHP_BIN}" artisan horizon:terminate

echo "[deploy] Desativando modo manutenção..."
"${PHP_BIN}" artisan up

echo "[deploy] Deploy concluído em $(date --iso-8601=seconds)"
