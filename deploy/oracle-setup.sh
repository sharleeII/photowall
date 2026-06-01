#!/bin/bash
# ============================================================
# Oracle Cloud Always Free — Photowall one-shot setup script
# Ubuntu 22.04 (Ampere ARM)
# Run as: sudo bash oracle-setup.sh YOUR_DOMAIN YOUR_ADMIN_PASS
# Example: sudo bash oracle-setup.sh 1-2-3-4.nip.io SuperSecret123
# ============================================================
set -euo pipefail

DOMAIN="${1:-CHANGE-ME.nip.io}"
ADMIN_PASS="${2:-CHANGE-ME}"
APP_DIR="/var/www/photowall"
REPO_URL="https://github.com/YOU/photowall.git"  # Update before running

echo "=== [1/10] System update ==="
apt-get update -qq && apt-get upgrade -y -qq

echo "=== [2/10] Install Nginx + PHP 8.2 + required extensions ==="
apt-get install -y -qq nginx php8.2-fpm php8.2-cli php8.2-sqlite3 \
    php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-intl \
    php8.2-curl git unzip certbot python3-certbot-nginx

echo "=== [3/10] Install Composer ==="
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "=== [4/10] Clone repo ==="
mkdir -p "$APP_DIR"
git clone "$REPO_URL" "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"

echo "=== [5/10] Install PHP dependencies ==="
cd "$APP_DIR"
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

echo "=== [6/10] Configure app_local.php ==="
SALT=$(php -r "echo bin2hex(random_bytes(32));")
cp "$APP_DIR/config/app_local.example.php" "$APP_DIR/config/app_local.php"
sed -i "s/CHANGE_ME/$SALT/g" "$APP_DIR/config/app_local.php"
# Set admin password securely
sed -i "s/'admin_password' => env.*/'admin_password' => env('PHOTOWALL_ADMIN_PASSWORD', '$ADMIN_PASS'),/" "$APP_DIR/config/app_local.php"

echo "=== [7/10] Initialize SQLite + migrations ==="
touch "$APP_DIR/config/photowall.sqlite"
chown www-data:www-data "$APP_DIR/config/photowall.sqlite"
sudo -u www-data "$APP_DIR/bin/cake" migrations migrate

echo "=== [8/10] Set permissions ==="
mkdir -p "$APP_DIR/webroot/files"
chown -R www-data:www-data "$APP_DIR/webroot/files" \
    "$APP_DIR/tmp" "$APP_DIR/logs"
chmod -R 775 "$APP_DIR/webroot/files" "$APP_DIR/tmp" "$APP_DIR/logs"

echo "=== [9/10] Nginx config ==="
sed "s/XX-XX-XX-XX.nip.io/$DOMAIN/g" "$APP_DIR/deploy/nginx.conf" \
    > /etc/nginx/sites-available/photowall
ln -sf /etc/nginx/sites-available/photowall /etc/nginx/sites-enabled/photowall
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "=== [10/10] SSL with Let's Encrypt ==="
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m admin@example.com

echo "=== [BACKUP] Setup cron rsync every 5 min ==="
BACKUP_DIR="/var/backups/photowall-files"
mkdir -p "$BACKUP_DIR"
(crontab -l 2>/dev/null; echo "*/5 * * * * rsync -a --delete $APP_DIR/webroot/files/ $BACKUP_DIR/ >> /var/log/photowall-rsync.log 2>&1") | crontab -

echo ""
echo "=========================================="
echo "  PHOTOWALL LIVE AT: https://$DOMAIN"
echo "  Admin login at:    https://$DOMAIN/admin"
echo "  Admin password:    $ADMIN_PASS"
echo "  CHANGE PASSWORD before the event!"
echo "=========================================="
