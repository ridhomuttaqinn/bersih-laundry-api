#!/bin/sh
set -eu
: "${APP_KEY:?Set APP_KEY in Render}"
: "${DB_URL:?Set DB_URL from Neon}"
: "${DEPLOY_TEST_TOKEN:?Set a long random DEPLOY_TEST_TOKEN}"
sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/10000/${PORT:-10000}/g" /etc/apache2/sites-available/000-default.conf
php artisan optimize:clear
php artisan migrate --force
if [ "${SEED_DEMO:-false}" = "true" ]; then php artisan db:seed --force; fi
php artisan config:cache
php artisan route:cache
exec apache2-foreground
