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
# Populate the real learning content (questions, tutorials, exam info).
# Deliberately NOT wired into DatabaseSeeder: the test suite seeds
# DatabaseSeeder for every test and assumes an admin-only baseline, so
# seeding content there breaks 25 tests. This seeder is idempotent
# (firstOrCreate on natural keys), so re-running it on every boot never
# duplicates rows.
php artisan db:seed --class=RealContentSeeder --force || true
php artisan storage:link || true

# AI screening and PDF jobs pin the pdf-extraction database connection, so
# they never run on the default sync queue. Render free has no worker
# service, so run that worker in this same container. The loop restarts it
# if it ever exits, and it keeps running after the exec below because it
# is no longer a child of this shell once Apache replaces it.
( while true; do php artisan queue:work pdf-extraction --queue=pdf-extraction --timeout=180 --sleep=3 || true; sleep 5; done ) &

exec "$@"
