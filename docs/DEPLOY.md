# Party CRM — Deployment Runbook

Target platform: **Ubuntu 24.04 LTS VPS** (minimum 2 CPU, 4 GB RAM, 40 GB SSD).

---

## 1. Server provisioning

```bash
# 1.1 Initial update
apt update && apt upgrade -y

# 1.2 Packages
apt install -y \
  nginx php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis \
  mariadb-server redis-server supervisor certbot python3-certbot-nginx \
  unattended-upgrades fail2ban ufw git curl
```

## 2. Hardening

```bash
# 2.1 Firewall — allow only SSH, HTTP, HTTPS
ufw default deny incoming
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# 2.2 fail2ban (uses defaults: 5 failures → 10-minute ban)
systemctl enable --now fail2ban

# 2.3 Unattended security updates
dpkg-reconfigure -plow unattended-upgrades

# 2.4 SSH keys only — disable password auth
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl reload ssh
```

## 3. MariaDB setup

```bash
mariadb-secure-installation   # answer prompts, set root password

mariadb -u root -p <<'SQL'
CREATE DATABASE party_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON party_crm.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 4. Deploy user & application

```bash
# 4.1 Create deploy user
useradd -m -s /bin/bash deploy
su - deploy

# 4.2 Clone / extract the release (from release archive)
# Option A — from git
git clone https://github.com/YOUR_ORG/party-crm.git /home/deploy/app
cd /home/deploy/app

# Option B — from release archive
# tar xzf party-crm-1.0.0.tar.gz
# mv party-crm-1.0.0 /home/deploy/app

# 4.3 .env
cp .env.example .env
chmod 600 .env
# Edit .env: APP_KEY, APP_URL, DB_*, MAIL_*, SESSION_LIFETIME=60, APP_DEBUG=false
php artisan key:generate
# ALSO generate: BLIND_INDEX_KEY=$(php artisan tinker --execute "echo base64_encode(random_bytes(32));")

# 4.4 Install dependencies (production)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4.5 Storage permissions
chown -R deploy:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> **Back up APP_KEY and BLIND_INDEX_KEY** in a secure password manager immediately.
> Losing BLIND_INDEX_KEY means member email/phone search will stop working permanently.

## 5. Nginx virtual host

Create `/etc/nginx/sites-available/party-crm`:

```nginx
server {
    listen 80;
    server_name your-party-domain.org;
    root /home/deploy/app/public;
    index index.php;

    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
ln -s /etc/nginx/sites-available/party-crm /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# TLS with Let's Encrypt
certbot --nginx -d your-party-domain.org
```

## 6. Queue worker (Supervisor)

Create `/etc/supervisor/conf.d/party-crm-worker.conf`:

```ini
[program:party-crm-worker]
command=/usr/bin/php /home/deploy/app/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
user=deploy
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/home/deploy/app/storage/logs/worker.log
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start party-crm-worker
```

## 7. Scheduler cron

```bash
# As user deploy (crontab -e)
* * * * * cd /home/deploy/app && php artisan schedule:run >> /dev/null 2>&1
```

## 8. First installation

After deployment, navigate to `https://your-party-domain.org/install` and complete the web installer.

Alternatively run from the server:

```bash
php artisan party:install \
  --party-name="Party Name" \
  --short-name="ABBREV" \
  --membership-prefix="PRE" \
  --locales="en,fr" \
  --default-locale="fr" \
  --currency="XOF" \
  --timezone="Africa/Dakar" \
  --admin-name="Admin Name" \
  --admin-email="admin@party.org" \
  --admin-password="SecurePass2026!" \
  --no-interaction
```

## 9. Backups

Nightly backup script `/home/deploy/backup.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
DEST="/home/deploy/backups/$(date +%F)"
mkdir -p "${DEST}"

# DB dump
mysqldump -u crm_user -p'CHANGE_THIS_PASSWORD' party_crm | gzip > "${DEST}/db.sql.gz"

# Storage & .env
tar czf "${DEST}/storage.tar.gz" /home/deploy/app/storage
cp /home/deploy/app/.env "${DEST}/.env.bak"

# Remove backups older than 30 days
find /home/deploy/backups -maxdepth 1 -mtime +30 -type d -exec rm -rf {} +

echo "Backup complete: ${DEST}"
```

Add to cron (as deploy):
```
0 2 * * * /home/deploy/backup.sh >> /home/deploy/backups/backup.log 2>&1
```

**Copy backups to a second location** in the jurisdiction approved by the party (e.g. SFTP to a secondary server or encrypted cloud storage). Never store backups only on the same server.

### Restore procedure

```bash
# 1. Put app in maintenance mode
php artisan down

# 2. Restore DB
gunzip < /home/deploy/backups/YYYY-MM-DD/db.sql.gz | mysql -u crm_user -p party_crm

# 3. Restore storage
tar xzf /home/deploy/backups/YYYY-MM-DD/storage.tar.gz -C /

# 4. Restore .env if needed
cp /home/deploy/backups/YYYY-MM-DD/.env.bak /home/deploy/app/.env

# 5. Clear caches and bring back up
php artisan config:clear && php artisan up
```

## 10. Updates

```bash
# Using the deploy script (from git)
bash /home/deploy/app/deploy.sh

# Using a release archive
php artisan party:update /path/to/party-crm-1.1.0.zip
```
