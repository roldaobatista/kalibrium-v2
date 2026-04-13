#!/usr/bin/env bash
# provision-staging.sh — Bootstrap idempotente do VPS de staging (KVM 1 Hostinger)
# Executar uma vez como root antes do primeiro deploy.
# Idempotente: verifica existência de cada componente antes de instalar.
set -euo pipefail

DEPLOY_PATH="${DEPLOY_PATH:-/var/www/kalibrium}"
DOMAIN="${DOMAIN:-staging.kalibrium.com.br}"
PHP_VERSION="8.4"

echo "=== Provision Staging — $(date --iso-8601=seconds) ==="
echo "DEPLOY_PATH=${DEPLOY_PATH}"
echo "DOMAIN=${DOMAIN}"

# ---------------------------------------------------------------------------
# 1. Dependências base
# ---------------------------------------------------------------------------
echo "[1/10] Atualizando apt e instalando dependências base..."
apt-get update -qq
apt-get install -y -qq \
    curl \
    git \
    nginx \
    supervisor \
    certbot \
    python3-certbot-nginx \
    unzip \
    acl

# ---------------------------------------------------------------------------
# 2. PHP 8.4 via repositório ondrej/php
# ---------------------------------------------------------------------------
if ! command -v php8.4 &>/dev/null; then
    echo "[2/10] Instalando PHP ${PHP_VERSION}..."
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    apt-get install -y -qq \
        "php${PHP_VERSION}-fpm" \
        "php${PHP_VERSION}-cli" \
        "php${PHP_VERSION}-pgsql" \
        "php${PHP_VERSION}-redis" \
        "php${PHP_VERSION}-bcmath" \
        "php${PHP_VERSION}-mbstring" \
        "php${PHP_VERSION}-xml" \
        "php${PHP_VERSION}-zip" \
        "php${PHP_VERSION}-curl" \
        "php${PHP_VERSION}-intl"
else
    echo "[2/10] PHP ${PHP_VERSION} já instalado — pulando."
fi

# ---------------------------------------------------------------------------
# 3. Composer 2
# ---------------------------------------------------------------------------
if ! command -v composer &>/dev/null; then
    echo "[3/10] Instalando Composer 2..."
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo 'ERROR: Invalid installer checksum' >&2
        rm composer-setup.php
        exit 1
    fi
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
else
    echo "[3/10] Composer já instalado — atualizando para v2..."
    composer self-update --2 --quiet || true
fi

# ---------------------------------------------------------------------------
# 4. Diretório da aplicação
# ---------------------------------------------------------------------------
echo "[4/10] Criando ${DEPLOY_PATH}..."
mkdir -p "${DEPLOY_PATH}"
chown -R www-data:www-data "${DEPLOY_PATH}"
chmod -R 755 "${DEPLOY_PATH}"
setfacl -R -m u:www-data:rwx "${DEPLOY_PATH}" 2>/dev/null || true

# ---------------------------------------------------------------------------
# 5. Nginx — virtual host
# ---------------------------------------------------------------------------
echo "[5/10] Configurando Nginx..."
NGINX_CONF="/etc/nginx/sites-available/kalibrium-staging.conf"
cp "$(dirname "$0")/../nginx/kalibrium-staging.conf" "${NGINX_CONF}"
ln -sf "${NGINX_CONF}" /etc/nginx/sites-enabled/kalibrium-staging.conf
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# ---------------------------------------------------------------------------
# 6. PHP-FPM — pool
# ---------------------------------------------------------------------------
echo "[6/10] Configurando PHP-FPM pool..."
cp "$(dirname "$0")/../php-fpm/kalibrium-staging.conf" \
    "/etc/php/${PHP_VERSION}/fpm/pool.d/kalibrium-staging.conf"
systemctl restart "php${PHP_VERSION}-fpm"

# ---------------------------------------------------------------------------
# 7. Supervisor — Horizon
# ---------------------------------------------------------------------------
echo "[7/10] Configurando Supervisor para Horizon..."
cp "$(dirname "$0")/../supervisor/horizon-staging.conf" \
    /etc/supervisor/conf.d/horizon.conf
supervisorctl reread
supervisorctl update

# ---------------------------------------------------------------------------
# 8. Crontab do Scheduler
# ---------------------------------------------------------------------------
echo "[8/10] Instalando crontab do Scheduler..."
crontab -u www-data "$(dirname "$0")/../crontab/staging.txt"

# ---------------------------------------------------------------------------
# 9. SSL via Let's Encrypt (apenas se certificado ainda não existe)
# ---------------------------------------------------------------------------
CERT_PATH="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
if [ ! -f "${CERT_PATH}" ]; then
    echo "[9/10] Emitindo certificado SSL via Certbot..."
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos \
        -m "devops@kalibrium.com.br" --redirect
else
    echo "[9/10] Certificado SSL já existe — pulando Certbot."
fi

# ---------------------------------------------------------------------------
# 10. Verificação final
# ---------------------------------------------------------------------------
echo "[10/10] Verificação final..."
php${PHP_VERSION} --version
nginx -v
supervisorctl status horizon || true

echo ""
echo "PROVISION OK — $(date --iso-8601=seconds)"
