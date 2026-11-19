#!/usr/bin/env bash
# install.sh — One-command Party CRM server setup for a fresh Ubuntu 24.04 VPS
# Usage: bash install.sh <domain> [release-archive.tar.gz]
#
# This script is idempotent: re-running it updates the existing configuration.
set -euo pipefail

DOMAIN="${1:-}"
ARCHIVE="${2:-}"
APP_DIR="/home/deploy/app"
DEPLOY_USER="deploy"
DB_NAME="party_crm"
DB_USER="crm_user"
DB_PASS="$(openssl rand -hex 24)"
PHP_VERSION="8.3"

if [[ -z "${DOMAIN}" ]]; then
  echo "Usage: bash install.sh <domain> [release-archive.tar.gz]"
  exit 1
fi

step() { echo -e "\n\033[1;34m→ $*\033[0m"; }

step "Updating system packages"
apt-get update -qq && apt-get upgrade -y -qq

step "Installing required packages"
apt-get install -y -qq \
  nginx "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" \
  "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-zip" "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-intl" \
  "php${PHP_VERSION}-redis" mariadb-server redis-server supervisor certbot \
  python3-certbot-nginx unattended-upgrades fail2ban ufw git curl composer

step "Hardening: firewall"
ufw --force default deny incoming
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

step "Hardening: fail2ban"
systemctl enable --now fail2ban

step "Hardening: unattended security updates"
dpkg-reconfigure -plow unattended-upgrades 2>/dev/null || true

step "Setting up MariaDB"
systemctl enable --now mariadb
if ! mariadb -u "${DB_USER}" -p"${DB_PASS}" -e "SELECT 1" "${DB_NAME}" 2>/dev/null; then
  mariadb -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  echo "DB_PASS=${DB_PASS}" >> /root/.party_crm_secrets
  chmod 600 /root/.party_crm_secrets
fi

step "Creating deploy user"
id -u "${DEPLOY_USER}" &>/dev/null || useradd -m -s /bin/bash "${DEPLOY_USER}"

step "Installing application"
if [[ -n "${ARCHIVE}" && -f "${ARCHIVE}" ]]; then
  mkdir -p "${APP_DIR}"
  tar xzf "${ARCHIVE}" -C "${APP_DIR}" --strip-components=1
elif [[ ! -d "${APP_DIR}/.git" ]]; then
  echo "No archive provided and no existing app found at ${APP_DIR}."
  echo "Provide a release archive: bash install.sh ${DOMAIN} party-crm-1.0.0.tar.gz"
  exit 1
fi

chown -R "${DEPLOY_USER}":"${DEPLOY_USER}" "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" 2>/dev/null || true

step "Setting up Nginx"
cat > /etc/nginx/sites-available/party-crm <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \\.php$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\\.(?!well-known).* { deny all; }
}
NGINX

ln -sf /etc/nginx/sites-available/party-crm /etc/nginx/sites-enabled/party-crm
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

step "Obtaining TLS certificate"
certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "admin@${DOMAIN}" || \
  echo "⚠  Certbot failed — set up TLS manually or ensure DNS is pointing to this server."

step "Setting up Supervisor queue worker"
cat > /etc/supervisor/conf.d/party-crm-worker.conf <<SUPERVISOR
[program:party-crm-worker]
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
user=${DEPLOY_USER}
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
SUPERVISOR
supervisorctl reread && supervisorctl update

step "Done"
echo ""
echo "┌─────────────────────────────────────────────────────────────────┐"
echo "│  Party CRM server setup complete                                │"
echo "│                                                                 │"
echo "│  Next: visit https://${DOMAIN}/install to finish setup.         │"
echo "│                                                                 │"
echo "│  DB credentials saved to: /root/.party_crm_secrets             │"
echo "│  Keep this file secure and delete after recording credentials. │"
echo "└─────────────────────────────────────────────────────────────────┘"
