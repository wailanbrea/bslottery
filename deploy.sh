#!/usr/bin/env bash
# BSLotery — Production Deploy Script (Linux)
# Usage: bash deploy.sh [--skip-migrate] [--skip-seed]
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$APP_DIR"

echo "=== BSLotery Deploy ==="
echo "Dir: $APP_DIR"
echo "PHP: $(php -r 'echo PHP_VERSION;')"

# ── Composer ──────────────────────────────────────────────────────────────────
echo "[1/8] Installing Composer dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── Config cache ──────────────────────────────────────────────────────────────
echo "[2/8] Caching config, routes, views, events..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── Migrations ────────────────────────────────────────────────────────────────
if [[ "${1:-}" != "--skip-migrate" ]]; then
    echo "[3/8] Running migrations..."
    php artisan migrate --force
else
    echo "[3/8] Migrations skipped."
fi

# ── Seeding (first deploy only) ───────────────────────────────────────────────
if [[ "${1:-}" == "--seed" || "${2:-}" == "--seed" ]]; then
    echo "[4/8] Seeding permissions and roles..."
    php artisan db:seed --class=PermissionSeeder --force
    php artisan db:seed --class=RoleSeeder --force
    php artisan db:seed --class=AccountingAccountSeeder --force
    echo "      Run manually: php artisan db:seed --class=DominicanLotteryCatalogSeeder"
else
    echo "[4/8] Seeding skipped (pass --seed for first deploy)."
fi

# ── Storage link ──────────────────────────────────────────────────────────────
echo "[5/8] Ensuring storage link..."
php artisan storage:link || true

# ── Queue restart ─────────────────────────────────────────────────────────────
echo "[6/8] Restarting queue workers..."
php artisan queue:restart || true

# ── Permissions ───────────────────────────────────────────────────────────────
echo "[7/8] Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# ── Health check ──────────────────────────────────────────────────────────────
echo "[8/8] Health check..."
php artisan about --only=environment 2>/dev/null | grep -E "App|PHP|Cache|Queue" || true

echo ""
echo "✓ Deploy complete."
echo ""
echo "Post-deploy reminders:"
echo "  - Cron: * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo "  - Worker: php artisan queue:work --queue=default,reports,winners,sync,notifications --sleep=3 --tries=3"
echo "  - OPcache: ensure opcache.enable=1 in php.ini"
echo "  - HTTPS: configure nginx/Apache for SSL + HSTS"
