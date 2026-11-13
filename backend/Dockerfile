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

# System libraries for the PHP extensions Laravel and its packages need,
# plus the PDF/OCR toolchain scripts/pdf_tools.py needs: Python 3, the
# Tesseract C library (tesserocr builds against it) and the Nepali and
# English language data. Without these, PDF preview and OCR fail on
# Render because config/pdf-tools.php defaults to a `python` binary
# that does not exist in this image.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl libcurl4-openssl-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    libonig-dev libxml2-dev libicu-dev libexif-dev \
    python3 python3-venv python3-dev build-essential pkg-config \
    libtesseract-dev libleptonica-dev tesseract-ocr tesseract-ocr-eng tesseract-ocr-nep \
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

# Provision the PDF/OCR runtime the same way scripts/setup-ocr.ps1 does
# on Windows: Python modules in one fixed directory, Nepali + English
# language data in another, then a `check` run that fails the build if
# any piece is missing. The venv interpreter is used so pip never hits
# Debian's externally-managed-environment block, and --target keeps the
# module directory independent of the Python minor version. The language
# data is copied out of the Debian tessdata directory (whose versioned
# path varies) so the config value below never has to guess it.
RUN python3 -m venv /opt/ocr-venv \
    && /opt/ocr-venv/bin/pip install --no-cache-dir --disable-pip-version-check \
        --target /opt/ocr-python -r scripts/requirements-pdf-tools.txt tesserocr==2.10.0 \
    && mkdir -p /opt/ocr-tessdata \
    && cp /usr/share/tesseract-ocr/*/tessdata/nep.traineddata /usr/share/tesseract-ocr/*/tessdata/eng.traineddata /opt/ocr-tessdata/ \
    && /opt/ocr-venv/bin/python scripts/pdf_tools.py check --modules /opt/ocr-python --tessdata /opt/ocr-tessdata

# Point config/pdf-tools.php at the provisioned runtime above. These are
# image defaults; Render dashboard env vars still override them, and
# local development keeps using storage/app/private/ocr-* instead.
ENV PDF_OCR_PYTHON=/opt/ocr-venv/bin/python
ENV PDF_OCR_MODULES=/opt/ocr-python
ENV PDF_OCR_TESSDATA=/opt/ocr-tessdata

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
