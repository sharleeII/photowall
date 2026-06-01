#!/bin/bash
# ============================================================
# Photowall — VPS setup (DigitalOcean / Hetzner / cualquier VPS Ubuntu 22.04)
#
# USO:
#   sudo bash vps-setup.sh TU_IP_O_DOMINIO TU_PASSWORD_ADMIN TU_EMAIL
#
# EJEMPLOS:
#   # Con nip.io (sin dominio propio):
#   sudo bash vps-setup.sh 1-2-3-4.nip.io MiFiesta2026! yo@gmail.com
#
#   # Con dominio propio:
#   sudo bash vps-setup.sh fotos.mifiesta.com MiFiesta2026! yo@gmail.com
#
# ANTES DE CORRER:
#   1. Sube el proyecto a GitHub y pon la URL en REPO_URL abajo.
#   2. Si usas nip.io, reemplaza la IP con formato 1-2-3-4 (guiones, no puntos).
# ============================================================
set -euo pipefail

DOMAIN="${1:?Falta el dominio. Ej: 1-2-3-4.nip.io}"
ADMIN_PASS="${2:?Falta la contrasena admin. Ej: MiFiesta2026!}"
EMAIL="${3:-admin@example.com}"
APP_DIR="/var/www/photowall"

# ---- CAMBIA ESTO con tu URL de GitHub ----
REPO_URL="https://github.com/TU_USUARIO/photowall.git"
# ------------------------------------------

echo ""
echo "================================================="
echo "  Photowall Deploy"
echo "  Dominio : $DOMAIN"
echo "  App dir : $APP_DIR"
echo "================================================="
echo ""

# [1] Update system
echo "[1/9] Actualizando sistema..."
apt-get update -qq && apt-get upgrade -y -qq

# [2] Install stack
echo "[2/9] Instalando Nginx + PHP 8.2 + extensiones..."
apt-get install -y -qq \
    nginx \
    php8.2-fpm php8.2-cli \
    php8.2-sqlite3 php8.2-gd php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-intl php8.2-curl \
    git unzip curl \
    certbot python3-certbot-nginx

# [3] Composer
echo "[3/9] Instalando Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# [4] Clone
echo "[4/9] Clonando repo..."
if [ -d "$APP_DIR/.git" ]; then
    echo "  Ya existe — haciendo git pull"
    cd "$APP_DIR" && git pull
else
    mkdir -p "$(dirname $APP_DIR)"
    git clone "$REPO_URL" "$APP_DIR"
fi
chown -R www-data:www-data "$APP_DIR"

# [5] Dependencies
echo "[5/9] Instalando dependencias PHP..."
cd "$APP_DIR"
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction -q

# [6] Config
echo "[6/9] Configurando app_local.php..."
if [ ! -f "$APP_DIR/config/app_local.php" ]; then
    SALT=$(php -r "echo bin2hex(random_bytes(32));")
    cp "$APP_DIR/config/app_local.example.php" "$APP_DIR/config/app_local.php"
    # Replace salt placeholder
    sed -i "s/'salt' => env('SECURITY_SALT', 'CHANGE_ME')/'salt' => env('SECURITY_SALT', '$SALT')/" \
        "$APP_DIR/config/app_local.php"
    # Replace admin password placeholder
    sed -i "s/'admin_password' => env('PHOTOWALL_ADMIN_PASSWORD', 'CHANGE_ME')/'admin_password' => env('PHOTOWALL_ADMIN_PASSWORD', '${ADMIN_PASS//\'/\'\\\'\'}')/g" \
        "$APP_DIR/config/app_local.php"
    echo "  app_local.php creado"
else
    echo "  app_local.php ya existe — no se sobreescribe"
fi

# [7] SQLite + migrations
echo "[7/9] Inicializando BD SQLite y migraciones..."
touch "$APP_DIR/config/photowall.sqlite"
chown www-data:www-data "$APP_DIR/config/photowall.sqlite"
chmod 664 "$APP_DIR/config/photowall.sqlite"
sudo -u www-data "$APP_DIR/bin/cake" migrations migrate

# [8] Permisos de carpetas
echo "[8/9] Configurando permisos..."
mkdir -p "$APP_DIR/webroot/files"
chown -R www-data:www-data \
    "$APP_DIR/webroot/files" \
    "$APP_DIR/tmp" \
    "$APP_DIR/logs" \
    "$APP_DIR/config/photowall.sqlite"
chmod -R 775 "$APP_DIR/webroot/files" "$APP_DIR/tmp" "$APP_DIR/logs"

# [9] Nginx + SSL
echo "[9/9] Configurando Nginx y SSL..."
# Nginx vhost
cat > /etc/nginx/sites-available/photowall << NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    root $APP_DIR/webroot;
    index index.php;

    client_max_body_size 25M;

    location ~* \.(jpg|jpeg|png|gif|webp|ico|css|js|woff2?)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    location ~* ^/(config|src|vendor|tests|tmp)/ {
        deny all;
    }

    access_log /var/log/nginx/photowall.access.log;
    error_log  /var/log/nginx/photowall.error.log;
}
NGINX

ln -sf /etc/nginx/sites-available/photowall /etc/nginx/sites-enabled/photowall
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# SSL — solo si no es IP directa
if [[ "$DOMAIN" == *"."* ]] && [[ "$DOMAIN" != *.*.*.* ]]; then
    echo "  Obteniendo certificado SSL Let's Encrypt para $DOMAIN..."
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect || \
        echo "  WARN: certbot fallo — revisa que el dominio apunte a este servidor"
else
    echo "  SKIP SSL: $DOMAIN parece IP directa. Usa nip.io para SSL gratuito."
fi

# Backup cron
echo "[+] Configurando backup automatico de fotos cada 5 min..."
BACKUP_DIR="/var/backups/photowall-files"
mkdir -p "$BACKUP_DIR"
(crontab -l 2>/dev/null | grep -v photowall; \
 echo "*/5 * * * * rsync -a $APP_DIR/webroot/files/ $BACKUP_DIR/ 2>>/var/log/photowall-rsync.log") \
 | crontab -

echo ""
echo "================================================="
echo ""
echo "  PHOTOWALL LIVE"
echo ""
echo "  URL publica :  https://$DOMAIN"
echo "  Admin panel :  https://$DOMAIN/admin"
echo "  Contrasena  :  $ADMIN_PASS"
echo ""
echo "  QR apunta a -> https://$DOMAIN/e/{slug}"
echo "  Proyector   -> https://$DOMAIN/e/{slug}/wall"
echo ""
echo "  Backup fotos -> $BACKUP_DIR (cron cada 5min)"
echo ""
echo "================================================="
