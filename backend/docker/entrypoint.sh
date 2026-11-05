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
# Apache serves requests as www-data, but the artisan commands below run as
# root. If root creates anything under storage first (it does not today, but
# a future command might), www-data can no longer write beside it — and the
# file cache then fails exactly that way (see the queue worker note below).
# So hand the whole tree to www-data on every boot.
chown -R www-data:www-data storage bootstrap/cache || true

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
# The worker MUST run as www-data, not root: it writes to the file cache
# (its heartbeat does a Cache::put on every loop), and Apache serves as
# www-data. When the worker ran as root, the cache shard directories it
# created were root-owned 0755, so www-data could not create files inside
# them — and the login throttle (RateLimiter on the file cache) then died
# with "fopen(.../storage/framework/cache/data/...): No such file or
# directory" on every login submit, before any database query ran.
# su -s overrides www-data's nologin shell for this one command.
( while true; do su www-data -s /bin/sh -c 'php artisan queue:work pdf-extraction --queue=pdf-extraction --timeout=180 --sleep=3' || true; sleep 5; done ) &

exec "$@"
