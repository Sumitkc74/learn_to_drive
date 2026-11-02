#!/bin/sh
set -e

# Render routes traffic to $PORT (10000 by default). Apache defaults to 80,
# so rebind it on every boot before anything else listens.
PORT="${PORT:-80}"
sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# Belt and braces for the Dockerfile mkdir: the compiled-view path is
# resolved with realpath() when config is cached below, so these
# directories must exist before config:cache runs.
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs

# Render sets env vars at runtime, so cache config on boot, not at build time.
php artisan config:cache

# First boot creates the schema; later boots are a no-op for migrate, and
# the seeder is idempotent (it skips the admin if one already exists, and
# does nothing at all unless SEED_ADMIN_PASSWORD is set).
php artisan migrate --force
php artisan db:seed --force || true
php artisan storage:link || true

exec "$@"
