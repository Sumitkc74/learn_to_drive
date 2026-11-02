# Learn to Drive backend — Docker image for Render's free web service.
# Build context is backend/ (see render.yaml at the repo root).
# Stage 1 builds the Vite frontend assets with Node 22 (Vite 8 requires it).
FROM node:22 AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Stage 2 runs Laravel on Apache + PHP 8.3.
FROM php:8.3-apache
WORKDIR /var/www/html

# System libraries for the PHP extensions Laravel and its packages need.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl libcurl4-openssl-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    libonig-dev libxml2-dev libicu-dev libexif-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring zip gd exif bcmath intl xml curl fileinfo \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Application source. .dockerignore keeps vendor, node_modules, .env and
# local build output out of the image; built assets come from stage 1.
COPY . /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

# Composer's post-autoload script runs artisan, which needs a .env file to
# exist at build time. This is the example file only — no secrets — and
# Render's runtime env vars override it when the container boots.
RUN cp .env.example .env

# .dockerignore excludes storage/framework/views (and cache/sessions/logs),
# and git does not track empty directories, so the image would otherwise
# have no storage/framework/views at all. config/view.php computes the
# compiled-view path with realpath(), which returns false for a missing
# directory — and Blade then throws "Please provide a valid cache path"
# on every page render. Create the directories explicitly.
RUN mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Apache must serve Laravel's public/ directory, not the repo root.
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/public>|' \
        /etc/apache2/apache2.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
