#!/usr/bin/env bash
# deploy.sh — Idempotent Party CRM update script
# Run as the deploy user: bash deploy.sh
# Requires: PHP 8.3, Composer, npm
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP="${PHP:-php}"
ARTISAN="${PHP} artisan"

echo "[deploy] Party CRM — $(date '+%Y-%m-%d %H:%M:%S')"

cd "${APP_DIR}"

# Check APP_KEY is set
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
  echo "[deploy] ⚠  APP_KEY not set. Run: php artisan key:generate"
  exit 1
fi

echo "[deploy] Pulling latest code…"
git pull --ff-only

echo "[deploy] Installing PHP dependencies…"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "[deploy] Building frontend assets…"
npm ci --silent
npm run build

echo "[deploy] Building mobile PWA…"
(cd mobile && npm ci --silent && npm run build)

echo "[deploy] Entering maintenance mode…"
${ARTISAN} down --retry=5 --refresh=10 --render="errors::503" || true

echo "[deploy] Running database migrations…"
${ARTISAN} migrate --force

echo "[deploy] Clearing and rebuilding caches…"
${ARTISAN} config:clear
${ARTISAN} route:clear
${ARTISAN} view:clear
${ARTISAN} event:clear
${ARTISAN} config:cache
${ARTISAN} route:cache

echo "[deploy] Restarting queue workers…"
${ARTISAN} queue:restart

echo "[deploy] Exiting maintenance mode…"
${ARTISAN} up

echo "[deploy] Done — $(date '+%Y-%m-%d %H:%M:%S')"
