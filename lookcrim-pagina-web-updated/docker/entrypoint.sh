#!/usr/bin/env bash
set -euo pipefail

: "${PORT:=8080}"

cd /var/www/html

echo "[LookCrime] container starting" >&1
echo "[LookCrime] APP_ENV=${APP_ENV:-unset} APP_DEBUG=${APP_DEBUG:-unset} LOG_CHANNEL=${LOG_CHANNEL:-unset}" >&1
echo "[LookCrime] time=$(date -Iseconds)" >&1

# Permisos (Cloud Run usa FS efímero, pero igual necesitamos escribir cache/logs)
mkdir -p \
	bootstrap/cache \
	storage/app/public \
	storage/framework/cache/data \
	storage/framework/sessions \
	storage/framework/views \
	storage/logs
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

# Generar config de nginx con el puerto de Cloud Run
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Symlink para storage (solo aplica si usás disco local/public)
php artisan storage:link >/dev/null 2>&1 || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
